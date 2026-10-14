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
 * LAND PREP PLANNER, by Anee (2026-10-14).
 *
 * The farmer walks a wizard (crop, how it goes in, variety, planting day,
 * lots, crew, soil, field history, place) and gets a dated land
 * preparation plan for every lot. The arithmetic is App\Support\
 * LandPrepPlanner's: PhilRice practice for rice (PalayCheck System), the
 * extension plans for the other crops, the rules the answers trigger, the
 * seed preparation of the chosen variety, and the crew laid on the
 * calendar so nobody is in two places at once. Anee reads it against the
 * forecast, the ENSO outlook and ten years of the area's weather, and adds
 * her own steps (tagged as hers, each with its reason), notes and the why.
 *
 * Saved on the analyses shelf (as_plant_analyses, kind landprep), renamable,
 * reopened from "Your plans", and sent to the Protocol Builder as a new
 * protocol or into one the farmer already has. Flat priced (AiPrices
 * 'landprep'), charged only when the plan is ready.
 */
class LandPrepController extends Controller
{
    public const KIND = 'landprep';

    /** The soil and water, by what a farmer can see and feel: key => [question, options, only rice|other|null]. */
    public const SOIL_Q = [
        'texture' => ['How does the soil feel when you squeeze it wet?', [
            'sandy' => ['Gritty and loose', 'Falls apart in the hand (galas, sandy)'],
            'loam' => ['Soft and crumbly', 'Holds a loose ball (loam)'],
            'clay' => ['Sticky and heavy', 'Makes a ribbon between the fingers (lagkitin, clay)'],
            'heavy_clay' => ['Heavy clay that cracks', 'Opens wide cracks when it dries'],
        ], null],
        'color' => ['What color is the topsoil?', [
            'dark' => ['Dark brown or black', ''], 'brown' => ['Brown', ''], 'red' => ['Red or yellow', ''],
            'gray' => ['Gray or bluish', 'Often wet'], 'pale' => ['Pale or whitish', ''],
        ], null],
        'water' => ['Where does the field get its water?', [
            'irrigation' => ['Irrigation canal', 'National or communal system'], 'pump' => ['Pump or shallow well', ''], 'rainfed' => ['Rain only', ''],
        ], null],
        'drainage' => ['After heavy rain, how fast does the water go?', [
            'fast' => ['Soaks in fast', 'Gone within hours'], 'day' => ['Within a day', 'Or I can drain it in a day'],
            'slow' => ['Slowly', 'Some spots stay wet for days'], 'stands' => ['It stands for days', 'Hard to drain'],
        ], null],
        'hardpan' => ['Is there a hard layer under the plow depth?', [
            'yes' => ['Yes', 'The plow or a bar hits it'], 'no' => ['No', ''],
        ], null],
        'salt' => ['Any signs of salt?', [
            'none' => ['None', ''], 'crust' => ['White crust in the dry season', ''], 'sea' => ['Sea water comes in at high tide', ''],
            'tips' => ['White leaf tips and stunted patches', 'On the last crop'], 'sodic' => ['A hard crust that seals', 'Water sits on top (sodium)'],
        ], null],
        'acidity' => ['Is the soil acidic?', [
            'untested' => ['Never tested', 'Or not sure'], 'none' => ['Not acidic', 'A test said so'],
            'mild' => ['Mildly acidic', 'pH about 5 to 5.5'], 'strong' => ['Strongly acidic', 'pH below 5'],
            'sulfate' => ['Acid sulfate', 'Coastal or swamp soil with yellow mottles'], 'alkaline' => ['Alkaline or limy', 'pH above 7.5'],
        ], null],
        'rocks' => ['Rocks or stones?', ['none' => ['None', ''], 'some' => ['Some', ''], 'many' => ['Many', '']], null],
        'organic' => ['How much organic matter?', [
            'low' => ['Little', 'Pale, hard, few worms'], 'medium' => ['Some', ''], 'high' => ['A lot', 'Dark, soft, many worms'],
        ], null],
        'slope' => ['How does the land lie?', [
            'flat' => ['Flat', ''], 'gentle' => ['Gently sloping', ''], 'steep' => ['Steep', ''], 'terraced' => ['Terraced', ''],
        ], null],
    ];

