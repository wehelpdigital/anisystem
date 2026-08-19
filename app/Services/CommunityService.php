<?php

namespace App\Services;

use App\Models\AsCroppingSchedule;
use App\Models\CommunityComment;
use App\Models\CommunityRating;

/**
 * What the Community needs to know about a crop plan: whether it is worth
 * publishing, what people have said about it, and how its days are counted.
 *
 * It was once a give-to-get space, and this class held the toll: hasPublished()
 * asked whether you had shared anything, browse() and decorate() built the
 * gallery of other people's plans. The owner opened the doors to everyone and
 * the Shared Plans page went with them, so all three went too.
 */
class CommunityService
{
    /** A plan needs at least this much substance before it can go public. */
    public const MIN_ACTIVITIES = 6;
    public const MIN_LOTS = 1;

    /**
     * Why this schedule can or cannot be published.
     *
     * @return array{ok:bool, activities:int, lots:int, reasons:array<int,string>}
     */
    public function publishEligibility(AsCroppingSchedule $schedule): array
    {
        $activities = $schedule->activities()->count();
        $lots = $schedule->lots()->count();

        $reasons = [];
        if ($activities < self::MIN_ACTIVITIES) {
            $need = self::MIN_ACTIVITIES - $activities;
            $reasons[] = "Add {$need} more " . ($need === 1 ? 'activity' : 'activities')
                . ' — a plan needs at least ' . self::MIN_ACTIVITIES . ' to be worth following.';
        }
        if ($lots < self::MIN_LOTS) {
            $reasons[] = 'Add at least one lot, so people can see what the plan was grown on.';
        }

        return [
            'ok' => $reasons === [],
            'activities' => $activities,
            'lots' => $lots,
            'reasons' => $reasons,
        ];
    }

    /**
     * Comments for a plan as a two-level thread: top-level entries, each with
     * its replies attached in order.
     */
    public function thread(int $scheduleId)
    {
        $all = CommunityComment::active()
            ->where('croppingScheduleId', $scheduleId)
            ->with('author')
            ->orderBy('id')
            ->get();

        $byParent = $all->whereNotNull('parentId')->groupBy('parentId');

        return $all->whereNull('parentId')->values()->map(function ($comment) use ($byParent) {
            $comment->setRelation('replies', $byParent->get($comment->id, collect())->values());

            return $comment;
        });
    }

    /** Rating breakdown for a plan: average, total, and a 5..1 histogram. */
    public function ratingSummary(int $scheduleId): array
    {
        $rows = CommunityRating::active()->where('croppingScheduleId', $scheduleId)->get();
        $histogram = array_fill_keys([5, 4, 3, 2, 1], 0);

        foreach ($rows as $row) {
            if (isset($histogram[$row->rating])) {
                $histogram[$row->rating]++;
            }
        }

        return [
            'average' => $rows->isEmpty() ? null : round($rows->avg('rating'), 1),
            'count' => $rows->count(),
            'histogram' => $histogram,
        ];
    }

    /**
     * Effective day-0 date per lot: the lot's manual date, else the earliest
     * day-zero activity that covers it. Powers the DAS/DAT labels on the plan.
     * (The plan's `lots` and `activities.lots` must already be loaded.)
     *
     * @return array<int,\Illuminate\Support\Carbon>
     */
    public function lotDayZero(AsCroppingSchedule $plan): array
    {
        $map = [];
        foreach ($plan->lots as $lot) {
            if ($lot->dayZeroDate) {
                $map[$lot->id] = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($plan->activities as $a) {
            if (! $a->isDayZero || ! $a->targetDate) {
                continue;
            }
            $d = \Illuminate\Support\Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (! isset($map[$lot->id]) || $d->lt($map[$lot->id])) {
                    $map[$lot->id] = $d;
                }
            }
        }

        return $map;
    }

    /**
     * "Lot A · DAS 21" labels for one activity's lots.
     *
     * @param  array<int,\Illuminate\Support\Carbon>  $dayZero
     * @return array<int,string>
     */
    public function dasLabels($activity, array $dayZero, string $dayType): array
    {
        if (! $activity->targetDate) {
            return [];
        }
        $target = \Illuminate\Support\Carbon::parse($activity->targetDate)->startOfDay();
        $labels = [];
        foreach ($activity->lots as $lot) {
            $anchor = $dayZero[$lot->id] ?? null;
            if ($anchor) {
                $das = (int) $anchor->copy()->startOfDay()->diffInDays($target, false);
                $labels[] = $lot->lotName . ' · ' . $dayType . ' ' . $das;
            } else {
                $labels[] = $lot->lotName;
            }
        }

        return $labels;
    }
}
