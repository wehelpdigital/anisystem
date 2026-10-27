<?php

namespace App\Support;

use App\Http\Controllers\CropProtocolController;
use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsScheduleLot;

/**
 * An Anee Auto lot's goal, counted by the app and not guessed (2026-10-28, the owner).
 * First ask: "realistic goal is 6.5t/ha when i tried it, can you fix how the logic works? because for
 * example in the npk calculator, it really depends in the # of seeds planted in corn. so there are
 * factors or micronutrients." Then the flow: "first, user should be asked how much to plant, the
 * spacing if needed (all optional). this is before the analysis of anee. then based on the calculation
 * of npk calculator plus the anee analysis, show realistic to achieve then achievable but risky (then
 * show the high potential). then user selects about it (each selection will have its own requirement
 * for fertilizer first so that user can choose what is the target)."
 *
 * The count, each step said in words (steps):
 *  1. the variety's best on record (NutrientBook, the rule NPK Plus uses), else the reading's,
 *     else the Philippine usual counted as 80% of a best;
 *  2. what the stand carries: the seed calculator for the spacing or the amount planted, with the
 *     variety's yield parts (the Crop Protocol's standYield); a thin stand by NPK Plus's seed check
 *     when only that is known; a full usual stand when the farmer said nothing;
 *  3. the field: NPK Plus's caps for salt, sodium and short water, the water taken as the lower of
 *     the cap and the share of the season's need it meets (twenty years of the place, ENSO, the pump);
 *  4. the elements likely short on this soil, said as what every goal needs fed.
 * Then three goals (LEVELS), each with the fertilizer it takes (NpkNeed through needFor, one door
 * with NPK Plus and the Crop Protocol), and the farmer picks one: that is the lot's target.
 */
final class AutoGoal
{
    /**
     * The goals: label, icon, the share of the variety's best (null: the traditional yield, counted from
     * the usual harvest), whether the field's caps hold it down, what it rests on, and the secondary and
     * micro elements fed for it (2026-10-28, the owner: "add traditional yield (unsecured) the one that is
     * low yield with low fertilizer use ... in higher yields add the micros"): none for the traditional
     * one, the likely short ones for the realistic one, and those to watch too for the higher ones.
     */
    public const LEVELS = [
        'traditional' => ['Traditional yield (unsecured)', '🌾', null, false, 'What farms around here usually harvest with the usual fertilizer and no micronutrients. Nothing secures it against the shortages of this soil.', []],
        'realistic' => ['Realistic to achieve', '✅', 0.8, true, 'What a well run field usually reaches, held to your stand, your water and your soil.', ['likely']],
        'risky' => ['Achievable but risky', '⚠️', 0.9, true, 'More fertilizer, and the weather and the water must go right.', ['likely', 'watch']],
        'high' => ['High potential', '🏆', 1.0, false, 'The best on record: a full stand, water whenever it is needed, and the soil put right.', ['likely', 'watch']],
    ];

    /** The usual harvest beside the farms around (the lot's yieldLast answer): what the traditional yield leans by. */
    private const LAST = ['worse' => 0.85, 'same' => 1.0, 'better' => 1.15, 'better_care' => 1.15];

    /** The inputs a lot keeps, as saved in the season's answers (answers.goals[lotId]): its planting and the goal picked. */
    public static function clean(array $g): array
    {
        $num = fn ($v, $lo, $hi) => is_numeric($v) && (float) $v >= $lo && (float) $v <= $hi ? round((float) $v, 3) : null;

        return array_filter([
            'by' => in_array($g['by'] ?? '', ['spacing', 'amount'], true) ? (string) $g['by'] : null,
            'row' => $num($g['row'] ?? null, 1, 3000), 'plant' => $num($g['plant'] ?? null, 1, 3000), 'perHill' => $num($g['perHill'] ?? null, 1, 50),
            'amount' => $num($g['amount'] ?? null, 0.001, 100000000), 'unit' => isset(NpkNeed::SEED_UNITS[$g['unit'] ?? '']) ? (string) $g['unit'] : null,
            'germ' => $num($g['germ'] ?? null, 1, 100),
            'pick' => isset(self::LEVELS[$g['pick'] ?? '']) ? (string) $g['pick'] : null,
        ], fn ($v) => $v !== null);
    }

