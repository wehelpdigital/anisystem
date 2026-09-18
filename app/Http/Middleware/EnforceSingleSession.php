<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * One active session per account. The user row records the session id that
 * currently owns the account; when a request arrives from a different session,
 * that session is signed out.
 *
 * Logging in somewhere new signs the other devices out, and they stay out: the
 * losing session is logged out and its remember cookie is forgotten, so it
 * cannot re-authenticate itself on the next request.
 *
 * Forgotten on THAT device only. The eviction used to call Auth::logout(),
 * which also cycles the remember token on the user row — and the token is
 * shared by every device's cookie, so it killed the winner's "keep me logged
 * in" as well. A member with a phone and a PC then lasted exactly one idle
 * session on the phone before the login page came back, and "the session
 * expires too quickly" was the complaint. logoutCurrentDevice() drops the
 * losing browser's cookie and leaves the token alone.
 *
 * The losing session used to keep its remember cookie altogether, on the
 * theory that a shared account would keep evicting itself as a deterrent. In
 * practice one person with two devices was signed out of whichever they were
 * using, repeatedly, because each device kept silently taking the account back
 * from the other. Sharing an account is still discouraged — the other person is
 * signed out for real — without punishing the ordinary case of owning two
 * devices.
 */
class EnforceSingleSession
{
    /**
     * Development hosts do not compete for the slot.
     *
     * Local and deployed now run against one shared database, so they also
     * share `currentSessionId`. Being signed in on localhost and on the live
     * site at once made every request from one flush the other, the remember
     * cookie re-claimed it, and the two bounced forever — a save or a card move
     * landing on the losing side simply signed the user out. Real users are all
     * on the deployed host, so ignoring dev origins costs the deterrent nothing.
     */
    private function isDevHost(Request $request): bool
    {
        $host = strtolower((string) $request->getHost());

        return $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost');
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isDevHost($request)) {
            return $next($request);
        }

        /* An admin looking through a client's eyes does not compete for the
         * client's one session. Claiming the slot here would sign the real
         * person out of their phone with "opened on another device" — for
         * something they did not do. */
        if ($request->session()->has('admin_impersonator')) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            $sid = $request->session()->getId();
            $stored = $user->currentSessionId ?? null;

            // Claim (or re-claim) the single slot when this session has no owner
            // recorded yet, or when the framework just restored us from the
            // remember cookie — the newest arrival owns the account.
            if (empty($stored) || Auth::viaRemember()) {
                if ($stored !== $sid) {
                    $user->forceFill(['currentSessionId' => $sid])->saveQuietly();
                }
            } elseif ($stored !== $sid) {
                // A newer login owns the account, so this device signs out — and
                // stays signed out: its remember cookie is forgotten with the
                // session, so it cannot silently take the account back. The
                // remember token itself is left alone (see the class comment),
                // so the device that logged in most recently keeps its "keep
                // me logged in" until someone deliberately logs in elsewhere.
                Auth::guard('web')->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'Signed out — your account was opened on another device. Log in again to continue here.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message, 'loggedOut' => true], 401);
                }

                return redirect()->route('login')->with('error', $message);
            }
        }

        return $next($request);
    }
}
