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
        {{-- One labelled menu instead of a row of mystery glyphs: each tool
             carries its name, and the button always shows what is active. --}}
        {{-- A bottom sheet, not a dropdown: the bar is an overflow-x scroller,
             and a scroll container clips BOTH axes — a dropdown nested in it
             opened invisibly. openSheet re-parents the sheet to <body>, where
             nothing can clip it. --}}
        {{-- Bigger, filled, and no longer a hamburger: there are two other
             hamburgers within an inch of this one (the page's and the
             shell's), and three identical glyphs meaning three different
             things is how a tap goes to the wrong place. --}}
        <button type="button" class="cmap-tool cmap-menu-btn" id="cmapToolsBtn" aria-haspopup="true">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4"/></svg>
            <span class="cmap-menu-lead">Tools</span>
            <span id="cmapToolLabel">Move map</span>
            <svg class="cmap-menu-caret" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="sheet hidden" id="cmapToolsSheet" style="--sheet-width:22rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Map tools</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body cmap-menu-body">
                <button type="button" class="cmap-mrow is-active" data-mtool="pan" data-short="Move map">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M2 12h20M12 2L9 5m3-3l3 3M12 22l-3-3m3 3l3-3M2 12l3-3m-3 3l3 3M22 12l-3-3m3 3l-3 3"/></svg>
                    <span>Move map</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="edit" data-short="Select">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4l7 16 2-6 6-2z"/></svg>
                    <span>Select &amp; edit a shape</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="pen" data-short="Pen">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L18 9l-3-3L5 16l-1 4z"/></svg>
                    <span>Freehand draw</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="line" data-short="Line">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 21L21 3M8.5 15.5l1.8 1.8M12 12l1.8 1.8M15.5 8.5l1.8 1.8"/></svg>
                    <span>Line — drag, shows distance</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="arrow" data-short="Arrow">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20L18 6M18 6h-7M18 6v7"/></svg>
                    <span>Arrow — drag to point at</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="path" data-short="Multi-line">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l5-6 4 3 6-8"/><path stroke-linecap="round" d="M3 17h.01M8 11h.01M12 14h.01M18 6h.01"/></svg>
                    <span>Multi-line — tap points, tap the 1st to close</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="rect" data-short="Box">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="1.5"/></svg>
                    <span>Box — drag, sides + area</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="area" data-short="Area">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 6-3 10H7L4 9l8-6z"/></svg>
                    <span>Area — tap corners, hectares</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="text" data-short="Text">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 6h14M12 6v13M9 19h6"/></svg>
                    <span>Text label</span>
                </button>
                <button type="button" class="cmap-mrow" data-mtool="erase" data-short="Erase">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l7-7 6 6-4 4H8l-4-3z"/><path stroke-linecap="round" d="M8 18h11"/></svg>
                    <span>Erase a shape</span>
                </button>
                <div class="cmap-msep"></div>
                <button type="button" class="cmap-mrow" data-maction="open">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    <span>Open a saved map</span>
                </button>
                <button type="button" class="cmap-mrow" data-maction="savemap">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/><path stroke-linecap="round" d="M9 8h6M9 12h6M9 16h4"/></svg>
                    <span>Save map to notes</span>
                </button>
                <button type="button" class="cmap-mrow" data-maction="saveimage">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-4.5-4.5L9 18"/></svg>
                    <span>Save as image note</span>
                </button>
            </div>
        </div>
        <button type="button" class="cmap-tool" id="cmapSearchBtn" title="Search a place" aria-label="Search a place">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
        </button>
        <div class="sheet hidden" id="cmapSearchSheet" style="--sheet-width:26rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Find a place</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body" style="padding-bottom:1rem">
                <input type="search" id="cmapSearch" class="form-input" placeholder="Town, barangay, landmark…" autocomplete="off">
            </div>
        </div>
        <button type="button" class="cmap-tool" id="cmapColorBtn" title="Drawing colour" aria-label="Choose drawing colour">
            <span class="cmap-color-dot" id="cmapColorDot"></span>
        </button>
        <div class="sheet hidden" id="cmapColorSheet" style="--sheet-width:22rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Drawing colour</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body cmap-swatches" style="padding-bottom:1.1rem">
                <button type="button" class="cmap-swatch is-active" data-mcolor="#f5c518" style="--c:#f5c518" aria-label="Yellow"></button>
                <button type="button" class="cmap-swatch" data-mcolor="#ffffff" style="--c:#ffffff" aria-label="White"></button>
                <button type="button" class="cmap-swatch" data-mcolor="#ef4444" style="--c:#ef4444" aria-label="Red"></button>
                <button type="button" class="cmap-swatch" data-mcolor="#22c55e" style="--c:#22c55e" aria-label="Green"></button>
                <button type="button" class="cmap-swatch" data-mcolor="#3b82f6" style="--c:#3b82f6" aria-label="Blue"></button>
                <button type="button" class="cmap-swatch" data-mcolor="#a855f7" style="--c:#a855f7" aria-label="Purple"></button>
                <button type="button" class="cmap-swatch" data-mcolor="#111827" style="--c:#111827" aria-label="Black"></button>
            </div>
        </div>
        <div class="sheet hidden" id="cmapSavesSheet" style="--sheet-width:26rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Saved team maps</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body" style="padding-bottom:1rem">
                {{-- A season builds up maps. Finding one by name beats reading
                     the whole list, and the count says whether the search
                     found anything without a second look. --}}
                <div class="cmap-savesearch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="text" id="cmapSaveSearch" placeholder="Search saved maps…" autocomplete="off">
                    <span id="cmapSaveCount"></span>
                </div>
                <div class="cmap-saves" id="cmapSavesList"></div>
            </div>
        </div>
        <div class="sheet hidden" id="cmapSaveSheet" style="--sheet-width:26rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title" id="cmapSaveTitleH">Save map to notes</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body" style="padding-bottom:1.1rem">
                <p class="cmap-save-hint" id="cmapSaveHint"></p>
                <label class="cmap-save-label" for="cmapSaveName">Title</label>
                <input type="text" id="cmapSaveName" class="form-input" placeholder="e.g. North lot irrigation plan" autocomplete="off">
                <label class="cmap-save-label" for="cmapSaveDesc">What is this map about? (optional)</label>
                <textarea id="cmapSaveDesc" class="form-textarea" rows="3"></textarea>
                {{-- Shown only when the shapes on screen came from a saved
                     map: the usual answer is "this one, changed". --}}
                <button type="button" class="cmap-save-go cmap-save-over" id="cmapSaveOver" hidden><span id="cmapSaveOverLabel">Save over this map</span></button>
                <button type="button" class="cmap-save-go" id="cmapSaveGo"><span id="cmapSaveGoTxt">Save as a new map</span></button>
            </div>
        </div>
        <span class="cmap-div"></span>
        <button type="button" class="cmap-tool" id="cmapUndo" disabled title="Undo" aria-label="Undo">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg>
        </button>
        <button type="button" class="cmap-tool" id="cmapRedo" disabled title="Redo" aria-label="Redo">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4"/></svg>
        </button>
        <button type="button" class="cmap-tool cmap-finish" id="cmapFinish" hidden title="Finish and save this shape">
            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span>Finish</span>
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
    <div class="cmap-stage">
        <div class="cmap-map" id="cmapMap"></div>
        <div class="cmap-veil" id="cmapVeil">
            <span class="cmap-veil-spin"></span>
            <span class="cmap-veil-txt">Finding your ground…</span>
        </div>
        <div class="cmap-editbar hidden" id="cmapEditBar">
            <span class="cmap-editbar-lbl" id="cmapEditLbl">Editing</span>
            <button type="button" id="cmapDelPoint" hidden>Delete point</button>
            <button type="button" id="cmapDelObj">Delete shape</button>
            <button type="button" id="cmapEditDone">Done</button>
        </div>
    </div>
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
    .cmap-tool.is-active,
    html.dark .cmap-tool.is-active { background: var(--color-brand-100); color: var(--color-brand-800); }
    .cmap-tool:disabled { opacity: .35; pointer-events: none; }
    /* Cooperative mode keeps two-finger pinch alive while drawing, but its
       "use two fingers to move the map" scrim would flash over every stroke —
       one finger here IS the tool, not a mistake. */
    .cmap-map .gm-style-moc { display: none !important; }
    .cmap-color-dot { width: 1.05rem; height: 1.05rem; border-radius: 999px; background: #f5c518;
        border: 2px solid rgb(255 255 255 / .9); box-shadow: 0 0 0 1px rgb(0 0 0 / .12); }
    .cmap-swatches { display: flex; flex-wrap: wrap; gap: .55rem; }
    .cmap-swatch { width: 2.1rem; height: 2.1rem; border-radius: 999px; background: var(--c);
        border: 2px solid rgb(0 0 0 / .08); transition: transform .15s ease, box-shadow .15s ease; }
    .cmap-swatch.is-active { box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-500); transform: scale(1.05); }
    .cmap-finish { background: var(--color-brand-600); color: #fff; width: auto; padding: 0 .6rem; gap: .3rem; font-size: .78rem; font-weight: 800; }
    .cmap-finish:hover { background: var(--color-brand-700); color: #fff; }
    .cmap-danger { color: #dc2626; }
    .cmap-danger:hover { background: #fee2e2; color: #b91c1c; }
    .cmap-div { width: 1px; align-self: stretch; background: var(--color-gray-200); flex-shrink: 0; }
    .cmap-menu-wrap { position: relative; flex-shrink: 0; }
    /* The one control that opens everything else, so it looks like it:
       filled, taller than the tool chips beside it, and it names both what it
       is (Tools) and what is currently active. */
    .cmap-menu-btn { width: auto; padding: 0 .75rem; gap: .4rem; font-size: .8rem; font-weight: 800;
        height: 2.6rem; background: #4a7c2a; color: #fff; border-color: #4a7c2a;
        box-shadow: 0 6px 16px -8px rgb(61 104 35 / .9); }
    .cmap-menu-btn:hover { background: #3d6823; border-color: #3d6823; color: #fff; }
    .cmap-menu-btn svg { width: 1.15rem; height: 1.15rem; }
    .cmap-menu-lead { font-size: .62rem; text-transform: uppercase; letter-spacing: .06em;
        opacity: .75; padding-right: .3rem; border-right: 1px solid rgb(255 255 255 / .35); }
    .cmap-menu-caret { width: .85rem !important; height: .85rem !important; opacity: .8; }
    .cmap-menu { position: absolute; top: calc(100% + .35rem); left: 0; z-index: 40; min-width: 15.5rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: .8rem;
        box-shadow: 0 14px 34px -10px rgb(0 0 0 / .3); padding: .3rem; }
    .cmap-menu.hidden { display: none; }
    .cmap-menu-body { display: flex; flex-direction: column; gap: .15rem; padding-bottom: .6rem; }
    .cmap-mrow { display: flex; align-items: center; gap: .55rem; width: 100%; text-align: left;
        padding: .5rem .6rem; border-radius: .55rem; font-size: .82rem; font-weight: 700; color: var(--color-gray-700); }
    .cmap-mrow svg { width: 1.05rem; height: 1.05rem; flex-shrink: 0; }
    .cmap-mrow:hover { background: var(--color-gray-50); }
    .cmap-mrow.is-active,
    html.dark .cmap-mrow.is-active { background: var(--color-brand-50); color: var(--color-brand-800); }
    .cmap-search { flex: 1 1 8rem; min-width: 6rem; height: 2.15rem; border: 1px solid var(--color-gray-200);
        border-radius: .6rem; padding: 0 .6rem; font-size: .8rem; background: var(--color-white); color: inherit; }
    html.dark .cmap-menu { background: #151b12; border-color: #2b3a1c; }
    html.dark .cmap-mrow { color: #cdd8c0; }
    html.dark .cmap-mrow:hover { background: #1c2416; }
    html.dark .cmap-search { background: #1c2416; border-color: #2b3a1c; }
    .cmap-stage { position: relative; flex: 1 1 auto; min-height: 0; display: flex; }
    .cmap-map { flex: 1 1 auto; min-height: 0; }
    /* Holds the screen until the map knows WHERE to look — no country-level
       flash and jump to the field. */
    .cmap-veil { position: absolute; inset: 0; z-index: 5; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .65rem;
        background: #161d16; color: #cbd5c0; font-size: .8rem; font-weight: 700;
        transition: opacity .45s cubic-bezier(.22,1,.36,1); }
    .cmap-veil.is-done { opacity: 0; pointer-events: none; }
    .cmap-veil-spin { width: 1.7rem; height: 1.7rem; border-radius: 999px;
        border: 3px solid rgb(255 255 255 / .14); border-top-color: #f5c518;
        animation: cmapVeilSpin .8s linear infinite; }
    @keyframes cmapVeilSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .cmap-veil { transition: opacity .01s linear; }
        .cmap-veil-spin { animation: none; border-top-color: rgb(255 255 255 / .14); }
    }
    .cmap-msep { height: 1px; background: var(--color-gray-100); margin: .35rem .2rem; }
    .cmap-save-hint { font-size: .78rem; color: var(--color-gray-500); line-height: 1.5; margin-bottom: .8rem; }
    .cmap-save-label { display: block; font-size: .72rem; font-weight: 700; color: var(--color-gray-600); margin: .6rem 0 .3rem; }
    .cmap-save-go { width: 100%; margin-top: .9rem; padding: .6rem; border-radius: .7rem; font-weight: 800;
        font-size: .88rem; color: #fff; background: linear-gradient(140deg, #6b9f3d, #3d6823); }
    .cmap-save-go:disabled { opacity: .6; }
    .cmap-saves { display: flex; flex-direction: column; gap: .45rem; }
    .cmap-saverow { text-align: left; border: 1px solid var(--color-gray-200); border-radius: .8rem;
        padding: .6rem .75rem; background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cmap-saverow:hover { border-color: var(--color-brand-400); transform: translateY(-1px); }
    .cmap-saverow-t { display: block; font-size: .85rem; font-weight: 800; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cmap-saverow-s { display: block; font-size: .7rem; color: var(--color-gray-500); margin-top: .1rem; }
    .cmap-savesearch { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; padding: .5rem .7rem;
        border-radius: .7rem; border: 1px solid var(--color-gray-200); background: var(--color-white); }
    .cmap-savesearch svg { width: 1rem; height: 1rem; color: var(--color-gray-400); flex-shrink: 0; }
    .cmap-savesearch input { flex: 1 1 auto; min-width: 0; border: 0; outline: none; background: transparent;
        font-size: .88rem; color: var(--color-gray-900); }
    .cmap-savesearch span { font-size: .7rem; font-weight: 700; color: var(--color-gray-400); white-space: nowrap; }
    .cmap-saverow { display: flex; align-items: flex-start; gap: .6rem; width: 100%; text-align: left;
        padding: .55rem; border-radius: .75rem; }
    .cmap-save-over { background: #4a7c2a !important; color: #fff !important; margin-bottom: .5rem; }
    .cmap-save-over:hover { background: #3d6823 !important; }
    .cmap-mark { width: 2.6rem; height: 2.6rem; border-radius: .7rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #e4efd4; color: #3d6823; }
    .cmap-mark.is-team { background: #dbeafe; color: #1d4ed8; }
    .cmap-mark svg { width: 1.3rem; height: 1.3rem; }
    .cmap-thumb { width: 3.4rem; height: 2.6rem; border-radius: .5rem; overflow: hidden; flex-shrink: 0;
        background: var(--color-gray-100); }
    .cmap-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .cmap-saverow-main { min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
    .cmap-tags { display: flex; flex-wrap: wrap; gap: .25rem; }
    .cmap-tag { font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .02em;
        padding: .1rem .4rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-500); }
    .cmap-tag.is-team { background: #dbeafe; color: #1d4ed8; }
    .cmap-tag.is-solo { background: #dcfce7; color: #15803d; }
    .cmap-tag.is-warn { background: #fef3c7; color: #92400e; }
    html.dark .cmap-savesearch { background: #141a10; border-color: #2b3a1c; }
    html.dark .cmap-savesearch input { color: #e6eddd; }
    html.dark .cmap-tag { background: #1c2416; color: #a9b89b; }
    .cmap-tag.is-note { background: #e4efd4; color: #3d6823; text-decoration: none; }
    .cmap-tag.is-note:hover { background: #d3e7bb; }
    .cmap-saves-empty { font-size: .8rem; color: var(--color-gray-500); text-align: center; padding: 1.2rem 0; }
    /* Select-tool action bar: floats over the map while a shape is held. */
    .cmap-editbar { position: absolute; left: 50%; bottom: .85rem; transform: translateX(-50%) translateY(0);
        z-index: 6; display: flex; align-items: center; gap: .4rem; padding: .4rem .55rem; border-radius: 999px;
        background: var(--color-white); box-shadow: 0 10px 30px rgb(0 0 0 / .25);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cmap-editbar.hidden { display: flex; opacity: 0; transform: translateX(-50%) translateY(.6rem); pointer-events: none; }
    .cmap-editbar-lbl { font-size: .72rem; font-weight: 800; color: var(--color-gray-500); padding: 0 .2rem 0 .45rem; white-space: nowrap; }
    .cmap-editbar button { font-size: .75rem; font-weight: 800; padding: .34rem .7rem; border-radius: 999px; white-space: nowrap; }
    #cmapDelPoint, #cmapDelObj { background: #fee2e2; color: #b91c1c; }
    #cmapEditDone { background: var(--color-gray-100); color: var(--color-gray-700); }
    @media (prefers-reduced-motion: reduce) { .cmap-editbar { transition: none; } }
    /* Measurement labels ride Google marker labels — these classes style them. */
    .cmap-lbl-g { background: rgb(17 24 39 / .82); border-radius: .45rem; padding: .1rem .4rem; white-space: nowrap; }
    .cmap-txt-g { background: #fff; border: 1.5px solid #111827; border-radius: .45rem; padding: .12rem .45rem; box-shadow: 0 2px 6px rgb(0 0 0 / .25); }
    .cmap-me-g { background: rgb(17 24 39 / .82); border-radius: .45rem; padding: .05rem .35rem; }
    /* Live-position dots are HTML overlays, not markers: markers cannot
       ripple, and two stacked markers is what buried the name UNDER the dot.
       Here the name hangs below and the ring breathes. */
    .cmap-dot-wrap { position: absolute; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: .18rem; pointer-events: none; }
    .cmap-dot { position: relative; width: 16px; height: 16px; border-radius: 999px; background: var(--dot, #16a34a); border: 2.5px solid #fff; box-shadow: 0 1px 4px rgb(0 0 0 / .45); }
    .cmap-dot::after { content: ''; position: absolute; inset: -3px; border-radius: 999px; border: 2px solid var(--dot, #16a34a); animation: cmapRipple 1.6s ease-out infinite; }
    @keyframes cmapRipple { 0% { transform: scale(1); opacity: .9; } 100% { transform: scale(2.6); opacity: 0; } }
    .cmap-dot-name { background: rgb(17 24 39 / .82); color: #fff; border-radius: .45rem; padding: .05rem .4rem; font-size: 10px; font-weight: 800; white-space: nowrap; }
    @media (prefers-reduced-motion: reduce) {
        .cmap-dot::after { animation: cmapBreathe 2.2s ease-in-out infinite; }
        @keyframes cmapBreathe { 0%, 100% { opacity: .2; transform: scale(1.5); } 50% { opacity: .7; transform: scale(1.5); } }
    }
    html.dark .cmap-bar { border-color: #2b3a1c; }
    html.dark .cmap-tool { background: #1c2416; color: #cdd8c0; }
    html.dark .cmap-tool:hover { background: #243019; }
</style>

@if ($cmapKey)
<script>
(() => {
    if (window.initCollabMap) return;
    const SID = {{ (int) $schedule->id }};
    // This file had always reached for the global escapeHtml; the saved-map
    // list I added called it esc, which exists in the other partials and never
    // here — so opening the list threw before it drew a single row.
    const esc = window.escapeHtml || ((v) => String(v == null ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
    const ME = {{ (int) auth()->id() }};
    const KEY = @json($cmapKey);
    const URLS = {
        objects: @json(route('sm.map')),
        push: @json(route('sm.map.push')),
        update: @json(route('sm.map.update')),
        remove: @json(route('sm.map.remove')),
        clear: @json(route('sm.map.clear')),
        loc: @json(route('sm.map.loc')),
        trace: @json(route('sm.map.trace')),
        saves: @json(route('sm.map.saves')),
        basemap: @json(route('sm.map.basemap')),
        save: @json(route('sm.map.save')),
        load: @json(route('sm.map.load')),
    };
    let map = null, proj = null, satOn = true;
    let tool = 'pan', color = '#f5c518', width = 3;
    let tempPts = [], tempShape = null;
    const layers = new Map();       // object id -> array of google overlays
    const locMarks = new Map();     // userId -> { parts, at }
    const G = () => window.google.maps;

    const hue = (uid) => 'hsl(' + ((uid * 137) % 360) + ', 70%, 45%)';
    const fmtM = (m) => m < 1000 ? m.toFixed(2) + ' m' : (m / 1000).toFixed(2) + ' km';
    const fmtA = (m2) => m2 < 10000
        ? m2.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' m²'
        : (m2 / 10000).toFixed(2) + ' ha';
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
    /* Finished shapes keep the same pins the drawing had — grab one and the
       shape reshapes live under it, saving for the whole team on release.
       A dragged box corner re-derives the box from the opposite corner. */
    function vertexPins(parts, o, pts, colorStr) {
        pts.forEach((p, i) => {
            const m = new (G().Marker)({
                map, position: LL(p), draggable: true, crossOnDrag: false,
                icon: pinIcon(colorStr, 1.2),
            });
            m.addListener('drag', (ev) => {
                const path = parts[0].getPath ? parts[0].getPath() : null;
                if (path) path.setAt(i, ev.latLng);
            });
            m.addListener('dragend', async (ev) => {
                const q = [ev.latLng.lat(), ev.latLng.lng()];
                const cur = objIndex.get(o.id) || o;
                if (o.kind === 'rect') {
                    // A dragged corner walks free — square no more, polygon
                    // still: the box converts to an area with that corner moved.
                    const corners = pts.map((pp, j) => (j === i ? q : pp));
                    try {
                        const res = await api(`${URLS.push}?scheduleId=${SID}`, {
                            method: 'POST', body: { kind: 'area', points: corners, color: cur.color, width: cur.width, label: cur.label },
                        });
                        renderObject(res.data.object);
                        pushHist({ type: 'add', object: res.data.object });
                        pushHist({ type: 'remove', object: cur });
                        await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: cur.id } }).catch(() => {});
                        dropObject(cur.id);
                    } catch (e) { if (window.toast) toast(e.message, 'error'); }
                    return;
                }
                const np = cur.points.map((pp, j) => (j === i ? q : pp));
                try {
                    const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: o.id, points: np } });
                    pushHist({ type: 'update', id: o.id, before: o.points, after: res.data.object.points });
                    dropObject(o.id);
                    renderObject(res.data.object);
                } catch (e) { if (window.toast) toast(e.message, 'error'); }
            });
            // Hold a pin (~half a second, without dragging) and it becomes
            // the live end of the shape: taps on the map grow it from there,
            // a tap on ANOTHER pin closes the ring into an area.
            let lpTimer = null;
            m.addListener('mousedown', () => {
                clearTimeout(lpTimer);
                lpTimer = setTimeout(() => beginExtend(o, i, m), 550);
            });
            m.addListener('mouseup', () => clearTimeout(lpTimer));
            m.addListener('dragstart', () => {
                clearTimeout(lpTimer);
                if (extending && extending.id === o.id && extending.index === i) cancelExtend();
            });
            m.addListener('click', () => {
                if (extending && extending.id === o.id) {
                    if (Date.now() - extending.at < 700) return;  // the long-press's own tail
                    if (extending.index === i) { cancelExtend(); if (window.toast) toast('Done drawing from that point.'); return; }
                    closeExtendInto(o);
                    return;
                }
                if (tool === 'edit') selectVertex(o, i, m, parts);
            });
            if (pendingExtend && pendingExtend.id === o.id && pendingExtend.index === i) {
                pendingExtend = null;
                beginExtend(o, i, m, true);
            }
            parts.push(m);
        });
    }
    let extending = null, pendingExtend = null;
    function beginExtend(o, i, marker, quiet) {
        if (o.kind === 'rect' || o.kind === 'area') return;       // already closed shapes
        cancelExtend();
        extending = { id: o.id, index: i, marker, color: o.color || '#f5c518', at: Date.now() };
        marker.setIcon(pinIcon(extending.color, 1.6));
        marker.setZIndex(9999);
        if (!quiet && window.toast) toast('Drawing from this point — tap the map to add, tap another point to close.');
    }
    function cancelExtend() {
        if (!extending) return;
        try { extending.marker.setIcon(pinIcon(extending.color, 1.2)); extending.marker.setZIndex(null); } catch (_) {}
        extending = null;
    }
    async function extendTo(latLng) {
        const cur = objIndex.get(extending.id);
        if (!cur) { cancelExtend(); return; }
        const q = [latLng.lat(), latLng.lng()];
        const np = cur.points.slice();
        let ni;
        if (extending.index === 0) { np.unshift(q); ni = 0; }
        else { ni = extending.index + 1; np.splice(ni, 0, q); }
        try {
            const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: cur.id, points: np } });
            pushHist({ type: 'update', id: cur.id, before: cur.points, after: res.data.object.points });
            pendingExtend = { id: cur.id, index: ni };            // re-arm on the fresh render
            dropObject(cur.id);
            renderObject(res.data.object);
        } catch (e) { pendingExtend = null; if (window.toast) toast(e.message, 'error'); }
    }
    async function closeExtendInto(o) {
        const cur = objIndex.get(o.id) || o;
        if ((cur.points || []).length < 3) { if (window.toast) toast('Need at least 3 points to close a shape.', 'error'); return; }
        cancelExtend();
        try {
            const res = await api(`${URLS.push}?scheduleId=${SID}`, {
                method: 'POST', body: { kind: 'area', points: cur.points, color: cur.color, width: cur.width, label: cur.label },
            });
            renderObject(res.data.object);
            // Two history steps in this order: one undo re-opens the ring,
            // a second removes the area again.
            pushHist({ type: 'add', object: res.data.object });
            pushHist({ type: 'remove', object: cur });
            await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: cur.id } }).catch(() => {});
            dropObject(cur.id);
            if (window.toast) toast('Closed into an area.');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
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
        objIndex.set(o.id, o);
        const parts = [];
        const style = { map, strokeColor: o.color || '#f5c518', strokeWeight: o.width || 3, clickable: true };
        const pts = o.points;
        if (o.kind === 'pen' || o.kind === 'line' || o.kind === 'path' || o.kind === 'arrow') {
            parts.push(new (G().Polyline)({ ...style, path: pts.map(LL),
                icons: o.kind === 'arrow' ? [ARROW_HEAD(style.strokeColor)] : null }));
            // Arrow tips stay clean — no teardrop pins. Move or reshape an
            // arrow with the Select tool.
            if (o.kind !== 'pen' && o.kind !== 'arrow') {
                vertexPins(parts, o, pts, style.strokeColor);
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
            vertexPins(parts, o, c, style.strokeColor);
            segLabels(parts, c, true);
            parts.push(textMark(centerOf(c), fmtA(areaOf(c)), 'cmap-lbl-g'));
        } else if (o.kind === 'area') {
            parts.push(new (G().Polygon)({ ...style, paths: pts.map(LL), fillColor: style.strokeColor, fillOpacity: .1 }));
            vertexPins(parts, o, pts, style.strokeColor);
            segLabels(parts, pts, true);
            parts.push(textMark(centerOf(pts), fmtA(areaOf(pts)), 'cmap-lbl-g'));
        } else if (o.kind === 'text') {
            const tm = textMark(pts[0], o.label || '', 'cmap-txt-g', '#111827');
            // Labels are decoration, but a text OBJECT must catch taps or it
            // could never be erased or moved.
            tm.setClickable(true);
            parts.push(tm);
        }
        // Taps on a shape route by tool: erase removes it for everyone, edit
        // picks it up for dragging and reshaping.
        parts.forEach((p) => p.addListener && p.addListener('click', () => {
            if (tool === 'erase') {
                pushHist({ type: 'remove', object: objIndex.get(o.id) || o });
                api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
                dropObject(o.id);
            } else if (tool === 'edit') {
                beginEdit(o, parts);
            }
        }));
        if (pendingEdit === o.id) { pendingEdit = null; beginEdit(o, parts); }
        layers.set(o.id, parts);
    }
    function dropObject(id) {
        if (extending && extending.id === id) cancelExtend();
        if (editing && editing.o.id === id) endEdit();
        (layers.get(id) || []).forEach((p) => p.setMap(null));
        layers.delete(id); objIndex.delete(id);
    }
    function dropAll() { cancelExtend(); endEdit(); layers.forEach((parts) => parts.forEach((p) => p.setMap(null))); layers.clear(); objIndex.clear(); }

    /* Undo is a history of inverse calls against the same endpoints the
       actions used, so every step also lands live for the team. Re-adding a
       removed shape mints a fresh id — the entries swap ids as they flow
       between the stacks, so chains keep working. */
    const objIndex = new Map();     // id -> shaped object (latest)
    const histUndo = [], histRedo = [];
    function syncHistBtns() {
        const u = document.getElementById('cmapUndo'), r = document.getElementById('cmapRedo');
        if (u) u.disabled = !histUndo.length;
        if (r) r.disabled = !histRedo.length;
    }
    function pushHist(entry) {
        histUndo.push(entry);
        if (histUndo.length > 30) histUndo.shift();
        histRedo.length = 0;
        syncHistBtns();
    }
    async function reAdd(object) {
        const res = await api(`${URLS.push}?scheduleId=${SID}`, {
            method: 'POST', body: { kind: object.kind, points: object.points, color: object.color, width: object.width, label: object.label },
        });
        renderObject(res.data.object);
        return res.data.object;
    }
    async function applyStep(step, into) {
        if (step.type === 'add') {
            await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: step.object.id } }).catch(() => {});
            dropObject(step.object.id);
            into.push({ type: 'remove', object: step.object });
        } else if (step.type === 'remove') {
            const fresh = await reAdd(step.object);
            into.push({ type: 'add', object: fresh });
        } else if (step.type === 'update') {
            await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: step.id, points: step.before } }).catch(() => {});
            const cur = objIndex.get(step.id);
            dropObject(step.id);
            if (cur) renderObject({ ...cur, points: step.before });
            into.push({ type: 'update', id: step.id, before: step.after, after: step.before });
        } else if (step.type === 'clear') {
            const restored = [];
            for (const o of step.objects) restored.push(await reAdd(o));
            into.push({ type: 'unclear', objects: restored });
        } else if (step.type === 'unclear') {
            for (const o of step.objects) {
                await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
                dropObject(o.id);
            }
            into.push({ type: 'clear', objects: step.objects });
        }
    }
    let histBusy = false;
    async function stepHist(from, into) {
        if (histBusy) return;
        const step = from.pop();
        if (!step) return;
        histBusy = true;
        try { await applyStep(step, into); } catch (e) { if (window.toast) toast(e.message, 'error'); }
        histBusy = false;
        syncHistBtns();
    }
    async function saveObject(kind, pts, label) {
        try {
            const res = await api(`${URLS.push}?scheduleId=${SID}`, {
                method: 'POST', body: { kind, points: pts, color, width, label: label || null },
            });
            renderObject(res.data.object);
            pushHist({ type: 'add', object: res.data.object });
            if (kind !== 'pen' && window.toast) toast('Saved to the team map.');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }

    /* ---------- drawing ---------- */
    /* The half-drawn shape streams to the room (throttled, broadcast-only,
       like the GPS beacons) so teammates watch it grow instead of having the
       finished shape pop in. done tells them to drop the ghost. */
    let tempDots = [];
    function dropTempDots() { tempDots.forEach((m) => m.setMap(null)); tempDots = []; }
    // The classic map pin — its tip IS the point, so what you grab is
    // exactly what you placed.
    const PIN = 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z';
    const pinIcon = (c, sc) => ({ path: PIN, scale: sc || 1.35, anchor: new (G().Point)(12, 22),
        fillColor: c || color, fillOpacity: 1, strokeColor: '#fff', strokeWeight: 1.5 });
    const ARROW_HEAD = (c) => ({ icon: { path: G().SymbolPath.FORWARD_CLOSED_ARROW, scale: 3.4,
        fillColor: c, fillOpacity: 1, strokeColor: c, strokeWeight: 1 }, offset: '100%' });
    /* Segment distances paint WHILE the shape is being made, not only after
       Finish. Labels are reused (moved, retexted) instead of recreated, so
       drag frames don't flicker. Pen is exempt — hundreds of tiny segments. */
    let tempLabels = [];
    function dropTempLabels() { tempLabels.forEach((m) => m.setMap(null)); tempLabels = []; }
    function refreshTempLabels(closed) {
        const ring = closed && tempPts.length > 2;
        const n = tempPts.length < 2 ? 0 : (ring ? tempPts.length : tempPts.length - 1);
        for (let i = 0; i < n; i++) {
            const j = (i + 1) % tempPts.length;
            const at = mid(tempPts[i], tempPts[j]);
            const txt = fmtM(dist(tempPts[i], tempPts[j]));
            if (tempLabels[i]) {
                tempLabels[i].setPosition(LL(at));
                tempLabels[i].setLabel({ text: txt, className: 'cmap-lbl-g', color: '#fff', fontSize: '11px', fontWeight: '800' });
            } else {
                tempLabels[i] = textMark(at, txt, 'cmap-lbl-g');
            }
        }
        while (tempLabels.length > n) tempLabels.pop().setMap(null);
    }
    let traceLast = 0, traceOn = false;
    function sendTrace(done) {
        if (done && !traceOn) return;
        traceOn = !done;
        api(`${URLS.trace}?scheduleId=${SID}`, { method: 'POST', body: done
            ? { done: 1 }
            : { kind: tool, color, points: tempPts.slice(-120) } }).catch(() => {});
    }
    function clearTemp() {
        tempPts = [];
        if (tempShape) { tempShape.setMap(null); tempShape = null; }
        document.getElementById('cmapFinish').hidden = true;
        dropTempDots();
        dropTempLabels();
        sendTrace(true);
    }
    function previewTemp(closed) {
        if (tempShape) tempShape.setMap(null);
        const opts = { map, strokeColor: color, strokeWeight: width, clickable: false };
        tempShape = closed
            ? new (G().Polygon)({ ...opts, paths: tempPts.map(LL), fillColor: color, fillOpacity: .06 })
            : new (G().Polyline)({ ...opts, path: tempPts.map(LL), icons: tool === 'arrow' ? [ARROW_HEAD(color)] : null });
        // An arrow points, it does not measure — no label clutter on it.
        if (tool === 'pen' || tool === 'arrow') dropTempLabels();
        else refreshTempLabels(closed);
        if (Date.now() - traceLast > 250) { traceLast = Date.now(); sendTrace(false); }
    }
    function setTool(t) {
        tool = t;
        clearTemp();
        cancelExtend();
        document.querySelectorAll('[data-mtool]').forEach((b) => b.classList.toggle('is-active', b.dataset.mtool === t));
        const row = document.querySelector('.cmap-mrow[data-mtool="' + t + '"]');
        const lab = document.getElementById('cmapToolLabel');
        if (row && lab) lab.textContent = row.dataset.short || row.textContent;
        window.closeSheet?.('cmapToolsSheet');
        // Pan keeps native gestures; every drawing tool takes the finger.
        if (t !== 'edit') endEdit();
        const free = (t === 'pan' || t === 'edit' || t === 'erase');
        // Drawing tools take ONE finger; two fingers stay the map's — pinch
        // zoom and rotate keep working mid-drawing. 'none' killed them all.
        map.setOptions({ gestureHandling: free ? 'greedy' : 'cooperative', draggableCursor: free ? null : 'crosshair' });
    }
    /* Multi-line taps can loop home: touching the first pin again closes
       the ring and saves it as an area. */
    function closeTempAsArea() {
        const pts = tempPts.slice();
        clearTemp();
        saveObject('area', pts);
    }
    function onTap(latLng) {
        const p = [latLng.lat(), latLng.lng()];
        if (tool === 'path' || tool === 'area') {
            if (tempPts.length >= 3 && proj.getProjection()) {
                const a = proj.getProjection().fromLatLngToContainerPixel(latLng);
                const f = proj.getProjection().fromLatLngToContainerPixel(LL(tempPts[0]));
                if (a && f && Math.hypot(a.x - f.x, a.y - f.y) < 18) { closeTempAsArea(); return; }
            }
            tempPts.push(p); previewTemp(tool === 'area');
            // Each tapped corner shows itself at once — and stays grabbable:
            // drag a dot to move the point and the line re-shapes under it.
            const idx = tempPts.length - 1;
            const dot = new (G().Marker)({
                map, position: LL(p), draggable: true, crossOnDrag: false,
                icon: pinIcon(),
            });
            dot.addListener('drag', (ev) => {
                tempPts[idx] = [ev.latLng.lat(), ev.latLng.lng()];
                previewTemp(tool === 'area');
            });
            if (idx === 0) dot.addListener('click', () => {
                if ((tool === 'path' || tool === 'area') && tempPts.length >= 3) closeTempAsArea();
            });
            tempDots.push(dot);
            document.getElementById('cmapFinish').hidden = tempPts.length < 2;
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
        // Point-then-point felt like surveying; point-and-DRAG feels like
        // drawing. Line and box ride the same pointer plumbing as the pen.
        const DRAG_TOOLS = ['pen', 'line', 'rect', 'arrow'];
        const rectCorners = (a, b) => {
            const bd = new (G().LatLngBounds)(LL(a), LL(b));
            const sw = bd.getSouthWest(), ne = bd.getNorthEast();
            return [[sw.lat(), sw.lng()], [sw.lat(), ne.lng()], [ne.lat(), ne.lng()], [ne.lat(), sw.lng()]];
        };
        let lastPt = null;
        el.addEventListener('pointerdown', (e) => {
            if (!DRAG_TOOLS.includes(tool) || !proj.getProjection()) return;
            // A press that starts ON a pin, GPS dot or label belongs to that
            // marker — drag it, hold it — never to a fresh drawing. Without
            // this, readjusting a line's endpoint also drew a new line.
            const panes = proj.getPanes && proj.getPanes();
            if (panes && panes.overlayMouseTarget && panes.overlayMouseTarget.contains(e.target)) return;
            // A second finger joining means the first was a pinch, not a
            // stroke — abandon the half-drawn shape and let the map take it.
            if (penDown) { penDown = false; clearTemp(); return; }
            penDown = true; penPts = [ll(e)]; lastPt = penPts[0];
            e.preventDefault();
        });
        el.addEventListener('pointermove', (e) => {
            if (!penDown || !DRAG_TOOLS.includes(tool)) return;
            const p = ll(e); lastPt = p;
            if (tool === 'pen') { penPts.push(p); tempPts = penPts; previewTemp(false); }
            else if (tool === 'line' || tool === 'arrow') { tempPts = [penPts[0], p]; previewTemp(false); }
            else { tempPts = rectCorners(penPts[0], p); previewTemp(true); }
            e.preventDefault();
        });
        const up = () => {
            if (!penDown) return;
            penDown = false;
            const t = tool, start = penPts[0], end = lastPt || start;
            const stream = penPts.filter((_, i) => i % 2 === 0);
            clearTemp();
            if (t === 'pen' && stream.length > 1) saveObject('pen', stream);
            else if ((t === 'line' || t === 'arrow') && dist(start, end) > 0.5) saveObject(t, [start, end]);
            else if (t === 'rect' && dist(start, end) > 0.5) {
                const b = new (G().LatLngBounds)(LL(start), LL(end));
                saveObject('rect', [[b.getSouthWest().lat(), b.getSouthWest().lng()], [b.getNorthEast().lat(), b.getNorthEast().lng()]]);
            }
        };
        el.addEventListener('pointerup', up);
        el.addEventListener('pointercancel', up);
        // Only a SINGLE finger is claimed for drawing; a second finger means
        // a zoom or rotate gesture, which stays the map's to handle.
        el.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1 && tool !== 'pan' && tool !== 'edit' && tool !== 'erase') e.preventDefault();
        }, { passive: false });
    }

    /* ---------- editing ----------
     * The edit tool picks one shape up: whole-shape dragging for everything,
     * vertex handles for the measured kinds. Pen strokes drag only — hundreds
     * of vertex handles help nobody — and a rect stays a rect: its corners are
     * re-derived from bounds on save. Saves debounce behind the gesture and
     * re-render, so the measurement labels land on the new geometry. */
    let editing = null, saveTimer = null, pendingEdit = null, selVertex = null;
    function showEditBar(o) {
        const KINDS = { pen: 'drawing', line: 'line', path: 'multi-line', rect: 'box', area: 'area', text: 'label', arrow: 'arrow' };
        document.getElementById('cmapEditLbl').textContent = 'Editing ' + (KINDS[o.kind] || 'shape');
        document.getElementById('cmapDelPoint').hidden = true;
        document.getElementById('cmapEditBar').classList.remove('hidden');
    }
    function clearSelVertex() {
        if (!selVertex) return;
        try { selVertex.marker.setIcon(pinIcon(selVertex.color, 1.2)); } catch (_) {}
        selVertex = null;
        const btn = document.getElementById('cmapDelPoint');
        if (btn) btn.hidden = true;
    }
    /* Tapping a pin with the Select tool holds that exact point: the bar
       offers to delete it (box corners and single-point labels excluded —
       a box corner reshapes instead, a label has only itself). */
    function selectVertex(o, i, m, parts) {
        beginEdit(o, parts);
        clearSelVertex();
        if (o.kind === 'rect' || o.kind === 'text') return;
        selVertex = { id: o.id, index: i, marker: m, color: o.color || '#f5c518' };
        m.setIcon(pinIcon(selVertex.color, 1.5));
        document.getElementById('cmapDelPoint').hidden = false;
    }
    async function deleteSelPoint() {
        if (!selVertex) return;
        const cur = objIndex.get(selVertex.id);
        if (!cur) { clearSelVertex(); return; }
        const min = cur.kind === 'area' ? 3 : 2;
        if ((cur.points || []).length <= min) {
            if (window.toast) toast('Too few points left — delete the whole shape instead.', 'error');
            return;
        }
        const np = cur.points.filter((_, j) => j !== selVertex.index);
        clearSelVertex();
        try {
            const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: cur.id, points: np } });
            pushHist({ type: 'update', id: cur.id, before: cur.points, after: res.data.object.points });
            pendingEdit = cur.id;                 // stay holding the shape
            dropObject(cur.id);
            renderObject(res.data.object);
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }
    async function deleteEditedObj() {
        if (!editing) return;
        const o = objIndex.get(editing.o.id) || editing.o;
        endEdit();
        pushHist({ type: 'remove', object: o });
        api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
        dropObject(o.id);
        if (window.toast) toast('Removed from the map.');
    }
    function geometryOf(o, parts) {
        const first = parts[0];
        if (o.kind === 'text') { const pos = first.getPosition(); return [[pos.lat(), pos.lng()]]; }
        const pts = [];
        first.getPath().forEach((v) => pts.push([v.lat(), v.lng()]));
        if (o.kind === 'rect') {
            const b = new (G().LatLngBounds)();
            pts.forEach((p) => b.extend(LL(p)));
            return [[b.getSouthWest().lat(), b.getSouthWest().lng()], [b.getNorthEast().lat(), b.getNorthEast().lng()]];
        }
        return pts;
    }
    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(async () => {
            if (!editing) return;
            const { o, parts } = editing;
            const pts = geometryOf(o, parts);
            try {
                const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: o.id, points: pts } });
                pushHist({ type: 'update', id: o.id, before: o.points, after: res.data.object.points });
                endEdit();
                dropObject(o.id);
                renderObject(res.data.object);
                // Stay picked up, so nudging can continue without re-tapping.
                if (tool === 'edit') beginEdit(res.data.object, layers.get(res.data.object.id));
            } catch (e) { if (window.toast) toast(e.message, 'error'); }
        }, 700);
    }
    function beginEdit(o, parts) {
        if (editing && editing.o.id === o.id) return;
        endEdit();
        const first = parts[0];
        if (first.setOptions) {
            first.setOptions({
                draggable: true,
                editable: (o.kind === 'line' || o.kind === 'path' || o.kind === 'area' || o.kind === 'arrow'),
            });
        } else if (first.setDraggable) {
            first.setDraggable(true);
        }
        editing = { o, parts, listeners: [] };
        if (first.getPath) {
            const path = first.getPath();
            editing.listeners.push(path.addListener('set_at', scheduleSave));
            editing.listeners.push(path.addListener('insert_at', scheduleSave));
            editing.listeners.push(path.addListener('remove_at', scheduleSave));
        }
        editing.listeners.push(first.addListener('dragend', scheduleSave));
        showEditBar(o);
    }
    function endEdit() {
        if (!editing) return;
        clearTimeout(saveTimer);
        editing.listeners.forEach((l) => l.remove());
        const first = editing.parts[0];
        if (first.setOptions) first.setOptions({ draggable: false, editable: false });
        else if (first.setDraggable) first.setDraggable(false);
        editing = null;
        clearSelVertex();
        const bar = document.getElementById('cmapEditBar');
        if (bar) bar.classList.add('hidden');
    }

    /* ---------- teammates' drawing ghosts ---------- */
    const ghosts = new Map();   // userId -> { shape, label, at }
    function dropGhost(uid) {
        const g = ghosts.get(uid);
        if (g) { g.shape.setMap(null); g.label.setMap(null); ghosts.delete(uid); }
    }
    function renderGhost(p) {
        dropGhost(p.userId);
        if (p.done || !Array.isArray(p.points) || !p.points.length) return;
        const closed = (p.kind === 'rect' || p.kind === 'area');
        const opts = { map, strokeColor: p.color || hue(p.userId), strokeWeight: 3, strokeOpacity: .55, clickable: false };
        const shape = closed
            ? new (G().Polygon)({ ...opts, paths: p.points.map(LL), fillColor: opts.strokeColor, fillOpacity: .05 })
            : new (G().Polyline)({ ...opts, path: p.points.map(LL) });
        const label = textMark(p.points[p.points.length - 1], (p.name || '') + ' is drawing…', 'cmap-me-g');
        ghosts.set(p.userId, { shape, label, at: Date.now() });
    }

    /* ---------- live GPS ---------- */
    let gpsWatch = null, lastSent = 0, DotClass = null, centeredOnMe = false;
    function dotClass() {
        if (DotClass) return DotClass;
        // An OverlayView so the dot is real HTML: markers cannot ripple, and a
        // second marker for the name is what buried the text under the dot.
        DotClass = class extends (G().OverlayView) {
            constructor(p) { super(); this.p = p; this.div = null; this.setMap(map); }
            onAdd() {
                const d = document.createElement('div');
                d.className = 'cmap-dot-wrap';
                d.innerHTML = '<span class="cmap-dot" style="--dot:' + hue(this.p.userId) + '"></span>'
                    + '<span class="cmap-dot-name">' + escapeHtml((this.p.name || '') + (this.p.userId === ME ? ' (you)' : '')) + '</span>';
                this.div = d;
                this.getPanes().overlayMouseTarget.appendChild(d);
            }
            draw() {
                if (!this.div || !this.getProjection()) return;
                const pt = this.getProjection().fromLatLngToDivPixel(LL([this.p.lat, this.p.lng]));
                if (pt) { this.div.style.left = pt.x + 'px'; this.div.style.top = pt.y + 'px'; }
            }
            move(lat, lng) { this.p.lat = lat; this.p.lng = lng; this.draw(); }
            onRemove() { if (this.div) { this.div.remove(); this.div = null; } }
        };
        return DotClass;
    }
    function renderLoc(p) {
        const cur = locMarks.get(p.userId);
        if (cur) { cur.ov.move(p.lat, p.lng); cur.at = Date.now(); return; }
        const D = dotClass();
        locMarks.set(p.userId, { ov: new D(p), at: Date.now() });
    }
    setInterval(() => {
        locMarks.forEach((v, k) => { if (Date.now() - v.at > 75000) { v.ov.setMap(null); locMarks.delete(k); } });
        // A ghost whose artist stopped reporting is an abandoned gesture.
        ghosts.forEach((v, k) => { if (Date.now() - v.at > 8000) dropGhost(k); });
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
            if (!centeredOnMe && !layers.size) { centeredOnMe = true; map.setCenter({ lat, lng }); map.setZoom(17); dropVeil(); }
            if (Date.now() - lastSent > 5000) {
                lastSent = Date.now();
                api(`${URLS.loc}?scheduleId=${SID}`, { method: 'POST', body: { lat, lng, acc } }).catch(() => {});
            }
        }, () => { if (window.toast) toast('Could not get your GPS position.', 'error'); btn.classList.remove('is-active'); gpsWatch = null; },
        { enableHighAccuracy: true, maximumAge: 3000, timeout: 15000 });
    }

    /* ---------- saved maps ---------- */
    function loadObjects(fit) {
        return api(`${URLS.objects}?scheduleId=${SID}`).then((r) => {
            (r.data.objects || []).forEach(renderObject);
            if (!fit) return false;
            const b = new (G().LatLngBounds)();
            let any = false;
            (r.data.objects || []).forEach((o) => o.kind !== 'text' && o.points.forEach((p) => { b.extend(LL(p)); any = true; }));
            if (any) { map.fitBounds(b, 48); G().event.addListenerOnce(map, 'idle', dropVeil); }
            return any;
        });
    }
    /* The saved picture is composed here rather than server-side, because the
       numbers ARE the map: Static Maps can draw a shape but cannot write
       "12.34 m" beside it. The imagery comes through our own origin (a
       googleapis.com image would taint the canvas and block export), then the
       shapes, their points and every measurement are drawn over it with the
       same helpers and formats the live map uses — so the note shows exactly
       what was on screen. */
    function loadImg(src) {
        return new Promise((res, rej) => {
            const im = new Image();
            im.onload = () => res(im);
            im.onerror = () => rej(new Error('Could not load the map imagery.'));
            im.src = src;
        });
    }
    async function composeMapPng() {
        const g = G();
        const proj = map.getProjection();
        const centre = map.getCenter();
        if (!proj || !centre) return null;
        const SIZE = 640, SCALE = 2, zoom = Math.round(map.getZoom() || 15);
        const img = await loadImg(`${URLS.basemap}?scheduleId=${SID}&lat=${centre.lat()}&lng=${centre.lng()}`
            + `&zoom=${zoom}&maptype=${satOn ? 'hybrid' : 'roadmap'}&size=${SIZE}`);

        const cv = document.createElement('canvas');
        cv.width = SIZE * SCALE; cv.height = SIZE * SCALE;
        const ctx = cv.getContext('2d');
        ctx.drawImage(img, 0, 0, cv.width, cv.height);

        // Same Mercator the imagery was rendered in, so overlay and ground line up.
        const world0 = proj.fromLatLngToPoint(centre);
        const k = Math.pow(2, zoom) * SCALE;
        const px = (p) => {
            const w = proj.fromLatLngToPoint(LL(p));
            return [(w.x - world0.x) * k + cv.width / 2, (w.y - world0.y) * k + cv.height / 2];
        };
        const label = (p, text) => {
            const [x, y] = px(p);
            ctx.font = '700 22px system-ui, -apple-system, "Segoe UI", sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const w = ctx.measureText(text).width + 16;
            ctx.fillStyle = 'rgba(17,24,39,.72)';
            ctx.beginPath();
            const r = 8, x0 = x - w / 2, y0 = y - 16;
            ctx.roundRect ? ctx.roundRect(x0, y0, w, 32, r) : ctx.rect(x0, y0, w, 32);
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.fillText(text, x, y);
        };
        const dot = (p, colour) => {
            const [x, y] = px(p);
            ctx.beginPath(); ctx.arc(x, y, 7, 0, Math.PI * 2);
            ctx.fillStyle = colour; ctx.fill();
            ctx.lineWidth = 3; ctx.strokeStyle = '#fff'; ctx.stroke();
        };

        [...objIndex.values()].forEach((o) => {
            const colour = o.color || '#f5c518';
            const pts = o.kind === 'rect' && o.points.length >= 2
                ? (() => {
                    const b = new (g.LatLngBounds)(LL(o.points[0]), LL(o.points[1]));
                    const sw = b.getSouthWest(), ne = b.getNorthEast();
                    return [[sw.lat(), sw.lng()], [sw.lat(), ne.lng()], [ne.lat(), ne.lng()], [ne.lat(), sw.lng()]];
                })()
                : o.points;
            const closed = (o.kind === 'rect' || o.kind === 'area');

            if (o.kind === 'text') { label(pts[0], o.label || ''); return; }

            ctx.beginPath();
            pts.forEach((p, i) => { const [x, y] = px(p); i ? ctx.lineTo(x, y) : ctx.moveTo(x, y); });
            if (closed) ctx.closePath();
            ctx.lineWidth = (o.width || 3) * SCALE;
            ctx.strokeStyle = colour;
            ctx.lineJoin = ctx.lineCap = 'round';
            if (closed) { ctx.fillStyle = colour + '22'; ctx.fill(); }
            ctx.stroke();

            if (o.kind === 'pen') return;               // a freehand line has no useful numbers
            pts.forEach((p) => dot(p, colour));
            if (o.kind === 'arrow') return;             // an arrow points, it does not measure

            const n = closed ? pts.length : pts.length - 1;
            for (let i = 0; i < n; i++) {
                const j = (i + 1) % pts.length;
                label(mid(pts[i], pts[j]), fmtM(dist(pts[i], pts[j])));
            }
            if (closed) label(centerOf(pts), fmtA(areaOf(pts)));
            else if (o.kind === 'path' && pts.length > 2) {
                let total = 0;
                for (let i = 0; i < pts.length - 1; i++) total += dist(pts[i], pts[i + 1]);
                label(pts[pts.length - 1], 'Σ ' + fmtM(total));
            }
        });

        return cv.toDataURL('image/png');
    }

    let saveMode = 'map';
    /* Which saved map the shapes on screen came from, if any. It is what makes
       "save over this one" possible — and what the header notice reports. */
    let LOADED_SAVE = null;
    function setLoadedSave(sv) {
        LOADED_SAVE = sv ? { id: sv.id, title: sv.title || 'Map' } : null;
        window.setEditingNotice?.(LOADED_SAVE
            ? 'You are working on the saved map “' + LOADED_SAVE.title + '”. Saving can replace it or keep it and make a new one.'
            : '');
    }
    function openSaveSheet(mode) {
        saveMode = mode;
        document.getElementById('cmapSaveTitleH').textContent = mode === 'map' ? 'Save map to notes' : 'Save as image note';
        document.getElementById('cmapSaveHint').textContent = mode === 'map'
            ? 'Keeps this map reopenable from the tools, and files a picture of it in the schedule notebook.'
            : 'Files a picture of the map, shapes and all, in the schedule notebook.';
        // Opened from a saved map: the common answer is "this one, changed",
        // and until now the only thing on offer was a second copy of it.
        const over = document.getElementById('cmapSaveOver');
        const overLbl = document.getElementById('cmapSaveOverLabel');
        const showOver = mode === 'map' && !!LOADED_SAVE;
        if (over) over.hidden = !showOver;
        if (showOver && overLbl) overLbl.textContent = 'Save over “' + LOADED_SAVE.title + '”';
        const name = document.getElementById('cmapSaveName');
        if (name && !name.value && LOADED_SAVE) name.value = LOADED_SAVE.title;
        window.openSheet?.('cmapSaveSheet');
    }
    async function doSaveMap(replace) {
        const btn = document.getElementById(replace ? 'cmapSaveOver' : 'cmapSaveGo');
        const c = map.getCenter();
        btn.disabled = true;
        const label = document.getElementById(replace ? 'cmapSaveOverLabel' : 'cmapSaveGoTxt');
        const was = label.textContent;
        label.textContent = 'Saving…';
        // Compose the picture here so it carries the points and measurements;
        // if that fails the server still draws a plain one from Static Maps.
        let image = null;
        try { image = await composeMapPng(); } catch (e) { image = null; }
        try {
            const r = await api(`${URLS.save}?scheduleId=${SID}`, { method: 'POST', body: {
                mode: saveMode,
                // Which file to write into. Absent means a new one.
                saveId: replace && LOADED_SAVE ? LOADED_SAVE.id : null,
                // Drawn with the team or drawn alone: the same tool, but not
                // the same thing to whoever loads it later.
                source: @json(request()->routeIs('sm.collab') ? 'team' : 'solo'),
                image,
                title: document.getElementById('cmapSaveName').value.trim(),
                description: document.getElementById('cmapSaveDesc').value.trim(),
                lat: c ? c.lat() : null, lng: c ? c.lng() : null,
                zoom: Math.round(map.getZoom() || 15), maptype: satOn ? 'hybrid' : 'roadmap',
            } });
            window.closeSheet?.('cmapSaveSheet');
            document.getElementById('cmapSaveName').value = '';
            document.getElementById('cmapSaveDesc').value = '';
            // A brand new map becomes the one being worked on, so the next
            // save can replace it rather than making a third copy.
            if (r && r.data && r.data.saveId) {
                setLoadedSave({ id: r.data.saveId, title: r.data.title || 'Map' });
            }
            if (window.toast) toast((r && r.message) || 'Saved.');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
        btn.disabled = false;
        label.textContent = was;
    }
    let SAVED_MAPS = [];

    function paintSaves(term) {
        const list = document.getElementById('cmapSavesList');
        const count = document.getElementById('cmapSaveCount');
        const q = (term || '').trim().toLowerCase();
        const rows = q
            ? SAVED_MAPS.filter((sv) => (sv.title + ' ' + sv.by).toLowerCase().includes(q))
            : SAVED_MAPS;

        count.textContent = SAVED_MAPS.length
            ? (q ? rows.length + ' of ' + SAVED_MAPS.length : SAVED_MAPS.length + (SAVED_MAPS.length === 1 ? ' map' : ' maps'))
            : '';

        if (!SAVED_MAPS.length) {
            list.innerHTML = '<p class="cmap-saves-empty">No saved maps yet — draw one, then “Save map to notes”.</p>';
            return;
        }
        if (!rows.length) {
            list.innerHTML = '<p class="cmap-saves-empty">Nothing matches that.</p>';
            return;
        }

        list.innerHTML = '';
        rows.forEach((sv) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'cmap-saverow';
            const shapes = sv.count + ' shape' + (sv.count === 1 ? '' : 's');
            // An icon, not a picture: the thumbnails were a download each,
            // told you nothing a title does not, and turned into a column of
            // broken frames whenever a file went missing.
            b.innerHTML = `
                <span class="cmap-mark ${sv.source === 'team' ? 'is-team' : ''}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                </span>
                <span class="cmap-saverow-main">
                    <span class="cmap-saverow-t">${esc(sv.title || 'Map')}</span>
                    <span class="cmap-tags">
                        <span class="cmap-tag ${sv.source === 'team' ? 'is-team' : 'is-solo'}">${sv.source === 'team' ? 'Team map' : 'My map'}</span>
                        <span class="cmap-tag">${esc(shapes)}</span>
                        ${sv.noteHref ? `<a class="cmap-tag is-note" href="${esc(sv.noteHref)}" title="Open the note this map is in">In a note</a>` : ''}
                    </span>
                    <span class="cmap-saverow-s">${esc(sv.by)} · ${esc(sv.when)}</span>
                </span>`;
            b.addEventListener('click', () => loadSavedMap(sv));
            list.appendChild(b);
        });
    }

    async function openSaves() {
        try {
            const r = await api(`${URLS.saves}?scheduleId=${SID}`);
            SAVED_MAPS = r.data.saves || [];
            const search = document.getElementById('cmapSaveSearch');
            if (search) search.value = '';
            paintSaves('');
            window.openSheet?.('cmapSavesSheet');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }

    document.getElementById('cmapSaveSearch')?.addEventListener('input', (e) => paintSaves(e.target.value));
    window.__openSavesForTest = openSaves;
    async function loadSavedMap(sv, opts) {
        // From the Maps module's grid the choice was already made by tapping
        // the card, and an empty canvas has nothing to lose — only a canvas
        // that still holds shapes is worth interrupting for.
        const skipAsk = !!(opts && opts.quiet) && objIndex.size === 0;
        const ok = skipAsk || (window.confirmAction
            ? await confirmAction({ title: 'Load “' + sv.title + '”?', message: 'Replaces the current shapes for the whole team.', confirmText: 'Load map' })
            : confirm('Load this map for everyone? It replaces the current shapes.'));
        if (!ok) return;
        try {
            await api(`${URLS.load}?scheduleId=${SID}`, { method: 'POST', body: { id: sv.id } });
            setLoadedSave(sv);
            window.closeSheet?.('cmapSavesSheet');
            endEdit(); dropAll();
            histUndo.length = 0; histRedo.length = 0; syncHistBtns();
            await loadObjects(true);
            if (window.toast) toast('Map loaded for the team.');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }

    /* ---------- the Maps module's grid drives the stage from outside ----------
       The grid lives in maps.blade.php, outside this IIFE, and may ask before
       the map has booted — Google's script loads on demand. The ask is parked
       here and drained right after the first loadObjects, so it always lands
       on a map that exists. */
    let pendingGridAsk = null;   // { kind: 'save', id } | { kind: 'blank' }
    async function wipeCanvas() {
        const snapshot = [...objIndex.values()];
        await api(`${URLS.clear}?scheduleId=${SID}`, { method: 'POST' });
        dropAll();
        if (snapshot.length) pushHist({ type: 'clear', objects: snapshot });
    }
    async function startBlankCanvas() {
        if (objIndex.size === 0) { setLoadedSave(null); return; }
        const n = objIndex.size;
        const ok = window.confirmAction
            ? await confirmAction({ title: 'Start a blank map?', message: 'Removes the ' + n + ' shape' + (n === 1 ? '' : 's') + ' on the canvas for the whole team. Save the current map first if it is worth keeping.', confirmText: 'Start blank' })
            : confirm('Start a blank map? This clears the current shapes for everyone.');
        if (!ok) return;
        try {
            await wipeCanvas();
            setLoadedSave(null);
        } catch (err) { if (window.toast) toast(err.message, 'error'); }
    }
    // One drain at a time: two quick taps otherwise raced two loadSave POSTs,
    // and the server's wipe-then-insert can interleave into a doubled canvas.
    let drainingAsk = false;
    async function drainGridAsk() {
        if (drainingAsk) return;
        drainingAsk = true;
        try {
            while (pendingGridAsk) {
                const ask = pendingGridAsk;
                pendingGridAsk = null;
                if (ask.kind === 'blank') { await startBlankCanvas(); continue; }
                if (LOADED_SAVE && LOADED_SAVE.id === ask.id) continue;   // already on screen
                try {
                    const r = await api(`${URLS.saves}?scheduleId=${SID}`);
                    const sv = (r.data.saves || []).find((s) => s.id === ask.id);
                    if (sv) await loadSavedMap(sv, { quiet: true });
                    else if (window.toast) toast('That saved map no longer exists.', 'error');
                } catch (e) { if (window.toast) toast(e.message, 'error'); }
            }
        } finally { drainingAsk = false; }
    }
    window.cmapOpenSaveById = (id) => {
        pendingGridAsk = { kind: 'save', id: parseInt(id, 10) || 0 };
        if (booted && map) drainGridAsk();
    };
    window.cmapStartBlank = () => {
        pendingGridAsk = { kind: 'blank' };
        if (booted && map) drainGridAsk();
    };
    // What the grid needs to know when the user walks back out of the stage.
    window.cmapShapeCount = () => objIndex.size;

    /* ---------- boot ---------- */
    let booted = false, loading = false, veilDone = false;
    function dropVeil() {
        if (veilDone) return;
        veilDone = true;
        const v = document.getElementById('cmapVeil');
        if (!v) return;
        v.classList.add('is-done');
        setTimeout(() => v.remove(), 500);
    }
    function buildMap() {
        // The last viewport this device used opens INSTANTLY zoomed-in; a
        // first-ever open keeps the veil up until GPS or the team's shapes
        // reveal where to look. Either way, no country view blinking away.
        let storedView = null;
        try { storedView = JSON.parse(localStorage.getItem('cmapView:' + SID) || 'null'); } catch (_) {}
        if (storedView && !(storedView.lat && storedView.lng && storedView.zoom)) storedView = null;
        if (storedView) centeredOnMe = true;
        map = new (G().Map)(document.getElementById('cmapMap'), {
            center: storedView ? { lat: storedView.lat, lng: storedView.lng } : { lat: 12.88, lng: 121.77 },
            zoom: storedView ? storedView.zoom : 6,
            mapTypeId: 'hybrid',                       // farmers plan on what the land looks like
            mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
            gestureHandling: 'greedy',
            // Vector rendering is what makes two-finger rotate and tilt work.
            renderingType: 'VECTOR',
            headingInteractionEnabled: true, tiltInteractionEnabled: true,
        });
        proj = new (G().OverlayView)();
        proj.onAdd = function () {}; proj.draw = function () {}; proj.onRemove = function () {};
        proj.setMap(map);

        G().event.addListenerOnce(map, 'tilesloaded', () => { if (storedView) dropVeil(); });
        setTimeout(dropVeil, 6000);
        // Remember where the team looks, but never a zoomed-out fallback view.
        map.addListener('idle', () => {
            try {
                const c = map.getCenter();
                if (c && map.getZoom() >= 10) localStorage.setItem('cmapView:' + SID, JSON.stringify({ lat: c.lat(), lng: c.lng(), zoom: map.getZoom() }));
            } catch (_) {}
        });

        map.addListener('click', (e) => {
            if (extending) { extendTo(e.latLng); return; }
            if (tool !== 'pan' && tool !== 'pen' && tool !== 'erase') onTap(e.latLng);
        });
        bindPen(map.getDiv());

        document.querySelectorAll('[data-mtool]').forEach((b) =>
            b.addEventListener('click', () => setTool(b.dataset.mtool)));
        document.getElementById('cmapToolsBtn').addEventListener('click', () => window.openSheet?.('cmapToolsSheet'));
        document.querySelectorAll('[data-maction]').forEach((b) => b.addEventListener('click', () => {
            window.closeSheet?.('cmapToolsSheet');
            if (b.dataset.maction === 'open') openSaves();
            else openSaveSheet(b.dataset.maction === 'savemap' ? 'map' : 'image');
        }));
        document.getElementById('cmapSaveGo').addEventListener('click', () => doSaveMap(false));
        document.getElementById('cmapSaveOver').addEventListener('click', () => doSaveMap(true));
        document.getElementById('cmapDelPoint').addEventListener('click', deleteSelPoint);
        document.getElementById('cmapDelObj').addEventListener('click', deleteEditedObj);
        document.getElementById('cmapEditDone').addEventListener('click', endEdit);
        document.getElementById('cmapUndo').addEventListener('click', () => stepHist(histUndo, histRedo));
        document.getElementById('cmapRedo').addEventListener('click', () => stepHist(histRedo, histUndo));
        document.getElementById('cmapColorBtn').addEventListener('click', () => window.openSheet?.('cmapColorSheet'));
        document.querySelectorAll('#cmapColorSheet .cmap-swatch').forEach((b) => b.addEventListener('click', () => {
            color = b.dataset.mcolor;
            document.querySelectorAll('#cmapColorSheet .cmap-swatch').forEach((x) => x.classList.toggle('is-active', x === b));
            const dotEl = document.getElementById('cmapColorDot');
            if (dotEl) dotEl.style.background = color;
            window.closeSheet?.('cmapColorSheet');
            // A half-drawn shape repaints on the spot, dots included.
            if (tempPts.length) {
                previewTemp(tool === 'area');
                tempDots.forEach((m) => m.setIcon(pinIcon()));
            }
        }));
        document.getElementById('cmapSearchBtn').addEventListener('click', () => {
            window.openSheet?.('cmapSearchSheet');
            // Search means typing — focusing here is the point, not a nuisance.
            setTimeout(() => document.getElementById('cmapSearch')?.focus(), 320);
        });
        // Search jumps the map anywhere by name; without Places on the key the
        // box goes away rather than sitting dead.
        try {
            const inp = document.getElementById('cmapSearch');
            const ac = new (G().places.Autocomplete)(inp, { fields: ['geometry'] });
            ac.addListener('place_changed', () => {
                const g = ac.getPlace().geometry;
                if (!g) return;
                window.closeSheet?.('cmapSearchSheet');
                if (g.viewport) map.fitBounds(g.viewport);
                else { map.setCenter(g.location); map.setZoom(17); }
            });
        } catch (_) { document.getElementById('cmapSearchBtn')?.remove(); }
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
            try {
                await wipeCanvas();
            } catch (err) { if (window.toast) toast(err.message, 'error'); }
        });

        // Existing shapes, then follow the room live — and only then whatever
        // the Maps grid asked for while the map was still booting, so a tapped
        // card lands on a map that exists. (?save= deep links land there too:
        // the grid reads them server-side and asks through the same door.)
        loadObjects(true).catch(() => {}).then(() => drainGridAsk());

        // On by default: seeing each other on the land is why the map exists.
        // The browser still asks permission; declining just leaves it off.
        toggleGps(document.getElementById('cmapGps'));

        if (window.Echo) {
            try {
                const ch = window.Echo.private('schedule-board.' + SID);
                ch.listen('.map.object', (p) => {
                    if (!p || p.actorUserId === ME) return;
                    if (p.action === 'add' && p.object) renderObject(p.object);
                    else if (p.action === 'update' && p.object) {
                        if (editing && editing.o.id === p.object.id) endEdit();
                        dropObject(p.object.id); renderObject(p.object);
                    }
                    else if (p.action === 'remove') dropObject(p.id);
                    else if (p.action === 'clear') dropAll();
                    else if (p.action === 'reload') {
                        // Someone loaded a saved map — take the fresh set whole.
                        endEdit(); dropAll();
                        histUndo.length = 0; histRedo.length = 0; syncHistBtns();
                        loadObjects(true).catch(() => {});
                    }
                });
                ch.listen('.map.loc', (p) => { if (p && p.userId !== ME) renderLoc(p); });
                ch.listen('.map.trace', (p) => { if (p && p.userId !== ME) renderGhost(p); });
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
            + '&libraries=geometry,places&v=weekly&loading=async&callback=__cmapBoot';
        s.async = true;
        s.onerror = () => {
            loading = false;
            const t = document.querySelector('#cmapVeil .cmap-veil-txt');
            if (t) t.textContent = 'Could not load Google Maps.';
            if (window.toast) toast('Could not load Google Maps — check the API key.', 'error');
        };
        document.head.appendChild(s);
    };
})();
</script>
@endif
