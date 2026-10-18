<?php

namespace App\Support;

use App\Services\WeatherService;
use Illuminate\Support\Carbon;

/**
 * The field and the season, counted into the Seed Count and Distance
 * Calculator (2026-10-19, the owner: "select also the lot where it will be
 * planted and the potential month ... the soil and water condition ... a
 * realistic penalty to the final stand or growth ... the enso forecast ...
 * as well as historical data"). Deterministic: no Anee, no credits.
 *
 * Two kinds of penalty, each a factor under 1 said as a plain line:
 *  - emergence (establishment): the share of seeds, pieces or seedlings that
 *    come up or survive, so it feeds the binomial final stand in
 *    SeedSpacing::compute(). For a transplanted crop raised from seed, the
 *    seedbed's temperature lowers the seedbed's germination instead (more
 *    seed to sow; the seedbed can be shaded or covered).
 *  - growth: the yield the plants that stand can carry. NPK Plus's own caps
 *    (NpkNeed::fieldCap, NpkModel::FIELD) and the season's sun, water and
 *    heat (SeasonFactors, the twin of NPK Plus's seasonPlanks()), so both
 *    tools read a field the same. With a season read, the season's water
 *    takes the place of the rainfed and partial caps (one shortfall, counted
 *    once).
 * They multiply, each group held to a floor. The lot's facts come from its
 * answers (facts()); the season from the shared stores only (context():
 * ClimateStore::locate, season, enso, NpkNeed::seasonWindow and ensoTilt,
 * WeatherService's kept reading). The page runs the same arithmetic (calc
 * block of resources/views/seed-spacing/index); change both.
 */
final class SeedPenalty
{
    /* ------------------------------------------------ the emergence penalties */

    /** Seeds stand salt better than seedlings: emergence loses this share of the crop's yield slope above its threshold (germination tolerance studies). */
    public const SALT_EMERGE = 0.5;

    /** The salt (EC, dS/m) read from signs of salt in the soil or the water when no test gives one: a white crust or burnt tips mean a soil about 4. */
    public const SALT_EC_SIGNS = 4.0;

    /** Sea water coming in at high tide: about 6 dS/m in the root zone of a coastal field. */
    public const SALT_EC_SEA = 6.0;

    /** Salt never takes more than this off the stand on its own. */
    public const SALT_FLOOR = 0.6;

    /** A sodic soil seals into a crust: small seeds (under 10 g for 1,000) push through it worst (crust strength studies: 10 to 30% fewer). */
    public const SODIC_SMALL = 0.85;

    public const SODIC_LARGE = 0.92;

    /** Clay or silt crusts after a hard rain on newly sown seed: the share lost when that rain comes, small seeds and large. */
    public const CRUST_SMALL = 0.12;

    public const CRUST_LARGE = 0.06;

    /** With no season read, the crust is counted at an average chance of a hard rain. */
    public const CRUST_FLAT_SMALL = 0.96;

    public const CRUST_FLAT_LARGE = 0.98;

    /** Water standing for days on an upland crop: seeds and seedlings rot or drown (waterlogging at emergence trials: 15 to 30% fewer). */
    public const WET_STANDS = 0.80;

    /** Slow drainage, or rust streaks and a rotten smell when dug: some spots stay wet. */
    public const WET_SLOW = 0.92;

    /** A field that floods most wet seasons, planted June to November, the flood months. */
    public const FLOOD_WET = 0.92;

    /** Direct seeded rice wants a drained surface its first week: water that stands on it washes and drowns the seed. */
    public const DSR_STANDS = 0.90;

    /** Strong acidity (pH under 5): aluminum stunts young roots of upland crops; rice stands it. */
    public const ACID_STRONG = 0.93;

    /** Acid sulfate soil: iron and acidity at their worst for seedlings. */
    public const ACID_SULFATE = 0.88;

    public const ACID_SULFATE_RICE = 0.92;

    /** A pH test under this is strong acidity. */
    public const ACID_PH = 5.0;

    /** A sandy rainfed field with no season read: seeds dry out before they root, counted at an average dry spell. */
    public const SANDY_DRY_FLAT = 0.94;

    /* --------------------------------------- the season at emergence (21 days) */

    /** The emergence window: the first three weeks after sowing or planting out. */
    public const EMERGE_DAYS = 21;

    /** A heavy start: this much rain in those three weeks (the wettest third of wet season windows in the lowlands). */
    public const HEAVY_MM = 300;

