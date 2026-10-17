<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The in-app Variety Finder's data (2026-10-14; not App\Support\VarietyBook,
 * which picks records for the analyses): every variety of rice
 * (App\Support\RiceVarieties) and of every other crop
 * (App\Support\CropVarieties) in one shape, read from the same files the
 * public finders and pages read, so the app never drifts from them. The
 * finder draws its cards, its filters and its detail sheet from this; it
 * never links to the public variety pages.
 *
 * One variety: s slug, n name, al other names, ln local name, y year, o
 * breeder or owner, reg registration, k kind, hy hybrid, gl glutinous, nw
 * new, d days (or months) to harvest, du unit, dt the registry's own words,
 * ya ym yield (average, top), yu unit, yt yield words, yx yield by season or
 * region, h hu height, res ratings [label, code, words, r|m|s], ok what it
 * holds up against (rice), grain [label, value, class, percent], traits
 * [label, value], env farms (rice), where, dom (luzon visayas mindanao),
 * sea seasons, est how it is planted, el elevation, rem remarks, uses,
 * notes {popular, facts, claims}, chip its type, q its search words.
 */
final class VarietyFinder
{
    /** Rating codes in words, and their colour: r holds up, m middling, s weak. */
    public const RATINGS = [
        'HR' => ['Highly resistant', 'r'], 'R' => ['Resistant', 'r'], 'MR' => ['Moderately resistant', 'r'],
        'HT' => ['Highly tolerant', 'r'], 'T' => ['Tolerant', 'r'], 'MT' => ['Moderately tolerant', 'r'],
        'I' => ['Intermediate', 'm'], 'MS' => ['Moderately susceptible', 's'], 'S' => ['Susceptible', 's'], 'HS' => ['Highly susceptible', 's'],
    ];

    private const GRADE = ['Pr' => 'Premium', 'G1' => 'Grade 1', 'G2' => 'Grade 2', 'G3' => 'Grade 3'];

    private const AMYLOSE = ['VL' => ['Very low', 'cooks very soft and sticky'], 'L' => ['Low', 'cooks soft'], 'l' => ['Low', 'cooks soft'],
        'I' => ['Intermediate', 'cooks soft but not sticky'], 'H' => ['High', 'cooks firm and fluffy']];

    private const LENGTH = ['Sh' => 'Short', 'S' => 'Short', 'M' => 'Medium', 'L' => 'Long', 'EL' => 'Extra long'];

    private const SHAPE = ['S' => 'Slender', 'I' => 'Intermediate', 'B' => 'Bold'];

    private const ESTABLISH = ['transplanted' => 'Transplanted', 'wet direct seeded' => 'Wet direct seeded', 'dry direct seeded' => 'Dry direct seeded'];

    /** A crop's shelf and icon when the crop catalog has no twin for it (crops.json group). */
    private const GROUP_FALLBACK = [
        'cereal' => ['corn', '🌾'], 'fruitveg' => ['vegetables', '🌱'], 'cucurbit' => ['vegetables', '🥒'], 'beanveg' => ['legumes', '🫛'],
        'leafy' => ['vegetables', '🥬'], 'bulb' => ['roots', '🧅'], 'rootcrop' => ['roots', '🍠'], 'legume' => ['legumes', '🫘'],
        'industrial' => ['industrial', '🌿'], 'plantation' => ['fruits', '🌴'], 'fruit' => ['fruits', '🌳'],
    ];

    /**
     * Every crop with varieties, rice first, in the shape the crop tag
     * (public.site.partials.crop-pick) takes: key => [local, English, icon,
     * shelf, count], shelf by shelf in the field catalogue's order.
     */
    public static function crops(): array
    {
        $book = CropVarieties::book();
        $rows = ['rice' => ['Palay', 'Rice', '🌾', 'rice', count(RiceVarieties::all())]];
        foreach (CropVarieties::crops() as $k => $c) {
            $meta = $book['crops'][$k] ?? [];
            $twin = $meta['catalogue'][0] ?? null;
            [$fbShelf, $fbIcon] = self::GROUP_FALLBACK[$meta['group'] ?? ''] ?? ['fruits', '🌱'];
            $icon = $twin ? (CropCatalog::CROPS[$twin]['icon'] ?? $fbIcon) : $fbIcon;
            $shelf = $twin && isset(CropCatalog::CROPS[$twin]) ? FieldCatalogue::shelfOf($twin) : $fbShelf;
            // The first of its local names: "sitaw", not "sitaw, string beans".
            $local = trim(explode(',', (string) ($meta['local'] ?? ''))[0]);
            $name = (string) ($c['name'] ?? ucfirst($k));
            $rows[$k] = [$local !== '' ? Str::ucfirst($local) : $name, $name, $icon, $shelf, (int) $c['count']];
        }
        $order = array_flip(array_keys(FieldCatalogue::SHELVES));
        uksort($rows, fn ($a, $b) => [($order[$rows[$a][3]] ?? 99), -$rows[$a][4], $rows[$a][1]] <=> [($order[$rows[$b][3]] ?? 99), -$rows[$b][4], $rows[$b][1]]);

        return $rows;
    }

