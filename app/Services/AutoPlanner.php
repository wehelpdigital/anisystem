<?php

namespace App\Services;

use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleWorker;
use App\Support\AutoBook;
use Illuminate\Support\Carbon;

/**
 * Anee Auto's day picker (2026-10-12).
 *
 * Every task is held to the season by an anchor (the day the crop goes in
 * the field, the day the seedbed is sown, or the day the plan started) and
 * a number of days from it, with a window it may move in. Each time the plan
 * is read again (a "not today", a done task that moved an anchor, the
 * morning run with a fresh forecast) every open task is placed on the best
 * day of its window:
 *
 *   - never before today, or before the day the farmer said "not until";
 *   - on a day whose weather suits the work: no spraying into rain or wind,
 *     no fertilizer into a downpour, no field work in the wet;
 *   - on a day the workers can carry it: each worker has two half days, off
 *     on their weekdays and dates off; critical work goes first;
 *   - as close to the agronomic day as those allow, later before earlier.
 *
 * A task whose window has passed is due today and says it is late. Nothing
 * here asks the model; it is arithmetic, so it runs every morning for free.
 */
class AutoPlanner
{
    /** Spray: no rain this likely, no wind this strong in the morning. */
    private const SPRAY_POP = 50;
    private const SPRAY_WIND = 15;
    /** Fertilizer: not into a downpour. */
    private const FERT_POP = 75;
    private const FERT_MM = 20;
    /** Field work: dry enough. */
    private const DRY_POP = 60;
    private const DRY_MM = 10;

    private const SKILL_FOR = [
        'herbicide' => 'spray', 'insecticide' => 'spray', 'fungicide' => 'spray', 'foliar' => 'spray',
        'fertilizer' => 'broadcast_granulars', 'land_prep' => 'operate_machine',
    ];

    public function __construct(private WeatherService $weather)
    {
    }

    /** The day an anchor stands on for a lot. */
    public static function anchorDate(AsAutoSeason $auto, ?AsAutoLot $al, string $anchor): Carbon
    {
        $start = ($auto->generatedAt ?? now())->copy()->timezone('Asia/Manila')->startOfDay();
        $field = $al?->fieldDate ? Carbon::parse($al->fieldDate->toDateString(), 'Asia/Manila') : $start;

        return match ($anchor) {
            'sow' => $al?->sowDate ? Carbon::parse($al->sowDate->toDateString(), 'Asia/Manila') : $field,
            'start' => $start,
            default => $field,
        };
    }

