<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satellite Analysis without Earth Engine (2026-10-07: the field read
 * "the satellite link is being set up" for as long as nobody had a Google
 * Cloud project to run services/field-health on).
 *
 * The same Sentinel-2 and Sentinel-1 pictures, from Microsoft's Planetary
 * Computer: a public STAC catalogue (which pictures cover the field) and a
 * public tile and statistics service (what the pictures say inside the
 * field). No account, no key, nothing to deploy. It answers in exactly the
 * shape the Earth Engine service does (see services/field-health/main.py),
 * so the report, Anee's prompt and the saved analyses do not change.
 *
 *   Sentinel-2 L2A: NDVI from B08 and B04; the scene classification (SCL)
 *     says which of the field's own pixels are clear (vegetation, bare
 *     soil, water, dark, unclassified) and which are cloud, shadow or
 *     cirrus. One statistics request returns the clear share, the NDVI of
 *     the clear pixels (sum of ndvi times clear over the sum of clear) and
 *     the spread of the whole field.
 *   Sentinel-1 RTC: VV and VH backscatter in dB (terrain corrected gamma
 *     nought), which sees through typhoon cloud.
 *
 * Its tile addresses never expire, unlike Earth Engine's map ids.
 */
class PlanetarySatellite
{
    private const STAC = 'https://planetarycomputer.microsoft.com/api/stac/v1/search';
    private const DATA = 'https://planetarycomputer.microsoft.com/api/data/v1';
    private const MIN_CLEAR = 0.6;        // share of the FIELD's own pixels that must be clear
    private const CLEAR = '((SCL==2)*1.0+(SCL==4)*1.0+(SCL==5)*1.0+(SCL==6)*1.0+(SCL==7)*1.0)';
    private const NDVI = '((B08*1.0-B04)/(B08*1.0+B04))';
    private const DB = '10*log10(vv);10*log10(vh);10*log10(vv)-10*log10(vh)';

