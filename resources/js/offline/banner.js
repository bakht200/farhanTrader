import { pendingOutboxCount } from './db';
import { getConnectivityStatus, onConnectivityChange, isOnline, checkNow } from './connectivity';
import { onBroadcast, broadcast } from './broadcast';
import { syncNow, uploadPendingToCloud } from './sync';
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

async function applyPill(pill, pendingEl, { online, pending, syncing }) {
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
        const ready = await hasAnyVault().catch(() => false);
        pill.textContent = ready ? 'Online · Offline ready' : 'Online';
        pill.title = ready
            ? 'Connected — this PC can keep working if internet drops'
            : 'Connected — sign in once online to enable offline on this PC';
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

    document.querySelectorAll('[data-ftpos-upload]').forEach((btn) => {
        if (btn.dataset.uploading === '1') {
            return;
        }
        btn.disabled = false;
        if (syncing) {
            btn.textContent = 'Uploading…';
        } else if (pending > 0) {
            btn.textContent = `Upload to cloud (${pending})`;
        } else {
            btn.textContent = 'Upload to cloud';
        }
        btn.title = pending > 0
            ? 'Send sales and other work from this PC to the cloud when you have internet'
            : 'Nothing waiting — tap after you work offline to send new changes';
    });
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

    await applyPill(
        document.getElementById('ftpos-connectivity-status'),
        document.getElementById('ftpos-pending-sync'),
        state
    );
    await applyPill(
        document.getElementById('pos-connectivity-status'),
        document.getElementById('pos-pending-sync'),
        state
    );
}

function ensureUploadButton(afterId, buttonId, className) {
    let btn = document.getElementById(buttonId);
    if (btn) {
        return btn;
    }
    const after = document.getElementById(afterId);
    if (!after || !after.parentElement) {
        return null;
    }
    btn = document.createElement('button');
    btn.type = 'button';
    btn.id = buttonId;
    btn.setAttribute('data-ftpos-upload', '1');
    btn.className = className;
    btn.textContent = 'Upload to cloud';
    after.insertAdjacentElement('afterend', btn);
    return btn;
}

async function handleUploadClick(event) {
    const btn = event.currentTarget;
    btn.dataset.uploading = '1';
    btn.disabled = true;
    btn.textContent = 'Uploading…';
    try {
        const result = await uploadPendingToCloud();
        const leftover = await pendingOutboxCount();
        if (result.pushed > 0 && leftover === 0) {
            showToast(`Uploaded ${result.pushed} change(s) to the cloud`);
        } else if (result.pushed > 0) {
            showToast(`Uploaded ${result.pushed}. ${leftover} still waiting.`);
        } else {
            showToast('Everything is already on the cloud');
        }
    } catch (e) {
        if (e?.name === 'SessionExpired' || /session expired/i.test(e.message || '')) {
            const { redirectToLogin } = await import('./session');
            redirectToLogin();
            return;
        }
        showToast(e.message || 'Upload failed');
    } finally {
        btn.dataset.uploading = '0';
        btn.disabled = false;
        await renderStatusPills();
    }
}

function bindUploadClick(id) {
    const btn = document.getElementById(id);
    if (!btn || btn.dataset.bound === '1') {
        return;
    }
    btn.dataset.bound = '1';
    btn.addEventListener('click', handleUploadClick);
}

function bindPillClick(id) {
    const pill = document.getElementById(id);
    if (!pill || pill.dataset.bound === '1') {
        return;
    }
    pill.dataset.bound = '1';
    pill.addEventListener('click', async () => {
        if (!isOnline()) {
            const recovered = await checkNow();
            if (!recovered) {
                showToast('No internet yet. Work stays on this PC until you upload.');
                return;
            }
        }
        try {
            await syncNow();
            showToast('All changes synced');
        } catch (e) {
            if (e?.name === 'SessionExpired' || /session expired/i.test(e.message || '')) {
                const { redirectToLogin } = await import('./session');
                redirectToLogin();
                return;
            }
            showToast(e.message || 'Sync failed');
        }
    });
}

export async function mountOfflineBanner() {
    removeLegacyBanner();
    ensureUploadButton(
        'ftpos-pending-sync',
        'ftpos-upload-cloud',
        'rounded-md px-3 py-1 text-xs font-semibold whitespace-nowrap bg-orange-500 text-white hover:bg-orange-600'
    );
    ensureUploadButton(
        'pos-pending-sync',
        'pos-upload-cloud',
        'rounded-md px-3 py-1 text-sm font-semibold whitespace-nowrap bg-orange-500 text-white hover:bg-orange-600 border-2 border-white/20'
    );
    bindPillClick('ftpos-connectivity-status');
    bindPillClick('pos-connectivity-status');
    bindUploadClick('ftpos-upload-cloud');
    bindUploadClick('pos-upload-cloud');
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
            if (/session expired/i.test(msg.payload?.message || '')) {
                return;
            }
            showToast(msg.payload?.message || 'Sync error');
        }
    });

    if (await hasAnyVault()) {
        broadcast('offline-ready', { ready: true });
    }

    setTimeout(() => {
        bindPillClick('ftpos-connectivity-status');
        bindPillClick('pos-connectivity-status');
        bindUploadClick('ftpos-upload-cloud');
        bindUploadClick('pos-upload-cloud');
        renderStatusPills();
    }, 800);
}

export { showToast, isOnline, renderStatusPills as renderBanner };
