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

        // Callers hand us route(), which is an absolute address to whatever
        // machine is running — and this app writes to the same database from
        // a laptop, from Railway and from the mother site. Keep the place,
        // drop the machine (see localUrl).
        $url = self::localUrl($url);

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

        $note = AnisystemNotification::create([
            'userId' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'actorUserId' => $actorUserId,
            'croppingScheduleId' => $croppingScheduleId,
            'deleteStatus' => 1,
        ]);

        $this->push($note);

        return $note;
    }

    /**
     * A notification's address, with the machine taken off it.
     *
     * route() builds an absolute URL from whoever is generating it, and one
     * database is shared by every copy of this app: a bell rung from a
     * laptop wrote http://anisystem.test/app/… into a row that a farmer
     * then tapped on the live site, and the mother site writes rows of its
     * own with whatever it thinks our address is. None of those hosts is
     * knowable at write time — but the path is the same everywhere, and a
     * path resolves against the site the reader is actually on.
     *
     * So the host comes off, always. A notification points at a place in
     * this app — that is what the bell is for — and every row in the table
     * says so: /app/… for the modules, /account/… for the subscription.
     * (An outward link, if one is ever wanted, must not come through here.)
     */
    public static function localUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        // A path already, or a scheme with no host to lose (mailto:, tel:).
        if (! preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }

        return $path
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    /**
     * Ring the bell now, rather than at the next sixty-second poll.
     *
     * Best-effort by design: the bell still polls, so a dropped broadcast
     * costs a minute of lateness and nothing else. Never let a realtime
     * hiccup fail the thing that caused the notification.
     */
    private function push(AnisystemNotification $note): void
    {
        $driver = config('broadcasting.default');
        $ready = in_array($driver, ['pusher', 'reverb', 'ably'], true)
            && filled(config("broadcasting.connections.$driver.key"));
        if (! $ready) {
            return;
        }

        try {
            broadcast(new \App\Events\UserNotified((int) $note->userId, [
                'id' => (int) $note->id,
                'type' => (string) $note->type,
                'title' => (string) $note->title,
                'body' => (string) ($note->body ?? ''),
                'url' => $note->url,
            ]));
        } catch (\Throwable $e) {
            // The poll will find it.
        }
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
