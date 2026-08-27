{{-- Collab Room photo tab: one picture, the whole team's pens.

     Pick a photo from the gallery, upload one, or take one — then everybody in
     the room draws over it together, live, with the pad's tools. The strokes
     are normalized to the PHOTO's own pixels (0..1), so a phone and a laptop
     mark the same spot of the same carabao whatever their screens look like.
     Saving files the drawn-over picture as a note, into the Gallery's Team
     photos album, or into an album of your choosing — tagged as a team image,
     because that is what it is. --}}

{{-- The picker defines window.smPickMedia once and guards against a second
     copy, so carrying it here costs nothing on pages that already have it. --}}
@include('sm.partials.media-picker', ['schedule' => $schedule])

<div class="cph-wrap" id="cphWrap">
    {{-- One door in, and it says what it does.

         Gallery / Upload / Camera stood here as three chips of equal weight,
         which asked somebody to pick a source before they had picked a photo,
         and gave each way one word to explain itself. One button opens all
         three now, in a sheet with room for a sentence each. --}}
    {{-- The tab says its own name, as the chat and the whiteboard do. --}}
    <div class="cph-head">
        <span class="cph-title">📸 Team photo</span>
    </div>

    {{-- The photo, and the pens over it. --}}
    <div class="cph-stage" id="cphStage">
        {{-- The way in sits at the head of the tools rather than floating
             above them: before a photo exists it is the only control there
             is, and after one exists it is one more thing you can do to the
             picture. Two boxes stacked with a gap read as two panels. --}}
        <div class="cph-srcrow">
            <button type="button" class="btn btn-outline btn-sm cph-add" id="cphAddBtn">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8a2 2 0 012-2h1.4l1-1.6h7.2l1 1.6H18a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.6v5M9.5 12.1h5"/></svg>
                <span id="cphAddLabel">Add photo</span>
            </button>
            <span class="cph-srchint">Everyone in the room draws on the same picture.</span>
        </div>
        <div class="cph-bar" id="cphBar" hidden>
            {{-- Row one: what you draw with, as one segmented control
                 rather than eight loose keys. --}}
            <div class="cph-row">
                <div class="cph-group">
                    <button type="button" class="cph-tool is-active" data-cph-tool="pen" title="Pen"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="line" title="Line"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 19L19 5"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="arrow" title="Arrow"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19L19 5m0 0h-7m7 0v7"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="rect" title="Box"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="1.5"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="circle" title="Circle"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="text" title="Text"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 6h14M12 6v13"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="eraser" title="Eraser (strokes only — the photo is safe)"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20h10M6.5 14.5l8-8a2 2 0 012.8 0l2.2 2.2a2 2 0 010 2.8l-8 8H8l-3.5-3.5a1.5 1.5 0 010-2.1l2-2z"/></svg></button>
                    <button type="button" class="cph-tool" data-cph-tool="move" title="Move and zoom the photo"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M2 12h20M12 2l-3 3m3-3l3 3M12 22l-3-3m3 3l3-3M2 12l3-3m-3 3l3 3M22 12l-3-3m3 3l-3 3"/></svg></button>
                </div>
            </div>
            {{-- Row two: what you do about it, in groups — how the pen
                 looks, taking a mark back, and what becomes of the whole
                 picture. The last two sit apart from the pens on purpose:
                 clearing everybody’s marks is not a pen stroke. --}}
            <div class="cph-row">
                <div class="cph-group">
                    <button type="button" class="cph-tool" id="cphColor" title="Colour"><span class="cph-color-dot" id="cphColorDot"></span></button>
                    <button type="button" class="cph-tool" id="cphSize" title="Line thickness"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1" d="M4 6h16"/><path stroke-linecap="round" stroke-width="2.5" d="M4 12h16"/><path stroke-linecap="round" stroke-width="4.5" d="M4 18.5h16"/></svg></button>
                </div>
                <div class="cph-group">
                    <button type="button" class="cph-tool" id="cphUndo" title="Take back my last stroke"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg></button>
                    <button type="button" class="cph-tool" id="cphRedo" title="Put it back" disabled><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4"/></svg></button>
                </div>
                <button type="button" class="cph-tool cph-danger" id="cphClear" title="Clear all strokes for the team"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg></button>
                <button type="button" class="cph-tool cph-saveic" id="cphSaveBtn" title="Keep this image" aria-label="Keep this image"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v5h6M8 14h8v6H8z"/></svg><span>Save</span></button>
            </div>
        </div>
        <div class="cph-box" id="cphBox">
            <div class="cph-none" id="cphNone">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="9" r="1.2"/></svg>
                <span>No photo up yet — tap <b>Add photo</b>, and the whole team draws on it together.</span>
            </div>
            <img id="cphImg" alt="" draggable="false" hidden>
            <canvas id="cphCanvas" style="visibility:hidden"></canvas>
        </div>
    </div>

    <input type="file" id="cphUploadInput" accept="image/jpeg,image/png,image/webp" hidden>
    {{-- capture= asks the phone for its camera rather than its files. --}}
    <input type="file" id="cphCameraInput" accept="image/*" capture="environment" hidden>
</div>

{{-- The three ways to a photo. A sheet rather than three chips: each way
     gets a line saying what it actually does, and the one this account cannot
     use is not drawn — a worker whose owner withheld the camera is not
     offered it here either, because this is that owner's season.

     Ordered as people reach for them: what the farm already has, then this
     device, then the camera. --}}
