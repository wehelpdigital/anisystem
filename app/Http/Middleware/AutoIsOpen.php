<?php

namespace App\Http\Middleware;

use App\Support\AutoLock;
use Closure;
use Illuminate\Http\Request;

/** Every Anee Auto route. A locked member gets the page that says we are still building it, or the same words as JSON. */
class AutoIsOpen
{
    public function handle(Request $request, Closure $next)
    {
        if (AutoLock::open()) {
            return $next($request);
        }
        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            return response()->json(['success' => false, 'message' => AutoLock::SAY, 'building' => true], 403);
        }

        return response()->view('auto.building');
    }
}
