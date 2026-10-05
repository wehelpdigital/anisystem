<?php

namespace App\Http\Middleware;

use App\Support\Region;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The international version is closed for maintenance (the owner's word,
 * 2026-10-05; the switch is config('app.international_open')).
 *
 * Two kinds of visit reach it: a page of the /en public site, and anything
 * at all by an account set to a country other than the Philippines. Both get
 * the maintenance page with a 503 (the status that tells a search engine
 * "come back later", never "this is gone"), and a request that wants JSON
 * gets the same words as JSON. Such an account may still log out. Super
 * admins are never stopped. The Philippine site, at the root, is untouched.
 */
class PauseInternational
{
    /** Doors that stay open to a paused account. */
    private const OPEN = ['logout', 'face.switch'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Region::intlPaused($request) || in_array($request->route()?->getName(), self::OPEN, true)) {
            return $next($request);
        }

        $user = $request->user();
        $account = $user && $request->route('face') !== 'en';
        $words = $account
            ? 'The international version of anee.io is closed for maintenance. Your account and your farm records are safe, and it will open again soon.'
            : 'The international version of anee.io is closed for maintenance. It will open again soon.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'maintenance' => true, 'message' => $words], 503)
                ->header('Retry-After', '86400');
        }

        return response()->view('public.maintenance', [
            'account' => $account,
            'countryName' => $account ? Region::name(Region::of($user)) : null,
        ], 503)->header('Retry-After', '86400');
    }
}