<div class="sheet hidden" id="cphSourceSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title">Add a photo</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="cph-srcs">
            <button type="button" class="cph-src" id="cphPickBtn">
                <span class="cph-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 15l3-3.5 2.4 2.8L15 11l3 4"/></svg></span>
                <span class="cph-src-t"><b>From the gallery</b><small>A picture this schedule already keeps.</small></span>
                <svg class="cph-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="cph-src" id="cphUploadBtn">
                <span class="cph-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 17v1.5A2.5 2.5 0 006.5 21h11a2.5 2.5 0 002.5-2.5V17"/></svg></span>
                <span class="cph-src-t"><b>Upload from this device</b><small>Choose a file on your phone or computer.</small></span>
                <svg class="cph-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            @if (\App\Support\WorkerContext::canUseModule('camera'))
            <button type="button" class="cph-src" id="cphCameraBtn">
                <span class="cph-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8a2 2 0 012-2h1.4l1-1.6h7.2l1 1.6H18a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V8z"/><circle cx="12" cy="13" r="3.4"/></svg></span>
                <span class="cph-src-t"><b>Take a photo now</b><small>Open the camera and put up what you see.</small></span>
                <svg class="cph-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            @endif
        </div>
    </div>
</div>

{{-- Colour picker: same swatches the whiteboard offers. --}}
<div class="sheet hidden" id="cphColorSheet" style="--sheet-width:20rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title">Pen colour</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="cph-swatches" id="cphSwatches"></div>
    </div>
</div>

{{-- Line thickness, its own sheet like the drawing surfaces have. --}}
<div class="sheet hidden" id="cphSizeSheet" style="--sheet-width:20rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title">Line thickness</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="cph-widths" id="cphWidths">
            <button type="button" data-cph-width="3"><span style="height:3px"></span><b>Fine</b></button>
            <button type="button" class="is-active" data-cph-width="5"><span style="height:5px"></span><b>Normal</b></button>
            <button type="button" data-cph-width="9"><span style="height:9px"></span><b>Thick</b></button>
            <button type="button" data-cph-width="14"><span style="height:14px"></span><b>Heavy</b></button>
        </div>
    </div>
</div>

{{-- The text tool asks here — a sheet, never a browser prompt. --}}
<div class="sheet hidden" id="cphTextSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title">Write on the photo</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body space-y-3" style="padding-bottom:1.1rem">
        <input type="text" id="cphTextInput" class="form-input" maxlength="500" placeholder="The words to put there">
        <button type="button" id="cphTextGo" class="btn btn-primary w-full">Place it</button>
    </div>
</div>

{{-- Where the drawn-over photo goes. --}}
<div class="sheet hidden" id="cphSaveSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title">Keep this image</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body space-y-3" style="padding-bottom:1.1rem">
        <div>
            <label class="form-label" for="cphSaveTitle">Name</label>
            <input type="text" id="cphSaveTitle" class="form-input" maxlength="191" placeholder="What this picture shows">
        </div>
        <div>
            <label class="form-label" for="cphSaveDesc">Description</label>
            <textarea id="cphSaveDesc" class="form-input form-textarea" rows="2" maxlength="2000" placeholder="Anything worth remembering about it"></textarea>
        </div>
        <div class="cph-dests" id="cphDests">
            <label class="cph-dest"><input type="radio" name="cphDest" value="note" checked><span><b>A new note</b><small>In the schedule's notebook</small></span></label>
            <label class="cph-dest"><input type="radio" name="cphDest" value="gallery"><span><b>The Gallery</b><small>Filed in the “Team photos” album</small></span></label>
            <label class="cph-dest"><input type="radio" name="cphDest" value="album"><span><b>An album</b><small>One the schedule already has</small></span></label>
        </div>
        <select id="cphAlbumSel" class="form-input hidden"></select>
        <p class="cph-teamnote">Saved as a team image — it was drawn together, and it says so.</p>
        <button type="button" id="cphSaveGo" class="btn btn-primary w-full">Save</button>
    </div>
</div>

