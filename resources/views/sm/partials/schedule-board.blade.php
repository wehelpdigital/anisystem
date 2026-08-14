{{--
    Collaborative team WHITEBOARD for a schedule. The canvas always fills its
    container (backed at device resolution); strokes are stored in normalized
    0..1 coordinates so every collaborator's board matches regardless of their
    window size. Synced via Pusher when configured and a reconcile poll
    otherwise. Strokes persist to as_schedule_board_events (per page) so late
    joiners rebuild the board. Multiple pages; export pages to the schedule notes.
    Opened inline in the Collab Room (window.mountScheduleBoard) or as a
    fullscreen overlay (window.openScheduleBoard). Expects: $schedule.
--}}
@php
    $boardHasTeam = \App\Support\ScheduleTeam::hasTeam($schedule);
    $boardCanAccess = \App\Support\ScheduleTeam::canAccess($schedule, (int) auth()->id());
@endphp
@if ($boardHasTeam && $boardCanAccess)
<div id="scheduleBoard" class="sb-overlay hidden" aria-hidden="true">
    <div class="sb-bar">
        <button type="button" id="sbClose" class="sb-btn" title="Close board" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <span class="sb-title">🎨 Team whiteboard</span>
        <span class="sb-live" id="sbLive"></span>
        <div class="sb-pages" id="sbPages">
            <button type="button" id="sbPagePrev" class="sb-pg" title="Previous page" aria-label="Previous page"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg></button>
            <span class="sb-pg-label" id="sbPageLabel">1 / 1</span>
            <button type="button" id="sbPageNext" class="sb-pg" title="Next page" aria-label="Next page"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg></button>
            <button type="button" id="sbPageAdd" class="sb-pg" title="Add a page" aria-label="Add page"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg></button>
        </div>
        <span class="sb-spacer"></span>
        <div class="sb-colors" id="sbColors">
            <button type="button" class="sb-swatch is-active" data-color="#111827" style="--c:#111827" aria-label="Black"></button>
            <button type="button" class="sb-swatch" data-color="#ef4444" style="--c:#ef4444" aria-label="Red"></button>
            <button type="button" class="sb-swatch" data-color="#f59e0b" style="--c:#f59e0b" aria-label="Amber"></button>
            <button type="button" class="sb-swatch" data-color="#22c55e" style="--c:#22c55e" aria-label="Green"></button>
            <button type="button" class="sb-swatch" data-color="#2563eb" style="--c:#2563eb" aria-label="Blue"></button>
            <button type="button" class="sb-swatch" data-color="#a855f7" style="--c:#a855f7" aria-label="Purple"></button>
        </div>
        <div class="sb-sizes" id="sbSizes">
            <button type="button" class="sb-size" data-size="3" aria-label="Thin"><span style="width:.35rem;height:.35rem"></span></button>
            <button type="button" class="sb-size is-active" data-size="6" aria-label="Medium"><span style="width:.6rem;height:.6rem"></span></button>
            <button type="button" class="sb-size" data-size="14" aria-label="Thick"><span style="width:.95rem;height:.95rem"></span></button>
        </div>
        <div class="sb-tools" id="sbTools">
            <button type="button" class="sb-tool is-active" data-tool="pen" title="Pen" aria-label="Pen"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.23 5.23l3.54 3.54M9 11l6-6 3 3-6 6H9v-3z"/></svg></button>
            <button type="button" class="sb-tool" data-tool="line" title="Line" aria-label="Line"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 19L19 5"/></svg></button>
            <button type="button" class="sb-tool" data-tool="arrow" title="Arrow" aria-label="Arrow"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19L19 5m0 0h-6m6 0v6"/></svg></button>
            <button type="button" class="sb-tool" data-tool="rect" title="Rectangle" aria-label="Rectangle"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4.5" y="6.5" width="15" height="11" rx="1"/></svg></button>
            <button type="button" class="sb-tool" data-tool="circle" title="Circle" aria-label="Circle"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg></button>
            <button type="button" class="sb-tool" data-tool="text" title="Text" aria-label="Text"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 6h14M12 6v12"/></svg></button>
            <button type="button" class="sb-tool" data-tool="eraser" title="Eraser" aria-label="Eraser"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16M8.5 16.5l8-8a2.5 2.5 0 000-3.5l-1.5-1.5a2.5 2.5 0 00-3.5 0l-8 8a2.5 2.5 0 000 3.5L5 16.5a2 2 0 001.4.6h2.1a2 2 0 001.4-.6z"/></svg></button>
        </div>
        <button type="button" id="sbSaveNotes" class="sb-btn" title="Save pages to schedule notes" aria-label="Save to notes">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a1 1 0 011-1h9l4 4v10a1 1 0 01-1 1H6a1 1 0 01-1-1V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 4v4h6M8 19v-5h8v5"/></svg>
        </button>
        <button type="button" id="sbDrafts" class="sb-btn" title="Past drawings" aria-label="Past drawings">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7a1 1 0 011-1h4l2 2h8a1 1 0 011 1v9a1 1 0 01-1 1H5a1 1 0 01-1-1V7z"/></svg>
            <span class="sb-badge hidden" id="sbDraftCount">0</span>
        </button>
        <button type="button" id="sbGrid" class="sb-btn" title="Toggle grid" aria-label="Toggle grid">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4z"/><path stroke-linecap="round" d="M4 10h16M4 15h16M10 4v16M15 4v16"/></svg>
        </button>
        <button type="button" id="sbUndo" class="sb-btn" title="Undo my last stroke" aria-label="Undo">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 10h8a5 5 0 010 10h-3"/></svg>
        </button>
        <button type="button" id="sbRedo" class="sb-btn" title="Redo" aria-label="Redo" disabled>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 14l4-4-4-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 10h-8a5 5 0 000 10h3"/></svg>
        </button>
        <button type="button" id="sbClear" class="sb-btn sb-btn-danger" title="Clear board" aria-label="Clear board">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
        </button>
        <button type="button" id="sbChatToggle" class="sb-btn is-active" title="Show or hide team chat" aria-label="Toggle team chat">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12a8 8 0 01-11.6 7.1L3 20l1-5.5A8 8 0 1121 12z"/></svg>
        </button>
    </div>
    <div class="sb-body">
        <div class="sb-stage">
            <canvas id="sbCanvas"></canvas>
        </div>
        {{-- The team chat panel is docked in here while the board is open. --}}
        <div class="sb-side" id="sbSide"></div>
    </div>

    {{-- Past drawings. Previews are painted from the stored strokes with the
         same renderer as the board, so no server-side rasterising is needed and
         a preview can never drift from what reopening actually gives you. --}}
    <div class="sb-modal hidden" id="sbDraftsModal" aria-hidden="true">
        <div class="sb-modal-card sb-drafts-card">
            <div class="sb-modal-head">
                <span class="sb-modal-title">Past drawings</span>
                <button type="button" id="sbDraftsX" class="sb-modal-x" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="sb-modal-hint">Drawings kept when the room emptied. Open one to carry on with it — your changes update that drawing. Anything saved to the notebook lives there instead.</p>
            <div class="sb-drafts" id="sbDraftsList">
                <p class="sb-drafts-empty">Loading…</p>
            </div>
        </div>
    </div>

    {{-- Save-to-notes modal: title + description, with a saving loader. --}}
    <div class="sb-modal hidden" id="sbSaveModal" aria-hidden="true">
        <div class="sb-modal-card">
            <div class="sb-modal-head">
                <span class="sb-modal-title">Save whiteboard to notes</span>
                <button type="button" id="sbSaveCancelX" class="sb-modal-x" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="sb-modal-hint">Saves <span id="sbSavePageCount">the board</span> as an image note in this schedule's notebook, so the team can find it later.</p>
            <label class="sb-modal-label" for="sbSaveTitle">Title</label>
            <input type="text" id="sbSaveTitle" class="sb-modal-input" maxlength="180" placeholder="e.g. Field layout plan">
            <label class="sb-modal-label" for="sbSaveDesc">What is this note about?</label>
            <textarea id="sbSaveDesc" class="sb-modal-textarea" rows="3" maxlength="5000" placeholder="Describe what the drawing shows and why it matters…"></textarea>
            <div class="sb-modal-actions">
                <button type="button" id="sbSaveCancel" class="sb-modal-btn ghost">Cancel</button>
                <button type="button" id="sbSaveConfirm" class="sb-modal-btn primary">
                    <span class="sb-modal-spin hidden" id="sbSaveSpin"></span>
                    <span id="sbSaveLabel">Save to notes</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .sb-overlay { position: fixed; inset: 0; z-index: 200; display: flex; flex-direction: column; background: rgb(17 24 39 / .82); backdrop-filter: blur(2px); }
    .sb-overlay.hidden { display: none; }
    /* Inline mount inside the Collab Room "Drawing" tab (not a fullscreen overlay). */
    .sb-overlay.sb-inline { position: static; inset: auto; z-index: auto; height: 100%; width: 100%; background: transparent; backdrop-filter: none; border-radius: 1rem; }
    .sb-hide { display: none !important; }
    .sb-bar { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; padding: .5rem .7rem; background: var(--color-white); border-bottom: 1px solid var(--color-gray-200); }
    .sb-title { font-family: var(--font-heading); font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .sb-live { font-size: .68rem; font-weight: 800; color: var(--color-gray-400); }
    .sb-live.on { color: #16a34a; }
    .sb-spacer { flex: 1 1 auto; }
    .sb-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; border-radius: .6rem; color: var(--color-gray-600); background: var(--color-gray-100); flex-shrink: 0; }
    .sb-btn:hover { background: var(--color-gray-200); color: var(--color-gray-800); }
    .sb-btn.is-active { background: var(--color-brand-600); color: #fff; }
    .sb-btn-danger:hover { background: #fee2e2; color: #b91c1c; }
    /* Nothing to redo keeps its place in the row, greyed — a button that comes
       and goes makes the whole toolbar jump while you are drawing. */
    .sb-btn:disabled { opacity: .38; }
    .sb-btn:disabled:hover { background: var(--color-gray-100); color: var(--color-gray-600); }
    .sb-tools { display: inline-flex; gap: .15rem; padding: .15rem .25rem; background: var(--color-gray-100); border-radius: .7rem; }
    .sb-tool { width: 1.95rem; height: 1.95rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-600); }
    .sb-tool:hover { background: var(--color-gray-200); }
    .sb-tool.is-active { background: var(--color-brand-600); color: #fff; }
    #sbCanvas.sb-grid { background-image: linear-gradient(rgb(0 0 0 / .09) 1px, transparent 1px), linear-gradient(90deg, rgb(0 0 0 / .09) 1px, transparent 1px); background-size: 40px 40px; }
    .sb-text-input { position: fixed; z-index: 210; border: 1px dashed #3f7fb0; background: rgb(255 255 255 / .92); color: #111827; padding: 0 .2rem; outline: none; min-width: 5rem; border-radius: .2rem; line-height: 1.2; }
    /* Save-to-notes modal */
    .sb-modal { position: fixed; inset: 0; z-index: 240; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgb(17 24 39 / .55); }
    .sb-modal.hidden { display: none; }
    .sb-modal-card { width: min(30rem, 100%); background: var(--color-white); border-radius: 1rem; padding: 1.1rem 1.2rem 1.2rem; box-shadow: 0 24px 60px rgb(0 0 0 / .4); animation: sbModalIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes sbModalIn { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes sbSpin { to { transform: rotate(360deg); } }
    .sb-modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .5rem; }
    .sb-modal-title { font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; color: var(--color-gray-900); }
    .sb-modal-x { width: 2rem; height: 2rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-500); }
    .sb-modal-x:hover { background: var(--color-gray-100); }
    .sb-modal-hint { font-size: .8rem; color: var(--color-gray-500); line-height: 1.5; margin-bottom: .9rem; }
    .sb-modal-label { display: block; font-size: .72rem; font-weight: 700; color: var(--color-gray-600); margin: 0 0 .3rem; }
    .sb-modal-input, .sb-modal-textarea { width: 100%; border: 1.5px solid var(--color-gray-200); border-radius: .7rem; padding: .5rem .65rem; font-size: .9rem; color: var(--color-gray-900); background: var(--color-white); outline: none; margin-bottom: .8rem; font-family: inherit; }
    .sb-modal-input:focus, .sb-modal-textarea:focus { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .sb-modal-textarea { resize: vertical; min-height: 4rem; line-height: 1.5; }
    .sb-modal-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: .3rem; }
    .sb-modal-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: .7rem; font-weight: 700; font-size: .85rem; }
    .sb-modal-btn.ghost { color: var(--color-gray-600); background: var(--color-gray-100); }
    .sb-modal-btn.ghost:hover { background: var(--color-gray-200); }
    .sb-modal-btn.primary { color: #fff; background: linear-gradient(140deg, #6b9f3d, #3d6823); }
    .sb-modal-btn.primary:disabled { opacity: .65; }
    .sb-modal-spin { width: 1rem; height: 1rem; border-radius: 999px; border: 2px solid rgb(255 255 255 / .4); border-top-color: #fff; animation: sbSpin .7s linear infinite; }
    .sb-modal-spin.hidden { display: none; }
    @media (prefers-reduced-motion: reduce) { .sb-modal-card { animation: none; } .sb-modal-spin { animation-duration: 1.4s; } }

    /* Past drawings */
    .sb-btn { position: relative; }
    .sb-badge { position: absolute; top: -.2rem; right: -.2rem; min-width: 1.05rem; height: 1.05rem; padding: 0 .22rem; border-radius: 999px; background: var(--color-brand-600); color: #fff; font-size: .62rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .sb-badge.hidden { display: none; }
    .sb-drafts-card { width: min(44rem, 100%); }
    .sb-drafts { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: .7rem; max-height: 60vh; overflow-y: auto; }
    .sb-draft { border: 1px solid var(--color-gray-200); border-radius: .8rem; overflow: hidden; background: var(--color-white); text-align: left; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .sb-draft:hover { border-color: var(--color-brand-400); transform: translateY(-2px); }
    .sb-draft canvas { display: block; width: 100%; height: 6rem; background: #fff; border-bottom: 1px solid var(--color-gray-100); }
    .sb-draft-meta { padding: .45rem .55rem .55rem; }
    .sb-draft-title { font-size: .78rem; font-weight: 700; color: var(--color-gray-800); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sb-draft-sub { font-size: .68rem; color: var(--color-gray-500); }
    .sb-drafts-empty { font-size: .82rem; color: var(--color-gray-500); grid-column: 1 / -1; padding: 1.5rem 0; text-align: center; }
    @media (prefers-reduced-motion: reduce) { .sb-draft:hover { transform: none; } }
    .sb-pages { display: inline-flex; align-items: center; gap: .1rem; padding: .1rem .2rem; background: var(--color-gray-100); border-radius: .7rem; }
    .sb-pg { width: 1.7rem; height: 1.7rem; border-radius: .45rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-600); }
    .sb-pg:hover:not(:disabled) { background: var(--color-gray-200); color: var(--color-gray-900); }
    .sb-pg:disabled { opacity: .35; cursor: default; }
    .sb-pg-label { font-size: .72rem; font-weight: 800; color: var(--color-gray-700); min-width: 2.4rem; text-align: center; font-variant-numeric: tabular-nums; }
    .sb-colors, .sb-sizes { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .3rem; background: var(--color-gray-100); border-radius: .7rem; }
    .sb-swatch { width: 1.5rem; height: 1.5rem; border-radius: 999px; background: var(--c); box-shadow: inset 0 0 0 2px rgb(255 255 255 / .85), 0 0 0 1px rgb(0 0 0 / .08); }
    .sb-swatch.is-active { box-shadow: inset 0 0 0 2px rgb(255 255 255 / .95), 0 0 0 2px var(--c); transform: scale(1.08); }
    .sb-size { width: 1.9rem; height: 1.9rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; }
    .sb-size span { display: block; border-radius: 999px; background: var(--color-gray-500); }
    .sb-size.is-active { background: var(--color-white); box-shadow: 0 1px 3px rgb(0 0 0 / .15); }
    .sb-size.is-active span { background: var(--color-gray-900); }
    .sb-body { flex: 1 1 auto; display: flex; min-height: 0; position: relative; }
    .sb-stage { flex: 1 1 auto; display: flex; min-height: 0; min-width: 0; padding: .4rem; }
    .sb-side { width: 21rem; flex-shrink: 0; background: var(--color-white); border-left: 1px solid var(--color-gray-200); display: flex; }
    .sb-side.collapsed { display: none; }
    @media (max-width: 767px) {
        .sb-side { position: absolute; top: 0; right: 0; bottom: 0; width: min(21rem, 88%); z-index: 5; box-shadow: -10px 0 30px rgb(0 0 0 / .35); }
    }
    /* Fill the whole stage — no letterboxing. Backing resolution is set in JS. */
    #sbCanvas { background: #fff; border-radius: .6rem; box-shadow: 0 12px 40px rgb(0 0 0 / .4); display: block; width: 100%; height: 100%; touch-action: none; cursor: crosshair; }
</style>

<script>
(() => {
    const init = () => {
        const overlay = document.getElementById('scheduleBoard');
        const canvas = document.getElementById('sbCanvas');
        if (!overlay || !canvas) return;
        const ctx = canvas.getContext('2d');
        const REF = 1280;   // reference width for scaling stroke widths / text sizes

        const SCHEDULE_ID = @json($schedule->id);
        const ME = @json((int) auth()->id());
        const U = {
            events: @json(route('sm.board')), push: @json(route('sm.board.push')),
            pages: @json(route('sm.board.pages')), pageCreate: @json(route('sm.board.page-create')),
            saveNotes: @json(route('sm.board.save-notes')), undo: @json(route('sm.board.undo')),
            open: @json(route('sm.board.open')), heartbeat: @json(route('sm.board.heartbeat')),
            drafts: @json(route('sm.board.drafts')), draftOpen: @json(route('sm.board.draft-open')),
        };

        let color = '#111827', width = 6, tool = 'pen';   // pen|eraser|line|arrow|rect|circle|text
        let drawing = false, uid = null, prevLocal = null, pending = [], lastSent = null, lastFlush = 0;
        let shapeStart = null, shapeEnd = null, snapshot = null;   // in-progress shape preview
        let lastId = 0, pollTimer = null, channel = null;
        const rendered = new Set();
        const isFreehand = () => tool === 'pen' || tool === 'eraser';

        /* ---------- pages ---------- */
        let pages = [{ page: 1 }];
        let currentPage = 1;

        /* ---------- coordinates (all points are normalized 0..1) ---------- */
        const clamp01 = (v) => v < 0 ? 0 : (v > 1 ? 1 : v);
        function toNorm(e) {
            const r = canvas.getBoundingClientRect();
            return [clamp01((e.clientX - r.left) / r.width), clamp01((e.clientY - r.top) / r.height)];
        }
        function clearCanvas() { ctx.clearRect(0, 0, canvas.width, canvas.height); }

        /* ---------- draw primitives (convert norm -> the target context's px) ---------- */
        function drawSeg(g, points, c, w, mode) {
            if (!points || !points.length) return;
            const gw = g.canvas.width, gh = g.canvas.height, sc = gw / REF;
            g.save(); g.lineCap = 'round'; g.lineJoin = 'round'; g.lineWidth = Math.max(1, (w || 4) * sc);
            if (mode === 'eraser') { g.globalCompositeOperation = 'destination-out'; g.strokeStyle = 'rgba(0,0,0,1)'; }
            else { g.globalCompositeOperation = 'source-over'; g.strokeStyle = c || '#111827'; }
            g.beginPath();
            g.moveTo(points[0][0] * gw, points[0][1] * gh);
            if (points.length === 1) g.lineTo(points[0][0] * gw + 0.5, points[0][1] * gh + 0.5);
            else for (let i = 1; i < points.length; i++) g.lineTo(points[i][0] * gw, points[i][1] * gh);
            g.stroke(); g.restore();
        }
        function drawShape(g, mode, a, b, c, w) {
            const gw = g.canvas.width, gh = g.canvas.height, sc = gw / REF;
            g.save(); g.lineCap = 'round'; g.lineJoin = 'round'; g.globalCompositeOperation = 'source-over';
            g.strokeStyle = c || '#111827'; g.fillStyle = c || '#111827'; g.lineWidth = Math.max(1, (w || 4) * sc);
            const x0 = a[0] * gw, y0 = a[1] * gh, x1 = b[0] * gw, y1 = b[1] * gh;
            if (mode === 'rect') { g.strokeRect(Math.min(x0, x1), Math.min(y0, y1), Math.abs(x1 - x0), Math.abs(y1 - y0)); }
            else if (mode === 'circle') { g.beginPath(); g.ellipse((x0 + x1) / 2, (y0 + y1) / 2, Math.abs(x1 - x0) / 2, Math.abs(y1 - y0) / 2, 0, 0, Math.PI * 2); g.stroke(); }
            else {
                g.beginPath(); g.moveTo(x0, y0); g.lineTo(x1, y1); g.stroke();
                if (mode === 'arrow') { const ang = Math.atan2(y1 - y0, x1 - x0), h = Math.max(12, (w || 4) * 3) * sc; g.beginPath(); g.moveTo(x1, y1); g.lineTo(x1 - h * Math.cos(ang - 0.42), y1 - h * Math.sin(ang - 0.42)); g.moveTo(x1, y1); g.lineTo(x1 - h * Math.cos(ang + 0.42), y1 - h * Math.sin(ang + 0.42)); g.stroke(); }
            }
            g.restore();
        }
        function drawTextAt(g, p, text, c, w) {
            const gw = g.canvas.width, gh = g.canvas.height, sc = gw / REF;
            g.save(); g.globalCompositeOperation = 'source-over'; g.fillStyle = c || '#111827';
            const size = Math.max(14, (w || 6) * 4) * sc; g.textBaseline = 'top';
            g.font = `600 ${size}px system-ui, -apple-system, sans-serif`;
            const x = p[0] * gw, y = p[1] * gh;
            String(text || '').split('\n').forEach((ln, i) => g.fillText(ln, x, y + i * size * 1.2));
            g.restore();
        }
        function paintEvent(g, mode, points, c, w, text) {
            if (mode === 'text') { if (points && points[0]) drawTextAt(g, points[0], text, c, w); return; }
            if (mode === 'line' || mode === 'arrow' || mode === 'rect' || mode === 'circle') { if (points && points.length >= 2) drawShape(g, mode, points[0], points[1], c, w); return; }
            drawSeg(g, points, c, w, mode);
        }
        // Shift-constrain to a visual square/circle (equal device-px deltas).
        const squareEnd = (a, b) => {
            const gw = canvas.width, gh = canvas.height;
            const dx = (b[0] - a[0]) * gw, dy = (b[1] - a[1]) * gh, m = Math.max(Math.abs(dx), Math.abs(dy));
            return [a[0] + (dx < 0 ? -m : m) / gw, a[1] + (dy < 0 ? -m : m) / gh];
        };

        /* ---------- fill the container (backing resolution follows the element) ---------- */
        function resizeCanvas() {
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            const cw = canvas.clientWidth, ch = canvas.clientHeight;
            if (!cw || !ch) return false;
            const bw = Math.max(1, Math.round(cw * dpr)), bh = Math.max(1, Math.round(ch * dpr));
            if (canvas.width === bw && canvas.height === bh) return false;
            canvas.width = bw; canvas.height = bh;   // this also clears the canvas
            return true;
        }
        let observing = false, resizeT = null;
        function onResizeSettled() {
            if (drawing) { clearTimeout(resizeT); resizeT = setTimeout(onResizeSettled, 200); return; }
            if (resizeCanvas()) rebuildPage();   // re-render normalized strokes at the new size
        }
        function observeSize() {
            if (observing || !window.ResizeObserver) return;
            observing = true;
            new ResizeObserver(() => { clearTimeout(resizeT); resizeT = setTimeout(onResizeSettled, 180); }).observe(canvas);
        }

        /* ---------- pages ---------- */
        function renderPageBar() {
            const idx = pages.findIndex((p) => p.page === currentPage);
            const label = document.getElementById('sbPageLabel');
            if (label) label.textContent = (idx < 0 ? 1 : idx + 1) + ' / ' + pages.length;
            const prev = document.getElementById('sbPagePrev'); if (prev) prev.disabled = idx <= 0;
            const next = document.getElementById('sbPageNext'); if (next) next.disabled = idx < 0 || idx >= pages.length - 1;
        }
        async function loadPages() {
            try {
                const r = await api(`${U.pages}?scheduleId=${SCHEDULE_ID}`);
                if (r && r.data && Array.isArray(r.data.pages) && r.data.pages.length) pages = r.data.pages;
            } catch (_) { /* keep default single page */ }
            if (!pages.some((p) => p.page === currentPage)) currentPage = pages[0].page;
            renderPageBar();
        }
        function whiteComposite(src, w, h) {
            const out = document.createElement('canvas'); out.width = w; out.height = h;
            const og = out.getContext('2d');
            og.fillStyle = '#ffffff'; og.fillRect(0, 0, w, h);
            og.drawImage(src, 0, 0);
            return out.toDataURL('image/png');
        }
        // Render every page to a uniform 1280x800 PNG (normalized points scale in).
        async function exportAllPages() {
            const EW = 1280, EH = 800, imgs = [];
            for (const p of pages) {
                const off = document.createElement('canvas'); off.width = EW; off.height = EH;
                const g = off.getContext('2d');
                try {
                    const r = await api(`${U.events}?scheduleId=${SCHEDULE_ID}&page=${p.page}&after=0`);
                    (r.data.events || []).forEach((ev) => { if (ev.type !== 'clear') paintEvent(g, ev.mode, ev.points, ev.color, ev.width, ev.text); });
                } catch (_) { /* export a blank page */ }
                imgs.push(whiteComposite(off, EW, EH));
            }
            return imgs;
        }

        /* ---------- apply a remote/loaded event (race-safe while I shape) ---------- */
        function applyEvent(ev, isRebuild) {
            if (ev.id) { if (rendered.has(ev.id)) return; rendered.add(ev.id); if (ev.id > lastId) lastId = ev.id; }
            // Someone took a stroke back. The sync only ever adds, so there is
            // no way to say "that one is gone" — the whole page is repainted
            // from what is still standing. Not for my own marker: I repainted
            // when I pressed the button.
            if (ev.type === 'undo') { if (!isRebuild && ev.userId !== ME) scheduleRebuild(); return; }
            const shaping = drawing && !isFreehand() && snapshot;
            if (shaping) ctx.putImageData(snapshot, 0, 0);   // strip my live preview first
            if (ev.type === 'clear') { clearCanvas(); }
            else if (isRebuild || ev.userId !== ME) paintEvent(ctx, ev.mode, ev.points, ev.color, ev.width, ev.text);
            if (shaping) { snapshot = ctx.getImageData(0, 0, canvas.width, canvas.height); drawShape(ctx, tool, shapeStart, shapeEnd, color, width); }
        }

        /* ---------- sending ---------- */
        function pushEvent(body) {
            dropRedo();
            body = Object.assign({ page: currentPage }, body);
            api(`${U.push}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body }).then((r) => {
                const id = r && r.data && r.data.event && r.data.event.id;
                if (id) { rendered.add(id); if (id > lastId) lastId = id; }
            }).catch(() => { /* reconcile poll will recover */ });
        }
        function sendBatch(points) { pushEvent({ uid, color, width, mode: tool, points }); }
        function maybeFlush(force) {
            if (!pending.length) return;
            if (!force && pending.length < 6 && (Date.now() - lastFlush) < 130) return;
            const batch = [lastSent, ...pending];
            lastSent = pending[pending.length - 1];
            pending = [];
            lastFlush = Date.now();
            sendBatch(batch);
        }

        /* ---------- text tool: an inline input at the click ---------- */
        function openTextInput(clientX, clientY, p) {
            document.getElementById('sbTextInput')?.remove();
            const inp = document.createElement('input');
            inp.id = 'sbTextInput'; inp.type = 'text'; inp.className = 'sb-text-input';
        // A box this small has no room for the dictation mic, and its own
        // blur commits and removes it — a tap on the mic would lose the text.
        inp.setAttribute('data-no-dictate', '');
            inp.style.left = clientX + 'px'; inp.style.top = clientY + 'px';
            inp.style.color = color;
            // Match the on-canvas text height in CSS px.
            inp.style.fontSize = (Math.max(14, width * 4) * (canvas.clientWidth / REF)) + 'px';
            document.body.appendChild(inp);
            setTimeout(() => inp.focus(), 0);
            let done = false;
            const commit = () => { if (done) return; done = true; const t = inp.value.trim(); inp.remove(); if (t) { drawTextAt(ctx, p, t, color, width); pushEvent({ uid, color, width, mode: 'text', points: [p], text: t }); } };
            inp.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') { ev.preventDefault(); commit(); } else if (ev.key === 'Escape') { done = true; inp.remove(); } });
            inp.addEventListener('blur', commit);
        }

        /* ---------- pointer ---------- */
        canvas.addEventListener('pointerdown', (e) => {
            if (e.button !== undefined && e.button !== 0) return;
            e.preventDefault();
            const p = toNorm(e);
            uid = 's' + Date.now().toString(36) + Math.round(performance.now()).toString(36);
            if (tool === 'text') { openTextInput(e.clientX, e.clientY, p); return; }
            canvas.setPointerCapture?.(e.pointerId);
            drawing = true;
            if (isFreehand()) { prevLocal = p; pending = [p]; lastSent = p; lastFlush = Date.now(); drawSeg(ctx, [p], color, width, tool); }
            else { shapeStart = p; shapeEnd = p; snapshot = ctx.getImageData(0, 0, canvas.width, canvas.height); }
        });
        canvas.addEventListener('pointermove', (e) => {
            if (!drawing) return;
            e.preventDefault();
            let p = toNorm(e);
            if (isFreehand()) { drawSeg(ctx, [prevLocal, p], color, width, tool); prevLocal = p; pending.push(p); maybeFlush(false); }
            else { if (e.shiftKey && (tool === 'rect' || tool === 'circle')) p = squareEnd(shapeStart, p); shapeEnd = p; ctx.putImageData(snapshot, 0, 0); drawShape(ctx, tool, shapeStart, p, color, width); }
        });
        const endStroke = (e) => {
            if (!drawing) return; drawing = false;
            try { canvas.releasePointerCapture?.(e.pointerId); } catch (_) {}
            if (isFreehand()) { maybeFlush(true); }
            else if (shapeStart && shapeEnd) { ctx.putImageData(snapshot, 0, 0); drawShape(ctx, tool, shapeStart, shapeEnd, color, width); pushEvent({ uid, color, width, mode: tool, points: [shapeStart, shapeEnd] }); shapeStart = shapeEnd = snapshot = null; }
        };
        canvas.addEventListener('pointerup', endStroke);
        canvas.addEventListener('pointercancel', endStroke);
        canvas.addEventListener('pointerleave', () => { if (drawing && isFreehand()) maybeFlush(true); });

        /* ---------- toolbar ---------- */
        document.getElementById('sbTools').addEventListener('click', (e) => {
            const b = e.target.closest('.sb-tool'); if (!b) return;
            tool = b.getAttribute('data-tool');
            overlay.querySelectorAll('.sb-tool').forEach((t) => t.classList.toggle('is-active', t === b));
        });
        document.getElementById('sbColors').addEventListener('click', (e) => {
            const b = e.target.closest('.sb-swatch'); if (!b) return;
            color = b.getAttribute('data-color');
            overlay.querySelectorAll('.sb-swatch').forEach((s) => s.classList.toggle('is-active', s === b));
            if (tool === 'eraser') { tool = 'pen'; overlay.querySelectorAll('.sb-tool').forEach((t) => t.classList.toggle('is-active', t.getAttribute('data-tool') === 'pen')); }
        });
        document.getElementById('sbSizes').addEventListener('click', (e) => {
            const b = e.target.closest('.sb-size'); if (!b) return;
            width = parseInt(b.getAttribute('data-size'), 10) || 6;
            overlay.querySelectorAll('.sb-size').forEach((s) => s.classList.toggle('is-active', s === b));
        });
        document.getElementById('sbGrid').addEventListener('click', (e) => {
            canvas.classList.toggle('sb-grid');
            e.currentTarget.classList.toggle('is-active', canvas.classList.contains('sb-grid'));
        });
        /* ---------- undo / redo ----------
           A shared board makes this personal: undo takes back MY last stroke,
           never a teammate's, so two people working at once never pull the rug
           out from under each other. Redo is a session trail of what I took
           back; drawing again abandons it, the way it does in every editor. */
        const myUndone = [];
        let undoBusy = false, rebuildSoon = null;
        function scheduleRebuild() {
            if (rebuildSoon) return;
            rebuildSoon = setTimeout(() => { rebuildSoon = null; rebuildPage(); }, 120);
        }
        function paintUndoBtns() {
            const r = document.getElementById('sbRedo');
            if (r) r.disabled = !myUndone.length || undoBusy;
        }
        function dropRedo() { if (myUndone.length) { myUndone.length = 0; paintUndoBtns(); } }
        async function stepUndo(redo) {
            if (undoBusy || (redo && !myUndone.length)) return;
            undoBusy = true; paintUndoBtns();
            try {
                const body = { page: currentPage, redo: redo ? 1 : 0 };
                if (redo) body.id = myUndone[myUndone.length - 1];
                const r = await api(`${U.undo}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body });
                const id = r && r.data ? r.data.id : null;
                if (redo) myUndone.pop();
                else if (id) myUndone.push(id);
                else { toast('Nothing of yours to undo on this page.'); return; }
                await rebuildPage();
            } catch (err) {
                // A redo the board can no longer honour (someone cleared the
                // page) is dropped rather than left to fail again.
                if (redo) myUndone.pop();
                toast(err.message || 'Could not do that.', 'error');
            } finally { undoBusy = false; paintUndoBtns(); }
        }
        document.getElementById('sbUndo').addEventListener('click', () => stepUndo(false));
        document.getElementById('sbRedo').addEventListener('click', () => stepUndo(true));

        document.getElementById('sbClear').addEventListener('click', async () => {
            const ok = (typeof confirmAction === 'function')
                ? await confirmAction({ title: 'Clear the page?', message: 'This clears the current whiteboard page for everyone on the team.', confirmText: 'Clear' })
                : confirm('Clear the page for everyone?');
            if (!ok) return;
            dropRedo();
            clearCanvas();
            api(`${U.push}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { type: 'clear', page: currentPage } })
                .then((r) => { const id = r && r.data && r.data.event && r.data.event.id; if (id) { rendered.add(id); if (id > lastId) lastId = id; } })
                .catch(() => {});
        });
        document.getElementById('sbChatToggle').addEventListener('click', (e) => {
            const side = document.getElementById('sbSide');
            const willShow = side.classList.contains('collapsed');
            side.classList.toggle('collapsed', !willShow);
            e.currentTarget.classList.toggle('is-active', willShow);
        });

        /* ---------- pages + save-to-notes ---------- */
        document.getElementById('sbPagePrev').addEventListener('click', () => {
            const idx = pages.findIndex((p) => p.page === currentPage);
            if (idx > 0) gotoPage(pages[idx - 1].page);
        });
        document.getElementById('sbPageNext').addEventListener('click', () => {
            const idx = pages.findIndex((p) => p.page === currentPage);
            if (idx >= 0 && idx < pages.length - 1) gotoPage(pages[idx + 1].page);
        });
        document.getElementById('sbPageAdd').addEventListener('click', async () => {
            try {
                const r = await api(`${U.pageCreate}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: {} });
                if (r && r.data) { if (Array.isArray(r.data.pages)) pages = r.data.pages; await gotoPage(r.data.page); }
            } catch (_) { if (typeof toast === 'function') toast('Could not add a page.', 'error'); }
        });
        /* ---------- save-to-notes modal ---------- */
        const saveModal = document.getElementById('sbSaveModal');
        function openSaveModal() {
            document.getElementById('sbSaveTitle').value = '';
            document.getElementById('sbSaveDesc').value = '';
            const n = pages.length;
            document.getElementById('sbSavePageCount').textContent = n <= 1 ? 'this page' : ('all ' + n + ' pages');
            saveModal.classList.remove('hidden');
            setTimeout(() => document.getElementById('sbSaveTitle').focus(), 30);
        }
        function closeSaveModal() { saveModal.classList.add('hidden'); }
        document.getElementById('sbSaveNotes').addEventListener('click', openSaveModal);
        document.getElementById('sbSaveCancel').addEventListener('click', closeSaveModal);
        document.getElementById('sbSaveCancelX').addEventListener('click', closeSaveModal);
        saveModal.addEventListener('click', (e) => { if (e.target === saveModal) closeSaveModal(); });
        // Escape closes the modal (capture, so it beats the board's own Esc handler).
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !saveModal.classList.contains('hidden')) { e.stopPropagation(); closeSaveModal(); }
        }, true);
        document.getElementById('sbSaveConfirm').addEventListener('click', async () => {
            const btn = document.getElementById('sbSaveConfirm');
            if (btn.disabled) return;
            btn.disabled = true;
            document.getElementById('sbSaveSpin').classList.remove('hidden');
            document.getElementById('sbSaveLabel').textContent = 'Saving…';
            try {
                const images = await exportAllPages();
                const title = document.getElementById('sbSaveTitle').value.trim();
                const description = document.getElementById('sbSaveDesc').value.trim();
                const r = await api(`${U.saveNotes}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { images, title, description } });
                if (typeof toast === 'function') toast((r && r.message) || 'Saved to the schedule notebook.', 'success');
                closeSaveModal();
            } catch (err) {
                if (typeof toast === 'function') toast('Could not save: ' + ((err && err.message) || 'error'), 'error');
            } finally {
                btn.disabled = false;
                document.getElementById('sbSaveSpin').classList.add('hidden');
                document.getElementById('sbSaveLabel').textContent = 'Save to notes';
            }
        });
        document.getElementById('sbClose').addEventListener('click', close);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !overlay.classList.contains('hidden')) close(); });

        /* ---------- realtime + reconcile ---------- */
        async function reconcile() {
            try {
                const r = await api(`${U.events}?scheduleId=${SCHEDULE_ID}&page=${currentPage}&after=${lastId}`);
                (r.data.events || []).forEach((ev) => applyEvent(ev, false));
            } catch (_) { /* transient */ }
        }
        function startRealtime() {
            const configured = !!window.Echo;
            const liveEl = document.getElementById('sbLive');
            // Reflect the socket's real state, not just that Echo was built —
            // it starts 'connecting', so the badge settles on the next tick.
            const paintBadge = () => {
                const live = window.realtimeReady?.() ?? false;
                liveEl.textContent = live ? '● Live' : '● Syncing';
                liveEl.classList.toggle('on', live);
            };
            paintBadge();
            if (configured) {
                try {
                    channel = window.Echo.private('schedule-board.' + SCHEDULE_ID);
                    // Only paint strokes that belong to the page I'm looking at.
                    channel.listen('.stroke', (ev) => { if ((ev.page || 1) === currentPage) applyEvent(ev, false); });
                    // Keep the page strip in sync when a teammate adds a page.
                    channel.listen('.board.page', (ev) => {
                        if (!ev || !Array.isArray(ev.pages)) return;
                        pages = ev.pages;
                        renderPageBar();
                        // A new session started, or someone reopened a past
                        // drawing: the canvas underneath us is a different one
                        // now, so repaint rather than drawing onto a ghost.
                        if (ev.action === 'reset') { currentPage = 1; renderPageBar(); rebuildPage(); }
                    });
                } catch (_) { channel = null; }
            }
            // Re-decide the cadence every tick instead of freezing it at load:
            // back off to 4s only while the socket is genuinely delivering, and
            // fall to 1s the moment it drops. Previously a key that could never
            // connect still counted as "live" and slowed sync to 4s — no push
            // AND lazy polling, which is what made the board feel dead.
            const tick = async () => {
                await reconcile();
                paintBadge();
                pollTimer = setTimeout(tick, window.realtimeReady?.() ? 4000 : 1000);
            };
            pollTimer = setTimeout(tick, 1000);
        }
        function stopRealtime() {
            if (channel) { try { window.Echo.leave('schedule-board.' + SCHEDULE_ID); } catch (_) {} channel = null; }
            if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
            // Stop claiming the room, so the next person to open it gets a
            // fresh canvas instead of being treated as joining us.
            if (beatTimer) { clearTimeout(beatTimer); beatTimer = null; }
        }

        /* ---------- shared: rebuild board state + go live ---------- */
        let started = false;
        // Repaint the current page from scratch (initial mount, page switch, resize).
        async function rebuildPage() {
            rendered.clear(); lastId = 0; clearCanvas();
            try {
                const r = await api(`${U.events}?scheduleId=${SCHEDULE_ID}&page=${currentPage}&after=0`);
                (r.data.events || []).forEach((ev) => applyEvent(ev, true));
                if (r.data.maxId > lastId) lastId = r.data.maxId;
            } catch (_) { /* start blank */ }
        }
        async function gotoPage(pg) {
            if (pg === currentPage) { renderPageBar(); return; }
            currentPage = pg;
            renderPageBar();
            await rebuildPage();
        }
        /* ---------- drawing sessions: fresh canvas, past drawings ---------- */
        const $ = (id) => document.getElementById(id);
        let beatTimer = null;

        // Tell the server we are in the room. It replies whether we got a fresh
        // page 1 (room was empty, previous drawing archived) or joined a canvas
        // someone is already working on — which must never be wiped by arriving.
        async function openSession() {
            try {
                const r = await api(`${U.open}?scheduleId=${SCHEDULE_ID}`, { method: 'POST' });
                setDraftCount(r.data.draftCount || 0);
                const a = r.data.archived;
                if (a && a.isDraft && window.toast) {
                    toast('Previous drawing kept under Past drawings.', 'success');
                }
            } catch (_) { /* board still works; it just will not have reset */ }
        }

        function startHeartbeat() {
            const beat = async () => {
                try { await api(`${U.heartbeat}?scheduleId=${SCHEDULE_ID}`, { method: 'POST' }); } catch (_) {}
                beatTimer = setTimeout(beat, 20000);
            };
            beatTimer = setTimeout(beat, 20000);
        }

        function setDraftCount(n) {
            const el = $('sbDraftCount');
            if (!el) return;
            el.textContent = n;
            el.classList.toggle('hidden', !n);
        }

        // Paint a draft's strokes into a small canvas using the board's own
        // renderer, so the thumbnail is the drawing, not a stale snapshot of it.
        function paintPreview(cv, strokes) {
            const g = cv.getContext('2d');
            const w = cv.width, h = cv.height;
            g.fillStyle = '#fff'; g.fillRect(0, 0, w, h);
            (strokes.events || []).filter((e) => (e.page || 1) === 1).forEach((e) => {
                const pts = (e.points || []).map((p) => ({ x: p[0] * w, y: p[1] * h }));
                if (!pts.length) return;
                g.save();
                g.strokeStyle = e.mode === 'eraser' ? '#fff' : (e.color || '#111827');
                g.lineWidth = Math.max(0.6, (e.width || 4) * (w / 1280));
                g.lineCap = 'round'; g.lineJoin = 'round';
                g.beginPath(); g.moveTo(pts[0].x, pts[0].y);
                pts.slice(1).forEach((p) => g.lineTo(p.x, p.y));
                g.stroke(); g.restore();
            });
        }

        async function showDrafts() {
            const list = $('sbDraftsList');
            list.innerHTML = '<p class="sb-drafts-empty">Loading…</p>';
            $('sbDraftsModal').classList.remove('hidden');
            try {
                const r = await api(`${U.drafts}?scheduleId=${SCHEDULE_ID}`);
                const drafts = r.data.drafts || [];
                setDraftCount(drafts.length);
                if (!drafts.length) {
                    list.innerHTML = '<p class="sb-drafts-empty">No past drawings yet. When everyone leaves the room, whatever is on the board is kept here.</p>';
                    return;
                }
                list.innerHTML = '';
                drafts.forEach((d) => {
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.className = 'sb-draft';
                    card.innerHTML = `<canvas width="300" height="180"></canvas>
                        <div class="sb-draft-meta">
                            <span class="sb-draft-title">${escapeHtml(d.title || 'Drawing')}</span>
                            <span class="sb-draft-sub">${escapeHtml(d.archivedAt || '')} · ${d.pageCount} page${d.pageCount === 1 ? '' : 's'}</span>
                        </div>`;
                    paintPreview(card.querySelector('canvas'), d.strokes || {});
                    card.addEventListener('click', () => openDraft(d.id));
                    list.appendChild(card);
                });
            } catch (e) {
                list.innerHTML = `<p class="sb-drafts-empty">${escapeHtml(e.message || 'Could not load past drawings.')}</p>`;
            }
        }

        async function openDraft(id) {
            try {
                const r = await api(`${U.draftOpen}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { id } });
                $('sbDraftsModal').classList.add('hidden');
                if (window.toast) toast(r.message || 'Drawing opened.', 'success');
                currentPage = 1;
                await loadPages();
                await rebuildPage();
            } catch (e) {
                if (window.toast) toast(e.message || 'Could not open that drawing.', 'error');
            }
        }

        $('sbDrafts')?.addEventListener('click', showDrafts);
        $('sbDraftsX')?.addEventListener('click', () => $('sbDraftsModal').classList.add('hidden'));
        $('sbDraftsModal')?.addEventListener('click', (e) => { if (e.target === $('sbDraftsModal')) $('sbDraftsModal').classList.add('hidden'); });

        async function startBoard() {
            await openSession();   // decides fresh-vs-join before anything paints
            startHeartbeat();
            await loadPages();
            resizeCanvas();
            await rebuildPage();
            startRealtime();   // subscribe once; page filtering happens in the handlers
            observeSize();     // keep filling the container on resize
        }

        /* ---------- overlay open / close (used from the team chat) ---------- */
        async function open() {
            overlay.classList.remove('hidden');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            // Dock the team chat as a sidebar so you can chat while drawing.
            const side = document.getElementById('sbSide');
            const narrow = window.innerWidth < 768;
            side.classList.toggle('collapsed', narrow);
            document.getElementById('sbChatToggle').classList.toggle('is-active', !narrow);
            if (typeof window.teamChatDock === 'function') window.teamChatDock(side);

            await startBoard();
        }
        function close() {
            if (typeof window.teamChatUndock === 'function') window.teamChatUndock();
            stopRealtime();
            overlay.classList.add('hidden');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
        window.openScheduleBoard = open;

        /* ---------- inline mount (Collab Room "Drawing" tab) ---------- */
        window.mountScheduleBoard = (container) => {
            if (!container) return;
            container.appendChild(overlay);
            overlay.classList.remove('hidden');
            overlay.classList.add('sb-inline');
            overlay.setAttribute('aria-hidden', 'false');
            // Inline mode drops the overlay-only chrome: close, and the chat
            // sidebar (chat is its own tab in the Collab Room).
            document.getElementById('sbClose')?.classList.add('sb-hide');
            document.getElementById('sbChatToggle')?.classList.add('sb-hide');
            document.getElementById('sbSide')?.classList.add('sb-hide');
            if (!started) { started = true; startBoard(); }
        };
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endif
