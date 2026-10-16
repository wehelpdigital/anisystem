<?php

namespace App\Support;

use App\Services\AiCreditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satellite Weather's weekly subscription (2026-10-12; renewing since
 * 2026-10-13, the owner's ask). The page costs the house on
 * every opening (a Google map load, the forecast grid, the storm and tile
 * reads), so it is open to a farm that holds a pass: seven days, paid in
 * credits, up to FAIR_OPENS openings (a pass used up early is renewed the
 * same way). Unlimited accounts need none. Anee's reading of the sky is
 * priced on its own, as before.
 *
 * THE PRICE IS CHECKED, NOT ASSUMED. AiPrices 'skypass' is the owner's
 * price (30 by default, editable in the mother app). price() never sells
 * below floor(). Since 2026-10-15 the floor is the pricing engine's quote
 * (App\Support\AiPricing, kind 'skypass'): what a week costs the house at
 * the openings a pass really sees (the 90th percentile of the passes that
 * ended, the model's own number until ten have), at list prices, times the
 * owner's margin (8x by default, never under 6x) over the cheapest a credit
 * is sold. With the engine switched off it is the old floor: a pass used to
 * its fair use limit, plus MARGIN.
 */
final class SkyPass
{
    public const DAYS = 7;
    /** A pass is renewed this long before it ends, so a farm with renewal on never sees the wall. */
    public const RENEW_AHEAD_MINUTES = 30;
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
        if (AiPricing::settings()['on']) {
            return AiPricing::credits('skypass');
        }
        $cost = array_sum(self::COST_PER_OPEN) * self::FAIR_OPENS * self::MARGIN;

        return (int) ceil($cost / self::CREDIT_PESO_FLOOR);
    }

    public static function price(): int
    {
        // The flat price is a floor only while the engine keeps flat floors (AiPricing flatFloor).
        $s = AiPricing::settings();

        return $s['on'] && ! $s['flatFloor'] ? self::floor() : max(AiPrices::of('skypass'), self::floor());
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

    /** What the page says about the farm's subscription. */
    public static function state(int $payerId): array
    {
        $unlimited = app(AiCreditService::class)->unlimited($payerId);
        $p = self::active($payerId);
        $renews = $p && (int) ($p->autoRenew ?? 0) === 1;
        // The last pass, running or not: what happened at its renewal, if it did not renew.
        $last = self::ready() ? DB::table('as_sky_passes')->where('userId', $payerId)->where('deleteStatus', 1)->orderByDesc('endsAt')->first() : null;

        return [
            'active' => $unlimited || $p !== null, 'unlimited' => $unlimited, 'price' => self::price(), 'days' => self::DAYS, 'fairOpens' => self::FAIR_OPENS,
            'endsAt' => $p ? Carbon::parse($p->endsAt)->timezone('Asia/Manila')->format('D, M j, g:i A') : null,
            'opensLeft' => $p ? max(0, self::FAIR_OPENS - (int) $p->opens) : null,
            'autoRenew' => $renews, 'since' => $p ? Carbon::parse($p->startsAt)->timezone('Asia/Manila')->format('M j') : null,
            'lapsed' => ! $p && $last ? ['endedAt' => Carbon::parse($last->endsAt)->timezone('Asia/Manila')->format('M j, g:i A'), 'note' => $last->renewNote] : null,
        ];
    }

    /** Renewal on or off for the running pass (off: it simply ends on its day). */
    public static function setAutoRenew(int $payerId, bool $on): bool
    {
        $p = self::active($payerId);
        if (! $p) {
            return false;
        }
        DB::table('as_sky_passes')->where('id', $p->id)->update(['autoRenew' => $on ? 1 : 0, 'renewNote' => null, 'updated_at' => now()]);

        return true;
    }

    /**
     * The renewals due: every pass with renewal on that ends within
     * RENEW_AHEAD_MINUTES (or has ended, or used up its openings) and has
     * not been renewed yet. Each is charged a week and a new pass starts
     * where it ends; a farm short of credits is not charged, its pass ends,
     * and the page says why. Run every few minutes by sky:renew.
     *
     * @return array{renewed:int,lapsed:int}
     */
    public static function renewDue(): array
    {
        if (! self::ready()) {
            return ['renewed' => 0, 'lapsed' => 0];
        }
        $credits = app(AiCreditService::class);
        $done = ['renewed' => 0, 'lapsed' => 0];
        $due = DB::table('as_sky_passes')->where('deleteStatus', 1)->where('autoRenew', 1)->whereNull('renewedTo')
            ->where(fn ($q) => $q->where('endsAt', '<=', now()->addMinutes(self::RENEW_AHEAD_MINUTES))->orWhere('opens', '>=', self::FAIR_OPENS))
            ->orderBy('endsAt')->limit(500)->get();
        foreach ($due as $p) {
            $price = self::price();
            $from = Carbon::parse($p->endsAt)->gt(now()) && (int) $p->opens < self::FAIR_OPENS ? Carbon::parse($p->endsAt) : now();
            $left = $credits->charge((int) $p->userId, $price, 'Satellite Weather: weekly renewal (' . self::DAYS . ' days, up to ' . self::FAIR_OPENS . ' openings)');
            if ($left === null) {
                DB::table('as_sky_passes')->where('id', $p->id)->update(['autoRenew' => 0, 'renewNote' => 'Not renewed: the farm had fewer than ' . $price . ' credits.', 'updated_at' => now()]);
                $done['lapsed']++;
                continue;
            }
            $newId = DB::table('as_sky_passes')->insertGetId(['userId' => $p->userId, 'startsAt' => $from, 'endsAt' => $from->copy()->addDays(self::DAYS), 'credits' => $price,
                'opens' => 0, 'autoRenew' => 1, 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now()]);
            // The old pass gives way at once when its openings ran out; otherwise it runs to its end.
            DB::table('as_sky_passes')->where('id', $p->id)->update(['renewedTo' => $newId, 'updated_at' => now()]
                + ((int) $p->opens >= self::FAIR_OPENS ? ['endsAt' => now()] : []));
            \Illuminate\Support\Facades\Cache::forget('skypass:ok:' . $p->userId);
            $done['renewed']++;
        }

        return $done;
    }

    /**
     * Buy a week. Charged first, under the ledger's lock; a farm that holds
     * a running pass is told so rather than charged twice.
     *
     * @return array{ok:bool,message:string,status?:int}
     */
    public static function buy(int $payerId, bool $autoRenew = true): array
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
            'autoRenew' => $autoRenew ? 1 : 0, 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return ['ok' => true, 'message' => $autoRenew ? 'Satellite Weather is yours. It renews every ' . self::DAYS . ' days from your credits until you cancel.' : 'Satellite Weather is open for ' . self::DAYS . ' days.'];
    }

    /** Whether any farm holds a running pass: the batch work only runs for subscribers. */
    public static function anyActive(): bool
    {
        return self::ready() && DB::table('as_sky_passes')->where('deleteStatus', 1)->where('endsAt', '>', now())->exists();
    }
}
