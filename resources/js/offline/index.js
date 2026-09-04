import { startConnectivityMonitor, isOnline, checkNow } from './connectivity';
import { mountOfflineBanner, showToast } from './banner';
import { bootstrap, startSyncScheduler, syncNow, uploadPendingToCloud } from './sync';
import {
    enrollVaultWithPassword,
    unlockOffline,
    hasAnyVault,
    getLocalSession,
    clearLocalSession,
    listVaultEmails,
} from './authVault';
import { queueOfflineSale, queueOfflineCustomer, queueOfflineExpense, queueOfflineSupplier, queueOfflineSupplierBill, queueOfflineSupplierPayment, supplierWallet } from './outbox';
import { db, getMeta, setMeta, pendingOutboxCount } from './db';
import { onBroadcast } from './broadcast';
import { prefetchAppShells, CACHE_NAME, CORE_SHELLS, PRECACHE_ROUTES, precacheBuildAssets } from './prefetch';
import { mountOfflineSupplierPanel } from './supplierPanel';
import { mountOfflineListPages } from './listPages';
import {
    isLoginPage,
    APP_VERSION,
    applyHardRefreshIfNeeded,
    installSessionGuards,
    logoutAndRedirect,
    redirectToLogin,
    probeServerSession,
    notifyServiceWorkerLogin,
    persistLastBranchId,
    readLastBranchId,
    resolveOfflineBranchId,
} from './session';

const PASSWORD_STASH_KEY = 'ftpos_enroll_password';

function showEnrollFailure(message) {
    let el = document.getElementById('ftpos-enroll-banner');
    if (!el) {
        el = document.createElement('div');
        el.id = 'ftpos-enroll-banner';
        el.setAttribute('role', 'alert');
        el.style.cssText = [
            'position:fixed',
            'top:0',
            'left:0',
            'right:0',
            'z-index:10002',
            'background:#fef3c7',
            'color:#92400e',
            'padding:12px 16px',
            'font-size:14px',
            'text-align:center',
            'border-bottom:1px solid #f59e0b',
        ].join(';');
        document.body.prepend(el);
    }
    el.textContent = '';
    const text = document.createElement('span');
    text.textContent = message || 'Offline not enabled on this PC. Stay online and sign in again.';
    const retry = document.createElement('button');
    retry.type = 'button';
    retry.textContent = 'Retry';
    retry.style.cssText = 'margin-left:12px;background:#92400e;color:#fff;border:0;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:13px';
    retry.addEventListener('click', async () => {
        retry.disabled = true;
        try {
            if (sessionStorage.getItem(PASSWORD_STASH_KEY)) {
                await maybeEnrollAfterLogin();
                return;
            }
            if (await hasAnyVault()) {
                await refreshOfflineCatalog();
                showToast('Offline access enabled on this device');
                clearEnrollBanner();
                return;
            }
            showToast('Sign out, stay online, and sign in once more.');
        } catch (e) {
            showToast(e.message || 'Retry failed');
        } finally {
            retry.disabled = false;
        }
    });
    el.appendChild(text);
    el.appendChild(retry);
}

function clearEnrollBanner() {
    const banner = document.getElementById('ftpos-enroll-banner');
    if (banner) {
        banner.remove();
    }
}

async function refreshOfflineCatalog() {
    await bootstrap();
    await precacheBuildAssets().catch(() => {});
    await prefetchAppShells().catch(() => {});
}

/**
 * After online login: vault + catalog + JS/CSS + page shells so Wi‑Fi drop is seamless.
 */
async function prepareOfflineDevice({ showSuccessToast = true } = {}) {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        return false;
    }
    try {
        await registerServiceWorker().catch(() => {});
        const enrolled = await maybeEnrollAfterLogin();
        if (!enrolled && !(await hasAnyVault())) {
            return false;
        }
        await bootstrap().catch(() => {});
        await precacheBuildAssets().catch(() => {});
        await cacheLoginShell();
        await pinDashboardNow();
        await prefetchAppShells(CORE_SHELLS).catch(() => {});
        await setMeta('offline_ready', true);
        await setMeta('offline_prepared_at', new Date().toISOString());
        if (showSuccessToast && !enrolled) {
            showToast('Offline ready — this PC works if internet drops');
        }
        clearEnrollBanner();
        return true;
    } catch (e) {
        console.warn('[offline] prepare failed', e);
        return false;
    }
}

