<?php

namespace App\Support;

/**
 * The parts of a crop protocol the app works out itself (2026-10-18, the
 * owner: "add suggestions for protection against pests, etc. but put a
 * disclaimer ... also add suggestions as well based on the requirement on
 * micronutrients. especially if you wanna hit a target").
 *
 * PROTECTION: the pests, diseases and weeds our field catalogue lists for the
 * crop (App\Support\FieldCatalogue, worst first for that crop, the lot's own
 * troubles of last season first of all), each with its active ingredients by
 * IRAC, FRAC or HRAC group. Anee is handed these and chooses, stage by stage,
 * what to watch and when it is worth acting; she may name only ingredients
 * from these lists. The registered products are added here from our product
 * book (App\Support\ProductBook: sold in the Philippines, the label naming the
 * crop), never by Anee, so no product is ever invented. Every protection card
 * carries DISCLAIMER.
 *
 * NUTRITION: what the recommended program still leaves short against NPK
 * Plus's need (App\Support\NpkNeed::check, the same numbers the calculator
 * gives): for N, P and K the bags of a straight fertilizer that close the gap;
 * for every secondary and micro element, the kilos of its usual source sized
 * to the gap (the NPK Plus shelf's own analysis), or a foliar spray of that
 * nutrient at the stage it pays, where the soil would lock a soil dose up or
 * the amount is a few grams.
 */
final class ProtocolCare
{
    public const DISCLAIMER = 'Whether and when to apply any pesticide, herbicide, fungicide or other chemical is your own decision. Decide from what you see in your field: the crop\'s stage, how many pests or how much disease is there, the weather, and the label of the product. These are suggestions to watch for and to prepare, not a calendar to spray by. Always read and follow the label, wear protection, and keep to the days the label asks before harvest.';

    /** Where each element usually comes from when the program leaves it short: [shelf slug, name, percent of the element]. */
    private const SOURCES = [
        'S' => [['gypsum', 'Gypsum (calcium sulfate)', 18]],
        'Ca' => [['gypsum', 'Gypsum (calcium sulfate)', 23], ['lime', 'Agricultural lime', 36]],
        'Mg' => [['kieserite', 'Kieserite (magnesium sulfate)', 15], ['dolomite', 'Dolomite', 12]],
        'Zn' => [['zinc-sulfate', 'Zinc sulfate (monohydrate)', 35]],
        'B' => [['borax', 'Borax', 11]],
        'Fe' => [['ferrous-sulfate', 'Ferrous sulfate', 20]],
        'Mn' => [['manganese-sulfate', 'Manganese sulfate', 31]],
        'Cu' => [['copper-sulfate', 'Copper sulfate', 25]],
        'Mo' => [['sodium-molybdate', 'Sodium molybdate', 39]],
    ];

    /** Trace elements a spray can carry well. */
    private const SPRAYABLE = ['Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo'];

    /* ============================================================ protection */

    /**
     * The crop's likely troubles from the catalogue: [pests, diseases], the
     * troubles the farm's lots had last season first, then the crop's worst.
     *
     * @param  array<string, list<string>>  $hadBy  trouble key (pest:slug, disease:slug) => the lot names that had it
     */
    public static function threats(string $crop, array $hadBy = [], int $max = 8): array
    {
        $fc = FieldCatalogue::cropKey($crop) ?? $crop;
        $out = [];
        foreach (['pests' => 'pest', 'diseases' => 'disease'] as $section => $key) {
            $rows = [];
            $i = 0;
            foreach (FieldCatalogue::problems($section) as $slug => $p) {
                $i++;
                if (! in_array($fc, (array) ($p['crops'] ?? []), true)) {
                    continue;
                }
                $had = $hadBy[$key . ':' . $slug] ?? [];
                $rank = (int) ($p['ranks'][$fc] ?? (100 + $i));
                $rows[] = [
                    'id' => $key . ':' . $slug, 'section' => $section, 'slug' => $slug, 'name' => (string) ($p['name'] ?? $slug), 'kind' => (string) ($p['kind'] ?? ''),
                    'hint' => (string) ($p['hint'] ?? ''), 'local' => (string) ($p['local'] ?? ''),
                    'ai' => array_values(array_filter(array_map(fn ($a) => is_array($a) ? ['name' => (string) $a[0], 'group' => (string) ($a[1] ?? '')] : null, (array) ($p['ai'] ?? [])))),
                    'no' => (string) ($p['no'] ?? ''), 'had' => $had, 'rank' => $had ? -100 + $rank : $rank,
                ];
            }
            usort($rows, fn ($a, $b) => $a['rank'] <=> $b['rank']);
            $out[$section] = array_slice($rows, 0, $max);
        }

        return $out;
    }

