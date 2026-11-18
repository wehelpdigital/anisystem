<?php

namespace App\Http\Middleware;

use App\Support\EmailConfirm;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A signed in account whose email was never confirmed (App\Support\EmailConfirm::needed) goes no further
 * (2026-11-19, the owner: "they should be verified first, we need to verify the emails"). The login already
 * refuses one; this catches a session that was open before the rule, or kept by "keep me logged in". The
 * account is signed out, a link goes to its inbox, and it lands on the "check your inbox" page, exactly as a new
 * signup does. Every page, the members' guides included: a guide opens in full only to a confirmed member.
 * An admin looking through a client's eyes (impersonation) is never stopped.
 */
class EnsureEmailConfirmed
{
    /** The way out, untouched. */
    private const OPEN = ['logout', 'admin.return'];

    /**
     * The inbox page, its buttons and the link the mail carries are guest doors: an account still signed in there
     * (a phone that kept its login taps the link) is signed out and the door opens as for a guest, so the link it
     * tapped still works instead of being bounced away.
     */
    private const INBOX = ['verify.notice', 'verify.status', 'verify.resend', 'verify.email'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || ! $request->hasSession() || $request->session()->has('admin_impersonator') || ! EmailConfirm::needed($user)) {
            return $next($request);
        }
        $door = (string) optional($request->route())->getName();
        if (in_array($door, self::OPEN, true)) {
            return $next($request);
        }
        $email = (string) $user->email;
        if (in_array($door, self::INBOX, true)) {
            Auth::guard('web')->logout();
            $request->session()->migrate(true);
            $request->session()->put('signup.email', $email);

            return $next($request);
        }

        try {
            EmailConfirm::send($user);
        } catch (\Throwable $e) {
            // The page still says what to do and offers a fresh link.
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('signup.email', $email);

        $say = 'Please confirm your email first. We sent a link to your inbox.';
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $say, 'needConfirm' => true], 401);
        }

        return redirect()->route('verify.notice')->with('error', $say);
    }
}
