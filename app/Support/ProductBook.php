<?php

namespace App\Support;

/**
 * The products farmers can buy for what the field helpers name (2026-10-14,
 * the owner; rebuilt from the registration list 2026-10-19): herbicides for
 * the weed control helper, and every insecticide, fungicide, nematicide,
 * molluscicide, rodenticide and acaricide registered for sale in the
 * Philippines whose label names a catalogue pest or disease on a crop.
 *
 * database/field-catalogue/products.json, one row a product: key, brand,
 * company, origin (ph | ph_arm | import, database/products/company-origin.json),
 * kind, ai (its active ingredients), group (HRAC, IRAC or FRAC), formulation,
 * note (one line in our own words, where one was written). A pest or disease
 * product carries `on`: crop => the entries its label names there ("p:slug" a
 * pest, "d:slug" a disease); a herbicide carries crops, weeds (its weed groups),
 * timing and app_ai (the helper's ingredient names it serves).
 *
 * The owner's order (2026-10-19, "prioritize on local companies in ph"): in
 * every list a Philippine company comes first, then the Philippine company of
 * a global group, then a foreign maker's office or trader. Shown inside
 * anee.io only, never with a source or a registration number. No rates, no
 * prices: the label decides.
 */
final class ProductBook
{
    /** Who comes first: a Philippine company, then a global group's Philippine company, then an importer. */
    public const ORIGIN_RANK = ['ph' => 1, 'ph_arm' => 2, 'import' => 3];

    public const ORIGIN_WORDS = ['ph' => 'Philippine company', 'ph_arm' => 'Global company, Philippine office', 'import' => 'Foreign maker'];

    private static ?array $all = null;

    private static ?array $idx = null;

