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
    /** The acting user's tier key: libre | solo | owner | admin. */
    public static function of(?User $user = null): string
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return 'libre';
        }

        return $user->planTier();
    }

    /** The tier that governs a schedule — its owner's. */
    public static function forSchedule($schedule): string
    {
        $owner = $schedule ? User::find($schedule->anisystemUserId) : null;

        return self::of($owner);
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

    /**
     * The uniform refusal. JSON callers get the shape the upgrade modal
     * listens for; page loads bounce to the subscription page with the
     * message as a flash. `$unlocksAt` names the cheapest tier that opens
     * this door ('solo' or 'owner') so the modal can sell that rung, not
     * a vague "subscribers".
     */
    public static function deny(string $message, string $unlocksAt = 'solo')
    {
        $request = request();
        if ($request->expectsJson() || $request->ajax()) {
            abort(response()->json([
                'success' => false,
                'tierLock' => true,
                'message' => $message,
                'tier' => $unlocksAt,
            ], 403));
        }

        abort(redirect()->route('account.subscription')->with('error', $message));
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
        self::deny('Your ' . self::limits(self::of($user))['name'] . ' plan\'s '
            . $capGb . ' GB storage is full. Free some space or upgrade for more.');
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
