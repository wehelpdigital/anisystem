<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsAutoEvent;
use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsCroppingSchedule;
use App\Models\AsGlobalWorker;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleWorker;
use App\Models\AsScheduleWorkerOffDay;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\AutoBrain;
use App\Services\AutoPlanner;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\AneeEmoji;
use App\Support\AutoBook;
use App\Support\AutoGoal;
use App\Support\CropCatalog;
use App\Support\CropStages;
use App\Support\FieldCatalogue;
use App\Support\FieldProfile;
use App\Support\MediaStore;
use App\Support\Tier;
use App\Support\WorkDays;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Anee Auto (2026-10-12): Anee plans the season and keeps planning it; the
 * farmer follows, and says when a day cannot be done.
 *
 * The flow:
 *   1. Start: how many lots, a word on why every lot managed must be in it,
 *      and the price (AiPrices 'auto', per lot) confirmed as final. The
 *      credits are taken there and a season is made at once, in setup, so
 *      nothing typed is lost to a closed tab or a dropped signal.
 *   2. The wizard, saved as it goes: the season, the lots (place, pin, crop,
 *      variety, the day it goes in the field), the workers and their days,
 *      the soil by its signs, the water as it really is, the past troubles.
 *   3. Anee's first reading (a job, searched on the web): variety, soil,
 *      season, a realistic goal and the fertilizer focus, per lot.
 *   4. The fertilizer, per lot, in NPK Plus itself (framed, prefilled from
 *      the reading): bars, micros, the barrel. An unbalanced plan may go on,
 *      with the reason kept.
 *   5. Start: the calendar is written lot by lot (a job), then placed on the
 *      days by AutoPlanner. The season is live and cannot be re-answered.
 *
 * Live: the season page is a chat: today and this week, done, not today
 * (moves it), observations (photo, video, voice, read by Anee), questions.
 * The morning run (auto:daily) re-plans with the new forecast and mails
 * the owner and the workers. Everything said and done is an as_auto_events
 * row: the post harvest story.
 */
class AneeAutoController extends Controller
{
    public const KIND = 'auto';

    /**
     * Fair use inside the flat price, per season per day, growing with the
     * lots paid for: a season of one lot gets 6 readings of the field and 15
     * questions a day, and each lot more adds 2 and 5. At about 1.20 pesos a
     * reading and 0.50 a question, even a season used to the limit every day
     * stays inside what its lots paid.
     */
    private const OBSERVE_BASE = 4;
    private const OBSERVE_PER_LOT = 2;
    private const ASK_BASE = 10;
    private const ASK_PER_LOT = 5;
    private const READINGS = 3;
    /** A season's calendar is long: one call may write for five minutes, so a job is dead only after eight quiet ones. */
    private const JOB_STALE = 480;

    public function __construct(private AiCreditService $credits, private AiClient $ai, private AutoBrain $brain, private AutoPlanner $planner)
    {
    }

    /* ------------------------------------------------------------ helpers */

    private function payer(): User
    {
        $id = WorkerContext::effectiveOwnerId();

        return $id === (int) Auth::id() ? Auth::user() : (User::find($id) ?? Auth::user());
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }

    /** Why this person cannot run Anee Auto, or null. */
    private function refusal(): ?string
    {
        if (WorkerContext::inWorkerContext()) {
            return 'Only the farm owner runs Anee Auto.';
        }
        if (! Tier::farmCan('aiAnalyses')) {
            return 'Anee Auto comes with ' . Tier::withPlan(Tier::farmUnlocksAt('aiAnalyses')) . ', and every plan above it.';
        }
        $payer = $this->payer();
        if (! $payer->canUseAi()) {
            return Tier::aneeNeeds($payer, 'Anee Auto');
        }
        if (! AiSetting::current()->isUsable()) {
            return 'Anee is not switched on yet. Please check back soon.';
        }

        return null;
    }

    private function auto(int $id): AsAutoSeason
    {
        return AsAutoSeason::where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->firstOrFail();
    }

    private function schedule(AsAutoSeason $auto): AsCroppingSchedule
    {
        return AsCroppingSchedule::where('id', $auto->croppingScheduleId)->where('anisystemUserId', Auth::id())->firstOrFail();
    }

    /** The wizard's answers can change only before the calendar is written. */
    private function setupOnly(AsAutoSeason $auto)
    {
        return $auto->status !== AsAutoSeason::SETTING_UP ? $this->json(false, 'This season is running. Its answers cannot change now.', [], 423) : null;
    }

    private function lotCap(): ?int
    {
        $cap = Tier::limit('lotsPerSchedule');

        return $cap === null ? null : (int) $cap;
    }

    /* -------------------------------------------------------------- pages */

    /**
     * There is no Anee Auto seasons page any more (2026-10-27, the owner: "remove the my seasons button and that
     * page when you click it"): each season opens from its card on Cropping Schedules, where a new one starts
     * too, so an old link or bookmark lands there.
     */
    public function index()
    {
        if (WorkerContext::inWorkerContext()) {
            return redirect()->route('app.dashboard')->with('error', 'Only the farm owner runs Anee Auto.');
        }

        return redirect()->route('sm.index');
    }

    public function quote()
    {
        $payer = $this->payer();
        $why = $this->refusal();

        return $this->json(true, 'ok', [
            'perLot' => AiPrices::of(self::KIND), 'balance' => round($this->credits->balance($payer->id), 2), 'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $why === null, 'whyNot' => $why, 'lotCap' => $this->lotCap(),
            'canSchedule' => Auth::user()->canCreateSchedule(),
        ]);
    }

    public function show(int $id)
    {
        $auto = $this->auto($id);
        $schedule = $this->schedule($auto);
        if ($auto->status === AsAutoSeason::SETTING_UP) {
            return view('auto.wizard', ['auto' => $auto, 'schedule' => $schedule, 'boot' => $this->wizardState($auto, $schedule)]);
        }

        return view('auto.season', ['auto' => $auto, 'schedule' => $schedule]);
    }

    /* ------------------------------------------------------------- start */