    /** The crop a key names, or rice. */
    public static function cropKey(?string $key): string
    {
        $key = strtolower(trim((string) $key));
        if ($key === '' || str_starts_with($key, 'rice')) {
            return 'rice';
        }
        if (str_starts_with($key, 'corn')) {
            return 'corn';
        }

        return isset(self::crops()[$key]) ? $key : 'rice';
    }

    /** One crop's finder: the crop's own words, its filters and its varieties. */
    public static function book(string $crop): array
    {
        $crop = self::cropKey($crop);
        $row = self::crops()[$crop];
        if ($crop === 'rice') {
            $items = array_map([self::class, 'rice'], RiceVarieties::all());
            $meta = ['harvest' => 'days from sowing to harvest'];
        } else {
            $items = array_map([self::class, 'crop'], CropVarieties::forCrop($crop));
            $meta = CropVarieties::book()['crops'][$crop] ?? [];
        }
        // What each variety's registration trials showed (2026-10-18), and the registered varieties only the trials hold.
        $items = self::withTrials($items, $crop);
        $units = array_count_values(array_filter(array_column($items, 'du')));
        arsort($units);

        return [
            'crop' => ['key' => $crop, 'local' => $row[0], 'name' => $row[1], 'icon' => $row[2], 'count' => count($items),
                'unit' => array_key_first($units) ?? 'days', 'harvest' => (string) ($meta['harvest'] ?? 'days to harvest'),
                'source' => $crop === 'rice' ? 'anee.io\'s variety records' : 'anee.io\'s variety records, and seed sellers for the few never registered'],
            'filters' => $crop === 'rice' ? self::riceFilters($items) : self::cropFilters($crop, $items),
            'items' => array_map([self::class, 'lean'], $items),
        ];
    }

    /** Stress names as the finder shows them. */
    private const STRESS = ['salinity' => 'Salt', 'submergence' => 'Flooding', 'drought' => 'Drought', 'cold' => 'Cold', 'heat' => 'Heat', 'lodging' => 'Lodging', 'acidity' => 'Acid soil'];

    /**
     * The finder's varieties with their registration trials joined on
     * (`tr`, App\Support\VarietyRegistry) and a figure the record lacks
     * filled from them; then the registered varieties only the trials
     * hold, in the same shape. Never a source in what it shows.
     */
    private static function withTrials(array $items, string $crop): array
    {
        try {
            [$by, $rest] = VarietyRegistry::split($crop);
        } catch (\Throwable $e) {
            report($e);

            return $items;
        }
        if (! $by && ! $rest) {
            return $items;
        }
        foreach ($items as &$it) {
            $t = $by[$it['n']] ?? null;
            if (! $t) {
                continue;
            }
            $it['tr'] = self::trialView($t, $it);
            if ($it['d'] === null && ! empty($t['days'])) {
                $it['d'] = (int) round($t['days']);
                $it['du'] = $it['du'] ?: 'days';
            }
            if ($it['ya'] === null && ! empty($t['yieldAvg']) && (($t['facts']['yieldUnit'] ?? 't/ha') === 't/ha')) {
                $it['ya'] = (float) $t['yieldAvg'];
                $it['ym'] ??= ! empty($t['yieldTop']) ? (float) $t['yieldTop'] : null;
                $it['yu'] = $it['yu'] ?: 't/ha';
                $it['yt'] = null;
            }
            if (! $it['res']) {
                $it['res'] = self::trialRatings($t);
            }
        }
        unset($it);
        foreach ($rest as $t) {
            $items[] = self::trialOnly($t, $crop);
        }

        return $items;
    }

