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
    @endphp
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="@yield('meta_description', 'anee.io — the cropping schedule manager for ' . \App\Support\Region::t('farmersOf') . '. Plan lots, workers, materials, activities and irrigation in one mobile-friendly web app.')">
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
</head>
<body class="min-h-screen flex flex-col bg-white">

    {{-- Shown whole or not at all, the same as inside the app. --}}
    @include('partials.boot-veil')

    {{-- Header. The full bar starts at lg: between md and lg the six links
         and three buttons did not fit and the page scrolled sideways. A page
         that sets @section('noHeader') goes without it: the ads landing
         page, where every way out of the offer is a lost signup. --}}
    @sectionMissing('noHeader')
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-gray-100" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="{{ route('home') }}" class="flex items-center shrink-0">
                    <img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io" class="h-7 md:h-8 w-auto">
                </a>

                <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-gray-700">
                    <a href="{{ route('home') }}" class="hover:text-brand-600 {{ request()->routeIs('home', 'ph.home') ? 'text-brand-700' : '' }}">Home</a>
                    <a href="{{ route('features') }}" class="hover:text-brand-600 {{ request()->routeIs('features', 'ph.features', 'site.features.show') ? 'text-brand-700' : '' }}">Features</a>
                    {{-- The guides, the problems and the blog, behind one word (the /ph face's: they are written for Philippine farms). --}}
                    @if (\App\Support\Region::ph())
                    <div class="relative" x-data="{ g: false }" @mouseenter="g = true" @mouseleave="g = false">
                        <button type="button" class="inline-flex items-center gap-1 hover:text-brand-600 {{ request()->routeIs('site.*') ? 'text-brand-700' : '' }}" @click="g = !g" :aria-expanded="g">
                            Guides
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="g && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div x-show="g" x-cloak x-transition.opacity.duration.200ms class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-72">
                            <div class="rounded-2xl bg-white shadow-card-lg ring-1 ring-black/5 p-2">
                                @foreach ([['crops', 'Crop guides', 'Palay, mais, gulay, coconut, banana'], ['problems', 'Crop problems', 'Pests, diseases and weeds'], ['blog', 'Blog', 'Fertilizer, pesticides, prices, Tagalog farm words']] as [$sec, $lab, $sub])
                                    <a href="{{ url('/' . $sec) }}" class="block rounded-xl px-3 py-2.5 hover:bg-brand-50">
                                        <span class="block text-sm font-bold text-gray-900">{{ $lab }}</span>
                                        <span class="block text-xs font-medium text-gray-500">{{ $sub }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('pricing') }}" class="hover:text-brand-600 {{ request()->routeIs('pricing', 'ph.pricing') ? 'text-brand-700' : '' }}">Pricing</a>
                    <a href="{{ route('about') }}" class="hover:text-brand-600 {{ request()->routeIs('about', 'ph.about') ? 'text-brand-700' : '' }}">About</a>
                    <a href="{{ route('contact') }}" class="hover:text-brand-600 {{ request()->routeIs('contact', 'ph.contact') ? 'text-brand-700' : '' }}">Contact</a>
                </nav>

                <div class="hidden lg:flex items-center gap-3">
                    @include('partials.face-switch')
                    @auth
                        <a href="{{ route('app.dashboard') }}" class="btn btn-accent btn-sm">Open My App</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log In</a>
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-sm">Get Started</a>
                    @endauth
                </div>

                <button type="button" class="lg:hidden p-2 -mr-2 text-gray-700" @click="open = !open" aria-label="Menu">
                    <svg x-show="!open" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="open" x-cloak class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition.opacity class="lg:hidden border-t border-gray-100 bg-white px-4 pb-5 pt-3 space-y-1">
            @foreach ([['home','Home'],['features','Features'],['pricing','Pricing'],['about','About'],['tutorial','Tutorial'],['contact','Contact Us']] as [$r, $label])
                <a href="{{ route($r) }}" class="block rounded-xl px-4 py-3 text-base font-semibold {{ request()->routeIs($r, 'ph.' . $r) ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ $label }}</a>
                @if ($r === 'features' && \App\Support\Region::ph())
                    <div class="grid grid-cols-3 gap-1.5 px-1 py-1">
                        @foreach ([['crops', 'Crop guides'], ['problems', 'Crop problems'], ['blog', 'Blog']] as [$sec, $lab])
                            <a href="{{ url('/' . $sec) }}" class="rounded-xl px-2 py-2.5 text-center text-sm font-bold {{ request()->is($sec, $sec . '/*') ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-800' }}">{{ $lab }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach
            <div class="pt-3 flex flex-col gap-2">
                @include('partials.face-switch', ['wide' => true])
                @auth
                    <a href="{{ route('app.dashboard') }}" class="btn btn-accent w-full">Open My App</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline w-full">Log In</a>
                    <a href="{{ route('signup') }}" class="btn btn-accent w-full">Get Started</a>
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
        $footCols = array_filter([['crops', 'Crop guides', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z'], ['problems', 'Crop problems', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'], ['blog', 'From the blog', 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z']], fn ($c) => ! empty($footLinks[$c[0]]));
    @endphp
    <section class="pf" aria-label="Site links">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-14">
            <div class="pf-grid {{ $footCols ? 'has-guides' : '' }}">
                <div class="pf-col">
                    <h4 class="pf-h"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Quick links</h4>
                    <ul class="pf-list">
                        <li><a href="{{ route('features') }}">Features</a></li>
                        <li><a href="{{ route('pricing') }}">Pricing</a></li>
                        <li><a href="{{ route('about') }}">About anee.io</a></li>
                        <li><a href="{{ route('tutorial') }}">Tutorial</a></li>
                        <li><a href="{{ route('contact') }}">Contact us</a></li>
                        <li><a href="{{ route('signup') }}">Create an account</a></li>
                        <li><a href="{{ route('login') }}">Log in</a></li>
                    </ul>
                </div>
                @foreach ($footCols as [$sec, $lab, $icon])
                    <div class="pf-col">
                        <h4 class="pf-h"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg><a href="{{ url('/' . $sec) }}">{{ $lab }}</a></h4>
                        <ul class="pf-list">
                            @foreach ($footLinks[$sec] as $l)<li><a href="{{ $l['url'] }}">{{ $l['label'] }}</a></li>@endforeach
                            <li><a href="{{ url('/' . $sec) }}" class="pf-all">See all ›</a></li>
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <style>
        .pf { background: linear-gradient(180deg, #f6faf1 0%, #eef6e6 100%); border-top: 1px solid #e1edd3; }
        .pf-grid { display: grid; gap: 2rem 1.5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pf-grid:not(.has-guides) { grid-template-columns: minmax(0, 1fr); }
        .pf-grid:not(.has-guides) .pf-list { display: flex; flex-wrap: wrap; gap: .4rem 1.4rem; }
        @media (min-width: 1024px) { .pf-grid.has-guides { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 2.5rem; } }
        .pf-h { display: flex; align-items: center; gap: .5rem; font-family: var(--font-heading); font-weight: 700; font-size: 1rem; color: #14210c; margin-bottom: .9rem; }
        .pf-h svg { width: 1.9rem; height: 1.9rem; padding: .4rem; border-radius: .65rem; background: #fff; color: #3d6823; box-shadow: 0 1px 0 #d9e9c6, 0 6px 14px -10px rgb(20 33 12 / .5); flex: none; }
        .pf-h a { color: inherit; text-decoration: none; }
        .pf-h a:hover { color: #3d6823; }
        .pf-list { display: grid; gap: .5rem; font-size: .9rem; }
        .pf-list a { color: #4b5563; text-decoration: none; transition: color .28s cubic-bezier(.22,1,.36,1), padding .28s cubic-bezier(.22,1,.36,1); }
        .pf-list a:hover { color: #3d6823; padding-left: .2rem; }
        .pf-list .pf-all { font-weight: 800; color: #3d6823; }
        @media (prefers-reduced-motion: reduce) { .pf-list a { transition: none; } }
    </style>

    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid gap-8 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] items-start">
            <div class="max-w-xl">
                {{-- Its own shape at any width: a squeezed column used to
                     stretch the wordmark sideways. --}}
                <img src="{{ asset('images/site/logo-white.png') }}?v=anee" alt="anee.io" class="block h-8 w-auto max-w-full object-contain object-left mb-4">
                <p class="text-sm leading-relaxed text-gray-400">
                    anee.io is the cropping schedule manager empowering {{ \App\Support\Region::t('farmersOf') }} with
                    education, technology, and quality products for a sustainable agricultural future.
                </p>
            </div>
            <div class="md:justify-self-end">
                <h4 class="text-white font-bold mb-3">Contact</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="mailto:support@anee.io" class="hover:text-accent-500">support@anee.io</a></li>
                    <li>Philippines</li>
                </ul>
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
