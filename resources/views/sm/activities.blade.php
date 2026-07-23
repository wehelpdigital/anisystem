@extends('layouts.app')

@section('title', 'Activities — ' . $schedule->title)
@section('page-title', 'Activities')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

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
        .date-header-count { font-size: 11px; font-weight: 700; color: var(--date-color); background: var(--tl-pill); border-radius: 999px; padding: .12rem .55rem; margin-left: auto; }
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

        .date-activities { display: flex; flex-direction: column; gap: .55rem; padding: .7rem; }
        .date-activities.drag-over { outline: 2px dashed #86b556; outline-offset: -4px; border-radius: .8rem; background: color-mix(in srgb, #86b556 12%, var(--tl-surface)); }

        .date-note-block {
            margin: .55rem .7rem 0; background: var(--tl-note-bg); border: 1px solid var(--tl-note-border); border-radius: .6rem;
            padding: .5rem .7rem; font-size: .8rem; color: var(--tl-note-text); white-space: pre-wrap;
        }

        /* ---- Activity card ----------------------------------------------
           Left rail carries the priority colour so the top-right stays free
           for actions. Tapping the card body opens the editor. */
        .activity-card {
            border: 1px solid var(--tl-border-soft); border-left: 3px solid var(--prio-color, #d1d5db);
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
        .activity-card-badges { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .3rem; }
        .activity-card-lots { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .45rem; }
        /* Meta strip: time + workers + materials/services on one wrapped row. */
        .activity-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .55rem; }
        .meta-time {
            display: inline-flex; align-items: center; gap: .25rem; background: var(--tl-surface-2); color: var(--tl-text-muted);
            border-radius: .5rem; padding: .2rem .45rem; font-size: 11.5px; font-weight: 600;
        }
        /* The date header drags the whole day's activities to another date. */
        .date-header[draggable="true"] { cursor: grab; }
        .date-header.dragging { opacity: .55; }
        .date-group.drag-over-group { outline: 2px dashed var(--date-color, #4A90E2); outline-offset: 2px; }
        /* "Hide empty dates" filter */
        body.hide-empty-dates .rest-day-marker { display: none; }

        /* ---- SPA shell ---------------------------------------------------
           `module-hidden` beats component classes that set their own display
           (the reason a plain `hidden` utility can lose here). */
        .module-hidden { display: none !important; }
        /* Injected modules keep their own chip nav in the markup — the toolbar
           hamburger replaces it, so hide it inside the shell. */
        #moduleHost .module-chip-nav { display: none; }
        #moduleHost > div { animation: app-fade-up .3s cubic-bezier(.22,1,.36,1) both; }
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
        .activity-card, .date-header[draggable="true"] { -webkit-touch-callout: none; }
        .activity-card-image img { max-width: 100%; max-height: 260px; border-radius: .6rem; border: 1px solid #eef0f3; }
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
        .activity-na-tag { background: #f3f4f6; color: #6b7280; border: 1px dashed #d1d5db; }
        .day-zero-badge { background: #ff9800; color: #fff; }

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
        }
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
        body.show-hidden-activities .rest-day-substitute { display: none; }
        .activity-card.filter-hidden { display: none !important; }
        .date-group.group-collapsed { display: none; }
        .rest-day-marker.filters-active { display: none; }

        /* DAS / Day-0 panels inside the activity sheet */
        .das-panel { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: .75rem; padding: .75rem; }
        .day-zero-panel { background: #fffbeb; border: 1px solid #fde68a; border-radius: .75rem; padding: .75rem; }

        /* Quill wrapper with HTML-source toggle */
        .sm-quill-wrap .ql-toolbar { border-top-left-radius: .75rem; border-top-right-radius: .75rem; border-color: #d1d5db; }
        .sm-quill-wrap .ql-container { border-bottom-left-radius: .75rem; border-bottom-right-radius: .75rem; border-color: #d1d5db; min-height: 130px; font-family: inherit; font-size: .875rem; }
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

        html.dark .das-panel { background: #151f30; border-color: #24354f; }
        html.dark .day-zero-panel { background: var(--tl-note-bg); border-color: var(--tl-note-border); }

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
            display: block; width: 100%; font-size: 10.5px; font-weight: 700; line-height: 1.25;
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
            <span id="currentModuleLabel">Modules - Activities</span>
        </button>

        <button type="button" id="readinessBtn" class="btn btn-white btn-sm relative {{ $readiness['count'] > 0 ? 'has-alerts' : '' }}"
                title="{{ $readiness['count'] > 0 ? $readiness['count'] . ($readiness['count'] === 1 ? ' thing still needs' : ' things still need') . ' setting up' : 'Everything is set up' }}">
            <span class="readiness-ripple" aria-hidden="true"></span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/></svg>
            <span class="hidden sm:inline">Notice</span>
            <span id="readinessCount"
                  class="absolute -top-1.5 -right-1.5 {{ $readiness['count'] > 0 ? 'inline-flex' : 'hidden' }} min-w-5 h-5 px-1 rounded-full {{ $readiness['blocking'] > 0 ? 'bg-red-500 text-white' : 'bg-accent-500 text-ink' }} text-[10px] font-bold items-center justify-center">{{ $readiness['count'] }}</span>
        </button>

        <button type="button" id="activityUndoBtn" class="btn btn-white btn-sm relative" data-activities-only disabled title="Nothing to undo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg>
            Undo
            <span id="activityUndoCount" class="absolute -top-1.5 -right-1.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[10px] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="activityRedoBtn" class="btn btn-white btn-sm relative" data-activities-only disabled title="Nothing to redo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4"/></svg>
            Redo
            <span id="activityRedoCount" class="absolute -top-1.5 -right-1.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[10px] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="openDraftsBtn" class="btn btn-white btn-sm" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            Drafts <span id="draftsBadge" class="badge badge-gray">{{ $draftsCount }}</span>
        </button>
        <button type="button" id="openReportBtn" class="btn btn-white btn-sm" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Report
        </button>
        <button type="button" data-sheet-open="filtersSheet" class="btn btn-white btn-sm relative" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            Search
            <span id="activeFilterCount" class="absolute -top-1.5 -right-1.5 hidden min-w-5 h-5 px-1 rounded-full bg-brand-600 text-white text-[10px] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="viewToggleBtn" class="btn btn-white btn-sm" data-activities-only
                title="Switch to calendar view" aria-pressed="false">
            <svg id="viewIconCalendar" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <svg id="viewIconList" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span id="viewToggleLabel">Calendar</span>
        </button>
    </div>
</div>

{{-- Module host: other modules are fetched as partials and injected here.
     Activities stays in the DOM (hidden) so its listeners survive. --}}
<div id="moduleHost" class="hidden"></div>

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

<div id="activitiesRoot">
{{-- ============================ VERSIONS STRIP ============================ --}}
<div class="flex items-center gap-1 mb-3">
    <div class="scroll-chips grow" id="versionStrip">
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
    <button type="button" id="addActivityBtn" class="btn btn-primary btn-sm shrink-0" data-activities-only>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        <span class="hidden sm:inline">Add Activity</span>
    </button>
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
                <input type="search" id="activitySearchInput" class="form-input pr-16" placeholder="Title, lots, workers, items…">
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

        <div>
            <label class="text-xs font-semibold text-gray-500">Display</label>
            <div class="mt-1.5 flex flex-wrap gap-2">
                <button type="button" id="toggleEmptyDatesBtn" class="btn btn-white btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                    <span id="toggleEmptyDatesLabel">Hide empty dates</span>
                </button>
                <button type="button" id="toggleHiddenBtn" class="btn btn-white btn-sm {{ $hiddenCount ? '' : 'hidden' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    <span id="toggleHiddenLabel">Show Hidden ({{ $hiddenCount }})</span>
                </button>
            </div>
            <p class="form-hint">Empty dates are the "no activities scheduled" rows between your working days.</p>
        </div>
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
                <div class="progress-marker" data-marker-id="{{ $marker->id }}" data-date="{{ $item['date'] }}">
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
                <div class="date-group date-color-{{ $item['color'] }} {{ $allHidden ? 'all-hidden' : '' }}" data-date="{{ $dateKey }}">
                    <div class="date-header"@if ($dateCarbon) draggable="true" title="Drag this header to move the whole day to another date"@endif>
                        @if ($dateCarbon)
                            <span class="date-header-day">{{ $dateCarbon->format('D') }}</span>
                            <span class="date-header-date">{{ $dateCarbon->format('M j, Y') }}</span>
                            @if ($latestEndCarbon)
                                <span class="date-header-range" title="At least one activity extends through {{ $latestEndCarbon->format('M j, Y') }}">
                                    &rarr; {{ $latestEndCarbon->format('M j') }}@if($latestEndCarbon->year !== $dateCarbon->year), {{ $latestEndCarbon->year }}@endif ({{ $groupSpanDays }}d)
                                </span>
                            @endif
                        @else
                            <span class="date-header-date">No date</span>
                        @endif
                        <span class="date-header-count">{{ $activitiesForDate->count() }} {{ Str::plural('activity', $activitiesForDate->count()) }}</span>
                        @if ($dateKey !== '__no-date__')
                            <button type="button" class="date-header-btn group-add-activity-btn" data-date="{{ $dateKey }}" title="Add a new activity to this date">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </button>
                            {{-- Secondary day actions: inline on desktop, overflow sheet on phones. --}}
                            <span class="hidden md:flex items-center gap-0.5">
                                <button type="button" class="date-header-btn date-note-btn {{ $noteRow ? 'has-note' : '' }}" data-date="{{ $dateKey }}" title="{{ $noteRow ? 'Edit the note for this date' : 'Add a note for this date' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn date-marker-btn {{ $existingMarker ? 'has-marker' : '' }}" data-date="{{ $dateKey }}" title="{{ $existingMarker ? 'Edit the resume-here marker' : 'Drop a resume-here marker after this date' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn change-group-date-btn" data-date="{{ $dateKey }}" title="Change date for all activities in this group">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn move-group-das-btn" data-date="{{ $dateKey }}" title="Move this whole day to a {{ $schedule->dayType }} number">
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
                    @if ($dateKey !== '__no-date__')
                        <div class="date-note-block" data-date="{{ $dateKey }}" @if(!$noteRow) style="display:none;" @endif>{{ $noteRow?->noteContent }}</div>
                    @endif
                    <div class="date-activities" data-date="{{ $dateKey }}">
                        @foreach ($activitiesForDate as $a)
                            @include('sm.partials.activity-card', ['a' => $a, 'schedule' => $schedule, 'activityTypes' => $activityTypes, 'lotDayZeroEff' => $lotDayZeroEff])
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

{{-- Mobile floating action button --}}
<button type="button" id="fabAddActivity" data-activities-only
    class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center"
    aria-label="Add activity">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
</button>
</div>{{-- /#activitiesRoot --}}
@endsection

@push('sheets')
@include('sm.partials.activities-sheets', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
])
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
    const MODULES = {
        activities:    { label: 'Activities',    url: @json(route('sm.activities',    ['id' => $schedule->id])) },
        settings:      { label: 'Settings',      url: @json(route('sm.settings',      ['id' => $schedule->id])) },
        lots:          { label: 'Lots',          url: @json(route('sm.lots',          ['id' => $schedule->id])) },
        workers:       { label: 'Workers',       url: @json(route('sm.workers',       ['id' => $schedule->id])) },
        materials:     { label: 'Materials',     url: @json(route('sm.materials',     ['id' => $schedule->id])) },
        services:      { label: 'Services',      url: @json(route('sm.services',      ['id' => $schedule->id])) },
        documentation: { label: 'Documentation', url: @json(route('sm.documentation', ['id' => $schedule->id])) },
        irrigations:   { label: 'Irrigation',    url: @json(route('sm.irrigations',   ['id' => $schedule->id])) },
        'post-harvest': { label: 'Post-harvest', url: @json(route('sm.post-harvest',  ['id' => $schedule->id])) },
    };

    const host = document.getElementById('moduleHost');
    const activitiesRoot = document.getElementById('activitiesRoot');
    const loader = document.getElementById('moduleLoader');
    const loaderLabel = document.getElementById('moduleLoaderLabel');
    const label = document.getElementById('currentModuleLabel');
    const loaded = new Map();           // key -> injected wrapper element
    let current = 'activities';
    let busy = false;

    const setActivitiesChrome = (on) => {
        document.querySelectorAll('[data-activities-only]').forEach((el) => {
            el.classList.toggle('module-hidden', !on);
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

    async function showModule(key, push = true) {
        if (busy || !MODULES[key] || key === current) { closeSheet('modulesSheet'); return; }
        busy = true;
        closeSheet('modulesSheet');

        // Hide whatever is showing.
        activitiesRoot.classList.add('module-hidden');
        loaded.forEach((el) => el.classList.add('module-hidden'));

        if (key === 'activities') {
            activitiesRoot.classList.remove('module-hidden');
            host.classList.add('hidden');
        } else if (loaded.has(key)) {
            host.classList.remove('hidden');
            loaded.get(key).classList.remove('module-hidden');
        } else {
            loaderLabel.textContent = 'Loading ' + MODULES[key].label + '…';
            loader.classList.remove('hidden');
            host.classList.add('hidden');
            try {
                const sep = MODULES[key].url.includes('?') ? '&' : '?';
                const res = await fetch(MODULES[key].url + sep + 'partial=1', {
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
        label.textContent = 'Modules - ' + MODULES[key].label;
        // Keep the app header + browser tab in step with the swapped module.
        const pageTitle = document.getElementById('appPageTitle');
        if (pageTitle) pageTitle.textContent = MODULES[key].label;
        document.title = MODULES[key].label + ' — ' + @json($schedule->title);
        setActivitiesChrome(key === 'activities');
        document.querySelectorAll('#modulesSheet .module-nav-row').forEach((row) => {
            row.querySelector('.module-nav-check')?.classList.toggle('hidden', row.dataset.module !== key);
        });
        if (push) history.pushState({ module: key }, '', MODULES[key].url);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        busy = false;
        // Leaving a module usually means something was just added or removed.
        window.smRefreshReadiness?.();
    }

    document.getElementById('modulesBtn')?.addEventListener('click', () => openSheet('modulesSheet'));
    document.addEventListener('click', (e) => {
        const row = e.target.closest('#modulesSheet .module-nav-row');
        if (row) { showModule(row.dataset.module); return; }
        // Module chip-nav links inside an injected partial stay in the shell.
        const link = e.target.closest('#moduleHost a[href]');
        if (link) {
            const hit = Object.keys(MODULES).find((k) => link.href.split('?')[0] === MODULES[k].url.split('?')[0]);
            if (hit) { e.preventDefault(); showModule(hit); }
        }
    });

    window.addEventListener('popstate', (e) => showModule((e.state && e.state.module) || 'activities', false));
    history.replaceState({ module: 'activities' }, '', MODULES.activities.url);
    window.smShowModule = showModule;

    /* ---- Readiness bell -------------------------------------------------
     * Flags what the plan is still missing (no day 0, no lots, activities
     * with no date...). Rippling while anything is outstanding; each row
     * jumps straight to the module that fixes it. */

    const readinessBtn = document.getElementById('readinessBtn');
    const readinessCount = document.getElementById('readinessCount');
    let READINESS = @json($readiness);

    const esc = (s) => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    function paintReadiness() {
        if (!readinessBtn) return;
        const n = READINESS.count || 0;
        const blocking = READINESS.blocking || 0;
        readinessBtn.classList.toggle('has-alerts', n > 0);
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
@include('sm.partials.activities-js', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
    'draftsCount' => $draftsCount,
])
@include('sm.partials.activities-calendar-js', ['schedule' => $schedule])
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
