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

        // The guides are public; their builder preview is not.
        if ($controller instanceof \App\Http\Controllers\SitePageController) {
            return $route->getName() !== 'site.preview' && $route->getName() !== 'site.preview.post';
        }

        // Try and Ask Anee: the page and the list of answers are public; the
        // steps and a visitor's own answer link are not.
        if ($controller instanceof \App\Http\Controllers\AskAneeController) {
            return in_array($route->getName(), ['ask.page', 'site.questions'], true);
        }

        return $controller instanceof PublicController || $controller instanceof LegalController;
    }

    /** The robots directive for this request: what the meta and the header say. */
    public static function robots(?Request $request = null): string
    {
        $request ??= request();
        // A page that asks to stay out on its own (a field catalogue fact sheet).
        if ($own = $request?->attributes->get('robots')) {
            return self::publicIndexable() ? (string) $own : 'noindex, nofollow';
        }
        // The international site is closed for maintenance: never indexed while it is.
        if (! Region::intlOpen() && $request?->route('face') === 'en') {
            return 'noindex, nofollow';
        }

        return self::publicIndexable() && self::isPublicPage($request) ? 'index, follow' : 'noindex, nofollow';
    }

    /** Behind the login, or a door of its own: closed to every crawler. */
    private const CLOSED = ['/app/', '/admin/', '/account', '/purchase', '/notifications', '/login', '/signup', '/auth/',
        '/forgot-password', '/reset-password', '/verify-email', '/verify-notice', '/pw/', '/s/', '/worker-invite/',
        '/ads/', '/storage/', '/broadcasting/', '/blog-preview', '/site-preview', '/deploy-check', '/up', '/ask-anee/answer/',
        // The flag's switch: it sets a cookie and redirects, never a page.
        '/face/'];

    /**
     * Facebook's own fetchers. They index nothing: they read a page to draw
     * a shared link's preview, to review an ad's destination, and to check
     * the domain-verification tag. Shut out with everyone else while the
     * switch is off, the domain could not be verified ("blocked by
     * robots.txt", 2026-09-29), so they may always read the public site.
     */
    private const LINK_READERS = ['facebookexternalhit', 'Facebot'];

    /** The body of robots.txt for the switch as it stands. */
    public static function robotsTxt(): string
    {
        $group = function (string $agent, bool $open): string {
            $lines = ['User-agent: ' . $agent];
            if (! $open) {
                $lines[] = 'Disallow: /';
            } else {
                foreach (self::CLOSED as $path) {
                    $lines[] = 'Disallow: ' . $path;
                }
                if (! Region::intlOpen()) {
                    $lines[] = 'Disallow: /en';   // closed for maintenance
                }
                $lines[] = 'Allow: /';
            }

            return implode("\n", $lines);
        };

        // A crawler follows the group that names it, never the * group too.
        $groups = array_map(fn ($agent) => $group($agent, true), self::LINK_READERS);
        $groups[] = $group('*', self::publicIndexable());
        // Where every public page is listed, once the site may be indexed.
        if (self::publicIndexable()) {
            $groups[] = 'Sitemap: ' . url('/sitemap.xml');
        }

        return implode("\n\n", $groups) . "\n";
    }
}
