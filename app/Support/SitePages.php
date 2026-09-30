<?php

namespace App\Support;

use App\Models\AsSitePage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The public site's guides, blog and feature pages (2026-10-01).
 *
 * Four sections, each with a hub and its pages:
 *
 *   crops     /crops, /crops/{slug}          crop guides
 *   problems  /problems, /problems/{slug}    pests, diseases, weeds
 *   blog      /blog, /blog/{slug}            fertilizer, pesticides, words, culture
 *   features  /features (the tour), /features/{slug}
 *
 * A page is blocks (see render()), written as JSON files in
 * database/site-pages and edited afterwards in the mother app's block builder
 * (AniSystem > Website pages). The writing rules are in
 * database/site-pages/STYLE.md.
 */
class SitePages
{
    public const SECTIONS = [
        'crops' => [
            'label' => 'Crop Guides',
            'crumb' => 'Crop guides',
            'hubTitle' => 'Crop Guides for Filipino Farmers',
            'metaTitle' => 'Crop Guides for Filipino Farmers: Palay, Mais and Gulay',
            'metaDescription' => 'Practical crop guides for Filipino farmers: palay, mais, vegetables, coconut and banana. How to plant, feed, protect and harvest each one.',
            'intro' => 'How to plant, feed and harvest the crops Filipino farms live on, from palay and mais to vegetables, coconut and banana. Written for the field, in English and Tagalog.',
            'kicker' => 'Crop guides',
        ],
        'problems' => [
            'label' => 'Crop Problems',
            'crumb' => 'Crop problems',
            'hubTitle' => 'Crop Problems: Pests, Diseases and Weeds',
            'metaTitle' => 'Crop Problems in the Philippines: Pests, Diseases, Weeds',
            'metaDescription' => 'Identify and manage the pests, diseases and weeds of Philippine crops: rice bug, black bug, planthopper, armyworm, anthracnose, sheath blight and more.',
            'intro' => 'Know what is eating or sickening your crop before you spend on a chemical. Each guide shows the signs, the timing and the ways to manage it.',
            'kicker' => 'Crop problems',
        ],
        'blog' => [
            'label' => 'Blog',
            'crumb' => 'Blog',
            'hubTitle' => 'The anee.io Farming Blog',
            'metaTitle' => 'Farming Blog: Fertilizer, Pesticides and Rice Prices',
            'metaDescription' => 'Fertilizer grades and how to compute them, pesticides and the FPA, palay prices, farm words in Tagalog and the stories behind our crops.',
            'intro' => 'Fertilizer grades and how to compute them, pesticides and the rules on them, palay prices, farm words in Tagalog, and the stories behind the crops we grow.',
            'kicker' => 'From the blog',
        ],
        'features' => [
            'label' => 'Features',
            'crumb' => 'Features',
            'hubTitle' => 'Features',
            'metaTitle' => 'anee.io Features',
            'metaDescription' => 'Every anee.io feature on its own page.',
            'intro' => '',
            'kicker' => 'Feature',
        ],
    ];

    /** Where a page lives. */
    public static function url(string $section, ?string $slug = null): string
    {
        return url('/' . $section . ($slug ? '/' . $slug : ''));
    }

    public static function pageUrl(AsSitePage $p): string
    {
        return self::url($p->section, $p->slug);
    }

    /** A live page, or null. */
    public static function find(string $section, string $slug): ?AsSitePage
    {
        if (! isset(self::SECTIONS[$section])) {
            return null;
        }
        try {
            return AsSitePage::live()->where('section', $section)->where('slug', $slug)->first();
        } catch (\Throwable $e) {
            return null;   // the table is not there yet
        }
    }

