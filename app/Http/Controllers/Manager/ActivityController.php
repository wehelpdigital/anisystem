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

        return view('sm.activities', [
            'schedule'          => $schedule,
            'draftsCount'       => $draftsCount,
            'dateNotesByDate'   => $dateNotesByDate,
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

        return $this->jsonOk('Activity loaded.', ['data' => $payload]);
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
            $paragraph .= '<img src="' . e(Storage::disk('public')->url($p)) . '" alt="Note photo">';
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
            Storage::disk('public')->putFileAs($relativeDir, $file, $stem . '.' . $ext);
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
            ->get(['id', 'isDone']);
        $validSet = $rows->pluck('id')->flip()->all();
        $doneSet = $rows->where('isDone', true)->pluck('id')->flip()->all();

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
            'workerIds.*'     => 'integer',
            'items'                 => 'nullable|array',
            'items.*.itemName'      => 'required_with:items|string|max:255',
            'items.*.unitPrice'     => 'nullable|numeric|min:0|max:99999999',
            'items.*.quantity'      => 'nullable|numeric|min:0',
            'items.*.unitOfMeasure' => 'nullable|string|max:30',
            'items.*.notes'         => 'nullable|string|max:500',
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

        $payload = [
            'croppingScheduleId' => $schedule->id,
            'activityTitle'      => $request->activityTitle,
            'targetDate'         => $request->targetDate,
            'targetEndDate'      => $request->filled('targetEndDate') ? $request->targetEndDate : null,
            'priority'           => $request->priority,
            'activityType'       => $activityType,
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
                $activity->workers()->sync($submittedWorkerIds);

                return $activity;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to save activity: ' . $e->getMessage(), 500);
        }

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data['imageUrl'] = $fresh->imageUrl();
        $data['images'] = $fresh->imageList();
        $data['items'] = $this->serializeItems($fresh);

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
            'media.*.type'   => 'required_with:media|in:image,video',
            'media.*.path'   => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
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
        $media = $this->normalizeNoteMedia($request->input('media'));

        $existing = AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $noteDate)
            ->first();

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
                'media'       => $this->mediaWithUrls($note->media),
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
            'content'  => 'nullable|string|max:20000',
            'media'          => 'nullable|array|max:20',
            'media.*.type'   => 'required_with:media|in:image,video',
            'media.*.path'   => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
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
                'content'  => $note->content,
                'media'    => $this->mediaWithUrls($note->media),
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
            ->filter(fn ($m) => in_array($m['type'] ?? '', ['image', 'video'], true) && filled($m['path'] ?? null))
            ->map(fn ($m) => array_filter([
                'type' => $m['type'],
                'path' => $m['path'],
                'poster' => $m['poster'] ?? null,
            ], fn ($v) => $v !== null))
            ->values()->all() ?: null;
    }

    /** Resolve each stored media item's path/poster to a public URL. */
    private function mediaWithUrls($media): array
    {
        return collect(is_array($media) ? $media : [])
            ->map(fn ($m) => [
                'type' => $m['type'] ?? 'image',
                'path' => $m['path'] ?? null,
                'poster' => $m['poster'] ?? null,
                'url' => ! empty($m['path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($m['path']) : null,
                'posterUrl' => ! empty($m['poster']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($m['poster']) : null,
            ])
            ->filter(fn ($m) => $m['url'])
            ->values()->all();
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