    /**
     * The weed control windows our catalogue gives the crop (the rice plan by
     * how it went in), each with its ingredients by weed group and HRAC group,
     * placed on the lot's own day count.
     */
    public static function weeds(string $crop, string $method, ?int $harvest = null): ?array
    {
        $fc = FieldCatalogue::cropKey($crop) ?? $crop;
        $plans = FieldCatalogue::weedPlans();
        $groups = FieldCatalogue::ingredients();
        $plan = null;
        $planKey = null;
        // Rice has two plans, by how it went in (the plans are keyed rice_transplanted and rice_direct).
        $want = str_starts_with($crop, 'rice') ? (str_starts_with($method, 'direct') || str_starts_with($crop, 'rice_dsr') ? 'rice_direct' : 'rice_transplanted') : null;
        foreach ($plans as $pk => $p) {
            if (! in_array($fc, (array) ($p['crops'] ?? []), true) || ($want && $pk !== $want)) {
                continue;
            }
            $plan = $p;
            $planKey = (string) $pk;
            break;
        }
        if (! $plan) {
            return null;
        }
        $h = $harvest ?: 100;
        $span = ['pre_plant' => [-7, -1], 'pre_emergence' => [0, 3], 'early_post' => [(int) round($h * 0.1), (int) round($h * 0.25)], 'late_post' => [(int) round($h * 0.25), (int) round($h * 0.45)]];
        $windows = [];
        foreach ((array) ($plan['windows'] ?? []) as $wk => $w) {
            $k = (string) ($w['key'] ?? $wk);
            if (preg_match('/^(\d+)-(\d+)$/', $k, $mm)) {
                [$from, $to] = [(int) $mm[1], (int) $mm[2]];
            } else {
                [$from, $to] = $span[$k] ?? [0, 0];
            }
            $ing = [];
            foreach (['grasses' => 'grass', 'sedges' => 'sedge', 'broadleaves' => 'broadleaf'] as $field => $g) {
                foreach ((array) ($w[$field] ?? []) as $name) {
                    $ing[$name] ??= ['name' => $name, 'hrac' => array_map('strval', (array) ($groups[$name] ?? [])), 'weeds' => []];
                    $ing[$name]['weeds'][] = $g;
                }
            }
            $windows[] = ['key' => $k, 'title' => (string) ($w['title'] ?? $w['label'] ?? ''), 'stage' => (string) ($w['stage'] ?? ''), 'timing' => (string) ($w['timing'] ?? ''),
                'from' => $from, 'to' => $to, 'steps' => array_values(array_map('strval', (array) ($w['steps'] ?? []))), 'ingredients' => array_values($ing)];
        }

        return ['key' => $planKey, 'label' => (string) ($plan['label'] ?? ''), 'after' => (string) ($plan['after'] ?? ''), 'windows' => $windows];
    }