    /** The ratings of a trial row in the finder's shape. */
    private static function trialRatings(array $t): array
    {
        $out = [];
        foreach ((array) ($t['facts']['ratings'] ?? []) as $label => $code) {
            if (is_string($code) && ($r = self::rating(Str::ucfirst((string) $label), $code))) {
                $out[] = $r;
            }
        }

        return $out;
    }

    /** What the detail sheet shows of the trials: plain words and numbers. */
    private static function trialView(array $t, ?array $it = null): array
    {
        $f = (array) ($t['facts'] ?? []);
        $unit = (string) ($f['yieldUnit'] ?? 't/ha');
        $yb = (array) ($f['yieldBy'] ?? []);
        $db = (array) ($f['daysBy'] ?? []);
        $sea = [];
        foreach (['dry' => 'Dry season', 'wet' => 'Wet season'] as $k => $label) {
            if (isset($yb[$k]) || isset($db[$k])) {
                $sea[] = [$label, isset($yb[$k]) ? (float) $yb[$k] : null, isset($db[$k]) ? (int) round($db[$k]) : null];
            }
        }
        foreach ((array) ($yb['islands'] ?? []) as $isl => $v) {
            if (is_numeric($v)) {
                $sea[] = [(string) $isl, (float) $v, null];
            }
        }
        $adv = [];
        foreach (array_slice((array) ($f['advantage'] ?? []), 0, 3) as $a) {
            if (isset($a['pct'])) {
                $adv[] = self::num(abs((float) $a['pct'])) . '% ' . ((float) $a['pct'] >= 0 ? 'more' : 'less') . ' than ' . ($a['over'] ?? 'the check variety')
                    . (! empty($a['season']) && $a['season'] !== 'across' ? ' in the ' . $a['season'] . ' season' : '');
            }
        }
        $te = (array) ($f['tested'] ?? []);
        $tried = trim(implode(', ', array_filter([
            ! empty($te['seasons']) ? $te['seasons'] . ' ' . ((int) $te['seasons'] === 1 ? 'season' : 'seasons') : null,
            ! empty($te['locations']) ? $te['locations'] . ' ' . ((int) $te['locations'] === 1 ? 'site' : 'sites') : null,
            $te['years'] ?? null,
        ])));
        $st = [];
        foreach ((array) ($t['stress'] ?? []) as $k => $w) {
            if (is_string($w) && $w !== '' && isset(self::STRESS[$k])) {
                $st[] = [self::STRESS[$k], Str::ucfirst($w)];
            }
        }
        $diff = null;
        $c = (array) ($t['conflicts'] ?? []);
        if ($it && isset($c['days']['ours'], $c['days']['trials'])) {
            $diff = 'Our record gives ' . self::num($c['days']['ours'], 0) . ' days; its registration trials averaged ' . self::num($c['days']['trials'], 0) . '.';
        } elseif ($it && isset($c['yield']['ours'], $c['yield']['trials'])) {
            $diff = 'Our record gives ' . self::num($c['yield']['ours'], 2) . ' t/ha; its registration trials averaged ' . self::num($c['yield']['trials'], 2) . '.';
        }

        return array_filter([
            'sea' => $sea,
            'u' => $unit,
            'avg' => isset($t['yieldAvg']) ? (float) $t['yieldAvg'] : null,
            'best' => isset($t['yieldTop']) ? (float) $t['yieldTop'] : null,
            'd' => isset($t['days']) ? (int) round($t['days']) : null,
            'h' => isset($t['heightCm']) ? (int) round($t['heightCm']) : null,
            'adv' => $adv,
            'tried' => $tried !== '' ? $tried : null,
            'st' => $st,
            'fit' => in_array($t['seasonFit'] ?? '', ['wet', 'dry'], true) ? 'Does better in the ' . $t['seasonFit'] . ' season' : null,
            'tra' => array_values(array_slice((array) ($f['traits'] ?? []), 0, 6)),
            'lim' => array_values(array_slice((array) ($f['limits'] ?? []), 0, 4)),
            'areas' => isset($f['areas']) ? self::plain((string) $f['areas']) : null,
            'diff' => $diff,
            'notes' => $t['notes'] ?? null,
        ], fn ($x) => $x !== null && $x !== [] && $x !== '');
    }

