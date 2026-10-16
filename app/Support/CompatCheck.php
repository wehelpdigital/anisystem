<?php

namespace App\Support;

/**
 * The Compatibility Checker's engine (2026-10-18, the owner: "save labor by
 * knowing if the fertilizers, sprays ... are safe to apply still for the
 * crops. this includes the salt content, chemical compatibility, and so on").
 *
 * In: the field (crop, stage, the water, the weather, the sprayer) and what
 * goes on together, each item a product (App\Support\ProductLibrary, a
 * farmer's own, or only a name) with how it goes on (sprayed or spread) and
 * its rate. Out: a verdict, a pair by pair reading, the whole tank checks
 * (water, pH, heat, strength, the crop's label), the mixing order, the jar
 * test with each product's amount in one liter, and how to split the work
 * into trips when something must not share a tank.
 *
 * Three levels, conservative: "Mixes fine" only when every ingredient is
 * known and no rule fires; "Jar test first" when a rule says care; "Do not
 * mix" when one says no. A product or pair the book cannot read is said
 * plainly ("We do not know it well enough"), and the page offers Anee's
 * online check for it. The label always wins, and every result says so.
 */
final class CompatCheck
{
    public const STAGES = ['preplant' => 'before planting', 'seedling' => 'as a seedling', 'vegetative' => 'while growing', 'flowering' => 'at flowering', 'fruiting' => 'filling grain or fruit'];

    public const RANK = ['ok' => 0, 'unknown' => 1, 'care' => 2, 'no' => 3];

    /** Amounts in a tank, in mL or g: one tablespoon is 15 mL, a teaspoon 5, a bag 50 kg. */
    public const UNITS = ['mL' => ['mL', 1], 'L' => ['mL', 1000], 'tbsp' => ['mL', 15], 'tsp' => ['mL', 5], 'g' => ['g', 1], 'kg' => ['g', 1000], 'bag' => ['g', 50000]];

    /** What one dose is for: a tank of its own size, a hectare, or a plant. */
    public const PERS = ['tank' => null, '16L' => 16, '20L' => 20, '200L' => 200, '1L' => 1, 'ha' => 'ha', 'plant' => 'plant'];

    private array $rules;

    private array $ctx;

    private array $items = [];

    private array $tank = [];

    private array $tips = [];

    public function __construct(array $ctx)
    {
        $this->rules = CompatBook::rules();
        $this->ctx = $ctx + ['crop' => null, 'stage' => '', 'water' => '', 'weather' => '', 'tankL' => 16.0, 'waterHa' => 200.0];
        $this->ctx['tankL'] = max(1.0, min(1000.0, (float) $this->ctx['tankL']));
        $this->ctx['waterHa'] = max(20.0, min(2000.0, (float) $this->ctx['waterHa']));
    }

