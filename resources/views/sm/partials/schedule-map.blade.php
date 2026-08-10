{{-- Collab Room map: draw and measure over real ground, together.
     Google Maps JS API (key in services.google_maps.key). Shapes persist and
     broadcast on the schedule's board channel; member GPS positions broadcast
     but are never stored. Expects: $schedule. --}}
@php $cmapKey = config('services.google_maps.key'); @endphp

<div class="cmap-wrap" id="cmapWrap">
@if (! $cmapKey)
    {{-- No key, no map — say so instead of a grey void. --}}
    <div class="cmap-nokey">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <p class="font-bold text-gray-800">The map needs a Google Maps key</p>
        <p class="text-sm text-gray-500">Set <code class="font-mono text-xs bg-gray-100 rounded px-1">GOOGLE_MAPS_KEY</code> in the environment and redeploy. The rest of the room works without it.</p>
    </div>
@else
    <div class="cmap-bar">
        <button type="button" class="cmap-tool is-active" data-mtool="pan" title="Move the map" aria-label="Move the map">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M2 12h20M12 2L9 5m3-3l3 3M12 22l-3-3m3 3l3-3M2 12l3-3m-3 3l3 3M22 12l-3-3m3 3l-3 3"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="pen" title="Freehand draw" aria-label="Freehand draw">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L18 9l-3-3L5 16l-1 4z"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="line" title="Line — shows its distance" aria-label="Measured line">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 21L21 3M8.5 15.5l1.8 1.8M12 12l1.8 1.8M15.5 8.5l1.8 1.8"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="path" title="Multi-line — a distance per segment" aria-label="Measured multi-line">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l5-6 4 3 6-8"/><path stroke-linecap="round" d="M3 17h.01M8 11h.01M12 14h.01M18 6h.01"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="rect" title="Box — side lengths and area" aria-label="Measured box">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="1.5"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="area" title="Area — tap corners, finish to see hectares" aria-label="Measured area">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 6-3 10H7L4 9l8-6z"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="text" title="Text label" aria-label="Text label">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 6h14M12 6v13M9 19h6"/></svg>
        </button>
        <button type="button" class="cmap-tool" data-mtool="erase" title="Erase — tap a shape to remove it" aria-label="Erase a shape">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l7-7 6 6-4 4H8l-4-3z"/><path stroke-linecap="round" d="M8 18h11"/></svg>
        </button>
        <span class="cmap-div"></span>
        <button type="button" class="cmap-tool cmap-finish" id="cmapFinish" hidden title="Finish this shape" aria-label="Finish this shape">
            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="cmap-tool is-active" id="cmapLayer" title="Toggle map / satellite" aria-label="Toggle map or satellite view" aria-pressed="true">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5"/></svg>
        </button>
        <button type="button" class="cmap-tool" id="cmapGps" title="Share my live GPS position with the team" aria-label="Share my live GPS position">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6.5"/><path stroke-linecap="round" d="M12 2v3.5M12 18.5V22M2 12h3.5M18.5 12H22M12 12h.01"/></svg>
        </button>
        <button type="button" class="cmap-tool cmap-danger" id="cmapClear" title="Clear the whole map for the team" aria-label="Clear the map for the team">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>
        </button>
    </div>
    <div class="cmap-map" id="cmapMap"></div>
@endif
</div>

