<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use Illuminate\Support\Carbon;

/**
 * Water in the Coverage Planner (2026-10-30, the owner: "also add range coverage for irrigation where plant gets stress
 * days after irrigating i mean the effect of the irrigation which also depends on the environmental, soil, and crop and
 * variety specs").
 *
 * Every watering on the lot's tasks, and every one planned in the planner, fills the soil; the crop and the air empty
 * it; rain fills it again. Counted a day at a time, as WaterBudget counts a protocol's watering:
 *  - the weather: the days already past as they were at the place (WeatherDays, only what is kept: nothing is read
 *    from outside while the page waits), the days ahead from the climate shelf's twenty years, the rain of a usual year
 *    (the middle one) or of a dry year (the driest in five); with no history kept, no rain and the month's usual heat;
 *  - the soil: what its texture holds a meter deep, less where it is shallow or a hard layer stops the roots, more
 *    with much organic matter; what a paddy loses into the ground a day (its drainage, its plow pan); a steep slope
 *    sheds part of the rain; shade drinks less;
 *  - the crop: what it drinks at each share of its season (its crop factor), how deep its roots reach as it grows, how
 *    far the soil may dry before it suffers; for a paddy, its standing water and how far below the surface it may drop;
 *  - the variety: its days to harvest (the drinking and the roots follow them), and whether it is known to hold out
 *    in a dry spell.
 * A day is covered while the root zone holds more than the crop's stress line. Past that line the crop is short of
 * water: a stress day, a gap when its stage matters. How much each stage matters for water is this class's table.
 */
final class RangeEffectWater
{
    /** The water checks: mode => [label, short, icon, word]. */
    public const MODES = [
        'usual' => ['Water, a usual year', 'Water', '💧', 'water'],
        'dry' => ['Water, a dry year', 'Water, dry year', '🌵', 'water'],
        'rain' => ['Rain only, no watering', 'Rain only', '🌧️', 'water'],
    ];

    /** How the water goes on: [name, said after a watering, the share that reaches the roots, the most one watering puts in the soil, mm]. */
    public const METHODS = [
        'flood' => ['Flooding', 'by flooding', 0.6, 70.0],
        'furrow' => ['Furrows', 'by furrow', 0.65, 60.0],
        'sprinkler' => ['Sprinklers', 'by sprinkler', 0.75, 35.0],
        'drip' => ['Drip lines', 'by drip', 0.9, 12.0],
        'hose' => ['A hose or a can', 'by hose', 0.8, 20.0],
    ];

    /** The standing water a paddy watering leaves when its words give no depth, mm. */
    private const PADDY_TARGET = ['irrigate' => 50.0, 'overflow' => 70.0, 'maintain' => 50.0];

    /** Rain a day that fills the soil again by itself, mm that reach the roots. */
    private const RAIN_FILL = 10.0;

    /** A watering stops being the one that carries the crop after this many days of stress. */
    private const CARRY_STRESS = 7;

    /** How much a stage suffers when the soil dries, by the crop book's stage names: [0 to 3, why]. */
    public const CRIT = [
        'Recovery' => [2, 'New seedlings need standing water to settle and send out roots.'],
        'Early tillering' => [2, 'Tillers form best in shallow water; a dry spell now cuts their number.'],
        'Active tillering' => [1, 'A short dry spell is safe now and firms the roots, as long as the soil stays damp.'],
        'Panicle initiation' => [3, 'The young panicle is forming: dry soil now means fewer grains.'],
        'Booting & heading' => [3, 'The most water sensitive days: a dry field now leaves empty grains.'],
        'Flowering' => [3, 'Pollen fails in dry soil and heat: keep the water steady.'],
        'Grain filling' => [2, 'The grains still fill from the water the leaves draw; let the field dry only near the end.'],
        'Ripening & harvest' => [0, 'The water is let go so the field dries for harvest.'],
        'Germination & emergence' => [2, 'Seeds need moist soil to sprout evenly, never standing water over them.'],
        'Seedling establishment' => [2, 'Young roots are shallow and dry out within days.'],
        'Emergence' => [2, 'Seeds need moist soil to come up evenly.'],
        'Early vegetative' => [1, 'Young plants can take a short dry spell while their roots go deeper.'],
        'Rapid growth' => [2, 'The crop drinks more each day as its leaves spread.'],
        'Tasselling' => [3, 'The tassels must shed pollen as the silks come out: stress now pulls the two apart.'],
        'Silking & pollination' => [3, 'The most water sensitive days for corn: a few dry days cost many kernels.'],
        'Milk stage' => [2, 'The ears are filling and sweetening; stress leaves them short.'],
        'Maturity & harvest' => [0, 'The crop is drying down and needs little water now.'],
        'Harvest' => [1, 'Keep the soil just moist while the picking goes on.'],
        'Seedling' => [2, 'Shallow young roots dry out within days.'],
        'Leaf growth' => [2, 'The leaves are mostly water: a dry spell keeps them small and tough.'],
        'Heading & filling' => [3, 'Heads stay loose or split when the water swings.'],
        'Vegetative' => [1, 'The plant can take a short dry spell, and its roots go deeper for it.'],
        'Fruit set & filling' => [3, 'Flowers and young fruits drop, and fruits crack, when the soil swings from dry to wet.'],
        'Vine growth' => [2, 'Fast growing vines drink a lot on hot days.'],
        'Pod filling' => [3, 'The pods fill from the water the plant draws now: stress leaves them empty.'],
        'Reproductive' => [3, 'Flowering and seed set suffer most from a dry spell.'],
        'Establishment' => [2, 'New roots are shallow and need steady moisture.'],
        'Root initiation' => [2, 'The storage roots start now: a dry spell cuts how many form.'],
        'Bulking' => [2, 'The roots swell from the water the plant takes: keep the soil moist, never soggy.'],
        'Bulbing' => [3, 'The bulbs swell now: stress leaves them small or split.'],
        'Maturity & curing' => [0, 'Watering stops so the bulbs cure and keep.'],
        'Germination' => [2, 'The cuttings need moist soil to sprout.'],
        'Tillering' => [2, 'New shoots form best in moist soil.'],
        'Grand growth' => [3, 'The cane grows fastest now and drinks the most.'],
        'Maturation' => [0, 'The cane is dried off now to raise its sugar.'],
        'Vegetative growth' => [2, 'The leaves grow fast and drink a lot.'],
        'Late vegetative' => [2, 'The bunch is forming inside the plant.'],
        'Shooting' => [3, 'The bunch comes out: stress now means fewer hands.'],
        'Bunch filling' => [3, 'The fingers fill from the water: a dry spell keeps them thin.'],
        'Juvenile growth' => [1, 'Young trees need steady moisture through the dry months.'],
        'First bearing' => [2, 'Flowering and fruit set suffer in a long dry spell.'],
        'Mature bearing' => [2, 'Flowering and fruit set suffer in a long dry spell.'],
        'Old and declining' => [1, 'Old trees still need water through the dry months.'],
    ];

