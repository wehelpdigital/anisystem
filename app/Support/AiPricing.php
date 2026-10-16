<?php

namespace App\Support;

use App\Models\AiSetting;
use App\Models\AsSiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Credits that scale with the job and keep the house's margin (2026-10-15,
 * the owner: "some users might have really lots of lots ... we want a 6x to
 * 10x profit margin, always, in the credits"). LOWERED 2026-10-16 (the owner:
 * "let's lower the cost of the anee products ... what if we just make it 4-6x
 * profit"): the target is 5x, the floor 4x and the ceiling 6x. The same day
 * the owner set the price list himself (App\Support\AiPrices), so the flat
 * prices are the floor again (flatFloor on) and the engine only raises a
 * price when a big job would earn under the target.
 *
 * WHAT A RUN COSTS THE HOUSE is modelled per kind at the 90th percentile
 * (p90), never the mean, so a heavy farm cannot drag a run under the floor:
 *
 *   tokens in  = baseIn  + unitIn  x (units beyond the free ones)
 *   tokens out = baseOut + unitOut x (units beyond the free ones), capped at outCap
 *   web search = grounded calls x queries per call x the fee per query
 *   third party = Open-Meteo weighted calls, Google map loads, mail, Earth Engine, other fees
 *
 * all at list prices (unit prices below, in US dollars) times the peso rate.
 *
 * WHAT A CREDIT IS WORTH is the cheapest a credit can be bought: the
 * cheapest active peso pack, and the dollar packs while the international
 * face is open (pesoPerCredit(), with an owner override). The margin has to
 * hold there, not at the list price of the starter pack.
 *
 * THE PRICE is friendly(target x cost / peso per credit). With flatFloor on
 * (the rule until 2026-10-16) today's flat price (App\Support\AiPrices) is a
 * floor under it and the engine never lowers a price; off, the flat price is
 * used only for a kind with no cost model.
 *
 * Every number lives on the as_site_settings shelf under `ai.pricing`
 * (JSON), edited in the mother app (AniSystem AI > Pricing engine). The
 * defaults below are the analysis of 2026-10-15 (as_ai_usage plus the code
 * of every tool); the mother app's twin (btc-check App\Support\AiPricingModel)
 * carries the same defaults and must be kept in step with this file.
 *
 * After a run, audit() compares what the run really cost with what it
 * charged and logs any run under the floor (4x), so the model can be tuned.
 */
final class AiPricing
{
    public const KEY = 'ai.pricing';

    /** The cheapest credit when nothing can be read (the Whole Season pack, 1,150 for ₱1,000). */
    public const FALLBACK_PESO_PER_CREDIT = 0.87;

    /**
     * USD per million tokens, [input, output], by provider and model family
     * (list prices, 2026-10-15; matched loosely on the model name, first hit
     * wins). gemini-pro-latest points at Gemini 3.1 Pro (Google's changelog,
     * 2026-03-09). Gemini 4 Argon is $2/$10 to Nov 30, then $4/$20.
     */
    public const MODEL_RATES = [
        'gemini' => ['argon' => [2.00, 10.00], 'flash-lite' => [0.10, 0.40], 'flash' => [0.75, 3.75], 'pro' => [2.00, 12.00], '*' => [2.00, 12.00]],
        'claude' => ['fable' => [10.00, 50.00], 'opus' => [4.00, 20.00], 'haiku' => [0.10, 0.50], 'sonnet' => [2.00, 10.00], '*' => [2.00, 10.00]],
        'openai' => ['nano' => [0.10, 0.40], 'mini' => [0.15, 0.60], 'gpt-5' => [1.25, 10.00], 'gpt-4.1' => [2.00, 8.00], 'gpt-4o' => [2.50, 10.00], '*' => [2.50, 10.00]],
    ];

    /** USD per web search query (Gemini 3 grounding bills each query; Claude each search; OpenAI each searched call). */
    public const SEARCH_FEE = ['gemini' => 0.014, 'claude' => 0.010, 'openai' => 0.030];

