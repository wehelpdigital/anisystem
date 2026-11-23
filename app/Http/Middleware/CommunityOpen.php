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
        /* The Community closed for now (App\Support\Community, 2026-11-24): a page of it, opened from an old link or a
         * notification, lands on Home (a public post link on the website's home). Its JSON endpoints keep answering,
         * because nothing on screen calls them any more and a half finished request should not break. */
        if ($request->isMethod('GET') && ! $request->expectsJson() && ! $request->ajax()
            && \App\Support\Community::isSocialPath($request->path()) && ! \App\Support\Community::open()) {
            return redirect($request->is('pw/*') ? '/' : '/app');
        }

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
