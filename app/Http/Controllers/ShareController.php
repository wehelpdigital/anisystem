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

        $lotDayZero = $this->lotDayZero($schedule);
        $timeline = $this->groupedTimeline($schedule, $lotDayZero);

        return view('share.schedule', [
            'schedule' => $schedule,
            'timeline' => $timeline,
            'lotDayZero' => $lotDayZero,
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

        $schedule->load('activities');   // for day-zero anchor
        $lotDayZero = $this->lotDayZero($schedule);

        return view('share.activity', [
            'schedule' => $schedule,
            'activity' => $activity,
            'dasLabels' => $this->activityDasLabels($activity, $lotDayZero, $schedule->dayType ?: 'DAS'),
            'ogDescription' => $this->activitySummary($activity),
        ]);
    }

    // ------------------------------------------------------------------

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

    /** DAS/DAT labels for an activity's lots, e.g. "Lot A · DAS 21". */
    private function activityDasLabels(AsScheduleActivity $activity, array $lotDayZero, string $dayType): array
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
                'lot' => $lot->lotName,
                'das' => $das === null ? null : $dayType . ' ' . $das,
            ];
        }

        return $labels;
    }

    /** Timeline grouped by date, each activity carrying its DAS labels. */
    private function groupedTimeline(AsCroppingSchedule $schedule, array $lotDayZero): array
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
                'das' => $this->activityDasLabels($a, $lotDayZero, $dayType),
            ];
        }

        return $groups;
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
