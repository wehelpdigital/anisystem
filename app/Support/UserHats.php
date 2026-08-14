<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\User;
use App\Models\WorkerGrant;

/**
 * The hats one person can wear here.
 *
 * The same account is often three things at once: someone farms their own
 * land, works two days a week on a neighbour's, and — for a handful of people
 * — administers the mother site as well. Those are not three accounts, and
 * making someone keep three passwords for one life would be absurd. They are
 * three ways of using this one, and the app has to ask which is meant before
 * it can show anything: a worker's screen is scoped to their boss's farm, an
 * owner's to their own.
 *
 * Each hat is an array:
 *   key     'own' | 'worker:<bossId>' | 'admin'
 *   kind    'own' | 'worker' | 'admin'
 *   title   what to call it on the chooser
 *   detail  a line of plain explanation
 *   bossId  the farm this hat looks at, when it looks at someone else's
 *   url     where choosing it should land (admin only — it leaves the app)
 */
class UserHats
{
    /** @return list<array<string, mixed>> */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $hats = [];

        $ownSchedules = AsCroppingSchedule::active()
            ->where('anisystemUserId', $user->id)
            ->count();

        $grants = WorkerGrant::active()
            ->where('workerUserId', $user->id)
            ->where('status', WorkerGrant::STATUS_ACTIVE)
            ->with('boss')
            ->get();

        // Someone with no land and no grants is still an owner-in-waiting —
        // the app has to open somewhere, and that somewhere is their own farm.
        if ($ownSchedules > 0 || $grants->isEmpty()) {
            $hats[] = [
                'key' => 'own',
                'kind' => 'own',
                'title' => 'My own farm',
                'detail' => $ownSchedules > 0
                    ? $ownSchedules . ' ' . str('schedule')->plural($ownSchedules) . ' of your own'
                    : 'Start your first cropping schedule',
                'count' => $ownSchedules,
                'bossId' => null,
                'url' => null,
            ];
        }

        foreach ($grants as $g) {
            $boss = $g->boss;
            $name = $boss ? trim(($boss->firstName ?? '') . ' ' . ($boss->lastName ?? '')) : '';
            // The farm's own count, not this account's: choosing this hat
            // shows that farm's schedules, so that is the number to promise.
            $theirs = AsCroppingSchedule::active()
                ->forClient((int) $g->bossUserId)
                ->count();
            $hats[] = [
                'key' => 'worker:' . $g->bossUserId,
                'kind' => 'worker',
                'title' => 'Worker at ' . ($name !== '' ? $name : 'a farm'),
                'detail' => $theirs . ' ' . str('schedule')->plural($theirs) . ' on this farm · '
                    . ($g->scheduleAccess === 'edit' ? 'you can add and change work' : 'you can look, not change'),
                'count' => $theirs,
                'bossId' => (int) $g->bossUserId,
                'url' => null,
            ];
        }

        // The mother site is where administration actually happens; this app
        // has no admin area of its own. It is a door, not a way of using this
        // app — an administrator is still their own farm's owner in here —
        // so it is kept out of the list of hats and offered separately.
        return $hats;
    }

    /**
     * The admin site, for accounts linked to one.
     *
     * Deliberately not a hat: presenting it beside "My own farm" asked people
     * to choose between being an administrator and being a farmer, which is
     * not a choice anybody has to make. They are both, always.
     */
    public static function adminUrl(?User $user): ?string
    {
        $motherUrl = (string) config('mother.url');

        return ($user && $user->adminUserId && $motherUrl !== '') ? $motherUrl : null;
    }

    /** Does this account have more than one way in? */
    public static function needsChoice(?User $user): bool
    {
        // Only the farms count. The admin door does not change what this app
        // shows, so it is never a reason to stop someone on the way in.
        return count(self::for($user)) > 1;
    }
}
