<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * One work calendar across a farm's lots (2026-10-18, the owner: "add a
 * question on how many workers you have (select those workers), and if auto
 * adjust schedule if there's conflicting or warn if # of workers is lacking
 * ... analyze deeply to avoid conflicts in schedule").
 *
 * Every lot of a crop protocol becomes a list of field jobs on its own day
 * count (the seedbed, the planting, each fertilizer application, the weed
 * control, the foliar sprays, the harvest), each with the person days it
 * takes for that lot's size and a SAFE WINDOW: the days around its usual day
 * the crop still takes it well (a topdressing within its stage, a basal a few
 * days either side of planting, a harvest from a few days early to a week
 * late). Then the farm's crew is laid over all of them, day by day, from each
 * worker's own work days (Global Workers: their days off) and the sprayers.
 *
 *  - "auto": Anee moves things so that no day ever asks more hands (or more
 *    sprayers) than the farm has. A lot's planting day may move within its
 *    month (or up to two weeks after the day the farmer picked), and each job
 *    may move inside its safe window. Work that still does not fit is late,
 *    and the extra workers that would fix it are counted.
 *  - "keep": every day stays where the farmer put it and every job on its
 *    usual day; the days that ask more workers than the farm has are listed
 *    with how many are short, and the crew that would cover them.
 *
 * Plain arithmetic, no AI: the same answers always give the same calendar,
 * and the page shows it before anything is paid for (CropProtocolController
 * need() with check). Person days a hectare follow the Land Prep Planner
 * (LandPrepPlanner::PLANTING: 20 planters and 8 pullers a hectare for hand
 * transplanted rice) and common Philippine practice for the rest. A guide:
 * the farmer's own crew knows its pace, and the report says so.
 */
final class ProtocolCrew
{
    public const MODES = ['auto', 'keep'];

    /** Person days a hectare to get the crop in, by how it goes in: [pd, who]. */
    private const PLANT = [
        'transplanted' => [28, 'people to pull and transplant the seedlings'],
        'direct_wet' => [1, 'people to sow the sprouted seed'],
        'direct_dry' => [2, 'people to sow the seed'],
        'nursery' => [12, 'people to set out the seedlings'],
        'sets' => [30, 'people to plant the sets or cloves'],
        'cuttings' => [8, 'people to plant the cuttings'],
        'tubers' => [10, 'people to plant the seed pieces'],
        'suckers' => [6, 'people to plant the suckers'],
        'sprouted' => [3, 'people to plant'],
        'tree_seedlings' => [3, 'people to plant the seedlings'],
    ];

    /** Sowing in place, by the crop's group (corn and beans are dibbled; leafy seed is drilled in rows). */
    private const DIRECT = ['Cereals & grains' => 4, 'Legumes' => 4, 'Leafy vegetables' => 6, 'Fruit vegetables' => 4, 'Root crops' => 6,
        'Onions & garlic' => 8, 'Industrial crops' => 4, 'Fruit trees' => 3, 'Other' => 4];

    /** Crops whose planting is their own. */
    private const PLANT_CROP = ['sugarcane' => 10, 'pineapple' => 20, 'banana' => 4, 'cotton' => 4, 'garlic' => 30, 'shallot' => 30];

    /** Days a seedling spends in the seedbed or tray before it is set out. */
    private const SEED_AGE = ['rice' => 21, 'onion' => 40, 'tobacco' => 40, 'celery' => 45, 'papaya' => 45, 'cabbage' => 25, 'broccoli' => 25, 'cauliflower' => 25,
        'tomato' => 28, 'eggplant' => 28, 'chili' => 28, 'bellpepper' => 28, 'lettuce' => 21, 'pechay' => 18, 'mustard' => 18,
        'ampalaya' => 14, 'cucumber' => 14, 'watermelon' => 14, 'melon' => 14, 'strawberry' => 30];

    /** Harvest: [person days a hectare, days early it may start, days late it may run]. */
    private const HARVEST_GROUP = ['Cereals & grains' => [15, 3, 7], 'Legumes' => [15, 3, 7], 'Leafy vegetables' => [15, 3, 7], 'Fruit vegetables' => [30, 3, 35],
        'Root crops' => [15, 5, 14], 'Onions & garlic' => [20, 3, 10], 'Industrial crops' => [20, 5, 14], 'Fruit trees' => [10, 5, 14], 'Other' => [12, 5, 10]];

    private const HARVEST_CROP = ['corn_yellow' => [10, 3, 10], 'corn_glutinous' => [10, 3, 7], 'corn_sweet' => [12, 2, 4], 'sorghum' => [10, 3, 10], 'sugarcane' => [25, 7, 21],
        'tobacco' => [30, 3, 35], 'banana' => [6, 7, 14]];

    /** Weeding by hand after the herbicide window, by group: [usual day, from, to, person days a hectare]. */
    private const WEED_GROUP = ['Cereals & grains' => [25, 21, 35, 3], 'Legumes' => [18, 14, 28, 5], 'Leafy vegetables' => [16, 12, 25, 8], 'Fruit vegetables' => [18, 14, 30, 8],
        'Root crops' => [30, 21, 45, 6], 'Onions & garlic' => [25, 21, 40, 10], 'Industrial crops' => [30, 21, 45, 5], 'Fruit trees' => [45, 30, 60, 4], 'Other' => [25, 20, 40, 5]];