    /** The candidates as the prompt reads them: one line a pest or disease, then the weed windows. */
    public static function promptLines(array $threats, ?array $weeds, int $maxAi = 4): string
    {
        $line = function (array $t) use ($maxAi): string {
            $ai = array_slice($t['ai'], 0, $maxAi);

            return '  - ' . $t['name'] . ' (' . mb_strtolower($t['kind']) . ($t['had'] ? '; ' . implode(', ', $t['had']) . ' had it last season' : '') . '): '
                . rtrim($t['hint'], '.') . '. Ingredients: ' . ($ai ? implode('; ', array_map(fn ($a) => $a['name'] . ($a['group'] !== '' ? ' (' . $a['group'] . ')' : ''), $ai)) : ($t['no'] !== '' ? 'none, ' . lcfirst(rtrim($t['no'], '.')) : 'none listed'));
        };
        $out = "- Pests our catalogue lists for it:\n" . implode("\n", array_map($line, $threats['pests'] ?? [])) . "\n"
            . "- Diseases our catalogue lists for it:\n" . implode("\n", array_map($line, $threats['diseases'] ?? [])) . "\n";
        if ($weeds && $weeds['windows']) {
            $out .= '- Weed control windows (' . $weeds['after'] . "), the ingredients by HRAC group:\n";
            foreach ($weeds['windows'] as $w) {
                $out .= '  - ' . $w['title'] . ' (' . $w['stage'] . '): ' . implode('; ', array_map(fn ($i) => $i['name'] . ($i['hrac'] ? ' (HRAC ' . implode(', ', $i['hrac']) . ')' : ''), array_slice($w['ingredients'], 0, 6))) . "\n";
            }
        }

        return $out;
    }

