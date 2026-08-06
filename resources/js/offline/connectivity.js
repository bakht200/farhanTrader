import { broadcast } from './broadcast';

const PROBE_URL = '/up';
const HEARTBEAT_MS = 15000;
const DEBOUNCE_MS = 2000;
const PROBE_TIMEOUT_MS = 3000;

/** @type {'offline' | 'online'} */
let status = navigator.onLine ? 'online' : 'offline';
let debounceTimer = null;
let heartbeatTimer = null;
const listeners = new Set();

export function getConnectivityStatus() {
    return status;
}

export function isOnline() {
    return status === 'online';
}

export function onConnectivityChange(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
}

function emit(next) {
    if (next === status) {
        return;
    }
    const prev = status;
    status = next;
    listeners.forEach((fn) => fn(status, prev));
    broadcast('connectivity', { status });
    window.dispatchEvent(new CustomEvent('ftpos-connectivity', { detail: { status, prev } }));
}

/**
 * Browser link must be up. Localhost /up still works with Wi‑Fi off,
 * so never treat probe success as online when navigator.onLine is false.
 */
function browserReportsOnline() {
    return typeof navigator !== 'undefined' && navigator.onLine !== false;
}

async function probe() {
    if (!browserReportsOnline()) {
        return false;
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), PROBE_TIMEOUT_MS);
    try {
        const res = await fetch(PROBE_URL, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { Accept: 'text/html,application/json' },
        });
        clearTimeout(timer);
        // Re-check: Wi‑Fi may have dropped during the request
        return res.ok && browserReportsOnline();
    } catch {
        clearTimeout(timer);
        return false;
    }
}

function setOfflineImmediate() {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
    emit('offline');
}

function setOnlineDebounced() {
    if (!browserReportsOnline()) {
        setOfflineImmediate();
        return;
    }
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(async () => {
        if (!browserReportsOnline()) {
            emit('offline');
            return;
        }
        const ok = await probe();
        if (ok) {
            emit('online');
        } else {
            emit('offline');
        }
    }, DEBOUNCE_MS);
}

export async function checkNow() {
    if (!browserReportsOnline()) {
        emit('offline');
        return false;
    }
    const ok = await probe();
    if (ok) {
        emit('online');
    } else {
        emit('offline');
    }
    return ok;
}

export function startConnectivityMonitor() {
    window.addEventListener('online', () => setOnlineDebounced());
    window.addEventListener('offline', () => setOfflineImmediate());

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            checkNow();
        }
    });

    checkNow();
    heartbeatTimer = setInterval(() => {
        checkNow();
    }, HEARTBEAT_MS);

    return () => {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
        }
    };
}

export function markOfflineFromError() {
    setOfflineImmediate();
}