    /** A dry start: less than this in those three weeks, too little to keep a seed row moist. */
    public const DRY_MM = 30;

    /** A heavy start rots and washes out this share of newly sown seed (field emergence after heavy rain). */
    public const RAIN_ROT = 0.15;

    /** A dry start costs this share of a rainfed sowing (false starts: seeds sprout, then dry out). */
    public const DRY_LOSS = 0.25;

    /** The part of the cardinal temperature loss a field seed takes: the soil warms and cools through the day. */
    public const TEMP_SOFT = 0.5;

    /** A forecast day of this much rain, or a week of FC_HEAVY_WEEK, makes a heavy start this likely. */
    public const FC_HEAVY_DAY = 50;

    public const FC_HEAVY_WEEK = 150;

    public const FC_HEAVY_P = 0.8;

    /** A forecast week under this much rain makes a dry start this likely. */
    public const FC_DRY_WEEK = 5;

    public const FC_DRY_P = 0.5;

    /** The forecast is read when planting falls within this many days. */
    public const FC_DAYS = 16;

    /* -------------------------------------------------- the growth penalties */

    /** A hard layer under the plow depth: roots and tubers fork and stay small (root crop hardpan trials: 10 to 20% less). */
    public const ROOT_HARDPAN = 0.85;

    /** Rock or gravel within a hand span: no room for roots and tubers. */
    public const ROOT_SHALLOW = 0.80;

    /** A hardpan under a rainfed upland crop: shallow roots run dry sooner. */
    public const HARDPAN_DRY = 0.95;

    /** Water standing for days through the season on an upland crop (waterlogging yield trials). */
    public const GROW_STANDS = 0.85;

    public const GROW_SLOW = 0.95;

    /** Rice: each day of 35 C or more at flowering, past the season's usual share, sterilizes this share of the spikelets (heat at anthesis). */
    public const FLOWER_HEAT = 0.03;

    public const FLOWER_FLOOR = 0.6;

    /** Rice flowers about this many days before harvest, over two weeks. */
    public const FLOWER_BEFORE = 35;

    public const FLOWER_DAYS = 14;

    /* -------------------------------------------------------------- floors */

    /** All emergence penalties together never take more than half the stand. */
    public const EMERGE_FLOOR = 0.5;

    /** All growth penalties together never take more than 60% of the harvest. */
    public const GROWTH_FLOOR = 0.4;

    /**
     * Germination by temperature, soil degrees C: [lowest, best from, best to,
     * highest]. Full germination in the best range, none at the limits, a
     * straight line between (the classic soil temperature tables for
     * vegetable seed; cereal, legume and root crop seed physiology).
     */
    public const GERM_T = [
        'rice' => [10, 20, 35, 42], 'rice_dsr' => [10, 20, 35, 42],
        'corn_yellow' => [10, 16, 35, 41], 'corn_white' => [10, 16, 35, 41], 'corn_sweet' => [10, 18, 33, 40], 'sorghum' => [10, 20, 35, 42],
        'mungbean' => [12, 20, 36, 42], 'peanut' => [14, 20, 34, 40], 'soybean' => [10, 18, 33, 40], 'stringbean' => [15, 20, 35, 40], 'bushsitaw' => [15, 20, 35, 40],
        'okra' => [16, 21, 35, 41], 'tomato' => [10, 16, 29, 35], 'eggplant' => [16, 24, 32, 35], 'chili' => [16, 18, 35, 37], 'bellpepper' => [16, 18, 32, 35],
        'ampalaya' => [16, 21, 35, 40], 'squash' => [16, 21, 35, 38], 'cucumber' => [16, 18, 35, 41], 'watermelon' => [16, 21, 35, 41], 'upo' => [16, 21, 35, 38], 'patola' => [16, 21, 35, 38],
        'pechay' => [4, 7, 32, 38], 'cabbage' => [4, 7, 35, 38], 'lettuce' => [2, 4, 27, 30], 'onion' => [2, 10, 35, 37], 'carrot' => [4, 7, 29, 35], 'radish' => [4, 7, 32, 35],
        'garlic' => [5, 10, 25, 32], 'sweetpotato' => [15, 21, 32, 38], 'cassava' => [16, 22, 34, 38], 'taro' => [15, 21, 33, 37], 'potato' => [7, 12, 22, 28],
        'papaya' => [15, 23, 35, 40], 'sugarcane' => [12, 22, 35, 40],
    ];

