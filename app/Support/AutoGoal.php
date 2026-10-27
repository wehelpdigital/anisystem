<?php

namespace App\Support;

use App\Http\Controllers\CropProtocolController;
use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsScheduleLot;

/**
 * An Anee Auto lot's realistic goal, counted by the app and not guessed
 * (2026-10-28, the owner: "realistic goal is 6.5t/ha when i tried it, can you
 * fix how the logic works? because for example in the npk calculator, it
 * really depends in the # of seeds planted in corn. so there are factors or
 * micronutrients. also goal is not selected like if the goal is max yield").
 *
 * The count, step by step, each step said in words:
 *  1. the variety's best on record (NutrientBook, the rule NPK Plus uses), else
 *     the reading's, else the Philippine usual counted as 80% of a best;
 *  2. the farmer's aim (AIMS: how far toward that best), or their own target;
 *  3. what the stand carries: the seed calculator for the spacing or the amount
 *     planted, with the variety's yield parts (the Crop Protocol's standYield);
 *     a thin stand by NPK Plus's seed check when only that is known;
 *  4. the field: NPK Plus's caps for salt, sodium and short water, the water
 *     taken as the lower of the cap and the share of the season's need the
 *     water meets (twenty years of the place, the ENSO outlook, the pump);
 *  5. the elements likely short on this soil, said as what the goal needs fed.
 * One door to the need model: CropProtocolController::needFor, so the goal reads
 * the same soil, water and season as NPK Plus and the Crop Protocol.
 */
final class AutoGoal
{
    /** The farmer's aim: label, words, icon, and how far toward the variety's best it reaches. */
    public const AIMS = [
        'yield' => ['Highest yield', 'Spend what it takes for the biggest harvest', '🌾', 0.9],
        'balanced' => ['In the middle', 'A good harvest without overspending', '⚖️', 0.8],
        'cost' => ['Lower cost', 'The leanest inputs that still make a decent crop', '💰', 0.65],
    ];

