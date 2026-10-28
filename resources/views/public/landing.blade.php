@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

@php
    /* The ads landing page (2026-09-29). Every word comes from
       App\Support\LandingPage: defaults in code, edits from the mother app
       (AniSystem > Landing page). The ad's tracking tags ride along to the
       signup, so a campaign can be measured to the account it made.
       The argument: precision agriculture; a bigger harvest despite the
       weather and the costs; every tool the farm needs, in one app.
       Since 2026-10-28 (the owner: "based on all these new changes, new
       features ... make this landing page for ads better, persuasive"): the
       problems as three cards, the losses as one compact list, the real
       Facebook post (why advice from strangers fails), the newest tools with
       their real screens, and every tool in folds that open onto how it
       helps. The four feature pillars are gone: the page was 22,000 px on a
       phone, and they said again what the cards around them say. */
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
    $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $arrow = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $mark = fn (string $t) => \App\Support\LandingPage::marked($t);
    // Every tool, by the step of the season it serves: How It Works' own
    // list, with the icons the tools wear in the app (2026-10-13).
    $hwSteps = \App\Support\HowItWorks::stages();
    $hwTools = collect($hwSteps)->flatMap(fn ($st) => $st['items'])->keyBy('key');
    // The plan every account has (the first in config/tiers): its tools are the free ones.
    $freeTier = array_key_first(array_filter(config('tiers'), fn ($t, $k) => $k !== 'admin', ARRAY_FILTER_USE_BOTH));
    $isFree = fn (array $t) => ($t['plan']['tier'] ?? null) === $freeTier;
@endphp

