<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The past days of a weather square, kept (2026-10-19, the owner: "history
 * on the other hand is not changeable"). A day that is over is read once
 * from Open-Meteo's archive and kept in as_weather_days, by the climate
 * shelf's 28 km square (ClimateStore::cellKey) and the date, and read from
 * there for good: the season report, the So Far report, Realign and the
 * field's last 30 days (FieldContext::weather) never ask for the same days
 * twice. Before, each read the archive itself, once a day, for the whole
 * season again.
 *
 * The climate shelf's own reads (ClimateStore::fill, the collector) write
 * the last KEEP_RECENT days of a square as well, from the answer they
 * already have, so the days a season asks for are usually kept before it
 * asks. The archive's newest days are early ones (the final reanalysis
 * replaces them within about three months); the collector's next read of
 * the square writes them again, and a day read FINAL_DAYS after it is marked
 * final. Nothing else reads a day twice.
 */
final class WeatherDays
{
    use Concerns\ShelfConnection;

    /** Ten variables: Open-Meteo counts up to ten as one call (per two weeks of days). */
    public const DAILY = 'precipitation_sum,temperature_2m_max,temperature_2m_min,temperature_2m_mean,wind_speed_10m_max,wind_gusts_10m_max,et0_fao_evapotranspiration,shortwave_radiation_sum,relative_humidity_2m_mean,weather_code';

    /** The days of a square the climate shelf's reads keep beside its weeks. */
    public const KEEP_RECENT = 400;

    /** A day read this many days after it is final. */
    public const FINAL_DAYS = 92;

    private const COLS = ['precipitation_sum' => 'rain', 'temperature_2m_max' => 'tmax', 'temperature_2m_min' => 'tmin', 'temperature_2m_mean' => 'tmean',
        'wind_speed_10m_max' => 'wind', 'wind_gusts_10m_max' => 'gust', 'et0_fao_evapotranspiration' => 'et0', 'shortwave_radiation_sum' => 'rad',
        'relative_humidity_2m_mean' => 'rh', 'weather_code' => 'code'];

    /** Archive reads made in this process (the harness counts them). */
    public static int $reads = 0;

    public static function ready(): bool
    {
        return (bool) Cache::remember('wxdays:' . self::conn() . ':has', 600, fn () => self::schema()->hasTable('as_weather_days'));
    }

