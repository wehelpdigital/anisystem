<?php

namespace App\Support;

use App\Http\Controllers\FieldHelperController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every page a person opens, sorted as it is written (2026-11-23, the owner: "in the admin panel, i wanna have the
 * option to view what users opened (pages) to know what makes them interested and a report module as well").
 *
 * One row on as_page_views per page: who (the member's account, or a guest's anee_vid cookie), the path with ids and
 * tokens folded, the page title, the site section or app tool it belongs to, and the crop it is about when the
 * address or the ?crop= names one. Guests on public pages, members on public pages and inside the app. Staff, staff
 * looking through a member's eyes, the test and system accounts, robots, prefetches and fetches leave no row. The
 * season shell swaps its modules without a page load, so it tells seen() which one came up.
 *
 * A guest's rows pass to the account at sign up (claim(), from VisitorJourney::claim), so the pages that won someone
 * over stay with them. Written after the answer has gone out; never throws. The admin panel reads it through
 * App\Support\InterestReport and forUser()/profile() here.
 */
final class PageViews
{
    public const TABLE = 'as_page_views';

    /** The query keys worth keeping (never an email, a token or an id). */
    private const QUERY = ['q', 'crop', 'tab', 'module', 'read', 'plan'];

    /** Never a page view: the plumbing, the panel itself and the one-time doors. */
    private const SKIP = ['admin', 'mother-api', 'api', 'deploy-check', 'launch.html', 'offline', 'sw.js', 'storage', 'build',
        'livewire', 'up', 'site-preview', 'blog-preview', 'reset-password', 'verify-email', 'worker-invite', 'pw', 'auth',
        'face', 'cron', 'svc', 'logout'];

    /** Doors whose next part is a secret: only the door is kept. */
    private const TOKEN_DOORS = ['ask-anee/answer', 'ask-anee/question', 's'];

    /** The same page again inside this many seconds is a reload, not a view. */
    private const RELOAD = 30;

    /** Test runs and the probes: their clicks are not anybody's interest. */
    private const TEST_MAIL = '/@(anisystem|example)\.test$|@example\.(com|org)$/i';

    /** After a page was answered (TrackJourney). $vid is the guest id VisitorJourney used, when it counted the visit. */
    public static function note(Request $request, Response $response, ?string $vid): void
    {
        try {
            if (! VisitorJourney::isPageView($request, $response)) {
                return;
            }
            $path = '/' . ltrim($request->path(), '/');
            $user = Auth::user();
            if (! self::countsFor($request, $user, $path)) {
                return;
            }
            // A guest is counted only when VisitorJourney counted them (a person, on a public page).
            if (! $user && ! $vid) {
                return;
            }
            $query = [];
            foreach (self::QUERY as $k) {
                $v = $request->query($k);
                if (is_string($v) && trim($v) !== '') {
                    $query[$k] = mb_substr(trim($v), 0, 80);
                }
            }
            $row = self::row($path, $query, VisitorJourney::title($response), $user?->id, $vid ?? VisitorJourney::vid($request),
                VisitorJourney::referrer($request, false), self::device((string) $request->userAgent()));
            if ($row) {
                app()->terminating(fn () => self::write($row));
            }
        } catch (\Throwable $e) {
            // Never a page for a count.
        }
    }

    /**
     * The season shell brought up a module without a page load (POST /app/page-seen): the same row a page load of
     * that module would have made. Members only; the path must be one of the app's own.
     */
    public static function seen(Request $request): void
    {
        try {
            $user = Auth::user();
            $raw = (string) $request->input('path', '');
            $parts = parse_url($raw);
            $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
            if (! $user || ! str_starts_with($path, '/app') || ! self::countsFor($request, $user, $path)) {
                return;
            }
            parse_str((string) ($parts['query'] ?? ''), $all);
            $query = [];
            foreach (self::QUERY as $k) {
                if (isset($all[$k]) && is_string($all[$k]) && trim($all[$k]) !== '') {
                    $query[$k] = mb_substr(trim($all[$k]), 0, 80);
                }
            }
            $title = trim(mb_substr(strip_tags((string) $request->input('title', '')), 0, 140)) ?: null;
            $row = self::row($path, $query, $title, (int) $user->id, VisitorJourney::vid($request), null,
                self::device((string) $request->userAgent()));
            if ($row) {
                app()->terminating(fn () => self::write($row));
            }
        } catch (\Throwable $e) {
            // A lost view is a lost view.
        }
    }

    /** The guest's rows become the new member's (sign up, both doors). */
    public static function claim(int $userId, string $vid): void
    {
        try {
            DB::table(self::TABLE)->where('vid', $vid)->whereNull('userId')->update(['userId' => $userId]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Guest rows after 180 days, every row after 400 (a year of members, compared with the year before's last weeks). */
    public static function prune(): int
    {
        $n = DB::table(self::TABLE)->whereNull('userId')->where('viewedAt', '<', now()->subDays(180))->delete();

        return $n + DB::table(self::TABLE)->where('viewedAt', '<', now()->subDays(400))->delete();
    }

    /** Whether this person's pages are counted at all. */
    public static function countsFor(Request $request, ?User $user, string $path): bool
    {
        // The live site only: a local copy shares the live database, and its clicks are ours, not a visitor's.
        if (! in_array(strtolower($request->getHost()), array_map('strtolower', (array) config('services.analytics.hosts')), true)) {
            return false;
        }
        $first = strtolower((string) explode('/', ltrim($path, '/'))[0]);
        if (in_array($first, self::SKIP, true)) {
            return false;
        }
        if ($user) {
            // Staff, and staff looking through a member's eyes, are not visitors; nor are the test and system accounts.
            if ($request->hasSession() && $request->session()->has('admin_impersonator')) {
                return false;
            }
            if ($user->isSuperAdmin() || in_array($user->email, User::SYSTEM_EMAILS, true) || preg_match(self::TEST_MAIL, (string) $user->email)) {
                return false;
            }

            // Members are people; only the scripted browsers of a test run are not.
            return ! preg_match('/headless|lighthouse|puppeteer|playwright|selenium|phantom/i', (string) $request->userAgent());
        }

        // Guests never see the app; VisitorJourney has already asked whether this is a person.
        return $first !== 'app';
    }

    /** One row, sorted: what the page is, and which crop it is about. Null when the page is not worth one. */
    private static function row(string $path, array $query, ?string $title, ?int $userId, ?string $vid, ?string $referrer, string $device): ?array
    {
        $path = self::fold($path);
        if ($path === null) {
            return null;
        }
        $c = self::classify($path, $query, $title);
        $search = isset($query['q']) ? mb_strtolower(trim(preg_replace('/\s+/u', ' ', $query['q']))) : null;
        // A search with an email or a phone number in it is not kept.
        if ($search !== null && (str_contains($search, '@') || preg_match('/\d{7,}/', $search))) {
            $search = null;
            unset($query['q']);
        }

        return [
            'userId' => $userId,
            'vid' => $vid,
            'area' => $c['area'],
            'kind' => $c['kind'],
            'topic' => $c['topic'] !== null ? mb_substr($c['topic'], 0, 120) : null,
            'crop' => $c['crop'],
            'path' => mb_substr($path, 0, 191),
            'query' => $query ? mb_substr(urldecode(http_build_query($query)), 0, 191) : null,
            'search' => $search !== null && $search !== '' ? mb_substr($search, 0, 120) : null,
            'title' => $title !== null ? mb_substr($title, 0, 160) : null,
            'referrer' => $referrer,
            'device' => $device,
            'viewedAt' => now(),
        ];
    }

    private static function write(array $row): void
    {
        try {
            // A reload, or the shell telling us about the module its own page load just showed, is not a second view.
            // (Asked of the table, not the cache: the cache lives in the database here and would keep a row per view.)
            $again = DB::table(self::TABLE)
                ->where($row['userId'] ? 'userId' : 'vid', $row['userId'] ?: $row['vid'])
                ->where('viewedAt', '>=', now()->subSeconds(self::RELOAD))
                ->where('path', $row['path'])
                ->when($row['query'] === null, fn ($q) => $q->whereNull('query'), fn ($q) => $q->where('query', $row['query']))
                ->exists();
            if (! $again) {
                DB::table(self::TABLE)->insert($row);
            }
        } catch (\Throwable $e) {
            // A lost view is a lost view.
        }
    }

    /**
     * The path as it is kept: a country face dropped, ids and tokens folded to :id, and only the door of an address
     * that carries a secret. Null for a door that is never counted.
     */
    public static function fold(string $path): ?string
    {
        $segs = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
        // /ph/pricing and /pricing are one page.
        if (isset($segs[0]) && in_array($segs[0], ['ph', 'en'], true)) {
            array_shift($segs);
        }
        $joined = implode('/', $segs);
        foreach (self::TOKEN_DOORS as $door) {
            if ($joined === $door || str_starts_with($joined, $door . '/')) {
                return '/' . $door;
            }
        }
        if (isset($segs[0]) && in_array(strtolower($segs[0]), self::SKIP, true)) {
            return null;
        }
        foreach ($segs as $i => $s) {
            // A number, or a long random looking word, names one record or one secret, never a kind of page.
            if (ctype_digit($s) || (strlen($s) >= 20 && preg_match('/^[A-Za-z0-9_-]+$/', $s) && preg_match('/\d/', $s) && ! str_contains($s, '-'))) {
                $segs[$i] = ':id';
            }
        }

        return '/' . implode('/', $segs);
    }

    /**
     * What a page is: [area, kind, topic, crop]. kind is the site section (crops, pests, blog, pricing...) or "app";
     * topic is the page inside it (a guide's slug) or the app tool (npk-plus, sm:notes, field-helpers:pests).
     */
    public static function classify(string $path, array $query = [], ?string $title = null): array
    {
        $segs = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
        $first = strtolower($segs[0] ?? '');
        $second = $segs[1] ?? null;
        // ?crop= carries a catalogue key (corn_yellow) or a plain word (banana).
        $crop = isset($query['crop']) ? self::cropOf(preg_split('/[_\-\s]+/', strtolower($query['crop']))) : null;

        if ($first === 'app') {
            $tool = strtolower($segs[1] ?? '');
            $topic = match (true) {
                $tool === '' => 'home',
                $tool === 'sm-activities' => 'sm:' . (preg_match('/^[a-z_]{2,20}$/', (string) ($query['module'] ?? '')) ? $query['module'] : 'activities'),
                $tool === 'sm' || str_starts_with($tool, 'sm-') => 'seasons',
                $tool === 'field-helpers' => 'field-helpers:' . strtolower($segs[2] ?? ''),
                default => $tool,
            };

            return ['area' => 'app', 'kind' => 'app', 'topic' => $topic, 'crop' => $crop];
        }

        $section = $first === 'question' ? 'questions' : $first;
        $kind = match (true) {
            $first === '' => 'home',
            isset(SitePages::SECTIONS[$section]) => $section,
            in_array($first, ['pricing', 'checkout', 'purchase'], true) => 'pricing',
            in_array($first, ['signup', 'register', 'login', 'verify-notice', 'verify', 'forgot-password', 'welcome'], true) => 'account',
            in_array($first, ['how-it-works', 'features-tour'], true) => 'how-it-works',
            $first === 'ask-anee' => 'ask-anee',
            $first === 'start' => 'ad-landing',
            in_array($first, ['best-farm-management-app-philippines', 'best-farm-management-software-philippines', 'best-farm-management-philippines'], true) => 'best-app',
            in_array($first, ['legal', 'privacy', 'terms', 'cookies'], true) => 'legal',
            in_array($first, ['about', 'contact'], true) => 'about',
            default => 'other',
        };
        $topic = match ($kind) {
            'home' => null,
            'other' => $first,
            'ask-anee' => $second ? 'ask-anee/' . $second : null,
            default => $second,
        };

        if ($crop === null && isset(SitePages::SECTIONS[$section])) {
            if ($section === 'rice-varieties') {
                $crop = 'Rice';
            } elseif ($section === 'crop-varieties' && $second) {
                $crop = self::varietyCrop($second);
            }
            if ($crop === null && $second) {
                $crop = self::cropOf(explode('-', strtolower($second)));
            }
            // A pest, disease or weed page's address may not name its crop; its title usually does. Not a section's
            // own list page, whose title names several crops ("Palay, Mais, Pests").
            if ($crop === null && $second && $title && in_array($section, ['pests', 'diseases', 'weeds', 'nutrient-deficiencies', 'problems', 'questions'], true)) {
                $crop = self::cropOf(preg_split('/[^\p{L}]+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY));
            }
        }

        return ['area' => 'site', 'kind' => $kind, 'topic' => $topic, 'crop' => $crop];
    }

    /** The crop a crop variety page (or a crop's finder) belongs to. */
    private static function varietyCrop(string $slug): ?string
    {
        static $bySlug = null;
        if ($bySlug === null) {
            $bySlug = [];
            $crops = CropVarieties::crops();
            foreach (CropVarieties::all() as $v) {
                if (isset($v['slug'], $v['crop'])) {
                    $bySlug[$v['slug']] = $crops[$v['crop']]['name'] ?? ucfirst($v['crop']);
                }
            }
            foreach ($crops as $c) {
                $bySlug[$c['slug']] ??= $c['name'];
            }
        }
        $name = $bySlug[$slug] ?? null;

        // "Corn (yellow)" and "Corn" are one crop's interest.
        return $name !== null ? self::cropOf(preg_split('/[^\p{L}]+/u', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY)) ?? $name : null;
    }

    /** The first crop named by these words, in its plain English name ("Rice", "Sweet potato"), or null. */
    public static function cropOf(array $words): ?string
    {
        $vocab = self::vocab();
        $words = array_values(array_map(fn ($w) => mb_strtolower(trim((string) $w)), $words));
        // In reading order; at each word a pair like "sweet potato" comes before the single "potato".
        $n = count($words);
        for ($i = 0; $i < $n; $i++) {
            if ($i < $n - 1 && isset($vocab[$words[$i] . '-' . $words[$i + 1]])) {
                return $vocab[$words[$i] . '-' . $words[$i + 1]];
            }
            if (isset($vocab[$words[$i]])) {
                return $vocab[$words[$i]];
            }
        }

        return null;
    }

    /** word => crop name, from the crop catalogue: English names, Tagalog names and the variety addresses. */
    private static function vocab(): array
    {
        static $vocab = null;
        if ($vocab !== null) {
            return $vocab;
        }
        // Tagalog words that are also everyday words ("tubo" is profit, "gabi" is night, "upo" is sit).
        $ambiguous = ['tubo', 'gabi', 'upo', 'mani', 'bulak', 'goma', 'batad', 'atis', 'suha', 'palma', 'gulay', 'mixed', 'field', 'bulb', 'sweet', 'white', 'yellow', 'dry', 'wet'];
        $vocab = [];
        foreach (CropCatalog::CROPS as $key => $c) {
            $label = (string) ($c['label'] ?? $key);
            $english = trim((string) preg_split('/[,(\/]/', $label)[0]);
            if ($english === '' || in_array(mb_strtolower($english), ['vegetables'], true)) {
                continue;
            }
            $name = mb_strtoupper(mb_substr($english, 0, 1)) . mb_strtolower(mb_substr($english, 1));
            $add = function (string $w) use (&$vocab, $name, $ambiguous) {
                $w = mb_strtolower(trim($w));
                if ($w !== '' && mb_strlen($w) > 2 && ! in_array($w, $ambiguous, true)) {
                    $vocab[$w] ??= $name;
                }
            };
            $add(str_replace(' ', '-', $english));
            $add(str_replace('_', '-', preg_replace('/_.*$/', '', $key)));
            if (preg_match('/\(([^()]+)\)\s*$/u', $label, $m)) {
                $tl = trim($m[1]);
                $add(str_replace(' ', '-', $tl));
                // "Mais na matamis" is still mais.
                $add(explode(' ', $tl)[0]);
            }
        }
        foreach (CropVarieties::SLUGS as $key => $slug) {
            if (isset($vocab[$key])) {
                $vocab[$slug] ??= $vocab[$key];
            }
        }
        // The words farmers use that the catalogue spells another way.
        foreach (['maize' => 'Corn', 'palay' => 'Rice', 'bigas' => 'Rice', 'sitao' => 'String bean', 'ube' => 'Purple yam',
            'saba' => 'Banana', 'lakatan' => 'Banana', 'cardava' => 'Banana', 'coco' => 'Coconut', 'kalamansi' => 'Calamansi',
            'pepper' => 'Chili', 'chilli' => 'Chili', 'onions' => 'Onion', 'bananas' => 'Banana', 'tomatoes' => 'Tomato',
            'potatoes' => 'Potato', 'mangoes' => 'Mango', 'vegetable' => null] as $w => $name) {
            if ($name === null) {
                unset($vocab[$w]);
            } else {
                $vocab[$w] ??= $name;
            }
        }

        return $vocab;
    }

    /** Phone, Tablet or Computer. */
    private static function device(string $ua): string
    {
        return preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua) ? 'Tablet'
            : (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $ua) ? 'Phone' : 'Computer');
    }

    /* ------------------------------------------------------------------ reading */

    /** The words the admin panel shows for a kind of page. */
    public static function kindLabel(string $kind): string
    {
        $mine = [
            'home' => 'Home page', 'pricing' => 'Pricing and checkout', 'account' => 'Sign up and log in',
            'how-it-works' => 'How it works', 'ask-anee' => 'Ask Anee', 'ad-landing' => 'Ad landing page',
            'best-app' => 'Best farm app pages', 'legal' => 'Legal pages', 'about' => 'About and contact',
            'app' => 'Inside the app', 'other' => 'Other pages',
        ];

        return $mine[$kind] ?? (SitePages::SECTIONS[$kind]['label'] ?? ucfirst(str_replace('-', ' ', $kind)));
    }

    /** The words the admin panel shows for an app tool. */
    public static function toolLabel(?string $topic): string
    {
        $topic = (string) $topic;
        $modules = [
            'activities' => 'Activities', 'settings' => 'Settings', 'lots' => 'Lots', 'workers' => 'Workers',
            'inventory' => 'Inventory', 'documentation' => 'Documentation', 'notes' => 'Notes', 'media' => 'Gallery',
            'gallery' => 'Gallery', 'growth' => 'Growth Stages', 'weather' => 'Weather', 'reports' => 'Reports', 'ai' => 'Chat Anee',
            'maps' => 'Maps', 'draw' => 'Draw', 'tags' => 'Tags', 'expenses' => 'Expenses', 'collab' => 'Collab Room',
        ];
        if (str_starts_with($topic, 'sm:')) {
            $m = substr($topic, 3);

            return 'Season: ' . ($modules[$m] ?? ucfirst(str_replace('_', ' ', $m)));
        }
        if (str_starts_with($topic, 'field-helpers:')) {
            $t = substr($topic, 14);

            return FieldHelperController::TOOLS[$t][0] ?? ('Field helpers' . ($t !== '' ? ': ' . ucfirst($t) : ''));
        }
        $tools = [
            'home' => 'App dashboard', 'seasons' => 'Seasons list', 'community' => 'Community', 'community-plan' => 'Community',
            'auto' => 'Anee Auto', 'protocol-builder' => 'Protocol Builder', 'sky-weather' => 'Weather', 'weather' => 'Weather',
            'land-prep' => 'Land Prep Planner', 'npk-plus' => 'NPK Plus', 'when-to-plant' => 'When to Plant',
            'what-to-plant' => 'What to Plant', 'variety-research' => 'Variety Research', 'seed-count-distance' => 'Seed Count and Distance',
            'satellite' => 'Satellite Analysis', 'crop-protocol' => 'Crop Protocol', 'compare' => 'Compare', 'stash' => 'The Stash',
            'market-prices' => 'Market Price Forecast', 'lcc-checker' => 'LCC Checker', 'hatian' => 'Hatian Calculator',
            'farm-lots' => 'Lots', 'benta-o-bilad' => 'Benta o Bilad', 'tags' => 'Tags', 'heat-map' => 'Heat Map Forecast',
            'field-profiles' => 'Field profiles', 'ai' => 'Chat Anee', 'ai-home' => 'Chat Anee', 'workers' => 'Workers',
            'welcome' => 'Welcome page', 'trash' => 'Trash', 'support' => 'Support', 'how-to-plant' => 'How to Plant',
            'ai-credits' => 'AI credits', 'varieties' => 'Varieties', 'tutorials' => 'Tutorials', 'notes' => 'Notes',
            'gallery' => 'Gallery', 'maps' => 'Maps', 'draw' => 'Draw', 'contacts' => 'Contact List', 'help' => 'Help',
            'settings' => 'Settings', 'shop' => 'Shop', 'account' => 'Account', 'notifications' => 'Notifications',
        ];

        return $tools[$topic] ?? ucfirst(str_replace(['-', '_'], ' ', $topic));
    }

    /** What one member opened, newest first, a page of 30 (the client sheet). Times in Manila. */
    public static function forUser(int $userId, ?int $beforeId = null, int $limit = 30): array
    {
        $q = DB::table(self::TABLE)->where('userId', $userId);
        if ($beforeId) {
            $q->where('id', '<', $beforeId);
        }
        $rows = $q->orderByDesc('id')->limit($limit + 1)
            ->get(['id', 'area', 'kind', 'topic', 'crop', 'path', 'query', 'title', 'referrer', 'viewedAt']);
        $more = $rows->count() > $limit;
        $signed = DB::table('anisystem_users')->where('id', $userId)->value('created_at');

        return [
            'rows' => $rows->take($limit)->map(fn ($r) => [
                'id' => (int) $r->id,
                'at' => Carbon::parse($r->viewedAt)->format('M j, g:i A'),
                'title' => $r->title ?: ($r->area === 'app' ? self::toolLabel($r->topic) : $r->path),
                'path' => $r->path . ($r->query ? '?' . $r->query : ''),
                'what' => $r->area === 'app' ? self::toolLabel($r->topic) : self::kindLabel($r->kind),
                'area' => $r->area,
                'crop' => $r->crop,
                'from' => $r->referrer,
                'before' => $signed && Carbon::parse($r->viewedAt)->lt(Carbon::parse($signed)),
            ])->values()->all(),
            'nextCursor' => $more ? (int) $rows->take($limit)->last()->id : null,
        ];
    }

    /** One member at a glance: the last 90 days counted by section, tool and crop (the client sheet's card). */
    public static function profile(int $userId): array
    {
        $since = now()->subDays(90);
        $base = fn () => DB::table(self::TABLE)->where('userId', $userId)->where('viewedAt', '>=', $since);
        $tot = $base()->selectRaw('COUNT(*) n, COUNT(DISTINCT DATE(viewedAt)) d, MAX(viewedAt) last')->first();
        $all = DB::table(self::TABLE)->where('userId', $userId)->selectRaw('COUNT(*) n, MIN(viewedAt) first')->first();
        $kinds = $base()->where('area', 'site')->selectRaw('kind, COUNT(*) n')->groupBy('kind')->orderByDesc('n')->limit(4)->get();
        $tools = $base()->where('area', 'app')->selectRaw('topic, COUNT(*) n')->groupBy('topic')->orderByDesc('n')->limit(5)->get();
        $crops = $base()->whereNotNull('crop')->selectRaw('crop, COUNT(*) n')->groupBy('crop')->orderByDesc('n')->limit(5)->get();

        return [
            'total' => (int) ($all->n ?? 0),
            'firstAt' => $all && $all->first ? Carbon::parse($all->first)->format('M j, Y') : null,
            'views90' => (int) ($tot->n ?? 0),
            'days90' => (int) ($tot->d ?? 0),
            'lastAt' => $tot && $tot->last ? Carbon::parse($tot->last)->diffForHumans() : null,
            'kinds' => $kinds->map(fn ($r) => ['label' => self::kindLabel($r->kind), 'n' => (int) $r->n])->all(),
            'tools' => $tools->map(fn ($r) => ['label' => self::toolLabel($r->topic), 'n' => (int) $r->n])->all(),
            'crops' => $crops->map(fn ($r) => ['label' => $r->crop, 'n' => (int) $r->n])->all(),
        ];
    }
}