    /** The inputs a lot's goal keeps, as saved in the season's answers (answers.goals[lotId]). */
    public static function clean(array $g): array
    {
        $num = fn ($v, $lo, $hi) => is_numeric($v) && (float) $v >= $lo && (float) $v <= $hi ? round((float) $v, 3) : null;

        return array_filter([
            'aim' => isset(self::AIMS[$g['aim'] ?? '']) ? (string) $g['aim'] : 'balanced',
            'by' => in_array($g['by'] ?? '', ['spacing', 'amount'], true) ? (string) $g['by'] : null,
            'row' => $num($g['row'] ?? null, 1, 3000), 'plant' => $num($g['plant'] ?? null, 1, 3000), 'perHill' => $num($g['perHill'] ?? null, 1, 50),
            'amount' => $num($g['amount'] ?? null, 0.001, 100000000), 'unit' => isset(NpkNeed::SEED_UNITS[$g['unit'] ?? '']) ? (string) $g['unit'] : null,
            'germ' => $num($g['germ'] ?? null, 1, 100), 'target' => $num($g['target'] ?? null, 0.01, 500),
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
            'spacing' => $sp ? ['row' => $sp['row'], 'plant' => $sp['plant'], 'perHill' => $sp['perHill'], 'meters' => $sp['meters'], 'word' => $sp['word'], 'note' => $sp['note']] : null,
            'germ' => PlantAmount::germ($crop, $protoMethod),
        ];
    }

    /** Every lot of the season: lotId => its goal. */
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

    /** One lot's goal: the number, the steps that led to it, and how its planting is asked. */
    public static function forLot(AsAutoSeason $auto, AsScheduleLot $lot, ?AsAutoLot $al, array $readVariety = []): array
    {
        $a = (array) $auto->answers;
        $goals = (array) ($a['goals'] ?? []);
        $g = self::clean((array) ($goals[(string) $lot->id] ?? []));
        $aim = $g['aim'];
        $crop = (string) $lot->crop;
        $method = self::protoMethod($crop, $al?->method);
        $out = ['aim' => $aim, 'inputs' => $g, 'method' => $method, 'plant' => self::plantOptions($crop, $method), 'tPerHa' => null, 'steps' => [], 'feed' => [], 'standKnown' => false];
        if ($crop === '') {
            return $out;
        }
        $npkKey = NpkNeed::cropKey($crop, $method);
        $c = NpkNeed::crop($npkKey);
        $areaHa = $lot->lotSize ? (float) $lot->lotSize * match ($lot->lotSizeUnit) { 'sqm' => 0.0001, 'acre' => 0.404686, default => 1 } : 1.0;

        // 1. The best: the variety's on record, else the reading's, else the usual counted as 80% of a best.
        $P = NutrientBook::varietyPotential($npkKey ?? $crop, $lot->variety);
        $from = 'record';
        if (! $P && (float) ($readVariety['yieldPotentialTPerHa'] ?? 0) > 0) {
            [$P, $from] = [(float) $readVariety['yieldPotentialTPerHa'], 'reading'];
        }
        if (! $P && ! empty($c['typicalYield']['hi'])) {
            [$P, $from] = [(float) $c['typicalYield']['hi'] / 0.8, 'usual'];
        }
        if (! $P) {
            return $out;
        }
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        // The variety's short name: "DEKALB 8282S VT Double PRO RIB Complete (DK 8282S)" reads as DK 8282S.
        $vname = ! $lot->variety ? 'this crop' : (preg_match('/\(([^()]{2,30})\)\s*$/', (string) $lot->variety, $vm) ? $vm[1] : (string) $lot->variety);
        $out['potential'] = round($P, 2);
        $out['steps'][] = ['🏆', match ($from) {
            'record' => 'The best ' . $vname . ' has given on record',
            'reading' => 'The best Anee found for ' . $vname,
            default => 'A good field of this crop, from the Philippine usual',
        }, $fmt($P) . ' t/ha'];

        // 2. The aim, or the farmer's own target.
        $T = (float) ($g['target'] ?? 0);
        $Y = $T > 0 ? $T : $P * self::AIMS[$aim][3];
        $out['steps'][] = $T > 0 ? ['🎯', 'Your own target', $fmt($T) . ' t/ha']
            : [self::AIMS[$aim][2], self::AIMS[$aim][0] . ' aims at ' . round(self::AIMS[$aim][3] * 100) . '% of it', $fmt($Y) . ' t/ha'];

        // The need model on the lot's soil, water, season and planting (one door: the Crop Protocol's).
        $soil = (array) ($a['soilRead'] ?? []);
        $waterClass = (string) ((AutoBook::WATER[$a['water'] ?? ''] ?? [])[2] ?? 'irrigated');
        $sp = ($g['by'] ?? null) === 'spacing' && ! empty($g['row']) && ! empty($g['plant']) ? ['row' => $g['row'], 'plant' => $g['plant'], 'perHill' => $g['perHill'] ?? null] : null;
        $byAmount = ($g['by'] ?? null) === 'amount' && ! empty($g['amount']);
        $p = [
            'crop' => $crop, 'method' => $method, 'variety' => (string) $lot->variety, 'area' => $areaHa,
            'potential' => $P, 'targetYield' => $T > 0 ? $T : null, 'yieldUnit' => 'tons',
            'seedAmount' => $byAmount ? $g['amount'] : null, 'seedUnit' => $byAmount ? ($g['unit'] ?? 'kg') : null, 'germination' => $g['germ'] ?? null, 'spacing' => $sp,
            'soil' => in_array($soil['texture'] ?? '', ['clay', 'loam', 'sandy', 'silty'], true) ? $soil['texture'] : 'unsure',
            'soilConditions' => array_values(array_intersect((array) ($soil['conditions'] ?? []), ['sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged', 'acidic', 'alkaline'])),
            'ph' => is_numeric($soil['tested'] ?? null) ? (float) $soil['tested'] : null,
            'water' => ['partial' => 'limited', 'rainfed' => 'rainfed'][$waterClass] ?? 'irrigated',
            'fieldProfile' => ! empty($a['fieldProfile']) ? (array) $a['fieldProfile'] : null,
            'location' => trim(implode(', ', array_filter([$lot->locTown, $lot->locProvince]))),
            'day' => $al?->fieldDate?->toDateString(),
        ];
        $r = rescue(fn () => CropProtocolController::needFor($p), ['known' => false], false);

        // 3. The stand: what the planting carries, else NPK Plus's thin stand check.
        $st = $r['stand'] ?? null;
        $sd = $r['seed'] ?? null;
        if ($st && ! empty($st['hi'])) {
            $out['standKnown'] = true;
            $out['stand'] = $st;
            $hills = (PlantAmount::spacing($crop, $method)['standBy'] ?? 'plants') === 'hills';
            $said = 'Your stand of about ' . number_format(round((float) $st['plants'], -2)) . ($hills ? ' hills' : ' plants') . ' a hectare carries ' . $fmt($st['lo']) . ' to ' . $fmt($st['hi']) . ' t/ha';
            if ($Y > (float) $st['hi']) {
                $Y = (float) $st['hi'];
                $out['steps'][] = ['🌱', $said, $fmt($Y) . ' t/ha'];
            } else {
                $out['steps'][] = ['🌱', $said . ', enough for this', 'holds'];
            }
        } elseif ($sd && ! empty($sd['thin'])) {
            $out['standKnown'] = true;
            $Y *= (float) $sd['sf'];
            $out['steps'][] = ['🌱', 'A thin stand for this crop: ' . $sd['words'], '× ' . round((float) $sd['sf'] * 100) . '%'];
        } elseif ($sd) {
            $out['standKnown'] = true;
            $out['steps'][] = ['🌱', 'Your planting makes a full stand', 'holds'];
        } else {
            $out['steps'][] = ['🌱', 'A full usual stand is counted: tell Anee how much you plant for a surer goal', 'assumed'];
        }

        // 4. The field: salt and sodium caps, and the water (the lower of its cap and the season's share).
        if ($c) {
            $conds = array_values(array_intersect((array) ($soil['conditions'] ?? []), ['saline', 'sodic']));
            $cap = NpkNeed::fieldCap($c, true, $waterClass, $conds);
            $k = 1.0;
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
            $Y *= max(NpkModel::FIELD['capFloor'], $k);
        }

        // 5. What the goal needs fed: the elements likely short on this soil.
        foreach ((array) (($r['micro'] ?? [])['likely'] ?? []) as $m) {
            $out['feed'][] = $m['name'];
        }
        $out['tPerHa'] = round($Y, 1);
        $out['steps'][] = ['✅', 'Realistic goal', $fmt($out['tPerHa']) . ' t/ha'];
        if ($out['feed']) {
            $out['steps'][] = ['🧪', 'It holds only if ' . self::join(array_map('mb_strtolower', $out['feed'])) . ' ' . (count($out['feed']) > 1 ? 'are' : 'is') . ' fed: likely short on this soil', ''];
        }
        $out['areaHa'] = round($areaHa, 4);
        $out['lotTons'] = round($out['tPerHa'] * $areaHa, 2);

        return $out;
    }

    /** The steps as one line for a prompt. */
    public static function words(array $goal): string
    {
        return implode('; ', array_map(fn ($s) => $s[1] . ($s[2] !== '' ? ': ' . $s[2] : ''), (array) ($goal['steps'] ?? [])));
    }

    private static function join(array $xs): string
    {
        return count($xs) > 1 ? implode(', ', array_slice($xs, 0, -1)) . ' and ' . end($xs) : (string) ($xs[0] ?? '');
    }
}
