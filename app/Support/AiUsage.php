<?php

namespace App\Support;

use App\Models\AiSetting;
use App\Services\AiCreditService;
use Illuminate\Support\Facades\DB;

/**
 * The house's side of every flat-priced run: what it cost in tokens next
 * to what it charged in credits. Written after the charge, best-effort --
 * the farmer's report never waits on bookkeeping -- and read by the mother
 * app's price list, where the margin under each price is shown.
 */
final class AiUsage
{
    /**
     * Record a run and hand back a short note for the ledger line, e.g.
     * " · 3.1K in / 5.2K out ≈ 30 metered", so the credit log itself shows
     * what the flat price covered.
     *
     * @param  array{tokensIn?:int,tokensOut?:int,searched?:bool}  $result
     */
    public static function record(string $kind, int $userId, int $payerId, ?int $refId, AiSetting $settings, array $result, int $credits): string
    {
        $in = (int) ($result['tokensIn'] ?? 0);
        $out = (int) ($result['tokensOut'] ?? 0);
        $metered = app(AiCreditService::class)->priceFor($settings, $in, $out);
        try {
            // Of the tokens in, those the provider's prefix cache served (2026-10-19): measured, so the owner
            // can see whether caching ever applies before any price counts on it.
            $cached = self::hasCached() ? ['tokensCached' => min($in, max(0, (int) ($result['tokensCached'] ?? 0)))] : [];
            DB::table('as_ai_usage')->insert($cached + [
                'kind' => mb_substr($kind, 0, 24),
                'userId' => $userId,
                'payerId' => $payerId,
                'refId' => $refId,
                // The model that answered, when the run says (Claude first answers on the owner's key: ClaudeFirst).
                'provider' => mb_substr((string) (($result['provider'] ?? '') ?: $settings->provider), 0, 16),
                'model' => mb_substr((string) (($result['model'] ?? '') ?: $settings->effectiveModel()), 0, 80),
                'tokensIn' => $in,
                'tokensOut' => $out,
                // A search paid for by this run (a kept brief's notes cost nothing again: ResearchShelf).
                'searched' => (bool) ($result['paidSearch'] ?? ($result['searched'] ?? false)),
                'credits' => $credits,
                'meteredCredits' => $metered,
                'created_at' => now('Asia/Manila'),
                'updated_at' => now('Asia/Manila'),
            ]);
        } catch (\Throwable $e) {
            // Bookkeeping is not worth a failed report.
        }

        return ' · ' . self::k($in) . ' in / ' . self::k($out) . ' out ≈ ' . number_format($metered) . ' metered'
            . (! empty($result['paidSearch'] ?? $result['searched'] ?? false) ? ' + web search' : (! empty($result['shelf']) ? ' + kept research' : ''));
    }

    /** Whether the ledger has the cached tokens column yet (it arrives by migration 2026_10_19_200000). */
    private static function hasCached(): bool
    {
        static $has = null;

        return $has ??= (bool) \Illuminate\Support\Facades\Cache::remember('ai-usage:has-cached', 3600,
            fn () => \Illuminate\Support\Facades\Schema::hasColumn('as_ai_usage', 'tokensCached'));
    }

    private static function k(int $n): string
    {
        return $n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K' : (string) $n;
    }
}
