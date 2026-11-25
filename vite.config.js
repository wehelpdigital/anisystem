import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/* The scripts a page loads as plain ones (Vite::asset in an ordinary script tag): nothing may be imported into them or
   exported from them, and the board keeps the strict mode it was written in, which the bundler takes off. */
const PLAIN = { board: true, 'range-effect': false };
const plainScripts = {
    name: 'plain-scripts',
    generateBundle(options, bundle) {
        for (const c of Object.values(bundle)) {
            if (c.type !== 'chunk' || !(c.name in PLAIN)) continue;
            if (c.imports.length || c.dynamicImports.length || c.exports.length) this.error(c.name + '.js must stay a plain script: no import, no export');
            if (PLAIN[c.name]) c.code = '"use strict";' + c.code;
        }
    },
};

export default defineConfig({
    plugins: [
        plainScripts,
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.js', 'resources/css/home-rest.css', 'resources/css/wait-card.css', 'resources/css/plaza.css',
                // The season's board and the Coverage Planner, as files the phone keeps. Plain scripts: each is written out
                // on its own, with nothing imported (the pages load them with Vite::asset in an ordinary script tag).
                'resources/css/board.css', 'resources/css/range-effect.css', 'resources/js/board.js', 'resources/js/range-effect.js'],
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
                    if (/resources\/js\/(core|phone-camera|site-events|calm)\.js$/.test(id) || /node_modules\/alpinejs\//.test(id)) {
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
