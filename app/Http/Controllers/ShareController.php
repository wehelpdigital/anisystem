<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Public, link-based views of a shared cropping plan — no login required. The
 * link carries the schedule's unguessable `shareToken`, so only someone the
 * owner gave it to can open it. Includes correct Open Graph meta so social /
 * chat / email previews render nicely.
 */
class ShareController extends Controller
{
    /** The whole plan: crop, lots, workers, and the day-by-day timeline. */
    public function schedule(string $token)
    {
        $schedule = $this->resolve($token);
        $schedule->load(['lots', 'workers', 'activities.lots', 'activities.workers']);

        $aliases = $this->privacyAliases($schedule);
        $lotDayZero = $this->lotDayZero($schedule);
        $timeline = $this->groupedTimeline($schedule, $lotDayZero, $aliases['lots']);

        return view('share.schedule', [
            'schedule' => $schedule,
            'timeline' => $timeline,
            'lotDayZero' => $lotDayZero,
            'workerAlias' => $aliases['workers'],
            'lotAlias' => $aliases['lots'],
            'ogDescription' => $this->scheduleSummary($schedule),
        ]);
    }

    /** A single activity from a shared plan. */
    public function activity(string $token, int $activityId)
    {
        $schedule = $this->resolve($token);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $activityId)
            ->with(['lots', 'workers'])
            ->first();

        if (! $activity) {
            abort(404);
        }

        $schedule->load(['activities', 'lots', 'workers']);   // for day-zero anchor + aliases
        $lotDayZero = $this->lotDayZero($schedule);
        $aliases = $this->privacyAliases($schedule);

