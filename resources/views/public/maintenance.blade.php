{{-- The international version is closed for maintenance (2026-10-05):
     App\Http\Middleware\PauseInternational answers its visits with this
     page and a 503. Standalone on purpose: the public layout's links would
     lead back onto the closed face. $account: an account set to another
     country (it may only log out); otherwise a visitor of the /en site. --}}
@php
    $toPh = preg_replace('#^/en(?=/|$)#', '', '/' . ltrim(request()->path(), '/')) ?: '/';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Closed for maintenance | anee.io</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=anee">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@600;700&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #1a2412; --soft: #5b6650; --line: #dfe8d2; --card: #ffffff; --ground: #f3f7ec; --green: #4a7c2a; --green-2: #3d6823; --sun: #f5c518; --ease: cubic-bezier(.22,1,.36,1); color-scheme: light; }
        @media (prefers-color-scheme: dark) {
            :root { --ink: #eef3e6; --soft: #a9b89b; --line: #2b3a1c; --card: #161f10; --ground: #0e140a; color-scheme: dark; }
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body { min-height: 100vh; min-height: 100dvh; display: grid; place-items: center; padding: max(1.5rem, env(safe-area-inset-top)) 1.25rem max(1.5rem, env(safe-area-inset-bottom));
            font-family: 'Nunito Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: var(--ink); background: var(--ground);
            background-image: radial-gradient(circle at 1px 1px, rgb(74 124 42 / .16) 1px, transparent 0); background-size: 22px 22px; }
        .mt { width: 100%; max-width: 30rem; text-align: center; animation: mtIn .7s var(--ease) both; }
        .mt-logo { display: inline-block; margin-bottom: 4.6rem; }
        .mt-logo img { display: block; height: 2rem; width: auto; }
        .mt-card { position: relative; padding: 2.6rem 1.6rem 1.8rem; border-radius: 1.5rem; background: var(--card); border: 1px solid var(--line);
            box-shadow: 0 1px 2px rgb(16 22 12 / .05), 0 24px 60px -28px rgb(16 22 12 / .35); }
        .mt-face { width: 6.2rem; height: 6.2rem; margin: -5.7rem auto 1rem; border-radius: 999px; overflow: hidden; background: #1a2c12;
            border: 3px solid var(--sun); box-shadow: 0 0 0 6px rgb(245 197 24 / .18); }
        .mt-face img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mt-kick { display: inline-flex; align-items: center; gap: .45rem; margin: 0 0 .7rem; padding: .3rem .8rem; border-radius: 999px;
            font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #6b4e00; background: rgb(245 197 24 / .22); }
        .mt-kick i { width: .5rem; height: .5rem; border-radius: 999px; background: var(--sun); animation: mtPulse 1.8s var(--ease) infinite; }
        h1 { margin: 0 0 .7rem; font-family: 'Instrument Sans', 'Nunito Sans', system-ui, sans-serif; font-size: clamp(1.45rem, 4.5vw, 1.8rem); line-height: 1.2; }
        p { margin: 0 0 .8rem; font-size: 1rem; line-height: 1.6; color: var(--soft); }
        .mt-acts { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; margin-top: 1.4rem; }
        .mt-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 2.9rem; padding: 0 1.3rem; border-radius: .85rem;
            font: inherit; font-weight: 800; font-size: .95rem; text-decoration: none; cursor: pointer; border: 1px solid transparent;
            transition: transform .28s var(--ease), background-color .28s var(--ease), border-color .28s var(--ease); }
        .mt-btn:active { transform: scale(.97); }
        .mt-go { color: #fff; background: linear-gradient(140deg, var(--green), var(--green-2)); }
        .mt-go:hover { transform: translateY(-1px); }
        .mt-alt { color: var(--ink); background: transparent; border-color: var(--line); }
        .mt-alt:hover { border-color: var(--green); }
        .mt-foot { margin-top: 1.3rem; font-size: .85rem; }
        .mt-foot a { color: var(--green); font-weight: 700; }
        @media (prefers-color-scheme: dark) { .mt-kick { color: #f5d46b; } .mt-foot a { color: #a8cc7e; } }
        @keyframes mtIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes mtPulse { 50% { opacity: .35; } }
        @media (prefers-reduced-motion: reduce) { .mt, .mt-kick i { animation: none; } .mt-btn { transition: none; } }
    </style>
</head>
<body>
    <main class="mt">
        <picture class="mt-logo">
            <source srcset="{{ asset('images/site/logo-white.png') }}?v=anee" media="(prefers-color-scheme: dark)">
            <img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io">
        </picture>
        <div class="mt-card">
            <div class="mt-face"><img src="{{ asset('images/anee/emoji/calm.png') }}" alt=""></div>
            <p class="mt-kick"><i aria-hidden="true"></i>Closed for maintenance</p>
            @if ($account)
                <h1>The international version is closed for now</h1>
                <p>Your account is set to {{ $countryName }}. We are getting anee.io ready for farmers outside the Philippines, so this version is closed while we work on it.</p>
                <p>Your account and your farm records are safe. It will open again soon.</p>
                <div class="mt-acts">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mt-btn mt-go">Log out</button>
                    </form>
                    <a class="mt-btn mt-alt" href="{{ url('/contact') }}">Contact us</a>
                </div>
            @else
                <h1>The international site is closed for now</h1>
                <p>We are getting anee.io ready for farmers outside the Philippines. This part of the site is closed while we work on it, and it will open again soon.</p>
                <p>The Philippine site is open as usual.</p>
                <div class="mt-acts">
                    <a class="mt-btn mt-go" href="{{ url('/face/ph') . '?to=' . urlencode($toPh) }}">Go to the Philippine site</a>
                </div>
                <p class="mt-foot">Questions? <a href="{{ url('/contact') }}">Contact us</a></p>
            @endif
        </div>
    </main>
</body>
</html>
