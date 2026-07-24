<!doctype html>
<html lang="en" class="js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('share-title', 'Shared cropping plan') · AniSystem</title>

    {{-- Open Graph / social preview --}}
    <meta property="og:site_name" content="AniSystem">
    <meta property="og:type" content="@yield('og-type', 'article')">
    <meta property="og:title" content="@yield('og-title', 'Shared cropping plan')">
    <meta property="og:description" content="@yield('og-description', 'A cropping plan shared from AniSystem.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og-image', asset('images/logo.png'))">
    <meta property="og:image:alt" content="@yield('og-title', 'Shared cropping plan')">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og-title', 'Shared cropping plan')">
    <meta name="twitter:description" content="@yield('og-description', 'A cropping plan shared from AniSystem.')">
    <meta name="twitter:image" content="@yield('og-image', asset('images/logo.png'))">
    <meta name="description" content="@yield('og-description', 'A cropping plan shared from AniSystem.')">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Nunito+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="AniSystem" class="h-8 w-auto">
            </a>
            <a href="{{ route('signup') }}" class="btn btn-primary btn-sm">Plan your own season</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 md:py-8">
        @yield('content')
    </main>

    <footer class="max-w-3xl mx-auto px-4 sm:px-6 py-8 text-center text-xs text-gray-400">
        Shared with <a href="{{ route('home') }}" class="font-semibold text-brand-600">AniSystem</a> —
        plan lots, workers and a day-by-day cropping schedule.
    </footer>
</body>
</html>
