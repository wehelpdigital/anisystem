<?php

namespace App\Support;

use App\Services\AiCreditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satellite Weather's week pass (2026-10-12). The page costs the house on
 * every opening (a Google map load, the forecast grid, the storm and tile
 * reads), so it is open to a farm that holds a pass: seven days, paid in
 * credits, up to FAIR_OPENS openings (a pass used up early is renewed the
 * same way). Unlimited accounts need none. Anee's reading of the sky is
 * priced on its own, as before.
 *
 * THE PRICE IS CHECKED, NOT ASSUMED. AiPrices 'skypass' is the owner's
 * price (30 by default, editable in the mother app). price() never sells
 * below floor(): what a pass used to its fair use limit costs the house,
 * at today's list prices, plus a margin, in credits at the cheapest a
 * credit is ever sold. Today that is about 27 credits, so 30 stands; if a
 * cost rises or the owner's price is set too low, the floor wins.
 */
final class SkyPass
{
    public const DAYS = 7;
    public const FAIR_OPENS = 40;

    /** What one opening costs the house, in pesos (2026-10-12 list prices, ₱58 to the dollar). */
    public const COST_PER_OPEN = [
        'googleMapLoad' => 0.406,   // Dynamic Maps, $7 per 1,000 loads past the monthly free 10,000
        'forecastGrid' => 0.06,     // the shared national grid, one fetch per 3 hours, on Open-Meteo's paid plan
        'serverAndTiles' => 0.04,   // the OpenWeatherMap tiles and frames through our server, cached and shared
    ];

    /** The cheapest a credit is ever sold (the Whole Season pack: 1,150 credits for ₱1,000). */
    public const CREDIT_PESO_FLOOR = 0.87;

    /** The least the house keeps over cost, at full fair use. */
    public const MARGIN = 1.15;

    public static function floor(): int
    {
        $cost = array_sum(self::COST_PER_OPEN) * self::FAIR_OPENS * self::MARGIN;

        return (int) ceil($cost / self::CREDIT_PESO_FLOOR);
    }

    public static function price(): int
    {
        return max(AiPrices::of('skypass'), self::floor());
    }

    private static function ready(): bool
    {
        static $ok = null;

        return $ok ??= Schema::hasTable('as_sky_passes');
    }

    /** The farm's pass that is running now, if any. */
    public static function active(int $payerId): ?object
    {
        if (! self::ready()) {
            return null;
        }

        return DB::table('as_sky_passes')->where('userId', $payerId)->where('deleteStatus', 1)
            ->where('endsAt', '>', now())->where('opens', '<', self::FAIR_OPENS)->orderByDesc('endsAt')->first();
    }

    public static function allows(int $payerId): bool
    {
        return app(AiCreditService::class)->unlimited($payerId) || self::active($payerId) !== null;
    }

    /** One more opening of the page against the pass. */
    public static function opened(int $payerId): void
    {
        if ($p = self::active($payerId)) {
            DB::table('as_sky_passes')->where('id', $p->id)->update(['opens' => DB::raw('opens + 1'), 'updated_at' => now()]);
        }
    }

    /** What the page says about the farm's pass. */
    public static function state(int $payerId): array
    {
        $unlimited = app(AiCreditService::class)->unlimited($payerId);
        $p = self::active($payerId);

        return [
            'active' => $unlimited || $p !== null, 'unlimited' => $unlimited, 'price' => self::price(), 'days' => self::DAYS, 'fairOpens' => self::FAIR_OPENS,
            'endsAt' => $p ? Carbon::parse($p->endsAt)->timezone('Asia/Manila')->format('D, M j, g:i A') : null,
            'opensLeft' => $p ? max(0, self::FAIR_OPENS - (int) $p->opens) : null,
        ];
    }

    /**
     * Buy a week. Charged first, under the ledger's lock; a farm that holds
     * a running pass is told so rather than charged twice.
     *
     * @return array{ok:bool,message:string,status?:int}
     */
    public static function buy(int $payerId): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'Satellite Weather passes are being set up. Please try again in a few minutes.', 'status' => 503];
        }
        if (self::active($payerId)) {
            return ['ok' => false, 'message' => 'Your farm already has a pass running.', 'status' => 409];
        }
        $price = self::price();
        $credits = app(AiCreditService::class);
        $left = $credits->charge($payerId, $price, 'Satellite Weather: a week pass (' . self::DAYS . ' days, up to ' . self::FAIR_OPENS . ' openings)');
        if ($left === null) {
            return ['ok' => false, 'message' => 'A week of Satellite Weather takes ' . $price . ' credits and you have ' . number_format((int) floor($credits->balance($payerId))) . '.', 'status' => 402];
        }
        DB::table('as_sky_passes')->insert(['userId' => $payerId, 'startsAt' => now(), 'endsAt' => now()->addDays(self::DAYS), 'credits' => $price, 'opens' => 0,
            'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return ['ok' => true, 'message' => 'Satellite Weather is open for ' . self::DAYS . ' days.'];
    }

    /** Whether any farm holds a running pass: the batch work only runs for subscribers. */
    public static function anyActive(): bool
    {
        return self::ready() && DB::table('as_sky_passes')->where('deleteStatus', 1)->where('endsAt', '>', now())->exists();
    }
}
