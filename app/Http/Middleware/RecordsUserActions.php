<?php

namespace App\Http\Middleware;

use App\Support\UserActions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every write a member makes, kept for the admin panel's Activity page (App\Support\UserActions, 2026-11-26): what
 * they did, or why the app said no. Last in the web group, so it sees the answer; written after it has gone out.
 */
class RecordsUserActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        UserActions::note($request, $response);

        return $response;
    }
}
