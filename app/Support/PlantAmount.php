<?php

namespace App\Support;

use App\Http\Controllers\CropProtocolController;

/**
 * How much a lot plants, said one of two ways (2026-10-21, the owner: "either
 * spacing (you can calculate the # of seeds as well) or the amount. then the
 * germination is a separate field between the two"). The Crop Protocol and
 * NPK Plus ask it alike: the spacing (rows, hills, plants a hill) counted into
 * the seeds it takes by the seed calculator (SeedSpacing::compute, at the seed
 * tag's germination when one is given), or the amount itself. One engine, so
 * the seeds a spacing takes read the same in every module.
 *
 * The germination is asked only where seed goes in (the owner: "either way you
 * are planting seeds so both applies it, not unless you are selecting it as
 * starting as transplant which is applicable for vegetables in which they buy
 * it as seedlings already"): GERM below, by the way the crop goes in.
 */
class PlantAmount
{
    /** The two ways, as the pages say them. */
    public const BY = [
        'spacing' => ['By spacing', 'Rows and hills: the seeds are counted for you'],
        'amount' => ['By amount', 'The kilos, seeds or plants you have'],
    ];

    /**
     * What goes in the ground by each of the protocol's ways (CropProtocolController::METHODS),
     * and so whether the seed tag's germination counts:
     *  seed       seed sown in place, or in the farmer's own rice seedbed: always, by spacing or by amount;
     *  seedlings  vegetable seedlings, bought ready to set out: not by spacing; by amount only when the
     *             amount is given as seed (kilos, grams or seeds), which is seed for a tray of one's own;
     *  none       cuttings, sets, tubers, suckers, a sprouted fruit, nursery trees: never.
     */
    public const GERM = [
        'direct' => 'seed', 'direct_wet' => 'seed', 'direct_dry' => 'seed', 'transplanted' => 'seed',
        'nursery' => 'seedlings',
        'tree_seedlings' => 'none', 'sets' => 'none', 'cuttings' => 'none', 'tubers' => 'none', 'suckers' => 'none', 'sprouted' => 'none',
    ];

    /** The amount units that are seed (by weight or by count). */
    public const SEED_UNITS = ['kg', 'g', 'seeds', 'hills'];

    /**
     * A crop's way in: the one picked, else its usual one (the protocol's first, so NPK Plus,
     * which asks no way, reads rice as transplanted, tomato as seedlings and pechay as sown).
     */
    public static function method(?string $crop, ?string $method = null): string
    {
        if ((string) $method !== '' && isset(self::GERM[$method])) {
            return (string) $method;
        }
        $crop = (string) $crop;

        return (string) (CropProtocolController::methodsFor($crop)[0] ?? 'direct');
    }

    /** Whether the germination counts for a crop and its way: seed, seedlings or none (GERM). */
    public static function germ(?string $crop, ?string $method = null): string
    {
        return self::GERM[self::method($crop, $method)] ?? 'none';
    }

    /** Whether a typed germination is read: by spacing for seed, by amount for seed given as seed. */
    public static function germApplies(?string $crop, ?string $method, ?string $by, ?string $unit): bool
    {
        $g = self::germ($crop, $method);
        $seedUnit = in_array((string) $unit, self::SEED_UNITS, true);

        return match ($g) {
            'seed' => $by === 'spacing' || $seedUnit,
            'seedlings' => $by !== 'spacing' && $seedUnit && $unit !== 'hills',
            default => false,
        };
    }