    public const DEFAULTS = [
        // Off: every price is today's flat price (App\Support\AiPrices), as before.
        'on' => true,
        // 4x to 6x since 2026-10-16 (was 8x in 6x to 10x).
        'target' => 5.0,
        'minMargin' => 4.0,
        'maxMargin' => 6.0,
        // The owner's price list (AiPrices) as the floor under the engine. Off: the engine sets every price at the target.
        'flatFloor' => true,
        // Pesos per credit; null = the cheapest channel, computed (pesoPerCredit()).
        'pesoPerCredit' => null,
        'usdRate' => 58.0,
        'prices' => [
            // USD per million tokens for the model Anee uses now; null = MODEL_RATES.
            'modelIn' => null,
            'modelOut' => null,
            // USD per search query; null = SEARCH_FEE for the provider.
            'searchQuery' => null,
            // Queries one grounded call makes, at p90 (as_ai_usage keeps only searched yes or no).
            'queriesPerSearch' => 8,
            // Open-Meteo Professional, $99 a month, spread over 500,000 weighted calls a month.
            'openMeteoCall' => 0.000198,
            // Google Maps JavaScript, a dynamic map load past the free 10,000 a month.
            'mapsLoad' => 0.007,
            // Resend Pro, $20 for 50,000 mails.
            'emailSend' => 0.0004,
            // One Earth Engine field read (EECU time plus Cloud Run plus the $500 platform fee spread); 0 while Planetary Computer serves the reads.
            'earthEngineRun' => 0.0,
        ],
        /*
         * Per kind, p90. unit: what makes the job bigger ('' = one size).
         * free: units the base already covers. units: the size the margin
         * table shows as typical. perUnitPrice: today's flat price is per
         * unit (npkplan, auto). om / omUnit: Open-Meteo weighted calls per
         * run / per unit. maps / mapsUnit: Google map loads. mail: mails per
         * run. ee: Earth Engine reads per run. usd / unitUsd: any other fee.
         * opts: extra blocks a run may add (landprep's variety lookup).
         */
        'kinds' => [
            // A named variety: the research leads with it and the report times by it (measured 2026-10: ₱12 to ₱13 a run without, ₱15 to ₱22 with).
            'wtp' => ['unit' => '', 'baseIn' => 10500, 'baseOut' => 8000, 'search' => 1, 'opts' => ['variety' => ['in' => 4500, 'out' => 10500]]],
            'what' => ['unit' => 'crop', 'min' => 0, 'free' => 0, 'units' => 3, 'max' => 16, 'baseIn' => 7200, 'baseOut' => 11300, 'unitIn' => 20, 'unitOut' => 230, 'outCap' => 20048],
            'variety' => ['unit' => 'variety', 'free' => 3, 'units' => 3, 'max' => 8, 'baseIn' => 10700, 'baseOut' => 11000, 'unitIn' => 250, 'unitOut' => 120, 'search' => 1, 'om' => 1],
            'protocol' => ['unit' => '', 'baseIn' => 16000, 'baseOut' => 16000, 'search' => 1, 'om' => 1],
            'season' => ['unit' => 'lot', 'free' => 1, 'units' => 2, 'max' => 200, 'baseIn' => 9000, 'baseOut' => 6000, 'unitIn' => 250, 'unitOut' => 80, 'outCap' => 10048, 'om' => 10],
            'sofar' => ['unit' => 'lot', 'free' => 1, 'units' => 2, 'max' => 200, 'baseIn' => 11500, 'baseOut' => 5000, 'unitIn' => 250, 'unitOut' => 80, 'outCap' => 10048, 'om' => 10],
            'compare' => ['unit' => '', 'baseIn' => 7000, 'baseOut' => 3200],
            'realign' => ['unit' => '', 'baseIn' => 7500, 'baseOut' => 3000, 'om' => 10],
            'builder' => ['unit' => 'task', 'free' => 40, 'units' => 31, 'max' => 300, 'baseIn' => 7000, 'baseOut' => 5000, 'unitIn' => 100, 'unitOut' => 10, 'outCap' => 9048],
            'satellite' => ['unit' => '', 'baseIn' => 10000, 'baseOut' => 8000, 'search' => 1, 'om' => 15, 'maps' => 2, 'ee' => 1],
            'skyweather' => ['unit' => '', 'baseIn' => 4500, 'baseOut' => 3000, 'om' => 3],
            'npk' => ['unit' => 'line', 'free' => 10, 'units' => 6, 'max' => 60, 'baseIn' => 7500, 'baseOut' => 6500, 'unitIn' => 50, 'unitOut' => 5, 'search' => 1],
            'npkplan' => ['unit' => 'lot', 'free' => 1, 'units' => 1, 'max' => 8, 'perUnitPrice' => true, 'baseIn' => 4500, 'baseOut' => 4500, 'unitIn' => 1400, 'unitOut' => 600, 'outCap' => 9048, 'om' => 2, 'omUnit' => 1],
            'npkproto' => ['unit' => 'task', 'free' => 10, 'units' => 6, 'max' => 120, 'baseIn' => 4000, 'baseOut' => 4000, 'unitIn' => 60, 'unitOut' => 40, 'outCap' => 8048],
            // Setup (Anee's reading plus one calendar per lot) and the season's own use of Observe and Ask per lot.
            'auto' => ['unit' => 'lot', 'free' => 1, 'units' => 1, 'max' => 20, 'perUnitPrice' => true, 'baseIn' => 35000, 'baseOut' => 45000, 'unitIn' => 13000, 'unitOut' => 24000,
                'search' => 1, 'om' => 10, 'omUnit' => 2, 'maps' => 1, 'mail' => 150, 'useObserve' => 10, 'useAsk' => 30],
            'autoObserve' => ['unit' => '', 'baseIn' => 11000, 'baseOut' => 4500],
            'autoAsk' => ['unit' => '', 'baseIn' => 12000, 'baseOut' => 2500],
            // A week of Satellite Weather: per opening a Google map load plus the shared forecast grid and the tiles.
            'skypass' => ['unit' => 'open', 'free' => 0, 'units' => 14, 'max' => 40, 'mapsUnit' => 1, 'unitUsd' => 0.0017],
            'lcc' => ['unit' => 'leaf', 'free' => 10, 'units' => 10, 'max' => 12, 'baseIn' => 4500, 'baseOut' => 3600, 'unitOut' => 150, 'outCap' => 5048],
            'landprep' => ['unit' => 'lot', 'free' => 1, 'units' => 1, 'max' => 12, 'perUnitPrice' => true, 'baseIn' => 7000, 'baseOut' => 4500, 'unitIn' => 400, 'unitOut' => 250, 'outCap' => 8048, 'om' => 3,
                'opts' => ['research' => ['in' => 2600, 'out' => 3800, 'search' => 1]]],
        ],
    ];

