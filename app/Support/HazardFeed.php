<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The hazard book's monthly refresh (hazards:refresh, 2026-10-18): the new
 * storms and events of the running year from the same open data the book
 * was built from, with our own maths, no AI.
 *
 *  - storms():  the recent best tracks; every town's closest pass, the wind
 *               estimated there and the dates the storm was near; the local
 *               name from the season's table of names
 *  - weather(): daily rain and heat per weather square for the running year;
 *               each storm's rain at its towns, dry spells and droughts
 *               against the square's normals, hot spells, heavy rain with no
 *               storm near, the rains' onset
 *  - reports(): the year's disaster pages (floods, landslides, eruptions,
 *               quakes, drought) and the provinces they name; a typhoon's
 *               page adds its provinces to the storm
 *  - quakes():  strong shallow earthquakes near land, and the towns that
 *               felt strong shaking
 * then the province and region rows of what changed, and the year lines.
 * Polite: a pause between calls, one read of each list. Nothing stored
 * names where it came from.
 */
final class HazardFeed
{
    use Concerns\ShelfConnection;

    private const UA = 'Mozilla/5.0 (compatible; aneeHazardHistory/1.0; farm hazard history, monthly)';

    private const TRACKS = 'https://www.ncei.noaa.gov/data/international-best-track-archive-for-climate-stewardship-ibtracs/v04r01/access/csv/ibtracs.last3years.list.v04r01.csv';

    private const DAILY = 'https://power.larc.nasa.gov/api/temporal/daily/point';

    private const PAR = [[120, 25], [135, 25], [135, 5], [115, 5], [115, 15], [120, 21], [120, 25]];

    private const KT = 1.852;

    /** Daily rain and highs per square, read by fetch() for storms() and weather(). */
    private static array $rain = [];

    private static array $tmax = [];

    /** The ids of the events this run wrote. */
    private static array $touched = [];

    /** @return array<string, mixed> */
    public static function run(array $parts, ?\Closure $say = null): array
    {
        $say ??= fn (string $m) => null;
        $out = [];
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);
        // The weather is read first (the storms' rain comes from it), and measured after the storms (heavy rain with no storm near needs them).
        if (in_array('weather', $parts, true)) {
            $out['read'] = self::fetch($say);
        }
        foreach (['storms', 'weather', 'reports', 'quakes'] as $p) {
            if (! in_array($p, $parts, true)) {
                continue;
            }
            try {
                $out[$p] = self::$p($say);
            } catch (\Throwable $e) {
                report($e);
                $out[$p] = 'failed: ' . $e->getMessage();
            }
            $say($p . ': ' . json_encode($out[$p]));
        }
        if (self::$touched) {
            HazardBook::aggregate(array_values(array_unique(self::$touched)));
        }
        $y = (int) now('Asia/Manila')->year;
        $years = (int) now('Asia/Manila')->month <= 2 ? [$y - 1, $y] : [$y];
        $out['years'] = HazardBook::rollup($years, $say);
        Cache::put('hazard-book:' . self::conn() . ':refreshed', now()->toIso8601String(), now()->addYear());

