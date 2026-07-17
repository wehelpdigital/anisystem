<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AniSystem') — AniSystem by AniSenso</title>
    <meta name="description" content="@yield('meta_description', 'AniSystem — the cropping schedule manager for Filipino farmers by AniSenso. Plan lots, workers, materials, activities and irrigation in one mobile-friendly web app.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Nunito+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex flex-col bg-white">

    {{-- Header --}}
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-gray-100" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="{{ route('home') }}" class="flex items-center shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="AniSystem by AniSenso" class="h-10 md:h-12 w-auto">
                </a>

                <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-gray-700">
                    <a href="{{ route('home') }}" class="hover:text-brand-600 {{ request()->routeIs('home') ? 'text-brand-700' : '' }}">Home</a>
                    <a href="{{ route('about') }}" class="hover:text-brand-600 {{ request()->routeIs('about') ? 'text-brand-700' : '' }}">About</a>
                    <a href="{{ route('tutorial') }}" class="hover:text-brand-600 {{ request()->routeIs('tutorial') ? 'text-brand-700' : '' }}">Tutorial</a>
                    <a href="{{ route('contact') }}" class="hover:text-brand-600 {{ request()->routeIs('contact') ? 'text-brand-700' : '' }}">Contact Us</a>
                </nav>

                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <a href="{{ route('app.dashboard') }}" class="btn btn-accent btn-sm">Open My App</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log In</a>
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-sm">Get Started</a>
                    @endauth
                </div>

                <button type="button" class="md:hidden p-2 -mr-2 text-gray-700" @click="open = !open" aria-label="Menu">
                    <svg x-show="!open" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="open" x-cloak class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition.opacity class="md:hidden border-t border-gray-100 bg-white px-4 pb-5 pt-3 space-y-1">
            @foreach ([['home','Home'],['about','About'],['tutorial','Tutorial'],['contact','Contact Us']] as [$r, $label])
                <a href="{{ route($r) }}" class="block rounded-xl px-4 py-3 text-base font-semibold {{ request()->routeIs($r) ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
            <div class="pt-3 flex flex-col gap-2">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="btn btn-accent w-full">Open My App</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline w-full">Log In</a>
                    <a href="{{ route('signup') }}" class="btn btn-accent w-full">Get Started</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="grow">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 grid gap-10 md:grid-cols-3">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="AniSystem by AniSenso" class="h-10 w-auto mb-4">
                <p class="text-sm leading-relaxed text-gray-400">
                    AniSystem is the cropping schedule manager by AniSenso — empowering Filipino farmers with
                    education, technology, and quality products for a sustainable agricultural future.
                </p>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="hover:text-accent-500">About AniSystem</a></li>
                    <li><a href="{{ route('tutorial') }}" class="hover:text-accent-500">Tutorial</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-accent-500">Contact Us</a></li>
                    <li><a href="{{ route('signup') }}" class="hover:text-accent-500">Create an Account</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-accent-500">Log In</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Contact</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li>support@anisenso.com</li>
                    <li>Philippines</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 text-xs text-gray-500 flex flex-col sm:flex-row justify-between gap-2">
                <span>© {{ date('Y') }} AniSystem · An AniSenso product</span>
                <span>Helping Filipino farmers reach maximum yield and income</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
    <script>
        @if (session('success')) toast(@json(session('success')), 'success'); @endif
        @if (session('error')) toast(@json(session('error')), 'error'); @endif
    </script>
</body>
</html>
