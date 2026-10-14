<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsProtocol;
use App\Models\AsProtocolVersion;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\WeatherService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\CropVarieties;
use App\Support\FieldCatalogue;
use App\Support\FieldProfile;
use App\Support\LandPrepPlanner as LP;
use App\Support\RiceVarieties;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * LAND PREP PLANNER, by Anee (2026-10-14, round two 2026-10-15).
 *
 * The farmer walks a wizard, one question a screen (crop, how it goes in,
 * variety, seed, planting day, lots, machines, workers, then for EVERY lot
 * its soil, water, acidity, last season, weeds and troubles, then the
 * place) and gets a dated land preparation plan for every lot. The
 * arithmetic is App\Support\LandPrepPlanner's: PhilRice practice for rice
 * (PalayCheck System), the extension plans for the other crops, the rules
 * each lot's answers trigger, the seed preparation of the chosen variety,
 * and the crew laid on the calendar so nobody is in two places at once. A
 * variety not on our list is looked up on the web first. Anee reads the
 * plan against the forecast, the ENSO outlook and ten years of the area's
 * weather, and adds her own steps (tagged as hers, each with its reason),
 * notes and the why. Too little time is a warning, never a refusal: the
 * plan is squeezed to fit and Anee says what comes first.
 *
 * Saved on the analyses shelf (as_plant_analyses, kind landprep), renamable,
 * reopened from "Your plans", and sent to the Protocol Builder as a new
 * protocol or into one the farmer already has. Flat priced (AiPrices
 * 'landprep'), charged only when the plan is ready.
 */
class LandPrepController extends Controller
{
    public const KIND = 'landprep';

    /** Asked once for the whole farm (rice only): where the seed is from. */
    public const SEED_Q = ['q' => 'Where is your seed from?', 'why' => 'Home kept seed gets a germination test before soaking, so the seedbed comes up even.', 'o' => [
        'certified' => ['Certified seed', 'With a valid tag'], 'own' => ['My own or a neighbor\'s', 'Kept from the last harvest'], 'old' => ['Kept from an earlier season', 'Older than one season'],
    ]];

