<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule — {{ $schedule->title }}</title>
    <style>
        @page { size: A4; margin: 20mm 18mm 22mm; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            background: #fff;
            color: #1a1f2b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 11.5pt;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            /* Stop long URLs / unbreakable strings from pushing past the page gutter. */
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        body { padding: 24px 28px 40px; max-width: 100%; }
        /* Belt-and-suspenders: anything that ends up wider than the page must be clipped + scrolled rather than blowing out the layout. */
        img, table, pre { max-width: 100%; }
        img { height: auto; }
        pre, code { white-space: pre-wrap; }
        .doc-header { border-bottom: 3px solid #1a1f2b; padding-bottom: 12px; margin-bottom: 18px; }
        .doc-header h1 { margin: 0 0 4px; font-size: 22pt; font-weight: 700; letter-spacing: -0.5px; }
        .doc-header .subtitle { color: #4a5160; font-size: 11pt; }
        .doc-meta { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 10px; font-size: 9.5pt; color: #6b7280; }
        .doc-meta strong { color: #1a1f2b; font-weight: 600; }
        .section { margin-top: 22px; page-break-inside: auto; }
        .section h2 {
            margin: 0 0 10px; font-size: 13pt; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            color: #1a1f2b; border-bottom: 1px solid #d9dde3; padding-bottom: 5px;
        }
        .summary-grid { display: flex; gap: 24px; flex-wrap: wrap; font-size: 10.5pt; }
        .summary-grid > div { min-width: 130px; }
        .summary-grid .label { color: #6b7280; font-size: 9.5pt; }
        .summary-grid .value { font-size: 14pt; font-weight: 700; color: #1a1f2b; }

        .description-block { color: #2a2f3a; font-size: 10pt; }
        .description-block p { margin: 0 0 0.5em; }
        .description-block ul, .description-block ol { margin: 0.25em 0 0.5em 1.4em; padding: 0; }
        .description-block li { margin-bottom: 0.15em; }
        .description-block h1, .description-block h2, .description-block h3, .description-block h4 { font-size: 10.5pt; font-weight: 700; margin: 0.5em 0 0.25em; }
        /* Quill 2 unified-list fix: bullet/ordered marker per <li> via
           data-list. Without this, an authored bullet list prints as a
           numbered list because <ol> defaults to decimal. */
        .description-block ol > li[data-list="bullet"]  { list-style-type: disc; }
        .description-block ol > li[data-list="ordered"] { list-style-type: decimal; }
        .description-block .ql-ui { display: none; }
        .description-block li.ql-indent-1 { margin-left: 1.5em; }
        .description-block li.ql-indent-2 { margin-left: 3em; }
        .description-block li.ql-indent-3 { margin-left: 4.5em; }
        .description-block li.ql-indent-4 { margin-left: 6em; }
        .description-block li.ql-indent-5 { margin-left: 7.5em; }
        .description-block li.ql-indent-6 { margin-left: 9em; }
        .description-block li.ql-indent-7 { margin-left: 10.5em; }
        .description-block li.ql-indent-8 { margin-left: 12em; }

        /* date-block is allowed to flow across pages — a date with many
           activities (10+) was forcing the whole block to the next page
           and leaving a half-empty page above. Individual .activity
           cards below still keep break-inside: avoid so cards stay
           whole; only the OUTER block is allowed to split. */
        .date-block { margin-bottom: 14px; }
        .date-block .date-bar {
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .date-block .date-bar {
            display: flex; align-items: baseline; gap: 10px;
            background: #f1f3f7;
            padding: 6px 10px;
            border-left: 4px solid #1a1f2b;
            margin-bottom: 6px;
        }
        .date-block .date-bar .day { font-weight: 600; color: #4a5160; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.8px; }
        .date-block .date-bar .date { font-weight: 700; font-size: 12pt; color: #1a1f2b; }
        .date-block .date-note {
            background: #fff8e6;
            border-left: 3px solid #d9a23a;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 10pt;
            color: #4d3a0d;
            line-height: 1.5;
            page-break-inside: avoid;
        }
        .date-block .date-note strong { color: #8a5e09; }
        /* Version-wide note (free-form commentary above the activity list).
           Blue accent so the reader doesn't confuse it with per-date notes. */
        .global-version-note {
            background: #eef4ff;
            border-left: 4px solid #4a73e3;
            padding: 9px 12px;
            margin-bottom: 12px;
            border-radius: 0 4px 4px 0;
            /* No break-inside: avoid — long version notes flow across pages. */
        }
        .global-version-note-label {
            font-weight: 700;
            font-size: 9pt;
            color: #2c3e8c;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .global-version-note-body {
            color: #1a2655;
            font-size: 10pt;
            line-height: 1.5;
        }

        /* Irrigation block — one card per irrigation entry, colored on the
           left by task type, with badges for task / range / priority. */
        .irr-block {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #1976d2;
            border-radius: 3px;
            padding: 8px 12px;
            margin-bottom: 10px;
            /* Allowed to flow — irrigation entries with many groups in the
               coverage list can be tall. Head + first line stay together
               via break-after: avoid on .irr-block-head below. */
        }
        .irr-block-head {
            display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
            margin-bottom: 4px;
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .irr-block-head .irr-name { color: #1f2937; font-size: 11pt; }
        .irr-task-badge,
        .irr-range-badge,
        .irr-prio-badge {
            color: #fff;
            font-size: 9pt;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .irr-block-line { font-size: 10pt; color: #495057; margin-top: 3px; }
        .irr-block-label { color: #1a4a7a; font-weight: 600; margin-right: 4px; }
        .irr-block-desc {
            font-size: 9.5pt; color: #555; margin-top: 4px;
            font-style: italic; line-height: 1.45;
        }
        .irr-block-coverage {
            margin: 4px 0 0 18px; padding: 0;
            font-size: 9.5pt; color: #495057;
        }
        .irr-block-coverage li { margin: 2px 0; }
        .irr-block-coverage .muted { color: #8b95a8; }

        /* ---- Protocol intro / Critical rules / Attachments (export) ---- */
        .critical-rules-callout {
            background: #fdf2f2;
            border: 2px solid #d9534f;
            border-radius: 4px;
            padding: 9px 12px;
            /* Allowed to split — a long list of rules shouldn't push
               the whole callout to the next page. */
        }
        .critical-rules-print-list li { page-break-inside: avoid; }
        .critical-rules-heading {
            font-weight: 700;
            color: #8a1d1d;
            font-size: 11pt;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .critical-rules-print-list { margin: 0; padding-left: 20px; }
        .critical-rules-print-list li {
            margin: 3px 0;
            color: #5a2828;
            font-size: 10pt;
            line-height: 1.45;
        }
        .protocol-intro-print {
            background: #fafbff;
            border: 1px solid #d3def1;
            border-left: 4px solid #4a73e3;
            padding: 9px 12px;
            color: #1a2655;
            font-size: 10pt;
            line-height: 1.55;
            /* Long protocols flow across pages — orphans/widows below
               keep paragraphs from splitting awkwardly. */
        }
        .protocol-intro-print p,
        .protocol-intro-print li { orphans: 3; widows: 3; }
        .protocol-intro-print h1,
        .protocol-intro-print h2,
        .protocol-intro-print h3 { color: #2c3e8c; margin: 0.4em 0 0.25em; font-size: 11pt; }
        .protocol-intro-print ul, .protocol-intro-print ol { margin-left: 1.2rem; }
        .attachments-print-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 9px;
        }
        .attachment-print-card {
            border: 1px solid #d9dde3;
            border-radius: 3px;
            background: #fff;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .attachment-print-img {
            width: 100%; height: auto;
            max-height: 200px;
            object-fit: cover;
            display: block;
        }
        .attachment-print-noimg {
            padding: 16px; text-align: center;
            color: #6b7280; font-size: 10pt;
        }
        .attachment-print-noimg strong {
            display: block; font-size: 13pt;
            color: #1f2937; margin-bottom: 3px;
        }
        .attachment-print-meta { padding: 5px 9px; border-top: 1px solid #eef0f4; }
        .attachment-print-meta strong { font-size: 9.5pt; color: #1f2937; }
        .attachment-print-desc {
            font-size: 9pt; color: #4a5160;
            margin-top: 3px; line-height: 1.4;
            word-break: break-word;
        }
        .date-block .date-bar .count { margin-left: auto; color: #6b7280; font-size: 9.5pt; }

        .activity {
            padding: 8px 12px 10px;
            margin: 0 0 8px 6px;
            border-left: 2px solid #d9dde3;
            page-break-inside: avoid;
        }
        .activity-title-row { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap; }
        .activity-title { font-weight: 700; font-size: 11.5pt; color: #1a1f2b; }
        .activity-range { font-size: 9.5pt; color: #4a5160; }
        .priority-pill { font-size: 8.5pt; padding: 1px 7px; border-radius: 8px; font-weight: 600; }
        .type-pill { font-size: 8.5pt; padding: 1px 7px; border-radius: 8px; font-weight: 600; background: #e2efd4; color: #2d4d1c; }
        .skill-chip { display: inline-block; font-size: 8.5pt; padding: 1px 6px; border-radius: 8px; background: #f0ead6; color: #6b4423; margin: 0 2px 2px 0; }
        .priority-critical { background: #9c1c1c; color: #fff; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
        .priority-high { background: #ffe1e1; color: #a82929; }
        .priority-medium { background: #fff3df; color: #8a6300; }
        .priority-low { background: #e8eaee; color: #495057; }
        .activity-line { margin-top: 4px; font-size: 10pt; color: #2a2f3a; }
        .activity-line .label { color: #6b7280; font-weight: 600; margin-right: 4px; }
        .chip { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 9pt; margin-right: 4px; margin-bottom: 2px; max-width: 100%; word-break: break-word; }
        .chip-lot { background: #eaf0fb; color: #2c4694; }
        .chip-worker { background: #fdebd9; color: #8a5400; }
        .chip-material { background: #eaf0fb; color: #3a4699; }
        .chip-service { background: #def4ea; color: #156d4e; }

        .desc-on-card { margin-top: 6px; font-size: 10pt; }
        .activity-image {
            margin-top: 6px;
            padding: 4px;
            background: #fff;
            border: 1px solid #d1d5db;
            display: inline-block;
            max-width: 100%;
            page-break-inside: avoid;
        }
        .activity-image img { display: block; max-width: 100%; max-height: 220px; }

        .lot-table, .worker-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10pt; table-layout: fixed; }
        .lot-table th, .worker-table th, .lot-table td, .worker-table td { text-align: left; padding: 4px 8px; border-bottom: 1px solid #ecedf0; word-break: break-word; vertical-align: top; }
        .lot-table th, .worker-table th { color: #6b7280; font-weight: 600; font-size: 9.5pt; }
        .description-block table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .description-block td, .description-block th { word-break: break-word; padding: 2px 6px; border: 1px solid #e6e8ec; }

        footer.doc-footer { margin-top: 28px; font-size: 9pt; color: #9aa0a6; text-align: center; border-top: 1px solid #ecedf0; padding-top: 8px; }

        @media print {
            body { padding: 0; }
            a { color: inherit; text-decoration: none; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    @php
        $sortedActivities = $schedule->activities->sortBy(function ($a) {
            $date = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') : 'ZZZZ-12-31';
            $seq = str_pad((string) (int) $a->sequenceOrder, 10, '0', STR_PAD_LEFT);
            return $date . '|' . $seq . '|' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT);
        })->values();
        $byDate = $sortedActivities->groupBy(function ($a) {
            return $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') : '__no-date__';
        });
        // Include note-only dates (days with a saved per-date note but no
        // activities scheduled). Without this, the date keys are built only
        // from activities and any standalone note would silently disappear
        // from the export. Merge, dedupe, sort.
        $noteDateKeys = $schedule->dateNotes
            ->map(fn ($n) => $n->noteDate ? $n->noteDate->format('Y-m-d') : null)
            ->filter()
            ->values();
        $dateKeys = $byDate->keys()
            ->merge($noteDateKeys)
            ->unique()
            ->reject(fn ($k) => $k === '__no-date__')
            ->sort()
            ->values();
        // Append the unscheduled bucket at the end (if it exists) so the
        // "No date assigned" group still renders after the dated ones.
        if ($byDate->has('__no-date__')) {
            $dateKeys = $dateKeys->push('__no-date__')->values();
        }

        $totalActivities = $sortedActivities->count();
        $firstDate = $sortedActivities->whereNotNull('targetDate')->first()?->targetDate;
        $lastDate = $sortedActivities->whereNotNull('targetDate')->sortByDesc(function ($a) {
            $end = $a->targetEndDate ?: $a->targetDate;
            return $end;
        })->first();
        $lastEnd = $lastDate ? ($lastDate->targetEndDate ?: $lastDate->targetDate) : null;
        $generatedAt = \Illuminate\Support\Carbon::now('Asia/Manila');
    @endphp

    <header class="doc-header">
        <h1>{{ $schedule->title }}</h1>
        @if($schedule->description)
            <div class="subtitle">{{ $schedule->description }}</div>
        @endif
        <div class="doc-meta">
            <span><strong>Status:</strong> {{ ucfirst($schedule->status) }}</span>
            <span><strong>Day Type:</strong> {{ $schedule->dayType }}</span>
            @if($firstDate)
                <span><strong>Spans:</strong>
                    {{ \Illuminate\Support\Carbon::parse($firstDate)->format('M j, Y') }}
                    @if($lastEnd) — {{ \Illuminate\Support\Carbon::parse($lastEnd)->format('M j, Y') }} @endif
                </span>
            @endif
            <span><strong>Generated:</strong> {{ $generatedAt->format('M j, Y · g:i A') }}</span>
            {{-- Filter provenance. A narrowed export must say so on its face:
                 printed and handed over, it is otherwise indistinguishable
                 from the complete schedule. --}}
            @if($exportActivitiesOnly || $exportStartFrom || $exportHasLotFilter || $exportHasDasMax || $exportHideWorkers || $exportHideNotes || $exportHideCriticality)
                @php
                    $exportFilterBits = [];
                    if ($exportActivitiesOnly) {
                        $exportFilterBits[] = 'activities only';
                    }
                    if ($exportStartFrom) {
                        $exportFilterBits[] = 'from ' . $exportStartFrom->format('M j, Y');
                    }
                    if ($exportHasDasMax) {
                        $exportFilterBits[] = 'up to ' . $schedule->dayType . ' ' . $exportDasMax;
                    }
                    $exportHiddenBits = [];
                    if ($exportHideWorkers)     { $exportHiddenBits[] = 'workers'; }
                    if ($exportHideNotes)       { $exportHiddenBits[] = 'notes'; }
                    if ($exportHideCriticality) { $exportHiddenBits[] = 'criticality'; }
                    if (count($exportHiddenBits)) {
                        $exportFilterBits[] = 'hidden: ' . implode(' + ', $exportHiddenBits);
                    }
                    if ($exportHasLotFilter) {
                        $exportLotNames = $schedule->lots
                            ->whereIn('id', $exportLotIds)
                            ->pluck('lotName')
                            ->all();
                        $exportFilterBits[] = count($exportLotNames)
                            ? 'lots: ' . implode(', ', $exportLotNames)
                                . ($exportIncludeNa ? ' (+ general)' : '')
                            : 'no lots selected';
                    } elseif (!$exportIncludeNa) {
                        $exportFilterBits[] = 'general activities excluded';
                    }
                @endphp
                <span class="export-filter-note">
                    <strong>Filtered:</strong> {{ implode(' · ', $exportFilterBits) }}
                </span>
            @endif
        </div>
    </header>

    @php
        // Active version's protocol introduction
        $exportProtocolVersion = $schedule->versions->firstWhere('isActive', true)
            ?? $schedule->versions->firstWhere('isOriginal', true)
            ?? $schedule->versions->first();
        $exportHasProtocolIntro = $exportProtocolVersion && !empty($exportProtocolVersion->globalActivityNote);
    @endphp

    {{-- Critical Rules — most prominent — render at the very top so the
         reader hits them first. Suppressed in activities-only mode. --}}
    @if(!$exportActivitiesOnly && $schedule->criticalRules->count() > 0)
        <section class="section">
            <div class="critical-rules-callout">
                <div class="critical-rules-heading">
                    ⚑ Critical Rules — Read Every Time
                </div>
                <ol class="critical-rules-print-list">
                    @foreach($schedule->criticalRules as $cRule)
                        <li>{{ $cRule->ruleText }}</li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- Protocol Introduction (rich text from the active version) --}}
    @if(!$exportActivitiesOnly && $exportHasProtocolIntro)
        <section class="section">
            <h2>Protocol Introduction</h2>
            <div class="protocol-intro-print">
                {!! $exportProtocolVersion->globalActivityNote !!}
            </div>
        </section>
    @endif

    {{-- Reference Attachments — print as a grid of images + descriptions.
         Falls back to a "file attached" placeholder for non-image types. --}}
    @if(!$exportActivitiesOnly && $schedule->attachments->count() > 0)
        <section class="section">
            <h2>Reference Attachments</h2>
            <div class="attachments-print-grid">
                @foreach($schedule->attachments as $att)
                    <div class="attachment-print-card">
                        @if($att->isImage() && $att->getPublicUrl())
                            <img class="attachment-print-img" src="{{ $att->getPublicUrl() }}" alt="{{ $att->filename }}">
                        @else
                            <div class="attachment-print-noimg">
                                <strong>{{ strtoupper(pathinfo($att->filename, PATHINFO_EXTENSION)) }}</strong>
                                <span class="muted">file attached</span>
                            </div>
                        @endif
                        <div class="attachment-print-meta">
                            <strong>{{ $att->filename }}</strong>
                            @if($att->description)
                                <div class="attachment-print-desc">{{ $att->description }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if(!$exportActivitiesOnly)
        <section class="section">
            <h2>Summary</h2>
            <div class="summary-grid">
                <div><div class="label">Total Activities</div><div class="value">{{ $totalActivities }}</div></div>
                <div><div class="label">Date Groups</div><div class="value">{{ $dateKeys->filter(fn($k) => $k !== '__no-date__')->count() }}</div></div>
                <div><div class="label">Lots</div><div class="value">{{ $schedule->lots->count() }}</div></div>
                {{-- Suppressed alongside the roster: a worker headcount is still
                     a worker fact, so leaving it would half-answer a request to
                     hide workers. --}}
                @if(!$exportHideWorkers)
                    <div><div class="label">Workers</div><div class="value">{{ $schedule->workers->count() }}</div></div>
                @endif
            </div>
        </section>
    @endif

    @if(!$exportActivitiesOnly && $schedule->lots->count())
        <section class="section">
            <h2>Lots</h2>
            <table class="lot-table">
                <thead><tr><th>Name</th><th>Size</th><th>Variety</th><th>Notes</th></tr></thead>
                <tbody>
                    @foreach($schedule->lots as $lot)
                        <tr>
                            <td><strong>{{ $lot->lotName }}</strong></td>
                            <td>{{ rtrim(rtrim((string) $lot->lotSize, '0'), '.') }} {{ $lot->lotSizeUnit }}</td>
                            <td>@if(!empty($lot->variety))<strong>{{ $lot->variety }}</strong>@else<span class="muted">—</span>@endif</td>
                            <td>{{ $lot->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if(!$exportActivitiesOnly && !$exportHideWorkers && $schedule->workers->count())
        <section class="section">
            <h2>Workers</h2>
            @php $skillsCatalog = \App\Models\AsScheduleWorker::SKILLS; @endphp
            <table class="worker-table">
                <thead><tr><th>Priority</th><th>Name</th><th>Cost / Half Day</th><th>Skills</th><th>Notes</th></tr></thead>
                <tbody>
                    @foreach($schedule->workers->sortBy('priority') as $w)
                        @php $wSkills = is_array($w->skills) ? $w->skills : []; @endphp
                        <tr>
                            <td>#{{ $w->priority }}</td>
                            <td><strong>{{ $w->workerName }}</strong></td>
                            <td>₱ {{ number_format($w->costPerHalfDay, 2) }}</td>
                            <td>
                                @if(count($wSkills) === 0)
                                    <span style="color:#9aa0a6;">—</span>
                                @else
                                    @foreach($wSkills as $k)
                                        @if(isset($skillsCatalog[$k]))
                                            <span class="skill-chip">{{ $skillsCatalog[$k] }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ $w->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="section">
        <h2>Activities</h2>
        @php
            // Index per-date notes once so each date-block can render its
            // commentary in O(1) without re-querying inside the loop.
            $dateNotesByDate = $schedule->dateNotes->keyBy(fn ($n) => $n->noteDate->format('Y-m-d'));
            // Active-version global note (free-form, version-wide commentary)
            $exportActiveVersion = $schedule->versions->firstWhere('isActive', true)
                ?? $schedule->versions->firstWhere('isOriginal', true)
                ?? $schedule->versions->first();
        @endphp
        @if(!$exportHideNotes && $exportActiveVersion && !empty($exportActiveVersion->globalActivityNote))
            <div class="global-version-note">
                <div class="global-version-note-label">Note for this version ({{ $exportActiveVersion->versionName }})</div>
                <div class="global-version-note-body">{!! $exportActiveVersion->globalActivityNote !!}</div>
            </div>
        @endif
        @forelse($dateKeys as $dateKey)
            @php
                // A date can be present in $dateKeys via either an activity
                // OR a standalone note — handle the note-only case where the
                // bucket is absent on $byDate.
                $activitiesForDate = $byDate->get($dateKey) ?? collect();
                $dateCarbon = ($dateKey !== '__no-date__') ? \Illuminate\Support\Carbon::parse($dateKey) : null;
                $exportNote = $dateNotesByDate->get($dateKey);
                $activityCount = $activitiesForDate->count();
            @endphp
            <div class="date-block">
                <div class="date-bar">
                    @if($dateCarbon)
                        <span class="day">{{ $dateCarbon->format('D') }}</span>
                        <span class="date">{{ $dateCarbon->format('F j, Y') }}</span>
                    @else
                        <span class="date">No date assigned</span>
                    @endif
                    @if($activityCount > 0)
                        <span class="count">{{ $activityCount }} {{ \Illuminate\Support\Str::plural('activity', $activityCount) }}</span>
                    @else
                        <span class="count">Note only</span>
                    @endif
                </div>
                @if($exportNote)
                    <div class="date-note">
                        <strong>Note:</strong> {!! nl2br(e($exportNote->noteContent)) !!}
                    </div>
                @endif

                @foreach($activitiesForDate as $a)
                    @php
                        $endCarbon = $a->targetEndDate ? \Illuminate\Support\Carbon::parse($a->targetEndDate) : null;
                        $startCarbon = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate) : null;
                        $isRange = $endCarbon && $startCarbon && $endCarbon->greaterThan($startCarbon);
                        $rangeDays = $isRange ? ($startCarbon->diffInDays($endCarbon) + 1) : 1;
                        $timeLabel = ['half' => 'Half day', 'whole' => 'Whole day', 'n/a' => 'N/A'][$a->timeRequired] ?? ucfirst($a->timeRequired);
                    @endphp
                    <div class="activity">
                        <div class="activity-title-row">
                            <span class="activity-title">{{ $a->activityTitle }}</span>
                            @if($isRange)
                                <span class="activity-range">→ {{ $endCarbon->format('M j') }} ({{ $rangeDays }} days)</span>
                            @endif
                            @if($a->activityType && isset(\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType]))
                                <span class="type-pill">{{ \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType] }}</span>
                            @endif
                            @if(!$exportHideCriticality)
                                <span class="priority-pill priority-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                            @endif
                        </div>
                        @if($a->description)
                            <div class="desc-on-card description-block">{!! $a->description !!}</div>
                        @endif
                        @if($a->imagePath)
                            <div class="activity-image">
                                <img src="{{ $a->imageUrl() }}" alt="">
                            </div>
                        @endif
                        <div class="activity-line">
                            <span class="label">Time:</span>{{ $timeLabel }}
                        </div>
                        @if($a->lots->count())
                            <div class="activity-line">
                                <span class="label">Lots:</span>
                                @foreach($a->lots as $lot)
                                    <span class="chip chip-lot">{{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif</span>
                                @endforeach
                            </div>
                        @endif
                        @if(!$exportHideWorkers && $a->workers->count())
                            <div class="activity-line">
                                <span class="label">Workers:</span>
                                @foreach($a->workers as $worker)
                                    <span class="chip chip-worker">{{ $worker->workerName }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($a->items->count())
                            <div class="activity-line">
                                <span class="label">Materials &amp; Services:</span>
                                @foreach($a->items as $it)
                                    @php
                                        $qtyTrim = rtrim(rtrim((string) $it->quantity, '0'), '.');
                                        $unit = $it->unitOfMeasure ?: ($it->material->unitOfMeasure ?? '');
                                    @endphp
                                    @if($it->itemType === 'material' && $it->material)
                                        <span class="chip chip-material">{{ $it->material->materialName }} ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif</span>
                                    @elseif($it->itemType === 'service' && $it->service)
                                        <span class="chip chip-service">{{ $it->service->serviceName }}@if($qtyTrim !== '1' || $unit) ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif @endif</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <p style="color: #6b7280; font-style: italic;">No activities have been defined for this schedule.</p>
        @endforelse
    </section>

    {{-- Irrigation Schedules — full detail dump so the printed copy has
         everything: task type with color/icon, priority, DAS-vs-Date
         range, lots, workers, and description. Ordered by sortOrder
         (drag-drop position) then startDay so the printed copy matches
         the on-screen card list. --}}
    @if(!$exportActivitiesOnly && $schedule->irrigations->count() > 0)
        <section class="section">
            <h2 class="page-break">Irrigation Schedules</h2>
            <p>Each entry shows the irrigation cycle, the task type, its priority for overlap resolution,
               the lots it pertains to, and the workers responsible.</p>
            @foreach($schedule->irrigations as $i)
                @php
                    $iMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($i->taskType);
                    $iPrio = (int) ($i->priority ?? 5);
                    $iIsDateMode = ($i->dayMode === 'date' && $i->startDate && $i->endDate);
                    $iRangeLabel = $iIsDateMode
                        ? $i->startDate->format('M j, Y') . ' — ' . $i->endDate->format('M j, Y')
                        : $schedule->dayType . ' ' . $i->startDay . '–' . $i->endDay;
                    $iPrioColor = ['','#9c1c1c','#d97a4f','#d9a23a','#7a8a99','#c8cdd5'][$iPrio] ?? '#c8cdd5';
                    $iPrioTextColor = $iPrio >= 3 ? '#3a2c0a' : '#fff';
                @endphp
                <div class="irr-block" style="border-left-color: {{ $iMeta['color'] }};">
                    <div class="irr-block-head">
                        <strong class="irr-name">{{ $i->irrigationTitle }}</strong>
                        <span class="irr-task-badge" style="background: {{ $iMeta['color'] }};">
                            {{ $iMeta['icon'] }} {{ $iMeta['label'] }}
                        </span>
                        <span class="irr-range-badge" style="background: {{ $iIsDateMode ? '#6b7280' : '#0c84d1' }};">
                            {{ $iIsDateMode ? '📅 ' : '' }}{{ $iRangeLabel }}
                        </span>
                        @if(!$exportHideCriticality)
                            <span class="irr-prio-badge" style="background: {{ $iPrioColor }}; color: {{ $iPrioTextColor }};">
                                P{{ $iPrio }}
                            </span>
                        @endif
                    </div>
                    @if($i->lots->count() > 0)
                        <div class="irr-block-line">
                            <span class="irr-block-label">Lots:</span>
                            @foreach($i->lots as $iLot)
                                <span class="chip chip-lot">{{ $iLot->lotName }}@if(!empty($iLot->variety)) · {{ $iLot->variety }}@endif</span>
                            @endforeach
                        </div>
                    @endif
                    {{-- Irrigation carries worker names too. "Hide workers" has to
                         mean the whole document, or names leak back in here. --}}
                    @if($exportHideWorkers)
                        {{-- nothing --}}
                    @elseif($i->workers->count() > 0)
                        <div class="irr-block-line">
                            <span class="irr-block-label">Workers:</span>
                            @foreach($i->workers as $iWk)
                                <span class="chip chip-worker">{{ $iWk->workerName }}</span>
                            @endforeach
                        </div>
                    @elseif($i->assignedWorker)
                        <div class="irr-block-line">
                            <span class="irr-block-label">Assigned:</span>
                            {{ $i->assignedWorker->workerName }}
                        </div>
                    @endif
                    @if($i->description)
                        <div class="irr-block-desc">{{ $i->description }}</div>
                    @endif

                    {{-- For DAS-mode irrigations, project the per-group calendar
                         dates so the printed copy resolves the relative DAS
                         offsets into concrete calendar dates the worker can act on. --}}
                    @if(!$iIsDateMode && $schedule->defaultGroupings->count() > 0)
                        <div class="irr-block-line">
                            <span class="irr-block-label">Calendar coverage per group:</span>
                        </div>
                        <ul class="irr-block-coverage">
                            @foreach($schedule->defaultGroupings as $g)
                                @php
                                    $gStart = $g->startDate ? \Illuminate\Support\Carbon::parse($g->startDate) : null;
                                @endphp
                                <li>
                                    <strong>{{ $g->groupName }}</strong>
                                    @if($g->lots->count() > 0)
                                        <small class="muted">({{ $g->lots->pluck('lotName')->implode(', ') }})</small>
                                    @endif
                                    @if($gStart)
                                        — {{ $gStart->copy()->addDays((int) $i->startDay)->format('M j, Y') }}
                                        @if($i->endDay !== $i->startDay)
                                            → {{ $gStart->copy()->addDays((int) $i->endDay)->format('M j, Y') }}
                                            ({{ ((int) $i->endDay - (int) $i->startDay) + 1 }}d)
                                        @endif
                                    @else
                                        <small class="muted">— no group start date set</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <footer class="doc-footer">
        {{ $schedule->title }} — printed {{ $generatedAt->format('M j, Y · g:i A') }} from AniSystem by AniSenso
    </footer>
</body>
</html>
