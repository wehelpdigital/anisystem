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
window.LIVEKIT_URL = import.meta.env.VITE_LIVEKIT_URL || '';

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
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