    /** A section's live pages, in order. */
    public static function inSection(string $section): Collection
    {
        try {
            return AsSitePage::live()->where('section', $section)
                ->orderBy('sortOrder')->orderBy('title')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * The home page's guides: the first few live pages of each reading section,
     * in their order, keyed by section. One query.
     */
    public static function homePicks(int $n = 5): array
    {
        try {
            $rows = AsSitePage::live()->whereIn('section', ['crops', 'problems', 'blog'])
                ->orderBy('sortOrder')->orderBy('title')
                ->get(['id', 'section', 'slug', 'lang', 'category', 'title', 'excerpt', 'heroImage']);
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach (['crops', 'problems', 'blog'] as $s) {
            $pages = $rows->where('section', $s)->take($n)->values();
            if ($pages->isNotEmpty()) {
                $out[$s] = $pages;
            }
        }

        return $out;
    }

    /** Pages worth reading next: the same category first, then the section. */
    public static function related(AsSitePage $page, int $n = 4): Collection
    {
        $pool = self::inSection($page->section)->where('id', '!=', $page->id);
        $same = $pool->where('category', $page->category);

        return $same->concat($pool->where('category', '!=', $page->category))->take($n)->values();
    }

    /** A picture's address: a site path, or a full address (a mother upload). */
    public static function img(?string $src): ?string
    {
        $src = trim((string) $src);
        if ($src === '') {
            return null;
        }

        return preg_match('#^https?://#i', $src) ? $src : asset(ltrim($src, '/'));
    }

    /** Minutes to read, at the pace of someone reading carefully. */
    public static function readMinutes(AsSitePage|array $page): int
    {
        return max(1, (int) round(self::wordCount($page) / 200));
    }

    public static function wordCount(AsSitePage|array $page): int
    {
        $p = $page instanceof AsSitePage ? $page->toArray() : $page;
        $text = ($p['excerpt'] ?? '') . ' ';
        foreach ((array) ($p['blocks'] ?? []) as $b) {
            $text .= ' ' . self::plainOf($b);
        }

        return str_word_count(strip_tags($text));
    }

    private static function plainOf(array $b): string
    {
        $out = [];
        array_walk_recursive($b, function ($v, $k) use (&$out) {
            if (is_string($v) && ! in_array($k, ['type', 'url', 'src', 'tone', 'level'], true)) {
                $out[] = $v;
            }
        });

        return implode(' ', $out);
    }

    /** The H2s, for the page's table of contents. */
    public static function toc(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $i => $b) {
            if (($b['type'] ?? '') === 'heading' && (int) ($b['level'] ?? 2) === 2 && trim((string) ($b['text'] ?? '')) !== '') {
                $out[] = ['id' => self::anchor($b['text'], $i), 'text' => (string) $b['text']];
            }
        }

        return $out;
    }

    public static function anchor(string $text, int $i): string
    {
        return (Str::slug($text) ?: 'section') . '-' . $i;
    }

    /** The FAQ items, for FAQPage structured data. */
    public static function faq(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'faq') {
                foreach ((array) ($b['items'] ?? []) as $it) {
                    if (trim((string) ($it['q'] ?? '')) !== '' && trim((string) ($it['a'] ?? '')) !== '') {
                        $out[] = ['q' => (string) $it['q'], 'a' => self::plain((string) $it['a'])];
                    }
                }
            }
        }

        return $out;
    }

    /**
     * A line of text as safe HTML: escaped first, then **bold** and
     * [label](address) links. An address on this site stays in the tab; one
     * elsewhere opens a new one and passes nothing on.
     */
    public static function inline(?string $text): string
    {
        $s = e((string) $text);
        $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);

        return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $url = html_entity_decode($m[2], ENT_QUOTES);
            if (preg_match('#^https?://#i', $url)) {
                $own = str_contains(parse_url($url, PHP_URL_HOST) ?? '', parse_url(url('/'), PHP_URL_HOST) ?? '###');

                return '<a href="' . e($url) . '"' . ($own ? '' : ' target="_blank" rel="noopener"') . '>' . $m[1] . '</a>';
            }
            if (! str_starts_with($url, '/') && ! str_starts_with($url, '#')) {
                return $m[1];   // nothing but our own paths and real web addresses
            }

