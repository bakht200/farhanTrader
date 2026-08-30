/* Farhan Traders offline Service Worker — no install prompt */
const CACHE_NAME = 'ftpos-pages-v13';
const APP_SHELL_PATH = '/__ftpos_app_shell';
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
      let loginRes = await fetch('/login', {
        credentials: 'same-origin',
        cache: 'no-store',
        redirect: 'manual',
      });
      if (!loginRes || !loginRes.ok || isRedirectResponse(loginRes)) {
        loginRes = await fetch('/__ftpos_login_shell', {
          credentials: 'same-origin',
          cache: 'no-store',
          redirect: 'manual',
        });
      }
      if (loginRes && loginRes.ok && !isRedirectResponse(loginRes)) {
        await storePage(cache, '/login', loginRes);
      }
    } catch (e) {}
    await self.skipWaiting();
  })());
});

async function adoptOldCaches(newCache) {
  const keys = await caches.keys();
  const stale = keys.filter((k) => k !== CACHE_NAME && k.startsWith('ftpos-'));
  for (const name of stale) {
    try {
      const old = await caches.open(name);
      const reqs = await old.keys();
      for (const req of reqs) {
        const already = await newCache.match(req);
        if (already) {
          continue;
        }
        const res = await old.match(req);
        if (res) {
          await newCache.put(req, res);
        }
      }
      await caches.delete(name);
    } catch (e) {}
  }
}

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    await adoptOldCaches(cache);
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

function isCustomerAppPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/customers' || p.startsWith('/customers/');
}

function isDashboardPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/' || p === '/dashboard';
}

