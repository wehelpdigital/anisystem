@extends('layouts.app')

@section('title', 'Activities: ' . $schedule->title)
@section('page-title', 'Activities')
@section('page-subtitle', $schedule->title)
@section('help-key', 'activities')
@section('back', \App\Support\BackTo::url(route('sm.hub', ['id' => $schedule->id]), $schedule->id))

{{-- The activity board is the work surface: on a phone the bottom tab bar eats
     a strip of it for navigation that Back already provides. --}}
@section('body-class', 'hide-tabbar no-zoom is-activities' . (request('module', 'activities') === 'activities' ? ' act-module-open' : ' module-booting'))

@if (request()->boolean('embed'))
{{-- Framed inside the Collab Room, this page is part of the room: it holds
     the socket that lets a teammate's change land on the board as it
     happens. On its own (no embed) it holds none — its own changes paint
     locally, and nothing else is listening. --}}
@section('realtime', 'room')
@push('head')
<script>document.documentElement.classList.add('collab-embed');</script>
<style>
    /* Collab Room embed (?embed=1): show only the activities board. Hide the app
       chrome (header/tabbar/footer), module switching, quick share, the Collab
       button and the floating widgets — keep add/move, versions, notes, markers. */
    html.collab-embed header.sticky, html.collab-embed .tabbar, html.collab-embed footer { display: none !important; }
    /* The button that brings a module's info line back lives in that header,
       so inside the embed the line must not be dismissible — otherwise it
       goes away with no way back. */
    html.collab-embed .mod-say-x { display: none; }
    html.collab-embed .mod-say-wrap.is-away { max-height: none; opacity: 1; margin-bottom: .85rem; }
    /* Enough headroom for the count badges, which hang ABOVE the buttons they
       belong to: at .6rem the bell's number was sliced in half by the tab's
       own "Team tasks" line sitting directly over the frame. */
    html.collab-embed main { padding: 1.4rem .75rem 1rem !important; max-width: none !important; }
    /* The version, Today and Add row is a second row of controls, not a
       continuation of the first: two pixels under the toolbar's divider it
       read as one crowded block with a line through the middle of it. */
    html.collab-embed #actHeaderBar { margin-top: .9rem; }
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
    html.collab-embed .activity-action-row[data-forward="openReportBtn"],
    /* Not in the Collab Room's tasks tab.
     *
     * The Tools hamburger opens a sheet of module errands that belong to the
     * full Activities page; the Mirror is a whole-plan reader that wants the
     * screen this tab does not have; and the AI button forwards to a float
     * that is already hidden in here, so it opened nothing. The room has its
     * own AI tab a thumb away. */
    html.collab-embed #activityActionsBtn,
    html.collab-embed #mirrorBtn,
    html.collab-embed #aiTechBtn { display: none !important; }
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
    {{-- The rich editor is the app-wide engine defined in app.js (window.Quill);
         it fetches its own assets on first use. --}}
    @vite('resources/css/board.css')
    {{-- Asked for at once, though it stands at the foot of the page. --}}
    <link rel="preload" as="script" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/board.js') }}">
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

    // Whether this request is standing in somebody else's farm. Not the same
    // question as canEdit(): the doors below are closed to every worker, an
    // editing one included, so the tier never comes into it. Declared up here
    // because the toolbar renders long before $boardMayEdit is worked out.
    $isWorker = \App\Support\WorkerContext::activeGrant() !== null;
    // Per module, as this worker's owner set it; true throughout for an owner.
    $may = fn (string $key) => \App\Support\WorkerContext::canUseModule($key);
    // Add Activity moved up into the toolbar, so what it may do has to be
    // known here rather than in the block further down that used to be the
    // first thing to ask. That block still reads these for the board itself.
    $boardMayEdit = \App\Support\WorkerContext::canEdit();
    $whyNoEdit = 'You do not have permission to edit this plan';
@endphp

{{-- ===================== TOOLBAR (sticky, persistent) =====================
     The modules hamburger lives here, inline with the activity actions. When
     another module is showing, the activities-only buttons hide. --}}
{{-- The utility classes here are corrected by the .sticky.top-14 rule in this
     page's own <style>: top-14 ignores the app bar's 1px border, and pt-0 left
     the count badges (which hang above the buttons) under the header's paint —
     so the CSS drops the bar by the border and pads BOTH sides: top so the
     badges live inside the bar's paint, bottom so the buttons don't sit on the
     bar's own divider line. The resulting height is what the Gallery's shelf
     bar measures its sticky offset from. The two are changed together, always,
     or the shelf bar floats free of the toolbar it is supposed to sit under. --}}
