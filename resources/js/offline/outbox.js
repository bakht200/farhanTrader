import { db, uuid, pendingOutboxCount } from './db';
import { broadcast } from './broadcast';
import { getLocalSession } from './authVault';

const ENTITY_ORDER = {
    customer: 10,
    product: 20,
    expense: 30,
    sale: 40,
    order: 50,
    payment: 60,
    supplier: 25,
};

export async function enqueueMutation({ entity, op, payload, branchId = null, clientUuid = null }) {
    const session = await getLocalSession();
    const id = clientUuid || uuid();
    const entry = {
        client_uuid: id,
        entity,
        op,
        payload,
        branch_id: branchId,
        user_id: session?.id || null,
        status: 'pending',
        created_at: new Date().toISOString(),
        priority: ENTITY_ORDER[entity] ?? 100,
    };

    await db.outbox.add(entry);
    broadcast('outbox-changed', { pending: await pendingOutboxCount() });
    return id;
}

export async function getPendingOutbox() {
    const rows = await db.outbox.where('status').equals('pending').toArray();
    return rows.sort((a, b) => (a.priority - b.priority) || (a.id - b.id));
}

export async function markOutboxAcked(clientUuid, serverId = null) {
    const row = await db.outbox.where('client_uuid').equals(clientUuid).first();
    if (row) {
        await db.outbox.update(row.id, {
            status: 'acked',
            server_id: serverId,
            acked_at: new Date().toISOString(),
        });
    }
    broadcast('outbox-changed', { pending: await pendingOutboxCount() });
}

export async function markOutboxConflict(clientUuid, conflict) {
    const row = await db.outbox.where('client_uuid').equals(clientUuid).first();
    if (row) {
        await db.outbox.update(row.id, {
            status: 'conflict',
            conflict,
        });
    }
    await db.conflicts.add({
        client_uuid: clientUuid,
        conflict,
        created_at: new Date().toISOString(),
    });
    broadcast('outbox-changed', { pending: await pendingOutboxCount() });
}

/**
 * Queue an offline POS sale and mirror it locally.
 */
export async function queueOfflineSale(salePayload, meta = {}) {
    const clientUuid = uuid();
    const branchId = meta.branchId || (await db.meta.get('active_branch_id'))?.value || null;
    const now = new Date().toISOString();

    const localSale = {
        id: `local-${clientUuid}`,
        client_uuid: clientUuid,
        branch_id: branchId,
        customer_id: salePayload.customer_id || null,
        sale_date: now.slice(0, 10),
        payment_method: salePayload.payment_method,
        paid_amount: salePayload.paid_amount,
        notes: salePayload.comment || salePayload.customer_name || '',
        status: 'completed',
        payment_status: 'pending',
        sync_status: 'pending',
        items: salePayload.items || [],
        updated_at: now,
        created_at: now,
    };

    await db.sales.put(localSale);

    // Optimistic stock decrement for non-custom items
    for (const item of salePayload.items || []) {
        if (item.is_custom == '1' || item.is_custom === true || !item.product_id) {
            continue;
        }
        const product = await db.products.get(Number(item.product_id));
        if (product) {
            const qty = Number(item.quantity) || 0;
            const next = Math.max(0, Number(product.stock_quantity || 0) - qty);
            await db.products.update(product.id, {
                stock_quantity: next,
                updated_at: now,
            });
            if (branchId) {
                const key = [Number(branchId), Number(item.product_id)];
                const stock = await db.branchStocks.get(key);
                if (stock) {
                    await db.branchStocks.put({
                        ...stock,
                        quantity: Math.max(0, Number(stock.quantity || 0) - qty),
                    });
                }
            }
        }
    }

    await enqueueMutation({
        entity: 'sale',
        op: 'create',
        payload: salePayload,
        branchId,
        clientUuid,
    });

    return { clientUuid, localSale };
}

export async function queueOfflineCustomer(payload) {
    const clientUuid = uuid();
    const now = new Date().toISOString();
    const branchId = payload.branch_id || (await db.meta.get('active_branch_id'))?.value || null;
    const local = {
        id: `local-${clientUuid}`,
        client_uuid: clientUuid,
        branch_id: branchId,
        name: payload.name,
        phone: payload.phone || null,
        email: payload.email || null,
        address: payload.address || null,
        sync_status: 'pending',
        updated_at: now,
        created_at: now,
    };
    await db.customers.put(local);
    await enqueueMutation({
        entity: 'customer',
        op: 'create',
        payload,
        branchId,
        clientUuid,
    });
    return { clientUuid, local };
}

export async function queueOfflineExpense(payload) {
    const clientUuid = uuid();
    const now = new Date().toISOString();
    const branchId = payload.branch_id || (await db.meta.get('active_branch_id'))?.value || null;
    const local = {
        id: `local-${clientUuid}`,
        client_uuid: clientUuid,
        branch_id: branchId,
        ...payload,
        sync_status: 'pending',
        updated_at: now,
        created_at: now,
    };
    await db.expenses.put(local);
    await enqueueMutation({
        entity: 'expense',
        op: 'create',
        payload,
        branchId,
        clientUuid,
    });
    return { clientUuid, local };
}

export async function queueOfflineSupplier(payload) {
    const clientUuid = uuid();
    const now = new Date().toISOString();
    const branchId = payload.branch_id || (await db.meta.get('active_branch_id'))?.value || null;
    const local = {
        id: `local-${clientUuid}`,
        client_uuid: clientUuid,
        branch_id: branchId,
        supplier_id: payload.supplier_id || null,
        name: payload.name,
        company_name: payload.company_name || null,
        email: payload.email || null,
        phone: payload.phone || null,
        address: payload.address || null,
        city: payload.city || null,
        state: payload.state || null,
        country: payload.country || null,
        postal_code: payload.postal_code || null,
        tax_id: payload.tax_id || null,
        is_active: true,
        sync_status: 'pending',
        total_paid: 0,
        remaining: 0,
        hasUnpaid: false,
        updated_at: now,
        created_at: now,
    };
    await db.suppliers.put(local);
    await enqueueMutation({
        entity: 'supplier',
        op: 'create',
        payload,
        branchId,
        clientUuid,
    });
    return { clientUuid, local };
}
