{{-- Collab Room map: draw and measure over real ground, together.
     Leaflet + OpenStreetMap / Esri satellite — no API key, no billing, which
     is why it is not Google Maps; swap the tile layer if a key ever exists.
     Shapes persist (they are plans, not scribbles) and broadcast live on the
     schedule's board channel; member GPS positions broadcast but are never
     stored. Expects: $schedule. --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="cmap-wrap" id="cmapWrap">
    <div class="cmap-bar">
        <button type="button" class="cmap-tool is-active" data-mtool="pan" title="Move the map">✋</button>
        <button type="button" class="cmap-tool" data-mtool="pen" title="Freehand draw">✏️</button>
        <button type="button" class="cmap-tool" data-mtool="line" title="Line — shows its distance">📏</button>
        <button type="button" class="cmap-tool" data-mtool="path" title="Multi-line — a distance per segment">〰️</button>
        <button type="button" class="cmap-tool" data-mtool="rect" title="Square/box — side lengths and area">⬜</button>
        <button type="button" class="cmap-tool" data-mtool="area" title="Area — tap corners, finish to see hectares">🔶</button>
        <button type="button" class="cmap-tool" data-mtool="text" title="Text label">🔤</button>
        <button type="button" class="cmap-tool" data-mtool="erase" title="Erase — tap a shape to remove it">🧽</button>
        <span class="cmap-div"></span>
        <button type="button" class="cmap-tool" id="cmapFinish" hidden title="Finish this shape">✔</button>
        <button type="button" class="cmap-tool" id="cmapLayer" title="Map / satellite">🛰️</button>
        <button type="button" class="cmap-tool" id="cmapGps" title="Share my live GPS position with the team">📍</button>
        <button type="button" class="cmap-tool" id="cmapClear" title="Clear the whole map for the team">🗑</button>
    </div>
    <div class="cmap-map" id="cmapMap"></div>
</div>

<style>
    .cmap-wrap { display: flex; flex-direction: column; height: 100%; min-height: 0; }
    .cmap-bar { display: flex; align-items: center; gap: .3rem; padding: .4rem .5rem; overflow-x: auto;
        scrollbar-width: none; border-bottom: 1px solid var(--color-gray-100); flex-shrink: 0; }
    .cmap-bar::-webkit-scrollbar { display: none; }
    .cmap-tool { min-width: 2.3rem; height: 2.3rem; border-radius: .6rem; font-size: 1.05rem;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        background: var(--color-gray-50); }
    .cmap-tool.is-active { background: var(--color-brand-100); box-shadow: inset 0 0 0 2px var(--color-brand-500); }
    .cmap-div { width: 1px; align-self: stretch; background: var(--color-gray-200); flex-shrink: 0; }
    .cmap-map { flex: 1 1 auto; min-height: 0; }
    /* Measurement labels: readable over any imagery. */
    .cmap-lbl { background: rgb(17 24 39 / .82); color: #fff; border: 0; border-radius: .45rem;
        padding: .1rem .4rem; font-size: .68rem; font-weight: 800; box-shadow: none; white-space: nowrap; }
    .cmap-lbl::before { display: none; }
    .cmap-txt { background: #fff; color: #111827; border: 1.5px solid #111827; border-radius: .45rem;
        padding: .12rem .45rem; font-size: .78rem; font-weight: 800; box-shadow: 0 2px 6px rgb(0 0 0 / .25); }
    .cmap-me { font-size: .66rem; font-weight: 800; }
    html.dark .cmap-bar { border-color: #2b3a1c; }
    html.dark .cmap-tool { background: #1c2416; }
</style>

<script>
(() => {
    if (window.initCollabMap) return;
    const SID = {{ (int) $schedule->id }};
    const ME = {{ (int) auth()->id() }};
    const URLS = {
        objects: @json(route('sm.map')),
        push: @json(route('sm.map.push')),
        remove: @json(route('sm.map.remove')),
        clear: @json(route('sm.map.clear')),
        loc: @json(route('sm.map.loc')),
    };
    let map = null, layerSat = null, layerOsm = null, satOn = true;
    let tool = 'pan', color = '#f5c518', width = 3;
    let tempPts = [], tempLayer = null;
    const layers = new Map();       // object id -> leaflet layer group
    const locMarks = new Map();     // userId -> { marker, at }

    const hue = (uid) => 'hsl(' + ((uid * 137) % 360) + ', 70%, 45%)';
    const fmtM = (m) => m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(2) + ' km';
    const fmtA = (m2) => m2 < 10000 ? Math.round(m2).toLocaleString() + ' m²' : (m2 / 10000).toFixed(2) + ' ha';

    /* Planar metres around a local origin — exact enough at field scale,
       and it keeps the geometry honest for the shoelace area below. */
    function toXY(pts) {
        const lat0 = pts[0][0] * Math.PI / 180;
        return pts.map((p) => [p[1] * 111320 * Math.cos(lat0), p[0] * 110540]);
    }
    function dist(a, b) { return map.distance(a, b); }
    function areaOf(pts) {
        const xy = toXY(pts); let s = 0;
        for (let i = 0; i < xy.length; i++) {
            const j = (i + 1) % xy.length;
            s += xy[i][0] * xy[j][1] - xy[j][0] * xy[i][1];
        }
        return Math.abs(s / 2);
    }
    const mid = (a, b) => [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];
    function segLabels(g, pts, closed) {
        const n = closed ? pts.length : pts.length - 1;
        for (let i = 0; i < n; i++) {
            const j = (i + 1) % pts.length;
            L.tooltip({ permanent: true, direction: 'center', className: 'cmap-lbl' })
                .setLatLng(mid(pts[i], pts[j])).setContent(fmtM(dist(pts[i], pts[j]))).addTo(g);
        }
    }

    /* One renderer for local, remote and loaded shapes — measurements are
       recomputed from the points, so every viewer sees identical numbers. */
    function renderObject(o) {
        if (layers.has(o.id)) return;
        const g = L.layerGroup().addTo(map);
        const style = { color: o.color || '#f5c518', weight: o.width || 3 };
        const pts = o.points;
        if (o.kind === 'pen') {
            L.polyline(pts, style).addTo(g);
        } else if (o.kind === 'line' || o.kind === 'path') {
            L.polyline(pts, style).addTo(g);
            segLabels(g, pts, false);
            if (o.kind === 'path' && pts.length > 2) {
                let total = 0;
                for (let i = 0; i < pts.length - 1; i++) total += dist(pts[i], pts[i + 1]);
                L.tooltip({ permanent: true, direction: 'top', className: 'cmap-lbl' })
                    .setLatLng(pts[pts.length - 1]).setContent('Σ ' + fmtM(total)).addTo(g);
            }
        } else if (o.kind === 'rect') {
            const b = L.latLngBounds(pts);
            L.rectangle(b, { ...style, fillOpacity: .08 }).addTo(g);
            const c = [[b.getSouth(), b.getWest()], [b.getSouth(), b.getEast()], [b.getNorth(), b.getEast()], [b.getNorth(), b.getWest()]];
            segLabels(g, c, true);
            L.tooltip({ permanent: true, direction: 'center', className: 'cmap-lbl' })
                .setLatLng(b.getCenter()).setContent(fmtA(areaOf(c))).addTo(g);
        } else if (o.kind === 'area') {
            L.polygon(pts, { ...style, fillOpacity: .1 }).addTo(g);
            segLabels(g, pts, true);
            L.tooltip({ permanent: true, direction: 'center', className: 'cmap-lbl' })
                .setLatLng(L.polygon(pts).getBounds().getCenter()).setContent(fmtA(areaOf(pts))).addTo(g);
        } else if (o.kind === 'text') {
            L.tooltip({ permanent: true, direction: 'center', className: 'cmap-txt', interactive: true })
                .setLatLng(pts[0]).setContent(escapeHtml(o.label || '')).addTo(g);
        }
        // The eraser removes whole shapes: any tap on the group while it is
        // the active tool deletes for everyone.
        g.eachLayer((l) => l.on && l.on('click', (e) => {
            if (tool !== 'erase') return;
            L.DomEvent.stop(e);
            api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
            dropObject(o.id);
        }));
        layers.set(o.id, g);
    }
    function dropObject(id) { const g = layers.get(id); if (g) { map.removeLayer(g); layers.delete(id); } }
    function dropAll() { layers.forEach((g) => map.removeLayer(g)); layers.clear(); }

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
        if (tempLayer) { map.removeLayer(tempLayer); tempLayer = null; }
        document.getElementById('cmapFinish').hidden = true;
    }
    function previewTemp(closed) {
        if (tempLayer) map.removeLayer(tempLayer);
        tempLayer = (closed ? L.polygon : L.polyline)(tempPts, { color, weight: width, dashArray: '6 4', fillOpacity: .06 }).addTo(map);
    }
    function setTool(t) {
        tool = t;
        clearTemp();
        document.querySelectorAll('.cmap-tool[data-mtool]').forEach((b) => b.classList.toggle('is-active', b.dataset.mtool === t));
        // Pan keeps native dragging; every drawing tool takes the finger.
        if (t === 'pan') map.dragging.enable(); else map.dragging.disable();
    }

    function onTap(latlng) {
        const p = [latlng.lat, latlng.lng];
        if (tool === 'line') {
            tempPts.push(p); previewTemp(false);
            if (tempPts.length === 2) { const pts = tempPts; clearTemp(); saveObject('line', pts); }
        } else if (tool === 'path' || tool === 'area') {
            tempPts.push(p); previewTemp(tool === 'area');
            document.getElementById('cmapFinish').hidden = tempPts.length < 2;
        } else if (tool === 'rect') {
            tempPts.push(p);
            if (tempPts.length === 2) {
                const b = L.latLngBounds(tempPts);
                const pts = [[b.getSouth(), b.getWest()], [b.getNorth(), b.getEast()]];
                clearTemp(); saveObject('rect', pts);
            } else { previewTemp(false); }
        } else if (tool === 'text') {
            const t = prompt('Label text:');
            if (t && t.trim()) saveObject('text', [p], t.trim().slice(0, 500));
        }
    }

    /* Freehand rides pointer events on the container — Leaflet's click
       synthesis is for taps, not strokes. */
    let penDown = false, penPts = [];
    function bindPen(el) {
        const ll = (e) => {
            const r = el.getBoundingClientRect();
            return map.containerPointToLatLng(L.point(e.clientX - r.left, e.clientY - r.top));
        };
        el.addEventListener('pointerdown', (e) => {
            if (tool !== 'pen') return;
            penDown = true; penPts = [];
            const p = ll(e); penPts.push([p.lat, p.lng]);
            e.preventDefault();
        });
        el.addEventListener('pointermove', (e) => {
            if (!penDown || tool !== 'pen') return;
            const p = ll(e); penPts.push([p.lat, p.lng]);
            tempPts = penPts; previewTemp(false);
            e.preventDefault();
        });
        const up = () => {
            if (!penDown) return;
            penDown = false;
            const pts = penPts.filter((_, i) => i % 2 === 0);   // thin the stream
            clearTemp();
            if (pts.length > 1) saveObject('pen', pts);
        };
        el.addEventListener('pointerup', up);
        el.addEventListener('pointercancel', up);
        // While a drawing tool is active the browser must not scroll the page.
        el.addEventListener('touchmove', (e) => { if (tool !== 'pan') e.preventDefault(); }, { passive: false });
    }

    /* ---------- live GPS ---------- */
    let gpsWatch = null, lastSent = 0;
    function renderLoc(p) {
        const stale = locMarks.get(p.userId);
        if (stale) map.removeLayer(stale.marker);
        const marker = L.circleMarker([p.lat, p.lng], {
            radius: 8, color: '#fff', weight: 2, fillColor: hue(p.userId), fillOpacity: 1,
        }).addTo(map).bindTooltip(escapeHtml(p.name || '') + (p.userId === ME ? ' (you)' : ''),
            { permanent: true, direction: 'top', offset: [0, -8], className: 'cmap-lbl cmap-me' });
        locMarks.set(p.userId, { marker, at: Date.now() });
    }
    setInterval(() => {
        // A dot that stopped reporting is somebody who left — let it fade out.
        locMarks.forEach((v, k) => { if (Date.now() - v.at > 75000) { map.removeLayer(v.marker); locMarks.delete(k); } });
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
    let booted = false;
    window.initCollabMap = function () {
        if (booted) { map && map.invalidateSize(); return; }
        booted = true;
        map = L.map('cmapMap', { zoomControl: true, attributionControl: true }).setView([12.88, 121.77], 6);
        layerOsm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            { maxZoom: 19, attribution: '© OpenStreetMap' });
        layerSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            { maxZoom: 19, attribution: 'Imagery © Esri' });
        layerSat.addTo(map);   // farmers plan on what the land looks like

        map.on('click', (e) => { if (tool !== 'pan' && tool !== 'pen' && tool !== 'erase') onTap(e.latlng); });
        bindPen(map.getContainer());

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
            (satOn ? layerSat : layerOsm).addTo(map);
            map.removeLayer(satOn ? layerOsm : layerSat);
            e.currentTarget.textContent = satOn ? '🛰️' : '🗺️';
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
            const all = [];
            (r.data.objects || []).forEach((o) => o.kind !== 'text' && o.points.forEach((p) => all.push(p)));
            if (all.length) map.fitBounds(L.latLngBounds(all).pad(.2));
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
    };
})();
</script>
