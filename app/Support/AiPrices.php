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
    public const DEFAULTS = [
        'wtp' => 100,
        'what' => 100,
        'variety' => 120,
        'protocol' => 150,
        'season' => 300,
        'sofar' => 200,
        'compare' => 30,
        'realign' => 60,
        'builder' => 100,
    ];

    public const NAMES = [
        'wtp' => 'When to Plant analysis',
        'what' => 'What to Plant analysis',
        'variety' => 'Variety research & comparison (searches the web)',
        'protocol' => 'Crop Protocol Analysis (season plan by growth stage, searches the web)',
        'season' => 'Anee Season Report',
        'sofar' => 'Analyze So Far report',
        'compare' => 'Comparison analysis',
        'realign' => 'Realign by Anee (growth stage)',
        'builder' => 'Protocol Builder review (Anee reads a protocol you wrote)',
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
