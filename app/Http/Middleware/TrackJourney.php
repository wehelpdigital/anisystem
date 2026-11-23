<?php

namespace App\Http\Middleware;

use App\Support\PageViews;
use App\Support\VisitorJourney;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A guest's public page views, kept as the road to their signup (App\Support\VisitorJourney), and every person's
 * page views for the admin panel's Interests report (App\Support\PageViews, 2026-11-23). Both are written after the
 * answer has gone out; robots, prefetches and staff are left out there.
 */
class TrackJourney
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $vid = VisitorJourney::note($request, $response);
        PageViews::note($request, $response, $vid);

        return $response;
    }
}
