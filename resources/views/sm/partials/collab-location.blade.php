{{-- The Location tab: where everyone actually is.

     On a farm the useful question is rarely "where is the north lot" — it is
     "who is standing near it". Each member who turns sharing on puts a dot on
     a shared map that moves as they do; everyone else sees it live.

     Positions travel over the room's data channel rather than the database:
     where somebody was ninety seconds ago is not a fact worth keeping, and a
     table of it would be a movement log nobody asked to be in. Turn sharing
     off and the dot goes with you.

     Expects: $schedule. --}}
<div class="loc-wrap" data-loc-root>
    <div class="loc-bar">
        <button type="button" id="locShare" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span id="locShareLabel">Share my location</span>
        </button>
        <button type="button" id="locRecenter" class="btn btn-white btn-sm" title="Fit everyone on screen">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 15v5h-5M15 4h5v5M9 20H4v-5"/></svg>
            Fit all
        </button>
        <span class="loc-count" id="locCount"></span>
    </div>

    <div class="loc-map" id="locMap"></div>

    <div class="loc-list" id="locList"></div>
</div>

@push('head')
<style>
    .loc-wrap { display: flex; flex-direction: column; height: 100%; min-height: 0; }
    .loc-bar { display: flex; align-items: center; gap: .4rem; padding: .5rem; flex-shrink: 0; flex-wrap: wrap; }
    .loc-count { margin-left: auto; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .loc-map { flex: 1 1 auto; min-height: 14rem; }
    .loc-list { flex-shrink: 0; max-height: 9rem; overflow-y: auto; padding: .35rem .5rem .5rem;
        display: flex; flex-direction: column; gap: .25rem; }
    .loc-row { display: flex; align-items: center; gap: .5rem; padding: .35rem .5rem; border-radius: .55rem;
        background: var(--color-gray-50); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .loc-row:hover { background: #eef6e6; }
    .loc-pin { width: 1.5rem; height: 1.5rem; border-radius: 999px; flex: none; color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .6rem; font-weight: 800; }
    .loc-name { font-size: .8rem; font-weight: 700; color: var(--color-gray-800);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .loc-when { margin-left: auto; flex: none; font-size: .66rem; font-weight: 600; color: var(--color-gray-400); }
    .loc-empty { padding: 1rem; text-align: center; font-size: .8rem; color: var(--color-gray-400); }
    html.dark .loc-row { background: rgb(255 255 255 / .05); }
    html.dark .loc-row:hover { background: rgb(61 104 35 / .25); }
    html.dark .loc-name { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .loc-row { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(function collabLocation() {
    const root = document.querySelector('[data-loc-root]');
    if (!root || window.__locBooted) return;
    window.__locBooted = true;

    const $ = (id) => document.getElementById(id);
    const esc = window.escapeHtml || ((v) => String(v ?? ''));
    const ME = @json((int) auth()->id());
    const MY_NAME = @json(auth()->user()?->firstName ?: 'You');
    const MAPS_KEY = @json(config('services.google_maps.key'));

    // Who is where. Keyed by user id; a position replaces the last one,
    // because only the current one is worth anything.
    const people = new Map();
    let map = null, markers = new Map(), watchId = null, sharing = false, ticker = null;

    const HUE = ['#4a7c2a', '#2563eb', '#b45309', '#9333ea', '#0891b2', '#dc2626', '#0f766e', '#c2410c'];
    const hueFor = (id) => HUE[Math.abs(Number(id) || 0) % HUE.length];
    const initials = (n) => String(n || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0] || '').join('').toUpperCase() || '?';

    /* The same Google Maps the Maps module uses — one mapping library in the
       app, one API key, one thing to be wrong. Loaded on first use so a room
       that never opens this tab spends no quota. */
    let mapLoading = null;
    function loadMaps() {
        if (window.google?.maps) return Promise.resolve();
        if (!MAPS_KEY) return Promise.reject(new Error('no key'));
        return mapLoading ??= new Promise((resolve, reject) => {
            window.__locMapBoot = resolve;
            const sc = document.createElement('script');
            sc.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(MAPS_KEY)
                + '&v=weekly&loading=async&callback=__locMapBoot';
            sc.async = true;
            sc.onerror = () => reject(new Error('load failed'));
            document.head.appendChild(sc);
        });
    }

    async function ensureMap() {
        if (map) return map;
        try { await loadMaps(); } catch (_) {
            $('locMap').innerHTML = '<p class="loc-empty">The map could not load — the list below still shows who is sharing.</p>';
            return null;
        }
        map = new google.maps.Map($('locMap'), {
            center: { lat: 12.8797, lng: 121.7740 },   // the country, until somebody says otherwise
            zoom: 6,
            mapTypeId: 'hybrid',
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: false,
        });
        return map;
    }

    /* A dot with initials in it, drawn as an SVG data URL: Google's markers
       take an image, and a face beats a pin when the question is who. */
    function markerIcon(name, id, stale) {
        const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34">'
            + '<circle cx="17" cy="17" r="14" fill="' + hueFor(id) + '" stroke="#fff" stroke-width="3"'
            + ' opacity="' + (stale ? '.45' : '1') + '"/>'
            + '<text x="17" y="21.5" text-anchor="middle" font-family="sans-serif" font-size="11"'
            + ' font-weight="700" fill="#fff">' + esc(initials(name)) + '</text></svg>';
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(34, 34),
            anchor: new google.maps.Point(17, 17),
        };
    }

    /* A position older than a minute is not where anyone is now — it is
       where they were when their phone last managed to say. Shown, because
       "last seen at the gate" is still useful, but dimmed so nobody reads it
       as live. */
    const isStale = (p) => (Date.now() - p.at) > 60000;

    async function paint() {
        const rows = [...people.values()].sort((a, b) => b.at - a.at);
        $('locCount').textContent = rows.length
            ? rows.length + ' ' + (rows.length === 1 ? 'person' : 'people') + ' sharing'
            : '';

        const list = $('locList');
        list.innerHTML = rows.length
            ? rows.map((p) => '<div class="loc-row" data-loc-user="' + p.id + '">'
                + '<span class="loc-pin" style="background:' + hueFor(p.id) + '">' + esc(initials(p.name)) + '</span>'
                + '<span class="loc-name">' + esc(p.name) + (Number(p.id) === ME ? ' (you)' : '') + '</span>'
                + '<span class="loc-when">' + (isStale(p) ? 'last seen ' + ago(p.at) : 'live') + '</span>'
                + '</div>').join('')
            : '<p class="loc-empty">Nobody is sharing a location. Tap <b>Share my location</b> and your dot appears here for the team.</p>';

        const m = await ensureMap();
        if (!m) return;

        // Drop the dots of people who stopped sharing.
        markers.forEach((mk, id) => { if (!people.has(id)) { mk.setMap(null); markers.delete(id); } });

        for (const p of rows) {
            const at = { lat: p.lat, lng: p.lng };
            let mk = markers.get(p.id);
            if (mk) { mk.setPosition(at); mk.setIcon(markerIcon(p.name, p.id, isStale(p))); }
            else {
                mk = new google.maps.Marker({
                    position: at, map: m, title: p.name,
                    icon: markerIcon(p.name, p.id, isStale(p)),
                });
                markers.set(p.id, mk);
            }
        }
    }

    function ago(ts) {
        const s = Math.round((Date.now() - ts) / 1000);
        if (s < 90) return s + 's ago';
        const mn = Math.round(s / 60);
        return mn < 60 ? mn + 'm ago' : Math.round(mn / 60) + 'h ago';
    }

    function fitAll() {
        if (!map || !markers.size) return;
        const bounds = new google.maps.LatLngBounds();
        markers.forEach((mk) => bounds.extend(mk.getPosition()));
        map.fitBounds(bounds, 48);
        // One dot has no extent, so fitBounds zooms to the maximum; pull back
        // to something a person can recognise ground in.
        if (markers.size === 1) {
            google.maps.event.addListenerOnce(map, 'idle', () => { if (map.getZoom() > 18) map.setZoom(17); });
        }
    }
    $('locRecenter').addEventListener('click', fitAll);

    $('locList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-loc-user]');
        if (!row || !map) return;
        const p = people.get(Number(row.dataset.locUser));
        if (!p) return;
        map.panTo({ lat: p.lat, lng: p.lng });
        if (map.getZoom() < 16) map.setZoom(16);
    });

    /* ---- Telling the room ----------------------------------------------
     * The room's data channel, not the database. Where somebody was ninety
     * seconds ago is not a fact worth keeping, and a table of it would be a
     * movement log nobody agreed to be in. Sharing stops, the dot goes. */
    function send(payload) {
        const room = window.smCall?.room();
        if (!room) return;
        try {
            room.localParticipant.publishData(
                new TextEncoder().encode(JSON.stringify(payload)),
                { reliable: false, topic: 'loc' }
            );
        } catch (_) { /* a dropped position is replaced a second later */ }
    }

    async function startSharing() {
        if (!navigator.geolocation) { window.toast?.('This device cannot report a location.', 'error'); return; }
        if (!window.isSecureContext && !/^(localhost|127\.)/.test(location.hostname)) {
            window.toast?.('Sharing a location needs HTTPS — open the app over https://.', 'error');
            return;
        }
        window.__smCameraJoin = true;                 // the widget stays out of the way
        const ok = await (window.smCall?.ensure('Team room') ?? Promise.resolve(false));
        window.__smCameraJoin = false;
        if (!ok) return;

        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                const mine = {
                    id: ME, name: MY_NAME,
                    lat: pos.coords.latitude, lng: pos.coords.longitude,
                    at: Date.now(),
                };
                people.set(ME, mine);
                send({ t: 'loc', ...mine });
                paint();
            },
            () => { window.toast?.('Location permission was denied.', 'error'); stopSharing(); },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );
        sharing = true;
        paintShare();
        window.toast?.('Sharing your location with the team.');
        setTimeout(fitAll, 1200);
    }

    function stopSharing() {
        if (watchId != null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
        sharing = false;
        people.delete(ME);
        send({ t: 'loc-off', id: ME });
        paintShare();
        paint();
    }

    function paintShare() {
        $('locShareLabel').textContent = sharing ? 'Stop sharing' : 'Share my location';
        $('locShare').classList.toggle('btn-primary', !sharing);
        $('locShare').classList.toggle('btn-white', sharing);
    }

    $('locShare').addEventListener('click', () => (sharing ? stopSharing() : startSharing()));

    /* ---- Hearing the room ---------------------------------------------- */
    function listen(room) {
        if (!room || room.__locWired) return;
        room.__locWired = true;
        const LK = window.LivekitClient;
        room.on(LK ? LK.RoomEvent.DataReceived : 'dataReceived', (payload, participant, kind, topic) => {
            if (topic && topic !== 'loc') return;
            let msg = null;
            try { msg = JSON.parse(new TextDecoder().decode(payload)); } catch (_) { return; }
            if (!msg || (msg.t !== 'loc' && msg.t !== 'loc-off')) return;
            const id = Number(msg.id);
            if (!id || id === ME) return;
            if (msg.t === 'loc-off') people.delete(id);
            else people.set(id, { id, name: msg.name || 'Member', lat: msg.lat, lng: msg.lng, at: Date.now() });
            paint();
        });
        // Someone who leaves the room takes their dot with them.
        room.on(LK ? LK.RoomEvent.ParticipantDisconnected : 'participantDisconnected', () => {
            // Identity is the user id; drop anyone no longer connected.
            const live = new Set();
            room.remoteParticipants.forEach((p) => live.add(Number(p.identity)));
            [...people.keys()].forEach((id) => { if (id !== ME && !live.has(id)) people.delete(id); });
            paint();
        });
    }

    window.smCall?.watch((evt) => {
        const room = window.smCall.room();
        if (room) listen(room);
        if (evt === 'gone') { people.clear(); if (sharing) stopSharing(); paint(); }
    });

    // "live" becomes "last seen" without anything arriving, so the labels
    // have to age on their own.
    ticker = setInterval(() => { if (people.size) paint(); }, 20000);

    document.addEventListener('collab:show', (e) => {
        if (e.detail?.tab !== 'location') return;
        // A map built inside a hidden panel measured a box of nothing; it has
        // to be told to look again once the panel is actually on screen.
        ensureMap().then(() => {
            if (!map) return;
            google.maps.event.trigger(map, 'resize');
            paint();
            fitAll();
        });
    });
    paint();
})();
</script>
@endpush
