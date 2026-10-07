<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One address for every page (2026-10-07): www.anee.io answered with the
 * same pages as anee.io, so a crawler saw every page twice. Any www. host
 * is sent, for good, to the same path on the bare domain.
 */
class DropWww
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->getHost());
        if (str_starts_with($host, 'www.') && in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return redirect()->to($request->getScheme() . '://' . substr($host, 4) . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
