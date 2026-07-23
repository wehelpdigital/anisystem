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
                        <a href="{{ route('account.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('account.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">Account</a>
                    </nav>

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
    </script>
</body>
</html>
