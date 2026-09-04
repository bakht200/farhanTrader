/**
 * Prefetch app HTML shells into the Cache Storage / Service Worker
 * so offline navigation works without visiting each page first.
 */

import { isOnline } from './connectivity';
import { db } from './db';

export const CACHE_NAME = 'ftpos-pages-v14';

export const CORE_SHELLS = [
    '/dashboard',
    '/customers',
    '/customers/create',
    '/suppliers',
    '/suppliers/create',
    '/sales/pos',
    '/products',
];
export const MAX_SUPPLIER_PREFETCH = 150;

export const PRECACHE_ROUTES = [
    '/',
    '/dashboard',
    '/profile',
    '/products',
    '/products/create',
    '/products/low-stocks',
    '/categories',
    '/units',
    '/expenses',
    '/expenses/create',
    '/sales',
    '/sales/pos',
    '/sales/invoices',
    '/orders',
    '/orders/completed',
    '/orders/pending',
    '/orders/on-hold',
    '/customers',
    '/customers/create',
    '/suppliers',
    '/suppliers/create',
    '/shares',
    '/reports',
    '/reports/profit-loss',
    '/reports/sales-report',
    '/reports/invoice-report',
    '/branches',
    '/branches/receipt-settings',
    '/branches/receipt-settings/edit',
    '/offline.html',
    '/logo.png',
    '/login',
];

async function waitForServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }
    const reg = await navigator.serviceWorker.ready;
    return reg?.active || navigator.serviceWorker.controller;
}

function collectNavUrls() {
    const urls = new Set(PRECACHE_ROUTES);
    document.querySelectorAll('a[href]').forEach((a) => {
        if (a.hasAttribute('data-requires-internet')) {
            return;
        }
        try {
            const u = new URL(a.getAttribute('href'), window.location.origin);
            if (u.origin !== window.location.origin) {
                return;
            }
            if (
                u.pathname.includes('ai-insights')
                || u.pathname.includes('health-check')
                || u.pathname.startsWith('/sync/')
                || u.pathname === '/up'
                || u.pathname.startsWith('/logout')
                || u.pathname.includes('anonymous-purchase')
            ) {
                return;
            }
            if (u.pathname && u.pathname !== '/') {
                urls.add(u.pathname);
            }
        } catch {
            // ignore bad hrefs
        }
    });
    return [...urls];
}

function isLoginPath(pathname) {
    const p = (pathname || '').replace(/\/+$/, '') || '/';
    return p === '/login' || p.startsWith('/login/');
}

function isPosPath(pathname) {
    const p = (pathname || '').replace(/\/+$/, '') || '/';
    return p === '/sales/pos';
}

function posCatalogIsEmpty(buf) {
    try {
        const slice = buf.byteLength > 65536 ? buf.slice(0, 65536) : buf;
        return new TextDecoder().decode(slice).includes('data-ftpos-catalog="empty"');
    } catch {
        return false;
    }
}

function htmlLooksLikeLogin(buf) {
    try {
        const slice = buf.byteLength > 65536 ? buf.slice(0, 65536) : buf;
        const text = new TextDecoder().decode(slice);
        if (text.includes('data-ftpos-page="login"')) {
            return true;
        }
        if (text.includes('<div data-ftpos-page="dashboard"')) {
            return false;
        }
        return text.includes('Sign In') && text.includes('name="email"');
    } catch {
        return false;
    }
}

async function storePage(cache, path, res) {
    if (!res || !res.ok) {
        return false;
    }
    try {
        const finalPath = new URL(res.url, window.location.origin).pathname;
        if (isLoginPath(finalPath) && !isLoginPath(path)) {
            return false;
        }
        if (isLoginPath(path) && !isLoginPath(finalPath)) {
            return false;
        }
    } catch {
        return false;
    }
    const buf = await res.clone().arrayBuffer();
    if (isLoginPath(path) && !htmlLooksLikeLogin(buf)) {
        return false;
    }
    if (isPosPath(path) && posCatalogIsEmpty(buf)) {
        return false;
    }
    const contentType = res.headers.get('Content-Type') || 'text/html; charset=UTF-8';
    const stored = new Response(buf, {
        status: 200,
        statusText: 'OK',
        headers: { 'Content-Type': contentType, 'X-Ftpos-Cached': '1' },
    });
    const absolute = new URL(path, window.location.origin).href;
    await cache.put(path, stored.clone());
    await cache.put(absolute, stored.clone());
    if (path.endsWith('/')) {
        await cache.put(path.replace(/\/$/, '') || '/', stored.clone());
    } else {
        await cache.put(`${path}/`, stored.clone());
    }
    return true;
}

