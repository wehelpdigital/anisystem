<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ties a logged-in session to the network it was established from. If the
 * client later moves to a clearly different network, the session is dropped and
 * the user must sign in again — only a manual logout otherwise ends the session.
 *
 * Deliberately forgiving so it never signs out a stationary user whose address
 * merely jitters:
 *   - Private / loopback / reserved ranges are ignored (local dev, office LAN).
 *   - A dual-stack client that reaches us over IPv4 on one request and IPv6 on
 *     the next is NOT treated as a move (the #1 cause of phantom logouts).
 *   - Only the network prefix is compared (IPv4 /24, IPv6 /48), so a dynamic IP
 *     rotating within the same ISP allocation — including IPv6 temporary
 *     addresses — keeps the session. A jump to a genuinely different network
 *     (home ↔ mobile data, a different ISP) still ends it.
 */
class BindSessionToIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $ip = $request->ip();

            if ($this->isPublic($ip)) {
                $bound = $request->session()->get('_bound_ip');

                if ($bound === null || ! $this->isPublic($bound)) {
                    // First public request (or replacing a stale private value): remember it.
                    $request->session()->put('_bound_ip', $ip);
                } elseif ($this->isDifferentNetwork($bound, $ip)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $message = 'You were signed out because your network changed.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $message, 'loggedOut' => true], 401);
                    }

                    return redirect()->route('login')->with('error', $message);
                }
            }
        }

        return $next($request);
    }

    /** True only for globally-routable public IPs (not loopback/private/reserved). */
    private function isPublic(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * A move worth signing out for: same IP family, different network prefix.
     * A family flip (IPv4 ↔ IPv6) is dual-stack, not a move, so it never counts.
     */
    private function isDifferentNetwork(string $bound, string $current): bool
    {
        if ($bound === $current) {
            return false;
        }

        $boundV4 = filter_var($bound, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        $currentV4 = filter_var($current, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;

        if ($boundV4 !== $currentV4) {
            return false; // dual-stack: same client over a different protocol
        }

        return $this->networkPrefix($bound, $boundV4) !== $this->networkPrefix($current, $currentV4);
    }

    /**
     * Leading bytes that identify the network: IPv4 /16 (2 bytes), IPv6 /48
     * (6 bytes).
     *
     * IPv4 was compared at /24, which is far too tight for the carrier-grade
     * NAT most Filipino users reach us through: a phone sitting still on mobile
     * data hops around its provider's pool all day — 152.233.68.x to
     * 152.233.15.x within one session was enough to force a sign-out mid-edit.
     * Those are the same ISP allocation, and /16 keeps them together while a
     * jump to a genuinely different provider still ends the session.
     */
    private function networkPrefix(string $ip, bool $isV4): ?string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip; // unparseable — fall back to the whole string
        }

        return substr($packed, 0, $isV4 ? 2 : 6);
    }
}
