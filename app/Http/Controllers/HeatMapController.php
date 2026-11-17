<?php

namespace App\Http\Controllers;

use App\Support\FarmLots;
use App\Support\FieldHours;
use App\Support\HeatForecast;
use App\Support\HeatGuide;
use App\Support\HeatYear;
use App\Support\PlaceSearch;
use App\Support\Region;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Heat Map Forecast (2026-11-17, the owner: "create me another feature under the your tools and this feature is
 * called heat map forecast ... first there's a wizard like the other modules that asks which lot to select and or
 * add new one if there's nothing in there, or link to the global maps. then next page is the google map showing your
 * lot area, with colors based on temperature, legend, and a draggable opacity for the colors ... another tab to show
 * the forecasted temperature and guides based on the future dates, as well as a play button in the map").
 *
 * The page (heat-map.index) asks for a lot of Global Lots (lots only since 2026-11-18, the owner: a lot carries its own
 * place on the map, its pin or its boundary), then paints the week's
 * temperatures around it on a Google map it turns, tilts and zooms like any Google map, reads the spot's own week
 * day by day and hour by hour for the work (App\Support\FieldHours, HeatGuide), and shows the year there month by
 * month (App\Support\HeatYear). The numbers come from App\Support\HeatForecast and the climate shelf.
 *
 * Libre + Anee and up (tier key heatMap, 2026-11-17, the owner: "only available on paid subscriptions just like the
 * other tools"); a worker's login has its own switch for it (WorkerTools 'heatmap'). No credits.
 */
class HeatMapController extends Controller
{
    public function page()
    {
        $this->gate();

        return view('heat-map.index');
    }

    /**
     * The week around a lot (lot=ID): where it is on the map, the heat square the map
     * plays, and the spot's own week: each day's heat reading and notes (HeatGuide) and the good hours for the
     * work (FieldHours). Never tied to what grows on the lot (the owner, 2026-11-17).
     */
    public function forecast(Request $request)
    {
        $this->gate();
        [$place, $fail] = $this->place($request);
        if ($fail) {
            return $fail;
        }
        $week = HeatForecast::around((float) $place['lat'], (float) $place['lng']);
        if (! $week) {
            return $this->json(false, 'The forecast could not be reached just now. Please try again in a minute.', ['place' => $place], 503);
        }

        $work = FieldHours::week($week['lot']);
        $days = [];
        foreach ((array) ($week['lot']['days'] ?? []) as $d) {
            $days[] = $d + ['guide' => HeatGuide::day($d, HeatForecast::dayHours($week['lot'], $d['date'])), 'work' => $work[$d['date']] ?? []];
        }
        $week['lot']['days'] = $days;

        return $this->json(true, 'ok', [
            'place' => $place,
            'week' => $week,
            'work' => array_map(fn ($w) => ['name' => $w[0], 'icon' => $w[1]], FieldHours::WORK),
            'today' => now('Asia/Manila')->toDateString(),
            'nowHour' => (int) now('Asia/Manila')->format('G'),
        ]);
    }

    /**
     * The year at the place (2026-11-17, the owner: "a graphical heatmap presentation as well for months to see in
     * your area"): twenty years of its weather month by month and week by week, and how each month usually suits
     * the work (HeatYear). A place not on the climate shelf yet answers pending while its years are fetched.
     */
    public function year(Request $request)
    {
        $this->gate();
        [$place, $fail] = $this->place($request);
        if ($fail) {
            return $fail;
        }
        $year = HeatYear::of((float) $place['lat'], (float) $place['lng'], $place['label'] ?: $place['name']);

        return $this->json(true, 'ok', ['place' => ['name' => $place['name'], 'how' => $place['how']], 'year' => $year,
            'work' => array_map(fn ($w) => ['name' => $w[0], 'icon' => $w[1]], HeatYear::WORK), 'month' => (int) now('Asia/Manila')->month]);
    }

    /** The lot a request names, one of the farm's, and where it is: [place, null] or [null, refusal]. Lots only since 2026-11-18. */
    private function place(Request $request): array
    {
        $lotId = (int) $request->query('lot');
        if ($lotId > 0) {
            $farmLot = FarmLots::one((int) WorkerContext::effectiveOwnerId(), $lotId);
            if (! $farmLot) {
                return [null, $this->json(false, 'That lot is not in your lots any more. Pick another one.', [], 404)];
            }
            $place = $this->lotPlace($farmLot);

            return $place ? [$place, null] : [null, $this->json(false, 'We could not find ' . $farmLot['name'] . ' on the map. Set its pin, or check its town and province.', ['noPlace' => true], 422)];
        }
        return [null, $this->json(false, 'Pick a lot first.', [], 422)];
    }

    /** A place to search when a lot has no pin: "Bantug, Science City of Munoz, Nueva Ecija". */
    private function lotPlace(array $lot): ?array
    {
        $ring = [];
        foreach ((array) ($lot['boundary'] ?? []) as $p) {
            if (is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1])) {
                $ring[] = [(float) $p[0], (float) $p[1]];
            }
        }
        $pin = isset($lot['pin']['lat'], $lot['pin']['lng']) ? ['lat' => (float) $lot['pin']['lat'], 'lng' => (float) $lot['pin']['lng']] : null;
        $base = ['kind' => 'lot', 'id' => (int) $lot['id'], 'name' => (string) $lot['name'], 'label' => (string) ($lot['where'] ?? $lot['place'] ?? ''),
            'ring' => count($ring) >= 3 ? $ring : null, 'pin' => $pin, 'areaHa' => $lot['areaHa'] ?? null];
        if (count($ring) >= 3) {
            return $base + ['lat' => array_sum(array_column($ring, 0)) / count($ring), 'lng' => array_sum(array_column($ring, 1)) / count($ring), 'how' => 'boundary'];
        }
        if ($pin) {
            return $base + $pin + ['how' => 'pin'];
        }
        $town = trim((string) preg_replace('/\s*\(Capital\)\s*$/i', '', (string) ($lot['town'] ?? '')));
        $prov = trim((string) ($lot['province'] ?? ''));
        $brgy = trim((string) ($lot['barangay'] ?? ''));
        $tail = Region::ph() ? ', Philippines' : '';
        $tries = array_values(array_filter([
            $brgy && $town ? [$brgy . ', ' . $town . ', ' . $prov . $tail, 'barangay'] : null,
            $town ? [$town . ', ' . $prov . $tail, 'town'] : null,
            $prov ? [$prov . $tail, 'province'] : null,
        ]));
        foreach ($tries as [$q, $how]) {
            $hit = rescue(fn () => PlaceSearch::find($q)[0] ?? null, null, false);
            if ($hit && isset($hit['lat'], $hit['lng'])) {
                return $base + ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lng'], 'how' => $how];
            }
        }

        return null;
    }

    /** Libre + Anee and up (2026-11-17, the owner). */
    private function gate(): void
    {
        if (! Tier::farmCan('heatMap')) {
            Tier::farmDenyFor('heatMap', 'The Heat Map Forecast comes with {plan}. See the week\'s heat over your lot, hour by hour, and what to do on the hottest days.');
        }
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
