@extends('layouts.public')

@include('public.partials.site-css')

@section('title', \App\Support\Region::ph() ? 'Farm App Features: Calendar, Satellite, NPK and Anee' : 'Farm Management App Features')
@section('meta_description', \App\Support\Region::ph()
    ? 'Every anee.io feature for Filipino farmers: the cropping calendar, satellite field health, typhoon watch, the NPK calculator, pest finders and Anee.'
    : 'Every anee.io feature: a cropping calendar by day count, satellite field health, storm tracking, a fertilizer calculator, workers, reports and Anee.')

{{-- FEATURES, REBUILT AROUND WHAT A FARMER NEEDS (2026-11-24, the owner: "analyze the features page deeply ... redesign
     it in a way that persuades users to register based on what they need ... it feels like something is off ... do you
     think its better to group it like ... crop preparation, and then during your planting, then harvest and post
     harvest? ... what it looks now looks complicated to understand").

     What was off: 65 cards of equal weight in eight steps named in our own words (16,800 px on a desktop), the benefit
     folded away behind a tap, a plan line on every card, and no question a farmer could answer about themselves. Now
     the page reads in three layers, most persuasive first:
       1. "What do you need help with?" Six needs a farmer has (plan, pests, fertilizer, weather, workers, money). One
          tap shows what changes for them, the few tools that do it, what it costs, and the way in (register).
       2. The season in four stages (the owner's idea): before you plant, planting and growing, protecting the crop,
          harvest and after. Each shows its four headline tools; the rest open on a tap, as short rows.
       3. Anee Auto, Anee, the plans in one look, the last call.
     Every tool still comes from How It Works (HowItWorks::stages), with its film and its guide. The Farmer community
     tool stays out while the Community is closed (App\Support\Community). --}}
@php
    $R = \App\Support\Region::class;
    $ph = $R::ph();
    $HW = \App\Support\HowItWorks::class;
    $stagesAll = $HW::stages();
    $tool = collect($stagesAll)->flatMap(fn ($st) => array_filter($st['items']))->keyBy('key');
    if (! \App\Support\Community::open()) {
        $tool->forget('community');
    }
    $fCount = $tool->count();
    $guideOf = function (array $it) use ($ph) {
        if (! empty($it['page']) && $ph) {
            return \App\Support\SitePages::url('features', $it['page']);
        }

        return ! empty($it['url']) ? url($it['url']) : null;
    };
    $planWord = ['libre' => 'Free', 'libreAnee' => 'Libre + Anee', 'solo' => 'Solo Farmer', 'owner' => 'Farm Owner'];
    $priceOf = fn (string $tier) => (float) ($R::tierPrice($tier, 'month') ?? 0);
    $money = fn (string $tier) => $priceOf($tier) > 0 ? $R::money($priceOf($tier), 0) : 'Free';

    // 1. What a farmer needs help with. Each: key, emoji, the need, the pain in their words, the promise, what changes,
    //    and the tools that do it (the first is the one shown on the phone).
    $needs = [
        ['plan', '🗓️', 'Plan a better season', 'Not sure when to plant, what to plant or which seed to buy?',
            'Plant on the best week, with the right crop and seed, and the whole season written before day zero.',
            ['The planting window that fits your town\'s weather and the El Niño or La Niña outlook', 'The crop and the variety that suit your land, compared before you buy seed', 'Every task of the season dated from your lot\'s own planting day'],
            ['when', 'what', 'variety', 'cropProtocol']],
        ['pests', '🐛', 'Stop pests, diseases and weeds', 'Spots, holes, worms or weeds, and no idea what to spray?',
            'Know what is attacking your crop the day you see it, and what to put on it, before it spreads.',
            ['Send a photo and Anee names the problem, in Tagalog or English', 'Only products registered for that pest on that crop, with the label to follow', 'How many days each spray keeps working, so nothing slips through'],
            ['chat', 'pestFinder', 'diseaseFinder', 'weedHelper']],
        ['feed', '🧪', 'Feed the crop right', 'Too much, too little or the wrong fertilizer, at the wrong time?',
            'Give the crop what it needs, in the week it needs it, without wasting a sack.',
            ['Every nutrient in your fertilizer plan counted against what the crop needs', 'Yellow or odd leaves traced to the nutrient that is missing', 'Which fertilizers and sprays can safely go on together'],
            ['npk', 'nutrientFinder', 'lcc', 'compat']],
        ['weather', '🌀', 'Beat the weather', 'Typhoons, dry spells and rain on your spray day?',
            'See the storm, the dry spell and the rain coming to your own field, and move the work before it hits.',
            ['Typhoon tracks, with how far the eye is from your field', 'The forecast and the week\'s heat for your exact lot', 'After bad weather, the real stage of your crop, so the plan follows'],
            ['skyWeather', 'weather', 'heatMap', 'realign']],
        ['team', '👥', 'Run the work and the workers', 'Tasks forgotten, workers asking what to do, the farm far from you?',
            'Every task on its day, every worker told, and the farm in your hand even when you are not there.',
            ['Today\'s work on one board, and in every worker\'s inbox at 6 AM', 'Daily rates, days off and payroll counted for you', 'Logins for your team, with only the access you allow'],
            ['schedule', 'board', 'workers', 'morning', 'access']],
        ['money', '💰', 'Know if you are making money', 'Money goes out all season and the profit is a guess?',
            'Every peso written down, the profit of each lot, and the best time to sell.',
            ['Expenses and labor counted per lot and per season', 'The profit of each lot, and this season beside the last', 'Whether to sell wet or dry it first, and where the price is heading'],
            ['profit', 'expenses', 'bilad', 'market', 'compare']],
    ];

    // 2. The season in four stages, from How It Works' eight steps.
    $stageOf = ['plan' => 'before', 'build' => 'before', 'plant' => 'grow', 'grow' => 'grow', 'protect' => 'protect', 'harvest' => 'harvest', 'reports' => 'harvest'];
    $stages = [
        'before' => ['Before you plant', 'Prepare', 'Decide when, what, where and how before a single seed goes in. A mistake costs the least while it is still on paper.',
            'lp/tractor.webp', null, 'A tractor preparing a field before planting', ['when', 'variety', 'protocol', 'npk']],
        'grow' => ['Planting and growing', 'Plant and grow', 'Every lot counted from its own day zero, every task on its day, and the crew, the stock and the weather on one board.',
            'photos/transplant-800.webp', 'photos/transplant-480.webp', 'Farmers transplanting rice seedlings', ['schedule', 'growth', 'workers', 'skyWeather']],
        'protect' => ['Protecting the crop', 'Protect', 'Yellow leaves, holes, weeds or a storm on the way. The sooner you know what it is and what to put on, the less it costs.',
            'photos/inspect-800.webp', 'photos/inspect-480.webp', 'A farmer inspecting his crop for pests', ['chat', 'pestFinder', 'nutrientFinder', 'rangeEffect']],
        'harvest' => ['Harvest and after', 'Harvest and learn', 'Write down what came off the field and where it went, see what the season earned, and make the next one better.',
            'photos/sacks-shed-800.webp', 'photos/sacks-shed-480.webp', 'Sacks of harvested palay in a shed', ['observations', 'profit', 'bilad', 'market']],
    ];
    $inStage = array_fill_keys(array_keys($stages), []);
    foreach ($stagesAll as $st) {
        $to = $stageOf[$st['key']] ?? null;
        if (! $to) {
            continue;
        }
        foreach (array_filter($st['items']) as $it) {
            if ($tool->has($it['key'])) {
                $inStage[$to][] = $it['key'];
            }
        }
    }
    // Land prep and the seed count are done before planting day, whatever step How It Works files them under.
    foreach (['landprep', 'seedCount'] as $k) {
        if (in_array($k, $inStage['grow'], true)) {
            $inStage['grow'] = array_values(array_diff($inStage['grow'], [$k]));
            $inStage['before'][] = $k;
        }
    }
    $auto = $tool->get('auto');

    $plans = [];
    foreach (['libre', 'libreAnee', 'solo', 'owner'] as $t) {
        $c = config('tiers.' . $t);
        if ($c) {
            $plans[] = ['key' => $t, 'name' => \App\Support\Tier::planName($t), 'price' => $money($t), 'per' => $priceOf($t) > 0 ? 'a month' : 'forever',
                'points' => array_slice($c['sheet'] ?? $c['features'] ?? [], 0, 3)];
        }
    }
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $play = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>';
    // A tool's film button, carrying what the film window says about it.
    $filmAttrs = function (string $key, string $where) use ($tool, $HW, $guideOf) {
        $it = $tool->get($key);
        $film = $it ? $HW::video($key) : null;
        if (! $film) {
            return '';
        }
        $helps = array_map(fn ($h) => $HW::helpHtml($h), (array) ($it['helps'] ?? []));

        return 'data-film="' . e($film[0]) . '" data-poster="' . e($film[1]) . '" data-key="' . e($key) . '" data-name="' . e($it['name']) . '" data-what="' . e($it['what']) . '" data-where="' . e($where)
            . '" data-guide="' . e((string) $guideOf($it)) . '" data-helps="' . e(json_encode($helps, JSON_UNESCAPED_UNICODE)) . '"';
    };
@endphp

@push('head')
<style>
    /* ---- FEATURES, by need (2026-11-24) ---------------------------------------------------------------------------
       The farm's deep green for the hero and Anee, white and a soft green for the reading parts, the brand's yellow
       only for the way in. Every motion stops for a reader who asked for less. */
    .fx-hero { position: relative; isolation: isolate; overflow: hidden; color: #eef4e6; background: radial-gradient(70rem 40rem at 85% -10%, #4a7c2a 0%, transparent 60%), linear-gradient(160deg, #1d3310, #0d1609 70%); }
    .fx-hero::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgb(255 255 255 / .1) 1px, transparent 1.5px); background-size: 22px 22px;
        -webkit-mask-image: linear-gradient(180deg, #000, transparent 85%); mask-image: linear-gradient(180deg, #000, transparent 85%); }
    .fx-hero-in { display: grid; gap: 2rem; align-items: center; }
    @media (min-width: 1024px) { .fx-hero-in { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); } }
    .fx-kick { display: inline-flex; align-items: center; gap: .5rem; font-size: .78rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: #f5c518; }
    .fx-kick i { width: .5rem; height: .5rem; border-radius: 999px; background: #f5c518; box-shadow: 0 0 0 .3rem rgb(245 197 24 / .2); }
    .fx-h1 { margin-top: .7rem; font-family: var(--font-heading); font-weight: 800; color: #fff; font-size: clamp(2.2rem, 5.2vw, 3.7rem); line-height: 1.05; letter-spacing: -.02em; text-wrap: balance; }
    .fx-h1 em { font-style: normal; color: #f5c518; }
    .fx-lede { margin-top: 1rem; max-width: 36rem; font-size: 1.06rem; line-height: 1.7; color: #c9dcb5; }
    .fx-stats { margin-top: 1.5rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; max-width: 30rem; }
    .fx-stats b { display: block; font-family: var(--font-heading); font-size: 2rem; font-weight: 800; line-height: 1; color: #fff; }
    .fx-stats small { display: block; margin-top: .35rem; font-size: .78rem; font-weight: 700; line-height: 1.35; color: #a8cc7e; }
    .fx-cta { margin-top: 1.7rem; display: flex; flex-wrap: wrap; gap: .7rem; }
    .fx-phones { position: relative; height: calc(min(210px, 44vw) * 1.95 + 2.5rem); }
    @media (min-width: 1024px) { .fx-phones { height: 30rem; } }
    .fx-phones .ph-frame { position: absolute; width: min(210px, 44vw); }
    @media (min-width: 1024px) { .fx-phones .ph-frame { width: 230px; } }
    .fx-phones .ph-frame img { aspect-ratio: 780 / 1520; object-fit: cover; object-position: top; }
    .fx-phones .p1 { left: 50%; top: 0; transform: translateX(-50%); z-index: 3; animation: fxFloat 6s ease-in-out infinite; }
    .fx-phones .p2 { left: 0; top: 2.2rem; transform: rotate(-8deg); z-index: 2; opacity: .92; }
    .fx-phones .p3 { right: 0; top: 2.2rem; transform: rotate(8deg); z-index: 2; opacity: .92; }
    @keyframes fxFloat { 50% { transform: translateX(-50%) translateY(-10px); } }
    @media (max-width: 479.98px) { .fx-phones .p2 { left: -4%; } .fx-phones .p3 { right: -4%; } }

    .fx-sec { scroll-margin-top: 5rem; }
    .fx-head { max-width: 44rem; margin: 0 auto; text-align: center; }
    .fx-k { font-size: .76rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #4a7c2a; }
    .fx-h2 { margin-top: .4rem; font-family: var(--font-heading); font-size: clamp(1.6rem, 4vw, 2.5rem); font-weight: 800; line-height: 1.12; color: #14210c; text-wrap: balance; }
    .fx-h2 em { font-style: normal; color: #4a7c2a; }
    .fx-p { margin-top: .7rem; font-size: 1.02rem; line-height: 1.65; color: #4b5563; }

    /* ---- 1. what do you need ---- */
    .fx-need-sec { padding: 3.5rem 0 4rem; background: #fbfcf9; }
    .fx-needs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; margin-top: 2rem; }
    @media (min-width: 900px) { .fx-needs { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .fx-need { display: flex; flex-direction: column; align-items: flex-start; gap: .35rem; padding: .9rem .85rem; border-radius: 1.1rem; text-align: left; background: #fff;
        border: 1.5px solid #e1ead6; color: #14210c; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .fx-need:hover { transform: translateY(-2px); border-color: #b9d39c; }
    .fx-need i { font-style: normal; font-size: 1.5rem; line-height: 1; }
    .fx-need b { font-size: .92rem; font-weight: 800; line-height: 1.25; }
    .fx-need[aria-selected="true"] { border-color: #2d5016; background: linear-gradient(150deg, #2d5016, #3f6d22); color: #fff; box-shadow: 0 18px 34px -22px rgb(45 80 22 / .9); }
    /* A phone reads the promise first; the screen follows, smaller. */
    @media (max-width: 899.98px) { .fx-pshot { order: 2; width: min(12rem, 56vw); } }
    .fx-panel { scroll-margin-top: 5rem; margin-top: 1.2rem; border-radius: 1.6rem; background: #fff; border: 1px solid #e1ead6; box-shadow: 0 30px 60px -48px rgb(20 33 12 / .7); overflow: hidden; }
    .fx-panel[hidden] { display: none; }
    .fx-panel.is-in { animation: fxPanel .45s cubic-bezier(.22,1,.36,1); }
    @keyframes fxPanel { from { opacity: 0; transform: translateY(10px); } }
    .fx-panel-in { display: grid; gap: 1.5rem; padding: 1.3rem; }
    @media (min-width: 900px) { .fx-panel-in { grid-template-columns: 15rem minmax(0, 1fr); gap: 2.2rem; padding: 2rem 2.2rem; align-items: start; } }
    .fx-pshot { position: relative; justify-self: center; width: min(14rem, 62vw); padding: .45rem; border: 0; border-radius: 2.1rem; background: #11180c; cursor: pointer;
        box-shadow: 0 30px 50px -28px rgb(0 0 0 / .7); }
    .fx-pshot img { display: block; width: 100%; aspect-ratio: 540 / 1170; object-fit: cover; object-position: top; border-radius: 1.7rem; background: #fff; }
    .fx-pshot .fx-playbtn { position: absolute; left: 50%; bottom: 1.2rem; transform: translateX(-50%); }
    .fx-playbtn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem .5rem .6rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #1a1a1a;
        background: #f5c518; box-shadow: 0 0 0 4px rgb(245 197 24 / .25), 0 10px 20px -10px rgb(0 0 0 / .5); white-space: nowrap; }
    .fx-playbtn svg { width: .95rem; height: .95rem; }
    .fx-pain { font-size: .86rem; font-weight: 700; color: #8a6400; }
    .fx-promise { margin-top: .35rem; font-family: var(--font-heading); font-size: clamp(1.3rem, 3vw, 1.75rem); font-weight: 800; line-height: 1.2; color: #14210c; text-wrap: balance; }
    .fx-gains { display: grid; gap: .5rem; margin-top: 1rem; }
    .fx-gains li { position: relative; padding-left: 1.75rem; font-size: .96rem; line-height: 1.55; color: #374151; }
    .fx-gains li::before { content: ''; position: absolute; left: 0; top: .2rem; width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #3f8f3a
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center / .75rem no-repeat; }
    .fx-tchips { display: grid; gap: .5rem; margin-top: 1.2rem; }
    @media (min-width: 640px) { .fx-tchips { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .fx-tchip { display: flex; align-items: center; gap: .65rem; padding: .6rem .7rem; border-radius: .95rem; background: #f6faf1; border: 1px solid #e5eedc; text-decoration: none; color: inherit;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    a.fx-tchip:hover, button.fx-tchip:hover { border-color: #b9d39c; background: #eef6e6; }
    button.fx-tchip { width: 100%; text-align: left; cursor: pointer; }
    .fx-tchip img { flex: none; width: 2.2rem; height: 2.2rem; object-fit: contain; padding: .25rem; border-radius: .7rem; background: #fff; box-shadow: 0 0 0 1px #e1ead6; }
    .fx-tchip img.is-face { padding: 0; object-fit: cover; }
    .fx-tchip span { min-width: 0; }
    .fx-tchip b { display: block; font-size: .88rem; font-weight: 800; color: #14210c; line-height: 1.25; }
    .fx-tchip small { display: block; font-size: .78rem; line-height: 1.35; color: #6b7280; }
    .fx-tier { display: inline-block; margin-left: .3rem; padding: .05rem .4rem; border-radius: 999px; font-size: .62rem; font-weight: 900; letter-spacing: .03em; vertical-align: .12em; white-space: nowrap; }
    .fx-tier.is-libre { color: #2f5219; background: #dff0cf; } .fx-tier.is-libreAnee { color: #5b4300; background: #fde68a; }
    .fx-tier.is-solo { color: #fff; background: #4a7c2a; } .fx-tier.is-owner { color: #fff; background: #2d5016; }
    .fx-go { display: flex; flex-wrap: wrap; align-items: center; gap: .7rem 1rem; margin-top: 1.4rem; }
    .fx-go .btn svg { width: 1.1rem; height: 1.1rem; }
    .fx-go small { font-size: .82rem; color: #6b7280; }

    /* ---- 2. the season in four stages ---- */
    .fx-season-sec { padding: 4rem 0; background: #fff; }
    .fx-stages { position: relative; display: grid; gap: 1.4rem; margin-top: 2.4rem; }
    .fx-stage { position: relative; display: grid; gap: 1.1rem; padding: 1.1rem; border-radius: 1.5rem; background: #fbfcf9; border: 1px solid #e5eedc; scroll-margin-top: 5rem; }
    @media (min-width: 900px) { .fx-stage { grid-template-columns: 20rem minmax(0, 1fr); gap: 1.8rem; padding: 1.4rem; } }
    .fx-anchor { position: absolute; top: -5.5rem; left: 0; width: 1px; height: 1px; }
    .fx-stage-pic { position: relative; overflow: hidden; border-radius: 1.1rem; aspect-ratio: 16 / 9; background: #1d3310; }
    @media (min-width: 900px) { .fx-stage-pic { aspect-ratio: auto; min-height: 15rem; } }
    .fx-stage-pic img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .fx-stage:hover .fx-stage-pic img { transform: scale(1.04); }
    .fx-stage-pic::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 35%, rgb(10 20 6 / .75)); }
    .fx-stage-tag { position: absolute; left: .9rem; bottom: .8rem; z-index: 1; display: inline-flex; align-items: center; gap: .5rem; color: #fff; font-weight: 800; }
    .fx-stage-tag b { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; font-size: .95rem; color: #1f1500; background: #f5c518; }
    .fx-stage h3 { font-family: var(--font-heading); font-size: clamp(1.3rem, 3vw, 1.65rem); font-weight: 800; color: #14210c; }
    .fx-stage-p { margin-top: .35rem; font-size: .95rem; line-height: 1.6; color: #4b5563; }
    .fx-top { display: grid; gap: .5rem; margin-top: 1rem; }
    @media (min-width: 640px) { .fx-top { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .fx-more { margin-top: .9rem; border-radius: 1rem; border: 1px dashed #c9dcb4; background: #fff; }
    .fx-more summary { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .75rem .9rem; cursor: pointer; list-style: none; font-size: .9rem; font-weight: 800; color: #2f5219; }
    .fx-more summary::-webkit-details-marker { display: none; }
    .fx-more summary i { font-style: normal; display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #eaf4df; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fx-more[open] summary i { transform: rotate(45deg); background: #f5c518; }
    .fx-rows { display: grid; gap: .1rem; padding: 0 .5rem .6rem; }
    @media (min-width: 640px) { .fx-rows { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 1rem; } }
    .fx-row { display: flex; align-items: center; gap: .6rem; padding: .5rem .4rem; border-radius: .7rem; }
    .fx-row img { flex: none; width: 1.8rem; height: 1.8rem; object-fit: contain; }
    .fx-row img.is-face { border-radius: 999px; object-fit: cover; }
    .fx-row span { flex: 1; min-width: 0; }
    .fx-row b { display: block; font-size: .86rem; font-weight: 800; color: #14210c; line-height: 1.25; }
    .fx-row small { display: block; font-size: .77rem; line-height: 1.35; color: #6b7280; }
    .fx-row-acts { flex: none; display: flex; gap: .3rem; }
    .fx-row-acts a, .fx-row-acts button { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; border: 1px solid #e1ead6; background: #fff; color: #2f5219;
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fx-row-acts a:hover, .fx-row-acts button:hover { background: #eef6e6; }
    .fx-row-acts button { color: #1a1a1a; background: #f5c518; border-color: #f5c518; }
    .fx-row-acts svg { width: .85rem; height: .85rem; }

    /* ---- Anee Auto ---- */
    .fx-auto { display: grid; gap: 1.2rem; align-items: center; margin-top: 1.4rem; padding: 1.3rem; border-radius: 1.5rem; background: linear-gradient(150deg, #fffaf0, #fdf2cf); border: 1px solid #f0dc95; }
    @media (min-width: 900px) { .fx-auto { grid-template-columns: auto minmax(0, 1fr) auto; padding: 1.5rem 1.8rem; } }
    .fx-auto img { width: 4rem; height: 4rem; border-radius: 1.2rem; object-fit: cover; box-shadow: 0 0 0 3px #f5c518; }
    .fx-auto h3 { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: #14210c; }
    .fx-auto p { margin-top: .3rem; font-size: .95rem; line-height: 1.6; color: #5b4a1a; }

    /* ---- plans ---- */
    .fx-plans-sec { padding: 4rem 0; background: #f6faf1; }
    .fx-plans { display: grid; gap: .8rem; margin-top: 2rem; }
    @media (min-width: 640px) { .fx-plans { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .fx-plans { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .fx-plan { padding: 1.2rem 1.15rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e1ead6; }
    .fx-plan.is-free { border: 2px solid #f5c518; }
    .fx-plan h3 { font-weight: 800; color: #14210c; }
    .fx-plan p.fx-price { margin-top: .3rem; } .fx-plan p.fx-price b { font-family: var(--font-heading); font-size: 1.7rem; font-weight: 800; color: #2d5016; } .fx-plan p.fx-price span { margin-left: .3rem; font-size: .82rem; font-weight: 700; color: #6b7280; }
    .fx-plan ul { display: grid; gap: .35rem; margin-top: .7rem; }
    .fx-plan li { position: relative; padding-left: 1.1rem; font-size: .84rem; line-height: 1.45; color: #4b5563; }
    .fx-plan li::before { content: ''; position: absolute; left: 0; top: .5rem; width: .45rem; height: .45rem; border-radius: 999px; background: #86b556; }

    /* ---- the film of a tool, on a phone ---- */
    .fx-modal { position: fixed; inset: 0; z-index: 200; display: grid; place-items: center; padding: 1rem; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .fx-modal.is-on { opacity: 1; }
    .fx-modal[hidden] { display: none; }
    .fx-modal-veil { position: absolute; inset: 0; background: rgb(8 14 5 / .72); backdrop-filter: blur(4px); }
    .fx-modal-box { position: relative; display: grid; gap: 1.4rem; align-items: center; width: min(46rem, 100%); max-height: calc(100vh - 2rem); overflow: auto; padding: 1.4rem;
        border-radius: 1.6rem; background: #fff; box-shadow: 0 40px 90px -30px rgb(0 0 0 / .7); transform: translateY(14px) scale(.98); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .fx-modal.is-on .fx-modal-box { transform: none; }
    @media (min-width: 720px) { .fx-modal-box { grid-template-columns: auto minmax(0, 1fr); padding: 1.8rem; } }
    .fx-modal-phone { justify-self: center; width: min(15rem, 62vw); aspect-ratio: 540 / 1170; padding: .45rem; border-radius: 2.2rem; background: #11180c; }
    @media (max-width: 719.98px) { .fx-modal-phone { width: min(12rem, 50vw); } }
    .fx-modal-phone video { width: 100%; height: 100%; display: block; object-fit: cover; border-radius: 1.8rem; background: #fff; }
    .fx-modal-where { font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
    .fx-modal-words h3 { margin-top: .3rem; font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: #14210c; }
    .fx-modal-words > p { margin-top: .6rem; color: #4b5563; line-height: 1.65; }
    .fx-modal-help { margin-top: .9rem; padding: .8rem .9rem; border-radius: 1rem; background: #fffbea; box-shadow: inset 0 0 0 1px #f3e2a6; }
    .fx-modal-help > b { display: block; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #8a6400; }
    .fx-modal-help ul { display: grid; gap: .35rem; margin: .5rem 0 0; padding: 0; list-style: none; }
    .fx-modal-help li { position: relative; padding-left: .95rem; font-size: .84rem; line-height: 1.5; color: #4b5563; }
    .fx-modal-help li::before { content: ''; position: absolute; left: 0; top: .55rem; width: .4rem; height: .4rem; border-radius: 999px; background: #f5c518; }
    .fx-modal-nav { margin-top: 1.2rem; display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }
    .fx-modal-nav button.fx-step { width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center; border: 1px solid #e1ead6; background: #fff; color: #2f5219; }
    .fx-modal-nav button.fx-step svg { width: 1.1rem; height: 1.1rem; }
    .fx-modal-nav a.fx-guide { font-size: .86rem; font-weight: 800; color: #2f5219; }
    .fx-x { position: absolute; right: .8rem; top: .8rem; z-index: 2; width: 2.3rem; height: 2.3rem; border-radius: 999px; display: grid; place-items: center; color: #374151; background: #f3f4f6; }
    .fx-x svg { width: 1.1rem; height: 1.1rem; }

    @media (prefers-reduced-motion: reduce) {
        .fx-phones .ph-frame { animation: none !important; }
        .fx-need, .fx-tchip, .fx-stage-pic img, .fx-more summary i, .fx-modal, .fx-modal-box { transition: none !important; }
        .fx-panel.is-in { animation: none; }
    }
</style>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="fx-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-12 sm:pt-16 sm:pb-16 fx-hero-in">
            <div class="animate-fade-up">
                <span class="fx-kick"><i aria-hidden="true"></i>Every feature</span>
                <h1 class="fx-h1">The Tools That Grow a <em>Bigger, Safer Harvest.</em></h1>
                <p class="fx-lede">
                    Tell us what you need help with, and we will show you the tools that do it: from planning the season to the last sack,
                    with Anee, your smart farm technician, beside you. Every screen on this page is the real app.
                </p>
                <div class="fx-stats">
                    <div><b>{{ $fCount }}</b><small>tools in one app</small></div>
                    <div><b>4</b><small>stages of the season</small></div>
                    <div><b>{{ $ph ? '85' : '100' }}</b><small>crops it knows by stage</small></div>
                </div>
                <div class="fx-cta">
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                    <a href="#need" class="btn btn-lg btn-on-dark">What do you need?</a>
                </div>
            </div>
            <div class="fx-phones" aria-hidden="true">
                <span class="ph-frame p2"><img src="{{ $HW::shot('pestFinder')[0] ?? asset('images/site/app/board.png') }}" alt="" loading="lazy" width="540" height="1170"></span>
                <span class="ph-frame p3"><img src="{{ $HW::shot('profit')[0] ?? asset('images/site/app/board.png') }}" alt="" loading="lazy" width="540" height="1170"></span>
                <span class="ph-frame p1"><img src="{{ asset('images/site/app/sky.webp') }}" alt="" width="780" height="1520"></span>
            </div>
        </div>
    </section>

    {{-- ================= 1. WHAT DO YOU NEED ================= --}}
    <section class="fx-need-sec fx-sec" id="need">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="fx-head reveal">
                <p class="fx-k">Start with your farm</p>
                <h2 class="fx-h2">What Do You <em>Need Help With?</em></h2>
                <p class="fx-p">Pick one. We will show you what changes on your farm and the tools that do it.</p>
            </div>
            <div class="fx-needs" role="tablist" aria-label="What you need help with">
                @foreach ($needs as $i => [$key, $em, $title])
                    <button type="button" class="fx-need reveal" role="tab" id="need-tab-{{ $key }}" aria-controls="need-{{ $key }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? '0' : '-1' }}" data-need="{{ $key }}" style="--reveal-delay: {{ $i * 0.05 }}s">
                        <i aria-hidden="true">{{ $em }}</i><b>{{ $title }}</b>
                    </button>
                @endforeach
            </div>
            @foreach ($needs as $i => [$key, $em, $title, $pain, $promise, $gains, $keys])
                @php
                    $keys = array_values(array_filter($keys, fn ($k) => $tool->has($k)));
                    $lead = $keys[0] ?? null;
                    $leadShot = $lead ? $HW::shot($lead) : null;
                    $leadFilm = $lead ? $filmAttrs($lead, $title) : '';
                    // What it costs, said once: one plan for all of them, or "each shows its plan" when they differ.
                    $tiers = collect($keys)->map(fn ($k) => $tool[$k]['plan']['tier'] ?? 'libre')->unique()->values();
                    $costSay = $tiers->count() === 1
                        ? ($tiers[0] === 'libre' ? 'Free on Libre, no card.' : 'Free to register. These come with ' . ($planWord[$tiers[0]] ?? '') . ' at ' . $money($tiers[0]) . ' a month.')
                        : ($tiers->contains('libre') ? 'Free to register, and some of these are free on Libre. Each shows the plan it comes with.' : 'Free to register. Each shows the plan it comes with.');
                @endphp
                <div class="fx-panel" id="need-{{ $key }}" role="tabpanel" aria-labelledby="need-tab-{{ $key }}" @if ($i !== 0) hidden @endif>
                    <div class="fx-panel-in">
                        @if ($leadShot)
                            @if ($leadFilm)
                                <button type="button" class="fx-pshot" {!! $leadFilm !!} aria-label="Watch {{ $tool[$lead]['name'] }} at work">
                                    <img src="{{ $leadShot[0] }}" @if ($leadShot[1]) srcset="{{ $leadShot[1] }}" sizes="14rem" @endif alt="{{ $tool[$lead]['name'] }} on a phone" loading="lazy" width="540" height="1170">
                                    <span class="fx-playbtn">{!! $play !!}Watch it work</span>
                                </button>
                            @else
                                <span class="fx-pshot"><img src="{{ $leadShot[0] }}" alt="{{ $tool[$lead]['name'] }} on a phone" loading="lazy" width="540" height="1170"></span>
                            @endif
                        @endif
                        <div>
                            <p class="fx-pain">{{ $pain }}</p>
                            <h3 class="fx-promise">{{ $promise }}</h3>
                            <ul class="fx-gains">
                                @foreach ($gains as $g)<li>{{ $g }}</li>@endforeach
                            </ul>
                            <div class="fx-tchips">
                                @foreach ($keys as $k)
                                    @php $it = $tool[$k]; $fa = $filmAttrs($k, $title); $g = $guideOf($it); @endphp
                                    @if ($fa)
                                        <button type="button" class="fx-tchip" {!! $fa !!}>
                                    @elseif ($g)
                                        <a href="{{ $g }}" class="fx-tchip">
                                    @else
                                        <span class="fx-tchip">
                                    @endif
                                        <img src="{{ asset('images/' . $it['icon']) }}" alt="" class="{{ str_starts_with($it['icon'], 'anee/') ? 'is-face' : '' }}" loading="lazy" width="36" height="36">
                                        <span><b>{{ $it['name'] }}<i class="fx-tier is-{{ $it['plan']['tier'] }}" style="font-style:normal">{{ $planWord[$it['plan']['tier']] ?? '' }}</i></b><small>{{ $it['short'] }}</small></span>
                                    @if ($fa)</button>@elseif ($g)</a>@else</span>@endif
                                @endforeach
                            </div>
                            <div class="fx-go">
                                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Register free and start {!! $arrow !!}</a>
                                <small>{{ $costSay }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= 2. THE SEASON IN FOUR STAGES ================= --}}
    <section class="fx-season-sec fx-sec" id="all">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="fx-head reveal">
                <p class="fx-k">Every tool, in its place</p>
                <h2 class="fx-h2">Your Whole Season in <em>Four Stages</em></h2>
                <p class="fx-p">From land preparation to the last sack. Each stage shows the tools farmers use most; tap to see every one.</p>
            </div>
            <div class="fx-stages">
                @foreach ($stages as $sk => [$name, $short, $promise, $img, $img480, $alt, $top])
                    @php
                        $top = array_values(array_filter($top, fn ($k) => in_array($k, $inStage[$sk], true)));
                        $rest = array_values(array_diff($inStage[$sk], $top));
                        $n = count($inStage[$sk]);
                    @endphp
                    <article class="fx-stage reveal" id="stage-{{ $sk }}">
                        {{-- The old eight steps' addresses (/features#step-plan and the like, from the About page and old links) land on their stage. --}}
                        @foreach (array_keys(array_filter($stageOf, fn ($to) => $to === $sk)) as $old)<span id="step-{{ $old }}" class="fx-anchor" aria-hidden="true"></span>@endforeach
                        <figure class="fx-stage-pic">
                            <img src="{{ asset('images/site/' . $img) }}" @if ($img480) srcset="{{ asset('images/site/' . $img480) }} 480w, {{ asset('images/site/' . $img) }} 800w" sizes="(min-width: 900px) 20rem, 92vw" @endif alt="{{ $alt }}" loading="lazy">
                            <span class="fx-stage-tag"><b>{{ $loop->iteration }}</b>{{ $short }}</span>
                        </figure>
                        <div>
                            <h3>{{ $name }}</h3>
                            <p class="fx-stage-p">{{ $promise }}</p>
                            <div class="fx-top">
                                @foreach ($top as $k)
                                    @php $it = $tool[$k]; $fa = $filmAttrs($k, $name); $g = $guideOf($it); @endphp
                                    @if ($fa)<button type="button" class="fx-tchip" {!! $fa !!}>@elseif ($g)<a href="{{ $g }}" class="fx-tchip">@else<span class="fx-tchip">@endif
                                        <img src="{{ asset('images/' . $it['icon']) }}" alt="" class="{{ str_starts_with($it['icon'], 'anee/') ? 'is-face' : '' }}" loading="lazy" width="36" height="36">
                                        <span><b>{{ $it['name'] }}</b><small>{{ $it['short'] }}</small></span>
                                    @if ($fa)</button>@elseif ($g)</a>@else</span>@endif
                                @endforeach
                            </div>
                            @if ($rest)
                                <details class="fx-more">
                                    <summary>See all {{ $n }} tools for {{ strtolower($name) }}<i aria-hidden="true">+</i></summary>
                                    <div class="fx-rows">
                                        @foreach ($rest as $k)
                                            @php $it = $tool[$k]; $fa = $filmAttrs($k, $name); $g = $guideOf($it); @endphp
                                            <div class="fx-row">
                                                <img src="{{ asset('images/' . $it['icon']) }}" alt="" class="{{ str_starts_with($it['icon'], 'anee/') ? 'is-face' : '' }}" loading="lazy" width="32" height="32">
                                                <span><b>{{ $it['name'] }}<i class="fx-tier is-{{ $it['plan']['tier'] }}" style="font-style:normal">{{ $planWord[$it['plan']['tier']] ?? '' }}</i></b><small>{{ $it['short'] }}</small></span>
                                                <span class="fx-row-acts">
                                                    @if ($g)<a href="{{ $g }}" aria-label="Read the guide to {{ $it['name'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>@endif
                                                    @if ($fa)<button type="button" {!! $fa !!} aria-label="Watch {{ $it['name'] }} at work">{!! $play !!}</button>@endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Or let Anee plan it all. --}}
            @if ($auto)
                <div class="fx-auto reveal" id="step-auto" style="scroll-margin-top:6rem">
                    <img src="{{ asset('images/' . $auto['icon']) }}" alt="" loading="lazy" width="64" height="64">
                    <div>
                        <h3>Rather not plan it yourself? Let Anee do it.</h3>
                        <p>{{ $auto['what'] }}</p>
                    </div>
                    @php $af = $filmAttrs('auto', 'The easiest way'); @endphp
                    @if ($af)<button type="button" class="fx-playbtn" {!! $af !!}>{!! $play !!}Watch Anee Auto</button>@endif
                </div>
            @endif
        </div>
    </section>

    {{-- The film of a tool, on a phone, with the tool before and after. --}}
    <div class="fx-modal" id="fxModal" hidden role="dialog" aria-modal="true" aria-labelledby="fxModalName">
        <div class="fx-modal-veil" data-close></div>
        <div class="fx-modal-box">
            <button type="button" class="fx-x" data-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            <div class="fx-modal-phone"><video id="fxModalVideo" muted playsinline loop preload="none"></video></div>
            <div class="fx-modal-words">
                <p class="fx-modal-where" id="fxModalWhere"></p>
                <h3 id="fxModalName"></h3>
                <p id="fxModalWhat"></p>
                <div class="fx-modal-help" id="fxModalHelp" hidden><b>How it helps your crops and your farm</b><ul></ul></div>
                <div class="fx-modal-nav">
                    <button type="button" class="fx-step" data-film-step="-1" aria-label="The tool before"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg></button>
                    <button type="button" class="fx-step" data-film-step="1" aria-label="The next tool"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg></button>
                    <a href="#" id="fxModalGuide" class="fx-guide">Read the guide</a>
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-sm" style="margin-left:auto">Register free</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= ANEE ================= --}}
    <section class="anee-band fx-sec" id="anee">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400 reveal">And through all of it</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance reveal">Anee, the smart farm technician who knows your farm</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto reveal">
                She reads your schedules, stages and weather before answering. Ask in {{ $ph ? 'Tagalog or English' : 'plain English' }},
                send a photo of the problem, run When to Plant and What to Plant for your town, read your field from space,
                check your fertilizer plan, or have her write the whole season's report. Anee runs on credits, so you pay only for what you ask.
            </p>
            <div class="mt-8 reveal">
                <a href="{{ url('/ask-anee') }}" class="btn btn-accent btn-lg">Ask Anee one question free</a>
            </div>
        </div>
    </section>

    {{-- ================= PLANS IN ONE LOOK ================= --}}
    <section class="fx-plans-sec fx-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="fx-head reveal">
                <p class="fx-k">Plans in {{ $ph ? 'pesos' : 'one look' }}</p>
                <h2 class="fx-h2">Start Free. <em>Add Only What You Need.</em></h2>
                <p class="fx-p">Every tool above says which plan it comes with. Most of the season runs on Libre, free forever.</p>
            </div>
            <div class="fx-plans">
                @foreach ($plans as $i => $p)
                    <article class="fx-plan reveal {{ $p['key'] === 'libre' ? 'is-free' : '' }}" style="--reveal-delay: {{ $i * 0.06 }}s">
                        <h3>{{ $p['name'] }}</h3>
                        <p class="fx-price"><b>{{ $p['price'] }}</b><span>{{ $p['per'] }}</span></p>
                        <ul>@foreach ($p['points'] as $pt)<li>{{ $pt }}</li>@endforeach</ul>
                    </article>
                @endforeach
            </div>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3 reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Register free</a>
                <a href="{{ route('pricing.compare') }}" class="btn btn-outline btn-lg">Compare every plan</a>
            </div>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Bring your next season here</h2>
            <p class="mt-4 text-gray-600">Set up your first cropping schedule in minutes: your lot, its crop and the whole calendar. Libre is free forever, and you can add Anee the day you need her.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Create your free account</a>
                <a href="{{ route('tutorial') }}" class="btn btn-outline btn-lg">Watch the tutorial</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
(() => {
    /* The needs: one panel at a time, swapped with a short rise; arrow keys move between them. */
    const tabs = [...document.querySelectorAll('[data-need]')];
    const pick = (tab, focus) => {
        tabs.forEach((t) => {
            const on = t === tab;
            t.setAttribute('aria-selected', String(on));
            t.tabIndex = on ? 0 : -1;
            const panel = document.getElementById('need-' + t.dataset.need);
            if (!panel) return;
            if (on) { panel.hidden = false; panel.classList.remove('is-in'); void panel.offsetWidth; panel.classList.add('is-in'); }
            else panel.hidden = true;
        });
        if (focus) tab.focus();
    };
    tabs.forEach((t, i) => {
        t.addEventListener('click', () => {
            pick(t);
            // On a phone the panel sits under six buttons: bring it into view.
            const panel = document.getElementById('need-' + t.dataset.need);
            if (panel && matchMedia('(max-width: 899.98px)').matches) panel.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        });
        t.addEventListener('keydown', (e) => {
            const d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
            if (d) { e.preventDefault(); pick(tabs[(i + d + tabs.length) % tabs.length], true); }
        });
    });

    /* Every tool's film on a phone, with the tool before and after (one entry per tool, in reading order). */
    const modal = document.getElementById('fxModal');
    const seen = new Set();
    const films = [...document.querySelectorAll('[data-film]')].filter((b) => !seen.has(b.dataset.key) && seen.add(b.dataset.key));
    if (!modal || !films.length) return;
    const v = document.getElementById('fxModalVideo');
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let at = 0, last = null;
    const show = (i) => {
        at = (i + films.length) % films.length;
        const b = films[at];
        document.getElementById('fxModalName').textContent = b.dataset.name;
        document.getElementById('fxModalWhat').textContent = b.dataset.what;
        document.getElementById('fxModalWhere').textContent = b.dataset.where;
        const help = document.getElementById('fxModalHelp');
        let helps = [];
        try { helps = JSON.parse(b.dataset.helps || '[]'); } catch (e) { helps = []; }
        help.hidden = !helps.length;
        help.querySelector('ul').innerHTML = helps.map((h) => '<li>' + h + '</li>').join('');
        const guide = document.getElementById('fxModalGuide');
        guide.hidden = !b.dataset.guide;
        if (b.dataset.guide) guide.href = b.dataset.guide;
        v.poster = b.dataset.poster || '';
        v.src = b.dataset.film;
        v.play().catch(() => {});
    };
    const open = (key) => {
        last = document.activeElement;
        modal.hidden = false;
        requestAnimationFrame(() => modal.classList.add('is-on'));
        document.documentElement.style.overflow = 'hidden';
        show(Math.max(0, films.findIndex((b) => b.dataset.key === key)));
        modal.querySelector('.fx-x').focus();
    };
    const close = () => {
        modal.classList.remove('is-on');
        v.pause(); v.removeAttribute('src'); v.load();
        document.documentElement.style.overflow = '';
        setTimeout(() => { modal.hidden = true; }, calm ? 0 : 280);
        last?.focus?.();
    };
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-film]');
        if (b && !modal.contains(b)) { e.preventDefault(); open(b.dataset.key); }
    });
    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close]')) close();
        const s = e.target.closest('[data-film-step]');
        if (s) show(at + Number(s.dataset.filmStep));
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