<div class="sticky top-14 md:top-16 z-20 bg-gray-50 -mx-4 px-4 sm:-mx-6 sm:px-6 pt-0 pb-2 mb-2 border-b border-gray-100">
    <div id="actToolbar" class="flex items-center gap-2 flex-wrap">
        <button type="button" id="modulesBtn" class="btn btn-white btn-sm" title="Switch module">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            {{-- It says what the button DOES, not where you already are: the
                 app header above this row already names the module, so
                 "Modules - Settings" was the same word twice and left nobody
                 any wiser about what pressing it would open. One word, and
                 the same word on every page — "Other Modules" here against
                 "All Modules" on a page opened on its own read as two
                 different doors when it is one. --}}
            <span id="currentModuleLabel">Modules</span>
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
        {{-- A wrench, and no chevron: three stacked lines is the sign for
             "modules" on the button to its left, and wearing it here made the
             two read as the same control opened twice. Now Tools stands with
             Notice, the eye and Search — one glyph, one word from sm up. --}}
        <button type="button" id="activityActionsBtn" class="btn btn-white btn-sm relative" data-activities-only title="Tools" aria-label="Tools">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <span class="hidden sm:inline">Tools</span>
        </button>

        <button type="button" id="readinessBtn" class="btn btn-white btn-sm relative {{ $readiness['count'] > 0 ? 'has-alerts' : '' }}"
                title="{{ $readiness['count'] > 0 ? $readiness['count'] . ($readiness['count'] === 1 ? ' thing still needs' : ' things still need') . ' to be set up' : 'Everything is set up' }}">
            <span class="readiness-ripple" aria-hidden="true"></span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/></svg>
            <span class="hidden sm:inline">Notice</span>
            {{-- Tucked onto the corner rather than hung above it: at -top-0.5
                 the count stood proud of every other control in the row, and
                 that is the edge the eye measures the row's top from. --}}
            <span id="readinessCount"
                  class="absolute top-0 right-0 -translate-y-1/3 translate-x-1/3 {{ $readiness['count'] > 0 ? 'inline-flex' : 'hidden' }} min-w-5 h-5 px-1 rounded-full {{ $readiness['blocking'] > 0 ? 'bg-red-500 text-white' : 'bg-accent-500 text-ink' }} text-[0.625rem] font-bold items-center justify-center">{{ $readiness['count'] }}</span>
        </button>

        {{-- Where the put-away module note comes back from. It belongs beside
             the Notice bell, with the rest of this module's own controls,
             rather than up in the app bar among the things that are true of
             every page. Empty until the note is folded away. --}}
        <span data-mod-say-slot></span>

        {{-- Two of the three the board is worked with, standing where Undo and
             Redo used to: what the board shows, and how to find something on
             it. (Add Activity stayed on the controls row, beside the rest of
             what is done TO the board.) Undo and Redo have gone into the Tools
             menu — they are the rarer answer to a mistake, not a daily control
             — but stay in the DOM (toolbar-in-menu) so the menu rows can
             forward to their handlers. --}}
        <button type="button" id="viewFilterBtn" class="btn btn-white btn-sm shrink-0 md:hidden" data-activities-only
                title="What the board shows" aria-label="What the board shows">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </button>
        {{-- Search & filter, one tap from the board. Icon-only on a phone so
             the row keeps to its line; the word joins it from sm up. Opens the
             same filters sheet the old menu row opened, and wears the same
             live filter-count badge (mirrored in syncActionsSheet). --}}
        <button type="button" id="searchToolbarBtn" data-sheet-open="filtersSheet" class="btn btn-white btn-sm shrink-0 relative" data-activities-only
                title="Search &amp; filter the board" aria-label="Search and filter the board">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            <span class="hidden sm:inline">Search</span>
            <span id="toolbarFilterCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-brand-600 text-white text-[0.625rem] font-bold items-center justify-center">0</span>
        </button>
        {{-- The mirror: the whole plan held up to be read, and nothing else.
             Every day, every activity, hidden ones included, done ones
             marked — with no button on any of it. The board you work on has
             to be re-filtered and un-hidden to answer "is that done?", and
             then put back; this answers it without touching the board at
             all. --}}
        <button type="button" id="mirrorBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
                title="Mirror: read the whole plan" aria-label="Mirror: read the whole plan">
            {{-- A compact mirror: the ring, the glass inside it, the little
                 loop you hold it by, and a shine across the top-left corner
                 of the glass. Drawn rather than dropped in as a picture — a
                 path is a few hundred bytes against a PNG's several thousand,
                 it stays sharp at any size, and it takes currentColor, so it
                 is the same ink as every other button in this bar in either
                 theme. A picture would have needed two of itself and would
                 still have been the wrong grey in one of them. --}}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.7"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                {{-- the loop, and the two shoulders it hangs from --}}
                <circle cx="12" cy="2.6" r="1.5"/>
                <path d="M10.6 3.2 8.2 5.9M13.4 3.2l2.4 2.7"/>
                {{-- the case, and the glass — the gap between them is what
                     makes this a mirror rather than a stopwatch, so it is
                     wide enough to survive being drawn at 16 pixels --}}
                <circle cx="12" cy="14.3" r="8.5"/>
                <circle cx="12" cy="14.3" r="5.7"/>
                {{-- the shine --}}
                <path d="M8.8 14.3a3.2 3.2 0 0 1 3.2-3.2"/>
                <path d="M8.85 16.7v.01"/>
            </svg>
        </button>
        {{-- Refresh: the board, freshly built.
             A long session on a big season accumulates what a long session
             accumulates — half-finished animations, a scroll position the
             browser is still catching up with, sheets that have been opened
             and closed thirty times. Rebuilding the page costs one read and
             hands all of it back, which is what people already do by hitting
             the browser's own reload; this puts it where the hand already is,
             next to the Mirror. --}}
        <button type="button" id="boardRefreshBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
                title="Refresh the board" aria-label="Refresh the board">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20 9A8 8 0 006.34 6.34M4 15a8 8 0 0013.66 2.66"/></svg>
            <span class="hidden sm:inline">Refresh</span>
        </button>
        {{-- No "Total two days" button any more, here or in the Tools menu:
             the stretch is asked of a day's own cost pill (tap it, choose
             the stretch), which is where the question comes up. --}}
        <button type="button" id="activityUndoBtn" class="btn btn-white btn-sm relative toolbar-in-menu" data-activities-only disabled title="Nothing to undo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg>
            <span class="hidden sm:inline">Undo</span>
            <span id="activityUndoCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[0.625rem] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="activityRedoBtn" class="btn btn-white btn-sm relative toolbar-in-menu" data-activities-only disabled title="Nothing to redo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4"/></svg>
            <span class="hidden sm:inline">Redo</span>
            <span id="activityRedoCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[0.625rem] font-bold items-center justify-center">0</span>
        </button>
        {{-- Calendar view + Add note: quick actions kept in the toolbar, right
             after Redo. Calendar collapses into the Tools menu on phones. --}}
        <button type="button" id="viewToggleBtn" class="btn btn-white btn-sm toolbar-desktop-action" data-activities-only
                title="Switch to calendar view" aria-pressed="false">
            <svg id="viewIconCalendar" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <svg id="viewIconList" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span id="viewToggleLabel">Calendar view</span>
        </button>
        {{-- Range Effect (2026-10-29, the owner: "just say range effect in desktop and put it after the calendar view"),
             said "Coverage" on the desktop button since 2026-10-30 (the owner: "in desktop rename range effect to coverage"):
             how long every product on the plan keeps working, against what each stage needs (sm.partials.range-effect). --}}
        <button type="button" id="rangeEffectBtn" class="btn btn-white btn-sm toolbar-desktop-action" data-activities-only
                title="Coverage: how long every product keeps working, day by day">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/><path d="M6.5 15.5c1.6-3.2 3.4-3.2 5 0s3.4 3.2 5 0"/></svg>
            <span>Coverage</span>
        </button>
        <button type="button" id="openNotesBtn" class="btn btn-white btn-sm toolbar-desktop-action" data-activities-only
                title="Open this season's notes">
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
        {{-- The camera lives only in the menu: it is a phone action, and the
             toolbar is already full on the screens that have one. --}}
        {{-- Four doors, each drawn only for somebody who may walk through it.
             The Tools rows forward their clicks to these buttons, so the rows
             alone were never the door — leave a button standing and anything
             that can call .click() still walks straight through. --}}
        @if ($may('camera'))
        <button type="button" id="captureTodayPhotoBtn" class="btn btn-white btn-sm toolbar-in-menu hidden" data-activities-only aria-hidden="true" tabindex="-1">Take a photo</button>
        @endif
        @if ($may('video'))
        <button type="button" id="recordTodayVideoBtn" class="btn btn-white btn-sm toolbar-in-menu hidden" data-activities-only aria-hidden="true" tabindex="-1">Record a video</button>
        @endif
        {{-- Draw and Maps are Global and Quick Tools now (2026-09-30), not
             rooms of the season. A day still takes a drawing or a map from
             its own menu. --}}
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
            Collapse all
        </button>
        <button type="button" id="openReportBtn" class="btn btn-white btn-sm toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9 9 0 1020.945 13H12a1 1 0 01-1-1V3.055zM15 3.936A9.02 9.02 0 0120.064 9H15V3.936z"/></svg>
            Report
        </button>
        <button type="button" id="openSearchBtn" data-sheet-open="filtersSheet" class="btn btn-white btn-sm relative toolbar-in-menu" data-activities-only>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            Search
            <span id="activeFilterCount" class="absolute -top-0.5 -right-0.5 hidden min-w-5 h-5 px-1 rounded-full bg-brand-600 text-white text-[0.625rem] font-bold items-center justify-center">0</span>
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

@php
    // What this viewer may do to the plan, for the bits of the board this file
    // draws itself. Twin of $mayEdit in partials/activity-card.blade.php and of
    // CAN_EDIT / MAY_NOTE in partials/activities-js.blade.php. A drag has no
    // button to grey out, so the affordance itself has to come and go.
    // ($boardMayEdit and $whyNoEdit are settled with $isWorker, above the
    // toolbar, because Add Activity now stands in it.)
    $boardMayNote = \App\Support\WorkerContext::canWriteModule('notes');
    // Dragging is its own question now. An editing worker may still change a
    // day through the sheets, where the change is deliberate and named; what
    // they may not do is rearrange the season with a thumb. Twin of MAY_DRAG /
    // MAY_DRAG_NOTE in partials/activities-js.blade.php.
    $boardMayDrag = $boardMayEdit && ! $isWorker;
    $boardMayDragNote = $boardMayNote && ! $isWorker;
    $whyNoNote = 'You do not have permission to add notes here';
@endphp

{{-- Long-board jump buttons (phones): the toolbar and the version bar sit at
     opposite ends of a list that can be thousands of pixels tall. --}}
<div class="act-jumps md:hidden" data-activities-only>
    {{-- Once the header has scrolled away: add an activity, then the
         jump-to-end pair. Add forwards to the real toolbar button — which is
         itself greyed for a viewer, and a disabled button's .click() does
         nothing at all, so this one has to say no in its own right. --}}
    <button type="button" id="actFabAdd" class="act-fab-add{{ $boardMayEdit ? '' : ' is-locked' }}" aria-label="Add an activity"
            @disabled(! $boardMayEdit) title="{{ $boardMayEdit ? 'Add an activity' : $whyNoEdit }}">
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
{{-- Switching modules waits with the same card the board does. The label
     the shell used to write into is kept, hidden, because the module name is
     still worth announcing to a screen reader even though the card now says
     something better to everybody else. --}}
<div id="moduleLoader" class="hidden">
    <div class="flex justify-center py-10">
        @include('sm.partials.wait-card')
        {{-- sr-only is a Tailwind utility this build cannot be relied on to
             carry (see the .topb-say note in plaza-css), so the hiding is
             written out rather than named. --}}
        <span id="moduleLoaderLabel" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0">Loading…</span>
    </div>
</div>

{{-- The board arrives before the two things it is read for: what the day
     costs, and what the sky is doing. Both need a round trip, so for a second
     or two the headers were visibly incomplete and then jumped as the pills
     landed. This covers that second — the board is drawn underneath and
     revealed once both have arrived (or given up). --}}
<div id="boardVeil" aria-hidden="true">
    @include('sm.partials.wait-card', ['waitPool' => \App\Models\AsLoadingLine::pool('board')])
</div>

<div id="activitiesRoot">
{{-- ============================ VERSIONS STRIP ============================ --}}
{{-- id: the floating jump stack watches this row and only shows once it has
     scrolled away (see activities-js). --}}
<div class="flex items-center gap-1 mb-3" id="actHeaderBar">
    {{-- min-w-0: without it this grow item refuses to shrink below its content
         width (flex min-width:auto), so long version lists would overflow the
         row instead of engaging the strip's own swipe scroll. --}}
    {{-- Phones: the chip strip folds behind one button + bottom sheet.

         The button says which version you are IN rather than the word
         "Versions": on a plan with one version the word was a label for a
         thing nobody had, and on a plan with four it still did not say which
         one you were reading. The name it wears is the answer to the question
         the button is actually asked. A long one trails off (see the ellipsis
         rule for #versionsSheetBtn > span) rather than pushing the row. --}}
    @php
        $activeVersion = $schedule->versions->firstWhere('isActive', 1) ?? $schedule->versions->first();
    @endphp
    <button type="button" id="versionsSheetBtn" class="btn btn-white btn-sm shrink-0 md:hidden"
            title="{{ $activeVersion?->versionName ? 'Version: ' . $activeVersion->versionName . '. Tap to switch, add or edit.' : 'Switch or add a plan version' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5"/></svg>
        <span id="versionsSheetLabel">{{ $activeVersion?->versionName ?: 'Versions' }}</span>
        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
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
        {{-- Switching between versions is reading the plan another way, so the
             chips stay. Starting a new one is authoring a second plan, which
             is the owner's call — for a worker the door is not drawn at all. --}}
        @if (! $isWorker)
        <button type="button" id="addVersionBtn" class="chip chip-dashed shrink-0" data-chip-manual>+ New version</button>
        @endif
    </div>
    <button type="button" id="manageVersionBtn" class="icon-btn shrink-0" title="Rename or delete the current version">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
    </button>
    {{-- Harvest dates (2026-10-12, where NPK Plus was): each lot's harvest by
         the calendar and as Anee realigned it (sm.partials.harvest-dates). --}}
    <button type="button" id="harvestDatesBtn" class="btn btn-white btn-sm shrink-0" data-activities-only title="Harvest dates of every lot">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V3.5"/><path d="M5 4h13l-2.2 4L18 12H5"/><path d="M9.3 4v8M13.6 4v8M5 8h11.8" stroke-width="1.3"/></svg>
        <span class="hidden sm:inline">Harvest</span>
    </button>
    {{-- Range Effect on a phone (2026-10-29, the owner: "just an icon in mobile, and put it after harvest button"). --}}
    <button type="button" id="rangeEffectMobileBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Coverage Planner: how long every product keeps working" aria-label="Coverage Planner">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/><path d="M6.5 15.5c1.6-3.2 3.4-3.2 5 0s3.4 3.2 5 0"/></svg>
    </button>
    <button type="button" id="todayTomorrowBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Scroll to today">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-5-5m5 5l5-5M5 20h14"/></svg>
        <span class="hidden sm:inline">Today</span>
    </button>
    {{-- The eye (both day filters, see #viewFilterSheet), Add Activity and
         Search have moved up to the toolbar row, into the place Undo and Redo
         used to hold. --}}
    <button type="button" id="toggleEmptyDatesBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Show or hide days with no activities">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <span id="toggleEmptyDatesLabel" class="hidden sm:inline">Hide empty dates</span>
    </button>
    <button type="button" id="toggleHiddenBtn" class="btn btn-white btn-sm shrink-0 toolbar-desktop-action {{ $hiddenCount ? '' : 'hidden' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        <span id="toggleHiddenLabel">Show hidden ({{ $hiddenCount }})</span>
    </button>
    {{-- Stays visible on phones too (no toolbar-desktop-action), sitting
         right before Add Activity as the one-tap done-days toggle. --}}
    <button type="button" id="toggleDoneDaysBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
            title="Hide days where all work is done" aria-pressed="false">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        <span id="toggleDoneDaysLabel" class="hidden sm:inline">Hide done days</span>
    </button>
    <button type="button" id="quickShareBtn" class="btn btn-white btn-sm shrink-0 toolbar-in-menu" data-activities-only
            title="Share this whole plan or email workers">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>
        <span class="hidden sm:inline">Quick Share</span>
    </button>
    <div class="shrink-0" id="addActivityWrap" data-activities-only>
        {{-- The board's most prominent write: drawn where everyone else has it,
             greyed and inert, saying why. openAddActivitySheet refuses too. --}}
        @if (! \App\Support\WorkerContext::activeGrant() || \App\Support\WorkerContext::canEdit())
        <button type="button" id="addActivityBtn" class="btn btn-primary btn-sm{{ $boardMayEdit ? '' : ' is-locked' }}"
                @disabled(! $boardMayEdit) @if (! $boardMayEdit) title="{{ $whyNoEdit }}" @endif>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span class="hidden sm:inline">Add Activity</span>
        </button>
        @endif
    </div>
    {{-- The technician, on the board rather than behind the Tools menu. Its
         bubble is hidden on phones (it covered the board), so this is how it
         opens there; it forwards to the bubble's own launcher, which keeps a
         single open/close path. Drawn only where there is one to open. --}}
    @if (\App\Models\AiSetting::current()?->isUsable() && ! $isWorker)
        <button type="button" id="aiTechBtn" class="btn btn-white btn-sm shrink-0" data-activities-only
                title="Ask Anee" aria-label="Ask Anee">
            <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="" class="w-5 h-5 rounded-full object-cover">
        </button>
    @endif
</div>
{{-- The free plan carries an advertisement here; a paid plan sees nothing. --}}
@include('partials.ad-slot', ['placement' => 'activities'])


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

        <div id="tagFilterWrap" class="hidden">
            <label class="text-xs font-semibold text-gray-500">Tags</label>
            <div class="flex flex-wrap gap-1.5 mt-1.5" id="tagFilterChips" data-chip-group></div>
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
                            title="Hide {{ $lot->lotName }}. Activities that also cover a lot you still show will stay.">
                            {{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif
                        </button>
                    @endforeach
                    <button type="button" class="chip chip-dashed min-h-9! py-1! text-xs" data-value="__na__"
                        title="Hide activities with no lot">No lot</button>
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
        <p class="text-sm text-gray-500 mt-1" id="calEmptyHint">Use the arrows to find months with work.</p>
    </div>
</div>

{{-- ============================ TIMELINE ============================ --}}
{{-- Only ever seen with "day-zero only" on and nothing to show for it:
     without this the board would just look broken. --}}
<div id="dayZeroNone" class="card card-body text-center text-gray-500 py-8 hidden">
    <p class="font-bold text-gray-800 mb-1">No day zero yet</p>
    <p class="text-sm">No activity is marked as day zero (DAS 0, DAP 0 or DAT 0). Open the activity that starts the count and tick “Mark this activity as Day 0”.</p>
</div>

<div id="activitiesList" class="activity-timeline">
    @if ($sortedActivities->count() === 0)
        <div id="activitiesEmpty" class="card card-body text-center text-gray-500 py-10">
            <p class="font-bold text-gray-800 mb-1">No activities yet</p>
            <p class="text-sm">Tap <strong>Add Activity</strong> to add your first one.</p>
        </div>
    @else
        @foreach ($timeline as $item)
            @if ($item['type'] === 'rest')
                <div class="rest-day-marker" data-date="{{ $item['date'] }}">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <div class="grow min-w-0">
                        <span class="rest-day-date">{{ $item['carbon']->format('l, F j, Y') }}</span>
                        <span class="rest-day-tag">No activities scheduled</span>
                        {{-- A delivery logged on a quiet day still needs a
                             place to read as a note. --}}
                        <div class="day-ivnotes" data-date="{{ $item['date'] }}" hidden></div>
                    </div>
                    <button type="button" class="btn btn-white btn-sm rest-day-add-btn shrink-0" data-date="{{ $item['date'] }}">+ Add</button>
                </div>
            @elseif ($item['type'] === 'marker')
                @php $marker = $item['marker']; @endphp
                <div class="progress-marker" data-marker-id="{{ $marker->id }}" data-date="{{ $item['date'] }}" @if ($boardMayDrag) draggable="true" @endif title="{{ $boardMayDrag ? 'Drag to move this marker to another day' : $whyNoEdit }}">
                    <div class="progress-marker-line">
                        <span class="progress-marker-bookmark">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                            Resume here: {{ $item['carbon']->format('M j, Y') }}
                        </span>
                        <span class="flex items-center gap-0.5">
                            <button type="button" class="icon-btn progress-marker-edit-btn" data-date="{{ $item['date'] }}" title="Edit marker note">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button type="button" class="icon-btn icon-btn-danger progress-marker-delete-btn" data-marker-id="{{ $marker->id }}" data-date="{{ $item['date'] }}" title="Remove marker">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
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
                    // Today's card wears a ring that breathes (see
                    // .date-group.is-today), so the day you are standing in is
                    // found at a glance rather than read for. The farm's own
                    // day, not the browser's: a phone in another timezone
                    // would otherwise ring the wrong card.
                    $isTodayGroup = $dateKey === now('Asia/Manila')->toDateString();
                @endphp
                @if ($allHidden)
                    <div class="rest-day-marker rest-day-substitute" data-date="{{ $dateKey }}">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <div class="grow min-w-0">
                            <span class="rest-day-date">{{ $dateCarbon->format('l, F j, Y') }}</span>
                            <span class="rest-day-tag">No activities scheduled</span>
                            <div class="day-ivnotes" data-date="{{ $dateKey }}" hidden></div>
                        </div>
                        <button type="button" class="btn btn-white btn-sm rest-day-add-btn shrink-0" data-date="{{ $dateKey }}">+ Add</button>
                    </div>
                @endif
                <div class="date-group date-color-{{ $item['color'] }} {{ $allHidden ? 'all-hidden' : '' }}{{ $isTodayGroup ? ' is-today' : '' }} is-folded" data-date="{{ $dateKey }}">
                    @php
                        // A number from the date itself, so a day keeps the
                        // same rhythm on every visit and its neighbours do not
                        // share it.
                        $dhBeat = crc32((string) $dateKey);
                    @endphp
                    <div class="date-header" style="--sw-t:{{ 9 + ($dhBeat % 7) }}s;--sw-d:-{{ $dhBeat % 11 }}s"@if ($dateCarbon && $boardMayDrag) draggable="true" title="Drag to move this whole day to another date"@endif>
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
                                        ? $dateCarbon->format('M j') . ' to ' . $latestEndCarbon->format('j') . ', ' . $dateCarbon->format('y')
                                        : $dateCarbon->format('M j') . ' to ' . $latestEndCarbon->format('M j') . ', ' . $dateCarbon->format('y'))
                                    : null;
                            @endphp
                            <span class="date-header-date{{ $rangeShort ? ' has-range' : '' }}"><span class="dh-long">{{ $dateCarbon->format('M j, Y') }}</span><span class="dh-short">{{ $dateCarbon->format('M j, y') }}</span>@if($rangeShort)<span class="dh-rangeshort">{{ $rangeShort }}</span>@endif</span>
                            @if ($latestEndCarbon)
                                <span class="date-header-range" title="At least one activity runs until {{ $latestEndCarbon->format('M j, Y') }}">
                                    &rarr; {{ $latestEndCarbon->format('M j') }}@if($latestEndCarbon->year !== $dateCarbon->year), {{ $latestEndCarbon->year }}@endif ({{ $groupSpanDays }}d)
                                </span>
                            @endif
                            {{-- Date Diff on the board (2026-09-29): tap here, then on
                                 another day, and the stretch between opens in a
                                 sheet. Twin of the JS renderer's; wired in
                                 activities-js (DATE DIFF). --}}
                            <button type="button" class="dd-btn" data-dd-date="{{ $dateKey }}" title="Count the days from this day to another one" aria-label="Count days from this day"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 4 4 4M16 7l4 4-4 4M4 11h16"/></svg></button>
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
                        <span class="date-header-stage" hidden title="Crop stage on this day"></span>
                        <span class="date-header-cash" hidden></span>
                        @if ($dateKey !== '__no-date__')
                            <button type="button" class="date-header-btn group-add-activity-btn" data-date="{{ $dateKey }}" title="Add an activity to this day">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </button>
                            {{-- Secondary day actions: inline on desktop, overflow sheet on phones. --}}
                            {{-- Never shown: the day's ⋮ is the door at every
                                 width now. The buttons stay in the DOM because
                                 the menu's rows forward their clicks here. --}}
                            <span class="hidden items-center gap-0.5">
                                {{-- The same lock the JS-rendered copy of this
                                     button carries. Without it the first render
                                     handed a worker who may not write notes a
                                     live button, and only a redraw took it away. --}}
                                @php
                                    $mayNoteHere = \App\Support\WorkerContext::canEdit()
                                        || \App\Support\WorkerContext::canAddNotes();
                                @endphp
                                <button type="button" class="date-header-btn date-note-btn{{ $mayNoteHere ? '' : ' is-locked' }}" data-date="{{ $dateKey }}"
                                        @disabled(! $mayNoteHere)
                                        title="{{ $mayNoteHere ? 'Add a note to this day' : 'You do not have permission to add notes here' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 20H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 8h3M9 12h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 15v5m2.5-2.5h-5"/></svg>
                                </button>
                                <button type="button" class="date-header-btn day-expense-btn" data-date="{{ $dateKey }}" title="Add an extra expense for this day">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M14.4 9.4a2.3 2.3 0 00-2.4-1.3c-1.3.1-2.3.8-2.3 1.9s1 1.7 2.5 1.9 2.6.8 2.6 2-1.1 1.9-2.5 1.9a2.4 2.4 0 01-2.4-1.3"/></svg>
                                </button>
                                <button type="button" class="date-header-btn date-marker-btn {{ $existingMarker ? 'has-marker' : '' }}" data-date="{{ $dateKey }}" title="{{ $existingMarker ? 'Edit the resume here marker' : 'Add a resume here marker after this day' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn share-day-btn" data-date="{{ $dateKey }}" title="Share this day (public link)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn change-group-date-btn" data-date="{{ $dateKey }}" title="Change the date of this whole day">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn move-group-das-btn" data-date="{{ $dateKey }}" title="Move this whole day to a day number">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <button type="button" class="date-header-btn date-header-delete-btn delete-group-date-btn" data-date="{{ $dateKey }}" title="Delete all activities on this day">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                                </button>
                            </span>
                            <button type="button" class="date-header-btn day-menu-btn" data-date="{{ $dateKey }}" title="More actions for this day">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                            </button>
                        @endif
                    </div>
                    <div class="date-body"><div class="date-body-inner">
                    @if ($dateKey !== '__no-date__')
                        @php
                            // Chips, not tiles — mirrored by _refreshDateNoteUI
                            // and buildDateGroupShell in the JS renderer. The
                            // wrapper sits OUTSIDE .date-note-inner so the
                            // phone's one-line clamp trims the words, not the
                            // way into the attachments.
                            $dnMedia = collect(is_array($noteRow?->media) ? $noteRow->media : [])
                                ->map(fn ($m) => empty($m['path']) ? null : [
                                    'type' => $m['type'] ?? 'image',
                                    'path' => $m['path'],
                                    'url' => \App\Support\MediaStore::url($m['path']),
                                    'poster' => $m['poster'] ?? null,
                                    'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
                                    'mapUrl' => ($mapUrlByPath ?? [])[$m['path']] ?? null,
                                    // The strokes ride along in data-media (as the JS
                                    // twin's does) or re-editing the note would save
                                    // the drawing back flattened.
                                    'strokes' => ($m['type'] ?? '') === 'drawing' ? ($m['strokes'] ?? null) : null,
                                ])->filter()->values();
                        @endphp
                        <div class="date-note-block" data-date="{{ $dateKey }}" data-content="{{ $noteRow?->noteContent }}" data-media="{{ $dnMedia->toJson() }}" title="{{ $boardMayDragNote ? 'Drag to place it between activities · tap the dots for options' : ($boardMayNote ? 'Tap the dots for options' : $whyNoNote) }}" @if(!$noteRow) style="display:none;" @endif><div class="date-note-inner rich-text">{!! $noteRow?->noteContent !!}</div>@if ($dnMedia->count())<div class="date-note-media">@include('sm.partials.note-attachments', ['media' => $dnMedia])</div>@endif<button type="button" class="note-kebab" title="Note options" aria-label="Note options"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.9"/><circle cx="12" cy="12" r="1.9"/><circle cx="12" cy="19" r="1.9"/></svg></button></div>
                        <div class="day-expense-block" data-date="{{ $dateKey }}" data-block-sort="{{ ($expenseSortByDate[$dateKey] ?? null) === null ? '' : $expenseSortByDate[$dateKey] }}"></div>
                        {{-- The income strip's own place. It was only ever
                             created by the board's JS renderer, so on a
                             freshly loaded page an income saved with the sheet
                             had nowhere to be drawn: the server said "saved",
                             the day showed nothing, and only a redraw of that
                             day ever brought it out. --}}
                        <div class="day-income-block" data-date="{{ $dateKey }}" data-block-sort="{{ ($incomeSortByDate[$dateKey] ?? null) === null ? '' : $incomeSortByDate[$dateKey] }}" hidden></div>
                        {{-- The shed's notes for this day — each hand-made
                             move as one note-shaped row, filled by the JS
                             painter from DAY_IV_NOTES. The host exists on the
                             fresh page for the same reason the income strip's
                             does: a div only the JS rebuild creates has
                             nowhere to land on first load. --}}
                        <div class="day-ivnotes" data-date="{{ $dateKey }}" hidden></div>
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

{{-- The bubble is the same technician the Tools row opened, so it goes with
     it: a door closed in the menu and left floating over the board is not
     closed. --}}
@if ($may('ai'))
@include('sm.partials.ai-float', ['schedule' => $schedule])
@endif
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
            <span class="text-xs font-semibold text-gray-500">Loading…</span>
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
                Things to check
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
<div class="sheet hidden" id="dxMenuSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="dxMenuTitle">Extra expense</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dxm-what" id="dxMenuWhat"></div>
        <button type="button" class="dxm-item" data-dxm="edit">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Edit<span class="dxm-sub">Change the amount or the note</span></span>
        </button>
        <button type="button" class="dxm-item" data-dxm="move">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Move to another day<span class="dxm-sub">It keeps its amount and note</span></span>
        </button>
        <button type="button" class="dxm-item dxm-danger" data-dxm="delete">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
            <span>Delete<span class="dxm-sub">You will be asked to confirm</span></span>
        </button>
    </div>
</div>

{{-- What can be done to a note. The same shape the expense row's menu has,
     because it is the same act: one line saying which note this is about,
     then the two things there are to do to it. --}}
<div class="sheet hidden" id="noteMenuSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dxm-what" id="noteMenuWhat"></div>
        <button type="button" class="dxm-item" data-nm="edit">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Edit<span class="dxm-sub">Open the note and change what it says</span></span>
        </button>
        <button type="button" class="dxm-item dxm-danger" data-nm="delete">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
            <span>Delete<span class="dxm-sub">You will be asked to confirm</span></span>
        </button>
    </div>
</div>

<div class="sheet hidden" id="dxMoveSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title">Move to another day</h3>
            <p class="text-xs text-gray-500 mt-0.5" id="dxMoveSubtitle"></p>
        </div>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="dxMoveBody"></div>
</div>

{{-- What can be done to a shed note on a day: the dx menu's shape, because
     it is the same act on a different ledger. The day list is borrowed from
     dxMoveSheet — one asker at a time, the contexts null each other. --}}
<div class="sheet hidden" id="ivnMenuSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Stock record</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dxm-what" id="ivnMenuWhat"></div>
        <button type="button" class="dxm-item" data-ivnm="edit">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Edit<span class="dxm-sub">Change the amount, the day or the note</span></span>
        </button>
        <button type="button" class="dxm-item" data-ivnm="move">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Move to another day<span class="dxm-sub">It keeps its amount and note</span></span>
        </button>
        <button type="button" class="dxm-item dxm-danger" data-ivnm="delete">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
            <span>Delete<span class="dxm-sub">You will be asked to confirm</span></span>
        </button>
    </div>
</div>

@include('sm.partials.draw-canvas')
@include('sm.partials.note-editor')
@include('sm.partials.note-lightbox')
@include('sm.partials.attach-media')

{{-- The eight inks a star can be dipped in.
     Declared once for the page and pointed at by id from the stylesheet, so
     a board holding two hundred stars holds one copy of each gradient. Kept
     out of the flow (not display:none — a hidden paint server is still a
     valid one, but a zero-size svg is the safer way to say it). --}}
<svg id="actStarInks" width="0" height="0" aria-hidden="true" focusable="false"
     style="position:absolute;width:0;height:0;overflow:hidden">
    <defs>
        <linearGradient id="actStar1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8fd14f"/><stop offset="1" stop-color="#2f5219"/></linearGradient>
        <linearGradient id="actStar2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fde047"/><stop offset="1" stop-color="#b45309"/></linearGradient>
        <linearGradient id="actStar3" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fdba74"/><stop offset="1" stop-color="#c2410c"/></linearGradient>
        <linearGradient id="actStar4" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fda4af"/><stop offset="1" stop-color="#be123c"/></linearGradient>
        <linearGradient id="actStar5" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f0abfc"/><stop offset="1" stop-color="#a21caf"/></linearGradient>
        {{-- Indigo rather than violet: beside Orchid, a violet Dusk was the
             same purple twice and the two names stopped meaning anything. --}}
        <linearGradient id="actStar6" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a5b4fc"/><stop offset="1" stop-color="#3730a3"/></linearGradient>
        <linearGradient id="actStar7" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7dd3fc"/><stop offset="1" stop-color="#0369a1"/></linearGradient>
        <linearGradient id="actStar8" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6ee7b7"/><stop offset="1" stop-color="#0f766e"/></linearGradient>
    </defs>
</svg>
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
        {{-- A tokenless schedule (old rows, seeded data) must not 500 the
             whole board over a share link. --}}
        scheduleUrl: @json($schedule->shareToken ? route('share.schedule', $schedule->shareToken) : null),
        title: @json($schedule->title),
        scheduleId: {{ $schedule->id }},
        emailUrl: @json(route('sm.quick-share.email')),
    };
    const MODULES = {
        activities:    { label: 'Activities',    url: @json(route('sm.activities',    ['id' => $schedule->id])) },
        settings:      { label: 'Settings',      url: @json(route('sm.settings',      ['id' => $schedule->id])) },
        lots:          { label: 'Lots',          url: @json(route('sm.lots',          ['id' => $schedule->id])) },
        @if (! $isWorker)
        workers:       { label: 'Workers',       url: @json(route('sm.workers',       ['id' => $schedule->id])) },
        @endif
        {{-- Fresh every time: what is on the shelf changes whenever an
             activity is ticked done, and a kept pane would go on showing
             the stock as it stood when the module was first opened. --}}
        @if ($may('inventory'))
        inventory:     { label: 'Inventory',     url: @json(route('sm.inventory',     ['id' => $schedule->id])), fresh: true },
        @endif
        @if (! $isWorker)
        documentation: { label: 'Documentation', url: @json(route('sm.documentation', ['id' => $schedule->id])) },
        @endif
        @if ($may('observations'))
        {{-- Fresh: the Harvest sheet files observations from the board, and
             a kept pane would not show them. --}}
        'post-harvest': { label: 'Observations', url: @json(route('sm.post-harvest',  ['id' => $schedule->id])), fresh: true },
        @endif
        {{-- Tags ride the board's own forms, so the door follows the board. --}}
        @if ($may('activities'))
        'tags': { label: 'Tags', url: @json(route('sm.tags', ['id' => $schedule->id])) },
        @endif
        @if ($may('notes'))
        notes:         { label: 'Notes',         url: @json(route('sm.notes',        ['id' => $schedule->id])) },
        @endif
        // sticky: the pane holds a booted Google map that cannot survive
        // re-injection (its script guards itself against running twice), so a
        // deep link is HANDED to the kept pane via window.smModuleDeepLink
        // instead of re-fetching it.
        {{-- Closed HERE rather than only in the menus: this table is what a
             ?module= deep link is answered from, so a module left in it is
             still one URL away however few doors show. (The middleware
             refuses it either way; this is about not offering a door that
             would only say no.) --}}
        {{-- maps and draw left this table on 2026-09-30: they are Global and
             Quick Tools (/app/maps, /app/draw), and their old addresses
             redirect there. --}}
        // Kept as an alias: 'media' was the Media Box, which is now the
        // Gallery's first tab. Old links and bookmarks still land right.
        media:         { label: 'Gallery',       url: @json(route('sm.gallery',      ['id' => $schedule->id])) },
        // fresh: the page is a computed reading of the other modules' data
        // (lot day types, day-zero activities). A pane kept from before a lot
        // changed goes on reporting the old counter, so it re-fetches on
        // every open instead of being served from the cache.
        growth:        { label: 'Growth Stages', url: @json(route('sm.growth',       ['id' => $schedule->id])), fresh: true },
        gallery:       { label: 'Gallery',       url: @json(route('sm.gallery',      ['id' => $schedule->id])) },
        weather:       { label: 'Weather',       url: @json(route('sm.weather.page', ['id' => $schedule->id])) },
        // fresh: the reports read the season as it stands right now.
        @if ($may('reports'))
        reports:       { label: 'Reports',       url: @json(route('sm.reports',      ['id' => $schedule->id])), fresh: true },
        @endif
        @if ($may('ai'))
        ai:            { label: 'Chat Anee',     url: @json(route('sm.ai',           ['id' => $schedule->id])) },
        @endif
    };

    // The address bar always stays on the Activities shell (…/sm-activities?id=X
    // with ?module=key for non-Activities modules) so a refresh reloads the
    // single-page shell, never the standalone module page. The partial fetch
    // still uses each module's own URL (see showModule).
    /* What still works with no signal. The captures and the voice recorder
       are not here because they are not rooms - they are the buttons on this
       board, and they keep their own work in the outbox. */
    const OFFLINE_ROOMS = new Set(['activities', 'notes', 'growth']);

    const ACTIVITIES_URL = MODULES.activities.url;
    /* Where the shell was opened from (?from=dashboard, ?from=tags), kept on
       the address the shell rewrites, so a refresh still knows where the
       header's back arrow goes. BackTo only honours its own short list. */
    const SHELL_FROM = @json(\App\Support\BackTo::key());
    const shellUrl = (key) => {
        let u = key === 'activities'
            ? ACTIVITIES_URL
            : ACTIVITIES_URL + (ACTIVITIES_URL.includes('?') ? '&' : '?') + 'module=' + key;
        if (SHELL_FROM) u += (u.includes('?') ? '&' : '?') + 'from=' + SHELL_FROM;
        return u;
    };

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
    /* Where a deep link came from.
     *
     * Tapping a drawing in a note is not "go to the Drawings module" — it is
     * "show me this drawing", and when you are done with it the place you
     * want back is the note. The module you were in when you asked is
     * remembered here so the thing you opened knows where to return you. */
    let cameFrom = null;

    /* WHERE BACK GOES.
     *
     * One button serves every module in this shell, and it used to say
     * "Activities" and mean it — so opening the AI Technician from the
     * cropping schedule's own page and pressing back landed you somewhere
     * you had never been. Which is fine when you DID come from the board,
     * and a small lie the rest of the time.
     *
     * Kept apart from cameFrom above, which is the deep-link origin the map
     * and the drawing pad read to return somebody to the note that sent
     * them. This is a plainer thing: the screen that was showing when this
     * one was opened.
     */
    let backTo = null;          // a module key inside this shell
    let enteredByLink = false;  // the shell opened straight into a module
    /* The module on screen and the one under it, kept for this tab, so a
       module come back to without its history entry (a reload while a sheet
       was open, a page walked back to that the browser had to load again)
       still knows it was opened from inside the season. */
    const TRAIL_KEY = 'sm-trail:' + @json((int) $schedule->id);
    const keepTrail = (key) => {
        try {
            if (key === 'activities') { sessionStorage.removeItem(TRAIL_KEY); return; }
            const prev = (backTo && MODULES[backTo] && backTo !== key) ? backTo : (enteredByLink ? null : 'activities');
            sessionStorage.setItem(TRAIL_KEY, JSON.stringify({ m: key, p: prev }));
        } catch (_) { /* private mode: the history entry still carries it */ }
    };
    const readTrail = (key) => {
        try {
            const t = JSON.parse(sessionStorage.getItem(TRAIL_KEY) || 'null');
            return t && t.m === key && t.p && MODULES[t.p] ? t.p : null;
        } catch (_) { return null; }
    };
    /* Two moves must not leave a trail behind them: the first paint of a deep
     * link, because `current` says 'activities' before anything has been
     * shown and the reader has not seen it; and a press of Back itself,
     * because recording where you came FROM while going back is how a back
     * button ends up bouncing between two screens for ever. */
    let noTrail = false;
    /* Out of the shell: the season's hub, unless the shell was opened from
       somewhere else that said so (the dashboard's Open Activities, a tag).
       The header's back arrow is painted from the same answer. */
    const HUB_URL = @json(\App\Support\BackTo::url(route('sm.hub', ['id' => $schedule->id]), $schedule->id));

    /** Say where back goes, so the word on the button is not a guess. */
    function paintModuleBack() {
        const btn = document.getElementById('moduleBackBtn');
        if (!btn) return;
        const word = btn.querySelector('span');
        // A second button saying "Schedule" is not worth its place in the row:
        // the app header's own back arrow, directly above it, already goes
        // exactly there. It earns its keep only when it names a screen inside
        // this shell — somewhere the header cannot take you.
        btn.dataset.homeward = backTo && MODULES[backTo] ? '' : '1';
        if (backTo && MODULES[backTo]) {
            if (word) word.textContent = MODULES[backTo].label;
            btn.title = 'Back to ' + MODULES[backTo].label;
        } else if (enteredByLink) {
            if (word) word.textContent = 'Season';
            btn.title = 'Back to the season';
        } else {
            if (word) word.textContent = 'Activities';
            btn.title = 'Back to activities';
        }
    }

    window.smCameFrom = () => cameFrom;
    // The origin's own name, so a module that offers a way back can say where
    // back goes instead of hoping the reader guesses. A button labelled
    // "All maps" that lands on the Gallery is a lie whichever place is right.
    window.smOriginLabel = () => (cameFrom && MODULES[cameFrom]) ? MODULES[cameFrom].label : null;
    window.smReturnToOrigin = () => {
        const back = cameFrom;
        cameFrom = null;
        if (back && MODULES[back]) { showModule(back, true); return true; }
        return false;
    };

    async function showModule(key, push = true, extra = '') {
        if (!MODULES[key]) { closeModulesSheetForNav(); return; }
        /* Out of signal, the rooms that ask something of the far end stay
           shut — and are SAID to be shut. Letting the fetch run instead
           threw, and the catch below lands you back on the board with no
           explanation, which is what "I tap Lots and it goes to Activities"
           felt like for every module that was not on the shelf. */
        /* Out of signal, only the rooms a farm day needs from the field
           open: the board, the notebook and the growth stages. Everything
           else is refused at the door with the offline card rather than
           opened onto a screen that cannot do its job. */
        if (window.aneeOffline?.isDown?.() && !OFFLINE_ROOMS.has(key)) {
            closeModulesSheetForNav();
            window.aneeOffline.sayLocked(MODULES[key].label);

            return;
        }
        // Only a deep link sets this, and only from somewhere else.
        cameFrom = extra && current && current !== key ? current : (extra ? cameFrom : null);
        // Every move inside the shell is remembered, deep link or not — this
        // is what the back button reads, and it is a different question from
        // the one cameFrom answers.
        if (noTrail) { noTrail = false; } else if (current && current !== key) { backTo = current; }
        // What to do with the pane once the module is on screen — a sticky
        // module takes its deep link by hand instead of by re-fetch.
        let handOffExtra = null;
        if (extra) {
            const handler = MODULES[key].sticky && loaded.has(key)
                && window.smModuleDeepLink && window.smModuleDeepLink[key];
            if (handler) {
                // The kept pane cannot be rebuilt (it owns a booted map), so
                // the query is delivered to it after it is shown again.
                handOffExtra = () => handler(extra);
            } else {
                const had = loaded.get(key);
                if (had) { had.remove(); loaded.delete(key); }
                if (key === current) current = null;
            }
        }
        // A fresh module is fetched every time it is opened — its page is a
        // reading of data the other modules change, and the kept pane keeps
        // saying whatever was true when it was first loaded. (fresh and
        // sticky must never meet on one module: dropping the pane here would
        // leave handOffExtra above holding a handler from the dead copy.)
        if (MODULES[key].fresh && loaded.has(key)) {
            loaded.get(key).remove();
            loaded.delete(key);
            if (key === current) current = null;
        }
        // Only refuse to re-open a module that is genuinely showing. The flag
        // and the screen can come apart — a popstate that names a module, a
        // load that ended badly — and when they do, refusing on the flag alone
        // leaves a button that does nothing for the rest of the session.
        // A deep link still gets delivered: "already open" is not "already
        // showing the thing the link names".
        if (key === current && isShowing(key)) { closeModulesSheetForNav(); if (handOffExtra) handOffExtra(); return; }
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
                // The deep link rides along, or the re-fetch lands on the
                // module's front door instead of the thing that was tapped.
                return showModule(key, push, extra);
            }
        } else {
            // Named, because an anonymous spinner under a stale headline read
            // as the wrong wait: "Loading Maps…" says what is actually coming.
            loaderLabel.textContent = 'Loading ' + (MODULES[key].label || '') + '…';
            loader.classList.remove('hidden');
            // Nothing moves behind it. A page that scrolls under a full-screen
            // wait is a page being read while it is still being built — and at
            // the bottom of a long one the reader scrolls straight out from
            // under the overlay and finds the half-made thing it was covering.
            document.documentElement.classList.add('mod-held');
            // A different reminder each time, and the clock starts now.
            window.rollWaitLine?.(loader.querySelector('.bv-card'));
            host.classList.add('hidden');
            try {
                const sep = MODULES[key].url.includes('?') ? '&' : '?';
                const res = await fetch(MODULES[key].url + sep + 'partial=1' + (extra ? '&' + extra : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
                });
                if (res.status === 403) {
                    // The farm's plan lacks this module: the sheet, not a toast.
                    let lock = null;
                    try { lock = await res.clone().json(); } catch (_) {}
                    if (lock && lock.tierLock) { window.aneeUpgrade?.(lock.message, lock.tier); throw Object.assign(new Error(''), { quiet: true }); }
                }
                if (!res.ok) throw new Error('Could not load ' + MODULES[key].label);
                const wrap = document.createElement('div');
                wrap.dataset.module = key;
                wrap.innerHTML = await res.text();
                host.appendChild(wrap);
                loaded.set(key, wrap);
                runScripts(wrap);
                host.classList.remove('hidden');
            } catch (err) {
                /* Offline, this is not a failure worth a stack of jargon: it
                   is a room that was never put on the shelf. Say which, so
                   the next warm can be trusted to have fixed it. */
                if (!err.quiet) toast(window.aneeOffline?.isDown?.()
                    ? (MODULES[key].label || 'That module') + ' is not saved on this phone yet. Open it once online and it will work offline next time.'
                    : (err.message || 'Could not load that module.'), 'error');
                activitiesRoot.classList.remove('module-hidden');
                key = 'activities';
            } finally {
                /* Not the instant the fetch lands.
                 *
                 * A module that comes back in eighty milliseconds still
                 * flashes a card nobody could read, and a module that comes
                 * back exactly as its own widgets are settling shows a page
                 * mid-arrangement. waitCardRelease gives every wait a full
                 * second and a very short one a further half — a floor on
                 * how long the card is up, never a delay added to real
                 * work. */
                const hide = () => {
                    loader.classList.add('hidden');
                    document.documentElement.classList.remove('mod-held');
                };
                if (window.waitCardRelease) window.waitCardRelease(loader, hide);
                else hide();
            }
        }

        current = key;
        keepTrail(key);
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
            return showModule(key, push, extra);
        }

        // The kept pane has the screen again — hand it the deep link it was
        // asked for, now that the thing that answers it is visible.
        if (handOffExtra) handOffExtra();
        // The board is the page itself — its deep links (a tag's activity,
        // a day an expense lives on) are handed over rather than fetched.
        if (key === 'activities' && extra) window.smBoardSpot?.(extra);

        // The button says what it opens, not where you are — so it reads the
        // same in every module and the switch has nothing to rewrite. The app
        // header below is where the module's own name is kept in step.
        label.textContent = 'Modules';
        // Keep the app header + browser tab in step with the swapped module.
        const pageTitle = document.getElementById('appPageTitle');
        if (pageTitle) pageTitle.textContent = MODULES[key].label;
        // The guide follows the module: the shell swaps content without a
        // reload, and a help button still pointing at Activities would explain
        // the wrong screen.
        window.smHelpKey?.(key);
        document.title = MODULES[key].label + ': ' + @json($schedule->title);
        setActivitiesChrome(key === 'activities');
        // Show a way back whenever another module is open, and say where to —
        // paintModuleBack decides whether it is worth showing at all, so it
        // runs first and the class below reads its answer.
        paintModuleBack();
        const backBtn = document.getElementById('moduleBackBtn');
        backBtn?.classList.toggle('hidden', key === 'activities' || backBtn.dataset.homeward === '1');
        // The AI module IS the technician chat — hide the floating one there.
        document.getElementById('aiFloat')?.classList.toggle('ai-float-off', key === 'ai');
        // The team-chat bubble is off in every module now (see
        // schedule-chat-float), so there is nothing for the shell to toggle:
        // Workers opens the chat from a worker's card, the Collab Room docks
        // it into its Chat tab, and no module carries a floating button.
        // ...and it takes the toolbar's row, so the arrow has to lead home.
        aiBackArrow(key === 'ai');
        // On phones the fab hides only while the ACTIVITIES module is showing
        // (its Tools menu takes over). The other modules keep the bubble —
        // their Tools hamburger is hidden with the rest of the activities
        // chrome, so without the fab they would have no way into the chat.
        document.body.classList.toggle('act-module-open', key === 'activities');
        document.querySelectorAll('#modulesSheet .module-nav-row').forEach((row) => {
            row.querySelector('.module-nav-check')?.classList.toggle('hidden', row.dataset.module !== key);
            row.classList.toggle('is-here', row.dataset.module === key);
        });
        // Tell the module it is on screen — a kept pane may want to refresh
        // what it shows (the Maps shelf re-reads its saved maps, for one).
        document.dispatchEvent(new CustomEvent('sm:module-shown', { detail: { key } }));
        // The admin panel's Interests report hears which module came up: the shell swaps modules without a page
        // load (App\Support\PageViews::seen, 2026-11-23). Sent once the address below is the module's own; the
        // server drops a repeat of the page load that just showed the same module.
        setTimeout(() => {
            try {
                fetch(@json(route('pageviews.seen')), {
                    method: 'POST', keepalive: true, credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ path: location.pathname + location.search, title: document.title }),
                }).catch(() => {});
            } catch (_) { /* a lost count is a lost count */ }
        }, 0);
        // The entry says how deep it is and which module was under it, so a
        // reload on it (or a page left and walked back to) still knows the way
        // back (2026-11-03, the owner: Back from Lots went to the season's
        // dashboard instead of Activities).
        if (push) { pushDepth++; history.pushState({ module: key, depth: pushDepth, prev: backTo && MODULES[backTo] ? backTo : 'activities' }, '', shellUrl(key)); }
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

    /* While the technician is showing, the masthead's back arrow means "back
     * to the board" rather than "back to the hub": the toolbar that used to
     * carry that button is hidden, and a screen you can only leave by going
     * up two levels is a trap. Its own href is put back on the way out. */
    function aiBackArrow(on) {
        const a = document.getElementById('appBackLink');
        if (!a) return;
        if (on) {
            if (!a.dataset.hubHref) a.dataset.hubHref = a.getAttribute('href') || '';
            /* Where the arrow leads is the same question the module's own
               back button answers, and the two must not disagree: somebody
               who reached the AI tab from the schedule's page is not sent to
               a board they have not seen. */
            const home = (backTo && MODULES[backTo]) ? backTo : (enteredByLink ? null : 'activities');
            a.setAttribute('href', home ? shellUrl(home) : (a.dataset.hubHref || HUB_URL));
            const word = home ? MODULES[home].label : 'the season';
            a.setAttribute('aria-label', 'Back to ' + word);
            a.title = 'Back to ' + word;
        } else if (a.dataset.hubHref) {
            a.setAttribute('href', a.dataset.hubHref);
            a.setAttribute('aria-label', 'Back');
            a.removeAttribute('title');
        }
    }
    /* ---- THE MIRROR ----------------------------------------------------
     * The whole plan, held up to be read: every day, every activity, the
     * hidden ones too, the done ones ticked — and not one thing that can be
     * changed. It exists because the working board answers "is that done?"
     * badly: you re-filter, un-hide, unfold, look, then put all three back.
     *
     * It is built by copying the board's own days rather than fetching the
     * plan again, so what it shows IS what the board holds — the same lot
     * tags, the same day numbers, the same money, with no second renderer to
     * drift out of step. The copy is then made inert three times over:
     *   1. everything that acts is deleted out of it,
     *   2. the CSS refuses pointer events to what is left,
     *   3. a capture-phase listener stops any click that still happens from
     *      ever reaching the board's delegated handlers underneath.
     * All three make one exception, by name, for the three-dot menus: a menu
     * is the way to an action rather than the action itself, it gates its own
     * rows the same way here as on the board, and being sent back to the
     * board to reach one is the errand this screen exists to save.
     * The panel also lives OUTSIDE #activitiesList, which is what every one
     * of those handlers scopes its queries to — so the copies are invisible
     * to them by construction. */
    (() => {
        const panel = document.getElementById('mirrorPanel');
        const openBtn = document.getElementById('mirrorBtn');
        if (!panel || !openBtn) return;
        const body = document.getElementById('mirrorBody');
        const none = document.getElementById('mirrorNone');
        const load = document.getElementById('mirrorLoad');
        const count = document.getElementById('mirrorCount');
        const qInput = document.getElementById('mirrorQuery');
        const range = document.getElementById('mirrorRange');
        const fromInput = document.getElementById('mirrorFrom');
        const toInput = document.getElementById('mirrorTo');
        const lotsBtn = document.getElementById('mirrorLotsBtn');
        const typesBtn = document.getElementById('mirrorTypesBtn');
        const pick = document.getElementById('mirrorPick');
        const pickBody = document.getElementById('mirrorPickBody');
        const pickTitle = document.getElementById('mirrorPickTitle');
        const lotsOn = new Set();
        const typesOn = new Set();
        const tagsOn = new Set();
        const tagNameById = new Map();
        function fillTagNames() {
            if (!window.smTags) return Promise.resolve();
            return smTags.all().then((all) => {
                tagNameById.clear();
                (all || []).forEach((t) => tagNameById.set(String(t.id), t.name));
            }).catch(() => {});
        }
        // id/slug -> the name a person would call it, read off the board.
        const lotNameById = new Map();
        const typeNameBySlug = new Map();
        let picking = 'lots';
        const find = document.getElementById('mirrorFind');
        const findSay = document.getElementById('mirrorFindSay');
        const findN = document.getElementById('mirrorFindN');
        const foldBtn = document.getElementById('mirrorFoldBtn');
        const foldLabel = document.getElementById('mirrorFoldLabel');
        const todayBtn = document.getElementById('mirrorTodayBtn');
        let mode = 'all';
        // Read off the board before its markers are stripped from the copies;
        // only a nicety now that MIR_TODAY is the real answer.
        let todayKey = '';
        // The same Asia/Manila date the board counts its days from.
        const MIR_TODAY = @json(now('Asia/Manila')->toDateString());

        // Anything that acts, in a place meant only for reading.
        // The chevron stays: folding a day shut is reading, not editing, and
        // on a season of seventy days it is how you get past what you have
        // already checked. The mirror runs it itself (see the click handler).
        /* The three dots stay.
         *
         * Everything else in here is stripped because the mirror is for
         * reading — but a menu is not an action, it is the way to one, and
         * being told "you'd have to go back to the board for that" is the
         * errand this screen exists to save. The sheets they open gate their
         * own rows exactly as they do on the board (a worker sees the same
         * menu in both places, or no dots at all), and their handlers are
         * delegated on `document`, so the copies reach them unchanged.
         *
         * Both kebabs wear a class that is otherwise stripped wholesale —
         * `.date-header-btn` and `.icon-btn` — so each is excepted by name
         * rather than the family being spared. */
        const STRIP = [
            '.date-header-btn:not(.day-menu-btn)', '.date-header-stage',
            '.date-header-weather', '.wx-mini-btn', '.rest-day-add-btn',
            '.icon-btn:not(.card-menu-btn)', '.note-kebab',
            '.note-fold-btn', '.act-fab-add', '.group-add-activity-btn',
            // The money strips carry their own kebab, drag grip and buttons
            // (dx-* — see the expense/income blocks in activities-js). The
            // figures stay; the handles on them do not.
            '.dx-kebab', '.dx-grip', '.dx-menu', '.dx-actions', '.dx-btn', '.dx-edit', '.dx-del',
            '.inline-note-grip', '.dd-btn',
        ].join(',');

        /* Which lots this plan actually uses, read off the board's own tags so
         * the list cannot name a lot the plan has never heard of. */
        /* What this plan actually uses — read off the board itself, so neither
         * list can offer a lot the farm has never worked or a kind of work
         * the plan has never scheduled. */
        function readVocabulary() {
            lotNameById.clear();
            typeNameBySlug.clear();
            document.querySelectorAll('#activitiesList .activity-card').forEach((c) => {
                c.querySelectorAll('.lot-tag[data-lot-id]').forEach((t) => {
                    const id = t.getAttribute('data-lot-id');
                    /* data-lot-name, not textContent: the chip carries the
                       lot's day count as well now, so reading what it says
                       would have offered "Apratado 1DAT+24" as a lot to
                       filter by - a name that matches nothing. */
                    const name = (t.getAttribute('data-lot-name') || '').trim();
                    if (id && name && !lotNameById.has(id)) lotNameById.set(id, name);
                });
                // A card can carry several kinds at once (data-activity-types
                // is the whole list; data-activity-type only the first).
                const slugs = (c.getAttribute('data-activity-types')
                    || c.getAttribute('data-activity-type') || '').split(',');
                slugs.map((s) => s.trim()).filter(Boolean).forEach((slug) => {
                    if (typeNameBySlug.has(slug)) return;
                    /* The board's own label for a kind of work lives in a
                     * badge on the card, but only for the plain kinds —
                     * irrigation, service and payroll draw something else
                     * instead. So the slug is made readable when there is no
                     * badge to borrow: "foliar_spray" → "Foliar spray". */
                    let label = '';
                    if (slugs.length === 1) {
                        label = (c.querySelector('.activity-type-badge')?.textContent || '').trim();
                    }
                    if (!label) {
                        label = slug.replace(/[_-]+/g, ' ');
                        label = label.charAt(0).toUpperCase() + label.slice(1);
                    }
                    typeNameBySlug.set(slug, label);
                });
            });
        }

        function setAndNames(which) {
            if (which === 'lots') return [lotsOn, lotNameById];
            if (which === 'tags') return [tagsOn, tagNameById];
            return [typesOn, typeNameBySlug];
        }
        function chosenNames(which) {
            const [set, names] = setAndNames(which);
            return [...set].map((k) => names.get(k) || k);
        }

        /* Both tags say what they are narrowing to rather than just how many:
         * "Apartado 1" is the answer to "which lot?", where "1 selected" is
         * only the answer to "did I pick anything?". Past two it counts,
         * because three names do not fit and would only trail off. */
        function sayPicks() {
            [['lots', lotsBtn, 'Every lot', 'lots'],
             ['types', typesBtn, 'Activity type', 'types'],
             ['tags', document.getElementById('mirrorTagsBtn'), 'Tags', 'tags']].forEach(([which, btn, empty, word]) => {
                if (!btn) return;
                const names = chosenNames(which);
                const t = btn.querySelector('.mir-pickbtn-t');
                t.textContent = !names.length ? empty
                    : names.length <= 2 ? names.join(', ')
                    : names.length + ' ' + word;
                btn.classList.toggle('is-set', names.length > 0);
            });
        }

        function openPick(which) {
            picking = which;
            const [set, names] = setAndNames(which);
            pickTitle.textContent = which === 'lots' ? 'Which lots?' : (which === 'tags' ? 'Which tags?' : 'Which activity types?');
            pickBody.innerHTML = '';
            [...names.entries()].sort((a, b) => a[1].localeCompare(b[1])).forEach(([key, name]) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'mir-lot' + (set.has(key) ? ' is-on' : '');
                b.textContent = name;
                b.setAttribute('aria-pressed', set.has(key) ? 'true' : 'false');
                b.addEventListener('click', () => {
                    // Each tag is its own yes/no, so several can be asked
                    // about at once; none chosen means all of them.
                    const on = !set.has(key);
                    if (on) set.add(key); else set.delete(key);
                    b.classList.toggle('is-on', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                    sayPicks();
                    sift();
                });
                pickBody.appendChild(b);
            });
            if (!names.size) {
                const p = document.createElement('p');
                p.className = 'mir-none';
                p.textContent = which === 'lots'
                    ? 'This plan has no lots on it yet.'
                    : 'No activity on this plan has a type yet.';
                pickBody.appendChild(p);
            }
            pick.hidden = false;
            pick.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => pick.classList.add('is-open'));
        }
        function closePick() {
            pick.classList.remove('is-open');
            setTimeout(() => { pick.hidden = true; pick.setAttribute('aria-hidden', 'true'); }, 280);
        }

        function build(refresh = false) {
            body.innerHTML = '';
            todayKey = (document.querySelector('#activitiesList .date-group.is-today')
                || {}).getAttribute?.('data-date') || '';
            readVocabulary();
            /* A rebuild after an edit keeps the question that was being asked.
             * Clearing the lot, type and tag picks here used to hand back the
             * whole season a tick later, at a different height, which is half
             * of why a tick moved the screen (the owner, 2026-09-29). Only
             * opening the mirror starts from nothing. */
            if (!refresh) {
                lotsOn.clear();
                typesOn.clear();
                tagsOn.clear();
            }
            sayPicks();
            const groups = document.querySelectorAll('#activitiesList .date-group[data-date]');
            groups.forEach((g) => {
                const copy = g.cloneNode(true);
                /* Open, unhidden, unfiltered. Show hidden, hide done days,
                 * hide empty dates, only-day-zero — those are settings on how
                 * you are LOOKING at the board, and the mirror does not
                 * inherit the squint. Whatever is switched on out there, the
                 * plan comes through here whole. */
                copy.classList.remove('all-hidden', 'group-collapsed',
                    'done-day-away', 'dz-away', 'filters-active', 'tt-highlight', 'is-today');
                /* Every day starts shut. Seventy days opened at once is a
                 * scroll with no landmarks in it; closed, the whole season
                 * is one screen of dates you can run an eye down, and the
                 * one you want opens with a tap. A search opens what it
                 * finds (see apply). */
                copy.classList.add('is-folded');
                copy.removeAttribute('style');
                copy.querySelectorAll(STRIP).forEach((el) => el.remove());
                copy.querySelectorAll('[draggable]').forEach((el) => el.removeAttribute('draggable'));
                // A hidden activity is exactly what somebody checking their
                // work needs to see, so the board's own hiding comes off —
                // and its fold with it: a mirror that has to be unfolded is
                // the errand this screen exists to save. The card keeps its
                // "Hidden" badge, which says the same thing without hiding.
                copy.querySelectorAll('.activity-card').forEach((c) => {
                    c.classList.remove('filter-hidden', 'dz-away', 'act-collapsed', 'is-hidden');
                    c.style.removeProperty('display');
                });
                copy.querySelectorAll('.rest-day-marker').forEach((r) => {
                    r.classList.remove('filters-active');
                    r.removeAttribute('style');
                });
                /* Notes open, but only the ones that exist. The board hides an
                 * empty note block with an inline display:none, and stripping
                 * that put an empty "Note" placeholder on every dayless day —
                 * so the hiding stays and only the fold is undone. */
                copy.querySelectorAll('.date-note-block').forEach((n) => {
                    if (n.style.display === 'none') return;
                    n.classList.add('is-open');
                    n.querySelectorAll('.note-fold').forEach((f) => { f.style.maxHeight = 'none'; });
                });
                /* Date Diff: pick two days and the gap between them is named
                 * on both. A plan is full of "how long after transplanting?"
                 * and "how many days did that take?", and counting squares on
                 * a calendar with a finger is how people get it wrong. Only a
                 * day that HAS a date can be measured to. */
                const key = copy.getAttribute('data-date') || '';
                const head = copy.querySelector('.date-header');
                if (head && key && key !== '__no-date__') {
                    const d = document.createElement('button');
                    d.type = 'button';
                    d.className = 'mir-diff';
                    d.dataset.date = key;
                    d.title = 'Count the days between this one and another';
                    d.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">'
                        + '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 4 4 4M16 7l4 4-4 4M4 11h16"/></svg>'
                        + '<span class="mir-diff-t">Count days</span>';
                    head.appendChild(d);
                }
                body.appendChild(copy);
            });
            // Date Diff picks were buttons in the old copies; carried over by
            // their dates on a refresh, let go on a fresh open.
            const diffDates = refresh ? diffPicks.map((b) => b.dataset.date) : [];
            diffPicks.length = 0;
            diffDates.forEach((d) => {
                const b = body.querySelector('.mir-diff[data-date="' + (window.CSS?.escape ? CSS.escape(d) : d) + '"]');
                if (b) diffPicks.push(b);
            });
            paintDiff();
            /* The copies were taken from the board and carry whatever it was
             * wearing, but a rebuild can land between a pick and its paint -
             * so the stretch is put back on the fresh ones. */
            window.cashRange?.paint();
            apply();
            // The copies inherit whatever the board decided about empty
            // menus, but a day can gain a saved reading between the two, so
            // the sweep is run over the fresh copies as well.
            window.syncDayKebabs?.(body);
        }

        /* An action taken from in here changes the board underneath, and the
         * copies were made before it did. Rebuilt rather than patched: a copy
         * is cheap and a half-right mirror is worse than a slow one. */
        let rebuildTimer = null;
        let rebuildAskedAt = 0;
        window.mirrorRefresh = () => {
            if (panel.hidden) return;
            // Debounced: one edit can touch the board half a dozen times as
            // the card is replaced, renumbered and re-sorted, and rebuilding
            // on each would be six copies of a season to throw five away.
            // But never starved: a board that keeps changing (a slow phone
            // still painting its days, a feed of edits) once pushed the
            // rebuild back for ever and the mirror went on showing the old
            // card. Once one has waited most of a second, it is let land.
            const now = performance.now();
            if (!rebuildTimer) rebuildAskedAt = now;
            else if (now - rebuildAskedAt > 900) return;
            clearTimeout(rebuildTimer);
            rebuildTimer = setTimeout(() => {
                rebuildTimer = null;
                if (panel.hidden) return;
                const openDays = new Set([...body.querySelectorAll('.date-group:not(.is-folded)[data-date]')]
                    .map((g) => g.getAttribute('data-date')));
                const anchor = mirrorAnchor();
                body.innerHTML = '';
                build(true);
                // Whatever was open stays open, and the eye keeps its place:
                // a refresh that folds the season shut and jumps to the top
                // is a worse answer than a stale card.
                openDays.forEach((d) => {
                    body.querySelector('.date-group[data-date="' + (window.CSS?.escape ? CSS.escape(d) : d) + '"]')
                        ?.classList.remove('is-folded');
                });
                syncFoldBtn();
                holdAnchor(anchor);
            }, 220);
        };

        /* WHERE THE EYE WAS, AS A THING RATHER THAN A NUMBER.
         *
         * Putting scrollTop back used to be the whole of it, and it could not
         * work: every day carries content-visibility:auto, so a fresh copy of
         * the season stands on ESTIMATED heights until each day is drawn, and
         * the same scrollTop then points somewhere else — a tick, or an edit
         * saved through the three dots, and the screen moved (the owner,
         * 2026-09-29). So the place is kept by what was there: the card last
         * touched, if it is still on screen, else the first day showing at
         * the top. After the rebuild that same thing is put back at the same
         * height, and checked again as the days around it settle. */
        let lastTouch = null;   // { id, at }
        body.addEventListener('pointerdown', (e) => {
            const card = e.target.closest?.('.activity-card[data-id]');
            lastTouch = card ? { id: card.getAttribute('data-id'), at: performance.now() } : null;
        }, true);
        function mirrorAnchor() {
            const top = body.getBoundingClientRect().top;
            const bottom = body.getBoundingClientRect().bottom;
            if (lastTouch && performance.now() - lastTouch.at < 120000) {
                const c = body.querySelector('.activity-card[data-id="' + (window.CSS?.escape ? CSS.escape(lastTouch.id) : lastTouch.id) + '"]:not(.mir-away)');
                const r = c?.getBoundingClientRect();
                if (r && r.bottom > top && r.top < bottom) return { card: lastTouch.id, offset: r.top - top };
            }
            const day = [...body.querySelectorAll('.date-group[data-date]:not(.mir-away)')]
                .find((g) => g.getBoundingClientRect().bottom > top + 1);
            return day ? { date: day.getAttribute('data-date'), offset: day.getBoundingClientRect().top - top } : null;
        }
        function holdAnchor(a) {
            if (!a) return;
            const find = () => (a.card
                ? body.querySelector('.activity-card[data-id="' + (window.CSS?.escape ? CSS.escape(a.card) : a.card) + '"]:not(.mir-away)')
                : null)
                || (a.date ? body.querySelector('.date-group[data-date="' + (window.CSS?.escape ? CSS.escape(a.date) : a.date) + '"]') : null);
            // A farmer who starts scrolling has taken the screen back.
            let theirs = false;
            const mine = () => { theirs = true; };
            ['wheel', 'touchmove', 'keydown'].forEach((t) => body.addEventListener(t, mine, { once: true, passive: true }));
            setTimeout(() => ['wheel', 'touchmove', 'keydown'].forEach((t) => body.removeEventListener(t, mine)), 400);
            const put = () => {
                if (theirs) return;
                const el = find();
                if (!el) return;
                const now = el.getBoundingClientRect().top - body.getBoundingClientRect().top;
                const drift = now - a.offset;
                if (Math.abs(drift) > 1) body.scrollTop += drift;
            };
            put();
            // The days either side draw themselves over the next frames and
            // can shift the ground once more; the place is re-held as they do.
            requestAnimationFrame(() => { put(); requestAnimationFrame(put); });
            setTimeout(put, 120);
            setTimeout(put, 320);
        }

        /* EVERY CHANGE, NOT THE ONES SOMEBODY REMEMBERED TO ANNOUNCE.
         *
         * Duplicating a card, editing one and saving, deleting, moving a day
         * — each takes a different road through the board, and only the ones
         * that end in a re-sort were calling mirrorRefresh. So an edit made
         * from the mirror's own three dots saved perfectly and the mirror
         * went on showing the old title.
         *
         * The board's list is watched instead. Whatever did the changing,
         * the copies are remade. Only while the mirror is open — the
         * observer is attached once and costs nothing when it returns
         * early. */
        const boardList = document.getElementById('activitiesList');
        if (boardList && window.MutationObserver) {
            // The done tick is an attribute flip, not a new node -- watched by
            // name, so a tick or an untick taken anywhere reaches the copies.
            new MutationObserver(() => { if (!panel.hidden) window.mirrorRefresh(); })
                .observe(boardList, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['data-is-done', 'data-is-hidden'] });
        }

        /* ---- Date Diff -------------------------------------------------
         * Two at a time, because a distance has two ends. Picking a third
         * lets the older of the two go rather than refusing the tap: the
         * gesture people make is "now compare THIS one to that one", and a
         * button that just declines leaves them hunting for which to undo. */
        const diffPicks = [];
        function toggleDiff(btn) {
            const at = diffPicks.indexOf(btn);
            if (at > -1) diffPicks.splice(at, 1);
            else {
                diffPicks.push(btn);
                if (diffPicks.length > 2) diffPicks.shift();
            }
            paintDiff();
        }
        function paintDiff() {
            let gap = null;
            if (diffPicks.length === 2) {
                const [a, b] = diffPicks.map((x) => x.dataset.date).sort();
                gap = Math.round(
                    (new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
            }
            body.querySelectorAll('.mir-diff').forEach((b) => {
                const on = diffPicks.includes(b);
                b.classList.toggle('is-on', on);
                b.classList.toggle('has-gap', on && gap !== null);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                const t = b.querySelector('.mir-diff-t');
                if (!t) return;
                // The number goes on BOTH ends of the measurement, so the
                // answer is there whichever one you are looking at.
                t.textContent = !on ? 'Count days'
                    : gap === null ? 'Pick another day'
                    : gap === 0 ? 'Same day'
                    : gap + (gap === 1 ? ' day apart' : ' days apart');
            });
        }

        /* What a day is asked, and what an activity is asked. A day matches on
         * its date; an activity on the text the board already indexed it by
         * (data-search) or on the day number printed in its lot meta. */
        function inRange(group) {
            const d = group.getAttribute('data-date') || '';
            const a = fromInput.value || '';
            const b = toInput.value || '';
            if (!a && !b) return true;
            if (!d || d === '__no-date__') return false;
            // Either end may be left open: a start alone reads "from then on",
            // an end alone "up to then". ISO dates compare as strings.
            if (a && d < a) return false;
            if (b && d > b) return false;
            return true;
        }

        function matches(group, card, q) {
            if (mode === 'date') return inRange(group);
            if (mode === 'all' || !q) return true;
            if (mode === 'text') return ((card.getAttribute('data-search') || '').toLowerCase()).includes(q);
            if (mode === 'day') {
                /* The board writes these with a sign — "DAS-2", "DAS0",
                 * "DAS+1" — because before and after the seeding are
                 * different days. So each tag is indexed twice: once as it
                 * reads, once with the sign taken out. "DAS+12" then answers
                 * to "das+12" and to "das12", and a bare "12" finds either
                 * side, which is what somebody who only remembers the number
                 * means. */
                const want = q.replace(/[\s·]/g, '');
                /* Read from the CHIP, which is where the count lives now.
                 * .lot-meta-tag is still asked because a variety is a word
                 * somebody may search the same way. */
                const meta = [...card.querySelectorAll('.lot-tag-das, .lot-meta-tag')].map((t) => {
                    const raw = (t.textContent || '').toLowerCase().replace(/[\s·]/g, '');
                    return raw + ' ' + raw.replace(/[+-]/g, '');
                }).join(' ');
                return meta.includes(want);
            }
            return true;
        }

        function apply() {
            // The shut card says the range the way the tags do — "Aug 25, 26",
            // not "2026-08-25", which is a key rather than a date.
            const said = (v) => (v ? new Date(v + 'T00:00:00').toLocaleDateString(undefined,
                { month: 'short', day: 'numeric', year: '2-digit' }) : '');
            const raw = mode === 'date'
                ? (fromInput.value || toInput.value
                    ? (said(fromInput.value) || 'the start') + ' → ' + (said(toInput.value) || 'the end')
                    : '')
                : (qInput.value || '').trim().toLowerCase();
            const asking = !!raw || lotsOn.size > 0 || typesOn.size > 0 || tagsOn.size > 0;
            let days = 0;
            let acts = 0;
            body.querySelectorAll('.date-group').forEach((g) => {
                const cards = [...g.querySelectorAll('.activity-card')];
                let shown = 0;
                cards.forEach((c) => {
                    // Lot and kind narrow whatever else is being asked rather
                    // than replacing it: "herbicide, on Apartado 1" is one
                    // question, and every part of it has to hold.
                    const onLot = lotsOn.size === 0
                        || [...lotsOn].some((id) => c.querySelector(`.lot-tag[data-lot-id="${id}"]`));
                    const kinds = (c.getAttribute('data-activity-types')
                        || c.getAttribute('data-activity-type') || '').split(',').map((s) => s.trim());
                    const onType = typesOn.size === 0 || kinds.some((k) => typesOn.has(k));
                    const onTag = tagsOn.size === 0
                        || String(c.getAttribute('data-tag-ids') || '').split(',').filter(Boolean).some((id) => tagsOn.has(id));
                    const ok = onLot && onType && onTag && matches(g, c, raw);
                    c.classList.toggle('mir-away', !ok);
                    if (ok) shown++;
                });
                // A date range keeps its days even where they hold nothing:
                // "that week is empty" is the answer, not a blank screen.
                const keep = mode === 'date' && raw && !lotsOn.size && !typesOn.size && !tagsOn.size
                    ? inRange(g)
                    : shown > 0;
                g.classList.toggle('mir-away', !keep);
                /* Everything opens shut, so a question has to open what it
                 * found — a day whose header alone is showing has not
                 * answered anything. Asking nothing shuts them again, which
                 * is the state the screen opens in. */
                if (asking) g.classList.toggle('is-folded', !(keep && shown > 0));
                else g.classList.add('is-folded');
                if (keep) { days++; acts += shown; }
            });
            none.classList.toggle('hidden', days > 0);
            count.textContent = asking
                ? `${acts} ${acts === 1 ? 'activity' : 'activities'} on ${days} ${days === 1 ? 'day' : 'days'}`
                : 'Every activity, for reading only';
            sayFilters(raw);
            syncFoldBtn();
        }

        /* What the shut card says it is asking. A filter you cannot see is a
         * filter you forget you set, and then the plan looks wrong. */
        function sayFilters(raw) {
            const bits = [];
            if (raw) bits.push((mode === 'day' ? 'Day ' : '') + raw);
            const names = [...chosenNames('lots'), ...chosenNames('types'), ...chosenNames('tags')];
            if (names.length) bits.push(names.join(', '));
            findN.textContent = String(bits.length);
            findN.classList.toggle('hidden', bits.length === 0);
            findSay.textContent = bits.length ? bits.join(' · ') : 'Search & filters';
        }

        /* Collapse all / expand all, and the label that tells the truth about
         * which one the next tap will do. */
        function syncFoldBtn() {
            const live = [...body.querySelectorAll('.date-group:not(.mir-away)')];
            const allShut = live.length > 0 && live.every((g) => g.classList.contains('is-folded'));
            foldBtn.dataset.folded = allShut ? '1' : '0';
            foldLabel.textContent = allShut ? 'Expand all' : 'Collapse all';
        }

        /* A filter that snaps is a different screen; one that settles is the
         * same screen answering. The list dips, the matching is done while it
         * is dim, and what survived comes back up. Typing is given a beat to
         * finish first — re-sifting on every keystroke of "herbicide" is nine
         * passes over the board for one question. */
        let siftTimer = null;
        function sift() {
            clearTimeout(siftTimer);
            body.classList.add('is-sifting');
            siftTimer = setTimeout(() => {
                apply();
                body.classList.remove('is-sifting');
                // Only the days still standing are asked to arrive; the
                // animation is re-armed by taking the class off first.
                body.querySelectorAll('.date-group:not(.mir-away)').forEach((g) => {
                    g.classList.remove('mir-sift-in');
                    void g.offsetWidth;
                    g.classList.add('mir-sift-in');
                });
            }, 160);
        }

        /* The screen arrives before the plan does. Showing the frame first and
         * copying a frame later is what keeps the opening smooth: a long
         * season is hundreds of nodes to clone, and doing it in the same beat
         * as the tap stalls the animation before it starts. The dots hold the
         * space while it happens. */
        function open() {
            fillTagNames().then(() => sayPicks());
            if (!panel.hidden) return;
            body.innerHTML = '';
            load.classList.add('is-on');
            none.classList.add('hidden');
            panel.hidden = false;
            panel.setAttribute('aria-hidden', 'false');
            // Sheets opened from in here have to clear the mirror; see the
            // html.mir-open rules beside .mir-body.
            document.documentElement.classList.add('mir-open');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                panel.classList.add('is-open');
                // Two frames on: the fade is under way and the paint is free.
                requestAnimationFrame(() => setTimeout(() => {
                    build();
                    load.classList.remove('is-on');
                }, 90));
            });
        }
        function close() {
            if (panel.hidden) return;
            if (!pick.hidden) closePick();
            panel.classList.remove('is-open');
            document.documentElement.classList.remove('mir-open');
            document.body.style.removeProperty('overflow');
            // Let the fade finish before the screen is taken away.
            setTimeout(() => {
                panel.hidden = true;
                panel.setAttribute('aria-hidden', 'true');
                body.innerHTML = '';    // copies are cheap; stale ones are not
            }, 280);
        }

        openBtn.addEventListener('click', open);
        document.getElementById('mirrorCloseBtn')?.addEventListener('click', close);
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape' || panel.hidden) return;
            // The picker is on top, so it is what Escape means first.
            if (!pick.hidden) { closePick(); return; }
            close();
        });

        panel.querySelectorAll('.mir-mode').forEach((b) => {
            b.addEventListener('click', () => {
                mode = b.getAttribute('data-mir-mode');
                panel.querySelectorAll('.mir-mode').forEach((x) => x.classList.toggle('is-on', x === b));
                qInput.classList.toggle('hidden', mode === 'all' || mode === 'date');
                range.classList.toggle('hidden', mode !== 'date');
                if (mode === 'all') { qInput.value = ''; fromInput.value = ''; toInput.value = ''; sayDates(); }
                if (mode === 'text') qInput.placeholder = 'Search activities, lots or items…';
                if (mode === 'day') qInput.placeholder = 'Day number, e.g. 12, DAS+12, DAT-3';
                sift();
                if (mode === 'text' || mode === 'day') window.smFocus?.(qInput, { delay: 60 });
            });
        });
        qInput.addEventListener('input', sift);
        fromInput.addEventListener('change', () => { sayDates(); sift(); });
        toInput.addEventListener('change', () => { sayDates(); sift(); });

        /* Each end of the range is a tag that summons the browser's own
         * calendar. showPicker() is the direct way and the phone's own
         * control answers it; where it is refused (an older browser, or a
         * call it does not consider user-driven) a plain click on the field
         * still opens the picker on mobile. */
        function summon(input) {
            try { input.showPicker(); } catch (_) { input.focus(); input.click(); }
        }
        function sayDates() {
            [[fromInput, 'mirrorFromT', 'From'], [toInput, 'mirrorToT', 'To']].forEach(([inp, id, empty]) => {
                const t = document.getElementById(id);
                const btn = t.closest('.mir-datebtn');
                if (!inp.value) { t.textContent = empty; btn.classList.remove('is-set'); return; }
                const d = new Date(inp.value + 'T00:00:00');
                t.textContent = d.toLocaleDateString(undefined,
                    { month: 'short', day: 'numeric', year: '2-digit' });
                btn.classList.add('is-set');
            });
            document.getElementById('mirrorDateClear')
                ?.classList.toggle('hidden', !fromInput.value && !toInput.value);
        }
        document.getElementById('mirrorFromBtn')?.addEventListener('click', () => summon(fromInput));
        document.getElementById('mirrorToBtn')?.addEventListener('click', () => summon(toInput));
        document.getElementById('mirrorDateClear')?.addEventListener('click', () => {
            fromInput.value = '';
            toInput.value = '';
            sayDates();
            sift();
        });

        lotsBtn?.addEventListener('click', () => openPick('lots'));
        typesBtn?.addEventListener('click', () => openPick('types'));
        document.getElementById('mirrorTagsBtn')?.addEventListener('click', () => openPick('tags'));
        document.getElementById('mirrorPickClose')?.addEventListener('click', closePick);
        document.getElementById('mirrorPickDone')?.addEventListener('click', closePick);
        document.getElementById('mirrorPickClear')?.addEventListener('click', () => {
            setAndNames(picking)[0].clear();
            pickBody.querySelectorAll('.mir-lot').forEach((b) => {
                b.classList.remove('is-on');
                b.setAttribute('aria-pressed', 'false');
            });
            sayPicks();
            sift();
        });
        // The ground behind the card dismisses it, the card itself does not.
        pick?.addEventListener('click', (e) => { if (e.target === pick) closePick(); });

        // The card itself folds, so the answer gets the screen once the
        // question has been asked.
        document.getElementById('mirrorFindHead')?.addEventListener('click', () => {
            const shut = find.classList.toggle('is-shut');
            document.getElementById('mirrorFindHead').setAttribute('aria-expanded', shut ? 'false' : 'true');
        });

        foldBtn?.addEventListener('click', () => {
            const openThem = foldBtn.dataset.folded === '1';
            body.querySelectorAll('.date-group:not(.mir-away)')
                .forEach((g) => g.classList.toggle('is-folded', !openThem));
            syncFoldBtn();
        });

        /* Today, in a list that can be seventy days long. It lands on the day
         * the board itself marked as today; failing that — a season that has
         * not started, or has ended — on the nearest day there is, because
         * "there is no today here" is better answered by showing where you
         * are than by refusing to move. */
        todayBtn?.addEventListener('click', () => {
            const live = [...body.querySelectorAll('.date-group:not(.mir-away)[data-date]')];
            if (!live.length) return;
            /* TODAY IS A DATE THE SERVER KNOWS, NOT ONE THE DOM MIGHT SHOW.
             *
             * This used to read the board's own `.is-today` marker, which is
             * only there when today HAS activities — and to fall back on
             * `new Date().toISOString()`, which is UTC and so reads yesterday
             * for most of a Manila morning. Aiming at the wrong day and then
             * hunting for the nearest one is how "go to today" landed several
             * days out. MIR_TODAY is the same Asia/Manila date the board
             * numbers its days from. */
            const aim = MIR_TODAY || todayKey;
            let target = live.find((g) => g.getAttribute('data-date') === aim);
            /* No group for today — an empty day, or one filtered away. The
             * next day that HAS something is what "today" means then: you are
             * asking what is coming, not what is behind you. Only if the
             * whole season is already past does it fall back to the last day
             * of it. */
            if (!target) {
                const after = live
                    .filter((g) => (g.getAttribute('data-date') || '') >= aim)
                    .sort((a, b) => (a.getAttribute('data-date') || '').localeCompare(b.getAttribute('data-date') || ''));
                target = after[0] || live[live.length - 1];
            }
            if (!target) return;
            target.classList.remove('is-folded');
            syncFoldBtn();
            /* The search card folds first. Open, it holds the top third of a
             * phone and the day landed pressed under it; the question has been
             * asked, so the answer gets the screen, as the card's own head
             * does. The scroll waits for the fold, which moves the list up. */
            const findHead = document.getElementById('mirrorFindHead');
            const wasOpen = !find.classList.contains('is-shut');
            if (wasOpen) {
                find.classList.add('is-shut');
                findHead?.setAttribute('aria-expanded', 'false');
            }
            const land = () => {
            /* The browser's own scrolling, not arithmetic of mine.
             *
             * A day card carries content-visibility:auto, so every day still
             * off screen stands on an ESTIMATE of its height rather than its
             * real one. Scrolling toward one therefore MOVES it: the days
             * passed on the way lay themselves out properly and the ground
             * shifts underneath. Measuring a distance and jumping it landed
             * seven weeks short, and re-measuring frame by frame never caught
             * up. scrollIntoView is told the element rather than a number, so
             * it is the one thing that cannot be made stale by the layout it
             * causes — measured at 0px off, and still 0 once settled. */
            /* Travelled, not teleported: the list runs to the day so the eye
             * keeps its place in the season rather than being cut to a screen
             * that looks like any other. Smooth is affordable now that the
             * days arrive shut — a folded list is short and its heights hold
             * still while the scroll is in flight. */
            const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            target.scrollIntoView({ block: 'start', behavior: smooth ? 'smooth' : 'auto' });
            // One more look once it has come to rest, in case the ground moved.
            // The day rests its scroll-margin below the top (see .mir-body .date-group).
            setTimeout(() => {
                const br = body.getBoundingClientRect();
                const tr = target.getBoundingClientRect();
                const air = parseFloat(getComputedStyle(target).scrollMarginTop) || 0;
                if (Math.abs(tr.top - br.top - air) > 12) target.scrollIntoView({ block: 'start' });
                // A brief rise so the eye knows which one it was brought to.
                target.classList.remove('mir-sift-in');
                void target.offsetWidth;
                target.classList.add('mir-sift-in');
            }, smooth ? 620 : 60);
            };
            if (wasOpen) setTimeout(land, 300); else land();
        });

        /* The third guard, and the mirror's own hands.
         *
         * Capture runs before a click can bubble back to the document
         * handlers that run the board, so stopping it here means a stray tap
         * on a copied card cannot open, tick, drag or delete the real one it
         * was copied from. The one gesture the mirror keeps for itself is
         * folding a day shut — reading, not editing — and it is served here,
         * inside the same guard, so it can never be mistaken for the board's
         * own fold. */
        body.addEventListener('click', (e) => {
            /* The three dots are the exception, and they have to be taken
             * before anything else here: this listener captures, so the
             * stopPropagation below would end the click before it ever
             * reached the button it was aimed at. Opening a menu is not
             * acting — the sheet it raises gates its own rows exactly as it
             * does on the board — so the click is let go on untouched to the
             * delegated handler in activities-js. */
            // ...and the done tick: checking work off is the mirror's job too.
            if (e.target.closest('.day-menu-btn, .card-menu-btn, .done-check')) return;
            e.stopPropagation();
            // Before the fold, because the diff tag sits inside the header it
            // would otherwise fold: a tap meant to measure must not shut the
            // day it is measuring from.
            const diff = e.target.closest('.mir-diff');
            if (diff) { e.preventDefault(); toggleDiff(diff); return; }
            /* THE DAY'S MONEY ASKS THE SAME QUESTION IT ASKS ON THE BOARD.
             *
             * Taken before the fold, for the reason the diff tag is: a tap
             * meant for the money must not shut the day it is about. While a
             * stretch is being totalled the pill is an end to pick; otherwise
             * it opens the chooser — this day's longhand, or a range starting
             * here — which is what the board does, and the mirror had a
             * button in its tools doing half of it instead. */
            const cash = e.target.closest('.date-header-cash');
            if (cash) {
                e.preventDefault();
                const g = cash.closest('.date-group');
                if (window.cashRange?.on()) {
                    window.cashRange.pick((g?.getAttribute('data-date') || '').trim());
                } else if (g) {
                    window.cashRange?.choose(g);
                }

                return;
            }
            const head = e.target.closest('.date-header');
            if (head) {
                const g = head.closest('.date-group');
                if (g) {
                    g.classList.toggle('is-folded');
                    syncFoldBtn();
                }
                return;
            }
            e.preventDefault();
        }, true);
        ['pointerdown', 'dragstart', 'submit'].forEach((type) => {
            body.addEventListener(type, (e) => { e.stopPropagation(); }, true);
        });
    })();

    /* Modules on a phone whose row is short of room: icon and word, else the
       word alone, else the icon alone, whichever fits whole (see the CSS by
       #actToolbar > #modulesBtn). Asked again whenever the row changes width. */
    (() => {
        const btn = document.getElementById('modulesBtn');
        const row = document.getElementById('actToolbar');
        if (!btn || !row || !('ResizeObserver' in window)) return;
        const over = () => btn.scrollWidth > btn.clientWidth + 1;
        const fitModulesBtn = () => {
            btn.classList.remove('mb-wordonly', 'mb-icononly');
            if (!btn.clientWidth || !over()) return;
            btn.classList.add('mb-wordonly');
            if (!over()) return;
            btn.classList.replace('mb-wordonly', 'mb-icononly');
        };
        new ResizeObserver(() => requestAnimationFrame(fitModulesBtn)).observe(row);
        fitModulesBtn();
    })();

    document.getElementById('modulesBtn')?.addEventListener('click', () => {
        /* Tick the row you are standing on, every time the sheet opens.
           showModule paints it on a switch, and nothing switches on first
           paint — so the board's own row went unticked until you had left it
           and come back, which is exactly when you least need telling. */
        document.querySelectorAll('#modulesSheet .module-nav-row').forEach((row) => {
            row.querySelector('.module-nav-check')?.classList.toggle('hidden', row.dataset.module !== current);
            row.classList.toggle('is-here', row.dataset.module === current);
        });
        openSheet('modulesSheet');
    });
    document.addEventListener('click', (e) => {
        const row = e.target.closest('#modulesSheet .module-nav-row');
        if (row) { showModule(row.dataset.module); return; }
        // ANY module link on the page stays in the shell — a note chip on the
        // activities board or in a read sheet is as much a module link as one
        // inside an injected partial, and letting it navigate tore the shell
        // down. The MODULES lookup below keeps every other link ordinary, and
        // so does a modified click that asks for a new tab.
        if (e.metaKey || e.ctrlKey || e.shiftKey) return;
        const link = e.target.closest('a[href]');
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
        /* Landing on the module we are already showing is not a navigation —
           it is the wake of an overlay entry collapsing (a sheet closed, and
           unregisterOverlay rewound its pushState). Re-showing it would put a
           fresh:true module through a teardown and refetch for the crime of
           cancelling a form. The depth bookkeeping stays untouched too: the
           module's own entry is the one we just landed on. */
        const target = (e.state && e.state.module) || 'activities';
        if (target === current) return;
        // The entry knows its own depth; one without (an older page) counts down.
        pushDepth = e.state && Number.isInteger(e.state.depth) ? e.state.depth : Math.max(0, pushDepth - 1);
        showModule(target, false);
    });

    /* The header chevron and the phone's Back button disagreed: Back walked
       module by module, the chevron jumped straight out to the hub. It now
       unwinds the same stack — one module at a time while there is one to
       unwind, and only then out to wherever the page says it came from
       (which, for a hub tile that deep-linked into a module, is the hub). */
    document.getElementById('appBackLink')?.addEventListener('click', (e) => {
        if (pushDepth > 0) { e.preventDefault(); history.back(); return; }
        /* No entry of ours under this one, and a module showing: the chevron
         * makes the SAME decision aiBackArrow and the module's own back
         * button make, back to the module that was showing, or to the board
         * when the board is genuinely where you were (2026-11-03, the owner:
         * Back from Lots, after a reload, went to the season's page instead
         * of Activities). It takes the place of this entry rather than adding
         * one, so the Back after it leaves the season as it should. Deep-
         * linked straight in from the hub tile there is no such screen: the
         * handler stands down and the anchor's own href (the cropping
         * schedule's page) does the walking. */
        if (current && current !== 'activities') {
            const home = (backTo && MODULES[backTo] && backTo !== current) ? backTo : (enteredByLink ? null : 'activities');
            if (home) {
                e.preventDefault();
                backTo = null;
                noTrail = true;
                Promise.resolve(showModule(home, false)).finally(() => {
                    if (current === home) history.replaceState({ module: home, depth: 0 }, '', shellUrl(home));
                });
            }
        }
    });
    window.smShowModule = showModule;

    // Toolbar "Notes" button opens the schedule notebook module (distinct from
    // the per-date notes in the timeline).
    document.getElementById('openNotesBtn')?.addEventListener('click', () => showModule('notes'));
    /* Back to wherever you actually came from: the module that was showing
       when this one was opened, or — if the shell was opened straight into a
       module from the schedule's own page — that page. Activities only when
       Activities is genuinely where you were. */
    document.getElementById('moduleBackBtn')?.addEventListener('click', () => {
        if (backTo && MODULES[backTo]) {
            const back = backTo;
            backTo = null;
            noTrail = true;
            showModule(back);
            return;
        }
        if (enteredByLink) { window.location.href = HUB_URL; return; }
        showModule('activities');
    });

    // Deep link: the hub tiles open this shell with ?module=<key>, so the module
    // loads here (with the hamburger) instead of as its own cut-off page. The
    // URL stays on the shell, so Back returns to the hub and a refresh reloads
    // the shell rather than the standalone page.
    // Deferred to DOMContentLoaded: showModule uses helpers (closeSheet, toast)
    // that the deferred app.js defines, which has not run during this inline
    // script but has by DOMContentLoaded.
    const applyDeepLink = () => {
        const wanted = new URLSearchParams(location.search).get('module');
        /* A module entry this shell pushed, come back to by a reload or by
           walking back from a page it opened: the board is underneath it in
           this tab's history, so Back goes there and not out to the season's
           page. Only a link straight into a module has nothing under it. */
        const st = history.state;
        const kept = !!(st && st.module === wanted && Number.isInteger(st.depth) && st.depth > 0);
        // The tab's note counts only for a reload or a walk back, never for a link opened fresh (the hub's Lots tile).
        let arrived = '';
        try { arrived = (performance.getEntriesByType('navigation')[0] || {}).type || ''; } catch (_) {}
        const under = kept ? (MODULES[st.prev] ? st.prev : 'activities')
            : (wanted && (arrived === 'reload' || arrived === 'back_forward') ? readTrail(wanted) : null);
        // The replaceStates below scrub the query to the shell's clean form,
        // and the board's own deep links (?highlight=, ?day= — a tag's
        // activity, the day an expense lives on) would be scrubbed with it.
        // Keep the arriving query where the board's spotter can read it.
        window.__boardArriveQuery = location.search;
        if (wanted && MODULES[wanted] && wanted !== 'activities') {
            /* Opened straight into a module, so the reader has never seen the
               board — "back to Activities" would send them somewhere new, and
               the shell's own starting value would otherwise record it as a
               screen they had been on. */
            noTrail = true;
            if (kept) {
                // Our entries are under this one: Back walks them.
                pushDepth = st.depth;
                backTo = under;
                history.replaceState(st, '', shellUrl(wanted));
            } else if (under) {
                // No entry of ours under it, but this tab says which module was: Back shows that one.
                backTo = under;
                history.replaceState({ module: wanted, depth: 0, prev: under }, '', shellUrl(wanted));
            } else {
                enteredByLink = true;
                history.replaceState({ module: wanted, depth: 0 }, '', shellUrl(wanted));
            }
            // module-booting kept the server-painted board out of sight; lift
            // it only once the module has actually landed, so Activities never
            // shows on the way in. setActivitiesChrome has re-hidden the
            // chrome with module-hidden by then, so nothing pops back.
            Promise.resolve(showModule(wanted, false))
                .finally(() => document.body.classList.remove('module-booting'));
        } else {
            // Unknown or absent key: this IS the Activities view — unhide it.
            document.body.classList.remove('module-booting');
            history.replaceState({ module: 'activities', depth: 0 }, '', shellUrl('activities'));
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
            // Drafts count in the menu; the filter count rides the toolbar's
            // own Search button now (it left the menu for the board).
            mirrorBadge('openDraftsBtn', 'actDraftsBadge', 'draftsBadge');
            mirrorBadge('searchToolbarBtn', 'toolbarFilterCount', 'activeFilterCount');

            // Undo and Redo live only in this menu now, so their rows carry
            // everything the buttons used to say in the toolbar: how many
            // steps are waiting, what the next one would put back, and the
            // greying that means there is nothing to go back to.
            [['activityUndoBtn', 'actUndoBadge', 'activityUndoCount'],
             ['activityRedoBtn', 'actRedoBadge', 'activityRedoCount']]
            .forEach(([btnId, badgeId, countId]) => {
                mirrorBadge(btnId, badgeId, countId);
                const row = document.querySelector('.activity-action-row[data-forward="' + btnId + '"]');
                const btn = document.getElementById(btnId);
                if (!row || !btn) return;
                row.disabled = btn.disabled;
                row.title = btn.title || '';
            });

            // (The Calendar/List switch lives in the eye menu now — see
            // #viewFilterSheet — so there is no view row here to mirror.)

            // Show/Hide Hidden: mirror label, hide the row when nothing is hidden.
            const hiddenRow = document.querySelector('.activity-action-row[data-forward="toggleHiddenBtn"]');
            const hiddenBtn = document.getElementById('toggleHiddenBtn');
            const hiddenLabel = document.getElementById('actHiddenLabel');
            if (hiddenRow && hiddenBtn) hiddenRow.classList.toggle('hidden', hiddenBtn.classList.contains('hidden'));
            if (hiddenLabel) hiddenLabel.textContent = (document.getElementById('toggleHiddenLabel')?.textContent || 'Show hidden').replace(/\s*\(\d+\)\s*$/, '');

        }

        actionsBtn.addEventListener('click', () => { syncActionsSheet(); openSheet('activityActionsSheet'); });
        // Keep the hamburger dot honest as things change underneath.
        document.addEventListener('activities:rendered', syncActionsSheet);
        syncActionsSheet();

        // The technician's own launcher, reached from the board rather than
        // from three taps down the Tools menu.
        document.getElementById('aiTechBtn')
            ?.addEventListener('click', () => document.getElementById('aiFloatFab')?.click());

        document.addEventListener('click', (e) => {
            const row = e.target.closest('.activity-action-row');
            if (!row || row.disabled) return;
            const target = document.getElementById(row.dataset.forward);
            if (!target) return;
            // Whichever sheet the row was standing in — the Tools menu or the
            // bolt's — rather than the one that used to be the only one.
            const sheet = row.closest('.sheet');
            if (sheet && sheet.id) closeSheet(sheet.id);
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
            ? n + (n === 1 ? ' thing still needs' : ' things still need') + ' to be set up'
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
            ? 'The red ones stop the plan from working. Do the rest when you can.'
            : 'The plan works without these, but they make it more useful.';

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
                ? 'Reminder paused. The Notice stays but won’t blink.'
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
    window.DAY_IV_NOTES = @json($dayInventoryNotes ?? (object) []);
    if (Array.isArray(window.DAY_IV_NOTES)) window.DAY_IV_NOTES = {};
    window.DAY_EXPENSES = @json($dayExpensesForJs);
    // A keyed collection JSON-encodes to an object; an empty one becomes [].
    if (Array.isArray(window.DAY_EXPENSES)) window.DAY_EXPENSES = {};

    // What the shed did, by day. Seeded whole rather than fetched per day:
    // most days have none of these, and a request per empty day is a request
    // for nothing. { 'YYYY-MM-DD': [{name, delta, before, after, ...}, ...] }
</script>
@include('sm.partials.activities-js', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
    'draftsCount' => $draftsCount,
])
@include('sm.partials.activities-calendar-js', ['schedule' => $schedule])
{{-- The shed's own sheet, for the day menu's two rows and for the picker in
     the activity sheet. Not standalone: the board wants the machinery, not
     the module's renderers, and the shelf is only fetched when something
     actually asks for it. --}}
@include('sm.partials.inventory-move-sheet')
@push('scripts')
<script>
    // The shell carries inventory-js for the board's stock picker; the shed
    // pane itself arrives as a partial that sets this again. Set it here too
    // so the first copy already knows whether this visitor holds the pen.
    window.IV_READONLY = @json(! \App\Support\WorkerContext::canWriteModule('inventory'));
</script>
@endpush
@include('sm.partials.inventory-js', ['schedule' => $schedule, 'standalone' => false])
{{-- The tag picker: one shared sheet for every form on the board. --}}
@include('sm.partials.tag-picker')
@include('sm.partials.harvest-dates', ['schedule' => $schedule])
@include('sm.partials.range-effect', ['schedule' => $schedule])
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
            n += document.querySelectorAll('#tagFilterChips .chip.is-selected').length;
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
            if (id === 'typeFilterChips' || id === 'lotFilterChips' || id === 'tagFilterChips') refreshBadge();
        });

        byId('clearFiltersBtn')?.addEventListener('click', () => {
            const search = byId('activitySearchInput');
            if (search && search.value) {
                search.value = '';
                search.dispatchEvent(new Event('input', { bubbles: true }));
            }
            ['typeFilterChips', 'lotFilterChips', 'tagFilterChips'].forEach((gid) => {
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

{{-- EVERY ROOM'S TUTORIAL, OFFERED AS THE ROOM OPENS.

     A module is a fragment this shell swaps in, and a script pushed from a
     fragment never runs -- so the shell holds the tutorials for all of its
     rooms and offers each as the shell announces it. The board itself is
     the one room that opens without an announcement when there is no
     ?module= on the address, so it is offered by hand. --}}
@push('scripts')
@include('partials.tutorial-offer', [
    'keys' => collect(array_keys(config('tutorials.pages', [])))->filter(fn ($k) => str_starts_with($k, 'module.'))->values()->all(),
])
<script>
(() => {
    // The shell's 'media' is the Gallery's old name; the tutorial has one key.
    const room = (key) => 'module.' + (key === 'media' ? 'gallery' : key);
    document.addEventListener('sm:module-shown', (e) => {
        if (e.detail?.key) (window.aneeTutorialQueue ||= []).push(room(e.detail.key));
    });
    if (!new URLSearchParams(location.search).get('module')) {
        (window.aneeTutorialQueue ||= []).push(room('activities'));
    }
})();
</script>
@endpush
