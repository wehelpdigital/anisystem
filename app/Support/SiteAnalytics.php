<?php

namespace App\Support;

use App\Models\AsOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Google Analytics on the public site (2026-11-05, the owner: "setup this google analytics code in the public site and
 * make sure that everything is tracked correctly for better analysis").
 *
 * The tag lives in partials.site-analytics, the words the site reports in resources/js/site-events.js. It runs on the
 * live address only, so a local page or a test server never counts as a visit, and it stays off every address that
 * carries a secret: Google would keep the token in its reports. Behind the login there is no tag at all.
 *
 * What happens past the login (a confirmed email, an order, a purchase) is told from the server instead: report(), the
 * Measurement Protocol. It tells the same visitor's story by reading their analytics cookie, sends no name, email or
 * phone, and does nothing until ANALYTICS_API_SECRET is set, which no local .env has.
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

    /**
     * This browser in Google's books, "clientId|sessionId", or null if it has never been on a tagged page. Read off the
     * raw header: the cookie middleware empties any cookie it did not seal itself, as it does these.
     */
    public static function visitor(Request $request): ?string
    {
        $cookies = [];
        foreach (explode(';', (string) $request->headers->get('cookie')) as $pair) {
            [$name, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $cookies[$name] = $value;
        }
        if (! preg_match('/^GA\d\.\d\.(\d+\.\d+)$/', $cookies['_ga'] ?? '', $client)) {
            return null;
        }
        $stream = '_ga_' . str_replace('G-', '', (string) config('services.analytics.measurement'));
        // GS1.1.1730000000.2... or the newer GS2.1.s1730000000$o2...
        $session = preg_match('/^GS\d\.\d\.s?(\d+)/', $cookies[$stream] ?? '', $m) ? $m[1] : '';

        return $client[1] . '|' . $session;
    }

    /** One event from the server, sent after the answer has gone so nobody waits on Google, and never a failure of its own. */
    public static function report(string $event, array $params, ?string $visitor, int $userId): void
    {
        self::send([$event => $params], $visitor, $userId);
    }

    /** Several events about one moment in one request, name => params: a finished registration is two at once. */
    public static function send(array $events, ?string $visitor, int $userId): void
    {
        $secret = (string) config('services.analytics.api_secret');
        if ($secret === '') {
            return;
        }
        [$client, $session] = array_pad(explode('|', (string) $visitor, 2), 2, '');
        $url = 'https://www.google-analytics.com/mp/collect?' . http_build_query([
            'measurement_id' => config('services.analytics.measurement'),
            'api_secret' => $secret,
        ]);
        $body = [
            // No cookie (the link opened on another phone): still counted, without knowing where they came from.
            'client_id' => $client !== '' ? $client : 'server.' . hash('crc32b', (string) $userId),
            'events' => array_map(fn ($name, $params) => ['name' => $name, 'params' => array_filter(
                ['session_id' => $session, 'engagement_time_msec' => 1] + $params,
                fn ($v) => $v !== '' && $v !== null,
            )], array_keys($events), array_values($events)),
        ];
        app()->terminating(fn () => rescue(fn () => Http::timeout(3)->post($url, $body), null, false));
    }

    /*
     * Registrations (2026-11-09, the owner: "track purchases and registrations in the app (libre)", then "the
     * registration we need it in the generate leads analysis"). GA4's Generate leads reports count people by three
     * events, so a Libre account walks through them:
     *   generate_lead       the account is asked for (the email form, or Google)       signedUp()
     *   qualify_lead        the account is finished (the emailed link, or Google)      confirmed()
     *   close_convert_lead  the first purchase is approved                             order()
     * lead_source 'signup' keeps them apart from Ask Anee's leads (lead_source 'ask_anee', sent by its own page).
     * Beside them stay the events the property already counts: sign_up (the form; the inbox page sends the email one
     * from the browser) and email_confirmed (the finished account, both doors).
     */
    private const LEAD = ['lead_source' => 'signup', 'plan' => 'libre'];

    /**
     * A Libre account was asked for. For email, the visitor is kept for the confirmation: the link is often opened in a
     * mail app's own browser, which has no analytics cookie, and the finished registration should still be credited to
     * whatever brought the signup. Four days: the link lasts three.
     */
    public static function signedUp(int $userId, Request $request, string $method): void
    {
        $visitor = self::visitor($request);
        $events = ['generate_lead' => self::LEAD + ['method' => $method]];
        if ($method === 'google') {
            // Google's door has no inbox page to say sign_up from the browser.
            $events = ['sign_up' => ['method' => 'google', 'plan' => 'libre']] + $events;
        } elseif ($visitor !== null && filled(config('services.analytics.api_secret'))) {
            rescue(fn () => Cache::put('ga.signup.' . $userId, $visitor, now()->addDays(4)), null, false);
        }
        self::send($events, $visitor, $userId);
    }

    /** A Libre account became real: the emailed link was clicked, or Google vouched for the address. */
    public static function confirmed(int $userId, Request $request, string $method): void
    {
        $kept = rescue(fn () => Cache::pull('ga.signup.' . $userId), null, false);
        self::send([
            'email_confirmed' => ['method' => $method, 'plan' => 'libre'],
            'qualify_lead' => self::LEAD + ['method' => $method],
        ], $kept ?: self::visitor($request), $userId);
    }

    /** An order was opened: its visitor is kept on it, since the decision comes later, from someone else's browser. */
    public static function orderOpened(AsOrder $order, Request $request): void
    {
        if (! $order->wasRecentlyCreated) {
            return;
        }
        $visitor = self::visitor($request);
        if ($visitor !== null) {
            rescue(fn () => $order->forceFill(['gaClient' => $visitor])->saveQuietly(), null, false);
        }
        self::order('begin_checkout', $order);
    }

    /**
     * begin_checkout (the order opened), add_payment_info (the receipt sent), purchase (approved) or refund (revoked).
     * The total is what SalesLedger counts, so the two agree. The buyer's first approved order is also the lead
     * converted.
     */
    public static function order(string $event, AsOrder $order): void
    {
        $events = [$event => [
            'transaction_id' => (string) $order->orderNumber,
            'value' => (float) $order->total,
            'currency' => (string) $order->currency,
            'payment_type' => (string) $order->method,
            'items' => [[
                'item_id' => (string) $order->itemKey, 'item_name' => (string) $order->itemName, 'item_category' => (string) $order->kind,
                'price' => (float) $order->total, 'quantity' => 1,
            ]],
        ]];
        if ($event === 'purchase' && self::firstPurchase($order)) {
            $events['close_convert_lead'] = ['lead_source' => 'signup', 'value' => (float) $order->total, 'currency' => (string) $order->currency];
        }
        self::send($events, $order->gaClient, (int) $order->userId);
    }

    /** No other order of this buyer was ever approved (a revoked one was, and keeps its approvedAt). Unsure: not first. */
    private static function firstPurchase(AsOrder $order): bool
    {
        return ! rescue(fn () => AsOrder::where('userId', $order->userId)->where('id', '!=', $order->id)
            ->whereNotNull('approvedAt')->exists(), true, false);
    }
}