    /** A job option with its own price on the list: When to Plant with a named variety is 150, not 100. */
    public const OPT_PRICES = ['wtp' => ['variety' => 'wtpVariety']];

    /** How each unit reads in the margin table and the why line. */
    public const UNIT_WORDS = [
        '' => ['run', 'runs'], 'lot' => ['lot', 'lots'], 'crop' => ['crop named', 'crops named'], 'variety' => ['variety', 'varieties'],
        'task' => ['task', 'tasks'], 'line' => ['fertilizer line', 'fertilizer lines'], 'leaf' => ['leaf', 'leaves'], 'open' => ['opening', 'openings'],
    ];

    /** @var array<string, mixed>|null */
    private static ?array $memo = null;

    private static ?float $ppcMemo = null;

    /** Anee's settings row, read once a request (a margin table quotes dozens of sizes). */
    private static ?AiSetting $aiMemo = null;

    /* ============================================================ settings */

    /** The owner's numbers over the defaults. */
    public static function settings(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        $out = self::DEFAULTS;
        try {
            $set = json_decode((string) AsSiteSetting::get(self::KEY, ''), true);
        } catch (\Throwable $e) {
            $set = null;
        }
        if (is_array($set)) {
            if (array_key_exists('on', $set)) {
                $out['on'] = (bool) $set['on'];
            }
            if (array_key_exists('flatFloor', $set)) {
                $out['flatFloor'] = (bool) $set['flatFloor'];
            }
            foreach (['target', 'minMargin', 'maxMargin', 'usdRate'] as $k) {
                if (isset($set[$k]) && is_numeric($set[$k]) && (float) $set[$k] > 0) {
                    $out[$k] = (float) $set[$k];
                }
            }
            if (isset($set['pesoPerCredit']) && is_numeric($set['pesoPerCredit']) && (float) $set['pesoPerCredit'] > 0) {
                $out['pesoPerCredit'] = (float) $set['pesoPerCredit'];
            }
            foreach ((array) ($set['prices'] ?? []) as $k => $v) {
                if (array_key_exists($k, $out['prices']) && is_numeric($v) && (float) $v >= 0) {
                    $out['prices'][$k] = (float) $v;
                }
            }
            foreach ((array) ($set['kinds'] ?? []) as $kind => $row) {
                if (! isset($out['kinds'][$kind]) || ! is_array($row)) {
                    continue;
                }
                foreach ($row as $k => $v) {
                    if ($k === 'opts' || $k === 'unit' || $k === 'perUnitPrice') {
                        continue;
                    }
                    if (is_numeric($v) && (float) $v >= 0) {
                        $out['kinds'][$kind][$k] = (float) $v;
                    }
                }
            }
        }
        // A target under the floor would sell under the floor.
        $out['minMargin'] = max(1.0, $out['minMargin']);
        $out['target'] = max($out['minMargin'], $out['target']);
        $out['maxMargin'] = max($out['target'], $out['maxMargin']);

        return self::$memo = $out;
    }

