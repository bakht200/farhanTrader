const CHANNEL_NAME = 'ftpos-offline';
let channel = null;

export function getChannel() {
    if (!channel && typeof BroadcastChannel !== 'undefined') {
        channel = new BroadcastChannel(CHANNEL_NAME);
    }
    return channel;
}

export function broadcast(type, payload = {}) {
    const ch = getChannel();
    if (ch) {
        ch.postMessage({ type, payload, at: Date.now() });
    }
    window.dispatchEvent(new CustomEvent('ftpos-offline', { detail: { type, payload } }));
}

export function onBroadcast(handler) {
    const ch = getChannel();
    if (ch) {
        ch.onmessage = (event) => handler(event.data);
    }
    window.addEventListener('ftpos-offline', (e) => handler(e.detail));
}
