import { broadcast } from './broadcast';

const PROBE_URL = '/up';
const PROBE_TIMEOUT_MS = 4000;
const HEARTBEAT_ONLINE_MS = 20000;
const HEARTBEAT_OFFLINE_MS = 4000;
const STABLE_HITS_NEEDED = 3;
const MIN_OFFLINE_MS = 5000;

/** @type {'offline' | 'online'} */
let status = typeof navigator !== 'undefined' && navigator.onLine !== false ? 'online' : 'offline';
let consecutiveFastHits = 0;
let requireStableRecovery = false;
let probing = false;
let heartbeatTimer = null;
let holdOfflineUntil = 0;
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
        notifyServiceWorker(next === 'online');
        return;
    }
    const prev = status;
    status = next;
    if (next === 'offline' && prev === 'online') {
        holdOfflineUntil = Date.now() + MIN_OFFLINE_MS;
        consecutiveFastHits = 0;
        requireStableRecovery = true;
    }
    if (next === 'online') {
        requireStableRecovery = false;
        consecutiveFastHits = STABLE_HITS_NEEDED;
        holdOfflineUntil = 0;
    }
    listeners.forEach((fn) => fn(status, prev));
    broadcast('connectivity', { status });
    window.dispatchEvent(new CustomEvent('ftpos-connectivity', { detail: { status, prev } }));
    notifyServiceWorker(next === 'online');
    restartHeartbeat();
}

function notifyServiceWorker(online) {
    if (!('serviceWorker' in navigator)) {
        return;
    }
    const msg = { type: 'CONNECTIVITY', online: !!online };
    navigator.serviceWorker.controller?.postMessage(msg);
    navigator.serviceWorker.ready
        .then((reg) => reg.active?.postMessage(msg))
        .catch(() => {});
}

/**
 * Browser link must be up. Localhost /up still works with Wi‑Fi off,
 * so never treat probe success as online when navigator.onLine is false.
 */
function browserReportsOnline() {
    return typeof navigator !== 'undefined' && navigator.onLine !== false;
}

function browserLooksSlow() {
    // Chrome's Network Information API often reports 2g/saveData on usable Wi‑Fi.
    // Do not treat that as offline — /up probe timing is the real signal.
    return false;
}

async function probe(timeoutMs = PROBE_TIMEOUT_MS) {
    if (!browserReportsOnline()) {
        return { ok: false, ms: 0, slow: true };
    }

    const controller = new AbortController();
    const started = performance.now();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
        const res = await fetch(PROBE_URL, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { Accept: 'text/html,application/json', 'X-Ftpos-Probe': '1' },
        });
        const ms = performance.now() - started;
        const ok = res.ok && browserReportsOnline();
        return { ok, ms };
    } catch {
        return { ok: false, ms: performance.now() - started };
    } finally {
        clearTimeout(timer);
    }
}

function canPromoteToOnline() {
    return Date.now() >= holdOfflineUntil;
}

async function evaluate({ instant = false, timeoutMs = PROBE_TIMEOUT_MS } = {}) {
    if (probing) {
        return status === 'online';
    }
    probing = true;
    try {
        if (!browserReportsOnline()) {
            consecutiveFastHits = 0;
            requireStableRecovery = true;
            emit('offline');
            return false;
        }

        const result = await probe(timeoutMs);
        if (!result.ok) {
            consecutiveFastHits = 0;
            requireStableRecovery = true;
            emit('offline');
            return false;
        }

        consecutiveFastHits += 1;
        const needed = instant || !requireStableRecovery || status === 'online'
            ? 1
            : STABLE_HITS_NEEDED;
        if (consecutiveFastHits >= needed && canPromoteToOnline()) {
            emit('online');
            return true;
        }

        return false;
    } finally {
        probing = false;
    }
}

function setOfflineImmediate() {
    consecutiveFastHits = 0;
    requireStableRecovery = true;
    emit('offline');
}

function scheduleBackgroundCheck(delayMs = 300) {
    setTimeout(() => {
        evaluate().catch(() => {});
    }, delayMs);
}

export async function checkNow(options = {}) {
    consecutiveFastHits = 0;
    return evaluate({ instant: true, timeoutMs: options.timeoutMs ?? PROBE_TIMEOUT_MS });
}

function restartHeartbeat() {
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
    }
    const ms = status === 'online' ? HEARTBEAT_ONLINE_MS : HEARTBEAT_OFFLINE_MS;
    heartbeatTimer = setInterval(() => {
        evaluate().catch(() => {});
    }, ms);
}

export async function startConnectivityMonitor() {
    // If the browser already reports a link, do not force cache-first POS/HTML.
    // Probe still confirms whether the shop API is reachable.
    notifyServiceWorker(browserReportsOnline());

    window.addEventListener('online', () => scheduleBackgroundCheck(400));
    window.addEventListener('offline', () => setOfflineImmediate());

    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    connection?.addEventListener?.('change', () => {
        if (!browserReportsOnline() || browserLooksSlow()) {
            setOfflineImmediate();
            return;
        }
        scheduleBackgroundCheck(200);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            scheduleBackgroundCheck(0);
        }
    });

    evaluate({ instant: true }).catch(() => {});
    restartHeartbeat();

    return () => {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
        }
    };
}

export function markOfflineFromError() {
    setOfflineImmediate();
    scheduleBackgroundCheck(HEARTBEAT_OFFLINE_MS);
}
