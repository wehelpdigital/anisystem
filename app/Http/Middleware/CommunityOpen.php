<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A suspended member does not get into the Community.
 *
 * Checked by path rather than stitched onto forty route definitions: every
 * community door — the wall, groups, blog, co-farmers, the lot — lives under
 * /app/community, and a middleware that watches the hallway cannot miss a
 * door somebody adds next month.
 *
 * The rest of the app is untouched on purpose. The suspension is FROM the
 * community, not from the account: schedules, inventory and the AI keep
 * working, because a farm does not stop needing tending over a forum row.
 */
class CommunityOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('app/community*')) {
            return $next($request);
        }

        $user = Auth::user();
        $until = $user?->communitySuspendedUntil;

        if ($user && $until && now()->lt($until)) {
            $says = 'Your Community access is suspended until ' . $until->format('M j, Y') . '.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $says], 403);
            }

            return redirect('/app')->with('error', $says);
        }

        return $next($request);
    }
}
