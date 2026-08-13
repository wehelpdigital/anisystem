@extends('layouts.app')

@section('title', 'Activities — ' . $schedule->title)
@section('page-title', 'Activities')
@section('page-subtitle', $schedule->title)
@section('help-key', 'activities')
@section('back', route('sm.hub', ['id' => $schedule->id]))

{{-- The activity board is the work surface: on a phone the bottom tab bar eats
     a strip of it for navigation that Back already provides. --}}
@section('body-class', 'hide-tabbar no-zoom is-activities' . (request('module', 'activities') === 'activities' ? ' act-module-open' : ' module-booting'))

@if (request()->boolean('embed'))
@push('head')
<script>document.documentElement.classList.add('collab-embed');</script>
<style>
    /* Collab Room embed (?embed=1): show only the activities board. Hide the app
       chrome (header/tabbar/footer), module switching, quick share, the Collab
       button and the floating widgets — keep add/move, versions, notes, markers. */
    html.collab-embed header.sticky, html.collab-embed .tabbar, html.collab-embed footer { display: none !important; }
    html.collab-embed main { padding: .6rem .75rem 1rem !important; max-width: none !important; }
    /* The sticky toolbar bleeds full-width via -mx-4/-mx-6 in the normal app; that
       bleed (up to 24px each side) exceeds the embed's smaller padding and causes a
       horizontal scrollbar. Neutralize it so the toolbar fits the embed width. */
    html.collab-embed .sticky.top-14 { top: 0 !important; margin-left: 0 !important; margin-right: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    html.collab-embed, html.collab-embed body { overflow-x: clip; }
    html.collab-embed #modulesBtn,
    html.collab-embed #collabRoomBtn,
    html.collab-embed #quickShareBtn,
    html.collab-embed #aiFloat,
    html.collab-embed #teamChat,
    html.collab-embed #scheduleBoard,
    html.collab-embed .activity-action-row[data-forward="quickShareBtn"],
    html.collab-embed .activity-action-row[data-forward="openReportBtn"] { display: none !important; }
</style>
<script>
    // Tell the Collab Room parent once the board has actually painted, so it can
    // hide its loader at the right moment (the iframe 'load' event fires before
    // this heavy page finishes its init + first paint).
    (() => {
        const signal = () => requestAnimationFrame(() => requestAnimationFrame(() => {
            try {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'collab:activities-ready' }, window.location.origin);
                }
            } catch (_) { /* cross-origin — ignore */ }
        }));
        // setTimeout(0) after DOMContentLoaded runs after the board's own init handler.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => setTimeout(signal, 0), { once: true });
        } else {
            setTimeout(signal, 0);
        }
    })();
