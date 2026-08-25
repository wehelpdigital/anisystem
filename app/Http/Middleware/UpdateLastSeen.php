<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refreshes the signed-in member's last-activity timestamp so the community can
 * show who is online. Throttled to at most one write per minute per user, and
 * written with a bare query (no updated_at churn) to keep it cheap on the
 * shared remote database.
 */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $seen = $user->lastSeenAt;

            if ($seen === null || $seen->lt(now()->subSeconds(60))) {
                User::where('id', $user->id)->update(['lastSeenAt' => now()]);
                $user->lastSeenAt = now();
                $this->markTheDay((int) $user->id);
            }
        }

        return $next($request);
    }

    /**
     * One diary row per member per day — the community ladder pays for
     * showing up, and this is where showing up is witnessed.
     *
     * Piggybacked on the last-seen throttle above (at most once a minute),
     * then guarded by a cache flag so the insert itself runs once a day; the
     * unique index makes a lost cache harmless. Failure is swallowed: a
     * missing table (migrations lagging) must never cost a page.
     */
    private function markTheDay(int $userId): void
    {
        $day = now()->toDateString();
        try {
            if (! \Illuminate\Support\Facades\Cache::add('as-member-day:' . $userId . ':' . $day, 1, now()->endOfDay())) {
                return;
            }
            \Illuminate\Support\Facades\DB::table('as_member_days')->insertOrIgnore([
                'userId' => $userId,
                'day' => $day,
            ]);
        } catch (\Throwable $e) {
            // The diary is a nicety; the request is the point.
        }
    }
}
