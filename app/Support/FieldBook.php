<?php

namespace App\Support;

/**
 * The in-app catalogues of the Field helpers (2026-10-14): every pest,
 * disease and weed as the Pest Finder, the Disease Finder, the Weed Control
 * Helper and their full screen catalogues draw them. Read from the same
 * field catalogue and site pages the public /pests, /diseases and /weeds
 * read (App\Support\FieldCatalogue, ProblemCatalogue), so the app and the
 * public site never drift apart; nothing here links to a public page.
 *
 * One entry: k slug, name, sci, local, kind (or group for a weed), hue,
 * crops, parts and signs (pests and diseases), where (the finder's step two),
 * hint, ai [[ingredient, group]], no (why not to spray), ranks, thumb, photo,
 * alt, credit, life (weeds), sources [{label, url}].
 */
final class FieldBook
{
    /** A picture for each kind of pest or disease, on its filter. */
    public const KIND_ICONS = [
        'Insect' => '🐛', 'Mite' => '🕷️', 'Snail' => '🐌', 'Rodent' => '🐀', 'Bird' => '🐦',
        'Fungus' => '🍄', 'Bacterium' => '🦠', 'Virus' => '🧬', 'Viroid' => '🧬', 'Nematode' => '🪱', 'Water mold' => '💧',
    ];

    /** Everything the in-app pest or disease finder and its catalogue need. */
    public static function problems(string $section): array
    {
        $pages = SitePages::inSection($section);
        $live = $pages->keyBy('slug');
        $items = ProblemCatalogue::items($section, $pages);
        $facts = ProblemCatalogue::finderFacts($section, $items);
        $isPests = $section === 'pests';
        $entries = [];
        foreach ($items as $slug => $e) {
            [$photo, $credit] = self::photo($section, $slug, $live->get($slug));
            $entries[] = [
                'k' => $slug, 'name' => $e['name'], 'sci' => $e['sci'], 'local' => $e['local'], 'kind' => $e['kind'],
                'hue' => ProblemCatalogue::KINDS[$e['kind']] ?? 98, 'crops' => $e['crops'], 'parts' => $e['parts'], 'signs' => $e['signs'],
                'where' => $isPests ? $e['parts'] : $e['signs'], 'hint' => $e['hint'], 'ai' => $e['ai'], 'no' => $e['no'],
                'ranks' => (object) $e['ranks'], 'thumb' => $e['thumb'], 'photo' => $photo ?: $e['thumb'], 'alt' => $e['alt'], 'credit' => $credit,
                'sources' => self::sources($e['sources'] ?? []),
            ] + self::advice($e);
        }
        $kinds = collect($entries)->countBy('kind')->sortDesc();

        return [
            'section' => $section,
            'entries' => $entries,
            'crops' => $facts['crops'],
            'askOpts' => $facts['askOpts'],
            'parts' => ProblemCatalogue::PARTS,
            'signs' => ProblemCatalogue::SIGNS,
            'words' => $facts['words'],
            'isPests' => $isPests,
            // The kind filter: [key, label, icon, how many], the commonest first.
            'facets' => $kinds->map(fn ($n, $k) => [$k, $k, self::KIND_ICONS[$k] ?? '🌱', $n])->values()->all(),
            'cropNames' => self::cropNames($facts['crops']),
            'products' => self::problemProducts($section, $entries),
            // Systemic, translaminar or contact, per active ingredient (2026-10-15).
            'mobility' => AiMobility::payload(),
            // The varieties rated to hold up against each one, per crop (2026-10-18): the variety records and their registration trials.
            'resist' => self::resistant($entries),
        ];
    }