async function maybeEnrollAfterLogin() {
    const password = sessionStorage.getItem(PASSWORD_STASH_KEY);
    if (!password) {
        return false;
    }
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        return false;
    }
    try {
        await refreshCsrfToken().catch(() => {});
        await enrollVaultWithPassword(password);
        sessionStorage.removeItem(PASSWORD_STASH_KEY);
        try {
            await refreshOfflineCatalog();
        } catch (e) {
            console.warn('[offline] catalog refresh failed after enroll', e);
        }
        showToast('Offline access enabled on this device');
        clearEnrollBanner();
        return true;
    } catch (e) {
        console.warn('[offline] enroll failed', e);
        showEnrollFailure(e.message || 'Offline not enabled on this PC. Stay online and sign in again.');
        showToast(e.message || 'Could not enable offline access on this device');
        return false;
    }
}

async function openCachedApp(path) {
    const order = [path, '/dashboard', '/__ftpos_app_shell', '/customers', '/suppliers', '/sales/pos', '/products'];
    try {
        const cache = await caches.open(CACHE_NAME);
        for (const candidate of order) {
            const hit = await cache.match(candidate)
                || await cache.match(new URL(candidate, window.location.origin).href)
                || await cache.match(candidate, { ignoreSearch: true });
            if (!hit) {
                continue;
            }
            const url = String(hit.url || '');
            if (url.includes('/offline.html') || url.includes('/login')) {
                continue;
            }
            window.location.replace(candidate.startsWith('/__') ? '/dashboard' : candidate);
            return true;
        }
    } catch {
        // ignore
    }
    window.location.replace(path);
    return false;
}

async function cacheLoginShell() {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        return;
    }
    if (!('caches' in window)) {
        return;
    }
    try {
        const urls = ['/__ftpos_login_shell', '/login'];
        for (const url of urls) {
            const res = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                redirect: 'manual',
                headers: { Accept: 'text/html,application/xhtml+xml,*/*' },
            });
            if (!res.ok) {
                continue;
            }
            const html = await res.clone().text();
            if (html.includes('<div data-ftpos-page="dashboard"')
                || !(html.includes('data-ftpos-page="login"') || (html.includes('Sign In') && html.includes('name="email"')))) {
                continue;
            }
            const cache = await caches.open(CACHE_NAME);
            await cache.put('/login', res.clone());
            await cache.put(new URL('/login', window.location.origin).href, res.clone());
            return;
        }
    } catch {
        // ignore
    }
}

async function pinDashboardNow() {
    if (!isOnline() || !('caches' in window)) {
        return;
    }
    try {
        const res = await fetch('/dashboard', {
            credentials: 'same-origin',
            cache: 'no-cache',
            headers: { Accept: 'text/html,application/xhtml+xml,*/*' },
        });
        if (!res.ok) {
            return;
        }
        const cache = await caches.open(CACHE_NAME);
        const stored = res.clone();
        await cache.put('/dashboard', stored.clone());
        await cache.put(new URL('/dashboard', window.location.origin).href, stored.clone());
        await cache.put('/__ftpos_app_shell', stored.clone());
    } catch {
        // ignore
    }
}

async function warmOfflineShells() {
    if (!isOnline()) {
        return;
    }
    if (window.__ftposWarmingShells) {
        return;
    }
    window.__ftposWarmingShells = true;
    try {
        await precacheBuildAssets().catch(() => {});
        await pinDashboardNow();
        await cacheLoginShell();
        await prefetchAppShells(CORE_SHELLS);
        try {
            if (sessionStorage.getItem(`ftpos_shells_warmed_${APP_VERSION}`)) {
                return;
            }
        } catch {
            // ignore
        }

        const run = async () => {
            if (!isOnline()) {
                return;
            }
            try {
                await precacheBuildAssets().catch(() => {});
                const result = await prefetchAppShells(PRECACHE_ROUTES);
                if (result.ok > 0) {
                    try {
                        sessionStorage.setItem(`ftpos_shells_warmed_${APP_VERSION}`, '1');
                    } catch {
                        // ignore
                    }
                }
            } catch (e) {
                console.warn('[offline] page precache failed', e);
            }
        };
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(() => {
                run();
            }, { timeout: 12000 });
            return;
        }
        setTimeout(run, 4000);
    } finally {
        window.__ftposWarmingShells = false;
    }
}

async function refreshCsrfToken() {
    const res = await fetch('/csrf-token', {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) {
        return false;
    }
    const data = await res.json().catch(() => ({}));
    const token = data.token;
    if (!token) {
        return false;
    }
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }
    return true;
}

async function updateLoginOfflineReadyUi() {
    const ready = document.getElementById('ftpos-offline-ready');
    const gate = document.getElementById('ftpos-offline-gate');
    if (!ready && !gate) {
        return;
    }
    try {
        const emails = await listVaultEmails();
        if (emails.length) {
            if (ready) {
                ready.classList.remove('hidden');
                const detail = ready.querySelector('[data-ready-detail]');
                if (detail) {
                    detail.textContent = `This PC is ready for offline sign-in (${emails.join(', ')}).`;
                }
            }
            if (gate) {
                gate.classList.add('hidden');
            }
        } else if (ready) {
            ready.classList.add('hidden');
        }
    } catch {
        // ignore
    }
}

