<?php

namespace App\Support;

use App\Models\AsSiteSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The field helpers (2026-10-14, the owner): the Weed Control Helper, the
 * Pest Finder, the Disease Finder, the Nutrient Deficiency Finder, the
 * Variety Finder and the Compatibility Checker, locked on the free plan
 * (Libre) and open on Libre + Anee and every plan above it
 * (config/tiers.php fieldHelpers).
 *
 * No add-on any more (2026-10-18, the owner: "remove that and instead just
 * put it all in the libre plus"): ManualPay no longer sells addon:fieldpack.
 * It was ₱20 a month (₱50 a year before 2026-10-16); nobody bought it. The
 * as_user_addons rows, grant() and revoke() stay only so an old order can
 * still be read or taken back. Whose plan counts: the farm's, the same as
 * every farm module (a worker standing in somebody's farm has what that
 * farm has).
 */
final class FieldPack
{
    public const KEY = 'fieldpack';

    /** The field helpers in the pack (FieldHelperController::TOOLS keys). */
    public const TOOLS = ['weeds', 'pests', 'diseases', 'nutrients', 'varieties', 'compat'];

    public const NAMES = ['Weed Control Helper', 'Pest Finder', 'Disease Finder', 'Nutrient Deficiency Finder', 'Variety Finder', 'Compatibility Checker'];

    /** The plan that opens them (the first one above Libre). */
    public const PLAN = 'libreAnee';

    /** One purchase opens the pack for a month (a calendar month; DAYS is its length for the per day line). */
    public const DAYS = 30;

    /** The price for a month, in the buyer's currency (pesos at home). */
    public static function price(): float
    {
        return Region::ph()
            ? (float) AsSiteSetting::get('addons.fieldpack.php', 20)
            : (float) AsSiteSetting::get('addons.fieldpack.usd', 0.5);
    }

    /** "Less than 67 centavos a day": the price over a month, said plainly. */
    public static function perDay(): string
    {
        $day = self::price() / self::DAYS;
        if (Region::ph()) {
            $centavos = (int) ceil($day * 100);

            return 'less than ' . $centavos . ' centavos a day';
        }

        return 'less than ' . (int) ceil($day * 100) . ' cents a day';
    }

    /** True when the plan itself carries the helpers (every plan above Libre). */
    public static function inPlan(?User $user): bool
    {
        return $user !== null && (bool) (Tier::limits($user->planTier())['fieldHelpers'] ?? true);
    }

    /** The newest active add-on row's end, or null. */
    public static function until(?User $user): ?Carbon
    {
        if (! $user || ! Schema::hasTable('as_user_addons')) {
            return null;
        }
        $end = DB::table('as_user_addons')->where('userId', $user->id)->where('addonKey', self::KEY)
            ->where('status', 'active')->where('startsAt', '<=', now())->where('expiresAt', '>', now())->max('expiresAt');

        return $end ? Carbon::parse($end) : null;
    }

    /** True when this member has the helpers, by plan or by add-on. */
    public static function openFor(?User $user): bool
    {
        return self::inPlan($user) || self::until($user) !== null;
    }

    /** Whether the farm this request stands in lacks the helpers. Cached per request. */
    public static function lockedHere(): bool
    {
        static $memo = [];
        if (! Auth::check()) {
            return true;
        }
        $owner = WorkerContext::effectiveOwnerId();
        if (! array_key_exists($owner, $memo)) {
            $user = $owner === (int) Auth::id() ? Auth::user() : User::find($owner);
            $memo[$owner] = ! self::openFor($user);
        }

        return $memo[$owner];
    }

    /** The checkout item for the add-on (ManualPay::item reads it). */
    public static function item(): array
    {
        return [
            'key' => 'addon:' . self::KEY, 'kind' => 'addon',
            'name' => 'Field helpers, one month',
            'short' => 'Field helpers',
            'tier' => null, 'period' => 'month', 'days' => self::DAYS, 'months' => 1,
            'packId' => null, 'credits' => 0,
            'price' => round(self::price(), 2), 'currency' => Region::currency(),
        ];
    }

    /**
     * An approved order opens the pack for a month, starting where an earlier
     * purchase ends. Returns the order's effect (for revoke).
     */
    public static function grant(int $userId, string $orderNumber, float $price, string $currency): array
    {
        $now = Carbon::now('Asia/Manila');
        $last = DB::table('as_user_addons')->where('userId', $userId)->where('addonKey', self::KEY)
            ->where('status', 'active')->where('expiresAt', '>', $now)->max('expiresAt');
        $start = $last ? Carbon::parse($last, 'Asia/Manila') : $now->copy();
        $end = $start->copy()->addMonthNoOverflow();
        $id = DB::table('as_user_addons')->insertGetId([
            'userId' => $userId, 'addonKey' => self::KEY, 'name' => 'Field helpers, one month',
            'price' => $price, 'currency' => $currency, 'startsAt' => $start, 'expiresAt' => $end,
            'orderNumber' => $orderNumber, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['addonId' => $id, 'startsAt' => $start->toDateTimeString(), 'expiresAt' => $end->toDateTimeString(),
            'queued' => $start->gt($now->copy()->addMinute())];
    }

    /** Take a grant back. */
    public static function revoke(int $addonId, string $why): void
    {
        DB::table('as_user_addons')->where('id', $addonId)->update(['status' => 'revoked', 'notes' => mb_substr($why, 0, 250), 'updated_at' => now()]);
    }
}
