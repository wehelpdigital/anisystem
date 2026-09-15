<?php

namespace App\Support;

use App\Models\AsAdSetting;
use App\Models\AsAdUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Advertising, and who sees it.
 *
 * The free plan is supported by a few ads; every paid plan is ad-free. So
 * a slot shows to a Libre account on its own farm -- never to a worker
 * standing in somebody else's farm, whose plan is not the question -- and
 * on the public pricing page to anybody who is not already paying. The
 * mother app decides what the slots carry (AniSystem > Ads): a Google
 * AdSense slot, a picture that links somewhere, or a network's script,
 * each with the surfaces it may appear on and a weight for the draw.
 *
 * Asked once per request: the settings row and the live units are read
 * the first time and kept, so a page with four slots costs two queries,
 * not eight.
 */
final class Ads
{
    private static ?AsAdSetting $settings = null;

    /** @var Collection<int, AsAdUnit>|null */
    private static ?Collection $units = null;

    /** The units already drawn on this page, so two slots do not show one twice. */
    private static array $drawn = [];

    public static function settings(): AsAdSetting
    {
        return self::$settings ??= AsAdSetting::current();
    }

    /** The master switch, and at least one unit to draw from. */
    public static function enabled(): bool
    {
        try {
            return self::settings()->isEnabled && self::units()->isNotEmpty();
        } catch (\Throwable $e) {
            // A deploy that has not run the migration yet: no ads, no error.
            return false;
        }
    }

    /**
     * Whether the person looking at this page is one who sees ads: a Libre
     * account on its own farm. A guest counts as one on the public pages.
     */
    public static function showTo(): bool
    {
        if (! self::enabled()) {
            return false;
        }
        $user = Auth::user();
        if (! $user) {
            return true;
        }
        if (WorkerContext::inWorkerContext()) {
            return false;
        }

        return Tier::of($user) === 'libre';
    }

    /** @return Collection<int, AsAdUnit> */
    public static function units(): Collection
    {
        return self::$units ??= AsAdUnit::live()->orderBy('sortOrder')->orderBy('id')->get();
    }

    /**
     * One unit for a surface, drawn by weight among those that fit it, or
     * null when nothing does. `$not` keeps a page with several slots from
     * showing the same unit twice while another would do.
     *
     * @param  list<int>  $not
     */
    public static function pick(string $placement, array $not = []): ?AsAdUnit
    {
        $fit = self::units()->filter(fn (AsAdUnit $u) => $u->fits($placement) && self::renderable($u));
        if ($fit->isEmpty()) {
            return null;
        }
        $fresh = $fit->reject(fn (AsAdUnit $u) => in_array((int) $u->id, $not, true));
        $pool = $fresh->isNotEmpty() ? $fresh : $fit;

        $total = max(1, (int) $pool->sum(fn (AsAdUnit $u) => max(1, (int) $u->weight)));
        $roll = random_int(1, $total);
        foreach ($pool as $u) {
            $roll -= max(1, (int) $u->weight);
            if ($roll <= 0) {
                return $u;
            }
        }

        return $pool->first();
    }

    /**
     * Draw a unit for a slot on the page: picked among what fits, not one
     * already drawn on this page when another would do, and counted as
     * seen. What every ad slot asks.
     */
    public static function draw(string $placement): ?AsAdUnit
    {
        if (! self::showTo()) {
            return null;
        }
        $unit = self::pick($placement, self::$drawn);
        if ($unit) {
            self::$drawn[] = (int) $unit->id;
            self::seen($unit);
        }

        return $unit;
    }

    /** A unit that has what its kind needs to be drawn at all. */
    private static function renderable(AsAdUnit $u): bool
    {
        return match ($u->kind) {
            'adsense' => filled($u->adSlot) && filled($u->adClient ?: self::adsenseClient()),
            'image' => filled($u->imagePath),
            'script' => filled(trim((string) $u->scriptHtml)),
            default => false,
        };
    }

    /** The AdSense publisher id, when the mother app has set one. */
    public static function adsenseClient(): ?string
    {
        $client = trim((string) self::settings()->adsenseClient);

        return $client !== '' ? $client : null;
    }

    /** Whether the page should load AdSense's own script at all. */
    public static function wantsAdsenseScript(): bool
    {
        if (! self::showTo() || ! self::adsenseClient()) {
            return false;
        }

        return self::settings()->autoAds || self::units()->contains(fn (AsAdUnit $u) => $u->kind === 'adsense');
    }

    /** A slot was drawn on a page: one more impression, best-effort. */
    public static function seen(AsAdUnit $unit): void
    {
        try {
            AsAdUnit::where('id', $unit->id)->increment('impressions');
        } catch (\Throwable $e) {
            // Counting is not worth a failed page.
        }
    }
}
