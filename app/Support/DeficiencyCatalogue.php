<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * THE NUTRIENT DEFICIENCY CATALOGUE (2026-10-16, the owner: "a nutrient
 * deficiency finder which targets deficiency, checks also products to help
 * (local products in PH), and with photos based on crop", public like the
 * pest and disease catalogues, gated, read whole by search engines).
 *
 * One entry is one crop and one nutrient (rice and nitrogen): where it shows
 * first, what it looks like, why it comes, what it is mistaken for, how to
 * be sure, what to apply and how to keep it away, with the sources it was
 * written from. The entries live in database/field-catalogue/deficiencies.json,
 * their photos in deficiency-photos.json (free licences only, a stand in
 * named as one), and the fertilizers sold in the Philippines by nutrient in
 * fertilizers.json (registered with the FPA; the register stays internal,
 * never shown, as with ProductBook).
 *
 * Read by the in app Nutrient Deficiency Finder (Field helpers, FieldBook),
 * the public /nutrient-deficiencies catalogue, and the page generator that
 * writes one page per crop (every deficiency of that crop, a section each)
 * and one page per nutrient.
 */
final class DeficiencyCatalogue
{
    public const SECTION = 'nutrient-deficiencies';

    /**
     * The twelve nutrients: name, symbol, Tagalog name, whether the plant moves
     * it from old leaves to new ones (so the old leaves show it first), hue,
     * and what it does, in a line.
     */
    public const NUTRIENTS = [
        'n' => ['Nitrogen', 'N', 'Nitroheno', true, 98, 'Makes the leaves green and the plant grow.'],
        'p' => ['Phosphorus', 'P', 'Posporo', true, 280, 'Builds the roots, the first growth and the flowers.'],
        'k' => ['Potassium', 'K', 'Potasyo', true, 30, 'Fills the grain and fruit and keeps the stems strong.'],
        's' => ['Sulfur', 'S', 'Asupre', false, 52, 'Helps make the green color and the proteins.'],
        'mg' => ['Magnesium', 'Mg', 'Magnesyo', true, 140, 'Sits at the heart of the green color of every leaf.'],
        'ca' => ['Calcium', 'Ca', 'Kalsiyum', false, 210, 'Builds the walls of new cells in tips, fruit and roots.'],
        'zn' => ['Zinc', 'Zn', 'Sink', false, 190, 'Helps the young leaves grow to full size.'],
        'fe' => ['Iron', 'Fe', 'Bakal', false, 12, 'Lets the young leaves make their green color.'],
        'mn' => ['Manganese', 'Mn', 'Manganeso', false, 330, 'Helps the leaves turn light into food.'],
        'b' => ['Boron', 'B', 'Boron', false, 350, 'Feeds the growing tip, the flowers and the fruit set.'],
        'cu' => ['Copper', 'Cu', 'Tanso', false, 24, 'Keeps new leaves and the grain healthy.'],
        'mo' => ['Molybdenum', 'Mo', 'Molibdenum', true, 250, 'Lets legumes and leafy crops use their nitrogen.'],
    ];

    /** The finder's step two: where it shows first. */
    public const WHERE = [
        'old' => 'Old, lower leaves first', 'young' => 'Young, upper leaves and the tip', 'whole' => 'The whole plant',
        'edges' => 'Leaf edges and tips', 'between' => 'Between the leaf veins', 'fruit' => 'Fruit, grain, pods or tubers', 'roots' => 'The roots',
    ];

    /** The finder's step three: what it looks like. */
    public const SIGNS = [
        'yellow' => 'Pale or yellow leaves', 'yellow_between' => 'Yellow between green veins', 'purple' => 'Purple or reddish color',
        'brown_edges' => 'Brown or burnt edges and tips', 'spots' => 'Spots or dead patches', 'small' => 'Small or bunched leaves',
        'stunted' => 'Short, thin, weak plants', 'dark' => 'Dark or bluish green leaves', 'curl' => 'Curled or twisted leaves',
        'dieback' => 'The growing tip dies', 'cracks' => 'Cracked or hollow stems, fruit or roots', 'rot' => 'Rotten fruit ends or tip burn',
        'poor_fill' => 'Empty grain, small fruit, few flowers', 'lodging' => 'Plants fall over',
    ];

    /** The kinds of fertilizer, as a card says them. */
    public const KINDS = [
        'straight' => 'Straight fertilizer', 'compound' => 'Compound fertilizer', 'foliar' => 'Foliar fertilizer',
        'micronutrient' => 'Micronutrient fertilizer', 'organic' => 'Organic fertilizer', 'liming' => 'Lime and soil conditioner',
    ];