    /** A table's own word where its stage differs from the general one. */
    private const CRIT_TABLE = [
        'sugarcane' => ['Ripening & harvest' => [0, 'The field is kept dry for the cut and for the sugar.']],
        'rice_dsr_dry' => ['Germination & emergence' => [2, 'Dry seeded rice needs moist soil to sprout evenly.']],
    ];

    /* =============================================================== the checks */

    /** A water check by its mode (w:usual, w:dry, w:rain), as RangeEffect::checkOf gives the others. */
    public static function check(string $mode): ?array
    {
        if (! isset(self::MODES[$mode])) {
            return null;
        }
        [$label, $short, $icon, $word] = self::MODES[$mode];

        return ['key' => 'w:' . $mode, 'kind' => 'water', 'mode' => $mode, 'label' => $label, 'short' => $short, 'word' => $word, 'icon' => $icon];
    }

    /** The water checks the planner offers. */
    public static function options(): array
    {
        $about = ['usual' => 'Your waterings and the rain of a usual year', 'dry' => 'The same in the driest year in five', 'rain' => 'What the rain gives with no watering'];

        return array_map(fn ($m) => ['key' => 'w:' . $m, 'name' => self::MODES[$m][0], 'short' => self::MODES[$m][1], 'icon' => self::MODES[$m][2], 'about' => $about[$m]],
            array_keys(self::MODES));
    }

    /** [level 0 to 3, why] for a stage, for water. */
    public static function critOf(?string $table, ?string $stage): array
    {
        if ($stage === null) {
            return [0, ''];
        }
        $row = self::CRIT_TABLE[$table][$stage] ?? self::CRIT[$stage] ?? null;

        return $row ? [(int) $row[0], (string) $row[1]] : [1, 'The crop needs steady moisture.'];
    }

    /** The phases a water day can be in, and their words for this lot. */
    public static function words(array $c): array
    {
        return $c['water'] === 'flooded'
            ? ['wet' => 'Standing water', 'moist' => 'Soaked, no standing water', 'drying' => 'Drying below the surface, still safe', 'dry' => 'Too dry: the crop is stressed']
            : ['wet' => 'Just watered or soaked by rain', 'moist' => 'Moist, plenty for the roots', 'drying' => 'Drying, no stress yet', 'dry' => 'Too dry: the crop is stressed'];
    }

    /* =============================================================== the waterings */

    /** What a watering task does, from its water task or its words: irrigate, maintain, overflow, drain, no_irrigation, let_subside. */
    public static function taskOf(?string $waterTask, string $words): string
    {
        if ($waterTask && isset(AsScheduleActivity::WATER_TASKS[$waterTask])) {
            return $waterTask === 'drain_water' ? 'drain' : $waterTask;
        }
        $w = mb_strtolower($words);

        return match (true) {
            (bool) preg_match('/\bno (?:irrigation|water)|\bstop (?:irrigat|water)/', $w) => 'no_irrigation',
            (bool) preg_match('/\bdrain/', $w) => 'drain',
            str_contains($w, 'subside') => 'let_subside',
            (bool) preg_match('/\b(?:maintain|keep)\b/', $w) => 'maintain',
            str_contains($w, 'overflow') => 'overflow',
            default => 'irrigate',
        };
    }