    /**
     * @param  list<array{product: array, applied: string, amount: ?float, unit: ?string, per: ?string, name: string, ref: string}>  $items
     */
    public function run(array $items): array
    {
        $crops = FieldCatalogue::crops();
        $crop = $this->ctx['crop'];
        $cropName = $crop && isset($crops[$crop]) ? $crops[$crop][1] : null;
        $cropShelf = $crop && isset($crops[$crop]) ? $crops[$crop][3] : null;
        foreach (array_values($items) as $i => $it) {
            $p = (array) $it['product'];
            $applied = in_array($it['applied'] ?? '', ['spray', 'soil'], true) ? $it['applied'] : (ProductLibrary::applied($p) === 'soil' ? 'soil' : 'spray');
            if (in_array($p['applied'] ?? '', ['bait', 'seed'], true)) {
                $applied = $p['applied'];
            }
            $tr = CompatBook::traits($p, $applied);
            $dose = $this->dose($it, $applied);
            $labelled = null;
            $use = null;
            if ($crop && $tr['isPest'] && ! empty($p['crops'])) {
                $labelled = in_array($crop, $p['crops'], true) || ($cropShelf === 'vegetables' && in_array('vegetables', $p['crops'], true))
                    || ($cropShelf === 'fruits' && in_array('fruits', $p['crops'], true)) || (str_starts_with((string) $crop, 'corn') && in_array('corn_yellow', $p['crops'], true) && $crop !== 'corn_sweet');
            }
            foreach ((array) ($p['uses'] ?? []) as $u) {
                if ($crop && ($u[0] ?? '') === $crop) {
                    $use = $u;
                    break;
                }
            }
            $this->items[] = [
                'i' => $i, 'name' => (string) ($it['name'] ?: ($p['b'] ?? 'Product')), 'company' => (string) ($p['c'] ?? ''), 'ref' => $it['ref'] ?? 'x',
                'type' => (string) ($p['t'] ?? 'other'), 'typeWord' => ProductLibrary::TYPES[$p['t'] ?? ''] ?? 'Product', 'f' => (string) ($p['f'] ?? ''),
                'applied' => $applied, 'p' => $p, 'tr' => $tr, 'flags' => array_flip($tr['flags']), 'dose' => $dose, 'labelled' => $labelled,
                'phi' => $use[4] ?? '', 'labelRate' => $use[2] ?? '', 'mix' => array_values(array_filter((array) ($p['mix'] ?? []))), 'restrict' => $this->restrictions($p, $crop),
            ];
        }
        $pairs = $this->pairs();
        $sprays = array_values(array_filter($this->items, fn ($x) => $x['applied'] === 'spray'));
        $soils = array_values(array_filter($this->items, fn ($x) => $x['applied'] === 'soil'));
        $strength = $this->strength($sprays, $cropName);
        $this->tankChecks($sprays, $soils, $strength, $cropName);
        $order = $this->order($sprays);
        $trips = $this->trips($sprays, $pairs, 'tank');
        $spreads = $this->trips($soils, $pairs, 'blend');
        $unknownItems = array_values(array_map(fn ($x) => $x['i'], array_filter($this->items, fn ($x) => ! $x['tr']['fact'])));
        $unknownPairs = array_values(array_map(fn ($p) => [$p['a'], $p['b']], array_filter($pairs, fn ($p) => $p['level'] === 'unknown')));

        $levels = array_merge(array_column($pairs, 'level'), array_column($this->tank, 'level'));
        $worst = 'ok';
        foreach ($levels as $l) {
            if (self::RANK[$l] > self::RANK[$worst]) {
                $worst = $l;
            }
        }
        if ($unknownItems && $worst === 'ok') {
            $worst = 'unknown';
        }

        return [
            'verdict' => $worst,
            'headline' => $this->headline($worst, $pairs, $trips, $unknownItems),
            'crop' => $crop, 'cropName' => $cropName, 'stage' => $this->ctx['stage'], 'stageWord' => self::STAGES[$this->ctx['stage']] ?? '',
            'tankL' => $this->ctx['tankL'], 'waterHa' => $this->ctx['waterHa'],
            'items' => array_map(fn ($x) => $this->itemOut($x), $this->items),
            'pairs' => $pairs,
            'tank' => array_values($this->tank),
            'tips' => array_values(array_unique($this->tips)),
            'strength' => $strength,
            'order' => $order,
            'jar' => $this->jar($sprays),
            'trips' => $trips,
            'spreads' => $spreads,
            'unknown' => ['items' => $unknownItems, 'pairs' => $unknownPairs],
            'gaps' => $this->gaps($pairs),
        ];
    }

    /* ================================================================ items */

    /** One item's amount: per tank (mL or g) and per liter for a spray, kg or L per hectare for the soil. */
    private function dose(array $it, string $applied): array
    {
        $amt = isset($it['amount']) && is_numeric($it['amount']) ? (float) $it['amount'] : null;
        $unit = self::UNITS[$it['unit'] ?? ''] ?? null;
        $per = array_key_exists($it['per'] ?? '', self::PERS) ? $it['per'] : null;
        if ($amt === null || $amt <= 0 || ! $unit || ! $per) {
            return ['set' => false];
        }
        [$base, $mult] = $unit;
        $qty = $amt * $mult;
        $out = ['set' => true, 'base' => $base, 'amount' => $amt, 'unit' => $it['unit'], 'per' => $per];
        if ($applied === 'spray') {
            $vol = self::PERS[$per] ?? null;
            if ($per === 'tank') {
                $vol = $this->ctx['tankL'];
            }
            if ($per === 'ha') {
                $perL = $qty / $this->ctx['waterHa'];
            } elseif (is_numeric($vol)) {
                $perL = $qty / (float) $vol;
            } else {
                return $out + ['perL' => null];
            }
            $out['perL'] = $perL;
            $out['perTank'] = $perL * $this->ctx['tankL'];
            $out['perHa'] = $perL * $this->ctx['waterHa'];
        } else {
            if ($per === 'ha') {
                $out['kgHa'] = $base === 'g' ? $qty / 1000 : $qty / 1000;
            } elseif ($per === 'plant') {
                $out['perPlant'] = $qty;
            }
        }

        return $out;
    }

