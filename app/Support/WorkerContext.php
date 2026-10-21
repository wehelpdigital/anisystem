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
    /** All active grants for the current user (their farms).
     *
     * Held for the request: a page asks this through activeGrant() once per
     * module tile, once per toolbar button and once more for the gate on the
     * way in, and the rows cannot change between two of those asks. */
    private static array $grantCache = [];

    /**
     * The season this request is about (2026-10-22: access is set per
     * cropping schedule, or for all of them). An id once known; null for the
     * farm as a whole; 'pending' on a season page whose season is not read
     * yet. Set by the gate from the request, and by the controllers when they
     * resolve the season (enterSeason), which is the last word.
     */
    private static $season = null;

    public static function useSeason($season): void
    {
        self::$season = is_numeric($season) && (int) $season > 0 ? (int) $season : ($season === 'pending' ? 'pending' : null);
    }

    /** @return int|string|null */
    public static function season()
    {
        return self::$season;
    }

    /**
     * A controller has found the season the request is about: from here on
     * every question is asked of that season's own setting, and the gate asks
     * its table again, because what it allowed for the farm as a whole may
     * not hold in this season. A refusal ends the request there.
     */
    public static function enterSeason(int $id): void
    {
        if (self::$season === $id || ! self::inWorkerContext()) {
            self::$season = $id;

            return;
        }
        self::$season = $id;
        $request = request();
        if ($request && $request->route()) {
            $no = \App\Http\Middleware\WorkerModuleAccess::verdict($request);
            if ($no) {
                throw new \Illuminate\Http\Exceptions\HttpResponseException($no);
            }
        }
    }

    public static function grants()
    {
        $uid = (int) Auth::id();
        if (! $uid) {
            return collect();
        }

        if (isset(self::$grantCache[$uid])) {
            return self::$grantCache[$uid];
        }

        return self::$grantCache[$uid] = WorkerGrant::active()
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

    /**
     * True only while the request is actually looking at someone else's farm.
     *
     * Not the same question as isWorker(): holding a grant somewhere says
     * nothing about the farm on screen. Anything that restricts what a worker
     * may do has to ask this one, or it punishes an owner for having once been
     * lent a neighbour's keys.
     */
    public static function inWorkerContext(): bool
    {
        return self::activeGrant() !== null;
    }

    /** The grant for the currently-selected farm, or null (acting as owner). */
    public static function activeGrant(): ?WorkerGrant
    {
        $grants = self::grants();
        if ($grants->isEmpty()) {
            return null;
        }

        $chosen = session('activeBossId');
        if ($chosen) {
            $grant = $grants->firstWhere('bossUserId', (int) $chosen);
            if ($grant) {
                return $grant;
            }
            // The picker stores the user's own id for "my own farm".
            if ((int) $chosen === (int) Auth::id()) {
                return null;
            }
        }

        // Nobody has chosen yet. Someone who farms their own land starts on it:
        // being lent access to a neighbour's does not make you their worker,
        // and defaulting the other way hid their own schedules completely —
        // new ones 404'd the moment they were created, because they belonged to
        // a farm the request was no longer looking at. A worker with no land of
        // their own still lands straight in the farm they work, which is the
        // case this default was written for.
        return self::ownsSchedules() ? null : $grants->first();
    }

    /** Does the current user have a farm of their own? */
    public static function ownsSchedules(): bool
    {
        static $cache = [];
        $uid = (int) Auth::id();
        if (! $uid) {
            return false;
        }

        return $cache[$uid] ??= \App\Models\AsCroppingSchedule::active()
            ->where('anisystemUserId', $uid)
            ->exists();
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

    /**
     * Which of a farm's seasons this request is allowed to see, or null for
     * "all of them".
     *
     * The login's access settings say (2026-10-22): an "all cropping
     * schedules" setting opens every season of the farm, now and to come;
     * otherwise the seasons with a setting of their own, and no others. Being
     * on a season's roster no longer opens it by itself: the roster is who
     * works there, the settings are what their login may open.
     *
     * Returns null for an owner and for a farm that is not the one on screen.
     * An empty array means "none of them", which is a real answer.
     */
    public static function visibleScheduleIdsFor(int $ownerId): ?array
    {
        $grant = self::activeGrant();
        if (! $grant || (int) $grant->bossUserId !== $ownerId) {
            return null;   // an owner, or a farm this session is not standing in
        }

        return $grant->seasonIds();
    }

    /**
     * The visible seasons where a module is open to whoever is asking, for
     * the pages that gather from every season (Global Notes, the maps, the
     * report comparisons). Null when nothing is withheld.
     *
     * @return array<int, int>|null
     */
    public static function seasonIdsWith(string $module, string $level = 'view'): ?array
    {
        $grant = self::activeGrant();
        if (! $grant) {
            return null;
        }
        $owner = (int) $grant->bossUserId;
        $ids = $grant->seasonIds() ?? \App\Models\AsCroppingSchedule::active()->where('anisystemUserId', $owner)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_filter($ids, function ($id) use ($grant, $module, $level) {
            $has = $grant->moduleAccess($module, (int) $id);

            return $level === 'edit' ? $has === 'edit' : $has !== 'none';
        }));
    }

    /** Whether the current worker context may write to schedules. */
    public static function canEdit(): bool
    {
        $grant = self::activeGrant();

        // Owners (no grant) always edit their own; workers need 'edit'.
        return $grant ? $grant->canEditSchedules(self::$season) : true;
    }

    /**
     * Whether the current worker context may write the day's notes.
     *
     * Deliberately looser than canEdit(): an owner can hand a view-only
     * worker the right to record what happened in the field without handing
     * them the right to change what is planned. Owners always may.
     */
    public static function canAddNotes(): bool
    {
        $grant = self::activeGrant();

        return $grant ? $grant->canAddNotes(self::$season) : true;
    }

    /** Whether the current worker context may at least view schedules. */
    public static function canView(): bool
    {
        $grant = self::activeGrant();

        return $grant ? $grant->canViewSchedules(self::$season) : true;
    }

    /**
     * What the farm has said about one module for whoever is asking.
     *
     * An owner standing on their own farm gets 'edit' for everything — there
     * is no grant to consult and nothing to withhold from themselves. Keys
     * are the ones in WorkerGrant::MODULES.
     */
    public static function moduleAccess(string $key): string
    {
        $grant = self::activeGrant();

        return $grant ? $grant->moduleAccess($key, self::$season) : 'edit';
    }

    /** May they open this module at all? */
    public static function canUseModule(string $key): bool
    {
        return self::moduleAccess($key) !== 'none';
    }

    /** May they add to it, not only look at it? */
    public static function canWriteModule(string $key): bool
    {
        return self::moduleAccess($key) === 'edit';
    }

    /**
     * May whoever is asking see and open one of the home screen's tools
     * (WorkerTools keys)? Always, outside somebody else's farm.
     */
    public static function canUseTool(string $key): bool
    {
        $grant = self::activeGrant();

        return $grant ? $grant->mayUseTool($key) : true;
    }

    /**
     * Whether the current context may use the community at all.
     *
     * NO, while standing in somebody else's farm — and it is not an owner's
     * switch to flip. Every worker login is a full free account of its own,
     * with its own feed, its own co-farmers and its own name on a post. The
     * community belongs to that account, reached by switching back to "my
     * own farm"; it was never the boss's to grant or withhold, and a wall
     * posted from inside a farm hat only confused whose voice it was.
     *
     * `communityAccess` stays on the row and is still written by the access
     * forms, because old grants carry it and nothing is gained by a
     * migration that drops a column somebody may yet want. It is simply no
     * longer asked.
     */
    public static function canUseCommunity(): bool
    {
        return self::activeGrant() === null;
    }
}
