<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use App\Support\WorkerContext;
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

        // Mother-site super admins get full access without an anee.io
        // subscription (they're bridged in via SuperAdminBridge).
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Throttled sync so admin verifications in /ecom-orders unlock access quickly.
        $this->subscriptions->syncUser($user);

        // Access is granted whenever ANY subscription is currently usable — not
        // just the newest row. Otherwise buying a renewal while still active
        // (which creates a newer PENDING row) would lock the user out until the
        // admin verifies the new payment, and a rejected renewal would lock them
        // out for the rest of their already-paid period.
        if ($user->hasActiveSubscription()) {
            return $next($request);
        }

        // An invited worker never buys a subscription — access is inherited from
        // the farm owner who invited them, so gating on the worker's own (always
        // empty) subscription locked every worker out of the app the moment they
        // logged in. Let them through while at least one of their bosses is
        // paying; WorkerContext then scopes what they can actually see.
        if ($this->hasSubscribedBoss()) {
            return $next($request);
        }

        // Workers cannot renew a plan that was never theirs, so send them a
        // message about the farm owner rather than a bill they cannot settle.
        // Which farm they are standing in decides whose bill this is.
        $isWorker = WorkerContext::inWorkerContext();
        $message = $isWorker
            ? 'This farm\'s subscription is not active. Please ask the farm owner to renew.'
            : 'Your subscription is not active. Please renew to continue.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'locked' => true,
            ], 403);
        }

        return redirect()->route('account.subscription')
            ->with('locked', true)
            ->with('error', $isWorker ? $message : null);
    }

    /**
     * True when the member works for at least one farm owner who is currently
     * subscribed. Each boss is synced first for the same reason the member is:
     * a payment the admin just verified should unlock the worker immediately.
     */
    private function hasSubscribedBoss(): bool
    {
        foreach (WorkerContext::grants() as $grant) {
            $boss = $grant->boss;

            if (! $boss) {
                continue;
            }

            if ($boss->isSuperAdmin()) {
                return true;
            }

            $this->subscriptions->syncUser($boss);

            if ($boss->hasActiveSubscription()) {
                return true;
            }
        }

        return false;
    }
}
