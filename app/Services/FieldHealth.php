<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The road to the field health service (services/field-health): the
 * FastAPI app that reads Sentinel-2 and Sentinel-1 through Google Earth
 * Engine, and the storm tracks. anee.io never talks to Earth Engine itself.
 *
 *   FIELD_HEALTH_URL     where the service runs (Cloud Run)
 *   FIELD_HEALTH_TOKEN   the shared secret, sent as X-Service-Token
 */
class FieldHealth
{
    public function configured(): bool
    {
        return (string) config('services.field_health.url') !== '' && (string) config('services.field_health.token') !== '';
    }

    /**
     * The field's latest look from space: NDVI (Sentinel-2, clouds masked)
     * and VV/VH backscatter (Sentinel-1), their times in UTC and Philippine
     * time, the tile URLs, the 3 by 3 zones and 90 days of history.
     *
     * @param  array  $polygon  a GeoJSON Polygon
     * @return array{ok: bool, data?: array, error?: string}
     */
    public function fieldHealth(array $polygon, int $days = 10): array
    {
        return $this->call('post', '/api/field-health', ['polygon' => $polygon, 'days' => $days], 170);
    }

    /** Fresh tile URLs for images a saved report read before (map ids expire). */
    public function tiles(array $polygon, ?string $s2Id, ?string $s1Id): array
    {
        return $this->call('post', '/api/tiles', ['polygon' => $polygon, 's2Id' => $s2Id, 's1Id' => $s1Id], 60);
    }

    /** Active tropical cyclones (GDACS), with distances to the point. */
    public function storms(?float $lat, ?float $lng): array
    {
        return $this->call('get', '/api/storms', array_filter(['lat' => $lat, 'lng' => $lng], fn ($v) => $v !== null), 45);
    }

    private function call(string $method, string $path, array $body, int $timeout): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'not-configured'];
        }
        try {
            $req = Http::timeout($timeout)->connectTimeout(8)->acceptJson()
                ->withHeaders(['X-Service-Token' => (string) config('services.field_health.token')]);
            $url = config('services.field_health.url') . $path;
            $res = $method === 'get' ? $req->get($url, $body) : $req->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('Field health ' . $path . ' gave no answer: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'The satellite service did not answer. Please try again in a few minutes.'];
        }
        if (! $res->successful()) {
            Log::warning('Field health ' . $path . ' answered ' . $res->status() . ': ' . mb_substr($res->body(), 0, 400));

            return ['ok' => false, 'error' => (string) ($res->json('detail') ?: $res->json('error') ?: 'The satellite service could not read this field.')];
        }

        return ['ok' => true, 'data' => (array) $res->json()];
    }
}