    /**
     * Anee's protection plan made safe: each threat matched to the catalogue,
     * its ingredients kept only where the catalogue lists them (with their
     * group), the registered products added from the product book, its stages
     * placed on the lot's days, and a note when two ingredients share a group.
     */
    public static function attach(array $plan, array $threats, ?array $weeds, string $crop, array $frame): array
    {
        $fc = FieldCatalogue::cropKey($crop) ?? $crop;
        $norm = fn (string $s) => trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($s)));
        $cands = array_merge($threats['pests'] ?? [], $threats['diseases'] ?? []);
        $weedIng = [];
        foreach ((array) ($weeds['windows'] ?? []) as $w) {
            foreach ($w['ingredients'] as $i) {
                $weedIng[$norm($i['name'])] = ['name' => $i['name'], 'group' => $i['hrac'] ? 'HRAC ' . implode(', ', $i['hrac']) : ''];
            }
        }
        $out = [];
        foreach (array_slice((array) ($plan['threats'] ?? []), 0, 12) as $t) {
            if (! is_array($t) || trim((string) ($t['name'] ?? '')) === '') {
                continue;
            }
            $n = $norm((string) $t['name']);
            $cand = null;
            foreach ($cands as $c) {
                $cn = $norm($c['name']);
                if ($cn === $n || str_contains($n, $cn) || str_contains($cn, $n)) {
                    $cand = $c;
                    break;
                }
            }
            $kind = in_array($t['kind'] ?? '', ['insect', 'disease', 'weed', 'other'], true) ? $t['kind']
                : ($cand ? ($cand['section'] === 'diseases' ? 'disease' : 'insect') : 'other');
            $known = [];
            foreach ($cand['ai'] ?? [] as $a) {
                $known[$norm($a['name'])] = $a;
            }
            if ($kind === 'weed') {
                $known += $weedIng;
            }
            $ai = [];
            $other = [];
            foreach (array_slice((array) ($t['ai'] ?? []), 0, 6) as $name) {
                $k = $norm((string) $name);
                $hit = $known[$k] ?? null;
                if (! $hit) {
                    foreach ($known as $kk => $a) {
                        if ($k !== '' && (str_contains($kk, $k) || str_contains($k, $kk))) {
                            $hit = $a;
                            break;
                        }
                    }
                }
                if (! $hit) {
                    $other[] = (string) $name;
                    continue;
                }
                $products = array_map(fn ($p) => ['brand' => $p['brand'], 'company' => $p['company'], 'form' => $p['form'], 'kind' => $p['kind']],
                    ProductBook::forIngredient($hit['name'], $fc, null, 2));
                $ai[$hit['name']] = ['name' => $hit['name'], 'group' => $hit['group'], 'products' => $products];
            }
            // A product the label names for this very pest, when no ingredient brought one.
            $byLabel = [];
            if ($cand && ! array_filter($ai, fn ($a) => $a['products'])) {
                $byLabel = array_map(fn ($p) => ['brand' => $p['brand'], 'company' => $p['company'], 'form' => $p['form'], 'ai' => implode(' plus ', (array) $p['ai']), 'group' => $p['group']],
                    ProductBook::forProblem($cand['section'], $cand['slug'], $fc, 2));
            }
            $groups = array_values(array_filter(array_map(fn ($a) => $a['group'], $ai)));
            $same = count($groups) > 1 && count(array_unique($groups)) === 1;
            $stages = [];
            foreach (array_slice((array) ($t['stages'] ?? []), 0, 4) as $sn) {
                $i = ProtocolCrew::stageIndex($frame, (string) $sn);
                if ($i >= 0 && ! in_array($frame['stages'][$i]['name'], array_column($stages, 'name'), true)) {
                    $s = $frame['stages'][$i];
                    $stages[] = ['name' => $s['name'], 'from' => $s['from'], 'to' => $s['to']];
                }
            }
            $out[] = [
                'name' => trim((string) $t['name']), 'kind' => $kind, 'slug' => $cand['slug'] ?? null, 'section' => $cand['section'] ?? null, 'local' => $cand['local'] ?? '',
                'had' => $cand['had'] ?? [], 'stages' => $stages,
                'sign' => trim((string) ($t['sign'] ?? '')), 'threshold' => trim((string) ($t['threshold'] ?? '')), 'cultural' => trim((string) ($t['cultural'] ?? '')),
                'weather' => trim((string) ($t['weather'] ?? '')), 'rotate' => trim((string) ($t['rotate'] ?? '')),
                'ai' => array_values($ai), 'aiOther' => $other, 'byLabel' => $byLabel,
                'sameGroup' => $same ? $groups[0] : null,
                'noSpray' => $cand && ! $cand['ai'] ? ($cand['no'] ?: 'No spray cures it: prevent it and remove sick plants.') : null,
            ];
        }

        return ['intro' => trim((string) ($plan['intro'] ?? '')), 'threats' => $out, 'disclaimer' => self::DISCLAIMER];
    }

    /** The weed windows for the report, each ingredient with up to two registered products on the crop's label. */
    public static function weedsForReport(?array $weeds, string $crop): ?array
    {
        if (! $weeds) {
            return null;
        }
        $fc = FieldCatalogue::cropKey($crop) ?? $crop;
        foreach ($weeds['windows'] as &$w) {
            foreach ($w['ingredients'] as &$i) {
                $i['products'] = array_map(fn ($p) => ['brand' => $p['brand'], 'company' => $p['company'], 'form' => $p['form']], ProductBook::forIngredient($i['name'], $fc, null, 2));
            }
            unset($i);
            $w['ingredients'] = array_slice($w['ingredients'], 0, 8);
        }
        unset($w);

        return $weeds;
    }

    /* ============================================================ nutrition */

    /** A shelf product's percent of one element (the NPK Plus shelf), else the usual figure. */
    private static function pct(string $slug, string $el, float $fallback): float
    {
        try {
            foreach (NpkPlan::shelf() as $p) {
                if (($p['slug'] ?? null) === $slug && ! empty($p['pct'][$el])) {
                    return (float) $p['pct'][$el];
                }
            }
        } catch (\Throwable) {
        }

        return $fallback;
    }

    /** "195 kg", "1.4 kg", "350 g": whole kilos past 20, a tenth below, grams under a kilo. */
    public static function amount(float $kg): string
    {
        if ($kg >= 20) {
            return number_format((float) round($kg)) . ' kg';
        }

        return $kg >= 1 ? rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.') . ' kg' : max(5, (int) (round($kg * 1000 / 5) * 5)) . ' g';
    }

    /** The stage a nutrient spray pays at. */
    private static function sprayStage(array $frame, string $el): string
    {
        $real = array_values(array_filter($frame['stages'], fn ($s) => empty($s['prep'])));
        $pick = fn (string $re) => collect($real)->first(fn ($s) => preg_match($re, mb_strtolower($s['name'])));
        $s = match ($el) {
            'B' => $pick('/flower|silk|head|boot|bloom|fruit set|tassel/') ?? ($real[2] ?? null),
            'Mo' => $real[1] ?? ($real[0] ?? null),
            default => $pick('/tiller|vegetative|rapid|establish|growth|leaf/') ?? ($real[1] ?? null),
        };

        return $s['name'] ?? ($real[0]['name'] ?? 'Early growth');
    }

    /**
     * What closes the secondary and micro element gaps of a program, sized
     * to the need.
     *
     * @param  list<array>  $elements  the program's element rows (CropProtocolController::tidy, NpkNeed::check)
     * @param  list<string>  $conds    the soil conditions (acidic, alkaline, sodic, saline...)
     */
    public static function micro(array $elements, array $frame, array $conds, float $area): array
    {
        $acid = in_array('acidic', $conds, true) || in_array('acid_sulfate', $conds, true);
        $alk = in_array('alkaline', $conds, true) || in_array('sodic', $conds, true);
        $rows = [];
        foreach ($elements as $e) {
            $el = (string) ($e['key'] ?? '');
            if (! isset(self::SOURCES[$el]) || ! empty($e['beneficial'])) {
                continue;
            }
            $need = $e['need'] !== null ? (float) $e['need'] : 0.0;
            $plan = (float) ($e['plan'] ?? 0);
            $state = (string) ($e['state'] ?? '');
            if ($need <= 0 || ! in_array($state, ['short', 'spray'], true)) {
                continue;
            }
            $gap = max(0, $need - $plan);
            [$slug, $product, $fb] = self::SOURCES[$el][$el === 'Ca' && $acid ? 1 : ($el === 'Mg' && $acid ? 1 : 0)];
            $pct = self::pct($slug, $el, (float) $fb);
            $kg = $pct > 0 ? $gap * 100 / $pct : 0;
            $sprayable = in_array($el, self::SPRAYABLE, true);
            // A spray where the soil would lock it up, or where the dose is a few grams.
            $spray = $sprayable && ($el === 'Fe' || $el === 'Mo' || ($alk && in_array($el, ['Zn', 'Mn', 'Cu'], true)) || $kg < 0.5);
            $stage = $sprayable ? self::sprayStage($frame, $el) : null;
            $rows[] = [
                'el' => $el, 'name' => (string) ($e['name'] ?? $el), 'need' => round($need, 3), 'plan' => round($plan, 3), 'gap' => round($gap, 3),
                'level' => (string) ($e['level'] ?? ''), 'why' => array_values((array) ($e['why'] ?? [])),
                'state' => $state, 'sprayAt' => $e['sprayAt'] ?? null,
                'soil' => $state === 'spray' ? null : ['slug' => $slug, 'product' => $product, 'pct' => $pct, 'kgPerHa' => round($kg, 2), 'totalKg' => round($kg * $area, 2),
                    'say' => self::amount($kg) . ' ' . $product . ' a hectare' . ($area > 0 && abs($area - 1) > 0.01 ? ', ' . self::amount($kg * $area) . ' for the lot' : ''),
                    'when' => $frame['stages'][0]['name'] ?? 'Land preparation'],
                'spray' => $sprayable ? ['name' => (NpkNeed::NAMES[$el] ?? $el) . ' spray', 'stage' => $state === 'spray' && $e['sprayAt'] ? (string) $e['sprayAt'] : $stage] : null,
                'pick' => $state === 'spray' ? 'spray' : ($spray ? 'spray' : 'soil'),
                'note' => match (true) {
                    $state === 'spray' => 'The program already gives it by a spray.',
                    $el === 'B' => 'Boron burns just above the need: never more than this, and never twice.',
                    $el === 'Ca' && $acid => 'This is the feeding dose, not a lime dose to raise the pH.',
                    $el === 'Fe' => 'Iron spread on the soil is locked up fast, so a spray works better.',
                    $el === 'Mo' => 'A few grams: as a spray, or mixed with the seed.',
                    $spray && $alk => 'This soil locks it up, so a spray works better than the soil dose.',
                    default => '',
                },
            ];
        }

        return $rows;
    }

    /**
     * The straight fertilizer that closes an N, P or K gap of a program, at
     * the stage the crop takes it.
     *
     * @param  list<array>  $check   the program's N, P2O5, K2O rows (verdict short|ok|over, have, need, gap)
     * @param  list<array>  $byStage the program's applications by stage
     */
    public static function fixes(array $check, array $byStage, array $frame, array $conds, float $area, array $granulars = []): array
    {
        $gap = ['N' => 0.0, 'P2O5' => 0.0, 'K2O' => 0.0];
        $label = [];
        foreach ($check as $c) {
            $el = ['n' => 'N', 'p' => 'P2O5', 'k' => 'K2O'][$c['key'] ?? ''] ?? null;
            if ($el && ($c['verdict'] ?? '') === 'short' && $c['need'] !== null) {
                $gap[$el] = max(0, (float) $c['need'] - (float) $c['have']);
                $label[$el] = $c['label'];
            }
        }
        $salty = in_array('saline', $conds, true) || in_array('sodic', $conds, true);
        $stageOf = function (string $el) use ($byStage, $frame): string {
            // The stage that already gets the most of it, else a sensible one.
            $best = null;
            foreach ($byStage as $i => $apps) {
                foreach ($apps as $a) {
                    $g = \App\Http\Controllers\CropProtocolController::grade((string) ($a['product'] ?? ''));
                    $k = ['N' => 0, 'P2O5' => 1, 'K2O' => 2][$el];
                    $v = $g ? $g[$k] * (float) ($a['bagsPerHa'] ?? 0) : 0;
                    if ($v > 0 && (! $best || $v > $best[1])) {
                        $best = [$i, $v];
                    }
                }
            }
            if ($best && isset($frame['stages'][$best[0]])) {
                return $frame['stages'][$best[0]]['name'];
            }

            return $frame['stages'][$el === 'N' ? 2 : 0]['name'] ?? ($frame['stages'][0]['name'] ?? '');
        };
        $out = [];
        $row = function (string $el, string $name, float $kg, string $note = '') use (&$out, $label, $gap, $area, $stageOf) {
            if ($kg < 5) {
                return;   // a few kilos is inside the reading's own error
            }
            $out[] = ['el' => $el, 'label' => $label[$el] ?? $el, 'gap' => (int) round($gap[$el]), 'product' => $name, 'kgPerHa' => round($kg, 1), 'bagsPerHa' => round($kg / 50, 2),
                'bags' => round($kg * $area / 50, 2), 'stage' => $stageOf($el), 'note' => $note];
        };
        // Phosphorus first: where nitrogen is short too (or the farmer buys it), DAP or ammophos brings both, and the nitrogen it brings comes off the urea.
        if ($gap['P2O5'] > 0) {
            $bothShort = $gap['N'] > 0;
            if (in_array('dap', $granulars, true) || ($bothShort && ! in_array('ammophos', $granulars, true))) {
                $kg = $gap['P2O5'] / 0.46;
                $gap['N'] = max(0, $gap['N'] - $kg * 0.18);
                $row('P2O5', 'DAP 18-46-0', $kg, $bothShort ? 'It brings nitrogen too, so the urea below is that much less.' : '');
            } elseif ($bothShort || in_array('ammophos', $granulars, true)) {
                $kg = $gap['P2O5'] / 0.20;
                $gap['N'] = max(0, $gap['N'] - $kg * 0.16);
                $row('P2O5', 'Ammonium phosphate 16-20-0', $kg, $bothShort ? 'It brings nitrogen too, so the urea below is that much less.' : '');
            } else {
                $row('P2O5', 'Solophos 0-18-0', $gap['P2O5'] / 0.18, 'It also brings calcium and sulfur.');
            }
        }
        if ($gap['N'] > 0) {
            $row('N', 'Urea 46-0-0', $gap['N'] * 100 / self::pct('urea', 'N', 46), 'Split it over the stages that take nitrogen, never all at once.');
        }
        if ($gap['K2O'] > 0) {
            $salty
                ? $row('K2O', 'Sulfate of potash 0-0-50', $gap['K2O'] * 100 / self::pct('sop', 'K2O', 50), 'Sulfate of potash, not muriate: this soil has salt or sodium, and muriate adds chloride.')
                : $row('K2O', 'Muriate of potash 0-0-60', $gap['K2O'] * 100 / self::pct('mop', 'K2O', 60));
        }

        return $out;
    }
}