    /** One person sprays about a hectare a day with a knapsack (16 liters, 12 to 16 loads). */
    public const SPRAY_PD = 1.0;

    /**
     * Machines that plant or harvest (2026-10-21, the owner: who plants and who harvests, lot by lot, with
     * machines as the Land Prep Planner offers them). crops: crop keys or their starts ("corn" for every
     * corn), methods: the ways in it serves (null for any), pd: person days a hectare the farm's own
     * workers put in running the farm's own machine; a hired one comes with its operator.
     */
    public const MACHINES = [
        'transplanter' => ['kind' => 'plant', 'name' => 'Rice transplanter', 'line' => 'Plants tray seedlings in rows', 'crops' => ['rice'], 'methods' => ['transplanted'], 'pd' => 3.0],
        'seeder' => ['kind' => 'plant', 'name' => 'Drum or palatak seeder', 'line' => 'Sows sprouted seed in rows, pulled by hand', 'crops' => ['rice'], 'methods' => ['direct_wet'], 'pd' => 1.5],
        'planter' => ['kind' => 'plant', 'name' => 'Corn planter or seed drill', 'line' => 'Drops the seed in rows as it is pulled', 'crops' => ['corn', 'sorghum', 'mungbean', 'peanut', 'soybean', 'rice'],
            'methods' => ['direct', 'direct_dry'], 'pd' => 1.0],
        'combine' => ['kind' => 'harvest', 'name' => 'Combine harvester', 'line' => 'Cuts and threshes in one pass', 'crops' => ['rice', 'corn_yellow', 'corn_white', 'corn_glutinous', 'sorghum'], 'methods' => null, 'pd' => 0.7],
        'reaper' => ['kind' => 'harvest', 'name' => 'Reaper and thresher', 'line' => 'Cuts the rows; the crew gathers and threshes', 'crops' => ['rice'], 'methods' => null, 'pd' => 6.0],
    ];

    /** Whether a machine serves this crop and way in. */
    public static function fits(string $machine, string $crop, string $method): bool
    {
        $m = self::MACHINES[$machine] ?? null;
        if (! $m) {
            return false;
        }
        $crops = array_filter($m['crops'], fn ($c) => $crop === $c || str_starts_with($crop, $c . '_') || ($c === 'rice' && str_starts_with($crop, 'rice')));

        return $crops !== [] && ($m['methods'] === null || in_array($method, $m['methods'], true));
    }

    /**
     * Who does a lot's planting or harvest: the farm's own workers by hand, a hired crew, or a machine,
     * the farm's own (its workers run it) or hired (it comes with its operator, so none of the farm's).
     *
     * @return array{by:string, machine:?string, hired:bool, pd:?float, name:?string}
     */
    public static function doneBy(string $kind, array $field, string $crop, string $method): array
    {
        $by = (string) ($field[$kind . 'By'] ?? 'own');
        if ($by === 'hired') {
            return ['by' => 'hired', 'machine' => null, 'hired' => true, 'pd' => null, 'name' => null];
        }
        $m = self::MACHINES[$by] ?? null;
        if ($m && $m['kind'] === $kind && self::fits($by, $crop, $method)) {
            return ['by' => 'machine', 'machine' => $by, 'hired' => empty($field[$kind . 'Own']), 'pd' => (float) $m['pd'], 'name' => $m['name']];
        }

        return ['by' => 'own', 'machine' => null, 'hired' => false, 'pd' => null, 'name' => null];
    }

    /* ============================================================ a lot's frame */

    /**
     * A lot's calendar, without dates: its stages in days on its own count
     * (a tree's months turned into days), the planting day as day 0, the
     * seedbed before it, and the day of the harvest.
     *
     * @param  array{crop:string, method:string, days?:?float, areaHa?:float}  $lot
     */
    public static function frame(array $lot): array
    {
        $crop = (string) $lot['crop'];
        $method = (string) $lot['method'];
        $counter = \App\Http\Controllers\CropProtocolController::counterFor($method, $crop);
        $perennial = CropCatalog::isPerennial($crop);
        $group = CropCatalog::CROPS[$crop]['group'] ?? 'Other';
        $nursery = in_array($method, ['transplanted', 'nursery'], true);
        $seedAge = $nursery ? (self::SEED_AGE[$crop] ?? (str_starts_with($crop, 'rice') ? 21 : 25)) : null;
        // The variety's own days to harvest when the record has them (rice counts them from sowing).
        $days = (float) ($lot['days'] ?? 0);
        $maturity = $days >= 30 && $days <= 500 ? (int) round($days) : CropCatalog::maturity($crop);
        $tableMaturity = $maturity && $seedAge && $counter === 'DAT' && str_starts_with($crop, 'rice') ? $maturity - $seedAge : $maturity;
        $table = CropStages::stagesFor($crop, $counter, $tableMaturity ?: null);
        $mult = $perennial ? 30 : 1;
        $stages = [];
        foreach (array_values($table) as $i => $st) {
            $from = (int) $st[0] * $mult;
            if ($perennial && $from > 365) {
                break;   // a tree's first year
            }
            $stages[] = ['name' => (string) $st[1], 'from' => $from, 'what' => (string) ($st[2] ?? ''), 'needs' => (string) ($st[3] ?? '')];
        }
        foreach ($stages as $i => &$s) {
            $s['to'] = isset($stages[$i + 1]) ? max($s['from'], $stages[$i + 1]['from'] - 1) : null;
        }
        unset($s);
        // The harvest: the variety's days, on this count (transplanted rice spends its seedbed days before day 0).
        $harvest = null;
        if (! $perennial && $maturity) {
            $harvest = str_starts_with($crop, 'rice') && $seedAge && $counter === 'DAT' ? $maturity - $seedAge : $maturity;
            $last = end($stages);
            // A short variety still harvests inside the last stage, never before it starts.
            if ($last && $harvest < $last['from'] + 3) {
                $harvest = $last['from'] + 3;
            }
        }
        if ($stages) {
            $n = count($stages) - 1;
            $stages[$n]['to'] = $harvest !== null ? max($stages[$n]['from'], $harvest + 7) : $stages[$n]['from'] + ($perennial ? 60 : 20);
        }
        // Before day 0: the basal goes in at the last harrowing.
        array_unshift($stages, ['name' => 'Land preparation', 'from' => -14, 'to' => -1, 'what' => 'The field is plowed and harrowed.', 'needs' => 'The basal fertilizer goes in at the last harrowing.', 'prep' => true]);

        return [
            'crop' => $crop, 'method' => $method, 'counter' => $counter, 'perennial' => $perennial, 'group' => $group,
            'stages' => $stages, 'harvest' => $harvest, 'seedAge' => $seedAge, 'maturity' => $maturity,
            'areaHa' => max(0.001, (float) ($lot['areaHa'] ?? 1)),
        ];
    }

