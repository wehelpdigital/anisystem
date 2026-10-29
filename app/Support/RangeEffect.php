<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use Illuminate\Support\Carbon;

/**
 * The Range Effect calendar (2026-10-29, the owner: "when it is clicked, it shows a full modal box full screen that
 * shows the current coverage of the following: N, P, K, Micros, pest protection, fungus protection, and so on based
 * on the inputs ... if user applied N in the plan how long is its effect in the plant? also show the criticality in
 * the calendar if its highly critical in that part based on the growth stage ... and or specific product that the
 * user plans to apply ... note that this can only count the specified per day applications in the inventory and
 * when it is used").
 *
 * One lot, one thing to check (a nutrient, a kind of threat, or one named pest or disease of the lot's crop), every
 * day from before planting to past harvest:
 *   - what the season's tasks put on (each product line of each day's task, from the shed or typed in, on the day
 *     its task is set; done or still planned), read through NPK Plus's matching for fertilizers and the product book
 *     and the ingredient dictionary for crop protection;
 *   - how long each application keeps working (RangeEffectBook: a nutrient by its form and the water, an active
 *     ingredient by its own days), drawn as a coverage level per day, 1 being a full usual dose at work;
 *   - how much that nutrient or threat matters at the crop's stage that day (0 none to 3 critical);
 *   - the verdict per day: covered, thin, a gap (critical and uncovered), or late (a nutrient put on when the crop
 *     no longer takes it up).
 * Products the farmer is thinking of ("tries": a product and a date) are counted the same way and marked, so the
 * calendar shows what they would add.
 */
class RangeEffect
{
    /** Check key => [element as NpkPlan names it, name]. */
    public const NUTRIENTS = [
        'N' => ['N', 'Nitrogen'], 'P' => ['P2O5', 'Phosphorus'], 'K' => ['K2O', 'Potassium'],
        'Ca' => ['Ca', 'Calcium'], 'Mg' => ['Mg', 'Magnesium'], 'S' => ['S', 'Sulfur'], 'Zn' => ['Zn', 'Zinc'], 'B' => ['B', 'Boron'],
        'Fe' => ['Fe', 'Iron'], 'Mn' => ['Mn', 'Manganese'], 'Cu' => ['Cu', 'Copper'], 'Mo' => ['Mo', 'Molybdenum'], 'Si' => ['Si', 'Silicon'],
    ];

    public const MICROS = ['Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si'];

    /** Threat key => [name, icon]. */
    public const THREATS = [
        'insect' => ['Insect pests', '🐛'], 'fungus' => ['Fungal diseases', '🍄'], 'bacteria' => ['Bacterial diseases', '🦠'], 'weed' => ['Weeds', '🌿'],
        'mite' => ['Mites', '🕷️'], 'snail' => ['Snails', '🐌'], 'rat' => ['Rats', '🐀'], 'nematode' => ['Nematodes', '🪱'],
    ];

    /** One usual application, kg of the element a hectare: on the soil, and sprayed on the leaves. Coverage 1 = one of these at work. */
    public const REF_SOIL = ['N' => 30, 'P2O5' => 25, 'K2O' => 25, 'Ca' => 25, 'Mg' => 10, 'S' => 10, 'Zn' => 2.5, 'B' => 0.6, 'Fe' => 2, 'Mn' => 2, 'Cu' => 1, 'Mo' => 0.1, 'Si' => 40];

    public const REF_FOLIAR = ['N' => 2, 'P2O5' => 1.2, 'K2O' => 2, 'Ca' => 0.6, 'Mg' => 0.5, 'S' => 0.5, 'Zn' => 0.25, 'B' => 0.1, 'Fe' => 0.25, 'Mn' => 0.2, 'Cu' => 0.1, 'Mo' => 0.02, 'Si' => 1];

    /** A catalogue entry's kind => the threat it falls under. */
    public const KIND_CAT = ['Insect' => 'insect', 'Mite' => 'mite', 'Snail' => 'snail', 'Rodent' => 'rat', 'Fungus' => 'fungus', 'Water mold' => 'fungus',
        'Bacterium' => 'bacteria', 'Virus' => 'insect', 'Nematode' => 'nematode'];

    /** The ingredient dictionary's kind => what it protects against, and its days when the book has none. */
    public const KIND_DEFAULT = [
        'insecticide' => [['insect'], 'contact', [5, 10, 14]], 'acaricide' => [['mite'], 'contact', [5, 10, 14]], 'fungicide' => [['fungus'], 'protectant', [5, 10, 14]],
        'bactericide' => [['bacteria'], 'contact', [3, 5, 7]], 'herbicide' => [['weed'], 'post', [3, 7, 10]], 'molluscicide' => [['snail'], 'bait', [3, 5, 7]],
        'rodenticide' => [['rat'], 'bait', [5, 10, 14]], 'nematicide' => [['nematode'], 'soil', [14, 21, 30]],
    ];

    public const MAX_DAYS = 240;

    /**
     * How many checks can be read side by side: no limit the farmer meets (2026-10-30, the owner: "compare should have no
     * limits"), only a guard against a runaway request; every nutrient, kind of threat and pest of a crop fits under it.
     */
    public const MAX_CHECKS = 120;

    /**
     * The way of counting, by date: a saved reading counted an older way is counted again when it is opened (the
     * owner, 2026-10-30: "i opened the saved file and the P is still at 78%"). Raise it whenever the numbers change.
     */
    public const VERSION = '2026-10-30.8';

    /**
     * What an application is doing on a day (2026-10-30, the owner: "the absorption stage, the ammonization in soil
     * stage, the effect stage and the color for strong effect to fading away"): still in the soil (urea turning into
     * ammonium, a salt dissolving, manure breaking down), being taken up, at its strongest, easing, fading away.
     */
    public const PHASES = ['soil', 'absorb', 'strong', 'easing', 'fading'];

    /** Days nitrogen spends in the soil before the roots can take it, [flooded, upland]: urea first turns into ammonium. */
    private const N_SOIL_DAYS = ['urea' => [2, 3], 'inhibitor' => [5, 6], 'complete' => [1, 2], 'ammonium sulfate' => [1, 1], 'ammonium phosphate' => [1, 1], 'nitrate' => [0, 0]];

    /**
     * Fertilizer salts sold under their own names (a product the book holds without a grade, or an ingredient tried on
     * its own): percent of each element in the salt, the oxides as NpkPlan names them, silicon as the element.
     */
    private const COMPOUNDS = [
        'potassium silicate' => ['K2O' => 28, 'Si' => 33], 'calcium silicate' => ['Ca' => 30, 'Si' => 21], 'sodium silicate' => ['Si' => 28],
        'silicic acid' => ['Si' => 29], 'potassium nitrate' => ['N' => 13, 'K2O' => 46], 'calcium nitrate' => ['N' => 15.5, 'Ca' => 19],
        'potassium sulfate|sulfate of potash|sulphate of potash' => ['K2O' => 50, 'S' => 18], 'potassium chloride|muriate of potash' => ['K2O' => 60],
        'mono ?potassium phosphate|\bmkp\b' => ['P2O5' => 52, 'K2O' => 34], 'magnesium sulfate|magnesium sulphate|epsom' => ['Mg' => 9.8, 'S' => 13],
        'kieserite' => ['Mg' => 16, 'S' => 22], 'zinc sulfate|zinc sulphate' => ['Zn' => 33, 'S' => 16], 'zinc oxide' => ['Zn' => 78],
        'borax' => ['B' => 11], 'boric acid' => ['B' => 17], 'solubor|disodium octaborate' => ['B' => 20.5], 'ferrous sulfate|iron sulfate' => ['Fe' => 20, 'S' => 11],
        'manganese sulfate' => ['Mn' => 31, 'S' => 18], 'copper sulfate' => ['Cu' => 25, 'S' => 12.8], 'sodium molybdate' => ['Mo' => 39], 'ammonium molybdate' => ['Mo' => 54],
        'calcium chloride' => ['Ca' => 27], 'gypsum|calcium sulfate' => ['Ca' => 23, 'S' => 18], 'dolomit' => ['Ca' => 21, 'Mg' => 12],
        'calcium carbonate|agricultural lime|aglime' => ['Ca' => 38],
    ];

    /** Units that say a line was sprayed or poured, not spread. */
    private const LIQUID_UNITS = ['l', 'liter', 'liters', 'litre', 'litres', 'lt', 'ltr', 'ml', 'g', 'gram', 'grams', 'gm', 'tbsp', 'tsp', 'sachet', 'sachets', 'pack', 'packs', 'bottle', 'bottles', 'cc'];

    /* =============================================================== the lots and what can be checked */

