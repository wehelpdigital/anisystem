<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The pages a visitor opened before they signed up (2026-11-16, the owner: "can we track down the travel path of a
 * user who registered so that we can check it what page she accessed first until he/she registered? maybe we can do
 * that when checking the admin panel and or in the mother software as well").
 *
 * A first party cookie (anee_vid, a random id and nothing else) names the browser. note() runs after every public
 * page a guest opens and adds a step to that browser's row on as_visitor_journeys: when, the path, the page title,
 * and the other site that sent them when there was one. The write happens after the answer has gone out, so a page
 * never waits for it. Robots, link previews, prefetches and signed in members leave no steps.
 *
 * claim() hands the row to a new account at sign up (both doors); confirmed() and chose() mark the email link and
 * the welcome page's choice. forUser() is what the admin panel shows; the mother app reads the same table. Rows
 * nobody claimed are dropped after 90 days (prune()). Never throws: a step is never worth a page.
 */
final class VisitorJourney
{
    public const COOKIE = 'anee_vid';

    private const TABLE = 'as_visitor_journeys';

    /** Steps kept on one row: the first ones always, then the newest. */
    private const CAP = 150;

    private const HEAD = 30;

    /** The query keys worth keeping with a step (never an email or a token). */
    private const QUERY = ['read', 'plan', 'q', 'page', 'crop', 'tab'];

    private const BOTS = '/bot|crawl|spider|slurp|preview|externalhit|facebot|facebookcatalog|meta-external|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|python|curl|wget|httpclient|okhttp|java\/|go-http|axios|node-fetch|undici|scrapy|ahrefs|semrush|mj12|dotbot|yandex|baidu|bytespider|petal|seznam|duckduck|embedly|whatsapp|telegram|viber|discord|slack|skype|vkshare|pinterest|redditbot|applebot|google-inspection|chrome-lighthouse|phantom|selenium|puppeteer|playwright/i';

    private const SKIP = ['app', 'admin', 'mother-api', 'api', 'deploy-check', 'launch.html', 'offline', 'sw.js', 'storage', 'build', 'livewire', 'up'];

    /**
     * After a page was answered: a step for a guest's own visit, written once the answer is out. Answers the browser's
     * id when the visit counted (App\Support\PageViews files the same view under it), else null.
     */
    public static function note(Request $request, Response $response): ?string
    {
        try {
            if (! self::counts($request, $response)) {
                return null;
            }
            $vid = self::vid($request);
            $isNew = $vid === null;
            if ($isNew) {
                $vid = Str::random(32);
                Cookie::queue(Cookie::make(self::COOKIE, $vid, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
            }
            $step = self::step($request, $response);
            $first = [
                'landing' => mb_substr($step['p'] . (isset($step['q']) ? '?' . http_build_query($step['q']) : ''), 0, 255),
                'referrer' => self::referrer($request, true),
                'utm' => self::utm($request),
                'device' => self::device((string) $request->userAgent()),
            ];
            app()->terminating(function () use ($vid, $step, $first) {
                self::write($vid, $step, $first);
            });

            return $vid;
        } catch (\Throwable $e) {
            // Never a page for a step.
            return null;
        }
    }

    /** The account born on this browser takes its road (sign up, both doors). */
    public static function claim(User $user, Request $request, string $method): void
    {
        try {
            $vid = self::vid($request);
            if (! $vid) {
                return;
            }
            $row = DB::table(self::TABLE)->where('vid', $vid)->first(['id', 'userId', 'steps']);
            if (! $row || $row->userId) {
                return;
            }
            $steps = json_decode((string) $row->steps, true) ?: [];
            $steps[] = ['t' => time(), 'e' => 'signup', 'n' => $method === 'google' ? 'Signed up with Google' : 'Signed up with email'];
            DB::table(self::TABLE)->where('id', $row->id)->update([
                'userId' => $user->id,
                'signedUpAt' => now(),
                'method' => $method,
                // Google proves the address on the spot.
                'confirmedAt' => $method === 'google' ? now() : null,
                'steps' => json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        } finally {
            // The pages this browser opened as a guest are the new member's too (the Interests report).
            if (isset($vid) && $vid) {
                PageViews::claim((int) $user->id, $vid);
            }
        }
    }

    /** The emailed link was tapped (on whatever browser). */
    public static function confirmed(int $userId): void
    {
        try {
            DB::table(self::TABLE)->where('userId', $userId)->whereNull('confirmedAt')
                ->update(['confirmedAt' => now(), 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** What the new member picked on the welcome page: read, dashboard or home. */
    public static function chose(int $userId, string $choice): void
    {
        try {
            DB::table(self::TABLE)->where('userId', $userId)->whereNull('choice')
                ->update(['choice' => $choice, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Rows nobody claimed, untouched for 90 days. */
    public static function prune(): int
    {
        return DB::table(self::TABLE)->whereNull('userId')->where('lastAt', '<', now()->subDays(90))->delete();
    }

    /**
     * The road as the admin panel shows it, or null when this account has none (it signed up before 2026-11-16, or
     * on a browser that keeps no cookies). Times in Manila.
     */
    public static function forUser(int $userId): ?array
    {
        $row = DB::table(self::TABLE)->where('userId', $userId)->orderByDesc('id')->first();

        return $row ? self::present($row) : null;
    }

    /** One row, worded for a person: shared with the mother app's twin (AnisystemClientsController::journey). */
    public static function present(object $row): array
    {
        $tz = 'Asia/Manila';
        $at = fn ($v) => $v ? Carbon::parse($v)->timezone($tz) : null;
        $first = $at($row->firstAt);
        $signed = $at($row->signedUpAt);
        $steps = json_decode((string) $row->steps, true) ?: [];
        $out = [];
        $prev = null;
        $visit = 1;
        foreach ($steps as $s) {
            $when = Carbon::createFromTimestamp((int) ($s['t'] ?? 0))->timezone($tz);
            // Half an hour without a page starts a new visit.
            if ($prev && $when->diffInMinutes($prev, true) > 30) {
                $visit++;
            }
            $out[] = [
                'at' => $when->format('M j, g:i A'),
                'visit' => $visit,
                'path' => isset($s['p']) ? $s['p'] . (! empty($s['q']) ? '?' . urldecode(http_build_query($s['q'])) : '') : null,
                'title' => $s['n'] ?? null,
                'from' => $s['r'] ?? null,
                'event' => $s['e'] ?? null,
                'after' => $signed && $when->gt($signed) && ($s['e'] ?? null) !== 'signup',
            ];
            $prev = $when;
        }

        return [
            'firstAt' => $first?->format('M j, Y g:i A'),
            'landing' => $row->landing,
            'referrer' => $row->referrer,
            'utm' => json_decode((string) $row->utm, true) ?: null,
            'device' => $row->device,
            'views' => (int) $row->views,
            'visits' => $visit,
            'signedUpAt' => $signed?->format('M j, Y g:i A'),
            'method' => $row->method,
            'confirmedAt' => $at($row->confirmedAt)?->format('M j, Y g:i A'),
            'choice' => $row->choice,
            // "2 days" from the first page to the account.
            'took' => $first && $signed ? $first->diffForHumans($signed, ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2]) : null,
            'steps' => $out,
        ];
    }

    /** Whether this answer is a person's own page view worth a step. */
    private static function counts(Request $request, Response $response): bool
    {
        if (! self::isPageView($request, $response)) {
            return false;
        }
        $first = strtolower((string) explode('/', ltrim($request->path(), '/'))[0]);
        if (in_array($first, self::SKIP, true)) {
            return false;
        }
        // The road is the one before the account; a member's pages are App\Support\PageViews' (2026-11-23).
        if (Auth::check()) {
            return false;
        }

        return self::isPerson($request);
    }

    /** A whole page opened in the browser's own window: not a fetch, a prefetch, a frame, an error or a file. */
    public static function isPageView(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson() || $response->getStatusCode() !== 200) {
            return false;
        }
        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }
        // Prefetches and frames are not visits.
        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?: $request->header('Purpose')));
        $dest = (string) $request->header('Sec-Fetch-Dest');

        return ! str_contains($purpose, 'prefetch') && ($dest === '' || $dest === 'document');
    }

    /** A person's browser rather than a robot, a link preview or a test run. */
    public static function isPerson(Request $request): bool
    {
        $ua = (string) $request->userAgent();
        // A person's browser always says which languages it reads; most robots do not.
        if ($ua === '' || preg_match(self::BOTS, $ua) || blank($request->header('Accept-Language'))) {
            return false;
        }

        return Crawler::name($request) === null;
    }

    /** The page's own title, read off the top of the page, without the site's name on the end. */
    public static function title(Response $response): ?string
    {
        $body = $response->getContent();
        if (is_string($body) && preg_match('#<title[^>]*>(.*?)</title>#is', substr($body, 0, 8000), $m)) {
            $t = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $t = trim((string) preg_replace('/\s*[|·]\s*anee\.io\s*$/iu', '', $t));

            return $t !== '' ? mb_substr($t, 0, 140) : null;
        }

        return null;
    }

    public static function vid(Request $request): ?string
    {
        $v = $request->cookie(self::COOKIE);

        return is_string($v) && preg_match('/^[A-Za-z0-9]{32}$/', $v) ? $v : null;
    }

    private static function step(Request $request, Response $response): array
    {
        $step = ['t' => time(), 'p' => mb_substr('/' . ltrim($request->path(), '/'), 0, 160)];
        $q = [];
        foreach (self::QUERY as $k) {
            $v = $request->query($k);
            if (is_string($v) && $v !== '') {
                $q[$k] = mb_substr($v, 0, 120);
            }
        }
        if ($q) {
            $step['q'] = $q;
        }
        // The page's own title, read off the top of the page (every page carries the site's name; it is cut).
        if ($t = self::title($response)) {
            $step['n'] = $t;
        }
        if ($r = self::referrer($request, false)) {
            $step['r'] = $r;
        }
        if ($u = self::utm($request)) {
            $step['u'] = $u['source'] ?? ($u['campaign'] ?? 'ad');
        }

        return $step;
    }

    /** Another site that sent this visit: its host, or the whole address (trimmed) for the row's first referrer. */
    public static function referrer(Request $request, bool $whole): ?string
    {
        $ref = (string) $request->headers->get('referer');
        $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
        if ($host === '' || $host === strtolower($request->getHost()) || str_ends_with($host, '.anee.io') || $host === 'anee.io') {
            return null;
        }
        if (! $whole) {
            return mb_substr(preg_replace('/^(www|m|l|lm)\./', '', $host), 0, 80);
        }

        // Only the host and path: a query on another site's address may carry anything.
        return mb_substr($host . (string) parse_url($ref, PHP_URL_PATH), 0, 255);
    }

    private static function utm(Request $request): ?array
    {
        $out = [];
        foreach (['source', 'medium', 'campaign', 'content', 'term'] as $k) {
            $v = $request->query('utm_' . $k);
            if (is_string($v) && trim($v) !== '') {
                $out[$k] = mb_substr(trim($v), 0, 80);
            }
        }
        foreach (['fbclid' => 'facebook', 'gclid' => 'google'] as $click => $source) {
            if (filled($request->query($click))) {
                $out['source'] ??= $source;
                $out['click'] = $click;
            }
        }

        return $out ?: null;
    }

    /** "Phone, Android, Facebook app": enough to picture the screen they read on. */
    public static function device(string $ua): string
    {
        $kind = preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua) ? 'Tablet'
            : (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $ua) ? 'Phone' : 'Computer');
        $os = match (true) {
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/i', $ua) => 'Mac',
            (bool) preg_match('/CrOS/i', $ua) => 'ChromeOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => null,
        };
        $app = match (true) {
            (bool) preg_match('/FBAN|FBAV|FB_IAB|FBIOS/i', $ua) => 'Facebook app',
            (bool) preg_match('/Messenger|MESSENGER/', $ua) => 'Messenger',
            (bool) preg_match('/Instagram/i', $ua) => 'Instagram app',
            (bool) preg_match('/TikTok|musical_ly|BytedanceWebview/i', $ua) => 'TikTok app',
            (bool) preg_match('/GSA\//', $ua) => 'Google app',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/Edg\//', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Firefox|FxiOS/i', $ua) => 'Firefox',
            (bool) preg_match('/CriOS|Chrome/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            default => null,
        };

        return implode(', ', array_filter([$kind, $os, $app]));
    }

    private static function write(string $vid, array $step, array $first): void
    {
        try {
            $now = now();
            $row = DB::table(self::TABLE)->where('vid', $vid)->first(['id', 'steps', 'utm']);
            if (! $row) {
                DB::table(self::TABLE)->insertOrIgnore([
                    'vid' => $vid,
                    'firstAt' => $now,
                    'lastAt' => $now,
                    'landing' => $first['landing'],
                    'referrer' => $first['referrer'],
                    'utm' => $first['utm'] ? json_encode($first['utm'], JSON_UNESCAPED_UNICODE) : null,
                    'device' => mb_substr($first['device'], 0, 80),
                    'views' => 1,
                    'steps' => json_encode([$step], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return;
            }
            $steps = json_decode((string) $row->steps, true) ?: [];
            $last = end($steps) ?: null;
            // The same page again within half a minute is a reload, not a step.
            if ($last && ($last['p'] ?? null) === $step['p'] && ($step['t'] - (int) ($last['t'] ?? 0)) < 30) {
                DB::table(self::TABLE)->where('id', $row->id)->update(['lastAt' => $now]);

                return;
            }
            $steps[] = $step;
            if (count($steps) > self::CAP) {
                array_splice($steps, self::HEAD, count($steps) - self::CAP);
            }
            $patch = [
                'lastAt' => $now,
                'views' => DB::raw('views + 1'),
                'steps' => json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ];
            // The first ad tags, whenever they first show up.
            if (! $row->utm && $first['utm']) {
                $patch['utm'] = json_encode($first['utm'], JSON_UNESCAPED_UNICODE);
            }
            DB::table(self::TABLE)->where('id', $row->id)->update($patch);
        } catch (\Throwable $e) {
            // A lost step is a lost step.
        }
    }
}
