<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Varieties Anee has looked up, kept (2026-10-18, the owner: "what you get,
 * save it in the db as well so that next time, it won't need anee to research
 * online for that ... do this for all other modules where anee needs to
 * research for a product or variety").
 *
 * The curated books (App\Support\RiceVarieties, App\Support\CropVarieties,
 * read together by App\Support\VarietyBook) hold what the registry and the
 * sellers publish. A variety a farmer names that is not in them used to be
 * searched on the web by every module, every time. Now the research brief of
 * a module asks for a fixed block of facts at the end of its notes
 * (askBlock()), the facts are read back (harvest()) and kept here, and every
 * module asks VarietyBook::find() first: a variety kept here is known, its
 * facts go to the prompt, and nothing is searched again (fewer tokens, no
 * search fee, the same facts every time).
 *
 * Rules:
 *  - Only a variety the research FOUND, with the web read (searched notes and
 *    their sources), a name that is a variety's name (never "seeds from Mang
 *    Ben" or "hybrid lang"), and at least one hard fact (days, a yield, an
 *    NSIC number, or the company and the type) is kept.
 *  - A curated record is never changed. When the research shows that the
 *    name the farmer typed is a variety already on the record, a row with
 *    aliasOf says so, and the name finds that record from then on.
 *  - A row Anee learned before is filled in, and refreshed when it has gone
 *    stale (a year, STALE_DAYS); a row the mother app has reviewed keeps its
 *    facts and only gains names.
 *  - Sources are kept for audit and the mother app, never shown to farmers.
 *
 * Read through App\Support\Concerns\ShelfConnection, so a rehearsal can
 * point it at another database (config('climate.connection')).
 */
final class VarietyLearned
{
    use Concerns\ShelfConnection;

    public const TABLE = 'as_learned_varieties';

    /** How long a learned row is trusted before the next run looks it up again. */
    public const STALE_DAYS = 365;

    /** Rows by crop family, per request. */
    private static array $rows = [];

