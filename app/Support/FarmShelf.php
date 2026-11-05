<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * What a worker saves from their worker account is also the farm's (2026-11-06, the
 * owner: "everything saved by the worker account in the worker account is also saved
 * in the owner account to be seen").
 *
 * Seasons already do this, since a season is the farm's. This is for what has no
 * season: Global Notes and Quick Voice, the Global Gallery, Maps and Draw, the saved
 * runs of the analyses, protocols, NPK calculations and contacts. Those keep the
 * worker as their userId and carry farmOwnerId, the boss whose farm they were saved in.
 *
 * It runs one way. The owner's shelves show what their workers saved and can change or
 * delete it, as the farm's. A worker's shelf stays their own and never shows the
 * owner's. Nothing saved from a personal account is marked, and a farm on a plan with
 * no worker logins marks nothing.
 *
 * A check that stops one person from starting the same paid run twice (the $standing
 * lookups) stays fenced to the person, because the shelf is not the question there.
 */
class FarmShelf
{
    /** The boss to mark on a save made now, or null when it is the person's own. */
    public static function stamp(): ?int
    {
        $grant = WorkerContext::activeGrant();

        return $grant && Tier::farmCan('workerLogins') ? (int) $grant->bossUserId : null;
    }

    /**
     * For ->where(FarmShelf::mine()): what is on this person's shelf. Their own saves,
     * and when they are standing in their own farm, what their workers saved for it.
     */
    public static function mine($me = null): \Closure
    {
        $me = (int) ($me ?? Auth::id());
        $ownFarm = ! WorkerContext::inWorkerContext();

        return function ($q) use ($me, $ownFarm) {
            $q->where('userId', $me);
            if ($ownFarm) {
                $q->orWhere('farmOwnerId', $me);
            }
        };
    }

    /** The first name to show on someone else's save, or null when it is the viewer's own. */
    public static function by($authorId): ?string
    {
        static $names = [];
        $authorId = (int) $authorId;
        if (! $authorId || $authorId === (int) Auth::id()) {
            return null;
        }
        $names[$authorId] ??= strtok(trim((string) optional(User::find($authorId))->full_name), ' ') ?: 'A worker';

        return $names[$authorId];
    }
}
