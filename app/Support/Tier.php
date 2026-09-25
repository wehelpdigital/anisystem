<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The single reader of config/tiers.php — every gate in the app asks HERE.
 *
 * Two kinds of limit, judged against two different people:
 *  - SCHEDULE-SCOPED limits (lots, workers, video on a farm, collab,
 *    reports, weather depth) are the SCHEDULE OWNER's tier — a worker
 *    inside a farm rides the owner's plan.
 *  - PERSONAL limits (storage, community media, discussions, maps total,
 *    schedule counts) are the acting user's own tier.
 *
 * Refusals are uniform: deny() returns the 403 JSON the front end's
 * upgrade modal listens for ({tierLock: true}), or a redirect for plain
 * page loads. The UI's faded buttons are a courtesy; these checks are
 * the door.
 */
final class Tier
{
    /** The acting user's own tier key: libre | solo | owner | admin. */
    public static function of(?User $user = null): string
    {
        $user = $user ?: Auth::user();

        return $user ? $user->planTier() : 'libre';
    }

    /** The tier that governs a schedule — its owner's. */
    public static function forSchedule($schedule): string
    {
        $owner = $schedule ? User::find($schedule->anisystemUserId) : null;

        return self::of($owner);
    }

    /* -------------------------------------------------- the farm's tier */

    /**
     * THE TIER OF THE FARM THIS REQUEST IS STANDING IN.
     *
     * A plan is bought by a farm, and everything a worker does while standing
     * in one is that farm's work — so farm-scoped doors ask this, not of().
     * Reading the worker's own plan was wrong in both directions: a farm on
     * the top rung had its workers refused a feature it had paid for, and a
     * worker who happened to hold a plan of their own carried it into a farm
     * that had bought nothing.
     *
     * This is deliberately NOT the same as of(). Personal limits — community
     * media, discussions, storage, the weather on your own dashboard — remain
     * the acting user's, because a job on somebody's farm is not a plan.
     *
     * The grant is cached per request and arrives with its boss already
     * loaded, so this costs no query.
     */
    public static function ofFarm(): string
    {
        $grant = WorkerContext::activeGrant();
        if (! $grant) {
            return self::of();
        }
        $boss = $grant->boss ?: User::find($grant->bossUserId);

        return $boss ? $boss->planTier() : 'libre';
    }

    /** True when a boolean feature is on for the farm being worked. */
    public static function farmCan(string $key): bool
    {
        return (bool) (self::limits(self::ofFarm())[$key] ?? false);
    }

    /** One limit judged by the farm being worked. */
    public static function farmLimit(string $key)
    {
        return self::limits(self::ofFarm())[$key] ?? null;
    }

    /** True while the refusal should say "ask the owner" rather than "buy". */
    public static function buyerIsHere(): bool
    {
        return ! WorkerContext::inWorkerContext();
    }

    /** The whole limit row for a tier key. */
    public static function limits(string $tier): array
    {
        return config('tiers.' . $tier, config('tiers.libre', []));
    }

    /** One limit for the acting user (or a given user). */
    public static function limit(string $key, ?User $user = null)
    {
        return self::limits(self::of($user))[$key] ?? null;
    }

    /** One limit judged by a schedule's owner. */
    public static function scheduleLimit($schedule, string $key)
    {
        return self::limits(self::forSchedule($schedule))[$key] ?? null;
    }

    /** True when a boolean feature is on for the acting user. */
    public static function can(string $key, ?User $user = null): bool
    {
        return (bool) (self::limit($key, $user) ?? false);
    }

    /** True when a boolean feature is on for a schedule's owner. */
    public static function scheduleCan($schedule, string $key): bool
    {
        return (bool) (self::scheduleLimit($schedule, $key) ?? false);
    }

    /* ------------------------------------------- which plan opens a door */

    /** The paid rungs, cheapest first, in config order. Never 'admin'. */
    public const PAID_LADDER = ['libreAnee', 'solo', 'owner'];

    /**
     * THE CHEAPEST PLAN THAT OPENS THIS DOOR, for somebody standing on
     * `$fromTier` (the acting user's own plan when not given).
     *
     * Every locked door asks here instead of naming a rung by hand, because a
     * hand-named rung drifts the moment a key moves on the ladder -- and "the
     * next plan up" is the wrong answer more often than not: the Collab Room
     * is the Farm Owner plan's, so telling a Libre member to buy Libre + Anee
     * sold them something that does not open it.
     *
     * Walks the paid ladder above `$fromTier` in order and returns the first
     * rung whose value for `$key` opens it:
     *  - a boolean switch opens on `true`;
     *  - a numeric cap opens on null (unlimited) or a number larger than the
     *    one the member has now -- "upgrade for more lots" means MORE lots.
     * Falls back to 'owner', the top of what is sold.
     */
    public static function unlocksAt(string $key, ?string $fromTier = null): string
    {
        $fromTier = $fromTier ?? self::of();
        $ladder = array_values(array_filter(
            array_keys((array) config('tiers', [])),
            fn ($k) => $k !== 'admin'
        ));
        if (! $ladder) {
            $ladder = array_merge(['libre'], self::PAID_LADDER);
        }
        $pos = array_search($fromTier, $ladder, true);
        $here = self::limits(in_array($fromTier, $ladder, true) ? $fromTier : 'libre')[$key] ?? null;

        foreach ($ladder as $i => $rung) {
            if ($rung === 'libre' || ($pos !== false && $i <= $pos)) {
                continue;   // the free floor is never sold; nor is anything at or below you
            }
            $row = self::limits($rung);
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $v = $row[$key];
            if (is_bool($v) || is_bool($here)) {
                if ($v === true) {
                    return $rung;
                }
                continue;
            }
            if ($v === null) {
                return $rung;   // unlimited
            }
            if (is_numeric($v) && ($here === null ? false : (float) $v > (float) $here)) {
                return $rung;
            }
        }

        return 'owner';
    }

