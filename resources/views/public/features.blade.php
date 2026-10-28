@extends('layouts.public')

@include('public.partials.site-css')

@section('title', \App\Support\Region::ph() ? 'Farm App Features: Calendar, Satellite, NPK and Anee' : 'Farm Management App Features')
@section('meta_description', \App\Support\Region::ph()
    ? 'Every anee.io feature for Filipino farmers: the cropping calendar, satellite field health, typhoon watch, the NPK calculator, pest finders and Anee.'
    : 'Every anee.io feature: a cropping calendar by day count, satellite field health, storm tracking, a fertilizer calculator, workers, reports and Anee.')

@php
    $R = \App\Support\Region::class;
    $ph = $R::ph();
    // Every tool, from How It Works itself (2026-10-13, the owner: the list here
    // had fallen behind): the steps of the season in the homepage's order,
    // Anee Auto first. A tool with a guide links to it; every tool has its film.
    $HW = \App\Support\HowItWorks::class;
    $fSteps = collect($HW::stages())->sortBy(fn ($st) => $st['key'] === 'auto' ? 0 : 1)->values()
        ->map(fn ($st) => $st['key'] === 'auto' ? array_replace($st, ['when' => 'The easiest way']) : $st)->all();
    $fTools = collect($fSteps)->flatMap(fn ($st) => $st['items'])->values();
    $fCount = $fTools->count();
    $fGuide = function (array $it) use ($ph) {
        if (! empty($it['page']) && $ph) {
            return \App\Support\SitePages::url('features', $it['page']);
        }

        return ! empty($it['url']) ? url($it['url']) : null;
    };
    $fFilm = fn (string $key) => $HW::video($key);
    // Every tool's screen as the app is now (2026-10-27, the owner: "update all screenshots"), filmed or not.
    $fShot = fn (string $key) => $HW::shot($key);
    // New this month: on their cards and in the band.
    // (2026-10-18: the tools placed in the season this week.)
    $fNew = ['globalLots', 'varietyFinder', 'landprep', 'lcc', 'news', 'nutrientFinder', 'compat', 'auto'];
    // Where each card's phone screen rests (percent down the screen, 2026-10-29): a sheet that opens at the bottom of
    // the screen shows the sheet. On hover, and once as the card comes in, the screen scrolls to the other end.
    $fFocus = ['cash' => 100, 'daycount' => 100, 'share' => 100, 'access' => 100, 'draw' => 0, 'chat' => 35];
@endphp

