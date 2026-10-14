<?php

namespace App\Support;

/**
 * Example products farmers can buy for the active ingredients the field
 * helpers name (2026-10-14, the owner): herbicides for the weed control
 * helper, insecticides, fungicides, molluscicides, nematicides and
 * rodenticides for the pest and disease finders, per crop.
 *
 * database/field-catalogue/products.json, one row a product: key, brand,
 * company, kind, ai (its active ingredients), group (HRAC, IRAC or FRAC),
 * formulation, crops (crop keys its label names), weeds (herbicides: the
 * weed groups), timing (herbicides: pre | early_post | post | burndown),
 * pests and diseases (catalogue slugs its label covers), note (one line in
 * our own words), app_ai (the ingredient names the helpers use that it
 * serves). Only products registered for sale in the Philippines, listed for
 * a crop only where the label names it. Shown inside anee.io only, with no
 * source line (the owner's call). No rates, no prices: the label decides.
 */
final class ProductBook
{
    /** Every product, keyed. */
    public static function all(): array
    {
        static $all = null;
        if ($all === null) {
            $rows = json_decode((string) @file_get_contents(database_path('field-catalogue/products.json')), true)['products'] ?? [];
            $all = [];
            foreach ($rows as $r) {
                $all[$r['key']] = $r;
            }
        }

        return $all;
    }

    /** One product, shaped for a card. */
    public static function card(array $p): array
    {
        return [
            'key' => $p['key'], 'brand' => $p['brand'], 'company' => $p['company'] ?? '', 'kind' => $p['kind'],
            'ai' => $p['ai'] ?? [], 'group' => $p['group'] ?? '', 'form' => $p['formulation'] ?? '',
            'timing' => $p['timing'] ?? null, 'note' => $p['note'] ?? '',
        ];
    }

    /**
     * The products for one active ingredient name as a helper writes it
     * ("Butachlor plus propanil"), on one crop; a herbicide may also be
     * narrowed to a weed group.
     */
    public static function forIngredient(string $ai, ?string $crop = null, ?string $weedGroup = null, int $max = 4): array
    {
        $want = self::norm($ai);
        $out = [];
        foreach (self::all() as $p) {
            $names = array_map([self::class, 'norm'], $p['app_ai'] ?? []);
            if (! in_array($want, $names, true)) {
                continue;
            }
            if ($crop && ! in_array($crop, $p['crops'] ?? [], true)) {
                continue;
            }
            if ($weedGroup && $p['kind'] === 'herbicide' && ! in_array($weedGroup, $p['weeds'] ?? [], true)) {
                continue;
            }
            $out[] = self::card($p);
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /** The products a label names for one pest or disease on one crop. */
    public static function forProblem(string $section, string $slug, ?string $crop = null, int $max = 6): array
    {
        $field = $section === 'diseases' ? 'diseases' : 'pests';
        $out = [];
        foreach (self::all() as $p) {
            if (! in_array($slug, $p[$field] ?? [], true)) {
                continue;
            }
            if ($crop && ! in_array($crop, $p['crops'] ?? [], true)) {
                continue;
            }
            $out[] = self::card($p);
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /**
     * Everything the in-app helpers need in one go: ingredient name (as
     * normalised) => [crop => [product cards]]. Kept small: only crops a
     * product's label names.
     */
    public static function byIngredient(): array
    {
        $map = [];
        foreach (self::all() as $p) {
            foreach ($p['app_ai'] ?? [] as $ai) {
                foreach ($p['crops'] ?? [] as $crop) {
                    $map[self::norm($ai)][$crop][] = $p['key'];
                }
            }
        }

        return $map;
    }

    public static function norm(string $s): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strtolower(str_replace(['-', ','], [' ', ''], $s))));
    }
}
