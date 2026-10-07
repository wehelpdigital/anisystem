<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * What a field's place says beyond the picture from space: the weather of
 * the last month and the next two weeks, the same weeks in earlier years,
 * the soil the global soil map gives for that spot, and the elevation.
 * Free public sources (Open-Meteo, ISRIC SoilGrids), each cached, each
 * failing quietly to an empty answer so an analysis never breaks on them.
 */
final class FieldContext
{
    /** The last 30 days and the next 16, day by day. */
    public static function weather(float $lat, float $lng): array
    {
        $key = sprintf('fc:wx:%.3f,%.3f', $lat, $lng);

        return Cache::remember($key, 3600, function () use ($lat, $lng) {
            try {
                $res = Http::timeout(15)->retry(1, 300)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $lat, 'longitude' => $lng, 'timezone' => 'Asia/Manila',
                    'past_days' => 30, 'forecast_days' => 16,
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,wind_gusts_10m_max,et0_fao_evapotranspiration,relative_humidity_2m_mean',
                ]);
            } catch (\Throwable $e) {
                return [];
            }
            $d = $res->ok() ? (array) $res->json('daily') : [];
            if (empty($d['time'])) {
                return [];
            }
            $today = now('Asia/Manila')->toDateString();
            $days = [];
            foreach ($d['time'] as $i => $t) {
                $days[] = [
                    'date' => $t, 'past' => $t < $today,
                    'tmax' => self::n($d['temperature_2m_max'][$i] ?? null, 1), 'tmin' => self::n($d['temperature_2m_min'][$i] ?? null, 1),
                    'rain' => self::n($d['precipitation_sum'][$i] ?? null, 1), 'pop' => self::n($d['precipitation_probability_max'][$i] ?? null, 0),
                    'wind' => self::n($d['wind_speed_10m_max'][$i] ?? null, 0), 'gust' => self::n($d['wind_gusts_10m_max'][$i] ?? null, 0),
                    'et0' => self::n($d['et0_fao_evapotranspiration'][$i] ?? null, 1), 'rh' => self::n($d['relative_humidity_2m_mean'][$i] ?? null, 0),
                    'code' => $d['weather_code'][$i] ?? null,
                ];
            }
            $past = array_filter($days, fn ($x) => $x['past']);
            $next = array_values(array_filter($days, fn ($x) => ! $x['past']));

            return [
                'days' => $days,
                'past30Rain' => round(array_sum(array_column($past, 'rain')), 1),
                'past30Et0' => round(array_sum(array_column($past, 'et0')), 1),
                'next16Rain' => round(array_sum(array_column($next, 'rain')), 1),
                'next10Rain' => round(array_sum(array_column(array_slice($next, 0, 10), 'rain')), 1),
                'maxGustNext10' => max(array_merge([0], array_column(array_slice($next, 0, 10), 'gust'))),
                'hotDaysNext10' => count(array_filter(array_slice($next, 0, 10), fn ($x) => ($x['tmax'] ?? 0) >= 35)),
            ];
        });
    }

    /** The same 60 days (this month and next) in each of the last five years. */
    public static function climate(float $lat, float $lng): array
    {
        $key = sprintf('fc:clim:%.2f,%.2f:%s', $lat, $lng, now('Asia/Manila')->format('Y-m'));

        return Cache::remember($key, 86400 * 20, function () use ($lat, $lng) {
            $years = [];
            $start = now('Asia/Manila')->startOfMonth();
            for ($y = 1; $y <= 5; $y++) {
                $from = $start->copy()->subYears($y);
                $to = $from->copy()->addDays(59);
                try {
                    $res = Http::timeout(15)->get('https://archive-api.open-meteo.com/v1/archive', [
                        'latitude' => $lat, 'longitude' => $lng, 'timezone' => 'Asia/Manila',
                        'start_date' => $from->toDateString(), 'end_date' => $to->toDateString(),
                        'daily' => 'precipitation_sum,temperature_2m_max,wind_gusts_10m_max',
                    ]);
                } catch (\Throwable $e) {
                    continue;
                }
                $d = $res->ok() ? (array) $res->json('daily') : [];
                if (empty($d['time'])) {
                    continue;
                }
                $rain = array_filter((array) ($d['precipitation_sum'] ?? []), 'is_numeric');
                $tmax = array_filter((array) ($d['temperature_2m_max'] ?? []), 'is_numeric');
                $gust = array_filter((array) ($d['wind_gusts_10m_max'] ?? []), 'is_numeric');
                $years[] = [
                    'from' => $from->toDateString(), 'to' => $to->toDateString(),
                    'rain' => round(array_sum($rain), 0),
                    'wetDays' => count(array_filter($rain, fn ($v) => $v >= 10)),
                    'maxDayRain' => $rain ? round(max($rain), 0) : null,
                    'hotDays' => count(array_filter($tmax, fn ($v) => $v >= 35)),
                    'maxGust' => $gust ? round(max($gust), 0) : null,
                ];
            }

            return $years;
        });
    }

    /**
     * The soil at the spot from ISRIC SoilGrids (250 m, modelled, not a
     * test of this field): pH, clay, sand, organic carbon, CEC, nitrogen,
     * in the top 30 cm.
     */
    public static function soil(float $lat, float $lng): array
    {
        $key = sprintf('fc:soil:%.3f,%.3f', $lat, $lng);

        return Cache::remember($key, 86400 * 90, function () use ($lat, $lng) {
            try {
                $q = http_build_query(['lon' => $lng, 'lat' => $lat, 'value' => 'mean'])
                    . '&property=phh2o&property=clay&property=sand&property=silt&property=soc&property=cec&property=nitrogen&property=bdod'
                    . '&depth=0-5cm&depth=5-15cm&depth=15-30cm';
                $res = Http::timeout(20)->acceptJson()->get('https://rest.isric.org/soilgrids/v2.0/properties/query?' . $q);
            } catch (\Throwable $e) {
                return [];
            }
            if (! $res->ok()) {
                return [];
            }
            $out = [];
            foreach ((array) $res->json('properties.layers') as $layer) {
                $name = $layer['name'] ?? '';
                $div = (float) ($layer['unit_measure']['d_factor'] ?? 1) ?: 1;
                $unit = (string) ($layer['unit_measure']['target_units'] ?? '');
                $vals = [];
                foreach ((array) ($layer['depths'] ?? []) as $dep) {
                    $m = $dep['values']['mean'] ?? null;
                    if ($m !== null) {
                        $vals[$dep['label'] ?? ''] = round($m / $div, 2);
                    }
                }
                if ($vals) {
                    $out[$name] = ['unit' => $unit, 'byDepth' => $vals, 'top30' => round(array_sum($vals) / count($vals), 2)];
                }
            }

            return $out;
        });
    }

    public static function elevation(float $lat, float $lng): ?float
    {
        return Cache::remember(sprintf('fc:elev:%.3f,%.3f', $lat, $lng), 86400 * 365, function () use ($lat, $lng) {
            try {
                $v = Http::timeout(8)->get('https://api.open-meteo.com/v1/elevation', ['latitude' => $lat, 'longitude' => $lng])->json('elevation.0');

                return is_numeric($v) ? round((float) $v, 0) : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    private static function n($v, int $dp): ?float
    {
        return is_numeric($v) ? round((float) $v, $dp) : null;
    }
}
