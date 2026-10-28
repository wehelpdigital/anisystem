<?php

namespace App\Support;

/**
 * The variety records as the analyses read them (2026-10-14): rice from
 * App\Support\RiceVarieties, every other crop from App\Support\CropVarieties,
 * in one shape, for an app crop key (CropCatalog: rice, rice_dsr_wet,
 * corn_yellow, tomato…).
 *
 * Variety Research gives Anee the records that best fit the field before she
 * ranks, and links each variety she names back to its record; What to Plant
 * shows, under every crop it recommends, the registered varieties that fit
 * the field, with a way to compare them. The picking is plain arithmetic on
 * the records (the growing environment, the island group, trial days and
 * yields, ratings), never the model's.
 */
class VarietyBook
{
    /** Provinces and big towns to their island group: the trial "domain". Longest first is not needed; the rightmost match wins. */
    private const PLACES = [
        'Luzon' => ['luzon', 'abra', 'apayao', 'benguet', 'baguio', 'ifugao', 'kalinga', 'mountain province', 'ilocos', 'laoag', 'vigan', 'la union', 'pangasinan',
            'dagupan', 'batanes', 'cagayan', 'tuguegarao', 'isabela', 'santiago city', 'nueva vizcaya', 'quirino', 'aurora', 'bataan', 'bulacan', 'nueva ecija',
            'cabanatuan', 'munoz', 'muñoz', 'pampanga', 'tarlac', 'zambales', 'olongapo', 'batangas', 'cavite', 'laguna', 'quezon', 'lucena', 'rizal', 'metro manila',
            'manila', 'marinduque', 'mindoro', 'palawan', 'puerto princesa', 'romblon', 'albay', 'legazpi', 'camarines', 'naga city', 'catanduanes', 'masbate',
            'sorsogon', 'calabarzon', 'mimaropa', 'bicol', 'central luzon', 'cordillera'],
        'Visayas' => ['visayas', 'aklan', 'kalibo', 'antique', 'capiz', 'roxas city', 'guimaras', 'iloilo', 'negros', 'bacolod', 'dumaguete', 'bohol', 'tagbilaran',
            'cebu', 'siquijor', 'biliran', 'samar', 'leyte', 'tacloban', 'ormoc', 'panay'],
        'Mindanao' => ['mindanao', 'zamboanga', 'bukidnon', 'malaybalay', 'valencia city', 'camiguin', 'lanao', 'iligan', 'marawi', 'misamis', 'cagayan de oro', 'davao',
            'tagum', 'digos', 'panabo', 'compostela', 'cotabato', 'kidapawan', 'midsayap', 'kabacan', 'koronadal', 'sarangani', 'general santos', 'sultan kudarat',
            'agusan', 'butuan', 'dinagat', 'surigao', 'basilan', 'maguindanao', 'sulu', 'tawi', 'caraga', 'soccsksargen', 'barmm', 'bangsamoro'],
    ];