    /** Salt and yield: [the soil EC (dS/m) where yield starts to fall, % lost for each dS/m past it] (the salt tolerance tables for crops). */
    public const SALT = [
        'default' => [1.5, 12],
        'rice' => [3.0, 12], 'rice_dsr' => [3.0, 12], 'corn_yellow' => [1.7, 12], 'corn_white' => [1.7, 12], 'corn_sweet' => [1.7, 12], 'sorghum' => [6.8, 16],
        'mungbean' => [1.8, 20.7], 'peanut' => [3.2, 29], 'soybean' => [5.0, 20], 'stringbean' => [4.9, 12], 'bushsitaw' => [4.9, 12],
        'tomato' => [2.5, 9.9], 'eggplant' => [1.1, 6.9], 'chili' => [1.5, 14], 'bellpepper' => [1.5, 14], 'squash' => [1.2, 13], 'cucumber' => [2.5, 13],
        'pechay' => [1.0, 10], 'cabbage' => [1.8, 9.7], 'lettuce' => [1.3, 13], 'onion' => [1.2, 16], 'carrot' => [1.0, 14], 'radish' => [1.2, 13],
        'garlic' => [3.9, 14.3], 'sweetpotato' => [1.5, 11], 'potato' => [1.7, 12], 'sugarcane' => [1.7, 5.9], 'coconut' => [4.0, 5], 'banana' => [1.0, 12],
        'banana_saba' => [1.0, 12], 'banana_cavendish' => [1.0, 12], 'calamansi' => [1.3, 13], 'mango' => [1.0, 15], 'cacao' => [1.0, 15], 'coffee' => [1.0, 15],
    ];

    /** Crops whose harvest is a root, tuber, corm or bulb in the soil: a hardpan or a shallow soil shows in the harvest. */
    public const ROOTS = ['carrot', 'radish', 'onion', 'garlic', 'sweetpotato', 'cassava', 'taro', 'potato'];

    /** The NPK Plus climate key a crop reads (NpkModel::CLIMATE), where it is not its own. */
    public const CLIMATE_KEY = ['rice_dsr' => 'rice_dsr_wet', 'corn_white' => 'corn_yellow', 'bushsitaw' => 'stringbean', 'banana_saba' => 'banana', 'banana_cavendish' => 'banana'];

    /** The crop catalog key a crop's days to harvest come from, where it is not its own. */
    public const CATALOG_KEY = ['rice_dsr' => 'rice_dsr_wet', 'corn_white' => 'corn_glutinous', 'bushsitaw' => 'stringbean', 'banana_saba' => 'banana', 'banana_cavendish' => 'banana'];

    /** What the page boots with. */
    public static function boot(): array
    {
        $k = [];
        foreach ((new \ReflectionClass(self::class))->getConstants() as $n => $v) {
            if (is_scalar($v)) {
                $k[$n] = $v;
            }
        }

        return ['k' => $k, 'germT' => self::GERM_T, 'salt' => self::SALT, 'roots' => self::ROOTS, 'climateKey' => self::CLIMATE_KEY,
            'climate' => NpkModel::CLIMATE, 'ensoTilt' => NpkModel::ENSO_TILT, 'field' => NpkModel::FIELD];
    }

    /** The crop's days to harvest, as NPK Plus reads a season over (a year for a perennial). */
    public static function seasonDays(string $key): int
    {
        return NpkNeed::seasonDays(self::CATALOG_KEY[$key] ?? $key);
    }

