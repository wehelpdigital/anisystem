<?php

namespace App\Http\Middleware;

use App\Support\Tier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * One place that decides whether the farm's PLAN has this module at all.
 *
 * The sibling of WorkerModuleAccess, for the same reason it exists: a module
 * has a dozen routes, and a tier check at the top of each action is a check
 * somebody forgets on the thirteenth. So the gate is a table of route-name
 * patterns to tier keys (config/tiers.php), and every route that matches is
 * refused -- read or write -- when the farm's plan lacks the key. Refused
 * the way every tier lock is: Tier::deny(), which the pages turn into the
 * upgrade sheet (JSON) or a walk to the subscription page.
 *
 * Whose plan: the FARM's (Tier::farmCan), which is the boss's for a worker
 * standing in somebody's farm and the member's own otherwise -- the same
 * answer the module tiles and the menus give, so a door never opens onto a
 * refusal or refuses what a tile offered.
 *
 * 2026-09-19: Workers and Inventory leave the free plans. Libre and Libre +
 * Anee keep the diary, the lots and the board; the shed and the crew are the
 * Solo Farmer plan's.
 */
class TierModuleAccess
{
    /** Route-name patterns, the tier key they need, and what to say when it is missing. */
    private const RULES = [
        ['sm.workers',     'workers',   'Workers come with the Solo Farmer plan — the crew, their days and their pay, on every activity.'],
        ['sm.workers.*',   'workers',   'Workers come with the Solo Farmer plan — the crew, their days and their pay, on every activity.'],
        ['sm.inventory',   'inventory', 'The Inventory comes with the Solo Farmer plan — the shed, its stock, and what each activity takes from it.'],
        ['sm.inventory.*', 'inventory', 'The Inventory comes with the Solo Farmer plan — the shed, its stock, and what each activity takes from it.'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $name = (string) optional($request->route())->getName();
        if ($name === '') {
            return $next($request);
        }

        foreach (self::RULES as [$pattern, $key, $say]) {
            if (Str::is($pattern, $name) && ! Tier::farmCan($key)) {
                Tier::deny($say, 'solo');
            }
        }

        return $next($request);
    }
}
