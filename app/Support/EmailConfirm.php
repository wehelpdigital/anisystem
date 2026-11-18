<?php

namespace App\Support;

use App\Models\User;
use App\Services\MailService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * Every account confirms its email before it uses anee.io (2026-11-19, the owner: "they should be verified first, we
 * need to verify the emails").
 *
 * A new email signup always did: it stays pending until its link is tapped. This closes what was left: an active
 * account whose address was never proven (made before confirmation existed, or by a door that did not ask) now meets
 * the same "check your inbox" page at the login and on its next visit, and gets its link. Proven already: a Google
 * login (Google proves it), a worker invite accepted (it was mailed to that address).
 *
 * Left alone, because no mail could reach them or nobody is there to read it: super admins, the house accounts
 * (User::SYSTEM_EMAILS) and the test and demo addresses (.test, .invalid, .local, example.com/net/org). Also a
 * worker login a boss made or linked for their crew (as_worker_grants): the boss, a confirmed account, vouches for
 * it and hands over the password, and a field hand's address is often one the boss made up, so no inbox answers.
 */
final class EmailConfirm
{
    /** Whether this account must confirm its address before going on. */
    public static function needed(?User $user): bool
    {
        if (! $user || $user->status !== 'active' || ! empty($user->emailVerifiedAt) || $user->isSuperAdmin()) {
            return false;
        }
        $email = mb_strtolower(trim((string) $user->email));
        if ($email === '' || in_array($email, User::SYSTEM_EMAILS, true)) {
            return false;
        }
        $domain = (string) substr((string) strrchr($email, '@'), 1);

        if ($domain === '' || preg_match('/(^|\.)(test|invalid|local|localhost)$|^example\.(com|net|org)$/', $domain)) {
            return false;
        }

        return ! self::crewLogin((int) $user->id);
    }

    /** @var array<int, bool> */
    private static array $crew = [];

    /** Whether a boss made or linked this login for their crew (any grant still on the books). */
    private static function crewLogin(int $userId): bool
    {
        return self::$crew[$userId] ??= \App\Models\WorkerGrant::where('workerUserId', $userId)
            ->where('deleteStatus', 1)->exists();
    }

    /**
     * The confirmation link, mailed. One a minute per account at most (the same rest the inbox page shows); true when
     * one went out now.
     */
    public static function send(User $user, bool $force = false): bool
    {
        if (! $force && ! Cache::add('verify-resend:' . $user->id, 1, 60)) {
            return false;
        }
        $link = URL::temporarySignedRoute('verify.email', now()->addDays(3), [
            'id' => $user->id,
            'hash' => sha1(mb_strtolower($user->email)),
        ]);
        $mail = app(MailService::class);

        /* The words live in the mother app's email editor (email_verification)
         * like every other email; the view is only for a database that has
         * not been given that template yet. */
        $tags = [
            'verifyUrl' => $link,
            'thanks' => Region::as(Region::of($user), fn () => Region::t('thanks')),
        ];
        if ($mail->render('email_verification', $tags, $user)) {
            $mail->sendTemplateToUser('email_verification', $user, $tags, ['userId' => $user->id]);

            return true;
        }

        $html = Region::as(Region::of($user), fn () => view('emails.verify-email', [
            'firstName' => $user->firstName,
            'link' => $link,
        ])->render());
        $mail->send($user->email, $user->full_name, 'Confirm your email for anee.io', $html, [
            'templateKey' => 'email_verification',
            'userId' => $user->id,
        ]);

        return true;
    }
}