    /** A registered variety only the trials hold, as a finder card. */
    private static function trialOnly(array $t, string $crop): array
    {
        $f = (array) ($t['facts'] ?? []);
        $type = (string) ($t['type'] ?? '');
        $hy = str_contains($type, 'hybrid');
        $kind = $crop === 'rice' ? ($hy ? 'Hybrid' : 'Inbred') : ($t['kind'] ? Str::ucfirst((string) $t['kind']) : ($type !== '' ? Str::ucfirst($type) : 'Registered variety'));
        $alias = array_values(array_filter(array_unique((array) ($t['aliases'] ?? []))));
        $islands = (array) ($f['islands'] ?? []);
        $dom = in_array('National', $islands, true) ? ['luzon', 'visayas', 'mindanao'] : array_values(array_map('strtolower', $islands));
        $envs = $crop === 'rice' ? (array) ($t['envs'] ?? []) : [];
        $res = self::trialRatings($t);
        $ok = [];
        if ($crop === 'rice') {
            $keys = ['blast' => 'blast', 'bacterial leaf blight' => 'blb', 'tungro' => 'tungro', 'brown planthopper' => 'bph', 'green leafhopper' => 'glh', 'stem borer' => 'sb', 'sheath blight' => 'shb'];
            foreach ((array) ($f['ratings'] ?? []) as $label => $code) {
                if (isset($keys[$label]) && in_array($code, ['R', 'MR', 'HR'], true)) {
                    $ok[] = $keys[$label];
                }
            }
        }
        $yu = (string) ($f['yieldUnit'] ?? 't/ha');
        $name = (string) $t['name'];
        $slug = 'tr-' . Str::slug($name . ' ' . ($t['localName'] ?? ''));
        $chip = $crop === 'rice' ? ($hy ? 'hybrid' : 'inbred') : ($crop === 'corn' ? ($hy ? 'hybrid' : 'opv') : Str::slug($kind));

        return [
            's' => $slug, 'n' => $name, 'al' => $alias, 'ln' => $t['localName'] ?? null, 'y' => $t['year'] ?? null, 'o' => $t['company'] ?? null,
            'reg' => $crop === 'rice' ? null : ($t['regNo'] ?? null),
            'k' => $kind, 'hy' => $hy, 'gl' => ! empty($f['grain']['glutinous']), 'nw' => false,
            'd' => isset($t['days']) ? (int) round($t['days']) : null, 'du' => (string) ($f['daysUnit'] ?? 'days'), 'dt' => null,
            'ya' => isset($t['yieldAvg']) ? (float) $t['yieldAvg'] : null, 'ym' => isset($t['yieldTop']) ? (float) $t['yieldTop'] : null, 'yu' => $yu, 'yt' => null, 'yx' => [],
            'h' => isset($t['heightCm']) ? (float) $t['heightCm'] : null, 'hu' => 'cm',
            'res' => $res, 'ok' => $ok, 'grain' => [], 'traits' => [],
            'env' => $envs,
            'envL' => array_values(array_filter(array_map(fn ($e) => RiceVarieties::ENVIRONMENTS[$e][0] ?? null, $envs))),
            'where' => in_array('National', $islands, true) ? ['Nationwide'] : $islands, 'dom' => $dom,
            'sea' => in_array($t['seasonFit'] ?? '', ['wet', 'dry'], true) ? [Str::ucfirst($t['seasonFit']) . ' season'] : [],
            'est' => [], 'el' => [], 'rem' => null, 'uses' => null, 'notes' => null,
            'chip' => $chip,
            'tr' => self::trialView($t),
            'q' => strtolower(implode(' ', array_filter([$name, str_replace([' ', '-'], '', $name), implode(' ', $alias), str_replace([' ', '-'], '', implode(' ', $alias)),
                $t['localName'] ?? '', $t['company'] ?? '', $t['regNo'] ?? '', $kind]))),
        ];
    }

    /** A variety without its empty facts (null, false, nothing): the page reads a missing key as none. */
    private static function lean(array $v): array
    {
        return array_filter($v, fn ($x) => $x !== null && $x !== false && $x !== [] && $x !== '');
    }

    /** A breeder as the finder shows it: a seed company, never a government body, state university or institute (the owner, 2026-10-18). */
    private static function breeder(?string $b): ?string
    {
        $b = trim((string) $b);

        return $b === '' || GovSource::isGov($b) || preg_match('/\b(?:universit|college|institute|bureau|department|council|station|center|centre)\b/i', $b) ? null : $b;
    }

    /** Words from the files for the screen: no semicolons in what a farmer reads. */
    private static function plain(string $s): string
    {
        return trim(str_replace(['; ', ';'], [', ', ','], $s));
    }