    /**
     * The seed calculator's crop and way in for a crop of the catalogue or NPK Plus
     * and its planting method; null when the calculator has no entry for it.
     *
     * @return array{0: string, 1: string, 2: bool}|null [SeedSpacing key, direct|transplant|piece, bought seedlings]
     */
    public static function key(?string $crop, ?string $method = null): ?array
    {
        $crop = (string) $crop;
        $method = self::method($crop, $method);
        $key = match (true) {
            in_array($crop, ['rice_dsr_wet', 'rice_dsr_dry'], true) => 'rice_dsr',
            str_starts_with($crop, 'rice') => $method === 'transplanted' ? 'rice' : 'rice_dsr',
            $crop === 'corn_glutinous' => 'corn_white',
            default => $crop,
        };
        $C = SeedSpacing::CROPS[$key] ?? null;
        if (! $C) {
            return null;
        }
        $way = match (true) {
            in_array($method, ['transplanted', 'nursery', 'tree_seedlings'], true) => 'transplant',
            in_array($method, ['cuttings', 'tubers', 'sets', 'suckers', 'sprouted'], true) => 'piece',
            default => 'direct',
        };
        // A nursery tree of a crop the calculator plants from pieces (banana, pineapple): its plantlets.
        if ($method === 'tree_seedlings' && ! in_array('transplant', $C['methods'], true) && in_array('piece', $C['methods'], true)) {
            $way = 'piece';
        }
        // A way the calculator has no entry for (tomato sown in place, onion from sets) is not counted
        // as another way: that crop is said by amount only.
        if (! in_array($way, $C['methods'], true)) {
            return null;
        }

        // Set out as seedlings, the farmer's own seedbed only for rice: the rest are bought (the owner).
        return [$key, $way, $way === 'transplant' && $method !== 'transplanted'];
    }

    /**
     * The units a crop's amount is given in by its way in (2026-10-21, the owner: only what the crop is planted by),
     * its usual one first: seed by weight or count for seed, seedlings (and seed for a tray of one's own) for
     * seedlings, the crop's pieces for cuttings, sets and tubers, plants for suckers and trees.
     *
     * @return list<string> keys of NpkNeed::SEED_UNITS
     */
    public static function units(?string $crop, ?string $method = null): array
    {
        $method = self::method($crop, $method);
        $known = NpkNeed::seedUnitsFor(NpkNeed::cropKey((string) $crop, $method) ?? (string) $crop);
        [$allowed, $fallback] = match ($method) {
            'direct', 'direct_wet', 'direct_dry', 'transplanted' => [['kg', 'g', 'seeds', 'hills'], ['kg', 'g', 'seeds']],
            'nursery' => [['kg', 'g', 'seeds'], []],
            'cuttings' => [['cuttings', 'plants'], ['cuttings']],
            'tubers', 'sets' => [['kg', 'plants'], ['kg']],
            default => [['plants'], ['plants']],
        };
        $out = array_values(array_intersect($known, $allowed)) ?: $fallback;

        // Vegetable seedlings are bought (the owner): seedlings first, seed for a tray of one's own after.
        return $method === 'nursery' ? array_values(array_unique(array_merge(['seedlings'], $out))) : $out;
    }

    /**
     * A crop's usual spacing for the form, in cm: rows, plants, plants a hill, the stand,
     * whether it is said in meters (trees), what goes in (seeds, seedlings, cuttings ...)
     * and whether a seed tag's germination counts. Null where the calculator has no entry.
     */
    public static function spacing(?string $crop, ?string $method = null): ?array
    {
        $k = self::key($crop, $method);
        if (! $k) {
            return null;
        }
        [$key, $way, $bought] = $k;
        $c = SeedSpacing::CROPS[$key];
        if (empty($c['row']) || empty($c['plant'])) {
            return null;
        }

        return ['row' => $c['row'], 'plant' => $c['plant'], 'perHill' => SeedSpacing::perHillFor($c, $way), 'standBy' => $c['standBy'] ?? 'plants', 'stand' => $c['stand'] ?? null,
            // Trees and other wide crops are said in meters; rice sown in place only when it goes in rows.
            'meters' => (float) $c['row'][1] >= 200, 'note' => $key === 'rice_dsr' ? 'Only when it is sown in rows, with a seeder or a drill.' : '',
            'way' => $way, 'word' => self::word($c, $way, $bought), 'fromSeed' => self::germ($crop, $method) === 'seed'];
    }

