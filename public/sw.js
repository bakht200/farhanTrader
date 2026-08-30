/* Farhan Traders offline Service Worker — no install prompt */
const CACHE_NAME = 'ftpos-pages-v9';
const SHELL_URLS = ['/offline.html', '/logo.png'];
const NAV_TIMEOUT_ONLINE_MS = 2500;
const NAV_TIMEOUT_UNCACHED_MS = 4000;
const NAV_TIMEOUT_POS_MS = 2500;

/** Page runtime tells us when the link is actually usable. Network-first until told otherwise. */
let preferCache = false;
/** After logout or an expired-session redirect, do not serve cached app shells. */
let loggedOut = false;
/** Vault unlock on this PC — Laravel may have no cookie. Use cache if the server sends /login. */
let vaultSession = false;
const LOGGED_OUT_FLAG = '/__ftpos_logged_out';
const VAULT_SESSION_FLAG = '/__ftpos_vault_session';

async function persistFlag(path, enabled) {
  try {
    const cache = await caches.open(CACHE_NAME);
    if (enabled) {
      await cache.put(path, new Response('1', {
        status: 200,
        headers: { 'Content-Type': 'text/plain', 'X-Ftpos-Auth': '1' },
      }));
    } else {
      await cache.delete(path);
      await cache.delete(new URL(path, self.location.origin).href);
    }
  } catch (e) {}
}

async function persistLoggedOut(value) {
  loggedOut = !!value;
  await persistFlag(LOGGED_OUT_FLAG, loggedOut);
}

async function persistVaultSession(value) {
  vaultSession = !!value;
  await persistFlag(VAULT_SESSION_FLAG, vaultSession);
}

async function restoreLoggedOut() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const hit = await cache.match(LOGGED_OUT_FLAG)
      || await cache.match(new URL(LOGGED_OUT_FLAG, self.location.origin).href);
    loggedOut = !!hit;
    const vaultHit = await cache.match(VAULT_SESSION_FLAG)
      || await cache.match(new URL(VAULT_SESSION_FLAG, self.location.origin).href);
    vaultSession = !!vaultHit;
  } catch (e) {}
  return loggedOut;
}

function ackMessage(event) {
  try {
    event.ports && event.ports[0] && event.ports[0].postMessage({ ok: true });
  } catch (e) {}
}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    await cache.addAll(SHELL_URLS);
    try {
      await cache.add('/login');
    } catch (e) {
      // Login may 302 if a session cookie is present during install.
    }
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    const stale = keys.filter((k) => k !== CACHE_NAME && k.startsWith('ftpos-'));
    await Promise.all(stale.map((k) => caches.delete(k)));
    await restoreLoggedOut();
    await self.clients.claim();
  })());
});

function isLoginPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/login' || p.startsWith('/login/');
}

function isLogoutPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/logout' || p.startsWith('/logout/');
}

function isPosPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/sales/pos';
}

function isWriteNavigationPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/suppliers/anonymous-purchase'
    || p === '/logout'
    || p.startsWith('/logout/');
}

function isSupplierAppPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/suppliers' || p.startsWith('/suppliers/');
}

function posCatalogIsEmpty(buf) {
  try {
    const slice = buf.byteLength > 65536 ? buf.slice(0, 65536) : buf;
    return new TextDecoder().decode(slice).includes('data-ftpos-catalog="empty"');
  } catch (e) {
    return false;
  }
}

function responseIsLogin(res) {
  try {
    return isLoginPath(new URL(res.url).pathname);
  } catch (e) {
    return false;
  }
}

function isOfflineHtml(res) {
  return String(res && res.url ? res.url : '').includes('/offline.html');
}

function loginRedirect() {
  return Response.redirect(new URL('/login', self.location.origin).href, 302);
}

async function notifySessionExpired() {
  await persistLoggedOut(true);
  preferCache = false;
  const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
  clients.forEach((client) => client.postMessage({ type: 'SESSION_EXPIRED' }));
}

