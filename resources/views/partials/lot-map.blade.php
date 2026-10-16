{{--
    A lot on the map (2026-10-16, the owner): its pin, and its boundary drawn
    point by point, each in a full screen map.

    window.aneeLotMap.pin(lot)  -> Promise<the saved lot row, or null>
        The map moves under a pin fixed at its middle; search a place, or use
        "my location", then "Save the pin". The boundary shows, for reference.
    window.aneeLotMap.draw(lot) -> Promise<the saved lot row, or null>
        Tap the map for each corner. Drag a point to move it; tap it to pick it,
        then Delete point. The small dots between two points add a point there,
        by a tap or a drag. Undo, Clear, then "Save the boundary" (3 points or
        more; none at all takes a saved boundary off).

    The map is a vector one (a Map ID), so it turns with two fingers like the
    Google Maps app and pinches to zoom; one finger pans (gestureHandling
    greedy). The compass puts north back up. The maps key allows the map only:
    place search goes through our server (FarmLotController::find, PlaceSearch).
--}}
@once
@php
    $lmData = [
        'key' => (string) config('services.google_maps.key'),
        'mapId' => (string) config('services.google_maps.map_id'),
        'urls' => ['lots' => url('/app/farm-lots'), 'find' => route('farmlots.find')],
    ];
