<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\EnsoOutlook;
use App\Support\NpkCrops;
use App\Support\SoilConditions;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * NPK Plus (2026-10-07): a fertilizer calculator, and Anee's reading of it.
 *
 * The calculator is free and has no AI in it. The farmer picks the crop and
 * the area, taps the fertilizers they plan to use (from the predefined shelf
 * in as_npk_products, or their own product typed in from its label), and
 * optionally a soil test. The page adds up every nutrient as the element and
 * as the oxide (N, P2O5/P, K2O/K, CaO/Ca, MgO/Mg, S, and the micronutrients
 * only when some product carries them), per hectare and for the whole area,
 * and sets it against what the crop takes up per ton of harvest (NpkCrops)
 * to say what yield it can feed, with the honest caveats.
 *
 * "Analyze further with Anee" (AiPrices 'npk') asks for the place, the soil,
 * the water and the timing, and Anee reads the plan against them.
 */
class NpkPlusController extends Controller
{
    public const KIND = 'npk';

    public const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'];

    public const CATEGORIES = [
        'nitrogen' => 'Nitrogen', 'phosphate' => 'Phosphate', 'potash' => 'Potash', 'complete' => 'Complete and blends',
        'foliar' => 'Water soluble and foliar', 'secondary' => 'Calcium, magnesium, sulfur, lime', 'micro' => 'Micronutrients',
        'organic' => 'Organic', 'bio' => 'Biofertilizers (inoculants)', 'mine' => 'My products',
    ];

    public const WATER = ['irrigated' => 'Irrigated', 'partial' => 'Partly irrigated', 'rainfed' => 'Rainfed only'];

    public const TIMING = [
        'basal' => 'All at planting (basal)',
        'split2' => 'Split in two',
        'split3' => 'Split in three or more',
        'unsure' => 'Not decided yet',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    public function page()
    {
        return view('npk-plus.index');
    }

    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable() && Tier::farmCan('aiAnalyses');
        $products = DB::table('as_npk_products')->where('deleteStatus', 1)
            ->where(fn ($q) => $q->whereNull('userId')->orWhere('userId', Auth::id()))
            ->orderByRaw('userId is null desc')->orderBy('sortOrder')->orderBy('id')->get();

        return $this->json(true, 'ok', [
            'products' => $products->map(fn ($p) => $this->productRow($p))->values(),
            'categories' => self::CATEGORIES,
            'crops' => collect(NpkCrops::TABLE)->map(fn ($c, $k) => ['key' => $k, 'label' => CropCatalog::label($k), 'icon' => CropCatalog::CROPS[$k]['icon'] ?? '🌱',
                'group' => CropCatalog::CROPS[$k]['group'] ?? 'Other crops', 'maturity' => CropCatalog::CROPS[$k]['maturity'] ?? null, 'perennial' => CropCatalog::isPerennial($k)] + $c)->values(),
            'soilConditions' => SoilConditions::OPTIONS,
            'model' => \App\Support\NpkModel::forClient(),
            'water' => self::WATER,
            'timing' => self::TIMING,
            'quote' => AiPrices::of(self::KIND),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null : (! Tier::farmCan('aiAnalyses') ? 'Anee\'s reading comes with ' . Tier::withPlan(Tier::farmUnlocksAt('aiAnalyses')) . '.' : Tier::aneeNeeds($payer)),
        ]);
    }

    private function productRow(object $p): array
    {
        $out = ['id' => (int) $p->id, 'slug' => $p->slug, 'name' => $p->name, 'local' => $p->local, 'category' => $p->userId ? 'mine' : $p->category,
            'form' => $p->form, 'unit' => $p->unit, 'bagKg' => $p->bagKg !== null ? (float) $p->bagKg : null, 'density' => $p->densityKgL !== null ? (float) $p->densityKgL : null,
            'note' => $p->note, 'own' => (bool) $p->userId, 'estimate' => json_decode((string) $p->estimate, true) ?: null, 'pct' => []];
        foreach (self::NUTRIENTS as $n) {
            if ((float) $p->{$n} > 0) {
                $out['pct'][$n] = (float) $p->{$n};
            }
        }

        return $out;
    }