    /** The field's look from space, in the Earth Engine service's shape. */
    public function fieldHealth(array $polygon, int $days = 10): array
    {
        try {
            $ring = $this->ring($polygon);
            $area = $this->areaHa($ring);
            if ($area > 2000) {
                return ['ok' => false, 'error' => 'This field is too large to read (over 2,000 hectares). Draw one field at a time.'];
            }
            $days = max(5, min($days, 30));
            $geom = ['type' => 'Polygon', 'coordinates' => [$ring]];
            $c = $this->centroid($ring);
            $s2 = $this->sentinel2($geom, $ring, $days);
            $s1 = $this->sentinel1($geom, $ring, $days);
            // One picture of the field and its surroundings per layer (a
            // GroundOverlay the browser smooths), beside the tiles.
            $box = $this->around($ring);
            if ($s2['available'] ?? false) {
                $s2['crops'] = ['rgb' => $this->cropRgb($s2['imageId'], $box), 'ndvi' => $this->cropNdvi($s2['imageId'], $box)];
            }
            if ($s1['available'] ?? false) {
                $s1['crops'] = ['sar' => $this->cropSar($s1['imageId'], $box)];
            }
            $mode = ($s2['available'] ?? false) && ($s1['available'] ?? false) ? 'optical+radar'
                : (($s1['available'] ?? false) ? 'radar-only' : (($s2['available'] ?? false) ? 'optical-only' : 'none'));

            return ['ok' => true, 'data' => [
                'ok' => true,
                'source' => 'planetary',
                'generatedAt' => $this->times(time()),
                'areaHa' => round($area, 2),
                'centroid' => ['lng' => round($c[0], 6), 'lat' => round($c[1], 6)],
                'cropBounds' => array_map(fn ($v) => round($v, 6), $box),
                'sentinel2' => $s2,
                'sentinel1' => $s1,
                'mode' => $mode,
                'series' => $this->series($geom, 90),
            ]];
        } catch (\Throwable $e) {
            Log::warning('Planetary satellite read failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'The satellite pictures could not be read right now. Please try again in a few minutes.'];
        }
    }

    /** Tile addresses for a saved report (they do not expire; built from the picture ids). */
    public function tiles(array $polygon, ?string $s2Id, ?string $s1Id): array
    {
        $out = [];
        $crops = [];
        $box = null;
        try {
            $box = $this->around($this->ring($polygon));
        } catch (\Throwable $e) {
        }
        if ($s2Id) {
            $out['ndvi'] = $this->tileNdvi($s2Id);
            $out['rgb'] = $this->tileRgb($s2Id);
            if ($box) {
                $crops['rgb'] = $this->cropRgb($s2Id, $box);
                $crops['ndvi'] = $this->cropNdvi($s2Id, $box);
            }
        }
        if ($s1Id) {
            $out['sar'] = $this->tileSar($s1Id);
            if ($box) {
                $crops['sar'] = $this->cropSar($s1Id, $box);
            }
        }

        return ['ok' => true, 'data' => ['ok' => true, 'tiles' => $out, 'crops' => $crops, 'cropBounds' => $box]];
    }

    // ------------------------------------------------------------------ Sentinel-2

    private function sentinel2(array $geom, array $ring, int $days): array
    {
        $items = $this->search('sentinel-2-l2a', $geom, 45, ['eo:cloud_cover' => ['lt' => 90]], 30);
        $cut = time() - $days * 86400;
        $recent = array_values(array_filter($items, fn ($i) => strtotime($i['properties']['datetime']) >= $cut));
        $older = array_values(array_filter($items, fn ($i) => strtotime($i['properties']['datetime']) < $cut));

        $pick = $this->firstClear($recent, $geom);
        if (! $pick) {
            // Typhoon cloud: no clear look inside the window. Say when the
            // last clear one was (up to 45 days back); the radar reads for now.
            $last = $this->firstClear($older, $geom);

            return ['available' => false, 'blockedByCloud' => true, 'lastClear' => $this->times($last ? strtotime($last[0]['properties']['datetime']) : null), 'windowDays' => $days];
        }
        [$item, $st] = $pick;
        $p = $item['properties'];
        $id = $item['id'];
        $whole = $st[2];
        $clearMean = $st[0]['mean'] > 0 ? $st[1]['mean'] / $st[0]['mean'] : $whole['mean'];
        $p50 = $whole['percentile_50'] ?? $whole['median'] ?? $clearMean;

        return [
            'available' => true,
            'imageId' => $id,
            'satellite' => $p['platform'] ?? 'Sentinel-2',
            'time' => $this->times(strtotime($p['datetime'])),
            'sceneCloud' => isset($p['eo:cloud_cover']) ? round((float) $p['eo:cloud_cover'], 1) : null,
            'fieldClear' => round((float) $st[0]['mean'], 2),
            'ndvi' => [
                'mean' => round($clearMean, 3), 'stdDev' => round((float) ($whole['std'] ?? 0), 3),
                'min' => round((float) $whole['min'], 3), 'max' => round((float) $whole['max'], 3),
                'p10' => $this->r($whole['percentile_10'] ?? null), 'p50' => $this->r($p50), 'p90' => $this->r($whole['percentile_90'] ?? null),
                'lowShare' => $this->r($this->shareBelow($whole['histogram'] ?? null, max(0.0, (float) $p50 - 0.1))),
            ],
            'zones' => $this->zones('sentinel-2-l2a', $id, $ring, self::CLEAR . ';' . self::NDVI . '*' . self::CLEAR, fn ($s) => $s[0]['mean'] > 0.05 ? $s[1]['mean'] / $s[0]['mean'] : null),
            'tiles' => ['ndvi' => $this->tileNdvi($id), 'rgb' => $this->tileRgb($id)],
            'windowDays' => $days,
        ];
    }

    /**
     * The newest of these pictures in which enough of the field is clear,
     * with its statistics: [item, [clear, ndvi times clear, ndvi]] or null.
     */
    private function firstClear(array $items, array $geom): ?array
    {
        foreach (array_chunk($items, 4) as $batch) {
            $stats = $this->statsMany('sentinel-2-l2a', array_column($batch, 'id'), $geom,
                self::CLEAR . ';' . self::NDVI . '*' . self::CLEAR . ';' . self::NDVI, ['p' => [10, 50, 90], 'histogram_bins' => 24, 'histogram_range' => '-0.2,1']);
            foreach ($batch as $k => $item) {
                $st = $stats[$k] ?? null;
                if ($st && count($st) >= 3 && $st[0]['mean'] >= self::MIN_CLEAR) {
                    return [$item, $st];
                }
            }
        }

        return null;
    }

    // ------------------------------------------------------------------ Sentinel-1

    private function sentinel1(array $geom, array $ring, int $days): array
    {
        // Sentinel-1 passes a place every 6 to 12 days: look a little further when needed.
        $window = $days;
        $items = $this->search('sentinel-1-rtc', $geom, $window, [], 6);
        if (! $items) {
            $window = max($days, 24);
            $items = $this->search('sentinel-1-rtc', $geom, $window, [], 6);
        }
        if (! $items) {
            return ['available' => false, 'windowDays' => $window];
        }
        $item = $items[0];
        $st = $this->statsMany('sentinel-1-rtc', [$item['id']], $geom, self::DB)[0] ?? null;
        if (! $st || count($st) < 3) {
            return ['available' => false, 'windowDays' => $window];
        }
        $p = $item['properties'];
        $vv = (float) $st[0]['mean'];
        $vh = (float) $st[1]['mean'];

        return [
            'available' => true,
            'imageId' => $item['id'],
            'satellite' => 'Sentinel-' . strtoupper(substr((string) ($p['platform'] ?? 'sentinel-1'), -2)),
            'pass' => isset($p['sat:orbit_state']) ? strtoupper($p['sat:orbit_state']) : null,
            'time' => $this->times(strtotime($p['datetime'])),
            'vvDb' => round($vv, 2), 'vhDb' => round($vh, 2),
            'vvStdDb' => round((float) $st[0]['std'], 2), 'vhStdDb' => round((float) $st[1]['std'], 2),
            'vvMinusVhDb' => round((float) $st[2]['mean'], 2),
            // Linear cross ratio VH/VV: rises with leafy biomass in most crops.
            'vhVvRatio' => round((10 ** ($vh / 10)) / (10 ** ($vv / 10)), 3),
            'zones' => $this->zones('sentinel-1-rtc', $item['id'], $ring, '10*log10(vh)', fn ($s) => $s[0]['mean']),
            'tiles' => ['sar' => $this->tileSar($item['id'])],
            'windowDays' => $window,
        ];
    }

    // ------------------------------------------------------------------ three months

    private function series(array $geom, int $days): array
    {
        $ndvi = [];
        $s2 = $this->search('sentinel-2-l2a', $geom, $days, ['eo:cloud_cover' => ['lt' => 70]], 60);
        foreach (array_chunk($s2, 8) as $batch) {
            $stats = $this->statsMany('sentinel-2-l2a', array_column($batch, 'id'), $geom, self::CLEAR . ';' . self::NDVI . '*' . self::CLEAR);
            foreach ($batch as $k => $item) {
                $st = $stats[$k] ?? null;
                if (! $st || count($st) < 2 || $st[0]['mean'] < self::MIN_CLEAR) {
                    continue;
                }
                $day = $this->phDay($item['properties']['datetime']);
                $ndvi[$day] = ['date' => $day, 'ndvi' => round($st[1]['mean'] / $st[0]['mean'], 3)];
            }
        }
        $radar = [];
        $s1 = $this->search('sentinel-1-rtc', $geom, $days, [], 40);
        foreach (array_chunk($s1, 8) as $batch) {
            $stats = $this->statsMany('sentinel-1-rtc', array_column($batch, 'id'), $geom, '10*log10(vv);10*log10(vh)');
            foreach ($batch as $k => $item) {
                $st = $stats[$k] ?? null;
                if (! $st || count($st) < 2) {
                    continue;
                }
                $day = $this->phDay($item['properties']['datetime']);
                $radar[$day] = ['date' => $day, 'vv' => round((float) $st[0]['mean'], 3), 'vh' => round((float) $st[1]['mean'], 3)];
            }
        }
        ksort($ndvi);
        ksort($radar);

        return ['ndvi' => array_values($ndvi), 'radar' => array_values($radar), 'days' => $days];
    }

    // ------------------------------------------------------------------ the services

    /** The pictures of a collection over the field in the last $days, newest first. */
    private function search(string $collection, array $geom, int $days, array $query, int $limit): array
    {
        $body = [
            'collections' => [$collection],
            'intersects' => $geom,
            'datetime' => gmdate('Y-m-d\TH:i:s\Z', time() - $days * 86400) . '/' . gmdate('Y-m-d\TH:i:s\Z'),
            'sortby' => [['field' => 'properties.datetime', 'direction' => 'desc']],
            'limit' => $limit,
        ];
        if ($query) {
            $body['query'] = $query;
        }
        $res = Http::timeout(40)->connectTimeout(8)->acceptJson()->retry(2, 800, throw: false)->post(self::STAC, $body);
        if (! $res->successful()) {
            throw new \RuntimeException('catalogue answered ' . $res->status());
        }

        return (array) ($res->json('features') ?? []);
    }

    /**
     * Statistics of several pictures over one shape, side by side: for each
     * picture, a list of its expression bands' statistics (in order), or null.
     */
    private function statsMany(string $collection, array $ids, array $geom, string $expression, array $extra = []): array
    {
        $feature = ['type' => 'Feature', 'properties' => (object) [], 'geometry' => $geom];
        $responses = Http::pool(function (Pool $pool) use ($collection, $ids, $feature, $expression, $extra) {
            foreach ($ids as $k => $id) {
                $q = http_build_query(['collection' => $collection, 'item' => $id, 'expression' => $expression, 'asset_as_band' => 'true'] + $extra);
                $q = preg_replace('/%5B\d+%5D=/', '=', $q);   // p[0]=10&p[1]=50 -> p=10&p=50
                $pool->as((string) $k)->timeout(60)->connectTimeout(8)->acceptJson()->post(self::DATA . '/item/statistics?' . $q, $feature);
            }
        });
        $out = [];
        foreach ($ids as $k => $id) {
            $r = $responses[(string) $k] ?? null;
            if (! $r instanceof \Illuminate\Http\Client\Response || ! $r->successful()) {
                $out[$k] = null;
                continue;
            }
            $stats = $r->json('properties.statistics');
            $out[$k] = is_array($stats) ? array_values($stats) : null;
        }

        return $out;
    }

    /**
     * The field cut into a 3 by 3 grid over its bounds: each cell's value, so
     * the report can say where the weak part is (north, south east...).
     */
    private function zones(string $collection, string $id, array $ring, string $expression, callable $value): array
    {
        $xs = array_column($ring, 0);
        $ys = array_column($ring, 1);
        [$x0, $x1, $y0, $y1] = [min($xs), max($xs), min($ys), max($ys)];
        $names = [['north west', 'north', 'north east'], ['west', 'center', 'east'], ['south west', 'south', 'south east']];
        $cells = [];
        for ($r = 0; $r < 3; $r++) {
            for ($c = 0; $c < 3; $c++) {
                $part = $this->clip($ring, $x0 + ($x1 - $x0) * $c / 3, $y1 - ($y1 - $y0) * ($r + 1) / 3, $x0 + ($x1 - $x0) * ($c + 1) / 3, $y1 - ($y1 - $y0) * $r / 3);
                if (count($part) >= 4 && $this->areaHa($part) > 0.001) {
                    $cells[] = [$r, $c, $part];
                }
            }
        }
        $out = [];
        // Every cell at once.
        $q = preg_replace('/%5B\d+%5D=/', '=', http_build_query(['collection' => $collection, 'item' => $id, 'expression' => $expression, 'asset_as_band' => 'true']));
        $responses = Http::pool(function (Pool $pool) use ($cells, $q) {
            foreach ($cells as $i => [$r, $c, $part]) {
                $pool->as((string) $i)->timeout(60)->connectTimeout(8)->acceptJson()
                    ->post(self::DATA . '/item/statistics?' . $q, ['type' => 'Feature', 'properties' => (object) [], 'geometry' => ['type' => 'Polygon', 'coordinates' => [$part]]]);
            }
        });
        $stats = [];
        foreach ($cells as $i => $cell) {
            $res = $responses[(string) $i] ?? null;
            $json = $res instanceof \Illuminate\Http\Client\Response && $res->successful() ? $res->json('properties.statistics') : null;
            $stats[$i] = is_array($json) ? array_values($json) : null;
        }
        foreach ($cells as $i => [$r, $c]) {
            $st = $stats[$i];
            $v = $st ? $value($st) : null;
            if ($v !== null && is_finite((float) $v)) {
                $out[] = ['row' => $r, 'col' => $c, 'where' => $names[$r][$c], 'mean' => round((float) $v, 3)];
            }
        }

        return $out;
    }

    private function tileNdvi(string $id): string
    {
        return $this->tile(['collection' => 'sentinel-2-l2a', 'item' => $id, 'expression' => '(B08-B04)/(B08+B04)', 'asset_as_band' => 'true',
            'rescale' => '0,0.9', 'colormap_name' => 'rdylgn', 'format' => 'png']);
    }

    private function tileRgb(string $id): string
    {
        return $this->tile(['collection' => 'sentinel-2-l2a', 'item' => $id, 'assets' => 'visual', 'asset_bidx' => 'visual|1,2,3', 'format' => 'png']);
    }

    private function tileSar(string $id): string
    {
        return self::DATA . '/item/tiles/WebMercatorQuad/{z}/{x}/{y}@1x?' . http_build_query(['collection' => 'sentinel-1-rtc', 'item' => $id,
            'expression' => self::DB, 'asset_as_band' => 'true', 'format' => 'png']) . '&rescale=-22,0&rescale=-28,-8&rescale=2,14';
    }

    /** [west, south, east, north] of the field and around it: at least 1.2 km a side, the field in the middle. */
    private function around(array $ring): array
    {
        $xs = array_column($ring, 0);
        $ys = array_column($ring, 1);
        [$w, $e, $s, $n] = [min($xs), max($xs), min($ys), max($ys)];
        $lat = deg2rad(($s + $n) / 2);
        $minLat = 1200 / 111320;
        $minLng = 1200 / (111320 * max(.2, cos($lat)));
        $padLat = max(($n - $s) * .6, ($minLat - ($n - $s)) / 2);
        $padLng = max(($e - $w) * .6, ($minLng - ($e - $w)) / 2);

        return [$w - $padLng, $s - $padLat, $e + $padLng, $n + $padLat];
    }

    private function crop(array $box, array $q): string
    {
        return self::DATA . '/item/bbox/' . implode(',', array_map(fn ($v) => round($v, 6), $box)) . '.png?' . preg_replace('/%5B\d+%5D=/', '=', http_build_query($q));
    }

    private function cropRgb(string $id, array $box): string
    {
        // The true color picture, brightened a little (it reads dark straight off the satellite).
        return $this->crop($box, ['collection' => 'sentinel-2-l2a', 'item' => $id, 'assets' => 'visual', 'asset_bidx' => 'visual|1,2,3',
            'color_formula' => 'Gamma RGB 1.6 Saturation 1.15']);
    }

    private function cropNdvi(string $id, array $box): string
    {
        return $this->crop($box, ['collection' => 'sentinel-2-l2a', 'item' => $id, 'expression' => '(B08-B04)/(B08+B04)', 'asset_as_band' => 'true',
            'rescale' => '0,0.9', 'colormap_name' => 'rdylgn']);
    }

    private function cropSar(string $id, array $box): string
    {
        return $this->crop($box, ['collection' => 'sentinel-1-rtc', 'item' => $id, 'expression' => self::DB, 'asset_as_band' => 'true'])
            . '&rescale=-22,0&rescale=-28,-8&rescale=2,14';
    }

    private function tile(array $q): string
    {
        return self::DATA . '/item/tiles/WebMercatorQuad/{z}/{x}/{y}@1x?' . http_build_query($q);
    }

    // ------------------------------------------------------------------ geometry and time

    /** The outer ring as [lng, lat] pairs, closed. */
    private function ring(array $polygon): array
    {
        $ring = array_values(array_map(fn ($p) => [(float) $p[0], (float) $p[1]], (array) ($polygon['coordinates'][0] ?? [])));
        if (count($ring) < 3) {
            throw new \InvalidArgumentException('a field needs at least three corners');
        }
        if ($ring[0] !== $ring[count($ring) - 1]) {
            $ring[] = $ring[0];
        }

        return $ring;
    }

    /** Area in hectares (the ring projected flat around its own latitude: plenty for one field). */
    private function areaHa(array $ring): float
    {
        $lat0 = deg2rad(array_sum(array_column($ring, 1)) / count($ring));
        $R = 6371008.8;
        $sum = 0.0;
        for ($i = 0, $n = count($ring) - 1; $i < $n; $i++) {
            [$x1, $y1] = [deg2rad($ring[$i][0]) * $R * cos($lat0), deg2rad($ring[$i][1]) * $R];
            [$x2, $y2] = [deg2rad($ring[$i + 1][0]) * $R * cos($lat0), deg2rad($ring[$i + 1][1]) * $R];
            $sum += $x1 * $y2 - $x2 * $y1;
        }

        return abs($sum) / 2 / 10000;
    }

    private function centroid(array $ring): array
    {
        $pts = array_slice($ring, 0, -1);

        return [array_sum(array_column($pts, 0)) / count($pts), array_sum(array_column($pts, 1)) / count($pts)];
    }

    /** The ring clipped to a rectangle (Sutherland and Hodgman), closed; empty when nothing is left. */
    private function clip(array $ring, float $x0, float $y0, float $x1, float $y1): array
    {
        $pts = array_slice($ring, 0, -1);
        $edges = [
            fn ($p) => $p[0] >= $x0, fn ($p) => $p[0] <= $x1, fn ($p) => $p[1] >= $y0, fn ($p) => $p[1] <= $y1,
        ];
        $cross = [
            fn ($a, $b) => [$x0, $a[1] + ($b[1] - $a[1]) * ($x0 - $a[0]) / ($b[0] - $a[0])],
            fn ($a, $b) => [$x1, $a[1] + ($b[1] - $a[1]) * ($x1 - $a[0]) / ($b[0] - $a[0])],
            fn ($a, $b) => [$a[0] + ($b[0] - $a[0]) * ($y0 - $a[1]) / ($b[1] - $a[1]), $y0],
            fn ($a, $b) => [$a[0] + ($b[0] - $a[0]) * ($y1 - $a[1]) / ($b[1] - $a[1]), $y1],
        ];
        foreach ($edges as $e => $in) {
            $next = [];
            $n = count($pts);
            for ($i = 0; $i < $n; $i++) {
                $cur = $pts[$i];
                $prev = $pts[($i + $n - 1) % $n];
                if ($in($cur)) {
                    if (! $in($prev)) {
                        $next[] = $cross[$e]($prev, $cur);
                    }
                    $next[] = $cur;
                } elseif ($in($prev)) {
                    $next[] = $cross[$e]($prev, $cur);
                }
            }
            $pts = $next;
            if (! $pts) {
                return [];
            }
        }
        $pts[] = $pts[0];

        return $pts;
    }

    /** Share of a histogram's pixels under a value (bins split in proportion). */
    private function shareBelow(?array $hist, float $under): ?float
    {
        if (! $hist || count($hist) < 2) {
            return null;
        }
        [$counts, $edges] = $hist;
        $total = array_sum($counts);
        if ($total <= 0) {
            return null;
        }
        $below = 0.0;
        foreach ($counts as $i => $n) {
            [$a, $b] = [$edges[$i], $edges[$i + 1]];
            if ($b <= $under) {
                $below += $n;
            } elseif ($a < $under) {
                $below += $n * ($under - $a) / max(1e-9, $b - $a);
            }
        }

        return $below / $total;
    }

    private function times(?int $ts): array
    {
        if (! $ts) {
            return ['utc' => null, 'ph' => null, 'phText' => null, 'ageDays' => null];
        }
        $utc = \Illuminate\Support\Carbon::createFromTimestampUTC($ts);
        $ph = $utc->copy()->timezone('Asia/Manila');

        return [
            'utc' => $utc->format('Y-m-d\TH:i:s\Z'),
            'ph' => $ph->format('Y-m-d\TH:i:sP'),
            'phText' => $ph->format('F j, Y, g:i A'),
            'ageDays' => round((time() - $ts) / 86400, 1),
        ];
    }

    private function phDay(string $iso): string
    {
        return \Illuminate\Support\Carbon::parse($iso)->timezone('Asia/Manila')->toDateString();
    }

    private function r(mixed $v, int $n = 3): ?float
    {
        return $v === null || ! is_numeric($v) ? null : round((float) $v, $n);
    }
}
