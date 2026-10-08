<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NPK Plus outside its own page (2026-10-07): what a season or a protocol
 * puts on the ground, read from the materials its tasks carry.
 *
 * A task's material is a typed name ("Urea 46-0-0", "Complete 14-14-14",
 * "Ammosul", "Ipot ng manok") and an amount in whatever unit the farmer
 * wrote. match() reads the name against the NPK Plus shelf (as_npk_products,
 * the farmer's own products first), a grade written in the name winning
 * over a word; kg() turns the amount into kilograms; nutrients() into kg of
 * each nutrient. A biofertilizer counts its per hectare estimate once per
 * application, whatever the pack size. A name that is plainly a fertilizer
 * but cannot be read is handed back as unread, so the page can say what it
 * left out instead of quietly counting less.
 */
final class NpkPlan
{
    public const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'];

    /** Words to shelf slugs, the longer and surer phrases first. */
    private const WORDS = [
        'sulfate of potash' => 'sop', 'sulphate of potash' => 'sop', 'potassium sulfate' => 'sop', 'potassium sulphate' => 'sop',
        'potassium nitrate' => 'potassium-nitrate', 'calcium nitrate' => 'calcium-nitrate', 'calnit' => 'calcium-nitrate',
        'ammonium sulfate' => 'ammonium-sulfate', 'ammonium sulphate' => 'ammonium-sulfate', 'ammosul' => 'ammonium-sulfate',
        'diammonium' => 'dap', 'monoammonium' => 'map', 'triple super' => 'tsp', 'solophos' => 'ssp', 'single super' => 'ssp',
        'rock phosphate' => 'rock-phosphate', 'muriate' => 'mop', 'potassium chloride' => 'mop',
        'zinc sulfate hepta' => 'zinc-sulfate-7', 'zinc sulphate hepta' => 'zinc-sulfate-7', 'zinc sulfate' => 'zinc-sulfate', 'zinc sulphate' => 'zinc-sulfate',
        'ferrous sulfate' => 'ferrous-sulfate', 'iron sulfate' => 'ferrous-sulfate', 'manganese sulfate' => 'manganese-sulfate',
        'copper sulfate' => 'copper-sulfate', 'molybdate' => 'sodium-molybdate', 'boric acid' => 'boric-acid', 'borax' => 'borax',
        'magnesium sulfate' => 'epsom', 'epsom' => 'epsom', 'kieserite' => 'kieserite', 'anhydrous gypsum' => 'gypsum-anhydrous', 'gypsum anhydrous' => 'gypsum-anhydrous', 'anhydrite' => 'gypsum-anhydrous', 'gypsum' => 'gypsum', 'dolomite' => 'dolomite',
        'agricultural lime' => 'lime', 'apog' => 'lime', 'calcitic' => 'lime', 'elemental sulfur' => 'sulfur',
        'chicken manure' => 'chicken-manure', 'chicken dung' => 'chicken-manure', 'ipot ng manok' => 'chicken-manure', 'poultry manure' => 'chicken-manure',
        'carabao manure' => 'cattle-manure', 'cow manure' => 'cattle-manure', 'cattle manure' => 'cattle-manure', 'kalabaw' => 'cattle-manure',
        'vermicompost' => 'vermicompost', 'vermicast' => 'vermicompost', 'organic fertilizer' => 'organic-fertilizer', 'fish emulsion' => 'fish-emulsion',
        'azospirillum' => 'azospirillum', 'azotobacter' => 'azotobacter', 'rhizobium' => 'rhizobium', 'megaterium' => 'bacillus-megaterium',
        'mycorrhiza' => 'mycorrhiza', 'potassium solubilizing' => 'ksb', 'mb basal' => 'complete-16-16-8-s',
        'urea' => 'urea', 'complete' => 'complete-14', 'potash' => 'mop', 'dap' => 'dap', 'mop' => 'mop', 'sop' => 'sop', 'tsp' => 'tsp', 'ssp' => 'ssp',
    ];

    /** A name that says it is a fertilizer, so leaving it out is worth saying. */
    private const LOOKS_LIKE = '/fertili[sz]er|abono|foliar|\bnpk\b|\d+\s*-\s*\d+\s*-\s*\d+|nitrogen|phosph|potas|calcium|magnes|zinc|boron|micronutri|manure|compost|organic|\blime\b|gypsum|anhydrite|sulfate|sulphate|nitrate|urea/i';

    private static ?array $shelf = null;

    /** The shelf as NPK Plus draws it, the farmer's own products first. */
    public static function shelf(?int $userId = null): array
    {
        if (self::$shelf !== null) {
            return self::$shelf;
        }
        $userId ??= (int) WorkerContext::effectiveOwnerId();
        $rows = DB::table('as_npk_products')->where('deleteStatus', 1)
            ->where(fn ($q) => $q->whereNull('userId')->orWhere('userId', $userId)->orWhere('userId', Auth::id()))
            ->orderByRaw('userId is null asc')->orderBy('sortOrder')->orderBy('id')->get();

        return self::$shelf = $rows->map(fn ($p) => self::row($p))->all();
    }

    public static function row(object $p): array
    {
        $pct = [];
        foreach (self::NUTRIENTS as $n) {
            if ((float) $p->{$n} > 0) {
                $pct[$n] = (float) $p->{$n};
            }
        }

        return ['id' => (int) $p->id, 'slug' => $p->slug, 'name' => $p->name, 'local' => $p->local, 'category' => $p->category, 'unit' => $p->unit,
            'bagKg' => $p->bagKg !== null ? (float) $p->bagKg : null, 'density' => $p->densityKgL !== null ? (float) $p->densityKgL : null,
            'own' => (bool) $p->userId, 'pct' => $pct, 'estimate' => json_decode((string) $p->estimate, true) ?: null];
    }

    /** The shelf product a typed name means, or null. A grade in the name wins. */
    public static function match(string $name): ?array
    {
        $low = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
        if ($low === '') {
            return null;
        }
        $shelf = self::shelf();
        foreach ($shelf as $p) {
            if ($p['own'] && mb_strtolower($p['name']) === $low) {
                return $p;
            }
        }
        if (preg_match('/(?<![\d.])(\d{1,2}(?:\.\d)?)\s*-\s*(\d{1,2}(?:\.\d)?)\s*-\s*(\d{1,2}(?:\.\d)?)(?:\s*[-+]\s*(\d{1,2})\s*s)?(?![\d.])/i', $low, $m)) {
            [$n, $p2, $k] = [(float) $m[1], (float) $m[2], (float) $m[3]];
            $s = isset($m[4]) && $m[4] !== '' ? (float) $m[4] : null;
            foreach ($shelf as $p) {
                if (abs(($p['pct']['N'] ?? 0) - $n) < .01 && abs(($p['pct']['P2O5'] ?? 0) - $p2) < .01 && abs(($p['pct']['K2O'] ?? 0) - $k) < .01
                    && ($s === null || abs(($p['pct']['S'] ?? 0) - $s) < .01) && ! $p['own']) {
                    return $p;
                }
            }
            if ($n + $p2 + $k > 0 && $n + $p2 + $k <= 100) {
                // A grade the shelf does not carry: taken as written.
                return ['id' => 0, 'slug' => null, 'name' => trim($name), 'local' => null, 'category' => 'complete', 'unit' => 'kg', 'bagKg' => 50.0, 'density' => 1.0,
                    'own' => false, 'pct' => array_filter(['N' => $n, 'P2O5' => $p2, 'K2O' => $k, 'S' => $s]), 'estimate' => null, 'fromGrade' => true];
            }
        }
        $bySlug = [];
        foreach ($shelf as $p) {
            if ($p['slug']) {
                $bySlug[$p['slug']] = $p;
            }
        }
        foreach (self::WORDS as $word => $slug) {
            $hit = str_contains($word, ' ') ? str_contains($low, $word) : (bool) preg_match('/\b' . preg_quote($word, '/') . '\b/', $low);
            if ($hit && isset($bySlug[$slug])) {
                return $bySlug[$slug];
            }
        }

        return null;
    }

    /** Whether an unread name is worth naming as left out. */
    public static function looksLikeFertilizer(string $name): bool
    {
        return (bool) preg_match(self::LOOKS_LIKE, $name);
    }

    /**
     * Kilograms of product in an amount, or null when the unit cannot say
     * (a pack, a sachet). A biofertilizer is never weighed: it counts as
     * one application (see nutrients()).
     */
    public static function kg(float $qty, ?string $unit, array $p): ?float
    {
        $u = mb_strtolower(trim((string) $unit));
        $u = rtrim($u, '.');

        return match (true) {
            $qty <= 0 => 0.0,
            in_array($u, ['kg', 'kgs', 'kilo', 'kilos', 'kilogram', 'kilograms'], true), $u === '' && $p['unit'] === 'kg' => $qty,
            in_array($u, ['g', 'gram', 'grams', 'gm', 'grms'], true) => $qty / 1000,
            in_array($u, ['bag', 'bags', 'sack', 'sacks', 'sako'], true) => $qty * ($p['bagKg'] ?: 50),
            in_array($u, ['l', 'liter', 'liters', 'litre', 'litres', 'lt', 'ltr'], true) => $qty * ($p['density'] ?: 1),
            in_array($u, ['ml', 'milliliter', 'milliliters'], true) => $qty / 1000 * ($p['density'] ?: 1),
            in_array($u, ['t', 'ton', 'tons', 'tonne', 'tonnes', 'mt'], true) => $qty * 1000,
            default => null,
        };
    }

    /**
     * Kg of each nutrient: the label's percentages over the weight, or a
     * biofertilizer's estimate per hectare times the hectares it covered.
     */
    public static function nutrients(array $p, ?float $kg, float $hectares): array
    {
        $out = [];
        if (! empty($p['estimate'])) {
            foreach ($p['estimate'] as $n => $v) {
                $out[$n] = (float) $v * max(0, $hectares);
            }

            return $out;
        }
        if ($kg === null) {
            return [];
        }
        foreach ($p['pct'] as $n => $v) {
            $out[$n] = $kg * $v / 100;
        }

        return $out;
    }

    /** A lot's size in hectares, or null when it has none. */
    public static function hectares($size, ?string $unit): ?float
    {
        $v = (float) $size;
        if ($v <= 0) {
            return null;
        }
        $u = mb_strtolower(trim((string) $unit));

        return match (true) {
            in_array($u, ['sqm', 'm2', 'm²', 'square meters', 'sq m'], true) => $v / 10000,
            in_array($u, ['acre', 'acres', 'ac'], true) => $v * 0.404686,
            default => $v,
        };
    }

    /** The NPK Plus crop row for a lot's or a protocol's crop, or null. */
    public static function crop(?string $crop): ?array
    {
        $key = CropStages::normalize((string) $crop) ?: (string) $crop;
        $key = ['corn' => 'corn_yellow'][$key] ?? $key;

        return NpkCrops::TABLE[$key] ?? null;
    }

    /** Totals rounded for the page and the prompt, nothing empty. */
    public static function round(array $t, int $dp = 1): array
    {
        $o = [];
        foreach (self::NUTRIENTS as $n) {
            if (($t[$n] ?? 0) > 0) {
                $o[$n] = round($t[$n], $n === 'N' || $n === 'P2O5' || $n === 'K2O' ? $dp : 3);
            }
        }

        return $o;
    }

    /** "N 92, P2O5 28, K2O 58 kg per ha; Zn 1.2" for a prompt. */
    public static function words(array $t): string
    {
        $t = self::round($t);
        if (! $t) {
            return 'nothing';
        }

        return implode(', ', array_map(fn ($n, $v) => $n . ' ' . $v, array_keys($t), $t)) . ' kg per ha';
    }
}
