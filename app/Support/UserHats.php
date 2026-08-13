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
                'bossId' => null,
                'url' => null,
            ];
        }

        foreach ($grants as $g) {
            $boss = $g->boss;
            $name = $boss ? trim(($boss->firstName ?? '') . ' ' . ($boss->lastName ?? '')) : '';
            $hats[] = [
                'key' => 'worker:' . $g->bossUserId,
                'kind' => 'worker',
                'title' => 'Worker at ' . ($name !== '' ? $name : 'a farm'),
                'detail' => $g->scheduleAccess === 'edit'
                    ? 'You can add and change work on this farm'
                    : 'You can see this farm\'s plan, without changing it',
                'bossId' => (int) $g->bossUserId,
                'url' => null,
            ];
        }

        // The mother site is where administration actually happens; this app
        // has no admin area of its own, so the hat is a door rather than a
        // mode. Shown only when the account is genuinely linked to an admin.
        $motherUrl = (string) config('mother.url');
        if ($user->adminUserId && $motherUrl !== '') {
            $hats[] = [
                'key' => 'admin',
                'kind' => 'admin',
                'title' => 'Administrator',
                'detail' => 'Open the admin site in a new tab',
                'bossId' => null,
                'url' => $motherUrl,
            ];
        }

        return $hats;
    }

    /** Does this account have more than one way in? */
    public static function needsChoice(?User $user): bool
    {
        // The admin door does not change what this app shows, so it alone is
        // not a reason to stop someone on the way in.
        return count(array_filter(self::for($user), fn ($h) => $h['kind'] !== 'admin')) > 1;
    }
}