    /** Every product, keyed, in the owner's order (origin first, then the file's order). */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }
        $rows = json_decode((string) @file_get_contents(database_path('field-catalogue/products.json')), true)['products'] ?? [];
        $all = [];
        foreach ($rows as $i => $r) {
            if (isset($r['on'])) {
                $on = (array) $r['on'];
                // A "corn" label counts for white, glutinous and sweet corn too (the file keeps corn_yellow only).
                if (isset($on['corn_yellow'])) {
                    foreach (['corn_glutinous', 'corn_sweet'] as $c) {
                        $on[$c] = array_values(array_unique(array_merge($on['corn_yellow'], (array) ($on[$c] ?? []))));
                    }
                }
                $r['on'] = $on;
                $r['crops'] = array_keys($on);
                $refs = array_merge(...array_values($on ?: [[]]));
                $r['pests'] = array_values(array_unique(array_map(fn ($x) => substr($x, 2), array_filter($refs, fn ($x) => str_starts_with($x, 'p:')))));
                $r['diseases'] = array_values(array_unique(array_map(fn ($x) => substr($x, 2), array_filter($refs, fn ($x) => str_starts_with($x, 'd:')))));
                $r['app_ai'] = [implode(' plus ', (array) ($r['ai'] ?? []))];
            }
            // Its ingredients as one key ("lambda cyhalothrin plus thiamethoxam"), worked out once.
            $r['_n'] = self::norm(implode(' plus ', (array) ($r['ai'] ?? [])));
            $r['_o'] = [self::ORIGIN_RANK[$r['origin'] ?? 'ph'] ?? 2, $i];
            $all[$r['key']] = $r;
        }
        uasort($all, fn ($a, $b) => $a['_o'] <=> $b['_o']);

        return self::$all = $all;
    }

    /** One product, shaped for a card: with its origin, and what its label names (on each crop) in the catalogue's words. */
    public static function card(array $p): array
    {
        $note = trim((string) ($p['note'] ?? ''));
        $line = isset($p['on']) ? self::labelLine((array) $p['on']) : '';

        return [
            'key' => $p['key'], 'brand' => $p['brand'], 'company' => $p['company'] ?? '', 'kind' => $p['kind'],
            'ai' => $p['ai'] ?? [], 'group' => $p['group'] ?? '', 'form' => $p['formulation'] ?? '',
            'timing' => $p['timing'] ?? null, 'note' => trim($note . ($note !== '' && $line !== '' ? ' ' : '') . $line),
            'origin' => $p['origin'] ?? 'ph',
        ];
    }

    /**
     * The products for one active ingredient name as a helper writes it
     * ("Butachlor plus propanil"), on one crop; a herbicide may also be
     * narrowed to a weed group. Philippine companies first.
     */
    public static function forIngredient(string $ai, ?string $crop = null, ?string $weedGroup = null, int $max = 4): array
    {
        return array_map(fn ($k) => self::card(self::all()[$k]), self::keysForIngredient($ai, $crop, $weedGroup, $max));
    }

    /** forIngredient, as keys. */
    public static function keysForIngredient(string $ai, ?string $crop = null, ?string $weedGroup = null, int $max = 4): array
    {
        $all = self::all();
        $out = [];
        foreach (self::oneABrand(self::index()['ing'][self::norm($ai)] ?? []) as $k) {
            $p = $all[$k];
            if ($crop && ! in_array($crop, $p['crops'] ?? [], true)) {
                continue;
            }
            if ($weedGroup && $p['kind'] === 'herbicide' && ! in_array($weedGroup, $p['weeds'] ?? [], true)) {
                continue;
            }
            $out[] = $k;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /** The products a label names for one pest or disease on one crop (any crop when none), Philippine companies first. */
    public static function forProblem(string $section, string $slug, ?string $crop = null, int $max = 6): array
    {
        return array_map(fn ($k) => self::card(self::all()[$k]), self::keysForProblem($section, $slug, $crop, $max));
    }

    /** forProblem, as keys. A ready mix comes after the single ingredient products. */
    public static function keysForProblem(string $section, string $slug, ?string $crop = null, int $max = 6): array
    {
        $ref = ($section === 'diseases' ? 'd:' : 'p:') . $slug;
        $keys = $crop ? (self::index()['prob'][$ref][$crop] ?? []) : (self::index()['probAny'][$ref] ?? []);
        $all = self::all();
        // The single ingredient products before the mixes, the owner's order kept within each.
        usort($keys, fn ($a, $b) => [count($all[$a]['ai'] ?? []) > 1, $all[$a]['_o']] <=> [count($all[$b]['ai'] ?? []) > 1, $all[$b]['_o']]);

        return array_slice(self::oneABrand($keys), 0, $max);
    }

    /** A brand two companies register shows once: the first in the owner's order (a Philippine company's). */
    private static function oneABrand(array $keys): array
    {
        $all = self::all();
        $seen = [];

        return array_values(array_filter($keys, function ($k) use ($all, &$seen) {
            $b = self::norm((string) ($all[$k]['brand'] ?? $k));

            return isset($seen[$b]) ? false : ($seen[$b] = true);
        }));
    }

    /**
     * The products of one active ingredient (or ready mix) whose label names
     * this pest or disease on this crop: what "Sold as" lists under an
     * ingredient in the finders. Keys, Philippine companies first.
     */
    public static function keysForProblemIngredient(string $section, string $slug, ?string $crop, string $ai, int $max = 3): array
    {
        $ref = ($section === 'diseases' ? 'd:' : 'p:') . $slug;
        $n = self::norm(self::bare($ai));
        $keys = $crop ? (self::index()['prob'][$ref][$crop] ?? []) : (self::index()['probAny'][$ref] ?? []);
        $all = self::all();
        $out = [];
        foreach (self::oneABrand($keys) as $k) {
            if (($all[$k]['_n'] ?? '') === $n) {
                $out[] = $k;
                if (count($out) >= $max) {
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Everything the in-app helpers need in one go: ingredient name (as
     * normalised) => [crop => [product keys]]. Kept small: only crops a
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

    /** A company's origin: ph, ph_arm or import (a name the list does not know counts as Philippine). */
    public static function origin(string $company): string
    {
        static $book = null;
        $book ??= json_decode((string) @file_get_contents(database_path('products/company-origin.json')), true)['companies'] ?? [];

        return (string) ($book[$company]['o'] ?? 'ph');
    }

    /** The key a product list is filed under: lower case, dashes as spaces, no commas, one space between words. Kept once worked out. */
    public static function norm(string $s): string
    {
        static $seen = [];
        if (isset($seen[$s])) {
            return $seen[$s];
        }
        $n = strtolower(strtr($s, ['-' => ' ', ',' => '', "\t" => ' ', "\n" => ' ', "\r" => ' ']));
        while (str_contains($n, '  ')) {
            $n = str_replace('  ', ' ', $n);
        }

        return $seen[$s] = trim($n);
    }

    /* ---------------------------------------------------------------- */

    /** An ingredient name without its bracket note: "Thiamethoxam (seed treatment)" is thiamethoxam. */
    private static function bare(string $ai): string
    {
        $b = trim((string) preg_replace('/\s*\([^)]*\)/', '', $ai));

        return $b !== '' ? $b : $ai;
    }

    /** The lookups, built once a request: ingredient => keys, entry => crop => keys, entry => keys (any crop). */
    private static function index(): array
    {
        if (self::$idx !== null) {
            return self::$idx;
        }
        $ing = $prob = $any = [];
        foreach (self::all() as $k => $p) {
            foreach ($p['app_ai'] ?? [] as $ai) {
                $ing[self::norm((string) $ai)][] = $k;
            }
            foreach ((array) ($p['on'] ?? []) as $crop => $refs) {
                foreach ($refs as $ref) {
                    $prob[$ref][$crop][] = $k;
                    $any[$ref][$k] = true;
                }
            }
            // A curated herbicide row's flat lists (pests and diseases slugs) count on each of its crops.
            if (! isset($p['on'])) {
                foreach (['pests' => 'p:', 'diseases' => 'd:'] as $f => $pre) {
                    foreach ((array) ($p[$f] ?? []) as $s) {
                        foreach ((array) ($p['crops'] ?? []) as $crop) {
                            $prob[$pre . $s][$crop][] = $k;
                        }
                        $any[$pre . $s][$k] = true;
                    }
                }
            }
        }

        return self::$idx = ['ing' => $ing, 'prob' => $prob, 'probAny' => array_map('array_keys', $any)];
    }

    /** "Its label names rice: stem borer, rice bug; mango: cecid fly." in the catalogue's own names, three crops at most. */
    private static function labelLine(array $on): string
    {
        $crops = FieldCatalogue::crops();
        $parts = [];
        $seen = [];
        foreach ($on as $crop => $refs) {
            $cw = FieldCatalogue::cropWord((string) $crop);
            if (isset($seen[$cw])) {
                continue;   // white and sweet corn read as corn once
            }
            $seen[$cw] = true;
            $names = [];
            foreach ((array) $refs as $ref) {
                $e = FieldCatalogue::problem(str_starts_with($ref, 'd:') ? 'diseases' : 'pests', substr($ref, 2));
                if (! $e) {
                    continue;
                }
                $n = trim((string) preg_replace('/\s*\([^)]*\)/', '', $e['name']));
                // "Rice stem borer" on rice reads "stem borer"; "Rice bug" stays "rice bug".
                $rest = (string) preg_replace('/^' . preg_quote($cw, '/') . '\s+/iu', '', $n);
                if ($rest !== $n && str_contains($rest, ' ')) {
                    $n = $rest;
                }
                $names[mb_strtolower($n)] = mb_strtolower(mb_substr($n, 0, 1)) . mb_substr($n, 1);
            }
            if (! $names || ! isset($crops[$crop])) {
                continue;
            }
            $names = array_values($names);
            $parts[] = $cw . ': ' . implode(', ', array_slice($names, 0, 5)) . (count($names) > 5 ? ' and more' : '');
            if (count($parts) >= 3) {
                break;
            }
        }
        // The crops left unnamed, each counted once (white and sweet corn are corn).
        $total = count(array_unique(array_map(fn ($c) => FieldCatalogue::cropWord((string) $c), array_keys($on))));
        $left = count($parts) >= 3 ? max(0, $total - count($seen)) : 0;

        return $parts ? 'Its label names ' . implode('; ', $parts) . ($left > 0 ? '; and ' . $left . ' more crop' . ($left === 1 ? '' : 's') : '') . '.' : '';
    }
}
