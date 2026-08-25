import { clearLocalSession } from './authVault';
import { CACHE_NAME } from './prefetch';

export const APP_VERSION = '1.1';
export const LOGIN_URL = '/login';

export function isLoginPage() {
    return !!document.querySelector('form[action*="login"]')
        || window.location.pathname.includes('/login');
}

export function notifyServiceWorkerLogout() {
    try {
        navigator.serviceWorker?.controller?.postMessage({ type: 'LOGOUT' });
    } catch {
        // ignore
    }
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
    notifyServiceWorkerLogout();
    try {
        await clearLocalSession();
    } catch {
        // ignore
    }
    if ('caches' in window) {
        try {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((k) => k.startsWith('ftpos-') || k === CACHE_NAME)
                    .map((k) => caches.delete(k))
            );
        } catch {
            // ignore
        }
    }
    try {
        const regs = await navigator.serviceWorker.getRegistrations();
        await Promise.all(regs.map((reg) => reg.unregister()));
    } catch {
        // ignore
    }
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
    notifyServiceWorkerLogout();
    try {
        await postLogout();
    } catch {
        // Session/CSRF may already be gone.
    }
    await wipeClientSession();
    goToLogin();
}

export async function probeServerSession() {
    if (isLoginPage() || window.__ftposLoggingOut) {
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
            if (event.data?.type === 'SESSION_EXPIRED') {
                redirectToLogin();
            }
        });
    }
}