    /**
     * For each pest or disease, per crop, the registered varieties rated
     * highly resistant, resistant or moderately resistant to it (the
     * curated records and their registration trials, App\Support\VarietyBook),
     * strongest first, then newest: [slug => [crop => [[name, other name, code, year]]]].
     * A rating is matched by its name inside the entry's name ("blast" in
     * "Rice blast"), so only the ones the records rate are listed.
     */
    public static function resistant(array $entries, int $limit = 8): array
    {
        // Kept an hour, and anew whenever a variety file changes (a deploy).
        $stamp = implode(':', array_map(fn ($f) => (string) (@filemtime(database_path($f)) ?: 0),
            ['rice-varieties/varieties.json', 'crop-varieties/varieties.json', VarietyRegistry::FILE]));
        $key = 'field-resist:' . md5(json_encode(array_map(fn ($e) => [$e['k'], $e['crops']], $entries)) . $limit) . ':' . $stamp;

        return \Illuminate\Support\Facades\Cache::remember($key, 3600, fn () => self::resistantNow($entries, $limit));
    }

    private static function resistantNow(array $entries, int $limit): array
    {
        $out = [];
        $rank = ['HR' => 3, 'R' => 2, 'MR' => 1];
        foreach ($entries as $e) {
            $name = ' ' . mb_strtolower((string) $e['name'] . ' ' . str_replace('-', ' ', (string) $e['k'])) . ' ';
            foreach ((array) $e['crops'] as $crop) {
                try {
                    $rows = VarietyBook::rows((string) $crop);
                } catch (\Throwable $ex) {
                    $rows = [];
                }
                $hits = [];
                foreach ($rows as $r) {
                    foreach ((array) $r['ratings'] as $label => $code) {
                        $label = mb_strtolower((string) $label);
                        if (! isset($rank[$code]) || mb_strlen($label) < 4 || ! str_contains($name, ' ' . $label . ' ')) {
                            continue;
                        }
                        // "leaf blight" is not "bacterial leaf blight", nor "blight" "sheath blight".
                        if (preg_match('/(bacterial|sheath|late|early|southern)\s+' . preg_quote($label, '/') . '/u', $name) && ! str_contains($label, ' ')) {
                            continue;
                        }
                        $hits[$r['name']] = [$r['name'], $r['aliases'][0] ?? null, $code, $r['year'] ?? null, $rank[$code]];
                    }
                }
                if (! $hits) {
                    continue;
                }
                usort($hits, fn ($a, $b) => [$b[4], $b[3] ?? 0] <=> [$a[4], $a[3] ?? 0]);
                $out[$e['k']][$crop] = array_map(fn ($h) => array_slice($h, 0, 4), array_slice($hits, 0, $limit));
            }
        }

        return $out;
    }

