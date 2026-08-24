/**
 * Prefetch app HTML shells into the Cache Storage / Service Worker
 * so offline navigation works without visiting each page first.
 */

import { isOnline } from './connectivity';

export const CACHE_NAME = 'ftpos-pages';

export const PRECACHE_ROUTES = [
    '/',
    '/dashboard',
    '/login',
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

async function storePage(cache, path, res) {
    if (!res || !res.ok) {
        return false;
    }
    const buf = await res.clone().arrayBuffer();
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

/**
 * Fetch each route while online (with cookies) and store in cache.
 */
export async function prefetchAppShells(urls) {
    if (!isOnline()) {
        return { ok: 0, failed: 0 };
    }

    const list = urls && urls.length ? urls : collectNavUrls();
    const worker = await waitForServiceWorker();
    if (worker) {
        worker.postMessage({ type: 'PRECACHE', urls: list });
    }

    let ok = 0;
    let failed = 0;
    const cache = 'caches' in window ? await caches.open(CACHE_NAME) : null;

    const batchSize = 3;
    for (let i = 0; i < list.length; i += batchSize) {
        if (!isOnline()) {
            break;
        }
        const batch = list.slice(i, i + batchSize);
        await Promise.all(
            batch.map(async (path) => {
                try {
                    const res = await fetch(path, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-cache',
                        redirect: 'follow',
                        headers: { Accept: 'text/html,application/xhtml+xml,*/*' },
                    });
                    if (path === '/login') {
                        try {
                            const finalPath = new URL(res.url).pathname;
                            if (!finalPath.includes('login')) {
                                failed += 1;
                                return;
                            }
                        } catch {
                            failed += 1;
                            return;
                        }
                    }
                    if (!cache || !(await storePage(cache, path, res))) {
                        failed += 1;
                        return;
                    }
                    if (path === '/' && cache) {
                        await storePage(cache, '/dashboard', res);
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
