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
    /**
     * The weather as a schedule module: the same per-lot forecast the day
     * headers summarise, plus an hour-by-hour tab for deciding when in the day
     * to work. Data arrives over the endpoint below; the page ships empty.
     */
    public function page(Request $request)
    {
        // Same gate every other module uses, so an invited worker reaches it
        // through the boss they work for rather than being locked out.
        $schedule = \App\Models\AsCroppingSchedule::active()
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
            ->where('id', (int) $request->query('id'))
            ->firstOrFail();

        return view('sm.weather', ['schedule' => $schedule]);
    }

    public function scheduleForecast(Request $request)
    {
        $scheduleId = (int) $request->query('scheduleId');
        // forClient, not the caller's own rows: an invited worker sees the
        // farm they work for, exactly as they do in every other module.
        $owned = $scheduleId && \App\Models\AsCroppingSchedule::active()
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
            ->where('id', $scheduleId)
            ->exists();
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

        // Hour-by-hour is only for the Weather module's own tab — the day
        // headers just want the daily chips, and fetching hours for every
        // location would treble that response for nothing.
        $wantHourly = $request->boolean('hourly');

        $resolved = [];
        foreach (array_slice($locations, 0, self::MAX_LOCATIONS, true) as $key => $info) {
            $fc = $this->weather->forecastForPlace($info['query'], 6);
            $resolved[$key] = $fc
                ? ['ok' => true, 'place' => $fc['place'], 'days' => $fc['days']]
                : ['ok' => false, 'place' => $info['label']];
            if ($wantHourly && $fc) {
                $resolved[$key]['hours'] = $this->weather->hourly($fc['lat'], $fc['lon'], 24) ?: [];
            }
        }

        $savedDates = $this->rememberForecast($scheduleId, $resolved);

        return response()->json([
            'success' => true,
            'data' => [
                'located' => ! empty($resolved),
                'locations' => $resolved,
                'lots' => $lotList,
                // Which dates now have a stored reading, so the board can offer
                // "View saved weather" on exactly those days.
                'savedDates' => $savedDates,
            ],
        ]);
    }

    /**
     * Keep what the forecast said, so a report or the AI technician can look
     * back at the weather a decision was made under. One row per schedule +
     * location + date, overwritten as the forecast changes: the store holds
     * the latest reading for each day rather than a pile of revisions.
     *
     * Best-effort — a farmer reading the forecast should never see an error
     * because writing the copy failed.
     */
    private function rememberForecast(int $scheduleId, array $resolved): array
    {
        $dates = [];
        try {
            $now = now('Asia/Manila');
            foreach ($resolved as $key => $loc) {
                if (empty($loc['ok']) || empty($loc['days'])) {
                    continue;
                }
                // Hours belong to the day they fall on, so the saved day can
                // show its own hour-by-hour rather than the whole fetch.
                $hoursByDate = [];
                foreach ($loc['hours'] ?? [] as $hour) {
                    $d = substr((string) ($hour['time'] ?? ''), 0, 10);
                    if ($d !== '') {
                        $hoursByDate[$d][] = $hour;
                    }
                }

                foreach ($loc['days'] as $day) {
                    $date = $day['date'] ?? null;
                    if (! $date) {
                        continue;
                    }
                    $values = [
                        'place' => $loc['place'] ?? null,
                        'day' => $day,
                        'capturedAt' => $now,
                        'deleteStatus' => 1,
                    ];
                    // Only overwrite the hours when this fetch actually carried
                    // them: the chips ask for days alone, and that must not
                    // erase the hour-by-hour a previous look already stored.
                    if (! empty($hoursByDate[$date])) {
                        $values['hours'] = $hoursByDate[$date];
                    }
                    \App\Models\AsScheduleWeatherDay::updateOrCreate(
                        [
                            'croppingScheduleId' => $scheduleId,
                            'locationKey' => (string) $key,
                            'forecastDate' => $date,
                        ],
                        $values
                    );
                    $dates[$date] = true;
                }
            }
        } catch (\Throwable $e) {
            return array_keys($dates);
        }

        return array_keys($dates);
    }

    /** The saved reading for one date, newest capture per location. */
    public function savedDay(Request $request)
    {
        $scheduleId = (int) $request->query('scheduleId');
        $owned = $scheduleId && \App\Models\AsCroppingSchedule::active()
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
            ->where('id', $scheduleId)
            ->exists();
        if (! $owned) {
            return response()->json(['success' => false, 'message' => 'Not your schedule.'], 403);
        }

        $date = (string) $request->query('date');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['success' => false, 'message' => 'Bad date.'], 422);
        }

        $rows = \App\Models\AsScheduleWeatherDay::where('deleteStatus', 1)
            ->where('croppingScheduleId', $scheduleId)
            ->whereDate('forecastDate', $date)
            ->orderBy('place')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'entries' => $rows->map(fn ($r) => [
                    'place' => $r->place,
                    'day' => $r->day,
                    'hours' => $r->hours ?: [],
                    'capturedAt' => $r->capturedAt?->timezone('Asia/Manila')->format('M j, Y g:ia'),
                ])->all(),
            ],
        ]);
    }
}
