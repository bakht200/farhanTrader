import { db, CACHE_VERSION, getMeta, setMeta, pendingOutboxCount } from './db';
import { isOnline, markOfflineFromError, onConnectivityChange } from './connectivity';
import { getPendingOutbox, markOutboxAcked, markOutboxConflict } from './outbox';
import { broadcast } from './broadcast';
import { getLocalSession, setLocalSession } from './authVault';

const PULL_INTERVAL_MS = 5 * 60 * 1000;
let syncing = false;
let pullTimer = null;
let isLeader = false;

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function api(url, options = {}) {
    try {
        const res = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf(),
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });

        if (res.status === 401) {
            broadcast('auth-required', {});
            throw new Error('Session expired. Please log in online.');
        }

        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            throw new Error(body.message || `Request failed (${res.status})`);
        }

        return res.json();
    } catch (err) {
        if (err.name === 'TypeError' || /Failed to fetch|NetworkError/i.test(err.message || '')) {
            markOfflineFromError();
        }
        throw err;
    }
}

async function replaceTable(table, rows) {
    await db.table(table).clear();
    if (rows?.length) {
        await db.table(table).bulkPut(rows);
    }
}

/**
 * Apply branch stock name/price/qty overrides onto cached products.
 * Ensures Branch 2 rate changes show up offline even when master products row is unchanged.
 */
async function applyBranchStockOverridesToProducts(stocks) {
    if (!stocks?.length) {
        return;
    }

    for (const stock of stocks) {
        const productId = Number(stock.product_id);
        if (!productId) {
            continue;
        }
        const product = await db.products.get(productId);
        if (!product) {
            continue;
        }

        const patch = {};
        if (stock.display_name != null && stock.display_name !== '') {
            patch.name = stock.display_name;
        }
        if (stock.purchase_price != null && stock.purchase_price !== '') {
            patch.purchase_price = stock.purchase_price;
        }
        if (stock.selling_price != null && stock.selling_price !== '') {
            patch.selling_price = stock.selling_price;
        }
        if (stock.retail_price != null && stock.retail_price !== '') {
            patch.retail_price = stock.retail_price;
        }
        if (stock.wholesale_price != null && stock.wholesale_price !== '') {
            patch.wholesale_price = stock.wholesale_price;
        }
        if (stock.selling_type) {
            patch.selling_type = stock.selling_type;
        }
        if (stock.stock_quantity != null) {
            patch.stock_quantity = stock.stock_quantity;
        } else if (stock.quantity != null) {
            patch.stock_quantity = stock.quantity;
        }

        if (Object.keys(patch).length) {
            await db.products.update(productId, patch);
        }
    }
}

export async function hydrateFromBootstrap(data) {
    await db.transaction(
        'rw',
        db.products,
        db.categories,
        db.units,
        db.customers,
        db.suppliers,
        db.sales,
        db.saleItems,
        db.orders,
        db.expenses,
        db.invoices,
        db.branches,
        db.productUnits,
        db.unitConversions,
        db.branchStocks,
        db.meta,
        async () => {
            await replaceTable('products', data.products || []);
            await replaceTable('categories', data.categories || []);
            await replaceTable('units', data.units || []);
            await replaceTable('customers', data.customers || []);
            await replaceTable('suppliers', data.suppliers || []);
            await replaceTable('sales', data.sales || []);
            await replaceTable('saleItems', data.sale_items || []);
            await replaceTable('orders', data.orders || []);
            await replaceTable('expenses', data.expenses || []);
            await replaceTable('invoices', data.invoices || []);
            await replaceTable('branches', data.branches || []);
            await replaceTable('productUnits', data.product_units || []);
            await replaceTable('unitConversions', data.unit_conversions || []);
            await replaceTable('branchStocks', data.branch_stocks || []);
            // products payload is already branch-merged from server; re-apply as safety net
            await applyBranchStockOverridesToProducts(data.branch_stocks || []);

            await setMeta('cache_version', data.cache_version || CACHE_VERSION);
            await setMeta('active_branch_id', data.active_branch_id);
            await setMeta('last_pulled_at', data.server_time || new Date().toISOString());
            await setMeta('user', data.user);
            await setMeta('offline_ready', true);
        }
    );

    if (data.user) {
        await setLocalSession(data.user);
    }

    broadcast('hydrated', { at: data.server_time });
}

export async function bootstrap() {
    if (!isOnline()) {
        return null;
    }
    const data = await api('/sync/bootstrap');
    await hydrateFromBootstrap(data);
    return data;
}