        return $out;
    }

    /* ------------------------------------------------------------- towns */

    /** [[lat, lng, province, town, region, cell], ...] */
    private static function towns(): array
    {
        static $t = null;

        return $t ??= self::db()->table('as_hazard_towns')->orderBy('id')->get(['lat', 'lng', 'province', 'town', 'region', 'cell'])
            ->map(fn ($r) => [(float) $r->lat, (float) $r->lng, $r->province, $r->town, $r->region, $r->cell])->all();
    }

    private static function km(float $a, float $b, float $c, float $d): float
    {
        $p1 = deg2rad($a);
        $p2 = deg2rad($c);
        $x = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin(deg2rad($d - $b) / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($x)));
    }

    private static function inPar(float $lat, float $lng): bool
    {
        $c = false;
        $r = self::PAR;
        for ($i = 0, $j = count($r) - 1; $i < count($r); $j = $i++) {
            [$xi, $yi] = $r[$i];
            [$xj, $yj] = $r[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $c = ! $c;
            }
        }

        return $c;
    }

    private static function category(float $kmh): string
    {
        return $kmh >= 185 ? 'Super typhoon' : ($kmh >= 118 ? 'Typhoon' : ($kmh >= 89 ? 'Severe tropical storm' : ($kmh >= 62 ? 'Tropical storm' : 'Tropical depression')));
    }

    private static function get(string $url, array $q = [], int $timeout = 60): ?\Illuminate\Http\Client\Response
    {
        $wait = 4;
        for ($i = 0; $i < 4; $i++) {
            try {
                $r = Http::withHeaders(['User-Agent' => self::UA])->timeout($timeout)->get($url, $q);
                if ($r->ok()) {
                    return $r;
                }
                if ($r->status() === 404) {
                    return null;
                }
            } catch (\Throwable $e) {
                Log::info('hazards: ' . $url . ' ' . $e->getMessage());
            }
            sleep($wait);
            $wait *= 2;
        }

        return null;
    }

    /* ------------------------------------------------------------ storms */

    /** The storms of the last $days days: their passes at every town. */
    public static function storms(\Closure $say, int $days = 75): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tracks');
        try {
            $r = Http::withHeaders(['User-Agent' => self::UA])->timeout(240)->sink($tmp)->get(self::TRACKS);
            if (! $r->ok()) {
                return ['error' => 'tracks ' . $r->status()];
            }
        } catch (\Throwable $e) {
            @unlink($tmp);

            return ['error' => $e->getMessage()];
        }
        $fh = fopen($tmp, 'r');
        $head = fgetcsv($fh);
        fgetcsv($fh);
        $ix = array_flip($head);
        $by = [];
        while (($row = fgetcsv($fh)) !== false) {
            if (($row[$ix['BASIN']] ?? '') !== 'WP') {
                continue;
            }
            $tt = trim($row[$ix['TRACK_TYPE']] ?? '');
            if (stripos($tt, 'spur') !== false || ! in_array($tt, ['main', 'PROVISIONAL', 'US-PROVISIONAL'], true)) {
                continue;
            }
            $num = fn ($k) => is_numeric(trim($row[$ix[$k]] ?? '')) ? (float) trim($row[$ix[$k]]) : null;
            $w10 = $num('TOKYO_WIND');
            $w1 = $num('USA_WIND');
            $sid = $row[$ix['SID']];
            $by[$sid]['name'] ??= trim($row[$ix['NAME']]);
            $by[$sid]['season'] ??= (int) $row[$ix['SEASON']];
            $by[$sid]['pts'][] = ['t' => Carbon::parse($row[$ix['ISO_TIME']], 'UTC'), 'lat' => $num('LAT'), 'lon' => $num('LON'),
                'wind' => $w10 !== null ? $w10 * self::KT : ($w1 !== null ? $w1 * self::KT * 0.88 : null), 'rmw' => $num('USA_RMW') ? $num('USA_RMW') * self::KT : null,
                'd2l' => $num('DIST2LAND')];
        }
        fclose($fh);
        @unlink($tmp);
        $since = now('UTC')->subDays($days);
        $names = [];
        $n = 0;
        foreach ($by as $sid => $s) {
            $last = end($s['pts'])['t'];
            if ($last->lt($since) || $s['season'] < 2005) {
                continue;
            }
            $names[$s['season']] ??= self::seasonNames($s['season']);
            if (self::storm($sid, $s, $names[$s['season']])) {
                $n++;
            }
        }

        return ['storms' => $n];
    }

    /** One storm's event and town rows. False when it came nowhere near. */
    private static function storm(string $sid, array $s, array $names): bool
    {
        $pts = array_values(array_filter($s['pts'], fn ($p) => $p['lat'] !== null && $p['lon'] !== null));
        if (count($pts) < 2) {
            return false;
        }
        $last = 45.0;
        foreach ($pts as &$p) {
            $p['wind'] ??= $last;
            $last = $p['wind'];
        }
        unset($p);
        // Hourly positions.
        $hrs = [];
        for ($i = 0; $i < count($pts); $i++) {
            $a = $pts[$i];
            $b = $pts[$i + 1] ?? $a;
            $n = $b === $a ? 1 : max(1, (int) floor($a['t']->diffInHours($b['t'])));
            for ($k = 0; $k < $n; $k++) {
                $f = $k / $n;
                $w = $a['wind'] + ($b['wind'] - $a['wind']) * $f;
                $la = $a['lat'] + ($b['lat'] - $a['lat']) * $f;
                $rm = ($a['rmw'] ?: 46.4 * exp(-0.0155 * $a['wind'] / 3.6 + 0.0169 * abs($a['lat']))) * (1 - $f) + ($b['rmw'] ?: 46.4 * exp(-0.0155 * $b['wind'] / 3.6 + 0.0169 * abs($b['lat']))) * $f;
                $hrs[] = ['t' => $a['t']->copy()->addHours($k), 'lat' => $la, 'lon' => $a['lon'] + ($b['lon'] - $a['lon']) * $f, 'w' => $w, 'r' => $rm,
                    'd2l' => $a['d2l'] !== null && $b['d2l'] !== null ? $a['d2l'] + ($b['d2l'] - $a['d2l']) * $f : $a['d2l']];
            }
        }
        $par = array_values(array_filter(array_keys($hrs), fn ($i) => self::inPar($hrs[$i]['lat'], $hrs[$i]['lon'])));
        $towns = self::towns();
        $links = [];
        $anyNear = false;
        foreach ($towns as $k => $t) {
            $dmin = INF;
            $imin = 0;
            $est = 0.0;
            $win = [];
            foreach ($hrs as $i => $h) {
                // a cheap box first: 6 degrees is beyond 600 km
                if (abs($h['lat'] - $t[0]) > 6 || abs($h['lon'] - $t[1]) > 6.5) {
                    continue;
                }
                $d = self::km($t[0], $t[1], $h['lat'], $h['lon']);
                if ($d < $dmin) {
                    $dmin = $d;
                    $imin = $i;
                }
                if ($d <= 600) {
                    $v = $d <= $h['r'] ? $h['w'] * $d / max(0.1, $h['r']) : $h['w'] * sqrt($h['r'] / max(0.1, $d));
                    $est = max($est, $v);
                }
                if ($d <= 500) {
                    $win[] = $i;
                }
            }
            if ($dmin > 500) {
                continue;
            }
            $anyNear = $anyNear || $dmin <= 250;
            $from = $win ? $hrs[$win[0]]['t']->copy()->addHours(8)->toDateString() : $hrs[$imin]['t']->copy()->addHours(8)->toDateString();
            $to = $win ? $hrs[end($win)]['t']->copy()->addHours(8)->toDateString() : $from;
            [$rain, $max] = self::rainAt($t[5], $from, Carbon::parse($to)->addDay()->toDateString());
            if ($dmin > 250 && ($rain ?? 0) < 150) {
                continue;
            }
            $sev = self::severity($est, $rain, $dmin);
            $links[] = [null, 'town', $t[2], $t[3], $from, $to, (int) round($dmin), (int) round($est), $rain, $max, null, null, null, $sev, 'measured', null];
        }
        if (! $par && ! $anyNear) {
            return false;
        }
        $loc = fn (Carbon $c) => $c->copy()->addHours(8);
        $a = $loc($hrs[$par[0] ?? 0]['t']);
        $b = $loc($hrs[$par ? end($par) : count($hrs) - 1]['t']);
        $peak = 0.0;
        foreach ($par ?: array_keys($hrs) as $i) {
            $peak = max($peak, $hrs[$i]['w']);
        }
        $peak = (int) (round($peak / 5) * 5);
        // Landfall: the first hours over land after hours at sea, at the nearest town.
        $lands = [];
        $prev = null;
        foreach ($hrs as $h) {
            if ($h['d2l'] === null) {
                continue;
            }
            $land = $h['d2l'] <= 0.5;
            if ($land && $prev === false) {
                $best = null;
                foreach ($towns as $t) {
                    $d = self::km($t[0], $t[1], $h['lat'], $h['lon']);
                    if ($best === null || $d < $best[0]) {
                        $best = [$d, $t];
                    }
                }
                if ($best && $best[0] <= 40) {
                    $w = $best[1][3] . ', ' . $best[1][2];
                    if (! in_array($w, $lands, true)) {
                        $lands[] = $w;
                    }
                }
            }
            $prev = $land;
        }
        $intl = in_array($s['name'], ['UNNAMED', 'NOT_NAMED', ''], true) ? null : ucwords(mb_strtolower($s['name']), '-');
        $local = $intl ? ($names[mb_strtolower($intl)] ?? null) : null;
        $nm = $local && $intl ? $local . ' (' . $intl . ')' : ($local ?: ($intl ?: 'An unnamed depression'));
        $span = $a->toDateString() === $b->toDateString() ? $a->format('M j, Y') : $a->format('M j') . ' to ' . $b->format('M j, Y');
        $summary = self::category($peak) . ' ' . $nm . ($par ? ' was in the area from ' : ' passed near the country from ') . $span
            . ($lands ? ', made landfall in ' . implode('; ', array_slice($lands, 0, 5)) : '') . ', with winds near ' . $peak . ' km/h at its strongest there.';
        $track = [];
        foreach ($hrs as $i => $h) {
            if ($i % 6 === 0 && $h['lat'] >= 3 && $h['lat'] <= 23 && $h['lon'] >= 114 && $h['lon'] <= 130) {
                $track[] = [$loc($h['t'])->format('Y-m-d H:00'), round($h['lat'], 2), round($h['lon'], 2), (int) round($h['w'])];
            }
        }
        $e = ['key' => 'tc:' . $sid, 'kind' => 'storm', 'name' => $intl, 'localName' => $local, 'year' => (int) $a->year, 'start' => $a->toDateString(),
            'end' => $b->toDateString(), 'peak' => mb_strtolower(self::category($peak)) . ', ' . $peak . ' km/h', 'strength' => $peak, 'landfall' => implode('; ', $lands) ?: null,
            'summary' => $summary, 'track' => $track, 'scope' => $anyNear ? 'town' : 'national', 'confidence' => 'measured'];
        self::writeEvent($e, $links, true);

        return true;
    }

    private static function severity(float $est, ?int $rain, float $dist): string
    {
        $rain ??= 0;
        $near = $dist <= 300;
        if ($est >= 118 || ($rain >= 300 && $near) || ($est >= 89 && $rain >= 200)) {
            return 'severe';
        }

        return ($est >= 62 || ($rain >= 150 && $near) || $rain >= 250) ? 'moderate' : 'minor';
    }

    /** The local names of a season's storms, from its table of names: [intl lower => local]. */
    private static function seasonNames(int $year): array
    {
        $key = 'hazard-feed:names:' . $year;
        $hit = Cache::get($key);
        if (is_array($hit) && $year < (int) now()->year) {
            return $hit;
        }
        $r = self::get('https://en.wikipedia.org/w/api.php', ['action' => 'parse', 'page' => $year . ' Pacific typhoon season', 'prop' => 'wikitext', 'format' => 'json', 'formatversion' => 2]);
        $w = (string) ($r?->json('parse.wikitext') ?? '');
        $out = [];
        if (preg_match_all('/^===\s*(?:Super |Violent |Very Strong )?(?:Typhoon|Tropical Storm|Severe Tropical Storm|Tropical Depression)\s+([A-Z][A-Za-z\-]+)\s*\(([A-Z][a-z]+)\)\s*===/m', $w, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $out[mb_strtolower($x[1])] = $x[2];
            }
        }
        if (preg_match_all('/^\|\s*(?:\[\[[^\]|]*\|)?([A-Z][A-Za-z\-]+)\s*\(([A-Z][a-z]+)\)\s*(?:\]\])?\s*\|\|/m', $w, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $out[mb_strtolower($x[1])] ??= $x[2];
            }
        }
        Cache::put($key, $out, now()->addDays(20));
        sleep(2);

        return $out;
    }

    /* ----------------------------------------------------------- weather */

    /** [total mm, wettest day mm] at a square between two days, from the rain weather() read. */
    private static function rainAt(?string $cell, string $from, string $to): array
    {
        $d = self::$rain[$cell] ?? null;
        if (! $d) {
            return [null, null];
        }
        $sum = 0.0;
        $max = 0.0;
        $n = 0;
        for ($x = Carbon::parse($from); $x->lte(Carbon::parse($to)); $x->addDay()) {
            $v = $d[$x->format('Ymd')] ?? null;
            if ($v !== null && $v > -900) {
                $sum += $v;
                $max = max($max, $v);
                $n++;
            }
        }

        return $n ? [(int) round($sum), (int) round($max)] : [null, null];
    }

    /** The running year's daily weather per square (from October before, so a dry spell that began then is seen). */
    public static function fetch(\Closure $say): int
    {
        $byCell = [];
        foreach (self::towns() as $t) {
            $byCell[$t[5]] = true;
        }
        $y = (int) now('Asia/Manila')->year;
        $from = Carbon::create($y - 1, 10, 1);
        $to = now('Asia/Manila')->subDays(2);
        $read = 0;
        foreach (array_keys($byCell) as $cell) {
            [$lat, $lng] = array_map('floatval', explode('_', (string) $cell));
            $r = self::get(self::DAILY, ['parameters' => 'PRECTOTCORR,T2M_MAX', 'community' => 'AG', 'latitude' => $lat, 'longitude' => $lng,
                'start' => $from->format('Ymd'), 'end' => $to->format('Ymd'), 'format' => 'JSON', 'time-standard' => 'LST'], 90);
            if ($r) {
                self::$rain[$cell] = (array) $r->json('properties.parameter.PRECTOTCORR');
                self::$tmax[$cell] = (array) $r->json('properties.parameter.T2M_MAX');
                $read++;
            }
            sleep(2);
        }
        $say('weather squares read ' . $read . ' of ' . count($byCell));

        return $read;
    }

    /** What the running year's weather measured: dry spells, droughts, hot spells, heavy rain, the rains' onset. */
    public static function weather(\Closure $say): array
    {
        $cells = json_decode((string) @gzdecode((string) @file_get_contents(database_path('hazards/cells.json.gz'))), true) ?: [];
        $towns = self::towns();
        $byCell = [];
        foreach ($towns as $k => $t) {
            $byCell[$t[5]][] = $k;
        }
        $y = (int) now('Asia/Manila')->year;
        $to = now('Asia/Manila')->subDays(2);
        $tmax = self::$tmax;
        $read = count(self::$rain);
        if (! $read) {
            return ['read' => 0];
        }
        if (! $cells) {
            // without each square's normals a dry spell or a hot spell cannot be told from the usual
            return ['read' => $read, 'error' => 'the squares\' normals (database/hazards/cells.json.gz) are missing'];
        }
        // The running year's measured events go first, then are written again.
        $old = self::db()->table('as_hazard_events')->where('eventKey', 'like', 'wx:%')->where('year', $y)->pluck('id')->all();
        foreach (array_chunk($old, 500) as $c) {
            self::db()->table('as_hazard_places')->whereIn('eventId', $c)->delete();
            self::db()->table('as_hazard_events')->whereIn('id', $c)->delete();
        }
        $runs = [];
        $onset = Cache::get('hazard-book:' . self::conn() . ':onset') ?: (json_decode((string) @gzdecode((string) @file_get_contents(database_path('hazards/onset.json.gz'))), true) ?: []);
        $jan = Carbon::create($y, 1, 1);
        foreach (self::$rain as $cell => $rain) {
            $c = $cells[$cell] ?? null;
            $normals = $c['normals'] ?? [];
            // months against normal
            $mon = [];
            foreach ($rain as $day => $v) {
                if ($v > -900) {
                    $k = substr($day, 0, 6);
                    $mon[$k] = ($mon[$k] ?? [0, 0]);
                    $mon[$k][0] += $v;
                    $mon[$k][1]++;
                }
            }
            $dev = [];
            foreach ($mon as $k => [$sum, $n]) {
                $nm = $normals[(int) substr($k, 4, 2) - 1] ?? null;
                $dev[] = [$k, $n >= 25 && $nm !== null && $nm >= 60 ? ($sum * 30.4 / $n - $nm) / $nm * 100 : null];
            }
            for ($i = 0; $i < count($dev);) {
                $any = 0;
                $big = 0;
                for ($j = $i; $j < count($dev) && $dev[$j][1] !== null && $dev[$j][1] <= -21; $j++) {
                    $any++;
                }
                for ($j = $i; $j < count($dev) && $dev[$j][1] !== null && $dev[$j][1] < -60; $j++) {
                    $big++;
                }
                $kind = ($big >= 3 || $any >= 5) ? 'drought' : (($any >= 3 || $big >= 2) ? 'dryspell' : null);
                if ($kind) {
                    $span = array_slice($dev, $i, max($any, $big));
                    $start = Carbon::createFromFormat('Ymd', $span[0][0] . '01');
                    if ($start->gte($jan)) {
                        $end = Carbon::createFromFormat('Ymd', end($span)[0] . '28');
                        $pct = (int) round(-array_sum(array_column($span, 1)) / count($span));
                        foreach ($byCell[$cell] ?? [] as $k) {
                            $runs[$kind][$towns[$k][4]][] = [$start, $end, $k, ['pct' => $pct, 'months' => count($span), 'sev' => $kind === 'drought' ? 'severe' : 'moderate']];
                        }
                    }
                    $i += count($span);
                } else {
                    $i++;
                }
            }
            // hot spells and heavy rain with no storm near, from January
            $thr = (float) ($c['heat'] ?? 35);
            $days = array_keys($rain);
            $tx = $tmax[$cell] ?? [];
            $run = [];
            $flush = function () use (&$run, &$runs, $byCell, $cell, $towns, $thr) {
                if (count($run) >= 3) {
                    $top = max(array_column($run, 1));
                    foreach ($byCell[$cell] ?? [] as $k) {
                        $runs['heat'][$towns[$k][4]][] = [Carbon::createFromFormat('Ymd', $run[0][0]), Carbon::createFromFormat('Ymd', end($run)[0]), $k,
                            ['days' => count($run), 'max' => $top, 'sev' => (count($run) >= 7 || $top >= $thr + 2.5) ? 'severe' : 'moderate']];
                    }
                }
                $run = [];
            };
            foreach ($days as $d) {
                if ($d < $jan->format('Ymd')) {
                    continue;
                }
                $v = $tx[$d] ?? -999;
                if ($v > -900 && $v >= $thr) {
                    $run[] = [$d, $v];
                } else {
                    $flush();
                }
                $r = $rain[$d] ?? -999;
                if ($r >= 100 && ! self::stormNear($cell, $d)) {
                    foreach ($byCell[$cell] ?? [] as $k) {
                        $runs['rain'][$towns[$k][4]][] = [Carbon::createFromFormat('Ymd', $d), Carbon::createFromFormat('Ymd', $d), $k, ['max' => (int) round($r), 'sev' => $r >= 180 ? 'severe' : 'moderate']];
                    }
                }
            }
            $flush();
            // the rains' onset this year
            if (! empty($c['drySeason'])) {
                $start = Carbon::create($y, 4, 1);
                for ($d = $start->copy(); $d->lte(Carbon::create($y, 8, 31)) && $d->lte($to->copy()->subDays(30)); $d->addDay()) {
                    $s5 = 0;
                    for ($q = 0; $q < 5; $q++) {
                        $s5 += max(0, (float) ($rain[$d->copy()->addDays($q)->format('Ymd')] ?? 0));
                    }
                    if ($s5 < 40) {
                        continue;
                    }
                    $ok = true;
                    for ($q = 5; $q <= 25 && $ok; $q++) {
                        $s10 = 0;
                        for ($z = 0; $z < 10; $z++) {
                            $s10 += max(0, (float) ($rain[$d->copy()->addDays($q + $z)->format('Ymd')] ?? 0));
                        }
                        $ok = $s10 >= 10;
                    }
                    if ($ok) {
                        $onset[$cell][(string) $y] = $d->format('m-d');
                        break;
                    }
                }
            }
        }
        Cache::put('hazard-book:' . self::conn() . ':onset', $onset, now()->addYear());
        @file_put_contents(storage_path('app/hazard-onset.json'), json_encode($onset));
        // Runs to events, per kind and region, close ones joined.
        $n = 0;
        foreach ($runs as $kind => $regions) {
            foreach ($regions as $region => $items) {
                usort($items, fn ($a, $b) => $a[0] <=> $b[0]);
                $gap = in_array($kind, ['drought', 'dryspell'], true) ? 31 : 3;
                $cl = [];
                foreach ($items as $it) {
                    if ($cl && $it[0]->lte(end($cl)['end']->copy()->addDays($gap))) {
                        $cl[count($cl) - 1]['end'] = $it[1]->gt(end($cl)['end']) ? $it[1] : end($cl)['end'];
                        $cl[count($cl) - 1]['items'][] = $it;
                    } else {
                        $cl[] = ['start' => $it[0], 'end' => $it[1], 'items' => [$it]];
                    }
                }
                foreach ($cl as $c) {
                    $n += self::weatherEvent($kind, $region, $c, $towns);
                }
            }
        }

        return ['read' => $read, 'events' => $n];
    }

    /** Whether a storm was within 600 km of a square's towns that day (its window). */
    private static function stormNear(string $cell, string $ymd): bool
    {
        static $days = null;
        if ($days === null) {
            $days = [];
            $y = (int) now('Asia/Manila')->year;
            $rows = self::db()->table('as_hazard_places')->join('as_hazard_events', 'as_hazard_events.id', '=', 'as_hazard_places.eventId')
                ->join('as_hazard_towns', 'as_hazard_towns.placeKey', '=', 'as_hazard_places.placeKey')
                ->where('as_hazard_events.kind', 'storm')->where('as_hazard_places.level', 'town')->where('as_hazard_places.year', '>=', $y - 1)
                ->get(['as_hazard_towns.cell', 'as_hazard_places.startDate', 'as_hazard_places.endDate']);
            foreach ($rows as $r) {
                for ($d = Carbon::parse($r->startDate); $d->lte(Carbon::parse($r->endDate)->addDay()); $d->addDay()) {
                    $days[$r->cell][$d->format('Ymd')] = true;
                }
            }
        }

        return isset($days[$cell][$ymd]);
    }

    private static function weatherEvent(string $kind, string $region, array $c, array $towns): int
    {
        $a = $c['start'];
        $b = $c['end'];
        $set = array_unique(array_map(fn ($i) => $i[2], $c['items']));
        $span = $a->toDateString() === $b->toDateString() ? $a->format('M j, Y') : $a->format('M j') . ' to ' . $b->format('M j, Y');
        if (in_array($kind, ['drought', 'dryspell'], true)) {
            $pct = (int) round(array_sum(array_map(fn ($i) => $i[3]['pct'], $c['items'])) / count($c['items']));
            $name = $kind === 'drought' ? 'Drought' : 'Dry spell';
            $summary = ($kind === 'drought' ? 'Drought' : 'A dry spell') . ': rain about ' . $pct . '% below normal from ' . $a->format('M Y') . ' to ' . $b->format('M Y') . ' over ' . count($set) . ' towns of ' . $region . '.';
            $peak = $pct . '% below normal';
        } elseif ($kind === 'heat') {
            $top = max(array_map(fn ($i) => $i[3]['max'], $c['items']));
            $name = 'Hot spell';
            $summary = 'A hot spell: up to ' . round($top) . ' C from ' . $span . ' in ' . count($set) . ' towns of ' . $region . '.';
            $peak = 'up to ' . round($top) . ' C';
        } else {
            $top = max(array_map(fn ($i) => $i[3]['max'], $c['items']));
            $name = 'Heavy rain';
            $summary = 'Heavy rain with no storm near: up to ' . $top . ' mm in a day from ' . $span . ' in ' . count($set) . ' towns of ' . $region . '.';
            $peak = $top . ' mm in a day';
        }
        $e = ['key' => 'wx:' . $kind . ':' . $region . ':' . $a->toDateString(), 'kind' => $kind, 'name' => $name, 'year' => (int) $a->year, 'start' => $a->toDateString(),
            'end' => $b->toDateString(), 'peak' => $peak, 'summary' => $summary, 'scope' => 'town', 'confidence' => 'measured'];
        $links = [];
        $done = [];
        foreach ($c['items'] as [$s, $en, $k, $x]) {
            if (isset($done[$k])) {
                continue;
            }
            $done[$k] = true;
            $t = $towns[$k];
            $links[] = match ($kind) {
                'drought', 'dryspell' => [null, 'town', $t[2], $t[3], $s->toDateString(), $en->toDateString(), null, null, null, null, $x['pct'], $x['months'], null, $x['sev'], 'measured', null],
                'heat' => [null, 'town', $t[2], $t[3], $s->toDateString(), $en->toDateString(), null, null, null, null, null, $x['days'], $x['max'], $x['sev'], 'measured', null],
                default => [null, 'town', $t[2], $t[3], $s->toDateString(), $en->toDateString(), null, null, $x['max'], $x['max'], null, null, null, $x['sev'], 'measured', null],
            };
        }
        self::writeEvent($e, $links, true);

        return 1;
    }

    /* ----------------------------------------------------------- reports */

    /** The year's disaster pages: new events, and the provinces a typhoon's page names. */
    public static function reports(\Closure $say): array
    {
        $y = (int) now('Asia/Manila')->year;
        $found = [];
        foreach ([[$y . '0101', $y . '0630'], [$y . '0701', $y . '1231']] as [$a, $b]) {
            $r = self::get('https://reliefweb.int/disasters', ['advanced-search' => '(C188)_(DA' . $a . '-' . $b . ')']);
            if ($r && preg_match_all('#href="(https://reliefweb\.int/disaster/([a-z]{2})-(\d{4})-\d+-[a-z]{3}[^"]*)"[^>]*>([^<]+)<#', $r->body(), $m, PREG_SET_ORDER)) {
                foreach ($m as $x) {
                    $found[$x[1]] = ['kind' => $x[2], 'year' => (int) $x[3], 'title' => html_entity_decode(trim($x[4]))];
                }
            }
            sleep(4);
        }
        $have = self::db()->table('as_hazard_events')->where('eventKey', 'like', 'page:%')->pluck('eventKey')->flip()->all();
        $kinds = ['fl' => 'flood', 'ff' => 'flood', 'ls' => 'landslide', 'ms' => 'landslide', 'vo' => 'volcano', 'eq' => 'earthquake', 'dr' => 'drought', 'tc' => 'storm', 'ec' => 'storm'];
        $n = 0;
        foreach ($found as $url => $f) {
            $slug = basename(parse_url($url, PHP_URL_PATH));
            if (isset($have['page:' . $slug]) || ! isset($kinds[$f['kind']]) || Cache::has('hazard-feed:page:' . $slug)) {
                continue;
            }
            $r = self::get($url);
            sleep(4);
            if (! $r) {
                continue;
            }
            $html = $r->body();
            $desc = '';
            if (preg_match('#id="overview-content"[^>]*>(.*?)</div>\s*</div>#s', $html, $m) || preg_match('#id="overview"[^>]*>(.*?)</section>#s', $html, $m)) {
                $desc = trim(preg_replace('/\s+/', ' ', strip_tags($m[1])));
            }
            Cache::put('hazard-feed:page:' . $slug, 1, now()->addDays(40));
            $provs = self::provincesIn($desc);
            if (! $provs) {
                continue;
            }
            $month = preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+' . $f['year'] . '/', $f['title'], $mm) ? Carbon::parse('1 ' . $mm[1] . ' ' . $f['year']) : Carbon::create($f['year'], 1, 1);
            $kind = $kinds[$f['kind']];
            if ($kind === 'storm') {
                // the provinces join the storm of that name and year
                if (preg_match('/(?:Typhoon|Tropical Storm|Tropical Depression|Severe Tropical Storm)\s+([A-Z][A-Za-z\-]+)/', $f['title'], $sm)) {
                    $ev = self::db()->table('as_hazard_events')->where('kind', 'storm')->where('year', $f['year'])->whereRaw('LOWER(name) = ?', [mb_strtolower($sm[1])])->first();
                    if ($ev) {
                        $rows = [];
                        foreach ($provs as $p) {
                            if (! self::db()->table('as_hazard_places')->where('eventId', $ev->id)->where('level', 'province')->where('province', $p)->exists()) {
                                $rows[] = HazardBook::placeRow((int) $ev->id, [null, 'province', $p, null, $ev->startDate, $ev->endDate, null, null, null, null, null, null, null,
                                    str_contains(mb_strtolower($desc), 'state of calamity') ? 'severe' : 'moderate', 'province', 'named in the damage reports'], self::regions());
                            }
                        }
                        if ($rows) {
                            self::db()->table('as_hazard_places')->insert($rows);
                            self::$touched[] = (int) $ev->id;
                            $n++;
                        }
                    }
                }
                continue;
            }
            $label = ['flood' => 'Floods', 'landslide' => 'Landslides', 'volcano' => 'Volcanic unrest', 'earthquake' => 'Earthquake', 'drought' => 'Drought'][$kind];
            $cause = preg_match('/southwest monsoon|habagat/i', $desc) ? 'the southwest monsoon' : (preg_match('/shear line/i', $desc) ? 'a shear line'
                : (preg_match('/low[- ]pressure area/i', $desc) ? 'a low pressure area' : (preg_match('/northeast monsoon|amihan/i', $desc) ? 'the northeast monsoon' : null)));
            $e = ['key' => 'page:' . $slug, 'kind' => $kind, 'name' => $label, 'year' => (int) $month->year, 'start' => $month->toDateString(), 'end' => $month->toDateString(),
                'summary' => $label . ' in ' . $month->format('M Y') . ($cause && in_array($kind, ['flood', 'landslide'], true) ? ' after ' . $cause : '') . ' in '
                    . implode(', ', array_slice($provs, 0, 6)) . (count($provs) > 6 ? ' and more' : '') . '.', 'scope' => 'province', 'confidence' => 'reported'];
            $links = array_map(fn ($p) => [null, 'province', $p, null, $month->toDateString(), $month->toDateString(), null, null, null, null, null, null, null, 'moderate', 'province', 'named in the reports'], $provs);
            self::writeEvent($e, $links, false);
            $n++;
        }

        return ['pages' => count($found), 'written' => $n];
    }

    /** The provinces a text names, in our names (the cities a report names stand for theirs). */
    private static function provincesIn(string $text): array
    {
        $t = ' ' . mb_strtolower(strtr($text, ['ñ' => 'n', 'Ñ' => 'N'])) . ' ';
        $alias = ['davao de oro' => 'Compostela Valley', 'north cotabato' => 'Cotabato', 'western samar' => 'Samar', 'metro manila' => 'Manila City',
            'cebu city' => 'Cebu', 'iloilo city' => 'Iloilo', 'davao city' => 'Davao Del Sur', 'zamboanga city' => 'Zamboanga Del Sur', 'cagayan de oro' => 'Misamis Oriental',
            'baguio' => 'Benguet', 'tacloban' => 'Leyte', 'legazpi' => 'Albay', 'bacolod' => 'Negros Occidental', 'general santos' => 'South Cotabato', 'butuan' => 'Agusan Del Norte',
            'puerto princesa' => 'Palawan', 'maguindanao del norte' => 'Maguindanao', 'maguindanao del sur' => 'Maguindanao', 'marikina' => 'Ncr, Second District', 'quezon city' => 'Ncr, Second District'];
        $names = array_keys(self::regions());
        $phr = [];
        foreach ($names as $p) {
            if (! str_starts_with($p, 'Ncr') && $p !== 'Manila City') {
                $phr[mb_strtolower($p)] = $p;
            }
        }
        $phr += $alias;
        uksort($phr, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $out = [];
        foreach ($phr as $k => $p) {
            $pat = '/(?<![a-z])' . preg_quote($k, '/') . ($k === 'isabela' || $k === 'quezon' || $k === 'cotabato' ? '(?!\s+city)' : '') . ($k === 'cagayan' ? '(?!\s+(?:de\s+oro|valley))' : '') . '(?![a-z])/';
            if (preg_match($pat, $t)) {
                $out[$p] = true;
                $t = preg_replace($pat, ' # ', $t);
            }
        }

        return array_keys($out);
    }

    /** [province => region] */
    private static function regions(): array
    {
        static $r = null;
        if ($r === null) {
            $r = [];
            foreach (self::towns() as $t) {
                $r[$t[2]] = $t[4];
            }
        }

        return $r;
    }

    /* ------------------------------------------------------------ quakes */

    /** Strong shallow quakes near land in the last $days days, and the towns that shook. */
    public static function quakes(\Closure $say, int $days = 75): array
    {
        $r = self::get('https://earthquake.usgs.gov/fdsnws/event/1/query', ['format' => 'geojson', 'starttime' => now('UTC')->subDays($days)->toDateString(),
            'minlatitude' => 4, 'maxlatitude' => 21.5, 'minlongitude' => 116, 'maxlongitude' => 128, 'minmagnitude' => 6.0]);
        if (! $r) {
            return ['error' => 'no list'];
        }
        $towns = self::towns();
        $n = 0;
        foreach ((array) $r->json('features') as $f) {
            $m = (float) ($f['properties']['mag'] ?? 0);
            [$lng, $lat, $dep] = array_pad((array) ($f['geometry']['coordinates'] ?? []), 3, 10);
            if ($m < 6 || $dep > 70) {
                continue;
            }
            $hits = [];
            foreach ($towns as $t) {
                $d = self::km((float) $lat, (float) $lng, $t[0], $t[1]);
                if ($d > 250) {
                    continue;
                }
                $rr = sqrt($d ** 2 + max((float) $dep, 10) ** 2);
                $i = 12.27 + 2.270 * ($m - 6) + 0.1304 * ($m - 6) ** 2 - 1.30 * log10($rr) - 0.0007070 * $rr + 1.95 * max(0, log10($rr / 50)) - 0.577 * $m * log10($rr);
                if ($i >= 6.0) {
                    $hits[] = [$t, $d, $i];
                }
            }
            if (! $hits) {
                continue;
            }
            usort($hits, fn ($a, $b) => $a[1] <=> $b[1]);
            $day = Carbon::createFromTimestampMs((int) $f['properties']['time'], 'Asia/Manila');
            $near = $hits[0];
            $e = ['key' => 'eq:' . ($f['id'] ?? $day->timestamp), 'kind' => 'earthquake', 'name' => sprintf('Magnitude %.1f earthquake', $m), 'year' => (int) $day->year,
                'start' => $day->toDateString(), 'end' => $day->toDateString(), 'peak' => sprintf('magnitude %.1f', $m), 'strength' => $m,
                'summary' => sprintf('A magnitude %.1f earthquake %d km from %s, %s on %s, %d km deep.', $m, (int) $near[1], $near[0][3], $near[0][2], $day->format('M j, Y'), (int) $dep),
                'scope' => 'town', 'confidence' => 'measured'];
            $links = array_map(fn ($h) => [null, 'town', $h[0][2], $h[0][3], $day->toDateString(), $day->toDateString(), (int) $h[1], null, null, null, null, null, round($h[2], 1),
                $h[2] >= 8 ? 'severe' : ($h[2] >= 7 ? 'moderate' : 'minor'), 'measured', null], $hits);
            self::writeEvent($e, $links, true);
            $n++;
        }

        return ['quakes' => $n];
    }

    /* ------------------------------------------------------------- write */

    /** An event and its links: the event by its key, its own links replaced ($measured: only the measured ones). */
    private static function writeEvent(array $e, array $links, bool $measured): void
    {
        $db = self::db();
        $db->table('as_hazard_events')->upsert([HazardBook::eventRow($e, now())], ['eventKey'],
            ['kind', 'name', 'localName', 'year', 'startDate', 'endDate', 'peak', 'strength', 'landfall', 'summary', 'farmImpact', 'damagePhp', 'areaHa', 'crops', 'track', 'scope', 'confidence', 'enso', 'updated_at']);
        $id = (int) $db->table('as_hazard_events')->where('eventKey', mb_substr((string) $e['key'], 0, 100))->value('id');
        if (! $id) {
            return;
        }
        $q = $db->table('as_hazard_places')->where('eventId', $id)->where('level', 'town');
        $measured ? $q->where('basis', 'measured')->delete() : $q->delete();
        if (! $measured) {
            $db->table('as_hazard_places')->where('eventId', $id)->where('level', 'province')->delete();
        }
        $reg = self::regions();
        foreach (array_chunk(array_map(fn ($l) => HazardBook::placeRow($id, $l, $reg), $links), 1000) as $chunk) {
            $db->table('as_hazard_places')->insert($chunk);
        }
        self::$touched[] = $id;
    }
}
