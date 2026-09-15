import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/*
 * Laravel Echo + Pusher, for the Collab Room and nothing else.
 *
 * A socket is opened only on a page that declared itself the room — the
 * layout prints <meta name="realtime-scope" content="room"> for the room
 * page and for the Activities module while it is framed inside it — and
 * only when a Pusher key is configured. Everywhere else window.Echo stays
 * undefined and every feature takes its polling path, which each already
 * had: the Activities module on its own, the Maps module, the team chat
 * floating on the Workers page, the bell in every header. Their own
 * changes paint the moment they are made; other people's arrive on the
 * next poll. Sockets and messages are the whole budget on Pusher's plan,
 * and a socket held open by every tab of every farmer — for a bell that
 * polls anyway — was most of the bill. The server keeps the same rule from
 * its side: a room event goes out only while somebody else is in the room
 * (App\Events\Concerns\HeardOnlyInTheRoom).
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
// The room's say-so comes only from the page, never from a build-time
// value: a dev bundle with a key in it must not open sockets everywhere.
const inRoom = (document.querySelector('meta[name="realtime-scope"]')?.content || '').trim() === 'room';

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

if (pusherKey && inRoom) {
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
