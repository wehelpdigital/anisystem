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
        /* The hairline of field-green along the top is gone. It was one
           restrained accent on a card that had nothing else going on; the
           card is weather now, and a bright green rule across the top of a
           rainy sky is a second thing shouting over the first. */
        .sch-hero-left { display: flex; align-items: center; gap: .85rem; min-width: 0; }

        /* Whose farm this is. A quiet strip above the greeting — it is a
           fact about the page, not an alarm, so it reads as a label until you
           are somewhere that is not your own, where it takes the brand tint. */
        .hat-strip { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem .55rem;
            margin-bottom: .6rem; padding: .45rem .7rem; border-radius: .7rem;
            background: var(--color-gray-100); border: 1px solid var(--color-gray-200); }
        .hat-strip.is-worker { background: var(--color-brand-50); border-color: var(--color-brand-100); }
        .hat-badge { font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
            color: var(--color-gray-500); }
        .hat-strip.is-worker .hat-badge { color: var(--color-brand-700); }
        .hat-where { font-size: .84rem; font-weight: 800; color: var(--color-gray-900); min-width: 0;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hat-what { font-size: .68rem; font-weight: 700; color: var(--color-gray-500);
            padding: .1rem .45rem; border-radius: 999px; background: var(--color-white); }
        .hat-switch { margin-left: auto; font-size: .72rem; font-weight: 800; color: var(--color-brand-700);
            padding: .15rem .5rem; border-radius: 999px; border: 1px solid var(--color-brand-200);
            background: var(--color-white); cursor: pointer;
            transition: background .28s cubic-bezier(.22,1,.36,1); }
        .hat-switch:hover { background: var(--color-brand-50); }
        .hat-opt { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
            padding: .6rem .7rem; border-radius: .7rem; cursor: pointer;
            transition: background .28s cubic-bezier(.22,1,.36,1); }
        .hat-opt:hover { background: var(--color-gray-50); }
        .hat-opt.is-on { background: var(--color-brand-50); }
        .hat-opt-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
        .hat-opt-txt b { font-size: .86rem; font-weight: 700; color: var(--color-gray-900); }
        .hat-opt-txt i { font-style: normal; font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
        .hat-opt-tick { flex: none; width: 1.1rem; height: 1.1rem; color: var(--color-brand-600); }
        .hat-opt-tick svg { width: 100%; height: 100%; }
        @media (prefers-reduced-motion: reduce) { .hat-switch, .hat-opt { transition: none; } }
        /* A drawn mark, not an emoji: platform emoji arrive as square little
           pictures and sat in the round badge like a photo in a porthole.
           The stroke icons match every other icon in the app. */
        .sch-hero-emoji { width: 3rem; height: 3rem; border-radius: 999px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            /* The badge floats — the same slow bob the dashboard's sun rides. */
            animation: heroBob 3.8s ease-in-out infinite alternate; }
        @keyframes heroBob { from { transform: translateY(2px); } to { transform: translateY(-3px); } }
        @media (prefers-reduced-motion: reduce) { .sch-hero-emoji { animation: none; } }
        .sch-hero-emoji svg { width: 1.55rem; height: 1.55rem; }
        /* THE MARK: weather behind, calendar in front.

           The bubble is bigger than the plain one it replaces because it now
           holds two drawings, and it clips — a raincloud drawn to the edge of
           a circle should stop at the circle. */
        /* THE MARK: a calendar, centred, and nothing else in the bubble. */
        /* Nine-tenths again, and bigger. It is the only picture on a card
           that is otherwise words and numbers, and at three and a half rem
           it read as an afterthought beside them. */
        .sch-hero-mark { width: 4.5rem; height: 4.5rem; overflow: hidden;
            background: rgb(255 255 255 / .9); }
        html.dark .sch-hero-mark { background: rgb(21 27 18 / .9); }
        .sch-hero-mark svg { width: 2.7rem; height: 2.7rem; }

        /* THE SKY: the whole card, filled by the atmosphere layer.
           No sizing or arrangement here — the atmosphere knows how to fill a
           box, and the only thing this side decides is how loud it is. */
        /* It arrives, rather than appearing.
           The weather is painted when the forecast answers, a second or two
           after the page — and a picture that pops into existence at frame
           zero of its own animations reads as a still image that then starts
           moving. It fades up instead, and everything inside it starts
           mid-cycle, so the first thing anybody sees is already weather. */
        /* Held at nothing for its first second, then faded up.
           Running the whole time — opacity does not pause an animation — so by
           the time it can be seen the drift, the squall and a dozen clouds are
           all under way rather than sitting on frame zero of them.
           What is on screen during that second is the card's own ground, which
           is the one thing guaranteed to match the card's own ground. A panel
           painted to look like it would have to ride the eighteen-second
           gradient this card sweeps and stay in step with it forever, and the
           drift would show as a patch. */
        .sch-hero-sky { position: absolute; inset: 0; z-index: 0;
            pointer-events: none; opacity: 0;
            transition: opacity 1.1s cubic-bezier(.22, 1, .36, 1); }
        .sch-hero-sky.is-on { opacity: .88; }
        html.dark .sch-hero-sky.is-on { opacity: .92; }
        /* Nothing is moving to wait for, so nothing waits. */
        @media (prefers-reduced-motion: reduce) {
            .sch-hero-sky { transition: none; }
        }
        /* Content over weather. Without this the stat tiles are opaque boxes
           sitting ON the sky rather than in front of it, and the sky stops
           halfway across the card. */
        .sch-hero-left, .sch-hero-stats { position: relative; z-index: 1; }

        /* The shelf's own badge: field green, like the page it heads. */
        .sch-hero-emoji.is-plan { background: linear-gradient(135deg, #eef6e4, #d5e8bd); color: #4a7c2a; }
        html.dark .sch-hero-emoji.is-plan { background: rgb(255 255 255 / .07); color: #a8cc7e; }
        .tod-morning { background: linear-gradient(135deg, #fff7e0, #fbe6ae); color: #d97706; }
        .tod-afternoon { background: linear-gradient(135deg, #e8f4fd, #cde7fa); color: #0284c7; }
        .tod-evening { background: linear-gradient(135deg, #e9e7fb, #d5d2f2); color: #6d28d9; }
        html.dark .tod-morning { color: #fbbf24; }
        html.dark .tod-afternoon { color: #7dd3fc; }
        html.dark .tod-evening { color: #c4b5fd; }
        .sch-hero-say { padding: .6rem .8rem; border-radius: .85rem;
            background: rgb(255 255 255 / .9);
            box-shadow: 0 1px 2px rgb(15 23 42 / .05); }
        html.dark .sch-hero-say { background: rgb(21 27 18 / .9);
            box-shadow: 0 1px 2px rgb(0 0 0 / .3); }
        .sch-hero-h { font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .sch-hero-p { font-size: .82rem; color: var(--color-gray-500); margin-top: .15rem; }
        .sch-hero-p b { font-weight: 700; color: var(--color-gray-700); }
        .sch-hero-cta { display: inline-flex; align-items: center; gap: .3rem; margin-top: .4rem;
            font-size: .78rem; font-weight: 700; color: #3d6823; }
        .sch-hero-cta svg { width: .85rem; height: .85rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .sch-hero-cta:hover svg { transform: translateX(3px); }
        @media (prefers-reduced-motion: reduce) { .sch-hero-cta svg { transition: none; } }
        .sch-hero-stats { display: flex; gap: .45rem; flex-wrap: wrap; }
        /* The same nine-tenths the words stand on. Weather runs behind all
           four of these, and a plate that is sheer under the heading and
           solid under the counts is two different decisions showing on one
           card. */
        .sch-stat { display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-width: 3.9rem; padding: .45rem .65rem; border-radius: .85rem;
            background: rgb(255 255 255 / .9); border: 1px solid var(--color-gray-200); }
        .sch-stat b { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.15; }
        .sch-stat i { font-style: normal; font-size: .6rem; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--color-gray-400); }
        /* Today's tile is the one that matters — it gets the accent. */
        .sch-stat.is-today { background: rgb(240 247 232 / .92); border-color: #cfe3b8; }
        .sch-stat.is-today b { color: #3d6823; }
        .sch-stat.is-today i { color: #6b9f3d; }
        html.dark .sch-hero { background-color: #151b12; border-color: #2b3a1c; }
        html.dark .sch-hero-h { color: #e8efe1; }
        html.dark .sch-hero-p { color: #a8bd93; }
        html.dark .sch-hero-p b { color: #cdd8c0; }
        html.dark .sch-hero-cta { color: #a5c97e; }
        html.dark .tod-morning, html.dark .tod-afternoon, html.dark .tod-evening { background: rgb(255 255 255 / .07); }
        html.dark .sch-stat { background: rgb(21 27 18 / .9); border-color: #2b3a1c; }
        html.dark .sch-stat b { color: #e8efe1; }
        html.dark .sch-stat.is-today { background: rgb(31 45 22 / .92); border-color: #3f5626; }
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
        /* --- Global and Quick Tools: one heading over the four doors ---
           Folds with a grid row rather than max-height, so the panel is
           exactly as tall as what is in it and the animation has a real end
           to reach. */
        .qa-panel { border: 1px solid var(--color-gray-200); border-radius: 1rem;
            background: var(--color-white); overflow: hidden; }
        .qa-panel-head { display: flex; align-items: center; gap: .7rem; width: 100%;
            text-align: left; padding: .7rem .8rem; cursor: pointer; background: none; border: 0; }
        .qa-panel-head:hover { background: var(--color-gray-50); }
        .qa-panel-ico { width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--color-brand-50); color: var(--color-brand-700); }
        .qa-panel-ico svg { width: 1.25rem; height: 1.25rem; }
        .qa-panel-txt { min-width: 0; flex: 1 1 auto; }
        .qa-panel-txt b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
        .qa-panel-txt i { display: block; font-style: normal; font-size: .75rem; color: var(--color-gray-500);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .qa-panel-chev { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-gray-400);
            transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .qa-panel.is-folded .qa-panel-chev { transform: rotate(-90deg); }
        .qa-panel-fold { display: grid; grid-template-rows: 1fr;
            transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
        .qa-panel.is-folded .qa-panel-fold { grid-template-rows: 0fr; }
        .qa-panel-fold > div { overflow: hidden; min-height: 0; }
        .qa-panel .qa-stack { padding: 0 .55rem .55rem; }
        /* Inside the panel the tiles are rows of a list, not cards on a page. */
        .qa-panel .qa-tile { border-color: var(--color-gray-100); }
        @media (prefers-reduced-motion: reduce) {
            .qa-panel-fold, .qa-panel-chev { transition: none; }
        }

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
        .qa-gallery:hover { border-color: #c7dbf5; } .qa-gallery:hover .qa-go { color: #1d4ed8; }
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
        .qa-gallery .qa-ico { background: #eaf1fd; color: #1d4ed8; }
        .qa-cap .qa-ico { background: #eef6e6; color: #3d6823; }
        .qa-wtp .qa-ico { background: #eef2fd; color: #4c65e0; }
        .qa-wtp:hover { border-color: #c7d2f5; } .qa-wtp:hover .qa-go { color: #4c65e0; }
        html.dark .qa-wtp .qa-ico { background: rgb(76 101 224 / .22); color: #a5b6f2; }
        .qa-rec .qa-ico { background: #fdecec; color: #b91c1c; }
        html.dark .qa-notes .qa-ico { background: rgb(180 83 9 / .18); color: #e0b457; }
        html.dark .qa-gallery .qa-ico { background: rgb(29 78 216 / .22); color: #9fc0f5; }
        html.dark .qa-cap .qa-ico { background: rgb(61 104 35 / .25); color: #a5c97e; }
        html.dark .qa-rec .qa-ico { background: rgb(185 28 28 / .2); color: #f0a3a3; }


        /* ---- the shelf's own controls: how it is arranged, and shutting
           all of it at once ---- */
        .sch-bar { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; }
        /* In the Archives the search field stands bare (the main page's
           wrapper and its gap belong to the other branch), so the bar
           brings its own air — the same 1rem the shelf view gets. */
        .sch-bar.is-arch { margin-top: 1rem; }
        /* ---- The Archives banner --------------------------------------
           The way back wears the community Search button's shape — a green
           outline with its icon — because it is the one deliberate thing on
           this page and should look like the app's other deliberate things. */
        .sch-arch { margin-bottom: 1rem; }
        .sch-arch-back { display: inline-flex; align-items: center; gap: .4rem;
            padding: .5rem .95rem; border-radius: 999px; cursor: pointer;
            font-size: .82rem; font-weight: 700; text-decoration: none;
            color: #2f5a17; background: var(--color-white);
            border: 1.5px solid #4a7c2a;
            transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .sch-arch-back svg { width: .95rem; height: .95rem; }
        .sch-arch-back:hover { background: #f2f8ec; color: #24380f; }
        .sch-arch-say { display: flex; align-items: flex-start; gap: .85rem; margin-top: .9rem; }
        .sch-arch-ico { flex: none; width: 2.75rem; height: 2.75rem; border-radius: .9rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: #eef2f7; color: #55617a; }
        .sch-arch-ico svg { width: 1.4rem; height: 1.4rem; }
        .sch-arch-h { font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .sch-arch-p { font-size: .82rem; line-height: 1.55; color: var(--color-gray-500); margin-top: .15rem; }
        html.dark .sch-arch-back { background: #151b12; color: #a5c97e; border-color: #4a7c2a; }
        html.dark .sch-arch-ico { background: rgb(255 255 255 / .06); color: #a8bd93; }
        html.dark .sch-arch-h { color: #e8efe1; }
        @media (prefers-reduced-motion: reduce) { .sch-arch-back { transition: none; } }

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
        /* A bare arrow, like the home page's fold chevrons: the white disc
           it used to sit in read as a white blob on the cover's light wash,
           and the arrow inside it washed out with it. */
        .se-chev { position: relative; z-index: 1; flex: none; width: 1.5rem; height: 1.5rem;
            display: flex; align-items: center; justify-content: center;
            color: #4b5563;
            transition: transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .se-cover:hover .se-chev { color: #3d6823; }
        .se-chev svg { width: .9rem; height: .9rem; }
        html.dark .se-chev { color: #d5dfc9; }
        .se-card.is-folded .se-chev { transform: rotate(-90deg); }
        /* Height animates, so folding reads as the card closing rather than
           the card vanishing. grid-template-rows does it without anyone
           having to measure the content first. */
        .se-body { display: block; }
        .se-card .se-fold-wrap { display: grid; grid-template-rows: 1fr; }
        /* The transition is on ONLY while a fold is actually happening.
           `1fr` resolves against the content, so a permanently-transitioned
           row animates on every relayout the card ever has — and swiping the
           lot strip is a relayout. That is what made the whole card bounce
           up and down under the finger: not the strip moving, but the row
           height being re-animated a hundred times on the way past. */
        .se-card.is-folding .se-fold-wrap { transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
        .se-card.is-folded .se-fold-wrap { grid-template-rows: 0fr; }
        .se-card .se-fold-wrap > * { min-height: 0; overflow: hidden; }
        .se-card.is-folded .se-body { padding-top: 0; padding-bottom: 0; }
        .se-card.is-folded { align-self: start; }
        @media (prefers-reduced-motion: reduce) {
            .se-chev, .se-card.is-folding .se-fold-wrap { transition: none; }
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
        /* Soft, desaturated tints — the status is the weather over the field.
           The cover breathes on the shared gradSweep tide: slow enough that a
           shelf of seasons shimmers like ground, not like adverts.
           !important because every status (and its night twin, at higher
           specificity) paints via the `background` shorthand, which quietly
           resets background-size to auto. */
        /* Earth, not weather.
         *
         * The three status tints were three different colours doing the job
         * the dot and the word beside them already do, and a shelf of them
         * read as a paint chart. One ground for every season — tilled soil,
         * light enough to keep dark type on it — and the status stays where
         * it was said: in the dot and in the word.
         *
         * Each cover drifts on its own clock (--sw-t) from its own point in
         * the cycle (--sw-d, a negative delay, so it starts mid-sweep). A
         * shelf where every gradient slid the same way at the same moment
         * read as one animation playing on six cards. */
        .se-cover { background-size: 220% 100% !important;
            animation: gradSweep var(--sw-t, 13s) ease-in-out infinite alternate;
            animation-delay: var(--sw-d, 0s); }
        @media (prefers-reduced-motion: reduce) { .se-cover { animation: none; } }
        .se-cover-active, .se-cover-setup, .se-cover-generated,
        .se-cover-completed, .se-cover-draft, .se-cover-archived {
            background: linear-gradient(120deg, #f4e9dc, #dfc9ac 42%, #cbb08c 68%, #ecdfcd); }
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
        /* The same ground after dark: turned earth rather than five colours
           of night. The status still speaks in the dot and the word. */
        html.dark .se-cover-active, html.dark .se-cover-setup,
        html.dark .se-cover-generated, html.dark .se-cover-completed,
        html.dark .se-cover-draft, html.dark .se-cover-archived {
            background: linear-gradient(120deg, #2a2018, #3a2c1e 42%, #4a3826 68%, #2f241a); }
        html.dark .se-status { background: rgb(0 0 0 / .45); color: #d5dfc9; }

        .se-desc { font-size: .8rem; color: var(--color-gray-500);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        /* On the cover, so they sit on a tinted ground rather than on white:
           the ink is the cover's own dark green, held back until a thumb or a
           pointer comes near. z-index because the cover paints a gradient
           over itself and these have to be above it to be clickable. */
        .se-tools { position: relative; z-index: 1; flex: none; margin-left: auto;
            display: inline-flex; align-items: center; gap: .1rem; }
        .se-tool { width: 1.9rem; height: 1.9rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: .5rem; color: rgb(61 104 35 / .5);
            transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .se-tool svg { width: 1rem; height: 1rem; }
        .se-tool:hover { background: rgb(255 255 255 / .55); color: #2f5219; }
        .se-tool.is-danger:hover { background: rgb(255 255 255 / .7); color: #dc2626; }
        html.dark .se-tool { color: rgb(213 223 201 / .55); }
        html.dark .se-tool:hover { background: rgb(0 0 0 / .3); color: #e8efe1; }
        html.dark .se-tool.is-danger:hover { color: #fca5a5; }
        @media (prefers-reduced-motion: reduce) { .se-tool { transition: none; } }
        /* The status WORD is the first thing to give up its space when the
           row runs out. The two tools and the chevron are hit targets and
           cannot shrink, and the season's name is what anybody is scanning
           for — so on a phone the pill keeps its coloured dot, which is the
           whole of the signal, and drops the five letters that repeat it.
           That is about seventy pixels back to the title. */
        @media (max-width: 480px) {
            .se-status { font-size: 0; gap: 0; padding: .3rem; }
            .se-status .se-dot { width: .5rem; height: .5rem; }
        }
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
            background: linear-gradient(90deg, #8fbf5e, #4a7c2a);
            /* The day-count fills share the gradSweep tide (layout). */
            background-size: 220% 100%;
            animation: gradSweep 9s ease-in-out infinite alternate; }
        @media (prefers-reduced-motion: reduce) { .se-lotbar span { animation: none; } }
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
            /* Same tide as the lot bars; the size gives it room to drift. */
            background-size: 220% 100%;
            animation: gradSweep 9s ease-in-out infinite alternate;
            transition: width .28s cubic-bezier(.22,1,.36,1); }
        .se-cover-completed ~ .card-body .se-prog span { background: linear-gradient(90deg, #93c5fd, #2563eb); background-size: 220% 100%; }
        .se-progline { display: flex; justify-content: space-between; align-items: baseline;
            margin-top: .3rem; font-size: .68rem; color: var(--color-gray-400); }
        .se-progline b { color: var(--color-gray-600); font-weight: 700; }
        html.dark .se-prog { background: rgb(255 255 255 / .08); }
        html.dark .se-progline b { color: #cdd8c0; }
        @media (prefers-reduced-motion: reduce) { .se-prog span { transition: none; animation: none; } }

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
    {{-- Whose seasons these are.

         One account is often several things at once — a farm of your own,
         work on a neighbour's, sometimes both — and every screen below is
         scoped to whichever is active. Without a line saying which, an
         unfamiliar list of seasons has to be decoded from the names on it.
         It only appears when there is something to say: a plain owner with no
         second hat is never told they are themselves. --}}
    @php
        $hatList = collect($hats ?? []);
        $hereKey = $isWorkerHere ? 'worker:' . optional(\App\Support\WorkerContext::activeGrant())->bossUserId : 'own';
        $hereName = $isWorkerHere
            ? ($workerBossName ?: 'a farm')
            : 'Your own farm';
        $canSwitch = $hatList->count() > 1;
    @endphp
    @if ($isWorkerHere || $canSwitch)
        <div class="hat-strip{{ $isWorkerHere ? ' is-worker' : '' }}">
            <span class="hat-badge">{{ $isWorkerHere ? 'Working at' : 'You are in' }}</span>
            <span class="hat-where">{{ $hereName }}</span>
            @if ($isWorkerHere)
                <span class="hat-what">{{ \App\Support\WorkerContext::canEdit() ? 'can add and change work' : 'can look, not change' }}</span>
            @endif
            @if ($canSwitch)
                <button type="button" class="hat-switch" id="hatSwitchBtn" aria-haspopup="dialog">Switch</button>
            @endif
        </div>

        @if ($canSwitch)
            {{-- The switch existed as a route and was reachable only at login;
                 somebody who wears two hats changes them during the day. --}}
            <div class="sheet hidden" id="hatSheet" style="--sheet-width:24rem">
                <div class="sheet-handle"></div>
                <div class="sheet-header">
                    <h3 class="sheet-title">Which farm?</h3>
                    <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
                </div>
                <div class="sheet-body space-y-1">
                    @foreach ($hatList as $hat)
                        <form method="POST" action="{{ route('worker.switch') }}">
                            @csrf
                            <input type="hidden" name="bossId" value="{{ $hat['bossId'] ?? 0 }}">
                            <button type="submit" class="hat-opt{{ $hat['key'] === $hereKey ? ' is-on' : '' }}">
                                <span class="hat-opt-txt">
                                    <b>{{ $hat['title'] }}</b>
                                    <i>{{ $hat['detail'] }}</i>
                                </span>
                                @if ($hat['key'] === $hereKey)
                                    <span class="hat-opt-tick" aria-hidden="true">
                                        <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- The Archives is a different page.

         Everything above the list — the greeting, the season counts, the
         quick tools, the "here are your cropping schedules for today" — is
         about the work in front of somebody. None of it is true of a shelf of
         finished seasons, and all of it stands between a reader and the one
         thing they came here to find. The archives get their own banner
         instead, and then the list. --}}
    @if ($showArchived ?? false)
        <div class="sch-arch">
            <a href="{{ route('sm.index') }}" class="sch-arch-back">
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to seasons
            </a>
            <div class="sch-arch-say">
                <span class="sch-arch-ico" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6.5h18v3H3zM4.5 9.5v9a2 2 0 002 2h11a2 2 0 002-2v-9"/><path stroke-linecap="round" d="M10 13.5h4"/></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="sch-arch-h">Archives</h1>
                    <p class="sch-arch-p">{{ $archivedCount }} closed
                        {{ \Illuminate\Support\Str::plural('season', $archivedCount) }}. Open any of them to
                        read or reopen — reopening puts it back on the shelf.</p>
                </div>
            </div>
        </div>
    @else
    @include('partials.farm-scenes')
    {{-- The skies too: a quiet board hands the panel over to the forecast,
         and it cannot do that without the drawings and the colour table. --}}
    @include('partials.weather-scenes')
    {{-- And the weather as a place rather than a picture of one: what fills
         the card behind everything on it. --}}
    @include('partials.weather-atmosphere')
    {{-- The panel wears the sky. Not the day's work: what is on the board is
         already said in words directly underneath, and a picture of a plough
         above a line saying "1 activity" was the same fact told twice. The
         weather is the thing the words do NOT say, and it is what decides
         whether today's plan survives contact with the morning. --}}
    <div class="sch-hero fs-pane fs-hue-sky" id="schHero">
        {{-- The weather, filling the card behind everything on it.

             Not three copies of an icon: real rain falling the height of the
             card, a sun off the top corner with its rays reaching in, cloud
             hanging over the top edge with only its underside showing. You
             are meant to read it the way you read the light in a room rather
             than the way you read a label — you look up, and the card is
             raining. The gradient tint is the same forecast said a second
             way, and the two sit on top of each other because they are the
             same fact. --}}
        <span class="sch-hero-sky" id="schHeroSky" aria-hidden="true"></span>
        @php
            // No hour in the greeting any more. This page is a list of
            // schedules, and "Good evening" was a second thing to read before
            // the one thing anybody opened it for — the line underneath still
            // says what day it is and what the day holds.
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
            {{-- A calendar, and only a calendar. The weather is behind the
                 whole card now — sharing a bubble the size of a thumbnail
                 with a raincloud made two drawings out of which neither
                 could be read. --}}
            <span class="sch-hero-emoji sch-hero-mark fs-slot" id="schHeroMark"
                  data-fs-act="quiet" data-fs-size="34" title="Your seasons, day by day"></span>
            {{-- A plate under the words.

                 The weather behind this card is the point of it, and the
                 point of a heading is that it can be read without effort.
                 Both are had by standing the text on nine-tenths of the
                 card's own colour: opaque enough that a raindrop never
                 crosses a letter, sheer enough that the sky still shows
                 through and the plate does not read as a box dropped on top
                 of the design. --}}
            <div class="min-w-0 sch-hero-say">
                <h1 class="sch-hero-h">Here are your cropping schedules for today</h1>
                <p class="sch-hero-p">{!! $__say !!}</p>
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
        {{-- Folded behind one heading: this page is a list of seasons, and
             four full-width doors above it pushed the list off a phone
             screen. The choice is remembered per farm, because whether these
             are useful depends on how somebody works, not on which visit it
             is. --}}
        <section class="qa-panel" id="globalTools">
            <button type="button" class="qa-panel-head" id="globalToolsHead" aria-expanded="true" aria-controls="globalToolsBody">
                <span class="qa-panel-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 012 2v1.2a6.9 6.9 0 011.7.7l.85-.85a2 2 0 012.83 0l.7.7a2 2 0 010 2.83l-.85.85c.31.53.55 1.1.7 1.7H20a2 2 0 012 2v1a2 2 0 01-2 2h-1.2M4 13a2 2 0 01-2-2v-1a2 2 0 012-2h1.2c.15-.6.39-1.17.7-1.7"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6zM7 20l5-5 5 5"/></svg></span>
                <span class="qa-panel-txt">
                    <b>Global and Quick Tools</b>
                    <i>Notes and pictures across every season, and the two ways to add one now.</i>
                </span>
                <svg class="qa-panel-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="qa-panel-fold" id="globalToolsBody">
              <div>
        <div class="qa-stack">
            <a href="{{ route('notes.hub') }}" class="qa-tile qa-notes">
                <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span class="qa-txt">
                    <b>Global Notes</b>
                    <i>Every note from every schedule, gathered in one place.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- Its twin: the notes hub gathers the words, this gathers the
                 pictures. Looking for a photo is remembering a picture, not a
                 season, so it does not ask which one first. --}}
            <a href="{{ route('wtp.page') }}" class="qa-tile qa-wtp">
                <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></span>
                <span class="qa-txt">
                    <b>When to Plant Analysis</b>
                    <i>The best planting window for your crop and place, argued from the climate. Uses AI credits.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ route('gallery.hub') }}" class="qa-tile qa-gallery">
                <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4"/></svg></span>
                <span class="qa-txt">
                    <b>Global Gallery</b>
                    <i>Every photo, drawing and saved map, from every schedule.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @php
                /* The camera and the recorder are modules the owner grants,
                   and the Hub has always drawn these two tiles only for
                   somebody who holds them. This list did not, so a worker
                   with no recorder still had a Quick Record button here — a
                   door with nothing behind it. */
                $qMayCamera = \App\Support\WorkerContext::canUseModule('camera');
                $qMayVideo = \App\Support\WorkerContext::canUseModule('video');
            @endphp
            @if ($allSchedules->isNotEmpty() && $qMayCamera)
                <button type="button" id="quickCaptureBtn" class="qa-tile qa-cap">
                    <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    <span class="qa-txt">
                        <b>Quick Capture</b>
                        <i>Photograph what you are standing in front of and file it now.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
            @if ($allSchedules->isNotEmpty() && $qMayVideo)
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
              </div>
            </div>
        </section>
    @endif

        {{-- The phone's door to a new season: a full row above the search,
             wearing the moving green, instead of a + hovering over the list.
             Desktop keeps its own CTA below; workers and the archive view
             get no door, same as the button this replaces. --}}
        @if (! $isWorkerHere && ! ($showArchived ?? false))
            <a href="{{ route('sm.create') }}" class="sch-add-cta md:hidden" id="schAddCta">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Add New Cropping Schedule
            </a>
        @endif

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
            @if (! $isWorkerHere)
            <a href="{{ route('sm.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                New Cropping Schedule
            </a>
            @endif
        </div>
    </div>

    <style>
        /* The green door breathes: the field-green gradient drifts end to
           end the way the day headers' wash does, so the button reads as
           alive without shouting. Reduced motion holds it still. */
        .sch-add-cta { display: flex; align-items: center; justify-content: center; gap: .5rem;
            width: 100%; padding: .85rem 1rem; border-radius: 1rem; color: #fff;
            font-weight: 800; font-size: .95rem; letter-spacing: .01em;
            background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
            background-size: 260% 100%;
            box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65);
            animation: schAddTide 5.5s ease-in-out infinite alternate;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .sch-add-cta:active { transform: scale(.985); }
        @keyframes schAddTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
        @media (prefers-reduced-motion: reduce) { .sch-add-cta { animation: none; background-position: 40% 50%; } }
        html.dark .sch-add-cta { box-shadow: 0 10px 22px -12px rgb(0 0 0 / .7); }
    </style>

    {{-- Two controls for the whole shelf. The orders live behind Filter
         rather than across the top: four pills that are mostly one answer
         cost a row of the screen every time you open the page, and the row
         they cost is the one the seasons wanted. --}}
    @if ($schedules->total() > 0)
        <div class="sch-bar{{ ($showArchived ?? false) ? ' is-arch' : '' }}">
            <button type="button" id="schFilterBtn" class="sch-pill{{ $sort !== 'updated' ? ' is-set' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12M10 20h4"/></svg>
                Filter
                @if ($sort !== 'updated')
                    <span class="sch-pill-now">{{ $sorts[$sort]['label'] }}</span>
                @endif
            </button>
            <button type="button" id="schFoldAll" class="sch-pill sch-foldall">Collapse all</button>
            {{-- The closed seasons, one tap away. A season that has been shut
                 is done being farmed, so it is not on this shelf — but it is
                 not gone either, and this is the door to it. Drawn only when
                 there is something behind the door, except while you are
                 standing in it. --}}
            @if (($archivedCount ?? 0) > 0 && ! ($showArchived ?? false))
                <a href="{{ route('sm.index', ['archived' => 1]) }}" class="sch-pill sch-archives">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6.5h18v3H3zM4.5 9.5v9a2 2 0 002 2h11a2 2 0 002-2v-9"/><path stroke-linecap="round" d="M10 13.5h4"/></svg>
                    Archives
                    <span class="sch-pill-now">{{ $archivedCount }}</span>
                </a>
            @endif
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
                @if ($showArchived ?? false)
                    {{-- Standing in the Archives with nothing in them. Telling
                         somebody to create their first schedule here would be
                         answering a question they did not ask. --}}
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Nothing in the archives</h2>
                    <p class="text-sm text-gray-500 mb-5">A season goes in here when you close it. Nothing is lost — you can open it from here, and reopening puts it back on the shelf.</p>
                    <a href="{{ route('sm.index') }}" class="btn btn-outline">Back to seasons</a>
                @elseif (request()->filled('search'))
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No schedules match your search</h2>
                    <p class="text-sm text-gray-500 mb-5">Try a different search, or clear it to see all your schedules.</p>
                    <a href="{{ route('sm.index') }}" class="btn btn-outline">Clear search</a>
                @else
                    @if ($isWorkerHere)
                        {{-- A worker sees the farm they were let into, so an
                             empty page means nothing has been shared with them
                             yet — not that they should start something. --}}
                        <h2 class="text-lg font-bold text-gray-900 mb-1">Nothing shared with you yet</h2>
                        <p class="text-sm text-gray-500 mb-5">When {{ $workerBossName ?: 'the farm owner' }} gives you a season to work on, it appears here.</p>
                    @else
                        <h2 class="text-lg font-bold text-gray-900 mb-1">No cropping schedules yet</h2>
                        <p class="text-sm text-gray-500 mb-5">Create your first schedule to start planning lots, workers and day-by-day activities.</p>
                        <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                            New Cropping Schedule
                        </a>
                    @endif
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
                         {{-- Its own clock and its own starting point, from its
                              own id: the same tide, out of step. --}}
                         style="--sw-t:{{ 10 + ($s->id % 7) }}s;--sw-d:-{{ $s->id % 11 }}s"
                         aria-expanded="true" aria-label="Fold or unfold {{ $s->title }}">
                        <span class="se-crops" aria-hidden="true">{{ count($card['icons']) ? implode('', $card['icons']) : '🌱' }}</span>
                        <h2 class="se-title" title="{{ $s->title }}">{{ $s->title }}</h2>
                        {{-- Duplicate and Delete, level with the name they
                             act on. They were at the foot of the card taking
                             a third of Open's row, then at the top of the
                             body floating in white space on a season with no
                             description. Here they are anchored to something:
                             the line that says which season this is.

                             The cover is the fold handle, so both of these
                             stop the click getting to it — a tap meant for
                             Duplicate that folds the card away instead is
                             the kind of thing you only forgive once. --}}
                        <span class="se-tools">
                            @if (! \App\Support\WorkerContext::activeGrant() || \App\Support\WorkerContext::canEdit())
                                <button type="button" class="se-tool"
                                    data-duplicate-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                    title="Duplicate this schedule" aria-label="Duplicate {{ $s->title }}">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            @endif
                            @if (! \App\Support\WorkerContext::activeGrant())
                                <button type="button" class="se-tool is-danger"
                                    data-delete-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                    title="Delete this schedule" aria-label="Delete {{ $s->title }}">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                                </button>
                            @endif
                        </span>
                        {{-- The status chip is gone. Its WORD was the first
                             thing to give up its space when the row ran out,
                             so on a phone all that was left was an orange dot
                             with nothing to say it meant "setting up" — a mark
                             beside a delete button that nobody could read. The
                             state is on the card itself, where there is room
                             to spell it. --}}
                        <span class="se-chev" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </div>
                    {{-- Wrapped so the fold can animate: a grid row going
                         1fr → 0fr closes to nothing without anyone having to
                         measure the contents first. --}}
                    <div class="se-fold-wrap">
                    <div class="card-body flex flex-col grow se-body">
                        {{-- The line about the season, and the two things you
                             rarely do to it.

                             Duplicate and Delete used to sit beside Open at
                             the foot of the card, taking a third of the width
                             from the one button anybody came to press. Up
                             here they are out of the way of the thumb and
                             still in plain sight — the far side of the first
                             line, which is where a card's own controls go
                             everywhere else in this app. --}}
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
                                                <span class="se-read-day">{{ \App\Support\LotCalendar::says($r) }}</span>
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

                        {{-- One button, the width of the card. It is the
                             only thing on this card anybody presses on
                             purpose, and it was sharing a row with two icons
                             that get pressed once a season. --}}
                        <div class="mt-3 sch-acts">
                            {{-- Its own clock and starting point, from its own
                                 id, so a shelf of these never sweeps as one. --}}
                            <a href="{{ route('sm.hub', ['id' => $s->id]) }}" class="btn btn-primary w-full sweep-fill sweep-green"
                               style="--sw-t:{{ 9 + ($s->id % 7) }}s;--sw-d:-{{ $s->id % 11 }}s">Open</a>
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

    {{-- The tip of the day has moved to the dashboard, above the schedules
         there: it is the first thing worth reading when the app opens, and it
         was read by nobody at the bottom of this list. --}}

    {{-- One floating button, for the one thing this page exists to start —
         and nothing a worker can start, so it is not drawn for them. Nor in
         the Archives: a shelf of finished seasons is not where anybody starts
         a new one, and a green + hanging over closed work reads as an
         invitation to do the wrong thing. --}}
    {{-- The floating + is gone: the phone's door to a new season is the
         green row above the search field now, where it can say its name. --}}

    @if (\App\Support\WorkerContext::canUseModule('camera'))
        @include('sm.partials.quick-capture', ['allSchedules' => $allSchedules])
    @endif
    @if (\App\Support\WorkerContext::canUseModule('video'))
        @include('sm.partials.quick-record', ['allSchedules' => $allSchedules])
    @endif
    {{-- Whether the tools panel is open. Kept per farm beside the folds this
         page already remembers, so a worker standing in somebody else's farm
         does not inherit the owner's choice. --}}
    <script>
    (function globalToolsFold() {
        const panel = document.getElementById('globalTools');
        const head = document.getElementById('globalToolsHead');
        if (!panel || !head) return;

        const KEY = 'smToolsFolded:' + @json(\App\Support\WorkerContext::effectiveOwnerId());
        const paint = (folded) => {
            panel.classList.toggle('is-folded', folded);
            head.setAttribute('aria-expanded', folded ? 'false' : 'true');
        };

        // Painted before the first frame where possible; the class only
        // changes a grid row, so there is nothing to flash.
        let folded = false;
        try { folded = localStorage.getItem(KEY) === '1'; } catch (_) {}
        paint(folded);

        head.addEventListener('click', () => {
            folded = !panel.classList.contains('is-folded');
            paint(folded);
            try { localStorage.setItem(KEY, folded ? '1' : '0'); } catch (_) {}
        });
    })();
    </script>

    {{-- Quick Record borrows the shared recorder, so the page needs it. --}}
    @include('community.partials.video-js')
@endsection

@push('scripts')
<script>
    // The hat switcher's opener. The sheet itself is the app's own component,
    // so closing, the backdrop and Escape are already handled.
    document.getElementById('hatSwitchBtn')?.addEventListener('click', () => window.openSheet?.('hatSheet'));
</script>
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

    /* The height transition is armed only for the length of a fold.
     *
     * Left on permanently it animated every relayout the card ever had —
     * including the ones a swipe of the lot strip causes — which is what
     * made the card bounce under the finger. */
    function arm(card) {
        card.classList.add('is-folding');
        clearTimeout(card.__foldTimer);
        card.__foldTimer = setTimeout(() => card.classList.remove('is-folding'), 340);
    }

    function toggleCard(card) {
        const id = String(card.dataset.scheduleCard);
        const folded = foldedSet();
        const nowFolded = !card.classList.contains('is-folded');
        arm(card);
        card.classList.toggle('is-folded', nowFolded);
        card.querySelector('[data-se-fold]')?.setAttribute('aria-expanded', nowFolded ? 'false' : 'true');
        nowFolded ? folded.add(id) : folded.delete(id);
        saveFolded(folded);
        paintFoldAll();
    }

    document.addEventListener('click', (e) => {
        const head = e.target.closest('[data-se-fold]');
        if (!head) return;
        // Duplicate and Delete live on the cover now, and the cover is the
        // fold handle. A tap meant for one of them that folds the card away
        // instead is the kind of thing you only forgive once.
        if (e.target.closest('.se-tools')) return;
        const card = head.closest('[data-schedule-card]');
        if (card) toggleCard(card);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const head = e.target.closest?.('[data-se-fold]');
        if (!head || e.target.closest?.('.se-tools')) return;
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
            arm(card);
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

/* THE PANEL WEARS THE SKY.
 *
 * A wet day and a clear one are not the same working day, and that is the one
 * thing the words on this card do not already say. The tint is the only thing
 * that changes — the calendar stays a calendar, because swapping the picture
 * a second after the page settles reads as a page correcting itself.
 *
 * Sky blue until the forecast answers, and quietly left alone if it does not:
 * a hero that goes grey to announce it could not reach the weather has made
 * the failure louder than anything on the card. */
(function heroWearsTheSky() {
    const hero = document.getElementById('schHero');
    if (!hero || !window.wxKeyFor) return;

    fetch(@json(route('app.weather')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((r) => (r.ok ? r.json() : null))
        .then((j) => {
            // Same shape the dashboard reads: locations keyed, each with a
            // run of days. The first farm that answered is the one standing
            // in the weather.
            const locs = (j && j.data && j.data.locations) || {};
            const first = Object.values(locs).find((l) => l && l.ok !== false && (l.days || []).length);
            const today = first && first.days[0];
            if (!today) return;
            const hour = new Date().getHours();
            const night = hour < 6 || hour >= 18;
            const key = window.wxKeyFor(today.code, night, today.max);
            const hue = window.wxHue ? window.wxHue(key) : '';
            if (!hue) return;
            hero.className = hero.className.replace(/\bfs-hue-\S+/g, '').trim() + ' ' + hue;

            /* And the weather itself, drifting across the card behind
             * everything on it. The tint says roughly; this says which.
             * Nothing on the card moves to make room — it appears behind, so
             * the page reads as finishing rather than as changing its mind. */
            /* And what time of day it is, which the sky key only half says.
             * A clear noon and a clear five o'clock are the same key and not
             * the same light, and the card is meant to read like a window. */
            const sky = document.getElementById('schHeroSky');
            if (sky && window.wxAtmosphere) {
                /* PAINTED NOW, SHOWN A SECOND LATER.
                 *
                 * The weather's animations start the moment it exists — but
                 * the first second of a drift, a squall and a dozen clouds all
                 * easing out of their own start points is the least convincing
                 * second of it, and that is exactly the second it used to
                 * arrive on.
                 *
                 * So it is painted straight away and simply not shown yet. It
                 * runs at zero opacity over the card's own ground — no cover,
                 * nothing to match, nothing to shift — and fades up once it is
                 * properly going.
                 */
                sky.innerHTML = window.wxAtmosphere(key, window.wxDaypart && window.wxDaypart(hour));
                setTimeout(() => sky.classList.add('is-on'), 1000);
            }
            const meta = (window.WX_SKIES || {})[key];
            const mark = document.getElementById('schHeroMark');
            if (mark && meta && meta.label) {
                mark.setAttribute('title', meta.label + ' — your seasons, day by day');
            }
        })
        .catch(() => {});
})();
</script>
@endpush