    /** Forget what was read this request (after the owner saves, or in a long job). */
    public static function forget(): void
    {
        self::$memo = null;
        self::$ppcMemo = null;
        self::$aiMemo = null;
    }

    /** One kind's model, with every field present. */
    public static function kind(string $kind): ?array
    {
        $k = self::settings()['kinds'][$kind] ?? null;
        if ($k === null) {
            return null;
        }

        return $k + ['unit' => '', 'min' => 1, 'free' => 1, 'units' => 1, 'max' => 1, 'perUnitPrice' => false, 'baseIn' => 0, 'baseOut' => 0, 'unitIn' => 0, 'unitOut' => 0,
            'outCap' => 0, 'search' => 0, 'om' => 0, 'omUnit' => 0, 'maps' => 0, 'mapsUnit' => 0, 'mail' => 0, 'ee' => 0, 'usd' => 0, 'unitUsd' => 0,
            'useObserve' => 0, 'useAsk' => 0, 'opts' => []];
    }

    /* ============================================================ unit prices */

    /** [USD per million in, USD per million out, USD per search query] for the model Anee uses now. */
    public static function modelRates(?AiSetting $settings = null): array
    {
        $p = self::settings()['prices'];
        try {
            $settings ??= (self::$aiMemo ??= AiSetting::current());
            $provider = (string) $settings->provider;
            $model = strtolower((string) $settings->effectiveModel());
        } catch (\Throwable $e) {
            $provider = 'gemini';
            $model = 'gemini-pro-latest';
        }
        $table = self::MODEL_RATES[$provider] ?? self::MODEL_RATES['gemini'];
        $rate = $table['*'];
        foreach ($table as $family => $r) {
            if ($family !== '*' && str_contains($model, $family)) {
                $rate = $r;
                break;
            }
        }

        return [
            $p['modelIn'] !== null ? (float) $p['modelIn'] : $rate[0],
            $p['modelOut'] !== null ? (float) $p['modelOut'] : $rate[1],
            $p['searchQuery'] !== null ? (float) $p['searchQuery'] : (self::SEARCH_FEE[$provider] ?? 0.014),
        ];
    }

    /* ============================================================ the credit */

