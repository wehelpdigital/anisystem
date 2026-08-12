{{-- Reusable drawing pad — object/vector editor. Include once per page.
     Exposes:
       window.openDrawCanvas(onSaveDataUrl [, existingPngUrl])
         → opens the full-screen pad; onSaveDataUrl(pngDataUrl) on save.
     Tools: select (move/resize/delete), pen (freehand), line, arrow, box,
     circle, text, and an object eraser. Works with mouse, pen and touch. --}}
<div class="draw-modal" id="drawModal" aria-hidden="true">
    <div class="draw-shell">
        {{-- Header: the way out and the way to keep the work, both on top where
             a full-screen page puts them — buried at the end of a wrapping
             toolbar they read as just more tools. --}}
        <div class="draw-head">
            <button type="button" class="draw-tool" id="drawBack" aria-label="Back" title="Back — discards the drawing">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <span class="draw-title">Drawing</span>
            <span class="grow"></span>
            <button type="button" class="btn btn-ghost btn-sm draw-cancel" id="drawCancel">Cancel</button>
            {{-- One button, because on a phone two full-width labels in the
                 header left no room for the drawing's name — and the choice
                 between them is a question, not two separate exits. It asks
                 when there is something to ask (see #drawAsk). --}}
            <button type="button" class="draw-save" id="drawSaveBtn" aria-label="Save this drawing" title="Save">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a1 1 0 011-1h9l4 4v10a1 1 0 01-1 1H6a1 1 0 01-1-1V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 4v4h6M8 19v-5h8v5"/></svg>
            </button>
        </div>
        <div class="draw-toolbar">
            <div class="draw-tools" id="drawTools">
                <button type="button" class="draw-tool" data-tool="select" title="Select · move · resize (marquee)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l7 16 2-6 6-2z" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="draw-tool is-active" data-tool="pen" title="Pen (freehand)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20l4-1 10-10-3-3L5 16l-1 4z" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="draw-tool" data-tool="line" title="Line">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20L20 4" stroke-linecap="round"/></svg>
                </button>
                <button type="button" class="draw-tool" data-tool="arrow" title="Arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20L20 4M20 4h-7M20 4v7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="draw-tool" data-tool="rect" title="Box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="6" width="16" height="12" rx="1.5"/></svg>
                </button>
                <button type="button" class="draw-tool" data-tool="ellipse" title="Circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="12" rx="8" ry="7"/></svg>
                </button>
                <button type="button" class="draw-tool" data-tool="text" title="Text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 6h14M12 6v13M9 19h6" stroke-linecap="round"/></svg>
                </button>
                <button type="button" class="draw-tool" data-tool="eraser" title="Eraser (rub out a shape)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15l7-7 6 6-4 4H8l-4-3z" stroke-linejoin="round"/><path d="M8 18h11" stroke-linecap="round"/></svg>
                </button>
            </div>
            <span class="draw-div"></span>
            <div class="draw-colors" id="drawColors"></div>
            <label class="draw-size">
                <span>Size</span>
                <input type="range" id="drawSize" min="1" max="40" value="4">
            </label>
            <span class="draw-div"></span>
            <button type="button" class="draw-tool" id="drawDelete" title="Delete selected (Del)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button type="button" class="draw-tool" id="drawGrid" title="Toggle grid">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9h18M3 15h18M9 3v18M15 3v18" stroke-linecap="round"/></svg>
            </button>
            <button type="button" class="draw-tool" id="drawUndo" title="Undo (Ctrl+Z)" aria-label="Undo" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 14l-4-4 4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 10h8a5 5 0 010 10h-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button type="button" class="draw-tool" id="drawRedo" title="Redo (Ctrl+Shift+Z)" aria-label="Redo" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 14l4-4-4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 10h-8a5 5 0 000 10h3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button type="button" class="draw-tool" id="drawClear" title="Clear all">Clear</button>
        </div>
        <div class="draw-stage" id="drawStage">
            <canvas id="drawCanvas"></canvas>
        </div>
        {{-- Asked inside the pad, not through the app's sheet layer: the pad
             sits above every sheet, and a sheet behind it would be a dialog
             you can hear but not see. --}}
        <div class="draw-ask" id="drawAsk" hidden>
            <div class="draw-ask-card" role="dialog" aria-modal="true" aria-labelledby="drawAskTitle">
                <h4 class="draw-ask-title" id="drawAskTitle">Keep this drawing</h4>
                <button type="button" class="draw-ask-opt" data-save-mode="drawing">
                    <span class="draw-ask-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="draw-ask-name">Save as drawing</span>
                        <span class="draw-ask-hint">Reopen it later and keep changing it</span>
                    </span>
                </button>
                <button type="button" class="draw-ask-opt" data-save-mode="image">
                    <span class="draw-ask-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="draw-ask-name">Save as image</span>
                        <span class="draw-ask-hint">A flat picture — it can be drawn over, not edited</span>
                    </span>
                </button>
                <button type="button" class="btn btn-ghost w-full mt-1" id="drawAskCancel">Cancel</button>
            </div>
        </div>
        <p class="draw-hint" id="drawHint">Pick a tool, colour and size. Hold <b>Shift</b> for a perfect square/circle. Use <b>Select</b> to move, resize or delete anything you drew — including text.</p>
    </div>
</div>

<style>
    /* Above every app overlay: sheets (z-50), toasts (z-70), the note editor's
       emoji popover (z-200). Below the blocking screen-loader (z-9999). */
    .draw-modal { position:fixed; inset:0; z-index:400; display:none; flex-direction:column;
        background:rgb(0 0 0 / .6); }
    .draw-modal.show { display:flex; animation:drawBackdropIn .18s ease both; }
    /* Whole-screen pad (no small modal box). */
    .draw-shell { position:relative; width:100%; height:100%; background:var(--color-white); overflow:hidden;
        display:flex; flex-direction:column; }
    .draw-modal.show .draw-shell { animation:drawShellIn .3s cubic-bezier(.22,1,.36,1) both; transform-origin:center; }
    @keyframes drawBackdropIn { from { opacity:0; } to { opacity:1; } }
    @keyframes drawShellIn { from { opacity:0; transform:scale(.96) translateY(10px); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) {
        .draw-modal.show, .draw-modal.show .draw-shell { animation:none; }
    }
    .draw-head { display:flex; align-items:center; gap:.5rem; padding:.55rem .7rem;
        border-bottom:1px solid var(--color-gray-100); flex-shrink:0; }
    .draw-title { font-family:var(--font-heading); font-weight:800; font-size:.95rem; color:var(--color-gray-900); }
    /* The one action in the header that is not a way out, so it is the only
       thing in there wearing the brand colour. */
    .draw-save { display:inline-flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem;
        border-radius:.7rem; background:var(--color-brand-600); color:#fff; cursor:pointer; flex-shrink:0;
        transition:background .28s cubic-bezier(.22,1,.36,1), transform .1s ease; }
    .draw-save:hover { background:var(--color-brand-700); }
    .draw-save:active { transform:scale(.94); }
    .draw-save svg { width:1.25rem; height:1.25rem; }
    /* The back arrow already discards on a phone; Cancel is the desktop's
       spelling of the same thing. */
    @media (max-width:520px) { .draw-cancel { display:none; } }

    /* Which kind of keeping — asked over the pad it belongs to. */
    .draw-ask { position:absolute; inset:0; z-index:5; display:flex; align-items:flex-end; justify-content:center;
        background:rgb(15 23 42 / .5); animation:drawAskFade .18s ease both; }
    .draw-ask[hidden] { display:none; }
    .draw-ask-card { width:100%; max-width:26rem; background:var(--color-white); border-radius:1rem 1rem 0 0;
        padding:1rem .9rem 1.1rem; display:flex; flex-direction:column; gap:.5rem;
        animation:drawAskUp .28s cubic-bezier(.22,1,.36,1) both; }
    .draw-ask-title { font-family:var(--font-heading); font-weight:800; font-size:1rem; color:var(--color-gray-900);
        margin-bottom:.15rem; }
    .draw-ask-opt { display:flex; align-items:center; gap:.7rem; width:100%; text-align:left; padding:.7rem;
        border:1px solid var(--color-gray-200); border-radius:.85rem; background:var(--color-white); cursor:pointer;
        transition:border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .draw-ask-opt:hover { border-color:var(--color-brand-400); background:var(--color-brand-50); }
    .draw-ask-ico { display:inline-flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem;
        border-radius:.7rem; background:var(--color-brand-50); color:var(--color-brand-700); flex-shrink:0; }
    .draw-ask-ico svg { width:1.3rem; height:1.3rem; }
    .draw-ask-name { display:block; font-weight:800; font-size:.88rem; color:var(--color-gray-900); }
    .draw-ask-hint { display:block; font-size:.72rem; color:var(--color-gray-500); }
    @keyframes drawAskFade { from { opacity:0; } to { opacity:1; } }
    @keyframes drawAskUp { from { transform:translateY(14%); opacity:0; } to { transform:none; opacity:1; } }
    @media (min-width:640px) {
        .draw-ask { align-items:center; }
        .draw-ask-card { border-radius:1rem; }
    }
    @media (prefers-reduced-motion:reduce) {
        .draw-ask, .draw-ask-card { animation:none; }
        .draw-save, .draw-ask-opt { transition:none; }
    }
    .draw-toolbar { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; padding:.55rem .7rem;
        border-bottom:1px solid var(--color-gray-100); flex-shrink:0; }
    .draw-tools { display:flex; gap:.15rem; }
    .draw-div { width:1px; align-self:stretch; background:var(--color-gray-200); margin:.15rem .15rem; }
    .draw-colors { display:flex; gap:.25rem; }
    .draw-colors button { width:1.5rem; height:1.5rem; border-radius:999px; border:2px solid transparent; cursor:pointer; }
    .draw-colors button.is-active { border-color:var(--color-gray-900); transform:scale(1.12); }
    .draw-size { display:flex; align-items:center; gap:.35rem; font-size:.72rem; color:var(--color-gray-500); font-weight:700; }
    .draw-size input { width:5.5rem; }
    .draw-tool { display:inline-flex; align-items:center; justify-content:center; min-width:2rem; height:2rem;
        font-size:.85rem; font-weight:700; padding:0 .45rem; border-radius:.5rem;
        background:var(--color-gray-50); color:var(--color-gray-700); cursor:pointer;
        transition:background .15s ease, color .15s ease, transform .1s ease; }
    .draw-tool svg { width:1.2rem; height:1.2rem; }
    .draw-tool:hover { background:var(--color-gray-100); }
    .draw-tool:active { transform:scale(.92); }
    /* Nothing to undo yet reads as greyed-out, not as a missing button: the
       pair keeps its place in the row so the toolbar does not reflow the
       moment you draw your first stroke. */
    .draw-tool:disabled { opacity:.35; cursor:default; }
    .draw-tool:disabled:hover { background:var(--color-gray-50); }
    .draw-tool:disabled:active { transform:none; }
    .draw-tool.is-active { background:var(--color-brand-100); color:var(--color-brand-800); }
    .draw-stage { flex:1; min-height:0; background:#eef1f4; overflow:hidden; touch-action:none;
        display:flex; align-items:center; justify-content:center; padding:.75rem; }
    /* Phones: the sheet goes edge to edge — the margin was dead space. */
    @media (max-width: 767px) { .draw-stage { padding:0; } #drawCanvas { border-radius:0; box-shadow:none; } }
    #drawCanvas { display:block; background:#fff; box-shadow:0 6px 24px -8px rgb(0 0 0 / .3);
        border-radius:.35rem; touch-action:none; cursor:crosshair; }
    .draw-hint { font-size:.72rem; color:var(--color-gray-400); padding:.4rem .7rem; flex-shrink:0; }
    .draw-hint b { color:var(--color-gray-600); }
    html.dark .draw-shell { background:#151b12; }
    html.dark .draw-toolbar { border-color:#2b3a1c; }
    html.dark .draw-head { border-color:#2b3a1c; }
    html.dark .draw-title { color:#e6eddd; }
    html.dark .draw-ask-card { background:#141a10; }
    html.dark .draw-ask-title, html.dark .draw-ask-name { color:#e6eddd; }
    html.dark .draw-ask-opt { background:#141a10; border-color:#2b3a1c; }
    html.dark .draw-ask-opt:hover { background:#1c2416; }
    html.dark .draw-stage { background:#0f130c; }
    html.dark .draw-tool { background:#1c2416; color:#cdd8c0; }
    html.dark .draw-tool:hover { background:#243019; }
    html.dark .draw-tool:disabled:hover { background:#1c2416; }
    html.dark .draw-div { background:#2b3a1c; }
</style>

<script>
(function drawPad() {
    if (window.__drawPadBound) return;
    window.__drawPadBound = true;

    const COLORS = ['#111827', '#dc2626', '#ea580c', '#ca8a04', '#16a34a', '#2563eb', '#7c3aed', '#db2777', '#ffffff'];
    const modal = document.getElementById('drawModal');
    const canvas = document.getElementById('drawCanvas');
    const stage = document.getElementById('drawStage');
    const ctx = canvas.getContext('2d');
    const sizeInput = document.getElementById('drawSize');
    const colorsWrap = document.getElementById('drawColors');
    const toolsWrap = document.getElementById('drawTools');
    const hint = document.getElementById('drawHint');

    const W0 = 1280, H0 = 900;        // the space existing drawings were made in
    let W = W0, H = H0;               // internal resolution → crisp export
    const HANDLE = 9;                 // resize-handle half-size (canvas units)

    let color = '#111827';
    let tool = 'pen';
    let onSave = null;
    let uid = 1;
    let gridOn = false;               // show a grid guide (not saved into the PNG)
    let exporting = false;            // suppresses the grid while capturing the export
    const GRID = 40;                  // grid spacing in canvas units
    const nextId = () => uid++;

    let objects = [];                 // the scene
    let selected = new Set();         // selected object ids
    const undoStack = [];             // JSON snapshots, oldest first
    const redoStack = [];             // snapshots undone, newest first

    // gesture state
    let mode = null;                  // 'draw' | 'move' | 'resize' | 'erase' | 'marquee'
    let cur = null;                   // object being drawn
    let drawOrigin = null;            // origin for rect/ellipse
    let moveStart = null;
    let resizeHandle = null;
    let startBBox = null;             // bbox at resize start
    let startClones = null;           // id → clone at gesture start
    let marquee = null;               // {x,y,w,h}

    const strokeW = () => parseInt(sizeInput.value, 10) || 4;
    const fontPx = () => Math.max(14, strokeW() * 4);
    const clone = (o) => JSON.parse(JSON.stringify(o));

    /* ---------- palette + tools ---------- */
    COLORS.forEach((c, i) => {
        const b = document.createElement('button');
        b.type = 'button'; b.style.background = c; b.setAttribute('data-color', c);
        if (i === 0) b.classList.add('is-active');
        if (c === '#ffffff') b.style.border = '2px solid #d1d5db';
        colorsWrap.appendChild(b);
    });
    colorsWrap.addEventListener('click', (e) => {
        const b = e.target.closest('[data-color]');
        if (!b) return;
        color = b.getAttribute('data-color');
        colorsWrap.querySelectorAll('button').forEach((x) => x.classList.remove('is-active'));
        b.classList.add('is-active');
        // Recolour a live selection so the swatch also acts as a "fill".
        if (selected.size) { pushUndo(); objects.forEach((o) => { if (selected.has(o.id)) o.color = color; }); render(); }
    });
    toolsWrap.addEventListener('click', (e) => {
        const b = e.target.closest('[data-tool]');
        if (!b) return;
        setTool(b.getAttribute('data-tool'));
    });
    function setTool(t) {
        tool = t;
        toolsWrap.querySelectorAll('[data-tool]').forEach((x) => x.classList.toggle('is-active', x.getAttribute('data-tool') === t));
        if (t !== 'select') { selected.clear(); render(); }
        canvas.style.cursor = t === 'select' ? 'default' : (t === 'eraser' ? 'cell' : 'crosshair');
    }

    /* ---------- geometry helpers ---------- */
    function bbox(o) {
        if (o.type === 'path') {
            let xs = o.points.map((p) => p.x), ys = o.points.map((p) => p.y);
            const pad = (o.width || 2) / 2;
            return norm(Math.min(...xs) - pad, Math.min(...ys) - pad, Math.max(...xs) + pad, Math.max(...ys) + pad);
        }
        if (o.type === 'line' || o.type === 'arrow') {
            const pad = (o.width || 2) / 2 + 4;
            return norm(Math.min(o.x1, o.x2) - pad, Math.min(o.y1, o.y2) - pad, Math.max(o.x1, o.x2) + pad, Math.max(o.y1, o.y2) + pad);
        }
        if (o.type === 'text') {
            ctx.font = 'bold ' + o.size + 'px sans-serif';
            const w = ctx.measureText(o.text).width;
            return { x: o.x, y: o.y - o.size, w: Math.max(w, 8), h: o.size * 1.3 };
        }
        // rect / ellipse
        return norm(o.x, o.y, o.x + o.w, o.y + o.h);
    }
    function norm(x1, y1, x2, y2) { return { x: Math.min(x1, x2), y: Math.min(y1, y2), w: Math.abs(x2 - x1), h: Math.abs(y2 - y1) }; }
    function inBox(b, x, y, pad = 6) { return x >= b.x - pad && x <= b.x + b.w + pad && y >= b.y - pad && y <= b.y + b.h + pad; }
    function boxesHit(a, b) { return !(b.x > a.x + a.w || b.x + b.w < a.x || b.y > a.y + a.h || b.y + b.h < a.y); }

    // Map an object's geometry from one bbox to another (move + resize unified).
    function remap(o, from, to) {
        const sx = from.w ? to.w / from.w : 1, sy = from.h ? to.h / from.h : 1;
        const mx = (x) => to.x + (x - from.x) * sx;
        const my = (y) => to.y + (y - from.y) * sy;
        if (o.type === 'path') o.points.forEach((p) => { p.x = mx(p.x); p.y = my(p.y); });
        else if (o.type === 'line' || o.type === 'arrow') { o.x1 = mx(o.x1); o.y1 = my(o.y1); o.x2 = mx(o.x2); o.y2 = my(o.y2); }
        else if (o.type === 'text') { const b = bbox(o); o.x = mx(o.x); o.size = Math.max(6, o.size * sy); o.y = my(b.y) + o.size; }
        else { const nx = mx(o.x), ny = my(o.y); o.x = nx; o.y = ny; o.w *= sx; o.h *= sy; }
    }

    /* ---------- rendering ---------- */
    function drawObject(o) {
        ctx.strokeStyle = o.color; ctx.fillStyle = o.color;
        ctx.lineWidth = o.width || 2; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
        if (o.type === 'path') {
            ctx.beginPath();
            o.points.forEach((p, i) => i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y));
            if (o.points.length === 1) { ctx.lineTo(o.points[0].x + 0.1, o.points[0].y); }
            ctx.stroke();
        } else if (o.type === 'line' || o.type === 'arrow') {
            ctx.beginPath(); ctx.moveTo(o.x1, o.y1); ctx.lineTo(o.x2, o.y2); ctx.stroke();
            if (o.type === 'arrow') {
                const a = Math.atan2(o.y2 - o.y1, o.x2 - o.x1), len = 10 + (o.width || 2) * 2.2;
                ctx.beginPath();
                ctx.moveTo(o.x2, o.y2); ctx.lineTo(o.x2 - len * Math.cos(a - 0.4), o.y2 - len * Math.sin(a - 0.4));
                ctx.moveTo(o.x2, o.y2); ctx.lineTo(o.x2 - len * Math.cos(a + 0.4), o.y2 - len * Math.sin(a + 0.4));
                ctx.stroke();
            }
        } else if (o.type === 'rect') {
            const b = norm(o.x, o.y, o.x + o.w, o.y + o.h);
            ctx.strokeRect(b.x, b.y, b.w, b.h);
        } else if (o.type === 'ellipse') {
            const b = norm(o.x, o.y, o.x + o.w, o.y + o.h);
            ctx.beginPath(); ctx.ellipse(b.x + b.w / 2, b.y + b.h / 2, Math.max(b.w / 2, 1), Math.max(b.h / 2, 1), 0, 0, 7); ctx.stroke();
        } else if (o.type === 'text') {
            ctx.font = 'bold ' + o.size + 'px sans-serif'; ctx.textBaseline = 'alphabetic';
            ctx.fillText(o.text, o.x, o.y);
        }
    }
    function drawSelection() {
        if (!selected.size) return;
        ctx.save();
        ctx.strokeStyle = '#2563eb'; ctx.lineWidth = 1.5; ctx.setLineDash([6, 4]);
        let single = null;
        objects.forEach((o) => { if (selected.has(o.id)) { const b = bbox(o); ctx.strokeRect(b.x, b.y, b.w, b.h); single = o; } });
        ctx.setLineDash([]);
        if (selected.size === 1 && single) {
            const b = bbox(single);
            ctx.fillStyle = '#2563eb';
            handlePoints(b).forEach((h) => { ctx.fillRect(h.x - HANDLE / 2, h.y - HANDLE / 2, HANDLE, HANDLE); });
        }
        ctx.restore();
    }
    function handlePoints(b) {
        const mx = b.x + b.w / 2, my = b.y + b.h / 2;
        return [
            { n: 'nw', x: b.x, y: b.y }, { n: 'n', x: mx, y: b.y }, { n: 'ne', x: b.x + b.w, y: b.y },
            { n: 'e', x: b.x + b.w, y: my }, { n: 'se', x: b.x + b.w, y: b.y + b.h },
            { n: 's', x: mx, y: b.y + b.h }, { n: 'sw', x: b.x, y: b.y + b.h }, { n: 'w', x: b.x, y: my },
        ];
    }
    function drawGrid() {
        ctx.save();
        ctx.strokeStyle = 'rgba(37, 99, 235, .12)'; ctx.lineWidth = 1;
        ctx.beginPath();
        for (let x = GRID; x < W; x += GRID) { ctx.moveTo(x + .5, 0); ctx.lineTo(x + .5, H); }
        for (let y = GRID; y < H; y += GRID) { ctx.moveTo(0, y + .5); ctx.lineTo(W, y + .5); }
        ctx.stroke();
        ctx.restore();
    }
    function render() {
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, W, H);
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, W, H);
        if (gridOn && !exporting) drawGrid();
        objects.forEach(drawObject);
        drawSelection();
        if (marquee) {
            ctx.save(); ctx.strokeStyle = '#2563eb'; ctx.fillStyle = 'rgba(37,99,235,.08)';
            ctx.setLineDash([5, 3]); ctx.fillRect(marquee.x, marquee.y, marquee.w, marquee.h);
            ctx.strokeRect(marquee.x, marquee.y, marquee.w, marquee.h); ctx.restore();
        }
    }

    /* ---------- hit testing ---------- */
    function hitObject(x, y) {
        for (let i = objects.length - 1; i >= 0; i--) { if (inBox(bbox(objects[i]), x, y)) return objects[i]; }
        return null;
    }
    function hitHandle(x, y) {
        if (selected.size !== 1) return null;
        const o = objects.find((k) => selected.has(k.id));
        if (!o) return null;
        for (const h of handlePoints(bbox(o))) { if (Math.abs(x - h.x) <= HANDLE && Math.abs(y - h.y) <= HANDLE) return h.n; }
        return null;
    }
    const CURSORS = { nw: 'nwse-resize', se: 'nwse-resize', ne: 'nesw-resize', sw: 'nesw-resize', n: 'ns-resize', s: 'ns-resize', e: 'ew-resize', w: 'ew-resize' };

    /* ---------- undo ---------- */
    /* ---------- history ----------
       Every change snapshots the scene first. A new change after undoing is a
       new branch, so the redo trail is dropped then — keeping it would let a
       redo paste back work that no longer follows from what is on screen. */
    function pushUndo() {
        undoStack.push(JSON.stringify(objects));
        if (undoStack.length > 60) undoStack.shift();
        redoStack.length = 0;
        paintHistory();
    }
    function paintHistory() {
        const u = document.getElementById('drawUndo'), r = document.getElementById('drawRedo');
        if (u) u.disabled = !undoStack.length;
        if (r) r.disabled = !redoStack.length;
    }
    function step(from, to) {
        if (!from.length) return;
        to.push(JSON.stringify(objects));
        objects = JSON.parse(from.pop());
        // Ids come back with the scene, but the counter does not — without
        // this a new shape can be handed an id an older one already owns, and
        // selecting one would select both.
        uid = Math.max(uid, ...objects.map((o) => (+o.id || 0) + 1), 1);
        selected.clear(); cur = null; mode = null; marquee = null;
        paintHistory(); render();
    }
    const undo = () => step(undoStack, redoStack);
    const redo = () => step(redoStack, undoStack);

    /* ---------- pointer input ---------- */
    function pos(e) {
        const r = canvas.getBoundingClientRect();
        const sx = W / r.width, sy = H / r.height;
        return { x: (e.clientX - r.left) * sx, y: (e.clientY - r.top) * sy };
    }
    canvas.addEventListener('pointerdown', (e) => {
        e.preventDefault(); canvas.setPointerCapture?.(e.pointerId);
        const p = pos(e);

        if (tool === 'select') {
            const h = hitHandle(p.x, p.y);
            if (h) { const o = objects.find((k) => selected.has(k.id)); pushUndo(); mode = 'resize'; resizeHandle = h; startBBox = bbox(o); startClones = { [o.id]: clone(o) }; return; }
            const o = hitObject(p.x, p.y);
            if (o) {
                if (!e.shiftKey && !selected.has(o.id)) selected = new Set([o.id]);
                else selected.add(o.id);
                pushUndo(); mode = 'move'; moveStart = p;
                startClones = {}; objects.forEach((k) => { if (selected.has(k.id)) startClones[k.id] = { obj: k, box: bbox(k) }; });
            } else { selected.clear(); mode = 'marquee'; drawOrigin = p; marquee = { x: p.x, y: p.y, w: 0, h: 0 }; }
            render(); return;
        }
        if (tool === 'eraser') { pushUndo(); mode = 'erase'; eraseAt(p); return; }
        if (tool === 'text') {
            const t = prompt('Text to add:');
            if (t && t.trim()) { pushUndo(); const o = { id: nextId(), type: 'text', color, size: fontPx(), x: p.x, y: p.y + fontPx() * 0.4, text: t.trim() }; objects.push(o); selected = new Set([o.id]); setTool('select'); }
            render(); return;
        }
        // freehand / shapes
        pushUndo(); mode = 'draw';
        if (tool === 'pen') cur = { id: nextId(), type: 'path', color, width: strokeW(), points: [p] };
        else if (tool === 'line' || tool === 'arrow') cur = { id: nextId(), type: tool, color, width: strokeW(), x1: p.x, y1: p.y, x2: p.x, y2: p.y };
        else { drawOrigin = p; cur = { id: nextId(), type: tool, color, width: strokeW(), x: p.x, y: p.y, w: 0, h: 0 }; }
        objects.push(cur); render();
    });
    canvas.addEventListener('pointermove', (e) => {
        const p = pos(e);
        if (!mode) {
            if (tool === 'select') { const h = hitHandle(p.x, p.y); canvas.style.cursor = h ? CURSORS[h] : (hitObject(p.x, p.y) ? 'move' : 'default'); }
            return;
        }
        e.preventDefault();
        if (mode === 'draw') {
            if (cur.type === 'path') cur.points.push(p);
            else if (cur.type === 'line' || cur.type === 'arrow') { cur.x2 = p.x; cur.y2 = p.y; }
            else {
                let w = p.x - drawOrigin.x, h = p.y - drawOrigin.y;
                // Hold Shift to keep box/circle a perfect square/circle.
                if (e.shiftKey) { const m = Math.max(Math.abs(w), Math.abs(h)); w = (w < 0 ? -m : m); h = (h < 0 ? -m : m); }
                cur.x = drawOrigin.x; cur.y = drawOrigin.y; cur.w = w; cur.h = h;
            }
        } else if (mode === 'move') {
            const dx = p.x - moveStart.x, dy = p.y - moveStart.y;
            Object.values(startClones).forEach(({ obj, box }) => { remap(obj, box, { x: box.x + dx, y: box.y + dy, w: box.w, h: box.h }); });
            // keep clones' box in sync so successive moves compose
            Object.keys(startClones).forEach((id) => { startClones[id].box = bbox(startClones[id].obj); });
            moveStart = p;
        } else if (mode === 'resize') {
            const o = objects.find((k) => selected.has(k.id)); if (!o) return;
            let { x, y, w, h } = startBBox; let l = x, t = y, r = x + w, bt = y + h;
            if (resizeHandle.includes('w')) l = p.x; if (resizeHandle.includes('e')) r = p.x;
            if (resizeHandle.includes('n')) t = p.y; if (resizeHandle.includes('s')) bt = p.y;
            const to = norm(l, t, r, bt); to.w = Math.max(to.w, 6); to.h = Math.max(to.h, 6);
            const fresh = clone(startClones[o.id]); remap(fresh, startBBox, to);
            Object.assign(o, fresh);
        } else if (mode === 'erase') { eraseAt(p); }
        else if (mode === 'marquee') { marquee = norm(drawOrigin.x, drawOrigin.y, p.x, p.y); }
        render();
    });
    function endGesture() {
        if (mode === 'draw' && cur) {
            const b = bbox(cur);
            const trivial = (cur.type === 'path' && cur.points.length < 2 && b.w < 3 && b.h < 3) ||
                ((cur.type === 'rect' || cur.type === 'ellipse') && Math.abs(cur.w) < 3 && Math.abs(cur.h) < 3) ||
                ((cur.type === 'line' || cur.type === 'arrow') && Math.hypot(cur.x2 - cur.x1, cur.y2 - cur.y1) < 3);
            if (trivial) objects.pop();
        }
        if (mode === 'marquee' && marquee) {
            selected = new Set(objects.filter((o) => boxesHit(marquee, bbox(o))).map((o) => o.id));
            marquee = null;
        }
        mode = null; cur = null; startClones = null; render();
    }
    canvas.addEventListener('pointerup', endGesture);
    canvas.addEventListener('pointercancel', endGesture);

    function eraseAt(p) {
        const o = hitObject(p.x, p.y);
        if (o) { objects = objects.filter((k) => k !== o); selected.delete(o.id); render(); }
    }
    function deleteSelected() {
        if (!selected.size) return;
        pushUndo(); objects = objects.filter((o) => !selected.has(o.id)); selected.clear(); render();
    }

    /* ---------- toolbar buttons ---------- */
    document.getElementById('drawDelete').addEventListener('click', deleteSelected);
    document.getElementById('drawUndo').addEventListener('click', undo);
    document.getElementById('drawRedo').addEventListener('click', redo);
    document.getElementById('drawClear').addEventListener('click', () => { pushUndo(); objects = []; selected.clear(); render(); });
    document.getElementById('drawGrid').addEventListener('click', (e) => {
        gridOn = !gridOn;
        e.currentTarget.classList.toggle('is-active', gridOn);
        render();
    });
    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('show')) return;
        if ((e.key === 'Delete' || e.key === 'Backspace') && selected.size) { e.preventDefault(); deleteSelected(); }
        // Both spellings of redo: Ctrl+Shift+Z everywhere, Ctrl+Y on Windows.
        if ((e.ctrlKey || e.metaKey) && (e.key === 'z' || e.key === 'Z')) { e.preventDefault(); e.shiftKey ? redo() : undo(); }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || e.key === 'Y')) { e.preventDefault(); redo(); }
        if (e.key === 'Escape') { if (askOpen()) showAsk(false); else close(); }
    });

    /* ---------- fit + open/close ---------- */
    function fitStage() {
        const cs = getComputedStyle(stage);
        const availW = stage.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        const availH = stage.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
        if (availW <= 0 || availH <= 0) return;
        const scale = Math.min(availW / W, availH / H);
        canvas.style.width = Math.round(W * scale) + 'px';
        canvas.style.height = Math.round(H * scale) + 'px';
    }
    window.addEventListener('resize', () => { if (modal.classList.contains('show')) fitStage(); });

    function close() {
        showAsk(false);
        window.unregisterOverlay?.('drawPad');
        modal.classList.remove('show'); modal.setAttribute('aria-hidden', 'true');
        onSave = null; document.body.style.overflow = '';
    }
    document.getElementById('drawCancel').addEventListener('click', close);
    document.getElementById('drawBack').addEventListener('click', close);
    function exportPng() {
        selected.clear(); marquee = null;
        // Capture without the grid guide, then restore the on-screen view.
        exporting = true; render();
        const data = canvas.toDataURL('image/png');
        exporting = false; render();
        return data;
    }
    /* Two kinds of keeping, one button. "Drawing" sends the strokes with the
       picture, so reopening gives back something that can still be changed
       rather than a photograph of it; "image" sends the picture alone. */
    const ask = document.getElementById('drawAsk');
    let editableAllowed = false;
    const askOpen = () => !ask.hidden;
    function showAsk(on) {
        ask.hidden = !on;
        // Back answers the question by dismissing it, the way it dismisses any
        // other overlay, rather than walking out of the pad.
        if (on) window.registerOverlay?.('drawAsk', () => showAsk(false));
        else window.unregisterOverlay?.('drawAsk');
    }
    function saveAs(mode) {
        const data = exportPng();
        if (onSave) onSave(data, mode === 'drawing' ? JSON.parse(JSON.stringify(objects || [])) : null);
        close();
    }
    document.getElementById('drawSaveBtn').addEventListener('click', () => {
        // Nothing to ask when only one kind is on offer — a dialog with one
        // real answer is a tap for its own sake.
        if (editableAllowed) showAsk(true);
        else saveAs('image');
    });
    ask.addEventListener('click', (e) => {
        const opt = e.target.closest('[data-save-mode]');
        if (opt) { saveAs(opt.getAttribute('data-save-mode')); return; }
        // The backdrop and Cancel close the question, not the drawing.
        if (e.target === ask || e.target.closest('#drawAskCancel')) showAsk(false);
    });

    function reset(existingUrl, existingObjects) {
        objects = []; selected.clear(); undoStack.length = 0; redoStack.length = 0; marquee = null; mode = null; cur = null; uid = 1;
        paintHistory();
        // A new drawing takes the aspect of the screen it opens on, so the
        // white sheet fills the stage instead of letterboxing inside it. An
        // existing drawing keeps the space it was made in — reshaping that
        // would stretch what the user already drew.
        if (existingUrl) { W = W0; H = H0; }
        else {
            const aw = Math.max(1, stage.clientWidth), ah = Math.max(1, stage.clientHeight);
            W = W0; H = Math.min(2600, Math.max(520, Math.round(W0 * ah / aw)));
        }
        canvas.width = W; canvas.height = H;
        setTool('pen'); render();
        if (existingObjects && existingObjects.length) {
            // Real strokes, so each one can be selected, moved and undone again
            // — unlike a PNG backdrop, which can only be drawn over.
            objects = JSON.parse(JSON.stringify(existingObjects));
            uid = objects.reduce((m, o) => Math.max(m, (o && o.id) || 0), 0) + 1;
            render();
        } else if (existingUrl) {
            const img = new Image(); img.crossOrigin = 'anonymous';
            img.onload = () => { ctx.drawImage(img, 0, 0, W, H); try { objects = []; } catch (_) {} render(); };
            img.src = existingUrl; // note: loaded as a backdrop only (not an editable object)
        }
    }

    /**
     * openDrawCanvas(cb, existingPngUrl, opts)
     *   cb(dataUrl, objects)  objects is null unless "Save as drawing" was used
     *   opts.objects          strokes from a previous drawing save, to reopen
     *   opts.editable         offer the "Save as drawing" button
     */
    window.openDrawCanvas = function (cb, existingUrl, opts) {
        opts = opts || {};
        onSave = cb || null;
        editableAllowed = !!opts.editable;
        showAsk(false);
        // Re-parent to <body> so `position:fixed` is relative to the viewport —
        // never trapped/cramped inside a transformed ancestor (the notes module
        // wrapper, an open sheet, etc.). Also sidesteps any duplicate #drawModal.
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        // A full-screen pad is the clearest case of all: Back should shut it,
        // not the page underneath it.
        window.registerOverlay?.('drawPad', close);
        document.body.style.overflow = 'hidden';
        reset(existingUrl, opts.objects);
        requestAnimationFrame(fitStage);
    };
})();
</script>