    /**
     * How long Anee may take over one plan (2026-10-15). Measured on the
     * live model: the same plan answered in 55 s one day and needed 211 s
     * the next, so the document clock (180 s) cut it off twice and the
     * farmer waited six minutes for "could not be reached". The job runs
     * after the response is sent, so it can afford one long try.
     */
    private const AI_TIMEOUT = 330;

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    private function guardTier(): void
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'The Land Prep Planner comes with {plan}, and with every plan above it.');
        }
    }

    public function page()
    {
        $this->guardTier();

        return view('land-prep.index', [
            'crops' => FieldCatalogue::crops(),
            'today' => now('Asia/Manila')->toDateString(),
        ]);
    }

    /** Everything the wizard needs that is not a crop list: methods, questions, machines, the price. */
    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = Tier::farmCan('aiAnalyses') && $payer->canUseAi() && $settings->isUsable();
        $methods = [];
        $lead = [];
        foreach (FieldCatalogue::crops() as $k => $c) {
            $methods[$k] = LP::methodsFor($k);
            foreach (array_keys($methods[$k]) as $m) {
                // How far ahead the work starts (the rest period and the area's calendar are not work).
                $work = array_filter(LP::baseSteps($k, $m), fn ($s) => ! in_array($s['key'], ['rest', 'calendar', 'soil_test'], true));
                $lead[$k . ':' . $m] = $work ? -min(array_column($work, 'from')) : 30;
            }
        }
        $rice = LP::riceBook()['methods'] ?? [];
        $equipment = [];
        foreach ((array) (LP::equipmentBook()['items'] ?? []) as $k => $e) {
            $equipment[$k] = ['name' => $e['name'], 'local' => $e['local'] ?? '', 'line' => $e['line'] ?? '', 'for' => $e['for'] ?? 'all',
                'plants' => ! empty($e['plants']), 'can' => array_keys((array) ($e['rates'] ?? [])),
                'photo' => ! empty($e['photo']['file']) ? asset(ltrim($e['photo']['file'], '/')) : null,
                'credit' => ! empty($e['photo']['file']) ? array_intersect_key($e['photo'], array_flip(['title', 'author', 'license', 'page', 'licenseUrl', 'what'])) : null];
        }

        return $this->json(true, 'ok', [
            'methods' => $methods,
            'lead' => $lead,
            'riceFacts' => array_map(fn ($m) => array_filter(['age' => $m['seedlingAge'] ?? null, 'rate' => $m['seedRate'] ?? null, 'spacing' => $m['spacing'] ?? null, 'stand' => $m['plantStand'] ?? null]), $rice),
            'groups' => array_map(fn ($k) => LP::groupFor($k), array_combine(array_keys(FieldCatalogue::crops()), array_keys(FieldCatalogue::crops()))),
            'seedQ' => self::SEED_Q,
            'equipment' => $equipment,
            'units' => array_map(fn ($u) => $u[0], LP::UNITS),
            'tags' => LP::TAGS,
            'reasons' => LP::REASONS,
            'today' => now('Asia/Manila')->toDateString(),
            'ph' => \App\Support\Region::ph(),
            'quote' => $canUse ? AiPrices::of(self::KIND) : null,
            'price' => AiPrices::of(self::KIND),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'creditsUrl' => \Illuminate\Support\Facades\Route::has('ai.credits') ? route('ai.credits') : null,
            'aneeFace' => $settings->faceUrl(),
            'whyNot' => $canUse ? null : (! Tier::farmCan('aiAnalyses') ? 'The Land Prep Planner comes with Libre + Anee and every plan above it.'
                : (! $payer->canUseAi() ? Tier::aneeNeeds($payer, 'The Land Prep Planner') : 'Anee is not switched on yet. Please check back soon.')),
        ]);
    }

    /** One crop's varieties for the picker, and for rice each one's seed company. */
    public function varieties(Request $request)
    {
        $crop = (string) $request->query('crop', 'rice');
        $rows = [];
        if (str_starts_with($crop, 'rice')) {
            foreach (RiceVarieties::all() as $v) {
                if (empty($v['name'])) {
                    continue;
                }
                $hy = ($v['type'] ?? '') === 'hybrid';
                $brand = $hy ? LP::brandFor($v) : null;
                $rows[] = array_filter([
                    'n' => (string) $v['name'],
                    'l' => (string) ($v['localName'] ?? ''),
                    't' => implode(', ', array_filter((array) ($v['tradeNames'] ?? []), 'is_string')),
                    'd' => (int) round((float) ($v['maturity'] ?? 0)) ?: null,
                    'h' => $hy ? 1 : 0,
                    'p' => ! empty($v['preferred']) ? 1 : 0,
                    'b' => $brand ? LP::brandName((string) $brand['brand']) : null,
                    'g' => $brand && ($brand['confidence'] ?? '') === 'general' ? 1 : null,
                ], fn ($x) => $x !== null && $x !== '');
            }
        } else {
            $book = CropVarieties::book();
            $keys = [];
            foreach ((array) ($book['crops'] ?? []) as $k => $c) {
                if (in_array($crop, (array) ($c['catalogue'] ?? []), true) || $k === $crop) {
                    $keys[] = $k;
                }
            }
            foreach ($keys as $k) {
                foreach (CropVarieties::forCrop($k) as $v) {
                    $rows[] = array_filter([
                        'n' => (string) $v['name'],
                        'l' => (string) ($v['localName'] ?? ''),
                        't' => implode(', ', array_filter((array) ($v['aliases'] ?? []), 'is_string')),
                        'd' => CropVarieties::days($v),
                        'u' => ($v['maturity']['unit'] ?? 'days') !== 'days' ? (string) $v['maturity']['unit'] : null,
                        'k' => CropVarieties::kind($v),
                    ], fn ($x) => $x !== null && $x !== '');
                }
            }
        }

        return $this->json(true, 'ok', ['crop' => $crop, 'rows' => $rows]);
    }

    /** What the seed preparation will be for a variety, before the run. */
    public function seed(Request $request)
    {
        $crop = (string) $request->query('crop', 'rice');
        $method = (string) $request->query('method', '');
        $variety = trim((string) $request->query('variety', ''));
        $prep = LP::seedPrep($crop, $method, $variety, (string) $request->query('type', ''));

        return $this->json(true, 'ok', ['prep' => $prep, 'known' => LP::knownVariety($crop, $variety)]);
    }

    /** The farmer's seasons and their lots, for "from your seasons". */
    public function seasons()
    {
        $rows = DB::table('as_schedule_lots as l')
            ->join('as_cropping_schedules as s', 's.id', '=', 'l.croppingScheduleId')
            ->where('s.anisystemUserId', (int) Auth::id())->where('s.deleteStatus', 1)->where('l.deleteStatus', 1)
            ->orderByDesc('s.id')->orderBy('l.id')->limit(300)
            ->get(['l.id', 'l.lotName', 'l.lotSize', 'l.lotSizeUnit', 'l.crop', 'l.dayZeroDate', 'l.transplantDate', 'l.locTown', 'l.locProvince',
                'l.pinLat', 'l.pinLng', 's.title as scheduleTitle', 's.id as scheduleId']);
        $seasons = [];
        foreach ($rows as $r) {
            $size = (float) $r->lotSize;
            $unit = match ((string) $r->lotSizeUnit) {
                'sqm' => 'sqm',
                'acre' => 'acre',
                default => 'ha',
            };
            $ha = $size * LP::UNITS[$unit][1];
            $seasons[$r->scheduleId] ??= ['id' => (int) $r->scheduleId, 'title' => (string) $r->scheduleTitle, 'lots' => []];
            $seasons[$r->scheduleId]['lots'][] = [
                'id' => (int) $r->id,
                'name' => (string) $r->lotName,
                'ha' => round($ha, 4),
                'input' => $size ?: null,
                'unit' => $unit,
                'size' => $size ? rtrim(rtrim(number_format($size, 2), '0'), '.') . ' ' . ['ha' => 'ha', 'sqm' => 'm²', 'acre' => 'acres'][$unit] : null,
                'crop' => $r->crop,
                'cropLabel' => $r->crop ? (FieldCatalogue::crops()[FieldCatalogue::cropKey($r->crop) ?? '']['1'] ?? str_replace([' — ', ' / '], [', ', ' or '], CropCatalog::label($r->crop))) : null,
                'planting' => $r->transplantDate ?: $r->dayZeroDate,
                'province' => $r->locProvince, 'town' => $r->locTown,
                'lat' => $r->pinLat !== null ? (float) $r->pinLat : null, 'lng' => $r->pinLng !== null ? (float) $r->pinLng : null,
            ];
        }

        return $this->json(true, 'ok', ['seasons' => array_values($seasons)]);
    }

    /**
     * Is there time, and how big a crew? Before the run: for each lot, the
     * days the recommended preparation needs against the days left, and the
     * steps a short run squeezes (with the lot answers when given, so lime,
     * a salt flush or a rest count); and, asked for, the recommended crew.
     * Nothing is refused here: the page warns and goes on.
     */
    public function check(Request $request)
    {
        $case = $this->caseFrom($request, true);
        if (is_string($case)) {
            return $this->json(false, $case, [], 422);
        }
        $out = ['time' => LP::timeCheck($case), 'dayWord' => LP::dayWord($case['crop'], $case['method'])];
        if ($request->boolean('advise')) {
            $out['crew'] = LP::recommendCrew($case);
        }

        return $this->json(true, 'ok', $out);
    }

    /* ================================================================ run */

    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'The Land Prep Planner'), [], 403);
        }
        $case = $this->caseFrom($request);
        if (is_string($case)) {
            return $this->json(false, $case, [], 422);
        }
        $price = AiPrices::of(self::KIND);
        if ($this->credits->balance($payer->id) < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this plan and have ' . number_format((int) floor($this->credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        // One in flight at a time: a double press must not buy two.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)
            ->where('status', 'pending')->where('deleteStatus', 1)->where('created_at', '>', now()->subMinutes(10))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id, 'research' => ! empty($case['varietyCustom'])]);
        }
        // Each lot's answers are kept as a field profile, so the next analysis can offer them (2026-10-15).
        FieldProfile::rememberLots((int) $payer->id, $case['crop'], $case['lots']);
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($this->title($case), 0, 190),
            'params' => json_encode($case), 'report' => json_encode(new \stdClass), 'credits' => 0,
            'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id, 'research' => ! empty($case['varietyCustom'])]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $case);
            exit;
        }
        @set_time_limit(self::AI_TIMEOUT + AiClient::TIMEOUT_SEARCHED * 2 + 120);
        $this->runJob($id, (int) $payer->id, $settings, $case);

        return $this->jobState($id);
    }

    /**
     * The wizard's answers, checked and normalised, or the first problem in
     * words. `$lenient` (the time check) lets the lots, their sizes and the
     * crew be missing: a missing lot is one hectare on the main planting day.
     */
    private function caseFrom(Request $request, bool $lenient = false): array|string
    {
        $crops = FieldCatalogue::crops();
        $v = Validator::make($request->all(), [
            'crop' => 'required|string|in:' . implode(',', array_keys($crops)),
            'method' => 'required|string|max:24',
            'variety' => 'nullable|string|max:120',
            'varietyCustom' => 'nullable|boolean',
            'seedType' => 'nullable|in:inbred,hybrid',
            'seedSource' => 'nullable|in:certified,own,old',
            'planting' => 'required|date_format:Y-m-d',
            'lots' => ($lenient ? 'nullable' : 'required') . '|array|max:12',
            'lots.*.name' => 'nullable|string|max:60',
            'lots.*.area' => ($lenient ? 'nullable' : 'required') . '|numeric|gt:0|max:10000000',
            'lots.*.unit' => 'nullable|in:' . implode(',', array_keys(LP::UNITS)),
            'lots.*.planting' => 'nullable|date_format:Y-m-d',
            'lots.*.sourceLotId' => 'nullable|integer',
            'lots.*.copiedFrom' => 'nullable|integer|min:0|max:11',
            'lots.*.edited' => 'nullable|boolean',
            'lots.*.soil' => 'nullable|array',
            'lots.*.history' => 'nullable|array',
            'lots.*.profile' => 'nullable|array',
            'crew' => 'nullable|array',
            'crew.workers' => 'nullable|array|max:100',
            'crew.workers.*.name' => 'nullable|string|max:40',
            'crew.workers.*.days' => 'nullable|array|max:7',
            'crew.fleet' => 'nullable|array|max:12',
            'crew.fleet.*.type' => 'nullable|string|max:40',
            'crew.fleet.*.own' => 'nullable|integer|min:0|max:20',
            'crew.fleet.*.hired' => 'nullable|integer|min:0|max:20',
            'crew.stagger' => 'nullable|boolean',
            'soil' => 'nullable|array',
            'history' => 'nullable|array',
            'location.province' => 'nullable|string|max:120',
            'location.town' => 'nullable|string|max:120',
            'location.place' => 'nullable|string|max:160',
            'location.lat' => 'nullable|numeric|between:-60,60',
            'location.lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
        ], [], ['lots.*.area' => 'lot size', 'lots.*.name' => 'lot name']);
        if ($v->fails()) {
            return $v->errors()->first();
        }
        $crop = (string) $request->input('crop');
        $method = (string) $request->input('method');
        if (! isset(LP::methodsFor($crop)[$method])) {
            return 'Pick how the crop goes in.';
        }
        $rice = str_starts_with($crop, 'rice');
        $today = now('Asia/Manila')->startOfDay();
        $main = Carbon::parse((string) $request->input('planting'), 'Asia/Manila');
        if ($main->lt($today) || $main->gt($today->copy()->addDays(400))) {
            return 'Pick a planting day from today to a year ahead.';
        }
        // Each lot's soil, water and history, one profile a lot (App\Support\FieldProfile), kept split the way the planner reads it.
        $answers = fn (array $raw): array => FieldProfile::split(FieldProfile::normalize($raw, $crop));
        $shared = $answers(['soil' => (array) $request->input('soil', []), 'history' => (array) $request->input('history', [])]);
        $lots = [];
        $given = array_values((array) $request->input('lots', []));
        if (! $given && $lenient) {
            $given = [['name' => 'Lot 1', 'area' => 1, 'unit' => 'ha']];
        }
        foreach ($given as $i => $l) {
            $name = mb_substr(trim((string) ($l['name'] ?? '')), 0, 60) ?: 'Lot ' . ($i + 1);
            $p = ! empty($l['planting']) ? Carbon::parse($l['planting'], 'Asia/Manila') : $main;
            if ($p->lt($today) || $p->gt($today->copy()->addDays(400))) {
                return 'The planting day of ' . $name . ' must be from today to a year ahead.';
            }
            $unit = isset(LP::UNITS[$l['unit'] ?? '']) ? (string) $l['unit'] : 'ha';
            $input = is_numeric($l['area'] ?? null) ? (float) $l['area'] : ($lenient ? 1.0 : 0.0);
            $ha = $input * LP::UNITS[$unit][1];
            if ($ha < 0.001 || $ha > 500) {
                return 'The size of ' . $name . ' must be from 10 square meters to 500 hectares.';
            }
            $own = isset($l['profile']) || isset($l['soil']) || isset($l['history'])
                ? $answers(array_merge((array) ($l['history'] ?? []), (array) ($l['soil'] ?? []), (array) ($l['profile'] ?? []))) : $shared;
            $lots[] = ['name' => $name, 'area' => round($ha, 4), 'input' => $input, 'unit' => $unit,
                'planting' => $p->toDateString(), 'own' => ! empty($l['planting']), 'sourceLotId' => isset($l['sourceLotId']) ? (int) $l['sourceLotId'] : null,
                'copiedFrom' => isset($l['copiedFrom']) && is_numeric($l['copiedFrom']) && (int) $l['copiedFrom'] !== $i ? (int) $l['copiedFrom'] : null,
                'edited' => ! empty($l['edited']),
                'soil' => $own['soil'], 'history' => $own['history']];
        }
        // The crew: each worker with their own days; the machines this crop and method can use.
        $usable = LP::equipmentFor($crop, $method);
        $c = (array) $request->input('crew', []);
        $c['fleet'] = array_values(array_filter((array) ($c['fleet'] ?? []), fn ($f) => is_array($f) && isset($usable[$f['type'] ?? ''])));
        if (! isset($c['workers'])) {
            $c['workers'] = [['name' => 'Worker 1', 'days' => [1, 2, 3, 4, 5, 6]]];
        }
        $crew = LP::crew($c);
        if (! $lenient && ! array_filter((array) ($c['workers'] ?? []), fn ($w) => ! empty($w['days']))) {
            return 'Give at least one worker a work day.';
        }
        $variety = trim((string) $request->input('variety', ''));

        return [
            'crop' => $crop,
            'cropLabel' => $crops[$crop][1] ?? CropCatalog::label($crop),
            'method' => $method,
            'variety' => $variety,
            'varietyCustom' => $variety !== '' && ! LP::knownVariety($crop, $variety),
            'seedType' => (string) $request->input('seedType', ''),
            'seedSource' => $rice ? (string) $request->input('seedSource', '') : '',
            'planting' => $main->toDateString(),
            'lots' => $lots,
            'crew' => $crew,
            'location' => array_filter([
                'province' => trim((string) $request->input('location.province', '')),
                'town' => trim((string) $request->input('location.town', '')),
                'place' => trim((string) $request->input('location.place', '')),
                'lat' => is_numeric($request->input('location.lat')) ? (float) $request->input('location.lat') : null,
                'lng' => is_numeric($request->input('location.lng')) ? (float) $request->input('location.lng') : null,
            ], fn ($x) => $x !== null && $x !== ''),
            'notes' => trim((string) $request->input('notes', '')),
        ];
    }

    /** The plan worked out, the variety looked up, Anee asked, the plan worked out again with her steps, the charge. */
    private function runJob(int $id, int $payerId, AiSetting $settings, array $case): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('start');
            $field = $settings->forField('PH');
            $research = $this->research($case, $field, $beat);
            if ($research) {
                $case['research'] = $research;
            }
            $plan = $this->plan($case);
            $weather = $this->weather($case, $plan);
            $beat('document');
            $result = $this->ai->askForJson($field, $this->prompt($case, $plan, $weather), 6000, fn (string $t) => $this->parse($t, $plan),
                ['onPhase' => $beat, 'timeout' => self::AI_TIMEOUT, 'retryAfterTimeout' => false]);
            if ($research) {
                // The house pays for the lookup too.
                $result['tokensIn'] = (int) ($result['tokensIn'] ?? 0) + (int) $research['tokensIn'];
                $result['tokensOut'] = (int) ($result['tokensOut'] ?? 0) + (int) $research['tokensOut'];
                $result['searched'] = ! empty($result['searched']) || ! empty($research['searched']);
            }
            if (! empty($result['timedOut'])) {
                throw new \RuntimeException('Anee is slow to answer right now and the plan did not finish. Nothing was charged. Please try again in a few minutes.');
            }
            if ($result['data'] === null) {
                Log::warning('landprep: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'Anee\'s plan could not be read. Nothing was charged. Please try again.');
            }
            $report = $this->compose($case, $plan, $weather, $result['data']);
            $price = (float) AiPrices::of(self::KIND);
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            $this->credits->chargeAllowingNegative($payerId, $price, mb_substr('Land Prep Planner: ' . $case['cropLabel'] . ', ' . count($case['lots']) . ' ' . (count($case['lots']) === 1 ? 'lot' : 'lots') . $note, 0, 250));
            DB::table('as_plant_analyses')->where('id', $id)->update([
                'report' => json_encode($report), 'credits' => round($price, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    /**
     * Anee looks up a variety that is not on our list (2026-10-15, the
     * owner: new varieties come out every season): one web searching ask,
     * before the plan, for its days to harvest, whether it is a hybrid, its
     * seed company, and the company's own soaking, water change and
     * incubation hours. The findings go into the prompt and onto the seed
     * card as Anee's research, never with links. A failed lookup is said on
     * the seed card and the plan goes on without it. Null when there is
     * nothing to look up.
     */
    private function research(array $case, AiSetting $settings, callable $beat): ?array
    {
        if (empty($case['varietyCustom']) || $case['variety'] === '') {
            return null;
        }
        $beat('research');
        $rice = str_starts_with($case['crop'], 'rice');
        $crop = FieldCatalogue::cropWord($case['crop']);
        $name = str_replace('"', '', $case['variety']);
        $said = in_array($case['seedType'], ['hybrid', 'inbred'], true) ? ' The farmer says it is ' . ($case['seedType'] === 'hybrid' ? 'a hybrid.' : 'an inbred.') : '';
        $prompt = <<<PROMPT
A Filipino farmer will plant the {$crop} variety "{$name}" in the Philippines.{$said} It is not in our variety list, so look it up on the web now: search for it and read the pages of its seed company or breeder, PhilRice, DA, BPI or the NSIC list.

Find:
1. The days from sowing to harvest.
2. Whether it is a hybrid or an inbred (or open pollinated), and its seed company or breeder.
3. The seed company's own instructions for preparing the seed before sowing: how many hours to soak it, how often to change the soaking water, and how many hours to incubate (sprout) it.

Write short plain notes of what the pages say, then end with exactly these lines, filled in. Write unknown when the pages do not say it. Never guess, and never fill in a general practice as if it were this variety's.
FOUND: yes or no (yes only if you found this exact variety)
MATURITY_DAYS: a number, or unknown
TYPE: hybrid, inbred, open pollinated, or unknown
COMPANY: the seed company or breeder, or unknown
SOAK_HOURS: a number, or a range like 24 to 48, or unknown
WATER_CHANGE_HOURS: a number, or unknown
INCUBATE_HOURS: a number, or a range like 24 to 36, or unknown
SEED_NOTES: one or two plain sentences of the company's own seed instructions, or unknown
PROMPT;
        $doc = $settings->forDocument();
        $began = microtime(true);
        $r = $this->ai->ask($doc, [], $prompt, null, 1800, ['search' => true, 'timeout' => AiClient::TIMEOUT_SEARCHED]);
        $in = (int) ($r['tokensIn'] ?? 0);
        $out = (int) ($r['tokensOut'] ?? 0);
        if (! ($r['ok'] ?? false) && microtime(true) - $began < 45) {
            // A quick failure (not a stall) gets one more try; a stall does not.
            $beat('research', 2);
            sleep(2);
            $r = $this->ai->ask($doc, [], $prompt, null, 1800, ['search' => true, 'timeout' => AiClient::TIMEOUT_SEARCHED]);
            $in += (int) ($r['tokensIn'] ?? 0);
            $out += (int) ($r['tokensOut'] ?? 0);
        }
        $res = ['asked' => true, 'ok' => (bool) ($r['ok'] ?? false) && trim((string) ($r['text'] ?? '')) !== '', 'searched' => (bool) ($r['searched'] ?? false),
            'found' => false, 'tokensIn' => $in, 'tokensOut' => $out, 'notes' => ''];
        if (! $res['ok']) {
            Log::info('landprep: variety research failed', ['variety' => $name, 'error' => $r['error'] ?? null]);

            return $res;
        }
        // No links ever reach the farmer: markdown links keep their words, bare links go with their brackets.
        $text = preg_replace(['#\[([^\]]*)\]\([^)]*\)#', '#\s*\(?\s*https?://[^\s)]*\)?#', '/[*`#]+/', '/\(\s*\)/'], ['$1', '', '', ''], (string) $r['text']);
        $get = function (string $k) use ($text): string {
            if (! preg_match('/^\W*' . $k . '\W*:\s*(.+)$/mi', $text, $m)) {
                return '';
            }
            $v = trim($m[1], " \t.");

            return preg_match('/^unknown\b/i', $v) ? '' : $v;
        };
        $clean = fn (string $v, int $max) => mb_substr(trim(preg_replace(['/\s+[\x{2013}\x{2014}]\s+|\s+-\s+/u', '/;\s*/', '/\s{2,}/'], [', ', ', ', ' '], strip_tags($v))), 0, $max);
        $range = function (string $v, int $lo, int $hi): ?array {
            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:to|-|\x{2013}|and)\s*(\d+(?:\.\d+)?)/u', $v, $m)) {
                $a = (int) round((float) $m[1]);
                $b = (int) round((float) $m[2]);
            } elseif (preg_match('/(\d+(?:\.\d+)?)/', $v, $m)) {
                $a = $b = (int) round((float) $m[1]);
            } else {
                return null;
            }
            [$a, $b] = [min($a, $b), max($a, $b)];

            return $a >= $lo && $b <= $hi ? [$a, $b] : null;
        };
        $type = strtolower($get('TYPE'));
        $days = $range($get('MATURITY_DAYS'), 40, 400);
        $refresh = $range($get('WATER_CHANGE_HOURS'), 2, 24);
        $res['found'] = str_starts_with(strtolower($get('FOUND')), 'yes');
        $res['days'] = $days ? (int) round(($days[0] + $days[1]) / 2) : null;
        $res['type'] = str_contains($type, 'hybrid') ? 'hybrid' : (str_contains($type, 'inbred') ? 'inbred' : (str_contains($type, 'open') ? 'open' : ''));
        $res['company'] = $clean($get('COMPANY'), 60) ?: null;
        $res['soak'] = $rice ? $range($get('SOAK_HOURS'), 1, 72) : null;
        $res['refresh'] = $rice && $refresh ? $refresh[0] : null;
        $res['incubate'] = $rice ? $range($get('INCUBATE_HOURS'), 6, 72) : null;
        $res['seedNotes'] = $clean($get('SEED_NOTES'), 320);
        $res['notes'] = $clean((string) preg_replace('/^\W*FOUND\W*:.*$/msi', '', $text), 1400);

        return $res;
    }

    /** The arithmetic before Anee: each lot's steps, rules, seed preparation, crew, bookings. */
    private function plan(array $case, array $extra = []): array
    {
        return LP::build($case, $extra);
    }

    /** The place, its forecast, ten years of its weather over the prep window, ENSO. All best effort. */
    private function weather(array $case, array $plan): array
    {
        $out = ['place' => null, 'forecast' => [], 'history' => null, 'plowWindow' => null, 'plantWeek' => null, 'enso' => null, 'ensoText' => ''];
        $loc = $case['location'];
        $label = trim(implode(', ', array_filter([$loc['town'] ?? null, $loc['province'] ?? null, $loc['place'] ?? null])));
        $lat = $loc['lat'] ?? null;
        $lng = $loc['lng'] ?? null;
        try {
            if (($lat === null || $lng === null) && $label !== '') {
                $geo = app(WeatherService::class)->geocode(str_replace(' (Capital)', '', $label));
                if ($geo) {
                    [$lat, $lng] = [$geo['lat'], $geo['lon']];
                } else {
                    $hit = \App\Support\PlaceSearch::find($label)[0] ?? null;
                    if ($hit) {
                        [$lat, $lng] = [(float) $hit['lat'], (float) $hit['lng']];
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        if ($lat !== null && $lng !== null) {
            $out['place'] = ['label' => $label !== '' ? str_replace(' (Capital)', '', $label) : round($lat, 3) . ', ' . round($lng, 3), 'lat' => round((float) $lat, 4), 'lng' => round((float) $lng, 4)];
            try {
                $out['forecast'] = app(WeatherService::class)->forecast((float) $lat, (float) $lng, 16) ?: [];
            } catch (\Throwable $e) {
            }
            try {
                $first = $plan['sched']['perLot'][0] ?? null;
                $P = Carbon::parse($plan['sched']['plantings'][0] ?? $case['planting']);
                $from = $P->copy()->addDays((int) min(array_column($plan['steps'], 'from')));
                $span = max(14, (int) $from->diffInDays($P->copy()->addDays(14)));
                $out['history'] = \App\Support\ClimateStore::season((float) $lat, (float) $lng, $from, $span, $label ?: null);
                $plow = collect($first['steps'] ?? [])->firstWhere('key', 'plow') ?? collect($first['steps'] ?? [])->firstWhere('key', 'crack_plow');
                if ($plow) {
                    $out['plowWindow'] = \App\Support\ClimateStore::season((float) $lat, (float) $lng, Carbon::parse($plow['start'] ?? $plow['winFrom']), 21, $label ?: null);
                }
                $out['plantWeek'] = \App\Support\ClimateStore::season((float) $lat, (float) $lng, $P->copy()->subDays(3), 14, $label ?: null);
            } catch (\Throwable $e) {
            }
        }
        try {
            $out['enso'] = \App\Support\EnsoOutlook::state();
            $out['ensoText'] = \App\Support\EnsoOutlook::forPrompt();
        } catch (\Throwable $e) {
        }

        return $out;
    }

    /** A lot's answers in words, for the prompt and the plan: [[short name, the answer], ...]. */
    private function answerWords(array $lot, string $crop): array
    {
        return FieldProfile::pairs(FieldProfile::normalize(['soil' => (array) ($lot['soil'] ?? []), 'history' => (array) ($lot['history'] ?? [])], $crop), $crop);
    }

    /** What Anee is asked: the plan as it stands, the farm, the weather, and the shape of her answer. */
    private function prompt(array $case, array $plan, array $weather): string
    {
        $case = $plan['case'] + $case;
        $methods = LP::methodsFor($case['crop']);
        $method = lcfirst($methods[$case['method']][0] ?? $case['method']);
        $word = $plan['dayWord'];
        $sched = $plan['sched'];
        $lots = implode("\n", array_map(fn ($l, $i) => '- ' . $l['name'] . ': ' . $l['area'] . ' ha, ' . $word . ' ' . Carbon::parse($sched['plantings'][$i])->format('D M j, Y')
            . ($sched['shift'][$i] ? ' (moved ' . $sched['shift'][$i] . ($sched['shift'][$i] === 1 ? ' day' : ' days') . ' later than asked so the crew can finish)' : ''), $case['lots'], array_keys($case['lots'])));
        // Lots with the same answers are told once.
        $groups = [];
        foreach ($case['lots'] as $i => $l) {
            $words = $this->answerWords($l, $case['crop']);
            $sig = json_encode($words);
            $groups[$sig] ??= ['names' => [], 'words' => $words];
            $groups[$sig]['names'][] = '"' . $l['name'] . '"';
        }
        $answers = implode("\n", array_map(fn ($g) => (count($case['lots']) > 1 ? (count($g['names']) > 1 ? 'Lots ' : 'Lot ') . implode(' and ', $g['names']) . ":\n" : '')
            . ($g['words'] ? '- ' . implode("\n- ", array_map(fn ($w) => $w[0] . ': ' . $w[1], $g['words'])) : '- nothing given'), $groups));
        $crew = $case['crew'];
        $cw = LP::crewWords($crew);
        $crewLine = $cw['workers'] . ' (' . $cw['days'] . '), ' . $cw['machines'] . '.' . ($crew['stagger'] ? ' Planting days may be staggered.' : '');
        $steps = implode("\n", array_map(fn ($s) => '- ' . $s['key'] . ' [' . $s['from'] . ' to ' . $s['to'] . '] ' . $s['what'] . ' (' . (LP::TAGS[$s['tag']] ?? $s['tag']) . ')'
            . (! empty($s['lotsWords']) ? ' ' . $s['lotsWords'] . '.' : '') . (! empty($s['rule']) ? ' Note: ' . $s['rule'] : '')
            . (! empty($s['squeezed']) ? ' SQUEEZED, was [' . $s['squeezed']['was'][0] . ' to ' . $s['squeezed']['was'][1] . ']' : ''), $plan['steps']));
        $first = $sched['perLot'][0] ?? ['steps' => []];
        $dated = implode("\n", array_map(fn ($r) => '- ' . $r['key'] . ': ' . (isset($r['start']) && $r['start'] ? $r['start'] . ' to ' . $r['end'] . ' (crew)' : $r['winFrom'] . ' to ' . $r['winTo'])
            . (isset($r['late']) ? ', LATE by ' . ($r['late'] ?? 'many') . ' days' : ''), $first['steps']));
        $conflicts = $sched['conflicts'] ? implode(', ', array_map(fn ($c) => $case['lots'][$c['lot']]['name'] . ' ' . $c['key'] . ' late ' . ($c['late'] ?? 'many') . ' days', array_slice($sched['conflicts'], 0, 12))) : 'none';
        $names = array_map(fn ($l) => $l['name'], $case['lots']);
        $notes = $plan['notes'] ? '- ' . implode("\n- ", array_map(fn ($n) => $n['text'] . ($n['lots'] ? ' (' . implode(', ', array_map(fn ($i) => $names[$i] ?? '', $n['lots'])) . ')' : ''), $plan['notes'])) : '- none';
        $time = $plan['squeeze']
            ? implode("\n", array_map(fn ($i, $q) => '- ' . $names[$i] . ': ' . $q['left'] . ' days left before ' . $word . ', the full preparation needs ' . $q['needed'] . '. The app squeezed ' . $q['count'] . ' steps to fit.'
                . ($q['age'] ? ' The seedlings will be ' . $q['age'] . ' days old instead of ' . $q['ageWas'] . '.' : ''), array_keys($plan['squeeze']), $plan['squeeze']))
            : '- Every lot has the full time it needs.';
        $prep = $plan['prep'];
        $seed = ! empty($prep['found']) && ! empty($prep['applies']) && isset($prep['soakHours'])
            ? ($prep['brand'] ?? '' ? $prep['brand'] . ' ' : '') . ($prep['type'] ?? '') . ' seed: soak ' . $prep['soakHours'] . ' h' . ($prep['refreshEveryHours'] ? ', change still water every ' . $prep['refreshEveryHours'] . ' h' : '') . ', incubate ' . $prep['incubateRange'][0] . ' to ' . $prep['incubateRange'][1] . ' h ('
                . match ($prep['confidence'] ?? '') { 'company' => 'from the seed company or PhilRice', 'research' => 'from Anee\'s own web research on this variety', default => 'general PhilRice practice, no brand instruction found' } . ')'
            : ($prep['say'] ?? 'not applicable');
        $r = $case['research'] ?? null;
        $research = ! $r ? '' : "\nVARIETY RESEARCH (your own web search just now, not on our list): " . (! $r['ok'] ? 'the lookup failed. Tell the farmer to follow the seed bag.'
            : (! $r['found'] ? 'this exact variety was not found. Tell the farmer to follow the seed bag.'
                : implode(', ', array_filter([$r['days'] ? 'about ' . $r['days'] . ' days to harvest' : null, $r['type'] ?: null, $r['company'] ? 'by ' . $r['company'] : null])) . '. Notes: ' . mb_substr($r['notes'], 0, 700)));
        $fc = $weather['forecast'] ? implode(', ', array_map(fn ($d) => $d['date'] . ' ' . $d['text'] . (isset($d['pop']) ? ' rain ' . $d['pop'] . '%' : '') . (isset($d['max']) ? ' ' . $d['min'] . ' to ' . $d['max'] . ' C' : ''), $weather['forecast'])) : 'not available';
        $h = $weather['history'];
        $climate = $h ? 'Over the whole preparation window to two weeks after ' . $word . ', the last ' . count($h['years']) . ' years averaged ' . round($h['avg']['rain']) . ' mm of rain (driest year ' . min(array_column($h['years'], 'rain')) . ' mm, wettest ' . max(array_column($h['years'], 'rain')) . ' mm), ' . $h['avg']['tmax'] . ' C average highs.' : 'not available';
        $pw = $weather['plowWindow'];
        $plowClimate = $pw ? 'The 3 weeks from the first plowing averaged ' . round($pw['avg']['rain']) . ' mm of rain (range ' . min(array_column($pw['years'], 'rain')) . ' to ' . max(array_column($pw['years'], 'rain')) . ' mm).' : '';
        $pk = $weather['plantWeek'];
        $plantClimate = $pk ? 'The two weeks around ' . $word . ' averaged ' . round($pk['avg']['rain']) . ' mm of rain.' : '';
        $enso = $weather['ensoText'] ?: 'not available';
        $place = $weather['place']['label'] ?? (trim(($case['location']['town'] ?? '') . ', ' . ($case['location']['province'] ?? ''), ', ') ?: 'not given');
        $variety = $case['variety'] !== '' ? $case['variety'] : 'not chosen yet';
        $seedSource = $case['seedSource'] !== '' ? (self::SEED_Q['o'][$case['seedSource']][0] ?? $case['seedSource']) : 'not given';
        $today = now('Asia/Manila')->format('D M j, Y');
        $lotNames = implode(', ', array_map(fn ($n) => '"' . $n . '"', $names));
        $farmerNotes = $case['notes'] !== '' ? $case['notes'] : 'none';
        $keys = implode(', ', array_column($plan['steps'], 'key'));
        $short = $plan['squeeze'] ? 'TIME IS SHORT for at least one lot (see TIME). In "shortTime", say plainly in 2 to 3 sentences what to do first, what to keep, and what can safely be shortened or skipped, with the lot names.' : 'There is enough time. Return "shortTime" as an empty string.';

        return <<<PROMPT
You are Anee, the smart farm technician of anee.io, planning the LAND PREPARATION of a Filipino farmer's lots. The app has already worked out the base plan: for rice, PhilRice practice (the PalayCheck System); for other crops, DA and extension practice; plus rules from each lot's answers and a crew calendar. Your job: read it against the soil, each field's history, the weather forecast, the ENSO outlook and the area's climate history, and ADD your judgement: your own extra steps where they are needed, notes on the steps that need them, the risks, and a plain analysis of why. Do NOT rewrite or remove the base steps, and do NOT compute dates: give day offsets, the app does the dates.

TODAY: {$today}
CROP: {$case['cropLabel']}, {$method}. Variety: {$variety}. Seed: {$seedSource}. Day 0 is the {$word} day, negative days come before it.
PLACE: {$place}
LOTS:
{$lots}
CREW: {$crewLine}
EACH LOT'S SOIL, WATER AND HISTORY (the farmer's own observations, lots with the same answers together):
{$answers}
FARMER'S NOTES: {$farmerNotes}
SEED PREPARATION: {$seed}{$research}

TIME:
{$time}

THE PLAN AS IT STANDS (key [from to] what (source)):
{$steps}

THE FIRST LOT'S DATES:
{$dated}

CREW CONFLICTS: {$conflicts}
RULES ALREADY APPLIED:
{$notes}

WEATHER
- Forecast, next 16 days at the field: {$fc}
- Climate history: {$climate} {$plowClimate} {$plantClimate}
- ENSO: {$enso}

RULES FOR YOUR ANSWER
- Add only steps that this farm needs because of something above (soil, history, weather, ENSO, crew, seed or water). At most 5. Each must say why in one sentence that names the fact. Never repeat a step that is already in the plan (keys: {$keys}).
- Offsets are days relative to the {$word} day (negative before it), from -120 to 30. Keep them realistic around the plan above, and never earlier than the days left.
- crew: "machine" or "manual" when the crew must do it (give effort as machine days or person days per hectare), else "none".
- lots: [] for every lot, or the names of the lots it applies to ({$lotNames}).
- Weather: say what the forecast and the history mean for plowing, soaking, puddling and the {$word} day (rain that delays plowing, a dry spell that leaves no water for soaking, a typhoon month). ENSO: say what the current outlook means for water during preparation. If the forecast does not reach the preparation window, say so and lean on the history.
- Crew: if there are conflicts, say plainly what to do (hire more hands, a second machine, or stagger the lots), with numbers.
- {$short}
- Pesticides and herbicides: name active ingredients only, never brands and never rates. Name no app as a source. PhilRice, IRRI and DA may be named. Never give web links.
- Plain, short words a farmer reads easily. No dashes between words, no semicolons, no markdown, no emoji. English with the odd Tagalog farm word where natural.

Return ONLY one JSON object, no fences:
{"summary":"3 to 4 sentences","analysis":[{"topic":"weather|enso|soil|history|crew|seed|water","title":"short","text":"2 to 3 sentences"}],"additions":[{"key":"short_snake_case","from":-20,"to":-18,"what":"short title","how":"what to do, 1 to 3 sentences","why":"one sentence naming the fact","reason":"weather|enso|soil|history|crew|seed|water","crew":"none|manual|machine","effort":0,"lots":[]}],"stepNotes":[{"key":"an existing key","note":"one sentence"}],"risks":[{"title":"short","level":"low|medium|high","text":"one or two sentences","when":"when to watch"}],"weather":"2 sentences on the weather for this preparation","crew":"1 to 2 sentences on the crew","shortTime":"","confidence":"low|moderate|high","dataGaps":["what would sharpen this plan"]}
analysis: 3 to 4 items. stepNotes: at most 5. risks: at most 3. dataGaps: at most 3.
Keep it short: no greeting, every text one or two plain sentences, the whole answer under about 750 words.
PROMPT;
    }

    /** Anee's JSON, checked: anything out of shape is dropped, never trusted. */
    private function parse(string $text, array $plan): ?array
    {
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text)));
        $a = strpos($text, '{');
        $b = strrpos($text, '}');
        if ($a === false || $b === false || $b <= $a) {
            return null;
        }
        $j = json_decode(substr($text, $a, $b - $a + 1), true);
        if (! is_array($j) || ! isset($j['summary']) || ! is_string($j['summary'])) {
            return null;
        }
        $clean = fn ($v, int $max = 600) => is_string($v) ? mb_substr(trim(preg_replace(['/:[a-z0-9_-]+:/i', '#https?://\S+#', '/\s+[\x{2013}\x{2014}]\s+|\s+-\s+/u', '/;\s*/', '/\s{2,}/'], ['', '', ', ', ', ', ' '], strip_tags($v))), 0, $max) : '';
        $topics = ['weather', 'enso', 'soil', 'history', 'crew', 'seed', 'water'];
        $have = array_column($plan['steps'], 'key');
        $out = ['summary' => $clean($j['summary'], 900), 'analysis' => [], 'additions' => [], 'stepNotes' => [], 'risks' => [],
            'weather' => $clean($j['weather'] ?? '', 600), 'crew' => $clean($j['crew'] ?? '', 500), 'shortTime' => $plan['squeeze'] ? $clean($j['shortTime'] ?? '', 700) : '',
            'confidence' => in_array($j['confidence'] ?? '', ['low', 'moderate', 'high'], true) ? $j['confidence'] : 'moderate', 'dataGaps' => []];
        foreach (array_slice((array) ($j['analysis'] ?? []), 0, 6) as $x) {
            if (is_array($x) && $clean($x['text'] ?? '') !== '') {
                $out['analysis'][] = ['topic' => in_array($x['topic'] ?? '', $topics, true) ? $x['topic'] : 'soil', 'title' => $clean($x['title'] ?? '', 80), 'text' => $clean($x['text'] ?? '', 700)];
            }
        }
        $seen = [];
        foreach (array_slice((array) ($j['additions'] ?? []), 0, 6) as $x) {
            if (! is_array($x) || ! is_numeric($x['from'] ?? null) || ! is_numeric($x['to'] ?? null) || $clean($x['what'] ?? '') === '') {
                continue;
            }
            $key = 'anee_' . substr(preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($x['key'] ?? ''))) ?: 'step', 0, 24);
            if (in_array($key, $have, true) || isset($seen[$key])) {
                $key .= '_' . (count($seen) + 1);
            }
            $seen[$key] = true;
            $from = max(-120, min(30, (int) $x['from']));
            $to = max(-120, min(30, (int) $x['to']));
            $crew = in_array($x['crew'] ?? '', ['manual', 'machine'], true) ? $x['crew'] : 'none';
            $effort = is_numeric($x['effort'] ?? null) ? max(0.1, min(40, (float) $x['effort'])) : 1.0;
            $out['additions'][] = [
                'key' => $key, 'from' => min($from, $to), 'to' => max($from, $to),
                'what' => $clean($x['what'], 120), 'how' => $clean($x['how'] ?? '', 700), 'why' => $clean($x['why'] ?? '', 300),
                'reason' => in_array($x['reason'] ?? '', $topics, true) ? $x['reason'] : 'soil',
                'crew' => $crew, 'effort' => $effort,
                'lots' => array_values(array_filter((array) ($x['lots'] ?? []), 'is_string')),
            ];
        }
        foreach (array_slice((array) ($j['stepNotes'] ?? []), 0, 8) as $x) {
            if (is_array($x) && in_array($x['key'] ?? '', $have, true) && $clean($x['note'] ?? '') !== '') {
                $out['stepNotes'][] = ['key' => $x['key'], 'note' => $clean($x['note'], 400)];
            }
        }
        foreach (array_slice((array) ($j['risks'] ?? []), 0, 5) as $x) {
            if (is_array($x) && $clean($x['title'] ?? '') !== '') {
                $out['risks'][] = ['title' => $clean($x['title'], 90), 'level' => in_array($x['level'] ?? '', ['low', 'medium', 'high'], true) ? $x['level'] : 'medium',
                    'text' => $clean($x['text'] ?? '', 500), 'when' => $clean($x['when'] ?? '', 120)];
            }
        }
        foreach (array_slice((array) ($j['dataGaps'] ?? []), 0, 3) as $g) {
            if ($clean($g) !== '') {
                $out['dataGaps'][] = $clean($g, 240);
            }
        }

        return $out;
    }

    /** The finished plan: Anee's steps worked into the arithmetic, every lot dated, the seed timeline, the weather card. */
    private function compose(array $case, array $plan, array $weather, array $anee): array
    {
        $names = array_map(fn ($l) => mb_strtolower($l['name']), $case['lots']);
        $extra = [];
        foreach ($anee['additions'] as $x) {
            $lots = [];
            foreach ($x['lots'] as $n) {
                $i = array_search(mb_strtolower(trim($n)), $names, true);
                if ($i !== false) {
                    $lots[] = (int) $i;
                }
            }
            $step = LP::step($x['key'], $x['from'], $x['to'], $x['what'], $x['how'], 'anee', null, $x['reason'], $x['why']);
            $step['type'] = $x['crew'] === 'machine' ? 'land_prep' : 'other';
            $step['by'] = 'anee';
            if ($x['crew'] !== 'none') {
                $step['work'] = [$x['crew'], $x['crew'] === 'machine' ? 'harrow' : $x['effort']];
            }
            if ($lots) {
                $step['lots'] = array_values(array_unique($lots));
                $step['lotsWords'] = 'For ' . implode(', ', array_map(fn ($i) => $case['lots'][$i]['name'], $step['lots']));
            }
            $extra[] = $step;
        }
        $final = $this->plan($case, $extra);
        $full = $final['case'];
        // Anee's notes ride on the steps they are about, on every lot that has them.
        $noteOn = function (array $steps) use ($anee): array {
            foreach ($steps as &$s) {
                foreach ($anee['stepNotes'] as $n) {
                    if ($n['key'] === $s['key']) {
                        $s['note'] = trim(($s['note'] ?? '') . ' ' . $n['note']);
                    }
                }
            }
            unset($s);

            return $steps;
        };
        $steps = $noteOn($final['steps']);
        $lotSteps = array_map($noteOn, $final['lotSteps']);
        $sched = $final['sched'];
        // The seed preparation, dated and timed for every lot from its own sowing day.
        $timeline = [];
        foreach ($sched['perLot'] as $pl) {
            $sow = null;
            foreach ($pl['steps'] as $r) {
                if (in_array($r['key'], ['sow_seedbed', 'sow_nursery'], true)) {
                    $sow = $r['start'] ?? $r['winFrom'];
                }
            }
            $sow ??= $pl['planting'];
            $timeline[] = ['lot' => $pl['lot'], 'sow' => $sow, 'events' => LP::seedTimeline($final['prep'], $sow)];
        }
        $prep = $final['prep'];
        $amounts = [];
        if (! empty($prep['seedKgPerHa'])) {
            foreach ($full['lots'] as $i => $l) {
                $amounts[] = ['lot' => $i, 'lo' => round($prep['seedKgPerHa'][0] * $l['area'], 1), 'hi' => round($prep['seedKgPerHa'][1] * $l['area'], 1)];
            }
        }
        $methods = LP::methodsFor($case['crop']);
        $h = $weather['history'];
        $items = (array) (LP::equipmentBook()['items'] ?? []);
        $crew = $full['crew'];
        $cw = LP::crewWords($crew);
        $research = $case['research'] ?? null;

        return [
            'v' => 2,
            'at' => now('Asia/Manila')->format('M j, Y g:i A'),
            'crop' => ['key' => $case['crop'], 'label' => $case['cropLabel'], 'icon' => FieldCatalogue::crops()[$case['crop']][2] ?? '🌱', 'local' => FieldCatalogue::crops()[$case['crop']][0] ?? $case['cropLabel']],
            'method' => ['key' => $case['method'], 'label' => $methods[$case['method']][0] ?? $case['method']],
            'dayWord' => $final['dayWord'],
            'variety' => $case['variety'],
            'seedSource' => $full['seedSource'] !== '' ? (self::SEED_Q['o'][$full['seedSource']][0] ?? '') : '',
            'lots' => array_map(fn ($l, $i) => [
                'name' => $l['name'], 'area' => $l['area'], 'input' => $l['input'] ?? null, 'unit' => $l['unit'] ?? 'ha',
                'asked' => $l['planting'], 'planting' => $sched['plantings'][$i], 'shift' => $sched['shift'][$i],
                'copiedFrom' => $l['copiedFrom'] ?? null, 'edited' => ! empty($l['edited']), 'answers' => $this->answerWords($l, $case['crop']),
                'squeeze' => isset($final['squeeze'][$i]) ? array_diff_key($final['squeeze'][$i], ['notes' => 1]) : null,
            ], $full['lots'], array_keys($full['lots'])),
            'crew' => [
                'workers' => count($crew['workers']), 'workerList' => $crew['workers'], 'stagger' => $crew['stagger'],
                'fleet' => array_map(fn ($f) => $f + ['name' => $items[$f['type']]['name'] ?? $f['type'], 'local' => $items[$f['type']]['local'] ?? '',
                    'photo' => ! empty($items[$f['type']]['photo']['file']) ? asset(ltrim($items[$f['type']]['photo']['file'], '/')) : null], $crew['fleet']),
                'equipmentLabel' => $cw['machines'], 'days' => $cw['days'],
            ],
            'steps' => $steps,
            'lotSteps' => $lotSteps,
            'perLot' => $sched['perLot'],
            'calendar' => $sched['calendar'],
            'conflicts' => $sched['conflicts'],
            'plantDays' => $sched['plantDays'],
            'bookings' => $final['bookings'],
            'rules' => $final['notes'],
            'seed' => $prep + ['timeline' => $timeline, 'amounts' => $amounts],
            'research' => $research ? array_intersect_key($research, array_flip(['ok', 'found', 'searched', 'days', 'type', 'company'])) : null,
            'weather' => [
                'place' => $weather['place'],
                'forecast' => array_map(fn ($d) => array_intersect_key($d, array_flip(['date', 'text', 'emoji', 'max', 'min', 'pop'])), $weather['forecast']),
                'history' => $h ? ['years' => count($h['years']), 'rain' => round($h['avg']['rain']), 'dry' => min(array_column($h['years'], 'rain')), 'wet' => max(array_column($h['years'], 'rain')), 'tmax' => $h['avg']['tmax']] : null,
                'plowRain' => $weather['plowWindow'] ? round($weather['plowWindow']['avg']['rain']) : null,
                'plantRain' => $weather['plantWeek'] ? round($weather['plantWeek']['avg']['rain']) : null,
                'enso' => $weather['enso'] ? $weather['enso']['label'] : null,
            ],
            'anee' => array_diff_key($anee, ['additions' => 1, 'stepNotes' => 1]),
        ];
    }

    private function title(array $case): string
    {
        $m = LP::methodsFor($case['crop'])[$case['method']][0] ?? '';
        $dates = array_unique(array_column($case['lots'], 'planting'));
        sort($dates);

        return $case['cropLabel'] . ', ' . lcfirst($m) . ' · ' . Carbon::parse($dates[0])->format('M j, Y') . ' · '
            . count($case['lots']) . ' ' . (count($case['lots']) === 1 ? 'lot' : 'lots');
    }

    /* ============================================================ the shelf */

    private function dead(object $r): bool
    {
        $beatAt = Carbon::parse($r->updated_at ?: $r->created_at);

        return $beatAt->lt(now()->subSeconds(self::AI_TIMEOUT + 90)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(20));
    }

    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)->where('kind', self::KIND)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That plan is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            if ($this->dead($r)) {
                $why = 'The plan was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1), 'beatAgo' => (int) max(0, now()->diffInSeconds(Carbon::parse($r->updated_at ?: $r->created_at), true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The plan failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Plan ready.', ['status' => 'ready', 'item' => $this->item($r), 'balance' => round($this->credits->balance($this->payer()->id), 2)]);
    }

    private function item(object $r): array
    {
        return ['id' => (int) $r->id, 'title' => $r->title, 'description' => $r->description, 'params' => json_decode((string) $r->params, true) ?: [],
            'report' => json_decode((string) $r->report, true) ?: [], 'credits' => (float) $r->credits, 'at' => Carbon::parse($r->created_at)->format('M j, Y')];
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 12;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('deleteStatus', 1)->where('status', 'ready')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')))
            ->orderByDesc('id')->skip(($page - 1) * $per)->take($per + 1)->get(['id', 'title', 'description', 'params', 'credits', 'created_at']);
        $more = $rows->count() > $per;

        return $this->json(true, 'ok', ['page' => $page, 'hasMore' => $more, 'rows' => $rows->take($per)->map(function ($r) {
            $p = json_decode((string) $r->params, true) ?: [];
            $lots = (array) ($p['lots'] ?? []);
            $dates = array_column($lots, 'planting');
            sort($dates);

            return [
                'id' => (int) $r->id, 'title' => $r->title, 'description' => $r->description, 'credits' => (float) $r->credits,
                'at' => Carbon::parse($r->created_at)->format('M j, Y'),
                'crop' => $p['crop'] ?? null, 'icon' => FieldCatalogue::crops()[$p['crop'] ?? ''][2] ?? '🌱',
                'method' => isset($p['crop'], $p['method']) ? (LP::methodsFor($p['crop'])[$p['method']][0] ?? '') : '',
                'lots' => count($lots), 'area' => round(array_sum(array_column($lots, 'area')), 2),
                'planting' => $dates ? Carbon::parse($dates[0])->format('M j, Y') : null,
            ];
        })->values()]);
    }

    public function one(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)->where('kind', self::KIND)->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return $this->json(false, 'That plan is gone.', [], 404);
        }

        return $this->json(true, 'ok', ['item' => $this->item($r)]);
    }

    public function meta(Request $request)
    {
        $title = trim((string) $request->input('title'));
        if ($title === '' || mb_strlen($title) > 191) {
            return $this->json(false, 'Give it a name up to 191 characters.', [], 422);
        }
        $ok = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', (int) $request->input('id'))->where('deleteStatus', 1)
            ->update(['title' => $title, 'updated_at' => now()]);
        if (! $ok) {
            return $this->json(false, 'That plan is not in your list.', [], 404);
        }

        return $this->json(true, 'Renamed.', ['title' => $title]);
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)->where('kind', self::KIND)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Plan deleted.');
    }

    /* ==================================================== to the builder */

    /** The member's own protocols, for "add to one of mine". */
    public function protocols()
    {
        $rows = AsProtocol::active()->where('userId', (int) Auth::id())->orderByDesc('updated_at')->limit(200)->get(['id', 'title', 'crop', 'dayType', 'updated_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($p) => [
            'id' => (int) $p->id, 'title' => $p->title, 'crop' => $p->crop,
            'cropLabel' => $p->crop ? (FieldCatalogue::crops()[$p->crop][1] ?? str_replace(' — ', ', ', (string) \App\Support\CropStages::label($p->crop))) : null,
            'cropIcon' => $p->crop ? \App\Support\CropStages::icon($p->crop) : '🌱',
            'dayType' => $p->dayType, 'updated' => Carbon::parse($p->updated_at)->format('M j, Y'),
        ])->values()]);
    }

    /**
     * Send a plan to the Protocol Builder: a new protocol counted the way
     * the crop is planted, or the version in use of one the member has, as
     * a "Land preparation" divider and one task per step on that
     * protocol's own count. Sending the same plan twice adds nothing twice.
     */
    public function port(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id' => 'required|integer', 'mode' => 'required|in:new,existing', 'protocolId' => 'nullable|integer', 'title' => 'nullable|string|max:190',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', (int) $request->input('id'))->where('kind', self::KIND)->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return $this->json(false, 'That plan is gone.', [], 404);
        }
        $report = json_decode((string) $r->report, true) ?: [];
        $params = json_decode((string) $r->params, true) ?: [];
        $crop = (string) ($params['crop'] ?? '');
        $method = (string) ($params['method'] ?? '');
        if ($request->input('mode') === 'new') {
            $pbCrop = \App\Support\CropStages::normalize($crop) ?? \App\Support\CropStages::normalize(\App\Support\CropCatalog::label($crop));
            $want = match (true) {
                LP::hasNursery($crop, $method) => 'DAT',
                in_array($method, ['dsr_wet_broadcast', 'dsr_wet_drum', 'dsr_dry', 'direct'], true) => 'DAS',
                default => 'DAP',
            };
            $dayType = ProtocolBuilderController::fitDayType($pbCrop, $want);
            $title = trim((string) $request->input('title')) ?: mb_substr('Land prep: ' . $r->title, 0, 190);
            $p = AsProtocol::create([
                'userId' => (int) Auth::id(), 'title' => mb_substr($title, 0, 190),
                'description' => 'From the Land Prep Planner: every land preparation step of "' . $r->title . '", counted from the ' . ($report['dayWord'] ?? 'planting') . ' day.',
                'tags' => [], 'crop' => $pbCrop, 'variety' => ($params['variety'] ?? '') !== '' ? mb_substr((string) $params['variety'], 0, 120) : null,
                'dayType' => $dayType, 'tasks' => [], 'history' => ['undo' => [], 'redo' => []], 'rev' => 1, 'deleteStatus' => 1,
            ]);
            $tasks = LP::toProtocol($report, ProtocolBuilderController::DAY_TYPES[$dayType]['counters'] ?? ['DAS'], (int) $r->id);
            $ver = AsProtocolVersion::create(['protocolId' => $p->id, 'userId' => (int) Auth::id(), 'name' => 'Version 1', 'tasks' => $tasks, 'materials' => [],
                'rules' => null, 'files' => [], 'history' => ['undo' => [], 'redo' => []], 'rev' => 1, 'sortOrder' => 0, 'deleteStatus' => 1]);
            $p->forceFill(['versionId' => $ver->id])->save();

            return $this->json(true, 'A new protocol was made with ' . (count($tasks) - 1) . ' steps.', ['protocolId' => (int) $p->id, 'url' => route('pb.open', ['id' => $p->id]), 'added' => count($tasks) - 1, 'title' => $p->title]);
        }
        $p = AsProtocol::active()->where('userId', (int) Auth::id())->where('id', (int) $request->input('protocolId'))->first();
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $ver = AsProtocolVersion::active()->where('protocolId', $p->id)->where('userId', (int) Auth::id())->orderByRaw('id = ? desc', [(int) $p->versionId])->orderBy('sortOrder')->orderBy('id')->first();
        if (! $ver) {
            return $this->json(false, 'Open that protocol once in the Protocol Builder, then send the plan again.', [], 409);
        }
        $have = array_values((array) ($ver->tasks ?? []));
        $ids = array_flip(array_map(fn ($t) => (string) ($t['id'] ?? ''), $have));
        $new = array_values(array_filter(LP::toProtocol($report, ProtocolBuilderController::DAY_TYPES[$p->dayType]['counters'] ?? ['DAS'], (int) $r->id), fn ($t) => ! isset($ids[$t['id']])));
        if (! $new) {
            return $this->json(true, 'This plan is already in that protocol.', ['protocolId' => (int) $p->id, 'url' => route('pb.open', ['id' => $p->id]), 'added' => 0, 'title' => $p->title]);
        }
        if (count($have) + count($new) > 300) {
            return $this->json(false, 'That protocol is too full to take ' . count($new) . ' more steps. Make a new protocol instead.', [], 422);
        }
        $top = $have ? max(array_map(fn ($t) => (int) ($t['pos'] ?? 0), $have)) : 0;
        foreach ($new as $k => $t) {
            $new[$k]['pos'] = $top + 10 * ($k + 1);
        }
        // The protocol's own undo can take it back in one step.
        $hist = is_array($ver->history) ? $ver->history : ['undo' => [], 'redo' => []];
        $hist['undo'] = array_slice(array_merge((array) ($hist['undo'] ?? []), [['tasks' => $have, 'materials' => array_values((array) ($ver->materials ?? [])), 'tab' => 'tasks']]), -15);
        $hist['redo'] = [];
        $ver->forceFill(['tasks' => array_merge($have, $new), 'history' => $hist, 'rev' => (int) $ver->rev + 1])->save();
        $added = count(array_filter($new, fn ($t) => ($t['kind'] ?? 'task') !== 'divider'));

        return $this->json(true, $added . ' steps were added to ' . $p->title . '.', ['protocolId' => (int) $p->id, 'url' => route('pb.open', ['id' => $p->id]), 'added' => $added, 'title' => $p->title]);
    }

    /* ============================================================ helpers */

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