    public function start(Request $request)
    {
        if ($why = $this->refusal()) {
            return $this->json(false, $why, [], 403);
        }
        if (! Auth::user()->canCreateSchedule()) {
            return $this->json(false, 'Your plan allows no more active seasons. Finish or archive one first.', ['upgrade' => true], 403);
        }
        $cap = $this->lotCap();
        $max = $cap === null ? 20 : min(20, $cap);
        $v = Validator::make($request->all(), ['lots' => 'required|integer|min:1|max:' . max(1, $max), 'agree' => 'accepted']);
        if ($v->fails()) {
            return $this->json(false, $cap !== null && (int) $request->input('lots') > $cap ? 'Your plan allows up to ' . $cap . ' lot' . ($cap === 1 ? '' : 's') . ' in a season.' : 'Say how many lots, and confirm the charge.', [], 422);
        }
        $lots = (int) $request->input('lots');
        $payer = $this->payer();
        $price = (float) AiPrices::of(self::KIND) * $lots;
        if (! $this->credits->unlimited((int) $payer->id) && $this->credits->balance($payer->id) < $price) {
            return $this->json(false, 'Anee Auto for ' . $lots . ' lot' . ($lots > 1 ? 's' : '') . ' takes ' . number_format($price) . ' credits and you have ' . number_format((int) floor($this->credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }

        $auto = DB::transaction(function () use ($lots, $payer, $price) {
            $title = 'Anee Auto season · ' . now('Asia/Manila')->format('M j, Y');
            $schedule = AsCroppingSchedule::create([
                'anisystemUserId' => Auth::id(), 'usersId' => (int) config('anisystem.order_users_id', 1), 'title' => $title, 'description' => null,
                'status' => 'setup', 'isActive' => 1, 'deleteStatus' => 1,
            ]);
            $schedule->forceFill(['aneeAuto' => 1])->save();
            \App\Models\AsScheduleActivityVersion::create(['croppingScheduleId' => $schedule->id, 'versionName' => 'Original', 'isOriginal' => 1, 'isActive' => 1, 'versionOrder' => 0, 'deleteStatus' => 1]);
            $auto = AsAutoSeason::create(['userId' => Auth::id(), 'croppingScheduleId' => $schedule->id, 'status' => AsAutoSeason::SETTING_UP, 'step' => 0,
                'lotsPaid' => $lots, 'creditsCharged' => $price, 'answers' => ['tags' => []], 'deleteStatus' => 1]);
            for ($i = 1; $i <= $lots; $i++) {
                $lot = AsScheduleLot::create(['croppingScheduleId' => $schedule->id, 'lotName' => 'Lot ' . $i, 'lotSizeUnit' => 'hectare', 'deleteStatus' => 1]);
                AsAutoLot::create(['autoId' => $auto->id, 'lotId' => $lot->id]);
            }
            $left = $this->credits->charge((int) $payer->id, $price, 'Anee Auto: ' . $lots . ' lot' . ($lots > 1 ? 's' : '') . ' (' . $title . ')');
            if ($left === null) {
                throw new \RuntimeException('credits');
            }
            AsAutoEvent::say($auto->id, 'setup', 'Salamat! :anee-happy: I am your Anee Auto for ' . $lots . ' lot' . ($lots > 1 ? 's' : '') . '. Tell me about the farm and I will plan the whole season, then keep it on track every day.', ['lots' => $lots, 'credits' => $price]);

            return $auto;
        });

        return $this->json(true, 'Anee Auto is yours. Let us set it up.', ['id' => $auto->id, 'url' => route('auto.show', $auto->id)]);
    }

    /* ------------------------------------------------------------ the wizard */

    private function wizardState(AsAutoSeason $auto, AsCroppingSchedule $schedule): array
    {
        $als = AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get();
        $lots = AsScheduleLot::whereIn('id', $als->pluck('lotId'))->get()->keyBy('id');
        $canWorkers = Tier::scheduleCan($schedule, 'workers');
        $cards = $canWorkers ? AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->with('offDays')->orderBy('priority')->get() : collect();
        $crops = collect(CropCatalog::visible())->map(fn ($c, $k) => ['key' => $k, 'label' => CropCatalog::label($k), 'icon' => $c['icon'] ?? '🌱', 'group' => $c['group'] ?? 'Other crops',
            'tree' => CropCatalog::isPerennial($k), 'maturity' => $c['maturity'] ?? null, 'methods' => AutoBook::methods($k), 'prep' => AutoBook::landPrep($k), 'nursery' => AutoBook::nursery($k)])->values();

        return [
            'id' => $auto->id, 'step' => $auto->step, 'lotsPaid' => $auto->lotsPaid, 'perLot' => AiPrices::of(self::KIND), 'lotCap' => $this->lotCap(),
            'balance' => round($this->credits->balance($this->payer()->id), 2), 'unlimited' => $this->credits->unlimited((int) $this->payer()->id),
            'title' => $schedule->title, 'description' => $schedule->description, 'answers' => $auto->answers ?: new \stdClass,
            'analysis' => $auto->analysis, 'analysisRuns' => (int) $auto->analysisRuns, 'readingsLeft' => max(0, self::READINGS - (int) $auto->analysisRuns),
            'job' => $auto->job ? ['kind' => $auto->job] + (array) $auto->jobState : null,
            'lots' => $als->map(function ($al) use ($lots) {
                $l = $lots->get($al->lotId);

                return $l ? ['id' => (int) $l->id, 'name' => $l->lotName, 'size' => $l->lotSize !== null ? (float) $l->lotSize : null, 'unit' => $l->lotSizeUnit === 'sqm' ? 'sqm' : 'ha',
                    'crop' => $l->crop, 'variety' => $l->variety, 'method' => $al->method, 'fieldDate' => $al->fieldDate?->toDateString(), 'sowDate' => $al->sowDate?->toDateString(),
                    'treeMonths' => $al->treeMonths, 'province' => $l->locProvince, 'town' => $l->locTown, 'barangay' => $l->locBarangay,
                    'pin' => $l->pinLat ? ['lat' => (float) $l->pinLat, 'lng' => (float) $l->pinLng] : null,
                    'calcId' => $al->calcId, 'fert' => $al->fert, 'imbalance' => (bool) $al->imbalance, 'imbalanceReason' => $al->imbalanceReason]
                    // How its planting is asked, from the first paint, so the lot card does not jump when the goals come back.
                    + ($l->crop ? ['plant' => AutoGoal::plantOptions($l->crop, $pm = AutoGoal::protoMethod($l->crop, $al->method)), 'protoMethod' => $pm,
                        'recommend' => rescue(fn () => AutoGoal::recommend($l->crop, $pm, $l->lotSize ? (float) $l->lotSize * match ($l->lotSizeUnit) { 'sqm' => 0.0001, 'acre' => 0.404686, default => 1 } : 1.0, (string) $l->variety), null, false)] : []) : null;
            })->filter()->values(),
            'canWorkers' => $canWorkers,
            'workers' => $cards->map(fn ($w) => ['id' => (int) $w->id, 'name' => $w->workerName, 'email' => $w->email, 'phone' => $w->phone, 'globalWorkerId' => $w->globalWorkerId,
                'off' => $w->offDays->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->values(), 'parts' => WorkDays::json($w->dayParts)])->values(),
            'roster' => $canWorkers ? AsGlobalWorker::where('userId', Auth::id())->where('deleteStatus', 1)->orderBy('workerName')->get()
                ->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->workerName, 'email' => $g->email, 'phone' => $g->phone, 'off' => $g->offWeekdays ?: [], 'parts' => WorkDays::json($g->dayParts)])->values() : [],
            'book' => ['signs' => AutoBook::SOIL_SIGNS, 'ph' => AutoBook::PH, 'water' => AutoBook::WATER, 'problems' => AutoBook::PROBLEMS, 'imbalance' => AutoBook::IMBALANCE],
            'optionsBook' => \App\Support\AutoOptions::forPage(),
            'options' => \App\Support\AutoOptions::of((array) ($auto->answers ?? [])),
            'crops' => $crops,
            'latest' => AutoBook::latestDate()->toDateString(), 'today' => now('Asia/Manila')->toDateString(),
            'mapsKey' => (string) config('services.google_maps.key'),
        ];
    }

    public function state(int $id)
    {
        $auto = $this->auto($id);

        return $this->json(true, 'ok', $this->wizardState($auto, $this->schedule($auto)));
    }

    public function saveBasics(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $v = Validator::make($request->all(), ['title' => 'required|string|max:255', 'description' => 'nullable|string|max:5000', 'tags' => 'nullable|array|max:20', 'tags.*' => 'string|max:40']);
        if ($v->fails()) {
            return $this->json(false, 'Give the season a name.', [], 422);
        }
        $this->schedule($auto)->update(['title' => trim((string) $request->input('title')), 'description' => $request->input('description')]);
        $a = (array) $auto->answers;
        $a['tags'] = array_values(array_unique(array_filter(array_map(fn ($t) => mb_substr(trim((string) $t), 0, 40), (array) $request->input('tags', [])))));
        $auto->update(['answers' => $a, 'step' => max((int) $auto->step, (int) $request->input('step', 0))]);

        return $this->json(true, 'Saved.');
    }

    public function saveLot(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $v = Validator::make($request->all(), [
            'lotId' => 'required|integer', 'name' => 'nullable|string|max:255', 'size' => 'nullable|numeric|min:0|max:100000', 'unit' => 'nullable|in:ha,sqm',
            'crop' => 'nullable|string|max:40', 'variety' => 'nullable|string|max:120', 'method' => 'nullable|in:transplanted,direct,planted,tree',
            'fieldDate' => 'nullable|date', 'treeMonths' => 'nullable|integer|min:0|max:1500',
            'province' => 'nullable|string|max:120', 'town' => 'nullable|string|max:120', 'barangay' => 'nullable|string|max:120',
            'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $al = AsAutoLot::where('autoId', $auto->id)->where('lotId', (int) $request->input('lotId'))->firstOrFail();
        $lot = AsScheduleLot::findOrFail($al->lotId);
        $crop = $request->input('crop');
        if ($crop && ! isset(CropCatalog::CROPS[$crop])) {
            return $this->json(false, 'Choose a crop from the list.', [], 422);
        }
        $method = $request->input('method');
        if ($crop && $method && ! array_key_exists($method, AutoBook::methods($crop))) {
            $method = array_key_first(AutoBook::methods($crop));
        }
        $field = $request->filled('fieldDate') ? Carbon::parse($request->input('fieldDate'), 'Asia/Manila')->startOfDay() : null;
        if ($field && $method !== 'tree') {
            if ($field->lt(now('Asia/Manila')->startOfDay())) {
                return $this->json(false, 'Pick today or a day ahead: Anee Auto plans from today.', [], 422);
            }
            if ($field->gt(AutoBook::latestDate())) {
                return $this->json(false, 'Pick a day before the end of next year.', [], 422);
            }
        }
        $sow = null;
        if ($field && $method === 'transplanted' && ($n = AutoBook::nursery($crop))) {
            $sow = $field->copy()->subDays((int) round(($n['min'] + $n['max']) / 2));
        }
        $prep = $field && $method !== 'tree' ? $field->copy()->subDays(AutoBook::landPrep($crop)['min']) : null;

        $lot->update(array_filter([
            'lotName' => $request->filled('name') ? trim((string) $request->input('name')) : null,
            'lotSize' => $request->input('size'), 'lotSizeUnit' => $request->input('unit') ? ($request->input('unit') === 'sqm' ? 'sqm' : 'hectare') : null,
            'crop' => $crop, 'variety' => $request->has('variety') ? (trim((string) $request->input('variety')) ?: '') : null,
            'dayType' => $method ? AutoBook::counterOf($method) : null,
            'locProvince' => $request->input('province'), 'locTown' => $request->input('town'), 'locBarangay' => $request->input('barangay'),
            'pinLat' => $request->input('lat'), 'pinLng' => $request->input('lng'),
        ], fn ($v) => $v !== null));
        if ($request->boolean('clearPin')) {
            $lot->update(['pinLat' => null, 'pinLng' => null]);
        }
        $al->update(['method' => $method ?: $al->method, 'fieldDate' => $method === 'tree' ? null : ($field?->toDateString() ?? $al->fieldDate?->toDateString()),
            'sowDate' => $sow?->toDateString(), 'prepDate' => $prep?->toDateString(), 'treeMonths' => $request->input('treeMonths', $al->treeMonths)]);
        if ($method === 'tree' && $request->filled('treeMonths')) {
            $lot->update(['treePlantedAt' => now('Asia/Manila')->subMonths((int) $request->input('treeMonths'))->toDateString()]);
        }
        // A crop or a day changed: the fertilizer plan was made for the old one.
        if ($request->hasAny(['crop', 'size', 'fieldDate']) && $al->calcId && ($al->wasChanged('fieldDate') || $lot->wasChanged(['crop', 'lotSize']))) {
            $al->update(['fert' => null, 'calcId' => null, 'imbalance' => 0, 'imbalanceReason' => null, 'imbalanceNote' => null]);
        }

        return $this->json(true, 'Saved.', ['sowDate' => $sow?->toDateString(), 'prepDate' => $prep?->toDateString(), 'method' => $al->method]);
    }

    /** One more lot, one more lot's price. */
    public function addLot(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $cap = $this->lotCap();
        $have = AsAutoLot::where('autoId', $auto->id)->count();
        if ($cap !== null && $have >= $cap) {
            return $this->json(false, 'Your plan allows up to ' . $cap . ' lot' . ($cap === 1 ? '' : 's') . ' in a season.', ['upgrade' => true], 403);
        }
        if (! $request->boolean('agree')) {
            return $this->json(false, 'Confirm the charge first.', [], 422);
        }
        $payer = $this->payer();
        $price = (float) AiPrices::of(self::KIND);
        $schedule = $this->schedule($auto);
        $lot = DB::transaction(function () use ($auto, $payer, $price, $have, $schedule) {
            if ($this->credits->charge((int) $payer->id, $price, 'Anee Auto: one more lot (' . $schedule->title . ')') === null) {
                return null;
            }
            $lot = AsScheduleLot::create(['croppingScheduleId' => $schedule->id, 'lotName' => 'Lot ' . ($have + 1), 'lotSizeUnit' => 'hectare', 'deleteStatus' => 1]);
            AsAutoLot::create(['autoId' => $auto->id, 'lotId' => $lot->id]);
            $auto->update(['lotsPaid' => (int) $auto->lotsPaid + 1, 'creditsCharged' => (float) $auto->creditsCharged + $price]);

            return $lot;
        });
        if (! $lot) {
            return $this->json(false, 'One more lot takes ' . number_format($price) . ' credits and you have ' . number_format((int) floor($this->credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }

        return $this->json(true, 'Lot added.', ['state' => $this->wizardState($auto->fresh(), $schedule)]);
    }

    public function saveWorkers(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $v = Validator::make($request->all(), ['workers' => 'nullable|array|max:60', 'workers.*.name' => 'required|string|max:255', 'workers.*.email' => 'nullable|email|max:255',
            'workers.*.phone' => 'nullable|string|max:32', 'workers.*.off' => 'nullable|array', 'workers.*.off.*' => 'integer|min:0|max:6', 'workers.*.parts' => 'nullable|array']);
        if ($v->fails()) {
            return $this->json(false, 'Each worker needs a name' . (str_contains($v->errors()->first(), 'email') ? ', and the email must be real' : '') . '.', [], 422);
        }
        $schedule = $this->schedule($auto);
        $list = collect((array) $request->input('workers', []));
        if (! Tier::scheduleCan($schedule, 'workers')) {
            // No Workers module on this plan: the helpers are kept for planning only.
            $a = (array) $auto->answers;
            $a['crew'] = $list->map(function ($w) {
                $off = array_values(array_unique(array_map('intval', (array) ($w['off'] ?? []))));

                return ['name' => mb_substr(trim((string) $w['name']), 0, 80), 'off' => $off, 'parts' => WorkDays::parts($w['parts'] ?? [], $off)];
            })->values()->all();
            $auto->update(['answers' => $a]);

            return $this->json(true, 'Saved.');
        }
        $cap = Tier::scheduleLimit($schedule, 'workersPerSchedule');
        if ($cap !== null && $list->count() > $cap) {
            return $this->json(false, 'This plan allows up to ' . $cap . ' workers in a season.', ['upgrade' => true], 403);
        }
        DB::transaction(function () use ($list, $schedule) {
            $keep = [];
            foreach ($list->values() as $i => $w) {
                $card = ! empty($w['id']) ? AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->find((int) $w['id']) : null;
                $global = ! empty($w['globalWorkerId']) ? AsGlobalWorker::where('userId', Auth::id())->where('deleteStatus', 1)->find((int) $w['globalWorkerId']) : null;
                $off = array_values(array_unique(array_map('intval', (array) ($w['off'] ?? []))));
                // A page that does not send the half days (an older one still open) keeps what was there.
                $parts = WorkDays::parts(array_key_exists('parts', $w) ? $w['parts'] : ($card?->dayParts ?? $global?->dayParts), $off);
                $facts = ['workerName' => mb_substr(trim((string) $w['name']), 0, 255), 'email' => ($w['email'] ?? null) ?: null, 'phone' => ($w['phone'] ?? null) ?: null, 'dayParts' => $parts, 'priority' => $i + 1];
                if (! $card) {
                    $card = AsScheduleWorker::create($facts + ['croppingScheduleId' => $schedule->id, 'costPerHalfDay' => $global?->costPerHalfDay ?? 0,
                        'skills' => $global?->skills, 'notes' => $global?->notes, 'globalWorkerId' => $global?->id, 'deleteStatus' => 1]);
                } else {
                    $card->update($facts);
                }
                AsScheduleWorkerOffDay::where('workerId', $card->id)->delete();
                foreach ($off as $dow) {
                    AsScheduleWorkerOffDay::create(['workerId' => $card->id, 'dayOfWeek' => $dow]);
                }
                try {
                    // Their week is theirs, not the season's: the roster keeps it (2026-10-28, the owner:
                    // "settings for selected workers should be synced in the global workers").
                    $g = AsGlobalWorker::forCard($card->fresh(), (int) Auth::id());
                    $g->update(['offWeekdays' => $off, 'dayParts' => $parts]);
                } catch (\Throwable $e) {
                    report($e);
                }
                $keep[] = $card->id;
            }
            AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->whereNotIn('id', $keep)->update(['deleteStatus' => 0]);
        });

        return $this->json(true, 'Saved.', ['workers' => $this->wizardState($auto, $schedule)['workers']]);
    }

    /**
     * The lots' goals (App\Support\AutoGoal, 2026-10-28): a lot's aim and planting saved when they are sent,
     * then every lot's goal counted again, since the soil, the water and the season move them too.
     */
    public function goal(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($request->has('lotId')) {
            if ($no = $this->setupOnly($auto)) {
                return $no;
            }
            $lotId = (int) $request->input('lotId');
            if (! AsAutoLot::where('autoId', $auto->id)->where('lotId', $lotId)->exists()) {
                return $this->json(false, 'That lot is not in this season.', [], 404);
            }
            $a = (array) $auto->answers;
            $goals = (array) ($a['goals'] ?? []);
            $goals[(string) $lotId] = AutoGoal::clean((array) $request->input('goal', []));
            $a['goals'] = $goals;
            $auto->update(['answers' => $a]);
        }

        return $this->json(true, 'ok', ['goals' => (object) AutoGoal::forSeason($auto->fresh())]);
    }

    public function saveAnswers(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $a = (array) $auto->answers;
        if ($request->has('soil')) {
            $soil = (array) $request->input('soil');
            $signs = array_values(array_intersect(array_keys(AutoBook::SOIL_SIGNS), (array) ($soil['signs'] ?? [])));
            $ph = array_key_exists($soil['ph'] ?? '', AutoBook::PH) ? $soil['ph'] : 'unsure';
            $tested = is_numeric($soil['tested'] ?? null) && $soil['tested'] >= 2 && $soil['tested'] <= 12 ? round((float) $soil['tested'], 1) : null;
            $a['soil'] = ['signs' => $signs, 'ph' => $ph, 'tested' => $tested];
            $a['soilRead'] = AutoBook::readSoil($signs, $ph, $tested);
        }
        if ($request->has('water')) {
            $a['water'] = array_key_exists((string) $request->input('water'), AutoBook::WATER) ? $request->input('water') : null;
            $a['waterNote'] = mb_substr(trim((string) $request->input('waterNote', '')), 0, 400) ?: null;
        }
        if ($request->has('problems')) {
            $a['problems'] = array_values(array_intersect(array_keys(AutoBook::PROBLEMS), (array) $request->input('problems', [])));
            $names = [];
            foreach ((array) $request->input('cropProblems', []) as $k) {
                [$section, $slug] = array_pad(explode(':', (string) $k, 2), 2, '');
                if ($p = FieldCatalogue::problem($section, $slug)) {
                    $names[$k] = $p['name'];
                }
            }
            $a['cropProblems'] = array_keys($names);
            $a['cropProblemNames'] = array_values($names);
        }
        if ($request->has('notes')) {
            $a['notes'] = mb_substr(trim((string) $request->input('notes')), 0, 2000) ?: null;
        }
        // A lot planted before its land preparation can finish, the farmer's yes to a shortened one (2026-10-28).
        if ($request->has('prepSqueeze')) {
            $mine = AsAutoLot::where('autoId', $auto->id)->pluck('lotId')->map(fn ($x) => (string) $x)->all();
            $a['prepSqueeze'] = array_filter(array_intersect_key(array_map('boolval', (array) $request->input('prepSqueeze', [])), array_flip($mine)));
        }
        // How the farmer wants the season run (App\Support\AutoOptions, 2026-10-28): the extras, the protection, the machines.
        if ($request->has('options')) {
            $a['options'] = \App\Support\AutoOptions::clean((array) $request->input('options', []) + ['set' => true]);
        }
        // The field in detail (App\Support\FieldProfile, 2026-10-15): the farm's
        // soil, water and last season, and the saved profile it started from.
        if ($request->has('fieldProfile')) {
            $fp = FieldProfile::fromInput($request->input('fieldProfile'));
            $from = $request->input('fieldProfileFrom');
            $a['fieldProfile'] = $fp ?: null;
            $a['fieldProfileFrom'] = $fp && is_array($from) && is_numeric($from['id'] ?? null)
                ? ['id' => (int) $from['id'], 'name' => mb_substr(trim((string) ($from['name'] ?? '')), 0, 120)] : null;
        }
        // Which of the farm's lots (Global Lots, App\Support\FarmLots) each lot
        // of this season was filled from (2026-10-16): the wizard's link only,
        // for this season's own lots.
        if ($request->has('farmLots')) {
            $mine = AsAutoLot::where('autoId', $auto->id)->pluck('lotId')->map(fn ($x) => (int) $x)->all();
            $links = [];
            foreach ((array) $request->input('farmLots', []) as $x) {
                $lot = (int) (is_array($x) ? ($x['lotId'] ?? 0) : 0);
                $farm = (int) (is_array($x) ? ($x['farmLotId'] ?? 0) : 0);
                if ($farm > 0 && in_array($lot, $mine, true)) {
                    $links[$lot] = ['lotId' => $lot, 'farmLotId' => $farm];
                }
            }
            $a['farmLots'] = array_values($links);
        }
        $auto->update(['answers' => $a, 'step' => max((int) $auto->step, (int) $request->input('step', 0))]);

        return $this->json(true, 'Saved.', ['soilRead' => $a['soilRead'] ?? null]);
    }

    /**
     * The best weeks to plant one lot, from today to a year ahead, two ways (App\Support\PlantWindow, 2026-10-28):
     * for the crop alone, and with the price at harvest; with the land preparation the farm can do before planting
     * (prep: the days it needs, the earliest ready day) and the days in the field to harvest. Kept half a day.
     */
    public function window(Request $request, int $id)
    {
        $auto = $this->auto($id);
        $al = AsAutoLot::where('autoId', $auto->id)->where('lotId', (int) $request->query('lotId'))->firstOrFail();
        $lot = AsScheduleLot::findOrFail($al->lotId);
        if (! $lot->crop || ! $al->method || $al->method === 'tree') {
            return $this->json(true, 'ok', ['window' => null]);
        }
        $a = (array) $auto->answers;
        $o = \App\Support\AutoOptions::of($a);
        $fp = (array) ($a['fieldProfile'] ?? []);
        $water = $a['water'] ?? ($fp ? (FieldProfile::legacy($fp)['waterAuto'] ?? null) : null);
        $prep = self::prepNeed((string) $lot->crop, (string) $al->method, $fp, $o);
        $n = AutoBook::nursery($lot->crop);
        $p = ['crop' => $lot->crop, 'method' => $al->method, 'maturity' => $lot->maturityDays() ?: CropCatalog::maturity($lot->crop),
            'nursery' => $n ? (int) round(($n['min'] + $n['max']) / 2) : 0, 'lat' => $lot->pinLat ? (float) $lot->pinLat : null, 'lng' => $lot->pinLng ? (float) $lot->pinLng : null,
            'location' => trim(implode(', ', array_filter([$lot->locTown, $lot->locProvince]))), 'water' => $water ?: 'pump_rain', 'prepNeed' => $prep['need'],
            'today' => now('Asia/Manila')->toDateString()];
        $w = Cache::remember('auto-window:v2:' . md5(json_encode($p)), 43200, fn () => rescue(fn () => \App\Support\PlantWindow::for($p), null, true));

        return $this->json(true, 'ok', ['window' => $w, 'prep' => $prep + ['on' => $o['landPrep']], 'waterKnown' => (bool) $water,
            'squeeze' => ! empty(((array) ($a['prepSqueeze'] ?? []))[(string) $lot->id])]);
    }

    /**
     * The days of land preparation a lot needs before planting, by the Land Prep Planner's own count (its steps for
     * the crop and way of planting, bent by the lot's last season: a rice field rests 30 days without rice), plus a
     * week to book a machine when hiring takes that long. need = the days, why = in words.
     *
     * @return array{need: int, why: string}
     */
    public static function prepNeed(string $crop, string $method, array $fp, array $o): array
    {
        $rice = str_starts_with($crop, 'rice');
        $has = fn (string $t) => (bool) array_filter((array) $o['fleet'], fn ($f) => $f['type'] === $t);
        $lp = $rice
            ? ($method === 'transplanted' ? ($has('transplanter') ? 'tpr_mechanical' : 'tpr_manual') : ($has('drum_seeder') ? 'dsr_wet_drum' : 'dsr_wet_broadcast'))
            : (function () use ($crop, $method) {
                $ms = array_keys(\App\Support\LandPrepPlanner::methodsFor($crop));

                return $method === 'transplanted' && in_array('transplant', $ms, true) ? 'transplant' : ($ms[0] ?? 'direct');
            })();
        $split = FieldProfile::split($fp);
        $need = 0;
        $why = '';
        try {
            $t = \App\Support\LandPrepPlanner::timeCheck(['crop' => $crop, 'method' => $lp, 'crew' => ['fleet' => $o['fleet'], 'workers' => 2],
                'lots' => [['name' => 'Lot', 'planting' => now('Asia/Manila')->addDays(400)->toDateString(), 'soil' => $split['soil'], 'history' => $split['history']]]])[0] ?? null;
            if ($t) {
                $need = (int) $t['need'];
                $why = ($t['rest'] && $t['rest'] >= $t['needed']) ? 'a rice field rests about 30 days with no rice before sowing' : 'about ' . $t['needed'] . ' days of land preparation';
            }
        } catch (\Throwable $e) {
            report($e);
        }
        if (! $need) {
            $need = (int) (AutoBook::landPrep($crop)['min'] ?? 21);
            $why = 'about ' . $need . ' days of land preparation';
        }
        $owns = (bool) array_filter((array) $o['fleet'], fn ($f) => $f['own'] > 0 && ! in_array($f['type'], ['transplanter', 'drum_seeder'], true));
        if (! $owns && $o['hire'] === 'slow') {
            $need += 7;
            $why .= ', and a week to book the hired machine';
        }

        return ['need' => $need, 'why' => $why];
    }

    /** The lots' crop problems to offer on the troubles step. */
    public function cropProblems(int $id)
    {
        $auto = $this->auto($id);
        $crops = AsScheduleLot::whereIn('id', AsAutoLot::where('autoId', $auto->id)->pluck('lotId'))->pluck('crop')->filter()->unique()->values()->all();

        return $this->json(true, 'ok', ['rows' => AutoBook::cropProblems($crops, 14)]);
    }

    /* ---------------------------------------------------------------- jobs */

    /** Is every lot answered enough for Anee to read it? Returns the first gap in words, or null. */
    private function gap(AsAutoSeason $auto, bool $forCalendar = false): ?string
    {
        foreach (AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get() as $al) {
            $lot = AsScheduleLot::find($al->lotId);
            $name = $lot?->lotName ?: 'A lot';
            if (! $lot?->crop) {
                return $name . ' has no crop yet.';
            }
            if (! $al->method) {
                return $name . ': say how it goes into the field.';
            }
            if ($al->method !== 'tree' && ! $al->fieldDate) {
                return $name . ' has no planting day yet.';
            }
            if (! $lot->lotSize) {
                return $name . ' has no size yet.';
            }
            if (! $lot->pinLat && ! ($lot->locProvince && $lot->locTown)) {
                return $name . ': pick its town and province, or drop a pin.';
            }
            if ($forCalendar && ! $al->fert && ! $al->imbalance) {
                return $name . ' has no fertilizer plan yet.';
            }
        }
        $a = (array) $auto->answers;
        if (empty($a['water'])) {
            return 'Say where the water comes from.';
        }

        return null;
    }

    public function analyze(int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        if ($why = $this->refusal()) {
            return $this->json(false, $why, [], 403);
        }
        if ($gap = $this->gap($auto)) {
            return $this->json(false, $gap, [], 422);
        }
        if ($this->running($auto)) {
            return $this->json(true, 'Already working on it.', ['pending' => true]);
        }
        if ((int) $auto->analysisRuns >= self::READINGS) {
            return $this->json(false, 'Anee has read this farm ' . self::READINGS . ' times already. Go on to the fertilizer.', [], 429);
        }
        $this->keepFieldProfile($auto);

        return $this->runInBackground($auto, 'reading', fn () => $this->runReading($auto->fresh()));
    }

    /**
     * The field in detail, kept for the next analysis when Anee first reads
     * the farm: against the season's one lot, or named after the season when
     * it has several (the answers are the farm's, not one lot's).
     */
    private function keepFieldProfile(AsAutoSeason $auto): void
    {
        $a = (array) $auto->answers;
        if (empty($a['fieldProfile'])) {
            return;
        }
        // The answers are one of the farm's lots, picked as they are: nothing new to keep.
        $from = (int) ($a['fieldProfileFrom']['id'] ?? 0);
        if ($from && in_array($from, array_map(fn ($x) => (int) ($x['farmLotId'] ?? 0), (array) ($a['farmLots'] ?? [])), true)) {
            return;
        }
        try {
            $lots = AsScheduleLot::whereIn('id', AsAutoLot::where('autoId', $auto->id)->pluck('lotId'))->get(['id', 'lotName', 'crop']);
            $one = $lots->count() === 1 ? $lots->first() : null;
            $name = $one ? ($one->lotName ?: 'Lot 1') : (string) ($this->schedule($auto)->title ?: 'My field');
            FieldProfile::keepFor((int) $this->payer()->id, (array) $a['fieldProfile'], $name, $lots->pluck('crop')->filter()->first(),
                $a['fieldProfileFrom']['id'] ?? null, $one ? (int) $one->id : null, $one ? (int) $auto->croppingScheduleId : null);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function generate(int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        if ($why = $this->refusal()) {
            return $this->json(false, $why, [], 403);
        }
        if (! $auto->analysis) {
            return $this->json(false, 'Let Anee read the farm first.', [], 422);
        }
        if ($gap = $this->gap($auto, true)) {
            return $this->json(false, $gap, [], 422);
        }
        if ($this->running($auto)) {
            return $this->json(true, 'Already working on it.', ['pending' => true]);
        }

        return $this->runInBackground($auto, 'calendar', fn () => $this->runCalendar($auto->fresh()));
    }

    private function running(AsAutoSeason $auto): bool
    {
        return $auto->job && ($auto->jobState['status'] ?? '') === 'pending' && $auto->jobAt && $auto->jobAt->gt(now()->subSeconds(self::JOB_STALE));
    }

    private function runInBackground(AsAutoSeason $auto, string $kind, callable $work)
    {
        $auto->update(['job' => $kind, 'jobState' => ['status' => 'pending', 'phase' => 'start', 'try' => 1], 'jobAt' => now()]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $auto->id]])->send();
            fastcgi_finish_request();
            $work();
            exit;
        }
        @set_time_limit(1800);
        $work();

        return $this->job($auto->id);
    }

    private function beat(AsAutoSeason $auto, string $phase, int $try = 1): void
    {
        AsAutoSeason::where('id', $auto->id)->update(['jobState' => json_encode(['status' => 'pending', 'phase' => $phase, 'try' => $try]), 'jobAt' => now()]);
    }

    private function finishJob(AsAutoSeason $auto, ?string $error = null): void
    {
        AsAutoSeason::where('id', $auto->id)->update(['jobState' => json_encode($error ? ['status' => 'failed', 'error' => $error] : ['status' => 'ready']), 'jobAt' => now()]);
    }

    public function job(int $id)
    {
        $auto = $this->auto($id);
        $s = (array) $auto->jobState;
        $status = $s['status'] ?? 'none';
        if ($status === 'pending' && (! $auto->jobAt || $auto->jobAt->lt(now()->subSeconds(self::JOB_STALE)))) {
            $this->finishJob($auto, 'Anee was stopped halfway. Nothing was lost; please try again.');
            $status = 'failed';
            $s['error'] = 'Anee was stopped halfway. Nothing was lost; please try again.';
        }
        if ($status === 'pending') {
            return $this->json(true, 'Working…', ['status' => 'pending', 'pending' => true, 'kind' => $auto->job, 'phase' => $s['phase'] ?? 'start', 'try' => (int) ($s['try'] ?? 1),
                'beatAgo' => $auto->jobAt ? (int) max(0, now()->diffInSeconds($auto->jobAt, true)) : null]);
        }
        if ($status === 'failed') {
            return $this->json(false, $s['error'] ?? 'Anee could not finish. Please try again.', ['status' => 'failed'], 422);
        }

        return $this->json(true, 'Ready.', ['status' => 'ready', 'kind' => $auto->job, 'analysis' => $auto->analysis, 'live' => $auto->status === AsAutoSeason::ACTIVE, 'url' => route('auto.show', $auto->id)]);
    }

    private function runReading(AsAutoSeason $auto): void
    {
        $settings = AiSetting::current()->forField('PH');
        try {
            $this->beat($auto, 'start');
            $unknown = AutoBrain::unknownVarietiesOf($auto);
            [$research, $doc] = $this->brain->readingPrompts($auto);
            // The brief is the case's (unknown varieties, a lot's soil the atlas lacks), so its notes are kept for the next farm (2026-10-19).
            $result = $this->ai->researchThenJson($settings, $research, $doc, 9000, fn (string $t) => AutoBrain::parseObject($t, 'lots'), fn ($phase, $try = 1) => $this->beat($auto, $phase, (int) $try),
                3000, ['kind' => 'auto', 'scope' => mb_substr('auto ' . $auto->id, 0, 250)]);
            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException($result['error'] ?? 'Anee\'s reading could not be read. Please try again.');
            }
            // Learn once (2026-10-18): the varieties the reading looked up are kept for every module after.
            foreach ($unknown as $crop => $names) {
                \App\Support\VarietyLearned::harvest((string) $crop, $names, (string) ($result['researchText'] ?? ''),
                    ['via' => 'auto', 'userId' => (int) $this->payer()->id, 'searched' => (bool) ($result['searched'] ?? false), 'sources' => (array) ($result['sources'] ?? [])]);
            }
            $data = $result['data'];
            // The goals are the app's count, never Anee's guess (2026-10-28, AutoGoal): Anee says their words only.
            // The numbers kept here are as read; the wizard shows them counted again (the farmer may change the planting).
            $goals = rescue(fn () => AutoGoal::forSeason($auto), [], false);
            foreach ((array) ($data['lots'] ?? []) as $i => $rl) {
                $g = $goals[(int) ($rl['lotId'] ?? 0)] ?? null;
                $w = (array) ($rl['goal'] ?? []);
                $data['lots'][$i]['goal'] = ['why' => (string) ($w['why'] ?? ''),
                    'words' => array_map(fn ($k) => mb_substr(trim((string) ($w[$k] ?? '')), 0, 400), array_combine(array_keys(AutoGoal::LEVELS), array_keys(AutoGoal::LEVELS)))]
                    + ($g && $g['levels'] ? ['levels' => array_map(fn ($lv) => $lv['t'], $g['levels']), 'steps' => $g['steps'], 'tPerHa' => $g['levels']['realistic']['t']] : []);
            }
            $data['at'] = now('Asia/Manila')->format('M j, Y g:i A');
            $data['sources'] = array_slice((array) ($result['sources'] ?? []), 0, 12);
            $data['searched'] = (bool) ($result['searched'] ?? false);
            AiUsage::record(self::KIND, (int) Auth::id(), (int) $this->payer()->id, $auto->id, $settings, $result, 0);
            $auto->update(['analysis' => $data, 'analysisAt' => now(), 'analysisRuns' => (int) $auto->analysisRuns + 1, 'step' => max((int) $auto->step, 7)]);
            AsAutoEvent::say($auto->id, 'analysis', $data['headline'] ?? 'I read the farm.', ['kind' => 'reading']);
            $this->finishJob($auto);
        } catch (\Throwable $e) {
            report($e);
            $this->finishJob($auto, mb_substr($e->getMessage(), 0, 300));
        }
    }

    private function runCalendar(AsAutoSeason $auto): void
    {
        $settings = AiSetting::current()->forField('PH');
        try {
            $als = AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get();
            $plans = [];
            $made = [];
            foreach ($als->values() as $i => $al) {
                $this->beat($auto, 'lot-' . ($i + 1));
                $prompt = $this->brain->calendarPrompt($auto, (int) $al->lotId);
                $result = $this->ai->askForJson($settings, $prompt, 24000, fn (string $t) => ($d = AutoBrain::parseObject($t, 'tasks')) && is_array($d['tasks']) && count($d['tasks']) >= 5 ? $d : null,
                    ['timeout' => 360, 'onPhase' => fn ($p, $try = 1) => $this->beat($auto, 'lot-' . ($i + 1), (int) $try)]);
                if (! ($result['ok'] ?? false)) {
                    throw new \RuntimeException('The calendar for ' . (AsScheduleLot::find($al->lotId)?->lotName ?: 'a lot') . ' could not be written. Nothing is lost; please try again.');
                }
                AiUsage::record(self::KIND, (int) Auth::id(), (int) $this->payer()->id, $auto->id, $settings, $result, 0);
                // A season is more than a handful of jobs: a short calendar is sent back once to be filled in.
                $count = count((array) $result['data']['tasks']);
                if ($al->method !== 'tree' && $count < 28) {
                    $this->beat($auto, 'lot-' . ($i + 1), 2);
                    $more = $this->ai->askForJson($settings, $prompt . "

YOUR FIRST ANSWER HAD ONLY " . $count . " TASKS: " . json_encode(array_map(fn ($t) => ($t['day'] ?? '?') . ' ' . ($t['title'] ?? ''), (array) $result['data']['tasks']), JSON_UNESCAPED_UNICODE)
                        . ". A farmer following you day by day needs the whole season: return the COMPLETE list again, at least 32 tasks, keeping those and adding what is missing (the seedbed or seed preparation, each land preparation pass, every water stage, a scouting walk every week from planting to grain fill, each weeding, every fertilizer split, the preventive sprays the crop book calls for, harvest preparations and right after harvest).",
                        24000, fn (string $t) => ($d = AutoBrain::parseObject($t, 'tasks')) && is_array($d['tasks']) && count($d['tasks']) > $count ? $d : null, ['timeout' => 360]);
                    if ($more['ok'] ?? false) {
                        AiUsage::record(self::KIND, (int) Auth::id(), (int) $this->payer()->id, $auto->id, $settings, $more, 0);
                        $result = $more;
                    }
                }
                $plans[(int) $al->lotId] = $result['data'];
            }
            $this->beat($auto, 'placing');
            DB::transaction(function () use ($auto, $als, $plans, &$made) {
                AsAutoTask::where('autoId', $auto->id)->where('origin', 'plan')->delete();
                $order = 0;
                foreach ($als as $al) {
                    $p = $plans[(int) $al->lotId] ?? null;
                    if (! $p) {
                        continue;
                    }
                    foreach (AutoBrain::taskRows((array) $p['tasks'], $auto->id, (int) $al->lotId, 'plan', $order) as $row) {
                        AsAutoTask::create($row);
                        $order++;
                    }
                    $made[(int) $al->lotId] = count((array) $p['tasks']);
                    if (! empty($p['harvestDay']) && $al->fieldDate) {
                        $al->update(['harvestDate' => Carbon::parse($al->fieldDate->toDateString())->addDays((int) $p['harvestDay'])->toDateString()]);
                    }
                    // The lot's own day counter, as every other module reads it.
                    $lot = AsScheduleLot::find($al->lotId);
                    if ($lot && $al->method !== 'tree' && $al->fieldDate) {
                        $lot->update(['dayZeroDate' => ($al->method === 'transplanted' && $al->sowDate ? $al->sowDate : $al->fieldDate)->toDateString(),
                            'transplantDate' => $al->method === 'transplanted' ? $al->fieldDate->toDateString() : null]);
                    }
                }
                $auto->update(['status' => AsAutoSeason::ACTIVE, 'generatedAt' => now(), 'plan' => [
                    'lots' => collect($plans)->map(fn ($p, $lotId) => ['lotId' => $lotId, 'strategy' => $p['strategy'] ?? null, 'harvestDay' => $p['harvestDay'] ?? null])->values()->all(),
                    'safety' => array_values(array_unique(collect($plans)->flatMap(fn ($p) => (array) ($p['safetyChecks'] ?? []))->map(fn ($x) => (string) $x)->all())),
                    'compatibility' => array_values(array_unique(collect($plans)->flatMap(fn ($p) => (array) ($p['compatibility'] ?? []))->map(fn ($x) => (string) $x)->all())),
                ]]);
                AsCroppingSchedule::where('id', $auto->croppingScheduleId)->update(['status' => 'generated']);
            });
            $auto = $auto->fresh();
            $this->planner->plan($auto);
            $names = AsScheduleLot::whereIn('id', array_keys($made))->pluck('lotName', 'id');
            foreach ((array) ($auto->plan['lots'] ?? []) as $l) {
                if (! empty($l['strategy'])) {
                    AsAutoEvent::say($auto->id, 'plan', $l['strategy'], ['tasks' => $made[$l['lotId']] ?? 0], ['lotId' => $l['lotId']]);
                }
            }
            AsAutoEvent::say($auto->id, 'briefing', 'Your season is planned :anee-thumbsup: ' . array_sum($made) . ' tasks across ' . count($made) . ' lot' . (count($made) > 1 ? 's' : '') . '. Each morning I check the weather and your workers and move what needs moving. Just tell me when a day cannot be done.', ['kind' => 'welcome']);
            $this->finishJob($auto);
        } catch (\Throwable $e) {
            report($e);
            $this->finishJob($auto, mb_substr($e->getMessage(), 0, 300));
        }
    }

    /* ------------------------------------------------------- the fertilizer */

    /** The lot's NPK Plus calculation, read back and kept with the season. */
    public function saveFert(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $al = AsAutoLot::where('autoId', $auto->id)->where('lotId', (int) $request->input('lotId'))->firstOrFail();
        $calc = DB::table('as_npk_calcs')->where('id', (int) $request->input('calcId'))->where('userId', Auth::id())->where('deleteStatus', 1)->first();
        if (! $calc) {
            return $this->json(false, 'That fertilizer plan could not be found.', [], 404);
        }
        $r = json_decode((string) $calc->result, true) ?: [];
        $fert = [
            'lines' => array_map(fn ($l) => array_intersect_key((array) $l, array_flip(['id', 'name', 'grade', 'amount', 'unit', 'kg'])), (array) ($r['lines'] ?? [])),
            'npkPerHa' => $r['npkPerHa'] ?? null, 'perHa' => $r['perHa'] ?? [], 'needs' => $r['needs'] ?? [], 'micros' => $r['micros'] ?? [],
            'reach' => $r['reach'] ?? null, 'goal' => $r['goal'] ?? null, 'areaHa' => $r['areaHa'] ?? null, 'season' => $r['season'] ?? null,
        ];
        $short = collect((array) $fert['needs'])->filter(fn ($n) => ($n['state'] ?? '') !== 'right')->keys()->all();
        $microShort = collect((array) $fert['micros'])->filter(fn ($m) => ($m['state'] ?? '') === 'short' || ($m['state'] ?? '') === 'over')->keys()->all();
        $balanced = ! $short && ! $microShort && ($fert['reach'] === null || (int) $fert['reach'] >= 90);
        $al->update(['calcId' => (int) $calc->id, 'fert' => $fert] + ($balanced ? ['imbalance' => 0, 'imbalanceReason' => null, 'imbalanceNote' => null] : []));

        return $this->json(true, 'Saved.', ['fert' => $fert, 'balanced' => $balanced, 'off' => array_merge($short, $microShort)]);
    }

    public function saveImbalance(Request $request, int $id)
    {
        $auto = $this->auto($id);
        if ($no = $this->setupOnly($auto)) {
            return $no;
        }
        $v = Validator::make($request->all(), ['lotId' => 'required|integer', 'reason' => 'required|in:' . implode(',', array_keys(AutoBook::IMBALANCE)), 'note' => 'nullable|string|max:1000']);
        if ($v->fails()) {
            return $this->json(false, 'Pick a reason.', [], 422);
        }
        $al = AsAutoLot::where('autoId', $auto->id)->where('lotId', (int) $request->input('lotId'))->firstOrFail();
        $al->update(['imbalance' => 1, 'imbalanceReason' => $request->input('reason'), 'imbalanceNote' => $request->input('note'), 'imbalanceAt' => now()]);

        return $this->json(true, 'Noted. Anee plans with what you have.');
    }

    /* -------------------------------------------------------------- live */

    public function feed(Request $request, int $id)
    {
        $auto = $this->auto($id);
        // The morning, if the scheduler has not run it yet today (no mail from here).
        if ($auto->status === AsAutoSeason::ACTIVE && ! ($auto->briefDate && $auto->briefDate->isSameDay(now('Asia/Manila'))) && ! $request->query('before')) {
            try {
                app(\App\Services\AutoMorning::class)->run($auto, false);
                $auto->refresh();
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $tz = 'Asia/Manila';
        $today = now($tz)->startOfDay();
        $als = AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get()->keyBy('lotId');
        $lots = AsScheduleLot::whereIn('id', $als->keys())->get()->keyBy('id');
        $people = AutoPlanner::people($auto)->keyBy('id');
        $tasks = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where(function ($q) use ($today) {
            $q->where(fn ($w) => $w->where('status', 'planned')->where('plannedDate', '<=', $today->copy()->addDays(13)->toDateString()))
                ->orWhere(fn ($w) => $w->where('status', 'done')->where('doneDate', '>=', $today->copy()->subDays(1)->toDateString()));
        })->orderBy('plannedDate')->orderByDesc('critical')->orderBy('sortOrder')->get();

        $lotFacts = $lots->map(function ($l) use ($als, $today) {
            $al = $als->get($l->id);
            $day = $al?->fieldDate ? (int) Carbon::parse($al->fieldDate->toDateString(), 'Asia/Manila')->diffInDays($today, false) : null;
            $das = $al?->sowDate ? (int) Carbon::parse($al->sowDate->toDateString(), 'Asia/Manila')->diffInDays($today, false) : null;
            $counter = $al?->method ? AutoBook::counterOf($al->method) : 'DAP';
            $stage = null;
            if ($day !== null && $day >= 0) {
                try {
                    $stage = CropStages::stageFor($l->crop, $day, $counter, $l->maturityDays())['label'] ?? null;
                } catch (\Throwable $e) {
                }
            }
            $say = $al?->method === 'tree' ? 'Trees, ' . intdiv((int) $al->treeMonths, 12) . ' yr'
                : ($day === null ? '' : ($day < 0 ? ($al->method === 'transplanted' && $das !== null && $das >= 0 ? 'DAS ' . $das . ' · transplant in ' . (-$day) . ' d' : abs($day) . ' days to ' . $counter . ' 0') : $counter . ' ' . $day));

            // How far through its season the lot is, field day to harvest, for the header's bar.
            $span = $al?->fieldDate && $al?->harvestDate ? max(1, (int) $al->fieldDate->diffInDays($al->harvestDate, false)) : null;

            return ['id' => (int) $l->id, 'name' => $l->lotName, 'crop' => $l->crop ? CropCatalog::label($l->crop) : null, 'icon' => CropCatalog::CROPS[$l->crop]['icon'] ?? '🌱',
                'variety' => $l->variety, 'day' => $day, 'say' => $say, 'stage' => $stage, 'harvest' => $al?->harvestDate?->format('M j'),
                'pct' => $span && $day !== null ? max(0, min(100, (int) round($day / $span * 100))) : null,
                'toHarvest' => $al?->harvestDate ? (int) $today->diffInDays(Carbon::parse($al->harvestDate->toDateString(), 'Asia/Manila'), false) : null];
        })->values();

        $sky = $this->planner->skies($lots);
        $first = $sky[0] ?? [];
        $week = [];
        for ($k = 0; $k < 7; $k++) {
            $d = $today->copy()->addDays($k)->toDateString();
            if (isset($first[$d])) {
                $week[] = ['date' => $d, 'dow' => $k === 0 ? 'Today' : $today->copy()->addDays($k)->format('D'), 'emoji' => $first[$d]['emoji'], 'pop' => $first[$d]['pop'], 'tmax' => $first[$d]['tmax'] ?? null];
            }
        }

        $row = fn (AsAutoTask $t) => [
            'id' => (int) $t->id, 'lotId' => $t->lotId, 'lot' => $t->lotId ? ($lots->get($t->lotId)?->lotName) : null, 'kind' => $t->kind,
            'kindLabel' => AutoBook::KINDS[$t->kind][0] ?? 'Farm work', 'emoji' => AutoBook::KINDS[$t->kind][1] ?? '🧑‍🌾', 'tone' => AutoBook::KINDS[$t->kind][3] ?? 'leaf',
            'title' => $t->title, 'summary' => $t->summary, 'why' => $t->why, 'detail' => $t->detail, 'date' => $t->plannedDate?->toDateString(),
            'when' => $t->plannedDate ? ($t->plannedDate->isSameDay($today) ? 'Today' : ($t->plannedDate->isSameDay($today->copy()->addDay()) ? 'Tomorrow' : $t->plannedDate->format('D, M j'))) : null,
            'time' => $t->timeOfDay, 'critical' => (bool) $t->critical, 'status' => $t->status, 'note' => $t->planNote, 'moved' => (int) $t->movedCount,
            'late' => $t->status === 'planned' && $t->plannedDate && $t->plannedDate->lt($today),
            'workers' => collect((array) $t->workerIds)->map(fn ($w) => $people->get($w)['name'] ?? null)->filter()->values(),
            'doneNote' => $t->doneNote, 'doneDate' => $t->doneDate?->format('M j'),
            'shop' => collect((array) ($t->detail['products'] ?? []))->filter(fn ($p) => ! empty($p['group']) && preg_match('/IRAC|FRAC|HRAC/i', (string) $p['group']))
                ->map(fn ($p) => FieldCatalogue::shopLinks((string) $p['name']))->values(),
        ];
        $open = $tasks->where('status', 'planned');
        $todayRows = $open->filter(fn ($t) => $t->plannedDate && $t->plannedDate->lte($today))->values();
        $weekRows = $open->filter(fn ($t) => $t->plannedDate && $t->plannedDate->gt($today) && $t->plannedDate->lte($today->copy()->addDays(6)))->values();
        $ready = $open->filter(fn ($t) => $t->plannedDate && $t->plannedDate->lte($today->copy()->addDays(10)))
            ->flatMap(fn ($t) => collect((array) ($t->detail['bring'] ?? []))->merge(collect((array) ($t->detail['products'] ?? []))->map(fn ($p) => trim(($p['name'] ?? '') . (! empty($p['amount']) ? ', ' . $p['amount'] : '')))))
            ->filter()->unique()->take(14)->values();

        $before = (int) $request->query('before', 0);
        $events = AsAutoEvent::where('autoId', $auto->id)->when($before > 0, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id')->limit(30)->get()->reverse()->values();
        // What the cards need beside the events: the tasks they name (as they stand now), the lots and the people.
        $taskIds = $events->flatMap(fn ($e) => array_merge([$e->taskId], (array) ($e->data['tasks'] ?? [])))->filter()->map(fn ($x) => (int) $x)->unique()->values();
        $ctx = ['tasks' => $taskIds->isEmpty() ? collect() : AsAutoTask::where('autoId', $auto->id)->whereIn('id', $taskIds)->get()->keyBy('id'),
            'lots' => $lots, 'people' => $people, 'today' => $today];

        return $this->json(true, 'ok', [
            'status' => $auto->status, 'title' => $this->schedule($auto)->title, 'lots' => $lotFacts, 'week' => $week,
            'today' => $todayRows->map($row)->values(), 'soon' => $weekRows->map($row)->values(),
            'done' => $tasks->where('status', 'done')->sortByDesc('doneAt')->take(6)->map($row)->values(),
            'ready' => $ready, 'left' => AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')->count(),
            'doneCount' => AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'done')->count(),
            'doneToday' => $tasks->where('status', 'done')->filter(fn ($t) => $t->doneDate && $t->doneDate->isSameDay($today))->map($row)->values(),
            'events' => $events->map(fn ($e) => $this->eventRow($e, $ctx))->values(), 'hasMore' => $events->count() === 30,
            'plan' => ['safety' => array_slice((array) ($auto->plan['safety'] ?? []), 0, 12), 'compatibility' => array_slice((array) ($auto->plan['compatibility'] ?? []), 0, 8)],
            'cant' => collect(AutoBook::CANT)->map(fn ($c, $k) => ['key' => $k, 'label' => $c[0], 'emoji' => $c[1]])->values(),
            'people' => $people->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name']])->values(),
        ]);
    }

    private function eventRow(AsAutoEvent $e, array $ctx = []): array
    {
        $body = (string) $e->body;
        // Anee writes a little markdown now and then: bold survives, the rest is plain words.
        $html = $e->role === 'farmer' ? nl2br(e($body)) : AneeEmoji::render(nl2br((string) preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', e($body))));

        return ['id' => (int) $e->id, 'role' => $e->role, 'kind' => $e->kind, 'html' => $html, 'data' => $e->data, 'taskId' => $e->taskId,
            'media' => collect((array) $e->media)->map(fn ($m) => ['type' => $m['type'] ?? 'image', 'url' => MediaStore::url($m['path'] ?? null)])->filter(fn ($m) => $m['url'])->values(),
            'at' => $e->created_at ? Carbon::parse($e->created_at)->timezone('Asia/Manila')->format('M j, g:i A') : '',
            'time' => $e->created_at ? Carbon::parse($e->created_at)->timezone('Asia/Manila')->format('g:i A') : '',
            'day' => $e->created_at ? $this->dayWord(Carbon::parse($e->created_at)->timezone('Asia/Manila')) : '',
            'card' => $ctx ? $this->cardOf($e, $ctx) : null];
    }

    /** "Today", "Yesterday", or the weekday and date, for the story's day lines. */
    private function dayWord(Carbon $at): string
    {
        $today = now('Asia/Manila')->startOfDay();

        return $at->isSameDay($today) ? 'Today' : ($at->isSameDay($today->copy()->subDay()) ? 'Yesterday' : $at->format($at->year === $today->year ? 'l, M j' : 'l, M j, Y'));
    }

    /**
     * What a story line shows as a card (2026-10-27, the owner: "don't make it look like a simple chat. so daily
     * thing should appear as cards"): the morning, a task done, a day that could not be, a task moved, what the
     * farmer showed and what Anee saw, each lot's plan. Null for plain talk (a question and its answer).
     */
    private function cardOf(AsAutoEvent $e, array $ctx): ?array
    {
        $d = (array) ($e->data ?? []);
        $lots = $ctx['lots'];
        $lotOf = fn ($id) => $id ? ['name' => $lots->get($id)?->lotName, 'icon' => CropCatalog::CROPS[$lots->get($id)?->crop]['icon'] ?? '🌱'] : null;
        $when = function (?string $date) use ($ctx) {
            if (! $date) {
                return null;
            }
            $c = Carbon::parse($date, 'Asia/Manila');

            return $c->isSameDay($ctx['today']) ? 'Today' : ($c->isSameDay($ctx['today']->copy()->addDay()) ? 'Tomorrow' : ($c->isSameDay($ctx['today']->copy()->subDay()) ? 'Yesterday' : $c->format('D, M j')));
        };
        $task = function ($id) use ($ctx, $lotOf, $when) {
            $t = $id ? $ctx['tasks']->get((int) $id) : null;

            return $t ? ['id' => (int) $t->id, 'title' => $t->title, 'emoji' => AutoBook::KINDS[$t->kind][1] ?? '🧑‍🌾', 'kind' => AutoBook::KINDS[$t->kind][0] ?? 'Farm work',
                'tone' => AutoBook::KINDS[$t->kind][3] ?? 'leaf', 'lot' => $lotOf($t->lotId)['name'] ?? null, 'critical' => (bool) $t->critical, 'status' => $t->status,
                'date' => $when($t->plannedDate?->toDateString()), 'done' => $t->status === 'done', 'doneDate' => $when($t->doneDate?->toDateString())] : null;
        };
        $why = ['weather' => 'for the weather', 'workers' => 'for the workers', 'both' => 'for the weather and the workers', 'late' => 'it is due', 'new' => null];
        // A re-plan's list names new tasks too (from null): only the ones that really moved are said.
        $movedList = fn ($list) => array_values(array_map(fn ($m) => ['title' => $m['title'] ?? '', 'to' => $when($m['to'] ?? null), 'why' => $why[$m['why'] ?? ''] ?? null],
            array_slice(array_filter((array) $list, fn ($m) => is_array($m) && ! empty($m['to']) && (! array_key_exists('from', $m) || $m['from'] !== null)), 0, 6)));

        return match (true) {
            $e->kind === 'briefing' && ! empty($d['finished']) => ['type' => 'finished'],
            $e->kind === 'briefing' && ($d['kind'] ?? null) === 'welcome' => ['type' => 'welcome'],
            $e->kind === 'briefing' && ! empty($d['date']) => [
                'type' => 'morning', 'date' => Carbon::parse($d['date'])->format('l, M j'), 'isToday' => Carbon::parse($d['date'])->isSameDay($ctx['today']),
                'sky' => $d['sky'] ?? null, 'lots' => $d['lots'] ?? null,
                'tasks' => array_values(array_filter(array_map($task, (array) ($d['tasks'] ?? [])))),
                'moved' => isset($d['movedList']) ? $movedList($d['movedList']) : null, 'movedCount' => (int) ($d['moved'] ?? 0), 'late' => (int) ($d['late'] ?? 0),
            ],
            $e->kind === 'done' => ['type' => 'done', 'task' => $task($e->taskId), 'lot' => $lotOf($e->lotId), 'used' => $d['used'] ?? null,
                'workers' => ($t = $ctx['tasks']->get((int) $e->taskId)) ? collect((array) ($t->doneData['workers'] ?? []))->map(fn ($w) => $ctx['people']->get($w)['name'] ?? null)->filter()->values()->all() : [],
                'note' => ($t = $ctx['tasks']->get((int) $e->taskId)) ? $t->doneNote : null],
            $e->kind === 'cant' => ['type' => 'cant', 'task' => $task($e->taskId), 'reason' => isset(AutoBook::CANT[$d['reason'] ?? '']) ? ['label' => AutoBook::CANT[$d['reason']][0], 'emoji' => AutoBook::CANT[$d['reason']][1]] : null,
                'from' => $when($d['from'] ?? null)],
            $e->kind === 'moved' => ['type' => 'moved', 'task' => $task($e->taskId), 'to' => $when($d['to'] ?? null),
                'from' => $when(collect((array) ($d['moved'] ?? []))->first(fn ($m) => (int) ($m['id'] ?? 0) === (int) $e->taskId)['from'] ?? null),
                'others' => $movedList(array_filter((array) ($d['moved'] ?? []), fn ($m) => (int) ($m['id'] ?? 0) !== (int) $e->taskId))],
            $e->kind === 'reply' && ! empty($d['what']) => ['type' => 'diagnosis', 'lot' => $lotOf($e->lotId)],
            $e->kind === 'reply' && $e->taskId && isset($d['moved']) => ['type' => 'praise', 'moved' => $movedList($d['moved'])],
            $e->kind === 'observation' => ['type' => 'seen', 'lot' => $lotOf($e->lotId)],
            $e->kind === 'plan' => ['type' => 'lotplan', 'lot' => $lotOf($e->lotId), 'tasks' => (int) ($d['tasks'] ?? 0)],
            $e->kind === 'analysis' => ['type' => 'reading'],
            $e->kind === 'setup' => ['type' => 'setup', 'lots' => (int) ($d['lots'] ?? 0)],
            default => null,
        };
    }

    private function liveTask(AsAutoSeason $auto, int $taskId): AsAutoTask
    {
        abort_unless($auto->status === AsAutoSeason::ACTIVE, 423);

        return AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->findOrFail($taskId);
    }

    public function done(Request $request, int $id, int $taskId)
    {
        $auto = $this->auto($id);
        $t = $this->liveTask($auto, $taskId);
        $v = Validator::make($request->all(), ['date' => 'nullable|date', 'note' => 'nullable|string|max:2000', 'workers' => 'nullable|array', 'used' => 'nullable|string|max:400',
            'photo' => 'nullable|image|max:12288']);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $today = now('Asia/Manila')->startOfDay();
        $date = $request->filled('date') ? Carbon::parse($request->input('date'), 'Asia/Manila')->startOfDay() : $today;
        if ($date->gt($today)) {
            $date = $today;
        }
        $media = [];
        if ($request->hasFile('photo')) {
            if ($p = MediaStore::putFile($request->file('photo'), 'auto', $auto->id)) {
                $media[] = ['type' => 'image', 'path' => $p];
            }
        }
        $t->update(['status' => 'done', 'doneDate' => $date->toDateString(), 'doneAt' => now(), 'doneNote' => $request->input('note'),
            'doneData' => array_filter(['workers' => array_values(array_map('intval', (array) $request->input('workers', []))), 'used' => $request->input('used'), 'media' => $media ?: null])]);

        // The day the crop went in is the day every count runs from: a sowing, transplanting or planting done on another day moves them all.
        $shift = '';
        $al = $t->lotId ? AsAutoLot::where('autoId', $auto->id)->where('lotId', $t->lotId)->first() : null;
        $isSeedbed = $t->anchor === 'sow' && in_array($t->kind, ['nursery', 'sowing'], true) && (int) $t->offsetDays === 0;
        $isField = $t->anchor === 'field' && in_array($t->kind, ['transplant', 'planting', 'sowing'], true) && (int) $t->offsetDays === 0;
        if ($al && ($isSeedbed || $isField)) {
            $col = $isSeedbed ? 'sowDate' : 'fieldDate';
            if ($al->{$col} && ! $al->{$col}->isSameDay($date)) {
                $diff = (int) Carbon::parse($al->{$col}->toDateString(), 'Asia/Manila')->diffInDays($date, false);
                $al->update([$col => $date->toDateString()] + ($al->harvestDate && ! $isSeedbed ? ['harvestDate' => $al->harvestDate->copy()->addDays($diff)->toDateString()] : []));
                $lot = AsScheduleLot::find($al->lotId);
                if ($lot) {
                    $lot->update($isSeedbed ? ['dayZeroDate' => $date->toDateString()] : ($al->method === 'transplanted' ? ['transplantDate' => $date->toDateString()] : ['dayZeroDate' => $date->toDateString()]));
                }
                $shift = ' The days after it now count from ' . $date->format('M j') . ' (' . ($diff > 0 ? $diff . ' day' . ($diff > 1 ? 's' : '') . ' later' : abs($diff) . ' day' . (abs($diff) > 1 ? 's' : '') . ' earlier') . ' than planned), so I moved what follows.';
            }
        }
        AsAutoEvent::say($auto->id, 'done', '✅ ' . $t->title . ($request->filled('note') ? "\n" . $request->input('note') : ''), ['task' => $t->title, 'used' => $request->input('used')],
            ['role' => 'farmer', 'taskId' => $t->id, 'lotId' => $t->lotId, 'userId' => Auth::id(), 'media' => $media ?: null]);
        $moved = $this->planner->plan($auto->fresh());
        $praise = ['Ayos! :anee-thumbsup:', 'Galing! :anee-happy:', 'Nice work :anee-grin:', 'Done and noted :anee-salute:'][$t->id % 4];
        AsAutoEvent::say($auto->id, 'reply', $praise . ' ' . $t->title . ' is done.' . $shift . $this->movedWords($moved), ['moved' => $moved], ['taskId' => $t->id, 'lotId' => $t->lotId]);

        return $this->json(true, 'Marked done.');
    }

    public function cant(Request $request, int $id, int $taskId)
    {
        $auto = $this->auto($id);
        $t = $this->liveTask($auto, $taskId);
        $v = Validator::make($request->all(), ['reason' => 'required|in:' . implode(',', array_keys(AutoBook::CANT)), 'note' => 'nullable|string|max:1000', 'days' => 'nullable|integer|min:1|max:30']);
        if ($v->fails()) {
            return $this->json(false, 'Pick a reason.', [], 422);
        }
        $why = AutoBook::CANT[$request->input('reason')];
        $wait = max((int) $why[2], (int) $request->input('days', 0));
        $from = $t->plannedDate?->toDateString();
        $t->update(['notBefore' => now('Asia/Manila')->startOfDay()->addDays($wait)->toDateString(), 'movedCount' => (int) $t->movedCount + 1]);
        AsAutoEvent::say($auto->id, 'cant', $why[1] . ' Cannot do "' . $t->title . '" today: ' . mb_strtolower($why[0]) . ($request->filled('note') ? '. ' . $request->input('note') : '') . '.',
            ['reason' => $request->input('reason'), 'from' => $from], ['role' => 'farmer', 'taskId' => $t->id, 'lotId' => $t->lotId, 'userId' => Auth::id()]);
        $moved = $this->planner->plan($auto->fresh());
        $t->refresh();
        $late = ! empty($t->planNote['late']) || ! empty($t->planNote['risk']);
        $say = 'No problem :anee-smile: I moved "' . $t->title . '" to ' . ($t->plannedDate ? $t->plannedDate->format('l, M j') : 'the next good day') . '.'
            . ($t->critical && $late ? ' This one matters for the yield, so let us not let it slip further :anee-concerned:' : '')
            . $this->movedWords(array_values(array_filter($moved, fn ($m) => $m['id'] !== $t->id)));

        AsAutoEvent::say($auto->id, 'moved', $say, ['to' => $t->plannedDate?->toDateString(), 'moved' => $moved], ['taskId' => $t->id, 'lotId' => $t->lotId]);

        return $this->json(true, 'Moved to ' . ($t->plannedDate ? $t->plannedDate->format('D, M j') : 'later') . '.', ['to' => $t->plannedDate?->toDateString()]);
    }

    /** "I also moved …", for the tasks a re-plan shifted. */
    private function movedWords(array $moved): string
    {
        $moved = array_values(array_filter($moved, fn ($m) => $m['from'] !== null));
        if (! $moved) {
            return '';
        }
        $why = ['weather' => 'for the weather', 'workers' => 'so the workers are not doubled up', 'both' => 'for the weather and the workers', 'late' => 'because it is due', 'new' => ''];
        $bits = array_map(fn ($m) => '"' . $m['title'] . '" to ' . Carbon::parse($m['to'])->format('D, M j') . (($why[$m['why']] ?? '') ? ' ' . $why[$m['why']] : ''), array_slice($moved, 0, 4));

        return ' I also moved ' . implode('; ', $bits) . (count($moved) > 4 ? '; and ' . (count($moved) - 4) . ' more' : '') . '.';
    }

    /** What the farmer saw: pictures, a video's frames, a voice note, words. Anee reads it. */
    public function observe(Request $request, int $id)
    {
        $auto = $this->auto($id);
        abort_unless($auto->status === AsAutoSeason::ACTIVE, 423);
        if ($why = $this->refusal()) {
            return $this->json(false, $why, [], 403);
        }
        $v = Validator::make($request->all(), [
            'lotId' => 'nullable|integer', 'text' => 'nullable|string|max:3000',
            'photos' => 'nullable|array|max:3', 'photos.*' => 'image|max:12288',
            'frames' => 'nullable|array|max:3', 'frames.*' => 'string|max:2500000',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:61440',
            'audio' => 'nullable|file|max:12288',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $used = AsAutoEvent::where('autoId', $auto->id)->where('kind', 'observation')->where('created_at', '>=', now('Asia/Manila')->startOfDay())->count();
        $cap = self::OBSERVE_BASE + self::OBSERVE_PER_LOT * max(1, (int) $auto->lotsPaid);
        if ($used >= $cap) {
            return $this->json(false, 'That is ' . $cap . ' observations today. Send the next one tomorrow, or ask in the chat.', [], 429);
        }
        $lotId = $request->filled('lotId') ? (int) $request->input('lotId') : null;
        if ($lotId && ! AsAutoLot::where('autoId', $auto->id)->where('lotId', $lotId)->exists()) {
            $lotId = null;
        }
        $text = trim((string) $request->input('text', ''));
        $pics = [];
        $media = [];
        foreach ((array) $request->file('photos', []) as $f) {
            $pics[] = ['mime' => $f->getMimeType() ?: 'image/jpeg', 'data' => base64_encode((string) file_get_contents($f->getRealPath()))];
            if ($p = MediaStore::putFile($f, 'auto', $auto->id)) {
                $media[] = ['type' => 'image', 'path' => $p];
            }
        }
        foreach ((array) $request->input('frames', []) as $dataUrl) {
            if (preg_match('#^data:(image/(?:jpeg|png|webp));base64,(.+)$#', (string) $dataUrl, $m)) {
                $pics[] = ['mime' => $m[1], 'data' => $m[2]];
            }
        }
        if ($request->hasFile('video') && ($p = MediaStore::putFile($request->file('video'), 'auto', $auto->id))) {
            $media[] = ['type' => 'video', 'path' => $p];
        }
        $heard = false;
        if ($request->hasFile('audio')) {
            if ($voice = $this->voiceForModel($request->file('audio'))) {
                $pics[] = $voice;
                $heard = true;
            }
            if ($p = MediaStore::putFile($request->file('audio'), 'auto', $auto->id)) {
                $media[] = ['type' => 'audio', 'path' => $p];
            }
        }
        if (! $pics && $text === '') {
            return $this->json(false, $request->hasFile('audio') ? 'Anee could not hear that recording here. Type a few words about it instead.' : 'Add a picture, a video or a few words.', [], 422);
        }
        $pics = array_slice($pics, 0, 5);
        $pictureCount = count(array_filter($pics, fn ($x) => str_starts_with((string) $x['mime'], 'image/')));
        $mine = AsAutoEvent::say($auto->id, 'observation', $text !== '' ? $text : ($heard ? '🎙️ A voice note from the field' : '📷 From the field'), ['pictures' => $pictureCount, 'voice' => $heard],
            ['role' => 'farmer', 'lotId' => $lotId, 'userId' => Auth::id(), 'media' => $media ?: null]);

        $settings = AiSetting::current()->forField('PH');
        $prompt = $this->brain->observationPrompt($auto, $lotId, $text, $pictureCount, (bool) $request->input('frames'), $heard);
        $res = $this->ai->ask($settings->forDocument(), [], $prompt, $pics ?: null, 5000, ['timeout' => 120]);
        $data = ($res['ok'] ?? false) ? AutoBrain::parseObject((string) $res['text'], 'reply') : null;
        AiUsage::record(self::KIND, (int) Auth::id(), (int) $this->payer()->id, $auto->id, $settings, $res, 0);
        if (! $data) {
            AsAutoEvent::say($auto->id, 'reply', 'I could not read that one clearly :anee-oops: Try another picture in good light, close to the leaf or the pest.', [], ['lotId' => $lotId]);

            return $this->json(false, 'Anee could not read it. Try again with a clearer picture.', [], 502);
        }
        $added = [];
        if (! empty($data['newTasks']) && is_array($data['newTasks'])) {
            $start = AutoPlanner::anchorDate($auto, null, 'start');
            $today = now('Asia/Manila')->startOfDay();
            $rows = array_map(function ($t) use ($start, $today) {
                $t['anchor'] = 'start';
                $t['day'] = (int) $start->diffInDays($today->copy()->addDays(max(0, (int) ($t['inDays'] ?? 0))), false);
                $t['early'] = 0;

                return $t;
            }, array_slice($data['newTasks'], 0, 5));
            foreach (AutoBrain::taskRows($rows, $auto->id, $lotId, 'observation', 10000 + $mine->id) as $row) {
                $added[] = AsAutoTask::create($row)->title;
            }
            if ($added) {
                $this->planner->plan($auto->fresh());
            }
        }
        $reply = trim((string) ($data['reply'] ?? ''));
        if ($added) {
            $reply .= "\nI added to your calendar: " . implode('; ', $added) . '.';
        }
        AsAutoEvent::say($auto->id, 'reply', $reply ?: ($data['what'] ?? 'Noted.'), [
            'what' => $data['what'] ?? null, 'confidence' => $data['confidence'] ?? null, 'severity' => $data['severity'] ?? null,
            'reading' => $data['reading'] ?? null, 'actions' => array_slice((array) ($data['actions'] ?? []), 0, 6), 'watch' => $data['watch'] ?? null, 'added' => $added,
        ], ['lotId' => $lotId, 'taskId' => null]);

        return $this->json(true, 'Read.');
    }

    /**
     * A voice note the model can hear: Gemini takes audio beside the words,
     * as mp3 here (ffmpeg makes it small and in a form it reads; a phone
     * records webm or mp4). Null where it cannot be heard.
     */
    private function voiceForModel(\Illuminate\Http\UploadedFile $file): ?array
    {
        if (AiSetting::current()->provider !== 'gemini') {
            return null;
        }
        $bin = \App\Support\VideoOptimizer::usableBinary();
        if ($bin) {
            $out = tempnam(sys_get_temp_dir(), 'auv') . '.mp3';
            $cmd = escapeshellarg($bin) . ' -y -hide_banner -loglevel error -i ' . escapeshellarg($file->getRealPath()) . ' -t 180 -vn -ac 1 -ar 16000 -b:a 32k ' . escapeshellarg($out) . ' 2>&1';
            @exec($cmd, $o, $code);
            if ($code === 0 && @filesize($out) > 500) {
                $data = base64_encode((string) file_get_contents($out));
                @unlink($out);

                return ['mime' => 'audio/mp3', 'data' => $data];
            }
            @unlink($out);
        }
        $mime = (string) $file->getMimeType();

        return in_array($mime, ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/aac', 'audio/flac'], true) && $file->getSize() < 8 * 1024 * 1024
            ? ['mime' => $mime === 'audio/mpeg' ? 'audio/mp3' : $mime, 'data' => base64_encode((string) file_get_contents($file->getRealPath()))] : null;
    }

    /** The season's story, day by day, for reading back and for the post harvest report. */
    public function log(int $id)
    {
        $auto = $this->auto($id);
        $lots = AsScheduleLot::whereIn('id', AsAutoLot::where('autoId', $auto->id)->pluck('lotId'))->pluck('lotName', 'id');
        $done = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'done')->orderBy('doneDate')->orderBy('doneAt')->get();
        $events = AsAutoEvent::where('autoId', $auto->id)->whereIn('kind', ['done', 'cant', 'moved', 'observation', 'reply', 'plan', 'analysis'])->orderBy('id')->get();
        $days = [];
        foreach ($events as $e) {
            $d = Carbon::parse($e->created_at)->timezone('Asia/Manila')->toDateString();
            $days[$d][] = ['at' => Carbon::parse($e->created_at)->timezone('Asia/Manila')->format('g:i A'), 'role' => $e->role, 'kind' => $e->kind,
                'lot' => $e->lotId ? ($lots[$e->lotId] ?? null) : null, 'text' => mb_substr(strip_tags((string) $e->body), 0, 600), 'data' => $e->data];
        }
        krsort($days);

        return $this->json(true, 'ok', [
            'days' => collect($days)->map(fn ($rows, $d) => ['date' => $d, 'label' => Carbon::parse($d)->format('l, M j, Y'), 'rows' => $rows])->values(),
            'done' => $done->map(fn ($t) => ['date' => $t->doneDate?->format('M j'), 'title' => $t->title, 'lot' => $t->lotId ? ($lots[$t->lotId] ?? null) : null,
                'kind' => AutoBook::KINDS[$t->kind][0] ?? 'Farm work', 'note' => $t->doneNote, 'used' => $t->doneData['used'] ?? null, 'planned' => $t->firstDate?->format('M j'),
                'moved' => (int) $t->movedCount])->values(),
            'counts' => ['done' => $done->count(), 'moved' => AsAutoEvent::where('autoId', $auto->id)->where('kind', 'cant')->count(),
                'observations' => AsAutoEvent::where('autoId', $auto->id)->where('kind', 'observation')->count()],
        ]);
    }

    public function ask(Request $request, int $id)
    {
        $auto = $this->auto($id);
        abort_unless($auto->isLive(), 423);
        if ($why = $this->refusal()) {
            return $this->json(false, $why, [], 403);
        }
        $q = trim((string) $request->input('text', ''));
        if ($q === '' || mb_strlen($q) > 2000) {
            return $this->json(false, 'Ask in a few words.', [], 422);
        }
        $used = AsAutoEvent::where('autoId', $auto->id)->where('kind', 'ask')->where('created_at', '>=', now('Asia/Manila')->startOfDay())->count();
        $cap = self::ASK_BASE + self::ASK_PER_LOT * max(1, (int) $auto->lotsPaid);
        if ($used >= $cap) {
            return $this->json(false, 'That is ' . $cap . ' questions today. Ask again tomorrow, or use Anee\'s chat.', [], 429);
        }
        AsAutoEvent::say($auto->id, 'ask', $q, [], ['role' => 'farmer', 'userId' => Auth::id()]);
        $recent = AsAutoEvent::where('autoId', $auto->id)->orderByDesc('id')->limit(10)->get()->reverse()
            ->map(fn ($e) => ($e->role === 'farmer' ? 'Farmer: ' : 'Anee: ') . mb_substr(strip_tags((string) $e->body), 0, 400))->values()->all();
        $settings = AiSetting::current()->forField('PH');
        $res = $this->ai->ask($settings, [], $this->brain->askPrompt($auto, $q, $recent), null, 1500, ['timeout' => 90]);
        AiUsage::record(self::KIND, (int) Auth::id(), (int) $this->payer()->id, $auto->id, $settings, $res, 0);
        if (! ($res['ok'] ?? false)) {
            return $this->json(false, $res['error'] ?? 'Anee could not answer just now. Please try again.', [], 502);
        }
        AsAutoEvent::say($auto->id, 'reply', trim((string) $res['text']), ['kind' => 'answer']);

        return $this->json(true, 'Answered.');
    }
}
