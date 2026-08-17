{{-- Reusable drawing pad — object/vector editor. Include once per page.
     Exposes:
       window.openDrawCanvas(onSaveDataUrl [, existingPngUrl])
         → opens the full-screen pad; onSaveDataUrl(pngDataUrl) on save.
     Tools: select (move/resize/delete), pen (freehand), line, arrow, box,
     circle, text, and an object eraser. Works with mouse, pen and touch.
     A drawing can be several pages; they travel with the strokes, and a
     drawing saved before pages existed still opens as its one page. --}}
<div class="draw-modal" id="drawModal" aria-hidden="true">
    <div class="draw-shell">
        {{-- Header: the way out and the way to keep the work, both on top where
             a full-screen page puts them — buried at the end of a wrapping
             toolbar they read as just more tools. --}}
        <div class="draw-head">
            <button type="button" class="draw-tool" id="drawBack" aria-label="Back" title="Back — discards the drawing">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <span class="draw-title" id="drawTitleText">Drawing</span>
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
                <button type="button" class="draw-tool" id="drawInsertImg" title="Insert a picture (it becomes a shape: move, resize, rotate, draw over it)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6" stroke-linejoin="round"/><circle cx="9" cy="9" r="1.3"/></svg>
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
                {{-- Only when you opened something that already exists: the
                     common answer is "yes, this is that drawing, changed" —
                     which used to be indistinguishable from starting a new
                     one. --}}
                <button type="button" class="draw-ask-opt" data-save-mode="overwrite" id="drawAskOverwrite" hidden>
                    <span class="draw-ask-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a1 1 0 011-1h9l4 4v10a1 1 0 01-1 1H6a1 1 0 01-1-1V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 4v4h6M8 19v-5h8v5"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="draw-ask-name">Save over this drawing</span>
                        <span class="draw-ask-hint" id="drawAskOverwriteHint">Replaces the one you opened</span>
                    </span>
                </button>
                <button type="button" class="draw-ask-opt" data-save-mode="drawing">
                    <span class="draw-ask-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="draw-ask-name" id="drawAskNewName">Save as drawing</span>
                        <span class="draw-ask-hint" id="drawAskNewHint">Reopen it later and keep changing it</span>
                    </span>
                </button>
                <button type="button" class="draw-ask-opt" data-save-mode="image">
                    <span class="draw-ask-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="draw-ask-name">Save as image</span>
                        <span class="draw-ask-hint" id="drawAskImageHint">A flat picture — it can be drawn over, not edited</span>
                    </span>
                </button>
                <button type="button" class="btn btn-ghost w-full mt-1" id="drawAskCancel">Cancel</button>
            </div>
        </div>
        {{-- Words to put on the sheet. Same reasoning as the save question: it
             lives inside the pad, because anything the app puts on its sheet
             layer opens underneath a full-screen pad. --}}
        <div class="draw-ask" id="drawTextAsk" hidden>
            <div class="draw-ask-card" role="dialog" aria-modal="true" aria-labelledby="drawTextAskTitle">
                <h4 class="draw-ask-title" id="drawTextAskTitle">Add text</h4>
                <input type="text" id="drawTextInput" class="draw-text-input" maxlength="200"
                       autocomplete="off" placeholder="What should it say?">
                <p class="draw-ask-hint">It lands where you tapped, in the colour and size you picked.</p>
                <div class="draw-ask-row">
                    <button type="button" class="btn btn-ghost" id="drawTextCancel">Cancel</button>
                    <button type="button" class="btn btn-primary" id="drawTextOk">Add it</button>
                </div>
            </div>
        </div>
        {{-- Pages. One drawing is often more than one sheet — a plan and then
             the detail of it — and before this the only way to get a second
             sheet was to save and start again, which filed them as two
             unrelated drawings. --}}
        <div class="draw-pages" id="drawPages">
            <span class="draw-pages-label" id="drawPagesLabel">Page 1 of 1</span>
            <button type="button" class="draw-tool" id="drawPagePrev" title="Previous page" aria-label="Previous page">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="draw-page-chips" id="drawPageChips"></div>
            <button type="button" class="draw-tool" id="drawPageNext" title="Next page" aria-label="Next page">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <span class="draw-div"></span>
            <button type="button" class="draw-tool" id="drawPageAdd" title="Add a page after this one">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="draw-page-add-label">Page</span>
            </button>
            <button type="button" class="draw-tool" id="drawPageDel" title="Remove this page" aria-label="Remove this page">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <p class="draw-hint" id="drawHint">Pick a tool, colour and size. Hold <b>Shift</b> for a perfect square/circle. Use <b>Select</b> to move, resize or delete anything you drew — including text and inserted pictures, which also get a rotate knob. The <b>+</b> above adds another page to this drawing.</p>
        <input type="file" id="drawImgInput" accept="image/jpeg,image/png,image/webp" class="hidden">
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
    .draw-ask-row { display:flex; gap:.5rem; margin-top:.25rem; }
    .draw-ask-row .btn { flex:1; }
    .draw-text-input { width:100%; border:1px solid var(--color-gray-300); border-radius:.7rem;
        padding:.65rem .75rem; font-size:.95rem; color:var(--color-gray-900); background:var(--color-white); }
    .draw-text-input:focus { outline:none; border-color:var(--color-brand-500);
        box-shadow:0 0 0 2px var(--color-brand-200); }
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
    .draw-tool.is-active,
    html.dark .draw-tool.is-active { background:var(--color-brand-100); color:var(--color-brand-800); }
    .draw-stage { flex:1; min-height:0; background:#eef1f4; overflow:hidden; touch-action:none;
        display:flex; align-items:center; justify-content:center; padding:.75rem; }
    /* Phones: the sheet goes edge to edge — the margin was dead space. */
    @media (max-width: 767px) { .draw-stage { padding:0; } #drawCanvas { border-radius:0; box-shadow:none; } }
    #drawCanvas { display:block; background:#fff; box-shadow:0 6px 24px -8px rgb(0 0 0 / .3);
        border-radius:.35rem; touch-action:none; cursor:crosshair; }
    .draw-hint { font-size:.72rem; color:var(--color-gray-400); padding:.4rem .7rem; flex-shrink:0; }
    .draw-hint b { color:var(--color-gray-600); }
    /* The page strip sits under the sheet, where a pile of paper would be. */
    .draw-pages { display:flex; align-items:center; gap:.35rem; padding:.4rem .7rem; flex-shrink:0;
        border-top:1px solid var(--color-gray-100); overflow-x:auto; }
    .draw-pages-label { font-size:.72rem; font-weight:700; color:var(--color-gray-500); flex-shrink:0;
        margin-right:.15rem; }
    .draw-page-chips { display:flex; gap:.2rem; }
    .draw-page { display:inline-flex; align-items:center; justify-content:center; min-width:1.9rem; height:1.9rem;
        padding:0 .35rem; border-radius:.5rem; font-size:.75rem; font-weight:700; cursor:pointer;
        background:var(--color-gray-50); color:var(--color-gray-600);
        transition:background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .draw-page:hover { background:var(--color-gray-100); }
    .draw-page.is-active { background:var(--color-brand-100); color:var(--color-brand-800); }
    .draw-page-add-label { font-size:.72rem; margin-left:.2rem; }
    /* Phones: the strip is the one place the toolbar cannot borrow room from,
       so the "+ Page" button loses its word rather than its target size. */
    @media (max-width:480px) {
        .draw-pages-label { display:none; }
        .draw-page-add-label { display:none; }
    }
    @media (prefers-reduced-motion:reduce) { .draw-page { transition:none; } }
    /* Toasts (z-250) and the app's confirm sheet (z-50) both live below a pad
       at z-400 — a warning nobody can see is not a warning. While the pad is
       up its own messages come over it, and the shared confirm is lifted for
       exactly as long as it is asking (any other sheet stays where it is:
       the note editor is often open behind the pad, and it belongs behind). */
    html.draw-pad-open #toast-stack { z-index:450; }
    /* Both halves of the lift are spelled with .draw-pad-open too, so a class
       left behind by some future accident cannot put the shared backdrop over
       every sheet in an app that has no pad up. */
    html.draw-pad-open.draw-pad-asking .sheet-backdrop.is-open { z-index:460; }
    html.draw-pad-open.draw-pad-asking #confirm-sheet { z-index:470; }
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
    html.dark .draw-pages { border-color:#2b3a1c; }
    html.dark .draw-page { background:#1c2416; color:#cdd8c0; }
    html.dark .draw-page:hover { background:#243019; }
    html.dark .draw-text-input { background:#141a10; border-color:#2b3a1c; color:#e6eddd; }
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
    let overwriteLabel = '';   // set per open: the drawing being replaced
    let backdropFailed = false;   // the picture we were asked to edit never loaded
    let uid = 1;
    let gridOn = false;               // show a grid guide (not saved into the PNG)
    let exporting = false;            // suppresses the grid while capturing the export
    const GRID = 40;                  // grid spacing in canvas units
    const nextId = () => uid++;

    let objects = [];                 // the scene on the page in front of you
    let selected = new Set();         // selected object ids
    const undoStack = [];             // JSON snapshots, oldest first
    const redoStack = [];             // snapshots undone, newest first

    /* Pages. Only the page you are looking at is live in `objects` and the two
       history stacks; the others wait here holding their own, so stepping to
       page 2 and pressing undo does not quietly rub out page 1. */
    let pages = [blankPage()];
    let pageIndex = 0;
    const MAX_PAGES = 12;             // past this a "drawing" is a document
    const PAGE_GAP = 28;              // white between pages in a stacked export
    // What a stacked export is allowed to be. The budget is in pixels, not in
    // height, because the height is what a browser refuses to allocate — and
    // capping the height alone shrank the width with it (see exportPng).
    const EXPORT_MAX_PX = 15e6;       // the most a canvas will still hand back a PNG for
    const EXPORT_MAX_H = 16000;       // and a height no browser argues with
    function blankPage() { return { objects: [], undo: [], redo: [] }; }

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
        if (o.type === 'image') {
            // The box the SCREEN sees: the rotated picture's envelope, so
            // selection, marquee and hit-testing follow the pixels around.
            return (o.rot || 0) ? imageAabb(o) : norm(o.x, o.y, o.x + o.w, o.y + o.h);
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
        else if (o.type === 'image') {
            // Scale about the centre and carry the rotation along — mapping
            // the corners like a rect would shear a turned picture.
            const cx = mx(o.x + o.w / 2), cy = my(o.y + o.h / 2);
            o.w = Math.max(12, o.w * sx); o.h = Math.max(12, o.h * sy);
            o.x = cx - o.w / 2; o.y = cy - o.h / 2;
        }
        else { const nx = mx(o.x), ny = my(o.y); o.x = nx; o.y = ny; o.w *= sx; o.h *= sy; }
    }

    /* ---------- inserted pictures ----------
       A picture is an object like any other: it lives in `objects`, so undo,
       marquee, move, resize and delete all already know it. Its pixels ride
       INSIDE the strokes as a data URL — shrunk hard first — so an editable
       drawing reopens whole on any device with no second fetch to lose. The
       Image elements for painting are cached here by that data URL, because
       JSON snapshots (undo) can clone the object freely but the decoded
       bitmap must not be decoded again every frame. */
    const imgCache = new Map();       // src -> { el, ready, promise }
    function padImage(src) {
        let c = imgCache.get(src);
        if (c) return c;
        const el = new Image();
        c = { el, ready: false };
        c.promise = new Promise((res) => {
            el.onload = () => { c.ready = true; render(); res(); };
            el.onerror = () => { c.ready = false; res(); };
        });
        el.src = src;
        imgCache.set(src, c);
        return c;
    }
    /** Every picture on every page, decoded — the export must not race one. */
    function imagesReady() {
        stashPage();
        const waits = [];
        pages.forEach((pg) => (pg.objects || []).forEach((o) => {
            if (o.type === 'image' && o.src) waits.push(padImage(o.src).promise);
        }));
        return Promise.all(waits);
    }
    /** The four corners of a rotated image, for its on-screen bounding box. */
    function imageAabb(o) {
        const cx = o.x + o.w / 2, cy = o.y + o.h / 2;
        const cos = Math.cos(o.rot || 0), sin = Math.sin(o.rot || 0);
        const xs = [], ys = [];
        [[-o.w / 2, -o.h / 2], [o.w / 2, -o.h / 2], [o.w / 2, o.h / 2], [-o.w / 2, o.h / 2]].forEach(([dx, dy]) => {
            xs.push(cx + dx * cos - dy * sin);
            ys.push(cy + dx * sin + dy * cos);
        });
        return norm(Math.min(...xs), Math.min(...ys), Math.max(...xs), Math.max(...ys));
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
        } else if (o.type === 'image') {
            const c = padImage(o.src);
            ctx.save();
            ctx.translate(o.x + o.w / 2, o.y + o.h / 2);
            if (o.rot) ctx.rotate(o.rot);
            if (c.ready) {
                ctx.drawImage(c.el, -o.w / 2, -o.h / 2, o.w, o.h);
            } else {
                // Still decoding (or gone): a quiet frame, never a broken glyph.
                ctx.fillStyle = 'rgba(107,114,128,.12)';
                ctx.fillRect(-o.w / 2, -o.h / 2, o.w, o.h);
                ctx.strokeStyle = 'rgba(107,114,128,.5)'; ctx.lineWidth = 1.5;
                ctx.strokeRect(-o.w / 2, -o.h / 2, o.w, o.h);
            }
            ctx.restore();
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
            if (single.type === 'image') {
                // The rotate knob: a stem out of the top, a ring on its end —
                // the grammar picture editors already taught.
                const kx = b.x + b.w / 2, ky = b.y - ROT_REACH;
                ctx.beginPath(); ctx.moveTo(kx, b.y); ctx.lineTo(kx, ky + 5); ctx.stroke();
                ctx.beginPath(); ctx.arc(kx, ky, 5, 0, 7); ctx.fillStyle = '#fff'; ctx.fill();
                ctx.strokeStyle = '#2563eb'; ctx.lineWidth = 2; ctx.stroke();
            }
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
    const ROT_REACH = 26;             // how far the rotate knob stands off the box
    function hitHandle(x, y) {
        if (selected.size !== 1) return null;
        const o = objects.find((k) => selected.has(k.id));
        if (!o) return null;
        const b = bbox(o);
        if (o.type === 'image') {
            const kx = b.x + b.w / 2, ky = b.y - ROT_REACH;
            if (Math.hypot(x - kx, y - ky) <= HANDLE + 3) return 'rot';
        }
        for (const h of handlePoints(b)) { if (Math.abs(x - h.x) <= HANDLE && Math.abs(y - h.y) <= HANDLE) return h.n; }
        return null;
    }
    const CURSORS = { nw: 'nwse-resize', se: 'nwse-resize', ne: 'nesw-resize', sw: 'nesw-resize', n: 'ns-resize', s: 'ns-resize', e: 'ew-resize', w: 'ew-resize', rot: 'grab' };

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

    /* ---------- pages ----------
       `objects` is reassigned all over this file (every filter makes a new
       array), so the page it belongs to only learns about it here. Read
       pages[] anywhere without stashing first and you are reading history. */
    const chipsWrap = document.getElementById('drawPageChips');
    const pageLabel = document.getElementById('drawPagesLabel');
    const pagePrev = document.getElementById('drawPagePrev');
    const pageNext = document.getElementById('drawPageNext');
    const pageAdd = document.getElementById('drawPageAdd');
    const pageDel = document.getElementById('drawPageDel');

    function stashPage() {
        const p = pages[pageIndex];
        if (!p) return;
        p.objects = objects;
        p.undo = undoStack.slice();
        p.redo = redoStack.slice();
    }
    function loadPage(i) {
        pageIndex = Math.max(0, Math.min(i, pages.length - 1));
        const p = pages[pageIndex];
        objects = p.objects;
        undoStack.length = 0; p.undo.forEach((s) => undoStack.push(s));
        redoStack.length = 0; p.redo.forEach((s) => redoStack.push(s));
        selected.clear(); cur = null; mode = null; marquee = null;
        paintHistory(); paintPages(); render();
    }
    function goPage(i) {
        if (i < 0 || i >= pages.length || i === pageIndex) return;
        stashPage();
        loadPage(i);
    }
    function addPage() {
        if (pages.length >= MAX_PAGES) {
            window.toast?.('That is as many pages as one drawing keeps.', 'error');
            return;
        }
        stashPage();
        // After this one, not at the end: pages are read in order, and a page
        // added while looking at page 2 belongs behind page 2.
        pages.splice(pageIndex + 1, 0, blankPage());
        loadPage(pageIndex + 1);
    }
    async function removePage() {
        if (pages.length < 2) return;
        stashPage();
        const on = (pages[pageIndex].objects || []).length;
        // Undo lives inside a page, so it cannot bring a removed page back —
        // which is the whole reason this one asks and Clear's dialog is only
        // about being sure.
        if (on) {
            const ok = await padConfirm({
                title: 'Remove page ' + (pageIndex + 1) + '?',
                message: 'Everything on it goes with it — ' + on + (on === 1 ? ' thing' : ' things') + ' drawn here.',
                detail: 'Undo cannot bring a whole page back.',
                confirmText: 'Remove page',
            });
            if (!ok) return;
        }
        pages.splice(pageIndex, 1);
        loadPage(Math.min(pageIndex, pages.length - 1));
    }
    function paintPages() {
        if (!chipsWrap) return;
        chipsWrap.innerHTML = pages.map((p, i) => '<button type="button" class="draw-page'
            + (i === pageIndex ? ' is-active" aria-current="true"' : '"')
            + ' data-page="' + i + '" aria-label="Page ' + (i + 1) + '">' + (i + 1) + '</button>').join('');
        if (pageLabel) pageLabel.textContent = 'Page ' + (pageIndex + 1) + ' of ' + pages.length;
        if (pagePrev) pagePrev.disabled = pageIndex === 0;
        if (pageNext) pageNext.disabled = pageIndex >= pages.length - 1;
        if (pageAdd) pageAdd.disabled = pages.length >= MAX_PAGES;
        if (pageDel) pageDel.disabled = pages.length < 2;
    }
    chipsWrap?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-page]');
        if (b) goPage(parseInt(b.getAttribute('data-page'), 10));
    });
    pagePrev?.addEventListener('click', () => goPage(pageIndex - 1));
    pageNext?.addEventListener('click', () => goPage(pageIndex + 1));
    pageAdd?.addEventListener('click', addPage);
    pageDel?.addEventListener('click', removePage);

    /**
     * Strokes on the wire, in the shape the reader can cope with.
     *
     * One page goes out flat, exactly as every drawing did before pages
     * existed — nothing that reads a saved drawing needs to learn anything
     * new. Only a drawing that really has pages sends pages, and it says
     * which one was in front so reopening puts you back on it.
     */
    function strokePayload() {
        stashPage();
        if (pages.length === 1) return JSON.parse(JSON.stringify(pages[0].objects || []));
        return pages.map((p, i) => ({
            objects: JSON.parse(JSON.stringify(p.objects || [])),
            current: i === pageIndex,
        }));
    }
    /** The other direction: a flat drawing is one page, pages are pages. */
    function normalizePages(raw) {
        const out = { pages: [], current: 0 };
        if (!Array.isArray(raw) || !raw.length) return out;
        const paged = raw.every((p) => p && typeof p === 'object' && Array.isArray(p.objects));
        if (paged) {
            raw.forEach((p, i) => {
                out.pages.push(JSON.parse(JSON.stringify(p.objects || [])));
                if (p.current) out.current = i;
            });
        } else {
            out.pages.push(JSON.parse(JSON.stringify(raw)));
        }
        return out;
    }
    const totalObjects = () => { stashPage(); return pages.reduce((n, p) => n + (p.objects || []).length, 0); };

    /**
     * The app's confirm is a sheet (z-50) and this pad is z-400, so asking
     * anything from in here used to be a dialog you could hear and not see.
     * It gets lifted for as long as it is asking, and put back after.
     * `assumeYes` is for the questions whose answer is recoverable anyway.
     */
    let asking = false;               // a shared confirm is up, over the pad
    async function padConfirm(opts) {
        if (typeof window.confirmAction !== 'function') return !!opts.assumeYes;
        const root = document.documentElement;
        asking = true;
        root.classList.add('draw-pad-asking');
        try {
            return await new Promise((resolve) => {
                let settled = false;
                const finish = (answer) => {
                    if (settled) return;
                    settled = true;
                    document.removeEventListener('sm:sheet-closed', onSheetClosed);
                    resolve(answer);
                };
                // confirmAction only resolves when one of its two buttons is
                // pressed. Escape, a tap on the dimmed backdrop and Android Back
                // all just close the sheet, and the answer never comes — which
                // used to leave the lift below stuck on <html> for the rest of
                // the page's life, and with it the shared backdrop sitting on
                // top of every other sheet in the app. A question waved away is
                // a "no". The tick is so a real answer still wins: it closes the
                // sheet first and resolves a microtask later.
                const onSheetClosed = (e) => {
                    if (e.detail && e.detail.id !== 'confirm-sheet') return;
                    setTimeout(() => finish(false), 0);
                };
                document.addEventListener('sm:sheet-closed', onSheetClosed);
                Promise.resolve(window.confirmAction(opts)).then(finish, () => finish(false));
            });
        } finally {
            asking = false;
            root.classList.remove('draw-pad-asking');
        }
    }

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
        if (tool === 'text') { askForText(p); return; }
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
            if (resizeHandle === 'rot') {
                // The knob leads and the picture follows: the angle is wherever
                // the finger is, measured from the centre, minus the quarter
                // turn that puts the knob at the top. Shift snaps to 15° so a
                // straightening-up ends straight.
                const cx = o.x + o.w / 2, cy = o.y + o.h / 2;
                let a = Math.atan2(p.y - cy, p.x - cx) + Math.PI / 2;
                if (e.shiftKey) a = Math.round(a / (Math.PI / 12)) * (Math.PI / 12);
                o.rot = a;
                render(); return;
            }
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

    /* ---------- inserting a picture ----------
       Shrunk hard before it becomes an object: a camera photo is megabytes the
       strokes JSON would have to carry forever. 1400px is plenty to draw over
       and keeps a page of pictures around the size of one photo upload. */
    const imgInput = document.getElementById('drawImgInput');
    document.getElementById('drawInsertImg')?.addEventListener('click', () => imgInput.click());
    imgInput?.addEventListener('change', async (e) => {
        const f = e.target.files && e.target.files[0];
        e.target.value = '';
        if (!f) return;
        try {
            const bmp = await createImageBitmap(f, { imageOrientation: 'from-image' });
            const MAX = 1400;
            const k = Math.min(1, MAX / Math.max(bmp.width, bmp.height));
            const c = document.createElement('canvas');
            c.width = Math.max(1, Math.round(bmp.width * k));
            c.height = Math.max(1, Math.round(bmp.height * k));
            c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
            if (bmp.close) bmp.close();
            // JPEG unless the picture has transparency to lose. A screenshot
            // with an alpha channel keeps it; a photo shrinks fourfold.
            const src = f.type === 'image/png' && hasAlpha(c)
                ? c.toDataURL('image/png')
                : c.toDataURL('image/jpeg', 0.82);
            // Landing size: comfortably inside the sheet, centred, ready to be
            // taken by the corners.
            const fit = Math.min(1, (W * 0.6) / c.width, (H * 0.6) / c.height);
            const w = c.width * fit, h = c.height * fit;
            pushUndo();
            const o = { id: nextId(), type: 'image', src, x: (W - w) / 2, y: (H - h) / 2, w, h, rot: 0 };
            objects.push(o);
            padImage(src);
            selected = new Set([o.id]);
            setTool('select');
            render();
            window.toast?.('Picture placed — drag it, take a corner, turn the knob, and draw right over it.');
        } catch (_) {
            window.toast?.('That picture could not be read.', 'error');
        }
    });
    function hasAlpha(c) {
        // Sampled, not scanned: a corner-to-corner diagonal is plenty to say
        // whether transparency is real, and a full readback of 1400px is not
        // free on a field phone.
        const g = c.getContext('2d');
        const n = 24;
        for (let i = 0; i < n; i++) {
            const d = g.getImageData(Math.floor((c.width - 1) * i / n), Math.floor((c.height - 1) * i / n), 1, 1).data;
            if (d[3] < 250) return true;
        }
        return false;
    }

    /* ---------- the text tool's question ----------
       Words are typed into the pad's own dialog. The browser's prompt() box
       could not be read on a phone, could not be styled, and on a full-screen
       pad it looked like the page had been hijacked by something else. */
    const textAsk = document.getElementById('drawTextAsk');
    const textInput = document.getElementById('drawTextInput');
    let textAt = null;                // where on the sheet the words will land
    const textAskOpen = () => textAsk && !textAsk.hidden;
    function showTextAsk(on) {
        if (!textAsk) return;
        textAsk.hidden = !on;
        // Back dismisses the question, the way it dismisses any other overlay.
        if (on) { window.registerOverlay?.('drawText', () => showTextAsk(false)); }
        else { window.unregisterOverlay?.('drawText'); textAt = null; }
    }
    function askForText(p) {
        if (!textAsk || !textInput) return;
        textAt = p;
        textInput.value = '';
        showTextAsk(true);
        // `always`: on a phone this dialog exists to be typed into, so the
        // keyboard is wanted rather than an intrusion.
        window.smFocus?.(textInput, { delay: 80, always: true });
    }
    function commitText() {
        const t = (textInput?.value || '').trim();
        const p = textAt;
        showTextAsk(false);
        if (!t || !p) return;
        pushUndo();
        const o = { id: nextId(), type: 'text', color, size: fontPx(), x: p.x, y: p.y + fontPx() * 0.4, text: t };
        objects.push(o);
        selected = new Set([o.id]);
        setTool('select');
        render();
    }
    document.getElementById('drawTextOk')?.addEventListener('click', commitText);
    document.getElementById('drawTextCancel')?.addEventListener('click', () => showTextAsk(false));
    textAsk?.addEventListener('click', (e) => { if (e.target === textAsk) showTextAsk(false); });
    textInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); commitText(); }
    });

    /* ---------- toolbar buttons ---------- */
    document.getElementById('drawDelete').addEventListener('click', deleteSelected);
    document.getElementById('drawUndo').addEventListener('click', undo);
    document.getElementById('drawRedo').addEventListener('click', redo);
    document.getElementById('drawClear').addEventListener('click', async (e) => {
        // An empty sheet has nothing to lose, and a dialog with only one
        // possible answer is a tap for its own sake.
        if (!objects.length) { window.toast?.('This sheet is already empty.', 'info'); return; }
        const btn = e.currentTarget;
        if (btn.disabled) return;
        btn.disabled = true;                     // no second Clear while the first is being asked
        try {
            const many = pages.length > 1;
            const ok = await padConfirm({
                title: many ? 'Clear page ' + (pageIndex + 1) + '?' : 'Clear this drawing?',
                message: many
                    ? 'This clears the whole sheet — every stroke on page ' + (pageIndex + 1)
                        + '. The other pages are left alone.'
                    : 'This clears the whole sheet — every stroke on it goes.',
                detail: 'Undo brings it back.',
                confirmText: 'Clear the sheet',
                // Nothing to ask with (no dialog helper) is not a reason to
                // block the button: this one is undoable.
                assumeYes: true,
            });
            if (!ok) return;
            pushUndo(); objects = []; selected.clear(); render();
        } finally { btn.disabled = false; }
    });
    document.getElementById('drawGrid').addEventListener('click', (e) => {
        gridOn = !gridOn;
        e.currentTarget.classList.toggle('is-active', gridOn);
        render();
    });
    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('show')) return;
        // A confirm on top of the pad answers its own Escape (app.js closes the
        // sheet). Letting this handler see the same press shut the whole pad and
        // threw the drawing away, which is a strange answer to "clear a page?".
        if (asking) return;
        // Typing into the pad's own text box is typing, not shortcuts —
        // otherwise a Backspace meant for a typo deletes the shape behind it.
        // Only fields you type into, though: the stroke-size slider is an
        // <input> too, and it kept the focus after a drag, which left Delete and
        // Ctrl+Z doing nothing until you clicked back on the canvas.
        const el = e.target;
        const tag = (el && el.tagName) || '';
        const typing = /^textarea$/i.test(tag)
            || (/^input$/i.test(tag) && !/^(range|checkbox|radio|button|submit|reset|color|file)$/i.test(el.type || 'text'));
        if (textAskOpen() || typing) {
            if (e.key === 'Escape' && textAskOpen()) { e.preventDefault(); showTextAsk(false); }
            return;
        }
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
        showTextAsk(false);
        window.unregisterOverlay?.('drawPad');
        modal.classList.remove('show'); modal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('draw-pad-open');
        // The lift belongs to a question this pad is asking. Nothing outside the
        // pad should ever be left wearing it — a stray one puts the shared
        // backdrop over every sheet in the app until the page is reloaded.
        if (!asking) document.documentElement.classList.remove('draw-pad-asking');
        onSave = null; document.body.style.overflow = '';
        // Whoever opened this may have somewhere to send you afterwards.
        document.dispatchEvent(new CustomEvent('sm:draw-pad-closed'));
    }
    document.getElementById('drawCancel').addEventListener('click', close);
    document.getElementById('drawBack').addEventListener('click', close);
    function exportPng() {
        selected.clear(); marquee = null;
        stashPage();
        // Capture without the grid guide, then restore the on-screen view.
        exporting = true;
        const live = objects;
        let data;
        if (pages.length === 1) {
            render();
            data = canvas.toDataURL('image/png');
        } else {
            // Every page in one picture, top to bottom. The alternative was to
            // photograph whichever page happened to be in front, which would
            // have made a three-page drawing look like a third of itself in
            // the Gallery and on its note.
            const tall = H * pages.length + PAGE_GAP * (pages.length - 1);
            // Scaling to a fixed height scaled the width with it, so a five-page
            // sketch on a phone came out 677px wide and its writing unreadable.
            // Pages are stacked, so the width never grows: it keeps every pixel
            // it has until the whole picture is more than a canvas will export.
            const k = Math.min(1, EXPORT_MAX_H / tall, Math.sqrt(EXPORT_MAX_PX / (W * tall)));
            const out = document.createElement('canvas');
            out.width = Math.max(1, Math.round(W * k));
            out.height = Math.max(1, Math.round(tall * k));
            const octx = out.getContext('2d');
            octx.fillStyle = '#ffffff'; octx.fillRect(0, 0, out.width, out.height);
            octx.strokeStyle = '#d1d5db'; octx.lineWidth = 1;
            pages.forEach((p, i) => {
                objects = p.objects || [];
                render();
                const y = Math.round(i * (H + PAGE_GAP) * k);
                octx.drawImage(canvas, 0, y, out.width, Math.max(1, Math.round(H * k)));
                // A hairline in the gutter, so the join reads as a page break
                // and not as a gap somebody forgot to draw in.
                if (i) {
                    const cut = y - Math.round(PAGE_GAP * k / 2) + 0.5;
                    octx.beginPath(); octx.moveTo(0, cut); octx.lineTo(out.width, cut); octx.stroke();
                }
            });
            data = out.toDataURL('image/png');
        }
        objects = live;
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
        // Several pages keep their strokes when saved as a drawing, and are
        // stacked into one tall picture when saved as an image. Say so while
        // the choice is being made, not afterwards.
        if (on) {
            const many = pages.length > 1;
            const kept = document.getElementById('drawAskNewHint');
            const flat = document.getElementById('drawAskImageHint');
            if (kept) kept.textContent = many
                ? 'All ' + pages.length + ' pages, still editable later'
                : 'Reopen it later and keep changing it';
            if (flat) flat.textContent = many
                ? 'All ' + pages.length + ' pages in one tall picture — not editable'
                : 'A flat picture — it can be drawn over, not edited';
        }
        // Back answers the question by dismissing it, the way it dismisses any
        // other overlay, rather than walking out of the pad.
        if (on) window.registerOverlay?.('drawAsk', () => showAsk(false));
        else window.unregisterOverlay?.('drawAsk');
    }
    async function saveAs(mode) {
        // A backdrop that never arrived leaves a blank sheet, and saving that
        // over the drawing it was meant to be editing destroys it — which is
        // exactly what "I opened my drawing and the note went blank" was.
        if (backdropFailed && !totalObjects()) {
            window.toast?.('That drawing could not be loaded — saving now would replace it with a blank one.', 'error');
            return;
        }
        // A drawing reopened seconds ago may still be decoding its pictures;
        // exporting mid-decode bakes the grey placeholder into the note.
        await imagesReady();
        const data = exportPng();
        // 'overwrite' keeps the strokes as 'drawing' does — the difference is
        // which file it lands in, which is the caller's business.
        const strokes = (mode === 'drawing' || mode === 'overwrite') ? strokePayload() : null;
        if (onSave) onSave(data, strokes, mode);
        close();
    }
    document.getElementById('drawSaveBtn').addEventListener('click', () => {
        // Nothing to ask when only one kind is on offer — a dialog with one
        // real answer is a tap for its own sake.
        if (editableAllowed || overwriteLabel) showAsk(true);
        else saveAs('image');
    });
    ask.addEventListener('click', (e) => {
        const opt = e.target.closest('[data-save-mode]');
        if (opt) { saveAs(opt.getAttribute('data-save-mode')); return; }
        // The backdrop and Cancel close the question, not the drawing.
        if (e.target === ask || e.target.closest('#drawAskCancel')) showAsk(false);
    });

    function reset(existingUrl, existingObjects) {
        backdropFailed = false;
        // A fresh drawing gets a usable Clear, whatever happened to the last one.
        const clearBtn = document.getElementById('drawClear');
        if (clearBtn) clearBtn.disabled = false;
        objects = []; selected.clear(); undoStack.length = 0; redoStack.length = 0; marquee = null; mode = null; cur = null; uid = 1;
        pages = [blankPage()]; pageIndex = 0;
        // A drawing saved before pages existed is a flat list of strokes; one
        // saved since is a list of pages. Both have to open, and neither may
        // lose a stroke on the way in.
        const restored = normalizePages(existingObjects);
        paintHistory();
        // A new drawing takes the aspect of the screen it opens on, so the
        // white sheet fills the stage instead of letterboxing inside it. An
        // existing drawing keeps the space it was made in — reshaping that
        // would stretch what the user already drew.
        // The sheet should fill the screen it is opened on. A new drawing simply
        // takes the shape of the stage. An old one keeps every pixel it already
        // had — but grows if this screen is taller in proportion, which is what
        // a landscape drawing opened on a phone needs: without it the canvas sat
        // as a band in the middle with dead grey above and below it. It never
        // shrinks, because shrinking would crop what someone already drew.
        {
            const aw = Math.max(1, stage.clientWidth), ah = Math.max(1, stage.clientHeight);
            const forThisScreen = Math.min(2600, Math.max(520, Math.round(W0 * ah / aw)));
            W = W0;
            H = (existingUrl || restored.pages.length)
                ? Math.max(H0, forThisScreen)
                : forThisScreen;
        }
        canvas.width = W; canvas.height = H;
        setTool('pen');
        if (restored.pages.length) {
            // Real strokes, so each one can be selected, moved and undone again
            // — unlike a PNG backdrop, which can only be drawn over. Ids are
            // counted across every page: one counter, no collisions when a
            // shape is drawn on page 3.
            pages = restored.pages.map((list) => ({ objects: list, undo: [], redo: [] }));
            uid = pages.reduce((m, p) => p.objects.reduce((n, o) => Math.max(n, (o && o.id) || 0), m), 0) + 1;
            // Back on the page you left off on.
            pageIndex = Math.min(restored.current, pages.length - 1);
        }
        loadPage(pageIndex);
        if (!restored.pages.length && existingUrl) {
            const img = new Image();
            // Cross-origin only where it is actually cross-origin: media now
            // lives on the mother app, and a canvas that has drawn an image
            // without CORS permission cannot be exported at all. With the
            // header in place this succeeds; without it, onerror below keeps
            // the original safe rather than quietly blanking it.
            try {
                if (new URL(existingUrl, window.location.href).origin !== window.location.origin) {
                    img.crossOrigin = 'anonymous';
                }
            } catch (_) { img.crossOrigin = 'anonymous'; }
            img.onerror = () => {
                backdropFailed = true;
                window.toast?.('That drawing could not be loaded. Anything you draw now would replace it, so it is safer to close and reopen.', 'error');
            };
            img.onload = () => {
                // Its own proportions, pinned to the top-left. Stretching it to
                // whatever height this screen wanted would distort the picture.
                const k = Math.min(W / img.width, H / img.height);
                ctx.drawImage(img, 0, 0, Math.round(img.width * k), Math.round(img.height * k));
                try { objects = []; } catch (_) {}
                render();
            };
            img.src = existingUrl; // note: loaded as a backdrop only (not an editable object)
        }
    }

    /**
     * openDrawCanvas(cb, existingPngUrl, opts)
     *   cb(dataUrl, objects)  objects is null unless "Save as drawing" was used;
     *                         one page comes back flat, several come back as
     *                         [{objects, current}] — hand either one straight
     *                         back through opts.objects to reopen it
     *   opts.objects          strokes from a previous drawing save, to reopen
     *   opts.editable         offer the "Save as drawing" button
     */
    window.openDrawCanvas = function (cb, existingUrl, opts) {
        opts = opts || {};
        onSave = cb || null;
        editableAllowed = !!opts.editable;
        // What the pad is looking at, if it is looking at something that
        // already has a name. Drives the "save over this one" answer.
        overwriteLabel = opts.overwrite ? (opts.overwriteLabel || 'the one you opened') : '';
        const over = document.getElementById('drawAskOverwrite');
        if (over) {
            over.hidden = !overwriteLabel;
            const hint = document.getElementById('drawAskOverwriteHint');
            if (hint && overwriteLabel) hint.textContent = 'Replaces ' + overwriteLabel;
        }
        const newName = document.getElementById('drawAskNewName');
        if (newName) newName.textContent = overwriteLabel ? 'Save as a new drawing' : 'Save as drawing';
        const title = document.getElementById('drawTitleText');
        if (title) title.textContent = opts.title || 'Drawing';
        showAsk(false);
        showTextAsk(false);
        // Re-parent to <body> so `position:fixed` is relative to the viewport —
        // never trapped/cramped inside a transformed ancestor (the notes module
        // wrapper, an open sheet, etc.). Also sidesteps any duplicate #drawModal.
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        // Marks the whole app as "a pad is up", which is what lets the pad's
        // own toasts and questions come over it instead of under it.
        document.documentElement.classList.add('draw-pad-open');
        // A full-screen pad is the clearest case of all: Back should shut it,
        // not the page underneath it.
        window.registerOverlay?.('drawPad', close);
        document.body.style.overflow = 'hidden';
        reset(existingUrl, opts.objects);
        requestAnimationFrame(fitStage);
    };
})();
</script>
