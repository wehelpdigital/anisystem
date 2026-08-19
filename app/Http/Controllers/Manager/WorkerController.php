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
     * The roster, and the logins that hang off it, belong to the farm owner.
     *
     * scheduleFromRequest()'s assertCanEdit() is not this question: it stops a
     * view-only worker and waves an editing one through, and an editing worker
     * is still one of the names on this list. Every door to the module is gone
     * from the UI, so anything arriving here is arriving by URL.
     */
    private function refuseWorker(): ?\Illuminate\Http\JsonResponse
    {
        if (! \App\Support\WorkerContext::inWorkerContext()) {
            return null;
        }

        return $this->jsonFail('Only the farm owner can manage workers.', 403);
    }

    /**
     * Module page: GET /app/sm-workers?id={scheduleId}
     */
    public function page(Request $request)
    {
        // A page, not an endpoint, so the answer is a page too. It used to be
        // a 404, which told a farmer the module was missing when it is simply
        // not theirs — the owner asked for the plain version instead.
        if ($no = $this->workerNoAccess('the Workers module')) {
            return $no;
        }

        $schedule = $this->schedule($request->query('id'));
        $schedule->load(['workers.offDates', 'workers.offDays']);

        // Login grants for this boss, mapped to each worker card so it can show
        // its login state (none / pending invite / active). Only the boss (the
        // tier that can create logins) sees these controls.
        $grantByWorker = [];
        if ($request->user()->canWorkerAccounts()) {
            $grants = \App\Models\WorkerGrant::active()
                ->where('bossUserId', $schedule->anisystemUserId)
                ->get();
            foreach ($schedule->workers as $w) {
                $g = $grants->firstWhere('scheduleWorkerId', $w->id);
                if (! $g && $w->email) {
                    $g = $grants->first(fn ($x) => mb_strtolower((string) $x->invitedEmail) === mb_strtolower((string) $w->email));
                }
                if ($g) {
                    $grantByWorker[$w->id] = [
                        'id' => $g->id,
                        'status' => $g->status,
                        'scheduleAccess' => $g->scheduleAccess,
                        'communityAccess' => (bool) $g->communityAccess,
                        'workerUserId' => $g->workerUserId ? (int) $g->workerUserId : null,
                    ];
                }
            }
        }

        return view('sm.workers', compact('schedule', 'grantByWorker'));
    }

    public function store(Request $request)
    {
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }

        $schedule = $this->scheduleFromRequest($request);

        $allowedSkillKeys = array_keys(AsScheduleWorker::SKILLS);
        $validator = Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:32',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'skills' => 'nullable|array',
            'skills.*' => ['string', Rule::in($allowedSkillKeys)],
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        // Priority is no longer edited; append new workers to the end so the
        // existing list order is preserved.
        $nextPriority = (int) AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->max('priority') + 1;

        $worker = AsScheduleWorker::create([
            'croppingScheduleId' => $schedule->id,
            'workerName' => $request->workerName,
            'email' => $request->filled('email') ? $request->email : null,
            'phone' => $request->filled('phone') ? trim($request->phone) : null,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
            'priority' => $nextPriority,
            'skills' => $this->normalizeSkills($request->input('skills', []), $allowedSkillKeys),
            'notes' => $request->notes,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Worker added.', ['data' => $worker]);
    }

    public function update(Request $request)
    {
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }

        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $allowedSkillKeys = array_keys(AsScheduleWorker::SKILLS);
        $validator = Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:32',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'skills' => 'nullable|array',
            'skills.*' => ['string', Rule::in($allowedSkillKeys)],
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $worker->update([
            'workerName' => $request->workerName,
            'email' => $request->filled('email') ? $request->email : null,
            'phone' => $request->filled('phone') ? trim($request->phone) : null,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
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
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }

        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $worker->update(['deleteStatus' => 0]);

        return $this->jsonOk('Worker deleted.');
    }

    public function rules(Request $request)
    {
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }

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
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }

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