    /** Every variety's names, for the catalogue's search: [crop, slug, name, other names, more words (the breeder)]. */
    public static function index(): array
    {
        $out = [];
        foreach (RiceVarieties::all() as $v) {
            $out[] = ['rice', $v['slug'], $v['name'], trim(implode(', ', array_filter(array_merge([$v['localName'] ?? ''], $v['tradeNames'] ?? [])))), (string) ($v['breeder'] ?? '')];
        }
        foreach (CropVarieties::all() as $v) {
            $out[] = [$v['crop'], $v['slug'], $v['name'], trim(implode(', ', array_filter(array_merge([$v['localName'] ?? ''], $v['aliases'] ?? [])))), (string) ($v['owner'] ?? '')];
        }

        return $out;
    }

    /* ---------------------------------------------------------------- */

    private static function num($x, int $d = 1): string
    {
        return rtrim(rtrim(number_format((float) $x, $d, '.', ''), '0'), '.');
    }

    private static function rating(string $label, ?string $code): ?array
    {
        $code = strtoupper(trim((string) $code));

        return isset(self::RATINGS[$code]) ? [$label, $code, self::RATINGS[$code][0], self::RATINGS[$code][1]] : null;
    }

    /** One rice variety. */
    private static function rice(array $v): array
    {
        $res = [];
        $ok = [];
        foreach (RiceVarieties::PROBLEMS as $k => [$label]) {
            $c = RiceVarieties::code($v['reactions'][$k] ?? null);
            if ($c && ($r = self::rating($label, $c))) {
                $res[] = $r;
                if (in_array($c, ['R', 'MR'], true)) {
                    $ok[] = $k;
                }
            }
        }
        $g = $v['grain'] ?? [];
        $grain = [];
        if (! empty($g['amylose'])) {
            [$w, $cook] = self::AMYLOSE[$g['amylose'][1] ?? ''] ?? [null, null];
            $grain[] = ['Amylose', self::num($g['amylose'][0]) . '%', $w ? $w . ', ' . $cook : '', null];
        }
        // A bar for the shares where more is better (milling, head rice); chalky grains are better fewer.
        foreach (['milling' => 'Milling recovery', 'head' => 'Head rice', 'chalky' => 'Chalky grains'] as $k => $label) {
            if (! empty($g[$k])) {
                $grain[] = [$label, self::num($g[$k][0]) . '%', self::GRADE[$g[$k][1] ?? ''] ?? '', $k === 'chalky' ? null : (float) $g[$k][0]];
            }
        }
        if (! empty($g['length'])) {
            $grain[] = ['Grain length', self::num($g['length'][0]) . ' mm', self::LENGTH[$g['length'][1] ?? ''] ?? '', null];
        }
        if (! empty($g['shape'])) {
            $grain[] = ['Grain shape', self::num($g['shape'][0]) . ' to 1', self::SHAPE[$g['shape'][1] ?? ''] ?? '', null];
        }
        if (! empty($g['protein'])) {
            $grain[] = ['Protein', self::num($g['protein']) . '%', '', null];
        }
        $where = array_values(array_unique(array_filter(array_merge([$v['domain'] ?? null, $v['location'] ?? null], (array) ($v['regions'] ?? [])))));
        $where = array_map(fn ($w) => $w === 'National' ? 'Nationwide' : $w, $where);
        $trade = array_values((array) ($v['tradeNames'] ?? []));
        $brand = collect(['bayer' => 'arize', 'syngenta' => 'nk', 'long ping' => 'longping lp', 'longping' => 'longping lp', 'sl agritech' => 'sl',
            'leads' => 'jackpot leads', 'bioseed' => 'biorice', 'seedworks' => 'us th', 'pioneer' => 'phb', 'advanta' => 'pac'])
            ->filter(fn ($w, $k) => str_contains(strtolower((string) ($v['breeder'] ?? '')), $k))->implode(' ');

        return [
            's' => $v['slug'], 'n' => $v['name'], 'al' => $trade, 'ln' => $v['localName'] ?? null, 'y' => $v['year'] ?? null, 'o' => self::breeder($v['breeder'] ?? null), 'reg' => null,
            'k' => ($v['type'] ?? '') === 'hybrid' ? 'Hybrid' : 'Inbred', 'hy' => ($v['type'] ?? '') === 'hybrid', 'gl' => ($v['special'] ?? null) === 'glutinous', 'nw' => ! empty($v['new']),
            'd' => ! empty($v['maturity']) ? (int) round($v['maturity']) : null, 'du' => 'days', 'dt' => null,
            'ya' => isset($v['yieldAvg']) ? (float) $v['yieldAvg'] : null, 'ym' => isset($v['yieldMax']) ? (float) $v['yieldMax'] : null, 'yu' => 't/ha', 'yt' => null, 'yx' => [],
            'h' => isset($v['height']) ? (float) $v['height'] : null, 'hu' => 'cm',
            'res' => $res, 'ok' => $ok, 'grain' => $grain, 'traits' => [],
            'env' => array_values((array) ($v['environments'] ?? [])),
            'envL' => array_values(array_filter(array_map(fn ($e) => RiceVarieties::ENVIRONMENTS[$e][0] ?? null, (array) ($v['environments'] ?? [])))),
            'where' => $where, 'dom' => [],
            'sea' => array_values(array_filter(array_map(fn ($s) => ['DS' => 'Dry season', 'WS' => 'Wet season'][$s] ?? null, (array) ($v['seasons'] ?? [])))),
            'est' => array_values(array_filter(array_map(fn ($e) => self::ESTABLISH[strtolower($e)] ?? ucfirst($e), (array) ($v['establishment'] ?? [])))),
            'el' => [], 'rem' => isset($v['remarks']) ? self::plain((string) $v['remarks']) : null, 'uses' => null, 'notes' => null,
            'chip' => ($v['type'] ?? '') === 'hybrid' ? 'hybrid' : 'inbred',
            'q' => strtolower($v['name'] . ' ' . str_replace(' ', '', $v['name']) . ' ' . ($v['localName'] ?? '') . ' ' . (self::breeder($v['breeder'] ?? null) ?? '') . ' ' . implode(' ', $trade)
                . ' ' . str_replace([' ', '-'], '', implode(' ', $trade)) . ' ' . $brand),
        ];
    }

