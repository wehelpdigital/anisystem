<?php

namespace App\Support;

use App\Models\WorkerGrant;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the "which farm am I in" context for a logged-in worker (#25).
 *
 * A worker can hold active grants from several bosses. The session key
 * `activeBossId` records the one they're currently viewing; everything schedule
 * related is scoped to that boss instead of the worker's own (empty) account.
 * Owners with no grants simply operate as themselves.
 */
class WorkerContext
{
    /** All active grants for the current user (their farms). */
    public static function grants()
    {
        $uid = (int) Auth::id();
        if (! $uid) {
            return collect();
        }

        return WorkerGrant::active()
            ->where('workerUserId', $uid)
            ->where('status', WorkerGrant::STATUS_ACTIVE)
            ->with('boss')
            ->get();
    }

    /** True if the current user is a worker in at least one farm. */
    public static function isWorker(): bool
    {
        return self::grants()->isNotEmpty();
    }

    /** The grant for the currently-selected farm, or null (acting as owner). */
    public static function activeGrant(): ?WorkerGrant
    {
        $grants = self::grants();
        if ($grants->isEmpty()) {
            return null;
        }

        $bossId = session('activeBossId');
        $grant = $bossId ? $grants->firstWhere('bossUserId', (int) $bossId) : null;

        // Default to the first farm if none chosen yet (single-farm workers
        // never see a picker).
        return $grant ?? $grants->first();
    }

    /**
     * The user id whose schedules the current request should see: the active
     * boss when in a worker context, otherwise the logged-in user themselves.
     */
    public static function effectiveOwnerId(): int
    {
        $grant = self::activeGrant();

        return $grant ? (int) $grant->bossUserId : (int) Auth::id();
    }

    /** Whether the current worker context may write to schedules. */
    public static function canEdit(): bool
    {
        $grant = self::activeGrant();

        // Owners (no grant) always edit their own; workers need 'edit'.
        return $grant ? $grant->canEditSchedules() : true;
    }

    /** Whether the current worker context may at least view schedules. */
    public static function canView(): bool
    {
        $grant = self::activeGrant();

        return $grant ? $grant->canViewSchedules() : true;
    }
}
