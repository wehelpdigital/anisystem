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
 * losing session is fully logged out, remember token included, so it cannot
 * re-authenticate itself on the next request.
 *
 * That last part matters. The losing session used to keep its remember cookie,
 * on the theory that a shared account would keep evicting itself as a
 * deterrent. In practice it meant one person with a phone and a PC was signed
 * out of whichever they were using, repeatedly, because each device kept
 * silently taking the account back from the other. Sharing an account is still
 * discouraged — the other person is signed out for real — without punishing the
 * ordinary case of owning two devices.
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
                // stays signed out.
                //
                // It used to keep its remember cookie, which meant the next
                // request re-claimed the slot automatically, displacing the
                // other device, which then re-claimed it back. One person with
                // a phone and a PC was thrown out of whichever they were
                // actually using, over and over. Auth::logout() cycles the
                // remember token as well, so this browser cannot silently take
                // the account back; the device that logged in most recently
                // keeps it until someone deliberately logs in elsewhere.
                Auth::logout();
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
