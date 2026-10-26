<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPricing;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\EnsoOutlook;
use App\Support\FieldProfile;
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
use Illuminate\Support\Str;

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
 * "Analyze further with Anee" (AiPricing 'npk') asks for the place, the soil,
 * the water and the timing, and Anee reads the plan against them.
 */
class NpkPlusController extends Controller
{
    public const KIND = 'npk';

    /** The most fertilizer lines a calculation holds: the one price the page shows is the engine's quote at this size. */
    private const PRICE_LINES = 60;

    // Cobalt (2026-10-20): a column of the shelf since cobalt sulfate joined it.
    public const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl', 'Co'];

    public const CATEGORIES = [
        'nitrogen' => 'Nitrogen', 'phosphate' => 'Phosphate', 'potash' => 'Potash', 'complete' => 'Complete and blends',
        'foliar' => 'Water soluble and foliar', 'secondary' => 'Calcium, magnesium, sulfur, lime', 'micro' => 'Micronutrients',
        'organic' => 'Organic', 'bio' => 'Biofertilizers (inoculants)', 'mine' => 'My products',
    ];

    public const WATER = ['irrigated' => 'Irrigated', 'pump' => 'Pump or well', 'partial' => 'Partly irrigated', 'rainfed' => 'Rainfed only'];

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
                'group' => CropCatalog::CROPS[$k]['group'] ?? 'Other crops', 'maturity' => CropCatalog::CROPS[$k]['maturity'] ?? null, 'perennial' => CropCatalog::isPerennial($k),
                // The crop's sources as shown under "How NPK Plus counted the need": never a government body, institute or university
                // (GovSource, 2026-10-19); the long 'about' notes are for us and are not sent.
                'sources' => \App\Support\GovSource::keep($c['sources'] ?? []),
                // Its usual spacing, for "By spacing" (2026-10-21, the owner); null where the seed calculator has none.
                'spacing' => \App\Support\PlantAmount::spacing($k),
                // Whether the seed tag's germination counts, by the crop's usual way in (PlantAmount::GERM): seed, seedlings or none.
                'germ' => \App\Support\PlantAmount::germ($k),
                // The amount's units by its usual way in, the usual first (PlantAmount::units).
                'units' => \App\Support\PlantAmount::units($k)] + array_diff_key($c, ['about' => 1]))->values(),
            'soilConditions' => SoilConditions::OPTIONS,
            'model' => \App\Support\NpkModel::forClient(),
            'water' => self::WATER,
            'timing' => self::TIMING,
            // Anee's reading only; the calculator is free. One price for any calculation
            // (App\Support\AiPricing): the engine's quote at the most lines a calculation holds.
            'quote' => AiPricing::credits(self::KIND, ['lines' => self::PRICE_LINES]),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null : (! Tier::farmCan('aiAnalyses') ? 'Anee\'s reading comes with ' . Tier::withPlan(Tier::farmUnlocksAt('aiAnalyses')) . '.' : Tier::aneeNeeds($payer)),
        ]);
    }

    private function productRow(object $p): array
    {
        $out = ['id' => (int) $p->id, 'slug' => $p->slug, 'name' => $p->name, 'local' => $p->local, 'category' => $p->userId ? 'mine' : $p->category,
            // A Philippine product someone has used: on the plan's list, not on the common shelf.
            'ph' => str_starts_with((string) $p->slug, 'ph-'),
            'form' => $p->form, 'unit' => $p->unit, 'bagKg' => $p->bagKg !== null ? (float) $p->bagKg : null, 'density' => $p->densityKgL !== null ? (float) $p->densityKgL : null,
            'note' => $p->note, 'own' => (bool) $p->userId, 'estimate' => json_decode((string) $p->estimate, true) ?: null, 'pct' => []];
        foreach (self::NUTRIENTS as $n) {
            if ((float) ($p->{$n} ?? 0) > 0) {
                $out['pct'][$n] = (float) $p->{$n};
            }
        }

        return $out;
    }

    /**
     * The season the crop will grow through (2026-10-08): the field found
     * from its town and province, the same weeks in each of the last twenty
     * years from the Open-Meteo weather history (rain, sunshine, the water
     * the air pulls from a crop, heat), and the ENSO state now. The page
     * turns them into the sun, water and temperature planks of the barrel.
     */
    public function season(Request $request)
    {
        $v = Validator::make($request->all(), [
            'town' => 'nullable|string|max:120', 'province' => 'nullable|string|max:120', 'lat' => 'nullable|numeric|between:-60,60', 'lng' => 'nullable|numeric|between:-180,180',
            'from' => 'nullable|date', 'days' => 'nullable|integer|min:20|max:400', 'crop' => 'nullable|string|max:40', 'barangay' => 'nullable|string|max:160',
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
        // The window as the Crop Protocol reads it too (NpkNeed::seasonWindow): its months and how far ahead it starts.
        $win = \App\Support\NpkNeed::seasonWindow($from, $days);

        return $this->json(true, 'ok', [
            'place' => ['label' => $label ?: round($lat, 3) . ', ' . round($lng, 3), 'lat' => round($lat, 4), 'lng' => round($lng, 4)],
            'window' => ['from' => $win['from'], 'days' => $win['days'], 'months' => $win['months']],
            'years' => $years, 'avg' => $avg, 'enso' => \App\Support\ClimateStore::enso(), 'through' => $hist['through'] ?? null,
            'monthsAhead' => $win['monthsAhead'],
            'area' => self::areaCard($request->input('province'), $request->input('town'), $request->input('barangay'), $request->input('crop')),
        ]);
    }

    /**
     * The area's typical soil for the soil step (the soil atlas, 2026-10-18):
     * what the page may offer to fill the answers the farmer left unknown,
     * in the same vocabulary the Crop Protocol fills (SoilAtlas::fill), so a
     * lot with no soil test reaches the same need in both. Null where the
     * atlas has nothing for the place.
     */
    private static function areaCard(?string $province, ?string $town, ?string $barangay, ?string $crop): ?array
    {
        $a = \App\Support\SoilAtlas::for($province, $town, $barangay, $crop);
        if (! $a || (! $a['tests'] && empty($a['texture']['typical']) && $a['phMid'] === null && empty($a['salinity']['surveyed']))) {
            return null;
        }
        $pl = $a['place'];

        return [
            'where' => trim(implode(', ', array_filter([$pl['level'] === 'barangay' ? $pl['barangay'] : null, $pl['town'], $pl['province']]))),
            'level' => $pl['level'], 'samples' => $a['samples'], 'year' => $a['year'],
            'tests' => (object) $a['tests'], 'texture' => $a['texture']['typical'] ?? null, 'ph' => $a['phMid'],
            'phBand' => $a['ph']['band'] ?? null, 'phRange' => $a['ph']['range'] ?? null,
            'salt' => $a['salinity']['risk'] ?? null, 'conditions' => $a['conditions'], 'zinc' => $a['zincArea'] ?? null,
            'lines' => array_values(array_filter(explode("\n", \App\Support\SoilAtlas::words($a, 'card')))),
        ];
    }

    /**
     * The Philippine products tab (2026-10-12): the hand checked ones, or every
     * registered product matching a search (App\Support\PhFertilizers).
     */
    public function phProducts(Request $request)
    {
        $q = Str::limit(trim((string) $request->query('q', '')), 80, '');
        $cat = (string) $request->query('cat', '');
        $cat = array_key_exists($cat, self::CATEGORIES) ? $cat : '';
        try {
            $rows = \App\Support\PhFertilizers::search($q, $cat, $q === '' ? 200 : 60);
            $total = DB::table('as_ph_fertilizers')->where('active', 1)->count();
            $asOf = DB::table('as_ph_fertilizers')->where('active', 1)->max('asOf');
        } catch (\Throwable $e) {
            return $this->json(true, 'ok', ['rows' => [], 'total' => 0, 'asOf' => null]);
        }

        return $this->json(true, 'ok', ['rows' => $rows, 'total' => $total, 'asOf' => $asOf]);
    }

    /** A Philippine product into the plan: its NPK Plus product, made the first time anyone uses it. */
    public function phUse(Request $request)
    {
        $p = \App\Support\PhFertilizers::product((int) $request->input('id'));
        if (! $p) {
            return $this->json(false, 'That product is no longer on the list.', [], 404);
        }

        return $this->json(true, 'ok', ['product' => $this->productRow($p)]);
    }

    /** A farmer's own product, typed in from its label. */
    public function storeProduct(Request $request)
    {
        $rules = ['name' => 'required|string|max:120', 'form' => 'required|in:granular,liquid,powder', 'density' => 'nullable|numeric|min:0.5|max:2.5',
            'pack' => 'nullable|numeric|min:0.001|max:100000', 'packUnit' => 'nullable|in:g,kg,mL,L'];
        foreach (self::NUTRIENTS as $n) {
            $rules[$n] = 'nullable|numeric|min:0|max:100';
        }
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $sum = 0;
        // The pack (2026-10-12): a solid's in kg (a bag of 50, a 500 g sachet), a
        // liquid's in liters (a 1 L bottle, a 250 mL one). A liquid's label
        // percent is grams per 100 mL, so its density stays 1 unless given.
        $liquid = $request->input('form') === 'liquid';
        $pack = (float) $request->input('pack', 0);
        $packIn = match ($request->input('packUnit')) { 'g', 'mL' => $pack / 1000, 'kg', 'L' => $pack, default => 0.0 };
        if ($packIn > 0 && ($liquid ? ! in_array($request->input('packUnit'), ['mL', 'L'], true) : ! in_array($request->input('packUnit'), ['g', 'kg'], true))) {
            return $this->json(false, $liquid ? 'A liquid comes in liters or milliliters.' : 'A solid comes in kilograms or grams.', [], 422);
        }
        $row = ['userId' => Auth::id(), 'slug' => null, 'name' => trim(strip_tags((string) $request->input('name'))), 'local' => null, 'category' => 'mine',
            'form' => $request->input('form'), 'unit' => $liquid ? 'L' : 'kg', 'bagKg' => $packIn > 0 ? round($packIn, 4) : ($liquid ? null : 50),
            'densityKgL' => $liquid ? (float) ($request->input('density') ?: 1) : null, 'note' => null, 'sortOrder' => 0, 'deleteStatus' => 1,
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
            // The field in detail (App\Support\FieldProfile, 2026-10-15), optional.
            'fieldProfile' => 'nullable|array', 'fieldProfileFrom' => 'nullable|integer',
            // The farm's lot it is for (App\Support\FarmLots, 2026-10-16), optional.
            'lotId' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $crop = $request->input('crop');
        $title = trim((string) $request->input('title')) ?: (($crop ? str_replace(' — ', ', ', CropCatalog::label($crop)) : 'Fertilizer plan') . ' · ' . rtrim(rtrim(number_format((float) $request->input('areaHa'), 2), '0'), '.') . ' ha · ' . now('Asia/Manila')->format('M j, Y'));
        // The field in detail rides in the calculation's setup, checked here, and is kept for the next analysis.
        $result = (array) $request->input('result', []);
        $fp = FieldProfile::fromInput($request->input('fieldProfile'), $crop ?: null);
        $result['setup'] = array_merge((array) ($result['setup'] ?? []), FieldProfile::forParams($fp, $crop ?: null), ['fieldProfileFrom' => $fp ? $request->input('fieldProfileFrom') : null]);
        // Inside Anee Auto the season keeps the profile itself (keepProfile false): no second copy here.
        // Nor for a farm lot: its answers are the lot's own, already kept in it.
        if ($fp && $request->boolean('keepProfile', true) && ! $request->filled('lotId')) {
            $set = (array) $result['setup'];
            $place = trim(implode(', ', array_filter([preg_replace('/\s*\(Capital\)\s*$/i', '', (string) ($set['town'] ?? '')), (string) ($set['prov'] ?? '')])));
            FieldProfile::keepFor(WorkerContext::effectiveOwnerId(), $fp, $place !== '' ? $place : 'My field', $crop ?: null, $request->input('fieldProfileFrom'));
        }
        $row = ['title' => mb_substr($title, 0, 190), 'crop' => $crop, 'areaHa' => (float) $request->input('areaHa'), 'targetYield' => $request->input('targetYield'),
            'lines' => json_encode($request->input('lines')), 'soil' => json_encode($request->input('soil')), 'result' => json_encode($result), 'updated_at' => now()];
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

        return $this->json(true, 'Moved to the Trash. Restore it from the Trash for 30 days.');
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
            // The field in detail (App\Support\FieldProfile), optional.
            'fieldProfile' => 'nullable|array', 'fieldProfileFrom' => 'nullable|integer',
            // The farm's lot it is for (App\Support\FarmLots, 2026-10-16), optional.
            'lotId' => 'nullable|integer', 'lotName' => 'nullable|string|max:120',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $calc = DB::table('as_npk_calcs')->where('id', (int) $request->input('calcId'))->where('userId', Auth::id())->where('deleteStatus', 1)->first();
        if (! $calc) {
            return $this->json(false, 'Save the calculation first.', [], 404);
        }
        /* The price the page showed is the price charged: one price for any
           calculation. A page that sends the quote for this calculation's
           own lines must match it. */
        $lines = count((array) (json_decode((string) $calc->lines, true) ?: []));
        $quote = AiPricing::quote(self::KIND, ['lines' => $lines]);
        $agreed = AiPricing::agreed($request, $quote, AiPricing::credits(self::KIND, ['lines' => self::PRICE_LINES]));
        if ($agreed === null) {
            return $this->json(false, 'The reading now costs ' . $quote['credits'] . ' credits. Please run it again.', ['quote' => $quote['credits']], 409);
        }
        $price = (float) $agreed;
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
            'lotId' => $request->filled('lotId') ? (int) $request->input('lotId') : null, 'lotName' => trim((string) $request->input('lotName', '')) ?: null,
        ] + FieldProfile::forParams(FieldProfile::fromInput($request->input('fieldProfile'), $calc->crop ?: null), $calc->crop ?: null);
        // The field in detail is kept for the next analysis, named after the place; a farm lot's answers are already its own.
        if (! $p['lotId']) {
            FieldProfile::keepFor((int) $payer->id, (array) ($p['fieldProfile'] ?? []), $p['location'], $calc->crop ?: null, $request->input('fieldProfileFrom'));
        }
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr('NPK Plus · ' . $calc->title, 0, 190), 'params' => json_encode($p),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $p, $calc, $price, $lines);
            exit;
        }
        @set_time_limit(600);
        $this->runJob($id, (int) $payer->id, $settings, $p, $calc, $price, $lines);

        return $this->jobState($id);
    }

    private function runJob(int $id, int $payerId, AiSetting $settings, array $p, object $calc, float $price, int $lines): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $settings = $settings->forField('PH');
            // The brief is the case's, not the field's: kept for the next reading of the same case (2026-10-18).
            $shelf = ['kind' => 'npk', 'scope' => implode(' · ', array_filter([$calc->crop ? CropCatalog::label($calc->crop) : null, $p['variety'] ?: null,
                \App\Support\ClimateRecord::placeKey((string) $p['location'], 'PH')['place'], $p['plantingDate'] ? substr((string) $p['plantingDate'], 0, 7) : null]))];
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p, $calc), $this->prompt($p, $calc), 7000, fn (string $t) => $this->parse($t), $beat, 3000, $shelf);
            $anee = $result['data'];
            if ($anee === null) {
                Log::warning('npk: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The reading could not be read. Nothing was charged. Please try again.');
            }
            // The calculator's verdict stands (2026-10-19), as the board's and the Builder's readings take theirs
            // (NutrientBook::overlay): the reading and the free sheet never disagree on short, right or too much.
            $anee['nutrients'] = self::overlayNeeds((array) ($anee['nutrients'] ?? []), (array) ((json_decode((string) $calc->result, true) ?: [])['needs'] ?? []));
            $report = ['format' => 1, 'anee' => $anee, 'webSources' => (array) ($result['sources'] ?? []), 'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            // The price agreed when the run began.
            $charged = $price;
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged, mb_substr('NPK Plus reading: ' . $calc->title . $note, 0, 250));
            AiPricing::audit(self::KIND, $result, $charged, ['lines' => $lines], $settings);
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report), 'credits' => round($charged, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
            DB::table('as_npk_calcs')->where('id', $calc->id)->update(['analysisId' => $id, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    /** The calculation, said plainly for a prompt. */
    /** A line's unit in words: "bag50" is "bags of 50 kg" (2026-10-12). */
    private static function unitWords(string $unit): string
    {
        if (preg_match('/^bag(\d+)$/', $unit, $m)) {
            return 'bags of ' . $m[1] . ' kg';
        }

        return ['bag' => 'bags', 'dose' => 'hectare doses', 'L' => 'liters'][$unit] ?? $unit;
    }

    /**
     * Anee's nutrient list with the calculator's own state for N, P2O5 and K2O
     * (short under 0.9 of the need, too much over 1.3, the page's rule): her
     * comment stays and her own status is kept as aneeStatus where it differed.
     */
    public static function overlayNeeds(array $list, array $needs): array
    {
        $alias = ['N' => 'N', 'NITROGEN' => 'N', 'P' => 'P2O5', 'P2O5' => 'P2O5', 'PHOSPHORUS' => 'P2O5', 'K' => 'K2O', 'K2O' => 'K2O', 'POTASSIUM' => 'K2O'];
        $say = ['short' => 'short', 'right' => 'right', 'over' => 'over'];
        foreach ($list as &$n) {
            if (! is_array($n)) {
                continue;
            }
            $k = $alias[strtoupper(trim((string) ($n['nutrient'] ?? '')))] ?? null;
            $state = $k ? ($say[(string) ($needs[$k]['state'] ?? '')] ?? null) : null;
            if ($state && ($n['status'] ?? null) !== $state) {
                $n['aneeStatus'] = $n['status'] ?? null;
                $n['status'] = $state;
            }
        }
        unset($n);

        return $list;
    }

    public static function planWords(object $calc): string
    {
        $res = json_decode((string) $calc->result, true) ?: [];
        $lines = collect(json_decode((string) $calc->lines, true) ?: [])->map(fn ($l) => '- ' . ($l['name'] ?? 'product') . (! empty($l['grade']) ? ' (' . $l['grade'] . ')' : '') . ': ' . ($l['amount'] ?? '?') . ' ' . self::unitWords((string) ($l['unit'] ?? 'kg'))
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
            . (($set['varietyPick']['source'] ?? '') === 'record' ? ' (from the variety records' . (! empty($set['varietyPick']['potential']) ? ', ' . (float) $set['varietyPick']['potential'] . ' t/ha ' . (($set['varietyPick']['potentialIs'] ?? '') === 'top' ? 'highest' : 'average') . ' trial yield' : '') . (($set['varietyPick']['potentialFrom'] ?? '') === 'own' ? '; the farmer set the potential themselves' : '') . ')'
                : (($set['varietyPick']['source'] ?? '') === 'custom' ? ' (typed by the farmer, not on record)' : ''))
            . (! empty($pot['value'])
                ? '; potential yield of the variety ' . (float) $pot['value'] . ' ' . mb_substr((string) ($pot['unit'] ?? 't/ha'), 0, 30)
                    . (! empty($pot['tPerHa']) && ($pot['unit'] ?? '') !== 't/ha' ? ' (' . round((float) $pot['tPerHa'], 2) . ' t/ha)' : '')
                : ($calc->targetYield ? '; target yield ' . (float) $calc->targetYield . ' t/ha' : ''))
            . (! empty($seed['amount'])
                ? (($seed['by'] ?? '') === 'spacing' && ! empty($seed['spacing']['row'])
                    // Planted by spacing (2026-10-21): the spacing and what the seed calculator counted for it.
                    ? '; planted by spacing: rows ' . (float) $seed['spacing']['row'] . ' cm apart, ' . (float) ($seed['spacing']['plant'] ?? 0) . ' cm between hills'
                        . (! empty($seed['spacing']['perHill']) ? ', ' . (int) $seed['spacing']['perHill'] . ' a hill' : '') . ' (' . mb_substr((string) ($seed['say'] ?? ''), 0, 600) . ')'
                    : '; planted with ' . (float) $seed['amount'] . ' ' . mb_substr((string) ($seed['unit'] ?? ''), 0, 30) . ' for the whole area'
                        . (! empty($seed['perHa']) ? ' (' . round((float) $seed['perHa'], 1) . ' per hectare)' : ''))
                    . (! empty($seed['germination']) ? ', at ' . round((float) $seed['germination']) . '% germination' : '')
                : '') . "\n"
            . \App\Support\NpkModel::yieldBasisWords($calc->crop) . "\n"
            . "Fertilizers planned (for the whole area):\n" . $lines . "\n"
            . 'Totals per hectare (elemental unless named as oxide): ' . json_encode($res['perHa'] ?? []) . "\n"
            . 'N-P2O5-K2O per hectare: ' . ($res['npkPerHa'] ?? '?') . "\n"
            . ($soil ? 'Soil test: ' . json_encode($soil) . "\n" : 'No soil test given.' . "\n")
            // Levels the farmer took from the area's typical soil (Use these, 2026-10-18): typical for the area, never a test of this field.
            . (! empty($set['area']['tests']) || ! empty($set['area']['filled'])
                ? 'Of these, typical for the area (soil samples taken there, not a test of this field; say so): ' . json_encode(array_filter(['levels' => $set['area']['tests'] ?? null, 'zinc' => $set['area']['zinc'] ?? null, 'filled' => $set['area']['filled'] ?? null])) . "\n" : '')
            . (! empty($set['texture']) && $set['texture'] !== 'unsure' ? 'Soil type: ' . $set['texture'] . "\n" : '')
            . (! empty($set['conditions']) ? 'Soil conditions the farmer picked: ' . implode(', ', array_map('strval', (array) $set['conditions'])) . "\n" : '')
            . (! empty($set['fieldAdjust']) ? 'What the calculator counted for the field in detail: ' . mb_substr(implode(' ', array_map('strval', (array) $set['fieldAdjust'])), 0, 900) . "\n" : '')
            . (! empty($res['goal']) ? 'Yield goal the calculator counted for: ' . json_encode($res['goal']) . "\n" : '')
            . (! empty($res['needs']) ? 'The calculator\'s need model, kg per ha for that goal on this soil (the official guide rate sized to the goal and adjusted for the soil; plan is what the plan gives): ' . json_encode($res['needs']) . "\n" : '')
            // The farmer left the micronutrients out (2026-10-21): the reading stays with N, P and K.
            . (($set['microsOn'] ?? true) === false ? 'THE FARMER LEFT THE MICRONUTRIENTS OUT of this plan: read and advise on nitrogen, phosphorus and potassium only, and add no secondary or micro element product.' . "\n" : '')
            . (! empty($res['micros']) ? 'The calculator\'s secondary, micro and beneficial element needs, kg of element per ha (level none, watch, likely, or test from a soil test; soil = the share of the crop\'s need the soil gives on its own, from the soil type, the pH, the area\'s soils and what locks it up): ' . json_encode($res['micros']) . "\n" : '')
            // What the plan does to the soil (2026-10-21): its acid against the soil's buffer, and the lockouts it makes.
            . (! empty($res['acid']) ? 'What the plan does to the soil, the app\'s count (kg of lime equivalent a hectare, below 0 raises the pH; pH change around the roots and in the field, below 0 sourer): ' . json_encode($res['acid']) . "\n" : '')
            . (! empty($res['lockouts']) ? 'Lockouts the plan itself makes (already counted in the micro needs): ' . implode(', ', array_map('strval', (array) $res['lockouts'])) . "\n" : '')
            . (isset($res['reach']) && $res['reach'] !== null ? 'Liebig reading: at the shortest plank (a nutrient, or the season\'s sun, water or heat) the plan meets about ' . (int) $res['reach'] . "% of the crop's need for the goal"
                . (isset($res['harvest']) && $res['harvest'] !== null ? '; with what the soil gives on its own, the harvest could still come near ' . (int) $res['harvest'] . '% of the goal this season' : '') . ".\n" : '')
            . (! empty($res['season']['carriesTPerHa']) ? 'What the season lets the variety carry, t/ha (from the years of weather at the field and the ENSO state; potential ' . json_encode($res['season']['potential'] ?? null) . '): ' . json_encode($res['season']['carriesTPerHa']) . "\n" : '')
            . (! empty($res['support']) ? 'The calculator\'s estimate of the yield the fertilizer alone can feed, t/ha per nutrient (soil supply not counted): ' . json_encode($res['support']) . "\n" : '');
    }

    /**
     * The research brief (2026-10-18): written from the crop, the variety,
     * the province, the planting month and the plan's biofertilizers only,
     * so its notes are kept (App\Support\ResearchShelf) for the next reading
     * of the same case. It no longer asks for the guide's kilograms when the
     * calculation carries NPK Plus's own need, nor for the rain around the
     * planting date: the field's twenty year weather record is the app's own
     * (App\Support\ClimateStore) and goes to the document.
     */
    private function researchPrompt(array $p, object $calc): string
    {
        $res = json_decode((string) $calc->result, true) ?: [];
        $bio = collect(json_decode((string) $calc->lines, true) ?: [])->filter(fn ($l) => ! empty($l['estimate']))->pluck('name')->filter()->unique()->sort()->values()->all();
        $month = $p['plantingDate'] ? \Illuminate\Support\Carbon::parse($p['plantingDate'])->format('F Y') : 'not given';
        $province = \App\Support\ClimateRecord::placeKey((string) $p['location'], 'PH')['place'];
        // The area's soil is the app's own (the soil atlas, 2026-10-18): asked of the web only where the atlas has nothing.
        $atlas = \App\Support\SoilAtlas::forWhere((string) $p['location'], (string) $calc->crop);
        $n = 1;

        return "Research for a fertilizer plan check in the Philippines. Use web search and write plain notes (not JSON).\n"
            . 'Province: ' . $province . "\nCrop: " . ($calc->crop ? CropCatalog::label($calc->crop) : 'not set') . ($p['variety'] ? ' (' . $p['variety'] . ')' : '') . "\n"
            . 'Planting: ' . $month . "\n\n"
            . 'Find: '
            . (! empty($res['needs'])
                ? ($n++) . ') the DA, BSWM or PhilRice guide\'s timing of this crop\'s fertilizer by growth stage in this province or region (which stage gets which share); the kilograms per hectare are already counted by the app, do not look them up; '
                : ($n++) . ') the DA, BSWM or PhilRice fertilizer recommendation for this crop in this province or region, and its timing by growth stage; ')
            . ($atlas ? '' : ($n++) . ') the soils of this area (pH, sodic, saline, acid sulfate, deficiencies such as zinc) from soil surveys or studies; ')
            . ($bio ? ($n++) . ') for these biofertilizers (' . implode(', ', $bio) . '), what field trials show they can replace; ' : '')
            // Real harvests (2026-10-17, the owner: "we need real people data").
            . ($n++) . ") REAL HARVESTS: the most recent derbies, techno demos and farmers' reported harvests of this crop (and this variety, when named) in this province or region, newest first: the yield (t/ha, or cavans per hectare with the kilos per cavan), the season, the place and the fertilizer used where reported. Cite each source. "
            . 'The app keeps the field\'s weather history and the ENSO state' . ($atlas ? ' and the area\'s soil survey' : '') . ': do not look those up. At most 900 words.';
    }

    /** The pump's year from the plan's setup (2026-10-21): "; the pump runs low every dry season, in March to May". */
    private static function pumpWords(object $calc): string
    {
        $res = json_decode((string) ($calc->result ?? ''), true) ?: [];
        $pump = (array) ($res['setup']['pump'] ?? []);
        $year = ['always' => 'gives water all year, even in a very dry year', 'badYears' => 'runs low or stops in a very dry year', 'everyYear' => 'runs low or stops every dry season'][$pump['year'] ?? ''] ?? null;
        if (! $year) {
            return '';
        }
        $stops = array_values(array_filter(array_map('intval', (array) ($pump['stops'] ?? []))));
        sort($stops);
        $m = \App\Support\NpkNeed::monthWords($stops);

        return '; the pump or well ' . $year . ($m !== '' && $pump['year'] !== 'always' ? ', in ' . $m : '') . ' (counted in the season\'s water)';
    }

    private function prompt(array $p, object $calc): string
    {
        return "You are Anee, a smart farm technician for Filipino farmers. Judge this fertilizer plan honestly and practically. Return ONE JSON object, nothing else.\n\n"
            . self::planWords($calc) . "\n"
            . 'Place: ' . $p['location'] . "\n"
            . 'Soil as the farmer knows it: ' . (SoilConditions::words($p['soilConditions'], $p['phValue']) ?: 'not stated') . "\n"
            . (SoilConditions::guidance($p['soilConditions']) ? SoilConditions::guidance($p['soilConditions']) . "\n" : '')
            . self::areaSoil($p, $calc)
            . 'Water: ' . (self::WATER[$p['water']] ?? $p['water']) . self::pumpWords($calc) . "\n"
            . FieldProfile::promptLine((array) ($p['fieldProfile'] ?? []), $calc->crop ?: null, '')
            . 'Planting date: ' . ($p['plantingDate'] ?: 'not given') . '; how the farmer means to apply it: ' . (self::TIMING[$p['timing']] ?? $p['timing']) . "\n"
            . ($p['variety'] ? 'Variety: ' . $p['variety'] . (($vf = \App\Support\VarietyBook::factsLine((string) $calc->crop, (string) $p['variety'])) !== '' ? ' (known: ' . $vf . ')' : '') . "\n" : '') . ($p['notes'] ? 'Notes: ' . $p['notes'] . "\n" : '')
            . 'ENSO: ' . (EnsoOutlook::forPrompt() ?: 'not available') . "\n"
            . self::historyFor($p + ['crop' => (string) $calc->crop]) . "\n"
            . "RULES: Weigh the plan against the crop's need and the soil, the water, the weather of that season and ENSO. The need is the calculator's (NPK Plus's need model): judge the plan against it and do not recount it. The field's weather record is measured history kept by the app: use it, with its El Niño and La Niña shares, for what the season does to losses and timing. Say if it is enough, short or too much for each nutrient, which product to cut, add or split, and WHEN each product should go on by growth stage. Name products by their grade or active nutrient, never a brand. Never invent a number that is not in the plan or the research notes; say what you cannot know. Remember: the yield still depends on the weather, the variety and the soil.\n\n"
            . 'Return exactly: {"verdict":"good|close|needs changes|unbalanced","headline":"one line","summary":"3 plain sentences",'
            . '"nutrients":[{"nutrient":"N|P2O5|K2O|S|Zn|...","status":"short|right|over","comment":""}],'
            . '"timing":[{"product":"","when":"growth stage and about which day","how":"","why":""}],'
            . '"changes":[{"change":"","why":""}],"soil":"what this soil does to the plan","weather":"what the season and ENSO mean for losses and timing",'
            . '"biofertilizers":"what the inoculants can and cannot replace (empty if none)","yield":"the yield this plan can realistically support and why, set beside what real farmers and derbies near the field harvested (place, season, yield) when the notes have it",'
            . '"warnings":[""],"confidence":"low|medium|high"}';
    }

    /** The area's typical soil (the soil atlas), as prompt lines; '' where the atlas knows nothing of the place. */
    private static function areaSoil(array $p, object $calc): string
    {
        $needs = (json_decode((string) $calc->result, true) ?: [])['needs'] ?? null;
        $w = \App\Support\SoilAtlas::words(\App\Support\SoilAtlas::forWhere((string) $p['location'], (string) $calc->crop), $needs ? 'short' : 'prompt');

        return $w !== '' ? $w . "\n" : '';
    }

    /** The field's weather record for the months the crop stands in the field (the climate shelf, 2026-10-18). */
    private static function historyFor(array $p): string
    {
        $from = $p['plantingDate'] ? \Illuminate\Support\Carbon::parse($p['plantingDate']) : now('Asia/Manila')->addDays(14);
        $focus = [];
        $crop = (string) ($p['crop'] ?? '');
        $days = (int) (\App\Support\VarietyBook::days($crop, (string) ($p['variety'] ?? '')) ?: ($crop !== '' ? CropCatalog::maturity($crop) : 120));
        for ($i = 0, $n = min(12, (int) ceil($days / 30) + 1); $i < $n; $i++) {
            $focus[] = (int) $from->copy()->startOfMonth()->addMonths($i)->month;
        }

        return \App\Support\ClimateStore::wordsFor((string) $p['location'], null, null, $focus, true)
            // The town's twenty years of storms and floods (the hazard book), so the split timing avoids the months they hit most years.
            . (class_exists(\App\Support\HazardBook::class) && method_exists(\App\Support\HazardBook::class, 'wordsFor') ? \App\Support\HazardBook::wordsFor((string) $p['location'], $focus, 1200) : '');
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

                return $this->json(false, $why, ['status' => 'failed'], 422);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1), 'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The reading failed and nothing was charged. Please try again.', ['status' => 'failed'], 422);
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