    /** Crops that share one public page: the three corns are one corn page. */
    private const PAGE_OF = ['corn_yellow' => 'corn', 'corn_sweet' => 'corn', 'corn_glutinous' => 'corn'];

    private static array $memo = [];

    private static function file(string $name): array
    {
        return self::$memo[$name] ??= (json_decode((string) @file_get_contents(database_path('field-catalogue/' . $name)), true) ?: []);
    }

    /** Every entry, slug => entry, with its nutrient's words and its crop's shelf. */
    public static function entries(): array
    {
        if (isset(self::$memo['entries'])) {
            return self::$memo['entries'];
        }
        $crops = FieldCatalogue::crops();
        $out = [];
        foreach ((array) (self::file('deficiencies.json')['entries'] ?? []) as $e) {
            $n = (string) ($e['nutrient'] ?? '');
            $c = (string) ($e['crop'] ?? '');
            if (! isset(self::NUTRIENTS[$n], $crops[$c]) || empty($e['slug'])) {
                continue;
            }
            $e['where'] = array_values(array_intersect((array) ($e['where'] ?? []), array_keys(self::WHERE)));
            $e['signs'] = array_values(array_intersect((array) ($e['signs'] ?? []), array_keys(self::SIGNS)));
            $e['materials'] = array_values(array_filter(array_map('strval', (array) ($e['materials'] ?? []))));
            $e['sources'] = array_values(array_filter((array) ($e['sources'] ?? []), fn ($s) => ! empty($s['url'])));
            $e['rank'] = (int) ($e['rank'] ?? 50);
            $e['group'] = FieldCatalogue::shelfOf($c);
            $e['nutrientName'] = self::NUTRIENTS[$n][0];
            $e['symbol'] = self::NUTRIENTS[$n][1];
            $e['hue'] = self::NUTRIENTS[$n][4];
            $out[(string) $e['slug']] = $e;
        }

        return self::$memo['entries'] = $out;
    }

    public static function entry(string $slug): ?array
    {
        return self::entries()[$slug] ?? null;
    }

    /** One crop's entries, the commonest trouble first. */
    public static function forCrop(string $crop): array
    {
        $list = array_values(array_filter(self::entries(), fn ($e) => $e['crop'] === $crop));
        usort($list, fn ($a, $b) => [$a['rank'], $a['nutrient']] <=> [$b['rank'], $b['nutrient']]);

        return $list;
    }

    /** One nutrient's entries, every crop's. */
    public static function forNutrient(string $n): array
    {
        return array_values(array_filter(self::entries(), fn ($e) => $e['nutrient'] === $n));
    }

    /** The crops with entries, in the catalog's order: crop => [local, English, icon, shelf, how many]. */
    public static function crops(): array
    {
        $used = array_count_values(array_column(self::entries(), 'crop'));

        return collect(FieldCatalogue::crops())->filter(fn ($c, $k) => isset($used[$k]))->map(fn ($c, $k) => [...$c, $used[$k]])->all();
    }

    /* ---- the public pages --------------------------------------------- */

    /** The page a crop's deficiencies live on: the corns share the corn page. */
    public static function pageKey(string $crop): string
    {
        return self::PAGE_OF[$crop] ?? $crop;
    }

    /** "rice-nutrient-deficiency", "bitter-gourd-nutrient-deficiency". */
    public static function cropSlug(string $crop): string
    {
        $key = self::pageKey($crop);
        $name = $key === 'corn' ? 'Corn' : (string) (FieldCatalogue::crops()[$key][1] ?? $key);
        $name = preg_replace('/\s*,.*$/', '', $name) ?? $name;

        return Str::slug($name) . '-nutrient-deficiency';
    }

    /** "nitrogen-deficiency". */
    public static function nutrientSlug(string $n): string
    {
        return Str::slug(self::NUTRIENTS[$n][0] ?? $n) . '-deficiency';
    }

    /** An entry's place: its crop page, at its own section. */
    public static function url(array $e): string
    {
        return SitePages::url(self::SECTION, self::cropSlug($e['crop'])) . '#' . self::anchor($e);
    }

    /** The section's anchor on the crop page: the nutrient ("nitrogen"), the heading's own id. The three corns share the corn page's sections. */
    public static function anchor(array $e): string
    {
        return Str::slug(self::NUTRIENTS[$e['nutrient']][0] ?? (string) $e['nutrient']);
    }

