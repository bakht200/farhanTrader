{{-- Shared stock snackbar + sound (Dashboard + POS) --}}
<script>
(function () {
    if (window.FTStockNotify) return;

    let snackbarQueue = [];
    let snackbarShowing = false;
    let audioCtx = null;
    let unlockBound = false;

    // Clear any leftover test-alert state from earlier builds
    try {
        localStorage.removeItem('ftpos-test-stock-alerts');
    } catch (e) {}
    document.getElementById('ft-test-stock-alerts-banner')?.remove();

    function ensureAudioCtx() {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return null;
        if (!audioCtx) audioCtx = new AudioCtx();
        return audioCtx;
    }

    async function unlockAudio() {
        try {
            const ctx = ensureAudioCtx();
            if (ctx && ctx.state === 'suspended') {
                await ctx.resume();
            }
            if (ctx) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                gain.gain.value = 0.0001;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.03);
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    function bindAudioUnlock() {
        if (unlockBound) return;
        unlockBound = true;
        const unlockOnce = () => {
            unlockAudio();
            document.removeEventListener('pointerdown', unlockOnce, true);
            document.removeEventListener('keydown', unlockOnce, true);
        };
        document.addEventListener('pointerdown', unlockOnce, true);
        document.addEventListener('keydown', unlockOnce, true);
    }

    function playWavBeep(level) {
        try {
            const isOut = level === 'out';
            const freq = isOut ? 880 : 660;
            const duration = isOut ? 0.35 : 0.18;
            const sampleRate = 22050;
            const numSamples = Math.floor(sampleRate * duration);
            const dataSize = numSamples * 2;
            const buffer = new ArrayBuffer(44 + dataSize);
            const view = new DataView(buffer);

            const writeStr = (offset, str) => {
                for (let i = 0; i < str.length; i++) view.setUint8(offset + i, str.charCodeAt(i));
            };
            writeStr(0, 'RIFF');
            view.setUint32(4, 36 + dataSize, true);
            writeStr(8, 'WAVE');
            writeStr(12, 'fmt ');
            view.setUint32(16, 16, true);
            view.setUint16(20, 1, true);
            view.setUint16(22, 1, true);
            view.setUint32(24, sampleRate, true);
            view.setUint32(28, sampleRate * 2, true);
            view.setUint16(32, 2, true);
            view.setUint16(34, 16, true);
            writeStr(36, 'data');
            view.setUint32(40, dataSize, true);

            for (let i = 0; i < numSamples; i++) {
                const t = i / sampleRate;
                const envelope = Math.min(1, i / (sampleRate * 0.02)) * Math.max(0, 1 - i / numSamples);
                const sample = Math.sin(2 * Math.PI * freq * t) * envelope * (isOut ? 0.55 : 0.4);
                view.setInt16(44 + i * 2, sample * 32767, true);
            }

            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
            const audio = new Audio('data:audio/wav;base64,' + btoa(binary));
            audio.volume = 0.85;
            const p = audio.play();
            if (p && typeof p.catch === 'function') p.catch(() => {});

            if (isOut) {
                setTimeout(() => playWavBeep('low'), 220);
            }
        } catch (e) {
            // ignore
        }
    }

    async function playSound(level = 'low') {
        try {
            await unlockAudio();
            playWavBeep(level);
        } catch (e) {
            // ignore
        }
    }

    function showSnackbar(message, level = 'low') {
        snackbarQueue.push({ message, level });
        drainQueue();
    }

    function drainQueue() {
        if (snackbarShowing || snackbarQueue.length === 0) return;
        snackbarShowing = true;
        const { message, level } = snackbarQueue.shift();

        document.getElementById('stock-alert-snackbar')?.remove();

        const snackbar = document.createElement('div');
        snackbar.id = 'stock-alert-snackbar';
        const bgClass = level === 'out' ? 'bg-red-600' : 'bg-amber-500';
        snackbar.className = `fixed bottom-4 right-4 z-[10050] px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 min-w-[300px] max-w-md text-white transform transition-all duration-300 ${bgClass}`;
        snackbar.innerHTML = `
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${level === 'out' ? 'M6 18L18 6M6 6l12 12' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'}"></path>
            </svg>
            <span class="flex-1 text-sm font-medium stock-alert-message"></span>
            <button type="button" class="text-white hover:text-gray-200" aria-label="Dismiss">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        const messageEl = snackbar.querySelector('.stock-alert-message');
        if (messageEl) messageEl.textContent = message;

        const dismiss = () => {
            snackbar.style.opacity = '0';
            snackbar.style.transform = 'translateY(20px)';
            setTimeout(() => {
                snackbar.remove();
                snackbarShowing = false;
                drainQueue();
            }, 300);
        };
        snackbar.querySelector('button')?.addEventListener('click', dismiss);
        document.body.appendChild(snackbar);
        setTimeout(dismiss, 5000);
    }

    function handleStockAlerts(alerts) {
        if (!Array.isArray(alerts) || alerts.length === 0) return;

        const dayKey = () => {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        };
        const storageKey = (productId, level) => `ftpos-stock-alert:${dayKey()}:${productId}:${level}`;
        const wasShown = (productId, level) => {
            try { return localStorage.getItem(storageKey(productId, level)) === '1'; } catch (e) { return false; }
        };
        const markShown = (productId, level) => {
            try { localStorage.setItem(storageKey(productId, level), '1'); } catch (e) {}
        };

        const fresh = alerts.filter((alert) => {
            const productId = Number(alert.product_id);
            const level = alert.level === 'out' ? 'out' : 'low';
            if (!productId) return true;
            if (wasShown(productId, level)) return false;
            markShown(productId, level);
            return true;
        });

        if (!fresh.length) return;

        let highest = 'low';
        fresh.forEach((alert) => {
            if (alert.level === 'out') highest = 'out';
            const name = alert.name || 'Product';
            const remaining = parseFloat(alert.remaining);
            const message = alert.level === 'out'
                ? `Out of stock: ${name}`
                : `Low stock: ${name} — ${Number.isNaN(remaining) ? '?' : remaining} left`;
            showSnackbar(message, alert.level === 'out' ? 'out' : 'low');
            if (window.FTQuantityAlerts?.updateProductRemaining) {
                window.FTQuantityAlerts.updateProductRemaining(alert.product_id, alert.remaining, { name });
            }
        });
        playSound(highest);
    }

    window.FTStockNotify = {
        showSnackbar,
        playSound,
        unlockAudio,
        handleStockAlerts,
    };

    window.showStockSnackbar = showSnackbar;
    window.playStockAlertSound = playSound;
    window.handleStockAlerts = handleStockAlerts;

    bindAudioUnlock();
})();
</script>
