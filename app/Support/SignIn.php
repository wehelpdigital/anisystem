<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;

/**
 * Signing a member in, with the two lifetimes the app promises.
 *
 * A session lives a day of idleness (config session.lifetime). "Keep me
 * logged in" adds the remember cookie, good for ten days from the last visit
 * (session.remember_lifetime; RefreshRememberCookie slides it on every
 * request), so a farmer who opens the app once a week never sees the login
 * page again. Every door that logs a user in -- the password form, Google,
 * the signup link, a worker's invite -- comes through here, so the ten days
 * are set in one place and not once per controller.
 */
final class SignIn
{
    public static function user(User $user, bool $remember): void
    {
        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration(self::rememberMinutes());
        }
        $guard->login($user, $remember);
    }

    /** How long the remember cookie lasts, in minutes, from the last visit. */
    public static function rememberMinutes(): int
    {
        return max(60, (int) config('session.remember_lifetime', 14400));
    }
}