    /** The field's history: key => [question, options, only rice|other|null]. */
    public const HISTORY_Q = [
        'prevCrop' => ['What grew here last?', [
            'rice_straw' => ['Rice, straw left on the field', ''], 'rice_no_straw' => ['Rice, straw burned or taken away', ''],
            'corn' => ['Corn or sorghum', ''], 'legume' => ['Mungbean, peanut or soybean', ''], 'vegetable' => ['Vegetables', ''],
            'fallow' => ['Nothing, left to weeds', ''], 'new_land' => ['Newly opened land', 'From grass, bush or forest'],
        ], null],
        'harvested' => ['When was it harvested?', [
            'under2w' => ['Less than 2 weeks ago', ''], '2to4w' => ['2 to 4 weeks ago', ''], '1to3m' => ['1 to 3 months ago', ''],
            'over3m' => ['Over 3 months ago', ''], 'not_yet' => ['Not harvested yet', ''],
        ], null],
        'stubble' => ['Is anything left standing?', [
            'none' => ['Nothing', 'Clean field'], 'stubble' => ['Stubble', ''], 'ratoon' => ['Ratoon or stray rice', 'Rice growing back'],
        ], null],
        'flooding' => ['Does the field go under water?', [
            'never' => ['Never', ''], 'sometimes' => ['Sometimes', 'In a strong typhoon'], 'often' => ['Most wet seasons', ''],
        ], null],
        'weeds' => ['How weedy was it last season?', [
            'few' => ['Few weeds', ''], 'many' => ['Many weeds', ''], 'tough' => ['Tough weeds', 'Cogon, nutsedge, grasses from roots'],
            'weedy_rice' => ['Weedy rice or heavy sedges', 'Ligaw na palay'],
        ], null],
        'snails' => ['Golden apple snails?', ['none' => ['None', ''], 'few' => ['A few', ''], 'many' => ['Many', '']], 'rice'],
        'ratsBirds' => ['Did rats or birds damage the last crop?', ['no' => ['No', ''], 'rats' => ['Rats', ''], 'birds' => ['Birds', ''], 'both' => ['Both', '']], null],
        'pests' => ['An outbreak last season?', [
            'no' => ['No', ''], 'tungro' => ['Tungro', ''], 'bph' => ['Brown planthopper', ''], 'blast' => ['Blast', ''],
        ], 'rice'],
        'zinc' => ['Dusty brown spots and stunting a few weeks after planting?', [
            'no' => ['No', ''], 'mild' => ['A little', ''], 'severe' => ['Badly', ''],
        ], 'rice'],
        'level' => ['Last season, did some spots stay dry while others held deep water?', [
            'level' => ['No, the water spread evenly', ''], 'some' => ['A few high and low spots', ''], 'uneven' => ['Very uneven', ''],
        ], 'rice'],
        'seedSource' => ['Where is your seed from?', [
            'certified' => ['Certified seed', 'With a valid tag'], 'own' => ['My own or a neighbor\'s', ''], 'old' => ['Kept from an earlier season', ''],
        ], 'rice'],
        'newLand' => ['Has the field been idle?', ['no' => ['No, farmed every season', ''], 'idle' => ['Idle for a few seasons', '']], null],
        'lastPlowed' => ['When was it last plowed?', [
            'under3m' => ['Less than 3 months ago', ''], '3to12m' => ['3 to 12 months ago', ''], 'over_year' => ['Over a year ago', ''], 'never' => ['Never', ''],
        ], null],
        'lastLimed' => ['When was it last limed?', [
            'never' => ['Never', ''], 'under_year' => ['Within the year', ''], '1to3y' => ['1 to 3 years ago', ''], 'over3y' => ['Over 3 years ago', ''],
        ], null],
    ];

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

    /** Everything the wizard needs that is not a crop list: methods, questions, the price. */
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

