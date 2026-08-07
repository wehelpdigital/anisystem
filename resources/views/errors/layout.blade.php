{{-- Self-contained error shell — no app layout, no DB, inline CSS — so it
     renders even when the database or a service provider is the thing that
     failed. Follows the SAME theme the app uses (the manual light/dark toggle
     saved in localStorage), not the OS setting. Extended by errors/{code}. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'AniSystem') }}</title>
    {{-- Match the app's theme before first paint (same key the app writes). --}}
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('anisystem-theme');
                const dark = saved ? saved === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.add(dark ? 'dark' : 'light');
            } catch (e) { /* localStorage blocked — default light */ }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Light is the default; .dark is toggled by the script above to match
           the app's saved theme. No prefers-color-scheme override, so a
           light-mode app on a dark-mode OS still shows a light error page. */
        :root {
            --brand: #4a7c2a; --brand-hover: #3d6823; --brand-50: #f3f8ec;
            --ink: #1c2412; --muted: #5b6b4a; --bg: #f6f8f1; --card: #ffffff;
            --border: #e4efd4; --shadow: 0 18px 50px rgba(40,70,20,.12);
        }
        :root.dark {
            --brand: #6aa540; --brand-hover: #7cb852; --brand-50: #1a2414;
            --ink: #eef3e6; --muted: #9fb389; --bg: #10160c; --card: #171f10;
            --border: #24331a; --shadow: 0 18px 50px rgba(0,0,0,.5);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--ink); display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 1.5rem; -webkit-font-smoothing: antialiased;
        }
        .err-card {
            width: 100%; max-width: 30rem; background: var(--card); border: 1px solid var(--border);
            border-radius: 1.4rem; box-shadow: var(--shadow); padding: 2.75rem 2rem 2.25rem;
            text-align: center; position: relative; overflow: hidden;
            animation: rise .5s cubic-bezier(.22,1,.36,1);
        }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .err-emoji {
            font-size: 3.4rem; line-height: 1; margin-bottom: .5rem; display: inline-block;
            animation: sway 4s ease-in-out infinite;
        }
        @keyframes sway { 0%,100% { transform: rotate(-5deg); } 50% { transform: rotate(5deg); } }
        .err-code {
            font-family: 'Fraunces', Georgia, serif; font-weight: 700; font-size: 3.75rem;
            line-height: 1; color: var(--brand); letter-spacing: -.02em; margin: .25rem 0 .1rem;
        }
        .err-title {
            font-family: 'Fraunces', Georgia, serif; font-weight: 600; font-size: 1.35rem;
            margin: 0 0 .5rem; color: var(--ink);
        }
        .err-msg { color: var(--muted); font-size: .975rem; line-height: 1.5; margin: 0 auto 1.6rem; max-width: 24rem; }
        .err-actions { display: flex; gap: .6rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: .4rem; font-weight: 600; font-size: .925rem;
            padding: .7rem 1.3rem; border-radius: .8rem; text-decoration: none; cursor: pointer;
            border: 1px solid transparent; transition: background .18s ease, transform .1s ease;
        }
        .btn:active { transform: scale(.97); }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-hover); }
        .btn-ghost { background: transparent; color: var(--brand); border-color: var(--border); }
        .btn-ghost:hover { background: var(--brand-50); }
        .err-ref { margin-top: 1.4rem; font-size: .75rem; color: var(--muted); opacity: .8; }
        .err-strip { position: absolute; left: 0; right: 0; top: 0; height: 5px; background: linear-gradient(90deg, var(--brand), var(--brand-hover)); }
    </style>
</head>
<body>
    <main class="err-card" role="alert">
        <div class="err-strip"></div>
        <span class="err-emoji">@yield('emoji', '🌾')</span>
        <div class="err-code">@yield('code')</div>
        <h1 class="err-title">@yield('title')</h1>
        <p class="err-msg">@yield('message')</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to safety</a>
            <a href="javascript:history.back()" class="btn btn-ghost">Go back</a>
        </div>
        @hasSection('ref')
            <p class="err-ref">@yield('ref')</p>
        @endif
    </main>
</body>
</html>
