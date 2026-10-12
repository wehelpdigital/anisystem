<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Weather forecasts via Open-Meteo (https://open-meteo.com). A free-text farm
 * location ("Town, Province") is geocoded to coordinates, then the forecast
 * is read.
 *
 * ONE READING PER PLACE, SHARED (2026-10-12, the owner). A place's forecast
 * is asked of Open-Meteo only when someone opens it and the reading kept for
 * that place (as_weather_forecasts) is three hours old or more; everyone
 * else at that place, the same barangay or town, reads the kept one. One call
 * brings the 16 days, a week of hours and the sky now, so the daily cards,
 * the hourly rail and the greeting cost one request between them, not three.
 * The model is Open-Meteo's best match, the highest resolution one it has for
 * the coordinates. The geocoder resolves to the town, so the barangays of a
 * town share its reading: forecast models are 9 to 13 km grids, finer than
 * any of them can tell apart.
 */
class WeatherService
{
    private const GEOCODE_URL = 'https://geocoding-api.open-meteo.com/v1/search';
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';

    /** How long a kept reading serves everyone at its place. */
    public const FRESH_HOURS = 3;

    private const HOURLY = 'weather_code,temperature_2m,apparent_temperature,precipitation_probability,precipitation,rain,relative_humidity_2m,wind_speed_10m,is_day';

    private const DAILY = 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max';

    /** Readings already looked up in this request. */
    private array $memo = [];

    /**
     * The kept reading for coordinates, or a new one when it is three hours
     * old or there is none. A failed ask serves the old reading if there is
     * one (stale beats nothing in the field); with neither, null.
     *
     * @return array{tz:string,hourly:array,daily:array,fetchedAt:string}|null
     */
    public function reading(float $lat, float $lon): ?array
    {
        $key = sprintf('%.2f,%.2f', $lat, $lon);
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }
        $row = null;
        try {
            $row = DB::table('as_weather_forecasts')->where('placeKey', $key)->first();
        } catch (\Throwable $e) {
            $row = null;   // no table yet: the cache below stands in
        }
        $kept = $row ? json_decode((string) $row->payload, true) : Cache::get('weather:reading:' . $key);
        $keptAt = $row ? Carbon::parse($row->fetchedAt) : (is_array($kept) ? Carbon::parse($kept['fetchedAt'] ?? 'now') : null);
        if (is_array($kept) && $keptAt && $keptAt->gt(now()->subHours(self::FRESH_HOURS))) {
            return $this->memo[$key] = $kept + ['fetchedAt' => $keptAt->toIso8601String()];
        }

        $fresh = null;
        try {
            $res = Http::timeout(10)->retry(1, 250)->get(self::FORECAST_URL, [
                'latitude' => $lat,
                'longitude' => $lon,
                'models' => 'best_match',
                'hourly' => self::HOURLY,
                'daily' => self::DAILY,
                'timezone' => 'auto',
                'forecast_days' => 16,
                'forecast_hours' => 168,
            ]);
            if ($res->ok() && is_array($res->json('daily')) && ! empty($res->json('daily.time')) && ! empty($res->json('hourly.time'))) {
                $fresh = ['tz' => (string) ($res->json('timezone') ?: 'UTC'), 'hourly' => $res->json('hourly'), 'daily' => $res->json('daily')];
            }
        } catch (\Throwable $e) {
            $fresh = null;
        }
        if (! $fresh) {
            return is_array($kept) ? ($this->memo[$key] = $kept + ['fetchedAt' => $keptAt?->toIso8601String() ?? now()->toIso8601String()]) : null;
        }

        $now = now();
        try {
            DB::table('as_weather_forecasts')->upsert([[
                'placeKey' => $key, 'lat' => round($lat, 4), 'lng' => round($lon, 4), 'model' => 'best_match',
                'payload' => json_encode($fresh, JSON_UNESCAPED_SLASHES), 'fetchedAt' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]], ['placeKey'], ['lat', 'lng', 'model', 'payload', 'fetchedAt', 'updated_at']);
        } catch (\Throwable $e) {
            Cache::put('weather:reading:' . $key, $fresh + ['fetchedAt' => $now->toIso8601String()], $now->copy()->addHours(self::FRESH_HOURS));
        }

        return $this->memo[$key] = $fresh + ['fetchedAt' => $now->toIso8601String()];
    }

    /** One hour of a reading, as the panels draw it. */
    private function hourRow(array $h, int $i, Carbon $when): array
    {
        $code = isset($h['weather_code'][$i]) ? (int) $h['weather_code'][$i] : null;
        $meta = $this->codeMeta((int) ($code ?? 0));

        return [
            'time' => $h['time'][$i],
            'hour' => $when->isoFormat('h A'),
            'dow' => $when->isoFormat('ddd'),
            'text' => $meta['text'],
            'emoji' => $meta['emoji'],
            'code' => $code,
            'night' => $when->hour < 6 || $when->hour >= 18,
            'temp' => isset($h['temperature_2m'][$i]) ? (int) round($h['temperature_2m'][$i]) : null,
            'pop' => isset($h['precipitation_probability'][$i]) ? (int) $h['precipitation_probability'][$i] : null,
            'mm' => isset($h['precipitation'][$i]) ? round((float) $h['precipitation'][$i], 1) : null,
            'humidity' => isset($h['relative_humidity_2m'][$i]) ? (int) $h['relative_humidity_2m'][$i] : null,
            'wind' => isset($h['wind_speed_10m'][$i]) ? (int) round($h['wind_speed_10m'][$i]) : null,
        ];
    }

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
            // When this place's reading was asked of Open-Meteo: it is asked
            // again once three hours have passed, so the panels can say how
            // fresh what they show is.
            'fetchedAt' => $this->forecastFetchedAt($geo['lat'], $geo['lon'], $dayCount)?->toIso8601String(),
        ];
    }

    /**
     * The sky as it is right now at coordinates -- not the day's summary.
     *
     * A day whose forecast says "rain" is dry for most of its hours, and a
     * greeting that calls a sunny noon maulang because it will pour at
     * five has not looked out of the window. This is the window: the
     * current conditions, cached twenty minutes so a page opened and
     * reopened is not twenty calls, and re-asked on the next load after
     * that. Null when the service cannot answer; never cached as a failure.
     *
     * @return array{code:int,text:string,emoji:string,temp:?int,feels:?int,isDay:bool,wind:?int,rain:?float,humidity:?int,at:?string,fetchedAt:string}|null
     */
    public function current(float $lat, float $lon): ?array
    {
        // The hour we are in, from the place's kept reading: a reading kept up
        // to three hours holds the hour now as its own forecast for it.
        $r = $this->reading($lat, $lon);
        if (! $r) {
            return null;
        }
        $h = $r['hourly'];
        $tz = $r['tz'];
        $now = Carbon::now($tz)->startOfHour();
        foreach ($h['time'] as $i => $iso) {
            $when = Carbon::parse($iso, $tz);
            if (! $when->equalTo($now)) {
                continue;
            }
            $code = (int) ($h['weather_code'][$i] ?? 0);
            $meta = $this->codeMeta($code);

            return [
                'code' => $code,
                'text' => $meta['text'],
                'emoji' => $meta['emoji'],
                'temp' => isset($h['temperature_2m'][$i]) ? (int) round($h['temperature_2m'][$i]) : null,
                'feels' => isset($h['apparent_temperature'][$i]) ? (int) round($h['apparent_temperature'][$i]) : null,
                'isDay' => isset($h['is_day'][$i]) ? (int) $h['is_day'][$i] === 1 : ($when->hour >= 6 && $when->hour < 18),
                'wind' => isset($h['wind_speed_10m'][$i]) ? (int) round($h['wind_speed_10m'][$i]) : null,
                'rain' => isset($h['rain'][$i]) ? (float) $h['rain'][$i] : (isset($h['precipitation'][$i]) ? (float) $h['precipitation'][$i] : null),
                'humidity' => isset($h['relative_humidity_2m'][$i]) ? (int) $h['relative_humidity_2m'][$i] : null,
                'at' => (string) $iso,
                'fetchedAt' => $r['fetchedAt'],
            ];
        }

        return null;
    }

    /** The sky right now for a free-text place, or null if it can't resolve. */
    public function currentForPlace(?string $place): ?array
    {
        $place = trim((string) $place);
        if ($place === '') {
            return null;
        }
        $geo = $this->geocode($place);

        return $geo ? $this->current($geo['lat'], $geo['lon']) : null;
    }

    /** When the cached daily forecast for these coordinates was fetched, if it is cached. */
    public function forecastFetchedAt(float $lat, float $lon, int $dayCount = 5): ?Carbon
    {
        $r = $this->reading($lat, $lon);

        return $r ? Carbon::parse($r['fetchedAt']) : null;
    }

    /**
     * Resolve a place name to coordinates. Cached 30 days on success; a genuine
     * "no such place" is cached briefly, while network errors aren't cached so
     * they retry next time.
     */
    public function geocode(string $place): ?array
    {
        // A Springfield in every state: the search is confined to the
        // farmer's own country, and the cache remembers which.
        $country = \App\Support\Region::code();
        $key = 'weather:geo:' . $country . ':' . md5(Str::lower(trim($place)));
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
                'countryCode' => $country,
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

    /** The next days for coordinates, today first (from the place's kept reading). */
    public function forecast(float $lat, float $lon, int $dayCount = 5): ?array
    {
        $dayCount = max(1, min(16, $dayCount));
        $r = $this->reading($lat, $lon);
        if (! $r) {
            return null;
        }
        $daily = $r['daily'];
        $today = Carbon::now($r['tz'])->toDateString();
        $days = [];
        foreach ($daily['time'] as $i => $iso) {
            if ($iso < $today || count($days) >= $dayCount) {
                continue;
            }
            $code = (int) ($daily['weather_code'][$i] ?? 0);
            $meta = $this->codeMeta($code);
            $when = Carbon::parse($iso);
            $first = empty($days);
            $days[] = [
                'date' => $iso,
                'isToday' => $first,
                'dow' => $first ? 'Today' : $when->isoFormat('ddd'),
                'label' => $when->isoFormat('MMM D'),
                'code' => $code,
                'text' => $meta['text'],
                'emoji' => $meta['emoji'],
                'max' => isset($daily['temperature_2m_max'][$i]) ? (int) round($daily['temperature_2m_max'][$i]) : null,
                'min' => isset($daily['temperature_2m_min'][$i]) ? (int) round($daily['temperature_2m_min'][$i]) : null,
                'pop' => isset($daily['precipitation_probability_max'][$i]) ? (int) $daily['precipitation_probability_max'][$i] : null,
            ];
        }

        return $days ?: null;
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
        $r = $this->reading($lat, $lon);
        if (! $r) {
            return null;
        }
        $h = $r['hourly'];
        $tz = $r['tz'];
        $now = Carbon::now($tz)->startOfHour();
        $out = [];
        foreach ($h['time'] as $i => $iso) {
            $when = Carbon::parse($iso, $tz);
            if ($when->lt($now) || count($out) >= $hours) {
                continue;
            }
            $out[] = $this->hourRow($h, $i, $when) + ['isNow' => empty($out), 'newDay' => $when->hour === 0];
        }

        return $out ?: null;
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
        $r = $this->reading($lat, $lon);
        if (! $r) {
            return null;
        }
        $h = $r['hourly'];
        $tz = $r['tz'];
        $now = Carbon::now($tz);
        $last = $now->copy()->startOfDay()->addDays($days);
        $out = [];
        foreach ($h['time'] as $i => $iso) {
            $when = Carbon::parse($iso, $tz);
            // Today starts at the hour we are in; every other day is whole.
            if (($when->isSameDay($now) && $when->lt($now->copy()->startOfHour())) || $when->lt($now->copy()->startOfDay()) || $when->gte($last)) {
                continue;
            }
            $row = $this->hourRow($h, $i, $when);
            unset($row['dow']);
            $out[$when->toDateString()][] = $row + ['isNow' => $when->isSameDay($now) && $when->hour === $now->hour];
        }

        return $out ?: null;
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
