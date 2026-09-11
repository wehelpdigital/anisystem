/* anee.io service worker.
 *
 * Deliberately thin. This app is a live record of a farm — a stale page
 * served from a cache is worse than a page that says it cannot reach the
 * server, so nothing is cached except the one offline notice. What the
 * worker is really here for is the two things a plain page cannot do:
 * install to the home screen, and raise a notification while the app is in
 * the background.
 */
const OFFLINE_URL = '/offline.html';
// Bumped with the rebrand: the old cache holds the old icon, and a
// service worker that keeps its key goes on serving it.
const CACHE = 'anee-shell-v2';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((c) => c.addAll([OFFLINE_URL, '/images/pwa/icon-192.png'])).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    /* Sweep out OLD SHELLS — and only those.
     *
     * This used to delete every cache whose key was not the current shell,
     * which took Offline Mode's two with it: the marker saying the farmer
     * asked for it, and every page it had kept. So each deploy that touched
     * this file quietly emptied the shelf, and a phone that had been made
     * ready for the field opened to nothing the next time it was out of
     * signal — with the switch still showing On, because the page's own
     * memory of the setting was never the thing that got wiped.
     *
     * The runtime copies and the flag are the farmer's, not the release's. */
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((k) => k.startsWith('anee-shell-') && k !== CACHE)
                .map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

/* ---- Offline Mode (opt-in, per device) --------------------------------
   The page flips it with a message; the flag survives SW restarts as a
   marker entry in its own cache. Off (the default) keeps the original
   contract: network first, nothing cached but the offline notice. On, the
   worker keeps a copy of every successful same-origin GET this browser
   makes, and serves the copy only when the network fails — so nothing is
   ever stale while online, and everything already visited still opens in
   the field. */
const RUNTIME = 'anee-offline-runtime-v1';
const FLAGS = 'anee-flags';
const FLAG_URL = '/__offline-mode-on';

self.addEventListener('message', (event) => {
    const d = event.data || {};
    if (d.type !== 'anee-offline') return;
    event.waitUntil(caches.open(FLAGS).then((c) => (d.on
        ? c.put(FLAG_URL, new Response('1'))
        // Turning it off also empties the copies — the person asked the
        // device to stop keeping the farm on disk.
        : Promise.all([c.delete(FLAG_URL), caches.delete(RUNTIME)]))));
});

const offlineOn = () => caches.open(FLAGS)
    .then((c) => c.match(FLAG_URL))
    .then((r) => !!r)
    .catch(() => false);

/* "That page came off the shelf."
 *
 * navigator.onLine is the only thing the page had to go on, and it is a poor
 * witness: it answers true for a phone attached to a router with no internet
 * behind it, and a freshly created document does not always inherit what the
 * one before it knew. The worker has the only certain answer, because it is
 * the thing whose fetch just failed — so it says so, and the yellow bar stops
 * depending on a guess. */
const SHELF_MARK = '/__served-offline';

/* A note left in the flags cache rather than a message posted to the page.
 *
 * postMessage cannot carry this across a navigation: at the moment the
 * worker answers with a cached page, the document that will display it does
 * not exist yet, so the message lands on the one being torn down. The note
 * survives the gap, and the new page reads it as it boots.
 *
 * Cleared by the first navigation that actually reaches the server. */
const sayServedFromShelf = () => Promise.all([
    caches.open(FLAGS).then((c) => c.put(SHELF_MARK, new Response(String(Date.now())))),
    self.clients.matchAll({ type: 'window' })
        .then((list) => list.forEach((c) => c.postMessage({ type: 'anee-served-offline' }))),
]).catch(() => {});

const sayReachedTheServer = () => caches.open(FLAGS)
    .then((c) => c.delete(SHELF_MARK))
    .catch(() => {});

/* Network first, always — being online never shows yesterday's board.
   A navigation that cannot reach the server falls back to the kept copy
   (Offline Mode) or to the page that says so. */
self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const sameOrigin = new URL(req.url).origin === self.location.origin;

    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            const keep = await offlineOn();
            try {
                const res = await fetch(req);
                if (keep && res.ok) {
                    const c = await caches.open(RUNTIME);
                    c.put(req, res.clone());
                }
                // The line is up: whatever the last page was served from, it
                // is not what this one came from.
                sayReachedTheServer();
                return res;
            } catch (_) {
                if (keep) {
                    /* ignoreVary, because the copy on the shelf was put
                       there by a warm fetch and this is a navigation: the
                       two ask with different Accept headers, and the day a
                       response carries Vary the exact match would miss a
                       page that is sitting right there. */
                    /* Awaited, not fired and forgotten: once respondWith has
                       its answer the browser is free to kill this worker,
                       and a note still being written dies with it. */
                    const hit = await caches.match(req, { cacheName: RUNTIME, ignoreVary: true });
                    if (hit) { await sayServedFromShelf(); return hit; }
                    /* A module reached with a query the shelf has not seen
                       exactly — a ?fresh= buster, ?tab=saved, a filter — is
                       still that module, and the offline card helps nobody.
                       So the same path is accepted as a near miss.

                       But ONLY when it is about the same thing: `id` names
                       which season a page belongs to, and handing back
                       season 46's Lots to somebody who asked for season 99
                       would be a wrong answer dressed as a right one, which
                       is worse than saying there is no signal. Same path,
                       same id, or nothing. */
                    const url = new URL(req.url);
                    const want = url.searchParams.get('id');
                    const cache = await caches.open(RUNTIME);
                    for (const k of await cache.keys()) {
                        const got = new URL(k.url);
                        if (got.pathname !== url.pathname) continue;
                        if ((got.searchParams.get('id') || '') !== (want || '')) continue;
                        const near = await cache.match(k, { ignoreVary: true });
                        if (near) { await sayServedFromShelf(); return near; }
                    }
                }
                return caches.match(OFFLINE_URL);
            }
        })());
        return;
    }

    if (!sameOrigin) return;
    event.respondWith((async () => {
        const keep = await offlineOn();
        if (!keep) return fetch(req);
        const cache = await caches.open(RUNTIME);
        try {
            const res = await fetch(req);
            if (res.ok) cache.put(req, res.clone());
            return res;
        } catch (_) {
            return (await cache.match(req)) || Response.error();
        }
    })());
});

/* A notification raised from the page (see app.js) or pushed by a server
   that has been given VAPID keys. Both land here. */
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (_) { data = { title: event.data && event.data.text() }; }
    const title = data.title || 'anee.io';
    event.waitUntil(self.registration.showNotification(title, {
        body: data.body || '',
        icon: '/images/pwa/icon-192.png',
        badge: '/images/pwa/icon-192.png',
        tag: data.tag || 'anisystem',
        renotify: true,
        data: { url: data.url || '/app' },
        vibrate: [90, 40, 90],
    }));
});

/* Tapping a notification should land on the thing it is about, in the tab
   that is already open where there is one. */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/app';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if ('focus' in client) {
                    client.navigate?.(url);
                    return client.focus();
                }
            }
            return self.clients.openWindow(url);
        })
    );
});
