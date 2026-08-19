@once
{{-- The step between choosing a photo and posting it.

     A farmer photographing a diseased leaf wants to circle the spot, write
     "ganito ang itsura" next to it, and send that — not the raw frame with an
     explanation typed underneath. So a picked photo passes through here first:
     filters, text, freehand, and arrows or boxes to point with.

     Everything is kept as a list of objects over the source image and only
     flattened on save, so any of it can be undone right up to the moment it
     leaves. The export is WebP because that is what this app stores.

     Public contract, deliberately tiny so every composer can use it:

         const edited = await window.smEditPhoto(file);   // File | null

     null means the farmer backed out; the caller should treat that as "no
     photo chosen" rather than as an error. --}}
<div class="pe-wrap hidden" id="peWrap" role="dialog" aria-modal="true" aria-label="Edit photo">
    <div class="pe-bar pe-bar-top">
        <button type="button" class="pe-icon" id="peCancel" aria-label="Cancel">✕</button>
        <span class="pe-title">Edit photo</span>
        <button type="button" class="btn btn-primary btn-sm" id="peDone">Use photo</button>
    </div>

    <div class="pe-stage" id="peStage">
        <canvas id="peCanvas"></canvas>
    </div>

    {{-- The tool being used decides what the second row offers. --}}
    <div class="pe-bar pe-bar-tools">
        <button type="button" class="pe-tool is-on" data-tool="filter" title="Filters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="9" cy="9" r="6"/><circle cx="15" cy="15" r="6"/></svg>
            <i>Filter</i>
        </button>
        <button type="button" class="pe-tool" data-tool="text" title="Add text">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" d="M4 6h16M9 6v13m6-13v13"/></svg>
            <i>Text</i>
        </button>
        <button type="button" class="pe-tool" data-tool="draw" title="Draw">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21l3.5-.8L20 6.7a2 2 0 10-2.8-2.8L3.8 17.5 3 21z"/></svg>
            <i>Draw</i>
        </button>
        <button type="button" class="pe-tool" data-tool="arrow" title="Arrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19L19 5m0 0h-7m7 0v7"/></svg>
            <i>Arrow</i>
        </button>
        <button type="button" class="pe-tool" data-tool="rect" title="Box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4" y="6" width="16" height="12" rx="2"/></svg>
            <i>Box</i>
        </button>
        <button type="button" class="pe-tool" data-tool="ellipse" title="Circle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><ellipse cx="12" cy="12" rx="8" ry="6"/></svg>
            <i>Circle</i>
        </button>
        <span class="pe-sep"></span>
        <button type="button" class="pe-icon" id="peUndo" title="Undo" aria-label="Undo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14L4 9l5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 9h9a7 7 0 010 14h-3"/></svg>
        </button>
    </div>

    {{-- Filters: named, because "warm" means something and "sepia(0.4)" does not. --}}
    <div class="pe-panel" id="pePanelFilter">
        <div class="pe-filters" id="peFilters"></div>
    </div>

    {{-- Everything a mark can be: its colour, and how thick. --}}
    <div class="pe-panel hidden" id="pePanelInk">
        <div class="pe-swatches" id="peInkColors"></div>
        <label class="pe-range">
            <span>Thickness</span>
            <input type="range" id="peWidth" min="2" max="28" value="6">
        </label>
    </div>

    {{-- Text carries more choices than a mark, so it gets its own row. --}}
    <div class="pe-panel hidden" id="pePanelText">
        <input type="text" id="peTextInput" class="form-input" maxlength="120" placeholder="Type something…">
        <div class="pe-textrow">
            <select id="peFont" class="form-select" aria-label="Font">
                <option value="'Nunito Sans', system-ui, sans-serif">Sans</option>
                <option value="Georgia, 'Times New Roman', serif">Serif</option>
                <option value="'Courier New', ui-monospace, monospace">Mono</option>
                <option value="Impact, 'Arial Black', sans-serif">Heavy</option>
            </select>
            <select id="peTextSize" class="form-select" aria-label="Text size">
                <option value="0.05">Small</option>
                <option value="0.08" selected>Medium</option>
                <option value="0.13">Large</option>
            </select>
        </div>
        <div class="pe-textrow">
            <span class="pe-lbl">Text</span>
            <div class="pe-swatches" id="peTextColors"></div>
        </div>
        <div class="pe-textrow">
            <span class="pe-lbl">Behind</span>
            <div class="pe-swatches" id="peBgColors"></div>
        </div>
        <label class="pe-check">
            <input type="checkbox" id="peTextBorder"> Outline the letters
        </label>
        <button type="button" class="btn btn-primary btn-sm w-full" id="peAddText">Place the text</button>
        <p class="pe-hint">Then drag it where you want it.</p>
    </div>
</div>

<style>
    .pe-wrap { position: fixed; inset: 0; z-index: 320; display: flex; flex-direction: column;
        background: #0b0f0a; color: #fff;
        padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);
        animation: peIn .28s cubic-bezier(.22,1,.36,1); }
    .pe-wrap.hidden { display: none; }
    @keyframes peIn { from { opacity: 0; } }

    .pe-bar { display: flex; align-items: center; gap: .4rem; padding: .55rem .7rem; flex: none; }
    .pe-bar-top { justify-content: space-between; border-bottom: 1px solid rgb(255 255 255 / .08); }
    .pe-title { font-weight: 700; font-size: .95rem; }
    .pe-icon { width: 2.25rem; height: 2.25rem; border-radius: 999px; border: 0; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgb(255 255 255 / .12); color: #fff; }
    .pe-icon svg { width: 1.15rem; height: 1.15rem; }
    .pe-icon:hover { background: rgb(255 255 255 / .2); }

    .pe-stage { flex: 1 1 auto; min-height: 0; display: flex; align-items: center; justify-content: center;
        padding: .5rem; overflow: hidden; }
    #peCanvas { max-width: 100%; max-height: 100%; touch-action: none; border-radius: .4rem;
        background: #000; cursor: crosshair; }

    .pe-bar-tools { overflow-x: auto; border-top: 1px solid rgb(255 255 255 / .08); }
    .pe-tool { flex: none; display: inline-flex; flex-direction: column; align-items: center; gap: .12rem;
        padding: .35rem .5rem; border-radius: .55rem; border: 0; cursor: pointer;
        background: none; color: rgb(255 255 255 / .65);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .pe-tool svg { width: 1.25rem; height: 1.25rem; }
    .pe-tool i { font-style: normal; font-size: .6rem; font-weight: 700; }
    .pe-tool.is-on { background: rgb(255 255 255 / .14); color: #fff; }
    .pe-sep { flex: 1 1 auto; }

    .pe-panel { flex: none; padding: .6rem .7rem .8rem; border-top: 1px solid rgb(255 255 255 / .08);
        display: flex; flex-direction: column; gap: .5rem; max-height: 42vh; overflow-y: auto; }
    .pe-panel.hidden { display: none; }
    .pe-filters { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .2rem; }
    .pe-filter { flex: none; width: 4.2rem; cursor: pointer; border: 0; background: none; padding: 0;
        color: rgb(255 255 255 / .7); }
    .pe-filter canvas { width: 4.2rem; height: 4.2rem; object-fit: cover; border-radius: .5rem;
        border: 2px solid transparent; display: block; background: #000; }
    .pe-filter.is-on { color: #fff; }
    .pe-filter.is-on canvas { border-color: #8fbf5f; }
    .pe-filter span { display: block; font-size: .6rem; font-weight: 700; margin-top: .15rem; }

    .pe-swatches { display: flex; gap: .35rem; flex-wrap: wrap; }
    .pe-sw { width: 1.6rem; height: 1.6rem; border-radius: 999px; cursor: pointer;
        border: 2px solid rgb(255 255 255 / .3); padding: 0; }
    .pe-sw.is-on { border-color: #fff; box-shadow: 0 0 0 2px rgb(143 191 95 / .8); }
    .pe-sw.is-none { background: repeating-linear-gradient(45deg, #444 0 4px, #222 4px 8px); }

    .pe-range { display: flex; align-items: center; gap: .6rem; font-size: .78rem; font-weight: 600; }
    .pe-range input { flex: 1; }
    .pe-textrow { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .pe-lbl { font-size: .72rem; font-weight: 700; color: rgb(255 255 255 / .6); min-width: 3.2rem; }
    .pe-check { display: flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 600; }
    .pe-hint { font-size: .7rem; color: rgb(255 255 255 / .45); text-align: center; }
    .pe-panel .form-input, .pe-panel .form-select {
        background: rgb(255 255 255 / .1); border-color: rgb(255 255 255 / .18); color: #fff; }
    .pe-panel .form-input::placeholder { color: rgb(255 255 255 / .45); }

    @media (prefers-reduced-motion: reduce) {
        .pe-wrap { animation: none; }
        .pe-tool { transition: none; }
    }
</style>

<script>
(function photoEditor() {
    if (window.smEditPhoto) return;

    const wrap = document.getElementById('peWrap');
    const canvas = document.getElementById('peCanvas');
    const ctx = canvas.getContext('2d');
    const $ = (id) => document.getElementById(id);

    /* A filter is a name and a canvas filter string; the browser does the
       maths, which is both faster and better than anything hand-rolled. */
    const FILTERS = [
        ['None', 'none'],
        ['Warm', 'saturate(1.25) sepia(.18) contrast(1.05)'],
        ['Cool', 'saturate(1.1) hue-rotate(-12deg) brightness(1.03)'],
        ['Bright', 'brightness(1.18) contrast(1.06)'],
        ['Punch', 'saturate(1.6) contrast(1.18)'],
        ['Mono', 'grayscale(1) contrast(1.08)'],
        ['Faded', 'saturate(.75) brightness(1.08) contrast(.92)'],
    ];
    const INKS = ['#ef4444', '#f59e0b', '#22c55e', '#3b82f6', '#a855f7', '#ffffff', '#111827'];
    const BGS = [null, '#000000', '#ffffff', '#ef4444', '#22c55e', '#3b82f6'];

    let img = null;              // the source, at natural size
    let filter = 'none';
    let items = [];              // strokes, shapes and text, in the order made
    let tool = 'filter';
    let ink = INKS[0];
    let lineWidth = 6;
    let textColor = '#ffffff';
    let textBg = null;
    let resolveWith = null;      // the promise the caller is waiting on

    /* ---------- painting ---------- */
    function draw() {
        if (!img) return;
        ctx.save();
        ctx.filter = filter;
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        ctx.restore();

        for (const it of items) {
            ctx.save();
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = it.color;
            ctx.fillStyle = it.color;
            ctx.lineWidth = it.width;
            if (it.kind === 'draw') {
                ctx.beginPath();
                it.points.forEach((p, i) => (i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y)));
                ctx.stroke();
            } else if (it.kind === 'rect') {
                ctx.strokeRect(it.x1, it.y1, it.x2 - it.x1, it.y2 - it.y1);
            } else if (it.kind === 'ellipse') {
                ctx.beginPath();
                ctx.ellipse((it.x1 + it.x2) / 2, (it.y1 + it.y2) / 2,
                    Math.abs(it.x2 - it.x1) / 2, Math.abs(it.y2 - it.y1) / 2, 0, 0, Math.PI * 2);
                ctx.stroke();
            } else if (it.kind === 'arrow') {
                arrow(it);
            } else if (it.kind === 'text') {
                text(it);
            }
            ctx.restore();
        }
    }

    function arrow(it) {
        const head = Math.max(it.width * 2.6, 12);
        const a = Math.atan2(it.y2 - it.y1, it.x2 - it.x1);
        ctx.beginPath();
        ctx.moveTo(it.x1, it.y1);
        ctx.lineTo(it.x2, it.y2);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(it.x2, it.y2);
        ctx.lineTo(it.x2 - head * Math.cos(a - Math.PI / 7), it.y2 - head * Math.sin(a - Math.PI / 7));
        ctx.lineTo(it.x2 - head * Math.cos(a + Math.PI / 7), it.y2 - head * Math.sin(a + Math.PI / 7));
        ctx.closePath();
        ctx.fill();
    }

    function text(it) {
        const size = Math.round(it.size * canvas.height);
        ctx.font = '700 ' + size + 'px ' + it.font;
        ctx.textBaseline = 'top';
        const pad = Math.round(size * 0.22);
        const w = ctx.measureText(it.body).width;
        if (it.bg) {
            ctx.fillStyle = it.bg;
            ctx.fillRect(it.x - pad, it.y - pad, w + pad * 2, size + pad * 2);
        }
        if (it.border) {
            // An outline is what keeps white letters readable over a white sky.
            ctx.lineWidth = Math.max(2, size * 0.08);
            ctx.strokeStyle = it.bg && it.bg !== '#000000' ? '#000000' : '#000000';
            ctx.strokeText(it.body, it.x, it.y);
        }
        ctx.fillStyle = it.color;
        ctx.fillText(it.body, it.x, it.y);
        it._w = w;
        it._h = size;
    }

    /* ---------- pointer work ---------- */
    const at = (e) => {
        const r = canvas.getBoundingClientRect();
        return {
            x: (e.clientX - r.left) * (canvas.width / r.width),
            y: (e.clientY - r.top) * (canvas.height / r.height),
        };
    };

    let live = null, dragging = null;

    canvas.addEventListener('pointerdown', (e) => {
        if (!img) return;
        canvas.setPointerCapture(e.pointerId);
        const p = at(e);

        // Text already placed can be picked up and moved, whatever tool is out.
        for (let i = items.length - 1; i >= 0; i--) {
            const it = items[i];
            if (it.kind !== 'text') continue;
            if (p.x >= it.x - 8 && p.x <= it.x + (it._w || 0) + 8
                && p.y >= it.y - 8 && p.y <= it.y + (it._h || 0) + 8) {
                dragging = { it, dx: p.x - it.x, dy: p.y - it.y };
                return;
            }
        }
        if (tool === 'filter' || tool === 'text') return;

        live = tool === 'draw'
            ? { kind: 'draw', color: ink, width: lineWidth, points: [p] }
            : { kind: tool, color: ink, width: lineWidth, x1: p.x, y1: p.y, x2: p.x, y2: p.y };
        items.push(live);
    });

    canvas.addEventListener('pointermove', (e) => {
        if (!img) return;
        const p = at(e);
        if (dragging) {
            dragging.it.x = p.x - dragging.dx;
            dragging.it.y = p.y - dragging.dy;
            draw();
            return;
        }
        if (!live) return;
        if (live.kind === 'draw') live.points.push(p);
        else { live.x2 = p.x; live.y2 = p.y; }
        draw();
    });

    const release = () => {
        // A tap that never moved leaves nothing behind but a dot nobody meant.
        if (live && live.kind === 'draw' && live.points.length < 2) items.pop();
        live = null;
        dragging = null;
        draw();
    };
    canvas.addEventListener('pointerup', release);
    canvas.addEventListener('pointercancel', release);

    /* ---------- the panels ---------- */
    function showPanel() {
        $('pePanelFilter').classList.toggle('hidden', tool !== 'filter');
        $('pePanelText').classList.toggle('hidden', tool !== 'text');
        $('pePanelInk').classList.toggle('hidden', tool === 'filter' || tool === 'text');
    }

    document.querySelectorAll('.pe-tool').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('.pe-tool').forEach((x) => x.classList.toggle('is-on', x === b));
        tool = b.dataset.tool;
        showPanel();
    }));

    function swatches(host, colors, current, onPick) {
        host.innerHTML = '';
        colors.forEach((c) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'pe-sw' + (c === current ? ' is-on' : '') + (c === null ? ' is-none' : '');
            if (c) b.style.background = c;
            b.setAttribute('aria-label', c || 'No background');
            b.addEventListener('click', () => {
                onPick(c);
                host.querySelectorAll('.pe-sw').forEach((x) => x.classList.toggle('is-on', x === b));
            });
            host.appendChild(b);
        });
    }

    swatches($('peInkColors'), INKS, ink, (c) => { ink = c; });
    swatches($('peTextColors'), INKS, textColor, (c) => { textColor = c; });
    swatches($('peBgColors'), BGS, textBg, (c) => { textBg = c; });
    $('peWidth').addEventListener('input', (e) => { lineWidth = parseInt(e.target.value, 10); });

    $('peAddText').addEventListener('click', () => {
        const body = $('peTextInput').value.trim();
        if (!body) { window.toast?.('Type something first.', 'error'); return; }
        items.push({
            kind: 'text',
            body,
            // Dropped near the top, then dragged: a phone has no room to
            // place-then-type, and the drag is the placing.
            x: canvas.width * 0.1,
            y: canvas.height * 0.12,
            size: parseFloat($('peTextSize').value),
            font: $('peFont').value,
            color: textColor,
            bg: textBg,
            border: $('peTextBorder').checked,
            width: 2,
        });
        $('peTextInput').value = '';
        draw();
        window.toast?.('Drag it where you want it.');
    });

    $('peUndo').addEventListener('click', () => {
        if (!items.length) { filter = 'none'; paintFilterChoices(); draw(); return; }
        items.pop();
        draw();
    });

    /* Filter thumbnails are drawn from the photo itself, so a farmer picks by
       looking at their own picture rather than at a word. */
    function paintFilterChoices() {
        const host = $('peFilters');
        host.innerHTML = '';
        FILTERS.forEach(([name, css]) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'pe-filter' + (css === filter ? ' is-on' : '');
            const c = document.createElement('canvas');
            c.width = 96; c.height = 96;
            const cc = c.getContext('2d');
            cc.filter = css;
            if (img) {
                const side = Math.min(img.naturalWidth, img.naturalHeight);
                cc.drawImage(img, (img.naturalWidth - side) / 2, (img.naturalHeight - side) / 2, side, side, 0, 0, 96, 96);
            }
            const s = document.createElement('span');
            s.textContent = name;
            b.appendChild(c);
            b.appendChild(s);
            b.addEventListener('click', () => {
                filter = css;
                host.querySelectorAll('.pe-filter').forEach((x) => x.classList.toggle('is-on', x === b));
                draw();
            });
            host.appendChild(b);
        });
    }

    /* ---------- open / close ---------- */
    function close(result) {
        wrap.classList.add('hidden');
        document.documentElement.classList.remove('overlay-open');
        const done = resolveWith;
        resolveWith = null;
        img = null;
        items = [];
        filter = 'none';
        if (done) done(result);
    }

    $('peCancel').addEventListener('click', () => close(null));
    $('peDone').addEventListener('click', () => {
        const btn = $('peDone');
        btn.disabled = true;
        const was = btn.textContent;
        btn.textContent = 'Saving…';
        // Flattened once, at the end: everything above is still editable until
        // this moment.
        canvas.toBlob((blob) => {
            btn.disabled = false;
            btn.textContent = was;
            if (!blob) { close(null); return; }
            close(new File([blob], 'edited-' + Date.now() + '.webp', { type: 'image/webp' }));
        }, 'image/webp', 0.86);
    });

    /**
     * Hand a photo in, get an edited one back.
     *
     * The canvas is capped at 1600px on its long side: bigger than any phone
     * screen shows and small enough that a WebP of it is a message rather
     * than a download.
     */
    window.smEditPhoto = (file) => new Promise((resolve) => {
        if (!file || !String(file.type || '').startsWith('image/')) { resolve(file || null); return; }
        const url = URL.createObjectURL(file);
        const image = new Image();
        image.onload = () => {
            URL.revokeObjectURL(url);
            img = image;
            const MAX = 1600;
            const scale = Math.min(1, MAX / Math.max(image.naturalWidth, image.naturalHeight));
            canvas.width = Math.round(image.naturalWidth * scale);
            canvas.height = Math.round(image.naturalHeight * scale);
            items = [];
            filter = 'none';
            tool = 'filter';
            document.querySelectorAll('.pe-tool').forEach((x) => x.classList.toggle('is-on', x.dataset.tool === 'filter'));
            showPanel();
            paintFilterChoices();
            draw();
            resolveWith = resolve;
            wrap.classList.remove('hidden');
            document.documentElement.classList.add('overlay-open');
        };
        image.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
        image.src = url;
    });

    /**
     * Edit whatever a file input is holding, and put the result back in it.
     *
     * Every composer in this app reads its photo straight off the input at
     * send time. Writing the edited file back means the editor slots in front
     * of all of them without any of their send paths knowing it exists — and
     * a farmer who backs out simply keeps the photo they picked.
     *
     * @return Promise<boolean> false when the input ends up empty
     */
    window.smEditInto = async (input) => {
        const file = input && input.files && input.files[0];
        if (!file) return false;
        if (!window.DataTransfer) return true;   // nothing to write back with
        const edited = await window.smEditPhoto(file);
        if (!edited) { input.value = ''; return false; }
        const dt = new DataTransfer();
        dt.items.add(edited);
        input.files = dt.files;
        return true;
    };
})();
</script>
@endonce
