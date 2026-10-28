@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

{{-- ANEE VS NOW (2026-10-28, the owner: "below how it works, add a new page in the public site called Anee vs Now then
     create an interactive page why should you use anee.io and why purchasing subscription, credits, or anee auto is a
     great investment. have a good comparison and make the page interactive, animated, great, with animated graphs
     ... make it persuasive in writing ... better on mobile as well").

     The case in the order a farmer weighs it: the farm now beside the farm with Anee (a picture to drag across), the
     nine jobs of a season done both ways (one switch flips them all; on a phone each card turns over), what guessing
     can take from a hectare beside a year of a plan (bars that grow with the hectares), what each plan costs counted
     what credits buy, a morning with Anee Auto, and the way in. (A palay calculator, each plan's season counted in
     sacks, came out the same day: the owner found it confusing.)

     Honest numbers only: the losses are the site's "up to" figures (published Philippine rice loss estimates, on a
     hectare grossing 60,000 to 100,000 pesos), the prices are the plans' and packs' own, and nothing promises a
     harvest. Anee Auto's price is never
     shown here. Plain words, no dashes, never "AI farm technician". --}}
@php
    $ph = \App\Support\Region::ph();
    $signup = route('signup');
    $pricing = route('pricing');
    $face = asset('images/anee/avatar-160.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    $cross = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg>';
    $flip = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0113.7-5.7L20 8.6M20 4v4.6h-4.6M20 12a8 8 0 01-13.7 5.7L4 15.4M4 20v-4.6h4.6"/></svg>';
    $money = fn (float $v) => \App\Support\Region::priceTag($v);
    $sym = trim(preg_replace('/[\d.,\s]/u', '', \App\Support\Region::priceTag(0))) ?: '₱';

    // The plans with a price, as config/tiers has them; the first plan is the free one.
    $tiers = \Illuminate\Support\Arr::except(config('tiers'), ['admin']);
    $freeTier = array_key_first($tiers);
    $plans = collect(array_keys($tiers))->filter(fn ($k) => $k !== $freeTier)->map(fn ($k) => [
        'key' => $k, 'name' => $tiers[$k]['name'],
        'month' => (float) \App\Support\Region::tierPrice($k, 'month'), 'year' => (float) \App\Support\Region::tierPrice($k, 'year'),
        'credits' => (int) ($tiers[$k]['creditsMonthly'] ?? 0),
    ])->values()->all();
    $freeName = $tiers[$freeTier]['name'];

    // Every tool How It Works lists, and the free ones.
    $tools = collect(\App\Support\HowItWorks::stages())->flatMap(fn ($st) => $st['items'])->keyBy('key');
    $toolCount = $tools->count();
    $freeCount = $tools->filter(fn ($t) => ($t['plan']['tier'] ?? null) === $freeTier)->count();
    $toolLink = function (string $key) use ($tools) {
        $t = $tools[$key] ?? null;
        if (! $t) {
            return null;
        }
        $href = ! empty($t['page']) ? url('/features/' . $t['page']) : null;

        return ['name' => $t['name'], 'href' => $href, 'icon' => asset('images/' . $t['icon']), 'face' => str_starts_with($t['icon'], 'anee/')];
    };

    // What guessing can take from a hectare: the site's own "up to" figures (the ads page and the homepage say the same).
    $leaks = collect(\App\Support\LandingPage::content()['losses']['items'] ?? [])->map(fn ($l) => [
        'n' => max(0, min(100, (int) ($l['n'] ?? 0))), 'title' => (string) ($l['title'] ?? ''),
    ])->filter(fn ($l) => $l['n'] > 0 && $l['title'] !== '')->values();
    $leakBy = fn (string $word) => $leaks->first(fn ($l) => str_contains(mb_strtolower($l['title']), $word));

    // Credit packs as the app sells them, and what an answer costs.
    $packs = [];
    try {
        $packs = \App\Models\AiCreditPack::active()->where('isActive', 1)->orderBy('sortOrder')->get()
            ->map(fn ($p) => ['credits' => (int) $p->credits, 'price' => (float) \App\Support\Region::packPrice($p)])
            ->filter(fn ($p) => $p['credits'] > 0)->values()->all();
    } catch (\Throwable $e) {
        $packs = [];
    }
    $perAnswer = 4;
    $perPhoto = 3;
    try {
        $perPhoto = (int) round((float) (\App\Models\AiSetting::query()->value('creditsPerImage') ?? 3)) ?: 3;
    } catch (\Throwable $e) {
        // The usual price.
    }
    $aneePlan = $plans[0] ?? null;

    // Anee's credits for a year, estimated for one lot and two seasons: When to Plant, Variety Ranking and a Crop
    // Protocol each season (their own list prices), and about two questions a week for a 17 week season, half with a
    // photo. A plan's monthly credits come off it; the rest is priced at the biggest pack's rate.
    $kindCredits = ['When to Plant' => 'wtp', 'Variety Ranking' => 'variety', 'a Crop Protocol' => 'protocol'];
    $seasonCredits = 0;
    foreach ($kindCredits as $kc) {
        try {
            $seasonCredits += (int) \App\Support\AiPricing::credits($kc, []);
        } catch (\Throwable $e) {
            $seasonCredits += ['wtp' => 100, 'variety' => 110, 'protocol' => 190][$kc];
        }
    }
    $seasonAsks = 2 * 17;
    $seasonCredits += intdiv($seasonAsks, 2) * $perAnswer + ($seasonAsks - intdiv($seasonAsks, 2)) * ($perAnswer + $perPhoto);
    $yearCredits = (int) (ceil($seasonCredits * 2 / 10) * 10);
    $perCredit = collect($packs)->map(fn ($k) => $k['price'] / max(1, $k['credits']))->min() ?: 0.87;
    $plans = array_map(function ($p) use ($yearCredits, $perCredit) {
        $p['buyCredits'] = max(0, $yearCredits - $p['credits'] * 12);
        $p['creditCost'] = (float) (round($p['buyCredits'] * $perCredit / 10) * 10);

        return $p;
    }, $plans);
    $planJs = array_map(fn ($p) => ['name' => $p['name'], 'month' => $p['month'], 'year' => $p['year'], 'included' => $p['credits'] * 12, 'buy' => $p['buyCredits'], 'creditCost' => $p['creditCost']], $plans);

    // The nine jobs of a season, now and with Anee.
    // The nine jobs of a season, now and with Anee (2026-10-28, the owner: "some boxes ... have huge blank spaces ...
    // put something in there to balance it out"): each side also says it out loud, the farmer's thought on one, Anee's
    // answer on the other, and every "now" names what it can cost (the site's "up to" figure where there is one).
    $jobs = [
        ['e' => '📅', 'job' => 'Knowing when to plant', 'now' => 'You plant when the neighbors do, or on the date that worked last year.', 'leak' => $leakBy('window'), 'cost' => '',
            'thinks' => 'The neighbors planted already. We should too.',
            'with' => 'When to Plant reads twenty years of your town\'s weather and the El Niño or La Niña outlook, then ranks the weeks for the safest start and the best price at harvest.',
            'says' => 'Plant in the second week of next month. It misses the worst of the storms.', 'tools' => ['when']],
        ['e' => '🌾', 'job' => 'Choosing the seed', 'now' => 'You buy what the store has, on the seller\'s word.', 'leak' => null, 'cost' => 'A seed that doesn\'t fit your season can cost the whole harvest.',
            'thinks' => 'Whatever the store has will do.',
            'with' => 'Every registered variety of your crop, ranked by Anee for your area and for what matters most to you: yield, survival or a quick harvest.',
            'says' => 'For your lot, this one ranks first. It stands up to flooding.', 'tools' => ['variety', 'varietyFinder']],
        // The right fertilizer and how much (2026-10-28, the owner, in place of the hatian: "add something about the
        // correct fertilizer (npk calculator) and amount to use"); its timing is the next job.
        ['e' => '🧮', 'job' => 'The right fertilizer, in the right amount', 'now' => 'You buy the sacks the store suggests and spread what feels right.', 'leak' => null, 'cost' => 'Too little starves the crop. Too much wastes money and can make the palay lodge.',
            'thinks' => 'Three sacks a hectare, like always.',
            'with' => 'NPK Plus adds up every nutrient in the fertilizers you plan to use, checks it against what your crop needs for the harvest you want, and shows what is short, right or too much before you buy a single sack.',
            'says' => 'You have enough nitrogen. Add a sack of 0-0-60 for potassium and skip the extra urea.', 'tools' => ['npk']],
        ['e' => '🧪', 'job' => 'Timing the fertilizer', 'now' => 'The same sacks on the usual days, whatever stage the crop is in.', 'leak' => $leakBy('fertilizer'), 'cost' => '',
            'thinks' => 'Same days as last year.',
            'with' => 'The Crop Protocol puts each sack at the stage the crop takes it up, counted from your own planting day.',
            'says' => 'Hold the second urea until Thursday. That\'s when the crop takes it up.', 'tools' => ['cropProtocol']],
        ['e' => '🐛', 'job' => 'A pest or a disease', 'now' => 'You post a photo in a group and get seven different answers.', 'leak' => $leakBy('wrong solution'), 'cost' => '',
            'thinks' => 'Let me ask the group.',
            'with' => 'Anee reads your photo with your crop\'s stage and weather, asks for the sign that decides it, and names the active ingredient that works.',
            'says' => 'Check the base of the stem. A small hole there means stem borer.', 'tools' => ['chat', 'pestFinder']],
        ['e' => '🌧️', 'job' => 'Rain on spray day', 'now' => 'You spray and hope the rain holds off.', 'leak' => null, 'cost' => 'A spray washed off is paid for twice: the product, the labor and the fuel.',
            'thinks' => 'It looks clear. Let\'s spray.',
            'with' => 'The forecast for your own lot, hour by hour, beside your plan. With Anee Auto the spray moves to a dry morning by itself.',
            'says' => 'Rain comes at 2 PM. Spray before 10 instead.', 'tools' => ['weather', 'auto']],
        ['e' => '💧', 'job' => 'Water at the right stage', 'now' => 'The field gets water when the canal has it, whatever the crop needs that week.', 'leak' => $leakBy('water'), 'cost' => '',
            'thinks' => 'The canal has water today, so the field gets it.',
            'with' => 'The Crop Protocol counts the water each lot needs week by week, so flowering never goes dry and ripening never floods.',
            'says' => 'Flowering starts next week. Keep 5 cm of water in Lot 2.', 'tools' => ['cropProtocol']],
        ['e' => '📒', 'job' => 'Knowing where the money went', 'now' => 'Wages, sacks and sprays live in a notebook and in your memory.', 'leak' => $leakBy('untracked'), 'cost' => '',
            'thinks' => 'I\'ll remember what I spent.',
            'with' => 'Every wage, sack and spray adds up lot by lot, and the season report shows what each sack of harvest cost to grow.',
            'says' => 'Lot 2 is costing more than Lot 1. Most of it is labor.', 'tools' => ['expenses', 'profit']],
        ['e' => '📈', 'job' => 'Selling the harvest', 'now' => 'You sell when the buyer comes, at the buyer\'s price.', 'leak' => null, 'cost' => 'Selling in the glut months takes pesos off every kilo.',
            'thinks' => 'The buyer is here. Let\'s sell.',
            'with' => 'The Market Price Forecast shows the months your crop sells highest, and Benta o Bilad shows whether drying first leaves more money in your hand.',
            'says' => 'Prices usually rise in February. Drying and waiting may pay.', 'tools' => ['market', 'bilad']],
    ];

    // A morning in the season: the farmer alone, and with Anee Auto.
    $morning = [
        ['5:30', 'AM', '🌅', 'You look at the sky and guess whether it will rain today.', 'Today\'s work is already on your phone, read against the forecast, and in each worker\'s email.'],
        ['7:00', 'AM', '☀️', 'A worker cannot come. The weeding waits, and nobody knows until when.', 'You tap Can\'t today. Anee moves the weeding to the best day near it and tells the crew.'],
        ['10:00', 'AM', '🌤️', 'Odd spots on the leaves. You post a photo in a group and wait.', 'You send the photo to Anee. She reads it, gives one answer, and fits the fix into the plan.'],
        ['2:00', 'PM', '🌦️', 'The rain comes an hour after the spray. The money washes off with it.', 'The spray was moved to this morning, so it had dried before the rain.'],
        ['7:00', 'PM', '🌙', 'You try to remember what you spent today, and on whom.', 'The day\'s work and costs are already counted, lot by lot.'],
    ];

    $faqs = [
        ['Is anee.io worth it for a small farm?', 'Yes, and a small farm feels a mistake the most. Libre is free forever with ' . $freeCount . ' of the ' . $toolCount . ' tools. ' . ($aneePlan ? $aneePlan['name'] . ' adds Anee for ' . $money($aneePlan['month']) . ' a month, less than a few kilos of palay. ' : '') . 'One spray saved from the rain pays for many months of it.'],
        ['What if I am not good with phones?', 'If you can send a photo in a chat, you can use anee.io. Ask Anee in Tagalog, Bisaya, Ilocano or English, and every main screen has a short video that shows how it works.'],
        ['Do I have to pay every month?', 'Nothing renews by itself. You pay for a month or a year at a time, and if you stop, your seasons and records stay safe and readable on Libre.'],
        ['Do credits expire?', 'No. Credits stay on your account until you spend them, across seasons. Every question and analysis shows its price in credits before it runs.'],
        ['How much is Anee Auto?', 'Anee Auto is priced per lot for the whole season, and the app shows the price before you start. Its credits cover Anee\'s planning, her daily readings and your questions all season.'],
        ['Will it really increase my harvest?', 'No app can promise a number, because the weather and the market decide part of it. What anee.io takes away is the loss that comes from guessing: late, early or wrong applications and costs nobody tracked.'],
    ];

    $title = 'Anee vs Now: Your Farm Today and Your Farm With anee.io';
    $desc = 'See your season done the way you farm now and with Anee, your smart farm technician: nine jobs side by side, what guessing can cost a hectare beside a year of a plan, and what credits and Anee Auto do for you.';
@endphp

@section('title', $title)
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ route('how.vs') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Anee vs Now: your farm today and your farm with anee.io">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ route('how.vs') }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-phone.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    @php
        $avnSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'WebPage', '@id' => route('how.vs') . '#page', 'url' => route('how.vs'), 'name' => $title, 'description' => $desc, 'inLanguage' => 'en-PH',
                    'primaryImageOfPage' => asset('images/site/photos/palay-phone.jpg')],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'How It Works', 'item' => route('how')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Anee vs Now', 'item' => route('how.vs')],
                ]],
                ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($avnSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
<style>
    /* ================= Anee vs Now ================= */
    .avn { --now: #b42318; --now-soft: #fff4f2; --now-line: #fbd5cf; --anee: #3d6823; --anee-soft: #f1f8e8; --anee-line: #cfe3b8; }

    /* ---- the hero: dark, the words beside a picture you drag across ---- */
    .avn-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff;
        background: radial-gradient(60rem 30rem at 85% 0%, #2c4f17 0%, transparent 60%), radial-gradient(40rem 26rem at 0% 100%, rgb(180 35 24 / .25), transparent 60%), #0f170a; }
    .avn-hero::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgb(255 255 255 / .08) 1px, transparent 1.5px); background-size: 22px 22px; }
    .avn-hero-grid { display: grid; gap: 2.2rem; align-items: center; padding-block: 2rem 3.5rem; }
    @media (min-width: 1024px) { .avn-hero-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 3.5rem; padding-block: 4rem 5rem; } }
    .avn-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 700; color: rgb(255 255 255 / .65); }
    .avn-crumbs a:hover { color: #fff; }
    .avn-crumbs svg { width: .8rem; height: .8rem; }
    .avn-crumbs [aria-current] { color: var(--hp-sun); }
    .avn-h1 { margin-top: 1rem; font-family: var(--font-heading); font-weight: 800; line-height: 1.06; letter-spacing: -.02em; font-size: clamp(2.3rem, 6vw, 4rem); text-wrap: balance; }
    .avn-h1 .is-now { color: #fca5a5; }
    .avn-h1 .is-vs { display: inline-block; margin: 0 .2em; padding: .02em .3em .06em; border-radius: .3em; font-size: .55em; vertical-align: .32em; color: #14210c; background: var(--hp-sun); }
    .avn-lede { margin-top: 2.1rem; max-width: 36rem; color: #d3dec7; font-size: clamp(1.02rem, 1.7vw, 1.16rem); line-height: 1.7; }
    .avn-lede b { color: #fff; }
    .avn-ctas { margin-top: 1.7rem; display: flex; flex-wrap: wrap; gap: .75rem; }
    @media (max-width: 520px) { .avn-ctas > * { flex: 1 1 100%; justify-content: center; } }

    /* The picture to drag: the farm now on the left, with Anee on the right, the handle between. */
    .avn-ba { position: relative; aspect-ratio: 4 / 3; border-radius: 1.6rem; overflow: hidden; user-select: none; touch-action: pan-y; cursor: ew-resize;
        box-shadow: 0 40px 80px -40px rgb(0 0 0 / .9), 0 0 0 1px rgb(255 255 255 / .12); --cut: 50%; }
    .avn-ba > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
    .avn-ba img.is-now { filter: saturate(.75) contrast(1.02); }
    .avn-ba img.is-anee { clip-path: inset(0 0 0 var(--cut)); }
    .avn-ba-tag { position: absolute; top: .9rem; z-index: 2; display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: 999px;
        font-size: .78rem; font-weight: 900; letter-spacing: .03em; box-shadow: 0 10px 22px -12px rgb(0 0 0 / .8); transition: opacity .28s var(--hp-ease); }
    .avn-ba-tag.is-now { left: .9rem; color: #fff; background: rgb(180 35 24 / .92); }
    .avn-ba-tag.is-anee { right: .9rem; color: #14210c; background: var(--hp-sun); }
    .avn-ba-tag img { width: 1.3rem; height: 1.3rem; border-radius: 999px; }
    .avn-ba-say { position: absolute; bottom: .9rem; z-index: 2; max-width: 46%; padding: .55rem .7rem; border-radius: .9rem; font-size: .76rem; font-weight: 700; line-height: 1.35;
        color: #fff; background: rgb(10 14 7 / .72); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); transition: opacity .28s var(--hp-ease); }
    .avn-ba-say.is-now { left: .9rem; }
    .avn-ba-say.is-anee { right: .9rem; text-align: right; }
    .avn-ba-line { position: absolute; top: 0; bottom: 0; left: var(--cut); z-index: 3; width: 3px; margin-left: -1.5px; background: #fff; box-shadow: 0 0 18px rgb(0 0 0 / .5); }
    .avn-ba-knob { position: absolute; top: 50%; left: var(--cut); z-index: 4; display: grid; place-items: center; width: 3.2rem; height: 3.2rem; margin: -1.6rem 0 0 -1.6rem;
        border-radius: 999px; color: #14210c; background: var(--hp-sun); box-shadow: 0 0 0 5px rgb(245 197 24 / .3), 0 14px 26px -10px rgb(0 0 0 / .8); cursor: ew-resize; }
    .avn-ba-knob svg { width: 1.5rem; height: 1.5rem; }
    .avn-ba-knob:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
    .avn-ba.is-hint .avn-ba-knob { animation: avnKnob 1.6s var(--hp-ease) 1; }
    @keyframes avnKnob { 0%, 100% { transform: none; } 30% { transform: translateX(-14px); } 65% { transform: translateX(14px); } }
    .avn-ba-help { margin-top: .75rem; text-align: center; font-size: .8rem; font-weight: 700; color: rgb(255 255 255 / .6); }

    /* ---- the switch: Now or With Anee, for every comparison on the page ---- */
    .avn-switch { position: relative; display: inline-grid; grid-template-columns: 1fr 1fr; padding: .3rem; border-radius: 999px; background: #fff;
        box-shadow: 0 14px 30px -18px rgb(20 33 12 / .55), 0 0 0 1px #e3ecd9; }
    .avn-switch button { position: relative; z-index: 1; min-width: 7.4rem; padding: .62rem 1.1rem; border-radius: 999px; font-size: .92rem; font-weight: 900; cursor: pointer;
        color: #4b5563; transition: color .28s var(--hp-ease); }
    .avn-switch::before { content: ''; position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc(50% - .3rem); border-radius: 999px; background: var(--now);
        transition: transform .32s var(--hp-ease), background-color .32s var(--hp-ease); }
    .avn[data-side="anee"] .avn-switch::before { transform: translateX(100%); background: var(--anee); }
    .avn[data-side="now"] .avn-switch [data-side-to="now"], .avn[data-side="anee"] .avn-switch [data-side-to="anee"] { color: #fff; }
    /* On a phone it also rides at the bottom of the screen while the comparisons are in view. */
    .avn-dock { position: fixed; left: 50%; bottom: calc(.9rem + env(safe-area-inset-bottom)); z-index: 55; transform: translate(-50%, 160%); transition: transform .32s var(--hp-ease); }
    .avn-dock.is-on { transform: translate(-50%, 0); }
    .avn-dock .avn-switch { box-shadow: 0 18px 40px -14px rgb(0 0 0 / .55), 0 0 0 1px #e3ecd9; }
    @media (min-width: 900px) { .avn-dock { display: none; } }

    /* ---- the nine jobs ---- */
    .avn-jobs-sec { background: linear-gradient(180deg, #fbfdf8, #f3f8ec); }
    .avn-jobs-top { display: flex; flex-direction: column; align-items: center; gap: 1rem; margin-top: 1.8rem; }
    .avn-score { display: flex; align-items: center; gap: .6rem; font-size: .86rem; font-weight: 800; color: #4b5563; }
    .avn-score b { font-family: var(--font-heading); font-size: 1.3rem; color: var(--hp-ink); }
    .avn-jobs { display: grid; gap: .9rem; margin-top: 2rem; }
    .avn-job { perspective: 1400px; }
    .avn-job-in { display: grid; transform-style: preserve-3d; transition: transform .6s var(--hp-ease); }
    .avn-face { grid-area: 1 / 1; display: flex; flex-direction: column; justify-content: space-between; gap: .7rem; padding: 1.1rem 1.1rem 1.15rem; border-radius: 1.3rem; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
    .avn-face.is-now { background: var(--now-soft); border: 1px solid var(--now-line); }
    .avn-face.is-anee { transform: rotateY(180deg); background: #fff; border: 1.5px solid var(--anee-line); box-shadow: 0 22px 44px -32px rgb(47 82 25 / .7); }
    .avn-job.is-anee .avn-job-in { transform: rotateY(180deg); }
    .avn-job-h { display: flex; align-items: center; gap: .65rem; }
    .avn-job-e { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .85rem; font-size: 1.2rem; background: #fff; box-shadow: 0 0 0 1px rgb(0 0 0 / .05); }
    .avn-job-h b { display: block; font-family: var(--font-heading); font-size: 1.04rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .avn-job-h small { display: inline-flex; align-items: center; gap: .3rem; margin-top: .1rem; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .is-now .avn-job-h small { color: var(--now); }
    .is-anee .avn-job-h small { color: var(--anee); }
    .avn-job-h small svg { width: .85rem; height: .85rem; }
    .avn-face-p { font-size: .93rem; line-height: 1.55; color: #374151; }
    .is-now .avn-face-p { color: #5b2a20; font-size: .98rem; font-weight: 600; line-height: 1.5; }
    /* What each side says out loud: the farmer's thought, and Anee's answer, as speech bubbles. */
    .avn-voice { display: flex; align-items: flex-end; gap: .5rem; }
    .avn-voice-who { flex: none; display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 999px; font-size: 1.1rem; background: #fff; box-shadow: 0 0 0 1px rgb(0 0 0 / .06); }
    img.avn-voice-who { object-fit: cover; box-shadow: 0 0 0 2px #fff, 0 0 0 3.5px var(--hp-sun); }
    .avn-voice q { quotes: '\201C' '\201D'; padding: .55rem .8rem; border-radius: 1rem 1rem 1rem .3rem; font-size: .9rem; font-weight: 700; font-style: italic; line-height: 1.4; }
    .avn-voice.is-farmer q { color: #7f1d1d; background: #fff; box-shadow: 0 0 0 1px var(--now-line); }
    .avn-voice.is-anee q { font-style: normal; color: #24400f; background: var(--anee-soft); box-shadow: 0 0 0 1px var(--anee-line); }
    .avn-cost.is-words { align-items: flex-start; font-weight: 700; line-height: 1.45; }
    .avn-cost.is-words span { flex: none; }
    .avn-cost { display: flex; align-items: center; gap: .55rem; padding: .5rem .7rem; border-radius: .8rem; font-size: .8rem; font-weight: 800; color: #991b1b; background: #fee2e2; }
    .avn-cost b { flex: none; white-space: nowrap; font-family: var(--font-heading); font-size: 1.05rem; }
    .avn-tools { display: flex; flex-wrap: wrap; gap: .4rem; }
    .avn-tool { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem .3rem .3rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        color: #24400f; background: var(--anee-soft); border: 1px solid var(--anee-line); transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    a.avn-tool:hover { background: #e3f0d3; transform: translateY(-1px); }
    .avn-tool img { width: 1.3rem; height: 1.3rem; padding: .12rem; border-radius: 999px; object-fit: contain; background: #fff; }
    .avn-tool img.is-face { padding: 0; object-fit: cover; }
    .avn-turn { align-self: flex-start; display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 900;
        cursor: pointer; transition: transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .avn-turn svg { width: 1rem; height: 1rem; }
    .is-now .avn-turn { color: #fff; background: var(--anee); box-shadow: 0 10px 20px -12px rgb(47 82 25 / .9); }
    .is-anee .avn-turn { color: var(--now); background: var(--now-soft); }
    .avn-turn:hover { transform: translateY(-1px); }
    /* A desk has room for both faces side by side: no turning, the switch only lights one side. */
    @media (min-width: 900px) {
        .avn-job { perspective: none; }
        .avn-job-in, .avn-job.is-anee .avn-job-in { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .9rem; transform: none; }
        .avn-face { grid-area: auto; }
        .avn-face.is-anee { transform: none; }
        .avn-turn { display: none; }
        .avn-face { transition: opacity .32s var(--hp-ease), filter .32s var(--hp-ease), box-shadow .32s var(--hp-ease); }
        .avn[data-side="now"] .avn-face.is-anee, .avn[data-side="anee"] .avn-face.is-now { opacity: .55; filter: saturate(.6); }
    }

    /* ---- the bars: what guessing can take, beside a year of a plan ---- */
    .avn-leaks-sec { color: #fff; background: radial-gradient(900px 420px at 90% 0%, rgb(180 35 24 / .28), transparent 60%), linear-gradient(180deg, #16210f, #0f170a); }
    .avn-panel { margin-top: 2.2rem; padding: 1.2rem; border-radius: 1.5rem; background: rgb(255 255 255 / .05); border: 1px solid rgb(255 255 255 / .1); }
    @media (min-width: 640px) { .avn-panel { padding: 1.6rem 1.7rem; } }
    .avn-ctrl { display: grid; gap: 1.1rem; }
    @media (min-width: 900px) { .avn-ctrl { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 2rem; } }
    .avn-ctrl-k { display: flex; justify-content: space-between; align-items: baseline; gap: .6rem; font-size: .8rem; font-weight: 800; color: rgb(255 255 255 / .75); }
    .avn-ctrl-k output { font-family: var(--font-heading); font-size: 1.15rem; color: #fff; }
    .avn-range { width: 100%; margin-top: .55rem; accent-color: var(--hp-sun); height: 1.6rem; cursor: pointer; }
    .avn-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .55rem; }
    .avn-chip { padding: .45rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; cursor: pointer; color: rgb(255 255 255 / .8); background: rgb(255 255 255 / .06);
        border: 1px solid rgb(255 255 255 / .16); transition: background-color .28s var(--hp-ease), color .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .avn-chip[aria-pressed="true"] { color: #14210c; background: var(--hp-sun); border-color: var(--hp-sun); }
    .avn-bars { display: grid; gap: .7rem; margin-top: 1.5rem; }
    .avn-bar { display: grid; gap: .3rem; }
    .avn-bar-k { display: flex; justify-content: space-between; gap: .75rem; font-size: .82rem; font-weight: 700; color: rgb(255 255 255 / .85); }
    .avn-bar-k span:last-child { flex: none; font-weight: 900; color: #fca5a5; font-variant-numeric: tabular-nums; }
    .avn-bar-t { position: relative; height: .7rem; border-radius: 999px; background: rgb(255 255 255 / .07); overflow: hidden; }
    .avn-bar-t i, .avn-bar-t b { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 999px; transition: width .9s var(--hp-ease); width: 0; }
    .avn-bar-t i { background: rgb(248 113 113 / .35); }
    .avn-bar-t b { background: linear-gradient(90deg, #f87171, #dc2626); }
    .avn-bar.is-plan .avn-bar-k span:last-child { color: #bef264; }
    .avn-bar.is-plan .avn-bar-t b { background: linear-gradient(90deg, #a3e635, #4d7c0f); min-width: 4px; }
    .avn-bar.is-plan { margin-top: .5rem; padding-top: .9rem; border-top: 1px dashed rgb(255 255 255 / .18); }
    .avn-bar.is-plan .avn-bar-k span:first-child { color: #ecfccb; }
    .avn-bar-parts { font-size: .76rem; line-height: 1.5; color: rgb(255 255 255 / .62); }
    .avn-bar-parts b { color: #d9f99d; font-weight: 800; }
    .avn-verdict { display: flex; align-items: center; gap: .9rem; margin-top: 1.4rem; padding: 1rem 1.1rem; border-radius: 1.2rem; background: rgb(245 197 24 / .1); border: 1px solid rgb(245 197 24 / .4); }
    .avn-verdict big { flex: none; font-family: var(--font-heading); font-size: clamp(2rem, 6vw, 2.8rem); font-weight: 800; line-height: 1; color: var(--hp-sun); font-variant-numeric: tabular-nums; }
    .avn-verdict p { font-size: .9rem; line-height: 1.5; color: #f3f4f6; }
    .avn-verdict p b { color: #fff; }
    .avn-note { margin-top: .9rem; font-size: .74rem; line-height: 1.5; color: rgb(255 255 255 / .5); }

    /* ---- credits ---- */
    .avn-credits-sec { background: linear-gradient(180deg, #f3f8ec, #fbfdf8); }
    .avn-packs { display: grid; gap: .7rem; margin-top: 2rem; }
    @media (min-width: 640px) { .avn-packs { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .avn-pack { position: relative; padding: 1rem 1.05rem; border-radius: 1.2rem; text-align: left; cursor: pointer; background: #fff; border: 1.5px solid #e3ecd9;
        transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .avn-pack:hover { transform: translateY(-2px); }
    .avn-pack[aria-pressed="true"] { border-color: var(--anee); box-shadow: 0 18px 36px -24px rgb(47 82 25 / .8); }
    .avn-pack b { display: block; font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; color: var(--hp-ink); }
    .avn-pack span { display: block; margin-top: .1rem; font-size: .84rem; font-weight: 700; color: #6b7280; }
    .avn-pack i { position: absolute; top: .8rem; right: .8rem; padding: .12rem .5rem; border-radius: 999px; font-style: normal; font-size: .64rem; font-weight: 900; color: #14210c; background: var(--hp-sun); }
    .avn-meter { margin-top: 1.1rem; padding: 1.1rem 1.15rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e3ecd9; }
    .avn-meter-row { display: grid; grid-template-columns: 2.4rem minmax(0, 1fr) auto; gap: .75rem; align-items: center; }
    .avn-meter-row + .avn-meter-row { margin-top: .85rem; }
    .avn-meter-e { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .85rem; font-size: 1.15rem; background: #f1f8e8; }
    .avn-meter-row p { font-size: .88rem; font-weight: 700; color: #374151; line-height: 1.35; }
    .avn-meter-row p small { display: block; font-weight: 600; color: #6b7280; }
    .avn-meter-row output { font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: var(--anee); font-variant-numeric: tabular-nums; }
    .avn-meter-bar { grid-column: 2 / -1; height: .45rem; border-radius: 999px; background: #eef3e8; overflow: hidden; }
    .avn-meter-bar b { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #84cc16, #3d6823); transition: width .9s var(--hp-ease); }
    .avn-fine { margin-top: .9rem; font-size: .8rem; color: #6b7280; line-height: 1.55; text-align: center; }

    /* ---- a morning with Anee Auto ---- */
    .avn-morning-sec { color: #fff; background: radial-gradient(50rem 26rem at 10% 0%, #2c4f17, transparent 60%), #0f170a; }
    .avn-day { position: relative; display: grid; gap: .8rem; margin-top: 2rem; }
    /* The day's line: a soft gradient, the times as pills on it (the sky, the hour, AM or PM), always inside their column. */
    .avn-day::before { content: ''; position: absolute; left: 1.75rem; top: 1.5rem; bottom: 1.5rem; width: 2px; margin-left: -1px; border-radius: 999px;
        background: linear-gradient(180deg, rgb(253 230 138 / .55), rgb(255 255 255 / .14) 45%, rgb(129 140 248 / .45)); }
    .avn-hour { position: relative; display: grid; grid-template-columns: 3.5rem minmax(0, 1fr); gap: .8rem; align-items: stretch; }
    .avn-hour-t { position: relative; z-index: 1; align-self: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .05rem;
        width: 3.5rem; padding: .45rem 0 .5rem; border-radius: 1.1rem; text-align: center; color: #14210c; background: #fff;
        box-shadow: 0 0 0 4px #0f170a, 0 10px 22px -12px rgb(0 0 0 / .8); transition: background-color .32s var(--hp-ease), box-shadow .32s var(--hp-ease); }
    .avn-hour-t i { font-style: normal; font-size: .95rem; line-height: 1.1; }
    .avn-hour-t b { font-size: .88rem; font-weight: 900; line-height: 1.1; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
    .avn-hour-t small { font-size: .58rem; font-weight: 900; letter-spacing: .1em; color: #6b7280; }
    .avn[data-side="anee"] .avn-hour-t { background: var(--hp-sun); box-shadow: 0 0 0 4px #0f170a, 0 0 22px -4px rgb(245 197 24 / .6); }
    .avn[data-side="anee"] .avn-hour-t small { color: #5b4300; }
    .avn-hour-c { display: grid; }
    .avn-hour-c p { grid-area: 1 / 1; display: flex; flex-direction: column; justify-content: center; padding: .8rem 1rem; border-radius: 1.1rem; font-size: .93rem; line-height: 1.5;
        transition: opacity .38s var(--hp-ease), transform .38s var(--hp-ease); }
    .avn-hour-c .is-now { color: #fee2e2; background: rgb(180 35 24 / .2); border: 1px solid rgb(248 113 113 / .3); }
    .avn-hour-c .is-anee { color: #ecfccb; background: rgb(132 204 22 / .14); border: 1px solid rgb(163 230 53 / .35); }
    .avn-hour-c p b { display: block; margin-bottom: .15rem; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; opacity: .8; }
    .avn[data-side="now"] .avn-hour-c .is-anee, .avn[data-side="anee"] .avn-hour-c .is-now { opacity: 0; transform: translateY(8px); pointer-events: none; }
    @media (min-width: 900px) {
        .avn-day::before { left: 50%; }
        .avn-hour { grid-template-columns: minmax(0, 1fr) 3.5rem minmax(0, 1fr); gap: 1rem; }
        .avn-hour-c .is-now { align-items: flex-end; }
        .avn-hour-c { display: contents; }
        .avn-hour-c p { grid-area: auto; }
        .avn-hour-c .is-now { grid-column: 1; grid-row: 1; text-align: right; }
        .avn-hour-t { grid-column: 2; grid-row: 1; }
        .avn-hour-c .is-anee { grid-column: 3; grid-row: 1; }
        .avn[data-side="now"] .avn-hour-c .is-anee, .avn[data-side="anee"] .avn-hour-c .is-now { opacity: .35; transform: none; }
    }
    .avn-auto-cta { margin-top: 2rem; display: flex; flex-direction: column; align-items: center; gap: .7rem; text-align: center; }
    .avn-auto-cta p { max-width: 38rem; font-size: .86rem; color: rgb(255 255 255 / .65); }

    /* ---- three ways in ---- */
    .avn-ways { display: grid; gap: 1rem; margin-top: 2.2rem; }
    @media (min-width: 900px) { .avn-ways { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .avn-way { position: relative; display: flex; flex-direction: column; gap: .6rem; padding: 1.3rem 1.25rem 1.35rem; border-radius: 1.4rem; background: #fff; border: 1px solid #e3ecd9;
        box-shadow: 0 22px 44px -36px rgb(20 33 12 / .65); overflow: hidden; }
    .avn-way::before { content: ''; position: absolute; inset: 0 0 auto; height: 4px; background: linear-gradient(90deg, #6b9f3d, #f5c518); }
    .avn-way-k { font-size: .72rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .avn-way h3 { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: var(--hp-ink); line-height: 1.2; }
    .avn-way-price { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #7a4b00; }
    .avn-way p { font-size: .9rem; line-height: 1.55; color: #4b5563; }
    .avn-way ul { display: grid; gap: .4rem; margin-top: .2rem; }
    .avn-way li { display: flex; gap: .45rem; font-size: .86rem; font-weight: 700; color: #374151; line-height: 1.4; }
    .avn-way li svg { flex: none; width: 1.1rem; height: 1.1rem; padding: .15rem; margin-top: .05rem; border-radius: 999px; color: #fff; background: #4a7c2a; }
    .avn-way .btn, .avn-way .hp-alt { margin-top: auto; }

    /* ---- the questions ---- */
    .avn-faq { display: grid; gap: .6rem; max-width: 46rem; margin: 2rem auto 0; }
    .avn-qa { border-radius: 1.1rem; background: #fff; border: 1px solid #e3ecd9; overflow: hidden; transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .avn-qa.is-open { border-color: #c9e0ad; box-shadow: 0 14px 30px -24px rgb(20 33 12 / .45); }
    .avn-qa button { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; text-align: left; cursor: pointer;
        font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--hp-ink); }
    .avn-qa button svg { flex: none; width: 1.25rem; height: 1.25rem; color: #4a7c2a; transition: transform .28s var(--hp-ease); }
    .avn-qa.is-open button svg { transform: rotate(45deg); }
    .avn-qa .a { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s var(--hp-ease); }
    .avn-qa.is-open .a { grid-template-rows: 1fr; }
    .avn-qa .a > div { min-height: 0; overflow: hidden; }
    .avn-qa .a p { padding: 0 1.15rem 1.05rem; color: #4b5563; line-height: 1.65; }

    /* ---- the close ---- */
    .avn-final { position: relative; isolation: isolate; overflow: hidden; color: #fff; text-align: center; }
    .avn-final img { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; object-position: center 35%; }
    .avn-final::before { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgb(13 19 9 / .55), rgb(13 19 9 / .85) 60%, rgb(13 19 9 / .94)); }
    .avn-final h2 { font-family: var(--font-heading); font-weight: 800; font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1.12; text-wrap: balance; }
    .avn-final h2 em { font-style: normal; color: var(--hp-sun); }
    .avn-final p { margin: 1rem auto 0; max-width: 36rem; color: #d3dec7; line-height: 1.65; }

    @media (prefers-reduced-motion: reduce) {
        .avn-job-in, .avn-face, .avn-switch::before, .avn-switch button, .avn-dock, .avn-bar-t i, .avn-bar-t b,
        .avn-meter-bar b, .avn-hour-c p, .avn-hour-t, .avn-qa .a, .avn-qa button svg, .avn-chip, .avn-pack, .avn-tool, .avn-turn, .avn-ba-tag, .avn-ba-say { transition: none !important; }
        .avn-ba.is-hint .avn-ba-knob { animation: none; }
        /* No turning over: the side shown simply changes. */
        .avn-face.is-anee, .avn-job.is-anee .avn-job-in { transform: none; }
        .avn-job:not(.is-anee) .avn-face.is-anee, .avn-job.is-anee .avn-face.is-now { visibility: hidden; }
    }
</style>
@endpush

@section('content')
<div class="avn" id="avn" data-side="now">

    {{-- ================= HERO ================= --}}
    <section class="avn-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 avn-hero-grid">
            <div>
                <nav class="avn-crumbs animate-fade-up" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <a href="{{ route('how') }}">How It Works</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <span aria-current="page">Anee vs Now</span>
                </nav>
                <h1 class="avn-h1 animate-fade-up" style="animation-delay:.06s">
                    Your Farm <span class="is-now">Now</span><span class="is-vs">VS</span>Your Farm
                    <span class="hp-mark"><span class="hp-shimmer">With Anee.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="avn-lede animate-fade-up" style="animation-delay:.12s">
                    Most of what a season loses is lost to <b>guessing</b>: a spray on the wrong day, a sack at the wrong stage, a price nobody checked.
                    Here is the same farm done both ways, and why a plan costs <b>less than one of those mistakes</b>.
                </p>
                <div class="avn-ctas animate-fade-up" style="animation-delay:.18s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free {!! $arrow !!}</a>
                    <a href="#avn-jobs" class="hp-alt is-glass">See the nine jobs</a>
                </div>
            </div>
            <div class="animate-fade-up" style="animation-delay:.14s">
                {{-- Drag the handle (or use the arrow keys on it): the farm now on the left, with Anee on the right. --}}
                <div class="avn-ba" id="avnBa">
                    <img class="is-now" src="{{ asset('images/site/lp/sacks.webp') }}" alt="A farmer among his fertilizer sacks, unsure, his notebook in his hand" width="1400" height="1046" fetchpriority="high">
                    <img class="is-anee" src="{{ asset('images/site/lp/palay-phone.webp') }}" alt="A farmer smiling at anee.io on his phone among his ripening palay" width="1400" height="1046">
                    <span class="avn-ba-tag is-now">Now</span>
                    <span class="avn-ba-tag is-anee"><img src="{{ $face }}" alt="">With Anee</span>
                    <span class="avn-ba-say is-now">Guessing the sacks, the days and the price</span>
                    <span class="avn-ba-say is-anee">Every decision read from his own farm</span>
                    <span class="avn-ba-line" aria-hidden="true"></span>
                    <button type="button" class="avn-ba-knob" role="slider" aria-label="Drag to compare the farm now and with Anee" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l-6 6 6 6M15 6l6 6-6 6"/></svg>
                    </button>
                </div>
                <p class="avn-ba-help">Drag the yellow handle to compare</p>
            </div>
        </div>
    </section>

    {{-- ================= THE NINE JOBS ================= --}}
    <section class="hp-sec avn-jobs-sec" id="avn-jobs" data-avn-zone>
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">The same season, done two ways</p>
                <h2 class="hp-h2">Nine Jobs Every Season Asks of You. <em>Now, and With Anee.</em></h2>
                <p class="hp-p">Flip the switch. First each job the way most farms do it today, and what that can cost. Then the same job with anee.io, and the tool that does it.</p>
            </div>
            <div class="avn-jobs-top reveal">
                <div class="avn-switch" role="group" aria-label="Show the farm now or with Anee">
                    <button type="button" data-side-to="now" aria-pressed="true">Now</button>
                    <button type="button" data-side-to="anee" aria-pressed="false">With Anee</button>
                </div>
                <p class="avn-score"><span>Jobs done by guessing:</span> <b data-avn-score>{{ count($jobs) }}</b> <span>of {{ count($jobs) }}</span></p>
            </div>
            <div class="avn-jobs">
                @foreach ($jobs as $ji => $jb)
                    <article class="avn-job reveal" style="--reveal-delay: {{ ($ji % 3) * 0.05 }}s">
                        <div class="avn-job-in">
                            <div class="avn-face is-now">
                                <div class="avn-job-h">
                                    <span class="avn-job-e" aria-hidden="true">{{ $jb['e'] }}</span>
                                    <div><b>{{ $jb['job'] }}</b><small>{!! $cross !!}Now</small></div>
                                </div>
                                <p class="avn-voice is-farmer"><span class="avn-voice-who" aria-hidden="true">👨‍🌾</span><q>{{ $jb['thinks'] }}</q></p>
                                <p class="avn-face-p">{{ $jb['now'] }}</p>
                                @if ($jb['leak'])
                                    <p class="avn-cost"><b>Up to {{ $jb['leak']['n'] }}%</b> {{ mb_strtolower($jb['leak']['title']) }}</p>
                                @elseif ($jb['cost'] !== '')
                                    <p class="avn-cost is-words"><span aria-hidden="true">⚠️</span>{{ $jb['cost'] }}</p>
                                @endif
                                <button type="button" class="avn-turn" data-turn>{!! $flip !!}See it with Anee</button>
                            </div>
                            <div class="avn-face is-anee">
                                <div class="avn-job-h">
                                    <span class="avn-job-e" aria-hidden="true">{{ $jb['e'] }}</span>
                                    <div><b>{{ $jb['job'] }}</b><small>{!! $tick !!}With Anee</small></div>
                                </div>
                                <p class="avn-voice is-anee"><img src="{{ $face }}" alt="" class="avn-voice-who" width="80" height="80" loading="lazy"><q>{{ $jb['says'] }}</q></p>
                                <p class="avn-face-p">{{ $jb['with'] }}</p>
                                <div class="avn-tools">
                                    @foreach ($jb['tools'] as $k)
                                        @php $tl = $toolLink($k); @endphp
                                        @if ($tl)
                                            @if ($tl['href'])
                                                <a class="avn-tool" href="{{ $tl['href'] }}"><img src="{{ $tl['icon'] }}" alt="" class="{{ $tl['face'] ? 'is-face' : '' }}" width="40" height="40" loading="lazy">{{ $tl['name'] }}</a>
                                            @else
                                                <span class="avn-tool"><img src="{{ $tl['icon'] }}" alt="" class="{{ $tl['face'] ? 'is-face' : '' }}" width="40" height="40" loading="lazy">{{ $tl['name'] }}</span>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                                <button type="button" class="avn-turn" data-turn>{!! $flip !!}Back to now</button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= WHAT GUESSING CAN TAKE ================= --}}
    <section class="hp-sec avn-leaks-sec on-dark">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">The math of one mistake</p>
                <h2 class="hp-h2">What Guessing Can Take, <em>Beside a Year of Anee.</em></h2>
                <p class="hp-p">Each red bar is what one kind of guessing can take from your fields in a season, at most. The green bar is a whole year of a plan. Move the slider to your own hectares.</p>
            </div>
            <div class="avn-panel reveal" id="avnLeaks">
                <div class="avn-ctrl">
                    <div>
                        <p class="avn-ctrl-k"><label for="avnHa">Your hectares</label><output id="avnHaOut">1 ha</output></p>
                        <input class="avn-range" type="range" id="avnHa" min="0.5" max="10" step="0.5" value="1">
                    </div>
                    <div>
                        <p class="avn-ctrl-k"><span>Compare with a year of</span></p>
                        <div class="avn-chips" role="group" aria-label="The plan to compare">
                            @foreach ($plans as $pi => $p)
                                <button type="button" class="avn-chip" data-plan="{{ $pi }}" aria-pressed="{{ $pi === 0 ? 'true' : 'false' }}">{{ $p['name'] }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="avn-bars" id="avnBars" aria-live="polite">
                    @foreach ($leaks as $li => $l)
                        <div class="avn-bar" data-n="{{ $l['n'] }}">
                            <p class="avn-bar-k"><span>{{ $l['title'] }}</span><span data-v></span></p>
                            <span class="avn-bar-t" aria-hidden="true"><i></i><b></b></span>
                        </div>
                    @endforeach
                    <div class="avn-bar is-plan">
                        <p class="avn-bar-k"><span data-plan-name></span><span data-v></span></p>
                        <span class="avn-bar-t" aria-hidden="true"><b></b></span>
                        <p class="avn-bar-parts" data-plan-parts></p>
                    </div>
                </div>
                <div class="avn-verdict">
                    <big data-times>0×</big>
                    <p data-verdict></p>
                </div>
                <p class="avn-note">The losses are the "up to" ranges from published Philippine rice loss estimates, on a hectare grossing {{ $money(60000) }} to {{ $money(100000) }} a season; the lighter part of each bar is the top of its range. They overlap, so they are not added up. Your farm's numbers differ, which is the point. Anee's credits are an estimate for one lot and two seasons a year: When to Plant, Variety Ranking and a Crop Protocol each season, and about two questions a week, half with a photo ({{ number_format($yearCredits) }} credits); every question and analysis shows its price before it runs.</p>
            </div>
        </div>
    </section>

    {{-- ================= CREDITS ================= --}}
    @if ($packs)
    <section class="hp-sec avn-credits-sec">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Credits</p>
                <h2 class="hp-h2">Pay Only for What You Ask. <em>Credits Never Expire.</em></h2>
                <p class="hp-p">Anee's answers and analyses run on credits. Every one shows its price before it runs, so you never pay for what you did not ask. Tap a pack to see what it buys.</p>
            </div>
            <div class="avn-packs reveal" id="avnPacks" role="group" aria-label="Credit packs">
                @foreach ($packs as $ki => $k)
                    @php $best = $ki === count($packs) - 1 && $k['credits'] > $k['price'] && count($packs) > 1; @endphp
                    <button type="button" class="avn-pack" data-credits="{{ $k['credits'] }}" aria-pressed="{{ $ki === 1 || count($packs) === 1 ? 'true' : 'false' }}">
                        @if ($best)<i>Most for the money</i>@endif
                        <b>{{ number_format($k['credits']) }} credits</b>
                        <span>{{ $money($k['price']) }}</span>
                    </button>
                @endforeach
            </div>
            <div class="avn-meter reveal" id="avnMeter" aria-live="polite">
                <div class="avn-meter-row">
                    <span class="avn-meter-e" aria-hidden="true">💬</span>
                    <p>Questions to Anee<small>about {{ $perAnswer }} credits each</small></p>
                    <output data-q>0</output>
                    <span class="avn-meter-bar" aria-hidden="true"><b data-qb></b></span>
                </div>
                <div class="avn-meter-row">
                    <span class="avn-meter-e" aria-hidden="true">📷</span>
                    <p>Questions with a photo<small>about {{ $perAnswer + $perPhoto }} credits each</small></p>
                    <output data-p>0</output>
                    <span class="avn-meter-bar" aria-hidden="true"><b data-pb></b></span>
                </div>
            </div>
            @php
                // Which plans add credits every month (a Blade @if glued to a word is not read as one, so the sentence is built here).
                $creditAdds = collect($plans)->filter(fn ($p) => $p['credits'] > 0)->map(fn ($p) => $p['name'] . ' adds ' . $p['credits'])->implode(' and ');
            @endphp
            <p class="avn-fine reveal">Credits come with {{ $aneePlan ? $aneePlan['name'] : 'the paid plans' }} and every plan above it{{ $creditAdds !== '' ? '; ' . $creditAdds . ' every month' : '' }}. A deep analysis, like When to Plant or a Crop Protocol, shows its own price before it runs.</p>
        </div>
    </section>
    @endif

    {{-- ================= A MORNING WITH ANEE AUTO ================= --}}
    <section class="hp-sec avn-morning-sec on-dark" data-avn-zone>
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Or let Anee do all of it</p>
                <h2 class="hp-h2">One Day in Your Season, <em>Alone or With Anee Auto.</em></h2>
                <p class="hp-p">Anee Auto plans the whole season for your lots, tells you each morning what to do, and moves the work herself when the rain comes or a worker cannot. Flip the switch and watch the same day change.</p>
            </div>
            <div class="avn-jobs-top reveal">
                <div class="avn-switch" role="group" aria-label="Show the day now or with Anee Auto">
                    <button type="button" data-side-to="now" aria-pressed="true">Now</button>
                    <button type="button" data-side-to="anee" aria-pressed="false">With Anee</button>
                </div>
            </div>
            <div class="avn-day">
                @foreach ($morning as $mi => [$at, $ampm, $sky, $alone, $auto])
                    <div class="avn-hour reveal" style="--reveal-delay: {{ $mi * 0.05 }}s">
                        <span class="avn-hour-t"><i aria-hidden="true">{{ $sky }}</i><b>{{ $at }}</b><small>{{ $ampm }}</small></span>
                        <div class="avn-hour-c">
                            <p class="is-now"><b>Now</b>{{ $alone }}</p>
                            <p class="is-anee"><b>With Anee Auto</b>{{ $auto }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="avn-auto-cta reveal">
                <a href="{{ route('how') }}#hw-step-auto" class="btn btn-accent btn-lg hp-go">See how Anee Auto works {!! $arrow !!}</a>
                <p>Anee Auto is priced per lot for the whole season. The app shows the price before you start, and its credits cover her planning, her daily readings and your questions.</p>
            </div>
        </div>
    </section>

    {{-- ================= THREE WAYS IN ================= --}}
    <section class="hp-sec" style="background:#fbfdf8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Where your money goes furthest</p>
                <h2 class="hp-h2">Three Ways to Spend, <em>Each Worth More Than It Costs.</em></h2>
            </div>
            <div class="avn-ways">
                <div class="avn-way reveal">
                    <p class="avn-way-k">A plan</p>
                    <h3>Your season, organized</h3>
                    @if ($aneePlan)<p class="avn-way-price">From {{ $money($aneePlan['month']) }} a month</p>@endif
                    <p>The board, the costs, the workers and the reports for every lot, with Anee a tap away.</p>
                    <ul>
                        <li>{!! $tick !!}Every task on its right day</li>
                        <li>{!! $tick !!}Every peso counted by itself</li>
                        <li>{!! $tick !!}Stop any time; nothing renews by itself</li>
                    </ul>
                    <a href="{{ $pricing }}" class="hp-alt">Compare the plans</a>
                </div>
                <div class="avn-way reveal" style="--reveal-delay:.07s">
                    <p class="avn-way-k">Credits</p>
                    <h3>Answers when you need them</h3>
                    @if ($packs)<p class="avn-way-price">From {{ $money($packs[0]['price']) }}</p>@endif
                    <p>A question, a photo of a sick leaf, or a full analysis of when to plant and what to grow. You see the price first.</p>
                    <ul>
                        <li>{!! $tick !!}About {{ $perAnswer }} credits an answer</li>
                        <li>{!! $tick !!}Never expire, across seasons</li>
                        <li>{!! $tick !!}One right answer can save a whole field</li>
                    </ul>
                    <a href="{{ $signup }}" class="hp-alt">Start free</a>
                </div>
                <div class="avn-way reveal" style="--reveal-delay:.14s">
                    <p class="avn-way-k">Anee Auto</p>
                    <h3>The season, done for you</h3>
                    <p class="avn-way-price">Per lot, for the whole season</p>
                    <p>Answer a few questions once. Anee plans every task from land preparation to harvest and keeps it on track every day.</p>
                    <ul>
                        <li>{!! $tick !!}Today's work every morning, for you and each worker</li>
                        <li>{!! $tick !!}Moved around the rain and your crew</li>
                        <li>{!! $tick !!}One mistake avoided usually costs more than Anee Auto does</li>
                    </ul>
                    <a href="{{ $signup }}" class="btn btn-accent hp-go">Let Anee plan my season {!! $arrow !!}</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= QUESTIONS ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Before you decide</p>
                <h2 class="hp-h2">Questions Farmers <em>Ask First.</em></h2>
            </div>
            <div class="avn-faq" id="avnFaq">
                @foreach ($faqs as $fi => [$q, $a])
                    <div class="avn-qa reveal">
                        <button type="button" aria-expanded="false" aria-controls="avnA{{ $fi }}">
                            <span>{{ $q }}</span>
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <div class="a" id="avnA{{ $fi }}" role="region"><div><p>{{ $a }}</p></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= THE CLOSE ================= --}}
    <section class="avn-final">
        <img src="{{ asset('images/site/lp/palay-phone.webp') }}" alt="" width="1400" height="1046" loading="lazy">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-20 sm:py-28">
            <h2 class="reveal">Stop Guessing. <em>Start This Season With Anee.</em></h2>
            <p class="reveal">Set up your first lot in two minutes. Free forever on {{ $freeName }}, no card needed, and upgrade only when it pays.</p>
            <div class="hp-cta is-tight reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ route('how.why') }}" class="hp-alt is-glass">Why Anee answers better</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The switch at hand on a phone, while the jobs or the day are on screen. --}}
    <div class="avn-dock" id="avnDock" aria-hidden="true">
        <div class="avn-switch" role="group" aria-label="Show the farm now or with Anee">
            <button type="button" data-side-to="now" aria-pressed="true" tabindex="-1">Now</button>
            <button type="button" data-side-to="anee" aria-pressed="false" tabindex="-1">With Anee</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('avn');
    if (!root) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const sym = @json($sym);
    const fmt = (v) => sym + Math.round(v).toLocaleString('en-PH');
    // A number that counts to its new value (or lands at once when motion is not welcome).
    const tweens = new WeakMap();
    const count = (el, to, show = (v) => Math.round(v).toLocaleString('en-PH')) => {
        if (!el) return;
        const from = tweens.get(el) ?? 0;
        tweens.set(el, to);
        if (calm) { el.textContent = show(to); return; }
        const t0 = performance.now();
        const step = (t) => {
            const k = Math.min(1, (t - t0) / 700);
            el.textContent = show(from + (to - from) * (1 - Math.pow(1 - k, 3)));
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    // ---- The picture to drag: now on the left, with Anee on the right. ----
    const ba = document.getElementById('avnBa');
    if (ba) {
        const knob = ba.querySelector('.avn-ba-knob');
        const set = (pct) => {
            pct = Math.max(4, Math.min(96, pct));
            ba.style.setProperty('--cut', pct + '%');
            knob.setAttribute('aria-valuenow', String(Math.round(pct)));
            ba.querySelectorAll('.is-now.avn-ba-tag, .is-now.avn-ba-say').forEach((e) => { e.style.opacity = pct < 22 ? '0' : '1'; });
            ba.querySelectorAll('.is-anee.avn-ba-tag, .is-anee.avn-ba-say').forEach((e) => { e.style.opacity = pct > 78 ? '0' : '1'; });
        };
        let drag = false;
        const at = (x) => { const r = ba.getBoundingClientRect(); set((x - r.left) / r.width * 100); };
        ba.addEventListener('pointerdown', (e) => { drag = true; ba.setPointerCapture(e.pointerId); at(e.clientX); ba.classList.remove('is-hint'); });
        ba.addEventListener('pointermove', (e) => { if (drag) at(e.clientX); });
        ba.addEventListener('pointerup', () => { drag = false; });
        ba.addEventListener('pointercancel', () => { drag = false; });
        knob.addEventListener('keydown', (e) => {
            const now = parseFloat(knob.getAttribute('aria-valuenow')) || 50;
            if (e.key === 'ArrowLeft') { set(now - 5); e.preventDefault(); }
            if (e.key === 'ArrowRight') { set(now + 5); e.preventDefault(); }
        });
        set(50);
        // A nudge once it is seen, so the handle reads as something to move.
        if (!calm && 'IntersectionObserver' in window) {
            const io = new IntersectionObserver((es) => { if (es.some((x) => x.isIntersecting)) { ba.classList.add('is-hint'); io.disconnect(); } }, { threshold: .6 });
            io.observe(ba);
        }
    }

    // ---- The switch: Now or With Anee, for the jobs and the day; each job can also be turned by itself. ----
    const jobs = [...root.querySelectorAll('.avn-job')];
    const score = root.querySelector('[data-avn-score]');
    const paintScore = () => count(score, jobs.filter((j) => !j.classList.contains('is-anee')).length);
    const side = (to) => {
        root.dataset.side = to;
        root.querySelectorAll('[data-side-to]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.sideTo === to)));
        jobs.forEach((j, i) => {
            const turn = () => j.classList.toggle('is-anee', to === 'anee');
            if (calm) turn(); else setTimeout(turn, i * 70);
        });
        setTimeout(paintScore, calm ? 0 : jobs.length * 70);
    };
    root.addEventListener('click', (e) => {
        const b = e.target.closest('[data-side-to]');
        if (b) { side(b.dataset.sideTo); return; }
        const t = e.target.closest('[data-turn]');
        if (t) { t.closest('.avn-job').classList.toggle('is-anee'); paintScore(); }
    });
    if (score) tweens.set(score, jobs.length);

    // The switch rides at the bottom of a phone's screen while a comparison is on it.
    const dock = document.getElementById('avnDock');
    if (dock && 'IntersectionObserver' in window) {
        const seen = new Set(), own = new Set();
        const paint = () => {
            const on = seen.size > 0 && own.size === 0;
            dock.classList.toggle('is-on', on);
            dock.setAttribute('aria-hidden', on ? 'false' : 'true');
            dock.querySelectorAll('button').forEach((b) => { b.tabIndex = on ? 0 : -1; });
        };
        const zio = new IntersectionObserver((es) => { es.forEach((e) => { if (e.isIntersecting) seen.add(e.target); else seen.delete(e.target); }); paint(); }, { rootMargin: '-30% 0px -30% 0px' });
        root.querySelectorAll('[data-avn-zone]').forEach((z) => zio.observe(z));
        const sio = new IntersectionObserver((es) => { es.forEach((e) => { if (e.isIntersecting) own.add(e.target); else own.delete(e.target); }); paint(); });
        root.querySelectorAll('[data-avn-zone] .avn-switch').forEach((sw) => sio.observe(sw));
    }

    // ---- The bars: what guessing can take from your hectares, beside a year of a plan. ----
    const PLANS = @json($planJs);
    const YEAR_CREDITS = {{ $yearCredits }};
    const leaks = document.getElementById('avnLeaks');
    if (leaks && PLANS.length) {
        const ha = document.getElementById('avnHa');
        let plan = 0, shown = false;
        const draw = () => {
            const h = parseFloat(ha.value) || 1;
            document.getElementById('avnHaOut').textContent = (h % 1 ? h.toFixed(1) : h) + ' ha';
            const rows = [...leaks.querySelectorAll('.avn-bar:not(.is-plan)')];
            const top = Math.max(...rows.map((r) => Number(r.dataset.n))) * 1000 * h;
            rows.forEach((r) => {
                const n = Number(r.dataset.n);
                const lo = n * 600 * h, hi = n * 1000 * h;
                r.querySelector('i').style.width = (shown ? hi / top * 100 : 0) + '%';
                r.querySelector('b').style.width = (shown ? lo / top * 100 : 0) + '%';
                r.querySelector('[data-v]').textContent = fmt(lo) + ' to ' + fmt(hi);
            });
            const p = PLANS[plan];
            const planYear = p.year > 0 ? p.year : p.month * 12;
            // The plan for the year, and Anee's credits beyond what the plan brings.
            const year = planYear + p.creditCost;
            const pr = leaks.querySelector('.avn-bar.is-plan');
            pr.querySelector('[data-plan-name]').textContent = 'The cost of ' + p.name + ' for a whole year, to help prevent all of these';
            pr.querySelector('[data-v]').textContent = fmt(year);
            const n = (v) => Math.round(v).toLocaleString('en-PH');
            pr.querySelector('[data-plan-parts]').innerHTML = '<b>' + fmt(planYear) + '</b> for the plan'
                + (p.buy > 0
                    ? ' + about <b>' + fmt(p.creditCost) + '</b> for Anee\'s credits (about ' + n(p.buy) + ' credits' + (p.included ? ' beyond the ' + n(p.included) + ' the plan brings' : '') + ')'
                    : '. The ' + n(p.included) + ' credits it brings a year cover the estimate of ' + n(YEAR_CREDITS)) + '.';
            pr.querySelector('b').style.width = (shown ? Math.max(.6, year / top * 100) : 0) + '%';
            const least = Math.min(...rows.map((r) => Number(r.dataset.n))) * 600 * h;
            const times = least / year;
            count(leaks.querySelector('[data-times]'), shown ? times : 0, (v) => (v >= 10 ? Math.round(v) : v.toFixed(1)) + '×');
            leaks.querySelector('[data-verdict]').innerHTML = 'The <b>smallest</b> of these losses, at the low end, is <b>' + fmt(least) + '</b> on your ' + (h % 1 ? h.toFixed(1) : h)
                + ' ha. A whole year of <b>' + p.name + '</b>, with Anee\'s credits, is about ' + fmt(year) + '. Head off even that one, once, and it has paid for itself <b>' + (times >= 10 ? Math.round(times) : times.toFixed(1)) + ' times</b>.';
        };
        ha.addEventListener('input', draw);
        leaks.querySelectorAll('[data-plan]').forEach((b) => b.addEventListener('click', () => {
            plan = Number(b.dataset.plan);
            leaks.querySelectorAll('[data-plan]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
            draw();
        }));
        draw();
        // The bars grow the first time they are seen.
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((es) => { if (es.some((x) => x.isIntersecting)) { shown = true; draw(); io.disconnect(); } }, { threshold: .25 });
            io.observe(leaks);
        } else { shown = true; draw(); }
    }

    // ---- Credits: what a pack buys. ----
    const packs = document.getElementById('avnPacks');
    const meter = document.getElementById('avnMeter');
    if (packs && meter) {
        const per = {{ $perAnswer }}, perPhoto = {{ $perAnswer + $perPhoto }};
        const max = Math.max(...[...packs.querySelectorAll('[data-credits]')].map((b) => Number(b.dataset.credits)));
        let seen = false;
        const draw = () => {
            const b = packs.querySelector('[aria-pressed="true"]') || packs.querySelector('[data-credits]');
            const c = Number(b.dataset.credits);
            count(meter.querySelector('[data-q]'), seen ? Math.floor(c / per) : 0);
            count(meter.querySelector('[data-p]'), seen ? Math.floor(c / perPhoto) : 0);
            meter.querySelector('[data-qb]').style.width = (seen ? c / max * 100 : 0) + '%';
            meter.querySelector('[data-pb]').style.width = (seen ? c / max * (per / perPhoto) * 100 : 0) + '%';
        };
        packs.addEventListener('click', (e) => {
            const b = e.target.closest('[data-credits]');
            if (!b) return;
            packs.querySelectorAll('[data-credits]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
            draw();
        });
        draw();
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((es) => { if (es.some((x) => x.isIntersecting)) { seen = true; draw(); io.disconnect(); } }, { threshold: .3 });
            io.observe(meter);
        } else { seen = true; draw(); }
    }

    // ---- The questions: one open at a time, each folding on its grid. ----
    document.getElementById('avnFaq')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.avn-qa > button');
        if (!btn) return;
        const qa = btn.parentElement;
        const open = !qa.classList.contains('is-open');
        document.querySelectorAll('#avnFaq .avn-qa.is-open').forEach((x) => { x.classList.remove('is-open'); x.querySelector('button').setAttribute('aria-expanded', 'false'); });
        qa.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
</script>
@endpush
