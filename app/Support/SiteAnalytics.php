<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Google Analytics on the public site (2026-11-05, the owner: "setup this google analytics code in the public site and
 * make sure that everything is tracked correctly for better analysis").
 *
 * The tag lives in partials.site-analytics, the words the site reports in resources/js/site-events.js. It runs on the
 * live address only, so a local page or a test server never counts as a visit, and it stays off every address that
 * carries a secret: Google would keep the token in its reports. Behind the login there is no tag at all.
 */
final class SiteAnalytics
{
    /** Addresses never tagged: a token in the path, a staff preview, or the app behind the login. */
    private const SHUT = [
        'reset-password/*', 'verify-email/*', 'worker-invite/*', 'pw/*', 's/*', 'ask-anee/answer/*', 'ask-anee/question/*',
        'blog-preview/*', 'site-preview*', 'face/*', 'auth/*', 'app', 'app/*', 'admin', 'admin/*', 'account', 'account/*',
        'purchase', 'purchase/*', 'checkout', 'checkout/*', 'notifications', 'notifications/*', 'mother-api/*', 'cron/*', 'svc/*',
    ];

    /** The tag belongs on this request. Never throws: the error pages ask too, when things have gone wrong. */
    public static function on(): bool
    {
        try {
            $request = request();

            return self::container() !== ''
                && in_array($request->getHost(), (array) config('services.analytics.hosts'), true)
                && ! $request->is(...self::SHUT);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function container(): string
    {
        return (string) config('services.analytics.container');
    }

    /** Staff, or staff looking through a member's eyes: not visitors. GA4 drops them once its internal filter is active. */
    public static function internal(): bool
    {
        $user = auth()->user();

        return ($user && $user->isSuperAdmin()) || session()->has('admin_impersonator');
    }

    /** Someone already signed in, reading the public site: split from the people the site is trying to win. */
    public static function memberStatus(): string
    {
        return auth()->check() ? 'member' : 'guest';
    }
}