export async function pull() {
    if (!isOnline()) {
        return null;
    }
    const since = await getMeta('last_pulled_at');
    const data = await api(`/sync/pull?since=${encodeURIComponent(since || '')}`);
    if (data.full) {
        await hydrateFromBootstrap(data);
        return data;
    }

    // Delta merge
    const merge = async (table, rows) => {
        if (rows?.length) {
            await db.table(table).bulkPut(rows);
        }
    };

    await merge('products', data.products);
    await merge('categories', data.categories);
    await merge('units', data.units);
    await merge('customers', data.customers);
    await merge('suppliers', data.suppliers);
    await merge('sales', data.sales);
    await merge('saleItems', data.sale_items);
    await merge('orders', data.orders);
    await merge('expenses', data.expenses);
    await merge('invoices', data.invoices);
    await merge('branches', data.branches);
    await merge('productUnits', data.product_units);
    await merge('unitConversions', data.unit_conversions);
    await merge('branchStocks', data.branch_stocks);
    // Branch-only price edits update branch_stocks; keep products cache in sync
    await applyBranchStockOverridesToProducts(data.branch_stocks);

    if (data.deleted) {
        for (const [table, ids] of Object.entries(data.deleted)) {
            if (ids?.length && db[table]) {
                await db[table].bulkDelete(ids);
            }
        }
    }

    await setMeta('last_pulled_at', data.server_time || new Date().toISOString());
    broadcast('pulled', { at: data.server_time });
    return data;
}

export async function pushOutbox() {
    if (!isOnline() || syncing) {
        return { pushed: 0, conflicts: [] };
    }

    const pending = await getPendingOutbox();
    if (!pending.length) {
        return { pushed: 0, conflicts: [] };
    }

    syncing = true;
    broadcast('sync-state', { state: 'syncing', pending: pending.length });

    try {
        const session = await getLocalSession();
        const payload = {
            items: pending.map((row) => ({
                client_uuid: row.client_uuid,
                entity: row.entity,
                op: row.op,
                payload: row.payload,
                branch_id: row.branch_id,
                user_id: row.user_id || session?.id,
                created_at: row.created_at,
            })),
        };

        const result = await api('/sync/push', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        const conflicts = [];
        for (const item of result.results || []) {
            if (item.status === 'ok') {
                await markOutboxAcked(item.client_uuid, item.server_id);
                if (item.entity === 'sale' && item.server_id) {
                    const local = await db.sales.where('client_uuid').equals(item.client_uuid).first();
                    if (local) {
                        await db.sales.delete(local.id);
                        await db.sales.put({
                            ...local,
                            id: item.server_id,
                            sync_status: 'synced',
                            sale_number: item.sale_number || local.sale_number,
                        });
                    }
                }
                if (item.entity === 'customer' && item.server_id) {
                    const local = await db.customers.where('client_uuid').equals(item.client_uuid).first();
                    if (local) {
                        await db.customers.delete(local.id);
                        await db.customers.put({
                            ...local,
                            id: item.server_id,
                            sync_status: 'synced',
                        });
                    }
                }
                if (item.entity === 'supplier' && item.server_id) {
                    const local = await db.suppliers.where('client_uuid').equals(item.client_uuid).first();
                    if (local) {
                        await db.suppliers.delete(local.id);
                        await db.suppliers.put({
                            ...local,
                            id: item.server_id,
                            supplier_id: item.supplier_id || local.supplier_id,
                            sync_status: 'synced',
                        });
                    }
                }
            } else if (item.status === 'conflict') {
                conflicts.push(item);
                await markOutboxConflict(item.client_uuid, item);
            }
        }

        await setMeta('last_pushed_at', new Date().toISOString());
        return { pushed: (result.results || []).filter((r) => r.status === 'ok').length, conflicts };
    } finally {
        syncing = false;
        const pendingLeft = await pendingOutboxCount();
        broadcast('sync-state', {
            state: pendingLeft ? 'online' : 'synced',
            pending: pendingLeft,
        });
    }
}

export async function syncNow() {
    if (!isOnline()) {
        throw new Error('You are offline.');
    }

    const run = async () => {
        broadcast('sync-state', { state: 'syncing', pending: await pendingOutboxCount() });
        try {
            const pushResult = await pushOutbox();
            await pull();
            broadcast('sync-state', {
                state: 'synced',
                pending: await pendingOutboxCount(),
            });
            broadcast('synced', {
                conflicts: pushResult.conflicts?.length || 0,
                pending: await pendingOutboxCount(),
            });
            return pushResult;
        } catch (e) {
            broadcast('sync-state', { state: 'online', pending: await pendingOutboxCount() });
            throw e;
        }
    };

    if (typeof navigator !== 'undefined' && navigator.locks) {
        return navigator.locks.request('ftpos-sync-leader', run);
    }

    if (!isLeader) {
        isLeader = true;
    }
    return run();
}

export function startSyncScheduler() {
    onConnectivityChange(async (status, prev) => {
        if (status === 'online' && prev === 'offline') {
            try {
                await syncNow();
            } catch (e) {
                console.warn('[offline] auto sync failed', e);
                broadcast('sync-error', { message: e.message });
            }
        }
    });

    pullTimer = setInterval(async () => {
        if (isOnline()) {
            try {
                const pending = await pendingOutboxCount();
                if (pending > 0) {
                    await syncNow();
                } else {
                    await pull();
                }
            } catch (e) {
                console.warn('[offline] scheduled sync failed', e);
            }
        }
    }, PULL_INTERVAL_MS);
}

export function isSyncing() {
    return syncing;
}