    /** The stage a day falls in (by index), or the nearest one. */
    public static function stageAt(array $frame, int $day): int
    {
        $at = 0;
        foreach ($frame['stages'] as $i => $s) {
            if ($day >= $s['from']) {
                $at = $i;
            }
        }

        return $at;
    }

    /** The stage a written name means: exact, then contained, then by its words; -1 when none. */
    public static function stageIndex(array $frame, string $name): int
    {
        $norm = fn (string $t) => trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($t)));
        $n = $norm($name);
        if ($n === '') {
            return -1;
        }
        foreach ($frame['stages'] as $i => $s) {
            if ($norm($s['name']) === $n) {
                return $i;
            }
        }
        foreach ($frame['stages'] as $i => $s) {
            $sn = $norm($s['name']);
            if ($sn !== '' && (str_contains($sn, $n) || str_contains($n, $sn))) {
                return $i;
            }
        }
        // "Basal", "at planting", "before planting": the land preparation or day 0.
        if (preg_match('/\b(basal|before|land prep|harrow|pre ?plant)/', $n)) {
            return 0;
        }
        if (preg_match('/\b(planting|transplant|sowing|seeding)\b/', $n)) {
            return min(1, count($frame['stages']) - 1);
        }
        $words = array_filter(explode(' ', $n), fn ($w) => strlen($w) > 3);
        foreach ($frame['stages'] as $i => $s) {
            foreach ($words as $w) {
                if (str_contains($norm($s['name']), $w)) {
                    return $i;
                }
            }
        }

        return -1;
    }

    /** The day an application goes on: its own number when it sits in its stage, else early in the stage. */
    public static function dayFor(array $frame, string $stage, mixed $day = null): int
    {
        $i = self::stageIndex($frame, $stage);
        $s = $frame['stages'][max(0, $i)] ?? ['from' => 0, 'to' => 0];
        $from = (int) $s['from'];
        $to = $s['to'] !== null ? (int) $s['to'] : $from + 14;
        if (is_numeric($day)) {
            $d = (int) round((float) $day) * ($frame['perennial'] && (float) $day < 60 && $from >= 60 ? 30 : 1);
            if ($i < 0 || ($d >= $from - 3 && $d <= $to + 3)) {
                return max(-14, $d);
            }
        }
        if ($i === 0) {
            return -1;   // the last harrowing
        }

        return $from + ($from === 0 ? 0 : min(3, max(0, intdiv($to - $from, 4))));
    }

    /* ============================================================ the jobs */

    public static function plantRate(string $crop, string $method): array
    {
        if (isset(self::PLANT_CROP[$crop])) {
            return [self::PLANT_CROP[$crop], self::PLANT[$method][1] ?? 'people to plant'];
        }
        if ($method === 'direct') {
            return [self::DIRECT[CropCatalog::CROPS[$crop]['group'] ?? 'Other'] ?? 4, 'people to sow'];
        }
        if ($method === 'transplanted' && ! str_starts_with($crop, 'rice')) {
            return self::PLANT['nursery'];
        }

        return self::PLANT[$method] ?? [4, 'people to plant'];
    }

    /** Person days a hectare for one fertilizer application, by how the crop takes it. */
    public static function fertRate(string $crop): float
    {
        $group = CropCatalog::CROPS[$crop]['group'] ?? 'Other';
        if (str_starts_with($crop, 'rice') || $crop === 'sorghum') {
            return 1.0;   // broadcast into shallow water or worked in
        }
        if (str_starts_with($crop, 'corn')) {
            return 2.0;   // side dressed along the rows
        }

        return match ($group) {
            'Legumes' => 1.5,
            'Fruit vegetables', 'Leafy vegetables' => 3.0,
            default => 2.0,
        };
    }

    /**
     * The usual field jobs for a lot when no program is written yet (the
     * preview before the run): the basal, then a topdressing at every stage
     * whose own words ask for fertilizer, as the Growth Stages module reads it.
     *
     * @return list<array{stage:string, day:int, what:string}>
     */
    public static function skeleton(array $frame): array
    {
        $apps = [['stage' => $frame['stages'][1]['name'] ?? 'Planting', 'day' => 0, 'what' => 'Basal fertilizer']];
        foreach ($frame['stages'] as $i => $s) {
            if ($i < 2 || ! empty($s['prep'])) {
                continue;
            }
            if (preg_match('/nitrogen|fertili[sz]|side ?dress|top ?dress|feed/i', $s['needs'] . ' ' . $s['what'])) {
                $apps[] = ['stage' => $s['name'], 'day' => self::dayFor($frame, $s['name']), 'what' => 'Topdressing'];
            }
        }
        if (count($apps) === 1 && $frame['harvest']) {
            foreach ([0.22, 0.45] as $f) {
                $d = (int) round($frame['harvest'] * $f);
                $apps[] = ['stage' => $frame['stages'][self::stageAt($frame, $d)]['name'] ?? '', 'day' => $d, 'what' => 'Topdressing'];
            }
        }
        if ($frame['perennial']) {
            $apps = [['stage' => $frame['stages'][1]['name'] ?? 'Planting', 'day' => 0, 'what' => 'Fertilizer in the planting hole']];
            foreach ([90, 180, 270] as $d) {
                $apps[] = ['stage' => $frame['stages'][self::stageAt($frame, $d)]['name'] ?? '', 'day' => $d, 'what' => 'Fertilizer around each plant'];
            }
        }

        return $apps;
    }

    /**
     * Every field job of a lot, on its own day count.
     *
     * @param  list<array{stage:string, day?:mixed, what?:string, products?:list<string>}>  $apps   the fertilizer applications (the recommended program, or skeleton())
     * @param  list<array{stage:string, what:string}>  $sprays   foliar sprays to plan (micronutrients)
     * @param  array{weedy?:?bool}  $field   what the lot said of its weeds (null: not said)
     * @return list<array>
     */
    public static function tasks(array $frame, array $apps, array $sprays = [], array $field = []): array
    {
        $area = (float) $frame['areaHa'];
        $crop = $frame['crop'];
        $group = $frame['group'];
        $out = [];
        $add = function (string $key, string $kind, string $title, int $day, int $lo, int $hi, float $pdHa, array $more = []) use (&$out, $area) {
            $out[] = $more + ['key' => $key, 'kind' => $kind, 'title' => $title, 'day' => $day, 'lo' => min($lo, $day), 'hi' => max($hi, $day),
                'pd' => round($pdHa * $area, 2), 'pdHa' => $pdHa, 'spray' => $kind === 'spray' || $kind === 'herb', 'what' => $more['what'] ?? ''];
        };
        // The seedbed or the tray, before day 0.
        if ($frame['seedAge']) {
            $rice = str_starts_with($crop, 'rice');
            $add('seedbed', 'seedbed', $rice ? 'Sow the seedbed' : 'Sow the seedling trays', -$frame['seedAge'], -$frame['seedAge'] - 2, -$frame['seedAge'] + 2, $rice ? 1.0 : 2.0,
                ['what' => $rice ? 'Prepare the seedbed and sow the soaked, sprouted seed.' : 'Fill the trays and sow.']);
        }
        // The planting itself: its work may spread over the first days (seedlings wait a day or two).
        [$plantPd, $who] = self::plantRate($crop, $frame['method']);
        // Who plants (2026-10-21): the farm's workers by hand, a hired crew, or a machine.
        $pb = self::doneBy('plant', $field, $crop, (string) $frame['method']);
        if ($pb['by'] === 'machine') {
            $plantPd = $pb['pd'];
            $who = $pb['hired'] ? 'the hired ' . mb_strtolower((string) $pb['name']) . ' and its operator' : 'people running the ' . mb_strtolower((string) $pb['name']);
        } elseif ($pb['by'] === 'hired') {
            $who = 'a hired planting crew';
        }
        $plantWord = match (true) {
            $frame['method'] === 'transplanted' || $frame['method'] === 'nursery' => 'Transplant',
            str_starts_with($frame['method'], 'direct') => 'Sow',
            default => 'Plant',
        };
        $add('plant', 'plant', $plantWord, 0, 0, $frame['seedAge'] ? 3 : 2, (float) $plantPd, ['who' => $who, 'what' => ucfirst($who) . '.', 'hired' => $pb['hired'], 'machine' => $pb['machine']]);
        // The fertilizer, one job a day (products on one day go on in one pass).
        $byDay = [];
        foreach ($apps as $ap) {
            $d = self::dayFor($frame, (string) ($ap['stage'] ?? ''), $ap['day'] ?? null);
            $byDay[$d][] = $ap;
        }
        ksort($byDay);
        $n = 0;
        foreach ($byDay as $d => $list) {
            $i = self::stageAt($frame, $d);
            $s = $frame['stages'][$i];
            $basal = $d <= 3;
            $lo = $basal ? $d - 3 : max((int) $s['from'] - 2, $d - 4);
            $hi = $basal ? $d + 5 : min($s['to'] !== null ? (int) $s['to'] : $d + 4, $d + 4);
            $products = array_values(array_unique(array_merge(...array_map(fn ($a) => (array) ($a['products'] ?? []), $list))));
            $words = array_values(array_unique(array_filter(array_map(fn ($a) => (string) ($a['what'] ?? ''), $list))));
            $n++;
            $add('fert' . $n, 'fert', $basal ? 'Basal fertilizer' : 'Fertilizer at ' . mb_strtolower($s['name']), $d, $lo, max($hi, $d + 1), self::fertRate($crop),
                ['stage' => $s['name'], 'products' => $products, 'what' => $products ? implode(', ', $products) : implode('; ', $words)]);
        }
        // Weed control: a pre emergence spray where the lot was weedy (or did not say), then the weeding.
        $weedy = $field['weedy'] ?? null;
        if (! $frame['perennial']) {
            if ($weedy !== false) {
                $rice = str_starts_with($crop, 'rice');
                $add('herb', 'herb', 'Pre emergence herbicide', $rice ? 2 : 1, 0, $rice ? 5 : 3, self::SPRAY_PD, ['what' => 'Only on a field that had many weeds. Read the label first.']);
            }
            [$wd, $wlo, $whi, $wpd] = self::WEED_GROUP[$group] ?? self::WEED_GROUP['Other'];
            if (str_starts_with($crop, 'rice')) {
                [$wd, $wlo, $whi, $wpd] = [20, 15, 30, 4];
            }
            if ($frame['harvest']) {
                $cap = max(10, (int) round($frame['harvest'] * 0.55));
                $whi = min($whi, $cap);
                $wd = min($wd, $whi);
                $wlo = min($wlo, $wd);
            }
            $add('weed', 'weed', 'Weeding', $wd, $wlo, $whi, $weedy === false ? max(1.5, $wpd / 2) : (float) $wpd, ['what' => 'By hand or with a weeder, between the rows.']);
        } else {
            $add('weed', 'weed', 'Ring weeding', 45, 30, 60, 4.0, ['what' => 'Clear a ring around each plant.']);
            $add('weed2', 'weed', 'Ring weeding', 135, 110, 160, 4.0, ['what' => 'Clear a ring around each plant.']);
        }
        // Foliar sprays planned for a nutrient.
        foreach (array_values($sprays) as $k => $sp) {
            $d = self::dayFor($frame, (string) ($sp['stage'] ?? ''), $sp['day'] ?? null);
            $add('spray' . ($k + 1), 'spray', (string) ($sp['title'] ?? 'Foliar spray'), $d, $d - 3, $d + 4, self::SPRAY_PD, ['stage' => $sp['stage'] ?? '', 'what' => (string) ($sp['what'] ?? '')]);
        }
        // The harvest.
        if ($frame['harvest']) {
            [$hpd, $early, $late] = self::HARVEST_CROP[$crop] ?? (self::HARVEST_GROUP[$group] ?? self::HARVEST_GROUP['Other']);
            // Who harvests (2026-10-21): the farm's workers, a hired crew, or a combine or a reaper.
            $hb = self::doneBy('harvest', $field, $crop, (string) $frame['method']);
            $hwhat = $late > 20 ? 'Picking by picking, over several weeks.' : 'Cut and gather; threshing by machine is not counted.';
            if ($hb['by'] === 'machine') {
                $hpd = $hb['pd'];
                $hwhat = $hb['hired'] ? 'The hired ' . mb_strtolower((string) $hb['name']) . ' comes with its operator.' : 'Your workers run the ' . mb_strtolower((string) $hb['name']) . '.';
            } elseif ($hb['by'] === 'hired') {
                $hwhat = 'A hired harvest crew. ' . $hwhat;
            }
            $add('harvest', 'harvest', 'Harvest', (int) $frame['harvest'], (int) $frame['harvest'] - $early, (int) $frame['harvest'] + $late, (float) $hpd,
                ['what' => $hwhat, 'hired' => $hb['hired'], 'machine' => $hb['machine']]);
        }
        usort($out, fn ($a, $b) => $a['day'] <=> $b['day']);

        return $out;
    }

    /* ============================================================ the crew */

    /**
     * The crew as the scheduler reads it: each worker's weekdays (0 Sunday to
     * 6 Saturday), the knapsack sprayers, and who plants and harvests.
     */
    public static function crewOf(array $raw): array
    {
        $workers = [];
        foreach (array_slice(array_values((array) ($raw['workers'] ?? [])), 0, 100) as $i => $w) {
            $days = array_values(array_unique(array_filter(array_map('intval', (array) ($w['days'] ?? [])), fn ($d) => $d >= 0 && $d <= 6)));
            sort($days);
            $workers[] = ['name' => mb_substr(trim((string) ($w['name'] ?? '')), 0, 40) ?: 'Worker ' . ($i + 1), 'days' => $days,
                'globalId' => isset($w['globalId']) && (int) $w['globalId'] > 0 ? (int) $w['globalId'] : null];
        }
        if (! $workers) {
            $workers[] = ['name' => 'Worker 1', 'days' => [1, 2, 3, 4, 5, 6], 'globalId' => null];
        }

        return [
            'workers' => $workers,
            'sprayers' => max(1, min(50, (int) ($raw['sprayers'] ?? 1))),
            'plantBy' => ($raw['plantBy'] ?? 'own') === 'hired' ? 'hired' : 'own',
        ];
    }

    /** Workers on each weekday, 0 (Sunday) to 6. */
    private static function weekCap(array $crew): array
    {
        $cap = array_fill(0, 7, 0);
        foreach ($crew['workers'] as $w) {
            foreach ($w['days'] as $d) {
                $cap[$d]++;
            }
        }

        return $cap;
    }

    private static function dn(string $date): int
    {
        return intdiv(Carbon::parse($date, 'UTC')->startOfDay()->getTimestamp(), 86400);
    }

    private static function ds(int $n): string
    {
        return gmdate('Y-m-d', $n * 86400);
    }

    private static function wd(int $n): int
    {
        return ($n + 4) % 7;   // 1970-01-01 was a Thursday
    }

    /**
     * The calendar for the whole farm.
     *
     * @param  list<array{i:int, name:string, month:string, asked:?string, tasks:list<array>}>  $lots
     * @return array the plan: each lot's planting day, every job with its days, the busy days, the short days, the late jobs, the crew that would fit
     */
    public static function schedule(array $lots, array $crew, string $mode, ?string $today = null, bool $advise = true): array
    {
        $mode = in_array($mode, self::MODES, true) ? $mode : 'auto';
        $crew = self::crewOf($crew);
        $today ??= now('Asia/Manila')->toDateString();
        $t0 = self::dn($today);
        $cap = self::weekCap($crew);
        $spr = (int) $crew['sprayers'];
        $hiredKinds = $crew['plantBy'] === 'hired' ? ['plant', 'harvest'] : [];
        $used = [];
        $usedS = [];
        $who = [];
        $out = ['mode' => $mode, 'today' => $today, 'lots' => [], 'tasks' => []];

        // The days a lot may be planted on: its own day (and two weeks after it, when Anee may move it), else its month from tomorrow.
        $window = function (array $l) use ($t0, $mode): array {
            if (! empty($l['asked'])) {
                $a = max($t0 + 1, self::dn($l['asked']));

                return [$a, $mode === 'auto' ? $a + 14 : $a];
            }
            $m = Carbon::parse(($l['month'] ?? substr(self::ds($t0 + 1), 0, 7)) . '-01', 'UTC');
            $from = max($t0 + 1, self::dn($m->toDateString()));
            $to = max($from, self::dn($m->copy()->endOfMonth()->toDateString()));

            // Kept dates with no day picked: the first day of the month the crew works.
            return [$from, $mode === 'auto' ? $to : min($to, $from + 6)];
        };
        $order = array_keys($lots);
        usort($order, function ($a, $b) use ($lots, $window) {
            return $window($lots[$a])[0] <=> $window($lots[$b])[0] ?: $a <=> $b;
        });

        // Lay one lot's jobs from a planting day into the days left. $commit writes them.
        $place = function (array $l, int $base, bool $commit) use (&$used, &$usedS, &$who, $cap, $spr, $hiredKinds, $mode): array {
            $u = $used;
            $us = $usedS;
            $placed = [];
            $short = 0.0;
            foreach ($l['tasks'] as $t) {
                $need = (float) $t['pd'];
                $days = [];
                // A hired crew or a hired machine: the whole farm's (old pages) or this lot's job (2026-10-21).
                $hired = in_array($t['kind'], $hiredKinds, true) || ! empty($t['hired']);
                if ($hired || $need <= 0) {
                    $placed[] = array_merge($t, ['hired' => $hired, 'days' => [[$base + $t['day'], $need]], 'left' => 0.0]);
                    continue;
                }
                if ($mode === 'keep') {
                    // Each job on its own days, as if alone: the clash is read afterwards.
                    $d = $base + $t['day'];
                    for ($k = 0; $need > 0.005 && $k < 120; $k++, $d++) {
                        $c = $cap[self::wd($d)];
                        if ($t['spray']) {
                            $c = min($c, $spr);
                        }
                        if ($c <= 0) {
                            continue;
                        }
                        $take = min($need, (float) $c);
                        $days[] = [$d, round($take, 3)];
                        $need -= $take;
                    }
                    $placed[] = $t + ['hired' => false, 'days' => $days, 'left' => 0.0];
                    continue;
                }
                // Auto: from its usual day to the end of its window, then back toward the start.
                $seq = array_merge(range($base + $t['day'], $base + $t['hi']), $t['lo'] < $t['day'] ? range($base + $t['day'] - 1, $base + $t['lo']) : []);
                foreach ($seq as $d) {
                    $c = $cap[self::wd($d)] - ($u[$d] ?? 0);
                    if ($t['spray']) {
                        $c = min($c, $spr - ($us[$d] ?? 0));
                    }
                    if ($c <= 0.005) {
                        continue;
                    }
                    $take = min($need, $c);
                    $u[$d] = ($u[$d] ?? 0) + $take;
                    if ($t['spray']) {
                        $us[$d] = ($us[$d] ?? 0) + $take;
                    }
                    $days[] = [$d, round($take, 3)];
                    $need -= $take;
                    if ($need <= 0.005) {
                        break;
                    }
                }
                usort($days, fn ($a, $b) => $a[0] <=> $b[0]);
                $short += max(0, $need);
                $placed[] = $t + ['hired' => false, 'days' => $days, 'left' => round(max(0, $need), 2)];
            }
            if ($commit) {
                $used = $u;
                $usedS = $us;
                foreach ($placed as $p) {
                    foreach ($p['days'] as [$d, $pd]) {
                        $who[$d][] = [$l['i'], $p['key'], $pd, $p['hired']];
                    }
                }
            }

            return [$placed, round($short, 2)];
        };

        foreach ($order as $k) {
            $l = $lots[$k];
            [$from, $to] = $window($l);
            $best = null;
            $plantOwn = ! in_array('plant', $hiredKinds, true) && ! array_filter($l['tasks'], fn ($t) => $t['kind'] === 'plant' && ! empty($t['hired']));
            for ($d = $from; $d <= $to; $d++) {
                // The planting day is a day the crew works when the crew plants; a hired crew is not asked for a Sunday.
                if ($d < $to && ($plantOwn ? $cap[self::wd($d)] <= 0 : self::wd($d) === 0)) {
                    continue;
                }
                [$pl, $short] = $place($l, $d, false);
                // When no day fits, a later day must save real work to be worth the wait (a third of a person day a day).
                $cost = $short + 0.35 * ($d - $from);
                if ($best === null || $cost < $best[2] - 0.01) {
                    $best = [$d, $short, $cost];
                }
                if ($short <= 0.01) {
                    break;
                }
            }
            $base = $best ? $best[0] : $from;
            // Why a lot moved off its first day: who holds the crew on the day it needed.
            $why = null;
            if ($mode === 'auto' && $base > $from) {
                [$pl0] = $place($l, $from, false);
                foreach ($pl0 as $p) {
                    if (($p['left'] ?? 0) > 0 || ($p['days'] && $p['days'][0][0] !== $from + $p['day'] && ! $p['hired'])) {
                        $at = $from + $p['day'];
                        $others = array_values(array_filter($who[$at] ?? [], fn ($x) => $x[0] !== $l['i'] && ! $x[3]));
                        $why = ['task' => $p['title'], 'date' => self::ds($at), 'by' => array_map(fn ($x) => ['lot' => $x[0], 'key' => $x[1]], array_slice($others, 0, 3))];
                        break;
                    }
                }
            }
            [$pl] = $place($l, $base, true);
            $asked = $window($l)[0];
            $out['lots'][$l['i']] = [
                'i' => $l['i'], 'planting' => self::ds($base), 'asked' => ! empty($l['asked']) ? $l['asked'] : null, 'first' => self::ds($from),
                'shift' => $base - (! empty($l['asked']) ? max($t0 + 1, self::dn($l['asked'])) : $from), 'picked' => empty($l['asked']), 'why' => $why,
            ];
            foreach ($pl as $p) {
                $dates = array_map(fn ($x) => $x[0], $p['days']);
                $out['tasks'][] = [
                    'lot' => $l['i'], 'key' => $p['key'], 'kind' => $p['kind'], 'title' => $p['title'], 'what' => $p['what'] ?? '', 'stage' => $p['stage'] ?? null,
                    'products' => $p['products'] ?? [], 'who' => $p['who'] ?? null,
                    'day' => $p['day'], 'usual' => self::ds($base + $p['day']), 'from' => $dates ? self::ds(min($dates)) : self::ds($base + $p['day']),
                    'to' => $dates ? self::ds(max($dates)) : self::ds($base + $p['day']), 'lo' => self::ds($base + $p['lo']), 'hi' => self::ds($base + $p['hi']),
                    'pd' => (float) $p['pd'], 'hired' => (bool) $p['hired'], 'spray' => (bool) $p['spray'], 'left' => (float) ($p['left'] ?? 0),
                    'days' => array_map(fn ($x) => [self::ds($x[0]), $x[1]], $p['days']),
                    'late' => $mode === 'keep' ? ($dates && max($dates) > $base + $p['hi']) : ($p['left'] ?? 0) > 0.01,
                    'moved' => $dates && ! $p['hired'] ? min($dates) - ($base + $p['day']) : 0,
                    // The hands a day it takes to finish inside its window.
                    'perDay' => (int) ceil((float) $p['pd'] / max(1, $p['hi'] - $p['day'] + 1) - 0.01),
                ];
            }
        }

        // The days, as the crew sees them.
        $days = [];
        foreach ($out['tasks'] as $t) {
            foreach ($t['days'] as [$date, $pd]) {
                $days[$date] ??= ['date' => $date, 'need' => 0.0, 'spray' => 0.0, 'hired' => 0.0, 'items' => []];
                if ($t['hired']) {
                    $days[$date]['hired'] += $pd;
                } else {
                    $days[$date]['need'] += $pd;
                    if ($t['spray']) {
                        $days[$date]['spray'] += $pd;
                    }
                }
                $days[$date]['items'][] = ['lot' => $t['lot'], 'key' => $t['key'], 'pd' => $pd, 'hired' => $t['hired']];
            }
        }
        ksort($days);
        $short = [];
        foreach ($days as $date => &$d) {
            $n = self::dn($date);
            $d['cap'] = $cap[self::wd($n)];
            $d['sprayCap'] = $spr;
            $d['weekday'] = self::wd($n);
            $d['need'] = round($d['need'], 2);
            $d['workers'] = (int) ceil($d['need'] - 0.01);
            $d['over'] = max(0, (int) ceil($d['need'] - $d['cap'] - 0.01));
            $d['sprayOver'] = max(0, (int) ceil($d['spray'] - $spr - 0.01));
            if ($d['over'] > 0 || $d['sprayOver'] > 0) {
                $short[] = $date;
            }
        }
        unset($d);
        $out['days'] = array_values($days);
        $out['short'] = $mode === 'keep' ? $short : [];
        $out['late'] = array_values(array_filter($out['tasks'], fn ($t) => $t['late']));
        $peak = null;
        foreach ($days as $d) {
            if (! $peak || $d['need'] > $peak['need']) {
                $peak = ['date' => $d['date'], 'need' => $d['need'], 'workers' => $d['workers'], 'cap' => $d['cap']];
            }
        }
        $out['peak'] = $peak;
        $all = array_merge(...array_map(fn ($t) => [$t['from'], $t['to']], $out['tasks'] ?: [['from' => $today, 'to' => $today]]));
        $out['start'] = $out['tasks'] ? min($all) : $today;
        $out['end'] = $out['tasks'] ? max($all) : $today;
        $out['crew'] = ['workers' => count($crew['workers']), 'week' => $cap, 'sprayers' => $spr, 'plantBy' => $crew['plantBy']];
        $trouble = $out['late'] || $out['short'];
        $out['ok'] = ! $trouble;
        // The crew it takes (2026-10-21, the owner: how many workers, with the knapsack sprayers the team has):
        // the fewest working Monday to Saturday that keep every job inside its days, and with one or two more sprayers.
        $out['recommend'] = null;
        if ($advise) {
            $fit = function (int $sp) use ($lots, $crew, $mode, $today): ?int {
                $ok = fn (int $n) => self::schedule($lots, ['workers' => array_fill(0, $n, ['name' => 'R', 'days' => [1, 2, 3, 4, 5, 6]]), 'sprayers' => $sp, 'plantBy' => $crew['plantBy']], $mode, $today, false)['ok'];
                if (! $ok(60)) {
                    return null;
                }
                [$lo, $hi] = [1, 60];
                while ($lo < $hi) {
                    $mid = intdiv($lo + $hi, 2);
                    if ($ok($mid)) {
                        $hi = $mid;
                    } else {
                        $lo = $mid + 1;
                    }
                }

                return $lo;
            };
            $w = $fit($spr);
            $rec = ['workers' => $w, 'sprayers' => $spr, 'alt' => null];
            foreach ([1, 2] as $k) {
                $w2 = $fit($spr + $k);
                if ($w2 !== null && ($w === null || $w2 < $w)) {
                    $rec['alt'] = ['sprayers' => $spr + $k, 'workers' => $w2];
                    break;
                }
            }
            $out['recommend'] = $rec;
        }
        // What would fit: more workers working Monday to Saturday (and one more sprayer when the sprays hold it up).
        $out['more'] = null;
        $out['moreSprayers'] = null;
        if ($trouble && $advise) {
            foreach ([1, 2, 3, 4, 5, 6, 8, 10, 12, 15, 20, 25, 30, 40, 50] as $n) {
                $c = $crew;
                for ($k = 0; $k < $n; $k++) {
                    $c['workers'][] = ['name' => 'Extra', 'days' => [1, 2, 3, 4, 5, 6]];
                }
                $c['sprayers'] = max($spr, $spr + (int) ceil($n / 2));
                $r = self::schedule($lots, $c, $mode, $today, false);
                if ($r['ok']) {
                    $out['more'] = $n;
                    // Was it the sprayers? The same crew with only more sprayers.
                    break;
                }
            }
            $c = $crew;
            $c['sprayers'] = $spr + 2;
            if (self::schedule($lots, $c, $mode, $today, false)['ok']) {
                $out['moreSprayers'] = 2;
                $c['sprayers'] = $spr + 1;
                if (self::schedule($lots, $c, $mode, $today, false)['ok']) {
                    $out['moreSprayers'] = 1;
                }
            }
        }

        return $out;
    }

    /** The work of a whole calendar in words, for the prompt and the report. */
    public static function words(array $s, array $names): array
    {
        $day = fn (string $d) => Carbon::parse($d)->format('D M j');
        $lines = [];
        $lateLots = array_unique(array_column($s['late'], 'lot'));
        foreach ($s['lots'] as $i => $l) {
            $nm = $names[$i] ?? ('Lot ' . ($i + 1));
            $from = $l['asked'] ? 'the day you picked' : 'the start of the month';
            $lines[] = $nm . ': planted ' . $day($l['planting']) . ($l['shift'] > 0
                ? ' (' . $l['shift'] . ' days after ' . $from . (in_array($i, $lateLots, true) ? ', where the crew falls least short' : ', so the crew can keep up') . ')'
                : ($l['picked'] ? ' (the first day that fits)' : ''));
        }
        if ($s['short']) {
            $lines[] = count($s['short']) . ' day' . (count($s['short']) === 1 ? ' asks' : 's ask') . ' more workers than you have.';
        }
        if ($s['late']) {
            $lines[] = count($s['late']) . ' job' . (count($s['late']) === 1 ? ' runs past its' : 's run past their') . ' safe days with this crew.';
        }
        if ($s['more']) {
            $lines[] = 'With ' . $s['more'] . ' more worker' . ($s['more'] === 1 ? '' : 's') . ' working Monday to Saturday, everything fits.';
        }

        return $lines;
    }
}
