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

        /* THE MAILING LIST LEARNS ABOUT THEM HERE, AND NOT A STEP EARLIER.
         *
         * This runs at the moment an address is PROVED — the click on the
         * emailed link, or Google vouching for it — which is why the welcome
         * credits and the welcome email are granted from here too. Pushing at
         * the point the form is submitted would fill the list with addresses
         * nobody has confirmed, including every robot that ever finds the
         * signup page, and a marketing list full of unproven addresses is a
         * sender reputation spent on nothing.
         *
         * Everyone arrives free: paying is a later, separate act, and the
         * upgrade is what should move them out of that segment.
         *
         * Wrapped like its neighbours. A third party being down is not a
         * reason to refuse somebody the account they just confirmed. */
        try {
            app(\App\Services\AcumbamailService::class)->addMember($user, 'free');
        } catch (\Throwable $e) {
            Log::warning('Acumbamail signup push failed for user '.$user->id.': '.$e->getMessage());
        }
    }
}
