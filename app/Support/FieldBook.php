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
            ];
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
                // The list's small picture is the crop's own photo only; the entry shows a stand in, said to be one.
                'thumb' => $ph && empty($ph['standin']) ? SitePages::img($ph['thumb'] ?? $ph['src']) : null, 'photo' => $ph ? SitePages::img($ph['src']) : null,
                'alt' => (string) ($ph['alt'] ?? $e['name']), 'credit' => trim((string) ($ph['credit'] ?? '')) ?: null, 'standin' => ! empty($ph['standin']),
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

    /** Pests or diseases: {cards, ip: ingredient => crop => keys, pp: slug => crop => keys}. */
    private static function problemProducts(string $section, array $entries): array
    {
        $ip = [];
        $pp = [];
        foreach ($entries as $e) {
            foreach (array_merge($e['crops'], ['*']) as $c) {
                foreach ($e['ai'] as $a) {
                    $n = ProductBook::norm((string) ($a[0] ?? ''));
                    if (! array_key_exists($c, $ip[$n] ?? [])) {
                        $ip[$n][$c] = self::prodsFor((string) $a[0], $c === '*' ? null : $c, null, 4);
                    }
                }
                $pp[$e['k']][$c] = self::prodsForProblem($section, $e['k'], $c === '*' ? null : $c, 6);
            }
        }

        return self::packProducts(['ip' => $ip, 'pp' => $pp]);
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
                                $ip[$n][$c][$g] = self::prodsFor($ai, $c, $g, 12);
                            }
                        }
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
