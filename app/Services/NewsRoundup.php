<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\AsSitePage;
use App\Models\AsSiteSetting;
use App\Support\AiUsage;
use App\Support\ArticleStyle;
use App\Support\SeoKeywords;
use App\Support\SitePages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Latest in Agriculture (2026-10-07): a farm news roundup, written by Anee
 * every few days from the RSS feeds kept in the mother app (as_news_feeds).
 *
 * One run:
 *   1. read every active feed and keep each story once (as_news_items, by
 *      its link), so a story two feeds carry is still one story
 *   2. take the stories no roundup has featured yet, from the last ten days
 *      (and never older than the last roundup's range)
 *   3. too few (under MIN_STORIES)? nothing is written: the run says why
 *   4. Anee picks the ones that matter to a Filipino farmer, groups them by
 *      theme, and writes for each a short summary in her own words and the
 *      "so what": why it matters on the farm. The page is then built here,
 *      not by the model: the links, the pictures and their credits come from
 *      the feed, so none of them can be invented
 *   5. the house rules are checked (PageWriter::audit) and the words cleaned
 *      (ArticleStyle); a page that misses goes back once with the list
 *   6. the page is published under /blog (kind 'roundup'), and its stories
 *      are marked featured so the next roundup leaves them out
 *
 * Asked for by the cron address (/cron/news-roundup?key=...), by the mother
 * app's "Write one now", or by the scheduler. Every few days only
 * (news.every_days, 3): an earlier ask is skipped unless forced.
 */
class NewsRoundup
{
    public const MIN_STORIES = 3;

    public const MAX_STORIES = 10;

    public const WINDOW_DAYS = 10;

    /** A run older than this that never finished is taken as dead. */
    public const STALE_MINUTES = 20;

    /** Sites whose posts are social videos: kept, but never their pictures (they expire). */
    private const SOCIAL = ['facebook.com', 'fb.watch', 'instagram.com', 'tiktok.com', 'x.com', 'twitter.com', 'youtube.com', 'youtu.be', 'threads.net'];

    /** A newsroom's own name, from its address. */
    private const PUBLISHERS = [
        'inquirer.net' => 'Inquirer', 'tribune.net.ph' => 'Daily Tribune', 'rappler.com' => 'Rappler', 'gmanetwork.com' => 'GMA News',
        'manilatimes.net' => 'The Manila Times', 'philstar.com' => 'Philstar', 'mb.com.ph' => 'Manila Bulletin', 'pna.gov.ph' => 'Philippine News Agency',
        'abs-cbn.com' => 'ABS CBN News', 'bworldonline.com' => 'BusinessWorld', 'businessmirror.com.ph' => 'BusinessMirror', 'pageone.ph' => 'PageOne',
        'sunstar.com.ph' => 'SunStar', 'news.mb.com.ph' => 'Manila Bulletin', 'manilastandard.net' => 'Manila Standard', 'interaksyon.philstar.com' => 'Interaksyon',
        'pia.gov.ph' => 'Philippine Information Agency', 'da.gov.ph' => 'Department of Agriculture', 'philrice.gov.ph' => 'PhilRice', 'pagasa.dost.gov.ph' => 'PAGASA',
        'bioengineer.org' => 'Bioengineer.org', 'reuters.com' => 'Reuters', 'bloomberg.com' => 'Bloomberg', 'cnnphilippines.com' => 'CNN Philippines',
        'onenews.ph' => 'One News', 'bilyonaryo.com' => 'Bilyonaryo', 'agriculture.com.ph' => 'Agriculture Monthly', 'ptvnews.ph' => 'PTV News',
        'bangkokpost.com' => 'Bangkok Post', 'khmertimeskh.com' => 'Khmer Times', 'rvasia.org' => 'Radio Veritas Asia', 'devex.com' => 'Devex',
        'thestar.com.my' => 'The Star', 'scmp.com' => 'South China Morning Post', 'nikkei.com' => 'Nikkei Asia', 'fao.org' => 'FAO', 'irri.org' => 'IRRI',
    ];

    /** Pages a feed sometimes gives that are not stories at all. */
    private const NOT_NEWS = ['pressreader.com'];

    /** A Facebook page's name, from its address. */
    private const PAGES = ['gmanews' => 'GMA News', 'abscbnnews' => 'ABS CBN News', 'inquirerdotnet' => 'Inquirer', 'rapplerdotcom' => 'Rappler',
        'pagasa.dost' => 'PAGASA', 'dost_pagasa' => 'PAGASA', 'philstarnews' => 'Philstar', 'cnnphilippines' => 'CNN Philippines', 'ptvph' => 'PTV News'];

    public function __construct(private AiClient $ai) {}

    // ------------------------------------------------------------------
    // The cron address
    // ------------------------------------------------------------------

    public static function cronKey(): string
    {
        return (string) AsSiteSetting::get('news.cron_key', '');
    }

    public static function cronUrl(): string
    {
        return url('/cron/news-roundup') . '?key=' . self::cronKey();
    }

    public static function everyDays(): int
    {
        return max(1, (int) AsSiteSetting::get('news.every_days', 3));
    }

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('as_news_runs');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Whether a page is a roundup (its schema is a NewsArticle). */
    public static function isRoundup(AsSitePage $page): bool
    {
        return ($page->kind ?? null) === 'roundup';
    }

    // ------------------------------------------------------------------
    // One run
    // ------------------------------------------------------------------

    /**
     * Should a roundup be written now? The reason when not, null when yes.
     */
    public function refusal(bool $force): ?string
    {
        if (! self::ready()) {
            return 'The news tables are not there yet.';
        }
        $busy = DB::table('as_news_runs')->where('status', 'running')
            ->where('created_at', '>=', now()->subMinutes(self::STALE_MINUTES))->exists();
        if ($busy) {
            return 'A roundup is being written right now.';
        }
        if (! $force) {
            $last = DB::table('as_news_runs')->where('status', 'created')->max('created_at');
            if ($last && Carbon::parse($last)->gt(now()->subDays(self::everyDays())->addHours(2))) {
                return 'The last roundup is under ' . self::everyDays() . ' days old (' . Carbon::parse($last)->timezone('Asia/Manila')->format('M j, g:i A') . ').';
            }
        }

        return null;
    }

    /** Opens a run's row and returns its id (or null with the reason why not). */
    public function open(string $trigger, bool $force): array
    {
        if ($why = $this->refusal($force)) {
            if (self::ready()) {
                DB::table('as_news_runs')->insert(['status' => 'skipped', 'reason' => $why, 'trigger' => $trigger, 'created_at' => now(), 'updated_at' => now()]);
            }

            return [null, $why];
        }
        // Runs left hanging (a worker killed mid write) are closed as failed.
        DB::table('as_news_runs')->where('status', 'running')->where('created_at', '<', now()->subMinutes(self::STALE_MINUTES))
            ->update(['status' => 'failed', 'reason' => 'It stopped before it finished.', 'updated_at' => now()]);

        return [DB::table('as_news_runs')->insertGetId(['status' => 'running', 'reason' => 'Reading the feeds', 'trigger' => $trigger,
            'created_at' => now(), 'updated_at' => now()]), null];
    }

    /** The whole run, for a run already opened. */
    public function work(int $runId): array
    {
        $note = fn (array $f) => DB::table('as_news_runs')->where('id', $runId)->update($f + ['updated_at' => now()]);
        try {
            $this->fetch();
            $items = $this->candidates();
            if ($items->count() < self::MIN_STORIES) {
                $note(['status' => 'skipped', 'reason' => 'No new stories yet (' . $items->count() . ' not featured before).', 'itemCount' => $items->count()]);

                return ['status' => 'skipped', 'reason' => 'No new stories yet.'];
            }
            $note(['reason' => 'Anee is writing (' . $items->count() . ' new stories)']);
            return $this->finish($runId, $this->write($items));
        } catch (\Throwable $e) {
            report($e);
            $note(['status' => 'failed', 'reason' => Str::limit($e->getMessage(), 480, '')]);

            return ['status' => 'failed', 'reason' => $e->getMessage()];
        }
    }

    /**
     * A written roundup published, its stories marked featured, the run
     * closed. Shared by the model's writing and a hand written answer
     * (answer(): the same JSON, the same building and checks).
     */
    public function finish(int $runId, array $out): array
    {
        $note = fn (array $f) => DB::table('as_news_runs')->where('id', $runId)->update($f + ['updated_at' => now()]);
        $note(['tokensIn' => (int) ($out['result']['tokensIn'] ?? 0), 'tokensOut' => (int) ($out['result']['tokensOut'] ?? 0)]);
        if (! $out['ok']) {
            $status = ($out['skip'] ?? false) ? 'skipped' : 'failed';
            $note(['status' => $status, 'reason' => Str::limit((string) $out['error'], 480, '')]);

            return ['status' => $status, 'reason' => $out['error']];
        }
        $page = $this->publish($out['page']);
        DB::table('as_news_items')->whereIn('id', $out['used'])->update(['featuredPageId' => $page->id, 'featuredAt' => now(), 'updated_at' => now()]);
        $note(['status' => 'created', 'reason' => $page->title, 'pageId' => $page->id, 'itemCount' => count($out['used']),
            'rangeFrom' => $out['range'][0]->toDateString(), 'rangeTo' => $out['range'][1]->toDateString()]);
        if ((int) ($out['result']['tokensIn'] ?? 0) > 0) {
            try {
                AiUsage::record('news-roundup', 0, 0, $runId, AiSetting::current(), $out['result'], 0);
            } catch (\Throwable $e) {
            }
        }
        SitePages::forgetCaches();

        return ['status' => 'created', 'url' => SitePages::pageUrl($page), 'title' => $page->title];
    }

    /**
     * A roundup answer written by hand in the model's JSON (stories may name
     * their "link" instead of an id), built and checked the same way.
     */
    public function answer(array $data, Collection $items): array
    {
        $byLink = $items->keyBy(fn ($r) => $r->link);
        foreach ($data['themes'] ?? [] as $t => $theme) {
            foreach ($theme['stories'] ?? [] as $i => $story) {
                if (empty($story['id']) && isset($byLink[$story['link'] ?? ''])) {
                    $data['themes'][$t]['stories'][$i]['id'] = $byLink[$story['link']]->id;
                }
            }
        }
        $byId = $items->keyBy('id');
        $built = $this->build($data, $byId);
        if (count($built['used']) < self::MIN_STORIES) {
            return ['ok' => false, 'error' => 'Fewer than ' . self::MIN_STORIES . ' of those stories are new.', 'result' => []];
        }
        $span = collect($built['used'])->map(fn ($id) => Carbon::parse($byId[$id]->publishedAt));

        return ['ok' => true, 'page' => $built['page'], 'used' => $built['used'], 'range' => [$span->min(), $span->max()],
            'problems' => PageWriter::audit($built['page']), 'result' => []];
    }

    // ------------------------------------------------------------------
    // The feeds
    // ------------------------------------------------------------------

    /** Reads every active feed; new stories are kept, known ones left alone. */
    public function fetch(): array
    {
        $report = [];
        foreach (DB::table('as_news_feeds')->where('deleteStatus', 1)->where('isActive', 1)->get() as $feed) {
            $kept = 0;
            try {
                $res = Http::timeout(25)->withHeaders(['User-Agent' => 'anee.io news roundup (+https://anee.io)', 'Accept' => 'application/rss+xml, application/xml, text/xml'])->get($feed->url);
                if (! $res->successful()) {
                    throw new \RuntimeException('HTTP ' . $res->status());
                }
                $xml = @simplexml_load_string($res->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
                if (! $xml || ! isset($xml->channel)) {
                    throw new \RuntimeException('Not an RSS feed');
                }
                $n = 0;
                foreach ($xml->channel->item as $it) {
                    $n++;
                    $row = $this->story($it, (int) $feed->id);
                    if ($row) {
                        $kept += DB::table('as_news_items')->insertOrIgnore($row);
                    }
                }
                DB::table('as_news_feeds')->where('id', $feed->id)->update(['lastFetchedAt' => now(), 'lastStatus' => 'OK: ' . $n . ' stories, ' . $kept . ' new',
                    'lastItemCount' => $n, 'updated_at' => now()]);
            } catch (\Throwable $e) {
                DB::table('as_news_feeds')->where('id', $feed->id)->update(['lastFetchedAt' => now(), 'lastStatus' => Str::limit('Failed: ' . $e->getMessage(), 180, ''), 'updated_at' => now()]);
            }
            $report[$feed->id] = $kept;
        }

        return $report;
    }

    /** One RSS item as an as_news_items row, or null when it is not a story. */
    private function story(\SimpleXMLElement $it, int $feedId): ?array
    {
        $link = trim((string) $it->link);
        $title = trim(html_entity_decode(strip_tags((string) $it->title), ENT_QUOTES | ENT_HTML5));
        if ($link === '' || ! preg_match('#^https?://#i', $link) || $title === '') {
            return null;
        }
        // A social post's title opens with its counts: "333K views · 3.4K reactions | ...".
        $title = trim(preg_replace('/^[\d.,]+[KMB]?\s+views?\s*·\s*[\d.,]+[KMB]?\s+reactions?\s*\|\s*/iu', '', $title));
        $host = strtolower((string) preg_replace('/^www\./', '', (string) parse_url($link, PHP_URL_HOST)));
        $social = (bool) array_filter(self::SOCIAL, fn ($s) => $host === $s || str_ends_with($host, '.' . $s));

        if (array_filter(self::NOT_NEWS, fn ($s) => $host === $s || str_ends_with($host, '.' . $s))) {
            return null;
        }
        $desc = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $it->description), ENT_QUOTES | ENT_HTML5)));
        // A social post often has only its page's name for a title: its words are the story.
        if ($social && $desc !== '' && (mb_strlen($title) < 40 || ! str_contains($title, ' '))) {
            $title = Str::limit($desc, 160);
        }
        if ($social && mb_strlen($title) < 25) {
            return null;
        }
        $image = null;
        $media = $it->children('http://search.yahoo.com/mrss/');
        if (isset($media->content)) {
            $image = (string) ($media->content->attributes()->url ?? '');
        }
        if (! $image && isset($it->enclosure) && str_starts_with((string) $it->enclosure->attributes()->type, 'image/')) {
            $image = (string) $it->enclosure->attributes()->url;
        }
        if (! $image && preg_match('/<img[^>]+src="([^"]+)"/i', (string) $it->description, $m)) {
            $image = html_entity_decode($m[1]);
        }
        // Pictures from social sites expire within days: never kept.
        if ($social || ! $image || ! str_starts_with($image, 'https://') || preg_match('#fbcdn\.net|cdninstagram|tiktokcdn#i', $image)) {
            $image = null;
        }
        $date = null;
        try {
            $date = Carbon::parse((string) $it->pubDate);
        } catch (\Throwable $e) {
        }

        return [
            'feedId' => $feedId,
            'linkHash' => sha1(self::canonical($link)),
            'title' => Str::limit($title, 390, ''),
            'link' => Str::limit($link, 990, ''),
            'source' => self::publisher($host, $link),
            'imageUrl' => $image ? Str::limit($image, 1490, '') : null,
            'summary' => $desc !== '' && $desc !== $title ? Str::limit($desc, 900, '') : null,
            'isSocial' => $social,
            'publishedAt' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /** A link without its tracking bits, so one story is one story. */
    private static function canonical(string $link): string
    {
        $p = parse_url($link);
        $q = [];
        parse_str($p['query'] ?? '', $q);
        $q = array_filter($q, fn ($k) => ! preg_match('/^(utm_|fbclid|gclid|mc_|ref$|s$|mibextid)/i', (string) $k), ARRAY_FILTER_USE_KEY);
        ksort($q);

        return strtolower(preg_replace('/^www\./', '', (string) ($p['host'] ?? ''))) . rtrim((string) ($p['path'] ?? ''), '/') . ($q ? '?' . http_build_query($q) : '');
    }

    private static function publisher(string $host, string $link): string
    {
        foreach (self::PUBLISHERS as $domain => $name) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return $name;
            }
        }
        if (str_contains($host, 'facebook.com')) {
            $first = strtolower((string) explode('/', trim((string) parse_url($link, PHP_URL_PATH), '/'))[0]);

            return isset(self::PAGES[$first]) ? self::PAGES[$first] . ' on Facebook' : 'Facebook';
        }
        $base = preg_replace('/\.(com|net|org|gov|edu)?\.?(ph)?$/', '', $host);

        return Str::title(str_replace(['-', '.'], ' ', (string) $base)) ?: $host;
    }

    // ------------------------------------------------------------------
    // Which stories
    // ------------------------------------------------------------------

    /** Stories no roundup has featured yet, newest first. */
    public function candidates(): Collection
    {
        $since = now()->subDays(self::WINDOW_DAYS);
        $lastTo = DB::table('as_news_runs')->where('status', 'created')->max('rangeTo');
        if ($lastTo && Carbon::parse($lastTo)->subDay()->gt($since)) {
            $since = Carbon::parse($lastTo)->subDay();
        }

        return DB::table('as_news_items')->whereNull('featuredPageId')
            ->where('publishedAt', '>=', $since)->where('publishedAt', '<=', now()->addHours(12))
            ->orderByDesc('publishedAt')->limit(30)->get()
            // The same story told by two feeds under two links: the first one stays.
            ->unique(fn ($r) => Str::slug(Str::limit($r->title, 60, '')))->values();
    }

    // ------------------------------------------------------------------
    // The writing
    // ------------------------------------------------------------------

    /**
     * @return array{ok: bool, page?: array, used?: list<int>, range?: array, error?: string, skip?: bool, result: array}
     */
    public function write(Collection $items): array
    {
        $settings = AiSetting::current()->forField('PH');
        $byId = $items->keyBy('id');
        $from = Carbon::parse($items->min('publishedAt'))->timezone('Asia/Manila');
        $to = Carbon::parse($items->max('publishedAt'))->timezone('Asia/Manila');
        $range = self::range($from, $to);
        $keywords = SeoKeywords::candidates($items->pluck('title')->implode(' ') . ' agriculture farm news philippines palay rice prices', 18)
            ->map(fn ($k) => $k->keyword . ' (' . number_format((int) $k->volume) . ' searches)')->all();
        $usedFocus = AsSitePage::query()->where('section', 'blog')->whereNotNull('focusKeyword')->pluck('focusKeyword')->map(fn ($f) => mb_strtolower($f))->unique()->values()->all();

        $prompt = $this->prompt($items, $range, $keywords, $usedFocus);
        $parse = fn (string $t) => $this->parse($t);
        $result = $this->ai->askForJson($settings, $prompt, 9000, $parse);
        if (! is_array($result['data'] ?? null)) {
            return ['ok' => false, 'error' => $result['error'] ?? 'The roundup came back unreadable.', 'result' => $result];
        }
        $data = $result['data'];
        $built = $this->build($data, $byId);
        if (count($built['used']) < self::MIN_STORIES) {
            return ['ok' => false, 'skip' => true, 'error' => 'Anee found fewer than ' . self::MIN_STORIES . ' stories worth a farmer\'s time.', 'result' => $result];
        }

        // The house rules, checked; one round back to fix what is missed.
        $problems = PageWriter::audit($built['page']);
        if ($problems) {
            $fix = $this->ai->askForJson($settings, $this->repair($data, $problems), 9000, $parse);
            foreach (['tokensIn', 'tokensOut'] as $k) {
                $result[$k] = (int) ($result[$k] ?? 0) + (int) ($fix[$k] ?? 0);
            }
            if (is_array($fix['data'] ?? null)) {
                $again = $this->build($fix['data'], $byId);
                if (count($again['used']) >= self::MIN_STORIES && count(PageWriter::audit($again['page'])) < count($problems)) {
                    $built = $again;
                }
            }
        }
        $used = $built['used'];
        $span = collect($used)->map(fn ($id) => Carbon::parse($byId[$id]->publishedAt));

        return ['ok' => true, 'page' => $built['page'], 'used' => $used, 'range' => [$span->min(), $span->max()], 'result' => $result];
    }

    /** "September 28 to October 7, 2026", "October 1 to 7, 2026". */
    public static function range(Carbon $from, Carbon $to): string
    {
        if ($from->isSameDay($to)) {
            return $to->format('F j, Y');
        }
        if ($from->isSameMonth($to)) {
            return $from->format('F j') . ' to ' . $to->format('j, Y');
        }

        return $from->format('F j') . ' to ' . $to->format('F j, Y');
    }

    private function prompt(Collection $items, string $range, array $keywords, array $usedFocus): string
    {
        $list = $items->map(fn ($r) => json_encode(array_filter([
            'id' => $r->id,
            'date' => Carbon::parse($r->publishedAt)->timezone('Asia/Manila')->format('M j'),
            'source' => $r->source,
            'title' => $r->title,
            'summary' => $r->summary ? Str::limit($r->summary, 400) : null,
            'social' => $r->isSocial ? 'a social media video post' : null,
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->implode("\n");

        return "You are writing anee.io's \"Latest in Agriculture\" farm news roundup for farmers in the Philippines, covering {$range}.\n"
            . "Below are the news stories our feeds collected (one JSON object per line). You only have each story's title, source, date and a short description: use nothing else as fact.\n\n"
            . $list . "\n\n"
            . "--- What to do ---\n"
            . "1. Pick the " . self::MIN_STORIES . ' to ' . self::MAX_STORIES . " stories that matter most to a Filipino farmer: rice and palay, corn, vegetables, prices of farm goods, fertilizer and fuel costs, weather and typhoons, government programs and aid, trade and imports, research a farmer can use. Leave out stories with nothing for a farmer, and when two stories report the same news, keep one.\n"
            . "2. Group them by theme, 2 to 5 themes, never one story per theme unless it stands alone. Each theme gets an H2 heading in Title Case that names the topic exactly (for example \"Palay Prices and Rice Supply\", \"Fuel and Farm Costs\", \"Weather and Typhoon Watch\", \"Help From the Government\", \"Exports and Markets\"). Order the themes by how much they matter to a farmer this week.\n"
            . "3. For every story: a headline in your own words (at most 12 words, Title Case), a summary of the core facts in your own words in 1 or 2 sentences (never copy the source's sentences), and the \"so what\": 2 or 3 sentences on why this news matters to a Filipino farmer and what they can do about it, in plain field terms (palay, cavans, hectares, inputs, the planting window, the harvest). The so what is our own commentary: it is what makes this page worth reading. Never add a number, name or claim that is not in the story given.\n"
            . "4. The title: lead with the most important story in specific words, then the rest (for example \"Palay Stocks Fall Before Harvest, Plus Fuel Prices and Typhoon Watch\"). Never a generic \"news roundup\" title. It must contain the focus keyphrase.\n"
            . "5. The excerpt: the first paragraph, 2 or 3 sentences, naming the date range ({$range}) and the top story, with the focus keyphrase.\n"
            . "6. The intro: one short paragraph on what this week means for farm work overall.\n"
            . "7. actions: 3 to 5 short, concrete things a farmer can do this week because of this news.\n"
            . "8. faq: 3 to 5 questions farmers would ask about this week's news, with direct answers drawn from the stories.\n"
            . "9. links: 3 to 5 of our own related pages from the list below that a reader of these stories would want next.\n"
            . "\n--- Focus keyphrase ---\nChoose one focus keyphrase a farmer would search for about the top story or this week's farm news, 2 to 5 words, preferably a keyword from the list below. Never one of these, already used: " . ($usedFocus ? implode(', ', array_slice($usedFocus, 0, 60)) : 'none') . ".\n"
            . PageWriter::voiceRules('en')
            . PageWriter::seoRules('', '900 to 1500', $keywords)
            . "\n--- Links ---\nInternal links go only in the links list (url from these pages):\n" . PageWriter::urlMap() . "\n"
            . PageWriter::honestRules()
            . "The cta: a title and a text of one sentence then two or three short benefit lines, each on its own line, on how anee.io helps a farmer act on news like this (the season calendar, Anee the smart farm technician, the weather per lot).\n"
            . "\n--- Answer ---\nReturn ONLY this JSON object, no fences, no commentary. Use the story ids exactly as given:\n"
            . '{"title": "...", "slug": "words-of-the-keyphrase", "metaTitle": "...", "metaDescription": "...", "focusKeyword": "...", "mainKeywords": ["1 or 2"], "keywordsUsed": ["..."], '
            . '"excerpt": "...", "intro": "...", "themes": [{"heading": "...", "stories": [{"id": 0, "headline": "...", "summary": "...", "soWhat": "..."}]}], '
            . '"actionsHeading": "an H2 for the actions", "actions": ["..."], "faqHeading": "an H2 for the questions", "faq": [{"q": "...", "a": "..."}], '
            . '"links": [{"label": "...", "url": "/path"}], "cta": {"title": "...", "text": "..."}}';
    }

    private function repair(array $data, array $problems): string
    {
        return "You wrote this farm news roundup for anee.io (JSON below). It breaks these rules:\n- " . implode("\n- ", $problems) . "\n\n"
            . "Fix every one. Keep the stories, their ids, the facts and the order. Keep the house rules: plain words, no dashes, no semicolons, keywords in natural word order. "
            . "The theme headings and actionsHeading and faqHeading are the page's H2 headings: put the focus keyphrase in one of them.\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n\nReturn ONLY the corrected JSON object in the same format, no fences, no commentary.";
    }

    private function parse(string $text): ?array
    {
        $t = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start === false || $end === false) {
            return null;
        }
        $d = json_decode(substr($t, $start, $end - $start + 1), true);

        return is_array($d) && ! empty($d['title']) && is_array($d['themes'] ?? null) && $d['themes'] ? $d : null;
    }

    /**
     * The page, built from the model's words and the feed's facts: every
     * story's link, source and picture come from the story itself.
     *
     * @return array{page: array, used: list<int>}
     */
    private function build(array $d, Collection $byId): array
    {
        $c = fn ($s) => ArticleStyle::clean((string) $s);
        $known = PageWriter::knownPaths();
        $blocks = [];
        $used = [];
        $hero = null;
        $sources = [];
        $pictures = 0;

        foreach ((array) $d['themes'] as $theme) {
            $stories = array_values(array_filter((array) ($theme['stories'] ?? []), fn ($s) => is_array($s) && isset($byId[(int) ($s['id'] ?? 0)])
                && ! in_array((int) $s['id'], $used, true)));
            if (! $stories || trim((string) ($theme['heading'] ?? '')) === '') {
                continue;
            }
            $blocks[] = ['type' => 'heading', 'level' => 2, 'text' => $c($theme['heading'])];
            foreach ($stories as $s) {
                if (count($used) >= self::MAX_STORIES) {
                    break;
                }
                $item = $byId[(int) $s['id']];
                $used[] = (int) $item->id;
                $when = Carbon::parse($item->publishedAt)->timezone('Asia/Manila')->format('F j');
                $blocks[] = ['type' => 'heading', 'level' => 3, 'text' => $c($s['headline'] ?? $item->title)];
                $picture = $pictures < 7 ? $this->picture($item->imageUrl) : null;
                if ($picture && $hero === null) {
                    // The top story's picture leads the page; it is not shown twice.
                    $hero = ['src' => $picture, 'alt' => $c($s['headline'] ?? $item->title) . ', a photo from ' . $item->source,
                        'credit' => '© ' . $item->source . ', from the [original report](' . $item->link . ')'];
                } elseif ($picture) {
                    $pictures++;
                    $blocks[] = ['type' => 'image', 'style' => 'news', 'src' => $picture, 'alt' => $c($s['headline'] ?? $item->title),
                        'caption' => 'Photo © ' . $item->source . '. [See the original report](' . $item->link . ')'];
                }
                $blocks[] = ['type' => 'text', 'text' => $c($s['summary'] ?? '')];
                if (trim((string) ($s['soWhat'] ?? '')) !== '') {
                    $blocks[] = ['type' => 'callout', 'tone' => 'tip', 'title' => 'What it means for your farm', 'text' => $c($s['soWhat'])];
                }
                $blocks[] = ['type' => 'text', 'text' => '**' . $item->source . ', ' . $when . '.** [Read the full report at ' . $item->source . '](' . $item->link . ')'];
                $sources[] = ['label' => $item->source . ': ' . $c($item->title), 'url' => $item->link];
            }
        }

        // What to do, the call, the questions, the next pages, the sources.
        $actions = array_values(array_filter(array_map($c, (array) ($d['actions'] ?? []))));
        if ($actions) {
            $blocks[] = ['type' => 'heading', 'level' => 2, 'text' => $c($d['actionsHeading'] ?? 'What to Do on Your Farm This Week')];
            $blocks[] = ['type' => 'list', 'ordered' => false, 'items' => $actions];
        }
        $cta = (array) ($d['cta'] ?? []);
        $blocks[] = ['type' => 'cta', 'title' => $c($cta['title'] ?? 'Act on the news before it reaches your field'),
            'text' => $c($cta['text'] ?? "anee.io keeps your season, your weather and Anee in one place.\nEvery task dated from each lot's own day zero\nThe weather forecast for every lot\nAnee, the smart farm technician, in Tagalog or English"),
            'label' => 'Try it for free', 'url' => '/signup'];
        $faq = array_values(array_filter((array) ($d['faq'] ?? []), fn ($f) => is_array($f) && trim((string) ($f['q'] ?? '')) !== '' && trim((string) ($f['a'] ?? '')) !== ''));
        if ($faq) {
            $blocks[] = ['type' => 'heading', 'level' => 2, 'text' => $c($d['faqHeading'] ?? 'Questions About This Week\'s Farm News')];
            $blocks[] = ['type' => 'faq', 'items' => array_map(fn ($f) => ['q' => $c($f['q']), 'a' => $c($f['a'])], $faq)];
        }
        $links = array_values(array_filter((array) ($d['links'] ?? []), fn ($l) => is_array($l)
            && in_array(rtrim((string) strtok((string) ($l['url'] ?? ''), '#?'), '/') ?: '/', $known, true)));
        if ($links) {
            $blocks[] = ['type' => 'links', 'title' => 'Read next', 'items' => array_map(fn ($l) => ['label' => $c($l['label'] ?? $l['url']), 'url' => (string) $l['url']], array_slice($links, 0, 6))];
        }
        if ($sources) {
            $blocks[] = ['type' => 'sources', 'items' => $sources];
        }

        // Up top: what this is, before the first story.
        $dates = collect($used)->map(fn ($id) => Carbon::parse($byId[$id]->publishedAt)->timezone('Asia/Manila'));
        $range = $used ? self::range($dates->min(), $dates->max()) : '';
        $names = collect($used)->map(fn ($id) => $byId[$id]->source)->unique()->values();
        $from = $names->count() > 1 ? $names->slice(0, -1)->implode(', ') . ' and ' . $names->last() : $names->implode('');
        $lead = [['type' => 'callout', 'tone' => 'info', 'title' => 'This roundup covers ' . $range,
            'text' => count($used) . ' stories from ' . $from . '. Each one comes with what it means for your farm and a link to the full report.']];
        if (trim((string) ($d['intro'] ?? '')) !== '') {
            $lead[] = ['type' => 'text', 'text' => $c($d['intro'])];
        }
        $blocks = array_merge($lead, $blocks);

        $focus = $c($d['focusKeyword'] ?? '');
        $title = $c($d['title']);
        $slug = Str::slug((string) ($d['slug'] ?? '') ?: ($focus ?: $title));
        $slug = Str::limit(trim($slug, '-'), 70, '') . '-' . now('Asia/Manila')->format('Y-m-d');

        return ['used' => $used, 'page' => [
            'title' => Str::limit($title, 180, ''),
            'slug' => $slug,
            'metaTitle' => PageWriter::cut($c($d['metaTitle'] ?? $title), 60),
            'metaDescription' => PageWriter::cut($c($d['metaDescription'] ?? ''), 156),
            'focusKeyword' => Str::limit($focus, 120, ''),
            'mainKeywords' => array_values(array_slice(array_filter(array_map($c, (array) ($d['mainKeywords'] ?? []))), 0, 2)),
            'keywords' => array_values(array_slice(array_unique(array_filter(array_map($c, array_merge((array) ($d['mainKeywords'] ?? []), (array) ($d['keywordsUsed'] ?? []))))), 0, 12)),
            'excerpt' => $c($d['excerpt'] ?? ''),
            'category' => 'Agriculture News',
            'heroImage' => $hero,
            'blocks' => ArticleStyle::cleanBlocks($blocks),
        ]];
    }

    /** A picture we can show: it answers, and it is an image. */
    private function picture(?string $url): ?string
    {
        // A newsroom's stand in (its logo, a filler) is not a picture of the story.
        if (! $url || preg_match('#(logo|filler|placeholder|default[-_]?image|no[-_]?image|blank\.)#i', (string) parse_url($url, PHP_URL_PATH))) {
            return null;
        }
        try {
            $r = Http::timeout(8)->withHeaders(['User-Agent' => 'Mozilla/5.0 (anee.io news roundup)', 'Range' => 'bytes=0-2047'])->get($url);

            return ($r->successful() || $r->status() === 206) && str_starts_with(strtolower((string) $r->header('Content-Type')), 'image/') ? $url : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The roundup, live under /blog, newest first in its section. */
    private function publish(array $p): AsSitePage
    {
        $slug = $p['slug'];
        $n = 2;
        while (AsSitePage::where('section', 'blog')->where('slug', $slug)->exists()) {
            $slug = $p['slug'] . '-' . $n++;
        }

        $page = AsSitePage::create([
            'section' => 'blog',
            'slug' => $slug,
            'lang' => 'en',
            'category' => $p['category'],
            'title' => $p['title'],
            'metaTitle' => $p['metaTitle'],
            'metaDescription' => $p['metaDescription'],
            'focusKeyword' => $p['focusKeyword'],
            'keywords' => $p['keywords'],
            'excerpt' => $p['excerpt'],
            'heroImage' => $p['heroImage'],
            'blocks' => $p['blocks'],
            'status' => 'published',
            // Newer roundups sort first: the hour since 2026 counted down.
            'sortOrder' => -1 * (int) floor((now()->timestamp - 1767225600) / 3600),
            'publishedAt' => now(),
            'deleteStatus' => 1,
            'showIn' => 'both',
        ]);
        DB::table('as_site_pages')->where('id', $page->id)->update(['kind' => 'roundup']);
        $page->kind = 'roundup';
        try {
            SeoKeywords::markUsed(SitePages::plain(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE)), $p['keywords']);
        } catch (\Throwable $e) {
        }

        return $page;
    }
}
