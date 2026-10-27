<?php

namespace App\Support;

use App\Models\AiSetting;
use App\Services\AiClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The daily farm news, read for the market (2026-10-27, the owner: "since we
 * are now getting agri news daily for the newsletter from the rss, if there
 * are updates about the import, better if to save it as well and include it
 * in the analysis").
 *
 * Every story the news feeds bring (as_news_items, App\Services\NewsRoundup)
 * is read once. A story that names a crop and talks of imports, tariffs,
 * bans, smuggling, a price cap or a support price, a glut or exports is
 * handed, a dozen at a time, to the cheapest model, which says whether it
 * really is news that moves a Philippine crop's farmgate price and, if so,
 * writes the event in our own words (checked against the story with
 * CopyCheck). The event joins the researched ones in as_market_events
 * (key "n" + the story's id, the newsroom as its source) unless it tells an
 * event already kept, which then only gains the newsroom as a source. From
 * there it reaches the Market Price Forecast, When to Plant and What to
 * Plant like every other event (App\Support\MarketBook). Run by
 * `php artisan market:news` each morning after the news is fetched.
 */
final class MarketNews
{
    /** A crop named in the story: the words, and our crop keys. */
    private const CROPS = [
        '/\b(rice|palay|bigas|paddy)\b/i' => 'rice', '/\b(corn|mais|maize)\b/i' => 'corn_yellow', '/\b(onions?|sibuyas)\b/i' => 'onion', '/\bshallots?\b/i' => 'shallot',
        '/\b(garlic|bawang)\b/i' => 'garlic', '/\b(sugar|sugarcane|tubo)\b/i' => 'sugarcane', '/\bcarrots?\b/i' => 'carrot', '/\bcabbages?\b/i' => 'cabbage',
        '/\bpotato(es)?\b/i' => 'potato', '/\bbroccoli\b/i' => 'broccoli', '/\btomato(es)?|kamatis\b/i' => 'tomato', '/\b(chili|chilli|sili|labuyo)\b/i' => 'chili',
        '/\bginger|luya\b/i' => 'ginger', '/\b(mung ?beans?|monggo|mongo)\b/i' => 'mungbean', '/\bbananas?|saging\b/i' => 'banana', '/\bmango(es)?|mangga\b/i' => 'mango',
        '/\bpineapples?\b/i' => 'pineapple', '/\b(coconut|copra|niyog)\b/i' => 'coconut', '/\bcoffee\b/i' => 'coffee', '/\b(cacao|cocoa)\b/i' => 'cacao',
        '/\bcalamansi\b/i' => 'calamansi', '/\bcassava\b/i' => 'cassava', '/\beggplants?|talong\b/i' => 'eggplant', '/\b(vegetables?|gulay|highland veg)/i' => 'vegetables',
    ];

    /** Trade and price news. */
    private const TRADE = '/\bimport|tariff|smuggl|\bbans?\b|banned|suspen|minimum access|\bMAV\b|price (?:cap|ceiling)|\bSRP\b|floor price|buying price|support price|farm ?gate|oversupply|glut|dumping|\bexport|safeguard|\bsanitary and phytosanitary|\bSPS\b|nagbaba|bagsak.presyo/i';

    public const KINDS = ['import_surge', 'import_ban', 'import_suspension', 'import_order', 'tariff', 'price_cap', 'support_price', 'smuggling', 'export', 'policy', 'scheduled', 'other'];

    /** Kinds that tell the same sort of news: a decision on imports, what came in or went out, a price set from above. */
    private const GROUPS = ['import_ban' => 'decide', 'import_suspension' => 'decide', 'import_order' => 'decide', 'tariff' => 'decide', 'policy' => 'decide', 'scheduled' => 'decide',
        'import_surge' => 'flow', 'smuggling' => 'flow', 'export' => 'flow', 'price_cap' => 'price', 'support_price' => 'price', 'other' => 'other'];

    /**
     * Whether two events are one: the same sort of news on a crop they share, within two weeks.
     * $e = an as_market_events row; the other is given by its kind, day and crops.
     */
    public static function sameEvent(object $e, string $kind, string $day, array $crops): bool
    {
        return (self::GROUPS[$e->kind] ?? $e->kind) === (self::GROUPS[$kind] ?? $kind)
            && abs(Carbon::parse($e->day)->diffInDays(Carbon::parse($day), false)) <= 14
            && (bool) array_intersect($crops, (array) json_decode((string) $e->crops, true));
    }

    public static function ready(): bool
    {
        return Schema::hasTable('as_market_news_seen') && Schema::hasTable('as_news_items') && Schema::hasTable('as_market_events');
    }

    /** The stories not yet read that name a crop and talk trade, oldest first. */
    public static function candidates(int $limit, int $days = 400): Collection
    {
        $seen = DB::table('as_market_news_seen')->pluck('newsItemId')->flip();
        $out = collect();
        DB::table('as_news_items')->where('isSocial', 0)->where(fn ($q) => $q->whereNull('publishedAt')->orWhere('publishedAt', '>=', now()->subDays($days)))
            ->orderBy('publishedAt')->orderBy('id')->select(['id', 'title', 'link', 'source', 'summary', 'publishedAt'])
            ->chunk(500, function ($rows) use ($seen, &$out, $limit) {
                foreach ($rows as $r) {
                    if (isset($seen[$r->id])) {
                        continue;
                    }
                    $text = $r->title . ' ' . strip_tags((string) $r->summary);
                    if (preg_match(self::TRADE, $text) && self::crops($text)) {
                        $out->push($r);
                    }
                    if ($out->count() >= $limit) {
                        return false;
                    }
                }
            });

        return $out;
    }

    /** The crop keys a text names. */
    public static function crops(string $text): array
    {
        $out = [];
        foreach (self::CROPS as $re => $k) {
            if (preg_match($re, $text)) {
                $out[] = $k;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Read the next stories. Returns [read, events made, sources added, failed asks]. $dry: ask but write
     * nothing. The stories that matched no keywords are marked read without an ask.
     */
    public static function scan(int $limit = 60, bool $dry = false): array
    {
        $stat = ['read' => 0, 'events' => 0, 'joined' => 0, 'failed' => 0, 'made' => [], 'via' => null];
        if (! self::ready()) {
            return $stat;
        }
        $ai = app(AiClient::class);
        // The owner's own Claude when its key is kept (2026-10-27); else Anee's cheapest model, as before.
        $settings = AiSetting::newsReader() ?? AiSetting::current()->cheap('low');
        $stat['via'] = ($c = $settings->onClaude()) ? 'claude ' . $c->effectiveModel() . ' (else ' . $settings->effectiveModel() . ')' : $settings->provider . ' ' . $settings->effectiveModel();
        foreach (self::candidates($limit)->chunk(12) as $batch) {
            $known = DB::table('as_market_events')->where('day', '>=', now()->subMonths(18)->toDateString())->orderByDesc('day')->limit(60)->get(['eventKey', 'day', 'title', 'crops']);
            $prompt = self::prompt($batch, $known);
            $result = $ai->askForJson($settings, $prompt, 6000, fn (string $t) => self::parse($t),
                ['system' => 'You read Philippine farm news for a market price desk. You answer only with JSON.', 'timeout' => 90, 'retryAfterTimeout' => false]);
            if ((int) ($result['tokensIn'] ?? 0) > 0) {
                rescue(fn () => AiUsage::record('market-news', 0, 0, null, $settings, $result, 0), null, false);
            }
            if (! is_array($result['data'] ?? null)) {
                $stat['failed']++;

                continue;
            }
            $byId = $batch->keyBy('id');
            $said = [];
            foreach ($result['data'] as $x) {
                $id = (int) ($x['id'] ?? 0);
                if (! isset($byId[$id])) {
                    continue;
                }
                $said[$id] = true;
                $r = $byId[$id];
                $key = null;
                if (! empty($x['relevant']) && ! $dry) {
                    $key = self::keep($r, $x, $known, $stat);
                }
                if (! $dry) {
                    DB::table('as_market_news_seen')->insertOrIgnore(['newsItemId' => $id, 'hit' => (bool) ($x['relevant'] ?? false), 'eventKey' => $key, 'created_at' => now(), 'updated_at' => now()]);
                }
                $stat['read']++;
            }
            // A story the answer skipped is read again next time.
        }
        if (! $dry && ($stat['events'] || $stat['joined'])) {
            MarketBook::forget();
        }

        return $stat;
    }

    /** The question for one batch. */
    private static function prompt(Collection $batch, Collection $known): string
    {
        $lines = $batch->map(fn ($r) => json_encode(array_filter([
            'id' => (int) $r->id, 'date' => $r->publishedAt ? Carbon::parse($r->publishedAt)->timezone('Asia/Manila')->toDateString() : null, 'newsroom' => $r->source,
            'title' => Str::limit((string) $r->title, 220, ''), 'text' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $r->summary))), 700, ''),
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->implode("\n");
        $kept = $known->map(fn ($e) => $e->eventKey . ' | ' . $e->day . ' | ' . implode(',', (array) json_decode((string) $e->crops, true)) . ' | ' . Str::limit((string) $e->title, 110, ''))->implode("\n");
        $kinds = implode(', ', self::KINDS);

        return "Below are farm news stories from the Philippines (one JSON object per line). For each, decide whether it reports something that moves, or is meant to move, the FARMGATE price of a Philippine crop through trade or price policy: imports arriving or being allowed, an import ban, suspension or quota, a tariff change, smuggling seized or exposed, a price cap or suggested retail price, a support or buying price for palay or corn, a glut blamed on imports, or exports. Weather damage, aid handouts, fertilizer prices, programs and speeches without a trade or price decision are NOT relevant.\n\n"
            . "STORIES:\n{$lines}\n\n"
            . "EVENTS ALREADY KEPT (key | date | crops | title):\n" . ($kept !== '' ? $kept : '(none)') . "\n\n"
            . "For every story return one object. When relevant is false, give only id and relevant. When true:\n"
            . "- crop_keys from: rice, corn_yellow, onion, shallot, garlic, sugarcane, carrot, cabbage, potato, broccoli, tomato, chili, ginger, mungbean, banana, mango, pineapple, coconut, coffee, cacao, calamansi, cassava, eggplant (\"vegetables\" only when no single crop is named);\n"
            . "- kind, one of: {$kinds} (\"scheduled\" for a decision that takes effect later);\n"
            . "- direction: the effect on FARMGATE prices the story reports or clearly implies: down, up or mixed;\n"
            . "- date: the day the event happens or takes effect (YYYY-MM-DD), else the story's date; end_date for a ban or window with an end, else null;\n"
            . "- title: at most 90 characters, and summary: at most 300 characters, both in YOUR OWN plain words for a farmer: never copy a phrase of eight words or more from the story; keep names, numbers and dates exact; no dashes as punctuation (write \"to\" for ranges); say what happened, how much, and what it does to the price farmers get;\n"
            . "- import_tons, tariff_from, tariff_to: numbers only when the story states them, else null; farmgate_note: the farmgate prices the story gives, else null;\n"
            . "- same_as: the key of an EVENT ALREADY KEPT that this story tells again (the same decision or arrival, not just the same topic), else null.\n"
            . 'Return ONLY this JSON, no fences: {"items":[{"id":1,"relevant":true,"crop_keys":["rice"],"kind":"import_suspension","direction":"up","date":"2026-09-01","end_date":null,"title":"","summary":"","import_tons":null,"tariff_from":null,"tariff_to":null,"farmgate_note":null,"same_as":null},{"id":2,"relevant":false}]}';
    }

    /** The answer's items, or null. */
    public static function parse(string $text): ?array
    {
        $t = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
        $j = json_decode($t, true);
        if (! is_array($j) && preg_match('/\{.*\}/s', $t, $m)) {
            $j = json_decode($m[0], true);
        }

        return is_array($j['items'] ?? null) ? array_values(array_filter($j['items'], 'is_array')) : null;
    }

    /** Keep one relevant story: a new event, or one more newsroom on an event already kept. Returns the event's key. */
    private static function keep(object $r, array $x, Collection $known, array &$stat): ?string
    {
        $crops = array_values(array_intersect((array) ($x['crop_keys'] ?? []), array_merge(array_values(self::CROPS), ['corn_white'])));
        if (! $crops) {
            return null;
        }
        $source = ['url' => (string) $r->link, 'publisher' => (string) ($r->source ?: parse_url((string) $r->link, PHP_URL_HOST)), 'type' => GovSource::isGovUrl((string) $r->link) ? 'government' : 'news'];
        $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($x['date'] ?? '')) ? $x['date'] : ($r->publishedAt ? Carbon::parse($r->publishedAt)->timezone('Asia/Manila')->toDateString() : now()->toDateString());
        $kind = in_array($x['kind'] ?? '', self::KINDS, true) ? $x['kind'] : 'other';
        // The same event already kept: by the answer's word, or the same crop and kind within two weeks.
        $same = null;
        if (! empty($x['same_as'])) {
            $same = DB::table('as_market_events')->where('eventKey', (string) $x['same_as'])->first();
        }
        if (! $same) {
            // A researched event first, then one the news brought.
            foreach (DB::table('as_market_events')->whereBetween('day', [Carbon::parse($day)->subDays(14)->toDateString(), Carbon::parse($day)->addDays(14)->toDateString()])
                ->orderByRaw("eventKey LIKE 'n%'")->get() as $e) {
                if (self::sameEvent($e, $kind, $day, $crops)) {
                    $same = $e;
                    break;
                }
            }
        }
        if ($same) {
            $src = (array) json_decode((string) $same->sources, true);
            if (! in_array($source['url'], array_column($src, 'url'), true)) {
                $src[] = $source;
                DB::table('as_market_events')->where('id', $same->id)->update(['sources' => json_encode(array_values($src)), 'updated_at' => now()]);
                $stat['joined']++;
            }

            return $same->eventKey;
        }
        $title = trim((string) ($x['title'] ?? ''));
        $summary = trim((string) ($x['summary'] ?? ''));
        if ($title === '' || $summary === '') {
            return null;
        }
        // Our own words: a summary that copies the story is not kept; a title that copies the headline gives way
        // to the summary's opening words (headlines are short, so a faithful title can repeat one).
        $story = $r->title . ' ' . strip_tags((string) $r->summary);
        if (CopyCheck::copies($summary, $story)) {
            return null;
        }
        if (CopyCheck::copies($title, $story)) {
            $first = trim(preg_split('/(?<=[.;,])\s/', $summary)[0], ' .,;');
            $title = mb_strlen($first) <= 90 ? $first : rtrim(mb_substr($first, 0, mb_strrpos(mb_substr($first, 0, 90), ' ') ?: 90));
            if ($title === '' || CopyCheck::copies($title, $story)) {
                return null;
            }
        }
        $key = 'n' . $r->id;
        $num = fn ($v) => is_numeric($v) ? $v : null;
        DB::table('as_market_events')->insertOrIgnore([
            'eventKey' => $key, 'crops' => json_encode($crops), 'day' => $day, 'precision' => 'day',
            'endDay' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($x['end_date'] ?? '')) ? $x['end_date'] : null,
            'kind' => $kind, 'direction' => in_array($x['direction'] ?? '', ['up', 'down', 'mixed'], true) ? $x['direction'] : 'mixed',
            'title' => mb_substr($title, 0, 160), 'summary' => mb_substr($summary, 0, 600), 'importTons' => $num($x['import_tons'] ?? null),
            'tariffFrom' => $num($x['tariff_from'] ?? null), 'tariffTo' => $num($x['tariff_to'] ?? null), 'farmgateNote' => $x['farmgate_note'] ?? null,
            'scope' => 'national', 'sources' => json_encode([$source]), 'quote' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $stat['events']++;
        $stat['made'][] = $day . ' ' . implode(',', $crops) . ': ' . $title;

        return $key;
    }
}