    public static function ready(): bool
    {
        return (bool) Cache::remember('learned-varieties:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable(self::TABLE));
    }

    /** The crop a row is kept under: rice by any method is rice; corn without a kind is yellow corn; the rest keep the app's key. */
    public static function family(?string $cropKey): string
    {
        $k = mb_strtolower(trim((string) $cropKey));
        if ($k === '') {
            return '';
        }
        if (str_starts_with($k, 'rice')) {
            return 'rice';
        }

        return mb_substr($k === 'corn' ? 'corn_yellow' : $k, 0, 40);
    }

    /**
     * Whether a typed name can be a variety's name at all. A nickname, a
     * seller's name, a type word or "I don't know" is not, and is never kept.
     */
    public static function nameable(?string $name): bool
    {
        $n = trim((string) $name);
        if (mb_strlen($n) < 2 || mb_strlen($n) > 80 || ! preg_match('/[a-z]/i', $n)) {
            return false;
        }
        $low = mb_strtolower($n);
        // Only type words and the crop's name ("hybrid rice", "certified seeds") say nothing of which variety.
        $rest = preg_replace('/\b(hybrids?|inbreds?|open|pollinated|f1|rice|palay|corn|mais|seeds?|binhi|certified|registered|good|sample|local|native|traditional|variety|varieties|yellow|white|sweet|glutinous|the|a|of|and|new|best|high|yielding|special|unknown|none|wala|any|test|n\/?a)\b/u', ' ', $low);
        if (strlen((string) preg_replace('/[^a-z0-9]+/', '', (string) $rest)) < 2) {
            return false;
        }

        return ! preg_match('/\b(mang|aling|kuya|ate|lolo|lola|tatay|nanay|kapitbahay|neighbou?r|seeds? (from|ni|of)|from (my|our|the|a)|galing|binhi ni|sariling|own seeds?|saved seeds?|my own|not sure|di ko alam|hindi ko alam|hindi alam|kahit ano|hybrid lang|inbred lang|farmer\'?s? own|tira|mixed seeds?|halo)\b/u', $low);
    }

    /** The lookup keys of a name, the same as the curated book's ("NSIC Rc 222" gives nsicrc222 and rc222). */
    public static function keys(?string $name): array
    {
        return VarietyBook::nameKeys((string) $name);
    }

    /** Every live row of a crop family, decoded; one query per request and crop. */
    public static function rowsOf(string $family): array
    {
        if ($family === '' || ! self::ready()) {
            return [];
        }
        $id = self::conn() . ':' . $family;
        if (! isset(self::$rows[$id])) {
            try {
                self::$rows[$id] = self::db()->table(self::TABLE)->where('crop', $family)->where('status', 1)->orderBy('id')->get()
                    ->map(fn ($r) => self::decode($r))->all();
            } catch (\Throwable $e) {
                report($e);
                self::$rows[$id] = [];
            }
        }

        return self::$rows[$id];
    }

    /** Whether a learned row is past its year (a reviewed row never is; an alias of a record never is). */
    public static function isStale(array $row): bool
    {
        return ! $row['reviewed'] && ! $row['aliasOf'] && $row['staleAt'] !== null && strtotime($row['staleAt']) < time();
    }

    /**
     * The learned row a written name means, or null. `$fresh` skips a row
     * past its year, so the module looks the variety up again (and learn()
     * refreshes the row).
     */
    public static function lookup(?string $cropKey, ?string $name, bool $fresh = true): ?array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        $rows = self::rowsOf(self::family($cropKey));
        if (! $rows) {
            return null;
        }
        $index = [];
        foreach ($rows as $r) {
            foreach (array_merge([$r['name']], $r['aliases'], $r['nsic'] ? [$r['nsic']] : []) as $n) {
                foreach (self::keys($n) as $k) {
                    $index[$k] ??= $r;
                }
            }
        }
        $parts = array_merge([$name], preg_split('/\s*[()\/,;]\s*|\s+or\s+|\s+aka\s+/i', $name) ?: []);
        foreach ($parts as $part) {
            foreach (self::keys($part) as $k) {
                if (isset($index[$k])) {
                    return $fresh && self::isStale($index[$k]) ? null : $index[$k];
                }
            }
        }

        return null;
    }

