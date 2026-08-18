@extends('layouts.app')

@section('title', $settings->assistantName)
@section('page-title', 'AI Technician')
@section('page-subtitle', 'Crop questions, answered')

@php
    // Super admins ride free — the wallet row hides for them (view-side check,
    // same pattern the floating assistant already uses).
    $aiUnlimited = app(\App\Services\AiCreditService::class)->unlimited((int) auth()->id());
@endphp

@push('head')
    <style>
        /* ===== Personal AI Technician — the "Field Advisor" language =====
           Same bones as the schedule AI page so the two read as one product.
           Theme vars everywhere; literal hex only where a gradient must stay
           identical in both modes (the deep-green header and send slips). */

        .ai-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; align-items: start; }
        @media (min-width: 1024px) { .ai-layout { grid-template-columns: 17.5rem minmax(0, 1fr); } }

        /* --- History rail (desktop; phones use the bottom sheet) --- */
        .ai-history-rail { display: none; }
        @media (min-width: 1024px) {
            .ai-history-rail { display: flex; flex-direction: column; position: sticky; top: 5rem;
                max-height: calc(100dvh - 8.5rem); border: 1px solid var(--color-gray-100);
                border-radius: 1rem; background: var(--color-white); box-shadow: var(--shadow-card); overflow: hidden; }
        }
        .ai-history-rail .rail-head { display: flex; align-items: center; gap: .5rem; padding: .75rem .9rem .55rem; }
        .ai-history-rail .rail-head h2 { font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
        .ai-history-rail .rail-scroll { overflow-y: auto; padding: 0 .5rem .5rem; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent; }

        /* --- History rows: the messenger's two-line language (rail + sheet) --- */
        .ai-newq { display: flex; align-items: center; gap: .6rem; width: 100%; border-radius: .8rem;
            padding: .6rem .7rem; font-size: .88rem; font-weight: 700; color: var(--color-brand-700);
            transition: background-color .15s ease; text-align: left; }
        .ai-newq:hover { background: var(--color-brand-50); }
        .ai-hrow { display: flex; align-items: center; gap: .25rem; border-radius: .8rem; padding: .15rem .25rem;
            transition: background-color .15s ease; }
        .ai-hrow:hover { background: var(--color-gray-100); }
        .ai-hrow.is-active { background: var(--color-brand-50); }
        .ai-hrow > a { flex: 1 1 auto; min-width: 0; padding: .45rem .45rem; }
        .ai-hrow .t { display: block; font-size: .88rem; font-weight: 700; color: var(--color-gray-800);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ai-hrow.is-active .t { color: var(--color-brand-800); }
        .ai-hrow .meta { display: block; font-size: .68rem; font-weight: 600; color: var(--color-gray-400);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: .1rem; }
        .ai-hrow .meta .lnk { color: var(--color-brand-600); }
        .ai-hact { width: 2rem; height: 2rem; border-radius: .55rem; display: inline-flex; align-items: center;
            justify-content: center; color: var(--color-gray-400); flex-shrink: 0; opacity: 0; transition: opacity .15s ease, background-color .15s ease; }
        .ai-hrow:hover .ai-hact, .ai-hrow.is-active .ai-hact { opacity: 1; }
        .ai-hact:hover { background: var(--color-gray-200); color: #b91c1c; }
        /* In the touch sheet there is no hover — the action stays visible. */
        #aiHistorySheet .ai-hact { opacity: 1; }
        .ai-hempty { text-align: center; padding: 1.5rem .75rem; color: var(--color-gray-400); font-size: .82rem; line-height: 1.5; }

        /* --- Chat column: a full-height flex column so the composer is truly
               pinned and the thread scrolls inside (dvh keeps the phone URL
               bar honest). --- */
        .aichat { display: flex; flex-direction: column; height: calc(100dvh - 12.5rem); min-height: min(26rem, 60dvh); width: 100%; }
        @media (max-width: 767px) { .aichat { height: calc(100dvh - 15rem); min-height: min(22rem, 55dvh); } }
        .aichat-thread { flex: 1 1 auto; overflow-y: auto; padding: .5rem .25rem 1.25rem; scroll-behavior: smooth; display: flex; flex-direction: column; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent; }
        .aichat-thread::-webkit-scrollbar { width: 6px; }
        .aichat-thread::-webkit-scrollbar-track { background: transparent; }
        .aichat-thread::-webkit-scrollbar-thumb { background: var(--color-gray-300); border-radius: 999px; }
        #aiWelcome { margin: auto 0; }
        .aimsg:first-child, .aichat-day:first-child { margin-top: auto; }

        /* --- Day separators: quiet hairlines with a whispered date --- */
        .aichat-day { display: flex; align-items: center; gap: .75rem; margin: .75rem 0; text-align: center; }
        .aichat-day::before, .aichat-day::after { content: ""; flex: 1; height: 1px; background: var(--color-gray-200); }
        .aichat-day span { font-size: .69rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }

        /* --- Masthead: the app's drifting header green (messenger language,
               gradSweep tide from the layout) --- */
        .ai-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .7rem .9rem; margin-bottom: .75rem; border-radius: 1.25rem; color: #fff;
            background: linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
            background-size: 240% 240%; animation: gradSweep 12s ease-in-out infinite alternate;
            box-shadow: 0 4px 16px -6px rgb(45 80 22 / .45); }
        .ai-avatar { position: relative; flex-shrink: 0; }
        .ai-avatar .aimsg-face { width: 2.75rem; height: 2.75rem; box-shadow: 0 0 0 2px rgb(255 255 255 / .9); background: rgb(255 255 255 / .16); color: #fff; }
        .ai-avatar::after { content: ""; position: absolute; right: -1px; bottom: -1px; width: .75rem; height: .75rem; border-radius: 999px; background: var(--color-accent-500); border: 2.5px solid #3d6823; }
        .ai-head-name { font-family: var(--font-heading); font-weight: 700; font-size: 1.02rem; line-height: 1.15; color: #fff; }
        .ai-role { display: inline-flex; align-items: center; margin-top: .2rem; padding: .1rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; color: #fff; background: rgb(255 255 255 / .18); }
        .ai-credits { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.5rem; padding: .3rem .8rem; border-radius: 999px; background: rgb(255 255 255 / .18); color: #fff; font-weight: 800; font-size: .92rem; font-variant-numeric: tabular-nums; transition: background .15s ease; }
        .ai-credits:hover { background: rgb(255 255 255 / .28); }
        .ai-credits svg { color: var(--color-accent-400); }
        .ai-sq { width: 2.5rem; height: 2.5rem; min-height: 2.5rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; background: rgb(255 255 255 / .18); color: #fff; transition: background .15s ease, transform .15s ease; }
        .ai-sq:hover { background: rgb(255 255 255 / .28); }
        .ai-sq:active { transform: scale(.94); }
        .ai-credits:focus-visible, .ai-sq:focus-visible, .aisuggest:focus-visible, #aiSendBtn:focus-visible, .ai-cam:focus-visible { outline: 2px solid var(--color-accent-400); outline-offset: 2px; }
        @media (max-width: 479px) {
            .ai-head { flex-wrap: wrap; row-gap: .5rem; padding: .6rem .75rem; }
            .ai-head > div:last-child { margin-left: auto; }
        }

        /* --- Welcome hero --- */
        .ai-hello { text-align: center; padding: 2rem 1.25rem 1.25rem; border-radius: 1.5rem; background: radial-gradient(120% 90% at 50% 0%, var(--color-brand-50) 0%, transparent 70%); }
        .ai-hello .aimsg-face { width: 4.5rem; height: 4.5rem; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(74 124 42 / .45); animation: aiFloatIdle 5s ease-in-out infinite; }
        .ai-hello h2 { font-family: var(--font-heading); font-size: 1.4rem; font-weight: 700; margin-top: 1rem; color: var(--color-gray-900); }
        .ai-hello .sub { font-size: 1rem; color: var(--color-gray-500); margin-top: .4rem; max-width: 26rem; margin-inline: auto; line-height: 1.6; }
        .ai-caps { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; margin-top: .9rem; }
        .ai-cap { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid var(--color-brand-100); }
        .ai-overline { font-size: .7rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: var(--color-gray-400); margin: 1.6rem 0 .6rem; }
        @keyframes aiFloatIdle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        /* --- Suggestion cards --- */
        .aisuggest { display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 3.5rem; padding: .75rem 1rem; text-align: left; border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white); box-shadow: var(--shadow-card); font-size: .97rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; transition: transform .18s cubic-bezier(.22,1,.36,1), border-color .18s ease, box-shadow .18s ease; animation: aiRise .3s ease both; }
        .aisuggest:nth-child(2) { animation-delay: .06s; }
        .aisuggest:nth-child(3) { animation-delay: .12s; }
        .aisuggest:hover { transform: translateY(-1px); border-color: var(--color-brand-300); box-shadow: var(--shadow-card-lg); }
        .aisuggest:active { transform: scale(.98); }
        .aisuggest .ic { width: 2.25rem; height: 2.25rem; border-radius: .75rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
        .aisuggest .t { flex: 1 1 auto; min-width: 0; }
        .aisuggest .go { margin-left: auto; flex-shrink: 0; color: var(--color-gray-400); transition: transform .18s ease, color .18s ease; }
        .aisuggest:hover .go { transform: translateX(3px); color: var(--color-brand-600); }

        /* --- Turns. Only NEW messages animate in — server-rendered history
               arrives settled, it does not cascade on load. --- */
        .aimsg { display: flex; gap: .65rem; margin-bottom: 1rem; align-items: flex-end; }
        .aimsg.is-new { animation: aiRise .28s cubic-bezier(.22,1,.36,1) both; }
        .aimsg.me { flex-direction: row-reverse; }
        @keyframes aiRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .aimsg-face {
            width: 2.4rem; height: 2.4rem; border-radius: 999px; flex-shrink: 0; overflow: hidden;
            display: flex; align-items: center; justify-content: center; margin-bottom: .1rem;
            background: var(--color-brand-50); color: var(--color-brand-700); font-size: .78rem; font-weight: 800;
        }
        .aimsg-face img { width: 100%; height: 100%; object-fit: cover; }
        .aimsg.me .aimsg-face { background: var(--color-brand-600); color: #fff; }
        .aimsg:not(.me) .aimsg-face { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 3px var(--color-brand-200); }

        /* Assistant "writes" on a sheet; the user "sends" a green slip. */
        .aibubble { max-width: min(82%, 34rem); padding: .8rem 1.05rem; font-size: 1.02rem; line-height: 1.62; background: var(--color-white); border: 1px solid var(--color-gray-100); border-radius: 1.15rem 1.15rem 1.15rem .35rem; box-shadow: 0 1px 2px rgb(26 26 26 / .06), 0 3px 10px -4px rgb(26 26 26 / .08); }
        /* Literal hex: var(--color-brand-700) repoints bright in dark mode and
           would sink the white text. */
        .aimsg.me .aibubble { background: linear-gradient(135deg, #4a7c2a, #3d6823); border-color: transparent; color: #fff; border-radius: 1.15rem 1.15rem .35rem 1.15rem; box-shadow: 0 3px 12px -4px rgb(45 80 22 / .45); }
        .aibubble p { margin: .4rem 0; } .aibubble p:first-child { margin-top: 0; } .aibubble p:last-child { margin-bottom: 0; }
        .aibubble ul { list-style: disc; padding-left: 1.25rem; margin: .4rem 0; }
        .aibubble ol { list-style: decimal; padding-left: 1.4rem; margin: .4rem 0; }
        .aibubble li { margin: .2rem 0; }
        .aibubble strong { font-weight: 700; }
        .aibubble img { max-width: 100%; max-height: 260px; border-radius: .6rem; margin-top: .4rem; }
        /* If an answer ever carries a table, it scrolls inside the bubble —
           never the page. */
        .aibubble table { display: block; max-width: 100%; overflow-x: auto; border-collapse: collapse; font-size: .9em; margin: .4rem 0; }
        .aibubble th, .aibubble td { border: 1px solid var(--color-gray-200); padding: .3rem .55rem; text-align: left; }
        /* A whispered clock, not a shout. */
        .ai-when { display: block; font-size: .66rem; font-weight: 600; opacity: .55; margin-top: .3rem; text-align: right; font-variant-numeric: tabular-nums; }
        .aibubble-cost { display: inline-flex; align-items: center; gap: .3rem; margin-top: .55rem; padding: .12rem .55rem; border-radius: 999px; font-size: .69rem; font-weight: 800; font-variant-numeric: tabular-nums; color: #8a6100; background: rgb(245 197 24 / .15); }
        .aibubble-cost::before { content: ""; width: .4rem; height: .4rem; border-radius: 999px; background: var(--color-accent-500); }
        .aimsg.me .aibubble-cost { background: rgb(255 255 255 / .2); color: #fff; }
        .aimsg.me .aibubble-cost::before { background: #fff; }

        /* --- Typing dots --- */
        .aidots { display: inline-flex; gap: .25rem; align-items: center; height: 1.2rem; }
        .aidots i { width: .42rem; height: .42rem; border-radius: 999px; background: var(--color-brand-500); opacity: .35; animation: aidot .9s cubic-bezier(.4,0,.2,1) infinite; }
        .aidots i:nth-child(2) { animation-delay: .15s; } .aidots i:nth-child(3) { animation-delay: .3s; }
        @keyframes aidot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        @keyframes ai-spin { to { transform: rotate(360deg); } }

        /* --- Composer dock --- */
        .aichat-composer { flex-shrink: 0; padding: .6rem 0 .1rem; background: linear-gradient(to top, var(--color-gray-50) 70%, transparent); }
        .aichat-box { display: flex; align-items: flex-end; gap: .4rem; padding: .45rem; border-radius: 1.35rem; background: var(--color-white); border: 1.5px solid var(--color-gray-200); box-shadow: var(--shadow-card-lg); transition: border-color .15s ease, box-shadow .15s ease; }
        .aichat-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18), var(--shadow-card-lg); }
        .ai-cam { width: 2.75rem; height: 2.75rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; transition: background .15s ease; }
        .ai-cam:hover { background: var(--color-brand-100); }
        #aiInput { resize: none; max-height: 9rem; font-size: 1rem; }
        #aiSendBtn { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.9rem; padding: 0 1.1rem; border-radius: 999px; color: #fff; font-weight: 700; font-size: .92rem; background: linear-gradient(140deg, #6b9f3d, #3d6823); box-shadow: 0 4px 12px -3px rgb(45 80 22 / .5); transition: transform .15s ease, opacity .15s ease; flex-shrink: 0; }
        #aiSendBtn:hover:not(:disabled) { transform: scale(1.04); }
        #aiSendBtn:active:not(:disabled) { transform: scale(.95); }
        #aiSendBtn:disabled { opacity: .55; }
        .ai-hint { text-align: center; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-top: .4rem; }
        #aiPhotoChip { background: var(--color-gray-100); border-radius: .75rem; padding: .35rem .6rem; }
        #aiPhotoChip.hidden { display: none; }
        #aiPhotoThumb { box-shadow: 0 0 0 2px var(--color-brand-200); }
        #aiSchedule { max-width: 11rem; }

        /* --- Notices: warm field notes (accent = money/attention) --- */
        .ai-note { display: flex; gap: .8rem; align-items: flex-start; margin-bottom: .75rem; padding: 1rem 1.1rem; border-radius: 1.25rem; border: 1px solid rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); }
        .ai-note.hidden { display: none; }
        /* Literal ink: var(--color-ink) flips near-white in dark mode. */
        .ai-note .ico { width: 2.4rem; height: 2.4rem; border-radius: .85rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }
        .ai-note h3 { font-family: var(--font-heading); font-size: 1rem; font-weight: 700; color: var(--color-gray-900); }
        .ai-note p { font-size: .85rem; color: var(--color-gray-600); margin-top: .15rem; }

        /* --- Dark mode: the var repoint does 90%; a few nudges finish it --- */
        html.dark .aibubble { box-shadow: 0 1px 2px rgb(0 0 0 / .35), 0 3px 10px -4px rgb(0 0 0 / .4); }
        html.dark .aibubble-cost { color: var(--color-accent-400); }
        html.dark .aimsg.me .aibubble-cost { color: #fff; }
        html.dark .aichat-box:focus-within { box-shadow: 0 0 0 3px rgb(124 184 79 / .22), var(--shadow-card-lg); }
        html.dark .ai-note { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }
        html.dark .ai-hello .aimsg-face { box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(0 0 0 / .6); }

        @media (prefers-reduced-motion: reduce) {
            .ai-head, .aimsg.is-new, .aisuggest, .ai-hello .aimsg-face { animation: none; }
            .aisuggest, .aisuggest .go, .aichat-box, #aiSendBtn, .ai-credits, .ai-sq { transition: none; }
            /* Slowed, not stopped — the pulse is the message that work is happening. */
            .aidots i { animation-duration: 1.8s; }
            [style*="ai-spin"] { animation-duration: 1.6s !important; }
        }
    </style>
@endpush

@section('content')

{{-- Dynamic breadcrumbs — the last crumb tracks the open chat and updates
     without a reload when you start a new question. --}}
<nav class="flex items-center gap-1.5 text-xs font-semibold text-gray-400 mb-3 flex-wrap" aria-label="Breadcrumb">
    <a href="{{ route('app.dashboard') }}" class="hover:text-brand-700 transition inline-flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
        Dashboard
    </a>
    <span class="text-gray-300">/</span>
    <a href="{{ route('ai.index') }}" class="hover:text-brand-700 transition">{{ $settings->assistantName }}</a>
    <span class="text-gray-300">/</span>
    <span id="aiCrumbCurrent" class="text-gray-700 truncate max-w-[45vw] sm:max-w-xs">{{ $conversation && $messages->isNotEmpty() ? \Illuminate\Support\Str::limit($conversation->title, 40) : 'New question' }}</span>
</nav>

<div class="ai-layout">

    {{-- Desktop-only history rail --}}
    <aside class="ai-history-rail">
        <div class="rail-head">
            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h2>Past questions</h2>
        </div>
        <div class="rail-scroll grow space-y-0.5">
            @include('ai.partials.history-list')
        </div>
    </aside>

<div class="aichat">

    {{-- Masthead: identity + wallet + history --}}
    <div class="ai-head">
        <div class="flex items-center gap-3 min-w-0">
            <div class="ai-avatar">
                <span class="aimsg-face">
                    @if ($settings->avatarPath)
                        <img src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
            </div>
            <div class="min-w-0">
                <p class="ai-head-name truncate">{{ $settings->assistantName }}</p>
                <span class="ai-role">Crop Technician</span>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            @unless ($aiUnlimited)
                <a href="{{ route('ai.credits') }}" class="ai-credits" title="AI Credits" aria-label="AI credits balance">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                    <span id="aiBalance">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</span>
                </a>
            @endunless
            <button type="button" class="ai-sq lg:hidden" id="aiHistoryBtn" title="Past questions" aria-label="Past questions">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
        </div>
    </div>

    @unless ($settings->isUsable())
        <div class="ai-note">
            <span class="ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 6.64a9 9 0 11-12.73 0M12 2v10"/></svg>
            </span>
            <div>
                <h3>The AI Technician is not switched on yet</h3>
                <p>It will appear here as soon as it is configured.</p>
            </div>
        </div>
    @endunless

    {{-- Out of credits --}}
    <div class="ai-note {{ ($balance > 0 || $aiUnlimited) ? 'hidden' : '' }}" id="aiNoCredits">
        <span class="ico">
            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
        </span>
        <div>
            <h3>You have no AI Credits left</h3>
            <p>A question costs about 4 credits, or 7 with a photo. Top up to keep asking.</p>
            <a href="{{ route('ai.credits') }}" class="btn btn-accent btn-sm mt-2">Get AI Credits</a>
        </div>
    </div>

    {{-- Thread. History renders settled (no entrance cascade); day separators
         and whispered timestamps keep a long thread readable. --}}
    <div class="aichat-thread" id="aiThread">
        @php $aiPrevDay = null; @endphp
        @forelse ($messages as $m)
            @php
                $aiDay = $m->created_at?->isToday() ? 'Today' : ($m->created_at?->isYesterday() ? 'Yesterday' : $m->created_at?->format('M j, Y'));
            @endphp
            @if ($aiDay && $aiDay !== $aiPrevDay)
                <div class="aichat-day"><span>{{ $aiDay }}</span></div>
                @php $aiPrevDay = $aiDay; @endphp
            @endif
            <div class="aimsg {{ $m->role === 'user' ? 'me' : '' }}">
                <span class="aimsg-face">
                    @if ($m->role === 'user')
                        {{ auth()->user()->initials }}
                    @elseif ($settings->avatarPath)
                        <img src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <div class="aibubble">
                    {!! \App\Support\AiMarkdown::toHtml($m->content) !!}
                    @if ($m->imagePath)
                        <img src="{{ \App\Support\MediaStore::url($m->imagePath) }}" alt="Attached photo">
                    @endif
                    @if ($m->role === 'assistant' && (float) $m->creditsCharged > 0 && ! $aiUnlimited)
                        <p class="aibubble-cost">{{ rtrim(rtrim(number_format((float) $m->creditsCharged, 2), '0'), '.') }} credits</p>
                    @endif
                    @if ($m->created_at)
                        <time class="ai-when" datetime="{{ $m->created_at->toIso8601String() }}">{{ $m->created_at->format('g:i A') }}</time>
                    @endif
                </div>
            </div>
        @empty
            @include('ai.partials.welcome')
        @endforelse
    </div>

    {{-- Welcome markup reused by JS when a new question resets the thread. --}}
    <template id="aiWelcomeTpl">
        @include('ai.partials.welcome')
    </template>

    {{-- Composer: pinned to the bottom of the dvh column --}}
    <div class="aichat-composer">
        <div id="aiPhotoChip" class="hidden mb-1.5 flex items-center gap-2 text-xs font-semibold text-gray-600">
            <img src="" alt="" class="w-9 h-9 rounded-lg object-cover" id="aiPhotoThumb">
            <span>Photo attached</span>
            <button type="button" class="text-red-600 font-bold" id="aiPhotoRemove">Remove</button>
        </div>
        <div class="aichat-box">
            <label class="ai-cam shrink-0" title="Attach a photo" aria-label="Attach a photo">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <input type="file" id="aiPhoto" accept="image/*" capture="environment" class="hidden">
            </label>
            <textarea id="aiInput" class="form-textarea border-0! shadow-none! focus:ring-0! p-2 grow bg-transparent!" rows="1"
                      maxlength="4000" placeholder="Ask about your crop…"
                      {{ $settings->isUsable() ? '' : 'disabled' }}></textarea>
            <button type="button" id="aiSendBtn" {{ $settings->isUsable() ? '' : 'disabled' }} aria-label="Send">
                <span class="ai-send-label">Ask</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
            </button>
        </div>
        <div class="flex items-center justify-center gap-2 mt-1.5">
            @if ($schedules->isNotEmpty())
                <select id="aiSchedule" class="form-select text-xs py-1 pl-2 pr-7 w-auto" title="Which plan is this about?">
                    <option value="">No plan</option>
                    @foreach ($schedules as $s)
                        <option value="{{ $s->id }}" @selected($conversation && $conversation->croppingScheduleId == $s->id)>{{ \Illuminate\Support\Str::limit($s->title, 26) }}</option>
                    @endforeach
                </select>
            @endif
            @unless ($aiUnlimited)
                <p class="ai-hint mt-0!">≈ 4 credits per answer · 7 with a photo</p>
            @endunless
        </div>
    </div>
</div>{{-- /.aichat --}}
</div>{{-- /.ai-layout --}}
@endsection

@push('sheets')
<div class="sheet hidden" id="aiHistorySheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Past questions</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        @include('ai.partials.history-list')
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const URLS = {
        ask: @json(route('ai.ask')),
        photo: @json(route('ai.photo')),
        newConvo: @json(route('ai.conversation.new')),
        delConvo: (id) => @json(route('ai.conversation.delete')) + '?id=' + id,
    };
    const AVATAR = @json($settings->avatarPath ? \App\Support\MediaStore::url($settings->avatarPath) : null);
    const MY_INITIALS = @json(auth()->user()->initials);
    const UNLIMITED = @json((bool) $aiUnlimited);
    const BOT_SVG = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>';

    let conversationId = @json($conversation->id ?? null);
    let photoPath = null;
    let busy = false;

    const byId = (id) => document.getElementById(id);
    const thread = byId('aiThread');

    const face = (isMe) => isMe
        ? escapeHtml(MY_INITIALS)
        : (AVATAR ? `<img src="${escapeHtml(AVATAR)}" alt="">` : BOT_SVG);
    const nowStamp = () => new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

    /**
     * The model answers in light markdown. Everything is escaped first, then a
     * small allow-list of bold / bullets / numbered lists / paragraphs is put
     * back — so nothing the model emits can inject markup.
     */
    function renderAnswer(text) {
        const esc = escapeHtml(text || '');
        const lines = esc.split(/\r?\n/);
        let html = '';
        let list = null;

        const closeList = () => { if (list) { html += `</${list}>`; list = null; } };

        for (const raw of lines) {
            const line = raw.trim();
            if (!line) { closeList(); continue; }

            const bullet = line.match(/^[-*•]\s+(.*)$/);
            const numbered = line.match(/^(\d+)[.)]\s+(.*)$/);

            if (bullet) {
                if (list !== 'ul') { closeList(); html += '<ul>'; list = 'ul'; }
                html += '<li>' + inline(bullet[1]) + '</li>';
            } else if (numbered) {
                if (list !== 'ol') { closeList(); html += '<ol>'; list = 'ol'; }
                html += '<li>' + inline(numbered[2]) + '</li>';
            } else {
                closeList();
                html += '<p>' + inline(line) + '</p>';
            }
        }
        closeList();
        return html || '<p></p>';
    }
    const inline = (s) => s
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|\s)\*([^*]+)\*(?=\s|$|[.,;:!?])/g, '$1<em>$2</em>');

    // New turns wear .is-new so only they animate in — history stays settled.
    function addTurn(isMe, html, imageUrl, costLine, stamped) {
        byId('aiWelcome')?.remove();
        const el = document.createElement('div');
        el.className = 'aimsg is-new' + (isMe ? ' me' : '');
        el.innerHTML = `
            <span class="aimsg-face">${face(isMe)}</span>
            <div class="aibubble">
                ${html}
                ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="Attached photo">` : ''}
                ${costLine ? `<p class="aibubble-cost">${escapeHtml(costLine)} credits</p>` : ''}
                ${stamped ? `<time class="ai-when">${escapeHtml(nowStamp())}</time>` : ''}
            </div>`;
        thread.appendChild(el);
        el.scrollIntoView({ behavior: 'smooth', block: 'end' });
        return el;
    }

    function setBalance(value) {
        const balEl = byId('aiBalance');
        if (balEl) balEl.textContent = String(Math.round(value * 100) / 100);
        // Accounts that ride free never see the empty-wallet note.
        byId('aiNoCredits')?.classList.toggle('hidden', UNLIMITED || value > 0);
    }

    // The send button says what it is doing.
    function setSending(on) {
        const btn = byId('aiSendBtn');
        if (!btn) return;
        btn.disabled = on;
        const label = btn.querySelector('.ai-send-label');
        if (label) label.textContent = on ? 'Sending…' : 'Ask';
        btn.setAttribute('aria-label', on ? 'Sending' : 'Send');
    }

    // Textarea grows with the message, up to the CSS max-height.
    const input = byId('aiInput');
    input?.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 144) + 'px';
    });
    input?.addEventListener('keydown', (e) => {
        // Enter sends on a desktop keyboard; Shift+Enter always adds a line.
        if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) {
            e.preventDefault();
            send();
        }
    });

    // Delegated so suggestion chips added by a reset-to-welcome also work.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-suggest');
        if (!btn || !input) return;
        input.value = (btn.querySelector('.t')?.textContent || btn.textContent).trim();
        input.dispatchEvent(new Event('input'));
        input.focus();
    });

    /* ---- Photo ---- */
    byId('aiPhoto')?.addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        const form = new FormData();
        form.append('image', file);
        try {
            const res = await api(URLS.photo, { method: 'POST', body: form });
            photoPath = res.data.path;
            byId('aiPhotoThumb').src = res.data.url;
            byId('aiPhotoChip').classList.remove('hidden');
            byId('aiPhotoChip').classList.add('flex');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            e.target.value = '';
        }
    });
    byId('aiPhotoRemove')?.addEventListener('click', () => {
        photoPath = null;
        byId('aiPhotoChip').classList.add('hidden');
        byId('aiPhotoChip').classList.remove('flex');
    });

    /* ---- Ask ---- */
    async function send() {
        if (busy) return;
        const message = input.value.trim();
        if (!message) { toast('Type a question first.', 'error'); return; }

        busy = true;
        setSending(true);
        const myPhoto = photoPath ? byId('aiPhotoThumb').src : null;
        addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', myPhoto, null, true);
        input.value = '';
        input.style.height = 'auto';

        const thinking = addTurn(false, '<span class="aidots"><i></i><i></i><i></i></span>');

        try {
            const res = await api(URLS.ask, {
                method: 'POST',
                body: {
                    message,
                    conversationId,
                    imagePath: photoPath,
                    scheduleId: byId('aiSchedule')?.value || null,
                },
            });
            conversationId = res.data.conversationId;
            const costLine = UNLIMITED ? '' : `<p class="aibubble-cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
            thinking.querySelector('.aibubble').innerHTML =
                renderAnswer(res.data.answer.content)
                + costLine
                + `<time class="ai-when">${escapeHtml(nowStamp())}</time>`;
            setBalance(res.data.balance);
            byId('aiPhotoRemove').click();
            thinking.scrollIntoView({ behavior: 'smooth', block: 'end' });
        } catch (err) {
            thinking.remove();
            if (err.data && err.data.outOfCredits) {
                setBalance(err.data.balance || 0);
                addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'
                    + `<p style="margin-top:.5rem"><a class="btn btn-accent btn-sm" href="${escapeHtml(@json(route('ai.credits')))}">Get AI Credits</a></p>`);
            } else {
                addTurn(false, '<p>' + escapeHtml(err.message) + '</p>');
            }
            // Give the question back so it is not lost.
            input.value = message;
            input.dispatchEvent(new Event('input'));
        } finally {
            busy = false;
            setSending(false);
            input.focus();
        }
    }
    byId('aiSendBtn')?.addEventListener('click', send);

    /* ---- Conversations ---- */
    byId('aiHistoryBtn')?.addEventListener('click', () => openSheet('aiHistorySheet'));

    const AI_INDEX = @json(route('ai.index'));
    const SPIN_SVG = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="animation:ai-spin .6s linear infinite"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.4" stroke-opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';

    const setCrumb = (title) => { const el = byId('aiCrumbCurrent'); if (el) el.textContent = title || 'New question'; };
    function resetToWelcome() {
        const tpl = byId('aiWelcomeTpl');
        thread.innerHTML = '';
        if (tpl && tpl.content) thread.appendChild(tpl.content.cloneNode(true));
    }
    function markConvoActive(id) {
        document.querySelectorAll('.ai-history-rail .rail-scroll .ai-hrow, #aiHistorySheet .sheet-body .ai-hrow').forEach((row) => {
            const a = row.querySelector('a');
            row.classList.toggle('is-active', !!a && a.getAttribute('href') === AI_INDEX + '?c=' + id);
        });
    }
    function addConvoEntry(id, title) {
        const entry = `<div class="ai-hrow is-active">
            <a href="${AI_INDEX}?c=${id}">
                <span class="t">${escapeHtml(title)}</span>
                <span class="meta">just now</span>
            </a>
            <button type="button" class="ai-hact js-del-convo" data-id="${id}" aria-label="Delete conversation">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>`;
        document.querySelectorAll('.ai-history-rail .rail-scroll, #aiHistorySheet .sheet-body').forEach((cont) => {
            cont.querySelectorAll('.ai-hrow').forEach((r) => r.classList.remove('is-active'));
            cont.querySelector('.ai-hempty')?.remove();
            const nb = cont.querySelector('.js-ai-new');
            if (nb) nb.insertAdjacentHTML('afterend', entry);
        });
    }

    // "Start a new question" — dynamic (no reload): reset the thread, refresh the
    // breadcrumb + history list, update the URL. Shows a loader while it runs.
    document.querySelectorAll('.js-ai-new').forEach((b) => b.addEventListener('click', async () => {
        if (b.dataset.loading) return;
        const prev = b.innerHTML;
        b.dataset.loading = '1';
        b.disabled = true;
        b.innerHTML = SPIN_SVG + '<span>Starting…</span>';
        try {
            const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: byId('aiSchedule')?.value || null } });
            conversationId = res.data.conversationId;
            resetToWelcome();
            setCrumb('New question');
            addConvoEntry(conversationId, 'New question');
            markConvoActive(conversationId);
            history.replaceState({}, '', AI_INDEX + '?c=' + conversationId);
            window.closeSheet && window.closeSheet('aiHistorySheet');
            byId('aiInput')?.focus();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            b.disabled = false;
            delete b.dataset.loading;
            b.innerHTML = prev;
        }
    }));
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-del-convo');
        if (!btn) return;
        const ok = await confirmAction({
            title: 'Delete this conversation?',
            message: 'The questions and answers in it will be removed.',
            detail: 'Credits already spent are not refunded.',
            confirmText: 'Delete',
        });
        if (!ok) return;
        try {
            await api(URLS.delConvo(btn.dataset.id), { method: 'DELETE' });
            window.location.href = '{{ route('ai.index') }}';
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    thread?.lastElementChild?.scrollIntoView({ block: 'end' });
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