async function setupLoginForm() {
    const form = document.querySelector('form[action*="login"]');
    if (!form) {
        return;
    }

    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const gate = document.getElementById('ftpos-offline-gate');
    const submitBtn = form.querySelector('button[type="submit"]');
    let submitting = false;

    await updateLoginOfflineReadyUi();

    const refreshIfVisible = () => {
        if (document.visibilityState === 'visible') {
            refreshCsrfToken().catch(() => {});
        }
    };
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            refreshCsrfToken().catch(() => {});
        }
    });
    document.addEventListener('visibilitychange', refreshIfVisible);
    refreshCsrfToken().catch(() => {});
    registerServiceWorker().catch(() => {});

    const setBusy = (busy) => {
        if (submitBtn) {
            submitBtn.disabled = !!busy;
            submitBtn.textContent = busy ? 'Signing in…' : 'Sign In';
        }
    };

    async function tryOfflineLogin(email, password) {
        const hasVault = await hasAnyVault();
        if (!hasVault) {
            if (gate) {
                gate.classList.remove('hidden');
            }
            showToast('Offline access not set up. Connect and log in once online.');
            return false;
        }

        await unlockOffline(email, password);
        try {
            navigator.serviceWorker?.controller?.postMessage({ type: 'CONNECTIVITY', online: false });
        } catch {
            // ignore
        }
        try {
            await navigator.serviceWorker?.ready;
        } catch {
            // ignore
        }
        await notifyServiceWorkerLogin({ vault: true });
        showToast('Signed in offline');
        await openCachedApp('/dashboard');
        return true;
    }

    // Stash password for vault enroll after successful online login
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting) {
            return;
        }

        const email = (emailInput?.value || '').trim();
        const password = passwordInput?.value || '';
        if (!email || !password) {
            showToast('Enter email and password');
            return;
        }

        submitting = true;
        setBusy(true);

        const browserOffline = typeof navigator !== 'undefined' && navigator.onLine === false;
        let serverUp = false;
        if (!browserOffline) {
            try {
                serverUp = await checkNow({ timeoutMs: 1500 });
            } catch {
                serverUp = false;
            }
        }

        if (serverUp) {
            sessionStorage.setItem(PASSWORD_STASH_KEY, password);
            try {
                await refreshCsrfToken();
            } catch {
                // Native submit still works if the token on the page is fresh.
            }
            await notifyServiceWorkerLogin();
            form.submit();
            return;
        }

        try {
            await tryOfflineLogin(email, password);
        } catch (e) {
            showToast(e.message || 'Offline login failed');
            if (gate) {
                const emails = await listVaultEmails();
                gate.classList.remove('hidden');
                const detail = gate.querySelector('[data-gate-detail]');
                if (detail) {
                    detail.textContent = emails.length
                        ? (e.message || 'Wrong email or password for offline unlock on this PC.')
                        : 'This device has never signed in while online. Connect to the internet and log in once.';
                }
            }
            submitting = false;
            setBusy(false);
        }
    });
}

async function guardAuthenticatedOffline() {
    // Cached app shells survive logout. Offline still requires a vault unlock.
    if (isOnline()) {
        return;
    }
    const session = await getLocalSession();
    if (!session && !isLoginPage()) {
        window.location.href = '/login';
    }
}

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }
    try {
        const reg = await navigator.serviceWorker.register(`/sw.js?v=${APP_VERSION}`, { updateViaCache: 'none' });
        reg.update().catch(() => {});
        if (reg.waiting) {
            reg.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
        // Take over immediately when a new SW is waiting
        if (reg.installing) {
            reg.installing.addEventListener('statechange', (e) => {
                if (e.target.state === 'installed' && navigator.serviceWorker.controller) {
                    e.target.postMessage({ type: 'SKIP_WAITING' });
                }
            });
        }
        return reg;
    } catch (err) {
        console.warn('[offline] SW registration failed', err);
        return null;
    }
}

function exposeApi() {
    window.FTOffline = {
        isOnline,
        checkNow,
        syncNow,
        uploadPendingToCloud,
        bootstrap,
        db,
        getMeta,
        pendingOutboxCount,
        queueOfflineSale,
        queueOfflineCustomer,
        queueOfflineExpense,
        queueOfflineSupplier,
        queueOfflineSupplierBill,
        queueOfflineSupplierPayment,
        supplierWallet,
        getLocalSession,
        clearLocalSession,
        unlockOffline,
        hasAnyVault,
        prefetchAppShells,
        precacheBuildAssets,
        prepareOfflineDevice,
        logoutAndRedirect,
        redirectToLogin,
        persistLastBranchId,
        readLastBranchId,
        resolveOfflineBranchId,
    };
}

