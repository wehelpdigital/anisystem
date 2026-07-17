@extends('layouts.app')

@section('title', 'Activities — ' . $schedule->title)
@section('page-title', 'Activities')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <style>
        /* ---- Timeline date-group color cycle (8 flat colors, mother parity) ---- */
        .date-color-0 { --date-color: #4A90E2; }
        .date-color-1 { --date-color: #50C878; }
        .date-color-2 { --date-color: #F39C12; }
        .date-color-3 { --date-color: #9B59B6; }
        .date-color-4 { --date-color: #1ABC9C; }
        .date-color-5 { --date-color: #E74C3C; }
        .date-color-6 { --date-color: #5C6BC0; }
        .date-color-7 { --date-color: #16A085; }

        .date-group {
            background: #fff; border: 1px solid #f3f4f6; border-left: 4px solid var(--date-color, #4A90E2);
            border-radius: 1rem; box-shadow: var(--shadow-card); margin-bottom: .9rem;
        }
        .date-header {
            display: flex; align-items: center; gap: .4rem; flex-wrap: wrap;
            padding: .55rem .8rem; border-radius: calc(1rem - 2px) calc(1rem - 2px) 0 0;
            background: color-mix(in srgb, var(--date-color, #4A90E2) 13%, #fff);
        }
        .date-header-day { font-weight: 800; font-size: .78rem; color: var(--date-color); text-transform: uppercase; }
        .date-header-date { font-weight: 700; font-size: .92rem; color: #111827; }
        .date-header-range { display: inline-flex; align-items: center; gap: .2rem; font-size: 11px; font-weight: 600; color: #374151; background: rgba(255,255,255,.75); border-radius: 999px; padding: .1rem .5rem; }
        .date-header-count { font-size: 11px; font-weight: 700; color: var(--date-color); background: rgba(255,255,255,.8); border-radius: 999px; padding: .1rem .55rem; margin-left: auto; }
        .date-header-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.15rem; height: 2.15rem; border-radius: .55rem; color: #4b5563; flex-shrink: 0;
        }
        .date-header-btn:hover { background: rgba(255,255,255,.75); color: #111827; }
        .date-note-btn.has-note, .date-marker-btn.has-marker { color: #b45309; }
        .date-header-delete-btn:hover { color: #dc2626; background: rgba(255,255,255,.75); }

        .date-activities { display: flex; flex-direction: column; gap: .55rem; padding: .7rem; }
        .date-activities.drag-over { outline: 2px dashed #86b556; outline-offset: -4px; border-radius: .8rem; background: #f7fbf1; }

        .date-note-block {
            margin: .55rem .7rem 0; background: #fffbeb; border: 1px solid #fde68a; border-radius: .6rem;
            padding: .5rem .7rem; font-size: .8rem; color: #78350f; white-space: pre-wrap;
        }

        .activity-card { border: 1px solid #eef0f3; border-radius: .85rem; background: #fff; padding: .7rem .85rem; }
        .activity-card[draggable="true"] { cursor: grab; }
        .activity-card.dragging { opacity: .45; }
        .activity-card-image img { max-width: 100%; max-height: 260px; border-radius: .6rem; border: 1px solid #eef0f3; }
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

        .icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: .6rem; color: #6b7280; flex-shrink: 0; cursor: pointer;
        }
        .icon-btn:hover { background: #f3f4f6; color: #374151; }
        .icon-btn-danger:hover { background: #fef2f2; color: #dc2626; }

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

@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'activities'])

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
</div>

{{-- ============================ TOOLBAR (sticky) ============================ --}}
<div class="sticky top-14 md:top-16 z-20 bg-gray-50 -mx-4 px-4 sm:-mx-6 sm:px-6 py-2 mb-3 border-b border-gray-100">
    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" id="activityUndoBtn" class="btn btn-white btn-sm relative" disabled title="Nothing to undo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg>
            Undo
            <span id="activityUndoCount" class="absolute -top-1.5 -right-1.5 hidden min-w-5 h-5 px-1 rounded-full bg-accent-500 text-ink text-[10px] font-bold items-center justify-center">0</span>
        </button>
        <button type="button" id="openDraftsBtn" class="btn btn-white btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            Drafts <span id="draftsBadge" class="badge badge-gray">{{ $draftsCount }}</span>
        </button>
        <button type="button" id="openLaborBtn" class="btn btn-white btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Labor
        </button>
        <button type="button" id="addActivityBtn" class="btn btn-primary btn-sm ml-auto hidden md:inline-flex">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Activity
        </button>
    </div>
</div>

{{-- ============================ FILTERS ============================ --}}
<div class="mb-4 space-y-2">
    <div class="relative">
        <input type="search" id="activitySearchInput" class="form-input pr-24" placeholder="Search activities, lots, workers, items…">
        <span id="activitySearchCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></span>
    </div>
    <div class="scroll-chips" id="typeFilterChips" data-chip-group>
        @foreach ($activityTypes as $slug => $label)
            <button type="button" class="chip shrink-0 min-h-9! py-1! text-xs" data-value="{{ $slug }}">{{ $label }}</button>
        @endforeach
    </div>
    @if ($schedule->lots->count())
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 shrink-0">Hide lots:</span>
            <div class="scroll-chips grow" id="lotFilterChips" data-chip-group>
                @foreach ($schedule->lots as $lot)
                    <button type="button" class="chip shrink-0 min-h-9! py-1! text-xs" data-value="{{ $lot->id }}"
                        title="Hide {{ $lot->lotName }} — cards covering another visible lot stay put">
                        {{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif
                    </button>
                @endforeach
                <button type="button" class="chip chip-dashed shrink-0 min-h-9! py-1! text-xs" data-value="__na__"
                    title="Hide activities not tied to any specific lot">N/A</button>
            </div>
            <button type="button" id="lotFilterAllBtn" class="text-xs font-semibold text-brand-700 shrink-0">Hide all</button>
            <button type="button" id="lotFilterClearBtn" class="text-xs font-semibold text-brand-700 shrink-0 hidden">Show all</button>
        </div>
    @endif
    <div>
        <button type="button" id="toggleHiddenBtn" class="btn btn-white btn-sm {{ $hiddenCount ? '' : 'hidden' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            <span id="toggleHiddenLabel">Show Hidden ({{ $hiddenCount }})</span>
        </button>
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
                    <div class="date-header">
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
                            <button type="button" class="date-header-btn date-note-btn {{ $noteRow ? 'has-note' : '' }}" data-date="{{ $dateKey }}" title="{{ $noteRow ? 'Edit the note for this date' : 'Add a note for this date' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </button>
                            <button type="button" class="date-header-btn date-marker-btn {{ $existingMarker ? 'has-marker' : '' }}" data-date="{{ $dateKey }}" title="{{ $existingMarker ? 'Edit the resume-here marker' : 'Drop a resume-here marker after this date' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                            </button>
                            <button type="button" class="date-header-btn change-group-date-btn" data-date="{{ $dateKey }}" title="Change date for all activities in this group">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>
                            <button type="button" class="date-header-btn date-header-delete-btn delete-group-date-btn" data-date="{{ $dateKey }}" title="Delete every activity in this group">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
<button type="button" id="fabAddActivity"
    class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center"
    aria-label="Add activity">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
</button>
@endsection

@push('sheets')
@include('sm.partials.activities-sheets', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
])
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>
@include('sm.partials.activities-js', [
    'schedule' => $schedule,
    'activityTypes' => $activityTypes,
    'activeVersion' => $activeVersion,
    'draftsCount' => $draftsCount,
])
@endpush
