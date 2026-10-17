<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The hazard book (2026-10-18, the owner: "research the past 20 years of
 * historical data about typhoons, floods, and calamities for each town in
 * the Philippines, region, province, and city ... so that anee don't need
 * to search for the history of these anymore ... do this town by town,
 * city by city, province by province, region by region").
 *
 * Every town and city of the gazetteer (1,648 with Caloocan's two districts),
 * its 87 provinces and 18 regions, from 2005 to the running year:
 *  - storms, measured from their tracks: every one that entered the area or
 *    passed within 250 km of a town, with its names, the dates it was in the
 *    area, its landfalls, its peak, and at each town the closest distance,
 *    the wind estimated there and the rain measured on the days it was near;
 *  - rain far below normal (dry spells and droughts), hot spells and heavy
 *    rain with no storm near, measured near each town;
 *  - floods, landslides, surges, eruptions, earthquakes, pests, animal
 *    diseases and cold spells, as reported for the town or its province;
 * kept in as_hazard_events and as_hazard_places, and rolled up a year a line
 * per place in as_hazard_years in the risk record's own form, so
 * ClimateRecord::missing() comes back empty for every Philippine place and
 * no analysis pays for a web search of a place's history.
 *
 * Built from open data by our own scripts (database/hazards/, loaded by
 * load()), kept fresh by hazards:refresh. Nothing here names where a fact
 * came from, and every prompt block says never to.
 *
 * How to use:
 *   HazardBook::words('Nueva Ecija', 'Science City of Muñoz', [6,7,8,9,10]) a prompt block ('' when unknown)
 *   HazardBook::facts('Science City of Muñoz, Nueva Ecija', $months)      ['lines' => block, 'data' => summary()] or null
 *   HazardBook::summary($province, $town, $lat, $lng)                       data: months tally, worst storms, events
 *   HazardBook::place($province, $town, $lat, $lng) / fromLocation($where)  our names and the keys
 *   HazardBook::years($key)                                                 the year rows (ClimateRecord reads these)
 */
final class HazardBook
{
    use Concerns\ShelfConnection;

    /** The book's build; a load of a newer build replaces the rows. */
    public const VERSION = 1;

    /** A storm counts at a place within this distance, or with this wind or rain there. */
    private const NEAR_KM = 150;

    private const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    private const SEV = ['minor' => 1, 'moderate' => 2, 'severe' => 3];

    /** The kinds as the risk record counts them. */
    private const TALLY = ['storm' => 'storm', 'flood' => 'flood', 'landslide' => 'flood', 'surge' => 'flood', 'rain' => 'flood',
        'drought' => 'drought', 'dryspell' => 'drought', 'heat' => 'heat', 'cold' => 'cold', 'earthquake' => 'quake', 'volcano' => 'volcano',
        'pest' => 'pest', 'animal' => 'pest'];

    /* ------------------------------------------------------------ state */

    /** The tables are there and hold the book. */
    public static function ready(): bool
    {
        try {
            // a "yes" is kept ten minutes, a "no" only one (the book may be loading)
            $key = 'hazard-book:' . self::conn() . ':ready';
            if (Cache::get($key) === true) {
                return true;
            }
            $yes = self::schema()->hasTable('as_hazard_years') && self::db()->table('as_hazard_years')->limit(1)->exists();
            Cache::put($key, $yes, $yes ? 600 : 60);

            return $yes;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function tables(): bool
    {
        try {
            return self::schema()->hasTable('as_hazard_years') && self::schema()->hasTable('as_hazard_places');
        } catch (\Throwable) {
            return false;
        }
    }

    /* ------------------------------------------------------------- keys */

    public static function provinceKey(string $province): string
    {
        return 'ph:' . mb_strtolower(trim($province));
    }

    public static function townKey(string $province, string $town): string
    {
        return 'ph:' . mb_strtolower(trim($province)) . '|' . mb_strtolower(trim($town));
    }

    public static function regionKey(string $region): string
    {
        return 'ph-region:' . mb_strtolower(trim($region));
    }

    /** A comparison key for names: lower case, no accents, no "City of", Sto. and Sta. spelled out. */
    public static function norm(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        $s = strtr($s, ['ñ' => 'n', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
        $s = preg_replace('/\([^)]*\)/', ' ', $s);
        $s = preg_replace('/\bsto\.?\s/', 'santo ', $s . ' ');
        $s = preg_replace('/\bsta\.?\s/', 'santa ', $s);
        // "Science City of Muñoz", "City of Muñoz" and "Muñoz City" are one town
        $s = preg_replace('/^.*\bcity of\s+|\s+city$|\s+province$|^province of\s+/', '', trim($s));
        $s = preg_replace('/\s+city$/', '', trim($s));
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** The towns of the book: [province => [norm(town) => town]], the provinces by norm, regions, points. Cached a day. */
    private static function gazetteer(): array
    {
        // An empty list (the book not loaded yet) is never kept for long: it would blind the book for a day.
        $key = 'hazard-book:' . self::conn() . ':gaz:v' . self::VERSION;
        $hit = Cache::get($key);
        if (is_array($hit) && ! empty($hit['prov'])) {
            return $hit;
        }
        $g = self::buildGazetteer();
        Cache::put($key, $g, $g['prov'] ? 86400 : 60);

        return $g;
    }

    private static function buildGazetteer(): array
    {
        return (function () {
            $out = ['prov' => [], 'towns' => [], 'region' => [], 'pts' => []];
            if (! self::schema()->hasTable('as_hazard_towns')) {
                return $out;
            }
            foreach (self::db()->table('as_hazard_towns')->get(['region', 'province', 'town', 'lat', 'lng', 'cell']) as $r) {
                $out['prov'][self::norm($r->province)] = $r->province;
                $out['towns'][$r->province][self::norm($r->town)] = $r->town;
                $out['region'][$r->province] = $r->region;
                $out['pts'][] = [(float) $r->lat, (float) $r->lng, $r->province, $r->town, $r->cell];
            }
            // The names a farmer or an older list may write for a province.
            foreach (['davao de oro' => 'Compostela Valley', 'north cotabato' => 'Cotabato', 'cotabato north cotabato' => 'Cotabato', 'western samar' => 'Samar',
                'samar western samar' => 'Samar', 'mindoro occidental' => 'Occidental Mindoro', 'mindoro oriental' => 'Oriental Mindoro', 'mt province' => 'Mountain Province',
                'maguindanao del norte' => 'Maguindanao', 'maguindanao del sur' => 'Maguindanao', 'manila' => 'Manila City', 'isabela city' => 'Isabela City',
                'cotabato city' => 'Cotabato City', 'tawi tawi' => 'Tawi-tawi', 'metro manila' => 'Manila City', 'ncr' => 'Manila City'] as $k => $v) {
                if (isset($out['region'][$v])) {
                    $out['prov'][$k] ??= $v;
                }
            }

            return $out;
        })();
    }

    /**
     * Our names for a place, and its keys:
     * ['province', 'town', 'region', 'key' (province), 'townKey', 'regionKey', 'level', 'where'], or null.
     * Through App\Support\PlaceNames when it is there (the one matcher every
     * module shares), else the book's own towns. A pin with no names finds
     * the nearest town.
     */
    public static function place(?string $province, ?string $town = null, ?float $lat = null, ?float $lng = null): ?array
    {
        $g = self::gazetteer();
        if (! $g['prov']) {
            return null;
        }
        $prov = null;
        $tw = null;
        if (class_exists(PlaceNames::class) && method_exists(PlaceNames::class, 'resolve') && ($province || $town)) {
            try {
                $r = PlaceNames::resolve($province, $town, null);
                $prov = $r['province'] ?? null;
                $tw = $r['town'] ?? null;
            } catch (\Throwable) {
                // the book's own match below
            }
        }
        if (! $prov && $province) {
            $prov = $g['prov'][self::norm($province)] ?? null;
        }
        if ($prov && ! $tw && $town) {
            $tw = $g['towns'][$prov][self::norm($town)] ?? null;
        }
        if (! $prov && $town) {
            // a town written alone: only when one province has it
            $hits = [];
            foreach ($g['towns'] as $p => $ts) {
                if (isset($ts[self::norm($town)])) {
                    $hits[] = [$p, $ts[self::norm($town)]];
                }
            }
            if (count($hits) === 1) {
                [$prov, $tw] = $hits[0];
            }
        }
        if (! $prov && $lat !== null && $lng !== null && ($lat != 0 || $lng != 0)) {
            $best = null;
            foreach ($g['pts'] as $p) {
                $d = ($p[0] - $lat) ** 2 + (($p[1] - $lng) * cos(deg2rad($lat))) ** 2;
                if ($best === null || $d < $best[0]) {
                    $best = [$d, $p];
                }
            }
            // within about 40 km of a town's centre
            if ($best && sqrt($best[0]) * 111 <= 40) {
                [$prov, $tw] = [$best[1][2], $best[1][3]];
            }
        }
        if (! $prov || ! isset($g['region'][$prov])) {
            return null;
        }
        if ($tw && ! isset($g['towns'][$prov][self::norm($tw)])) {
            $tw = $g['towns'][$prov][self::norm($tw)] ?? null;
        }
        $region = $g['region'][$prov];

        return ['province' => $prov, 'town' => $tw, 'region' => $region, 'key' => self::provinceKey($prov),
            'townKey' => $tw ? self::townKey($prov, $tw) : null, 'regionKey' => self::regionKey($region),
            'level' => $tw ? 'town' : 'province', 'where' => $tw ? $tw . ', ' . self::label($prov) : self::label($prov)];
    }

    /** A province as a farmer reads it ("Ncr, Second District" is Metro Manila). */
    public static function label(string $province): string
    {
        if (str_starts_with(mb_strtolower($province), 'ncr')) {
            return 'Metro Manila';
        }

        return ['Compostela Valley' => 'Davao de Oro', 'Tawi-tawi' => 'Tawi-Tawi'][$province] ?? preg_replace_callback('/\bDel\b/', fn () => 'del', $province);
    }

    /**
     * A written place ("Barangay, Town, Province", "Town, Province" or
     * "Province") as ['province' => ?, 'town' => ?] in our names.
     */
    public static function fromLocation(string $location): array
    {
        if (class_exists(PlaceNames::class) && method_exists(PlaceNames::class, 'fromWhere')) {
            try {
                $r = PlaceNames::fromWhere($location);
                if (! empty($r['province']) && ! empty($r['town'])) {
                    return ['province' => $r['province'], 'town' => $r['town']];
                }
            } catch (\Throwable) {
                // the book's own reading below
            }
        }
        $parts =array_values(array_filter(array_map('trim', explode(',', $location)), fn ($p) => $p !== '' && ! preg_match('/^(philippines|ph)$/i', $p)));
        $g = self::gazetteer();
        $prov = null;
        $town = null;
        // NCR districts carry a comma of their own ("Quezon City, Ncr, Second District").
        $joined = mb_strtolower(implode(', ', $parts));
        foreach (['second' => 'Ncr, Second District', 'third' => 'Ncr, Third District', 'fourth' => 'Ncr, Fourth District'] as $w => $name) {
            if (str_contains($joined, 'ncr, ' . $w . ' district')) {
                $prov = $name;
                $parts = array_values(array_filter($parts, fn ($p) => ! preg_match('/^(ncr|' . $w . ' district)$/i', $p)));
                break;
            }
        }
        if (! $prov) {
            foreach (array_reverse($parts) as $i => $p) {
                if (isset($g['prov'][self::norm($p)])) {
                    $prov = $g['prov'][self::norm($p)];
                    $parts = array_slice($parts, 0, count($parts) - 1 - $i);
                    break;
                }
            }
        }
        foreach (array_reverse($parts) as $p) {
            if ($prov && isset($g['towns'][$prov][self::norm($p)])) {
                $town = $g['towns'][$prov][self::norm($p)];
                break;
            }
            if (! $prov) {
                $town = $p;
                break;
            }
        }
        if (! $prov && $town) {
            $hit = self::place(null, $town);
            if ($hit) {
                return ['province' => $hit['province'], 'town' => $hit['town']];
            }
        }

        return ['province' => $prov, 'town' => $town];
    }

    /* ------------------------------------------------------------- read */

    /**
     * The year rows of a place key (a town's, a province's or a region's),
     * by year. A town with no rows of its own reads its province's.
     */
    public static function years(string $key): array
    {
        if (! self::ready()) {
            return [];
        }
        $out = [];
        try {
            foreach (self::db()->table('as_hazard_years')->where('placeKey', $key)->orderBy('year')->get() as $r) {
                $out[(int) $r->year] = $r;
            }
            if (! $out && str_contains($key, '|')) {
                return self::years(strstr($key, '|', true));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $out;
    }

    /** The place's rows: its own, else (a town) its province's; with the place it stands for. */
    private static function rowsFor(array $pl): array
    {
        $rows = $pl['townKey'] ? self::years($pl['townKey']) : [];
        $own = (bool) $rows;
        if (! $rows) {
            $rows = self::years($pl['key']);
        }

        return [$rows, $own && $pl['town'] ? 'town' : 'province'];
    }

    /**
     * The history of a place as data: the month tally, the worst storms near
     * it, the other events, the span. Null when the book does not know it.
     */
    public static function summary(?string $province, ?string $town = null, ?float $lat = null, ?float $lng = null): ?array
    {
        $pl = self::place($province, $town, $lat, $lng);
        if (! $pl) {
            return null;
        }
        [$rows, $level] = self::rowsFor($pl);
        if (! $rows) {
            return null;
        }
        $months = [];
        $storms = [];
        $events = [];
        foreach ($rows as $y => $r) {
            $seen = [];
            foreach ((array) json_decode((string) $r->events, true) as $e) {
                $k = $e['kind'] ?? 'other';
                foreach ((array) ($e['months'] ?? []) as $m) {
                    $months[$m] ??= [];
                    if (empty($seen[$k][$m])) {
                        $months[$m][$k] = ($months[$m][$k] ?? 0) + 1;
                        $seen[$k][$m] = true;
                    }
                    if (($e['impact'] ?? '') === 'severe') {
                        $months[$m][$k . 'Severe'] = ($months[$m][$k . 'Severe'] ?? 0) + 1;
                    }
                }
                $item = ['year' => $y] + array_intersect_key($e, array_flip(['name', 'local', 'start', 'end', 'distanceKm', 'windKmh', 'rainMm', 'what', 'impact', 'basis', 'pctBelow', 'days', 'value']));
                $item['severity'] = $e['impact'] ?? 'moderate';
                unset($item['impact']);
                if ($k === 'storm') {
                    $storms[] = $item;
                } else {
                    $events[] = ['kind' => $e['src'] ?? $k] + $item;
                }
            }
        }
        ksort($months);
        $score = fn ($s) => (self::SEV[$s['severity']] ?? 1) * 1000 + (int) ($s['windKmh'] ?? 0) + (int) (($s['rainMm'] ?? 0) / 3);
        usort($storms, fn ($a, $b) => $score($b) <=> $score($a));
        $worst = [];
        $tot = [];
        foreach ($months as $m => $c) {
            $tot[$m] = ($c['storm'] ?? 0) * 2 + ($c['stormSevere'] ?? 0) * 3 + ($c['flood'] ?? 0) * 2 + ($c['floodSevere'] ?? 0) * 2;
        }
        arsort($tot);
        foreach (array_slice(array_keys(array_filter($tot)), 0, 3) as $m) {
            $worst[] = self::MONTHS[$m];
        }
        $span = [array_key_first($rows), array_key_last($rows)];

        return ['place' => $level === 'town' ? $pl['where'] : self::label($pl['province']), 'level' => $level, 'province' => $pl['province'], 'town' => $pl['town'],
            'region' => $pl['region'], 'span' => $span, 'years' => count($rows), 'months' => $months, 'worstMonths' => $worst,
            'storms' => array_slice($storms, 0, 40), 'events' => array_slice($events, 0, 60)];
    }

    /**
     * The history of a place for a prompt: what the months held over the
     * years, the worst storms near it, the floods, droughts and the rest,
     * and (with $lines) one line a year. $focus marks the months an
     * analysis is about and lists their storms first. '' when unknown.
     */
    public static function words(?string $province, ?string $town = null, array $focus = [], int $maxChars = 2400, bool $lines = false): string
    {
        try {
            $pl = self::place($province, $town);
            if (! $pl) {
                return '';
            }
            [$rows, $level] = self::rowsFor($pl);
            if (! $rows) {
                return '';
            }

            return self::block($rows, $level === 'town' ? $pl['where'] : self::label($pl['province']), $level, $focus, $maxChars, $lines);
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    /** words() from a written place ("Town, Province"). */
    public static function wordsFor(string $location, array $focus = [], int $maxChars = 2400, bool $lines = false): string
    {
        $p = self::fromLocation($location);

        return $p['province'] || $p['town'] ? self::words($p['province'], $p['town'], $focus, $maxChars, $lines) : '';
    }

    /**
     * For the Crop Protocol and any module with one seam for a place's
     * facts: ['lines' => the prompt block, 'data' => summary()] or null.
     */
    public static function facts(string $location, array $focus = []): ?array
    {
        $p = self::fromLocation($location);
        if (! $p['province'] && ! $p['town']) {
            return null;
        }
        $lines = self::words($p['province'], $p['town'], $focus);
        if ($lines === '') {
            return null;
        }

        return ['lines' => $lines, 'data' => self::summary($p['province'], $p['town'])];
    }

    /**
     * A short line on a few months only (a chat, a weather reading, a
     * morning check): how many of the years had a storm, a flood or a dry
     * spell in each, and the worst storms of those months. A place is a
     * written one ("Town, Province"), a pin, or both. '' when unknown.
     */
    public static function monthsWords(?string $location, array $months, ?float $lat = null, ?float $lng = null, int $maxChars = 900): string
    {
        try {
            $w = $location ? self::fromLocation($location) : ['province' => null, 'town' => null];
            $pl = self::place($w['province'], $w['town'], $lat, $lng);
            if (! $pl || ! $months) {
                return '';
            }
            [$rows, $level] = self::rowsFor($pl);
            if (! $rows) {
                return '';
            }
            $months = array_values(array_unique(array_map('intval', $months)));
            $n = count($rows);
            $c = [];
            $worst = [];
            foreach ($rows as $y => $r) {
                $seen = [];
                foreach ((array) json_decode((string) $r->events, true) as $e) {
                    $k = $e['kind'] ?? 'other';
                    foreach (array_intersect((array) ($e['months'] ?? []), $months) as $m) {
                        if (empty($seen[$k][$m])) {
                            $c[$m][$k] = ($c[$m][$k] ?? 0) + 1;
                            $seen[$k][$m] = true;
                        }
                        if (($e['impact'] ?? '') === 'severe' && empty($seen[$k . 's'][$m])) {
                            $c[$m][$k . 'Severe'] = ($c[$m][$k . 'Severe'] ?? 0) + 1;
                            $seen[$k . 's'][$m] = true;
                        }
                    }
                    if ($k === 'storm' && ($e['impact'] ?? '') !== 'minor' && array_intersect((array) ($e['months'] ?? []), $months)) {
                        $worst[] = ['s' => (self::SEV[$e['impact']] ?? 1) * 1000 + (int) ($e['windKmh'] ?? 0) + (int) (($e['rainMm'] ?? 0) / 3), 't' => $y . ' ' . $e['what']];
                    }
                }
            }
            $bits = [];
            foreach ($months as $m) {
                $x = $c[$m] ?? [];
                $part = [];
                foreach (['storm' => 'storms', 'flood' => 'floods or heavy rain', 'drought' => 'dry spells', 'heat' => 'hot spells'] as $k => $w2) {
                    if (! empty($x[$k])) {
                        $part[] = $w2 . ' in ' . $x[$k] . (! empty($x[$k . 'Severe']) ? ' (' . $x[$k . 'Severe'] . ' severe)' : '');
                    }
                }
                $bits[] = self::MONTHS[$m] . ': ' . ($part ? implode(', ', $part) : 'none recorded');
            }
            usort($worst, fn ($a, $b) => $b['s'] <=> $a['s']);
            $out = 'THIS PLACE IN THESE MONTHS, ' . min(array_keys($rows)) . ' to ' . max(array_keys($rows)) . ' (' . ($level === 'town' ? $pl['where'] : self::label($pl['province']))
                . '; years with one, of ' . $n . '; measured near the ' . ($level === 'town' ? 'town' : 'province') . ' or reported for it, kept by the app; never say where it comes from): '
                . implode('; ', $bits) . ".\n";
            foreach (array_slice($worst, 0, 4) as $x) {
                if (mb_strlen($out) + mb_strlen($x['t']) + 30 > $maxChars) {
                    break;
                }
                $out .= ($x === $worst[0] ? 'The worst storms then: ' : '; ') . $x['t'];
            }

            return rtrim($out) . "\n";
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    /**
     * What struck a place between two dates (a season's own weeks): the
     * storms with what they did there, the floods, dry spells, heat and the
     * rest, one short line each. For a season report or a growth realign:
     * a delay can be put down to what really happened. '' when nothing.
     */
    public static function between(?string $location, string $from, string $to, ?float $lat = null, ?float $lng = null, int $maxChars = 1200): string
    {
        try {
            $w = $location ? self::fromLocation($location) : ['province' => null, 'town' => null];
            $pl = self::place($w['province'], $w['town'], $lat, $lng);
            if (! $pl) {
                return '';
            }
            [$rows, $level] = self::rowsFor($pl);
            $a = substr($from, 0, 10);
            $b = substr($to, 0, 10);
            $hits = [];
            foreach ($rows as $y => $r) {
                if ($y < (int) substr($a, 0, 4) || $y > (int) substr($b, 0, 4)) {
                    continue;
                }
                foreach ((array) json_decode((string) $r->events, true) as $e) {
                    $s = (string) ($e['start'] ?? '');
                    $en = (string) ($e['end'] ?? $s);
                    if ($s !== '' && $s <= $b && $en >= $a && ($e['impact'] ?? '') !== 'minor') {
                        $hits[$s . $e['what']] = $y . ' ' . $e['what'];
                    }
                }
            }
            if (! $hits) {
                return '';
            }
            ksort($hits);
            $out = 'WHAT STRUCK ' . mb_strtoupper($level === 'town' ? $pl['where'] : self::label($pl['province'])) . ' FROM ' . $a . ' TO ' . $b
                . " (measured near the place or reported for it, kept by the app; never say where it comes from):\n";
            foreach ($hits as $t) {
                if (mb_strlen($out) + mb_strlen($t) + 1 > $maxChars) {
                    break;
                }
                $out .= $t . "\n";
            }

            return $out;
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    /** The block itself, from year rows. */
    public static function block(array $rows, string $place, string $level, array $focus = [], int $maxChars = 2400, bool $lines = false): string
    {
        $years = array_keys($rows);
        $first = min($years);
        $last = max($years);
        $n = count($years);
        $tally = [];
        $storms = [];
        $other = [];
        foreach ($rows as $y => $r) {
            $seen = [];
            foreach ((array) json_decode((string) $r->events, true) as $e) {
                $k = $e['kind'] ?? 'other';
                foreach ((array) ($e['months'] ?? []) as $m) {
                    if (empty($seen[$k][$m])) {
                        $tally[$k][$m] = ($tally[$k][$m] ?? 0) + 1;
                        $seen[$k][$m] = true;
                    }
                    if (($e['impact'] ?? '') === 'severe' && empty($seen[$k . 's'][$m])) {
                        $tally[$k . 'Severe'][$m] = ($tally[$k . 'Severe'][$m] ?? 0) + 1;
                        $seen[$k . 's'][$m] = true;
                    }
                }
                // a storm counts once, in the month it began here
                if ($k === 'storm' && ! empty($e['months'])) {
                    $tally['stormAll'][$e['months'][0]] = ($tally['stormAll'][$e['months'][0]] ?? 0) + 1;
                }
                $e['year'] = $y;
                if ($k === 'storm') {
                    $storms[] = $e;
                } elseif (($e['impact'] ?? '') !== 'minor') {
                    $other[] = $e;
                }
            }
        }
        $head = "THE PLACE'S HAZARD HISTORY, {$first} to {$last} ({$n} years) for {$place}, kept by the app: storms measured from their tracks"
            . ($level === 'town' ? ' near the town' : ' near the province\'s towns') . ', rain far below normal, hot spells and heavy rain measured near it, '
            . 'and floods, landslides, quakes, eruptions and pests as reported' . ($level === 'town' ? ' for the town or its province' : ' for the province')
            . '. Use it as known history; never say where it comes from and never name any agency or database.' . "\n";
        $mon = self::MONTHS;
        $tl = [];
        foreach (['storm' => 'Storms that hit', 'flood' => 'Floods and heavy rain', 'drought' => 'Dry spells and drought', 'heat' => 'Hot spells', 'quake' => 'Strong quakes', 'volcano' => 'Ashfall or lahar', 'pest' => 'Pest or disease outbreaks', 'cold' => 'Cold spells'] as $k => $label) {
            if (empty($tally[$k])) {
                continue;
            }
            ksort($tally[$k]);
            $bits = [];
            foreach ($tally[$k] as $m => $c) {
                $sv = $tally[$k . 'Severe'][$m] ?? 0;
                $all = $k === 'storm' ? (int) ($tally['stormAll'][$m] ?? 0) : 0;
                $note = array_filter([$all > $c ? $all . ' in all' : null, $sv ? $sv . ' severe' : null]);
                $bits[] = ($focus && in_array($m, $focus, true) ? '*' : '') . $mon[$m] . ' ' . $c . ($note ? ' (' . implode(', ', $note) . ')' : '');
            }
            $tl[] = $label . ': ' . implode(', ', $bits);
        }
        $out = $head . 'YEARS WITH ONE, BY MONTH (of ' . $n . ' years; storms "in all" count each storm once' . ($focus ? '; * the months this analysis is about' : '') . "):\n" . ($tl ? implode("\n", $tl) : 'none recorded') . "\n";
        // The worst storms, the analysis's months first.
        $score = fn ($s) => (self::SEV[$s['impact'] ?? 'minor'] ?? 1) * 1000 + (int) ($s['windKmh'] ?? 0) + (int) (($s['rainMm'] ?? 0) / 3)
            + ($focus && array_intersect((array) ($s['months'] ?? []), $focus) ? 5000 : 0);
        usort($storms, fn ($a, $b) => $score($b) <=> $score($a));
        $sl = array_map(fn ($s) => $s['year'] . ' ' . $s['what'], array_slice(array_values(array_filter($storms, fn ($s) => ($s['impact'] ?? '') !== 'minor')), 0, 10));
        // Reported floods and the like before the measured spells, the strongest and latest first.
        usort($other, fn ($a, $b) => (self::SEV[$b['impact'] ?? 'minor'] <=> self::SEV[$a['impact'] ?? 'minor'])
            ?: ((($a['basis'] ?? '') === 'measured') <=> (($b['basis'] ?? '') === 'measured')) ?: ($b['year'] <=> $a['year']));
        $ol = array_map(fn ($e) => $e['year'] . ' ' . $e['what'], array_slice($other, 0, 10));
        // The rains' start, measured: the usual day and the last years.
        $rl = '';
        $usual = null;
        $on = [];
        foreach ($rows as $y => $r) {
            if ($r->rains && preg_match('/began (\w+ \d+)(?: \(usually (\w+ \d+)\))?/', (string) $r->rains, $m)) {
                $on[] = $y . ' ' . $m[1];
                $usual = $m[2] ?? $usual;
            }
        }
        if ($on) {
            $rl = 'THE RAINS BEGAN (measured near ' . ($level === 'town' ? 'the town' : 'the province') . ')' . ($usual ? ', usually ' . $usual : '') . ': ' . implode(', ', array_slice($on, -10)) . "\n";
        }
        // Fit the room: the worst storms and the other events take turns.
        $room = $maxChars - mb_strlen($out) - mb_strlen($rl) - 120;
        $takeS = [];
        $takeO = [];
        for ($i = 0; $i < 10; $i++) {
            foreach ([[&$takeS, $sl], [&$takeO, $ol]] as $pair) {
                if (isset($pair[1][$i]) && mb_strlen($pair[1][$i]) + 1 <= $room) {
                    $pair[0][] = $pair[1][$i];
                    $room -= mb_strlen($pair[1][$i]) + 1;
                }
            }
        }
        if ($takeS) {
            $out .= "THE WORST STORMS HERE:\n" . implode("\n", $takeS) . "\n";
        }
        if ($takeO) {
            $out .= "OTHER EVENTS (floods, dry spells, heat, quakes, eruptions, pests):\n" . implode("\n", $takeO) . "\n";
        }
        $out .= $rl;
        if ($lines) {
            $out .= "ONE LINE A YEAR:\n" . implode("\n", array_map(fn ($r) => $r->line, $rows)) . "\n";
        }
        if (mb_strlen($out) > $maxChars) {
            $cut = mb_substr($out, 0, $maxChars);
            $out = mb_substr($cut, 0, (int) mb_strrpos($cut, "\n")) . "\n";
        }

        return $out;
    }

    /* ------------------------------------------------------------- load */

    /**
     * Load the committed book (database/hazards/): towns, events and their
     * places, then the province and region rows, then the year lines.
     * Idempotent: an event is matched by its key, its places replaced.
     *
     * @return array<string, int>
     */
    public static function load(?string $dir = null, ?\Closure $say = null): array
    {
        @ini_set('memory_limit', '1024M');
        $dir ??= database_path('hazards');
        $say ??= fn (string $m) => null;
        $db = self::db();
        $now = now();
        $n = ['towns' => 0, 'events' => 0, 'places' => 0, 'years' => 0];

        // Towns.
        $towns = json_decode((string) @file_get_contents($dir . '/towns.json'), true) ?: [];
        $rows = [];
        foreach ($towns as $t) {
            [$region, $province, $town, $lat, $lng, $cell] = $t;
            $rows[] = ['placeKey' => self::townKey($province, $town), 'region' => $region, 'province' => $province, 'town' => $town, 'lat' => $lat, 'lng' => $lng, 'cell' => $cell];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            $db->table('as_hazard_towns')->upsert($chunk, ['placeKey'], ['region', 'province', 'town', 'lat', 'lng', 'cell']);
        }
        $n['towns'] = count($rows);
        $regionOf = [];
        foreach ($rows as $r) {
            $regionOf[$r['province']] = $r['region'];
        }
        Cache::forget('hazard-book:' . self::conn() . ':gaz:v' . self::VERSION);
        $say('towns ' . $n['towns']);

        // Events.
        $events = json_decode((string) gzdecode((string) @file_get_contents($dir . '/events.json.gz')), true) ?: [];
        $rows = [];
        foreach ($events as $e) {
            $rows[] = self::eventRow($e, $now);
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            $db->table('as_hazard_events')->upsert($chunk, ['eventKey'], ['kind', 'name', 'localName', 'year', 'startDate', 'endDate', 'peak', 'strength', 'landfall',
                'summary', 'farmImpact', 'damagePhp', 'areaHa', 'crops', 'track', 'scope', 'confidence', 'enso', 'updated_at']);
        }
        $n['events'] = count($rows);
        // An event a newer build no longer holds goes, with its places: every researched one (the refresh never writes
        // those), and a measured spell of a past year (the refresh writes only the running year's).
        $inFile = array_flip(array_column($rows, 'eventKey'));
        $gone = [];
        foreach ($db->table('as_hazard_events')->get(['id', 'eventKey', 'year']) as $r) {
            $prefix = strstr((string) $r->eventKey, ':', true);
            if (! isset($inFile[$r->eventKey]) && (in_array($prefix, ['geo', 'farm', 'flood', 'dfo'], true) || ($prefix === 'wx' && (int) $r->year < (int) now('Asia/Manila')->year))) {
                $gone[] = (int) $r->id;
            }
        }
        foreach (array_chunk($gone, 500) as $chunk) {
            $db->table('as_hazard_places')->whereIn('eventId', $chunk)->delete();
            $db->table('as_hazard_events')->whereIn('id', $chunk)->delete();
        }
        $n['dropped'] = count($gone);
        $ids = $db->table('as_hazard_events')->pluck('id', 'eventKey')->all();
        unset($events, $rows);
        $say('events ' . $n['events']);

        // Places: the loaded events' rows are replaced.
        $loaded = array_values(array_intersect_key($ids, $inFile));
        foreach (array_chunk($loaded, 500) as $chunk) {
            $db->table('as_hazard_places')->whereIn('eventId', $chunk)->delete();
        }
        $buf = [];
        $fh = gzopen($dir . '/places.ndjson.gz', 'rb');
        while ($fh && ($line = gzgets($fh)) !== false) {
            $l = json_decode($line, true);
            if (! is_array($l) || ! isset($ids[$l[0]])) {
                continue;
            }
            $buf[] = self::placeRow($ids[$l[0]], $l, $regionOf);
            if (count($buf) >= 1000) {
                $db->table('as_hazard_places')->insert($buf);
                $n['places'] += count($buf);
                $buf = [];
            }
        }
        if ($fh) {
            gzclose($fh);
        }
        if ($buf) {
            $db->table('as_hazard_places')->insert($buf);
            $n['places'] += count($buf);
        }
        $say('places ' . $n['places']);
        $n['places'] += self::aggregate();
        $say('with province and region rows ' . $n['places']);
        $onset = json_decode((string) gzdecode((string) @file_get_contents($dir . '/onset.json.gz')), true) ?: [];
        Cache::put('hazard-book:' . self::conn() . ':onset', $onset, now()->addYear());
        @file_put_contents(storage_path('app/hazard-onset.json'), json_encode($onset));
        $n['years'] = self::rollup(null, $say);
        Cache::forget('hazard-book:' . self::conn() . ':ready');

        return $n;
    }

    /** An event as a row. */
    public static function eventRow(array $e, $now): array
    {
        $txt = fn ($s, int $max) => $s === null || $s === '' ? null : mb_substr(GovSource::scrub((string) $s), 0, $max);

        return ['eventKey' => mb_substr((string) $e['key'], 0, 100), 'kind' => (string) $e['kind'], 'name' => $txt($e['name'] ?? null, 120),
            'localName' => isset($e['localName']) ? mb_substr((string) $e['localName'], 0, 40) : null, 'year' => (int) $e['year'],
            'startDate' => $e['start'], 'endDate' => $e['end'] ?? null, 'peak' => $txt($e['peak'] ?? null, 80),
            'strength' => isset($e['strength']) && is_numeric($e['strength']) ? round((float) $e['strength'], 1) : null,
            'landfall' => $txt($e['landfall'] ?? null, 400), 'summary' => $txt($e['summary'] ?? null, 2000), 'farmImpact' => $txt($e['farmImpact'] ?? null, 2000),
            'damagePhp' => isset($e['damagePhp']) && $e['damagePhp'] > 0 ? (int) $e['damagePhp'] : null,
            'areaHa' => isset($e['areaHa']) && $e['areaHa'] > 0 ? (int) $e['areaHa'] : null,
            'crops' => ! empty($e['crops']) ? json_encode(array_values((array) $e['crops'])) : null,
            'track' => ! empty($e['track']) ? json_encode($e['track']) : null, 'scope' => (string) ($e['scope'] ?? 'province'),
            'confidence' => (string) ($e['confidence'] ?? 'reported'), 'enso' => $e['enso'] ?? null, 'created_at' => $now, 'updated_at' => $now];
    }

    /** One link [key, level, province, town, start, end, dist, wind, rain, maxDay, pct, days, value, severity, basis, effect] as a row. */
    public static function placeRow(int $eventId, array $l, array $regionOf): array
    {
        [, $level, $prov, $town, $start, $end, $dist, $wind, $rain, $maxDay, $pct, $days, $value, $sev, $basis, $effect] = array_pad($l, 16, null);
        $start = $start ?: '2005-01-01';
        $c = fn ($v, $max) => $v === null ? null : max(0, min($max, (int) round((float) $v)));

        return ['eventId' => $eventId, 'level' => $town ? 'town' : $level, 'placeKey' => $town ? self::townKey($prov, $town) : self::provinceKey($prov),
            'region' => $regionOf[$prov] ?? null, 'province' => $prov, 'town' => $town, 'year' => (int) substr($start, 0, 4), 'month' => (int) substr($start, 5, 2),
            'startDate' => $start, 'endDate' => $end ?: $start, 'distanceKm' => $c($dist, 65000), 'windKmh' => $c($wind, 65000), 'rainMm' => $c($rain, 65000),
            'maxDayMm' => $c($maxDay, 65000), 'pctBelow' => $c($pct, 100), 'days' => $c($days, 65000), 'value' => $value === null ? null : round((float) $value, 1),
            'severity' => in_array($sev, ['minor', 'moderate', 'severe'], true) ? $sev : 'moderate', 'basis' => (string) ($basis ?: 'measured'),
            'effect' => $effect === null || $effect === '' ? null : mb_substr(GovSource::scrub((string) $effect), 0, 255)];
    }

    /**
     * The province and region rows measured from their towns (the closest
     * pass, the strongest wind, the most rain, in how many towns), and the
     * region rows of what was reported for its provinces. $eventIds limits
     * it to some events (the refresh); their old rows go first.
     */
    public static function aggregate(?array $eventIds = null): int
    {
        $db = self::db();
        $in = $eventIds ? ' AND eventId IN (' . implode(',', array_map('intval', $eventIds)) . ')' : '';
        if ($eventIds) {
            $db->table('as_hazard_places')->whereIn('eventId', $eventIds)->where(fn ($q) => $q->where('level', 'region')->orWhere(fn ($q) => $q->where('level', 'province')->where('basis', 'measured')))->delete();
        } else {
            $db->table('as_hazard_places')->where('level', 'region')->delete();
            $db->table('as_hazard_places')->where('level', 'province')->where('basis', 'measured')->delete();
        }
        $sev = "CASE MAX(CASE severity WHEN 'severe' THEN 3 WHEN 'moderate' THEN 2 ELSE 1 END) WHEN 3 THEN 'severe' WHEN 2 THEN 'moderate' ELSE 'minor' END";
        $cols = 'eventId, level, placeKey, region, province, town, year, month, startDate, endDate, distanceKm, windKmh, rainMm, maxDayMm, pctBelow, days, value, severity, basis, effect';
        $before = (int) $db->table('as_hazard_places')->count();
        $db->statement("INSERT INTO as_hazard_places ({$cols})
            SELECT eventId, 'province', CONCAT('ph:', LOWER(province)), MAX(region), province, NULL, MIN(year), MIN(month), MIN(startDate), MAX(endDate),
                   MIN(distanceKm), MAX(windKmh), MAX(rainMm), MAX(maxDayMm), MAX(pctBelow), MAX(days), MAX(value), {$sev}, 'measured', CONCAT('measured at ', COUNT(*), ' towns')
            FROM as_hazard_places WHERE level = 'town' AND basis = 'measured'{$in} GROUP BY eventId, province");
        $db->statement("INSERT INTO as_hazard_places ({$cols})
            SELECT eventId, 'region', CONCAT('ph-region:', LOWER(region)), region, NULL, NULL, MIN(year), MIN(month), MIN(startDate), MAX(endDate),
                   MIN(distanceKm), MAX(windKmh), MAX(rainMm), MAX(maxDayMm), MAX(pctBelow), MAX(days), MAX(value), {$sev}, 'measured', CONCAT('measured at ', COUNT(*), ' towns')
            FROM as_hazard_places WHERE level = 'town' AND basis = 'measured' AND region IS NOT NULL{$in} GROUP BY eventId, region");
        $db->statement("INSERT INTO as_hazard_places ({$cols})
            SELECT eventId, 'region', CONCAT('ph-region:', LOWER(region)), region, NULL, NULL, MIN(year), MIN(month), MIN(startDate), MAX(endDate),
                   NULL, NULL, NULL, NULL, NULL, NULL, NULL, {$sev}, 'province', LEFT(CONCAT('reported in ', GROUP_CONCAT(DISTINCT province ORDER BY province SEPARATOR ', ')), 255)
            FROM as_hazard_places WHERE level IN ('province', 'town') AND basis IN ('province', 'town') AND region IS NOT NULL{$in} GROUP BY eventId, region");

        return (int) $db->table('as_hazard_places')->count() - $before;
    }

    /**
     * The year lines of every place (or of $years only), in the risk
     * record's form: "YEAR 2015 | storms: ... | floods: ... | drought: ... |
     * heat: ... | other: ... | rains: ...", each fact saying whether it was
     * measured near the place or reported for it or its province.
     */
    public static function rollup(?array $years = null, ?\Closure $say = null): int
    {
        $db = self::db();
        $say ??= fn (string $m) => null;
        $now = now();
        $running = (int) now('Asia/Manila')->year;
        [$from, $to] = [$running - ClimateStore::YEARS - 1, $running];
        $years = $years ?: range($from, $to);
        $events = [];
        foreach ($db->table('as_hazard_events')->whereIn('year', array_merge($years, [min($years) - 1]))->get(['id', 'kind', 'name', 'localName', 'peak', 'startDate', 'endDate', 'enso']) as $e) {
            $events[(int) $e->id] = $e;
        }
        $onset = self::onset();
        $towns = $db->table('as_hazard_towns')->get(['province', 'town', 'region', 'cell']);
        $byProv = [];
        foreach ($towns as $t) {
            $byProv[$t->province][] = $t;
        }
        $enso = self::ensoYears();
        $count = 0;
        foreach ($byProv as $prov => $ts) {
            // Every row of the province's towns and of the province, in two reads.
            $rows = $db->table('as_hazard_places')->where('province', $prov)->whereIn('year', $years)->get();
            $byKey = [];
            $provReported = [];
            foreach ($rows as $r) {
                if (! isset($events[(int) $r->eventId])) {
                    continue;
                }
                $byKey[$r->placeKey][(int) $r->year][] = $r;
                if ($r->level === 'province' && $r->basis !== 'measured') {
                    $provReported[(int) $r->eventId] = $r;
                }
            }
            $out = [];
            foreach ($ts as $t) {
                $key = self::townKey($prov, $t->town);
                foreach ($years as $y) {
                    // the province's reports count for the town too, said as the province's
                    $mine = $byKey[$key][$y] ?? [];
                    $have = array_flip(array_map(fn ($r) => (int) $r->eventId, $mine));
                    foreach ($provReported as $eid => $pr) {
                        if ((int) $pr->year === $y) {
                            $mine[] = $pr;
                        }
                    }
                    $out[] = self::yearRow($key, 'town', $t->region, $prov, $t->town, $y, $mine, $events, $onset[$t->cell][(string) $y] ?? null, $onset[$t->cell]['median'] ?? null, $enso[$y] ?? null, $now, $running);
                }
            }
            $pkey = self::provinceKey($prov);
            $cells = array_count_values(array_filter(array_map(fn ($t) => $t->cell, $ts)));
            arsort($cells);
            $pcell = array_key_first($cells);
            foreach ($years as $y) {
                $mine = $byKey[$pkey][$y] ?? [];
                // towns named in reports show under the province with their names
                foreach ($ts as $t) {
                    foreach ($byKey[self::townKey($prov, $t->town)][$y] ?? [] as $r) {
                        if ($r->basis === 'town') {
                            $mine[] = $r;
                        }
                    }
                }
                $out[] = self::yearRow($pkey, 'province', $ts[0]->region, $prov, null, $y, $mine, $events, $onset[$pcell][(string) $y] ?? null, $onset[$pcell]['median'] ?? null, $enso[$y] ?? null, $now, $running);
            }
            foreach (array_chunk($out, 300) as $chunk) {
                $db->table('as_hazard_years')->upsert($chunk, ['placeKey', 'year'], ['level', 'region', 'province', 'town', 'line', 'events', 'storms', 'floods', 'droughts', 'heat', 'others', 'rains', 'complete', 'builtAt']);
            }
            $count += count($out);
        }
        // Regions.
        foreach (array_unique(array_map(fn ($t) => $t->region, $towns->all())) as $region) {
            $rkey = self::regionKey($region);
            $rows = $db->table('as_hazard_places')->where('placeKey', $rkey)->whereIn('year', $years)->get();
            $by = [];
            foreach ($rows as $r) {
                if (isset($events[(int) $r->eventId])) {
                    $by[(int) $r->year][] = $r;
                }
            }
            $out = [];
            foreach ($years as $y) {
                $out[] = self::yearRow($rkey, 'region', $region, null, null, $y, $by[$y] ?? [], $events, null, null, $enso[$y] ?? null, $now, $running);
            }
            $db->table('as_hazard_years')->upsert($out, ['placeKey', 'year'], ['level', 'region', 'province', 'town', 'line', 'events', 'storms', 'floods', 'droughts', 'heat', 'others', 'rains', 'complete', 'builtAt']);
            $count += count($out);
        }
        $say('year lines ' . $count);
        Cache::forget('hazard-book:' . self::conn() . ':ready');

        return $count;
    }

    /** The rains' onset per weather square and year, kept at load. */
    private static function onset(): array
    {
        $hit = Cache::get('hazard-book:' . self::conn() . ':onset');
        if (is_array($hit)) {
            return $hit;
        }
        $f = storage_path('app/hazard-onset.json');
        $d = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
        if (! $d) {
            $d = json_decode((string) @gzdecode((string) @file_get_contents(database_path('hazards/onset.json.gz'))), true) ?: [];
        }

        return $d;
    }

    /** Each year's El Niño and La Niña months, in words ("El Niño Jan to May; La Niña Sep to Dec"). */
    private static function ensoYears(): array
    {
        $oni = [];
        try {
            $oni = ClimateStore::oniByMonth();
        } catch (\Throwable) {
            // none kept: no ENSO part
        }
        $out = [];
        foreach ($oni as $y => $ms) {
            $parts = [];
            foreach (['El Niño' => fn ($v) => $v >= 0.5, 'La Niña' => fn ($v) => $v <= -0.5] as $name => $test) {
                $hit = array_keys(array_filter($ms, fn ($v) => $v !== null && $test((float) $v)));
                if ($hit) {
                    sort($hit);
                    $parts[] = $name . ' ' . self::MONTHS[$hit[0]] . (count($hit) > 1 ? ' to ' . self::MONTHS[end($hit)] : '');
                }
            }
            $out[(int) $y] = $parts ? implode('; ', $parts) : null;
        }

        return $out;
    }

    /** One place's year: the line, the events for the tally, the counts. */
    private static function yearRow(string $key, string $level, ?string $region, ?string $prov, ?string $town, int $y, array $rows, array $events, ?string $onset, ?string $median, ?string $enso, $now, int $running): array
    {
        $parts = ['storm' => [], 'flood' => [], 'drought' => [], 'heat' => [], 'other' => []];
        $items = [];
        // One item per event: measured numbers first, a report beside them.
        $byEvent = [];
        foreach ($rows as $r) {
            $byEvent[(int) $r->eventId][] = $r;
        }
        $kept = [];
        foreach ($byEvent as $eid => $rs) {
            $e = $events[$eid];
            $meas = null;
            $rep = [];
            foreach ($rs as $r) {
                if ($r->basis === 'measured') {
                    $meas ??= $r;
                } else {
                    $rep[] = $r;
                }
            }
            $kind = (string) $e->kind;
            $group = self::TALLY[$kind] ?? 'other';
            $start = Carbon::parse(($meas ?? $rs[0])->startDate);
            $end = Carbon::parse(($meas ?? $rs[0])->endDate ?: $start);
            $months = [];
            for ($d = $start->copy()->startOfMonth(); $d->lte($end) && count($months) < 12; $d->addMonth()) {
                if ((int) $d->year === $y) {
                    $months[] = (int) $d->month;
                }
            }
            $months = $months ?: [(int) $start->month];
            $sev = 'minor';
            foreach ($rs as $r) {
                $sev = self::SEV[$r->severity] > self::SEV[$sev] ? $r->severity : $sev;
            }
            $mw = self::MONTHS[$months[0]] . (count($months) > 1 ? ' to ' . self::MONTHS[end($months)] : '');
            $what = null;
            $repWord = self::reportWord($rep, $level);
            if ($kind === 'storm') {
                $name = $e->localName && $e->name ? $e->localName . ' (' . $e->name . ')' : ($e->localName ?: ($e->name ?: 'a depression'));
                if ($meas) {
                    $dist = $meas->distanceKm !== null ? (int) $meas->distanceKm : 999;
                    $matters = $dist <= self::NEAR_KM || (int) $meas->windKmh >= 62 || ((int) $meas->rainMm >= 100 && $dist <= 300);
                    $repSev = 'minor';
                    foreach ($rep as $r) {
                        $repSev = self::SEV[$r->severity] > self::SEV[$repSev] ? $r->severity : $repSev;
                    }
                    if (! $matters && $repSev === 'minor') {
                        continue;
                    }
                    $bits = [];
                    if ($meas->distanceKm !== null) {
                        $bits[] = $dist <= 15 ? 'passed over' : ($dist > 250 ? 'far pass, ' . $dist . ' km away' : $dist . ' km away');
                    }
                    if ($meas->windKmh) {
                        $bits[] = 'wind about ' . (int) (round($meas->windKmh / 5) * 5) . ' km/h';
                    }
                    if ($meas->rainMm) {
                        $bits[] = $meas->rainMm . ' mm rain';
                    }
                    $what = $mw . ' ' . $name . ' ' . $sev . ': ' . implode(', ', $bits) . ($level === 'town' ? ' (measured)' : ' (measured at its towns)') . $repWord;
                } else {
                    if ($sev === 'minor') {
                        continue;
                    }
                    $what = $mw . ' ' . $name . ' ' . $sev . $repWord;
                }
            } elseif (in_array($kind, ['drought', 'dryspell'], true)) {
                $label = $kind === 'drought' ? 'drought' : 'dry spell';
                $what = $mw . ' ' . $label . ' ' . $sev . ($meas ? ': rain ' . (int) $meas->pctBelow . '% below normal (measured)' : '') . $repWord;
            } elseif ($kind === 'heat') {
                $what = $mw . ' hot spell ' . $sev . ($meas ? ': ' . (int) $meas->days . ' days, up to ' . round((float) $meas->value) . ' C (measured)' : '') . $repWord;
            } elseif ($kind === 'rain') {
                $what = $mw . ' heavy rain ' . $sev . ($meas ? ': ' . (int) $meas->maxDayMm . ' mm in a day, no storm near (measured)' : '');
            } elseif ($kind === 'earthquake') {
                $what = $mw . ' ' . ($e->peak ?: 'earthquake') . ' ' . $sev . ($meas && $meas->distanceKm !== null ? ': ' . $meas->distanceKm . ' km away (measured)' : '') . $repWord;
            } else {
                $label = ['flood' => 'flood', 'landslide' => 'landslide', 'surge' => 'storm surge', 'volcano' => 'eruption', 'pest' => 'pest outbreak', 'animal' => 'animal disease', 'cold' => 'cold spell'][$kind] ?? $kind;
                $nm = $e->name && ! in_array(mb_strtolower($e->name), ['floods', 'flood', 'landslides'], true) ? ', ' . $e->name : '';
                $eff = '';
                foreach ($rep as $r) {
                    if ($r->effect && ! str_starts_with($r->effect, 'named in') && ! str_starts_with($r->effect, 'reported in') && ! str_starts_with($r->effect, 'measured at')) {
                        $eff = ', ' . mb_substr($r->effect, 0, 90);
                        break;
                    }
                }
                $what = $mw . ' ' . $label . $nm . ' ' . $sev . $eff . $repWord;
            }
            $what = trim(preg_replace('/\s+/', ' ', GovSource::scrub($what)));
            // Two measured spells of one kind in the same months are one: the stronger stays.
            $family = ['drought' => 'dry', 'dryspell' => 'dry', 'heat' => 'heat', 'rain' => 'rain'][$kind] ?? null;
            if ($family && $meas) {
                $clash = null;
                foreach ($kept[$family] ?? [] as $i => $k) {
                    if (array_intersect($k['months'], $months)) {
                        $clash = $i;
                        break;
                    }
                }
                if ($clash !== null) {
                    if (self::SEV[$sev] <= self::SEV[$kept[$family][$clash]['sev']]) {
                        continue;
                    }
                    $drop = $kept[$family][$clash]['what'];
                    foreach ($parts as $pk => $list) {
                        $parts[$pk] = array_values(array_filter($list, fn ($x) => $x[1] !== $drop));
                    }
                    $items = array_values(array_filter($items, fn ($x) => $x['what'] !== mb_substr($drop, 0, 220)));
                    unset($kept[$family][$clash]);
                }
                $kept[$family][] = ['months' => $months, 'sev' => $sev, 'what' => $what];
            }
            $parts[$group === 'storm' ? 'storm' : ($group === 'flood' ? 'flood' : ($group === 'drought' ? 'drought' : ($group === 'heat' ? 'heat' : 'other')))][] = [$start->timestamp, $what];
            $item = ['kind' => $group, 'src' => $kind, 'months' => $months, 'impact' => $sev, 'what' => mb_substr($what, 0, 220),
                'start' => $start->toDateString(), 'end' => $end->toDateString(), 'basis' => $meas ? 'measured' : ($rep ? $rep[0]->basis : 'province')];
            if ($kind === 'storm') {
                $item += ['name' => $e->name, 'local' => $e->localName, 'distanceKm' => $meas?->distanceKm, 'windKmh' => $meas?->windKmh, 'rainMm' => $meas?->rainMm];
            } elseif ($meas) {
                $item += array_filter(['pctBelow' => $meas->pctBelow, 'days' => $meas->days, 'value' => $meas->value !== null ? (float) $meas->value : null, 'rainMm' => $meas->maxDayMm], fn ($v) => $v !== null);
            }
            $items[] = $item;
        }
        $say = function (array $list): string {
            if (! $list) {
                return 'none';
            }
            usort($list, fn ($a, $b) => $a[0] <=> $b[0]);

            return implode('; ', array_map(fn ($x) => $x[1], $list));
        };
        $rains = null;
        if ($onset) {
            $rains = 'began ' . self::md($onset) . ($median ? ' (usually ' . self::md($median) . ')' : '');
        }
        $line = 'YEAR ' . $y . ' | storms: ' . $say($parts['storm']) . ' | floods: ' . $say($parts['flood']) . ' | drought: ' . $say($parts['drought'])
            . ' | heat: ' . $say($parts['heat']) . ($parts['other'] ? ' | other: ' . $say($parts['other']) : '') . ($rains ? ' | rains: ' . $rains : '')
            . ($enso ? ' | ENSO: ' . $enso : '');
        $cnt = fn ($g) => min(255, count(array_filter($items, fn ($i) => $i['kind'] === $g)));

        return ['placeKey' => $key, 'level' => $level, 'region' => $region, 'province' => $prov, 'town' => $town, 'year' => $y, 'line' => mb_substr($line, 0, 60000),
            'events' => json_encode($items, JSON_UNESCAPED_UNICODE), 'storms' => $cnt('storm'), 'floods' => $cnt('flood'), 'droughts' => $cnt('drought'),
            'heat' => $cnt('heat'), 'others' => min(255, count($items) - $cnt('storm') - $cnt('flood') - $cnt('drought') - $cnt('heat')), 'rains' => $rains,
            'complete' => $y < $running, 'builtAt' => $now];
    }

    /** How a report reads beside a fact ("reported for the province", "named for the town"). */
    private static function reportWord(array $rep, string $level): string
    {
        if (! $rep) {
            return '';
        }
        $town = array_filter($rep, fn ($r) => $r->basis === 'town');
        $calam = (bool) array_filter($rep, fn ($r) => $r->effect && str_contains($r->effect, 'state of calamity'));
        if ($level === 'town') {
            $w = $town ? ' (reported for the town' : ' (reported for the province';
        } elseif ($level === 'province') {
            $names = array_values(array_unique(array_filter(array_map(fn ($r) => $r->town, $town))));
            $w = $names ? ' (reported in ' . implode(', ', array_slice($names, 0, 4)) . (count($names) > 4 ? ' and more' : '') : ' (reported for the province';
        } else {
            $eff = $rep[0]->effect ?: 'reported';
            $w = ' (' . mb_substr($eff, 0, 120);
        }

        return $w . ($calam ? ', state of calamity' : '') . ')';
    }

    private static function md(string $mmdd): string
    {
        [$m, $d] = array_map('intval', explode('-', $mmdd));

        return (self::MONTHS[$m] ?? '') . ' ' . $d;
    }
}