async function clearPageCaches() {
    if (!('caches' in window)) {
        return;
    }
    try {
        await caches.delete(CACHE_NAME);
        const keys = await caches.keys();
        await Promise.all(
            keys.filter((k) => k.startsWith('ftpos-')).map((k) => caches.delete(k))
        );
    } catch (e) {
        console.warn('[offline] clear page caches failed', e);
    }
}

/**
 * When admin switches branch, server sets window.__ftBranchSwitched (inline script
 * already notified other tabs). Here we refresh IndexedDB for the new branch.
 */
async function applyBranchSwitchFromPage() {
    const payload = window.__ftBranchSwitched;
    if (!payload || !payload.id) {
        return;
    }

    try {
        await setMeta('active_branch_id', payload.id);
        persistLastBranchId(payload.id);
        await clearPageCaches();
        if (isOnline()) {
            await bootstrap().catch(() => {});
            try {
                sessionStorage.removeItem(`ftpos_shells_warmed_${APP_VERSION}`);
            } catch {
                // ignore
            }
            await warmOfflineShells();
        }
    } catch (e) {
        console.warn('[offline] branch switch refresh failed', e);
    }
}

export async function bootOfflineRuntime() {
    exposeApi();
    installSessionGuards();
    if (await applyHardRefreshIfNeeded()) {
        return;
    }
    registerServiceWorker().catch(() => {});
    startConnectivityMonitor().catch(() => {});
    mountOfflineBanner().catch(() => {});

    if (isLoginPage()) {
        setupLoginForm();
        cacheLoginShell();
        if (isOnline()) {
            precacheBuildAssets().catch(() => {});
        }
        return;
    }

    notifyServiceWorkerLogin();
    const pageBranch = Number(document.body?.getAttribute('data-ft-branch-id') || 0);
    if (pageBranch) {
        persistLastBranchId(pageBranch);
        setMeta('active_branch_id', pageBranch).catch(() => {});
    }

    await guardAuthenticatedOffline();
    mountOfflineSupplierPanel();
    mountOfflineListPages().catch(() => {});
    if (isOnline()) {
        probeServerSession().catch(() => {});
    }

    startSyncScheduler();

    if (isOnline()) {
        const justLoggedIn = !!sessionStorage.getItem(PASSWORD_STASH_KEY);
        prepareOfflineDevice({ showSuccessToast: justLoggedIn })
            .then((ok) => {
                if (!ok && document.body?.dataset?.ftOfflineBoot !== '0') {
                    return bootstrap().catch(() => null);
                }
                return null;
            })
            .catch(() => {});
        if (!window.__ftBranchSwitched) {
            warmOfflineShells();
        }
        pendingOutboxCount()
            .then((pending) => (pending > 0 ? syncNow() : null))
            .catch(() => {});
    }

    onBroadcast((msg) => {
        if (msg?.type === 'session' && msg.payload?.user === null && !isLoginPage()) {
            // Skip redirect during intentional logout — form POST must run first
            if (window.__ftposLoggingOut) {
                return;
            }
            // Logged out in another tab
            window.location.replace('/login');
        }
        if (msg?.type === 'auth-required') {
            if (typeof navigator !== 'undefined' && navigator.onLine === false) {
                return;
            }
            redirectToLogin();
        }
        // branch-changed is handled by inline layout script so other tabs
        // refresh even if this Vite bundle is cached/stale.
    });

    applyBranchSwitchFromPage().catch(() => {});

    // Online-only modules — Wi‑Fi badges only when offline
    function syncOnlineOnlyBadges(online = isOnline()) {
        document.querySelectorAll('.ftpos-online-only-badge').forEach((el) => {
            if (online) {
                el.classList.add('hidden');
            } else {
                el.classList.remove('hidden');
            }
        });
    }

    syncOnlineOnlyBadges();
    window.addEventListener('ftpos-connectivity', (e) => {
        const online = e.detail?.status === 'online';
        syncOnlineOnlyBadges(online);
        if (online) {
            warmOfflineShells();
            prepareOfflineDevice({ showSuccessToast: false }).catch(() => {});
            probeServerSession().catch(() => {});
        }
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || isOnline()) {
            return;
        }
        const href = a.getAttribute('href') || '';
        const needsNet = a.hasAttribute('data-requires-internet')
            || href.includes('ai-insights')
            || href.includes('health-check');
        if (needsNet) {
            e.preventDefault();
            showToast('No internet — this feature requires a connection');
        }
    });
}

bootOfflineRuntime();
