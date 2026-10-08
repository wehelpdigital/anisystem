<?php

namespace App\Support;

use Illuminate\Support\Carbon;
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
 */
final class ClimateStore
{
    public const YEARS = 10;

    /* ------------------------------------------------------------- ENSO */

    /** The latest stored ENSO reading, or a first one fetched now if none is kept yet. */
    public static function enso(): ?array
    {
        if (! Schema::hasTable('as_enso_readings')) {
            return EnsoOutlook::state();
        }
        $r = DB::table('as_enso_readings')->orderByDesc('fetchedAt')->first();
        if (! $r) {
            return self::refreshEnso();
        }

        return ['oni' => (float) $r->oni, 'phase' => $r->phase, 'strength' => (string) $r->strength, 'label' => $r->label,
            'season' => $r->season, 'alert' => $r->alert, 'asOf' => Carbon::parse($r->fetchedAt)->toDateString()];
    }

    /** Read NOAA now and keep the reading (one row a day at most). */
    public static function refreshEnso(): ?array
    {
        try {
            $txt = Http::timeout(15)->get('https://www.cpc.ncep.noaa.gov/data/indices/oni.ascii.txt')->body();
        } catch (\Throwable $e) {
            Log::warning('climate: ONI not read', ['e' => $e->getMessage()]);

            return null;
        }
        $oni = null;
        $season = null;
        $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
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
        $a = abs($oni);
        $strength = $a >= 2 ? 'very strong' : ($a >= 1.5 ? 'strong' : ($a >= 1 ? 'moderate' : 'weak'));
        $phase = $oni >= 0.5 ? 'el_nino' : ($oni <= -0.5 ? 'la_nina' : 'neutral');
        $label = $phase === 'neutral' ? 'ENSO neutral' : ('A ' . $strength . ' ' . ($phase === 'el_nino' ? 'El Niño' : 'La Niña'));
        $alert = null;
        $synopsis = null;
        try {
            $html = Http::timeout(15)->get('https://www.cpc.ncep.noaa.gov/products/analysis_monitoring/enso_advisory/ensodisc.shtml')->body();
            $text = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_ireplace('&nbsp;', ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
            if (preg_match('/ENSO Alert System Status:\s*(.{3,60}?)\s*(?:Synopsis|$)/iu', $text, $m)) {
                $alert = trim($m[1]);
            }
            if (preg_match('/Synopsis:\s*(.{40,600})/iu', $text, $m)) {
                $dot = mb_strrpos($m[1], '. ');
                $synopsis = trim($dot !== false ? mb_substr($m[1], 0, $dot + 1) : $m[1]);
            }
        } catch (\Throwable $e) {
            // The advisory is a nicety; the ONI is the number.
        }
        $row = ['oni' => $oni, 'season' => $season, 'phase' => $phase, 'strength' => $phase === 'neutral' ? null : $strength, 'label' => $label,
            'alert' => $alert ? mb_substr($alert, 0, 80) : null, 'synopsis' => $synopsis, 'fetchedAt' => now(), 'updated_at' => now()];
        if (Schema::hasTable('as_enso_readings')) {
            $today = DB::table('as_enso_readings')->whereDate('fetchedAt', now()->toDateString())->first();
            $today ? DB::table('as_enso_readings')->where('id', $today->id)->update($row) : DB::table('as_enso_readings')->insert($row + ['created_at' => now()]);
        }

        return ['oni' => $oni, 'phase' => $phase, 'strength' => (string) $row['strength'], 'label' => $label, 'season' => $season, 'alert' => $row['alert'], 'asOf' => now()->toDateString()];
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
        $c = DB::table('as_weather_cells')->where('cellKey', $key)->first();
        if (! $c) {
            [$qlat, $qlng] = array_map('floatval', explode(',', $key));
            DB::table('as_weather_cells')->insertOrIgnore(['cellKey' => $key, 'lat' => $qlat, 'lng' => $qlng, 'label' => $label ? mb_substr($label, 0, 160) : null, 'created_at' => now(), 'updated_at' => now()]);
            $c = DB::table('as_weather_cells')->where('cellKey', $key)->first();
        }

        return $c;
    }

    /**
     * Fill a cell from the archive: everything since a year before what it
     * holds (the last weeks are re-summed as late days arrive), or eleven
     * years when it is new. Returns the weeks written.
     */
    public static function fill(object $cell): int
    {
        $start = $cell->fetchedThrough ? Carbon::parse($cell->fetchedThrough)->subYear()->startOfYear() : now('Asia/Manila')->subYears(self::YEARS + 1)->startOfYear();
        $end = now('Asia/Manila')->subDays(6)->startOfDay();
        try {
            $r = Http::timeout(40)->get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude' => (float) $cell->lat, 'longitude' => (float) $cell->lng, 'timezone' => 'Asia/Manila',
                'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
                'daily' => 'precipitation_sum,shortwave_radiation_sum,et0_fao_evapotranspiration,temperature_2m_max,temperature_2m_min,temperature_2m_mean',
            ]);
        } catch (\Throwable $e) {
            Log::warning('climate: archive not read', ['cell' => $cell->cellKey, 'e' => $e->getMessage()]);

            return 0;
        }
        $d = $r->ok() ? (array) $r->json('daily') : [];
        if (empty($d['time'])) {
            return 0;
        }
        $weeks = [];
        foreach ($d['time'] as $i => $day) {
            $t = Carbon::parse($day);
            $k = $t->year . '-' . (intdiv($t->dayOfYear - 1, 7) + 1);
            $w = $weeks[$k] ?? ['cellId' => $cell->id, 'year' => $t->year, 'week' => intdiv($t->dayOfYear - 1, 7) + 1, 'days' => 0, 'rain' => 0, 'rad' => 0, 'et0' => 0,
                'tmean' => 0, 'tmax' => 0, 'hot30' => 0, 'hot32' => 0, 'hot35' => 0, 'cool15' => 0];
            $tx = (float) ($d['temperature_2m_max'][$i] ?? 0);
            $w['days']++;
            $w['rain'] += (float) ($d['precipitation_sum'][$i] ?? 0);
            $w['rad'] += (float) ($d['shortwave_radiation_sum'][$i] ?? 0);
            $w['et0'] += (float) ($d['et0_fao_evapotranspiration'][$i] ?? 0);
            $w['tmean'] += (float) ($d['temperature_2m_mean'][$i] ?? 0);
            $w['tmax'] += $tx;
            $w['hot30'] += $tx >= 30 ? 1 : 0;
            $w['hot32'] += $tx >= 32 ? 1 : 0;
            $w['hot35'] += $tx >= 35 ? 1 : 0;
            $w['cool15'] += (float) ($d['temperature_2m_min'][$i] ?? 20) <= 15 ? 1 : 0;
            $weeks[$k] = $w;
        }
        $rows = array_map(fn ($w) => array_merge($w, ['rain' => round($w['rain'], 1), 'rad' => round($w['rad'], 1), 'et0' => round($w['et0'], 1),
            'tmean' => round($w['tmean'], 1), 'tmax' => round($w['tmax'], 1)]), array_values($weeks));
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('as_weather_weeks')->upsert($chunk, ['cellId', 'year', 'week'], ['days', 'rain', 'rad', 'et0', 'tmean', 'tmax', 'hot30', 'hot32', 'hot35', 'cool15']);
        }
        DB::table('as_weather_cells')->where('id', $cell->id)->update(['fetchedThrough' => end($d['time']), 'updated_at' => now()]);

        return count($rows);
    }

    /**
     * The same weeks in each of the last ten years: rain, sun, the water
     * the air pulls from a crop, heat. From the shelf; a new cell is filled
     * once first. Null when there is no history to be had.
     */
    public static function season(float $lat, float $lng, Carbon $from, int $days, ?string $label = null): ?array
    {
        if (! Schema::hasTable('as_weather_weeks')) {
            return null;
        }
        $cell = self::cell($lat, $lng, $label);
        if (! $cell->fetchedThrough) {
            self::fill($cell);
            $cell = DB::table('as_weather_cells')->where('id', $cell->id)->first();
        }
        DB::table('as_weather_cells')->where('id', $cell->id)->update(['lastUsedAt' => now()]);
        $rows = DB::table('as_weather_weeks')->where('cellId', $cell->id)->get();
        if ($rows->isEmpty()) {
            return null;
        }
        $by = [];
        foreach ($rows as $r) {
            $by[$r->year][$r->week] = $r;
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

        return ['years' => $years, 'avg' => $avg, 'cell' => $cell->cellKey, 'through' => $cell->fetchedThrough];
    }
}
