<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;

/**
 * Printable / standalone documents for a cropping schedule.
 *
 * Ported faithfully from the mother app's ActivityController
 * (aniSensoAdmin\ScheduleManager) — export(), workerPresentation() and
 * cardViewer() — with identical query-param options, eager loads and
 * computations. The headless-Chrome workerPresentationPdf() method is
 * intentionally omitted: the browser print dialog covers PDF output.
 */
class DocumentController extends BaseScheduleController
{
    /**
     * Printable export of the ACTIVE version (opened in a new tab; user
     * prints to PDF from the browser).
     *
     * Query options (all optional):
     *  - activitiesOnly  : drop every non-activity section
     *  - startFrom       : Y-m-d cutoff — keeps activities whose END >= cutoff
     *  - dasMax          : upper DAS/DAP bound (earliest-DAS convention)
     *  - lotIds[]        : restrict activities to these lots (-1 sentinel = none)
     *  - includeNa       : keep activities not tied to any lot (default yes)
     *  - hideWorkers / hideNotes / hideCriticality
     */
    public function export(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots',
            'workers',
            'activities' => function ($q) {
                // Hidden activities are excluded from the export — same
                // policy as worker presentation and card viewer.
                $q->where('isHidden', false)
                    ->orderBy('targetDate')
                    ->orderBy('sequenceOrder')
                    ->orderBy('id');
            },
            'activities.lots',
            'activities.workers',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('sortOrder', 'asc')->orderBy('startDay', 'asc'),
            'irrigations.workers',
            'irrigations.lots',
            'irrigations.assignedWorker',
            'dateNotes',
            'versions',
            'defaultGroupings.lots',
            'attachments',
            'criticalRules',
        ]);

        // ---- Export view options (all optional query params) ----
        //
        // Filtering happens here rather than in the view so every figure the
        // view derives from $schedule->activities (totals, first/last date,
        // the date-group keys) is computed from the SAME filtered set — a
        // view-level filter would leave the Summary counting rows the reader
        // can no longer see.
        $exportActivitiesOnly = $request->boolean('activitiesOnly');

        $exportStartFrom = null;
        if ($request->filled('startFrom')) {
            try {
                $exportStartFrom = \Illuminate\Support\Carbon::parse($request->input('startFrom'))->startOfDay();
            } catch (\Throwable $e) {
                $exportStartFrom = null; // unparseable date → treat as "no cutoff"
            }
        }

        // A lot filter is "present" whenever the param was sent at all. The
        // client sends the sentinel -1 when the user unchecks every lot, so
        // "none selected" narrows to zero rows instead of silently meaning
        // "show everything" (the empty-array ambiguity).
        $exportLotIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('lotIds', [])
        )));
        $exportHasLotFilter = $request->has('lotIds');
        $exportIncludeNa = !$request->has('includeNa') || $request->boolean('includeNa');

        $exportHideWorkers     = $request->boolean('hideWorkers');
        $exportHideNotes       = $request->boolean('hideNotes');
        $exportHideCriticality = $request->boolean('hideCriticality');

        $exportHasDasMax = $request->filled('dasMax') && is_numeric($request->input('dasMax'));
        $exportDasMax = $exportHasDasMax ? (int) $request->input('dasMax') : null;

        // Effective Day 0 anchor per lot: the lot's manual date is the
        // baseline, and any activity flagged as Day 0 overrides it (earliest
        // wins). Built from the UNFILTERED activity set — a DAS cap has to be
        // measured against the schedule's real anchors, not against whatever
        // survives the filter.
        $exportLotDayZero = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $exportLotDayZero[$lot->id] = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (!$a->isDayZero || !$a->targetDate) {
                continue;
            }
            $aDate = \Illuminate\Support\Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (!isset($exportLotDayZero[$lot->id]) || $aDate->lt($exportLotDayZero[$lot->id])) {
                    $exportLotDayZero[$lot->id] = $aDate->copy();
                }
            }
        }

        if ($exportStartFrom || $exportHasLotFilter || $exportHasDasMax) {
            $filteredActivities = $schedule->activities->filter(
                function ($a) use (
                    $exportStartFrom,
                    $exportHasLotFilter,
                    $exportLotIds,
                    $exportIncludeNa,
                    $exportHasDasMax,
                    $exportDasMax,
                    $exportLotDayZero
                ) {
                    $aLotIds = $a->lots->pluck('id')->all();

                    if ($exportStartFrom) {
                        // Compare the END of the range, not the start: an activity
                        // that began earlier but is still running on the cutoff is
                        // work the reader still needs. Undated activities have
                        // nothing to compare, so a date-bounded document drops them.
                        $end = $a->targetEndDate ?: $a->targetDate;
                        if (!$end || \Illuminate\Support\Carbon::parse($end)->lt($exportStartFrom)) {
                            return false;
                        }
                    }

                    if ($exportHasLotFilter) {
                        // No lots at all = a general "N/A" activity; it applies to
                        // every lot, so it's governed by its own toggle.
                        if (count($aLotIds) === 0) {
                            return $exportIncludeNa;
                        }
                        if (count(array_intersect($aLotIds, $exportLotIds)) === 0) {
                            return false;
                        }
                    }

                    if ($exportHasDasMax) {
                        if (!$a->targetDate) {
                            return false;
                        }
                        $aDate = \Illuminate\Support\Carbon::parse($a->targetDate);
                        // Only measure against lots the reader is actually seeing.
                        $consideredLotIds = $exportHasLotFilter
                            ? array_values(array_intersect($aLotIds, $exportLotIds))
                            : $aLotIds;
                        $deltas = [];
                        foreach ($consideredLotIds as $lotId) {
                            if (!isset($exportLotDayZero[$lotId])) {
                                continue;
                            }
                            $deltas[] = (int) $exportLotDayZero[$lotId]->diffInDays($aDate, false);
                        }
                        // No anchor → no DAS → nothing to compare a bound against,
                        // so the activity can't satisfy it. Same rule the labor
                        // summary applies. This is what drops general N/A
                        // activities (they have no lot, hence no Day 0) once a
                        // DAS cap is set.
                        if (empty($deltas)) {
                            return false;
                        }
                        // Earliest DAS across the activity's lots — matches the
                        // "earliest DAS across its lots" convention used by the
                        // labor summary's DAS range filter.
                        if (min($deltas) > $exportDasMax) {
                            return false;
                        }
                    }

                    return true;
                }
            )->values();
            $schedule->setRelation('activities', $filteredActivities);
        }

        // Per-date notes are keyed off dates too — without this a note sitting
        // before the cutoff would still print and re-introduce a date the
        // document is supposed to start after. Dropping them outright when the
        // user asked to hide notes also stops note-only dates from rendering as
        // empty date blocks.
        if ($exportHideNotes) {
            $schedule->setRelation('dateNotes', $schedule->dateNotes->take(0));
        } elseif ($exportStartFrom) {
            $schedule->setRelation('dateNotes', $schedule->dateNotes->filter(
                fn ($n) => $n->noteDate && $n->noteDate->gte($exportStartFrom)
            )->values());
        }

        return view('sm.documents.export', compact(
            'schedule',
            'exportActivitiesOnly',
            'exportStartFrom',
            'exportLotIds',
            'exportHasLotFilter',
            'exportIncludeNa',
            'exportHasDasMax',
            'exportDasMax',
            'exportHideWorkers',
            'exportHideNotes',
            'exportHideCriticality'
        ));
    }

    /**
     * Full printable worker briefing (standalone HTML; browser print → PDF).
     *
     * Query options: showDesc, showIrrigation, showCalendar, laborOnly,
     * workerIds[] (-1 sentinel handled by the intval/filter combination).
     */
    public function workerPresentation(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots',
            'workers' => fn ($q) => $q->orderBy('priority', 'asc'),
            // Skip activities the user toggled as hidden — they stay
            // visible on the setup timeline (dimmed) but are excluded
            // from the worker briefing.
            'activities' => fn ($q) => $q->where('isHidden', false)->orderBy('targetDate', 'asc'),
            'activities.workers',
            'activities.lots',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('startDay', 'asc'),
            'irrigations.assignedWorker',
            'irrigations.workers',
            'irrigations.lots',
            'defaultGroupings.lots',
            'dateNotes',
            'versions',
            'attachments',
            'criticalRules',
        ]);

        // ---- Effective Day 0 anchor per lot (manual + activity flags) ----
        $lotDayZero = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $lotDayZero[$lot->id] = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (!$a->isDayZero || !$a->targetDate) continue;
            $aDate = $a->targetDate;
            foreach ($a->lots as $lot) {
                if (!isset($lotDayZero[$lot->id]) || $aDate->lt($lotDayZero[$lot->id])) {
                    $lotDayZero[$lot->id] = $aDate->copy();
                }
            }
        }

        // ---- Per-worker stats (work-day list, monthly counts, earnings) ----
        $workerStats = [];
        foreach ($schedule->workers as $worker) {
            $workDays = [];
            $byMonth = [];
            $halfCount = 0; $wholeCount = 0; $naCount = 0;
            foreach ($schedule->activities as $activity) {
                if (!$activity->workers->contains('id', $worker->id)) continue;
                $start = $activity->targetDate;
                if (!$start) continue;
                $end = $activity->targetEndDate ? $activity->targetEndDate : $start;
                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $monthKey = $d->format('Y-m');
                    $byMonth[$monthKey] = ($byMonth[$monthKey] ?? 0) + 1;
                    $workDays[] = [
                        'date'         => $d->copy(),
                        'timeRequired' => $activity->timeRequired,
                    ];
                    if ($activity->timeRequired === 'whole') {
                        $wholeCount++;
                    } elseif ($activity->timeRequired === 'half') {
                        $halfCount++;
                    } else {
                        $naCount++;
                    }
                }
            }
            usort($workDays, function ($x, $y) {
                if ($x['date']->equalTo($y['date'])) return 0;
                return $x['date']->lt($y['date']) ? -1 : 1;
            });
            ksort($byMonth);
            $units = ($wholeCount * 2) + ($halfCount * 1);
            $earnings = $units * (float) $worker->costPerHalfDay;
            $workerStats[] = [
                'worker'     => $worker,
                'workDays'   => $workDays,
                'totalDays'  => count($workDays),
                'byMonth'    => $byMonth,
                'halfCount'  => $halfCount,
                'wholeCount' => $wholeCount,
                'naCount'    => $naCount,
                'units'      => $units,
                'earnings'   => round($earnings, 2),
            ];
        }

        // ---- Aggregate monthly labor counts across all workers ----
        // Counts the number of DISTINCT calendar days per month that have at
        // least one scheduled activity. NOT multiplied by the number of
        // workers — if 3 workers all work the same day, that's 1 day, not 3.
        $aggregateMonthly = [];
        $monthDates = []; // monthKey → set of date keys
        foreach ($schedule->activities as $activity) {
            if (!$activity->targetDate) continue;
            $start = $activity->targetDate;
            $end = $activity->targetEndDate ? $activity->targetEndDate : $start;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $monthKey = $d->format('Y-m');
                $dateKey  = $d->format('Y-m-d');
                $monthDates[$monthKey][$dateKey] = true;
            }
        }
        foreach ($monthDates as $monthKey => $dates) {
            $aggregateMonthly[$monthKey] = count($dates);
        }
        ksort($aggregateMonthly);

        // ---- Irrigation calendar mapping per group → contiguous BANDS per
        // week, so the calendar can render an irrigation cycle as a single
        // bar spanning multiple cells instead of one chip per day.
        //
        // A cycle for a group runs continuously from (groupStart + startDAS)
        // through (groupStart + endDAS). For each calendar week the cycle
        // touches, we emit one band segment with its startCol/endCol in
        // that week's Sun–Sat lane (1-based, 1 = Sunday).
        //
        // After collection, each week's bands are row-packed greedily so
        // non-overlapping segments share a row and only true overlaps stack.
        $irrigationBandsByWeek = [];
        // ---- Priority-resolved band generation ----
        //
        // Step 1: collect every raw (start, end, group) window for every
        //         irrigation, tagged with its priority.
        // Step 2: per group, walk day-by-day and pick the winning window
        //         (lowest priority number; tie-break: latest id wins so
        //         newer edits override older ones).
        // Step 3: collapse contiguous days that have the same winning
        //         irrigation back into single bands — this is how a
        //         priority-1 day in the middle of a DAS 1–10 priority-5
        //         band splits the original into 1–4 and 6–10.
        // Step 4: feed the resolved bands into the per-week segmenter the
        //         calendar grid renders from.
        $rawWindows = [];
        foreach ($schedule->irrigations as $irrigation) {
            $taskMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($irrigation->taskType);
            $priority = (int) ($irrigation->priority ?? 5);

            if ($irrigation->dayMode === 'date' && $irrigation->startDate && $irrigation->endDate) {
                $rawWindows[] = [
                    'start'      => $irrigation->startDate->copy(),
                    'end'        => $irrigation->endDate->copy(),
                    'groupKey'   => '__absolute__', // date-mode is not tied to a default-grouping
                    'groupName'  => '',
                    'priority'   => $priority,
                    'irrigation' => $irrigation,
                    'taskMeta'   => $taskMeta,
                ];
            } else {
                foreach ($schedule->defaultGroupings as $group) {
                    $groupStart = $group->startDate ? \Illuminate\Support\Carbon::parse($group->startDate) : null;
                    if (!$groupStart) continue;
                    $rawWindows[] = [
                        'start'      => $groupStart->copy()->addDays((int) $irrigation->startDay),
                        'end'        => $groupStart->copy()->addDays((int) $irrigation->endDay),
                        'groupKey'   => 'g' . $group->id,
                        'groupName'  => $group->groupName,
                        'priority'   => $priority,
                        'irrigation' => $irrigation,
                        'taskMeta'   => $taskMeta,
                    ];
                }
            }
        }

        // Per-day winners, partitioned by group so that two groups can have
        // their own independent bands (a priority-1 in Group A doesn't
        // touch Group B's bands).
        $dayWinners = []; // [groupKey => [Y-m-d => window]]
        foreach ($rawWindows as $win) {
            $cursor = $win['start']->copy();
            while ($cursor->lte($win['end'])) {
                $dateKey = $cursor->format('Y-m-d');
                $existing = $dayWinners[$win['groupKey']][$dateKey] ?? null;
                $existingPriority = $existing['priority'] ?? PHP_INT_MAX;
                $beats = $win['priority'] < $existingPriority
                    || ($win['priority'] === $existingPriority
                        && (int) $win['irrigation']->id > (int) ($existing['irrigation']->id ?? -1));
                if ($beats) {
                    $dayWinners[$win['groupKey']][$dateKey] = $win;
                }
                $cursor->addDay();
            }
        }

        // Collapse contiguous same-irrigation winning days back into bands.
        $resolvedWindows = [];
        foreach ($dayWinners as $groupKey => $byDate) {
            ksort($byDate);
            $current = null;
            $bandStart = null;
            $bandEnd = null;
            foreach ($byDate as $dateKey => $winner) {
                $today = \Illuminate\Support\Carbon::parse($dateKey);
                $sameIrrig = $current && (int) $current['irrigation']->id === (int) $winner['irrigation']->id;
                $contiguous = $bandEnd && $bandEnd->copy()->addDay()->equalTo($today);
                if ($sameIrrig && $contiguous) {
                    $bandEnd = $today->copy();
                    continue;
                }
                if ($current !== null) {
                    $resolvedWindows[] = array_merge($current, [
                        'start' => $bandStart,
                        'end'   => $bandEnd,
                    ]);
                }
                $current = $winner;
                $bandStart = $today->copy();
                $bandEnd = $today->copy();
            }
            if ($current !== null) {
                $resolvedWindows[] = array_merge($current, [
                    'start' => $bandStart,
                    'end'   => $bandEnd,
                ]);
            }
        }

        // Flatten the resolved windows into a per-date lookup so the
        // activities timeline can show "what irrigation is happening on
        // this calendar day" inside each date block. Deduplicated by
        // irrigation id — if the same irrigation covers multiple default
        // groupings that all land on this calendar day, we emit ONE row
        // and list all the relevant group names inside it. Without this
        // dedupe an irrigation in 2 groups would print twice on every
        // overlapping day.
        $byDateAndId = []; // [dateKey => [irrigationId => entry]]
        foreach ($resolvedWindows as $win) {
            $cursor = $win['start']->copy();
            $irrId  = (int) $win['irrigation']->id;
            while ($cursor->lte($win['end'])) {
                $dateKey = $cursor->format('Y-m-d');
                if (!isset($byDateAndId[$dateKey][$irrId])) {
                    $byDateAndId[$dateKey][$irrId] = [
                        'irrigation' => $win['irrigation'],
                        'taskMeta'   => $win['taskMeta'],
                        'priority'   => $win['priority'],
                        'groupNames' => [],
                    ];
                }
                if (!empty($win['groupName'])
                    && !in_array($win['groupName'], $byDateAndId[$dateKey][$irrId]['groupNames'], true)) {
                    $byDateAndId[$dateKey][$irrId]['groupNames'][] = $win['groupName'];
                }
                $cursor->addDay();
            }
        }
        // Strip the inner irrigation-id keys so the view can foreach over
        // a plain numeric list. Order is by priority asc, then irrigation
        // id desc — same precedence the band-cut algorithm uses for ties.
        $irrigationsByDate = [];
        foreach ($byDateAndId as $dateKey => $entries) {
            usort($entries, function ($a, $b) {
                if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
                return (int) $b['irrigation']->id <=> (int) $a['irrigation']->id;
            });
            $irrigationsByDate[$dateKey] = $entries;
        }

        // Per-week segmenting on the resolved (priority-cut) bands.
        foreach ($resolvedWindows as $win) {
            $irrigation = $win['irrigation'];
            $taskMeta   = $win['taskMeta'];
            $cycleStart = $win['start'];
            $cycleEnd   = $win['end'];
            $segStart   = $cycleStart->copy();
            while ($segStart->lte($cycleEnd)) {
                $weekSunday = $segStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                $weekSaturday = $weekSunday->copy()->addDays(6);
                $segEnd = $weekSaturday->lt($cycleEnd) ? $weekSaturday->copy() : $cycleEnd->copy();
                $startCol = $segStart->dayOfWeek + 1; // Sun=0 → 1, Sat=6 → 7
                $endCol   = $segEnd->dayOfWeek + 1;
                $weekKey = $weekSunday->format('Y-m-d');
                $irrigationBandsByWeek[$weekKey][] = [
                    'irrigationId' => $irrigation->id,
                    'title'        => $irrigation->irrigationTitle,
                    'groupName'    => $win['groupName'],
                    'startCol'     => $startCol,
                    'endCol'       => $endCol,
                    'startDate'    => $segStart->copy(),
                    'endDate'      => $segEnd->copy(),
                    'dasStart'     => (int) $irrigation->startDay,
                    'dasEnd'       => (int) $irrigation->endDay,
                    'priority'     => (int) ($irrigation->priority ?? 5),
                    'taskType'     => $taskMeta['slug'],
                    'taskLabel'    => $taskMeta['label'],
                    'taskIcon'     => $taskMeta['icon'],
                    'color'        => $taskMeta['color'],
                ];
                $segStart = $segEnd->copy()->addDay();
            }
        }
        // Greedy row-packing per week so non-overlapping bands share a row.
        foreach ($irrigationBandsByWeek as $weekKey => &$bands) {
            usort($bands, function ($a, $b) {
                if ($a['startCol'] !== $b['startCol']) return $a['startCol'] <=> $b['startCol'];
                return $a['endCol'] <=> $b['endCol'];
            });
            $rowEnds = []; // rowIdx → last endCol in that row
            foreach ($bands as &$band) {
                $placed = false;
                foreach ($rowEnds as $rowIdx => $endCol) {
                    if ($band['startCol'] > $endCol) {
                        $band['row'] = $rowIdx + 1;
                        $rowEnds[$rowIdx] = $band['endCol'];
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $rowEnds[] = $band['endCol'];
                    $band['row'] = count($rowEnds);
                }
            }
            unset($band);
        }
        unset($bands);

        // Span lookup: keep a date → count for span-extension (just so days
        // touched by an irrigation extend $lastDate even if no activities
        // happen on them).
        $irrigationDates = [];
        foreach ($irrigationBandsByWeek as $bands) {
            foreach ($bands as $b) {
                $cursor = $b['startDate']->copy();
                while ($cursor->lte($b['endDate'])) {
                    $irrigationDates[$cursor->format('Y-m-d')] = true;
                    $cursor->addDay();
                }
            }
        }

        // ---- Activity calendar lookup ----
        // Single-day activities live in the cells they happen on (chip inside
        // the cell). Multi-day activities are emitted as week-segmented bands
        // (just like irrigation cycles) so the date range reads as one long
        // bar above the cells it spans instead of being repeated per day.
        $activitiesByDate = [];
        $activityBandsByWeek = [];
        $priorityColors = [
            'critical' => '#8a1d1d',
            'high'     => '#c95a35',
            'medium'   => '#5b8c3a',
            'low'      => '#6b7280',
        ];
        foreach ($schedule->activities as $activity) {
            if (!$activity->targetDate) continue;
            $start = $activity->targetDate;
            $end = $activity->targetEndDate ? $activity->targetEndDate : $start;
            $isRange = $end->gt($start);

            if (!$isRange) {
                $key = $start->format('Y-m-d');
                if (!isset($activitiesByDate[$key])) $activitiesByDate[$key] = [];
                $activitiesByDate[$key][] = $activity;
                continue;
            }

            // Multi-day → split into per-week contiguous segments.
            $segStart = $start->copy();
            $workerList = $activity->workers->pluck('workerName')->implode(', ');
            while ($segStart->lte($end)) {
                $weekSunday   = $segStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                $weekSaturday = $weekSunday->copy()->addDays(6);
                $segEnd       = $weekSaturday->lt($end) ? $weekSaturday->copy() : $end->copy();
                $startCol     = $segStart->dayOfWeek + 1;
                $endCol       = $segEnd->dayOfWeek + 1;
                $weekKey      = $weekSunday->format('Y-m-d');
                $activityBandsByWeek[$weekKey][] = [
                    'activityId' => $activity->id,
                    'title'      => $activity->activityTitle,
                    'workers'    => $workerList,
                    'priority'   => $activity->priority,
                    'color'      => $priorityColors[$activity->priority] ?? '#5b8c3a',
                    'startCol'   => $startCol,
                    'endCol'     => $endCol,
                    'startDate'  => $segStart->copy(),
                    'endDate'    => $segEnd->copy(),
                    'totalStart' => $start->copy(),
                    'totalEnd'   => $end->copy(),
                ];
                $segStart = $segEnd->copy()->addDay();
            }
        }
        // Greedy row-pack per week so non-overlapping bands share a row.
        foreach ($activityBandsByWeek as $weekKey => &$bands) {
            usort($bands, function ($a, $b) {
                if ($a['startCol'] !== $b['startCol']) return $a['startCol'] <=> $b['startCol'];
                return $a['endCol'] <=> $b['endCol'];
            });
            $rowEnds = [];
            foreach ($bands as &$band) {
                $placed = false;
                foreach ($rowEnds as $rowIdx => $endCol) {
                    if ($band['startCol'] > $endCol) {
                        $band['row'] = $rowIdx + 1;
                        $rowEnds[$rowIdx] = $band['endCol'];
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $rowEnds[] = $band['endCol'];
                    $band['row'] = count($rowEnds);
                }
            }
            unset($band);
        }
        unset($bands);

        // ---- Calendar span: earliest of any activity/irrigation, latest of either ----
        $firstDate = null;
        $lastDate = null;
        foreach ($schedule->activities as $a) {
            if (!$a->targetDate) continue;
            $s = $a->targetDate;
            $e = $a->targetEndDate ? $a->targetEndDate : $s;
            if (!$firstDate || $s->lt($firstDate)) $firstDate = $s->copy();
            if (!$lastDate || $e->gt($lastDate))   $lastDate  = $e->copy();
        }
        foreach (array_keys($irrigationDates) as $dateKey) {
            $d = \Illuminate\Support\Carbon::parse($dateKey);
            if (!$firstDate || $d->lt($firstDate)) $firstDate = $d->copy();
            if (!$lastDate || $d->gt($lastDate))   $lastDate  = $d->copy();
        }

        // ---- Build the list of months that the calendar should span ----
        $calendarMonths = [];
        if ($firstDate && $lastDate) {
            $cursor = $firstDate->copy()->startOfMonth();
            $end    = $lastDate->copy()->endOfMonth();
            while ($cursor->lte($end)) {
                $calendarMonths[] = $cursor->copy();
                $cursor->addMonth();
            }
        }

        // Base64-encode attachment images so the printed PDF is fully
        // self-contained (no http:// fetches during PDF generation). Cap at
        // ~5MB per image to keep the rendered HTML reasonable.
        $attachmentsEmbedded = [];
        foreach ($schedule->attachments as $att) {
            $payload = [
                'id'          => $att->id,
                'filename'    => $att->filename,
                'mimeType'    => $att->mimeType,
                'description' => $att->description,
                'isImage'     => $att->isImage(),
                'dataUri'     => null,
                'url'         => $att->getPublicUrl(),
            ];
            $abs = $att->getAbsolutePath();
            if ($abs && $att->isImage() && filesize($abs) < 5 * 1024 * 1024) {
                $bytes = @file_get_contents($abs);
                if ($bytes !== false) {
                    $payload['dataUri'] = 'data:' . $att->mimeType . ';base64,' . base64_encode($bytes);
                }
            }
            $attachmentsEmbedded[] = $payload;
        }

        // Same treatment for activity reference images — keyed by activity
        // id so the view can do {{ $activityImages[$a->id] ?? $a->imageUrl() }}
        // and silently fall back to the public URL when the embed is too
        // large to inline. Cap at 3MB per image; the activity list can be
        // long and we want the rendered HTML to stay under ~50MB even with
        // dozens of activities each carrying an image.
        $activityImages = [];
        foreach ($schedule->activities as $a) {
            if (empty($a->imagePath)) continue;
            $abs = $a->imageAbsolutePath();
            if (!$abs || filesize($abs) >= 3 * 1024 * 1024) continue;
            $bytes = @file_get_contents($abs);
            if ($bytes === false) continue;
            $mime = function_exists('mime_content_type')
                ? (mime_content_type($abs) ?: 'image/jpeg')
                : 'image/jpeg';
            $activityImages[$a->id] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        }

        // Optional-section toggles set by the pre-generate modal in the
        // activities tab.
        $showDescriptions = $request->boolean('showDesc');
        $showIrrigation   = $request->boolean('showIrrigation');
        $showCalendar     = $request->boolean('showCalendar');

        // Labor-only mode: hide intro tables, the activities timeline,
        // irrigation, and calendar. Only the per-worker pages + monthly
        // labor counts remain. When labor-only is on, the optional toggles
        // for irrigation/calendar are effectively forced off.
        $laborOnly = $request->boolean('laborOnly');
        if ($laborOnly) {
            $showIrrigation = false;
            $showCalendar   = false;
        }

        // Skip workers with zero assigned work days — typically rows the user
        // added to the schedule but hasn't assigned to any activity in the
        // protocol yet. Done BEFORE the explicit workerIds filter so that
        // filter only narrows among the workers who actually have something
        // to do.
        $workerStats = array_values(array_filter(
            $workerStats,
            fn ($row) => (int) $row['totalDays'] > 0
        ));

        // Worker filter: empty = include everyone (default). When set,
        // workerStats is filtered to only those IDs so the per-worker pages
        // section renders exactly the picked workers, in priority order.
        $workerIdsFilter = array_values(array_unique(array_filter(
            array_map('intval', (array) $request->input('workerIds', []))
        )));
        if (!empty($workerIdsFilter)) {
            $workerStats = array_values(array_filter(
                $workerStats,
                fn ($row) => in_array((int) $row['worker']->id, $workerIdsFilter, true)
            ));
        }

        return view('sm.documents.worker-presentation', [
            'schedule'              => $schedule,
            'lotDayZero'            => $lotDayZero,
            'workerStats'           => $workerStats,
            'aggregateMonthly'      => $aggregateMonthly,
            'irrigationBandsByWeek' => $irrigationBandsByWeek,
            'irrigationsByDate'     => $irrigationsByDate,
            'activityBandsByWeek'   => $activityBandsByWeek,
            'activitiesByDate'      => $activitiesByDate,
            'calendarMonths'        => $calendarMonths,
            'firstDate'             => $firstDate,
            'lastDate'              => $lastDate,
            'generatedAt'           => \Illuminate\Support\Carbon::now('Asia/Manila'),
            'showDescriptions'      => $showDescriptions,
            'showIrrigation'        => $showIrrigation,
            'showCalendar'          => $showCalendar,
            'laborOnly'             => $laborOnly,
            'workerIdsFilter'       => $workerIdsFilter,
            'attachmentsEmbedded'   => $attachmentsEmbedded,
            'activityImages'        => $activityImages,
        ]);
    }

    /**
     * PowerPoint-style per-day slides: one slide per calendar day that has
     * an activity, irrigation, or note. Multi-day activities recur on every
     * covered day with a "Day X of Y" indicator.
     *
     * Per-day irrigation is priority-resolved (same algorithm the worker
     * presentation uses) so a priority-1 "No Irrigation" interrupt
     * correctly displaces lower-priority bands on overlapping days.
     */
    public function cardViewer(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots',
            'workers',
            // Hidden activities are excluded from the card viewer — same
            // policy as worker presentation and export.
            'activities' => fn ($q) => $q->where('isHidden', false)->orderBy('targetDate', 'asc'),
            'activities.workers',
            'activities.lots',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('sortOrder', 'asc'),
            'irrigations.workers',
            'irrigations.lots',
            'irrigations.assignedWorker',
            'defaultGroupings.lots',
            'versions',
            'dateNotes',
            'criticalRules',
        ]);

        $activeVersion = $schedule->versions->firstWhere('isActive', true)
            ?? $schedule->versions->firstWhere('isOriginal', true)
            ?? $schedule->versions->first();

        $irrigationsByDate = $this->resolveIrrigationsByDate($schedule);
        $dateNotesByDate   = $schedule->dateNotes->keyBy(fn ($n) => $n->noteDate->format('Y-m-d'));

        // Collect every calendar day that needs a slide: anything spanned
        // by an activity, any day with irrigation, any day with a note.
        // Empty days are skipped — they'd be uninformative slides.
        $dateSet = [];
        foreach ($schedule->activities as $a) {
            if (!$a->targetDate) continue;
            $start = $a->targetDate;
            $end   = $a->targetEndDate ?: $start;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateSet[$d->format('Y-m-d')] = true;
            }
        }
        foreach (array_keys($irrigationsByDate) as $dk) $dateSet[$dk] = true;
        foreach ($dateNotesByDate->keys() as $dk)       $dateSet[$dk] = true;
        ksort($dateSet);
        $dateKeys = array_keys($dateSet);

        $firstDate = !empty($dateKeys) ? \Illuminate\Support\Carbon::parse($dateKeys[0])                    : null;
        $lastDate  = !empty($dateKeys) ? \Illuminate\Support\Carbon::parse($dateKeys[count($dateKeys) - 1]) : null;

        // Build per-slide data. activitiesForDay includes EVERY activity
        // whose [start, end] range covers this date — so multi-day
        // activities recur across their span.
        $slides = [];
        foreach ($dateKeys as $idx => $dateKey) {
            $dateCarbon = \Illuminate\Support\Carbon::parse($dateKey);
            $activitiesForDay = $schedule->activities->filter(function ($a) use ($dateCarbon) {
                if (!$a->targetDate) return false;
                $start = $a->targetDate;
                $end   = $a->targetEndDate ?: $start;
                return $start->lte($dateCarbon) && $dateCarbon->lte($end);
            })->values();

            $slides[] = [
                'dateKey'     => $dateKey,
                'date'        => $dateCarbon,
                'dayIndex'    => $idx + 1,
                'activities'  => $activitiesForDay,
                'irrigations' => $irrigationsByDate[$dateKey] ?? [],
                'note'        => $dateNotesByDate->get($dateKey),
            ];
        }

        return view('sm.documents.card-viewer', [
            'schedule'      => $schedule,
            'activeVersion' => $activeVersion,
            'slides'        => $slides,
            'firstDate'     => $firstDate,
            'lastDate'      => $lastDate,
            'criticalRules' => $schedule->criticalRules,
            'generatedAt'   => \Illuminate\Support\Carbon::now('Asia/Manila'),
        ]);
    }

    /**
     * Per-date map of priority-resolved irrigation entries. Same algorithm
     * the workerPresentation uses inline — extracted here so the card
     * viewer can reuse it without duplicating the band-cut logic.
     *
     * The map shape is: ['Y-m-d' => [
     *   ['irrigation' => Model, 'taskMeta' => array, 'priority' => int,
     *    'groupNames' => string[]],
     *   ...
     * ]]
     *
     * Deduplicated by irrigation id within each date — when the same
     * irrigation covers multiple default-groupings on the same calendar
     * day, the entry lists every affected group via groupNames[].
     */
    private function resolveIrrigationsByDate(\App\Models\AsCroppingSchedule $schedule): array
    {
        $rawWindows = [];
        foreach ($schedule->irrigations as $irrigation) {
            $taskMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($irrigation->taskType);
            $priority = (int) ($irrigation->priority ?? 5);

            if ($irrigation->dayMode === 'date' && $irrigation->startDate && $irrigation->endDate) {
                $rawWindows[] = [
                    'start'      => $irrigation->startDate->copy(),
                    'end'        => $irrigation->endDate->copy(),
                    'groupKey'   => '__absolute__',
                    'groupName'  => '',
                    'priority'   => $priority,
                    'irrigation' => $irrigation,
                    'taskMeta'   => $taskMeta,
                ];
            } else {
                foreach ($schedule->defaultGroupings as $group) {
                    $groupStart = $group->startDate ? \Illuminate\Support\Carbon::parse($group->startDate) : null;
                    if (!$groupStart) continue;
                    $rawWindows[] = [
                        'start'      => $groupStart->copy()->addDays((int) $irrigation->startDay),
                        'end'        => $groupStart->copy()->addDays((int) $irrigation->endDay),
                        'groupKey'   => 'g' . $group->id,
                        'groupName'  => $group->groupName,
                        'priority'   => $priority,
                        'irrigation' => $irrigation,
                        'taskMeta'   => $taskMeta,
                    ];
                }
            }
        }

        // Per-day winners within each group context (priority resolution).
        $dayWinners = [];
        foreach ($rawWindows as $win) {
            $cursor = $win['start']->copy();
            while ($cursor->lte($win['end'])) {
                $dateKey = $cursor->format('Y-m-d');
                $existing = $dayWinners[$win['groupKey']][$dateKey] ?? null;
                $existingPriority = $existing['priority'] ?? PHP_INT_MAX;
                $beats = $win['priority'] < $existingPriority
                    || ($win['priority'] === $existingPriority
                        && (int) $win['irrigation']->id > (int) ($existing['irrigation']->id ?? -1));
                if ($beats) {
                    $dayWinners[$win['groupKey']][$dateKey] = $win;
                }
                $cursor->addDay();
            }
        }

        // Flatten + deduplicate by irrigation id; collect groupNames.
        $byDateAndId = [];
        foreach ($dayWinners as $groupKey => $byDate) {
            foreach ($byDate as $dateKey => $winner) {
                $irrId = (int) $winner['irrigation']->id;
                if (!isset($byDateAndId[$dateKey][$irrId])) {
                    $byDateAndId[$dateKey][$irrId] = [
                        'irrigation' => $winner['irrigation'],
                        'taskMeta'   => $winner['taskMeta'],
                        'priority'   => $winner['priority'],
                        'groupNames' => [],
                    ];
                }
                if (!empty($winner['groupName'])
                    && !in_array($winner['groupName'], $byDateAndId[$dateKey][$irrId]['groupNames'], true)) {
                    $byDateAndId[$dateKey][$irrId]['groupNames'][] = $winner['groupName'];
                }
            }
        }

        $result = [];
        foreach ($byDateAndId as $dateKey => $entries) {
            usort($entries, function ($a, $b) {
                if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
                return (int) $b['irrigation']->id <=> (int) $a['irrigation']->id;
            });
            $result[$dateKey] = array_values($entries);
        }
        return $result;
    }
}
