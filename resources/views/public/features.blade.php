@extends('layouts.public')

@include('public.partials.site-css')

@section('title', \App\Support\Region::ph() ? 'Farm App Features: Calendar, Satellite, NPK and Anee' : 'Farm Management App Features')
@section('meta_description', \App\Support\Region::ph()
    ? 'Every anee.io feature for Filipino farmers: the cropping calendar, satellite field health, typhoon watch, the NPK calculator, pest finders and Anee.'
    : 'Every anee.io feature: a cropping calendar by day count, satellite field health, storm tracking, a fertilizer calculator, workers, reports and Anee.')

@php
    $R = \App\Support\Region::class;
    $ph = $R::ph();
    $tick = '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $fCount = $featurePages->count();
    $fNew = ['satellite-analysis', 'satellite-weather', 'npk-plus-calculator', 'pest-and-disease-finders', 'the-stash'];
    // The filter's order; only the categories the pages use are shown.
    $fCats = collect(['Planning', 'Agronomy', 'Weather', 'Crop care', 'Anee', 'Workers', 'Records', 'Money', 'Community', 'Resources'])
        ->filter(fn ($c) => $featurePages->contains('category', $c))->values();
    // The tour, part by part: anchor, chip, kicker, new?, title, words, the
    // list, the screens (app shot, alt), the guide it opens.
    $fTour = [
        ['plan', 'Plan', 'Plan', false, 'The activities board: your season, day by day',
            'Build the whole calendar from land prep to harvest. Every task is dated from each lot\'s own day zero, so the timing stays right even when lots were sown a week apart.',
            ['Tasks, irrigation, hired services, payroll days and reminder checklists', 'Drag to reschedule, drafts for plans not yet decided, versions to try other plans',
             'Each spray task says where the spray goes: under the leaves, over the canopy, at the base', 'Undo that still works after you log out, because it is saved on the server'],
            [['images/site/app/board.png', 'The activities board of the anee.io cropping calendar']], 'cropping-calendar'],
        ['space', 'From space', 'From space', true, 'Your field and your sky, seen from space',
            'Satellite Analysis reads the field you draw from the newest Sentinel 2 picture and Sentinel 1 radar, which sees through typhoon clouds, and Anee tells you what it means. Satellite Weather plays back the clouds and rain over your farm, fast forwards the forecast and follows every typhoon.',
            ['Crop health score, greenness (NDVI) heatmap and the weak spots to walk first', 'Radar readings when clouds hide the field, the last 90 days as a line',
             'Typhoon paths with their cone, and a warning when one comes within 300 km', 'Anee reads it against your lot: its crop, its stage, the soil and ENSO'],
            [['images/site/app/satellite.webp', 'Satellite Analysis report with a crop health score of a corn field'], ['images/site/app/sky.webp', 'Satellite Weather showing clouds over Luzon and a typhoon ring around the farm']], 'satellite-analysis'],
        ['fertilizer', 'Fertilizer', 'Fertilizer', true, 'NPK Plus: every nutrient in your fertilizer plan',
            'Tap the fertilizers you plan to use, from urea and complete to manure and inoculants, or add your own from its label. NPK Plus adds up every nutrient as the element and the oxide, checks it against what your crop needs and shows the yield it can feed. Free, and as often as you like.',
            ['Nitrogen, phosphorus, potassium, and the micronutrients only when you use them', 'Biofertilizers like Azospirillum counted from field trial estimates',
             'Your soil test moves the target, low or high', 'On the board and in the Protocol Builder: what each lot has had and what is still planned'],
            [['images/site/app/npk.webp', 'NPK Plus showing nitrogen, phosphorus and potassium per hectare against the usual rate for rice']], 'npk-plus-calculator'],
        ['crop-care', 'Crop care', 'Crop care', true, 'From what you see to what to spray',
            'Pick the crop and what you see in the field. The Pest Finder and the Disease Finder name what fits, with the active ingredients that work and their groups, so you can switch groups and keep them working. The Weed Control Helper says what to do at the age of your rice.',
            [($ph ? 'Pests and diseases of palay, mais, gulay and fruit trees' : 'Pests and diseases of the main crops'), 'IRAC and FRAC groups, active ingredients only, never brands',
             'When not to spray at all, said plainly', 'Send Anee a photo when you are not sure'],
            [['images/site/app/finder.webp', 'The Pest Finder listing rice pests with the active ingredients to spray and their IRAC groups']], 'pest-and-disease-finders'],
        ['people', 'People', 'People', false, 'Workers, payroll and permissions that fit a real farm',
            'Keep a list of your workers with their rates and skills, assign them to activities, and watch the labor cost add up as you plan. Give a worker their own login and decide, part by part, what they can see and what they can change.',
            ['None, view or edit, for each part of the app and each worker', 'Payroll days with each worker\'s own rate, for half or whole days',
             'Your bell rings when a worker finishes a task, adds a photo or records a voice note', 'The morning email tells the whole team today\'s plan at 6 AM'],
            [['images/site/app/how/workers.webp', 'Workers and payroll in the anee.io app']], 'farm-workers-and-payroll'],
        ['records', 'Records', 'Records', false, 'Notes, photos, videos and your own voice',
            'The fastest record is the one you can make standing in the mud. Take a photo, film it, or just say it. Quick Voice saves a spoken note in seconds, and everything lands in a gallery you can search.',
            ['Notes for each season and each day, plus Global Notes for everything else', 'Voice notes play right on the card in notes, activities and chat',
             'A drawing pad for sketching over field photos', 'Works without signal, and syncs when it comes back'],
            [['images/site/app/notes.png', 'Notes with photos and voice recordings']], 'notes-photos-and-voice'],
        ['agronomy', 'Agronomy', 'Agronomy', false, 'Growth stages and weather that read your fields',
            'anee.io knows ' . ($ph ? '85 Philippine crops' : 'nearly a hundred crops') . '. Pick any date and it says where every lot stands: the growth stage, what it needs and what to watch for, with the week\'s forecast beside it.',
            [($ph ? 'Palay, mais, gulay and fruit trees' : 'Rice, corn, vegetables and fruit trees') . ', annuals and perennials both', 'Realign by Anee when a crop runs ahead of or behind the calendar',
             'Maps: draw and measure your lots, drop pins, save team maps'],
            [['images/site/app/growth.png', 'Growth stages of each lot']], 'growth-stages-and-weather'],
        ['money', 'Money', 'Money', false, 'Inventory, expenses and reports that agree to the ' . ($ph ? 'peso' : 'cent'),
            'The shed keeps your stock, and every move in or out is recorded with a name. Labor, expenses and profit reports are worked out straight from the plan, and Anee can write a full report of the season on top.',
            ['Inventory items and moves, with an audit trail of who did what', 'Labor, expenses and profit, computed and never guessed',
             'Post harvest observations with yields, buyers and prices', 'Compare any two reports side by side'],
            [['images/site/app/dashboard.png', 'The dashboard with money and season summaries']], 'farm-reports'],
        ['community', 'Community', 'Community', false, 'Cofarmers, discussions and a ladder worth climbing',
            'A news feed for wins and warnings, focused discussion rooms, direct messages with photos, clips and voice notes, and a ladder of 100 levels that turns helping into a game.',
            ['Public, password and approval rooms for private groups', 'A team Collab Room per season: chat, whiteboard and calls', 'The latest farm news, with what it means for your farm'],
            [['images/site/app/community.png', 'The farmer community feed']], 'farmer-community'],
    ];
