<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — anee.io</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Nunito+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- The same pre-paint theme boot the client app runs, so an admin who
         reads at night is not flashed white on the way in. --}}
    <script>
        (() => {
            const root = document.documentElement;
            const get = (k) => { try { return localStorage.getItem(k); } catch (_) { return null; } };
            const saved = get('anisystem-theme');
            const dark = saved ? saved === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            root.classList.toggle('dark', dark);
            root.dataset.fontScale = get('sm-a11y-font') || 'md';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* THE ADMIN SHELL.
           Mobile first: one column, sticky header, the three sections as a
           segmented bar that is thumb-sized on a phone and simply narrow on a
           desk. Everything else is the client app's own classes — cards,
           buttons, sheets — because an admin should not have to learn a second
           dialect of the same app. */
        .ad-top { position: sticky; top: 0; z-index: 40;
            background: var(--color-white); border-bottom: 1px solid var(--color-gray-100);
            padding-top: env(safe-area-inset-top); }
        .ad-top-in { display: flex; align-items: center; gap: .6rem; padding: .6rem .9rem;
            max-width: 64rem; margin: 0 auto; }
        .ad-back { display: inline-flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: 999px; color: var(--color-gray-500); flex: none; }
        .ad-back:hover { background: var(--color-gray-100); color: var(--color-gray-900); }
        .ad-title { font-weight: 800; color: var(--color-gray-900); font-size: 1.02rem;
            font-family: var(--font-heading); line-height: 1.15; }
        .ad-sub { font-size: .7rem; color: var(--color-gray-400); font-weight: 600; }

        .ad-tabs { display: flex; gap: .25rem; max-width: 64rem; margin: 0 auto;
            padding: 0 .9rem .55rem; }
        .ad-tab { flex: 1 1 0; display: flex; align-items: center; justify-content: center; gap: .4rem;
            min-height: 2.5rem; border-radius: .7rem; font-size: .84rem; font-weight: 700;
            color: var(--color-gray-500); }
        .ad-tab:hover { background: var(--color-gray-50); color: var(--color-gray-800); }
        .ad-tab.is-on { background: var(--color-brand-50); color: var(--color-brand-800); }
        .ad-tab svg { width: 1.05rem; height: 1.05rem; flex: none; }
        @media (min-width: 640px) { .ad-tab { flex: 0 0 auto; padding: 0 1rem; } }

        .ad-main { max-width: 64rem; margin: 0 auto; padding: 1rem .9rem 4rem; }

        /* A NUMBER WITH A NAME — the dashboard's stat cards. */
        .ad-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
        @media (min-width: 768px) { .ad-stats { grid-template-columns: repeat(4, 1fr); } }
        .ad-stat { padding: .8rem .9rem; }
        .ad-stat b { display: block; font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900);
            font-family: var(--font-heading); line-height: 1.1; }
        .ad-stat span { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); margin-top: .15rem; }
        .ad-stat small { font-size: .7rem; font-weight: 700; color: var(--color-brand-700); }

        /* TWELVE MONTHS AS BARS. Divs, not a chart library: the question is
           "which month was bigger", and flexbox answers it in no kilobytes. */
        .ch-wrap { display: flex; align-items: flex-end; gap: .3rem; height: 8.5rem; padding-top: 1.1rem; }
        .ch-col { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: .25rem;
            min-width: 0; height: 100%; justify-content: flex-end; }
        .ch-bar { width: 100%; max-width: 2rem; border-radius: .3rem .3rem 0 0;
            background: var(--color-brand-500); min-height: 2px; position: relative;
            transition: height .28s cubic-bezier(.22,1,.36,1); }
        .ch-bar i { position: absolute; top: -1.05rem; left: 50%; transform: translateX(-50%);
            font-style: normal; font-size: .58rem; font-weight: 700; color: var(--color-gray-500); white-space: nowrap; }
        .ch-lbl { font-size: .58rem; font-weight: 600; color: var(--color-gray-400); }

        /* ONE ROW OF A LIST — a client, a ticket. */
        .ad-row { display: flex; align-items: center; gap: .7rem; padding: .75rem .85rem;
            width: 100%; text-align: left; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
        .ad-row:last-child { border-bottom: 0; }
        .ad-row:hover { background: var(--color-gray-50); }
        .ad-face { width: 2.4rem; height: 2.4rem; border-radius: 999px; flex: none;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .95rem; color: var(--color-brand-800); background: var(--color-brand-50); }
        .ad-mid { flex: 1 1 auto; min-width: 0; }
        .ad-name { display: block; font-weight: 700; font-size: .9rem; color: var(--color-gray-900);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-meta { display: block; font-size: .73rem; color: var(--color-gray-400);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-end { flex: none; text-align: right; }

        /* THE SCROLL'S OWN LOADER — visible while the next page travels. */
        .ad-more { display: flex; align-items: center; justify-content: center; gap: .5rem; }
        /* The hidden attribute loses to display:flex — say it louder. */
        .ad-more[hidden] { display: none; }
        .ad-more { 
            padding: 1.1rem; color: var(--color-gray-400); font-size: .8rem; font-weight: 600; }
        .ad-spin { width: 1.1rem; height: 1.1rem; border: 2px solid var(--color-gray-200);
            border-top-color: var(--color-brand-600); border-radius: 999px;
            animation: adspin .8s linear infinite; }
        @keyframes adspin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .ad-spin { animation-duration: 1.6s; } }

        /* Skeletons for the first paint, so loading looks like loading. */
        .ad-skel { border-radius: .6rem; background: var(--color-gray-100);
            animation: adskel 1.2s ease-in-out infinite alternate; }
        @keyframes adskel { from { opacity: .55; } to { opacity: 1; } }

        .ad-badge { display: inline-flex; align-items: center; font-size: .62rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .03em; padding: .12rem .45rem; border-radius: 999px; }
        .ad-badge.is-admin { background: #ede9fe; color: #5b21b6; }
        .ad-badge.is-susp { background: #fef2f2; color: #b91c1c; }
        .ad-badge.is-open { background: #fffbeb; color: #b45309; }
        .ad-badge.is-answered { background: var(--color-brand-50); color: var(--color-brand-800); }
        .ad-badge.is-closed { background: var(--color-gray-100); color: var(--color-gray-500); }
        .ad-dot { width: .5rem; height: .5rem; border-radius: 999px; background: #22c55e; display: inline-block; }

        /* The thread inside a ticket. */
        .tk-msg { max-width: 85%; border-radius: .9rem; padding: .55rem .75rem; font-size: .85rem;
            background: var(--color-gray-100); color: var(--color-gray-900); }
        .tk-msg.is-admin { background: var(--color-brand-600); color: #fff; margin-left: auto; }
        .tk-msg .tk-who { display: block; font-size: .64rem; font-weight: 800; opacity: .75; margin-bottom: .1rem; }
        .tk-msg .tk-at { display: block; font-size: .62rem; opacity: .6; margin-top: .2rem; }

        html.dark .ad-top { background: #10160c; border-color: #1f2917; }
        html.dark .ad-title { color: #e8efe1; }
        html.dark .ad-tab.is-on { background: #22301a; color: #cfe6b8; }
        html.dark .ad-tab:hover { background: #182112; color: #d8e4cb; }
        html.dark .ad-stat b { color: #e8efe1; }
        html.dark .ad-row { border-color: #222b1a; }
        html.dark .ad-row:hover { background: #161e10; }
        html.dark .ad-name { color: #e8efe1; }
        html.dark .ad-face { background: #22301a; color: #cfe6b8; }
        html.dark .ad-skel { background: #1c2417; }
        html.dark .tk-msg { background: #1c2417; color: #e8efe1; }
        html.dark .ad-badge.is-admin { background: #2d2748; color: #c4b5fd; }
        html.dark .ad-badge.is-susp { background: #3a1d1d; color: #fca5a5; }
        html.dark .ad-badge.is-open { background: #2c2213; color: #f0c274; }
        html.dark .ad-badge.is-closed { background: #1c2417; color: #9ca3af; }
    </style>
    @stack('head')
</head>
<body class="h-full bg-gray-50 dark:bg-[#0c1108] text-gray-900 antialiased">

    <header class="ad-top">
        <div class="ad-top-in">
            {{-- The way back. An admin is a client with a second hat, and the
                 first hat is one tap away. --}}
            <a href="/app" class="ad-back" title="Back to the client panel" aria-label="Back to the client panel">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="flex-1 min-w-0">
                <p class="ad-title">Admin panel</p>
                <p class="ad-sub">@yield('subtitle', 'anee.io')</p>
            </div>
            <span class="ad-face" title="{{ auth()->user()->email }}">{{ mb_strtoupper(mb_substr(auth()->user()->firstName ?? 'A', 0, 1)) }}</span>
        </div>
        <nav class="ad-tabs" role="tablist">
            <a href="{{ route('admin.dashboard') }}" class="ad-tab {{ request()->routeIs('admin.dashboard') ? 'is-on' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.clients') }}" class="ad-tab {{ request()->routeIs('admin.clients') ? 'is-on' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-1.33-7.77"/></svg>
                <span>Clients</span>
            </a>
            <a href="{{ route('admin.support') }}" class="ad-tab {{ request()->routeIs('admin.support') ? 'is-on' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-9 7l3.5-3.5H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14z"/></svg>
                <span>Support</span>
            </a>
        </nav>
    </header>

    <main class="ad-main">
        @yield('content')
    </main>

    @stack('sheets')

    <script>
        /* THE SCROLL THAT FEEDS ITSELF.
           One helper for every list in the panel: a sentinel at the bottom of
           the list asks for the next page when it comes into view, shows its
           spinner while the page travels, and retires when the server says
           there is no more. Search resets it. */
        window.adminFeed = function adminFeed(opts) {
            const list = document.getElementById(opts.listId);
            const more = document.getElementById(opts.moreId);
            let cursor = null;
            let busy = false;
            let done = false;
            let seq = 0;

            let page = async function () {
                if (busy || done) return;
                busy = true;
                const mySeq = seq;
                more.hidden = false;
                try {
                    const qs = new URLSearchParams(opts.params ? opts.params() : {});
                    if (cursor) qs.set('cursor', cursor);
                    const res = await api(opts.url + '?' + qs.toString(), { method: 'GET' });
                    if (mySeq !== seq) return; // a reset happened mid-flight
                    const rows = res.data?.rows || [];
                    cursor = res.data?.nextCursor || null;
                    done = !cursor;
                    opts.render(rows, res.data);
                    if (!list.children.length && opts.empty) opts.empty();
                } catch (err) {
                    (window.toast || console.error)(err.message, 'error');
                    done = true;
                } finally {
                    busy = false;
                    more.hidden = done;
                }
            };

            /* The sentinel: ask again whenever the loader scrolls into view.
               And again after each page lands while it is STILL in view —
               an observer only speaks at crossings, and a sentinel that never
               left the padded viewport never crosses back in. */
            let inView = false;
            new IntersectionObserver((entries) => {
                inView = entries[entries.length - 1].isIntersecting;
                if (inView) page();
            }, { rootMargin: '320px' }).observe(more);

            const drained = () => { if (inView && !done && !busy) setTimeout(page, 60); };
            const origPage = page;
            page = async function () { await origPage(); drained(); };

            /* app.js is a deferred module: on a fast paint this inline script
               runs first and api() does not exist yet. The first page waits
               for it rather than dying into a toast that also does not exist. */
            if (window.api) page();
            else window.addEventListener('load', page, { once: true });

            return {
                reset() {
                    seq++;
                    cursor = null;
                    done = false;
                    busy = false;
                    list.innerHTML = '';
                    page();
                },
            };
        };

        window.adminEsc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        // app.js is a deferred module; on a fast paint DOMContentLoaded can
        // beat it. A flash worth showing is worth waiting a beat for.
        window.addEventListener('load', () => {
            @if (session('success')) window.toast?.(@json(session('success')), 'success'); @endif
            @if (session('error')) window.toast?.(@json(session('error')), 'error'); @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
