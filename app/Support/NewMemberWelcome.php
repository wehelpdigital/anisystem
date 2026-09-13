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

        /* A SEASON TO PRACTISE ON.
         *
         * The app has nineteen modules and a brand-new account has nothing in
         * any of them, so every door opens onto an empty room. This hands over
         * a small season already in progress — two lots, a hired hand, three
         * weeks of work with some of it ticked off — and the guided walk that
         * points at the parts of it.
         *
         * Here rather than at the signup form, for the reason everything else
         * in this method is here: an account is born 'pending', and writing a
         * season and a dozen activities for every robot that finds the signup
         * page is a lot of rows for nothing. It builds itself once, never
         * twice, and it does not spend the one active season a free account
         * may keep.
         *
         * Wrapped like its neighbours — DemoSchedule swallows its own failures
         * too, but a season nobody could write is not a reason to refuse
         * somebody the account they just confirmed. */
        try {
            \App\Support\DemoSchedule::createFor($user);
        } catch (\Throwable $e) {
            Log::warning('Demo schedule failed for user '.$user->id.': '.$e->getMessage());
        }
    }
}