    /**
     * Place every open task. Returns the tasks that moved, with from and to,
     * so Anee can say why.
     *
     * @return array<int, array{id:int,title:string,from:?string,to:string,why:string,lotId:?int}>
     */
    public function plan(AsAutoSeason $auto): array
    {
        $tz = 'Asia/Manila';
        $today = now($tz)->startOfDay();
        $lots = AsAutoLot::where('autoId', $auto->id)->get()->keyBy('lotId');
        $lotRows = AsScheduleLot::whereIn('id', $lots->keys())->get()->keyBy('id');
        $workers = self::people($auto);
        $sky = $this->skies($lotRows);

        $tasks = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')->get();
        // Work already given out today and after, by done tasks, does not count against capacity: those are finished.
        $load = [];   // date => [workerId => half days taken]
        $crewLoad = [];   // date => half days taken on a farm with no workers listed

        // Critical first, then the agronomic day, then the plan's own order.
        $rows = $tasks->map(function (AsAutoTask $t) use ($auto, $lots) {
            $al = $t->lotId ? $lots->get($t->lotId) : null;
            $base = self::anchorDate($auto, $al, $t->anchor ?: 'field')->addDays((int) $t->offsetDays);

            return ['t' => $t, 'base' => $base];
        })->sort(function ($a, $b) {
            return [-(int) $a['t']->critical, $a['base']->timestamp, (int) $a['t']->sortOrder, $a['t']->id] <=> [-(int) $b['t']->critical, $b['base']->timestamp, (int) $b['t']->sortOrder, $b['t']->id];
        })->values();

        $moved = [];
        foreach ($rows as $r) {
            /** @var AsAutoTask $t */
            $t = $r['t'];
            $base = $r['base'];
            $lo = $base->copy()->subDays((int) $t->windowBefore);
            $hi = $base->copy()->addDays(max(0, (int) $t->windowAfter));
            $floor = $today->copy();
            if ($t->notBefore && Carbon::parse($t->notBefore->toDateString(), $tz)->gt($floor)) {
                $floor = Carbon::parse($t->notBefore->toDateString(), $tz);
            }
            $late = $hi->lt($floor);
            if ($lo->lt($floor)) {
                $lo = $floor->copy();
            }
            if ($hi->lt($lo)) {
                $hi = $lo->copy()->addDays($late ? 3 : 0);   // a late task gets a short reach of its own
            }

            $rule = $this->ruleOf($t);
            $daysSky = $sky[$t->lotId ?? 0] ?? $sky[0] ?? [];
            $pick = null;
            $pickWhy = null;
            $fallback = null;
            $short = null;   // a day with the weather right but too few hands, for work that cannot wait
            foreach ($this->candidates($lo, $hi, $late ? $lo : $base) as $day) {
                $d = $day->toDateString();
                $weatherOk = $this->weatherOk($rule, $daysSky[$d] ?? null);
                $crew = $this->crewFor($t, $day, $workers, $load, $crewLoad);
                if ($weatherOk && $crew !== null) {
                    $pick = $day;
                    $pickWhy = $crew;
                    break;
                }
                if ($weatherOk && $t->critical && $short === null) {
                    $short = $day;
                }
                $fallback ??= ['day' => $day, 'weather' => $weatherOk, 'crew' => $crew];
            }
            $note = [];
            if (! $pick && $short) {
                // Critical work goes ahead on its best dry day with whoever is free; the farmer is told it is short handed.
                $pick = $short;
                $pickWhy = $this->crewFor($t, $pick, $workers, $load, $crewLoad, true) ?? [];
                $note['risk'] = 'workers';
            }
            if (! $pick) {
                // Nothing in the window fits: the agronomic day (or the first one we may use), with the reason said.
                $pick = ($fallback['day'] ?? $lo)->copy();
                $pickWhy = $this->crewFor($t, $pick, $workers, $load, $crewLoad, true) ?? [];
                if ($fallback && ! $fallback['weather']) {
                    $note['risk'] = 'weather';
                }
                if ($fallback && $fallback['crew'] === null) {
                    $note['risk'] = isset($note['risk']) ? 'both' : 'workers';
                }
            }
            if ($late) {
                $note['late'] = true;
            }
            $d = $pick->toDateString();
            foreach ((array) $pickWhy as $wid) {
                $load[$d][$wid] = ($load[$d][$wid] ?? 0) + 1;
            }
            if (! $workers->count()) {
                $crewLoad[$d] = ($crewLoad[$d] ?? 0) + max(1, (int) $t->crew);
            }
            if ($sk = $daysSky[$d] ?? null) {
                $note['sky'] = ['pop' => $sk['pop'], 'mm' => $sk['mm'], 'wind' => $sk['wind'], 'emoji' => $sk['emoji']];
            }

            $from = $t->plannedDate?->toDateString();
            if ($from !== $d) {
                $moved[] = ['id' => (int) $t->id, 'title' => $t->title, 'from' => $from, 'to' => $d, 'lotId' => $t->lotId,
                    'why' => $from === null ? 'new' : ($note['risk'] ?? (isset($note['late']) ? 'late' : ($this->weatherOk($rule, $daysSky[$from] ?? null) ? 'workers' : 'weather')))];
            }
            $t->forceFill(['plannedDate' => $d, 'firstDate' => $t->firstDate ?? $d, 'workerIds' => array_values((array) $pickWhy) ?: null, 'planNote' => $note ?: null])->save();
        }
        $auto->forceFill(['plannedAt' => now()])->save();

        return $moved;
    }

