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
 * Designed to coexist with the always-remember login (LoginController): the
 * losing session is dropped WITHOUT cycling the remember token, so the browser's
 * remember cookie stays valid — returning to that device re-authenticates
 * automatically and re-claims the slot. Two people sharing one account therefore
 * keep bouncing each other out (the intended deterrent), while a single person
 * on one device is never disturbed.
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
                // A newer login elsewhere owns the account now. End this session
                // but leave the remember cookie intact (session flush does not
                // cycle the remember token) so a return visit re-logs in.
                $request->session()->flush();
                $request->session()->regenerate();

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
