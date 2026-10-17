<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A place's risk record, kept year by year (2026-10-18, the owner: "whenever
 * a weather history of that area is being checked like 20 year weather
 * history, save that in the database per year or so so that when ai needs
 * to check it in the future online, ai can check first if a history was
 * saved ... since history does not really change at all").
 *
 * When to Plant and the Satellite Analysis used to send Anee to the web for
 * twenty years of a province's storms, floods, droughts and heat on every
 * run. Now the research ends with one line per year in a fixed form, those
 * lines are kept here per province (as_climate_years), and the next run for
 * that province reads them and searches only the years it lacks: none, once
 * the twenty are kept, except the year still running, read again after
 * REFRESH_DAYS. The weather numbers themselves (rain, gusts, heat) come from
 * the climate shelf (App\Support\ClimateStore); this holds what only the
 * news and the disaster reports say: which storm, which flood, how bad.
 *
 * Only searched research is kept. A province is the key: storms and floods
 * are reported by province, and a town's twenty years are its province's.
 *
 * Since 2026-10-18 the hazard book (App\Support\HazardBook) holds every
 * Philippine town's, province's and region's twenty years, measured and
 * reported, built from open data and refreshed twice a month: placeKey()
 * gives the town's key too ('townKey', 'where'), stored() reads the book
 * first, so missing() is empty for every Philippine place and the analyses
 * search for no year of its history; words() then says the book's block.
 * The web research below stays for a place the book does not know.
 */
final class ClimateRecord
{
    use Concerns\ShelfConnection;

    /** A year still running is read again after this many days. */
    public const REFRESH_DAYS = 45;

