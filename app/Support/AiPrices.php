<?php

namespace App\Support;

use App\Models\AsSiteSetting;

/**
 * Anee's price list: what each flat-priced analysis costs, in credits.
 *
 * Flat, not metered: the number said before the run is exactly the number
 * charged, whatever the model spends. The defaults below are the prices
 * the owner set; the mother app (AniSystem AI > Anee's price list) can
 * change any of them without a deploy, and the change is read here on the
 * next request. Every run also writes what it actually cost the house
 * (App\Support\AiUsage), so the margin under each price can be seen and
 * the price moved when it should be.
 */
final class AiPrices
{
    public const KEY = 'ai.prices';

    /** The analyses, their default prices, and how they read on a screen. */
    /* The owner's price list of 2026-10-16. Every price here is what a run of
       the usual size costs; the pricing engine (App\Support\AiPricing) raises
       one only when a big job would earn under its target. The three the owner
       did not name (skyweather, npkplan, npkproto) keep the engine's 4x to 6x
       prices of the same day. */
    public const DEFAULTS = [
        'wtp' => 100,
        // When to Plant with a named variety: the research leads with the variety.
        'wtpVariety' => 150,
        'what' => 50,
        'variety' => 100,
        'protocol' => 150,
        // 2026-10-18, the Crop Protocol for several lots: the first lot is `protocol`, each
        // more lot adds `protocolLot`, each more crop or variety (read up on) `protocolCase`.
        'protocolLot' => 40,
        'protocolCase' => 70,
        'season' => 100,
        'sofar' => 50,
        'compare' => 30,
        'realign' => 30,
        'builder' => 100,
        // 2026-10-07: the satellite pair and the NPK Plus readings.
        'satellite' => 100,
        'skyweather' => 20,
        'npk' => 80,
        'npkplan' => 25,
        'npkproto' => 20,
        // 2026-10-12: Anee Auto, per lot, for the whole season. 1,500 since 2026-10-28 (the owner's pick; was 1,000):
        // the season writes its plan again until approved and includes its final report, about P207 a lot at Gemini's
        // rates (6.3x), with a season allowance of questions and observations a lot (AneeAutoController::SEASON_ASKS,
        // SEASON_OBSERVES) so a season that asks all day cannot cost more than it paid.
        'auto' => 1500,
        // Past that allowance, one more question or one more reading of the field (5x their cost, AiPricing kinds).
        'autoAsk' => 18,
        'autoObserve' => 25,
        // Satellite Weather, a week of the page (App\Support\SkyPass keeps it above cost).
        'skypass' => 50,
        // 2026-10-14: the LCC Checker, Anee reading leaf photos against the leaf color chart.
        'lcc' => 30,
        // 2026-10-14: the Land Prep Planner, Anee planning every lot's land preparation.
        // Per lot since 2026-10-16.
        'landprep' => 50,
        // 2026-10-18: the Compatibility Checker's online check, Anee looking up what the product book lacks.
        // The owner: a flat 80 whenever at least one product is not in the book, one product or six;
        // nothing (and no Anee) when the book knows them all. What she finds is kept, so it is asked once.
        'compat' => 80,
        // 2026-10-22: a Land Prep or Crop Protocol plan put into a season that already has work: Anee checks it
        // against the board (the other lots, the crew, what is already there) and places it. Priced by the work read.
        'fitcheck' => 25,
    ];

    public const NAMES = [
        'wtp' => 'When to Plant analysis',
        'wtpVariety' => 'When to Plant analysis with a named variety',
        'what' => 'What to Plant analysis',
        'variety' => 'Variety Ranking (searches the web)',
        'protocol' => 'Crop Protocol Analysis, one lot (season plan by growth stage, searches the web)',
        'protocolLot' => 'Crop Protocol Analysis, each lot after the first',
        'protocolCase' => 'Crop Protocol Analysis, each more crop or variety Anee reads up on',
        'season' => 'Anee Season Report',
        'sofar' => 'Analyze So Far report',
        'compare' => 'Comparison analysis',
        'realign' => 'Realign by Anee (growth stage)',
        'builder' => 'Protocol Builder review (Anee reads a protocol you wrote)',
        'satellite' => 'Satellite Analysis (Sentinel-2 and Sentinel-1 read of a drawn field, searches the web)',
        'skyweather' => 'Satellite Weather (clouds, storm tracks and the forecast, read against a lot)',
        'npk' => 'NPK Plus, analyzed by Anee (a fertilizer calculation, searches the web)',
        'npkplan' => 'NPK Plus season check (Activities, per lot)',
        'npkproto' => 'NPK Plus protocol check (Protocol Builder)',
        'auto' => 'Anee Auto, per lot (a whole season planned and kept on track by Anee)',
        'skypass' => 'Satellite Weather, a week pass (never sold below what a week costs the house)',
        'lcc' => 'LCC Checker (Anee reads rice leaf photos against the leaf color chart)',
        'landprep' => 'Land Prep Planner, per lot (every lot\'s land preparation, around the crew and the weather)',
        'compat' => 'Compatibility Checker, the online check (Anee looks up the products the book lacks, searches the web; a flat price whatever the count)',
        'fitcheck' => 'A plan put into a season that already has work (Anee checks it against the board and the crew, and places it)',
    ];

    /** @var array<string, int>|null */
    private static ?array $memo = null;

    /** The price of one analysis, in whole credits. */
    public static function of(string $key): int
    {
        return (int) (self::all()[$key] ?? self::DEFAULTS[$key] ?? 0);
    }

    /** @return array<string, int> every price, the owner's over the default */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        $set = json_decode((string) AsSiteSetting::get(self::KEY, ''), true);
        $out = self::DEFAULTS;
        foreach ((array) $set as $k => $v) {
            if (array_key_exists($k, $out) && is_numeric($v) && (int) $v >= 1) {
                $out[$k] = (int) $v;
            }
        }

        return self::$memo = $out;
    }
}
