import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.js', 'resources/css/home-rest.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // The app's realtime (resources/js/bootstrap.js with axios, Laravel Echo and Pusher) in a chunk of its own, so the app
    // entry runs it BEFORE the core it shares with the public entry, the order app.js always had (2026-11-18); the public
    // entry loads only the core.
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('resources/js/bootstrap.js') || /node_modules\/(axios|laravel-echo|pusher-js)\//.test(id)) {
                        return 'realtime';
                    }
                    // What both entries share, in one chunk of its own: neither entry pulls the other in.
                    if (/resources\/js\/(core|phone-camera|site-events)\.js$/.test(id) || /node_modules\/alpinejs\//.test(id)) {
                        return 'core';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
