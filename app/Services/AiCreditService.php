<?php

namespace App\Services;

use App\Models\AiCreditLedger;
use App\Models\AiSetting;
use Illuminate\Support\Facades\DB;

/**
 * AI Credits: the client-facing unit of AI usage.
 *
 * The balance is never stored as a mutable number — it is the sum of the
 * ledger's deltas, so it can always be recomputed and audited. Every write
 * takes a row lock over the client's ledger so two concurrent answers cannot
 * both spend the last credit.
 */
class AiCreditService
{
    /**
     * What is left, in whole credits. The ledger keeps the exact sum; the
     * balance anybody sees or spends against is that sum rounded DOWN, since
     * nothing costs less than one credit and a fraction of one buys nothing.
     */
    public function balance(int $userId): float
    {
        return (float) floor((float) AiCreditLedger::active()->where('userId', $userId)->sum('delta'));
    }

    /** Add credits (purchase, signup allowance, admin adjustment, refund). */
    public function grant(int $userId, float $credits, string $reason, string $source = 'purchase', ?int $adminUserId = null): float
    {
        if ($credits <= 0) {
            return $this->balance($userId);
        }

        return $this->write($userId, $credits, $reason, $source, null, $adminUserId);
    }

    /**
     * Spend credits. Returns the new balance, or null when the client cannot
     * afford it — the caller must have checked `canAfford` first, but this is
     * the authority.
     */
    /**
     * Whether this account rides free. Mother-site admins bridged into
     * anee.io run the platform - metering them would be charging the house
     * for its own electricity, and the owner said it plainly: admin accounts
     * have unlimited AI credits.
     */
    public function unlimited(int $userId): bool
    {
        $u = \App\Models\User::find($userId);

        return $u !== null && $u->isSuperAdmin();
    }

    public function charge(int $userId, float $credits, string $reason, ?int $messageId = null): ?float
    {
        if ($credits <= 0 || $this->unlimited($userId)) {
            return $this->balance($userId);
        }

        return DB::transaction(function () use ($userId, $credits, $reason, $messageId) {
            $current = (float) AiCreditLedger::active()
                ->where('userId', $userId)
                ->lockForUpdate()
                ->sum('delta');

            if ($current < $credits) {
                return null;
            }

            $after = round($current - $credits, 2);
            AiCreditLedger::create([
                'userId' => $userId,
                'delta' => -1 * round($credits, 2),
                'balanceAfter' => $after,
                'reason' => $reason,
                'source' => 'usage',
                'messageId' => $messageId,
                'deleteStatus' => 1,
            ]);

            return $after;
        });
    }

    /** Deduct without refusing — used to true-up after a call already happened. */
    public function chargeAllowingNegative(int $userId, float $credits, string $reason, ?int $messageId = null): float
    {
        if ($this->unlimited($userId)) {
            return $this->balance($userId);
        }

        return $this->write($userId, -1 * round($credits, 2), $reason, 'usage', $messageId);
    }

    /**
     * What an exchange costs, given the tokens it used.
     *
     * Whole credits, rounded UP: 6.2 credits is 7 -- the owner's rule
     * (2026-09-15), so no screen ever shows a decimal and a tiny question
     * is never free. It is also where the metered chat's margin lives; see
     * AiSetting for the per-thousand rates.
     */
    public function priceFor(AiSetting $settings, int $tokensIn, int $tokensOut, int $images = 0): float
    {
        $cost = ($tokensIn / 1000) * (float) $settings->creditsPerInputK
            + ($tokensOut / 1000) * (float) $settings->creditsPerOutputK
            + $images * (float) $settings->creditsPerImage;

        return (float) max(1, (int) ceil($cost - 0.000001));
    }

    /**
     * A conservative pre-flight estimate, so a client with an empty balance is
     * told before the provider is called rather than after.
     */
    /**
     * What every question carries before its own text.
     *
     * The instructions the provider is actually given -- house rules, persona,
     * the faces -- measured rather than guessed, because they are edited from
     * the mother app and grew by half again the day Anee learned to react. On
     * top of that, room for the earlier turns of the chat, which cannot be
     * known while somebody is still typing.
     *
     * Static and argument-free so the four composers can quote the same number
     * without each being handed a settings row.
     */
    public static function overheadTokens(?AiSetting $settings = null): int
    {
        $settings = $settings ?: AiSetting::current();

        // ~4 characters per token is the usual rough rule.
        return (int) ceil(mb_strlen($settings->instructions()) / 4) + self::HISTORY_ALLOWANCE;
    }

    /** Room for the turns before this one, which nobody can count in advance. */
    private const HISTORY_ALLOWANCE = 400;

    public function estimate(AiSetting $settings, string $prompt, int $images = 0): float
    {
        $promptTokens = (int) ceil(mb_strlen($prompt) / 4) + self::overheadTokens($settings);

        return $this->priceFor($settings, $promptTokens, (int) $settings->maxOutputTokens / 2, $images);
    }

    /** Recent movements, newest first, for the credits page. */
    public function history(int $userId, int $limit = 30)
    {
        return AiCreditLedger::active()
            ->where('userId', $userId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    // ------------------------------------------------------------------

    private function write(int $userId, float $delta, string $reason, string $source, ?int $messageId = null, ?int $adminUserId = null): float
    {
        return DB::transaction(function () use ($userId, $delta, $reason, $source, $messageId, $adminUserId) {
            $current = (float) AiCreditLedger::active()
                ->where('userId', $userId)
                ->lockForUpdate()
                ->sum('delta');

            $after = round($current + $delta, 2);

            AiCreditLedger::create([
                'userId' => $userId,
                'delta' => round($delta, 2),
                'balanceAfter' => $after,
                'reason' => $reason,
                'source' => $source,
                'messageId' => $messageId,
                'adminUserId' => $adminUserId,
                'deleteStatus' => 1,
            ]);

            return $after;
        });
    }
}
