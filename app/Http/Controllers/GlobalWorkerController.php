<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsGlobalWorker;
use App\Models\AsScheduleWorker;
use App\Models\AsScheduleWorkerOffDay;
use App\Models\WorkerGrant;
use App\Support\Tier;
use App\Support\WorkDays;
use App\Support\WorkerContext;
use App\Support\WorkerGrantState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Global Workers (2026-10-12): the farm's own roster, above the seasons, in
 * Global and Quick Tools. Each person once: their name, contact, usual pay,
 * skills and weekdays off; added to any season in a tap, where they become
 * that season's card (activities, pay, login). Editing someone here updates
 * who they are on every season card that is them; their pay on a season
 * stays that season's. The owner's own (a worker in someone's farm does not
 * manage the farm's roster). Workers come with Solo Farmer and up.
 */
class GlobalWorkerController extends Controller
{
    private function ownerOnly(): ?\Illuminate\Http\JsonResponse
    {
        return WorkerContext::inWorkerContext() ? response()->json(['success' => false, 'message' => 'Only the farm owner manages the workers.'], 403) : null;
    }

    public function page()
    {
        if (WorkerContext::inWorkerContext()) {
            return redirect()->route('app.dashboard')->with('error', 'Only the farm owner manages the workers.');
        }

        return view('workers.global', [
            'skills' => AsScheduleWorker::SKILLS,
            'seasons' => AsCroppingSchedule::active()->where('anisystemUserId', Auth::id())->where('deleteStatus', 1)->orderByDesc('id')->get(['id', 'title', 'cropType', 'status']),
            // Logins (2026-10-18): the same tier gate the season page asks.
            'canLogins' => Auth::user()->canWorkerAccounts(),
        ]);
    }

    /** Everyone on the roster, with the seasons each is on, and their login. */
    public function list()
    {
        if ($no = $this->ownerOnly()) {
            return $no;
        }
        $me = (int) Auth::id();
        $rows = AsGlobalWorker::where('userId', $me)->where('deleteStatus', 1)->orderBy('workerName')->get();
        $cards = AsScheduleWorker::active()->whereIn('globalWorkerId', $rows->pluck('id'))
            ->join('as_cropping_schedules as s', 's.id', '=', 'as_schedule_workers.croppingScheduleId')->where('s.deleteStatus', 1)
            ->get(['as_schedule_workers.id', 'as_schedule_workers.globalWorkerId', 's.id as seasonId', 's.title as seasonTitle'])->groupBy('globalWorkerId');
        // The logins this owner gave, found for each person the way every
        // season card finds its own (WorkerGrant::findFor), so the roster and
        // the seasons always show the same one. Only for a plan with logins,
        // as on the season page.
        $grants = Auth::user()->canWorkerAccounts() ? WorkerGrant::active()->where('bossUserId', $me)->get() : collect();

        return response()->json(['success' => true, 'data' => $rows->map(function ($g) use ($cards, $grants) {
            $mine = $cards->get($g->id, collect());
            $grant = $grants->isEmpty() ? null : WorkerGrant::findFor($grants, (int) $g->id, $mine->pluck('id')->all(), $g->email);

            return $this->row($g, $mine, $grant);
        })->values()]);
    }

    private function row(AsGlobalWorker $g, $cards, ?WorkerGrant $grant = null): array
    {
        return ['id' => (int) $g->id, 'workerName' => $g->workerName, 'email' => $g->email, 'phone' => $g->phone, 'costPerHalfDay' => (float) $g->costPerHalfDay,
            'skills' => $g->skills ?: [], 'offWeekdays' => $g->offWeekdays ?: [], 'dayParts' => WorkDays::json($g->dayParts), 'notes' => $g->notes,
            'seasons' => collect($cards)->map(fn ($c) => ['id' => (int) $c->seasonId, 'title' => $c->seasonTitle, 'cardId' => (int) $c->id])->values()->all(),
            'login' => $grant ? WorkerGrantState::withLinks($grant) : null];
    }

    private function validated(Request $request)
    {
        return Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:32',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'skills' => 'nullable|array',
            'skills.*' => ['string', Rule::in(array_keys(AsScheduleWorker::SKILLS))],
            'offWeekdays' => 'nullable|array',
            'offWeekdays.*' => 'integer|min:0|max:6',
            'dayParts' => 'nullable|array',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function facts(Request $request): array
    {
        $skills = array_values(array_intersect(array_keys(AsScheduleWorker::SKILLS), array_unique(array_filter((array) $request->input('skills', [])))));
        $off = array_values(array_unique(array_map('intval', (array) $request->input('offWeekdays', []))));
        sort($off);

        return ['workerName' => trim((string) $request->input('workerName')), 'email' => $request->filled('email') ? trim((string) $request->input('email')) : null,
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'costPerHalfDay' => is_numeric($request->input('costPerHalfDay')) ? (float) $request->input('costPerHalfDay') : 0,
            'skills' => $skills ?: null, 'offWeekdays' => $off ?: null, 'dayParts' => WorkDays::parts($request->input('dayParts', []), $off), 'notes' => $request->input('notes')];
    }

    public function store(Request $request)
    {
        if ($no = $this->ownerOnly()) {
            return $no;
        }
        if (! Tier::can('workers')) {
            Tier::denyFor('workers', 'Workers come with {plan}. Keep your crew, their work days and their pay on every activity.');
        }
        $v = $this->validated($request);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Please check what you entered.', 'errors' => $v->errors()], 422);
        }
        $f = $this->facts($request);
        $key = AsGlobalWorker::personKey($f['workerName'], $f['email'], $f['phone']);
        if ($twin = AsGlobalWorker::where('userId', Auth::id())->where('personKey', $key)->where('deleteStatus', 1)->first()) {
            return response()->json(['success' => false, 'message' => $twin->workerName . ' is already on your roster.'], 422);
        }
        $g = AsGlobalWorker::create($f + ['userId' => Auth::id(), 'personKey' => $key, 'deleteStatus' => 1]);

        return response()->json(['success' => true, 'message' => 'Worker added.', 'data' => $this->row($g, [])]);
    }

    public function update(Request $request, int $id)
    {
        if ($no = $this->ownerOnly()) {
            return $no;
        }
        $g = AsGlobalWorker::where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->firstOrFail();
        $v = $this->validated($request);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Please check what you entered.', 'errors' => $v->errors()], 422);
        }
        $f = $this->facts($request);
        if (! $request->has('dayParts')) {
            $f['dayParts'] = WorkDays::parts($g->dayParts, (array) ($f['offWeekdays'] ?? []));   // a caller that does not know the half days keeps them
        }
        $f['personKey'] = AsGlobalWorker::personKey($f['workerName'], $f['email'], $f['phone']);
        if ($twin = AsGlobalWorker::where('userId', Auth::id())->where('personKey', $f['personKey'])->where('deleteStatus', 1)->where('id', '!=', $g->id)->first()) {
            return response()->json(['success' => false, 'message' => $twin->workerName . ' is already on your roster with that name and number or email.'], 422);
        }
        $offChanged = ($g->offWeekdays ?: []) != ($f['offWeekdays'] ?: []) || (WorkDays::parts($g->dayParts) ?? []) != ($f['dayParts'] ?? []);
        DB::transaction(function () use ($g, $f, $offChanged) {
            $g->update($f);
            // Who they are, on every season card that is them (pay stays each season's own).
            // A closed season is a record: its cards stay as they were.
            $cardIds = AsScheduleWorker::active()->where('globalWorkerId', $g->id)
                ->whereIn('croppingScheduleId', AsCroppingSchedule::active()->where('anisystemUserId', Auth::id())
                    ->whereNotIn('status', [AsCroppingSchedule::STATUS_COMPLETED, AsCroppingSchedule::STATUS_ARCHIVED])->pluck('id'))
                ->pluck('id');
            AsScheduleWorker::whereIn('id', $cardIds)->update(['workerName' => $f['workerName'], 'email' => $f['email'], 'phone' => $f['phone'],
                'skills' => $f['skills'] ? json_encode($f['skills']) : null, 'updated_at' => now()]);
            // Their usual week too, the days off and the half days, when it changed here (the season sheets write it back the same way).
            if ($offChanged) {
                AsScheduleWorker::whereIn('id', $cardIds)->update(['dayParts' => $f['dayParts'] ? json_encode($f['dayParts']) : null]);
                AsScheduleWorkerOffDay::whereIn('workerId', $cardIds)->delete();
                foreach ($cardIds as $cid) {
                    foreach ((array) ($f['offWeekdays'] ?? []) as $dow) {
                        AsScheduleWorkerOffDay::create(['workerId' => $cid, 'dayOfWeek' => (int) $dow]);
                    }
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Worker saved. Their season cards say the same.', 'data' => $this->row($g->fresh(), [])]);
    }

    public function destroy(int $id)
    {
        if ($no = $this->ownerOnly()) {
            return $no;
        }
        $g = AsGlobalWorker::where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->firstOrFail();
        // Off the roster only; the seasons they worked keep their cards and their pay
        // (still linked, so editing an old card does not bring them back).
        $g->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => $g->workerName . ' is off your roster. The seasons they worked keep their record.']);
    }

    /** Onto one of the owner's seasons, as that season's card. */
    public function addToSeason(Request $request, int $id)
    {
        if ($no = $this->ownerOnly()) {
            return $no;
        }
        $g = AsGlobalWorker::where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->firstOrFail();
        $season = AsCroppingSchedule::active()->where('anisystemUserId', Auth::id())->where('id', (int) $request->input('scheduleId'))->firstOrFail();
        if ($season->isLocked()) {
            return response()->json(['success' => false, 'message' => $season->title . ' is closed. Reopen it to add workers.'], 423);
        }
        // The season's Logs diary records the arrival.
        $request->attributes->set('auditScheduleId', (int) $season->id);
        if (! Tier::scheduleCan($season, 'workers')) {
            Tier::scheduleDenyFor($season, 'workers', 'Workers come with {plan}. Keep your crew, their work days and their pay on every activity.');
        }
        if ($g->inSeason((int) $season->id)) {
            return response()->json(['success' => false, 'message' => $g->workerName . ' is already in ' . $season->title . '.'], 422);
        }
        $cap = Tier::scheduleLimit($season, 'workersPerSchedule');
        if ($cap !== null && AsScheduleWorker::active()->where('croppingScheduleId', $season->id)->count() >= $cap) {
            Tier::scheduleDenyFor($season, 'workersPerSchedule', 'This plan allows up to ' . $cap . ' workers per season. Move up to {plan} to add more.');
        }
        $card = AsScheduleWorker::create([
            'croppingScheduleId' => $season->id, 'workerName' => $g->workerName, 'email' => $g->email, 'phone' => $g->phone,
            'costPerHalfDay' => $g->costPerHalfDay, 'skills' => $g->skills, 'dayParts' => WorkDays::parts($g->dayParts, (array) ($g->offWeekdays ?? [])), 'notes' => $g->notes, 'globalWorkerId' => $g->id,
            'priority' => (int) AsScheduleWorker::active()->where('croppingScheduleId', $season->id)->max('priority') + 1, 'deleteStatus' => 1,
        ]);
        foreach ((array) ($g->offWeekdays ?? []) as $dow) {
            AsScheduleWorkerOffDay::create(['workerId' => $card->id, 'dayOfWeek' => (int) $dow]);
        }

        return response()->json(['success' => true, 'message' => $g->workerName . ' is in ' . $season->title . ' now.', 'data' => ['cardId' => (int) $card->id, 'season' => ['id' => (int) $season->id, 'title' => $season->title, 'cardId' => (int) $card->id]]]);
    }
}