    /**
     * Everything the in-app Nutrient Deficiency Finder and its catalogue need
     * (2026-10-16): one entry a crop and a nutrient, its photo, and only the
     * fertilizers sold here this page can show, per nutrient and crop.
     */
    public static function deficiencies(): array
    {
        $D = DeficiencyCatalogue::class;
        $entries = [];
        $cards = [];
        $byNc = [];
        foreach ($D::entries() as $slug => $e) {
            $ph = $D::photo($slug, $e['nutrient']);
            $entries[] = [
                'k' => $slug, 'name' => $e['name'], 'local' => (string) ($e['local'] ?? ''), 'kind' => $e['nutrientName'], 'n' => $e['nutrient'], 'sym' => $e['symbol'],
                'hue' => $e['hue'], 'crops' => [$e['crop']], 'where' => $e['where'], 'signs' => $e['signs'],
                'hint' => (string) ($e['hint'] ?? ''), 'symptoms' => (string) ($e['symptoms'] ?? ''), 'stage' => (string) ($e['stage'] ?? ''),
                'causes' => (string) ($e['causes'] ?? ''), 'confuse' => (string) ($e['confuse'] ?? ''), 'confirm' => (string) ($e['confirm'] ?? ''),
                'fix' => (string) ($e['fix'] ?? ''), 'prevent' => (string) ($e['prevent'] ?? ''), 'materials' => $e['materials'],
                'ranks' => (object) [$e['crop'] => $e['rank']],
                // The list's small picture: the crop's own photo, or the nearest crop's with its name on it (2026-10-28, the
                // owner: "look for a same deficiency on a different crop but nearest to the family of that crop ... but have a
                // disclaimer that it is a different crop"). `note` says so under it and when it is shown large.
                'thumb' => $ph && (empty($ph['standin']) || ($ph['kin'] ?? 0) >= 1) ? SitePages::img($ph['thumb'] ?? $ph['src']) : null, 'photo' => $ph ? SitePages::img($ph['src']) : null,
                'alt' => (string) ($ph['alt'] ?? $e['name']), 'credit' => trim((string) ($ph['credit'] ?? '')) ?: null, 'standin' => ! empty($ph['standin']),
                'near' => ! empty($ph['standin']) && ($ph['kin'] ?? 0) >= 1 ? (($ph['shows'] ?? '') !== '' ? $D::plantName((string) $ph['shows']) : 'another crop') : null, 'note' => $ph['note'] ?? null,
                'sources' => self::sources(array_map(fn ($x) => ['label' => $x['title'] ?? ($x['label'] ?? ''), 'url' => $x['url'] ?? null], $e['sources'])),
            ];
            if (! isset($byNc[$e['nutrient']][$e['crop']])) {
                $ps = $D::productsFor($e['nutrient'], $e['crop'], 6);
                $byNc[$e['nutrient']][$e['crop']] = array_column($ps, 'key');
                foreach ($ps as $p) {
                    $cards[$p['key']] ??= [
                        'key' => $p['key'], 'brand' => $p['brand'], 'company' => $p['company'] ?? '', 'kind' => $p['kind'] ?? '', 'fert' => true,
                        'grade' => $p['grade'] ?? '', 'form' => $p['form'] ?? '', 'use' => $p['use'] ?? '', 'note' => $p['note'] ?? '',
                        'nutrients' => array_values(array_map(fn ($n) => $D::NUTRIENTS[$n][0] ?? $n, (array) ($p['nutrients'] ?? []))),
                        'crops' => array_values((array) ($p['crops'] ?? [])),
                    ];
                }
            }
        }
        $crops = $D::crops();
        $kinds = collect($entries)->countBy('n');

        return [
            'section' => $D::SECTION,
            'entries' => $entries,
            'crops' => $crops,
            'cropNames' => self::cropNames($crops),
            'where' => $D::WHERE,
            'signs' => $D::SIGNS,
            // The nutrient filter: [key, name, symbol, how many], N P K first.
            'facets' => collect($D::NUTRIENTS)->filter(fn ($x, $k) => isset($kinds[$k]))->map(fn ($x, $k) => [$x[0], $x[0], $x[1], $kinds[$k]])->values()->all(),
            'nutrients' => collect($D::NUTRIENTS)->map(fn ($x) => ['name' => $x[0], 'sym' => $x[1], 'tl' => $x[2], 'mobile' => $x[3], 'hue' => $x[4], 'role' => $x[5]])->all(),
            'kinds' => $D::KINDS,
            'products' => ['cards' => $cards, 'nc' => $byNc],
        ];
    }