            return '<a href="' . e(str_starts_with($url, '/') ? url($url) : $url) . '">' . $m[1] . '</a>';
        }, $s);
    }

    /** The same line with the markup taken back out. */
    public static function plain(?string $text): string
    {
        $s = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', (string) $text);

        return str_replace('**', '', $s);
    }

    /** Paragraphs from a text block: a blank line starts the next one. */
    public static function paragraphs(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $text) ?: []), fn ($p) => $p !== ''));
    }

    /** Links in the site footer: a few from each section, cached for an hour. */
    public static function footerLinks(): array
    {
        try {
            return Cache::remember('site-pages:footer', 3600, function () {
                $pick = fn (string $section, int $n) => AsSitePage::live()->where('section', $section)
                    ->orderBy('sortOrder')->orderBy('id')->limit($n)->get(['section', 'slug', 'title', 'metaTitle'])
                    ->map(fn ($p) => ['url' => self::url($p->section, $p->slug), 'label' => self::shortTitle($p)])->all();

                return ['crops' => $pick('crops', 6), 'problems' => $pick('problems', 6), 'blog' => $pick('blog', 6)];
            });
        } catch (\Throwable $e) {
            return ['crops' => [], 'problems' => [], 'blog' => []];
        }
    }

    /** A title short enough for a menu: up to its colon. */
    public static function shortTitle(AsSitePage $p): string
    {
        $t = (string) $p->title;

        return trim(Str::before($t, ':')) ?: $t;
    }

    public static function forgetCaches(): void
    {
        Cache::forget('site-pages:footer');
    }

    // ------------------------------------------------------------------
    // The shipped pages
    // ------------------------------------------------------------------

    /**
     * Load database/site-pages/*\/*.json into the table.
     *
     * A page that is not there yet is created. A page nobody has edited in the
     * mother app (editedAt empty) is brought up to the file's version. A page
     * someone edited keeps their version, and only its seedJson is refreshed,
     * so "put it back" in the mother app restores the newest shipped one.
     *
     * @return array{created: int, updated: int, kept: int}
     */
    public static function sync(?string $dir = null): array
    {
        $dir ??= database_path('site-pages');
        $counts = ['created' => 0, 'updated' => 0, 'kept' => 0];
        $order = [];
        foreach (glob($dir . '/*/*.json') ?: [] as $file) {
            $json = file_get_contents($file);
            $p = json_decode((string) $json, true);
            if (! is_array($p) || empty($p['section']) || empty($p['slug']) || ! isset(self::SECTIONS[$p['section']])) {
                continue;
            }
            $hash = sha1($json);
            $order[$p['section']] = ($order[$p['section']] ?? 0) + 1;
            $fields = self::fieldsFromSeed($p) + ['seedHash' => $hash, 'seedJson' => $json];
            // A file's sortOrder (the pages ranked by the searches they
            // answer) places it in its section; the builder never sets one.
            $place = isset($p['sortOrder']) ? ['sortOrder' => (int) $p['sortOrder']] : [];
            $fields += $place;
            $row = AsSitePage::where('section', $p['section'])->where('slug', $p['slug'])->first();
            if (! $row) {
                AsSitePage::create($fields + [
                    'status' => 'published',
                    'publishedAt' => now(),
                    'sortOrder' => $order[$p['section']] * 10,
                    'deleteStatus' => 1,
                ]);
                $counts['created']++;
            } elseif ($row->editedAt === null) {
                if ($row->seedHash !== $hash) {
                    $row->update($fields);
                    $counts['updated']++;
                }
            } else {
                $row->update(['seedHash' => $hash, 'seedJson' => $json] + $place);
                $counts['kept']++;
            }
        }
        self::forgetCaches();

        return $counts;
    }

    /** The row fields a shipped file sets. */
    public static function fieldsFromSeed(array $p): array
    {
        return [
            'section' => $p['section'],
            'slug' => $p['slug'],
            'lang' => $p['lang'] ?? 'en',
            'category' => $p['category'] ?? null,
            'title' => $p['title'] ?? $p['slug'],
            'metaTitle' => $p['metaTitle'] ?? null,
            'metaDescription' => $p['metaDescription'] ?? null,
            'focusKeyword' => $p['focusKeyword'] ?? null,
            'keywords' => $p['keywords'] ?? [],
            'excerpt' => $p['excerpt'] ?? null,
            'heroImage' => $p['heroImage'] ?? null,
            'blocks' => $p['blocks'] ?? [],
        ];
    }
}
