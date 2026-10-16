<?php

namespace App\Support;

/**
 * What the Compatibility Checker knows about a product, read from the
 * product book (App\Support\ProductLibrary) and two files:
 *
 *   database/products/ingredients.json   every active ingredient: its kind,
 *       chemical class, mode of action group (IRAC, FRAC, HRAC), and the
 *       flags the rules read (organophosphate, copper, sulfonylurea, breaks
 *       down in alkaline water, a living organism, and so on), with the
 *       patterns that recognise the name however a label writes it;
 *   database/products/compat-rules.json  the pair rules, the whole tank
 *       checks, the mixing order, the jar test and the foliar strength
 *       limits (written in the build session from extension and label
 *       sources, kept there for audit).
 *
 * traits() turns one product (or a farmer's own) into the set of flags the
 * rules match: its ingredients' flags, its type (pest, insect, fung, herb,
 * fert...), its formulation (f_dry, f_flow, f_ec, f_sl...) and its
 * fertilizer chemistry (fe_calcium, fe_sulfate, fe_phosphate, fe_urea...).
 */
final class CompatBook
{
    /** How each formulation goes into the tank (the mixing order reads it). */
    public const FORM_GROUP = [
        'WP' => 'dry', 'WG' => 'dry', 'WDG' => 'dry', 'DF' => 'dry', 'WS' => 'dry', 'EG' => 'dry',
        'SP' => 'sl', 'SG' => 'sl', 'WSG' => 'sl', 'WSP' => 'sl',
        'SC' => 'flow', 'CS' => 'flow', 'ZC' => 'flow', 'SE' => 'flow', 'ZW' => 'flow', 'F' => 'flow', 'FW' => 'flow', 'SCO' => 'flow', 'FC' => 'flow',
        'EC' => 'ec', 'EW' => 'ec', 'ME' => 'ec', 'OD' => 'ec', 'OL' => 'ec', 'DC' => 'ec', 'E' => 'ec', 'ES' => 'ec', 'OS' => 'ec', 'OF' => 'ec', 'UL' => 'ec', 'ULV' => 'ec',
        'SL' => 'sl', 'AS' => 'sl', 'L' => 'sl', 'LC' => 'sl', 'S' => 'sl', 'C' => 'sl',
        'G' => 'gran', 'GR' => 'gran', 'GB' => 'bait', 'P' => 'bait', 'RB' => 'bait', 'BB' => 'bait', 'B' => 'bait', 'TAB' => 'bait', 'PA' => 'bait', 'MB' => 'bait',
        'DP' => 'dust', 'D' => 'dust', 'FS' => 'seed', 'DS' => 'seed', 'ST' => 'seed', 'SD' => 'seed',
    ];

    /** Types that are crop protection, and the fertilizer types. */
    public const PEST_TYPES = ['insecticide', 'fungicide', 'herbicide', 'molluscicide', 'nematicide', 'acaricide', 'rodenticide', 'pgr'];

    public const FERT_TYPES = ['granular', 'soluble', 'foliar', 'amendment', 'biostimulant', 'biological'];

    /** Fertilizer traits that carry salts into a spray (organic and living ones carry few). */
    private const SALT_TRAITS = ['urea', 'ammonium', 'nitrate', 'calcium', 'sulfate', 'phosphate', 'chloride', 'metal', 'chelate', 'boron', 'magnesium', 'silicate', 'thiosulfate'];

    /** Ingredient flags that make a fungicide a chemical one (a living fungus dies in it). */
    private const FUNG_CHEM = ['dtc', 'copper', 'sulfur', 'captan', 'chlorothalonil', 'strobi', 'dmi', 'benzim', 'phenylamide', 'sdhi', 'cymoxanil', 'phosphonate'];

    private static ?array $ings = null;

    private static ?array $rules = null;

    private static ?array $byNorm = null;

    public static function ingredients(): array
    {
        if (self::$ings === null) {
            $d = json_decode((string) @file_get_contents(database_path('products/ingredients.json')), true);
            self::$ings = (array) ($d['ingredients'] ?? []);
        }

        return self::$ings;
    }

    public static function rules(): array
    {
        if (self::$rules === null) {
            self::$rules = json_decode((string) @file_get_contents(database_path('products/compat-rules.json')), true) ?: [];
        }

        return self::$rules;
    }

    /** The ingredient a name means (an exact name first, then the label patterns), or null. */
    public static function ingredient(string $name): ?array
    {
        $ings = self::ingredients();
        if (isset($ings[$name])) {
            return ['name' => $name] + $ings[$name];
        }
        if (self::$byNorm === null) {
            self::$byNorm = [];
            foreach ($ings as $n => $row) {
                self::$byNorm[self::norm($n)] = $n;
            }
        }
        $k = self::norm($name);
        if ($k === '') {
            return null;
        }
        if (isset(self::$byNorm[$k])) {
            $n = self::$byNorm[$k];

            return ['name' => $n] + $ings[$n];
        }
        $up = strtoupper(trim((string) preg_replace('/\s+/', ' ', str_replace(['–', '—'], '-', $name))));
        foreach ($ings as $n => $row) {
            foreach ((array) ($row['rx'] ?? []) as $rx) {
                if (@preg_match('#' . str_replace('#', '\#', $rx) . '#u', $up)) {
                    return ['name' => $n] + $row;
                }
            }
        }

        return null;
    }