    /** One variety of any other crop. */
    private static function crop(array $v): array
    {
        $kind = self::plain(CropVarieties::kind($v));
        $m = $v['maturity'] ?? null;
        $y = $v['yield'] ?? null;
        $yx = [];
        foreach ((array) ($y['seasons'] ?? []) as $s => $val) {
            if (is_numeric($val)) {
                $yx[] = [['DS' => 'Dry season', 'WS' => 'Wet season'][$s] ?? $s, (float) $val];
            }
        }
        foreach ((array) ($y['regional'] ?? []) as $r) {
            if (is_array($r) && isset($r[1]) && is_numeric($r[1]) && count((array) ($y['regional'] ?? [])) > 1) {
                $yx[] = [(string) $r[0], (float) $r[1]];
            }
        }
        $res = [];
        foreach ((array) ($v['reactions'] ?? []) as $r) {
            if (is_array($r) && ($x = self::rating((string) $r[0], $r[1] ?? null))) {
                $res[] = $x;
            }
        }
        $traits = [];
        foreach ((array) ($v['traits'] ?? []) as $t) {
            if (! is_array($t) || ($t[1] ?? '') === '' || preg_match('/^type$/i', (string) $t[0])) {
                continue;
            }
            $val = (string) $t[1];
            if (is_numeric($val)) {
                $val = (float) $val >= 100 ? number_format((float) $val) : self::num($val, 2);
            }
            $traits[] = [self::plain((string) $t[0]), self::plain($val)];
        }
        $domain = (array) ($v['domain'] ?? []);
        $dom = in_array('National', $domain, true) ? ['luzon', 'visayas', 'mindanao'] : array_values(array_map('strtolower', $domain));
        $r = $v['research'] ?? null;
        $notes = null;
        if (is_array($r)) {
            $f = (array) ($r['facts'] ?? []);
            $facts = [];
            foreach (['maturityDays' => 'Harvest', 'harvest' => 'Harvest', 'yield' => 'Yield', 'fruit' => 'Fruit or grain', 'areas' => 'Where it is grown',
                'season' => 'Season', 'uses' => 'Uses', 'gm' => 'Built in traits'] as $k => $label) {
                if (! empty($f[$k]) && is_string($f[$k]) && ! ($k === 'harvest' && ! empty($f['maturityDays']))) {
                    $facts[] = [$label, self::plain(str_replace(' (Roundup)', '', $f[$k]))];
                }
            }
            $notes = ['popular' => self::plain((string) ($r['popular'] ?? '')), 'by' => (string) (self::breeder($r['company'] ?? null) ?? ''), 'claims' => ! empty($r['claims']),
                'facts' => $facts, 'traits' => array_values(array_map([self::class, 'plain'], array_filter((array) ($f['traits'] ?? []), 'is_string')))];
        }
        $alias = array_values((array) ($v['aliases'] ?? []));
        $chip = ($v['crop'] ?? '') === 'corn' ? CropVarieties::chip($v) : Str::slug($kind);
        $dt = is_array($m) && ! empty($m['text']) && ! is_numeric($m['text']) ? (string) preg_replace('/\bN\.?\s?A\b\.?/', 'not given', (string) $m['text']) : null;

        return [
            's' => $v['slug'], 'n' => $v['name'], 'al' => $alias, 'ln' => $v['localName'] ?? null, 'y' => $v['year'] ?? null, 'o' => self::breeder($v['owner'] ?? null), 'reg' => $v['reg'] ?? null,
            'k' => $kind, 'hy' => str_contains(strtolower($kind), 'hybrid'), 'gl' => false, 'nw' => false,
            'd' => CropVarieties::days($v), 'du' => is_array($m) ? (($m['unit'] ?? 'days') === 'months' ? 'months' : 'days') : null, 'dt' => $dt,
            'ya' => isset($y['value']) && is_numeric($y['value']) ? (float) $y['value'] : null, 'ym' => null, 'yu' => $y['unit'] ?? null,
            'yt' => ! isset($y['value']) && ! empty($y['text']) ? (string) $y['text'] : null, 'yx' => $yx,
            'h' => isset($v['height']['value']) && is_numeric($v['height']['value']) ? (float) $v['height']['value'] : null, 'hu' => $v['height']['unit'] ?? 'cm',
            'res' => $res, 'ok' => [], 'grain' => [], 'traits' => $traits,
            'env' => [], 'envL' => [],
            'where' => in_array('National', $domain, true) ? ['Nationwide'] : $domain, 'dom' => $dom,
            'sea' => array_values(array_filter(array_map(fn ($s) => ['dry' => 'Dry season', 'wet' => 'Wet season'][strtolower($s)] ?? null, (array) ($v['seasons'] ?? [])))),
            'est' => [], 'el' => array_values(array_map('ucfirst', (array) ($v['elevation'] ?? []))),
            'rem' => null, 'uses' => $v['uses'] ?? null, 'notes' => $notes,
            'chip' => $chip,
            'q' => strtolower(implode(' ', array_filter([$v['name'], str_replace([' ', '-'], '', $v['name']), implode(' ', $alias), str_replace([' ', '-'], '', implode(' ', $alias)),
                $v['localName'] ?? '', self::breeder($v['owner'] ?? null) ?? '', $v['reg'] ?? '', $kind]))),
        ];
    }