    /** The island group a written place lies in, or null. "Quezon, Bukidnon" reads as Bukidnon: the rightmost name wins. */
    public static function island(?string $place): ?string
    {
        $t = ' ' . mb_strtolower((string) $place) . ' ';
        $best = null;
        $at = -1;
        $len = 0;
        foreach (self::PLACES as $island => $names) {
            foreach ($names as $n) {
                if (! preg_match_all('/(?<![a-z])' . preg_quote($n, '/') . '(?![a-z])/u', $t, $m, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                $pos = (int) end($m[0])[1];
                if ($pos > $at || ($pos === $at && mb_strlen($n) > $len)) {
                    [$best, $at, $len] = [$island, $pos, mb_strlen($n)];
                }
            }
        }

        return $best;
    }

    /** Which book an app crop key reads: ['rice', null] or ['crop', db key, corn kind filter]. */
    public static function bookFor(?string $cropKey): ?array
    {
        $k = (string) $cropKey;
        if ($k === '') {
            return null;
        }
        if (str_starts_with($k, 'rice')) {
            return ['rice', 'rice', null];
        }
        if (str_starts_with($k, 'corn_')) {
            return ['crop', 'corn', substr($k, 5)];
        }
        // The crops with records, read once a request (crops() walks every record).
        static $crops = null;
        $crops ??= CropVarieties::crops();

        return isset($crops[$k]) ? ['crop', $k, null] : null;
    }

    /** The app crop key a written crop name means ("Rice — transplanted (Palay)", "Munggo", "Tomato (Kamatis)"), or null. */
    public static function cropKeyFor(?string $text): ?string
    {
        $t = self::flat($text);
        if ($t === '') {
            return null;
        }
        static $index = null;
        if ($index === null) {
            $index = [];
            foreach (CropCatalog::CROPS as $key => $c) {
                $label = (string) ($c['label'] ?? '');
                if ($label === '' || empty($c['kind'])) {
                    continue;
                }
                $index[self::flat($label)] = $key;
                $index[self::flat(preg_replace('/\s*\(.*$/', '', $label))] ??= $key;
                if (preg_match('/\(([^)]+)\)/', $label, $m)) {
                    $index[self::flat($m[1])] ??= $key;
                }
                $index[self::flat(str_replace('_', ' ', $key))] ??= $key;
            }
            // The plain words for the split crops.
            $index['rice'] = 'rice';
            $index['palay'] = 'rice';
            $index['corn'] = 'corn_yellow';
            $index['mais'] = 'corn_yellow';
            $index['monggo'] = 'mungbean';
            $index['munggo'] = 'mungbean';
            uksort($index, fn ($a, $b) => strlen($b) <=> strlen($a));
        }
        if (isset($index[$t])) {
            return $index[$t];
        }
        foreach ($index as $k => $key) {
            if (strlen($k) >= 4 && str_contains($t, $k)) {
                return $key;
            }
        }

        return null;
    }

    /** Rows by book, per request (the trial facts are joined once). */
    private static array $rowCache = [];

    /**
     * Every record of the crop in one shape. Each carries `trials` when its
     * registration papers were read (App\Support\VarietyRegistry, 2026-10-18),
     * and a registered variety only the papers hold comes after the curated
     * ones, in the same shape (`trialsOnly`).
     */
    public static function rows(?string $cropKey): array
    {
        $book = self::bookFor($cropKey);
        if (! $book) {
            return [];
        }
        $id = $book[1] . ':' . ($book[2] ?? '') . ':' . (VarietyRegistry::$off ? 'off' : 'on');
        if (isset(self::$rowCache[$id])) {
            return self::$rowCache[$id];
        }
        if ($book[0] === 'rice') {
            return self::$rowCache[$id] = array_map(fn ($r) => $r + ['book' => 'rice'], self::withTrials(array_map(fn ($v) => self::fromRice($v), RiceVarieties::all()), 'rice'));
        }
        $rows = self::withTrials(array_map(fn ($v) => self::fromCrop($v), CropVarieties::forCrop($book[1])), $book[1]);
        if ($book[1] === 'corn' && $book[2]) {
            $want = $book[2];
            $rows = array_values(array_filter($rows, function ($r) use ($want) {
                $k = strtolower($r['kind']);

                return match ($want) {
                    'sweet' => str_contains($k, 'sweet'),
                    'glutinous' => str_contains($k, 'glutinous') || $r['color'] === 'white',
                    default => ! str_contains($k, 'sweet') && ! str_contains($k, 'glutinous') && $r['color'] !== 'white',
                };
            }));
        }

        // Each row knows its crop (VarietyShield reads the crop's own pests and diseases).
        return self::$rowCache[$id] = array_map(fn ($r) => $r + ['book' => $book[1] === 'corn' ? 'corn_' . ($book[2] ?: 'yellow') : $book[1]], $rows);
    }

    /** Forget the rows read this request (tests, measurements). */
    public static function forget(): void
    {
        self::$rowCache = [];
        self::$matchIndex = [];
        VarietyRegistry::forget();
    }

    /**
     * The curated records with the trial facts of their registration papers
     * joined on (`trials`), and the registered varieties only the papers
     * hold appended. The curated figures are kept; a figure the record lacks
     * (the newest varieties have no days or yield yet) is filled from the
     * trials and marked (`fromTrials`).
     */
    private static function withTrials(array $rows, string $bookKey): array
    {
        try {
            [$by, $rest] = VarietyRegistry::split($bookKey);
        } catch (\Throwable $e) {
            report($e);

            return $rows;
        }
        if (! $by && ! $rest) {
            return $rows;
        }
        foreach ($rows as &$r) {
            $t = $by[$r['name']] ?? null;
            if (! $t) {
                continue;
            }
            $r['trials'] = VarietyRegistry::trials($t);
            $filled = [];
            if ($r['days'] === null && ! empty($t['days'])) {
                $r['days'] = (float) $t['days'];
                $filled[] = 'days';
            }
            if ($r['yield'] === null && ! empty($t['yieldAvg']) && ($r['yieldUnit'] ?? 't/ha') === 't/ha') {
                $r['yield'] = (float) $t['yieldAvg'];
                $r['yieldTop'] ??= ! empty($t['yieldTop']) ? (float) $t['yieldTop'] : null;
                $filled[] = 'yield';
            }
            // The best trial site is the variety's potential (2026-10-21, the owner: a 15 t corn target was held to the
            // 9.1 t average of DK 8282S): a curated average keeps the registration trials' best site beside it.
            if (($r['yieldTop'] ?? null) === null && ! empty($t['yieldTop']) && ($r['yieldUnit'] ?? 't/ha') === 't/ha' && (float) $t['yieldTop'] > (float) ($r['yield'] ?? 0)) {
                $r['yieldTop'] = (float) $t['yieldTop'];
            }
            if (! $r['ratings'] && ! empty($t['facts']['ratings'])) {
                $r['ratings'] = self::shortRatings((array) $t['facts']['ratings']);
                $filled[] = 'ratings';
            }
            if (! $r['envs'] && ! empty($t['envs']) && $bookKey === 'rice') {
                $r['envs'] = (array) $t['envs'];
                $r['where'] = implode(', ', array_map(fn ($e) => self::RICE_ENVS[$e] ?? $e, $r['envs']));
                $filled[] = 'envs';
            }
            if (! $r['domain'] && ! empty($t['facts']['islands'])) {
                $r['domain'] = (array) $t['facts']['islands'];
            }
            if (! $r['seasons'] && in_array($t['seasonFit'] ?? '', ['wet', 'dry'], true)) {
                $r['seasons'] = [$t['seasonFit']];
            }
            if ($filled) {
                $r['fromTrials'] = $filled;
            }
        }
        unset($r);
        foreach ($rest as $t) {
            $rows[] = self::fromRegistry($t, $bookKey);
        }

        return $rows;
    }

    /** The ratings the fit arithmetic reads: one code per pest or disease. */
    private static function shortRatings(array $ratings): array
    {
        $out = [];
        foreach ($ratings as $k => $c) {
            if (is_string($c) && in_array($c, ['HR', 'R', 'MR', 'T', 'I', 'MS', 'S', 'HS'], true)) {
                $out[(string) $k] = $c;
            }
        }

        return $out;
    }

    /** A registered variety only its papers hold, as a record. */
    private static function fromRegistry(array $t, string $bookKey): array
    {
        $f = (array) ($t['facts'] ?? []);
        $type = (string) ($t['type'] ?? '');
        $hybrid = str_contains($type, 'hybrid');
        $envs = $bookKey === 'rice' ? (array) ($t['envs'] ?? []) : [];
        $islands = (array) ($f['islands'] ?? []);

        return [
            'name' => $t['name'],
            'aliases' => array_values(array_filter(array_unique(array_merge([$t['localName'] ?? null], (array) ($t['aliases'] ?? []))))),
            'type' => $hybrid ? 'hybrid' : ($bookKey === 'rice' ? 'inbred' : 'other'),
            'kind' => trim((string) ($t['kind'] ?? '') ?: ($hybrid ? 'hybrid' : ($bookKey === 'rice' ? 'inbred' : ($type ?: 'variety')))),
            'color' => $f['color'] ?? null,
            'year' => $t['year'] ?? null,
            'days' => isset($t['days']) ? (float) $t['days'] : null,
            'daysUnit' => (string) ($f['daysUnit'] ?? 'days'),
            'yield' => isset($t['yieldAvg']) && ($f['yieldUnit'] ?? 't/ha') === 't/ha' ? (float) $t['yieldAvg'] : null,
            'yieldTop' => isset($t['yieldTop']) && ($f['yieldUnit'] ?? 't/ha') === 't/ha' ? (float) $t['yieldTop'] : null,
            'yieldUnit' => (string) ($f['yieldUnit'] ?? 't/ha'),
            'ratings' => self::shortRatings((array) ($f['ratings'] ?? [])),
            'envs' => $envs,
            'domain' => $islands,
            'seasons' => in_array($t['seasonFit'] ?? '', ['wet', 'dry'], true) ? [$t['seasonFit']] : [],
            'elevation' => in_array('cool', $envs, true) ? ['highland'] : [],
            'where' => $envs ? implode(', ', array_map(fn ($e) => self::RICE_ENVS[$e] ?? $e, $envs)) : ($islands ? (in_array('National', $islands, true) ? 'nationwide' : implode(', ', $islands)) : ''),
            'registered' => true,
            'url' => url('/app/field-helpers/varieties') . '?crop=' . rawurlencode($bookKey) . '&q=' . rawurlencode((string) $t['name']),
            'seller' => '',
            'trials' => VarietyRegistry::trials($t),
            'trialsOnly' => true,
        ];
    }

    /**
     * A record's registration trial facts in words for a prompt, '' when it
     * has none. Short: the line promptLines() adds; full: every test, rating,
     * stress, grain, area and limit (the Crop Protocol, the season reports).
     */
    public static function trialWords(array $rec, bool $full = false): string
    {
        if (empty($rec['trials']) || ! is_array($rec['trials'])) {
            return '';
        }
        $t = $rec['trials'];
        if (! $full) {
            // Short: only what the record's own line does not already say (prompts stay lean).
            if (isset($t['days'], $rec['days']) && abs((float) $t['days'] - (float) $rec['days']) <= 2 && empty($t['conflict']['days'])) {
                unset($t['days']);
            }
            if (isset($t['yield']['avg'], $rec['yield']) && $rec['yield'] > 0 && abs((float) $t['yield']['avg'] - (float) $rec['yield']) / (float) $rec['yield'] <= 0.05) {
                unset($t['yield']['avg']);
            }
            if (isset($t['heightCm']) && empty($rec['trialsOnly'])) {
                unset($t['heightCm']);
            }
            $own = (array) ($rec['ratings'] ?? []);
            $t['ratings'] = array_filter((array) ($t['ratings'] ?? []), fn ($c, $k) => ($own[$k] ?? null) !== $c, ARRAY_FILTER_USE_BOTH);
            unset($t['traits'], $t['tested']['years']);
        }
        $w = VarietyRegistry::words($t, $full, (string) ($rec['daysUnit'] ?? 'days'));
        if ($w === '') {
            return '';
        }
        // Where the curated figure and the trials differ, both are said; the curated one leads.
        $c = (array) ($t['conflict'] ?? []);
        $diff = [];
        foreach (['days' => 'days', 'yield' => 't/ha average'] as $k => $unit) {
            if (isset($c[$k]['ours'], $c[$k]['trials'])) {
                $diff[] = self::num((float) $c[$k]['ours']) . ' ' . $unit . ' on record against ' . self::num((float) $c[$k]['trials']) . ' in the registration trials';
            }
        }

        return 'registration trials: ' . $w . ($diff ? ' (differs: ' . implode('; ', $diff) . ')' : '');
    }

    /**
     * The records that best fit a field, best first, with hybrids and the
     * rest both in the list where the crop has both.
     *
     * $case: island (Luzon|Visayas|Mindanao|null), envs (the growing
     * environments wanted, rice's words), elevation (lowland|upland|highland),
     * season (wet|dry), problems (pests, disease…).
     */
    public static function candidates(?string $cropKey, array $case, int $limit = 12): array
    {
        $rows = self::rows($cropKey);
        if (! $rows) {
            return [];
        }
        // Yields ranked within the crop and type, so a hybrid is not measured against inbreds.
        $byType = [];
        foreach ($rows as $r) {
            if ($r['yield'] !== null && $r['yieldUnit'] === 't/ha') {
                $byType[$r['type']][] = $r['yield'];
            }
        }
        foreach ($byType as &$list) {
            sort($list);
        }
        unset($list);
        $want = array_values(array_unique(array_filter((array) ($case['envs'] ?? []))));
        $special = array_intersect($want, ['saline', 'flood', 'drought', 'cool', 'upland']);
        $island = $case['island'] ?? null;
        $year = (int) now('Asia/Manila')->format('Y');
        $problems = (array) ($case['problems'] ?? []);
        foreach ($rows as &$r) {
            $s = 0;
            if ($want) {
                $hit = array_intersect($r['envs'], $want);
                if ($hit) {
                    $s += array_intersect($hit, $special) ? 45 : 25;
                } elseif ($r['envs']) {
                    $s -= $special ? 40 : 20;
                }
            }
            if ($island) {
                if (in_array('National', $r['domain'], true) || in_array($island, $r['domain'], true)) {
                    $s += 12;
                } elseif ($r['domain']) {
                    $s -= 10;
                }
            }
            if (($case['elevation'] ?? null) && $r['elevation']) {
                $s += in_array($case['elevation'] === 'highland' ? 'highland' : 'lowland', $r['elevation'], true) ? 10 : -10;
            }
            if (($case['season'] ?? null) && $r['seasons']) {
                $s += in_array($case['season'], $r['seasons'], true) ? 5 : -3;
            }
            $s += ($r['days'] !== null ? 8 : 0) + ($r['yield'] !== null ? 8 : 0);
            // Special rice (pigmented, zinc, aromatic) and glutinous ones are
            // for a buyer who asks for them, not the first answer for a field.
            if (preg_match('/special|glutinous/', $r['kind']) && empty($case['special'])) {
                $s -= 15;
            }
            if ($r['yield'] !== null && ! empty($byType[$r['type']])) {
                $list = $byType[$r['type']];
                $below = count(array_filter($list, fn ($x) => $x <= $r['yield']));
                $s += (int) round(20 * $below / count($list));
            }
            $good = count(array_filter($r['ratings'], fn ($c) => in_array($c, ['HR', 'R', 'MR', 'T'], true)));
            $s += min(10, $good * (array_intersect($problems, ['pests', 'disease']) ? 3 : 2));
            if ($r['year']) {
                $s += $r['year'] >= $year - 6 ? 6 : ($r['year'] >= $year - 12 ? 3 : 0);
            }
            $r['fit'] = $s;
        }
        unset($r);
        usort($rows, fn ($a, $b) => [$b['fit'], $b['yield'] ?? 0, $b['year'] ?? 0] <=> [$a['fit'], $a['yield'] ?? 0, $a['year'] ?? 0]);
        // A hard ground (saline, flood, drought, cool, upland) picks by fit
        // alone: a hybrid bred for good land is no answer to salt.
        if ($special && array_filter($rows, fn ($r) => array_intersect($r['envs'], $special))) {
            return array_slice($rows, 0, $limit);
        }
        // Both kinds, where there are both: about half each, the best first.
        $hy = array_values(array_filter($rows, fn ($r) => $r['type'] === 'hybrid'));
        $other = array_values(array_filter($rows, fn ($r) => $r['type'] !== 'hybrid'));
        if (! $hy || ! $other) {
            return array_slice($rows, 0, $limit);
        }
        $nHy = min(count($hy), (int) floor($limit / 2));
        $pick = array_merge(array_slice($hy, 0, $nHy), array_slice($other, 0, $limit - $nHy));
        usort($pick, fn ($a, $b) => $b['fit'] <=> $a['fit']);

        return $pick;
    }

    /** The records of the varieties a farmer named, first, then the best fits; one of each. */
    public static function namedThenFit(?string $cropKey, array $named, array $case, int $limit): array
    {
        $out = [];
        foreach ($named as $n) {
            // A variety Anee looked up before is the farmer's too (learn once, 2026-10-18).
            if (($m = self::find($cropKey, (string) $n)) && ! isset($out[$m['name']])) {
                $out[$m['name']] = $m;
            }
        }
        foreach (self::candidates($cropKey, $case, $limit) as $r) {
            if (count($out) >= $limit + count($named)) {
                break;
            }
            $out[$r['name']] ??= $r;
        }

        return array_values($out);
    }

    /** The growing environments a case wants, in rice's words, from the analyses' own fields. */
    public static function envsFor(?string $cropKey, array $problems = [], ?string $water = null, ?string $elevation = null): array
    {
        if (! str_starts_with((string) $cropKey, 'rice')) {
            return [];
        }
        $e = match ($cropKey) {
            'rice_dsr_wet' => ['irrigated', 'rainfed-dsr'],
            'rice_dsr_dry' => ['rainfed-dsr', 'upland'],
            default => $water === 'rainfed' ? ['rainfed-tpr', 'rainfed-dsr'] : ['irrigated'],
        };
        $p = array_flip($problems);
        if (isset($p['salinity']) || isset($p['sea'])) {
            $e[] = 'saline';
        }
        if (isset($p['floods'])) {
            $e[] = 'flood';
        }
        if (isset($p['drought']) || $water === 'limited') {
            $e[] = 'drought';
        }
        if ($elevation === 'highland') {
            $e[] = 'cool';
        }

        return array_values(array_unique($e));
    }

    /** The record a written variety name means ("NSIC Rc 222 (Tubigan 18)", "Jackpot 102", "DK 8899S"), or null. */
    public static function match(?string $cropKey, ?string $name): ?array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        $book = self::bookFor($cropKey);
        if (! $book) {
            return null;
        }
        $id = $book[1] . ':' . ($book[2] ?? '') . ':' . (VarietyRegistry::$off ? 'off' : 'on');
        if (! isset(self::$matchIndex[$id])) {
            $index = [];
            foreach (self::rows($cropKey) as $r) {
                // The curated names first; the names the registration papers add (line names, the registered number with its year) after.
                foreach (array_merge([$r['name']], $r['aliases'], (array) ($r['trials']['names'] ?? [])) as $n) {
                    foreach (self::keys((string) $n) as $k) {
                        $index[$k] ??= $r;
                    }
                }
            }
            self::$matchIndex[$id] = $index;
        }
        $parts = array_merge([$name], preg_split('/\s*[()\/,;]\s*|\s+or\s+|\s+aka\s+/i', $name) ?: []);
        foreach ($parts as $part) {
            foreach (self::keys($part) as $k) {
                if (isset(self::$matchIndex[$id][$k])) {
                    return self::$matchIndex[$id][$k];
                }
            }
        }

        return null;
    }

    /** Name indexes by book, per request. */
    private static array $matchIndex = [];

    /**
     * The variety a written name means, looked up first by every module
     * before any research (2026-10-18, the owner: learn once): the curated
     * record (match()), else a variety Anee looked up before and the app kept
     * (App\Support\VarietyLearned): a kept alias leads to its record, a kept
     * variety comes back in the record shape with `learned` set (its id, the
     * day it was learned, how sure, the company, ratings, seed hours) and
     * `learnedLine` (its facts in one line for a prompt). Null when neither
     * knows it, or the kept row is past its year (`$fresh`), so the module
     * looks it up again.
     */
    public static function find(?string $cropKey, ?string $name, bool $fresh = true): ?array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        if ($m = self::match($cropKey, $name)) {
            return $m;
        }
        try {
            $l = VarietyLearned::lookup($cropKey, $name, $fresh);
        } catch (\Throwable $e) {
            report($e);
            $l = null;
        }
        if (! $l) {
            return null;
        }
        if ($l['aliasOf']) {
            return self::match($cropKey, $l['aliasOf']);
        }

        return VarietyLearned::asRecord($l) + ['learnedLine' => VarietyLearned::words($l)];
    }

