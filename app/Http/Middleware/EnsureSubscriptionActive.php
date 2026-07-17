<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the schedule manager behind an active subscription. Pending, expired,
 * suspended, cancelled and rejected subscribers are redirected to the
 * subscription page, which explains their status and offers renewal.
 */
class EnsureSubscriptionActive
{
    public function __construct(private SubscriptionService $subscriptions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Throttled sync so admin verifications in /ecom-orders unlock access quickly.
        $this->subscriptions->syncUser($user);

        // Access is granted whenever ANY subscription is currently usable — not
        // just the newest row. Otherwise buying a renewal while still active
        // (which creates a newer PENDING row) would lock the user out until the
        // admin verifies the new payment, and a rejected renewal would lock them
        // out for the rest of their already-paid period.
        if (! $user->hasActiveSubscription()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your subscription is not active. Please renew to continue.',
                    'locked' => true,
                ], 403);
            }

            return redirect()->route('account.subscription')
                ->with('locked', true);
        }

        return $next($request);
    }
}