@push('head')
<style>
    /* ---- FEATURES (redrawn 2026-10-07; since 2026-10-13 every tool of How
       It Works, step by step, each with its film) ----------------------------
       A hero that moves, a band of every feature sliding past (each one
       opens its guide), every feature as a card with a filter by part of
       the farm, Anee, then the way in. The tour and its sticky chip bar were
       taken out at the owner's word (2026-10-07). The house curve
       throughout; still when asked. */
    .fz-hero { position: relative; isolation: isolate; overflow: hidden; color: #eef4e6; background: radial-gradient(70rem 40rem at 85% -10%, #4a7c2a 0%, transparent 60%), linear-gradient(160deg, #1d3310, #0d1609 70%); }
    .fz-hero::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgb(255 255 255 / .1) 1px, transparent 1.5px); background-size: 22px 22px;
        -webkit-mask-image: linear-gradient(180deg, #000, transparent 85%); mask-image: linear-gradient(180deg, #000, transparent 85%); }
    .fz-orb { position: absolute; z-index: -1; border-radius: 999px; filter: blur(40px); opacity: .5; animation: fzDrift 14s ease-in-out infinite alternate; }
    .fz-orb.a { width: 22rem; height: 22rem; left: -6rem; top: 8rem; background: #f5c518; opacity: .14; }
    .fz-orb.b { width: 28rem; height: 28rem; right: -8rem; bottom: -10rem; background: #66bd63; opacity: .2; animation-duration: 18s; }
    @keyframes fzDrift { to { transform: translate(3rem, -2rem) scale(1.1); } }
    .fz-hero-in { display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 1024px) { .fz-hero-in { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); } }
    .fz-kick { display: inline-flex; align-items: center; gap: .5rem; font-size: .78rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: #f5c518; }
    .fz-kick i { width: .5rem; height: .5rem; border-radius: 999px; background: #f5c518; box-shadow: 0 0 0 .3rem rgb(245 197 24 / .2); animation: fzPulse 2s ease-in-out infinite; }
    @keyframes fzPulse { 50% { box-shadow: 0 0 0 .55rem rgb(245 197 24 / 0); } }
    .fz-h1 { margin-top: .7rem; font-family: var(--font-heading); font-weight: 800; color: #fff; font-size: clamp(2.3rem, 5.4vw, 3.9rem); line-height: 1.04; letter-spacing: -.02em; text-wrap: balance; }
    .fz-h1 em { font-style: normal; background: linear-gradient(100deg, #c08a12, #f7d774 30%, #fff3b8 45%, #e0aa2a 65%, #f7d774); -webkit-background-clip: text; background-clip: text; color: transparent;
        background-size: 200% 100%; animation: fzGold 7s ease-in-out infinite alternate; }
    @keyframes fzGold { to { background-position: 100% 50%; } }
    .fz-lede { margin-top: 1.1rem; max-width: 36rem; font-size: 1.05rem; line-height: 1.7; color: #c9dcb5; }
    .fz-stats { margin-top: 1.6rem; display: flex; flex-wrap: wrap; gap: 1.6rem; }
    .fz-stats div b { display: block; font-family: var(--font-heading); font-size: 2.1rem; font-weight: 800; line-height: 1; color: #fff; }
    .fz-stats div small { display: block; margin-top: .35rem; font-size: .78rem; font-weight: 700; color: #a8cc7e; }
    .fz-cta { margin-top: 1.8rem; display: flex; flex-wrap: wrap; gap: .7rem; }
    .fz-phones { position: relative; height: 31rem; }
    .fz-phones .ph-frame { position: absolute; width: min(240px, 46vw); }
    /* Every hero screen the same shape (the fresh stills are taller), its top shown. */
    .fz-phones .ph-frame img { aspect-ratio: 780 / 1520; object-fit: cover; object-position: top; }
    .fz-phones .p1 { left: 50%; top: 0; transform: translateX(-50%); z-index: 3; animation: fzFloat 6s ease-in-out infinite; }
    .fz-phones .p2 { left: 2%; top: 3.5rem; transform: rotate(-8deg); z-index: 2; opacity: .92; animation: fzFloat2 7s ease-in-out infinite; }
    .fz-phones .p3 { right: 2%; top: 3.5rem; transform: rotate(8deg); z-index: 2; opacity: .92; animation: fzFloat3 7.5s ease-in-out infinite; }
    @keyframes fzFloat { 50% { transform: translateX(-50%) translateY(-10px); } }
    @keyframes fzFloat2 { 50% { transform: rotate(-8deg) translateY(-8px); } }
    @keyframes fzFloat3 { 50% { transform: rotate(8deg) translateY(-8px); } }
    /* Below a desk the box is as tall as the phones it holds (a frame is
       about 1.95 times as tall as it is wide), so the tilted side phones
       end above the band of features instead of sitting on its chips. */
    @media (max-width: 1023.98px) {
        .fz-phones { height: calc(min(200px, 44vw) * 1.95 + 2.75rem); }
        .fz-phones .ph-frame { width: min(200px, 44vw); }
        .fz-phones .p2, .fz-phones .p3 { top: 2rem; }
    }
    @media (max-width: 479.98px) { .fz-phones .p2 { left: -4%; } .fz-phones .p3 { right: -4%; } }
    /* A phone: the three numbers side by side, not two and one. */
    @media (max-width: 639.98px) {
        .fz-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        .fz-stats div b { font-size: 1.85rem; }
        .fz-stats div small { line-height: 1.35; }
    }

    /* Every feature, sliding past. */
    .fz-band { position: relative; overflow: hidden; padding: 1rem 0; background: #0d1609; border-top: 1px solid rgb(255 255 255 / .06); border-bottom: 1px solid rgb(255 255 255 / .06);
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
    .fz-track { display: flex; gap: .55rem; width: max-content; animation: fzSlide 190s linear infinite; }
    .fz-track.rev { animation-direction: reverse; animation-duration: 220s; margin-top: .55rem; }
    .fz-band:hover .fz-track, .fz-band:focus-within .fz-track { animation-play-state: paused; }
    .fz-track a { flex: none; text-decoration: none; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fz-track a:hover, .fz-track a:focus-visible { color: #fff; background: rgb(255 255 255 / .14); border-color: rgb(255 255 255 / .3); }
    .fz-track a.is-new:hover, .fz-track a.is-new:focus-visible { color: #1f1500; background: #ffd84a; }
    .fz-track a { flex: none; padding: .5rem .95rem; border-radius: 999px; font-size: .85rem; font-weight: 800; color: #dbe7cf; background: rgb(255 255 255 / .06); border: 1px solid rgb(255 255 255 / .1); white-space: nowrap; }
    .fz-track a.is-new { color: #1f1500; background: #f5c518; border-color: #f5c518; }
    @keyframes fzSlide { to { transform: translateX(-50%); } }

    /* Every tool, step by step (2026-10-13). */
    .fz-sec { scroll-margin-top: 5rem; }
    .fz-all { position: relative; padding: 3.5rem 0 4rem; background: #fbfcf9; border-bottom: 1px solid #eef2e8; }
    .fz-steps-nav { position: sticky; top: 4rem; z-index: 20; margin-top: 1.4rem; padding: .55rem 0; background: rgb(251 252 249 / .92); backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px); border-bottom: 1px solid #eef2e8; }
    @media (min-width: 768px) { .fz-steps-nav { top: 5rem; } }
    .fz-steps-row { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; scroll-snap-type: x proximity; }
    .fz-steps-row::-webkit-scrollbar { display: none; }
    .fz-steps-row a { flex: none; scroll-snap-align: start; display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .8rem .42rem .45rem; border-radius: 999px;
        font-size: .82rem; font-weight: 800; color: #374151; background: #fff; border: 1px solid #e1ead6; text-decoration: none;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fz-steps-row a b { display: grid; place-items: center; width: 1.45rem; height: 1.45rem; border-radius: 999px; font-size: .72rem; color: #2f5219; background: #eef6e6; }
    .fz-steps-row a.is-auto b { color: #2b3a1c; background: #f5c518; }
    .fz-steps-row a.is-on { color: #fff; background: #2d5016; border-color: #2d5016; }
    .fz-steps-row a.is-on b { color: #1f1500; background: #f5c518; }
    .fz-step { scroll-margin-top: 9rem; padding-top: 2.6rem; }
    .fz-step-head { display: flex; gap: 1rem; align-items: flex-start; max-width: 46rem; }
    .fz-step-n { flex: none; width: 2.8rem; height: 2.8rem; border-radius: 999px; display: grid; place-items: center; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800;
        color: #fff; background: #2d5016; box-shadow: 0 0 0 5px #e6f0dc; }
    .fz-step.is-auto .fz-step-n { color: #2b3a1c; background: #f5c518; box-shadow: 0 0 0 5px #fff1bf; }
    .fz-step-when { font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
    .fz-step.is-auto .fz-step-when { color: #8a6a00; }
    .fz-step-h { margin-top: .2rem; font-family: var(--font-heading); font-size: clamp(1.35rem, 2.6vw, 1.75rem); font-weight: 800; color: #14210c; line-height: 1.15; }
    .fz-step-p { margin-top: .4rem; color: #4b5563; line-height: 1.6; }
    .fz-tools { margin-top: 1.3rem; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
    .fz-tool { position: relative; display: flex; flex-direction: column; overflow: hidden; border-radius: 1.3rem; background: #fff; border: 1px solid #e5ebdf;
        box-shadow: 0 18px 40px -36px rgb(20 33 12 / .5); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fz-tool:hover { transform: translateY(-4px); border-color: #c9dcb4; box-shadow: 0 28px 50px -32px rgb(20 33 12 / .55); }
    .fz-step.is-auto .fz-tool { border-color: #f0dc95; background: linear-gradient(160deg, #fffaf0, #fff 55%); }
    /* A tool's screen (2026-10-29, the owner: "screenshots of the /features is bad"): a real phone rising out of a soft
       stage, big enough to read, its screen scrolling slowly to the other end on hover and once as the card comes in. */
    .fz-shot { --stage: 15.5rem; position: relative; display: block; width: 100%; height: var(--stage); padding: 0; border: 0; cursor: pointer; overflow: hidden;
        background: radial-gradient(70% 90% at 50% 105%, #cfe5b6, transparent 70%), radial-gradient(rgb(74 124 42 / .12) 1px, transparent 1.4px) 0 0 / 14px 14px,
            linear-gradient(165deg, #f6faf1, #e6f1da); border-bottom: 1px solid #e5ebdf; }
    .fz-step.is-auto .fz-shot { background: radial-gradient(70% 90% at 50% 105%, #fde68a, transparent 70%), radial-gradient(rgb(184 138 0 / .14) 1px, transparent 1.4px) 0 0 / 14px 14px,
            linear-gradient(165deg, #fffaf0, #fdf2cf); }
    /* The phone sits right of centre, clear of the tool's icon that rises over the stage's left corner. */
    .fz-dev { position: absolute; left: 58%; top: 1.25rem; bottom: 0; width: 60%; translate: -50% 0; padding: .55rem .34rem 0; border-radius: 1.7rem 1.7rem 0 0;
        background: linear-gradient(160deg, #303a26, #0e140a 70%); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08), 0 30px 46px -22px rgb(20 33 12 / .7);
        transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .fz-dev::before { content: ''; position: absolute; left: 50%; top: .17rem; width: 2.4rem; height: .26rem; margin-left: -1.2rem; border-radius: 999px; background: #05070a; }
    .fz-scr { display: block; height: 100%; overflow: hidden; border-radius: 1.3rem 1.3rem 0 0; background: #fff; }
    .fz-scr img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: 50% var(--fy, 0%); transition: object-position 4.5s cubic-bezier(.45,0,.25,1); }
    .fz-tool:hover .fz-dev, .fz-shot:focus-visible .fz-dev { transform: translateY(-.45rem); }
    .fz-tool:hover .fz-scr img, .fz-shot:focus-visible .fz-scr img { object-position: 50% calc(100% - var(--fy, 0%)); }
    /* Once, as the card comes in: down the screen and back. */
    @media (prefers-reduced-motion: no-preference) {
        html.js .fz-tool.reveal.is-visible:not(:hover) .fz-scr img { animation: fzPeek 7s cubic-bezier(.45,0,.25,1) calc(.9s + var(--reveal-delay, 0s)) 1; }
        @keyframes fzPeek { 0%, 12% { object-position: 50% var(--fy, 0%); } 50%, 62% { object-position: 50% calc(100% - var(--fy, 0%)); } 100% { object-position: 50% var(--fy, 0%); } }
    }
    .fz-play { position: absolute; z-index: 2; right: .9rem; top: .9rem; width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center; color: #1a1a1a;
        background: #f5c518; box-shadow: 0 0 0 5px rgb(245 197 24 / .25), 0 10px 20px -10px rgb(0 0 0 / .5); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fz-play svg { width: 1rem; height: 1rem; margin-left: 2px; }
    .fz-shot:hover .fz-play, .fz-shot:focus-visible .fz-play { transform: scale(1.1); }
    .fz-shot.is-still { cursor: default; }
    .fz-new { position: absolute; z-index: 3; left: .8rem; top: .8rem; padding: .18rem .55rem; border-radius: 999px; font-size: .62rem; font-weight: 900; letter-spacing: .08em;
        text-transform: uppercase; color: #1f1500; background: #f5c518; }
    .fz-tool-body { position: relative; display: flex; flex: 1; flex-direction: column; gap: .35rem; padding: 0 1.15rem 1.15rem; }
    .fz-ico { width: 3rem; height: 3rem; margin-top: -1.5rem; border-radius: 1rem; display: grid; place-items: center; overflow: hidden; background: #fff;
        box-shadow: 0 0 0 1px #e1ead6, 0 10px 22px -12px rgb(20 33 12 / .5); }
    .fz-ico img { width: 2rem; height: 2rem; object-fit: contain; }
    /* A tool not filmed yet (2026-10-18): no picture above, so the icon sits inside the card and the New tag moves right. */
    .fz-tool.no-film .fz-tool-body { padding-top: 1.15rem; }
    .fz-tool.no-film .fz-ico { margin-top: 0; }
    .fz-tool.no-film .fz-new { left: auto; right: .8rem; }
    .fz-ico.is-face img { width: 100%; height: 100%; object-fit: cover; }
    .fz-tool h4 { margin-top: .3rem; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #14210c; line-height: 1.3; }
    .fz-anee { display: inline-block; margin-left: .4rem; padding: .08rem .45rem; border-radius: 999px; vertical-align: .12em; font-style: normal; font-size: .62rem; font-weight: 900;
        letter-spacing: .05em; text-transform: uppercase; color: #5b4300; background: #fde68a; }
    .fz-short { font-size: .86rem; font-weight: 700; color: #2f5219; }
    .fz-what { font-size: .84rem; line-height: 1.55; color: #4b5563; }
    .fz-helps { margin-top: .5rem; border-radius: .8rem; background: #fffbea; box-shadow: inset 0 0 0 1px #f3e2a6; }
    .fz-helps summary { cursor: pointer; padding: .5rem .7rem; font-size: .76rem; font-weight: 800; color: #8a6400; list-style: none; }
    .fz-helps summary::-webkit-details-marker { display: none; }
    .fz-helps summary::after { content: '+'; float: right; font-weight: 900; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fz-helps[open] summary::after { transform: rotate(45deg); }
    .fz-helps ul { display: grid; gap: .3rem; margin: 0; padding: 0 .7rem .65rem 1.6rem; font-size: .78rem; line-height: 1.45; color: #4b5563; }
    .fz-helps b { color: #1f2937; }
    /* The plan a tool comes with, its dot in the plan's colour (2026-10-18). */
    .fz-plan { display: flex; align-items: flex-start; gap: .45rem; font-size: .76rem; font-weight: 800; line-height: 1.35; color: #2f5219; }
    .fz-plan i { flex: none; width: .55rem; height: .55rem; margin-top: .28rem; border-radius: 999px; background: #86b556; box-shadow: 0 0 0 3px rgb(134 181 86 / .2); }
    .fz-plan.is-libreAnee i { background: #f5c518; box-shadow: 0 0 0 3px rgb(245 197 24 / .25); }
    .fz-plan.is-solo i { background: #4a7c2a; }
    .fz-plan.is-owner i { background: #2d5016; }
    .fz-plan small { display: block; font-size: .72rem; font-weight: 700; color: #6b7f5a; }
    .fz-go { margin-top: auto; padding-top: .4rem; display: inline-flex; align-items: center; gap: .25rem; font-size: .82rem; font-weight: 800; color: #2f5219; text-decoration: none; }
    .fz-go svg { width: .9rem; height: .9rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fz-go:hover svg { transform: translateX(3px); }
    /* Anee Auto: one tool, so one wide card: the screen beside the words and her six steps. */
    .fz-auto-steps { margin: .5rem 0 .2rem; padding: 0; list-style: none; display: grid; gap: .45rem; }
    @media (min-width: 560px) { .fz-auto-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .fz-auto-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .fz-auto-steps li { display: flex; gap: .5rem; align-items: flex-start; padding: .55rem .65rem; border-radius: .9rem; background: #fffaf0; border: 1px solid #f0e3b4;
        font-size: .78rem; line-height: 1.4; color: #5b6651; }
    .fz-auto-steps li b { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: grid; place-items: center; font-size: .7rem; color: #2b3a1c; background: #f5c518; }
    .fz-auto-steps li strong { display: block; font-size: .84rem; color: #14210c; }
    .fz-step.is-auto .fz-tools { grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 768px) {
        .fz-step.is-auto .fz-tool { flex-direction: row; }
        .fz-step.is-auto .fz-shot { flex: none; width: 18rem; height: auto; min-height: 21rem; border-bottom: 0; border-right: 1px solid #f0dc95; }
        .fz-step.is-auto .fz-dev { width: 64%; top: 1.6rem; }
        .fz-step.is-auto .fz-tool-body { padding: 1.4rem 1.5rem 1.4rem .4rem; }
        .fz-step.is-auto .fz-ico { margin-top: 0; }
        .fz-step.is-auto .fz-what { font-size: .9rem; }
    }
    .fz-or { display: flex; align-items: center; gap: .8rem; margin-top: 2.4rem; font-size: .76rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
    .fz-or::before, .fz-or::after { content: ''; flex: 1; border-top: 2px dashed #c9dcb4; }
    /* A phone (2026-10-13, the owner: it felt cluttered): one tool to a row,
       its icon, its name and its line, and a play button that opens its film
       full size. The little screens were too small to read, so they go. */
    @media (max-width: 559.98px) {
        .fz-tools { grid-template-columns: minmax(0, 1fr); gap: .55rem; }
        .fz-tool { flex-direction: row; align-items: center; border-radius: 1.1rem; }
        .fz-tool:hover { transform: none; }
        .fz-shot { order: 2; flex: none; width: auto; height: auto; padding: 0 .85rem 0 .3rem; background: none; overflow: visible; border: 0; }
        .fz-dev { display: none; }
        .fz-play { position: static; width: 2.6rem; height: 2.6rem; box-shadow: 0 0 0 4px rgb(245 197 24 / .22), 0 8px 16px -8px rgb(0 0 0 / .45); }
        .fz-tool-body { order: 1; flex: 1; min-width: 0; display: grid; grid-template-columns: auto minmax(0, 1fr); column-gap: .75rem; row-gap: .12rem;
            align-items: center; padding: .8rem .2rem .8rem .8rem; }
        .fz-ico { grid-row: 1 / span 4; align-self: start; width: 2.7rem; height: 2.7rem; margin-top: 0; border-radius: .85rem; }
        .fz-ico img { width: 1.7rem; height: 1.7rem; }
        .fz-tool h4 { margin-top: 0; font-size: .95rem; }
        .fz-short { font-size: .8rem; line-height: 1.4; }
        .fz-what { display: none; }
        .fz-plan { grid-column: 2; font-size: .72rem; }
        .fz-go { grid-column: 2; padding-top: .2rem; font-size: .76rem; }
        .fz-new { left: auto; right: 4.1rem; top: .5rem; font-size: .55rem; padding: .1rem .45rem; }
        .fz-tool.no-film .fz-new, .fz-tool.no-play .fz-new { right: .8rem; }
        .fz-tool.no-film .fz-tool-body, .fz-tool.no-play .fz-tool-body { padding: .8rem .9rem .8rem .8rem; }
        /* A screen with no film has nothing to play on a phone, where the little screens are hidden. */
        .fz-shot.is-still { display: none; }
        /* Anee Auto keeps its six steps under the words. */
        .fz-step.is-auto .fz-tool { flex-direction: column; align-items: stretch; }
        .fz-step.is-auto .fz-shot { order: 0; position: absolute; right: .3rem; top: .9rem; z-index: 2; }
        .fz-step.is-auto .fz-tool-body { padding: .9rem .9rem 1rem; }
        .fz-step.is-auto .fz-tool h4 { padding-right: 3.2rem; }
        .fz-auto-steps { grid-column: 1 / -1; }
        .fz-step.is-auto .fz-what { display: block; grid-column: 1 / -1; margin-top: .4rem; font-size: .84rem; }
        .fz-step-head { gap: .75rem; }
        .fz-step-n { width: 2.3rem; height: 2.3rem; font-size: 1rem; }
    }
    /* The film modal. */
    .fz-modal { position: fixed; inset: 0; z-index: 200; display: grid; place-items: center; padding: 1rem; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .fz-modal.is-on { opacity: 1; }
    .fz-modal[hidden] { display: none; }
    .fz-modal-veil { position: absolute; inset: 0; background: rgb(8 14 5 / .72); backdrop-filter: blur(4px); }
    .fz-modal-box { position: relative; display: grid; gap: 1.4rem; align-items: center; width: min(46rem, 100%); max-height: calc(100vh - 2rem); overflow: auto; padding: 1.4rem;
        border-radius: 1.6rem; background: #fff; box-shadow: 0 40px 90px -30px rgb(0 0 0 / .7); transform: translateY(14px) scale(.98); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .fz-modal.is-on .fz-modal-box { transform: none; }
    @media (min-width: 720px) { .fz-modal-box { grid-template-columns: auto minmax(0, 1fr); padding: 1.8rem; } }
    .fz-modal-phone { justify-self: center; width: min(15rem, 62vw); aspect-ratio: 540 / 1170; padding: .45rem; border-radius: 2.2rem; background: #11180c; box-shadow: 0 30px 60px -30px rgb(0 0 0 / .8); }
    @media (max-width: 719.98px) { .fz-modal-phone { width: min(12rem, 50vw); } }
    .fz-modal-phone video { width: 100%; height: 100%; display: block; object-fit: cover; border-radius: 1.8rem; background: #fff; }
    .fz-modal-step { font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
    .fz-modal-words h3 { margin-top: .3rem; font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: #14210c; }
    .fz-modal-words p#fzModalWhat { margin-top: .6rem; color: #4b5563; line-height: 1.65; }
    .fz-modal-nav { margin-top: 1.2rem; display: flex; align-items: center; gap: .6rem; }
    .fz-modal-help { margin-top: .9rem; padding: .8rem .9rem; border-radius: 1rem; background: #fffbea; box-shadow: inset 0 0 0 1px #f3e2a6; }
    .fz-modal-help > b { display: block; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #8a6400; }
    .fz-modal-help ul { display: grid; gap: .35rem; margin: .5rem 0 0; padding: 0; list-style: none; }
    .fz-modal-help li { position: relative; padding-left: .95rem; font-size: .84rem; line-height: 1.5; color: #4b5563; }
    .fz-modal-help li::before { content: ''; position: absolute; left: 0; top: .55rem; width: .4rem; height: .4rem; border-radius: 999px; background: #f5c518; }
    .fz-modal-help b b, .fz-modal-help li b { color: #1f2937; }
    .fz-modal-nav button { width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center; border: 1px solid #e1ead6; background: #fff; color: #2f5219;
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fz-modal-nav button:hover { background: #f1f7ea; }
    .fz-modal-nav button svg { width: 1.1rem; height: 1.1rem; }
    .fz-modal-nav .fz-go { margin: 0 auto 0 .4rem; padding: 0; }
    .fz-x { position: absolute; right: .8rem; top: .8rem; z-index: 2; width: 2.3rem; height: 2.3rem; border-radius: 999px; display: grid; place-items: center; color: #374151; background: #f3f4f6; }
    .fz-x svg { width: 1.1rem; height: 1.1rem; }

    @media (prefers-reduced-motion: reduce) {
        .fz-orb, .fz-kick i, .fz-h1 em, .fz-phones .ph-frame, .fz-track { animation: none !important; }
        .fz-track { flex-wrap: wrap; width: auto; justify-content: center; }
        .fz-track.rev { display: none; }
        .fz-tool, .fz-dev, .fz-scr img, .fz-modal, .fz-modal-box, .fz-play { transition: none !important; }
        .fz-scr img { animation: none !important; }
    }
</style>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="fz-hero">
        <i class="fz-orb a" aria-hidden="true"></i><i class="fz-orb b" aria-hidden="true"></i>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-12 sm:pt-20 sm:pb-16 fz-hero-in">
            <div class="animate-fade-up">
                <span class="fz-kick"><i aria-hidden="true"></i>Every feature</span>
                <h1 class="fz-h1">Everything Your Farm <em>Runs On.</em></h1>
                <p class="fz-lede">
                    One farm app for the whole season: the cropping calendar, your field seen from space, the typhoon on its way, every nutrient in your fertilizer,
                    your workers and every {{ $ph ? 'peso' : 'dollar' }}, with Anee, your smart farm technician, beside you. Every screen below is the real thing.
                </p>
                <div class="fz-stats">
                    <div><b>{{ $fCount }}</b><small>tools, each on a phone</small></div>
                    <div><b>{{ count($fSteps) }}</b><small>steps of the season</small></div>
                    <div><b>{{ $ph ? '85' : '100' }}</b><small>crops it knows by stage</small></div>
                </div>
                <div class="fz-cta">
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                    <a href="#all" class="btn btn-lg btn-on-dark">See every tool</a>
                </div>
            </div>
            <div class="fz-phones" aria-hidden="true">
                {{-- Fresh screens of the app as it is now (2026-10-29): Anee Auto's morning and the profit report beside Satellite Weather. --}}
                <span class="ph-frame p2"><img src="{{ \App\Support\HowItWorks::shot('auto')[0] ?? asset('images/site/app/board.png') }}" alt="" loading="lazy" width="540" height="1170"></span>
                <span class="ph-frame p3"><img src="{{ \App\Support\HowItWorks::shot('profit')[0] ?? asset('images/site/app/npk.webp') }}" alt="" loading="lazy" width="540" height="1170"></span>
                <span class="ph-frame p1"><img src="{{ asset('images/site/app/sky.webp') }}" alt="" width="780" height="1520"></span>
            </div>
        </div>
        {{-- Every feature, sliding past slowly; each chip opens its guide. --}}
        <nav class="fz-band" aria-label="Every feature">
            <div class="fz-track">
                @foreach ($fTools->concat($fTools) as $i => $it)
                    <a href="#tool-{{ $it['key'] }}" class="{{ in_array($it['key'], $fNew, true) ? 'is-new' : '' }}" @if ($i >= $fCount) aria-hidden="true" tabindex="-1" @endif>{{ $it['name'] }}</a>
                @endforeach
            </div>
            <div class="fz-track rev" aria-hidden="true">
                @foreach ($fTools->reverse()->concat($fTools->reverse()) as $it)
                    <a href="#tool-{{ $it['key'] }}" class="{{ in_array($it['key'], $fNew, true) ? 'is-new' : '' }}" tabindex="-1">{{ $it['name'] }}</a>
                @endforeach
            </div>
        </nav>
    </section>

    {{-- ================= EVERY TOOL, STEP BY STEP ================= --}}
    <section class="fz-all fz-sec" id="all">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one farm app</p>
                <h2 class="mt-2 font-heading text-2xl sm:text-3xl font-bold text-ink text-balance">{{ $fCount }} Tools for Your Whole Season, Step by Step</h2>
                <p class="mt-3 text-gray-600">Each step of the season has its own tools, and they work together: plan it, run it, record it, and ask Anee from the same phone. Tap play on any tool to watch it at work.</p>
            </div>
        </div>
        {{-- The steps, always at hand while reading. --}}
        <nav class="fz-steps-nav" aria-label="The steps of the season">
            <div class="max-w-6xl mx-auto px-4 sm:px-6"><div class="fz-steps-row">
                @foreach ($fSteps as $si => $st)
                    <a href="#step-{{ $st['key'] }}" class="{{ $st['key'] === 'auto' ? 'is-auto' : '' }}" data-step-link="{{ $st['key'] }}"><b>{{ $si + 1 }}</b>{{ $st['title'] }}</a>
                @endforeach
            </div></div>
        </nav>
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            @foreach ($fSteps as $si => $st)
                <div class="fz-step {{ $st['key'] === 'auto' ? 'is-auto' : '' }}" id="step-{{ $st['key'] }}" data-step="{{ $st['key'] }}">
                    <div class="fz-step-head reveal">
                        <span class="fz-step-n">{{ $si + 1 }}</span>
                        <div>
                            <p class="fz-step-when">{{ $st['when'] }} · {{ count($st['items']) }} {{ \Illuminate\Support\Str::plural('tool', count($st['items'])) }}</p>
                            <h3 class="fz-step-h">{{ $st['title'] }}</h3>
                            <p class="fz-step-p">{{ $st['lede'] }}</p>
                        </div>
                    </div>
                    <div class="fz-tools">
                        @foreach ($st['items'] as $ti => $it)
                            @php $film = $fFilm($it['key']); $shot = $fShot($it['key']); $guide = $fGuide($it); @endphp
                            <article class="fz-tool reveal {{ $film || $shot ? '' : 'no-film' }} {{ $film ? '' : 'no-play' }}" id="tool-{{ $it['key'] }}" style="--reveal-delay: {{ ($ti % 3) * 0.06 }}s">
                                @if ($film)
                                    <button type="button" class="fz-shot" data-film="{{ $film[0] }}" data-poster="{{ $film[1] }}" data-key="{{ $it['key'] }}" aria-label="Watch {{ $it['name'] }} at work">
                                        <span class="fz-dev" style="--fy: {{ $fFocus[$it['key']] ?? 0 }}%"><span class="fz-scr"><img src="{{ $shot[0] ?? $film[1] }}" @if (! empty($shot[1])) srcset="{{ $shot[1] }}" sizes="(min-width: 768px) 11rem, 40vw" @endif alt="{{ $it['name'] }} on a phone" loading="lazy" decoding="async" width="540" height="1170"></span></span>
                                        <span class="fz-play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg></span>
                                    </button>
                                @elseif ($shot)
                                    {{-- Not filmed yet: its screen as it is, without a play button. --}}
                                    <span class="fz-shot is-still">
                                        <span class="fz-dev" style="--fy: {{ $fFocus[$it['key']] ?? 0 }}%"><span class="fz-scr"><img src="{{ $shot[0] }}" @if ($shot[1]) srcset="{{ $shot[1] }}" sizes="(min-width: 768px) 11rem, 40vw" @endif alt="{{ $it['name'] }} on a phone" loading="lazy" decoding="async" width="540" height="1170"></span></span>
                                    </span>
                                @endif
                                @if (in_array($it['key'], $fNew, true))<span class="fz-new">New</span>@endif
                                <div class="fz-tool-body">
                                    <span class="fz-ico{{ str_starts_with($it['icon'], 'anee/') ? ' is-face' : '' }}"><img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy" width="40" height="40"></span>
                                    <h4>{{ $it['name'] }}@if ($it['anee'])<i class="fz-anee">Anee</i>@endif</h4>
                                    <p class="fz-short">{{ $it['short'] }}</p>
                                    <p class="fz-what">{{ $it['what'] }}</p>
                                    @if (! empty($it['helps']))
                                        <details class="fz-helps"><summary>How it helps your crops and your farm</summary><ul>@foreach ($it['helps'] as $h)<li>{!! \App\Support\HowItWorks::helpHtml($h) !!}</li>@endforeach</ul></details>
                                    @endif
                                    {{-- The plan it comes with, read off config/tiers.php (2026-10-18). --}}
                                    <p class="fz-plan is-{{ $it['plan']['tier'] }}"><i aria-hidden="true"></i><span>{{ $it['plan']['says'] }}@if ($it['plan']['more'])<small>{{ $it['plan']['more'] }}</small>@endif</span></p>
                                    @if ($it['key'] === 'auto')
                                        <ol class="fz-auto-steps">
                                            @foreach ([['Tell her once', 'Lots, crop, soil, water, fertilizer, workers.'], ['She analyzes it all', 'Variety, El Niño or La Niña, the forecast, your soil.'], ['She plans the season', 'Every task from land preparation to harvest.'], ['You just follow', "Today's work every morning, by email too."], ['She keeps it on track', "Can't today? She moves it to the best day."], ['Show her the field', 'A photo, a video or a voice note.']] as $ak => [$at, $aw])
                                                <li><b>{{ $ak + 1 }}</b><span><strong>{{ $at }}</strong>{{ $aw }}</span></li>
                                            @endforeach
                                        </ol>
                                    @endif
                                    @if ($guide)<a href="{{ $guide }}" class="fz-go">Read the guide <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if ($st['key'] === 'auto')
                        <p class="fz-or reveal"><span>Or do it yourself, step by step</span></p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- The film of a tool, on a phone. --}}
    <div class="fz-modal" id="fzModal" hidden role="dialog" aria-modal="true" aria-labelledby="fzModalName">
        <div class="fz-modal-veil" data-close></div>
        <div class="fz-modal-box">
            <button type="button" class="fz-x" data-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            <div class="fz-modal-phone"><video id="fzModalVideo" muted playsinline loop preload="none"></video></div>
            <div class="fz-modal-words">
                <p class="fz-modal-step" id="fzModalStep"></p>
                <h3 id="fzModalName"></h3>
                <p id="fzModalWhat"></p>
                {{-- How it helps (2026-10-28, the owner: "when you click it and a modal shows explaining it. it should also explain how it helps your crops and your farm"). --}}
                <div class="fz-modal-help" id="fzModalHelp" hidden><b>How it helps your crops and your farm</b><ul></ul></div>
                <div class="fz-modal-nav">
                    <button type="button" data-step-film="-1" aria-label="The tool before"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg></button>
                    <a href="#" id="fzModalGuide" class="fz-go">Read the guide</a>
                    <button type="button" data-step-film="1" aria-label="The next tool"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg></button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= ANEE ================= --}}
    <section class="anee-band fz-sec" id="anee">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400 reveal">And through all of it</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance reveal">Anee, the smart farm technician who knows your farm</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto reveal">
                She reads your schedules, stages and weather before answering. Ask in {{ $ph ? 'Tagalog or English' : 'plain English' }},
                send a photo of the problem, run When to Plant and What to Plant for your town, read your field from space,
                check your fertilizer plan, or have her write the whole season's report. Anee runs on credits, so you pay only for what you ask.
            </p>
            <div class="mt-8 reveal">
                <a href="{{ route('pricing') }}" class="btn btn-accent btn-lg">See plans and credits</a>
            </div>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Bring your next season here</h2>
            <p class="mt-4 text-gray-600">Set up your first cropping schedule in minutes: your lot, its crop and the whole calendar. Libre is free forever, and every tool above says the plan it comes with.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">Get Started</a>
                <a href="{{ route('tutorial') }}" class="btn btn-outline btn-lg">Watch the tutorial</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
(() => {
    /* The steps bar lights the step being read, and keeps it in view. */
    const links = [...document.querySelectorAll('[data-step-link]')];
    const steps = [...document.querySelectorAll('.fz-step')];
    if (links.length && 'IntersectionObserver' in window) {
        const row = document.querySelector('.fz-steps-row');
        const mark = (key) => links.forEach((a) => {
            const on = a.dataset.stepLink === key;
            a.classList.toggle('is-on', on);
            if (on && row && row.scrollWidth > row.clientWidth) row.scrollTo({ left: a.offsetLeft - 16, behavior: 'smooth' });
        });
        const io = new IntersectionObserver((es) => {
            const vis = es.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (vis.length) mark(vis[0].target.dataset.step);
        }, { rootMargin: '-35% 0px -55% 0px' });
        steps.forEach((s) => io.observe(s));
    }

    /* Every tool's film on a phone, with the tool before and after. */
    const modal = document.getElementById('fzModal');
    const shots = [...document.querySelectorAll('.fz-shot[data-film]')];
    if (!modal || !shots.length) return;
    const v = document.getElementById('fzModalVideo');
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let at = 0, last = null;
    const show = (i) => {
        at = (i + shots.length) % shots.length;
        const b = shots[at], card = b.closest('.fz-tool'), step = b.closest('.fz-step');
        document.getElementById('fzModalName').textContent = card.querySelector('h4').childNodes[0].textContent.trim();
        document.getElementById('fzModalWhat').textContent = card.querySelector('.fz-what').textContent;
        const helps = card.querySelector('.fz-helps ul'), help = document.getElementById('fzModalHelp');
        help.hidden = !helps;
        help.querySelector('ul').innerHTML = helps ? helps.innerHTML : '';
        document.getElementById('fzModalStep').textContent = 'Step ' + step.querySelector('.fz-step-n').textContent + ' · ' + step.querySelector('.fz-step-h').textContent;
        const g = card.querySelector('.fz-go'), guide = document.getElementById('fzModalGuide');
        guide.hidden = !g; if (g) guide.href = g.href;
        v.poster = b.dataset.poster; v.src = b.dataset.film;
        v.play().catch(() => {});
    };
    const open = (i) => {
        last = document.activeElement;
        modal.hidden = false;
        requestAnimationFrame(() => modal.classList.add('is-on'));
        document.documentElement.style.overflow = 'hidden';
        show(i);
        modal.querySelector('.fz-x').focus();
    };
    const close = () => {
        modal.classList.remove('is-on');
        v.pause(); v.removeAttribute('src'); v.load();
        document.documentElement.style.overflow = '';
        setTimeout(() => { modal.hidden = true; }, calm ? 0 : 280);
        last?.focus?.();
    };
    shots.forEach((b, i) => b.addEventListener('click', () => open(i)));
    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close]')) close();
        const s = e.target.closest('[data-step-film]');
        if (s) show(at + Number(s.dataset.stepFilm));
    });
    document.addEventListener('keydown', (e) => {
        if (modal.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') show(at + 1);
        if (e.key === 'ArrowLeft') show(at - 1);
    });
})();
</script>
@endpush
