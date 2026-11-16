<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The temperatures the Heat Map Forecast paints (2026-11-17, the owner: "a heat map forecast and shows your lot
 * location ... colors based on temperature ... a play button in the map showing the next days temperature").
 *
 * A square of 9 by 9 points around the lot, 0.04 degrees apart (about 4.4 km, so the square is about 35 km across),
 * asked of the Open-Meteo model for every hour of a week's temperature, and the lot's own spot asked at the same time
 * for what the work needs: the temperature, what it feels like, the humidity, the rain and its chance, the wind and
 * the cloud by the hour, and each day's sky, rain chance and sun strength (App\Support\FieldHours reads them).
 * The model corrects its temperature for the height of the ground, so hills and valleys show; on flat land the
 * square is nearly one color, which is the truth.
 *
 * Kept per lot for the 3 hour block it was asked in (the house rule for forecasts, Freshness::FORECAST_HOURS),
 * under a lock, so a farm opening the page twice makes one call. The last good answer is kept two days more, so a
 * down weather service shows the last forecast with its time instead of nothing.
 *
 * Google's Weather API answers one point a call and 24 hours a page: this square would be about 800 calls a
 * refresh, and the app's Google key opens the map only. Google draws the map; the model gives the numbers.
 */
final class HeatForecast
{
    /** Points on a side of the square. Odd, so the lot sits on the middle one. */
    public const SIZE = 9;

    /** Degrees between two points. */
    public const STEP = 0.04;

    /** Days ahead, today first. */
    public const DAYS = 7;

    /** The map's hour by hour plays every third hour. */
    public const FRAME_HOURS = 3;

    private const URL = 'https://api.open-meteo.com/v1/forecast';

    private const TZ = 'Asia/Manila';

    /** Bump to drop every kept square at once (a change of shape). */
    private const VER = 'v3';

    /**
     * The week around a point, or null when the model cannot be reached and nothing is kept.
     *
     * @return array{grid: array, frames: list<array{at: string, t: list<float>}>, days: list<array>, lot: array, tz: string, fetchedAt: string, stale?: bool}|null
     */
    public static function around(float $lat, float $lng): ?array
    {
        $key = sprintf('%.3f,%.3f', $lat, $lng);
        $now = now(self::TZ);
        $block = $now->format('Ymd') . '-' . intdiv((int) $now->format('G'), Freshness::FORECAST_HOURS);
        $ck = 'heat:' . self::VER . ':' . $key . ':' . $block;
        $got = rescue(fn () => Cache::get($ck), null, false);
        if (is_array($got)) {
            return $got;
        }
        $got = rescue(fn () => Cache::lock('lock:' . $ck, 40)->block(30, function () use ($ck, $lat, $lng, $key) {
            $have = Cache::get($ck);

            return is_array($have) ? $have : self::fetch($lat, $lng, $ck, $key);
        }), null, false);
        if (is_array($got)) {
            return $got;
        }
        $last = rescue(fn () => Cache::get('heat:last:' . self::VER . ':' . $key), null, false);

        return is_array($last) ? $last + ['stale' => true] : null;
    }

    /** Ask the model, shape the answer, and keep it. */
    private static function fetch(float $lat, float $lng, string $ck, string $key): ?array
    {
        $half = (self::SIZE - 1) / 2;
        $lat0 = round($lat - $half * self::STEP, 4);
        $lng0 = round($lng - $half * self::STEP, 4);
        $lats = [];
        $lngs = [];
        for ($r = 0; $r < self::SIZE; $r++) {
            for ($c = 0; $c < self::SIZE; $c++) {
                $lats[] = round($lat0 + $r * self::STEP, 4);
                $lngs[] = round($lng0 + $c * self::STEP, 4);
            }
        }
        $n = self::SIZE * self::SIZE;

        // Two asks at once (2026-11-17, the work guide): the square's temperature only, and the lot's own week with
        // what the work needs, the rain, its chance, the wind and the cloud. 82 places either way, each under the
        // ten variables Open-Meteo weighs as one call.
        try {
            [$gr, $lr] = Http::pool(fn ($pool) => [
                $pool->timeout(25)->get(self::URL, [
                    'latitude' => implode(',', $lats), 'longitude' => implode(',', $lngs),
                    'hourly' => 'temperature_2m', 'forecast_days' => self::DAYS, 'timezone' => self::TZ,
                ]),
                $pool->timeout(25)->get(self::URL, [
                    'latitude' => round($lat, 5), 'longitude' => round($lng, 5),
                    'hourly' => 'temperature_2m,apparent_temperature,relative_humidity_2m,precipitation_probability,precipitation,wind_speed_10m,cloud_cover',
                    'daily' => 'weather_code,precipitation_probability_max,uv_index_max',
                    'forecast_days' => self::DAYS, 'timezone' => self::TZ,
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::info('Heat map forecast could not be fetched: ' . $e->getMessage());

            return null;
        }
        $grid = $gr instanceof \Illuminate\Http\Client\Response && $gr->ok() ? $gr->json() : null;
        $me = $lr instanceof \Illuminate\Http\Client\Response && $lr->ok() ? $lr->json() : null;
        if (! is_array($grid) || ! array_is_list($grid) || count($grid) !== $n || ! isset($grid[0]['hourly']['time'])
            || ! is_array($me) || ! isset($me['hourly']['time'], $me['daily']['time'])) {
            Log::info('Heat map forecast came back unusable: HTTP ' . ($gr instanceof \Illuminate\Http\Client\Response ? $gr->status() : 'none') . '/' . ($lr instanceof \Illuminate\Http\Client\Response ? $lr->status() : 'none'));

            return null;
        }
        $all = $grid;

        $times = array_values((array) $all[0]['hourly']['time']);
        $num = fn ($v, int $dp = 1) => is_numeric($v) ? round((float) $v, $dp) : null;

        // The map's frames: every third hour, from the block the hour is in (12 AM, 3 AM, ... 9 PM).
        $nowHour = now(self::TZ)->startOfHour();
        $startAt = $nowHour->copy()->setTime(intdiv($nowHour->hour, self::FRAME_HOURS) * self::FRAME_HOURS, 0)->format('Y-m-d\TH:i');
        $from = array_search($startAt, $times, true);
        $from = $from === false ? 0 : (int) $from;
        $frames = [];
        for ($k = $from; $k < count($times); $k += self::FRAME_HOURS) {
            $t = [];
            for ($i = 0; $i < $n; $i++) {
                $t[] = $num($all[$i]['hourly']['temperature_2m'][$k] ?? null);
            }
            $frames[] = ['at' => $times[$k], 't' => $t];
        }

        // Each day's high and low at every point, read off the same hours the map plays and the chart draws, so the
        // badge, the colors and the chart never disagree by the half degree the model's own daily figures can.
        $dates = array_values((array) $me['daily']['time']);
        $hoursOf = [];
        foreach ($times as $k => $t) {
            $hoursOf[substr((string) $t, 0, 10)][] = $k;
        }
        $span = function (array $series, string $date, bool $high) use ($hoursOf, $num) {
            $vals = [];
            foreach ($hoursOf[$date] ?? [] as $k) {
                if (isset($series[$k]) && is_numeric($series[$k])) {
                    $vals[] = (float) $series[$k];
                }
            }

            return $vals ? $num($high ? max($vals) : min($vals)) : null;
        };
        $days = [];
        foreach ($dates as $d => $date) {
            $max = [];
            $min = [];
            for ($i = 0; $i < $n; $i++) {
                $max[] = $span((array) ($all[$i]['hourly']['temperature_2m'] ?? []), $date, true);
                $min[] = $span((array) ($all[$i]['hourly']['temperature_2m'] ?? []), $date, false);
            }
            $days[] = ['date' => $date, 'max' => $max, 'min' => $min];
        }

        // The lot's own week: every hour (its hours line up with the square's), and its days.
        $h = (array) ($me['hourly'] ?? []);
        $int = fn ($v) => is_numeric($v) ? (int) round((float) $v) : null;
        $lotHours = ['from' => $times[0] ?? null, 't' => [], 'f' => [], 'h' => [], 'pp' => [], 'p' => [], 'w' => [], 'c' => []];
        foreach ($times as $k => $_) {
            $lotHours['t'][] = $num($h['temperature_2m'][$k] ?? null);
            $lotHours['f'][] = $num($h['apparent_temperature'][$k] ?? null);
            $lotHours['h'][] = $int($h['relative_humidity_2m'][$k] ?? null);
            $lotHours['pp'][] = $int($h['precipitation_probability'][$k] ?? null);
            $lotHours['p'][] = $num($h['precipitation'][$k] ?? null);
            $lotHours['w'][] = $int($h['wind_speed_10m'][$k] ?? null);
            $lotHours['c'][] = $int($h['cloud_cover'][$k] ?? null);
        }
        $dd = (array) ($me['daily'] ?? []);
        $lotDays = [];
        foreach ($dates as $d => $date) {
            $rain = 0.0;
            foreach ($hoursOf[$date] ?? [] as $k) {
                $rain += is_numeric($h['precipitation'][$k] ?? null) ? (float) $h['precipitation'][$k] : 0.0;
            }
            $lotDays[] = [
                'date' => $date,
                'code' => $int($dd['weather_code'][$d] ?? null),
                'max' => $span((array) ($h['temperature_2m'] ?? []), $date, true),
                'min' => $span((array) ($h['temperature_2m'] ?? []), $date, false),
                'fmax' => $span((array) ($h['apparent_temperature'] ?? []), $date, true),
                'pop' => $int($dd['precipitation_probability_max'][$d] ?? null),
                'uv' => $num($dd['uv_index_max'][$d] ?? null),
                'rain' => round($rain, 1),
            ];
        }

        $out = [
            'grid' => ['lat0' => $lat0, 'lng0' => $lng0, 'step' => self::STEP, 'size' => self::SIZE],
            'frames' => $frames,
            'days' => $days,
            'lot' => ['hours' => $lotHours, 'days' => $lotDays, 'elev' => isset($me['elevation']) && is_numeric($me['elevation']) ? (int) round($me['elevation']) : null],
            'tz' => self::TZ,
            'fetchedAt' => now()->toIso8601String(),
        ];
        rescue(fn () => Cache::put($ck, $out, now()->addHours(Freshness::FORECAST_HOURS)->addMinutes(15)), null, false);
        rescue(fn () => Cache::put('heat:last:' . self::VER . ':' . $key, $out, now()->addDays(2)), null, false);

        return $out;
    }

    /** Where a date's midnight sits in the lot's hours (the index of its 12 AM), or null. */
    public static function dayStart(array $lot, string $date): ?int
    {
        $from = $lot['hours']['from'] ?? null;

        return $from ? (int) Carbon::parse($from, self::TZ)->diffInHours(Carbon::parse($date . ' 00:00', self::TZ), false) : null;
    }

    /** The hours of one day of the lot's week, as [hour => [t, f, h]]. */
    public static function dayHours(array $lot, string $date): array
    {
        $hours = (array) ($lot['hours'] ?? []);
        $start = self::dayStart($lot, $date);
        if ($start === null) {
            return [];
        }
        $out = [];
        for ($i = 0; $i < 24; $i++) {
            $k = $start + $i;
            if ($k < 0 || ! array_key_exists($k, $hours['t'] ?? [])) {
                continue;
            }
            $out[$i] = [$hours['t'][$k] ?? null, $hours['f'][$k] ?? null, $hours['h'][$k] ?? null];
        }

        return $out;
    }
}
