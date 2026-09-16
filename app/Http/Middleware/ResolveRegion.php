<?php

namespace App\Http\Middleware;

use App\Support\Region;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settles which country this request is in before anything renders.
 *
 * `?country=XX` on any address is an explicit choice and is remembered in
 * the session (it is how the owner looks at the international face from
 * Manila). The public site's routes carry a {face} segment; its default is
 * set here so `route('pricing')` keeps working everywhere in the app and
 * lands on the visitor's own face.
 */
class ResolveRegion
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->query('country') !== null) {
            $chosen = Region::valid($request->query('country'));
            if ($chosen) {
                Region::choose($request, $chosen);
            }
        }
        Region::forget();
        URL::defaults(['face' => Region::face()]);

        return $next($request);
    }
}
