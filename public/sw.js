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
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
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
                return res;
            } catch (_) {
                if (keep) {
                    const hit = await caches.match(req, { cacheName: RUNTIME });
                    if (hit) return hit;
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