<style>
    .cph-wrap { display: flex; flex-direction: column; min-height: 0; height: 100%; gap: .4rem; }
    /* One button, and a line of words beside it. */
    /* The tab's own name, in the shape the chat and the whiteboard use. */
    .cph-head { display: flex; align-items: center; gap: .5rem; padding: .1rem .1rem .15rem; }
    .cph-title { font-size: .85rem; font-weight: 800; color: var(--color-gray-800); }
    html.dark .cph-title { color: #e8efe1; }

    /* The head of the tool card, not a panel of its own: same background and
       border as the bar below it, joined to it with no seam so the two read
       as one container. Before there is a photo the bar is hidden and this
       is simply the whole card. */
    .cph-srcrow { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
        margin-top: .35rem; padding: .4rem .45rem;
        border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    /* Joined only when there is something below to join to. */
    .cph-stage:has(.cph-bar:not([hidden])) .cph-srcrow {
        border-bottom-left-radius: 0; border-bottom-right-radius: 0;
        border-bottom-color: transparent; margin-bottom: calc(-1 * .4rem - 1px); }
    .cph-stage:has(.cph-bar:not([hidden])) .cph-bar {
        border-top-left-radius: 0; border-top-right-radius: 0; }
    .cph-add svg { width: 1.05rem; height: 1.05rem; }
    .cph-srchint { font-size: .72rem; color: var(--color-gray-400); }
    @media (max-width: 560px) { .cph-srchint { display: none; } }
    html.dark .cph-srcrow { background: #151b12; border-color: #2b3a1c; }

    /* The three ways, in the sheet. */
    .cph-srcs { display: flex; flex-direction: column; gap: .5rem; }
    .cph-src { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-radius: .85rem; border: 1px solid var(--color-gray-200);
        background: var(--color-white);
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .1s ease; }
    .cph-src:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .cph-src:active { transform: scale(.985); }
    .cph-src-ic { flex-shrink: 0; width: 2.4rem; height: 2.4rem; border-radius: .7rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-brand-100); color: var(--color-brand-800); }
    .cph-src-ic svg { width: 1.25rem; height: 1.25rem; }
    .cph-src-t { flex: 1; min-width: 0; }
    .cph-src-t b { display: block; font-size: .84rem; font-weight: 800; color: var(--color-gray-800); }
    .cph-src-t small { display: block; font-size: .7rem; color: var(--color-gray-400); margin-top: .05rem; }
    .cph-src-go { width: 1rem; height: 1rem; flex-shrink: 0; color: var(--color-gray-300); }
    @media (prefers-reduced-motion: reduce) { .cph-add, .cph-src { transition: none; } }
    html.dark .cph-src { background: #1c2416; border-color: #2b3a1c; }
    html.dark .cph-src-t b { color: #e8efe1; }

    /* Before a photo: a quiet dashed square with a line of words. Pure CSS and
       inline SVG — never an <img> with nothing behind it, which renders as the
       browser's broken-picture glyph and reads as a bug. */
    .cph-none { display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .5rem; padding: 1.2rem; text-align: center; color: var(--color-gray-400);
        font-size: .78rem; max-width: 22rem; }
    .cph-none svg { width: 2rem; height: 2rem; opacity: .6; }
    .cph-none[hidden] { display: none; }

    .cph-stage { flex: 1; display: flex; flex-direction: column; min-height: 0; gap: .4rem; }
    /* The bar is one card holding two rows, not two rows of loose keys
       floating on the page above the picture. */
    .cph-bar { display: flex; flex-direction: column; gap: .3rem; padding: .35rem;
        border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .cph-bar[hidden] { display: none; }
    .cph-row { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
    .cph-group { display: inline-flex; align-items: center; gap: .1rem; padding: .15rem;
        border-radius: .75rem; background: var(--color-white); box-shadow: inset 0 0 0 1px var(--color-gray-200); }
    .cph-row .cph-saveic { margin-left: auto; width: auto; padding: 0 .7rem; gap: .35rem;
        font-size: .76rem; font-weight: 800; background: #4a7c2a; color: #fff; }
    .cph-row .cph-saveic:hover { background: #3d6823; color: #fff; }
    .cph-saveic.is-gone { display: none; }
    /* The move tool holds the photo, not a pen. */
    .cph-box.is-move canvas { cursor: grab; }
    .cph-box.is-move canvas:active { cursor: grabbing; }
    .cph-tool { min-width: 2.15rem; height: 2.15rem; border-radius: .6rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-gray-100); color: var(--color-gray-600);
        transition: background .15s ease, color .15s ease, transform .1s ease; }
    .cph-tool svg { width: 1.1rem; height: 1.1rem; }
    .cph-tool:hover { background: var(--color-gray-200); color: var(--color-gray-800); }
    .cph-tool:active { transform: scale(.92); }
    .cph-tool.is-active { background: var(--color-brand-100); color: var(--color-brand-800); }
    .cph-tool:disabled { opacity: .35; pointer-events: none; }
    /* Inside a group the keys share one surface; only the chosen one is lit. */
    .cph-group .cph-tool { background: transparent; }
    .cph-group .cph-tool:hover { background: var(--color-gray-100); }
    .cph-group .cph-tool.is-active { background: var(--color-brand-100); color: var(--color-brand-800); }
    /* Clearing everybody's marks says so at rest, not only under the cursor. */
    .cph-danger { color: #b91c1c; background: #fee2e2; }
    .cph-danger:hover { background: #fecaca; color: #991b1b; }
    .cph-color-dot { width: 1.05rem; height: 1.05rem; border-radius: 999px; background: #f5c518;
        border: 2px solid rgb(255 255 255 / .9); box-shadow: 0 0 0 1px rgb(0 0 0 / .12); }

    /* The photo sits in a box; the canvas covers the box exactly, so a canvas
       point IS a box point and only the contain-rect math knows the image. */
    .cph-box { position: relative; flex: 1; min-height: 12rem; border-radius: .8rem; overflow: hidden;
        border: 1.5px dashed var(--color-gray-200); background: var(--color-gray-50);
        display: flex; align-items: center; justify-content: center; }
    .cph-box.has-photo { border: 0; background: #10140c; }
    html.dark .cph-box { background: #151b12; border-color: #2b3a1c; }
    html.dark .cph-box.has-photo { background: #10140c; }
    .cph-box img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;
        user-select: none; -webkit-user-drag: none; }
    .cph-box img[hidden] { display: none; }
    .cph-box canvas { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none;
        cursor: crosshair; }
    .cph-hint { font-size: .7rem; color: var(--color-gray-400); min-height: 1em; }

    .cph-swatches { display: grid; grid-template-columns: repeat(8, 1fr); gap: .45rem; margin-bottom: .8rem; }
    .cph-swatch { width: 100%; aspect-ratio: 1; border-radius: 999px; border: 2px solid rgb(0 0 0 / .08); }
    .cph-swatch.is-active { outline: 3px solid var(--color-brand-300); outline-offset: 2px; }
    .cph-widths { display: flex; gap: .5rem; }
    .cph-widths button { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .4rem;
        padding: .55rem; border-radius: .7rem; background: var(--color-gray-100); font-size: .7rem; font-weight: 700;
        color: var(--color-gray-600); }
    .cph-widths button span { display: block; width: 70%; border-radius: 99px; background: currentColor; }
    .cph-widths button.is-active { background: var(--color-brand-100); color: var(--color-brand-800); }

    .cph-dests { display: flex; flex-direction: column; gap: .4rem; }
    .cph-dest { display: flex; align-items: center; gap: .6rem; padding: .55rem .7rem; border-radius: .7rem;
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .cph-dest:has(input:checked) { border-color: var(--color-brand-500); background: var(--color-brand-50); }
    .cph-dest b { display: block; font-size: .8rem; color: var(--color-gray-800); }
    .cph-dest small { font-size: .68rem; color: var(--color-gray-400); }
    .cph-teamnote { font-size: .7rem; color: var(--color-gray-400); }

    html.dark .cph-source { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .cph-tool { background: #1c2416; color: #cdd8c0; }
    html.dark .cph-bar { background: #151b12; border-color: #2b3a1c; }
    html.dark .cph-group { background: #10140c; box-shadow: inset 0 0 0 1px #2b3a1c; }
    html.dark .cph-group .cph-tool { background: transparent; }
    html.dark .cph-empty-title { color: #e8efe1; }
    html.dark .cph-dest { border-color: #2b3a1c; }
    html.dark .cph-dest b { color: #e8efe1; }
</style>

<script>
(() => {
    'use strict';
    const SID = {{ (int) $schedule->id }};
    const ME = {{ (int) auth()->id() }};
    const U = {
        state: @json(route('sm.photo')),
        set: @json(route('sm.photo.set')),
        push: @json(route('sm.photo.push')),
        undo: @json(route('sm.photo.undo')),
        save: @json(route('sm.photo.save')),
    };
    const $id = (x) => document.getElementById(x);
    const api = (...a) => window.api(...a);

    let booted = false, photoUrl = null, gen = '', lastId = 0, canSave = true;
    let strokes = [];                    // [{id, uid, mode, color, width, points, text, userId}]
    const rendered = new Set();          // event ids already on the canvas
    let tool = 'pen', color = '#f5c518', width = 5;
    let pollTimer = null, channel = null;

    /* ---------- geometry: photo pixels <-> canvas pixels ---------- */
    /* Strokes live in the photo's own 0..1 space. The canvas covers the box;
       the photo sits inside it object-fit:contain — this is that rectangle. */
    const img = () => $id('cphImg');
    /* The viewport: zoom and pan, one set of numbers. The img wears it as a
     * CSS transform and fitRect folds the SAME numbers into the stroke math,
     * so the ink and the photo can never slide apart. Strokes live in the
     * photo's own 0..1 space throughout — the room never sees your zoom. */
    const view = { z: 1, x: 0, y: 0 };
    function applyView() {
        const im = img();
        im.style.transformOrigin = '0 0';
        im.style.transform = (view.z === 1 && !view.x && !view.y)
            ? '' : 'translate(' + view.x + 'px,' + view.y + 'px) scale(' + view.z + ')';
        paintAll();
    }
    function resetView() { view.z = 1; view.x = 0; view.y = 0; applyView(); }
    function zoomAt(px, py, factor) {
        const z = Math.max(1, Math.min(6, view.z * factor));
        if (z === view.z) return;
        // The point under the finger stays under the finger.
        view.x = px - ((px - view.x) / view.z) * z;
        view.y = py - ((py - view.y) / view.z) * z;
        view.z = z;
        if (z === 1) { view.x = 0; view.y = 0; }
        clampView();
        applyView();
    }
    function clampView() {
        // The photo may wander but never leave: at least a third of the box
        // stays covered, or a hard pan strands the screen on blank ground.
        const b = $id('cphBox');
        view.x = Math.max(-b.clientWidth * view.z + b.clientWidth / 3, Math.min(view.x, b.clientWidth * 2 / 3));
        view.y = Math.max(-b.clientHeight * view.z + b.clientHeight / 3, Math.min(view.y, b.clientHeight * 2 / 3));
    }
    function fitRect() {
        const box = $id('cphBox').getBoundingClientRect();
        const iw = img().naturalWidth || 1, ih = img().naturalHeight || 1;
        const s = Math.min(box.width / iw, box.height / ih);
        const dw = iw * s, dh = ih * s;
        const bx = (box.width - dw) / 2, by = (box.height - dh) / 2;
        return {
            dx: bx * view.z + view.x, dy: by * view.z + view.y,
            dw: dw * view.z, dh: dh * view.z,
            bw: box.width, bh: box.height,
        };
    }
    const toNorm = (x, y, r) => [
        Math.max(0, Math.min(1, (x - r.dx) / r.dw)),
        Math.max(0, Math.min(1, (y - r.dy) / r.dh)),
    ];

    /* ---------- painting ---------- */
    function paintStroke(g, s, r) {
        const px = (p) => [r.dx + p[0] * r.dw, r.dy + p[1] * r.dh];
        // Width scales with the photo, so a line drawn thick on a phone is
        // the same fraction of the picture on a laptop.
        const w = Math.max(1, (s.width || 4) * (r.dw / 900));
        g.lineWidth = w;
        g.lineCap = 'round'; g.lineJoin = 'round';
        g.strokeStyle = s.color || '#f5c518';
        g.fillStyle = s.color || '#f5c518';
        // The eraser rubs out strokes, never the photo: the canvas holds only
        // ink, so knocking pixels out of it reveals the picture underneath.
        g.globalCompositeOperation = s.mode === 'eraser' ? 'destination-out' : 'source-over';
        if (s.mode === 'eraser') g.lineWidth = w * 3;
        const pts = (s.points || []).map(px);
        if (!pts.length) return;
        if (s.mode === 'text') {
            g.globalCompositeOperation = 'source-over';
            g.font = '800 ' + Math.max(12, r.dw / 28) + 'px system-ui, sans-serif';
            g.strokeStyle = 'rgb(0 0 0 / .55)'; g.lineWidth = 3;
            g.strokeText(s.text || '', pts[0][0], pts[0][1]);
            g.fillText(s.text || '', pts[0][0], pts[0][1]);
            return;
        }
        if (s.mode === 'rect' && pts.length >= 2) {
            g.strokeRect(pts[0][0], pts[0][1], pts[1][0] - pts[0][0], pts[1][1] - pts[0][1]);
            return;
        }
        if (s.mode === 'circle' && pts.length >= 2) {
            const cx = (pts[0][0] + pts[1][0]) / 2, cy = (pts[0][1] + pts[1][1]) / 2;
            g.beginPath();
            g.ellipse(cx, cy, Math.abs(pts[1][0] - pts[0][0]) / 2, Math.abs(pts[1][1] - pts[0][1]) / 2, 0, 0, Math.PI * 2);
            g.stroke();
            return;
        }
        g.beginPath();
        g.moveTo(pts[0][0], pts[0][1]);
        for (let i = 1; i < pts.length; i++) g.lineTo(pts[i][0], pts[i][1]);
        if ((s.mode === 'line' || s.mode === 'arrow') && pts.length >= 2) {
            g.beginPath(); g.moveTo(pts[0][0], pts[0][1]); g.lineTo(pts[pts.length - 1][0], pts[pts.length - 1][1]);
        }
        g.stroke();
        if (s.mode === 'arrow' && pts.length >= 2) {
            const a = pts[0], b = pts[pts.length - 1];
            const ang = Math.atan2(b[1] - a[1], b[0] - a[0]), h = Math.max(8, w * 3);
            g.beginPath();
            g.moveTo(b[0], b[1]);
            g.lineTo(b[0] - h * Math.cos(ang - 0.5), b[1] - h * Math.sin(ang - 0.5));
            g.moveTo(b[0], b[1]);
            g.lineTo(b[0] - h * Math.cos(ang + 0.5), b[1] - h * Math.sin(ang + 0.5));
            g.stroke();
        }
    }
    function paintAll(preview) {
        const c = $id('cphCanvas');
        const box = $id('cphBox');
        if (c.width !== box.clientWidth || c.height !== box.clientHeight) {
            c.width = box.clientWidth; c.height = box.clientHeight;
        }
        const g = c.getContext('2d');
        g.clearRect(0, 0, c.width, c.height);
        const r = fitRect();
        strokes.forEach((s) => paintStroke(g, s, r));
        if (preview) paintStroke(g, preview, r);
        g.globalCompositeOperation = 'source-over';
    }

    /* ---------- state application (Echo and poll share these) ---------- */
    function applyStroke(ev) {
        if (!ev || rendered.has(ev.id)) return;
        rendered.add(ev.id);
        if (ev.id > lastId) lastId = ev.id;
        strokes.push(ev);
        paintAll();
    }
    function applyRemove(ids) {
        const gone = new Set(ids || []);
        strokes = strokes.filter((s) => !gone.has(s.id));
        paintAll();
    }
    function applyClear() {
        strokes = [];
        paintAll();
    }
    function applyPhoto(url, g2) {
        photoUrl = url; gen = g2 || '';
        strokes = []; rendered.clear(); lastId = 0;
        // A new photo starts at its own size, not the last one's zoom.
        view.z = 1; view.x = 0; view.y = 0;
        img().style.transform = '';
        showStage(true);
        loadPhoto(url);
    }
    function showStage(on) {
        // The stage is always the layout; what changes is whether it holds a
        // photo or the placeholder. The img is display:none until it has a
        // real picture — an <img> with nothing behind it renders the browser's
        // broken-picture glyph, which reads as a bug rather than an absence.
        $id('cphBar').hidden = !on;
        $id('cphNone').hidden = on;
        // The one button carries both errands, so it has to say which it is.
        $id('cphAddLabel').textContent = on ? 'Change photo' : 'Add photo';
        $id('cphBox').classList.toggle('has-photo', on);
        img().hidden = !on;
        $id('cphCanvas').style.visibility = on ? '' : 'hidden';
    }
    function loadPhoto(url) {
        if (!url) { showStage(false); return; }
        const im = img();
        // anonymous so the save-composite is allowed to read the pixels back —
        // the mother site already answers its storage with open CORS.
        im.crossOrigin = 'anonymous';
        im.onload = () => paintAll();
        // A picker path whose file is gone (this dev disk forgets) must not
        // sit there as a broken glyph pretending to be the team's photo.
        im.onerror = () => {
            showStage(false);
            if (window.toast) toast('That photo could not be loaded — pick another.', 'error');
        };
        im.src = url;
    }

    /* ---------- realtime + poll ---------- */
    function startLive() {
        try {
            channel = window.Echo?.private('schedule-board.' + SID);
            channel?.listen('.photo.event', (p) => {
                if (!p) return;
                if (p.action === 'stroke') { if (p.actorUserId !== ME) applyStroke(p.event); return; }
                if (p.action === 'remove') { applyRemove(p.ids); return; }
                if (p.action === 'clear') { if (p.actorUserId !== ME) applyClear(); return; }
                if (p.action === 'photo' && p.gen !== gen) applyPhoto(p.url, p.gen);
            });
        } catch (_) { /* the poll below carries the room by itself */ }
        const tick = async () => {
            try {
                const r = await api(`${U.state}?scheduleId=${SID}&after=${lastId}`);
                const d = r.data || {};
                canSave = !!d.canSave; syncSave();
                if ((d.gen || '') !== gen) {
                    // The photo changed under us: take the new world whole.
                    gen = d.gen || '';
                    if (d.photo) { photoUrl = d.photo.url; strokes = []; rendered.clear(); lastId = 0; showStage(true); loadPhoto(photoUrl); }
                    else { showStage(false); }
                    // The events in this reply belong to the new photo.
                }
                (d.events || []).forEach(applyStroke);
            } catch (_) { /* transient */ }
            const live = window.realtimeReady?.() ?? false;
            pollTimer = setTimeout(tick, live ? 5000 : 1600);
        };
        tick();
    }
    function syncSave() {
        const b = $id('cphSaveBtn');
        b.classList.toggle('is-gone', !canSave);
    }

    /* ---------- drawing ---------- */
    let drawing = null;   // {mode, uid, points(norm), flushed, timer}
    const uid = () => ME + '-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);

    async function pushStroke(body) {
        try {
            const r = await api(`${U.push}?scheduleId=${SID}`, { method: 'POST', body });
            const id = r?.data?.id;
            if (id) { rendered.add(id); if (id > lastId) lastId = id; return id; }
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
        return null;
    }

    /* Pen lines stream while the finger moves — a batch every 300ms sharing
       one uid, each batch opening where the last closed so the pieces join.
       Shapes and text go whole on release. */
    function flushPen(final) {
        const d = drawing;
        if (!d || (d.mode !== 'pen' && d.mode !== 'eraser')) return;
        const fresh = d.points.slice(d.flushed ? d.flushed - 1 : 0);
        if (fresh.length < 2) { if (final) endLocal(); return; }
        d.flushed = d.points.length;
        const mine = { id: null, uid: d.uid, mode: d.mode, color, width, points: fresh, userId: ME };
        pushStroke({ points: fresh, mode: d.mode, color, width, uid: d.uid }).then((id) => {
            if (id) { mine.id = id; }
        });
        strokes.push(mine);
        myOrder.push(mine);
        if (final) endLocal();
    }
    function endLocal() { drawing = null; paintAll(); }

    const myOrder = [];       // my strokes, for the redo stack
    let redoStack = [];

    function canvasPoint(e) {
        const b = $id('cphCanvas').getBoundingClientRect();
        return [e.clientX - b.left, e.clientY - b.top];
    }

    /* The move tool's fingers. Two at once is a pinch; the map of live
     * pointers is what tells one from the other. */
    const fingers = new Map();
    let pan = null, pinch = null;

    function bindCanvas() {
        const c = $id('cphCanvas');
        c.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            if (tool === 'move') {
                c.setPointerCapture(e.pointerId);
                const [mx, my] = canvasPoint(e);
                fingers.set(e.pointerId, { x: mx, y: my });
                if (fingers.size === 2) {
                    const [a, b] = [...fingers.values()];
                    pinch = { d: Math.hypot(a.x - b.x, a.y - b.y) || 1, z: view.z };
                    pan = null;
                } else {
                    pan = { x: mx, y: my, vx: view.x, vy: view.y };
                }
                return;
            }
            const r = fitRect();
            const [x, y] = canvasPoint(e);
            const p = toNorm(x, y, r);
            c.setPointerCapture(e.pointerId);
            if (tool === 'text') {
                textAt = p;
                $id('cphTextInput').value = '';
                window.openSheet?.('cphTextSheet');
                window.smFocus?.($id('cphTextInput'), { delay: 150 });
                return;
            }
            drawing = { mode: tool, uid: uid(), points: [p], flushed: 0, start: p };
            if (tool === 'pen' || tool === 'eraser') {
                drawing.timer = setInterval(() => flushPen(false), 300);
            }
        });
        c.addEventListener('pointermove', (e) => {
            if (tool === 'move') {
                if (!fingers.has(e.pointerId)) return;
                const [mx, my] = canvasPoint(e);
                fingers.set(e.pointerId, { x: mx, y: my });
                if (pinch && fingers.size === 2) {
                    const [a, b] = [...fingers.values()];
                    const d = Math.hypot(a.x - b.x, a.y - b.y) || 1;
                    zoomAt((a.x + b.x) / 2, (a.y + b.y) / 2, (pinch.z * (d / pinch.d)) / view.z);
                } else if (pan) {
                    view.x = pan.vx + (mx - pan.x);
                    view.y = pan.vy + (my - pan.y);
                    clampView();
                    applyView();
                }
                return;
            }
            if (!drawing) return;
            const r = fitRect();
            const [x, y] = canvasPoint(e);
            const p = toNorm(x, y, r);
            if (drawing.mode === 'pen' || drawing.mode === 'eraser') {
                drawing.points.push(p);
                paintAll({ mode: drawing.mode, color, width, points: drawing.points.slice(drawing.flushed ? drawing.flushed - 1 : 0) });
            } else {
                drawing.points = [drawing.start, p];
                paintAll({ mode: drawing.mode, color, width, points: drawing.points });
            }
        });
        const up = (e) => {
            if (e && fingers.delete(e.pointerId)) {
                if (fingers.size < 2) pinch = null;
                if (!fingers.size) pan = null;
                if (fingers.size === 1) { const f = [...fingers.values()][0]; pan = { x: f.x, y: f.y, vx: view.x, vy: view.y }; }
            }
            if (!drawing) return;
            const d = drawing;
            if (d.mode === 'pen' || d.mode === 'eraser') {
                clearInterval(d.timer);
                flushPen(true);
                return;
            }
            if (d.points.length >= 2) {
                const mine = { id: null, uid: d.uid, mode: d.mode, color, width, points: d.points, userId: ME };
                strokes.push(mine); myOrder.push(mine);
                pushStroke({ points: d.points, mode: d.mode, color, width, uid: d.uid })
                    .then((id) => { if (id) mine.id = id; });
            }
            endLocal();
        };
        c.addEventListener('pointerup', up);
        c.addEventListener('pointercancel', up);
        // A wheel zooms under the move tool — the desktop's pinch.
        c.addEventListener('wheel', (e) => {
            if (tool !== 'move') return;
            e.preventDefault();
            const [mx, my] = canvasPoint(e);
            zoomAt(mx, my, e.deltaY < 0 ? 1.15 : 1 / 1.15);
        }, { passive: false });
        // Double-tap puts the whole photo back — the gesture every map has
        // taught. dblclick fires for a double-tap on touch screens too.
        c.addEventListener('dblclick', () => { if (tool === 'move') resetView(); });
        window.addEventListener('resize', () => paintAll());
    }

    /* ---------- text ---------- */
    let textAt = null;
    function bindText() {
        $id('cphTextGo').addEventListener('click', () => {
            const t = $id('cphTextInput').value.trim();
            window.closeSheet?.('cphTextSheet');
            if (!t || !textAt) return;
            const mine = { id: null, uid: uid(), mode: 'text', color, width, points: [textAt], text: t, userId: ME };
            strokes.push(mine); myOrder.push(mine);
            paintAll();
            pushStroke({ points: [textAt], mode: 'text', color, width, text: t, uid: mine.uid })
                .then((id) => { if (id) mine.id = id; });
            textAt = null;
        });
        $id('cphTextInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); $id('cphTextGo').click(); }
        });
    }

    /* ---------- undo / redo (mine only) ---------- */
    function bindHistory() {
        $id('cphUndo').addEventListener('click', async () => {
            const last = myOrder.length ? myOrder[myOrder.length - 1] : null;
            try {
                const r = await api(`${U.undo}?scheduleId=${SID}`, { method: 'POST', body: last && last.uid ? { uid: last.uid } : {} });
                const ids = (r?.data?.ids) || [];
                if (!ids.length && !last) { if (window.toast) toast('Nothing of yours to take back.'); return; }
                // Everything with that uid is one gesture; it leaves together.
                const uidGone = last ? last.uid : null;
                const taken = uidGone ? myOrder.filter((s) => s.uid === uidGone) : [];
                if (taken.length) {
                    redoStack.push({
                        mode: taken[0].mode, color: taken[0].color, width: taken[0].width,
                        text: taken[0].text,
                        points: taken.flatMap((s) => s.points),
                    });
                    $id('cphRedo').disabled = false;
                }
                for (let i = myOrder.length - 1; i >= 0; i--) if (myOrder[i].uid === uidGone) myOrder.splice(i, 1);
                const goneIds = new Set(ids);
                strokes = strokes.filter((s) => !(goneIds.has(s.id) || (uidGone && s.uid === uidGone)));
                paintAll();
            } catch (e) { if (window.toast) toast(e.message, 'error'); }
        });
        $id('cphRedo').addEventListener('click', () => {
            const s = redoStack.pop();
            $id('cphRedo').disabled = !redoStack.length;
            if (!s) return;
            // Redo re-posts under a fresh id — the same way the map re-adds a
            // shape — so the room sees it arrive like any other stroke.
            const mine = { id: null, uid: uid(), mode: s.mode, color: s.color, width: s.width, points: s.points, text: s.text, userId: ME };
            strokes.push(mine); myOrder.push(mine);
            paintAll();
            pushStroke({ points: s.points, mode: s.mode, color: s.color, width: s.width, text: s.text, uid: mine.uid })
                .then((id) => { if (id) mine.id = id; });
        });
    }

    /* ---------- clear / swap / sources ---------- */
    function bindActions() {
        $id('cphClear').addEventListener('click', async () => {
            if (!strokes.length) { if (window.toast) toast('Nothing drawn yet.'); return; }
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Clear the strokes?', message: 'Every pen mark on this photo goes, for the whole team. The photo itself stays.', confirmText: 'Clear' })
                : confirm('Clear all strokes for the team?');
            if (!ok) return;
            applyClear();
            api(`${U.push}?scheduleId=${SID}`, { method: 'POST', body: { type: 'clear' } }).catch(() => {});
            myOrder.length = 0; redoStack = []; $id('cphRedo').disabled = true;
        });
        /* The button opens the sheet; the sheet's rows do what the three
           chips used to. Each closes it first, so the picker — a sheet of
           its own — never opens underneath this one. */
        $id('cphAddBtn').addEventListener('click', () => window.openSheet?.('cphSourceSheet'));
        $id('cphPickBtn').addEventListener('click', () => {
            window.closeSheet?.('cphSourceSheet');
            if (typeof window.smPickMedia !== 'function') { if (window.toast) toast('The gallery picker is not available here.', 'error'); return; }
            window.smPickMedia({
                scheduleId: SID, kinds: 'image', title: 'Choose the photo to draw on',
                onPick: (item) => {
                    if (!item || !item.path) return;
                    setPhoto({ path: item.path });
                },
            });
        });
        $id('cphUploadBtn').addEventListener('click', () => {
            window.closeSheet?.('cphSourceSheet');
            $id('cphUploadInput').click();
        });
        // Optional: an account without the camera module has no such row.
        $id('cphCameraBtn')?.addEventListener('click', () => {
            window.closeSheet?.('cphSourceSheet');
            $id('cphCameraInput').click();
        });
        ['cphUploadInput', 'cphCameraInput'].forEach((idn) => {
            $id(idn).addEventListener('change', (e) => {
                const f = e.target.files && e.target.files[0];
                e.target.value = '';
                if (f) setPhoto({ file: f });
            });
        });
    }
    async function setPhoto(src) {
        const busy = window.smBusy ? smBusy('Putting the photo up…') : null;
        try {
            let r;
            if (src.file) {
                const fd = new FormData();
                fd.append('photo', src.file);
                r = await api(`${U.set}?scheduleId=${SID}`, { method: 'POST', body: fd });
            } else {
                r = await api(`${U.set}?scheduleId=${SID}`, { method: 'POST', body: { path: src.path } });
            }
            const d = r.data || {};
            applyPhoto(d.url, d.gen);
            myOrder.length = 0; redoStack = []; $id('cphRedo').disabled = true;
        } catch (e) { if (window.toast) toast(e.message, 'error'); }
        // close(), which is what smBusy actually hands back. This said done()
        // with optional chaining, which is how a wrong method name becomes a
        // spinner that never leaves instead of an error that names itself.
        finally { busy?.close?.(); }
    }

    /* ---------- tools, colour, width ---------- */
    const SWATCHES = ['#f5c518', '#ef4444', '#3b82f6', '#22c55e', '#a855f7', '#f97316', '#ffffff', '#111827'];
    function bindTools() {
        document.querySelectorAll('[data-cph-tool]').forEach((b) => b.addEventListener('click', () => {
            tool = b.getAttribute('data-cph-tool');
            document.querySelectorAll('[data-cph-tool]').forEach((x) => x.classList.toggle('is-active', x === b));
            $id('cphBox').classList.toggle('is-move', tool === 'move');
        }));
        const host = $id('cphSwatches');
        SWATCHES.forEach((c) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'cph-swatch' + (c === color ? ' is-active' : '');
            b.style.background = c;
            b.addEventListener('click', () => {
                color = c;
                $id('cphColorDot').style.background = c;
                host.querySelectorAll('.cph-swatch').forEach((x) => x.classList.toggle('is-active', x === b));
                window.closeSheet?.('cphColorSheet');
            });
            host.appendChild(b);
        });
        $id('cphColor').addEventListener('click', () => window.openSheet?.('cphColorSheet'));
        $id('cphSize').addEventListener('click', () => window.openSheet?.('cphSizeSheet'));
        document.querySelectorAll('[data-cph-width]').forEach((b) => b.addEventListener('click', () => {
            width = parseInt(b.getAttribute('data-cph-width'), 10) || 5;
            document.querySelectorAll('[data-cph-width]').forEach((x) => x.classList.toggle('is-active', x === b));
            window.closeSheet?.('cphSizeSheet');
        }));
    }

    /* ---------- saving ---------- */
    function bindSave() {
        $id('cphSaveBtn').addEventListener('click', async () => {
            $id('cphSaveTitle').value = '';
            $id('cphSaveDesc').value = '';
            window.openSheet?.('cphSaveSheet');
            // The album shelf, fetched when the sheet opens so it is current.
            try {
                const r = await api(`${U.state}?scheduleId=${SID}&after=${lastId}&albums=1`);
                const sel = $id('cphAlbumSel');
                sel.innerHTML = (r.data.albums || [])
                    .map((a) => `<option value="${a.id}">${window.escapeHtml ? escapeHtml(a.title) : String(a.title).replace(/[<>&"]/g, '')}</option>`)
                    .join('');
            } catch (_) { /* the two other destinations still work */ }
        });
        document.querySelectorAll('input[name="cphDest"]').forEach((rb) => rb.addEventListener('change', () => {
            $id('cphAlbumSel').classList.toggle('hidden', rb.value !== 'album' || !rb.checked);
        }));
        $id('cphSaveGo').addEventListener('click', async () => {
            const title = $id('cphSaveTitle').value.trim();
            if (!title) { if (window.toast) toast('Give the image a name first.', 'error'); window.smFocus?.($id('cphSaveTitle')); return; }
            const dest = document.querySelector('input[name="cphDest"]:checked')?.value || 'note';
            const albumId = dest === 'album' ? parseInt($id('cphAlbumSel').value, 10) : null;
            if (dest === 'album' && !albumId) { if (window.toast) toast('Pick the album.', 'error'); return; }
            const image = composeImage();
            if (!image) { if (window.toast) toast('Could not read the photo back — try re-opening the tab.', 'error'); return; }
            const btn = $id('cphSaveGo');
            btn.disabled = true;
            try {
                const r = await api(`${U.save}?scheduleId=${SID}`, { method: 'POST', body: {
                    image, dest, albumId,
                    title, description: $id('cphSaveDesc').value.trim(),
                } });
                window.closeSheet?.('cphSaveSheet');
                if (window.toast) toast((r && r.message) || 'Saved.', 'success');
            } catch (e) { if (window.toast) toast(e.message, 'error'); }
            finally { btn.disabled = false; }
        });
    }
    /* The photo at its own full size with every stroke scaled onto it. The
       strokes render on a transparent layer first so the eraser can knock ink
       out without ever touching the photograph. */
    function composeImage() {
        const im = img();
        const iw = im.naturalWidth, ih = im.naturalHeight;
        if (!iw || !ih) return null;
        const cap = 2600;                       // phones choke composing 4K
        const s = Math.min(1, cap / Math.max(iw, ih));
        const w = Math.round(iw * s), h = Math.round(ih * s);
        try {
            const layer = document.createElement('canvas');
            layer.width = w; layer.height = h;
            const lg = layer.getContext('2d');
            const r = { dx: 0, dy: 0, dw: w, dh: h };
            strokes.forEach((st) => paintStroke(lg, st, r));
            const out = document.createElement('canvas');
            out.width = w; out.height = h;
            const og = out.getContext('2d');
            og.drawImage(im, 0, 0, w, h);
            og.drawImage(layer, 0, 0);
            return out.toDataURL('image/jpeg', 0.92);
        } catch (_) { return null; }            // a tainted canvas says no here
    }

    /* ---------- boot ---------- */
    window.initCollabPhoto = function () {
        if (booted) { paintAll(); return; }
        booted = true;
        bindCanvas(); bindText(); bindHistory(); bindActions(); bindTools(); bindSave();
        api(`${U.state}?scheduleId=${SID}&after=0`).then((r) => {
            const d = r.data || {};
            gen = d.gen || ''; canSave = !!d.canSave; syncSave();
            if (d.photo) {
                photoUrl = d.photo.url;
                showStage(true);
                loadPhoto(photoUrl);
            }
            (d.events || []).forEach(applyStroke);
            startLive();
        }).catch(() => { startLive(); });
    };
})();
</script>