    /**
     * One line of what is known about a variety, for any prompt that has a
     * crop and a variety name and nothing else (the chat's season context,
     * NPK Plus, Satellite, Realign, the Builder review, Ask Anee): the
     * record's figures, its registration trials in short, or a kept lookup.
     * $crop may be an app crop key or a written crop name. '' when the name
     * is unknown, or the field is not in the Philippines (the records speak
     * only for a field there). No search, no AI: files and one cached query.
     */
    public static function factsLine(?string $crop, ?string $variety, bool $full = false, ?string $country = null): string
    {
        $variety = trim((string) $variety);
        if ($variety === '' || mb_strlen($variety) > 120) {
            return '';
        }
        $fc = Region::valid($country) ?: Region::code();
        if ($fc !== Region::HOME) {
            return '';
        }
        $key = self::bookFor($crop) ? (string) $crop : self::cropKeyFor($crop);
        $rec = $key ? self::find($key, $variety) : null;
        if (! $rec) {
            return '';
        }
        $line = ltrim(self::promptLines([$rec]), '- ');
        if ($full && ($tw = self::trialWords($rec, true)) !== '') {
            // The full trial block replaces the short one promptLines() carries.
            $short = self::trialWords($rec);
            $line = $short !== '' ? str_replace($short, $tw, $line) : $line . ' ' . $tw;
        }

        return $line;
    }

