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
import { queueOfflineSale, queueOfflineCustomer, queueOfflineExpense } from './outbox';
import { db, getMeta, pendingOutboxCount } from './db';
import { onBroadcast } from './broadcast';

const PASSWORD_STASH_KEY = 'ftpos_enroll_password';

function isLoginPage() {
    return !!document.querySelector('form[action*="login"]') || window.location.pathname.includes('/login');
}

function isAuthenticatedShell() {
    return !!document.querySelector('meta[name="csrf-token"]') && !isLoginPage() && !!document.getElementById('ftpos-app-shell');
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

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('[offline] SW registration failed', err);
        });
    });
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
        getLocalSession,
        clearLocalSession,
        unlockOffline,
        hasAnyVault,
    };
}

export async function bootOfflineRuntime() {
    exposeApi();
    registerServiceWorker();
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
    });

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
        syncOnlineOnlyBadges(e.detail?.status === 'online');
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
