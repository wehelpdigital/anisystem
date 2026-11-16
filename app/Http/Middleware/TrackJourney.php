<?php

namespace App\Http\Middleware;

use App\Support\VisitorJourney;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A guest's public page views, kept as the road to their signup (App\Support\VisitorJourney). The step is written
 * after the answer has gone out; robots, prefetches and members are left out there.
 */
class TrackJourney
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        VisitorJourney::note($request, $response);

        return $response;
    }
}