    /**
     * A variety's days to harvest for any module that times a crop by it, by
     * the one rule a season's lot uses (AsScheduleLot::maturityDays): the
     * record's days (its registration trials fill a record that has none),
     * kept only when near the crop's usual length (0.65x to 1.45x), so a day
     * count read in the wrong unit never stretches a calendar. Null when the
     * variety is unknown, has no days, or the crop is a tree crop.
     */
    public static function days(?string $crop, ?string $variety): ?int
    {
        $name = trim((string) $variety);
        if ($name === '' || ! $crop || CropCatalog::isPerennial((string) $crop)) {
            return null;
        }
        try {
            $rec = self::find((string) $crop, $name);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if (! $rec || empty($rec['days']) || ($rec['daysUnit'] ?? 'days') !== 'days') {
            return null;
        }

        return self::nearUsual((int) round((float) $rec['days']), $crop);
    }

    /** A variety's day count when it sits within 0.65x to 1.45x of the crop's usual length, else null. */
    public static function nearUsual(?int $days, ?string $crop): ?int
    {
        $usual = CropStages::maturity($crop);
        if (! $days || $days <= 0) {
            return null;
        }

        return ! $usual || ($days >= $usual * 0.65 && $days <= $usual * 1.45) ? $days : null;
    }

    /** Whether the name is known (on the record or kept from an earlier lookup), so no research is needed. */
    public static function known(?string $cropKey, ?string $name): bool
    {
        return self::find($cropKey, $name) !== null;
    }

    /** A module used a kept variety instead of searching: counted (the mother app shows it). */
    public static function used(?array $rec): void
    {
        if (! empty($rec['learned']['id'])) {
            VarietyLearned::hit((int) $rec['learned']['id']);
        }
    }

    /** A record as the reports show it. */
    public static function brief(array $r): array
    {
        // The other name worth showing: not one that only repeats this one
        // ("Red Dragon onion" for Red Dragon, "DK 8899" for DK8899S).
        $n = self::flat($r['name']);
        $alias = null;
        foreach ($r['aliases'] as $a) {
            $f = self::flat($a);
            if ($f !== '' && ! str_contains($f, $n) && ! str_contains($n, $f)) {
                $alias = $a;
                break;
            }
        }

        return [
            'name' => $r['name'],
            'alias' => $alias,
            'type' => $r['type'],
            'kind' => $r['kind'],
            'year' => $r['year'],
            'days' => $r['days'],
            'daysUnit' => $r['daysUnit'],
            'yield' => $r['yield'],
            'yieldUnit' => $r['yieldUnit'],
            'ratings' => $r['ratings'],
            'where' => $r['where'],
            'registered' => $r['registered'],
            'url' => $r['url'],
            'learned' => ! empty($r['learned']),
        ];
    }

    /** The records a prompt reads, one line each. */
    public static function promptLines(array $rows): string
    {
        return implode("\n", array_map(function ($r) {
            // A variety Anee looked up before (App\Support\VarietyLearned): its kept facts, said as hers.
            if (! empty($r['learnedLine'])) {
                return '- ' . $r['learnedLine'];
            }
            $bits = [$r['type'] === 'hybrid' ? 'hybrid' : ($r['kind'] ?: 'inbred')];
            if ($r['aliases']) {
                $bits[] = 'also ' . implode(', ', array_slice($r['aliases'], 0, 3));
            }
            if ($r['year']) {
                $bits[] = (string) $r['year'];
            }
            if ($r['days'] !== null) {
                $bits[] = self::num($r['days']) . ' ' . $r['daysUnit'];
            }
            if ($r['yield'] !== null) {
                $bits[] = self::num($r['yield']) . ' ' . $r['yieldUnit'] . ' average' . ($r['yieldTop'] ? ', top ' . self::num($r['yieldTop']) : '');
            }
            if ($r['where']) {
                $bits[] = $r['where'];
            }
            if ($r['ratings']) {
                $bits[] = 'ratings: ' . implode(', ', array_map(fn ($k, $c) => $k . ' ' . $c, array_keys($r['ratings']), $r['ratings']));
            }
            if (! $r['registered']) {
                $bits[] = 'not registered, seller figures';
            }
            if (! empty($r['seller'])) {
                // A seller's note may quote an agency ("listed by DA Region 2"): the figure stays, whose it is goes (GovSource).
                $bits[] = 'the seller says: ' . GovSource::scrub((string) $r['seller']);
            }
            // What its registration papers say, in short (2026-10-18); never where it came from.
            if (($tw = self::trialWords($r)) !== '') {
                $bits[] = $tw;
            }
            // What its own seed guards it from, and what that changes in the spraying (2026-10-28, VarietyShield).
            try {
                if (($sh = VarietyShield::of($r, $r['book'] ?? null))['say'] !== '') {
                    $bits[] = 'protection: ' . $sh['say'];
                }
            } catch (\Throwable $e) {
                report($e);
            }

            return '- ' . $r['name'] . ' [' . implode('; ', $bits) . ']';
        }, $rows));
    }

    /** The finder of a crop on the public site. */
    public static function finderUrl(?string $cropKey): ?string
    {
        $book = self::bookFor($cropKey);
        if (! $book) {
            return null;
        }

        return $book[0] === 'rice' ? url('/rice-varieties') : url('/crop-varieties/' . CropVarieties::slugOf($book[1]));
    }

    /* ------------------------------------------------------------------ */

    private const RICE_RATINGS = ['blast' => 'blast', 'blb' => 'bacterial leaf blight', 'tungro' => 'tungro', 'bph' => 'brown planthopper',
        'glh' => 'green leafhopper', 'sb' => 'stem borer', 'shb' => 'sheath blight'];

    private const RICE_ENVS = ['irrigated' => 'irrigated lowland', 'rainfed-tpr' => 'rainfed lowland, transplanted', 'rainfed-dsr' => 'rainfed lowland, direct seeded',
        'upland' => 'upland', 'saline' => 'saline prone', 'flood' => 'flood prone', 'drought' => 'drought prone', 'cool' => 'cool elevated'];

    private static function fromRice(array $v): array
    {
        $ratings = [];
        foreach ($v['reactions'] ?? [] as $k => $raw) {
            if (($c = RiceVarieties::code($raw)) && isset(self::RICE_RATINGS[$k])) {
                $ratings[self::RICE_RATINGS[$k]] = $c;
            }
        }
        $dom = (string) ($v['domain'] ?? '') . ' ' . implode(' ', $v['regions'] ?? []);
        $islands = array_values(array_filter(['Luzon', 'Visayas', 'Mindanao'], fn ($i) => stripos($dom, $i) !== false));
        if (preg_match('/national|nationwide|all regions/i', $dom)) {
            $islands = ['National'];
        }

        return [
            'name' => $v['name'],
            'aliases' => array_values(array_filter(array_merge($v['tradeNames'] ?? [], [$v['localName'] ?? null]))),
            'type' => ($v['type'] ?? '') === 'hybrid' ? 'hybrid' : 'inbred',
            'kind' => trim((($v['special'] ?? null) ? $v['special'] . ' ' : '') . (($v['type'] ?? '') === 'hybrid' ? 'hybrid' : 'inbred')),
            'color' => null,
            'year' => $v['year'] ?? null,
            'days' => isset($v['maturity']) ? (float) $v['maturity'] : null,
            'daysUnit' => 'days',
            'yield' => isset($v['yieldAvg']) ? (float) $v['yieldAvg'] : null,
            'yieldTop' => isset($v['yieldMax']) ? (float) $v['yieldMax'] : null,
            'yieldUnit' => 't/ha',
            'ratings' => $ratings,
            'envs' => $v['environments'] ?? [],
            'domain' => $islands,
            'seasons' => array_values(array_filter(array_map(fn ($s) => ['DS' => 'dry', 'WS' => 'wet'][$s] ?? null, $v['seasons'] ?? []))),
            'elevation' => in_array('cool', $v['environments'] ?? [], true) ? ['highland'] : [],
            'where' => implode(', ', array_map(fn ($e) => self::RICE_ENVS[$e] ?? $e, $v['environments'] ?? [])),
            'registered' => true,
            'url' => url('/rice-varieties/' . $v['slug']),
            'seller' => self::sellerWords((array) ($v['seller']['facts'] ?? [])),
        ];
    }

    private static function fromCrop(array $v): array
    {
        $kind = CropVarieties::kind($v);
        $ratings = [];
        foreach ($v['reactions'] ?? [] as [$lab, $c]) {
            if (in_array($c, ['HR', 'R', 'MR', 'T', 'I', 'MS', 'S', 'HS'], true)) {
                $ratings[mb_strtolower($lab)] = $c;
            }
        }
        $m = $v['maturity'] ?? null;
        $y = $v['yield'] ?? null;
        $dom = $v['domain'] ?? [];
        $where = $dom ? (in_array('National', $dom, true) ? 'nationwide' : implode(', ', $dom)) : '';
        if (! empty($v['elevation'])) {
            $where = trim($where . ($where ? ', ' : '') . implode(' and ', $v['elevation']));
        }

        return [
            'name' => $v['name'],
            'aliases' => array_values(array_filter(array_merge($v['aliases'] ?? [], [$v['localName'] ?? null]))),
            'type' => str_contains(strtolower($kind), 'hybrid') ? 'hybrid' : 'other',
            'kind' => strtolower($kind),
            'color' => $v['color'] ?? null,
            'year' => $v['year'] ?? null,
            'days' => $m ? (float) round($m['value']) : null,
            'daysUnit' => $m['unit'] ?? 'days',
            'yield' => isset($y['value']) ? (float) $y['value'] : null,
            'yieldTop' => null,
            'yieldUnit' => $y['unit'] ?? 't/ha',
            'ratings' => $ratings,
            'envs' => [],
            'domain' => $dom,
            'seasons' => $v['seasons'] ?? [],
            'elevation' => $v['elevation'] ?? [],
            'where' => $where,
            'registered' => ! empty($v['reg']),
            'url' => url('/crop-varieties/' . $v['slug']),
            'seller' => self::sellerWords((array) ($v['research']['facts'] ?? [])),
        ];
    }

    /**
     * The seller's own figures in a line an analysis can read (2026-10-16,
     * the owner: the Dekalb, NK and Arize profiles "can be used for AI
     * analysis"): days, yield, the traits that decide a plan, the stand and
     * the fertilizer it was trialled on. Short: prompts stay lean.
     */
    public static function sellerWords(array $f): string
    {
        // Brackets (who said it, which trial) stay on the page; a cut falls between words.
        $cut = function (string $t, int $max): string {
            $t = trim((string) preg_replace(['/\s*\([^)]*\)/u', '/\s+/u'], ['', ' '], $t));
            if (mb_strlen($t) <= $max) {
                return $t;
            }
            $t = mb_substr($t, 0, $max);

            return rtrim(mb_substr($t, 0, (int) (mb_strrpos($t, ' ') ?: $max)), ' ,;:') . '…';
        };
        $bits = [];
        foreach (['maturityDays' => 'days', 'yield' => 'yield', 'density' => 'stand', 'seedRate' => 'seed', 'fertilizer' => 'fertilizer', 'gm' => 'traits in the seed'] as $k => $lab) {
            if (! empty($f[$k]) && is_string($f[$k])) {
                $bits[] = $lab . ' ' . $cut($f[$k], $k === 'gm' ? 100 : 80);
            }
        }
        $traits = array_values(array_filter(array_map(fn ($t) => is_string($t) ? $cut($t, 60) : '', (array) ($f['traits'] ?? []))));
        if ($traits) {
            $bits[] = implode(', ', array_slice($traits, 0, 5));
        }

        return $cut(implode('; ', $bits), 360);
    }

    /** The lookup keys of a name, for App\Support\VarietyLearned (the same keys the records are matched by). */
    public static function nameKeys(string $n): array
    {
        return self::keys($n);
    }

    /** The lookup keys a name gives: "NSIC Rc 222" -> nsicrc222, rc222; "Dekalb 8899S" -> dk8899s; "NSIC 2019 Rc 600" also -> nsicrc600, rc600. */
    private static function keys(string $n): array
    {
        $f = self::flat(str_ireplace(['dekalb', 'pioneer'], ['dk', 'p'], $n));
        $f = preg_replace('/(f1|hybrid|variety|seeds?)$/', '', $f);
        if (strlen($f) < 2) {
            return [];
        }
        $out = [$f];
        if (preg_match('/^(nsic|psb|bpi|upl)(.+)$/', $f, $m)) {
            $out[] = $m[2];
            // The registration year written in the number (2026-10-18, learn once): "NSIC 2019 Rc 600" is NSIC Rc 600.
            if (preg_match('/^(?:19|20)\d\d([a-z].*)$/', $m[2], $y)) {
                $out[] = $m[1] . $y[1];
                $out[] = $y[1];
            }
        }

        return array_values(array_unique($out));
    }

    private static function flat(?string $s): string
    {
        $s = mb_strtolower((string) $s);
        $s = strtr($s, ['ñ' => 'n', '—' => ' ', '–' => ' ']);

        return (string) preg_replace('/[^a-z0-9]+/', '', $s);
    }

    private static function num(float $x): string
    {
        return rtrim(rtrim(number_format($x, 2, '.', ''), '0'), '.');
    }
}