    /** One more module read the row instead of searching. Best effort. */
    public static function hit(int $id): void
    {
        if ($id < 1 || ! self::ready()) {
            return;
        }
        try {
            self::db()->table(self::TABLE)->where('id', $id)->update(['hits' => DB::raw('hits + 1'), 'lastHitAt' => now()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* ======================================================== the research */

    /**
     * The lines a research brief ends with: one block of facts for each
     * variety named, in a fixed form the app reads back (parse()). Rice also
     * gets the seed preparation lines the Land Prep Planner times.
     */
    public static function askBlock(array $names, ?string $cropKey): string
    {
        $names = array_values(array_unique(array_filter(array_map(fn ($n) => trim(str_replace('"', '', (string) $n)), $names))));
        if (! $names) {
            return '';
        }
        $rice = str_starts_with((string) $cropKey, 'rice');
        $seed = $rice
            ? "SOAK_HOURS: the seed company's own soaking hours, a number or a range like 24 to 48\nWATER_CHANGE_HOURS: how often to change the soaking water, in hours\nINCUBATE_HOURS: the company's own incubation (sprouting) hours, a number or a range\nSEED_NOTES: one or two plain sentences of the company's own seed instructions\n"
            : "SEED_NOTES: one or two plain sentences of the seller's own seed or planting instructions\n";

        return "\nTHE VARIETY FACTS, FOR THE APP TO KEEP: at the very end of your notes, for each of these varieties (" . implode(', ', array_map(fn ($n) => '"' . $n . '"', $names)) . '), write one block exactly in this form, each line filled from the pages you read. '
            . "Write unknown for anything the pages do not say. Never guess, and never fill in a general practice or another variety's figure as if it were this one's. FOUND is yes only when the pages describe this exact variety.\n"
            . "VARIETY: the name as given above\nFOUND: yes or no\nNAME: its registered or published name\nOTHER_NAMES: the names it is sold under, separated by commas\nNSIC: its registration number (like NSIC 2019 Rc 600), or unknown\n"
            . "COMPANY: the breeder or seed company\nTYPE: hybrid, inbred, open pollinated, or unknown\nRELEASED: the year it was released or registered\nMATURITY_DAYS: days from sowing to harvest, a number or a range like 110 to 115\n"
            . "YIELD_AVERAGE_T_HA: its average yield in tons per hectare\nYIELD_TOP_T_HA: its highest published yield in tons per hectare\nHEIGHT_CM: plant height in cm\nSEASON: the season or growing environment it is bred or recommended for\n"
            . "RESISTANCE: the pests and diseases it resists or is weak against, with the published rating\nTOLERANCE: drought, flooding, salinity, heat, cold or lodging, as published\nGRAIN: grain or fruit quality in a few words\n"
            . "SEED_RATE_KG_HA: seed per hectare in kg, a number or a range\n" . $seed
            . "CAUTION: its known weakness, in a few words\nSOURCES: the names of the pages or agencies you read for it\nEND\n";
    }

    /**
     * The fact blocks in research notes: [['VARIETY' => ..., 'FOUND' => ...,
     * ...], ...]. Markdown marks are stripped; "unknown" reads as empty.
     */
    public static function parse(string $text): array
    {
        $blocks = [];
        $cur = null;
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim((string) preg_replace(['/[*`#>]+/', '/^\s*[-•]\s+/u'], ['', ''], $line));
            if (! preg_match('/^([A-Z][A-Z_ ]{1,30})\s*:\s*(.*)$/u', $line, $m)) {
                if ($cur !== null && strtoupper($line) === 'END') {
                    $blocks[] = $cur;
                    $cur = null;
                }
                continue;
            }
            $k = str_replace(' ', '_', strtoupper(trim($m[1])));
            $v = trim($m[2], " \t.");
            if (preg_match('/^(unknown|not (stated|given|found|published|available)|n\/?a|none( found)?|-)$/i', $v)) {
                $v = '';
            }
            if ($k === 'VARIETY') {
                if ($cur !== null) {
                    $blocks[] = $cur;
                }
                $cur = ['VARIETY' => $v];
                continue;
            }
            if ($cur !== null && ! isset($cur[$k])) {
                $cur[$k] = $v;
            }
        }
        if ($cur !== null) {
            $blocks[] = $cur;
        }

        return $blocks;
    }

    /** A parsed block as plain facts (numbers checked, words cleaned, links gone). */
    public static function facts(array $b, ?string $cropKey = null): array
    {
        $rice = str_starts_with((string) $cropKey, 'rice');
        $clean = fn ($v, int $max) => mb_substr(trim((string) preg_replace(['#\[([^\]]*)\]\([^)]*\)#', '#\s*\(?\s*https?://[^\s)]*\)?#', '/\s+[\x{2013}\x{2014}]\s+|\s+-\s+/u', '/\s{2,}/'],
            ['$1', '', ', ', ' '], strip_tags((string) $v)), " ,;"), 0, $max);
        $range = function ($v, float $lo, float $hi): ?array {
            $v = str_replace(',', '', (string) $v);
            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:to|-|\x{2013}|and)\s*(\d+(?:\.\d+)?)/u', $v, $m)) {
                [$a, $b] = [(float) $m[1], (float) $m[2]];
            } elseif (preg_match('/(\d+(?:\.\d+)?)/', $v, $m)) {
                $a = $b = (float) $m[1];
            } else {
                return null;
            }
            [$a, $b] = [min($a, $b), max($a, $b)];

            return $a >= $lo && $b <= $hi ? [$a, $b] : null;
        };
        $tons = function ($v) use ($range): ?float {
            $s = mb_strtolower((string) $v);
            $r = $range($s, 0.05, 2000);
            if (! $r) {
                return null;
            }
            $x = ($r[0] + $r[1]) / 2;
            if (str_contains($s, 'kg/ha') || str_contains($s, 'kg per ha') || str_contains($s, 'kilograms per')) {
                $x /= 1000;
            } elseif (str_contains($s, 'cavan')) {
                $x *= preg_match('/(\d+)\s*(?:kg|kilo)/', $s, $k) && (int) $k[1] >= 30 && (int) $k[1] <= 60 ? (int) $k[1] / 1000 : 0.05;
            }

            return $x > 0 && $x <= 150 ? round($x, 2) : null;
        };
        $type = mb_strtolower((string) ($b['TYPE'] ?? ''));
        $days = $range($b['MATURITY_DAYS'] ?? '', 20, 420);
        $year = preg_match('/\b(19[5-9]\d|20[0-4]\d)\b/', (string) ($b['RELEASED'] ?? ''), $y) ? (int) $y[1] : null;
        // A registration number as written ("NSIC 2019 Rc 600"): letters and digits, short.
        $nsicRaw = $clean($b['NSIC'] ?? '', 40);
        $nsic = preg_match('/[a-z]/i', $nsicRaw) && preg_match('/\d/', $nsicRaw) ? $nsicRaw : '';
        $other = array_values(array_filter(array_map(fn ($x) => $clean($x, 60), preg_split('/\s*[,;]\s*|\s+or\s+/i', (string) ($b['OTHER_NAMES'] ?? '')) ?: []),
            fn ($x) => $x !== '' && self::nameable($x)));
        $refresh = $range($b['WATER_CHANGE_HOURS'] ?? '', 2, 24);

        return [
            'asked' => $clean($b['VARIETY'] ?? '', 80),
            'found' => str_starts_with(mb_strtolower((string) ($b['FOUND'] ?? '')), 'yes'),
            'name' => $clean($b['NAME'] ?? '', 120),
            'other' => array_slice($other, 0, 8),
            'nsic' => $nsic,
            'company' => $clean($b['COMPANY'] ?? '', 120),
            'type' => str_contains($type, 'hybrid') ? 'hybrid' : (str_contains($type, 'inbred') ? 'inbred' : (str_contains($type, 'open') ? 'open' : '')),
            'year' => $year,
            'days' => $days ? (int) round(($days[0] + $days[1]) / 2) : null,
            'daysRange' => $days ? [(int) $days[0], (int) $days[1]] : null,
            'yieldAvg' => $tons($b['YIELD_AVERAGE_T_HA'] ?? $b['YIELD_AVERAGE'] ?? ''),
            'yieldTop' => $tons($b['YIELD_TOP_T_HA'] ?? $b['YIELD_TOP'] ?? ''),
            'height' => ($h = $range($b['HEIGHT_CM'] ?? '', 10, 600)) ? (int) round(($h[0] + $h[1]) / 2) : null,
            'season' => $clean($b['SEASON'] ?? '', 160),
            'reactions' => $clean($b['RESISTANCE'] ?? '', 400),
            'tolerances' => $clean($b['TOLERANCE'] ?? '', 400),
            'grain' => $clean($b['GRAIN'] ?? '', 300),
            'seedRate' => ($s = $range($b['SEED_RATE_KG_HA'] ?? '', 0.1, 250)) ? [$s[0], $s[1]] : null,
            'soak' => $rice && ($x = $range($b['SOAK_HOURS'] ?? '', 1, 72)) ? [(int) round($x[0]), (int) round($x[1])] : null,
            'refresh' => $rice && $refresh ? (int) round($refresh[0]) : null,
            'incubate' => $rice && ($x = $range($b['INCUBATE_HOURS'] ?? '', 6, 72)) ? [(int) round($x[0]), (int) round($x[1])] : null,
            'seedNotes' => $clean($b['SEED_NOTES'] ?? '', 320),
            'caution' => $clean($b['CAUTION'] ?? '', 300),
            'sourceNote' => $clean($b['SOURCES'] ?? '', 255),
        ];
    }

    /**
     * Keep what a module's research found about the varieties it asked
     * about. $asked: the names the brief asked for; $meta: via (the module),
     * userId, searched (the notes came from the web), sources (the pages).
     * Returns [asked name => what was kept: ['id', 'name'] or ['aliasOf']].
     */
    public static function harvest(?string $cropKey, array $asked, string $notes, array $meta): array
    {
        $out = [];
        if (! $asked || trim($notes) === '' || empty($meta['searched'])) {
            return $out;
        }
        $wanted = [];
        foreach ($asked as $a) {
            foreach (self::keys((string) $a) as $k) {
                $wanted[$k] = (string) $a;
            }
        }
        foreach (self::parse($notes) as $b) {
            $f = self::facts($b, $cropKey);
            $name = null;
            foreach (array_merge(self::keys($f['asked']), self::keys($f['name'])) as $k) {
                if (isset($wanted[$k])) {
                    $name = $wanted[$k];
                    break;
                }
            }
            if ($name === null && count($asked) === 1) {
                $name = (string) $asked[0];
            }
            if ($name === null) {
                continue;
            }
            try {
                $kept = self::learn($cropKey, $name, $f, $meta);
            } catch (\Throwable $e) {
                report($e);
                $kept = null;
            }
            if ($kept) {
                $out[$name] = $kept;
            }
        }

        return $out;
    }

    /**
     * Keep one variety's facts (see the rules at the top). Returns
     * ['id' => row id, 'name' => kept name] for a learned row, ['aliasOf' =>
     * the record's name] when the name is a variety already on the record,
     * or null when nothing was kept.
     */
    public static function learn(?string $cropKey, string $asked, array $f, array $meta): ?array
    {
        $family = self::family($cropKey);
        $asked = trim($asked);
        $published = trim((string) ($f['name'] ?? '')) ?: $asked;
        $sources = array_values(array_filter((array) ($meta['sources'] ?? []), 'is_array'));
        if ($family === '' || ! self::ready() || empty($f['found']) || empty($meta['searched']) || (! $sources && trim((string) ($f['sourceNote'] ?? '')) === '')) {
            return null;
        }
        if (! self::nameable($asked) || ! self::nameable($published)) {
            return null;
        }
        $hard = ! empty($f['days']) || ! empty($f['yieldAvg']) || ! empty($f['yieldTop']) || ! empty($f['nsic']) || (! empty($f['company']) && ! empty($f['type']));
        if (! $hard) {
            return null;
        }
        $via = mb_substr((string) ($meta['via'] ?? ''), 0, 24);
        $now = now();
        $enc = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $srcList = array_slice(array_map(fn ($s) => array_filter(['title' => mb_substr((string) ($s['title'] ?? ''), 0, 160), 'url' => mb_substr((string) ($s['url'] ?? ''), 0, 400)]), $sources), 0, 12);
        $names = array_values(array_unique(array_filter(array_merge([$asked, $published], (array) ($f['other'] ?? [])), fn ($n) => self::nameable($n))));

        // A variety already on the curated record: never changed, the name only points at it.
        $record = null;
        foreach (array_merge([$published, (string) ($f['nsic'] ?? '')], (array) ($f['other'] ?? [])) as $n) {
            if ($n !== '' && ($record = VarietyBook::match($cropKey, $n))) {
                break;
            }
        }
        if ($record) {
            if (VarietyBook::match($cropKey, $asked)) {
                return ['aliasOf' => $record['name']];
            }
            $key = self::keys($asked)[0] ?? '';
            if ($key === '') {
                return null;
            }
            self::db()->table(self::TABLE)->upsert([[
                'crop' => $family, 'name' => mb_substr($asked, 0, 120), 'nameKey' => mb_substr($key, 0, 120), 'aliases' => $enc([]), 'aliasOf' => mb_substr($record['name'], 0, 120),
                'sources' => $enc($srcList), 'confidence' => 'high', 'foundBy' => $via, 'userId' => $meta['userId'] ?? null, 'status' => 1, 'staleAt' => null,
                'facts' => $enc(['sourceNote' => $f['sourceNote'] ?? '', 'published' => $published]), 'created_at' => $now, 'updated_at' => $now,
            ]], ['crop', 'nameKey'], ['aliasOf', 'sources', 'foundBy', 'status', 'updated_at']);
            self::forget();

            return ['aliasOf' => $record['name']];
        }

        $conf = (! empty($f['nsic']) && ! empty($f['days'])) || (count($sources) >= 3 && ! empty($f['days']) && ! empty($f['company'])) ? 'high' : 'medium';
        $seed = array_filter(['soak' => $f['soak'] ?? null, 'refresh' => $f['refresh'] ?? null, 'incubate' => $f['incubate'] ?? null, 'rate' => $f['seedRate'] ?? null,
            'notes' => ($f['seedNotes'] ?? '') ?: null], fn ($v) => $v !== null && $v !== '');
        $facts = array_filter(['daysRange' => $f['daysRange'] ?? null, 'sourceNote' => $f['sourceNote'] ?? '', 'published' => $published], fn ($v) => $v !== null && $v !== '');
        $cols = array_filter([
            'nsic' => ($f['nsic'] ?? '') ?: null, 'company' => ($f['company'] ?? '') ?: null, 'type' => ($f['type'] ?? '') ?: null, 'year' => $f['year'] ?? null,
            'maturityDays' => $f['days'] ?? null, 'yieldAvg' => $f['yieldAvg'] ?? null, 'yieldTop' => $f['yieldTop'] ?? null, 'heightCm' => $f['height'] ?? null,
            'season' => ($f['season'] ?? '') ?: null, 'reactions' => ($f['reactions'] ?? '') ?: null, 'tolerances' => ($f['tolerances'] ?? '') ?: null,
            'grain' => ($f['grain'] ?? '') ?: null, 'caution' => ($f['caution'] ?? '') ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        // A row learned before, by any of the names: filled in, or refreshed when stale; a reviewed row only gains names.
        $old = null;
        foreach (array_merge($names, ! empty($f['nsic']) ? [$f['nsic']] : []) as $n) {
            if ($old = self::lookup($cropKey, $n, false)) {
                break;
            }
        }
        if ($old && ! $old['aliasOf']) {
            $aliases = array_values(array_unique(array_filter(array_merge($old['aliases'], $names), fn ($n) => self::flat($n) !== self::flat($old['name']))));
            $up = ['aliases' => $enc(array_slice($aliases, 0, 24)), 'updated_at' => $now];
            if (! $old['reviewed']) {
                $refresh = self::isStale($old) || ($conf === 'high' && $old['confidence'] !== 'high');
                foreach ($cols as $k => $v) {
                    if ($refresh || $old[$k] === null || $old[$k] === '') {
                        $up[$k] = $v;
                    }
                }
                $up['seed'] = $enc($refresh ? ($seed + $old['seed']) : ($old['seed'] + $seed));
                $up['facts'] = $enc($refresh ? ($facts + $old['facts']) : ($old['facts'] + $facts));
                $up['sources'] = $enc(array_slice(array_values(array_unique(array_merge($srcList, $old['sources']), SORT_REGULAR)), 0, 16));
                $up['confidence'] = $conf === 'high' ? 'high' : $old['confidence'];
                $up['foundBy'] = $old['foundBy'] !== '' && ! str_contains($old['foundBy'], $via) ? mb_substr($old['foundBy'] . ',' . $via, 0, 24) : ($old['foundBy'] ?: $via);
                if ($refresh) {
                    $up['staleAt'] = $now->copy()->addDays(self::STALE_DAYS);
                }
            }
            self::db()->table(self::TABLE)->where('id', $old['id'])->update($up);
            self::forget();

            return ['id' => $old['id'], 'name' => $old['name']];
        }

        $key = self::keys($published)[0] ?? (self::keys($asked)[0] ?? '');
        if ($key === '') {
            return null;
        }
        $row = $cols + [
            'crop' => $family, 'name' => mb_substr($published, 0, 120), 'nameKey' => mb_substr($key, 0, 120),
            'aliases' => $enc(array_values(array_filter($names, fn ($n) => self::flat($n) !== self::flat($published)))), 'aliasOf' => null,
            'seed' => $enc((object) $seed), 'facts' => $enc((object) $facts), 'sources' => $enc($srcList), 'confidence' => $conf, 'foundBy' => $via,
            'userId' => $meta['userId'] ?? null, 'status' => 1, 'reviewed' => 0, 'hits' => 0, 'staleAt' => $now->copy()->addDays(self::STALE_DAYS),
            'created_at' => $now, 'updated_at' => $now,
        ];
        try {
            $id = (int) self::db()->table(self::TABLE)->insertGetId($row);
        } catch (\Illuminate\Database\QueryException $e) {
            // The same name kept a moment ago by another run (or retired): that row is the answer.
            $id = (int) self::db()->table(self::TABLE)->where('crop', $family)->where('nameKey', mb_substr($key, 0, 120))->value('id');
            if ($id) {
                self::db()->table(self::TABLE)->where('id', $id)->update(['status' => 1, 'updated_at' => $now]);
            }
        }
        self::forget();

        return $id ? ['id' => $id, 'name' => $published] : null;
    }

    public static function forget(): void
    {
        self::$rows = [];
    }

    /* ======================================================== the shapes */

    /** A row as the curated book's record shape (App\Support\VarietyBook::rows), with `learned` set. */
    public static function asRecord(array $r): array
    {
        $type = $r['type'] === 'hybrid' ? 'hybrid' : ($r['crop'] === 'rice' ? 'inbred' : 'other');

        return [
            'name' => $r['name'],
            'aliases' => array_values(array_filter(array_merge($r['nsic'] && self::flat($r['nsic']) !== self::flat($r['name']) ? [$r['nsic']] : [], $r['aliases']))),
            'type' => $type,
            'kind' => $r['type'] === 'open' ? 'open pollinated' : ($r['type'] ?: $type),
            'color' => null,
            'year' => $r['year'],
            'days' => $r['maturityDays'] !== null ? (float) $r['maturityDays'] : null,
            'daysUnit' => 'days',
            'yield' => $r['yieldAvg'],
            'yieldTop' => $r['yieldTop'],
            'yieldUnit' => 't/ha',
            'ratings' => [],
            'envs' => [],
            'domain' => [],
            'seasons' => [],
            'elevation' => [],
            'where' => (string) ($r['season'] ?? ''),
            'registered' => (bool) $r['nsic'],
            'url' => null,
            'seller' => '',
            'learned' => [
                'id' => $r['id'], 'at' => $r['learnedAt'], 'confidence' => $r['confidence'], 'foundBy' => $r['foundBy'], 'company' => $r['company'], 'nsic' => $r['nsic'],
                'height' => $r['heightCm'], 'season' => $r['season'], 'reactions' => $r['reactions'], 'tolerances' => $r['tolerances'], 'grain' => $r['grain'],
                'caution' => $r['caution'], 'seed' => $r['seed'], 'reviewed' => $r['reviewed'],
            ],
        ];
    }

    /**
     * A row as the Land Prep Planner's research result
     * (LandPrepController::research, LandPrepPlanner::seedPrep): found, with
     * the days, type, company and the seed hours, nothing spent.
     */
    public static function asResearch(array $r): array
    {
        $s = $r['seed'];

        return [
            'asked' => true, 'ok' => true, 'found' => true, 'searched' => false, 'kept' => true, 'learnedId' => $r['id'], 'learnedAt' => $r['learnedAt'],
            'tokensIn' => 0, 'tokensOut' => 0,
            'days' => $r['maturityDays'], 'type' => $r['type'] === 'open' ? 'open' : (string) ($r['type'] ?? ''), 'company' => $r['company'],
            'soak' => isset($s['soak']) ? array_map('intval', (array) $s['soak']) : null, 'refresh' => isset($s['refresh']) ? (int) $s['refresh'] : null,
            'incubate' => isset($s['incubate']) ? array_map('intval', (array) $s['incubate']) : null, 'seedNotes' => (string) ($s['notes'] ?? ''),
            'notes' => self::words($r),
        ];
    }

    /** The row in one line of facts for a prompt. */
    public static function words(array $r): string
    {
        $num = fn ($x) => rtrim(rtrim(number_format((float) $x, 2, '.', ''), '0'), '.');
        $bits = [$r['type'] === 'open' ? 'open pollinated' : ($r['type'] ?: 'type not published')];
        if ($r['nsic'] && self::flat($r['nsic']) !== self::flat($r['name'])) {
            $bits[] = $r['nsic'];
        }
        if ($r['aliases']) {
            $bits[] = 'also sold as ' . implode(', ', array_slice($r['aliases'], 0, 3));
        }
        if ($r['company']) {
            $bits[] = 'by ' . $r['company'];
        }
        if ($r['year']) {
            $bits[] = 'released ' . $r['year'];
        }
        if ($r['maturityDays']) {
            $range = (array) ($r['facts']['daysRange'] ?? []);
            $bits[] = ($range && $range[0] !== $range[1] ? $range[0] . ' to ' . $range[1] : $r['maturityDays']) . ' days to harvest';
        }
        if ($r['yieldAvg'] !== null || $r['yieldTop'] !== null) {
            $bits[] = trim(($r['yieldAvg'] !== null ? $num($r['yieldAvg']) . ' t/ha average' : '') . ($r['yieldTop'] !== null ? ($r['yieldAvg'] !== null ? ', ' : '') . $num($r['yieldTop']) . ' t/ha at best' : ''));
        }
        if ($r['heightCm']) {
            $bits[] = $r['heightCm'] . ' cm tall';
        }
        foreach (['season' => 'for ', 'reactions' => 'ratings: ', 'tolerances' => 'tolerance: ', 'grain' => 'grain: ', 'caution' => 'watch: '] as $k => $lead) {
            if (! empty($r[$k])) {
                $bits[] = $lead . $r[$k];
            }
        }
        $s = $r['seed'];
        if (! empty($s['soak'])) {
            $bits[] = 'seed soak ' . implode(' to ', array_unique((array) $s['soak'])) . ' h, incubate ' . (! empty($s['incubate']) ? implode(' to ', array_unique((array) $s['incubate'])) : 'not published') . ' h';
        }
        if (! empty($s['rate'])) {
            $bits[] = 'seed ' . implode(' to ', array_map($num, array_unique((array) $s['rate']))) . ' kg/ha';
        }
        $when = $r['learnedAt'] ? date('M Y', strtotime($r['learnedAt'])) : '';

        return $r['name'] . ' [' . implode('; ', $bits) . '; from Anee\'s own web research' . ($when !== '' ? ' of ' . $when : '') . ', not a registry record]';
    }

    /* ======================================================== internals */

    private static function decode(object $r): array
    {
        $j = fn ($v) => is_string($v) ? (json_decode($v, true) ?: []) : (array) ($v ?? []);

        return [
            'id' => (int) $r->id, 'crop' => (string) $r->crop, 'name' => (string) $r->name, 'nameKey' => (string) $r->nameKey,
            'aliases' => array_values(array_filter((array) $j($r->aliases), 'is_string')), 'aliasOf' => $r->aliasOf ?: null,
            'nsic' => $r->nsic ?: null, 'company' => $r->company ?: null, 'type' => $r->type ?: null, 'year' => $r->year !== null ? (int) $r->year : null,
            'maturityDays' => $r->maturityDays !== null ? (int) $r->maturityDays : null,
            'yieldAvg' => $r->yieldAvg !== null ? (float) $r->yieldAvg : null, 'yieldTop' => $r->yieldTop !== null ? (float) $r->yieldTop : null,
            'heightCm' => $r->heightCm !== null ? (int) $r->heightCm : null, 'season' => $r->season, 'reactions' => $r->reactions, 'tolerances' => $r->tolerances,
            'grain' => $r->grain, 'caution' => $r->caution, 'seed' => (array) $j($r->seed), 'facts' => (array) $j($r->facts), 'sources' => (array) $j($r->sources),
            'confidence' => (string) $r->confidence, 'foundBy' => (string) $r->foundBy, 'reviewed' => (bool) $r->reviewed,
            'staleAt' => $r->staleAt ? (string) $r->staleAt : null, 'learnedAt' => $r->created_at ? (string) $r->created_at : null, 'hits' => (int) $r->hits,
        ];
    }

    private static function flat(?string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower((string) $s));
    }
}
