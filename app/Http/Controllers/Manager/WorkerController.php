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
 * mother app, plus page() rendering the anee.io mobile-first module page.
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
                    // Everything the sheet can set, so it opens showing what
                    // is true. It used to carry four of these fields, and the
                    // note permission was not one of them: the box was always
                    // drawn unticked, and saving the sheet then took the right
                    // away from a worker who had it.
                    $grantByWorker[$w->id] = \App\Support\WorkerGrantState::of($g);
                }
            }
        }

        return view('sm.workers', compact('schedule', 'grantByWorker'));
    }

    /**
     * What this farm already knows about an email.
     *
     * One person can work for several owners and on several of one owner's
     * seasons, so the same address can be a worker card here, a card on
     * another season, an anee.io account, or all three -- and a card typed
     * fresh each time is how one person ends up as three slightly different
     * people. Both doors of the Add Worker sheet ask here as the email is
     * typed, and the answer says everything at once:
     *
     *   self       the owner's own address
     *   onRoster   a card on THIS season already carries it (the card)
     *   elsewhere  a card on another of this owner's seasons carries it,
     *              with the facts on it, so they can be reused rather than
     *              retyped
     *   found      an anee.io account signs in with it (the account), plus
     *              any login grant this owner already gave it
     *
     * Exact email only, and never a search -- an address is something the
     * owner already has, not something the app helps them guess; the one
     * fact given away is that an account exists, which the grant flow has
     * always said in its own reply. `exclude` is the card being edited, so
     * a worker's own email is not reported as a duplicate of itself.
     */
    public function account(Request $request)
    {
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }
        $schedule = $this->scheduleFromRequest($request);

        $email = mb_strtolower(trim((string) $request->query('email')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonFail('Enter a full email address.', 422);
        }
        $exclude = (int) $request->query('exclude', 0);
        $facts = $this->emailFacts($schedule, $email, $exclude, $request->user());

        return $this->jsonOk($facts['found'] ? 'Account found.' : 'No account with that email.', ['data' => $facts]);
    }

    /** The answer account() gives, as an array; store() and update() ask it too. */
    private function emailFacts(\App\Models\AsCroppingSchedule $schedule, string $email, int $exclude, \App\Models\User $me): array
    {
        $ownerId = (int) $schedule->anisystemUserId;
        $self = $email === mb_strtolower((string) $me->email);

        // A card on this season already carrying the address.
        $onRoster = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->when($exclude > 0, fn ($q) => $q->where('id', '!=', $exclude))
            ->first();

        // The same person on another of this owner's seasons: the freshest
        // card, with what was written on it.
        $elsewhere = AsScheduleWorker::active()
            ->join('as_cropping_schedules as s', 's.id', '=', 'as_schedule_workers.croppingScheduleId')
            ->where('s.anisystemUserId', $ownerId)
            ->where('s.deleteStatus', 1)
            ->where('s.id', '!=', $schedule->id)
            ->whereRaw('LOWER(as_schedule_workers.email) = ?', [$email])
            ->orderByDesc('as_schedule_workers.updated_at')
            ->select('as_schedule_workers.*', 's.title as scheduleTitle')
            ->first();

        $user = $self ? null : \App\Models\User::active()->whereRaw('LOWER(email) = ?', [$email])->first();
        $grant = $user ? \App\Models\WorkerGrant::active()
            ->where('bossUserId', $ownerId)
            ->where(fn ($q) => $q->where('workerUserId', $user->id)->orWhereRaw('LOWER(invitedEmail) = ?', [$email]))
            ->first() : null;

        return [
            'self' => $self,
            'found' => (bool) $user,
            'account' => $user ? [
                'id' => (int) $user->id,
                'name' => $user->full_name ?: $user->email,
                'email' => (string) $user->email,
                'phone' => $user->phone ? (string) $user->phone : null,
                'initials' => $user->initials ?: '·',
                'avatar' => $user->avatarPath ? \App\Support\MediaStore::url($user->avatarPath) : null,
                'since' => $user->created_at?->format('M Y'),
            ] : null,
            'onRoster' => $onRoster ? (int) $onRoster->id : null,
            'onRosterName' => $onRoster?->workerName,
            'elsewhere' => $elsewhere ? [
                'workerId' => (int) $elsewhere->id,
                'scheduleId' => (int) $elsewhere->croppingScheduleId,
                'scheduleTitle' => (string) ($elsewhere->scheduleTitle ?: 'another season'),
                'name' => (string) $elsewhere->workerName,
                'phone' => $elsewhere->phone,
                'costPerHalfDay' => (float) $elsewhere->costPerHalfDay,
                'skills' => array_values((array) ($elsewhere->skills ?? [])),
            ] : null,
            'login' => $grant ? (string) $grant->status : null,
            // The whole grant, when there is one: the new card wears it as
            // it is, with the rights the owner already chose.
            'grant' => $grant ? \App\Support\WorkerGrantState::of($grant) + ['scheduleWorkerId' => $grant->scheduleWorkerId ? (int) $grant->scheduleWorkerId : null] : null,
        ];
    }

    /**
     * One card per address per season. Two cards with one email are one
     * person counted twice -- on the day's labour, on the roster, and in
     * the login the page matches to a card by that email.
     */
    private function refuseDuplicateEmail(\App\Models\AsCroppingSchedule $schedule, ?string $email, int $exclude = 0): ?\Illuminate\Http\JsonResponse
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '') {
            return null;
        }
        $twin = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->when($exclude > 0, fn ($q) => $q->where('id', '!=', $exclude))
            ->first();
        if (! $twin) {
            return null;
        }

        return $this->jsonFail(($twin->workerName ?: 'Somebody') . ' is already on this schedule with that email — open their card instead.', 422, ['data' => ['twinId' => (int) $twin->id]]);
    }

    public function store(Request $request)
    {
        if ($refusal = $this->refuseWorker()) {
            return $refusal;
        }

        $schedule = $this->scheduleFromRequest($request);

        /* A worker picked from their anee.io account (the sheet's second
         * tab): the card takes the account's own name, email and phone, so
         * the two never disagree about who this is. The account has to be
         * real, somebody else's, and not on this roster already. */
        if ($request->filled('accountUserId')) {
            $account = \App\Models\User::active()->find((int) $request->input('accountUserId'));
            if (! $account || (int) $account->id === (int) $request->user()->id) {
                return $this->jsonFail('That account could not be used.', 422);
            }
            if ($twin = $this->refuseDuplicateEmail($schedule, $account->email)) {
                return $twin;
            }
            $request->merge([
                'workerName' => $account->full_name ?: $account->email,
                'email' => $account->email,
                'phone' => $request->filled('phone') ? $request->input('phone') : ($account->phone ?: null),
            ]);
        }

        // The tier's worker cap, judged by the schedule owner's plan.
        $wCap = \App\Support\Tier::scheduleLimit($schedule, 'workersPerSchedule');
        if ($wCap !== null && AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->count() >= $wCap) {
            \App\Support\Tier::deny('This plan allows up to ' . $wCap . ' workers per schedule. Upgrade to add more.');
        }

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
        if ($twin = $this->refuseDuplicateEmail($schedule, $request->input('email'))) {
            return $twin;
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

        if ($request->has('tags')) {
            \App\Support\ScheduleTags::sync($schedule, 'worker', (int) $worker->id, $request->input('tags', []));
        }

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
        if ($twin = $this->refuseDuplicateEmail($schedule, $request->input('email'), (int) $worker->id)) {
            return $twin;
        }

        $worker->update([
            'workerName' => $request->workerName,
            'email' => $request->filled('email') ? $request->email : null,
            'phone' => $request->filled('phone') ? trim($request->phone) : null,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
            'skills' => $this->normalizeSkills($request->input('skills', []), $allowedSkillKeys),
            'notes' => $request->notes,
        ]);

        if ($request->has('tags')) {
            \App\Support\ScheduleTags::sync($schedule, 'worker', (int) $worker->id, $request->input('tags', []));
        }

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