    /** What the FPA bans or restricts for this product (by ingredient, and by crop for some). */
    private function restrictions(array $p, ?string $crop): array
    {
        static $list = null;
        if ($list === null) {
            $d = json_decode((string) @file_get_contents(database_path('products/fpa-restricted.json')), true);
            $list = (array) ($d['items'] ?? []);
        }
        $out = [];
        $names = array_map(fn ($a) => mb_strtolower((string) ($a['n'] ?? '')), (array) ($p['ai'] ?? []));
        foreach ($list as $r) {
            $hit = false;
            foreach ((array) $r['match'] as $m) {
                foreach ($names as $n) {
                    if ($n !== '' && str_contains($n, mb_strtolower($m))) {
                        $hit = true;
                    }
                }
            }
            if (! $hit) {
                continue;
            }
            if (! empty($r['crops']) && (! $crop || ! in_array($crop, $r['crops'], true))) {
                continue;
            }
            $out[] = ['level' => $r['level'], 'say' => $r['say']];
        }

        return $out;
    }

    private function itemOut(array $x): array
    {
        $d = $x['dose'];
        $chips = [];
        foreach ($x['tr']['known'] as $k) {
            $chips[] = ['n' => $k['name'], 'cls' => $k['cls'], 'grp' => $k['grp']];
        }

        return [
            'i' => $x['i'], 'name' => $x['name'], 'company' => $x['company'], 'ref' => $x['ref'], 'type' => $x['type'], 'typeWord' => $x['typeWord'], 'f' => $x['f'],
            'applied' => $x['applied'], 'known' => $x['tr']['fact'], 'unknownAi' => $x['tr']['unknown'], 'ai' => $chips,
            'labelled' => $x['labelled'], 'phi' => $x['phi'], 'labelRate' => $x['labelRate'], 'mix' => array_slice($x['mix'], 0, 6), 'restrict' => $x['restrict'],
            'dose' => $d['set'] ? array_filter([
                'perTank' => isset($d['perTank']) ? $this->amount($d['perTank'], $d['base']) : null,
                'perL' => isset($d['perL']) ? $this->amount($d['perL'], $d['base']) : null,
                'kgHa' => isset($d['kgHa']) ? round($d['kgHa'], 1) : null,
            ]) : null,
        ];
    }

    /** An amount said plainly: "12 mL", "1.5 g", "1.2 L", "about 2 tbsp". */
    public function amount(float $v, string $base): string
    {
        if ($base === 'mL') {
            if ($v >= 1000) {
                return ProductLibrary::num(round($v / 1000, 2)) . ' L';
            }

            return ProductLibrary::num($v >= 10 ? round($v) : round($v, $v >= 1 ? 1 : 2)) . ' mL';
        }
        if ($v >= 1000) {
            return ProductLibrary::num(round($v / 1000, 2)) . ' kg';
        }

        return ProductLibrary::num($v >= 10 ? round($v) : round($v, $v >= 1 ? 1 : 2)) . ' g';
    }

    /* ================================================================ pairs */

    private function has(array $x, array $flags): bool
    {
        foreach ($flags as $f) {
            if (isset($x['flags'][$f])) {
                return true;
            }
        }

        return false;
    }

