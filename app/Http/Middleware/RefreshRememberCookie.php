<?php

namespace App\Http\Middleware;

use App\Support\SignIn;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Keep me logged in" counts from the last visit, not from the login.
 *
 * Laravel writes the remember cookie once, at login, with a fixed expiry.
 * Left alone, a member who ticked the box would still be thrown out on day
 * ten in the middle of whatever they were doing. So every signed-in request
 * that arrived with the remember cookie sends it back with a fresh ten days
 * on it: the cookie only ever runs out after ten days of not opening the app.
 *
 * The cookie's value is untouched -- the same token the user row carries --
 * so a device signed out elsewhere (EnforceSingleSession forgets the cookie)
 * gets nothing here: the guard says it is not signed in, and we do nothing.
 */
class RefreshRememberCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard && $guard->check()) {
            $name = $guard->getRecallerName();
            $value = $request->cookies->get($name);
            // A login this very request has already queued a fresh cookie with
            // a new token; re-queuing the request's old value would overwrite
            // it, and the box the member just ticked would do nothing.
            if (is_string($value) && $value !== '' && ! Cookie::hasQueued($name)) {
                // Same name, same value, new expiry. Path, domain, secure and
                // SameSite come from the cookie jar's defaults (config/session),
                // exactly as the guard set them at login.
                Cookie::queue(Cookie::make($name, $value, SignIn::rememberMinutes()));
            }
        }

        return $response;
    }
}
