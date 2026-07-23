<?php

namespace App\Services;

use App\Models\AsCroppingSchedule;
use App\Models\CommunityComment;
use App\Models\CommunityRating;
use Illuminate\Support\Facades\DB;

/**
 * The Community is a give-to-get space: a member browses other people's crop
 * plans only once they have published one of their own, and a plan is only
 * publishable when there is actually something to learn from it.
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

    /** Has this member published anything? Browsing is gated on it. */
    public function hasPublished(int $userId): bool
    {
        return AsCroppingSchedule::active()
            ->forClient($userId)
            ->where('isPublic', 1)
            ->exists();
    }

    /**
     * Published plans, newest first, with their author, rating summary and
     * comment count. Excludes the viewer's own plans by default so the browse
     * page is about learning from other people.
     */
    public function browse(int $viewerId, array $filters = [], bool $includeOwn = false)
    {
        $query = AsCroppingSchedule::active()
            ->where('isPublic', 1)
            ->when(! $includeOwn, fn ($q) => $q->where('anisystemUserId', '!=', $viewerId))
            ->when(filled($filters['q'] ?? null), function ($q) use ($filters) {
                $term = '%' . $filters['q'] . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', $term)
                        ->orWhere('cropType', 'like', $term)
                        ->orWhere('cropVariety', 'like', $term)
                        ->orWhere('publicSummary', 'like', $term)
                        ->orWhere('publicRegion', 'like', $term);
                });
            })
            ->when(filled($filters['crop'] ?? null), fn ($q) => $q->where('cropType', $filters['crop']))
            ->orderByDesc('publishedAt')
            ->orderByDesc('id');

        $plans = $query->with('owner')->get();

        return $this->decorate($plans);
    }

    /** Attach rating + comment counts to a set of plans in two queries. */
    public function decorate($plans)
    {
        $ids = $plans->pluck('id')->all();
        if ($ids === []) {
            return $plans;
        }

        $ratings = CommunityRating::active()
            ->whereIn('croppingScheduleId', $ids)
            ->groupBy('croppingScheduleId')
            ->select('croppingScheduleId', DB::raw('AVG(rating) as avgRating'), DB::raw('COUNT(*) as ratingCount'))
            ->get()
            ->keyBy('croppingScheduleId');

        $comments = CommunityComment::active()
            ->whereIn('croppingScheduleId', $ids)
            ->groupBy('croppingScheduleId')
            ->select('croppingScheduleId', DB::raw('COUNT(*) as commentCount'))
            ->get()
            ->keyBy('croppingScheduleId');

        // Activity counts drive the "N steps" line on each card.
        $activities = DB::table('as_schedule_activities')
            ->join('as_schedule_activity_versions', function ($join) {
                $join->on('as_schedule_activity_versions.id', '=', 'as_schedule_activities.versionId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->whereIn('as_schedule_activities.croppingScheduleId', $ids)
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 0)
            ->groupBy('as_schedule_activities.croppingScheduleId')
            ->select('as_schedule_activities.croppingScheduleId', DB::raw('COUNT(*) as activityCount'))
            ->pluck('activityCount', 'croppingScheduleId');

        foreach ($plans as $plan) {
            $r = $ratings->get($plan->id);
            $plan->avgRating = $r ? round((float) $r->avgRating, 1) : null;
            $plan->ratingCount = $r ? (int) $r->ratingCount : 0;
            $plan->commentCount = (int) (optional($comments->get($plan->id))->commentCount ?? 0);
            $plan->activityCount = (int) ($activities[$plan->id] ?? 0);
        }

        return $plans;
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
}