async function storePage(cache, path, res) {
  if (!res || !res.ok) {
    return false;
  }
  // Never store a login page under another route (session expired redirect).
  if (responseIsLogin(res) && !isLoginPath(path)) {
    return false;
  }
  // Rebuild response so cache.put works even after redirects
  const buf = await res.clone().arrayBuffer();
  if (isPosPath(path) && posCatalogIsEmpty(buf)) {
    return false;
  }
  const contentType = res.headers.get('Content-Type') || 'text/html; charset=UTF-8';
  const stored = new Response(buf, {
    status: 200,
    statusText: 'OK',
    headers: { 'Content-Type': contentType, 'X-Ftpos-Cached': '1' },
  });
  const absolute = new URL(path, self.location.origin).href;
  await cache.put(path, stored.clone());
  await cache.put(absolute, stored.clone());
  if (path.endsWith('/')) {
    await cache.put(path.replace(/\/$/, '') || '/', stored.clone());
  } else {
    await cache.put(path + '/', stored.clone());
  }
  return true;
}

async function precacheUrls(urls) {
  const cache = await caches.open(CACHE_NAME);
  for (const path of urls) {
    try {
      const res = await fetch(path, {
        credentials: 'same-origin',
        cache: 'no-cache',
        redirect: 'follow',
      });
      await storePage(cache, path, res);
    } catch (e) {
      // ignore individual failures
    }
  }
}

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data?.type === 'CONNECTIVITY') {
    preferCache = event.data.online !== true;
  }
  if (event.data?.type === 'LOGOUT') {
    preferCache = false;
    vaultSession = false;
    event.waitUntil(Promise.all([
      persistLoggedOut(true),
      persistVaultSession(false),
    ]).then(() => ackMessage(event)));
    return;
  }
  if (event.data?.type === 'LOGIN') {
    loggedOut = false;
    vaultSession = event.data?.vault === true || browserIsOffline();
    preferCache = browserIsOffline();
    event.waitUntil(Promise.all([
      persistLoggedOut(false),
      persistVaultSession(true),
    ]).then(() => ackMessage(event)));
    return;
  }
  if (event.data?.type === 'PRECACHE' && Array.isArray(event.data.urls)) {
    event.waitUntil(precacheUrls(event.data.urls));
  }
});

async function fetchWithTimeout(request, ms) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);
  try {
    return await fetch(request, { signal: controller.signal, cache: 'no-store', credentials: 'same-origin', redirect: 'manual' });
  } finally {
    clearTimeout(timer);
  }
}

function isRedirectResponse(res) {
  return !!res && (res.type === 'opaqueredirect' || [301, 302, 303, 307, 308].includes(res.status));
}

function browserIsOffline() {
  return !!(self.navigator && self.navigator.onLine === false);
}

function redirectGoesToLogin(res) {
  if (!isRedirectResponse(res)) {
    return false;
  }
  try {
    const loc = res.headers.get('Location');
    if (!loc) {
      return res.type === 'opaqueredirect';
    }
    return isLoginPath(new URL(loc, self.location.origin).pathname);
  } catch (e) {
    return false;
  }
}

async function cachedLoginPage() {
  return await caches.match(new URL('/login', self.location.origin).href)
    || await caches.match('/login');
}

async function handleAuthNavigation(request) {
  try {
    const res = await fetch(request, { cache: 'no-store', credentials: 'same-origin', redirect: 'manual' });
    if (res && res.ok) {
      try {
        const cache = await caches.open(CACHE_NAME);
        const path = new URL(request.url).pathname;
        await storePage(cache, path, res.clone());
      } catch (e) {}
    }
    return res;
  } catch (e) {
    return (await cachedLoginPage()) || loginRedirect();
  }
}

