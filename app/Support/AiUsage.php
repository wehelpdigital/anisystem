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
            DB::table('as_ai_usage')->insert([
                'kind' => mb_substr($kind, 0, 24),
                'userId' => $userId,
                'payerId' => $payerId,
                'refId' => $refId,
                'provider' => mb_substr((string) $settings->provider, 0, 16),
                'model' => mb_substr((string) $settings->effectiveModel(), 0, 80),
                'tokensIn' => $in,
                'tokensOut' => $out,
                'searched' => (bool) ($result['searched'] ?? false),
                'credits' => $credits,
                'meteredCredits' => $metered,
                'created_at' => now('Asia/Manila'),
                'updated_at' => now('Asia/Manila'),
            ]);
        } catch (\Throwable $e) {
            // Bookkeeping is not worth a failed report.
        }

        return ' · ' . self::k($in) . ' in / ' . self::k($out) . ' out ≈ ' . number_format($metered) . ' metered'
            . (! empty($result['searched']) ? ' + web search' : '');
    }

    private static function k(int $n): string
    {
        return $n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K' : (string) $n;
    }
}