    /** Everything the in-app weed catalogue needs (the helper reads its plans itself). */
    public static function weeds(): array
    {
        $pages = SitePages::inSection('weeds');
        $live = $pages->keyBy('slug');
        $entries = [];
        $used = [];
        $hue = ['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38];
        foreach (FieldCatalogue::weeds() as $slug => $w) {
            $page = $live->get($slug);
            [$photo, $credit, $thumb, $alt] = self::photo('weeds', $slug, $page, true);
            foreach ($w['crops'] as $c) {
                $used[$c] = ($used[$c] ?? 0) + 1;
            }
            $entries[] = [
                'k' => $slug, 'name' => $w['name'], 'sci' => $w['sci'], 'local' => $w['local'], 'kind' => $w['group'],
                'hue' => $hue[$w['group']] ?? 98, 'life' => $w['life'], 'crops' => $w['crops'], 'hint' => $w['hint'],
                'thumb' => $thumb, 'photo' => $photo ?: $thumb, 'alt' => $alt ?: $w['name'], 'credit' => $credit,
                'sources' => self::sources($w['sources'] ?? []),
                // How to keep it out, and the ingredients whose Philippine labels name it (2026-10-18).
                'stop' => $w['stop'] ?? '', 'named' => $w['named'] ?? [],
            ];
        }
        $crops = collect(FieldCatalogue::crops())->filter(fn ($c, $k) => isset($used[$k]))->map(fn ($c, $k) => [...$c, $used[$k]])->all();
        $groups = collect($entries)->countBy('kind');

        return [
            'section' => 'weeds',
            'entries' => $entries,
            'crops' => $crops,
            'facets' => collect(WeedControl::GROUPS)->map(fn ($g, $k) => [$k, $g['label'], null, $groups[$k] ?? 0])->values()->all(),
            'groups' => WeedControl::GROUPS,
            'cropNames' => self::cropNames($crops),
            'products' => self::weedProducts(array_keys($crops)),
            'mobility' => AiMobility::payload(),
        ];
    }

    /* ---- Products sold here (App\Support\ProductBook) -------------------
       Only the products this page can show, worked out here so the page
       never carries the whole list: under each active ingredient, per crop
       ("*" for no crop in mind), and for a pest or disease the products
       whose label names it. Same matching as ProductBook::forIngredient and
       forProblem (the file's order, the crop on the label, the weed group
       for a herbicide), from one index instead of a pass per question. */

    /**
     * What the registration list and our research add to one entry for the
     * finders (2026-10-19): the registered ingredients by crop (aiBy, best
     * first), the ones not advised (avoid), prevention, natural enemies, when
     * to act, the rotation line, and for a virus the pests that carry it
     * (vec: {k, name, local, ai: crop => the pest's own list on that crop}).
     */
    private static function advice(array $e): array
    {
        // A caution (row[5]) repeats on every crop: each entry sends its own once, in whys, and the rows say which.
        $whys = [];
        $at = [];
        $pack = function (array $rows) use (&$whys, &$at): array {
            return array_map(function ($r) use (&$whys, &$at) {
                $w = (string) ($r[5] ?? '');
                if ($w !== '' && ! isset($at[$w])) {
                    $at[$w] = count($whys);
                    $whys[] = $w;
                }
                $r[5] = $w === '' ? -1 : $at[$w];

                return $r;
            }, array_values($rows));
        };
        $vec = [];
        foreach ((array) ($e['vector'] ?? []) as $slug) {
            $v = FieldCatalogue::problem('pests', (string) $slug);
            if (! $v) {
                continue;
            }
            $by = [];
            foreach ((array) $e['crops'] as $c) {
                if (in_array($c, $v['crops'], true)) {
                    $by[$c] = $pack(FieldCatalogue::aiFor($v, $c));
                }
            }
            $vec[] = ['k' => (string) $slug, 'name' => $v['name'], 'local' => $v['local'], 'ai' => (object) $by, 'any' => $pack(FieldCatalogue::aiFor($v, null))];
        }
        $aiBy = array_map($pack, (array) ($e['aiBy'] ?? []));

        return ['aiBy' => (object) $aiBy, 'whys' => $whys, 'avoid' => array_values((array) ($e['avoid'] ?? [])),
            'prevent' => array_values((array) ($e['prevent'] ?? [])), 'bio' => array_values((array) ($e['bio'] ?? [])),
            'threshold' => (string) ($e['threshold'] ?? ''), 'rotate' => (string) ($e['rotate'] ?? ''), 'vec' => $vec];
    }

    /**
     * Pests or diseases: {cards, pi: "p:slug" or "d:slug" => crop => ingredient
     * => keys (products whose label names that very entry), ip: ingredient =>
     * crop => keys (the ingredient on the crop's labels, for an entry no label
     * names), pp: slug => crop => keys (every product labelled for it)}.
     * Philippine companies first everywhere (ProductBook). A virus's vectors
     * get their own lists too, so its card can show what controls them.
     */
    private static function problemProducts(string $section, array $entries): array
    {
        // Kept six hours, and anew whenever the product book or an entry's lists change (a deploy, a sync).
        $stamp = md5((string) @filemtime(database_path('field-catalogue/products.json')) . json_encode(array_map(fn ($e) => [$e['k'], $e['crops'], $e['ai'], $e['aiBy'] ?? null, $e['vec'] ?? null], $entries)));
        try {
            return \Illuminate\Support\Facades\Cache::remember('field-products:v1:' . $section . ':' . $stamp, 21600, fn () => self::problemProductsNow($section, $entries));
        } catch (\Throwable $e) {
            return self::problemProductsNow($section, $entries);
        }
    }

    private static function problemProductsNow(string $section, array $entries): array
    {
        $ip = [];
        $pi = [];
        $pp = [];
        $sold = function (string $sec, string $slug, string $c, array $rows) use (&$ip, &$pi) {
            $crop = $c === '*' ? null : $c;
            foreach ($rows as $a) {
                $name = (string) ($a[0] ?? '');
                $n = ProductBook::norm($name);
                if ($name === '' || isset($pi[$sec[0] . ':' . $slug][$c][$n])) {
                    continue;
                }
                $keys = ProductBook::keysForProblemIngredient($sec, $slug, $crop, $name, 3);
                if ($keys) {
                    $pi[$sec[0] . ':' . $slug][$c][$n] = $keys;
                } elseif (! array_key_exists($c, $ip[$n] ?? [])) {
                    // "Thiamethoxam (seed treatment)" is sold as thiamethoxam: the bracket note is how, not what.
                    $plain = trim((string) preg_replace('/\s*\([^)]*\)/', '', $name));
                    $ip[$n][$c] = ProductBook::keysForIngredient($plain !== '' ? $plain : $name, $crop, null, 3);
                }
            }
        };
        foreach ($entries as $e) {
            foreach (array_merge($e['crops'], ['*']) as $c) {
                $sold($section, $e['k'], $c, FieldCatalogue::aiFor($e, $c === '*' ? null : $c));
                $pp[$e['k']][$c] = ProductBook::keysForProblem($section, $e['k'], $c === '*' ? null : $c, 6);
            }
            foreach ($e['vec'] ?? [] as $v) {
                foreach ((array) $v['ai'] as $c => $rows) {
                    $sold('pests', $v['k'], (string) $c, $rows);
                }
                $sold('pests', $v['k'], '*', $v['any']);
            }
        }

        return self::packProducts(['ip' => $ip, 'pi' => $pi, 'pp' => $pp]);
    }

    /**
     * Weeds: {cards, ip: ingredient => crop => weed group => keys (up to
     * twelve; the page takes four after matching every group seen), wp:
     * weed group => crop => keys (the herbicides whose label names it)}.
     */
    private static function weedProducts(array $crops): array
    {
        $ip = [];
        foreach (FieldCatalogue::weedPlans() as $plan) {
            foreach ($plan['crops'] as $c) {
                foreach ($plan['windows'] as $w) {
                    foreach (['grasses', 'sedges', 'broadleaves'] as $g) {
                        foreach ($w[$g] ?? [] as $ai) {
                            $n = ProductBook::norm($ai);
                            if (! isset($ip[$n][$c][$g])) {
                                // "Pendimethalin (corn only)" is sold as pendimethalin: the bracket note is how, not what.
                                $ip[$n][$c][$g] = self::herbFor($ai, $c, $g, 12);
                            }
                        }
                    }
                }
            }
        }
        // The ingredients a weed's own labels name, on the crops they name (the catalogue's detail).
        foreach (FieldCatalogue::weeds() as $wd) {
            $g = (string) ($wd['group'] ?? '');
            foreach ((array) ($wd['named'] ?? []) as $nm) {
                $ai = (string) ($nm[0] ?? '');
                foreach ((array) ($nm[2] ?? []) as $c) {
                    $n = ProductBook::norm($ai);
                    if ($ai !== '' && $g !== '' && ! isset($ip[$n][$c][$g])) {
                        $ip[$n][$c][$g] = self::herbFor($ai, (string) $c, $g, 12);
                    }
                }
            }
        }
        $wp = [];
        $all = ProductBook::all();
        foreach (array_keys(WeedControl::GROUPS) as $g) {
            foreach (array_merge($crops, ['*']) as $c) {
                $keys = [];
                foreach ($all as $k => $p) {
                    if (($p['kind'] ?? '') === 'herbicide' && in_array($g, $p['weeds'] ?? [], true) && ($c === '*' || in_array($c, $p['crops'] ?? [], true))) {
                        $keys[] = $k;
                        if (count($keys) >= 6) {
                            break;
                        }
                    }
                }
                $wp[$g][$c] = $keys;
            }
        }

        return self::packProducts(['ip' => $ip, 'wp' => $wp]);
    }

    /** Ingredient (as ProductBook::norm writes it) => product keys, in the file's order. */
    private static function prodIndex(): array
    {
        static $idx = null;
        if ($idx === null) {
            $idx = [];
            foreach (ProductBook::all() as $k => $p) {
                foreach ($p['app_ai'] ?? [] as $ai) {
                    $idx[ProductBook::norm((string) $ai)][$k] = true;
                }
            }
            $idx = array_map('array_keys', $idx);
        }

        return $idx;
    }

    /**
     * The herbicides sold for one of the weed helper's ingredient names, as
     * keys (2026-10-18): the bracket note dropped and the parts of a mix in
     * any order ("Penoxsulam plus cyhalofop butyl" is the product made of
     * cyhalofop butyl and penoxsulam), on the crop and the weed group its
     * label names, in ProductBook's order (Philippine companies first).
     */
    private static function herbFor(string $ai, ?string $crop, ?string $group, int $max): array
    {
        static $idx = null;
        if ($idx === null) {
            $idx = [];
            foreach (ProductBook::all() as $k => $p) {
                if (($p['kind'] ?? '') !== 'herbicide') {
                    continue;
                }
                $names = (array) ($p['app_ai'] ?? []);
                $names[] = implode(' plus ', (array) ($p['ai'] ?? []));
                foreach ($names as $n) {
                    $idx[self::mixKey((string) $n)][$k] = true;
                }
            }
            $idx = array_map('array_keys', $idx);
        }
        $all = ProductBook::all();
        $out = [];
        foreach ($idx[self::mixKey($ai)] ?? [] as $k) {
            $p = $all[$k];
            if ($crop && ! in_array($crop, $p['crops'] ?? [], true)) {
                continue;
            }
            if ($group && ! in_array($group, $p['weeds'] ?? [], true)) {
                continue;
            }
            $out[] = $k;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /** A mix's parts, bracket note dropped, each as ProductBook::norm writes it, sorted: one key whatever the order. */
    private static function mixKey(string $ai): string
    {
        $parts = preg_split('/\s+plus\s+|\s*\+\s*/i', self::bare($ai)) ?: [$ai];
        $parts = array_map(fn ($x) => ProductBook::norm($x), $parts);
        sort($parts);

        return implode(' + ', $parts);
    }

    /** An ingredient name without its bracket note: "Glyphosate (directed)" is glyphosate. */
    private static function bare(string $ai): string
    {
        $b = trim((string) preg_replace('/\s*\([^)]*\)/', '', $ai));

        return $b !== '' ? $b : $ai;
    }

    /** ProductBook::forIngredient, as keys. */
    private static function prodsFor(string $ai, ?string $crop, ?string $group, int $max): array
    {
        $all = ProductBook::all();
        $out = [];
        foreach (self::prodIndex()[ProductBook::norm($ai)] ?? [] as $k) {
            $p = $all[$k];
            if ($crop && ! in_array($crop, $p['crops'] ?? [], true)) {
                continue;
            }
            if ($group && ($p['kind'] ?? '') === 'herbicide' && ! in_array($group, $p['weeds'] ?? [], true)) {
                continue;
            }
            $out[] = $k;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /** ProductBook::forProblem, as keys. */
    private static function prodsForProblem(string $section, string $slug, ?string $crop, int $max): array
    {
        static $idx = [];
        $field = $section === 'diseases' ? 'diseases' : 'pests';
        if (! isset($idx[$field])) {
            $idx[$field] = [];
            foreach (ProductBook::all() as $k => $p) {
                foreach ($p[$field] ?? [] as $s) {
                    $idx[$field][$s][] = $k;
                }
            }
        }
        $all = ProductBook::all();
        $out = [];
        foreach ($idx[$field][$slug] ?? [] as $k) {
            if ($crop && ! in_array($crop, $all[$k]['crops'] ?? [], true)) {
                continue;
            }
            $out[] = $k;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /**
     * The lists without their empty corners, and a card for every product
     * they name (ProductBook::card, with the crops its label names).
     */
    private static function packProducts(array $lists): array
    {
        $used = [];
        $prune = function ($x) use (&$prune, &$used) {
            if (array_is_list($x)) {
                foreach ($x as $k) {
                    $used[$k] = true;
                }

                return $x ?: null;
            }
            $out = [];
            foreach ($x as $k => $v) {
                $v = $prune($v);
                if ($v !== null) {
                    $out[$k] = $v;
                }
            }

            return $out ?: null;
        };
        foreach ($lists as $name => $l) {
            $lists[$name] = (object) ($prune($l) ?? []);
        }
        $all = ProductBook::all();
        $names = FieldCatalogue::crops();
        $cards = [];
        foreach (array_keys($used) as $k) {
            if (isset($all[$k])) {
                $cards[$k] = ProductBook::card($all[$k]) + ['crops' => array_values(array_unique(array_map(
                    fn ($c) => $names[$c][0] ?? ucfirst(str_replace('_', ' ', $c)), $all[$k]['crops'] ?? []))),
                    // How its active ingredients move in the plant: systemic, translaminar, contact.
                    'mob' => AiMobility::ofList($all[$k]['ai'] ?? [])['c'] ?? []];
            }
        }

        return ['cards' => (object) $cards] + $lists;
    }

    /** Crop names for the detail's chips: key => [local, English, icon]. */
    private static function cropNames(array $crops): array
    {
        return collect($crops)->map(fn ($c) => [$c[0], $c[1], $c[2]])->all();
    }

    /** The sources an entry names, with a web address each (or none). */
    private static function sources(array $list): array
    {
        // Never an agency, a state university or a research institute (GovSource, the owner 2026-10-17).
        return collect(GovSource::keep($list))->filter(fn ($s) => is_array($s) && ! empty($s['label']))
            ->map(fn ($s) => ['label' => (string) $s['label'], 'url' => preg_match('#^https?://#', (string) ($s['url'] ?? '')) ? $s['url'] : null])->values()->all();
    }

    /**
     * An entry's picture: its profile page's hero, else the fact sheet's
     * photo (database/field-catalogue/photos.json), with its credit, since
     * most are shared under a licence that asks for it. Returns [big,
     * credit], or with $all [big, credit, thumb, alt].
     */
    private static function photo(string $section, string $slug, $page, bool $all = false): array
    {
        $h = $page && is_array($page->heroImage) ? $page->heroImage : (FieldCatalogue::photo($section, $slug) ?? []);
        $src = (string) ($h['src'] ?? '');
        $thumb = (string) ($h['thumb'] ?? '');
        if ($thumb === '' && str_starts_with($src, '/images/')) {
            $twin = preg_replace('/\.(jpe?g|png|webp)$/i', '-480.webp', $src);
            $thumb = $twin && is_file(public_path(ltrim($twin, '/'))) ? $twin : $src;
        }
        $big = SitePages::img($src);
        $credit = trim((string) ($h['credit'] ?? '')) ?: null;

        return $all ? [$big, $credit, SitePages::img($thumb ?: $src), $h['alt'] ?? null] : [$big, $credit];
    }
}