    /** The Crop Protocol's way in for an Anee Auto lot's (transplanted, direct, planted, tree). */
    public static function protoMethod(?string $crop, ?string $method): string
    {
        $ms = CropProtocolController::methodsFor((string) $crop);
        $want = match ((string) $method) {
            'transplanted' => ['transplanted', 'nursery'],
            'tree' => ['tree_seedlings', 'suckers', 'nursery'],
            default => ['direct', 'direct_wet', 'direct_dry', 'cuttings', 'tubers', 'sets', 'suckers', 'sprouted', 'nursery'],
        };
        foreach ($want as $m) {
            if (in_array($m, $ms, true)) {
                return $m;
            }
        }

        return (string) ($ms[0] ?? 'direct');
    }

    /** How a lot's planting is asked: its units, its usual spacing and whether germination counts. */
    public static function plantOptions(?string $crop, string $protoMethod): array
    {
        $sp = PlantAmount::spacing($crop, $protoMethod);
        $units = PlantAmount::units($crop, $protoMethod);

        return [
            'units' => $units,
            'unitWords' => (object) array_combine($units, array_map(fn ($u) => (string) (NpkNeed::SEED_UNITS[$u][1] ?? $u), $units)),
            'spacing' => $sp ? ['row' => $sp['row'], 'plant' => $sp['plant'], 'perHill' => $sp['perHill'], 'meters' => $sp['meters'], 'word' => $sp['word'], 'note' => $sp['note'], 'standBy' => $sp['standBy']] : null,
            'germ' => PlantAmount::germ($crop, $protoMethod),
        ];
    }

    /** Every lot of the season: lotId => its goals. */
    public static function forSeason(AsAutoSeason $auto): array
    {
        $out = [];
        $reads = collect((array) (($auto->analysis ?? [])['lots'] ?? []))->keyBy(fn ($r) => (int) ($r['lotId'] ?? 0));
        foreach (AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get() as $al) {
            $lot = AsScheduleLot::find($al->lotId);
            if ($lot && (int) $lot->deleteStatus === 1) {
                $out[(int) $lot->id] = self::forLot($auto, $lot, $al, (array) (($reads->get((int) $lot->id) ?? [])['variety'] ?? []));
            }
        }

        return $out;
    }

