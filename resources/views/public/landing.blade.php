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
        'board' => ['lp/board.webp', 'The anee.io season board: each day with its growth stage, an open herbicide task on its DAT count', 1600],
        'growth' => ['lp/growth.webp', 'The growth stage of a rice lot, with what to do now and what to watch for', 1600],
        'weather' => ['lp/weather.webp', 'The forecast for a lot in Muñoz: the chance of rain by the day and by the hour', 1566],
        'hub' => ['lp/hub.webp', 'A season\'s modules in anee.io: lots, workers, inventory, weather, growth stages, maps, Anee, reports and more', 1566],
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
    .lp-h1 { font-family: var(--font-heading); font-weight: 800; color: #14210c; line-height: 1.06; letter-spacing: -.02em;
        font-size: clamp(2.1rem, 5.2vw, 3.6rem); text-wrap: balance; }
    /* A long headline a size down, so the email box still shows on the first screen. */
    .lp-h1.is-long { font-size: clamp(1.8rem, 4.1vw, 2.9rem); line-height: 1.08; }
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
    /* Proof strip */
    .lp-proof { border-block: 1px solid #edf2e7; background: #fff; }
    .lp-proof-row { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem 1.6rem; }
    .lp-proof-row span { display: inline-flex; align-items: center; gap: .45rem; font-size: .88rem; font-weight: 700; color: #3f4a37; }
    .lp-proof-row svg { width: 1.05rem; height: 1.05rem; color: #4a7c2a; }
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
    .lp-loss-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); }
    .lp-loss { padding: 1.3rem 1.2rem 1.25rem; border-radius: 1.25rem; background: rgb(255 255 255 / .05);
        border: 1px solid rgb(255 255 255 / .1); }
    .lp-loss .n { display: flex; align-items: baseline; gap: .35rem; }
    .lp-loss .n small { font-size: .78rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #fca5a5; }
    .lp-loss .n b { font-family: var(--font-heading); font-size: 2.9rem; line-height: 1; font-weight: 900; color: #fecaca; font-variant-numeric: tabular-nums; }
    .lp-loss .bar { height: .4rem; border-radius: 999px; margin: .8rem 0 .9rem; background: rgb(255 255 255 / .1); overflow: hidden; }
    .lp-loss .bar i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #f97316, #ef4444);
        transition: width 1.2s cubic-bezier(.22,1,.36,1); }
    .lp-loss.is-lit .bar i { width: var(--w); }
    .lp-loss h3 { font-weight: 800; font-size: 1.02rem; line-height: 1.35; }
    .lp-loss p { margin-top: .35rem; font-size: .88rem; line-height: 1.55; color: rgb(255 255 255 / .72); }
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
        .lp-bar, .lp-qa .a, .lp-qa button svg, .lp-tile, .lp-right, .lp-loss .bar i { transition: none; }
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

    {{-- ================= 2. PROOF ================= --}}
    <section class="lp-proof">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
            <p class="text-center text-xs font-extrabold uppercase tracking-wider text-gray-500">{{ $lp['proof']['lead'] }}</p>
            <div class="lp-proof-row mt-3">
                @foreach ($lp['proof']['items'] as $item)
                    <span>{!! $check !!}{{ $item }}</span>
                @endforeach
            </div>
            @if (($lp['proof']['stats'] ?? 'show') !== 'hide' && ($stats['activities'] ?? '—') !== '—')
                <p class="mt-3 text-center text-sm text-gray-500"><b class="text-gray-800">{{ $stats['activities'] }}</b> farm activities planned so far, across <b class="text-gray-800">{{ $stats['seasons'] }}</b> seasons.</p>
            @endif
        </div>
    </section>

    {{-- ================= 3. THE PROBLEM: weather and costs ================= --}}
    <section class="py-16 sm:py-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                <div class="lp-photo reveal" style="aspect-ratio: 4 / 3">
                    <img src="{{ \App\Support\LandingPage::imageUrl($lp['problem']['image'], 'lp/sacks.webp') }}" alt="A farmer in his fertilizer shed, trying to work out his notebook" width="1400" height="1046" loading="lazy">
                </div>
                <div class="reveal">
                    <span class="fx-kicker">{{ $lp['problem']['kicker'] }}</span>
                    <h2 class="fx-h">{{ $lp['problem']['headline'] }}</h2>
                    <ul class="lp-pain mt-6">
                        @foreach ($lp['problem']['bullets'] as $b)
                            <li><b aria-hidden="true">✕</b><span>{{ $b }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= What guessing costs a hectare ================= --}}
    @if (! empty($lp['losses']['items']))
        <section class="lp-losses" id="lpLosses">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
                <h2 class="font-heading font-extrabold text-2xl sm:text-4xl text-center text-balance reveal">{{ $lp['losses']['headline'] }}</h2>
                <div class="lp-loss-grid mt-9">
                    @foreach ($lp['losses']['items'] as $i => $l)
                        @php $n = max(0, min(100, (int) ($l['n'] ?? 0))); @endphp
                        <div class="lp-loss reveal" style="--w: {{ $n }}%; --reveal-delay: {{ $i * 0.08 }}s">
                            <div class="n"><small>Up to</small><b data-n="{{ $n }}">{{ $n }}%</b></div>
                            <div class="bar" aria-hidden="true"><i></i></div>
                            <h3>{{ $l['title'] ?? '' }}</h3>
                            @if (trim($l['text'] ?? '') !== '')<p>{{ $l['text'] }}</p>@endif
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
    const losses = document.querySelectorAll('#lpLosses .lp-loss');
    const light = (card) => {
        card.classList.add('is-lit');
        const b = card.querySelector('b[data-n]');
        const to = Number(b.dataset.n) || 0;
        if (calm) { b.textContent = to + '%'; return; }
        const t0 = performance.now();
        const step = (t) => {
            const k = Math.min(1, (t - t0) / 1100);
            b.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))) + '%';
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };
    if (losses.length && 'IntersectionObserver' in window) {
        losses.forEach((c) => { if (!calm) c.querySelector('b[data-n]').textContent = '0%'; });
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
@endpush