    /* ---- photos -------------------------------------------------------- */

    /** An entry's photo, or its nutrient's general one (named as a stand in), or null. */
    public static function photo(?string $slug, ?string $nutrient = null): ?array
    {
        $all = self::file('deficiency-photos.json');
        $p = $slug !== null ? ($all['entries'][$slug] ?? null) : null;
        if (! $p && $nutrient !== null && ! empty($all['general'][$nutrient])) {
            $p = $all['general'][$nutrient] + ['standin' => true];
        }

        return $p && ! empty($p['src']) ? $p : null;
    }

    /* ---- fertilizers sold here ------------------------------------------ */

    /** Every product, key => product (without its register line). */
    public static function fertilizers(): array
    {
        if (isset(self::$memo['fert'])) {
            return self::$memo['fert'];
        }
        $out = [];
        foreach ((array) (self::file('fertilizers.json')['products'] ?? []) as $p) {
            if (empty($p['key']) || empty($p['brand'])) {
                continue;
            }
            unset($p['source']);
            $out[(string) $p['key']] = $p;
        }

        return self::$memo['fert'] = $out;
    }

    /**
     * The products that supply one nutrient, the likeliest help first: for
     * nitrogen, phosphorus and potassium the straight and compound
     * fertilizers, for the others the products made for that nutrient before
     * the mixed foliars that carry a trace of it; among equals, a label that
     * names the crop before one that does not.
     *
     * @return list<array>
     */
    public static function productsFor(string $n, ?string $crop = null, int $max = 6): array
    {
        $order = in_array($n, ['n', 'p', 'k'], true)
            ? ['straight' => 0, 'compound' => 1, 'organic' => 2, 'foliar' => 3, 'micronutrient' => 4, 'liming' => 5]
            : ['micronutrient' => 0, 'straight' => 1, 'liming' => 2, 'foliar' => 3, 'compound' => 4, 'organic' => 5];
        if ($n === 's') {
            // Sulfur comes with ammonium sulfate, sulfate of potash and gypsum, not with a zinc spray.
            $order = ['straight' => 0, 'compound' => 1, 'liming' => 2, 'micronutrient' => 3, 'foliar' => 4, 'organic' => 5];
        }
        if ($n === 'ca' || $n === 'mg') {
            $order = ['liming' => 0, 'straight' => 1, 'micronutrient' => 2, 'foliar' => 3, 'compound' => 4, 'organic' => 5];
        }
        $label = self::cropWords($crop);
        $list = [];
        $i = 0;
        foreach (self::fertilizers() as $p) {
            $i++;
            $ns = (array) ($p['nutrients'] ?? []);
            if (! in_array($n, $ns, true)) {
                continue;
            }
            $crops = array_map('strtolower', (array) ($p['crops'] ?? []));
            // A product labelled only for other crops is left out for this one.
            $fits = ! $crops || ! $label || (bool) array_filter($crops, fn ($c) => (bool) array_filter($label, fn ($w) => str_contains($c, $w)));
            if (! $fits) {
                continue;
            }
            $named = $crops && $label && $fits;
            // The kind first, then a product made for few nutrients before a blend that only carries a trace, then the crop on the label.
            $list[] = [$order[$p['kind'] ?? ''] ?? 9, min(count($ns), 5), $named ? 0 : 1, $i, $p];
        }
        usort($list, fn ($a, $b) => array_slice($a, 0, 4) <=> array_slice($b, 0, 4));
        // Two a company at most, so the list shows the choice there is.
        $out = [];
        $per = [];
        foreach (array_column($list, 4) as $p) {
            $c = strtolower((string) ($p['company'] ?? '')) ?: $p['key'];
            if (($per[$c] ?? 0) >= 2) {
                continue;
            }
            $per[$c] = ($per[$c] ?? 0) + 1;
            $out[] = $p;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /** The words a label may use for a crop ("rice", "palay"). */
    private static function cropWords(?string $crop): array
    {
        if (! $crop) {
            return [];
        }
        $c = FieldCatalogue::crops()[$crop] ?? null;
        if (! $c) {
            return [];
        }
        $words = [strtolower((string) $c[0]), strtolower(preg_replace('/\s*,.*$/', '', (string) $c[1]) ?? '')];
        if (str_starts_with($crop, 'corn')) {
            $words[] = 'corn';
        }

        return array_values(array_filter(array_unique($words)));
    }
}
