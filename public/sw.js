/* Farhan Traders offline Service Worker — no install prompt */
const CACHE_NAME = 'ftpos-pages-v3';
const SHELL_URLS = ['/offline.html', '/logo.png'];
const NAV_TIMEOUT_ONLINE_MS = 2000;
const NAV_TIMEOUT_UNCACHED_MS = 8000;

/** Page runtime tells us when the link is actually usable. Default to cache. */
let preferCache = true;

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

async function storePage(cache, path, res) {
  if (!res || !res.ok) {
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
  if (event.data?.type === 'PRECACHE' && Array.isArray(event.data.urls)) {
    event.waitUntil(precacheUrls(event.data.urls));
  }
});

async function fetchWithTimeout(request, ms) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);
  try {
    return await fetch(request, { signal: controller.signal });
  } finally {
    clearTimeout(timer);
  }
}

async function handleNavigation(request) {
  const url = new URL(request.url);
  const cached = await matchNavigation(request);

  if (preferCache && cached && !String(cached.url || '').includes('/offline.html')) {
    return cached;
  }

  try {
    const res = await fetchWithTimeout(
      request,
      cached && !String(cached.url || '').includes('/offline.html')
        ? NAV_TIMEOUT_ONLINE_MS
        : NAV_TIMEOUT_UNCACHED_MS
    );
    if (res && res.ok) {
      try {
        const cache = await caches.open(CACHE_NAME);
        await storePage(cache, url.pathname, res.clone());
      } catch (e) {}
      return res;
    }
  } catch (e) {}

  return cached;
}

async function matchNavigation(request) {
  const url = new URL(request.url);
  const cache = await caches.open(CACHE_NAME);
  const candidates = [
    request,
    url.href,
    url.pathname,
    url.pathname.endsWith('/') ? url.pathname.slice(0, -1) : url.pathname + '/',
  ];
  for (const key of candidates) {
    const hit = await cache.match(key, { ignoreSearch: true });
    if (hit) {
      return hit;
    }
  }
  const globalHit = await caches.match(request, { ignoreSearch: true });
  if (globalHit) {
    return globalHit;
  }
  if (url.pathname === '/' || url.pathname === '') {
    const dash = await cache.match('/dashboard', { ignoreSearch: true })
      || await cache.match(new URL('/dashboard', self.location.origin).href);
    if (dash) {
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
        // Always network-first for sw.js so updates apply
        if (url.pathname === '/sw.js') {
          try {
            const fresh = await fetch(req);
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
