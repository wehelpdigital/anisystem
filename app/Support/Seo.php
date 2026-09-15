<?php

namespace App\Support;

use App\Http\Controllers\LegalController;
use App\Http\Controllers\PublicController;
use App\Models\AsSiteSetting;
use Illuminate\Http\Request;

/**
 * What search engines may see.
 *
 * The app behind the login -- a farmer's schedules, notes, pictures, the
 * community, the admin -- is nobody's to index, ever. The public site
 * (the home page, features, pricing, about, contact, the legal pages)
 * may be, but only once the owner flips the switch in the mother app
 * (AniSystem > Search indexing); until then the whole domain says no.
 *
 * Said three ways so nothing slips: the robots meta in every layout, an
 * X-Robots-Tag header on every response, and robots.txt.
 */
final class Seo
{
    public const KEY = 'seo.publicIndexable';

    /** Whether the owner has allowed the public site to be indexed. */
    public static function publicIndexable(): bool
    {
        return AsSiteSetting::yes(self::KEY, false);
    }

    /**
     * Whether this request is for a page of the public site: one the
     * marketing controllers serve. Token doors (a shared plan, a worker's
     * invitation, a post shared outward) and the auth forms are not.
     */
    public static function isPublicPage(?Request $request): bool
    {
        $route = $request?->route();
        if (! $route) {
            return false;
        }
        try {
            $controller = $route->getController();
        } catch (\Throwable $e) {
            return false;
        }

        return $controller instanceof PublicController || $controller instanceof LegalController;
    }

    /** The robots directive for this request: what the meta and the header say. */
    public static function robots(?Request $request = null): string
    {
        $request ??= request();

        return self::publicIndexable() && self::isPublicPage($request) ? 'index, follow' : 'noindex, nofollow';
    }

    /** The body of robots.txt for the switch as it stands. */
    public static function robotsTxt(): string
    {
        if (! self::publicIndexable()) {
            return "User-agent: *\nDisallow: /\n";
        }

        $closed = ['/app/', '/admin/', '/account', '/purchase', '/notifications', '/login', '/signup', '/auth/',
            '/forgot-password', '/reset-password', '/verify-email', '/verify-notice', '/pw/', '/s/', '/worker-invite/',
            '/ads/', '/storage/', '/broadcasting/', '/blog-preview', '/deploy-check', '/up'];
        $lines = ['User-agent: *'];
        foreach ($closed as $path) {
            $lines[] = 'Disallow: ' . $path;
        }
        $lines[] = 'Allow: /';

        return implode("\n", $lines) . "\n";
    }
}
