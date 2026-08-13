import { startConnectivityMonitor, isOnline, checkNow } from './connectivity';
import { mountOfflineBanner, showToast } from './banner';
import { bootstrap, startSyncScheduler, syncNow } from './sync';
import {
    enrollVaultWithPassword,
    unlockOffline,
    hasAnyVault,
    getLocalSession,
    clearLocalSession,
    listVaultEmails,
} from './authVault';
import { queueOfflineSale, queueOfflineCustomer, queueOfflineExpense, queueOfflineSupplier } from './outbox';
import { db, getMeta, setMeta, pendingOutboxCount } from './db';
import { onBroadcast, broadcast } from './broadcast';
import { prefetchAppShells, CACHE_NAME } from './prefetch';

const PASSWORD_STASH_KEY = 'ftpos_enroll_password';

function isLoginPage() {
    return !!document.querySelector('form[action*="login"]') || window.location.pathname.includes('/login');
}

async function maybeEnrollAfterLogin() {
    const password = sessionStorage.getItem(PASSWORD_STASH_KEY);
    if (!password) {
        return;
    }
    sessionStorage.removeItem(PASSWORD_STASH_KEY);
    if (!isOnline()) {
        return;
    }
    try {
        await enrollVaultWithPassword(password);
        await bootstrap();
        showToast('Offline access enabled on this device');
    } catch (e) {
        console.warn('[offline] enroll failed', e);
    }
}

async function warmOfflineShells() {
    if (!isOnline()) {
        return;
    }
    try {
        const result = await prefetchAppShells();
        if (result.ok > 0) {
            console.info(`[offline] precached ${result.ok} pages for offline use`);
        }
    } catch (e) {
        console.warn('[offline] page precache failed', e);
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

    // Stash password for vault enroll after successful online login
    form.addEventListener('submit', async (event) => {
        const email = emailInput?.value || '';
        const password = passwordInput?.value || '';

        const online = await checkNow();
        if (online) {
            sessionStorage.setItem(PASSWORD_STASH_KEY, password);
            return; // normal submit
        }

        event.preventDefault();

        try {
            const hasVault = await hasAnyVault();
            if (!hasVault) {
                if (gate) {
                    gate.classList.remove('hidden');
                }
                showToast('Offline access not set up. Connect and log in once online.');
                return;
            }

            await unlockOffline(email, password);
            showToast('Signed in offline');
            window.location.href = '/dashboard';
        } catch (e) {
            showToast(e.message || 'Offline login failed');
            if (gate) {
                const emails = await listVaultEmails();
                gate.classList.remove('hidden');
                const detail = gate.querySelector('[data-gate-detail]');
                if (detail) {
                    detail.textContent = emails.length
                        ? e.message
                        : 'This device has never signed in while online. Connect to the internet and log in once.';
                }
            }
        }
    });
}

async function guardAuthenticatedOffline() {
    // When offline and visiting app pages without server session, local session is enough for UI that uses IndexedDB.
    if (isOnline()) {
        return;
    }
    const session = await getLocalSession();
    if (!session && !(await hasAnyVault())) {
        // Unenrolled offline — go to login/gate
        if (!isLoginPage()) {
            window.location.href = '/login';
        }
    }
}

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }
    try {
        const reg = await navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' });
        await reg.update().catch(() => {});
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
        bootstrap,
        db,
        getMeta,
        pendingOutboxCount,
        queueOfflineSale,
        queueOfflineCustomer,
        queueOfflineExpense,
        queueOfflineSupplier,
        getLocalSession,
        clearLocalSession,
        unlockOffline,
        hasAnyVault,
        prefetchAppShells,
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
 * When admin switches branch, server sets window.__ftBranchSwitched.
 * Refresh IndexedDB for the new branch and tell other tabs to reload.
 */
async function applyBranchSwitchFromPage() {
    const payload = window.__ftBranchSwitched;
    if (!payload || !payload.id) {
        return;
    }

    // Avoid re-broadcast loops in the same document
    window.__ftBranchSwitched = null;

    try {
        await setMeta('active_branch_id', payload.id);
        await clearPageCaches();
        if (isOnline()) {
            await bootstrap().catch(() => {});
            // Rebuild shells for the new branch context
            warmOfflineShells();
        }
    } catch (e) {
        console.warn('[offline] branch switch refresh failed', e);
    }

    broadcast('branch-changed', {
        id: Number(payload.id),
        name: payload.name || '',
    });
}

export async function bootOfflineRuntime() {
    exposeApi();
    await registerServiceWorker();
    startConnectivityMonitor();
    await mountOfflineBanner();

    if (isLoginPage()) {
        await setupLoginForm();
        return;
    }

    await guardAuthenticatedOffline();

    if (isOnline()) {
        try {
            await maybeEnrollAfterLogin();
            // Always refresh cache when online in app
            if (document.body?.dataset?.ftOfflineBoot !== '0') {
                await bootstrap().catch(async () => {
                    // If bootstrap fails due to auth, ignore; page still works online via Laravel
                });
            }
            // Auto-cache main app pages so offline nav works without visiting each page
            warmOfflineShells();
            startSyncScheduler();
            const pending = await pendingOutboxCount();
            if (pending > 0) {
                await syncNow().catch(() => {});
            }
        } catch (e) {
            console.warn('[offline] boot sync issue', e);
        }
    } else {
        startSyncScheduler();
    }

    onBroadcast((msg) => {
        if (msg?.type === 'session' && msg.payload?.user === null && !isLoginPage()) {
            // Skip redirect during intentional logout — form POST must run first
            if (window.__ftposLoggingOut) {
                return;
            }
            // Logged out in another tab
            window.location.href = '/login';
        }
        if (msg?.type === 'auth-required') {
            showToast('Session expired — log in online to sync pending changes');
        }
        if (msg?.type === 'branch-changed') {
            const switchedId = Number(msg.payload?.id || 0);
            const currentId = Number(document.body?.dataset?.ftBranchId || 0);
            // Same branch already showing — no need to bounce
            if (currentId && switchedId && currentId === switchedId) {
                return;
            }
            clearPageCaches()
                .catch(() => {})
                .finally(() => {
                    if (!window.location.pathname.startsWith('/dashboard')) {
                        window.location.href = '/dashboard';
                    } else {
                        window.location.reload();
                    }
                });
        }
    });

    await applyBranchSwitchFromPage();

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
