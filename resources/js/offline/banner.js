import { pendingOutboxCount } from './db';
import { getConnectivityStatus, onConnectivityChange, isOnline } from './connectivity';
import { onBroadcast, broadcast } from './broadcast';
import { syncNow } from './sync';
import { hasAnyVault } from './authVault';

let lastSyncState = null;

const PILL_BASE_APP = 'rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap';
const PILL_BASE_POS = 'rounded-full px-4 py-1 text-sm font-semibold whitespace-nowrap border-2 border-white/20';

function ensureToastEl() {
    let el = document.getElementById('ftpos-offline-toast');
    if (!el) {
        el = document.createElement('div');
        el.id = 'ftpos-offline-toast';
        el.style.cssText = [
            'display:none',
            'position:fixed',
            'right:16px',
            'bottom:16px',
            'z-index:10001',
            'max-width:360px',
            'padding:12px 16px',
            'border-radius:8px',
            'background:#111827',
            'color:#fff',
            'font-size:14px',
            'box-shadow:0 8px 24px rgba(0,0,0,.2)',
        ].join(';');
        document.body.appendChild(el);
    }
    return el;
}

let toastTimer = null;
function showToast(message, ms = 4000) {
    const el = ensureToastEl();
    el.textContent = message;
    el.style.display = 'block';
    if (toastTimer) {
        clearTimeout(toastTimer);
    }
    toastTimer = setTimeout(() => {
        el.style.display = 'none';
    }, ms);
}

function removeLegacyBanner() {
    const el = document.getElementById('ftpos-offline-banner');
    if (el) {
        el.remove();
    }
}

function applyPill(pill, pendingEl, { online, pending, syncing }) {
    if (!pill) {
        return;
    }

    const isPos = pill.id === 'pos-connectivity-status';
    const base = isPos ? PILL_BASE_POS : PILL_BASE_APP;

    if (!online) {
        pill.className = `${base} bg-red-600 text-white`;
        pill.textContent = 'OFFLINE';
        pill.title = 'Internet is slow or disconnected — using this device. Connection is checked in the background.';
    } else if (syncing) {
        pill.className = `${base} bg-amber-400 text-gray-900`;
        pill.textContent = 'SYNCING…';
        pill.title = 'Uploading pending changes — click to sync now';
    } else {
        pill.className = `${base} bg-green-600 text-white`;
        pill.textContent = 'Online';
        pill.title = 'Connected — click to sync now';
    }

    if (pendingEl) {
        if (pending > 0) {
            pendingEl.classList.remove('hidden');
            pendingEl.textContent = `${pending} pending`;
            pendingEl.className = isPos
                ? `${PILL_BASE_POS} bg-amber-400 text-gray-900`
                : `${PILL_BASE_APP} bg-amber-100 text-amber-900 border border-amber-300`;
        } else {
            pendingEl.classList.add('hidden');
            pendingEl.textContent = '';
        }
    }
}

async function renderStatusPills(extra = {}) {
    removeLegacyBanner();

    const status = extra.status || getConnectivityStatus();
    const pending = extra.pending ?? (await pendingOutboxCount());
    const syncState = extra.syncState ?? lastSyncState;
    if (extra.syncState) {
        lastSyncState = extra.syncState;
    }

    const online = status === 'online';
    const syncing = syncState === 'syncing' || (online && pending > 0);
    const state = { online, pending, syncing };

    applyPill(
        document.getElementById('ftpos-connectivity-status'),
        document.getElementById('ftpos-pending-sync'),
        state
    );
    applyPill(
        document.getElementById('pos-connectivity-status'),
        document.getElementById('pos-pending-sync'),
        state
    );
}

function bindPillClick(id) {
    const pill = document.getElementById(id);
    if (!pill || pill.dataset.bound === '1') {
        return;
    }
    pill.dataset.bound = '1';
    pill.addEventListener('click', async () => {
        if (!isOnline()) {
            return;
        }
        try {
            await syncNow();
            showToast('All changes synced');
        } catch (e) {
            showToast(e.message || 'Sync failed');
        }
    });
}

export async function mountOfflineBanner() {
    removeLegacyBanner();
    bindPillClick('ftpos-connectivity-status');
    bindPillClick('pos-connectivity-status');
    await renderStatusPills();

    onConnectivityChange(async (status) => {
        if (status === 'online') {
            lastSyncState = 'syncing';
            await renderStatusPills({ status, syncState: 'syncing' });
            return;
        }
        await renderStatusPills({ status });
    });

    onBroadcast(async (msg) => {
        if (!msg?.type) {
            return;
        }
        if (msg.type === 'outbox-changed' || msg.type === 'synced' || msg.type === 'hydrated' || msg.type === 'vault' || msg.type === 'offline-ready') {
            if (msg.type === 'synced') {
                lastSyncState = 'synced';
            }
            await renderStatusPills({ pending: msg.payload?.pending, syncState: lastSyncState });
        }
        if (msg.type === 'sync-state') {
            lastSyncState = msg.payload?.state || null;
            await renderStatusPills({
                syncState: lastSyncState,
                pending: msg.payload?.pending,
            });
        }
        if (msg.type === 'sync-error') {
            showToast(msg.payload?.message || 'Sync error');
        }
    });

    if (await hasAnyVault()) {
        broadcast('offline-ready', { ready: true });
    }

    setTimeout(() => {
        bindPillClick('ftpos-connectivity-status');
        bindPillClick('pos-connectivity-status');
        renderStatusPills();
    }, 800);
}

export { showToast, isOnline, renderStatusPills as renderBanner };