    /** The rung for a door on the farm being worked (the boss's plan for a worker). */
    public static function farmUnlocksAt(string $key): string
    {
        return self::unlocksAt($key, self::ofFarm());
    }

    /** The rung for a door judged by a schedule owner's plan. */
    public static function scheduleUnlocksAt($schedule, string $key): string
    {
        return self::unlocksAt($key, self::forSchedule($schedule));
    }

    /** A rung's display name: 'Farm Owner', 'Libre + Anee'. */
    public static function planName(string $tier): string
    {
        return (string) (config('tiers.' . $tier . '.name') ?? ucfirst($tier));
    }

    /**
     * How a sentence names a rung: "Libre + Anee" (a name that already reads
     * as a plan) or "the Solo Farmer plan". For words like "... comes with
     * {withPlan}".
     */
    public static function withPlan(string $tier): string
    {
        return $tier === 'libreAnee'
            ? self::planName($tier)
            : 'the ' . self::planName($tier) . ' plan';
    }

    /** A door's words with `{plan}` filled in for a rung: what data-lock-say carries. */
    public static function say(string $rung, string $words): string
    {
        return str_replace('{plan}', self::withPlan($rung), $words);
    }

    /**
     * Deny, naming the cheapest rung that opens `$key` for somebody on
     * `$fromTier` (the acting user's plan when not given). The message may
     * carry `{plan}` -- replaced with withPlan() of that rung -- so the words
     * and the card on the sheet can never disagree.
     */
    public static function denyFor(string $key, string $message, ?string $fromTier = null)
    {
        $rung = self::unlocksAt($key, $fromTier);

        return self::deny(str_replace('{plan}', self::withPlan($rung), $message), $rung);
    }

    /** denyFor(), judged by the farm being worked. */
    public static function farmDenyFor(string $key, string $message)
    {
        return self::denyFor($key, $message, self::ofFarm());
    }

    /** denyFor(), judged by a schedule owner's plan. */
    public static function scheduleDenyFor($schedule, string $key, string $message)
    {
        return self::denyFor($key, $message, self::forSchedule($schedule));
    }

    /**
     * The uniform refusal. JSON callers get the shape the upgrade modal
     * listens for; page loads bounce to the subscription page with the
     * message as a flash. `$unlocksAt` names the cheapest tier that opens
     * this door so the modal can sell that rung, not a vague "subscribers"
     * -- prefer denyFor(), which works the rung out from the ladder.
     */
    public static function deny(string $message, string $unlocksAt = 'solo')
    {
        if (! in_array($unlocksAt, self::PAID_LADDER, true)) {
            $unlocksAt = 'owner';
        }
        $request = request();
        if ($request->expectsJson() || $request->ajax()) {
            abort(response()->json([
                'success' => false,
                'tierLock' => true,
                'message' => $message,
                'tier' => $unlocksAt,
                'tierName' => self::planName($unlocksAt),
            ], 403));
        }

        // A worker sent to their own subscription page would be reading about
        // a plan that has nothing to do with the door that just shut.
        if (WorkerContext::inWorkerContext()) {
            abort(redirect()->route('app.dashboard')->with('error', $message
                . ' Only the farm owner can change that.'));
        }

        // The subscription page opens the same sheet the tap would have,
        // selling the same rung (the layout reads `tierLock`).
        abort(redirect()->route('account.subscription')
            ->with('error', $message)
            ->with('tierLock', $unlocksAt));
    }

    /* ---------------------------------------------------------- storage */

    /** Bytes this user's uploads have recorded in the ledger. */
    public static function storageUsed(?User $user = null): int
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return 0;
        }

        return (int) DB::table('as_storage_ledger')
            ->where('userId', $user->id)
            ->where('deleteStatus', 1)
            ->sum('bytes');
    }

    /**
     * Refuse an upload that would pass the tier's cap. Called by
     * MediaStore before a file is stored, so every road in — notes,
     * activities, community, chat — meets the same wall.
     */
    public static function assertStorage(int $addBytes, ?User $user = null): void
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return;
        }
        $capGb = self::limit('storageGb', $user);
        if ($capGb === null) {
            return;   // unlimited
        }
        $cap = (int) ($capGb * 1024 * 1024 * 1024);
        if (self::storageUsed($user) + $addBytes <= $cap) {
            return;
        }
        $tier = self::of($user);
        self::denyFor('storageGb', 'Your ' . self::limits($tier)['name'] . ' plan\'s '
            . $capGb . ' GB storage is full. Free some space, or move up to {plan} for more.', $tier);
    }

    /** Write an upload into the ledger, so the cap stays honest. */
    public static function recordUpload(int $bytes, string $path, string $source = ''): void
    {
        $userId = (int) Auth::id();
        if (! $userId || $bytes <= 0) {
            return;
        }
        DB::table('as_storage_ledger')->insert([
            'userId' => $userId,
            'bytes' => $bytes,
            'path' => mb_substr($path, 0, 500),
            'source' => mb_substr($source, 0, 60),
            'deleteStatus' => 1,
            'created_at' => now('Asia/Manila'),
            'updated_at' => now('Asia/Manila'),
        ]);
    }

    /** Forget a deleted file's bytes (best effort, matched by path). */
    public static function releaseUpload(string $path): void
    {
        DB::table('as_storage_ledger')
            ->where('path', mb_substr($path, 0, 500))
            ->where('deleteStatus', 1)
            ->limit(1)
            ->update(['deleteStatus' => 0, 'updated_at' => now('Asia/Manila')]);
    }
}