        return $this->json(true, 'ok', [
            'methods' => $methods,
            'lead' => $lead,
            'riceFacts' => array_map(fn ($m) => array_filter(['age' => $m['seedlingAge'] ?? null, 'rate' => $m['seedRate'] ?? null, 'spacing' => $m['spacing'] ?? null, 'stand' => $m['plantStand'] ?? null]), $rice),
            'groups' => array_map(fn ($k) => LP::groupFor($k), array_combine(array_keys(FieldCatalogue::crops()), array_keys(FieldCatalogue::crops()))),
            'soil' => self::SOIL_Q,
            'history' => self::HISTORY_Q,
            'equipment' => LP::EQUIPMENT,
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
        $prep = LP::seedPrep($crop, $method, trim((string) $request->query('variety', '')), (string) $request->query('type', ''));

        return $this->json(true, 'ok', ['prep' => $prep]);
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
            $ha = match ((string) $r->lotSizeUnit) {
                'sqm' => $size / 10000,
                'acre' => $size * 0.4047,
                default => $size,
            };
            $seasons[$r->scheduleId] ??= ['id' => (int) $r->scheduleId, 'title' => (string) $r->scheduleTitle, 'lots' => []];
            $seasons[$r->scheduleId]['lots'][] = [
                'id' => (int) $r->id,
                'name' => (string) $r->lotName,
                'ha' => round($ha, 3),
                'size' => $size ? rtrim(rtrim(number_format($size, 2), '0'), '.') . ' ' . ($r->lotSizeUnit === 'hectare' || ! $r->lotSizeUnit ? 'ha' : $r->lotSizeUnit) : null,
                'crop' => $r->crop,
                'cropLabel' => $r->crop ? CropCatalog::label($r->crop) : null,
                'planting' => $r->transplantDate ?: $r->dayZeroDate,
                'province' => $r->locProvince, 'town' => $r->locTown,
                'lat' => $r->pinLat !== null ? (float) $r->pinLat : null, 'lng' => $r->pinLng !== null ? (float) $r->pinLng : null,
            ];
        }

        return $this->json(true, 'ok', ['seasons' => array_values($seasons)]);
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
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($this->title($case), 0, 190),
            'params' => json_encode($case), 'report' => json_encode(new \stdClass), 'credits' => 0,
            'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $case);
            exit;
        }
        @set_time_limit(300);
        $this->runJob($id, (int) $payer->id, $settings, $case);

