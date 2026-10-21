<?php

namespace App\Support;

/**
 * How much a lot plants, said one of two ways (2026-10-21, the owner: "either
 * spacing (you can calculate the # of seeds as well) or the amount. then the
 * germination is a separate field between the two"). The Crop Protocol and
 * NPK Plus ask it alike: the spacing (rows, hills, plants a hill) counted into
 * the seeds it takes by the seed calculator (SeedSpacing::compute, at the seed
 * tag's germination when one is given), or the amount itself. One engine, so
 * the seeds a spacing takes read the same in every module.
 */
class PlantAmount
{
    /** The two ways, as the pages say them. */
    public const BY = [
        'spacing' => ['By spacing', 'Rows and hills: the seeds are counted for you'],
        'amount' => ['By amount', 'The kilos, seeds or plants you have'],
    ];

    /**
     * The seed calculator's crop and way in for a crop of the catalogue or NPK Plus
     * and its planting method (the protocol's methods: transplanted, direct_wet,
     * nursery, cuttings ...); null when the calculator has no entry for it.
     *
     * @return array{0: string, 1: string}|null [SeedSpacing key, direct|transplant|piece]
     */
    public static function key(?string $crop, ?string $method = null): ?array
    {
        $crop = (string) $crop;
        $method = (string) $method;
        $key = match (true) {
            in_array($crop, ['rice_dsr_wet', 'rice_dsr_dry'], true) => 'rice_dsr',
            str_starts_with($crop, 'rice') => $method === '' || $method === 'transplanted' ? 'rice' : 'rice_dsr',
            $crop === 'corn_glutinous' => 'corn_white',
            default => $crop,
        };
        $C = SeedSpacing::CROPS[$key] ?? null;
        if (! $C) {
            return null;
        }
        $way = match (true) {
            $method === '' => $C['methods'][0],
            in_array($method, ['transplanted', 'nursery', 'tree_seedlings'], true) => 'transplant',
            in_array($method, ['cuttings', 'tubers', 'sets', 'suckers', 'sprouted'], true) => 'piece',
            default => 'direct',
        };

        return [$key, in_array($way, $C['methods'], true) ? $way : $C['methods'][0]];
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
        [$key, $way] = $k;
        $c = SeedSpacing::CROPS[$key];
        if (empty($c['row']) || empty($c['plant'])) {
            return null;
        }

        return ['row' => $c['row'], 'plant' => $c['plant'], 'perHill' => SeedSpacing::perHillFor($c, $way), 'standBy' => $c['standBy'] ?? 'plants', 'stand' => $c['stand'] ?? null,
            // Trees and other wide crops are said in meters; rice sown in place only when it goes in rows.
            'meters' => (float) $c['row'][1] >= 200, 'note' => $key === 'rice_dsr' ? 'Only when it is sown in rows, with a seeder or a drill.' : '',
            'way' => $way, 'word' => self::word($c, $way), 'fromSeed' => self::fromSeed($c, $way)];
    }

    /** What is put in the ground: seeds, seedlings, or the crop's pieces (cuttings, setts, plantlets). */
    private static function word(array $C, string $way): string
    {
        return match (true) {
            $way === 'piece' => (string) ($C['piece'][1] ?? 'pieces'),
            $way === 'transplant' && empty($C['tgw']) => 'seedlings',
            default => 'seeds',
        };
    }

    /** Whether a seed tag's germination counts: seed sown in place or in the farmer's own seedbed. */
    private static function fromSeed(array $C, string $way): bool
    {
        return $way === 'direct' || ($way === 'transplant' && ! empty($C['tgw']));
    }

    /**
     * The seeds a spacing takes: rows and plants in cm, plants a hill (empty: the calculator's
     * suggestion for the germination), the seed tag's germination, the lot's hectares and the
     * variety. Null when the spacing is not given or the crop has no entry.
     */
    public static function count(?string $crop, ?string $method, array $sp, ?float $germ = null, float $areaHa = 0, ?string $variety = null): ?array
    {
        $k = self::key($crop, $method);
        $row = (float) ($sp['row'] ?? 0);
        $plant = (float) ($sp['plant'] ?? 0);
        if (! $k || $row < 1 || $plant < 1) {
            return null;
        }
        [$key, $way] = $k;
        $C = SeedSpacing::CROPS[$key];
        $hill = (int) ($sp['perHill'] ?? 0);
        $in = ['crop' => $key, 'method' => $way, 'variety' => (string) $variety, 'layout' => 'rows', 'mode' => 'seeds',
            'area' => $areaHa > 0 ? $areaHa : null, 'areaUnit' => 'ha', 'row' => $row, 'plant' => $plant,
            'hillMode' => $hill > 0 ? 'set' : 'auto'] + ($hill > 0 ? ['perHill' => $hill] : []);
        if ($germ !== null && $germ > 0 && self::fromSeed($C, $way)) {
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
            'crop' => $key, 'way' => $way, 'word' => self::word($C, $way), 'fromSeed' => self::fromSeed($C, $way),
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
