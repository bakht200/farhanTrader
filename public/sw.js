/* Farhan Traders offline Service Worker — no install prompt */
const CACHE_NAME = 'ftpos-pages-v5';
const SHELL_URLS = ['/offline.html', '/logo.png'];
const NAV_TIMEOUT_ONLINE_MS = 2000;
const NAV_TIMEOUT_UNCACHED_MS = 8000;

/** Page runtime tells us when the link is actually usable. Default to cache. */
let preferCache = true;
/** After logout or an expired-session redirect, do not serve cached app shells. */
let loggedOut = false;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_URLS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => k !== CACHE_NAME && k.startsWith('ftpos-'))
          .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

function isLoginPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/login' || p.startsWith('/login/');
}

function isLogoutPath(pathname) {
  const p = (pathname || '').replace(/\/+$/, '') || '/';
  return p === '/logout' || p.startsWith('/logout/');
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
  loggedOut = true;
  preferCache = false;
  const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
  clients.forEach((client) => client.postMessage({ type: 'SESSION_EXPIRED' }));
}

async function storePage(cache, path, res) {
  if (!res || !res.ok) {
    return false;
  }
  // Never store login HTML, and never store a login redirect under another route.
  if (isLoginPath(path) || responseIsLogin(res)) {
    return false;
  }
  // Rebuild response so cache.put works even after redirects
  const buf = await res.clone().arrayBuffer();
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
    loggedOut = true;
    preferCache = false;
  }
  if (event.data?.type === 'PRECACHE' && Array.isArray(event.data.urls)) {
    event.waitUntil(precacheUrls(event.data.urls));
  }
});

async function fetchWithTimeout(request, ms) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);
  try {
    return await fetch(request, { signal: controller.signal, cache: 'no-store', credentials: 'same-origin', redirect: 'follow' });
  } finally {
    clearTimeout(timer);
  }
}

async function handleAuthNavigation(request) {
  try {
    return await fetch(request, { cache: 'no-store', credentials: 'same-origin', redirect: 'follow' });
  } catch (e) {
    return loginRedirect();
  }
}

async function handleNavigation(request) {
  const url = new URL(request.url);
  const path = url.pathname;

  if (loggedOut && !isLoginPath(path)) {
    return loginRedirect();
  }

  if (isLoginPath(path) || isLogoutPath(path)) {
    return handleAuthNavigation(request);
  }

  const cached = await matchNavigation(request);
  const cacheUsable = cached && !isOfflineHtml(cached) && !responseIsLogin(cached);

  // Fast path while the link is slow: serve cache, then check if the session died.
  if (preferCache && cacheUsable) {
    fetch(request, { cache: 'no-store', credentials: 'same-origin', redirect: 'follow' })
      .then((res) => {
        if (res && responseIsLogin(res)) {
          return notifySessionExpired();
        }
        if (res && res.ok && !responseIsLogin(res)) {
          loggedOut = false;
        }
      })
      .catch(() => {});
    return cached;
  }

  try {
    const res = await fetchWithTimeout(
      request,
      cacheUsable ? NAV_TIMEOUT_ONLINE_MS : NAV_TIMEOUT_UNCACHED_MS
    );
    if (res && responseIsLogin(res) && !isLoginPath(path)) {
      await notifySessionExpired();
      return loginRedirect();
    }
    if (res && res.ok && !responseIsLogin(res)) {
      loggedOut = false;
      try {
        const cache = await caches.open(CACHE_NAME);
        await storePage(cache, path, res.clone());
      } catch (e) {}
      return res;
    }
  } catch (e) {}

  if (cacheUsable) {
    return cached;
  }

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
    if (hit && !responseIsLogin(hit)) {
      return hit;
    }
    if (hit && isLoginPath(url.pathname)) {
      return hit;
    }
  }
  const globalHit = await caches.match(request, { ignoreSearch: true });
  if (globalHit && (!responseIsLogin(globalHit) || isLoginPath(url.pathname))) {
    return globalHit;
  }
  if (!loggedOut && (url.pathname === '/' || url.pathname === '')) {
    const dash = await cache.match('/dashboard', { ignoreSearch: true })
      || await cache.match(new URL('/dashboard', self.location.origin).href);
    if (dash && !responseIsLogin(dash)) {
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

  if (url.pathname === '/up' || url.pathname.startsWith('/sync/')) {
    return;
  }

  if (req.mode === 'navigate') {
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
