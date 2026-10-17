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
    /**
     * The last 30 days and the next 16, day by day, through the app's own
     * stores (2026-10-19): the past days of the field's weather square, read
     * once and kept (WeatherDays), and today on from the place's field
     * reading, shared by every analysis there and kept three hours
     * (WeatherService::fieldReading). Before, one forecast call of its own
     * brought both, about 3.3 weighted calls a place every three hours. The
     * past days carry no rain chance (the archive has none).
     */
    public static function weather(float $lat, float $lng): array
    {
        $key = sprintf('fc:wx3:%.2f,%.2f', $lat, $lng);
        $hit = Cache::get($key);
        if (is_array($hit) && $hit) {
            return $hit;
        }
        $days = [];
        try {
            $past = WeatherDays::range($lat, $lng, now('Asia/Manila')->subDays(30)->toDateString(), now('Asia/Manila')->subDay()->toDateString());
        } catch (\Throwable $e) {
            report($e);
            $past = [];
        }
        foreach ($past as $t => $v) {
            $days[] = ['date' => $t, 'past' => true, 'tmax' => self::n($v['tmax'], 1), 'tmin' => self::n($v['tmin'], 1), 'rain' => self::n($v['rain'], 1), 'pop' => null,
                'wind' => self::n($v['wind'], 0), 'gust' => self::n($v['gust'], 0), 'et0' => self::n($v['et0'], 1), 'rh' => self::n($v['rh'], 0), 'code' => $v['code']];
        }
        $r = app(\App\Services\WeatherService::class)->fieldReading($lat, $lng);
        $d = (array) ($r['daily'] ?? []);
        $today = now('Asia/Manila')->toDateString();
        foreach ((array) ($d['time'] ?? []) as $i => $t) {
            if ($t < $today) {
                continue;
            }
            $days[] = [
                'date' => $t, 'past' => false,
                'tmax' => self::n($d['temperature_2m_max'][$i] ?? null, 1), 'tmin' => self::n($d['temperature_2m_min'][$i] ?? null, 1),
                'rain' => self::n($d['precipitation_sum'][$i] ?? null, 1), 'pop' => self::n($d['precipitation_probability_max'][$i] ?? null, 0),
                'wind' => self::n($d['wind_speed_10m_max'][$i] ?? null, 0), 'gust' => self::n($d['wind_gusts_10m_max'][$i] ?? null, 0),
                'et0' => null, 'rh' => null, 'code' => $d['weather_code'][$i] ?? null,
            ];
        }
        if (! $days) {
            return [];
        }
        $past = array_filter($days, fn ($x) => $x['past']);
        $next = array_values(array_filter($days, fn ($x) => ! $x['past']));
        $out = [
            'days' => $days,
            'past30Rain' => round(array_sum(array_column($past, 'rain')), 1),
            'past30Et0' => round(array_sum(array_column($past, 'et0')), 1),
            'next16Rain' => round(array_sum(array_column($next, 'rain')), 1),
            'next10Rain' => round(array_sum(array_column(array_slice($next, 0, 10), 'rain')), 1),
            'maxGustNext10' => max(array_merge([0], array_column(array_slice($next, 0, 10), 'gust'))),
            'hotDaysNext10' => count(array_filter(array_slice($next, 0, 10), fn ($x) => ($x['tmax'] ?? 0) >= 35)),
        ];
        // Only a whole answer is kept for the three hours; a part is asked for again next time.
        if ($past && $next) {
            Cache::put($key, $out, now()->addHours(Freshness::FORECAST_HOURS));
        }

        return $out;
    }

    /**
     * The same 60 days (this month and next) in each of the last twenty
     * years, from the climate shelf (App\Support\ClimateStore, 2026-10-18),
     * which keeps them: a place is read once. It read five years from the
     * archive before, five calls a place a month, and still did where the
     * shelf had nothing until 2026-10-19; the shelf's own fill is that read.
     */
    public static function climate(float $lat, float $lng): array
    {
        try {
            return ClimateStore::sameDays($lat, $lng, now('Asia/Manila')->startOfMonth(), 60);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
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
