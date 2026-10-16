<?php

namespace App\Support;

/**
 * What the product book knows about a product a farmer named, said in a few
 * words for a prompt (2026-10-18, the owner: learn once; "do this for all
 * other modules where anee needs to research for a product").
 *
 * A module that hands Anee a protocol or a plan with products in it (the
 * Protocol Builder's review) used to leave her to remember what each brand
 * is. The book (App\Support\ProductLibrary: every product registered with
 * the FPA, the companies' labels, and the products Anee looked up online for
 * an earlier farmer) is read first, and its facts go in beside the name: the
 * type, the active ingredients and their strength or the guaranteed
 * analysis, the formulation. Nothing is looked up online here, and no source
 * is ever named (the owner's rule for products).
 */
final class ProductFacts
{
    /** Lines by name, per request. */
    private static array $said = [];

    /** "insecticide; Phenthoate 500 g/L; EC" or "granular fertilizer; 15-9-20, Mg 1.1, S 3.8, B 0.02", or null when the book does not know it. */
    public static function line(string $name, bool $brandsOnly = true): ?string
    {
        if (trim($name) === '') {
            return null;
        }
        $k = mb_strtolower(trim($name)) . '|' . (int) $brandsOnly;
        if (array_key_exists($k, self::$said)) {
            return self::$said[$k];
        }
        $p = null;
        try {
            $p = ProductLibrary::find($name);
        } catch (\Throwable $e) {
            report($e);
        }
        // A plain material (urea, complete 14-14-14) says nothing its name does not: only brands are spelled out, unless asked.
        if (! $p || ($brandsOnly && str_starts_with((string) ($p['k'] ?? ''), 'mat-'))) {
            return self::$said[$k] = null;
        }
        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
        $bits = [mb_strtolower(ProductLibrary::TYPES[$p['t'] ?? ''] ?? 'product')];
        $ai = array_values(array_filter(array_map(fn ($a) => trim(($a['n'] ?? '') . (isset($a['a']) ? ' ' . $num($a['a']) . ' ' . ($a['u'] ?? '') : '')), (array) ($p['ai'] ?? []))));
        if ($ai) {
            $bits[] = implode(' + ', array_slice($ai, 0, 4));
        }
        $n = (array) ($p['n'] ?? []);
        if ($n) {
            $grade = isset($n['N']) || isset($n['P2O5']) || isset($n['K2O']) ? $num($n['N'] ?? 0) . '-' . $num($n['P2O5'] ?? 0) . '-' . $num($n['K2O'] ?? 0) : '';
            $more = array_map(fn ($el, $v) => $el . ' ' . $num($v), array_keys(array_diff_key($n, ['N' => 1, 'P2O5' => 1, 'K2O' => 1])), array_diff_key($n, ['N' => 1, 'P2O5' => 1, 'K2O' => 1]));
            $bits[] = trim($grade . ($grade && $more ? ', ' : '') . implode(', ', array_slice($more, 0, 6)));
        }
        if (! empty($p['f']) && ! in_array(mb_strtolower((string) $p['f']), ['granular', 'soluble', 'liquid'], true)) {
            $bits[] = (string) $p['f'];
        }
        if (($p['source'] ?? 'file') === 'anee') {
            $bits[] = 'from Anee\'s own earlier lookup';
        }

        return self::$said[$k] = implode('; ', array_filter($bits));
    }
}
