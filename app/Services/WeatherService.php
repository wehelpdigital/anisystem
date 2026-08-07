<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 5-day weather forecast via Open-Meteo (https://open-meteo.com) — free, no API
 * key. A free-text farm location ("Town, Province") is geocoded to coordinates,
 * then the daily forecast is fetched. Geocoding and forecasts are cached so a
 * page load rarely touches the network and we stay well inside the free tier.
 */
class WeatherService
{
    private const GEOCODE_URL = 'https://geocoding-api.open-meteo.com/v1/search';
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';

    /** Full forecast for a free-text place, or null if it can't resolve. */
    public function forecastForPlace(?string $place, int $dayCount = 5): ?array
    {
        $place = trim((string) $place);
        if ($place === '') {
            return null;
        }

        $geo = $this->geocode($place);
        if (! $geo) {
            return null;
        }

        $days = $this->forecast($geo['lat'], $geo['lon'], $dayCount);
        if ($days === null) {
            return null;
        }

        return [
            'place' => $geo['label'],
            'lat' => $geo['lat'],
            'lon' => $geo['lon'],
            'days' => $days,
        ];
    }

    /**
     * Resolve a place name to coordinates. Cached 30 days on success; a genuine
     * "no such place" is cached briefly, while network errors aren't cached so
     * they retry next time.
     */
    public function geocode(string $place): ?array
    {
        $key = 'weather:geo:' . md5(Str::lower(trim($place)));
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        if ($cached === 'none') {
            return null;
        }

        // Open-Meteo matches a single token best, so query on the town (first
        // part) and use the province half only to disambiguate the results.
        $town = $this->cleanTown(trim((string) Str::of($place)->explode(',')->first()));
        $provinceHint = Str::lower(trim((string) Str::of($place)->explode(',')->last()));

        try {
            $res = Http::timeout(8)->retry(1, 200)->get(self::GEOCODE_URL, [
                'name' => $town,
                'count' => 5,
                'language' => 'en',
                'format' => 'json',
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $res->ok()) {
            return null;
        }

        $results = $res->json('results') ?: [];
        if (empty($results)) {
            Cache::put($key, 'none', now()->addHours(6));
            return null;
        }

        // Prefer a result whose admin region matches the province hint.
        $pick = $results[0];
        if ($provinceHint !== '' && $provinceHint !== Str::lower($town)) {
            foreach ($results as $r) {
                $hay = Str::lower(($r['admin1'] ?? '') . ' ' . ($r['admin2'] ?? '') . ' ' . ($r['admin3'] ?? ''));
                if ($hay !== '' && str_contains($hay, $provinceHint)) {
                    $pick = $r;
                    break;
                }
            }
        }

        $out = [
            'lat' => round((float) $pick['latitude'], 4),
            'lon' => round((float) $pick['longitude'], 4),
            'label' => collect([$pick['name'] ?? null, $pick['admin1'] ?? null])
                ->filter()->unique()->implode(', '),
        ];
        Cache::put($key, $out, now()->addDays(30));

        return $out;
    }

    /** Daily forecast for coordinates. Cached ~1h; failures aren't cached. */
    public function forecast(float $lat, float $lon, int $dayCount = 5): ?array
    {
        $dayCount = max(1, min(16, $dayCount));
        $key = sprintf('weather:fc:%.2f,%.2f:%d', $lat, $lon, $dayCount);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $res = Http::timeout(8)->retry(1, 200)->get(self::FORECAST_URL, [
                'latitude' => $lat,
                'longitude' => $lon,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'timezone' => 'auto',
                'forecast_days' => $dayCount,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $res->ok()) {
            return null;
        }

        $daily = $res->json('daily');
        if (! is_array($daily) || empty($daily['time'])) {
            return null;
        }

        $days = [];
        foreach ($daily['time'] as $i => $iso) {
            $code = (int) ($daily['weather_code'][$i] ?? 0);
            $meta = $this->codeMeta($code);
            $when = Carbon::parse($iso);
            $days[] = [
                'date' => $iso,
                'isToday' => $i === 0,
                'dow' => $i === 0 ? 'Today' : $when->isoFormat('ddd'),
                'label' => $when->isoFormat('MMM D'),
                'code' => $code,
                'text' => $meta['text'],
                'emoji' => $meta['emoji'],
                'max' => isset($daily['temperature_2m_max'][$i]) ? (int) round($daily['temperature_2m_max'][$i]) : null,
                'min' => isset($daily['temperature_2m_min'][$i]) ? (int) round($daily['temperature_2m_min'][$i]) : null,
                'pop' => isset($daily['precipitation_probability_max'][$i]) ? (int) $daily['precipitation_probability_max'][$i] : null,
            ];
        }

        Cache::put($key, $days, now()->addHour());

        return $days;
    }

    /**
     * Normalise a PSGC town name into something the geocoder resolves well:
     * drop "(Bitulok & Sabani)" parentheticals and unwrap "City Of Gapan" /
     * "Science City Of Muñoz" down to the place name itself.
     */
    private function cleanTown(string $town): string
    {
        $town = preg_replace('/\s*\(.*?\)\s*/', ' ', $town);
        if (preg_match('/\bcity of\s+(.+)$/i', $town, $m)) {
            $town = $m[1];
        }

        return trim(preg_replace('/\s+/', ' ', $town));
    }

    /** WMO weather-interpretation code → emoji + short label. */
    private function codeMeta(int $code): array
    {
        return match (true) {
            $code === 0 => ['emoji' => '☀️', 'text' => 'Clear'],
            $code === 1 => ['emoji' => '🌤️', 'text' => 'Mainly clear'],
            $code === 2 => ['emoji' => '⛅', 'text' => 'Partly cloudy'],
            $code === 3 => ['emoji' => '☁️', 'text' => 'Overcast'],
            in_array($code, [45, 48], true) => ['emoji' => '🌫️', 'text' => 'Fog'],
            in_array($code, [51, 53, 55, 56, 57], true) => ['emoji' => '🌦️', 'text' => 'Drizzle'],
            in_array($code, [61, 63, 65, 66, 67], true) => ['emoji' => '🌧️', 'text' => 'Rain'],
            in_array($code, [80, 81, 82], true) => ['emoji' => '🌦️', 'text' => 'Rain showers'],
            in_array($code, [71, 73, 75, 77, 85, 86], true) => ['emoji' => '🌨️', 'text' => 'Snow'],
            $code === 95 => ['emoji' => '⛈️', 'text' => 'Thunderstorm'],
            in_array($code, [96, 99], true) => ['emoji' => '⛈️', 'text' => 'Thunderstorm, hail'],
            default => ['emoji' => '🌡️', 'text' => 'Mixed'],
        };
    }
}