        return $this->jobState($id);
    }

    /** The wizard's answers, checked and normalised, or the first problem in words. */
    private function caseFrom(Request $request): array|string
    {
        $crops = FieldCatalogue::crops();
        $v = Validator::make($request->all(), [
            'crop' => 'required|string|in:' . implode(',', array_keys($crops)),
            'method' => 'required|string|max:24',
            'variety' => 'nullable|string|max:120',
            'seedType' => 'nullable|in:inbred,hybrid',
            'planting' => 'required|date_format:Y-m-d',
            'lots' => 'required|array|min:1|max:12',
            'lots.*.name' => 'required|string|max:60',
            'lots.*.area' => 'required|numeric|min:0.01|max:500',
            'lots.*.planting' => 'nullable|date_format:Y-m-d',
            'lots.*.sourceLotId' => 'nullable|integer',
            'crew.workers' => 'required|integer|min:1|max:200',
            'crew.equipment' => 'required|in:' . implode(',', array_keys(LP::EQUIPMENT)),
            'crew.machines' => 'nullable|integer|min:1|max:10',
            'crew.daysPerWeek' => 'nullable|in:5,6,7',
            'crew.stagger' => 'nullable|boolean',
            'soil' => 'nullable|array',
            'history' => 'nullable|array',
            'location.province' => 'nullable|string|max:120',
            'location.town' => 'nullable|string|max:120',
            'location.place' => 'nullable|string|max:160',
            'location.lat' => 'nullable|numeric|between:-60,60',
            'location.lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
        ], [], ['lots.*.name' => 'lot name', 'lots.*.area' => 'lot area', 'crew.workers' => 'number of workers']);
        if ($v->fails()) {
            return $v->errors()->first();
        }
        $crop = (string) $request->input('crop');
        $method = (string) $request->input('method');
        if (! isset(LP::methodsFor($crop)[$method])) {
            return 'Pick how the crop goes in.';
        }
        $today = now('Asia/Manila')->startOfDay();
        $main = Carbon::parse((string) $request->input('planting'), 'Asia/Manila');
        if ($main->lt($today) || $main->gt($today->copy()->addDays(400))) {
            return 'Pick a planting day from today to a year ahead.';
        }
        $lots = [];
        foreach (array_values((array) $request->input('lots')) as $i => $l) {
            $p = ! empty($l['planting']) ? Carbon::parse($l['planting'], 'Asia/Manila') : $main;
            if ($p->lt($today) || $p->gt($today->copy()->addDays(400))) {
                return 'The planting day of ' . trim((string) $l['name']) . ' must be from today to a year ahead.';
            }
            $lots[] = ['name' => mb_substr(trim((string) $l['name']), 0, 60) ?: 'Lot ' . ($i + 1), 'area' => round((float) $l['area'], 3),
                'planting' => $p->toDateString(), 'own' => ! empty($l['planting']), 'sourceLotId' => isset($l['sourceLotId']) ? (int) $l['sourceLotId'] : null];
        }
        $pick = function (array $table, array $given, bool $rice): array {
            $out = [];
            foreach ($table as $k => [$q, $opts, $only]) {
                if (($only === 'rice' && ! $rice) || ($only === 'other' && $rice)) {
                    continue;
                }
                $v = (string) ($given[$k] ?? '');
                if (isset($opts[$v])) {
                    $out[$k] = $v;
                }
            }

            return $out;
        };
        $rice = str_starts_with($crop, 'rice');
        $soil = $pick(self::SOIL_Q, (array) $request->input('soil', []), $rice);
        $ph = $request->input('soil.ph');
        if (is_numeric($ph) && (float) $ph >= 3 && (float) $ph <= 10) {
            $soil['ph'] = round((float) $ph, 1);
        }
        $crew = (array) $request->input('crew');

        return [
            'crop' => $crop,
            'cropLabel' => $crops[$crop][1] ?? CropCatalog::label($crop),
            'method' => $method,
            'variety' => trim((string) $request->input('variety', '')),
            'seedType' => (string) $request->input('seedType', ''),
            'planting' => $main->toDateString(),
            'lots' => $lots,
            'crew' => [
                'workers' => (int) $crew['workers'],
                'equipment' => (string) $crew['equipment'],
                'machines' => max(1, (int) ($crew['machines'] ?? 1)),
                'daysPerWeek' => (int) ($crew['daysPerWeek'] ?? 6),
                'stagger' => filter_var($crew['stagger'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            'soil' => $soil,
            'history' => $pick(self::HISTORY_Q, (array) $request->input('history', []), $rice),
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

    /** The plan worked out, Anee asked, the plan worked out again with her steps, the charge. */
    private function runJob(int $id, int $payerId, AiSetting $settings, array $case): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('start');
            $plan = $this->plan($case);
            $weather = $this->weather($case, $plan);
            $result = $this->ai->askForJson($settings->forField('PH'), $this->prompt($case, $plan, $weather), 9000, fn (string $t) => $this->parse($t, $plan), ['onPhase' => $beat]);
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

    /** The arithmetic before Anee: steps, rules, seed preparation, crew. */
    private function plan(array $case, array $extra = []): array
    {
        $steps = LP::baseSteps($case['crop'], $case['method']);
        [$steps, $notes] = LP::applyAnswers($steps, $case);
        foreach ($extra as $s) {
            $steps = array_values(array_filter($steps, fn ($x) => $x['key'] !== $s['key']));
            $steps[] = $s;
        }
        usort($steps, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
        $prep = LP::seedPrep($case['crop'], $case['method'], $case['variety'], $case['seedType']);
        $sched = LP::schedule($steps, $case['lots'], $case['crew'], $case);

        return ['steps' => $steps, 'notes' => $notes, 'prep' => $prep, 'sched' => $sched, 'dayWord' => LP::dayWord($case['crop'], $case['method'])];
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

    /** What Anee is asked: the plan as it stands, the farm, the weather, and the shape of her answer. */
    private function prompt(array $case, array $plan, array $weather): string
    {
        $methods = LP::methodsFor($case['crop']);
        $method = lcfirst($methods[$case['method']][0] ?? $case['method']);
        $word = $plan['dayWord'];
        $say = function (array $table, array $given): string {
            $out = [];
            foreach ($given as $k => $v) {
                if (isset($table[$k][1][$v])) {
                    $out[] = rtrim($table[$k][0], '?') . ': ' . $table[$k][1][$v][0] . ($table[$k][1][$v][1] ? ' (' . $table[$k][1][$v][1] . ')' : '');
                }
            }

            return $out ? '- ' . implode("\n- ", $out) : '- nothing given';
        };
        $soil = $say(self::SOIL_Q, $case['soil']) . (isset($case['soil']['ph']) ? "\n- Tested pH: " . $case['soil']['ph'] : '');
        $hist = $say(self::HISTORY_Q, $case['history']);
        $crew = $case['crew'];
        $eq = LP::EQUIPMENT[$crew['equipment']][0] ?? $crew['equipment'];
        $lots = implode("\n", array_map(fn ($l, $i) => '- ' . $l['name'] . ': ' . $l['area'] . ' ha, ' . $word . ' ' . Carbon::parse($plan['sched']['plantings'][$i])->format('D M j, Y')
            . ($plan['sched']['shift'][$i] ? ' (moved ' . $plan['sched']['shift'][$i] . ($plan['sched']['shift'][$i] === 1 ? ' day' : ' days') . ' later than asked so the crew can finish)' : ''), $case['lots'], array_keys($case['lots'])));
        $steps = implode("\n", array_map(fn ($s) => '- ' . $s['key'] . ' [' . $s['from'] . ' to ' . $s['to'] . '] ' . $s['what'] . ' (' . (LP::TAGS[$s['tag']] ?? $s['tag']) . ')'
            . (! empty($s['rule']) ? ' Note: ' . $s['rule'] : ''), $plan['steps']));
        $first = $plan['sched']['perLot'][0] ?? ['steps' => []];
        $dated = implode("\n", array_map(fn ($r) => '- ' . $r['key'] . ': ' . (isset($r['start']) && $r['start'] ? $r['start'] . ' to ' . $r['end'] . ' (crew)' : $r['winFrom'] . ' to ' . $r['winTo'])
            . (isset($r['late']) ? ', LATE by ' . ($r['late'] ?? 'many') . ' days' : '') . (! empty($r['past']) ? ', window already passed' : ''), $first['steps']));
        $conflicts = $plan['sched']['conflicts'] ? implode('; ', array_map(fn ($c) => $case['lots'][$c['lot']]['name'] . ' ' . $c['key'] . ' late ' . ($c['late'] ?? 'many') . ' days' . ($c['why'] === 'past' ? ' (window already passed)' : ''), $plan['sched']['conflicts'])) : 'none';
        $notes = $plan['notes'] ? '- ' . implode("\n- ", array_map(fn ($n) => $n['text'], $plan['notes'])) : '- none';
        $prep = $plan['prep'];
        $seed = ! empty($prep['found']) && ! empty($prep['applies'])
            ? ($prep['brand'] ? $prep['brand'] . ' ' : '') . ($prep['type'] ?? '') . ' seed: soak ' . $prep['soakHours'] . ' h' . ($prep['refreshEveryHours'] ? ', change still water every ' . $prep['refreshEveryHours'] . ' h' : '') . ', incubate ' . $prep['incubateRange'][0] . ' to ' . $prep['incubateRange'][1] . ' h (' . ($prep['confidence'] === 'company' ? 'from the seed company or PhilRice' : 'general PhilRice hybrid practice, no brand instruction found') . ')'
            : ($prep['say'] ?? 'not applicable');
        $fc = $weather['forecast'] ? implode('; ', array_map(fn ($d) => $d['date'] . ' ' . $d['text'] . (isset($d['pop']) ? ' rain ' . $d['pop'] . '%' : '') . (isset($d['max']) ? ' ' . $d['min'] . ' to ' . $d['max'] . ' C' : ''), $weather['forecast'])) : 'not available';
        $h = $weather['history'];
        $climate = $h ? 'Over the whole preparation window to two weeks after ' . $word . ', the last ' . count($h['years']) . ' years averaged ' . round($h['avg']['rain']) . ' mm of rain (driest year ' . min(array_column($h['years'], 'rain')) . ' mm, wettest ' . max(array_column($h['years'], 'rain')) . ' mm), ' . $h['avg']['tmax'] . ' C average highs.' : 'not available';
        $pw = $weather['plowWindow'];
        $plowClimate = $pw ? 'The 3 weeks from the first plowing averaged ' . round($pw['avg']['rain']) . ' mm of rain (range ' . min(array_column($pw['years'], 'rain')) . ' to ' . max(array_column($pw['years'], 'rain')) . ' mm).' : '';
        $pk = $weather['plantWeek'];
        $plantClimate = $pk ? 'The two weeks around ' . $word . ' averaged ' . round($pk['avg']['rain']) . ' mm of rain.' : '';
        $enso = $weather['ensoText'] ?: 'not available';
        $place = $weather['place']['label'] ?? (trim(($case['location']['town'] ?? '') . ', ' . ($case['location']['province'] ?? ''), ', ') ?: 'not given');
        $variety = $case['variety'] !== '' ? $case['variety'] : 'not chosen yet';
        $today = now('Asia/Manila')->format('D M j, Y');
        $lotNames = implode(', ', array_map(fn ($l) => '"' . $l['name'] . '"', $case['lots']));
        $farmerNotes = $case['notes'] !== '' ? $case['notes'] : 'none';
        $keys = implode(', ', array_column($plan['steps'], 'key'));

        return <<<PROMPT
You are Anee, the smart farm technician of anee.io, planning the LAND PREPARATION of a Filipino farmer's lots. The app has already worked out the base plan: for rice, PhilRice practice (the PalayCheck System); for other crops, DA and extension practice; plus rules from the farmer's answers and a crew calendar. Your job: read it against the soil, the field's history, the weather forecast, the ENSO outlook and the area's climate history, and ADD your judgement: your own extra steps where they are needed, notes on the steps that need them, the risks, and a plain analysis of why. Do NOT rewrite or remove the base steps, and do NOT compute dates: give day offsets; the app does the dates.

TODAY: {$today}
CROP: {$case['cropLabel']}, {$method}. Variety: {$variety}. Day 0 is the {$word} day; negative days come before it.
PLACE: {$place}
LOTS:
{$lots}
CREW: {$crew['workers']} workers, {$eq}, {$crew['machines']} machine(s), {$crew['daysPerWeek']} work days a week.
SOIL AND WATER (the farmer's own observations):
{$soil}
FIELD HISTORY:
{$hist}
FARMER'S NOTES: {$farmerNotes}
SEED PREPARATION: {$seed}

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
- Add only steps that this farm needs because of something above (soil, history, weather, ENSO, crew, seed or water). At most 6. Each must say why in one sentence that names the fact. Never repeat a step that is already in the plan (keys: {$keys}).
- Offsets are days relative to the {$word} day (negative before it), from -120 to 30. Keep them realistic around the plan above.
- crew: "machine" or "manual" when the crew must do it (give effort as machine days or person days per hectare), else "none".
- lots: [] for every lot, or the names of the lots it applies to ({$lotNames}).
- Weather: say what the forecast and the history mean for plowing, soaking, puddling and the {$word} day (rain that delays plowing, a dry spell that leaves no water for soaking, a typhoon month). ENSO: say what the current outlook means for water during preparation. If the forecast does not reach the preparation window, say so and lean on the history.
- Crew: if there are conflicts, say plainly what to do (hire more hands, a second machine, or stagger the lots), with numbers.
- Pesticides and herbicides: name active ingredients only, never brands and never rates. Name no app as a source. PhilRice, IRRI and DA may be named.
- Plain, short words a farmer reads easily. No dashes between words, no semicolons, no markdown, no emoji. English with the odd Tagalog farm word where natural.

Return ONLY one JSON object, no fences:
{"summary":"3 to 4 sentences","analysis":[{"topic":"weather|enso|soil|history|crew|seed|water","title":"short","text":"2 to 3 sentences"}],"additions":[{"key":"short_snake_case","from":-20,"to":-18,"what":"short title","how":"what to do, 1 to 3 sentences","why":"one sentence naming the fact","reason":"weather|enso|soil|history|crew|seed|water","crew":"none|manual|machine","effort":0,"lots":[]}],"stepNotes":[{"key":"an existing key","note":"one sentence"}],"risks":[{"title":"short","level":"low|medium|high","text":"one or two sentences","when":"when to watch"}],"weather":"2 sentences on the weather for this preparation","crew":"1 to 2 sentences on the crew","confidence":"low|moderate|high","dataGaps":["what would sharpen this plan"]}
analysis: 3 to 6 items. stepNotes: at most 8. risks: at most 5. dataGaps: at most 3.
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
        $clean = fn ($v, int $max = 600) => is_string($v) ? mb_substr(trim(preg_replace(['/:[a-z0-9_-]+:/i', '/\s+[\x{2013}\x{2014}]\s+|\s+-\s+/u', '/;\s*/', '/\s{2,}/'], ['', ', ', ', ', ' '], strip_tags($v))), 0, $max) : '';
        $topics = ['weather', 'enso', 'soil', 'history', 'crew', 'seed', 'water'];
        $have = array_column($plan['steps'], 'key');
        $out = ['summary' => $clean($j['summary'], 900), 'analysis' => [], 'additions' => [], 'stepNotes' => [], 'risks' => [],
            'weather' => $clean($j['weather'] ?? '', 600), 'crew' => $clean($j['crew'] ?? '', 500),
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
        // Anee's notes ride on the steps they are about.
        foreach ($final['steps'] as &$s) {
            foreach ($anee['stepNotes'] as $n) {
                if ($n['key'] === $s['key']) {
                    $s['note'] = trim(($s['note'] ?? '') . ' ' . $n['note']);
                }
            }
        }
        unset($s);
        // The seed preparation, dated and timed for every lot from its own sowing day.
        $timeline = [];
        foreach ($final['sched']['perLot'] as $pl) {
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
            foreach ($case['lots'] as $i => $l) {
                $amounts[] = ['lot' => $i, 'lo' => round($prep['seedKgPerHa'][0] * $l['area'], 1), 'hi' => round($prep['seedKgPerHa'][1] * $l['area'], 1)];
            }
        }
        $methods = LP::methodsFor($case['crop']);
        $h = $weather['history'];

        return [
            'v' => 1,
            'at' => now('Asia/Manila')->format('M j, Y g:i A'),
            'crop' => ['key' => $case['crop'], 'label' => $case['cropLabel'], 'icon' => FieldCatalogue::crops()[$case['crop']][2] ?? '🌱', 'local' => FieldCatalogue::crops()[$case['crop']][0] ?? $case['cropLabel']],
            'method' => ['key' => $case['method'], 'label' => $methods[$case['method']][0] ?? $case['method']],
            'dayWord' => $final['dayWord'],
            'variety' => $case['variety'],
            'lots' => array_map(fn ($l, $i) => ['name' => $l['name'], 'area' => $l['area'], 'asked' => $l['planting'], 'planting' => $final['sched']['plantings'][$i], 'shift' => $final['sched']['shift'][$i]], $case['lots'], array_keys($case['lots'])),
            'crew' => $case['crew'] + ['equipmentLabel' => LP::EQUIPMENT[$case['crew']['equipment']][0] ?? ''],
            'steps' => $final['steps'],
            'perLot' => $final['sched']['perLot'],
            'calendar' => $final['sched']['calendar'],
            'conflicts' => $final['sched']['conflicts'],
            'plantDays' => $final['sched']['plantDays'],
            'rules' => $final['notes'],
            'seed' => $prep + ['timeline' => $timeline, 'amounts' => $amounts],
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

        return $beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(20));
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