    /** Every pair of items, read against the rules for how the two go on. */
    private function pairs(): array
    {
        $out = [];
        $n = count($this->items);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $x = $this->items[$i];
                $y = $this->items[$j];
                if (in_array($x['applied'], ['bait', 'seed'], true) || in_array($y['applied'], ['bait', 'seed'], true)) {
                    continue;
                }
                $mode = $x['applied'] === 'spray' && $y['applied'] === 'spray' ? 'tank' : ($x['applied'] === 'soil' && $y['applied'] === 'soil' ? 'blend' : 'near');
                $hits = [];
                foreach ((array) ($this->rules['pairs'] ?? []) as $r) {
                    $on = (array) $r['on'];
                    if (! in_array($mode, $on, true) && ! (in_array('near', $on, true) && ! empty($r['gap']))) {
                        continue;
                    }
                    $fwd = $this->side($x, $r['a'], $r['anot'] ?? []) && $this->side($y, $r['b'], $r['bnot'] ?? []);
                    $rev = $this->side($y, $r['a'], $r['anot'] ?? []) && $this->side($x, $r['b'], $r['bnot'] ?? []);
                    if (! $fwd && ! $rev) {
                        continue;
                    }
                    [$a, $b] = $fwd ? [$x, $y] : [$y, $x];
                    $level = $r['level'];
                    // A rule written for the tank, met by a pair on the field days apart: only its gap matters.
                    if ($mode !== 'tank' && ! in_array($mode, $on, true)) {
                        $level = $level === 'no' ? 'care' : $level;
                    }
                    $hits[] = ['id' => $r['id'], 'level' => $level, 'why' => $this->fill($r['why'], $a, $b), 'fix' => $this->fill($r['fix'], $a, $b), 'gap' => $r['gap'] ?? null];
                }
                $known = $x['tr']['fact'] && $y['tr']['fact'];
                $level = 'ok';
                foreach ($hits as $h) {
                    if (self::RANK[$h['level']] > self::RANK[$level]) {
                        $level = $h['level'];
                    }
                }
                if (! $known && $level === 'ok') {
                    $level = 'unknown';
                }
                $good = $hits && ! array_filter($hits, fn ($h) => $h['level'] !== 'ok');
                $out[] = ['a' => $i, 'b' => $j, 'mode' => $mode, 'level' => $level, 'known' => $known, 'good' => $good,
                    'reasons' => array_values(array_filter($hits, fn ($h) => $h['level'] !== 'ok' || $good)),
                    'gap' => max(array_merge([0], array_map(fn ($h) => (int) ($h['gap'] ?? 0), array_filter($hits, fn ($h) => $h['level'] !== 'ok')))) ?: null];
            }
        }
        usort($out, fn ($p, $q) => self::RANK[$q['level']] <=> self::RANK[$p['level']] ?: $p['a'] <=> $q['a'] ?: $p['b'] <=> $q['b']);

        return $out;
    }

    private function side(array $x, array $flags, array $not): bool
    {
        return $this->has($x, $flags) && ! ($not && $this->has($x, $not));
    }

    private function fill(string $s, array $a, ?array $b = null, array $more = []): string
    {
        $map = ['{a}' => $a['name'], '{b}' => $b['name'] ?? '', '{crop}' => mb_strtolower((string) ($this->cropName() ?? 'your crop'))];
        foreach ($more as $k => $v) {
            $map['{' . $k . '}'] = (string) $v;
        }

        return strtr($s, $map);
    }

    private function cropName(): ?string
    {
        $c = FieldCatalogue::crops();

        return $this->ctx['crop'] && isset($c[$this->ctx['crop']]) ? $c[$this->ctx['crop']][1] : null;
    }

    /* ================================================================ the whole tank */

    private function note(string $id, string $level, array $items, array $more = [], ?array $a = null, ?array $b = null): void
    {
        $w = (array) ($this->rules['tank'][$id] ?? []);
        if (! $w) {
            return;
        }
        $names = implode(', ', array_map(fn ($x) => $x['name'], $items));
        $a = $a ?? ['name' => $names];
        $this->tank[] = ['id' => $id, 'level' => $level ?: $w['level'], 'items' => array_map(fn ($x) => $x['i'], $items),
            'why' => $this->fill((string) $w['why'], $a, $b, $more), 'fix' => $this->fill((string) $w['fix'], $a, $b, $more)];
    }

    private function with(array $list, array $flags): array
    {
        return array_values(array_filter($list, fn ($x) => $this->has($x, $flags)));
    }

    private function tankChecks(array $sprays, array $soils, ?array $strength, ?string $cropName): void
    {
        $water = $this->ctx['water'];
        $weather = $this->ctx['weather'];
        $stage = $this->ctx['stage'];
        $crop = $this->ctx['crop'];
        foreach ($this->items as $x) {
            if ($x['applied'] === 'bait') {
                $this->note('bait_in_tank', 'no', [$x]);
            }
            if ($x['applied'] === 'seed') {
                $this->note('seed_in_tank', 'care', [$x]);
            }
            foreach ($x['restrict'] as $r) {
                $this->tank[] = ['id' => 'fpa_' . $r['level'], 'level' => $r['level'] === 'banned' ? 'no' : 'care', 'items' => [$x['i']],
                    'why' => $x['name'] . ': ' . $r['say'], 'fix' => $r['level'] === 'banned' ? 'Choose another product.' : 'Use it only as the FPA allows.'];
            }
            // The label and the crop.
            if ($x['labelled'] === false && $crop) {
                $nonsel = isset($x['flags']['herb_nonselective']);
                if (isset($x['flags']['herb']) && ! ($nonsel && $stage === 'preplant')) {
                    $this->note($nonsel ? 'not_labelled' : 'herb_crop_not_labelled', $nonsel ? 'care' : 'no', [$x], [], $x);
                } elseif (! isset($x['flags']['herb'])) {
                    $this->note('not_labelled', 'care', [$x], [], $x);
                }
            }
        }
        if ($sprays) {
            $hardies = $this->with($sprays, ['hard']);
            if ($hardies && $water === 'hard') {
                $ams = $this->with($sprays, ['fe_ams', 'conditioner']);
                if (! $ams) {
                    $this->note('hard_glyphosate', 'care', $hardies);
                } else {
                    $this->tips[] = 'Good: ' . $ams[0]['name'] . ' in the mix softens the hard water for ' . $hardies[0]['name'] . '. Put it in the water first.';
                }
            } elseif ($hardies && $water === '') {
                $this->tips[] = 'If your water is hard (a deep well, a white crust on pails), dissolve spray grade ammonium sulfate in it before you add ' . $hardies[0]['name'] . '.';
            }
            $muddy = $this->with($sprays, ['mud']);
            if ($muddy && $water === 'muddy') {
                $this->note('muddy_binders', 'no', $muddy);
            } elseif ($muddy) {
                $this->tips[] = 'Use clear water for ' . $muddy[0]['name'] . '. Clay in muddy water takes it out of the spray.';
            }
            $alkFast = $this->with($sprays, ['alk_fast']);
            if ($alkFast && $water === 'hard') {
                $this->note('alk_water', 'care', $alkFast);
            } elseif ($alkFast || $this->with($sprays, ['alk_slow'])) {
                $this->tips[] = 'Spray within an hour or two of mixing and never leave the tank overnight: ' . implode(', ', array_map(fn ($x) => $x['name'], $alkFast ?: $this->with($sprays, ['alk_slow']))) . ' break down standing in the water.';
            }
            $alkSens = $this->with($sprays, ['alk_fast', 'alk_slow']);
            $acidSens = $this->with($sprays, ['acid_sens']);
            $a1 = array_values(array_filter($alkSens, fn ($x) => ! isset($x['flags']['acid_sens'])));
            $b1 = array_values(array_filter($acidSens, fn ($x) => ! isset($x['flags']['alk_fast']) && ! isset($x['flags']['alk_slow'])));
            if ($a1 && $b1) {
                $this->note('ph_conflict', 'care', array_merge($a1, $b1), [], ['name' => implode(', ', array_map(fn ($x) => $x['name'], $a1))], ['name' => implode(', ', array_map(fn ($x) => $x['name'], $b1))]);
            }
            if (count($sprays) >= 5) {
                $this->note('too_many', 'care', $sprays);
            }
            // The same active ingredient twice, the same group twice.
            for ($i = 0; $i < count($sprays); $i++) {
                for ($j = $i + 1; $j < count($sprays); $j++) {
                    $x = $sprays[$i];
                    $y = $sprays[$j];
                    $ax = array_column($x['tr']['known'], 'name');
                    $ay = array_column($y['tr']['known'], 'name');
                    $same = array_values(array_intersect($ax, $ay));
                    if ($same) {
                        $this->note('same_ai', 'care', [$x, $y], ['ai' => implode(', ', $same)], $x, $y);

                        continue;
                    }
                    $gx = array_filter($x['tr']['groups'], fn ($g) => ! preg_match('/\b(UN|UNB|UNF|NC|BM\d*|M\d+)\b|^$/', $g));
                    $gy = array_filter($y['tr']['groups'], fn ($g) => ! preg_match('/\b(UN|UNB|UNF|NC|BM\d*|M\d+)\b|^$/', $g));
                    $sg = array_values(array_intersect($gx, $gy));
                    if ($sg) {
                        $this->note('same_group', 'care', [$x, $y], ['grp' => $sg[0]], $x, $y);
                    }
                }
            }
            if ($strength && ($strength['pct'] ?? 0) >= 1.0 && $this->with($sprays, ['f_ec', 'f_dry'])) {
                $this->note('salt_emulsion', 'care', $this->with($sprays, ['f_ec', 'f_dry']));
            }
            // Heat, rain and the crop.
            $sulfur = $this->with($sprays, ['sulfur']);
            $oil = $this->with($sprays, ['oil']);
            $fert = $this->with($sprays, ['fert_salt']);
            if ($weather === 'hot') {
                if ($sulfur) {
                    $this->note('heat_sulfur', 'no', $sulfur);
                }
                if ($oil) {
                    $this->note('heat_oil', 'care', $oil);
                }
                if ($fert) {
                    $this->note('heat_fert', 'care', $fert);
                }
            } elseif ($weather === '' && ($sulfur || $oil)) {
                $this->tips[] = 'Do not spray ' . implode(' or ', array_map(fn ($x) => $x['name'], array_merge($sulfur, $oil))) . ' when it is hotter than about 32 C. Spray early in the morning.';
            }
            if ($weather === 'rain') {
                $contact = array_values(array_filter($sprays, fn ($x) => $this->has($x, ['dtc', 'copper', 'chlorothalonil', 'sulfur', 'captan', 'fert_salt', 'pyr'])));
                if ($contact) {
                    $this->note('rain_soon', 'care', $contact);
                }
            }
            $cuc = (array) ($this->rules['cucurbits'] ?? []);
            $beans = (array) ($this->rules['beans'] ?? []);
            $copper = $this->with($sprays, ['copper']);
            if ($copper && $crop && (in_array($crop, $cuc, true) || in_array($crop, $beans, true)) && in_array($stage, ['seedling', 'vegetative', 'flowering', ''], true)) {
                $this->note('copper_young_cucurbit', 'care', $copper);
            }
            if ($sulfur && $crop && in_array($crop, $cuc, true)) {
                $this->note('sulfur_cucurbit', 'care', $sulfur);
            }
            if ($oil && $stage === 'flowering') {
                $this->note('oil_flowers', 'care', $oil);
            }
            if ($strength && ($strength['level'] ?? 'ok') !== 'ok') {
                foreach ((array) ($strength['notes'] ?? []) as $n) {
                    $this->tank[] = $n;
                }
            }
        }
        if ($soils) {
            $salty = $this->with($soils, ['fe_chloride', 'fe_nitrate', 'fe_ammonium', 'fe_urea']);
            if ($salty && $stage === 'seedling') {
                $this->note('salt_seedling', 'care', $salty);
            }
            $grans = $this->with($soils, ['fert_gran']);
            if ($grans && $stage !== 'preplant' && $crop && ! in_array($crop, ['rice', 'sugarcane', 'corn_yellow', 'corn_glutinous', 'sorghum'], true)) {
                $this->tips[] = 'Spread the granules when the leaves are dry and brush or wash off any that stay on the plants: they burn spots where they sit.';
            }
        }
    }

    /* ================================================================ strength */

    /** How strong the fertilizer in the spray is, against the crop's limit. */
    private function strength(array $sprays, ?string $cropName): ?array
    {
        $ferts = $this->with($sprays, ['fert_salt']);
        if (! $ferts) {
            return null;
        }
        $f = (array) ($this->rules['foliar'] ?? []);
        $crop = $this->ctx['crop'];
        $group = 'moderate';
        if ($crop && in_array($crop, (array) ($f['groups']['tolerant'] ?? []), true)) {
            $group = 'tolerant';
        } elseif ($crop && in_array($crop, (array) ($f['groups']['sensitive'] ?? []), true)) {
            $group = 'sensitive';
        }
        $lim = (float) ($f['total'][$group] ?? 1.0);
        $ulim = (float) ($f['urea'][$group] ?? 1.0);
        $stage = $this->ctx['stage'];
        $mult = (float) ($f['stage'][$stage] ?? 1.0);
        if ($this->ctx['weather'] === 'hot') {
            $mult *= (float) ($f['hot'] ?? 0.75);
        }
        $lim = round($lim * $mult, 2);
        $ulim = round($ulim * $mult, 2);
        $gl = 0.0;
        $urea = 0.0;
        $missing = [];
        foreach ($ferts as $x) {
            $d = $x['dose'];
            if (empty($d['perL'])) {
                $missing[] = $x['name'];

                continue;
            }
            $density = (float) ($x['p']['density'] ?? 1.0);
            $g = $d['base'] === 'mL' ? $d['perL'] * $density : $d['perL'];
            // A liquid fertilizer is part water: count what its analysis says it carries, at least a third.
            if ($d['base'] === 'mL' && ! empty($x['p']['n'])) {
                $share = min(1.0, max(0.33, array_sum(array_map('floatval', (array) $x['p']['n'])) / 100 * 1.6));
                $g *= $share;
            }
            $gl += $g;
            if (isset($x['flags']['fe_urea'])) {
                $ureaShare = ($x['p']['k'] ?? '') === 'urea' || (float) ($x['p']['n']['N'] ?? 0) >= 44 ? 1.0 : 0.5;
                $urea += $g * $ureaShare;
            }
        }
        $pct = round($gl / 10, 2);
        $upct = round($urea / 10, 2);
        $stageWord = self::STAGES[$stage] ?? '';
        $level = 'ok';
        $notes = [];
        $tankL = $this->ctx['tankL'];
        if ($pct > $lim && $gl > 0) {
            $level = $pct > $lim * 1.5 ? 'no' : 'care';
            $w = (array) ($this->rules['tank']['strength_fert'] ?? []);
            $max = $this->amount($lim * 10 * $tankL, 'g');
            $more = ['pct' => ProductLibrary::num($pct), 'gl' => ProductLibrary::num(round($gl, 1)), 'lim' => ProductLibrary::num($lim), 'max' => $max . ' of fertilizer',
                'crop' => mb_strtolower($cropName ?? 'this crop'), 'stage' => $stageWord];
            $notes[] = ['id' => 'strength_fert', 'level' => $level, 'items' => array_map(fn ($x) => $x['i'], $ferts),
                'why' => $this->fill((string) ($w['why'] ?? ''), ['name' => ''], null, $more), 'fix' => $this->fill((string) ($w['fix'] ?? ''), ['name' => ''], null, $more)];
        }
        if ($upct > $ulim && $urea > 0) {
            $l2 = $upct > $ulim * 1.5 ? 'no' : 'care';
            if (self::RANK[$l2] > self::RANK[$level]) {
                $level = $l2;
            }
            $w = (array) ($this->rules['tank']['strength_urea'] ?? []);
            $more = ['pct' => ProductLibrary::num($upct), 'lim' => ProductLibrary::num($ulim), 'max' => $this->amount($ulim * 10 * $tankL, 'g'),
                'crop' => mb_strtolower($cropName ?? 'this crop'), 'stage' => $stageWord];
            $notes[] = ['id' => 'strength_urea', 'level' => $l2, 'items' => array_map(fn ($x) => $x['i'], $this->with($ferts, ['fe_urea'])),
                'why' => $this->fill((string) ($w['why'] ?? ''), ['name' => ''], null, $more), 'fix' => $this->fill((string) ($w['fix'] ?? ''), ['name' => ''], null, $more)];
        }

        return ['gl' => round($gl, 2), 'pct' => $pct, 'urea' => $upct, 'limit' => $lim, 'ureaLimit' => $ulim, 'group' => $group, 'level' => $level,
            'missing' => $missing, 'notes' => $notes, 'items' => array_map(fn ($x) => $x['i'], $ferts)];
    }

    /* ================================================================ order, jar, trips */

    private function order(array $sprays): array
    {
        if (! $sprays) {
            return [];
        }
        $slot = function (array $x): string {
            $fl = $x['flags'];
            if (isset($fl['conditioner']) || isset($fl['fe_ams']) || isset($fl['acidifier'])) {
                return 'conditioner';
            }
            if ($x['type'] === 'adjuvant' || isset($fl['oil']) || isset($fl['surfactant']) || isset($fl['sticker']) || isset($fl['organosilicone'])) {
                return 'helper';
            }
            if (isset($fl['fert']) && ! isset($fl['pest'])) {
                return 'fert';
            }

            return match ($x['tr']['group']) {
                'dry', 'dust' => 'dry',
                'flow', 'seed' => 'flow',
                'ec' => 'ec',
                'sl' => 'sl',
                default => isset($fl['pest']) ? 'sl' : 'fert',
            };
        };
        $by = [];
        foreach ($sprays as $x) {
            $by[$slot($x)][] = $x['name'];
        }
        $steps = [];
        foreach ((array) ($this->rules['order'] ?? []) as $st) {
            $k = $st['k'];
            if (in_array($k, ['water', 'top'], true)) {
                $steps[] = ['k' => $k, 'say' => $st['say'], 'items' => []];
            } elseif ($k === 'agitate') {
                if (! empty($by['dry'])) {
                    $steps[] = ['k' => $k, 'say' => $st['say'], 'items' => []];
                }
            } elseif (! empty($by[$k])) {
                $steps[] = ['k' => $k, 'say' => str_replace('{items}', implode(', ', $by[$k]), $st['say']), 'items' => $by[$k]];
            }
        }

        return $steps;
    }

    /** Each spray product's amount in a one liter jar (the tank's strength, made small). */
    private function jar(array $sprays): array
    {
        $out = [];
        foreach ($sprays as $x) {
            $d = $x['dose'];
            $out[] = ['i' => $x['i'], 'name' => $x['name'], 'amount' => ! empty($d['perL']) ? $this->amount($d['perL'], $d['base']) : null];
        }

        return $out;
    }

    /** Split the items into trips so that no two that must not mix share one (a greedy colouring). */
    private function trips(array $list, array $pairs, string $mode): array
    {
        if (count($list) < 2) {
            return $list ? [array_map(fn ($x) => $x['i'], $list)] : [];
        }
        $bad = [];
        foreach ($pairs as $p) {
            if ($p['mode'] === $mode && $p['level'] === 'no') {
                $bad[$p['a']][$p['b']] = true;
                $bad[$p['b']][$p['a']] = true;
            }
        }
        $trips = [];
        foreach ($list as $x) {
            $placed = false;
            foreach ($trips as &$t) {
                $ok = true;
                foreach ($t as $j) {
                    if (! empty($bad[$x['i']][$j])) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $t[] = $x['i'];
                    $placed = true;
                    break;
                }
            }
            unset($t);
            if (! $placed) {
                $trips[] = [$x['i']];
            }
        }

        return $trips;
    }

    /** Pairs that must be days apart, for the "keep apart" line. */
    private function gaps(array $pairs): array
    {
        $out = [];
        foreach ($pairs as $p) {
            if ($p['gap'] && $p['level'] !== 'ok') {
                $out[] = ['a' => $p['a'], 'b' => $p['b'], 'days' => $p['gap']];
            }
        }

        return $out;
    }

    private function headline(string $worst, array $pairs, array $trips, array $unknownItems): array
    {
        $no = count(array_filter($pairs, fn ($p) => $p['level'] === 'no'));
        $n = count($this->items);
        $sprayTrips = count($trips);

        return match ($worst) {
            'no' => ['t' => 'Do not mix all of these', 's' => ($no ? $no . ' ' . ($no === 1 ? 'pair' : 'pairs') . ' must not go together. ' : '')
                . ($sprayTrips > 1 ? 'Split the sprays into ' . $sprayTrips . ' trips, as shown below.' : 'Read what to do instead below.')],
            'care' => ['t' => 'Jar test first', 's' => 'These can go together with care. Read the notes, then test a small jar before you fill the tank.'],
            'unknown' => ['t' => 'We cannot check all of it', 's' => 'We do not know ' . (count($unknownItems) === 1 ? 'one of these' : count($unknownItems) . ' of these') . ' well enough. What we know mixes fine.'],
            default => ['t' => $n < 2 ? 'Nothing to mix with yet' : 'Mixes fine', 's' => $n < 2 ? 'Add one more product to check them together.'
                : 'No known clash between these. The label wins, and a jar test is wise when a mix is new to you.'],
        };
    }
}
