<!doctype html>
{{-- class="booting": the page's own content stays out of sight until it is
     whole (see partials.boot-veil-css). --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="booting">
<head>
    <meta charset="utf-8">
    {{-- First in the head: some verifiers read only the top of a page. --}}
    @include('partials.site-verification')
    @include('partials.boot-veil-css')
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    {{-- Page pinch-zoom is off app-wide, on the owner's ask: the two places
         zoom belongs (the Google map, the image lightbox) implement their own
         and keep working — element handlers still receive their events. The
         meta covers Chrome/Android; Safari ignores it, so its gesture is
         refused by hand, and touch-action drops the browser's double-tap
         zoom while leaving pan and element-level pinch alone. --}}
    <style>html { touch-action: manipulation; }</style>
    <script>
        document.addEventListener('gesturestart', function (e) { e.preventDefault(); });
        document.addEventListener('gesturechange', function (e) { e.preventDefault(); });
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Enables the scroll-reveal hidden state only when JS is present, with a
         failsafe that shows everything if the JS bundle never boots — so
         content is never stuck invisible. --}}
    <script>
        document.documentElement.classList.add('js');
        window.addEventListener('load', function () {
            setTimeout(function () {
                if (!window.__revealBooted) {
                    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
                }
            }, 600);
        });
    </script>
    {{-- A page that writes its whole title (the guides: "... | anee.io") says so. --}}
    @php
        // A page that writes its whole title (the guides) says so; every other
        // page gives its name and the brand follows. Sections are escaped
        // when they are set, so they are printed as they are.
        $pageTitle = trim($__env->yieldContent('title_full'));
        if ($pageTitle === '') {
            $pageTitle = trim($__env->yieldContent('title', 'anee.io'));
            $pageTitle = $pageTitle === 'anee.io' ? $pageTitle : $pageTitle . ' | anee.io';
        }
        // A long title drops the brand at its end rather than run past what a
        // results page shows (2026-10-07 SEO audit): the page's own words matter more.
        if (mb_strlen(html_entity_decode($pageTitle)) > 65 && str_ends_with($pageTitle, ' | anee.io')) {
            $pageTitle = mb_substr($pageTitle, 0, -10);
        }
    @endphp
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="@yield('meta_description', 'anee.io is the smart farm app for ' . \App\Support\Region::t('farmersOf') . ': cropping calendar, costs, workers, field maps and Anee, your smart farm technician, in one app for any phone.')">
    {{-- Indexable only once the mother app's switch says so (App\Support\Seo). --}}
    <meta name="robots" content="{{ \App\Support\Seo::robots() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=anee">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Nunito+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Night mode for guests belongs to the login page alone: the app mirrors
         its theme choice into a cookie so a returning member's login screen
         matches the app they left. The marketing pages are a different
         audience and stay bright whatever that cookie says, so the pre-paint
         is emitted only for views that opt in with
         @section('honours-theme-cookie'). Inline and ahead of the stylesheet
         so dark never flashes white. No cookie means light — guests default
         bright, never the OS preference. --}}
    @hasSection('honours-theme-cookie')
        <script data-theme-prepaint>
            (() => {
                try {
                    if (/(?:^|;\s*)anisystem-theme=dark(?:;|$)/.test(document.cookie)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (_) {}
            })();
        </script>
    @endif
    <script>window.ANEE_REGION = @json(\App\Support\Region::js());</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Google AdSense, only where the free plan carries ads and only for the
         person who sees them (App\Support\Ads): a paid account never loads it. --}}
    @if (\App\Support\Ads::wantsAdsenseScript())
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ \App\Support\Ads::adsenseClient() }}" crossorigin="anonymous"></script>
    @endif
    @stack('head')
    <style>
        /* The phone menu (layouts/public). */
        /* Only the root is locked: a locked body would become the scroll box and
           carry the sticky header away with the page. */
        html.pm-lock { overflow: hidden; }
        /* A blurred header would hold the fixed panel inside itself
           (backdrop-filter makes a containing block), so a phone's is solid. */
        @media (max-width: 1023.98px) { .pm-host { background: #fff !important; -webkit-backdrop-filter: none !important; backdrop-filter: none !important; } }
        .pm-burger { position: relative; width: 2.75rem; height: 2.75rem; margin-right: -.5rem; border-radius: .9rem; display: grid; place-items: center; color: #374151; }
        .pm-burger span { position: absolute; left: .75rem; right: .75rem; height: 2px; border-radius: 2px; background: currentColor;
            transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .2s ease, top .28s cubic-bezier(.22,1,.36,1); }
        .pm-burger span:nth-child(1) { top: .95rem; } .pm-burger span:nth-child(2) { top: 1.34rem; } .pm-burger span:nth-child(3) { top: 1.73rem; }
        .pm-burger.is-open span:nth-child(1) { top: 1.34rem; transform: rotate(45deg); }
        .pm-burger.is-open span:nth-child(2) { opacity: 0; transform: scaleX(.3); }
        .pm-burger.is-open span:nth-child(3) { top: 1.34rem; transform: rotate(-45deg); }
        .pm { position: fixed; left: 0; right: 0; top: 4rem; bottom: 0; z-index: 39; display: flex; flex-direction: column;
            background: linear-gradient(180deg, #fff 0%, #f6faf1 100%); border-top: 1px solid #eef2ea; }
        @media (min-width: 768px) { .pm { top: 5rem; } }
        .pm-in, .pm-out { transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .pm-from { opacity: 0; transform: translateY(-.75rem); }
        .pm-to { opacity: 1; transform: none; }
        .pm-scroll { flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 1rem 1rem 1.25rem; }
        .pm-pages { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        .pm-page { display: flex; align-items: center; gap: .65rem; padding: .85rem .9rem; border-radius: 1rem; background: #fff; border: 1px solid #edf1e8;
            font-weight: 700; color: #1f2937; text-decoration: none; animation: pmRow .36s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 25ms);
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .pm-page svg { width: 1.25rem; height: 1.25rem; color: #4d7c2a; flex: none; }
        .pm-page.is-on { background: #eef6e6; border-color: #cfe3b8; color: #2f5219; }
        .pm-page:active { background: #f3f8ec; }
        .pm-label { margin: 1.25rem .25rem .6rem; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #4d7c2a; }
        .pm-guides { display: grid; gap: .5rem; }
        .pm-guide { display: flex; align-items: center; gap: .8rem; padding: .8rem .9rem; border-radius: 1rem; background: #fff; border: 1px solid #edf1e8; text-decoration: none;
            animation: pmRow .36s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 25ms); }
        .pm-guide.is-on { border-color: hsl(var(--h) 45% 72%); background: hsl(var(--h) 60% 97%); }
        .pm-guide b { display: block; font-family: var(--font-heading); font-size: .98rem; color: #14210c; }
        .pm-guide small { display: block; font-size: .78rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pm-gico { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .8rem; display: grid; place-items: center; color: hsl(var(--h) 60% 30%);
            background: linear-gradient(145deg, hsl(var(--h) 70% 94%), hsl(var(--h) 60% 86%)); }
        .pm-gico svg { width: 1.3rem; height: 1.3rem; }
        .pm-chev { width: 1rem; height: 1rem; margin-left: auto; color: #9ca3af; flex: none; }
        .pm-face { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.25rem; padding: .7rem .9rem; border-radius: 1rem;
            background: #fff; border: 1px solid #edf1e8; font-size: .85rem; font-weight: 700; color: #4b5563; }
        .pm-foot { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; padding: .85rem 1rem calc(.85rem + env(safe-area-inset-bottom, 0px));
            background: #fff; border-top: 1px solid #eef2ea; box-shadow: 0 -10px 24px -20px rgb(20 33 12 / .45); }
        .pm-foot .btn { width: 100%; justify-content: center; }
        .pm-foot .w-full { grid-column: 1 / -1; }
        /* Try and Ask Anee: the one gold door in the bar and at the top of the phone menu. */
        .ask-pill { display: inline-flex; align-items: center; gap: .45rem; padding: .3rem .8rem .3rem .3rem; border-radius: 999px;
            background: linear-gradient(135deg, #fff6d6, #fde68a); color: #3b2f00 !important; font-weight: 800; box-shadow: 0 0 0 1px #f3d36b inset;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); white-space: nowrap; }
        .ask-pill img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
        .ask-pill:hover, .ask-pill.is-on { transform: translateY(-1px); box-shadow: 0 0 0 1px #e8bf3a inset, 0 8px 18px -10px rgb(180 130 0 / .7); }
        .pm-ask { display: flex; align-items: center; gap: .8rem; margin-bottom: .75rem; padding: .8rem .9rem; border-radius: 1rem; text-decoration: none;
            background: linear-gradient(135deg, #fff6d6, #fde68a); box-shadow: 0 0 0 1px #f3d36b inset; }
        .pm-ask img { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
        .pm-ask b { display: block; font-family: var(--font-heading); font-size: 1rem; color: #3b2f00; }
        .pm-ask small { display: block; font-size: .8rem; color: #6b5300; }
        @media (prefers-reduced-motion: reduce) { .ask-pill { transition: none; } }
        /* A <details> that is closing still says [open] until its height has
           folded away; its icon turns back at the start, not the end. */
        details.is-closing > summary svg { transform: none !important; rotate: none !important; }
        /* Between lg and xl the bar is short of room: the logo is Home, About
           and Contact wait in the footer, and the pill says the short name. */
        @media (max-width: 1279.98px) { .nav-xl { display: none; } .ask-pill .ask-more { display: none; } }
        /* How It Works joined the bar (2026-10-01): below 1440 the logo is Home. */
        @media (max-width: 1439.98px) { .nav-home { display: none; } }
        @media (min-width: 1024px) and (max-width: 1279.98px) { header #pubNav { margin-left: 1.5rem; column-gap: 1.25rem; } }
        #pubNav ~ div .btn { white-space: nowrap; }
        /* The slot grows into the space between the links and the flags and
           centres the pill in it; the links keep their place after the logo. */
        @media (min-width: 1024px) {
            #pubNav { margin-left: 3.5rem; }
            .ask-slot { flex: 1 1 auto; min-width: 0; justify-content: center; padding: 0 .75rem; }
        }
        .ask-pill { font-size: .875rem; }
        /* The wordmark's letters sit in the lower part of the logo (the leaves
           and the dot of the i reach higher), so a logo centred by its box
           reads about 3px low against the links beside it. Lifted by its own
           height's 9%, the letters line up with the menu, the pill and the
           buttons (measured, 2026-10-01). */
        .pub-logo { transform: translateY(-9%); }
        @keyframes pmRow { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) {
            .pm-burger span, .pm-in, .pm-out { transition: none; }
            .pm-page, .pm-guide { animation: none; }
        }
    </style>
    @include('partials.face-switch-css')
    {{-- The footer's styles (they sat in the body, which a markup check counts an error). --}}
    <style>
        .pf { background: linear-gradient(180deg, #f6faf1 0%, #eef6e6 100%); border-top: 1px solid #e1edd3; }
        .pf-grid { display: grid; gap: 2rem 1.5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pf-grid:not(.has-guides) { grid-template-columns: minmax(0, 1fr); }
        .pf-grid:not(.has-guides) .pf-list { display: flex; flex-wrap: wrap; gap: .4rem 1.4rem; }
        @media (min-width: 1024px) { .pf-grid.has-guides { grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 2rem; } }
        .pf-h { display: flex; align-items: center; gap: .5rem; width: 100%; text-align: left; font-family: var(--font-heading); font-weight: 700; font-size: 1rem; color: #14210c; margin-bottom: .9rem; cursor: default; }
        .pf-plus { display: none; }
        .pf-fold { display: grid; grid-template-rows: 1fr; }
        .pf-fold > ul { overflow: hidden; }
        /* A phone folds each column to its heading; a tap opens it. */
        @media (max-width: 639.98px) {
            .pf-grid.has-guides { grid-template-columns: minmax(0, 1fr); gap: 0; }
            .pf-grid.has-guides .pf-col { border-bottom: 1px solid #dfeccf; }
            .pf-grid.has-guides .pf-h { margin: 0; padding: .85rem 0; cursor: pointer; }
            .pf-grid.has-guides .pf-plus { display: block; position: relative; margin-left: auto; width: .9rem; height: .9rem; }
            .pf-grid.has-guides .pf-plus::before, .pf-grid.has-guides .pf-plus::after { content: ""; position: absolute; left: 0; right: 0; top: 50%; height: 2px; margin-top: -1px; border-radius: 2px; background: #3d6823;
                transition: transform .28s cubic-bezier(.22,1,.36,1); }
            .pf-grid.has-guides .pf-plus::after { transform: rotate(90deg); }
            .pf-grid.has-guides .is-open .pf-plus::after { transform: rotate(0); }
            .pf-grid.has-guides .pf-fold { grid-template-rows: 0fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
            .pf-grid.has-guides .is-open .pf-fold { grid-template-rows: 1fr; }
            .pf-grid.has-guides .pf-list { padding-left: 2.4rem; }
            .pf-grid.has-guides .pf-list li:last-child { margin-bottom: 1rem; }
        }
        .pf-h svg { width: 1.9rem; height: 1.9rem; padding: .4rem; border-radius: .65rem; background: #fff; color: #3d6823; box-shadow: 0 1px 0 #d9e9c6, 0 6px 14px -10px rgb(20 33 12 / .5); flex: none; }
        .pf-list { display: grid; gap: .5rem; font-size: .9rem; }
        .pf-list a { color: #4b5563; text-decoration: none; transition: color .28s cubic-bezier(.22,1,.36,1), padding .28s cubic-bezier(.22,1,.36,1); }
        .pf-list a:hover { color: #3d6823; padding-left: .2rem; }
        .pf-list .pf-all { font-weight: 800; color: #3d6823; }
        @media (prefers-reduced-motion: reduce) { .pf-list a, .pf-fold, .pf-plus::before, .pf-plus::after { transition: none !important; } }
        /* Our tech ecosystem, in the footer (2026-10-07): each mark in one
           plain colour, brighter on hover; the house curve. */
        .te { margin-top: 1.4rem; padding-top: 1.2rem; border-top: 1px solid rgb(255 255 255 / .08); }
        .te-h { font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #f5c518; }
        .te-p { margin-top: .45rem; font-size: .8rem; line-height: 1.6; color: #9ca3af; }
        .te-list { margin-top: .8rem; display: flex; flex-wrap: wrap; gap: .4rem; }
        .te-list li { display: inline-flex; align-items: center; gap: .45rem; padding: .38rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 700; color: #d1d5db;
            background: rgb(255 255 255 / .05); border: 1px solid rgb(255 255 255 / .08); transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
        .te-list li:hover { color: #fff; border-color: rgb(255 255 255 / .22); background: rgb(255 255 255 / .09); }
        .te-mark { flex: none; width: 1rem; height: 1rem; background: currentColor; -webkit-mask: var(--m) center / contain no-repeat; mask: var(--m) center / contain no-repeat; }
        .te-fine { margin-top: .6rem; font-size: .74rem; color: #6b7280; }
        @media (prefers-reduced-motion: reduce) { .te-list li { transition: none; } }
        @media (min-width: 768px) { .ft-grid { grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 3rem; } }
        .ft-side { display: grid; gap: 1.6rem; align-content: start; }
        .ft-h { margin-bottom: .7rem; font-weight: 700; color: #fff; }
        .ft-list { display: grid; gap: .45rem; font-size: .86rem; color: #9ca3af; }
        .ft-list a { color: #9ca3af; text-decoration: none; transition: color .28s cubic-bezier(.22,1,.36,1); }
        .ft-list a:hover { color: #f5c518; }
        .ft-two { display: grid; gap: 1.6rem; }
        @media (min-width: 480px) { .ft-two { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.4rem; } }
        .ft-note { font-size: .72rem; line-height: 1.5; color: #6b7280; }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white">

    {{-- Shown whole or not at all, the same as inside the app. --}}
    @include('partials.boot-veil')

    {{-- Header. The full bar starts at lg: between md and lg the six links
         and three buttons did not fit and the page scrolled sideways. A page
         that sets @section('noHeader') goes without it: the ads landing
         page, where every way out of the offer is a lost signup. --}}
    @sectionMissing('noHeader')
    <header class="pm-host sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-gray-100" x-data="{ open: false }"
            x-effect="document.documentElement.classList.toggle('pm-lock', open)" @keydown.escape.window="open = false">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="{{ route('home') }}" class="flex items-center shrink-0">
                    <img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io" class="pub-logo h-7 md:h-8 w-auto">
                </a>

                <nav id="pubNav" class="hidden lg:flex items-center gap-7 text-sm font-semibold text-gray-700">
                    <a href="{{ route('home') }}" class="nav-xl nav-home hover:text-brand-600 {{ request()->routeIs('home', 'ph.home') ? 'text-brand-700' : '' }}">Home</a>
                    {{-- How It Works, with Features under it (2026-10-01). The words themselves still open the steps. --}}
                    <div class="relative" x-data="{ h: false }" @mouseenter="h = true" @mouseleave="h = false" @focusin="h = true" @focusout="h = $el.contains($event.relatedTarget)">
                        <a href="{{ route('how') }}" class="inline-flex items-center gap-1 whitespace-nowrap hover:text-brand-600 {{ request()->routeIs('how', 'ph.how', 'features', 'ph.features', 'site.features.show') ? 'text-brand-700' : '' }}" :aria-expanded="h">
                            How It Works
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="h && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </a>
                        <div x-show="h" x-cloak x-transition.opacity.duration.200ms class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-72">
                            <div class="rounded-2xl bg-white shadow-card-lg ring-1 ring-black/5 p-2">
                                <a href="{{ route('how') }}" class="block rounded-xl px-3 py-2.5 hover:bg-brand-50 {{ request()->routeIs('how', 'ph.how') ? 'bg-brand-50' : '' }}">
                                    <span class="block text-sm font-bold text-gray-900">How It Works</span>
                                    <span class="block text-xs font-medium text-gray-500">Seven steps, with Anee at every one</span>
                                </a>
                                <a href="{{ route('features') }}" class="block rounded-xl px-3 py-2.5 hover:bg-brand-50 {{ request()->routeIs('features', 'ph.features', 'site.features.show') ? 'bg-brand-50' : '' }}">
                                    <span class="block text-sm font-bold text-gray-900">Features</span>
                                    <span class="block text-xs font-medium text-gray-500">Every tool your farm runs on</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- The guides, the problems and the blog, behind one word (the /ph face's: they are written for Philippine farms). --}}
                    @if (\App\Support\Region::ph())
                    <div class="relative" x-data="{ g: false }" @mouseenter="g = true" @mouseleave="g = false">
                        <button type="button" class="inline-flex items-center gap-1 hover:text-brand-600 {{ request()->routeIs('site.*') ? 'text-brand-700' : '' }}" @click="g = !g" :aria-expanded="g">
                            Guides
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="g && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div x-show="g" x-cloak x-transition.opacity.duration.200ms class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-72">
                            <div class="rounded-2xl bg-white shadow-card-lg ring-1 ring-black/5 p-2">
                                @foreach ([['crops', 'Crop guides', 'Palay, mais, gulay, coconut, banana'], ['land-preparation', 'Land preparation', 'Per crop, and for sodic, acid and saline soil'], ['pests', 'Crop pests', 'Insects, kuhol, rats and birds on every crop'], ['diseases', 'Crop diseases', 'Fungi, bacteria and viruses on every crop'], ['weeds', 'Weeds and grasses', 'Grasses, sedges, broadleaves and their control'], ['blog', 'Latest in Agriculture', 'Farm news roundups, prices, fertilizer, farm words']] as [$sec, $lab, $sub])
                                    <a href="{{ url('/' . $sec) }}" class="block rounded-xl px-3 py-2.5 hover:bg-brand-50">
                                        <span class="block text-sm font-bold text-gray-900">{{ $lab }}</span>
                                        <span class="block text-xs font-medium text-gray-500">{{ $sub }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    {{-- Pricing, and under it the side by side table (2026-10-01). The word itself still opens the plans. --}}
                    <div class="relative" x-data="{ p: false }" @mouseenter="p = true" @mouseleave="p = false" @focusin="p = true" @focusout="p = $el.contains($event.relatedTarget)">
                        <a href="{{ route('pricing') }}" class="inline-flex items-center gap-1 hover:text-brand-600 {{ request()->routeIs('pricing', 'ph.pricing', 'pricing.compare', 'ph.pricing.compare') ? 'text-brand-700' : '' }}" :aria-expanded="p">
                            Pricing
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="p && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </a>
                        <div x-show="p" x-cloak x-transition.opacity.duration.200ms class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-72">
                            <div class="rounded-2xl bg-white shadow-card-lg ring-1 ring-black/5 p-2">
                                <a href="{{ route('pricing') }}" class="block rounded-xl px-3 py-2.5 hover:bg-brand-50 {{ request()->routeIs('pricing', 'ph.pricing') ? 'bg-brand-50' : '' }}">
                                    <span class="block text-sm font-bold text-gray-900">Plans and prices</span>
                                    <span class="block text-xs font-medium text-gray-500">Libre is free forever</span>
                                </a>
                                <a href="{{ route('pricing.compare') }}" class="block rounded-xl px-3 py-2.5 hover:bg-brand-50 {{ request()->routeIs('pricing.compare', 'ph.pricing.compare') ? 'bg-brand-50' : '' }}">
                                    <span class="block text-sm font-bold text-gray-900">Compare plans</span>
                                    <span class="block text-xs font-medium text-gray-500">Every plan side by side</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('about') }}" class="nav-xl hover:text-brand-600 {{ request()->routeIs('about', 'ph.about') ? 'text-brand-700' : '' }}">About</a>
                    <a href="{{ route('contact') }}" class="nav-xl hover:text-brand-600 {{ request()->routeIs('contact', 'ph.contact') ? 'text-brand-700' : '' }}">Contact</a>
                </nav>

                {{-- The featured door: one free question for Anee (AskAneeController).
                     Its own slot, taking the room between the last link and the
                     flags, so the pill stands in the middle of that gap. --}}
                <div class="ask-slot hidden lg:flex">
                    <a href="{{ url('/ask-anee') }}" class="ask-pill {{ request()->routeIs('ask.*') ? 'is-on' : '' }}">
                        <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" aria-hidden="true">
                        <span><span class="ask-more">Try and </span>Ask Anee</span>
                    </a>
                </div>

                <div class="hidden lg:flex items-center gap-3">
                    @include('partials.face-switch')
                    @auth
                        <a href="{{ route('app.dashboard') }}" class="btn btn-accent btn-sm">Open My App</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log In</a>
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-sm">Get Started</a>
                    @endauth
                </div>

                {{-- Three bars that fold into a cross. --}}
                <button type="button" class="pm-burger lg:hidden" :class="open && 'is-open'" @click="open = !open" :aria-expanded="open" aria-controls="pmPanel" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        {{-- The phone menu: a panel under the header, over the page. --}}
        @php
            $pmPages = [
                ['home', 'Home', 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10'],
                ['how', 'How It Works', 'M6 21a2 2 0 100-4 2 2 0 000 4zM18 7a2 2 0 100-4 2 2 0 000 4zM6 17V11a4 4 0 014-4h6M18 7v6a4 4 0 01-4 4H8'],
                ['features', 'Features', 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z'],
                ['pricing', 'Pricing', 'M7 7h.01M3 12l9-9h8v8l-9 9-8-8z'],
                ['pricing.compare', 'Compare plans', 'M9 17V7m0 10H5a2 2 0 01-2-2V9a2 2 0 012-2h4m0 10h6m-6-10h6m0 10V7m0 10h4a2 2 0 002-2V9a2 2 0 00-2-2h-4'],
                ['about', 'About', 'M12 11v6m0-10h.01M12 21a9 9 0 110-18 9 9 0 010 18z'],
                ['tutorial', 'Tutorial', 'M15 10l4.6-2.3A1 1 0 0121 8.6v6.8a1 1 0 01-1.4.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                ['contact', 'Contact', 'M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ];
            $pmGuides = [
                ['crops', 'Crop guides', 'Palay, mais, gulay, coconut, banana', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', 100],
                ['land-preparation', 'Land preparation', 'Per crop and per soil', 'M3 17l4-4 4 4 4-6 6 6M3 21h18M12 3v4m-4-2l1.5 1.5M16 5l-1.5 1.5', 60],
                ['pests', 'Crop pests', 'Insects, kuhol, rats and birds', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z', 30],
                ['diseases', 'Crop diseases', 'Fungi, bacteria and viruses', 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3zM9 12l2 2 4-4', 0],
                ['weeds', 'Weeds and grasses', 'Grasses, sedges and broadleaves', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', 80],
                ['blog', 'Latest in Agriculture', 'Farm news, prices, fertilizer, farm words', 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 150],
            ];
        @endphp
        <div id="pmPanel" class="pm lg:hidden" x-show="open" x-cloak
             x-transition:enter="pm-in" x-transition:enter-start="pm-from" x-transition:enter-end="pm-to"
             x-transition:leave="pm-out" x-transition:leave-start="pm-to" x-transition:leave-end="pm-from">
            <div class="pm-scroll">
                <a href="{{ url('/ask-anee') }}" class="pm-ask" style="--i: 0">
                    <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" aria-hidden="true">
                    <span class="min-w-0">
                        <b>Try and Ask Anee</b>
                        <small>Ask one farming question for free</small>
                    </span>
                    <svg class="pm-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <nav class="pm-pages" aria-label="Pages">
                    @foreach ($pmPages as $i => [$r, $label, $icon])
                        @php $on = request()->routeIs($r, 'ph.' . $r) || ($r === 'features' && request()->routeIs('site.features.show')); @endphp
                        <a href="{{ route($r) }}" class="pm-page {{ $on ? 'is-on' : '' }}" style="--i: {{ $i }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </nav>

                @if (\App\Support\Region::ph())
                    <p class="pm-label">Free farm guides</p>
                    <div class="pm-guides">
                        @foreach ($pmGuides as $i => [$sec, $lab, $sub, $icon, $hue])
                            <a href="{{ url('/' . $sec) }}" class="pm-guide {{ request()->is($sec, $sec . '/*') ? 'is-on' : '' }}" style="--h: {{ $hue }}; --i: {{ $i + 6 }}">
                                <span class="pm-gico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg></span>
                                <span class="min-w-0">
                                    <b>{{ $lab }}</b>
                                    <small>{{ $sub }}</small>
                                </span>
                                <svg class="pm-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="pm-face">
                    <span>Where you farm</span>
                    @include('partials.face-switch')
                </div>
            </div>
            <div class="pm-foot">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="btn btn-accent w-full">Open My App</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Log In</a>
                    <a href="{{ route('signup') }}" class="btn btn-accent">Get Started</a>
                @endauth
            </div>
        </div>
    </header>
    @endif

    <main class="grow">
        @yield('content')
    </main>

    {{-- Footer --}}
    {{-- The pre footer: every door on the site in one light band, so the
         dark footer under it only has to say who we are. The guide columns
         are the /ph face's (they are written for Philippine farms). --}}
    @php
        $footLinks = \App\Support\Region::ph() ? \App\Support\SitePages::footerLinks() : [];
        $footCols = array_filter([['crops', 'Crop guides', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z'], ['pests', 'Crop pests', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'], ['weeds', 'Weeds and grasses', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z'], ['blog', 'Latest in Agriculture', 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z']], fn ($c) => ! empty($footLinks[$c[0]]));
    @endphp
    <section class="pf" aria-label="Site links">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-14">
            <div class="pf-grid {{ $footCols ? 'has-guides' : '' }}">
                <div class="pf-col" x-data="{ o: false }" :class="o && 'is-open'">
                    <button type="button" class="pf-h" @click="o = !o" :aria-expanded="o"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><span>Quick links</span><i class="pf-plus" aria-hidden="true"></i></button>
                    <div class="pf-fold"><ul class="pf-list">
                        <li><a href="{{ route('how') }}">How it works</a></li>
                        <li><a href="{{ route('features') }}">Features</a></li>
                        <li><a href="{{ url('/ask-anee') }}">Ask Anee for free</a></li>
                        <li><a href="{{ url('/questions') }}">Farmers' questions</a></li>
                        <li><a href="{{ route('pricing') }}">Pricing</a></li>
                        <li><a href="{{ route('pricing.compare') }}">Compare plans</a></li>
                        <li><a href="{{ route('about') }}">About anee.io</a></li>
                        <li><a href="{{ route('tutorial') }}">Tutorial</a></li>
                        <li><a href="{{ route('contact') }}">Contact us</a></li>
                        <li><a href="{{ route('signup') }}">Create an account</a></li>
                        <li><a href="{{ route('login') }}">Log in</a></li>
                    </ul></div>
                </div>
                @foreach ($footCols as [$sec, $lab, $icon])
                    <div class="pf-col" x-data="{ o: false }" :class="o && 'is-open'">
                        <button type="button" class="pf-h" @click="o = !o" :aria-expanded="o"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg><span>{{ $lab }}</span><i class="pf-plus" aria-hidden="true"></i></button>
                        <div class="pf-fold"><ul class="pf-list">
                            @foreach ($footLinks[$sec] as $l)<li><a href="{{ $l['url'] }}">{{ $l['label'] }}</a></li>@endforeach
                            <li><a href="{{ url('/' . $sec) }}" class="pf-all">See all {{ strtolower($lab) === 'latest in agriculture' ? 'the latest' : strtolower($lab) }} ›</a></li>
                        </ul></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid gap-8 items-start ft-grid">
            <div class="max-w-xl">
                {{-- Its own shape at any width: a squeezed column used to
                     stretch the wordmark sideways. --}}
                <img src="{{ asset('images/site/logo-white.png') }}?v=anee" alt="anee.io" class="block h-8 w-auto max-w-full object-contain object-left mb-4">
                <p class="text-sm leading-relaxed text-gray-400">
                    anee.io is the smart farm app for {{ \App\Support\Region::t('farmersOf') }}: the cropping calendar,
                    every {{ \App\Support\Region::ph() ? 'peso' : 'dollar' }}, the workers, the field seen from space and Anee, your smart farm technician,
                    in one place. Built by farm technicians, for farms that last.
                </p>
                {{-- Our tech ecosystem (2026-10-07): what anee.io is built on.
                     Marks are shown as their owners publish them, in one
                     plain colour, never altered; a source with no published
                     mark (or one whose mark may not be used, like NASA's) is
                     named in words. --}}
                @php
                    $teMarks = [
                        // What anee.io is built on (owner, 2026-10-07).
                        ['Anthropic', 'anthropic', 'Claude, by Anthropic: one of the AI models Anee can think with'],
                        ['Google Gemini', 'googlegemini', 'Anee thinks with Gemini, by Google'],
                        ['Google Maps Platform', 'googlemaps', 'Maps, places and drawing your fields'],
                        ['Google Earth Engine', 'googleearthengine', 'Satellite analysis of your field'],
                        ['Copernicus Sentinel', null, 'European Space Agency satellites: Sentinel 2 pictures and Sentinel 1 radar'],
                        ['NASA GIBS', null, 'Himawari 9 cloud imagery, through NASA Global Imagery Browse Services'],
                        ['Open-Meteo', null, 'Weather forecasts and climate history'],
                        ['OpenWeather', null, 'Live rain, cloud and wind layers'],
                        ['RainViewer', null, 'Rain radar'],
                        ['GDACS', null, 'Typhoon tracks, from the Global Disaster Alert and Coordination System'],
                        ['ISRIC SoilGrids', null, 'Soil maps'],
                        ['OpenStreetMap', 'openstreetmap', 'Place search'],
                        ['Pusher', 'pusher', 'Live updates: team chat, the whiteboard and the board as it changes'],
                        ['LiveKit', 'livekit', 'Live video calls and cameras in the Collab Room'],
                        ['Cloudflare', 'cloudflare', 'A fast, safe connection'],
                        ['Laravel Cloud', 'laravel', 'Where anee.io runs: the Laravel framework on Laravel Cloud'],
                    ];
                @endphp
                <div class="te">
                    <h2 class="te-h">Our tech ecosystem</h2>
                    <p class="te-p">anee.io is built on the core, industry standard technology trusted by leading apps and research agencies: AI from Anthropic and Google, maps and satellite analysis from Google, satellites from the European Space Agency and NASA, weather from Open-Meteo and OpenWeather, and live chat and video calls from Pusher and LiveKit.</p>
                    <ul class="te-list">
                        @foreach ($teMarks as [$teName, $teIcon, $teWhat])
                            <li title="{{ $teWhat }}">
                                @if ($teIcon)
                                    <span class="te-mark" style="--m: url('{{ asset('images/tech/' . $teIcon . '.svg') }}')" aria-hidden="true"></span>
                                @endif
                                <span>{{ $teName }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="te-fine">Names and marks belong to their owners and are shown to say what anee.io uses. It does not mean they endorse anee.io.</p>
                </div>
            </div>
            {{-- The right column (2026-10-07): how to reach us, then the
                 agencies a Filipino farmer leans on and the newsrooms the
                 Latest in Agriculture roundups read. --}}
            @php
                $ftGov = [
                    ['Department of Agriculture', 'https://www.da.gov.ph'],
                    ['PhilRice', 'https://www.philrice.gov.ph'],
                    ['PAGASA weather', 'https://www.pagasa.dost.gov.ph'],
                    ['Bureau of Plant Industry', 'https://www.bpi.da.gov.ph'],
                    ['Fertilizer and Pesticide Authority', 'https://fpa.da.gov.ph'],
                    ['Agricultural Training Institute', 'https://ati.da.gov.ph'],
                    ['Philippine Statistics Authority', 'https://psa.gov.ph'],
                    ['IRRI', 'https://www.irri.org'],
                ];
                $ftNews = [
                    ['Philippine News Agency', 'https://www.pna.gov.ph'],
                    ['Inquirer', 'https://www.inquirer.net'],
                    ['GMA News', 'https://www.gmanetwork.com/news'],
                    ['Philstar', 'https://www.philstar.com'],
                    ['Rappler', 'https://www.rappler.com'],
                    ['ABS CBN News', 'https://www.abs-cbn.com/news'],
                    ['Daily Tribune', 'https://tribune.net.ph'],
                    ['The Manila Times', 'https://www.manilatimes.net'],
                    ['BusinessWorld', 'https://www.bworldonline.com'],
                ];
            @endphp
            <div class="ft-side">
                <div>
                    <h2 class="ft-h">Contact</h2>
                    <ul class="ft-list">
                        <li><a href="mailto:support@anee.io">support@anee.io</a></li>
                        <li>Philippines</li>
                    </ul>
                </div>
                @if (\App\Support\Region::ph())
                    <div class="ft-two">
                        <div>
                            <h2 class="ft-h">Government and research</h2>
                            <ul class="ft-list">
                                @foreach ($ftGov as [$ftName, $ftUrl])<li><a href="{{ $ftUrl }}" target="_blank" rel="noopener">{{ $ftName }}</a></li>@endforeach
                            </ul>
                        </div>
                        <div>
                            <h2 class="ft-h">Our news sources</h2>
                            <ul class="ft-list">
                                @foreach ($ftNews as [$ftName, $ftUrl])<li><a href="{{ $ftUrl }}" target="_blank" rel="noopener">{{ $ftName }}</a></li>@endforeach
                            </ul>
                        </div>
                    </div>
                    <p class="ft-note">Links to the agencies and newsrooms anee.io reads. They are not partners and do not endorse anee.io.</p>
                @endif
            </div>
        </div>
        <div class="border-t border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 text-xs text-gray-500 flex flex-col sm:flex-row justify-between gap-2">
                {{-- The legal pages, reachable before anyone signs up (inside the app
                     they sit in the app footer instead). --}}
                <span>© {{ date('Y') }} anee.io · <a href="{{ route('legal.show', ['slug' => 'privacy']) }}" class="hover:text-accent-500">Privacy</a> · <a href="{{ route('legal.show', ['slug' => 'terms']) }}" class="hover:text-accent-500">Terms</a> · <a href="{{ route('legal.show', ['slug' => 'cookies']) }}" class="hover:text-accent-500">Cookies</a> · <a href="{{ route('landing') }}" class="hover:text-accent-500">Start free</a></span>
                <span class="inline-flex items-center gap-1.5">
                    Helping {{ \App\Support\Region::t('farmersOf') }} reach maximum yield and income
                    <svg class="w-3.5 h-3.5 text-brand-500 footer-heart" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7.5-4.9-9.6-9A5.6 5.6 0 0 1 12 6.3a5.6 5.6 0 0 1 9.6 5.7C19.5 16.1 12 21 12 21z"/></svg>
                </span>
            </div>
        </div>
    </footer>

    @stack('scripts')
    <script>
        /* Every accordion on the public pages opens and shuts by gliding.
           A <details> on its own snaps, so its summary's click is taken
           here: the height runs from where it is to where it is going on
           the house easing, the answer fades up as it opens, and a click
           mid way turns it around from wherever it stands. A <details
           data-no-anim> keeps the browser's own snap. */
        (() => {
            const EASE = 'cubic-bezier(.22,1,.36,1)';
            const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const shut = (d, s) => {
                const cs = getComputedStyle(d);
                return s.offsetHeight + parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom)
                    + parseFloat(cs.borderTopWidth) + parseFloat(cs.borderBottomWidth);
            };
            document.addEventListener('click', (e) => {
                const s = e.target.closest('details > summary');
                if (!s || e.defaultPrevented) return;
                const d = s.parentElement;
                if (d.hasAttribute('data-no-anim') || still() || typeof d.animate !== 'function') return;
                e.preventDefault();
                const from = d.offsetHeight;
                if (d._glide) { d._glide.onfinish = null; d._glide.cancel(); }
                d.style.overflow = 'hidden';
                const closing = d.open && !d.classList.contains('is-closing');
                if (closing) {
                    d.classList.add('is-closing');
                    const a = d.animate({ height: [from + 'px', shut(d, s) + 'px'] }, { duration: 280, easing: EASE });
                    d._glide = a;
                    a.onfinish = () => { d.open = false; d.classList.remove('is-closing'); d.style.overflow = ''; d._glide = null; };
                    return;
                }
                d.classList.remove('is-closing');
                d.open = true;
                const to = d.offsetHeight;
                const a = d.animate({ height: [from + 'px', to + 'px'] }, { duration: 300, easing: EASE });
                d._glide = a;
                a.onfinish = () => { d.style.overflow = ''; d._glide = null; };
                [...d.children].filter((c) => c !== s).forEach((c) => c.animate(
                    { opacity: [0, 1], transform: ['translateY(-4px)', 'none'] }, { duration: 300, easing: EASE }));
            });
        })();
    </script>
    {{-- A form's first field is focused for a keyboard and a mouse only. On a
         phone, focusing it throws the keypad up over the page before anybody
         has read it (2026-09-30), so a touch screen waits to be tapped. --}}
    <script>
        (() => {
            const el = document.querySelector('[data-desktop-focus]');
            if (el && window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches) el.focus({ preventScroll: true });
        })();
    </script>
    <script>
        {{-- window.toast lives in the Vite module bundle, which runs after
             inline scripts parse — so flashes wait for DOMContentLoaded. --}}
        @if (session('success')) document.addEventListener('DOMContentLoaded', () => window.toast?.(@json(session('success')), 'success')); @endif
        @if (session('error')) document.addEventListener('DOMContentLoaded', () => window.toast?.(@json(session('error')), 'error')); @endif
    </script>
</body>
</html>
