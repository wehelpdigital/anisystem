@extends('layouts.app')

@section('title', 'Cropping Schedules')
@section('page-title', 'Cropping Schedules')
@section('page-subtitle', 'Plan and manage your seasons')
@section('help-key', 'schedules')

@push('head')
    <style>
        /* Deleting a season asks for the word, so the dialog is a page of its
           own rather than a toast you can dismiss by leaning on the screen. */
        .del-modal { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center;
            justify-content: center; padding: 1rem; background: rgb(15 23 42 / .55);
            animation: delIn .18s ease both; }
        .del-card { width: 100%; max-width: 26rem; background: var(--color-white); border-radius: 1rem;
            padding: 1.1rem 1.15rem 1.15rem; box-shadow: 0 24px 60px -24px rgb(0 0 0 / .5);
            animation: delUp .28s cubic-bezier(.22,1,.36,1) both; }
        .del-title { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800;
            color: var(--color-gray-900); margin-bottom: .4rem; }
        .del-text { font-size: .85rem; line-height: 1.55; color: var(--color-gray-600); margin-bottom: .9rem; }
        .del-label { display: block; font-size: .78rem; font-weight: 700; color: var(--color-gray-700);
            margin-bottom: .35rem; }
        .del-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem; }
        .del-actions .btn-danger { background: #dc2626; color: #fff; }
        .del-actions .btn-danger:hover { background: #b91c1c; }
        .del-actions .btn-danger:disabled { opacity: .45; cursor: not-allowed; background: #dc2626; }
        @keyframes delIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes delUp { from { transform: translateY(10px); opacity: 0; } to { transform: none; opacity: 1; } }
        html.dark .del-card { background: #141a10; }
        html.dark .del-title { color: #e6eddd; }
        html.dark .del-text { color: #bcc9b0; }
        html.dark .del-label { color: #d7e3cb; }
        @media (prefers-reduced-motion: reduce) { .del-modal, .del-card { animation: none; } }

        /* ---- the greeting card: a hello with the day's answer in it. The
           badge wears the hour, the line under the name says what today
           actually holds, and the numbers stand as small labelled tiles —
           calm neutrals, so the season covers below stay the picture. ---- */
        .sch-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .85rem 1.25rem; padding: 1.05rem 1.25rem; margin-bottom: 1rem; border-radius: 1.1rem;
            background: var(--color-white); border: 1px solid var(--color-gray-200); position: relative; overflow: hidden; }
        /* One restrained accent: a hairline of field-green along the top. */
        .sch-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
            background: linear-gradient(90deg, #6b9f3d, #b8d38e 55%, transparent); }
        .sch-hero-left { display: flex; align-items: center; gap: .85rem; min-width: 0; }
        /* A drawn mark, not an emoji: platform emoji arrive as square little
           pictures and sat in the round badge like a photo in a porthole.
           The stroke icons match every other icon in the app. */
        .sch-hero-emoji { width: 3rem; height: 3rem; border-radius: 999px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; }
        .sch-hero-emoji svg { width: 1.55rem; height: 1.55rem; }
        .tod-morning { background: linear-gradient(135deg, #fff7e0, #fbe6ae); color: #d97706; }
        .tod-afternoon { background: linear-gradient(135deg, #e8f4fd, #cde7fa); color: #0284c7; }
        .tod-evening { background: linear-gradient(135deg, #e9e7fb, #d5d2f2); color: #6d28d9; }
        html.dark .tod-morning { color: #fbbf24; }
        html.dark .tod-afternoon { color: #7dd3fc; }
        html.dark .tod-evening { color: #c4b5fd; }
        .sch-hero-h { font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .sch-hero-p { font-size: .82rem; color: var(--color-gray-500); margin-top: .15rem; }
        .sch-hero-p b { font-weight: 700; color: var(--color-gray-700); }
        .sch-hero-cta { display: inline-flex; align-items: center; gap: .3rem; margin-top: .4rem;
            font-size: .78rem; font-weight: 700; color: #3d6823; }
        .sch-hero-cta svg { width: .85rem; height: .85rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .sch-hero-cta:hover svg { transform: translateX(3px); }
        @media (prefers-reduced-motion: reduce) { .sch-hero-cta svg { transition: none; } }
        .sch-hero-stats { display: flex; gap: .45rem; flex-wrap: wrap; }
        .sch-stat { display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-width: 3.9rem; padding: .45rem .65rem; border-radius: .85rem;
            background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
        .sch-stat b { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.15; }
        .sch-stat i { font-style: normal; font-size: .6rem; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--color-gray-400); }
        /* Today's tile is the one that matters — it gets the accent. */
        .sch-stat.is-today { background: #f0f7e8; border-color: #cfe3b8; }
        .sch-stat.is-today b { color: #3d6823; }
        .sch-stat.is-today i { color: #6b9f3d; }
        html.dark .sch-hero { background: #151b12; border-color: #2b3a1c; }
        html.dark .sch-hero-h { color: #e8efe1; }
        html.dark .sch-hero-p { color: #a8bd93; }
        html.dark .sch-hero-p b { color: #cdd8c0; }
        html.dark .sch-hero-cta { color: #a5c97e; }
        html.dark .tod-morning, html.dark .tod-afternoon, html.dark .tod-evening { background: rgb(255 255 255 / .07); }
        html.dark .sch-stat { background: rgb(255 255 255 / .05); border-color: #2b3a1c; }
        html.dark .sch-stat b { color: #e8efe1; }
        html.dark .sch-stat.is-today { background: rgb(61 104 35 / .22); border-color: #3f5626; }
        html.dark .sch-stat.is-today b { color: #bfe19a; }
        @media (max-width: 640px) {
            /* The tiles take the second row, evenly, instead of ragging. */
            .sch-hero-stats { width: 100%; }
            .sch-hero-stats .sch-stat { flex: 1 1 0; min-width: 0; }
        }

        /* ---- the three quick doors, one to a row ------------------------
           Shaped like the Hub's module tiles, because they do the same job:
           a tinted icon you recognise before you read, a name, and a line
           saying why you would tap it. Across a phone in a single strip
           there was room for none of that. ---- */
        .qa-stack { display: grid; gap: .5rem; }
        .qa-tile { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
            padding: .7rem .8rem; border-radius: .9rem; cursor: pointer; text-decoration: none;
            background: var(--color-white); border: 1px solid var(--color-gray-200);
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1),
                border-color .28s cubic-bezier(.22,1,.36,1); }
        .qa-tile:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -16px rgb(0 0 0 / .5); }
        .qa-tile .qa-ico { width: 2.6rem; height: 2.6rem; border-radius: .75rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center; }
        .qa-tile .qa-ico svg { width: 1.3rem; height: 1.3rem; }
        .qa-tile .qa-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
        .qa-tile .qa-txt b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .qa-tile .qa-txt i { font-style: normal; font-size: .72rem; font-weight: 500; line-height: 1.4;
            color: var(--color-gray-500); }
        .qa-go { width: .95rem; height: .95rem; flex: none; color: var(--color-gray-300);
            transition: transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .qa-tile:hover .qa-go { transform: translateX(2px); }
        .qa-notes:hover { border-color: #f0dcae; } .qa-notes:hover .qa-go { color: #b45309; }
        .qa-cap:hover { border-color: #cfe3b8; } .qa-cap:hover .qa-go { color: #3d6823; }
        .qa-rec:hover { border-color: #f3c4c4; } .qa-rec:hover .qa-go { color: #b91c1c; }
        html.dark .qa-tile { background: #151b12; border-color: #2b3a1c; }
        html.dark .qa-tile .qa-txt b { color: #e8efe1; }
        html.dark .qa-tile .qa-txt i { color: #93a684; }
        @media (prefers-reduced-motion: reduce) { .qa-tile, .qa-go { transition: none; } }

        /* The hues the three doors are told apart by, and the icon plate
           they sit on. Kept out of .qa-tile so the colour of a door and the
           shape of one stay separate things. */
        .qa-ico { border-radius: .75rem; }
        .qa-notes .qa-ico { background: #fdf6e6; color: #b45309; }
        .qa-cap .qa-ico { background: #eef6e6; color: #3d6823; }
        .qa-rec .qa-ico { background: #fdecec; color: #b91c1c; }
        html.dark .qa-notes .qa-ico { background: rgb(180 83 9 / .18); color: #e0b457; }
        html.dark .qa-cap .qa-ico { background: rgb(61 104 35 / .25); color: #a5c97e; }
        html.dark .qa-rec .qa-ico { background: rgb(185 28 28 / .2); color: #f0a3a3; }


        /* ---- the shelf's own controls: how it is arranged, and shutting
           all of it at once ---- */
        .sch-bar { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; }
        .sch-pill { display: inline-flex; align-items: center; gap: .35rem; flex: none;
            padding: .35rem .75rem; border-radius: 999px; cursor: pointer;
            font-size: .76rem; font-weight: 700; color: var(--color-gray-600);
            border: 1px solid var(--color-gray-200); background: var(--color-white);
            transition: border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .sch-pill svg { width: .85rem; height: .85rem; }
        .sch-pill:hover { border-color: #a8cc7e; color: #3d6823; }
        /* Says which order is on when it is not the default, so a shelf that
           looks oddly arranged explains itself without opening anything. */
        .sch-pill.is-set { border-color: #4a7c2a; color: #2f5a17; background: #f4f9ee; }
        .sch-pill-now { padding-left: .4rem; margin-left: .1rem; border-left: 1px solid currentColor;
            opacity: .75; font-weight: 600; }
        .sch-foldall { margin-left: auto; }
        html.dark .sch-pill { background: #151b12; border-color: #2b3a1c; color: #9fb08e; }
        html.dark .sch-pill.is-set { background: #24301a; border-color: #6ba33c; color: #d8f0be; }

        /* ---- the arrange sheet ---- */
        .sch-modal { position: fixed; inset: 0; z-index: 120; display: flex;
            align-items: flex-end; justify-content: center; }
        @media (min-width: 640px) { .sch-modal { align-items: center; padding: 1.5rem; } }
        .sch-modal.hidden { display: none; }
        .sch-modal-back { position: absolute; inset: 0; background: rgb(10 14 20 / .55);
            opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        .sch-modal.is-open .sch-modal-back { opacity: 1; }
        .sch-modal-card { position: relative; width: 100%; max-width: 24rem;
            background: var(--color-white); border-radius: 1rem 1rem 0 0; overflow: hidden;
            box-shadow: var(--shadow-card-lg); transform: translateY(1.5rem); opacity: 0;
            transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
        @media (min-width: 640px) { .sch-modal-card { border-radius: 1rem; } }
        .sch-modal.is-open .sch-modal-card { transform: none; opacity: 1; }
        .sch-modal-head { display: flex; align-items: center; justify-content: space-between;
            padding: .8rem 1rem; border-bottom: 1px solid var(--color-gray-100); }
        .sch-modal-body { padding: .5rem; display: grid; gap: .2rem;
            padding-bottom: calc(.5rem + env(safe-area-inset-bottom, 0px)); }
        .sch-opt { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem;
            border-radius: .7rem; text-decoration: none;
            transition: background .28s cubic-bezier(.22,1,.36,1); }
        .sch-opt:hover { background: var(--color-gray-50); }
        .sch-opt.is-on { background: #f4f9ee; }
        .sch-opt-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
        .sch-opt-txt b { font-size: .86rem; font-weight: 700; color: var(--color-gray-900); }
        .sch-opt-txt i { font-style: normal; font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
        .sch-opt-tick { flex: none; width: 1.1rem; height: 1.1rem; color: #4a7c2a; opacity: 0;
            transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        .sch-opt-tick svg { width: 100%; height: 100%; }
        .sch-opt.is-on .sch-opt-tick { opacity: 1; }
        /* The grid dims a touch while the next order is fetched — enough to
           say something is happening, not enough to flash. */
        #scheduleResults { transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        #scheduleResults.is-swapping { opacity: .45; pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { #scheduleResults { transition: none; } }

        .sch-modal-foot { display: flex; align-items: center; gap: .5rem; padding: .6rem .8rem;
            border-top: 1px solid var(--color-gray-100);
            padding-bottom: calc(.6rem + env(safe-area-inset-bottom, 0px)); }
        .sch-clear { padding: .35rem .7rem; border-radius: 999px; cursor: pointer;
            font-size: .76rem; font-weight: 700; color: var(--color-gray-500);
            transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .sch-clear:hover { color: #b91c1c; background: #fef2f2; }
        .sch-clear[disabled] { opacity: .4; pointer-events: none; }
        html.dark .sch-modal-foot { border-color: #2b3a1c; }
        html.dark .sch-modal-card { background: #151b12; }
        html.dark .sch-modal-head { border-color: #2b3a1c; }
        html.dark .sch-opt:hover { background: rgb(255 255 255 / .05); }
        html.dark .sch-opt.is-on { background: #24301a; }
        html.dark .sch-opt-txt b { color: #e8efe1; }
        @media (prefers-reduced-motion: reduce) {
            .sch-pill, .sch-modal-back, .sch-modal-card, .sch-opt, .sch-opt-tick { transition: none; }
        }

        /* ---- folding a card away -------------------------------------
           The cover stays: a folded season is still its name, its crops and
           its state, which is most of what you scan a shelf for. Only the
           working detail underneath goes. ---- */
        .se-cover[data-se-fold] { cursor: pointer; user-select: none; }
        /* After the status, not over it. Pinned to the corner it landed on
           top of the pill, which is the one thing on the cover that already
           had that corner. It is a flex item now, so the row makes room. */
        .se-chev { position: relative; z-index: 1; flex: none; width: 1.5rem; height: 1.5rem;
            display: flex; align-items: center; justify-content: center; border-radius: 999px;
            color: var(--color-gray-500); background: rgb(255 255 255 / .7); backdrop-filter: blur(2px);
            transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .se-cover:hover .se-chev { background: rgb(255 255 255 / .95); color: #3d6823; }
        .se-chev svg { width: .8rem; height: .8rem; }
        html.dark .se-chev { background: rgb(0 0 0 / .45); color: #d5dfc9; }
        .se-card.is-folded .se-chev { transform: rotate(-90deg); }
        /* Height animates, so folding reads as the card closing rather than
           the card vanishing. grid-template-rows does it without anyone
           having to measure the content first. */
        .se-body { display: block; }
        .se-card .se-fold-wrap { display: grid; grid-template-rows: 1fr;
            transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
        .se-card.is-folded .se-fold-wrap { grid-template-rows: 0fr; }
        .se-card .se-fold-wrap > * { min-height: 0; overflow: hidden; }
        .se-card.is-folded .se-body { padding-top: 0; padding-bottom: 0; }
        .se-card.is-folded { align-self: start; }
        @media (prefers-reduced-motion: reduce) {
            .se-chev, .se-card .se-fold-wrap { transition: none; }
        }

        /* ---- season cards: each schedule is a shelfful of ground, so the
           card leads with a field-toned cover, the crops growing on it, and
           where the season stands — before any chrome. ---- */
        .se-card { overflow: hidden; }
        /* One level row — crops, name and status share a line, vertically
           centred, instead of the pill floating a corner higher than the
           name it describes. */
        .se-cover { position: relative; height: 4.6rem; display: flex; align-items: center;
            gap: .6rem; padding: .55rem .8rem; }
        /* Soft, desaturated tints — the status is the weather over the field. */
        .se-cover-active    { background: linear-gradient(120deg, #eef6e6, #d9e9c8); }
        .se-cover-setup     { background: linear-gradient(120deg, #fdf6e6, #f5e6c4); }
        .se-cover-generated { background: linear-gradient(120deg, #eef0fb, #dde2f5); }
        .se-cover-completed { background: linear-gradient(120deg, #edf3f9, #d9e6f2); }
        .se-cover-draft     { background: linear-gradient(120deg, #f4f5f4, #e7eae6); }
        .se-cover-archived  { background: linear-gradient(120deg, #e5e7eb, #d2d6dc); }
        /* A faint horizon line so the tint reads as ground, not just paint. */
        .se-cover::after { content: ''; position: absolute; inset: auto 0 0 0; height: 1.4rem;
            background: linear-gradient(180deg, transparent, rgb(0 0 0 / .05)); pointer-events: none; }
        .se-crops { font-size: 1.7rem; line-height: 1; letter-spacing: .1em; position: relative; z-index: 1;
            flex-shrink: 0; filter: drop-shadow(0 2px 3px rgb(0 0 0 / .12)); }
        /* The schedule's name IS the banner: it stands on the cover beside
           the crops, and the body below is all season-reading. */
        .se-title { position: relative; z-index: 1; min-width: 0; flex: 1 1 auto;
            font-weight: 800; font-size: 1rem; line-height: 1.25; color: var(--color-gray-900);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            text-shadow: 0 1px 0 rgb(255 255 255 / .5); }
        html.dark .se-title { color: #e8efe1; text-shadow: none; }
        .se-status { position: relative; z-index: 1; margin-left: auto; flex-shrink: 0;
            /* The chevron sits after this, so the gap between them is the
               cover's own gap rather than a nudge. */
            display: inline-flex; align-items: center;
            gap: .35rem; padding: .28rem .6rem; border-radius: 999px; background: rgb(255 255 255 / .85);
            font-size: .68rem; font-weight: 700; color: var(--color-gray-700); text-transform: capitalize;
            backdrop-filter: blur(2px); }
        .se-dot { width: .5rem; height: .5rem; border-radius: 999px; background: #9ca3af; }
        .se-dot-active { background: #4a7c2a; box-shadow: 0 0 0 3px rgb(74 124 42 / .18); }
        .se-dot-setup { background: #d97706; }
        .se-dot-generated { background: #6366f1; }
        .se-dot-completed { background: #2563eb; }
        .se-dot-archived { background: #374151; }
        html.dark .se-cover-active    { background: linear-gradient(120deg, #1e2817, #26331b); }
        html.dark .se-cover-setup     { background: linear-gradient(120deg, #2a2414, #332b16); }
        html.dark .se-cover-generated { background: linear-gradient(120deg, #1d2030, #232741); }
        html.dark .se-cover-completed { background: linear-gradient(120deg, #17222d, #1b2a3a); }
        html.dark .se-cover-draft, html.dark .se-cover-archived { background: linear-gradient(120deg, #1a1e18, #232823); }
        html.dark .se-status { background: rgb(0 0 0 / .45); color: #d5dfc9; }

        .se-desc { font-size: .8rem; color: var(--color-gray-500);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        /* Where the crop stands today — the one line a farmer opens this
           page for, so it reads before the counts do. */
        /* One lot at a time, slid rather than stacked: a season with five
           lots would otherwise push the card's own buttons off the screen.
           A native scroller, so a thumb swipes it and a keyboard can too. */
        .se-reads { margin-top: .6rem; }
        /* overscroll-behavior-x: none is the whole fix for the bounce. A
           scroll container drags elastically past its own ends by default,
           and inside a card that reads as the card stretching and springing
           back rather than as a strip reaching its last lot. `contain` is not
           enough — that only stops the pull reaching the page behind it. */
        .se-reads-rail { display: flex; overflow-x: auto; scroll-snap-type: x mandatory;
            overscroll-behavior-x: none; scroll-behavior: smooth;
            align-items: stretch; scrollbar-width: none; -ms-overflow-style: none; }
        .se-reads-rail::-webkit-scrollbar { display: none; }
        /* scroll-snap-stop: always so a hard flick moves one lot, not four —
           skipping past the lot you were reaching for is its own kind of
           bounce. */
        .se-reads-rail > .se-slide { flex: 0 0 100%; scroll-snap-align: start;
            scroll-snap-stop: always; min-width: 0; }
        .se-reads-rail .se-read { margin-top: 0; }
        /* One bar per lot: they are at different points in different crops. */
        .se-lotbar { height: .3rem; border-radius: 999px; background: var(--color-gray-100);
            overflow: hidden; margin-top: .35rem; }
        .se-lotbar span { display: block; height: 100%; border-radius: 999px;
            background: linear-gradient(90deg, #8fbf5e, #4a7c2a); }
        .se-lotfoot { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem;
            margin-top: .2rem; font-size: .66rem; color: var(--color-gray-400); }
        .se-lotfoot span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .se-lotfoot b { color: var(--color-gray-600); font-weight: 700; flex: none; }
        html.dark .se-lotbar { background: rgb(255 255 255 / .08); }
        html.dark .se-lotfoot b { color: #cdd8c0; }
        .se-reads-foot { display: flex; align-items: center; gap: .3rem; margin-top: .35rem; }
        /* Bigger hit area than they look: a dot is .3rem of paint inside a
           thumb's worth of padding, so tapping one is not a test of aim. */
        .se-rdots { display: inline-flex; align-items: center; gap: .1rem; }
        .se-rdots i { width: .3rem; height: .3rem; border-radius: 999px; background: var(--color-gray-300);
            box-sizing: content-box; padding: .38rem .14rem; background-clip: content-box; cursor: pointer;
            transition: background .28s cubic-bezier(.22,1,.36,1),
                width .28s cubic-bezier(.22,1,.36,1), height .28s cubic-bezier(.22,1,.36,1); }
        .se-rdots i:hover { background: var(--color-gray-400); background-clip: content-box; }
        /* The chosen one grows, it does not stretch. Widening alone turned it
           into a lozenge sitting in a line of circles. */
        .se-rdots i.is-on { background: #4a7c2a; background-clip: content-box;
            width: .46rem; height: .46rem; padding: .3rem .14rem; }
        .se-rcount { margin-left: auto; font-size: .62rem; font-weight: 700; color: var(--color-gray-400); }
        html.dark .se-rdots i { background: #3f4a37; background-clip: content-box; }
        html.dark .se-rdots i.is-on { background: #86b556; background-clip: content-box; }
        @media (prefers-reduced-motion: reduce) { .se-rdots i { transition: none; } }

        .se-read { display: flex; align-items: baseline; gap: .4rem; margin-top: .6rem;
            font-size: .8rem; font-weight: 700; color: #3d6823; }
        .se-read-day { font-size: .95rem; font-weight: 800; white-space: nowrap; }
        .se-read-stage { font-weight: 700; color: var(--color-gray-700); min-width: 0; }
        .se-read-lot { font-weight: 600; color: var(--color-gray-400); font-size: .72rem; min-width: 0; }
        .se-read.is-quiet { color: var(--color-gray-400); font-weight: 600; }
        html.dark .se-read { color: #a5c97e; }
        html.dark .se-read-stage { color: #cdd8c0; }

        .se-prog { height: .35rem; border-radius: 999px; background: var(--color-gray-100);
            overflow: hidden; margin-top: .55rem; }
        .se-prog span { display: block; height: 100%; border-radius: 999px;
            background: linear-gradient(90deg, #8fbf5e, #4a7c2a);
            transition: width .28s cubic-bezier(.22,1,.36,1); }
        .se-cover-completed ~ .card-body .se-prog span { background: linear-gradient(90deg, #93c5fd, #2563eb); }
        .se-progline { display: flex; justify-content: space-between; align-items: baseline;
            margin-top: .3rem; font-size: .68rem; color: var(--color-gray-400); }
        .se-progline b { color: var(--color-gray-600); font-weight: 700; }
        html.dark .se-prog { background: rgb(255 255 255 / .08); }
        html.dark .se-progline b { color: #cdd8c0; }
        @media (prefers-reduced-motion: reduce) { .se-prog span { transition: none; } }

        .se-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .9rem;
            margin-top: auto; padding-top: .7rem; font-size: .72rem; font-weight: 500;
            color: var(--color-gray-500); }
        .se-meta svg { width: .95rem; height: .95rem; }
        /* When the season was planted in the app — a quiet tag on its own
           line under the counts. */
        .se-when-tag { flex-basis: 100%; align-self: flex-start; }
        .se-when-tag span { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .55rem;
            border-radius: 999px; background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
            font-size: .66rem; font-weight: 600; color: var(--color-gray-500); }
        html.dark .se-when-tag span { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }

        @media (max-width: 767px) {
            /* A phone shows one card at a time; every row of chrome inside it
               is a row of the next card pushed off screen. */
            .se-card .card-body { padding: .8rem .9rem; }
            .se-cover { height: 3.6rem; }
            .se-crops { font-size: 1.45rem; }
            .se-title { font-size: .9rem; }
            .se-desc { font-size: .78rem; }
            .se-meta { gap: .3rem .8rem; }
            /* Open is what you came for: it takes the width, and the two
               destructive-ish actions shrink to icons beside it rather than
               competing for the same emphasis. */
            .sch-acts .btn:first-child { flex: 1 1 auto; }
            .sch-acts .btn-ghost { padding-left: .55rem !important; padding-right: .55rem !important; }
            .sch-quick .btn { justify-content: center; }
        }
    </style>
@endpush

@section('content')

    {{-- A hello with the day's answer in it: who you are, what today holds,
         and the way straight onto the board that holds it. --}}
    <div class="sch-hero">
        @php
            $__h = (int) now()->format('G');
            [$__greet, $__tod] = $__h < 12
                ? ['Good morning', 'tod-morning']
                : ($__h < 18 ? ['Good afternoon', 'tod-afternoon'] : ['Good evening', 'tod-evening']);
            // Built here rather than inline: a trailing full stop after an
            // @endif is not a directive Blade recognises, and the if never
            // closes.
            $__say = now()->format('l, F j');
            if ($summary['schedules'] === 0) {
                $__say .= ' — nothing planned yet. A schedule is where a season starts.';
            } elseif ($summary['today'] > 0) {
                $__say .= ' — <b>' . $summary['today'] . ' ' . \Illuminate\Support\Str::plural('activity', $summary['today']) . '</b> on the board today';
                $__say .= $summary['active'] ? ', ' . $summary['active'] . ' ' . \Illuminate\Support\Str::plural('season', $summary['active']) . ' running.' : '.';
            } else {
                $__say .= ' — a quiet day, nothing planned on the boards.';
            }
        @endphp
        <div class="sch-hero-left">
            <span class="sch-hero-emoji {{ $__tod }}" aria-hidden="true">
                @if ($__tod === 'tod-morning')
                    {{-- Sunrise: half a sun lifting over the ground line. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v2M5.3 6.7l1.4 1.4M18.7 6.7l-1.4 1.4M8 15a4 4 0 118 0M2 15h2m16 0h2M3 19h18"/></svg>
                @elseif ($__tod === 'tod-afternoon')
                    {{-- Full sun, high. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4l1.4-1.4"/></svg>
                @else
                    {{-- The moon the app already draws elsewhere. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                @endif
            </span>
            <div class="min-w-0">
                <h1 class="sch-hero-h">{{ $__greet }}, {{ \Illuminate\Support\Str::title(auth()->user()->firstName ?: 'farmer') }}</h1>
                <p class="sch-hero-p">{!! $__say !!}</p>
                @if (($todayHref ?? null) && $summary['today'] > 0)
                    <a href="{{ $todayHref }}" class="sch-hero-cta">
                        Open today's board
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h14"/></svg>
                    </a>
                @endif
            </div>
        </div>
        <div class="sch-hero-stats">
            <span class="sch-stat is-today"><b>{{ $summary['today'] }}</b><i>today</i></span>
            <span class="sch-stat"><b>{{ $summary['lots'] }}</b><i>{{ \Illuminate\Support\Str::plural('lot', $summary['lots']) }}</i></span>
            <span class="sch-stat"><b>{{ $summary['workers'] }}</b><i>{{ \Illuminate\Support\Str::plural('worker', $summary['workers']) }}</i></span>
        </div>
    </div>

    {{-- Top bar: search on its own row, the desktop CTAs on a second row below. --}}
    <div class="flex flex-col gap-3 mb-4 md:mb-6">
        {{-- Three doors, one to a row. They were a squeezed strip of three
             across a phone, where every word fell to an ellipsis and none of
             them said what the thing was for. Given a row each they can be
             what the Hub's tiles are: an icon you recognise, a name, and a
             line saying why you would tap it. --}}
        <div class="qa-stack">
            <a href="{{ route('notes.hub') }}" class="qa-tile qa-notes">
                <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span class="qa-txt">
                    <b>Global Notes</b>
                    <i>Every note from every schedule, gathered in one place.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @if ($allSchedules->isNotEmpty())
                <button type="button" id="quickCaptureBtn" class="qa-tile qa-cap">
                    <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    <span class="qa-txt">
                        <b>Quick Capture</b>
                        <i>Photograph what you are standing in front of and file it now.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button type="button" id="quickRecordBtn" class="qa-tile qa-rec">
                    <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg></span>
                    <span class="qa-txt">
                        <b>Quick Record</b>
                        <i>Film it when a picture will not do — a sound, a leak, a machine.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
        </div>

        {{-- Search runs as you type (see the script below); the button-less form
             still submits on Enter as a no-JS fallback. --}}
        <form method="GET" action="{{ route('sm.index') }}" role="search" id="scheduleSearchForm" class="flex-1">
            <div class="relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="text" name="search" id="scheduleSearch" value="{{ request('search') }}" class="form-input pl-11! pr-16! w-full"
                    placeholder="Search schedules…" aria-label="Search schedules" autocomplete="off" enterkeyhint="search">
                <svg id="scheduleSearchSpin" class="hidden absolute right-9 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <button type="button" id="scheduleSearchClear" class="{{ request('search') ? '' : 'hidden' }} absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-full text-gray-400 hover:bg-gray-100" aria-label="Clear search">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </form>

        {{-- Desktop CTA. Wrapped so `hidden` reliably hides it on phones (a
             bare `.btn` is unlayered CSS and would otherwise beat `hidden`);
             the floating + button is the phone equivalent. --}}
        <div class="hidden md:flex md:justify-end md:items-center gap-2">
            <a href="{{ route('sm.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                New Cropping Schedule
            </a>
        </div>
    </div>

    {{-- Two controls for the whole shelf. The orders live behind Filter
         rather than across the top: four pills that are mostly one answer
         cost a row of the screen every time you open the page, and the row
         they cost is the one the seasons wanted. --}}
    @if ($schedules->total() > 0)
        <div class="sch-bar">
            <button type="button" id="schFilterBtn" class="sch-pill{{ $sort !== 'updated' ? ' is-set' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12M10 20h4"/></svg>
                Filter
                @if ($sort !== 'updated')
                    <span class="sch-pill-now">{{ $sorts[$sort]['label'] }}</span>
                @endif
            </button>
            <button type="button" id="schFoldAll" class="sch-pill sch-foldall">Collapse all</button>
        </div>

        {{-- The orders, asked for once. --}}
        <div class="sch-modal hidden" id="schFilterModal" role="dialog" aria-modal="true" aria-label="Arrange schedules">
            <div class="sch-modal-back" data-sch-close></div>
            <div class="sch-modal-card">
                <div class="sch-modal-head">
                    <p class="font-bold text-gray-900">Arrange schedules</p>
                    <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-sch-close aria-label="Close">✕</button>
                </div>
                <div class="sch-modal-body">
                    @foreach ($sorts as $key => $meta)
                        <a class="sch-opt{{ $sort === $key ? ' is-on' : '' }}" data-sort="{{ $key }}"
                           href="{{ route('sm.index', array_filter(['search' => request('search'), 'sort' => $key === 'updated' ? null : $key])) }}">
                            <span class="sch-opt-txt">
                                <b>{{ $meta['label'] }}</b>
                                <i>{{ $meta['why'] }}</i>
                            </span>
                            <span class="sch-opt-tick" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="sch-modal-foot">
                    {{-- A way back, always offered. Working out that "last
                         updated" was the one you started with is not
                         something anybody should have to do. --}}
                    <button type="button" class="sch-clear" data-sort="updated"
                            data-href="{{ route('sm.index', array_filter(['search' => request('search')])) }}">Clear filter</button>
                    <button type="button" class="btn btn-white btn-sm ml-auto" data-sch-close>Done</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Live-search swaps this block's contents (see script). --}}
    <div id="scheduleResults">
    @if ($schedules->isEmpty())
        {{-- Friendly empty state --}}
        <div class="card">
            <div class="card-body text-center py-14">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 8h6"/></svg>
                </div>
                @if (request()->filled('search'))
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No schedules match your search</h2>
                    <p class="text-sm text-gray-500 mb-5">Try a different search, or clear it to see all your schedules.</p>
                    <a href="{{ route('sm.index') }}" class="btn btn-outline">Clear search</a>
                @else
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No cropping schedules yet</h2>
                    <p class="text-sm text-gray-500 mb-5">Create your first schedule to start planning lots, workers and day-by-day activities.</p>
                    <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        New Cropping Schedule
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger-children" id="schedulesGrid">
            @foreach ($schedules as $s)
                @php $card = $cards[$s->id] ?? ['icons' => [], 'reading' => null, 'progress' => null, 'window' => null]; @endphp
                <div class="card card-hover flex flex-col se-card" data-schedule-card="{{ $s->id }}">
                    {{-- The cover IS the status: a field-toned wash, the crops
                         growing on it, and the season's state as weather over
                         it — readable from across the grid. It is also the
                         handle: tapping it folds the card away, so a farm with
                         twenty seasons can be a list of twenty names again. --}}
                    <div class="se-cover se-cover-{{ $s->status }}" data-se-fold role="button" tabindex="0"
                         aria-expanded="true" aria-label="Fold or unfold {{ $s->title }}">
                        <span class="se-crops" aria-hidden="true">{{ count($card['icons']) ? implode('', $card['icons']) : '🌱' }}</span>
                        <h2 class="se-title" title="{{ $s->title }}">{{ $s->title }}</h2>
                        <span class="se-status"><i class="se-dot se-dot-{{ $s->status }}"></i>{{ $s->status }}</span>
                        <span class="se-chev" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </div>
                    {{-- Wrapped so the fold can animate: a grid row going
                         1fr → 0fr closes to nothing without anyone having to
                         measure the contents first. --}}
                    <div class="se-fold-wrap">
                    <div class="card-body flex flex-col grow se-body">
                        @if ($s->description)
                            <p class="se-desc">{{ \Illuminate\Support\Str::limit($s->description, 100) }}</p>
                        @endif

                        {{-- Where each lot stands today — same arithmetic as
                             Growth Stages, so the two pages never disagree.
                             A season has more than one lot, so the strip
                             slides: swipe it, or use the arrows. --}}
                        @php $reads = $card['readings'] ?? []; @endphp
                        @if (count($reads))
                            <div class="se-reads{{ count($reads) > 1 ? ' has-many' : '' }}">
                                <div class="se-reads-rail" data-reads>
                                    @foreach ($reads as $r)
                                        <div class="se-slide">
                                            <div class="se-read">
                                                <span class="se-read-day">{{ $r['counter'] }} {{ $r['day'] }}</span>
                                                @if ($r['stage'])
                                                    <span class="se-read-stage truncate">· {{ $r['stage'] }}</span>
                                                @endif
                                                <span class="se-read-lot truncate">{{ $r['icon'] }} {{ $r['lot'] }}</span>
                                            </div>
                                            @if ($r['through'] !== null)
                                                <div class="se-lotbar"><span style="width: {{ $r['through'] }}%"></span></div>
                                                <div class="se-lotfoot">
                                                    <span>@if ($r['next']) {{ $r['next'] }} in {{ $r['nextIn'] }} {{ \Illuminate\Support\Str::plural('day', (int) $r['nextIn']) }} @else The harvest window @endif</span>
                                                    <b>{{ $r['through'] }}%</b>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if (count($reads) > 1)
                                    {{-- Dots only. The strip is swiped, and a
                                         pair of arrows beside the dots was two
                                         controls for one gesture — the dots
                                         already say where you are and how many
                                         there are, and they take a tap too. --}}
                                    <div class="se-reads-foot">
                                        <span class="se-rdots">
                                            @foreach ($reads as $i => $r)
                                                <i class="{{ $i === 0 ? 'is-on' : '' }}" data-rdot="{{ $i }}" role="button" tabindex="0" aria-label="Lot {{ $i + 1 }}"></i>
                                            @endforeach
                                        </span>
                                        <span class="se-rcount">{{ count($reads) }} lots</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="se-read is-quiet">Not counting yet — the season starts at day zero.</div>
                        @endif

                        {{-- The season's own span, shown only when no lot has a
                             reading of its own to show instead. --}}
                        @if ($card['progress'] !== null && ! count($reads))
                            <div class="se-prog"><span style="width: {{ $card['progress'] }}%"></span></div>
                            <div class="se-progline"><span>{{ $card['window'] }}</span><b>{{ $card['progress'] }}%</b></div>
                        @elseif ($card['window'])
                            <div class="se-progline"><span>{{ $card['window'] }}</span></div>
                        @endif

                        <div class="se-meta">
                            <span class="inline-flex items-center gap-1" title="Lots">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                                {{ $s->lots_count }} {{ \Illuminate\Support\Str::plural('lot', $s->lots_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="Workers">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                                {{ $s->workers_count }} {{ \Illuminate\Support\Str::plural('worker', $s->workers_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="Activities">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01"/></svg>
                                {{ $s->activities_count }} {{ \Illuminate\Support\Str::plural('activity', $s->activities_count) }}
                            </span>
                            <span class="se-when-tag"><span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Created {{ $s->created_at->format('M j, Y') }}
                            </span></span>
                        </div>

                        <div class="flex items-center gap-2 mt-3 sch-acts">
                            <a href="{{ route('sm.hub', ['id' => $s->id]) }}" class="btn btn-primary flex-1">Open</a>
                            <button type="button"
                                class="btn btn-ghost px-3! text-gray-500 hover:bg-gray-100!"
                                data-duplicate-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                title="Duplicate this schedule" aria-label="Duplicate schedule">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <button type="button"
                                class="btn btn-ghost px-3! text-red-500 hover:bg-red-50!"
                                data-delete-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                aria-label="Delete schedule">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                            </button>
                        </div>
                    </div>
                    </div>{{-- /.se-fold-wrap --}}
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $schedules->links() }}
        </div>
    @endif
    </div>{{-- /#scheduleResults --}}

    {{-- The tip reads last on purpose: it is worth knowing, but it is not
         what anyone opened this page for, and at the top it pushed the
         schedules themselves below the fold on a phone. --}}
    @include('sm.partials.tip-of-day', ['tip' => $tip ?? null, 'aiHref' => ($schedules->first() ? route('sm.ai', ['id' => $schedules->first()->id]) : null)])

    {{-- One floating button, for the one thing this page exists to start. --}}
    <a href="{{ route('sm.create') }}"
        class="md:hidden fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg flex items-center justify-center"
        aria-label="New cropping schedule">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
    </a>

    @include('sm.partials.quick-capture', ['allSchedules' => $allSchedules])
    @include('sm.partials.quick-record', ['allSchedules' => $allSchedules])
    {{-- Quick Record borrows the shared recorder, so the page needs it. --}}
    @include('community.partials.video-js')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    /* ---- the lot strip on a season card -----------------------------
     * The rail scrolls by itself; this only keeps the dots honest and
     * lets the arrows drive it. Re-run after a live search swaps cards. */
    function wireReadRails(scope) {
        (scope || document).querySelectorAll('.se-reads.has-many').forEach((box) => {
            if (box.dataset.wired === '1') return;
            box.dataset.wired = '1';
            const rail = box.querySelector('[data-reads]');
            const dots = [...box.querySelectorAll('.se-rdots i')];
            const at = () => Math.round(rail.scrollLeft / Math.max(1, rail.clientWidth));
            const paint = () => {
                const i = at();
                dots.forEach((d, n) => d.classList.toggle('is-on', n === i));
            };
            const goTo = (i) => rail.scrollTo({
                left: Math.max(0, Math.min(dots.length - 1, i)) * rail.clientWidth,
                behavior: 'smooth',
            });
            rail.addEventListener('scroll', () => window.requestAnimationFrame(paint), { passive: true });
            // The dots are the whole control now: they say where you are, and
            // they take you there.
            dots.forEach((dot, i) => {
                dot.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); goTo(i); });
                dot.addEventListener('keydown', (e) => {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    e.preventDefault(); e.stopPropagation(); goTo(i);
                });
            });
            // A card is a link; a swipe on the strip is not a tap on it.
            rail.addEventListener('click', (e) => e.stopPropagation());
            paint();
        });
    }
    wireReadRails();
    window.smWireReadRails = wireReadRails;

    /* ---- Folding a season away ------------------------------------------
     * A farm that has been running a while has a shelf of seasons, most of
     * which are finished. Folding one keeps its cover — the name, the crops,
     * the state — and puts away the working detail underneath, so the shelf
     * can be scanned as a list of names again.
     *
     * Which ones are folded is remembered per farm, because the answer is
     * about this farm's seasons and would be wrong on someone else's. */
    const FOLD_KEY = 'smFolded:' + @json(\App\Support\WorkerContext::effectiveOwnerId());

    function foldedSet() {
        try { return new Set(JSON.parse(localStorage.getItem(FOLD_KEY) || '[]')); }
        catch (_) { return new Set(); }
    }
    function saveFolded(set) {
        try { localStorage.setItem(FOLD_KEY, JSON.stringify([...set])); } catch (_) {}
    }

    function paintFoldAll() {
        const btn = document.getElementById('schFoldAll');
        if (!btn) return;
        const cards = [...document.querySelectorAll('[data-schedule-card]')];
        const anyOpen = cards.some((c) => !c.classList.contains('is-folded'));
        btn.textContent = anyOpen ? 'Collapse all' : 'Expand all';
    }

    function applyFolds(scope) {
        const folded = foldedSet();
        (scope || document).querySelectorAll('[data-schedule-card]').forEach((card) => {
            const on = folded.has(String(card.dataset.scheduleCard));
            card.classList.toggle('is-folded', on);
            card.querySelector('[data-se-fold]')?.setAttribute('aria-expanded', on ? 'false' : 'true');
        });
        paintFoldAll();
    }

    function toggleCard(card) {
        const id = String(card.dataset.scheduleCard);
        const folded = foldedSet();
        const nowFolded = !card.classList.contains('is-folded');
        card.classList.toggle('is-folded', nowFolded);
        card.querySelector('[data-se-fold]')?.setAttribute('aria-expanded', nowFolded ? 'false' : 'true');
        nowFolded ? folded.add(id) : folded.delete(id);
        saveFolded(folded);
        paintFoldAll();
    }

    document.addEventListener('click', (e) => {
        const head = e.target.closest('[data-se-fold]');
        if (!head) return;
        const card = head.closest('[data-schedule-card]');
        if (card) toggleCard(card);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const head = e.target.closest?.('[data-se-fold]');
        if (!head) return;
        e.preventDefault();
        const card = head.closest('[data-schedule-card]');
        if (card) toggleCard(card);
    });

    document.getElementById('schFoldAll')?.addEventListener('click', () => {
        const cards = [...document.querySelectorAll('[data-schedule-card]')];
        // "Collapse all" while anything is open; once everything is shut the
        // same button is the way back.
        const shut = cards.some((c) => !c.classList.contains('is-folded'));
        const folded = foldedSet();
        cards.forEach((card) => {
            card.classList.toggle('is-folded', shut);
            card.querySelector('[data-se-fold]')?.setAttribute('aria-expanded', shut ? 'false' : 'true');
            const id = String(card.dataset.scheduleCard);
            shut ? folded.add(id) : folded.delete(id);
        });
        saveFolded(folded);
        paintFoldAll();
    });

    applyFolds();
    window.smApplyFolds = applyFolds;

    /* ---- The arrange sheet ---------------------------------------------
     * Each option is a plain link, so choosing one is a normal page load
     * that carries the search along with it. Nothing to keep in sync. */
    (function arrangeSheet() {
        const modal = document.getElementById('schFilterModal');
        const btn = document.getElementById('schFilterBtn');
        if (!modal || !btn) return;

        const open = () => {
            modal.classList.remove('hidden');
            void modal.offsetWidth;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            window.registerOverlay?.('schFilter', close);
        };
        const close = () => {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            setTimeout(() => modal.classList.add('hidden'), 260);
        };

        btn.addEventListener('click', open);
        modal.addEventListener('click', (e) => { if (e.target.closest('[data-sch-close]')) close(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
        });

        /* Choosing an order swaps the grid in place. A full navigation
           reloaded the header, the doors and the tip of the day to change
           the sequence of three cards, and the whole screen blinked to do
           it — the same fetch-and-swap the search already uses says the same
           thing without the flash. */
        function paintChoice(key) {
            modal.querySelectorAll('.sch-opt').forEach((o) => {
                o.classList.toggle('is-on', o.dataset.sort === key);
            });
            const clear = modal.querySelector('.sch-clear');
            if (clear) clear.disabled = key === 'updated';
            // The pill says which order is on, unless it is the default.
            btn.classList.toggle('is-set', key !== 'updated');
            let now = btn.querySelector('.sch-pill-now');
            const label = modal.querySelector('.sch-opt[data-sort="' + key + '"] b')?.textContent || '';
            if (key === 'updated') { now?.remove(); return; }
            if (!now) {
                now = document.createElement('span');
                now.className = 'sch-pill-now';
                btn.appendChild(now);
            }
            now.textContent = label;
        }

        async function choose(href, key) {
            if (!results) return;
            close();
            results.classList.add('is-swapping');
            try {
                const res = await fetch(href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const fresh = doc.getElementById('scheduleResults');
                if (fresh) {
                    results.innerHTML = fresh.innerHTML;
                    window.smWireReadRails?.(results);
                    window.smApplyFolds?.(results);
                }
                // The address bar keeps up so a reload lands on the same
                // shelf, without the reload having happened.
                history.replaceState(null, '', href);
                paintChoice(key);
            } catch (_) {
                // Nothing swapped; the order on screen is still the true one.
            } finally {
                results.classList.remove('is-swapping');
            }
        }

        const results = document.getElementById('scheduleResults');
        modal.addEventListener('click', (e) => {
            const opt = e.target.closest('.sch-opt, .sch-clear');
            if (!opt) return;
            e.preventDefault();
            choose(opt.getAttribute('href') || opt.dataset.href, opt.dataset.sort);
        });

        paintChoice(@json($sort));
    })();

    // ---- Live search: fetch as you type and swap the results in place.
    (() => {
        const form = document.getElementById('scheduleSearchForm');
        const input = document.getElementById('scheduleSearch');
        const clearBtn = document.getElementById('scheduleSearchClear');
        const spin = document.getElementById('scheduleSearchSpin');
        const results = document.getElementById('scheduleResults');
        if (!form || !input || !results) return;

        const BASE = @json(route('sm.index'));
        let token = 0;
        let debounce = null;

        async function runSearch(push = true) {
            const q = input.value.trim();
            clearBtn.classList.toggle('hidden', q === '');
            // The chosen order survives a search; losing it mid-typing
            // reshuffles the shelf under the person reading it.
            const qs = new URLSearchParams();
            if (q) qs.set('search', q);
            const sortNow = new URLSearchParams(location.search).get('sort');
            if (sortNow) qs.set('sort', sortNow);
            const url = BASE + (qs.toString() ? ('?' + qs.toString()) : '');
            const mine = ++token;
            spin.classList.remove('hidden');
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const html = await res.text();
                if (mine !== token) return;                 // a newer keystroke won
                const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('scheduleResults');
                if (fresh) {
                    results.innerHTML = fresh.innerHTML;
                    // The cards are new; their lot strips need driving, and
                    // they arrive open regardless of what was folded before.
                    window.smWireReadRails?.(results);
                    window.smApplyFolds?.(results);
                }
                if (push) history.replaceState(null, '', url);
            } catch (_) {
                /* keep the current results on a transient failure */
            } finally {
                if (mine === token) spin.classList.add('hidden');
            }
        }

        input.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(runSearch, 250);
        });
        // Enter shouldn't full-reload; run the search immediately instead.
        form.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(debounce); runSearch(); });
        clearBtn.addEventListener('click', () => { input.value = ''; input.focus(); clearTimeout(debounce); runSearch(); });
    })();

    // Delete schedule (soft delete) -> remove card.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-delete-schedule]');
        if (!btn) return;

        const id = btn.getAttribute('data-delete-schedule');
        const title = btn.getAttribute('data-title') || 'this schedule';

        // A season's worth of work should not go on a mistaken tap. Typing the
        // word is the one confirmation that cannot be given by reflex.
        const ok = await confirmDelete(title);
        if (!ok) return;

        try {
            const res = await api(`{{ route('sm.destroy') }}?id=${id}`, { method: 'DELETE' });
            toast(res.message);
            document.querySelector(`[data-schedule-card="${id}"]`)?.remove();
            if (!document.querySelector('[data-schedule-card]')) window.location.reload();
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    /**
     * Ask for the word DELETE before removing a schedule.
     *
     * Resolves true only when it was typed and Delete pressed; Escape, the
     * backdrop and Cancel all mean no.
     */
    function confirmDelete(title) {
        return new Promise((resolve) => {
            const wrap = document.createElement('div');
            wrap.className = 'del-modal';
            wrap.innerHTML = `
                <div class="del-card" role="dialog" aria-modal="true" aria-labelledby="delTitle">
                    <h3 class="del-title" id="delTitle">Delete this schedule?</h3>
                    <p class="del-text"><strong>${escapeHtml(title)}</strong> and everything filed under it — lots,
                        workers, activities, notes — disappear from your account.</p>
                    <label class="del-label" for="delWord">Type <b>DELETE</b> to confirm</label>
                    <input type="text" id="delWord" class="form-input" autocomplete="off" spellcheck="false" placeholder="DELETE">
                    <div class="del-actions">
                        <button type="button" class="btn btn-white" data-del-no>Cancel</button>
                        <button type="button" class="btn btn-danger" data-del-yes disabled>Delete schedule</button>
                    </div>
                </div>`;
            document.body.appendChild(wrap);
            document.documentElement.style.overflow = 'hidden';

            const field = wrap.querySelector('#delWord');
            const go = wrap.querySelector('[data-del-yes]');
            const done = (answer) => {
                document.documentElement.style.overflow = '';
                wrap.remove();
                document.removeEventListener('keydown', onKey);
                resolve(answer);
            };
            const onKey = (ev) => {
                if (ev.key === 'Escape') done(false);
                if (ev.key === 'Enter' && !go.disabled) done(true);
            };

            field.addEventListener('input', () => {
                go.disabled = field.value.trim().toUpperCase() !== 'DELETE';
            });
            wrap.addEventListener('click', (ev) => {
                if (ev.target === wrap || ev.target.closest('[data-del-no]')) done(false);
                else if (ev.target.closest('[data-del-yes]') && !go.disabled) done(true);
            });
            document.addEventListener('keydown', onKey);
            window.smFocus(field, { delay: 60 });
        });
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-duplicate-schedule]');
        if (!btn) return;

        const id = btn.getAttribute('data-duplicate-schedule');
        const title = btn.getAttribute('data-title') || 'this schedule';

        const ok = await confirmAction({
            title: 'Duplicate schedule?',
            message: `A full copy of "${title}" — every module, activity and version — will be created as "Copy of ${title}".`,
            confirmText: 'Duplicate',
            confirmClass: 'btn-primary',
        });
        if (!ok) return;

        btn.disabled = true;
        const loader = screenLoader(`Duplicating "${title}"…`);
        try {
            const res = await api(`{{ route('sm.duplicate') }}?id=${id}`, { method: 'POST' });
            toast(res.message);
            window.location.href = res.data.hubUrl;
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });
});
</script>
@endpush