    /**
     * The people who do the work: the season's worker cards with their days
     * off, or, on a plan without the Workers module, the helpers the wizard
     * was told about (names and the weekdays they cannot come).
     *
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,off:int[],dates:string[],skills:string[],priority:int,email:?string}>
     */
    public static function people(AsAutoSeason $auto)
    {
        $cards = AsScheduleWorker::active()->where('croppingScheduleId', $auto->croppingScheduleId)->with(['offDays', 'offDates'])->get();
        if ($cards->count()) {
            return $cards->map(fn ($w) => ['id' => (int) $w->id, 'name' => (string) $w->workerName, 'email' => $w->email,
                'off' => $w->offDays->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->all(),
                'dates' => $w->offDates->map(fn ($o) => substr((string) $o->offDate, 0, 10))->all(),
                'skills' => (array) ($w->skills ?? []), 'priority' => (int) $w->priority])->values();
        }

        return collect((array) ($auto->answers['crew'] ?? []))->values()->map(fn ($c, $i) => ['id' => -1 - $i, 'name' => (string) ($c['name'] ?? ('Helper ' . ($i + 1))), 'email' => null,
            'off' => array_map('intval', (array) ($c['off'] ?? [])), 'dates' => [], 'skills' => [], 'priority' => $i + 1]);
    }

    /** Days to try, nearest the agronomic day first, a later day before an earlier one. */
    private function candidates(Carbon $lo, Carbon $hi, Carbon $base): array
    {
        $base = $base->lt($lo) ? $lo->copy() : ($base->gt($hi) ? $hi->copy() : $base->copy());
        $out = [$base->copy()];
        for ($k = 1; $k <= 60; $k++) {
            $later = $base->copy()->addDays($k);
            $earlier = $base->copy()->subDays($k);
            $any = false;
            if ($later->lte($hi)) {
                $out[] = $later;
                $any = true;
            }
            if ($earlier->gte($lo)) {
                $out[] = $earlier;
                $any = true;
            }
            if (! $any) {
                break;
            }
        }

        return $out;
    }

    private function ruleOf(AsAutoTask $t): string
    {
        if ($t->weather && $t->weather !== 'any') {
            return $t->weather;
        }

        return AutoBook::KINDS[$t->kind][2] ?? 'any';
    }

    /** Whether a day's sky suits the work. An unknown day (past the forecast) is taken as fit. */
    private function weatherOk(string $rule, ?array $sky): bool
    {
        if (! $sky || $rule === 'any' || $rule === 'wet') {
            return true;
        }

        return match ($rule) {
            'spray' => $sky['pop'] < self::SPRAY_POP && ($sky['amMm'] ?? 0) < 1 && ($sky['amWind'] === null || $sky['amWind'] < self::SPRAY_WIND),
            'fert' => $sky['pop'] < self::FERT_POP && ($sky['mm'] === null || $sky['mm'] < self::FERT_MM),
            'dry' => $sky['pop'] < self::DRY_POP && ($sky['mm'] === null || $sky['mm'] < self::DRY_MM),
            default => true,
        };
    }