    /** The depth a task's words give, mm ("5 cm", "3 to 5 cm", "25 mm"), or null. */
    public static function depthOf(string $words): ?float
    {
        $w = mb_strtolower(strip_tags($words));
        $mm = null;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:to|-|–)\s*(\d+(?:\.\d+)?)\s*(cm|mm)\b/u', $w, $m)) {
            $mm = (float) $m[2] * ($m[3] === 'cm' ? 10 : 1);
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(cm|mm)\b/', $w, $m)) {
            $mm = (float) $m[1] * ($m[2] === 'cm' ? 10 : 1);
        }

        return $mm !== null && $mm >= 3 && $mm <= 300 ? $mm : null;
    }

    /** How a task's words say the water goes on, or null. A paddy is flooded. */
    public static function methodOf(string $words, bool $paddy): ?string
    {
        if ($paddy) {
            return 'flood';
        }
        $w = mb_strtolower($words);

        return match (true) {
            str_contains($w, 'drip') => 'drip',
            str_contains($w, 'sprinkl') => 'sprinkler',
            (bool) preg_match('/\b(?:hose|watering can|by hand|pail|regadera)/', $w) => 'hose',
            str_contains($w, 'furrow') => 'furrow',
            (bool) preg_match('/\bflood|\bbasin/', $w) => 'flood',
            default => null,
        };
    }

    /**
     * The lot's waterings as applications: its irrigation tasks (a task that names no lot is every lot's), then the
     * waterings planned here ({water, date, qty, unit cm|mm, method}). A no-water or let-subside task puts nothing in.
     */
    public static function events(AsCroppingSchedule $schedule, array $c, array $tries): array
    {
        $lot = $c['lot'];
        $paddy = $c['water'] === 'flooded';
        $allIds = $schedule->lots->where('deleteStatus', 1)->pluck('id')->map(fn ($i) => (int) $i)->all();
        $out = [];
        foreach ($schedule->activities as $act) {
            if ($act->activityType !== 'irrigation' || ! $act->targetDate || (int) ($act->deleteStatus ?? 1) !== 1 || (int) ($act->isHidden ?? 0) === 1) {
                continue;
            }
            $ids = $act->lots->pluck('id')->map(fn ($i) => (int) $i)->filter(fn ($i) => in_array($i, $allIds, true))->values()->all() ?: $allIds;
            if (! in_array((int) $lot->id, $ids, true)) {
                continue;
            }
            $words = trim((string) $act->activityTitle . ' ' . (string) ($act->subtitle ?? '') . ' ' . strip_tags((string) ($act->description ?? '')));
            $task = self::taskOf($act->waterTask, $words);
            if (in_array($task, ['no_irrigation', 'let_subside'], true)) {
                continue;
            }
            $out[] = self::event(Carbon::parse($act->targetDate)->startOfDay(), $task, self::depthOf($words), self::methodOf($words, $paddy), $paddy)
                + ['title' => trim((string) $act->activityTitle) ?: 'Watering', 'done' => (int) ($act->isDone ?? 0) === 1, 'try' => false, 'tryIndex' => null, 'activityId' => (int) $act->id];
        }
        foreach (array_values($tries) as $i => $t) {
            if (empty($t['water'])) {
                continue;
            }
            $date = isset($t['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $t['date']) ? Carbon::parse($t['date'])->startOfDay() : null;
            if (! $date) {
                continue;
            }
            $q = max(0.0, (float) ($t['qty'] ?? 0));
            $mm = $q > 0 ? $q * (($t['unit'] ?? 'cm') === 'mm' ? 1 : 10) : null;
            $method = $paddy ? 'flood' : (isset(self::METHODS[$t['method'] ?? '']) ? (string) $t['method'] : null);
            $out[] = self::event($date, 'irrigate', $mm !== null ? min(300.0, $mm) : null, $method, $paddy)
                + ['title' => 'If you water', 'done' => false, 'try' => true, 'tryIndex' => $i, 'activityId' => null];
        }

        return $out;
    }

    private static function event(Carbon $date, string $task, ?float $mm, ?string $method, bool $paddy): array
    {
        $name = match ($task) {
            'drain' => 'Drain the field', 'maintain' => 'Keep the water', 'overflow' => $paddy ? 'Flood the field' : 'A deep watering', default => 'Watering',
        };
        $as = $name . ($mm !== null && $task !== 'drain' ? ', ' . self::cm($mm) . ($paddy ? ' of standing water' : '') : '')
            . ($method && ! $paddy && $task !== 'drain' ? ' ' . self::METHODS[$method][1] : '');

        return ['date' => $date, 'name' => $name, 'as' => $as, 'company' => null, 'qty' => $mm ?? 0.0, 'unit' => $mm !== null ? 'mm' : '', 'nut' => [], 'ai' => [],
            'form' => 'water', 'kindWord' => 'Watering', 'doseKnown' => $mm !== null, 'shed' => false, 'method' => $method,
            'water' => ['task' => $task, 'mm' => $mm, 'method' => $method]];
    }

    /** A placeholder curve: a watering acts on a water check, its days come from the count. */
    public static function curve(): array
    {
        return ['water' => true, 'onset' => 0, 'peak' => 0, 'lo' => 3, 'typ' => 7, 'hi' => 21, 'amp' => 1.0, 'words' => [], 'note' => '', 'conf' => 'book'];
    }

    /** Whether a water task counts on a check: on rain only, the drains still do, the waterings not. */
    public static function counts(array $check, array $a): bool
    {
        return ($check['mode'] ?? '') !== 'rain' || ($a['water']['task'] ?? '') === 'drain';
    }

    /* =============================================================== the count */

    /**
     * The days, counted, for one mode: per day the water left above the stress line (v, 0 to 1), its phase, the
     * watering carrying it, the try layer; each watering's effect; the series for the page; the notes.
     *
     * @param  array  $info  per day: label (the stage), in (the crop is in the field), date
     */
    public static function run(array $c, array $apps, array $info, array $soil, string $mode): array
    {
        $n = count($info);
        $paddy = $c['water'] === 'flooded';
        $P = self::params($c, $soil);
        $wx = self::weather($c, $soil, $info[0]['date']->copy()->subDays(14), $info[$n - 1]['date'], $mode === 'dry' ? 'dry' : 'usual');
        $ev = [];
        $isTry = [];
        foreach ($apps as $a) {
            if (isset($a['water']) && self::counts(['mode' => $mode], $a)) {
                $ev[$a['date']->toDateString()][] = $a;
                $isTry[$a['i']] = (bool) $a['try'];
            }
        }
        $defMethod = $paddy ? 'flood' : ((float) ($c['ha'] ?? 0) > 0 && (float) $c['ha'] < 0.05 ? 'hose' : 'furrow');
        $dryEnd = WaterBudget::AWD_LOW + 0.5 * $P['taw'] * $P['r1'];

        // Two weeks of bare soil before the first day, against the rain that fell on it.
        $D = 0.0;
        $W = 0.0;
        for ($d = $info[0]['date']->copy()->subDays(14); $d->lt($info[0]['date']); $d->addDay()) {
            [$rain, $et0] = $wx['days'][$d->toDateString()] ?? [0.0, WaterBudget::ET0_MONTH[(int) $d->month], 0];
            $D = min($P['taw'] * $P['r0'], max(0.0, $D + 0.5 * $et0 * $P['etF'] - $rain * $P['rainEff']));
            $W = max(-$dryEnd, min(100.0, $W + $rain - 0.5 * $et0 - ($W > 0 ? $P['perc'] : 0.0)));
        }

        $rows = [];
        $ser = ['r' => [], 'e' => [], 'g' => [], 'lv' => [], 'top' => [], 'line' => [], 'k' => []];
        $carrier = null;
        $stressRun = 0;
        $maintain = null;
        $carried = [];
        $netBy = [];
        $endBy = [];
        foreach ($info as $i => $day) {
            $d = $day['date'];
            $ds = $d->toDateString();
            $t = $c['anchor'] ? (int) $c['anchor']->diffInDays($d, false) : ($c['tree'] ? 0 : -1);
            $growing = $c['tree'] || ($t >= 0 && $day['in']);
            $kc = ! $growing ? 0.5 : ($c['tree'] ? (float) $P['kc'] : WaterBudget::kc(min(1.0, $t / $P['H']), (float) $P['kc'], $paddy, false));
            $root = min($P['cap'], $c['tree'] ? $P['r1'] : ($t < 0 ? $P['r0'] : $P['r0'] + ($P['r1'] - $P['r0']) * min(1.0, $t / (0.5 * $P['H']))));
            [$rain, $et0, $kept] = $wx['days'][$ds] ?? [0.0, WaterBudget::ET0_MONTH[(int) $d->month], 0];
            $etc = $et0 * $P['etF'] * $kc;
            $tawR = $P['taw'] * $root;
            $raw = min(0.8, max(0.1, $P['p'] + 0.04 * (5 - $etc))) * $tawR;

            // A paddy is puddled for its planting: transplanted into standing water, wet seeded into soaked mud.
            if ($paddy && $t === 0 && ! $c['tree']) {
                $W = max($W, $c['counter'] === 'DAT' ? 30.0 : 0.0);
            }
            // The waterings of the day.
            $gross = 0.0;
            $fresh = false;
            foreach ($ev[$ds] ?? [] as $a) {
                $w = $a['water'];
                $put = 0.0;
                if ($paddy) {
                    if ($w['task'] === 'drain') {
                        $W = min($W, 0.0);
                        $maintain = null;
                    } else {
                        $target = (float) ($w['mm'] ?? self::PADDY_TARGET[$w['task']] ?? 50.0);
                        $put = max(0.0, $target - $W);
                        $W = max($W, $target);
                        $gross += $put / WaterBudget::PADDY_EFF;
                        $maintain = $w['task'] === 'maintain' ? $target : null;
                        $fresh = true;
                    }
                } elseif ($w['task'] !== 'drain') {
                    $m = self::METHODS[$w['method'] ?? $defMethod] ?? self::METHODS['furrow'];
                    // A depth given goes on as given, what reaches the roots of it; none, the root zone is filled up to
                    // what one watering of that kind puts in (a deep watering fills it whatever).
                    $put = $w['mm'] !== null ? min($D, (float) $w['mm'] * $m[2]) : ($w['task'] === 'overflow' ? $D : min($D, $m[3]));
                    $D -= $put;
                    $gross += $w['mm'] ?? ($put / $m[2]);
                    $maintain = $w['task'] === 'maintain' ? 0.0 : null;
                    $fresh = true;
                }
                $carrier = $a['i'];
                $stressRun = 0;
                $netBy[$a['i']] = ($netBy[$a['i']] ?? 0.0) + $put;
            }

            // The day: rain in, the crop's drink and the ground's share out.
            if ($paddy) {
                $W += $rain - $etc - ($W > 0 ? $P['perc'] : 0.0);
                $W = min(max(100.0, ($maintain ?? 50.0) + 50.0), $W);
                if ($maintain !== null && $W < $maintain) {
                    $gross += ($maintain - $W) / WaterBudget::PADDY_EFF;
                    $W = $maintain;
                }
                $W = max($W, -$dryEnd - 30);
            } else {
                $D = max(0.0, $D + $etc - $rain * $P['rainEff']);
                if ($maintain !== null) {
                    $gross += $D / (self::METHODS[$defMethod][2]);
                    $D = 0.0;
                }
                $D = min($D, $tawR);
            }
            if ($paddy) {
                $v = match (true) {
                    $W >= 10 => 1.0,
                    $W >= 0 => 0.85 + 0.015 * $W,
                    $W >= -WaterBudget::AWD_LOW => 0.85 - 0.5 * (-$W / WaterBudget::AWD_LOW),
                    default => 0.35 * max(0.0, 1 - (-$W - WaterBudget::AWD_LOW) / max(1.0, $dryEnd - WaterBudget::AWD_LOW)),
                };
            } else {
                $v = $D <= $raw ? 1 - 0.65 * $D / max(1.0, $raw) : 0.35 * max(0.0, ($tawR - $D) / max(1.0, $tawR - $raw));
            }
            // A soaking rain carries the crop from here, not the last watering.
            $rainWet = ! $fresh && $rain * ($paddy ? 1 : $P['rainEff']) >= self::RAIN_FILL && $v >= 0.75;
            if ($rainWet && $carrier !== null) {
                $endBy[$carrier] ??= ['rain', $ds];
                $carrier = null;
            }
            $ph = match (true) {
                $fresh || $rainWet || ($paddy && $W >= 10) => 'wet',
                $v >= 0.75 => 'moist',
                $v >= 0.35 => 'drying',
                default => 'dry',
            };
            $stressRun = $v < 0.35 ? $stressRun + 1 : 0;
            if ($carrier !== null && $stressRun > self::CARRY_STRESS) {
                $endBy[$carrier] ??= ['stress', $ds];
                $carrier = null;
            }
            if ($carrier !== null) {
                $carried[$carrier][] = $i;
            }
            $rows[] = ['v' => round(max(0.0, min(1.0, $v)), 2), 'p' => $ph, 'by' => $carrier !== null ? [$carrier] : [], 't' => $carrier !== null && ($isTry[$carrier] ?? false) ? round($v, 2) : 0];
            $ser['r'][] = round($rain, 1);
            $ser['e'][] = round($etc, 1);
            $ser['g'][] = round($gross, 1);
            $ser['lv'][] = round($paddy ? $W : $tawR - $D, 1);
            $ser['top'][] = $paddy ? null : round($tawR, 1);
            $ser['line'][] = $paddy ? -WaterBudget::AWD_LOW : round($tawR - $raw, 1);
            $ser['k'][] = (int) $kept;
        }

        // Each watering: how long it kept the crop out of stress.
        $effects = [];
        foreach ($apps as $a) {
            if (! isset($a['water']) || ! self::counts(['mode' => $mode], $a)) {
                continue;
            }
            $effects[$a['i']] = self::effectOf($a, $carried[$a['i']] ?? [], $rows, $info, $endBy[$a['i']] ?? null, (float) ($netBy[$a['i']] ?? 0), $paddy);
        }

        return ['rows' => $rows, 'effects' => $effects, 'series' => $ser, 'P' => $P, 'wx' => array_diff_key($wx, ['days' => 1]), 'paddy' => $paddy, 'dryEnd' => $dryEnd];
    }

    /** One watering's effect, for the page: its phases, until when it keeps the crop out of stress, what ends it. */
    private static function effectOf(array $a, array $ix, array $rows, array $info, ?array $end, float $net, bool $paddy): array
    {
        $date = $a['date'];
        $task = $a['water']['task'];
        $phases = [];
        $words = ['wet' => $paddy ? 'Standing water' : 'Soaking in', 'moist' => $paddy ? 'Soaked' : 'Moist', 'drying' => 'Drying, still fine', 'dry' => 'Too dry: stressed'];
        foreach ($ix as $k) {
            $p = $rows[$k]['p'];
            $last = $phases ? count($phases) - 1 : -1;
            if ($last >= 0 && $phases[$last]['p'] === $p && $phases[$last]['k'] === $k - 1) {
                $phases[$last]['to'] = $info[$k]['date']->toDateString();
                $phases[$last]['n']++;
                $phases[$last]['k'] = $k;
            } else {
                $phases[] = ['p' => $p, 'from' => $info[$k]['date']->toDateString(), 'to' => $info[$k]['date']->toDateString(), 'n' => 1, 'w' => $words[$p], 'k' => $k];
            }
        }
        $phases = array_map(fn ($x) => array_diff_key($x, ['k' => 1]), $phases);
        $stress = null;
        $okTo = null;
        $moist = 0;
        foreach ($ix as $k) {
            if ($rows[$k]['v'] < 0.35) {
                $stress = $info[$k]['date'];
                break;
            }
            $okTo = $info[$k]['date'];
            $moist += in_array($rows[$k]['p'], ['wet', 'moist'], true) ? 1 : 0;
        }
        $until = $ix ? $info[end($ix)]['date'] : $date;
        $okDays = $okTo ? (int) $date->diffInDays($okTo) + 1 : 0;
        $say = fn (Carbon $d) => $d->format('M j');
        if ($task === 'drain') {
            $note = 'Lets the standing water go. ' . ($stress ? 'The soil dries from here: the crop is short of water from ' . $say($stress) . ', unless rain or a watering comes first.' : 'The soil stays safe until the next watering or rain.');
        } else {
            $put = $net >= 1 ? ($paddy ? 'Fills the paddy to its water line (about ' . (int) round($net) . ' mm).' : 'Puts about ' . (int) round($net) . ' mm into the root zone.')
                : ($paddy ? 'The paddy was full already.' : 'The soil was moist already: little of it stays.');
            $after = match (true) {
                $stress !== null => ' The crop has enough until ' . $say($okTo ?? $date) . ', and is short of water from ' . $say($stress) . ' unless rain or another watering comes.',
                ($end[0] ?? '') === 'rain' => ' Rain on ' . $say(Carbon::parse($end[1])) . ' soaks the soil again before the crop runs short.',
                default => $ix ? ' The crop stays out of stress until the next watering.' : '',
            };
            $note = ($task === 'maintain' ? 'Keeps the water topped up every day until the next water task. ' : '') . $put . $after;
        }

        return [
            'from' => $date->toDateString(), 'full' => ($okTo ?? $date)->toDateString(), 'until' => $until->toDateString(), 'days' => [$moist, $okDays, count($ix)], 'amp' => 1.0,
            'dose' => null, 'kgHa' => null, 'ref' => null, 'el' => null, 'note' => $note, 'conf' => 'book', 'by' => null, 'move' => null, 'phases' => $phases,
            'water' => ['net' => (int) round($net), 'stress' => $stress?->toDateString(), 'ends' => $end[0] ?? ($ix ? 'next' : 'none'), 'task' => $task],
        ];
    }

    /* =============================================================== the lot's numbers */

    /** The crop, the soil and the variety, in the count's numbers. */
    private static function params(array $c, array $soil): array
    {
        $crop = (string) ($c['crop'] ?? '');
        $key = (string) ($soil['key'] ?? '') ?: $crop;
        $rootKey = isset(WaterBudget::ROOT[$key]) ? $key : (isset(WaterBudget::ROOT[$crop]) ? $crop : 'default');
        $K = array_merge(NpkModel::CLIMATE['default'], NpkModel::CLIMATE[$key] ?? NpkModel::CLIMATE[$crop] ?? []);
        $paddy = $c['water'] === 'flooded';
        $a = (array) ($soil['answers'] ?? []);

        // The soil: the lot's answer, else the area's typical soil, else counted as a loam.
        $tex = (string) ($a['texture'] ?? '');
        $texFrom = 'answers';
        if (! isset(WaterBudget::TAW[$tex])) {
            $tex = ['clay' => 'clay', 'loam' => 'loam', 'sandy' => 'sandy', 'silty' => 'silt', 'silt' => 'silt'][(string) ($soil['facts']['texture'] ?? '')] ?? '';
            $texFrom = $tex !== '' ? 'area' : 'none';
        }
        $tex = $tex ?: 'loam';
        $org = (string) ($a['organic'] ?? '');
        $taw = WaterBudget::TAW[$tex] * ($org === 'high' ? 1.1 : ($org === 'low' ? 0.95 : 1));
        $depth = (string) ($a['depth'] ?? '');
        $cap = ['shallow' => 0.25, 'medium' => 0.5][$depth] ?? 9.0;
        $hard = ($a['hardpan'] ?? '') === 'yes';
        if ($hard && ! $paddy) {
            $cap = min($cap, 0.4);
        }
        [$r0, $r1] = WaterBudget::ROOT[$rootKey];
        $p = (float) (WaterBudget::P[$rootKey] ?? WaterBudget::P['default']);
        $drain = (string) ($a['drainage'] ?? '');
        $perc = WaterBudget::PERC[$tex] * (['fast' => 1.5, 'day' => 1.0, 'slow' => 0.6, 'stands' => 0.4][$drain] ?? 1.0) * ($hard ? 0.7 : 1.0);
        $sun = (string) ($a['sun'] ?? '');
        $etF = ['part' => 0.85, 'shade' => 0.7][$sun] ?? 1.0;
        $steep = ($a['slope'] ?? '') === 'steep';

        // The variety: known to hold out in a dry spell, it lets the soil dry a little further before it suffers.
        $variety = trim((string) ($c['lot']->variety ?? ''));
        $tough = false;
        if ($variety !== '' && ! $c['tree'] && $crop !== '') {
            try {
                $rec = VarietyBook::find($crop, $variety);
            } catch (\Throwable $e) {
                report($e);
                $rec = null;
            }
            if ($rec) {
                $tough = (bool) array_intersect(array_map('strval', (array) ($rec['envs'] ?? [])), ['drought', 'upland'])
                    || (bool) preg_match('/drought/i', (string) json_encode([$rec['ratings'] ?? null, $rec['traits'] ?? null, $rec['where'] ?? null]));
            }
        }
        if ($tough) {
            $p = min(0.8, $p * 1.15);
        }
        $H = $c['anchor'] && $c['harvest'] ? max(30, (int) $c['anchor']->diffInDays($c['harvest'])) : max(30, (int) ($c['maturity'] ?: 110));

        return ['tex' => $tex, 'texFrom' => $texFrom, 'taw' => $taw, 'cap' => $cap, 'depth' => $depth, 'hard' => $hard, 'org' => $org, 'r0' => $r0, 'r1' => min($cap, $r1), 'rDeep' => $r1,
            'p' => $p, 'perc' => $perc, 'drain' => $drain, 'etF' => $etF, 'sun' => $sun, 'rainEff' => $steep ? 0.65 : WaterBudget::RAIN_EFF, 'steep' => $steep,
            'kc' => (float) $K['kc'], 'H' => $H, 'variety' => $variety, 'tough' => $tough];
    }

    /**
     * The rain and the air's pull, a day each from $from to $to: [rain mm, ET0 mm, kept 1|0]. The days kept at the
     * place as they were; the others from one year of the climate shelf's history ($pick usual: the middle one by
     * its rain over those days; dry: the driest in five); with no history, no rain and the month's usual ET0.
     */
    private static function weather(array $c, array $soil, Carbon $from, Carbon $to, string $pick): array
    {
        $lot = $c['lot'];
        $custom = $c['custom'] ?? null;
        $place = trim((string) ($soil['facts']['place'] ?? ''));
        $loc = null;
        if (! $custom && $lot->pinLat !== null && $lot->pinLng !== null && ((float) $lot->pinLat != 0 || (float) $lot->pinLng != 0)) {
            $loc = ['lat' => (float) $lot->pinLat, 'lng' => (float) $lot->pinLng, 'label' => $place ?: 'the lot\'s pin'];
        } elseif ($place !== '') {
            try {
                $loc = ClimateStore::locate($place, null, null, false);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        static $memo = [];
        $mk = ($loc ? round($loc['lat'], 3) . ',' . round($loc['lng'], 3) : 'none') . '|' . $from->toDateString() . '|' . $to->toDateString() . '|' . $pick;
        if (isset($memo[$mk])) {
            return $memo[$mk];
        }
        $kept = [];
        $T = null;
        if ($loc) {
            $kept = WeatherDays::kept((float) $loc['lat'], (float) $loc['lng'], $from->toDateString(), $to->toDateString());
            try {
                $T = ClimateStore::weekTable((float) $loc['lat'], (float) $loc['lng']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $days = [];
        $need = [];
        $keptTo = null;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $ds = $d->toDateString();
            $k = $kept[$ds] ?? null;
            if ($k && $k['rain'] !== null && $k['et0'] !== null) {
                $days[$ds] = [(float) $k['rain'], (float) $k['et0'], 1];
                $keptTo = $ds;

                continue;
            }
            $need[] = [$ds, intdiv($d->dayOfYear - 1, 7) + 1, $d->year - $from->year, (int) $d->month];
        }
        $year = null;
        $years = 0;
        if ($T && $need) {
            $tot = [];
            foreach (array_keys($T) as $y) {
                $ok = 0;
                $rain = 0.0;
                foreach ($need as [, $w, $yo]) {
                    $wk = $T[$y + $yo][$w] ?? null;
                    if ($wk && $wk['days']) {
                        $ok++;
                        $rain += $wk['rain'] / $wk['days'];
                    }
                }
                if ($ok >= count($need) * 0.9) {
                    $tot[$y] = $rain;
                }
            }
            $years = count($tot);
            if ($years >= 3) {
                asort($tot);
                $ys = array_keys($tot);
                $year = (int) $ys[$pick === 'dry' ? (int) round(0.2 * ($years - 1)) : intdiv($years, 2)];
            }
        }
        foreach ($need as [$ds, $w, $yo, $m]) {
            $wk = $year !== null ? ($T[$year + $yo][$w] ?? null) : null;
            $days[$ds] = $wk && $wk['days'] ? [$wk['rain'] / $wk['days'], $wk['et0'] / $wk['days'], 0] : [0.0, WaterBudget::ET0_MONTH[$m], 0];
        }

        return $memo[$mk] = ['days' => $days, 'year' => $year, 'years' => $years, 'keptTo' => $keptTo, 'place' => $loc ? (string) ($loc['label'] ?: $place) : null,
            'pick' => $pick, 'ahead' => count($need)];
    }

    /* =============================================================== in words */

    /**
     * The reading's water numbers and notes: stress days, rain, waterings, what the crop drank, how often it needs
     * water at its peak, and the notes on the soil, the weather, the crop and the waterings.
     */
    public static function summary(array $run, array $days, array $info, array $apps, array $c, array $soil, array $check): array
    {
        $P = $run['P'];
        $ser = $run['series'];
        $paddy = $run['paddy'];
        $mode = $check['mode'];
        $stress = 0;
        $stressCrit = 0;
        $rain = 0.0;
        $use = 0.0;
        $irr = 0.0;
        $peak = [];
        foreach ($days as $i => $d) {
            if (empty($info[$i]['in'])) {
                continue;
            }
            $rain += $ser['r'][$i];
            $use += $ser['e'][$i];
            $irr += $ser['g'][$i];
            if ($d['v'] < 0.35 && $d['c'] >= 1) {
                $stress++;
                $stressCrit += $d['c'] >= 2 ? 1 : 0;
            }
            $peak[] = $ser['e'][$i];
        }
        rsort($peak);
        $peakUse = $peak ? array_sum(array_slice($peak, 0, max(1, (int) ceil(count($peak) * 0.25)))) / max(1, min(count($peak), (int) ceil(count($peak) * 0.25))) : 0.0;
        $startUse = $peak ? min($peak) : 0.0;
        $tawPeak = $P['taw'] * $P['r1'];
        $rawPeak = min(0.8, max(0.1, $P['p'] + 0.04 * (5 - $peakUse))) * $tawPeak;
        $every = $paddy
            ? (int) max(1, round((50 + WaterBudget::AWD_LOW) / max(0.5, $peakUse + $P['perc'])))
            : (int) max(1, round($rawPeak / max(0.5, $peakUse)));
        $waterings = array_values(array_filter($apps, fn ($a) => isset($a['water']) && $a['water']['task'] !== 'drain'));

        // The notes, grouped as the page groups them: the soil, then the water.
        $notes = [RangeEffectLogic::soilFactNote($soil)];
        $texSay = ['sandy' => 'Sandy soil', 'loam' => 'Loam', 'silt' => 'Silty soil', 'clay' => 'Clay', 'heavy_clay' => 'Heavy clay'][$P['tex']];
        $bits = [];
        if ($P['org'] === 'high') {
            $bits[] = 'a little more for its organic matter';
        }
        if ($P['depth'] === 'shallow' || $P['depth'] === 'medium') {
            $bits[] = 'the roots stop where the rock or gravel starts, at about ' . (int) round($P['cap'] * 100) . ' cm';
        } elseif ($P['hard'] && ! $paddy) {
            $bits[] = 'the hard layer stops the roots at about 40 cm';
        }
        $from = ['answers' => '', 'area' => ' (the area\'s typical soil)', 'none' => ' (no soil described yet: counted as a loam)'][$P['texFrom']];
        if ($paddy) {
            $notes[] = ['k' => 'soil', 'icon' => '🌊', 'tone' => 'info', 'title' => 'What your paddy loses',
                'text' => $texSay . $from . ': about ' . self::num($P['perc']) . ' mm of its standing water sinks into the ground a day'
                    . ($P['hard'] ? ', held back by the plow pan' : '') . (in_array($P['drain'], ['slow', 'stands'], true) ? ', slowly, as it drains slowly' : ($P['drain'] === 'fast' ? ', quickly, as it drains fast' : ''))
                    . ', besides what the crop drinks. Once the water is gone the soil dries from the top: down to about 15 cm below the surface it is still safe for rice, past that the crop is short of water.'];
        } else {
            $notes[] = ['k' => 'soil', 'icon' => '🪣', 'tone' => 'info', 'title' => 'What your soil holds',
                'text' => $texSay . $from . ' holds about ' . (int) round($P['taw']) . ' mm of water for the roots in a meter of soil' . ($bits ? ', ' . implode(', and ', $bits) : '') . '. '
                    . 'With the roots at their deepest, about ' . (int) round($P['r1'] * 100) . ' cm, a full root zone holds about ' . (int) round($tawPeak) . ' mm, and the crop starts to suffer once about '
                    . (int) round($rawPeak) . ' mm of it is used.' . ($P['steep'] ? ' On a steep slope part of the rain runs off before it soaks in.' : '')];
        }

        // The weather.
        $wx = $run['wx'];
        // Days kept only before the first day drawn (the two weeks the count starts from) are not worth a word.
        if ($wx['keptTo'] && $wx['keptTo'] < $info[0]['date']->toDateString()) {
            $wx['keptTo'] = null;
        }
        $past = $wx['keptTo'] ? 'Up to ' . Carbon::parse($wx['keptTo'])->format('M j') . ', the rain and heat as they were at ' . ($wx['place'] ?: 'the place') . '. ' : '';
        $ahead = match (true) {
            ! $wx['place'] => 'This lot has no place set, so the days are counted with no rain and the usual heat of each month: the waterings carry all of it. Set the lot\'s place to count its weather.',
            $wx['year'] === null => 'No weather history is kept for ' . $wx['place'] . ' yet, so ' . ($past ? 'the days after' : 'the days') . ' are counted with no rain and the usual heat of each month.',
            default => ($past ? 'After that, ' : 'The days ') . ($past ? 'the' : 'are counted with the') . ' rain and heat of ' . $wx['year'] . ', '
                . ($wx['pick'] === 'dry' ? 'the driest year in five' : 'a usual year') . ' of the ' . $wx['years'] . ' years kept for this place.',
        };
        $notes[] = ['k' => 'water', 'icon' => '🌦️', 'tone' => $wx['place'] && $wx['year'] !== null ? 'info' : 'warn', 'title' => 'The weather it counts',
            'text' => $past . $ahead . ($mode === 'rain' ? ' No watering is counted: this is what the rain alone gives.' : '')];

        // The crop and its variety.
        $notes[] = ['k' => 'water', 'icon' => '🌱', 'tone' => 'info', 'title' => 'What the crop drinks',
            'text' => (string) ($c['cropLabel'] ?? 'The crop') . ' drinks about ' . self::num($startUse) . ' mm a day at the start and up to ' . self::num($peakUse) . ' mm a day at its peak, with the heat of the days counted'
                . ($P['etF'] < 1 ? ', less in the shade it gets' : '') . '. ' . ($paddy ? '' : 'Its roots go from about ' . (int) round($P['r0'] * 100) . ' cm to about ' . (int) round($P['r1'] * 100) . ' cm. ')
                . ($P['variety'] !== '' ? $P['variety'] . ' is counted at ' . $P['H'] . ' days to harvest' . ($P['tough'] ? ', and is known to hold out in dry spells, so the soil may dry a little further before it suffers' : '') . '.' : 'Counted at ' . $P['H'] . ' days to harvest.')];

        // The waterings.
        $tries = count(array_filter($waterings, fn ($a) => $a['try']));
        if ($mode === 'rain') {
            $text = ($stress ? 'With the rain alone this season has ' . $stress . ' stress day' . ($stress === 1 ? '' : 's') . ', ' . $stressCrit . ' of them on stages that matter.'
                : 'The rain alone keeps the crop out of stress all season.') . ' Compare it with Water to see what your waterings add.';
            $notes[] = ['k' => 'water', 'icon' => '🌧️', 'tone' => $stressCrit ? 'warn' : 'good', 'title' => 'The rain alone', 'text' => $text];
        } else {
            $w = count($waterings);
            $text = ($w ? ($w === 1 ? 'One watering' : $w . ' waterings') . ' on this lot' . ($tries ? ', ' . $tries . ' of them added by you' : '') . ', about ' . (int) round($irr) . ' mm in all. ' : 'No watering on this lot\'s tasks yet: only the rain is counted. ')
                . ($paddy
                    ? 'At its peak, 5 cm of standing water lasts about ' . (int) max(1, round(50 / max(0.5, $peakUse + $P['perc']))) . ' days with no rain, and the field is safe for about ' . $every . ' days in all before the crop is short.'
                    : 'At its peak, a full root zone lasts about ' . $every . ' days with no rain before the crop is short of water.')
                . ($stressCrit ? ' ' . $stressCrit . ' stress day' . ($stressCrit === 1 ? ' falls' : 's fall') . ' on stages that matter: add a watering a day or two before each.' : ' No stress on the stages that matter.');
            $notes[] = ['k' => 'water', 'icon' => '🚿', 'tone' => $stressCrit ? 'warn' : 'good', 'title' => 'Your waterings', 'text' => $text];
        }

        return [
            'facts' => ['paddy' => $paddy, 'mode' => $mode, 'stress' => $stress, 'stressCrit' => $stressCrit, 'waterings' => count($waterings), 'tries' => $tries,
                'rain' => (int) round($rain), 'irr' => (int) round($irr), 'use' => (int) round($use), 'every' => $every, 'peak' => round($peakUse, 1),
                'year' => $wx['year'], 'years' => $wx['years'], 'keptTo' => $wx['keptTo'], 'place' => $wx['place'], 'tex' => $P['tex'],
                'taw' => (int) round($tawPeak), 'raw' => (int) round($rawPeak), 'dryEnd' => (int) round($run['dryEnd'])],
            'r' => $ser['r'], 'e' => $ser['e'], 'g' => $ser['g'], 'lv' => $ser['lv'], 'top' => $ser['top'], 'line' => $ser['line'], 'k' => $ser['k'],
            'notes' => $notes,
        ];
    }

    /** A depth in cm, as a farmer says it: "5 cm", "2.5 cm". */
    public static function cm(float $mm): string
    {
        return rtrim(rtrim(number_format($mm / 10, 1, '.', ''), '0'), '.') . ' cm';
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }
}
