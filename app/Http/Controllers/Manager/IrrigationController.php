<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleIrrigation;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IrrigationController extends BaseScheduleController
{
    /**
     * Irrigation module page. Takes ?id={scheduleId} like every module
     * page. Loads the schedule's irrigation entries (manual sortOrder,
     * then startDay, then id — enforced by the relation) with both pivot
     * sets, plus the lots and workers catalogs for the form chips.
     */
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load(['irrigations.workers', 'irrigations.lots', 'lots', 'workers']);

        // Lean JSON payload for the client-side renderer: row fields plus
        // workerIds/lotIds arrays (the same shape the save endpoints return).
        $irrigations = $schedule->irrigations->map(function ($irr) {
            $arr = $irr->toArray();
            $arr['workerIds'] = $irr->workers->pluck('id')->values();
            $arr['lotIds'] = $irr->lots->pluck('id')->values();
            unset($arr['workers'], $arr['lots']);
            return $arr;
        })->values();

        return view('sm.irrigations', [
            'schedule' => $schedule,
            'irrigations' => $irrigations,
            'lots' => $schedule->lots,
            'workers' => $schedule->workers,
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request)
    {
        return $this->save($request, $this->queryId($request));
    }

    /**
     * Batch-update sortOrder for many irrigation rows at once. Body:
     *   items: [{ id, sortOrder }, ...]
     * Only rows that already belong to the requesting schedule get
     * updated — the validSet guard protects against tampered ids being
     * smuggled in alongside legitimate ones.
     */
    public function reorder(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|integer',
            'items.*.sortOrder'  => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $items = (array) $request->input('items');
        $ids   = array_map(fn ($it) => (int) $it['id'], $items);

        $validIds = AsScheduleIrrigation::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
        $validSet = array_flip($validIds);

        try {
            DB::transaction(function () use ($items, $validSet) {
                foreach ($items as $it) {
                    $id = (int) $it['id'];
                    if (!isset($validSet[$id])) continue;
                    AsScheduleIrrigation::where('id', $id)
                        ->update(['sortOrder' => (int) $it['sortOrder']]);
                }
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to reorder: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Order saved.', ['count' => count($items)]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $irrigation = AsScheduleIrrigation::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$irrigation) return $this->jsonFail('Irrigation entry not found.', 404);

        $irrigation->update(['deleteStatus' => 0]);
        return $this->jsonOk('Irrigation entry deleted.');
    }

    /**
     * Duplicate an irrigation entry: copy all fields (title, description,
     * day range OR date range based on dayMode, task type, worker, etc.)
     * into a new row with " (copy)" suffixed on the title. Returns the
     * fresh row with assignedWorker loaded so the UI can render it without
     * a follow-up fetch.
     */
    public function duplicate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $source = AsScheduleIrrigation::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['workers', 'lots'])
            ->first();
        if (!$source) return $this->jsonFail('Irrigation entry not found.', 404);

        try {
            $copy = DB::transaction(function () use ($source) {
                $copy = $source->replicate();
                $copy->irrigationTitle = mb_substr($source->irrigationTitle, 0, 240) . ' (copy)';
                $copy->save();
                // Clone both pivot sets so the copy carries the same workers
                // and lots as the source — otherwise duplicate would silently
                // drop the multi-assignment data.
                $copy->workers()->sync($source->workers->pluck('id')->all());
                $copy->lots()->sync($source->lots->pluck('id')->all());
                return $copy;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to duplicate irrigation: ' . $e->getMessage(), 500);
        }

        $fresh = $copy->fresh(['assignedWorker', 'workers', 'lots']);
        $data = $fresh->toArray();
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data['lotIds']    = $fresh->lots->pluck('id');

        return $this->jsonOk('Irrigation duplicated.', ['data' => $data]);
    }

    private function save(Request $request, $id = null)
    {
        $schedule = $this->scheduleFromRequest($request);

        // The form supports two range modes: relative DAS day-numbers
        // (existing behavior) and absolute calendar dates. Only the active
        // mode's fields are required — the other mode's inputs are accepted
        // but optional so the UI can hold values from a previously-edited
        // entry without forcing the user to clear them.
        $dayMode = $request->input('dayMode') === 'date' ? 'date' : 'das';

        $validator = Validator::make($request->all(), [
            'irrigationTitle'   => 'required|string|max:255',
            'description'       => 'nullable|string|max:2000',
            'dayMode'           => 'nullable|in:das,date',
            'startDay'          => $dayMode === 'das' ? 'required|integer' : 'nullable|integer',
            'endDay'            => $dayMode === 'das' ? 'required|integer|gte:startDay' : 'nullable|integer',
            'startDate'         => $dayMode === 'date' ? 'required|date' : 'nullable|date',
            'endDate'           => $dayMode === 'date' ? 'required|date|after_or_equal:startDate' : 'nullable|date',
            'taskType'          => ['nullable', 'string', Rule::in(array_keys(AsScheduleIrrigation::TASK_TYPES))],
            'priority'          => 'nullable|integer|min:1|max:5',
            'assignedWorkerId'  => 'nullable|integer',
            'workerIds'         => 'nullable|array',
            'workerIds.*'       => 'integer',
            'lotIds'            => 'nullable|array',
            'lotIds.*'          => 'integer',
            'timeRequired'      => 'nullable|in:half,whole',
        ], [
            'startDate.required' => 'Pick a start date for the date range.',
            'endDate.required'   => 'Pick an end date for the date range.',
            'endDate.after_or_equal' => 'End date must be on or after the start date.',
            'startDay.required'  => 'Enter a start day for the DAS range.',
            'endDay.required'    => 'Enter an end day for the DAS range.',
            'endDay.gte'         => 'End day must be greater than or equal to start day.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        if ($request->filled('assignedWorkerId')) {
            $ok = AsScheduleWorker::active()
                ->where('croppingScheduleId', $schedule->id)
                ->where('id', $request->assignedWorkerId)
                ->exists();
            if (!$ok) return $this->jsonFail('Selected worker does not belong to this schedule.', 422);
        }

        // Validate every picked worker / lot id actually belongs to this
        // schedule before syncing — protects against client tampering or
        // stale IDs left over from a switched schedule.
        $validWorkerIds = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $submittedWorkerIds = collect((array) $request->input('workerIds', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => in_array($v, $validWorkerIds, true))
            ->unique()->values()->all();

        $validLotIds = AsScheduleLot::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $submittedLotIds = collect((array) $request->input('lotIds', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => in_array($v, $validLotIds, true))
            ->unique()->values()->all();

        // Persist both modes' values when present, but normalize the inactive
        // mode's columns to safe defaults so display logic can trust dayMode
        // as the single source of truth. Keep `assignedWorkerId` mirrored to
        // the first picked worker so legacy consumers (calendar generator,
        // older reports) don't break — the new workers() pivot is the
        // canonical source for the multi-worker case.
        $legacyAssignedId = !empty($submittedWorkerIds) ? $submittedWorkerIds[0] : null;

        $payload = [
            'croppingScheduleId' => $schedule->id,
            'irrigationTitle'    => $request->irrigationTitle,
            'description'        => $request->description,
            'dayMode'            => $dayMode,
            'startDay'           => $dayMode === 'das' ? (int) $request->input('startDay', 0) : 0,
            'endDay'             => $dayMode === 'das' ? (int) $request->input('endDay', 0)   : 0,
            'startDate'          => $dayMode === 'date' ? $request->input('startDate') : null,
            'endDate'            => $dayMode === 'date' ? $request->input('endDate')   : null,
            'taskType'           => $request->input('taskType') ?: 'irrigate',
            'priority'           => (int) ($request->input('priority') ?: 5),
            'assignedWorkerId'   => $legacyAssignedId,
            // Field removed from the irrigation form — kept on the column so the
            // calendar generator (which feeds it into event.timeOfDay) keeps
            // working. Default to 'half' for any newly-created entry.
            'timeRequired'       => $request->input('timeRequired') ?: 'half',
            'deleteStatus'       => 1,
        ];

        try {
            $irrigation = DB::transaction(function () use ($id, $schedule, $payload, $submittedWorkerIds, $submittedLotIds) {
                if ($id) {
                    $irrigation = AsScheduleIrrigation::active()
                        ->where('croppingScheduleId', $schedule->id)
                        ->where('id', $id)
                        ->first();
                    if (!$irrigation) abort(404, 'Irrigation entry not found.');
                    $irrigation->update($payload);
                } else {
                    $irrigation = AsScheduleIrrigation::create($payload);
                }

                // Replace the pivot sets with the submitted IDs. sync() does
                // both insert + delete in one shot so the pivots always end
                // up matching the form state.
                $irrigation->workers()->sync($submittedWorkerIds);
                $irrigation->lots()->sync($submittedLotIds);

                return $irrigation;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to save irrigation: ' . $e->getMessage(), 500);
        }

        $fresh = $irrigation->fresh(['assignedWorker', 'workers', 'lots']);
        $data = $fresh->toArray();
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data['lotIds']    = $fresh->lots->pluck('id');

        return $this->jsonOk($id ? 'Irrigation entry updated.' : 'Irrigation entry added.', [
            'data' => $data,
        ]);
    }
}
