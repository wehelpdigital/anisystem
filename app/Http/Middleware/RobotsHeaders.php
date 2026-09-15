<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every response says whether a search engine may keep it: noindex for
 * the whole app behind the login and for the public site until the owner
 * opens it (see App\Support\Seo). A header rather than only a meta, so a
 * JSON answer, a picture, a redirect are covered too.
 */
class RobotsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        try {
            $response->headers->set('X-Robots-Tag', Seo::robots($request));
        } catch (\Throwable $e) {
            // Never let the header cost the page.
        }

        return $response;
    }
}