        return view('share.activity', [
            'schedule' => $schedule,
            'activity' => $activity,
            'dasLabels' => $this->activityDasLabels($activity, $lotDayZero, $schedule->dayType ?: 'DAS', $aliases['lots']),
            'workerAlias' => $aliases['workers'],
            'lotAlias' => $aliases['lots'],
            'ogDescription' => $this->activitySummary($activity),
        ]);
    }

    /** One calendar day from a shared plan — every activity on that date. */
    public function day(string $token, string $date)
    {
        $schedule = $this->resolve($token);

        try {
            $carbon = Carbon::parse($date)->startOfDay();
        } catch (\Throwable $e) {
            abort(404);
        }
        $target = $carbon->format('Y-m-d');

        $schedule->load(['lots', 'workers', 'activities.lots', 'activities.workers']);
        $lotDayZero = $this->lotDayZero($schedule);
        $dayType = $schedule->dayType ?: 'DAS';
        $aliases = $this->privacyAliases($schedule);

        $rows = [];
        foreach ($schedule->activities as $a) {
            if ($a->isHidden || ! $a->targetDate) {
                continue;
            }
            $start = Carbon::parse($a->targetDate)->format('Y-m-d');
            $end = $a->targetEndDate ? Carbon::parse($a->targetEndDate)->format('Y-m-d') : $start;
            if ($target < $start || $target > $end) {
                continue;
            }
            $rows[] = [
                'activity' => $a,
                'das' => $this->activityDasLabels($a, $lotDayZero, $dayType, $aliases['lots']),
                'isStart' => $target === $start,
            ];
        }

        usort($rows, fn ($x, $y) => (int) $x['activity']->sequenceOrder <=> (int) $y['activity']->sequenceOrder);

        return view('share.day', [
            'schedule' => $schedule,
            'date' => $carbon,
            'rows' => $rows,
            'workerAlias' => $aliases['workers'],
            'lotAlias' => $aliases['lots'],
            'ogDescription' => $this->daySummary($schedule, $carbon, count($rows)),
        ]);
    }

    // ------------------------------------------------------------------

    private function daySummary(AsCroppingSchedule $schedule, Carbon $date, int $count): string
    {
        $bits = array_filter([
            $schedule->cropType,
            $count . ' ' . Str::plural('activity', $count) . ' on ' . $date->format('M j, Y'),
        ]);

        return trim($schedule->title . ' — ' . implode(' · ', $bits)) . '.';
    }

    private function resolve(string $token): AsCroppingSchedule
    {
        $schedule = AsCroppingSchedule::active()->where('shareToken', $token)->first();
        if (! $schedule) {
            abort(404);
        }

        return $schedule;
    }

    /** Effective day-0 date per lot: manual lot date, or earliest day-zero activity. */
    private function lotDayZero(AsCroppingSchedule $schedule): array
    {
        $map = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $map[$lot->id] = Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (! $a->isDayZero || ! $a->targetDate) {
                continue;
            }
            $d = Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (! isset($map[$lot->id]) || $d->lt($map[$lot->id])) {
                    $map[$lot->id] = $d;
                }
            }
        }

        return $map;
    }

    /** DAS/DAT labels for an activity's lots, e.g. "Lot 1 · DAS 21" (aliased). */
    private function activityDasLabels(AsScheduleActivity $activity, array $lotDayZero, string $dayType, array $lotAlias = []): array
    {
        if (! $activity->targetDate) {
            return [];
        }
        $target = Carbon::parse($activity->targetDate)->startOfDay();
        $labels = [];
        foreach ($activity->lots as $lot) {
            $anchor = $lotDayZero[$lot->id] ?? null;
            $das = $anchor ? (int) $anchor->copy()->startOfDay()->diffInDays($target, false) : null;
            $labels[] = [
                'lot' => $lotAlias[$lot->id] ?? $lot->lotName,
                'das' => $das === null ? null : $dayType . ' ' . $das,
            ];
        }

        return $labels;
    }

    /** Timeline grouped by date, each activity carrying its DAS labels. */
    private function groupedTimeline(AsCroppingSchedule $schedule, array $lotDayZero, array $lotAlias = []): array
    {
        $dayType = $schedule->dayType ?: 'DAS';
        $sorted = $schedule->activities
            ->filter(fn ($a) => ! $a->isHidden)
            ->sortBy(fn ($a) => ($a->targetDate ? Carbon::parse($a->targetDate)->format('Y-m-d') : 'ZZZZ')
                . '|' . str_pad((string) (int) $a->sequenceOrder, 6, '0', STR_PAD_LEFT));

        $groups = [];
        foreach ($sorted as $a) {
            $key = $a->targetDate ? Carbon::parse($a->targetDate)->format('Y-m-d') : 'no-date';
            $groups[$key][] = [
                'activity' => $a,
                'das' => $this->activityDasLabels($a, $lotDayZero, $dayType, $lotAlias),
            ];
        }

        return $groups;
    }

    /**
     * Privacy aliases for a public share: real worker/lot names are replaced by
     * "Worker 1/2/3…" and "Lot 1/2/3…" so a shared plan never leaks who works a
     * grower's fields or what their parcels are called. Stable ordering (lot by
     * id, worker by priority then id) so the same entity keeps the same number.
     *
     * @return array{workers: array<int,string>, lots: array<int,string>}
     */
    private function privacyAliases(AsCroppingSchedule $schedule): array
    {
        $lots = [];
        foreach ($schedule->lots->sortBy('id')->values() as $i => $lot) {
            $lots[$lot->id] = 'Lot ' . ($i + 1);
        }

        $workers = [];
        $ordered = $schedule->workers->sortBy(fn ($w) => [(int) ($w->priority ?? 999), (int) $w->id])->values();
        foreach ($ordered as $i => $worker) {
            $workers[$worker->id] = 'Worker ' . ($i + 1);
        }

        return ['workers' => $workers, 'lots' => $lots];
    }

    private function scheduleSummary(AsCroppingSchedule $schedule): string
    {
        $bits = array_filter([
            $schedule->cropType,
            $schedule->lots->count() . ' ' . Str::plural('lot', $schedule->lots->count()),
            $schedule->activities->count() . ' ' . Str::plural('activity', $schedule->activities->count()),
        ]);

        return 'A cropping plan on AniSystem — ' . implode(' · ', $bits) . '.';
    }

    private function activitySummary(AsScheduleActivity $activity): string
    {
        $when = $activity->targetDate ? Carbon::parse($activity->targetDate)->format('M j, Y') : 'unscheduled';
        $lots = $activity->lots->pluck('lotName')->implode(', ');

        return trim($when . ($lots ? ' · ' . $lots : '')) . ' — shared from AniSystem.';
    }
}
