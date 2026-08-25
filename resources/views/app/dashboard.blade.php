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
    .dash-hero { display: grid; grid-template-columns: auto minmax(0, 1fr);
        align-items: center; gap: .55rem .95rem;
        padding: 1.25rem 1.35rem; border-radius: 1.1rem; position: relative; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .dash-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, #6b9f3d, #b8d38e 55%, transparent);
        /* The hairline drifts like the rest of the app's accents — gradSweep
           lives in the layout; the oversize is what gives it room to move. */
        background-size: 220% 100%;
        animation: gradSweep 12s ease-in-out infinite alternate; }
    @media (prefers-reduced-motion: reduce) { .dash-hero::before { animation: none; } }
    .dash-hero-mark { grid-column: 1; grid-row: 1 / -1; align-self: center;
        width: 3rem; height: 3rem; border-radius: 999px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; }
    .dash-hero-mark svg { width: 1.55rem; height: 1.55rem; }
    .dash-hero-body { grid-column: 2; min-width: 0; }
    .tod-morning { background: linear-gradient(135deg, #fff7e0, #fbe6ae); color: #d97706; }
    .tod-afternoon { background: linear-gradient(135deg, #e8f4fd, #cde7fa); color: #0284c7; }
    .tod-evening { background: linear-gradient(135deg, #e9e7fb, #d5d2f2); color: #6d28d9; }
    html.dark .tod-morning { color: #fbbf24; }
    html.dark .tod-afternoon { color: #7dd3fc; }
    html.dark .tod-evening { color: #c4b5fd; }
    html.dark .tod-morning, html.dark .tod-afternoon, html.dark .tod-evening { background: rgb(255 255 255 / .07); }
    .dash-hero-h { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800;
        color: var(--color-gray-900); line-height: 1.2; letter-spacing: -.01em; }
    .dash-hero-p { font-size: .82rem; color: var(--color-gray-500); margin-top: .2rem; }
    /* Enough air that the greeting reads as a welcome rather than a header. */
    .dash-hero-mark { width: 3.35rem; height: 3.35rem; }
    .dash-hero-mark svg { width: 1.7rem; height: 1.7rem; }
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
    .dash-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem;
        border-radius: 999px; font-size: .74rem; font-weight: 700;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .dash-chip.is-ok { background: #f0f7e8; border-color: #cfe3b8; color: #3d6823; }
    .dash-chip.is-warn { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    html.dark .dash-hero { background: #151b12; border-color: #2b3a1c; }
    html.dark .dash-hero-h { color: #e8efe1; }
    html.dark .dash-hero-p { color: #a8bd93; }
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
    .ds-head { display: flex; align-items: center; gap: .5rem; width: 100%; text-align: left;
        background: none; border: none; padding: 0; cursor: pointer; }
    .ds-head h3 { flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ds-chev { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        color: var(--color-gray-400); background: var(--color-gray-100);
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
    .ds-body { padding-top: .75rem; }
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
    html.dark .ds-chev { background: rgb(255 255 255 / .07); color: #9fb08e; }
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
        .dash-hero { padding: .9rem 1rem; gap: .7rem; }
        .dash-hero-state { margin-left: 0; flex-basis: 100%; }
        .dash-stat b { font-size: 1.15rem; }
    }
    @media (prefers-reduced-motion: reduce) { .dash-stat { transition: none; } }
    {{-- .wall-act composer buttons live in plaza-css (shared). --}}
    /* The wall composer's own bar, borrowed line for line (feed.blade.php
       keeps the original) so the two sheets read as one form. */
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
        [$__greet, $__tod] = $__h < 12
            ? ['Magandang umaga', 'tod-morning']
            : ($__h < 18 ? ['Magandang hapon', 'tod-afternoon'] : ['Magandang gabi', 'tod-evening']);
    @endphp
    <div class="dash-hero">
        <span class="dash-hero-mark {{ $__tod }}" aria-hidden="true">
            @if ($__tod === 'tod-morning')
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v2M5.3 6.7l1.4 1.4M18.7 6.7l-1.4 1.4M8 15a4 4 0 118 0M2 15h2m16 0h2M3 19h18"/></svg>
            @elseif ($__tod === 'tod-afternoon')
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4l1.4-1.4"/></svg>
            @else
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            @endif
        </span>
        <div class="dash-hero-body">
            <h2 class="dash-hero-h">{{ $__greet }}, {{ \Illuminate\Support\Str::title($user->firstName ?: 'kaibigan') }}</h2>
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
            @if ($isSuperAdmin)
                <span class="dash-chip is-ok">🛡️ Admin access</span>
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
            <h2 class="text-base md:text-lg font-bold text-gray-900">🌾 My Cropping Schedules</h2>
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
                    <div class="card card-hover min-w-0 ds-card" data-schedule-card="{{ $schedule->id }}">
                        <div class="card-body !p-4 flex flex-col gap-3 h-full min-w-0">
                            <button type="button" class="ds-head" data-ds-fold aria-expanded="true"
                                    aria-label="Fold or unfold {{ $schedule->title }}">
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
                    <h2 class="text-base md:text-lg font-bold text-gray-900">🌾 Community Wall</h2>
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
    const imgChip = document.getElementById('dashImageChip');
    const POST_URL = @json(route('community.wall.post', ['userId' => auth()->id()]));
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';

    // Photo chip
    imageInput?.addEventListener('change', async () => {
        // Same stop as the community composer: edit first, then it is a post.
        if (imageInput.files[0] && window.smEditInto) await window.smEditInto(imageInput);
        const f = imageInput.files[0];
        if (f) { document.getElementById('dashImageThumb').src = URL.createObjectURL(f); imgChip.style.display = 'flex'; }
        else imgChip.style.display = 'none';
    });
    document.getElementById('dashImageClear')?.addEventListener('click', () => { imageInput.value = ''; imgChip.style.display = 'none'; });

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
        const img = imageInput.files[0];
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!text && !img && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = vid ? 'Uploading…' : 'Posting…';
        try {
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (img) fd.append('image', img);
            if (vid) fd.append('video', vid);
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
            imageInput.value = ''; imgChip.style.display = 'none';
            window.plazaClearVideo && window.plazaClearVideo(host);
            window.closeSheet?.('dashComposerSheet');
            toast('Posted to your wall.');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.innerHTML = prev; }
    });
})();
</script>

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
    function dayCell(d, isToday) {
        const today = isToday || d.isToday;
        // No box — today is simply shown in green (theme-aware via .wx-today-label).
        return `<div class="flex-1 min-w-0 text-center rounded-lg px-1 py-1.5">
            <p class="text-[0.625rem] font-bold ${today ? 'wx-today-label' : 'text-gray-500'} truncate">${esc(today ? 'Today' : d.dow)}</p>
            <div class="text-xl leading-none my-0.5" title="${esc(d.text)}">${d.emoji}</div>
            <p class="text-[0.688rem] font-bold ${today ? 'wx-today-label' : 'text-gray-800'}">${d.max != null ? d.max + '°' : '–'}<span class="text-gray-400 font-medium">${d.min != null ? '/' + d.min + '°' : ''}</span></p>
            ${d.pop != null ? `<p class="text-[0.562rem] font-semibold text-blue-500">💧${d.pop}%</p>` : ''}
        </div>`;
    }
    function locBlock(loc) {
        if (!loc) return '';
        if (loc.ok === false) {
            return `<p class="text-[0.688rem] text-gray-400 mt-2 pt-2 border-t border-gray-100">🌦️ Weather unavailable for ${esc(loc.place || 'this location')}</p>`;
        }
        return `<div class="mt-2 pt-2 border-t border-gray-100">
            <p class="text-[0.688rem] font-semibold text-gray-500 mb-1.5 truncate">📍 ${esc(loc.place)}</p>
            <div class="flex gap-1">${loc.days.map((d, i) => dayCell(d, i === 0)).join('')}</div>
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

            <div id="dashImageChip" class="hidden mt-2 items-center gap-2 text-xs font-semibold text-gray-600">
                <img src="" id="dashImageThumb" class="w-10 h-10 rounded-lg object-cover" alt="">
                <button type="button" id="dashImageClear" class="text-red-600 font-bold">Remove photo</button>
            </div>
            <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none">
                <span class="js-video-name"></span>
                <button type="button" class="js-video-clear text-red-600 font-bold">Remove</button>
            </span>

            {{-- The ways to add to it, said out loud — the wall's bar. --}}
            <div class="comp-add comp-add-box">
                <span class="comp-add-lbl">Add to your post</span>
                <div class="comp-add-row">
                    <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="file" id="dashImage" accept="image/*" class="hidden">
                    </label>
                    <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                        <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
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
@endpush
