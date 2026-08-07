<?php

namespace App\Http\Controllers;

use App\Models\AsScheduleLot;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    /** Don't resolve more distinct locations than this in one request. */
    private const MAX_LOCATIONS = 12;

    public function __construct(private WeatherService $weather)
    {
    }

    /**
     * 5-day forecast for every distinct lot location across the farmer's
     * schedules. Identical addresses are resolved once (deduped by location key)
     * so repeated locations never cost an extra API call, and the response maps
     * each schedule to the location keys it uses. Loaded over AJAX so the
     * external calls never block the dashboard.
     */
    public function forecast(Request $request)
    {
        $user = $request->user();
        $scheduleIds = $user->schedules()->pluck('id')->all();

        $lots = AsScheduleLot::active()
            ->whereIn('croppingScheduleId', $scheduleIds ?: [-1])
            ->get(['id', 'croppingScheduleId', 'locBarangay', 'locZone', 'locTown', 'locProvince']);

        // Distinct locations + which schedules reference each of them.
        $locations = [];          // key => ['query' => ..., 'label' => ...]
        $scheduleKeys = [];       // scheduleId => [key => key]
        foreach ($lots as $lot) {
            $query = $lot->geocode_query;
            if (! $query) {
                continue;
            }
            $key = $lot->location_key;
            $locations[$key] ??= ['query' => $query, 'label' => $lot->full_address ?: $query];
            $scheduleKeys[$lot->croppingScheduleId][$key] = $key;
        }

        // Resolve each distinct location once (bounded for safety).
        $resolved = [];
        foreach (array_slice($locations, 0, self::MAX_LOCATIONS, true) as $key => $info) {
            $forecast = $this->weather->forecastForPlace($info['query']);
            $resolved[$key] = $forecast
                ? ['ok' => true, 'place' => $forecast['place'], 'days' => $forecast['days']]
                : ['ok' => false, 'place' => $info['label']];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'located' => ! empty($resolved),
                'locations' => $resolved,
                'schedules' => array_map('array_values', $scheduleKeys),
            ],
        ]);
    }

    /**
     * 6-day forecast (today + 5) for every lot of ONE schedule, keyed per lot so
     * the activities board can show each lot's own weather. Locations are still
     * resolved once per distinct address; each lot maps to its location key.
     */
    public function scheduleForecast(Request $request)
    {
        $user = $request->user();
        $scheduleId = (int) $request->query('scheduleId');
        $owned = $scheduleId && $user->schedules()->where('id', $scheduleId)->exists();
        if (! $owned) {
            return response()->json(['success' => true, 'data' => ['located' => false, 'locations' => [], 'lots' => []]]);
        }

        $lots = AsScheduleLot::active()
            ->where('croppingScheduleId', $scheduleId)
            ->orderBy('lotName')
            ->get(['id', 'lotName', 'locBarangay', 'locZone', 'locTown', 'locProvince']);

        $locations = [];   // key => ['query','label']
        $lotList = [];
        foreach ($lots as $lot) {
            $key = $lot->location_key;
            $lotList[] = [
                'id' => $lot->id,
                'name' => $lot->lotName ?: 'Lot',
                'locationKey' => $key,
                'address' => $lot->full_address,
            ];
            if ($key && ! isset($locations[$key])) {
                $locations[$key] = ['query' => $lot->geocode_query, 'label' => $lot->full_address ?: $lot->geocode_query];
            }
        }

        $resolved = [];
        foreach (array_slice($locations, 0, self::MAX_LOCATIONS, true) as $key => $info) {
            $fc = $this->weather->forecastForPlace($info['query'], 6);
            $resolved[$key] = $fc
                ? ['ok' => true, 'place' => $fc['place'], 'days' => $fc['days']]
                : ['ok' => false, 'place' => $info['label']];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'located' => ! empty($resolved),
                'locations' => $resolved,
                'lots' => $lotList,
            ],
        ]);
    }
}