    /**
     * Every way a credit is bought, in pesos per credit, and whether it
     * counts toward the floor today. Free credits (sign up, Libre starter,
     * admin grants) are listed for the owner but never set the floor: no
     * margin holds on a credit given away.
     *
     * @return array<int, array{channel:string, pesoPerCredit:float, counts:bool, note:string}>
     */
    public static function channels(): array
    {
        $rate = (float) self::settings()['usdRate'];
        $intl = (bool) config('app.international_open', false);
        $out = [];
        try {
            $packs = DB::table('anisystem_ai_credit_packs')->where('deleteStatus', 1)->where('isActive', 1)->orderBy('sortOrder')->get();
        } catch (\Throwable $e) {
            $packs = collect();
        }
        $usd = [];
        try {
            $usd = (array) (Region::usd()['packs'] ?? []);
        } catch (\Throwable $e) {
        }
        foreach ($packs as $p) {
            if ((int) $p->credits <= 0) {
                continue;
            }
            $out[] = ['channel' => $p->packName . ' pack (₱' . number_format((float) $p->price, 0) . ' for ' . number_format((int) $p->credits) . ')',
                'pesoPerCredit' => round((float) $p->price / (int) $p->credits, 4), 'counts' => true, 'note' => 'Sold in the Philippines'];
            $dollars = $usd[$p->packKey] ?? null;
            if (is_numeric($dollars) && (float) $dollars > 0) {
                $out[] = ['channel' => $p->packName . ' pack abroad ($' . rtrim(rtrim(number_format((float) $dollars, 2), '0'), '.') . ' for ' . number_format((int) $p->credits) . ')',
                    'pesoPerCredit' => round((float) $dollars * $rate / (int) $p->credits, 4), 'counts' => $intl,
                    'note' => $intl ? 'Sold outside the Philippines' : 'International is closed, so it does not set the floor today'];
            }
        }
        foreach (['solo', 'owner'] as $tier) {
            $t = (array) config('tiers.' . $tier, []);
            $m = (int) ($t['creditsMonthly'] ?? 0);
            if ($m > 0 && ! empty($t['price'])) {
                $out[] = ['channel' => ($t['name'] ?? ucfirst($tier)) . ' plan credits (₱' . number_format((float) $t['price'], 0) . ' a month with ' . $m . ')',
                    'pesoPerCredit' => round((float) $t['price'] / $m, 4), 'counts' => false, 'note' => 'Bundled with the plan, valued at the whole plan price'];
            }
        }
        try {
            $signup = (int) AiSetting::current()->freeCreditsOnSignup;
        } catch (\Throwable $e) {
            $signup = 0;
        }
        $out[] = ['channel' => 'Sign up gift (' . $signup . ' credits), Libre starter (' . (int) config('tiers.libre.creditsOnGrant', 0) . '), admin grants',
            'pesoPerCredit' => 0.0, 'counts' => false, 'note' => 'Free, so no margin can hold on them'];

        return $out;
    }

    /** The cheapest a credit is bought at today: the owner's override, else the cheapest channel that counts. */
    public static function pesoPerCredit(): float
    {
        if (self::$ppcMemo !== null) {
            return self::$ppcMemo;
        }
        $set = self::settings()['pesoPerCredit'];
        if ($set !== null && (float) $set > 0) {
            return self::$ppcMemo = (float) $set;
        }
        $low = Cache::remember('ai-pricing:ppc:' . md5(json_encode([self::settings()['usdRate'], (bool) config('app.international_open', false)])), 600, function () {
            $counted = array_filter(self::channels(), fn ($c) => $c['counts'] && $c['pesoPerCredit'] > 0);

            return $counted ? min(array_column($counted, 'pesoPerCredit')) : self::FALLBACK_PESO_PER_CREDIT;
        });

        return self::$ppcMemo = max(0.01, (float) $low);
    }

    /* ============================================================ the quote */

    /** The units a size asks for, within the kind's own bounds. */
    public static function units(string $kind, array $size = []): int
    {
        $k = self::kind($kind);
        if (! $k || $k['unit'] === '') {
            return 1;
        }
        $plural = ['leaf' => 'leaves', 'variety' => 'varieties'][$k['unit']] ?? $k['unit'] . 's';
        $u = $size['units'] ?? $size[$k['unit']] ?? $size[$plural] ?? null;
        if ($u === null) {
            // Not said: the typical size (the week pass reads its own from the passes that ended).
            $u = $kind === 'skypass' ? self::skypassOpens() : $k['units'];
        }
        $max = (int) $k['max'] > 0 ? (int) $k['max'] : PHP_INT_MAX;

        return max((int) $k['min'], min($max, (int) ceil((float) $u)));
    }