    /** What is put in the ground: seeds, seedlings, or the crop's pieces (cuttings, setts, plantlets). */
    private static function word(array $C, string $way, bool $bought): string
    {
        return match (true) {
            $way === 'piece' => (string) ($C['piece'][1] ?? 'pieces'),
            $way === 'transplant' && ($bought || empty($C['tgw'])) => 'seedlings',
            default => 'seeds',
        };
    }

    /**
     * The seeds a spacing takes: rows and plants in cm, plants a hill (empty: the calculator's
     * suggestion for the germination), the seed tag's germination, the lot's hectares and the
     * variety. Seedlings that are bought are counted as seedlings. Null when the spacing is
     * not given or the crop has no entry.
     */
    public static function count(?string $crop, ?string $method, array $sp, ?float $germ = null, float $areaHa = 0, ?string $variety = null): ?array
    {
        $k = self::key($crop, $method);
        $row = (float) ($sp['row'] ?? 0);
        $plant = (float) ($sp['plant'] ?? 0);
        if (! $k || $row < 1 || $plant < 1) {
            return null;
        }
        [$key, $way, $bought] = $k;
        $C = SeedSpacing::CROPS[$key];
        $fromSeed = self::germ($crop, $method) === 'seed';
        $hill = (int) ($sp['perHill'] ?? 0);
        $in = ['crop' => $key, 'method' => $way, 'variety' => (string) $variety, 'layout' => 'rows', 'mode' => 'seeds', 'bought' => $bought,
            'area' => $areaHa > 0 ? $areaHa : null, 'areaUnit' => 'ha', 'row' => $row, 'plant' => $plant,
            'hillMode' => $hill > 0 ? 'set' : 'auto'] + ($hill > 0 ? ['perHill' => $hill] : []);
        if ($germ !== null && $germ > 0 && $fromSeed) {
            $in['germTag'] = $germ;
        }
        try {
            $c = SeedSpacing::read($in);
            $r = SeedSpacing::compute($c);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        $area = $areaHa > 0 ? $areaHa : null;
        $kg = $r['kgLo'] !== null ? [(float) $r['kgLo'], (float) $r['kgHi']] : null;
        $out = [
            'crop' => $key, 'way' => $way, 'word' => self::word($C, $way, $bought), 'fromSeed' => $fromSeed,
            'standBy' => $C['standBy'] ?? 'plants', 'perHill' => (int) $r['perHill'], 'suggested' => $hill <= 0,
            'hillsHa' => (float) $r['hillsHa'], 'plantsHa' => (float) $r['plantsHa'], 'standHa' => (float) $r['standHa'],
            'usualStand' => $C['stand'] ?? null, 'status' => $r['status'],
            'buy' => [(float) $r['buyLo'], (float) $r['buyHi']], 'kg' => $kg,
            'buyLot' => $area ? [(float) $r['buyLo'] * $area, (float) $r['buyHi'] * $area] : null,
            'kgLot' => $area && $kg ? [$kg[0] * $area, $kg[1] * $area] : null,
            'germ' => round((float) $r['seed']['germ'], 1), 'germFrom' => (string) ($r['seed']['from']['germ'] ?? 'crop'),
        ];

        return $out + ['say' => self::say($out)];
    }

    /** The count in words, the same on every page. */
    public static function say(array $n): string
    {
        // Big counts read rounded: 892,857 seeds is about 893,000.
        $int = fn ($v) => number_format(round((float) $v, $v >= 100000 ? -3 : ($v >= 10000 ? -2 : 0)));
        $span = fn (array $a, callable $f) => $f($a[0]) === $f($a[1]) ? $f($a[0]) : $f($a[0]) . ' to ' . $f($a[1]);
        // Seed under a kilo reads in grams.
        $kgs = fn (array $a) => $a[1] < 1 ? $span([$a[0] * 1000, $a[1] * 1000], $int) . ' g' : $span($a, fn ($v) => $v >= 10 ? $int($v) : rtrim(rtrim(number_format((float) $v, 1, '.', ','), '0'), '.')) . ' kg';
        $hills = $n['standBy'] === 'hills';
        $usual = $n['usualStand'] ? ' (usual ' . $int($n['usualStand'][0]) . ' to ' . $int($n['usualStand'][1]) . ')' : '';
        if ($n['way'] !== 'direct' && $n['perHill'] === 1) {
            // One seedling, cutting or tree a hole: the holes, then the plants that live.
            $s = 'About ' . $int($n['hillsHa']) . ' planting holes a hectare. About ' . $int($n['plantsHa']) . ' plants stand after the usual losses' . $usual . '.';
        } else {
            $what = $n['way'] === 'transplant' ? ($n['crop'] === 'rice' ? 'seedling' : 'plant') : ($n['word'] === 'seeds' ? 'seed' : rtrim($n['word'], 's'));
            $s = 'About ' . $int($n['hillsHa']) . ' hills a hectare, ' . $n['perHill'] . ' ' . $what . ($n['perHill'] === 1 ? '' : 's') . ' each'
                . ($n['suggested'] && $n['way'] === 'direct' ? ' (enough for the germination)' : '') . '.';
            $s .= ' About ' . $int($hills ? $n['standHa'] : $n['plantsHa']) . ($hills ? ' hills stand filled' : ' plants stand') . ' a hectare' . $usual . '.';
        }
        $s .= ' You need about ' . ($n['kg'] ? $kgs($n['kg']) . ' of ' . ($n['word'] === 'seeds' ? 'seed' : $n['word']) . ' a hectare, ' . $span($n['buy'], $int) . ' ' . $n['word']
            : $span($n['buy'], $int) . ' ' . $n['word'] . ' a hectare');
        $s .= $n['fromSeed'] ? ', at ' . rtrim(rtrim(number_format($n['germ'], 1), '0'), '.') . '% germination' . ($n['germFrom'] === 'tag' ? '' : ' (the usual)') : '';
        $s .= '.';
        if ($n['kgLot'] || $n['buyLot']) {
            $s .= ' For this lot: ' . ($n['kgLot'] ? $kgs($n['kgLot']) : $span($n['buyLot'], $int) . ' ' . $n['word']) . '.';
        }

        return $s . match ($n['status']) {
            'sparse' => ' The stand is very thin for this crop.',
            'thin' => ' The stand is a little thin.',
            'crowded' => ' The stand is crowded.',
            'danger' => ' The stand is far too crowded.',
            default => '',
        };
    }

    /**
     * The count as NPK Plus's seed answer (NpkNeed::seed) for the lot: the stand where the
     * guides give the crop as a count (hills where they count hills), else the kilos of seed
     * where they give a weight; null when neither can be compared.
     *
     * @return array{amount: float, unit: string, germination: float|null}|null
     */
    public static function asSeed(?array $n, ?string $npkKey, float $areaHa): ?array
    {
        $sd = $npkKey !== null ? (NpkModel::SEEDING[$npkKey] ?? null) : null;
        if (! $n || ! $sd || $areaHa <= 0) {
            return null;
        }
        if (! empty($sd['n'])) {
            $hills = ($sd['countWord'] ?? '') === 'hills';

            return ['amount' => round(($hills ? $n['hillsHa'] : $n['hillsHa'] * $n['perHill']) * $areaHa, 1), 'unit' => $hills ? 'hills' : 'plants', 'germination' => null];
        }
        if (! empty($sd['w']) && $n['kg']) {
            // The kilos already carry the germination they were counted at; NpkNeed reads the tag's against the usual.
            return ['amount' => round(($n['kg'][0] + $n['kg'][1]) / 2 * $areaHa, 3), 'unit' => 'kg', 'germination' => $n['germFrom'] === 'tag' ? $n['germ'] : null];
        }

        return null;
    }
}