function htmlLooksLikeDashboard(buf) {
  try {
    const slice = buf.byteLength > 8192 ? buf.slice(0, 8192) : buf;
    const text = new TextDecoder().decode(slice);
    return text.includes('data-ftpos-page="dashboard"')
      || text.includes("here's what's happening with your store");
  } catch (e) {
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
  } catch (e) {
    return false;
  }
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

function fallbackDocument() {
  return new Response(
    '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Farhan Traders</title></head><body style="font-family:sans-serif;padding:24px;max-width:32rem;margin:40px auto"><h1>Farhan Traders</h1><p>This page is not on this PC yet. Open the site once while online, then you can use it with Wi-Fi off.</p><p><a href="/login">Sign In</a> · <a href="/dashboard">Dashboard</a></p></body></html>',
    { status: 200, headers: { 'Content-Type': 'text/html; charset=UTF-8', 'Cache-Control': 'no-store' } }
  );
}

async function firstHtml(paths) {
  for (const path of paths) {
    const hit = await caches.match(path, { ignoreSearch: true })
      || await caches.match(new URL(path, self.location.origin).href, { ignoreSearch: true });
    if (hit && !isOfflineHtml(hit)) {
      if (path === '/login' || String(path).replace(/\/+$/, '') === '/login') {
        const buf = await hit.clone().arrayBuffer();
        if (!htmlLooksLikeLogin(buf)) {
          continue;
        }
      }
      return hit;
    }
  }
  return null;
}

async function offlineNavigationFallback(requestPath, preferLogin) {
  const p = (requestPath || '').replace(/\/+$/, '') || '/';
  if (preferLogin) {
    return serveLoginHtml();
  }
  if (isCustomerAppPath(p)) {
    return (await firstHtml(['/customers', APP_SHELL_PATH, '/dashboard', '/login'])) || fallbackDocument();
  }
  if (isSupplierAppPath(p)) {
    return (await firstHtml(['/suppliers', APP_SHELL_PATH, '/dashboard', '/login'])) || fallbackDocument();
  }
  if (isDashboardPath(p)) {
    return (await firstHtml(['/dashboard', APP_SHELL_PATH, '/customers', '/suppliers', '/login'])) || fallbackDocument();
  }
  return (await firstHtml([p, APP_SHELL_PATH, '/dashboard', '/login'])) || fallbackDocument();
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
  if (isLoginPath(path) && !htmlLooksLikeLogin(buf)) {
    return false;
  }
  if (isPosPath(path) && posCatalogIsEmpty(buf)) {
    return false;
  }
  if (!isDashboardPath(path) && !isLoginPath(path) && htmlLooksLikeDashboard(buf)) {
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
  if (
    !isLoginPath(path)
    && path !== '/offline.html'
    && !path.startsWith('/__ftpos_')
    && !isOfflineHtml(stored)
  ) {
    await cache.put(APP_SHELL_PATH, stored.clone());
    await cache.put(new URL(APP_SHELL_PATH, self.location.origin).href, stored.clone());
  }
  return true;
}

let precacheGeneration = 0;

async function precacheUrls(urls) {
  const generation = precacheGeneration;
  if (loggedOut) {
    return;
  }
  const cache = await caches.open(CACHE_NAME);
  for (const path of urls) {
    if (generation !== precacheGeneration || loggedOut) {
      return;
    }
    try {
      if (!isLoginPath(path)) {
        const already = await cache.match(path, { ignoreSearch: true })
          || await cache.match(new URL(path, self.location.origin).href, { ignoreSearch: true });
        if (already && already.ok) {
          continue;
        }
      }
      const fetchPath = isLoginPath(path) ? '/__ftpos_login_shell' : path;
      const res = await fetch(fetchPath, {
        credentials: 'same-origin',
        cache: 'no-cache',
        redirect: 'manual',
      });
      if (!res || !res.ok || isRedirectResponse(res)) {
        continue;
      }
      await storePage(cache, isLoginPath(path) ? '/login' : path, res);
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
    precacheGeneration += 1;
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
    if (loggedOut) {
      return;
    }
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
  const keys = await caches.keys();
  for (const name of keys) {
    try {
      const cache = await caches.open(name);
      const hit = await cache.match('/login', { ignoreSearch: true })
        || await cache.match(new URL('/login', self.location.origin).href, { ignoreSearch: true });
      if (hit && !isOfflineHtml(hit)) {
        const buf = await hit.clone().arrayBuffer();
        if (htmlLooksLikeLogin(buf)) {
          return hit;
        }
      }
    } catch (e) {}
  }
  return null;
}

async function serveLoginHtml() {
  return (await cachedLoginPage()) || fallbackDocument();
}

async function handleAuthNavigation(request) {
  if (browserIsOffline()) {
    return serveLoginHtml();
  }
  try {
    const res = await fetch(request, { cache: 'no-store', credentials: 'same-origin', redirect: 'manual' });
    if (!res || !res.ok || isRedirectResponse(res)) {
      return serveLoginHtml();
    }
    try {
      const cache = await caches.open(CACHE_NAME);
      const path = new URL(request.url).pathname;
      await storePage(cache, path, res.clone());
    } catch (e) {}
    return res;
  } catch (e) {
    return serveLoginHtml();
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
      return serveLoginHtml();
    }
    // Internet is on: a just-submitted Sign In must reach Laravel.
    // Never keep painting the login form over /dashboard.
    try {
      const res = await fetchWithTimeout(request, NAV_TIMEOUT_UNCACHED_MS);
      if (res && res.ok && !isRedirectResponse(res) && !responseIsLogin(res) && !isOfflineHtml(res)) {
        await persistLoggedOut(false);
        try {
          const cache = await caches.open(CACHE_NAME);
          await storePage(cache, path, res.clone());
        } catch (e) {}
        return res;
      }
    } catch (e) {}
    return serveLoginHtml();
  }

  if (isLoginPath(path)) {
    if (offline) {
      return serveLoginHtml();
    }
    return handleAuthNavigation(request);
  }

  if (isLogoutPath(path)) {
    return serveLoginHtml();
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
      return serveLoginHtml();
    }
    if (isRedirectResponse(res)) {
      if (cacheUsable) {
        return cached;
      }
      if (offline) {
        return offlineNavigationFallback(path, false);
      }
      return fallbackDocument();
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

  if (cached) {
    return cached;
  }
  if (offline) {
    return offlineNavigationFallback(path, false);
  }
  return fallbackDocument();
}

async function matchNavigation(request) {
  const url = new URL(request.url);
  if (loggedOut && !isLoginPath(url.pathname)) {
    return cachedLoginPage();
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
    if (hit && isLoginPath(url.pathname)) {
      return hit;
    }
    if (hit && !responseIsLogin(hit) && !isOfflineHtml(hit)) {
      if (!isDashboardPath(url.pathname)) {
        const buf = await hit.clone().arrayBuffer();
        if (htmlLooksLikeDashboard(buf)) {
          continue;
        }
      }
      return hit;
    }
  }
  if (isCustomerAppPath(url.pathname)) {
    const list = await cache.match('/customers', { ignoreSearch: true })
      || await cache.match(new URL('/customers', self.location.origin).href);
    if (list && !responseIsLogin(list) && !isOfflineHtml(list)) {
      const buf = await list.clone().arrayBuffer();
      if (!htmlLooksLikeDashboard(buf)) {
        return list;
      }
    }
  }
  if (isSupplierAppPath(url.pathname)) {
    const list = await cache.match('/suppliers', { ignoreSearch: true })
      || await cache.match(new URL('/suppliers', self.location.origin).href);
    if (list && !responseIsLogin(list) && !isOfflineHtml(list)) {
      const buf = await list.clone().arrayBuffer();
      if (!htmlLooksLikeDashboard(buf)) {
        return list;
      }
    }
  }
  if (!loggedOut) {
    const shell = await cache.match(APP_SHELL_PATH)
      || await cache.match('/dashboard', { ignoreSearch: true })
      || await cache.match(new URL('/dashboard', self.location.origin).href);
    if (shell && !responseIsLogin(shell) && !isOfflineHtml(shell)) {
      return shell;
    }
  }
  return null;
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  const offline = browserIsOffline();

  // Sign In POST is a document navigation. Never send it to the network
  // when the link is down — that is Chrome's dinosaur page.
  // When online, let the browser complete the POST so /dashboard is not canceled.
  if (isLoginPath(url.pathname) && req.method === 'POST') {
    if (offline) {
      event.respondWith(serveLoginHtml());
    }
    return;
  }

  // Logout POST is a document navigation. Never send it to the network
  // when Wi‑Fi is off — that is the Chrome dinosaur page.
  if (req.mode === 'navigate' && isLogoutPath(url.pathname)) {
    if (offline) {
      event.respondWith((async () => {
        await persistLoggedOut(true);
        await persistVaultSession(false);
        return serveLoginHtml();
      })());
      return;
    }
    return;
  }

  if (req.mode === 'navigate' && offline) {
    event.respondWith(handleNavigation(new Request(req.url, {
      method: 'GET',
      mode: 'navigate',
      credentials: 'same-origin',
    })));
    return;
  }

  if (req.method !== 'GET') {
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
        const offline = browserIsOffline();
        const cachedAsset = await cache.match(req) || await cache.match(url.pathname);
        if (offline && cachedAsset) {
          return cachedAsset;
        }
        // Network-first for scripts so logout/session fixes are not stuck on an old bundle.
        if (url.pathname === '/sw.js' || url.pathname.startsWith('/build/') || url.pathname.endsWith('.js')) {
          try {
            const fresh = await fetch(req, { cache: 'no-store' });
            if (fresh && fresh.ok && url.pathname.startsWith('/build/')) {
              cache.put(req, fresh.clone());
            }
            return fresh;
          } catch (e) {
            if (cachedAsset) {
              return cachedAsset;
            }
            if (url.pathname.endsWith('.js') || url.pathname.startsWith('/build/')) {
              return new Response('/* offline */', {
                status: 200,
                headers: { 'Content-Type': 'application/javascript' },
              });
            }
            return new Response('', { status: 200 });
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
