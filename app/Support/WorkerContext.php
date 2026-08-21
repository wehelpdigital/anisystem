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

    /** Whether the current worker context may write to schedules. */
    public static function canEdit(): bool
    {
        $grant = self::activeGrant();

        // Owners (no grant) always edit their own; workers need 'edit'.
        return $grant ? $grant->canEditSchedules() : true;
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

        return $grant ? $grant->canAddNotes() : true;
    }

    /** Whether the current worker context may at least view schedules. */
    public static function canView(): bool
    {
        $grant = self::activeGrant();

        return $grant ? $grant->canViewSchedules() : true;
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

        return $grant ? $grant->moduleAccess($key) : 'edit';
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
     * Whether the current worker context may use the community at all.
     *
     * The column has existed since worker logins did and was never once
     * asked, so an owner could turn community access off and watch nothing
     * happen. Owners are not workers and are never refused.
     */
    public static function canUseCommunity(): bool
    {
        $grant = self::activeGrant();

        return $grant ? (bool) $grant->communityAccess : true;
    }
}