    /**
     * The season the crop will grow through (2026-10-08): the field found
     * from its town and province, the same weeks in each of the last ten
     * years from the Open-Meteo weather history (rain, sunshine, the water
     * the air pulls from a crop, heat), and the ENSO state now. The page
     * turns them into the sun, water and temperature planks of the barrel.
     */
    public function season(Request $request)
    {
        $v = Validator::make($request->all(), [
            'town' => 'nullable|string|max:120', 'province' => 'nullable|string|max:120', 'lat' => 'nullable|numeric|between:-60,60', 'lng' => 'nullable|numeric|between:-180,180',
            'from' => 'nullable|date', 'days' => 'nullable|integer|min:20|max:400',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $label = trim(implode(', ', array_filter([$request->input('town'), $request->input('province')])));
        if ($lat === null || $lng === null) {
            if ($label === '') {
                return $this->json(false, 'Say where the field is.', [], 422);
            }
            $hit = \App\Support\PlaceSearch::find($label)[0] ?? (\App\Support\PlaceSearch::find((string) $request->input('province'))[0] ?? null);
            if (! $hit) {
                return $this->json(false, 'That place could not be found on the map.', [], 422);
            }
            $lat = (float) $hit['lat'];
            $lng = (float) $hit['lng'];
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        $from = $request->input('from') ? \Illuminate\Support\Carbon::parse($request->input('from')) : now('Asia/Manila')->addDays(14);
        $days = (int) ($request->input('days') ?: 110);

        // From the climate shelf (App\Support\ClimateStore), collected by the
        // scheduler: nothing outside is asked on a calculation. A place nobody
        // has asked about before is filled once and kept.
        $hist = \App\Support\ClimateStore::season($lat, $lng, $from, $days, $label ?: null);
        $years = $hist['years'] ?? [];
        $avg = $hist['avg'] ?? null;
        $months = [];
        for ($d = 0; $d < $days; $d += 7) {
            $months[(int) $from->copy()->addDays($d)->format('n')] = true;
        }

        return $this->json(true, 'ok', [
            'place' => ['label' => $label ?: round($lat, 3) . ', ' . round($lng, 3), 'lat' => round($lat, 4), 'lng' => round($lng, 4)],
            'window' => ['from' => $from->toDateString(), 'days' => $days, 'months' => array_keys($months)],
            'years' => $years, 'avg' => $avg, 'enso' => \App\Support\ClimateStore::enso(), 'through' => $hist['through'] ?? null,
            'monthsAhead' => (int) max(0, now('Asia/Manila')->startOfDay()->diffInMonths($from->copy()->startOfDay(), false)),
        ]);
    }

    /** A farmer's own product, typed in from its label. */
    public function storeProduct(Request $request)
    {
        $rules = ['name' => 'required|string|max:120', 'form' => 'required|in:granular,liquid,powder', 'density' => 'nullable|numeric|min:0.5|max:2.5'];
        foreach (self::NUTRIENTS as $n) {
            $rules[$n] = 'nullable|numeric|min:0|max:100';
        }
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $sum = 0;
        $row = ['userId' => Auth::id(), 'slug' => null, 'name' => trim(strip_tags((string) $request->input('name'))), 'local' => null, 'category' => 'mine',
            'form' => $request->input('form'), 'unit' => $request->input('form') === 'liquid' ? 'L' : 'kg', 'bagKg' => $request->input('form') === 'liquid' ? null : 50,
            'densityKgL' => $request->input('form') === 'liquid' ? (float) ($request->input('density') ?: 1) : null, 'note' => null, 'sortOrder' => 0, 'deleteStatus' => 1,
            'created_at' => now(), 'updated_at' => now()];
        foreach (self::NUTRIENTS as $n) {
            $row[$n] = round((float) $request->input($n, 0), 3);
            $sum += $row[$n];
        }
        if ($sum <= 0) {
            return $this->json(false, 'Type at least one nutrient from the label.', [], 422);
        }
        if ($sum > 100) {
            return $this->json(false, 'The percentages add up to more than 100. Check the label.', [], 422);
        }
        $id = DB::table('as_npk_products')->insertGetId($row);

        return $this->json(true, 'Saved to My products.', ['product' => $this->productRow(DB::table('as_npk_products')->find($id))]);
    }

    public function destroyProduct(int $id)
    {
        DB::table('as_npk_products')->where('id', $id)->where('userId', Auth::id())->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Removed.');
    }

    /* ------------------------------------------------------- calculations */

    public function save(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id' => 'nullable|integer', 'title' => 'nullable|string|max:190', 'crop' => 'nullable|string|max:40', 'areaHa' => 'required|numeric|min:0.0001|max:100000',
            'targetYield' => 'nullable|numeric|min:0|max:1000', 'lines' => 'required|array|min:1|max:60', 'soil' => 'nullable|array', 'result' => 'nullable|array',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $crop = $request->input('crop');
        $title = trim((string) $request->input('title')) ?: (($crop ? str_replace(' — ', ', ', CropCatalog::label($crop)) : 'Fertilizer plan') . ' · ' . rtrim(rtrim(number_format((float) $request->input('areaHa'), 2), '0'), '.') . ' ha · ' . now('Asia/Manila')->format('M j, Y'));
        $row = ['title' => mb_substr($title, 0, 190), 'crop' => $crop, 'areaHa' => (float) $request->input('areaHa'), 'targetYield' => $request->input('targetYield'),
            'lines' => json_encode($request->input('lines')), 'soil' => json_encode($request->input('soil')), 'result' => json_encode($request->input('result')), 'updated_at' => now()];
        $id = (int) $request->input('id');
        if ($id && DB::table('as_npk_calcs')->where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->exists()) {
            DB::table('as_npk_calcs')->where('id', $id)->update($row);
        } else {
            $id = DB::table('as_npk_calcs')->insertGetId($row + ['userId' => Auth::id(), 'deleteStatus' => 1, 'created_at' => now()]);
        }

        return $this->json(true, 'Saved.', ['id' => $id, 'title' => $row['title']]);
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $rows = DB::table('as_npk_calcs')->where('userId', Auth::id())->where('deleteStatus', 1)
            ->when($q !== '', fn ($w) => $w->where('title', 'like', '%' . $q . '%'))
            ->orderByDesc('updated_at')->skip(($page - 1) * 20)->take(21)->get(['id', 'title', 'crop', 'areaHa', 'result', 'analysisId', 'updated_at']);

        return $this->json(true, 'ok', ['hasMore' => $rows->count() > 20, 'rows' => $rows->take(20)->map(fn ($r) => [
            'id' => $r->id, 'title' => $r->title, 'npk' => (json_decode((string) $r->result, true) ?: [])['npkPerHa'] ?? null, 'analyzed' => (bool) $r->analysisId,
            'at' => \Illuminate\Support\Carbon::parse($r->updated_at)->timezone('Asia/Manila')->format('M j, Y'),
        ])->values()]);
    }

    public function one(int $id)
    {
        $r = DB::table('as_npk_calcs')->where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That calculation is gone.', [], 404);
        }
        $analysis = $r->analysisId ? DB::table('as_plant_analyses')->where('id', $r->analysisId)->where('userId', Auth::id())->where('status', 'ready')->first() : null;

        return $this->json(true, 'ok', ['id' => $r->id, 'title' => $r->title, 'crop' => $r->crop, 'areaHa' => (float) $r->areaHa, 'targetYield' => $r->targetYield !== null ? (float) $r->targetYield : null,
            'lines' => json_decode((string) $r->lines, true), 'soil' => json_decode((string) $r->soil, true), 'result' => json_decode((string) $r->result, true),
            'analysis' => $analysis ? ['id' => $analysis->id, 'report' => json_decode((string) $analysis->report, true), 'params' => json_decode((string) $analysis->params, true)] : null]);
    }

    public function destroy(int $id)
    {
        DB::table('as_npk_calcs')->where('id', $id)->where('userId', Auth::id())->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Deleted.');
    }

    /* --------------------------------------------------------------- Anee */

    public function analyze(Request $request)
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Anee\'s reading of a fertilizer plan comes with {plan}, and with every plan above it.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'The reading'), [], 403);
        }
        $v = Validator::make($request->all(), [
            'calcId' => 'required|integer', 'location' => 'required|string|max:160', 'soilConditions' => 'nullable|array|max:7',
            'soilConditions.*' => 'string|in:' . implode(',', array_keys(SoilConditions::OPTIONS)), 'phValue' => 'nullable|numeric|min:2|max:12',
            'water' => 'required|in:' . implode(',', array_keys(self::WATER)), 'plantingDate' => 'nullable|date', 'timing' => 'required|in:' . implode(',', array_keys(self::TIMING)),
            'variety' => 'nullable|string|max:80', 'notes' => 'nullable|string|max:800',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $calc = DB::table('as_npk_calcs')->where('id', (int) $request->input('calcId'))->where('userId', Auth::id())->where('deleteStatus', 1)->first();
        if (! $calc) {
            return $this->json(false, 'Save the calculation first.', [], 404);
        }
        $price = (float) AiPrices::of(self::KIND);
        if ($this->credits->balance($payer->id) < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . ceil($price) . ' credits for this reading and have ' . number_format((int) floor($this->credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(12))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }
        $p = [
            'calcId' => (int) $calc->id, 'location' => trim((string) $request->input('location')), 'soilConditions' => SoilConditions::normalize($request->input('soilConditions', [])),
            'phValue' => SoilConditions::phValue($request->input('phValue')), 'water' => (string) $request->input('water'), 'plantingDate' => $request->input('plantingDate'),
            'timing' => (string) $request->input('timing'), 'variety' => trim((string) $request->input('variety', '')), 'notes' => trim((string) $request->input('notes', '')),
        ];
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr('NPK Plus · ' . $calc->title, 0, 190), 'params' => json_encode($p),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $p, $calc);
            exit;
        }
        @set_time_limit(600);
        $this->runJob($id, (int) $payer->id, $settings, $p, $calc);

        return $this->jobState($id);
    }

    private function runJob(int $id, int $payerId, AiSetting $settings, array $p, object $calc): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $settings = $settings->forField('PH');
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p, $calc), $this->prompt($p, $calc), 7000, fn (string $t) => $this->parse($t), $beat);
            $anee = $result['data'];
            if ($anee === null) {
                Log::warning('npk: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The reading could not be read. Nothing was charged. Please try again.');
            }
            $report = ['format' => 1, 'anee' => $anee, 'webSources' => (array) ($result['sources'] ?? []), 'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            $charged = (float) AiPrices::of(self::KIND);
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged, mb_substr('NPK Plus reading: ' . $calc->title . $note, 0, 250));
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report), 'credits' => round($charged, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
            DB::table('as_npk_calcs')->where('id', $calc->id)->update(['analysisId' => $id, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    /** The calculation, said plainly for a prompt. */
    public static function planWords(object $calc): string
    {
        $res = json_decode((string) $calc->result, true) ?: [];
        $lines = collect(json_decode((string) $calc->lines, true) ?: [])->map(fn ($l) => '- ' . ($l['name'] ?? 'product') . (! empty($l['grade']) ? ' (' . $l['grade'] . ')' : '') . ': ' . ($l['amount'] ?? '?') . ' ' . ($l['unit'] ?? 'kg')
            . ' = ' . ($l['kg'] ?? '?') . ' kg; carries ' . collect($l['pct'] ?? [])->map(fn ($v, $k) => $k . ' ' . $v . '%')->implode(', ')
            . (! empty($l['estimate']) ? '; biofertilizer estimate ' . collect($l['estimate'])->map(fn ($v, $k) => $k . ' ' . $v . ' kg per dose')->implode(', ') : ''))->implode("\n");
        $soil = json_decode((string) $calc->soil, true) ?: [];
        // The crop's setup from the form (2026-10-07): the variety, what is
        // planted and how much, and the yield the variety can give.
        $set = (array) ($res['setup'] ?? []);
        $seed = (array) ($set['seed'] ?? []);
        $pot = (array) ($set['potential'] ?? []);

        return 'Crop: ' . ($calc->crop ? CropCatalog::label($calc->crop) : 'not set') . '; area ' . (float) $calc->areaHa . ' ha'
            . (! empty($set['variety']) ? '; variety ' . mb_substr((string) $set['variety'], 0, 80) : '')
            . (! empty($pot['value'])
                ? '; potential yield of the variety ' . (float) $pot['value'] . ' ' . mb_substr((string) ($pot['unit'] ?? 't/ha'), 0, 30)
                    . (! empty($pot['tPerHa']) && ($pot['unit'] ?? '') !== 't/ha' ? ' (' . round((float) $pot['tPerHa'], 2) . ' t/ha)' : '')
                : ($calc->targetYield ? '; target yield ' . (float) $calc->targetYield . ' t/ha' : ''))
            . (! empty($seed['amount'])
                ? '; planted with ' . (float) $seed['amount'] . ' ' . mb_substr((string) ($seed['unit'] ?? ''), 0, 30) . ' for the whole area'
                    . (! empty($seed['perHa']) ? ' (' . round((float) $seed['perHa'], 1) . ' per hectare)' : '')
                : '') . "\n"
            . "Fertilizers planned (for the whole area):\n" . $lines . "\n"
            . 'Totals per hectare (elemental unless named as oxide): ' . json_encode($res['perHa'] ?? []) . "\n"
            . 'N-P2O5-K2O per hectare: ' . ($res['npkPerHa'] ?? '?') . "\n"
            . ($soil ? 'Soil test: ' . json_encode($soil) . "\n" : 'No soil test given.' . "\n")
            . (! empty($set['texture']) && $set['texture'] !== 'unsure' ? 'Soil type: ' . $set['texture'] . "\n" : '')
            . (! empty($set['conditions']) ? 'Soil conditions the farmer picked: ' . implode(', ', array_map('strval', (array) $set['conditions'])) . "\n" : '')
            . (! empty($res['goal']) ? 'Yield goal the calculator counted for: ' . json_encode($res['goal']) . "\n" : '')
            . (! empty($res['needs']) ? 'The calculator\'s need model, kg per ha for that goal on this soil (the official guide rate sized to the goal and adjusted for the soil; plan is what the plan gives): ' . json_encode($res['needs']) . "\n" : '')
            . (! empty($res['micros']) ? 'The calculator\'s secondary, micro and beneficial element needs, kg of element per ha (level none, watch, likely, or test from a soil test): ' . json_encode($res['micros']) . "\n" : '')
            . (isset($res['reach']) && $res['reach'] !== null ? 'Liebig reading: the scarcest nutrient lets the crop reach about ' . (int) $res['reach'] . "% of the goal by the calculator's count.\n" : '')
            . (! empty($res['support']) ? 'The calculator\'s estimate of the yield the fertilizer alone can feed, t/ha per nutrient (soil supply not counted): ' . json_encode($res['support']) . "\n" : '');
    }

    private function researchPrompt(array $p, object $calc): string
    {
        return "Research for a fertilizer plan check in the Philippines. Use web search and write plain notes (not JSON).\n"
            . 'Place: ' . $p['location'] . "\nCrop: " . ($calc->crop ? CropCatalog::label($calc->crop) : 'not set') . ($p['variety'] ? ' (' . $p['variety'] . ')' : '') . "\n"
            . 'Planting date: ' . ($p['plantingDate'] ?: 'not given') . "\n\n"
            . "Find: 1) the DA, BSWM or PhilRice fertilizer recommendation for this crop in this province or region, and its timing by growth stage; "
            . "2) the soils of this area (pH, sodic, saline, acid sulfate, deficiencies such as zinc) from BSWM soil surveys or studies; "
            . "3) the rainfall pattern around the planting date here and what ENSO forecasts mean for it (leaching, flooding, drought, heat); "
            . "4) for any biofertilizer in the plan, what field trials show it can replace. Cite each source.";
    }

    private function prompt(array $p, object $calc): string
    {
        return "You are Anee, a smart farm technician for Filipino farmers. Judge this fertilizer plan honestly and practically. Return ONE JSON object, nothing else.\n\n"
            . self::planWords($calc) . "\n"
            . 'Place: ' . $p['location'] . "\n"
            . 'Soil as the farmer knows it: ' . (SoilConditions::words($p['soilConditions'], $p['phValue']) ?: 'not stated') . "\n"
            . (SoilConditions::guidance($p['soilConditions']) ? SoilConditions::guidance($p['soilConditions']) . "\n" : '')
            . 'Water: ' . (self::WATER[$p['water']] ?? $p['water']) . "\n"
            . 'Planting date: ' . ($p['plantingDate'] ?: 'not given') . '; how the farmer means to apply it: ' . (self::TIMING[$p['timing']] ?? $p['timing']) . "\n"
            . ($p['variety'] ? 'Variety: ' . $p['variety'] . "\n" : '') . ($p['notes'] ? 'Notes: ' . $p['notes'] . "\n" : '')
            . 'ENSO: ' . (EnsoOutlook::forPrompt() ?: 'not available') . "\n\n"
            . "RULES: Weigh the plan against the crop's need and the soil, the water, the weather of that season and ENSO. Say if it is enough, short or too much for each nutrient, which product to cut, add or split, and WHEN each product should go on by growth stage. Name products by their grade or active nutrient, never a brand. Never invent a number that is not in the plan or the research notes; say what you cannot know. Remember: the yield still depends on the weather, the variety and the soil.\n\n"
            . 'Return exactly: {"verdict":"good|close|needs changes|unbalanced","headline":"one line","summary":"3 plain sentences",'
            . '"nutrients":[{"nutrient":"N|P2O5|K2O|S|Zn|...","status":"short|right|over","comment":""}],'
            . '"timing":[{"product":"","when":"growth stage and about which day","how":"","why":""}],'
            . '"changes":[{"change":"","why":""}],"soil":"what this soil does to the plan","weather":"what the season and ENSO mean for losses and timing",'
            . '"biofertilizers":"what the inoculants can and cannot replace (empty if none)","yield":"the yield this plan can realistically support and why",'
            . '"warnings":[""],"confidence":"low|medium|high"}';
    }

    private function parse(string $text): ?array
    {
        $t = preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? $text;
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $d = json_decode(substr($t, $a, $b - $a + 1), true);

        return is_array($d) && isset($d['verdict']) ? $d : null;
    }

    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That reading is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            $beatAt = \Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(20))) {
                $why = 'The reading was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1), 'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The reading failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Ready.', ['status' => 'ready', 'id' => (int) $r->id, 'report' => json_decode($r->report, true), 'params' => json_decode($r->params, true),
            'charged' => (float) $r->credits, 'balance' => round($this->credits->balance($this->payer()->id), 2)]);
    }

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
