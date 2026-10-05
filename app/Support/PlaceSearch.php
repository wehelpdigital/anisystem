<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Find a place by name for the map's search box (2026-10-05).
 *
 * Google's place search is not on the site's maps key (its API restrictions
 * allow the map itself and nothing else: Places, Places (New) and the
 * Geocoder all come back "not authorized"), so the box asks OpenStreetMap's
 * Nominatim from the server instead. It knows towns, barangays, roads and
 * landmarks, which is what a farmer types.
 *
 * Nominatim's house rules shape this: a real User-Agent, at most one request
 * a second, and no search-as-you-type. So the page asks once per Enter, the
 * answer is cached for a month, and a busy second waits once and then falls
 * back to Open-Meteo's town list (the weather's own geocoder), which only
 * knows towns but always answers.
 */
final class PlaceSearch
{
    private const NOMINATIM = 'https://nominatim.openstreetmap.org/search';
    private const OPEN_METEO = 'https://geocoding-api.open-meteo.com/v1/search';
    private const AGENT = 'anee.io farm map search (https://anee.io)';

    /**
     * Up to six places for words, each { name, label, lat, lng, bbox? }, where
     * bbox is [south, north, west, east] when the place has an extent.
     */
    public static function find(string $words): array
    {
        $words = trim(preg_replace('/\s+/u', ' ', $words));
        if (mb_strlen($words) < 2) {
            return [];
        }
        $country = strtolower(Region::code());
        $key = 'placesearch:' . $country . ':' . md5(Str::lower($words));
        $cached = rescue(fn () => Cache::get($key), null, false);
        if (is_array($cached)) {
            return $cached;
        }

        $found = self::nominatim($words, $country);
        if ($found === null) {
            $found = self::openMeteo($words, $country);
        }
        if ($found === null) {
            return [];   // both down: not cached, the next Enter asks again
        }
        rescue(fn () => Cache::put($key, $found, $found ? now()->addDays(30) : now()->addHours(6)), null, false);

        return $found;
    }

    /** OpenStreetMap's answer, or null when it could not be asked. */
    private static function nominatim(string $words, string $country): ?array
    {
        // One request a second for the whole site: a second caller waits once.
        $go = fn () => RateLimiter::attempt('nominatim', 1, fn () => true, 1);
        if (! $go()) {
            usleep(1_050_000);
            if (! $go()) {
                return null;
            }
        }

        try {
            $res = Http::timeout(8)
                ->withHeaders(['User-Agent' => self::AGENT, 'Accept-Language' => 'en'])
                ->get(self::NOMINATIM, [
                    'q' => $words,
                    'format' => 'jsonv2',
                    'countrycodes' => $country,
                    'limit' => 6,
                    'addressdetails' => 0,
                ]);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $res->ok() || ! is_array($res->json())) {
            return null;
        }

        return collect($res->json())->map(function ($r) {
            $parts = array_values(array_filter(array_map('trim', explode(',', (string) ($r['display_name'] ?? '')))));
            $name = trim((string) ($r['name'] ?? '')) ?: ($parts[0] ?? '');
            // The rest of the address names where it is, without the
            // country, the postcode or the name said twice.
            $where = collect($parts)
                ->reject(fn ($p) => $p === $name || preg_match('/^\d{3,6}$/', $p))
                ->slice(0, -1)
                ->take(3)
                ->implode(', ');
            $bb = $r['boundingbox'] ?? null;

            return [
                'name' => $name,
                'label' => $where,
                'lat' => round((float) $r['lat'], 6),
                'lng' => round((float) $r['lon'], 6),
                'bbox' => is_array($bb) && count($bb) === 4 ? array_map('floatval', $bb) : null,
            ];
        })->filter(fn ($p) => $p['name'] !== '')->values()->all();
    }

    /** Open-Meteo's towns: the fallback that always answers, or null when it is down too. */
    private static function openMeteo(string $words, string $country): ?array
    {
        $town = trim((string) Str::of($words)->explode(',')->first());
        try {
            $res = Http::timeout(8)->get(self::OPEN_METEO, [
                'name' => $town,
                'count' => 6,
                'language' => 'en',
                'format' => 'json',
                'countryCode' => strtoupper($country),
            ]);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $res->ok()) {
            return null;
        }

        return collect($res->json('results') ?: [])->map(fn ($r) => [
            'name' => (string) ($r['name'] ?? ''),
            'label' => collect([$r['admin2'] ?? null, $r['admin1'] ?? null])->filter()->unique()->implode(', '),
            'lat' => round((float) $r['latitude'], 6),
            'lng' => round((float) $r['longitude'], 6),
            'bbox' => null,
        ])->filter(fn ($p) => $p['name'] !== '')->values()->all();
    }
}
