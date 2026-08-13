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
     * Hour-by-hour for the next stretch, starting at the current hour in the
     * location's own timezone — a farmer planning a spray wants "is it raining
     * at 2pm", which a daily maximum cannot answer. Cached half an hour: the
     * upstream data only refreshes hourly, and this rides the same free tier.
     */
    public function hourly(float $lat, float $lon, int $hours = 24): ?array
    {
        $hours = max(6, min(48, $hours));
        $key = sprintf('weather:hr:%.2f,%.2f:%d', $lat, $lon, $hours);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $res = Http::timeout(8)->retry(1, 200)->get(self::FORECAST_URL, [
                'latitude' => $lat,
                'longitude' => $lon,
                'hourly' => 'weather_code,temperature_2m,precipitation_probability,precipitation,relative_humidity_2m,wind_speed_10m',
                'timezone' => 'auto',
                // Two calendar days always covers the next 24 hours, whatever
                // the time of day this is asked.
                'forecast_days' => 3,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $res->ok()) {
            return null;
        }

        $h = $res->json('hourly');
        if (! is_array($h) || empty($h['time'])) {
            return null;
        }

        // Open-Meteo returns whole days in local time; start at the hour we are
        // actually in, so the first card is "now" rather than midnight.
        $tz = $res->json('timezone') ?: 'UTC';
        $now = Carbon::now($tz);
        $out = [];
        foreach ($h['time'] as $i => $iso) {
            $when = Carbon::parse($iso, $tz);
            if ($when->lt($now->copy()->startOfHour()) || count($out) >= $hours) {
                continue;
            }
            $meta = $this->codeMeta((int) ($h['weather_code'][$i] ?? 0));
            $out[] = [
                'time' => $iso,
                'hour' => $when->isoFormat('h A'),
                'dow' => $when->isoFormat('ddd'),
                'isNow' => empty($out),
                'newDay' => $when->hour === 0,
                'text' => $meta['text'],
                'emoji' => $meta['emoji'],
                'temp' => isset($h['temperature_2m'][$i]) ? (int) round($h['temperature_2m'][$i]) : null,
                'pop' => isset($h['precipitation_probability'][$i]) ? (int) $h['precipitation_probability'][$i] : null,
                'mm' => isset($h['precipitation'][$i]) ? round((float) $h['precipitation'][$i], 1) : null,
                'humidity' => isset($h['relative_humidity_2m'][$i]) ? (int) $h['relative_humidity_2m'][$i] : null,
                'wind' => isset($h['wind_speed_10m'][$i]) ? (int) round($h['wind_speed_10m'][$i]) : null,
            ];
        }

        if (empty($out)) {
            return null;
        }

        Cache::put($key, $out, now()->addMinutes(30));

        return $out;
    }

    /** Hour-by-hour for a free-text place, or null if it cannot resolve. */
    /**
     * The next few days of hours, grouped by the day they belong to.
     *
     * The hourly rail used to be its own tab showing the next 24 hours, which
     * answered "what is this afternoon like" and nothing else. A grower
     * planning a spray for Thursday wants Thursday's hours, so the days are
     * the way in and this is what they open.
     *
     * @return array<string, list<array<string, mixed>>>|null  date => hours
     */
    public function hourlyByDay(float $lat, float $lon, int $days = 6): ?array
    {
        $days = max(1, min(7, $days));
        $key = sprintf('weather:hrday:%.2f,%.2f:%d', $lat, $lon, $days);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $res = Http::timeout(8)->retry(1, 200)->get(self::FORECAST_URL, [
                'latitude' => $lat,
                'longitude' => $lon,
                'hourly' => 'weather_code,temperature_2m,precipitation_probability,precipitation,relative_humidity_2m,wind_speed_10m',
                'timezone' => 'auto',
                'forecast_days' => $days,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $res->ok()) {
            return null;
        }

        $h = $res->json('hourly');
        if (! is_array($h) || empty($h['time'])) {
            return null;
        }

        $tz = $res->json('timezone') ?: 'UTC';
        $now = Carbon::now($tz);
        $out = [];
        foreach ($h['time'] as $i => $iso) {
            $when = Carbon::parse($iso, $tz);
            $date = $when->toDateString();
            // Today starts at the hour we are in; every other day is whole.
            if ($when->isSameDay($now) && $when->lt($now->copy()->startOfHour())) {
                continue;
            }
            $meta = $this->codeMeta((int) ($h['weather_code'][$i] ?? 0));
            $out[$date][] = [
                'time' => $iso,
                'hour' => $when->isoFormat('h A'),
                'isNow' => $when->isSameDay($now) && $when->hour === $now->hour,
                'text' => $meta['text'],
                'emoji' => $meta['emoji'],
                'temp' => isset($h['temperature_2m'][$i]) ? (int) round($h['temperature_2m'][$i]) : null,
                'pop' => isset($h['precipitation_probability'][$i]) ? (int) $h['precipitation_probability'][$i] : null,
                'mm' => isset($h['precipitation'][$i]) ? round((float) $h['precipitation'][$i], 1) : null,
                'humidity' => isset($h['relative_humidity_2m'][$i]) ? (int) $h['relative_humidity_2m'][$i] : null,
                'wind' => isset($h['wind_speed_10m'][$i]) ? (int) round($h['wind_speed_10m'][$i]) : null,
            ];
        }

        if (empty($out)) {
            return null;
        }

        Cache::put($key, $out, now()->addMinutes(30));

        return $out;
    }

    public function hourlyForPlace(?string $place, int $hours = 24): ?array
    {
        $geo = $this->geocode(trim((string) $place));

        return $geo ? $this->hourly($geo['lat'], $geo['lon'], $hours) : null;
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
