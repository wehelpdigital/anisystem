@extends('layouts.public')

@include('public.partials.site-css')

@php
    /* The ads landing page (2026-09-29). Every word comes from
       App\Support\LandingPage: defaults in code, edits from the mother app
       (AniSystem > Landing page). The ad's tracking tags ride along to the
       signup, so a campaign can be measured to the account it made.
       The argument: precision agriculture; a bigger harvest despite the
       weather and the costs; every tool the farm needs, in one app. */
    $lp = $lp ?? \App\Support\LandingPage::content();
    $keep = collect(request()->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid']))
        ->filter(fn ($v) => is_string($v) && $v !== '' && strlen($v) <= 200)->all();
    $signup = route('signup') . ($keep ? '?' . http_build_query($keep) : '');
    $shots = [
        'board' => ['lp/board.webp', 'The anee.io season board: each day with its growth stage, and an open herbicide task with its DAT count', 1600],
        'growth' => ['lp/growth.webp', 'The growth stage of a rice lot, with what to do now and what to watch for', 1600],
        'weather' => ['lp/weather.webp', 'The forecast for a lot in Muñoz: the chance of rain by the day and by the hour', 1566],
        'hub' => ['lp/hub.webp', 'The parts of a season in anee.io: lots, workers, inventory, weather, growth stages, maps, Anee, reports and more', 1566],
        'report-top' => ['lp/report-top.webp', 'An anee.io season report: net profit, money in and out, harvest per hectare', 1600],
        'report-money' => ['lp/report-money.webp', 'Where the money went, by category and by month', 1600],
        'datediff' => ['lp/datediff.webp', 'The days between a herbicide and a fungicide, measured in one tap', 1600],
    ];
    $photos = ['anee-chat-hand' => ['lp/anee-chat-hand.webp', 'Anee answering a photo of a rice field, on a farmer\'s phone in the field', 1600]];
    $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $arrow = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $mark = fn (string $t) => \App\Support\LandingPage::marked($t);
    // The tiles, in their groups, in the order the editor keeps them.
    $groups = [];
    foreach ($lp['more']['items'] as $t) {
        $groups[trim($t['group'] ?? '')][] = $t;
    }
@endphp

@section('title', $lp['meta']['title'])
@section('meta_description', strip_tags($lp['meta']['description'] !== '' ? $lp['meta']['description'] : $lp['hero']['sub']))
{{-- No top bar here: every way out of the offer is a signup lost. --}}
@section('noHeader', '1')

@push('head')
@include('partials.ad-tags')
<style>
    /* ---- the landing page's own dress (site-css holds the shared pieces) ---- */
    /* Try it (2026-10-07): the crop and the day on the left, the season
       landing task by task on the right; each row slides in on the house
       curve and the line between them fills as they come. */
    .lp-demo { background: linear-gradient(180deg, #fff, #f6faf1); }
    .lpd { display: grid; gap: 1.2rem; align-items: start; }
    @media (min-width: 900px) { .lpd { grid-template-columns: minmax(0, 20rem) minmax(0, 1fr); gap: 1.6rem; } .lpd-ask { position: sticky; top: 1.5rem; } }
    .lpd-ask { padding: 1.2rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 20px 40px -34px rgb(20 33 12 / .6); }
    .lpd-q { display: flex; align-items: center; gap: .5rem; margin: .2rem 0 .6rem; font-family: var(--font-heading); font-weight: 800; color: #14210c; }
    .lpd-q + .lpd-crops + .lpd-q { margin-top: 1.1rem; }
    .lpd-q i { font-style: normal; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; font-size: .76rem; color: #fff; background: #4a7c2a; }
    .lpd-crops { display: grid; gap: .4rem; }
    .lpd-crops button { display: flex; align-items: center; gap: .45rem; padding: .6rem .8rem; border-radius: .9rem; text-align: left; font-weight: 800; color: #374151; background: #f6f8f3;
        border: 1px solid #e5ebdf; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lpd-crops button small { margin-left: auto; font-size: .72rem; font-weight: 600; opacity: .75; }
    .lpd-crops button[aria-checked="true"] { background: #2d5016; border-color: #2d5016; color: #fff; }
    .lpd-out { border-radius: 1.3rem; overflow: hidden; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 24px 48px -36px rgb(20 33 12 / .65); }
    .lpd-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .3rem .8rem; padding: .95rem 1.15rem; color: #e8efe1; background: linear-gradient(135deg, #3d6823, #24400f 80%); }
    .lpd-head b { font-family: var(--font-heading); font-size: 1.1rem; color: #fff; }
    .lpd-head span { font-size: .8rem; color: #cfe0bd; }
    .lpd-list { position: relative; display: grid; gap: .1rem; padding: .8rem 1rem 1rem 2.6rem; }
    .lpd-list::before { content: ''; position: absolute; left: 1.45rem; top: 1.3rem; bottom: 1.3rem; width: 2px; background: #e4efd4; }
    .lpd-list::after { content: ''; position: absolute; left: 1.45rem; top: 1.3rem; width: 2px; height: var(--fill, 0%); max-height: calc(100% - 2.6rem); background: #4a7c2a; transition: height 1.2s cubic-bezier(.22,1,.36,1); }
    .lpd-row { position: relative; display: grid; grid-template-columns: 5.6rem minmax(0, 1fr); gap: .2rem .8rem; align-items: baseline; padding: .55rem 0; border-bottom: 1px dashed #eef2ea;
        opacity: 0; transform: translateX(10px); transition: opacity .4s cubic-bezier(.22,1,.36,1), transform .4s cubic-bezier(.22,1,.36,1); }
    .lpd-row.is-in { opacity: 1; transform: none; }
    .lpd-row:last-child { border-bottom: 0; }
    .lpd-row::before { content: ''; position: absolute; left: -1.43rem; top: .9rem; width: .7rem; height: .7rem; border-radius: 999px; background: #fff; border: 2px solid #4a7c2a; }
    .lpd-row.is-key::before { background: #f5c518; border-color: #c79e00; }
    .lpd-when b { display: block; font-size: .86rem; color: #14210c; }
    .lpd-when small { font-size: .7rem; font-weight: 800; color: #4a7c2a; }
    .lpd-what b { font-size: .92rem; color: #1f2937; }
    .lpd-what small { display: block; margin-top: .1rem; font-size: .78rem; line-height: 1.45; color: #6b7280; }
    .lpd-what em { display: inline-block; margin-top: .25rem; padding: .1rem .5rem; border-radius: 999px; font-style: normal; font-size: .68rem; font-weight: 800; color: #075985; background: #e0f2fe; }
    .lpd-note { margin-top: 1rem; text-align: center; font-size: .8rem; color: #6b7280; }
    .lp-demo .fx-h em, .lp-demo .fx-h strong { font-style: normal; color: #4a7c2a; }
    /* New this season, on a dark band: four real screens. */
    .lp-space { position: relative; isolation: isolate; overflow: hidden; color: #e8efe1; background: radial-gradient(60rem 30rem at 80% 0%, #2c4f17 0%, transparent 60%), #0d1609; }
    .lp-space::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgb(255 255 255 / .1) 1px, transparent 1.5px); background-size: 22px 22px; opacity: .6; }
    .lp-space-kick { display: inline-block; padding: .3rem .8rem; border-radius: 999px; font-size: .76rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #1f1500; background: #f5c518; }
    .lp-space-h { margin: .8rem auto 0; max-width: 46rem; font-family: var(--font-heading); font-weight: 800; color: #fff; font-size: clamp(1.8rem, 4vw, 2.8rem); line-height: 1.15; }
    .lp-space-h em, .lp-space-h strong { font-style: normal; color: #f5c518; }
    .lp-space-p { margin: .9rem auto 0; max-width: 42rem; color: #b9caa8; line-height: 1.65; }
    .lp-space-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14.5rem, 1fr)); }
    .lp-space-card { overflow: hidden; border-radius: 1.3rem; background: rgb(255 255 255 / .05); border: 1px solid rgb(255 255 255 / .1);
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-space-card:hover { transform: translateY(-5px); border-color: rgb(245 197 24 / .5); }
    .lp-space-shot { position: relative; display: block; height: 15rem; overflow: hidden; background: linear-gradient(160deg, #1f3512, #0d1609); }
    .lp-space-shot img { position: absolute; left: 50%; top: 1.1rem; width: 70%; transform: translateX(-50%); border-radius: 1.1rem 1.1rem 0 0; box-shadow: 0 18px 34px -16px rgb(0 0 0 / .9);
        transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .lp-space-card:hover .lp-space-shot img { transform: translateX(-50%) translateY(-8px); }
    .lp-space-tx { padding: 1rem 1.1rem 1.2rem; }
    .lp-space-tx h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: #fff; }
    .lp-space-tx p { margin-top: .35rem; font-size: .88rem; line-height: 1.55; color: #b9caa8; }
    @media (prefers-reduced-motion: reduce) {
        .lpd-row { opacity: 1; transform: none; transition: none; }
        .lpd-list::after { transition: none; }
        .lpd-crops button, .lp-space-card, .lp-space-shot img { transition: none; }
    }

    .lp-hero { position: relative; isolation: isolate; overflow: hidden;
        background: radial-gradient(1200px 520px at 85% -10%, #e4efd4 0%, transparent 60%),
                    radial-gradient(900px 480px at -10% 110%, #fdf3c7 0%, transparent 55%), #fbfdf8; }
    .lp-hero::after { content: ''; position: absolute; inset: auto -10% -60px -10%; height: 120px; z-index: -1;
        background: #fff; border-radius: 50% 50% 0 0 / 100% 100% 0 0; }
    .lp-logo { display: inline-block; }
    .lp-logo img { display: block; height: 1.9rem; width: auto; }
    @media (min-width: 768px) { .lp-logo img { height: 2.2rem; } }
    .lp-kicker { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .85rem; border-radius: 999px;
        font-size: .78rem; font-weight: 800; color: #2d5016; background: #e4efd4; border: 1px solid #c9e0ad; }
    .lp-kicker i { width: .5rem; height: .5rem; border-radius: 999px; background: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .2);
        animation: lpBeat 2.2s ease-in-out infinite; }
    @keyframes lpBeat { 0%, 100% { box-shadow: 0 0 0 3px rgb(107 159 61 / .25); } 50% { box-shadow: 0 0 0 7px rgb(107 159 61 / .05); } }
    .lp-h1 { font-family: var(--font-heading); font-weight: 800; color: #14210c; line-height: 1.2; letter-spacing: -.02em;
        font-size: clamp(2.1rem, 5.2vw, 3.6rem); text-wrap: balance; }
    /* A long headline a size down, so the email box still shows on the first screen. */
    .lp-h1.is-long { font-size: clamp(1.8rem, 4.1vw, 2.9rem); line-height: 1.22; }
    .lp-h1 em, .lp-closer h2 em { font-style: normal; background: linear-gradient(transparent 62%, #fadd6d 62%); padding: 0 .1em; }
    .lp-closer h2 em { background: linear-gradient(transparent 62%, rgb(250 221 109 / .55) 62%); }
    .lp-sub { color: #3f4a37; font-size: clamp(1rem, 1.6vw, 1.15rem); line-height: 1.65; text-wrap: pretty; }
    .lp-form { display: flex; gap: .5rem; padding: .4rem; border-radius: 1.1rem; background: #fff;
        box-shadow: 0 18px 40px -22px rgb(20 33 12 / .45), 0 0 0 1px #dcead0; max-width: 34rem; }
    .lp-form input { flex: 1 1 auto; min-width: 0; border: 0; outline: none; background: transparent; padding: .8rem .9rem;
        font-size: 1rem; color: #14210c; }
    .lp-form button { flex: none; }
    @media (max-width: 520px) {
        .lp-form { flex-direction: column; padding: .5rem; }
        .lp-form button { width: 100%; }
    }
    .lp-note { display: flex; flex-wrap: wrap; gap: .35rem 1rem; margin-top: .85rem; font-size: .82rem; font-weight: 700; color: #4b5a3d; }
    /* The hero's words set against the phone, on a screen wide enough to have
       the two side by side; stacked on a phone they read from the left. */
    @media (min-width: 1024px) {
        .lp-copy.is-right { text-align: right; }
        .lp-copy.is-right .lp-sub, .lp-copy.is-right .lp-form { margin-left: auto; }
        .lp-copy.is-right .lp-note { justify-content: flex-end; }
    }
    .lp-note span { display: inline-flex; align-items: center; gap: .35rem; }
    .lp-note svg { width: 1rem; height: 1rem; color: #4a7c2a; }
    /* The hero's phone, and what floats beside it. */
    .lp-stage { position: relative; display: flex; justify-content: center; padding: 1.5rem 0 2.5rem; }
    .lp-stage .ph-frame { width: min(320px, 76vw); }
    .lp-float { position: absolute; z-index: 3; display: flex; align-items: center; gap: .55rem; padding: .6rem .8rem;
        border-radius: 1rem; background: #fff; box-shadow: 0 16px 36px -18px rgb(20 33 12 / .5), 0 0 0 1px #e7eedf;
        font-size: .8rem; font-weight: 800; color: #14210c; white-space: nowrap; animation: lpFloat 6s ease-in-out infinite; }
    .lp-float small { display: block; font-size: .68rem; font-weight: 700; color: #6b7a5e; }
    .lp-float .dot { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1rem; }
    .lp-float.f1 { top: 18%; left: max(0px, calc(50% - 260px)); }
    .lp-float.f2 { bottom: 16%; right: max(0px, calc(50% - 270px)); animation-delay: -3s; }
    @keyframes lpFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @media (max-width: 480px) { .lp-float.f1 { left: 0; top: 10%; } .lp-float.f2 { right: 0; bottom: 10%; } }
    /* The problem rows: photo and words side by side, the photo's side
       alternating (is-flip puts it on the right). White, so the hero's arc
       runs straight into it. */
    .lp-problems { background: #fff; }
    /* The title over the three problem rows. */
    .lp-problems-h { font-family: var(--font-heading); font-weight: 800; color: #14210c; letter-spacing: -.02em; line-height: 1.15;
        font-size: clamp(1.9rem, 4.4vw, 3rem); text-wrap: balance; }
    .lp-problems .fx-kicker + .lp-problems-h { margin-top: .5rem; }
    .lp-problems-sub { margin-top: .85rem; color: #3f4a37; font-size: clamp(1rem, 1.6vw, 1.15rem); line-height: 1.65; }
    .lp-row2 { display: grid; gap: 2.25rem; align-items: center; }
    .lp-row2 .lp-photo { aspect-ratio: 4 / 3; }
    /* A grid column may shrink below its longest unbroken line, or a one-line
       tick stretches the whole row past the phone's edge. */
    .lp-row2 > * { min-width: 0; }
    @media (min-width: 1024px) {
        .lp-row2 { grid-template-columns: 1fr 1fr; gap: 3.5rem; }
        .lp-row2.is-flip > .lp-photo { order: 2; }
    }
    /* How anee.io helps: a green card of ticks under each problem. */
    .lp-fixes { margin-top: 1.4rem; padding: 1rem 1.1rem 1.05rem; border-radius: 1.1rem; background: #f3f8ec; border: 1px solid #dcead0; }
    .lp-fixes-h { font-size: .74rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .lp-fixes .fx-list { margin-top: .55rem; }
    /* One line each, never two (the owner, 2026-09-30): the words are kept
       short, and a longer one from the editor ends in an ellipsis. */
    .lp-fixes .fx-list { grid-template-columns: minmax(0, 1fr); }
    .lp-fixes .fx-list li { align-items: center; min-width: 0; }
    .lp-fixes .fx-list li span { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lp-fixes .fx-list li svg { margin-top: 0; }
    @media (max-width: 400px) { .lp-fixes .fx-list li { font-size: .9rem; } }
    /* Problem */
    .lp-pain { display: grid; gap: .7rem; }
    .lp-pain li { display: flex; gap: .75rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 1rem;
        background: #fff7f5; border: 1px solid #fbdad3; color: #5b2a20; font-weight: 600; line-height: 1.5; }
    .lp-pain li b { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center;
        background: #fde2dc; color: #b42318; font-size: .9rem; }
    .lp-photo { border-radius: 1.5rem; overflow: hidden; box-shadow: 0 30px 60px -32px rgb(20 33 12 / .55); }
    .lp-photo img { display: block; width: 100%; height: 100%; object-fit: cover; }
    /* What guessing costs: a dark band of "up to" figures that count up once seen. */
    .lp-losses { position: relative; isolation: isolate; overflow: hidden; color: #fff;
        background: radial-gradient(900px 420px at 90% 0%, rgb(180 35 24 / .28), transparent 60%), linear-gradient(180deg, #16210f, #0f170a); }
    .lp-losses-sub { margin-top: .9rem; font-size: clamp(1rem, 1.6vw, 1.15rem); line-height: 1.6; color: rgb(255 255 255 / .8); }
    .lp-loss-grid { display: grid; gap: 1.1rem; }
    @media (min-width: 768px) { .lp-loss-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .lp-losses .loss-card { border-color: transparent; box-shadow: 0 20px 44px -26px rgb(0 0 0 / .7); }
    .lp-loss-note { margin-top: 1.1rem; font-size: .78rem; color: rgb(255 255 255 / .55); text-align: center; line-height: 1.55; }
    /* Precision: the four "rights" */
    .lp-rights { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14.5rem), 1fr)); }
    .lp-right { position: relative; padding: 1.35rem 1.2rem 1.25rem; border-radius: 1.25rem; background: #fff;
        border: 1px solid #e3ecd9; box-shadow: 0 14px 30px -26px rgb(20 33 12 / .55); overflow: hidden;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lp-right::before { content: ''; position: absolute; inset: 0 0 auto; height: 4px; background: linear-gradient(90deg, #6b9f3d, #f5c518); }
    .lp-right:hover { transform: translateY(-3px); box-shadow: 0 20px 36px -24px rgb(20 33 12 / .55); }
    .lp-right .e { display: grid; place-items: center; width: 2.9rem; height: 2.9rem; border-radius: 1rem; background: #f0f7e6; font-size: 1.45rem; }
    .lp-right h3 { margin-top: .85rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
    .lp-right p { margin-top: .35rem; color: #4b5563; line-height: 1.6; font-size: .93rem; }
    /* Three steps */
    .lp-steps { display: grid; gap: 1rem; counter-reset: s; }
    @media (min-width: 820px) { .lp-steps { grid-template-columns: repeat(3, 1fr); gap: 1.25rem; } }
    .lp-step { position: relative; padding: 1.4rem 1.25rem 1.3rem; border-radius: 1.25rem; background: #fff;
        border: 1px solid #e3ecd9; box-shadow: 0 12px 30px -24px rgb(20 33 12 / .5); }
    .lp-step b.n { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px;
        background: linear-gradient(135deg, #6b9f3d, #3d6823); color: #fff; font-weight: 900; font-size: 1.05rem; }
    .lp-step h3 { margin-top: .85rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
    .lp-step p { margin-top: .35rem; color: #4b5563; line-height: 1.6; font-size: .95rem; }
    @media (min-width: 820px) {
        .lp-step:not(:last-child)::after { content: ''; position: absolute; top: 2.6rem; right: -1.05rem; width: .9rem; height: 2px;
            background: repeating-linear-gradient(90deg, #6b9f3d 0 4px, transparent 4px 7px); }
    }
    /* Plan badge on a pillar */
    .lp-plan { display: inline-flex; align-items: center; gap: .35rem; margin-top: 1.1rem; padding: .3rem .75rem; border-radius: 999px;
        font-size: .76rem; font-weight: 800; color: #2d5016; background: #f3f8ec; border: 1px solid #dcead0; }
    .lp-plan.is-paid { color: #7a4b00; background: #fff7df; border-color: #f6e2a4; }
    /* Everything in one app: the season's modules beside the tools, grouped. */
    .lp-all { display: grid; gap: 2.5rem; align-items: start; }
    @media (min-width: 1024px) { .lp-all { grid-template-columns: 20rem 1fr; gap: 3.5rem; } .lp-all-phone { position: sticky; top: 2rem; } }
    .lp-all-phone { display: flex; justify-content: center; }
    .lp-all-phone .ph-frame { width: min(280px, 72vw); }
    .lp-group + .lp-group { margin-top: 1.6rem; }
    .lp-group-h { display: flex; align-items: center; gap: .6rem; margin-bottom: .7rem; font-size: .74rem; font-weight: 900;
        letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
    .lp-group-h::after { content: ''; flex: 1; height: 1px; background: #dcead0; }
    /* Two to a row everywhere, so a group of four is a square, never three and
       an orphan; a group's odd last tile takes the whole row. */
    .lp-tiles { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lp-tile:last-child:nth-child(odd) { grid-column: 1 / -1; }
    .lp-tile { display: flex; gap: .75rem; align-items: flex-start; padding: .9rem .95rem; border-radius: 1rem; background: #fff;
        border: 1px solid #e7eee0; transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lp-tile:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -22px rgb(20 33 12 / .45); }
    .lp-tile .e { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .8rem; background: #f3f8ec;
        font-size: 1.2rem; line-height: 1; }
    .lp-tile h4 { font-weight: 800; color: #14210c; font-size: .95rem; line-height: 1.3; }
    .lp-tile p { margin-top: .15rem; font-size: .84rem; color: #5b6651; line-height: 1.45; }
    @media (max-width: 559px) {
        .lp-tile { flex-direction: column; gap: .5rem; padding: .8rem; }
        .lp-tile h4 { font-size: .9rem; }
        .lp-tile p { font-size: .8rem; }
    }
    /* Testimonials */
    .lp-quotes { display: grid; gap: 1.1rem; }
    @media (min-width: 900px) { .lp-quotes { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .lp-quote { display: flex; flex-direction: column; gap: 1rem; padding: 1.4rem; border-radius: 1.25rem; background: #fff;
        border: 1px solid #e7eee0; box-shadow: 0 14px 34px -26px rgb(20 33 12 / .5); }
    .lp-quote blockquote { font-size: 1.02rem; line-height: 1.6; color: #1f2a17; font-weight: 600; }
    .lp-quote figcaption { display: flex; align-items: center; gap: .75rem; margin-top: auto; }
    .lp-quote img, .lp-quote .ini { width: 3rem; height: 3rem; border-radius: 999px; object-fit: cover; flex: none; }
    .lp-quote .ini { display: grid; place-items: center; background: #e4efd4; color: #2d5016; font-weight: 900; }
    .lp-quote b { display: block; color: #14210c; }
    .lp-quote small { display: block; color: #6b7a5e; font-size: .8rem; }
    .lp-stars { color: #eab308; letter-spacing: .1em; font-size: .95rem; }
    /* The measurable part of a testimonial, set apart so the eye lands on it. */
    .lp-result { display: inline-flex; align-self: flex-start; align-items: center; gap: .4rem; padding: .35rem .75rem; border-radius: 999px;
        background: #ecfccb; color: #2d5016; font-size: .84rem; font-weight: 800; }
    .lp-result svg { width: 1rem; height: 1rem; flex: none; }
    /* FAQ: folds on a 0fr/1fr grid, animated, never a snap. */
    .lp-faq { display: grid; gap: .7rem; }
    .lp-qa { border-radius: 1.1rem; background: #fff; border: 1px solid #e3ecd9; overflow: hidden;
        transition: box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-qa.is-open { border-color: #c9e0ad; box-shadow: 0 14px 30px -24px rgb(20 33 12 / .45); }
    .lp-qa button { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem;
        text-align: left; font-family: var(--font-heading); font-weight: 800; color: #14210c; font-size: 1rem; cursor: pointer; }
    .lp-qa button svg { flex: none; width: 1.3rem; height: 1.3rem; color: #4a7c2a; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lp-qa.is-open button svg { transform: rotate(45deg); }
    .lp-qa .a { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .lp-qa.is-open .a { grid-template-rows: 1fr; }
    .lp-qa .a > div { min-height: 0; overflow: hidden; }
    .lp-qa .a p { padding: 0 1.15rem 1.05rem; color: #4b5563; line-height: 1.65; }
    /* The closer */
    .lp-closer { position: relative; isolation: isolate; overflow: hidden; color: #fff; }
    .lp-closer img.bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; object-position: center 35%; }
    .lp-closer::before { content: ''; position: absolute; inset: 0; z-index: -1;
        background: linear-gradient(180deg, rgb(13 19 9 / .55), rgb(13 19 9 / .82) 60%, rgb(13 19 9 / .92)); }
    .lp-closer .lp-form { box-shadow: 0 22px 44px -20px rgb(0 0 0 / .6); margin-inline: auto; }
    .lp-risk { margin-top: 1rem; font-size: .9rem; font-weight: 700; color: #d9f99d; }
    /* The bar that waits at the foot of a phone once the hero's form has gone by. */
    .lp-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 45; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom, 0px));
        background: rgb(255 255 255 / .96); backdrop-filter: blur(8px); border-top: 1px solid #e3ecd9;
        transform: translateY(110%); transition: transform .32s cubic-bezier(.22,1,.36,1); }
    .lp-bar.is-on { transform: none; }
    .lp-bar a { width: 100%; }
    @media (min-width: 900px) { .lp-bar { display: none; } }
    @media (prefers-reduced-motion: reduce) {
        .lp-float, .lp-kicker i { animation: none; }
        .lp-bar, .lp-qa .a, .lp-qa button svg, .lp-tile, .lp-right, .loss-bar i { transition: none; }
    }
</style>
@endpush

@section('content')

    {{-- ================= 1. HERO ================= --}}
    <section class="lp-hero">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6 pb-16 sm:pt-8 sm:pb-24">
            {{-- The brand, and nothing to click away to. --}}
            <span class="lp-logo"><img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io" width="220" height="44"></span>
            <div class="grid lg:grid-cols-[1.1fr_1fr] gap-8 lg:gap-12 items-center mt-8 sm:mt-10">
                <div class="lp-copy animate-fade-up {{ ($lp['hero']['align'] ?? 'left') === 'right' ? 'is-right' : '' }}">
                    <span class="lp-kicker"><i aria-hidden="true"></i>{{ $lp['hero']['kicker'] }}</span>
                    <h1 class="lp-h1 mt-5 {{ mb_strlen($lp['hero']['headline']) > 70 ? 'is-long' : '' }}">{!! $mark($lp['hero']['headline']) !!}</h1>
                    <p class="lp-sub mt-5 max-w-xl">{{ $lp['hero']['sub'] }}</p>
                    <form class="lp-form mt-7" action="{{ route('signup') }}" method="get" id="lpHeroForm">
                        @foreach ($keep as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                        <input type="email" name="email" required autocomplete="email" inputmode="email" placeholder="Your email address" aria-label="Your email address">
                        <button type="submit" class="btn btn-accent btn-lg">{{ $lp['hero']['cta'] }} {!! $arrow !!}</button>
                    </form>
                    <p class="lp-note">
                        @foreach (preg_split('/\s*[.·]\s+|\s*\.\s*$/', trim($lp['hero']['note'])) as $bit)
                            @if (trim($bit) !== '')<span>{!! $check !!}{{ rtrim(trim($bit), '.') }}</span>@endif
                        @endforeach
                    </p>
                </div>
                <div class="lp-stage animate-fade-up" style="animation-delay: .12s">
                    <div class="ph-frame ph-tilt-r">
                        <img src="{{ \App\Support\LandingPage::imageUrl($lp['hero']['image'], $shots['board'][0]) }}" alt="{{ $shots['board'][1] }}" width="780" height="1600" fetchpriority="high">
                    </div>
                    {{-- A chip left without a title in the editor is not drawn. --}}
                    @foreach (array_slice(array_values(array_filter($lp['hero']['chips'] ?? [], fn ($c) => trim($c['title'] ?? '') !== '')), 0, 2) as $ci => $chip)
                        <div class="lp-float f{{ $ci + 1 }}" aria-hidden="true">
                            <span class="dot" style="background:{{ $ci ? '#e0edfb' : '#fff3e6' }}">{{ $chip['icon'] ?? '🌱' }}</span>
                            <span>{{ $chip['title'] ?? '' }}<small>{{ $chip['sub'] ?? '' }}</small></span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================= 2. THE PROBLEM: the weather, then fuel, then fertilizer =================
         Photo and words side by side, the photo's side alternating: the
         weather's on the left, fuel's on the right, fertilizer's on the left.
         Each row says what goes wrong, then how anee.io helps. --}}
    @php
        $photoAlts = [
            'storm' => 'Freshly transplanted paddies under a grey, rainy sky',
            'tractor' => 'A farmer plowing a flooded paddy with a diesel hand tractor',
            'sacks' => 'A farmer among the fertilizer sacks in his shed, writing in his notebook',
            'palay-phone' => 'A farmer checking anee.io on his phone among ripening palay',
            'anee-chat-hand' => 'Anee answering a photo of a rice field, on a farmer\'s phone in the field',
        ];
        $rowPhoto = fn (?string $upload, string $key) => \App\Support\LandingPage::imageUrl($upload, 'lp/' . (isset($photoAlts[$key]) ? $key : 'storm') . '.webp');
    @endphp
    <section class="lp-problems overflow-x-clip">
        @if (trim($lp['problem']['sectionTitle'] ?? '') !== '')
            <div class="max-w-3xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14 text-center reveal">
                @if (trim($lp['problem']['sectionKicker'] ?? '') !== '')<span class="fx-kicker">{{ $lp['problem']['sectionKicker'] }}</span>@endif
                <h2 class="lp-problems-h">{{ $lp['problem']['sectionTitle'] }}</h2>
                @if (trim($lp['problem']['sectionSub'] ?? '') !== '')<p class="lp-problems-sub">{{ $lp['problem']['sectionSub'] }}</p>@endif
            </div>
        @endif
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-16 sm:pt-14 sm:pb-24 space-y-20 sm:space-y-28">
            <div class="lp-row2">
                <div class="lp-photo reveal">
                    <img src="{{ $rowPhoto($lp['problem']['image'], 'storm') }}" alt="{{ $photoAlts['storm'] }}" width="1400" height="1050" loading="lazy">
                </div>
                <div class="reveal">
                    <span class="fx-kicker">{{ $lp['problem']['kicker'] }}</span>
                    <h2 class="fx-h">{{ $lp['problem']['headline'] }}</h2>
                    <ul class="lp-pain mt-6">
                        @foreach ($lp['problem']['bullets'] as $b)
                            <li><b aria-hidden="true">✕</b><span>{{ $b }}</span></li>
                        @endforeach
                    </ul>
                    @if (! empty($lp['problem']['fixes']))
                        <div class="lp-fixes">
                            <p class="lp-fixes-h">{{ $lp['costs']['helpsLabel'] }}</p>
                            <ul class="fx-list">
                                @foreach ($lp['problem']['fixes'] as $f)
                                    <li>{!! $check !!}<span>{{ $f }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            @foreach ($lp['costs']['items'] as $i => $c)
                @php $key = $c['image'] ?: 'tractor'; @endphp
                <div class="lp-row2 {{ $i % 2 === 0 ? 'is-flip' : '' }}">
                    <div class="lp-photo reveal">
                        <img src="{{ $rowPhoto($c['upload'] ?? '', $key) }}" alt="{{ $photoAlts[$key] ?? $c['headline'] }}" width="1400" height="1050" loading="lazy">
                    </div>
                    <div class="reveal">
                        <span class="fx-kicker">{{ $c['kicker'] }}</span>
                        <h2 class="fx-h">{{ $c['headline'] }}</h2>
                        @if (trim($c['text']) !== '')<p class="fx-p">{{ $c['text'] }}</p>@endif
                        @if (! empty($c['fixes']))
                            <div class="lp-fixes">
                                <p class="lp-fixes-h">{{ $lp['costs']['helpsLabel'] }}</p>
                                <ul class="fx-list">
                                    @foreach ($c['fixes'] as $f)
                                        <li>{!! $check !!}<span>{{ $f }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= What guessing costs a hectare =================
         The home page's eight leaks ("What a Season Loses Without
         Intervention"), in its own cards: a photo, an "up to" counter that
         counts up with its red bar, and what it takes from a hectare. White
         cards on the dark band. --}}
    @if (! empty($lp['losses']['items']))
        <section class="lp-losses" id="lpLosses">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
                <div class="max-w-2xl mx-auto text-center reveal">
                    <h2 class="font-heading font-extrabold text-2xl sm:text-4xl text-balance">{{ $lp['losses']['headline'] }}</h2>
                    @if (trim($lp['losses']['sub'] ?? '') !== '')<p class="lp-losses-sub">{{ $lp['losses']['sub'] }}</p>@endif
                </div>
                <div class="lp-loss-grid mt-10">
                    @foreach ($lp['losses']['items'] as $i => $l)
                        @php
                            $n = max(0, min(100, (int) ($l['n'] ?? 0)));
                            $pic = in_array($l['image'] ?? '', \App\Support\LandingPage::LOSS_PHOTOS, true) ? $l['image'] : 'palay-heads';
                            $peso = trim($l['peso'] ?? '');
                        @endphp
                        <div class="loss-card loss-card2 reveal" style="--loss: {{ $n }}%; --reveal-delay: {{ ($i % 2) * 0.08 }}s">
                            <div class="loss-img"><img src="{{ \App\Support\LandingPage::imageUrl($l['upload'] ?? '', 'lp/loss/' . $pic . '.webp') }}" alt="" width="400" height="500" loading="lazy"></div>
                            <div class="loss-body">
                                <p class="loss-upto">Up to</p>
                                <p class="loss-n"><span data-n="{{ $n }}">{{ $n }}</span><small>%</small></p>
                                <p class="loss-l">{{ $l['title'] ?? '' }}</p>
                                @if (trim($l['text'] ?? '') !== '')<p class="loss-p">{{ $l['text'] }}</p>@endif
                                <p class="loss-peso">{{ \App\Support\Region::ph() && $peso !== '' ? $peso . ' lost per hectare' : 'up to ' . $n . '% of what a hectare earns, lost' }}</p>
                                <div class="loss-bar" aria-hidden="true"><i></i></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if (trim($lp['losses']['note'] ?? '') !== '')<p class="lp-loss-note reveal">{{ $lp['losses']['note'] }}</p>@endif
            </div>
        </section>
    @endif

    {{-- ================= The answer: precision agriculture, then how it works ================= --}}
    <section class="py-16 sm:py-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <span class="fx-kicker">{{ $lp['precision']['kicker'] }}</span>
                <h2 class="fx-h mx-auto max-w-3xl text-balance">{{ $lp['precision']['headline'] }}</h2>
                @if (trim($lp['precision']['sub']) !== '')<p class="fx-p mx-auto max-w-2xl">{{ $lp['precision']['sub'] }}</p>@endif
            </div>
            <div class="lp-rights mt-9">
                @foreach ($lp['precision']['items'] as $i => $r)
                    <div class="lp-right reveal" style="--reveal-delay: {{ $i * 0.07 }}s">
                        <span class="e" aria-hidden="true">{{ $r['icon'] ?: '🌱' }}</span>
                        <h3>{{ $r['title'] }}</h3>
                        <p>{{ $r['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-16 sm:mt-20 text-center reveal">
                <span class="fx-kicker">{{ $lp['problem']['solutionKicker'] }}</span>
                <h2 class="fx-h mx-auto max-w-2xl text-balance">{{ $lp['problem']['solutionHeadline'] }}</h2>
            </div>
            <div class="lp-steps mt-8">
                @foreach ($lp['problem']['steps'] as $i => $st)
                    <div class="lp-step reveal" style="--reveal-delay: {{ $i * 0.08 }}s">
                        <b class="n">{{ $i + 1 }}</b>
                        <h3>{{ $st['title'] }}</h3>
                        <p>{{ $st['text'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center reveal">
                <a href="{{ $signup }}" class="btn btn-primary btn-lg">{{ $lp['hero']['cta'] }} {!! $arrow !!}</a>
            </div>
        </div>
    </section>

    {{-- ================= Try it: a season in ten seconds (2026-10-07) =================
         The visitor picks a crop and a planting day and watches a sample
         season land on its days, counted the way anee.io counts them.
         The tasks are common practice, said as a sample under the list. --}}
    @if (! empty($lp['demo']['headline']))
    <section class="lp-demo" id="lpDemo">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
            <div class="text-center reveal">
                <span class="fx-kicker">{{ $lp['demo']['kicker'] }}</span>
                <h2 class="fx-h mx-auto max-w-3xl text-balance">{!! $mark($lp['demo']['headline']) !!}</h2>
                @if (trim($lp['demo']['sub']) !== '')<p class="fx-p mx-auto max-w-2xl">{{ $lp['demo']['sub'] }}</p>@endif
            </div>
            <div class="lpd mt-9 reveal">
                <div class="lpd-ask">
                    <p class="lpd-q"><i>1</i>Your crop</p>
                    <div class="lpd-crops" role="radiogroup" aria-label="Your crop">
                        <button type="button" role="radio" aria-checked="true" data-crop="rice">🌾 {{ \App\Support\Region::ph() ? 'Palay' : 'Rice' }}<small>transplanted</small></button>
                        <button type="button" role="radio" aria-checked="false" data-crop="corn">🌽 {{ \App\Support\Region::ph() ? 'Mais' : 'Corn' }}<small>yellow corn</small></button>
                        <button type="button" role="radio" aria-checked="false" data-crop="veg">🍆 {{ \App\Support\Region::ph() ? 'Talong' : 'Eggplant' }}<small>transplanted</small></button>
                    </div>
                    <p class="lpd-q"><i>2</i>The day you plant</p>
                    <input type="date" id="lpdDate" class="form-input w-full" aria-label="The day you plant">
                    <a href="{{ $signup }}" class="btn btn-primary btn-lg w-full mt-5 lpd-go">{{ $lp['demo']['cta'] }} {!! $arrow !!}</a>
                </div>
                <div class="lpd-out" aria-live="polite">
                    <div class="lpd-head"><b id="lpdTitle">Your season</b><span id="lpdSpan"></span></div>
                    <ol class="lpd-list" id="lpdList"></ol>
                </div>
            </div>
            @if (trim($lp['demo']['note'] ?? '') !== '')<p class="lpd-note reveal">{{ $lp['demo']['note'] }}</p>@endif
        </div>
    </section>
    @endif

    {{-- ================= New this season: from space and from the label (2026-10-07) ================= --}}
    @if (! empty($lp['space']['items']))
    <section class="lp-space">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24">
            <div class="text-center reveal">
                <span class="lp-space-kick">{{ $lp['space']['kicker'] }}</span>
                <h2 class="lp-space-h text-balance">{!! $mark($lp['space']['headline']) !!}</h2>
                @if (trim($lp['space']['sub']) !== '')<p class="lp-space-p">{{ $lp['space']['sub'] }}</p>@endif
            </div>
            <div class="lp-space-grid mt-10">
                @foreach ($lp['space']['items'] as $i => $t)
                    @php $img = in_array($t['image'] ?? '', ['satellite', 'sky', 'npk', 'finder', 'stash'], true) ? $t['image'] : 'sky'; @endphp
                    <div class="lp-space-card reveal" style="--reveal-delay: {{ ($i % 4) * 0.07 }}s">
                        <span class="lp-space-shot"><img src="{{ asset('images/site/app/' . $img . '.webp') }}" alt="{{ $t['title'] }} in anee.io" width="780" height="1520" loading="lazy"></span>
                        <div class="lp-space-tx"><h3>{{ $t['title'] }}</h3><p>{{ $t['text'] }}</p></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center reveal">
                <a href="{{ $signup }}" class="btn btn-accent btn-lg">{{ $lp['hero']['cta'] }} {!! $arrow !!}</a>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= 4. FEATURES AS BENEFITS (zigzag) ================= --}}
    <section class="py-16 sm:py-24 bg-gradient-to-b from-brand-50/60 to-white overflow-x-clip">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-20 sm:space-y-28">
            @foreach ($lp['pillars'] as $i => $p)
                @php
                    // An upload from the editor stands in for the built-in picture,
                    // framed as a phone screenshot or shown as a photo.
                    $key = $p['image'] ?: 'board';
                    $upload = \App\Support\LandingPage::photoUrl($p['upload'] ?? '');
                    $isPhoto = $upload ? ($p['frame'] ?? '') === 'photo' : isset($photos[$key]);
                    [$src, $alt, $tall] = $photos[$key] ?? $shots[$key] ?? $shots['board'];
                    $src = $upload ?: asset('images/site/' . $src);
                    $alt = $upload ? $p['title'] : $alt;
                    $paid = ! str_contains(strtolower($p['plan'] ?? ''), 'free');
                @endphp
                <div class="fx-row {{ $i % 2 ? 'is-flip' : '' }}">
                    <div class="fx-media fx-glow reveal">
                        @if ($isPhoto)
                            <div class="lp-photo" style="max-width: 26rem; aspect-ratio: 3 / 4">
                                <img src="{{ $src }}" alt="{{ $alt }}" width="1200" height="1600" loading="lazy">
                            </div>
                        @else
                            <div class="ph-frame {{ $i % 2 ? 'ph-tilt-l' : 'ph-tilt-r' }}">
                                <img src="{{ $src }}" alt="{{ $alt }}" width="780" height="{{ $tall }}" loading="lazy">
                            </div>
                        @endif
                    </div>
                    <div class="reveal">
                        <span class="fx-kicker">{{ $p['kicker'] }}</span>
                        <h2 class="fx-h">{{ $p['title'] }}</h2>
                        <p class="fx-p">{{ $p['text'] }}</p>
                        <ul class="fx-list">
                            @foreach ($p['bullets'] as $b)
                                <li>{!! $check !!}<span>{{ $b }}</span></li>
                            @endforeach
                        </ul>
                        @if (! empty($p['plan']))<span class="lp-plan {{ $paid ? 'is-paid' : '' }}">{{ $p['plan'] }}</span>@endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= Everything the farm needs, in one app ================= --}}
    <section class="pt-14 pb-16 sm:pt-20 sm:pb-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <h2 class="fx-h mx-auto max-w-3xl text-balance">{{ $lp['more']['headline'] }}</h2>
                @if (trim($lp['more']['sub'] ?? '') !== '')<p class="fx-p mx-auto max-w-2xl">{{ $lp['more']['sub'] }}</p>@endif
            </div>
            <div class="lp-all mt-10">
                <div class="lp-all-phone reveal">
                    <div class="ph-frame ph-tilt-l">
                        <img src="{{ \App\Support\LandingPage::imageUrl($lp['more']['image'] ?? '', $shots['hub'][0]) }}" alt="{{ $shots['hub'][1] }}" width="780" height="{{ $shots['hub'][2] }}" loading="lazy">
                    </div>
                </div>
                <div>
                    @foreach ($groups as $name => $tiles)
                        <div class="lp-group reveal">
                            @if ($name !== '')<p class="lp-group-h">{{ $name }}</p>@endif
                            <div class="lp-tiles">
                                @foreach ($tiles as $t)
                                    <div class="lp-tile">
                                        <span class="e" aria-hidden="true">{{ $t['icon'] ?: '🌱' }}</span>
                                        <div><h4>{{ $t['title'] }}</h4><p>{{ $t['text'] }}</p></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================= 5. TESTIMONIALS — real farmers only, written in the mother app ================= --}}
    @if (! empty($lp['testimonials']['items']))
        <section class="py-16 sm:py-24 bg-brand-50/60">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <h2 class="fx-h text-center reveal">{{ $lp['testimonials']['headline'] }}</h2>
                <div class="lp-quotes mt-10">
                    @foreach (array_slice($lp['testimonials']['items'], 0, 3) as $i => $t)
                        @php $photo = \App\Support\LandingPage::photoUrl($t['photo'] ?? null); @endphp
                        <figure class="lp-quote reveal" style="--reveal-delay: {{ $i * 0.08 }}s">
                            @if (($t['rating'] ?? 0) > 0)<span class="lp-stars" aria-label="{{ (int) $t['rating'] }} out of 5">{{ str_repeat('★', min(5, (int) $t['rating'])) }}</span>@endif
                            <blockquote>“{{ $t['quote'] ?? '' }}”</blockquote>
                            @if (trim($t['result'] ?? '') !== '')<p class="lp-result">{!! $check !!}<span>{{ $t['result'] }}</span></p>@endif
                            <figcaption>
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="{{ $t['name'] ?? '' }}" loading="lazy" width="96" height="96">
                                @else
                                    <span class="ini" aria-hidden="true">{{ mb_strtoupper(mb_substr($t['name'] ?? '?', 0, 1)) }}</span>
                                @endif
                                <span><b>{{ $t['name'] ?? '' }}</b><small>{{ collect([$t['role'] ?? '', $t['location'] ?? ''])->filter()->implode(' · ') }}</small></span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= 6. FAQ ================= --}}
    <section class="py-16 sm:py-24">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <h2 class="fx-h text-center reveal">{{ $lp['faq']['headline'] }}</h2>
            <div class="lp-faq mt-8" id="lpFaq">
                @foreach ($lp['faq']['items'] as $i => $qa)
                    <div class="lp-qa reveal">
                        <button type="button" aria-expanded="false" aria-controls="lpA{{ $i }}">
                            <span>{{ $qa['q'] }}</span>
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <div class="a" id="lpA{{ $i }}" role="region"><div><p>{{ $qa['a'] }}</p></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= 7. THE CLOSER ================= --}}
    <section class="lp-closer" id="lpCloser">
        <img class="bg" src="{{ \App\Support\LandingPage::imageUrl($lp['closer']['image'], 'lp/palay-phone.webp') }}" alt="" width="1400" height="1046" loading="lazy">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-20 sm:py-28 text-center">
            <h2 class="font-heading font-extrabold text-3xl sm:text-5xl leading-tight text-balance reveal">{!! $mark($lp['closer']['headline']) !!}</h2>
            <p class="mt-4 text-base sm:text-lg text-gray-200 reveal">{{ $lp['closer']['sub'] }}</p>
            <form class="lp-form mt-8 reveal" action="{{ route('signup') }}" method="get">
                @foreach ($keep as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <input type="email" name="email" required autocomplete="email" inputmode="email" placeholder="Your email address" aria-label="Your email address">
                <button type="submit" class="btn btn-accent btn-lg">{{ $lp['closer']['cta'] }} {!! $arrow !!}</button>
            </form>
            <p class="lp-risk reveal">{{ $lp['closer']['risk'] }}</p>
        </div>
    </section>

    {{-- The phone's bar: the offer, one thumb away, once the hero's form has gone by. --}}
    <div class="lp-bar" id="lpBar" aria-hidden="true">
        <a href="{{ $signup }}" class="btn btn-accent btn-lg" tabindex="-1">{{ $lp['hero']['cta'] }} {!! $arrow !!}</a>
    </div>

@endsection

@push('scripts')
<script>
(() => {
    // FAQ: one open at a time, each folding on its own grid (see .lp-qa).
    document.getElementById('lpFaq')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.lp-qa > button');
        if (!btn) return;
        const qa = btn.parentElement;
        const open = !qa.classList.contains('is-open');
        document.querySelectorAll('#lpFaq .lp-qa.is-open').forEach((x) => { x.classList.remove('is-open'); x.querySelector('button').setAttribute('aria-expanded', 'false'); });
        qa.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // What guessing costs: each figure counts up, and its bar fills, the first time it is seen.
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const losses = document.querySelectorAll('#lpLosses .loss-card');
    const light = (card) => {
        card.classList.add('is-lit');
        const b = card.querySelector('[data-n]');
        const to = Number(b.dataset.n) || 0;
        if (calm) { b.textContent = to; return; }
        const t0 = performance.now();
        const step = (t) => {
            const k = Math.min(1, (t - t0) / 1100);
            b.textContent = Math.round(to * (1 - Math.pow(1 - k, 3)));
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };
    if (losses.length && 'IntersectionObserver' in window) {
        losses.forEach((c) => { if (!calm) c.querySelector('[data-n]').textContent = '0'; });
        const io = new IntersectionObserver((entries) => entries.forEach((en) => {
            if (en.isIntersecting) { light(en.target); io.unobserve(en.target); }
        }), { threshold: .4 });
        losses.forEach((c) => io.observe(c));
    } else {
        losses.forEach(light);
    }

    // The phone's bar: on once the hero's form is out of sight, off again at the closer's own form.
    const bar = document.getElementById('lpBar');
    const hero = document.getElementById('lpHeroForm');
    const closer = document.getElementById('lpCloser');
    if (bar && hero && closer && 'IntersectionObserver' in window) {
        let heroGone = false, closerIn = false;
        const paint = () => {
            const on = heroGone && !closerIn;
            bar.classList.toggle('is-on', on);
            bar.setAttribute('aria-hidden', on ? 'false' : 'true');
            bar.querySelector('a').tabIndex = on ? 0 : -1;
        };
        new IntersectionObserver(([e]) => { heroGone = !e.isIntersecting && e.boundingClientRect.top < 0; paint(); }).observe(hero);
        new IntersectionObserver(([e]) => { closerIn = e.isIntersecting; paint(); }, { threshold: .15 }).observe(closer);
    }
})();
</script>

<script>
(() => {
    /* Try it: a sample season, counted the way anee.io counts it. Common
       practice only; the note under the list says so. */
    const box = document.getElementById('lpDemo');
    if (!box) return;
    const ph = @json(\App\Support\Region::ph());
    const SEASONS = {
        rice: { name: ph ? 'Palay, transplanted' : 'Rice, transplanted', unit: 'DAT', len: 105, tasks: [
            [-21, 'Prepare the seedbed', 'Soak and sow the seed for the seedlings.', 0, 'DAS 0'],
            [-14, 'Plow and flood the field', 'First plowing, then let the stubble rot under water.'],
            [-2, 'Final harrowing and leveling', 'A level field keeps the water even and the weeds down.'],
            [0, 'Transplant, with basal fertilizer', 'Complete and ammonium phosphate, worked into the mud.', 1],
            [3, 'Pre-emergence herbicide', 'On standing water, three days after transplanting.', 0, '', 'Checks the forecast first'],
            [14, 'First top-dress', 'Urea at early tillering, on a thin sheet of water.', 1],
            [35, 'Second top-dress', 'Urea and potash at panicle initiation.', 1],
            [45, 'Scout for stem borer and leaffolder', 'Spray only past the threshold.', 0, '', 'Checks the forecast first'],
            [60, 'Flowering: keep 5 cm of water', 'The stage that decides the grain.'],
            [85, 'Drain the field', 'Two weeks before harvest.'],
            [100, 'Harvest window', 'When 85 percent of the grains are golden.', 1],
        ] },
        corn: { name: ph ? 'Mais, yellow corn' : 'Yellow corn', unit: 'DAP', len: 110, tasks: [
            [-14, 'Plow and harrow', 'Two passes, then furrows 75 cm apart.'],
            [0, 'Plant, with basal fertilizer', 'Seeds in the furrow, complete fertilizer beside them.', 1],
            [3, 'Pre-emergence herbicide', 'On moist soil, before the weeds come up.', 0, '', 'Checks the forecast first'],
            [14, 'First side-dress and off-barring', 'Urea beside the row, soil pulled away from the plants.', 1],
            [21, 'Scout for fall armyworm', 'Look into the whorl; spray only when needed.', 0, '', 'Checks the forecast first'],
            [30, 'Second side-dress and hilling up', 'Urea, then soil back against the stalks.', 1],
            [55, 'Tasseling and silking', 'Water now if the soil is dry.'],
            [100, 'Harvest window', 'When the husks are dry and the black layer shows.', 1],
        ] },
        veg: { name: ph ? 'Talong, transplanted' : 'Eggplant, transplanted', unit: 'DAT', len: 120, tasks: [
            [-30, 'Sow the seedlings', 'In trays or a seedbed, under a light shade.', 0, 'DAS 0'],
            [-7, 'Prepare raised beds', 'Beds 1 m wide with mulch, and organic fertilizer worked in.'],
            [0, 'Transplant, with basal fertilizer', 'In the late afternoon, then water.', 1],
            [7, 'Replant the missing hills', 'Keep the stand even.'],
            [14, 'First side-dress', 'Complete fertilizer beside each plant.', 1],
            [25, 'Stake the plants', 'Before the first fruits weigh them down.'],
            [30, 'Scout for fruit and shoot borer', 'Cut and bury the wilted tips each week.', 0, '', 'Checks the forecast first'],
            [50, 'First harvest', 'Then every three to four days.', 1],
            [110, 'Last harvest and clearing', 'Pull the old plants to break the pest cycle.'],
        ] },
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    let crop = 'rice', timers = [];
    const d0 = new Date(); d0.setDate(d0.getDate() + 14);
    $('lpdDate').value = d0.toISOString().slice(0, 10);
    const fmt = (d) => d.toLocaleDateString(ph ? 'en-PH' : undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    const draw = () => {
        timers.forEach(clearTimeout); timers = [];
        const S = SEASONS[crop];
        const base = new Date(($('lpdDate').value || d0.toISOString().slice(0, 10)) + 'T00:00:00');
        const at = (n) => { const d = new Date(base); d.setDate(d.getDate() + n); return d; };
        $('lpdTitle').textContent = S.name;
        $('lpdSpan').textContent = fmt(at(S.tasks[0][0])) + ' to ' + fmt(at(S.tasks[S.tasks.length - 1][0])) + ' · ' + S.tasks.length + ' tasks';
        const list = $('lpdList');
        list.style.setProperty('--fill', '0%');
        list.innerHTML = S.tasks.map(([n, t, sub, key, label, wx]) => '<li class="lpd-row' + (key ? ' is-key' : '') + '"><span class="lpd-when"><b>' + esc(fmt(at(n))) + '</b><small>'
            + esc(label || (n < 0 ? Math.abs(n) + ' days before' : S.unit + ' ' + n)) + '</small></span><span class="lpd-what"><b>' + esc(t) + '</b><small>' + esc(sub) + '</small>'
            + (wx ? '<em>' + esc(wx) + '</em>' : '') + '</span></li>').join('');
        const rows = [...list.children];
        if (still()) { rows.forEach((r) => r.classList.add('is-in')); list.style.setProperty('--fill', '100%'); return; }
        rows.forEach((r, i) => timers.push(setTimeout(() => { r.classList.add('is-in'); list.style.setProperty('--fill', Math.round((i + 1) / rows.length * 100) + '%'); }, 120 + i * 140)));
    };
    box.querySelectorAll('[data-crop]').forEach((b) => b.addEventListener('click', () => {
        crop = b.dataset.crop;
        box.querySelectorAll('[data-crop]').forEach((x) => x.setAttribute('aria-checked', String(x === b)));
        draw();
    }));
    $('lpdDate').addEventListener('change', draw);
    // It plays the first time it comes into view.
    const io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) { io.disconnect(); draw(); } }, { rootMargin: '0px 0px -25% 0px' });
    io.observe(box);
})();
</script>
@endpush