    /**
     * What a lot's answers say about establishing and growing a crop, in one
     * flat shape the page reads too: the texture, drainage, flooding, salt
     * (with a test's EC), acidity (with a test's pH), hardpan, depth, the
     * water source as NPK Plus reads it, signs when dug, salty water.
     */
    public static function facts(array $answers): array
    {
        $a = FieldProfile::normalize($answers);
        $L = FieldProfile::legacy($answers);
        $test = FieldProfile::testLatest((array) ($a['tests'] ?? []));
        $salt = array_values(array_diff(array_map('strval', (array) ($a['salt'] ?? [])), ['none']));
        $waterLook = array_map('strval', (array) ($a['waterLook'] ?? []));
        $ph = isset($test['ph']) && is_numeric($test['ph']) ? (float) $test['ph'] : ($L['phValue'] !== null ? (float) $L['phValue'] : null);
        $pick = fn ($k) => isset($a[$k]) && is_string($a[$k]) && $a[$k] !== '' ? $a[$k] : null;

        return [
            'texture' => $pick('texture'), 'drainage' => $pick('drainage'), 'flooding' => $pick('flooding'), 'acidity' => $pick('acidity'),
            'hardpan' => $pick('hardpan'), 'depth' => $pick('depth'), 'elevation' => $pick('elevation'), 'organic' => $pick('organic'),
            'salt' => $salt, 'signs' => array_values(array_diff(array_map('strval', (array) ($a['signs'] ?? [])), ['none'])),
            'waterSalty' => in_array('salty', $waterLook, true),
            'ph' => $ph, 'ec' => isset($test['ec']) && is_numeric($test['ec']) && (float) $test['ec'] > 0 ? (float) $test['ec'] : null,
            'water' => in_array($L['waterNpk'] ?? null, ['irrigated', 'partial', 'rainfed'], true) ? $L['waterNpk'] : null,
            'saline' => in_array('saline', (array) $L['soilConditions'], true), 'sodic' => in_array('sodic', (array) $L['soilConditions'], true),
            'sulfate' => ($a['acidity'] ?? null) === 'sulfate',
        ];
    }

    /** The planting day a month and an optional day mean (the 15th when no day is given, never before today). */
    public static function plantingDay(?string $month, ?int $day): ?Carbon
    {
        if (! $month || ! preg_match('/^(\d{4})-(\d{2})$/', $month, $m)) {
            return null;
        }
        $first = Carbon::create((int) $m[1], (int) $m[2], 1, 0, 0, 0, 'Asia/Manila');
        $d = $first->copy()->day(max(1, min($first->daysInMonth, $day ?: 15)));
        $today = now('Asia/Manila')->startOfDay();

        return $d->lt($today) && $first->format('Y-m') === $today->format('Y-m') ? $today : $d;
    }

    /**
     * The lot and the season for a calculation, from the shared stores only:
     * the lot's facts; with a place and a planting month, twenty years of the
     * same weeks (the whole season, its first three weeks and, for rice, its
     * flowering), the ENSO outlook and, within FC_DAYS, the place's kept
     * forecast. Nothing outside is asked here but what those stores ask.
     */
    public static function context(int $ownerId, ?int $lotId, string $crop, ?string $month, ?int $day): array
    {
        $out = ['lot' => null, 'facts' => null, 'season' => null, 'why' => null];
        $lot = $lotId ? FarmLots::one($ownerId, $lotId) : null;
        if ($lot) {
            $out['lot'] = ['id' => (int) $lot['id'], 'name' => (string) ($lot['name'] ?? 'My lot'), 'where' => $lot['where'] ?? '', 'place' => $lot['place'] ?? '',
                'areaHa' => $lot['areaHa'] ?? null, 'areaUnit' => $lot['areaUnit'] ?? 'ha'];
            $out['facts'] = self::facts((array) ($lot['answers'] ?? []));
        }
        $from = self::plantingDay($month, $day);
        if (! $from) {
            return $out;
        }
        if (! $lot || trim((string) ($lot['where'] ?? '')) === '') {
            $out['why'] = $lot ? 'noplace' : 'nolot';

            return $out;
        }
        $pin = $lot['pin'] ?? null;
        $loc = rescue(fn () => ClimateStore::locate((string) $lot['where'], $pin['lat'] ?? null, $pin['lng'] ?? null), null, false);
        if (! $loc) {
            $out['why'] = 'noplace';

            return $out;
        }
        $days = self::seasonDays($crop);
        $label = (string) ($lot['place'] ?: $lot['where']);
        $read = fn (Carbon $f, int $n) => rescue(fn () => ClimateStore::season((float) $loc['lat'], (float) $loc['lng'], $f, $n, (string) $lot['where']), null, false);
        $hist = $read($from, $days);
        if (! $hist) {
            $out['why'] = 'nohistory';

            return $out;
        }
        $emerge = $read($from, self::EMERGE_DAYS);
        $flower = str_starts_with($crop, 'rice') && $days > self::FLOWER_BEFORE + self::FLOWER_DAYS
            ? $read($from->copy()->addDays($days - self::FLOWER_BEFORE), self::FLOWER_DAYS) : null;
        $out['season'] = [
            'place' => $label, 'from' => $from->toDateString(), 'days' => $days,
            'window' => NpkNeed::seasonWindow($from, $days), 'ewindow' => NpkNeed::seasonWindow($from, self::EMERGE_DAYS),
            'hist' => ['years' => $hist['years'], 'avg' => $hist['avg'], 'span' => $hist['span'] ?? null],
            'emerge' => $emerge ? ['years' => $emerge['years'], 'avg' => $emerge['avg']] : null,
            'flower' => $flower ? ['avg' => $flower['avg']] : null,
            'enso' => rescue(fn () => ClimateStore::enso(), null, false),
            'forecast' => self::forecast((float) $loc['lat'], (float) $loc['lng'], $from),
        ];

        return $out;
    }

