<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Support\CropCatalog;
use App\Support\CropStages;
use App\Support\FarmLots;
use App\Support\HeatForecast;
use App\Support\HeatGuide;
use App\Support\LotCalendar;
use App\Support\LotSync;
use App\Support\MapAccess;
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
 * The page (heat-map.index) asks for a lot of Global Lots or a saved map of Global Maps, then paints the week's
 * temperatures around it on a Google map it turns, tilts and zooms like any Google map, and reads the lot's own
 * week day by day with what to do (App\Support\HeatGuide). The numbers come from App\Support\HeatForecast.
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
     * The saved maps of Global Maps that have something drawn on them, newest first: a map can stand in for a lot
     * (the owner: "or link to the global maps").
     */
    public function maps()
    {
        $this->gate();
        $rows = MapAccess::shelf()->orderByDesc('updated_at')->orderByDesc('id')->limit(60)->get();
        $byId = $rows->keyBy('id');
        $out = [];
        foreach (MapAccess::rows($rows) as $r) {
            $save = $byId->get($r['id']);
            $geo = $save ? self::shapes((string) $save->objects) : null;
            if (! $geo) {
                continue;
            }
            $out[] = ['id' => $r['id'], 'title' => $r['title'], 'when' => $r['when'], 'by' => $r['mine'] ? null : $r['by'], 'season' => $r['season'],
                'count' => count($geo['shapes']), 'thumbUrl' => $r['thumbUrl'], 'mapUrl' => $r['url']];
        }

        return $this->json(true, 'ok', ['maps' => $out, 'mapsUrl' => route('maps.page')]);
    }

    /**
     * The week around a lot (lot=ID) or a saved map (map=ID): where it is on the map, the heat square the map
     * plays, the lot's own week with a guide for each day, and the crop's stage on each date when the lot is in
     * an open season.
     */
    public function forecast(Request $request)
    {
        $this->gate();
        $lotId = (int) $request->query('lot');
        $mapId = (int) $request->query('map');
        $place = null;
        $farmLot = null;
        if ($lotId > 0) {
            $farmLot = FarmLots::one((int) WorkerContext::effectiveOwnerId(), $lotId);
            if (! $farmLot) {
                return $this->json(false, 'That lot is not in your lots any more. Pick another one.', [], 404);
            }
            $place = $this->lotPlace($farmLot);
            if (! $place) {
                return $this->json(false, 'We could not find ' . $farmLot['name'] . ' on the map. Set its pin, or check its town and province.', ['noPlace' => true], 422);
            }
        } elseif ($mapId > 0) {
            $save = MapAccess::viewable($mapId);
            $geo = $save ? self::shapes((string) $save->objects) : null;
            if (! $save || ! $geo) {
                return $this->json(false, $save ? 'That map has nothing drawn on it yet. Draw your field on it in Global Maps first.' : 'That map is not open to you.', [], $save ? 422 : 404);
            }
            $place = ['kind' => 'map', 'id' => (int) $save->id, 'name' => (string) $save->title, 'label' => 'A map from Global Maps', 'lat' => $geo['center'][0], 'lng' => $geo['center'][1],
                'how' => 'map', 'bounds' => $geo['bounds'], 'shapes' => $geo['shapes'], 'ring' => null, 'pin' => null];
        } else {
            return $this->json(false, 'Pick a lot or a map first.', [], 422);
        }

        $week = HeatForecast::around((float) $place['lat'], (float) $place['lng']);
        if (! $week) {
            return $this->json(false, 'The forecast could not be reached just now. Please try again in a minute.', ['place' => $place], 503);
        }

        $grow = $farmLot ? rescue(fn () => $this->growing($farmLot), null, false) : null;
        $days = [];
        foreach ((array) ($week['lot']['days'] ?? []) as $d) {
            $g = $grow ? ($grow['stages'][$d['date']] ?? null) : null;
            $days[] = $d + ['guide' => HeatGuide::day($d, HeatForecast::dayHours($week['lot'], $d['date']), $g ? $grow['crop'] + ['stage' => $g['stage'], 'day' => $g['day'], 'counter' => $g['counter']] : null),
                'grow' => $g];
        }
        $week['lot']['days'] = $days;

        return $this->json(true, 'ok', [
            'place' => $place,
            'week' => $week,
            'grow' => $grow ? ['season' => $grow['season'], 'lot' => $grow['lotName'], 'crop' => $grow['crop']['label'], 'cropKey' => $grow['crop']['crop'], 'icon' => $grow['crop']['icon']] : null,
            'today' => now('Asia/Manila')->toDateString(),
        ]);
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

    /**
     * What grows on the lot now: its season lot in an open season (linked by farmLotId, or by its name), the crop,
     * and the crop's stage on each date of the week, counted the way the whole app counts (LotCalendar).
     */
    private function growing(array $farmLot): ?array
    {
        $owner = (int) WorkerContext::effectiveOwnerId();
        $seasons = AsCroppingSchedule::query()->forClient($owner)->where('deleteStatus', 1)
            ->whereNotIn('status', [AsCroppingSchedule::STATUS_COMPLETED, AsCroppingSchedule::STATUS_ARCHIVED])
            ->orderByDesc('id')->limit(30)->get(['id', 'title', 'cropType', 'status', 'dayType']);
        if ($seasons->isEmpty()) {
            return null;
        }
        $key = fn ($s) => trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $s)));
        $linked = LotSync::ready();
        $lots = AsScheduleLot::whereIn('croppingScheduleId', $seasons->pluck('id'))->where('deleteStatus', 1)->whereNull('harvestedAt')->get()
            ->filter(fn ($l) => ($linked && (int) ($l->farmLotId ?? 0) === (int) $farmLot['id'])
                || (int) ($farmLot['lotId'] ?? 0) === (int) $l->id
                || (! ($linked && $l->farmLotId) && $key($l->lotName) !== '' && $key($l->lotName) === $key($farmLot['name'])))
            ->sortByDesc('croppingScheduleId');
        $dates = [];
        $today = now('Asia/Manila')->startOfDay();
        for ($i = 0; $i < HeatForecast::DAYS; $i++) {
            $dates[] = $today->copy()->addDays($i);
        }
        foreach ($lots as $lot) {
            $s = $seasons->firstWhere('id', $lot->croppingScheduleId);
            $crop = CropStages::normalize($lot->crop ?: $s?->cropType);
            if (! $s || ! $crop) {
                continue;
            }
            $s->load(['lots', 'activities' => fn ($q) => $q->where(fn ($w) => $w->where('isDayZero', 1)->orWhere('isTransplant', 1))->with('lots')]);
            [$zero, $trans] = LotCalendar::effectiveAnchors($s);
            $stages = [];
            foreach ($dates as $on) {
                $age = LotCalendar::ageOf($lot, $on, $zero[$lot->id] ?? null, $trans[$lot->id] ?? null);
                $day = $lot->stageDay($age);
                $st = $day !== null ? CropStages::stageFor($crop, $day, $age['counter'] ?? null, $lot->maturityDays()) : null;
                if ($st) {
                    $stages[$on->toDateString()] = ['stage' => $st['label'], 'day' => (int) ($age['day'] ?? $day), 'counter' => (string) ($age['counter'] ?? '')];
                }
            }
            if (! $stages) {
                continue;
            }
            $label = CropCatalog::label($crop);
            $word = mb_strtolower(trim((string) preg_split('/[,(]/u', $label)[0])) ?: 'crop';

            return [
                'season' => (string) $s->title,
                'lotName' => (string) $lot->lotName,
                'crop' => ['crop' => $crop, 'word' => $word, 'label' => $label, 'icon' => CropStages::icon($crop), 'lot' => (string) $lot->lotName],
                'stages' => $stages,
            ];
        }

        return null;
    }

    /**
     * A saved map's shapes as the heat map draws them: closed outlines, lines and pins, each with its color, and
     * the box around them all. Null when nothing on it has a place.
     *
     * @return array{shapes: list<array>, bounds: array{0: float, 1: float, 2: float, 3: float}, center: array{0: float, 1: float}}|null
     */
    public static function shapes(string $json): ?array
    {
        $objects = json_decode($json, true);
        if (! is_array($objects)) {
            return null;
        }
        $shapes = [];
        $s = 90.0;
        $n = -90.0;
        $w = 180.0;
        $e = -180.0;
        foreach ($objects as $o) {
            if (! is_array($o) || count($shapes) >= 300) {
                continue;
            }
            $kind = (string) ($o['kind'] ?? '');
            $pts = [];
            foreach ((array) ($o['points'] ?? []) as $p) {
                if (is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1]) && abs((float) $p[0]) <= 90 && abs((float) $p[1]) <= 180) {
                    $pts[] = [round((float) $p[0], 6), round((float) $p[1], 6)];
                }
            }
            if (! $pts) {
                continue;
            }
            // An upright box is kept as its two corners; a turned one as its four (MapAccess, the map's own rule).
            if ($kind === 'rect' && count($pts) === 2) {
                [$a, $b] = $pts;
                $pts = [[min($a[0], $b[0]), min($a[1], $b[1])], [min($a[0], $b[0]), max($a[1], $b[1])], [max($a[0], $b[0]), max($a[1], $b[1])], [max($a[0], $b[0]), min($a[1], $b[1])]];
            }
            $type = match (true) {
                in_array($kind, ['area', 'rect', 'sketch'], true) && count($pts) >= 3 => 'closed',
                $kind === 'pin' || count($pts) === 1 => 'pin',
                default => 'line',
            };
            if ($type === 'pin' && $kind === 'text') {
                continue;
            }
            // A long pen stroke keeps every few points: the outline, not the ink.
            if (count($pts) > 240) {
                $step = (int) ceil(count($pts) / 240);
                $pts = array_values(array_filter($pts, fn ($_, $i) => $i % $step === 0, ARRAY_FILTER_USE_BOTH));
            }
            foreach ($pts as [$la, $ln]) {
                $s = min($s, $la);
                $n = max($n, $la);
                $w = min($w, $ln);
                $e = max($e, $ln);
            }
            $color = (string) ($o['color'] ?? '');
            $shapes[] = ['type' => $type, 'color' => preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : '#f5c518', 'points' => $pts,
                'label' => isset($o['label']) && is_string($o['label']) ? mb_substr(trim($o['label']), 0, 60) : null];
        }
        if (! $shapes || $n < $s) {
            return null;
        }

        return ['shapes' => $shapes, 'bounds' => [$s, $w, $n, $e], 'center' => [($s + $n) / 2, ($w + $e) / 2]];
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