    private static function norm(string $s): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($s)));
    }

    /** Names for a datalist: every ingredient the book knows (the farmer's own product form). */
    public static function ingredientNames(): array
    {
        return array_values(array_filter(array_keys(self::ingredients()), fn ($n) => ! preg_match('/\((other|bag film)\)|^Pheromone|^Insect traits/', $n)));
    }

    /**
     * Everything the rules need about one product: its flags, its ingredient
     * records (with the ones the book does not know), its tank group and
     * whether it is a fertilizer that carries salt.
     *
     * @param  array  $p  a product (ProductLibrary row, a material, or a farmer's own shaped the same way)
     */
    public static function traits(array $p, string $applied): array
    {
        $flags = [];
        $known = [];
        $unknown = [];
        $groups = [];
        $type = (string) ($p['t'] ?? 'other');
        foreach ((array) ($p['ai'] ?? []) as $a) {
            $name = (string) ($a['n'] ?? '');
            if ($name === '') {
                continue;
            }
            $ing = self::ingredient($name) ?? (isset($a['raw']) ? self::ingredient((string) $a['raw']) : null);
            if (! $ing) {
                $unknown[] = $name;

                continue;
            }
            $known[] = ['name' => $ing['name'], 'cls' => $ing['cls'], 'grp' => $ing['grp'], 'kind' => $ing['kind']];
            foreach ((array) $ing['flags'] as $f) {
                $flags[$f] = true;
            }
            if ($ing['grp'] !== '') {
                $groups[] = $ing['grp'];
            }
        }
        // Flags Anee found for an ingredient the book lacks (kept on the product row).
        foreach ((array) ($p['aiFlags'] ?? []) as $f) {
            $flags[(string) $f] = true;
        }
        foreach ((array) ($p['flags'] ?? []) as $f) {
            $flags[(string) $f] = true;
        }
        $form = strtoupper((string) ($p['f'] ?? ''));
        $group = self::FORM_GROUP[$form] ?? null;
        $isPest = in_array($type, self::PEST_TYPES, true);
        $isFert = in_array($type, self::FERT_TYPES, true) || ($type === 'chemical' && ! empty($p['n']));
        if ($isPest) {
            $flags['pest'] = true;
        }
        if (in_array($type, ['insecticide', 'acaricide', 'nematicide', 'molluscicide'], true)) {
            $flags['insect'] = true;
        }
        if ($type === 'fungicide') {
            $flags['fung'] = true;
            $living = isset($flags['living_fungus']) || isset($flags['living_bact']) || isset($flags['botanical']);
            if (array_intersect(self::FUNG_CHEM, array_keys($flags)) || (! $living && $known)) {
                $flags['fung_chem'] = true;
            }
        }
        if ($type === 'herbicide') {
            $flags['herb'] = true;
            $flags[isset($flags['glyphosate']) || isset($flags['glufosinate']) || isset($flags['bipyridyl']) ? 'herb_nonselective' : 'herb_selective'] = true;
        }
        if ($type === 'adjuvant') {
            $flags['adjuvant_any'] = true;
        }
        if ($group) {
            $flags['f_' . $group] = true;
        }
        // The fertilizer's chemistry, from its traits (the book's analysis or a material's own list).
        $salt = false;
        foreach ((array) ($p['tr'] ?? []) as $t) {
            $flags['fe_' . $t] = true;
            if (in_array($t, self::SALT_TRAITS, true)) {
                $salt = true;
            }
        }
        if ($isFert || $salt) {
            $flags['fert'] = true;
            if ($salt) {
                $flags['fert_salt'] = true;
            }
            if ($applied === 'soil') {
                $flags['fert_gran'] = true;
            }
        }
        if ($isPest && $applied === 'soil') {
            $flags['pest_gran'] = true;
        }
        if (! empty($flags['fe_ammonium']) && ! empty($flags['fe_sulfate']) && abs((float) ($p['n']['N'] ?? 0) - 21) < 1.5 && empty($p['n']['P2O5']) && empty($p['n']['K2O'])) {
            $flags['fe_ams'] = true;
        }
        // A product the book holds no ingredient for, and no fertilizer analysis, is unknown to the rules.
        $hasFacts = $known || ! empty($p['n']) || ! empty($p['tr']) || ! empty($p['aiFlags']) || ! empty($p['flags']);

        return [
            'flags' => array_keys($flags),
            'known' => $known,
            'unknown' => $unknown,
            'groups' => array_values(array_unique($groups)),
            'group' => $group,
            'fact' => $hasFacts && ! $unknown,
            'isPest' => $isPest,
            'isFert' => $isFert || $salt,
        ];
    }
}