async function collectSupplierPageUrls() {
    try {
        const suppliers = (await db.suppliers.toArray()).slice(0, MAX_SUPPLIER_PREFETCH);
        const urls = [];
        for (const supplier of suppliers) {
            if (supplier?.id == null) {
                continue;
            }
            const id = encodeURIComponent(supplier.id);
            urls.push(`/suppliers/${id}`);
            urls.push(`/suppliers/${id}/transactions/create`);
        }
        return urls;
    } catch {
        return [];
    }
}

/**
 * Cache Vite build assets so login/POS JS still runs when the network drops.
 * Without this, the service worker may serve an empty JS stub and offline login dies.
 */
export async function precacheBuildAssets() {
    if (!isOnline() || !('caches' in window)) {
        return { ok: 0, failed: 0 };
    }

    const cache = await caches.open(CACHE_NAME);
    const urls = new Set();

    try {
        document.querySelectorAll('script[src], link[rel="stylesheet"][href]').forEach((el) => {
            const raw = el.getAttribute('src') || el.getAttribute('href');
            if (!raw) {
                return;
            }
            try {
                const u = new URL(raw, window.location.origin);
                if (u.origin === window.location.origin) {
                    urls.add(u.pathname + u.search);
                    urls.add(u.href);
                }
            } catch {
                // ignore
            }
        });
    } catch {
        // ignore
    }

    try {
        const manifestRes = await fetch('/build/manifest.json', {
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (manifestRes.ok) {
            await cache.put('/build/manifest.json', manifestRes.clone());
            await cache.put(new URL('/build/manifest.json', window.location.origin).href, manifestRes.clone());
            const manifest = await manifestRes.json();
            Object.values(manifest || {}).forEach((entry) => {
                if (entry?.file) {
                    urls.add(`/build/${entry.file}`);
                }
                (entry?.css || []).forEach((css) => urls.add(`/build/${css}`));
                (entry?.assets || []).forEach((asset) => urls.add(`/build/${asset}`));
            });
        }
    } catch {
        // ignore
    }

    urls.add('/logo.png');
    urls.add('/offline.html');

    let ok = 0;
    let failed = 0;
    for (const url of urls) {
        if (!isOnline()) {
            break;
        }
        try {
            const res = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!res.ok) {
                failed += 1;
                continue;
            }
            await cache.put(url, res.clone());
            try {
                const abs = new URL(url, window.location.origin).href;
                await cache.put(abs, res.clone());
                await cache.put(new URL(url, window.location.origin).pathname, res.clone());
            } catch {
                // ignore
            }
            ok += 1;
        } catch {
            failed += 1;
        }
    }

    return { ok, failed, total: urls.size };
}

/**
 * Fetch each route while online (with cookies) and store in cache.
 */
export async function prefetchAppShells(urls, options = {}) {
    if (!isOnline()) {
        return { ok: 0, failed: 0 };
    }
    if (typeof window !== 'undefined' && (window.location.pathname || '').includes('/login')) {
        return { ok: 0, failed: 0 };
    }

    const extra = options.includeSuppliers ? await collectSupplierPageUrls() : [];
    const list = [...new Set([...(urls && urls.length ? urls : collectNavUrls()), ...extra])]
        .filter((path) => !isLoginPath(path));
    const worker = await waitForServiceWorker();
    if (worker) {
        worker.postMessage({ type: 'PRECACHE', urls: list });
        // Service worker caches pages in the background so this tab stays responsive.
        return { ok: list.length, failed: 0, total: list.length, delegated: true };
    }

    let ok = 0;
    let failed = 0;
    const cache = 'caches' in window ? await caches.open(CACHE_NAME) : null;

    const batchSize = 1;
    for (let i = 0; i < list.length; i += batchSize) {
        if (!isOnline()) {
            break;
        }
        const batch = list.slice(i, i + batchSize);
        await Promise.all(
            batch.map(async (path) => {
                try {
                    const isLogin = path === '/login' || isLoginPath(path);
                    const res = await fetch(isLogin ? '/__ftpos_login_shell' : path, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-cache',
                        redirect: isLogin ? 'manual' : 'follow',
                        headers: { Accept: 'text/html,application/xhtml+xml,*/*' },
                    });
                    if (path === '/login' || isLoginPath(path)) {
                        if (!cache || !(await storePage(cache, '/login', res))) {
                            failed += 1;
                            return;
                        }
                        ok += 1;
                        return;
                    }
                    if (!cache || !(await storePage(cache, path, res))) {
                        failed += 1;
                        return;
                    }
                    if (path === '/' && cache) {
                        try {
                            if (!isLoginPath(new URL(res.url).pathname)) {
                                await storePage(cache, '/dashboard', res);
                            }
                        } catch {
                            // skip
                        }
                    }
                    ok += 1;
                } catch {
                    failed += 1;
                }
            })
        );
    }

    return { ok, failed, total: list.length };
}
