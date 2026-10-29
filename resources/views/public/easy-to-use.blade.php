@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

{{-- EASY TO USE (2026-10-29, the owner: "add a new section in the homepage and how it works (new page for this) about
     anee.io is easy to use and understand then say because it has video tutorials, it has taglish icons, etc. ... and
     also specifically mention that it is made and designed by filipino farmers for filipino farmers thats why it is
     catered and designed for the philippines").

     The homepage's section (public.partials.easy-section) is the middle of this page; around it: Anee greeting in four
     languages, every screen teaching itself, real Taglish tags to tap (the app's own glossary), the field things
     (offline, install, bigger text, night mode, photos, voice notes), the Philippine things counted, questions, the way
     in. Every claim is what the app does today. Plain words, no dashes, never "AI farm technician". --}}
@php
    $signup = route('signup');
    $face = asset('images/anee/avatar-160.jpg');
    $emo = fn (string $n) => \App\Support\AneeEmoji::url($n);
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';

    // Anee says hello, the way you would.
    $hellos = [
        ['Tagalog', 'Magandang umaga! Ano po ang tanong mo tungkol sa bukid?'],
        ['Bisaya', 'Maayong buntag! Unsay imong pangutana bahin sa uma?'],
        ['Ilocano', 'Naimbag a bigatmo! Ania ti saludsodmo maipapan iti talon?'],
        ['English', 'Good morning! What would you like to know about your farm?'],
    ];

    // Real tags from the app's glossary, the words a season is full of.
    $book = \App\Support\Tagalog::book();
    $tlKeys = ['dat', 'das', 'tillering', 'panicle-initiation', 'basal', 'topdress', 'npk'];
    $tags = collect($tlKeys)->filter(fn ($k) => isset($book[$k]))->map(fn ($k) => ['key' => $k, 'en' => $book[$k]['en'], 'tl' => $book[$k]['tl']])->values()->all();

    // Screens that open with their own video.
    $screens = ['Dashboard', 'Cropping schedules', 'Each season', 'Activities', 'Calendar', 'Lots', 'Workers', 'Expenses', 'Inventory', 'Notes', 'Gallery', 'Observations', 'Reports', 'Maps', 'Growth stage'];

    $count = function (callable $fn, int $fallback) {
        try {
            $n = (int) $fn();

            return $n > 0 ? $n : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    };
    $crops = $count(fn () => \App\Support\Region::as('PH', fn () => count(\App\Support\CropCatalog::visible())), 85);
    $pests = $count(fn () => count(\App\Support\FieldCatalogue::problems('pests')) + count(\App\Support\FieldCatalogue::problems('diseases')), 229);
    $products = $count(function () {
        $p = json_decode((string) @file_get_contents(database_path('products/pesticides.json')), true);
        $f = json_decode((string) @file_get_contents(database_path('products/fertilizers.json')), true);

        return count($p['products'] ?? []) + count($f['products'] ?? []);
    }, 7000);
    $products = (int) (floor($products / 100) * 100);

    $faqs = [
        ['I am not good with phones. Can I still use it?', 'Yes. If you can send a photo in a chat, you can use anee.io. Today\'s work is the first thing you see, a task is marked done with one tap, and every main screen opens with a short video that shows you how.'],
        ['Is it in Tagalog?', 'The screens are in plain English with Taglish help tags beside the technical words: tap one for the meaning in Tagalog. Anee herself answers in Tagalog, Bisaya, Ilocano or English, whichever you ask in.'],
        ['Does it work where there is no signal?', 'Turn on offline mode in your settings while you have signal, and your season opens in the field even without it. Asking Anee needs a connection, because she reads the latest weather for you.'],
        ['Who made anee.io?', 'Filipino farmers who plant palay, mais and gulay, for farmers like them. It is made for Philippine crops, weather, products and markets, and it counts in cavans, sacks, pesos and hectares.'],
        ['What if I get stuck?', 'Watch the screen\'s video again from the help button, ask Anee how to do it, or send us a message from the Contact page. A real person reads every message.'],
    ];

    $title = 'Easy to Use: Made by Filipino Farmers, for Filipino Farmers';
    $desc = 'anee.io is easy to use and easy to understand: a short video on every screen, Taglish help tags on the technical words, Anee in Tagalog, Bisaya, Ilocano or English, and offline mode for the field. Made and designed by Filipino farmers for Filipino farmers.';
@endphp

@section('title', $title)
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ route('how.easy') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="anee.io is easy to use: made by Filipino farmers, for Filipino farmers">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ route('how.easy') }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-phone.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    @php
        $ezpSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'WebPage', '@id' => route('how.easy') . '#page', 'url' => route('how.easy'), 'name' => $title, 'description' => $desc, 'inLanguage' => 'en-PH'],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'How It Works', 'item' => route('how')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Easy to Use', 'item' => route('how.easy')],
                ]],
                ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ezpSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<style>
    /* ===================== EASY TO USE, THE PAGE (ezp-) ===================== */
    .ezp { overflow-x: clip; }
    .ezp-hero { position: relative; overflow: hidden; background: radial-gradient(50rem 28rem at 100% 0%, #fde9a8, transparent 60%), radial-gradient(40rem 30rem at 0% 100%, #dcefc8, transparent 60%), #fffdf6; }
    .ezp-hero-grid { display: grid; gap: 2.5rem; align-items: center; padding-block: 3rem 4.5rem; }
    @media (min-width: 1024px) { .ezp-hero-grid { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 3.5rem; padding-block: 4.5rem 6rem; } }
    .ezp-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-size: .82rem; font-weight: 700; color: #6b7280; }
    .ezp-crumbs a { color: #4a7c2a; text-decoration: none; }
    .ezp-crumbs svg { width: .8rem; height: .8rem; opacity: .6; }
    .ezp-h1 { margin-top: 1.1rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.02em; line-height: 1.05; font-size: clamp(2.3rem, 5.6vw, 3.9rem); color: #14210c; text-wrap: balance; }
    .ezp-h1 .hp-shimmer { background-image: linear-gradient(100deg, #4a7c2a 0%, #84cc16 35%, #4a7c2a 60%, #2f5219 85%, #4a7c2a 100%); }
    .ezp-lede { margin-top: 1.4rem; max-width: 36rem; font-size: clamp(1.02rem, 1.6vw, 1.15rem); line-height: 1.7; color: #4b5563; }
    .ezp-lede b { color: #14210c; }
    .ezp-badge { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1.4rem; padding: .45rem .9rem .45rem .5rem; border-radius: 999px; font-size: .84rem; font-weight: 900; color: #14210c;
        background: #fff; box-shadow: 0 0 0 1.5px #f5c518, 0 12px 24px -16px rgb(184 138 0 / .6); }
    .ezp-badge i { display: grid; place-items: center; width: 1.9rem; height: 1.3rem; border-radius: .25rem; overflow: hidden; box-shadow: 0 0 0 1px rgb(0 0 0 / .08); }
    .ezp-badge i svg { display: block; width: 100%; height: 100%; }
    .ezp-ctas { margin-top: 1.8rem; display: flex; flex-wrap: wrap; gap: .8rem; }
    /* Anee greets you, in four languages. */
    .ezp-hello { position: relative; display: grid; place-items: center; min-height: 22rem; }
    .ezp-hello-face { position: relative; margin-top: 3.2rem; width: 11rem; aspect-ratio: 1; border-radius: 999px; overflow: hidden; box-shadow: 0 0 0 6px #fff, 0 0 0 10px #f5c518, 0 30px 60px -20px rgb(120 90 0 / .6); animation: ezpBob 6s ease-in-out infinite; }
    .ezp-hello-face img { width: 100%; height: 100%; object-fit: cover; }
    @keyframes ezpBob { 50% { translate: 0 -8px; } }
    .ezp-wave { position: absolute; width: 15rem; aspect-ratio: 1; border-radius: 999px; border: 2px solid rgb(245 197 24 / .5); animation: ezpWave 3s ease-out infinite; }
    .ezp-wave.w2 { animation-delay: 1s; } .ezp-wave.w3 { animation-delay: 2s; }
    @keyframes ezpWave { from { transform: scale(.7); opacity: .9; } to { transform: scale(1.7); opacity: 0; } }
    .ezp-bubble { position: absolute; top: 0; left: 50%; width: min(19rem, 88vw); padding: .85rem 1rem; border-radius: 1.2rem 1.2rem 1.2rem .35rem; background: #fff; transform: translateX(-30%);
        box-shadow: 0 20px 40px -20px rgb(20 33 12 / .5), 0 0 0 1px #ece4c8; }
    .ezp-bubble small { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #14210c; background: #fde68a; }
    .ezp-bubble p { margin-top: .4rem; font-size: .98rem; font-weight: 700; line-height: 1.45; color: #14210c; transition: opacity .3s var(--hp-ease), transform .3s var(--hp-ease); }
    .ezp-bubble.is-swap p, .ezp-bubble.is-swap small { opacity: 0; transform: translateY(6px); }
    .ezp-bubble small { transition: opacity .3s var(--hp-ease), transform .3s var(--hp-ease); }
    .ezp-langs { position: absolute; bottom: 0; left: 50%; display: flex; gap: .4rem; transform: translateX(-50%); }
    .ezp-langs span { padding: .3rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 900; color: #4b5563; background: #fff; box-shadow: 0 0 0 1px #e5e7eb; transition: background-color .3s, color .3s, box-shadow .3s; }
    .ezp-langs span.is-on { color: #14210c; background: #f5c518; box-shadow: none; }
    @media (max-width: 479.98px) { .ezp-langs span { font-size: .66rem; padding: .25rem .5rem; } .ezp-hello-face { width: 9rem; } }

    /* Every screen teaches itself. */
    .ezp-tut { background: linear-gradient(180deg, #f6faf0, #fff); }
    .ezp-tut-grid { display: grid; gap: 2.5rem; align-items: center; margin-top: 3rem; }
    @media (min-width: 900px) { .ezp-tut-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .ezp-card { max-width: 24rem; margin: 0 auto; border-radius: 1.6rem; overflow: hidden; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 40px 70px -40px rgb(20 33 12 / .6); }
    .ezp-card-v { position: relative; display: grid; place-items: center; aspect-ratio: 16 / 10; background: linear-gradient(135deg, #2f5219, #6b9f3d); overflow: hidden; }
    .ezp-card-v::after { content: ''; position: absolute; left: 0; bottom: 0; height: 4px; width: 0; background: #f5c518; animation: ezpProg 6s linear infinite; }
    @keyframes ezpProg { to { width: 100%; } }
    .ezp-card-v img { width: 7rem; height: 7rem; animation: ezpBob 4s ease-in-out infinite; }
    .ezp-card-v b { position: absolute; left: .9rem; top: .8rem; padding: .25rem .6rem; border-radius: .6rem; font-size: .72rem; font-weight: 900; color: #fff; background: rgb(0 0 0 / .3); }
    .ezp-card-t { padding: 1.1rem 1.2rem 1.3rem; }
    .ezp-card-t h3 { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #14210c; }
    .ezp-card-t p { margin-top: .35rem; font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .ezp-card-b { display: flex; gap: .5rem; margin-top: 1rem; }
    .ezp-card-b span { flex: 1; padding: .6rem; border-radius: .8rem; font-size: .8rem; font-weight: 800; text-align: center; color: #2f5219; background: #f3f8ec; }
    .ezp-card-b span:last-child { color: #6b7280; background: transparent; box-shadow: inset 0 0 0 1px #e5e7eb; }
    .ezp-screens { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: 1.4rem; }
    .ezp-screens span { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .75rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #2f5219; background: #fff; box-shadow: inset 0 0 0 1.5px #c9dcb4;
        opacity: 0; transform: scale(.8); transition: opacity .4s var(--hp-ease), transform .5s cubic-bezier(.34,1.56,.64,1); transition-delay: calc(var(--k) * .05s); }
    .ezp-screens.is-in span { opacity: 1; transform: none; }
    .ezp-screens span::before { content: '▶'; font-size: .55rem; color: #f5a00b; }

    /* Tap a word. */
    .ezp-tl { background: radial-gradient(40rem 24rem at 50% 0%, #fff4c9, transparent 60%), #fffdf6; }
    .ezp-words { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; max-width: 46rem; margin: 2.5rem auto 0; }
    .ezp-word { display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1rem; border-radius: 999px; font-size: .95rem; font-weight: 800; color: #14210c; cursor: pointer; background: #fff;
        box-shadow: 0 0 0 1.5px #ece4c8; transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), background-color .28s; }
    .ezp-word i { padding: .1rem .4rem; border-radius: 999px; font-style: normal; font-size: .62rem; font-weight: 900; background: #fde68a; }
    .ezp-word:hover { transform: translateY(-2px); }
    .ezp-word.is-on { background: #14210c; color: #fff; box-shadow: 0 14px 26px -14px rgb(20 33 12 / .7); }
    .ezp-word.is-on i { color: #14210c; }
    .ezp-mean { max-width: 40rem; margin: 1.4rem auto 0; padding: 1.3rem 1.4rem; border-radius: 1.4rem; background: #fffbe8; box-shadow: 0 0 0 1.5px #fde68a, 0 24px 46px -30px rgb(120 90 0 / .55); }
    .ezp-mean b { display: block; font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #14210c; }
    .ezp-mean p { margin-top: .45rem; font-size: 1rem; line-height: 1.6; color: #3f3a1e; }
    .ezp-mean.is-swap b, .ezp-mean.is-swap p { opacity: 0; transform: translateY(6px); }
    .ezp-mean b, .ezp-mean p { transition: opacity .3s var(--hp-ease), transform .3s var(--hp-ease); }

    /* Built for the field. */
    .ezp-field { display: grid; gap: 1rem; max-width: 62rem; margin: 3rem auto 0; }
    @media (min-width: 720px) { .ezp-field { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .ezp-f { position: relative; padding: 1.35rem; border-radius: 1.4rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 22px 44px -34px rgb(20 33 12 / .55); transition: transform .28s var(--hp-ease); }
    .ezp-f:hover { transform: translateY(-4px); }
    .ezp-f em { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 1rem; font-style: normal; font-size: 1.45rem; background: #f3f8ec; }
    .ezp-f b { display: block; margin-top: .8rem; font-family: var(--font-heading); font-size: 1.08rem; font-weight: 800; color: #14210c; }
    .ezp-f p { margin-top: .35rem; font-size: .9rem; line-height: 1.6; color: #4b5563; }

    /* Philippine, counted. */
    .ezp-ph { position: relative; overflow: hidden; color: #fff; background: radial-gradient(40rem 26rem at 100% 0%, rgb(245 197 24 / .18), transparent 60%), linear-gradient(160deg, #1c3a12, #0f1f09); }
    .ezp-ph-sun { position: absolute; right: -6rem; top: -6rem; width: 26rem; opacity: .12; animation: ezpSpin 60s linear infinite; pointer-events: none; }
    .ezp-ph-sun svg { width: 100%; fill: #f5c518; }
    @keyframes ezpSpin { to { transform: rotate(360deg); } }
    .ezp-nums { display: grid; gap: .9rem; max-width: 62rem; margin: 3rem auto 0; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 900px) { .ezp-nums { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .ezp-num { padding: 1.3rem 1.2rem; border-radius: 1.3rem; background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .14); }
    .ezp-num b { display: block; font-family: var(--font-heading); font-size: clamp(1.5rem, 3.4vw, 2.2rem); font-weight: 900; line-height: 1; color: #f5c518; }
    .ezp-num span { display: block; margin-top: .45rem; font-size: .9rem; font-weight: 700; line-height: 1.45; color: #d6e2cb; }
    .ezp-pledge { max-width: 46rem; margin: 2.6rem auto 0; text-align: center; font-family: var(--font-heading); font-size: clamp(1.3rem, 3vw, 1.9rem); font-weight: 800; line-height: 1.3; }
    .ezp-pledge em { font-style: normal; color: #f5c518; }

    .ezp-faq { max-width: 50rem; margin: 2.5rem auto 0; display: grid; gap: .7rem; }
    .ezp-faq details { border-radius: 1.1rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9; overflow: hidden; }
    .ezp-faq summary { display: flex; align-items: center; gap: .8rem; padding: 1rem 1.15rem; cursor: pointer; list-style: none; font-weight: 800; color: #14210c; }
    .ezp-faq summary::-webkit-details-marker { display: none; }
    .ezp-faq summary::after { content: ''; margin-left: auto; flex: none; width: .65rem; height: .65rem; border-right: 2.5px solid #4a7c2a; border-bottom: 2.5px solid #4a7c2a; transform: rotate(45deg); transition: transform .28s var(--hp-ease); }
    .ezp-faq details[open] summary::after { transform: rotate(225deg); }
    .ezp-faq p { padding: 0 1.15rem 1.1rem; font-size: .95rem; line-height: 1.65; color: #4b5563; }

    @media (prefers-reduced-motion: reduce) {
        .ezp *, .ezp *::before, .ezp *::after { animation: none !important; transition: none !important; }
        .ezp-screens span { opacity: 1 !important; transform: none !important; }
    }
</style>
@endpush

@section('content')
<div class="ezp" id="ezp">

    {{-- ================= HERO ================= --}}
    <section class="ezp-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 ezp-hero-grid">
            <div>
                <nav class="ezp-crumbs animate-fade-up" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <a href="{{ route('how') }}">How It Works</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <span aria-current="page">Easy to Use</span>
                </nav>
                <h1 class="ezp-h1 animate-fade-up" style="animation-delay:.06s">
                    Farming Is Hard Enough.
                    <span class="hp-mark"><span class="hp-shimmer">The App Should Be Easy.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="ezp-lede animate-fade-up" style="animation-delay:.12s">
                    anee.io was made for the farmer in the field, not for the office. <b>Every screen teaches itself</b> with a short video,
                    <b>every hard word explains itself</b> with a Taglish tag, and Anee answers in the language you ask in.
                </p>
                <p class="ezp-badge animate-fade-up" style="animation-delay:.16s"><i aria-hidden="true"><svg viewBox="0 0 30 20" aria-hidden="true"><rect width="30" height="10" fill="#0038a8"/><rect y="10" width="30" height="10" fill="#ce1126"/><path d="M0 0 L17.3 10 L0 20 Z" fill="#fff"/><circle cx="5.8" cy="10" r="2.2" fill="#fcd116"/></svg></i>Made and designed by Filipino farmers, for Filipino farmers</p>
                <div class="ezp-ctas animate-fade-up" style="animation-delay:.2s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Try it free {!! $arrow !!}</a>
                    <a href="{{ route('tutorial') }}" class="hp-alt">Watch the tutorial</a>
                </div>
            </div>
            <div class="ezp-hello animate-fade-up" style="animation-delay:.14s" data-ezp-hello data-hellos='@json($hellos)' aria-label="Anee greets you in Tagalog, Bisaya, Ilocano and English">
                <span class="ezp-wave" aria-hidden="true"></span><span class="ezp-wave w2" aria-hidden="true"></span><span class="ezp-wave w3" aria-hidden="true"></span>
                <div class="ezp-bubble"><small data-ezp-lang>{{ $hellos[0][0] }}</small><p data-ezp-say>{{ $hellos[0][1] }}</p></div>
                <span class="ezp-hello-face"><img src="{{ $emo('wave') }}" alt="" width="256" height="256"></span>
                <div class="ezp-langs" aria-hidden="true">@foreach ($hellos as $i => $h)<span class="{{ $i === 0 ? 'is-on' : '' }}">{{ $h[0] }}</span>@endforeach</div>
            </div>
        </div>
    </section>

    {{-- ================= THE HOMEPAGE'S SECTION ================= --}}
    @include('public.partials.easy-section', ['signup' => $signup, 'ezOnPage' => true])

    {{-- ================= EVERY SCREEN TEACHES ITSELF ================= --}}
    <section class="hp-sec ezp-tut" aria-labelledby="ezpTutH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="ezp-tut-grid">
                <div class="reveal">
                    <p class="hp-kick">Video tutorials</p>
                    <h2 class="hp-h2" id="ezpTutH">Every Screen <em>Teaches Itself.</em></h2>
                    <p class="hp-p">The first time you open a screen, a short video shows what it is for and how to use it. Watch it, then close it for good with one tap. Want to see it again? It is one tap away, any time.</p>
                    <div class="ezp-screens" data-ezp-screens>
                        @foreach ($screens as $i => $sc)<span style="--k: {{ $i }}">{{ $sc }}</span>@endforeach
                    </div>
                </div>
                <div class="ezp-card reveal" style="--reveal-delay:.08s" aria-hidden="true">
                    <div class="ezp-card-v"><b>0:30</b><img src="{{ $emo('smile') }}" alt=""></div>
                    <div class="ezp-card-t">
                        <h3>Your activities, day by day</h3>
                        <p>Every task of your season on its day. Tap a task to see who does it and what it needs. Tick it when it is done.</p>
                        <div class="ezp-card-b"><span>Close</span><span>Don't show this again</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TAP A WORD ================= --}}
    @if ($tags)
    <section class="hp-sec ezp-tl" aria-labelledby="ezpTlH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Taglish help tags</p>
                <h2 class="hp-h2" id="ezpTlH">Tap a Word. <em>Understand It.</em></h2>
                <p class="hp-p">Farming is full of words like DAT, basal and panicle initiation. In anee.io each one wears a small Taglish tag. Tap it, and the meaning opens in plain Tagalog. Try it here, these are the app's own.</p>
            </div>
            <div class="ezp-words reveal" data-ezp-words data-tags='@json($tags)' role="tablist" aria-label="Farming words">
                @foreach ($tags as $i => $t)
                    <button type="button" class="ezp-word {{ $i === 0 ? 'is-on' : '' }}" data-i="{{ $i }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">{{ \Illuminate\Support\Str::before($t['en'], ' (') }}<i>Taglish</i></button>
                @endforeach
            </div>
            <div class="ezp-mean reveal" data-ezp-mean role="tabpanel" aria-live="polite">
                <b>{{ $tags[0]['en'] }}</b>
                <p>{{ $tags[0]['tl'] }}</p>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= BUILT FOR THE FIELD ================= --}}
    <section class="hp-sec" aria-labelledby="ezpFieldH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Built for the field</p>
                <h2 class="hp-h2" id="ezpFieldH">Made for Muddy Hands <em>and Weak Signal.</em></h2>
                <p class="hp-p">The field is not an office. anee.io was shaped around the places and the moments you actually use it.</p>
            </div>
            <div class="ezp-field">
                <div class="ezp-f reveal"><em>📶</em><b>Offline mode</b><p>Turn it on while you have signal, and your season opens in the field even without any.</p></div>
                <div class="ezp-f reveal" style="--reveal-delay:.06s"><em>📲</em><b>On your phone like an app</b><p>Add anee.io to your home screen and open it with one tap, no app store needed.</p></div>
                <div class="ezp-f reveal" style="--reveal-delay:.12s"><em>📸</em><b>A photo says it all</b><p>No need to describe a pest. Snap it, send it, and Anee reads what you see.</p></div>
                <div class="ezp-f reveal"><em>🎙️</em><b>Voice notes for the crew</b><p>Record a quick voice note on a day of the plan instead of typing it out.</p></div>
                <div class="ezp-f reveal" style="--reveal-delay:.06s"><em>🔠</em><b>Bigger text, clearer screens</b><p>Make the words bigger and the colors stronger in your settings, for eyes that work hard in the sun.</p></div>
                <div class="ezp-f reveal" style="--reveal-delay:.12s"><em>🌙</em><b>Night mode</b><p>Easy on the eyes at five in the morning, when the day's plan comes in.</p></div>
            </div>
        </div>
    </section>

    {{-- ================= PHILIPPINE, COUNTED ================= --}}
    <section class="hp-sec ezp-ph on-dark" aria-labelledby="ezpPhH">
        <div class="ezp-ph-sun" aria-hidden="true"><svg viewBox="0 0 100 100">@for ($r = 0; $r < 8; $r++)<path d="M50 4 L55 28 L50 23 L45 28 Z" transform="rotate({{ $r * 45 }} 50 50)"/>@endfor<circle cx="50" cy="50" r="18"/></svg></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6" style="position:relative">
            <div class="hp-head reveal">
                <p class="hp-kick">Gawa ng magsasakang Pilipino</p>
                <h2 class="hp-h2" id="ezpPhH">Made Here, for Here. <em>Para sa Magsasakang Pilipino.</em></h2>
                <p class="hp-p">Farming apps built for other countries ask about frost and dollars. anee.io was made and designed by Filipino farmers, so it knows the typhoon season, the palay price, the hatian and the products on the shelf of your agri supply.</p>
            </div>
            <div class="ezp-nums">
                <div class="ezp-num reveal"><b>{{ $crops }}</b><span>Philippine crops, by the names you use: palay, mais, sitaw, ampalaya, kamote</span></div>
                <div class="ezp-num reveal" style="--reveal-delay:.06s"><b>{{ number_format($products) }}+</b><span>products registered in the Philippines, Filipino owned companies first</span></div>
                <div class="ezp-num reveal" style="--reveal-delay:.12s"><b>Every town</b><span>its twenty years of typhoons, floods and droughts</span></div>
                <div class="ezp-num reveal"><b>{{ $pests }}</b><span>pests and diseases, with the local names farmers call them</span></div>
                <div class="ezp-num reveal" style="--reveal-delay:.06s"><b>₱</b><span>prices from Philippine markets, and when your harvest sells best</span></div>
                <div class="ezp-num reveal" style="--reveal-delay:.12s"><b>Hatian</b><span>farm sharing, Benta o Bilad, cavans and sacks, counted the way you do it</span></div>
            </div>
            <p class="ezp-pledge reveal">Gawa ng magsasakang Pilipino, <em>para sa magsasakang Pilipino.</em></p>
        </div>
    </section>

    {{-- ================= QUESTIONS ================= --}}
    <section class="hp-sec" aria-labelledby="ezpFaqH">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Questions</p>
                <h2 class="hp-h2" id="ezpFaqH">Not Sure It Is <em>for You?</em></h2>
            </div>
            <div class="ezp-faq">
                @foreach ($faqs as $i => [$q, $a])
                    <details class="reveal" @if ($i === 0) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
                @endforeach
            </div>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See How It Works</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('ezp');
    if (!root) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const seen = (el, fn, th) => {
        if (!el) return;
        if (!('IntersectionObserver' in window)) { fn(true); return; }
        new IntersectionObserver((es) => es.forEach((e) => fn(e.isIntersecting)), { threshold: th || .3 }).observe(el);
    };

    /* Anee says hello in four languages, one after another. */
    const hello = root.querySelector('[data-ezp-hello]');
    if (hello && !calm) {
        const list = JSON.parse(hello.dataset.hellos || '[]'), bubble = hello.querySelector('.ezp-bubble'), langs = [...hello.querySelectorAll('.ezp-langs span')];
        let k = 0, t = 0;
        const next = () => {
            k = (k + 1) % list.length;
            bubble.classList.add('is-swap');
            setTimeout(() => {
                hello.querySelector('[data-ezp-lang]').textContent = list[k][0];
                hello.querySelector('[data-ezp-say]').textContent = list[k][1];
                langs.forEach((l, i) => l.classList.toggle('is-on', i === k));
                bubble.classList.remove('is-swap');
            }, 300);
            t = setTimeout(next, 3200);
        };
        seen(hello, (on) => { clearTimeout(t); if (on) t = setTimeout(next, 3200); });
    }

    /* The screens that teach themselves pop in once seen. */
    const screens = root.querySelector('[data-ezp-screens]');
    seen(screens, (on) => { if (on) screens.classList.add('is-in'); });

    /* Tap a word: its meaning opens below. It also turns by itself until a word is tapped. */
    const words = root.querySelector('[data-ezp-words]'), mean = root.querySelector('[data-ezp-mean]');
    if (words && mean) {
        const tags = JSON.parse(words.dataset.tags || '[]'), btns = [...words.querySelectorAll('.ezp-word')];
        let auto = 0, touched = false, cur = 0;
        const show = (i) => {
            cur = i;
            btns.forEach((b, j) => { b.classList.toggle('is-on', j === i); b.setAttribute('aria-selected', j === i ? 'true' : 'false'); });
            mean.classList.add('is-swap');
            setTimeout(() => { mean.querySelector('b').textContent = tags[i].en; mean.querySelector('p').textContent = tags[i].tl; mean.classList.remove('is-swap'); }, calm ? 0 : 260);
        };
        btns.forEach((b, i) => b.addEventListener('click', () => { touched = true; clearTimeout(auto); show(i); }));
        const turn = () => { if (touched) return; show((cur + 1) % tags.length); auto = setTimeout(turn, 3600); };
        if (!calm) seen(words, (on) => { clearTimeout(auto); if (on && !touched) auto = setTimeout(turn, 3600); });
    }
})();
</script>
@endpush
