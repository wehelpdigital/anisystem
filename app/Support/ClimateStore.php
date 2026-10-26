<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The climate shelf (2026-10-08): ENSO and the weather history, collected
 * by the scheduler (climate:enso daily, climate:history hourly in small
 * batches) and read from our own tables. NPK Plus's season reading asks
 * nothing outside on a calculation; a place nobody has asked about before
 * is filled once, the first time, and kept.
 *
 * Weather history is kept per quarter degree square (about 28 km), as
 * weekly sums: week N of a year is its days (N-1)*7+1 to N*7 (the 53rd
 * holds the last one or two days). From Open-Meteo's archive (ERA5), free
 * and keyless.
 *
 * Twenty years since 2026-10-18 (the owner: "let's make the 20 year weather
 * climate history the default, save that in the database per year or so"):
 * a cell holds every week from January of YEARS + 1 years ago, and its weeks
 * carry the extremes too (the wettest day, days of 10 and 50 mm or more, dry
 * days, the strongest gust and the days of storm force gusts). A year that
 * is over never changes, so it is read once; only the last four months are
 * read again (ERA5's early days are replaced by the final ones). A cell from
 * before (eleven years, sums only) is read again once, whole, by the
 * collector under a daily budget, or at once when an analysis needs it.
 * record() turns the twenty years into a month by month table, with what El
 * Niño and La Niña years did there (NOAA's ONI history, as_enso_seasons), and
 * words() says it for a prompt: the analyses read that instead of searching
 * the web for a place's weather history.
 */
final class ClimateStore
{
    use Concerns\ShelfConnection;

    /** The years of history every reader gets (the full years before this one). */
    public const YEARS = 20;

    /** 2 = the weeks carry the extremes. */
    public const SHAPE = 2;

    /**
     * Archive calls the collector may spend in a day, counted the way
     * Open-Meteo weighs them (a call per two weeks of days, up to ten
     * variables). The free plan allows 10,000; an analysis waiting on a cell
     * is never refused, only counted.
     */
    public const DAILY_BUDGET = 8000;

    /**
     * A gust the record counts as a storm's, in km/h. ERA5's gusts reach 60
     * in a cold season monsoon surge too; at 70 they gather in the typhoon
     * months (checked on the Nueva Ecija cells, 2006 to 2025).
     */
    public const STORM_GUST = 70;

    /**
     * The archive's daily variables: the weeks' seven and, since 2026-10-19,
     * the wind, the humidity and the weather code that the kept days
     * (WeatherDays) carry too. Ten are still one weighted call.
     */
    private const DAILY = WeatherDays::DAILY;

    /** NOAA's three month seasons, by their middle month. */
    private const SEASONS = ['DJF' => 1, 'JFM' => 2, 'FMA' => 3, 'MAM' => 4, 'AMJ' => 5, 'MJJ' => 6, 'JJA' => 7, 'JAS' => 8, 'ASO' => 9, 'SON' => 10, 'OND' => 11, 'NDJ' => 12];

    private const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /* ------------------------------------------------------------- ENSO */

    /**
     * The latest stored ENSO reading. One older than Freshness::ENSO_DAYS
     * (the collector stopped) or none at all is read from NOAA now and kept,
     * at most once in half an hour; when NOAA does not answer, the last kept
     * one stands.
     */
    public static function enso(): ?array
    {
        if (! Cache::remember(self::cacheKey('has:enso'), 600, fn () => self::schema()->hasTable('as_enso_readings'))) {
            return EnsoOutlook::state();
        }
        $r = Cache::remember(self::cacheKey('enso:last'), 600, fn () => (array) (self::db()->table('as_enso_readings')->orderByDesc('fetchedAt')->first() ?? []));
        if ((! $r || ! Freshness::ensoFresh($r['fetchedAt'] ?? null)) && Cache::add(self::cacheKey('enso:tried'), 1, now()->addMinutes(30))) {
            $fresh = self::refreshEnso();
            if ($fresh) {
                return $fresh;
            }
        }
        if (! $r) {
            return null;
        }

        return ['oni' => (float) $r['oni'], 'phase' => $r['phase'], 'strength' => (string) $r['strength'], 'label' => $r['label'],
            'season' => $r['season'], 'alert' => $r['alert'], 'asOf' => Carbon::parse($r['fetchedAt'])->toDateString()];
    }

    /**
     * Read NOAA now and keep the reading (one row a day at most), the whole
     * ONI history and the advisory (EnsoOutlook::readAdvisory, one row per
     * advisory in as_enso_advisories).
     */
    public static function refreshEnso(): ?array
    {
        try {
            $txt = Http::timeout(15)->get(EnsoOutlook::ONI_URL)->body();
        } catch (\Throwable $e) {
            Log::warning('climate: ONI not read', ['e' => $e->getMessage()]);

            return null;
        }
        $oni = null;
        $season = null;
        $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
        $history = [];
        foreach ($rows as $row) {
            $p = preg_split('/\s+/', $row);
            if (count($p) >= 4 && isset(self::SEASONS[$p[0]]) && ctype_digit($p[1]) && is_numeric($p[3])) {
                $history[] = ['year' => (int) $p[1], 'season' => $p[0], 'mid' => self::SEASONS[$p[0]], 'oni' => round((float) $p[3], 2)];
            }
        }
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $p = preg_split('/\s+/', $rows[$i]);
            if (count($p) >= 4 && is_numeric($p[3])) {
                $oni = (float) $p[3];
                $season = $p[0] . ' ' . $p[1];
                break;
            }
        }
        if ($oni === null) {
            return null;
        }
        self::keepOni($history);
        $a = abs($oni);
        $strength = $a >= 2 ? 'very strong' : ($a >= 1.5 ? 'strong' : ($a >= 1 ? 'moderate' : 'weak'));
        $phase = $oni >= 0.5 ? 'el_nino' : ($oni <= -0.5 ? 'la_nina' : 'neutral');
        $label = $phase === 'neutral' ? 'ENSO neutral' : ('A ' . $strength . ' ' . ($phase === 'el_nino' ? 'El Niño' : 'La Niña'));
        // The advisory is a nicety; the ONI is the number. It is kept on its own, one row per advisory.
        $adv = EnsoOutlook::readAdvisory();
        if ($adv) {
            EnsoOutlook::keepAdvisory($adv);
        }
        $alert = $adv['alert'] ?? null;
        $synopsis = $adv['synopsis'] ?? null;
        $row = ['oni' => $oni, 'season' => $season, 'phase' => $phase, 'strength' => $phase === 'neutral' ? null : $strength, 'label' => $label,
            'alert' => $alert ? mb_substr($alert, 0, 80) : null, 'synopsis' => $synopsis, 'fetchedAt' => now(), 'updated_at' => now()];
        if (self::schema()->hasTable('as_enso_readings')) {
            $today = self::db()->table('as_enso_readings')->whereDate('fetchedAt', now()->toDateString())->first();
            $today ? self::db()->table('as_enso_readings')->where('id', $today->id)->update($row) : self::db()->table('as_enso_readings')->insert($row + ['created_at' => now()]);
            Cache::forget(self::cacheKey('enso:last'));
        }

        return ['oni' => $oni, 'phase' => $phase, 'strength' => (string) $row['strength'], 'label' => $label, 'season' => $season, 'alert' => $row['alert'], 'asOf' => now()->toDateString()];
    }

    /**
     * NOAA's ONI history kept whole (2026-10-18). Seasons more than a year
     * old are final and written once; the last twelve are written again,
     * since NOAA revises them as the base period moves.
     */
    private static function keepOni(array $history): void
    {
        if (! $history || ! self::schema()->hasTable('as_enso_seasons')) {
            return;
        }
        try {
            $have = (int) self::db()->table('as_enso_seasons')->count();
            $rows = $have >= count($history) - 12 ? array_slice($history, -12) : $history;
            foreach (array_chunk($rows, 300) as $chunk) {
                self::db()->table('as_enso_seasons')->upsert($chunk, ['year', 'season'], ['oni', 'mid']);
            }
            Cache::forget(self::cacheKey('oni'));
        } catch (\Throwable $e) {
            Log::warning('climate: ONI history not kept', ['e' => $e->getMessage()]);
        }
    }

    /**
     * The ONI of every month since 1950, as [year][month] = anomaly (the
     * three month season centred on it). Empty until the history is kept.
     */
    public static function oniByMonth(): array
    {
        if (! self::schema()->hasTable('as_enso_seasons')) {
            return [];
        }

        return Cache::remember(self::cacheKey('oni'), 86400, function () {
            $out = [];
            foreach (self::db()->table('as_enso_seasons')->get(['year', 'mid', 'oni']) as $r) {
                $out[(int) $r->year][(int) $r->mid] = (float) $r->oni;
            }

            return $out;
        });
    }

    /**
     * What ENSO did in the months of a season that is over or under way, in
     * one line for a prompt (2026-10-19, the season reports): each month's
     * ONI from the kept history, and the phase it adds up to. '' when the
     * history holds none of those months (the ONI runs about a month behind).
     */
    public static function oniWords(string $from, string $to): string
    {
        $oni = self::oniByMonth();
        $a = Carbon::parse($from)->startOfMonth();
        $b = Carbon::parse($to)->startOfMonth();
        $bits = [];
        $vals = [];
        for ($m = $a->copy(); $m->lte($b) && count($bits) < 18; $m->addMonth()) {
            $v = $oni[(int) $m->year][(int) $m->month] ?? null;
            if ($v !== null) {
                $bits[] = self::MONTHS[(int) $m->month] . ' ' . $m->year . ' ' . sprintf('%+.2f', $v);
                $vals[] = $v;
            }
        }
        if (! $bits) {
            return '';
        }
        $avg = array_sum($vals) / count($vals);
        $phase = $avg >= 0.5 ? 'an El Niño season' : ($avg <= -0.5 ? 'a La Niña season' : 'an ENSO neutral season');

        return 'ENSO IN THE SEASON\'S MONTHS (the ONI, the 3 month running sea temperature anomaly; +0.5 or more is El Niño, -0.5 or less La Niña): '
            . implode(', ', $bits) . ' (' . $phase . ').';
    }

    /* ---------------------------------------------------------- weather */

    public static function cellKey(float $lat, float $lng): string
    {
        return sprintf('%.2f,%.2f', round($lat * 4) / 4, round($lng * 4) / 4);
    }

    /** The cell for a point, made if new (not yet filled). */
    public static function cell(float $lat, float $lng, ?string $label = null): object
    {
        $key = self::cellKey($lat, $lng);
        $c = self::db()->table('as_weather_cells')->where('cellKey', $key)->first();
        if (! $c) {
            [$qlat, $qlng] = array_map('floatval', explode(',', $key));
            self::db()->table('as_weather_cells')->insertOrIgnore(['cellKey' => $key, 'lat' => $qlat, 'lng' => $qlng, 'label' => $label ? mb_substr($label, 0, 160) : null, 'created_at' => now(), 'updated_at' => now()]);
            $c = self::db()->table('as_weather_cells')->where('cellKey', $key)->first();
        }

        return $c;
    }

    /** The first day a full cell holds: January of YEARS + 1 years ago. */
    public static function start(): Carbon
    {
        return now('Asia/Manila')->subYears(self::YEARS + 1)->startOfYear()->startOfDay();
    }

    /** Whether the weeks can carry the extremes (the 2026-10-18 columns are there). */
    public static function extremes(): bool
    {
        return (bool) Cache::remember(self::cacheKey('shape2'), 600, fn () => self::schema()->hasTable('as_weather_weeks')
            && self::schema()->hasColumn('as_weather_weeks', 'gust60') && self::schema()->hasColumn('as_weather_cells', 'shape'));
    }

    /**
     * What a cell still lacks, or null when it holds the twenty years in
     * the current shape: 'new' (nothing yet), 'shape' (eleven years of sums,
     * from before), 'older' (the years before what it holds).
     */
    public static function lacks(object $cell): ?string
    {
        if (! $cell->fetchedThrough) {
            return 'new';
        }
        if (! self::extremes()) {
            return null;
        }
        if ((int) ($cell->shape ?? 1) < self::SHAPE) {
            return 'shape';
        }
        if (! $cell->fetchedFrom || Carbon::parse($cell->fetchedFrom)->gt(self::start())) {
            return 'older';
        }

        return null;
    }

    /** Weighted archive calls spent today, and what is left of the collector's budget. */
    public static function budgetLeft(): int
    {
        return self::DAILY_BUDGET - (int) Cache::get(self::cacheKey('om:' . now('UTC')->toDateString()), 0);
    }

    private static function spend(Carbon $from, Carbon $to): void
    {
        $key = self::cacheKey('om:' . now('UTC')->toDateString());
        $w = (int) max(1, ceil(($from->diffInDays($to) + 1) / 14));
        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key, $w);
    }

    /** What a whole twenty year read of a cell costs, in Open-Meteo's weighted calls. */
    public static function fullWeight(): int
    {
        return (int) ceil((self::start()->diffInDays(now('Asia/Manila')) + 1) / 14);
    }

    /**
     * Fill a cell from the archive and return the weeks written.
     *
     * New, from before (sums only) or short of the older years: everything
     * from start(), in one read, so every week carries the extremes. Else
     * the last four months before what it holds, from the start of their
     * week (ERA5's early days are replaced by the final ones within about
     * three months, so those weeks are summed again; older weeks never
     * change, and are not asked for again: 2026-10-12, it used to re-read a
     * year and a half every fortnight).
     */
    public static function fill(object $cell): int
    {
        $whole = self::lacks($cell) !== null || ! $cell->fetchedThrough;
        if (! $whole) {
            $start = Carbon::parse($cell->fetchedThrough)->subDays(120);
            $start = $start->copy()->subDays(($start->dayOfYear - 1) % 7)->startOfDay();
        } else {
            $start = self::start();
        }
        $end = now('Asia/Manila')->subDays(6)->startOfDay();
        $shape2 = self::extremes();
        try {
            $r = Http::timeout($whole ? 60 : 40)->retry(1, 2000, throw: false)->get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude' => (float) $cell->lat, 'longitude' => (float) $cell->lng, 'timezone' => 'Asia/Manila',
                'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
                'daily' => self::DAILY,
            ]);
        } catch (\Throwable $e) {
            Log::warning('climate: archive not read', ['cell' => $cell->cellKey, 'e' => $e->getMessage()]);

            return 0;
        }
        self::spend($start, $end);
        $d = $r->ok() ? (array) $r->json('daily') : [];
        if (empty($d['time'])) {
            if (! $r->ok()) {
                Log::warning('climate: archive refused', ['cell' => $cell->cellKey, 'status' => $r->status(), 'body' => mb_substr((string) $r->body(), 0, 200)]);
            }

            return 0;
        }
        // The last days of the answer are kept day by day as well (the season reports and Realign read them).
        try {
            WeatherDays::keep((string) $cell->cellKey, $d);
        } catch (\Throwable $e) {
            report($e);
        }
        $weeks = [];
        foreach ($d['time'] as $i => $day) {
            $t = Carbon::parse($day);
            $wn = intdiv($t->dayOfYear - 1, 7) + 1;
            $k = $t->year . '-' . $wn;
            $w = $weeks[$k] ?? ['cellId' => $cell->id, 'year' => $t->year, 'week' => $wn, 'days' => 0, 'rain' => 0, 'rad' => 0, 'et0' => 0,
                'tmean' => 0, 'tmax' => 0, 'hot30' => 0, 'hot32' => 0, 'hot35' => 0, 'cool15' => 0,
                'rainMax' => 0, 'wet10' => 0, 'wet50' => 0, 'dry1' => 0, 'gustMax' => null, 'gust60' => 0];
            $tx = (float) ($d['temperature_2m_max'][$i] ?? 0);
            $rain = (float) ($d['precipitation_sum'][$i] ?? 0);
            $gust = $d['wind_gusts_10m_max'][$i] ?? null;
            $w['days']++;
            $w['rain'] += $rain;
            $w['rad'] += (float) ($d['shortwave_radiation_sum'][$i] ?? 0);
            $w['et0'] += (float) ($d['et0_fao_evapotranspiration'][$i] ?? 0);
            $w['tmean'] += (float) ($d['temperature_2m_mean'][$i] ?? 0);
            $w['tmax'] += $tx;
            $w['hot30'] += $tx >= 30 ? 1 : 0;
            $w['hot32'] += $tx >= 32 ? 1 : 0;
            $w['hot35'] += $tx >= 35 ? 1 : 0;
            $w['cool15'] += (float) ($d['temperature_2m_min'][$i] ?? 20) <= 15 ? 1 : 0;
            $w['rainMax'] = max($w['rainMax'], $rain);
            $w['wet10'] += $rain >= 10 ? 1 : 0;
            $w['wet50'] += $rain >= 50 ? 1 : 0;
            $w['dry1'] += $rain < 1 ? 1 : 0;
            if (is_numeric($gust)) {
                $w['gustMax'] = max((float) ($w['gustMax'] ?? 0), (float) $gust);
                $w['gust60'] += (float) $gust >= 60 ? 1 : 0;
            }
            $weeks[$k] = $w;
        }
        $cols = ['days', 'rain', 'rad', 'et0', 'tmean', 'tmax', 'hot30', 'hot32', 'hot35', 'cool15'];
        $extra = ['rainMax', 'wet10', 'wet50', 'dry1', 'gustMax', 'gust60'];
        $rows = array_map(function ($w) use ($shape2, $extra) {
            $w = array_merge($w, ['rain' => round($w['rain'], 1), 'rad' => round($w['rad'], 1), 'et0' => round($w['et0'], 1),
                'tmean' => round($w['tmean'], 1), 'tmax' => round($w['tmax'], 1), 'rainMax' => round($w['rainMax'], 1),
                'gustMax' => $w['gustMax'] !== null ? round($w['gustMax'], 1) : null]);

            return $shape2 ? $w : array_diff_key($w, array_flip($extra));
        }, array_values($weeks));
        foreach (array_chunk($rows, 200) as $chunk) {
            self::db()->table('as_weather_weeks')->upsert($chunk, ['cellId', 'year', 'week'], $shape2 ? array_merge($cols, $extra) : $cols);
        }
        $set = ['fetchedThrough' => end($d['time']), 'updated_at' => now()];
        if ($whole && $shape2) {
            $set += ['fetchedFrom' => $d['time'][0], 'shape' => self::SHAPE];
        }
        self::db()->table('as_weather_cells')->where('id', $cell->id)->update($set);

        return count($rows);
    }

    /**
     * A cell ready to read: filled when new, and, when $whole, brought to the
     * twenty years and the extremes at once (an analysis waiting for it).
     */
    public static function ready(object $cell, bool $whole = false): object
    {
        $lacks = self::lacks($cell);
        if ($lacks === 'new' || ($whole && $lacks !== null)) {
            self::fill($cell);
            $cell = self::db()->table('as_weather_cells')->where('id', $cell->id)->first();
        }
        self::db()->table('as_weather_cells')->where('id', $cell->id)->update(['lastUsedAt' => now()]);

        return $cell;
    }

    /** Every stored week of a cell, as [year][week] => row. */
    private static function weeks(object $cell): array
    {
        $by = [];
        foreach (self::db()->table('as_weather_weeks')->where('cellId', $cell->id)->get() as $r) {
            $by[(int) $r->year][(int) $r->week] = $r;
        }

        return $by;
    }

    /** weekTable()'s answers in this request, by cell. */
    private static array $weekMemo = [];

    /**
     * Every kept week of a point's cell as [year][week] => [days, rain, et0,
     * hot35], for a reader that runs its own water count over the years
     * (App\Support\WaterBudget, 2026-10-26). Read only: a cell not yet
     * filled answers null and nothing is fetched or made, since it is read
     * while a saved report opens.
     */
    public static function weekTable(float $lat, float $lng): ?array
    {
        $key = self::cellKey($lat, $lng);
        if (array_key_exists($key, self::$weekMemo)) {
            return self::$weekMemo[$key];
        }
        if (! self::schema()->hasTable('as_weather_weeks')) {
            return self::$weekMemo[$key] = null;
        }
        $cell = self::db()->table('as_weather_cells')->where('cellKey', $key)->first();
        if (! $cell || ! $cell->fetchedThrough) {
            return self::$weekMemo[$key] = null;
        }
        $by = [];
        foreach (self::db()->table('as_weather_weeks')->where('cellId', $cell->id)->get(['year', 'week', 'days', 'rain', 'et0', 'hot35']) as $r) {
            $by[(int) $r->year][(int) $r->week] = ['days' => (int) $r->days, 'rain' => (float) $r->rain, 'et0' => (float) $r->et0, 'hot35' => (int) $r->hot35];
        }

        return self::$weekMemo[$key] = $by ?: null;
    }

    /**
     * The same weeks in each of the last twenty years: rain, sun, the water
     * the air pulls from a crop, heat. From the shelf; a new cell is filled
     * once first. Null when there is no history to be had.
     */
    public static function season(float $lat, float $lng, Carbon $from, int $days, ?string $label = null): ?array
    {
        if (! self::schema()->hasTable('as_weather_weeks')) {
            return null;
        }
        $cell = self::ready(self::cell($lat, $lng, $label));
        $by = self::weeks($cell);
        if (! $by) {
            return null;
        }
        $startWeek = intdiv($from->dayOfYear - 1, 7) + 1;
        $n = (int) ceil($days / 7);
        $years = [];
        for ($y = (int) now('Asia/Manila')->year - self::YEARS; $y <= (int) now('Asia/Manila')->year; $y++) {
            $sum = ['days' => 0, 'rain' => 0.0, 'rad' => 0.0, 'et0' => 0.0, 'tmean' => 0.0, 'tmax' => 0.0, 'hot30' => 0, 'hot32' => 0, 'hot35' => 0, 'cool15' => 0];
            $yy = $y;
            $wk = $startWeek;
            for ($k = 0; $k < $n; $k++) {
                $r = $by[$yy][$wk] ?? null;
                if ($r) {
                    foreach ($sum as $f => $v) {
                        $sum[$f] += (float) $r->{$f};
                    }
                }
                $wk++;
                if ($wk > 53) {
                    $wk = 1;
                    $yy++;
                }
            }
            if ($sum['days'] < $days * 0.9) {
                continue;
            }
            $dd = $sum['days'];
            $years[] = ['year' => $y, 'rain' => round($sum['rain'] * $days / $dd), 'rad' => round($sum['rad'] / $dd, 1), 'et0' => round($sum['et0'] * $days / $dd),
                'tmean' => round($sum['tmean'] / $dd, 1), 'tmax' => round($sum['tmax'] / $dd, 1),
                'hot30' => (int) round($sum['hot30'] * $days / $dd), 'hot32' => (int) round($sum['hot32'] * $days / $dd), 'hot35' => (int) round($sum['hot35'] * $days / $dd),
                'cool15' => (int) round($sum['cool15'] * $days / $dd)];
        }
        if (! $years) {
            return null;
        }
        $avg = [];
        foreach (['rain', 'rad', 'et0', 'tmean', 'tmax', 'hot30', 'hot32', 'hot35', 'cool15'] as $k) {
            $avg[$k] = round(array_sum(array_column($years, $k)) / count($years), 1);
        }

        return ['years' => $years, 'avg' => $avg, 'cell' => $cell->cellKey, 'through' => $cell->fetchedThrough,
            'span' => $years[0]['year'] . ' to ' . end($years)['year']];
    }

    /**
     * The same days in each of the last twenty years, the shape
     * FieldContext::climate() always gave (it read five years from the
     * archive, five calls a place, until 2026-10-18): from, to, rain, days of
     * 10 mm or more, the wettest day, days of 35 C or more, the strongest
     * gust. A week is counted when its middle day falls inside the window.
     */
    public static function sameDays(float $lat, float $lng, Carbon $from, int $days, ?string $label = null): array
    {
        if (! self::schema()->hasTable('as_weather_weeks')) {
            return [];
        }
        $cell = self::ready(self::cell($lat, $lng, $label), true);
        $by = self::weeks($cell);
        $ext = self::extremes() && (int) ($cell->shape ?? 1) >= self::SHAPE;
        $out = [];
        $thisYear = (int) now('Asia/Manila')->year;
        for ($ago = 1; $ago <= self::YEARS; $ago++) {
            $a = $from->copy()->subYears($ago)->startOfDay();
            $b = $a->copy()->addDays($days - 1);
            $sum = ['days' => 0, 'rain' => 0.0, 'wet10' => 0, 'rainMax' => null, 'hot35' => 0, 'gustMax' => null];
            for ($d = $a->copy(); $d->lte($b); $d->addDays(7)) {
                $r = $by[$d->year][intdiv($d->dayOfYear - 1, 7) + 1] ?? null;
                if (! $r) {
                    continue;
                }
                $sum['days'] += (int) $r->days;
                $sum['rain'] += (float) $r->rain;
                $sum['hot35'] += (int) $r->hot35;
                if ($ext) {
                    $sum['wet10'] += (int) $r->wet10;
                    $sum['rainMax'] = max((float) $sum['rainMax'], (float) $r->rainMax);
                    $sum['gustMax'] = $r->gustMax !== null ? max((float) $sum['gustMax'], (float) $r->gustMax) : $sum['gustMax'];
                }
            }
            if ($sum['days'] < $days * 0.85 || $b->year > $thisYear) {
                continue;
            }
            $k = $days / max(1, $sum['days']);
            $out[] = ['from' => $a->toDateString(), 'to' => $b->toDateString(), 'rain' => round($sum['rain'] * $k, 0),
                'wetDays' => $ext ? (int) round($sum['wet10'] * $k) : null, 'maxDayRain' => $ext ? round((float) $sum['rainMax'], 0) : null,
                'hotDays' => (int) round($sum['hot35'] * $k), 'maxGust' => $ext && $sum['gustMax'] !== null ? round($sum['gustMax'], 0) : null];
        }

        return $out;
    }

    /** The same days in earlier years, in a few lines for a prompt: the spread, the extremes, the last five years. */
    public static function sameDaysWords(array $years): string
    {
        if (! $years) {
            return 'not available';
        }
        $rain = array_column($years, 'rain');
        sort($rain);
        $med = $rain[intdiv(count($rain), 2)];
        $dry = collect($years)->sortBy('rain')->first();
        $wet = collect($years)->sortByDesc('rain')->first();
        $hot = round(array_sum(array_column($years, 'hotDays')) / count($years), 1);
        $line = 'The same ' . ((int) Carbon::parse($years[0]['from'])->diffInDays(Carbon::parse($years[0]['to'])) + 1) . ' days in each of the last ' . count($years) . ' years ('
            . substr(end($years)['from'], 0, 4) . ' to ' . substr($years[0]['from'], 0, 4) . '): rain median ' . $med . ' mm, driest ' . $dry['rain'] . ' mm (' . substr($dry['from'], 0, 4) . '), wettest '
            . $wet['rain'] . ' mm (' . substr($wet['from'], 0, 4) . '); days of 35 C or more, ' . $hot . ' on average';
        if (($years[0]['wetDays'] ?? null) !== null) {
            $wd = round(array_sum(array_column($years, 'wetDays')) / count($years), 1);
            $big = collect($years)->sortByDesc('maxDayRain')->first();
            $gust = collect($years)->filter(fn ($y) => ($y['maxGust'] ?? 0) >= self::STORM_GUST)->count();
            $line .= '; days of 10 mm or more, ' . $wd . ' on average; the wettest single day ' . $big['maxDayRain'] . ' mm (' . substr($big['from'], 0, 4) . '); '
                . $gust . ' of the years had storm gusts (' . self::STORM_GUST . ' km/h or more)';
        }
        $last = collect($years)->take(5)->map(fn ($y) => substr($y['from'], 0, 4) . ' ' . $y['rain'] . ' mm' . (($y['wetDays'] ?? null) !== null ? ', ' . $y['wetDays'] . ' days of 10 mm' : '')
            . ($y['hotDays'] ? ', ' . $y['hotDays'] . ' hot days' : '') . (($y['maxGust'] ?? null) ? ', gust ' . $y['maxGust'] . ' km/h' : ''))->implode('; ');

        return $line . '. The last five: ' . $last . '.';
    }

    /**
     * The field's weather record month by month over the last YEARS full
     * years, from the shelf (2026-10-18): for each month the median rain and
     * its driest and wettest years, the wettest single day, the years with a
     * day of 50 mm or more, the years with storm force gusts, the hot, cool
     * and dry days, and the rain of El Niño and La Niña months beside the
     * usual. Null when the place has no history. $whole brings an old cell
     * to the twenty years first (an analysis, not a page, should ask so).
     */
    public static function record(float $lat, float $lng, ?string $label = null, bool $whole = true, bool $fetch = true): ?array
    {
        if (! self::schema()->hasTable('as_weather_weeks')) {
            return null;
        }
        $key = self::cacheKey('rec:v2:' . self::cellKey($lat, $lng) . ':' . now('Asia/Manila')->format('Y-m'));
        $hit = Cache::get($key);
        if (is_array($hit) && (($hit['whole'] ?? false) || ! $whole)) {
            return $hit;
        }
        if ($fetch) {
            $cell = self::ready(self::cell($lat, $lng, $label), $whole);
        } else {
            // A request a farmer waits on reads only what is kept; a cell not filled yet is filled by the collector within the hour.
            $cell = self::cell($lat, $lng, $label);
            self::db()->table('as_weather_cells')->where('id', $cell->id)->update(['lastUsedAt' => now()]);
            if (! $cell->fetchedThrough) {
                return null;
            }
        }
        $by = self::weeks($cell);
        if (! $by) {
            return null;
        }
        $ext = self::extremes() && (int) ($cell->shape ?? 1) >= self::SHAPE;
        $last = (int) now('Asia/Manila')->year - 1;
        $first = $last - self::YEARS + 1;
        $oni = self::oniByMonth();
        // Each week to the month of its middle day.
        $m = [];
        for ($y = $first; $y <= $last; $y++) {
            foreach ($by[$y] ?? [] as $wn => $r) {
                $mid = Carbon::create($y, 1, 1)->addDays(($wn - 1) * 7 + 3);
                if ($mid->year !== $y) {
                    $mid = Carbon::create($y, 12, 31);
                }
                $mo = (int) $mid->month;
                $c = $m[$mo][$y] ?? ['days' => 0, 'rain' => 0.0, 'rainMax' => 0.0, 'wet50' => 0, 'gust60' => 0, 'gustMax' => 0.0, 'hot35' => 0, 'cool15' => 0, 'dry1' => 0];
                $c['days'] += (int) $r->days;
                $c['rain'] += (float) $r->rain;
                $c['hot35'] += (int) $r->hot35;
                $c['cool15'] += (int) $r->cool15;
                if ($ext) {
                    $c['rainMax'] = max($c['rainMax'], (float) $r->rainMax);
                    $c['wet50'] += (int) $r->wet50;
                    $c['gust60'] += (int) $r->gust60;
                    $c['gustMax'] = max($c['gustMax'], (float) $r->gustMax);
                    $c['dry1'] += (int) $r->dry1;
                }
                $m[$mo][$y] = $c;
            }
        }
        $months = [];
        $yearsSeen = [];
        for ($mo = 1; $mo <= 12; $mo++) {
            $rows = array_filter($m[$mo] ?? [], fn ($c) => $c['days'] >= 21);
            if (! $rows) {
                continue;
            }
            $dim = Carbon::create(2001, $mo, 1)->daysInMonth;
            $rain = [];
            foreach ($rows as $y => $c) {
                $rain[$y] = $c['rain'] * $dim / $c['days'];
                $yearsSeen[$y] = true;
            }
            $sorted = $rain;
            sort($sorted);
            $n = count($sorted);
            $median = $n % 2 ? $sorted[intdiv($n, 2)] : ($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2;
            $mean = array_sum($rain) / $n;
            $dryY = array_search(min($rain), $rain, true);
            $wetY = array_search(max($rain), $rain, true);
            $row = ['month' => $mo, 'years' => $n, 'rainMedian' => (int) round($median), 'rainMean' => (int) round($mean),
                'driest' => ['year' => $dryY, 'mm' => (int) round($rain[$dryY])], 'wettest' => ['year' => $wetY, 'mm' => (int) round($rain[$wetY])],
                'hotDays' => round(array_sum(array_column($rows, 'hot35')) / $n, 1), 'coolDays' => round(array_sum(array_column($rows, 'cool15')) / $n, 1)];
            if ($ext) {
                $bigY = null;
                $big = 0.0;
                foreach ($rows as $y => $c) {
                    if ($c['rainMax'] > $big) {
                        $big = $c['rainMax'];
                        $bigY = $y;
                    }
                }
                $row += ['wettestDay' => ['year' => $bigY, 'mm' => (int) round($big)],
                    'heavyYears' => count(array_filter($rows, fn ($c) => $c['wet50'] > 0)),
                    'gustYears' => array_keys(array_filter($rows, fn ($c) => $c['gustMax'] >= self::STORM_GUST)),
                    'dryDays' => round(array_sum(array_column($rows, 'dry1')) / $n, 1)];
            }
            // El Niño and La Niña months beside the usual (the ONI of the season centred on the month).
            if ($oni) {
                $nino = [];
                $nina = [];
                foreach ($rain as $y => $mm) {
                    $o = $oni[$y][$mo] ?? null;
                    if ($o === null) {
                        continue;
                    }
                    if ($o >= 0.5) {
                        $nino[] = $mm;
                    } elseif ($o <= -0.5) {
                        $nina[] = $mm;
                    }
                }
                $pct = fn (array $v) => $v && $mean > 0 ? (int) round(array_sum($v) / count($v) / $mean * 100) : null;
                $row['elNino'] = ['n' => count($nino), 'pct' => $pct($nino)];
                $row['laNina'] = ['n' => count($nina), 'pct' => $pct($nina)];
            }
            $months[$mo] = $row;
        }
        if (! $months) {
            return null;
        }
        ksort($yearsSeen);
        $span = array_key_first($yearsSeen) . ' to ' . array_key_last($yearsSeen);
        $out = ['span' => $span, 'years' => count($yearsSeen), 'cell' => $cell->cellKey, 'label' => $cell->label, 'months' => $months, 'extremes' => $ext,
            'enso' => (bool) $oni, 'whole' => $whole && self::lacks($cell) === null];
        Cache::put($key, $out, now()->addDays(3));

        return $out;
    }

    /**
     * The record in words for a prompt: a header and one line a month,
     * about 1,200 characters. $focus (month numbers) marks the months the
     * analysis is about.
     */
    public static function words(?array $rec, array $focus = [], bool $only = false): string
    {
        if (! $rec) {
            return '';
        }
        if ($only && $focus) {
            // The months in the crop's own order (a December planting reads Dec, Jan, Feb...).
            $ordered = [];
            foreach ($focus as $mo) {
                if (isset($rec['months'][$mo])) {
                    $ordered[$mo] = $rec['months'][$mo];
                }
            }
            $rec['months'] = $ordered;
            $focus = [];
        }
        $head = 'THE FIELD\'S WEATHER RECORD, ' . $rec['span'] . ' (' . $rec['years'] . ' years, the app\'s own stored copy of the ERA5 weather history from Open-Meteo for the 28 km square around the field: measured numbers, kept by the app, not searched)'
            . ".\nEach month: usual rain, median (driest year to wettest year)"
            . ($rec['extremes'] ? '; the wettest single day; the years with a day of 50 mm or more; the years with storm gusts of ' . self::STORM_GUST . ' km/h or more (reanalysis gusts run below a typhoon\'s peak: read them as a count of stormy years, not their strength)' : '')
            . '; days of 35 C or more, when any' . ($rec['enso'] ? '; rain in El Niño months and in La Niña months as a share of the usual' : '') . ".\n";
        $lines = [];
        foreach ($rec['months'] as $mo => $r) {
            $s = ($focus && in_array($mo, $focus, true) ? '* ' : '') . self::MONTHS[$mo] . ': ' . $r['rainMedian'] . ' mm (' . $r['driest']['mm'] . ' in ' . $r['driest']['year'] . ' to ' . $r['wettest']['mm'] . ' in ' . $r['wettest']['year'] . ')';
            if ($rec['extremes'] && isset($r['wettestDay'])) {
                $g = $r['gustYears'];
                $s .= '; wettest day ' . $r['wettestDay']['mm'] . ' mm (' . $r['wettestDay']['year'] . ')'
                    . ($r['heavyYears'] ? '; a 50 mm day in ' . $r['heavyYears'] . ' ' . ($r['heavyYears'] === 1 ? 'year' : 'years') : '')
                    . ($g ? '; storm gusts in ' . count($g) . ' ' . (count($g) === 1 ? 'year' : 'years') . ' (' . (count($g) > 4 ? 'last ' : '') . implode(', ', array_slice($g, -4)) . ')' : '');
            }
            $s .= $r['hotDays'] >= 0.1 ? '; ' . $r['hotDays'] . ' hot days' : '';
            $s .= $r['coolDays'] >= 0.1 ? '; ' . $r['coolDays'] . ' days at 15 C or below' : '';
            if (! empty($r['elNino']['pct']) || ! empty($r['laNina']['pct'])) {
                $s .= '; El Niño ' . ($r['elNino']['pct'] !== null ? $r['elNino']['pct'] . '%' : 'none') . ', La Niña ' . ($r['laNina']['pct'] !== null ? $r['laNina']['pct'] . '%' : 'none');
            }
            $lines[] = $s;
        }

        return $head . implode("\n", $lines) . ($focus ? "\n(* the months this analysis is about)" : '') . "\n";
    }

    /**
     * Where a place is, for the shelf: the pin when given, else the place
     * found by name (cached a month), else its province's cell. Null when
     * none is found.
     *
     * @return array{lat: float, lng: float, label: string}|null
     */
    public static function locate(string $place, ?float $lat = null, ?float $lng = null, bool $lookUp = true): ?array
    {
        $place = trim($place);
        if ($lat !== null && $lng !== null && ($lat != 0 || $lng != 0)) {
            return ['lat' => $lat, 'lng' => $lng, 'label' => $place];
        }
        if ($place === '') {
            return null;
        }
        $key = self::cacheKey('loc:' . md5(mb_strtolower($place)));
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit;
        }
        $found = null;
        try {
            $h = $lookUp ? (PlaceSearch::find($place)[0] ?? null) : null;
            if ($h) {
                $found = ['lat' => (float) $h['lat'], 'lng' => (float) $h['lng'], 'label' => $place];
            }
        } catch (\Throwable $e) {
            // The province's own cell is the fallback.
        }
        $searched = $found !== null;
        if (! $found && self::schema()->hasTable('as_weather_cells')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $place))));
            foreach (array_reverse($parts) as $p) {
                $c = self::db()->table('as_weather_cells')->whereRaw('LOWER(label) = ?', [mb_strtolower($p)])->first();
                if ($c) {
                    $found = ['lat' => (float) $c->lat, 'lng' => (float) $c->lng, 'label' => $place];
                    break;
                }
            }
        }
        // A place found on the map is kept a month; the province's cell stands in only until it is looked up.
        if ($found && ($searched || $lookUp)) {
            Cache::put($key, $found, now()->addDays($searched ? 30 : 1));
        }

        return $found;
    }

    /** The record of a place by name or pin, in words for a prompt (empty when the place has none). */
    /**
     * $fetch false (a prompt built while the farmer's request waits): no
     * lookup on the map and no archive read; what is kept, or nothing.
     */
    public static function wordsFor(string $place, ?float $lat = null, ?float $lng = null, array $focus = [], bool $only = false, bool $fetch = true): string
    {
        try {
            $at = self::locate($place, $lat, $lng, $fetch);

            return $at ? self::words(self::record($at['lat'], $at['lng'], null, $fetch, $fetch), $focus, $only) : '';
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    /** Cache keys carry the connection, so a rehearsal database never reads the live one's answers. */
    private static function cacheKey(string $k): string
    {
        return 'climate:' . self::conn() . ':' . $k;
    }
}