    /** Every lot of the season, with its crop, its count and the pests and diseases its crop can carry. */
    public static function lots(AsCroppingSchedule $schedule): array
    {
        $anchors = LotCalendar::effectiveAnchors($schedule);
        $out = [];
        foreach ($schedule->lots->where('deleteStatus', 1)->sortBy('id') as $lot) {
            $c = self::ctx($schedule, $lot, $anchors);
            $out[] = [
                'id' => (int) $lot->id, 'name' => (string) ($lot->lotName ?: 'Lot ' . $lot->id), 'crop' => $c['cropLabel'], 'cropKey' => $c['crop'], 'icon' => CropStages::icon($c['crop']),
                'variety' => (string) ($lot->variety ?: ''), 'counter' => $c['counter'], 'ha' => $c['ha'], 'anchor' => $c['anchor']?->toDateString(),
                'harvest' => $c['harvest']?->toDateString(), 'hasDay' => $c['anchor'] !== null, 'threats' => self::threatsFor($c['crop']),
                // For a what-if lot built on this one: its place, and its field answers to start from.
                'place' => trim(implode(', ', array_filter([$lot->locBarangay, $lot->locTown, $lot->locProvince]))),
                'answers' => (object) (FieldProfile::storeReady() ? LotSync::answersFor($schedule, $lot) : []),
            ];
        }

        return $out;
    }

    /** The pests and diseases of a crop, from the catalogue: slug, name, local name, kind, the threat it falls under. */
    public static function threatsFor(?string $crop): array
    {
        $cat = FieldCatalogue::cropKey($crop);
        if (! $cat) {
            return [];
        }
        $out = [];
        foreach (['pests', 'diseases'] as $sec) {
            foreach (FieldCatalogue::problems($sec) as $slug => $e) {
                $kind = (string) ($e['kind'] ?? '');
                if (! in_array($cat, (array) ($e['crops'] ?? []), true) || ! isset(self::KIND_CAT[$kind])) {
                    continue;
                }
                $out[] = ['slug' => (string) ($e['slug'] ?? $slug), 'name' => (string) ($e['name'] ?? $slug), 'local' => (string) ($e['local'] ?? ''), 'kind' => $kind,
                    'cat' => self::KIND_CAT[$kind], 'sec' => $sec];
            }
        }
        usort($out, fn ($a, $b) => [$a['sec'], $a['name']] <=> [$b['sec'], $b['name']]);

        return $out;
    }

    /** One lot's crop, count, anchors, stage table and water. */
    private static function ctx(AsCroppingSchedule $schedule, AsScheduleLot $lot, array $anchors): array
    {
        [$zeroMap, $trMap] = $anchors;
        $crop = CropStages::normalize($lot->crop ?: $schedule->cropType);
        $zero = $zeroMap[$lot->id] ?? null;
        $tr = $trMap[$lot->id] ?? null;
        $mode = strtoupper((string) ($lot->dayType ?: CropStages::counter($crop)));
        $tree = $crop && CropStages::isPerennial($crop);
        $maturity = $tree ? null : $lot->maturityDays();
        // The day the field's own count starts: the transplant for a transplanted lot, else day zero.
        $anchor = $tree ? null : (($mode === 'DAT' && $tr) ? $tr->copy() : ($zero ? $zero->copy() : null));
        // Anee's realign moves the harvest as it moves the stages (2026-10-30): a crop she found 12 days behind is cut
        // 12 days later, as Harvest dates reads it, so its last stages are no longer cut off at the calendar's day.
        $shift = $tree ? 0 : (int) ($lot->growthShiftDays ?? 0);

        return [
            'lot' => $lot, 'crop' => $crop, 'cropLabel' => CropStages::label($crop) ?: ((string) ($lot->crop ?: 'Crop not set')), 'mode' => $mode, 'tree' => $tree,
            'zero' => $zero, 'tr' => $tr, 'anchor' => $anchor, 'maturity' => $maturity, 'harvest' => $anchor && $maturity ? $anchor->copy()->addDays($maturity - $shift) : null,
            'calHarvest' => $anchor && $maturity ? $anchor->copy()->addDays($maturity) : null,
            'counter' => $tree ? 'AGE' : ($mode === 'DAT' ? 'DAT' : ($mode === 'DAP' ? 'DAP' : 'DAS')),
            'table' => self::tableOf($crop, $mode), 'water' => $crop && str_starts_with($crop, 'rice') && $crop !== 'rice_dsr_dry' ? 'flooded' : 'upland',
            'ha' => NpkPlan::hectares($lot->lotSize, $lot->lotSizeUnit),
        ];
    }

    /** Which stage table of the book a crop reads: its own, or its pattern's. */
    public static function tableOf(?string $crop, string $mode = 'DAT'): ?string
    {
        if (! $crop) {
            return null;
        }
        if ($crop === 'rice') {
            return $mode === 'DAS' ? 'rice_dsr_wet' : 'rice';
        }
        if (isset(CropStages::TABLES[$crop])) {
            return $crop;
        }
        if (CropStages::isPerennial($crop)) {
            return 'tree';
        }

        return (string) (CropCatalog::CROPS[$crop]['pattern'] ?? 'cereal');
    }

    /** What a check key means: kind nutrient|threat|pest, its name, icon, the element or the threat. */
    public static function checkOf(string $key, ?string $crop = null): ?array
    {
        [$k, $v] = array_pad(explode(':', $key, 2), 2, '');
        if ($k === 'n' && isset(self::NUTRIENTS[$v])) {
            return ['key' => $key, 'kind' => 'nutrient', 'el' => self::NUTRIENTS[$v][0], 'nut' => $v, 'label' => self::NUTRIENTS[$v][1] . ' (' . $v . ')', 'short' => $v, 'word' => mb_strtolower(self::NUTRIENTS[$v][1]), 'icon' => '🧪'];
        }
        if ($k === 't' && isset(self::THREATS[$v])) {
            return ['key' => $key, 'kind' => 'threat', 'cat' => $v, 'label' => self::THREATS[$v][0], 'short' => self::THREATS[$v][0], 'word' => mb_strtolower(self::THREATS[$v][0]), 'icon' => self::THREATS[$v][1]];
        }
        if ($k === 'p' && $v !== '') {
            foreach (['pests', 'diseases'] as $sec) {
                $e = FieldCatalogue::problem($sec, $v);
                if ($e && isset(self::KIND_CAT[$e['kind'] ?? ''])) {
                    $cat = self::KIND_CAT[$e['kind']];

                    return ['key' => $key, 'kind' => 'pest', 'slug' => $v, 'cat' => $cat, 'label' => (string) $e['name'], 'short' => (string) $e['name'], 'word' => mb_strtolower((string) $e['name']),
                        'icon' => self::THREATS[$cat][1], 'ai' => self::pestIngredients($e, FieldCatalogue::cropKey($crop))];
                }
            }
        }

        return null;
    }

    /** The active ingredients a pest's entry names (for the crop when it says), as dictionary names. */
    private static function pestIngredients(array $e, ?string $cat): array
    {
        $rows = ($cat && ! empty($e['aiBy'][$cat])) ? $e['aiBy'][$cat] : ($e['ai'] ?? []);
        $out = [];
        foreach ((array) $rows as $r) {
            $name = is_array($r) ? (string) ($r[0] ?? '') : (string) $r;
            foreach (array_merge([$name], preg_split('/\s+plus\s+|\s*\+\s*/i', $name)) as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $d = CompatBook::ingredient($part);
                $out[$d['name'] ?? $part] = true;
            }
        }

        return array_keys($out);
    }

    /* =============================================================== the reading */

