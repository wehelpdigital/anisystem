<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleWorker;
use App\Models\AsScheduleWorkerOffDate;
use App\Models\AsScheduleWorkerOffDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Workers — store/update/destroy/rules/saveRules ported verbatim from the
 * mother app, plus page() rendering the AniSystem mobile-first module page.
 */
class WorkerController extends BaseScheduleController
{
    /**
     * Module page: GET /app/sm-workers?id={scheduleId}
     */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $schedule->load(['workers.offDates', 'workers.offDays']);

        return view('sm.workers', compact('schedule'));
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $allowedSkillKeys = array_keys(AsScheduleWorker::SKILLS);
        $validator = Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'skills' => 'nullable|array',
            'skills.*' => ['string', Rule::in($allowedSkillKeys)],
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $worker = AsScheduleWorker::create([
            'croppingScheduleId' => $schedule->id,
            'workerName' => $request->workerName,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
            'priority' => $request->priority,
            'skills' => $this->normalizeSkills($request->input('skills', []), $allowedSkillKeys),
            'notes' => $request->notes,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Worker added.', ['data' => $worker]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $allowedSkillKeys = array_keys(AsScheduleWorker::SKILLS);
        $validator = Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'skills' => 'nullable|array',
            'skills.*' => ['string', Rule::in($allowedSkillKeys)],
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $worker->update([
            'workerName' => $request->workerName,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
            'priority' => $request->priority,
            'skills' => $this->normalizeSkills($request->input('skills', []), $allowedSkillKeys),
            'notes' => $request->notes,
        ]);

        return $this->jsonOk('Worker updated.', ['data' => $worker]);
    }

    /**
     * Filter user-submitted skill slugs down to known values, de-duped and
     * preserving the catalog order. Returns null when empty so the DB stores
     * NULL rather than an empty JSON array.
     */
    private function normalizeSkills($submitted, array $allowed): ?array
    {
        $clean = array_values(array_intersect($allowed, array_unique(array_filter((array) $submitted))));
        return empty($clean) ? null : $clean;
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $worker->update(['deleteStatus' => 0]);

        return $this->jsonOk('Worker deleted.');
    }

    public function rules(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['offDates', 'offDays'])
            ->first();

        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        return $this->jsonOk('Worker rules.', [
            'data' => [
                'worker' => $worker,
                'offDates' => $worker->offDates,
                'offDays' => $worker->offDays->pluck('dayOfWeek'),
            ],
        ]);
    }

    public function saveRules(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $validator = Validator::make($request->all(), [
            'offDates'   => 'nullable|array',
            'offDates.*' => 'nullable|date',
            'offDays'    => 'nullable|array',
            'offDays.*'  => 'integer|min:0|max:6',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        DB::transaction(function () use ($worker, $request) {
            AsScheduleWorkerOffDate::where('workerId', $worker->id)->delete();
            AsScheduleWorkerOffDay::where('workerId', $worker->id)->delete();

            foreach ((array) $request->input('offDates', []) as $d) {
                if (!$d) continue;
                AsScheduleWorkerOffDate::create(['workerId' => $worker->id, 'offDate' => $d]);
            }

            foreach (array_unique((array) $request->input('offDays', [])) as $dow) {
                AsScheduleWorkerOffDay::create(['workerId' => $worker->id, 'dayOfWeek' => (int) $dow]);
            }
        });

        return $this->jsonOk('Rules saved.');
    }
}
