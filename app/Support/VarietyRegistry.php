<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The registration trial facts of the registered varieties (2026-10-18, the
 * owner: "get all details and scan all tests ... put all you scanned and get
 * (not the files itself ... but your summaries) in the database which can be
 * used to add more accuracy to the analysis and outputs ... this is for data
 * analysis support only").
 *
 * Every variety's registration papers were read (scanned pages, OCR and a
 * reader of our own, never an AI run), the facts written down in our own
 * words and numbers: maturity and height by season, yields by season and the
 * best test, the yield advantage over the check varieties, how many seasons
 * and sites it was tried in, the rating for each pest and disease, its
 * tolerance of salt, drought, flooding, cold and lodging, grain or fruit
 * quality, where and in which season it is recommended, and its known
 * limits. The seed file is database/variety-registry/registry.json (our own
 * data; the build scripts and the raw pages stay in the build session's
 * scratchpad and are never committed), loaded into as_variety_registry by
 * the migration through sync().
 *
 * The rules:
 *  - Nothing stored names where a fact came from: no agency, bureau,
 *    council, registry, station, state university, institute, IRRI or FAO
 *    (the owner, 2026-10-17). Test stations became their towns. A private
 *    seed company that bred or sells a variety stays, as a fact about it.
 *  - The curated books (App\Support\RiceVarieties, App\Support\CropVarieties)
 *    are never overwritten. A row matched to a curated record names it
 *    (bookName); where the two disagree both are kept (conflicts) and the
 *    analyses keep the curated figure, the one the variety pages and the
 *    finders show, and are told the trial figure beside it.
 *  - sourceRef is the item's id in the papers, for a re-run only. It never
 *    reaches a screen or a prompt (trials() leaves it out).
 *
 * App\Support\VarietyBook is the only door the modules use: its rows carry
 * `trials` (this class's trials()), promptLines() says them compactly and
 * trialWords() says them in full. Read through
 * App\Support\Concerns\ShelfConnection; until the table exists the file
 * answers.
 */
final class VarietyRegistry
{
    use Concerns\ShelfConnection;

    public const TABLE = 'as_variety_registry';

    /** The seed file, relative to database/. */
    public const FILE = 'variety-registry/registry.json';

    /** A measurement switch (before and after runs): true reads nothing. */
    public static bool $off = false;

    /** Rows by book key, per request. */
    private static array $books = [];

    private static ?bool $ready = null;

    /** The words of a rating code. */
    public const RATING_WORDS = [
        'HR' => 'highly resistant', 'R' => 'resistant', 'MR' => 'moderately resistant', 'I' => 'intermediate',
        'MS' => 'moderately susceptible', 'S' => 'susceptible', 'HS' => 'highly susceptible',
        'HT' => 'highly tolerant', 'T' => 'tolerant', 'MT' => 'moderately tolerant',
    ];

    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = (bool) Cache::remember('variety-registry:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable(self::TABLE));
            } catch (\Throwable $e) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /** The seed file's stamp: a new file (a deploy) reads the table afresh. */
    private static function stamp(): string
    {
        return (string) (@filemtime(database_path(self::FILE)) ?: 0);
    }

    /** Every row of the seed file, decoded (the fallback and sync's input). */
    public static function fileRows(?string $file = null): array
    {
        $file ??= database_path(self::FILE);
        $rows = json_decode((string) @file_get_contents($file), true);

        return is_array($rows['rows'] ?? null) ? $rows['rows'] : (is_array($rows) && array_is_list($rows) ? $rows : []);
    }

    /**
     * Every row of a book (VarietyBook's book key: rice, corn, tomato ...),
     * decoded, in the file's order. One query per book, kept six hours.
     */
    public static function book(?string $bookKey): array
    {
        $bookKey = (string) $bookKey;
        if (self::$off || $bookKey === '') {
            return [];
        }
        $all = self::all();

        return $all[$bookKey] ?? [];
    }

    /**
     * Every live row, by book key: ONE query for the whole table (What to
     * Plant reads sixty crops in one prompt), kept six hours and per request.
     */
    public static function all(): array
    {
        if (self::$off) {
            return [];
        }
        $id = self::conn();
        if (isset(self::$books[$id])) {
            return self::$books[$id];
        }
        $group = function (array $rows): array {
            $out = [];
            foreach ($rows as $r) {
                $out[(string) ($r['crop'] ?? '')][] = $r;
            }

            return $out;
        };
        try {
            $by = self::ready()
                ? Cache::remember('variety-registry:' . $id . ':all:' . self::stamp(), 21600, fn () => $group(
                    self::db()->table(self::TABLE)->where('status', 1)->orderBy('sortOrder')->get()->map(fn ($r) => self::decode($r))->all()))
                : $group(self::fileRows());
        } catch (\Throwable $e) {
            report($e);
            $by = $group(self::fileRows());
        }

        return self::$books[$id] = $by;
    }

    /** Forget what this request read (tests, sync). */
    public static function forget(): void
    {
        self::$books = [];
        self::$ready = null;
    }

    /** The rows of a book by the curated record each one matched (bookName), and the rows no record matched. */
    public static function split(?string $bookKey): array
    {
        $by = [];
        $rest = [];
        foreach (self::book($bookKey) as $r) {
            if (! empty($r['bookName'])) {
                $by[$r['bookName']] ??= $r;
            } else {
                $rest[] = $r;
            }
        }

        return [$by, $rest];
    }

    /**
     * The varieties of a crop on record as tolerant of a stress (salinity,
     * submergence, drought, cold, heat), most tolerant first:
     * [['name', 'aliases', 'rating', 'days']]. For the soil and climate
     * readers ("salt tolerant varieties on record").
     */
    public static function tolerant(string $cropKey, string $stress = 'salinity', int $limit = 6): array
    {
        $book = VarietyBook::bookFor($cropKey);
        if (! $book) {
            return [];
        }
        $rank = ['highly tolerant' => 4, 'tolerant' => 3, 'moderately tolerant' => 2];
        $out = [];
        $bredFor = ['salinity' => 'saline', 'submergence' => 'flood', 'drought' => 'drought', 'cold' => 'cool'];
        foreach (self::book($book[1]) as $r) {
            $w = (string) ($r['stress'][$stress] ?? '');
            if (! isset($rank[$w]) && isset($bredFor[$stress]) && in_array($bredFor[$stress], (array) ($r['envs'] ?? []), true)) {
                $w = 'moderately tolerant';
            }
            if (! isset($rank[$w])) {
                continue;
            }
            $out[] = ['name' => $r['bookName'] ?: $r['name'], 'aliases' => array_values(array_filter(array_merge([$r['localName'] ?? null], $r['aliases'] ?? []))),
                'rating' => $w, 'days' => $r['days'] ?? null, 'year' => $r['year'] ?? null, 'r' => $rank[$w]];
        }
        usort($out, fn ($a, $b) => [$b['r'], $b['year'] ?? 0] <=> [$a['r'], $a['year'] ?? 0]);

        return array_map(function ($x) {
            unset($x['r']);

            return $x;
        }, array_slice($out, 0, $limit));
    }

    /**
     * A row's facts as a record carries them (`trials`): numbers as
     * numbers, words as words, nothing that names a source and no sourceRef.
     */
    public static function trials(array $r): array
    {
        $f = (array) ($r['facts'] ?? []);

        return array_filter([
            'days' => $r['days'] ?? null,
            'daysBy' => $f['daysBy'] ?? null,
            'heightCm' => $r['heightCm'] ?? null,
            'yield' => array_filter(['avg' => $r['yieldAvg'] ?? null, 'top' => $r['yieldTop'] ?? null] + (array) ($f['yieldBy'] ?? []) + ['unit' => $f['yieldUnit'] ?? 't/ha', 'scope' => $f['yieldScope'] ?? null], fn ($v) => $v !== null),
            'advantage' => $f['advantage'] ?? null,
            'tests' => $f['tests'] ?? null,
            'tested' => $f['tested'] ?? null,
            'ratings' => $f['ratings'] ?? null,
            'stress' => $r['stress'] ?? null,
            'grain' => $f['grain'] ?? null,
            'traits' => $f['traits'] ?? null,
            'areas' => $f['areas'] ?? null,
            'islands' => $f['islands'] ?? null,
            'envs' => $r['envs'] ?? null,
            'seasonFit' => $r['seasonFit'] ?? null,
            'limits' => $f['limits'] ?? null,
            'type' => $r['type'] ?? null,
            'company' => $r['company'] ?? null,
            'year' => $r['year'] ?? null,
            'notes' => $r['notes'] ?? null,
            'conflict' => $r['conflicts'] ?? null,
            // Every name the papers know it by (the registered number with its year, the local and line names), for matching.
            'names' => array_values(array_filter(array_unique(array_merge([$r['name'] ?? null, $r['regNo'] ?? null, $r['localName'] ?? null], (array) ($r['aliases'] ?? []))))),
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /**
     * The trial facts in words for a prompt. Short (the default): one line
     * of what decides a plan (days and yield by season, the advantage over
     * the check, the weak and strong ratings, the stresses, the season and
     * the limits). Full: every test, every rating, the grain, the areas.
     */
    public static function words(array $t, bool $full = false, string $daysUnit = 'days'): string
    {
        if (! $t) {
            return '';
        }
        $n = fn ($x) => rtrim(rtrim(number_format((float) $x, 2, '.', ''), '0'), '.');
        $bits = [];
        $by = (array) ($t['daysBy'] ?? []);
        if (! empty($t['days']) || $by) {
            $d = ! empty($t['days']) ? $n($t['days']) . ' ' . $daysUnit : '';
            $s = array_filter([isset($by['dry']) ? 'dry ' . $n($by['dry']) : null, isset($by['wet']) ? 'wet ' . $n($by['wet']) : null]);
            $bits[] = $d !== '' ? trim($d . ($s ? ' (' . implode(', ', $s) . ')' : '')) : $daysUnit . ' ' . implode(', ', $s);
        }
        $y = (array) ($t['yield'] ?? []);
        $u = (string) ($y['unit'] ?? 't/ha');
        if ($y) {
            $parts = [];
            if (isset($y['avg'])) {
                $parts[] = $n($y['avg']) . ' ' . $u . ' average';
            }
            foreach (['dry' => 'dry', 'wet' => 'wet'] as $k => $lab) {
                if (isset($y[$k])) {
                    $parts[] = ($parts ? '' : $u . ' ') . $lab . ' ' . $n($y[$k]);
                }
            }
            foreach ((array) ($y['islands'] ?? []) as $isl => $v) {
                if (count($parts) < 6 && is_numeric($v)) {
                    $parts[] = $isl . ' ' . $n($v);
                }
            }
            if (isset($y['top'])) {
                $parts[] = ($parts ? '' : $u . ' ') . 'best test ' . $n($y['top']);
            }
            if ($parts) {
                // A regional release: its means are one island group's.
                $bits[] = implode(', ', $parts) . (! empty($y['scope']) ? ' (' . $y['scope'] . ' trials)' : '');
            }
        }
        foreach (array_slice((array) ($t['advantage'] ?? []), 0, $full ? 4 : 2) as $a) {
            if (isset($a['pct'])) {
                $bits[] = $n(abs((float) $a['pct'])) . '% ' . ($a['pct'] >= 0 ? 'more' : 'less') . ' than ' . ($a['over'] ?? 'the check') . (! empty($a['season']) && $a['season'] !== 'across' ? ' in the ' . $a['season'] . ' season' : '');
            }
        }
        if (! empty($t['tested'])) {
            $te = (array) $t['tested'];
            $w = array_filter([! empty($te['seasons']) ? $te['seasons'] . ((int) $te['seasons'] === 1 ? ' season' : ' seasons') : null,
                ! empty($te['locations']) ? $te['locations'] . ((int) $te['locations'] === 1 ? ' site' : ' sites') : null, $te['years'] ?? null]);
            if ($w) {
                $bits[] = 'tried ' . implode(', ', $w);
            }
        }
        if (! empty($t['heightCm'])) {
            $bits[] = $n($t['heightCm']) . ' cm tall';
        }
        $rat = (array) ($t['ratings'] ?? []);
        if ($rat) {
            $bits[] = 'ratings ' . implode(', ', array_map(fn ($k, $c) => $k . ' ' . $c, array_keys($rat), $rat));
        }
        $st = array_filter((array) ($t['stress'] ?? []));
        if ($st) {
            $bits[] = implode(', ', array_map(fn ($k, $w) => $k . ' ' . $w, array_keys($st), $st));
        }
        if (! empty($t['seasonFit']) && $t['seasonFit'] !== 'both') {
            $bits[] = 'better in the ' . $t['seasonFit'] . ' season';
        }
        if ($full) {
            $g = (array) ($t['grain'] ?? []);
            if ($g) {
                $bits[] = 'quality: ' . implode(', ', array_map(fn ($k, $v) => $k . ' ' . (is_array($v) ? implode(' ', $v) : $v), array_keys($g), $g));
            }
            if (! empty($t['traits'])) {
                $bits[] = implode(', ', array_slice((array) $t['traits'], 0, 6));
            }
            if (! empty($t['areas'])) {
                $bits[] = 'recommended for ' . $t['areas'];
            }
            foreach (array_slice(array_values(array_filter((array) ($t['tests'] ?? []), fn ($x) => ($x['season'] ?? '') !== 'across')), 0, 4) as $x) {
                if (isset($x['yield'])) {
                    $bits[] = 'at ' . ($x['where'] ?? 'one site') . (! empty($x['season']) ? ' (' . $x['season'] . ')' : '') . ' ' . $n($x['yield']) . ' ' . $u;
                }
            }
        } elseif (! empty($t['traits'])) {
            $bits[] = implode(', ', array_slice((array) $t['traits'], 0, 3));
        }
        if (! empty($t['limits'])) {
            $bits[] = 'watch: ' . implode(', ', array_slice((array) $t['limits'], 0, $full ? 4 : 2));
        }

        return implode('; ', array_filter($bits));
    }

    /* ======================================================== the load */

    /**
     * Load the seed file into the table, in batches, by (crop, nameKey):
     * new rows go in, changed rows are replaced, rows gone from the file are
     * retired (status 0). Idempotent; returns counts.
     */
    public static function sync(?string $file = null, int $batch = 100): array
    {
        $rows = self::fileRows($file);
        if (! $rows || ! self::schema()->hasTable(self::TABLE)) {
            return ['rows' => 0, 'written' => 0, 'retired' => 0];
        }
        $now = now()->format('Y-m-d H:i:s');
        $db = self::db();
        $have = [];
        foreach ($db->table(self::TABLE)->get(['crop', 'nameKey', 'hash', 'status']) as $h) {
            $have[$h->crop . '|' . $h->nameKey] = (int) $h->status === 1 ? $h->hash : '';
        }
        $keys = [];
        $todo = [];
        foreach ($rows as $i => $r) {
            $rec = self::encode($r, $i);
            $keys[$rec['crop'] . '|' . $rec['nameKey']] = true;
            if (($have[$rec['crop'] . '|' . $rec['nameKey']] ?? null) === $rec['hash']) {
                continue;
            }
            $todo[] = $rec + ['created_at' => $now, 'updated_at' => $now];
        }
        $cols = array_keys(self::encode($rows[0], 0));
        $cols = array_values(array_diff($cols, ['crop', 'nameKey']));
        $cols[] = 'updated_at';
        foreach (array_chunk($todo, $batch) as $chunk) {
            $db->table(self::TABLE)->upsert($chunk, ['crop', 'nameKey'], $cols);
        }
        $retired = 0;
        foreach ($db->table(self::TABLE)->where('status', 1)->get(['id', 'crop', 'nameKey']) as $r) {
            if (! isset($keys[$r->crop . '|' . $r->nameKey])) {
                $retired += $db->table(self::TABLE)->where('id', $r->id)->update(['status' => 0, 'updated_at' => $now]);
            }
        }
        self::forget();

        return ['rows' => count($rows), 'written' => count($todo), 'retired' => $retired];
    }

    /** A seed row as table columns. */
    private static function encode(array $r, int $i): array
    {
        $j = fn ($v) => $v === null || $v === [] ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $rec = [
            'crop' => mb_substr((string) $r['crop'], 0, 40),
            'nameKey' => mb_substr((string) $r['nameKey'], 0, 120),
            'name' => mb_substr((string) $r['name'], 0, 120),
            'localName' => isset($r['localName']) ? mb_substr((string) $r['localName'], 0, 120) : null,
            'aliases' => $j(array_values((array) ($r['aliases'] ?? []))),
            'regNo' => isset($r['regNo']) ? mb_substr((string) $r['regNo'], 0, 60) : null,
            'year' => $r['year'] ?? null,
            'type' => isset($r['type']) ? mb_substr((string) $r['type'], 0, 24) : null,
            'kind' => isset($r['kind']) ? mb_substr((string) $r['kind'], 0, 60) : null,
            'company' => isset($r['company']) ? mb_substr((string) $r['company'], 0, 120) : null,
            'envs' => $j($r['envs'] ?? null),
            'seasonFit' => $r['seasonFit'] ?? null,
            'days' => $r['days'] ?? null,
            'heightCm' => $r['heightCm'] ?? null,
            'yieldAvg' => $r['yieldAvg'] ?? null,
            'yieldTop' => $r['yieldTop'] ?? null,
            'stress' => $j($r['stress'] ?? null),
            // Rated tolerant of salt, or released for salt affected fields.
            'salinityTolerant' => in_array($r['stress']['salinity'] ?? '', ['tolerant', 'highly tolerant', 'moderately tolerant'], true) || in_array('saline', (array) ($r['envs'] ?? []), true) ? 1 : 0,
            'facts' => $j($r['facts'] ?? null),
            'notes' => $r['notes'] ?? null,
            'bookName' => isset($r['bookName']) ? mb_substr((string) $r['bookName'], 0, 120) : null,
            'conflicts' => $j($r['conflicts'] ?? null),
            'quality' => (int) ($r['quality'] ?? 0),
            'sourceRef' => isset($r['ref']) ? mb_substr((string) $r['ref'], 0, 80) : null,
            'sortOrder' => $i,
            'status' => 1,
        ];
        $rec['hash'] = md5(json_encode($rec));

        return $rec;
    }

    private static function decode(object $r): array
    {
        $j = fn ($v) => is_string($v) ? (json_decode($v, true) ?: []) : (array) ($v ?? []);

        return [
            'crop' => (string) $r->crop, 'nameKey' => (string) $r->nameKey, 'name' => (string) $r->name, 'localName' => $r->localName ?: null,
            'aliases' => array_values(array_filter((array) $j($r->aliases), 'is_string')), 'regNo' => $r->regNo ?: null,
            'year' => $r->year !== null ? (int) $r->year : null, 'type' => $r->type ?: null, 'kind' => $r->kind ?: null, 'company' => $r->company ?: null,
            'envs' => (array) $j($r->envs), 'seasonFit' => $r->seasonFit ?: null,
            'days' => $r->days !== null ? (float) $r->days : null, 'heightCm' => $r->heightCm !== null ? (float) $r->heightCm : null,
            'yieldAvg' => $r->yieldAvg !== null ? (float) $r->yieldAvg : null, 'yieldTop' => $r->yieldTop !== null ? (float) $r->yieldTop : null,
            'stress' => (array) $j($r->stress), 'facts' => (array) $j($r->facts), 'notes' => $r->notes ?: null,
            'bookName' => $r->bookName ?: null, 'conflicts' => (array) $j($r->conflicts), 'quality' => (int) $r->quality,
        ];
    }
}
