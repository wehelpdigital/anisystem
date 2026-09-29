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
        $t0 = microtime(true);
        $response = $next($request);

        $ua = (string) $request->userAgent();
        // Facebook's own networks too (AS32934), whatever the visitor calls
        // itself: the domain verifier may not say facebookexternalhit.
        $fromFacebook = (bool) preg_match('/^(31\.13|66\.220|69\.63|69\.171|173\.252|157\.240|129\.134|179\.60|185\.60|204\.15|102\.132|163\.70|57\.14[1-4])\./', (string) $request->ip());
        if (($ua !== '' && preg_match(self::PATTERN, $ua)) || $fromFacebook) {
            try {
                $body = $response->getContent();
                $seen = Cache::get(self::KEY, []);
                array_unshift($seen, [
                    'at' => now()->toIso8601String(),
                    'method' => $request->method(),
                    'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 80),
                    'status' => $response->getStatusCode(),
                    // "The document returned no data": what the page really was.
                    'bytes' => $body === false ? null : strlen($body),
                    'type' => $response->headers->get('Content-Type'),
                    'ms' => (int) round((microtime(true) - $t0) * 1000),
                    'asked' => array_filter([
                        'range' => $request->header('Range'),
                        'encoding' => $request->header('Accept-Encoding'),
                        'accept' => $request->header('Accept'),
                        'lang' => $request->header('Accept-Language'),
                        'country' => $request->header('CF-IPCountry'),
                    ]),
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
