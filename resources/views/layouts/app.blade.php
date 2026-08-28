<!doctype html>
{{-- class="booting": the page's own content stays out of sight until it is
     whole (see partials.boot-veil-css). Stamped by the server rather than by
     a script, so there is no frame in which it has not been applied. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="booting">
<head>
    <meta charset="utf-8">
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
    {{-- Realtime endpoints resolved at request time rather than baked into the
         bundle, so a key change needs no rebuild and an unexpanded
         "${PUSHER_APP_KEY}" from a hosting dashboard can never reach Echo. --}}
    @if (\App\Support\RealtimeConfig::pusherKey())
        <meta name="pusher-key" content="{{ \App\Support\RealtimeConfig::pusherKey() }}">
        <meta name="pusher-cluster" content="{{ \App\Support\RealtimeConfig::pusherCluster() }}">
    @endif
    @if (\App\Support\RealtimeConfig::livekitUrl())
        <meta name="livekit-url" content="{{ \App\Support\RealtimeConfig::livekitUrl() }}">
    @endif
    {{-- Ahead of everything: the page is shown whole or not at all. --}}
    @include('partials.boot-veil-css')
    <title>@yield('title', 'Dashboard') | anee.io</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=anee">
    {{-- The SVG is for browsers that take one; the rest get a PNG at the two
         sizes they actually draw, and the .ico for the ones that only ever
         ask for that. --}}
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96.png') }}?v=anee">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v=anee">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v=anee">
    {{-- Installable: the app can live on the home screen, run without the
         browser's chrome, and raise notifications like anything else on the
         phone. --}}
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v=anee">
    {{-- The ground the app icon is drawn on, so an installed window
         and its icon are the same green. --}}
    <meta name="theme-color" content="#2B3A1C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="anee.io">
    {{-- Apple rounds its own corners and never masks further, so this one is
         full-bleed: a rounded square inside a rounded square shows a hairline
         of the wrong colour down every edge. --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=anee">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Nunito+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Applied before first paint so night mode never flashes white, and so
         a grower who needs bigger text never sees one frame of the small
         kind. Kept inline and dependency-free on purpose: this has to run
         before the stylesheet paints anything. --}}
    <script>
        (() => {
            const root = document.documentElement;
            const get = (k) => { try { return localStorage.getItem(k); } catch (_) { return null; } };
            const saved = get('anisystem-theme');
            const dark = saved ? saved === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            root.classList.toggle('dark', dark);
            // Mirrored into a cookie because the login page is served before
            // auth — localStorage stays the in-app source of truth, the cookie
            // is what lets a guest page match the mode pre-paint.
            try { document.cookie = 'anisystem-theme=' + (dark ? 'dark' : 'light') + ';path=/;max-age=31536000;SameSite=Lax'; } catch (_) {}
            // Accessibility: text size, contrast, motion, link underlines.
            root.dataset.fontScale = get('sm-a11y-font') || 'md';
            root.classList.toggle('sm-contrast', get('sm-a11y-contrast') === '1');
            root.classList.toggle('sm-still', get('sm-a11y-motion') === '1');
            root.classList.toggle('sm-underline', get('sm-a11y-underline') === '1');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    {{-- Five pages pushed their whole stylesheet to a stack nobody rendered,
         and shipped unstyled because of it: the wall, saved posts, co-farmers,
         the Global Gallery and the no-access page. Both names are honoured
         rather than renaming every push, so the next page to guess "styles"
         still works. --}}
    @stack('styles')
</head>
{{-- `body-class` lets a page opt into layout-level changes, e.g. the Collab
     Room hiding the mobile tab bar to use the full screen. --}}
<body class="min-h-screen flex flex-col bg-gray-50 @yield('body-class')">

    {{-- First thing in the body, so it is the first thing painted: the page
         is built behind it and shown whole. --}}
    @include('partials.boot-veil')

    {{-- Top app bar --}}
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14 md:h-16 gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    @hasSection('back')
                        <a href="@yield('back')" id="appBackLink" class="p-2 -ml-2 rounded-full text-gray-500 hover:bg-gray-100 shrink-0" aria-label="Back">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @else
                        <a href="{{ route('app.dashboard') }}" class="shrink-0">
                            {{-- The mark, not the wordmark: the words are six
                                 and a half times as wide as they are tall, and
                                 at this height that is two hundred pixels of a
                                 three-ninety screen with the page title still
                                 to come. --}}
                            <img src="{{ asset('images/logo-mark.png') }}?v=anee" alt="anee.io" class="h-8 md:h-9 w-auto">
                        </a>
                    @endif
                    <div class="min-w-0">
                        <h1 id="appPageTitle" class="text-base md:text-lg font-bold text-gray-900 truncate leading-tight">@yield('page-title', 'anee.io')</h1>
                        @hasSection('page-subtitle')
                            <p class="text-xs text-gray-500 truncate">@yield('page-subtitle')</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1 md:gap-5 shrink-0">
                    {{-- Desktop nav --}}
                    {{-- lg, not md: six links plus the logo leave a 768px
                         tablet's page title twelve pixels wide. --}}
                    <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold">
                        <a href="{{ route('app.dashboard') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('app.dashboard') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Dashboard</a>
                        <a href="{{ route('sm.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('sm.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Schedules</a>
                        {{-- Only where the farm's owner has left it open: a
                             worker whose grant says no community is refused at
                             the door, and a link that only refuses is worse
                             than no link. --}}
                        @if (\App\Support\WorkerContext::canUseCommunity())
                        <a href="{{ route('community.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('community.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Community</a>
                        @endif
                        <a href="{{ route('tutorials.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('tutorials.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Tutorials</a>
                        <a href="{{ route('support.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('support.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Support</a>
                        <a href="{{ route('account.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('account.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Account</a>
                    </nav>

                    {{-- How to use this page. Only appears where a module has
                         declared itself, so it never points at a guide that
                         does not describe what is on screen. It blinks until
                         the reader has opened it once — after that it is just
                         a button, per module, remembered in this browser. --}}
                    @hasSection('help-key')
                        <a href="#" id="appHelpBtn" data-help-key="@yield('help-key')"
                           class="help-btn" title="How to use this page" aria-label="How to use this page">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.6 9.4a2.5 2.5 0 014.9.6c0 1.2-1 1.7-1.8 2.2-.5.4-.7.8-.7 1.4M12 16.6h.01"/>
                            </svg>
                            <span class="help-btn-label">How to use</span>
                        </a>
                    @endif

                    <style>
                        /* Sits above the app (z-50 sheets) and below the drawing
                           pad (z-400), like the rest of the app's overlays. */
                        .notif-sheet {
                            /* Above the AI bubble, which parks itself at 60 and
                               climbs to 200 when opened — the two sit in the
                               same corner, and the sheet was arriving behind a
                               button. Still under the toasts (250) and the
                               drawing pad (400), which both belong on top. */
                            position: fixed; left: 0; right: 0; bottom: 0; z-index: 240;
                            background: var(--color-white, #fff);
                            border-top-left-radius: 1.1rem; border-top-right-radius: 1.1rem;
                            box-shadow: 0 -18px 50px -20px rgb(0 0 0 / .45);
                            max-height: min(78vh, 40rem); display: flex; flex-direction: column;
                            padding-bottom: env(safe-area-inset-bottom, 0px);
                        }
                        @media (min-width: 768px) {
                            /* On a desk it keeps its own width and sits in the
                               corner it came from, still rising from the bottom. */
                            .notif-sheet { left: auto; right: 1.25rem; width: 24rem; border-radius: 1.1rem; bottom: 1.25rem; }
                        }
                        .notif-grip { width: 2.5rem; height: .25rem; border-radius: 999px; background: #d1d5db; margin: .5rem auto .25rem; }
                        @media (min-width: 768px) { .notif-grip { display: none; } }
                        .notif-body { overflow-y: auto; -webkit-overflow-scrolling: touch; }
                        html.dark .notif-sheet { background: #151b12; }
                    </style>

                    {{-- "You are working on a saved file" — set by a module
                         (the map, so far) when what is on screen came from
                         somewhere and will go back there. Hidden until one
                         says so, because on a new file there is nothing to
                         say. window.setEditingNotice(text) turns it on;
                         calling it with nothing turns it off. --}}
                    <button type="button" id="editingNoticeBtn" hidden
                        class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full text-brand-700 bg-brand-50 hover:bg-brand-100 transition"
                        aria-label="What you are editing">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01"/></svg>
                    </button>
                    {{-- Where a module's own explanation goes when it is put
                         away: the line folds up into this, and this brings it
                         back. Driven by sm/partials/module-note. --}}
                    <button type="button" id="modSayBtn" hidden
                        class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full text-brand-700 bg-brand-50 hover:bg-brand-100 transition"
                        title="What this module is for" aria-label="What this module is for">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-5m0-3h.01"/></svg>
                    </button>

                    {{-- A page's own header controls, seated beside the bell —
                         the AI chat parks its session menu here instead of
                         spending a banner on it. --}}
                    @stack('appbar-actions')

                    {{-- Notification bell --}}
                    {{-- While this is open the page's floating buttons step
                         aside: the AI bubble and the team bubble both live in
                         the bottom-right corner, which is exactly where the
                         sheet arrives, and a button floating over a message
                         you are trying to read is not a button anyone wants. --}}
                    <div class="relative" x-data="notificationBell()" x-init="init()" @click.outside="open = false"
                        x-effect="document.documentElement.classList.toggle('overlay-open', open)">
                        <button type="button" @click="toggle()"
                            class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full text-gray-500 hover:bg-gray-100 transition"
                            aria-label="Notifications">
                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/></svg>
                            <span x-show="unread > 0" x-cloak
                                class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-red-500 text-white text-[0.625rem] font-bold inline-flex items-center justify-center"
                                x-text="unread > 99 ? '99+' : unread"></span>
                        </button>
                        {{-- A sheet from the bottom, not a dropdown pinned to a
                             corner. Every other list in the app arrives that way —
                             the day menu, the tools, the filters — and on a phone a
                             320px card hanging off the top-right corner was the one
                             thing you had to reach for rather than have handed to
                             you. Backdrop included, so a tap anywhere shuts it. --}}
                        <div x-show="open" x-cloak x-transition.opacity
                            class="fixed inset-0 z-[235] bg-black/40" @click="open = false"></div>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition transform ease-out duration-300"
                            x-transition:enter-start="translate-y-full"
                            x-transition:enter-end="translate-y-0"
                            x-transition:leave="transition transform ease-in duration-200"
                            x-transition:leave-start="translate-y-0"
                            x-transition:leave-end="translate-y-full"
                            class="notif-sheet">
                            <div class="notif-grip"></div>
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <p class="font-bold text-gray-900 text-sm">Notifications</p>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="markAll()" x-show="unread > 0"
                                        class="text-xs font-semibold text-brand-600 hover:text-brand-700">Mark all read</button>
                                    <button type="button" @click="open = false"
                                        class="btn-ghost p-1.5 rounded-full text-gray-400" aria-label="Close">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="notif-body">
                                <template x-if="loading">
                                    <div class="px-4 py-8 text-center text-sm text-gray-400">Loading…</div>
                                </template>
                                <template x-if="!loading && items.length === 0">
                                    <div class="px-4 py-10 text-center">
                                        <p class="text-sm text-gray-500">You're all caught up.</p>
                                    </div>
                                </template>
                                <template x-for="n in items" :key="n.id">
                                    <a :href="n.url || '#'" @click="open = false; if (!n.isRead) read(n)"
                                        class="block px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition"
                                        :class="!n.isRead && 'bg-brand-50/40'">
                                        <div class="flex items-start gap-2.5">
                                            <span class="mt-1 w-2 h-2 rounded-full shrink-0" :class="n.isRead ? 'bg-transparent' : 'bg-brand-500'"></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 leading-snug" x-text="n.title"></p>
                                                <p x-show="n.body" class="text-xs text-gray-500 mt-0.5 leading-snug" x-text="n.body"></p>
                                                <p class="text-[0.688rem] text-gray-400 mt-1" x-text="n.ago"></p>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Account dropdown --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 transition overflow-hidden"
                            aria-label="Account menu"
                            data-me-avatar data-initials="{{ auth()->user()->initials ?? '?' }}">
                            @if (auth()->user()?->avatarPath)
                                <img src="{{ \App\Support\MediaStore::url(auth()->user()->avatarPath) }}" alt="" class="w-full h-full object-cover">
                            @else
                                {{ auth()->user()->initials ?? '?' }}
                            @endif
                        </button>
                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 mt-2 w-60 card p-2 z-50">
                            <div class="px-3 py-2 border-b border-gray-100 mb-1">
                                <p class="font-bold text-gray-900 text-sm truncate">{{ auth()->user()->full_name ?? '' }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                            </div>
                            @if (\App\Support\UserHats::needsChoice(auth()->user()))
                                {{-- The same question login asks, for when the
                                     answer changes halfway through a day. --}}
                                <a href="{{ route('account.choose') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    Switch account
                                </a>
                            @endif
                            <a href="{{ route('account.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">My Account</a>
                            <a href="{{ route('account.subscription') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">My Subscription</a>
                            {{-- How the app behaves for this person: text size,
                                 contrast, movement. Account is who you are;
                                 this is how you read. --}}
                            <a href="{{ route('account.settings') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Settings</a>
                            {{-- Only where the device will actually take it:
                                 hidden once installed, and hidden entirely on
                                 a browser that cannot install. --}}
                            <button type="button" id="pwaInstallBtn" hidden
                                class="w-full flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-brand-700 hover:bg-brand-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                                Install to device
                            </button>
                            {{-- The way to reach a person. It existed as a page
                                 and had no door: nothing in the app linked to
                                 it, so a grower with a problem had nowhere to
                                 go but the phone. --}}
                            <a href="{{ route('support.index') }}" class="flex items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <span>Help &amp; Support</span>
                                @php $openTickets = \App\Models\SupportTicket::where('userId', auth()->id())->where('deleteStatus', 1)->whereIn('status', ['open', 'answered'])->count(); @endphp
                                @if ($openTickets)
                                    <span class="badge badge-green">{{ $openTickets }}</span>
                                @endif
                            </a>
                            <button type="button" id="themeToggle"
                                class="w-full flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                role="switch" aria-checked="false">
                                <span class="flex items-center gap-2">
                                    <svg id="themeIconMoon" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                    <svg id="themeIconSun" class="w-4 h-4 text-accent-500 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.66-6.66l-1.42 1.42M7.76 16.24l-1.42 1.42m12.32 0l-1.42-1.42M7.76 7.76L6.34 6.34M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    <span id="themeToggleLabel">Night mode</span>
                                </span>
                                <span class="theme-switch" aria-hidden="true"><span class="theme-switch-knob"></span></span>
                            </button>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50">Log Out</button>
                            </form>
                        </div>
                    </div>

                    {{-- Farm switcher (workers under one or more bosses) --}}
                    @php $__farmGrants = \App\Support\WorkerContext::grants(); @endphp
                    @if ($__farmGrants->isNotEmpty())
                        @php $__activeGrant = \App\Support\WorkerContext::activeGrant(); @endphp
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" title="Switch farm">
                            <button type="button" @click="open = !open"
                                class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full bg-brand-50 text-brand-700 hover:bg-brand-100 transition text-lg"
                                aria-label="Switch farm">🏡</button>
                            <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-64 card p-2 z-50">
                                <p class="px-3 py-1 text-xs font-bold text-gray-400 uppercase tracking-wide">Switch farm</p>
                                {{-- Your own land first, and only when there is
                                     any: someone who owns nothing has no farm to
                                     switch back to, and offering one would be a
                                     door onto an empty room. Posting 0 clears
                                     the choice, which is how "mine" is stored. --}}
                                @if (\App\Support\WorkerContext::ownsSchedules())
                                    <form method="POST" action="{{ route('worker.switch') }}">
                                        @csrf
                                        <input type="hidden" name="bossId" value="0">
                                        <button type="submit" class="w-full text-left rounded-lg px-3 py-2.5 text-sm {{ $__activeGrant === null ? 'bg-brand-50 text-brand-700 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                                            🏡 My farm
                                            <span class="text-xs text-gray-400">· Owner</span>
                                        </button>
                                    </form>
                                @endif
                                @foreach ($__farmGrants as $__g)
                                    <form method="POST" action="{{ route('worker.switch') }}">
                                        @csrf
                                        <input type="hidden" name="bossId" value="{{ $__g->bossUserId }}">
                                        <button type="submit" class="w-full text-left rounded-lg px-3 py-2.5 text-sm {{ optional($__activeGrant)->bossUserId === $__g->bossUserId ? 'bg-brand-50 text-brand-700 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                                            🌾 {{ optional($__g->boss)->full_name ?: 'Farm' }}
                                            <span class="text-xs text-gray-400">· {{ ucfirst($__g->scheduleAccess) }}</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <script>
        /* One line for any module to say what file is open. It shows an info
           button beside the bell, and tapping it says the same thing in
           words — a badge nobody can read is decoration. */
        window.setEditingNotice = function (text) {
            const btn = document.getElementById('editingNoticeBtn');
            if (!btn) return;
            btn.hidden = !text;
            btn.dataset.notice = text || '';
        };
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#editingNoticeBtn');
            if (!btn) return;
            const text = btn.dataset.notice || '';
            if (text && window.toast) window.toast(text);
        });

        // Top-bar notification bell (Alpine component). Defined before Alpine
        // inits so x-data="notificationBell()" resolves.
        window.notificationBell = function () {
            const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
            const urls = {
                index: @json(route('notifications.index')),
                count: @json(route('notifications.count')),
                read: @json(route('notifications.read')),
                readAll: @json(route('notifications.read-all')),
                seen: @json(route('notifications.seen')),
            };
            return {
                open: false, loading: false, items: [], unread: 0,
                init() {
                    this.refreshCount();
                    // The poll stays as the floor — a dropped broadcast costs
                    // a minute, not the message.
                    setInterval(() => { if (!this.open) this.refreshCount(); }, 60000);

                    /* The bell's own line. Anything addressed to this person
                       arrives the moment it happens: the count moves, the
                       bell shakes, and if the app is not what they are
                       looking at, the device says so too. */
                    const ME = {{ (int) auth()->id() }};
                    try {
                        window.Echo?.private('user.' + ME).listen('.notify', (p) => {
                            this.unread = (this.unread || 0) + 1;
                            if (this.open) this.load();
                            this.buzz();
                            window.smNotify?.({
                                title: p.title || 'anee.io',
                                body: p.body || '',
                                url: p.url || '/app',
                                tag: 'n' + (p.type || 'x'),
                            });
                        });
                    } catch (_) { /* no realtime here — the poll covers it */ }

                    // Asked for once the person is plainly using the app, not
                    // on the doorstep: a permission prompt on arrival is the
                    // fastest way to be told no forever.
                    setTimeout(() => { window.smAskToNotify?.(); }, 12000);
                },
                buzz() {
                    const el = this.$el?.querySelector('button');
                    if (!el) return;
                    el.classList.remove('bell-buzz');
                    void el.offsetWidth;
                    el.classList.add('bell-buzz');
                    setTimeout(() => el.classList.remove('bell-buzz'), 900);
                },
                async toggle() {
                    this.open = !this.open;
                    if (!this.open) return;
                    await this.load();
                    /* Seeing them is reading them.
                     *
                     * The badge counted every unread row while this panel
                     * shows the newest thirty, so anyone past thirty read all
                     * they could see and the number stayed up — which reads
                     * as the badge counting things already read. The rows
                     * keep their dots for this view; only the count settles.
                     *
                     * After a beat, so a panel opened and shut by accident
                     * does not silently clear the lot. */
                    clearTimeout(this._seenTimer);
                    this._seenTimer = setTimeout(async () => {
                        if (!this.open || this.unread === 0) return;
                        this.unread = 0;
                        try {
                            await fetch(urls.seen, { method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                                credentials: 'same-origin' });
                        } catch (_) { /* the poll will correct it */ }
                    }, 900);
                },
                async load() {
                    this.loading = true;
                    try {
                        const r = await fetch(urls.index, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        const d = await r.json();
                        this.items = d.data.items; this.unread = d.data.unread;
                    } catch (_) { /* keep prior state */ } finally { this.loading = false; }
                },
                async refreshCount() {
                    try {
                        const r = await fetch(urls.count, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        const d = await r.json(); this.unread = d.data.unread;
                    } catch (_) {}
                },
                async read(n) {
                    n.isRead = true; this.unread = Math.max(0, this.unread - 1);
                    try {
                        await fetch(urls.read, { method: 'POST', keepalive: true,
                            headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({ id: n.id }) });
                    } catch (_) {}
                },
                async markAll() {
                    this.items.forEach((n) => n.isRead = true); this.unread = 0;
                    try {
                        await fetch(urls.readAll, { method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
                    } catch (_) {}
                },
            };
        };
    </script>

    <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-4 md:py-8 app-enter grow">
        @yield('content')
    </main>

    {{-- Footer: legal links + breathing room at the bottom of every page.
         Extra bottom padding on phones clears the fixed tab bar. --}}
    <footer class="mt-6 md:mt-10 border-t border-gray-100 footer-safe">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-gray-400">© {{ date('Y') }} anee.io. All rights reserved.</p>
            <nav class="flex flex-wrap items-center justify-center text-xs" style="column-gap:1.75rem;row-gap:.5rem;" aria-label="Legal">
                <a href="{{ route('legal.show', ['slug' => 'privacy']) }}" class="text-gray-500 hover:text-brand-700 font-semibold">Privacy</a>
                <a href="{{ route('legal.show', ['slug' => 'terms']) }}" class="text-gray-500 hover:text-brand-700 font-semibold">Terms</a>
                <a href="{{ route('legal.show', ['slug' => 'cookies']) }}" class="text-gray-500 hover:text-brand-700 font-semibold">Cookies</a>
                <a href="{{ route('legal.show', ['slug' => 'about']) }}" class="text-gray-500 hover:text-brand-700 font-semibold">About</a>
            </nav>
        </div>
    </footer>

    {{-- Mobile bottom tab bar --}}
    <nav class="tabbar">
        <a href="{{ route('app.dashboard') }}" data-nav-loader class="tabbar-item {{ request()->routeIs('app.dashboard') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('sm.index') }}" data-nav-loader class="tabbar-item {{ request()->routeIs('sm.*') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 8h2m4 0h2m-8 4h2m4 0h2"/></svg>
            <span>Schedules</span>
        </a>
        @if (\App\Support\WorkerContext::canUseCommunity())
        <a href="{{ route('community.index') }}" data-nav-loader class="tabbar-item {{ request()->routeIs('community.*') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
            <span>Community</span>
        </a>
        @endif
        <a href="{{ route('account.index') }}" data-nav-loader class="tabbar-item {{ request()->routeIs('account.*') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Account</span>
        </a>
    </nav>

    {{-- Navigation veil: the app answers a tap on a place-to-go immediately,
         even when the next page is still being cooked. Opt-in via
         data-nav-loader on real links only — buttons that open sheets keep
         answering for themselves, and the module shell swaps panes without
         leaving the page, so it never wears this. --}}
    <style>
        .nav-loader { position: fixed; inset: 0; z-index: 300; display: flex; align-items: center; justify-content: center;
            background: rgb(249 250 251 / .72); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);
            opacity: 0; pointer-events: none;
            transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        /* pointer-events stay off until it is truly on, so a cancelled show
           can never trap a tap behind an invisible sheet of glass. */
        .nav-loader.is-on { opacity: 1; pointer-events: auto; }
        html.dark .nav-loader { background: rgb(10 14 8 / .72); }
        /* The card lifts in rather than appearing, so a fast navigation that
           never gets to show it does not flash a box at anybody. */
        .nav-loader .bv-card { transform: translateY(6px) scale(.98); opacity: 0;
            transition: transform .3s cubic-bezier(.22,1,.36,1), opacity .3s cubic-bezier(.22,1,.36,1); }
        .nav-loader.is-on .bv-card { transform: none; opacity: 1; }
        @media (prefers-reduced-motion: reduce) {
            .nav-loader { transition: none; }
            .nav-loader .bv-card { transition: none; transform: none; opacity: 1; }
        }

        /* One slow tide for every gradient that wants to look alive — accent
           lines, season covers, progress fills. Sites give the element a
           background-size over 100% on whichever axis should drift and play
           this at 8-14s alternate; layers sized to the box simply stay put.
           Each site carries its own reduced-motion guard, and html.sm-still
           blankets the lot. */
        @keyframes gradSweep {
            from { background-position: 0% 0%; }
            to   { background-position: 100% 100%; }
        }

        /* --- A filled thing that drifts ---
         *
         * The same tide as everything else, said once so four buttons on
         * three pages cannot drift apart in the code while drifting together
         * on the screen. The colour is three stops the caller sets; the
         * clock and the starting point are two more, so a column of these
         * never marches (a negative delay starts a button mid-sweep).
         */
        .sweep-fill {
            background-image: linear-gradient(120deg,
                var(--sw-1), var(--sw-2) 28%, var(--sw-3) 52%, var(--sw-2) 76%, var(--sw-1));
            background-size: 220% 100%;
            animation: gradSweep var(--sw-t, 10s) ease-in-out infinite alternate;
            animation-delay: var(--sw-d, 0s);
            border: 0;
        }
        .sweep-fill:hover { filter: brightness(1.06); }
        .sweep-green { --sw-1: #2f5219; --sw-2: #4a7c2a; --sw-3: #6b9f3d; color: #fff; }
        .sweep-amber { --sw-1: #a16207; --sw-2: #d97706; --sw-3: #f0a92a; color: #1a1a1a; }
        @media (prefers-reduced-motion: reduce) { .sweep-fill { animation: none; } }

        /* --- The page's own ground, by day ---
         *
         * White under white cards gives the eye no edge to hold, and a screen
         * read in a field at noon is the worst place to ask it to find one.
         * A soft grey puts the cards a step in front of the page without
         * taking any contrast off the words on them. Night keeps its own
         * ground, and so does the community, whose green is deliberate.
         */
        html:not(.dark) body:not(.plaza-ground) { background-color: #f1f3f5; }
    </style>
    {{-- Going somewhere. The card the activities board waits with, on every
         page in the app: a scene from the farm, a line, and the aside under
         it. A ring that turns says only "not broken yet"; this says what is
         happening and gives somebody something to read while it does. --}}
    <div id="navLoader" class="nav-loader" hidden aria-hidden="true">
        @include('sm.partials.wait-card')
    </div>
    <script>
        (() => {
            const veil = document.getElementById('navLoader');
            if (!veil) return;
            const clear = () => { veil.classList.remove('is-on'); veil.hidden = true; };
            // A different line every time it goes up.
            const roll = () => { try { window.rollWaitLine?.(veil.querySelector('.bv-card')); } catch (_) {} };
            document.addEventListener('click', (e) => {
                // Every real link, not only the ones that thought to ask:
                // a tap that is about to unload this page should say so.
                // data-no-nav-loader opts one out by hand; nothing else has
                // to be listed, because the check below waits to see whether
                // anybody claimed the click first.
                const a = e.target.closest('a[href]');
                if (!a || e.defaultPrevented) return;
                if (a.hasAttribute('data-no-nav-loader')) return;
                // Only a plain left-click in this tab is a navigation we will
                // witness: new-tab clicks and downloads leave this page alone.
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if ((a.target && a.target !== '_self') || a.hasAttribute('download')) return;
                const href = a.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                let url;
                try { url = new URL(a.href, location.href); } catch (_) { return; }
                if (url.origin !== location.origin) return;
                // A hash-jump within this page never unloads it.
                if (url.hash && url.pathname === location.pathname && url.search === location.search) return;
                /* Asked at the end of the queue, not here.
                 *
                 * Scripts pushed to the foot of the page register their
                 * click handlers AFTER this one, so a link they intercept
                 * still looks like a navigation at this moment — and a veil
                 * raised for a page that never leaves is a spinner nobody
                 * can dismiss. A zero timeout lands after every listener has
                 * had its say, when defaultPrevented finally tells the
                 * truth. */
                setTimeout(() => {
                    if (e.defaultPrevented) return;
                    roll();
                    veil.hidden = false;
                    requestAnimationFrame(() => veil.classList.add('is-on'));
                    // And if we are somehow still here — a handler that
                    // navigated by other means, a click that led nowhere —
                    // the veil gives up rather than sitting on the page.
                    setTimeout(clear, 5000);
                }, 0);
            });
            // Fired on every arrival including back/forward cache restores —
            // the one signal that always says "you can see the page again".
            window.addEventListener('pageshow', clear);
        })();
    </script>

    {{-- Messenger dock — community pages + the dashboard (which shows the
         co-farmer feed with its Message buttons). Kept off the schedule pages
         so it stays clear of the AI float. Needs plaza-css tokens, which both
         the community pages and the dashboard include.

         On the homepage the dock comes without its floating button: that
         corner belongs to the farm, and Messages is one tap away in the
         community. The dock itself stays, because the Message icon on every
         card there opens a chat window inside it. --}}
    @auth
        @php $msgrHome = request()->is('app') || request()->routeIs('app.dashboard'); @endphp
        @if ($msgrHome || request()->is('app/community') || request()->is('app/community/*'))
            @include('community.partials.messenger', ['launcher' => ! $msgrHome])
            {{-- Emoji picker + photo lightbox for the dock (guarded singletons —
                 safe even when the page already includes them). --}}
            @include('community.partials.emoji-js')
            @include('community.partials.lightbox-js')
        @endif
    @endauth

    @stack('sheets')
    <script>
        /* The question mark knows which guide it wants and how wide the screen
           holding it is — the server cannot measure either. It also blinks
           until it has been opened once for that module, because a help button
           nobody notices is the same as no help button. */
        (() => {
            const btn = document.getElementById('appHelpBtn');
            if (!btn) return;
            const KEY = 'helpSeen:';
            const base = @json(url('/app/help'));
            const device = () => {
                const w = Math.min(window.innerWidth, window.outerWidth || window.innerWidth);
                return w < 768 ? 'mobile' : (w < 1180 ? 'tablet' : 'desktop');
            };
            const seen = (k) => { try { return localStorage.getItem(KEY + k) === '1'; } catch (_) { return true; } };
            const markSeen = (k) => { try { localStorage.setItem(KEY + k, '1'); } catch (_) {} };

            function paint() {
                const key = btn.getAttribute('data-help-key') || '';
                btn.classList.toggle('is-new', !!key && !seen(key));
                btn.setAttribute('href', `${base}/${encodeURIComponent(key)}?device=${device()}`
                    + `&from=${encodeURIComponent(location.pathname + location.search)}`);
            }
            btn.addEventListener('click', () => {
                const key = btn.getAttribute('data-help-key') || '';
                if (key) markSeen(key);
                btn.classList.remove('is-new');
            });
            // The module shell swaps modules without reloading, so the button
            // has to follow whatever is on screen now.
            window.smHelpKey = (key) => { btn.setAttribute('data-help-key', key || ''); paint(); };
            window.addEventListener('resize', paint);
            paint();
        })();
    </script>

    {{-- Asked at most once, of someone who has used the app long enough to
         have a view, and never again once they answer. --}}
    @include('partials.review-prompt', ['askForReview' => \App\Http\Controllers\ReviewController::shouldAsk()])

    @stack('scripts')
    <script>
        @if (session('success')) toast(@json(session('success')), 'success'); @endif
        @if (session('error')) toast(@json(session('error')), 'error'); @endif

        // Night mode. The class is already on <html> from the head script; this
        // only keeps the switch in sync and handles flipping it.
        (() => {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggle');
            // Kept beside localStorage so the login page still shows the right
            // mode after a logout that follows a toggle on this very page.
            const remember = (dark) => {
                try { document.cookie = 'anisystem-theme=' + (dark ? 'dark' : 'light') + ';path=/;max-age=31536000;SameSite=Lax'; } catch (_) {}
            };

            const paint = () => {
                const dark = root.classList.contains('dark');
                document.getElementById('themeIconMoon')?.classList.toggle('hidden', dark);
                document.getElementById('themeIconSun')?.classList.toggle('hidden', !dark);
                const label = document.getElementById('themeToggleLabel');
                if (label) label.textContent = dark ? 'Day mode' : 'Night mode';
                btn?.setAttribute('aria-checked', dark ? 'true' : 'false');
                btn?.classList.toggle('is-on', dark);
            };

            btn?.addEventListener('click', () => {
                const dark = !root.classList.contains('dark');
                root.classList.add('theme-animating');
                root.classList.toggle('dark', dark);
                localStorage.setItem('anisystem-theme', dark ? 'dark' : 'light');
                remember(dark);
                paint();
                setTimeout(() => root.classList.remove('theme-animating'), 300);
            });

            // Follow the OS only while the user has not made an explicit choice.
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (localStorage.getItem('anisystem-theme')) return;
                root.classList.toggle('dark', e.matches);
                remember(e.matches);
                paint();
            });

            paint();
        })();
    </script>
</body>
</html>