    /**
     * The calendar for one lot and one check. $tries: [{date: Y-m-d, product: product array (see productOf), qty, unit}].
     */
    /**
     * $custom: a what-if lot (2026-10-30, the owner: "have a custom button just to see if what it will look in a custom
     * soil ... this one will not be saved as a lot, only for experimentation reference that can be saved in that range
     * effect modal reading"): the lot's crop, days and tasks with other field answers and place, {name, place, answers}.
     */
    public static function run(AsCroppingSchedule $schedule, int $lotId, string $checkKey, array $tries = [], array $also = [], array $logic = [], ?array $custom = null): array
    {
        $schedule->loadMissing(['lots', 'activities.lots', 'activities.items.material']);
        $lot = $schedule->lots->firstWhere('id', $lotId);
        if (! $lot || (int) $lot->deleteStatus !== 1) {
            throw new \InvalidArgumentException('That lot is not on this season.');
        }
        $anchors = LotCalendar::effectiveAnchors($schedule);
        $c = self::ctx($schedule, $lot, $anchors);
        if ($custom) {
            $c['custom'] = self::customOf($custom);
        }
        $check = self::checkOf($checkKey, $c['crop']) ?? self::checkOf('n:N');
        $today = Carbon::now('Asia/Manila')->startOfDay();
        // The deeper reading (RangeEffectLogic): amounts, the soil, the links between nutrients, the crop's health.
        $opts = RangeEffectLogic::opts($logic);
        $soil = RangeEffectLogic::soil($schedule, $lot, $c);
        $c['soilF'] = $opts['soil'] ? $soil['f'] : [];

        // Every product line on this lot's tasks, read once; then the tries.
        $apps = self::applications($schedule, $c);
        foreach (array_values($tries) as $i => $t) {
            $app = self::fromTry($t, $c, $i);
            if ($app) {
                $apps[] = $app;
            }
        }
        // Other checks read beside this one (the owner, 2026-10-30: "allow multiple selections in check to see the
        // relationship of each"): the same products and days, each check's own curves.
        $alsoChecks = [];
        foreach (array_slice(array_values(array_unique(array_map('strval', $also))), 0, self::MAX_CHECKS - 1) as $k) {
            $ck = self::checkOf($k, $c['crop']);
            if ($ck && $ck['key'] !== $check['key']) {
                $alsoChecks[$ck['key']] = $ck;
            }
        }
        foreach ($apps as $i => &$a) {
            $a['i'] = $i;
            $a['curve'] = self::curveFor($a, $check, $c);
            $a['relevant'] = $a['curve'] !== null;
            $a['curves'] = [];
            $a['span'] = $a['curve']['hi'] ?? null;
            foreach ($alsoChecks as $k => $ck) {
                $a['curves'][$k] = self::curveFor($a, $ck, $c);
                if ($a['curves'][$k]) {
                    $a['span'] = max((int) ($a['span'] ?? 0), (int) $a['curves'][$k]['hi']);
                }
            }
        }
        unset($a);

        // The stretch: before planting to past harvest, or around the work when the lot has no day yet.
        [$from, $to] = self::stretch($c, $apps, $today);

        $crit = $c['table'] ? RangeEffectBook::crit($c['table']) : [];
        $lateNotes = $c['table'] ? RangeEffectBook::late($c['table']) : [];
        $days = [];
        $stages = [];
        $info = [];
        $last = false;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            [$stage, $dayLabel] = self::stageOn($c, $d);
            $info[] = ['label' => $stage['label'] ?? null, 'in' => $stage !== null && ! ($c['harvest'] && $d->gt($c['harvest'])), 'date' => $d->copy()];
            [$lvl, $why] = self::critOf($crit, $stage['label'] ?? null, $check);
            $cov = 0.0;
            $tryCov = 0.0;
            $by = [];
            // What the strongest one is doing that day, and whether another is still in the soil.
            $phase = '';
            $top = 0.0;
            $inSoil = false;
            foreach ($apps as $a) {
                if (! $a['curve']) {
                    continue;
                }
                $t = (int) $a['date']->diffInDays($d, false);
                $ph = self::phaseOf($a['curve'], $t);
                if ($ph === 'soil') {
                    $inSoil = true;
                }
                $v = self::level($a['curve'], $t);
                if ($v <= 0.01) {
                    continue;
                }
                if ($v > $top) {
                    $top = $v;
                    $phase = (string) $ph;
                }
                $by[] = $a['i'];
                if ($check['kind'] === 'nutrient') {
                    $cov += $v;
                } else {
                    $cov = max($cov, $v);
                }
                if ($a['try']) {
                    $tryCov = max($tryCov, $v);
                }
            }
            $cov = min(1.6, $cov);
            $afterHarvest = $c['harvest'] && $d->gt($c['harvest']);
            $state = self::stateOf($stage !== null && ! $afterHarvest, $lvl, $cov, $check['kind'] === 'nutrient');
            $label = $stage['label'] ?? null;
            if ($afterHarvest) {
                $label = 'After harvest';
            }
            if ($label !== $last) {
                $real = $label !== null && ! $afterHarvest;
                // What the crop does and needs then, and its place in the crop's table, for the stage's own sheet.
                $stages[] = ['label' => $label ?? ($c['anchor'] ? 'Before planting' : 'No planting day yet'), 'from' => $d->toDateString(), 'to' => $d->toDateString(),
                    'crit' => $lvl, 'why' => $why, 'stage' => $real,
                    'what' => $real ? (string) ($stage['what'] ?? '') : '', 'needs' => $real ? (string) ($stage['needs'] ?? '') : '',
                    'index' => $real ? ($stage['index'] ?? null) : null, 'count' => $real ? ($stage['count'] ?? null) : null];
                $last = $label;
            } else {
                $stages[count($stages) - 1]['to'] = $d->toDateString();
                if ($lvl > $stages[count($stages) - 1]['crit']) {
                    $stages[count($stages) - 1]['crit'] = $lvl;
                    $stages[count($stages) - 1]['why'] = $why;
                }
            }
            $days[] = ['d' => $d->toDateString(), 'c' => $lvl, 'v' => round($cov, 2), 's' => $state, 'g' => count($stages) - 1, 'dl' => $dayLabel,
                'a' => $by, 't' => $tryCov > 0.01 ? round($tryCov, 2) : 0, 'p' => $phase ?: ($inSoil ? 'soil' : ''), 'sl' => $inSoil ? 1 : 0];
        }
        $realign = self::realignOf($c, $stages);
        $relevant = array_filter($apps, fn ($a) => $a['curve'] !== null);
        $series = [];
        foreach ($alsoChecks as $k => $ck) {
            $series[] = self::seriesOf($apps, $k, $ck, $days, $info, $stages, $crit, $lateNotes, $c);
        }
        $deep = RangeEffectLogic::read($days, $apps, $check, $c, $soil, $opts, $info);
        if ($opts['health'] && $check['kind'] !== 'nutrient') {
            [$marks, $healthNotes] = self::health($apps, $check, $c, $days, $info, $crit);
            foreach ($days as $i => &$day) {
                $day['h'] = $marks[$i] ?? '';
            }
            unset($day);
            $deep['notes'] = array_merge($deep['notes'], $healthNotes);
        }