    /**
     * What one run of this size costs the house, at p90, in pesos, with the
     * parts it is made of.
     *
     * @return array{units:int, tokensIn:int, tokensOut:int, searches:float, aiPhp:float, searchPhp:float, thirdPhp:float, costPhp:float, unitPhp:float}
     */
    public static function cost(string $kind, array $size = [], ?AiSetting $settings = null): array
    {
        $k = self::kind($kind);
        $s = self::settings();
        $p = $s['prices'];
        $rate = (float) $s['usdRate'];
        [$pin, $pout, $fee] = self::modelRates($settings);
        $u = self::units($kind, $size);
        $extra = max(0, $u - (int) $k['free']);

        $in = $k['baseIn'] + $k['unitIn'] * $extra;
        $out = $k['baseOut'] + $k['unitOut'] * $extra;
        if ($k['outCap'] > 0) {
            $out = min($out, max($k['baseOut'], $k['outCap']));
        }
        $search = (float) $k['search'];
        foreach ((array) $k['opts'] as $opt => $add) {
            if (! empty($size[$opt])) {
                $in += (float) ($add['in'] ?? 0);
                $out += (float) ($add['out'] ?? 0);
                $search += (float) ($add['search'] ?? 0);
            }
        }
        // Anee Auto: the season's Observe and Ask, per lot, at their own cost.
        $useUsd = 0.0;
        if ($k['useObserve'] > 0 || $k['useAsk'] > 0) {
            foreach (['autoObserve' => $k['useObserve'], 'autoAsk' => $k['useAsk']] as $sub => $n) {
                $sk = self::kind($sub);
                if ($sk && $n > 0) {
                    $useUsd += $n * $u * (($sk['baseIn'] * $pin + $sk['baseOut'] * $pout) / 1e6);
                }
            }
        }
        $aiUsd = ($in * $pin + $out * $pout) / 1e6 + $useUsd;
        $searchUsd = $search * (float) $p['queriesPerSearch'] * $fee;
        $thirdUsd = ($k['om'] + $k['omUnit'] * $u) * (float) $p['openMeteoCall']
            + ($k['maps'] + $k['mapsUnit'] * $u) * (float) $p['mapsLoad']
            + $k['mail'] * (float) $p['emailSend']
            + $k['ee'] * (float) $p['earthEngineRun']
            + $k['usd'] + $k['unitUsd'] * $u;
        // What one more unit adds, for the "per unit" line.
        $unitUsd = ($k['unitIn'] * $pin + $k['unitOut'] * $pout) / 1e6 + $k['omUnit'] * (float) $p['openMeteoCall'] + $k['mapsUnit'] * (float) $p['mapsLoad'] + $k['unitUsd']
            + ($u > 0 ? $useUsd / $u : 0);

        return [
            'units' => $u, 'tokensIn' => (int) round($in), 'tokensOut' => (int) round($out), 'searches' => $search,
            'aiPhp' => round($aiUsd * $rate, 2), 'searchPhp' => round($searchUsd * $rate, 2), 'thirdPhp' => round($thirdUsd * $rate, 2),
            'costPhp' => round(($aiUsd + $searchUsd + $thirdUsd) * $rate, 2), 'unitPhp' => round($unitUsd * $rate, 3),
        ];
    }

