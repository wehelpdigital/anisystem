<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Tropical cyclones from GDACS (the European Commission JRC and UN OCHA's
 * alert system), shaped for the map: the eye now, every track point with its
 * time and whether it is a forecast, the cone of uncertainty, and the
 * distance to a farm.
 *
 * The field health service (services/field-health) serves the same shape at
 * /api/storms; this twin answers when that service is not connected, so the
 * storm layer never depends on it.
 */
final class StormTracks
{
    private const API = 'https://www.gdacs.org/gdacsapi/api';

    /** @return array{ok: bool, source: string, storms: array, checkedAt: string} */
    public static function near(?float $lat, ?float $lng, int $days = 10): array
    {
        $raw = Cache::remember('storms:gdacs:' . $days, 900, fn () => self::fetch($days));
        $storms = [];
        foreach ($raw as $s) {
            if ($lat !== null && $lng !== null && $s['points']) {
                $eye = $s['eye'];
                $s['distanceKm'] = $eye ? round(self::km($lat, $lng, $eye['lat'], $eye['lng']), 1) : null;
                $near = collect($s['points'])->sortBy(fn ($p) => self::km($lat, $lng, $p['lat'], $p['lng']))->first();
                $s['closest'] = $near + ['km' => round(self::km($lat, $lng, $near['lat'], $near['lng']), 1)];
            }
            $storms[] = $s;
        }
        usort($storms, fn ($a, $b) => [! $a['current'], $a['distanceKm'] ?? 1e9] <=> [! $b['current'], $b['distanceKm'] ?? 1e9]);

        return ['ok' => true, 'source' => 'GDACS (European Commission JRC and UN OCHA)', 'storms' => $storms, 'checkedAt' => now('Asia/Manila')->toIso8601String()];
    }

    /** Great circle distance in kilometres (Haversine). */
    public static function km(float $aLat, float $aLng, float $bLat, float $bLng): float
    {
        $r = 6371.0;
        $p1 = deg2rad($aLat);
        $p2 = deg2rad($bLat);
        $dp = deg2rad($bLat - $aLat);
        $dl = deg2rad($bLng - $aLng);
        $h = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($h)));
    }

    private static function fetch(int $days): array
    {
        $to = now('UTC');
        try {
            $list = Http::timeout(20)->acceptJson()->get(self::API . '/events/geteventlist/SEARCH', [
                'eventlist' => 'TC', 'fromdate' => $to->copy()->subDays($days)->toDateString(), 'todate' => $to->toDateString(),
            ])->json('features') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ((array) $list as $ev) {
            $p = $ev['properties'] ?? [];
            try {
                $geo = Http::timeout(25)->acceptJson()->get((string) ($p['url']['geometry'] ?? ''))->json('features') ?? [];
            } catch (\Throwable $e) {
                continue;
            }
            $year = (int) substr((string) ($p['fromdate'] ?? $to->year), 0, 4);
            $lines = [];
            $points = [];
            $cone = null;
            foreach ((array) $geo as $f) {
                $fp = $f['properties'] ?? [];
                $g = $f['geometry'] ?? [];
                $cls = (string) ($fp['Class'] ?? '');
                if (str_starts_with($cls, 'Line_Line_')) {
                    $lines[(int) substr(strrchr($cls, '_'), 1)] = ['forecast' => (bool) ($fp['forecast'] ?? false), 'cat' => $fp['polygonlabel'] ?? null];
                } elseif (str_starts_with($cls, 'Point_Polygon_Point_')) {
                    $ring = $g['coordinates'][0] ?? [];
                    if (! $ring) {
                        continue;
                    }
                    $label = str_replace(' UTC', '', (string) ($fp['polygonlabel'] ?? ''));
                    $when = null;
                    if (preg_match('#^(\d{1,2})/(\d{1,2}) (\d{1,2}):(\d{2})$#', $label, $m)) {
                        $when = \Illuminate\Support\Carbon::create($year, (int) $m[2], (int) $m[1], (int) $m[3], (int) $m[4], 0, 'UTC');
                    }
                    $points[] = [
                        'i' => (int) substr(strrchr($cls, '_'), 1),
                        'lng' => round(array_sum(array_column($ring, 0)) / count($ring), 4),
                        'lat' => round(array_sum(array_column($ring, 1)) / count($ring), 4),
                        'utc' => $when?->format('Y-m-d\TH:i:s\Z'),
                        'ph' => $when?->copy()->timezone('Asia/Manila')->format('Y-m-d\TH:iP'),
                    ];
                } elseif ($cls === 'Poly_Cones') {
                    $cone = $g;
                }
            }
            usort($points, fn ($a, $b) => $a['i'] <=> $b['i']);
            foreach ($points as &$pt) {
                $seg = $lines[$pt['i']] ?? $lines[$pt['i'] - 1] ?? [];
                $pt['forecast'] = (bool) ($lines[$pt['i']]['forecast'] ?? false);
                $pt['cat'] = $seg['cat'] ?? null;
            }
            unset($pt);
            $past = array_values(array_filter($points, fn ($q) => ! $q['forecast']));
            $out[] = [
                'id' => $p['eventid'] ?? null, 'episode' => $p['episodeid'] ?? null, 'name' => $p['eventname'] ?? ($p['name'] ?? 'Storm'),
                'alert' => $p['alertlevel'] ?? null, 'current' => strtolower((string) ($p['iscurrent'] ?? '')) === 'true',
                'from' => $p['fromdate'] ?? null, 'to' => $p['todate'] ?? null, 'source' => $p['source'] ?? null,
                'maxWindKmh' => $p['severitydata']['severity'] ?? null, 'report' => $p['url']['report'] ?? null,
                'eye' => $past ? end($past) : ($points[0] ?? null), 'points' => $points, 'cone' => $cone,
            ];
        }

        return $out;
    }
}
