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
            }
        }

        return $next($request);
    }
}