async function handleNavigation(request) {
  await restoreLoggedOut();
  const url = new URL(request.url);
  const path = url.pathname;
  const forceLive = url.searchParams.has('_live');
  const offline = browserIsOffline();

  if (forceLive) {
    preferCache = false;
  }

  if (loggedOut && !isLoginPath(path)) {
    if (offline) {
      return (await cachedLoginPage()) || loginRedirect();
    }
    try {
      const res = await fetchWithTimeout(request, 2500);
      if (isRedirectResponse(res)) {
        return res;
      }
      if (res && responseIsLogin(res)) {
        return res;
      }
      if (res && res.ok && !responseIsLogin(res) && !isOfflineHtml(res)) {
        await persistLoggedOut(false);
        return res;
      }
    } catch (e) {}
    return (await cachedLoginPage()) || loginRedirect();
  }

  if (isLoginPath(path) || isLogoutPath(path)) {
    if (offline && isLoginPath(path)) {
      return (await cachedLoginPage()) || handleAuthNavigation(request);
    }
    return handleAuthNavigation(request);
  }

  const cached = await matchNavigation(request);
  const cacheUsable = cached && !isOfflineHtml(cached) && !responseIsLogin(cached);

  // Wi‑Fi off still reaches 127.0.0.1. Never ask Laravel for a session then.
  const useCacheFirst = !forceLive && cacheUsable && (preferCache || offline);

  if (useCacheFirst) {
    return cached;
  }

  try {
    const res = await fetchWithTimeout(
      request,
      forceLive || isPosPath(path)
        ? NAV_TIMEOUT_POS_MS
        : (cacheUsable ? NAV_TIMEOUT_ONLINE_MS : NAV_TIMEOUT_UNCACHED_MS)
    );
    if (redirectGoesToLogin(res) || (res && responseIsLogin(res) && !isLoginPath(path))) {
      if (cacheUsable && (offline || preferCache || vaultSession)) {
        return cached;
      }
      if (!offline && !vaultSession) {
        await notifySessionExpired();
      }
      return (await cachedLoginPage()) || loginRedirect();
    }
    if (isRedirectResponse(res)) {
      return res;
    }
    if (res && res.ok && !responseIsLogin(res) && !isOfflineHtml(res)) {
      loggedOut = false;
      try {
        const cache = await caches.open(CACHE_NAME);
        await storePage(cache, path, res.clone());
      } catch (e) {}
      return res;
    }
  } catch (e) {}

  return cached;
}

async function matchNavigation(request) {
  const url = new URL(request.url);
  if (loggedOut && !isLoginPath(url.pathname)) {
    return caches.match(new URL('/login', self.location.origin).href)
      || caches.match('/login');
  }
  const cache = await caches.open(CACHE_NAME);
  const candidates = [
    request,
    url.href,
    url.pathname,
    url.pathname.endsWith('/') ? url.pathname.slice(0, -1) : url.pathname + '/',
  ];
  for (const key of candidates) {
    const hit = await cache.match(key, { ignoreSearch: true });
    if (hit && !responseIsLogin(hit) && !isOfflineHtml(hit)) {
      return hit;
    }
    if (hit && isLoginPath(url.pathname)) {
      return hit;
    }
  }
  const globalHit = await caches.match(request, { ignoreSearch: true });
  if (globalHit && !isOfflineHtml(globalHit) && (!responseIsLogin(globalHit) || isLoginPath(url.pathname))) {
    return globalHit;
  }
  if (isSupplierAppPath(url.pathname)) {
    const list = await cache.match('/suppliers', { ignoreSearch: true })
      || await cache.match(new URL('/suppliers', self.location.origin).href);
    if (list && !responseIsLogin(list) && !isOfflineHtml(list)) {
      return list;
    }
  }
  if (!loggedOut) {
    const dash = await cache.match('/dashboard', { ignoreSearch: true })
      || await cache.match(new URL('/dashboard', self.location.origin).href);
    if (dash && !responseIsLogin(dash) && !isOfflineHtml(dash)) {
      return dash;
    }
  }
  return cache.match('/offline.html');
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  if (url.pathname === '/up' || url.pathname === '/csrf-token' || url.pathname.startsWith('/sync/')) {
    return;
  }

  if (req.mode === 'navigate') {
    if (isWriteNavigationPath(url.pathname)) {
      return;
    }
    event.respondWith(handleNavigation(req));
    return;
  }

  if (
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/css/') ||
    url.pathname.startsWith('/js/') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname === '/sw.js'
  ) {
    event.respondWith(
      caches.open(CACHE_NAME).then(async (cache) => {
        // Network-first for scripts so logout/session fixes are not stuck on an old bundle.
        if (url.pathname === '/sw.js' || url.pathname.startsWith('/build/') || url.pathname.endsWith('.js')) {
          try {
            const fresh = await fetch(req, { cache: 'no-store' });
            if (fresh && fresh.ok && url.pathname.startsWith('/build/')) {
              cache.put(req, fresh.clone());
            }
            return fresh;
          } catch (e) {
            return (await cache.match(req)) || Response.error();
          }
        }
        const cached = await cache.match(req);
        const network = fetch(req)
          .then((res) => {
            if (res && res.ok) {
              cache.put(req, res.clone());
            }
            return res;
          })
          .catch(() => cached);
        return cached || network;
      })
    );
  }
});