        return [
            'lot' => ['id' => (int) $lot->id, 'name' => (string) ($lot->lotName ?: 'Lot ' . $lot->id), 'crop' => $c['cropLabel'], 'icon' => CropStages::icon($c['crop']),
                'counter' => $c['counter'], 'ha' => $c['ha'], 'anchor' => $c['anchor']?->toDateString(), 'harvest' => $c['harvest']?->toDateString(), 'water' => $c['water'],
                'hasDay' => $c['anchor'] !== null || $c['tree'], 'realign' => $realign, 'delay' => $c['tree'] ? 0 : max(0, (int) ($lot->delayDays ?? 0)),
                'custom' => isset($c['custom']) ? ['name' => $c['custom']['name'], 'place' => $c['custom']['place'], 'count' => count($c['custom']['answers'])] : null],
            'check' => array_diff_key($check, ['ai' => 1]),
            'from' => $from->toDateString(), 'to' => $to->toDateString(), 'today' => $today->toDateString(),
            'days' => $days, 'stages' => $stages,
            'apps' => array_map(fn ($a) => self::appOut($a, $check), $apps),
            'summary' => self::summary($days, $stages, $apps, $check, $lateNotes, $c),
            'tables' => ['crit' => $c['table'], 'hasCrit' => (bool) $crit],
            'phaseWords' => self::phaseWords($check, $relevant),
            'series' => $series,
            'logic' => ['opts' => $opts, 'soil' => $soil['facts'], 'amt' => $deep['amt'], 'notes' => $deep['notes'], 'others' => $deep['others']],
            'version' => self::VERSION,
        ];
    }

    /**
     * Anee's realign of this lot, when one was saved: the days it found the crop ahead of (+) or behind (-) the
     * calendar, when, how sure, and what it saw. The stages above already follow it (stageDay); each real stage
     * also gets the dates the calendar alone would give it ('cal'), so the stage's sheet can show both.
     * Null for a lot never realigned, and for trees (they count months, not days).
     */
    private static function realignOf(array $c, array &$stages): ?array
    {
        $lot = $c['lot'];
        if ($c['tree'] || ! $lot->growthRealignedAt || ! is_array($lot->growthRealign)) {
            return null;
        }
        $r = $lot->growthRealign;
        $shift = (int) ($lot->growthShiftDays ?? 0);
        if ($shift !== 0 && $c['anchor'] && $c['crop']) {
            $table = CropStages::stagesFor($c['crop'], $c['counter'], $c['maturity']);
            foreach ($stages as &$g) {
                $i = $g['index'];
                if (! $g['stage'] || $i === null || ! isset($table[$i])) {
                    continue;
                }
                $from = $c['anchor']->copy()->addDays((int) $table[$i][0]);
                $to = isset($table[$i + 1]) ? $c['anchor']->copy()->addDays((int) $table[$i + 1][0] - 1) : $c['calHarvest'];
                if ($to && $c['calHarvest'] && $to->gt($c['calHarvest'])) {
                    $to = $c['calHarvest']->copy();
                }
                $g['cal'] = ['from' => $from->toDateString(), 'to' => $to ? $to->toDateString() : null];
            }
            unset($g);
        }

        return ['shift' => $shift, 'at' => $lot->growthRealignedAt->copy()->timezone('Asia/Manila')->toDateString(),
            'confidence' => isset($r['confidence']) ? (int) $r['confidence'] : null, 'summary' => (string) ($r['summary'] ?? ''),
            'stage' => $r['stageLabel'] ?? null, 'calendarStage' => $r['calendarStageLabel'] ?? null];
    }

    /**
     * What the nutrients do to the crop's guard (the health part of the logic), for a check of insects, fungal or
     * bacterial diseases or one of them by name: nitrogen still strong when the stage no longer takes it, or very
     * heavy at any stage, makes soft lush leaves that blast, sheath blight, bacterial blight and planthoppers find
     * easy; potassium and silicon at work help the crop hold out. Per day: 'n' (nitrogen risk), 'k' (a guard
     * helped), ''. With notes for the runs.
     */
    private static function health(array $apps, array $check, array $c, array $days, array $info, array $crit): array
    {
        $cat = $check['cat'] ?? null;
        if (! in_array($cat, ['insect', 'fungus', 'bacteria'], true)) {
            return [[], []];
        }
        $nCk = self::checkOf('n:N');
        $kCk = self::checkOf('n:K');
        $sCk = self::checkOf('n:Si');
        $curves = [];
        foreach ($apps as $a) {
            $curves[] = [$a, self::curveFor($a, $nCk, $c), self::curveFor($a, $kCk, $c), self::curveFor($a, $sCk, $c)];
        }
        $marks = [];
        $runs = ['n' => [], 'k' => []];
        $open = [];
        $who = ['n' => [], 'k' => []];
        foreach ($days as $i => $d) {
            $date = $info[$i]['date'];
            $n = 0.0;
            $k = 0.0;
            $si = 0.0;
            foreach ($curves as [$a, $cn, $ck, $cs]) {
                $t = (int) $a['date']->diffInDays($date, false);
                foreach ([[$cn, 'n'], [$ck, 'k'], [$cs, 's']] as [$cv, $w]) {
                    if (! $cv) {
                        continue;
                    }
                    $v = self::level($cv, $t);
                    if ($v <= 0.01) {
                        continue;
                    }
                    if ($w === 'n') {
                        $n += $v;
                        $who['n'][$a['as']] = true;
                    } elseif ($w === 'k') {
                        $k += $v;
                        $who['k'][$a['as']] = true;
                    } else {
                        $si += $v;
                        $who['k'][$a['as']] = true;
                    }
                }
            }
            $mark = '';
            $late = false;
            if (! empty($info[$i]['in'])) {
                $nLvl = self::critOf($crit, $info[$i]['label'], $nCk)[0];
                $late = $nLvl === 0 && $n >= 0.35;
                if ($late || $n >= 1.45) {
                    $mark = 'n';
                } elseif ($k >= 0.75 || $si >= 0.35) {
                    $mark = 'k';
                }
            }
            $marks[$i] = $mark;
            foreach (['n', 'k'] as $rk) {
                if ($mark === $rk) {
                    $open[$rk] ??= ['from' => $d['d'], 'to' => $d['d'], 'n' => 0, 'late' => false];
                    $open[$rk]['to'] = $d['d'];
                    $open[$rk]['n']++;
                    $open[$rk]['late'] = $open[$rk]['late'] || ($rk === 'n' && ! empty($late));
                } elseif (isset($open[$rk])) {
                    $runs[$rk][] = $open[$rk];
                    unset($open[$rk]);
                }
            }
        }
        foreach ($open as $rk => $r) {
            $runs[$rk][] = $r;
        }
        $say = fn ($r) => Carbon::parse($r['from'])->format('M j') . ($r['to'] !== $r['from'] ? ' to ' . Carbon::parse($r['to'])->format('M j') : '');
        $them = ['insect' => 'planthoppers, leafhoppers and leaffolders', 'fungus' => 'blast and sheath blight', 'bacteria' => 'bacterial leaf blight'][$cat];
        $notes = [];
        foreach (array_slice(array_filter($runs['n'], fn ($r) => $r['n'] >= 3), 0, 3) as $r) {
            $notes[] = ['k' => 'health', 'icon' => '🌿', 'tone' => 'warn', 'title' => $say($r) . ': nitrogen is lush',
                'text' => ($r['late'] ? 'Nitrogen is still strong on these days, more than the stage takes.' : 'More than one full dose of nitrogen is working on these days.')
                    . ' It makes soft, lush leaves that ' . $them . ' find easy. Scout these days more often, and keep the next nitrogen light or hold it.',
                'from' => $r['from'], 'to' => $r['to']];
        }
        foreach (array_slice(array_filter($runs['k'], fn ($r) => $r['n'] >= 5), 0, 2) as $r) {
            $notes[] = ['k' => 'health', 'icon' => '🛡️', 'tone' => 'good', 'title' => $say($r) . ': potassium and silicon at work',
                'text' => 'Potassium or silicon is working on these days. They make firmer cell walls and stems, which helps the crop hold out against ' . $them . ': it does not replace a spray when the pest is there.',
                'from' => $r['from'], 'to' => $r['to']];
        }

        return [$marks, $notes];
    }

    /**
     * Another check over the same days, kept small for the side by side view: coverage, verdict, phase and how
     * critical, one entry a day, with its own count of covered days.
     */
    private static function seriesOf(array $apps, string $key, array $check, array $days, array $info, array $stages, array $crit, array $lateNotes, array $c): array
    {
        $nut = $check['kind'] === 'nutrient';
        $rows = [];
        foreach ($days as $i => $day) {
            [$lvl] = self::critOf($crit, $info[$i]['label'], $check);
            $cov = 0.0;
            $top = 0.0;
            $phase = '';
            $soil = false;
            foreach ($apps as $a) {
                $k = $a['curves'][$key] ?? null;
                if (! $k) {
                    continue;
                }
                $t = (int) $a['date']->diffInDays($info[$i]['date'], false);
                $ph = self::phaseOf($k, $t);
                $soil = $soil || $ph === 'soil';
                $v = self::level($k, $t);
                if ($v <= 0.01) {
                    continue;
                }
                if ($v > $top) {
                    $top = $v;
                    $phase = (string) $ph;
                }
                $cov = $nut ? $cov + $v : max($cov, $v);
            }
            $cov = min(1.6, $cov);
            $rows[] = ['d' => $day['d'], 'c' => $lvl, 'v' => round($cov, 2), 's' => self::stateOf($info[$i]['in'], $lvl, $cov, $nut), 'g' => $day['g'], 'p' => $phase ?: ($soil ? 'soil' : '')];
        }
        $mine = array_map(fn ($a) => ['curve' => $a['curves'][$key] ?? null, 'relevant' => ($a['curves'][$key] ?? null) !== null] + $a, $apps);
        $sum = self::summary($rows, $stages, $mine, $check, $lateNotes, $c);
        $gaps = array_map(fn ($r) => ['why' => self::critOf($crit, $stages[$r['g']]['label'] ?? null, $check)[1]] + $r, array_slice($sum['gaps'], 0, 3));

        return [
            'check' => array_diff_key($check, ['ai' => 1]), 'pct' => $sum['pct'], 'coveredDays' => $sum['coveredDays'], 'thinDays' => $sum['thinDays'], 'gapDays' => $sum['gapDays'],
            'lateDays' => array_sum(array_column($sum['late'], 'n')), 'apps' => $sum['apps'], 'gaps' => $gaps,
            'v' => array_column($rows, 'v'), 's' => array_column($rows, 's'), 'p' => array_column($rows, 'p'), 'c' => array_column($rows, 'c'),
            'words' => self::phaseWords($check, array_filter($mine, fn ($a) => $a['curve'] !== null)),
        ];
    }

    /**
     * The calendar's names for the phases: one product's own word when every product shares it ("Breaking down in
     * the soil" when all of it is manure), else the check's general one.
     */
    private static function phaseWords(array $check, array $relevant): array
    {
        $nut = $check['kind'] === 'nutrient';
        $out = $nut
            ? ['soil' => $check['nut'] === 'N' ? 'In the soil, turning into ammonium' : 'In the soil, dissolving', 'absorb' => 'Being taken up', 'strong' => 'Strong effect',
                'easing' => 'Still working, easing', 'fading' => 'Fading away']
            : ['soil' => 'Taking hold', 'absorb' => 'Moving into the plant', 'strong' => 'Strong protection', 'easing' => 'Still protecting, easing', 'fading' => 'Wearing off'];
        foreach (['soil', 'absorb'] as $p) {
            $words = array_unique(array_filter(array_map(fn ($a) => $a['curve']['words'][$p] ?? null, $relevant)));
            if (count($words) === 1) {
                $out[$p] = reset($words);
            }
        }

        return $out;
    }

    /** The first and last day drawn. */
    private static function stretch(array $c, array $apps, Carbon $today): array
    {
        $starts = [];
        $ends = [];
        if ($c['tree']) {
            $starts[] = $today->copy()->subDays(60);
            $ends[] = $today->copy()->addDays(150);
        }
        if ($c['zero']) {
            $starts[] = $c['zero']->copy()->subDays(7);
        }
        if ($c['anchor']) {
            $starts[] = $c['anchor']->copy()->subDays(7);
        }
        if ($c['harvest']) {
            $ends[] = $c['harvest']->copy()->addDays(7);
        }
        foreach ($apps as $a) {
            // Any product acting on any of the checks being compared.
            if (($a['span'] ?? null) !== null) {
                $starts[] = $a['date']->copy()->subDays(3);
                $ends[] = $a['date']->copy()->addDays(min(60, (int) $a['span']) + 3);
            }
        }
        if (! $starts) {
            $starts[] = $today->copy()->subDays(14);
        }
        if (! $ends) {
            $ends[] = $today->copy()->addDays(120);
        }
        $from = min($starts)->copy()->startOfDay();
        $to = max($ends)->copy()->startOfDay();
        if ($c['harvest'] && $to->gt($c['harvest']->copy()->addDays(21))) {
            $to = $c['harvest']->copy()->addDays(21);
        }
        if ($from->diffInDays($to) > self::MAX_DAYS) {
            $to = $from->copy()->addDays(self::MAX_DAYS);
        }
        if ($to->lt($from)) {
            $to = $from->copy()->addDays(30);
        }

        return [$from, $to];
    }

    /** The crop's stage on a day, and the day said the board's way ("DAT 21"); [null, null] before the field has the crop. */
    private static function stageOn(array $c, Carbon $d): array
    {
        $lot = $c['lot'];
        if (! $c['crop']) {
            return [null, null];
        }
        if ($c['tree']) {
            $age = LotCalendar::ageOf($lot, $d, $c['zero'], $c['tr']);
            if (! $age) {
                return [null, null];
            }

            return [CropStages::stageFor($c['crop'], $age['day'], 'AGE'), LotCalendar::says($age)];
        }
        if (! $c['anchor'] || $d->lt($c['anchor'])) {
            // A transplanted lot's seedbed days: the seedlings are not in this field yet.
            return [null, null];
        }
        $day = (int) $c['anchor']->diffInDays($d);
        $counter = $c['counter'];
        $stageDay = $lot->stageDay(['day' => $day, 'counter' => $counter]);
        $stage = CropStages::stageFor($c['crop'], $stageDay, $counter, $c['maturity']);

        return [$stage, $counter . ' ' . $day];
    }

    /** [level 0 to 3, why] for a stage and a check, from the book. */
    private static function critOf(array $crit, ?string $stage, array $check): array
    {
        if ($stage === null || ! isset($crit[$stage])) {
            return [0, ''];
        }
        $row = $crit[$stage];
        $pick = match ($check['kind']) {
            'nutrient' => $row['nutrients'][$check['nut']] ?? null,
            'threat' => $row['threats'][$check['cat']] ?? null,
            default => $row['threats'][$check['slug']] ?? $row['threats'][$check['cat']] ?? null,
        };
        if (! is_array($pick)) {
            return [0, ''];
        }

        return [max(0, min(3, (int) ($pick[0] ?? 0))), (string) ($pick[1] ?? '')];
    }

    /** gap (critical, uncovered), thin, ok, watch, late (a nutrient the crop no longer takes), extra, pre, none. */
    private static function stateOf(bool $inCrop, int $lvl, float $cov, bool $nutrient): string
    {
        if (! $inCrop) {
            return $cov >= 0.35 ? 'pre' : 'none';
        }
        if ($lvl >= 2) {
            return $cov >= 0.75 ? 'ok' : ($cov >= 0.35 ? 'thin' : 'gap');
        }
        if ($lvl === 1) {
            return $cov >= 0.35 ? 'ok' : 'watch';
        }

        return $cov >= 0.35 ? ($nutrient ? 'late' : 'extra') : 'none';
    }

    /* =============================================================== applications */

    /** Every product line on the lot's tasks: [{date, title, name, done, try, qty, unit, nut (kg/ha by element), ai [names], form, nsrc, ...}]. */
    private static function applications(AsCroppingSchedule $schedule, array $c): array
    {
        $lot = $c['lot'];
        $live = $schedule->lots->where('deleteStatus', 1);
        $allIds = $live->pluck('id')->map(fn ($i) => (int) $i)->all();
        $area = $live->mapWithKeys(fn ($l) => [(int) $l->id => NpkPlan::hectares($l->lotSize, $l->lotSizeUnit)])->all();
        $out = [];
        foreach ($schedule->activities as $act) {
            if (! $act->targetDate || (int) ($act->deleteStatus ?? 1) !== 1 || (int) ($act->isHidden ?? 0) === 1) {
                continue;
            }
            $ids = $act->lots->pluck('id')->map(fn ($i) => (int) $i)->filter(fn ($i) => in_array($i, $allIds, true))->values()->all() ?: $allIds;
            if (! in_array((int) $lot->id, $ids, true)) {
                continue;
            }
            $sizes = array_map(fn ($i) => $area[$i] ?? null, $ids);
            $even = in_array(null, $sizes, true) || array_sum($sizes) <= 0;
            $share = $even ? 1 / count($ids) : ($area[$lot->id] ?? 0) / array_sum($sizes);
            foreach ($act->items as $it) {
                if ($it->itemType === 'service' || (int) ($it->deleteStatus ?? 1) !== 1) {
                    continue;
                }
                $name = trim($it->displayName());
                if ($name === '' || $name === 'Item') {
                    continue;
                }
                // A task the farmer called a foliar spray was sprayed, whatever unit its lines are in (2026-10-30: the
                // planner's own products put on the plan as foliar keep reading as foliar).
                $a = self::read($name, (float) $it->quantity, (string) ($it->unitOfMeasure ?: $it->displayUnit() ?: ''), $share, $c, Carbon::parse($act->targetDate)->startOfDay(),
                    null, $act->activityType === 'foliar_spray' ? 'foliar' : null);
                if (! $a) {
                    continue;
                }
                $out[] = $a + ['title' => trim((string) $act->activityTitle) ?: 'Task', 'done' => (int) ($act->isDone ?? 0) === 1, 'try' => false,
                    'shed' => (bool) $it->inventoryItemId, 'activityId' => (int) $act->id];
            }
        }

        return $out;
    }

    /** A try: a product the farmer is thinking of, on a date. */
    private static function fromTry(array $t, array $c, int $i): ?array
    {
        $p = $t['product'] ?? null;
        $date = isset($t['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $t['date']) ? Carbon::parse($t['date'])->startOfDay() : null;
        if (! is_array($p) || ! $date) {
            return null;
        }
        // How the farmer will put it on (the owner, 2026-10-30: "application selection should be either granular or foliar").
        $method = in_array($t['method'] ?? null, ['granular', 'foliar'], true) ? $t['method'] : null;
        $a = self::read((string) ($p['b'] ?? $p['name'] ?? 'Product'), (float) ($t['qty'] ?? 0), (string) ($t['unit'] ?? ''), 1.0, $c, $date, $p, $method);

        return $a ? $a + ['title' => 'If you apply it', 'done' => false, 'try' => true, 'tryIndex' => $i, 'shed' => false, 'activityId' => null, 'method' => $method] : null;
    }

    /**
     * One product line read: what it carries and what it protects against. $book: a product array from the book
     * (a try), else the line is matched by its name. Null when it is neither a fertilizer nor a crop protection
     * product (labor, fuel, seed...). $method: granular or foliar when the farmer said so (a try), else read from the line.
     */
    private static function read(string $name, float $qty, string $unit, float $share, array $c, Carbon $date, ?array $book = null, ?string $method = null): ?array
    {
        // A name is looked up once per request (a season repeats its products, and every look is a database read).
        static $seen = [];
        if (! $book) {
            $seen[$name] ??= [NpkPlan::match($name), ProductLibrary::find($name)];
            [$npk, $book] = $seen[$name];
        } else {
            $npk = null;
        }
        $u = mb_strtolower(rtrim(trim($unit), '.'));

        // What it carries, kg of each element a hectare of this lot.
        $pct = [];
        if ($npk) {
            $pct = $npk['pct'];
        } elseif ($book && ! empty($book['n'])) {
            foreach ((array) $book['n'] as $k => $v) {
                $k = $k === 'SiO2' ? 'Si' : $k;
                $pct[$k] = (float) $v * ($k === 'Si' && isset($book['n']['SiO2']) ? 0.4675 : 1);
            }
        }
        $salt = false;
        if (! $pct && ! ($npk['estimate'] ?? null)) {
            // A salt under its own name carries what the salt is made of (potassium silicate: potassium and silicon).
            $pct = self::compoundPct($name, $book, $u, $method);
            $salt = (bool) $pct;
        }
        $nut = [];
        $doseKnown = false;
        $prodKgHa = null;
        $estimate = $npk['estimate'] ?? null;
        if ($pct || $estimate) {
            $p = $npk ?: ['unit' => in_array($u, ['l', 'liter', 'liters', 'ml'], true) ? 'L' : 'kg', 'bagKg' => 50, 'density' => 1, 'pct' => $pct, 'estimate' => null];
            $kg = $qty > 0 ? NpkPlan::kg($qty, $unit, $p) : null;
            $ha = $c['ha'] ?: 1.0;
            // The product itself, kg a hectare: what the soil logic reads (its acid, its lime, its gypsum).
            $prodKgHa = $kg !== null && $kg > 0 && ! $estimate ? $kg * $share / $ha : null;
            if ($estimate) {
                foreach ($estimate as $n => $v) {
                    $nut[$n] = (float) $v;
                }
                $doseKnown = true;
            } elseif ($kg !== null && $kg > 0) {
                foreach ($pct as $n => $v) {
                    $nut[$n] = $kg * $share * $v / 100 / $ha;
                }
                $doseKnown = true;
            } else {
                foreach ($pct as $n => $v) {
                    $nut[$n] = null;   // carried, amount not read: counted as one usual dose
                }
            }
        }

        // What it protects against: the book's ingredients, else the ingredient names written in the line.
        $ai = [];
        foreach ((array) ($book['ai'] ?? []) as $x) {
            $n = (string) ($x['n'] ?? '');
            if ($n !== '') {
                $d = CompatBook::ingredient($n);
                $ai[$d['name'] ?? $n] = true;
            }
        }
        if (! $ai && (! $nut || $salt)) {
            foreach (self::ingredientsIn($name) as $n) {
                $ai[$n] = true;
            }
        }
        $ai = array_keys($ai);
        if (! $nut && ! $ai) {
            return null;
        }

        // How it went on: sprayed or poured, slow release, organic, biological, or spread on the soil (before
        // planting it is basal, after it a topdress).
        $cat = (string) ($npk['category'] ?? '');
        $low = mb_strtolower($name . ' ' . ($npk['slug'] ?? '') . ' ' . ($book['b'] ?? ''));
        $soilForm = ($c['anchor'] && $date->lte($c['anchor']->copy()->addDays(3))) || (! $c['anchor']) ? 'basal' : 'topdress';
        $form = match (true) {
            $method === 'foliar' => 'foliar',
            $cat === 'bio' || ($book['t'] ?? '') === 'biological' && $nut => 'bio',
            $cat === 'organic' || preg_match('/manure|compost|vermi|organic fert|ipot/u', $low) === 1 => 'organic',
            preg_match('/controlled|coated|slow.?release|polymer|\bscu\b|\bcrf\b/u', $low) === 1 => 'slow',
            $method === 'granular' => $soilForm,
            $cat === 'foliar' || in_array($u, self::LIQUID_UNITS, true) || in_array((string) ($book['t'] ?? ''), ['foliar', 'soluble'], true) && ! in_array($u, ['kg', 'bag', 'bags', 'sack', 'sacks'], true) => 'foliar',
            default => $soilForm,
        };
        $nsrc = match (true) {
            preg_match('/inhibitor|nbpt|agrotain|dcd|nitrapyrin/u', $low) === 1 => 'inhibitor',
            $form === 'slow' => 'controlled',
            $form === 'organic' => 'manure',
            str_contains($low, 'urea') => 'urea',
            preg_match('/ammonium.?sulfate|ammosul|21-0-0/u', $low) === 1 => 'ammonium sulfate',
            str_contains($low, 'nitrate') => 'nitrate',
            preg_match('/phosphate|\bdap\b|\bmap\b|16-20-0|18-46-0/u', $low) === 1 => 'ammonium phosphate',
            $cat === 'complete' || preg_match('/\b\d{1,2}-\d{1,2}-\d{1,2}\b/u', $low) === 1 => 'complete',
            default => null,
        };
        $label = $npk ? ($npk['name'] . (! empty($npk['local']) && preg_match('/^\d/', (string) $npk['local']) ? ' ' . $npk['local'] : '')) : ($book['b'] ?? $name);

        return ['date' => $date, 'name' => $name, 'as' => $label, 'company' => (string) ($book['c'] ?? ''), 'qty' => $qty, 'unit' => $unit, 'nut' => $nut, 'doseKnown' => $doseKnown,
            'ai' => $ai, 'form' => $form, 'nsrc' => $nsrc, 'kindWord' => $book ? (ProductLibrary::TYPES[$book['t'] ?? ''] ?? null) : ($npk ? 'Fertilizer' : null),
            'pct' => $pct, 'slug' => (string) ($npk['slug'] ?? ''), 'prodKgHa' => $prodKgHa];
    }

    /**
     * Percent of each element in a product made of a known salt: the salt's own share, times how much of the product
     * the salt is (its label amount; all of it for the bare salt, about a third for a bare liquid; a fifth for a
     * brand that names the salt without an amount).
     */
    private static function compoundPct(string $name, ?array $book, string $u, ?string $method): array
    {
        $liquid = in_array($u, ['l', 'liter', 'liters', 'litre', 'litres', 'lt', 'ltr', 'ml', 'cc'], true);
        $parts = [];
        foreach ((array) ($book['ai'] ?? []) as $x) {
            $n = (string) ($x['n'] ?? '');
            if ($n === '') {
                continue;
            }
            $amt = is_numeric($x['a'] ?? null) ? (float) $x['a'] : null;
            $au = mb_strtolower((string) ($x['u'] ?? ''));
            $parts[] = [$n, $amt === null ? null : (str_contains($au, '%') || ($au === '' && $amt <= 100) ? $amt : $amt / 10)];
        }
        $title = trim($name . ' ' . (($book['b'] ?? '') !== $name ? ($book['b'] ?? '') : ''));
        if (! $parts) {
            $parts[] = [$title, null];
        }
        $pct = [];
        foreach ($parts as [$n, $share]) {
            $low = mb_strtolower($n);
            foreach (self::COMPOUNDS as $rx => $els) {
                if (preg_match('/' . $rx . '/u', $low) !== 1) {
                    continue;
                }
                if ($share === null) {
                    $rest = preg_replace('/[^a-z]/', '', (string) preg_replace('/' . $rx . '/u', '', mb_strtolower($title)));
                    $bare = strlen((string) $rest) < 4;
                    $share = $bare ? ($liquid && $method !== 'granular' ? 30 : 100) : 20;
                }
                foreach ($els as $el => $v) {
                    $pct[$el] = ($pct[$el] ?? 0) + $v * min(100, $share) / 100;
                }
                break;
            }
        }

        return $pct;
    }

    /** Active ingredients written in a line ("Prevathon (chlorantraniliprole)"), by the dictionary's own patterns. */
    private static function ingredientsIn(string $text): array
    {
        $up = strtoupper(trim((string) preg_replace('/\s+/', ' ', $text)));
        if (strlen($up) < 4) {
            return [];
        }
        $found = [];
        foreach (CompatBook::ingredients() as $n => $row) {
            foreach ((array) ($row['rx'] ?? []) as $rx) {
                if (@preg_match('#' . str_replace('#', '\#', $rx) . '#u', $up)) {
                    $found[] = $n;
                    break;
                }
            }
        }

        return array_slice($found, 0, 4);
    }

    /**
     * How one application acts on the check: {onset, peak, mid, typ, hi, amp, fade, note, conf, why}, or null when it
     * does not touch it.
     */
    private static function curveFor(array $a, array $check, array $c): ?array
    {
        if ($check['kind'] === 'nutrient') {
            if (! array_key_exists($check['el'], $a['nut'])) {
                return null;
            }
            $kgHa = $a['nut'][$check['el']];
            $ref = ($a['form'] === 'foliar' ? self::REF_FOLIAR : self::REF_SOIL)[$check['el']] ?? 1;
            $ratio = $kgHa === null ? 1.0 : $kgHa / $ref;
            if ($kgHa !== null && $ratio < 0.03 && ! $a['try']) {
                return null;   // a trace on the label, not a dose (a try is always shown, however little)
            }
            $amp = max(0.05, min(1.6, $ratio));
            $prof = RangeEffectBook::nutrient($check['nut'], $a['form'], $c['water']);
            [$on, $pk, $days, $fade] = $prof ? [(int) ($prof['onset'] ?? 0), (int) ($prof['peak'] ?? 3), array_map('intval', (array) $prof['days']), (string) ($prof['fade'] ?? 'gradual')]
                : self::nutrientFallback($check['nut'], $a['form']);
            if ($check['nut'] === 'N' && $a['nsrc']) {
                $f = self::nFactor($a['nsrc'], $c['water']);
                $days = array_map(fn ($x) => (int) max(1, round($x * $f)), $days);
                // Urea spread on the soil or the water first turns into ammonium; ammonium salts only dissolve.
                if (in_array($a['form'], ['basal', 'topdress'], true) && isset(self::N_SOIL_DAYS[$a['nsrc']])) {
                    $on = self::N_SOIL_DAYS[$a['nsrc']][$c['water'] === 'flooded' ? 0 : 1];
                }
            }
            // Your soil (RangeEffectLogic, switched on): sandy soil washes it down sooner, clay holds it longer.
            if (! empty($c['soilF'])) {
                $dm = RangeEffectLogic::daysMult($check['el'], $c['soilF']);
                $days = array_map(fn ($x) => (int) max(1, round($x * $dm)), $days);
            }
            [$lo, $typ, $hi] = self::days3($days);
            $pk = max($pk, $on + ($on > 0 ? 2 : 0));

            return ['onset' => $on, 'peak' => max($on, min($pk, $typ)), 'typ' => $typ, 'hi' => max($typ, $hi), 'lo' => $lo, 'amp' => round($amp, 2), 'fade' => $fade,
                'note' => (string) ($prof['note'] ?? ''), 'conf' => $prof ? 'book' : 'estimate', 'kgHa' => $kgHa, 'ref' => $ref, 'ratio' => round($ratio, 3),
                'words' => self::nutrientWords($check['nut'], $a['form'], $a['nsrc']),
                // Roots taking it up is the crop feeding on it (the owner, 2026-10-30: a basal P on DAS 0 read as a week's
                // gap while its roots were taking it up): counted as covered from the first day of uptake, rising to
                // full; slower for manure, coated and biological, which open up over weeks.
                'rise' => in_array($a['form'], ['organic', 'slow', 'bio'], true) ? 0.35 : 0.75,
                // While it is still in the soil: a phosphorus, potassium or micro salt placed at the roots dissolves within a
                // day or two and counts as there (the owner, 2026-10-30: a P put on at DAS 0 must cover DAS 0); urea turning
                // into ammonium is partly there; manure, coated and biological are not there yet.
                'pre' => in_array($a['form'], ['organic', 'slow', 'bio'], true) ? 0.0 : ($check['nut'] === 'N' && in_array($a['nsrc'], ['urea', 'inhibitor', null], true) ? 0.4 : 0.75)];
        }

        // A threat or a named pest: the longest working of the line's ingredients that act on it.
        $best = null;
        foreach ($a['ai'] as $n) {
            $e = RangeEffectBook::ingredient($n);
            $targets = $e['targets'] ?? null;
            $days = $e['days'] ?? null;
            $move = $e['move'] ?? null;
            $on = (int) ($e['onset'] ?? 0);
            $fade = (string) ($e['fade'] ?? 'gradual');
            $conf = $e ? ($e['_conf'] ?? 'med') : 'estimate';
            if (! $e) {
                $d = CompatBook::ingredient($n);
                $def = self::KIND_DEFAULT[$d['kind'] ?? ''] ?? null;
                if (! $def) {
                    continue;
                }
                [$targets, $move, $days] = $def;
            }
            $hits = $check['kind'] === 'pest' ? in_array($n, $check['ai'] ?? [], true) : in_array($check['cat'], (array) $targets, true);
            if (! $hits) {
                continue;
            }
            [$lo, $typ, $hi] = self::days3(array_map('intval', (array) $days));
            if (! $best || $typ > $best['typ']) {
                // A systemic spray takes a day or two to move through the plant; it guards most of the way from the start.
                $rise = ['systemic' => 2, 'translaminar' => 1][$move] ?? 0;
                $best = ['onset' => $on, 'peak' => max($on, min($on + $rise, $typ)), 'rise' => $rise ? 0.7 : 0, 'typ' => $typ, 'hi' => max($typ, $hi), 'lo' => $lo, 'amp' => 1.0,
                    'fade' => $fade, 'move' => $move, 'note' => (string) ($e['note'] ?? ''), 'conf' => $conf, 'by' => $n, 'words' => self::threatWords((string) $move)];
            }
        }

        return $best;
    }

    /** A nutrient's own words for its first two phases (the rest read the same for every nutrient). */
    private static function nutrientWords(string $nut, string $form, ?string $nsrc): array
    {
        $soil = match (true) {
            $form === 'organic' => 'Breaking down in the soil',
            $form === 'bio' => 'Microbes settling on the roots',
            $form === 'slow' => 'The coating letting it out',
            $form === 'drench' => 'Soaking down to the roots',
            $form === 'foliar' => 'On the leaves, not in yet',
            $nut === 'N' && $nsrc === 'urea' => 'Turning into ammonium in the soil',
            $nut === 'N' && $nsrc === 'inhibitor' => 'Turning into ammonium slowly, held back by the inhibitor',
            $nut === 'N' && in_array($nsrc, ['complete', 'ammonium sulfate', 'ammonium phosphate'], true) => 'Dissolving into ammonium in the soil',
            default => 'Dissolving in the soil water',
        };

        return ['soil' => $soil, 'absorb' => $form === 'foliar' ? 'Leaves taking it in' : 'Roots taking it up', 'strong' => 'Strongest feeding', 'easing' => 'Still feeding, easing',
            'fading' => 'Fading away'];
    }

    /** A spray's words for its phases, by how it moves. */
    private static function threatWords(string $move): array
    {
        $soil = match ($move) {
            'biological' => 'Settling in and spreading',
            'bait' => 'Pests eating the bait, slow to act',
            'systemic' => 'The plant switching it on',
            'translaminar' => 'Soaking into the leaf',
            'pre', 'pre_post', 'soil', 'fumigant' => 'Settling into the soil and water',
            default => 'Slow start, taking hold',
        };

        return ['soil' => $soil, 'absorb' => $move === 'translaminar' ? 'Soaking into the leaf' : 'Moving through the plant', 'strong' => 'Strongest protection',
            'easing' => 'Still protecting, easing', 'fading' => 'Wearing off'];
    }

    /** Where each phase starts and ends, in days after the application: [phase => [first, last]], empty phases left out. */
    private static function phaseSpans(array $k): array
    {
        $on = (int) $k['onset'];
        $pk = (int) $k['peak'];
        $typ = (int) $k['typ'];
        $hi = (int) $k['hi'];
        // As level() draws it: full to 60% of the way from the peak to the usual end, easing to that end, then fading.
        $sharp = ($k['fade'] ?? '') === 'sharp';
        $edge = $sharp ? $typ : (int) floor($pk + ($typ - $pk) * 0.6);
        $out = [];
        foreach (['soil' => [0, $on - 1], 'absorb' => [$on, $pk - 1], 'strong' => [$pk, $edge], 'easing' => [$edge + 1, $typ], 'fading' => [$typ + 1, $hi]] as $p => [$a, $b]) {
            if ($b >= $a) {
                $out[$p] = [$a, $b];
            }
        }

        return $out;
    }

    /** What an application is doing on day t after it went on, or null before it or after it is gone. */
    public static function phaseOf(array $k, int $t): ?string
    {
        foreach (self::phaseSpans($k) as $p => [$a, $b]) {
            if ($t >= $a && $t <= $b) {
                return $p;
            }
        }

        return null;
    }

    /** What a product acts on, as check keys with their names: the nutrients it carries and the threats its ingredients hit. */
    private static function actsOf(array $a): array
    {
        $out = [];
        foreach (array_keys($a['nut']) as $el) {
            foreach (self::NUTRIENTS as $key => [$e, $nm]) {
                if ($e === $el) {
                    $out['n:' . $key] = $nm . ' (' . $key . ')';
                }
            }
        }
        foreach ($a['ai'] as $n) {
            $e = RangeEffectBook::ingredient($n);
            $targets = $e['targets'] ?? (self::KIND_DEFAULT[CompatBook::ingredient($n)['kind'] ?? ''][0] ?? []);
            foreach ((array) $targets as $t) {
                if (isset(self::THREATS[$t])) {
                    $out['t:' . $t] = self::THREATS[$t][0];
                }
            }
        }

        return array_map(fn ($k, $v) => ['key' => $k, 'label' => $v], array_keys($out), $out);
    }

    /** [lo, typ, hi], sorted, never under a day. */
    private static function days3(array $d): array
    {
        $d = array_values(array_map(fn ($x) => max(1, (int) $x), $d)) ?: [7, 10, 14];
        while (count($d) < 3) {
            $d[] = end($d);
        }
        $d = array_slice($d, 0, 3);
        sort($d);

        return $d;
    }

    /** A nutrient's days when the book has none: [onset, peak, [lo, typ, hi], fade]. */
    private static function nutrientFallback(string $nut, string $form): array
    {
        if ($form === 'foliar' || $form === 'drench') {
            return [0, 2, [5, 9, 14], 'sharp'];
        }
        if ($form === 'organic') {
            return [7, 21, [40, 60, 90], 'gradual'];
        }
        if ($form === 'slow') {
            return [3, 14, [45, 60, 90], 'gradual'];
        }
        if ($form === 'bio') {
            return [7, 21, [30, 45, 60], 'gradual'];
        }

        return match ($nut) {
            'N' => [1, 5, [12, 18, 25], 'gradual'],
            'P' => [3, 14, [35, 50, 70], 'gradual'],
            'K' => [2, 10, [25, 35, 50], 'gradual'],
            default => [3, 14, [30, 45, 70], 'gradual'],
        };
    }

    /** How long an N source lasts against urea, from the book's sources. */
    private static function nFactor(string $src, string $water): float
    {
        $norm = fn ($s) => preg_replace('/[^a-z]/', '', mb_strtolower((string) $s));
        $want = $norm($src);
        foreach (RangeEffectBook::sources() as $k => $v) {
            $f = is_array($v) ? ($v[$water] ?? $v['factor'] ?? null) : $v;
            if (is_numeric($f) && ($norm($k) === $want || str_contains($norm($k), $want) || str_contains($want, $norm($k)))) {
                return max(0.3, min(5.0, (float) $f));
            }
        }

        return match ($src) {
            'ammonium sulfate' => 1.2, 'nitrate' => 0.9, 'ammonium phosphate' => 1.2, 'complete' => 1.15, 'inhibitor' => 1.5, 'controlled' => 3.0, 'manure' => 2.5,
            default => 1.0,
        };
    }

    /** The level a curve gives on day t after the application (0 to amp). */
    public static function level(array $k, int $t): float
    {
        $on = $k['onset'];
        $pk = $k['peak'];
        $typ = $k['typ'];
        $hi = $k['hi'];
        if ($t < 0 || $t > $hi) {
            return 0.0;
        }
        // Still in the soil: a salt dissolving is partly there already; manure, coated or biological is not yet.
        if ($t < $on) {
            return (float) ($k['pre'] ?? 0) * (float) $k['amp'];
        }
        if ($t < $pk) {
            $rise = (float) ($k['rise'] ?? 0);
            $v = $rise + (1 - $rise) * ($t - $on + 1) / ($pk - $on + 1);
        } elseif ($t <= $typ) {
            // Full until most of its days have passed, easing as the end comes near.
            $edge = $pk + ($typ - $pk) * 0.6;
            $v = $t <= $edge || ($k['fade'] ?? '') === 'sharp' ? 1.0 : 1 - 0.6 * (($t - $edge) / max(1, $typ - $edge));
        } else {
            $start = ($k['fade'] ?? '') === 'sharp' ? 0.2 : 0.4;
            $v = $start * (1 - ($t - $typ) / max(1, $hi - $typ));
        }

        return max(0.0, $v) * (float) $k['amp'];
    }

    /** One application as the page draws it. */
    private static function appOut(array $a, array $check): array
    {
        $k = $a['curve'];
        $amount = $a['qty'] > 0 ? rtrim(rtrim(number_format($a['qty'], 3, '.', ''), '0'), '.') . ' ' . trim($a['unit']) : '';
        $carries = [];
        foreach ($a['nut'] as $n => $v) {
            $carries[] = $n . ($v !== null ? ' ' . (round($v, $v < 1 ? 2 : 1)) . ' kg/ha' : '');
        }
        $formWord = ['basal' => 'worked into the soil', 'topdress' => 'spread on the crop', 'foliar' => 'sprayed', 'drench' => 'poured at the roots', 'slow' => 'slow release',
            'organic' => 'organic', 'bio' => 'biological'][$a['form']] ?? '';
        $phases = [];
        if ($k) {
            foreach (self::phaseSpans($k) as $p => [$s, $e]) {
                $phases[] = ['p' => $p, 'from' => $a['date']->copy()->addDays($s)->toDateString(), 'to' => $a['date']->copy()->addDays($e)->toDateString(), 'n' => $e - $s + 1,
                    'w' => $k['words'][$p] ?? ''];
            }
        }
        $ratio = $k['ratio'] ?? null;

        return [
            'i' => $a['i'], 'date' => $a['date']->toDateString(), 'title' => $a['title'], 'name' => $a['name'], 'as' => $a['as'], 'company' => $a['company'], 'amount' => $amount,
            'done' => $a['done'], 'try' => $a['try'], 'tryIndex' => $a['tryIndex'] ?? null, 'shed' => $a['shed'], 'kindWord' => $a['kindWord'], 'form' => $a['form'], 'formWord' => $formWord,
            'method' => $a['method'] ?? null, 'carries' => $carries, 'ai' => $a['ai'], 'relevant' => $a['relevant'],
            // A try that misses the check says what it does act on, so the sheet can offer it.
            'acts' => $a['try'] ? self::actsOf($a) : [],
            'effect' => $k ? [
                'from' => $a['date']->copy()->addDays($k['onset'])->toDateString(), 'full' => $a['date']->copy()->addDays($k['typ'])->toDateString(),
                'until' => $a['date']->copy()->addDays($k['hi'])->toDateString(), 'days' => [$k['lo'], $k['typ'], $k['hi']], 'amp' => $k['amp'],
                'dose' => $check['kind'] === 'nutrient' ? ($a['doseKnown'] ? ($ratio !== null && $ratio < 0.15 ? 'trace' : ($k['amp'] >= 1.25 ? 'heavy' : ($k['amp'] >= 0.75 ? 'full' : 'light'))) : 'unknown') : null,
                'kgHa' => isset($k['kgHa']) ? round((float) $k['kgHa'], $k['kgHa'] < 1 ? 2 : 1) : null, 'ref' => $k['ref'] ?? null, 'el' => $check['el'] ?? null,
                'note' => $k['note'], 'conf' => $k['conf'], 'by' => $k['by'] ?? null, 'move' => $k['move'] ?? null, 'phases' => $phases,
            ] : null,
        ];
    }

    /** What the calendar says, in numbers and runs: coverage of the critical days, the gaps, the late ones. */
    private static function summary(array $days, array $stages, array $apps, array $check, array $lateNotes, array $c): array
    {
        $crit = 0;
        $covered = 0;
        $thin = 0;
        $runs = ['gap' => [], 'thin' => [], 'late' => [], 'extra' => []];
        $open = [];
        foreach ($days as $i => $d) {
            if ($d['c'] >= 2 && in_array($d['s'], ['ok', 'thin', 'gap'], true)) {
                $crit++;
                $covered += $d['s'] === 'ok' ? 1 : 0;
                $thin += $d['s'] === 'thin' ? 1 : 0;
            }
            foreach (array_keys($runs) as $s) {
                if ($d['s'] === $s) {
                    if (! isset($open[$s])) {
                        $open[$s] = ['from' => $d['d'], 'to' => $d['d'], 'g' => $d['g'], 'c' => $d['c'], 'n' => 0];
                    }
                    $open[$s]['to'] = $d['d'];
                    $open[$s]['n']++;
                    $open[$s]['c'] = max($open[$s]['c'], $d['c']);
                } elseif (isset($open[$s])) {
                    $runs[$s][] = $open[$s];
                    unset($open[$s]);
                }
            }
        }
        foreach ($open as $s => $r) {
            $runs[$s][] = $r;
        }
        foreach ($runs as $s => &$list) {
            $list = array_map(fn ($r) => $r + ['stage' => $stages[$r['g']]['label'] ?? '', 'why' => $stages[$r['g']]['why'] ?? ''], $list);
        }
        unset($list);
        $relevant = array_values(array_filter($apps, fn ($a) => $a['relevant']));
        $late = $check['kind'] === 'nutrient' ? (string) ($lateNotes[$check['nut']] ?? '') : '';

        return [
            'critDays' => $crit, 'coveredDays' => $covered, 'thinDays' => $thin, 'gapDays' => array_sum(array_column($runs['gap'], 'n')),
            'pct' => $crit ? (int) round(100 * ($covered + 0.5 * $thin) / $crit) : null,
            'gaps' => array_slice($runs['gap'], 0, 8), 'thin' => array_slice($runs['thin'], 0, 6), 'late' => array_slice($runs['late'], 0, 6), 'extra' => array_slice($runs['extra'], 0, 6),
            'lateNote' => $late, 'apps' => count($relevant), 'tries' => count(array_filter($relevant, fn ($a) => $a['try'])),
            'other' => count(array_filter($apps, fn ($a) => ! $a['relevant'])),
        ];
    }

    /** A what-if lot, cleaned: its name, its place (for the area's soil and weather) and its field answers. */
    public static function customOf(array $in): array
    {
        $answers = is_array($in['answers'] ?? null) ? FieldProfile::normalize($in['answers'], null) : [];

        return ['name' => mb_substr(trim((string) ($in['name'] ?? '')), 0, 80) ?: 'What if', 'place' => mb_substr(trim((string) ($in['place'] ?? '')), 0, 160),
            'answers' => $answers, 'farm' => ! empty($in['farm'])];
    }

    /** A product the book holds, by id, as a try reads it. */
    public static function productOf(int $id): ?array
    {
        return ProductLibrary::many([$id])[$id] ?? null;
    }
}