    /**
     * Who can do it that day: the workers off neither by weekday nor by date,
     * with a half day free, the skilled ones first, the least loaded first.
     * Null when too few are free. A farm with no workers listed is the
     * family's: three half days a day.
     */
    private function crewFor(AsAutoTask $t, Carbon $day, $workers, array $load, array $crewLoad, bool $force = false): ?array
    {
        $need = max(1, (int) $t->crew);
        $d = $day->toDateString();
        if (! $workers->count()) {
            return ($crewLoad[$d] ?? 0) + $need <= 3 || $force ? [] : null;
        }
        $dow = (int) $day->dayOfWeek;
        $skill = self::SKILL_FOR[$t->kind] ?? null;
        $free = $workers->filter(fn ($w) => ! in_array($dow, $w['off'], true) && ! in_array($d, $w['dates'], true) && ($load[$d][$w['id']] ?? 0) < 2)
            ->sortBy(fn ($w) => [$skill && in_array($skill, $w['skills'], true) ? 0 : 1, $load[$d][$w['id']] ?? 0, $w['priority']])->values();
        if ($free->count() >= $need) {
            return $free->take($need)->pluck('id')->map(fn ($v) => (int) $v)->all();
        }
        if ($force) {
            return $free->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        return null;
    }

    /**
     * The sky over each lot, day by day: rain chance, rain in mm, and the
     * morning's wind and rain for spraying. One kept reading per place
     * (WeatherService), so lots near each other share it. Keyed by lot id;
     * 0 is the farm's first place, for tasks of no lot.
     *
     * @return array<int, array<string, array{pop:int,mm:?float,amMm:?float,amWind:?int,wind:?int,emoji:string}>>
     */
    public function skies($lotRows): array
    {
        $out = [];
        foreach ($lotRows as $lot) {
            $at = $this->placeOf($lot);
            if (! $at) {
                continue;
            }
            $days = $this->skyAt($at[0], $at[1]);
            if (! $days) {
                continue;
            }
            $out[(int) $lot->id] = $days;
            $out[0] ??= $days;
        }

        return $out;
    }

    /** One place's sky, day by day, from its kept reading. */
    public function skyAt(float $lat, float $lng): array
    {
        $r = $this->weather->reading($lat, $lng);
        if (! $r) {
            return [];
        }
        $days = [];
        $d = (array) ($r['daily'] ?? []);
        foreach ((array) ($d['time'] ?? []) as $i => $iso) {
            $code = (int) ($d['weather_code'][$i] ?? 0);
            $days[$iso] = ['pop' => (int) ($d['precipitation_probability_max'][$i] ?? 0), 'mm' => null, 'amMm' => null, 'amWind' => null, 'wind' => null,
                'tmax' => isset($d['temperature_2m_max'][$i]) ? (int) round($d['temperature_2m_max'][$i]) : null,
                'emoji' => $code >= 95 ? '⛈️' : ($code >= 61 ? '🌧️' : ($code >= 51 ? '🌦️' : ($code >= 3 ? '☁️' : ($code >= 1 ? '⛅' : '☀️'))))];
        }
        $h = (array) ($r['hourly'] ?? []);
        foreach ((array) ($h['time'] ?? []) as $i => $iso) {
            $day = substr($iso, 0, 10);
            if (! isset($days[$day])) {
                continue;
            }
            $hour = (int) substr($iso, 11, 2);
            $mm = (float) ($h['precipitation'][$i] ?? 0);
            $wind = (int) round((float) ($h['wind_speed_10m'][$i] ?? 0));
            $days[$day]['mm'] = round(($days[$day]['mm'] ?? 0) + $mm, 1);
            $days[$day]['wind'] = max($days[$day]['wind'] ?? 0, $wind);
            if ($hour >= 6 && $hour <= 13) {
                $days[$day]['amMm'] = round(($days[$day]['amMm'] ?? 0) + $mm, 1);
                if ($hour <= 10) {
                    $days[$day]['amWind'] = max($days[$day]['amWind'] ?? 0, $wind);
                }
            }
        }

        return $days;
    }

    /** Where a lot is: its pin, or its town and province found once. */
    public function placeOf(AsScheduleLot $lot): ?array
    {
        if ($lot->pinLat && $lot->pinLng) {
            return [(float) $lot->pinLat, (float) $lot->pinLng];
        }
        $words = trim(implode(', ', array_filter([$lot->locTown, $lot->locProvince])));
        if ($words === '') {
            return null;
        }
        $g = $this->weather->geocode($words);

        return $g ? [(float) $g['lat'], (float) ($g['lon'] ?? $g['lng'] ?? 0)] : null;
    }
}