    /**
     * The days of the square around a point from $from to $to (dates; $to
     * is held to yesterday), as date => [rain, tmax, tmin, tmean, wind,
     * gust, et0, rad, rh, code]. Days not kept are read from the archive
     * once, in as few reads as their gaps allow, and kept. A day the archive
     * does not have yet is left out. [] when nothing can be had.
     */
    public static function range(float $lat, float $lng, string $from, string $to): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to = min(Carbon::parse($to)->toDateString(), now('Asia/Manila')->subDay()->toDateString());
        if ($from > $to) {
            return [];
        }
        $cellKey = ClimateStore::cellKey($lat, $lng);
        [$clat, $clng] = array_map('floatval', explode(',', $cellKey));
        if (! self::ready()) {
            return self::read($clat, $clng, $from, $to);
        }
        try {
            $kept = self::rows($cellKey, $from, $to);
            $missing = [];
            for ($d = Carbon::parse($from); $d->toDateString() <= $to; $d->addDay()) {
                if (! isset($kept[$d->toDateString()])) {
                    $missing[] = $d->toDateString();
                }
            }
            foreach (self::runs($missing) as [$a, $b]) {
                $got = self::read($clat, $clng, $a, $b);
                if ($got) {
                    self::write($cellKey, $got);
                    $kept = array_merge($kept, $got);
                }
            }
            ksort($kept);

            return $kept;
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Only the days already kept for the square around a point, $from to $to:
     * nothing is read from the archive. For a reader that runs while a page
     * waits (the Coverage Planner's water check, 2026-10-30) and counts the
     * days it lacks from the climate shelf instead. [] when none are kept.
     */
    public static function kept(float $lat, float $lng, string $from, string $to): array
    {
        $to = min(Carbon::parse($to)->toDateString(), now('Asia/Manila')->subDay()->toDateString());
        $from = Carbon::parse($from)->toDateString();
        if ($from > $to || ! self::ready()) {
            return [];
        }
        try {
            return self::rows(ClimateStore::cellKey($lat, $lng), $from, $to);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Keep days the climate shelf already read (its archive answer, the
     * DAILY variables by name): the last KEEP_RECENT of them. Returns the
     * days written.
     */
    public static function keep(string $cellKey, array $daily): int
    {
        if (empty($daily['time']) || ! self::ready()) {
            return 0;
        }
        $since = now('Asia/Manila')->subDays(self::KEEP_RECENT)->toDateString();
        $days = self::shape($daily, $since);
        if ($days) {
            self::write($cellKey, $days);
        }

        return count($days);
    }

    /** Month by month for a prompt or a chart: rain, wet and dry days, hot days, windy days (wind of 40 km/h or more), mean top heat. */
    public static function months(array $days): array
    {
        $m = [];
        foreach ($days as $d => $v) {
            $ym = substr($d, 0, 7);
            $m[$ym] ??= ['ym' => $ym, 'rain' => 0.0, 'wet' => 0, 'dry' => 0, 'hot' => 0, 'windy' => 0, 'n' => 0, 'tmax' => 0.0];
            $r = (float) ($v['rain'] ?? 0);
            $m[$ym]['rain'] += $r;
            $m[$ym]['wet'] += $r >= 1 ? 1 : 0;
            $m[$ym]['dry'] += $r < 1 ? 1 : 0;
            $m[$ym]['hot'] += (float) ($v['tmax'] ?? 0) >= 35 ? 1 : 0;
            $m[$ym]['windy'] += (float) ($v['wind'] ?? 0) >= 40 ? 1 : 0;
            $m[$ym]['tmax'] += (float) ($v['tmax'] ?? 0);
            $m[$ym]['n']++;
        }
        foreach ($m as &$v) {
            $v['rain'] = round($v['rain'], 1);
            $v['tmax'] = $v['n'] ? round($v['tmax'] / $v['n'], 1) : null;
        }
        unset($v);

        return array_values($m);
    }

    /**
     * A season's sky in one line for a prompt (the season reports and
     * Realign): each month's rain beside the usual for those days (the
     * median of the field's twenty years, from the climate shelf, scaled to
     * the days the season had in the month), its wet, dry, hot and windy
     * days. '' when no day is kept or can be read.
     */
    public static function seasonWords(float $lat, float $lng, string $from, string $to, ?array $months = null): string
    {
        $months ??= self::months(self::range($lat, $lng, $from, $to));
        if (! $months) {
            return '';
        }
        $rec = null;
        try {
            $rec = ClimateStore::record($lat, $lng, null, false, false);
        } catch (\Throwable $e) {
            $rec = null;
        }
        $bits = [];
        foreach ($months as $v) {
            $usual = $rec['months'][(int) substr($v['ym'], 5, 2)]['rainMedian'] ?? null;
            $dim = Carbon::parse($v['ym'] . '-01')->daysInMonth;
            $bits[] = $v['ym'] . ': ' . round($v['rain']) . 'mm rain' . ($usual !== null && $v['n'] >= 7 ? ' (usual ' . round($usual * $v['n'] / $dim) . ')' : '')
                . ' over ' . $v['wet'] . ' wet days, ' . $v['dry'] . ' dry days' . ($v['hot'] ? ', ' . $v['hot'] . ' days ≥35°C' : '')
                . ($v['windy'] ? ', ' . $v['windy'] . ' windy days (≥40 km/h wind)' : '');
        }
        return 'THE SKY OVER THE SEASON (the field\'s daily weather as kept by the app, ' . $from . ' → ' . $to
            . ($rec ? '; usual = the median of its last ' . $rec['years'] . ' years for the same days' : '') . '): ' . implode(' | ', $bits) . '.';
    }

    /* ------------------------------------------------------------------ */

    private static function rows(string $cellKey, string $from, string $to): array
    {
        $out = [];
        foreach (self::db()->table('as_weather_days')->where('cellKey', $cellKey)->whereBetween('day', [$from, $to])->orderBy('day')->get() as $r) {
            $out[substr((string) $r->day, 0, 10)] = ['rain' => self::num($r->rain), 'tmax' => self::num($r->tmax), 'tmin' => self::num($r->tmin), 'tmean' => self::num($r->tmean),
                'wind' => self::num($r->wind), 'gust' => self::num($r->gust), 'et0' => self::num($r->et0), 'rad' => self::num($r->rad),
                'rh' => $r->rh !== null ? (int) $r->rh : null, 'code' => $r->code !== null ? (int) $r->code : null];
        }

        return $out;
    }

    /** Missing dates as [from, to] runs; runs less than two weeks apart are read as one. */
    private static function runs(array $dates): array
    {
        $runs = [];
        foreach ($dates as $d) {
            $last = $runs ? $runs[count($runs) - 1] : null;
            if ($last && Carbon::parse($last[1])->diffInDays(Carbon::parse($d)) <= 14) {
                $runs[count($runs) - 1][1] = $d;
            } else {
                $runs[] = [$d, $d];
            }
        }

        return $runs;
    }

    /** The archive's days for the square's middle, shaped; [] when it does not answer (nothing is kept then). */
    private static function read(float $lat, float $lng, string $from, string $to): array
    {
        try {
            self::$reads++;
            $r = Http::timeout(20)->retry(1, 1500, throw: false)->get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude' => $lat, 'longitude' => $lng, 'timezone' => 'Asia/Manila', 'start_date' => $from, 'end_date' => $to, 'daily' => self::DAILY,
            ]);
        } catch (\Throwable $e) {
            Log::warning('weather days: archive not read', ['at' => $lat . ',' . $lng, 'e' => $e->getMessage()]);

            return [];
        }

        return $r->ok() ? self::shape((array) $r->json('daily')) : [];
    }

    /** An archive answer as date => row, the days without rain or heat left out (the archive does not have them yet). */
    private static function shape(array $d, string $since = '0000-00-00'): array
    {
        $out = [];
        foreach ((array) ($d['time'] ?? []) as $i => $day) {
            if ($day < $since || ! is_numeric($d['precipitation_sum'][$i] ?? null) || ! is_numeric($d['temperature_2m_max'][$i] ?? null)) {
                continue;
            }
            $row = [];
            foreach (self::COLS as $var => $col) {
                $v = $d[$var][$i] ?? null;
                $row[$col] = is_numeric($v) ? (in_array($col, ['rh', 'code'], true) ? (int) round((float) $v) : round((float) $v, 1)) : null;
            }
            $out[$day] = $row;
        }

        return $out;
    }

    private static function write(string $cellKey, array $days): void
    {
        $now = now();
        $final = now('Asia/Manila')->subDays(self::FINAL_DAYS)->toDateString();
        $rows = [];
        foreach ($days as $day => $v) {
            $rows[] = ['cellKey' => $cellKey, 'day' => $day] + $v + ['final' => $day <= $final ? 1 : 0, 'keptAt' => $now];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            // A newer reading of a day replaces the older one (an early day turns final; a final one reads the same).
            self::db()->table('as_weather_days')->upsert($chunk, ['cellKey', 'day'], array_merge(array_values(self::COLS), ['final', 'keptAt']));
        }
    }

    private static function num($v): ?float
    {
        return $v !== null ? (float) $v : null;
    }
}
