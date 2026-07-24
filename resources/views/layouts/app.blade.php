<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | AniSystem</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Nunito+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Applied before first paint so night mode never flashes white. --}}
    <script>
        (() => {
            const saved = localStorage.getItem('anisystem-theme');
            const dark = saved ? saved === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-gray-50">

    {{-- Top app bar --}}
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14 md:h-16 gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    @hasSection('back')
                        <a href="@yield('back')" class="p-2 -ml-2 rounded-full text-gray-500 hover:bg-gray-100 shrink-0" aria-label="Back">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @else
                        <a href="{{ route('app.dashboard') }}" class="shrink-0">
                            <img src="{{ asset('images/logo.png') }}" alt="AniSystem" class="h-8 md:h-9 w-auto">
                        </a>
                    @endif
                    <div class="min-w-0">
                        <h1 id="appPageTitle" class="text-base md:text-lg font-bold text-gray-900 truncate leading-tight">@yield('page-title', 'AniSystem')</h1>
                        @hasSection('page-subtitle')
                            <p class="text-xs text-gray-500 truncate">@yield('page-subtitle')</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1 md:gap-5 shrink-0">
                    {{-- Desktop nav --}}
                    <nav class="hidden md:flex items-center gap-1 text-sm font-semibold">
                        <a href="{{ route('app.dashboard') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('app.dashboard') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Dashboard</a>
                        <a href="{{ route('sm.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('sm.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Schedules</a>
                        <a href="{{ route('community.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('community.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Community</a>
                        <a href="{{ route('account.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('account.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Account</a>
                    </nav>

                    {{-- Notification bell --}}
                    <div class="relative" x-data="notificationBell()" x-init="init()" @click.outside="open = false">
                        <button type="button" @click="toggle()"
                            class="relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full text-gray-500 hover:bg-gray-100 transition"
                            aria-label="Notifications">
                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/></svg>
                            <span x-show="unread > 0" x-cloak
                                class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold inline-flex items-center justify-center"
                                x-text="unread > 99 ? '99+' : unread"></span>
                        </button>
                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] card p-0 z-50 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <p class="font-bold text-gray-900 text-sm">Notifications</p>
                                <button type="button" @click="markAll()" x-show="unread > 0"
                                    class="text-xs font-semibold text-brand-600 hover:text-brand-700">Mark all read</button>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
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
                                                <p class="text-[11px] text-gray-400 mt-1" x-text="n.ago"></p>
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
                            class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 transition"
                            aria-label="Account menu">
                            {{ auth()->user()->initials ?? '?' }}
                        </button>
                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 mt-2 w-60 card p-2 z-50">
                            <div class="px-3 py-2 border-b border-gray-100 mb-1">
                                <p class="font-bold text-gray-900 text-sm truncate">{{ auth()->user()->full_name ?? '' }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                            </div>
                            <a href="{{ route('account.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">My Account</a>
                            <a href="{{ route('account.subscription') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">My Subscription</a>
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
                </div>
            </div>
        </div>
    </header>

    <script>
        // Top-bar notification bell (Alpine component). Defined before Alpine
        // inits so x-data="notificationBell()" resolves.
        window.notificationBell = function () {
            const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
            const urls = {
                index: @json(route('notifications.index')),
                count: @json(route('notifications.count')),
                read: @json(route('notifications.read')),
                readAll: @json(route('notifications.read-all')),
            };
            return {
                open: false, loading: false, items: [], unread: 0,
                init() {
                    this.refreshCount();
                    setInterval(() => { if (!this.open) this.refreshCount(); }, 60000);
                },
                async toggle() {
                    this.open = !this.open;
                    if (this.open) await this.load();
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

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-4 md:py-8 page-safe-bottom app-enter">
        @yield('content')
    </main>

    {{-- Mobile bottom tab bar --}}
    <nav class="tabbar">
        <a href="{{ route('app.dashboard') }}" class="tabbar-item {{ request()->routeIs('app.dashboard') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('sm.index') }}" class="tabbar-item {{ request()->routeIs('sm.*') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 8h2m4 0h2m-8 4h2m4 0h2"/></svg>
            <span>Schedules</span>
        </a>
        <a href="{{ route('community.index') }}" class="tabbar-item {{ request()->routeIs('community.*') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
            <span>Community</span>
        </a>
        <a href="{{ route('account.index') }}" class="tabbar-item {{ request()->routeIs('account.*') ? 'is-active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Account</span>
        </a>
    </nav>

    @stack('sheets')
    @stack('scripts')
    <script>
        @if (session('success')) toast(@json(session('success')), 'success'); @endif
        @if (session('error')) toast(@json(session('error')), 'error'); @endif

        // Night mode. The class is already on <html> from the head script; this
        // only keeps the switch in sync and handles flipping it.
        (() => {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggle');

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
                paint();
                setTimeout(() => root.classList.remove('theme-animating'), 300);
            });

            // Follow the OS only while the user has not made an explicit choice.
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (localStorage.getItem('anisystem-theme')) return;
                root.classList.toggle('dark', e.matches);
                paint();
            });

            paint();
        })();
    </script>
</body>
</html>
