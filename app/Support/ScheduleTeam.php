<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\User;
use App\Models\WorkerGrant;
use Illuminate\Support\Collection;

/**
 * The "team" of a cropping schedule for the group chat: the owner plus every
 * worker who holds an active login grant to that owner. Worker grants are
 * boss-wide (a granted worker can already see all of the boss's schedules via
 * WorkerContext), so the roster is the same across the owner's schedules while
 * the messages themselves stay scoped per schedule.
 */
class ScheduleTeam
{
    /** User ids in the team: owner first, then active worker sub-members. */
    public static function memberIds(AsCroppingSchedule $schedule): array
    {
        $ownerId = (int) $schedule->anisystemUserId;

        $workerIds = WorkerGrant::active()
            ->where('bossUserId', $ownerId)
            ->where('status', WorkerGrant::STATUS_ACTIVE)
            ->whereNotNull('workerUserId')
            ->pluck('workerUserId')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge([$ownerId], $workerIds)));
    }

    /** The member User models (active accounts only), owner first. */
    public static function members(AsCroppingSchedule $schedule): Collection
    {
        $ids = self::memberIds($schedule);
        $users = User::whereIn('id', $ids)->where('deleteStatus', 1)->get()->keyBy('id');

        // Preserve owner-first ordering.
        return collect($ids)->map(fn ($id) => $users->get($id))->filter()->values();
    }

    /**
     * True once the owner has provisioned any worker login — an accepted
     * sub-member OR a still-pending invite. Showing the chat as soon as a login
     * is created (or invited) keeps the feature discoverable; the roster itself
     * only lists members who actually have an account.
     */
    public static function hasTeam(AsCroppingSchedule $schedule): bool
    {
        if (count(self::memberIds($schedule)) > 1) {
            return true;
        }

        return WorkerGrant::active()
            ->where('bossUserId', (int) $schedule->anisystemUserId)
            ->whereIn('status', [WorkerGrant::STATUS_PENDING, WorkerGrant::STATUS_ACTIVE])
            ->exists();
    }

    /** Whether a user may take part in this schedule's team chat. */
    public static function canAccess(AsCroppingSchedule $schedule, ?int $userId): bool
    {
        return $userId !== null && in_array((int) $userId, self::memberIds($schedule), true);
    }
}
