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
                {{-- Opening and saving used to live at the bottom of this
                     list, under ten drawing tools. They are not tools — they
                     are what you do with the map — so they have their own
                     button and their own sheet now. --}}
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
        {{-- How thick the line is drawn. The pen had no such control at all:
             one weight for a field boundary and for a footpath. --}}
        <button type="button" class="cmap-tool" id="cmapSizeBtn" title="Line thickness" aria-label="Line thickness">
            <span class="cmap-size-dot" id="cmapSizeDot"></span>
        </button>
        <div class="sheet hidden" id="cmapSizeSheet" style="--sheet-width:22rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Line thickness</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body" style="padding-bottom:1.1rem">
                <div class="cmap-sizes">
                    <button type="button" class="cmap-sizeopt" data-msize="2"><span style="height:2px"></span><b>Thin</b></button>
                    <button type="button" class="cmap-sizeopt is-active" data-msize="3"><span style="height:3px"></span><b>Normal</b></button>
                    <button type="button" class="cmap-sizeopt" data-msize="5"><span style="height:5px"></span><b>Thick</b></button>
                    <button type="button" class="cmap-sizeopt" data-msize="8"><span style="height:8px"></span><b>Heavy</b></button>
                </div>
            </div>
        </div>
        {{-- Writing on the map. It has no button of its own in the bar: the
             way in is the Text tool (tap the ground) or tapping a label that
             is already there — so there is nothing here for the Maps module
             to proxy, only a sheet that opens over whichever screen asked.

             prompt() used to do this, which meant one line of plain text, no
             say in how it looked, and a browser dialog over the field you
             were pointing at. --}}
        <div class="sheet hidden" id="cmapTextSheet" style="--sheet-width:26rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title" id="cmapTextTitleH">Add a label</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body" style="padding-bottom:1.1rem">
                <label class="cmap-save-label" for="cmapTextInput">What should it say?</label>
                {{-- Rows, not a single line: a gate label is often two words
                     over two lines, and the column takes 500 characters. --}}
                <textarea id="cmapTextInput" class="form-textarea" rows="3" maxlength="500"
                    placeholder="North gate&#10;keep clear"></textarea>
                <p class="cmap-text-left" id="cmapTextLeft"></p>
                <label class="cmap-save-label">Lettering</label>
                {{-- Each one is written in itself — the name of a typeface
                     tells a farmer nothing, the shape of it tells them
                     everything. The stacks are set from JS so there is one
                     list of families, not two. --}}
                <div class="cmap-fonts" id="cmapFontRow">
                    <button type="button" class="cmap-fontopt is-active" data-mfont="sans">
                        <span class="cmap-fontsample">Aa</span><b>Plain</b>
                    </button>
                    <button type="button" class="cmap-fontopt" data-mfont="serif">
                        <span class="cmap-fontsample">Aa</span><b>Book</b>
                    </button>
                    <button type="button" class="cmap-fontopt" data-mfont="cond">
                        <span class="cmap-fontsample">Aa</span><b>Narrow</b>
                    </button>
                    <button type="button" class="cmap-fontopt" data-mfont="mono">
                        <span class="cmap-fontsample">Aa</span><b>Even</b>
                    </button>
                </div>
                <p class="cmap-text-hint">
                    Size is set on the map: the label comes up held, with a round
                    <b>A</b> beside it — drag that away to make it bigger, back in to
                    make it smaller.
                </p>
                <button type="button" class="cmap-save-go" id="cmapTextGo"><span id="cmapTextGoTxt">Place label</span></button>
            </div>
        </div>
        {{-- Where you are, and what the ground looks like — beside the pen's
             own settings, because they are all "how the map reads".

             Two buttons, because they are two different questions. This one
             answers "where am I" and moves the map there; the one beside it
             answers "everyone, here I am" and keeps answering it. Sharing used
             to be the only way to get taken to yourself, which made a private
             question cost a broadcast. --}}
        <button type="button" class="cmap-tool" id="cmapFindMe" title="Centre the map on me" aria-label="Centre the map on my position">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.25"/><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" d="M12 1.5v2.5M12 20v2.5M1.5 12h2.5M20 12h2.5"/></svg>
        </button>
        <button type="button" class="cmap-tool" id="cmapGps" title="Share my live GPS position with the team" aria-label="Share my live GPS position">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6.5"/><path stroke-linecap="round" d="M12 2v3.5M12 18.5V22M2 12h3.5M18.5 12H22M12 12h.01"/></svg>
        </button>
        <button type="button" class="cmap-tool is-active" id="cmapLayer" title="Toggle map / satellite" aria-label="Toggle map or satellite view" aria-pressed="true">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5"/></svg>
        </button>
        <button type="button" class="cmap-tool cmap-danger" id="cmapClear" title="Clear the whole map for the team" aria-label="Clear the map for the team">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>
        </button>
        {{-- Opening and saving, together, out of the tools list. --}}
        <button type="button" class="cmap-tool cmap-savebtn" id="cmapSaveMenuBtn" title="Open or save a map" aria-label="Open or save a map">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v5h6M8 14h8v6H8z"/></svg>
        </button>
        <div class="sheet hidden" id="cmapSaveMenuSheet" style="--sheet-width:24rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Maps</h3>
                <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sheet-body cmap-menu-body">
                <button type="button" class="cmap-mrow" data-maction="open">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    <span>Open a saved map</span>
                </button>
                <button type="button" class="cmap-mrow" data-maction="savemap">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/><path stroke-linecap="round" d="M9 8h6M9 12h6M9 16h4"/></svg>
                    <span>Save map to notes<small>Reopenable later, picture filed in Notes</small></span>
                </button>
                <button type="button" class="cmap-mrow" data-maction="saveimage">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-4.5-4.5L9 18"/></svg>
                    <span>Save as image note<small>A picture only, filed in Notes</small></span>
                </button>
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
    </div>
    <div class="cmap-stage">
        <div class="cmap-map" id="cmapMap"></div>
        <div class="cmap-veil" id="cmapVeil">
            <span class="cmap-veil-spin"></span>
            <span class="cmap-veil-txt">Finding your ground…</span>
        </div>
        {{-- Three seconds of holding still is a long time to wonder whether
             anything is happening. This is the answer: a ring that closes on
             the exact spot the new point would land, and is gone the instant
             the finger lifts or wanders. --}}
        <div class="cmap-hold" id="cmapHold" hidden aria-hidden="true">
            <svg viewBox="0 0 44 44">
                <circle class="cmap-hold-trk" cx="22" cy="22" r="19"></circle>
                <circle class="cmap-hold-arc" cx="22" cy="22" r="19"></circle>
            </svg>
            <span class="cmap-hold-dot"></span>
        </div>
        <div class="cmap-editbar hidden" id="cmapEditBar">
            <span class="cmap-editbar-lbl" id="cmapEditLbl">Editing</span>
            {{-- Only a label has words to change, so only a label offers it.
                 Tapping the label itself opens the same sheet; this is the way
                 back to it once the sheet has been closed and the label is
                 still held. --}}
            <button type="button" id="cmapEditText" hidden>Edit text</button>
            <button type="button" id="cmapDelPoint" hidden>Delete point</button>
            <button type="button" id="cmapDelObj">Delete shape</button>
            <button type="button" id="cmapEditDone">Done</button>
        </div>
        {{-- An opened map writes itself back as you work. This is the whole of
             what it says about it: a word, then gone. --}}
        <div class="cmap-saved" id="cmapSaved" aria-live="polite">
            <span class="cmap-saved-dot"></span>
            <span id="cmapSavedTxt">Saved</span>
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
    /* Waiting on a satellite, which on a phone under trees is a real wait. The
       pulse is on the icon rather than the button so the button keeps its shape
       in the row, and it overrides the :disabled dimming — a control that is
       busy is not the same as one that is refused, and should not look it. */
    .cmap-tool.is-busy { opacity: 1; }
    .cmap-tool.is-busy svg { animation: cmapSeek 1.1s ease-in-out infinite; transform-origin: 50% 50%; }
    @keyframes cmapSeek {
        0%, 100% { opacity: .45; transform: scale(.88); }
        50% { opacity: 1; transform: scale(1.06); }
    }
    @media (prefers-reduced-motion: reduce) {
        .cmap-tool.is-busy svg { animation: none; opacity: .55; }
    }
    /* Cooperative mode keeps two-finger pinch alive while drawing, but its
       "use two fingers to move the map" scrim would flash over every stroke —
       one finger here IS the tool, not a mistake. */
    .cmap-map .gm-style-moc { display: none !important; }
    .cmap-color-dot { width: 1.05rem; height: 1.05rem; border-radius: 999px; background: #f5c518;
        border: 2px solid rgb(255 255 255 / .9); box-shadow: 0 0 0 1px rgb(0 0 0 / .12); }
    /* The pen's weight, shown as itself. */
    .cmap-size-dot { width: 1.05rem; height: 1.05rem; border-radius: 999px; position: relative;
        background: var(--color-gray-300); }
    .cmap-size-dot::after { content: ''; position: absolute; left: 50%; top: 50%; width: .8rem;
        transform: translate(-50%, -50%); border-radius: 999px; background: var(--color-gray-700);
        height: var(--w, 3px); }
    html.dark .cmap-size-dot { background: #3f4a37; }
    html.dark .cmap-size-dot::after { background: #cdd8c0; }
    .cmap-sizes { display: grid; gap: .5rem; }
    .cmap-sizeopt { display: flex; align-items: center; gap: .75rem; padding: .6rem .75rem;
        border-radius: .7rem; border: 2px solid var(--color-gray-200); background: var(--color-white);
        cursor: pointer; text-align: left; }
    .cmap-sizeopt span { flex: 1 1 auto; border-radius: 999px; background: var(--color-gray-700); }
    .cmap-sizeopt b { flex: 0 0 auto; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); }
    .cmap-sizeopt.is-active { border-color: #4a7c2a; background: #f0f7e8; }
    html.dark .cmap-sizeopt { background: #151b12; border-color: #2b3a1c; }
    html.dark .cmap-sizeopt span { background: #cdd8c0; }
    html.dark .cmap-sizeopt b { color: #cdd8c0; }
    html.dark .cmap-sizeopt.is-active { border-color: #6b9f3d; background: rgb(107 159 61 / .22); }
    /* The lettering picker: a row of the four faces, each shown in itself.
       Wraps rather than scrolls — four is few enough to see at once, and a
       scroller hides the one you have not looked at yet. */
    .cmap-fonts { display: grid; grid-template-columns: repeat(auto-fit, minmax(5rem, 1fr)); gap: .5rem; }
    .cmap-fontopt { display: flex; flex-direction: column; align-items: center; gap: .15rem;
        padding: .55rem .4rem; border-radius: .7rem; border: 2px solid var(--color-gray-200);
        background: var(--color-white); cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .cmap-fontsample { font-size: 1.45rem; line-height: 1.1; font-weight: 800; color: var(--color-gray-900); }
    .cmap-fontopt b { font-size: .68rem; font-weight: 700; color: var(--color-gray-500); }
    .cmap-fontopt.is-active { border-color: #4a7c2a; background: #f0f7e8; }
    html.dark .cmap-fontopt { background: #151b12; border-color: #2b3a1c; }
    html.dark .cmap-fontsample { color: #e8efe1; }
    html.dark .cmap-fontopt b { color: #a9b89b; }
    html.dark .cmap-fontopt.is-active { border-color: #6b9f3d; background: rgb(107 159 61 / .22); }
    @media (prefers-reduced-motion: reduce) { .cmap-fontopt { transition: none; } }
    .cmap-text-left { font-size: .68rem; color: var(--color-gray-400); text-align: right; margin-top: .25rem; }
    .cmap-text-hint { font-size: .72rem; color: var(--color-gray-500); line-height: 1.5; margin-top: .7rem; }
    .cmap-text-hint b { font-weight: 800; color: var(--color-gray-700); }
    html.dark .cmap-text-hint b { color: #e8efe1; }
    .cmap-savebtn { background: #4a7c2a; color: #fff; }
    .cmap-savebtn:hover { background: #3d6823; color: #fff; }
    .cmap-mrow small { display: block; font-size: .68rem; font-weight: 500; color: var(--color-gray-400); margin-top: .1rem; }
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
    #cmapEditText { background: var(--color-brand-100); color: var(--color-brand-800); }
    @media (prefers-reduced-motion: reduce) { .cmap-editbar { transition: none; } }
    /* The autosave's only voice: it appears, says the word, and goes away. */
    .cmap-saved { position: absolute; top: .6rem; right: .6rem; z-index: 6; pointer-events: none;
        display: flex; align-items: center; gap: .35rem; padding: .25rem .6rem; border-radius: 999px;
        font-size: .7rem; font-weight: 800; color: var(--color-gray-600);
        background: rgb(255 255 255 / .93); box-shadow: 0 8px 22px -10px rgb(0 0 0 / .55);
        opacity: 0; transform: translateY(-.4rem);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cmap-saved.is-on { opacity: 1; transform: translateY(0); }
    .cmap-saved-dot { width: .45rem; height: .45rem; border-radius: 999px; background: #4a7c2a; flex-shrink: 0; }
    .cmap-saved.is-working .cmap-saved-dot { background: #f5c518; animation: cmapSavePulse 1.1s ease-in-out infinite; }
    .cmap-saved.is-failed { color: #b91c1c; }
    .cmap-saved.is-failed .cmap-saved-dot { background: #dc2626; }
    @keyframes cmapSavePulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
    html.dark .cmap-saved { background: rgb(21 27 18 / .93); color: #cdd8c0; }
    @media (prefers-reduced-motion: reduce) {
        .cmap-saved { transition: opacity .01s linear; }
        .cmap-saved.is-working .cmap-saved-dot { animation: none; }
    }
    /* The press-and-hold ring for dropping a point into an edge. It sits on the
       edge, not under the finger, so it answers the only question the hold
       raises: not "is it counting" but "where is this point going". The sweep
       is linear on purpose — it is a clock, and a clock that eases lies about
       how much of the wait is left. */
    .cmap-hold { position: absolute; z-index: 6; width: 44px; height: 44px; margin: -22px 0 0 -22px;
        pointer-events: none; opacity: 0; transform: scale(.72);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cmap-hold.is-on { opacity: 1; transform: scale(1); }
    .cmap-hold svg { width: 100%; height: 100%; transform: rotate(-90deg); overflow: visible; }
    .cmap-hold circle { fill: none; stroke-width: 4; stroke-linecap: round; }
    .cmap-hold-trk { stroke: rgb(17 24 39 / .3); }
    /* 2πr for r=19 — the whole ring as one dash, hidden, then wound on. */
    .cmap-hold-arc { stroke: #f5c518; stroke-dasharray: 119.4; stroke-dashoffset: 119.4;
        filter: drop-shadow(0 1px 2px rgb(0 0 0 / .5)); }
    .cmap-hold.is-on .cmap-hold-arc { animation: cmapHoldFill var(--hold-ms, 3000ms) linear forwards; }
    @keyframes cmapHoldFill { to { stroke-dashoffset: 0; } }
    .cmap-hold-dot { position: absolute; left: 50%; top: 50%; width: .5rem; height: .5rem;
        margin: -.25rem 0 0 -.25rem; border-radius: 999px; background: #fff;
        box-shadow: 0 0 0 2px rgb(17 24 39 / .55); }
    @media (prefers-reduced-motion: reduce) {
        /* No pop and no sweep — but the press still has to show, or the three
           seconds read as a dead map. The ring simply stands there instead. */
        .cmap-hold { transition: opacity .01s linear; transform: none; }
        .cmap-hold.is-on { transform: none; }
        .cmap-hold.is-on .cmap-hold-arc { animation: none; stroke-dashoffset: 0; opacity: .6; }
    }
    /* Measurement labels ride Google marker labels — these classes style them. */
    .cmap-lbl-g { background: rgb(17 24 39 / .82); border-radius: .45rem; padding: .1rem .4rem; white-space: nowrap; }
    /* The ruler badge that reveals a shape's numbers. It hangs below the point
       it belongs to rather than on it — see BADGE_DISC for why the gap is
       measured in screen pixels and not in metres. */
    .cmap-mbadge { cursor: pointer; line-height: 1; }
    /* A written label. pre-line, not nowrap: the editor takes several lines
       and every one of them has to survive the trip to the ground — the
       measurement labels above are one number and stay on one line. */
    .cmap-txt-g { background: #fff; border: 1.5px solid #111827; border-radius: .45rem;
        padding: .12rem .45rem; box-shadow: 0 2px 6px rgb(0 0 0 / .25);
        white-space: pre-line; text-align: center; line-height: 1.2; }
    .cmap-me-g { background: rgb(17 24 39 / .82); border-radius: .45rem; padding: .05rem .35rem; }
    /* Live-position dots are HTML overlays, not markers: markers cannot
       ripple, and two stacked markers is what buried the name UNDER the dot.
       Here the name hangs below and the ring breathes. */
    .cmap-dot-wrap { position: absolute; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: .18rem; pointer-events: none; }
    .cmap-dot { position: relative; width: 16px; height: 16px; border-radius: 999px; background: var(--dot, #16a34a); border: 2.5px solid #fff; box-shadow: 0 1px 4px rgb(0 0 0 / .45); }
    .cmap-dot::after { content: ''; position: absolute; inset: -3px; border-radius: 999px; border: 2px solid var(--dot, #16a34a); animation: cmapRipple 1.6s ease-out infinite; }
    @keyframes cmapRipple { 0% { transform: scale(1); opacity: .9; } 100% { transform: scale(2.6); opacity: 0; } }
    .cmap-dot-name { background: rgb(17 24 39 / .82); color: #fff; border-radius: .45rem; padding: .05rem .4rem; font-size: .62rem; font-weight: 800; white-space: nowrap; }
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
    /* Corners taken back off a shape that is still being tapped out, waiting
       for Redo. tempPts is the undo half of the same pair — the corners are
       already stacked there in the order they were placed, and a second copy
       of them is a second thing to keep in step. Declared up here with the
       shape it belongs to so syncHistBtns can read it. */
    let draftRedo = [];
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

    /* ---------- how a written label is lettered ----------
     *
     * Four faces, and no @font-face anywhere near them. This map is read
     * standing in a field on whatever bar of signal the field has, and a
     * webfont there is a label that is invisible until the download lands —
     * or forever. So every stack below is families the device already has,
     * ending in the generic that CSS guarantees.
     *
     * `cond` is the honest one: Windows has Arial Narrow, Android answers to
     * sans-serif-condensed (its Roboto Condensed), most desktop Linux has
     * Liberation Sans Narrow — and Apple has nothing condensed under a name
     * worth trusting, so an iPhone quietly gets plain sans. Narrow where
     * narrow exists, readable everywhere, downloaded never. */
    const FONTS = {
        sans: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        serif: 'Georgia, "Times New Roman", Times, serif',
        cond: '"Arial Narrow", "Roboto Condensed", "Liberation Sans Narrow", sans-serif-condensed, sans-serif',
        mono: 'ui-monospace, SFMono-Regular, Menlo, Consolas, "Roboto Mono", "Courier New", monospace',
    };
    // Small enough to sit beside a fence without shouting, big enough to name
    // a whole paddock from across it. 16 is where a new one starts.
    const TXT_MIN = 10, TXT_MAX = 48, TXT_NEW = 16;
    // What a label looked like before any of this existed, and still does.
    const TXT_LEGACY = 11;
    const clampTxt = (n) => Math.min(TXT_MAX, Math.max(TXT_MIN, Math.round(n)));

    /* What face and what size to draw this label at.
     *
     * `width` is stroke thickness for every other kind, and text objects were
     * saved with whatever the pen happened to be set to — so it cannot simply
     * be read as a type size. `font` is the tell: a row that has one was
     * written since labels could be lettered and means its width; a row
     * without one predates that, and keeps the hardcoded 11px and Google's own
     * face, which is exactly how it looks today. */
    function textStyle(o) {
        const family = FONTS[o && o.font] || null;
        return { family, px: family ? clampTxt(parseInt(o.width, 10) || TXT_NEW) : TXT_LEGACY };
    }
    /** The marker label a text object wears — one shape, wherever it is set. */
    function textLabelOpts(o, sizeOverride) {
        const st = textStyle(o);
        const label = {
            text: String((o && o.label) || ''), className: 'cmap-txt-g', color: '#111827',
            fontSize: (sizeOverride ? clampTxt(sizeOverride) : st.px) + 'px', fontWeight: '800',
        };
        // Left unset for the old rows on purpose: naming a family here, even
        // the sans one, would change what every label drawn before today
        // looks like.
        if (st.family) label.fontFamily = st.family;
        return label;
    }
    /* Measurements are asked for, not broadcast.
     *
     * Every side of every shape used to shout its length and every field its
     * area, all at once — so a map with four lots on it was unreadable, and
     * the numbers covered the ground they described. Each shape now wears one
     * small ruler badge; tapping it shows that shape's numbers, tapping it
     * again puts them away. What you opened is remembered per shape. */
    const MEASURE_KEY = 'cmapMeasure:' + SID;
    const measureOpen = (() => {
        try { return new Set(JSON.parse(localStorage.getItem(MEASURE_KEY) || '[]')); } catch (_) { return new Set(); }
    })();
    const saveMeasure = () => {
        try { localStorage.setItem(MEASURE_KEY, JSON.stringify([...measureOpen])); } catch (_) { /* private mode */ }
    };
    // Per shape: the label markers, and the badge that reveals them.
    const measures = new Map();

    function showMeasure(id, on) {
        const m = measures.get(id);
        if (!m) return;
        m.labels.forEach((lbl) => lbl.setMap(on ? map : null));
        if (m.badge) {
            m.badge.setLabel({ text: on ? '×' : '📏', className: 'cmap-mbadge', color: '#fff', fontSize: '11px', fontWeight: '800' });
        }
        if (on) measureOpen.add(String(id)); else measureOpen.delete(String(id));
    }

    /* The badge sat on the very spot it was meant to reveal: a polygon's area
       is written at its centre and a line's length at its midpoint, and the
       badge was placed at exactly those two points — so the number it opened
       appeared underneath it and could not be read.
       It now hangs a fixed 26 screen pixels below that spot. The drop is
       baked into the icon PATH, drawn at scale 1, so path units ARE screen
       pixels: the gap is identical at every zoom, which an offset expressed
       in latitude could never be. labelOrigin follows the disc down so the
       ruler/× rides inside it. */
    const BADGE_DROP = 26;
    // An 11-radius disc centred BADGE_DROP below the origin — written in the
    // same cubic notation as the pin above, which is the notation Google's
    // symbol parser is known here to take.
    const BADGE_DISC = 'M0 15C6.08 15 11 19.92 11 26C11 32.08 6.08 37 0 37'
        + 'C-6.08 37 -11 32.08 -11 26C-11 19.92 -6.08 15 0 15Z';

    /** The badge that opens a shape's numbers, hung under its middle. */
    function measureBadge(parts, id, at, colorStr, labels, meta) {
        // Nothing to reveal, nothing to offer: a badge with no numbers behind
        // it is a pin that does nothing when tapped.
        if (!labels || !labels.length) return null;
        /* Nowhere obvious to hang it is NOT the same answer.
         *
         * This used to bail on a missing anchor too, which meant a shape whose
         * centre could not be found came out as the one shape on the map
         * showing numbers with no way to put them away — and a missing badge
         * is indistinguishable from a feature that was never built, which is
         * exactly why "the area tool has no toggle" took four rounds to pin
         * down. So: bounding centre if the inside search comes back empty,
         * first corner if even that cannot be had, and silence only if there
         * are genuinely no points at all. */
        const mpts = (meta && meta.pts) || null;
        const spot = at
            || (mpts && mpts.length ? centerOf(mpts) : null)
            || (mpts && mpts[0])
            || null;
        if (!spot) return null;
        const badge = new (G().Marker)({
            map, position: LL(spot), clickable: true, zIndex: 60, title: 'Show or hide this shape’s measurements',
            icon: { path: BADGE_DISC, scale: 1, fillColor: colorStr || '#4a7c2a',
                fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2,
                labelOrigin: new (G().Point)(0, BADGE_DROP) },
            label: { text: '📏', className: 'cmap-mbadge', color: '#fff', fontSize: '11px', fontWeight: '800' },
        });
        badge.addListener('click', () => {
            // While erasing, a tap on anything of this shape removes it —
            // the badge must not become a hole in that.
            if (tool === 'erase' || tool === 'edit') return;
            const m = measures.get(id);
            showMeasure(id, !(m && m.labels.length && m.labels[0].getMap()));
            saveMeasure();
        });
        parts.push(badge);
        // Registered here rather than by position in `parts`: the caller
        // pushing one more thing afterwards would otherwise hand the toggle
        // a polyline, which has no label to change.
        // The rest of the entry is what refreshMeasure needs to redo the sums
        // in place when the shape moves — which marker is which side, which
        // one holds the total, which one holds the area.
        measures.set(id, {
            labels: labels || [], badge,
            kind: (meta && meta.kind) || null,
            closed: !!(meta && meta.closed),
            segs: (meta && meta.segs) || [],
            total: (meta && meta.total) || null,
            area: (meta && meta.area) || null,
        });
        showMeasure(id, measureOpen.has(String(id)));
        return badge;
    }

    function segLabels(parts, pts, closed, bag) {
        const n = closed ? pts.length : pts.length - 1;
        const out = [];
        for (let i = 0; i < n; i++) {
            const j = (i + 1) % pts.length;
            const lbl = textMark(mid(pts[i], pts[j]), fmtM(dist(pts[i], pts[j])), 'cmap-lbl-g');
            parts.push(lbl);
            out.push(lbl);
            if (bag) bag.push(lbl);
        }
        // Handed back in edge order so a later reshape can move side 3's
        // number rather than mint a new one.
        return out;
    }

    /* A measurement is recomputed in place, never rebuilt.
     *
     * The badge and the numbers stay the same markers for the life of the
     * shape — moved and re-texted, the way the drawing preview's labels
     * already are. Tearing them down instead would delete the badge under the
     * finger and put a fresh one back, so an open measurement would blink
     * shut every time a corner moved. */
    function relabel(mk, at, text) {
        mk.setPosition(LL(at));
        mk.setLabel({ text, className: 'cmap-lbl-g', color: '#fff', fontSize: '11px', fontWeight: '800' });
    }
    function refreshMeasure(id, pts) {
        const m = measures.get(id);
        if (!m || !pts || pts.length < 2) return;
        // Caught mid-surgery — a ring with two corners is a shape on its way
        // somewhere. The render that lands afterwards will square it up.
        if (m.closed && pts.length < 3) return;
        const parts = layers.get(id) || [];
        const on = measureOpen.has(String(id));
        const n = m.closed ? pts.length : pts.length - 1;
        for (let i = 0; i < n; i++) {
            const j = (i + 1) % pts.length;
            const at = mid(pts[i], pts[j]), txt = fmtM(dist(pts[i], pts[j]));
            if (m.segs[i]) { relabel(m.segs[i], at, txt); continue; }
            // A side that did not exist a moment ago: a point arrived. Born
            // hidden or visible to match whatever the badge is currently
            // saying, so one new side cannot show a number the others don't.
            const lbl = textMark(at, txt, 'cmap-lbl-g');
            lbl.setMap(on ? map : null);
            m.segs[i] = lbl; m.labels.push(lbl); parts.push(lbl);
        }
        // A side that went away takes its number with it — out of `parts`
        // too, or dropObject would later hide a marker already off the map.
        while (m.segs.length > n) {
            const lbl = m.segs.pop();
            lbl.setMap(null);
            const li = m.labels.indexOf(lbl); if (li >= 0) m.labels.splice(li, 1);
            const pi = parts.indexOf(lbl); if (pi >= 0) parts.splice(pi, 1);
        }
        if (m.total) {
            let sum = 0;
            for (let i = 0; i < pts.length - 1; i++) sum += dist(pts[i], pts[i + 1]);
            relabel(m.total, pts[pts.length - 1], 'Σ ' + fmtM(sum));
        }
        const at = anchorOf(m.kind, pts);
        if (!at) return;
        if (m.area) relabel(m.area, at, fmtA(areaOf(pts)));
        if (m.badge) m.badge.setPosition(LL(at));
    }
    /* The badge follows the shape while the shape is still moving.
     *
     * The ask was that the anchor be rechecked and repositioned whenever the
     * shape changed, and "changed" includes the half second a corner is under
     * a thumb, not only the save that lands after it. The live outline is read
     * off the shape itself rather than passed in, so it does not matter who
     * moved it: a teardrop pin, one of Google's own handles, or a drag of the
     * whole thing. Every OTHER way a shape changes — a teammate's edit over
     * Echo, undo, redo, deleting a point, adding one, closing a ring — drops
     * the shape and renders it again, which computes the anchor from the new
     * points anyway. */
    let trackFrame = null;
    const trackIds = new Set();
    function trackMeasure(id) {
        if (!measures.has(id)) return;
        trackIds.add(id);
        // A drag fires far faster than a badge needs to move, and finding the
        // inside centre is a search — once a frame is plenty.
        if (trackFrame) return;
        trackFrame = requestAnimationFrame(() => {
            trackFrame = null;
            const ids = [...trackIds];
            trackIds.clear();
            ids.forEach((k) => {
                // The frame can land after the shape has gone: a teammate
                // cleared the map, or the save came back and re-rendered.
                const parts = layers.get(k);
                const first = parts && parts[0];
                if (!first || !first.getPath) return;
                const pts = [];
                first.getPath().forEach((v) => pts.push([v.lat(), v.lng()]));
                refreshMeasure(k, pts);
            });
        });
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
                // The numbers describe where the shape is now, not where it
                // was when the finger went down — badge included.
                trackMeasure(o.id);
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
                        carryMeasure(cur.id, res.data.object.id);
                        renderObject(res.data.object);
                        // One action, one undo step — see applyStep's 'swap'.
                        pushHist({ type: 'swap', added: res.data.object, removed: cur });
                        await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: cur.id } }).catch(() => {});
                        dropObject(cur.id, true);
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
            // The point a three-second hold just asked for, handed straight
            // back: picked up, fattened, and draggable without another tap.
            if (pendingPoint && pendingPoint.id === o.id && pendingPoint.index === i) {
                pendingPoint = null;
                selectVertex(o, i, m, parts);
            }
            parts.push(m);
        });
    }
    let extending = null, pendingExtend = null;
    function beginExtend(o, i, marker, quiet) {
        if (o.kind === 'rect' || o.kind === 'area') return;       // already closed shapes
        // The long-press that gets here started half a second ago, and the map
        // can have been cleared or replaced since — this pin is off the canvas
        // and the shape behind it is not there to draw on from.
        if (!objIndex.has(o.id)) return;
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
            // The one conversion of the three that never carried this, so a
            // multi-line with its numbers open closed into an area with them
            // all shut — the moment they matter most, since closing the ring
            // is what gives the shape an area to report in the first place.
            carryMeasure(cur.id, res.data.object.id);
            renderObject(res.data.object);
            // One undo re-opens the ring, and that is the whole of it.
            pushHist({ type: 'swap', added: res.data.object, removed: cur });
            await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: cur.id } }).catch(() => {});
            dropObject(cur.id, true);
            if (window.toast) toast('Closed into an area.');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }
    function centerOf(pts) {
        const b = new (G().LatLngBounds)();
        pts.forEach((p) => b.extend(LL(p)));
        return [b.getCenter().lat(), b.getCenter().lng()];
    }
    /* Where a field's number belongs.
     *
     * centerOf is the centre of the BOUNDING BOX, which is the middle only of
     * shapes that fill their box. Bend a paddock around a creek, or walk an L
     * around a barn, and that point lands in the creek or on the barn —
     * outside the field whose area it claims to state. That was the
     * complaint, and it is not a rounding error: on a bad L it is half a
     * paddock out.
     *
     * So the anchor is the pole of inaccessibility instead: the interior
     * point furthest from any edge. It is what a person means by "the middle
     * of that field", it is still dead centre of a rectangle, and — the whole
     * point — it is inside. There is no tidy formula for it, so it is found
     * the way it always is: a coarse grid over the box, keep the best cell,
     * look again around the winner, six passes. That settles to well under a
     * fence post, which is finer than a badge can be placed anyway.
     *
     * The search runs in a flat local frame with longitude squeezed by
     * cos(latitude), so "furthest from any edge" means what the eye means by
     * it and not what it means in raw degrees. Nothing in the loop builds a
     * google.maps object: the grid probes the outline several hundred times,
     * and containsLocation would want a Polygon and a LatLng for every one of
     * them. It is not needed either — the search proves containment itself,
     * because the winning depth is positive or we fall back and say so. */
    const DEG = Math.PI / 180;
    function insideCenter(pts) {
        // Two corners, or three in a row: there is no inside to be central
        // in. It is a line wearing a polygon's name — anchor it like one.
        if (!pts || pts.length < 3) return alongMid(pts || []);
        const kx = Math.cos((pts.reduce((s, p) => s + p[0], 0) / pts.length) * DEG) || 1;
        const P = pts.map((p) => [p[1] * kx, p[0]]);          // x east, y north, evenly scaled
        let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
        P.forEach((q) => {
            x0 = Math.min(x0, q[0]); y0 = Math.min(y0, q[1]);
            x1 = Math.max(x1, q[0]); y1 = Math.max(y1, q[1]);
        });
        const w = x1 - x0, h = y1 - y0;
        // Collinear, or every corner stacked on one spot: a box with no width
        // to lay a grid across, and dividing by it would find only NaN.
        if (!(w > 0) || !(h > 0)) return centerOf(pts);
        /** How deep inside a point is: distance to the nearest edge, negative outside. */
        const depth = (x, y) => {
            let near = Infinity, inside = false;
            for (let i = 0, j = P.length - 1; i < P.length; j = i++) {
                const ax = P[j][0], ay = P[j][1], bx = P[i][0], by = P[i][1];
                // Even-odd ray cast. An outline that crosses itself gets the
                // reading even-odd gives it — lobes in, overlaps out — which
                // is as good an answer as exists for a shape nobody meant to
                // draw, and it cannot divide by zero: the test only runs on
                // edges that straddle this latitude, so ay and by differ.
                if ((ay > y) !== (by > y) && x < ax + (bx - ax) * (y - ay) / (by - ay)) inside = !inside;
                const vx = bx - ax, vy = by - ay, l2 = vx * vx + vy * vy;
                const t = l2 > 0 ? Math.max(0, Math.min(1, ((x - ax) * vx + (y - ay) * vy) / l2)) : 0;
                const dx = x - (ax + vx * t), dy = y - (ay + vy * t);
                near = Math.min(near, dx * dx + dy * dy);
            }
            return (inside ? 1 : -1) * Math.sqrt(near);
        };
        let best = null, gx = x0, gy = y0, gw = w, gh = h;
        for (let pass = 0; pass < 6; pass++) {
            for (let i = 0; i <= 10; i++) {
                for (let j = 0; j <= 10; j++) {
                    const x = gx + gw * i / 10, y = gy + gh * j / 10;
                    const d = depth(x, y);
                    if (!best || d > best.d) best = { x, y, d };
                }
            }
            // The next pass looks only around the winner, at a quarter of the
            // span. The winner itself is re-probed dead centre of that window,
            // so a pass can sharpen the answer but never lose it.
            gw /= 4; gh /= 4; gx = best.x - gw / 2; gy = best.y - gh / 2;
        }
        // A sliver thinner than the finest cell, or a figure-eight whose grid
        // never landed in a lobe. The box centre is what this did for years
        // and it beats no badge at all.
        if (!best || !(best.d > 0)) return centerOf(pts);
        return [best.y, best.x / kx];
    }
    /* An open path hung its badge at the midpoint of a straight line drawn
       between its two ENDS — so a fence that zigzags, or one that doubles
       back, wore its length in the middle of the next paddock. It now sits ON
       the path, half the walk from either end. For a plain two-point line
       that is exactly where it always was. */
    function alongMid(pts) {
        if (!pts || !pts.length) return null;
        if (pts.length < 2) return pts[0];
        const seg = [];
        let total = 0;
        for (let i = 0; i < pts.length - 1; i++) { const d = dist(pts[i], pts[i + 1]); seg.push(d); total += d; }
        // A path that never left its first point has no halfway to find.
        if (!(total > 0)) return pts[0];
        let left = total / 2;
        for (let i = 0; i < seg.length; i++) {
            if (left > seg[i]) { left -= seg[i]; continue; }
            const f = left / seg[i];
            const sph = G().geometry.spherical;
            // interpolate walks the great circle the segment is actually
            // drawn along, so the badge sits on the line, not beside it.
            if (sph && sph.interpolate) {
                const q = sph.interpolate(LL(pts[i]), LL(pts[i + 1]), f);
                return [q.lat(), q.lng()];
            }
            return [pts[i][0] + (pts[i + 1][0] - pts[i][0]) * f, pts[i][1] + (pts[i + 1][1] - pts[i][1]) * f];
        }
        return pts[pts.length - 1];
    }
    /** The one spot a shape's numbers belong: on the shape, and central to it. */
    function anchorOf(kind, pts) {
        if (!pts || !pts.length) return null;
        if (pts.length === 1) return pts[0];
        return (kind === 'rect' || kind === 'area') ? insideCenter(pts) : alongMid(pts);
    }

    /* One renderer for local, remote and loaded shapes — measurements are
       recomputed from the points, so every viewer reads identical numbers. */
    function renderObject(o) {
        if (layers.has(o.id)) return;
        objIndex.set(o.id, o);
        const parts = [];
        // The numbers this shape can show, gathered so one badge can reveal
        // or hide them all together.
        const mlabels = [];
        const style = { map, strokeColor: o.color || '#f5c518', strokeWeight: o.width || 3, clickable: true };
        const pts = o.points;
        if (o.kind === 'pen' || o.kind === 'line' || o.kind === 'path' || o.kind === 'arrow') {
            parts.push(new (G().Polyline)({ ...style, path: pts.map(LL),
                icons: o.kind === 'arrow' ? [ARROW_HEAD(style.strokeColor)] : null }));
            // Arrow tips stay clean — no teardrop pins. Move or reshape an
            // arrow with the Select tool.
            if (o.kind !== 'pen' && o.kind !== 'arrow') {
                vertexPins(parts, o, pts, style.strokeColor);
                const segs = segLabels(parts, pts, false, mlabels);
                let tot = null;
                if (o.kind === 'path' && pts.length > 2) {
                    let total = 0;
                    for (let i = 0; i < pts.length - 1; i++) total += dist(pts[i], pts[i + 1]);
                    tot = textMark(pts[pts.length - 1], 'Σ ' + fmtM(total), 'cmap-lbl-g');
                    parts.push(tot); mlabels.push(tot);
                }
                measureBadge(parts, o.id, anchorOf(o.kind, pts), style.strokeColor, mlabels,
                    { kind: o.kind, closed: false, segs, total: tot, pts });
            }
        } else if (o.kind === 'rect') {
            const b = new (G().LatLngBounds)(LL(pts[0]), LL(pts[1]));
            const sw = b.getSouthWest(), ne = b.getNorthEast();
            const c = [[sw.lat(), sw.lng()], [sw.lat(), ne.lng()], [ne.lat(), ne.lng()], [ne.lat(), sw.lng()]];
            parts.push(new (G().Polygon)({ ...style, paths: c.map(LL), fillColor: style.strokeColor, fillOpacity: .08 }));
            vertexPins(parts, o, c, style.strokeColor);
            const segs = segLabels(parts, c, true, mlabels);
            // The area label and the badge share one anchor on purpose: the
            // disc hangs BADGE_DROP below the number it opens, and that only
            // works while they are measured from the same spot.
            const at = anchorOf('rect', c);
            const ar = textMark(at, fmtA(areaOf(c)), 'cmap-lbl-g');
            parts.push(ar); mlabels.push(ar);
            measureBadge(parts, o.id, at, style.strokeColor, mlabels,
                { kind: 'rect', closed: true, segs, area: ar, pts: c });
        } else if (o.kind === 'area') {
            parts.push(new (G().Polygon)({ ...style, paths: pts.map(LL), fillColor: style.strokeColor, fillOpacity: .1 }));
            vertexPins(parts, o, pts, style.strokeColor);
            const segs2 = segLabels(parts, pts, true, mlabels);
            const at2 = anchorOf('area', pts);
            const ar2 = textMark(at2, fmtA(areaOf(pts)), 'cmap-lbl-g');
            parts.push(ar2); mlabels.push(ar2);
            measureBadge(parts, o.id, at2, style.strokeColor, mlabels,
                { kind: 'area', closed: true, segs: segs2, area: ar2, pts });
        } else if (o.kind === 'text') {
            const tm = textMark(pts[0], o.label || '', 'cmap-txt-g', '#111827');
            // Its own face and its own size, for whoever is looking: the
            // person who typed it, a teammate it arrived at over Echo, and
            // this same screen after a reload — all three land here.
            tm.setLabel(textLabelOpts(o));
            // Labels are decoration, but a text OBJECT must catch taps or it
            // could never be erased, moved or rewritten.
            tm.setClickable(true);
            // Press the words and they come with you. Every other shape is
            // moved by picking it up with Select & edit first; a label is the
            // one thing on this map small enough that finding it, tapping it
            // and then dragging it is three gestures to do one thing.
            tm.setDraggable(textDraggable());
            tm.addListener('dragend', (ev) => {
                // A picked-up label is already owed this save by beginEdit's
                // own dragend, which goes through scheduleSave. Both firing
                // posts the move twice and stacks two undo steps for one drag.
                if (editing && editing.o.id === o.id) return;
                commitTextMove(o, ev.latLng);
            });
            parts.push(tm);
        }
        // Taps on a shape route by tool: erase removes it for everyone, edit
        // picks it up for dragging and reshaping.
        parts.forEach((p) => p.addListener && p.addListener('click', () => {
            if (tool === 'erase') {
                pushHist({ type: 'remove', object: objIndex.get(o.id) || o, measured: wasMeasured(o.id) });
                api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
                dropObject(o.id, true);
            } else if (tool === 'edit') {
                beginEdit(o, parts);
                // A label's whole content is words, so picking one up and
                // opening what it says are the same intention. A drag never
                // gets here — Google fires click only when nothing moved.
                if (o.kind === 'text') openTextSheet(objIndex.get(o.id) || o);
            } else if (tool === 'text' && o.kind === 'text') {
                // With the Text tool out, a tap on ground writes a new label
                // and a tap on a label rewrites that one — otherwise the only
                // thing this gesture could do was stack a second label on top
                // of the first.
                openTextSheet(objIndex.get(o.id) || o);
            }
        }));
        if (pendingEdit === o.id) { pendingEdit = null; beginEdit(o, parts); }
        layers.set(o.id, parts);
    }
    /* `forget` separates a shape being REMOVED from a shape being REDRAWN.
     *
     * Nearly every edit in this file is a dropObject immediately followed by a
     * renderObject on the same id — a vertex nudged, a shape dragged, a point
     * added, a teammate's update arriving. Evicting the remembered-open flag on
     * the way through meant the numbers you had open closed themselves every
     * time you touched the shape, which for an area lot is every few seconds.
     * The eviction is still right when the shape is genuinely gone, so the
     * callers that actually delete something say so. */
    function dropObject(id, forget) {
        if (extending && extending.id === id) cancelExtend();
        if (editing && editing.o.id === id) endEdit();
        (layers.get(id) || []).forEach((p) => p.setMap(null));
        layers.delete(id); objIndex.delete(id); measures.delete(id);
        // A shape that no longer exists should not keep a place in the
        // remembered set — a season of edits would fill it with ghosts.
        if (forget && measureOpen.delete(String(id))) saveMeasure();
    }
    /* Carry the open flag onto the id that replaces it. Several edits do not
     * update a shape but mint a new one — a dragged box corner becomes an area,
     * a closed multi-line becomes an area, undo re-adds with a fresh id — and
     * the new id was never in the set, so the numbers vanished at exactly the
     * moment the shape became the kind whose numbers matter most. */
    function carryMeasure(oldId, newId) {
        if (oldId == null || newId == null || String(oldId) === String(newId)) return;
        if (!measureOpen.has(String(oldId))) return;
        measureOpen.delete(String(oldId));
        measureOpen.add(String(newId));
        saveMeasure();
    }
    // Bumped every time the canvas is replaced wholesale, so a gesture that
    // began against the old shapes can tell that they are gone rather than
    // finishing itself against whatever took their place.
    let canvasGen = 0;
    /* measureOpen deliberately survives this. dropAll runs when a saved map is
     * opened, when a blank canvas is started and when anyone clears — and
     * opening a saved map is the ordinary way into the Maps module, so wiping
     * the set there meant a map you saved with its numbers showing came back
     * with them all put away, every single time. Stale ids cost a few bytes in
     * localStorage and are pruned by the removal paths; a map that forgets what
     * you asked to see costs the point of the feature. */
    function dropAll() { canvasGen++; clearTemp(); cancelExtend(); endEdit(); pendingPoint = null; layers.forEach((parts) => parts.forEach((p) => p.setMap(null))); layers.clear(); objIndex.clear(); measures.clear(); }

    /* Undo is a history of inverse calls against the same endpoints the
       actions used, so every step also lands live for the team. Re-adding a
       removed shape mints a fresh id — the entries swap ids as they flow
       between the stacks, so chains keep working. */
    const objIndex = new Map();     // id -> shaped object (latest)
    const histUndo = [], histRedo = [];
    function syncHistBtns() {
        const u = document.getElementById('cmapUndo'), r = document.getElementById('cmapRedo');
        // Corners pending on a half-drawn shape are the first thing either
        // button answers to, so they arm it just as a committed step does.
        // Otherwise Undo would go grey with four corners down and Redo would
        // refuse to put back the one you had just taken off.
        const drafting = (tool === 'path' || tool === 'area');
        if (u) u.disabled = !histUndo.length && !(drafting && tempPts.length);
        if (r) r.disabled = !histRedo.length && !(drafting && draftRedo.length);
    }
    /* One clock for both stacks. A half-drawn corner and a committed change are
     * different kinds of thing, but Undo means "the last thing I did" and the
     * user does not sort them into kinds. Serving corners first regardless of
     * when they were placed made a saved, team-broadcast change unreachable
     * behind a corner tapped before it. */
    let actSeq = 0;
    function pushHist(entry) {
        entry.seq = ++actSeq;
        histUndo.push(entry);
        if (histUndo.length > 30) histUndo.shift();
        histRedo.length = 0;
        syncHistBtns();
        // Every change this client makes to the map passes through here, which
        // makes it the one place the autosave has to listen. Shapes arriving
        // from the room do not — the person who moved it is the one who saves.
        markMapDirty();
    }
    async function reAdd(object) {
        const res = await api(`${URLS.push}?scheduleId=${SID}`, {
            // font travels with it, or undoing the deletion of a label would
            // put the words back in a face nobody chose.
            method: 'POST', body: { kind: object.kind, points: object.points, color: object.color, width: object.width, font: object.font || null, label: object.label },
        });
        renderObject(res.data.object);
        return res.data.object;
    }
    /* Was this shape showing its numbers when it went away, and put them back
     * on whatever id replaces it.
     *
     * dropObject(id, true) forgets the flag on purpose — a season of edits
     * would otherwise fill the remembered set with ghosts — so the answer has
     * to be taken before the removal and carried on the history step itself.
     * Without this an area you had open, undone and redone came back with its
     * hectares hidden behind a badge the size of a thumbnail. */
    const wasMeasured = (id) => measureOpen.has(String(id));
    function restoreMeasured(id, on) {
        if (!on || id == null) return;
        measureOpen.add(String(id));
        saveMeasure();
    }
    async function applyStep(step, into) {
        if (step.type === 'add') {
            const shown = wasMeasured(step.object.id);
            await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: step.object.id } }).catch(() => {});
            dropObject(step.object.id, true);
            into.push({ type: 'remove', object: step.object, measured: shown });
        } else if (step.type === 'remove') {
            const fresh = await reAdd(step.object);
            // Marked before nothing, since reAdd has already rendered — so the
            // numbers are opened the way any other reveal opens them.
            if (step.measured) { restoreMeasured(fresh.id, true); showMeasure(fresh.id, true); }
            into.push({ type: 'add', object: fresh });
        } else if (step.type === 'swap') {
            /* One conversion, one press.
             *
             * A box that becomes an area — a corner dragged free, a point held
             * into an edge, a ring closed from a pin — used to file two steps,
             * an add and a remove. One Undo popped only the remove, so the old
             * box came back while its replacement was still lying on the same
             * ground, and it took a second press to work out which of the two
             * shapes you were looking at. It is one action, so it is one step:
             * the new shape goes, the old one comes back, and the inverse is
             * filed for Redo to walk straight back the other way. */
            const shown = wasMeasured(step.added.id);
            await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: step.added.id } }).catch(() => {});
            dropObject(step.added.id, true);
            const back = await reAdd(step.removed);
            if (shown) { restoreMeasured(back.id, true); showMeasure(back.id, true); }
            into.push({ type: 'swap', added: back, removed: step.added });
        } else if (step.type === 'update') {
            await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: step.id, points: step.before } }).catch(() => {});
            const cur = objIndex.get(step.id);
            dropObject(step.id);
            if (cur) renderObject({ ...cur, points: step.before });
            into.push({ type: 'update', id: step.id, before: step.after, after: step.before });
        } else if (step.type === 'label') {
            /* A label's words, face and size — everything about it that is not
             * where it sits. These used to file no step at all, on the reasoning
             * that undo is a stack of geometry. But the Undo button does not go
             * grey while that is true: it still held whatever geometry step came
             * before, so renaming a label and pressing Undo reverted somebody's
             * fence line instead, and posted that to the whole team. A stack you
             * can add to silently is worse than one that carries an extra kind
             * of step. */
            await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: step.id, ...step.before } }).catch(() => {});
            const cur = objIndex.get(step.id);
            if (cur) { dropObject(step.id); renderObject({ ...cur, ...step.before }); }
            into.push({ type: 'label', id: step.id, before: step.after, after: step.before });
        } else if (step.type === 'clear') {
            const restored = [];
            for (const o of step.objects) restored.push(await reAdd(o));
            into.push({ type: 'unclear', objects: restored });
        } else if (step.type === 'unclear') {
            for (const o of step.objects) {
                await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: o.id } }).catch(() => {});
                dropObject(o.id, true);
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
        // applyStep files its inverse straight onto the other stack rather than
        // through pushHist, so undo and redo have to say so themselves.
        markMapDirty();
    }
    /** `extra` overrides the pen's own settings — a label's width is a type
        size, not a stroke, and it brings a font with it. */
    async function saveObject(kind, pts, label, extra) {
        try {
            const res = await api(`${URLS.push}?scheduleId=${SID}`, {
                method: 'POST', body: { kind, points: pts, color, width, label: label || null, ...(extra || {}) },
            });
            /* A shape you just drew shows its numbers without being asked.
             *
             * Measurements are remembered per shape id, and a shape that has
             * only just been created was never in that set — so an area drawn
             * corner by corner arrived with its hectares hidden behind a badge
             * the size of a thumbnail, which reads as "the area tool does not
             * show the area". Measuring is the reason anybody draws one. Marked
             * before the render so the paint below opens it in the same breath,
             * with no flash of a closed shape. */
            if (kind !== 'pen' && kind !== 'text') {
                measureOpen.add(String(res.data.object.id));
                saveMeasure();
            }
            renderObject(res.data.object);
            pushHist({ type: 'add', object: res.data.object });
            // A label says its own thing about what to do next, and pen
            // strokes say nothing at all.
            if (kind !== 'pen' && kind !== 'text' && window.toast) toast('Saved to the team map.');
            return res.data.object;
        } catch (e) { if (window.toast) toast(e.message, 'error'); return null; }
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
    let tempLabels = [], tempArea = null, tempBadge = null;
    /* The half-drawn shape gets the same off-switch the finished one has.
     *
     * Every finished shape wears a ruler badge you tap to put its numbers
     * away. The shape being tapped out wore nothing — and with the Area tool
     * that is not a passing state, it is where you live: corner after corner,
     * side lengths and a running hectare figure painted over the very ground
     * you are pacing, with no way to clear them until you press Finish. A box
     * never showed the problem because a box is one drag and it is over.
     *
     * Default open, because the running area is the reason anybody reaches for
     * this tool. The choice sticks across shapes — put the numbers away once
     * and the next ring you tap out starts quiet. */
    let draftMeasureOn = true;
    const draftBadgeLabel = () => ({
        text: draftMeasureOn ? '×' : '📏', className: 'cmap-mbadge',
        color: '#fff', fontSize: '11px', fontWeight: '800',
    });
    function paintDraftMeasure() {
        tempLabels.forEach((m) => m.setMap(draftMeasureOn ? map : null));
        if (tempArea) tempArea.setMap(draftMeasureOn ? map : null);
        if (tempBadge) tempBadge.setLabel(draftBadgeLabel());
    }
    /* One disc under the shape being drawn, doing exactly what a finished
     * shape's badge does — same drop below the anchor, same × and ruler — so
     * the gesture is learned once and works everywhere. Moved on each preview
     * frame rather than rebuilt, for the same reason the side labels are.
     *
     * Only the two tap-out tools get one. Line, box and freehand reach here
     * mid-drag, where a badge would appear and vanish inside a second and be
     * a clickable target under a finger that is already busy. */
    function syncDraftBadge(ring) {
        const nothingToHide = !tempLabels.length && !tempArea;
        if (nothingToHide || (tool !== 'path' && tool !== 'area')) {
            if (tempBadge) { tempBadge.setMap(null); tempBadge = null; }
            return;
        }
        const at = anchorOf(ring ? 'area' : 'path', tempPts) || tempPts[0];
        if (!at) return;
        if (tempBadge) tempBadge.setPosition(LL(at));
        else {
            tempBadge = new (G().Marker)({
                map, position: LL(at), clickable: true, zIndex: 60,
                title: 'Show or hide this shape’s measurements',
                icon: { path: BADGE_DISC, scale: 1, fillColor: color, fillOpacity: .95,
                    strokeColor: '#fff', strokeWeight: 2,
                    labelOrigin: new (G().Point)(0, BADGE_DROP) },
                label: draftBadgeLabel(),
            });
            tempBadge.addListener('click', () => {
                draftMeasureOn = !draftMeasureOn;
                paintDraftMeasure();
            });
        }
        // Labels born on this frame arrive on the map whatever the badge says,
        // so the answer is applied after they exist rather than before.
        paintDraftMeasure();
    }
    // Cleared with the side labels, or a finished ring would leave its running
    // total floating over the next shape somebody starts.
    function dropTempLabels() {
        tempLabels.forEach((m) => m.setMap(null)); tempLabels = [];
        if (tempArea) { tempArea.setMap(null); tempArea = null; }
        if (tempBadge) { tempBadge.setMap(null); tempBadge = null; }
    }
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

        /* The number the shape is being drawn FOR.
         *
         * Only the sides were ever drawn while tapping out a ring, so an area
         * told you the length of each fence and never how much ground it held
         * until you had finished and gone looking for its badge. The box tool
         * had no such gap — you drag it and watch the hectares change — which
         * is exactly the comparison that was made. Three corners is the first
         * moment there is an area to speak of, and it is recomputed on every
         * tap and every dragged corner, so it counts up with the ring. */
        if (ring) {
            const txt = fmtA(areaOf(tempPts));
            const at = anchorOf('area', tempPts);
            if (tempArea) { tempArea.setPosition(LL(at)); tempArea.setLabel({ text: txt, className: 'cmap-lbl-g', color: '#fff', fontSize: '11px', fontWeight: '800' }); }
            else tempArea = textMark(at, txt, 'cmap-lbl-g');
        } else if (tempArea) {
            // Back under three corners: there is no ground enclosed to report.
            tempArea.setMap(null); tempArea = null;
        }
        syncDraftBadge(ring);
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
        // Corners taken back belong to the shape they came off. Kept, they
        // would let a Redo after a tool change push somebody's old corner into
        // whatever is being drawn now. setTool calls through here, so that is
        // the tool-change case covered too.
        draftRedo = [];
        if (tempShape) { tempShape.setMap(null); tempShape = null; }
        document.getElementById('cmapFinish').hidden = true;
        dropTempDots();
        dropTempLabels();
        sendTrace(true);
        syncHistBtns();
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
    /* ---------- moving a label by hand ----------
     *
     * Which tools leave a label free to be picked up. Direct manipulation was
     * the ask, so there is no tool to choose first — but a label sitting under
     * the finger must not eat a gesture that already means something else.
     * Text means "write a new one here", erase means "delete what I touch",
     * and the drawing tools own the finger outright. Pan and Select & edit are
     * the two that are not already spending it, which is the same pair the
     * long-press-for-a-point handler carves out for the same reason. */
    function textDraggable() { return tool === 'pan' || tool === 'edit'; }
    /* A marker's draggable flag is set once, when it is rendered, and the tool
     * changes long after that — so every label ALREADY on the map has to be
     * told, not just the next one drawn. */
    function syncTextDrag() {
        objIndex.forEach((o, id) => {
            if (o.kind !== 'text') return;
            const m = (layers.get(id) || [])[0];
            if (m && m.setDraggable) m.setDraggable(textDraggable());
        });
    }
    /* Where a dragged label landed, made to stick.
     *
     * The same road every reshape takes — the update endpoint with the id and
     * the new points, a history step so undo walks it back, and a re-render off
     * what the server returned rather than what the finger left behind. pushHist
     * is also what marks the map dirty, so the autosave hears about this move
     * exactly like any other.
     *
     * Only where it landed goes to the room, never the journey. A half-drawn
     * shape streams itself because a teammate watching a fence being paced out
     * learns something from the line so far; a label in flight is one word
     * hovering over the wrong field, and it would cost a write per frame to
     * show it. The update broadcast on release moves it for everyone at once. */
    async function commitTextMove(o, latLng) {
        const cur = objIndex.get(o.id) || o;
        const np = [[latLng.lat(), latLng.lng()]];
        try {
            const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: cur.id, points: np } });
            // The finger is off it but the network is not, and the room can
            // clear the map or open another one inside that wait. Re-rendering
            // then would strand one label of the old map in the middle of the
            // new one — and mark that new map dirty so it saved that way.
            if (!objIndex.has(cur.id)) return;
            pushHist({ type: 'update', id: cur.id, before: cur.points, after: res.data.object.points });
            dropObject(cur.id);
            renderObject(res.data.object);
        } catch (e) {
            // The words are sitting where the finger dropped them and the
            // server never agreed to it. Put them back on the last spot the
            // map still believes in, rather than leaving a label that looks
            // moved and is not.
            const m = (layers.get(cur.id) || [])[0];
            if (m && m.setPosition && cur.points && cur.points[0]) m.setPosition(LL(cur.points[0]));
            if (window.toast) toast(e.message, 'error');
        }
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
        // Labels that are already out there follow the tool too — otherwise a
        // map drawn before the tool changed keeps whatever it was rendered
        // with, and half the labels answer to a gesture the other half ignore.
        syncTextDrag();
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
    /* One corner down, and everything that says so: the point, its dot, the
       outline, the numbers, and whether Finish and the history buttons are
       reachable yet. Redo places a corner through here too, so a put-back
       corner is the same corner in every respect — draggable dot included. */
    function placeCorner(p) {
        tempPts.push(p); previewTemp(tool === 'area');
        // Each tapped corner shows itself at once — and stays grabbable:
        // drag a dot to move the point and the line re-shapes under it.
        const idx = tempPts.length - 1;
        // Stamped on the dot, which is already popped in step with tempPts, so
        // the two cannot drift the way a parallel array would.
        const dotSeq = ++actSeq;
        const dot = new (G().Marker)({
            map, position: LL(p), draggable: true, crossOnDrag: false,
            icon: pinIcon(),
        });
        dot.__seq = dotSeq;
        dot.addListener('drag', (ev) => {
            tempPts[idx] = [ev.latLng.lat(), ev.latLng.lng()];
            previewTemp(tool === 'area');
        });
        if (idx === 0) dot.addListener('click', () => {
            if ((tool === 'path' || tool === 'area') && tempPts.length >= 3) closeTempAsArea();
        });
        tempDots.push(dot);
        document.getElementById('cmapFinish').hidden = tempPts.length < 2;
        syncHistBtns();
    }
    /* Undo, while the shape is still being tapped out.
     *
     * The history below this only ever held committed shapes, so pressing Undo
     * with four corners down reached straight past them and popped the last
     * finished step — deleting a shape the team had actually agreed on, over
     * the network, because somebody mis-tapped a corner. A box never showed
     * this: a box is one drag and there is nothing pending to reach past.
     * Corners come off first now, and the committed stack is only reached once
     * none are left. Gated to the two tap-out tools because they are the only
     * ones where tempPts outlives the gesture — line, box and freehand fill it
     * mid-drag and clear it on release. */
    function takeBackCorner() {
        if (tool !== 'path' && tool !== 'area') return false;
        if (!tempPts.length) return false;
        // Only if the corner really is the more recent act. Otherwise the
        // committed step wins and this defers to it, which is the whole point
        // of stamping them from one counter.
        const top = histUndo[histUndo.length - 1];
        const dotTop = tempDots[tempDots.length - 1];
        if (top && dotTop && (top.seq || 0) > (dotTop.__seq || 0)) return false;
        const gone = tempPts.pop();
        const dot = tempDots.pop();
        // The point remembers when it was placed, so putting it back can be
        // ordered against the committed stack the same way taking it off was.
        if (dot) { gone.__seq = dot.__seq; dot.setMap(null); }
        draftRedo.push(gone);
        // Everything the tap put on screen comes back off with it: the outline
        // redraws, the side lengths recount, and the running area drops away by
        // itself once the ring is back under three corners.
        previewTemp(tool === 'area');
        document.getElementById('cmapFinish').hidden = tempPts.length < 2;
        syncHistBtns();
        return true;
    }
    function putBackCorner() {
        if (tool !== 'path' && tool !== 'area') return false;
        if (!draftRedo.length) return false;
        // Mirrors the take-back: redo the more recently undone of the two.
        const top = histRedo[histRedo.length - 1];
        if (top && (top.seq || 0) > (draftRedo[draftRedo.length - 1].__seq || 0)) return false;
        placeCorner(draftRedo.pop());
        return true;
    }
    function onTap(latLng) {
        const p = [latLng.lat(), latLng.lng()];
        if (tool === 'path' || tool === 'area') {
            if (tempPts.length >= 3 && proj.getProjection()) {
                const a = proj.getProjection().fromLatLngToContainerPixel(latLng);
                const f = proj.getProjection().fromLatLngToContainerPixel(LL(tempPts[0]));
                if (a && f && Math.hypot(a.x - f.x, a.y - f.y) < 18) { closeTempAsArea(); return; }
            }
            // A fresh tap is a new branch: whatever was taken back is not
            // coming home now, exactly as pushHist drops the committed redos.
            draftRedo = [];
            placeCorner(p);
        } else if (tool === 'text') {
            // Nothing is created here. The ground is remembered, the sheet
            // asks, and only pressing its button writes anything — so backing
            // out of a label leaves the map exactly as it was.
            openTextSheet(null, p);
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
    // pendingPoint: a point that has just been inserted on purpose and should
    // come back from the re-render already held, so the finger that asked for
    // it can drag it without hunting for it first.
    let editing = null, saveTimer = null, pendingEdit = null, selVertex = null, pendingPoint = null;
    function showEditBar(o) {
        const KINDS = { pen: 'drawing', line: 'line', path: 'multi-line', rect: 'box', area: 'area', text: 'label', arrow: 'arrow' };
        document.getElementById('cmapEditLbl').textContent = 'Editing ' + (KINDS[o.kind] || 'shape');
        document.getElementById('cmapDelPoint').hidden = true;
        // Only a label has words behind it.
        document.getElementById('cmapEditText').hidden = o.kind !== 'text';
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
        dropObject(o.id, true);
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
                // The gesture is over but the network is not, and the room can
                // clear the map or open another one inside that wait. The shape
                // this was holding is off the canvas by then — re-rendering it
                // would strand one shape of the old map in the middle of the
                // new one, and mark that new map dirty so it saved that way.
                if (!objIndex.has(o.id)) return;
                pushHist({ type: 'update', id: o.id, before: o.points, after: res.data.object.points });
                endEdit();
                dropObject(o.id);
                renderObject(res.data.object);
                // Stay picked up, so nudging can continue without re-tapping.
                if (tool === 'edit') beginEdit(res.data.object, layers.get(res.data.object.id));
            } catch (e) { if (window.toast) toast(e.message, 'error'); }
        }, 700);
    }
    /* Google's `editable` sells two things in one box: the handles on the
       corners, which resize a shape, and the ghost handles halfway along every
       side, which ADD a corner the moment they are nudged. There is no flag for
       one without the other, and the ghosts sit exactly where a thumb lands
       when it means to drag the shape — so shapes were quietly growing points
       nobody asked for. Since the option does not exist, the insertion is
       undone instead: the point goes back out on the same tick it came in, and
       the shape is what it was.
       The deliberate insertion never reaches here. That one goes out to the
       server and comes back as a fresh render, so there is no live path for it
       to fire insert_at on — the only thing that can fire it is a ghost. */
    let pathSurgery = false;      // our own removal, which is not news to save
    function refuseMidpoint(shape, path, i) {
        if (pathSurgery) return;                       // removeAt, bouncing back at us
        pathSurgery = true;
        try { path.removeAt(i); } catch (_) {}
        pathSurgery = false;
        // Google is mid-drag on a handle it still believes in, and its next
        // move would land on whatever vertex now answers to that index —
        // silently dragging the wrong corner. Turning editing off and on makes
        // it rebuild its handles from the path as it actually is, which leaves
        // that drag holding nothing. Next tick, so it is not done underneath
        // the event it is still dispatching.
        setTimeout(() => {
            if (!editing || editing.parts[0] !== shape) return;
            try { shape.setOptions({ editable: false }); shape.setOptions({ editable: true }); } catch (_) {}
        }, 0);
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
            // Every one of these moves the outline, so every one of them owes
            // the measurement a recount. trackMeasure waits for the next
            // frame, which also means it reads the path AFTER refuseMidpoint
            // has taken the ghost's point back out.
            editing.listeners.push(path.addListener('set_at', () => { trackMeasure(o.id); scheduleSave(); }));
            editing.listeners.push(path.addListener('insert_at', (i) => { refuseMidpoint(first, path, i); trackMeasure(o.id); }));
            // A vertex can still leave by the front door — the Delete point
            // button, or Google's own right-click on a handle. Only our own
            // undoing of a midpoint is silent.
            editing.listeners.push(path.addListener('remove_at', () => { trackMeasure(o.id); if (!pathSurgery) scheduleSave(); }));
        }
        // Dragging the whole shape moves every corner of it, so the badge has
        // as far to travel as the field does.
        editing.listeners.push(first.addListener('drag', () => trackMeasure(o.id)));
        editing.listeners.push(first.addListener('dragend', () => { trackMeasure(o.id); scheduleSave(); }));
        showEditBar(o);
        // A held label grows a handle for its type size. Same selection, same
        // bar, same Done — it is one more thing this shape can be dragged by,
        // not a second way of picking something up.
        if (o.kind === 'text') showSizeHandle(o, parts);
    }
    function endEdit() {
        if (!editing) return;
        clearTimeout(saveTimer);
        editing.listeners.forEach((l) => l.remove());
        const first = editing.parts[0];
        if (first.setOptions) first.setOptions({ draggable: false, editable: false });
        else if (first.setDraggable) first.setDraggable(false);
        // A label is draggable whether or not it is selected, so putting the
        // selection down must not quietly take that away — and this runs on
        // every tool change, which is exactly when it would have. `tool` is
        // already the new one by the time setTool gets here.
        if (editing.o.kind === 'text' && first.setDraggable) first.setDraggable(textDraggable());
        editing = null;
        clearSelVertex();
        dropSizeHandle();
        const bar = document.getElementById('cmapEditBar');
        if (bar) bar.classList.add('hidden');
    }

    /* ---------- sizing a label by dragging it ----------
     *
     * A list of sizes would have been three lines of code, and wrong: nobody
     * knows whether 22px is the right size for THIS label over THIS field
     * until they see it there. So the size is pulled out of the label like a
     * corner out of a shape — the same gesture the rest of this map is made
     * of, and the only one that works with a thumb.
     *
     * It is a draggable marker rather than an HTML handle for the same reason
     * every vertex pin is: Google's markers already do touch, which is the
     * primary input here, and they already live in the pane that the pen's
     * pointer handlers know to keep their hands off.
     *
     * The distance from the words to the handle IS the size — measured in
     * screen pixels, so it reads the same at every zoom, which is also why the
     * handle has to be put back on its ring whenever the camera moves. */
    const HANDLE_R0 = 38, HANDLE_K = 1.5;
    let sizeHandle = null, sizeHandleFor = null, sizeLive = TXT_NEW, sizeDragging = false;
    const handleFace = (text, px) => ({ text, className: 'cmap-mbadge', color: '#fff', fontSize: px + 'px', fontWeight: '800' });
    /** Where the handle belongs on the ground for a given size. */
    function handleLatLng(anchor, px) {
        const pr = proj && proj.getProjection();
        const at = anchor && pr && pr.fromLatLngToContainerPixel(anchor);
        if (!at) return null;
        return pr.fromContainerPixelToLatLng(new (G().Point)(at.x + HANDLE_R0 + (px - TXT_MIN) * HANDLE_K, at.y));
    }
    function placeSizeHandle() {
        // Never while it is being pulled: the map settling under a drag would
        // otherwise snatch the handle back onto its ring out of the finger.
        if (!sizeHandle || !sizeHandleFor || sizeDragging) return;
        const ll = handleLatLng(sizeHandleFor.mark.getPosition(), sizeLive);
        if (ll) sizeHandle.setPosition(ll);
    }
    function dropSizeHandle() {
        if (sizeHandle) { sizeHandle.setMap(null); sizeHandle = null; }
        sizeHandleFor = null;
        sizeDragging = false;
    }
    function showSizeHandle(o, parts) {
        dropSizeHandle();
        const mark = parts && parts[0];
        // The projection is what turns "a thumb away" into a place on the
        // ground. Without it there is nowhere to put the handle at all, and a
        // label with no handle is still perfectly usable.
        if (!mark || !mark.getPosition || !proj || !proj.getProjection()) return;
        sizeLive = textStyle(o).px;
        const ll = handleLatLng(mark.getPosition(), sizeLive);
        if (!ll) return;
        sizeHandleFor = { o, mark };
        sizeHandle = new (G().Marker)({
            map, position: ll, draggable: true, crossOnDrag: false, zIndex: 70,
            title: 'Drag to size this label',
            // Bigger than it looks it needs to be: this is dragged one-handed,
            // outdoors, by somebody holding something else in the other hand.
            icon: { path: G().SymbolPath.CIRCLE, scale: 14, fillColor: '#111827',
                fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2.5 },
            label: handleFace('A', 13),
        });
        sizeHandle.addListener('dragstart', () => { sizeDragging = true; });
        sizeHandle.addListener('drag', (ev) => {
            if (!sizeHandleFor) return;
            const pr = proj.getProjection();
            const a = pr && pr.fromLatLngToContainerPixel(mark.getPosition());
            const b = pr && pr.fromLatLngToContainerPixel(ev.latLng);
            if (!a || !b) return;
            const px = clampTxt(TXT_MIN + (Math.hypot(b.x - a.x, b.y - a.y) - HANDLE_R0) / HANDLE_K);
            if (px === sizeLive) return;
            sizeLive = px;
            // The words change size under the finger, at the size they will
            // keep — which is the whole reason this is a drag and not a menu.
            mark.setLabel(textLabelOpts(sizeHandleFor.o, px));
            // And the handle says the number, so a size can be repeated on the
            // next label instead of guessed at twice.
            sizeHandle.setLabel(handleFace(String(px), 12));
        });
        sizeHandle.addListener('dragend', () => {
            sizeDragging = false;
            if (!sizeHandleFor) return;
            sizeHandle.setLabel(handleFace('A', 13));
            // Pulled at any angle, parked on the ring: the distance was the
            // question, the direction was never part of it.
            placeSizeHandle();
            commitTextSize(sizeHandleFor.o, sizeLive);
        });
        if (!editing) return;
        // The handle follows the words — dragged by hand, and pushed about by
        // every zoom, since its offset is only true in screen pixels.
        editing.listeners.push(mark.addListener('drag', placeSizeHandle));
        editing.listeners.push(map.addListener('idle', placeSizeHandle));
    }
    async function commitTextSize(o, px) {
        const cur = objIndex.get(o.id) || o;
        // A label from before lettering existed has no font, and a size stored
        // without one would be read back as a stroke width — so sizing an old
        // label is also the moment it gets the plain face. That is the only
        // thing that makes its width mean anything.
        const font = FONTS[cur.font] ? cur.font : 'sans';
        if (textStyle(cur).px === px && cur.font === font) return;
        try {
            const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: cur.id, width: px, font } });
            const fresh = res.data.object;
            // The room can clear the map or open a different one while this
            // write is on the wire, and dropAll has then emptied both indexes.
            // Writing the label back in at that point is worse than losing it:
            // objIndex would hold a shape `layers` knows nothing about, so
            // nothing could ever drop it, composeMapPng walks objIndex and
            // would paint the departed map's label into the arriving map's
            // autosaved picture, and the shelf would count a shape that is not
            // there. Same guard scheduleSave uses two functions up.
            if (!objIndex.has(cur.id)) return;
            pushHist({ type: 'label', id: cur.id,
                before: { label: cur.label, font: cur.font, width: cur.width },
                after: { label: fresh.label, font: fresh.font, width: fresh.width } });
            objIndex.set(fresh.id, fresh);
            if (sizeHandleFor && sizeHandleFor.o.id === fresh.id) sizeHandleFor.o = fresh;
            if (editing && editing.o.id === fresh.id) editing.o = fresh;
            // No history step: undo is a stack of geometry, and a size is not
            // geometry. The saved map is still owed it.
            markMapDirty();
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }

    /* ---------- the label editor ----------
     *
     * One sheet for both jobs, because they are one job: a label is words and
     * a face, whether it exists yet or not. A NEW one is nothing but a
     * remembered patch of ground until the button is pressed — so cancelling
     * cannot leave anything behind, there being nothing to leave. */
    let textDraft = null;      // { at: [lat,lng] } for a new label | { id } for one being changed
    let textFont = 'sans', textSize = TXT_NEW;
    function paintFontRow() {
        document.querySelectorAll('#cmapTextSheet .cmap-fontopt').forEach((b) =>
            b.classList.toggle('is-active', b.dataset.mfont === textFont));
    }
    function sayTextLeft() {
        const ta = document.getElementById('cmapTextInput');
        const out = document.getElementById('cmapTextLeft');
        if (!ta || !out) return;
        const left = 500 - ta.value.length;
        // Silent until it starts to matter — a counter on an empty box is
        // just a number to ignore.
        out.textContent = left > 80 ? '' : left + ' characters left';
    }
    function openTextSheet(o, at) {
        textDraft = o ? { id: o.id } : { at };
        textFont = (o && FONTS[o.font]) ? o.font : 'sans';
        // Whatever it looks like now is where the editor starts from, old row
        // or new — textStyle already answers that question for both.
        textSize = o ? textStyle(o).px : TXT_NEW;
        const ta = document.getElementById('cmapTextInput');
        if (ta) ta.value = o ? String(o.label || '') : '';
        document.getElementById('cmapTextTitleH').textContent = o ? 'Edit this label' : 'Add a label';
        document.getElementById('cmapTextGoTxt').textContent = o ? 'Save label' : 'Place label';
        paintFontRow();
        sayTextLeft();
        window.openSheet?.('cmapTextSheet');
        // Desktop lands in the box; smFocus leaves a phone alone, where the
        // keyboard would cover the lettering row before it was looked at.
        window.smFocus?.('cmapTextInput', { delay: 320 });
    }
    async function commitTextSheet() {
        const draft = textDraft;
        if (!draft) return;
        const ta = document.getElementById('cmapTextInput');
        // Line breaks are the point of a textarea and survive all the way to
        // the ground (see .cmap-txt-g); only the ends are tidied, and the
        // column's 500 is the column's 500.
        const words = String(ta ? ta.value : '').replace(/\r\n/g, '\n').trim().slice(0, 500);
        if (!words) { if (window.toast) toast('A label needs some words.', 'error'); return; }
        const btn = document.getElementById('cmapTextGo');
        const cap = document.getElementById('cmapTextGoTxt');
        const was = cap.textContent;
        btn.disabled = true; cap.textContent = 'Saving…';
        try {
            if (draft.id) {
                const res = await api(`${URLS.update}?scheduleId=${SID}`, {
                    method: 'POST', body: { id: draft.id, label: words, font: textFont, width: textSize },
                });
                // The canvas we set out to edit may not be the canvas any more:
                // a clear or a saved map opening during this write empties both
                // indexes, and re-rendering here would plant this label in
                // whatever arrived. Losing an edit to a map that is gone is the
                // right outcome; putting it on somebody else's map is not.
                if (!objIndex.has(draft.id)) { window.closeSheet?.('cmapTextSheet'); return; }
                // Held before the swap and held after it: the same trick the
                // Delete-point button uses, so the handle comes back too.
                pushHist({ type: 'label', id: draft.id,
                    before: { label: draft.label, font: draft.font, width: draft.width },
                    after: { label: words, font: textFont, width: textSize } });
                if (editing && editing.o.id === draft.id) pendingEdit = draft.id;
                dropObject(draft.id);
                renderObject(res.data.object);
                markMapDirty();
                window.closeSheet?.('cmapTextSheet');
            } else {
                const obj = await saveObject('text', [draft.at], words, { width: textSize, font: textFont });
                // saveObject says its own piece when a write fails. The sheet
                // stays open over it rather than closing on a label that was
                // never placed and taking the typing with it.
                if (!obj) { btn.disabled = false; cap.textContent = was; return; }
                window.closeSheet?.('cmapTextSheet');
                const parts = layers.get(obj.id);
                if (parts && parts.length) {
                    // Picked up the instant it lands, which is the only way
                    // the size handle is ever found: it is standing beside the
                    // words before anyone goes looking for it.
                    beginEdit(obj, parts);
                    if (window.toast) toast('Label placed — drag the A beside it to size it.');
                }
            }
            textDraft = null;
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
        btn.disabled = false; cap.textContent = was;
    }

    /* ---------- press and hold an edge to drop a point into it ----------
     * Google hands this out only through an editable polygon's midpoint
     * handles, which is two problems: a box is never editable (it is stored
     * as two opposite corners, so it has no path to hand over), and those
     * handles are smaller than a fingertip on the phones this is used on.
     * So the gesture is ours — hold still on an edge and a point appears
     * there, already grabbable.
     *
     * The hold has to be the ONLY thing happening: a pan, a pinch, a dragged
     * shape or a pin's own long-press all cancel it before it fires. */
    // Three seconds, and this gesture's alone: nothing else times off it (the
    // pin's "draw on from here" keeps its own half-second). Adding a point is
    // now the only way a shape grows one, so the press has to be long enough
    // that nobody arrives at it by resting a thumb on a fence line.
    const EDGE_HOLD_MS = 3000, HOLD_SLOP = 8, EDGE_HIT = 18;
    // A point is only worth dropping where a point can then be grabbed, which
    // is exactly the kinds vertexPins draws handles for.
    const INSERTABLE = ['line', 'path', 'rect', 'area'];
    const ptOf = (el, e) => {
        const r = el.getBoundingClientRect();
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    };
    /** A shape as the corners you can see — a box is two corners in the row. */
    function ringOf(o) {
        if (o.kind !== 'rect' || (o.points || []).length < 2) return o.points || [];
        const b = new (G().LatLngBounds)(LL(o.points[0]), LL(o.points[1]));
        const sw = b.getSouthWest(), ne = b.getNorthEast();
        return [[sw.lat(), sw.lng()], [sw.lat(), ne.lng()], [ne.lat(), ne.lng()], [ne.lat(), sw.lng()]];
    }
    /* The edge nearest a press, measured in screen pixels: "nearest" has to
       mean what it looks like from where the finger is, not what it is in
       metres — the same shape read at two zooms would otherwise answer
       differently to the same press. */
    function nearestEdge(at) {
        const pr = proj.getProjection();
        if (!pr) return null;
        let best = null;
        objIndex.forEach((o) => {
            if (!INSERTABLE.includes(o.kind)) return;
            const ring = ringOf(o);
            if (ring.length < 2) return;
            const closed = (o.kind === 'rect' || o.kind === 'area');
            const px = ring.map((p) => pr.fromLatLngToContainerPixel(LL(p)));
            if (px.some((q) => !q)) return;
            const n = closed ? px.length : px.length - 1;
            for (let i = 0; i < n; i++) {
                const a = px[i], b = px[(i + 1) % px.length];
                const vx = b.x - a.x, vy = b.y - a.y, len2 = vx * vx + vy * vy;
                if (len2 < 576) continue;      // under 24px on screen: no room for another point
                // Clamped well clear of both ends, so a press near a corner is
                // measured against the middle of the edge and misses the
                // threshold — that press belongs to the corner's pin.
                const t = Math.min(.85, Math.max(.15, ((at.x - a.x) * vx + (at.y - a.y) * vy) / len2));
                const hx = a.x + vx * t, hy = a.y + vy * t;
                const d = Math.hypot(at.x - hx, at.y - hy);
                if (d <= EDGE_HIT && (!best || d < best.d)) best = { o, i, ring, d, at: new (G().Point)(hx, hy) };
            }
        });
        return best;
    }
    /** Put the new corner in and persist it down the same road every reshape takes. */
    async function insertOnEdge(hit) {
        const pr = proj.getProjection();
        const ll = pr && pr.fromContainerPixelToLatLng(hit.at);
        if (!ll) return;
        const cur = objIndex.get(hit.o.id) || hit.o;
        const ring = hit.ring.slice();
        ring.splice(hit.i + 1, 0, [ll.lat(), ll.lng()]);
        try {
            if (cur.kind === 'rect') {
                // A box has no fifth corner — the row holds two. Same
                // conversion a dragged corner does: it becomes an area, with
                // the new point already in it.
                const res = await api(`${URLS.push}?scheduleId=${SID}`, {
                    method: 'POST', body: { kind: 'area', points: ring, color: cur.color, width: cur.width, label: cur.label },
                });
                if (tool === 'edit') {
                    pendingEdit = res.data.object.id;
                    pendingPoint = { id: res.data.object.id, index: hit.i + 1 };
                }
                carryMeasure(cur.id, res.data.object.id);
                renderObject(res.data.object);
                // One held edge, one undo step — see applyStep's 'swap'.
                pushHist({ type: 'swap', added: res.data.object, removed: cur });
                await api(`${URLS.remove}?scheduleId=${SID}`, { method: 'DELETE', body: { id: cur.id } }).catch(() => {});
                dropObject(cur.id, true);
                if (window.toast) toast('Point added — the box is now an area you can reshape.');
                return;
            }
            const res = await api(`${URLS.update}?scheduleId=${SID}`, { method: 'POST', body: { id: cur.id, points: ring } });
            pushHist({ type: 'update', id: cur.id, before: cur.points, after: res.data.object.points });
            if (tool === 'edit') {
                pendingEdit = cur.id;                      // stay holding the shape
                pendingPoint = { id: cur.id, index: hit.i + 1 };
            }
            dropObject(cur.id);
            renderObject(res.data.object);
            if (window.toast) toast('Point added — drag it to reshape.');
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
    }
    /* Where the point would land, drawn on the ground it would land on. The
       ring lives in the stage next to the map rather than inside Google's
       container, so it is measured across from one to the other rather than
       assumed to share a corner. */
    function showHold(el, at) {
        const ring = document.getElementById('cmapHold');
        if (!ring) return;
        // Shown before it is measured: a hidden element has no offsetParent to
        // measure across to, and would land in the stage's top-left corner.
        ring.hidden = false;
        const par = ring.offsetParent || el;
        const a = el.getBoundingClientRect(), b = par.getBoundingClientRect();
        ring.style.left = (at.x + a.left - b.left) + 'px';
        ring.style.top = (at.y + a.top - b.top) + 'px';
        ring.style.setProperty('--hold-ms', EDGE_HOLD_MS + 'ms');
        // A class the element already carries is not a new animation — the
        // second hold in a row would show a ring that is already full.
        ring.classList.remove('is-on');
        void ring.offsetWidth;
        ring.classList.add('is-on');
    }
    function hideHold() {
        const ring = document.getElementById('cmapHold');
        if (!ring) return;
        // Gone, not faded: the promise the ring makes is that letting go
        // cancels, and a ring still sitting there argues with that.
        ring.classList.remove('is-on');
        ring.hidden = true;
    }
    function bindEdgeInsert(el) {
        let timer = null, from = null, pid = null;
        const stop = () => { clearTimeout(timer); timer = null; from = null; pid = null; hideHold(); };
        el.addEventListener('pointerdown', (e) => {
            // A second finger means a pinch, so the first was never a hold.
            if (timer) { stop(); return; }
            // Only the two tools that are not already spending the finger:
            // the drawing tools own it, and erase and text mean something
            // else entirely by a press.
            if (tool !== 'pan' && tool !== 'edit') return;
            if (extending || !proj.getProjection()) return;
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            // A press that starts on a pin belongs to that pin — it has its
            // own long-press (draw on from here) and its own drag.
            const panes = proj.getPanes && proj.getPanes();
            if (panes && panes.overlayMouseTarget && panes.overlayMouseTarget.contains(e.target)) return;
            const at0 = ptOf(el, e);
            // Nothing to grow a point out of here, so nothing to promise: no
            // ring, no timer, and the press stays the map's own.
            const seen = nearestEdge(at0);
            if (!seen) return;
            from = at0; pid = e.pointerId;
            const gen = canvasGen;
            showHold(el, seen.at);
            timer = setTimeout(() => {
                const at = from;
                stop();
                // Three seconds is plenty of time for the room to clear the map
                // or open another one. The shapes under this finger are not the
                // ones it came down on, and dropping a point into a stranger's
                // fence line is not what the hold asked for.
                if (canvasGen !== gen) return;
                // Measured again rather than trusted from the press: the map
                // is pinned for the duration, but a teammate's shape is not.
                const hit = nearestEdge(at);
                if (!hit) return;
                // A gesture that ends with no sign of itself reads as broken.
                try { if (navigator.vibrate) navigator.vibrate(12); } catch (_) {}
                insertOnEdge(hit);
            }, EDGE_HOLD_MS);
        });
        el.addEventListener('pointermove', (e) => {
            if (!timer || e.pointerId !== pid) return;
            // Moved: the map is being panned, or a picked-up shape dragged.
            // Either of those wins over a point nobody has asked for yet.
            const p = ptOf(el, e);
            if (Math.hypot(p.x - from.x, p.y - from.y) > HOLD_SLOP) stop();
        });
        ['pointerup', 'pointercancel', 'pointerleave'].forEach((n) => el.addEventListener(n, stop));
        // Ground moving under a still finger invalidates the pixel we measured
        // the press against — a keyboard zoom or a programmatic pan counts.
        map.addListener('dragstart', stop);
        map.addListener('zoom_changed', stop);
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
                    + '<span class="cmap-dot-name">' + esc((this.p.name || '') + (this.p.userId === ME ? ' (you)' : '')) + '</span>';
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
    /* ---------- being taken to yourself ----------------------------------
     *
     * map.panTo() animates a short hop and teleports a long one, which is the
     * wrong way round: the long trip is exactly the one where a person needs to
     * see which way the ground moved. So the centre is eased by hand, on the
     * house curve, and the zoom rides the same clock so arriving is one motion
     * rather than a slide followed by a jerk.
     *
     * Interrupted by touching the map, and by pressing the button again — a
     * flight you are trying to escape from is a hijack, not an animation. */
    // Longer than a single motion would need, because it is two overlapping
    // ones — the travel and the drop-in — sharing one clock.
    const FLY_MS = 820;
    let flying = null;
    function cancelFly() {
        if (!flying) return;
        cancelAnimationFrame(flying.raf);
        if (flying.escape) G().event.removeListener(flying.escape);
        flying = null;
    }
    function flyTo(lat, lng, wantZoom) {
        if (!map) return;
        // NaN is a number, which is why `typeof wantZoom === 'number'` was not
        // the check it looked like, and why an undefined destination flew
        // silently: every arithmetic below produced NaN, the map declined it
        // without complaint, and the caller went on to announce it had arrived.
        // Refusing here would have named the bug on the first press.
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        cancelFly();
        const from = map.getCenter();
        if (!from) return;
        const fromLat = from.lat(), fromLng = from.lng();
        const fromZoom = map.getZoom() || 17;
        const toZoom = Number.isFinite(wantZoom) ? wantZoom : fromZoom;
        const still = Math.abs(fromLat - lat) < 1e-7 && Math.abs(fromLng - lng) < 1e-7
            && Math.abs(fromZoom - toZoom) < 0.01;
        if (still) return;

        // Somebody who has asked not to be moved through space still wants to
        // end up in the right place.
        const calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (calm) {
            map.setCenter({ lat, lng });
            if (Math.abs(toZoom - fromZoom) > 0.01) map.setZoom(toZoom);
            return;
        }

        // cubic-bezier(.22,1,.36,1) is the house curve; this is its shape —
        // nearly all of the distance early, settling rather than braking.
        const ease = (t) => 1 - Math.pow(1 - t, 4);
        const zooms = Math.abs(toZoom - fromZoom) > 0.01;

        /* One camera write per frame, not two.
         *
         * setZoom() does not set the zoom, it starts Google's own zoom
         * animation — which drives the camera from wherever it thinks it began.
         * Called sixty times a second beside setCenter, it restarts that
         * animation every frame and the centre we set on the line before is
         * simply overruled. The map zoomed and never travelled, which is
         * exactly what it looked like. moveCamera() writes the whole camera at
         * once and animates nothing of its own, which is what a frame loop
         * wants. Where it is missing, the pan runs alone and the zoom is left
         * to the end rather than fighting it. */
        const canCam = typeof map.moveCamera === 'function';
        const cam = (c, z) => {
            if (canCam) { map.moveCamera({ center: c, zoom: z }); return; }
            map.setCenter(c);
        };

        /* Travel first, arrive second. Crossing a farm at zoom 19 is a blur
         * nobody can read, so the pan owns the first stretch of the clock and
         * the zoom drops in over the last, with a little overlap so it reads as
         * one movement rather than two. */
        const PAN_END = 0.72, ZOOM_FROM = 0.42;
        const t0 = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - t0) / FLY_MS);
            const kPan = ease(Math.min(1, t / PAN_END));
            const kZoom = ease(Math.max(0, (t - ZOOM_FROM) / (1 - ZOOM_FROM)));
            cam(
                { lat: fromLat + (lat - fromLat) * kPan, lng: fromLng + (lng - fromLng) * kPan },
                zooms ? fromZoom + (toZoom - fromZoom) * kZoom : fromZoom
            );
            if (t < 1) { flying.raf = requestAnimationFrame(step); return; }
            // Land on whole numbers: a raster basemap left at zoom 16.98 keeps
            // rendering scaled tiles, which reads as a map that is slightly out
            // of focus and never comes back. This is also where the zoom
            // happens at all on a map with no moveCamera.
            map.setCenter({ lat, lng });
            if (zooms) map.setZoom(Math.round(toZoom));
            cancelFly();

            /* Check that it actually arrived.
             *
             * The camera is not ours alone — a fitBounds finishing late, a
             * resize restoring the view it remembered, a stored view being
             * reapplied — and any of those landing just after this one wins,
             * silently, leaving a map that plainly did not go where it was
             * asked. Cheaper to look than to reason about who else was writing:
             * if the centre is more than a few metres off once the map settles,
             * say so once more, plainly. Once only, so two writers cannot spend
             * the afternoon correcting each other. */
            G().event.addListenerOnce(map, 'idle', () => {
                const at = map.getCenter();
                if (!at) return;
                const off = Math.abs(at.lat() - lat) + Math.abs(at.lng() - lng);
                if (off > 1e-5) map.setCenter({ lat, lng });
            });
        };
        // Armed before the first frame, so a drag that starts inside the very
        // first tick still calls it off. A pinch reports as a drag too.
        flying = {
            raf: requestAnimationFrame(step),
            escape: G().event.addListenerOnce(map, 'dragstart', () => cancelFly()),
        };
    }

    /* The last fix we were given, whoever asked for it. Pressing the button a
     * second time should not make somebody stand still waiting for a satellite
     * to tell them what they were told ten seconds ago. */
    let myFix = null;
    // Long enough that a double-tap is instant, short enough that it cannot
    // answer for somewhere you have walked away from. Twenty seconds is fifty
    // paces, and the whole point of the button is where you are standing NOW.
    const FIX_FRESH = 10000;

    /* How close to go in, decided by how well the browser actually knows.
     *
     * This used to be a flat zoom 17 whatever came back. A phone under open sky
     * reports five metres and 17 is right; a laptop with no GPS radio reports
     * a wifi or IP guess measured in kilometres, and diving to 17 on that lands
     * you in a hundred-metre square that is nowhere near where you are standing
     * — while the only thing you can SEE happening is a very large zoom. Which
     * is a fair description of the complaint.
     *
     * So the accuracy circle is made to fill about half the smaller side of the
     * map instead: a good fix goes in close, a vague one stops where the truth
     * stops. metres-per-pixel at zoom z is 156543.03392·cos(lat)/2^z, and this
     * is that, solved for z. */
    function zoomForAccuracy(lat, acc) {
        const el = document.getElementById('cmapMap');
        const side = Math.max(160, Math.min(el?.clientWidth || 640, el?.clientHeight || 480));
        const metres = Math.max(8, Number(acc) || 0);      // 8 m is as sure as anything gets
        const perPixel = (2 * metres) / (side * 0.5);
        const z = Math.log2((156543.03392 * Math.cos(lat * Math.PI / 180)) / perPixel);
        if (!Number.isFinite(z)) return 17;   // a sane close-in default, never NaN
        return Math.max(12, Math.min(19, Math.round(z)));
    }

    /* No accuracy ring is drawn. It was put here to make the fix's margin of
     * error visible while that was the thing under suspicion, and it read as
     * another shape on a map whose whole business is shapes you drew on
     * purpose. The accuracy still decides how far in to go, and still gets said
     * in words below, which is the part that was worth keeping. */

    /* Say how sure it is, because "it moved somewhere" and "it moved somewhere
     * right" look identical, and the difference is usually the device rather
     * than the map. A reading in kilometres is the answer to "why is it not
     * where I am standing". */
    function sayWhere(acc) {
        if (!window.toast) return;
        const m = Math.round(Number(acc) || 0);
        if (!m) { toast('Centred on you.'); return; }
        if (m > 750) {
            toast(`Centred on you, but only to about ${(m / 1000).toFixed(1)} km — this device is guessing from the network, not GPS.`, 'error');
            return;
        }
        toast(`Centred on you — accurate to about ${m} m.`);
    }

    function findMe(btn) {
        // The button is drawn with the toolbar, which is drawn before the map
        // has finished booting — and everything below wants a map to move.
        if (!map) {
            if (window.toast) toast('The map is still loading.', 'error');
            return;
        }
        if (!navigator.geolocation) {
            if (window.toast) toast('This device has no GPS.', 'error');
            return;
        }
        /* One shape, {lat, lng, acc}, and it is the cache's shape.
         *
         * This took the browser's shape — latitude/longitude/accuracy — and was
         * handed the cache, which speaks lat/lng/acc. Every field came out
         * undefined. Nothing threw: the centre became NaN and the map ignored
         * it, the zoom became NaN and the map ignored that too, and the toast
         * rounded a missing accuracy to 0 and cheerfully said "Centred on you".
         * Then it wrote the undefined fix back over the good one with a fresh
         * timestamp, so every press inside the next twenty seconds took the
         * same poisoned path and re-poisoned it on the way through. The first
         * press of a session worked; the second onwards said it had worked and
         * did nothing, which is what was being reported. */
        const land = (fix) => {
            const { lat, lng, acc } = fix || {};
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                // Loud, not silent. A fix with no numbers in it is a bug, and
                // the last one hid behind a cheerful message for three rounds.
                myFix = null;
                if (window.toast) toast('That position came back empty — try once more.', 'error');
                return;
            }
            // Draw the dot as well as move: a map that jumps somewhere with
            // nothing on it has not actually answered "where am I".
            // Local only — sharing is the button next door, and stays its own
            // decision.
            renderLoc({ userId: ME, name: 'Me', lat, lng, acc });
            centeredOnMe = true;
            dropVeil();
            flyTo(lat, lng, zoomForAccuracy(lat, acc));
            sayWhere(acc);
        };

        // Stamped when the reading was TAKEN, never when it is re-used, so the
        // cache cannot renew itself. Reusing kept pushing the expiry forward,
        // which is how a fix from where you were standing several minutes ago
        // stayed "fresh" for as long as you kept pressing the button.
        if (myFix && Date.now() - myFix.at < FIX_FRESH) { land(myFix); return; }
        myFix = null;

        btn.classList.add('is-busy');
        btn.disabled = true;
        const done = () => { btn.classList.remove('is-busy'); btn.disabled = false; };
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                done();
                const c = pos && pos.coords;
                myFix = c ? { lat: c.latitude, lng: c.longitude, acc: c.accuracy, at: Date.now() } : null;
                land(myFix);
            },
            (err) => {
                done();
                if (!window.toast) return;
                // Three different problems that all used to say the same thing.
                if (err && err.code === 1) toast('This app is not allowed to use your location — turn it on for this site in your browser settings.', 'error');
                else if (err && err.code === 3) toast('Still looking for a signal. Under open sky it comes faster.', 'error');
                else toast('Could not work out where you are.', 'error');
            },
            // maximumAge 0: ask for a real reading. The twenty-second cache
            // above already covers "do not pester the satellites"; letting the
            // browser answer from ITS cache as well is how a stale, coarse fix
            // from whenever the tab last felt like it gets treated as now.
            { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 }
        );
    }

    function toggleGps(btn) {
        if (gpsWatch !== null) {
            navigator.geolocation.clearWatch(gpsWatch); gpsWatch = null;
            btn.classList.remove('is-active');
            return;
        }
        if (!navigator.geolocation) { if (window.toast) toast('No GPS on this device.', 'error'); return; }
        btn.classList.add('is-active');
        /* Pressing the button is a request to be taken there.
         *
         * The only pan used to be the passive one below, and it required
         * BOTH that nothing had centred the map yet AND that the schedule
         * had no shapes on it. A farm with a single lot drawn failed the
         * second test, and any return visit failed the first — so the dot
         * appeared and the map stayed where it was. That reads as "GPS finds
         * me but the map does not move", which is what it was. */
        let goToMe = true;
        gpsWatch = navigator.geolocation.watchPosition((pos) => {
            const { latitude: lat, longitude: lng, accuracy: acc } = pos.coords;
            // Feeds the same cache the Centre-on-me button reads, so while
            // sharing is on that button answers off this stream instead of
            // asking the satellites all over again.
            myFix = { lat, lng, acc, at: Date.now() };
            renderLoc({ userId: ME, name: 'Me', lat, lng, acc });
            if (goToMe) {
                goToMe = false;
                centeredOnMe = true;
                map.panTo({ lat, lng });
                if (map.getZoom() < 17) map.setZoom(17);
                dropVeil();
            }
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
        /* A written label, in its own face at its own size, on the same white
           plate the screen draws it on — a picture that letters the map
           differently is not a picture of the map. The canvas is drawn at
           SCALE, which is exactly the ratio the measurement labels above
           already use: 11 on screen, 22 here. Lines are kept apart rather
           than run together, since the editor takes several of them. */
        const plate = (p, o) => {
            const st = textStyle(o);
            const lines = String(o.label || '').split('\n');
            const fs = st.px * SCALE;
            // No family for the old rows, deliberately: they were drawn in
            // whatever Google letters a marker with, and still are.
            ctx.font = '800 ' + fs + 'px ' + (st.family || 'Roboto, Arial, sans-serif');
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const lh = fs * 1.2;
            const w = Math.max(...lines.map((t) => ctx.measureText(t).width)) + fs;
            const h = lh * lines.length + fs * 0.4;
            const [x, y] = px(p);
            const x0 = x - w / 2, y0 = y - h / 2;
            ctx.beginPath();
            ctx.roundRect ? ctx.roundRect(x0, y0, w, h, fs * 0.35) : ctx.rect(x0, y0, w, h);
            ctx.fillStyle = '#fff';
            ctx.fill();
            ctx.lineWidth = 1.5 * SCALE;
            ctx.strokeStyle = '#111827';
            ctx.stroke();
            ctx.fillStyle = '#111827';
            lines.forEach((t, i) => ctx.fillText(t, x, y0 + fs * 0.2 + lh * (i + 0.5)));
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

            if (o.kind === 'text') { plate(pts[0], o); return; }

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
            // The printed map has the same middle the live one does — a
            // number outside the field it belongs to reads no better on paper.
            if (closed && pts.length > 1) label(anchorOf(o.kind, pts), fmtA(areaOf(pts)));
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
    /* ---------- an opened map keeps itself saved ----------
     * A saved map is a file, and a file that only changes when you remember
     * to press Save is a file that loses an afternoon. Every change made here
     * writes back into the map it was opened from: quiet for two seconds
     * after the last one, so a flurry of nudges is a single write, and never
     * more often than once every fifteen, because each write composes a fresh
     * picture of the whole map. Only the person editing writes — the shapes
     * themselves were already live for everyone. */
    const AUTO_QUIET = 2000, AUTO_EVERY = 15000;
    let autoTimer = null, autoLast = 0, autoBusy = false, autoAgain = false, autoSayTimer = null;
    // Bumped whenever what a write was composed against stops being true. A
    // write that is already fetching its imagery cannot be called back, only
    // told not to land — this is what it checks before it posts.
    let autoEpoch = 0;
    // Whether the file is owed anything. `autoTimer` cannot answer that — it
    // keeps a spent handle after each one fires — and the flush below has to
    // know whether it is rescuing an afternoon or paying for a picture nobody
    // asked for.
    let autoDirty = false;
    // The write on the wire, if any. It cannot be recalled, so anything that
    // is about to change the shapes waits for it rather than racing it.
    let autoInFlight = null;
    function sayAutosave(state) {
        const el = document.getElementById('cmapSaved');
        if (!el) return;
        const txt = document.getElementById('cmapSavedTxt');
        clearTimeout(autoSayTimer);
        el.classList.toggle('is-working', state === 'saving');
        el.classList.toggle('is-failed', state === 'failed');
        if (txt) txt.textContent = state === 'saving' ? 'Saving…' : (state === 'failed' ? 'Not saved' : 'Saved');
        el.classList.add('is-on');
        // "Saving…" stays until it resolves; the other two are a glance, not
        // a banner sitting on the ground you are trying to read.
        if (state !== 'saving') autoSayTimer = setTimeout(() => el.classList.remove('is-on'), state === 'failed' ? 4500 : 1800);
    }
    function markMapDirty() {
        if (!LOADED_SAVE) return;         // a scratch canvas has no file to write into
        autoDirty = true;
        clearTimeout(autoTimer);
        autoTimer = setTimeout(runAutosave, Math.max(AUTO_QUIET, AUTO_EVERY - (Date.now() - autoLast)));
    }
    /**
     * Drop a queued write AND the edits it was carrying.
     *
     * For the deliberate wipes only — clearing the map, or a blank canvas
     * started here or in the room — where there is no longer a file those
     * edits belong in and nothing is lost by letting them go. Anything that
     * merely swaps which map is on screen wants flushAutosave() instead: the
     * write can be up to fifteen seconds old, which is an afternoon's last
     * few shapes.
     */
    function cancelAutosave() {
        clearTimeout(autoTimer);
        autoTimer = null;
        autoAgain = false;
        autoDirty = false;
        // A write already halfway through composing its picture is every bit
        // as stale as the queued one — it just has to be turned away at the
        // door instead of stopped at the gate.
        autoEpoch++;
    }
    /** The write itself. Identical whether the timer asks for it or a flush does. */
    function postMapWrite(target, image) {
        const c = map.getCenter();
        return api(`${URLS.save}?scheduleId=${SID}`, { method: 'POST', body: {
            mode: 'map', quiet: 1, saveId: target.id, title: target.title, image,
            lat: c ? c.lat() : null, lng: c ? c.lng() : null,
            zoom: Math.round(map.getZoom() || 15), maptype: satOn ? 'hybrid' : 'roadmap',
        } });
    }
    /**
     * Give the map you are leaving the edits you made to it, before it stops
     * being the map on screen.
     *
     * Must be AWAITED BY THE CALLER BEFORE it replaces the shapes. The server
     * takes no shapes from the body — it reads the live ones — so this only
     * writes what it says while the shapes it was composed against are still
     * the ones there. Flushed afterwards it would pour the arriving map into
     * the departing map's file, which is precisely the overwrite the plain
     * cancel was added to stop.
     */
    async function flushAutosave() {
        // A write already posted cannot be recalled, so wait for it to land
        // rather than let the caller's own POST overtake and undo it.
        if (autoInFlight) { try { await autoInFlight; } catch (_) { /* it said so itself */ } }
        const owed = autoDirty;
        const target = LOADED_SAVE;
        // Whatever this writes now is everything the queued one and the one
        // still composing were between them going to write.
        cancelAutosave();
        const epoch = autoEpoch;
        if (!owed || !target || !objIndex.size) return;
        sayAutosave('saving');
        let image = null;
        try { image = await composeMapPng(); } catch (_) { image = null; }
        // The caller is waiting on us, so it cannot have moved the map — but
        // the room can, and a clear or a load arriving mid-compose leaves the
        // same nothing-is-right position every other write here checks for.
        if (autoEpoch !== epoch) { sayAutosave('failed'); return; }
        try {
            autoInFlight = postMapWrite(target, image);
            await autoInFlight;
            sayAutosave('saved');
        } catch (e) {
            // No retry: the map is about to be replaced, so there is nothing
            // left to retry into. Said out loud, because leaving quietly is
            // how this looked exactly like saving.
            sayAutosave('failed');
            if (window.toast) toast('Could not save “' + target.title + '” before opening the other map.', 'error');
        } finally { autoInFlight = null; }
        autoLast = Date.now();
    }
    async function runAutosave() {
        if (!LOADED_SAVE) return;
        // An emptied canvas is far more often "about to draw" than "meant to
        // empty the file", and the server refuses a save with no shapes
        // anyway. The next shape drawn saves the lot.
        if (!objIndex.size) return;
        if (autoBusy) { autoAgain = true; return; }
        autoBusy = true;
        sayAutosave('saving');
        const target = LOADED_SAVE;
        const epoch = autoEpoch;
        // The picture is what the notebook shows; composing it here is what
        // makes it carry the measurements. A failure only costs the picture.
        let image = null;
        try { image = await composeMapPng(); } catch (_) { image = null; }
        // Composing fetches the imagery, which takes as long as it takes — and
        // in that gap the room can clear this map, open a different one, or the
        // user can press Save themselves. The shapes on screen now belong to
        // whatever arrived; writing them into the file we set out to save would
        // put one map inside another, or an older picture over a deliberate one.
        if (autoEpoch !== epoch || !LOADED_SAVE || LOADED_SAVE.id !== target.id) {
            autoBusy = false;
            // A change made to whatever is open now still deserves its write.
            if (autoAgain) { autoAgain = false; markMapDirty(); }
            return;
        }
        // Settled here rather than at the top: everything drawn up to this
        // line goes in the write below, and anything drawn after it arms a
        // fresh timer of its own.
        autoDirty = false;
        try {
            autoInFlight = postMapWrite(target, image);
            await autoInFlight;
            sayAutosave('saved');
        } catch (e) {
            // No retry of its own: the next edit asks again. A server saying
            // no every two seconds would say it a thousand times an hour. But
            // the file is still owed this, so a flush on the way out tries it
            // one last time.
            autoDirty = true;
            sayAutosave('failed');
        } finally { autoInFlight = null; }
        autoLast = Date.now();
        autoBusy = false;
        if (autoAgain) { autoAgain = false; markMapDirty(); }
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
        // Before composing, not after: an autosave that is queued or already
        // holding a picture of a moment ago must not land behind the file this
        // button is about to write on purpose. Everything it was going to save
        // is in this save too — the server reads the shapes live.
        //
        // Only when this button writes a map file at all. "Save as image note"
        // files a picture and nothing else: no ScheduleMapSave, no saveId, and
        // the map you are standing on is not touched. Dropping the queued
        // write there threw away its last few minutes and put nothing in their
        // place — exporting a picture is not a reason to lose the map.
        if (saveMode === 'map') cancelAutosave();
        // Which file "Save over" means was settled when the button was drawn.
        const target = replace ? LOADED_SAVE : null;
        // And what the room was looking at when it was pressed. Read after the
        // cancel above, which bumps the epoch itself.
        const epoch = autoEpoch;
        // Compose the picture here so it carries the points and measurements;
        // if that fails the server still draws a plain one from Static Maps.
        let image = null;
        try { image = await composeMapPng(); } catch (e) { image = null; }
        // Composing takes a second, and in it the room can clear the map or
        // open a different one. By then no answer is right, and this is as
        // true of a new map as of an overwrite: the server reads the shapes
        // live and takes none of them from us, so "Save as a new map" — the
        // default, and the only offer on a scratch canvas — would mint a file
        // out of somebody else's shapes under this title, while "Save over"
        // would pour them into the file named on the button. Write nothing.
        if (autoEpoch !== epoch || (replace && (!target || !LOADED_SAVE || LOADED_SAVE.id !== target.id))) {
            btn.disabled = false;
            label.textContent = was;
            if (window.toast) toast('The map on screen changed while that was saving — nothing was written. Try again now that you can see what you are saving.', 'error');
            return;
        }
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
            // The file is current as of now, so the autosave's clock starts
            // again from here rather than firing straight after this one. A
            // timer armed WHILE this was composing belongs to an edit made
            // since, and is deliberately left to fire: cancelling it here is
            // how an afternoon's last nudge goes missing.
            autoLast = Date.now();
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
    async function loadSavedMap(sv) {
        // No confirmation: tapping a map IS the answer. Opening a saved map
        // is opening a file, and a question in front of every open taught
        // people to stop reading it. The canvas it replaces can always be
        // re-loaded from its own save.
        //
        // The map being left gets its edits first. Before the load, not after:
        // loading wipes and re-inserts every shape server-side, and the save
        // endpoint reads the shapes live — a write sent afterwards would file
        // the arriving map under the departing one's name. Up to fifteen
        // seconds of work can be sitting in that queued write, and dropping it
        // is how the shapes you drew never reached the map you drew them on.
        await flushAutosave();
        try {
            await api(`${URLS.load}?scheduleId=${SID}`, { method: 'POST', body: { id: sv.id } });
            setLoadedSave(sv);
            window.closeSheet?.('cmapSavesSheet');
            endEdit(); dropAll();
            histUndo.length = 0; histRedo.length = 0; syncHistBtns();
            await loadObjects(true);
            // No toast: the map arriving on screen IS the confirmation, and a
            // banner over it only covers the thing you asked to see.
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
        // Same reasoning as the clear that arrives from the room: an emptied
        // canvas is not the saved map any more, and what gets drawn next is a
        // new map — not a replacement for the one that was just swept away.
        cancelAutosave();
        setLoadedSave(null);
        if (snapshot.length) pushHist({ type: 'clear', objects: snapshot });
    }
    async function startBlankCanvas() {
        // Nothing on screen, so nothing to confirm — but a write can still be
        // armed: deleting the last shape marks the map dirty, and the timer
        // that fires two seconds later bails on the empty canvas without
        // clearing the flag. Letting go of the file while that flag is still
        // up leaves it pointing at nothing, and the next map adopted from a
        // teammate's save inherits it — which is how a "your last edits were
        // not saved" warning reaches somebody who edited nothing.
        if (objIndex.size === 0) { cancelAutosave(); setLoadedSave(null); return; }
        const n = objIndex.size;
        const ok = window.confirmAction
            ? await confirmAction({ title: 'Start a blank map?', message: 'Removes the ' + n + ' shape' + (n === 1 ? '' : 's') + ' on the canvas for the whole team. Save the current map first if it is worth keeping.', confirmText: 'Start blank' })
            : confirm('Start a blank map? This clears the current shapes for everyone.');
        if (!ok) return;
        try {
            await wipeCanvas();   // which is also what lets go of the open map
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
                    if (sv) await loadSavedMap(sv);
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
        bindEdgeInsert(map.getDiv());

        document.querySelectorAll('[data-mtool]').forEach((b) =>
            b.addEventListener('click', () => setTool(b.dataset.mtool)));
        document.getElementById('cmapToolsBtn').addEventListener('click', () => window.openSheet?.('cmapToolsSheet'));
        document.getElementById('cmapSaveMenuBtn')?.addEventListener('click', () => window.openSheet?.('cmapSaveMenuSheet'));
        document.querySelectorAll('[data-maction]').forEach((b) => b.addEventListener('click', () => {
            window.closeSheet?.('cmapSaveMenuSheet');
            if (b.dataset.maction === 'open') openSaves();
            else openSaveSheet(b.dataset.maction === 'savemap' ? 'map' : 'image');
        }));

        /* ---- how thick the pen draws ---- */
        const sizeDot = document.getElementById('cmapSizeDot');
        const sayWidth = () => { if (sizeDot) sizeDot.style.setProperty('--w', Math.min(8, width) + 'px'); };
        sayWidth();
        document.getElementById('cmapSizeBtn')?.addEventListener('click', () => window.openSheet?.('cmapSizeSheet'));
        document.querySelectorAll('#cmapSizeSheet .cmap-sizeopt').forEach((b) => b.addEventListener('click', () => {
            width = parseInt(b.dataset.msize, 10) || 3;
            document.querySelectorAll('#cmapSizeSheet .cmap-sizeopt').forEach((x) => x.classList.toggle('is-active', x === b));
            sayWidth();
            window.closeSheet?.('cmapSizeSheet');
            // A half-drawn line takes the new weight immediately.
            if (tempPts.length) previewTemp(tool === 'area');
        }));
        /* ---- writing on the map ---- */
        // Each face shown in itself, painted from the one list of stacks —
        // duplicating them into the stylesheet is how the picker ends up
        // showing a font the map does not draw with.
        document.querySelectorAll('#cmapTextSheet .cmap-fontopt').forEach((b) => {
            const sample = b.querySelector('.cmap-fontsample');
            if (sample) sample.style.fontFamily = FONTS[b.dataset.mfont] || '';
            b.addEventListener('click', () => { textFont = b.dataset.mfont; paintFontRow(); });
        });
        document.getElementById('cmapTextInput')?.addEventListener('input', sayTextLeft);
        document.getElementById('cmapTextGo')?.addEventListener('click', commitTextSheet);
        document.getElementById('cmapEditText')?.addEventListener('click', () => {
            if (editing) openTextSheet(objIndex.get(editing.o.id) || editing.o);
        });
        // Every other way out of the sheet — the ×, the backdrop, the phone's
        // Back button — is a cancel. Letting the draft stand would mean the
        // next label typed landed on ground somebody walked away from.
        document.getElementById('cmapTextSheet')?.addEventListener('sheet:close', () => { textDraft = null; });
        document.getElementById('cmapSaveGo').addEventListener('click', () => doSaveMap(false));
        document.getElementById('cmapSaveOver').addEventListener('click', () => doSaveMap(true));
        document.getElementById('cmapDelPoint').addEventListener('click', deleteSelPoint);
        document.getElementById('cmapDelObj').addEventListener('click', deleteEditedObj);
        document.getElementById('cmapEditDone').addEventListener('click', endEdit);
        // Pending corners first, the committed history only once there are
        // none — so Undo never reaches over a half-drawn shape to delete a
        // finished one for the whole team.
        document.getElementById('cmapUndo').addEventListener('click', () => {
            if (takeBackCorner()) return;
            stepHist(histUndo, histRedo);
        });
        document.getElementById('cmapRedo').addEventListener('click', () => {
            if (putBackCorner()) return;
            stepHist(histRedo, histUndo);
        });
        document.getElementById('cmapColorBtn').addEventListener('click', () => window.openSheet?.('cmapColorSheet'));
        document.querySelectorAll('#cmapColorSheet .cmap-swatch').forEach((b) => b.addEventListener('click', () => {
            color = b.dataset.mcolor;
            document.querySelectorAll('#cmapColorSheet .cmap-swatch').forEach((x) => x.classList.toggle('is-active', x === b));
            const dotEl = document.getElementById('cmapColorDot');
            if (dotEl) dotEl.style.background = color;
            window.closeSheet?.('cmapColorSheet');
            // A half-drawn shape repaints on the spot, dots included — and the
            // badge, which is coloured to match the shape it belongs to.
            if (tempPts.length) {
                previewTemp(tool === 'area');
                tempDots.forEach((m) => m.setIcon(pinIcon()));
                if (tempBadge) tempBadge.setIcon({ path: BADGE_DISC, scale: 1, fillColor: color,
                    fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2,
                    labelOrigin: new (G().Point)(0, BADGE_DROP) });
            }
        }));
        document.getElementById('cmapSearchBtn').addEventListener('click', () => {
            window.openSheet?.('cmapSearchSheet');
            // Search means typing — focusing here is the point, not a nuisance.
            window.smFocus('cmapSearch', { delay: 320 });
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
        document.getElementById('cmapFindMe')?.addEventListener('click', (e) => findMe(e.currentTarget));
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
                    if (!p) return;
                    /* "Mine" is this ACCOUNT, not this screen. The same person
                       has the room open on a laptop and a phone, and every one
                       of those clients hears itself.
                       Shapes are still skipped for them — the screen that drew
                       one already has it, and re-applying it is how a dragged
                       shape jumps back. But the two actions that change what a
                       screen BELIEVES it is editing are not: skipping those
                       left the other tab still holding a saved map that had
                       been swept out from under it, and its next autosave
                       wrote that emptied canvas over the file. */
                    const mine = p.actorUserId === ME;
                    if (p.action === 'add') { if (!mine && p.object) renderObject(p.object); }
                    else if (p.action === 'update') {
                        if (mine || !p.object) return;
                        if (editing && editing.o.id === p.object.id) endEdit();
                        dropObject(p.object.id); renderObject(p.object);
                    }
                    else if (p.action === 'remove') { if (!mine) dropObject(p.id, true); }
                    else if (p.action === 'clear') {
                        dropAll();
                        // Someone started a blank map. This screen was writing
                        // into a saved one, and going on believing that is how
                        // the next shape drawn here ends up posted as the whole
                        // of somebody's map — so let the file go, and drop the
                        // write that was already queued for it.
                        cancelAutosave();
                        setLoadedSave(null);
                    }
                    else if (p.action === 'saved') {
                        // Someone's map wrote itself back. Follow the file so
                        // an edit made from this screen lands in the same one
                        // instead of quietly forking a second copy — but only
                        // from a screen that is not on a file already. The
                        // shapes are the room's; WHICH file they are saved as
                        // is the saver's choice, and a teammate keeping a copy
                        // must not drag everyone else's next edit into it, nor
                        // move the target out from under a save mid-compose.
                        if (p.saveId && !LOADED_SAVE) setLoadedSave({ id: p.saveId, title: p.title || 'Map' });
                        // And only the person who saved is told so. This fires
                        // on every client in the room, which meant a green
                        // "Saved" over the work of people who had saved
                        // nothing — the one word this pill must never lie about.
                        if (mine) sayAutosave('saved');
                    }
                    else if (p.action === 'reload') {
                        // Someone loaded a saved map — take the fresh set whole.
                        // Even the client that asked for it refetches, and even
                        // when the file named is the one already open: loading
                        // re-inserts every shape server-side, so every id on
                        // screen is stale whoever pressed the button.
                        //
                        // The one place a queued write cannot be rescued. By
                        // the time this arrives the server has already swapped
                        // the shapes, and the save endpoint reads them live —
                        // so a flush here would file the arriving map under the
                        // departing one's name, which is worse than losing the
                        // edits. Whoever pressed the button flushed before
                        // asking (loadSavedMap); everyone else is told what
                        // went, because silence here reads as "it saved".
                        const owed = autoDirty && !!LOADED_SAVE;
                        const leaving = LOADED_SAVE ? LOADED_SAVE.title : '';
                        cancelAutosave();
                        if (owed) {
                            sayAutosave('failed');
                            if (window.toast) toast('Someone opened another map — the last edits to “' + leaving + '” were not saved.', 'error');
                        }
                        endEdit(); dropAll();
                        histUndo.length = 0; histRedo.length = 0; syncHistBtns();
                        // And take which file it is: without this the room ends
                        // up editing a map only one person is saving.
                        if (p.saveId) setLoadedSave({ id: p.saveId, title: p.title || 'Map' });
                        loadObjects(true).catch(() => {});
                    }
                });
                // These two stay skipped for the whole account, deliberately.
                // They key on userId, so a phone and a laptop reporting the
                // same person would fight over one dot and one ghost — and the
                // local watchPosition and the half-drawn shape already draw
                // them here. Nothing about either changes what this screen
                // thinks it is editing, so nothing is lost by not hearing it.
                ch.listen('.map.loc', (p) => { if (p && p.userId !== ME) renderLoc(p); });
                ch.listen('.map.trace', (p) => { if (p && p.userId !== ME) renderGhost(p); });
            } catch (_) { /* map still works solo */ }
        }
    }

    /* Look at the box again.
     *
     * Google measures the container once, when the map is constructed. In
     * the Collab Room the map lives in a tab, and the construction can land
     * while that tab is off screen — the Maps script is fetched on first
     * show and boots whenever it arrives, by which time the reader may have
     * moved on. A map built against a box of nothing stays that size, which
     * is the grey rectangle people report as "the map tab does not work".
     * Every return to the tab now asks it to measure again. */
    window.cmapRefresh = function () {
        if (!booted || !map || !window.google?.maps) return;
        const at = map.getCenter();
        G().event.trigger(map, 'resize');
        if (at) map.setCenter(at);
    };

    window.initCollabMap = function () {
        if (booted) { window.cmapRefresh(); return; }
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