    /** One option of a filter: key, label, how many, and (days) the range it takes. */
    private static function opt(string $k, string $label, int $n, ?array $range = null): array
    {
        return array_filter(['k' => $k, 'l' => $label, 'n' => $n, 'lo' => $range[0] ?? null, 'hi' => $range[1] ?? null], fn ($x) => $x !== null);
    }

    private static function riceFilters(array $items): array
    {
        $c = collect($items);
        $env = [];
        foreach (RiceVarieties::ENVIRONMENTS as $k => [$long]) {
            $n = $c->filter(fn ($v) => in_array($k, $v['env'], true))->count();
            if ($n) {
                $env[] = self::opt($k, $long, $n);
            }
        }
        $res = [];
        foreach (RiceVarieties::PROBLEMS as $k => [$label]) {
            $res[] = self::opt($k, $label, $c->filter(fn ($v) => in_array($k, $v['ok'], true))->count());
        }
        $days = fn ($lo, $hi) => $c->filter(fn ($v) => $v['d'] && $v['d'] >= $lo && $v['d'] <= $hi)->count();

        return [
            ['key' => 'env', 'label' => 'Kind of farm', 'all' => 'Any farm', 'opts' => $env],
            ['key' => 'res', 'label' => 'Resistant to', 'all' => 'Any', 'multi' => true, 'opts' => $res,
                'note' => 'Rated resistant or moderately resistant in trials. Pick more than one to need them all.'],
            ['key' => 'type', 'label' => 'Type', 'all' => 'Every type', 'opts' => [
                self::opt('inbred', 'Inbred', $c->where('chip', 'inbred')->count()), self::opt('hybrid', 'Hybrid', $c->where('chip', 'hybrid')->count()),
                self::opt('glutinous', 'Glutinous', $c->where('gl', true)->count())]],
            ['key' => 'days', 'label' => 'Days to harvest', 'all' => 'Any', 'opts' => [
                self::opt('110', '110 days or fewer', $days(0, 110), [0, 110]), self::opt('120', '111 to 120 days', $days(111, 120), [111, 120]),
                self::opt('999', 'More than 120 days', $days(121, 9999), [121, 9999])]],
            ['key' => 'sort', 'label' => 'Sort by', 'opts' => [self::opt('year', 'Newest first', 0), self::opt('yield', 'Highest average yield', 0),
                self::opt('days', 'Fewest days to harvest', 0), self::opt('name', 'Name', 0)]],
        ];
    }