</script>
@endpush
@endif

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <style>
        /* ---- Timeline surfaces ----------------------------------------------
           The timeline paints with literal colours rather than Tailwind
           utilities, so night mode needs its own set of tokens here. */
        :root {
            --tl-surface: #fff;
            --tl-surface-2: #f3f4f6;
            --tl-border: #f3f4f6;
            --tl-border-soft: #eef0f3;
            --tl-text: #111827;
            --tl-text-soft: #374151;
            --tl-text-muted: #4b5563;
            --tl-text-faint: #6b7280;
            --tl-header-tint: 13%;
            --tl-pill: rgba(255, 255, 255, .8);
            --tl-hover: rgba(255, 255, 255, .75);
            --tl-note-bg: #fffbeb;
            --tl-note-border: #fde68a;
            --tl-note-text: #78350f;
            --tl-rest-bg: #fafafa;
            --tl-rest-border: #f3f4f6;
        }
        html.dark {
            --tl-surface: #191d23;
            --tl-surface-2: #262c34;
            --tl-border: #2c323b;
            --tl-border-soft: #2c323b;
            --tl-text: #f2f5f8;
            --tl-text-soft: #ccd4dd;
            --tl-text-muted: #b4bdc8;
            --tl-text-faint: #98a2ae;
            --tl-header-tint: 18%;
            --tl-pill: rgba(0, 0, 0, .3);
            --tl-hover: rgba(255, 255, 255, .1);
            --tl-note-bg: #2c2410;
            --tl-note-border: #4a3d16;
            --tl-note-text: #f2cd76;
            --tl-rest-bg: #14171c;
            --tl-rest-border: #23282f;
        }

        /* ---- Timeline date-group color cycle (8 flat colors, mother parity) ---- */
        .date-color-0 { --date-color: #4A90E2; }
        .date-color-1 { --date-color: #50C878; }
        .date-color-2 { --date-color: #F39C12; }
        .date-color-3 { --date-color: #9B59B6; }
        .date-color-4 { --date-color: #1ABC9C; }
        .date-color-5 { --date-color: #E74C3C; }
        .date-color-6 { --date-color: #5C6BC0; }
        .date-color-7 { --date-color: #16A085; }
        /* Night mode needs lighter cycle colours: these are used as *text* on a
           dark tinted header, where the saturated indigo/purple go unreadable. */
        html.dark .date-color-0 { --date-color: #7FB3EF; }
        html.dark .date-color-1 { --date-color: #6ED694; }
        html.dark .date-color-2 { --date-color: #F5B450; }
        html.dark .date-color-3 { --date-color: #C48AD8; }
        html.dark .date-color-4 { --date-color: #4FD6BC; }
        html.dark .date-color-5 { --date-color: #F5837A; }
        html.dark .date-color-6 { --date-color: #8E9AE8; }
        html.dark .date-color-7 { --date-color: #4FD0B4; }

        .date-group {
            background: var(--tl-surface); border: 1px solid var(--tl-border); border-left: 4px solid var(--date-color, #4A90E2);
            border-radius: 1rem; box-shadow: var(--shadow-card); margin-bottom: .9rem;
        }
        .date-header {
            display: flex; align-items: center; gap: .4rem; flex-wrap: wrap;
            padding: .55rem .8rem; border-radius: calc(1rem - 2px) calc(1rem - 2px) 0 0;
            background: color-mix(in srgb, var(--date-color, #4A90E2) var(--tl-header-tint), var(--tl-surface));
        }
        .date-header-day { font-weight: 800; font-size: .8rem; color: var(--date-color); text-transform: uppercase; }
        .date-header-date { font-weight: 800; font-size: 1rem; color: var(--tl-text); }
        .date-header-range { display: inline-flex; align-items: center; gap: .2rem; font-size: 11px; font-weight: 600; color: var(--tl-text-soft); background: var(--tl-hover); border-radius: 999px; padding: .1rem .5rem; }
        /* What the day costs, next to what the sky is doing: two facts about
           the day, on one line. Placement is done in paintDayCash(), which
           knows where the forecast ended up. */
        /* Both are facts about the day rather than controls, so they drop to a
           line of their own under the date and its buttons. The break is a
           zero-height item that fills the row: with a wrapping flex header,
           that is what forces the two after it onto the next line. */
        .dh-rowbreak { order: 90; flex-basis: 100%; height: 0; margin: 0; }
        .date-header-weather, .wx-mini-btn { order: 91; }
        .date-header-cash { order: 92; display: inline-flex; align-items: center; gap: .28rem;
            font-size: 11px; font-weight: 800; color: var(--color-amber-800, #92400e);
            background: var(--color-amber-50, #fffbeb); border: 1px solid var(--color-amber-200, #fde68a);
            border-radius: 999px; padding: .14rem .5rem .14rem .42rem; flex-shrink: 0; }
        .date-header-cash svg { width: .85rem; height: .85rem; }
        .date-header-cash { cursor: pointer; }
        .date-header-cash:hover { filter: brightness(.97); }
        /* Payroll wears amber, the colour the money already uses on this board,
           so a wage day is not mistaken for a field task at a glance. */
        .payroll-badge { display: inline-flex; align-items: center; gap: .25rem;
            background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        html.dark .payroll-badge { background: rgb(120 53 15 / .35); color: #fcd34d; border-color: rgb(180 83 9 / .5); }

        .act-check-total { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem;
            margin-top: .25rem; padding-top: .35rem; border-top: 1px dashed var(--tl-border, rgb(0 0 0 / .12));
            font-size: .74rem; font-weight: 800; color: var(--tl-text, var(--color-gray-800)); }
        .type-ico-payroll { background: #fef3c7; color: #92400e; }
        .type-ico-reminder { background: #ede9fe; color: #6d28d9; }
        html.dark .type-ico-reminder { background: rgb(109 40 217 / .3); color: #c4b5fd; }
        html.dark .type-ico-payroll { background: rgb(120 53 15 / .35); color: #fcd34d; }

        /* The roster on a payroll card. Compact, because it sits inside a card
           that already carries a title and a date. */
        .act-check { display: flex; flex-direction: column; gap: .1rem; margin-top: .5rem; padding: .35rem .5rem;
            border-radius: .6rem; background: var(--tl-hover, rgb(0 0 0 / .03)); }
        .act-check-row { display: flex; align-items: center; gap: .5rem; padding: .22rem 0; cursor: pointer; }
        .act-check-row input { width: 1.05rem; height: 1.05rem; border-radius: .3rem; flex-shrink: 0; }
        .act-check-name { min-width: 0; flex: 1 1 auto; font-size: .78rem; font-weight: 700;
            color: var(--tl-text, var(--color-gray-800)); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .act-check-pay { font-size: .74rem; font-weight: 800; color: var(--tl-text-soft, var(--color-gray-600)); white-space: nowrap; }
        .act-check-row.is-out .act-check-name,
        .act-check-row.is-out .act-check-pay { opacity: .45; text-decoration: line-through; }

        /* Attendance: a tick each, and what the day comes to once the people
           who did not turn up are taken out. */
        .att-head { display: flex; flex-direction: column; gap: .1rem; padding: .85rem .95rem; border-radius: .9rem;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; }
        .att-head-label { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #15803d; }
        .att-head-amt { font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; color: #14532d; line-height: 1.2; }
        .att-head-sub { font-size: .72rem; color: #166534; opacity: .8; }
        .att-row { display: flex; align-items: center; gap: .6rem; padding: .55rem .1rem; border-bottom: 1px solid var(--color-gray-100); }
        .att-row:last-child { border-bottom: 0; }
        .att-row input { width: 1.2rem; height: 1.2rem; border-radius: .35rem; flex-shrink: 0; }
        .att-name { min-width: 0; flex: 1 1 auto; font-size: .88rem; font-weight: 700; color: var(--color-gray-800); }
        .att-jobs { display: block; font-size: .68rem; font-weight: 600; color: var(--color-gray-400); }
        .att-pay { font-size: .85rem; font-weight: 800; color: var(--color-gray-900); white-space: nowrap; }
        .att-row.is-out .att-name, .att-row.is-out .att-pay { color: var(--color-gray-400); text-decoration: line-through; }
        .att-row.is-out .att-jobs { text-decoration: none; }
        .att-empty { padding: 1.5rem .5rem; text-align: center; font-size: .82rem; color: var(--color-gray-400); }
        html.dark .att-head { background: linear-gradient(135deg, #16220f 0%, #1d2f16 100%); border-color: #2f4a1e; }
        html.dark .att-head-amt { color: #bbf7d0; }
        html.dark .att-name, html.dark .att-pay { color: #e6eddd; }
        html.dark .att-row { border-color: #223018; }

        /* The breakdown behind the day's figure. Money reads better grouped
           than listed: the total leads, then wages and extras as two blocks
           you can weigh against each other, each in its own colour so a glance
           is enough to tell which is which. */
        .dc-hero { border-radius: 1rem; padding: 1rem 1.1rem 1.1rem; margin-bottom: .9rem;
            display: flex; flex-direction: column; gap: .1rem;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; }
        .dc-hero-label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #b45309; }
        .dc-hero-amt { font-family: var(--font-heading); font-size: 1.9rem; line-height: 1.15; font-weight: 800; color: #78350f; }
        .dc-hero-sub { font-size: .74rem; color: #92400e; opacity: .85; }
        /* How the day divides — the same two colours the sections wear, so it
           needs no legend. */
        .dc-split { display: block; height: .35rem; border-radius: 999px; margin-top: .7rem; overflow: hidden; background: #f59e0b; }
        .dc-split-wages { display: block; height: 100%; background: #15803d; }

        .dc-sec { border: 1px solid var(--color-gray-200); border-radius: .85rem; overflow: hidden;
            margin-bottom: .7rem; background: var(--color-white); }
        .dc-sec-head { display: flex; align-items: center; gap: .5rem; padding: .5rem .7rem; }
        .dc-sec-ico { display: inline-flex; align-items: center; justify-content: center; width: 1.6rem; height: 1.6rem;
            border-radius: .5rem; flex-shrink: 0; }
        .dc-sec-ico svg { width: .95rem; height: .95rem; }
        .dc-sec-title { font-size: .74rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .dc-sec-sum { margin-left: auto; font-size: .88rem; font-weight: 800; }
        .dc-sec-body { padding: .1rem .7rem .55rem; }
        .dc-sec-wages .dc-sec-head { background: #f0fdf4; }
        .dc-sec-wages .dc-sec-ico { background: #dcfce7; color: #15803d; }
        .dc-sec-wages .dc-sec-title, .dc-sec-wages .dc-sec-sum { color: #14532d; }
        .dc-sec-extra .dc-sec-head { background: #fffbeb; }
        .dc-sec-extra .dc-sec-ico { background: #fef3c7; color: #b45309; }
        .dc-sec-extra .dc-sec-title, .dc-sec-extra .dc-sec-sum { color: #78350f; }

        .dc-row { display: flex; align-items: baseline; gap: .55rem; padding: .5rem 0; }
        .dc-row + .dc-row { border-top: 1px solid var(--color-gray-100); }
        .dc-dot { width: .4rem; height: .4rem; border-radius: 999px; flex-shrink: 0; transform: translateY(-.15rem); }
        .dc-dot-wages { background: #22c55e; }
        .dc-dot-extra { background: #f59e0b; }
        .dc-name { min-width: 0; flex: 1 1 auto; font-size: .85rem; font-weight: 700; color: var(--color-gray-800); }
        .dc-detail { display: block; font-size: .7rem; font-weight: 600; color: var(--color-gray-400); margin-top: .1rem; }
        .dc-amt { font-size: .85rem; font-weight: 800; color: var(--color-gray-900); white-space: nowrap; }
        .dc-foot { margin-top: .5rem; font-size: .72rem; line-height: 1.5; color: var(--color-gray-400); }

        html.dark .dc-hero { background: linear-gradient(135deg, #292014 0%, #3b2c14 100%); border-color: #6b4b12; }
        html.dark .dc-hero-label { color: #fbbf24; }
        html.dark .dc-hero-amt { color: #fde68a; }
        html.dark .dc-hero-sub { color: #fcd34d; }
        html.dark .dc-sec { background: #141a10; border-color: #2b3a1c; }
        html.dark .dc-sec-wages .dc-sec-head { background: #16220f; }
        html.dark .dc-sec-wages .dc-sec-title, html.dark .dc-sec-wages .dc-sec-sum { color: #bbf7d0; }
        html.dark .dc-sec-extra .dc-sec-head { background: #241b0c; }
        html.dark .dc-sec-extra .dc-sec-title, html.dark .dc-sec-extra .dc-sec-sum { color: #fde68a; }
        html.dark .dc-name { color: #e6eddd; }
        html.dark .dc-amt { color: #f3f7ee; }
        html.dark .dc-row + .dc-row { border-color: #223018; }
        .date-header-cash[hidden] { display: none; }
        /* The growth-stage pill sits just before the money one. */
        .date-header-stage { order: 91; display: inline-flex; align-items: center; gap: .28rem;
            padding: .12rem .45rem; border-radius: 999px; font-size: .68rem; font-weight: 800;
            background: #e4efd4; color: #3d6823; border: 1px solid #c9e0ad; cursor: pointer; }
        .date-header-stage[hidden] { display: none; }
        .date-header-stage .dhs-emoji { font-size: .8rem; line-height: 1; }
        .date-header-stage:hover { filter: brightness(.97); }
        html.dark .date-header-stage { background: rgb(61 104 35 / .35); color: #a8cc7e; border-color: rgb(61 104 35 / .6); }

        /* The sheet: one card per lot, its stage, and the whole run of stages
           with where today falls in it. */
        .gs-lot { border: 1px solid var(--color-gray-200); border-radius: .9rem; overflow: hidden; margin-bottom: .75rem; }
        .gs-head { display: flex; align-items: center; gap: .6rem; padding: .7rem .8rem;
            background: linear-gradient(135deg, #f3f8ec, #e4efd4); }
        html.dark .gs-head { background: linear-gradient(135deg, #1c2416, #24301a); }
        .gs-emoji { font-size: 1.5rem; line-height: 1; }
        .gs-lotname { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); }
        html.dark .gs-lotname { color: #e5e9f5; }
        .gs-day { font-size: .7rem; font-weight: 700; color: #3d6823; }
        .gs-body { padding: .75rem .8rem; }
        .gs-now { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
        html.dark .gs-now { color: #e5e9f5; }
        .gs-what { font-size: .84rem; color: var(--tl-text-muted, #4b5563); margin-top: .2rem; line-height: 1.45; }
        .gs-needs { margin-top: .5rem; padding: .5rem .6rem; border-radius: .6rem; background: #fff7ed;
            border: 1px solid #fed7aa; font-size: .8rem; color: #9a3412; line-height: 1.4; }
        html.dark .gs-needs { background: rgb(154 52 18 / .18); border-color: rgb(154 52 18 / .5); color: #fdba74; }
        .gs-needs b { display: block; font-size: .64rem; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .15rem; }
        .gs-bar { height: .4rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .6rem; }
        .gs-bar span { display: block; height: 100%; border-radius: 999px; background: #4a7c2a; }
        .gs-next { font-size: .72rem; color: var(--tl-text-muted, #6b7280); margin-top: .35rem; }
        .gs-steps { margin-top: .7rem; border-top: 1px dashed var(--color-gray-200); padding-top: .6rem; display: grid; gap: .3rem; }
        .gs-step { display: flex; align-items: flex-start; gap: .5rem; font-size: .78rem; color: var(--tl-text-muted, #6b7280); }
        .gs-dot { flex: 0 0 auto; width: .6rem; height: .6rem; border-radius: 999px; margin-top: .35rem;
            background: var(--color-gray-300); }
        .gs-step.is-past .gs-dot { background: #a8cc7e; }
        .gs-step.is-now { color: var(--color-gray-900); font-weight: 700; }
        html.dark .gs-step.is-now { color: #e5e9f5; }
        .gs-step.is-now .gs-dot { background: #4a7c2a; box-shadow: 0 0 0 3px rgb(74 124 42 / .2); }
        .gs-when { flex: 0 0 auto; font-variant-numeric: tabular-nums; opacity: .7; }
        .gs-none { text-align: center; color: var(--color-gray-400); font-size: .82rem; padding: 1.5rem .5rem; }
        .gs-foot { font-size: .76rem; line-height: 1.5; color: #92400e; margin-top: .5rem;
            padding: .65rem .75rem; border-radius: .7rem; background: #fffbeb; border: 1px solid #fde68a; }
        html.dark .gs-foot { background: rgb(180 83 9 / .16); border-color: rgb(180 83 9 / .45); color: #fcd34d; }
        html.dark .date-header-cash { color: #fcd34d; background: rgb(120 53 15 / .35); border-color: rgb(180 83 9 / .5); }
        .date-header-count { font-size: 11px; font-weight: 700; color: var(--date-color); background: var(--tl-pill); border-radius: 999px; padding: .12rem .55rem; margin-left: auto; flex-shrink: 0; }
        /* Per-day weather chips in the date header — scroll/drag if they overflow. */
        /* Shares the second line with the cost rather than filling it: the
           strip scrolls its chips internally, so giving up the space it does
           not need costs nothing. `flex-basis: 0` is what makes it yield —
           auto would have it claim its content width first and push the cost
           down to a third line. */
        .date-header-weather { min-width: 0; flex: 1 1 auto; }
        .date-header-cash { flex: 0 0 auto; }
        .date-header-weather.scroll-chips { gap: .25rem; padding: 0; margin: 0; }
        .wx-chip { display: inline-flex; align-items: center; gap: .22rem; flex-shrink: 0; font-size: 10.5px; font-weight: 700; padding: .1rem .42rem; border-radius: 999px; background: var(--tl-pill); color: var(--tl-text-muted); white-space: nowrap; cursor: pointer; border: 1px solid transparent; transition: border-color .15s ease; }
        .wx-chip:hover { border-color: var(--date-color, #4A90E2); }
        .wx-chip .wx-emoji { font-size: 12px; line-height: 1; }
        .wx-chip .wx-loc { color: var(--tl-text-soft); max-width: 6rem; overflow: hidden; text-overflow: ellipsis; }
        .wx-chip .wx-temp { color: var(--tl-text); font-variant-numeric: tabular-nums; }
        .rest-day-weather { margin-top: .35rem; max-width: 100%; }
        .wx-cloud { display: inline-block; animation: wxCloudFloat 1.6s ease-in-out infinite; }
        @keyframes wxCloudFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
        @media (prefers-reduced-motion: reduce) { .wx-cloud { animation: none; } }
        /* Per-day agronomic reminder pill in the date header. Quiet/grey once
           every reminder is acknowledged; amber + pulsing while any is unread. */
        .day-warn-btn { display: inline-flex; align-items: center; gap: .2rem; flex-shrink: 0; height: 1.5rem; padding: 0 .5rem; border-radius: 999px; background: var(--tl-pill); color: var(--tl-text-muted); border: 1px solid transparent; font-size: 11px; font-weight: 800; cursor: pointer; transition: transform .15s ease, background .15s ease; }
        .day-warn-btn:hover { transform: translateY(-1px); }
        .day-warn-btn svg { width: .95rem; height: .95rem; }
        .day-warn-btn .cnt { font-variant-numeric: tabular-nums; }
        .day-warn-btn.has-unread { background: #fef3c7; color: #b45309; border-color: #fcd34d; animation: warnPulse 2.4s ease-in-out infinite; }
        .day-warn-btn.has-unread:hover { background: #fde68a; }
        @keyframes warnPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); } 50% { box-shadow: 0 0 0 3px rgba(245,158,11,.20); } }
        @media (prefers-reduced-motion: reduce) { .day-warn-btn.has-unread { animation: none; } }
        html.dark .day-warn-btn.has-unread { background: #3a2c07; color: #fbbf24; border-color: #6b4e0e; }
        html.dark .day-warn-btn.has-unread:hover { background: #4a3a0a; }
        /* Reminder cards inside the day-warning sheet. */
        .warn-item { display: flex; gap: .7rem; padding: .8rem .85rem; border-radius: .85rem; border: 1px solid #fde68a; background: #fffbeb; transition: opacity .2s ease; }
        html.dark .warn-item { border-color: #6b4e0e; background: #241b06; }
        .warn-item.is-read { opacity: .55; }
        .warn-item.is-read .warn-item-title { text-decoration: line-through; }
        .warn-item-ico { font-size: 1.3rem; line-height: 1.1; flex-shrink: 0; }
        .warn-item-title { font-weight: 800; color: #92400e; font-size: .9rem; }
        html.dark .warn-item-title { color: #fcd34d; }
        .warn-item-lots { display: flex; flex-wrap: wrap; gap: .3rem; margin: .35rem 0; }
        .warn-item-lots .lot { font-size: 10.5px; font-weight: 700; color: #fff; padding: .06rem .5rem; border-radius: 999px; }
        .warn-item-detail { font-size: .8rem; color: #7c6f57; line-height: 1.5; }
        html.dark .warn-item-detail { color: #c3b79f; }
        .warn-read-check { margin-top: .6rem; display: inline-flex; align-items: center; gap: .45rem; font-size: 11.5px; font-weight: 800; color: #a16207; cursor: pointer; user-select: none; }
        .warn-read-check input { width: 1rem; height: 1rem; border-radius: .25rem; accent-color: #d97706; }
        html.dark .warn-read-check { color: #fcd34d; }
        .date-header-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.6rem; height: 2.6rem; border-radius: .6rem; color: var(--tl-text-muted); flex-shrink: 0;
        }
        @media (min-width: 768px) {
            .date-header-btn { width: 2.25rem; height: 2.25rem; }
        }
        .date-header-btn:hover { background: var(--tl-hover); color: var(--tl-text); }
        .date-note-btn.has-note, .date-marker-btn.has-marker { color: #b45309; }
        html.dark .date-note-btn.has-note, html.dark .date-marker-btn.has-marker { color: #eec155; }
        .date-header-delete-btn:hover { color: #dc2626; background: var(--tl-hover); }
        html.dark .date-header-delete-btn:hover { color: #f47c7c; }

        /* Icon buttons swap to this mini spinner while their fetch is in flight. */
        .btn-spin { animation: btnspin .7s linear infinite; }
        @keyframes btnspin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .btn-spin { animation-duration: 1.4s; } }

        /* Accordion: a day folds down to its header; the chevron flags state.
           The body is a 1fr→0fr grid row so height animates without knowing
           the content size (old browsers simply snap — still correct). */
        .date-chevron { width: 1rem; height: 1rem; flex-shrink: 0; color: var(--date-color); transition: transform .18s ease; }
        .date-group:not(.is-folded) .date-chevron { transform: rotate(90deg); }
        .date-body { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
        .date-body-inner { overflow: hidden; min-height: 0; }
        .date-group.is-folded .date-body { grid-template-rows: 0fr; }
        /* Restoring the remembered open days on load applies instantly. */
        #activitiesList.no-fold-anim .date-body,
        #activitiesList.no-fold-anim .date-chevron { transition: none; }
        @media (prefers-reduced-motion: reduce) { .date-body, .date-chevron { transition: none; } }

        .date-activities { display: flex; flex-direction: column; gap: .55rem; padding: .7rem; }
        .date-activities.drag-over { outline: 2px dashed #86b556; outline-offset: -4px; border-radius: .8rem; background: color-mix(in srgb, #86b556 12%, var(--tl-surface)); }

        /* A note pinned to a day says what it is before it says anything
           else. */
        .inline-note-title { font-size: .82rem; font-weight: 800; color: var(--color-gray-900);
            line-height: 1.3; margin-bottom: .15rem; }
        html.dark .inline-note-title { color: #e8efe1; }
        .inline-note .note-atts { margin-top: .4rem; }

        .date-note-block {
            margin: .55rem .7rem 0; background: var(--tl-note-bg); border: 1px solid var(--tl-note-border); border-radius: .6rem;
            padding: .5rem .7rem; font-size: .8rem; color: var(--tl-note-text); white-space: pre-wrap;
            cursor: pointer; position: relative; transition: box-shadow .15s ease, border-color .15s ease;
        }
        .date-note-block:hover { border-color: #f5c518; box-shadow: 0 1px 6px rgb(0 0 0 / .06); }
        /* Edit + delete buttons — reveal on hover (desktop), always shown on
           touch. Delete = red trash, edit = green pencil, on a white chip. */
        /* Clears the edit button's far edge (right 2.6rem + 1.9rem wide) plus a
           gap. Unconditional on purpose: the buttons are absolutely positioned
           over this text, and tying the reservation to a media query means the
           two drift apart the moment either is touched. */
        .date-note-inner { padding-right: 4.9rem; }
        .date-note-edit, .date-note-del {
            position: absolute; top: .3rem; width: 1.9rem; height: 1.9rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
            background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .2); opacity: 0;
            transition: opacity .15s ease, background .15s ease, transform .1s ease;
        }
        .date-note-del { right: .35rem; color: #dc2626; }
        .date-note-edit { right: 2.6rem; color: var(--color-brand-700); }
        .date-note-edit svg, .date-note-del svg { width: 1.1rem; height: 1.1rem; }
        .date-note-block:hover .date-note-edit, .date-note-block:hover .date-note-del { opacity: 1; }
        .date-note-del:hover { background: #fee2e2; }
        .date-note-edit:hover { background: #eef7e8; }
        .date-note-del:active, .date-note-edit:active { transform: scale(.9); }

        /* Desktop keeps the full date, the word "activities" and the arrow
           badge; the folded range is a phone-only spelling. */
        .dh-short, .dh-rangeshort { display: none; }

        /* The kebab sheet names the activity it is about to act on, so the name
           has to be readable — two lines before it gives up, not one clipped. */
        .card-menu-title {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; line-height: 1.3;
        }

        /* The details sheet is where the trimmed text is meant to be readable,
           so undo the card's clamping inside it — these override the one-line
           rules because they are later and carry the extra class. */
        .activity-info-body .activity-card {
            border: 0; box-shadow: none; padding: 0; background: transparent;
        }
        /* The sheet is a read view — the clone drops the buttons, so drop the
           fold chevron and drag grip with them (they promise actions the
           sheet does not offer, and the grip has no padding gutter here). */
        .activity-info-body .activity-card::before,
        .activity-info-body .activity-card::after { display: none; }
        /* Three class selectors on purpose. The card's one-line rules are
           `.activity-card .activity-card-title` — the same weight as a
           two-class override — and they sit later in this file, so an equal
           match would lose on source order and the sheet built to show the
           full text would keep hiding it. */
        .activity-info-body .activity-card .activity-card-title {
            display: block; white-space: normal; overflow: visible;
            text-overflow: clip; -webkit-line-clamp: unset; font-size: 1.05rem;
        }
        .activity-info-body .activity-card .activity-description-content {
            display: block; -webkit-line-clamp: unset; overflow: visible;
            max-height: none;
        }
        .activity-info-body .activity-card .activity-card-lothead {
            /* The sheet has no kebab to make room for, so the card's cap on
               this box would only squeeze the tags it exists to show. */
            overflow: visible; flex-wrap: wrap; max-width: none;
        }
        .activity-info-body .activity-card .activity-card-badges { flex-wrap: wrap; }

        /* Notes read in full inside the sheet — three classes again, to beat
           the one-line rules below on specificity rather than source order. */
        .activity-info-body .inline-note .inline-note-body,
        .activity-info-body .date-note-block .date-note-inner {
            display: block; white-space: pre-wrap; overflow: visible;
            text-overflow: clip; -webkit-line-clamp: unset; max-height: none;
            padding-right: 0;
        }
        .activity-info-body .inline-note,
        .activity-info-body .date-note-block {
            padding-right: .7rem; cursor: default; border-style: solid;
        }

        /* ---- Mobile: day header, notes and activity cards ----
           Everything below is phone-only; the desktop layout is untouched. */
        @media (max-width: 767px) {
            /* "Jun 17, 2026" -> "Jun 17, 26" and "2 activities" -> "2", so the
               date, the count and the kebab all fit on one line. */
            .dh-long, .dh-word, .dh-modprefix { display: none; }
            .dh-short { display: inline; }

            /* A multi-day group reads as one range instead of a start date plus
               an arrow badge repeating it — same information, about half the
               width, and the kebab keeps its place on the line. */
            .date-header-date.has-range .dh-short { display: none; }
            .date-header-date.has-range .dh-rangeshort { display: inline; white-space: nowrap; }
            .date-header-range { display: none; }

            /* The note text reserved 2.2rem on the right, but the edit button
               starts at 2.6rem — so both buttons sat on top of the words. Clear
               the pair properly, and keep them visible: there is no hover on a
               phone, so a reveal-on-hover control is one you can never see. */
            .date-note-edit, .date-note-del { opacity: 1; }

            /* The header is wrap-enabled for desktop, where there is room for
               the weather chips. On a phone that wrap is what threw the + and
               the kebab onto a second line: keep one row and let the chips —
               the only thing here that can afford to lose width — give way. */
            /* Keep the date, count, + and kebab together on the first line, and
               drop the weather onto a full-width line of its own beneath them.
               Squeezing it into the same row is what clipped the forecast: the
               chips are a scrolling strip, so denying them width hides days
               rather than shrinking them. Order puts them last whatever their
               position in the markup. */
            .date-header { flex-wrap: wrap; }
            .date-header > * { flex-shrink: 0; }
            /* The one thing on the control line that can give way is the date
               text — everything else is a button. In a wrapping flex container
               items hop to the next line INSTEAD of shrinking, so the kebab
               wrapped before the date was ever squeezed. A hand-measured
               reserve used to hold the room for it, but it only fitted the
               buttons of the day it was measured on: a two-digit activity
               count, a warnings pill or a range badge each ate into it until
               the kebab dropped a line again. `flex-basis: 0` retires the
               guess — the date claims no width of its own, so it can never
               push anything off the line, and simply grows into whatever the
               buttons leave, ellipsizing when that is little. */
            .date-header-date {
                flex: 1 1 0; min-width: 0;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            /* One and two digits cost the same, so the line does not reflow
               when a day ticks over from 9 to 10 activities. */
            .date-header-count { min-width: 1.9rem; text-align: center; }
            /* Its own line, but not the whole of it — the day's cost sits at
               the end of the same row. `1 1 0` yields the space the chips do
               not need; `1 0 100%` claimed the lot and pushed the cost onto a
               third line. The break element above is what put them here. */
            .date-header-weather {
                order: 91; flex: 1 1 auto; min-width: 0;
                overflow-x: auto; overflow-y: hidden;
            }
            /* What the strip becomes when the day has more forecasts than the
               screen can carry (see collapseIfCramped). */
            .wx-mini-btn {
                order: 91; flex: 0 0 auto; display: inline-flex; align-items: center; gap: .3rem;
                font-size: 11px; font-weight: 700; color: var(--tl-text-soft);
                background: var(--tl-hover); border-radius: 999px; padding: .15rem .55rem;
            }
            .wx-mini-btn .wx-mini-n {
                font-size: 10px; font-weight: 800; color: var(--tl-text-faint);
                border-left: 1px solid var(--tl-border-soft); padding-left: .3rem;
            }
            .date-header-range { flex: 0 1 auto; min-width: 0; overflow: hidden; }
        }

        /* Touch has no hover, so a reveal-on-hover control is one you can never
           see — show the day-note pair outright. The room they need is already
           reserved unconditionally above. */
        @media (hover: none), (pointer: coarse) {
            .date-note-edit, .date-note-del { opacity: 1; }

            /* Notes read as one line here, like the cards around them, so a day
               with several notes still fits on a screen. Tapping one opens it
               in full — see openNoteInfo. */
            /* Clamped, not nowrap: a note's text sits inside <p> elements, and
               text-overflow cannot trim a block child — the ellipsis had
               nothing to act on, so the note simply kept its full height. A
               line clamp counts rendered lines, so it works through them. */
            .inline-note .inline-note-body,
            .date-note-block .date-note-inner {
                display: -webkit-box; -webkit-box-orient: vertical;
                -webkit-line-clamp: 1; overflow: hidden; white-space: normal;
            }
            /* Keep the children inline so the clamp measures one flowing line
               rather than one line per paragraph. */
            .inline-note .inline-note-body > *,
            .date-note-block .date-note-inner > * { display: inline; margin: 0; }
            .inline-note .inline-note-body img,
            .date-note-block .date-note-inner img { display: none; }

            /* Card head on one row: done, type icon, lot, kebab — with the
               title and its badges on full-width rows underneath.
               `display: contents` lifts the two wrapper divs out of the way so
               their children become items of the head row directly, which lets
               the row wrap where we want without restructuring the markup (the
               Blade partial and the JS renderer are twins and must stay
               identical, so a DOM change would have to be made twice). */
            .activity-card > .flex.items-start.justify-between { flex-wrap: wrap; row-gap: .4rem; justify-content: flex-start; }
            .activity-card > .flex.items-start.justify-between > .flex.items-start { display: contents; }
            .activity-card > .flex.items-start.justify-between > .flex.items-start > .min-w-0.grow { display: contents; }

            .activity-card .done-check,
            .activity-card .type-ico { flex: 0 0 auto; align-self: center; }
            .activity-card .activity-card-lothead {
                /* The tag's cap height sits high in its box, so centring the
                   box still reads as high against the round icons either side.
                   Nudge it down to sit on their optical centre. */
                /* Nudged with position, not margin: on a centred flex item a
                   top margin grows the margin box and the centring gives back
                   half of it, so the tag barely moves. */
                /* Sized to its tags, not to the row: a growing lot would push
                   the kebab back out to the far edge instead of letting it
                   sit right after the name. The max-width is what keeps the
                   two together — a wrapping flex line breaks BEFORE it
                   shrinks, so a card with two long lot tags used to throw the
                   lots onto their own line and the kebab onto a third. Capped
                   to the space the row has left, the box never triggers that
                   break and scrolls its tags internally instead. */
                /* 8.5rem = the check, the type icon, the 44px kebab and the
                   three gaps beside it, measured rather than guessed. */
                flex: 0 1 auto; min-width: 0; max-width: calc(100% - 8.5rem); align-self: center;
                position: relative; top: .18rem;
                display: flex; flex-wrap: nowrap; gap: .25rem;
                overflow-x: auto; scrollbar-width: none;
            }
            .activity-card .activity-card-lothead::-webkit-scrollbar { display: none; }
            /* Tags keep their own width inside that capped box — left to
               shrink they broke "Lot A — North Field" over three lines and
               made the head row as tall as the card. They scroll instead. */
            .activity-card .activity-card-lothead > .item-tag { flex: 0 0 auto; white-space: nowrap; }
            /* The kebab rides the head row again, right after the lot name.
               It is back in flow, so `order` is what keeps it on that first
               line: the title, badges and lot meta are full-width items of
               this same wrapped row (their wrappers are display:contents) and
               in source order they come BEFORE the actions box, which would
               otherwise strand the kebab on a line of its own underneath.
               The corner it used to be pinned to now belongs to the drag
               grip; the row still reserves that column so a long lot name
               cannot run under the grip or the fold chevron. */
            .activity-card { position: relative; }
            .activity-card > .flex.items-start.justify-between > .flex.items-center {
                position: static; order: 1; align-self: center; margin: 0;
            }
            .activity-card .activity-card-title,
            .activity-card .activity-card-badges,
            .activity-card .activity-card-lotmeta { order: 2; }
            .activity-card > .flex.items-start.justify-between { padding-right: 1.9rem; }
            .activity-card .card-menu-btn { flex: 0 0 auto; }

            /* ---- Card accordion (phones) ----
               A day of full cards is a wall; collapsed to their head row the
               whole day scans at a glance and a drag has short distances to
               travel. Tap expands one card, tap again folds it; the open set
               persists per schedule (see CARD_OPEN in the JS).
               The head row is the card's first child, so everything after it
               folds away; badges and lot meta live INSIDE the head (promoted
               by the display:contents restructure above), so they are named
               separately. Three-class selectors on purpose: the clamps they
               override sit later in this file, and equal specificity would
               lose on source order — the trap this file keeps springing. */
            .activity-card.act-collapsed > :not(:first-child) { display: none; }
            .activity-card.act-collapsed .activity-card-badges,
            .activity-card.act-collapsed .activity-card-lotmeta { display: none; }
            /* An expanded card is the read view, so the scanning clamps come
               off: full title, full description. */
            .activity-card:not(.act-collapsed) .activity-card-title {
                white-space: normal; overflow: visible; text-overflow: clip;
            }
            .activity-card:not(.act-collapsed) .activity-description-content {
                display: block; -webkit-line-clamp: unset; overflow: visible;
            }

            /* Jump buttons: bottom-right, clear of the day headers. */
            .act-jumps { position: fixed; right: .8rem; bottom: 1rem; z-index: 40; display: flex; flex-direction: column; gap: .45rem; }
            .act-jumps button { width: 2.6rem; height: 2.6rem; border-radius: 999px; background: var(--color-white); color: var(--color-gray-600); border: 1px solid var(--color-gray-200); box-shadow: 0 4px 14px rgb(0 0 0 / .18); display: flex; align-items: center; justify-content: center; }
            .act-jumps button svg { width: 1.2rem; height: 1.2rem; }
            .act-jumps button:active { transform: scale(.92); }
            .act-jumps.module-hidden { display: none; }
            /* The jump buttons earn their place only once the header bar
               (versions + Today) has scrolled away — while it is on screen
               they stay out of the way. Animated, per the house rule. */
            .act-jumps { transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
            .act-jumps.bar-visible { opacity: 0; pointer-events: none; transform: translateY(.5rem); }
            .act-jumps .act-fab-add { background: var(--color-brand-600); color: #fff; border-color: transparent; }
            @media (prefers-reduced-motion: reduce) { .act-jumps { transition: none; } }
            .version-sheet-row { width: 100%; display: flex; align-items: center; justify-content: space-between;
                gap: .6rem; text-align: left; padding: .7rem .8rem; border-radius: .8rem; font-weight: 700;
                color: var(--color-gray-800); background: var(--color-white); border: 1px solid var(--color-gray-200);
                margin-bottom: .45rem; }
            .version-sheet-row.is-current { border-color: var(--color-brand-500); background: var(--color-brand-50); }
            .vsr-now { font-size: .68rem; font-weight: 800; color: var(--color-brand-700); text-transform: uppercase; flex-shrink: 0; }
            .vsr-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .vsr-add { justify-content: center; border-style: dashed; color: var(--color-gray-500); }

            /* A chevron says the card folds — drawn by CSS so the twin Blade
               and JS renderers stay byte-identical. */
            .activity-card::before {
                content: ''; position: absolute; right: 1.05rem; top: 3.1rem;
                width: .5rem; height: .5rem; pointer-events: none;
                border-right: 2px solid var(--tl-text-faint, #9ca3af);
                border-bottom: 2px solid var(--tl-text-faint, #9ca3af);
                transform: rotate(45deg);
                transition: transform .28s cubic-bezier(.22,1,.36,1);
            }
            .activity-card:not(.act-collapsed)::before { transform: rotate(225deg); }

            /* A grip says the card can be dragged — the same six dots the
               inline notes use, drawn in CSS like the chevron so the twin
               renderers stay byte-identical, and pointer-events:none so the
               drag listeners still get every touch. It holds the card's
               top-right corner, above the fold chevron, in the column the
               head row already reserves — so nothing reflows around it. */
            .activity-card::after {
                content: ''; position: absolute; right: .62rem; top: .72rem;
                width: 2px; height: 2px; border-radius: 50%;
                pointer-events: none; opacity: .55;
                background: var(--tl-text-faint, #9ca3af);
                box-shadow: 4px 0 0 var(--tl-text-faint, #9ca3af),
                            0 5px 0 var(--tl-text-faint, #9ca3af),
                            4px 5px 0 var(--tl-text-faint, #9ca3af),
                            0 10px 0 var(--tl-text-faint, #9ca3af),
                            4px 10px 0 var(--tl-text-faint, #9ca3af);
            }
            /* A done card is locked (draggable="false") — no grip to promise
               otherwise. Same for one mid-drag: the ghost carries its own. */
            .activity-card.is-done::after,
            .activity-card.dragging::after { display: none; }

            /* Title and tags each claim their own full-width row. */
            .activity-card .activity-card-title,
            .activity-card .activity-card-badges,
            .activity-card .activity-card-lotmeta { flex: 0 0 100%; width: 100%; min-width: 0; }
            /* One line each, trimmed with an ellipsis. A long title used to wrap
               to three or four lines and push everything below it down the
               screen, so a card told you less per scroll than a short one did.
               The full text is a tap away in the activity itself. */
            .activity-card .activity-card-title {
                margin-top: .1rem;
                /* The two-line clamp further down this file is a -webkit-box,
                   which cannot survive here: on mobile the title is a flex item
                   (its wrappers are display:contents), and a flex item blockifies
                   that box, so the clamp silently stops applying. Take the title
                   out of flex layout with a block display, then a plain
                   single-line ellipsis works. */
                display: block;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            /* The description is rich text, so its children are blocks and
               text-overflow has nothing to act on — clamp the box instead. */
            .activity-card .activity-description-content {
                display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .activity-card .activity-description-content > * { margin: 0; }
        }
        .date-note-block.dragging, .progress-marker.dragging { opacity: .45; }
        .progress-marker[draggable="true"] { cursor: grab; }
        html.dark .date-note-block:hover { border-color: #eec155; }
        html.dark .date-note-edit, html.dark .date-note-del { background: #232b1a; }
        html.dark .date-note-del { color: #f87171; }
        html.dark .date-note-edit { color: #9fd979; }
        @media (hover: none), (pointer: coarse) {
            .date-note-edit, .date-note-del { opacity: 1; width: 2.4rem; height: 2.4rem; }
            /* Clears the edit button's far edge: right 3rem + 2.4rem wide, plus
               a gap. It was 2.8rem, which did not even reach the button's near
               edge — so on a phone the note text ran underneath both buttons. */
            .date-note-inner { padding-right: 5.7rem; }
            .date-note-edit { right: 3rem; }
            .date-note-edit svg, .date-note-del svg { width: 1.35rem; height: 1.35rem; }
        }
        @media (prefers-reduced-motion: reduce) { .date-note-block { transition: none; } }

        /* Moving a note/marker to another day: a spinner + dim while the save
           runs, then the note animates into its new home (never a blank snap). */
        .date-note-block.is-moving, .progress-marker.is-moving { pointer-events: none; opacity: .65; position: relative; }
        .date-note-block.is-moving::after { opacity: 0 !important; }
        .note-move-spin { position: absolute; top: .3rem; right: .5rem; color: #b45309; display: inline-flex; }
        .note-move-spin svg { width: 1rem; height: 1rem; animation: btnspin .7s linear infinite; }
        html.dark .note-move-spin { color: #eec155; }
        @keyframes noteLandIn { from { opacity: 0; transform: translateY(-10px) scale(.97); } to { opacity: 1; transform: none; } }
        .note-landed { animation: noteLandIn .34s cubic-bezier(.22,1,.36,1); }
        @media (prefers-reduced-motion: reduce) { .note-move-spin svg { animation-duration: 1.4s; } .note-landed { animation: none; } }

        /* ---- Inline sticky notes: multiple per day, dropped between cards ---- */
        .inline-note {
            position: relative; background: var(--tl-note-bg); border: 1px dashed var(--tl-note-border);
            /* Right padding clears the edit button at its largest — touch sizes
               it 2.5rem wide at right 3.2rem — so the text never runs under it
               on any device. 3.5rem only cleared the desktop pair. */
            border-radius: .6rem; padding: .5rem 6.1rem .5rem 1.85rem; font-size: .82rem; color: var(--tl-note-text);
            word-break: break-word; cursor: grab;
            user-select: none; -webkit-user-select: none;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .inline-note:hover { border-color: #f5c518; box-shadow: 0 1px 6px rgb(0 0 0 / .06); }
        .inline-note.is-editing { cursor: text; user-select: text; -webkit-user-select: text; border-style: solid; border-color: #f5c518; background: var(--tl-surface); color: var(--tl-text); }
        /* Grip: the clear drag affordance (whole note is still draggable). */
        .inline-note-grip { position: absolute; left: .05rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; padding: .3rem; color: var(--tl-note-border); cursor: grab; touch-action: none; }
        .inline-note-grip:active { cursor: grabbing; }
        /* A finger needs more than the six dots to aim at — the note's left
           padding already reserves this room, so nothing shifts. */
        @media (hover: none), (pointer: coarse) {
            .inline-note-grip { padding: .5rem .4rem; }
        }
        .inline-note:hover .inline-note-grip { color: #d9a441; }
        .inline-note.is-editing .inline-note-grip { display: none; }
        html.dark .inline-note-grip { color: #6b5a2a; }
        .inline-note.is-editing .inline-note-body { outline: none; }
        .inline-note.dragging { opacity: .45; }
        .inline-note[draggable="true"] { cursor: grab; }
        /* Small "Note" title so the sticky note announces itself as a note. */
        .inline-note-tag {
            display: inline-flex; align-items: center; gap: .2rem; line-height: 1;
            font-size: .58rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
            color: #b45309; opacity: .8; margin-bottom: .15rem; user-select: none;
        }
        .inline-note-tag svg { width: .72rem; height: .72rem; }
        html.dark .inline-note-tag { color: #e0b457; }
        .inline-note-body { min-height: 1em; white-space: pre-wrap; }
        /* Keep the note compact — drawings/images never blow up its height. */
        .inline-note-body img { max-width: 100%; max-height: 10rem; border-radius: .4rem; display: inline-block; margin: .2rem 0; }
        .inline-note-body p { margin: .15rem 0; }
        .inline-note-body ul { list-style: disc; padding-left: 1.15rem; }
        .inline-note-body ol { list-style: decimal; padding-left: 1.3rem; }
        .inline-note-media:not(:has(.nm)) { display: none; }
        .inline-note-media { display: grid; grid-template-columns: repeat(auto-fill, minmax(4.5rem, 1fr)); gap: .35rem; margin-top: .4rem; }
        .inline-note-media .nm { position: relative; border-radius: .45rem; overflow: hidden; background: #000; aspect-ratio: 1; }
        .inline-note-media .nm img, .inline-note-media .nm video { width: 100%; height: 100%; object-fit: cover; display: block; }
        /* Per-day (amber) note media gallery — same look. */
        .date-note-block .date-note-media:empty { display: none; }
        .date-note-block .date-note-media { display: grid; grid-template-columns: repeat(auto-fill, minmax(4.5rem, 1fr)); gap: .35rem; margin-top: .4rem; white-space: normal; }
        .date-note-block .date-note-media .nm { position: relative; border-radius: .45rem; overflow: hidden; background: #000; aspect-ratio: 1; }
        .date-note-block .date-note-media .nm img, .date-note-block .date-note-media .nm video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .date-note-block img { max-width: 100%; max-height: 10rem; border-radius: .4rem; }
        /* Edit + delete: ALWAYS visible (also on touch), clearly coloured on a
           solid white chip so they stand out on the yellow note. Delete = red
           trash, edit = green pencil. Bigger tap targets on phones. */
        .inline-note-del, .inline-note-edit {
            position: absolute; top: .3rem; width: 2rem; height: 2rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
            background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .2); opacity: 1;
            transition: background .15s ease, transform .1s ease;
        }
        .inline-note-del { right: .35rem; color: #dc2626; }
        .inline-note-edit { right: 2.65rem; color: var(--color-brand-700); }
        .inline-note-del svg, .inline-note-edit svg { width: 1.15rem; height: 1.15rem; }
        .inline-note-del:active, .inline-note-edit:active { transform: scale(.9); }
        .inline-note-del:hover { background: #fee2e2; }
        .inline-note-edit:hover { background: #eef7e8; }
        html.dark .inline-note-del, html.dark .inline-note-edit { background: #232b1a; box-shadow: 0 1px 3px rgb(0 0 0 / .4); }
        html.dark .inline-note-del { color: #f87171; }
        html.dark .inline-note-edit { color: #9fd979; }
        /* Phones: bigger tap targets. */
        @media (hover: none), (pointer: coarse) {
            .inline-note-del, .inline-note-edit { width: 2.5rem; height: 2.5rem; }
            .inline-note-edit { right: 3.2rem; }
            .inline-note-del svg, .inline-note-edit svg { width: 1.4rem; height: 1.4rem; }
            /* The note body clamps to one line on phones, so the box is now
               shorter than these buttons — pinned to top:.3rem they hung out
               below it. Centre on the box instead, which fits any height, and
               keep the press feedback inside the same transform so :active
               does not undo the centring. The min-height keeps a short note
               tall enough to be a comfortable tap target at all. */
            .inline-note, .date-note-block { min-height: 3.1rem; }
            .inline-note-del, .inline-note-edit,
            .date-note-edit, .date-note-del { top: 50%; transform: translateY(-50%); }
            .inline-note-del:active, .inline-note-edit:active,
            .date-note-del:active, .date-note-edit:active { transform: translateY(-50%) scale(.9); }
        }
        /* While saving/moving, the spinner owns the top-right corner (the action
           buttons are non-interactive during the move anyway) — no overlap. */
        .inline-note.is-moving .inline-note-del, .inline-note.is-moving .inline-note-edit { display: none; }
        .inline-note.note-landed { animation: noteLandIn .34s cubic-bezier(.22,1,.36,1); }

        /* ---- Per-day extra expenses strip ------------------------------- */
        .day-expense-block { margin: .55rem .7rem 0; }
        .day-expense-block:empty { display: none; }
        /* What the day earned, under what it cost — green against the
           expenses' red so the two never get read for each other. */
        .day-income-block { margin: .35rem .7rem 0; display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; }
        .day-income-block:empty, .day-income-block[hidden] { display: none; }
        .day-income-total { font-size: .7rem; font-weight: 800; color: var(--color-brand-700);
            background: var(--color-brand-50); border-radius: 999px; padding: .1rem .5rem; }
        .day-income-chip { font-size: .68rem; font-weight: 600; color: var(--color-gray-600);
            background: var(--tl-hover, var(--color-gray-100)); border-radius: 999px; padding: .1rem .5rem; }
        .dx-card {
            background: #fff7ed; border: 1px solid #fed7aa; border-radius: .6rem; overflow: hidden;
        }
        .dx-head {
            display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem;
            font-size: .74rem; font-weight: 700; color: #9a3412; letter-spacing: .01em;
        }
        .dx-head .dx-total { margin-left: auto; font-variant-numeric: tabular-nums; }
        .dx-list { display: flex; flex-direction: column; }
        .dx-row {
            display: flex; align-items: center; gap: .5rem; padding: .4rem .6rem;
            border-top: 1px dashed #fed7aa; font-size: .8rem; color: #7c2d12;
        }
        .dx-row .dx-amt { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .dx-row .dx-note { color: #9a3412; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dx-row .dx-actions { margin-left: auto; display: flex; gap: .1rem; flex-shrink: 0; }
        .dx-row .dx-btn {
            display: inline-flex; align-items: center; justify-content: center; width: 1.7rem; height: 1.7rem;
            border-radius: .4rem; color: #c2410c; transition: background .15s ease, color .15s ease;
        }
        .dx-row .dx-btn:hover { background: #ffedd5; }
        .dx-row .dx-btn.dx-del:hover { background: #fee2e2; color: #dc2626; }
        .dx-add {
            display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .6rem; width: 100%;
            border-top: 1px dashed #fed7aa; font-size: .76rem; font-weight: 700; color: #c2410c;
            transition: background .15s ease;
        }
        .dx-add:hover { background: #ffedd5; }
        .dx-add.dx-add-solo { border-top: 0; }
        /* Empty-state: a slim ghost "+ expense" when a day has none yet. */
        .dx-empty {
            display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .55rem;
            border: 1px dashed var(--tl-border); border-radius: .5rem; background: transparent;
            font-size: .72rem; font-weight: 600; color: var(--tl-text-faint);
            transition: border-color .15s ease, color .15s ease, background .15s ease;
        }
        .dx-empty:hover { border-color: #fdba74; color: #c2410c; background: #fff7ed; }

        html.dark .dx-card { background: #2b1c0e; border-color: #4a3115; }
        html.dark .dx-head { color: #f0a868; }
        html.dark .dx-row { color: #e9c9a3; border-color: #4a3115; }
        html.dark .dx-row .dx-note { color: #d1a878; }
        html.dark .dx-row .dx-btn { color: #f0a868; }
        html.dark .dx-row .dx-btn:hover { background: #3a2712; }
        html.dark .dx-row .dx-btn.dx-del:hover { background: #3d1c1f; color: #f47c7c; }
        html.dark .dx-add { color: #f0a868; border-color: #4a3115; }
        html.dark .dx-add:hover { background: #3a2712; }
        html.dark .dx-empty:hover { border-color: #7a5220; color: #f0a868; background: #2b1c0e; }

        /* ---- Activity card ----------------------------------------------
           Left rail carries the priority colour so the top-right stays free
           for actions. Tapping the card body opens the editor. */
        .activity-card {
            /* Left accent = the lot's auto colour (falls back to priority when the
               activity isn't tied to a lot). Priority still shows in the pill. */
            border: 1px solid var(--tl-border-soft); border-left: 4px solid var(--lot-accent, var(--prio-color, #d1d5db));
            border-radius: .85rem; background: var(--tl-surface); padding: .75rem .85rem;
            user-select: none; -webkit-user-select: none;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .activity-card:hover { box-shadow: var(--shadow-card); }
        .activity-card.prio-critical { --prio-color: #9c1c1c; }
        .activity-card.prio-high     { --prio-color: #f46a6a; }
        .activity-card.prio-medium   { --prio-color: #f1b44c; }
        .activity-card.prio-low      { --prio-color: #cbd5e1; }
        .activity-card[draggable="true"] { cursor: grab; }
        .activity-card img { -webkit-user-drag: none; }

        .activity-card-title {
            font-weight: 700; font-size: .95rem; line-height: 1.35; color: var(--tl-text);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        /* Photos embedded in description notes stay thumbnail-sized on cards. */
        .activity-description-content img { max-height: 9rem; border-radius: .5rem; display: inline-block; margin: .3rem .3rem 0 0; vertical-align: top; }

        /* Done cards: every action collapses down to a single "add note" button. */
        .activity-card.is-done .done-hide { display: none !important; }
        .activity-card:not(.is-done) .add-note-activity-btn { display: none; }

        /* Big "done" checkbox: checking locks the activity (no drag/edit). */
        .done-check {
            width: 1.9rem; height: 1.9rem; border-radius: .55rem; flex-shrink: 0;
            border: 2px solid var(--color-gray-300); background: var(--tl-surface); color: transparent;
            display: flex; align-items: center; justify-content: center; margin-top: .2rem; cursor: pointer;
            transition: background .18s ease, border-color .18s ease, transform .12s ease;
        }
        .done-check svg { width: 1.1rem; height: 1.1rem; }
        .done-check:hover { border-color: var(--color-brand-500); }
        .done-check:active { transform: scale(.88); }
        .done-check.is-checked { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; animation: donePop .25s cubic-bezier(.22,1,.36,1); }
        @keyframes donePop { 0% { transform: scale(.7); } 60% { transform: scale(1.1); } 100% { transform: scale(1); } }
        .activity-card.is-done { opacity: .8; }
        .activity-card.is-done .activity-card-title { text-decoration: line-through; text-decoration-thickness: 2px; text-decoration-color: var(--color-brand-500); }
        .activity-card.is-done[draggable="false"] { cursor: default; }
        @media (prefers-reduced-motion: reduce) { .done-check.is-checked { animation: none; } .done-check { transition: none; } }

        /* Type chip before the title: task / irrigation / service at a glance. */
        .type-ico { width: 2.1rem; height: 2.1rem; border-radius: .6rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; margin-top: .05rem; }
        .type-ico svg { width: 1.2rem; height: 1.2rem; }
        .type-ico-task { background: var(--color-brand-50); color: var(--color-brand-700); }
        .type-ico-irrigation { background: rgb(47 143 216 / .14); color: #2f8fd8; }
        html.dark .type-ico-irrigation { background: rgb(47 143 216 / .2); color: #6db5e8; }
        .type-ico-service { background: rgb(224 145 46 / .15); color: #c26d13; }
        html.dark .type-ico-service { background: rgb(224 145 46 / .2); color: #f3a257; }
        .activity-card-badges { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .3rem; }
        .activity-card-lots { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .45rem; }
        /* Lot header: shown ABOVE the title as a bold, unmissable label so you
           know which lot each activity belongs to at a glance. */
        .activity-card-lothead { margin-top: 0; margin-bottom: .35rem; gap: .35rem; }
        .activity-card-lothead .lot-tag {
            background: #3f4bb5; color: #fff; font-size: 12px; font-weight: 800;
            padding: .22rem .55rem .22rem .42rem; border-radius: .5rem; box-shadow: 0 1px 2px rgb(0 0 0 / .15);
        }
        .activity-card-lothead .lot-tag::before {
            content: ""; width: .82rem; height: .82rem; flex-shrink: 0; background: currentColor;
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z'/%3E%3C/svg%3E") center/contain no-repeat;
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z'/%3E%3C/svg%3E") center/contain no-repeat;
        }
        .activity-card-lothead .activity-na-tag { font-weight: 700; }
        html.dark .activity-card-lothead .lot-tag { background: #5b67d6; color: #fff; }
        /* Variety + DAS, below the title, as regular neutral tags. */
        .activity-card-lotmeta { margin-top: .4rem; }
        .activity-card-lotmeta:empty { display: none; margin-top: 0; }
        .activity-card-lotmeta .lot-meta-tag { background: #f3f4f6; color: #4b5563; font-weight: 600; }
        html.dark .activity-card-lotmeta .lot-meta-tag { background: var(--tl-surface-2); color: var(--tl-text-faint); }
        /* Day-counter type converter dropdown (DAS / DAT / DAP). */
        .day-type-menu { position: absolute; top: calc(100% + .35rem); left: 0; z-index: 30; min-width: 14.5rem; background: var(--tl-surface); border: 1px solid var(--tl-border); border-radius: .75rem; box-shadow: 0 14px 34px -10px rgb(0 0 0 / .35); padding: .35rem; animation: app-pop-in .18s cubic-bezier(.22,1,.36,1) both; }
        .day-type-menu.hidden { display: none; }
        .day-type-menu-hd { font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--tl-text-faint); padding: .3rem .5rem .35rem; }
        .day-type-opt { display: flex; align-items: baseline; gap: .5rem; width: 100%; text-align: left; padding: .5rem .55rem; border-radius: .55rem; color: var(--tl-text); }
        .day-type-opt:hover { background: var(--tl-hover); }
        .day-type-opt strong { font-size: .85rem; font-weight: 800; min-width: 2.5rem; }
        .day-type-opt span { font-size: .72rem; color: var(--tl-text-faint); }
        .day-type-opt.is-current { background: var(--color-brand-50); }
        .day-type-opt.is-current strong { color: var(--color-brand-700); }
        html.dark .day-type-opt.is-current { background: rgb(107 159 61 / .16); }
        @media (prefers-reduced-motion: reduce) { .day-type-menu { animation: none; } }
        /* Meta strip: time + workers + materials/services on one wrapped row. */
        .activity-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .55rem; }
        /* Two panes in the activity sheet. Hiding is done from the host with
           one class rather than by toggling each child: the type tabs already
           show and hide panels of their own, and a pane switch that restored
           children by hand would fight them. */
        .act-pane-count { min-width: 1.15rem; padding: 0 .25rem; border-radius: 999px; font-size: .65rem;
            background: var(--color-brand-600); color: #fff; }
        /* .form-input is unlayered component CSS, so its width:100% beats any
           Tailwind width utility — the amount box ate the row and squeezed the
           worker's name to nothing. Width belongs here, at the same level. */
        /* The checklist itself: a name, a box to tick, and — once ticked — how
           much of the day and at what price. An unticked row stays quiet. */
        .wp-row { display: flex; align-items: center; gap: .55rem; padding: .3rem .15rem; border-radius: .5rem; }
        .wp-row.is-on { background: var(--color-brand-50); }
        .wp-tick { display: inline-flex; align-items: center; cursor: pointer; }
        .wp-tick input { width: 1.15rem; height: 1.15rem; border-radius: .35rem; }
        .wp-name { min-width: 0; flex: 1 1 auto; font-size: .85rem; font-weight: 700; color: var(--color-gray-800);
            overflow: hidden; text-overflow: ellipsis; }
        .wp-row.is-on .wp-name { color: var(--color-brand-900, #14532d); }
        .wp-rate { display: block; font-size: .68rem; font-weight: 600; color: var(--color-gray-400); }
        .wp-part { display: flex; gap: .1rem; padding: .12rem; border-radius: .5rem; background: var(--color-gray-100);
            flex-shrink: 0; }
        .wp-part button { padding: .22rem .5rem; border-radius: .4rem; font-size: .7rem; font-weight: 700;
            color: var(--color-gray-500); }
        .wp-part button.is-on { background: var(--color-white); color: var(--color-brand-700); }
        html.dark .wp-row.is-on { background: #1a2413; }
        html.dark .wp-name { color: #e6eddd; }
        html.dark .wp-part { background: #1c2416; }
        html.dark .wp-part button.is-on { background: #243019; color: #bfe3a4; }
        .wp-amount { width: 6.5rem !important; flex: 0 0 6.5rem; }
        #activitySheet .space-y-4.on-workers > * { display: none; }
        #activitySheet .space-y-4.on-workers > #activityWorkersPane,
        #activitySheet .space-y-4.on-workers > .keep-on-workers { display: block; }
        /* Half or whole day says nothing about attendance, so the checklist
           keeps the priority beside it and drops the length. */
        #activitySheet .space-y-4.on-workers > .keep-on-workers { display: grid; }
        #activitySheet .space-y-4.on-workers .js-time-required { display: none; }
        #activitySheet .space-y-4.on-workers > #activityModeTabs { display: flex; }

        /* The wage bill for an activity: the total leads, the breakdown follows
           quietly, and on a narrow screen the breakdown wraps under it. */
        .activity-labour { display: flex; flex-wrap: wrap; align-items: baseline; gap: .4rem; margin-top: .4rem;
            font-size: .7rem; color: var(--color-gray-500); }
        .activity-labour .al-total { font-size: .78rem; font-weight: 800; color: var(--color-gray-800); }
        .activity-labour .al-parts { min-width: 0; }

        /* Things this activity points at. Read as links, not as more badges —
           a badge describes the activity, a tag goes somewhere. */
        .activity-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
        .activity-tags:empty { display: none; }
        .act-tag { display: inline-flex; align-items: center; gap: .28rem; max-width: 100%;
            padding: .2rem .5rem .2rem .38rem; border-radius: 999px; font-size: .68rem; font-weight: 700;
            color: var(--color-brand-800); background: var(--color-brand-50);
            border: 1px solid var(--color-brand-100); cursor: pointer;
            transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .act-tag:hover { background: var(--color-brand-100); border-color: var(--color-brand-300); }
        .act-tag svg { width: .85rem; height: .85rem; flex-shrink: 0; }
        .act-tag { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        #activityTagTabs .btn.is-on { background: var(--color-white); color: var(--color-brand-700); }
        @media (prefers-reduced-motion: reduce) { .act-tag { transition: none; } }
        .meta-time {
            display: inline-flex; align-items: center; gap: .25rem; background: var(--tl-surface-2); color: var(--tl-text-muted);
            border-radius: .5rem; padding: .2rem .45rem; font-size: 11.5px; font-weight: 600;
        }
        /* The date header drags the whole day's activities to another date. */
        .date-header[draggable="true"] { cursor: grab; }
        .date-header.dragging { opacity: .55; }
        .date-group.drag-over-group { outline: 2px dashed var(--date-color, #4A90E2); outline-offset: 2px; }
        /* "Hide empty dates" filter */
        /* Hiding empty dates folds each row shut instead of snapping away.
           (The collapsed-state rules live with .rest-day-marker below.) */

        /* ---- SPA shell ---------------------------------------------------
           `module-hidden` beats component classes that set their own display
           (the reason a plain `hidden` utility can lose here). */
        .module-hidden { display: none !important; }
        /* Deep-linked straight into a module (?module=notes): the JS swap only
           runs at DOMContentLoaded, so without this the whole Activities board
           painted first and then vanished — Notes appeared to route through
           Activities. Hide the board and its chrome from FIRST PAINT and show
           the loader instead; applyDeepLink lifts the class once the module
           has actually landed (or immediately, for an unknown key). */
        body.module-booting #activitiesRoot,
        body.module-booting [data-activities-only] { display: none !important; }
        body.module-booting #moduleLoader { display: block !important; }
        /* While booting, a plain page-coloured sheet with a spinner covers
           everything — the header retitling, the back button popping in, the
           chrome settling — so the first thing seen IS the module. Pseudo
           elements so there is no markup to clean up; the sheet vanishes with
           the class once applyDeepLink lands the module. */
        body.module-booting::after {
            content: ''; position: fixed; inset: 0; z-index: 300;
            background: var(--color-gray-50);
        }
        body.module-booting::before {
            content: ''; position: fixed; z-index: 301;
            top: 42%; left: 50%; width: 2.25rem; height: 2.25rem;
            margin-left: -1.125rem; border-radius: 999px;
            border: 3px solid var(--color-brand-200);
            border-top-color: var(--color-brand-600);
            animation: modBootSpin .8s linear infinite;
        }
        @keyframes modBootSpin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) {
            body.module-booting::before { animation-duration: 1.6s; }
        }
        /* On phones the remaining desktop-only tools (undo / redo / show-hidden)
           collapse into the Tools menu; they show only from md up. The rule
           wins over the buttons' own display/`hidden` toggling on small screens.
           These carry !important because `.btn` is unlayered CSS and would
           otherwise beat Tailwind's layered `hidden` / `md:hidden` utilities. */
        @media (max-width: 767px) {
            .toolbar-desktop-action { display: none !important; }
            /* Done cards keep their "add note" button on desktop only — on a
               phone it sat alone above the fold chevron and read as clutter.
               !important for the same unlayered-CSS reason as above. */
            .add-note-activity-btn { display: none !important; }
            /* Both day filters live behind the eye button here; the real
               buttons stay in the DOM so the sheet can forward to them. */
            #toggleEmptyDatesBtn, #toggleDoneDaysBtn { display: none !important; }
            /* The versions chip strip folds behind the Versions button. This
               is keyed on WIDTH alone: it used to sit inside the touch-only
               media query, so any narrow screen reporting a fine pointer (a
               phone asking for the desktop site, a touch laptop) kept the
               strip, grew this row past the screen and pushed the eye and Add
               buttons off the right edge where nothing could reach them. */
            #versionStrip { display: none !important; }
            /* And if a row ever does outgrow the screen, it wraps rather than
               hiding controls off-edge. */
            #actHeaderBar { flex-wrap: wrap; row-gap: .35rem; }
            /* These two moved into the eye sheet on phones; the Tools rows
               would be the duplicates now. They stay for desktop, where
               Contract All has no other entry point at all. */
            .activity-action-row[data-forward="contractAllBtn"],
            .activity-action-row[data-forward="toggleHiddenBtn"] { display: none !important; }
        }
        /* Phones only. `md:hidden` alone does NOT hold here: `.btn` is
           unlayered CSS and beats Tailwind's layered utility, so the button
           kept showing next to the two real toggles on desktop. */
        @media (min-width: 768px) { #viewFilterBtn { display: none !important; } }
        #viewFilterBtn.is-filtering { background: var(--color-brand-50); border-color: var(--color-brand-400); color: var(--color-brand-800); }
        .view-filter-row { width: 100%; display: flex; align-items: center; gap: .75rem; text-align: left;
            padding: .7rem .8rem; border-radius: .8rem; background: var(--color-white);
            border: 1px solid var(--color-gray-200); margin-bottom: .45rem; }
        .vf-ico { width: 2.25rem; height: 2.25rem; border-radius: .7rem; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: var(--color-brand-50); color: var(--color-brand-600); }
        .vf-name { display: block; font-weight: 700; color: var(--color-gray-800); }
        .vf-sub { display: block; font-size: .72rem; color: var(--color-gray-500); }
        .vf-state { flex-shrink: 0; font-size: .7rem; font-weight: 800; text-transform: uppercase;
            padding: .2rem .5rem; border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-700); }
        .vf-state.is-off { background: var(--color-gray-100); color: var(--color-gray-500); }
        /* An action, not a state: "Contract all" does a thing and closes. */
        .vf-go { flex-shrink: 0; font-size: .7rem; font-weight: 800; text-transform: uppercase;
            padding: .2rem .5rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-600); }
        /* Own class, not Tailwind's `hidden`: .view-filter-row's display is
           unlayered CSS here and would beat the layered utility. */
        .view-filter-row.is-gone { display: none !important; }
        /* Drafts / Report / Search / Calendar / Weather now live only inside the
           Tools menu (#activityActionsBtn) on every screen size. They stay in the
           DOM so the menu rows can forward clicks to their real handlers. */
        .toolbar-in-menu { display: none !important; }
        #toggleHiddenBtn.hidden { display: none !important; }
        /* !important so the disabled dimming survives the sheet's fade-in
           animation (which otherwise forces opacity back to 1). */
        .activity-action-row:disabled { opacity: .4 !important; pointer-events: none; }
        /* Reminder checklist — the builder in the sheet, then the ticks on
           the card. Both read as a list of small promises about a day. */
        .rem-rows { display: grid; gap: .5rem; }
        .rem-row { display: grid; grid-template-columns: 1fr auto; gap: .4rem; align-items: center;
            border: 1px solid var(--color-gray-200, #e5e7eb); border-radius: .7rem; padding: .5rem; }
        .rem-row .rem-text { width: 100%; }
        .rem-row .rem-del { width: 2rem; height: 2rem; border-radius: 999px; color: #9ca3af;
            display: inline-flex; align-items: center; justify-content: center; }
        .rem-row .rem-del:hover { background: #fee2e2; color: #b91c1c; }
        .rem-money { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
        .rem-money .rem-kind { flex: 0 0 auto; }
        .rem-money .rem-amount { flex: 1 1 7rem; min-width: 6rem; }
        .rem-money.is-free .rem-amount { display: none; }

        .act-rem { margin-top: .5rem; border-top: 1px dashed var(--color-gray-200, #e5e7eb); padding-top: .5rem; }
        .act-rem-row { display: flex; align-items: center; gap: .5rem; padding: .3rem .1rem; cursor: pointer; }
        .act-rem-row input { width: 1.1rem; height: 1.1rem; flex: 0 0 auto; accent-color: #4a7c2a; }
        .act-rem-name { flex: 1 1 auto; font-size: .85rem; font-weight: 600; color: #374151; }
        .act-rem-row.is-done .act-rem-name { color: #9ca3af; text-decoration: line-through; }
        .act-rem-amt { flex: 0 0 auto; font-size: .78rem; font-weight: 800; }
        .act-rem-amt.is-expense { color: #b91c1c; }
        .act-rem-amt.is-income { color: #15803d; }
        .act-rem-total { display: flex; justify-content: space-between; gap: .5rem; margin-top: .35rem;
            padding-top: .35rem; border-top: 1px solid var(--color-gray-100, #f3f4f6);
            font-size: .78rem; font-weight: 800; color: #374151; }
        html.dark .act-rem-name { color: #cdd8c0; }
        html.dark .act-rem-total { color: #cdd8c0; }
        html.dark .rem-row { border-color: #2a3050; }

        /* The date, said the way a person says it. The native input sits on
           top at zero opacity so the phone's picker still opens on a tap. */
        .date-pill { position: relative; display: inline-flex; align-items: center; gap: .5rem;
            padding: .5rem .85rem; min-height: 2.75rem; border: 2px solid var(--color-gray-200, #e5e7eb);
            border-radius: 999px; background: #fff; cursor: pointer; max-width: 100%;
            transition: border-color .2s ease, background .2s ease; }
        .date-pill:hover { border-color: #a8cc7e; background: #f3f8ec; }
        .date-pill:focus-within { border-color: #4a7c2a; }
        .date-pill .dp-ico { width: 1.15rem; height: 1.15rem; color: #4a7c2a; flex: 0 0 auto; }
        .date-pill .dp-text { font-size: .92rem; font-weight: 700; color: #1f2937; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis; }
        .date-pill.is-empty .dp-text { font-weight: 500; color: #9ca3af; }
        .date-pill .dp-input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0;
            border: 0; padding: 0; margin: 0; cursor: pointer; background: transparent; }
        html.dark .date-pill { background: #1c2136; border-color: #2a3050; }
        html.dark .date-pill .dp-text { color: #e5e9f5; }
        html.dark .date-pill:hover { background: #232a45; border-color: #3d6823; }
        @media (prefers-reduced-motion: reduce) { .date-pill { transition: none; } }

        /* The veil over a board still working out its figures. Absolute over
           the timeline rather than fixed over the app: the header, the
           toolbar and the tab bar all still work while it is up. */
        /* The veil measures against the page body of this module, so it
           covers the board and nothing above it. Without a positioned
           ancestor `inset: 0` would resolve against the viewport and put a
           sheet over the header too. */
        body.is-activities main { position: relative; }
        #boardVeil { position: absolute; inset: 0; z-index: 30; display: flex; align-items: flex-start;
            justify-content: center; padding-top: 4rem; background: var(--color-gray-50, #f9fafb);
            transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        #boardVeil.is-done { opacity: 0; pointer-events: none; }
        #boardVeil[hidden] { display: none; }
        .bv-card { display: flex; flex-direction: column; align-items: center; gap: .5rem;
            padding: 1.4rem 1.8rem; border-radius: 1rem; background: var(--color-white, #fff);
            border: 1px solid var(--color-gray-200, #e5e7eb);
            box-shadow: 0 20px 45px -30px rgb(15 23 42 / .6); }
        .bv-spin { width: 2.1rem; height: 2.1rem; border-radius: 999px;
            border: 3px solid var(--color-gray-200, #e5e7eb); border-top-color: #4a7c2a;
            animation: bvSpin .8s linear infinite; }
        @keyframes bvSpin { to { transform: rotate(360deg); } }
        .bv-text { font-size: .9rem; font-weight: 800; color: var(--color-gray-800, #1f2937); }
        .bv-sub { font-size: .72rem; color: var(--color-gray-400, #9ca3af); }
        html.dark #boardVeil { background: #10160c; }
        html.dark .bv-card { background: #151b12; border-color: #2b3a1c; }
        html.dark .bv-text { color: #e8efe1; }
        @media (prefers-reduced-motion: reduce) {
            .bv-spin { animation-duration: 2.4s; }
            #boardVeil { transition: none; }
        }

        /* Day-zero only: hide everything that is not an anchor, and any day
           header left with nothing under it. Unlayered and !important for the
           usual reason — these cards carry their own display. */
        body.only-day-zero #activitiesList .activity-card:not([data-is-day-zero="1"]):not([data-is-transplant="1"]) { display: none !important; }
        body.only-day-zero .date-group.dz-away { display: none !important; }
        body.only-day-zero .activity-card { animation: app-fade-in .28s ease both; }
        @media (prefers-reduced-motion: reduce) { body.only-day-zero .activity-card { animation: none; } }

        /* A worker the rules say is off this day. Still there to be picked —
           the farm may need them anyway — but it takes a deliberate yes, and
           the name says so afterwards. */
        .worker-chip.is-off { opacity: .5; border-style: dashed; }
        .worker-chip.is-off:hover { opacity: .75; }
        .worker-chip.is-forced { border-color: #fca5a5; }
        .chip-forced, .w-forced {
            margin-left: .3rem; font-size: .58rem; font-weight: 800; letter-spacing: .04em;
            text-transform: uppercase; color: #dc2626;
        }
        .worker-tag .w-forced, .act-check-name .w-forced { color: #dc2626; }
        html.dark .chip-forced, html.dark .w-forced { color: #fca5a5; }

        /* Injected modules keep their own chip nav in the markup — the toolbar
           hamburger replaces it, so hide it inside the shell. */
        #moduleHost .module-chip-nav { display: none; }
        /* Opacity only, for the same reason <main> fades without moving: a
           transform here — even the identity matrix `animation-fill-mode: both`
           leaves behind when the fade is over — makes the pane the containing
           block for every `position: fixed` child inside it. The module's own
           sheets then hang off the pane instead of the viewport, and a closed
           sheet parked below its own content stretched the page by its full
           height: a long empty scroll under the footer. The module still
           arrives with motion — showModule adds .sm-view-in, which animates the
           slide and takes itself off again when it ends. */
        #moduleHost > div { animation: app-fade-in .3s cubic-bezier(.22,1,.36,1) both; }
        /* The item being dragged stays in place as the live insertion slot,
           dimmed and outlined; a faded copy travels with the pointer/finger. */
        .activity-card.dragging {
            opacity: .3;
            outline: 2px dashed #b9c6a8;
            outline-offset: -2px;
            filter: grayscale(.5);
        }
        .drag-ghost {
            position: fixed; top: 0; left: 0; z-index: 90;
            margin: 0; pointer-events: none;
            opacity: .72;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .3);
            transform-origin: top left; will-change: transform;
        }
        /* No text selection or long-press callout while a touch drag is live. */
        body.is-touch-dragging {
            user-select: none; -webkit-user-select: none;
            -webkit-touch-callout: none; overscroll-behavior: contain;
        }
        /* No zoom on this screen. Releasing a dragged card reads as the second
           tap of a double-tap, so the board jumped after every drop, and a
           pinch part-way through a drag left the page magnified mid-move.
           `manipulation` only stopped the double-tap half; permitting panning
           alone stops both. Scrolling is unaffected, and this is scoped to the
           page rather than the viewport meta, so zoom still works everywhere
           else in the app. An inline touch-action set while dragging wins. */
        body.no-zoom, body.no-zoom * { touch-action: pan-x pan-y; }

        /* The element being dragged opts out of browser touch handling, so a
           scroll started with a second finger cannot claim the finger that is
           doing the dragging — the browser would otherwise cancel that pointer
           and the drag with it. The page still scrolls; the drag survives.
           These carry `body.no-zoom` because the rule above matches
           `body.no-zoom *` — one class AND an element name, which outweighs a
           lone class and quietly handed these elements back to the browser.
           That is what stopped notes being draggable on phones at all: cards
           drag on touch events and can preventDefault their way out, but a
           note drags on POINTER events, where only touch-action can stop the
           browser scrolling and cancelling the pointer mid-drag. */
        .dragging,
        body.no-zoom .dragging,
        body.no-zoom .inline-note-grip { touch-action: none; }
        .activity-card, .date-header[draggable="true"] { -webkit-touch-callout: none; }
        .activity-card-image img { max-width: 100%; max-height: 260px; border-radius: .6rem; border: 1px solid #eef0f3; }

        /* Task / Irrigation mode tabs (add-activity sheet) */
        /* The four kinds of activity read as tags, not as a segmented strip:
           same round-bordered pill the lot and worker chips use further down
           the same form, so picking a kind looks like the picking the rest of
           the sheet already asks for. They are still tabs — one at a time,
           arrow keys and all — this is only how they look. */
        .activity-mode-tabs { display: flex; flex-wrap: wrap; gap: .4rem; padding: 0; background: transparent; border-radius: 0; width: 100%; }
        /* Hiding the strip when editing has to be said here. This rule is
           unlayered, so its display beats Tailwind's .hidden utility — adding
           that class did nothing at all, which is why the type was still
           changeable on an activity that already exists. */
        .activity-mode-tabs.is-locked { display: none !important; }
        /* A fourth kind never fitted a phone in one row; as tags they wrap
           onto the next line instead of sliding out of sight. */
        @media (max-width: 640px) {
            .activity-mode-tab { padding: .42rem .65rem; font-size: .8rem; }
        }
        .activity-mode-tab {
            flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            padding: .45rem .8rem; border: 2px solid var(--color-gray-200, #e5e7eb); background: #fff;
            border-radius: 999px; font-size: .85rem; font-weight: 600; color: #374151; cursor: pointer;
            transition: background .25s ease, color .25s ease, border-color .25s ease, transform .15s ease;
        }
        .activity-mode-tab:hover { border-color: #a8cc7e; background: #f3f8ec; }
        .activity-mode-tab.is-active { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
        .activity-mode-tab.is-active:hover { background: #3d6823; border-color: #3d6823; }
        .activity-mode-tab:active { transform: scale(.97); }
        .activity-mode-tab .act-pane-count { background: rgb(255 255 255 / .25); }
        html.dark .activity-mode-tab { background: #1c2136; border-color: #2a3050; color: #cdd8c0; }
        html.dark .activity-mode-tab:hover { background: #232a45; border-color: #3d6823; }
        html.dark .activity-mode-tab.is-active { background: #4a7c2a; border-color: #6b9f3d; color: #fff; }
        @media (prefers-reduced-motion: reduce) { .activity-mode-tab { transition: none; } }

        /* Date ↔ DAS chooser inside the activity sheet (smaller sibling of the mode tabs) */
        .when-tabs { display: inline-flex; gap: .25rem; padding: .2rem; background: #f1f3f7; border-radius: .65rem; width: 100%; margin-bottom: .5rem; }
        .when-tab { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .45rem .6rem; border: none; background: transparent; border-radius: .5rem; font-size: .8rem; font-weight: 600; color: #5b6472; cursor: pointer; transition: background .25s ease, color .25s ease, box-shadow .25s ease; }
        .when-tab.is-active { background: #fff; color: #1f2937; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
        .when-tab:active { transform: scale(.97); }
        .when-tab:disabled { opacity: .45; cursor: not-allowed; }
        html.dark .when-tabs { background: #1c2136; }
        html.dark .when-tab.is-active { background: #2a3050; color: #e5e9f5; }
        .when-pane.hidden { display: none !important; }
        .when-pane-in { animation: modeFieldIn .28s cubic-bezier(.22,1,.36,1); }
        @media (prefers-reduced-motion: reduce) { .when-pane-in { animation: none; } }

        /* Pop-in for buttons that appear after a done-check swap */
        @keyframes btnPopIn { from { opacity: 0; transform: scale(.7); } to { opacity: 1; transform: none; } }
        .btn-pop-in { animation: btnPopIn .28s cubic-bezier(.22,1,.36,1); }
        @media (prefers-reduced-motion: reduce) { .btn-pop-in { animation: none; } }

        /* Fade + slide the mode-specific field into view when tabs change. */
        @keyframes modeFieldIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
        .mode-field-in { animation: modeFieldIn .28s cubic-bezier(.22, 1, .36, 1) both; }

        /* Water-task badge on irrigation activity cards — tint from --wt */
        .water-task-badge { background: color-mix(in srgb, var(--wt) 14%, transparent); color: var(--wt); border: 1px solid color-mix(in srgb, var(--wt) 42%, transparent); }
        .service-badge { background: #eef4ec; color: #3d6a1c; border: 1px solid #cfe1c4; }
        html.dark .service-badge { background: #22331c; color: #b7d69a; border-color: #33502a; }

        /* Reference-image thumbnails on cards (multiple) */
        .activity-card-images { display: flex; flex-wrap: wrap; gap: .4rem; }
        .activity-card-images img { width: 84px; height: 84px; object-fit: cover; border-radius: .6rem; border: 1px solid #eef0f3; }
        html.dark .activity-card-images img { border-color: var(--tl-border-soft); }

        /* Reference-image gallery in the sheet (thumb + remove) */
        .activity-image-thumb { position: relative; aspect-ratio: 1; border-radius: .6rem; overflow: hidden; border: 1px solid #e5e7eb; }
        .activity-image-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .activity-image-x { position: absolute; top: .25rem; right: .25rem; width: 1.9rem; height: 1.9rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; background: rgba(17,24,39,.72); color: #fff; font-size: .9rem; line-height: 1; cursor: pointer; }
        @media (max-width: 640px) { .activity-image-x { width: 2.4rem; height: 2.4rem; font-size: 1.1rem; } }

        /* Bigger remove-X on material/service tags for easier mobile taps */
        .remove-item-tag { display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 9999px; color: inherit; opacity: .7; font-weight: 700; cursor: pointer; }
        .remove-item-tag:hover, .remove-item-tag:active { opacity: 1; background: rgba(0,0,0,.1); }
        @media (max-width: 640px) { .remove-item-tag { width: 2.1rem; height: 2.1rem; font-size: 1rem; } }
        /* Keep list rows scannable — the full text is in the editor. */
        .activity-description-content {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .activity-description-content p { margin: .25rem 0; }
        .activity-description-content ul { list-style: disc; padding-left: 1.25rem; }
        .activity-description-content ol { list-style: decimal; padding-left: 1.25rem; }
        .activity-description-content a { color: #4a7c2a; text-decoration: underline; }

        .item-tag {
            display: inline-flex; align-items: center; gap: .25rem; background: #eef0fb; color: #3a4699;
            border-radius: .5rem; padding: .18rem .5rem; font-size: 11.5px; font-weight: 600;
        }
        .worker-tag { background: #fef3e8; color: #a66200; }
        .service-tag { background: #e6f7f1; color: #0f6f4d; }
        .item-tag-price { font-weight: 700; opacity: .85; }
        .activity-na-tag { background: #f3f4f6; color: #6b7280; border: 1px dashed #d1d5db; }
        .day-zero-badge { background: #ff9800; color: #fff; }
        .transplant-badge { background: #16a34a; color: #fff; }

        /* Touch targets: 44px on phones, tighter once there's a mouse. */
        .icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.75rem; height: 2.75rem; border-radius: .65rem; color: #6b7280; flex-shrink: 0; cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }
        .icon-btn:active { background: #e5e7eb; }
        .icon-btn:hover { background: #f3f4f6; color: #374151; }
        .icon-btn-danger:hover { background: #fef2f2; color: #dc2626; }
        @media (min-width: 768px) {
            .icon-btn { width: 2.375rem; height: 2.375rem; }
            /* These component classes set `display`, which beats Tailwind's
               md:hidden — hide the phone overflow buttons explicitly. */
            .card-menu-btn, .day-menu-btn { display: none; }
        }

        .rest-day-marker {
            display: flex; align-items: center; gap: .6rem; padding: .55rem .8rem;
            border: 1.5px dashed #d1d5db; border-radius: .8rem; color: #6b7280; background: #fafafa; margin-bottom: .9rem;
            max-height: 6rem; overflow: hidden;
            transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .2s ease,
                margin-bottom .28s cubic-bezier(.22,1,.36,1), padding .28s cubic-bezier(.22,1,.36,1), border-width .28s ease;
        }
        body.hide-empty-dates .rest-day-marker,
        .rest-day-marker.filters-active {
            max-height: 0; opacity: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0; border-width: 0;
        }
        @media (prefers-reduced-motion: reduce) { .rest-day-marker { transition: none; } }
        .rest-day-marker.drag-over { border-color: #6b9f3d; background: #f3f8ec; }
        .rest-day-date { display: block; font-weight: 600; font-size: .82rem; color: #4b5563; }
        .rest-day-tag { display: block; font-size: .72rem; color: #9ca3af; }

        .progress-marker { margin: -0.35rem 0 .9rem; }
        .progress-marker-line {
            display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap;
            border-top: 2px dashed #f59e0b; padding-top: .45rem;
        }
        .progress-marker-bookmark {
            display: inline-flex; align-items: center; gap: .35rem; background: #fffbeb; border: 1px solid #fcd34d;
            color: #92400e; font-size: .78rem; font-weight: 700; border-radius: 999px; padding: .18rem .7rem;
        }
        .progress-marker-note {
            margin-top: .4rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: .6rem;
            padding: .5rem .7rem; font-size: .8rem; color: #78350f; white-space: pre-wrap;
        }

        /* Hidden-activity semantics (mother parity) */
        .activity-card.is-hidden { display: none; }
        body.show-hidden-activities .activity-card.is-hidden { display: block; opacity: .55; filter: grayscale(.4); }
        body:not(.show-hidden-activities) .date-group.all-hidden { display: none; }
        /* Fully-done days folded away by the toolbar toggle (the fold/unfold
           itself is height-animated in JS with the house easing). */
        .date-group.done-day-away { display: none; }
        #toggleDoneDaysBtn[aria-pressed="true"] { background: var(--color-brand-50); border-color: var(--color-brand-400); color: var(--color-brand-800); }
        body.show-hidden-activities .rest-day-substitute { display: none; }
        .activity-card.filter-hidden { display: none !important; }
        .date-group.group-collapsed { display: none; }
        /* .filters-active shares the animated collapse defined above. */

        /* DAS / Day-0 panels inside the activity sheet */
        .das-panel { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: .75rem; padding: .75rem; }
        .day-zero-panel { background: #fffbeb; border: 1px solid #fde68a; border-radius: .75rem; padding: .75rem; }
        .transplant-panel { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: .75rem; padding: .75rem; }

        /* Quill wrapper with HTML-source toggle */
        .sm-quill-wrap .ql-toolbar { border-top-left-radius: .75rem; border-top-right-radius: .75rem; border-color: #d1d5db; }
        /* Height and look come from the shared rules in app.css. */
        .sm-quill-wrap.is-html-mode .quill-host-wrap { display: none; }
        .sm-quill-wrap:not(.is-html-mode) .quill-source { display: none; }

        /* Version chips */
        .version-chip.is-selected svg { color: #f5c518; }

        /* Labor summary tables */
        .labor-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .labor-table th { text-align: left; font-weight: 700; color: #6b7280; padding: .4rem .5rem; background: #f9fafb; white-space: nowrap; }
        .labor-table td { padding: .45rem .5rem; border-top: 1px solid #f3f4f6; vertical-align: top; }
        .labor-table .num { text-align: right; white-space: nowrap; }

        /* ---- Night mode for the timeline -------------------------------
           Everything above that paints a pale tint needs a dark counterpart:
           same hue, dark wash, bright foreground. */
        html.dark .activity-card-image img { border-color: var(--tl-border-soft); }
        html.dark .activity-description-content a { color: #9ccd74; }

        html.dark .item-tag { background: #232847; color: #a9b3f0; }
        html.dark .worker-tag { background: #33240f; color: #e9b563; }
        html.dark .service-tag { background: #0e2b23; color: #63c8a5; }
        html.dark .activity-na-tag { background: var(--tl-surface-2); color: var(--tl-text-faint); border-color: var(--tl-border); }
        html.dark .day-zero-badge { background: #b56b00; color: #fff; }
        html.dark .transplant-badge { background: #15803d; color: #fff; }

        html.dark .icon-btn { color: var(--tl-text-faint); }
        html.dark .icon-btn:active { background: #333a44; }
        html.dark .icon-btn:hover { background: var(--tl-surface-2); color: var(--tl-text); }
        html.dark .icon-btn-danger:hover { background: #3d1c1f; color: #f47c7c; }

        html.dark .rest-day-marker {
            border-color: var(--tl-rest-border); color: var(--tl-text-faint); background: var(--tl-rest-bg);
        }
        html.dark .rest-day-marker.drag-over { border-color: #7cb84f; background: #1e2a17; }
        html.dark .rest-day-date { color: var(--tl-text-muted); }
        html.dark .rest-day-tag { color: var(--tl-text-faint); }

        html.dark .progress-marker-bookmark { background: var(--tl-note-bg); border-color: var(--tl-note-border); color: var(--tl-note-text); }
        html.dark .progress-marker-note { background: var(--tl-note-bg); border-color: var(--tl-note-border); color: var(--tl-note-text); }

        .das-panel.das-locked select, .das-panel.das-locked input { opacity: .5; cursor: not-allowed; }
        html.dark .das-panel { background: #151f30; border-color: #24354f; }
        html.dark .day-zero-panel { background: var(--tl-note-bg); border-color: var(--tl-note-border); }
        /* The panel labels are literal blue-900/amber-900 ink — invisible on the
           dark washes above, so they get bright counterparts. */
        .das-panel .form-label { color: #1e3a8a; }
        html.dark .das-panel .form-label { color: #cfe0f7 !important; }
        html.dark .das-panel p, html.dark .das-panel .text-blue-800 { color: #a9c5ea; }
        html.dark .das-panel .form-select, html.dark .das-panel .form-input { color: #e6eefb; }
        /* Inactive tab text was a dim grey on the dark tab bar — lift it. */
        html.dark .when-tab { color: #aeb8ce; }
        /* In-sheet day-type converter pills (inside the By-DAS panel). */
        .das-daytype { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; margin-top: .6rem; padding-top: .55rem; border-top: 1px solid #bfdbfe; }
        html.dark .das-daytype { border-color: #24354f; }
        .das-daytype span { font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #3b6bb3; margin-right: .1rem; }
        html.dark .das-daytype span { color: #9ab7e0; }
        .das-dt-opt { font-size: .72rem; font-weight: 800; padding: .22rem .55rem; border-radius: 999px; background: #dbeafe; color: #1e40af; }
        .das-dt-opt:hover { background: #c7dbfd; }
        .das-dt-opt.is-current { background: #2563eb; color: #fff; }
        html.dark .das-dt-opt { background: #22314b; color: #bcd3f5; }
        html.dark .das-dt-opt.is-current { background: #3b82f6; color: #fff; }
        html.dark .day-zero-panel .text-amber-900 { color: #f5d896; }
        html.dark .day-zero-panel .text-amber-800\/80 { color: #eec155; }
        html.dark .transplant-panel { background: #0f2318; border-color: #1f4a30; }
        html.dark .transplant-panel .text-green-900 { color: #a7e8bd; }
        html.dark .transplant-panel .text-green-800\/80 { color: #7fd39a; }

        html.dark .sm-quill-wrap .ql-toolbar,
        html.dark .sm-quill-wrap .ql-container { border-color: var(--tl-border); background: var(--tl-surface); }
        html.dark .sm-quill-wrap .ql-editor { color: var(--tl-text); }
        html.dark .sm-quill-wrap .ql-editor.ql-blank::before { color: var(--tl-text-faint); }
        html.dark .sm-quill-wrap .ql-stroke { stroke: var(--tl-text-muted); }
        html.dark .sm-quill-wrap .ql-fill { fill: var(--tl-text-muted); }
        html.dark .sm-quill-wrap .ql-picker-label { color: var(--tl-text-muted); }

        html.dark .labor-table th { color: var(--tl-text-faint); background: var(--tl-surface-2); }
        html.dark .labor-table td { border-color: var(--tl-border); }

        html.dark .drag-ghost { box-shadow: 0 18px 40px rgba(0, 0, 0, .6); }
        html.dark .activity-card.dragging { outline-color: #4a5563; }

        /* ---- Readiness bell ----------------------------------------------
           A water-ripple pulses out of the button while something still needs
           setting up, and the bell itself gives an occasional nudge. Both stop
           the moment the list is clear. */
        #readinessBtn { overflow: visible; }
        .readiness-ripple {
            position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
            opacity: 0;
        }
        #readinessBtn.has-alerts .readiness-ripple::before,
        #readinessBtn.has-alerts .readiness-ripple::after {
            content: ''; position: absolute; inset: 0; border-radius: inherit;
            border: 2px solid var(--ripple-color, #f5c518);
            animation: readiness-ripple 2.4s cubic-bezier(.22, 1, .36, 1) infinite;
        }
        #readinessBtn.has-alerts .readiness-ripple::after { animation-delay: 1.2s; }
        #readinessBtn.has-alerts .readiness-ripple { opacity: 1; }
        #readinessBtn.has-blocking { --ripple-color: #ef4444; }
        #readinessBtn.has-alerts svg { animation: readiness-nudge 2.4s ease-in-out infinite; transform-origin: 50% 15%; }

        @keyframes readiness-ripple {
            0%   { transform: scale(1);    opacity: .75; }
            70%  { transform: scale(1.55); opacity: 0; }
            100% { transform: scale(1.55); opacity: 0; }
        }
        @keyframes readiness-nudge {
            0%, 62%, 100% { transform: rotate(0); }
            68% { transform: rotate(-12deg); }
            74% { transform: rotate(10deg); }
            80% { transform: rotate(-6deg); }
            86% { transform: rotate(4deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            #readinessBtn.has-alerts .readiness-ripple::before,
            #readinessBtn.has-alerts .readiness-ripple::after,
            #readinessBtn.has-alerts svg { animation: none; }
            #readinessBtn.has-alerts .readiness-ripple::before { opacity: .5; }
        }

        /* ---- Calendar view -------------------------------------------------
           A month grid sized so a whole month fits on a phone without
           scrolling sideways; each day is still a 44px-ish tap target. */
        .cal-head { display: flex; align-items: center; gap: .4rem; margin-bottom: .6rem; }
        .cal-grid-head {
            display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px;
            margin-bottom: 2px;
        }
        .cal-grid-head span {
            text-align: center; font-size: 10.5px; font-weight: 800; letter-spacing: .04em;
            text-transform: uppercase; color: var(--tl-text-faint); padding: .3rem 0;
        }
        .cal-grid {
            display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px;
            background: var(--tl-border); border: 1px solid var(--tl-border);
            border-radius: .9rem; overflow: hidden;
        }
        .cal-day {
            background: var(--tl-surface); min-height: 4.6rem; padding: .25rem .3rem;
            display: flex; flex-direction: column; gap: .15rem;
            text-align: left; cursor: pointer; position: relative;
            transition: background .12s ease;
        }
        .cal-day:hover { background: var(--tl-surface-2); }
        .cal-day.is-outside { background: var(--tl-rest-bg); }
        .cal-day.is-outside .cal-daynum { opacity: .35; }
        .cal-daynum {
            font-size: 11.5px; font-weight: 700; color: var(--tl-text-muted);
            line-height: 1.5rem; min-width: 1.5rem; text-align: center; border-radius: 999px;
        }
        .cal-day.is-today .cal-daynum { background: var(--color-brand-600); color: #fff; }
        .cal-day.is-dayzero .cal-daynum { box-shadow: inset 0 0 0 2px #ff9800; }
        html.dark .cal-day.is-dayzero .cal-daynum { box-shadow: inset 0 0 0 2px #d98b1f; }

        .cal-chip {
            display: block; width: 100%; font-size: 12px; font-weight: 700; line-height: 1.3;
            border-radius: .3rem; padding: .1rem .25rem;
            border-left: 3px solid var(--prio-color, #d1d5db);
            background: var(--tl-surface-2); color: var(--tl-text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: left;
        }
        .cal-chip.prio-critical { --prio-color: #9c1c1c; }
        .cal-chip.prio-high     { --prio-color: #f46a6a; }
        .cal-chip.prio-medium   { --prio-color: #f1b44c; }
        .cal-chip.prio-low      { --prio-color: #cbd5e1; }
        .cal-chip.is-continuation { opacity: .55; font-style: italic; }
        /* Inline type icon inside a month chip, tinted like the list cards. */
        .cal-chip svg { width: 11px; height: 11px; display: inline-block; vertical-align: -1.5px; margin-right: .2rem; }
        .cal-chip.type-task svg { color: var(--color-brand-700); }
        .cal-chip.type-irrigation svg { color: #2f8fd8; }
        html.dark .cal-chip.type-irrigation svg { color: #6db5e8; }
        .cal-chip.type-service svg { color: #c26d13; }
        html.dark .cal-chip.type-service svg { color: #f3a257; }
        .cal-more { font-size: 10px; font-weight: 700; color: var(--tl-text-faint); padding-left: .2rem; }

        /* Rows in the day sheet — full-width, unlike the chips in the grid. */
        .cal-day-row {
            display: flex; align-items: flex-start; gap: .6rem; width: 100%; text-align: left;
            border: 1px solid var(--tl-border-soft); border-radius: .75rem;
            background: var(--tl-surface); padding: .7rem .8rem;
            transition: background .12s ease;
        }
        .cal-day-row:hover { background: var(--tl-surface-2); }
        .cal-day-rail {
            width: .25rem; border-radius: 999px; align-self: stretch; flex-shrink: 0;
            background: var(--prio-color, #d1d5db);
        }
        .cal-day-row.prio-critical { --prio-color: #9c1c1c; }
        .cal-day-row.prio-high     { --prio-color: #f46a6a; }
        .cal-day-row.prio-medium   { --prio-color: #f1b44c; }
        .cal-day-row.prio-low      { --prio-color: #cbd5e1; }

        /* Dropping a card onto a day moves it there, same as the list. */
        .cal-day.drag-over { background: color-mix(in srgb, #86b556 16%, var(--tl-surface)); }

        /* Phones: chips become dots so a month still fits. */
        @media (max-width: 640px) {
            .cal-day { min-height: 3.4rem; padding: .2rem .15rem; align-items: center; }
            .cal-chip {
                width: .4rem; height: .4rem; padding: 0; border-radius: 999px; border-left: 0;
                background: var(--prio-color, #9ca3af); text-indent: -999em; overflow: hidden;
            }
            /* Dots have no room for icons (text-indent doesn't move SVGs). */
            .cal-chip svg { display: none; }
            .cal-dots { display: flex; flex-wrap: wrap; gap: 2px; justify-content: center; }
            .cal-more { font-size: 9px; }
        }

        .readiness-row { display: flex; gap: .75rem; padding: .7rem .25rem; border-bottom: 1px solid var(--tl-border); }
        .readiness-row:last-child { border-bottom: 0; }
        .readiness-dot {
            width: .55rem; height: .55rem; border-radius: 999px; margin-top: .42rem; flex-shrink: 0;
            background: #f5c518;
        }
        .readiness-row.is-blocking .readiness-dot { background: #ef4444; }
    </style>
@endpush

@section('content')
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    // ---- Effective Day 0 anchor per lot: manual dayZeroDate overridden by
    // the EARLIEST isDayZero activity covering the lot (mother parity).
    $lotDayZeroEff = [];
    foreach ($schedule->lots as $lot) {
        if ($lot->dayZeroDate) {
            $lotDayZeroEff[$lot->id] = Carbon::parse($lot->dayZeroDate);
        }
    }
    foreach ($schedule->activities as $a) {
        if (!$a->isDayZero || !$a->targetDate) continue;
        $aDate = Carbon::parse($a->targetDate);
        foreach ($a->lots as $lot) {
            if (!isset($lotDayZeroEff[$lot->id]) || $aDate->lt($lotDayZeroEff[$lot->id])) {
                $lotDayZeroEff[$lot->id] = $aDate->copy();
            }
        }
    }

    // ---- Effective transplant anchor (DAT 0) per lot: manual transplantDate
    // overridden by the EARLIEST isTransplant activity covering the lot. On/after
    // this date, activities count in DAT (a fresh counter) instead of DAS.
    $lotTransplantEff = [];
    foreach ($schedule->lots as $lot) {
        if ($lot->transplantDate) {
            $lotTransplantEff[$lot->id] = Carbon::parse($lot->transplantDate);
        }
    }
    foreach ($schedule->activities as $a) {
        if (!$a->isTransplant || !$a->targetDate) continue;
        $aDate = Carbon::parse($a->targetDate);
        foreach ($a->lots as $lot) {
            if (!isset($lotTransplantEff[$lot->id]) || $aDate->lt($lotTransplantEff[$lot->id])) {
                $lotTransplantEff[$lot->id] = $aDate->copy();
            }
        }
    }

    // ---- Sort + group activities exactly like the mother setup tab.
    $sortedActivities = $schedule->activities->sortBy(function ($a) {
        $date = $a->targetDate ? Carbon::parse($a->targetDate)->format('Y-m-d') : 'ZZZZ-12-31';
        $seq = str_pad((string) (int) $a->sequenceOrder, 10, '0', STR_PAD_LEFT);
        $lotSig = $a->lots->pluck('id')->sort()->values()->implode(',');
        return $date . '|' . $seq . '|' . $lotSig . '|' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT);
    })->values();
    $byDate = $sortedActivities->groupBy(function ($a) {
        return $a->targetDate ? Carbon::parse($a->targetDate)->format('Y-m-d') : '__no-date__';
    });

    // ---- Rest-day computation: a day is covered when inside ANY [start,end].
    $coveredDays = [];
    $firstDate = null;
    $lastDate = null;
    foreach ($sortedActivities as $a) {
        if (!$a->targetDate) continue;
        $s = Carbon::parse($a->targetDate);
        $e = $a->targetEndDate ? Carbon::parse($a->targetEndDate) : $s->copy();
        for ($d = $s->copy(); $d->lte($e); $d->addDay()) {
            $coveredDays[$d->format('Y-m-d')] = true;
        }
        if (!$firstDate || $s->lt($firstDate)) $firstDate = $s->copy();
        if (!$lastDate || $e->gt($lastDate)) $lastDate = $e->copy();
    }
    $timeline = [];
    $colorCursor = 0;
    if ($firstDate && $lastDate) {
        for ($d = $firstDate->copy(); $d->lte($lastDate); $d->addDay()) {
            $key = $d->format('Y-m-d');
            if (isset($byDate[$key])) {
                $timeline[] = ['type' => 'group', 'date' => $key, 'color' => $colorCursor, 'carbon' => $d->copy()];
                $colorCursor = ($colorCursor + 1) % 8;
            } elseif (!isset($coveredDays[$key])) {
                $timeline[] = ['type' => 'rest', 'date' => $key, 'carbon' => $d->copy()];
            }
        }
    }
    if (isset($byDate['__no-date__'])) {
        $timeline[] = ['type' => 'group', 'date' => '__no-date__', 'color' => 0, 'carbon' => null];
    }

    // ---- Splice progress markers immediately AFTER their date row; orphans last.
    if ($markersByDate->count()) {
        $expanded = [];
        $seenMarkerDates = [];
        foreach ($timeline as $row) {
            $expanded[] = $row;
            $rowDate = $row['date'];
            if ($rowDate !== '__no-date__' && isset($markersByDate[$rowDate])) {
                $expanded[] = ['type' => 'marker', 'date' => $rowDate, 'carbon' => $row['carbon'] ?? Carbon::parse($rowDate), 'marker' => $markersByDate[$rowDate]];
                $seenMarkerDates[$rowDate] = true;
            }
        }
        foreach ($markersByDate as $dateKey => $marker) {
            if (!isset($seenMarkerDates[$dateKey])) {
                $expanded[] = ['type' => 'marker', 'date' => $dateKey, 'carbon' => Carbon::parse($dateKey), 'marker' => $marker];
            }
        }
        $timeline = $expanded;
    }

    $hiddenCount = $sortedActivities->where('isHidden', true)->count();
@endphp

{{-- ===================== TOOLBAR (sticky, persistent) =====================
     The modules hamburger lives here, inline with the activity actions. When
     another module is showing, the activities-only buttons hide. --}}
<div class="sticky top-14 md:top-16 z-20 bg-gray-50 -mx-4 px-4 sm:-mx-6 sm:px-6 py-2 mb-3 border-b border-gray-100">
    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" id="modulesBtn" class="btn btn-white btn-sm" title="Switch module">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            {{-- "Modules - " is dropped on phones so the label stays short and
                 leaves the toolbar room for Tools, Undo and Redo on one line. --}}
            <span id="currentModuleLabel"><span class="dh-modprefix">Modules - </span>Activities</span>
        </button>
        {{-- Right after the module it is leaving, not flung to the far edge:
             pinned right it sat directly under the notification bell, reading
             as part of the app header rather than as this row's own control. --}}
        <button type="button" id="moduleBackBtn" class="btn btn-white btn-sm hidden" title="Back to activities">
            <span>Activities</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        {{-- Tools menu: collapses Drafts / Report / Search / Calendar / Weather
             (and, on phones, undo/redo/show-hidden) into one hamburger, like the
             Modules button. Each row forwards to the real button below. --}}
        <button type="button" id="activityActionsBtn" class="btn btn-white btn-sm relative" data-activities-only title="Tools" aria-label="Tools">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span class="hidden sm:inline">Tools</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <button type="button" id="readinessBtn" class="btn btn-white btn-sm relative {{ $readiness['count'] > 0 ? 'has-alerts' : '' }}"
                title="{{ $readiness['count'] > 0 ? $readiness['count'] . ($readiness['count'] === 1 ? ' thing still needs' : ' things still need') . ' setting up' : 'Everything is set up' }}">
            <span class="readiness-ripple" aria-hidden="true"></span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/></svg>
            <span class="hidden sm:inline">Notice</span>
            <span id="readinessCount"
                  class="absolute -top-0.5 -right-0.5 {{ $readiness['count'] > 0 ? 'inline-flex' : 'hidden' }} min-w-5 h-5 px-1 rounded-full {{ $readiness['blocking'] > 0 ? 'bg-red-500 text-white' : 'bg-accent-500 text-ink' }} text-[10px] font-bold items-center justify-center">{{ $readiness['count'] }}</span>
        </button>

        <button type="button" id="activityUndoBtn" class="btn btn-white btn-sm relative" data-activities-only disabled title="Nothing to undo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg>
            <span class="hidden sm:inline">Undo</span>
            <span id="activityUndoCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[10px] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="activityRedoBtn" class="btn btn-white btn-sm relative" data-activities-only disabled title="Nothing to redo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4"/></svg>
            <span class="hidden sm:inline">Redo</span>
            <span id="activityRedoCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[10px] font-bold items-center justify-center">0</span>
        </button>
        {{-- Calendar view + Add note: quick actions kept in the toolbar, right
             after Redo. Calendar collapses into the Tools menu on phones. --}}
        <button type="button" id="viewToggleBtn" class="btn btn-white btn-sm toolbar-desktop-action" data-activities-only
                title="Switch to calendar view" aria-pressed="false">
            <svg id="viewIconCalendar" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <svg id="viewIconList" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span id="viewToggleLabel">Calendar</span>
        </button>
        <button type="button" id="openNotesBtn" class="btn btn-white btn-sm toolbar-desktop-action" data-activities-only
                title="Open the schedule notebook">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="hidden sm:inline">Notes</span>
        </button>
        @if (\App\Support\ScheduleTeam::hasTeam($schedule))
        <a href="{{ route('sm.collab', ['id' => $schedule->id]) }}" id="collabRoomBtn" data-collab-open class="btn btn-primary btn-sm toolbar-desktop-action" data-activities-only title="Open the team Collab Room">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M5 4v11a2 2 0 002 2h10a2 2 0 002-2V4M8 9h8M8 12h5M12 17v4m-3 0h6"/></svg>
            <span class="hidden sm:inline">Collab Room</span>
        </a>
        @endif
        {{-- Peers of Notes, and reachable the same way. They live only in the
             Tools menu — the toolbar has no room for two more — and the menu
             rows forward their clicks here. --}}
        <button type="button" id="openDrawBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4"/></svg>
            Draw
        </button>
        <button type="button" id="openMapsBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
            Maps
        </button>
        <button type="button" id="openDraftsBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            Drafts <span id="draftsBadge" class="badge badge-gray">{{ $draftsCount }}</span>
        </button>
        {{-- Lives only in the Tools menu; the menu row forwards its click here. --}}
        {{-- Lives only in the Tools menu (like the rest of this row), but the
         menu forwards clicks to real buttons, so it needs to be one. --}}
    <button type="button" id="growthStageBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8"/></svg>
        Growth stage
    </button>
    <button type="button" id="contractAllBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M5 19l7-7 7 7"/></svg>
            Contract All
        </button>
        <button type="button" id="openReportBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9 9 0 1020.945 13H12a1 1 0 01-1-1V3.055zM15 3.936A9.02 9.02 0 0120.064 9H15V3.936z"/></svg>
            Report
        </button>
        <button type="button" id="openSearchBtn" data-sheet-open="filtersSheet" class="btn btn-white btn-sm relative toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            Search
            <span id="activeFilterCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-brand-600 text-white text-[10px] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="weatherBtn" class="btn btn-white btn-sm relative toolbar-in-menu" data-activities-only title="Weather forecast for each lot" aria-label="Weather forecast">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg>
            <span class="hidden sm:inline">Weather</span>
        </button>
    </div>
</div>

{{-- Collab Room member picker (only rendered when the team has other members). --}}
@if (\App\Support\ScheduleTeam::hasTeam($schedule))
    @include('sm.partials.collab-enter-modal', ['schedule' => $schedule])
@endif

{{-- Module host: other modules are fetched as partials and injected here.
     Activities stays in the DOM (hidden) so its listeners survive. --}}
<div id="moduleHost" class="hidden"></div>

{{-- Long-board jump buttons (phones): the toolbar and the version bar sit at
     opposite ends of a list that can be thousands of pixels tall. --}}
<div class="act-jumps md:hidden" data-activities-only>
    {{-- Once the header has scrolled away: add an activity, then the
         jump-to-end pair. Add forwards to the real toolbar button. --}}
    <button type="button" id="actFabAdd" class="act-fab-add" aria-label="Add an activity" title="Add an activity">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    </button>
    <button type="button" id="actJumpTop" aria-label="Jump to the top">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
    </button>
    <button type="button" id="actJumpBottom" aria-label="Jump to the bottom">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
</div>

{{-- Full-surface loader while a module is being fetched --}}
<div id="moduleLoader" class="hidden">
    <div class="card">
        <div class="card-body flex items-center justify-center gap-3 py-16 text-gray-500">
            <svg class="w-6 h-6 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
            <span class="font-semibold" id="moduleLoaderLabel">Loading…</span>
        </div>
    </div>
</div>

{{-- The board arrives before the two things it is read for: what the day
     costs, and what the sky is doing. Both need a round trip, so for a second
     or two the headers were visibly incomplete and then jumped as the pills
     landed. This covers that second — the board is drawn underneath and
     revealed once both have arrived (or given up). --}}
<div id="boardVeil" aria-hidden="true">
    <div class="bv-card">
        <span class="bv-spin" aria-hidden="true"></span>
        <span class="bv-text" id="boardVeilText">Working out the day…</span>
        <span class="bv-sub">costs, then the forecast</span>
    </div>
</div>

<div id="activitiesRoot">
{{-- ============================ VERSIONS STRIP ============================ --}}
{{-- id: the floating jump stack watches this row and only shows once it has
     scrolled away (see activities-js). --}}
<div class="flex items-center gap-1 mb-3" id="actHeaderBar">
    {{-- min-w-0: without it this grow item refuses to shrink below its content
         width (flex min-width:auto), so long version lists would overflow the
         row instead of engaging the strip's own swipe scroll. --}}
    {{-- Phones: the chip strip folds behind one button + bottom sheet. --}}
    <button type="button" id="versionsSheetBtn" class="btn btn-white btn-sm shrink-0 md:hidden" title="Switch or add a plan version">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5"/></svg>
        <span>Versions</span>
    </button>
    <div class="scroll-chips grow min-w-0" id="versionStrip">
        @foreach ($schedule->versions as $v)
            <button type="button"
                class="chip shrink-0 version-chip {{ $v->isActive ? 'is-selected' : '' }}"
                data-chip-manual
                data-version-id="{{ $v->id }}"
                data-version-name="{{ $v->versionName }}"
                data-version-description="{{ $v->description }}"
                data-is-original="{{ $v->isOriginal ? 1 : 0 }}"
                data-is-active="{{ $v->isActive ? 1 : 0 }}"
                title="{{ $v->description ?: $v->versionName }}">
                @if ($v->isOriginal)
                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                @endif
                {{ $v->versionName }}
            </button>
        @endforeach
        <button type="button" id="addVersionBtn" class="chip chip-dashed shrink-0" data-chip-manual>+ Version</button>
    </div>
    <button type="button" id="manageVersionBtn" class="icon-btn shrink-0" title="Rename or delete the current version">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
    </button>
    <button type="button" id="todayTomorrowBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Scroll to today">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-5-5m5 5l5-5M5 20h14"/></svg>
        <span class="hidden sm:inline">Today</span>
    </button>
    {{-- Phones: one eye button owns both day filters (see #viewFilterSheet),
         so the toolbar carries one control instead of two toggles. --}}
    <button type="button" id="viewFilterBtn" class="btn btn-white btn-sm shrink-0 md:hidden" data-activities-only
            title="What the board shows" aria-label="What the board shows">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    </button>
    <button type="button" id="toggleEmptyDatesBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Show or hide the empty &quot;no activities&quot; dates">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <span id="toggleEmptyDatesLabel" class="hidden sm:inline">Hide empty dates</span>
    </button>
    <button type="button" id="toggleHiddenBtn" class="btn btn-white btn-sm shrink-0 toolbar-desktop-action {{ $hiddenCount ? '' : 'hidden' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        <span id="toggleHiddenLabel">Show Hidden ({{ $hiddenCount }})</span>
    </button>
    {{-- Stays visible on phones too (no toolbar-desktop-action), sitting
         right before Add Activity as the one-tap done-days toggle. --}}
    <button type="button" id="toggleDoneDaysBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Hide the days where every activity is already done" aria-pressed="false">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        <span id="toggleDoneDaysLabel" class="hidden sm:inline">Hide done days</span>
    </button>
    <button type="button" id="quickShareBtn" class="btn btn-white btn-sm shrink-0 toolbar-in-menu" data-activities-only
            title="Share this whole plan or email workers">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>
        <span class="hidden sm:inline">Quick Share</span>
    </button>
    <div class="shrink-0" id="addActivityWrap" data-activities-only>
        <button type="button" id="addActivityBtn" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span class="hidden sm:inline">Add Activity</span>
        </button>
    </div>
</div>


{{-- ============================ FILTERS (bottom sheet) ============================ --}}
<div class="sheet hidden" id="filtersSheet" style="--sheet-width: 30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search &amp; filter</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-5">
        <div>
            <label class="text-xs font-semibold text-gray-500">Search</label>
            <div class="relative mt-1.5">
                <input type="search" id="activitySearchInput" class="form-input pr-16!" placeholder="Title, lots, workers, items…">
                <span id="activitySearchCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></span>
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-gray-500">Activity type</label>
            <div class="flex flex-wrap gap-1.5 mt-1.5" id="typeFilterChips" data-chip-group>
                @foreach ($activityTypes as $slug => $label)
                    <button type="button" class="chip min-h-9! py-1! text-xs" data-value="{{ $slug }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($schedule->lots->count())
            <div>
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold text-gray-500">Hide lots</label>
                    <div class="flex items-center gap-3">
                        <button type="button" id="lotFilterAllBtn" class="text-xs font-semibold text-brand-700">Hide all</button>
                        <button type="button" id="lotFilterClearBtn" class="text-xs font-semibold text-brand-700 hidden">Show all</button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-1.5" id="lotFilterChips" data-chip-group>
                    @foreach ($schedule->lots as $lot)
                        <button type="button" class="chip min-h-9! py-1! text-xs" data-value="{{ $lot->id }}"
                            title="Hide {{ $lot->lotName }} — cards covering another visible lot stay put">
                            {{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif
                        </button>
                    @endforeach
                    <button type="button" class="chip chip-dashed min-h-9! py-1! text-xs" data-value="__na__"
                        title="Hide activities not tied to any specific lot">N/A</button>
                </div>
            </div>
        @endif

    </div>
    <div class="sheet-footer">
        <button type="button" id="clearFiltersBtn" class="btn btn-ghost">Clear filters</button>
        <button type="button" data-sheet-close class="btn btn-primary">Done</button>
    </div>
</div>

{{-- ============================ CALENDAR ============================
     A month view of exactly what the list is showing. Built from the list's
     own cards, so filters, edits, drags and version switches carry over
     without a second copy of the data. --}}
<div id="calendarRoot" class="hidden">
    <div class="cal-head">
        <button type="button" class="icon-btn" id="calPrev" aria-label="Previous month">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="grow text-center min-w-0">
            <p class="font-bold text-gray-900 leading-tight" id="calMonthLabel"></p>
            <p class="text-xs text-gray-500" id="calMonthMeta"></p>
        </div>
        <button type="button" class="icon-btn" id="calNext" aria-label="Next month">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" class="btn btn-white btn-sm shrink-0" id="calToday">Today</button>
    </div>

    <div class="cal-grid-head">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
            <span>{{ $d }}</span>
        @endforeach
    </div>
    <div class="cal-grid" id="calGrid"></div>

    <div class="card p-8 text-center hidden" id="calEmpty">
        <p class="font-semibold text-gray-700">Nothing scheduled here</p>
        <p class="text-sm text-gray-500 mt-1" id="calEmptyHint">Use the arrows to find the months with work in them.</p>
    </div>
</div>

{{-- ============================ TIMELINE ============================ --}}
{{-- Only ever seen with "day-zero only" on and nothing to show for it:
     without this the board would just look broken. --}}
<div id="dayZeroNone" class="card card-body text-center text-gray-500 py-8 hidden">
    <p class="font-bold text-gray-800 mb-1">No day-zero activity yet</p>
    <p class="text-sm">Nothing on this plan is marked as the DAS 0 / DAP 0 / DAT 0 anchor. Tick “this is day zero” on the activity that starts the count.</p>
</div>

<div id="activitiesList" class="activity-timeline">
    @if ($sortedActivities->count() === 0)
        <div id="activitiesEmpty" class="card card-body text-center text-gray-500 py-10">
            <p class="font-bold text-gray-800 mb-1">No activities defined yet.</p>
            <p class="text-sm">Tap <strong>Add Activity</strong> to define your first step.</p>
        </div>
    @else
        @foreach ($timeline as $item)
            @if ($item['type'] === 'rest')
                <div class="rest-day-marker" data-date="{{ $item['date'] }}">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <div class="grow min-w-0">
                        <span class="rest-day-date">{{ $item['carbon']->format('l, F j, Y') }}</span>
                        <span class="rest-day-tag">No activities scheduled</span>
                    </div>
                    <button type="button" class="btn btn-white btn-sm rest-day-add-btn shrink-0" data-date="{{ $item['date'] }}">+ Add</button>
                </div>
            @elseif ($item['type'] === 'marker')
                @php $marker = $item['marker']; @endphp
                <div class="progress-marker" data-marker-id="{{ $marker->id }}" data-date="{{ $item['date'] }}" draggable="true" title="Drag to move this marker to another day">
                    <div class="progress-marker-line">
                        <span class="progress-marker-bookmark">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                            Resume here — {{ $item['carbon']->format('M j, Y') }}
                        </span>
                        <span class="flex items-center gap-0.5">
                            <button type="button" class="icon-btn progress-marker-edit-btn" data-date="{{ $item['date'] }}" title="Edit marker note">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button type="button" class="icon-btn icon-btn-danger progress-marker-delete-btn" data-marker-id="{{ $marker->id }}" data-date="{{ $item['date'] }}" title="Remove marker">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </span>
                    </div>
                    @if ($marker->noteContent)
                        <div class="progress-marker-note">{{ $marker->noteContent }}</div>
                    @endif
                </div>
            @else
                @php
                    $dateKey = $item['date'];
                    $activitiesForDate = $byDate->get($dateKey);
                    $dateCarbon = $dateKey !== '__no-date__' ? Carbon::parse($dateKey) : null;
                    $latestEndCarbon = null;
                    if ($dateCarbon) {
                        foreach ($activitiesForDate as $_act) {
                            $_e = $_act->targetEndDate ? Carbon::parse($_act->targetEndDate) : null;
                            if ($_e && $_e->greaterThan($dateCarbon) && (!$latestEndCarbon || $_e->greaterThan($latestEndCarbon))) {
                                $latestEndCarbon = $_e->copy();
                            }
                        }
                    }
                    $groupSpanDays = $latestEndCarbon ? ($dateCarbon->diffInDays($latestEndCarbon) + 1) : 0;
                    $allHidden = $dateCarbon
                        && $activitiesForDate->isNotEmpty()
                        && $activitiesForDate->every(fn ($_a) => (bool) $_a->isHidden);
                    $noteRow = $dateKey !== '__no-date__' ? $dateNotesByDate->get($dateKey) : null;
                    $existingMarker = $dateKey !== '__no-date__' ? $markersByDate->get($dateKey) : null;
                @endphp
                @if ($allHidden)
                    <div class="rest-day-marker rest-day-substitute" data-date="{{ $dateKey }}">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <div class="grow min-w-0">
                            <span class="rest-day-date">{{ $dateCarbon->format('l, F j, Y') }}</span>
                            <span class="rest-day-tag">No activities scheduled</span>
                        </div>
                        <button type="button" class="btn btn-white btn-sm rest-day-add-btn shrink-0" data-date="{{ $dateKey }}">+ Add</button>
                    </div>
                @endif
                <div class="date-group date-color-{{ $item['color'] }} {{ $allHidden ? 'all-hidden' : '' }} is-folded" data-date="{{ $dateKey }}">
                    <div class="date-header"@if ($dateCarbon) draggable="true" title="Drag this header to move the whole day to another date"@endif>
                        <svg class="date-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        @if ($dateCarbon)
                            <span class="date-header-day">{{ $dateCarbon->format('D') }}</span>
                            {{-- Twin of the JS renderer: every spelling, CSS picks one.
                                 dh-rangeshort folds a multi-day group into a single
                                 "Sep 25–28, 26" for phones, replacing the start date
                                 plus the arrow badge that repeats it. --}}
                            @php
                                $sameMonth = $latestEndCarbon
                                    && $latestEndCarbon->month === $dateCarbon->month
                                    && $latestEndCarbon->year === $dateCarbon->year;
                                $rangeShort = $latestEndCarbon
                                    ? ($sameMonth
                                        ? $dateCarbon->format('M j') . '–' . $latestEndCarbon->format('j') . ', ' . $dateCarbon->format('y')
                                        : $dateCarbon->format('M j') . ' – ' . $latestEndCarbon->format('M j') . ', ' . $dateCarbon->format('y'))
                                    : null;
                            @endphp
                            <span class="date-header-date{{ $rangeShort ? ' has-range' : '' }}"><span class="dh-long">{{ $dateCarbon->format('M j, Y') }}</span><span class="dh-short">{{ $dateCarbon->format('M j, y') }}</span>@if($rangeShort)<span class="dh-rangeshort">{{ $rangeShort }}</span>@endif</span>
                            @if ($latestEndCarbon)
                                <span class="date-header-range" title="At least one activity extends through {{ $latestEndCarbon->format('M j, Y') }}">
                                    &rarr; {{ $latestEndCarbon->format('M j') }}@if($latestEndCarbon->year !== $dateCarbon->year), {{ $latestEndCarbon->year }}@endif ({{ $groupSpanDays }}d)
                                </span>
                            @endif
                        @else
                            <span class="date-header-date">No date</span>
                        @endif
                        <span class="date-header-count">{{ $activitiesForDate->count() }}<span class="dh-word"> {{ Str::plural('activity', $activitiesForDate->count()) }}</span></span>
                        {{-- Filled in by paintDayCash() once the board is up:
                             the figure comes off the cards themselves, so it
                             cannot drift from the lines under them. --}}
                        {{-- What the crop is doing today, before what the day
                             costs: the plant's business comes before the
                             wallet's, and one explains the other. --}}
                        <span class="date-header-stage" hidden title="What the crop is doing on this day"></span>
                        <span class="date-header-cash" hidden></span>
                        @if ($dateKey !== '__no-date__')
                            <button type="button" class="date-header-btn group-add-activity-btn" data-date="{{ $dateKey }}" title="Add a new activity to this date">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </button>
                            {{-- Secondary day actions: inline on desktop, overflow sheet on phones. --}}
                            <span class="hidden md:flex items-center gap-0.5">
                                <button type="button" class="date-header-btn date-note-btn" data-date="{{ $dateKey }}" title="Add a note to this day">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 20H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 8h3M9 12h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 15v5m2.5-2.5h-5"/></svg>
                                </button>
                                <button type="button" class="date-header-btn day-expense-btn" data-date="{{ $dateKey }}" title="Add an extra expense for this day">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M14.4 9.4a2.3 2.3 0 00-2.4-1.3c-1.3.1-2.3.8-2.3 1.9s1 1.7 2.5 1.9 2.6.8 2.6 2-1.1 1.9-2.5 1.9a2.4 2.4 0 01-2.4-1.3"/></svg>
                                </button>
                                <button type="button" class="date-header-btn date-marker-btn {{ $existingMarker ? 'has-marker' : '' }}" data-date="{{ $dateKey }}" title="{{ $existingMarker ? 'Edit the resume-here marker' : 'Drop a resume-here marker after this date' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn share-day-btn" data-date="{{ $dateKey }}" title="Share this day's schedule (public link)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn change-group-date-btn" data-date="{{ $dateKey }}" title="Change date for all activities in this group">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn move-group-das-btn" data-date="{{ $dateKey }}" title="Move this whole day to a specific day number">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn date-header-delete-btn delete-group-date-btn" data-date="{{ $dateKey }}" title="Delete every activity in this group">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </span>
                            <button type="button" class="date-header-btn day-menu-btn md:hidden" data-date="{{ $dateKey }}" title="More actions for this day">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                            </button>
                        @endif
                    </div>
                    <div class="date-body"><div class="date-body-inner">
                    @if ($dateKey !== '__no-date__')
                        @php
                            $dnMedia = collect(is_array($noteRow?->media) ? $noteRow->media : [])
                                ->map(fn ($m) => empty($m['path']) ? null : [
                                    'type' => $m['type'] ?? 'image',
                                    'path' => $m['path'],
                                    'url' => \App\Support\MediaStore::url($m['path']),
                                    'poster' => $m['poster'] ?? null,
                                    'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
                                ])->filter()->values();
                        @endphp
                        <div class="date-note-block" data-date="{{ $dateKey }}" data-content="{{ $noteRow?->noteContent }}" data-media="{{ $dnMedia->toJson() }}" title="Drag to place it between activities · click to edit" @if(!$noteRow) style="display:none;" @endif><div class="date-note-inner rich-text">{!! $noteRow?->noteContent !!}@if ($dnMedia->count())<div class="date-note-media">@include('sm.partials.note-media', ['media' => $dnMedia])</div>@endif</div><button type="button" class="date-note-edit" title="Edit note" aria-label="Edit note"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button><button type="button" class="date-note-del" title="Delete note" aria-label="Delete note"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg></button></div>
                        <div class="day-expense-block" data-date="{{ $dateKey }}"></div>
                        <div class="day-income-block" data-date="{{ $dateKey }}" hidden></div>
                    @endif
                    @php
                        // Interleave positioned inline notes with the day's cards by order.
                        $dayInlineNotes = $dateKey !== '__no-date__' ? ($inlineNotesByDate[$dateKey] ?? collect()) : collect();
                        $dayItems = collect();
                        foreach ($activitiesForDate as $a) { $dayItems->push(['order' => (int) $a->sequenceOrder, 'kind' => 'card', 'a' => $a]); }
                        foreach ($dayInlineNotes as $nn) { $dayItems->push(['order' => (int) $nn->sortKey, 'kind' => 'note', 'note' => $nn]); }
                        $dayItems = $dayItems->sortBy(fn ($it) => sprintf('%08d%d', $it['order'] + 1000000, $it['kind'] === 'card' ? 0 : 1))->values();
                    @endphp
                    <div class="date-activities" data-date="{{ $dateKey }}">
                        @foreach ($dayItems as $it)
                            @if ($it['kind'] === 'card')
                                @include('sm.partials.activity-card', ['a' => $it['a'], 'schedule' => $schedule, 'activityTypes' => $activityTypes, 'lotDayZeroEff' => $lotDayZeroEff, 'lotTransplantEff' => $lotTransplantEff])
                            @else
                                @include('sm.partials.inline-note', ['note' => $it['note']])
                            @endif
                        @endforeach
                    </div>
                    </div></div>
                </div>
            @endif
        @endforeach
    @endif
</div>

</div>{{-- /#activitiesRoot --}}

@include('sm.partials.ai-float', ['schedule' => $schedule])
{{-- Team chat + whiteboard now live in the Collab Room (Collab Room button). --}}
@endsection

@push('sheets')
@include('sm.partials.activities-sheets', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
])
{{-- Same panels the Weather module shows, so the sheet and the module
     cannot drift apart. --}}
@include('sm.partials.weather-panels')
<div class="sheet hidden" id="weatherSheet" style="--sheet-width:38rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Weather by lot</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="weatherBody">
        <div class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50/60 px-3 py-4">
            <span class="wx-cloud text-2xl leading-none" aria-hidden="true">☁️</span>
            <span class="text-xs font-semibold text-gray-500">Loading weather forecast…</span>
        </div>
    </div>
</div>

{{-- Agronomic reminders for a single day (spray overload, same-lot double-up,
     granular-on-granular, spraying before forecast rain). Populated by JS. --}}
<div class="sheet hidden" id="dayWarnSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                Things to double-check
            </h3>
            <p class="text-xs text-gray-500 mt-0.5" id="dayWarnSubtitle"></p>
        </div>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div id="dayWarnBody" class="space-y-2.5"></div>
        <div class="mt-4 flex justify-end">
            <button type="button" id="dayWarnMarkAll" class="btn btn-white btn-sm">Mark all as read</button>
        </div>
    </div>
</div>
@include('sm.partials.draw-canvas')
@include('sm.partials.note-editor')
@include('sm.partials.note-lightbox')
@include('community.partials.video-js')
@endpush

@push('scripts')
<script>
/* ======================================================================
 * Schedule shell — swaps modules in place instead of loading a new page.
 * Each module is fetched once as a partial, injected, and then cached in
 * the DOM (hidden) so returning to it is instant and its event listeners
 * are never bound twice.
 * ==================================================================== */
(() => {
    const SCHEDULE_ID = {{ $schedule->id }};
    // Public share links (unguessable token) — read by the activity share sheet.
    window.SM_SHARE = {
        token: @json($schedule->shareToken),
        scheduleUrl: @json(route('share.schedule', $schedule->shareToken)),
        title: @json($schedule->title),
        scheduleId: {{ $schedule->id }},
        emailUrl: @json(route('sm.quick-share.email')),
    };
    const MODULES = {
        activities:    { label: 'Activities',    url: @json(route('sm.activities',    ['id' => $schedule->id])) },
        settings:      { label: 'Settings',      url: @json(route('sm.settings',      ['id' => $schedule->id])) },
        lots:          { label: 'Lots',          url: @json(route('sm.lots',          ['id' => $schedule->id])) },
        workers:       { label: 'Workers',       url: @json(route('sm.workers',       ['id' => $schedule->id])) },
        documentation: { label: 'Documentation', url: @json(route('sm.documentation', ['id' => $schedule->id])) },
        'post-harvest': { label: 'Post-harvest', url: @json(route('sm.post-harvest',  ['id' => $schedule->id])) },
        notes:         { label: 'Notes',         url: @json(route('sm.notes',        ['id' => $schedule->id])) },
        maps:          { label: 'Maps',          url: @json(route('sm.maps',         ['id' => $schedule->id])) },
        draw:          { label: 'Draw',          url: @json(route('sm.draw',         ['id' => $schedule->id])) },
        media:         { label: 'Media Box',     url: @json(route('sm.media',        ['id' => $schedule->id])) },
        growth:        { label: 'Growth Stages', url: @json(route('sm.growth',       ['id' => $schedule->id])) },
        gallery:       { label: 'Gallery',       url: @json(route('sm.gallery',      ['id' => $schedule->id])) },
        weather:       { label: 'Weather',       url: @json(route('sm.weather.page', ['id' => $schedule->id])) },
        ai:            { label: 'AI Technician', url: @json(route('sm.ai',           ['id' => $schedule->id])) },
    };

    // The address bar always stays on the Activities shell (…/sm-activities?id=X
    // with ?module=key for non-Activities modules) so a refresh reloads the
    // single-page shell, never the standalone module page. The partial fetch
    // still uses each module's own URL (see showModule).
    const ACTIVITIES_URL = MODULES.activities.url;
    const shellUrl = (key) => key === 'activities'
        ? ACTIVITIES_URL
        : ACTIVITIES_URL + (ACTIVITIES_URL.includes('?') ? '&' : '?') + 'module=' + key;

    const host = document.getElementById('moduleHost');
    const activitiesRoot = document.getElementById('activitiesRoot');
    const loader = document.getElementById('moduleLoader');
    const loaderLabel = document.getElementById('moduleLoaderLabel');
    const label = document.getElementById('currentModuleLabel');
    const loaded = new Map();           // key -> injected wrapper element
    let current = 'activities';
    let busy = false;
    let activitiesScrollY = 0;   // remembered scroll position for Activities

    const setActivitiesChrome = (on) => {
        document.querySelectorAll('[data-activities-only]').forEach((el) => {
            if (window.animToggleHidden) window.animToggleHidden(el, !on, 'module-hidden');
            else el.classList.toggle('module-hidden', !on);
        });
    };

    /** innerHTML never executes <script>; re-create them so module JS runs. */
    const runScripts = (root) => {
        root.querySelectorAll('script').forEach((old) => {
            const s = document.createElement('script');
            [...old.attributes].forEach((a) => s.setAttribute(a.name, a.value));
            s.textContent = old.textContent;
            old.replaceWith(s);
        });
    };

    // How many module entries this page has pushed — what the chevron unwinds.
    let pushDepth = 0;

    /** Is the module the shell thinks is open actually the one on screen? */
    function isShowing(key) {
        const el = key === 'activities' ? activitiesRoot : loaded.get(key);
        return !!el && !el.classList.contains('module-hidden')
            && (key === 'activities' || !host.classList.contains('hidden'));
    }

    /**
     * Close the modules sheet on the way out, without letting it rewind
     * history. The sheet owns a history entry so Back can close it; rewinding
     * that while we are about to push the new module's own entry means the
     * pop lands after the push, and the shell obediently returns to the module
     * we just left. That is the "clicking Maps leaves me on Lots" fault.
     */
    function closeModulesSheetForNav() {
        window.forgetOverlay?.('sheet:modulesSheet');
        closeSheet('modulesSheet');
    }

    /**
     * @param {string} key    which module
     * @param {boolean} push  add a history entry
     * @param {string} extra  query string the module itself understands — the
     *                        drawing to open, the saved map to load. A module
     *                        asked for with one is always re-fetched: a pane
     *                        cached from an earlier visit knows nothing about
     *                        the thing you just tapped.
     */
    async function showModule(key, push = true, extra = '') {
        if (!MODULES[key]) { closeModulesSheetForNav(); return; }
        if (extra) {
            const had = loaded.get(key);
            if (had) { had.remove(); loaded.delete(key); }
            if (key === current) current = null;
        }
        // Only refuse to re-open a module that is genuinely showing. The flag
        // and the screen can come apart — a popstate that names a module, a
        // load that ended badly — and when they do, refusing on the flag alone
        // leaves a button that does nothing for the rest of the session.
        if (key === current && isShowing(key)) { closeModulesSheetForNav(); return; }
        if (busy) { closeModulesSheetForNav(); return; }
        busy = true;
        closeModulesSheetForNav();

        try {
        // Remember where you were in Activities so returning restores it.
        if (current === 'activities') activitiesScrollY = window.scrollY || window.pageYOffset || 0;

        // Hide whatever is showing.
        activitiesRoot.classList.add('module-hidden');
        loaded.forEach((el) => el.classList.add('module-hidden'));

        if (key === 'activities') {
            activitiesRoot.classList.remove('module-hidden');
            host.classList.add('hidden');
        } else if (loaded.has(key)) {
            host.classList.remove('hidden');
            const el = loaded.get(key);
            el.classList.remove('module-hidden');
            // A kept module that will not come back — its node detached by a
            // re-render, or emptied by a script that ran badly — must not leave
            // the button dead. Forget it and fetch it again, which is what a
            // first visit does and is known to work.
            if (!el.isConnected || !el.innerHTML.trim()) {
                loaded.delete(key);
                el.remove();
                busy = false;
                return showModule(key, push);
            }
        } else {
            loaderLabel.textContent = 'Loading ' + MODULES[key].label + '…';
            loader.classList.remove('hidden');
            host.classList.add('hidden');
            try {
                const sep = MODULES[key].url.includes('?') ? '&' : '?';
                const res = await fetch(MODULES[key].url + sep + 'partial=1' + (extra ? '&' + extra : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Could not load ' + MODULES[key].label);
                const wrap = document.createElement('div');
                wrap.dataset.module = key;
                wrap.innerHTML = await res.text();
                host.appendChild(wrap);
                loaded.set(key, wrap);
                runScripts(wrap);
                host.classList.remove('hidden');
            } catch (err) {
                toast(err.message || 'Could not load that module.', 'error');
                activitiesRoot.classList.remove('module-hidden');
                key = 'activities';
            } finally {
                loader.classList.add('hidden');
            }
        }

        current = key;
        // Gentle fade-in on the module that just became visible.
        const shownEl = key === 'activities' ? activitiesRoot : (loaded.get(key) || host);
        if (shownEl) {
            shownEl.classList.remove('sm-view-in');
            void shownEl.offsetWidth;
            shownEl.classList.add('sm-view-in');
            shownEl.addEventListener('animationend', () => shownEl.classList.remove('sm-view-in'), { once: true });
        }
        // Believe the screen, not the bookkeeping: if the module still is not
        // showing, the kept copy is no good — drop it and load it fresh.
        if (key !== 'activities' && !isShowing(key) && loaded.has(key)) {
            loaded.get(key)?.remove();
            loaded.delete(key);
            current = null;
            busy = false;
            return showModule(key, push);
        }

        // Keep the prefix in its own span so CSS can drop it on phones; using
        // textContent here would flatten it away on the first module switch.
        label.innerHTML = '<span class="dh-modprefix">Modules - </span>' + escapeHtml(MODULES[key].label);
        // Keep the app header + browser tab in step with the swapped module.
        const pageTitle = document.getElementById('appPageTitle');
        if (pageTitle) pageTitle.textContent = MODULES[key].label;
        // The guide follows the module: the shell swaps content without a
        // reload, and a help button still pointing at Activities would explain
        // the wrong screen.
        window.smHelpKey?.(key);
        document.title = MODULES[key].label + ' — ' + @json($schedule->title);
        setActivitiesChrome(key === 'activities');
        // Show a "back to Activities" button whenever another module is open.
        document.getElementById('moduleBackBtn')?.classList.toggle('hidden', key === 'activities');
        // The AI module IS the technician chat — hide the floating one there.
        document.getElementById('aiFloat')?.classList.toggle('ai-float-off', key === 'ai');
        // On phones the fab hides only while the ACTIVITIES module is showing
        // (its Tools menu takes over). The other modules keep the bubble —
        // their Tools hamburger is hidden with the rest of the activities
        // chrome, so without the fab they would have no way into the chat.
        document.body.classList.toggle('act-module-open', key === 'activities');
        document.querySelectorAll('#modulesSheet .module-nav-row').forEach((row) => {
            row.querySelector('.module-nav-check')?.classList.toggle('hidden', row.dataset.module !== key);
        });
        if (push) { history.pushState({ module: key }, '', shellUrl(key)); pushDepth++; }
        // Returning to Activities restores your prior scroll; other modules start at the top.
        if (key === 'activities') {
            const y = activitiesScrollY;
            requestAnimationFrame(() => window.scrollTo({ top: y, behavior: 'auto' }));
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        // Leaving a module usually means something was just added or removed.
        window.smRefreshReadiness?.();
        } finally {
            // Whatever went wrong in there, the shell must not be left refusing
            // every switch that comes after it.
            busy = false;
        }
    }

    document.getElementById('modulesBtn')?.addEventListener('click', () => openSheet('modulesSheet'));
    document.addEventListener('click', (e) => {
        const row = e.target.closest('#modulesSheet .module-nav-row');
        if (row) { showModule(row.dataset.module); return; }
        // Module chip-nav links inside an injected partial stay in the shell.
        const link = e.target.closest('#moduleHost a[href]');
        if (link) {
            const hit = Object.keys(MODULES).find((k) => link.href.split('?')[0] === MODULES[k].url.split('?')[0]);
            if (hit) {
                e.preventDefault();
                // "View map" and "Open drawing" name the one they mean in the
                // query; dropping it landed you in the module's front door
                // instead of on the thing you tapped.
                const q = (link.href.split('?')[1] || '')
                    .split('&').filter((kv) => !/^(id|partial)=/.test(kv)).join('&');
                showModule(hit, true, q);
            }
        }
    });

    window.addEventListener('popstate', (e) => {
        // An overlay's own entry says nothing about which module is open, and
        // reading it as "no module named, so Activities" is how closing a
        // sheet could throw you out of the module you were working in.
        if (e.state && e.state.__overlay) return;
        // A switch already under way owns the outcome; a pop that arrives
        // mid-flight is older news than what the reader just asked for.
        if (busy) return;
        if (pushDepth > 0) pushDepth--;
        showModule((e.state && e.state.module) || 'activities', false);
    });

    /* The header chevron and the phone's Back button disagreed: Back walked
       module by module, the chevron jumped straight out to the hub. It now
       unwinds the same stack — one module at a time while there is one to
       unwind, and only then out to wherever the page says it came from
       (which, for a hub tile that deep-linked into a module, is the hub). */
    document.getElementById('appBackLink')?.addEventListener('click', (e) => {
        if (pushDepth > 0) { e.preventDefault(); history.back(); }
    });
    window.smShowModule = showModule;

    // Toolbar "Notes" button opens the schedule notebook module (distinct from
    // the per-date notes in the timeline).
    document.getElementById('openNotesBtn')?.addEventListener('click', () => showModule('notes'));
    document.getElementById('openDrawBtn')?.addEventListener('click', () => showModule('draw'));
    document.getElementById('openMapsBtn')?.addEventListener('click', () => showModule('maps'));
    // "Back to Activities" from any open module.
    document.getElementById('moduleBackBtn')?.addEventListener('click', () => showModule('activities'));

    // Deep link: the hub tiles open this shell with ?module=<key>, so the module
    // loads here (with the hamburger) instead of as its own cut-off page. The
    // URL stays on the shell, so Back returns to the hub and a refresh reloads
    // the shell rather than the standalone page.
    // Deferred to DOMContentLoaded: showModule uses helpers (closeSheet, toast)
    // that the deferred app.js defines, which has not run during this inline
    // script but has by DOMContentLoaded.
    const applyDeepLink = () => {
        const wanted = new URLSearchParams(location.search).get('module');
        if (wanted && MODULES[wanted] && wanted !== 'activities') {
            history.replaceState({ module: wanted }, '', shellUrl(wanted));
            // module-booting kept the server-painted board out of sight; lift
            // it only once the module has actually landed, so Activities never
            // shows on the way in. setActivitiesChrome has re-hidden the
            // chrome with module-hidden by then, so nothing pops back.
            Promise.resolve(showModule(wanted, false))
                .finally(() => document.body.classList.remove('module-booting'));
        } else {
            // Unknown or absent key: this IS the Activities view — unhide it.
            document.body.classList.remove('module-booting');
            history.replaceState({ module: 'activities' }, '', shellUrl('activities'));
        }
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyDeepLink, { once: true });
    } else {
        applyDeepLink();
    }

    /* ---- Phone activity-tools menu -------------------------------------
     * One hamburger collapses the toolbar actions. Each row forwards to the
     * real (desktop-only) button, so there is a single set of handlers. The
     * rows mirror the real buttons' badges, labels and disabled/visible state
     * every time the sheet opens. */
    (() => {
        const actionsBtn = document.getElementById('activityActionsBtn');
        if (!actionsBtn) return;

        // real button id -> sheet badge/label to mirror
        const mirrorBadge = (btnId, countEl, srcId) => {
            const badge = document.getElementById(countEl);
            const src = document.getElementById(srcId);
            if (!badge || !src) return;
            const n = (src.textContent || '').trim();
            const show = n && n !== '0' && !src.classList.contains('hidden');
            badge.textContent = n || '0';
            // Inline style: `.badge` is unlayered CSS and beats a `.hidden` class.
            badge.style.display = show ? '' : 'none';
        };

        function syncActionsSheet() {
            // Drafts / Search filter counts.
            mirrorBadge('openDraftsBtn', 'actDraftsBadge', 'draftsBadge');
            mirrorBadge('openSearchBtn', 'actFilterBadge', 'activeFilterCount');

            // Calendar / List label mirrors the real toggle.
            const viewLabel = document.getElementById('actViewLabel');
            const realView = document.getElementById('viewToggleLabel');
            if (viewLabel && realView) viewLabel.textContent = realView.textContent;

            // Show/Hide Hidden: mirror label, hide the row when nothing is hidden.
            const hiddenRow = document.querySelector('.activity-action-row[data-forward="toggleHiddenBtn"]');
            const hiddenBtn = document.getElementById('toggleHiddenBtn');
            const hiddenLabel = document.getElementById('actHiddenLabel');
            if (hiddenRow && hiddenBtn) hiddenRow.classList.toggle('hidden', hiddenBtn.classList.contains('hidden'));
            if (hiddenLabel) hiddenLabel.textContent = (document.getElementById('toggleHiddenLabel')?.textContent || 'Show Hidden').replace(/\s*\(\d+\)\s*$/, '');

        }

        actionsBtn.addEventListener('click', () => { syncActionsSheet(); openSheet('activityActionsSheet'); });
        // Keep the hamburger dot honest as things change underneath.
        document.addEventListener('activities:rendered', syncActionsSheet);
        syncActionsSheet();

        document.addEventListener('click', (e) => {
            const row = e.target.closest('.activity-action-row');
            if (!row || row.disabled) return;
            const target = document.getElementById(row.dataset.forward);
            if (!target) return;
            closeSheet('activityActionsSheet');
            // Let the sheet finish closing before the next action fires.
            setTimeout(() => target.click(), 240);
        });
    })();

    /* ---- Readiness bell -------------------------------------------------
     * Flags what the plan is still missing (no day 0, no lots, activities
     * with no date...). Rippling while anything is outstanding; each row
     * jumps straight to the module that fixes it. */

    const readinessBtn = document.getElementById('readinessBtn');
    const readinessCount = document.getElementById('readinessCount');
    let READINESS = @json($readiness);

    const esc = (s) => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    // The Notice can be "dismissed" so it stops pulsing without disappearing.
    // We store a signature of the current items; if that set later changes, the
    // mute no longer matches and the Notice starts blinking again on its own.
    const READINESS_MUTE_KEY = 'readinessMuted:' + @json($schedule->id);
    function readinessSignature() {
        const items = (READINESS.items || [])
            .map((it) => (it.module || '') + ':' + (it.label || '')).sort().join('|');
        return (READINESS.count || 0) + '/' + (READINESS.blocking || 0) + '/' + items;
    }
    function isReadinessMuted() {
        try { return localStorage.getItem(READINESS_MUTE_KEY) === readinessSignature(); }
        catch (_) { return false; }
    }
    function setReadinessMuted(on) {
        try {
            if (on) localStorage.setItem(READINESS_MUTE_KEY, readinessSignature());
            else localStorage.removeItem(READINESS_MUTE_KEY);
        } catch (_) { /* private mode — just skip persistence */ }
        paintReadiness();
    }

    function paintReadiness() {
        if (!readinessBtn) return;
        const n = READINESS.count || 0;
        const blocking = READINESS.blocking || 0;
        const muted = isReadinessMuted();
        // Muted keeps the button + count but drops the pulsing ring and nudge.
        readinessBtn.classList.toggle('has-alerts', n > 0 && !muted);
        readinessBtn.classList.toggle('has-blocking', blocking > 0);
        readinessBtn.title = n > 0
            ? n + (n === 1 ? ' thing still needs' : ' things still need') + ' setting up'
            : 'Everything is set up';
        readinessCount.textContent = n;
        readinessCount.classList.toggle('hidden', n === 0);
        readinessCount.classList.toggle('inline-flex', n > 0);
        readinessCount.className = readinessCount.className
            .replace(/bg-(red-500|accent-500)|text-(white|ink)/g, '').trim()
            + (blocking > 0 ? ' bg-red-500 text-white' : ' bg-accent-500 text-ink');

        const list = document.getElementById('readinessList');
        const clear = document.getElementById('readinessAllClear');
        const intro = document.getElementById('readinessIntro');
        if (!list) return;

        list.classList.toggle('hidden', n === 0);
        clear.classList.toggle('hidden', n > 0);
        intro.classList.toggle('hidden', n === 0);
        intro.textContent = blocking > 0
            ? 'The first few stop the plan from working properly — the rest are worth doing when you get a chance.'
            : 'None of these stop the plan from working, but they will make it more useful.';

        list.innerHTML = (READINESS.items || []).map((it) => `
            <button type="button" class="readiness-row w-full text-left hover:bg-gray-50 rounded-lg ${it.severity === 'blocking' ? 'is-blocking' : ''}"
                    data-readiness-module="${esc(it.module)}">
                <span class="readiness-dot"></span>
                <span class="min-w-0 flex-1">
                    <span class="block font-bold text-gray-900 text-sm">${esc(it.label)}</span>
                    <span class="block text-xs text-gray-500 mt-0.5">${esc(it.detail)}</span>
                </span>
                <svg class="w-4 h-4 text-gray-300 shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`).join('');

        const muteBar = document.getElementById('readinessMuteBar');
        const muteHint = document.getElementById('readinessMuteHint');
        const muteBtn = document.getElementById('readinessMuteBtn');
        if (muteBar && muteHint && muteBtn) {
            muteBar.classList.toggle('hidden', n === 0);
            muteBar.classList.toggle('flex', n > 0);
            muteHint.textContent = muted
                ? 'Reminder paused — the Notice stays, but won’t blink.'
                : 'The Notice keeps blinking until these are set up.';
            muteBtn.textContent = muted ? 'Turn reminder back on' : 'Stop the blinking';
        }
    }

    async function refreshReadiness() {
        try {
            const res = await fetch(@json(route('sm.activities.readiness', ['id' => $schedule->id])), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (json && json.success) { READINESS = json.data; paintReadiness(); }
        } catch (_) { /* a stale badge is better than an error toast */ }
    }

    readinessBtn?.addEventListener('click', () => { openSheet('readinessSheet'); refreshReadiness(); });
    document.getElementById('readinessMuteBtn')?.addEventListener('click', () => setReadinessMuted(!isReadinessMuted()));
    document.addEventListener('click', (e) => {
        const row = e.target.closest('[data-readiness-module]');
        if (!row) return;
        closeSheet('readinessSheet');
        setTimeout(() => showModule(row.dataset.readinessModule), 240);
    });

    // Anything that changes the plan can move the needle, so re-check after
    // module edits and when the user comes back to the tab.
    window.smRefreshReadiness = refreshReadiness;
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshReadiness(); });
    paintReadiness();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>
@php
    // Built in a raw-PHP block (not inside @json's argument) so Blade's
    // directive parser never sees the array-literal brackets.
    $dayExpensesForJs = ($expensesByDate ?? collect())->map(function ($grp) {
        return $grp->map(fn ($e) => [
            'id'     => $e->id,
            'amount' => (float) $e->amount,
            'note'   => $e->note,
        ])->values();
    });
@endphp
<script>
    // Per-day extra expenses (amount + note), keyed by date. Seeded from the
    // server; kept live by the expense sheet so day strips re-render without a
    // reload. { 'YYYY-MM-DD': [{id, amount, note}, ...] }
    window.DAY_EXPENSES = @json($dayExpensesForJs);
    // A keyed collection JSON-encodes to an object; an empty one becomes [].
    if (Array.isArray(window.DAY_EXPENSES)) window.DAY_EXPENSES = {};
</script>
@include('sm.partials.activities-js', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
    'draftsCount' => $draftsCount,
])
@include('sm.partials.activities-calendar-js', ['schedule' => $schedule])
@include('community.partials.lightbox-js')
<script>
    // Filter-sheet extras: active-filter count badge on the toolbar button, and
    // a "Clear filters" action. Reuses the events the activities filter logic
    // already listens to (input + chips:change), so no duplication of logic.
    (function activityFilterSheet() {
        const byId = (id) => document.getElementById(id);

        function countActive() {
            let n = (byId('activitySearchInput')?.value || '').trim() ? 1 : 0;
            n += document.querySelectorAll('#typeFilterChips .chip.is-selected').length;
            n += document.querySelectorAll('#lotFilterChips .chip.is-selected').length;
            return n;
        }
        function refreshBadge() {
            const badge = byId('activeFilterCount');
            if (!badge) return;
            const n = countActive();
            badge.textContent = n;
            badge.classList.toggle('hidden', n === 0);
            badge.classList.toggle('inline-flex', n > 0);
        }

        // "Hide empty dates" — collapses the rest-day rows. Persisted per schedule.
        const HIDE_EMPTY_KEY = 'hideEmptyDates:' + @json($schedule->id);
        function applyHideEmpty(on) {
            document.body.classList.toggle('hide-empty-dates', on);
            const label = byId('toggleEmptyDatesLabel');
            if (label) label.textContent = on ? 'Show empty dates' : 'Hide empty dates';
            byId('toggleEmptyDatesBtn')?.classList.toggle('btn-primary', on);
            byId('toggleEmptyDatesBtn')?.classList.toggle('btn-white', !on);
            try { localStorage.setItem(HIDE_EMPTY_KEY, on ? '1' : '0'); } catch (_) { /* noop */ }
        }
        byId('toggleEmptyDatesBtn')?.addEventListener('click', () => {
            applyHideEmpty(!document.body.classList.contains('hide-empty-dates'));
        });
        try { applyHideEmpty(localStorage.getItem(HIDE_EMPTY_KEY) === '1'); } catch (_) { /* noop */ }

        byId('activitySearchInput')?.addEventListener('input', refreshBadge);
        document.addEventListener('chips:change', (e) => {
            const id = e.target?.id;
            if (id === 'typeFilterChips' || id === 'lotFilterChips') refreshBadge();
        });

        byId('clearFiltersBtn')?.addEventListener('click', () => {
            const search = byId('activitySearchInput');
            if (search && search.value) {
                search.value = '';
                search.dispatchEvent(new Event('input', { bubbles: true }));
            }
            ['typeFilterChips', 'lotFilterChips'].forEach((gid) => {
                const group = byId(gid);
                if (!group) return;
                let changed = false;
                group.querySelectorAll('.chip.is-selected').forEach((c) => {
                    c.classList.remove('is-selected');
                    changed = true;
                });
                if (changed) group.dispatchEvent(new CustomEvent('chips:change', { bubbles: true }));
            });
            refreshBadge();
        });

        refreshBadge();
    })();
</script>
@endpush
