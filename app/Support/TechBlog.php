<?php

namespace App\Support;

use App\Models\AsCommunityBlogPost;
use App\Models\AsSitePage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The Technician's Blog follows the public site (2026-10-01).
 *
 * Every live crop guide, crop problem and blog page on the public site is an
 * article in the community's Tech Blog too: one as_community_blog_posts row
 * per page, tied by sitePageId, its body drawn from the page's blocks. The
 * page is the source. Change it in the mother's Website pages and the
 * article follows (syncIfStale, on the next visit); take it down and the
 * article goes with it. Links between guides stay inside the app: a link to
 * /crops/rice opens the Rice article here, not the public page.
 *
 * Posting here never rings anyone's bell. The mother's blog editor notifies
 * every member when it publishes; fifty guides arriving at once would have
 * been fifty bells.
 */
class TechBlog
{
    public const SECTIONS = ['crops', 'problems', 'blog'];

    public const AUTHOR = 'anee.io Technicians';

    /** Bump when the drawing below changes, so every article is drawn again. */
    private const VERSION = 2;

    private const STAMP = 'tech-blog:synced';

    private const CHECKED = 'tech-blog:checked';

    /**
     * Bring the articles up to the pages when the pages have changed. Asks
     * at most every five minutes, and only redraws when the pages' count or
     * newest edit moved, so the pages that call it pay one cached lookup.
     */
    public static function syncIfStale(): void
    {
        try {
            if (Cache::get(self::CHECKED)) {
                return;
            }
            Cache::put(self::CHECKED, 1, 300);
            $sig = self::signature();
            if (Cache::get(self::STAMP) === $sig) {
                return;
            }
            self::sync();
            Cache::forever(self::STAMP, $sig);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function signature(): string
    {
        $row = AsSitePage::whereIn('section', self::SECTIONS)
            ->selectRaw("count(*) as n, max(updated_at) as u, sum(deleteStatus = 1 and status = 'published') as live")
            ->first();

        return self::VERSION . '|' . $row->n . '|' . $row->u . '|' . $row->live;
    }

    /** @return array{created: int, updated: int, hidden: int} */
    public static function sync(): array
    {
        $counts = ['created' => 0, 'updated' => 0, 'hidden' => 0];
        if (! Schema::hasColumn('as_community_blog_posts', 'sitePageId')) {
            return $counts;
        }
        $pages = AsSitePage::whereIn('section', self::SECTIONS)
            ->orderBy('sortOrder')->orderBy('id')->get();
        $posts = AsCommunityBlogPost::whereNotNull('sitePageId')->get()->keyBy('sitePageId');
        $live = fn (AsSitePage $p) => (int) $p->deleteStatus === 1 && $p->status === 'published';

        // First every live page gets its row, so the links between pages can
        // point at rows when the bodies are drawn.
        foreach ($pages as $p) {
            $post = $posts[$p->id] ?? null;
            if (! $live($p)) {
                if ($post && (int) $post->deleteStatus === 1) {
                    $post->update(['deleteStatus' => 0]);
                    $counts['hidden']++;
                }

                continue;
            }
            if (! $post) {
                $posts[$p->id] = AsCommunityBlogPost::create([
                    'sitePageId' => $p->id, 'title' => Str::limit((string) $p->title, 188), 'slug' => self::slug($p),
                    'body' => '', 'authorName' => self::AUTHOR, 'isPublished' => true,
                    'publishedAt' => $p->publishedAt ?? now(), 'viewCount' => 0, 'deleteStatus' => 1,
                ]);
                $counts['created']++;
            }
        }
        $map = [];
        foreach ($pages as $p) {
            if ($live($p) && isset($posts[$p->id])) {
                $map['/' . $p->section . '/' . $p->slug] = (int) $posts[$p->id]->id;
            }
        }

        // The list reads newest first. The pages went up together, so each
        // is placed in its day by reading order: a crop guide, a crop
        // problem, a blog post, the next crop guide, and so on.
        $rank = [];
        foreach (self::SECTIONS as $si => $s) {
            foreach ($pages->filter(fn ($p) => $p->section === $s && $live($p))->values() as $i => $p) {
                $rank[$p->id] = $i * count(self::SECTIONS) + $si;
            }
        }

        foreach ($pages as $p) {
            if (! $live($p)) {
                continue;
            }
            $post = $posts[$p->id];
            $hero = is_array($p->heroImage) ? $p->heroImage : [];
            $base = $p->publishedAt ?? $p->created_at ?? now();
            $post->fill([
                'title' => Str::limit((string) $p->title, 188),
                'slug' => self::slug($p),
                // The column holds 500; a page's line under the title can run longer.
                'excerpt' => Str::limit(SitePages::plain((string) $p->excerpt), 490) ?: null,
                'body' => self::body((array) $p->blocks, $map, $hero),
                'coverImagePath' => trim((string) ($hero['src'] ?? '')) ?: self::stockCover($p),
                'coverPaths' => null,
                'authorName' => self::AUTHOR,
                'isPublished' => true,
                // The day the page went up, placed in the day by reading order
                // (the pages' own times are seconds apart in no useful order).
                'publishedAt' => $base->copy()->startOfDay()->addSeconds(10000 - ($rank[$p->id] ?? 0)),
                'deleteStatus' => 1,
            ]);
            if ($post->isDirty()) {
                $post->save();
                $counts['updated']++;
            }
        }
        Cache::forget(self::CHECKED);

        return $counts;
    }

    /** The article's address word: its section and the page's slug. */
    private static function slug(AsSitePage $p): string
    {
        return Str::limit($p->section . '-' . $p->slug, 191, '');
    }

    /** A page with no picture of its own borrows one of the site's farm photos. */
    private static function stockCover(AsSitePage $p): string
    {
        $pool = [
            'crops' => ['/images/site/palay.jpg', '/images/site/corn-rows.jpg', '/images/site/photos/transplant.jpg', '/images/site/fields-aerial.jpg'],
            'problems' => ['/images/site/photos/inspect.jpg', '/images/site/photos/palay-heads.jpg', '/images/site/photos/storm-paddies.jpg'],
            'blog' => ['/images/site/photos/sacks.jpg', '/images/site/harvest-hands.jpg', '/images/site/photos/sacks-shed.jpg', '/images/site/hero-terraces.jpg'],
        ][$p->section] ?? ['/images/site/palay.jpg'];

        return $pool[$p->id % count($pool)];
    }

    /**
     * A page's blocks as article HTML, in the shapes the article page
     * already dresses (CommunityText::articleHtml keeps them). The sign up
     * boxes are left out: everyone reading here already has an account.
     */
    public static function body(array $blocks, array $map, array $hero = []): string
    {
        $in = fn ($t) => self::link(SitePages::inline((string) $t), $map);
        $h = '';
        foreach ($blocks as $b) {
            $type = $b['type'] ?? '';
            if ($type === 'heading' && trim((string) ($b['text'] ?? '')) !== '') {
                $tag = (int) ($b['level'] ?? 2) === 3 ? 'h3' : 'h2';
                $h .= "<$tag>" . e($b['text']) . "</$tag>\n";
            } elseif ($type === 'text') {
                foreach (SitePages::paragraphs($b['text'] ?? '') as $para) {
                    $h .= '<p>' . $in($para) . "</p>\n";
                }
            } elseif ($type === 'list') {
                $items = array_filter((array) ($b['items'] ?? []), fn ($x) => trim((string) $x) !== '');
                if ($items) {
                    $tag = ! empty($b['ordered']) ? 'ol' : 'ul';
                    $h .= "<$tag>" . implode('', array_map(fn ($x) => '<li>' . $in($x) . '</li>', $items)) . "</$tag>\n";
                }
            } elseif ($type === 'steps') {
                $items = array_filter((array) ($b['items'] ?? []), 'is_array');
                if ($items) {
                    $h .= '<ol>' . implode('', array_map(fn ($x) => '<li>'
                        . (trim((string) ($x['title'] ?? '')) !== '' ? '<b>' . e($x['title']) . '.</b> ' : '')
                        . $in($x['text'] ?? '') . '</li>', $items)) . "</ol>\n";
                }
            } elseif ($type === 'table') {
                $rows = array_values(array_filter((array) ($b['rows'] ?? []), 'is_array'));
                if ($rows) {
                    $h .= '<div class="a-table"><table><thead><tr>'
                        . implode('', array_map(fn ($c) => '<th>' . $in((string) $c) . '</th>', $rows[0])) . '</tr></thead><tbody>'
                        . implode('', array_map(fn ($r) => '<tr>' . implode('', array_map(fn ($c) => '<td>' . $in((string) $c) . '</td>', $r)) . '</tr>', array_slice($rows, 1)))
                        . "</tbody></table></div>\n"
                        . (trim((string) ($b['caption'] ?? '')) !== '' ? '<div class="a-cap">' . e($b['caption']) . "</div>\n" : '');
                }
            } elseif ($type === 'callout') {
                $h .= '<div class="a-note' . (($b['tone'] ?? '') === 'warn' ? ' is-warn' : '') . '">'
                    . (trim((string) ($b['title'] ?? '')) !== '' ? '<b>' . e($b['title']) . '</b>' : '')
                    . $in($b['text'] ?? '') . "</div>\n";
            } elseif ($type === 'image') {
                $src = SitePages::img($b['src'] ?? ($b['url'] ?? null));
                if ($src) {
                    $h .= '<div class="a-fig"><img src="' . e(self::local($src)) . '" alt="' . e($b['alt'] ?? '') . '">'
                        . (trim((string) ($b['caption'] ?? '')) !== '' ? '<div class="a-cap">' . $in($b['caption']) . '</div>' : '') . "</div>\n";
                }
            } elseif ($type === 'quote') {
                $h .= '<blockquote>' . $in($b['text'] ?? '')
                    . (trim((string) ($b['cite'] ?? '')) !== '' ? ' <cite>' . e($b['cite']) . '</cite>' : '') . "</blockquote>\n";
            } elseif ($type === 'faq') {
                foreach ((array) ($b['items'] ?? []) as $it) {
                    if (trim((string) ($it['q'] ?? '')) !== '') {
                        $h .= '<h4>' . e($it['q']) . '</h4><p>' . $in($it['a'] ?? '') . "</p>\n";
                    }
                }
            } elseif ($type === 'links') {
                $items = array_filter((array) ($b['items'] ?? []), fn ($x) => trim((string) ($x['url'] ?? '')) !== '');
                if ($items) {
                    $h .= '<div class="a-note"><b>' . e($b['title'] ?? 'Related guides') . '</b><ul>'
                        . implode('', array_map(fn ($x) => '<li>' . $in('[' . str_replace(['[', ']'], '', (string) ($x['label'] ?? $x['url'])) . '](' . $x['url'] . ')') . '</li>', $items))
                        . "</ul></div>\n";
                }
            } elseif ($type === 'sources') {
                $items = array_filter((array) ($b['items'] ?? []), fn ($x) => preg_match('#^https?://#', (string) ($x['url'] ?? '')));
                if ($items) {
                    $h .= '<p><b>Sources</b></p><ol>'
                        . implode('', array_map(fn ($x) => '<li><a href="' . e($x['url']) . '">' . e($x['label'] ?? $x['url']) . '</a></li>', $items))
                        . "</ol>\n";
                }
            } elseif ($type === 'divider') {
                $h .= "<hr>\n";
            }
        }
        if (trim((string) ($hero['credit'] ?? '')) !== '') {
            $h .= '<div class="a-cap">Cover photo: ' . e($hero['credit']) . "</div>\n";
        }

        return $h;
    }

    /**
     * Links as the article needs them: this host's addresses made relative
     * (a sync run anywhere must not bake its own host in), and a link to a
     * guide that is also an article pointed at the article.
     */
    private static function link(string $html, array $map): string
    {
        $root = rtrim(url('/'), '/');

        return preg_replace_callback('/href="([^"]*)"/', function ($m) use ($root, $map) {
            $href = html_entity_decode($m[1], ENT_QUOTES);
            if (str_starts_with($href, $root . '/')) {
                $href = substr($href, strlen($root));
            }
            $path = strtok($href, '#?');
            if (isset($map[$path])) {
                $href = '/app/community/blog/' . $map[$path];
            }

            return 'href="' . e($href) . '"';
        }, $html);
    }

    /** A picture on this host as a path, so the stored body names no host. */
    private static function local(string $src): string
    {
        $root = rtrim(url('/'), '/');

        return str_starts_with($src, $root . '/') ? substr($src, strlen($root)) : $src;
    }
}