    /** One lot: the stand it plants, the three goals with their fertilizer, the one picked, and the steps behind them. */
    public static function forLot(AsAutoSeason $auto, AsScheduleLot $lot, ?AsAutoLot $al, array $readVariety = []): array
    {
        $a = (array) $auto->answers;
        $goals = (array) ($a['goals'] ?? []);
        $g = self::clean((array) ($goals[(string) $lot->id] ?? []));
        $crop = (string) $lot->crop;
        $method = self::protoMethod($crop, $al?->method);
        $out = ['inputs' => $g, 'pick' => $g['pick'] ?? null, 'method' => $method, 'plant' => self::plantOptions($crop, $method),
            'levels' => null, 'tPerHa' => null, 'steps' => [], 'feed' => [], 'standSay' => '', 'standKnown' => false];
        if ($crop === '') {
            return $out;
        }
        $npkKey = NpkNeed::cropKey($crop, $method);
        $c = NpkNeed::crop($npkKey);
        $areaHa = $lot->lotSize ? (float) $lot->lotSize * match ($lot->lotSizeUnit) { 'sqm' => 0.0001, 'acre' => 0.404686, default => 1 } : 1.0;
        $out['areaHa'] = round($areaHa, 4);

        // The need model on the lot's soil, water, season and planting (one door: the Crop Protocol's).
        $soil = (array) ($a['soilRead'] ?? []);
        $waterClass = (string) ((AutoBook::WATER[$a['water'] ?? ''] ?? [])[2] ?? 'irrigated');
        $sp = ($g['by'] ?? null) === 'spacing' && ! empty($g['row']) && ! empty($g['plant']) ? ['row' => $g['row'], 'plant' => $g['plant'], 'perHill' => $g['perHill'] ?? null] : null;
        $byAmount = ($g['by'] ?? null) === 'amount' && ! empty($g['amount']);
        $p = [
            'crop' => $crop, 'method' => $method, 'variety' => (string) $lot->variety, 'area' => $areaHa, 'yieldUnit' => 'tons',
            'seedAmount' => $byAmount ? $g['amount'] : null, 'seedUnit' => $byAmount ? ($g['unit'] ?? 'kg') : null, 'germination' => $g['germ'] ?? null, 'spacing' => $sp,
            'soil' => in_array($soil['texture'] ?? '', ['clay', 'loam', 'sandy', 'silty'], true) ? $soil['texture'] : 'unsure',
            'soilConditions' => array_values(array_intersect((array) ($soil['conditions'] ?? []), ['sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged', 'acidic', 'alkaline'])),
            'ph' => is_numeric($soil['tested'] ?? null) ? (float) $soil['tested'] : null,
            'water' => ['partial' => 'limited', 'rainfed' => 'rainfed'][$waterClass] ?? 'irrigated',
            'fieldProfile' => ! empty($a['fieldProfile']) ? (array) $a['fieldProfile'] : null,
            'location' => trim(implode(', ', array_filter([$lot->locTown, $lot->locProvince]))),
            'day' => $al?->fieldDate?->toDateString(),
        ];

        // 1. The best: the variety's on record, else the reading's, else the usual counted as 80% of a best.
        $P = NutrientBook::varietyPotential($npkKey ?? $crop, $lot->variety);
        $from = 'record';
        if (! $P && (float) ($readVariety['yieldPotentialTPerHa'] ?? 0) > 0) {
            [$P, $from] = [(float) $readVariety['yieldPotentialTPerHa'], 'reading'];
        }
        if (! $P && ! empty($c['typicalYield']['hi'])) {
            [$P, $from] = [(float) $c['typicalYield']['hi'] / 0.8, 'usual'];
        }
        $r = rescue(fn () => CropProtocolController::needFor($p + ['potential' => $P ?: null, 'targetYield' => null]), ['known' => false], false);

        // 2. The stand: what the planting carries, said on the lot card before any goal.
        $hills = (($out['plant']['spacing'] ?? [])['standBy'] ?? 'plants') === 'hills';
        $st = $r['stand'] ?? null;
        $sd = $r['seed'] ?? null;
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        $standLo = $standHi = null;
        $sf = 1.0;
        if ($st && ! empty($st['hi'])) {
            [$standLo, $standHi] = [(float) $st['lo'], (float) $st['hi']];
            $out['standKnown'] = true;
            $out['standSay'] = 'About ' . number_format(round((float) $st['plants'], -2)) . ($hills ? ' hills stand filled' : ' plants stand') . ' a hectare.';
        } elseif ($sd) {
            $out['standKnown'] = true;
            $out['standSay'] = (string) $sd['words'];
            $sf = ! empty($sd['thin']) ? (float) $sd['sf'] : 1.0;
        }
        if (! $P) {
            return $out;
        }
        // The variety's short name: "DEKALB 8282S VT Double PRO RIB Complete (DK 8282S)" reads as DK 8282S.
        $vname = ! $lot->variety ? 'this crop' : (preg_match('/\(([^()]{2,30})\)\s*$/', (string) $lot->variety, $vm) ? $vm[1] : (string) $lot->variety);
        $out['potential'] = round($P, 2);
        $out['steps'][] = ['🏆', match ($from) {
            'record' => 'The best ' . $vname . ' has given on record',
            'reading' => 'The best Anee found for ' . $vname,
            default => 'A good field of this crop, from the Philippine usual',
        }, $fmt($P) . ' t/ha'];
        if ($standHi !== null) {
            $out['steps'][] = ['🌱', 'Your stand of about ' . number_format(round((float) $st['plants'], -2)) . ($hills ? ' hills' : ' plants') . ' a hectare carries', $fmt($standLo) . ' to ' . $fmt($standHi) . ' t/ha'];
        } elseif ($sf < 1) {
            $out['steps'][] = ['🌱', 'A thin stand for this crop: ' . $sd['words'], '× ' . round($sf * 100) . '%'];
        } elseif ($sd) {
            $out['steps'][] = ['🌱', 'Your planting makes a full stand', 'holds'];
        } else {
            $out['steps'][] = ['🌱', 'A full usual stand is counted: say how much you plant on the Lots step for surer goals', 'assumed'];
        }

        // 3. The field: the water (the lower of its cap and the season's share), salt and sodium.
        $k = 1.0;
        if ($c) {
            $conds = array_values(array_intersect((array) ($soil['conditions'] ?? []), ['saline', 'sodic']));
            $cap = NpkNeed::fieldCap($c, true, $waterClass, $conds);
            $kind = str_starts_with($c['key'], 'rice') ? 'rice' : 'other';
            $season = $r['season'] ?? null;
            $share = $season && isset($season['waterShare']) ? (float) $season['waterShare'] : null;
            $waterCap = $waterClass === 'irrigated' ? 1.0 : (float) NpkModel::FIELD['caps'][$waterClass][$kind];
            $wk = $share !== null && $share < 0.97 ? min($waterCap, max(NpkModel::FIELD['capFloor'], $share)) : $waterCap;
            if ($wk < 0.995) {
                $k *= $wk;
                $out['steps'][] = ['💧', $share !== null && $share < $waterCap ? 'The water meets about ' . round($share * 100) . '% of what the crop drinks this season' . (! empty($season['enso']) ? ' (' . $season['enso'] . ')' : '')
                    : (AutoBook::WATER[$a['water'] ?? ''][0] ?? 'The water is not sure'), '× ' . round($wk * 100) . '%'];
            }
            foreach ($cap['why'] as [$why, $f]) {
                if (in_array($why, ['saline', 'sodic'], true)) {
                    $k *= (float) $f;
                    $out['steps'][] = [$why === 'sodic' ? '🧂' : '🌊', $why === 'sodic' ? 'Sodium in the soil holds the roots back' : 'Salt in the soil holds the roots back', '× ' . round((float) $f * 100) . '%'];
                }
            }
            $k = max(NpkModel::FIELD['capFloor'], $k);
        }

        // 4. What every goal needs fed: the elements likely short on this soil.
        foreach ((array) (($r['micro'] ?? [])['likely'] ?? []) as $m) {
            $out['feed'][] = $m['name'];
        }
        if ($out['feed']) {
            $out['steps'][] = ['🧪', 'The goals above the traditional one need ' . self::join(array_map('mb_strtolower', $out['feed'])) . ' fed: likely short on this soil', ''];
        }

        // The goals: the traditional yield from the usual harvest of these parts (leaned by how the lot's last harvest
        // compared with its neighbours), the realistic one at the stand's middle, the risky one at its top, the high
        // potential at the best the stand allows with nothing held back. Each with the fertilizer it takes.
        $fp = ! empty($a['fieldProfile']) ? FieldProfile::normalize((array) $a['fieldProfile']) : [];
        $usual = ! empty($c['typicalYield']['hi']) ? (float) $c['typicalYield']['hi'] : $P * 0.45;
        $lean = self::LAST[$fp['yieldLast'] ?? ''] ?? 1.0;
        $levels = [];
        foreach (self::LEVELS as $key => [$label, $icon, $share, $capped, $say]) {
            if ($share === null) {
                $t = $usual * $lean;
            } else {
                $t = $P * $share;
                if ($standHi !== null) {
                    $t = min($t, $key === 'realistic' ? ($standLo + $standHi) / 2 : $standHi);
                }
                $t *= $sf * ($capped ? $k : 1.0);
            }
            $levels[$key] = ['key' => $key, 'label' => $label, 'icon' => $icon, 'say' => $say, 't' => round($t, 1)];
        }
        // Never out of order: a lower goal never above a higher one.
        $levels['traditional']['t'] = min($levels['traditional']['t'], $levels['realistic']['t']);
        $levels['risky']['t'] = max($levels['risky']['t'], $levels['realistic']['t']);
        $levels['high']['t'] = max($levels['high']['t'], $levels['risky']['t']);
        if ($lean !== 1.0) {
            $out['steps'][] = ['🌾', 'Your last harvest ' . ($lean > 1 ? 'beat' : 'fell short of') . ' the farms around it: the traditional yield leans ' . ($lean > 1 ? 'up' : 'down'), '× ' . round($lean * 100) . '%'];
        }
        // The goals already carry the stand, so the need is counted without it (NPK Plus would thin the goal a second time).
        $pNeed = array_merge($p, ['seedAmount' => null, 'seedUnit' => null, 'germination' => null, 'spacing' => null]);
        foreach ($levels as $key => $lv) {
            $n = rescue(fn () => CropProtocolController::needFor($pNeed + ['potential' => $P, 'targetYield' => $lv['t']]), ['known' => false], false);
            $need = array_intersect_key((array) ($n['need'] ?? []), array_flip(['N', 'P2O5', 'K2O']));
            // The secondary and micro elements fed for this goal (LEVELS), the likely short ones first.
            $micros = [];
            $feed = self::LEVELS[$key][5];
            foreach ((array) ($n['micros'] ?? []) as $el => $m) {
                if (in_array($m['level'] ?? '', $feed, true) && empty($m['beneficial']) && (float) $m['need'] > 0) {
                    $micros[] = ['el' => $el, 'name' => $m['name'], 'need' => round((float) $m['need'], 2), 'lot' => round((float) $m['need'] * $areaHa, 2), 'likely' => $m['level'] === 'likely'];
                }
            }
            usort($micros, fn ($x, $y) => $y['likely'] <=> $x['likely']);
            $levels[$key] += ['lotT' => round($lv['t'] * $areaHa, 2), 'need' => $need, 'lotNeed' => array_map(fn ($v) => (int) round($v * $areaHa), $need), 'micros' => $micros,
                // What the traditional yield leaves unfed: the shortages that stay.
                'unfed' => $feed === [] ? $out['feed'] : []];
        }
        $out['levels'] = $levels;
        $out['tPerHa'] = $out['pick'] ? $levels[$out['pick']]['t'] : null;

        return $out;
    }

    /** The steps and the three goals as words for a prompt. */
    public static function words(array $goal): string
    {
        $s = implode('; ', array_map(fn ($x) => $x[1] . ($x[2] !== '' ? ': ' . $x[2] : ''), (array) ($goal['steps'] ?? [])));
        foreach ((array) ($goal['levels'] ?? []) as $lv) {
            $s .= '. ' . $lv['label'] . ': ' . $lv['t'] . ' t/ha, needs N ' . ($lv['need']['N'] ?? '?') . ', P2O5 ' . ($lv['need']['P2O5'] ?? '?') . ', K2O ' . ($lv['need']['K2O'] ?? '?') . ' kg/ha';
        }

        return $s;
    }

    private static function join(array $xs): string
    {
        return count($xs) > 1 ? implode(', ', array_slice($xs, 0, -1)) . ' and ' . end($xs) : (string) ($xs[0] ?? '');
    }
}
