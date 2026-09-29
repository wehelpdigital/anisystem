<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the last few visits of Facebook's link readers (facebookexternalhit,
 * Facebot, the catalog and preview fetchers): when, what, and what we
 * answered. Shown on /deploy-check as `linkReaders`.
 *
 * Why (2026-09-29): Facebook's domain verification reported a 403 while every
 * copy of its request sent from here got a 200. A visit that never appears
 * in this list never reached the app: the edge in front of it refused it.
 * One that appears carries the status the app gave it.
 */
class NoteLinkReaders
{
    public const KEY = 'link-readers.recent';

    private const PATTERN = '/facebookexternalhit|facebot|facebookcatalog|meta-externalfetcher/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ua = (string) $request->userAgent();
        if ($ua !== '' && preg_match(self::PATTERN, $ua)) {
            try {
                $seen = Cache::get(self::KEY, []);
                array_unshift($seen, [
                    'at' => now()->toIso8601String(),
                    'method' => $request->method(),
                    'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 80),
                    'status' => $response->getStatusCode(),
                    'agent' => mb_substr($ua, 0, 90),
                    // The network, not the address: enough to tell Facebook's own.
                    'from' => preg_replace('/(\d+\.\d+)\.\d+\.\d+$/', '$1.x.x', (string) $request->ip()),
                ]);
                Cache::put(self::KEY, array_slice($seen, 0, 25), now()->addDays(7));
            } catch (\Throwable $e) {
                // A note is never worth a page.
            }
        }

        return $response;
    }
}
