<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * The moment an address is proven real — by the emailed link or by Google
 * vouching for it — the account crosses from 'pending' to member: status
 * flips, the welcome credits land, the welcome mail goes out. One door for
 * both paths so the two can never drift on what "joining" means.
 */
class NewMemberWelcome
{
    public static function activate(User $user): void
    {
        $user->forceFill([
            'status' => 'active',
            'emailVerifiedAt' => now(),
        ])->save();

        // Free allowance so a new member can try the AI Technician before
        // deciding whether to buy credits. Granted here — after proof — so a
        // robot minting unverified accounts mints nothing.
        try {
            $freeCredits = (int) \App\Models\AiSetting::current()->freeCreditsOnSignup;
            if ($freeCredits > 0) {
                app(\App\Services\AiCreditService::class)
                    ->grant($user->id, $freeCredits, 'Welcome credits', 'signup');
            }
        } catch (\Throwable $e) {
            Log::warning('Welcome AI credits failed for user '.$user->id.': '.$e->getMessage());
        }

        try {
            app(\App\Services\MailService::class)->sendTemplateToUser('registration_welcome', $user);
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed for user '.$user->id.': '.$e->getMessage());
        }
    }
}
