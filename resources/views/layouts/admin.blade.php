<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    {{-- Nothing behind the login is anybody's to index. --}}
    <meta name="robots" content="noindex, nofollow">
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
           Mobile first: one column, one sticky header. The header carries the
           way back, the panel's name, the module tag, and -- on the pages that
           have one -- the page's own search or filters (@section('bar')), so
           the whole top of the screen is a single sticky block with a single
           ground. Everything else is the client app's own classes -- cards,
           buttons, sheets -- because an admin should not have to learn a
           second dialect of the same app.

           Colours are the palette's tokens, not literal hex: html.dark
           re-points the tokens, so the same rule is right in both themes and
           the panel wears the client app's own night colours instead of a
           green-black of its own. Literal hex survives only where a colour
           has no token (the hat badges). */
        html { scroll-padding-top: calc(var(--ad-top-h, 4rem) + .75rem); }
        .ad-top { position: sticky; top: 0; z-index: 40;
            background: var(--color-white); border-bottom: 1px solid var(--color-gray-200);
            padding-top: env(safe-area-inset-top); }
        .ad-top-in { display: flex; align-items: center; gap: .6rem; padding: .55rem .9rem;
            max-width: 64rem; margin: 0 auto; }
        .ad-back { display: inline-flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: 999px; color: var(--color-gray-500); flex: none;
            margin-left: -.35rem;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .ad-back:hover { background: var(--color-gray-100); color: var(--color-gray-900); }
        .ad-title { font-weight: 800; color: var(--color-gray-900); font-size: 1.02rem;
            font-family: var(--font-heading); line-height: 1.15;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-sub { font-size: .7rem; color: var(--color-gray-400); font-weight: 600;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* The module tag: names the room you are in, opens the sheet of rooms.
           It shares the first row with the title, so the header costs one
           row of the phone's height instead of two. */
        .ad-navtag { display: inline-flex; align-items: center; gap: .4rem; flex: none;
            padding: .42rem .5rem .42rem .75rem; border-radius: 999px; cursor: pointer;
            background: var(--color-brand-50); color: var(--color-brand-800);
            font-weight: 800; font-size: .82rem; border: 1px solid var(--color-brand-200);
            transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .ad-navtag:hover { background: var(--color-brand-100); }
        .ad-navtag > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-navtag svg { width: 1rem; height: 1rem; flex: none; }
        .ad-navtag-c { opacity: .6; }
        /* In the header it shares a row with the title, so it may not take
           more than about half of it; on the narrowest phones it drops its
           leading glyph so "Admin panel" still reads whole. */
        .ad-top .ad-navtag { max-width: 52vw; }
        @media (max-width: 359px) {
            .ad-top .ad-navtag { max-width: 48vw; padding-left: .6rem; }
            .ad-top .ad-navtag > svg:first-child { display: none; }
        }
        /* The sheet of rooms. */
        .ad-nav-row { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
            padding: .7rem .8rem; border-radius: .8rem; margin-bottom: .35rem;
            border: 1px solid var(--color-gray-200); color: var(--color-gray-800);
            transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .ad-nav-row:hover { background: var(--color-gray-50); }
        .ad-nav-row.is-on { border-color: var(--color-brand-300); background: var(--color-brand-50); }
        .ad-nav-row svg { width: 1.15rem; height: 1.15rem; flex: none; color: var(--color-brand-700); }
        .ad-nav-row b { display: block; font-size: .88rem; font-weight: 800; }
        .ad-nav-row i { display: block; font-style: normal; font-size: .72rem; color: var(--color-gray-500); }
        /* brand-300 turns bright lime at night; the selected room wants a
           quieter edge than that. */
        html.dark .ad-nav-row.is-on { border-color: var(--color-brand-200); }

        /* THE PAGE'S OWN BAR, INSIDE THE HEADER.
           Search on Clients and Support, the filter chips on Reports. It
           used to be a second sticky element under the header, offset by
           the header's measured height and pulled up over the page with a
           negative margin -- which is how it came to cover the bottom of
           Support's tab chips, and why, once the header had scrolled away
           (the body used to stop one screen down, see <body>), the search
           floated mid-screen with rows showing above it. Living inside the
           header there is nothing left to measure and nothing to overlap. */
        .ad-bar { max-width: 64rem; margin: 0 auto; padding: 0 .9rem .65rem; }
        .ad-bar > * + * { margin-top: .5rem; }
        /* A fold's gap travels inside it, so a shut fold leaves no gap. */
        .ad-bar > .ad-fold { margin-top: 0; }
        .ad-bar > * + .ad-fold > :first-child { padding-top: .5rem; }
        .ad-search { position: relative; display: block; }
        .ad-search > svg { position: absolute; left: .8rem; top: 50%; width: 1.05rem; height: 1.05rem;
            transform: translateY(-50%); color: var(--color-gray-400); pointer-events: none; }
        .ad-search > .form-input { padding-left: 2.4rem; }
        /* Filter chips: a row that WRAPS rather than scrolling sideways, so a
           chip is never cut off at the screen's edge. Compact inside the bar:
           the bar sits on every scroll position of the page and pays for
           its height the whole way down. */
        .ad-chips { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
        .ad-chips .chip { min-height: 2.1rem; padding: .3rem .8rem; font-size: .82rem; border-width: 1.5px; }
        .ad-chips .chip span:empty { display: none; }

        /* A TWO-ROOM SWITCH (Support's Tickets / Canned responses): one pill,
           a thumb that slides to the room you are in. */
        .ad-seg { position: relative; display: grid; grid-template-columns: 1fr 1fr; max-width: 24rem;
            padding: 3px; border-radius: 999px; background: var(--color-gray-100); }
        .ad-seg button { position: relative; z-index: 1; padding: .42rem .7rem; border-radius: 999px;
            font-size: .82rem; font-weight: 700; color: var(--color-gray-500); white-space: nowrap;
            transition: color .28s cubic-bezier(.22,1,.36,1); }
        .ad-seg button.is-on { color: var(--color-gray-900); }
        .ad-seg-thumb { position: absolute; z-index: 0; top: 3px; bottom: 3px; left: 3px; width: calc(50% - 3px);
            border-radius: 999px; background: var(--color-white);
            box-shadow: 0 1px 3px rgb(0 0 0 / .12), 0 1px 1px rgb(0 0 0 / .04);
            transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .ad-seg[data-on="1"] .ad-seg-thumb { transform: translateX(100%); }
        html.dark .ad-seg { background: var(--color-gray-50); }
        html.dark .ad-seg-thumb { background: var(--color-gray-200); box-shadow: none; }

        /* A fold that slides: max-height, never grid rows (see the concertina
           notes in app.js) -- set by adminFold() below. */
        .ad-fold { overflow: hidden; transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
        .ad-fold.is-shut { max-height: 0; opacity: 0; }

        /* A pane arriving: the house rise. */
        @keyframes adRise { from { opacity: 0; transform: translateY(.4rem); } to { opacity: 1; transform: none; } }
        .ad-rise { animation: adRise .28s cubic-bezier(.22,1,.36,1) both; }

        .ad-main { max-width: 64rem; margin: 0 auto; padding: 1rem .9rem 4rem; }

        /* A NUMBER WITH A NAME — the dashboard's stat cards. */
        .ad-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
        @media (min-width: 768px) { .ad-stats { grid-template-columns: repeat(4, 1fr); } }
        .ad-stat { padding: .8rem .9rem; min-width: 0; }
        .ad-stat b { display: block; font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900);
            font-family: var(--font-heading); line-height: 1.1;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-stat span { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); margin-top: .15rem; }
        .ad-stat small { display: block; font-size: .7rem; font-weight: 700; color: var(--color-brand-700); margin-top: .2rem; }

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
        @media (max-width: 479px) { .ch-wrap { gap: .2rem; } .ch-bar i { font-size: .54rem; } }

        /* ONE ROW OF A LIST — a client, a ticket. */
        .ad-row { display: flex; align-items: center; gap: .7rem; padding: .75rem .85rem;
            width: 100%; text-align: left; border-bottom: 1px solid var(--color-gray-100); cursor: pointer;
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .ad-row:last-child { border-bottom: 0; }
        .ad-row:hover { background: var(--color-gray-50); }
        .ad-face { width: 2.4rem; height: 2.4rem; border-radius: 999px; flex: none;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .95rem; color: var(--color-brand-800); background: var(--color-brand-50); }
        .ad-mid { flex: 1 1 auto; min-width: 0; }
        .ad-name { display: block; font-weight: 700; font-size: .9rem; color: var(--color-gray-900);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        /* A name that wears hats: the name keeps its ellipsis, the badges
           wrap under it instead of vanishing into it. */
        .ad-namerow { display: flex; flex-wrap: wrap; align-items: center; gap: .2rem .35rem; min-width: 0; }
        .ad-namerow .ad-name { min-width: 0; max-width: 100%; }
        .ad-meta { display: block; font-size: .73rem; color: var(--color-gray-400);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ad-end { flex: none; text-align: right; }

        /* THE SCROLL'S OWN LOADER — visible while the next page travels. */
        .ad-more { display: flex; align-items: center; justify-content: center; gap: .5rem;
            padding: 1.1rem; color: var(--color-gray-400); font-size: .8rem; font-weight: 600; }
        /* The hidden attribute loses to display:flex — say it louder. */
        .ad-more[hidden] { display: none; }
        .ad-spin { width: 1.1rem; height: 1.1rem; border: 2px solid var(--color-gray-200);
            border-top-color: var(--color-brand-600); border-radius: 999px;
            animation: adspin .8s linear infinite; }
        @keyframes adspin { to { transform: rotate(360deg); } }

        /* Skeletons for the first paint, so loading looks like loading. */
        .ad-skel { border-radius: .6rem; background: var(--color-gray-100);
            animation: adskel 1.2s ease-in-out infinite alternate; }
        @keyframes adskel { from { opacity: .55; } to { opacity: 1; } }

        .ad-badge { display: inline-flex; align-items: center; font-size: .62rem; font-weight: 800; white-space: nowrap;
            text-transform: uppercase; letter-spacing: .03em; padding: .12rem .45rem; border-radius: 999px; }
        .ad-badge.is-admin { background: #ede9fe; color: #5b21b6; }
        .ad-badge.is-susp { background: #fef2f2; color: #b91c1c; }
        .ad-badge.is-role { background: #eaf1fd; color: #1d4ed8; }
        .ad-badge.is-open { background: #fffbeb; color: #b45309; }
        .ad-badge.is-answered { background: var(--color-brand-50); color: var(--color-brand-800); }
        .ad-badge.is-closed { background: var(--color-gray-100); color: var(--color-gray-500); }
        .ad-dot { width: .5rem; height: .5rem; border-radius: 999px; background: #22c55e; display: inline-block; }

        /* The thread inside a ticket. */
        .tk-msg { max-width: 85%; border-radius: .9rem; padding: .55rem .75rem; font-size: .85rem;
            background: var(--color-gray-100); color: var(--color-gray-900); overflow-wrap: anywhere; }
        .tk-msg.is-admin { background: var(--color-brand-600); color: #fff; margin-left: auto; }
        .tk-msg .tk-who { display: block; font-size: .64rem; font-weight: 800; opacity: .75; margin-bottom: .1rem; }
        .tk-msg .tk-at { display: block; font-size: .62rem; opacity: .6; margin-top: .2rem; }

        /* The client app's tag family, dressed for the panel's night: the
           shared partial paints the chosen word a literal dark green and the
           tag a green-black of its own, which read as a dim smudge beside
           the token-coloured fields around it. */
        html.dark .ad-main .crop-tag { background: var(--color-white); border-color: var(--color-gray-200); }
        html.dark .ad-main .crop-tag:hover { background: var(--color-brand-50); border-color: var(--color-brand-200); }
        html.dark .ad-main .crop-tag-t:not(.is-none) { color: var(--color-brand-900); }
        html.dark .ad-main .crop-tag-t.is-none { color: var(--color-gray-500); }

        /* LIGHT MODE'S QUIET TEXT, MEASURED. gray-400 on a white card is
           2.60:1 — quiet is not the same as inaudible. Scoped to light so the
           dark values, which measured clean, stay exactly as they are. The
           sheets are listed by id because openSheet() re-parents them to
           <body>, out from under .ad-main. */
        html:not(.dark) .ad-sub,
        html:not(.dark) .ad-meta,
        html:not(.dark) .ch-lbl,
        html:not(.dark) .ad-stat span,
        html:not(.dark) .ad-more,
        html:not(.dark) .ad-main .text-gray-400,
        html:not(.dark) #clSheet .text-gray-400,
        html:not(.dark) #tkSheet .text-gray-400 { color: var(--color-gray-500); }

        /* Night: only the colours with no token need saying again. */
        html.dark .ad-badge.is-admin { background: #2d2748; color: #c4b5fd; }
        html.dark .ad-badge.is-susp { background: #3a1d1d; color: #fca5a5; }
        html.dark .ad-badge.is-role { background: #16202f; color: #9fc0f5; }
        html.dark .ad-badge.is-open { background: #2c2213; color: #f0c274; }
        html.dark .ad-badge.is-closed { color: var(--color-gray-600); }

        @media (prefers-reduced-motion: reduce) {
            .ad-spin { animation-duration: 1.6s; }
            .ad-rise { animation: none; }
            .ad-fold, .ad-seg-thumb, .ad-seg button, .ch-bar, .ad-row, .ad-back, .ad-navtag, .ad-nav-row { transition: none; }
        }
        html.sm-still .ad-rise { animation: none; }
        html.sm-still .ad-fold, html.sm-still .ad-seg-thumb { transition: none; }
    </style>
    @stack('head')
</head>
{{-- min-h-full, NOT h-full. A body pinned to one viewport's height is
     the sticky header's containing block: the header could only stick
     until the body's bottom edge came up, so one screen down it slid away
     and the page's search was left floating mid-screen over the rows. --}}
<body class="min-h-full bg-gray-50 text-gray-900 antialiased">

    {{-- Four modules folded into one tag: it names the room you are in and
         opens the sheet of rooms. --}}
    @php
        $adminHere = request()->routeIs('admin.clients') ? 'Clients'
            : (request()->routeIs('admin.support') ? 'Support'
            : (request()->routeIs('admin.reports') ? 'Reports'
            : (request()->routeIs('admin.sales') ? 'Sales Analysis' : 'Dashboard')));
    @endphp
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
            <button type="button" class="ad-navtag" id="adminNavBtn" aria-haspopup="dialog" title="Open another module">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                <span>{{ $adminHere }}</span>
                <svg class="ad-navtag-c" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>
        @hasSection('bar')
            <div class="ad-bar">@yield('bar')</div>
        @endif
    </header>

    <main class="ad-main">
        @yield('content')
    </main>

    @stack('sheets')

    {{-- Which room of the panel to open. --}}
    <div class="sheet hidden" id="adminNavSheet" style="--sheet-width:24rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Open a module</h3>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
        </div>
        <div class="sheet-body">
            <a class="ad-nav-row {{ request()->routeIs('admin.dashboard') ? 'is-on' : '' }}" href="{{ route('admin.dashboard') }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
                <span class="min-w-0"><b>Dashboard</b><i>Registrations, sales and the platform's pulse</i></span>
            </a>
            <a class="ad-nav-row {{ request()->routeIs('admin.clients') ? 'is-on' : '' }}" href="{{ route('admin.clients') }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-1.33-7.77"/></svg>
                <span class="min-w-0"><b>Clients</b><i>Every account, and what can be done for it</i></span>
            </a>
            <a class="ad-nav-row {{ request()->routeIs('admin.support') ? 'is-on' : '' }}" href="{{ route('admin.support') }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-9 7l3.5-3.5H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14z"/></svg>
                <span class="min-w-0"><b>Support</b><i>Tickets, grouped by the person who raised them</i></span>
            </a>
            <a class="ad-nav-row {{ request()->routeIs('admin.reports') ? 'is-on' : '' }}" href="{{ route('admin.reports') }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21V4m0 1l9-2 9 4-9 2-9-4zm0 8l9-2 9 4-9 2-9-4z"/></svg>
                <span class="min-w-0"><b>Reports</b><i>What the community flagged</i></span>
            </a>
            <a class="ad-nav-row {{ request()->routeIs('admin.sales') ? 'is-on' : '' }}" href="{{ route('admin.sales') }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8m0 0v5m0-5h-5"/></svg>
                <span class="min-w-0"><b>Sales Analysis</b><i>What a peso of advertising actually bought</i></span>
            </a>
        </div>
    </div>
    <script>
        document.getElementById('adminNavBtn')?.addEventListener('click', () => window.openSheet && window.openSheet('adminNavSheet'));
    </script>

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

            /* The first page of a fresh list wears skeleton rows while it
               travels (the live database answers in seconds, not frames),
               and the rows that replace them rise in rather than pop. */
            const skel = '<div class="ad-row" data-ad-skel aria-hidden="true" style="cursor:default">'
                + '<span class="ad-skel" style="width:2.4rem;height:2.4rem;border-radius:999px;flex:none"></span>'
                + '<span class="ad-mid"><span class="ad-skel" style="display:block;height:.8rem;width:42%"></span>'
                + '<span class="ad-skel" style="display:block;height:.65rem;width:64%;margin-top:.45rem"></span></span></div>';
            const skeleton = () => { list.innerHTML = skel.repeat(4); };
            skeleton();

            let page = async function () {
                if (busy || done) return;
                busy = true;
                const mySeq = seq;
                const firstPage = !cursor;
                more.hidden = firstPage;
                try {
                    const qs = new URLSearchParams(opts.params ? opts.params() : {});
                    if (cursor) qs.set('cursor', cursor);
                    const res = await api(opts.url + '?' + qs.toString(), { method: 'GET' });
                    if (mySeq !== seq) return; // a reset happened mid-flight
                    const rows = res.data?.rows || [];
                    cursor = res.data?.nextCursor || null;
                    done = !cursor;
                    if (firstPage) list.innerHTML = '';
                    opts.render(rows, res.data);
                    if (firstPage && window.adminRise) window.adminRise(list);
                    if (!list.children.length && opts.empty) opts.empty();
                } catch (err) {
                    (window.toast || console.error)(err.message, 'error');
                    done = true;
                    list.querySelectorAll('[data-ad-skel]').forEach((n) => n.remove());
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
                    skeleton();
                    page();
                },
            };
        };

        /* A fold that slides both ways. max-height from the measured height
           to zero and back, then let go of the number so the content can
           grow; a class flip alone would snap (none and 0 do not tween). */
        window.adminFold = function adminFold(el, open) {
            if (!el) return;
            const still = document.documentElement.classList.contains('sm-still')
                || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const shut = el.classList.contains('is-shut');
            if (open === !shut) return;
            if (still) { el.style.maxHeight = ''; el.classList.toggle('is-shut', !open); return; }
            clearTimeout(el._adFoldT);
            if (open) {
                el.classList.remove('is-shut');
                el.style.maxHeight = '0px';
                requestAnimationFrame(() => {
                    el.style.maxHeight = el.scrollHeight + 'px';
                    el._adFoldT = setTimeout(() => { el.style.maxHeight = ''; }, 300);
                });
            } else {
                el.style.maxHeight = el.scrollHeight + 'px';
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    el.style.maxHeight = '';
                    el.classList.add('is-shut');
                }));
            }
        };

        /* Replays the house rise on a pane that just came into view. */
        window.adminRise = function adminRise(el) {
            if (!el) return;
            el.classList.remove('ad-rise');
            void el.offsetWidth;
            el.classList.add('ad-rise');
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
    <script>
        /* The header's height, measured: html's scroll-padding reads it, so
           anything scrolled into view (a sales read, a canned template being
           edited) lands below the sticky header instead of under it. It
           grows with a bigger font scale and with the page's own bar. */
        (() => {
            const top = document.querySelector('.ad-top');
            if (!top) return;
            const set = () => document.documentElement.style.setProperty('--ad-top-h', top.getBoundingClientRect().height + 'px');
            set();
            if ('ResizeObserver' in window) new ResizeObserver(set).observe(top);
            window.addEventListener('resize', set, { passive: true });
        })();
    </script>
</body>
</html>
