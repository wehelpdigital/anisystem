@extends('layouts.app')

@section('title', 'Cropping Schedules')
@section('page-title', 'Cropping Schedules')
@section('page-subtitle', 'Plan and manage your seasons')
@section('help-key', 'schedules')

@push('head')
    <style>
        /* Deleting a season asks for the word, so the dialog is a page of its
           own rather than a toast you can dismiss by leaning on the screen. */
        .del-modal { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center;
            justify-content: center; padding: 1rem; background: rgb(15 23 42 / .55);
            animation: delIn .18s ease both; }
        .del-card { width: 100%; max-width: 26rem; background: var(--color-white); border-radius: 1rem;
            padding: 1.1rem 1.15rem 1.15rem; box-shadow: 0 24px 60px -24px rgb(0 0 0 / .5);
            animation: delUp .28s cubic-bezier(.22,1,.36,1) both; }
        .del-title { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800;
            color: var(--color-gray-900); margin-bottom: .4rem; }
        .del-text { font-size: .85rem; line-height: 1.55; color: var(--color-gray-600); margin-bottom: .9rem; }
        .del-label { display: block; font-size: .78rem; font-weight: 700; color: var(--color-gray-700);
            margin-bottom: .35rem; }
        .del-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem; }
        .del-actions .btn-danger { background: #dc2626; color: #fff; }
        .del-actions .btn-danger:hover { background: #b91c1c; }
        .del-actions .btn-danger:disabled { opacity: .45; cursor: not-allowed; background: #dc2626; }
        @keyframes delIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes delUp { from { transform: translateY(10px); opacity: 0; } to { transform: none; opacity: 1; } }
        html.dark .del-card { background: #141a10; }
        html.dark .del-title { color: #e6eddd; }
        html.dark .del-text { color: #bcc9b0; }
        html.dark .del-label { color: #d7e3cb; }
        @media (prefers-reduced-motion: reduce) { .del-modal, .del-card { animation: none; } }

        /* ---- the greeting card: a hello with the day's answer in it. The
           badge wears the hour, the line under the name says what today
           actually holds, and the numbers stand as small labelled tiles —
           calm neutrals, so the season covers below stay the picture. ---- */
        .sch-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .85rem 1.25rem; padding: 1.05rem 1.25rem; margin-bottom: 1rem; border-radius: 1.1rem;
            background: var(--color-white); border: 1px solid var(--color-gray-200); position: relative; overflow: hidden; }
        /* One restrained accent: a hairline of field-green along the top. */
        .sch-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
            background: linear-gradient(90deg, #6b9f3d, #b8d38e 55%, transparent); }
        .sch-hero-left { display: flex; align-items: center; gap: .85rem; min-width: 0; }
        /* A drawn mark, not an emoji: platform emoji arrive as square little
           pictures and sat in the round badge like a photo in a porthole.
           The stroke icons match every other icon in the app. */
        .sch-hero-emoji { width: 3rem; height: 3rem; border-radius: 999px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; }
        .sch-hero-emoji svg { width: 1.55rem; height: 1.55rem; }
        .tod-morning { background: linear-gradient(135deg, #fff7e0, #fbe6ae); color: #d97706; }
        .tod-afternoon { background: linear-gradient(135deg, #e8f4fd, #cde7fa); color: #0284c7; }
        .tod-evening { background: linear-gradient(135deg, #e9e7fb, #d5d2f2); color: #6d28d9; }
        html.dark .tod-morning { color: #fbbf24; }
        html.dark .tod-afternoon { color: #7dd3fc; }
        html.dark .tod-evening { color: #c4b5fd; }
        .sch-hero-h { font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .sch-hero-p { font-size: .82rem; color: var(--color-gray-500); margin-top: .15rem; }
        .sch-hero-p b { font-weight: 700; color: var(--color-gray-700); }
        .sch-hero-cta { display: inline-flex; align-items: center; gap: .3rem; margin-top: .4rem;
            font-size: .78rem; font-weight: 700; color: #3d6823; }
        .sch-hero-cta svg { width: .85rem; height: .85rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .sch-hero-cta:hover svg { transform: translateX(3px); }
        @media (prefers-reduced-motion: reduce) { .sch-hero-cta svg { transition: none; } }
        .sch-hero-stats { display: flex; gap: .45rem; flex-wrap: wrap; }
        .sch-stat { display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-width: 3.9rem; padding: .45rem .65rem; border-radius: .85rem;
            background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
        .sch-stat b { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.15; }
        .sch-stat i { font-style: normal; font-size: .6rem; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--color-gray-400); }
        /* Today's tile is the one that matters — it gets the accent. */
        .sch-stat.is-today { background: #f0f7e8; border-color: #cfe3b8; }
        .sch-stat.is-today b { color: #3d6823; }
        .sch-stat.is-today i { color: #6b9f3d; }
        html.dark .sch-hero { background: #151b12; border-color: #2b3a1c; }
        html.dark .sch-hero-h { color: #e8efe1; }
        html.dark .sch-hero-p { color: #a8bd93; }
        html.dark .sch-hero-p b { color: #cdd8c0; }
        html.dark .sch-hero-cta { color: #a5c97e; }
        html.dark .tod-morning, html.dark .tod-afternoon, html.dark .tod-evening { background: rgb(255 255 255 / .07); }
        html.dark .sch-stat { background: rgb(255 255 255 / .05); border-color: #2b3a1c; }
        html.dark .sch-stat b { color: #e8efe1; }
        html.dark .sch-stat.is-today { background: rgb(61 104 35 / .22); border-color: #3f5626; }
        html.dark .sch-stat.is-today b { color: #bfe19a; }
        @media (max-width: 640px) {
            /* The tiles take the second row, evenly, instead of ragging. */
            .sch-hero-stats { width: 100%; }
            .sch-hero-stats .sch-stat { flex: 1 1 0; min-width: 0; }
        }

        /* ---- the two quick doors: notes and the camera. Feature buttons
           with a face, not two more grey pills — each carries its own tinted
           icon and, where there is room, a word on what it is for. ---- */
        .qa-btn { display: inline-flex; align-items: center; gap: .6rem; padding: .4rem 1rem .4rem .45rem;
            border-radius: 999px; background: var(--color-white); border: 1px solid var(--color-gray-200);
            font-size: .84rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; text-align: left;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1),
                border-color .28s cubic-bezier(.22,1,.36,1); }
        .qa-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -14px rgb(0 0 0 / .45); }
        .qa-btn:active { transform: translateY(0); }
        .qa-ico { width: 2.1rem; height: 2.1rem; border-radius: 999px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; }
        .qa-ico svg { width: 1.15rem; height: 1.15rem; }
        .qa-notes .qa-ico { background: #fdf6e6; color: #b45309; }
        .qa-notes:hover { border-color: #f0dcae; }
        .qa-cap .qa-ico { background: #eef6e6; color: #3d6823; }
        .qa-cap:hover { border-color: #cfe3b8; }
        .qa-txt { display: flex; flex-direction: column; line-height: 1.15; min-width: 0; }
        .qa-sub { display: none; font-size: .68rem; font-weight: 500; color: var(--color-gray-400); }
        @media (min-width: 768px) { .qa-sub { display: block; } }
        html.dark .qa-btn { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
        html.dark .qa-notes .qa-ico { background: rgb(180 83 9 / .18); color: #e0b457; }
        html.dark .qa-cap .qa-ico { background: rgb(61 104 35 / .25); color: #a5c97e; }
        html.dark .qa-sub { color: #7d8f6e; }
        @media (prefers-reduced-motion: reduce) { .qa-btn { transition: none; } }
        /* Phones: the pair shares the row under the search, evenly. */
        .sch-quick .qa-btn { flex: 1 1 0; justify-content: flex-start; }

        /* ---- season cards: each schedule is a shelfful of ground, so the
           card leads with a field-toned cover, the crops growing on it, and
           where the season stands — before any chrome. ---- */
        .se-card { overflow: hidden; }
        /* One level row — crops, name and status share a line, vertically
           centred, instead of the pill floating a corner higher than the
           name it describes. */
        .se-cover { position: relative; height: 4.6rem; display: flex; align-items: center;
            gap: .6rem; padding: .55rem .8rem; }
        /* Soft, desaturated tints — the status is the weather over the field. */
        .se-cover-active    { background: linear-gradient(120deg, #eef6e6, #d9e9c8); }
        .se-cover-setup     { background: linear-gradient(120deg, #fdf6e6, #f5e6c4); }
        .se-cover-generated { background: linear-gradient(120deg, #eef0fb, #dde2f5); }
        .se-cover-completed { background: linear-gradient(120deg, #edf3f9, #d9e6f2); }
        .se-cover-draft     { background: linear-gradient(120deg, #f4f5f4, #e7eae6); }
        .se-cover-archived  { background: linear-gradient(120deg, #e5e7eb, #d2d6dc); }
        /* A faint horizon line so the tint reads as ground, not just paint. */
        .se-cover::after { content: ''; position: absolute; inset: auto 0 0 0; height: 1.4rem;
            background: linear-gradient(180deg, transparent, rgb(0 0 0 / .05)); pointer-events: none; }
        .se-crops { font-size: 1.7rem; line-height: 1; letter-spacing: .1em; position: relative; z-index: 1;
            flex-shrink: 0; filter: drop-shadow(0 2px 3px rgb(0 0 0 / .12)); }
        /* The schedule's name IS the banner: it stands on the cover beside
           the crops, and the body below is all season-reading. */
        .se-title { position: relative; z-index: 1; min-width: 0; flex: 1 1 auto;
            font-weight: 800; font-size: 1rem; line-height: 1.25; color: var(--color-gray-900);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            text-shadow: 0 1px 0 rgb(255 255 255 / .5); }
        html.dark .se-title { color: #e8efe1; text-shadow: none; }
        .se-status { position: relative; z-index: 1; margin-left: auto; flex-shrink: 0;
            display: inline-flex; align-items: center;
            gap: .35rem; padding: .28rem .6rem; border-radius: 999px; background: rgb(255 255 255 / .85);
            font-size: .68rem; font-weight: 700; color: var(--color-gray-700); text-transform: capitalize;
            backdrop-filter: blur(2px); }
        .se-dot { width: .5rem; height: .5rem; border-radius: 999px; background: #9ca3af; }
        .se-dot-active { background: #4a7c2a; box-shadow: 0 0 0 3px rgb(74 124 42 / .18); }
        .se-dot-setup { background: #d97706; }
        .se-dot-generated { background: #6366f1; }
        .se-dot-completed { background: #2563eb; }
        .se-dot-archived { background: #374151; }
        html.dark .se-cover-active    { background: linear-gradient(120deg, #1e2817, #26331b); }
        html.dark .se-cover-setup     { background: linear-gradient(120deg, #2a2414, #332b16); }
        html.dark .se-cover-generated { background: linear-gradient(120deg, #1d2030, #232741); }
        html.dark .se-cover-completed { background: linear-gradient(120deg, #17222d, #1b2a3a); }
        html.dark .se-cover-draft, html.dark .se-cover-archived { background: linear-gradient(120deg, #1a1e18, #232823); }
        html.dark .se-status { background: rgb(0 0 0 / .45); color: #d5dfc9; }

        .se-desc { font-size: .8rem; color: var(--color-gray-500);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        /* Where the crop stands today — the one line a farmer opens this
           page for, so it reads before the counts do. */
        /* One lot at a time, slid rather than stacked: a season with five
           lots would otherwise push the card's own buttons off the screen.
           A native scroller, so a thumb swipes it and a keyboard can too. */
        .se-reads { margin-top: .6rem; }
        .se-reads-rail { display: flex; overflow-x: auto; scroll-snap-type: x mandatory;
            scrollbar-width: none; -ms-overflow-style: none; }
        .se-reads-rail::-webkit-scrollbar { display: none; }
        .se-reads-rail > .se-read { flex: 0 0 100%; scroll-snap-align: start; margin-top: 0; }
        .se-reads-foot { display: flex; align-items: center; gap: .3rem; margin-top: .35rem; }
        .se-rnav { width: 1.3rem; height: 1.3rem; border-radius: 999px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--color-gray-400); background: var(--color-gray-50); cursor: pointer; }
        .se-rnav svg { width: .7rem; height: .7rem; }
        .se-rnav:hover { background: #e4efd4; color: #3d6823; }
        .se-rdots { display: inline-flex; align-items: center; gap: .22rem; }
        .se-rdots i { width: .3rem; height: .3rem; border-radius: 999px; background: var(--color-gray-300);
            transition: background .28s cubic-bezier(.22,1,.36,1), width .28s cubic-bezier(.22,1,.36,1); }
        .se-rdots i.is-on { background: #4a7c2a; width: .8rem; }
        .se-rcount { margin-left: auto; font-size: .62rem; font-weight: 700; color: var(--color-gray-400); }
        html.dark .se-rnav { background: rgb(255 255 255 / .06); color: #9fb08e; }
        html.dark .se-rdots i { background: #3f4a37; }
        html.dark .se-rdots i.is-on { background: #86b556; }
        @media (prefers-reduced-motion: reduce) { .se-rdots i { transition: none; } }

        .se-read { display: flex; align-items: baseline; gap: .4rem; margin-top: .6rem;
            font-size: .8rem; font-weight: 700; color: #3d6823; }
        .se-read-day { font-size: .95rem; font-weight: 800; white-space: nowrap; }
        .se-read-stage { font-weight: 700; color: var(--color-gray-700); min-width: 0; }
        .se-read-lot { font-weight: 600; color: var(--color-gray-400); font-size: .72rem; min-width: 0; }
        .se-read.is-quiet { color: var(--color-gray-400); font-weight: 600; }
        html.dark .se-read { color: #a5c97e; }
        html.dark .se-read-stage { color: #cdd8c0; }

        .se-prog { height: .35rem; border-radius: 999px; background: var(--color-gray-100);
            overflow: hidden; margin-top: .55rem; }
        .se-prog span { display: block; height: 100%; border-radius: 999px;
            background: linear-gradient(90deg, #8fbf5e, #4a7c2a);
            transition: width .28s cubic-bezier(.22,1,.36,1); }
        .se-cover-completed ~ .card-body .se-prog span { background: linear-gradient(90deg, #93c5fd, #2563eb); }
        .se-progline { display: flex; justify-content: space-between; align-items: baseline;
            margin-top: .3rem; font-size: .68rem; color: var(--color-gray-400); }
        .se-progline b { color: var(--color-gray-600); font-weight: 700; }
        html.dark .se-prog { background: rgb(255 255 255 / .08); }
        html.dark .se-progline b { color: #cdd8c0; }
        @media (prefers-reduced-motion: reduce) { .se-prog span { transition: none; } }

        .se-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .9rem;
            margin-top: auto; padding-top: .7rem; font-size: .72rem; font-weight: 500;
            color: var(--color-gray-500); }
        .se-meta svg { width: .95rem; height: .95rem; }
        /* When the season was planted in the app — a quiet tag on its own
           line under the counts. */
        .se-when-tag { flex-basis: 100%; align-self: flex-start; }
        .se-when-tag span { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .55rem;
            border-radius: 999px; background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
            font-size: .66rem; font-weight: 600; color: var(--color-gray-500); }
        html.dark .se-when-tag span { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }

        @media (max-width: 767px) {
            /* A phone shows one card at a time; every row of chrome inside it
               is a row of the next card pushed off screen. */
            .se-card .card-body { padding: .8rem .9rem; }
            .se-cover { height: 3.6rem; }
            .se-crops { font-size: 1.45rem; }
            .se-title { font-size: .9rem; }
            .se-desc { font-size: .78rem; }
            .se-meta { gap: .3rem .8rem; }
            /* Open is what you came for: it takes the width, and the two
               destructive-ish actions shrink to icons beside it rather than
               competing for the same emphasis. */
            .sch-acts .btn:first-child { flex: 1 1 auto; }
            .sch-acts .btn-ghost { padding-left: .55rem !important; padding-right: .55rem !important; }
            .sch-quick .btn { justify-content: center; }
        }
    </style>
@endpush

@section('content')

    {{-- A hello with the day's answer in it: who you are, what today holds,
         and the way straight onto the board that holds it. --}}
    <div class="sch-hero">
        @php
            $__h = (int) now()->format('G');
            [$__greet, $__tod] = $__h < 12
                ? ['Good morning', 'tod-morning']
                : ($__h < 18 ? ['Good afternoon', 'tod-afternoon'] : ['Good evening', 'tod-evening']);
            // Built here rather than inline: a trailing full stop after an
            // @endif is not a directive Blade recognises, and the if never
            // closes.
            $__say = now()->format('l, F j');
            if ($summary['schedules'] === 0) {
                $__say .= ' — nothing planned yet. A schedule is where a season starts.';
            } elseif ($summary['today'] > 0) {
                $__say .= ' — <b>' . $summary['today'] . ' ' . \Illuminate\Support\Str::plural('activity', $summary['today']) . '</b> on the board today';
                $__say .= $summary['active'] ? ', ' . $summary['active'] . ' ' . \Illuminate\Support\Str::plural('season', $summary['active']) . ' running.' : '.';
            } else {
                $__say .= ' — a quiet day, nothing planned on the boards.';
            }
        @endphp
        <div class="sch-hero-left">
            <span class="sch-hero-emoji {{ $__tod }}" aria-hidden="true">
                @if ($__tod === 'tod-morning')
                    {{-- Sunrise: half a sun lifting over the ground line. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v2M5.3 6.7l1.4 1.4M18.7 6.7l-1.4 1.4M8 15a4 4 0 118 0M2 15h2m16 0h2M3 19h18"/></svg>
                @elseif ($__tod === 'tod-afternoon')
                    {{-- Full sun, high. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4l1.4-1.4"/></svg>
                @else
                    {{-- The moon the app already draws elsewhere. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                @endif
            </span>
            <div class="min-w-0">
                <h1 class="sch-hero-h">{{ $__greet }}, {{ \Illuminate\Support\Str::title(auth()->user()->firstName ?: 'farmer') }}</h1>
                <p class="sch-hero-p">{!! $__say !!}</p>
                @if (($todayHref ?? null) && $summary['today'] > 0)
                    <a href="{{ $todayHref }}" class="sch-hero-cta">
                        Open today's board
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h14"/></svg>
                    </a>
                @endif
            </div>
        </div>
        <div class="sch-hero-stats">
            <span class="sch-stat is-today"><b>{{ $summary['today'] }}</b><i>today</i></span>
            <span class="sch-stat"><b>{{ $summary['lots'] }}</b><i>{{ \Illuminate\Support\Str::plural('lot', $summary['lots']) }}</i></span>
            <span class="sch-stat"><b>{{ $summary['workers'] }}</b><i>{{ \Illuminate\Support\Str::plural('worker', $summary['workers']) }}</i></span>
        </div>
    </div>

    {{-- Top bar: search on its own row, the desktop CTAs on a second row below. --}}
    <div class="flex flex-col gap-3 mb-4 md:mb-6">
        {{-- Search runs as you type (see the script below); the button-less form
             still submits on Enter as a no-JS fallback. --}}
        <form method="GET" action="{{ route('sm.index') }}" role="search" id="scheduleSearchForm" class="flex-1">
            <div class="relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="text" name="search" id="scheduleSearch" value="{{ request('search') }}" class="form-input pl-11! pr-16! w-full"
                    placeholder="Search schedules…" aria-label="Search schedules" autocomplete="off" enterkeyhint="search">
                <svg id="scheduleSearchSpin" class="hidden absolute right-9 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <button type="button" id="scheduleSearchClear" class="{{ request('search') ? '' : 'hidden' }} absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-full text-gray-400 hover:bg-gray-100" aria-label="Clear search">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </form>

        {{-- Phones: the same two secondary actions as a compact row under the
             search, rather than a tower of floating buttons stacked up the
             right edge — three FABs covered the list they were meant to act
             on, and only one of them was the thing you came here to do. --}}
        <div class="flex md:hidden gap-2 sch-quick">
            <a href="{{ route('notes.hub') }}" class="qa-btn qa-notes">
                <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span class="qa-txt">Global notes</span>
            </a>
            @if ($allSchedules->isNotEmpty())
                <button type="button" id="quickCaptureFab" class="qa-btn qa-cap">
                    <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    <span class="qa-txt">Quick capture</span>
                </button>
            @endif
        </div>

        {{-- Desktop CTA. Wrapped so `hidden` reliably hides it on phones (a
             bare `.btn` is unlayered CSS and would otherwise beat `hidden`);
             the floating + button is the phone equivalent. --}}
        <div class="hidden md:flex md:justify-end md:items-center gap-2">
            <a href="{{ route('notes.hub') }}" class="qa-btn qa-notes" title="Every note from every schedule, in one place">
                <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span class="qa-txt">Global notes<span class="qa-sub">every schedule's notebook</span></span>
            </a>
            @if ($allSchedules->isNotEmpty())
                <button type="button" id="quickCaptureBtn" class="qa-btn qa-cap">
                    <span class="qa-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    <span class="qa-txt">Quick Capture<span class="qa-sub">snap it, file it now</span></span>
                </button>
            @endif
            <a href="{{ route('sm.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                New Cropping Schedule
            </a>
        </div>
    </div>

    {{-- Live-search swaps this block's contents (see script). --}}
    <div id="scheduleResults">
    @if ($schedules->isEmpty())
        {{-- Friendly empty state --}}
        <div class="card">
            <div class="card-body text-center py-14">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 8h6"/></svg>
                </div>
                @if (request()->filled('search'))
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No schedules match your search</h2>
                    <p class="text-sm text-gray-500 mb-5">Try a different search, or clear it to see all your schedules.</p>
                    <a href="{{ route('sm.index') }}" class="btn btn-outline">Clear search</a>
                @else
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No cropping schedules yet</h2>
                    <p class="text-sm text-gray-500 mb-5">Create your first schedule to start planning lots, workers and day-by-day activities.</p>
                    <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        New Cropping Schedule
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger-children" id="schedulesGrid">
            @foreach ($schedules as $s)
                @php $card = $cards[$s->id] ?? ['icons' => [], 'reading' => null, 'progress' => null, 'window' => null]; @endphp
                <div class="card card-hover flex flex-col se-card" data-schedule-card="{{ $s->id }}">
                    {{-- The cover IS the status: a field-toned wash, the crops
                         growing on it, and the season's state as weather over
                         it — readable from across the grid. --}}
                    <div class="se-cover se-cover-{{ $s->status }}">
                        <span class="se-crops" aria-hidden="true">{{ count($card['icons']) ? implode('', $card['icons']) : '🌱' }}</span>
                        <h2 class="se-title" title="{{ $s->title }}">{{ $s->title }}</h2>
                        <span class="se-status"><i class="se-dot se-dot-{{ $s->status }}"></i>{{ $s->status }}</span>
                    </div>
                    <div class="card-body flex flex-col grow">
                        @if ($s->description)
                            <p class="se-desc">{{ \Illuminate\Support\Str::limit($s->description, 100) }}</p>
                        @endif

                        {{-- Where each lot stands today — same arithmetic as
                             Growth Stages, so the two pages never disagree.
                             A season has more than one lot, so the strip
                             slides: swipe it, or use the arrows. --}}
                        @php $reads = $card['readings'] ?? []; @endphp
                        @if (count($reads))
                            <div class="se-reads{{ count($reads) > 1 ? ' has-many' : '' }}">
                                <div class="se-reads-rail" data-reads>
                                    @foreach ($reads as $r)
                                        <div class="se-read">
                                            <span class="se-read-day">{{ $r['counter'] }} {{ $r['day'] }}</span>
                                            @if ($r['stage'])
                                                <span class="se-read-stage truncate">· {{ $r['stage'] }}</span>
                                            @endif
                                            <span class="se-read-lot truncate">{{ $r['icon'] }} {{ $r['lot'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if (count($reads) > 1)
                                    <div class="se-reads-foot">
                                        <button type="button" class="se-rnav" data-rprev aria-label="Previous lot">
                                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <span class="se-rdots">
                                            @foreach ($reads as $i => $r)
                                                <i class="{{ $i === 0 ? 'is-on' : '' }}"></i>
                                            @endforeach
                                        </span>
                                        <button type="button" class="se-rnav" data-rnext aria-label="Next lot">
                                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <span class="se-rcount">{{ count($reads) }} lots</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="se-read is-quiet">Not counting yet — the season starts at day zero.</div>
                        @endif

                        @if ($card['progress'] !== null)
                            <div class="se-prog"><span style="width: {{ $card['progress'] }}%"></span></div>
                            <div class="se-progline"><span>{{ $card['window'] }}</span><b>{{ $card['progress'] }}%</b></div>
                        @endif

                        <div class="se-meta">
                            <span class="inline-flex items-center gap-1" title="Lots">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                                {{ $s->lots_count }} {{ \Illuminate\Support\Str::plural('lot', $s->lots_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="Workers">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                                {{ $s->workers_count }} {{ \Illuminate\Support\Str::plural('worker', $s->workers_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="Activities">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01"/></svg>
                                {{ $s->activities_count }} {{ \Illuminate\Support\Str::plural('activity', $s->activities_count) }}
                            </span>
                            <span class="se-when-tag"><span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Created {{ $s->created_at->format('M j, Y') }}
                            </span></span>
                        </div>

                        <div class="flex items-center gap-2 mt-3 sch-acts">
                            <a href="{{ route('sm.hub', ['id' => $s->id]) }}" class="btn btn-primary flex-1">Open</a>
                            <button type="button"
                                class="btn btn-ghost px-3! text-gray-500 hover:bg-gray-100!"
                                data-duplicate-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                title="Duplicate this schedule" aria-label="Duplicate schedule">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <button type="button"
                                class="btn btn-ghost px-3! text-red-500 hover:bg-red-50!"
                                data-delete-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                aria-label="Delete schedule">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $schedules->links() }}
        </div>
    @endif
    </div>{{-- /#scheduleResults --}}

    {{-- The tip reads last on purpose: it is worth knowing, but it is not
         what anyone opened this page for, and at the top it pushed the
         schedules themselves below the fold on a phone. --}}
    @include('sm.partials.tip-of-day', ['tip' => $tip ?? null, 'aiHref' => ($schedules->first() ? route('sm.ai', ['id' => $schedules->first()->id]) : null)])

    {{-- One floating button, for the one thing this page exists to start. --}}
    <a href="{{ route('sm.create') }}"
        class="md:hidden fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg flex items-center justify-center"
        aria-label="New cropping schedule">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
    </a>

    @include('sm.partials.quick-capture', ['allSchedules' => $allSchedules])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    /* ---- the lot strip on a season card -----------------------------
     * The rail scrolls by itself; this only keeps the dots honest and
     * lets the arrows drive it. Re-run after a live search swaps cards. */
    function wireReadRails(scope) {
        (scope || document).querySelectorAll('.se-reads.has-many').forEach((box) => {
            if (box.dataset.wired === '1') return;
            box.dataset.wired = '1';
            const rail = box.querySelector('[data-reads]');
            const dots = [...box.querySelectorAll('.se-rdots i')];
            const at = () => Math.round(rail.scrollLeft / Math.max(1, rail.clientWidth));
            const paint = () => {
                const i = at();
                dots.forEach((d, n) => d.classList.toggle('is-on', n === i));
            };
            const go = (step) => {
                const next = Math.max(0, Math.min(dots.length - 1, at() + step));
                rail.scrollTo({ left: next * rail.clientWidth, behavior: 'smooth' });
            };
            rail.addEventListener('scroll', () => window.requestAnimationFrame(paint), { passive: true });
            box.querySelector('[data-rprev]')?.addEventListener('click', (e) => { e.preventDefault(); go(-1); });
            box.querySelector('[data-rnext]')?.addEventListener('click', (e) => { e.preventDefault(); go(1); });
            // A card is a link; a swipe on the strip is not a tap on it.
            rail.addEventListener('click', (e) => e.stopPropagation());
            paint();
        });
    }
    wireReadRails();
    window.smWireReadRails = wireReadRails;

    // ---- Live search: fetch as you type and swap the results in place.
    (() => {
        const form = document.getElementById('scheduleSearchForm');
        const input = document.getElementById('scheduleSearch');
        const clearBtn = document.getElementById('scheduleSearchClear');
        const spin = document.getElementById('scheduleSearchSpin');
        const results = document.getElementById('scheduleResults');
        if (!form || !input || !results) return;

        const BASE = @json(route('sm.index'));
        let token = 0;
        let debounce = null;

        async function runSearch(push = true) {
            const q = input.value.trim();
            clearBtn.classList.toggle('hidden', q === '');
            const url = BASE + (q ? ('?search=' + encodeURIComponent(q)) : '');
            const mine = ++token;
            spin.classList.remove('hidden');
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const html = await res.text();
                if (mine !== token) return;                 // a newer keystroke won
                const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('scheduleResults');
                if (fresh) {
                    results.innerHTML = fresh.innerHTML;
                    // The cards are new; their lot strips need driving.
                    window.smWireReadRails?.(results);
                }
                if (push) history.replaceState(null, '', url);
            } catch (_) {
                /* keep the current results on a transient failure */
            } finally {
                if (mine === token) spin.classList.add('hidden');
            }
        }

        input.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(runSearch, 250);
        });
        // Enter shouldn't full-reload; run the search immediately instead.
        form.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(debounce); runSearch(); });
        clearBtn.addEventListener('click', () => { input.value = ''; input.focus(); clearTimeout(debounce); runSearch(); });
    })();

    // Delete schedule (soft delete) -> remove card.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-delete-schedule]');
        if (!btn) return;

        const id = btn.getAttribute('data-delete-schedule');
        const title = btn.getAttribute('data-title') || 'this schedule';

        // A season's worth of work should not go on a mistaken tap. Typing the
        // word is the one confirmation that cannot be given by reflex.
        const ok = await confirmDelete(title);
        if (!ok) return;

        try {
            const res = await api(`{{ route('sm.destroy') }}?id=${id}`, { method: 'DELETE' });
            toast(res.message);
            document.querySelector(`[data-schedule-card="${id}"]`)?.remove();
            if (!document.querySelector('[data-schedule-card]')) window.location.reload();
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    /**
     * Ask for the word DELETE before removing a schedule.
     *
     * Resolves true only when it was typed and Delete pressed; Escape, the
     * backdrop and Cancel all mean no.
     */
    function confirmDelete(title) {
        return new Promise((resolve) => {
            const wrap = document.createElement('div');
            wrap.className = 'del-modal';
            wrap.innerHTML = `
                <div class="del-card" role="dialog" aria-modal="true" aria-labelledby="delTitle">
                    <h3 class="del-title" id="delTitle">Delete this schedule?</h3>
                    <p class="del-text"><strong>${escapeHtml(title)}</strong> and everything filed under it — lots,
                        workers, activities, notes — disappear from your account.</p>
                    <label class="del-label" for="delWord">Type <b>DELETE</b> to confirm</label>
                    <input type="text" id="delWord" class="form-input" autocomplete="off" spellcheck="false" placeholder="DELETE">
                    <div class="del-actions">
                        <button type="button" class="btn btn-white" data-del-no>Cancel</button>
                        <button type="button" class="btn btn-danger" data-del-yes disabled>Delete schedule</button>
                    </div>
                </div>`;
            document.body.appendChild(wrap);
            document.documentElement.style.overflow = 'hidden';

            const field = wrap.querySelector('#delWord');
            const go = wrap.querySelector('[data-del-yes]');
            const done = (answer) => {
                document.documentElement.style.overflow = '';
                wrap.remove();
                document.removeEventListener('keydown', onKey);
                resolve(answer);
            };
            const onKey = (ev) => {
                if (ev.key === 'Escape') done(false);
                if (ev.key === 'Enter' && !go.disabled) done(true);
            };

            field.addEventListener('input', () => {
                go.disabled = field.value.trim().toUpperCase() !== 'DELETE';
            });
            wrap.addEventListener('click', (ev) => {
                if (ev.target === wrap || ev.target.closest('[data-del-no]')) done(false);
                else if (ev.target.closest('[data-del-yes]') && !go.disabled) done(true);
            });
            document.addEventListener('keydown', onKey);
            window.smFocus(field, { delay: 60 });
        });
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-duplicate-schedule]');
        if (!btn) return;

        const id = btn.getAttribute('data-duplicate-schedule');
        const title = btn.getAttribute('data-title') || 'this schedule';

        const ok = await confirmAction({
            title: 'Duplicate schedule?',
            message: `A full copy of "${title}" — every module, activity and version — will be created as "Copy of ${title}".`,
            confirmText: 'Duplicate',
            confirmClass: 'btn-primary',
        });
        if (!ok) return;

        btn.disabled = true;
        const loader = screenLoader(`Duplicating "${title}"…`);
        try {
            const res = await api(`{{ route('sm.duplicate') }}?id=${id}`, { method: 'POST' });
            toast(res.message);
            window.location.href = res.data.hubUrl;
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });
});
</script>
@endpush
