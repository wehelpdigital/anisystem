<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use App\Support\WorkerContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The subscription doorman — reshaped for the tier ladder (2026-09-08).
 *
 * There is no locked-out state any more: a member with no active
 * subscription IS a tier — Libre, the free floor — and walks in with
 * Libre's limits. This middleware now only (a) requires login, and
 * (b) keeps the member's subscription rows synced so a payment the
 * admin just verified upgrades their tier on the very next request.
 * What each tier may actually do is asked feature-by-feature through
 * App\Support\Tier at the gates themselves.
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

        // Super admins hold no subscription rows to sync.
        if (! $user->isSuperAdmin()) {
            // Throttled sync so admin verifications unlock access quickly.
            $this->subscriptions->syncUser($user);
        }

        return $next($request);
    }
}
