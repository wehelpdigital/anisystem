@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

{{-- ANEE AUTO, the page (2026-10-29, the owner: "under how it works add another page called anee auto then create a page
     for it, make it look good and the contents is highly inspirational about the contents of anee auto how it works
     inside anee. make it highly animated as well").

     The season the way Anee Auto runs it, in the order a farmer lives it: the promise (a phone playing a real Anee Auto
     morning, the day's notes floating in beside it), what happens inside her in seven steps (a line that grows as the
     page is read, each step with its own small animation), a week where the rain comes and the spray moves by itself,
     one day hour by hour under a moving sun, what she reads before every decision (counted up), the change it makes, the
     questions people ask, and the way in.

     Every fact is what the app does: the 5:00 AM re-plan (AutoMorning), the morning plan by email to the owner and the
     workers, Done and Can't today, a photo read and its fix added to the calendar, the three goals per lot, the season
     log. Anee Auto's price is never shown. Plain words, no dashes, never "AI farm technician". --}}
@php
    $signup = route('signup');
    $face = asset('images/anee/avatar-160.jpg');
    $emo = fn (string $n) => \App\Support\AneeEmoji::url($n);
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    $aapFilm = function (string $f) {
        $path = public_path('videos/site/' . $f);

        return asset('videos/site/' . $f) . (is_file($path) ? '?v=' . filemtime($path) : '');
    };

    // What she reads, counted from the app's own books where they can be.
    $count = function (callable $fn, int $fallback) {
        try {
            $n = (int) $fn();

            return $n > 0 ? $n : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    };
    $pests = $count(fn () => count(\App\Support\FieldCatalogue::problems('pests')) + count(\App\Support\FieldCatalogue::problems('diseases')), 229);
    $weeds = $count(fn () => count(\App\Support\FieldCatalogue::weeds()), 81);
    $crops = $count(fn () => count(\App\Support\CropCatalog::CROPS), 85);
    $products = $count(function () {
        $p = json_decode((string) @file_get_contents(database_path('products/pesticides.json')), true);
        $f = json_decode((string) @file_get_contents(database_path('products/fertilizers.json')), true);

        return count($p['products'] ?? []) + count($f['products'] ?? []);
    }, 7000);
    $products = (int) (floor($products / 100) * 100);

    // Inside Anee Auto: the seven steps.
    $steps = [
        ['k' => 'tell', 'n' => 1, 'e' => 'wave', 'h' => 'You tell her about your farm, once', 'p' => 'Your lots and where they are, the crop and the seed, the soil, the water, your workers and how you like to farm. A few friendly questions, saved as you go. You never fill in a form again.',
            'chips' => ['Your lots', 'The map', 'Your soil', 'The water', 'The seed', 'Your workers', 'Your budget']],
        ['k' => 'read', 'n' => 2, 'e' => 'thinking', 'h' => 'She reads everything that decides a season', 'p' => 'Twenty years of your town\'s weather, the El Niño or La Niña outlook, the typhoons and floods your place has seen, your soil, your variety\'s own record, the pests of your crop and the prices at harvest. All of it, before a single task is written.'],
        ['k' => 'goal', 'n' => 3, 'e' => 'choose', 'h' => 'You choose the harvest to aim for', 'p' => 'For every lot she lays out three goals: realistic, achievable with some risk, and the high potential. Each comes with the fertilizer it takes. You pick. She plans for it.',
            'goals' => [['Realistic', 62, 'is-a'], ['Achievable', 80, 'is-b'], ['High potential', 100, 'is-c']]],
        ['k' => 'plan', 'n' => 4, 'e' => 'yes', 'h' => 'She writes the whole season', 'p' => 'Land preparation to harvest, every task on its day, counted from your own planting day: each fertilizer at the stage the crop takes it up, each spray before the pest peaks, the water at every stage, and who does what.'],
        ['k' => 'dawn', 'n' => 5, 'e' => 'idea', 'h' => 'Every morning at 5:00, she checks again', 'p' => 'Before you wake, Anee reads the new forecast and what was done yesterday, and moves what needs moving. Then the morning plan goes out: to you, and to every worker with an email.'],
        ['k' => 'crew', 'n' => 6, 'e' => 'thumbsup', 'h' => 'You and your crew just follow', 'p' => 'Tap Done when a task is done, or Can\'t today when it cannot be, and the plan makes room. Send her a photo of anything odd in the field: she reads it, says what it is and adds the fix to the calendar.'],
        ['k' => 'harvest', 'n' => 7, 'e' => 'starstruck', 'h' => 'Harvest, and every lesson kept', 'p' => 'Every task, every change, every photo and every peso is in the season log. Next season starts from what this one taught you, not from memory.'],
    ];

    // A day with Anee Auto, hour by hour.
    $day = [
        ['5:00', 'AM', '🌅', 'The plan, checked again', 'Anee reads the new forecast and yesterday\'s work, and moves what needs moving.'],
        ['5:05', 'AM', '✉️', 'The morning plan goes out', 'You and your workers get today\'s tasks, lot by lot, with the weather beside them.'],
        ['7:00', 'AM', '🧑‍🌾', 'The crew starts', 'Pedro knows the spray is on Lot 1 this morning, before the rain. Nobody has to ask.'],
        ['10:00', 'AM', '📸', 'A photo from the field', 'Odd spots on a leaf. Anee reads the photo, names the problem and puts the fix on the calendar.'],
        ['2:00', 'PM', '🌧️', 'The rain arrives', 'The spray went on at seven and has dried. The money did not wash off.'],
        ['6:00', 'PM', '✅', 'The day, counted', 'Tasks ticked done, the costs written down, tomorrow already planned.'],
    ];

    // The week where the rain comes (the demo): days, and the tasks on them.
    $week = [['Mon', [['💧', 'Water 3 to 5 cm']]], ['Tue', [['👀', 'Scout for stem borer']]], ['Wed', [['🌿', 'Hand weeding']]],
        ['Thu', [['💧', 'Check the water']]], ['Fri', []], ['Sat', [['👀', 'Scout for leaffolder']]], ['Sun', []]];

    $faqs = [
        ['Do I need to know how to plan a season?', 'No. That is the whole point of Anee Auto. You answer questions about your farm in plain words, and Anee does the planning, the timing and the checking. You follow the plan she gives you each morning.'],
        ['What if the weather changes?', 'Anee checks the forecast for your own lot every morning at 5:00 and moves the work around the rain: a spray moves to a dry morning, a fertilizer waits until the field can hold it. You see what moved and why.'],
        ['Do my workers need the app?', 'No. Every worker with an email gets the morning plan with their own tasks. Workers you give a login can also tick tasks done from their own phone.'],
        ['What if something comes up and a task cannot be done today?', 'Tap Can\'t today and say why if you like. Anee finds the next good day for it and shifts what depends on it.'],
        ['Can I still ask Anee questions?', 'Yes, as many as the season allows. Ask about anything on the farm, in Tagalog, Bisaya, Ilocano or English, and send photos. Her answers know your season, because she planned it.'],
        ['How much is Anee Auto?', 'Anee Auto is priced per lot for the whole season, and the app shows the price before you start. Its credits cover the planning, the daily checks and your questions all season.'],
    ];

    $title = 'Anee Auto: Your Whole Season Planned, Then Re-Planned Every Morning';
    $desc = 'With Anee Auto you answer a few questions about your farm once. Anee reads the weather, your soil and your variety, writes the whole season, checks it again at 5:00 every morning, and tells you and your workers what to do each day.';
@endphp

@section('title', $title)
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ route('how.auto') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Anee Auto: your whole season planned, then re-planned every morning">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ route('how.auto') }}">
    <meta property="og:image" content="{{ asset('videos/site/anee-auto-day.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    @php
        $aapSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'WebPage', '@id' => route('how.auto') . '#page', 'url' => route('how.auto'), 'name' => $title, 'description' => $desc, 'inLanguage' => 'en-PH'],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'How It Works', 'item' => route('how')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Anee Auto', 'item' => route('how.auto')],
                ]],
                ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($aapSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<style>
    /* ===================== ANEE AUTO, THE PAGE (aap-) ===================== */
    .aap { --aap-dark: #0f1a0a; --aap-deep: #1c2e12; overflow-x: clip; }
    .aap-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-size: .82rem; font-weight: 700; color: rgb(255 255 255 / .7); }
    .aap-crumbs a { color: rgb(255 255 255 / .85); text-decoration: none; }
    .aap-crumbs a:hover { color: var(--hp-sun); }
    .aap-crumbs svg { width: .8rem; height: .8rem; opacity: .6; }

    /* ---- hero ---- */
    .aap-hero { position: relative; isolation: isolate; color: #fff; overflow: hidden;
        background: radial-gradient(60rem 30rem at 85% 10%, rgb(245 197 24 / .16), transparent 60%),
            radial-gradient(50rem 40rem at 0% 100%, rgb(74 124 42 / .5), transparent 60%), linear-gradient(160deg, #142410, #0b1407 70%); }
    .aap-sky { position: absolute; inset: 0; z-index: -1; pointer-events: none; }
    .aap-sky i { position: absolute; width: 2px; height: 2px; border-radius: 999px; background: #fff; opacity: .5; animation: aapTwinkle 4s ease-in-out infinite; }
    @keyframes aapTwinkle { 50% { opacity: .1; } }
    .aap-sun { position: absolute; z-index: -1; right: -8rem; bottom: -14rem; width: 36rem; aspect-ratio: 1; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, rgb(253 224 71 / .5), rgb(245 158 11 / .18) 55%, transparent 72%); animation: aapRise 14s ease-in-out infinite alternate; }
    @keyframes aapRise { from { transform: translateY(2rem); } to { transform: translateY(-2.5rem); } }
    .aap-hero-grid { display: grid; gap: 3rem; align-items: center; padding-block: 3.5rem 5rem; }
    @media (min-width: 1024px) { .aap-hero-grid { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 4rem; padding-block: 5rem 6.5rem; } }
    .aap-kick { display: inline-flex; align-items: center; gap: .55rem; margin-top: 1.2rem; padding: .4rem .95rem .4rem .45rem; border-radius: 999px;
        font-size: .82rem; font-weight: 800; color: #f4d778; background: rgb(255 255 255 / .08); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .18); }
    .aap-kick img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #f5c518; }
    .aap-h1 { margin-top: 1.1rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.02em; line-height: 1.04; font-size: clamp(2.4rem, 6vw, 4.1rem); text-wrap: balance; }
    .aap-lede { margin-top: 1.5rem; max-width: 36rem; font-size: clamp(1.02rem, 1.6vw, 1.15rem); line-height: 1.7; color: #d6e2cb; }
    .aap-lede b { color: #fff; }
    .aap-ctas { margin-top: 2rem; display: flex; flex-wrap: wrap; gap: .8rem; }
    .aap-skip { margin: 1.6rem 0 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; gap: .45rem .6rem; }
    .aap-skip li { display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #e7efdc;
        background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .14); }
    .aap-skip li::before { content: ''; width: .9rem; height: 2px; border-radius: 2px; background: #f87171; }
    /* The phone, and the day's notes that float in beside it. */
    .aap-stage { position: relative; display: grid; place-items: center; padding: 1rem 0 2rem; }
    .aap-ring { position: absolute; width: min(26rem, 92%); aspect-ratio: 1; border-radius: 999px; border: 1px dashed rgb(255 255 255 / .16); animation: aapSpin 70s linear infinite; }
    .aap-ring.is-in { width: min(19rem, 70%); border: 1px solid rgb(168 204 126 / .2); animation-direction: reverse; animation-duration: 50s; }
    @keyframes aapSpin { to { transform: rotate(360deg); } }
    .aap-phone { position: relative; z-index: 1; width: min(15.5rem, 62vw); aspect-ratio: 420 / 910; padding: .45rem; border-radius: 2.3rem;
        background: linear-gradient(155deg, #2a3820, #0b1207 60%);
        box-shadow: 0 0 0 2px rgb(245 197 24 / .5), 0 0 60px rgb(245 197 24 / .2), 0 60px 100px -40px rgb(0 0 0 / .9); animation: aapBob 7s ease-in-out infinite; }
    @keyframes aapBob { 50% { translate: 0 -10px; } }
    .aap-phone video { display: block; width: 100%; height: 100%; border-radius: 1.9rem; object-fit: cover; background: #eef2ea; }
    .aap-note { position: absolute; z-index: 2; display: flex; align-items: center; gap: .6rem; max-width: 15rem; padding: .6rem .8rem .6rem .6rem; border-radius: 1rem;
        color: var(--hp-ink); background: rgb(255 255 255 / .96); box-shadow: 0 2px 6px rgb(0 0 0 / .2), 0 20px 40px -14px rgb(0 0 0 / .6);
        opacity: 0; transform: translateY(14px) scale(.94); transition: opacity .6s var(--hp-ease), transform .6s var(--hp-ease); }
    .aap-note.is-on { opacity: 1; transform: none; }
    .aap-note b { display: block; font-size: .8rem; font-weight: 900; line-height: 1.25; }
    .aap-note small { display: block; margin-top: .1rem; font-size: .72rem; line-height: 1.35; color: #4b5563; }
    .aap-note-i { flex: none; display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: .75rem; font-size: 1.15rem; background: #fff6d6; }
    .aap-note.n1 { left: -1rem; top: 12%; }
    .aap-note.n2 { right: -1rem; top: 38%; }
    .aap-note.n3 { left: 0; bottom: 14%; }
    @media (max-width: 639.98px) {
        .aap-note { max-width: 12rem; padding: .45rem .6rem .45rem .45rem; }
        .aap-note small { display: none; }
        .aap-note-i { width: 1.8rem; height: 1.8rem; font-size: .95rem; }
        .aap-note.n1 { left: -.4rem; } .aap-note.n2 { right: -.4rem; } .aap-note.n3 { left: -.2rem; }
    }

    /* ---- inside Anee Auto: the seven steps on a line that grows ---- */
    .aap-inside { position: relative; background: linear-gradient(180deg, #fbfdf8, #f2f8ea); }
    .aap-track { position: relative; max-width: 58rem; margin: 3.5rem auto 0; padding-left: 0; list-style: none; }
    .aap-track::before, .aap-fill { content: ''; position: absolute; left: 1.55rem; top: 1rem; bottom: 1rem; width: 4px; border-radius: 4px; }
    .aap-track::before { background: #e3ecd9; }
    .aap-fill { background: linear-gradient(180deg, #a3e635, #f5c518); height: var(--aap-fill, 0%); bottom: auto; box-shadow: 0 0 12px rgb(245 197 24 / .5); transition: height .2s linear; }
    @media (min-width: 900px) { .aap-track::before, .aap-fill { left: 50%; margin-left: -2px; } }
    .aap-step { position: relative; display: grid; grid-template-columns: 3.4rem minmax(0, 1fr); gap: 1rem; align-items: start; margin-top: 2.2rem; }
    .aap-step:first-of-type { margin-top: 0; }
    @media (min-width: 900px) {
        .aap-step { grid-template-columns: minmax(0, 1fr) 3.4rem minmax(0, 1fr); gap: 2rem; margin-top: 3rem; }
        .aap-step .aap-dot { grid-column: 2; grid-row: 1; }
        .aap-step .aap-card { grid-column: 1; grid-row: 1; }
        .aap-step .aap-art { grid-column: 3; grid-row: 1; }
        .aap-step:nth-of-type(even) .aap-card { grid-column: 3; }
        .aap-step:nth-of-type(even) .aap-art { grid-column: 1; }
    }
    .aap-dot { position: relative; z-index: 1; display: grid; place-items: center; width: 3.4rem; height: 3.4rem; border-radius: 999px; background: #fff;
        box-shadow: 0 0 0 4px #e3ecd9; transition: box-shadow .5s var(--hp-ease), transform .5s var(--hp-ease); }
    .aap-dot img { width: 2.6rem; height: 2.6rem; }
    .aap-dot b { position: absolute; right: -.3rem; top: -.3rem; display: grid; place-items: center; width: 1.45rem; height: 1.45rem; border-radius: 999px;
        font-size: .72rem; font-weight: 900; color: #14210c; background: #f5c518; box-shadow: 0 0 0 3px #fff; }
    .aap-step.is-in .aap-dot { box-shadow: 0 0 0 4px #f5c518, 0 0 24px rgb(245 197 24 / .55); transform: scale(1.06); }
    .aap-card { padding: 1.3rem 1.35rem 1.4rem; border-radius: 1.4rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 22px 44px -32px rgb(20 33 12 / .55);
        opacity: 0; transform: translateY(18px); transition: opacity .7s var(--hp-ease), transform .7s var(--hp-ease); }
    .aap-step.is-in .aap-card { opacity: 1; transform: none; }
    .aap-card h3 { font-family: var(--font-heading); font-size: clamp(1.15rem, 2.2vw, 1.4rem); font-weight: 800; line-height: 1.25; color: var(--hp-ink); }
    .aap-card p { margin-top: .55rem; font-size: .96rem; line-height: 1.65; color: #4b5563; }
    .aap-art { grid-column: 2; min-height: 0; opacity: 0; transform: translateY(18px); transition: opacity .7s var(--hp-ease) .15s, transform .7s var(--hp-ease) .15s; }
    .aap-step.is-in .aap-art { opacity: 1; transform: none; }
    /* Step art: the chips she asks for. */
    .aap-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
    .aap-chips span { padding: .45rem .8rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #2f5219; background: #fff; box-shadow: inset 0 0 0 1.5px #c9dcb4;
        opacity: 0; transform: scale(.7); transition: opacity .4s var(--hp-ease), transform .5s cubic-bezier(.34,1.56,.64,1); transition-delay: calc(.3s + var(--k) * .12s); }
    .aap-step.is-in .aap-chips span { opacity: 1; transform: none; }
    .aap-chips span.is-done { color: #14210c; background: #f5c518; box-shadow: none; }
    /* Step art: what she reads, orbiting her. */
    .aap-orb { position: relative; width: min(100%, 18rem); aspect-ratio: 1; margin: 0 auto; }
    .aap-orb::before { content: ''; position: absolute; inset: 14%; border-radius: 999px; border: 1px dashed #c9dcb4; animation: aapSpin 40s linear infinite; }
    .aap-orb-core { position: absolute; left: 50%; top: 50%; width: 30%; aspect-ratio: 1; transform: translate(-50%, -50%); border-radius: 999px; overflow: hidden;
        box-shadow: 0 0 0 4px #fff, 0 0 0 7px #f5c518, 0 0 30px rgb(245 197 24 / .5); animation: aapThink 2.4s ease-in-out infinite; }
    .aap-orb-core img { width: 100%; height: 100%; object-fit: cover; }
    @keyframes aapThink { 50% { box-shadow: 0 0 0 4px #fff, 0 0 0 7px #a3e635, 0 0 40px rgb(163 230 53 / .6); } }
    .aap-orb-s { position: absolute; left: 50%; top: 50%; display: grid; place-items: center; width: 3rem; height: 3rem; margin: -1.5rem 0 0 -1.5rem; border-radius: 999px;
        font-size: 1.35rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 10px 20px -10px rgb(20 33 12 / .5);
        transform: rotate(var(--a)) translateY(-7.6rem) rotate(calc(var(--a) * -1)); animation: aapLight 5.6s ease-in-out infinite; animation-delay: calc(var(--k) * .8s); }
    @media (max-width: 639.98px) { .aap-orb-s { transform: rotate(var(--a)) translateY(-6.4rem) rotate(calc(var(--a) * -1)); width: 2.6rem; height: 2.6rem; margin: -1.3rem 0 0 -1.3rem; font-size: 1.1rem; } }
    @keyframes aapLight { 0%, 30%, 100% { box-shadow: 0 0 0 1px #e3ecd9, 0 10px 20px -10px rgb(20 33 12 / .5); } 10% { box-shadow: 0 0 0 3px #f5c518, 0 0 22px rgb(245 197 24 / .7); } }
    .aap-orb-s small { position: absolute; top: 100%; margin-top: .25rem; white-space: nowrap; font-size: .62rem; font-weight: 800; color: #4b5563; }
    /* Step art: three goals. */
    .aap-goals { display: grid; gap: .6rem; }
    .aap-goal { display: grid; grid-template-columns: 7.5rem minmax(0, 1fr); gap: .7rem; align-items: center; padding: .7rem .85rem; border-radius: 1rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9; }
    .aap-goal b { font-size: .86rem; font-weight: 900; color: var(--hp-ink); }
    .aap-goal i { display: block; height: .8rem; border-radius: 999px; background: #eef3e8; overflow: hidden; }
    .aap-goal i::after { content: ''; display: block; height: 100%; width: 0; border-radius: inherit; transition: width 1.2s var(--hp-ease); transition-delay: calc(.4s + var(--k) * .2s); }
    .aap-goal.is-a i::after { background: #a3e635; } .aap-goal.is-b i::after { background: #f5c518; } .aap-goal.is-c i::after { background: linear-gradient(90deg, #f5c518, #f97316); }
    .aap-step.is-in .aap-goal i::after { width: var(--w); }
    .aap-goal.is-picked { box-shadow: 0 0 0 2px #4a7c2a, 0 12px 26px -16px rgb(47 82 25 / .6); }
    .aap-goal.is-picked b::after { content: ' ✓'; color: #4a7c2a; }
    /* Step art: a month that fills with tasks. */
    .aap-month { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; padding: .8rem; border-radius: 1.2rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9; }
    .aap-month span { position: relative; aspect-ratio: 1; border-radius: .5rem; background: #f4f8ef; font-size: .62rem; font-weight: 800; color: #9aa892; padding: .2rem .25rem; }
    .aap-month span i { position: absolute; left: 50%; bottom: .25rem; transform: translateX(-50%) scale(0); font-style: normal; font-size: .95rem; line-height: 1;
        transition: transform .45s cubic-bezier(.34,1.56,.64,1); transition-delay: calc(.4s + var(--k) * .08s); }
    .aap-step.is-in .aap-month span i { transform: translateX(-50%) scale(1); }
    .aap-month span.is-task { background: #fff6d6; color: #8a6d00; }
    /* Step art: sunrise and the clock. */
    .aap-dawn { position: relative; height: 11rem; border-radius: 1.4rem; overflow: hidden; background: linear-gradient(180deg, #1e293b, #7c2d12 70%, #f59e0b); box-shadow: 0 0 0 1px #e3ecd9; }
    .aap-dawn-sun { position: absolute; left: 50%; bottom: -3rem; width: 6rem; height: 6rem; margin-left: -3rem; border-radius: 999px;
        background: radial-gradient(closest-side, #fff7cc, #fde047 45%, rgb(245 158 11 / 0) 72%); }
    .aap-step.is-in .aap-dawn-sun { animation: aapDawn 6s ease-in-out infinite; }
    @keyframes aapDawn { 0% { transform: translateY(2rem); } 60%, 100% { transform: translateY(-3.6rem); } }
    .aap-dawn-sky { position: absolute; inset: 0; background: linear-gradient(180deg, rgb(56 189 248 / 0), rgb(56 189 248 / .55)); opacity: 0; }
    .aap-step.is-in .aap-dawn-sky { animation: aapDay 6s ease-in-out infinite; }
    @keyframes aapDay { 0%, 20% { opacity: 0; } 70%, 100% { opacity: 1; } }
    .aap-clock { position: absolute; left: 1rem; top: 1rem; padding: .35rem .7rem; border-radius: .7rem; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: #fff; background: rgb(0 0 0 / .35); }
    .aap-mail { position: absolute; right: 1rem; top: 1rem; display: flex; align-items: center; gap: .4rem; padding: .4rem .7rem; border-radius: .8rem; font-size: .76rem; font-weight: 900;
        color: #14210c; background: #fff; box-shadow: 0 10px 20px -10px rgb(0 0 0 / .7); opacity: 0; }
    .aap-step.is-in .aap-mail { animation: aapMail 6s ease-in-out infinite; }
    @keyframes aapMail { 0%, 45% { opacity: 0; transform: translateY(-8px); } 55%, 92% { opacity: 1; transform: none; } 100% { opacity: 0; } }
    .aap-replan { position: absolute; left: 1rem; right: 1rem; bottom: 1rem; display: flex; gap: .4rem; }
    .aap-replan span { flex: 1; padding: .35rem .45rem; border-radius: .6rem; font-size: .66rem; font-weight: 800; color: #fff; background: rgb(255 255 255 / .16); text-align: center; }
    .aap-replan span.is-move { position: relative; z-index: 1; background: #f5c518; color: #14210c; }
    .aap-step.is-in .aap-replan span.is-move { animation: aapHop 6s ease-in-out infinite; }
    @keyframes aapHop { 0%, 30% { transform: translateX(110%); opacity: .4; } 45%, 100% { transform: none; opacity: 1; } }
    /* Step art: the crew's list. */
    .aap-list { display: grid; gap: .45rem; padding: .8rem; border-radius: 1.2rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9; }
    .aap-row { display: flex; align-items: center; gap: .6rem; padding: .55rem .65rem; border-radius: .85rem; background: #f7faf3; font-size: .84rem; font-weight: 800; color: var(--hp-ink); }
    .aap-row small { margin-left: auto; font-size: .72rem; font-weight: 800; color: #6b7280; }
    .aap-box { flex: none; display: grid; place-items: center; width: 1.4rem; height: 1.4rem; border-radius: .45rem; box-shadow: inset 0 0 0 2px #c9dcb4; color: transparent; transition: background-color .3s, color .3s, box-shadow .3s; }
    .aap-box svg { width: .9rem; height: .9rem; }
    .aap-row.is-ticked .aap-box { background: #4a7c2a; color: #fff; box-shadow: none; }
    .aap-row.is-ticked span:not(.aap-box) { color: #6b7280; text-decoration: line-through; text-decoration-color: #a3c48a; }
    .aap-row.is-cant small { color: #b45309; }
    /* Step art: the season log. */
    .aap-log { display: grid; gap: .5rem; }
    .aap-log div { display: flex; align-items: center; gap: .7rem; padding: .7rem .85rem; border-radius: 1rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9;
        opacity: 0; transform: translateX(-12px); transition: opacity .5s var(--hp-ease), transform .5s var(--hp-ease); transition-delay: calc(.35s + var(--k) * .15s); }
    .aap-step.is-in .aap-log div { opacity: 1; transform: none; }
    .aap-log b { font-size: .86rem; color: var(--hp-ink); }
    .aap-log small { display: block; font-size: .74rem; color: #6b7280; }
    .aap-log em { flex: none; display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: .75rem; font-style: normal; font-size: 1.1rem; background: #f3f8ec; }

    /* ---- the rain comes, the spray moves ---- */
    .aap-rain { position: relative; overflow: hidden; color: #fff; background: radial-gradient(50rem 30rem at 20% 0%, rgb(56 189 248 / .14), transparent 60%), linear-gradient(180deg, #0f1a0a, #172812); }
    .aap-weekbox { position: relative; max-width: 60rem; margin: 3rem auto 0; padding: 1.2rem; border-radius: 1.6rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); }
    .aap-week { position: relative; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .5rem; }
    .aap-wd { position: relative; min-height: 9.5rem; padding: .6rem .45rem; border-radius: 1rem; background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); transition: background-color .5s; }
    .aap-wd > b { display: block; font-size: .78rem; font-weight: 900; color: #d6e2cb; text-align: center; }
    .aap-wd > em { display: block; margin-top: .2rem; font-style: normal; font-size: 1.3rem; text-align: center; transition: transform .5s; }
    .aap-wd.is-wet { background: rgb(56 189 248 / .14); box-shadow: inset 0 0 0 1px rgb(125 211 252 / .45); }
    .aap-task { display: block; margin-top: .45rem; padding: .35rem .4rem; border-radius: .6rem; font-size: .66rem; font-weight: 800; line-height: 1.25; color: #14210c; background: #eef6e4; text-align: center; }
    .aap-task.is-spray { position: absolute; left: .45rem; right: .45rem; top: 6.3rem; margin: 0; background: #f5c518; box-shadow: 0 10px 22px -10px rgb(245 197 24 / .9); z-index: 2;
        transition: transform 1.1s cubic-bezier(.65,0,.35,1), box-shadow .4s; }
    .aap-task.is-spray.is-alarm { box-shadow: 0 0 0 3px #f87171, 0 10px 22px -10px rgb(248 113 113 / .9); }
    .aap-drops { position: absolute; inset: 2.6rem 0 0; overflow: hidden; border-radius: 1rem; opacity: 0; transition: opacity .6s; pointer-events: none; }
    .aap-wd.is-wet .aap-drops { opacity: 1; }
    .aap-drops i { position: absolute; top: -20%; width: 2px; height: 1.1rem; border-radius: 2px; background: linear-gradient(rgb(186 230 253 / 0), #bae6fd); animation: aapDrop .8s linear infinite; }
    @keyframes aapDrop { to { transform: translateY(9rem); } }
    .aap-cloud { position: absolute; z-index: 3; top: -1.2rem; left: 100%; font-size: 2.4rem; transition: left 1.6s cubic-bezier(.22,1,.36,1), opacity .6s; opacity: 0; pointer-events: none; }
    .aap-toast { display: flex; align-items: center; gap: .7rem; max-width: 34rem; margin: 1.1rem auto 0; padding: .75rem 1rem; border-radius: 1rem; color: var(--hp-ink); background: #fff;
        box-shadow: 0 20px 40px -20px rgb(0 0 0 / .8); opacity: 0; transform: translateY(12px); transition: opacity .5s var(--hp-ease), transform .5s var(--hp-ease); }
    .aap-toast.is-on { opacity: 1; transform: none; }
    .aap-toast img { width: 2.2rem; height: 2.2rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #f5c518; }
    .aap-toast b { display: block; font-size: .86rem; }
    .aap-toast small { display: block; font-size: .78rem; color: #4b5563; }
    .aap-replay { display: flex; justify-content: center; margin-top: 1rem; }
    .aap-replay button { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem 1rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #fff; cursor: pointer;
        background: rgb(255 255 255 / .1); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); transition: background-color .28s var(--hp-ease); }
    .aap-replay button:hover { background: rgb(255 255 255 / .18); }
    .aap-replay svg { width: 1rem; height: 1rem; }
    @media (max-width: 639.98px) {
        .aap-weekbox { padding: .7rem; border-radius: 1.2rem; }
        /* Four days a row on a phone, so every task can be read. */
        .aap-week { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .35rem; }
        .aap-wd { min-height: 9.4rem; padding: .5rem .3rem; border-radius: .8rem; }
        .aap-wd > b { font-size: .72rem; }
        .aap-wd > em { font-size: 1.1rem; }
        .aap-task { padding: .3rem .25rem; font-size: .62rem; border-radius: .5rem; }
        .aap-task.is-spray { top: 6.7rem; }
        .aap-cloud { font-size: 1.8rem; }
    }

    /* ---- one day, under a moving sun ---- */
    .aap-day { background: linear-gradient(180deg, #fffaf0, #fdf4dc); }
    .aap-arc { position: relative; max-width: 62rem; height: 7rem; margin: 2.5rem auto 0; }
    .aap-arc svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .aap-arc path { fill: none; stroke: #ecd9a0; stroke-width: 2; stroke-dasharray: 4 6; }
    .aap-arc .aap-arc-sun { position: absolute; width: 3rem; height: 3rem; margin: -1.5rem 0 0 -1.5rem; border-radius: 999px; left: var(--sx, 5%); top: var(--sy, 90%);
        background: radial-gradient(closest-side, #fff7cc, #fde047 50%, rgb(245 158 11 / .0) 75%); filter: drop-shadow(0 0 12px rgb(245 197 24 / .8));
        transition: left 1.2s var(--hp-ease), top 1.2s var(--hp-ease); }
    .aap-hours { display: grid; gap: .8rem; max-width: 62rem; margin: .5rem auto 0; }
    @media (min-width: 900px) { .aap-hours { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    @media (min-width: 560px) and (max-width: 899.98px) { .aap-hours { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .aap-hour { padding: 1rem; border-radius: 1.2rem; background: #fff; box-shadow: 0 0 0 1px #f0e2b6; transition: box-shadow .5s var(--hp-ease), transform .5s var(--hp-ease), background-color .5s; }
    .aap-hour.is-now { transform: translateY(-6px); background: #fffdf5; box-shadow: 0 0 0 2px #f5c518, 0 20px 40px -24px rgb(184 138 0 / .6); }
    .aap-hour-t { display: flex; align-items: baseline; gap: .3rem; font-family: var(--font-heading); font-size: 1.35rem; font-weight: 900; color: var(--hp-ink); }
    .aap-hour-t small { font-size: .74rem; font-weight: 800; color: #9a7b14; }
    .aap-hour-t em { margin-left: auto; font-style: normal; font-size: 1.3rem; }
    .aap-hour b { display: block; margin-top: .45rem; font-size: .9rem; color: var(--hp-ink); }
    .aap-hour p { margin-top: .3rem; font-size: .82rem; line-height: 1.5; color: #4b5563; }

    /* ---- what she reads, counted ---- */
    .aap-reads { display: grid; gap: .9rem; max-width: 62rem; margin: 3rem auto 0; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 900px) { .aap-reads { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .aap-read { position: relative; overflow: hidden; padding: 1.2rem 1.1rem; border-radius: 1.3rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 18px 36px -30px rgb(20 33 12 / .5); }
    .aap-read::after { content: ''; position: absolute; right: -2rem; top: -2rem; width: 6rem; height: 6rem; border-radius: 999px; background: radial-gradient(closest-side, rgb(163 230 53 / .2), transparent); }
    .aap-read em { font-style: normal; font-size: 1.6rem; }
    .aap-read b { display: block; margin-top: .4rem; font-family: var(--font-heading); font-size: clamp(1.6rem, 3.4vw, 2.2rem); font-weight: 900; line-height: 1; color: var(--hp-green); }
    .aap-read span { display: block; margin-top: .4rem; font-size: .84rem; font-weight: 700; line-height: 1.4; color: #4b5563; }

    /* ---- the inspiration band ---- */
    .aap-band { position: relative; overflow: hidden; color: #fff; text-align: center; background: linear-gradient(135deg, #1c3a12, #2f5219 55%, #4a7c2a); }
    .aap-band::before { content: ''; position: absolute; inset: 0; background: url("{{ asset('images/site/photos/golden-palay-1080.webp') }}") center / cover; opacity: .14; mix-blend-mode: luminosity; }
    .aap-quote { position: relative; max-width: 52rem; margin: 0 auto; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.7rem, 4vw, 2.8rem); line-height: 1.2; letter-spacing: -.01em; }
    .aap-quote span { display: inline-block; opacity: 0; transform: translateY(14px); transition: opacity .6s var(--hp-ease), transform .6s var(--hp-ease); transition-delay: calc(var(--k) * .07s); }
    .aap-quote.is-in span { opacity: 1; transform: none; }
    .aap-quote span.is-gold { color: var(--hp-sun); }
    .aap-quote-by { position: relative; display: inline-flex; align-items: center; gap: .6rem; margin-top: 1.6rem; font-weight: 800; color: #d6e2cb; }
    .aap-quote-by img { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #f5c518; }

    /* ---- what changes for you ---- */
    .aap-wins { display: grid; gap: 1rem; max-width: 62rem; margin: 3rem auto 0; }
    @media (min-width: 720px) { .aap-wins { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .aap-win { padding: 1.4rem; border-radius: 1.4rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 22px 44px -34px rgb(20 33 12 / .55); transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .aap-win:hover { transform: translateY(-4px); box-shadow: 0 0 0 1px #c9dcb4, 0 30px 50px -30px rgb(20 33 12 / .55); }
    .aap-win em { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 1rem; font-style: normal; font-size: 1.5rem; background: #f3f8ec; }
    .aap-win b { display: block; margin-top: .8rem; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--hp-ink); }
    .aap-win p { margin-top: .4rem; font-size: .92rem; line-height: 1.6; color: #4b5563; }

    /* ---- questions ---- */
    .aap-faq { max-width: 50rem; margin: 2.5rem auto 0; display: grid; gap: .7rem; }
    .aap-faq details { border-radius: 1.1rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9; overflow: hidden; }
    .aap-faq summary { display: flex; align-items: center; gap: .8rem; padding: 1rem 1.15rem; cursor: pointer; list-style: none; font-weight: 800; color: var(--hp-ink); }
    .aap-faq summary::-webkit-details-marker { display: none; }
    .aap-faq summary::after { content: ''; margin-left: auto; flex: none; width: .65rem; height: .65rem; border-right: 2.5px solid #4a7c2a; border-bottom: 2.5px solid #4a7c2a;
        transform: rotate(45deg); transition: transform .28s var(--hp-ease); }
    .aap-faq details[open] summary::after { transform: rotate(225deg); }
    .aap-faq p { padding: 0 1.15rem 1.1rem; font-size: .95rem; line-height: 1.65; color: #4b5563; }

    /* ---- the way in ---- */
    .aap-end { position: relative; overflow: hidden; color: #fff; text-align: center; background: radial-gradient(50rem 26rem at 50% 0%, rgb(245 197 24 / .18), transparent 60%), linear-gradient(180deg, #142410, #0b1407); }
    .aap-end img.aap-end-face { width: 5rem; height: 5rem; margin: 0 auto; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 4px #fff, 0 0 0 8px #f5c518, 0 0 40px rgb(245 197 24 / .5); animation: aapBob 6s ease-in-out infinite; }

    @media (prefers-reduced-motion: reduce) {
        .aap *, .aap *::before, .aap *::after { animation: none !important; transition: none !important; }
        .aap-note, .aap-card, .aap-art, .aap-chips span, .aap-log div, .aap-quote span, .aap-toast { opacity: 1 !important; transform: none !important; }
        .aap-goal i::after { width: var(--w); }
        .aap-month span i { transform: translateX(-50%) scale(1); }
    }
</style>
@endpush

@section('content')
<div class="aap" id="aap">

    {{-- ================= HERO ================= --}}
    <section class="aap-hero">
        <div class="aap-sky" aria-hidden="true">
            @for ($i = 0; $i < 26; $i++)<i style="left: {{ ($i * 37) % 100 }}%; top: {{ ($i * 23) % 60 }}%; animation-delay: {{ ($i * 0.37) % 4 }}s"></i>@endfor
        </div>
        <div class="aap-sun" aria-hidden="true"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 aap-hero-grid">
            <div>
                <nav class="aap-crumbs animate-fade-up" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <a href="{{ route('how') }}">How It Works</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <span aria-current="page">Anee Auto</span>
                </nav>
                <p class="aap-kick animate-fade-up" style="animation-delay:.04s"><img src="{{ $face }}" alt="" width="40" height="40">Anee Auto</p>
                <h1 class="aap-h1 animate-fade-up" style="animation-delay:.08s">
                    Your Whole Season, Planned for You.
                    <span class="hp-mark"><span class="hp-shimmer">Just Follow Anee.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="aap-lede animate-fade-up" style="animation-delay:.14s">
                    You know your land better than anyone. What a season asks on top of that, the planning, the timing, the weather, the checking every single morning,
                    is <b>Anee's job now</b>. Answer a few questions about your farm once. She writes the whole season, checks it again at <b>5:00 every morning</b>,
                    and tells you and your workers exactly what to do today.
                </p>
                <div class="aap-ctas animate-fade-up" style="animation-delay:.2s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free, then add Anee Auto {!! $arrow !!}</a>
                    <a href="#aap-inside" class="hp-alt is-glass">See how she plans</a>
                </div>
                <ul class="aap-skip animate-fade-up" style="animation-delay:.26s" aria-label="What you no longer have to do">
                    <li>No schedules to build</li>
                    <li>No guessing the weather</li>
                    <li>No missed fertilizer days</li>
                    <li>No telling every worker</li>
                </ul>
            </div>
            <div class="aap-stage animate-fade-up" style="animation-delay:.16s" data-aap-hero>
                <span class="aap-ring" aria-hidden="true"></span>
                <span class="aap-ring is-in" aria-hidden="true"></span>
                <div class="aap-phone">
                    <video muted loop playsinline preload="none" poster="{{ $aapFilm('anee-auto-day.webp') }}" aria-label="Anee Auto in the app: the season's lots, the week's weather, then today's plan with each task and who does it" width="420" height="910">
                        <source src="{{ $aapFilm('anee-auto-day.mp4') }}" type="video/mp4">
                    </video>
                </div>
                <div class="aap-note n1"><span class="aap-note-i">🌅</span><span><b>5:00 AM · Plan checked</b><small>The forecast changed. Two tasks moved.</small></span></div>
                <div class="aap-note n2"><span class="aap-note-i">🌧️</span><span><b>Rain at 2 PM</b><small>The spray moved to 7 AM, while it is dry.</small></span></div>
                <div class="aap-note n3"><span class="aap-note-i">✉️</span><span><b>Crew of 3 told</b><small>Each worker has today's tasks by email.</small></span></div>
            </div>
        </div>
    </section>

    {{-- ================= INSIDE ANEE AUTO ================= --}}
    <section class="hp-sec aap-inside" id="aap-inside" aria-labelledby="aapInsideH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Inside Anee Auto</p>
                <h2 class="hp-h2" id="aapInsideH">Seven Steps. <em>One Season That Runs Itself.</em></h2>
                <p class="hp-p">This is what happens inside Anee from the first question to the last sack. You do the farming. She does the thinking that farming needs, every day.</p>
            </div>
            <ol class="aap-track" data-aap-track>
                <span class="aap-fill" aria-hidden="true"></span>
                @foreach ($steps as $s)
                    <li class="aap-step" data-aap-step>
                        <span class="aap-dot"><img src="{{ $emo($s['e']) }}" alt="" width="64" height="64" loading="lazy"><b>{{ $s['n'] }}</b></span>
                        <div class="aap-card">
                            <h3>{{ $s['h'] }}</h3>
                            <p>{{ $s['p'] }}</p>
                        </div>
                        <div class="aap-art" aria-hidden="true">
                            @switch($s['k'])
                                @case('tell')
                                    <div class="aap-chips">
                                        @foreach ($s['chips'] as $i => $c)<span class="{{ $i < 5 ? 'is-done' : '' }}" style="--k: {{ $i }}">{{ $i < 5 ? '✓ ' : '' }}{{ $c }}</span>@endforeach
                                    </div>
                                    @break
                                @case('read')
                                    <div class="aap-orb">
                                        <span class="aap-orb-core"><img src="{{ $face }}" alt=""></span>
                                        @foreach ([['🌦️', 'Weather'], ['🌀', 'El Niño'], ['🌊', 'Floods'], ['🪨', 'Soil'], ['🌾', 'Variety'], ['🐛', 'Pests'], ['📈', 'Prices']] as $i => [$ic, $w])
                                            <span class="aap-orb-s" style="--a: {{ round($i * 360 / 7) }}deg; --k: {{ $i }}">{{ $ic }}<small>{{ $w }}</small></span>
                                        @endforeach
                                    </div>
                                    @break
                                @case('goal')
                                    <div class="aap-goals">
                                        @foreach ($s['goals'] as $i => [$g, $w, $cls])
                                            <div class="aap-goal {{ $cls }} {{ $i === 1 ? 'is-picked' : '' }}" style="--k: {{ $i }}; --w: {{ $w }}%"><b>{{ $g }}</b><i></i></div>
                                        @endforeach
                                    </div>
                                    @break
                                @case('plan')
                                    @php $taskDays = [2 => '💧', 5 => '🧪', 8 => '👀', 11 => '🌿', 13 => '💧', 16 => '🧪', 19 => '🛡️', 22 => '👀', 25 => '💧', 27 => '🧪']; @endphp
                                    <div class="aap-month">
                                        @for ($d = 1; $d <= 28; $d++)
                                            <span class="{{ isset($taskDays[$d]) ? 'is-task' : '' }}">{{ $d }}@if (isset($taskDays[$d]))<i style="--k: {{ array_search($d, array_keys($taskDays)) }}">{{ $taskDays[$d] }}</i>@endif</span>
                                        @endfor
                                    </div>
                                    @break
                                @case('dawn')
                                    <div class="aap-dawn">
                                        <span class="aap-dawn-sky"></span>
                                        <span class="aap-dawn-sun"></span>
                                        <span class="aap-clock">5:00 AM</span>
                                        <span class="aap-mail">✉️ Morning plan sent</span>
                                        <span class="aap-replan"><span>Weed</span><span class="is-move">Spray, moved</span><span>Water</span></span>
                                    </div>
                                    @break
                                @case('crew')
                                    <div class="aap-list" data-aap-crew>
                                        <div class="aap-row"><span class="aap-box">{!! $tick !!}</span><span>Keep water at 3 to 5 cm</span><small>Juan</small></div>
                                        <div class="aap-row"><span class="aap-box">{!! $tick !!}</span><span>Scout for stem borer</span><small>Pedro</small></div>
                                        <div class="aap-row"><span class="aap-box">{!! $tick !!}</span><span>Hand weeding, levees</span><small>Maria</small></div>
                                    </div>
                                    @break
                                @case('harvest')
                                    <div class="aap-log">
                                        <div style="--k: 0"><em>🌾</em><span><b>Harvest, Lot 1</b><small>Every task and change of the season, in one log</small></span></div>
                                        <div style="--k: 1"><em>📸</em><span><b>Photos and what they showed</b><small>Kept with the day they were taken</small></span></div>
                                        <div style="--k: 2"><em>💰</em><span><b>Every peso, lot by lot</b><small>Ready for next season's plan</small></span></div>
                                    </div>
                                    @break
                            @endswitch
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= THE RAIN COMES, THE SPRAY MOVES ================= --}}
    <section class="hp-sec aap-rain on-dark" aria-labelledby="aapRainH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Watch it happen</p>
                <h2 class="hp-h2" id="aapRainH">The Rain Comes. <em>The Plan Moves by Itself.</em></h2>
                <p class="hp-p">A spray washed off by the rain is paid for twice. Here is a real week: Thursday's forecast turns wet, and before anyone wakes up, Anee has already moved the work.</p>
            </div>
            <div class="aap-weekbox reveal" data-aap-week>
                <span class="aap-cloud" aria-hidden="true">🌧️</span>
                <div class="aap-week">
                    @foreach ($week as $i => [$d, $tasks])
                        <div class="aap-wd" data-wd="{{ $i }}">
                            <b>{{ $d }}</b><em data-wd-sky>{{ $i === 3 ? '⛅' : ($i === 5 ? '🌤️' : '☀️') }}</em>
                            @foreach ($tasks as [$ic, $t])<span class="aap-task">{{ $ic }} {{ $t }}</span>@endforeach
                            @if ($i === 3)<span class="aap-drops">@for ($r = 0; $r < 10; $r++)<i style="left: {{ 6 + $r * 9 }}%; animation-delay: {{ ($r * 0.13) }}s"></i>@endfor</span>@endif
                        </div>
                    @endforeach
                    <span class="aap-task is-spray" data-aap-spray>🛡️ Spray, Lot 1</span>
                </div>
                <div class="aap-toast" data-aap-toast role="status">
                    <img src="{{ $face }}" alt="" width="40" height="40">
                    <span><b data-aap-toast-h>Thursday is clear. The spray stays.</b><small data-aap-toast-p>Pedro has it on his morning plan.</small></span>
                </div>
            </div>
            <div class="aap-replay"><button type="button" data-aap-replay><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0113.7-5.7L20 8.6M20 4v4.6h-4.6"/></svg>Play the week again</button></div>
        </div>
    </section>

    {{-- ================= A DAY WITH ANEE AUTO ================= --}}
    <section class="hp-sec aap-day" aria-labelledby="aapDayH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">One day</p>
                <h2 class="hp-h2" id="aapDayH">From Sunrise to Sundown, <em>Anee Is on It.</em></h2>
                <p class="hp-p">She is up before you are, and the day is counted before you sleep. This is an ordinary Thursday with Anee Auto.</p>
            </div>
            <div class="aap-arc" aria-hidden="true" data-aap-arc>
                <svg viewBox="0 0 100 40" preserveAspectRatio="none"><path d="M2 38 Q 50 -14 98 38"/></svg>
                <span class="aap-arc-sun"></span>
            </div>
            <div class="aap-hours" data-aap-hours>
                @foreach ($day as [$t, $ap, $ic, $h, $p])
                    <div class="aap-hour reveal" style="--reveal-delay: {{ $loop->index * 0.06 }}s">
                        <div class="aap-hour-t">{{ $t }}<small>{{ $ap }}</small><em aria-hidden="true">{{ $ic }}</em></div>
                        <b>{{ $h }}</b>
                        <p>{{ $p }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= WHAT SHE READS ================= --}}
    <section class="hp-sec" aria-labelledby="aapReadsH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Before every decision</p>
                <h2 class="hp-h2" id="aapReadsH">She Reads All of This, <em>So You Never Have to Guess.</em></h2>
                <p class="hp-p">A good technician walks the field and reads the sky. Anee does that for every lot, every morning, with more behind each answer than any one person could carry.</p>
            </div>
            <div class="aap-reads" data-aap-reads>
                <div class="aap-read reveal"><em>🌦️</em><b data-to="20">20</b><span>years of your town's weather, month by month</span></div>
                <div class="aap-read reveal" style="--reveal-delay:.06s"><em>🐛</em><b data-to="{{ $pests }}">{{ $pests }}</b><span>pests and diseases, with what works on each</span></div>
                <div class="aap-read reveal" style="--reveal-delay:.12s"><em>🧴</em><b data-to="{{ $products }}" data-plus>{{ number_format($products) }}+</b><span>products registered in the Philippines</span></div>
                <div class="aap-read reveal" style="--reveal-delay:.18s"><em>🌿</em><b data-to="{{ $weeds }}">{{ $weeds }}</b><span>weeds of Philippine fields, and their windows</span></div>
                <div class="aap-read reveal"><em>🌀</em><b>El Niño</b><span>or La Niña, read for your season's months</span></div>
                <div class="aap-read reveal" style="--reveal-delay:.06s"><em>🪨</em><b>Your soil</b><span>from your answers, your test, and your area's record</span></div>
                <div class="aap-read reveal" style="--reveal-delay:.12s"><em>🌾</em><b>Your seed</b><span>your variety's own maturity, yield and weak spots</span></div>
                <div class="aap-read reveal" style="--reveal-delay:.18s"><em>📈</em><b>Prices</b><span>when your harvest is likely to sell best</span></div>
            </div>
        </div>
    </section>

    {{-- ================= THE INSPIRATION ================= --}}
    <section class="hp-sec aap-band" aria-label="Why Anee Auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            @php
                $quote = 'Farming will always be hard work. It should never have to be guesswork. You bring the land and the hands. Anee brings the planning, every single morning.';
                $gold = ['guesswork.', 'every', 'single', 'morning.'];
            @endphp
            <p class="aap-quote" data-aap-quote>
                @foreach (explode(' ', $quote) as $i => $w)<span style="--k: {{ $i }}" class="{{ in_array($w, $gold, true) && $i > 6 ? 'is-gold' : '' }}">{{ $w }}</span> @endforeach
            </p>
            <div class="text-center"><span class="aap-quote-by"><img src="{{ $face }}" alt="" width="80" height="80">Anee, your smart farm technician</span></div>
        </div>
    </section>

    {{-- ================= WHAT CHANGES ================= --}}
    <section class="hp-sec" aria-labelledby="aapWinsH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">What changes for you</p>
                <h2 class="hp-h2" id="aapWinsH">More Season, <em>Less Worry.</em></h2>
            </div>
            <div class="aap-wins">
                <div class="aap-win reveal"><em>⏰</em><b>Your evenings back</b><p>No planning at night, no notebook of dates, no calling around. The plan is written and kept up to date for you.</p></div>
                <div class="aap-win reveal" style="--reveal-delay:.06s"><em>🎯</em><b>Every task on its right day</b><p>Fertilizer at the stage the crop takes it up, sprays before the pest peaks, and the rain worked around, morning after morning.</p></div>
                <div class="aap-win reveal" style="--reveal-delay:.12s"><em>🧑‍🤝‍🧑</em><b>A crew that knows the plan</b><p>Each worker gets their own tasks every morning. Fewer mistakes, fewer phone calls, no work done twice.</p></div>
                <div class="aap-win reveal"><em>📸</em><b>Problems caught early</b><p>Send a photo the moment something looks off. Anee names it and puts the fix on the calendar the same day.</p></div>
                <div class="aap-win reveal" style="--reveal-delay:.06s"><em>📒</em><b>A season you can learn from</b><p>Every task, change, photo and peso is kept, so each season starts smarter than the last.</p></div>
                <div class="aap-win reveal" style="--reveal-delay:.12s"><em>🌾</em><b>The harvest you aimed for</b><p>You picked the goal. Every day of the plan works toward it, and you see how close you are.</p></div>
            </div>
        </div>
    </section>

    {{-- ================= QUESTIONS ================= --}}
    <section class="hp-sec" style="padding-top:0" aria-labelledby="aapFaqH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Questions</p>
                <h2 class="hp-h2" id="aapFaqH">What Farmers <em>Ask Us</em></h2>
            </div>
            <div class="aap-faq">
                @foreach ($faqs as $i => [$q, $a])
                    <details class="reveal" @if ($i === 0) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= THE WAY IN ================= --}}
    <section class="hp-sec aap-end on-dark" aria-labelledby="aapEndH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <img class="aap-end-face" src="{{ $emo('wink') }}" alt="" width="128" height="128">
                <h2 class="hp-h2" id="aapEndH" style="margin-top:1.4rem">Let This Be the Season <em>You Just Follow Anee.</em></h2>
                <p class="hp-p">Start free on Libre, then turn on Anee Auto for your lots when you are ready. It is priced per lot for the whole season, and the app shows the price before you start.</p>
            </div>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See all of How It Works</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed. <a href="{{ route('how.vs') }}">See Anee vs Now</a></p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('aap');
    if (!root) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const seen = (el, fn, opts) => {
        if (!el) return;
        if (!('IntersectionObserver' in window)) { fn(true); return; }
        new IntersectionObserver((es) => es.forEach((e) => fn(e.isIntersecting, e)), opts || { threshold: .25 }).observe(el);
    };

    /* The hero: the film plays while seen; the day's notes float in one by one, then again. */
    const hero = root.querySelector('[data-aap-hero]');
    if (hero) {
        const v = hero.querySelector('video'), notes = [...hero.querySelectorAll('.aap-note')];
        let t = 0, k = 0;
        const turn = () => { notes.forEach((n, i) => n.classList.toggle('is-on', i <= k)); k = (k + 1) % (notes.length + 1); t = setTimeout(turn, k === 0 ? 2600 : 1800); };
        if (calm) notes.forEach((n) => n.classList.add('is-on'));
        seen(hero, (on) => {
            if (on) { if (!calm) { v.play().catch(() => {}); clearTimeout(t); turn(); } }
            else { v.pause(); clearTimeout(t); }
        }, { threshold: .2 });
    }

    /* Inside: each step lights as it is reached, and the line fills with the reading. */
    const track = root.querySelector('[data-aap-track]');
    if (track) {
        const fill = track.querySelector('.aap-fill');
        track.querySelectorAll('[data-aap-step]').forEach((st) => seen(st, (on) => { if (on) st.classList.add('is-in'); }, { threshold: .35 }));
        const paint = () => {
            const r = track.getBoundingClientRect(), mid = innerHeight * .6;
            const p = Math.max(0, Math.min(1, (mid - r.top) / r.height));
            fill.style.setProperty('--aap-fill', (p * 100).toFixed(1) + '%');
        };
        if (calm) fill.style.setProperty('--aap-fill', '100%');
        else { addEventListener('scroll', paint, { passive: true }); paint(); }
        // The crew ticks its list, one by one, and one task says Can't today.
        const crew = track.querySelector('[data-aap-crew]');
        if (crew && !calm) {
            const rows = [...crew.querySelectorAll('.aap-row')];
            let c = 0, ct = 0;
            const tick = () => {
                if (c === rows.length) { rows.forEach((r) => { r.classList.remove('is-ticked', 'is-cant'); r.querySelector('small').textContent = r.dataset.who; }); c = 0; ct = setTimeout(tick, 900); return; }
                const r = rows[c];
                if (c === 2) { r.classList.add('is-cant'); r.querySelector('small').textContent = 'Moved to Fri'; } else r.classList.add('is-ticked');
                c++; ct = setTimeout(tick, 1300);
            };
            rows.forEach((r) => { r.dataset.who = r.querySelector('small').textContent; });
            seen(crew, (on) => { clearTimeout(ct); if (on) ct = setTimeout(tick, 900); });
        }
    }

    /* The week: Thursday turns wet, the spray hops to Wednesday morning, the crew is told. */
    const week = root.querySelector('[data-aap-week]');
    if (week) {
        const spray = week.querySelector('[data-aap-spray]'), cloud = week.querySelector('.aap-cloud'), toast = week.querySelector('[data-aap-toast]');
        const days = [...week.querySelectorAll('.aap-wd')], thu = days[3], wed = days[2];
        const th = toast.querySelector('[data-aap-toast-h]'), tp = toast.querySelector('[data-aap-toast-p]');
        let timers = [], ran = false;
        const at = (ms, fn) => timers.push(setTimeout(fn, ms));
        const place = (day) => {
            const g = week.querySelector('.aap-week').getBoundingClientRect(), d = day.getBoundingClientRect(), base = days[3].getBoundingClientRect();
            spray.style.transform = 'translateX(' + (d.left - base.left).toFixed(1) + 'px)';
            spray.style.left = (base.left - g.left + (innerWidth < 640 ? 3 : 7)) + 'px';
            spray.style.width = (base.width - (innerWidth < 640 ? 6 : 14)) + 'px';
            spray.style.right = 'auto';
        };
        const reset = () => {
            timers.forEach(clearTimeout); timers = [];
            thu.classList.remove('is-wet'); thu.querySelector('[data-wd-sky]').textContent = '⛅';
            spray.classList.remove('is-alarm'); spray.style.transition = 'none'; place(thu); void spray.offsetWidth; spray.style.transition = '';
            cloud.style.opacity = '0'; cloud.style.left = '100%';
            th.textContent = 'Thursday looks fine. The spray is set.'; tp.textContent = 'Pedro has it on his plan.'; toast.classList.add('is-on');
        };
        const play = () => {
            reset();
            if (calm) { thu.classList.add('is-wet'); place(wed); th.textContent = 'Rain on Thursday afternoon. The spray moved to Wednesday, 7 AM.'; tp.textContent = 'Pedro was told on his morning plan.'; return; }
            at(1400, () => { toast.classList.remove('is-on'); cloud.style.opacity = '1'; cloud.style.left = (thu.offsetLeft + thu.offsetWidth / 2 - 20) + 'px'; });
            at(2600, () => { thu.classList.add('is-wet'); thu.querySelector('[data-wd-sky]').textContent = '🌧️'; spray.classList.add('is-alarm'); });
            at(3800, () => { spray.classList.remove('is-alarm'); place(wed); });
            at(5000, () => { th.textContent = '5:00 AM: rain on Thursday afternoon. The spray moved to Wednesday, 7 AM.'; tp.textContent = 'Pedro was told on his morning plan. Nothing to wash off.'; toast.classList.add('is-on'); });
        };
        reset();
        addEventListener('resize', () => { if (!ran) reset(); });
        seen(week, (on) => { if (on && !ran) { ran = true; play(); } }, { threshold: .45 });
        root.querySelector('[data-aap-replay]')?.addEventListener('click', () => { ran = true; play(); });
    }

    /* One day: the sun travels the arc as the hours light up, one after another. */
    const arc = root.querySelector('[data-aap-arc]'), hours = [...root.querySelectorAll('[data-aap-hours] .aap-hour')];
    if (arc && hours.length) {
        let h = 0, ht = 0;
        const sun = (i) => {
            const t = hours.length > 1 ? i / (hours.length - 1) : 0;
            // The arc's own curve (M2 38 Q50 -14 98 38) in its 100 by 40 box.
            const x = 2 + 96 * t, y = 38 - 104 * t + 104 * t * t;
            arc.querySelector('.aap-arc-sun').style.top = Math.max(0, Math.min(100, (y / 40) * 100)) + '%';
            arc.querySelector('.aap-arc-sun').style.left = x + '%';
            hours.forEach((el, j) => el.classList.toggle('is-now', j === i));
        };
        const run = () => { sun(h); h = (h + 1) % hours.length; ht = setTimeout(run, 2200); };
        if (calm) sun(0);
        else seen(arc.parentElement, (on) => { clearTimeout(ht); if (on) run(); }, { threshold: .3 });
    }

    /* What she reads: the numbers count up once seen. */
    root.querySelectorAll('[data-aap-reads] [data-to]').forEach((b) => {
        const to = +b.dataset.to, plus = b.hasAttribute('data-plus');
        if (calm || !to) return;
        b.textContent = '0';
        seen(b, (on) => {
            if (!on || b.dataset.done) return;
            b.dataset.done = '1';
            const t0 = performance.now();
            const step = (t) => { const k = Math.min(1, (t - t0) / 1500), v = Math.round(to * (1 - Math.pow(1 - k, 3))); b.textContent = v.toLocaleString() + (plus && k >= 1 ? '+' : ''); if (k < 1) requestAnimationFrame(step); };
            requestAnimationFrame(step);
        }, { threshold: .6 });
    });

    /* The quote, word by word. */
    const q = root.querySelector('[data-aap-quote]');
    if (q) seen(q, (on) => { if (on) q.classList.add('is-in'); }, { threshold: .4 });
})();
</script>
@endpush
