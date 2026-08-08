import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/*
 * Laravel Echo + Pusher for realtime features (the collaborative whiteboard).
 * Only initialised when a Pusher key is configured — otherwise features fall
 * back to polling, so the app runs fine with no realtime service set up.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// LiveKit (Collab Room calls) is heavy, so it's loaded on demand — the chunk
// only downloads when someone actually starts/joins a call.
window.loadLivekit = () => import('livekit-client');

/*
 * Realtime settings come from the server (meta tags rendered per request) and
 * fall back to the build-time VITE_* values for local `npm run dev`.
 *
 * A value left as an unexpanded "${PUSHER_APP_KEY}" is discarded. Laravel's
 * .env expands that syntax but a hosting dashboard does not, and the literal
 * used to reach Echo as its key: the socket could never authenticate, yet
 * window.Echo existed, so every feature believed realtime was working and
 * slowed its polling fallback accordingly. Treating it as absent means the
 * app degrades honestly to fast polling instead.
 */
const PLACEHOLDER = /^\$\{[A-Za-z_][A-Za-z0-9_]*\}$/;

function setting(metaName, buildValue) {
    const fromMeta = document.querySelector(`meta[name="${metaName}"]`)?.content;
    const value = (fromMeta || buildValue || '').trim();

    return value && !PLACEHOLDER.test(value) ? value : '';
}

window.LIVEKIT_URL = setting('livekit-url', import.meta.env.VITE_LIVEKIT_URL);

const pusherKey = setting('pusher-key', import.meta.env.VITE_PUSHER_APP_KEY);
const pusherCluster = setting('pusher-cluster', import.meta.env.VITE_PUSHER_APP_CLUSTER) || 'mt1';

/*
 * Whether realtime is actually carrying messages right now — not merely
 * whether Echo was constructed. Callers use this to choose a polling cadence,
 * so a Pusher outage or a bad key speeds polling up rather than slowing it.
 */
window.realtimeReady = function realtimeReady() {
    try {
        return window.Echo?.connector?.pusher?.connection?.state === 'connected';
    } catch (_) {
        return false;
    }
};

if (pusherKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        // Channel auth uses the session cookie; send the CSRF token with it.
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });
}
