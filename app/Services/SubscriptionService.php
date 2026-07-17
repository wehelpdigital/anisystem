<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Subscription lifecycle: syncing against the mother system's order decisions
 * (verify / reject / cancel in /ecom-orders), activation, expiry.
 */
class SubscriptionService
{
    public function __construct(private MailService $mail)
    {
    }

    /**
     * Cheap per-user sync, throttled via cache. Called from middleware so a
     * client's access unlocks shortly after the admin verifies the payment,
     * even if the mother-side hook did not run.
     */
    public function syncUser(User $user, bool $force = false): void
    {
        $cacheKey = 'anisystem:sub-sync:'.$user->id;

        if (! $force && Cache::get($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, 1, 60);

        try {
            $this->syncPendingAgainstOrders($user);
            $this->expireOverdue($user);
        } catch (\Throwable $e) {
            // Remote DB flakiness must never take down a request.
            Log::warning('Subscription sync failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    private function syncPendingAgainstOrders(User $user): void
    {
        // Include rejected subs too: an admin who rejects then re-verifies the
        // same order (undo-reject) must still grant access.
        $syncable = $user->subscriptions()
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_REJECTED])
            ->whereNotNull('ecomOrderId')
            ->get();

        foreach ($syncable as $subscription) {
            $order = DB::table('ecom_orders')->where('id', $subscription->ecomOrderId)->first();
            if (! $order) {
                continue;
            }

            // Payment verified always wins (covers pending -> verified AND the
            // reject-then-verify correction) — check it BEFORE cancellation so a
            // stale "cancelled" order status can't override a verified payment.
            if ($order->paymentVerificationStatus === 'verified') {
                $this->activate($subscription, sendEmail: true);

                continue;
            }

            if ((int) ($order->deleteStatus ?? 1) === 0 || $order->orderStatus === 'cancelled' || $order->orderStatus === 'refunded') {
                // Only cancel a not-yet-active subscription; a paid+verified sub
                // is never cancelled by a stale order status.
                $subscription->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelledAt' => Carbon::now('Asia/Manila'),
                    'notes' => trim(($subscription->notes ?? '')."\nOrder {$order->orderNumber} was cancelled in the mother system."),
                ]);

                continue;
            }

            if ($order->paymentVerificationStatus === 'rejected' && $subscription->status === Subscription::STATUS_PENDING) {
                $subscription->update(['status' => Subscription::STATUS_REJECTED]);
                $this->mail->sendTemplateToUser('payment_rejected', $user, [
                    'orderNumber' => $subscription->orderNumber,
                    'planName' => $subscription->planName,
                    'price' => number_format((float) $subscription->price, 2),
                ]);
            }
        }
    }

    /**
     * Activate a verified subscription. If the user already has active time
     * remaining, the new period stacks on top of it (renewal semantics).
     *
     * The row is locked and its status re-checked inside the transaction so the
     * mother-app hook and this sync path can never double-activate the same
     * subscription (which would discard stacked renewal time).
     */
    public function activate(Subscription $subscription, bool $sendEmail = false): void
    {
        $now = Carbon::now('Asia/Manila');

        $outcome = DB::transaction(function () use ($subscription, $now) {
            $fresh = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            // Already activated by the other path — do nothing (idempotent).
            if (! $fresh || $fresh->status === Subscription::STATUS_ACTIVE) {
                return null;
            }

            $user = $fresh->user;
            $base = $now;
            $isRenewal = false;

            $current = $user?->activeSubscription();
            if ($current && $current->id !== $fresh->id && $current->expiresAt?->isFuture()) {
                $base = $current->expiresAt->copy();
                $isRenewal = true;
            }

            $fresh->update([
                'status' => Subscription::STATUS_ACTIVE,
                'startsAt' => $base,
                'expiresAt' => $base->copy()->addDays($fresh->durationDays),
                'verifiedAt' => $now,
            ]);

            return ['subscription' => $fresh, 'isRenewal' => $isRenewal];
        });

        if ($outcome && $sendEmail) {
            $sub = $outcome['subscription'];
            $user = $sub->user;
            if ($user) {
                $this->mail->sendTemplateToUser($outcome['isRenewal'] ? 'subscription_renewed' : 'payment_approved', $user, [
                    'orderNumber' => $sub->orderNumber,
                    'planName' => $sub->planName,
                    'price' => number_format((float) $sub->price, 2),
                    'expiresAt' => $sub->expiresAt->format('M j, Y'),
                ]);
            }
        }
    }

    private function expireOverdue(User $user): void
    {
        $overdue = $user->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('expiresAt')
            ->where('expiresAt', '<=', Carbon::now('Asia/Manila'))
            ->get();

        foreach ($overdue as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
            // Suppress the "expired" email if a stacked renewal already covers
            // the user — the period rolled over, they did not lose access.
            if (! $user->fresh()->hasActiveSubscription()) {
                $this->mail->sendTemplateToUser('subscription_expired', $user, [
                    'planName' => $subscription->planName,
                    'expiresAt' => $subscription->expiresAt->format('M j, Y'),
                ]);
            }
        }
    }

    /**
     * Daily maintenance across ALL users: persist expirations and send
     * "expiring soon" notices. Invoked by the anisystem:check-subscriptions
     * console command (schedule it daily).
     */
    public function runMaintenance(): array
    {
        $now = Carbon::now('Asia/Manila');
        $expired = 0;
        $notified = 0;
        $synced = 0;

        User::query()->active()
            ->whereHas('subscriptions', fn ($q) => $q->whereIn('status', [
                Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE,
            ]))
            ->with('subscriptions')
            ->chunkById(100, function ($users) use (&$expired, &$notified, &$synced, $now) {
                foreach ($users as $user) {
                    $this->syncPendingAgainstOrders($user);
                    $synced++;

                    // Re-read after sync so newly-activated rows are reflected.
                    foreach ($user->fresh()->subscriptions as $subscription) {
                        if ($subscription->status === Subscription::STATUS_ACTIVE && $subscription->expiresAt) {
                            if ($subscription->expiresAt->lessThanOrEqualTo($now)) {
                                $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
                                // Don't cry wolf when a stacked renewal still covers them.
                                if (! $user->fresh()->hasActiveSubscription()) {
                                    $this->mail->sendTemplateToUser('subscription_expired', $user, [
                                        'planName' => $subscription->planName,
                                        'expiresAt' => $subscription->expiresAt->format('M j, Y'),
                                    ]);
                                }
                                $expired++;
                            } elseif (
                                // Carbon 3's diffInDays is signed; measure now -> expiry
                                // so a future expiry is a POSITIVE day count.
                                $now->diffInDays($subscription->expiresAt, false) >= 0
                                && $now->diffInDays($subscription->expiresAt, false) <= config('anisystem.expiry_notice_days', 7)
                                && $subscription->expiryNotifiedAt === null
                            ) {
                                $this->mail->sendTemplateToUser('subscription_expiring', $user, [
                                    'planName' => $subscription->planName,
                                    'expiresAt' => $subscription->expiresAt->format('M j, Y'),
                                ]);
                                $subscription->update(['expiryNotifiedAt' => $now]);
                                $notified++;
                            }
                        }
                    }
                }
            });

        return compact('synced', 'expired', 'notified');
    }
}