@endphp
@push('head')
<style>
    dialog.lm { position: fixed; inset: 0; width: 100%; height: 100%; max-width: none; max-height: none; margin: 0; padding: 0; border: 0; overflow: hidden; background: #0b1208; color: var(--color-gray-900); }
    dialog.lm::backdrop { background: rgb(5 10 3 / .6); }
    dialog.lm[open] { animation: lmIn .3s cubic-bezier(.22,1,.36,1) both; }
    dialog.lm.is-closing { animation: lmOut .22s cubic-bezier(.22,1,.36,1) both; }
    @keyframes lmIn { from { opacity: 0; transform: scale(.985); } }
    @keyframes lmOut { to { opacity: 0; transform: scale(.985); } }
    .lm-map { position: absolute; inset: 0; }
    .lm-top { position: absolute; left: 0; right: 0; top: 0; z-index: 2; display: grid; gap: .5rem; padding: calc(env(safe-area-inset-top) + .6rem) .75rem 0; pointer-events: none; max-width: 40rem; margin: 0 auto; }
    .lm-top > * { pointer-events: auto; }
    .lm-head { display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .4rem .55rem .4rem .4rem; border-radius: 1.1rem; background: var(--color-white); box-shadow: 0 10px 28px -12px rgb(0 0 0 / .55); }
    .lm-head > div { flex: 1; min-width: 0; }
    .lm-head b { display: block; font-size: .98rem; font-weight: 800; line-height: 1.25; color: var(--color-gray-900); }
    .lm-head small { display: block; font-size: .76rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lm-x { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-700); background: var(--color-gray-100); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lm-x:hover { background: var(--color-gray-200); }
    .lm-x svg { width: 1.15rem; height: 1.15rem; }
    .lm-find { display: flex; align-items: center; gap: .4rem; min-width: 0; padding: .3rem .3rem .3rem .8rem; border-radius: 1.1rem; background: var(--color-white); box-shadow: 0 10px 28px -12px rgb(0 0 0 / .55); }
    .lm-find svg { flex: none; width: 1.05rem; height: 1.05rem; color: var(--color-brand-600); }
    .lm-find input { flex: 1; min-width: 0; min-height: 2.6rem; border: 0; outline: none; box-shadow: none; background: transparent; font-size: 1rem; color: var(--color-gray-900); }
    .lm-find button { flex: none; min-height: 2.5rem; padding: 0 1rem; border-radius: .85rem; font-size: .86rem; font-weight: 800; color: #fff; background: var(--color-brand-600); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lm-find button:hover { background: var(--color-brand-700); }
    .lm-find button:disabled { opacity: .6; }
    .lm-res { max-height: 42vh; overflow-y: auto; border-radius: 1.1rem; background: var(--color-white); box-shadow: 0 10px 28px -12px rgb(0 0 0 / .55); animation: lmDrop .26s cubic-bezier(.22,1,.36,1) both; }
    .lm-res[hidden] { display: none; }
    @keyframes lmDrop { from { opacity: 0; transform: translateY(-6px); } }
    .lm-res button { display: block; width: 100%; padding: .65rem .9rem; text-align: left; border-top: 1px solid var(--color-gray-100); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lm-res button:first-child { border-top: 0; }
    .lm-res button:hover { background: var(--color-brand-50); }
    .lm-res b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .lm-res small { display: block; font-size: .76rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lm-res p { padding: .8rem .9rem; font-size: .86rem; color: var(--color-gray-500); }
    .lm-side { position: absolute; left: .75rem; top: 50%; z-index: 2; display: grid; gap: .5rem; translate: 0 -50%; }
    .lm-side button { width: 2.9rem; height: 2.9rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-800); background: var(--color-white); box-shadow: 0 8px 22px -10px rgb(0 0 0 / .55);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .lm-side button:hover { background: var(--color-brand-50); }
    .lm-side button:active { transform: scale(.94); }
    .lm-side button[hidden] { display: none; }
    .lm-side svg { width: 1.25rem; height: 1.25rem; }
    .lm-north svg { transition: transform .2s linear; }
    .lm-north i { position: absolute; font-style: normal; font-size: .58rem; font-weight: 900; color: #dc2626; translate: 0 -.9rem; }
    .lm-north { position: relative; }
    .lm-bar { position: absolute; left: .75rem; right: .75rem; bottom: calc(env(safe-area-inset-bottom) + .75rem); z-index: 2; display: grid; gap: .6rem; max-width: 38rem; margin: 0 auto; padding: .75rem .8rem;
        border-radius: 1.2rem; background: var(--color-white); box-shadow: 0 14px 34px -14px rgb(0 0 0 / .6); }
    .lm-say { font-size: .84rem; line-height: 1.45; color: var(--color-gray-600); }
    .lm-say b { display: block; font-size: 1rem; font-weight: 800; color: var(--color-gray-900); }
    .lm-say small { display: block; margin-top: .1rem; font-size: .74rem; color: var(--color-gray-500); }
    .lm-btns { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; }
    .lm-btns .btn { min-height: 2.75rem; padding-left: .95rem; padding-right: .95rem; font-size: .86rem; }
    .lm-btns .lm-main { margin-left: auto; }
    .lm-btns .lm-del { color: #b91c1c; }
    .lm-btns .btn[hidden] { display: none; }
    /* The pin, fixed at the middle: the map moves under it. Its tip is the spot. */
    .lm-cross { position: absolute; left: 50%; top: 50%; z-index: 1; width: 2.6rem; height: 3.5rem; translate: -50% -100%; pointer-events: none; filter: drop-shadow(0 6px 6px rgb(0 0 0 / .45)); transition: translate .2s cubic-bezier(.22,1,.36,1); }
    .lm-cross[hidden] { display: none; }
    .lm-cross.is-lift { translate: -50% -112%; }
    .lm-dot { position: absolute; left: 50%; top: 50%; z-index: 1; width: .55rem; height: .55rem; margin: -.275rem 0 0 -.275rem; border-radius: 999px; background: rgb(0 0 0 / .45); pointer-events: none; }
    .lm-dot[hidden] { display: none; }
    .lm-refused { position: absolute; left: 1rem; right: 1rem; top: 50%; z-index: 3; max-width: 26rem; margin: 0 auto; translate: 0 -50%; padding: 1rem 1.1rem; border-radius: 1.1rem; font-size: .88rem; line-height: 1.5;
        color: var(--color-gray-700); background: var(--color-white); box-shadow: 0 14px 34px -14px rgb(0 0 0 / .6); }
    .lm-refused[hidden] { display: none; }
    .lm-refused b { display: block; margin-bottom: .2rem; color: var(--color-gray-900); }
    html.lm-lock { overflow: hidden; }
    @media (prefers-reduced-motion: reduce) { dialog.lm[open], dialog.lm.is-closing, .lm-res { animation: none; } .lm-side button, .lm-cross, .lm-north svg { transition: none; } }
    html.sm-still dialog.lm[open], html.sm-still .lm-res { animation: none; }
</style>
@endpush

@push('sheets')
<dialog class="lm" id="lmView" aria-labelledby="lmTitle">
    <div class="lm-map" id="lmMap"></div>
    <svg class="lm-cross" id="lmCross" viewBox="0 0 40 54" aria-hidden="true" hidden><path d="M20 53C20 53 37 31 37 19A17 17 0 0 0 3 19C3 31 20 53 20 53Z" fill="#dc2626" stroke="#fff" stroke-width="2.5"/><circle cx="20" cy="19" r="6.5" fill="#fff"/></svg>
    <span class="lm-dot" id="lmDot" hidden></span>
    <div class="lm-top">
        <div class="lm-head">
            <button type="button" class="lm-x" id="lmClose" aria-label="Close the map"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            <div><b id="lmTitle">Your lot</b><small id="lmSub"></small></div>
        </div>
        <form class="lm-find" id="lmFind" role="search">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <label class="sr-only" for="lmQ">Find a place</label>
            <input type="search" id="lmQ" placeholder="Find a barangay, a town or a landmark" autocomplete="off" maxlength="120" enterkeyhint="search">
            <button type="submit" id="lmGo">Find</button>
        </form>
        <div class="lm-res" id="lmRes" hidden></div>
    </div>
    <div class="lm-side">
        <button type="button" id="lmLocate" aria-label="Go to where I am"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg></button>
        <button type="button" id="lmNorth" class="lm-north" aria-label="Turn north back up" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l4 9h-8z" fill="#dc2626"/><path d="M12 21l-4-9h8z" fill="currentColor" opacity=".55"/></svg></button>
        <button type="button" id="lmFit" aria-label="Show the whole lot"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg></button>
    </div>
    <div class="lm-bar" id="lmBar">
        <div class="lm-say" id="lmSay" aria-live="polite"></div>
        <div class="lm-btns" id="lmBtns"></div>
    </div>
    <div class="lm-refused" id="lmRefused" hidden><b>The map cannot be shown right now.</b><span id="lmRefusedSay">Google Maps did not load. Check the connection, then close this and open it again.</span></div>
</dialog>
@endpush

@push('scripts')
<script>
(() => {
    if (window.aneeLotMap) return;
    const D = @json($lmData);
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => document.documentElement.classList.contains('sm-still') || matchMedia('(prefers-reduced-motion: reduce)').matches;
    const G = () => window.google.maps;
    const nf = (v, d) => Number(v).toLocaleString(undefined, { maximumFractionDigits: d });

    /* ---- Google Maps, loaded once; another surface already loading it is waited for, never doubled ---- */
    let mapsP = null;
    const mapsOk = () => !!(window.google && window.google.maps && window.google.maps.Map && window.google.maps.geometry);
    const loadMaps = () => mapsP ??= new Promise((resolve, reject) => {
        if (mapsOk()) return resolve();
        const wait = () => { let n = 0; const t = setInterval(() => { if (mapsOk()) { clearInterval(t); resolve(); } else if (++n > 200) { clearInterval(t); mapsP = null; reject(new Error('Google Maps did not load.')); } }, 100); };
        if (document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]')) return wait();
        const cb = '__lmMaps' + Date.now();
        window[cb] = () => resolve();
        const s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(D.key) + '&libraries=geometry&v=weekly&loading=async&callback=' + cb;
        s.async = true;
        s.onerror = () => { mapsP = null; reject(new Error('Google Maps did not load. Check the connection, then try again.')); };
        document.head.appendChild(s);
    });
    // A refused key: say so on the map instead of Google's grey box.
    const prevAuth = window.gm_authFailure;
    window.gm_authFailure = function () { try { if (typeof prevAuth === 'function') prevAuth(); } catch (_) {} refused('Google Maps refused this site. Please tell us, and try again later.'); };
    function refused(msg) { $('lmRefusedSay').textContent = msg; $('lmRefused').hidden = false; }

    /* ---- the map, made once and kept ---- */
    let map = null;
    function ensureMap() {
        if (map) return map;
        const fine = matchMedia('(pointer: fine)').matches;
        map = new (G().Map)($('lmMap'), {
            center: { lat: 12.88, lng: 121.77 }, zoom: 6, mapTypeId: 'hybrid', tilt: 0,
            // A vector map (a Map ID) turns with two fingers like the Google Maps app.
            mapId: D.mapId || 'DEMO_MAP_ID', renderingType: 'VECTOR', headingInteractionEnabled: true, tiltInteractionEnabled: false,
            gestureHandling: 'greedy', clickableIcons: false, keyboardShortcuts: true,
            streetViewControl: false, fullscreenControl: false, mapTypeControl: false, cameraControl: false,
            zoomControl: fine, zoomControlOptions: { position: G().ControlPosition.RIGHT_CENTER },
            rotateControl: true, rotateControlOptions: { position: G().ControlPosition.RIGHT_CENTER },
        });
        map.addListener('click', (e) => onTap(e.latLng));
        map.addListener('heading_changed', paintNorth);
        // Vector or raster, as Google settled it (a raster map cannot turn): kept on the dialog for a look.
        map.addListener('renderingtype_changed', () => { $('lmView').dataset.render = String(map.getRenderingType() || ''); });
        map.addListener('dragstart', () => { if (S && S.mode === 'pin') $('lmCross').classList.add('is-lift'); });
        map.addListener('idle', () => { $('lmCross').classList.remove('is-lift'); if (S && S.mode === 'pin') say(); });
        return map;
    }
    function paintNorth() {
        const h = map ? (map.getHeading() || 0) : 0;
        const b = $('lmNorth');
        b.hidden = Math.abs(((h % 360) + 360) % 360) < 1;
        b.querySelector('svg').style.transform = 'rotate(' + (-h) + 'deg)';
    }

    /* ---- one opening: the pin, or the boundary ---- */
    let S = null;   // { mode, lot, resolve, row, pts, hist, sel, marks, mids, poly, ctx, busy }
    const LL = (p) => ({ lat: Number(p[0]), lng: Number(p[1]) });
    const AR = (ll) => [Math.round(ll.lat() * 1e7) / 1e7, Math.round(ll.lng() * 1e7) / 1e7];
    const MID = (a, b) => [(Number(a[0]) + Number(b[0])) / 2, (Number(a[1]) + Number(b[1])) / 2];
    const lockOn = (on) => document.documentElement.classList.toggle('lm-lock', on);

    function open(mode, lot) {
        return new Promise((resolve) => {
            if (S && S.resolve) S.resolve(null);
            const dlg = $('lmView');
            S = { mode, lot, resolve, row: null, pts: mode === 'draw' ? JSON.parse(JSON.stringify(lot.boundary || [])) : [], hist: [], sel: -1, marks: [], mids: [], poly: null, ctx: [], busy: false };
            $('lmTitle').textContent = mode === 'pin' ? (lot.pin ? 'Move the pin' : 'Set the pin') : (lot.boundary ? 'Change the boundary' : 'Draw the boundary');
            $('lmSub').textContent = [lot.name, lot.place].filter(Boolean).join(' · ');
            $('lmQ').value = ''; $('lmRes').hidden = true; $('lmRefused').hidden = true;
            $('lmCross').hidden = mode !== 'pin'; $('lmDot').hidden = mode !== 'pin';
            say(); paintBar();
            if (!dlg.open) dlg.showModal();
            dlg.classList.remove('is-closing');
            lockOn(true);
            if (window.registerOverlay) window.registerOverlay('lotmap', () => close());
            go();
        });
    }
    async function go() {
        const s = S;
        try { await loadMaps(); } catch (err) { refused(err.message); return; }
        if (S !== s) return;
        ensureMap();
        map.setHeading(0);
        clearShapes();
        drawContext();
        if (s.mode === 'draw') paintDraw();
        await frame();
        paintNorth();
    }
    function close() {
        const dlg = $('lmView');
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('lotmap');
        const s = S;
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); lockOn(false); clearShapes(); if (s && s.resolve) { const r = s.resolve; s.resolve = null; r(s.row); } };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 200);
    }
    $('lmView').addEventListener('cancel', (e) => { e.preventDefault(); close(); });
    $('lmClose').addEventListener('click', close);

    /* Where to look first: the boundary, the pin, the lot's barangay and town, else the country. */
    async function frame() {
        const lot = S.lot;
        const ring = S.mode === 'draw' && S.pts.length ? S.pts : (lot.boundary || null);
        if (ring && ring.length) { fit(ring); return; }
        if (lot.pin) { map.setCenter(lot.pin); map.setZoom(18); return; }
        const q = [lot.barangay, lot.town, lot.province].filter(Boolean).join(', ');
        if (q) {
            try {
                const r = await window.api(D.urls.find + '?q=' + encodeURIComponent(q));
                const p = ((r.data && r.data.places) || [])[0];
                if (p) { goTo(p, 16); return; }
            } catch (_) { /* the country view below */ }
        }
        map.setCenter({ lat: 12.88, lng: 121.77 }); map.setZoom(6);
    }
    function fit(ring) {
        const b = new (G().LatLngBounds)();
        ring.forEach((p) => b.extend(LL(p)));
        if (ring.length === 1) { map.setCenter(LL(ring[0])); map.setZoom(18); return; }
        map.fitBounds(b, { top: 150, bottom: 170, left: 60, right: 60 });
    }
    function goTo(p, zoom) {
        if (p.bbox && p.bbox.length === 4) {
            const [s, n, w, e] = p.bbox.map(Number);
            if (n - s < 0.3 && e - w < 0.3) { map.fitBounds({ south: s, north: n, west: w, east: e }, 40); return; }
        }
        map.setCenter({ lat: Number(p.lat), lng: Number(p.lng) });
        map.setZoom(zoom || 17);
    }

    /* What the other mode left: the boundary behind the pin, the pin inside the boundary. */
    function drawContext() {
        const lot = S.lot;
        if (S.mode === 'pin' && lot.boundary && lot.boundary.length >= 3) {
            S.ctx.push(new (G().Polygon)({ map, paths: lot.boundary.map(LL), strokeColor: '#f5c518', strokeWeight: 2, strokeOpacity: .9, fillColor: '#f5c518', fillOpacity: .1, clickable: false }));
        }
        if (S.mode === 'draw' && lot.pin) {
            S.ctx.push(new (G().Marker)({ map, position: lot.pin, clickable: false, zIndex: 5,
                icon: { path: 'M0 0C0 0 9-12 9-18A9 9 0 0 0-9-18C-9-12 0 0 0 0Z', fillColor: '#dc2626', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 1.5, scale: 1.1 } }));
        }
    }
    function clearShapes() {
        if (!S) return;
        [...S.marks, ...S.mids, ...S.ctx].forEach((m) => m.setMap(null));
        if (S.poly) S.poly.setMap(null);
        S.marks = []; S.mids = []; S.ctx = []; S.poly = null;
    }

    /* ---- the boundary, point by point ---- */
    const dotIcon = (kind) => ({ path: G().SymbolPath.CIRCLE, scale: kind === 'sel' ? 11 : (kind === 'first' ? 9 : 8),
        fillColor: kind === 'sel' ? '#ef4444' : (kind === 'first' ? '#f5c518' : '#ffffff'), fillOpacity: 1, strokeColor: kind === 'sel' ? '#ffffff' : '#1d3310', strokeWeight: kind === 'sel' ? 3 : 2.5 });
    const midIcon = () => ({ path: G().SymbolPath.CIRCLE, scale: 6, fillColor: '#ffffff', fillOpacity: .6, strokeColor: '#1d3310', strokeOpacity: .75, strokeWeight: 1.5 });
    const snap = () => { S.hist.push(JSON.stringify(S.pts)); if (S.hist.length > 100) S.hist.shift(); };
    const reshape = () => { if (S.poly) S.poly.setPath(S.pts.map(LL)); };
    function paintDraw() {
        S.marks.forEach((m) => m.setMap(null)); S.mids.forEach((m) => m.setMap(null));
        if (!S.poly) S.poly = new (G().Polygon)({ map, strokeColor: '#f5c518', strokeWeight: 3, fillColor: '#f5c518', fillOpacity: .2, clickable: false, zIndex: 1 });
        reshape();
        const n = S.pts.length;
        S.marks = S.pts.map((p, i) => {
            const m = new (G().Marker)({ map, position: LL(p), draggable: true, crossOnDrag: false, zIndex: 40 + i, title: 'Point ' + (i + 1),
                icon: dotIcon(i === S.sel ? 'sel' : (i === 0 ? 'first' : 'dot')) });
            m.addListener('dragstart', () => { snap(); });
            m.addListener('drag', (e) => { S.pts[i] = AR(e.latLng); reshape(); placeMids(); say(); });
            m.addListener('dragend', () => paintDraw());
            m.addListener('click', () => select(S.sel === i ? -1 : i));
            return m;
        });
        // Between two points: tap or drag the small dot to add a point there.
        const edges = n >= 3 ? n : Math.max(0, n - 1);
        S.mids = [];
        for (let i = 0; i < edges; i++) {
            const m = new (G().Marker)({ map, position: LL(MID(S.pts[i], S.pts[(i + 1) % n])), draggable: true, crossOnDrag: false, zIndex: 30, title: 'Add a point here', icon: midIcon() });
            let at = -1;
            m.addListener('dragstart', () => { snap(); at = i + 1; S.pts.splice(at, 0, MID(S.pts[i], S.pts[(i + 1) % n])); S.sel = -1; });
            m.addListener('drag', (e) => { if (at < 0) return; S.pts[at] = AR(e.latLng); reshape(); say(); });
            m.addListener('dragend', () => { S.sel = at; at = -1; paintDraw(); });
            m.addListener('click', () => { snap(); S.pts.splice(i + 1, 0, MID(S.pts[i], S.pts[(i + 1) % n])); S.sel = i + 1; paintDraw(); });
            S.mids.push(m);
        }
        say(); paintBar();
    }
    function placeMids() {
        const n = S.pts.length;
        S.mids.forEach((m, i) => { if (S.pts[i] && S.pts[(i + 1) % n]) m.setPosition(LL(MID(S.pts[i], S.pts[(i + 1) % n]))); });
    }
    function select(i) {
        S.sel = i;
        S.marks.forEach((m, j) => m.setIcon(dotIcon(j === i ? 'sel' : (j === 0 ? 'first' : 'dot'))));
        say(); paintBar();
    }
    function onTap(ll) {
        if (!S || S.busy) return;
        // Setting the pin: a tap brings that spot under the pin.
        if (S.mode === 'pin') { map.panTo(ll); return; }
        // A picked point is put down first; the next tap adds.
        if (S.sel >= 0) { select(-1); return; }
        if (S.pts.length >= 500) { window.toast?.('That is the most points a boundary can have.', 'error'); return; }
        snap();
        S.pts.push(AR(ll));
        paintDraw();
    }

    /* ---- what the bar says, and its buttons ---- */
    const areaHa = () => (S.pts.length >= 3 ? G().geometry.spherical.computeArea(S.pts.map(LL)) / 10000 : 0);
    const meters = (m) => (m >= 1000 ? nf(m / 1000, 2) + ' km' : nf(m, 0) + ' m');
    function say() {
        const el = $('lmSay');
        if (!S) return;
        if (S.mode === 'pin') {
            const c = map && map.getCenter();
            el.innerHTML = '<b>Move the map until the pin sits on your lot</b><small>' + (c ? nf(c.lat(), 6) + ', ' + nf(c.lng(), 6) + ' · ' : '') + 'Search a place, or tap the target to go where you are.</small>';
            return;
        }
        const n = S.pts.length;
        if (!window.google || !window.google.maps || n < 3) {
            el.innerHTML = '<b>' + (n ? 'Tap ' + (3 - n) + ' more corner' + (3 - n === 1 ? '' : 's') : 'Tap the map at each corner of the lot') + '</b><small>Go around its edge in order. Two fingers turn and zoom the map.</small>';
            return;
        }
        const ha = areaHa();
        const per = G().geometry.spherical.computeLength([...S.pts, S.pts[0]].map(LL));
        el.innerHTML = '<b>' + nf(ha, ha < 10 ? 2 : 1) + ' ha' + (ha < 1 ? ' (' + nf(ha * 10000, 0) + ' sq m)' : '') + ' · ' + n + ' points</b>'
            + '<small>' + (S.sel >= 0 ? 'Point ' + (S.sel + 1) + ' is picked: drag it, or delete it. Tap the map to put it down.' : 'Edge ' + meters(per) + '. Drag a point to move it, tap it to pick it. A small dot adds a point between two.') + '</small>';
    }
    function paintBar() {
        const b = $('lmBtns');
        if (!S) return;
        const lot = S.lot, busy = S.busy ? ' disabled' : '';
        if (S.mode === 'pin') {
            b.innerHTML = (lot.pin ? '<button type="button" class="btn btn-white lm-del" data-lm="unpin"' + busy + '>Remove the pin</button>' : '')
                + '<button type="button" class="btn btn-primary lm-main" data-lm="savepin"' + busy + '>' + (S.busy ? 'Saving…' : 'Save the pin') + '</button>';
            return;
        }
        const n = S.pts.length;
        const off = n === 0 && lot.boundary;
        b.innerHTML = '<button type="button" class="btn btn-white" data-lm="undo"' + (S.hist.length && !S.busy ? '' : ' disabled') + '>Undo</button>'
            + (S.sel >= 0 ? '<button type="button" class="btn btn-white lm-del" data-lm="delpt"' + busy + '>Delete point</button>' : '')
            + (n ? '<button type="button" class="btn btn-white" data-lm="clear"' + busy + '>Clear</button>' : '')
            + '<button type="button" class="btn btn-primary lm-main" data-lm="savedraw"' + (n >= 3 || off ? busy : ' disabled') + '>' + (S.busy ? 'Saving…' : (off ? 'Remove the boundary' : 'Save the boundary')) + '</button>';
    }
    $('lmBtns').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-lm]');
        if (!b || b.disabled || !S) return;
        const act = b.dataset.lm;
        if (act === 'undo') { if (S.hist.length) { S.pts = JSON.parse(S.hist.pop()); S.sel = -1; paintDraw(); } return; }
        if (act === 'delpt') { if (S.sel >= 0) { snap(); S.pts.splice(S.sel, 1); S.sel = -1; paintDraw(); } return; }
        if (act === 'clear') { snap(); S.pts = []; S.sel = -1; paintDraw(); window.toast?.('Cleared. Undo brings it back.'); return; }
        if (act === 'savepin' || act === 'unpin') {
            const c = map && map.getCenter();
            if (act === 'savepin' && !c) return;
            await send('pin', act === 'unpin' ? { lat: null, lng: null } : { lat: Math.round(c.lat() * 1e7) / 1e7, lng: Math.round(c.lng() * 1e7) / 1e7 });
            return;
        }
        if (act === 'savedraw') await send('boundary', { points: S.pts.length >= 3 ? S.pts : [] });
    });
    async function send(what, body) {
        const s = S;
        s.busy = true; paintBar();
        try {
            const r = await window.api(D.urls.lots + '/' + s.lot.id + '/' + what, { method: 'POST', body });
            s.row = r.data.row;
            window.toast?.(r.message, 'success');
            window.dispatchEvent(new CustomEvent('aneelots:changed', { detail: { row: s.row } }));
            close();
        } catch (err) {
            window.toast?.(err.message || 'It could not be saved. Please try again.', 'error');
        } finally { s.busy = false; if (S === s) paintBar(); }
    }

    /* ---- search, where I am, the whole lot, north up ---- */
    $('lmFind').addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = $('lmQ').value.trim();
        const box = $('lmRes');
        if (q.length < 2) { box.hidden = true; return; }
        $('lmGo').disabled = true;
        box.hidden = false;
        box.innerHTML = '<p>Finding ' + esc(q) + '…</p>';
        try {
            const r = await window.api(D.urls.find + '?q=' + encodeURIComponent(q));
            const list = (r.data && r.data.places) || [];
            box.innerHTML = list.length ? list.map((p, i) => '<button type="button" data-lmp="' + i + '"><b>' + esc(p.name || p.label) + '</b><small>' + esc(p.label || '') + '</small></button>').join('') : '<p>Nothing by that name. Try the barangay and the town.</p>';
            box._places = list;
        } catch (err) { box.innerHTML = '<p>' + esc(err.message || 'The search did not answer. Try again.') + '</p>'; }
        finally { $('lmGo').disabled = false; }
    });
    $('lmRes').addEventListener('click', (e) => {
        const b = e.target.closest('[data-lmp]');
        if (!b || !map) return;
        const p = ($('lmRes')._places || [])[Number(b.dataset.lmp)];
        if (!p) return;
        $('lmRes').hidden = true;
        $('lmQ').blur();
        goTo(p, 17);
    });
    $('lmQ').addEventListener('input', () => { if (!$('lmQ').value.trim()) $('lmRes').hidden = true; });
    let meMark = null;
    $('lmLocate').addEventListener('click', () => {
        if (!navigator.geolocation || !map) { window.toast?.('This device cannot tell where it is.', 'error'); return; }
        $('lmLocate').disabled = true;
        navigator.geolocation.getCurrentPosition((pos) => {
            $('lmLocate').disabled = false;
            const at = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            map.setCenter(at); map.setZoom(Math.max(map.getZoom() || 0, 18));
            if (meMark) meMark.setMap(null);
            meMark = new (G().Marker)({ map, position: at, clickable: false, zIndex: 4, icon: { path: G().SymbolPath.CIRCLE, scale: 7, fillColor: '#2563eb', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 3 } });
        }, () => { $('lmLocate').disabled = false; window.toast?.('Your location is off or not allowed. Turn it on, or search the place instead.', 'error'); }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 });
    });
    $('lmFit').addEventListener('click', () => {
        if (!map || !S) return;
        const ring = S.mode === 'draw' && S.pts.length ? S.pts : (S.lot.boundary || null);
        if (ring && ring.length) fit(ring); else if (S.lot.pin) { map.setCenter(S.lot.pin); map.setZoom(18); } else frame();
    });
    $('lmNorth').addEventListener('click', () => { if (map) { map.setHeading(0); map.setTilt(0); } });

    window.aneeLotMap = { pin: (lot) => open('pin', lot), draw: (lot) => open('draw', lot) };
})();
</script>
@endpush
@endonce
