<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleDateNote;
use App\Models\AsScheduleDayExpense;
use App\Models\AsScheduleDefaultGroupingLot;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleMaterial;
use App\Models\AsScheduleService;
use App\Models\AsScheduleWorker;
use App\Services\ScheduleReadinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Activities module — faithful port of the mother app's ActivityController
 * (btc-check aniSensoAdmin\ScheduleManager\ActivityController) minus the
 * export / worker-presentation / card-viewer document endpoints, which live
 * in Manager\DocumentController in AniSystem.
 */
class ActivityController extends BaseScheduleController
{
    /**
     * Module page — the activity timeline. Server-renders the initial
     * timeline (date groups, rest days, markers, notes) exactly like the
     * mother setup tab so the page is useful before any JS runs.
     */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        $schedule->load([
            'lots',
            'workers' => fn ($q) => $q->orderBy('priority', 'asc'),
            'materials',
            'services',
            'versions',
            'activities.workers',
            'activities.lots',
            'activities.items.material',
            'activities.items.service',
            'dateNotes',
            'dayExpenses',
            'progressMarkers',
            'defaultGroupings.lots',
        ]);

        $draftsCount = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 1)
            ->count();

        $dateNotesByDate = $schedule->dateNotes->keyBy(fn ($n) => $n->noteDate->format('Y-m-d'));
        $markersByDate = $schedule->progressMarkers->keyBy(fn ($m) => $m->markerDate->format('Y-m-d'));
        $expensesByDate = $schedule->dayExpenses->groupBy(fn ($e) => $e->expenseDate->format('Y-m-d'));

        $activeVersion = $schedule->versions->firstWhere('isActive', true)
            ?? $schedule->versions->firstWhere('isOriginal', true)
            ?? $schedule->versions->first();

        // Positioned sticky notes that interleave with the day's activity cards.
        $inlineNotesByDate = \App\Models\AsInlineNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('versionId', $activeVersion?->id)
            ->orderBy('sortKey')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($n) => $n->noteDate->format('Y-m-d'));

        // Days the forecast store already holds a reading for, so the day
        // menu can offer "View saved weather" on exactly those.
        $savedWeatherDates = \App\Models\AsScheduleWeatherDay::where('deleteStatus', 1)
            ->where('croppingScheduleId', $schedule->id)
            ->orderBy('forecastDate')
            ->pluck('forecastDate')
            ->map(fn ($d) => $d instanceof \Illuminate\Support\Carbon ? $d->format('Y-m-d') : (string) $d)
            ->unique()
            ->values()
            ->all();

        return view('sm.activities', [
            'savedWeatherDates' => $savedWeatherDates,
            'schedule'          => $schedule,
            'draftsCount'       => $draftsCount,
            'dateNotesByDate'   => $dateNotesByDate,
            // Where a note's map attachment leads: its saved map in Maps.
            'mapUrlByPath'      => $this->mapUrlByPath($schedule->id),
            'inlineNotesByDate' => $inlineNotesByDate,
            'expensesByDate'    => $expensesByDate,
            'markersByDate'     => $markersByDate,
            'activeVersion'     => $activeVersion,
            'activityTypes'   => AsScheduleActivity::ACTIVITY_TYPES,
            'waterTasks'      => AsScheduleActivity::WATER_TASKS,
            'itemCatalog'     => $this->itemCatalog($schedule),
            'readiness'       => (new ScheduleReadinessService())->check($schedule),
        ]);
    }

    /**
     * What is still missing from this schedule. Polled by the readiness bell
     * so the badge stays honest after an edit without a page reload.
     */
    public function readiness(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $schedule->load(['lots', 'workers', 'materials', 'services', 'activities.lots']);

        return response()->json([
            'success' => true,
            'message' => 'Readiness loaded.',
            'data'    => (new ScheduleReadinessService())->check($schedule),
        ]);
    }

    public function store(Request $request)
    {
        return $this->saveActivity($request, null);
    }

    public function update(Request $request)
    {
        return $this->saveActivity($request, $this->queryId($request));
    }

    public function show(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['items.material', 'items.service', 'lots', 'workers'])
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $payload = $activity->toArray();
        $payload['lotIds'] = $activity->lots->pluck('id');
        $payload['workerIds'] = $activity->workers->pluck('id');
        // Resolved public URL alongside the stored path so the sheet can
        // render the existing image immediately without re-deriving.
        $payload['imageUrl'] = $activity->imageUrl();
        $payload['images'] = $activity->imageList();
        $payload['items'] = $this->serializeItems($activity);
        $payload = array_merge($payload, $this->serializeWorkerPay($activity));

        return $this->jsonOk('Activity loaded.', ['data' => $payload]);
    }

    /**
     * What went on this ground before, and how long ago.
     *
     * Standing in front of a task, the question is rarely "what is this" —
     * it is "when did this lot last get a herbicide", because the answer
     * decides whether today's spray is safe, wasted, or the second one in a
     * week. The board holds the answer and made a person scroll for it.
     *
     * Counted per category, per lot, against everything before this task's
     * own date, drafts excluded — a draft has not happened.
     */
    public function advanced(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with('lots')
            ->first();
        if (! $activity) {
            return $this->jsonFail('Activity not found.', 404);
        }

        // The categories a grower actually asks about, in the order they ask.
        $groups = [
            'herbicide' => ['label' => 'Herbicide', 'types' => ['herbicide']],
            'foliar_spray' => ['label' => 'Foliar spray', 'types' => ['foliar_spray']],
            'fertilizer' => ['label' => 'Granular fertiliser', 'types' => ['fertilizer']],
            'pesticide' => ['label' => 'Pesticide / insecticide', 'types' => ['pesticide']],
            'fungicide' => ['label' => 'Fungicide', 'types' => ['fungicide']],
            'copper_fungicide' => ['label' => 'Copper-based fungicide', 'types' => ['copper_fungicide']],
            'microbial' => ['label' => 'Microbial / bio', 'types' => ['microbial']],
        ];

        $on = $activity->targetDate ? $activity->targetDate->copy()->startOfDay() : now()->startOfDay();
        $lotIds = $activity->lots->pluck('id')->all();

        // Everything on this schedule that happened before this task, on the
        // lots this task covers (or anywhere, when it covers none).
        $earlier = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 0)
            ->where('id', '!=', $activity->id)
            ->whereDate('targetDate', '<=', $on->toDateString())
            ->with('lots')
            ->orderByDesc('targetDate')
            ->get()
            ->filter(function ($a) use ($lotIds, $activity, $on) {
                if ($a->targetDate && $a->targetDate->isSameDay($on) && $a->id > $activity->id) {
                    return false;   // same day, entered after: not "before"
                }
                if (! $lotIds) {
                    return true;
                }
                $its = $a->lots->pluck('id')->all();

                return ! $its || array_intersect($its, $lotIds);
            });

        $rows = [];
        foreach ($groups as $key => $g) {
            $hit = $earlier->first(fn ($a) => (bool) array_intersect($a->typeSlugs(), $g['types']));
            $rows[] = [
                'key' => $key,
                'label' => $g['label'],
                'title' => $hit?->activityTitle,
                'date' => $hit?->targetDate?->toDateString(),
                'when' => $hit?->targetDate?->format('M j, Y'),
                // Days between that one and this one. 0 means the same day,
                // which is worth seeing rather than rounding away.
                'daysBefore' => $hit && $hit->targetDate
                    ? (int) $hit->targetDate->copy()->startOfDay()->diffInDays($on)
                    : null,
                'lots' => $hit ? $hit->lots->pluck('lotName')->all() : [],
            ];
        }

        return $this->jsonOk('Advanced info.', [
            'data' => [
                'activity' => [
                    'id' => $activity->id,
                    'title' => $activity->activityTitle,
                    'date' => $activity->targetDate?->format('M j, Y'),
                    'types' => $activity->typeLabels(),
                ],
                'lots' => $activity->lots->pluck('lotName')->all(),
                'rows' => $rows,
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if ($schedule->isLocked()) {
            return $this->jsonFail('This schedule is marked completed and locked. Reopen it in the Hub to make changes.', 423);
        }
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        // Soft-delete is reversible, so we keep the image file on disk
        // even after deletion — restoration brings it back wired up.
        $activity->update(['deleteStatus' => 0]);
        $this->broadcastBoard($schedule, 'deleted', ['id' => $activity->id], $activity->versionId);
        return $this->jsonOk('Activity deleted.');
    }

    /**
     * Toggle the per-activity isHidden flag. Hidden activities stay on
     * the timeline (dimmed, with a "Hidden" tag) but are filtered out of
     * the worker presentation, card viewer, and export schedule.
     */
    public function toggleHidden(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $next = !((bool) $activity->isHidden);
        $activity->update(['isHidden' => $next]);
        $this->broadcastBoard($schedule, 'toggle-hidden', ['id' => $activity->id, 'isHidden' => $next], $activity->versionId);

        return $this->jsonOk($next ? 'Activity hidden from presentations.' : 'Activity restored to presentations.', [
            'data' => [
                'id'       => $activity->id,
                'isHidden' => $next,
            ],
        ]);
    }

    /**
     * Toggle the per-activity isDone flag. Done activities lock on the
     * timeline: no dragging, no full edit — only appended notes.
     */
    public function toggleDone(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $next = !((bool) $activity->isDone);
        $activity->update(['isDone' => $next]);
        $this->broadcastBoard($schedule, 'toggle-done', ['id' => $activity->id, 'isDone' => $next], $activity->versionId);

        return $this->jsonOk($next ? 'Marked as done — the activity is now locked.' : 'Reopened for editing.', [
            'data' => [
                'id'     => $activity->id,
                'isDone' => $next,
            ],
        ]);
    }

    /**
     * Append a timestamped note paragraph to a (locked) activity's
     * description without touching anything else on it.
     */
    public function appendNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $note = trim((string) $request->input('note', ''));
        // Photos attached to the note: paths from prior image-upload calls,
        // constrained to this schedule's own upload directory.
        $images = collect((array) $request->input('images', []))
            ->filter(fn ($p) => is_string($p) && str_starts_with($p, 'schedule-activities/' . $schedule->id . '/'))
            ->take(8)
            ->values();
        if ($note === '' && $images->isEmpty()) {
            return $this->jsonFail('Write the note first.', 422);
        }

        $stamp = now()->format('M j, Y g:i A');
        $paragraph = '<p><em>Note (' . e($stamp) . '):</em>' . ($note !== '' ? ' ' . e($note) : '') . '</p>';
        foreach ($images as $p) {
            $paragraph .= '<img src="' . e(\App\Support\MediaStore::url($p)) . '" alt="Note photo">';
        }
        $activity->update(['description' => trim(($activity->description ?? '') . $paragraph)]);
        $this->broadcastBoard($schedule, 'reload', ['id' => $activity->id], $activity->versionId);

        return $this->jsonOk('Note added.', [
            'data' => [
                'id'          => $activity->id,
                'description' => $activity->description,
            ],
        ]);
    }

    /**
     * Upload a reference image for an activity. Written to the public disk
     * under schedule-activities/{scheduleId}/{uuid}.{ext}; the relative
     * path is returned so the client stashes it in a hidden input and the
     * next activity save persists it. Orphans (upload without save) are
     * tolerated.
     */
    public function uploadImage(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
        ], [
            'image.required' => 'Pick an image to upload.',
            'image.image'    => 'File must be an image.',
            'image.mimes'    => 'Allowed types: JPG, PNG, WebP, GIF.',
            'image.max'      => 'Image is too large — max 8 MB.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $file         = $request->file('image');
        // Extension derived from content, never the client filename (RCE/XSS guard).
        $ext          = \App\Support\UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $stem         = Str::uuid()->toString();
        $relativeDir  = 'schedule-activities/' . $schedule->id;
        $relativePath = $relativeDir . '/' . $stem . '.' . $ext;

        try {
            $stored = \App\Support\MediaStore::putFile($file, 'schedule-activities', $schedule->id);
            if ($stored === null) {
                return $this->jsonFail('Image upload failed.', 500);
            }
            $relativePath = $stored;
        } catch (\Throwable $e) {
            return $this->jsonFail('Image upload failed: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Image uploaded.', [
            'data' => [
                'imagePath' => $relativePath,
                'imageUrl'  => asset('storage/' . $relativePath),
            ],
        ]);
    }

    /**
     * Aggregate the labor cost for every non-draft activity on the schedule.
     *
     * Cost rule per (activity, worker, day):
     *   - timeRequired = 'n/a'   → ₱0
     *   - timeRequired = 'half'  → 1× worker.costPerHalfDay per day in range
     *   - timeRequired = 'whole' → 2× worker.costPerHalfDay per day in range
     *
     * Optional query filters: groupIds[] (resolved to lotIds), lotIds[],
     * workerIds[], dasMin/dasMax, startDate/endDate (pro-rated window).
     */
    /** The Labor Report page — charts + breakdown; data comes from laborSummary. */
    public function laborReportPage(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load('workers');

        return view('sm.labor-report', ['schedule' => $schedule]);
    }

    public function laborSummary(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $activities = $schedule->activities()
            ->with(['workers' => fn ($q) => $q->orderBy('priority', 'asc'), 'lots'])
            ->get();

        // --- Parse filters ---
        $groupIdsFilter  = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('groupIds', [])))));
        $lotIdsRaw       = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('lotIds', [])))));
        $workerIdsFilter = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('workerIds', [])))));

        // Resolve groups → lots and merge with any explicit lotIds filter.
        $lotIdsFromGroups = [];
        if (!empty($groupIdsFilter)) {
            $lotIdsFromGroups = AsScheduleDefaultGroupingLot::whereIn('defaultGroupingId', $groupIdsFilter)
                ->pluck('lotId')->map(fn ($id) => (int) $id)->all();
        }
        $lotIdsFilter = array_values(array_unique(array_merge($lotIdsRaw, $lotIdsFromGroups)));

        $hasLotFilter    = !empty($lotIdsFilter);
        $hasWorkerFilter = !empty($workerIdsFilter);
        $hasDasMin = $request->filled('dasMin') && is_numeric($request->dasMin);
        $hasDasMax = $request->filled('dasMax') && is_numeric($request->dasMax);
        $hasDasFilter = $hasDasMin || $hasDasMax;
        $dasMin = $hasDasMin ? (int) $request->dasMin : PHP_INT_MIN;
        $dasMax = $hasDasMax ? (int) $request->dasMax : PHP_INT_MAX;

        // Calendar date range filter — multi-day activities pro-rated to the
        // days inside the window; non-overlapping activities dropped.
        $hasStartDate = $request->filled('startDate');
        $hasEndDate   = $request->filled('endDate');
        $hasDateFilter = $hasStartDate || $hasEndDate;
        try {
            $dateMin = $hasStartDate ? Carbon::parse($request->input('startDate'))->startOfDay() : null;
            $dateMax = $hasEndDate   ? Carbon::parse($request->input('endDate'))->endOfDay()   : null;
        } catch (\Throwable $e) {
            $dateMin = $dateMax = null;
            $hasDateFilter = false;
        }

        // --- Effective Day 0 anchor per lot (matches the JS recompute logic). ---
        $lotDayZero = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $lotDayZero[$lot->id] = Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($activities as $a) {
            if (!$a->isDayZero || !$a->targetDate) continue;
            $aDate = Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (!isset($lotDayZero[$lot->id]) || $aDate->lt($lotDayZero[$lot->id])) {
                    $lotDayZero[$lot->id] = $aDate->copy();
                }
            }
        }

        $perWorker = [];
        $perActivity = [];
        $grandTotal = 0.0;
        $totals = ['halfDays' => 0, 'wholeDays' => 0, 'naCount' => 0, 'totalAssignments' => 0];

        $phaseTemplate = ['count' => 0, 'cost' => 0.0, 'assignments' => 0, 'halfDays' => 0, 'wholeDays' => 0, 'naCount' => 0];
        $phases = [
            'preDayZero' => $phaseTemplate,
            'cropping'   => $phaseTemplate,
            'unanchored' => $phaseTemplate,
        ];

        $unitsFor = function ($timeRequired) {
            return match ($timeRequired) {
                'whole' => 2,
                'half'  => 1,
                default => 0,
            };
        };

        foreach ($activities as $activity) {
            $activityLotIds = $activity->lots->pluck('id')->all();

            // --- Lot filter: activity must cover at least one selected lot ---
            if ($hasLotFilter && empty(array_intersect($activityLotIds, $lotIdsFilter))) {
                continue;
            }

            // --- DAS: at least one considered lot must yield a DAS in range ---
            $activityDas = null;
            $candidateDeltas = [];
            if ($activity->targetDate) {
                $aDate = Carbon::parse($activity->targetDate);
                $consideredLotIds = $hasLotFilter
                    ? array_values(array_intersect($activityLotIds, $lotIdsFilter))
                    : $activityLotIds;
                foreach ($consideredLotIds as $lotId) {
                    if (!isset($lotDayZero[$lotId])) continue;
                    $delta = (int) $lotDayZero[$lotId]->diffInDays($aDate, false);
                    $candidateDeltas[] = $delta;
                }
                if (!empty($candidateDeltas)) {
                    $activityDas = min($candidateDeltas);
                }
            }
            if ($hasDasFilter) {
                if ($activityDas === null) continue;
                $inRange = false;
                foreach ($candidateDeltas as $delta) {
                    if ($delta >= $dasMin && $delta <= $dasMax) { $inRange = true; break; }
                }
                if (!$inRange) continue;
            }

            // --- Worker filter ---
            $effectiveWorkers = $hasWorkerFilter
                ? $activity->workers->whereIn('id', $workerIdsFilter)
                : $activity->workers;
            if ($hasWorkerFilter && $effectiveWorkers->count() === 0) {
                continue;
            }

            $units = $unitsFor($activity->timeRequired);

            $actStart = $activity->targetDate;
            $actEnd   = $activity->targetEndDate ?: $activity->targetDate;
            $rangeDays = 1;
            if ($actStart && $actEnd) {
                $rangeDays = (int) $actStart->diffInDays($actEnd) + 1;
                if ($rangeDays < 1) $rangeDays = 1;
            }

            // Calendar-date filter: clip [start, end] to [dateMin, dateMax].
            if ($hasDateFilter) {
                if (!$actStart || !$actEnd) continue; // can't compare without dates
                $clipStart = $dateMin && $actStart->lt($dateMin) ? $dateMin->copy() : $actStart->copy();
                $clipEnd   = $dateMax && $actEnd->gt($dateMax)   ? $dateMax->copy() : $actEnd->copy();
                if ($clipStart->gt($clipEnd)) continue; // no overlap
                $rangeDays = (int) $clipStart->copy()->startOfDay()->diffInDays($clipEnd->copy()->startOfDay()) + 1;
                if ($rangeDays < 1) continue;
            }

            $phaseKey = 'unanchored';
            if ($activityDas !== null) {
                $phaseKey = $activityDas < 0 ? 'preDayZero' : 'cropping';
            }

            $activityCost = 0.0;
            $workerRateSum = 0.0;

            foreach ($effectiveWorkers as $worker) {
                $rate = (float) $worker->costPerHalfDay;
                $workerRateSum += $rate;
                $costPerWorkerPerDay = $rate * $units;
                $cost = $costPerWorkerPerDay * $rangeDays;
                $activityCost += $cost;
                $totals['totalAssignments']++;
                $phases[$phaseKey]['assignments']++;

                if (!isset($perWorker[$worker->id])) {
                    $perWorker[$worker->id] = [
                        'id'              => $worker->id,
                        'name'            => $worker->workerName,
                        'priority'        => (int) $worker->priority,
                        'costPerHalfDay'  => $rate,
                        'halfDays'        => 0,
                        'wholeDays'       => 0,
                        'naCount'         => 0,
                        'assignmentCount' => 0,
                        'total'           => 0.0,
                        'preDayZeroTotal' => 0.0,
                        'croppingTotal'   => 0.0,
                        'unanchoredTotal' => 0.0,
                    ];
                }
                $perWorker[$worker->id]['assignmentCount']++;
                $perWorker[$worker->id]['total'] += $cost;
                if ($phaseKey === 'preDayZero')   $perWorker[$worker->id]['preDayZeroTotal'] += $cost;
                elseif ($phaseKey === 'cropping') $perWorker[$worker->id]['croppingTotal']   += $cost;
                else                              $perWorker[$worker->id]['unanchoredTotal'] += $cost;

                if ($activity->timeRequired === 'half') {
                    $perWorker[$worker->id]['halfDays'] += $rangeDays;
                    $totals['halfDays']                 += $rangeDays;
                    $phases[$phaseKey]['halfDays']      += $rangeDays;
                } elseif ($activity->timeRequired === 'whole') {
                    $perWorker[$worker->id]['wholeDays'] += $rangeDays;
                    $totals['wholeDays']                 += $rangeDays;
                    $phases[$phaseKey]['wholeDays']      += $rangeDays;
                } else {
                    $perWorker[$worker->id]['naCount'] += $rangeDays;
                    $totals['naCount']                 += $rangeDays;
                    $phases[$phaseKey]['naCount']      += $rangeDays;
                }
            }

            $grandTotal += $activityCost;
            $phases[$phaseKey]['count']++;
            $phases[$phaseKey]['cost'] += $activityCost;

            $perActivity[] = [
                'id'            => $activity->id,
                'activityTitle' => $activity->activityTitle,
                'targetDate'    => $activity->targetDate ? $activity->targetDate->format('Y-m-d') : null,
                'targetEndDate' => $activity->targetEndDate ? $activity->targetEndDate->format('Y-m-d') : null,
                'rangeDays'     => $rangeDays,
                'timeRequired'  => $activity->timeRequired,
                'unitsPerDay'   => $units,
                'workerCount'   => $effectiveWorkers->count(),
                'workerRateSum' => round($workerRateSum, 2),
                'das'           => $activityDas,
                'phase'         => $phaseKey,
                'cost'          => round($activityCost, 2),
            ];
        }

        foreach ($phases as &$p) { $p['cost'] = round($p['cost'], 2); } unset($p);

        foreach ($perWorker as &$w) {
            $w['total']           = round($w['total'], 2);
            $w['preDayZeroTotal'] = round($w['preDayZeroTotal'], 2);
            $w['croppingTotal']   = round($w['croppingTotal'], 2);
            $w['unanchoredTotal'] = round($w['unanchoredTotal'], 2);
        }
        unset($w);

        usort($perWorker, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
            return strcmp($a['name'], $b['name']);
        });

        $filtersEcho = [
            'groupIds'  => $groupIdsFilter,
            'lotIds'    => $lotIdsFilter,
            'workerIds' => $workerIdsFilter,
            'dasMin'    => $hasDasMin ? $dasMin : null,
            'dasMax'    => $hasDasMax ? $dasMax : null,
            'startDate' => $dateMin ? $dateMin->format('Y-m-d') : null,
            'endDate'   => $dateMax ? $dateMax->format('Y-m-d') : null,
        ];

        return $this->jsonOk('Labor summary computed.', [
            'data' => [
                'grandTotal'      => round($grandTotal, 2),
                'totalActivities' => count($perActivity),
                'totals'          => $totals,
                'phases'          => $phases,
                'perWorker'       => array_values($perWorker),
                'perActivity'     => $perActivity,
                'filters'         => $filtersEcho,
                'dayType'         => $schedule->dayType,
                'scheduleTitle'   => $schedule->title,
            ],
        ]);
    }

    /**
     * Move an active activity into the Drafts bin (isDraft = 1).
     */
    public function toDraft(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ((int) $activity->isDraft === 1) {
            return $this->jsonFail('Activity is already a draft.', 422);
        }

        $activity->update(['isDraft' => 1]);
        return $this->jsonOk('Moved to drafts.');
    }

    /**
     * Pull a draft back onto the timeline (isDraft = 0). Returns the full
     * payload so the caller can render it without a follow-up fetch.
     */
    public function fromDraft(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ((int) $activity->isDraft !== 1) {
            return $this->jsonFail('Activity is not a draft.', 422);
        }

        $activity->update(['isDraft' => 0]);

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data = array_merge($data, $this->serializeWorkerPay($fresh));

        return $this->jsonOk('Restored from drafts.', ['data' => $data]);
    }

    /**
     * List every drafted activity for this schedule (lean payload).
     */
    public function listDrafts(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $drafts = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 1)
            ->with(['lots'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id'            => $a->id,
                    'activityTitle' => $a->activityTitle,
                    'targetDate'    => $a->targetDate ? $a->targetDate->format('Y-m-d') : null,
                    'targetEndDate' => $a->targetEndDate ? $a->targetEndDate->format('Y-m-d') : null,
                    'priority'      => $a->priority,
                    'isDayZero'     => (bool) $a->isDayZero,
                    'isTransplant'  => (bool) $a->isTransplant,
                    'updatedAt'     => $a->updated_at ? $a->updated_at->format('Y-m-d H:i') : null,
                    'lots'          => $a->lots->map(fn ($l) => ['id' => $l->id, 'lotName' => $l->lotName])->values(),
                ];
            });

        return $this->jsonOk('Drafts loaded.', ['data' => $drafts, 'count' => $drafts->count()]);
    }

    /**
     * Restore a soft-deleted activity (deleteStatus 0 → 1). Powers the
     * in-page undo stack.
     */
    public function restore(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ((int) $activity->deleteStatus === 1) {
            return $this->jsonFail('Activity is not deleted.', 422);
        }

        $activity->update(['deleteStatus' => 1]);

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data = array_merge($data, $this->serializeWorkerPay($fresh));

        return $this->jsonOk('Activity restored.', ['data' => $data]);
    }

    /**
     * Batch-update target date (+ optional end date) and manual sequence
     * order for many activities at once. Used by drag-and-drop and by the
     * group date-change / group-delete undo flows.
     *
     * Body: items: [{ id, targetDate, targetEndDate|null, sequenceOrder }]
     */
    public function reorder(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'items'                 => 'required|array|min:1',
            'items.*.id'            => 'required|integer',
            'items.*.targetDate'    => 'required|date',
            'items.*.targetEndDate' => 'nullable|date|after_or_equal:items.*.targetDate',
            'items.*.sequenceOrder' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $items = (array) $request->input('items');
        $ids = array_map(fn ($it) => (int) $it['id'], $items);

        // Only allow updates to activities owned by this schedule; done
        // activities are locked in place — only their sequence may change.
        $rows = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $ids)
            ->get(['id', 'isDone', 'versionId', 'targetDate', 'isDraft']);
        $validSet = $rows->pluck('id')->flip()->all();
        $doneSet = $rows->where('isDone', true)->pluck('id')->flip()->all();

        // Dragging a card to another day comes through here, so this is where
        // a day gets overfilled. Count what each destination is gaining and
        // refuse the whole move if it would push a day past its ceiling —
        // rejecting before the transaction leaves the board untouched.
        $arrivalsByDate = [];
        foreach ($items as $it) {
            $id = (int) $it['id'];
            $row = $rows->firstWhere('id', $id);
            if (! $row || isset($doneSet[$id]) || $row->isDraft) {
                continue;
            }
            $to = Carbon::parse($it['targetDate'])->format('Y-m-d');
            $from = $row->targetDate ? Carbon::parse($row->targetDate)->format('Y-m-d') : null;
            if ($to === $from) {
                continue;   // reordering within a day changes no day's size
            }
            $arrivalsByDate[$to][] = $id;
        }
        foreach ($arrivalsByDate as $date => $incomingIds) {
            $versionId = $rows->firstWhere('id', $incomingIds[0])->versionId;
            $already = $this->activitiesOnDate($schedule->id, $versionId, $date);
            if ($already + count($incomingIds) > self::MAX_ACTIVITIES_PER_DATE) {
                return $this->jsonFail(
                    'That day would go past ' . self::MAX_ACTIVITIES_PER_DATE
                        . ' activities — the most one day can hold. Move some to another day first.',
                    422
                );
            }
        }

        try {
            DB::transaction(function () use ($items, $validSet, $doneSet) {
                foreach ($items as $it) {
                    $id = (int) $it['id'];
                    if (!isset($validSet[$id])) continue;
                    $update = ['sequenceOrder' => (int) $it['sequenceOrder']];
                    if (!isset($doneSet[$id])) {
                        $update['targetDate'] = $it['targetDate'];
                        if (array_key_exists('targetEndDate', $it)) {
                            $update['targetEndDate'] = !empty($it['targetEndDate']) ? $it['targetEndDate'] : null;
                        }
                    }
                    AsScheduleActivity::where('id', $id)->update($update);
                }
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to reorder: ' . $e->getMessage(), 500);
        }

        $this->broadcastBoard($schedule, 'reordered', ['items' => $items], $this->activeVersionIdFor($schedule->id));
        return $this->jsonOk('Order saved.', ['count' => count($items)]);
    }

    /**
     * Update only the targetDate of an activity.
     */
    public function setDate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $validator = Validator::make($request->all(), [
            'targetDate' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ($activity->isDone) {
            return $this->jsonFail('This activity is marked done and locked in place.', 422);
        }

        // Dropping onto a day that is already full is refused, so the drag
        // springs back instead of quietly building an unusable day.
        $full = $this->dateFullMessage(
            $schedule->id,
            $activity->versionId,
            Carbon::parse($request->targetDate)->format('Y-m-d'),
            (int) $activity->id
        );
        if ($full) {
            return $this->jsonFail($full, 422);
        }

        $activity->update(['targetDate' => $request->targetDate]);
        $this->broadcastBoard($schedule, 'set-date', ['id' => $activity->id, 'targetDate' => $request->targetDate], $activity->versionId);

        return $this->jsonOk('Activity moved.', [
            'data' => ['id' => $activity->id, 'targetDate' => $request->targetDate],
        ]);
    }

    /**
     * Duplicate an activity: copy fields, items, and lot/worker pivots into
     * a new row (title + " (copy)"), landing in the currently-ACTIVE version.
     */
    public function duplicate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $source = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['items', 'lots', 'workers'])
            ->first();
        if (!$source) return $this->jsonFail('Activity not found.', 404);

        $activeVersionId = $this->activeVersionIdFor($schedule->id);

        // The copy lands on the source's day, so that day needs room for it.
        if (! $source->isDraft) {
            $full = $this->dateFullMessage(
                $schedule->id,
                $activeVersionId,
                $source->targetDate ? Carbon::parse($source->targetDate)->format('Y-m-d') : null
            );
            if ($full) {
                return $this->jsonFail($full, 422);
            }
        }

        try {
            $new = DB::transaction(function () use ($source, $activeVersionId) {
                $copy = $source->replicate(['sequenceOrder']);
                $copy->activityTitle = mb_substr($source->activityTitle, 0, 240) . ' (copy)';
                // Duplicate always lands in the currently-active version.
                if ($activeVersionId) {
                    $copy->versionId = $activeVersionId;
                }
                $copy->sourceActivityId = $source->id;
                $copy->save();

                // Copy active items
                foreach ($source->items as $item) {
                    if ((int) $item->deleteStatus !== 1) continue;
                    $itemCopy = $item->replicate();
                    $itemCopy->activityId = $copy->id;
                    $itemCopy->save();
                }

                // Copy lot + worker pivots
                $copy->lots()->sync($source->lots->pluck('id')->all());
                $copy->workers()->sync($source->workers->pluck('id')->all());

                return $copy;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to duplicate activity: ' . $e->getMessage(), 500);
        }

        $fresh = $new->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data = array_merge($data, $this->serializeWorkerPay($fresh));

        $this->broadcastBoard($schedule, 'saved', $data, $fresh->versionId);
        return $this->jsonOk('Activity duplicated.', ['data' => $data]);
    }

    /**
     * Uniform item shape for the front-end (legacy material/service rows
     * resolve their name/unit via the model helpers).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function serializeItems(AsScheduleActivity $activity)
    {
        return $activity->items->map(fn ($it) => [
            'id' => $it->id,
            'itemName' => $it->displayName(),
            'unitPrice' => $it->unitPrice !== null ? (float) $it->unitPrice : null,
            'quantity' => $it->quantity !== null ? (float) $it->quantity : null,
            'unitOfMeasure' => $it->displayUnit(),
        ])->values();
    }

    /**
     * Reusable item catalog for a schedule: every item name that's been used,
     * the distinct prices seen for each, and a suggested unit. Seeded from the
     * legacy materials/services catalogs plus all name-based activity items, so
     * users can re-select names/prices instead of retyping.
     *
     * @return array{names: array<int, string>, prices: array<string, array<int, string>>, units: array<string, string>}
     */
    private function itemCatalog(\App\Models\AsCroppingSchedule $schedule): array
    {
        $names = [];   // name => true
        $prices = [];  // name => [price => true]
        $units = [];   // name => unit

        $add = function ($name, $price, $unit) use (&$names, &$prices, &$units) {
            $name = trim((string) $name);
            if ($name === '') return;
            $names[$name] = true;
            if ($price !== null && $price !== '' && is_numeric($price)) {
                $prices[$name][number_format((float) $price, 2, '.', '')] = true;
            }
            if (filled($unit) && empty($units[$name])) {
                $units[$name] = $unit;
            }
        };

        foreach ($schedule->materials as $m) {
            $add($m->materialName, $m->priceAmount, $m->unitOfMeasure);
        }
        foreach ($schedule->services as $s) {
            $add($s->serviceName, $s->serviceCost, null);
        }
        AsScheduleActivityItem::query()
            ->join('as_schedule_activities', 'as_schedule_activities.id', '=', 'as_schedule_activity_items.activityId')
            ->where('as_schedule_activities.croppingScheduleId', $schedule->id)
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activity_items.deleteStatus', 1)
            ->whereNotNull('as_schedule_activity_items.itemName')
            ->get(['as_schedule_activity_items.itemName', 'as_schedule_activity_items.unitPrice', 'as_schedule_activity_items.unitOfMeasure'])
            ->each(fn ($r) => $add($r->itemName, $r->unitPrice, $r->unitOfMeasure));

        ksort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'names' => array_keys($names),
            'prices' => array_map(fn ($m) => array_keys($m), $prices),
            'units' => $units,
        ];
    }

    private function saveActivity(Request $request, $id = null)
    {
        $schedule = $this->scheduleFromRequest($request);
        if ($schedule->isLocked()) {
            return $this->jsonFail('This schedule is marked completed and locked. Reopen it in the Hub to make changes.', 423);
        }

        $validator = Validator::make($request->all(), [
            'activityTitle'   => 'required|string|max:255',
            'targetDate'      => 'required|date',
            'targetEndDate'   => 'nullable|date|after_or_equal:targetDate',
            'priority'        => 'required|in:critical,high,medium,low',
            'activityType'    => ['nullable', 'string', Rule::in(array_keys(AsScheduleActivity::ACTIVITY_TYPES))],
            // The rest of what is in the tank. Same catalogue, and the primary
            // is filtered out of it on the way in so nothing counts twice.
            'extraTypes'      => ['nullable', 'array', 'max:8'],
            'extraTypes.*'    => ['string', Rule::in(array_keys(AsScheduleActivity::ACTIVITY_TYPES))],
            'waterTask'       => ['nullable', 'string', Rule::in(array_keys(AsScheduleActivity::WATER_TASKS))],
            'servicePrice'    => 'nullable|numeric|min:0|max:99999999',
            'isDayZero'       => 'nullable|boolean',
            'isTransplant'    => 'nullable|boolean',
            'isDraft'         => 'nullable|boolean',
            'description'     => 'nullable|string|max:20000',
            // imagePath(s) are relative paths under the `public` disk, set by
            // prior image-upload calls. Empty = no image.
            'imagePath'       => 'nullable|string|max:500',
            'imagePaths'      => 'nullable|array|max:12',
            'imagePaths.*'    => 'string|max:500',
            'timeRequired'    => 'required|in:half,whole,n/a',
            // Empty lotIds = "N/A — not lot-specific".
            'lotIds'          => 'nullable|array',
            'lotIds.*'        => 'integer',
            'workerIds'       => 'nullable|array',
            // Per-worker: a whole day or a half, and an agreed amount when it
            // is not the worker's usual rate.
            'workerPay'          => 'nullable|array',
            'workerPay.*.dayPart' => 'nullable|in:whole,half',
            'workerPay.*.amount'  => 'nullable|numeric|min:0|max:9999999',
            'workerIds.*'     => 'integer',
            'items'                 => 'nullable|array',
            'items.*.itemName'      => 'required_with:items|string|max:255',
            'items.*.unitPrice'     => 'nullable|numeric|min:0|max:99999999',
            'items.*.quantity'      => 'nullable|numeric|min:0',
            'items.*.unitOfMeasure' => 'nullable|string|max:30',
            'items.*.notes'         => 'nullable|string|max:500',
            // A reminder checklist: the day's errands, each optionally
            // carrying money that only counts once the line is ticked.
            'reminders'             => 'nullable|array|max:60',
            'reminders.*.text'      => 'required_with:reminders|string|max:255',
            'reminders.*.kind'      => 'nullable|in:none,expense,income',
            'reminders.*.amount'    => 'nullable|numeric|min:0|max:99999999',
            'reminders.*.done'      => 'nullable|boolean',
        ], [
            'lotIds.required' => 'Pick at least one lot for this activity.',
            'lotIds.min'      => 'Pick at least one lot for this activity.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        // A done activity is locked — only notes (appendNote) or unchecking
        // (toggleDone) may touch it.
        if ($id !== null) {
            $locked = AsScheduleActivity::active()
                ->where('croppingScheduleId', $schedule->id)
                ->where('id', $id)
                ->value('isDone');
            if ($locked) {
                return $this->jsonFail('This activity is marked done and locked. Un-check it to edit.', 422);
            }
        }

        // Lots must belong to this schedule. Empty array is allowed (N/A).
        $validLotIds = AsScheduleLot::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $rawLotIds = collect($request->input('lotIds', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0); // strip 0/negative
        $submittedLotIds = $rawLotIds
            ->filter(fn ($v) => in_array($v, $validLotIds, true))
            ->unique()->values()->all();
        // Lot ids submitted but NONE valid → tampered payload.
        if ($rawLotIds->isNotEmpty() && empty($submittedLotIds)) {
            return $this->jsonFail('Selected lots do not belong to this schedule.', 422);
        }

        // Workers must belong to this schedule (zero allowed).
        $validWorkerIds = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $submittedWorkerIds = collect($request->input('workerIds', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => in_array($v, $validWorkerIds, true))
            ->unique()->values()->all();

        // Items are free-form now (name + price + qty + unit), so there's no
        // catalog id to cross-check — just the field validation above.

        // Normalize incoming reference image path(s) + path-traversal guard.
        // Accept the multi-image `imagePaths` list, falling back to the legacy
        // single `imagePath`. Every path must live under schedule-activities/.
        $rawPaths = $request->filled('imagePaths')
            ? (array) $request->input('imagePaths', [])
            : [(string) $request->input('imagePath', '')];
        $submittedImagePaths = [];
        foreach ($rawPaths as $p) {
            $p = ltrim(trim((string) $p), '/\\');
            if ($p === '') continue;
            if (str_contains($p, '..') || ! str_starts_with($p, 'schedule-activities/')) {
                return $this->jsonFail('Invalid image path.', 422);
            }
            $submittedImagePaths[] = $p;
        }
        $submittedImagePaths = array_values(array_unique($submittedImagePaths));
        $submittedImagePath = $submittedImagePaths[0] ?? null;

        $activityType = $request->filled('activityType') ? $request->activityType : null;
        // Everything else in the same tank, minus the primary and the kinds
        // that are a mode of their own rather than a chemical.
        $extraTypes = collect((array) $request->input('extraTypes', []))
            ->filter(fn ($t) => is_string($t) && $t !== '' && $t !== $activityType)
            ->reject(fn ($t) => in_array($t, ['irrigation', 'service', 'worker_payroll', 'reminder_checklist'], true))
            ->unique()->values()->all();

        $payload = [
            'croppingScheduleId' => $schedule->id,
            'activityTitle'      => $request->activityTitle,
            'targetDate'         => $request->targetDate,
            'targetEndDate'      => $request->filled('targetEndDate') ? $request->targetEndDate : null,
            'priority'           => $request->priority,
            'activityType'       => $activityType,
            'extraTypes'         => $extraTypes ?: null,
            // Water task only applies to irrigation activities.
            'waterTask'          => $activityType === 'irrigation'
                ? ($request->filled('waterTask') ? $request->waterTask : 'irrigate')
                : null,
            // Service price only applies to service activities.
            'servicePrice'       => $activityType === 'service'
                ? ($request->filled('servicePrice') ? $request->servicePrice : null)
                : null,
            'isDayZero'          => $request->boolean('isDayZero'),
            'isTransplant'       => $request->boolean('isTransplant'),
            // Sanitize client rich text — it is rendered raw and shared with the admin app.
            'description'        => \App\Support\HtmlSanitizer::rich($request->description),
            'imagePath'          => $submittedImagePath,
            'imagePaths'         => $submittedImagePaths ?: null,
            'timeRequired'       => $request->timeRequired,
            'reminders'          => $activityType === 'reminder_checklist'
                ? $this->cleanReminders($request->input('reminders'))
                : null,
            'deleteStatus'       => 1,
        ];

        // New activities always land in the schedule's active version; edits
        // never move versions. A new activity may be created straight into the
        // Drafts bin (isDraft = 1) — it keeps its target date so the drafts
        // list can show the day it was planned for.
        if ($id === null) {
            $activeVersionId = $this->activeVersionIdFor($schedule->id);
            if ($activeVersionId) {
                $payload['versionId'] = $activeVersionId;
            }
            $payload['isDraft'] = $request->boolean('isDraft') ? 1 : 0;

            // Drafts sit off the board, so they do not fill a day.
            if (! $payload['isDraft']) {
                $full = $this->dateFullMessage($schedule->id, $activeVersionId, $request->targetDate);
                if ($full) {
                    return $this->jsonFail($full, 422);
                }
            }
        } else {
            // An edit only needs checking when it carries the activity to a
            // different day; staying put can never overfill anything.
            $existing = AsScheduleActivity::active()
                ->where('croppingScheduleId', $schedule->id)
                ->where('id', $id)
                ->first(['id', 'versionId', 'targetDate', 'isDraft']);
            $movingTo = $request->targetDate ? Carbon::parse($request->targetDate)->format('Y-m-d') : null;
            $wasOn = $existing?->targetDate ? Carbon::parse($existing->targetDate)->format('Y-m-d') : null;
            if ($existing && ! $existing->isDraft && $movingTo && $movingTo !== $wasOn) {
                $full = $this->dateFullMessage($schedule->id, $existing->versionId, $movingTo, (int) $existing->id);
                if ($full) {
                    return $this->jsonFail($full, 422);
                }
            }
        }

        try {
            $activity = DB::transaction(function () use ($id, $schedule, $payload, $request, $submittedLotIds, $submittedWorkerIds, $submittedImagePaths) {
                if ($id) {
                    $activity = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
                    if (!$activity) {
                        abort(404, 'Activity not found.');
                    }
                    // Clean up any prior image files that are no longer referenced.
                    $removed = array_diff($activity->imagePathList(), $submittedImagePaths);
                    foreach ($removed as $gone) {
                        try {
                            Storage::disk('public')->delete($gone);
                        } catch (\Throwable $e) {
                            // Non-fatal — orphan files can be janitor-cleaned.
                        }
                    }
                    $activity->update($payload);
                } else {
                    $activity = AsScheduleActivity::create($payload);
                }

                // Replace-all item semantics: soft-delete everything, then
                // recreate from the submitted payload.
                AsScheduleActivityItem::where('activityId', $activity->id)->update(['deleteStatus' => 0]);

                foreach ((array) $request->input('items', []) as $item) {
                    $name = trim((string) ($item['itemName'] ?? ''));
                    if ($name === '') continue;
                    AsScheduleActivityItem::create([
                        'activityId'    => $activity->id,
                        'itemType'      => 'custom',
                        'itemName'      => $name,
                        'unitPrice'     => isset($item['unitPrice']) && $item['unitPrice'] !== '' ? $item['unitPrice'] : null,
                        'quantity'      => isset($item['quantity']) && $item['quantity'] !== '' ? $item['quantity'] : null,
                        'unitOfMeasure' => isset($item['unitOfMeasure']) && $item['unitOfMeasure'] !== '' ? $item['unitOfMeasure'] : null,
                        'notes'         => $item['notes'] ?? null,
                        'deleteStatus'  => 1,
                    ]);
                }

                // Replace pivot rows with the submitted lot + worker sets.
                $activity->lots()->sync($submittedLotIds);
                $activity->workers()->sync($this->workerPivot($request, $submittedWorkerIds));

                return $activity;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to save activity: ' . $e->getMessage(), 500);
        }

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data = array_merge($data, $this->serializeWorkerPay($fresh));
        $data['imageUrl'] = $fresh->imageUrl();
        $data['images'] = $fresh->imageList();
        $data['items'] = $this->serializeItems($fresh);
        $data = array_merge($data, $this->serializeWorkerPay($fresh));

        $this->broadcastBoard($schedule, 'saved', $data, $fresh->versionId);

        return $this->jsonOk($id ? 'Activity updated.' : 'Activity added.', [
            'data' => $data,
        ]);
    }

    /**
     * Upsert the per-date note for the schedule's active version.
     * Empty content collapses to a soft delete ("Note cleared.").
     */
    public function saveDateNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'noteDate'    => 'required|date',
            'noteContent' => 'nullable|string|max:20000',
            'media'          => 'nullable|array|max:20',
            'media.*.type'   => 'required_with:media|in:image,video,drawing,map',
            'media.*.path'   => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
            'media.*.strokes' => 'nullable|array|max:4000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        $noteDate = $request->input('noteDate');
        // Notes are rich text from the WYSIWYG editor — sanitize like activity
        // descriptions. No text, no drawing and no media clears the note.
        $content = \App\Support\HtmlSanitizer::rich($request->input('noteContent'));

        $existing = AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $noteDate)
            ->first();

        // A request that says nothing about media is editing the words only —
        // the plain note sheet and "move note to date" both did, and writing
        // null over the media deleted every attachment the note carried.
        // Clearing attachments is said explicitly with media: [].
        $media = $request->has('media')
            ? $this->normalizeNoteMedia($request->input('media'))
            : (is_array($existing?->media) && $existing->media !== [] ? $existing->media : null);

        if (! $this->noteHasBody($content) && empty($media)) {
            if ($existing) {
                $existing->update(['deleteStatus' => 0]);
            }
            return $this->jsonOk('Note cleared.', ['data' => null]);
        }

        if ($existing) {
            $existing->update(['noteContent' => $content, 'media' => $media]);
            $note = $existing;
        } else {
            $note = AsScheduleDateNote::create([
                'croppingScheduleId' => $schedule->id,
                'versionId'          => $versionId,
                'noteDate'           => $noteDate,
                'noteContent'        => $content,
                'media'              => $media,
                'deleteStatus'       => 1,
            ]);
        }

        $this->broadcastBoard($schedule, 'reload', ['noteDate' => $note->noteDate->format('Y-m-d')], $versionId);

        return $this->jsonOk($existing ? 'Note updated.' : 'Note added.', [
            'data' => [
                'id'          => $note->id,
                'noteDate'    => $note->noteDate->format('Y-m-d'),
                'noteContent' => $note->noteContent,
                'media'       => $this->mediaWithUrls($note->media, $schedule->id),
            ],
        ]);
    }

    /**
     * Soft-delete the per-date note for the active version. Idempotent.
     */
    public function deleteDateNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'noteDate' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $request->input('noteDate'))
            ->update(['deleteStatus' => 0]);

        return $this->jsonOk('Note deleted.');
    }

    /**
     * Create/update/move a positioned inline note (the ones that sit between
     * a day's activity cards). Upsert by id; empty content removes it.
     */
    public function inlineNoteSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'id'       => 'nullable|integer',
            'noteDate' => 'required|date',
            'sortKey'  => 'nullable|integer',
            // Each note on a day has its own name, so a day with three of them
            // reads as three things rather than one long block.
            'title'    => 'nullable|string|max:191',
            'content'  => 'nullable|string|max:20000',
            'media'          => 'nullable|array|max:20',
            'media.*.type'   => 'required_with:media|in:image,video,drawing,map',
            'media.*.path'   => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
            'media.*.strokes' => 'nullable|array|max:4000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        $content = \App\Support\HtmlSanitizer::rich($request->input('content'));
        $media = $this->normalizeNoteMedia($request->input('media'));

        $id = (int) $request->input('id');
        $note = $id
            ? \App\Models\AsInlineNote::active()->forSchedule($schedule->id)->forVersion($versionId)->where('id', $id)->first()
            : null;

        // Nothing to keep (no text, no drawing, no media) clears/skips the note.
        if (! $this->noteHasBody($content) && empty($media)) {
            if ($note) {
                $note->update(['deleteStatus' => 0]);
                $this->broadcastBoard($schedule, 'reload', [], null);

                return $this->jsonOk('Note removed.', ['data' => null, 'removed' => true]);
            }

            return $this->jsonOk('Nothing to save.', ['data' => null]);
        }

        $payload = [
            'noteDate' => $request->input('noteDate'),
            'sortKey'  => (int) $request->input('sortKey', 0),
            'title'    => trim((string) $request->input('title')) ?: null,
            'content'  => $content,
            'media'    => $media,
        ];

        if ($note) {
            $note->update($payload);
        } else {
            $note = \App\Models\AsInlineNote::create($payload + [
                'croppingScheduleId' => $schedule->id,
                'versionId'          => $versionId,
                'deleteStatus'       => 1,
            ]);
        }

        $this->broadcastBoard($schedule, 'reload', ['noteDate' => $note->noteDate->format('Y-m-d')], $versionId);

        return $this->jsonOk($id ? 'Note updated.' : 'Note added.', [
            'data' => [
                'id'       => $note->id,
                'noteDate' => $note->noteDate->format('Y-m-d'),
                'sortKey'  => $note->sortKey,
                'title'    => $note->title,
                'content'  => $note->content,
                'media'    => $this->mediaWithUrls($note->media, $schedule->id),
            ],
        ]);
    }

    /** A note has real content if it has visible text, an image, or media. */
    private function noteHasBody(?string $content): bool
    {
        $content = (string) $content;

        return filled(trim(strip_tags($content))) || str_contains($content, '<img');
    }

    /** Keep only well-formed {type,path,poster} media rows. */
    private function normalizeNoteMedia($media): ?array
    {
        return collect(is_array($media) ? $media : [])
            ->filter(fn ($m) => in_array($m['type'] ?? '', ['image', 'video', 'drawing', 'map'], true) && filled($m['path'] ?? null))
            ->map(fn ($m) => array_filter([
                'type' => $m['type'],
                'path' => $m['path'],
                'poster' => $m['poster'] ?? null,
                // What makes a drawing reopenable rather than just a picture.
                'strokes' => ($m['type'] ?? '') === 'drawing' ? ($m['strokes'] ?? null) : null,
            ], fn ($v) => $v !== null))
            ->values()->all() ?: null;
    }

    /** Resolve each stored media item's path/poster to a public URL. */
    private function mediaWithUrls($media, ?int $scheduleId = null): array
    {
        $mapUrls = $scheduleId ? $this->mapUrlByPath($scheduleId) : [];

        return collect(is_array($media) ? $media : [])
            ->map(fn ($m) => [
                'type' => $m['type'] ?? 'image',
                'path' => $m['path'] ?? null,
                'poster' => $m['poster'] ?? null,
                'strokes' => $m['strokes'] ?? null,
                'url' => ! empty($m['path']) ? \App\Support\MediaStore::url($m['path']) : null,
                'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
                // A map chip should open THE map, not the module's front door
                // — the saved map whose filed picture this attachment is.
                'mapUrl' => $mapUrls[$m['path'] ?? ''] ?? null,
            ])
            ->filter(fn ($m) => $m['url'])
            ->values()->all();
    }

    /** @var array<int, array<string, string>> per-request cache, keyed by schedule */
    private array $mapUrlsCache = [];

    /**
     * Picture path → "open this saved map" URL. A map attachment stores only
     * the picture a save filed in the notebook; the save it came from is what
     * the Maps module can actually reopen, so the link is recovered by
     * matching that picture back to its save.
     */
    private function mapUrlByPath(int $scheduleId): array
    {
        if (isset($this->mapUrlsCache[$scheduleId])) {
            return $this->mapUrlsCache[$scheduleId];
        }

        $saves = \App\Models\ScheduleMapSave::active()
            ->where('scheduleId', $scheduleId)
            ->orderByDesc('id')
            ->get(['id', 'noteId']);
        $notes = \App\Models\AsScheduleNote::active()
            ->whereIn('id', $saves->pluck('noteId')->filter()->all())
            ->get()->keyBy('id');

        $byPath = [];
        foreach ($saves as $save) {
            $note = $save->noteId ? $notes->get($save->noteId) : null;
            foreach ((is_array($note?->media) ? $note->media : []) as $m) {
                $path = (string) ($m['path'] ?? '');
                $isMap = ($m['type'] ?? '') === 'map' || preg_match('~/map-[A-Za-z0-9]+\.png$~', $path);
                if ($path !== '' && $isMap && ! isset($byPath[$path])) {
                    $byPath[$path] = route('sm.maps', ['id' => $scheduleId, 'save' => $save->id]);
                }
            }
        }

        return $this->mapUrlsCache[$scheduleId] = $byPath;
    }

    /** Soft-delete a positioned inline note. */
    public function inlineNoteDelete(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $versionId = $this->activeVersionIdFor($schedule->id);

        \App\Models\AsInlineNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->where('id', (int) ($request->input('id') ?? $request->query('id')))
            ->update(['deleteStatus' => 0]);
        $this->broadcastBoard($schedule, 'reload', [], null);

        return $this->jsonOk('Note removed.');
    }

    /**
     * List the extra expenses logged against a single date (active version),
     * with the running total. Used to refresh the day's expense block after
     * an add/edit/delete without a full page reload.
     */
    /* ---- Day income -------------------------------------------------------
     * The mirror of the extra expenses below: money a day brought in, for the
     * services a farm sells alongside the crop. Same shape, same day menu,
     * separate table — see AsScheduleDayIncome for why. */

    /* ---- Attendance -------------------------------------------------------
     * Who was put on the day's work is the plan; who turned up is the record.
     * They are kept apart, because a worker taken off an activity next season
     * should not lose the fact that they were there on Tuesday.
     */

    /** The day's roster: every worker with work that day, and what they earn. */
    public function attendance(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        // input(), not query(): marking someone hands the request straight to
        // this method with the date merged in, and a merged value never
        // reaches the query string.
        $date = (string) $request->input('date');
        if (! $date) {
            return $this->jsonFail('Which day?', 422);
        }

        $absent = \App\Models\AsScheduleAttendance::absentOn($schedule->id, $date);
        $roster = [];

        foreach ($this->dayActivities($schedule->id, $date) as $activity) {
            foreach ($activity->workers as $worker) {
                $id = (string) $worker->id;
                $roster[$id] ??= [
                    'workerId' => (int) $worker->id,
                    'name' => $worker->workerName,
                    'present' => ! in_array($worker->id, $absent, true),
                    'pay' => 0.0,
                    'jobs' => [],
                ];
                $roster[$id]['pay'] += $activity->workerPay($worker);
                $roster[$id]['jobs'][] = [
                    'title' => (string) $activity->activityTitle,
                    'part' => $activity->dayPartFor($worker),
                    'pay' => $activity->workerPay($worker),
                ];
            }
        }

        $roster = array_values($roster);
        usort($roster, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $this->jsonOk('Roster loaded.', ['data' => [
            'date' => $date,
            'workers' => $roster,
            'planned' => round(array_sum(array_column($roster, 'pay')), 2),
            'payable' => round(array_sum(array_map(
                fn ($w) => $w['present'] ? $w['pay'] : 0,
                $roster
            )), 2),
        ]]);
    }

    /** Mark one worker present or absent for a day. */
    public function markAttendance(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $data = $request->validate([
            'date' => 'required|date',
            'workerId' => 'required|integer',
            'present' => 'required|boolean',
            'note' => 'nullable|string|max:191',
        ]);

        $worker = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $data['workerId'])
            ->first();
        if (! $worker) {
            return $this->jsonFail('That worker is not on this schedule.', 404);
        }

        \App\Models\AsScheduleAttendance::updateOrCreate(
            [
                'croppingScheduleId' => $schedule->id,
                'workerId' => $worker->id,
                'workDate' => $data['date'],
            ],
            [
                'isPresent' => (bool) $data['present'],
                'note' => $data['note'] ?? null,
                'markedByUserId' => \Illuminate\Support\Facades\Auth::id(),
            ]
        );

        // Hand back the day's new total so the board never has to work out for
        // itself what a tick was worth.
        return $this->attendance($request->merge(['date' => $data['date']]));
    }

    /** @return \Illuminate\Support\Collection<int, AsScheduleActivity> */
    private function dayActivities(int $scheduleId, string $date)
    {
        return AsScheduleActivity::active()
            ->where('croppingScheduleId', $scheduleId)
            ->where('isDraft', 0)
            ->whereDate('targetDate', $date)
            ->with('workers')
            ->get();
    }

    /* ---- Tags: things an activity points at ------------------------------
     * A drawing, a map or a note already lives somewhere; an activity that
     * needs one should point at it, not hold a second copy that drifts. The
     * tag keeps only what it takes to name it and open it.
     */

    /** Everything in this schedule an activity could be tagged with. */
    public function taggables(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $notes = \App\Models\AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->orderByDesc('id')->limit(100)->get();

        $drawings = [];
        foreach ($notes as $note) {
            foreach ((is_array($note->media) ? array_values($note->media) : []) as $i => $m) {
                $path = (string) ($m['path'] ?? '');
                $isDrawing = ($m['type'] ?? '') === 'drawing'
                    || preg_match('~/(board|draw)-[A-Za-z0-9]+\.png$~', $path);
                if (! $isDrawing || $path === '') {
                    continue;
                }
                $drawings[] = [
                    'ref' => $note->id . ':' . $i,
                    'label' => (string) $note->title,
                    'url' => \App\Support\MediaStore::url($path),
                ];
            }
        }

        $maps = \App\Models\ScheduleMapSave::active()
            ->where('scheduleId', $schedule->id)
            ->orderByDesc('id')->limit(50)->get()
            ->map(fn ($m) => [
                'ref' => (string) $m->id,
                'label' => (string) ($m->title ?: 'Map'),
                'url' => route('sm.maps', ['id' => $schedule->id, 'save' => $m->id]),
            ])->all();

        return $this->jsonOk('Loaded.', ['data' => [
            'drawings' => $drawings,
            'maps' => $maps,
            'notes' => $notes->map(fn ($n) => [
                'ref' => (string) $n->id,
                'label' => (string) $n->title,
                'url' => route('sm.notes', ['id' => $schedule->id, 'note' => $n->id]),
            ])->all(),
        ]]);
    }

    /** Point an activity at one of them, or take the pointer back. */
    public function tagActivity(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'activityId' => 'required|integer',
            'kind' => 'required|in:drawing,map,note',
            'ref' => 'required|string|max:64',
            'label' => 'required|string|max:120',
            'url' => 'required|string|max:600',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $activity = $this->activityFor($schedule->id, (int) $request->input('activityId'));
        if (! $activity) {
            return $this->jsonFail('Activity not found.', 404);
        }

        $tags = is_array($activity->tags) ? array_values($activity->tags) : [];
        $tag = [
            'kind' => $request->input('kind'),
            'ref' => (string) $request->input('ref'),
            'label' => (string) $request->input('label'),
            'url' => (string) $request->input('url'),
        ];
        // The same thing tagged twice is still one tag.
        foreach ($tags as $t) {
            if (($t['kind'] ?? '') === $tag['kind'] && (string) ($t['ref'] ?? '') === $tag['ref']) {
                return $this->jsonOk('Already tagged.', ['data' => ['tags' => $tags]]);
            }
        }
        if (count($tags) >= 12) {
            return $this->jsonFail('That activity already carries 12 tags.', 422);
        }
        $tags[] = $tag;
        $activity->update(['tags' => $tags]);

        return $this->jsonOk('Tagged.', ['data' => ['tags' => $tags]]);
    }

    public function untagActivity(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $activity = $this->activityFor($schedule->id, (int) $request->input('activityId'));
        if (! $activity) {
            return $this->jsonFail('Activity not found.', 404);
        }

        $tags = is_array($activity->tags) ? array_values($activity->tags) : [];
        $i = (int) $request->input('index');
        if (isset($tags[$i])) {
            unset($tags[$i]);
            $tags = array_values($tags);
            $activity->update(['tags' => $tags ?: null]);
        }

        return $this->jsonOk('Tag removed.', ['data' => ['tags' => $tags]]);
    }

    /**
     * Turn the submitted worker list into pivot rows.
     *
     * A missing entry means the usual arrangement — a whole day at the
     * worker's own rate — so nothing has to be filled in for the common case.
     *
     * @param  array<int, int>  $workerIds
     * @return array<int, array<string, mixed>>
     */
    private function workerPivot(Request $request, array $workerIds): array
    {
        $pay = (array) $request->input('workerPay', []);
        $rows = [];
        foreach ($workerIds as $id) {
            $one = (array) ($pay[$id] ?? $pay[(string) $id] ?? []);
            $amount = $one['amount'] ?? null;
            $part = $one['dayPart'] ?? null;
            $rows[$id] = [
                // Null, not 'whole': nothing said means "as long as the task",
                // which the activity already knows.
                'dayPart' => in_array($part, ['half', 'whole'], true) ? $part : null,
                'salaryAmount' => ($amount === null || $amount === '') ? null : round((float) $amount, 2),
            ];
        }

        return $rows;
    }

    /** Store shape for the checklist: text, kind, amount, done — nothing else. */
    private function cleanReminders($rows): ?array
    {
        $out = collect(is_array($rows) ? $rows : [])
            ->map(function ($r) {
                $text = trim((string) ($r['text'] ?? ''));
                if ($text === '') {
                    return null;
                }
                $kind = in_array($r['kind'] ?? '', ['expense', 'income'], true) ? $r['kind'] : 'none';

                return [
                    'text' => mb_substr($text, 0, 255),
                    'kind' => $kind,
                    'amount' => $kind === 'none' ? 0 : round((float) ($r['amount'] ?? 0), 2),
                    'done' => (bool) ($r['done'] ?? false),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $out ?: null;
    }

    /**
     * Tick or untick one line of a reminder checklist.
     *
     * A ticked line that carries money writes an ordinary day expense or day
     * income row, and unticking removes it again. Doing it that way rather
     * than inventing a parallel total means the day header, the "why does
     * this day cost that" breakdown, the income list and every report pick it
     * up without knowing reminders exist at all.
     */
    public function reminderToggle(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if ($schedule->isLocked()) {
            return $this->jsonFail('This schedule is marked completed and locked. Reopen it in the Hub to make changes.', 423);
        }

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', (int) $request->input('activityId'))
            ->first();
        if (! $activity) {
            return $this->jsonFail('Activity not found.', 404);
        }

        $rows = (array) ($activity->reminders ?? []);
        $index = (int) $request->input('index', -1);
        if (! array_key_exists($index, $rows)) {
            return $this->jsonFail('That reminder is no longer there.', 404);
        }

        $done = $request->boolean('done');
        $rows[$index]['done'] = $done;
        $activity->update(['reminders' => $rows]);

        $row = $activity->fresh()->reminderList()[$index] ?? null;
        if ($row && $row['kind'] !== 'none' && $row['amount'] > 0) {
            $this->syncReminderMoney($activity, $index, $row, $done);
        }

        $fresh = $activity->fresh();
        $this->broadcastBoard($schedule, 'reload', ['id' => $activity->id], $activity->versionId);

        return $this->jsonOk($done ? 'Ticked.' : 'Unticked.', ['data' => [
            'id' => $fresh->id,
            'reminders' => $fresh->reminderList(),
            'expenseTotal' => $fresh->reminderTotal('expense'),
            'incomeTotal' => $fresh->reminderTotal('income'),
        ]]);
    }

    /** Put the money row where a ticked reminder says it belongs, or take it away. */
    private function syncReminderMoney(AsScheduleActivity $activity, int $index, array $row, bool $done): void
    {
        $ref = 'reminder:' . $activity->id . ':' . $index;
        $date = $activity->targetDate?->toDateString();
        if (! $date) {
            return;
        }

        $model = $row['kind'] === 'income'
            ? \App\Models\AsScheduleDayIncome::class
            : \App\Models\AsScheduleDayExpense::class;
        $dateField = $row['kind'] === 'income' ? 'incomeDate' : 'expenseDate';

        $existing = $model::where('croppingScheduleId', $activity->croppingScheduleId)
            ->where('sourceRef', $ref)
            ->first();

        if (! $done) {
            $existing?->update(['deleteStatus' => 0]);

            return;
        }

        $payload = [
            'croppingScheduleId' => $activity->croppingScheduleId,
            'versionId' => $activity->versionId,
            $dateField => $date,
            'amount' => $row['amount'],
            'note' => $row['text'],
            'sourceRef' => $ref,
            'deleteStatus' => 1,
        ];
        if ($row['kind'] === 'income') {
            $payload['title'] = $row['text'];
        }

        if ($existing) {
            $existing->update($payload);
        } else {
            $model::create($payload);
        }
    }

    /** What each worker on an activity is owed, and the bill for the day. */
    private function serializeWorkerPay(AsScheduleActivity $activity): array
    {
        return [
            'workerPay' => $activity->workers->mapWithKeys(fn ($w) => [(string) $w->id => [
                // The stored choice, which is null when this worker simply
                // follows the task's own length. Sending the resolved value
                // here made the sheet think a choice had been made, and saving
                // then wrote it down — so a task later changed to a whole day
                // kept paying half, because "half" had been baked into the
                // worker's row by an edit that never touched it.
                'dayPart' => $w->pivot->dayPart ?: null,
                'effectivePart' => $activity->dayPartFor($w),
                // Whether they actually turned up, so a payroll card can carry
                // its own ticks rather than sending you to a sheet to find out.
                'present' => ! in_array($w->id, \App\Models\AsScheduleAttendance::absentOn(
                    (int) $activity->croppingScheduleId,
                    (string) $activity->targetDate?->toDateString()
                ), true),
                'amount' => $w->pivot->salaryAmount !== null ? (float) $w->pivot->salaryAmount : null,
                'total' => $activity->workerPay($w),
                'name' => $w->workerName,
            ]])->all(),
            'labourTotal' => $activity->labourTotal(),
        ];
    }

    private function activityFor(int $scheduleId, int $id): ?\App\Models\AsScheduleActivity
    {
        return $id ? \App\Models\AsScheduleActivity::active()
            ->where('croppingScheduleId', $scheduleId)
            ->where('id', $id)
            ->first() : null;
    }

    public function listDayIncomes(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        $validator = Validator::make($request->all(), [
            'incomeDate' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $rows = \App\Models\AsScheduleDayIncome::active()
            ->forSchedule($schedule->id)
            ->forVersion($this->activeVersionIdFor($schedule->id))
            ->whereDate('incomeDate', $request->input('incomeDate'))
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->jsonOk('Income loaded.', [
            'data'  => $this->serializeIncomes($rows),
            'total' => (float) $rows->sum('amount'),
        ]);
    }

    /** Create or update one income entry; pass incomeId to edit. */
    public function saveDayIncome(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'incomeId'   => 'nullable|integer',
            'incomeDate' => 'required|date',
            'amount'     => 'required|numeric|min:0|max:99999999',
            'title'      => 'nullable|string|max:191',
            'note'       => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        $date = $request->input('incomeDate');
        $amount = round((float) $request->input('amount'), 2);
        $title = trim((string) $request->input('title', ''));
        $note = trim((string) $request->input('note', ''));

        $id = $request->input('incomeId');
        if ($id) {
            $income = \App\Models\AsScheduleDayIncome::active()
                ->forSchedule($schedule->id)
                ->where('id', (int) $id)
                ->first();
            if (!$income) {
                return $this->jsonFail('Income entry not found.', 404);
            }
            $income->update([
                'incomeDate' => $date,
                'amount' => $amount,
                'title' => $title !== '' ? $title : null,
                'note' => $note !== '' ? $note : null,
            ]);
        } else {
            $nextOrder = (int) \App\Models\AsScheduleDayIncome::active()
                ->forSchedule($schedule->id)
                ->forVersion($versionId)
                ->whereDate('incomeDate', $date)
                ->max('sortOrder');
            $income = \App\Models\AsScheduleDayIncome::create([
                'croppingScheduleId' => $schedule->id,
                'versionId' => $versionId,
                'incomeDate' => $date,
                'amount' => $amount,
                'title' => $title !== '' ? $title : null,
                'note' => $note !== '' ? $note : null,
                'sortOrder' => $nextOrder + 1,
                'deleteStatus' => 1,
            ]);
        }

        $rows = \App\Models\AsScheduleDayIncome::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('incomeDate', $date)
            ->orderBy('sortOrder')->orderBy('id')
            ->get();
        $this->broadcastBoard($schedule, 'reload', ['incomeDate' => $date], $versionId);

        return $this->jsonOk('Income saved.', [
            'data' => $this->serializeIncomes($rows),
            'total' => (float) $rows->sum('amount'),
            'saved' => (int) $income->id,
        ]);
    }

    public function deleteDayIncome(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $income = \App\Models\AsScheduleDayIncome::active()
            ->forSchedule($schedule->id)
            ->where('id', (int) $request->input('incomeId'))
            ->first();
        if (!$income) {
            return $this->jsonFail('Income entry not found.', 404);
        }

        $date = $income->incomeDate?->format('Y-m-d');
        $income->update(['deleteStatus' => 0]);

        $rows = \App\Models\AsScheduleDayIncome::active()
            ->forSchedule($schedule->id)
            ->forVersion($this->activeVersionIdFor($schedule->id))
            ->whereDate('incomeDate', $date)
            ->orderBy('sortOrder')->orderBy('id')
            ->get();
        $this->broadcastBoard($schedule, 'reload', ['incomeDate' => $date], $this->activeVersionIdFor($schedule->id));

        return $this->jsonOk('Income removed.', [
            'data' => $this->serializeIncomes($rows),
            'total' => (float) $rows->sum('amount'),
        ]);
    }

    private function serializeIncomes($rows): array
    {
        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'amount' => (float) $r->amount,
            'title' => $r->title,
            'note' => $r->note,
            'date' => $r->incomeDate?->format('Y-m-d'),
        ])->all();
    }

    public function listDayExpenses(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        $validator = Validator::make($request->all(), [
            'expenseDate' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        $rows = AsScheduleDayExpense::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('expenseDate', $request->input('expenseDate'))
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->jsonOk('Expenses loaded.', [
            'data'  => $this->serializeExpenses($rows),
            'total' => (float) $rows->sum('amount'),
        ]);
    }

    /**
     * Create or update one extra expense (amount + note) for a date. Pass an
     * `expenseId` to edit an existing row, omit it to add a new one.
     */
    public function saveDayExpense(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'expenseId'   => 'nullable|integer',
            'expenseDate' => 'required|date',
            'amount'      => 'required|numeric|min:0|max:99999999',
            'note'        => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        $expenseDate = $request->input('expenseDate');
        $amount = round((float) $request->input('amount'), 2);
        $note   = trim((string) $request->input('note', ''));

        $expenseId = $request->input('expenseId');
        if ($expenseId) {
            $expense = AsScheduleDayExpense::active()
                ->forSchedule($schedule->id)
                ->where('id', (int) $expenseId)
                ->first();
            if (!$expense) {
                return $this->jsonFail('Expense not found.', 404);
            }
            $expense->update([
                'expenseDate' => $expenseDate,
                'amount'      => $amount,
                'note'        => $note !== '' ? $note : null,
            ]);
        } else {
            $nextOrder = (int) AsScheduleDayExpense::active()
                ->forSchedule($schedule->id)
                ->forVersion($versionId)
                ->whereDate('expenseDate', $expenseDate)
                ->max('sortOrder');
            $expense = AsScheduleDayExpense::create([
                'croppingScheduleId' => $schedule->id,
                'versionId'          => $versionId,
                'expenseDate'        => $expenseDate,
                'amount'             => $amount,
                'note'               => $note !== '' ? $note : null,
                'sortOrder'          => $nextOrder + 1,
                'deleteStatus'       => 1,
            ]);
        }

        // Return the whole day's list + total so the UI can re-render cleanly.
        $rows = AsScheduleDayExpense::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('expenseDate', $expenseDate)
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->jsonOk($expenseId ? 'Expense updated.' : 'Expense added.', [
            'data'  => $this->serializeExpenses($rows),
            'total' => (float) $rows->sum('amount'),
        ]);
    }

    /**
     * Soft-delete one extra expense and return the remaining day list + total.
     */
    public function deleteDayExpense(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $expenseId = (int) $request->input('expenseId');
        $expense = AsScheduleDayExpense::active()
            ->forSchedule($schedule->id)
            ->where('id', $expenseId)
            ->first();
        if (!$expense) {
            return $this->jsonFail('Expense not found.', 404);
        }

        $expenseDate = $expense->expenseDate->format('Y-m-d');
        $versionId = $expense->versionId;
        $expense->update(['deleteStatus' => 0]);

        $rows = AsScheduleDayExpense::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('expenseDate', $expenseDate)
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->jsonOk('Expense deleted.', [
            'data'  => $this->serializeExpenses($rows),
            'total' => (float) $rows->sum('amount'),
        ]);
    }

    private function serializeExpenses($rows): array
    {
        return $rows->map(fn ($e) => [
            'id'          => $e->id,
            'expenseDate' => $e->expenseDate->format('Y-m-d'),
            'amount'      => (float) $e->amount,
            'note'        => $e->note,
        ])->values()->all();
    }

    /**
     * Resolve the active version id for a schedule, falling back to whichever
     * version exists (preferring Original) if no row is flagged isActive.
     */
    /**
     * A day's ceiling. Less a business rule than a floor under the board: the
     * day header, its counts and the per-day layouts are built for a number of
     * activities a person can actually read, and a runaway import or a stuck
     * duplicate button should hit a wall rather than produce a day nobody can
     * use. Two digits is also what the header reserves room for.
     */
    private const MAX_ACTIVITIES_PER_DATE = 99;

    /**
     * How many activities that day already carries, counted the way the board
     * counts them: same schedule, same version, not a draft. `$ignoreId` skips
     * the row being moved, so re-saving an activity onto its own date is never
     * blocked by itself.
     */
    private function activitiesOnDate(int $scheduleId, ?int $versionId, ?string $date, ?int $ignoreId = null): int
    {
        if (! $date) {
            return 0;
        }

        $query = AsScheduleActivity::active()
            ->where('croppingScheduleId', $scheduleId)
            ->whereDate('targetDate', $date)
            ->where(function ($w) {
                $w->where('isDraft', 0)->orWhereNull('isDraft');
            });

        if ($versionId) {
            $query->where('versionId', $versionId);
        }
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return (int) $query->count();
    }

    /** The message a full day gets, or null when there is still room. */
    private function dateFullMessage(int $scheduleId, ?int $versionId, ?string $date, ?int $ignoreId = null): ?string
    {
        if (! $date || $this->activitiesOnDate($scheduleId, $versionId, $date, $ignoreId) < self::MAX_ACTIVITIES_PER_DATE) {
            return null;
        }

        return 'That day already has ' . self::MAX_ACTIVITIES_PER_DATE
            . ' activities — the most one day can hold. Move some to another day first.';
    }

    private function activeVersionIdFor(int $scheduleId): ?int
    {
        $active = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->where('isActive', 1)
            ->orderBy('id', 'asc')
            ->first();
        if ($active) return (int) $active->id;

        $fallback = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->orderBy('isOriginal', 'desc')
            ->orderBy('id', 'asc')
            ->first();
        if ($fallback) return (int) $fallback->id;

        // Self-heal: schedules must always carry an Original version, otherwise
        // the version-scoped relations (activities/drafts/notes/markers) can
        // never see their rows. Create it on first use.
        $original = AsScheduleActivityVersion::create([
            'croppingScheduleId' => $scheduleId,
            'versionName' => 'Original',
            'isOriginal' => 1,
            'isActive' => 1,
            'versionOrder' => 0,
            'deleteStatus' => 1,
        ]);

        return (int) $original->id;
    }
}