    private const KINDS = ['storms' => 'storm', 'floods' => 'flood', 'drought' => 'drought', 'heat' => 'heat', 'frost' => 'frost'];

    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    public static function ready(): bool
    {
        return (bool) Cache::remember('climate-record:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable('as_climate_years'));
    }

    /** The years a record covers: the twenty full years before this one, and this one. */
    public static function span(): array
    {
        $to = (int) now('Asia/Manila')->year;

        return [$to - ClimateStore::YEARS, $to];
    }

    /**
     * The province a written place belongs to, as the record's key:
     * ['key' => 'ph:nueva ecija', 'place' => 'Nueva Ecija'].
     */
    public static function placeKey(string $location, ?string $country = null): array
    {
        $country = strtoupper($country ?: Region::code());
        $parts = array_values(array_filter(array_map('trim', explode(',', $location)), fn ($p) => $p !== '' && ! preg_match('/^philippines$/i', $p)));
        if ($country === 'PH' && HazardBook::ready()) {
            // The hazard book's names (the gazetteer every lot uses), down to the town (2026-10-18).
            $w = HazardBook::fromLocation($location);
            $pl = $w['province'] || $w['town'] ? HazardBook::place($w['province'], $w['town']) : null;
            if ($pl) {
                return ['key' => $pl['key'], 'place' => HazardBook::label($pl['province']), 'province' => $pl['province'], 'town' => $pl['town'],
                    'townKey' => $pl['townKey'], 'where' => $pl['where'], 'region' => $pl['region']];
            }
        }
        if ($country === 'PH') {
            static $provinces = null;
            $provinces ??= array_keys((array) json_decode((string) @file_get_contents(public_path('data/ph-locations.json')), true));
            $by = [];
            foreach ($provinces as $p) {
                $by[mb_strtolower($p)] = $p;
            }
            foreach (array_reverse($parts) as $p) {
                $k = mb_strtolower(preg_replace('/\s+province$/i', '', $p));
                if (isset($by[$k])) {
                    return ['key' => 'ph:' . $k, 'place' => $by[$k]];
                }
            }
            $last = end($parts) ?: $location;

            return ['key' => 'ph:' . mb_strtolower(mb_substr($last, 0, 100)), 'place' => mb_substr($last, 0, 160)];
        }
        $tail = implode(', ', array_slice($parts, -2)) ?: $location;

        return ['key' => strtolower($country) . ':' . mb_strtolower(mb_substr($tail, 0, 100)), 'place' => mb_substr($tail, 0, 160)];
    }

    /**
     * The kept years of a place, by year: the hazard book's first (a town's
     * key reads the town, else its province; App\Support\HazardBook), then
     * what web research kept for the years the book lacks.
     */
    public static function stored(string $key): array
    {
        [$from, $to] = self::span();
        $out = [];
        if (str_starts_with($key, 'ph:') || str_starts_with($key, 'ph-region:')) {
            // Every year the book holds (2005 on), as HazardBook::words reads them: a town's
            // record says the same span and the same month counts in every module (2026-10-19).
            foreach (HazardBook::years($key) as $y => $r) {
                if ($y <= $to) {
                    $r->researchedAt = $r->builtAt;
                    $r->searched = false;
                    $r->book = true;
                    $out[$y] = $r;
                }
            }
        }
        if (! self::ready()) {
            return $out;
        }
        $key = str_contains($key, '|') ? strstr($key, '|', true) : $key;
        foreach (self::db()->table('as_climate_years')->where('placeKey', $key)->whereBetween('year', [$from, $to])->orderBy('year')->get() as $r) {
            $out[(int) $r->year] ??= $r;
        }
        ksort($out);

        return $out;
    }

    /** Whether the hazard book holds a place's record (no web search of its history then). */
    public static function inBook(string $key): bool
    {
        return (bool) HazardBook::years($key);
    }

    /**
     * The years still to be read for a place: never read, or read while
     * they were still running and not since REFRESH_DAYS.
     */
    public static function missing(string $key): array
    {
        [$from, $to] = self::span();
        $have = self::stored($key);
        if (! $have && ! self::ready()) {
            return range($from, $to);
        }
        $out = [];
        for ($y = $from; $y <= $to; $y++) {
            $r = $have[$y] ?? null;
            // A year the hazard book holds is never searched: the book refreshes itself (hazards:refresh),
            // and harvest() never writes over it, so a search for its running year was paid on every run.
            if ($r && ! empty($r->book)) {
                continue;
            }
            if (! $r || (! $r->complete && (! $r->researchedAt || now()->diffInDays($r->researchedAt, true) >= self::REFRESH_DAYS))) {
                $out[] = $y;
            }
        }

        return $out;
    }

    /** Years in words: "2006 to 2025 and 2026", or "2026". */
    public static function yearsWords(array $years): string
    {
        sort($years);
        $runs = [];
        foreach ($years as $y) {
            if ($runs && end($runs)[1] === $y - 1) {
                $runs[count($runs) - 1][1] = $y;
            } else {
                $runs[] = [$y, $y];
            }
        }
        $w = array_map(fn ($r) => $r[0] === $r[1] ? (string) $r[0] : $r[0] . ' to ' . $r[1], $runs);

        return count($w) > 1 ? implode(', ', array_slice($w, 0, -1)) . ' and ' . end($w) : ($w[0] ?? '');
    }

    /**
     * The research brief's part that writes the record: one line per year
     * asked, in a fixed form the app can keep.
     */
    public static function ask(string $place, array $years, bool $fieldPH): string
    {
        $span = self::yearsWords($years);
        $frost = $fieldPH ? '' : ' | frost: Month, what happened';
        $rains = $fieldPH ? ' | rains: when PAGASA declared the rainy season\'s onset and its end that year, and any long break' : ' | season: how the growing season behaved (early or late rains or spring, shifted frost dates)';

        return "YEAR BY YEAR ({$span}): end the notes with one line for EACH of these years for {$place}, in exactly this form, plain text, one year per line, nothing between the lines:\n"
            . "YEAR 2013 | storms: Mon Name severe; Mon Name moderate | floods: Mon, cause, severe | drought: Mon to Mon, severe | heat: Mon, moderate{$frost}{$rains}\n"
            . "Months as three letters (Jan, Feb...). Severity is one word: severe, moderate or minor, from the damage to crops reported. Name every tropical cyclone that affected the province that year (a landfall, a close pass, or a wind signal raised over it), with its local and international names. Write none where nothing happened, and unknown where the year could not be found. A year still running: what has happened so far.\n";
    }

    /**
     * Keep the YEAR lines of searched notes for a place. Returns the notes
     * with those lines taken out (the rest still goes to the document) and
     * how many years were kept.
     *
     * @return array{rest: string, kept: int}
     */
    public static function harvest(string $key, string $place, string $country, string $notes, bool $searched, array $sources = []): array
    {
        $kept = 0;
        $rest = [];
        $rows = [];
        [$from, $to] = self::span();
        foreach (preg_split('/\R/', $notes) as $line) {
            // A model's list marks and bold come off: "- **YEAR 2010 | ...**".
            $plain = trim(preg_replace(['/[*_`#>]+/', '/^\s*(?:[-\x{2022}]|\d+[.)])\s*/u'], ['', ''], $line));
            if (! preg_match('/^YEAR\s+(\d{4})\s*[|:]\s*(.+)$/i', $plain, $m)) {
                $rest[] = $line;
                continue;
            }
            $year = (int) $m[1];
            if ($year < $from || $year > $to) {
                continue;
            }
            $rows[$year] = self::parse($year, GovSource::scrub($m[2]));
        }
        // The hazard book is the record of a Philippine place: web research never writes over it.
        $book = str_starts_with($key, 'ph') ? HazardBook::years($key) : [];
        $rows = array_diff_key($rows, $book);
        if ($searched && $rows && self::ready()) {
            $now = now();
            $out = [];
            foreach ($rows as $year => $r) {
                $out[] = ['placeKey' => mb_substr($key, 0, 120), 'place' => mb_substr($place, 0, 160), 'country' => strtoupper(substr($country, 0, 2)), 'year' => $year,
                    'line' => mb_substr($r['line'], 0, 2000), 'events' => json_encode($r['events']), 'storms' => $r['counts']['storm'], 'floods' => $r['counts']['flood'],
                    'droughts' => $r['counts']['drought'], 'heat' => $r['counts']['heat'], 'rains' => $r['rains'] !== null ? mb_substr($r['rains'], 0, 200) : null,
                    'sources' => json_encode(array_slice(array_values(array_unique(array_filter(array_map(fn ($s) => is_array($s) ? (string) ($s['title'] ?? '') : (string) $s, $sources)))), 0, 12)),
                    'searched' => true, 'complete' => $year < (int) now('Asia/Manila')->year, 'researchedAt' => $now, 'created_at' => $now, 'updated_at' => $now];
            }
            try {
                self::db()->table('as_climate_years')->upsert($out, ['placeKey', 'year'], ['place', 'line', 'events', 'storms', 'floods', 'droughts', 'heat', 'rains', 'sources', 'searched', 'complete', 'researchedAt', 'updated_at']);
                $kept = count($out);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return ['rest' => trim(implode("\n", $rest)), 'kept' => $kept, 'years' => array_keys($rows)];
    }

    /** One YEAR line, read: its events by kind and month, the counts, the rains. */
    private static function parse(int $year, string $body): array
    {
        $events = [];
        $counts = ['storm' => 0, 'flood' => 0, 'drought' => 0, 'heat' => 0, 'frost' => 0];
        $rains = null;
        foreach (explode('|', $body) as $part) {
            if (! preg_match('/^\s*([a-z ]+?)\s*:\s*(.*)$/i', $part, $mm)) {
                continue;
            }
            $label = strtolower(trim($mm[1]));
            $text = trim($mm[2]);
            if (in_array($label, ['rains', 'rain', 'season'], true)) {
                $rains = preg_match('/^(none|unknown)\b/i', $text) ? null : $text;
                continue;
            }
            $kind = self::KINDS[$label] ?? (self::KINDS[rtrim($label, 's') . 's'] ?? (str_starts_with($label, 'storm') ? 'storm' : (str_starts_with($label, 'flood') ? 'flood' : null)));
            if (! $kind || preg_match('/^(none|unknown|n\/a)\b/i', $text)) {
                continue;
            }
            foreach (array_filter(array_map('trim', explode(';', $text))) as $e) {
                // Month words only, never the start of a storm's name ("Mario" is not March).
                preg_match_all('/\b(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sept?(?:ember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\b/i', $e, $ms);
                $months = array_values(array_unique(array_map(fn ($x) => self::MONTHS[strtolower(substr($x, 0, 3))], $ms[1] ?? [])));
                // "Feb to Apr" is February, March and April.
                if (count($months) === 2 && preg_match('/\b[a-z]+\s+(?:to|until|through)\s+[a-z]+\b/i', $e) && $months[0] !== $months[1]) {
                    $span = [];
                    for ($k = $months[0]; ; $k = $k % 12 + 1) {
                        $span[] = $k;
                        if ($k === $months[1] || count($span) > 12) {
                            break;
                        }
                    }
                    $months = $span;
                }
                preg_match('/\b(severe|moderate|minor|high|low)\b/i', $e, $sv);
                $impact = strtolower($sv[1] ?? 'moderate');
                $impact = ['high' => 'severe', 'low' => 'minor'][$impact] ?? $impact;
                $events[] = ['kind' => $kind, 'months' => $months, 'impact' => $impact, 'what' => mb_substr($e, 0, 160)];
                $counts[$kind]++;
            }
        }

        return ['line' => 'YEAR ' . $year . ' | ' . trim($body), 'events' => $events, 'counts' => $counts, 'rains' => $rains];
    }

    /**
     * The kept record of a place for a prompt: its year lines and a tally
     * by month counted from them, so the month odds are counted, not
     * guessed. Empty when nothing is kept.
     */
    public static function words(string $key, string $place, bool $lines = true, array $focus = []): string
    {
        $have = self::stored($key);
        if (! $have) {
            return '';
        }
        // The hazard book's record: measured and reported, never searched (2026-10-18).
        $book = array_filter($have, fn ($r) => ! empty($r->book));
        if ($book) {
            $first = reset($book);
            $label = $first->town ? $first->town . ', ' . HazardBook::label((string) $first->province) : ($first->province ? HazardBook::label((string) $first->province) : $place);

            return HazardBook::block($book, $label, $first->town ? 'town' : 'province', $focus, $lines ? 3200 : 1800);
        }
        if (! $lines) {
            $t = self::tallyLines($have);

            return "THE PLACE'S RISK RECORD, KEPT BY THE APP (" . self::yearsWords(array_keys($have)) . ', ' . count($have) . ' years of ' . $place
                . ", from earlier web research), counted by month: the years with at least one event (and for cyclones, how many in all):\n" . implode("\n", $t) . "\n";
        }
        $years = array_keys($have);
        $lines = array_map(fn ($r) => $r->line, $have);
        $t = self::tallyLines($have);
        $dates = collect($have)->pluck('researchedAt')->filter()->map(fn ($d) => substr((string) $d, 0, 10))->unique()->sort()->values();

        return "THE PLACE'S RISK RECORD, KEPT BY THE APP (" . self::yearsWords($years) . ', ' . count($years) . ' years of ' . $place
            . ': what earlier web research found, one line a year, read on ' . ($dates->count() > 1 ? $dates->first() . ' to ' . $dates->last() : (string) $dates->first()) . "; treat it as research notes):\n"
            . implode("\n", $lines) . "\n"
            . 'COUNTED FROM THOSE LINES, by month, the years with at least one event' . "\n(and for cyclones, how many in all):\n" . implode("\n", $t) . "\n";
    }

    /** The month tally in lines: "Tropical cyclones: Jul 9 (11), 2 severe; Aug 8 (10)...". */
    private static function tallyLines(array $have): array
    {
        $tally = self::tally($have);
        $mon = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $t = [];
        foreach (['storm' => 'Tropical cyclones', 'flood' => 'Floods', 'drought' => 'Drought', 'heat' => 'Heat'] as $k => $label) {
            $bits = [];
            for ($m = 1; $m <= 12; $m++) {
                $c = $tally[$k][$m] ?? ['years' => 0, 'events' => 0, 'severe' => 0];
                if ($c['years'] > 0) {
                    $bits[] = $mon[$m] . ' ' . $c['years'] . ($k === 'storm' ? ' (' . $c['events'] . ')' : '') . ($c['severe'] ? ', ' . $c['severe'] . ' severe' : '');
                }
            }
            $t[] = $label . ': ' . ($bits ? implode('; ', $bits) : 'none recorded');
        }

        return $t;
    }

    /** Per kind and month: the years with an event, the events, the severe ones. */
    public static function tally(array $have): array
    {
        $out = [];
        foreach ($have as $y => $r) {
            $seen = [];
            foreach ((array) json_decode((string) $r->events, true) as $e) {
                foreach ((array) ($e['months'] ?? []) as $m) {
                    $k = $e['kind'] ?? 'other';
                    $out[$k][$m] ??= ['years' => 0, 'events' => 0, 'severe' => 0];
                    $out[$k][$m]['events']++;
                    $out[$k][$m]['severe'] += ($e['impact'] ?? '') === 'severe' ? 1 : 0;
                    if (empty($seen[$k][$m])) {
                        $out[$k][$m]['years']++;
                        $seen[$k][$m] = true;
                    }
                }
            }
        }

        return $out;
    }
}
