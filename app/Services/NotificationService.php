<?php

namespace App\Services;

use App\Models\AnisystemNotification;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Creates in-app notifications for AniSystem clients. Community features call
 * notify() when someone comments/replies/connects; subscription expiry is
 * synced lazily on page load via syncExpiryReminder().
 */
class NotificationService
{
    /**
     * Record a notification. When $dedupeWindowHours is given, an identical
     * (userId, type, url) notification created inside that window is skipped so
     * repeat events don't spam the bell.
     */
    public function notify(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?int $actorUserId = null,
        ?int $croppingScheduleId = null,
        ?int $dedupeWindowHours = null,
    ): ?AnisystemNotification {
        // Never notify yourself about your own action.
        if ($actorUserId !== null && $actorUserId === $userId) {
            return null;
        }

        if ($dedupeWindowHours !== null) {
            $exists = AnisystemNotification::active()
                ->forUser($userId)
                ->where('type', $type)
                ->when($url !== null, fn ($q) => $q->where('url', $url))
                ->where('created_at', '>=', Carbon::now()->subHours($dedupeWindowHours))
                ->exists();
            if ($exists) {
                return null;
            }
        }

        return AnisystemNotification::create([
            'userId' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'actorUserId' => $actorUserId,
            'croppingScheduleId' => $croppingScheduleId,
            'deleteStatus' => 1,
        ]);
    }

    /**
     * Ensure there's an up-to-date expiry reminder when the user's subscription
     * is within $withinDays of lapsing. Idempotent per day so it's safe to call
     * on every dashboard load.
     */
    public function syncExpiryReminder(User $user, int $withinDays = 14): void
    {
        $sub = $user->currentSubscription();
        if (! $sub || ! $sub->expiresAt) {
            return;
        }

        $expiresAt = Carbon::parse($sub->expiresAt);
        $now = Carbon::now('Asia/Manila');
        $daysLeft = (int) ceil($now->diffInDays($expiresAt, false));

        // Only remind in the run-up window (including a couple of days past due).
        if ($daysLeft > $withinDays || $daysLeft < -2) {
            return;
        }

        // One reminder per user per day (keyed by the expiry URL + a day stamp
        // in the body would be overkill — dedupe on 20h keeps it to one/day).
        $already = AnisystemNotification::active()
            ->forUser($user->id)
            ->where('type', 'expiry')
            ->where('created_at', '>=', $now->copy()->subHours(20))
            ->exists();
        if ($already) {
            return;
        }

        $title = $daysLeft < 0
            ? 'Your subscription has expired'
            : ($daysLeft === 0 ? 'Your subscription expires today' : "Your subscription expires in {$daysLeft} " . str('day')->plural($daysLeft));

        $this->notify(
            userId: $user->id,
            type: 'expiry',
            title: $title,
            body: 'Renew to keep planning, sharing and emailing your schedules without interruption.',
            url: route('account.subscription'),
        );
    }
}