    private static function cropFilters(string $crop, array $items): array
    {
        $c = collect($items);
        $out = [];
        // The type: corn by its kinds, every other crop by the kinds it has (two to seven of them).
        $names = ['hybrid' => 'Yellow hybrid', 'gm' => 'GM hybrid', 'white-hybrid' => 'White hybrid', 'opv' => 'Open pollinated', 'glutinous' => 'Glutinous', 'sweet' => 'Sweet corn'];
        if ($crop === 'corn') {
            $opts = collect($names)->map(fn ($l, $k) => self::opt($k, $l, $c->where('chip', $k)->count()))->filter(fn ($o) => $o['n'] > 0)->values()->all();
        } else {
            $kinds = $c->filter(fn ($v) => $v['k'] !== '' && ! str_starts_with(strtolower($v['k']), 'not stated'))->countBy('k')->sortDesc();
            $opts = $kinds->count() >= 2 && $kinds->count() <= 7 ? $kinds->map(fn ($n, $k) => self::opt(Str::slug($k), $k, $n))->values()->all() : [];
        }
        if ($opts) {
            $out[] = ['key' => 'type', 'label' => 'Type', 'all' => 'Every type', 'opts' => $opts];
        }
        if ($c->filter(fn ($v) => $v['dom'])->count() >= 3) {
            $out[] = ['key' => 'dom', 'label' => 'Recommended for', 'all' => 'Anywhere', 'opts' => collect(['luzon' => 'Luzon', 'visayas' => 'Visayas', 'mindanao' => 'Mindanao'])
                ->map(fn ($l, $k) => self::opt($k, $l, $c->filter(fn ($v) => in_array($k, $v['dom'], true))->count()))->filter(fn ($o) => $o['n'] > 0)->values()->all()];
        }
        $ds = $c->pluck('d')->filter()->sort()->values();
        $unit = $c->pluck('du')->filter()->countBy()->sortDesc()->keys()->first() ?? 'days';
        if ($ds->count() >= 3) {
            $t1 = (int) $ds[(int) floor($ds->count() / 3)];
            $t2 = (int) $ds[(int) floor($ds->count() * 2 / 3)];
            $n = fn ($lo, $hi) => $ds->filter(fn ($d) => $d >= $lo && $d <= $hi)->count();
            $opts = [self::opt('short', $t1 . ' ' . $unit . ' or fewer', $n(0, $t1), [0, $t1])];
            if ($t2 > $t1) {
                $opts[] = self::opt('mid', ($t2 > $t1 + 1 ? ($t1 + 1) . ' to ' : '') . $t2 . ' ' . $unit, $n($t1 + 1, $t2), [$t1 + 1, $t2]);
            }
            $opts[] = self::opt('long', 'More than ' . $t2 . ' ' . $unit, $n($t2 + 1, 99999), [$t2 + 1, 99999]);
            $out[] = ['key' => 'days', 'label' => ucfirst($unit) . ' to harvest', 'all' => 'Any', 'opts' => array_values(array_filter($opts, fn ($o) => $o['n'] > 0))];
        }
        $sort = [self::opt('year', 'Newest first', 0)];
        if ($c->filter(fn ($v) => $v['ya'] !== null)->count() >= 3) {
            $sort[] = self::opt('yield', 'Highest yield', 0);
        }
        if ($ds->count() >= 3) {
            $sort[] = self::opt('days', 'Fewest ' . $unit . ' to harvest', 0);
        }
        $sort[] = self::opt('name', 'Name', 0);
        $out[] = ['key' => 'sort', 'label' => 'Sort by', 'opts' => $sort];

        return $out;
    }
}