    /**
     * The price of a run of this size, in whole credits, and why.
     *
     * @return array{credits:int, base:int, perUnit:float, units:int, unitWord:string, costPhp:float, margin:float, flat:int, raised:bool, under:bool, over:bool, pesoPerCredit:float, why:string}
     */
    public static function quote(string $kind, array $size = [], ?AiSetting $settings = null): array
    {
        $s = self::settings();
        $k = self::kind($kind);
        $flatOne = AiPrices::of($kind);
        foreach (self::OPT_PRICES[$kind] ?? [] as $opt => $key) {
            if (! empty($size[$opt])) {
                $flatOne = max($flatOne, AiPrices::of($key));
            }
        }
        if (! $k) {
            return ['credits' => $flatOne, 'base' => $flatOne, 'perUnit' => 0.0, 'units' => 1, 'unitWord' => 'run', 'costPhp' => 0.0, 'margin' => 0.0, 'flat' => $flatOne,
                'raised' => false, 'under' => false, 'over' => false, 'pesoPerCredit' => self::pesoPerCredit(), 'why' => 'No cost model for this kind: today\'s flat price.'];
        }
        $c = self::cost($kind, $size, $settings);
        $u = $c['units'];
        $ppc = self::pesoPerCredit();
        $flat = $flatOne * ($k['perUnitPrice'] ? $u : 1);
        $raw = $c['costPhp'] > 0 ? (int) ceil($s['target'] * $c['costPhp'] / $ppc - 1e-9) : 0;
        $engine = self::friendly($raw);
        $credits = ! $s['on'] ? $flat : ($s['flatFloor'] ? max($flat, $engine) : ($engine > 0 ? $engine : $flat));
        $margin = $c['costPhp'] > 0 ? round($credits * $ppc / $c['costPhp'], 2) : 0.0;
        $words = self::UNIT_WORDS[$k['unit']] ?? [$k['unit'], $k['unit'] . 's'];
        // The base: the same kind at its free size (one lot, one run, no crops named).
        $baseUnits = max((int) $k['min'], (int) $k['free']);
        $base = $k['unit'] === '' || $u <= $baseUnits ? $credits
            : (int) self::quote($kind, ['units' => $baseUnits] + $size, $settings)['credits'];
        $perUnit = round($s['target'] * $c['unitPhp'] / $ppc, 1);

        $why = 'At p90 a ' . ($k['unit'] === '' ? 'run' : 'run of ' . $u . ' ' . ($u === 1 ? $words[0] : $words[1]))
            . ' costs the house ₱' . number_format($c['costPhp'], 2)
            . ' (' . number_format($c['tokensIn'] / 1000, 1) . 'K in, ' . number_format($c['tokensOut'] / 1000, 1) . 'K out'
            . ($c['searches'] > 0 ? ', web search ₱' . number_format($c['searchPhp'], 2) : '')
            . ($c['thirdPhp'] > 0 ? ', other services ₱' . number_format($c['thirdPhp'], 2) : '') . ').'
            . ' A credit sells for as little as ₱' . number_format($ppc, 3) . ', so ' . rtrim(rtrim(number_format($s['target'], 1), '0'), '.') . 'x needs ' . $engine . ' credits.'
            . ' Today\'s flat price is ' . $flat . '. ' . (! $s['on'] ? 'The engine is off, so the flat price stands.'
                : ($credits > $flat ? 'Raised to ' . $credits . '.' : ($credits < $flat ? 'Lowered to ' . $credits . '.' : 'The flat price stands.')))
            . ' Margin ' . number_format($margin, 1) . 'x.';

        return [
            'credits' => (int) $credits, 'base' => (int) $base, 'perUnit' => $perUnit, 'units' => $u, 'unitWord' => $u === 1 ? $words[0] : $words[1],
            'costPhp' => $c['costPhp'], 'margin' => $margin, 'flat' => (int) $flat, 'raised' => $credits > $flat,
            'under' => $margin < $s['minMargin'], 'over' => $margin > $s['maxMargin'], 'pesoPerCredit' => $ppc, 'why' => $why,
        ];
    }

    /** Just the credits. */
    public static function credits(string $kind, array $size = []): int
    {
        return self::quote($kind, $size)['credits'];
    }

    /**
     * One price per unit that covers every size, for a page that shows "N
     * credits per lot" and multiplies (NPK Plus season check, Anee Auto):
     * the highest per unit price any size needs, so price x units is never
     * under the quote for that size.
     */
    public static function linearPrice(string $kind): int
    {
        $k = self::kind($kind);
        if (! $k) {
            return AiPrices::of($kind);
        }
        $per = 0;
        $top = min(200, max(1, (int) $k['max']));
        for ($n = max(1, (int) $k['min']); $n <= $top; $n++) {
            $per = max($per, (int) ceil(self::quote($kind, ['units' => $n])['credits'] / $n));
        }

        $s = self::settings();

        return $s['on'] && ! $s['flatFloor'] && $per > 0 ? self::friendly($per) : max(AiPrices::of($kind), self::friendly($per));
    }

    /** Fives under a hundred, tens under a thousand, fifties above. Always up. */
    public static function friendly(int $credits): int
    {
        if ($credits <= 0) {
            return 0;
        }
        $step = $credits < 100 ? 5 : ($credits < 1000 ? 10 : 50);

        return (int) (ceil($credits / $step) * $step);
    }

    /**
     * The price said before the run is the price charged. A page that shows
     * a scaled quote sends it back as `quote`; a page that does not (an older
     * view) is charged the flat price it was shown. A quote that no longer
     * matches (the job grew, the owner changed a number) is refused, so the
     * page can show the new price: null means "say the new price first".
     */
    public static function agreed(Request $request, array $quote, int $shownWithoutQuote, string $param = 'quote'): ?int
    {
        if (! $request->filled($param) || ! is_numeric($request->input($param))) {
            return $shownWithoutQuote;
        }

        return (int) round((float) $request->input($param)) === (int) $quote['credits'] ? (int) $quote['credits'] : null;
    }

    /* ============================================================ after the run */

