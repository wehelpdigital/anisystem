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
     * The nutrients: name, symbol, Tagalog name, whether the plant moves it
     * from old leaves to new ones (so the old leaves show it first), hue,
     * and what it does, in a line. Silicon (2026-10-19) is a helpful element,
     * not a required one, as NPK Plus counts it: rice and sugarcane take up
     * a great deal of it.
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
        'si' => ['Silicon', 'Si', 'Silikon', false, 160, 'Stiffens the leaves and stems of rice and sugarcane against lodging, pests and blast.'],
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
            // Never an agency, a state university or a research institute (GovSource, the owner 2026-10-17).
            $e['sources'] = GovSource::keep(array_values(array_filter((array) ($e['sources'] ?? []), fn ($s) => ! empty($s['url']))));
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

    /**
     * How near two crops are, for a photo of one shown for the other (2026-10-28, the owner: "if no images are found
     * online, then look for a same deficiency on a different crop but nearest to the family of that crop ... but have a
     * disclaimer that it is a different crop but its near on how it looks"): crop or plant => [genus, family, order,
     * look]. The look is the last resort and only where shortages show alike: a grass shows them in stripes, a broad
     * leaf herb or tree between its veins; an onion, a taro or a cactus has no look alike outside its own kin. The
     * plants that are not crops here are the ones some stand in photos really show.
     */
    public const KIN = [
        'rice' => ['oryza', 'Poaceae', 'Poales', 'grass'], 'corn_yellow' => ['zea', 'Poaceae', 'Poales', 'grass'], 'corn_sweet' => ['zea', 'Poaceae', 'Poales', 'grass'],
        'corn_glutinous' => ['zea', 'Poaceae', 'Poales', 'grass'], 'sorghum' => ['sorghum', 'Poaceae', 'Poales', 'grass'], 'sugarcane' => ['saccharum', 'Poaceae', 'Poales', 'grass'],
        'bamboo' => ['bamboo', 'Poaceae', 'Poales', 'grass'], 'pineapple' => ['ananas', 'Bromeliaceae', 'Poales', ''],
        'mungbean' => ['vigna', 'Fabaceae', 'Fabales', 'herb'], 'cowpea' => ['vigna', 'Fabaceae', 'Fabales', 'herb'], 'stringbean' => ['vigna', 'Fabaceae', 'Fabales', 'herb'],
        'limabean' => ['phaseolus', 'Fabaceae', 'Fabales', 'herb'], 'soybean' => ['glycine', 'Fabaceae', 'Fabales', 'herb'], 'peanut' => ['arachis', 'Fabaceae', 'Fabales', 'herb'],
        'wingedbean' => ['psophocarpus', 'Fabaceae', 'Fabales', 'herb'], 'pigeonpea' => ['cajanus', 'Fabaceae', 'Fabales', 'herb'], 'tamarind' => ['tamarindus', 'Fabaceae', 'Fabales', 'tree'],
        'sweetpotato' => ['ipomoea', 'Convolvulaceae', 'Solanales', 'herb'], 'kangkong' => ['ipomoea', 'Convolvulaceae', 'Solanales', 'herb'],
        'potato' => ['solanum', 'Solanaceae', 'Solanales', 'herb'], 'tomato' => ['solanum', 'Solanaceae', 'Solanales', 'herb'], 'eggplant' => ['solanum', 'Solanaceae', 'Solanales', 'herb'],
        'chili' => ['capsicum', 'Solanaceae', 'Solanales', 'herb'], 'bellpepper' => ['capsicum', 'Solanaceae', 'Solanales', 'herb'], 'tobacco' => ['nicotiana', 'Solanaceae', 'Solanales', 'herb'],
        'cassava' => ['manihot', 'Euphorbiaceae', 'Malpighiales', 'herb'], 'rubber' => ['hevea', 'Euphorbiaceae', 'Malpighiales', 'tree'], 'mangosteen' => ['garcinia', 'Clusiaceae', 'Malpighiales', 'tree'],
        'taro' => ['colocasia', 'Araceae', 'Alismatales', ''], 'ubi' => ['dioscorea', 'Dioscoreaceae', 'Dioscoreales', ''],
        'carrot' => ['daucus', 'Apiaceae', 'Apiales', 'herb'], 'celery' => ['apium', 'Apiaceae', 'Apiales', 'herb'],
        'radish' => ['raphanus', 'Brassicaceae', 'Brassicales', 'herb'], 'pechay' => ['brassica', 'Brassicaceae', 'Brassicales', 'herb'], 'cabbage' => ['brassica', 'Brassicaceae', 'Brassicales', 'herb'],
        'mustard' => ['brassica', 'Brassicaceae', 'Brassicales', 'herb'], 'broccoli' => ['brassica', 'Brassicaceae', 'Brassicales', 'herb'], 'cauliflower' => ['brassica', 'Brassicaceae', 'Brassicales', 'herb'],
        'papaya' => ['carica', 'Caricaceae', 'Brassicales', 'tree'], 'malunggay' => ['moringa', 'Moringaceae', 'Brassicales', 'tree'],
        'ginger' => ['zingiber', 'Zingiberaceae', 'Zingiberales', ''], 'turmeric' => ['curcuma', 'Zingiberaceae', 'Zingiberales', ''],
        'abaca' => ['musa', 'Musaceae', 'Zingiberales', ''], 'banana' => ['musa', 'Musaceae', 'Zingiberales', ''],
        'lettuce' => ['lactuca', 'Asteraceae', 'Asterales', 'herb'], 'alugbati' => ['basella', 'Basellaceae', 'Caryophyllales', 'herb'], 'dragonfruit' => ['selenicereus', 'Cactaceae', 'Caryophyllales', ''],
        'saluyot' => ['corchorus', 'Malvaceae', 'Malvales', 'herb'], 'okra' => ['abelmoschus', 'Malvaceae', 'Malvales', 'herb'], 'cotton' => ['gossypium', 'Malvaceae', 'Malvales', 'herb'],
        'cacao' => ['theobroma', 'Malvaceae', 'Malvales', 'tree'], 'durian' => ['durio', 'Malvaceae', 'Malvales', 'tree'],
        'ampalaya' => ['momordica', 'Cucurbitaceae', 'Cucurbitales', 'herb'], 'squash' => ['cucurbita', 'Cucurbitaceae', 'Cucurbitales', 'herb'], 'cucumber' => ['cucumis', 'Cucurbitaceae', 'Cucurbitales', 'herb'],
        'melon' => ['cucumis', 'Cucurbitaceae', 'Cucurbitales', 'herb'], 'patola' => ['luffa', 'Cucurbitaceae', 'Cucurbitales', 'herb'], 'upo' => ['lagenaria', 'Cucurbitaceae', 'Cucurbitales', 'herb'],
        'sayote' => ['sechium', 'Cucurbitaceae', 'Cucurbitales', 'herb'], 'watermelon' => ['citrullus', 'Cucurbitaceae', 'Cucurbitales', 'herb'],
        'strawberry' => ['fragaria', 'Rosaceae', 'Rosales', 'herb'], 'jackfruit' => ['artocarpus', 'Moraceae', 'Rosales', 'tree'],
        'onion' => ['allium', 'Amaryllidaceae', 'Asparagales', ''], 'onion_spring' => ['allium', 'Amaryllidaceae', 'Asparagales', ''], 'garlic' => ['allium', 'Amaryllidaceae', 'Asparagales', ''],
        'shallot' => ['allium', 'Amaryllidaceae', 'Asparagales', ''],
        'oilpalm' => ['elaeis', 'Arecaceae', 'Arecales', 'palm'], 'coconut' => ['cocos', 'Arecaceae', 'Arecales', 'palm'],
        'mango' => ['mangifera', 'Anacardiaceae', 'Sapindales', 'tree'], 'cashew' => ['anacardium', 'Anacardiaceae', 'Sapindales', 'tree'],
        'calamansi' => ['citrus', 'Rutaceae', 'Sapindales', 'tree'], 'citrus' => ['citrus', 'Rutaceae', 'Sapindales', 'tree'], 'pomelo' => ['citrus', 'Rutaceae', 'Sapindales', 'tree'],
        'lanzones' => ['lansium', 'Meliaceae', 'Sapindales', 'tree'], 'santol' => ['sandoricum', 'Meliaceae', 'Sapindales', 'tree'], 'rambutan' => ['nephelium', 'Sapindaceae', 'Sapindales', 'tree'],
        'avocado' => ['persea', 'Lauraceae', 'Laurales', 'tree'], 'guava' => ['psidium', 'Myrtaceae', 'Myrtales', 'tree'],
        'chico' => ['manilkara', 'Sapotaceae', 'Ericales', 'tree'], 'starapple' => ['chrysophyllum', 'Sapotaceae', 'Ericales', 'tree'],
        'atis' => ['annona', 'Annonaceae', 'Magnoliales', 'tree'], 'guyabano' => ['annona', 'Annonaceae', 'Magnoliales', 'tree'],
        'coffee' => ['coffea', 'Rubiaceae', 'Gentianales', 'tree'],
        // What some stand in photos really show.
        'common-bean' => ['phaseolus', 'Fabaceae', 'Fabales', 'herb'], 'ornamental-ginger' => ['zingiber', 'Zingiberaceae', 'Zingiberales', ''],
        'flowering-ginger' => ['alpinia', 'Zingiberaceae', 'Zingiberales', ''], 'breadfruit' => ['artocarpus', 'Moraceae', 'Rosales', 'tree'],
        'endive' => ['cichorium', 'Asteraceae', 'Asterales', 'herb'], 'date-palm' => ['phoenix', 'Arecaceae', 'Arecales', 'palm'], 'palm' => ['', 'Arecaceae', 'Arecales', 'palm'],
        'zucchini' => ['cucurbita', 'Cucurbitaceae', 'Cucurbitales', 'herb'], 'scarlet-eggplant' => ['solanum', 'Solanaceae', 'Solanales', 'herb'],
        'gardenia' => ['gardenia', 'Rubiaceae', 'Gentianales', 'tree'], 'groundcover' => ['', '', '', 'herb'], 'ornamental-tree' => ['', '', '', 'tree'],
    ];

    /** The plants above that are not crops of the catalogue, in words. */
    private const PLANTS = ['common-bean' => 'common bean', 'ornamental-ginger' => 'ornamental ginger', 'flowering-ginger' => 'flowering ginger', 'breadfruit' => 'breadfruit',
        'endive' => 'endive', 'date-palm' => 'date palm', 'palm' => 'another palm', 'zucchini' => 'zucchini', 'scarlet-eggplant' => 'scarlet eggplant', 'gardenia' => 'gardenia',
        'groundcover' => 'a groundcover plant', 'ornamental-tree' => 'an ornamental tree'];

    /**
     * 4 the same genus, 3 the same family, 2 the same order and the same look (ginger and banana), 1 the same order or
     * only the same look (a tree and a shrub of one order, two broad leaf herbs), 0 nothing alike.
     */
    public static function kin(string $a, string $b): int
    {
        $x = self::KIN[$a] ?? null;
        $y = self::KIN[$b] ?? null;
        if (! $x || ! $y) {
            return 0;
        }
        if ($x[0] !== '' && $x[0] === $y[0]) {
            return 4;
        }
        if ($x[1] !== '' && $x[1] === $y[1]) {
            return 3;
        }
        $look = $x[3] === $y[3];
        if ($x[2] !== '' && $x[2] === $y[2]) {
            return $look ? 2 : 1;
        }

        return $look && $x[3] !== '' ? 1 : 0;
    }

    /** A crop or plant in words: "chili", "common bean". */
    public static function plantName(string $k): string
    {
        // "Onion, bulb" and "Citrus, dalandan or ponkan" say their first words.
        return self::PLANTS[$k] ?? mb_strtolower(trim(explode(',', (string) (FieldCatalogue::crops()[$k][1] ?? str_replace('_', ' ', $k)))[0]));
    }

    /**
     * An entry's photo. Its own crop's when there is one; else the nearest crop's photo of the same shortage (the same
     * genus, then family, then kind of plant; a stand in picked by hand wins a tie, it was matched by eye); else its
     * nutrient's general one. A stand in carries `shows` (what it really is), `kin` and `note`, the words said under it.
     */
    public static function photo(?string $slug, ?string $nutrient = null): ?array
    {
        $all = self::file('deficiency-photos.json');
        $p = $slug !== null ? ($all['entries'][$slug] ?? null) : null;
        if ($p && empty($p['standin'])) {
            return ! empty($p['src']) ? $p : null;
        }
        $e = $slug !== null ? (self::entries()[$slug] ?? null) : null;
        $nutrient ??= $e['nutrient'] ?? null;
        $crop = $e['crop'] ?? null;
        if ($crop && $nutrient) {
            $best = $p ? ['p' => $p, 'k' => self::kin($crop, (string) ($p['shows'] ?? ''))] : null;
            foreach (self::ownPhotos()[$nutrient] ?? [] as $c => $own) {
                $k = self::kin($crop, $c);
                if ($c !== $crop && $k > ($best['k'] ?? 0)) {
                    $best = ['p' => ['standin' => true, 'shows' => $c] + $own, 'k' => $k];
                }
            }
            $p = $best['p'] ?? null;
        }
        if (! $p && $nutrient !== null && ! empty($all['general'][$nutrient])) {
            $p = ['standin' => true] + $all['general'][$nutrient];
        }
        if (! $p || empty($p['src'])) {
            return null;
        }
        $shows = (string) ($p['shows'] ?? '');
        $kin = $crop && $shows !== '' ? self::kin($crop, $shows) : 0;

        return $p + ['kin' => $kin, 'note' => self::standinNote($crop, $shows, $kin, $nutrient)];
    }

    /** The words under a stand in: what crop it really is, how near, and that it is a different crop. */
    public static function standinNote(?string $crop, string $shows, int $kin, ?string $nutrient): string
    {
        $n = mb_strtolower(self::NUTRIENTS[$nutrient][0] ?? 'this');
        $for = $crop ? self::plantName($crop) : 'this crop';
        if ($shows === '') {
            return 'The photo shows ' . $n . ' deficiency on another crop: no free photo of it on ' . $for . ' was found.';
        }
        $on = self::plantName($shows);
        $near = [4 => ', a close relative of ' . $for . ', so it looks much the same', 3 => ', from the same plant family as ' . $for . ', so it looks much the same',
            2 => ', a distant relative of ' . $for . ', so the signs look alike', 1 => ', a different crop on which the signs look alike'][$kin] ?? ', a different crop';

        return 'This photo shows ' . $n . ' deficiency on ' . $on . $near . '. It is not ' . $for . ': no free photo of it on ' . $for . ' was found.';
    }

    /** Every crop's own photo, by nutrient: nutrient => crop => photo (the first entry of that crop). */
    private static function ownPhotos(): array
    {
        if (isset(self::$memo['ownPhotos'])) {
            return self::$memo['ownPhotos'];
        }
        $out = [];
        $entries = self::entries();
        foreach ((array) (self::file('deficiency-photos.json')['entries'] ?? []) as $slug => $p) {
            $e = $entries[$slug] ?? null;
            if ($e && empty($p['standin']) && ! empty($p['src'])) {
                $out[$e['nutrient']][$e['crop']] ??= $p;
            }
        }

        return self::$memo['ownPhotos'] = $out;
    }

    /* ---- fertilizers sold here ------------------------------------------ */

    /**
     * The order of kinds per nutrient: for N, P and K the bags for the soil
     * first; for sulfur its own (ammonium sulfate, sulfate of potash and
     * gypsum before a zinc spray); for calcium, magnesium and silicon the
     * liming materials first; for the trace elements the products made for
     * them.
     */
    private const KIND_ORDER = [
        'npk' => ['straight' => 0, 'compound' => 0, 'organic' => 2, 'foliar' => 3, 'micronutrient' => 4, 'liming' => 5],
        's' => ['straight' => 0, 'compound' => 1, 'liming' => 2, 'micronutrient' => 3, 'foliar' => 4, 'organic' => 5],
        'camg' => ['liming' => 0, 'straight' => 1, 'micronutrient' => 2, 'foliar' => 3, 'compound' => 4, 'organic' => 5],
        'micro' => ['micronutrient' => 0, 'straight' => 1, 'liming' => 2, 'foliar' => 3, 'compound' => 4, 'organic' => 5],
    ];

    /** The materials, the surest fix first, per nutrient (anything else after). */
    private const MATS = [
        'n' => ['urea', 'ammosul', 'amcl', 'urea_slow', 'canitrate', 'n_slow', 'urea_zn', 'kno3', 'n_other', 'nk', 'aps', 'n_liquid'],
        'p' => ['dap', 'aps', 'ssp', 'tsp', 'rockp', 'map', 'np', 'mkp', 'npk_p'],
        'k' => ['mop', 'sop', 'kno3', 'nk', 'kmag', 'k_other', 'mop_sol', 'mkp', 'npk_k', 'k_liquid', 'polysulphate'],
        'ca' => ['lime', 'dolomite', 'canitrate', 'gypsum', 'canitrate_b', 'cacl2', 'ca_soil', 'ssp', 'silicate', 'cab', 'ca_liquid'],
        'mg' => ['dolomite', 'kieserite', 'mgso4', 'mgo', 'mg_soil', 'kmag', 'mgnitrate', 'mg_liquid'],
        's' => ['ammosul', 'gypsum', 'sop', 'kieserite', 'sulfur', 'ssp', 'mgso4', 'aps', 'kmag', 'polysulphate'],
        'zn' => ['znso4', 'zn_soil', 'zno', 'zn_liquid', 'multi_micro'],
        'b' => ['borax', 'borate_gran', 'solubor', 'boric', 'b_soil', 'b_liquid', 'cab', 'canitrate_b'],
        'fe' => ['feso4', 'fe_liquid', 'fe_soil', 'multi_micro'],
        'mn' => ['mnso4', 'mn_liquid', 'mn_soil', 'multi_micro'],
        'cu' => ['cuso4', 'cu_liquid', 'cu_soil', 'multi_micro'],
        'mo' => ['molybdate', 'mo_liquid', 'multi_micro'],
        'si' => ['silicate', 'si_liquid'],
    ];

    /** The owner's order (2026-10-19): a Philippine company's own brand, a global maker's, then a trading house's product. */
    private const MAKER = ['ph' => 0, 'ph_arm' => 1, 'import' => 2];

    /** Nitrate is lost in a flooded paddy; chloride hurts the leaf of tobacco. */
    private const NO_NITRATE = ['rice'];

    private const NO_CHLORIDE = ['tobacco'];

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

    /** One nutrient's products in the file's order, read once a request. */
    private static function forN(string $n): array
    {
        if (! isset(self::$memo['byN'])) {
            $by = [];
            $i = 0;
            foreach (self::fertilizers() as $p) {
                $i++;
                foreach ((array) ($p['roles'] ?? []) as $k => $role) {
                    $by[$k][] = [$i, $p];
                }
            }
            self::$memo['byN'] = $by;
        }

        return self::$memo['byN'][$n] ?? [];
    }

    /** One nutrient's products with their order keys (no crop yet), sorted once a request. */
    private static function ranked(string $n): array
    {
        if (isset(self::$memo['ranked'][$n])) {
            return self::$memo['ranked'][$n];
        }
        $order = in_array($n, ['n', 'p', 'k'], true) ? self::KIND_ORDER['npk']
            : ($n === 's' ? self::KIND_ORDER['s'] : (in_array($n, ['ca', 'mg', 'si'], true) ? self::KIND_ORDER['camg'] : self::KIND_ORDER['micro']));
        $mats = self::MATS[$n] ?? [];
        $rows = [];
        foreach (self::forN($n) as [$i, $p]) {
            $role = (string) ($p['roles'][$n] ?? '');
            $mi = array_search((string) ($p['mat'] ?? ''), $mats, true);
            $rows[] = ['k' => [
                $role === 'main' ? 0 : 1, $order[$p['kind'] ?? ''] ?? 9, $mi === false ? count($mats) : $mi, self::MAKER[$p['maker'] ?? ''] ?? 2,
                min(count((array) $p['roles']), 5), 1, empty($p['pop']) ? 1 : 0, empty($p['local']) ? 1 : 0,
                -(int) ($p['w'] ?? 0), -(float) ($p['pct'][$n] ?? 0), $i,
            ], 'p' => $p, 'role' => $role, 'named' => false];
        }
        usort($rows, fn ($a, $b) => $a['k'] <=> $b['k']);

        return self::$memo['ranked'][$n] = $rows;
    }

    /**
     * The registered products that really supply one nutrient for one crop,
     * the likeliest help first (2026-10-19, the owner: "prioritize on local
     * companies in ph"). The order, first key first:
     *  1. made for the nutrient (roles main) before a mix that only carries
     *     a share of it;
     *  2. the kind, in the nutrient's own order (KIND_ORDER);
     *  3. the material, the surest fix first (MATS);
     *  4. the maker: a Philippine company's own brand, then a global maker's,
     *     then a trading house's product with no maker named;
     *  5. fewer nutrients before many;
     *  6. the crop on the label;
     *  7. the brands most stores carry, made or packed here, the larger
     *     registrant, the stronger product, the file's order.
     * The list then takes the best of each kind (soil, soluble, spray), the
     * best of each material, one mix whose label names the crop, then the
     * rest, never more than two from one company. A product labelled only
     * for other crops is left out, as is nitrate for palay and chloride for
     * tobacco. The scratchpad nd2/rank.py is the twin the public pages use:
     * change both.
     *
     * @return list<array>
     */
    public static function productsFor(string $n, ?string $crop = null, int $max = 6): array
    {
        $memoKey = 'pf|' . $n . '|' . $crop . '|' . $max;
        if (isset(self::$memo[$memoKey])) {
            return self::$memo[$memoKey];
        }
        $rows = [];
        $renamed = false;
        foreach (self::ranked($n) as $r) {
            $ck = (array) ($r['p']['cropKeys'] ?? []);
            if ($crop && $ck && ! in_array($crop, $ck, true)) {
                continue;
            }
            $p = $r['p'];
            if ($crop && ((in_array($crop, self::NO_NITRATE, true) && ! empty($p['nitrate'])) || (in_array($crop, self::NO_CHLORIDE, true) && ! empty($p['chloride'])))) {
                continue;
            }
            // The crop on the label moves a product up among its equals.
            $r['named'] = $crop && $ck && in_array($crop, $ck, true);
            if ($r['named']) {
                $r['k'][5] = 0;
                $renamed = true;
            }
            $rows[] = $r;
        }
        if ($renamed) {
            usort($rows, fn ($a, $b) => $a['k'] <=> $b['k']);
        }
        $taken = [];
        $per = [];
        $seen = [];
        $carried = false;
        foreach ([['main', 'kind'], ['main', 'mat'], ['carries', 'named'], ['main', 'rest'], ['carries', 'mat'], ['carries', 'rest']] as [$role, $step]) {
            foreach ($rows as $idx => $r) {
                if (count($taken) >= $max) {
                    break 2;
                }
                if ($r['role'] !== $role || isset($taken[$idx])) {
                    continue;
                }
                $p = $r['p'];
                if ($step === 'named' && (! $r['named'] || $carried)) {
                    continue;
                }
                if (($step === 'kind' && isset($seen['k|' . $role . '|' . ($p['kind'] ?? '')])) || ($step === 'mat' && isset($seen['m|' . $role . '|' . ($p['mat'] ?? '')]))) {
                    continue;
                }
                $c = mb_strtolower((string) ($p['company'] ?? '')) ?: $p['key'];
                if (($per[$c] ?? 0) >= 2) {
                    continue;
                }
                $per[$c] = ($per[$c] ?? 0) + 1;
                $taken[$idx] = true;
                $seen['k|' . $role . '|' . ($p['kind'] ?? '')] = true;
                $seen['m|' . $role . '|' . ($p['mat'] ?? '')] = true;
                $carried = $carried || $role === 'carries';
            }
        }
        ksort($taken);

        return self::$memo[$memoKey] = array_values(array_map(fn ($idx) => $rows[$idx]['p'], array_keys($taken)));
    }
}
