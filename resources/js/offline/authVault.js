import bcrypt from 'bcryptjs';
import { db, getMeta, setMeta } from './db';
import { broadcast } from './broadcast';

const LOCKOUT_KEY = 'vault_lockout';
const MAX_ATTEMPTS = 5;
const LOCKOUT_MS = 60_000;

function normalizeHash(hash) {
    if (!hash) {
        return hash;
    }
    return hash.replace(/^\$2y\$/, '$2a$');
}

export async function saveVault({ email, passwordHash, user }) {
    const normalizedEmail = email.trim().toLowerCase();
    await db.vault.put({
        email: normalizedEmail,
        password_hash: passwordHash,
        user: {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role,
            branch_id: user.branch_id,
            is_admin: !!user.is_admin,
        },
        enrolled_at: new Date().toISOString(),
    });
    await setMeta('offline_ready', true);
    broadcast('vault', { email: normalizedEmail });
}

export async function getVault(email) {
    return db.vault.get(email.trim().toLowerCase());
}

export async function hasAnyVault() {
    return (await db.vault.count()) > 0;
}

export async function listVaultEmails() {
    return db.vault.toCollection().primaryKeys();
}

async function getLockout() {
    return (await getMeta(LOCKOUT_KEY)) || { attempts: 0, until: 0 };
}

async function setLockout(data) {
    await setMeta(LOCKOUT_KEY, data);
}

export async function unlockOffline(email, password) {
    const lock = await getLockout();
    if (lock.until && Date.now() < lock.until) {
        const seconds = Math.ceil((lock.until - Date.now()) / 1000);
        throw new Error(`Too many attempts. Try again in ${seconds}s.`);
    }

    const vault = await getVault(email);
    if (!vault) {
        throw new Error('No offline profile for this email on this device. Connect and log in once online.');
    }

    const ok = bcrypt.compareSync(password, normalizeHash(vault.password_hash));
    if (!ok) {
        const attempts = (lock.attempts || 0) + 1;
        const next = { attempts, until: 0 };
        if (attempts >= MAX_ATTEMPTS) {
            next.until = Date.now() + LOCKOUT_MS;
            next.attempts = 0;
        }
        await setLockout(next);
        throw new Error('Invalid email or password.');
    }

    await setLockout({ attempts: 0, until: 0 });
    await setLocalSession(vault.user);
    return vault.user;
}

export async function setLocalSession(user) {
    await db.localSession.put({
        key: 'current',
        user,
        unlocked_at: new Date().toISOString(),
    });
    broadcast('session', { user });
}

export async function getLocalSession() {
    const row = await db.localSession.get('current');
    return row?.user || null;
}

export async function clearLocalSession() {
    await db.localSession.delete('current');
    broadcast('session', { user: null });
}

export async function enrollVaultWithPassword(password) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch('/sync/enroll-vault', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ password }),
    });

    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.message || 'Could not enroll offline vault.');
    }

    const data = await res.json();
    await saveVault({
        email: data.user.email,
        passwordHash: data.password_hash,
        user: data.user,
    });
    await setLocalSession(data.user);
    return data.user;
}