    /** What a finished run really cost, in pesos, from its tokens. */
    public static function runCost(array $usage, ?AiSetting $settings = null, float $thirdPhp = 0.0): float
    {
        $s = self::settings();
        [$pin, $pout, $fee] = self::modelRates($settings);
        $usd = ((int) ($usage['tokensIn'] ?? 0) * $pin + (int) ($usage['tokensOut'] ?? 0) * $pout) / 1e6
            + (! empty($usage['searched']) ? (float) ($usage['searchCalls'] ?? 1) * (float) $s['prices']['queriesPerSearch'] * $fee : 0.0);

        return round($usd * (float) $s['usdRate'] + $thirdPhp, 2);
    }

    /**
     * The guard after a run: what it cost against what it charged, at the
     * cheapest credit. A run under the floor (4x) is logged so the model can
     * be tuned; the margin is handed back. Never throws.
     */
    public static function audit(string $kind, array $usageResult, float $charged, array $size = [], ?AiSetting $settings = null): ?float
    {
        try {
            $k = self::kind($kind);
            $third = 0.0;
            if ($k) {
                // The run's own third party calls, as modelled (the tokens are measured, these are not).
                $c = self::cost($kind, $size, $settings);
                $third = $c['thirdPhp'];
            }
            $cost = self::runCost($usageResult, $settings, $third);
            if ($cost <= 0 || $charged <= 0) {
                return null;
            }
            $margin = round($charged * self::pesoPerCredit() / $cost, 2);
            $s = self::settings();
            if ($margin < $s['minMargin']) {
                Log::warning('ai-pricing: a run came in under the margin floor', [
                    'kind' => $kind, 'margin' => $margin, 'floor' => $s['minMargin'], 'charged' => $charged, 'costPhp' => $cost,
                    'tokensIn' => (int) ($usageResult['tokensIn'] ?? 0), 'tokensOut' => (int) ($usageResult['tokensOut'] ?? 0),
                    'searched' => ! empty($usageResult['searched']), 'size' => $size, 'pesoPerCredit' => self::pesoPerCredit(),
                ]);
            }

            return $margin;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* ============================================================ the metered chat */

    /**
     * The chat is metered per thousand tokens (AiSetting creditsPerInputK /
     * creditsPerOutputK), so its margin is a ratio, not a quote: what a
     * thousand tokens sell for at the cheapest credit over what they cost.
     *
     * @return array{inMargin:float, outMargin:float, perInputK:float, perOutputK:float, okInputK:float, okOutputK:float}
     */
    public static function chatCheck(?AiSetting $settings = null): array
    {
        $settings ??= AiSetting::current();
        $s = self::settings();
        [$pin, $pout] = self::modelRates($settings);
        $ppc = self::pesoPerCredit();
        $costIn = $pin / 1000 * $s['usdRate'];
        $costOut = $pout / 1000 * $s['usdRate'];
        $inK = (float) $settings->creditsPerInputK;
        $outK = (float) $settings->creditsPerOutputK;

        return [
            'perInputK' => $inK, 'perOutputK' => $outK,
            'inMargin' => $costIn > 0 ? round($inK * $ppc / $costIn, 2) : 0.0,
            'outMargin' => $costOut > 0 ? round($outK * $ppc / $costOut, 2) : 0.0,
            // The rates that would hold the floor.
            'okInputK' => round(ceil($s['minMargin'] * $costIn / $ppc * 10) / 10, 1),
            'okOutputK' => round(ceil($s['minMargin'] * $costOut / $ppc * 10) / 10, 1),
        ];
    }

    /* ============================================================ the week pass */

    /**
     * Openings per pass at p90, read from the passes that have ended once
     * there are ten of them; the model's own number until then.
     */
    public static function skypassOpens(): int
    {
        $default = (int) (self::kind('skypass')['units'] ?? 14);
        try {
            return (int) Cache::remember('ai-pricing:skypass-opens', 21600, function () use ($default) {
                if (! Schema::hasTable('as_sky_passes')) {
                    return $default;
                }
                $opens = DB::table('as_sky_passes')->where('deleteStatus', 1)->where('endsAt', '<', now())->orderBy('opens')->pluck('opens')->map(fn ($o) => (int) $o)->all();
                if (count($opens) < 10) {
                    return $default;
                }

                return max(1, $opens[(int) floor(0.9 * (count($opens) - 1))]);
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
