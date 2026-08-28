@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your farm at a glance')

{{-- Reuse the shared community design system so the avatars / hues on this
     page match the plaza exactly (extend the partial, don't reinvent). --}}
@push('head')
@include('community.partials.plaza-css')
<style>
    /* Feed + sidebar shell (mirrors the wall page): the co-farmer feed is the
       main column, AI + discussions ride a sticky rail on wide screens and fold
       below the feed on tablet/mobile. */
    .dash-shell { display: grid; grid-template-columns: 1fr; gap: 1.25rem; align-items: start; }
    @media (min-width: 1024px) {
        .dash-shell { grid-template-columns: minmax(0, 1fr) 20rem; }
        .dash-side { position: sticky; top: 5rem; }
    }
    /* The forecast panel: a tinted, drifting box rather than a row of
       emoji on the card's own white. The tint is today's sky said in
       colour and is held under a fifth of an alpha, because the numbers
       have to stay louder than the mood. */
    .dash-wx-panel { padding: .6rem .7rem .5rem; }
    .dash-wx-place { font-size: .688rem; font-weight: 700; color: var(--color-gray-600);
        margin-bottom: .4rem; }
    .dash-wx-place b { font-weight: 800; color: var(--color-gray-800); }
    .dash-wx-art { display: flex; align-items: center; justify-content: center;
        height: 1.9rem; margin: .15rem 0; }
    .dash-wx-advice { font-size: .656rem; line-height: 1.5; color: var(--color-gray-600);
        margin-top: .5rem; padding-top: .45rem;
        border-top: 1px dashed rgb(148 163 184 / .35); }
    html.dark .dash-wx-place { color: #b7c2ad; }
    html.dark .dash-wx-place b { color: #e8efe1; }
    html.dark .dash-wx-advice { color: #b7c2ad; border-top-color: rgb(148 163 184 / .2); }

    /* Weather: shimmering skeleton while it loads, then the forecast fades up. */
    :root { --wx-sk1: #edf0f3; --wx-sk2: #dfe4ea; }
    html.dark { --wx-sk1: #1b2417; --wx-sk2: #27331d; }
    .wx-loading { margin-top: .5rem; padding-top: .5rem; border-top: 1px solid var(--color-gray-100); }
    html.dark .wx-loading { border-color: #2b3a1c; }
    .wx-skel { background: linear-gradient(90deg, var(--wx-sk1) 25%, var(--wx-sk2) 50%, var(--wx-sk1) 75%);
        background-size: 200% 100%; border-radius: .5rem; animation: wxShimmer 1.3s linear infinite; }
    .wx-skel-line { height: .72rem; width: 45%; border-radius: 999px; margin-bottom: .55rem; }
    .wx-skel-row { display: flex; gap: .3rem; }
    .wx-skel-cell { flex: 1 1 0; height: 3.7rem; }
    @keyframes wxShimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    .dash-wx.wx-ready > * { animation: wxReveal .45s cubic-bezier(.22, 1, .36, 1) both; }
    @keyframes wxReveal { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .wx-skel { animation: none; opacity: .6; }
        .dash-wx.wx-ready > * { animation: none; }
    }
    /* "Today" weather cell — no box, just green text (the theme doesn't remap
       the green ramp, so both modes are set explicitly here). */
    .wx-today-label { color: #15803d; }
    html.dark .wx-today-label { color: #86efac; }

    /* ---- the greeting and the account's numbers, in the same calm
       language as the schedules shelf: a white card, one green hairline,
       the hour drawn rather than shouted. ---- */
    /* A column for the hour, a column for the words.
     *
     * As a wrapping flex row the badge took a whole line of its own on a
     * phone, with the greeting under it and the plan chip adrift on a third
     * line at the far right — three ragged rows for two facts. A grid keeps
     * the badge beside what it belongs to at every width, and lets the chip
     * fall in under the words rather than off to the side of nothing. */
    /* The padding is held in variables because the cover band has to bleed
       back out through it to the card's own edges, and it changes on a
       phone — one place to read it from, so the two can never disagree. */
    /* The greeting stands on the house green, moving.
       Not a flat panel: the gradient is oversized and rides the same slow
       gradSweep tide every accent in this app is on, so the colour under the
       words drifts the way weather does rather than sitting there. */
    .dash-hero { display: grid; grid-template-columns: auto minmax(0, 1fr);
        align-items: center; gap: .55rem .95rem;
        --dh-px: 1.35rem; --dh-py: 1.25rem;
        padding: var(--dh-py) var(--dh-px); border-radius: 1.1rem; position: relative; overflow: hidden;
        background-image: linear-gradient(118deg, #f2f8ec, #e2f0d2 22%, #cfe6b6 46%, #e8f4dc 66%, #dceecb 84%, #f2f8ec);
        background-size: 260% 100%;
        animation: gradSweep 14s ease-in-out infinite alternate;
        border: 1px solid #cfe0b8; }
    @media (prefers-reduced-motion: reduce) { .dash-hero { animation: none; } }
    .dash-hero-mark { grid-column: 1; grid-row: 1 / -1; align-self: center;
        width: 3rem; height: 3rem; border-radius: 999px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        /* The circle is the window frame: the scene runs to its edges and is
           cut by it, rather than being a small glyph floating in a tint. */
        overflow: hidden; }
    .tod-svg { width: 100%; height: 100%; display: block; }
    .dash-hero-body { grid-column: 2; min-width: 0; }
    /* The scene paints its own sky, so the tints these classes used to carry
       would only show through the corners the circle already cuts off. They
       stay as hooks — the markup and a future scene may want them. */
    .dash-hero-h { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800;
        color: #24380f; line-height: 1.2; letter-spacing: -.01em; }
    .dash-hero-p { font-size: .82rem; color: #4c6b33; margin-top: .2rem; }
    /* Enough air that the greeting reads as a welcome rather than a header. */
    .dash-hero-mark { width: 3.35rem; height: 3.35rem; }

    /* ---- The sky, moving ------------------------------------------------
       Every part is a group with one animation on it. Nothing is scripted,
       and everything stops when stillness is asked for — a scene that keeps
       turning behind somebody who has said no to motion is the one place a
       nice idea becomes a rude one. */
    .tod-rays { transform-origin: 28px 34px; animation: todSpin 26s linear infinite; }
    .tod-rays-fast { transform-origin: 28px 24px; animation-duration: 18s; }
    /* Dawn: the sun comes up, and the light warms as it does. */
    .tod-rise { animation: todRise 7s ease-in-out infinite alternate; }
    @keyframes todRise { from { transform: translateY(3.5px); } to { transform: translateY(-1px); } }
    @keyframes todSpin { to { transform: rotate(360deg); } }
    /* Clouds cross and come round again. The two at night run at different
       speeds, which is the whole of the depth. */
    .tod-cloud-a { animation: todDrift 15s linear infinite; }
    .tod-cloud-n1 { animation: todDrift 19s linear infinite; }
    .tod-cloud-n2 { animation: todDrift 27s linear infinite; animation-delay: -9s; }
    @keyframes todDrift { from { transform: translateX(-24px); } to { transform: translateX(60px); } }
    .tod-moon { transform-origin: 30px 24px; animation: todBreathe 6s ease-in-out infinite; }
    @keyframes todBreathe { 0%, 100% { opacity: .96; } 50% { opacity: 1; } }
    .tod-star { animation: todTwinkle 3.4s ease-in-out infinite; }
    .tod-s2 { animation-delay: .8s; } .tod-s3 { animation-delay: 1.6s; } .tod-s4 { animation-delay: 2.4s; }
    @keyframes todTwinkle { 0%, 100% { opacity: .28; } 50% { opacity: 1; } }
    .tod-bird { animation: todFly 11s linear infinite; }
    @keyframes todFly { from { transform: translate(-14px, 4px); } to { transform: translate(48px, -6px); } }
    @media (prefers-reduced-motion: reduce) {
        .tod-rays, .tod-rise, .tod-cloud, .tod-moon, .tod-star, .tod-bird { animation: none !important; }
    }
    /* The hour's badge floats — a slow bob, the way the sun hangs in the
       sky rather than sits on a shelf. */
    .dash-hero-mark { animation: heroBob 3.8s ease-in-out infinite alternate; }
    /* A weather scene fills the same circle the hour's did — the mark is a
       window onto the sky either way, and the frame does not change. */
    .dash-hero-mark.is-weather .wx-sky { width: 100%; height: 100%; }
    @keyframes heroBob { from { transform: translateY(2px); } to { transform: translateY(-3px); } }
    @media (prefers-reduced-motion: reduce) { .dash-hero-mark { animation: none; } }
    .dash-hero-warn { display: inline-flex; align-items: center; gap: .3rem; margin-top: .35rem;
        font-size: .78rem; font-weight: 700; color: #b45309; }
    .dash-hero-warn svg { width: .85rem; height: .85rem; }
    /* Under the words on a phone, beside them once there is room. */
    .dash-hero-state { grid-column: 2; justify-self: start;
        display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    /* The rank chip, dressed to the dash-chip's measurements so the pair
       reads as one row of facts. The arc colours stay. */
    .dash-hero-state .rankb { padding: .35rem .75rem; font-size: .74rem; max-width: 14rem;
        line-height: 1.5; /* the dash-chip's, so the pair stands one height */ }
    .dash-hero-state .rankb .rankb-e { font-size: .85rem; }
    @media (min-width: 640px) {
        .dash-hero { grid-template-columns: auto minmax(0, 1fr) auto; }
        .dash-hero-state { grid-column: 3; grid-row: 1 / -1; justify-self: end; align-self: center;
            flex-direction: column; align-items: flex-end; }
    }
    /* --- Your own field, across the top of your own greeting ---
       A row of its own at the head of the hero's grid, bled back out through
       the card's padding to its edges; everything else drops one row and the
       hour's badge stands half over the photo's lower edge, the way a face
       stands on a profile. Only drawn when there is a cover to draw, so the
       rows below only move for somebody who has chosen a picture. */
    .dash-hero-cover { grid-column: 1 / -1; grid-row: 1; position: relative;
        margin: calc(var(--dh-py) * -1) calc(var(--dh-px) * -1) 0; }
    /* No wash over the photograph any more: the moving green now lives
       BEHIND the greeting, where it is decoration rather than something laid
       over somebody's own field. The cover shows what it shows. */
    /* The greeting keeps its distance from the photo's lower edge — the same
       clearance a post's name gets under the same band. */
    .dash-hero:has(.dash-hero-cover) { row-gap: .6rem; }
    /* The hour sits on the greeting's own midline, not half-buried in the
       photo above it. Standing on the cover's edge it was reading as part of
       the picture and left the two lines of words hanging off its shoulder;
       centred against them it is what it always was — a mark beside the
       sentence it belongs to. */
    .dash-hero:has(.dash-hero-cover) .dash-hero-mark { grid-row: 2; align-self: center;
        margin-top: 0; position: relative; }
    .dash-hero:has(.dash-hero-cover) .dash-hero-body { grid-column: 2; grid-row: 2; }
    .dash-hero:has(.dash-hero-cover) .dash-hero-state { grid-column: 2; grid-row: 3; }
    @media (min-width: 640px) {
        .dash-hero:has(.dash-hero-cover) .dash-hero-state { grid-column: 3; grid-row: 2; align-self: center; }
    }
    .dash-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem;
        border-radius: 999px; font-size: .74rem; font-weight: 700;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .dash-chip.is-ok { background: #f0f7e8; border-color: #cfe3b8; color: #3d6823; }
    .dash-chip.is-warn { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    /* Dark keeps the same idea a few stops down: still a light-ON-dark wash
       rather than a slab, still moving. */
    html.dark .dash-hero { border-color: #2b3a1c;
        background-image: linear-gradient(118deg, #18200f, #22301a 22%, #2c3f21 46%, #24331b 66%, #1d2714 84%, #18200f); }
    html.dark .dash-hero-h { color: #e8efe1; }
    html.dark .dash-hero-p { color: #a8bd93; }
    html.dark .dash-hero-warn { color: #fdba74; }
    html.dark .dash-chip { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .dash-chip.is-ok { background: rgb(61 104 35 / .22); border-color: #3f5626; color: #bfe19a; }
    html.dark .dash-chip.is-warn { background: rgb(154 52 18 / .2); border-color: rgb(154 52 18 / .5); color: #fdba74; }

    /* ---- what a season has next ------------------------------------
       A heading that says when and how much, then the tasks themselves on
       a rail. A day with five jobs used to be five inches of card; it is
       now one card you push sideways. ---- */
    /* min-width: 0 at every link in the chain. A grid item and a flex item
       both default to min-width: auto, which means "never narrower than your
       content" — so one long task name, one unbroken lot list, or a rail of
       cards propagates all the way up and the whole page scrolls sideways.
       The rail scrolls; nothing above it should. */
    .dn-block { border-radius: .9rem; padding: .6rem .65rem .65rem; background: var(--color-gray-50); min-width: 0; }
    .dn-block.is-today { background: #f0f7e8; }
    .dn-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .5rem; }
    .dn-when { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-width: 3rem; padding: .25rem .4rem; border-radius: .55rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .dn-when b { font-size: .8rem; font-weight: 800; line-height: 1.1; color: var(--color-gray-700); }
    .dn-when i { font-style: normal; font-size: .58rem; font-weight: 700; color: var(--color-gray-400); }
    .dn-block.is-today .dn-when { background: #4a7c2a; border-color: #4a7c2a; }
    .dn-block.is-today .dn-when b { color: #fff; }
    .dn-block.is-today .dn-when i { color: rgb(255 255 255 / .85); }
    .dn-headtxt { min-width: 0; flex: 1 1 auto; display: flex; flex-direction: column; line-height: 1.2; }
    .dn-headtxt b { font-size: .8rem; font-weight: 800; color: var(--color-gray-800); }
    .dn-headtxt i { font-style: normal; font-size: .68rem; color: var(--color-gray-500); }
    .dn-all { flex: 0 0 auto; display: inline-flex; align-items: center; gap: .2rem; font-size: .7rem;
        font-weight: 800; color: #3d6823; text-decoration: none; }
    .dn-all svg { width: .8rem; height: .8rem; }

    /* The rail: one whole task per view, swiped on a phone, arrowed on a
       mouse. `min-width: 0` is load-bearing — a flex child that scrolls its
       own overflow still reports its content width to the parent unless it
       is told it may be narrower, and this one was quietly widening the page
       until the whole dashboard scrolled sideways. */
    .dn-slider { position: relative; min-width: 0; }
    .dn-rail { display: flex; gap: .5rem; overflow-x: auto; scroll-snap-type: x mandatory;
        min-width: 0; padding-bottom: .15rem; scrollbar-width: none; -ms-overflow-style: none;
        scroll-behavior: smooth;
        /* none, not contain: contain only stops the pull reaching the page,
           while the rail still drags elastically past its own ends — which
           inside a card reads as the card stretching. */
        overscroll-behavior-x: none; }
    .dn-rail::-webkit-scrollbar { display: none; }
    /* A whole card, every time. Half a card at the edge looks like a bug. */
    .dn-card { flex: 0 0 100%; min-width: 0; scroll-snap-align: center; scroll-snap-stop: always;
        display: flex; flex-direction: column;
        gap: .25rem; padding: .6rem .7rem .65rem; border-radius: .7rem; text-decoration: none;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        border-left: 3px solid var(--dn-prio, #d1d5db);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .dn-card:hover { transform: translateY(-1px); }
    .dn-slider.is-single .dn-rail { overflow-x: visible; scroll-snap-type: none; }
    .dn-slider.is-single .dn-arrow { display: none; }

    /* The arrows only exist while there is somewhere to go, and they say so
       by arriving rather than by appearing. */
    .dn-arrow { position: absolute; top: 50%; z-index: 2; width: 1.9rem; height: 1.9rem;
        display: flex; align-items: center; justify-content: center; border-radius: 999px;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        box-shadow: 0 4px 14px -6px rgb(0 0 0 / .45); color: var(--color-gray-600);
        opacity: 0; pointer-events: none; transform: translateY(-50%) scale(.8);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .dn-arrow svg { width: .9rem; height: .9rem; }
    /* Two conditions, both required: the list is being moved, and there is
       something in that direction to move to. */
    .dn-slider.is-live .dn-arrow.can-go { opacity: 1; pointer-events: auto; transform: translateY(-50%) scale(1); }
    .dn-arrow:hover { color: #3d6823; border-color: #a8cc7e; }
    .dn-prev { left: -.35rem; }
    .dn-next { right: -.35rem; }
    html.dark .dn-arrow { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    .dn-card.prio-critical { --dn-prio: #9c1c1c; }
    .dn-card.prio-high { --dn-prio: #f46a6a; }
    .dn-card.prio-medium { --dn-prio: #f1b44c; }
    .dn-card.prio-low { --dn-prio: #cbd5e1; }
    .dn-card-top { display: flex; align-items: center; gap: .35rem; min-width: 0; }
    .dn-type { font-size: .6rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        color: var(--color-gray-400); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dn-prio { margin-left: auto; flex: none; font-size: .58rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; padding: .1rem .4rem; border-radius: 999px;
        background: color-mix(in srgb, var(--dn-prio) 18%, transparent); color: var(--dn-prio); }
    .dn-title { font-size: .84rem; font-weight: 700; line-height: 1.35; color: var(--color-gray-900);
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dn-facts { display: flex; flex-wrap: wrap; gap: .1rem .6rem; margin-top: .1rem; }
    .dn-fact { display: inline-flex; align-items: center; gap: .22rem; min-width: 0;
        font-size: .66rem; font-weight: 600; color: var(--color-gray-500); }
    .dn-fact svg { width: .7rem; height: .7rem; flex: none; }
    .dn-fact { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dn-quiet { font-size: .78rem; color: var(--color-gray-400); }
    html.dark .dn-block { background: rgb(255 255 255 / .04); }
    html.dark .dn-block.is-today { background: rgb(61 104 35 / .22); }
    html.dark .dn-when { background: #151b12; border-color: #2b3a1c; }
    html.dark .dn-when b { color: #e8efe1; }
    html.dark .dn-headtxt b { color: #e8efe1; }
    html.dark .dn-all { color: #a5c97e; }
    html.dark .dn-card { background: #151b12; border-color: #2b3a1c; border-left-color: var(--dn-prio, #3f4a37); }
    html.dark .dn-title { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .dn-card, .dn-arrow { transition: none; }
        .dn-rail { scroll-behavior: auto; }
    }

    /* ---- a season on the home page folds too -------------------------
       Same idea as the schedules page: the name stays, the working detail
       goes. The transition is armed only while a fold is happening —
       grid-template-rows: 1fr resolves against content, so a permanently
       transitioned row re-animates on every relayout the card has. */
    /* The schedules page's tilled-soil cover, worn here too: the same
       ground, the same slow drift on the shared gradSweep tide, each card
       on its own clock — so a season looks like the same season on both
       shelves. Bled to the card's edges (the body wears 1rem of padding)
       and rounded into its top corners. */
    .ds-head { position: relative; display: flex; align-items: center; gap: .5rem;
        width: calc(100% + 2rem); text-align: left; border: none; cursor: pointer;
        margin: -1rem -1rem 0; padding: .55rem 1rem;
        /* The schedules page's covers stand 58px tall (their status pill sets
           it); this head holds no pill, so the height is named instead —
           a folded season then closes to the same 60px on both shelves. */
        min-height: 3.625rem;
        /* The card is rounded-2xl (1rem); minus its 1px border. */
        border-radius: calc(1rem - 1px) calc(1rem - 1px) 0 0;
        background: linear-gradient(120deg, #f4e9dc, #dfc9ac 42%, #cbb08c 68%, #ecdfcd);
        background-size: 220% 100%;
        animation: gradSweep var(--sw-t, 13s) ease-in-out infinite alternate;
        animation-delay: var(--sw-d, 0s);
        transition: margin-bottom .28s cubic-bezier(.22,1,.36,1), border-radius .28s cubic-bezier(.22,1,.36,1); }
    /* Folded, the cover IS the card: it swallows the body's bottom padding
       too and rounds all four corners, instead of leaving a strip of bare
       card under the soil. */
    .ds-card.is-folded .ds-head { margin-bottom: -1rem; border-radius: calc(1rem - 1px); }
    .ds-card.is-folded .ds-head::after { display: none; }
    /* The faint horizon line that makes the tint read as ground. */
    .ds-head::after { content: ''; position: absolute; inset: auto 0 0 0; height: 1rem;
        background: linear-gradient(180deg, transparent, rgb(0 0 0 / .05)); pointer-events: none; }
    html.dark .ds-head {
        background: linear-gradient(120deg, #2a2018, #3a2c1e 42%, #4a3826 68%, #2f241a);
        background-size: 220% 100%; }
    @media (prefers-reduced-motion: reduce) { .ds-head { animation: none; transition: none; } }
    /* se-crops' measurements, so the same season shows the same face. */
    .ds-crops { font-size: 1.7rem; line-height: 1; letter-spacing: .1em; position: relative; z-index: 1;
        flex-shrink: 0; filter: drop-shadow(0 2px 3px rgb(0 0 0 / .12)); }
    @media (max-width: 767px) { .ds-crops { font-size: 1.45rem; } }
    .ds-head h3 { flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        position: relative; z-index: 1; text-shadow: 0 1px 0 rgb(255 255 255 / .5); }
    html.dark .ds-head h3 { text-shadow: none; }
    .ds-chev { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px;
        display: flex; align-items: center; justify-content: center; position: relative; z-index: 1;
        color: var(--color-gray-500); background: rgb(255 255 255 / .75);
        transition: transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .ds-chev svg { width: .8rem; height: .8rem; }
    .ds-head:hover .ds-chev { color: #3d6823; }
    .ds-card.is-folded .ds-chev { transform: rotate(-90deg); }
    /* The space between the name and the detail belongs to the detail: flex
       `gap` stands its ground even beside a zero-height row, which left every
       folded season wearing an empty strip under its title. As ds-body
       padding it lives inside the fold, so the closing row clips it — and the
       fold animation carries it, instead of the gap snapping away after. */
    .ds-card .card-body { gap: 0; }
    .ds-body { padding-top: .85rem; }
    .ds-fold-wrap { display: grid; grid-template-rows: 1fr; min-height: 0; }
    .ds-card.is-folding .ds-fold-wrap { transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .ds-card.is-folded .ds-fold-wrap { grid-template-rows: 0fr; }
    /* Padding is incompressible: a 0fr row still stands as tall as its
       child's padding-top, so a folded card wore a 12px ghost strip under
       the title — more below the name than above it. Folded, the padding
       goes too, and the card closes around the name evenly. */
    .ds-card.is-folded .ds-body { padding-top: 0; }
    .ds-fold-wrap > * { min-height: 0; overflow: hidden; }
    .ds-card.is-folded { align-self: start; }
    html.dark .ds-chev { background: rgb(0 0 0 / .45); color: #d5dfc9; }
    @media (prefers-reduced-motion: reduce) {
        .ds-chev, .ds-card.is-folding .ds-fold-wrap { transition: none; }
    }

    /* Your face on the composer, with what's on your mind above it — the
       same cloud, in the same place, as everywhere else in the community.
       The shared .status-cloud does the shape; this only makes it yours:
       clickable, and inviting while it is empty. */
    .dash-me { position: relative; border: none; background: none; padding: 0; cursor: pointer;
        max-width: 3.5rem; }
    /* The cloud is decoration everywhere else, so the shared rule turns
       pointer events off. Here it is half the button. */
    .dash-me .status-cloud { pointer-events: auto; max-width: 9rem;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .dash-me:hover .status-cloud { transform: translateY(-1px); box-shadow: 0 6px 16px rgb(0 0 0 / .18); }
    /* Nothing said yet: a dashed invitation rather than a statement. */
    .dash-me .status-cloud.is-empty { background: var(--color-brand-50);
        border-color: var(--color-brand-300); border-style: dashed; }
    .dash-me .status-cloud.is-empty .status-cloud-text { color: var(--color-brand-700); font-weight: 700; }
    .dash-me .status-cloud.is-empty::after { border-top-color: var(--color-brand-50); }
    /* Room for the cloud to sit above the avatar without meeting the card. */
    .dash-comp-row { padding-top: 1.35rem; }
    @media (prefers-reduced-motion: reduce) { .dash-me .status-cloud { transition: none; } }

    /* The composer: a field you can see yourself writing in. */
    .dash-comp { padding: .85rem !important; }
    .dash-comp-box { min-height: 6.5rem; font-size: .85rem; line-height: 1.55; resize: vertical; }
    .dash-comp-box::placeholder { font-size: .82rem; }

    .dash-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .dash-stat { display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .1rem; padding: .8rem .6rem; border-radius: .9rem; text-align: center; text-decoration: none;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    a.dash-stat:hover { border-color: #cfe3b8; transform: translateY(-1px); }
    .dash-stat b { font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.1; }
    .dash-stat .dash-stat-word { font-size: .95rem; }
    .dash-stat i { font-style: normal; font-size: .62rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: var(--color-gray-400); }
    .dash-stat.is-lead { background: #f0f7e8; border-color: #cfe3b8; }
    .dash-stat.is-lead b { color: #3d6823; }
    .dash-stat.is-lead i { color: #6b9f3d; }
    .dash-stat.is-warn b { color: #c2410c; }
    html.dark .dash-stat { background: #151b12; border-color: #2b3a1c; }
    html.dark .dash-stat b { color: #e8efe1; }
    html.dark .dash-stat.is-lead { background: rgb(61 104 35 / .22); border-color: #3f5626; }
    html.dark .dash-stat.is-lead b { color: #bfe19a; }
    @media (max-width: 480px) {
        .dash-hero { --dh-px: 1rem; --dh-py: .9rem; gap: .7rem; }
        .dash-hero-state { margin-left: 0; flex-basis: 100%; }
        .dash-stat b { font-size: 1.15rem; }
    }
    @media (prefers-reduced-motion: reduce) { .dash-stat { transition: none; } }
    {{-- .wall-act composer buttons live in plaza-css (shared). --}}
    /* The wall composer's own bar and shot tiles, borrowed line for line
       (feed.blade.php keeps the originals) so the two sheets read as one
       form. */
    .comp-shots { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.6rem; }
    .comp-shots.hidden { display:none; }
    .comp-shot-one { position:relative; width:4.5rem; height:4.5rem; border-radius:.6rem;
        overflow:hidden; background:var(--color-gray-100); flex:none; }
    .comp-shot-one img { width:100%; height:100%; object-fit:cover; display:block; }
    .comp-shot-one button { position:absolute; top:.15rem; right:.15rem; width:1.35rem; height:1.35rem;
        border:0; border-radius:999px; cursor:pointer; display:flex; align-items:center; justify-content:center;
        background:rgb(17 24 39 / .62); color:#fff; font-size:.7rem; line-height:1; }
    .comp-shot-one button:hover { background:rgb(185 28 28 / .9); }
    html.dark .comp-shot-one { background:rgb(255 255 255 / .06); }
    /* A clip's tile: the shot tile gone dark, wearing the clapperboard. */
    .comp-shot-one.is-clip { background:#10131a; }
    .comp-shot-one.is-clip::after { content:'\1F3AC'; position:absolute; inset:0; display:flex;
        align-items:center; justify-content:center; font-size:1.15rem; pointer-events:none;
        text-shadow:0 1px 4px rgb(0 0 0 / .6); }
    .comp-shot-one.is-clip img { opacity:.85; }

    .comp-top { display:flex; align-items:flex-start; gap:.75rem; margin-bottom:.7rem; }
    /* Two lines of text against a taller face read as hanging from its
       crown; centred, the pair sits level. */
    .comp-top > .min-w-0 { align-self: center; }
    .comp-add { margin-top:.55rem; display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
    .comp-add-box { padding:.35rem .5rem .35rem .7rem; border-radius:.8rem;
        border:1px solid var(--color-gray-200); background:var(--color-gray-50); }
    html.dark .comp-add-box { border-color:rgb(255 255 255 / .08); background:rgb(255 255 255 / .03); }
    .comp-add-lbl { font-size:.72rem; font-weight:800; color:var(--color-gray-500); }
    .comp-add-row { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; margin-left:auto; }
    .comp-add-row .wall-act { width:2.15rem; height:2.15rem; border-radius:.6rem;
        display:inline-flex; align-items:center; justify-content:center; }
    /* The bar over the homepage wall: what you came to do, then a word about
       where you are. The same shape the community wall opens with — said
       again here rather than shared, because it is four declarations and the
       wall keeps its copy in its own page. */
    .wall-bar { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; }
    .wb-act { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0; }
    .wb-hint { margin-left: auto; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    @media (max-width: 599px) { .wb-hint { display: none; } }
</style>
@endpush

@section('content')
@php
    $isSuperAdmin = $user->isSuperAdmin();   // mother-site admin — full access, no plan needed
    $status = $subscription?->effective_status;
    $daysRemaining = $subscription?->daysRemaining();
    $isActive = $status === \App\Models\Subscription::STATUS_ACTIVE;
    $expiringSoon = $isActive && $daysRemaining !== null && $daysRemaining <= 7;

    $scheduleBadge = fn (?string $s) => match ($s) {
        'draft' => ['badge-gray', 'Draft'],
        'setup' => ['badge-yellow', 'Setting up'],
        'generated' => ['badge-green', 'Generated'],
        'completed' => ['badge-blue', 'Completed'],
        'archived' => ['badge-gray', 'Archived'],
        default => ['badge-gray', ucfirst((string) $s)],
    };

    $snippet = fn (?string $body, int $len = 90) => \Illuminate\Support\Str::limit(strip_tags((string) $body), $len);
@endphp

<div class="space-y-5 md:space-y-6">

    {{-- The greeting, in the same language as every other page: a calm card
         with the hour drawn on it, not a slab of green. --}}
    @php
        $__h = (int) now('Asia/Manila')->format('G');
        [$__greet, $__tod, $__timeWord] = $__h < 12
            ? ['Magandang umaga', 'tod-morning', 'umaga']
            : ($__h < 18
                ? ['Magandang hapon', 'tod-afternoon', 'hapon']
                : ['Magandang gabi', 'tod-evening', 'gabi']);
    @endphp
    <div class="dash-hero">
        {{-- Your own field across the top of your own greeting, with the hour
             standing half over its edge the way a face stands on a profile.
             Only if you have set a cover: the home screen is the first thing
             this app shows anybody, and a band of house green there would
             cost every farmer without a picture a fifth of it to say nothing. --}}
        @if (filled($user->coverPath ?? null))
            @include('community.partials.cover-band', ['coverUser' => $user, 'coverClass' => 'dash-hero-cover'])
        @endif
        {{-- Not an icon of the hour — a window onto it.

             A glyph tells you what time it is, which the line underneath
             already does. A scene tells you what the day is like, which is
             the thing a farmer actually looks up to check. So: the sun climbs
             and turns at dawn, stands high and hot with a cloud crossing it
             at noon, and after six the moon sits behind clouds that drift
             over it while the stars come and go.

             Everything is inside a 56-unit box clipped by the circle it sits
             in, and every moving part is a CSS animation on a group — no
             JavaScript, and nothing that runs when somebody has asked for
             stillness. --}}
        <span class="dash-hero-mark {{ $__tod }}" id="dashHeroMark"
              data-time-word="{{ $__timeWord }}" data-night="{{ $__tod === 'tod-evening' ? 1 : 0 }}"
              aria-hidden="true">
            @if ($__tod === 'tod-morning')
                <svg class="tod-svg" viewBox="0 0 56 56" fill="none">
                    <defs>
                        <linearGradient id="todSkyM" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#ffe6ad"/><stop offset=".62" stop-color="#ffc98a"/>
                            <stop offset="1" stop-color="#ffb27a"/>
                        </linearGradient>
                    </defs>
                    <rect width="56" height="56" fill="url(#todSkyM)"/>
                    {{-- The sun climbs, and its light turns with it. --}}
                    <g class="tod-rise">
                        <g class="tod-rays">
                            <path d="M28 16v-7M28 53v7M9 34h-7M54 34h7M15.4 21.4l-5-5M40.6 46.6l5 5M45.6 21.4l5-5M10.4 46.6l-5 5"
                                  stroke="#fff3cf" stroke-width="3.4" stroke-linecap="round" opacity=".85"/>
                        </g>
                        <circle cx="28" cy="34" r="9.5" fill="#fff0bd"/>
                        <circle cx="28" cy="34" r="7.6" fill="#fbbf24"/>
                    </g>
                    {{-- The ground the sun comes up over. --}}
                    <path d="M0 41c9-3 16 2 27 1s20-5 29-2v16H0z" fill="#7fae57"/>
                    <path d="M0 45c10-2 17 2 28 1s18-4 28-2v12H0z" fill="#4a7c2a"/>
                    <g class="tod-bird"><path d="M12 15c1.6-1.8 3.2-1.8 4.6 0M18 12.6c1.4-1.6 2.8-1.6 4 0"
                        stroke="#8a5a2b" stroke-width="1.4" stroke-linecap="round" opacity=".7"/></g>
                </svg>
            @elseif ($__tod === 'tod-afternoon')
                <svg class="tod-svg" viewBox="0 0 56 56" fill="none">
                    <defs>
                        <linearGradient id="todSkyA" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#9fd2f5"/><stop offset="1" stop-color="#d7ecfb"/>
                        </linearGradient>
                    </defs>
                    <rect width="56" height="56" fill="url(#todSkyA)"/>
                    <g class="tod-rays tod-rays-fast">
                        <path d="M28 8V1M28 40v7M8 24H1M48 24h7M14.6 10.6l-5-5M41.4 37.4l5 5M41.4 10.6l5-5M14.6 37.4l-5 5"
                              stroke="#ffe9a8" stroke-width="3.4" stroke-linecap="round"/>
                    </g>
                    <circle cx="28" cy="24" r="9.5" fill="#ffe9a8"/>
                    <circle cx="28" cy="24" r="7.6" fill="#fbbf24"/>
                    {{-- One cloud crossing, because a sky with nothing moving
                         in it is a picture rather than a day. --}}
                    <g class="tod-cloud tod-cloud-a">
                        <path d="M6 30a4.4 4.4 0 0 1 .8-8.7A6.4 6.4 0 0 1 19 22.8a4.1 4.1 0 0 1 .6 7.2z" fill="#ffffff" opacity=".92"/>
                    </g>
                    <path d="M0 40c9-3 16 2 27 1s20-5 29-2v17H0z" fill="#7fae57"/>
                    <path d="M0 45c10-2 17 2 28 1s18-4 28-2v12H0z" fill="#4a7c2a"/>
                </svg>
            @else
                <svg class="tod-svg" viewBox="0 0 56 56" fill="none">
                    <defs>
                        <linearGradient id="todSkyE" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#1e2b52"/><stop offset="1" stop-color="#3d4a78"/>
                        </linearGradient>
                    </defs>
                    <rect width="56" height="56" fill="url(#todSkyE)"/>
                    <g fill="#fff3cf">
                        <circle class="tod-star tod-s1" cx="11" cy="12" r="1.5"/>
                        <circle class="tod-star tod-s2" cx="45" cy="9" r="1.2"/>
                        <circle class="tod-star tod-s3" cx="38" cy="20" r="1"/>
                        <circle class="tod-star tod-s4" cx="17" cy="24" r="1.1"/>
                    </g>
                    {{-- A crescent, made by taking a bite out of a disc — a
                         stroked arc goes to mush at this size. --}}
                    <path class="tod-moon" d="M34 8a14 14 0 1 0 10 22A16.5 16.5 0 0 1 34 8z" fill="#f2f4ff"/>
                    {{-- Two clouds, at different speeds, so the sky has depth
                         rather than one band sliding past. --}}
                    <g class="tod-cloud tod-cloud-n1">
                        <path d="M4 27a4.4 4.4 0 0 1 .8-8.7A6.4 6.4 0 0 1 17 19.8a4.1 4.1 0 0 1 .6 7.2z" fill="#8e9ac4" opacity=".75"/>
                    </g>
                    <g class="tod-cloud tod-cloud-n2">
                        <path d="M2 36a3.4 3.4 0 0 1 .6-6.7A5 5 0 0 1 12 30.4a3.2 3.2 0 0 1 .5 5.6z" fill="#b3bde0" opacity=".6"/>
                    </g>
                    <path d="M0 42c9-3 16 2 27 1s20-5 29-2v16H0z" fill="#2c3d1c"/>
                    <path d="M0 46c10-2 17 2 28 1s18-4 28-2v11H0z" fill="#1d2a13"/>
                </svg>
            @endif
        </span>
        <div class="dash-hero-body">
            {{-- "Magandang umaga" is true at six on any morning and says
                 nothing about this one. When the forecast for this farmer's
                 own location is in and confident, the first word is replaced
                 by one that has looked out of the window — "Maulang umaga",
                 "Mainit na hapon", "Mahanging gabi" — and the scene beside it
                 becomes that weather. Until then, and forever if no location
                 is set, it stays as it was. --}}
            <h2 class="dash-hero-h"><span id="dashGreetWord">{{ $__greet }}</span>, {{ \Illuminate\Support\Str::title($user->firstName ?: 'kaibigan') }}</h2>
            <p class="dash-hero-p">{{ now('Asia/Manila')->format('l, F j') }} — {{ $scheduleCount === 0 ? 'no seasons planned yet.' : $scheduleCount . ' ' . \Illuminate\Support\Str::plural('season', $scheduleCount) . ' on the shelf.' }}</p>
            @if ($expiringSoon)
                <a href="{{ route('purchase.plans') }}" class="dash-hero-warn">
                    Renew before your subscription expires
                    <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>
        <div class="dash-hero-state">
            {{-- Your standing in the community, sized like the chip beside
                 it: the same pill, the same type, one of the facts about
                 this account rather than a decoration on the name. It links
                 to the ladder so climbing is one tap from the greeting. --}}
            @include('community.partials.rank-badge', ['rankUser' => $user, 'rankChipLike' => true])
            {{-- And your seat, on the days you hold one. It is the fact most
                 worth seeing on your own greeting, because it is the one that
                 can be gone tomorrow. --}}
            @include('community.partials.top-badge', ['topUser' => $user])
            {{-- No chip for a super admin: admin power lives in the mother
                 site, and in here an admin is just another member. The
                 subscription facts still speak for everyone else. --}}
            @if ($isSuperAdmin)
            @elseif ($isActive)
                <span class="dash-chip {{ $expiringSoon ? 'is-warn' : 'is-ok' }}">
                    {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                </span>
            @elseif ($status === 'pending')
                <span class="dash-chip is-warn">Verification pending</span>
            @else
                <a href="{{ route('purchase.plans') }}" class="btn btn-primary btn-sm">Subscribe now</a>
            @endif
        </div>
    </div>

    {{-- What the account holds, as labelled tiles rather than three cards
         each shouting a different size of number. --}}
    <div class="dash-stats">
        <a href="{{ route('sm.index') }}" class="dash-stat is-lead">
            <b>{{ number_format($scheduleCount) }}</b>
            <i>{{ \Illuminate\Support\Str::plural('Schedule', $scheduleCount) }}</i>
        </a>
        <div class="dash-stat">
            <b class="dash-stat-word">{{ $isSuperAdmin ? 'Admin' : ($isActive ? $subscription->planName : '—') }}</b>
            <i>Active plan</i>
        </div>
        <div class="dash-stat {{ $expiringSoon ? 'is-warn' : '' }}">
            <b>{{ $isSuperAdmin ? '∞' : ($isActive && $daysRemaining !== null ? number_format($daysRemaining) : '—') }}</b>
            <i>Days left</i>
        </div>
    </div>

    {{-- The AI technician's one thing worth knowing today. It sat at the
         bottom of the schedules page, where a page of seasons is what people
         scroll past it to reach; here it is read on the way in. --}}
    @include('sm.partials.tip-of-day', [
        'tip' => $tip ?? null,
        'aiHref' => (($canUseAi ?? false) && $latestSchedules->isNotEmpty())
            ? route('sm.ai', ['id' => $latestSchedules->first()->id])
            : null,
    ])

    {{-- My Cropping Schedules (top — the primary workspace) --}}
    <div>
        <div class="flex items-center justify-between gap-3 mb-3 px-1">
            <h2 class="text-base md:text-lg font-bold text-gray-900">📅 My Cropping Schedules</h2>
            @if ($latestSchedules->isNotEmpty())
                <a href="{{ route('sm.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">View all</a>
            @endif
        </div>

        @if ($latestSchedules->isEmpty())
            <div class="card">
                <div class="card-body text-center py-10 md:py-14">
                    <svg class="w-24 h-24 mx-auto mb-4 text-brand-300" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3 2-5.5 5.5-5.5.2 2.8-1.6 5.5-5.5 5.5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3-2-5.5-5.5-5.5C6.3 10.3 8.1 13 12 13z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16"/>
                    </svg>
                    @if ($scheduleCount > 0)
                        <h3 class="text-lg font-bold text-gray-900">Nothing planned for {{ $shelfYear }}</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
                            This shelf shows the seasons with activities dated in {{ $shelfYear }}. Your other
                            schedules are all still there — open them to review, or start this year's.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-5">
                            <a href="{{ route('sm.index') }}" class="btn btn-white btn-lg">View all schedules</a>
                            <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">+ New Schedule</a>
                        </div>
                    @else
                        <h3 class="text-lg font-bold text-gray-900">Plant your first schedule</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-xs mx-auto">
                            Create a cropping schedule to plan your lots, workers, activities and irrigation for the season.
                        </p>
                        <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg mt-5">+ New Cropping Schedule</a>
                    @endif
                </div>
            </div>
        @else
            {{-- One active schedule fills the row; two or more go side by side. --}}
            <div class="grid gap-3 {{ $latestSchedules->count() > 1 ? 'sm:grid-cols-2' : 'grid-cols-1' }}">
                @foreach ($latestSchedules as $schedule)
                    @php
                        [$sBadge, $sLabel] = $scheduleBadge($schedule->status);
                        $next = $scheduleNext[$schedule->id] ?? null;
                    @endphp
                    {{-- Folds the same way the schedules page folds, and
                         remembers it in the same place, so a season you put
                         away stays away wherever you meet it. --}}
                    <div class="card card-hover min-w-0 ds-card" data-schedule-card="{{ $schedule->id }}"
                         style="--sw-t:{{ 10 + ($schedule->id % 7) }}s;--sw-d:-{{ $schedule->id % 11 }}s">
                        <div class="card-body !p-4 flex flex-col gap-3 h-full min-w-0">
                            <button type="button" class="ds-head" data-ds-fold aria-expanded="true"
                                    aria-label="Fold or unfold {{ $schedule->title }}">
                                {{-- The crops growing on this season, exactly as
                                     the schedules page stands them on its
                                     covers. --}}
                                <span class="ds-crops" aria-hidden="true">{{ count($scheduleCrops[$schedule->id] ?? []) ? implode('', $scheduleCrops[$schedule->id]) : '🌱' }}</span>
                                <h3 class="font-bold text-gray-900 leading-snug min-w-0">{{ $schedule->title }}</h3>
                                <span class="ds-chev" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div class="ds-fold-wrap">
                            <div class="ds-body flex flex-col gap-3 h-full min-w-0">

                            {{-- What is next on THIS season: today's work, or
                                 the nearest day that has any. The day is a
                                 heading; the tasks themselves slide, so four
                                 jobs do not become four inches of card. --}}
                            @if ($next)
                                <div class="dn-block {{ $next['isToday'] ? 'is-today' : '' }}">
                                    <div class="dn-head">
                                        <span class="dn-when">
                                            <b>{{ $next['isToday'] ? 'Today' : $next['date']->format('D') }}</b>
                                            <i>{{ $next['date']->format('M j') }}</i>
                                        </span>
                                        <span class="dn-headtxt">
                                            <b>{{ $next['activities']->count() }} {{ \Illuminate\Support\Str::plural('task', $next['activities']->count()) }}</b>
                                            <i>@if ($next['isToday']) due today @else in {{ $next['daysAway'] }} {{ \Illuminate\Support\Str::plural('day', $next['daysAway']) }} @endif</i>
                                        </span>
                                        <a class="dn-all" href="{{ route('sm.activities', ['id' => $schedule->id]) }}">
                                            Open board
                                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>

                                    {{-- One task is not a slider. More than one
                                         slides a whole card at a time, so the
                                         next is never half-shown — a card cut
                                         off at the edge reads as a rendering
                                         fault, not as an invitation. --}}
                                    <div class="dn-slider{{ $next['activities']->count() > 1 ? '' : ' is-single' }}" data-dn-slider>
                                        <button type="button" class="dn-arrow dn-prev" data-dn-prev aria-label="Previous task">
                                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <button type="button" class="dn-arrow dn-next" data-dn-next aria-label="Next task">
                                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    <div class="dn-rail" data-dn-rail>
                                        @foreach ($next['activities'] as $act)
                                            @php
                                                $prio = $act->priority ?: 'medium';
                                                $typeLabel = \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$act->activityType] ?? null;
                                                $hours = match ($act->timeRequired) {
                                                    'whole' => 'Whole day',
                                                    'half' => 'Half day',
                                                    default => null,
                                                };
                                                $lotNames = $act->lots->pluck('lotName')->take(2)->implode(', ');
                                                $moreLots = max(0, $act->lots->count() - 2);
                                            @endphp
                                            <a class="dn-card prio-{{ $prio }}"
                                               href="{{ route('sm.activities', ['id' => $schedule->id]) }}">
                                                <span class="dn-card-top">
                                                    @if ($typeLabel)
                                                        <span class="dn-type">{{ $typeLabel }}</span>
                                                    @endif
                                                    <span class="dn-prio">{{ ucfirst($prio) }}</span>
                                                </span>
                                                {{-- Cut to one line; the whole name is still there to hover or read aloud. --}}
                                                <span class="dn-title" title="{{ $act->activityTitle }}">{{ $act->activityTitle }}</span>
                                                <span class="dn-facts">
                                                    @if ($lotNames)
                                                        <span class="dn-fact">
                                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                                                            {{ $lotNames }}@if ($moreLots) +{{ $moreLots }}@endif
                                                        </span>
                                                    @endif
                                                    @if ($act->workers_count)
                                                        <span class="dn-fact">
                                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                                                            {{ $act->workers_count }}
                                                        </span>
                                                    @endif
                                                    @if ($hours)
                                                        <span class="dn-fact">
                                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            {{ $hours }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                    </div>
                                </div>
                            @else
                                <p class="dn-quiet">Nothing planned on this season yet.</p>
                            @endif

                            {{-- Local weather for this schedule's lot location(s).
                                 Filled by JS from the deduped /app/weather feed. --}}
                            @if ($scheduleHasLocation[$schedule->id] ?? false)
                                <div data-weather-for="{{ $schedule->id }}" class="dash-wx">
                                    <div class="wx-loading" role="status" aria-label="Loading">
                                        <div class="wx-skel wx-skel-line"></div>
                                        <div class="wx-skel-row">
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="inline-flex items-center gap-1 text-[0.688rem] font-semibold text-brand-600 hover:text-brand-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Add a lot address for local weather
                                </a>
                            @endif

                            <div class="flex items-center justify-between gap-3 mt-auto pt-1">
                                <div class="min-w-0 flex items-center gap-2 flex-wrap">
                                    {{-- The shelf is ordered by this, so the card says it:
                                         "why is that one on top" should be answerable
                                         without opening either. Falls back to the day it
                                         was made, for a season nobody has touched since. --}}
                                    @php
                                        $touched = $schedule->lastTouchedAt
                                            ? \Illuminate\Support\Carbon::parse($schedule->lastTouchedAt)
                                            : $schedule->updated_at;
                                        // abs(): this Carbon returns a SIGNED difference, and
                                        // a season made in May came back as -147699 minutes,
                                        // which is under two and made every card say "Created".
                                        $madeToday = $touched && $schedule->created_at
                                            && abs($touched->diffInMinutes($schedule->created_at)) < 2;
                                    @endphp
                                    <p class="text-xs text-gray-500 shrink-0">
                                        @if ($touched && ! $madeToday)
                                            Updated {{ $touched->timezone('Asia/Manila')->diffForHumans() }}
                                        @else
                                            Created {{ $schedule->created_at?->format('M j, Y') }}
                                        @endif
                                    </p>
                                    <span class="badge {{ $sBadge }} shrink-0">{{ $sLabel }}</span>
                                </div>
                                <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}" class="btn btn-outline btn-sm shrink-0">Open</a>
                            </div>
                            </div>
                            </div>{{-- /.ds-fold-wrap --}}
                        </div>
                    </div>
                @endforeach
            </div>
            {{-- Starting a season belongs on the schedules page, which has
                 its own floating button for it and is one tap away. Here it
                 was a full-width green bar under every visit, for the one
                 errand nobody opens the dashboard to do. --}}
        @endif
    </div>

    {{-- ===================== Feed + sidebar shell ===================== --}}
    <div class="dash-shell">

        {{-- MAIN COLUMN: support (if any) + your co-farmers' wall feed --}}
        <div class="min-w-0 space-y-5 md:space-y-6">

            @if ($openTickets->isNotEmpty())
                <section class="card">
                    <div class="card-body !p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h2 class="text-sm md:text-base font-bold text-gray-900">🛟 Open support tickets</h2>
                            <a href="{{ route('support.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">All tickets →</a>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($openTickets as $ticket)
                                @php $tAnswered = $ticket->status === 'answered'; $tWhen = $ticket->lastReplyAt ?? $ticket->created_at; @endphp
                                <a href="{{ route('support.show', ['id' => $ticket->id]) }}" class="block px-1 py-2.5 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="font-semibold text-gray-900 text-sm leading-snug min-w-0 line-clamp-1">{{ $ticket->subject }}</p>
                                        <span class="badge {{ $tAnswered ? 'badge-green' : 'badge-yellow' }} shrink-0">{{ $tAnswered ? 'Answered' : 'Open' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        @if ($ticket->category)<span class="font-semibold text-gray-500">{{ ucfirst($ticket->category) }}</span> · @endif
                                        Last reply {{ optional($tWhen)->diffForHumans() }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if (\App\Support\WorkerContext::canUseCommunity())
            <div>
                <div class="flex items-center justify-between gap-3 mb-3 px-1">
                    <h2 class="text-base md:text-lg font-bold text-gray-900">🏘️ Community Wall</h2>
                    <a href="{{ route('community.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">See more</a>
                </div>

                {{-- One button, not a box you scroll past.

                     The composer used to sit open at the top of the homepage
                     wall, four lines of empty textarea between the reader and
                     the posts they came for. It is the same composer — the
                     same ids, the same photo/video/emoji doors — in a sheet
                     that comes up over the page, which is exactly how the
                     community wall asks the same question. --}}
                <div class="wall-bar" id="dashWallBar">
                    <button type="button" id="dashWriteBtn" class="btn btn-outline btn-sm wb-act">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        <span class="wb-act-lbl">New post</span>
                    </button>
                    <span class="wb-hint">Ano'ng balita sa bukid?</span>
                </div>

                <div id="dashWallFeed" data-animate-list>
                    @foreach ($connectedWall as $post)
                        @include('community.partials.feed-post', [
                            'post' => $post,
                            'friendIds' => $friendIds,
                            'followingIds' => $followingIds ?? [],
                            'savedIds' => $savedIds ?? [],
                        ])
                    @endforeach
                </div>
                <a href="{{ route('community.index') }}" id="dashWallEmpty" class="card card-hover block {{ $connectedWall->isEmpty() ? '' : 'hidden' }}">
                    <div class="card-body text-center py-8">
                        <div class="text-3xl mb-2">🌱</div>
                        <h3 class="font-bold text-gray-900">Meet your co-farmers</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Share an update above, or connect with other farmers to see their posts here.</p>
                    </div>
                </a>
            </div>
            @endif

        </div>

        {{-- SIDEBAR: AI Technician + Latest Discussions --}}
        <aside class="dash-side space-y-4">

            @if ($canUseAi)
                <section class="card">
                    <div class="card-body !p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-baseline gap-2 min-w-0">
                                <h2 class="text-sm font-bold text-gray-900 shrink-0">🤖 AI Technician</h2>
                                <a href="{{ route('ai.credits') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 whitespace-nowrap" title="Buy AI credits">⚡ {{ number_format((int) $aiBalance) }}</a>
                            </div>
                            <a href="{{ route('ai.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">Open →</a>
                        </div>
                        @if ($recentChats->isEmpty())
                            <a href="{{ route('ai.index') }}" class="flex items-center gap-3 px-1 py-2 rounded-lg hover:bg-gray-50 transition">
                                <span class="text-2xl leading-none">💬</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-sm">Start a conversation</p>
                                    <p class="text-xs text-gray-500">Ask about pests, fertilizer, planting…</p>
                                </div>
                            </a>
                        @else
                            <div class="divide-y divide-gray-100">
                                @foreach ($recentChats as $chat)
                                    <a href="{{ route('ai.index', ['c' => $chat->id, 'scheduleId' => $chat->croppingScheduleId]) }}" class="flex items-start gap-2.5 px-1 py-2.5 hover:bg-gray-50 transition">
                                        <span class="text-lg leading-none mt-0.5">💬</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 text-sm leading-snug line-clamp-1">{{ $chat->title ?: 'Untitled chat' }}</p>
                                            <p class="text-xs text-gray-400">Updated {{ $chat->updated_at?->diffForHumans() }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-body !p-4">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h2 class="text-sm font-bold text-gray-900">💬 Latest Discussions</h2>
                        <a href="{{ route('community.groups.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">See more →</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestDiscussions as $d)
                            <a href="{{ route('community.groups.show', ['id' => $d->groupId]) }}" class="block px-1 py-2.5 hover:bg-gray-50 transition">
                                <p class="text-xs text-gray-400 leading-tight truncate">
                                    <span class="font-semibold text-brand-700">{{ optional($d->group)->name }}</span>
                                    · {{ optional($d->author)->full_name }}
                                </p>
                                @if ($d->title)
                                    <p class="font-bold text-gray-900 text-sm leading-snug mt-0.5 line-clamp-2">{{ $d->title }}</p>
                                @else
                                    <p class="text-sm text-gray-700 leading-snug mt-0.5 line-clamp-2">{{ $snippet($d->body) }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    💬 {{ $d->replies?->count() ?? 0 }} {{ \Illuminate\Support\Str::plural('reply', $d->replies?->count() ?? 0) }}
                                    · {{ $d->created_at?->diffForHumans() }}
                                </p>
                            </a>
                        @empty
                            <a href="{{ route('community.groups.index') }}" class="block px-1 py-4 text-center text-sm text-gray-400 hover:text-brand-700">Join a discussion group →</a>
                        @endforelse
                    </div>
                </div>
            </section>

        </aside>

    </div>

    {{-- Latest from the Technician's Blog --}}
    @if (!empty($latestBlog) && $latestBlog->isNotEmpty())
        <div>
            <div class="flex items-center justify-between gap-3 mb-3 px-1">
                <h2 class="text-base md:text-lg font-bold text-gray-900">📰 From the Technician's Blog</h2>
                <a href="{{ route('community.blog') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">See all →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($latestBlog as $article)
                    <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="card card-hover overflow-hidden block">
                        <div style="aspect-ratio:16/9;background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50));overflow:hidden;">
                            @if ($article->coverUrl())
                                <img src="{{ $article->coverUrl() }}" alt="" loading="lazy" class="w-full h-full object-cover"
                                     data-cover data-cover-alt="{{ $article->coverUrlOnMother() }}">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">🌾</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <span class="font-bold text-gray-900 text-sm leading-tight block">{{ \Illuminate\Support\Str::limit($article->title, 60) }}</span>
                            @if ($article->publishedAt)<span class="text-xs text-gray-400">{{ $article->publishedAt->format('M j, Y') }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>

{{-- The wall's cards work here too, so their sheets have to come along. --}}
@include('community.partials.photo-editor')
@include('community.partials.post-actions')
@include('community.partials.wall-comments-modal')
{{-- Tapping your own photo on the composer asks what is on your mind, the
     same composer the community profile uses. --}}
@include('community.partials.status-modal')
@endsection

@push('scripts')
{{-- The co-farmer feed reuses the wall's post cards, so it needs the same
     community JS: reactions, comment form, emoji, photo, mentions, modal. --}}
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.composer-preview-js')
@include('community.partials.avatar-zoom')
{{-- The wall on this page draws the very same cards the community wall
     does, data-view markers and all — and the script that watches them
     was never included here, so a post read on the home screen was
     never counted as read. --}}
@include('community.partials.views-js')
@include('community.partials.mutual-js')

@push('scripts')
<script>
/* Folding a season on the home page.
 *
 * It shares the schedules page's memory — the same key, keyed to the farm —
 * because it is the same question about the same seasons. Put one away on
 * either page and it is away on both. */
(function dashFold() {
    const KEY = 'smFolded:' + @json(\App\Support\WorkerContext::effectiveOwnerId());

    const read = () => {
        try { return new Set(JSON.parse(localStorage.getItem(KEY) || '[]')); }
        catch (_) { return new Set(); }
    };
    const write = (set) => {
        try { localStorage.setItem(KEY, JSON.stringify([...set])); } catch (_) {}
    };

    const cards = () => document.querySelectorAll('.ds-card[data-schedule-card]');

    // Armed only for the length of a fold; see the note beside the CSS.
    function arm(card) {
        card.classList.add('is-folding');
        clearTimeout(card.__foldTimer);
        card.__foldTimer = setTimeout(() => card.classList.remove('is-folding'), 340);
    }

    function apply() {
        const folded = read();
        cards().forEach((card) => {
            const on = folded.has(String(card.dataset.scheduleCard));
            card.classList.toggle('is-folded', on);
            card.querySelector('[data-ds-fold]')?.setAttribute('aria-expanded', on ? 'false' : 'true');
        });
    }

    document.addEventListener('click', (e) => {
        const head = e.target.closest('[data-ds-fold]');
        if (!head) return;
        const card = head.closest('.ds-card[data-schedule-card]');
        if (!card) return;
        const folded = read();
        const id = String(card.dataset.scheduleCard);
        const now = !card.classList.contains('is-folded');
        arm(card);
        card.classList.toggle('is-folded', now);
        head.setAttribute('aria-expanded', now ? 'false' : 'true');
        now ? folded.add(id) : folded.delete(id);
        write(folded);
    });

    apply();
})();

(function dashToday() {
    /* Each day's tasks slide one whole card at a time.
     *
     * The arrows belong to the gesture, not to the card. They appear as you
     * start moving the list and fade out once you stop, so a card sitting
     * still is just the task — no furniture over it. Which of the two shows
     * still depends on there being somewhere to go, so the last card never
     * offers a way forward that does nothing.
     *
     * They stay awake while the pointer is on one of them, because an arrow
     * that vanishes from under the cursor cannot be clicked. */
    const REST = 1100;      // how long they linger after the last movement

    document.querySelectorAll('[data-dn-slider]').forEach((slider) => {
        const rail = slider.querySelector('[data-dn-rail]');
        const prev = slider.querySelector('[data-dn-prev]');
        const next = slider.querySelector('[data-dn-next]');
        if (!rail || slider.classList.contains('is-single')) return;

        let sleep = null;
        let held = false;   // the pointer is resting on an arrow

        const reach = () => {
            // A pixel of slack: sub-pixel scroll positions never land exactly
            // on the end, and without it the last arrow never turns off.
            const max = rail.scrollWidth - rail.clientWidth;
            prev.classList.toggle('can-go', rail.scrollLeft > 2);
            next.classList.toggle('can-go', rail.scrollLeft < max - 2);
        };

        const wake = () => {
            reach();
            slider.classList.add('is-live');
            clearTimeout(sleep);
            sleep = setTimeout(() => { if (!held) slider.classList.remove('is-live'); }, REST);
        };

        const step = (dir) => {
            const card = rail.querySelector('.dn-card');
            const by = card ? card.getBoundingClientRect().width + 8 : rail.clientWidth;
            rail.scrollBy({ left: dir * by, behavior: 'smooth' });
        };

        // Anything that moves the list, or is about to.
        rail.addEventListener('scroll', wake, { passive: true });
        rail.addEventListener('pointerdown', wake, { passive: true });
        rail.addEventListener('touchstart', wake, { passive: true });
        rail.addEventListener('wheel', wake, { passive: true });

        [prev, next].forEach((btn) => {
            btn.addEventListener('click', () => step(btn === prev ? -1 : 1));
            btn.addEventListener('pointerenter', () => { held = true; wake(); });
            btn.addEventListener('pointerleave', () => { held = false; wake(); });
        });

        window.addEventListener('resize', reach);
        reach();
    });

    /* The blog's covers are written by the mother site, onto its disk. When
       this app cannot serve one — a fresh deploy on an ephemeral disk is the
       usual reason — fall back to the mother's copy, and to the placeholder
       if that is gone too. A broken-image icon is the one outcome nobody
       should ever see. */
    document.querySelectorAll('[data-cover]').forEach((img) => {
        img.addEventListener('error', function onErr() {
            const alt = img.getAttribute('data-cover-alt');
            if (alt && img.src !== alt) { img.src = alt; return; }
            img.removeEventListener('error', onErr);
            const holder = img.parentElement;
            if (holder) holder.innerHTML = '<div class="w-full h-full flex items-center justify-center text-3xl">🌾</div>';
        });
    });
})();
</script>
@endpush

{{-- Dashboard wall composer — posts to your own wall (the same wall shown in
     /app/community and on your profile). Photo + video (upload or record). --}}
<script>
(() => {
    const host = document.getElementById('dashComposer');
    if (!host) return;
    const body = document.getElementById('dashPostBody');
    const btn = document.getElementById('dashPostBtn');
    const feed = document.getElementById('dashWallFeed');
    const empty = document.getElementById('dashWallEmpty');
    const imageInput = document.getElementById('dashImage');
    const POST_URL = @json(route('community.wall.post', ['userId' => auth()->id()]));
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';

    const camInput = document.getElementById('dashCamera');
    const shotsRow = document.getElementById('dashShots');

    /* The wall composer's own model: one list holds every picture — files
     * to upload and pictures already kept here — and each door only adds to
     * it. Eight at most, the same ceiling the wall keeps. */
    const MAX_SHOTS = 8;
    const shots = [];

    function paintShots() {
        if (!shotsRow) return;
        shotsRow.classList.toggle('hidden', shots.length === 0);
        shotsRow.innerHTML = shots.map((sh, i) =>
            '<span class="comp-shot-one"><img src="' + sh.url + '" alt="">'
            + '<button type="button" data-shot="' + i + '" aria-label="Remove photo">✕</button></span>').join('');
    }
    shotsRow?.addEventListener('click', (e) => {
        const rm = e.target.closest('[data-shot]');
        if (!rm) return;
        const gone = shots.splice(Number(rm.dataset.shot), 1)[0];
        // Object URLs are ours to release; a long session leaks them otherwise.
        if (gone && gone.file) { try { URL.revokeObjectURL(gone.url); } catch (_) {} }
        paintShots();
    });
    function addFile(f) {
        if (!f) return;
        if (shots.length >= MAX_SHOTS) { toast('That is eight photos — the most a post carries.', 'error'); return; }
        shots.push({ file: f, url: URL.createObjectURL(f) });
    }
    function addPick(item) {
        if (!item || !item.path) return;
        if (shots.length >= MAX_SHOTS) { toast('That is eight photos — the most a post carries.', 'error'); return; }
        if (shots.some((sh) => sh.path === item.path)) return;   // the same picture twice is once
        shots.push({ path: item.path, url: item.url || '' });
    }
    function clearPhoto() {
        shots.forEach((sh) => { if (sh.file) { try { URL.revokeObjectURL(sh.url); } catch (_) {} } });
        shots.length = 0;
        imageInput.value = '';
        if (camInput) camInput.value = '';
        paintShots();
    }

    // Door one: this device. One photo still goes through the editor —
    // filters, a word, an arrow at the thing you mean. Several do not: five
    // photos would be five trips through it.
    imageInput?.addEventListener('change', async () => {
        const picked = [...(imageInput.files || [])];
        if (picked.length === 1 && window.smEditInto) {
            await window.smEditInto(imageInput);
            addFile((imageInput.files || [])[0]);
        } else {
            picked.forEach(addFile);
        }
        imageInput.value = '';
        paintShots();
    });
    // Door two: the camera, straight in.
    camInput?.addEventListener('change', () => {
        addFile((camInput.files || [])[0]);
        camInput.value = '';
        paintShots();
    });

        /* The chooser: the wall's three doors, in the same order. */
    document.getElementById('dashPhotoBtn')?.addEventListener('click', () => window.openSheet?.('dashPhotoSheet'));
    document.getElementById('dashSrcUpload')?.addEventListener('click', () => {
        window.closeSheet?.('dashPhotoSheet');
        imageInput?.click();
    });
    document.getElementById('dashSrcCamera')?.addEventListener('click', () => {
        window.closeSheet?.('dashPhotoSheet');
        camInput?.click();
    });
    document.getElementById('dashSrcGallery')?.addEventListener('click', () => {
        window.closeSheet?.('dashPhotoSheet');
        if (typeof window.smPickMedia !== 'function') { toast('The gallery is not available here.', 'error'); return; }
        // The picker overlays every sheet (its own layer sits above them),
        // so the composer stays where it is and is simply there again when
        // the picker goes — the same arrangement the wall's composer uses.
        /* Tap to collect, then one button to bring them all — the picker's
         * multi mode, with room for whatever the post has not already taken. */
        window.smPickMedia({
            allSchedules: true, kinds: 'image', title: 'From your gallery',
            multiple: true,
            max: Math.max(1, MAX_SHOTS - shots.length),
            onPick: (item) => { addPick(item); paintShots(); },
        });
    });
    /* ---- Clips: the comment box's model, worn by the composer -------------
     * One list holds every clip — files to upload and gallery references —
     * capped at three alongside whatever the record button holds. Tiles sit
     * in their own strip with a ✕ apiece. */
    const MAX_CLIPS = 3;
    const clips = [];
    const clipsRow = document.getElementById('dashClips');
    /* The record button's slot lives on the composer element; asked for by
     * id, because this block runs before the submit handler names it. */
    const clipTally = () => {
        const h = document.getElementById('dashComposer');
        return clips.length + ((h && window.plazaVideoFile && window.plazaVideoFile(h)) ? 1 : 0);
    };
    function paintClips() {
        if (!clipsRow) return;
        clipsRow.classList.toggle('hidden', clips.length === 0);
        clipsRow.innerHTML = clips.map((c, i) =>
            '<span class="comp-shot-one is-clip">' + (c.url ? '<img src="' + c.url + '" alt="">' : '')
            + '<button type="button" data-clip="' + i + '" aria-label="Remove video">✕</button></span>').join('');
    }
    clipsRow?.addEventListener('click', (e) => {
        const rm = e.target.closest('[data-clip]');
        if (!rm) return;
        const gone = clips.splice(Number(rm.dataset.clip), 1)[0];
        if (gone && gone.file) { try { URL.revokeObjectURL(gone.url); } catch (_) {} }
        paintClips();
    });
    function addClipFile(f) {
        if (!f) return;
        if (clipTally() >= MAX_CLIPS) { toast('That is three clips — the most a post carries.', 'error'); return; }
        clips.push({ file: f, url: '' });
    }
    function addClipPick(item) {
        if (!item || !item.path) return;
        if (clipTally() >= MAX_CLIPS) { toast('That is three clips — the most a post carries.', 'error'); return; }
        if (clips.some((c) => c.path === item.path)) return;
        clips.push({ path: item.path, url: item.posterUrl || item.url || '' });
    }
    function clearClips() {
        clips.length = 0;
        const vf = document.getElementById('dashVideoFiles');
        if (vf) vf.value = '';
        paintClips();
    }
    document.getElementById('dashVideoBtn')?.addEventListener('click', () => window.openSheet?.('dashVideoSheet'));
    document.getElementById('dashVSrcUpload')?.addEventListener('click', () => {
        window.closeSheet?.('dashVideoSheet');
        document.getElementById('dashVideoFiles')?.click();
    });
    document.getElementById('dashVideoFiles')?.addEventListener('change', (e) => {
        [...(e.target.files || [])].forEach(addClipFile);
        e.target.value = '';
        paintClips();
    });
    document.getElementById('dashVSrcGallery')?.addEventListener('click', () => {
        window.closeSheet?.('dashVideoSheet');
        if (typeof window.smPickMedia !== 'function') { toast('The gallery is not available here.', 'error'); return; }
        window.smPickMedia({
            allSchedules: true, kinds: 'video', title: 'A clip from my gallery',
            multiple: true,
            max: Math.max(1, MAX_CLIPS - clipTally()),
            onPick: (item) => { addClipPick(item); paintClips(); },
        });
    });


    // Grow the textarea with content.
    body?.addEventListener('input', () => { body.style.height = 'auto'; body.style.height = Math.min(body.scrollHeight, 200) + 'px'; });

    /* The door. The composer lives in a sheet now, so writing a post starts
       with saying you want to — and the cursor is in the box by the time the
       sheet has finished coming up. */
    document.getElementById('dashWriteBtn')?.addEventListener('click', () => {
        window.openSheet?.('dashComposerSheet');
        window.smFocus?.(body, { delay: 140 });
    });

    btn?.addEventListener('click', async () => {
        const text = body.value.trim();
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!text && !shots.length && !clips.length && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = (vid || shots.length > 2) ? 'Uploading…' : 'Posting…';
        try {
            const fd = new FormData();
            if (text) fd.append('body', text);
            // Files go up; a picture the app already keeps travels as its path.
            shots.forEach((sh) => {
                if (sh.file) fd.append('images[]', sh.file);
                else if (sh.path) fd.append('galleryPaths[]', sh.path);
            });
            if (vid) fd.append('video', vid);
            // The clip list splits the way a comment's does.
            clips.forEach((c) => {
                if (c.file) fd.append('videos[]', c.file);
                else if (c.path) fd.append('galleryVideoPaths[]', c.path);
            });
            fd.append('render', 'feed');
            const res = await fetch(POST_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (!data.success) { toast(data.message || 'Could not post.', 'error'); return; }
            if (feed && data.data?.html) {
                feed.insertAdjacentHTML('afterbegin', data.data.html);
                const added = feed.firstElementChild;
                if (added) { added.classList.add('plaza-comment-enter'); added.addEventListener('animationend', () => added.classList.remove('plaza-comment-enter'), { once: true }); }
            }
            if (empty) empty.classList.add('hidden');
            body.value = ''; body.style.height = 'auto';
            clearPhoto();
            window.plazaClearVideo && window.plazaClearVideo(host);
            clearClips();
            window.closeSheet?.('dashComposerSheet');
            toast('Posted to your wall.');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.innerHTML = prev; }
    });
})();
</script>

{{-- Every sky the app can draw, and the colours that go with them. --}}
@include('partials.weather-scenes')

{{-- Per-schedule weather: one deduped fetch fills every schedule card's
     [data-weather-for] slot. Fetched after paint so Open-Meteo never blocks the
     dashboard; each card degrades to nothing on failure. --}}
<script>
(() => {
    const slots = Array.from(document.querySelectorAll('[data-weather-for]'));
    if (!slots.length) return;
    const esc = window.escapeHtml || ((s) => String(s == null ? '' : s));

    // The forecast always starts on today, so day index 0 is today — mark it by
    // index rather than a (possibly cached) flag, so the green marker is reliable.
    /* Which sky each day is. The forecast hands over the WMO code and the
       day's high, which is all wxKeyFor needs — and a thirty-six degree
       clear day is a different working day from a twenty-eight degree one,
       which is the distinction an emoji cannot make. */
    const skyOf = (d) => (window.wxKeyFor ? window.wxKeyFor(d.code, false, d.max) : 'cloudy');

    function dayCell(d, isToday) {
        const today = isToday || d.isToday;
        const key = skyOf(d);
        const name = window.wxName ? window.wxName(key, true) : (d.text || '');
        // The drawing replaces the emoji: a rain cell now actually rains.
        const art = window.wxSky ? window.wxSky(key, 30) : `<div class="text-xl">${d.emoji}</div>`;
        return `<div class="flex-1 min-w-0 text-center rounded-lg px-1 py-1.5">
            <p class="text-[0.625rem] font-bold ${today ? 'wx-today-label' : 'text-gray-500'} truncate">${esc(today ? 'Today' : d.dow)}</p>
            <div class="dash-wx-art" title="${esc(name)}">${art}</div>
            <p class="text-[0.688rem] font-bold ${today ? 'wx-today-label' : 'text-gray-800'}">${d.max != null ? d.max + '°' : '–'}<span class="text-gray-400 font-medium">${d.min != null ? '/' + d.min + '°' : ''}</span></p>
            ${d.pop != null ? `<p class="text-[0.562rem] font-semibold text-blue-500">${d.pop}%</p>` : ''}
        </div>`;
    }

    function locBlock(loc) {
        if (!loc) return '';
        if (loc.ok === false) {
            return `<p class="text-[0.688rem] text-gray-400 mt-2 pt-2 border-t border-gray-100">Weather unavailable for ${esc(loc.place || 'this location')}</p>`;
        }
        /* The panel takes today's colour and today's advice.
         *
         * A row of temperatures says what the sky will do; it does not say
         * what to do about it, which is the only reason anybody opens a
         * forecast. The line underneath is the answer — hold the spraying,
         * bring the grain in, do the heavy work before ten — and the tint
         * behind the whole panel is the same sky said in colour, so a
         * farmer knows what kind of week it is before reading a number. */
        const todayKey = loc.days && loc.days.length ? skyOf(loc.days[0]) : 'cloudy';
        const hue = window.wxHue ? window.wxHue(todayKey) : '';
        const advice = window.wxAdvice ? window.wxAdvice(todayKey) : '';
        const name = window.wxName ? window.wxName(todayKey, true) : '';
        return `<div class="dash-wx-panel ${hue} mt-2">
            <p class="dash-wx-place truncate">${esc(loc.place)}${name ? ` · <b>${esc(name)}</b>` : ''}</p>
            <div class="flex gap-1">${loc.days.map((d, i) => dayCell(d, i === 0)).join('')}</div>
            ${advice ? `<p class="dash-wx-advice">${esc(advice)}</p>` : ''}
        </div>`;
    }

    async function load() {
        let data;
        try {
            const res = await fetch(@json(route('app.weather')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            data = await res.json();
        } catch (_) { slots.forEach((s) => { s.style.display = 'none'; }); return; }

        if (!data || !data.success || !data.data) { slots.forEach((s) => { s.style.display = 'none'; }); return; }
        const locations = data.data.locations || {};
        const schedules = data.data.schedules || {};

        /* The greeting looks out of the window.
         *
         * Only when the sky is actually worth naming: a twenty percent
         * chance of rain does not make it a maulang umaga, so a wet key has
         * to come with a real probability behind it before it takes the
         * word. Everything else — a clear sky, a hot day, a windy one — is
         * the code the forecast gave us and needs no second opinion.
         *
         * The first location on the list is the one that speaks. A farmer
         * with two farms is standing in one of them, and the alternative is
         * a greeting that hedges. */
        (function greetByWeather() {
            const mark = document.getElementById('dashHeroMark');
            const word = document.getElementById('dashGreetWord');
            if (!mark || !word || !window.wxKeyFor) return;
            const first = Object.values(locations).find((l) => l && l.ok !== false && (l.days || []).length);
            if (!first) return;
            const today = first.days[0];
            const night = mark.getAttribute('data-night') === '1';
            const key = window.wxKeyFor(today.code, night, today.max);
            const wet = ['rain', 'heavy_rain', 'showers', 'drizzle', 'storm'].includes(key);
            if (wet && (today.pop == null || today.pop < 60)) return;
            const meta = (window.WX_SKIES || {})[key];
            if (!meta || !meta.greeting) return;

            word.textContent = meta.greeting + ' ' + (mark.getAttribute('data-time-word') || '');
            // The hour's own scene steps aside for the weather's.
            mark.classList.add('is-weather');
            mark.innerHTML = window.wxSky(key);
            mark.setAttribute('title', meta.label);
        })();

        slots.forEach((slot) => {
            const id = slot.getAttribute('data-weather-for');
            const keys = schedules[id] || [];
            if (!keys.length) { slot.style.display = 'none'; return; }
            const html = keys.map((k) => locBlock(locations[k])).join('');
            if (html) { slot.innerHTML = html; slot.classList.add('wx-ready'); }   // fades the forecast up
            else slot.style.display = 'none';
        });
    }
    load();
})();
</script>
@endpush


@push('sheets')
{{-- The homepage's composer, in the same sheet the community wall's lives in.
     Moved here rather than rewritten: the ids the dashboard's JS binds to are
     exactly the ones it had when it sat open on the page. --}}
<div class="sheet hidden" id="dashComposerSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Write a post</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    {{-- A shade more headroom than the wall's sheet: this cloud rides
         higher over its avatar, and at 1rem its top edge sat under the
         header rule. --}}
    <div class="sheet-body" style="padding-top:2.3rem;padding-bottom:1.1rem">
        <div id="dashComposer" data-video-host>
            {{-- The wall form's head: the cloud over your face (still the
                 button that sets what is on your mind), your name beside it,
                 the fields full-width below — the same shape the community
                 wall and a discussion's Start-a-topic now share. --}}
            <div class="comp-top">
                <button type="button" id="dashMe" class="dash-me status-cloud-wrap shrink-0" title="Set what's on your mind" data-status-bubble>
                    <span class="status-cloud dash-me-cloud{{ filled(auth()->user()?->statusBubble) ? '' : ' is-empty' }}" id="dashMeBubble">
                        <span class="status-cloud-text" data-status-text data-empty-label="💭 What's on your mind?">{{ auth()->user()?->statusBubble ?: "💭 What's on your mind?" }}</span>
                    </span>
                    <span class="avatar avatar-md {{ \App\Support\CommunityAvatar::hue(auth()->user()->full_name ?? '?') }} overflow-hidden"
                          data-me-avatar data-initials="{{ auth()->user()->initials ?? '?' }}">
                        @if (auth()->user()?->avatarPath)
                            <img src="{{ \App\Support\MediaStore::url(auth()->user()->avatarPath) }}" alt="" class="w-full h-full object-cover">
                        @else
                            {{ auth()->user()->initials ?? '?' }}
                        @endif
                    </span>
                </button>
                <div class="min-w-0 grow">
                    <p class="text-sm leading-tight font-semibold text-gray-900">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-gray-400">Posting to your wall</p>
                </div>
            </div>
            <textarea id="dashPostBody" data-mentionable data-preview="#dashPreview" rows="4" maxlength="5000" class="form-textarea w-full dash-comp-box" placeholder="Share something with your co-farmers — a question, a photo of the field, what the weather did…"></textarea>
            <div id="dashPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>

            {{-- What is coming with the post, shown as itself — the wall's
                 strip of tiles, one ✕ apiece. --}}
            <div class="comp-shots hidden" id="dashShots"></div>
            <div class="comp-shots hidden" id="dashClips"></div>
            <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none">
                <span class="js-video-name"></span>
                <button type="button" class="js-video-clear text-red-600 font-bold">Remove</button>
            </span>

            {{-- The ways to add to it, said out loud — the wall's bar. --}}
            <div class="comp-add comp-add-box">
                <span class="comp-add-lbl">Add to your post</span>
                <div class="comp-add-row">
                    {{-- One door to three ways in — this device, the camera,
                         or the pictures the app already keeps, exactly as the
                         wall's composer offers them. --}}
                    <button type="button" class="wall-act" id="dashPhotoBtn" title="Add a photo" aria-label="Add a photo">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    <input type="file" id="dashImage" accept="image/jpeg,image/png,image/webp" class="hidden" multiple>
                    {{-- capture= asks the phone for its camera rather than its files. --}}
                    <input type="file" id="dashCamera" accept="image/*" capture="environment" class="hidden">
                    {{-- Two doors behind one icon — upload or the gallery —
                         the same pair a comment's video button offers. --}}
                    <button type="button" class="wall-act" id="dashVideoBtn" title="Add a video" aria-label="Add a video">
                        <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <input type="file" id="dashVideoFiles" accept="video/*" class="hidden" multiple>
                    <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
                    </button>
                    <input type="file" class="js-video-file hidden" accept="video/*">
                    <button type="button" class="wall-act js-emoji-btn" data-target="dashPostBody" title="Add an emoji" aria-label="Add an emoji">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                </div>
            </div>

            <button type="button" id="dashPostBtn" class="btn btn-primary comp-send">Post</button>
        </div>
    </div>
</div>


{{-- Where a clip comes from — the comment box's two doors, in a sheet.
     Filming stays its own button beside the icon, because a phone already
     looking at the thing should not have to read a menu first. --}}
<div class="sheet hidden" id="dashVideoSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a video</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="plaza-srcs">
            <button type="button" class="plaza-src" id="dashVSrcUpload">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 17v1.5A2.5 2.5 0 006.5 21h11a2.5 2.5 0 002.5-2.5V17"/></svg></span>
                <span class="plaza-src-t"><b>Upload from phone</b><small>One clip or several at once — up to a minute each.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="plaza-src" id="dashVSrcGallery">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 10.5v5l4.5-2.5-4.5-2.5z"/></svg></span>
                <span class="plaza-src-t"><b>From my gallery</b><small>Clips your seasons already keep.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
    </div>
</div>
{{-- Where a picture comes from — the same three doors the wall's composer
     opens, in the same words. --}}
<div class="sheet hidden" id="dashPhotoSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a photo</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="plaza-srcs">
            <button type="button" class="plaza-src" id="dashSrcUpload">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 17v1.5A2.5 2.5 0 006.5 21h11a2.5 2.5 0 002.5-2.5V17"/></svg></span>
                <span class="plaza-src-t"><b>Upload from this device</b><small>Pick a photo off this phone or computer.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="plaza-src" id="dashSrcCamera">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8a2 2 0 012-2h1.4l1-1.6h7.2l1 1.6H18a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V8z"/><circle cx="12" cy="13" r="3.4"/></svg></span>
                <span class="plaza-src-t"><b>Take a photo now</b><small>Open the camera and shoot what you see.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="plaza-src" id="dashSrcGallery">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 15l3-3.5 2.4 2.8L15 11l3 4"/></svg></span>
                <span class="plaza-src-t"><b>From my gallery</b><small>Photos your seasons already keep.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
    </div>
</div>
@endpush
