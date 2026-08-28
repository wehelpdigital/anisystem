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

/* Network first, always. Only a navigation that cannot reach the server
   falls back, and it falls back to a page that says so rather than to a
   yesterday's copy of the board. */
self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET' || req.mode !== 'navigate') return;
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
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
