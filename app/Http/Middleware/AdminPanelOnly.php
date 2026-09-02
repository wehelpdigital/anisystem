<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel's door.
 *
 * Admins are the mother-site accounts bridged into anee.io — the same test
 * every privileged thing here already uses (isSuperAdmin), so there is one
 * answer to "who runs the platform" rather than two that can drift.
 */
class AdminPanelOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || ! $user->isSuperAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Admins only.'], 403);
            }

            return redirect('/app');
        }

        return $next($request);
    }
}