    /** The place's kept forecast for the days of the emergence window it reaches: date, high, low, chance of rain, mm (the first week). */
    private static function forecast(float $lat, float $lng, Carbon $from): ?array
    {
        $today = now('Asia/Manila')->startOfDay();
        if ($from->gt($today->copy()->addDays(self::FC_DAYS - 1))) {
            return null;
        }
        $ws = app(WeatherService::class);
        $days = rescue(fn () => $ws->forecast($lat, $lng, self::FC_DAYS), null, false);
        if (! $days) {
            return null;
        }
        // Rain by day from the kept reading's hours (it carries a week of them).
        $mm = [];
        $r = rescue(fn () => $ws->reading($lat, $lng), null, false);
        foreach ((array) ($r['hourly']['time'] ?? []) as $i => $t) {
            $d = substr((string) $t, 0, 10);
            $mm[$d] = ($mm[$d] ?? 0) + (float) ($r['hourly']['precipitation'][$i] ?? 0);
        }
        $end = $from->copy()->addDays(self::EMERGE_DAYS)->toDateString();
        $out = [];
        foreach ($days as $d) {
            if ($d['date'] < $from->toDateString() || $d['date'] >= $end || $d['max'] === null || $d['min'] === null) {
                continue;
            }
            $out[] = ['date' => $d['date'], 'max' => $d['max'], 'min' => $d['min'], 'pop' => $d['pop'], 'mm' => isset($mm[$d['date']]) ? round($mm[$d['date']], 1) : null];
        }

        return $out ?: null;
    }

    /** The season's numbers at emergence: the chance of a heavy or a dry start, the mean and the warm part of the day, with the outlook and the forecast. */
    public static function weather(?array $season): ?array
    {
        if (! $season || empty($season['emerge']['years'])) {
            return null;
        }
        $t = NpkNeed::ensoTilt($season['enso'] ?? null, $season['ewindow']);
        $ys = $season['emerge']['years'];
        $n = count($ys);
        $heavy = count(array_filter($ys, fn ($y) => (float) $y['rain'] * $t['rain'] >= self::HEAVY_MM)) / $n;
        $dry = count(array_filter($ys, fn ($y) => (float) $y['rain'] * $t['rain'] < self::DRY_MM)) / $n;
        $tm = (float) $season['emerge']['avg']['tmean'] + $t['temp'];
        $tx = (float) $season['emerge']['avg']['tmax'] + $t['temp'];
        $rain = (float) $season['emerge']['avg']['rain'] * $t['rain'];
        $fc = null;
        $F = (array) ($season['forecast'] ?? []);
        if ($F) {
            $nF = min(self::EMERGE_DAYS, count($F));
            $tmF = array_sum(array_map(fn ($d) => ((float) $d['max'] + (float) $d['min']) / 2, $F)) / count($F);
            $txF = array_sum(array_map(fn ($d) => (float) $d['max'], $F)) / count($F);
            $tm = ($tm * (self::EMERGE_DAYS - $nF) + $tmF * $nF) / self::EMERGE_DAYS;
            $tx = ($tx * (self::EMERGE_DAYS - $nF) + $txF * $nF) / self::EMERGE_DAYS;
            $wk = array_values(array_filter(array_slice($F, 0, 7), fn ($d) => $d['mm'] !== null));
            $sum = array_sum(array_map(fn ($d) => (float) $d['mm'], $wk));
            $max = $wk ? max(array_map(fn ($d) => (float) $d['mm'], $wk)) : 0.0;
            $fc = ['days' => $nF, 'tm' => $tmF, 'tx' => $txF, 'mmDays' => count($wk), 'sum' => $sum, 'max' => $max, 'from' => $F[0]['date'], 'to' => end($F)['date']];
            if (count($wk) >= 5) {
                if ($max >= self::FC_HEAVY_DAY || $sum >= self::FC_HEAVY_WEEK) {
                    $heavy = max($heavy, self::FC_HEAVY_P);
                    $fc['heavy'] = true;
                }
                if ($sum < self::FC_DRY_WEEK) {
                    $dry = max($dry, self::FC_DRY_P);
                    $fc['dry'] = true;
                }
            }
        }

        return ['heavy' => $heavy, 'dry' => $dry, 'tm' => $tm, 'tw' => ($tm + $tx) / 2, 'tx' => $tx, 'rain' => $rain, 'years' => $n, 'tilt' => $t, 'fc' => $fc];
    }

