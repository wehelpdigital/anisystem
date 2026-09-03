{{-- The Gallery's chrome: the shelf picker, the panes, the search and
     filter row, the tiles and the Team box cards.

     Lives here because there are two galleries wearing it — a season's
     own, and the Global Gallery that asks the same question across every
     season. A grower who has learned where photos live in one should not
     have to learn a second place, which only holds if there is one
     stylesheet rather than two that drift. --}}
<style>
    .ga-top { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .9rem; }
    .ga-new { display: inline-flex; align-items: center; gap: .4rem; height: 2.75rem; padding: 0 1rem;
        border-radius: .8rem; background: #4a7c2a; color: #fff; font-size: .85rem; font-weight: 800;
        cursor: pointer; box-shadow: 0 8px 18px -12px rgb(61 104 35 / .9); }
    .ga-new:hover { background: #3d6823; }
    .ga-new svg { width: 1.05rem; height: 1.05rem; }

    .ga-album { border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white);
        margin-bottom: .9rem; overflow: hidden; }
    .ga-head { display: flex; align-items: flex-start; gap: .6rem; padding: .8rem .9rem; }
    .ga-title { font-size: .98rem; font-weight: 800; color: var(--color-gray-900); }
    .ga-desc { font-size: .8rem; line-height: 1.45; color: var(--tl-text-muted, #6b7280); margin-top: .15rem; }
    .ga-count { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #3d6823; background: #e4efd4; border-radius: 999px; padding: .15rem .5rem; }
    .ga-acts { margin-left: auto; display: flex; gap: .25rem; flex: 0 0 auto; }
    .ga-act { width: 2rem; height: 2rem; border-radius: .55rem; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-gray-500); background: var(--color-gray-50); cursor: pointer; }
    .ga-act:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
    .ga-act.is-danger:hover { background: #fee2e2; color: #b91c1c; }
    .ga-act svg { width: 1rem; height: 1rem; }

    .ga-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr)); gap: .5rem;
        padding: 0 .9rem .9rem; }
    .ga-cell { position: relative; aspect-ratio: 1; border-radius: .7rem; overflow: hidden; background: #0b1220;
        cursor: pointer; }
    .ga-cell img, .ga-cell video { position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: cover; display: block;
        opacity: 0; transition: opacity .28s ease; }
    .ga-cell img.is-loaded, .ga-cell video.is-loaded { opacity: 1; }
    /* While the picture decodes the square shimmers instead of sitting black —
       the same sweep the note thumbnails wear (.nm in the lightbox partial).
       The cell background is dark in both themes, so one white sweep serves
       light and dark alike. .ga-shot is the All-shelf twin of this cell. */
    .ga-cell::before, .ga-shot::before { content: ''; position: absolute; inset: 0;
        background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.14) 50%, rgba(255,255,255,0) 80%);
        background-size: 220% 100%; animation: gaShimmer 1.15s linear infinite; pointer-events: none; }
    /* .ga-noshot is the poster that 404'd under a clip that may be fine:
       not "File missing" (that is is-gone, and it would slander the video),
       but the sweep must stop — a shimmer with nothing coming reads as
       "still loading" forever. The play disc stands on the dark square. */
    .ga-cell:has(img.is-loaded)::before, .ga-cell:has(video.is-loaded)::before, .ga-cell.is-gone::before,
    .ga-shot:has(img.is-loaded)::before, .ga-shot:has(video.is-loaded)::before,
    .ga-shot.is-gone::before, .ga-shot.ga-noshot::before, .ga-shot:empty::before { display: none; }
    @keyframes gaShimmer { from { background-position: 220% 0; } to { background-position: -220% 0; } }
    /* Still a loader, just a quiet one: a faint standing sheen rather than a
       moving sweep, so reduced motion is not a return to the black square. */
    @media (prefers-reduced-motion: reduce) {
        .ga-cell::before, .ga-shot::before { animation: none; background: rgb(255 255 255 / .07); }
    }
    /* So a still frame is not mistaken for a photo. */
    /* An image the room drew together wears its provenance. Top-left, because
       the selection tick owns the other corner and the caption owns the foot. */
    .ga-teamchip { position: absolute; top: .3rem; right: .3rem; z-index: 2;
        font-size: .56rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase;
        color: #fff; background: rgb(61 104 35 / .92); border-radius: .5rem; padding: .12rem .34rem;
        pointer-events: none; }
    .ga-vid { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: 2rem; height: 2rem; border-radius: 999px; background: rgb(0 0 0 / .55); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .8rem; pointer-events: none; }
    .ga-cell.is-gone::after { content: 'File missing'; position: absolute; inset: 0; display: flex;
        align-items: center; justify-content: center; font-size: .66rem; font-weight: 700; color: #94a3b8; }
    /* Picking is a mode: the tick is always there to start it, and once one
       picture is chosen the whole grid becomes a checklist. */
    /* The tick on an album picture. Owns this name — anything else wanting
       to be called "pick" gets absolutely positioned into this corner. */
    .ga-pick { position: absolute; top: .3rem; left: .3rem; width: 1.5rem; height: 1.5rem; border-radius: 999px;
        background: rgb(17 24 39 / .55); border: 2px solid rgb(255 255 255 / .8); display: inline-flex;
        align-items: center; justify-content: center; color: transparent; }
    .ga-cell.is-picked .ga-pick { background: #4a7c2a; border-color: #fff; color: #fff; }
    .ga-cell.is-picked { outline: 3px solid #4a7c2a; outline-offset: -3px; }
    .ga-pick svg { width: .9rem; height: .9rem; }
    /* What the capture called this one picture, on the picture. A tile with
       no name is a square of pixels, and somebody typed that name for a
       reason. The description follows it when there is a line to spare. */
    .ga-cap { position: absolute; left: 0; right: 0; bottom: 0; padding: 1.1rem .4rem .35rem;
        background: linear-gradient(to top, rgb(0 0 0 / .8), rgb(0 0 0 / 0));
        color: #fff; pointer-events: none; }
    .ga-cap b { display: block; font-size: .68rem; font-weight: 800; line-height: 1.3;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ga-cap i { display: block; font-style: normal; font-size: .62rem; line-height: 1.35; opacity: .8;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* The strip the Gallery hangs on the shared lightbox — see
       lightboxCaption() for why it is hung rather than built in. */
    .ga-lb-cap { position: fixed; left: 50%; transform: translateX(-50%); bottom: 1.1rem; z-index: 2;
        max-width: min(90vw, 40rem); padding: .5rem .9rem; border-radius: .8rem; text-align: center;
        background: rgb(0 0 0 / .62); backdrop-filter: blur(3px); color: #fff; pointer-events: none; }
    .ga-lb-cap[hidden] { display: none; }
    .ga-lb-cap b { display: block; font-size: .88rem; font-weight: 800; line-height: 1.35; }
    .ga-lb-cap i { display: block; font-style: normal; font-size: .78rem; line-height: 1.45;
        margin-top: .1rem; opacity: .82; }
    /* The rename pencil the Gallery hangs beside the lightbox's download
       button — the top-right row runs close 1rem / download 4.25 / AI 7.5 /
       this 10.75, the same 3.25rem rhythm the other three keep. Only media
       carrying a gallery image id ever shows it; see lightboxRename(). */
    .ga-lb-rename { position: fixed; top: 1rem; right: 10.75rem; width: 2.75rem; height: 2.75rem;
        border-radius: 999px; background: rgb(255 255 255 / .16); color: #fff; display: inline-flex;
        align-items: center; justify-content: center; cursor: pointer; z-index: 2;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ga-lb-rename:hover { background: #4a7c2a; }
    .ga-lb-rename svg { width: 1.2rem; height: 1.2rem; }
    /* Explicit: the class's inline-flex outranks the UA's [hidden] rule. */
    .ga-lb-rename[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) { .ga-lb-rename { transition: none; } }
    /* Sheets live at z-50 and the lightbox at z-160, so the rename sheet
       would open UNDERNEATH the picture it renames. While it is up, it and
       the shared backdrop step over the lightbox — the same scoped bump the
       draw pad gives its confirm sheet. The class comes off when the sheet
       closes, so nothing else ever stacks differently. */
    html.ga-naming .sheet-backdrop.is-open { z-index: 170; }
    html.ga-naming #gaNameSheet { z-index: 180; }
    .ga-empty { padding: 0 .9rem 1rem; font-size: .8rem; color: var(--color-gray-400); }

    /* The picking bar. It appears IN the album being picked from, right under
       its header, because that is where the eyes already are - a dark pill at
       the bottom of the viewport read as an unrelated popup, and on a phone it
       sat over the very pictures being chosen. It moves between albums with
       the most recent pick, while the actions always cover everything picked. */
    .ga-bar { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap;
        margin: 0 .9rem .6rem; padding: .5rem .6rem; border-radius: .7rem;
        background: var(--color-brand-50); border: 1px solid var(--color-brand-200, #cfe0bd);
        animation: gaBarIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes gaBarIn { from { opacity: 0; transform: translateY(-.35rem); } }
    @media (prefers-reduced-motion: reduce) { .ga-bar { animation: none; } }
    .ga-bar[hidden] { display: none; }
    .ga-bar-n { font-size: .78rem; font-weight: 800; color: #3d6823; margin-right: auto; }
    .ga-bar button { font-size: .76rem; font-weight: 700; padding: .38rem .7rem; border-radius: .55rem;
        background: var(--color-white); color: var(--color-gray-700);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .ga-bar button:hover { background: var(--color-gray-50); }
    .ga-bar .ga-bar-del { color: #b91c1c; border-color: #fecaca; }
    .ga-bar .ga-bar-del:hover { background: #fef2f2; }
    html.dark .ga-bar { background: #1b2716; border-color: #33471f; }
    html.dark .ga-bar-n { color: #cde3b3; }
    html.dark .ga-bar button { background: #151b12; color: #cdd8c0; border-color: #2b3a1c; }
    .ga-bar .ga-bar-del:hover { background: #b91c1c; color: #fff; }
    @media (prefers-reduced-motion: reduce) { .ga-bar { animation: none; } .ga-cell img { transition: none; } }

    /* ---- tabs, and the "everything" shelf ---- */
    /* The shelf picker: one button that says where you are.
     *
     * It sticks under the module's own toolbar rather than scrolling away
     * beneath it — which is what it was doing, and a control you cannot see
     * is a control you have to remember. `top` matches the toolbar's height
     * on each breakpoint; the shell's sticky bar sits at z-20, so this rides
     * just under it and over the shelf. */
    .ga-shelfbar { position: sticky; top: 3.5rem; z-index: 15; margin-bottom: .8rem;
        padding: .45rem 0 .5rem; background: var(--color-gray-50); }
    @media (min-width: 768px) { .ga-shelfbar { top: 4rem; } }
    /* Inside the Activities shell the toolbar is already sticky below the app
       bar, so the picker sits under both. */
    /* App header + the shell's toolbar, measured from the toolbar, not
       guessed. The toolbar regained .5rem of bottom padding (buttons were
       sitting on its divider line), so these come up by the same half rem —
       the margin below it does not count, because when the page is scrolled
       the shelf bar sticks against the toolbar's edge, not against the gap
       after it. */
    body:has(#activitiesRoot) .ga-shelfbar { top: 6.65rem; }
    @media (min-width: 768px) { body:has(#activitiesRoot) .ga-shelfbar { top: 7.45rem; } }
    /* In the Collab Room the app bar is hidden entirely. */
    html.collab-embed .ga-shelfbar { top: 0; }
    html.dark .ga-shelfbar { background: #10160e; }
    /* Nothing of its own: it wears .btn.btn-white.btn-sm, the same as the
       Modules and Tools buttons it sits beside. Only the count needs a word,
       and only to keep it from reading as part of the name. */
    #gaTabBtn .ga-n { font-weight: 700; color: var(--color-gray-400); opacity: 1; }
    html.dark #gaTabBtn .ga-n { color: #7d8f6e; }
    #gaTabBtn svg:last-child { color: var(--color-gray-400); }
    /* No overflow: hidden here. On a flex item that switches the automatic
       minimum size from `auto` to 0, which once let the label shrink away to
       nothing and leave a button that was only an icon. */
    #gaTabNow { white-space: nowrap; }

    .ga-modal { position: fixed; inset: 0; z-index: 120; display: flex; align-items: flex-end; justify-content: center; }
    @media (min-width: 640px) { .ga-modal { align-items: center; padding: 1.5rem; } }
    .ga-modal.hidden { display: none; }
    .ga-modal-back { position: absolute; inset: 0; background: rgb(10 14 20 / .55); opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ga-modal.is-open .ga-modal-back { opacity: 1; }
    .ga-modal-card { position: relative; width: 100%; max-width: 24rem; background: var(--color-white);
        border-radius: 1rem 1rem 0 0; overflow: hidden; box-shadow: var(--shadow-card-lg);
        transform: translateY(1.5rem); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 640px) { .ga-modal-card { border-radius: 1rem; } }
    .ga-modal.is-open .ga-modal-card { transform: none; opacity: 1; }
    .ga-modal-head { display: flex; align-items: center; justify-content: space-between;
        padding: .8rem 1rem; border-bottom: 1px solid var(--color-gray-100); }
    .ga-modal-body { padding: .5rem; display: grid; gap: .2rem;
        padding-bottom: calc(.5rem + env(safe-area-inset-bottom, 0px)); }
    .ga-opt { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .6rem .7rem; border-radius: .7rem; cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ga-opt:hover { background: var(--color-gray-50); }
    .ga-opt.is-on { background: #f4f9ee; }
    .ga-opt-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
    .ga-opt-txt b { font-size: .86rem; font-weight: 700; color: var(--color-gray-900); }
    .ga-opt-txt i { font-style: normal; font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .ga-opt-tick { flex: none; width: 1.1rem; height: 1.1rem; color: #4a7c2a; opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ga-opt-tick svg { width: 100%; height: 100%; }
    .ga-opt.is-on .ga-opt-tick { opacity: 1; }
    html.dark .ga-modal-card { background: #151b12; }
    html.dark .ga-modal-head { border-color: #2b3a1c; }
    html.dark .ga-opt:hover { background: rgb(255 255 255 / .05); }
    html.dark .ga-opt.is-on { background: #24301a; }
    html.dark .ga-opt-txt b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .ga-modal-back, .ga-modal-card, .ga-opt, .ga-opt-tick { transition: none; }
    }
    /* The count that rides on the picker and on each shelf in the sheet. */
    .ga-n { font-size: .7rem; opacity: .75; font-weight: 800; }
    .ga-pane[hidden] { display: none; }
    /* A shelf arrives rather than appearing. Short and slight — the point is
       to say "this is a different shelf now", not to make anyone wait. */
    .ga-pane.is-in { animation: gaPaneIn .3s cubic-bezier(.22,1,.36,1) both; }
    @keyframes gaPaneIn { from { opacity: 0; transform: translateY(.5rem); } }
    @media (prefers-reduced-motion: reduce) { .ga-pane.is-in { animation: none; } }
    .ga-tools { display: flex; gap: .5rem; align-items: center; margin-bottom: .7rem; flex-wrap: wrap; }
    .ga-search { position: relative; flex: 1 1 12rem; }
    .ga-search input { width: 100%; padding: .5rem .7rem .5rem 2.1rem; border-radius: .7rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white); font-size: .85rem; }
    .ga-search svg { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%);
        width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .ga-filters { display: flex; gap: .3rem; flex-wrap: wrap; }
    .ga-filter { padding: .3rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700;
        border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-600); cursor: pointer; }
    .ga-filter.is-on { background: #eaf4dd; border-color: #a8cc7e; color: #3d6823; }

    .ga-all { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: .7rem; }
    /* Every tile is the same height whatever its caption does, so the shelf
       reads as a shelf. The picture is a fixed square; only the words below
       it vary, and they are given room for two lines either way. */
    .ga-wrap { position: relative; height: 100%; }
    .ga-item { position: relative; height: 100%; display: flex; flex-direction: column;
        border-radius: .8rem; overflow: hidden; background: var(--color-white);
        border: 1px solid var(--color-gray-200); text-align: left; width: 100%;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1),
            border-color .28s cubic-bezier(.22,1,.36,1); }
    .ga-item:hover { transform: translateY(-2px); border-color: #a8cc7e;
        box-shadow: 0 12px 26px -18px rgb(0 0 0 / .5); }
    /* The square is the law, not a preference: with the media in normal
       flow, a tall photo's intrinsic height overrode aspect-ratio and every
       card wore a different thumbnail. Pinned absolute, the box keeps its
       ratio and the picture fills whatever it is given. */
    .ga-shot { position: relative; aspect-ratio: 1; flex: none; background: #0b1220; overflow: hidden; }
    .ga-shot img, .ga-shot video { position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: cover; display: block;
        opacity: 0; transition: opacity .28s ease; }
    .ga-shot img.is-loaded, .ga-shot video.is-loaded { opacity: 1; }
    /* The bin rides on the tile rather than inside it: the tile is already a
       button, and a button inside a button is not a thing. Both are the full
       height of the cell, so "on the tile" is where it lands. */
    .ga-del { position: absolute; right: .4rem; top: .4rem; z-index: 2; width: 1.8rem; height: 1.8rem;
        display: flex; align-items: center; justify-content: center; border-radius: .55rem; cursor: pointer;
        background: rgb(17 24 39 / .62); color: #fff; opacity: 0;
        backdrop-filter: blur(2px);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ga-del svg { width: .9rem; height: .9rem; }
    .ga-wrap:hover .ga-del, .ga-del:focus-visible { opacity: 1; }
    .ga-del:hover { background: #dc2626; }
    /* No hover on a touch screen, so there it simply shows. */
    @media (hover: none) { .ga-del { opacity: .9; } }
    @media (prefers-reduced-motion: reduce) { .ga-del { transition: none; } }
    /* A shot with nothing in it says so, whether the file 404'd or the tile
       was drawn with no picture to put in it. */
    .ga-shot.is-gone::after, .ga-shot:empty::after { content: 'File missing'; position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: .66rem; font-weight: 700; color: #94a3b8; }
    .ga-kind { position: absolute; left: .35rem; top: .35rem; padding: .1rem .4rem; border-radius: 999px;
        font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        background: rgb(17 24 39 / .65); color: #fff; }
    .ga-kind.is-drawing { background: rgb(217 119 6 / .9); }
    .ga-kind.is-map { background: rgb(37 99 235 / .9); }
    .ga-kind.is-video { background: rgb(190 24 93 / .9); }
    /* A disc, not a bare glyph: over a bright frame a white triangle with a
       shadow is a smudge, and this has to read as "this one moves". */
    .ga-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        pointer-events: none; }
    .ga-play svg { width: 1.15rem; height: 1.15rem; color: #fff; margin-left: .1rem; }
    .ga-play::before { content: ''; position: absolute; width: 2.5rem; height: 2.5rem; border-radius: 999px;
        background: rgb(17 24 39 / .55); backdrop-filter: blur(2px); }
    .ga-play svg { position: relative; }
    .ga-info { padding: .45rem .55rem .55rem; display: flex; flex-direction: column; flex: 1 1 auto; }
    .ga-it { font-size: .76rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ga-is { font-size: .64rem; font-weight: 600; color: var(--color-gray-400); margin-top: auto; padding-top: .2rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ga-none { text-align: center; padding: 2.5rem 1rem; color: var(--color-gray-400); font-size: .85rem; }
    /* Where the next screenful is asked for. It spans the grid so it sits
       below the last row rather than pretending to be a tile. */
    .ga-more, .tb-grid > .ga-more { grid-column: 1 / -1; display: flex; align-items: center;
        justify-content: center; padding: 1.2rem 0; }
    .ga-more-spin { width: 1.2rem; height: 1.2rem; border-radius: 999px;
        border: 2.5px solid var(--color-gray-200); border-top-color: #4a7c2a;
        animation: gaMoreSpin .7s linear infinite; }
    @keyframes gaMoreSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .ga-more-spin { animation-duration: 1.6s; } }

    html.dark .ga-item { background: #151b12; border-color: #2b3a1c; }
    html.dark .ga-it { color: #e8efe1; }
    html.dark .ga-search input, html.dark .ga-filter { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }

    html.dark .ga-album { background: #151b12; border-color: #2b3a1c; }
    html.dark .ga-title { color: #e8efe1; }
    html.dark .ga-count { background: rgb(61 104 35 / .35); color: #a8cc7e; }
    html.dark .ga-act { background: rgb(255 255 255 / .05); color: #cdd8c0; }
</style>
<style>
    .tb-say { font-size: .8rem; line-height: 1.6; color: var(--color-gray-500); margin-bottom: .6rem; }
    .tb-filters { margin-bottom: .7rem; }
    .tb-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); gap: .7rem; }
    .tb-card { display: flex; flex-direction: column; border-radius: .8rem; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .tb-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -18px rgb(0 0 0 / .5); }
    .tb-shot { position: relative; aspect-ratio: 16/10; background: #0b1220; overflow: hidden; }
    .tb-shot img, .tb-shot video { position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: cover; display: block; }
    .tb-kind { position: absolute; left: .4rem; top: .4rem; padding: .1rem .45rem; border-radius: 999px;
        font-size: .6rem; font-weight: 800; letter-spacing: .02em; text-transform: uppercase;
        background: rgb(0 0 0 / .6); color: #fff; }
    .tb-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        pointer-events: none; }
    .tb-play span { width: 2.6rem; height: 2.6rem; border-radius: 999px; background: rgb(0 0 0 / .55);
        display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; }
    .tb-save { position: absolute; right: .4rem; top: .4rem; z-index: 2; width: 1.8rem; height: 1.8rem;
        display: flex; align-items: center; justify-content: center; border-radius: .55rem;
        background: rgb(17 24 39 / .62); color: #fff; backdrop-filter: blur(2px);
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .tb-save svg { width: .9rem; height: .9rem; }
    .tb-save:hover { background: #4a7c2a; }
    @media (prefers-reduced-motion: reduce) { .tb-save { transition: none; } }
    .tb-body { padding: .55rem .65rem .65rem; display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
    .tb-title { font-size: .84rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tb-note { font-size: .72rem; color: var(--color-gray-500); line-height: 1.45;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tb-meta { margin-top: .15rem; font-size: .66rem; font-weight: 600; color: var(--color-gray-400); }
    html.dark .tb-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .tb-title { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .tb-card { transition: none; } }
</style>
