import { clearLocalSession } from './authVault';
import { getMeta, setMeta } from './db';

export const APP_VERSION = '2.14';
export const LOGIN_URL = '/login';
export const LAST_BRANCH_STORAGE_KEY = 'ftpos_last_branch_id';
const VERSION_STORAGE_KEY = 'ftpos_app_version';

export function persistLastBranchId(id) {
    const n = Number(id);
    if (!n) {
        return;
    }
    try {
        window.localStorage.setItem(LAST_BRANCH_STORAGE_KEY, String(n));
    } catch {
        // private mode
    }
    setMeta('active_branch_id', n).catch(() => {});
}

export function readLastBranchId() {
    try {
        const n = Number(window.localStorage.getItem(LAST_BRANCH_STORAGE_KEY) || 0);
        return n > 0 ? n : null;
    } catch {
        return null;
    }
}

export async function resolveOfflineBranchId() {
    const fromPage = Number(document.body?.getAttribute('data-ft-branch-id') || 0);
    if (fromPage) {
        return fromPage;
    }
    const fromStorage = readLastBranchId();
    if (fromStorage) {
        return fromStorage;
    }
    try {
        const n = Number(await getMeta('active_branch_id', 0) || 0);
        return n > 0 ? n : null;
    } catch {
        return null;
    }
}

function postToServiceWorker(type, extra = {}) {
    return new Promise((resolve) => {
        try {
            const controller = navigator.serviceWorker?.controller;
            if (!controller) {
                resolve();
                return;
            }
            const channel = new MessageChannel();
            const timer = setTimeout(resolve, 400);
            channel.port1.onmessage = () => {
                clearTimeout(timer);
                resolve();
            };
            controller.postMessage({ type, ...extra }, [channel.port2]);
        } catch {
            resolve();
        }
    });
}

export function notifyServiceWorkerLogin(options = {}) {
    return postToServiceWorker('LOGIN', { vault: !!options.vault });
}

function browserIsOffline() {
    return typeof navigator !== 'undefined' && navigator.onLine === false;
}

/**
 * Remember the running app version. Do not reload or wipe caches —
 * the service worker already drops old ftpos-* names on activate.
 */
export async function applyHardRefreshIfNeeded() {
    try {
        window.localStorage.setItem(VERSION_STORAGE_KEY, APP_VERSION);
    } catch {
        // private mode
    }
    return false;
}

export function isLoginPage() {
    return !!document.querySelector('form[action*="login"]')
        || window.location.pathname.includes('/login');
}

export function notifyServiceWorkerLogout() {
    return postToServiceWorker('LOGOUT');
}

function postLogout() {
    const form = document.getElementById('ftpos-logout-form');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const body = form ? new FormData(form) : new FormData();
    if (!form && csrf) {
        body.append('_token', csrf);
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 2500);

    return fetch('/logout', {
        method: 'POST',
        credentials: 'same-origin',
        body,
        redirect: 'manual',
        keepalive: true,
        signal: controller.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    }).catch(() => null).finally(() => clearTimeout(timer));
}

async function wipeClientSession() {
    await notifyServiceWorkerLogout();
    try {
        await clearLocalSession();
    } catch {
        // ignore
    }
    // Keep the service worker, page caches, vault hashes, and catalog so
    // the same PC can sign in again with no internet tomorrow.
}

function goToLogin() {
    window.location.replace(LOGIN_URL);
}

export async function redirectToLogin() {
    if (isLoginPage()) {
        return;
    }
    window.__ftposLoggingOut = true;
    await wipeClientSession();
    goToLogin();
}

export async function logoutAndRedirect() {
    window.__ftposLoggingOut = true;
    await notifyServiceWorkerLogout();
    if (!browserIsOffline()) {
        try {
            await postLogout();
        } catch {
            // Session/CSRF may already be gone.
        }
    }
    await wipeClientSession();
    goToLogin();
}

export async function probeServerSession() {
    if (isLoginPage() || window.__ftposLoggingOut || browserIsOffline()) {
        return;
    }
    try {
        const res = await fetch('/sync/ping', {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.status === 401 || res.status === 419) {
            await redirectToLogin();
        }
    } catch {
        // No network — stay on the cached app / offline vault session.
    }
}

function shouldIgnoreAuthFailure(url) {
    try {
        const path = new URL(url, window.location.origin).pathname;
        return path === '/up'
            || path === '/login'
            || path.startsWith('/login/')
            || path === '/logout'
            || path.startsWith('/logout/');
    } catch {
        return false;
    }
}

export function installSessionGuards() {
    if (window.__ftposFetchPatched) {
        return;
    }
    window.__ftposFetchPatched = true;

    const originalFetch = window.fetch.bind(window);
    window.fetch = async function patchedFetch(input, init) {
        const res = await originalFetch(input, init);
        try {
            const url = typeof input === 'string'
                ? input
                : (input && typeof input === 'object' && 'url' in input ? input.url : '');
            if (
                (res.status === 401 || res.status === 419)
                && !isLoginPage()
                && !shouldIgnoreAuthFailure(url)
                && !browserIsOffline()
            ) {
                redirectToLogin();
            }
        } catch {
            // ignore
        }
        return res;
    };

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'SESSION_EXPIRED' && !browserIsOffline()) {
                redirectToLogin();
            }
        });
    }
}