    /** The share of normal germination at a temperature, on the crop's cardinal line. */
    public static function cardinal(array $T, float $t): float
    {
        [$b, $o1, $o2, $x] = $T;
        if ($t <= $b || $t >= $x) {
            return 0.0;
        }
        if ($t < $o1) {
            return ($t - $b) / ($o1 - $b);
        }
        if ($t > $o2) {
            return ($x - $t) / ($x - $o2);
        }

        return 1.0;
    }

    /**
     * The penalties for a crop, a method and a water source on a lot in a
     * season. Each line: [code, factor, from (lot, season, forecast), a number
     * for its words]. Notes are things said, not counted.
     */
    public static function of(array $C, string $key, string $method, ?array $facts, ?array $season): array
    {
        $E = [];
        $S = [];
        $G = [];
        $notes = [];
        $f = $facts;
        $rice = str_starts_with($key, 'rice');
        $tp = $method === 'transplant';
        $pc = $method === 'piece';
        $dir = $method === 'direct';
        $nursery = $tp && ! empty($C['tgw']);
        $small = ! $pc && ! empty($C['tgw']) && (float) $C['tgw'][1] < 10;
        $W = self::weather($season);
        $water = $f['water'] ?? null;
        $wk = $water ?? 'irrigated';
        // How a method takes each hazard: a transplant is past the seed's worst days.
        $wt = fn (float $k, float $w) => 1 - $w * (1 - $k);
        $add = function (array &$L, string $code, float $k, string $from, $v = null) {
            if ($k < 0.9995) {
                $L[] = [$code, $k, $from, $v];
            }
        };
        if ($f) {
            if ($water === null) {
                $notes[] = 'nowater';
            }
            // Salt: a test's EC, else the signs; seeds stand it better than seedlings.
            $sea = in_array('sea', $f['salt'], true);
            $ec = $f['ec'] ?? (($f['saline'] || $f['waterSalty']) ? ($sea ? self::SALT_EC_SEA : self::SALT_EC_SIGNS) : null);
            if ($ec !== null) {
                [$thr, $slope] = self::SALT[$key] ?? self::SALT['default'];
                $add($E, 'salt', max(self::SALT_FLOOR, 1 - self::SALT_EMERGE * $slope / 100 * max(0, $ec - $thr)), 'lot', $ec);
            }
            if ($f['sodic'] && $dir && ! $rice) {
                $add($E, 'sodic', $small ? self::SODIC_SMALL : self::SODIC_LARGE, 'lot');
            }
            // Water that stays: upland crops rot and drown; direct seeded rice wants a drained surface.
            $dr = $f['drainage'] ?? (array_intersect($f['signs'], ['rust', 'smell']) ? 'slow' : null);
            $wW = $tp ? 0.7 : 1;
            if (! $rice) {
                if ($dr === 'stands') {
                    $add($E, 'wet', $wt(self::WET_STANDS, $wW), 'lot', 'stands');
                } elseif ($dr === 'slow') {
                    $add($E, 'wet', $wt(self::WET_SLOW, $wW), 'lot', 'slow');
                }
                $mo = $season ? (int) substr((string) $season['from'], 5, 2) : null;
                if (($f['flooding'] ?? null) === 'often' && $mo !== null && $mo >= 6 && $mo <= 11) {
                    $add($E, 'flood', $wt(self::FLOOD_WET, $wW), 'lot');
                }
            } elseif ($key === 'rice_dsr' && $dr === 'stands') {
                $add($E, 'dsrwater', self::DSR_STANDS, 'lot');
            }
            // Acidity: a pH test first, else the answer.
            $strong = ($f['ph'] !== null && $f['ph'] < self::ACID_PH) || ($f['ph'] === null && ($f['acidity'] ?? null) === 'strong');
            if ($f['sulfate']) {
                $add($E, 'sulfate', $wt($rice ? self::ACID_SULFATE_RICE : self::ACID_SULFATE, $tp ? 0.5 : 1), 'lot');
            } elseif ($strong && ! $rice) {
                $add($E, 'acid', $wt(self::ACID_STRONG, $tp ? 0.5 : 1), 'lot', $f['ph']);
            }
            // Crusting and drying need the weather; with none read they count at an average chance.
            $crusty = in_array($f['texture'] ?? '', ['silt', 'clay', 'heavy_clay'], true) && $dir && ! $rice && ! $f['sodic'];
            if ($crusty) {
                if ($W) {
                    $add($E, 'crust', 1 - ($small ? self::CRUST_SMALL : self::CRUST_LARGE) * $W['heavy'], 'season', $W['heavy']);
                } else {
                    $add($E, 'crust', $small ? self::CRUST_FLAT_SMALL : self::CRUST_FLAT_LARGE, 'lot');
                }
            }
            if (! $W && ($f['texture'] ?? null) === 'sandy' && $water === 'rainfed' && ($dir || $pc)) {
                $add($E, 'sandy', self::SANDY_DRY_FLAT, 'lot');
            }
        }
        if ($W) {
            // A heavy start: newly sown seed rots or washes out.
            $poor = $f && (in_array($f['drainage'] ?? '', ['slow', 'stands'], true) || in_array($f['texture'] ?? '', ['clay', 'heavy_clay'], true));
            $wR = ($tp ? 0.33 : ($pc ? 0.7 : 1)) * ($poor ? 1.3 : 1);
            $add($E, 'heavy', max(0.0, 1 - self::RAIN_ROT * $W['heavy'] * $wR), ! empty($W['fc']['heavy']) ? 'forecast' : 'season', $W['heavy']);
            // A dry start: only where the field waits on the rain.
            if ($wk !== 'irrigated' && ! ($rice && $tp && $wk === 'partial')) {
                $tex = $f['texture'] ?? null;
                $mult = $tex === 'sandy' ? 1.4 : (in_array($tex, ['clay', 'heavy_clay'], true) ? 0.8 : 1);
                $wD = ($wk === 'partial' ? 0.5 : 1) * ($tp ? 0.7 : 1) * $mult;
                $add($E, 'dry', max(0.0, 1 - self::DRY_LOSS * $W['dry'] * $wD), ! empty($W['fc']['dry']) ? 'forecast' : 'season', $W['dry']);
            }
            // Temperature against the crop's germination range: the seedbed for a transplant, the field otherwise.
            $T = self::GERM_T[$key] ?? null;
            if ($T && ($dir || $pc || $nursery)) {
                $cool = $W['tm'] < $T[1] ? self::cardinal($T, $W['tm']) : 1.0;
                $hot = $W['tw'] > $T[2] ? self::cardinal($T, $W['tw']) : 1.0;
                $k = 1 - self::TEMP_SOFT * (1 - min($cool, $hot));
                $code = $hot < $cool ? 'hot' : 'cool';
                if ($nursery) {
                    $add($S, $code, $k, $W['fc'] ? 'forecast' : 'season', $hot < $cool ? $W['tw'] : $W['tm']);
                } else {
                    $add($E, $code, $k, $W['fc'] ? 'forecast' : 'season', $hot < $cool ? $W['tw'] : $W['tm']);
                }
            }
        }

        // Growth: NPK Plus's caps; with a season, its water stands for the rainfed and partial caps.
        $capWater = $season && ! empty($season['hist']['avg']) ? 'irrigated' : $wk;
        $conds = $f ? array_values(array_filter([$f['saline'] || $f['waterSalty'] ? 'saline' : null, $f['sodic'] ? 'sodic' : null])) : [];
        $cap = NpkNeed::fieldCap(['key' => $key], (bool) $f, $capWater, $conds);
        foreach ($cap['why'] as $w) {
            $add($G, 'cap-' . $w[0], (float) $w[1], 'lot');
        }
        $SF = $season ? SeasonFactors::of(self::CLIMATE_KEY[$key] ?? $key, $season['hist'], $season['window'], $season['enso'] ?? null, $wk) : null;
        if ($SF) {
            $min = min(1.0, $SF['sun'], $SF['water'], $SF['temp']);
            $which = $min === $SF['water'] ? 'water' : ($min === $SF['temp'] ? 'temp' : 'sun');
            $add($G, 'season-' . $which, $min, 'season', $which === 'water' ? $SF['wr'] : ($which === 'temp' ? $SF['tmean'] : $SF['rad']));
        }
        if ($f) {
            if (in_array($key, self::ROOTS, true)) {
                $kr = min(($f['hardpan'] ?? null) === 'yes' ? self::ROOT_HARDPAN : 1.0, ($f['depth'] ?? null) === 'shallow' ? self::ROOT_SHALLOW : 1.0);
                $add($G, ($f['depth'] ?? null) === 'shallow' && $kr === self::ROOT_SHALLOW ? 'shallow' : 'hardpan', $kr, 'lot');
            } elseif (($f['hardpan'] ?? null) === 'yes' && $wk === 'rainfed' && ! $rice) {
                $add($G, 'hardpandry', self::HARDPAN_DRY, 'lot');
            }
            if (! $rice) {
                $dr = $f['drainage'] ?? (array_intersect($f['signs'], ['rust', 'smell']) ? 'slow' : null);
                if ($dr === 'stands') {
                    $add($G, 'growwet', self::GROW_STANDS, 'lot', 'stands');
                } elseif ($dr === 'slow') {
                    $add($G, 'growwet', self::GROW_SLOW, 'lot', 'slow');
                }
            }
        }
        // Rice: heat at flowering, past the season's own share of hot days, empties spikelets.
        $flower = 1.0;
        if ($rice && $season && ! empty($season['flower']['avg']) && $SF) {
            $usual = $SF['hotN'] * self::FLOWER_DAYS / max(1, (int) $season['days']);
            $phase = $season['enso']['phase'] ?? null;
            $hotF = (float) ($season['flower']['avg']['hot35'] ?? 0) * ($phase === 'el_nino' ? 1.3 : ($phase === 'la_nina' ? 0.85 : 1));
            $excess = max(0.0, $hotF - $usual);
            $flower = max(self::FLOWER_FLOOR, 1 - self::FLOWER_HEAT * $excess);
            if ($flower < 0.9995) {
                $G[] = ['flower', $flower, 'season', $excess];
            }
        }
        // NPK Plus's caps keep their own floor together (NpkModel::FIELD capFloor), as fieldCap() does.
        $prod = function (array $L, float $floor, array $skip = []) {
            $k = 1.0;
            $cap = 1.0;
            foreach ($L as $l) {
                if (in_array($l[0], $skip, true)) {
                    continue;
                }
                if (str_starts_with($l[0], 'cap-')) {
                    $cap *= $l[1];
                } else {
                    $k *= $l[1];
                }
            }

            return max($floor, $k * max((float) NpkModel::FIELD['capFloor'], $cap));
        };

        return [
            'emerge' => ['k' => $prod($E, self::EMERGE_FLOOR), 'lines' => $E],
            'seedbed' => ['k' => $prod($S, self::EMERGE_FLOOR), 'lines' => $S],
            // The flowering line lowers the filled share; the rest the harvest.
            'growth' => ['k' => $prod($G, self::GROWTH_FLOOR, ['flower']), 'lines' => $G],
            'flower' => $flower,
            'notes' => $notes,
            'weather' => $W ? ['heavy' => round($W['heavy'], 4), 'dry' => round($W['dry'], 4), 'tm' => round($W['tm'], 2), 'tw' => round($W['tw'], 2), 'fc' => $W['fc']] : null,
            'season' => $SF ? ['sun' => round($SF['sun'], 4), 'water' => round($SF['water'], 4), 'temp' => round($SF['temp'], 4), 'rain' => round($SF['rain'], 1),
                'rad' => round($SF['rad'], 2), 'tmean' => round($SF['tmean'], 2), 'need' => round($SF['need'], 1), 'wr' => round($SF['wr'], 4), 'hotN' => round($SF['hotN'], 2),
                'tiltRain' => round($SF['tilt']['rain'], 4)] : null,
        ];
    }

    /** No penalty at all. */
    public static function none(): array
    {
        return ['emerge' => ['k' => 1.0, 'lines' => []], 'seedbed' => ['k' => 1.0, 'lines' => []], 'growth' => ['k' => 1.0, 'lines' => []], 'flower' => 1.0,
            'notes' => [], 'weather' => null, 'season' => null];
    }
}
