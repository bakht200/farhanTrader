import Dexie from 'dexie';

export const CACHE_VERSION = 1;

export const db = new Dexie('farhantrader_offline');

db.version(1).stores({
    meta: 'key',
    vault: 'email',
    localSession: 'key',
    products: 'id, branch_id, name, sku, updated_at',
    categories: 'id, name, updated_at',
    units: 'id, name, updated_at',
    customers: 'id, branch_id, name, client_uuid, updated_at',
    suppliers: 'id, branch_id, name, client_uuid, updated_at',
    sales: 'id, branch_id, client_uuid, customer_id, sale_date, updated_at',
    saleItems: 'id, sale_id, client_uuid, product_id',
    orders: 'id, branch_id, client_uuid, status, updated_at',
    expenses: 'id, branch_id, client_uuid, updated_at',
    invoices: 'id, branch_id, sale_id, updated_at',
    branches: 'id, name',
    productUnits: 'id, product_id, unit_id',
    unitConversions: 'id, product_id',
    branchStocks: '[branch_id+product_id], branch_id, product_id',
    outbox: '++id, client_uuid, entity, status, created_at, user_id',
    conflicts: '++id, client_uuid, created_at',
});

export async function getMeta(key, fallback = null) {
    const row = await db.meta.get(key);
    return row ? row.value : fallback;
}

export async function setMeta(key, value) {
    await db.meta.put({ key, value });
}

export async function pendingOutboxCount() {
    return db.outbox.where('status').equals('pending').count();
}

export function uuid() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}