@endphp

@push('head')
<style>
    /* ---- FEATURES (redrawn 2026-10-07) ------------------------------------
       A hero that moves, a band of every feature sliding past (each one
       opens its guide), every feature as a card with a filter, a sticky chip
       bar, then the tour: a phone that changes screen as each part of the
       farm scrolls past it. The house curve throughout; still when asked. */
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
    .fz-phones .p1 { left: 50%; top: 0; transform: translateX(-50%); z-index: 3; animation: fzFloat 6s ease-in-out infinite; }
    .fz-phones .p2 { left: 2%; top: 3.5rem; transform: rotate(-8deg); z-index: 2; opacity: .92; animation: fzFloat2 7s ease-in-out infinite; }
    .fz-phones .p3 { right: 2%; top: 3.5rem; transform: rotate(8deg); z-index: 2; opacity: .92; animation: fzFloat3 7.5s ease-in-out infinite; }
    @keyframes fzFloat { 50% { transform: translateX(-50%) translateY(-10px); } }
    @keyframes fzFloat2 { 50% { transform: rotate(-8deg) translateY(-8px); } }
    @keyframes fzFloat3 { 50% { transform: rotate(8deg) translateY(-8px); } }
    @media (max-width: 1023.98px) { .fz-phones { height: 25rem; } .fz-phones .ph-frame { width: min(200px, 44vw); } }
    @media (max-width: 479.98px) { .fz-phones { height: 21rem; } .fz-phones .p2 { left: -4%; } .fz-phones .p3 { right: -4%; } }

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

    /* The chip bar that follows the reader. */
    .fz-nav { position: sticky; top: 4.5rem; z-index: 20; background: rgb(255 255 255 / .9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid #eef2ea; }
    @media (min-width: 1024px) { .fz-nav { top: 5rem; } }
    .fz-nav-in { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; padding: .65rem 0; }
    .fz-nav-in::-webkit-scrollbar { display: none; }
    .fz-nav a { flex: none; padding: .45rem .85rem; border-radius: 999px; font-size: .84rem; font-weight: 800; text-decoration: none; color: #4b5563;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .fz-nav a:hover { background: #f3f8ec; color: #2d5016; }
    .fz-nav a.is-on { background: #2d5016; color: #fff; }

    /* Every feature, filtered by part of the farm. */
    .fz-sec { scroll-margin-top: 9rem; }
    .fz-cats { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: 1.6rem; }
    .fz-cats button { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: 999px; border: 1px solid #e1ead6; background: #fff; cursor: pointer;
        font-size: .86rem; font-weight: 800; color: #374151; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .fz-cats button:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .fz-cats button b { min-width: 1.4rem; padding: .05rem .4rem; border-radius: 999px; font-size: .72rem; text-align: center; background: #eef6e6; color: #2f5219; }
    .fz-cats button[aria-pressed="true"] { background: #2d5016; border-color: #2d5016; color: #fff; }
    .fz-cats button[aria-pressed="true"] b { background: #f5c518; color: #1f1500; }

    /* The tour: a phone that stays while the parts scroll past it. */
    .ft { position: relative; display: grid; gap: 3rem; }
    @media (min-width: 1024px) { .ft { grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); gap: 4.5rem; align-items: start; } }
    .ft-stage { display: none; }
    @media (min-width: 1024px) {
        .ft-stage { display: grid; place-items: center; position: sticky; top: calc(var(--fz-top, 8rem) + 1.5rem); height: min(40rem, calc(100vh - var(--fz-top, 8rem) - 3rem)); }
    }
    .ft-halo { position: absolute; inset: 8% 6%; border-radius: 999px; filter: blur(30px); opacity: .55; transition: background .6s cubic-bezier(.22,1,.36,1);
        background: radial-gradient(closest-side, hsl(var(--ft-h, 100) 60% 70% / .7), transparent); }
    .ft-phone { position: relative; width: min(290px, 100%); aspect-ratio: 780 / 1520; max-height: 100%; border-radius: 2.4rem; padding: .55rem; background: #14210c;
        box-shadow: 0 0 0 1px rgb(255 255 255 / .06) inset, 0 40px 80px -40px rgb(20 33 12 / .8); }
    .ft-screen { position: relative; width: 100%; height: 100%; border-radius: 1.9rem; overflow: hidden; background: #f6f8f3; }
    .ft-screen img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: top; opacity: 0; transform: scale(1.04) translateY(10px);
        transition: opacity .6s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1); }
    .ft-screen img.is-on { opacity: 1; transform: none; }
    .ft-dots { position: absolute; right: -1.6rem; top: 50%; translate: 0 -50%; display: grid; gap: .45rem; }
    .ft-dots i { width: .45rem; height: .45rem; border-radius: 999px; background: #cfdcc2; transition: height .4s cubic-bezier(.22,1,.36,1), background-color .4s cubic-bezier(.22,1,.36,1); }
    .ft-dots i.is-on { height: 1.4rem; background: #3d6823; }
    .ft-steps { display: grid; gap: 1.5rem; }
    @media (min-width: 1024px) { .ft-steps { gap: 0; } .ft-step { min-height: 78vh; display: flex; flex-direction: column; justify-content: center; } }
    .ft-step { scroll-margin-top: 9rem; }
    .ft-card { position: relative; padding: 1.6rem 1.5rem; border-radius: 1.6rem; background: #fff; border: 1px solid #e8efe0; box-shadow: 0 30px 60px -50px rgb(20 33 12 / .6);
        transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1), box-shadow .5s cubic-bezier(.22,1,.36,1), border-color .5s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 1024px) {
        html.js .ft-step:not(.is-on) .ft-card { opacity: .38; transform: scale(.97); }
        .ft-step.is-on .ft-card { border-color: hsl(var(--ft-h, 100) 45% 78%); box-shadow: 0 40px 70px -46px hsl(var(--ft-h, 100) 40% 20% / .55); }
    }
    .ft-k { display: inline-flex; align-items: center; gap: .5rem; font-size: .78rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #3d6823; }
    .ft-k span { display: grid; place-items: center; width: 1.7rem; height: 1.7rem; border-radius: .6rem; font-size: .8rem; color: #fff; background: #3d6823; }
    .ft-k i { font-style: normal; padding: .12rem .5rem; border-radius: 999px; font-size: .62rem; letter-spacing: .08em; color: #1f1500; background: #f5c518; }
    .ft-h { margin-top: .7rem; font-family: var(--font-heading); font-size: clamp(1.45rem, 2.6vw, 2rem); font-weight: 800; line-height: 1.15; color: #14210c; text-wrap: balance; }
    .ft-p { margin-top: .8rem; color: #4b5563; line-height: 1.7; }
    .ft-list { margin-top: 1rem; display: grid; gap: .55rem; }
    .ft-list li { display: flex; gap: .6rem; align-items: flex-start; color: #374151; font-size: .94rem; line-height: 1.55; opacity: 0; transform: translateX(-8px);
        transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--n) * 70ms + .1s); }
    .ft-step.is-seen .ft-list li, html:not(.js) .ft-list li { opacity: 1; transform: none; }
    .ft-list li svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; color: #4a7c2a; }
    .ft-go { display: inline-flex; align-items: center; gap: .35rem; margin-top: 1.2rem; font-weight: 800; color: #3d6823; text-decoration: none; }
    .ft-go svg { width: 1rem; height: 1rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ft-go:hover svg { transform: translateX(3px); }
    /* On a phone, each part carries its own screen. */
    .ft-inline { display: flex; justify-content: center; gap: .8rem; margin: 0 0 1.2rem; }
    .ft-inline img { width: min(46%, 11rem); border-radius: 1.2rem; box-shadow: 0 0 0 5px #14210c, 0 24px 40px -24px rgb(20 33 12 / .6); }
    @media (min-width: 1024px) { .ft-inline { display: none; } }
    .fz-link { display: inline-flex; align-items: center; gap: .35rem; margin-top: 1.1rem; font-weight: 800; color: #3d6823; text-decoration: none; }
    .fz-link:hover { text-decoration: underline; }

    @media (prefers-reduced-motion: reduce) {
        .fz-orb, .fz-kick i, .fz-h1 em, .fz-phones .ph-frame, .fz-track { animation: none !important; }
        .fz-track { flex-wrap: wrap; width: auto; justify-content: center; }
        .fz-track.rev { display: none; }
        .fz-nav a, .fz-cats button, .ft-card, .ft-screen img, .ft-list li, .ft-dots i, .ft-halo { transition: none !important; }
        .ft-list li { opacity: 1; transform: none; }
    }
</style>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="fz-hero">
        <i class="fz-orb a" aria-hidden="true"></i><i class="fz-orb b" aria-hidden="true"></i>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-12 sm:pt-20 sm:pb-16 fz-hero-in">
            <div class="animate-fade-up">
                <span class="fz-kick"><i aria-hidden="true"></i>The full tour</span>
                <h1 class="fz-h1">Everything Your Farm <em>Runs On.</em></h1>
                <p class="fz-lede">
                    One farm app for the whole season: the cropping calendar, your field seen from space, the typhoon on its way, every nutrient in your fertilizer,
                    your workers and every {{ $ph ? 'peso' : 'dollar' }}, with Anee, your smart farm technician, beside you. Every screen below is the real thing.
                </p>
                <div class="fz-stats">
                    <div><b>{{ $fCount }}</b><small>features, each with its own guide</small></div>
                    <div><b>{{ $ph ? '85' : '100' }}</b><small>crops it knows by stage</small></div>
                    <div><b>2</b><small>satellites over your field</small></div>
                </div>
                <div class="fz-cta">
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                    <a href="#all" class="btn btn-lg btn-on-dark">See every feature</a>
                </div>
            </div>
            <div class="fz-phones" aria-hidden="true">
                <span class="ph-frame p2"><img src="{{ asset('images/site/app/board.png') }}" alt="" loading="lazy" width="780" height="1520"></span>
                <span class="ph-frame p3"><img src="{{ asset('images/site/app/npk.webp') }}" alt="" loading="lazy" width="780" height="1520"></span>
                <span class="ph-frame p1"><img src="{{ asset('images/site/app/sky.webp') }}" alt="" width="780" height="1520"></span>
            </div>
        </div>
        {{-- Every feature, sliding past slowly; each chip opens its guide. --}}
        <nav class="fz-band" aria-label="Every feature">
            <div class="fz-track">
                @foreach ($featurePages->concat($featurePages) as $i => $fp)
                    <a href="{{ \App\Support\SitePages::pageUrl($fp) }}" class="{{ in_array($fp->slug, $fNew, true) ? 'is-new' : '' }}" @if ($i >= $fCount) aria-hidden="true" tabindex="-1" @endif>{{ \App\Support\SitePages::feature($fp)['name'] }}</a>
                @endforeach
            </div>
            <div class="fz-track rev" aria-hidden="true">
                @foreach ($featurePages->reverse()->concat($featurePages->reverse()) as $fp)
                    <a href="{{ \App\Support\SitePages::pageUrl($fp) }}" class="{{ in_array($fp->slug, $fNew, true) ? 'is-new' : '' }}" tabindex="-1">{{ \App\Support\SitePages::feature($fp)['name'] }}</a>
                @endforeach
            </div>
        </nav>
    </section>

    {{-- ================= THE CHIP BAR ================= --}}
    <nav class="fz-nav" aria-label="Feature sections">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 fz-nav-in" id="fzNav">
            <a href="#all" data-to="all">All {{ $fCount }} features</a>
            @foreach ($fTour as [$k, $l])<a href="#{{ $k }}" data-to="{{ $k }}">{{ $l }}</a>@endforeach
        </div>
    </nav>

    {{-- ================= EVERY FEATURE, ONE CARD EACH ================= --}}
    @if ($featurePages->isNotEmpty())
    <section class="relative py-14 sm:py-16 bg-[#fbfcf9] border-b border-gray-100 fz-sec" id="all">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one farm app</p>
                <h2 class="mt-2 font-heading text-2xl sm:text-3xl font-bold text-ink text-balance">{{ $fCount }} Features for One Cropping Season</h2>
                <p class="mt-3 text-gray-600">Each one works on its own and with the rest: plan the season, keep the records, and ask for advice from the same phone. Pick a part of the farm, then open any feature for its full guide.</p>
            </div>
            <div class="fz-cats reveal" role="group" aria-label="Show features for">
                <button type="button" data-cat="" aria-pressed="true">All <b>{{ $fCount }}</b></button>
                @foreach ($fCats as $c)
                    <button type="button" data-cat="{{ $c }}" aria-pressed="false">{{ $c }} <b>{{ $featurePages->where('category', $c)->count() }}</b></button>
                @endforeach
            </div>
            <div class="mt-6">@include('public.site.feature-grid', ['pages' => $featurePages])</div>
        </div>
    </section>
    @endif

    {{-- ================= THE TOUR ================= --}}
    {{-- Each part of the farm in turn (rebuilt 2026-10-07): on a wide screen
         the phone stays and changes screen as each part comes into view; on
         a phone, each part carries its own screen. --}}
    <section class="py-16 sm:py-24 bg-white" id="tour">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">The tour</p>
                <h2 class="mt-2 font-heading text-2xl sm:text-3xl font-bold text-ink text-balance">Part by Part, the Way a Season Runs</h2>
                <p class="mt-3 text-gray-600">Scroll on: the phone shows each part of the app as you read about it. Every screen is the real thing.</p>
            </div>
            <div class="ft mt-10" data-tour>
                <div class="ft-stage" aria-hidden="true">
                    <i class="ft-halo"></i>
                    <div class="ft-phone">
                        <div class="ft-screen">
                            @foreach ($fTour as $ti => $tp)
                                @foreach ($tp[7] as $si => [$src, $alt])
                                    <img src="{{ asset($src) }}" alt="" data-shot="{{ $ti }}-{{ $si }}" class="{{ $ti === 0 && $si === 0 ? 'is-on' : '' }}" loading="{{ $ti === 0 ? 'eager' : 'lazy' }}" decoding="async">
                                @endforeach
                            @endforeach
                        </div>
                        <span class="ft-dots">@foreach ($fTour as $ti => $tp)<i class="{{ $ti === 0 ? 'is-on' : '' }}"></i>@endforeach</span>
                    </div>
                </div>
                <div class="ft-steps">
                    @foreach ($fTour as $ti => [$tk, $tchip, $tkick, $tnew, $th, $tpara, $tlist, $tshots, $tguide])
                        <div class="ft-step fz-sec{{ $ti === 0 ? ' is-on' : '' }}" id="{{ $tk }}" data-step="{{ $ti }}" data-shots="{{ count($tshots) }}" style="--ft-h: {{ [100, 210, 35, 0, 160, 260, 120, 45, 300, 50][$ti] ?? 100 }}">
                            <div class="ft-card">
                                <div class="ft-inline">
                                    @foreach ($tshots as [$src, $alt])<img src="{{ asset($src) }}" alt="{{ $alt }}" loading="lazy" decoding="async">@endforeach
                                </div>
                                <p class="ft-k"><span>{{ $ti + 1 }}</span>{{ $tkick }}@if ($tnew)<i>New</i>@endif</p>
                                <h3 class="ft-h">{{ $th }}</h3>
                                <p class="ft-p">{{ $tpara }}</p>
                                <ul class="ft-list">
                                    @foreach ($tlist as $li => $item)<li style="--n: {{ $li }}">{!! $tick !!}<span>{{ $item }}</span></li>@endforeach
                                </ul>
                                <a href="{{ url('/features/' . $tguide) }}" class="ft-go">Read the full guide <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></a>
                                @if ($tk === 'crop-care')<a href="{{ url('/pests') }}#finder" class="ft-go" style="margin-left: 1rem">Try the Pest Finder ›</a>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

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
            <p class="mt-4 text-gray-600">Set up your first cropping schedule in minutes: lots, workers and the whole calendar. Libre is free forever.</p>
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
    /* The chip bar follows the reader: the section in view lights its chip,
       and the chip slides into view in the bar. */
    const nav = document.getElementById('fzNav');
    if (!nav) return;
    // The bar hangs right under the site header, whatever its height today.
    const hdr = document.querySelector('header');
    const hang = () => {
        if (!hdr) return;
        const h = Math.round(hdr.getBoundingClientRect().height);
        nav.parentElement.style.top = h + 'px';
        // The tour's phone stays below the header and the chip bar.
        document.documentElement.style.setProperty('--fz-top', (h + nav.parentElement.offsetHeight) + 'px');
    };
    hang();
    addEventListener('resize', hang, { passive: true });
    if (!('IntersectionObserver' in window)) return;
    const chips = [...nav.querySelectorAll('a[data-to]')];
    const secs = chips.map((a) => document.getElementById(a.dataset.to)).filter(Boolean);
    let on = null;
    const light = (id) => {
        if (id === on) return;
        on = id;
        chips.forEach((a) => a.classList.toggle('is-on', a.dataset.to === id));
        const a = chips.find((x) => x.dataset.to === id);
        if (a) nav.scrollTo({ left: a.offsetLeft - nav.clientWidth / 2 + a.clientWidth / 2, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    };
    const io = new IntersectionObserver((es) => {
        const vis = es.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
        if (vis) light(vis.target.id);
    }, { rootMargin: '-40% 0px -55% 0px' });
    secs.forEach((s) => io.observe(s));
})();

(() => {
    /* The filter: the cards of one part of the farm, coming back softly. */
    const bar = document.querySelector('.fz-cats');
    const cards = [...document.querySelectorAll('#all .fg-card')];
    if (!bar || !cards.length) return;
    bar.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-cat]');
        if (!b) return;
        bar.querySelectorAll('button').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        let n = 0;
        cards.forEach((c) => {
            const show = !b.dataset.cat || c.dataset.cat === b.dataset.cat;
            c.classList.toggle('is-out', !show);
            c.classList.remove('is-in');
            if (show) { c.style.setProperty('--n', n++); void c.offsetWidth; c.classList.add('is-in'); }
        });
    });
})();

(() => {
    /* The tour: the part in the middle of the screen lights its card and
       puts its screen on the phone (two screens take turns). */
    const tour = document.querySelector('[data-tour]');
    if (!tour) return;
    const steps = [...tour.querySelectorAll('.ft-step')];
    const shots = [...tour.querySelectorAll('.ft-screen img')];
    const dots = [...tour.querySelectorAll('.ft-dots i')];
    const stage = tour.querySelector('.ft-stage');
    let on = -1, turn = null, which = 0;
    const show = (key) => shots.forEach((im) => im.classList.toggle('is-on', im.dataset.shot === key));
    const light = (i) => {
        if (i === on) return;
        on = i;
        steps.forEach((s, k) => s.classList.toggle('is-on', k === i));
        dots.forEach((d, k) => d.classList.toggle('is-on', k === i));
        if (stage) stage.style.setProperty('--ft-h', getComputedStyle(steps[i]).getPropertyValue('--ft-h'));
        clearInterval(turn); which = 0;
        show(i + '-0');
        const n = Number(steps[i].dataset.shots || 1);
        if (n > 1 && !matchMedia('(prefers-reduced-motion: reduce)').matches) turn = setInterval(() => { which = (which + 1) % n; show(i + '-' + which); }, 2600);
    };
    if (!('IntersectionObserver' in window)) { steps.forEach((s) => s.classList.add('is-seen')); return; }
    const seen = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-seen'); seen.unobserve(e.target); } }), { threshold: .25 });
    const mid = new IntersectionObserver((es) => {
        const v = es.filter((e) => e.isIntersecting)[0];
        if (v) light(steps.indexOf(v.target));
    }, { rootMargin: '-45% 0px -50% 0px' });
    steps.forEach((s) => { seen.observe(s); mid.observe(s); });
})();
</script>
@endpush