<style>
    .cmap-wrap { display: flex; flex-direction: column; height: 100%; min-height: 0; }
    .cmap-nokey { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem; padding: 2rem; text-align: center; color: var(--color-gray-400); }
    .cmap-bar { display: flex; align-items: center; gap: .3rem; padding: .4rem .5rem; overflow-x: auto;
        scrollbar-width: none; border-bottom: 1px solid var(--color-gray-100); flex-shrink: 0; }
    .cmap-bar::-webkit-scrollbar { display: none; }
    /* Same visual language as the whiteboard toolbar. */
    .cmap-tool { min-width: 2.15rem; height: 2.15rem; border-radius: .6rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-gray-100); color: var(--color-gray-600);
        transition: background .15s ease, color .15s ease, transform .1s ease; }
    .cmap-tool svg { width: 1.15rem; height: 1.15rem; }
    .cmap-tool:hover { background: var(--color-gray-200); color: var(--color-gray-800); }
    .cmap-tool:active { transform: scale(.92); }
    .cmap-tool.is-active { background: var(--color-brand-100); color: var(--color-brand-800); }
    .cmap-finish { background: var(--color-brand-600); color: #fff; }
    .cmap-finish:hover { background: var(--color-brand-700); color: #fff; }
    .cmap-danger { color: #dc2626; }
    .cmap-danger:hover { background: #fee2e2; color: #b91c1c; }
    .cmap-div { width: 1px; align-self: stretch; background: var(--color-gray-200); flex-shrink: 0; }
    .cmap-map { flex: 1 1 auto; min-height: 0; }
    /* Measurement labels ride Google marker labels — these classes style them. */
    .cmap-lbl-g { background: rgb(17 24 39 / .82); border-radius: .45rem; padding: .1rem .4rem; white-space: nowrap; }
    .cmap-txt-g { background: #fff; border: 1.5px solid #111827; border-radius: .45rem; padding: .12rem .45rem; box-shadow: 0 2px 6px rgb(0 0 0 / .25); }
    .cmap-me-g { background: rgb(17 24 39 / .82); border-radius: .45rem; padding: .05rem .35rem; }
    html.dark .cmap-bar { border-color: #2b3a1c; }
    html.dark .cmap-tool { background: #1c2416; color: #cdd8c0; }
    html.dark .cmap-tool:hover { background: #243019; }
</style>

@if ($cmapKey)
<script>
(() => {
    if (window.initCollabMap) return;
    const SID = {{ (int) $schedule->id }};
    const ME = {{ (int) auth()->id() }};
    const KEY = @json($cmapKey);
    const URLS = {
        objects: @json(route('sm.map')),
        push: @json(route('sm.map.push')),
        remove: @json(route('sm.map.remove')),
        clear: @json(route('sm.map.clear')),
        loc: @json(route('sm.map.loc')),
    };
    let map = null, proj = null, satOn = true;
    let tool = 'pan', color = '#f5c518', width = 3;
    let tempPts = [], tempShape = null;
    const layers = new Map();       // object id -> array of google overlays
    const locMarks = new Map();     // userId -> { parts, at }
    const G = () => window.google.maps;

    const hue = (uid) => 'hsl(' + ((uid * 137) % 360) + ', 70%, 45%)';
    const fmtM = (m) => m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(2) + ' km';
    const fmtA = (m2) => m2 < 10000 ? Math.round(m2).toLocaleString() + ' m²' : (m2 / 10000).toFixed(2) + ' ha';
    const LL = (p) => new (G().LatLng)(p[0], p[1]);
    const dist = (a, b) => G().geometry.spherical.computeDistanceBetween(LL(a), LL(b));
    const areaOf = (pts) => G().geometry.spherical.computeArea(pts.map(LL));
    const mid = (a, b) => [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];

    /* Text-only markers carry every measurement: a zero-scale symbol so only
       the label paints, styled by the cmap-* classes above. */
    function textMark(at, text, cls, colorOverride) {
        return new (G().Marker)({
            map, position: LL(at), clickable: false,
            icon: { path: G().SymbolPath.CIRCLE, scale: 0 },
            label: { text, className: cls, color: colorOverride || '#fff', fontSize: '11px', fontWeight: '800' },
        });
    }
    function segLabels(parts, pts, closed) {
        const n = closed ? pts.length : pts.length - 1;
        for (let i = 0; i < n; i++) {
            const j = (i + 1) % pts.length;
            parts.push(textMark(mid(pts[i], pts[j]), fmtM(dist(pts[i], pts[j])), 'cmap-lbl-g'));
        }
    }
    function centerOf(pts) {
        const b = new (G().LatLngBounds)();
        pts.forEach((p) => b.extend(LL(p)));
        return [b.getCenter().lat(), b.getCenter().lng()];
    }

    /* One renderer for local, remote and loaded shapes — measurements are
       recomputed from the points, so every viewer reads identical numbers. */
    function renderObject(o) {
        if (layers.has(o.id)) return;
        const parts = [];
        const style = { map, strokeColor: o.color || '#f5c518', strokeWeight: o.width || 3, clickable: true };
        const pts = o.points;
        if (o.kind === 'pen' || o.kind === 'line' || o.kind === 'path') {
            parts.push(new (G().Polyline)({ ...style, path: pts.map(LL) }));
            if (o.kind !== 'pen') {
                segLabels(parts, pts, false);
                if (o.kind === 'path' && pts.length > 2) {
                    let total = 0;
                    for (let i = 0; i < pts.length - 1; i++) total += dist(pts[i], pts[i + 1]);
                    parts.push(textMark(pts[pts.length - 1], 'Σ ' + fmtM(total), 'cmap-lbl-g'));
                }
            }
        } else if (o.kind === 'rect') {
            const b = new (G().LatLngBounds)(LL(pts[0]), LL(pts[1]));
            const sw = b.getSouthWest(), ne = b.getNorthEast();
            const c = [[sw.lat(), sw.lng()], [sw.lat(), ne.lng()], [ne.lat(), ne.lng()], [ne.lat(), sw.lng()]];
            parts.push(new (G().Polygon)({ ...style, paths: c.map(LL), fillColor: style.strokeColor, fillOpacity: .08 }));
            segLabels(parts, c, true);
            parts.push(textMark(centerOf(c), fmtA(areaOf(c)), 'cmap-lbl-g'));
        } else if (o.kind === 'area') {
            parts.push(new (G().Polygon)({ ...style, paths: pts.map(LL), fillColor: style.strokeColor, fillOpacity: .1 }));
            segLabels(parts, pts, true);
            parts.push(textMark(centerOf(pts), fmtA(areaOf(pts)), 'cmap-lbl-g'));
        } else if (o.kind === 'text') {
            parts.push(textMark(pts[0], o.label || '', 'cmap-txt-g', '#111827'));
        }
        // The eraser removes whole shapes for everyone.
        parts.forEach((p) => p.addListener && p.addListener('click', () => {
            if (tool !== 'erase') return;
            api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
            dropObject(o.id);
        }));
        layers.set(o.id, parts);
    }
    function dropObject(id) { (layers.get(id) || []).forEach((p) => p.setMap(null)); layers.delete(id); }
    function dropAll() { layers.forEach((parts) => parts.forEach((p) => p.setMap(null))); layers.clear(); }

    async function saveObject(kind, pts, label) {
        try {
            const res = await api(`${URLS.push}?scheduleId=${SID}`, {
                method: 'POST', body: { kind, points: pts, color, width, label: label || null },
            });
            renderObject(res.data.object);
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }

    /* ---------- drawing ---------- */
    function clearTemp() {
        tempPts = [];
        if (tempShape) { tempShape.setMap(null); tempShape = null; }
        document.getElementById('cmapFinish').hidden = true;
    }
    function previewTemp(closed) {
        if (tempShape) tempShape.setMap(null);
        const opts = { map, strokeColor: color, strokeWeight: width, clickable: false };
        tempShape = closed
            ? new (G().Polygon)({ ...opts, paths: tempPts.map(LL), fillColor: color, fillOpacity: .06 })
            : new (G().Polyline)({ ...opts, path: tempPts.map(LL) });
    }
    function setTool(t) {
        tool = t;
        clearTemp();
        document.querySelectorAll('.cmap-tool[data-mtool]').forEach((b) => b.classList.toggle('is-active', b.dataset.mtool === t));
        // Pan keeps native gestures; every drawing tool takes the finger.
        map.setOptions({ gestureHandling: t === 'pan' ? 'greedy' : 'none', draggableCursor: t === 'pan' ? null : 'crosshair' });
    }
    function onTap(latLng) {
        const p = [latLng.lat(), latLng.lng()];
        if (tool === 'line') {
            tempPts.push(p); previewTemp(false);
            if (tempPts.length === 2) { const pts = tempPts; clearTemp(); saveObject('line', pts); }
        } else if (tool === 'path' || tool === 'area') {
            tempPts.push(p); previewTemp(tool === 'area');
            document.getElementById('cmapFinish').hidden = tempPts.length < 2;
        } else if (tool === 'rect') {
            tempPts.push(p);
            if (tempPts.length === 2) {
                const b = new (G().LatLngBounds)(LL(tempPts[0]), LL(tempPts[1]));
                const pts = [[b.getSouthWest().lat(), b.getSouthWest().lng()], [b.getNorthEast().lat(), b.getNorthEast().lng()]];
                clearTemp(); saveObject('rect', pts);
            } else { previewTemp(false); }
        } else if (tool === 'text') {
            const t = prompt('Label text:');
            if (t && t.trim()) saveObject('text', [p], t.trim().slice(0, 500));
        }
    }

    /* Freehand rides pointer events on the container; an OverlayView lends us
       the pixel→latLng projection Google keeps behind one. */
    let penDown = false, penPts = [];
    function bindPen(el) {
        const ll = (e) => {
            const r = el.getBoundingClientRect();
            const pt = new (G().Point)(e.clientX - r.left, e.clientY - r.top);
            const latLng = proj.getProjection().fromContainerPixelToLatLng(pt);
            return [latLng.lat(), latLng.lng()];
        };
        el.addEventListener('pointerdown', (e) => {
            if (tool !== 'pen' || !proj.getProjection()) return;
            penDown = true; penPts = [ll(e)];
            e.preventDefault();
        });
        el.addEventListener('pointermove', (e) => {
            if (!penDown || tool !== 'pen') return;
            penPts.push(ll(e));
            tempPts = penPts; previewTemp(false);
            e.preventDefault();
        });
        const up = () => {
            if (!penDown) return;
            penDown = false;
            const pts = penPts.filter((_, i) => i % 2 === 0);
            clearTemp();
            if (pts.length > 1) saveObject('pen', pts);
        };
        el.addEventListener('pointerup', up);
        el.addEventListener('pointercancel', up);
        el.addEventListener('touchmove', (e) => { if (tool !== 'pan') e.preventDefault(); }, { passive: false });
    }

    /* ---------- live GPS ---------- */
    let gpsWatch = null, lastSent = 0;
    function renderLoc(p) {
        const old = locMarks.get(p.userId);
        if (old) old.parts.forEach((m) => m.setMap(null));
        const parts = [
            new (G().Marker)({
                map, position: LL([p.lat, p.lng]), clickable: false, zIndex: 9000,
                icon: { path: G().SymbolPath.CIRCLE, scale: 8, fillColor: hue(p.userId), fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
            }),
            textMark([p.lat, p.lng], (p.name || '') + (p.userId === ME ? ' (you)' : ''), 'cmap-me-g'),
        ];
        locMarks.set(p.userId, { parts, at: Date.now() });
    }
    setInterval(() => {
        locMarks.forEach((v, k) => { if (Date.now() - v.at > 75000) { v.parts.forEach((m) => m.setMap(null)); locMarks.delete(k); } });
    }, 15000);
    function toggleGps(btn) {
        if (gpsWatch !== null) {
            navigator.geolocation.clearWatch(gpsWatch); gpsWatch = null;
            btn.classList.remove('is-active');
            return;
        }
        if (!navigator.geolocation) { if (window.toast) toast('No GPS on this device.', 'error'); return; }
        btn.classList.add('is-active');
        gpsWatch = navigator.geolocation.watchPosition((pos) => {
            const { latitude: lat, longitude: lng, accuracy: acc } = pos.coords;
            renderLoc({ userId: ME, name: 'Me', lat, lng, acc });
            if (Date.now() - lastSent > 5000) {
                lastSent = Date.now();
                api(`${URLS.loc}?scheduleId=${SID}`, { method: 'POST', body: { lat, lng, acc } }).catch(() => {});
            }
        }, () => { if (window.toast) toast('Could not get your GPS position.', 'error'); btn.classList.remove('is-active'); gpsWatch = null; },
        { enableHighAccuracy: true, maximumAge: 3000, timeout: 15000 });
    }

    /* ---------- boot ---------- */
    let booted = false, loading = false;
    function buildMap() {
        map = new (G().Map)(document.getElementById('cmapMap'), {
            center: { lat: 12.88, lng: 121.77 }, zoom: 6,
            mapTypeId: 'hybrid',                       // farmers plan on what the land looks like
            mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
            gestureHandling: 'greedy',
        });
        proj = new (G().OverlayView)();
        proj.onAdd = function () {}; proj.draw = function () {}; proj.onRemove = function () {};
        proj.setMap(map);

        map.addListener('click', (e) => { if (tool !== 'pan' && tool !== 'pen' && tool !== 'erase') onTap(e.latLng); });
        bindPen(map.getDiv());

        document.querySelectorAll('.cmap-tool[data-mtool]').forEach((b) =>
            b.addEventListener('click', () => setTool(b.dataset.mtool)));
        document.getElementById('cmapFinish').addEventListener('click', () => {
            if (tempPts.length < 2) return;
            const pts = tempPts, kind = tool === 'area' ? 'area' : 'path';
            clearTemp();
            if (kind === 'area' && pts.length < 3) { if (window.toast) toast('An area needs at least 3 corners.', 'error'); return; }
            saveObject(kind, pts);
        });
        document.getElementById('cmapLayer').addEventListener('click', (e) => {
            satOn = !satOn;
            map.setMapTypeId(satOn ? 'hybrid' : 'roadmap');
            e.currentTarget.classList.toggle('is-active', satOn);
            e.currentTarget.setAttribute('aria-pressed', satOn ? 'true' : 'false');
        });
        document.getElementById('cmapGps').addEventListener('click', (e) => toggleGps(e.currentTarget));
        document.getElementById('cmapClear').addEventListener('click', async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Clear the map?', message: 'Removes every shape for the whole team.', confirmText: 'Clear map' })
                : confirm('Clear the map for everyone?');
            if (!ok) return;
            try { await api(`${URLS.clear}?scheduleId=${SID}`, { method: 'POST' }); dropAll(); }
            catch (err) { if (window.toast) toast(err.message, 'error'); }
        });

        // Existing shapes, then follow the room live.
        api(`${URLS.objects}?scheduleId=${SID}`).then((r) => {
            (r.data.objects || []).forEach(renderObject);
            const b = new (G().LatLngBounds)();
            let any = false;
            (r.data.objects || []).forEach((o) => o.kind !== 'text' && o.points.forEach((p) => { b.extend(LL(p)); any = true; }));
            if (any) map.fitBounds(b, 48);
        }).catch(() => {});

        if (window.Echo) {
            try {
                const ch = window.Echo.private('schedule-board.' + SID);
                ch.listen('.map.object', (p) => {
                    if (!p || p.actorUserId === ME) return;
                    if (p.action === 'add' && p.object) renderObject(p.object);
                    else if (p.action === 'remove') dropObject(p.id);
                    else if (p.action === 'clear') dropAll();
                });
                ch.listen('.map.loc', (p) => { if (p && p.userId !== ME) renderLoc(p); });
            } catch (_) { /* map still works solo */ }
        }
    }

    window.initCollabMap = function () {
        if (booted) return;
        if (window.google && window.google.maps) { booted = true; buildMap(); return; }
        if (loading) return;
        loading = true;
        // Loaded only when the tab is opened — no quota spent on rooms that
        // never look at the map.
        window.__cmapBoot = () => { booted = true; buildMap(); };
        const s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(KEY)
            + '&libraries=geometry&v=weekly&loading=async&callback=__cmapBoot';
        s.async = true;
        s.onerror = () => { loading = false; if (window.toast) toast('Could not load Google Maps — check the API key.', 'error'); };
        document.head.appendChild(s);
    };
})();
</script>
@endif