@section('title', $lp['meta']['title'])
@section('meta_description', strip_tags($lp['meta']['description'] !== '' ? $lp['meta']['description'] : $lp['hero']['sub']))
{{-- No top bar here: every way out of the offer is a signup lost. --}}
@section('noHeader', '1')
{{-- Nor the footer's band of site links (2026-10-28): the legal links stay. --}}
@section('slimFooter', '1')

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
    .lp-demo .fx-h em, .lp-demo .fx-h strong, .lp-more-h em { font-style: normal; color: #4a7c2a; }
    /* What the day counts and the gold dots mean, under the season's name. */
    .lpd-key { flex-basis: 100%; display: flex; flex-wrap: wrap; gap: .2rem .9rem; margin-top: .15rem; font-size: .74rem; color: #cfe0bd; }
    .lpd-key span { display: inline-flex; align-items: center; gap: .35rem; }
    .lpd-key i { flex: none; width: .6rem; height: .6rem; border-radius: 999px; background: #f5c518; border: 2px solid #c79e00; }
    /* The way on after the sample: under the list on a phone, where the
       reader has just finished it; beside it, in the picker, on a desk. */
    .lpd-end { display: none; padding: 0 1rem 1.1rem; }
    @media (max-width: 899.98px) {
        .lpd-ask .lpd-go { display: none; }
        .lpd-end { display: block; }
        /* The three crops side by side, so the season shows right under them. */
        .lpd-ask { padding: 1rem; }
        .lpd-crops { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lpd-crops button { flex-direction: column; justify-content: center; gap: .1rem; min-height: 4.4rem; padding: .55rem .35rem; text-align: center; font-size: .92rem; }
        .lpd-crops button .lpd-emo { font-size: 1.35rem; line-height: 1.1; }
        .lpd-crops button small { margin-left: 0; font-size: .68rem; line-height: 1.2; }
        .lpd-q + .lpd-crops + .lpd-q { margin-top: .9rem; }
    }
    /* New this season, on a dark band: four real screens. */
    .lp-space { position: relative; isolation: isolate; overflow: hidden; color: #e8efe1; background: radial-gradient(60rem 30rem at 80% 0%, #2c4f17 0%, transparent 60%), #0d1609; }
    .lp-space::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgb(255 255 255 / .1) 1px, transparent 1.5px); background-size: 22px 22px; opacity: .6; }
    .lp-space-kick { display: inline-block; padding: .3rem .8rem; border-radius: 999px; font-size: .76rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #1f1500; background: #f5c518; }
    .lp-space-h { margin: .8rem auto 0; max-width: 46rem; font-family: var(--font-heading); font-weight: 800; color: #fff; font-size: clamp(1.8rem, 4vw, 2.8rem); line-height: 1.15; }
    .lp-space-h em, .lp-space-h strong { font-style: normal; color: #f5c518; }
    .lp-space-p { margin: .9rem auto 0; max-width: 42rem; color: #b9caa8; line-height: 1.65; }
    .lp-space-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14.5rem, 1fr)); }
    @media (min-width: 900px) { .lp-space-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .lp-space-plan { display: inline-flex; align-items: center; gap: .3rem; margin-top: .55rem; padding: .2rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 900;
        color: #d9f99d; background: rgb(132 204 22 / .14); border: 1px solid rgb(163 230 53 / .35); }
    .lp-space-plan.is-paid { color: #fde68a; background: rgb(245 197 24 / .1); border-color: rgb(245 197 24 / .4); }
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
    /* A phone: each tool as a row, its real screen beside its words, so the
       four read in about one screen instead of two and a half. */
    @media (max-width: 559.98px) {
        .lp-space-grid { gap: .8rem; }
        .lp-space-card { display: grid; grid-template-columns: 40% minmax(0, 1fr); }
        .lp-space-shot { height: auto; min-height: 12.5rem; }
        .lp-space-shot img { width: 84%; top: .9rem; border-radius: .8rem .8rem 0 0; }
        .lp-space-tx { align-self: center; padding: .95rem 1rem 1rem .95rem; }
        .lp-space-tx h3 { font-size: 1rem; line-height: 1.25; }
        .lp-space-tx p { font-size: .84rem; line-height: 1.5; }
    }
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
    .lp-logo img { display: block; width: 200px; max-width: none; height: auto; aspect-ratio: 1041 / 160; object-fit: contain; }
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
    /* On the dark photo a half clear marker turned muddy: the marked words
       are gold instead, as on the dark New this season band. */
    .lp-closer h2 em { background: none; padding: 0; color: #fadd6d; }
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
        .lp-copy.is-right .lp-hello .as-say { margin-left: auto; flex-direction: row-reverse; }
        .lp-copy.is-right .lp-hello .as-bubble { border-radius: 1.1rem 1.1rem .3rem 1.1rem; }
    }
    .lp-note span { display: inline-flex; align-items: center; gap: .35rem; }
    .lp-note svg { width: 1rem; height: 1rem; color: #4a7c2a; }
    /* The hero's phone, and what floats beside it. */
    .lp-stage { position: relative; display: flex; justify-content: center; padding: 1.5rem 0 2.5rem; }
    .lp-stage .ph-frame { width: min(320px, 76vw); }
    .lp-float { position: absolute; z-index: 3; display: flex; align-items: center; gap: .55rem; padding: .6rem .8rem;
        border-radius: 1rem; background: #fff; box-shadow: 0 16px 36px -18px rgb(20 33 12 / .5), 0 0 0 1px #e7eedf;
        font-size: .8rem; font-weight: 800; color: #14210c; white-space: nowrap; animation: lpFloat 6s ease-in-out infinite; }
    .lp-float small { display: block; font-size: .74rem; font-weight: 700; color: #5f6e52; }
    .lp-float .dot { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1rem; }
    .lp-float.f1 { top: 18%; left: max(0px, calc(50% - 260px)); }
    .lp-float.f2 { bottom: 16%; right: max(0px, calc(50% - 270px)); animation-delay: -3s; }
    @keyframes lpFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @media (max-width: 480px) { .lp-float.f1 { left: 0; top: 10%; } .lp-float.f2 { right: 0; bottom: 10%; } }
    /* The problems (2026-10-28): three cards, each its photo with the kicker and the headline on it, what goes wrong
       under it, then how anee.io helps. Three across on a desk, one under another on a phone. White, so the hero's arc
       runs straight into it. */
    .lp-problems { background: #fff; }
    .lp-problems-h { font-family: var(--font-heading); font-weight: 800; color: #14210c; letter-spacing: -.02em; line-height: 1.15;
        font-size: clamp(1.9rem, 4.4vw, 3rem); text-wrap: balance; }
    .lp-problems .fx-kicker + .lp-problems-h { margin-top: .5rem; }
    .lp-problems-sub { margin-top: .85rem; color: #3f4a37; font-size: clamp(1rem, 1.6vw, 1.15rem); line-height: 1.65; }
    .lp-probs { display: grid; gap: 1.1rem; }
    @media (min-width: 960px) { .lp-probs { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.25rem; } }
    .lp-prob { min-width: 0; display: flex; flex-direction: column; overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid #ece8e1;
        box-shadow: 0 26px 50px -38px rgb(20 33 12 / .6); }
    .lp-prob-top { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #1f2a17; }
    .lp-prob-top img { display: block; width: 100%; height: 100%; object-fit: cover; transition: transform .8s cubic-bezier(.22,1,.36,1); }
    .lp-prob:hover .lp-prob-top img { transform: scale(1.04); }
    .lp-prob-top::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgb(10 14 7 / 0) 28%, rgb(10 14 7 / .85) 100%); }
    .lp-prob-cap { position: absolute; left: 0; right: 0; bottom: 0; z-index: 1; padding: 1rem 1.1rem 1.05rem; color: #fff; }
    .lp-prob-k { display: inline-block; padding: .22rem .6rem; border-radius: 999px; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase;
        color: #fff; background: rgb(185 28 28 / .92); }
    .lp-prob-cap h3 { margin-top: .5rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; line-height: 1.25; text-wrap: balance; text-shadow: 0 2px 14px rgb(0 0 0 / .55); }
    .lp-prob-body { flex: 1; display: flex; flex-direction: column; gap: .9rem; padding: 1rem 1.05rem 1.1rem; }
    .lp-prob-body > p { color: #4b5563; line-height: 1.6; font-size: .94rem; }
    /* What goes wrong, as plain lines with their red marks: the weather's card stays near its neighbours' height. */
    .lp-prob .lp-pain { gap: .55rem; }
    .lp-prob .lp-pain li { padding: 0; gap: .6rem; background: none; border: 0; font-size: .9rem; color: #5b2a20; }
    .lp-prob .lp-pain li b { width: 1.35rem; height: 1.35rem; font-size: .72rem; margin-top: .1rem; }
    .lp-prob .lp-fixes { margin-top: auto; }
    @media (prefers-reduced-motion: reduce) { .lp-prob-top img { transition: none; } }
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
    /* What guessing costs: a dark band of "up to" figures that count up once seen, one compact row each
       (2026-10-28): the photo, the figure, what it takes and from a hectare, a red bar filling to it. */
    .lp-losses { position: relative; isolation: isolate; overflow: hidden; color: #fff;
        background: radial-gradient(900px 420px at 90% 0%, rgb(180 35 24 / .28), transparent 60%), linear-gradient(180deg, #16210f, #0f170a); }
    .lp-losses-sub { margin-top: .9rem; font-size: clamp(1rem, 1.6vw, 1.15rem); line-height: 1.6; color: rgb(255 255 255 / .8); }
    .lp-lgrid { display: grid; gap: .65rem; }
    @media (min-width: 768px) { .lp-lgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; } }
    .lp-lrow { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr); gap: .85rem; align-items: center; padding: .65rem .8rem .7rem .65rem; border-radius: 1.15rem;
        background: rgb(255 255 255 / .06); border: 1px solid rgb(255 255 255 / .1); }
    .lp-lrow-img { width: 4.6rem; height: 4.6rem; border-radius: .85rem; object-fit: cover; }
    .lp-lrow-b { min-width: 0; display: grid; grid-template-columns: auto minmax(0, 1fr); column-gap: .75rem; align-items: center; }
    .lp-lrow-n { grid-row: span 2; font-family: var(--font-heading); font-weight: 800; font-size: 2.05rem; line-height: .95; color: #fca5a5; text-align: center; }
    .lp-lrow-n small { display: block; margin-bottom: .2rem; font-family: var(--font-sans, inherit); font-size: .56rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: rgb(255 255 255 / .6); }
    .lp-lrow-n i { font-style: normal; font-size: .95rem; }
    .lp-lrow-t { font-weight: 800; color: #fff; font-size: .93rem; line-height: 1.3; }
    .lp-lrow-peso { margin-top: .15rem; font-size: .78rem; font-weight: 700; color: #fcd34d; }
    .lp-lrow-p { display: none; grid-column: 1 / -1; margin-top: .45rem; font-size: .82rem; color: rgb(255 255 255 / .62); line-height: 1.5; }
    @media (min-width: 768px) { .lp-lrow-p { display: block; } }
    .lp-lrow-bar { grid-column: 1 / -1; display: block; margin-top: .55rem; height: 4px; border-radius: 999px; background: rgb(255 255 255 / .1); overflow: hidden; }
    .lp-lrow-bar b { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #f87171, #dc2626); transition: width 1.1s cubic-bezier(.22,1,.36,1); }
    .lp-lrow.is-lit .lp-lrow-bar b { width: var(--loss); }
    .lp-loss-note { margin-top: 1.1rem; font-size: .78rem; color: rgb(255 255 255 / .55); text-align: center; line-height: 1.55; }
    @media (prefers-reduced-motion: reduce) { .lp-lrow-bar b { transition: none; } }
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
    /* A phone: the icon beside the title, not on a line of its own. */
    @media (max-width: 639.98px) {
        .lp-right { display: grid; grid-template-columns: 2.6rem minmax(0, 1fr); column-gap: .85rem; align-items: center; padding: 1.15rem 1.1rem 1.15rem; }
        .lp-right .e { width: 2.6rem; height: 2.6rem; border-radius: .85rem; font-size: 1.3rem; }
        .lp-right h3 { margin-top: 0; }
        .lp-right p { grid-column: 1 / -1; margin-top: .6rem; }
    }
    /* Three steps */
    .lp-steps { display: grid; gap: 1rem; counter-reset: s; }
    @media (min-width: 820px) { .lp-steps { grid-template-columns: repeat(3, 1fr); gap: 1.25rem; } }
    .lp-step { position: relative; padding: 1.4rem 1.25rem 1.3rem; border-radius: 1.25rem; background: #fff;
        border: 1px solid #e3ecd9; box-shadow: 0 12px 30px -24px rgb(20 33 12 / .5); }
    .lp-step b.n { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px;
        background: linear-gradient(135deg, #6b9f3d, #3d6823); color: #fff; font-weight: 900; font-size: 1.05rem; }
    .lp-step h3 { margin-top: .85rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
    .lp-step p { margin-top: .35rem; color: #4b5563; line-height: 1.6; font-size: .95rem; }
    @media (max-width: 639.98px) {
        .lp-step { display: grid; grid-template-columns: 2.4rem minmax(0, 1fr); column-gap: .85rem; align-items: center; padding: 1.15rem 1.1rem 1.15rem; }
        .lp-step h3 { margin-top: 0; }
        .lp-step p { grid-column: 1 / -1; margin-top: .55rem; }
    }
    @media (min-width: 820px) {
        .lp-step:not(:last-child)::after { content: ''; position: absolute; top: 2.6rem; right: -1.05rem; width: .9rem; height: 2px;
            background: repeating-linear-gradient(90deg, #6b9f3d 0 4px, transparent 4px 7px); }
    }
    /* Everything in one app (2026-10-28): the season's modules beside the tools, in one fold per step of the season,
       each fold's head showing its count, how many are free and its first icons; a tool opens a sheet with what it
       does and how it helps. The first fold starts open. */
    .lp-all { display: grid; gap: 2.5rem; align-items: start; }
    @media (min-width: 1024px) { .lp-all { grid-template-columns: 20rem 1fr; gap: 3.5rem; } .lp-all-phone { position: sticky; top: 2rem; } }
    .lp-all-phone { display: flex; justify-content: center; }
    /* On a phone the tools speak for themselves: the screenshot would only make the page longer. */
    @media (max-width: 1023.98px) { .lp-all-phone { display: none; } }
    .lp-all-phone .ph-frame { width: min(280px, 72vw); }
    .lp-folds { display: grid; gap: .6rem; }
    .lp-fold { border-radius: 1.2rem; background: #fff; border: 1px solid #e3ecd9; overflow: hidden;
        transition: box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-fold.is-open { border-color: #c9e0ad; box-shadow: 0 18px 36px -28px rgb(20 33 12 / .55); }
    .lp-fold-h { width: 100%; display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; text-align: left; cursor: pointer; }
    .lp-fold-h .n { flex: none; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; font-size: .85rem; font-weight: 900; color: #fff;
        background: linear-gradient(135deg, #6b9f3d, #3d6823); }
    .lp-fold-h .t { flex: 1; min-width: 0; font-family: var(--font-heading); font-weight: 800; color: #14210c; font-size: 1rem; line-height: 1.25; }
    .lp-fold-h .t small { display: block; margin-top: .1rem; font-family: var(--font-sans, inherit); font-size: .74rem; font-weight: 700; color: #6b7a5e; }
    .lp-fold-h .t small b { color: #3d6823; }
    .lp-fold-ics { flex: none; display: flex; }
    .lp-fold-ics img { width: 1.85rem; height: 1.85rem; padding: .25rem; border-radius: 999px; object-fit: contain; background: #f3f8ec; box-shadow: 0 0 0 2px #fff; }
    .lp-fold-ics img + img { margin-left: -.5rem; }
    .lp-fold-ics img.is-face { padding: 0; object-fit: cover; }
    @media (max-width: 420px) { .lp-fold-ics img:nth-child(n+4) { display: none; } }
    .lp-fold-h > svg { flex: none; width: 1.2rem; height: 1.2rem; color: #4a7c2a; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lp-fold.is-open .lp-fold-h > svg { transform: rotate(180deg); }
    .lp-fold-b { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .32s cubic-bezier(.22,1,.36,1); }
    .lp-fold.is-open .lp-fold-b { grid-template-rows: 1fr; }
    .lp-fold-b > div { min-height: 0; overflow: hidden; }
    .lp-fold-b .lp-tiles { padding: .45rem .85rem .95rem; }
    /* Two to a row everywhere, so a step of four is a square, never three and an orphan; a step's odd last tile takes the whole row. */
    .lp-tiles { display: grid; gap: .6rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lp-tile:last-child:nth-child(odd) { grid-column: 1 / -1; }
    .lp-tile { position: relative; display: flex; gap: .7rem; align-items: flex-start; width: 100%; padding: .8rem .85rem; border-radius: 1rem; text-align: left; cursor: pointer;
        background: #fafcf7; border: 1px solid #e7eee0; transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-tile:hover, .lp-tile:focus-visible { transform: translateY(-2px); border-color: #c9e0ad; box-shadow: 0 16px 30px -22px rgb(20 33 12 / .45); }
    .lp-tile .e { flex: none; display: grid; place-items: center; width: 2.3rem; height: 2.3rem; border-radius: .8rem; background: #f0f7e6; overflow: hidden; }
    .lp-tile .e img { width: 1.5rem; height: 1.5rem; object-fit: contain; }
    .lp-tile .e.is-face img { width: 100%; height: 100%; object-fit: cover; }
    .lp-tile-tag { display: inline-block; margin-left: .3rem; padding: .05rem .4rem; border-radius: 999px; vertical-align: .1em; font-style: normal; font-size: .6rem; font-weight: 900;
        letter-spacing: .04em; color: #5b4300; background: #fde68a; }
    .lp-tile-free { position: absolute; top: .45rem; right: .5rem; padding: .05rem .4rem; border-radius: 999px; font-style: normal; font-size: .58rem; font-weight: 900; letter-spacing: .04em;
        text-transform: uppercase; color: #2d5016; background: #dcfce7; }
    .lp-tile-h { display: block; padding-right: 2.2rem; font-weight: 800; color: #14210c; font-size: .9rem; line-height: 1.3; }
    .lp-tile small { display: block; margin-top: .15rem; font-size: .8rem; color: #5b6651; line-height: 1.45; }
    /* On a phone a tile is its icon and its name, read at a glance; a tap says the rest. */
    @media (max-width: 559px) {
        .lp-tile { align-items: center; gap: .55rem; padding: .62rem .6rem; }
        .lp-tile .e { width: 2rem; height: 2rem; }
        .lp-tile-h { font-size: .8rem; padding-right: 0; }
        .lp-tile small { display: none; }
        .lp-tile-free { top: -.35rem; right: .35rem; }
    }

    /* A tool's sheet: up from the bottom on a phone, a card in the middle on a desk. */
    .lp-sheet { position: fixed; inset: 0; z-index: 90; display: flex; align-items: flex-end; justify-content: center; }
    .lp-sheet[hidden] { display: none; }
    .lp-sheet-bg { position: absolute; inset: 0; background: rgb(12 18 8 / .55); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .lp-sheet-card { position: relative; width: 100%; max-width: 34rem; max-height: 88vh; overflow-y: auto; overscroll-behavior: contain; border-radius: 1.5rem 1.5rem 0 0; background: #fff;
        box-shadow: 0 -20px 50px -20px rgb(0 0 0 / .5); transform: translateY(100%); transition: transform .32s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 640px) {
        .lp-sheet { align-items: center; padding: 1.5rem; }
        .lp-sheet-card { border-radius: 1.5rem; transform: translateY(24px) scale(.97); opacity: 0; transition: transform .32s cubic-bezier(.22,1,.36,1), opacity .32s cubic-bezier(.22,1,.36,1); }
    }
    .lp-sheet.is-on .lp-sheet-bg { opacity: 1; }
    .lp-sheet.is-on .lp-sheet-card { transform: none; opacity: 1; }
    .lp-sheet-x { position: absolute; top: .7rem; right: .7rem; z-index: 2; display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: 999px; cursor: pointer;
        color: #fff; background: rgb(20 33 12 / .55); }
    .lp-sheet-x svg { width: 1.1rem; height: 1.1rem; }
    .lp-sheet-shot { position: relative; height: 11rem; overflow: hidden; background: linear-gradient(160deg, #2c4f17, #0d1609); }
    .lp-sheet-shot img { position: absolute; left: 50%; top: 1rem; width: 62%; max-width: 15rem; transform: translateX(-50%); border-radius: 1rem 1rem 0 0; box-shadow: 0 18px 34px -16px rgb(0 0 0 / .9); }
    .lp-sheet-in { padding: 1.1rem 1.2rem 1.3rem; }
    .lp-sheet-h { display: flex; align-items: center; gap: .7rem; }
    .lp-sheet-h img { flex: none; width: 2.6rem; height: 2.6rem; padding: .35rem; border-radius: .9rem; object-fit: contain; background: #f0f7e6; }
    .lp-sheet-h img.is-face { padding: 0; object-fit: cover; }
    .lp-sheet-h b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #14210c; line-height: 1.2; }
    .lp-sheet-h small { display: block; margin-top: .1rem; font-size: .8rem; font-weight: 700; color: #4a7c2a; }
    .lp-sheet-p { margin-top: .85rem; color: #374151; line-height: 1.6; font-size: .94rem; }
    .lp-sheet-help { margin-top: 1rem; padding: .9rem 1rem; border-radius: 1rem; background: #f3f8ec; border: 1px solid #dcead0; }
    .lp-sheet-help > b { display: block; font-size: .72rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .lp-sheet-help div { position: relative; margin-top: .55rem; padding-left: 1.4rem; font-size: .88rem; line-height: 1.5; color: #374151; }
    .lp-sheet-help div::before { content: ''; position: absolute; left: .1rem; top: .35rem; width: .8rem; height: .45rem; border-left: 2.5px solid #4a7c2a; border-bottom: 2.5px solid #4a7c2a; transform: rotate(-45deg); }
    .lp-sheet-help div b { color: #14210c; }
    .lp-sheet-plan { display: inline-flex; margin-top: 1rem; padding: .3rem .75rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #2d5016; background: #ecfccb; }
    .lp-sheet-plan.is-paid { color: #7a4b00; background: #fff7df; }
    .lp-sheet-go { display: flex; width: 100%; justify-content: center; margin-top: 1.1rem; }
    @media (prefers-reduced-motion: reduce) {
        .lp-fold, .lp-fold-b, .lp-fold-h > svg, .lp-tile, .lp-sheet-bg, .lp-sheet-card { transition: none !important; }
    }

    /* The button that stays at hand on a phone: in once the hero's own
       button is gone, out again over the closer's. */
    .lp-dock { position: fixed; left: 0; right: 0; bottom: 0; z-index: 60; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom));
        background: rgb(255 255 255 / .94); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); box-shadow: 0 -12px 30px -18px rgb(20 33 12 / .45);
        transform: translateY(110%); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lp-dock.is-on { transform: none; }
    .lp-dock a { display: flex; width: 100%; justify-content: center; }
    .lp-dock small { display: block; margin-top: .35rem; text-align: center; font-size: .72rem; font-weight: 700; color: #5b6651; }
    @media (min-width: 1024px) { .lp-dock { display: none; } }
    @media (prefers-reduced-motion: reduce) { .lp-dock { transition: none; } }
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
    @media (prefers-reduced-motion: reduce) {
        .lp-float, .lp-kicker i { animation: none; }
        .lp-qa .a, .lp-qa button svg, .lp-tile, .lp-right { transition: none; }
    }
</style>
@endpush

@section('content')

    {{-- ================= 1. HERO ================= --}}
    <section class="lp-hero">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6 pb-16 sm:pt-8 sm:pb-24">
            {{-- The brand, and nothing to click away to. --}}
            <span class="lp-logo"><img src="{{ asset('images/logo.png') }}?v=anee2" alt="anee.io" width="1041" height="160"></span>
            <div class="grid lg:grid-cols-[1.1fr_1fr] gap-8 lg:gap-12 items-center mt-8 sm:mt-10">
                <div class="lp-copy animate-fade-up {{ ($lp['hero']['align'] ?? 'left') === 'right' ? 'is-right' : '' }}">
                    <span class="lp-kicker"><i aria-hidden="true"></i>{{ $lp['hero']['kicker'] }}</span>
                    <h1 class="lp-h1 mt-5 {{ mb_strlen($lp['hero']['headline']) > 70 ? 'is-long' : '' }}">{!! $mark($lp['hero']['headline']) !!}</h1>
                    <p class="lp-sub mt-5 max-w-xl">{{ $lp['hero']['sub'] }}</p>
                    <div class="lp-hello mt-6">@include('public.partials.anee-says', ['say' => $lp['says']['hero'] ?? '', 'tone' => 'light', 'align' => 'left'])</div>
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
    @php
        // The three problems as one list: the weather (with what goes wrong), then fuel and fertilizer (with their words).
        $probs = [[
            'src' => $rowPhoto($lp['problem']['image'], 'storm'), 'alt' => $photoAlts['storm'],
            'kicker' => $lp['problem']['kicker'], 'headline' => $lp['problem']['headline'],
            'pains' => $lp['problem']['bullets'], 'text' => '', 'fixes' => $lp['problem']['fixes'],
        ]];
        foreach ($lp['costs']['items'] as $c) {
            $key = $c['image'] ?: 'tractor';
            $probs[] = ['src' => $rowPhoto($c['upload'] ?? '', $key), 'alt' => $photoAlts[$key] ?? $c['headline'],
                'kicker' => $c['kicker'], 'headline' => $c['headline'], 'pains' => [], 'text' => $c['text'], 'fixes' => $c['fixes']];
        }
    @endphp
    <section class="lp-problems">
        @if (trim($lp['problem']['sectionTitle'] ?? '') !== '')
            <div class="max-w-3xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14 text-center reveal">
                <div class="mb-6">@include('public.partials.anee-says', ['say' => $lp['says']['problem'] ?? '', 'tone' => 'light', 'align' => 'center'])</div>
                @if (trim($lp['problem']['sectionKicker'] ?? '') !== '')<span class="fx-kicker">{{ $lp['problem']['sectionKicker'] }}</span>@endif
                <h2 class="lp-problems-h">{{ $lp['problem']['sectionTitle'] }}</h2>
                @if (trim($lp['problem']['sectionSub'] ?? '') !== '')<p class="lp-problems-sub">{{ $lp['problem']['sectionSub'] }}</p>@endif
            </div>
        @endif
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-9 pb-16 sm:pt-12 sm:pb-20">
            <div class="lp-probs">
                @foreach ($probs as $i => $pr)
                    <article class="lp-prob reveal" style="--reveal-delay: {{ $i * 0.08 }}s">
                        <div class="lp-prob-top">
                            <img src="{{ $pr['src'] }}" alt="{{ $pr['alt'] }}" width="1400" height="1050" loading="lazy">
                            <div class="lp-prob-cap"><span class="lp-prob-k">{{ $pr['kicker'] }}</span><h3>{{ $pr['headline'] }}</h3></div>
                        </div>
                        <div class="lp-prob-body">
                            @if (! empty($pr['pains']))
                                <ul class="lp-pain">
                                    @foreach ($pr['pains'] as $b)
                                        <li><b aria-hidden="true">✕</b><span>{{ $b }}</span></li>
                                    @endforeach
                                </ul>
                            @elseif (trim($pr['text']) !== '')
                                <p>{{ $pr['text'] }}</p>
                            @endif
                            @if (! empty($pr['fixes']))
                                <div class="lp-fixes">
                                    <p class="lp-fixes-h">{{ $lp['costs']['helpsLabel'] }}</p>
                                    <ul class="fx-list">
                                        @foreach ($pr['fixes'] as $f)
                                            <li>{!! $check !!}<span>{{ $f }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
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
                    <div class="mb-6">@include('public.partials.anee-says', ['say' => $lp['says']['losses'] ?? '', 'tone' => 'dark', 'align' => 'center'])</div>
                    <h2 class="font-heading font-extrabold text-2xl sm:text-4xl text-balance">{{ $lp['losses']['headline'] }}</h2>
                    @if (trim($lp['losses']['sub'] ?? '') !== '')<p class="lp-losses-sub">{{ $lp['losses']['sub'] }}</p>@endif
                </div>
                <div class="lp-lgrid mt-9">
                    @foreach ($lp['losses']['items'] as $i => $l)
                        @php
                            $n = max(0, min(100, (int) ($l['n'] ?? 0)));
                            $pic = in_array($l['image'] ?? '', \App\Support\LandingPage::LOSS_PHOTOS, true) ? $l['image'] : 'palay-heads';
                            $peso = trim($l['peso'] ?? '');
                        @endphp
                        <div class="lp-lrow reveal" style="--loss: {{ $n }}%; --reveal-delay: {{ ($i % 2) * 0.06 }}s">
                            <img class="lp-lrow-img" src="{{ \App\Support\LandingPage::imageUrl($l['upload'] ?? '', 'lp/loss/' . $pic . '.webp') }}" alt="" width="400" height="500" loading="lazy">
                            <div class="lp-lrow-b">
                                <p class="lp-lrow-n"><small>Up to</small><span data-n="{{ $n }}">{{ $n }}</span><i>%</i></p>
                                <p class="lp-lrow-t">{{ $l['title'] ?? '' }}</p>
                                @if (\App\Support\Region::ph() && $peso !== '')
                                    <p class="lp-lrow-peso">{{ $peso }} lost per hectare</p>
                                @else
                                    <p class="lp-lrow-peso">Up to {{ $n }}% of what a hectare earns</p>
                                @endif
                                @if (trim($l['text'] ?? '') !== '')<p class="lp-lrow-p">{{ $l['text'] }}</p>@endif
                                <span class="lp-lrow-bar" aria-hidden="true"><b></b></span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if (trim($lp['losses']['note'] ?? '') !== '')<p class="lp-loss-note reveal">{{ $lp['losses']['note'] }}</p>@endif
            </div>
        </section>
    @endif

    {{-- ================= Where most farmers get answers today (2026-10-28) =================
         The real Facebook post, its twenty comments and seven causes: why advice from strangers fails, then one line on
         what Anee does instead. Its button signs up with the ad's tags; nothing on it leads off the page. --}}
    @include('public.partials.social-guess', ['sgSignup' => $signup, 'sgAsk' => false, 'sgLine' => $lp['says']['social'] ?? ''])

    {{-- ================= The answer: precision agriculture, then how it works ================= --}}
    <section class="py-16 sm:py-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <div class="mb-6">@include('public.partials.anee-says', ['say' => $lp['says']['precision'] ?? '', 'tone' => 'light', 'align' => 'center'])</div>
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

    {{-- ================= Anee Auto: the easiest way (2026-10-13) =================
         The homepage's own section, in the site's dress, with the words the
         mother app keeps for it. --}}
    @if (! empty($lp['auto']['headline']))
        @include('public.partials.anee-auto-section', ['signup' => $signup, 'aaLook' => 'site', 'aaText' => $lp['auto'], 'aaLine' => $lp['says']['auto'] ?? ''])
    @endif

    {{-- ================= Try it: a season in ten seconds (2026-10-07) =================
         The visitor picks a crop and a planting day and watches a sample
         season land on its days, counted the way anee.io counts them.
         The tasks are common practice, said as a sample under the list. --}}
    @if (! empty($lp['demo']['headline']))
    <section class="lp-demo" id="lpDemo">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
            <div class="text-center reveal">
                <div class="mb-6">@include('public.partials.anee-says', ['say' => $lp['says']['demo'] ?? '', 'tone' => 'light', 'align' => 'center'])</div>
                <span class="fx-kicker">{{ $lp['demo']['kicker'] }}</span>
                <h2 class="fx-h mx-auto max-w-3xl text-balance">{!! $mark($lp['demo']['headline']) !!}</h2>
                @if (trim($lp['demo']['sub']) !== '')<p class="fx-p mx-auto max-w-2xl">{{ $lp['demo']['sub'] }}</p>@endif
            </div>
            <div class="lpd mt-9 reveal">
                <div class="lpd-ask">
                    <p class="lpd-q"><i>1</i>Your crop</p>
                    <div class="lpd-crops" role="radiogroup" aria-label="Your crop">
                        <button type="button" role="radio" aria-checked="true" data-crop="rice"><span class="lpd-emo" aria-hidden="true">🌾</span> {{ \App\Support\Region::ph() ? 'Palay' : 'Rice' }}<small>transplanted</small></button>
                        <button type="button" role="radio" aria-checked="false" data-crop="corn"><span class="lpd-emo" aria-hidden="true">🌽</span> {{ \App\Support\Region::ph() ? 'Mais' : 'Corn' }}<small>yellow corn</small></button>
                        <button type="button" role="radio" aria-checked="false" data-crop="veg"><span class="lpd-emo" aria-hidden="true">🍆</span> {{ \App\Support\Region::ph() ? 'Talong' : 'Eggplant' }}<small>transplanted</small></button>
                    </div>
                    <p class="lpd-q"><i>2</i><label for="lpdDate">The day you plant</label></p>
                    <input type="date" id="lpdDate" class="form-input w-full">
                    <a href="{{ $signup }}" class="btn btn-primary btn-lg w-full mt-5 lpd-go">{{ $lp['demo']['cta'] }} {!! $arrow !!}</a>
                </div>
                <div class="lpd-out" aria-live="polite">
                    <div class="lpd-head"><b id="lpdTitle">Your season</b><span id="lpdSpan"></span><p class="lpd-key" id="lpdKey"></p></div>
                    <ol class="lpd-list" id="lpdList"></ol>
                    <div class="lpd-end"><a href="{{ $signup }}" class="btn btn-primary btn-lg w-full lpd-go">{{ $lp['demo']['cta'] }} {!! $arrow !!}</a></div>
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
                <div class="mb-6">@include('public.partials.anee-says', ['say' => $lp['says']['space'] ?? '', 'tone' => 'dark', 'align' => 'center'])</div>
                <span class="lp-space-kick">{{ $lp['space']['kicker'] }}</span>
                <h2 class="lp-space-h text-balance">{!! $mark($lp['space']['headline']) !!}</h2>
                @if (trim($lp['space']['sub']) !== '')<p class="lp-space-p">{{ $lp['space']['sub'] }}</p>@endif
            </div>
            <div class="lp-space-grid mt-10">
                @foreach ($lp['space']['items'] as $i => $t)
                    @php
                        // An item's image is a tool's key (How It Works): its screen of the app as it is now, and its plan.
                        // The first four items' old picture names still find their tools.
                        $img = (string) ($t['image'] ?? '');
                        $toolKey = ['sky' => 'skyWeather', 'finder' => 'pestFinder'][$img] ?? $img;
                        $tool = $hwTools[$toolKey] ?? null;
                        $shot = $tool ? \App\Support\HowItWorks::shot($toolKey) : null;
                        $src = $shot[0] ?? asset('images/site/app/' . (in_array($img, ['satellite', 'sky', 'npk', 'finder', 'stash'], true) ? $img : 'sky') . '.webp');
                    @endphp
                    <div class="lp-space-card reveal" style="--reveal-delay: {{ ($i % 3) * 0.07 }}s">
                        <span class="lp-space-shot"><img src="{{ $src }}" @if (! empty($shot[1])) srcset="{{ $shot[1] }}" sizes="(max-width: 559px) 40vw, 260px" @endif alt="{{ $t['title'] }} in anee.io" width="540" height="1170" loading="lazy"></span>
                        <div class="lp-space-tx">
                            <h3>{{ $t['title'] }}</h3>
                            <p>{{ $t['text'] }}</p>
                            @if ($tool)
                                <span class="lp-space-plan {{ $isFree($tool) ? '' : 'is-paid' }}">{{ $isFree($tool) ? 'Free on ' . $tool['plan']['name'] : 'With ' . $tool['plan']['name'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center reveal">
                <a href="{{ $signup }}" class="btn btn-accent btn-lg">{{ $lp['hero']['cta'] }} {!! $arrow !!}</a>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= Everything the farm needs, in one app ================= --}}
    <section class="pt-14 pb-16 sm:pt-20 sm:pb-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <div class="mb-6">@include('public.partials.anee-says', ['say' => $lp['says']['more'] ?? '', 'tone' => 'light', 'align' => 'center'])</div>
                <h2 class="fx-h lp-more-h mx-auto max-w-3xl text-balance">{!! $mark($lp['more']['headline']) !!}</h2>
                @if (trim($lp['more']['sub'] ?? '') !== '')<p class="fx-p mx-auto max-w-2xl">{{ $lp['more']['sub'] }}</p>@endif
            </div>
            <div class="lp-all mt-10">
                <div class="lp-all-phone reveal">
                    <div class="ph-frame ph-tilt-l">
                        <img src="{{ \App\Support\LandingPage::imageUrl($lp['more']['image'] ?? '', $shots['hub'][0]) }}" alt="{{ $shots['hub'][1] }}" width="780" height="{{ $shots['hub'][2] }}" loading="lazy">
                    </div>
                </div>
                <div class="lp-folds" id="lpFolds">
                    @foreach ($hwSteps as $si => $st)
                        @php
                            $nAll = count($st['items']);
                            $nFree = count(array_filter($st['items'], $isFree));
                        @endphp
                        <div class="lp-fold reveal {{ $si === 0 ? 'is-open' : '' }}">
                            <button type="button" class="lp-fold-h" aria-expanded="{{ $si === 0 ? 'true' : 'false' }}" aria-controls="lpFold{{ $si }}">
                                <b class="n">{{ $si + 1 }}</b>
                                <span class="t">{{ $st['title'] }}<small>{{ $nAll }} {{ $nAll === 1 ? 'tool' : 'tools' }}@if ($nFree), <b>{{ $nFree === $nAll ? 'all free' : $nFree . ' free' }}</b>@endif</small></span>
                                <span class="lp-fold-ics" aria-hidden="true">
                                    @foreach (array_slice($st['items'], 0, 4) as $t)
                                        <img src="{{ asset('images/' . $t['icon']) }}" alt="" width="40" height="40" loading="lazy" class="{{ str_starts_with($t['icon'], 'anee/') ? 'is-face' : '' }}">
                                    @endforeach
                                </span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="lp-fold-b" id="lpFold{{ $si }}" role="region"><div>
                                <div class="lp-tiles">
                                    @foreach ($st['items'] as $t)
                                        <button type="button" class="lp-tile" data-tool="{{ $t['key'] }}" @if ($si !== 0) tabindex="-1" @endif>
                                            <span class="e{{ str_starts_with($t['icon'], 'anee/') ? ' is-face' : '' }}" aria-hidden="true"><img src="{{ asset('images/' . $t['icon']) }}" alt="" loading="lazy" width="40" height="40"></span>
                                            <span><span class="lp-tile-h">{{ $t['name'] }}@if ($t['anee'])<i class="lp-tile-tag">Anee</i>@endif</span><small>{{ $t['short'] }}</small></span>
                                            @if ($isFree($t))<i class="lp-tile-free">Free</i>@endif
                                        </button>
                                    @endforeach
                                </div>
                            </div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- A tool's sheet: what it does, how it helps, its plan, and the way in. Filled from lpTools when a tool is tapped. --}}
    <div class="lp-sheet" id="lpSheet" hidden>
        <div class="lp-sheet-bg" data-sheet-close></div>
        <div class="lp-sheet-card" role="dialog" aria-modal="true" aria-labelledby="lpSheetName">
            <button type="button" class="lp-sheet-x" data-sheet-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            <div class="lp-sheet-shot" id="lpSheetShot"></div>
            <div class="lp-sheet-in">
                <div id="lpSheetBody"></div>
                <a href="{{ $signup }}" class="btn btn-accent btn-lg lp-sheet-go">{{ $lp['hero']['cta'] }} {!! $arrow !!}</a>
            </div>
        </div>
    </div>
    @php
        $lpTools = $hwTools->map(fn ($t) => [
            'name' => $t['name'],
            'short' => $t['short'],
            'icon' => asset('images/' . $t['icon']),
            'face' => str_starts_with($t['icon'], 'anee/'),
            'anee' => (bool) $t['anee'],
            'what' => $t['what'],
            'helps' => array_map([\App\Support\HowItWorks::class, 'helpHtml'], $t['helps'] ?? []),
            'plan' => $isFree($t) ? 'Free on ' . $t['plan']['name'] . ', forever' : 'Comes with ' . $t['plan']['name'],
            'free' => $isFree($t),
            'shot' => \App\Support\HowItWorks::shot($t['key'])[0] ?? null,
        ]);
    @endphp
    <script type="application/json" id="lpTools">{!! json_encode($lpTools, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

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
            <div class="mb-6 reveal">@include('public.partials.anee-says', ['say' => $lp['says']['faq'] ?? '', 'tone' => 'light', 'align' => 'center'])</div>
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
            <div class="mb-6 reveal">@include('public.partials.anee-says', ['say' => $lp['says']['closer'] ?? '', 'tone' => 'dark', 'align' => 'center'])</div>
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

    <div class="lp-dock" id="lpDock" aria-hidden="true">
        <a href="{{ $signup }}" class="btn btn-accent btn-lg" tabindex="-1">{{ $lp['hero']['cta'] }} {!! $arrow !!}</a>
        {{-- The hero's ticks, the first two: one line on any phone. --}}
        <small>{{ collect(preg_split('/\s*[.·]\s+|\s*\.\s*$/', trim($lp['hero']['note'])))->map(fn ($b) => rtrim(trim($b), '.'))->filter()->take(2)->implode(' · ') }}</small>
    </div>


@endsection

@push('scripts')
<script>
(() => {
    // The phone's dock: up once the hero's button has scrolled away, down
    // while any sign up form is on screen (the hero's or the closer's).
    const dock = document.getElementById('lpDock');
    if (dock && 'IntersectionObserver' in window) {
        const forms = [...document.querySelectorAll('.lp-form')];
        const seen = new Set();
        let past = false;
        const set = () => {
            const on = past && seen.size === 0;
            dock.classList.toggle('is-on', on);
            dock.setAttribute('aria-hidden', on ? 'false' : 'true');
            dock.querySelector('a').tabIndex = on ? 0 : -1;
        };
        new IntersectionObserver((es) => {
            es.forEach((e) => {
                if (e.isIntersecting) seen.add(e.target); else seen.delete(e.target);
                if (e.target === forms[0] && !e.isIntersecting) past = e.boundingClientRect.top < 0;
            });
            set();
        }).observe(forms[0]);
        forms.slice(1).forEach((f) => new IntersectionObserver((es) => { es.forEach((e) => { if (e.isIntersecting) seen.add(e.target); else seen.delete(e.target); }); set(); }).observe(f));
    }

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
    const losses = document.querySelectorAll('#lpLosses .lp-lrow');
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

    // Every tool: a fold per step (opened and closed on its grid, its tiles reachable by tab only while open), and a
    // tool's sheet with what it does and how it helps your crops and your farm.
    document.getElementById('lpFolds')?.addEventListener('click', (e) => {
        const head = e.target.closest('.lp-fold-h');
        if (head) {
            const fold = head.parentElement;
            const open = !fold.classList.contains('is-open');
            fold.classList.toggle('is-open', open);
            head.setAttribute('aria-expanded', open ? 'true' : 'false');
            fold.querySelectorAll('.lp-tile').forEach((t) => { t.tabIndex = open ? 0 : -1; });
            return;
        }
        const tile = e.target.closest('[data-tool]');
        if (tile) openSheet(tile.dataset.tool, tile);
    });
    let TOOLS = {};
    try { TOOLS = JSON.parse(document.getElementById('lpTools')?.textContent || '{}'); } catch (_) { TOOLS = {}; }
    const sheet = document.getElementById('lpSheet');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let back = null, shutT = 0;
    function openSheet(key, from) {
        const t = TOOLS[key];
        if (!t || !sheet) return;
        back = from || null;
        clearTimeout(shutT);
        document.getElementById('lpSheetShot').innerHTML = t.shot ? '<img src="' + esc(t.shot) + '" alt="' + esc(t.name) + ' in anee.io">' : '';
        document.getElementById('lpSheetShot').hidden = !t.shot;
        document.getElementById('lpSheetBody').innerHTML =
            '<div class="lp-sheet-h"><img src="' + esc(t.icon) + '" alt=""' + (t.face ? ' class="is-face"' : '') + '><div><b id="lpSheetName">' + esc(t.name) + '</b><small>'
            + (t.anee ? 'Anee does the work · ' : '') + esc(t.short) + '</small></div></div>'
            + '<p class="lp-sheet-p">' + esc(t.what) + '</p>'
            + (t.helps && t.helps.length ? '<div class="lp-sheet-help"><b>How it helps your crops and your farm</b>' + t.helps.map((h) => '<div>' + h + '</div>').join('') + '</div>' : '')
            + '<span class="lp-sheet-plan' + (t.free ? '' : ' is-paid') + '">' + esc(t.plan) + '</span>';
        sheet.hidden = false;
        sheet.querySelector('.lp-sheet-card').scrollTop = 0;
        document.documentElement.style.overflow = 'hidden';
        requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('is-on')));
        sheet.querySelector('.lp-sheet-x').focus({ preventScroll: true });
    }
    function shutSheet() {
        if (!sheet || sheet.hidden) return;
        sheet.classList.remove('is-on');
        document.documentElement.style.overflow = '';
        shutT = setTimeout(() => { sheet.hidden = true; }, calm ? 0 : 340);
        if (back) back.focus({ preventScroll: true });
    }
    sheet?.addEventListener('click', (e) => { if (e.target.closest('[data-sheet-close]')) shutSheet(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') shutSheet(); });
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
            [3, 'Preemergence herbicide', 'On standing water, three days after transplanting.', 0, '', 'Checks the forecast first'],
            [14, 'First top dress', 'Urea at early tillering, on a thin sheet of water.', 1],
            [35, 'Second top dress', 'Urea and potash at panicle initiation.', 1],
            [45, 'Scout for stem borer and leaffolder', 'Spray only past the threshold.', 0, '', 'Checks the forecast first'],
            [60, 'Flowering: keep 5 cm of water', 'The stage that decides the grain.'],
            [85, 'Drain the field', 'Two weeks before harvest.'],
            [100, 'Harvest window', 'When 85 percent of the grains are golden.', 1],
        ] },
        corn: { name: ph ? 'Mais, yellow corn' : 'Yellow corn', unit: 'DAP', len: 110, tasks: [
            [-14, 'Plow and harrow', 'Two passes, then furrows 75 cm apart.'],
            [0, 'Plant, with basal fertilizer', 'Seeds in the furrow, complete fertilizer beside them.', 1],
            [3, 'Preemergence herbicide', 'On moist soil, before the weeds come up.', 0, '', 'Checks the forecast first'],
            [14, 'First side dress and off barring', 'Urea beside the row, soil pulled away from the plants.', 1],
            [21, 'Scout for fall armyworm', 'Look into the whorl; spray only when needed.', 0, '', 'Checks the forecast first'],
            [30, 'Second side dress and hilling up', 'Urea, then soil back against the stalks.', 1],
            [55, 'Tasseling and silking', 'Water now if the soil is dry.'],
            [100, 'Harvest window', 'When the husks are dry and the black layer shows.', 1],
        ] },
        veg: { name: ph ? 'Talong, transplanted' : 'Eggplant, transplanted', unit: 'DAT', len: 120, tasks: [
            [-30, 'Sow the seedlings', 'In trays or a seedbed, under a light shade.', 0, 'DAS 0'],
            [-7, 'Prepare raised beds', 'Beds 1 m wide with mulch, and organic fertilizer worked in.'],
            [0, 'Transplant, with basal fertilizer', 'In the late afternoon, then water.', 1],
            [7, 'Replant the missing hills', 'Keep the stand even.'],
            [14, 'First side dress', 'Complete fertilizer beside each plant.', 1],
            [25, 'Stake the plants', 'Before the first fruits weigh them down.'],
            [30, 'Scout for fruit and shoot borer', 'Cut and bury the wilted tips each week.', 0, '', 'Checks the forecast first'],
            [50, 'First harvest', 'Then every three to four days.', 1],
            [110, 'Last harvest and clearing', 'Pull the old plants to break the pest cycle.'],
        ] },
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    let crop = 'rice', timers = [], rowIO = null;
    // Two weeks from today, in the visitor's own calendar (toISOString is
    // UTC, which in Manila before 8 in the morning is still yesterday).
    const d0 = new Date(); d0.setDate(d0.getDate() + 14);
    const ymd = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    $('lpdDate').value = ymd(d0);
    const fmt = (d) => d.toLocaleDateString(ph ? 'en-PH' : undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    // The day counts, said in words the first time a reader meets them.
    const UNITS = { DAS: 'days after sowing', DAT: 'days after transplanting', DAP: 'days after planting' };
    const draw = () => {
        timers.forEach(clearTimeout); timers = [];
        const S = SEASONS[crop];
        const base = new Date(($('lpdDate').value || ymd(d0)) + 'T00:00:00');
        const at = (n) => { const d = new Date(base); d.setDate(d.getDate() + n); return d; };
        $('lpdTitle').textContent = S.name;
        $('lpdSpan').textContent = fmt(at(S.tasks[0][0])) + ' to ' + fmt(at(S.tasks[S.tasks.length - 1][0])) + ' · ' + S.tasks.length + ' tasks';
        const used = [...new Set(S.tasks.map((t) => (t[4] || (t[0] >= 0 ? S.unit : '')).split(' ')[0]).filter((u) => UNITS[u]))];
        $('lpdKey').innerHTML = used.map((u) => '<span>' + u + ': ' + UNITS[u] + '</span>').join('')
            + '<span><i aria-hidden="true"></i>Fertilizer and harvest days</span>';
        const list = $('lpdList');
        list.style.setProperty('--fill', '0%');
        list.innerHTML = S.tasks.map(([n, t, sub, key, label, wx]) => '<li class="lpd-row' + (key ? ' is-key' : '') + '"><span class="lpd-when"><b>' + esc(fmt(at(n))) + '</b><small>'
            + esc(label || (n < 0 ? Math.abs(n) + ' days before' : S.unit + ' ' + n)) + '</small></span><span class="lpd-what"><b>' + esc(t) + '</b><small>' + esc(sub) + '</small>'
            + (wx ? '<em>' + esc(wx) + '</em>' : '') + '</span></li>').join('');
        const rows = [...list.children];
        if (rowIO) { rowIO.disconnect(); rowIO = null; }
        if (still() || !('IntersectionObserver' in window)) { rows.forEach((r) => r.classList.add('is-in')); list.style.setProperty('--fill', '100%'); return; }
        // Each task lands as the reader reaches it: the ones on screen one
        // after another, the rest when scrolled to (on a phone the list runs
        // two screens, and a timer alone finished it out of sight). The line
        // fills to the last task that has landed.
        const fill = () => {
            let last = -1;
            rows.forEach((r, i) => { if (r.classList.contains('is-in')) last = i; });
            list.style.setProperty('--fill', Math.round((last + 1) / rows.length * 100) + '%');
        };
        let next = 0;
        rowIO = new IntersectionObserver((es) => {
            const now = performance.now();
            next = Math.max(next, now);
            es.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top).forEach((e) => {
                rowIO.unobserve(e.target);
                next += 140;
                timers.push(setTimeout(() => { e.target.classList.add('is-in'); fill(); }, next - now));
            });
        }, { rootMargin: '0px 0px -6% 0px' });
        rows.forEach((r) => rowIO.observe(r));
    };
    // It plays the first time the season itself comes into view. On a phone
    // that is a screen below the section's title, and watching the section
    // let the rows land before the reader got there. A pick made before then
    // plays it at once, and the first sight does not play it again.
    let io = null;
    const pick = () => { if (io) { io.disconnect(); io = null; } draw(); };
    box.querySelectorAll('[data-crop]').forEach((b) => b.addEventListener('click', () => {
        crop = b.dataset.crop;
        box.querySelectorAll('[data-crop]').forEach((x) => x.setAttribute('aria-checked', String(x === b)));
        pick();
    }));
    $('lpdDate').addEventListener('change', pick);
    const out = box.querySelector('.lpd-out') || box;
    if (!('IntersectionObserver' in window)) { draw(); return; }
    io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) pick(); }, { rootMargin: '0px 0px -15% 0px' });
    io.observe(out);
})();
</script>
@endpush
