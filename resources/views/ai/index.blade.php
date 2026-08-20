@extends('layouts.app')
{{-- On phones this page is a chat app: the tab bar would eat the composer's
     row, so it steps aside the way the Collab Room's does. --}}
@section('body-class', 'hide-tabbar')

@section('title', $settings->assistantName)
@section('page-title', 'AI Technician')
@section('page-subtitle', 'Crop questions, answered')

@php
    // Super admins ride free — the wallet row hides for them (view-side check,
    // same pattern the floating assistant already uses).
    $aiUnlimited = app(\App\Services\AiCreditService::class)->unlimited((int) auth()->id());
    // The real per-photo price, so the hint stays honest when several photos
    // ride on one question.
    $aiPerPhoto = (float) ($settings->creditsPerImage ?? 0);
    $aiPerPhotoTxt = rtrim(rtrim(number_format($aiPerPhoto, 2), '0'), '.');
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
        /* On a phone this page IS the chat: the tab bar is hidden (body
           class), the footer steps aside, main's paddings are cancelled, and
           the column runs to the true bottom of the viewport — measured, so
           the composer sits ON the footer line instead of floating 115px
           above it. 6.3rem = app bar + main's top padding + one crumb line. */
        @media (max-width: 767px) {
            .aichat { height: calc(100dvh - 6.3rem); min-height: min(22rem, 55dvh); margin-bottom: -1rem; }
            .aichat-composer { padding-bottom: calc(.3rem + env(safe-area-inset-bottom)); }
            footer { display: none; }
        }
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
        .ai-credits { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.1rem; padding: .25rem .7rem; border-radius: 999px; background: rgb(255 255 255 / .18); color: #fff; font-weight: 800; font-size: .8rem; font-variant-numeric: tabular-nums; transition: background .15s ease; }
        .ai-credits:hover { background: rgb(255 255 255 / .28); }
        .ai-credits svg { color: var(--color-accent-400); }
        .ai-sq { width: 2.5rem; height: 2.5rem; min-height: 2.5rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; background: rgb(255 255 255 / .18); color: #fff; transition: background .15s ease, transform .15s ease; }
        .ai-sq:hover { background: rgb(255 255 255 / .28); }
        .ai-sq:active { transform: scale(.94); }
        .ai-credits:focus-visible, .ai-sq:focus-visible, .aisuggest:focus-visible, #aiSendBtn:focus-visible, .ai-cam:focus-visible { outline: 2px solid var(--color-accent-400); outline-offset: 2px; }
        /* One row on phones, not two: the name truncates, so nothing needs
           the wrap that used to drop the wallet under the title and double
           the masthead's height on exactly the screens with least to spare. */
        @media (max-width: 479px) {
            .ai-head { padding: .55rem .7rem; gap: .5rem; }
            .ai-avatar .aimsg-face { width: 2.25rem; height: 2.25rem; }
            .ai-head-name { font-size: .92rem; }
            .ai-role { font-size: .6rem; padding: .08rem .45rem; }
        }

        /* --- Welcome hero --- */
        .ai-hello { text-align: center; padding: 2rem 1.25rem 1.25rem; border-radius: 1.5rem; background: radial-gradient(120% 90% at 50% 0%, var(--color-brand-50) 0%, transparent 70%); }
        .ai-hello .aimsg-face { width: 3.5rem; height: 3.5rem; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(74 124 42 / .45); animation: aiFloatIdle 5s ease-in-out infinite; }
        .ai-hello h2 { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; margin-top: 1rem; color: var(--color-gray-900); }
        .ai-hello .sub { font-size: .85rem; color: var(--color-gray-500); margin-top: .4rem; max-width: 26rem; margin-inline: auto; line-height: 1.6; }
        .ai-caps { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; margin-top: .9rem; }
        .ai-cap { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700; color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid var(--color-brand-100); }
        .ai-overline { font-size: .7rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: var(--color-gray-400); margin: 1.6rem 0 .6rem; }
        @keyframes aiFloatIdle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        /* --- Suggestion cards --- */
        .aisuggest { display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 3rem; padding: .6rem .85rem; text-align: left; border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white); box-shadow: var(--shadow-card); font-size: .85rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; transition: transform .18s cubic-bezier(.22,1,.36,1), border-color .18s ease, box-shadow .18s ease; animation: aiRise .3s ease both; }
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
        .aibubble { max-width: min(82%, 34rem); padding: .6rem .85rem; font-size: .92rem; line-height: 1.55; background: var(--color-white); border: 1px solid var(--color-gray-100); border-radius: 1.15rem 1.15rem 1.15rem .35rem; box-shadow: 0 1px 2px rgb(26 26 26 / .06), 0 3px 10px -4px rgb(26 26 26 / .08); }
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
        /* No scrollbar while the box is still growing — the autosize handler
           flips this to auto only once the text passes the max height. */
        #aiInput { resize: none; max-height: 9rem; overflow-y: hidden; font-size: 1rem; }
        #aiSendBtn { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.9rem; padding: 0 1.1rem; border-radius: 999px; color: #fff; font-weight: 700; font-size: .92rem; background: linear-gradient(140deg, #6b9f3d, #3d6823); box-shadow: 0 4px 12px -3px rgb(45 80 22 / .5); transition: transform .15s ease, opacity .15s ease; flex-shrink: 0; }
        #aiSendBtn:hover:not(:disabled) { transform: scale(1.04); }
        #aiSendBtn:active:not(:disabled) { transform: scale(.95); }
        #aiSendBtn:disabled { opacity: .55; }
        .ai-hint { text-align: center; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-top: .4rem; }
        /* The attached plan, above the box. Reads as a thing carried with
           the question, like a photo chip, because that is what it is. */
        .ai-planchip { display: flex; align-items: center; gap: .5rem; margin: 0 0 .4rem; padding: .4rem .55rem;
            border-radius: .7rem; background: var(--color-brand-50); border: 1px solid var(--color-brand-100);
            animation: aiPlanIn .28s cubic-bezier(.22,1,.36,1); }
        .ai-planchip[hidden] { display: none; }
        @keyframes aiPlanIn { from { opacity: 0; transform: translateY(.25rem); } }
        @media (prefers-reduced-motion: reduce) { .ai-planchip { animation: none; } }
        .ai-planchip-ic { flex: none; width: 1.8rem; height: 1.8rem; border-radius: .5rem; display: flex;
            align-items: center; justify-content: center; background: var(--color-white);
            color: var(--color-brand-700); }
        .ai-planchip-ic svg { width: 1rem; height: 1rem; }
        .ai-planchip-txt { display: flex; flex-direction: column; min-width: 0; flex: 1 1 auto; }
        .ai-planchip-txt b { font-size: .76rem; font-weight: 800; color: var(--color-gray-900);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ai-planchip-txt i { font-style: normal; font-size: .66rem; color: var(--color-gray-500); }
        .ai-planchip-x { flex: none; border: 0; background: transparent; color: var(--color-gray-400);
            font-size: .8rem; cursor: pointer; padding: .15rem .25rem; }
        .ai-planchip-x:hover { color: #b91c1c; }
        /* The plan button wears the camera's shape; when a plan is on, it is lit. */
        #aiPlanBtn.is-on { color: var(--color-brand-700); background: var(--color-brand-50); }

        /* --- Attached photo chips: one thumbnail per photo, each with its
               own remove. A chip mid-upload wears a spinner instead of ✕. --- */
        #aiPhotoChips { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .45rem; }
        #aiPhotoChips:empty { display: none; }
        /* Says so while a photo is on the wire - a busy chip alone was easy
           to read as a broken thumbnail rather than work in progress. */
        .ai-busyline { display: flex; align-items: center; gap: .4rem; margin-bottom: .45rem;
            font-size: .72rem; font-weight: 700; color: var(--color-brand-700); }
        .ai-busyline.hidden { display: none; }
        .ai-busyline .sp { width: .8rem; height: .8rem; border-radius: 999px; flex-shrink: 0;
            border: 2px solid var(--color-brand-200); border-top-color: var(--color-brand-600);
            animation: aiBusySpin .8s linear infinite; }
        @keyframes aiBusySpin { to { transform: rotate(360deg); } }
        /* The chip shimmers under its picture while the copy is in flight -
           gallery images can take a moment to even paint. */
        .ai-chip.is-busy { background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-200) 50%, var(--color-gray-100) 60%);
            background-size: 200% 100%; animation: aiChipShimmer 1.2s linear infinite; }
        @keyframes aiChipShimmer { to { background-position: -200% 0; } }
        .ai-chip { position: relative; width: 3.4rem; height: 3.4rem; border-radius: .75rem; overflow: hidden;
            box-shadow: 0 0 0 2px var(--color-brand-200); background: var(--color-gray-100);
            animation: aiChipIn .28s cubic-bezier(.22,1,.36,1) both; }
        .ai-chip img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .ai-chip .x { position: absolute; top: .15rem; right: .15rem; width: 1.2rem; height: 1.2rem;
            border-radius: 999px; display: flex; align-items: center; justify-content: center;
            background: rgb(17 24 39 / .72); color: #fff; font-size: .62rem; font-weight: 800; line-height: 1;
            transition: transform .15s ease, background-color .15s ease; }
        .ai-chip .x:hover { background: #b91c1c; transform: scale(1.1); }
        .ai-chip .st { position: absolute; inset: 0; display: none; align-items: center; justify-content: center;
            background: rgb(255 255 255 / .55); color: var(--color-brand-700); }
        .ai-chip.is-busy .st { display: flex; }
        .ai-chip.is-busy .x { display: none; }
        html.dark .ai-chip .st { background: rgb(0 0 0 / .45); color: #fff; }
        @keyframes aiChipIn { from { opacity: 0; transform: scale(.8); } to { opacity: 1; transform: none; } }

        /* --- Photos inside a bubble: one keeps its natural shape, two or
               more settle into a tidy square grid. --- */
        .ai-shots { display: grid; gap: .35rem; margin-top: .4rem; }
        .ai-shots img { margin-top: 0; }
        .ai-shots.is-multi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ai-shots.is-multi img { width: 100%; aspect-ratio: 1; object-fit: cover; max-height: none; }

        /* --- The attach chooser's doors (house sheet rows) --- */
        .ai-attach-opt { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .7rem .8rem;
            border-radius: .9rem; text-align: left; font-size: .95rem; font-weight: 700; color: var(--color-gray-800);
            transition: background-color .15s ease; }
        .ai-attach-opt:hover { background: var(--color-gray-100); }
        .ai-attach-opt .ic { width: 2.4rem; height: 2.4rem; border-radius: .8rem; flex-shrink: 0; display: flex;
            align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
        .ai-attach-opt .sub { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }

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
            .ai-head, .aimsg.is-new, .aisuggest, .ai-hello .aimsg-face, .ai-chip { animation: none; }
            .aisuggest, .aisuggest .go, .aichat-box, #aiSendBtn, .ai-credits, .ai-sq, .ai-attach-opt, .ai-chip .x { transition: none; }
            /* Slowed, not stopped — the pulse is the message that work is happening. */
            .aidots i { animation-duration: 1.8s; }
            .ai-busyline .sp { animation-duration: 1.6s; }
            .ai-chip.is-busy { animation: none; }
            [style*="ai-spin"] { animation-duration: 1.6s !important; }
        }
    </style>
@endpush

@section('content')

{{-- Dynamic breadcrumbs — the last crumb tracks the open chat and updates
     without a reload when you start a new question. --}}
{{-- On phones the middle crumb is the masthead's own name repeated one line
     above it, and the wrap it forced pushed the chat below the fold — so the
     trail stays one line and the name crumb waits for a wider screen. --}}
<nav class="flex items-center gap-1.5 text-xs font-semibold text-gray-400 mb-3 min-w-0" aria-label="Breadcrumb">
    <a href="{{ route('app.dashboard') }}" class="hover:text-brand-700 transition inline-flex items-center gap-1 shrink-0">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
        Dashboard
    </a>
    <span class="text-gray-300 hidden sm:inline">/</span>
    <a href="{{ route('ai.index') }}" class="hover:text-brand-700 transition hidden sm:inline truncate">{{ $settings->assistantName }}</a>
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
            <p>A question costs about 4 credits{{ $aiPerPhoto > 0 ? ', plus ' . $aiPerPhotoTxt . ' for each photo' : '' }}. Top up to keep asking.</p>
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
                    @php
                        // Every photo on the turn — the new column when it is
                        // there, the legacy single path for older rows.
                        $mShots = array_values(array_filter((array) ($m->imagePaths ?: ($m->imagePath ? [$m->imagePath] : []))));
                    @endphp
                    @if ($mShots)
                        <div class="ai-shots {{ count($mShots) > 1 ? 'is-multi' : '' }}">
                            @foreach ($mShots as $mShot)
                                <img src="{{ \App\Support\MediaStore::url($mShot) }}" alt="Attached photo">
                            @endforeach
                        </div>
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
        {{-- One chip per attached photo, each with its own remove. --}}
        <div id="aiPhotoChips" aria-label="Attached photos" aria-live="polite"></div>
        {{-- The attached plan, and what it is adding. --}}
        <div id="aiPlanChip" class="ai-planchip" hidden>
            <span class="ai-planchip-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <span class="ai-planchip-txt"><b id="aiPlanName">Plan</b><i id="aiPlanSub">attached</i></span>
            <button type="button" id="aiPlanX" class="ai-planchip-x" aria-label="Remove the plan">✕</button>
        </div>
        <div id="aiAttachBusy" class="ai-busyline hidden" role="status"><span class="sp" aria-hidden="true"></span><span class="tx">Attaching photo…</span></div>
        <div class="aichat-box">
            <button type="button" class="ai-cam shrink-0" id="aiAttachBtn" title="Add photos" aria-label="Add photos" aria-haspopup="dialog">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
            {{-- Beside the camera, because it is the same kind of act: this
                 question carries something extra. Not a dropdown — a chat is
                 not "about" a season, a question either brings the plan or
                 does not. --}}
            @if ($schedules->isNotEmpty())
                <button type="button" class="ai-cam shrink-0" id="aiPlanBtn" title="Attach my plan" aria-label="Attach my plan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </button>
            @endif
            <input type="file" id="aiPhotoFiles" accept="image/*" multiple class="hidden">
            <input type="file" id="aiPhotoCam" accept="image/*" capture="environment" class="hidden">
            <textarea id="aiInput" class="form-textarea border-0! shadow-none! focus:ring-0! p-2 grow bg-transparent!" rows="1"
                      maxlength="4000" placeholder="Ask about your crop…"
                      {{ $settings->isUsable() ? '' : 'disabled' }}></textarea>
            <button type="button" id="aiSendBtn" {{ $settings->isUsable() ? '' : 'disabled' }} aria-label="Send">
                <span class="ai-send-label">Ask</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
            </button>
        </div>
        <div class="flex items-center justify-center gap-2 mt-1.5">
                <p class="ai-hint mt-0!" id="aiHint" data-idle="≈ 4 credits per answer{{ $aiPerPhoto > 0 ? ' · +' . $aiPerPhotoTxt . ' per photo' : '' }}">≈ 4 credits per answer{{ $aiPerPhoto > 0 ? ' · +' . $aiPerPhotoTxt . ' per photo' : '' }}</p>
        </div>
    </div>
</div>{{-- /.aichat --}}
</div>{{-- /.ai-layout --}}
@endsection

@push('sheets')
{{-- The attach chooser: every way a photo can arrive, behind one button
     (the messenger's + chooser, spoken in the house sheet language). The
     gallery door only shows where the season picker travels with the page. --}}
{{-- Which plan, when there is more than one. --}}
<div class="sheet hidden" id="aiPlanSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Attach a plan</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1" id="aiPlanList"></div>
</div>

<div class="sheet hidden" id="aiAttachSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add photos</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="ai-attach-opt" id="aiAttachUpload">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
            <span>Upload photos<span class="sub">Pick one or several from your device</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiAttachCamera">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
            <span>Take a photo<span class="sub">Point the camera at the problem</span></span>
        </button>
        <button type="button" class="ai-attach-opt hidden" id="aiAttachGallery">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>
            <span>From the gallery<span class="sub">A photo one of your seasons keeps</span></span>
        </button>
    </div>
</div>
{{-- The season picker itself — this page never carried it, so the gallery
     door above stayed shut for everyone. @once, so nothing doubles up. --}}
@include('sm.partials.media-picker')

{{-- The big green masthead is gone on the owner's ask; what it held lives
     behind one square button beside the bell. --}}
<div class="sheet hidden" id="aiMenuSheet" style="--sheet-width:20rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">{{ $settings->assistantName }}</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="ai-attach-opt js-ai-new" id="aiMenuNew">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></span>
            <span>New session<span class="sub">Start a fresh question</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiMenuHistory">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
            <span>Recent chats<span class="sub">Pick up an earlier question</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiMenuLink">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m7.156-2.344a4 4 0 015.656 0l.014.014a4 4 0 010 5.642l-1.5 1.5M8.5 15.5l7-7"/></svg></span>
            <span>Link<span class="sub">Tie this chat to one of your plans</span></span>
        </button>
        @unless ($aiUnlimited)
            <a href="{{ route('ai.credits') }}" class="ai-attach-opt">
                <span class="ic"><svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg></span>
                <span>AI credits<span class="sub"><span id="aiBalance">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</span> left — top up here</span></span>
            </a>
        @endunless
    </div>
</div>
@endpush

@push('appbar-actions')
<button type="button" id="aiMenuBtn"
        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full text-gray-500 hover:bg-gray-100 transition overflow-hidden"
        title="AI Technician options" aria-label="AI Technician options" aria-haspopup="dialog">
    @if ($settings->avatarPath)
        <img src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="" class="w-7 h-7 md:w-8 md:h-8 rounded-full object-cover">
    @else
        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
    @endif
</button>
@endpush

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
        attach: @json(route('ai.photo.existing')),
        newConvo: @json(route('ai.conversation.new')),
        delConvo: (id) => @json(route('ai.conversation.delete')) + '?id=' + id,
        planPreview: @json(route('ai.plan.preview')),
    };
    const CAN_ASK = @json((bool) $settings->isUsable());
    const AVATAR = @json($settings->avatarPath ? \App\Support\MediaStore::url($settings->avatarPath) : null);
    const MY_INITIALS = @json(auth()->user()->initials);
    const UNLIMITED = @json((bool) $aiUnlimited);
    /* The bill, quoted before it is run up: the server's own pre-flight
       formula, mirrored, repriced on every keystroke and every chip. */
    @php
        // Precomputed: @json splits on commas (value, flags, depth) and an
        // inline array literal compiles to truncated, unparseable PHP.
        $aiPriceCard = ['inK' => (float) $settings->creditsPerInputK, 'outK' => (float) $settings->creditsPerOutputK, 'img' => (float) $settings->creditsPerImage, 'halfOut' => (int) $settings->maxOutputTokens / 2];
    @endphp
    const PRICE = @json($aiPriceCard);
    // The seasons this account may attach. The old dropdown carried them; the
    // picker sheet does now, and the send path reads the attached one.
    const PLANS = @json($schedules->map(fn ($s) => ['id' => (int) $s->id, 'title' => (string) $s->title])->values());
    /* The plan riding on this question, if the farmer attached one.
       tokens is measured by the server from the very text the question will
       carry, so the quote below is the same arithmetic the charge uses. */
    let attachedPlan = null;   // { id, title, activities, tokens }

    function sayEstimate() {
        const hint = byId('aiHint');
        if (!hint) return;
        const msg = (input?.value || '').trim();
        const shots = chips ? chips.children.length : 0;
        if (!msg && !shots && !attachedPlan) { hint.textContent = hint.dataset.idle || ''; return; }
        const tin = Math.ceil(msg.length / 4) + 900 + (attachedPlan ? attachedPlan.tokens : 0);
        const cost = Math.max(.01, Math.round((tin / 1000 * PRICE.inK + PRICE.halfOut / 1000 * PRICE.outK + shots * PRICE.img) * 100) / 100);
        hint.textContent = attachedPlan
            ? `≈ ${cost} credits — your plan is attached`
            : `≈ ${cost} credits for this question`;
    }

    const BOT_SVG = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>';

    let conversationId = @json($conversation->id ?? null);
    // Up to six photos ride on one question; each chip carries its stored
    // path once its upload lands, and send waits for the stragglers.
    const MAX_PHOTOS = 6;
    let uploadsBusy = 0;
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
    // `images` is a list of URLs: one renders naturally, two or more grid up.
    function addTurn(isMe, html, images, costLine, stamped) {
        byId('aiWelcome')?.remove();
        const shots = (images || []).filter(Boolean);
        const shotsHtml = shots.length
            ? `<div class="ai-shots${shots.length > 1 ? ' is-multi' : ''}">${shots.map((u) => `<img src="${escapeHtml(u)}" alt="Attached photo">`).join('')}</div>`
            : '';
        const el = document.createElement('div');
        el.className = 'aimsg is-new' + (isMe ? ' me' : '');
        el.innerHTML = `
            <span class="aimsg-face">${face(isMe)}</span>
            <div class="aibubble">
                ${html}
                ${shotsHtml}
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

    // The send button says what it is doing — and stays down while any
    // photo upload is still in flight.
    function updateSend() {
        const btn = byId('aiSendBtn');
        if (btn) btn.disabled = !CAN_ASK || busy || uploadsBusy > 0;
        const line = byId('aiAttachBusy');
        if (line) {
            line.classList.toggle('hidden', uploadsBusy === 0);
            line.querySelector('.tx').textContent = uploadsBusy > 1 ? `Attaching ${uploadsBusy} photos…` : 'Attaching photo…';
        }
    }
    function setSending(on) {
        const btn = byId('aiSendBtn');
        if (!btn) return;
        updateSend();
        const label = btn.querySelector('.ai-send-label');
        if (label) label.textContent = on ? 'Sending…' : 'Ask';
        btn.setAttribute('aria-label', on ? 'Sending' : 'Send');
    }

    // Textarea grows with the message, up to the CSS max-height.
    const input = byId('aiInput');
    input?.addEventListener('input', () => {
        input.style.height = 'auto';
        // The bar only shows once the box has stopped growing — while it still
        // fits, the height IS the scroll.
        input.style.overflowY = input.scrollHeight > 144 ? 'auto' : 'hidden';
        input.style.height = Math.min(input.scrollHeight, 144) + 'px';
        sayEstimate();
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

    /* ---- Photos: chips under the composer, one per attachment ---- */
    const chips = byId('aiPhotoChips');
    const CHIP_SPIN = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="animation:ai-spin .7s linear infinite"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.6" stroke-opacity=".3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>';

    function attachedPaths() {
        return [...chips.querySelectorAll('.ai-chip[data-path]')].map((c) => c.dataset.path);
    }
    function attachedUrls() {
        return [...chips.querySelectorAll('.ai-chip[data-path] img')].map((i) => i.src);
    }
    function attachedScheds() {
        return [...chips.querySelectorAll('.ai-chip[data-path]')].map((c) => c.dataset.sched ? parseInt(c.dataset.sched, 10) : null);
    }
    function roomForAnother() {
        if (chips.children.length < MAX_PHOTOS) return true;
        toast('Up to ' + MAX_PHOTOS + ' photos per question — remove one to add another.', 'error');
        return false;
    }
    function addChip(previewUrl) {
        const el = document.createElement('div');
        el.className = 'ai-chip is-busy';
        el.innerHTML = `<img src="${escapeHtml(previewUrl)}" alt="">`
            + `<span class="st">${CHIP_SPIN}</span>`
            + '<button type="button" class="x" aria-label="Remove photo">✕</button>';
        chips.appendChild(el);
        sayEstimate();
        return el;
    }
    function dropChip(el) {
        if (el._blob) { try { URL.revokeObjectURL(el._blob); } catch (e) {} }
        el.remove();
        sayEstimate();
    }
    function clearPhotos() {
        [...chips.children].forEach(dropChip);
    }
    chips?.addEventListener('click', (e) => {
        const x = e.target.closest('.ai-chip .x');
        if (x) dropChip(x.closest('.ai-chip'));
    });

    // Uploads run one call per file; the chip spins until its path lands.
    function uploadOne(file) {
        if (!file || !(file.type || '').startsWith('image/')) return;
        if (!roomForAnother()) return;
        const preview = URL.createObjectURL(file);
        const chip = addChip(preview);
        chip._blob = preview;
        uploadsBusy++; updateSend();
        const form = new FormData();
        form.append('image', file);
        api(URLS.photo, { method: 'POST', body: form })
            .then((res) => { chip.dataset.path = res.data.path; chip.classList.remove('is-busy'); })
            .catch((err) => { toast(err.message, 'error'); dropChip(chip); })
            .finally(() => { uploadsBusy--; updateSend(); });
    }
    byId('aiPhotoFiles')?.addEventListener('change', (e) => {
        [...(e.target.files || [])].forEach(uploadOne);
        e.target.value = '';
    });
    byId('aiPhotoCam')?.addEventListener('change', (e) => {
        [...(e.target.files || [])].forEach(uploadOne);
        e.target.value = '';
    });

    // A gallery pick is already hosted here — the server keeps its own copy.
    function attachFromGallery(item, gallerySid) {
        // A reference, not a copy: the server already hosts this picture and
        // the ask endpoint honours the picker's own list — so the chip lands
        // done, instantly. Copying at attach time (a download plus a
        // re-upload before a word was typed) was the slowness.
        if (!item || !item.url || !item.path || !roomForAnother()) return;
        const chip = addChip(item.url);
        chip.dataset.path = item.path;
        chip.dataset.sched = String(gallerySid || '');
        chip.classList.remove('is-busy');
        sayEstimate();
    }

    /* ---- Attaching the plan ----------------------------------------
     * One button, one question: shall this question carry your plan? With
     * several seasons it asks which; with one it asks whether. Either way the
     * answer is measured before it is agreed to, because it costs credits and
     * a farmer should never find that out afterwards. */
    function drawPlanChip() {
        const chip = byId('aiPlanChip');
        if (!chip) return;
        byId('aiPlanBtn')?.classList.toggle('is-on', !!attachedPlan);
        if (!attachedPlan) { chip.hidden = true; sayEstimate(); return; }
        byId('aiPlanName').textContent = attachedPlan.title;
        byId('aiPlanSub').textContent = attachedPlan.activities
            ? `${attachedPlan.activities} ${attachedPlan.activities === 1 ? 'activity' : 'activities'} — the AI reads this first`
            : 'the AI reads this first';
        chip.hidden = false;
        sayEstimate();
    }

    async function attachPlan(id, title) {
        const busy = byId('aiAttachBusy');
        if (busy) { busy.querySelector('.tx').textContent = 'Measuring your plan…'; busy.classList.remove('hidden'); }
        try {
            const res = await api(URLS.planPreview + '?scheduleId=' + encodeURIComponent(id), { method: 'GET' });
            const d = res.data || {};
            attachedPlan = { id: d.id, title: d.title || title, activities: d.activities || 0, tokens: d.tokens || 0 };
            drawPlanChip();
        } catch (err) {
            toast(err.message || 'That plan could not be attached.', 'error');
        } finally {
            if (busy) { busy.classList.add('hidden'); busy.querySelector('.tx').textContent = 'Attaching photo…'; }
        }
    }

    byId('aiPlanBtn')?.addEventListener('click', async () => {
        if (attachedPlan) {
            const off = await (window.confirmAction
                ? window.confirmAction({
                    title: 'Take the plan off this question?',
                    message: 'The answer will be about what you ask, without your season behind it.',
                    confirmText: 'Take it off',
                })
                : Promise.resolve(true));
            if (off) { attachedPlan = null; drawPlanChip(); }
            return;
        }
        if (!PLANS.length) { toast('You have no cropping plan to attach yet.', 'error'); return; }
        if (PLANS.length === 1) {
            const ok = await (window.confirmAction
                ? window.confirmAction({
                    title: 'Attach "' + PLANS[0].title + '"?',
                    message: 'The AI reads your plan — the work so far, day by day — before answering. It uses a few more credits, and the estimate below will say how many.',
                    confirmText: 'Attach it',
                })
                : Promise.resolve(true));
            if (ok) attachPlan(PLANS[0].id, PLANS[0].title);
            return;
        }
        // Several: ask which, in the house sheet the attach chooser uses.
        const list = byId('aiPlanList');
        list.innerHTML = PLANS.map((p) => `
            <button type="button" class="ai-attach-opt" data-plan="${p.id}">
                <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span>${escapeHtml(p.title)}<span class="sub">The AI reads this plan first</span></span>
            </button>`).join('');
        openSheet('aiPlanSheet');
    });
    byId('aiPlanList')?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-plan]');
        if (!b) return;
        window.closeSheet && window.closeSheet('aiPlanSheet');
        const id = parseInt(b.getAttribute('data-plan'), 10);
        attachPlan(id, (PLANS.find((p) => p.id === id) || {}).title || 'Plan');
    });
    byId('aiPlanX')?.addEventListener('click', () => { attachedPlan = null; drawPlanChip(); });

    /* ---- The attach chooser (house sheet). The picker now travels with
            this page, so the gallery door shows for anyone with a season to
            pick from. Which season: the chosen plan, or the only one there
            is; with several and none chosen, the door asks rather than
            guesses at whose photos to show. ---- */
    function galleryScheduleId() {
        // The attached plan is the season whose gallery is meant; failing
        // that, the only season there is.
        if (attachedPlan) return attachedPlan.id;
        return PLANS.length === 1 ? PLANS[0].id : 0;
    }
    const hasSchedules = () => PLANS.length > 0;
    const canGallery = () => typeof window.smPickMedia === 'function' && hasSchedules();
    byId('aiAttachBtn')?.addEventListener('click', () => {
        byId('aiAttachGallery')?.classList.toggle('hidden', !canGallery());
        openSheet('aiAttachSheet');
    });
    byId('aiAttachUpload')?.addEventListener('click', () => {
        window.closeSheet && window.closeSheet('aiAttachSheet');
        byId('aiPhotoFiles')?.click();
    });
    byId('aiAttachCamera')?.addEventListener('click', () => {
        window.closeSheet && window.closeSheet('aiAttachSheet');
        byId('aiPhotoCam')?.click();
    });
    byId('aiAttachGallery')?.addEventListener('click', () => {
        window.closeSheet && window.closeSheet('aiAttachSheet');
        if (!canGallery()) return;
        const sid = galleryScheduleId();
        if (!sid) {
            toast('Attach the plan first — its gallery is what opens.', 'error');
            byId('aiPlanBtn')?.focus();
            return;
        }
        window.smPickMedia({
            scheduleId: sid,
            kinds: 'image',
            title: 'Attach from the gallery',
            // Several at once - the question can carry what room remains.
            multiple: true,
            max: MAX_PHOTOS - chips.children.length,
            onPick: (item) => attachFromGallery(item, sid),
        });
    });

    /* ---- Ask ---- */
    async function send() {
        if (busy) return;
        if (uploadsBusy > 0) { toast('Wait a moment — a photo is still uploading.', 'error'); return; }
        const message = input.value.trim();
        if (!message) { toast('Type a question first.', 'error'); return; }

        busy = true;
        setSending(true);
        const myPaths = attachedPaths();
        const myScheds = attachedScheds();
        addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', attachedUrls(), null, true);
        input.value = '';
        sayEstimate();
        input.style.height = 'auto';

        const thinking = addTurn(false, '<span class="aidots"><i></i><i></i><i></i></span>');

        try {
            const res = await api(URLS.ask, {
                method: 'POST',
                body: {
                    message,
                    conversationId,
                    imagePaths: myPaths,
                    imageScheduleIds: myScheds,
                    // Sent only when the farmer attached it; the flag is what
                    // turns a label into the plan being read.
                    scheduleId: attachedPlan ? attachedPlan.id : null,
                    attachPlan: attachedPlan ? 1 : 0,
                },
            });
            conversationId = res.data.conversationId;
            // The chips leave the moment the send is known good - before any
            // templating that could throw and strand them in the composer.
            clearPhotos();
            const costLine = UNLIMITED ? '' : `<p class="aibubble-cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
            thinking.querySelector('.aibubble').innerHTML =
                renderAnswer(res.data.answer.content)
                + costLine
                + `<time class="ai-when">${escapeHtml(nowStamp())}</time>`;
            setBalance(res.data.balance);
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
            // Kept on purpose - a retry should not re-pick its photos. Said
            // out loud, so a failed send never reads as "sent but not cleared".
            if (chips.children.length) toast('Your photos are still attached, ready for the retry.');
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

    /* The square button beside the bell — the masthead's jobs, one sheet. */
    byId('aiMenuBtn')?.addEventListener('click', () => openSheet('aiMenuSheet'));
    byId('aiMenuNew')?.addEventListener('click', () => window.closeSheet?.('aiMenuSheet'));
    byId('aiMenuHistory')?.addEventListener('click', () => { window.closeSheet?.('aiMenuSheet'); openSheet('aiHistorySheet'); });
    byId('aiMenuLink')?.addEventListener('click', () => {
        window.closeSheet?.('aiMenuSheet');
        // This page ties a chat to a plan through the composer's selector —
        // walk the hand there rather than grow a second control for it.
        toast('Pick the plan below — this chat ties itself to it.');
        byId('aiPlanBtn')?.focus();
    });

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
            const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: attachedPlan ? attachedPlan.id : null } });
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
            // Accounts that ride free never hear about credits.
            detail: UNLIMITED ? '' : 'Credits already spent are not refunded.',
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
