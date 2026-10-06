<?php

namespace App\Support;

use App\Models\AsSitePage;
use Illuminate\Support\Str;

/**
 * The structured data a public page carries (2026-10-01): one JSON-LD
 * @graph, every node tied to the others by @id, built from the page itself
 * so it can never disagree with what the reader sees.
 *
 *   Organization + WebSite      who publishes, and where
 *   WebPage (QAPage for an answered question, with the Question and its
 *   accepted Answer)            the page, its breadcrumb, its picture
 *   Article / BlogPosting       headline, description, picture, dates,
 *                               keywords, section, word count, sources
 *   BreadcrumbList              Home > section > page
 *   FAQPage                     from the faq blocks
 *   HowTo                       one per steps block, named by its heading
 */
class PageSchema
{
    public static function graph(AsSitePage $page, array $blocks, array $meta, string $canonical, array $faq): array
    {
        $site = rtrim(url('/'), '/');
        $org = $site . '/#organization';
        $web = $site . '/#website';
        $pageId = $canonical . '#webpage';
        $imgId = $canonical . '#primaryimage';
        $crumbId = $canonical . '#breadcrumb';
        $lang = $page->lang === 'tl' ? 'fil-PH' : 'en-PH';
        $updated = $page->updated_at ?? now();
        $published = $page->publishedAt ?? $updated;
        $isQuestion = $page->section === 'questions';
        // A farm news roundup (App\Services\NewsRoundup) is news: a NewsArticle
        // that lists the reports it covers.
        $isNews = ($page->kind ?? null) === 'roundup';
        $isFeature = $page->section === 'features';
        $seoTitle = trim((string) ($page->metaTitle ?: $page->title));
        $description = (string) ($page->metaDescription ?: Str::limit(SitePages::plain($page->excerpt), 155));
        $sectionUrl = $isFeature ? route('features') : SitePages::url($page->section);

        $graph = [[
            '@type' => 'Organization',
            '@id' => $org,
            'name' => 'anee.io',
            'url' => $site . '/',
            'logo' => ['@type' => 'ImageObject', '@id' => $site . '/#logo', 'url' => asset('images/logo.png'), 'caption' => 'anee.io'],
            'email' => 'support@anee.io',
            'areaServed' => 'PH',
        ], [
            '@type' => 'WebSite',
            '@id' => $web,
            'url' => $site . '/',
            'name' => 'anee.io',
            'publisher' => ['@id' => $org],
            'inLanguage' => 'en-PH',
        ]];

        $hero = is_array($page->heroImage) ? $page->heroImage : [];
        $heroSrc = SitePages::img($hero['src'] ?? null);
        if ($heroSrc) {
            $img = ['@type' => 'ImageObject', '@id' => $imgId, 'url' => $heroSrc, 'contentUrl' => $heroSrc,
                'caption' => (string) ($hero['alt'] ?? $page->title), 'inLanguage' => $lang];
            if (! preg_match('#^https?://#i', (string) ($hero['src'] ?? '')) && ($size = @getimagesize(public_path(ltrim((string) $hero['src'], '/'))))) {
                $img['width'] = $size[0];
                $img['height'] = $size[1];
            }
            if (trim((string) ($hero['credit'] ?? '')) !== '') {
                // The credit as words; a link inside it is the picture's own page.
                $img['creditText'] = SitePages::plain((string) $hero['credit']);
                $img['copyrightNotice'] = SitePages::plain((string) $hero['credit']);
                if (preg_match('/\]\((https?:\/\/[^)\s]+)\)/', (string) $hero['credit'], $m)) {
                    $img['acquireLicensePage'] = $m[1];
                }
            }
            $graph[] = $img;
        }

        $webpage = [
            '@type' => $isQuestion ? 'QAPage' : 'WebPage',
            '@id' => $pageId,
            'url' => $canonical,
            'name' => $seoTitle,
            'description' => $description,
            'isPartOf' => ['@id' => $web],
            'breadcrumb' => ['@id' => $crumbId],
            'inLanguage' => $lang,
            'datePublished' => $published->toAtomString(),
            'dateModified' => $updated->toAtomString(),
        ] + ($heroSrc ? ['primaryImageOfPage' => ['@id' => $imgId]] : []);

        // An answered question: the question the farmer asked, and the
        // answer's opening as the accepted one.
        if ($isQuestion) {
            $asked = collect($blocks)->first(fn ($b) => ($b['type'] ?? '') === 'callout');
            $webpage['mainEntity'] = [
                '@type' => 'Question',
                'name' => $page->title,
                'text' => $asked ? SitePages::plain((string) ($asked['text'] ?? '')) : $page->title,
                'answerCount' => 1,
                'datePublished' => $published->toAtomString(),
                'author' => ['@type' => 'Person', 'name' => 'A farmer'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => SitePages::plain((string) $page->excerpt),
                    'url' => $canonical,
                    'datePublished' => $published->toAtomString(),
                    'upvoteCount' => 0,
                    'author' => ['@id' => $org],
                ],
            ];
        }
        $graph[] = $webpage;

        if (! $isFeature) {
            $keywords = array_values(array_unique(array_filter(array_map('trim', array_merge([(string) $page->focusKeyword], (array) ($page->keywords ?? []))))));
            $sources = [];
            foreach ($blocks as $b) {
                if (($b['type'] ?? '') === 'sources') {
                    foreach ((array) ($b['items'] ?? []) as $it) {
                        if (preg_match('#^https?://#i', (string) ($it['url'] ?? ''))) {
                            $sources[] = ['@type' => 'CreativeWork', 'name' => (string) ($it['label'] ?? $it['url']), 'url' => (string) $it['url']];
                        }
                    }
                }
            }
            $graph[] = array_filter([
                '@type' => $isNews ? 'NewsArticle' : ($page->section === 'blog' ? 'BlogPosting' : 'Article'),
                '@id' => $canonical . '#article',
                'isPartOf' => ['@id' => $pageId],
                'mainEntityOfPage' => ['@id' => $pageId],
                'headline' => Str::limit((string) $page->title, 110, ''),
                'description' => $description,
                'image' => $heroSrc ? ['@id' => $imgId] : null,
                'author' => ['@id' => $org],
                'publisher' => ['@id' => $org],
                'datePublished' => $published->toAtomString(),
                'dateModified' => $updated->toAtomString(),
                'inLanguage' => $lang,
                'articleSection' => $page->category ?: ($meta['label'] ?? null),
                'keywords' => $keywords ? implode(', ', $keywords) : null,
                'wordCount' => SitePages::wordCount($page),
                'citation' => $sources ?: null,
                'isAccessibleForFree' => true,
                'copyrightHolder' => ['@id' => $org],
                'copyrightYear' => (int) $published->format('Y'),
                // The reports a roundup covers, each one's own page.
                'mentions' => $isNews && $sources ? array_map(fn ($s) => ['@type' => 'NewsArticle', 'headline' => Str::limit(Str::after($s['name'], ': '), 110, ''),
                    'url' => $s['url'], 'publisher' => ['@type' => 'Organization', 'name' => Str::before($s['name'], ': ')]], $sources) : null,
            ], fn ($v) => $v !== null);
            if ($isNews && $sources) {
                $graph[] = [
                    '@type' => 'ItemList',
                    '@id' => $canonical . '#stories',
                    'name' => 'The stories in this roundup',
                    'numberOfItems' => count($sources),
                    'itemListElement' => array_map(fn ($s, $n) => ['@type' => 'ListItem', 'position' => $n + 1, 'url' => $s['url'], 'name' => $s['name']],
                        $sources, array_keys($sources)),
                ];
            }
        }

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $crumbId,
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $site . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $meta['label'] ?? 'Guides', 'item' => $sectionUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $page->title, 'item' => $canonical],
            ],
        ];

        if ($faq) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical . '#faq',
                'isPartOf' => ['@id' => $pageId],
                'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faq),
            ];
        }

        // Each list of steps is a HowTo, named by the heading above it.
        $heading = null;
        foreach ($blocks as $i => $b) {
            if (($b['type'] ?? '') === 'heading') {
                $heading = ['text' => (string) ($b['text'] ?? ''), 'i' => $i];
            }
            if (($b['type'] ?? '') !== 'steps') {
                continue;
            }
            $steps = array_values(array_filter((array) ($b['items'] ?? []), fn ($s) => is_array($s) && trim((string) ($s['title'] ?? $s['text'] ?? '')) !== ''));
            if (count($steps) < 2) {
                continue;
            }
            $graph[] = [
                '@type' => 'HowTo',
                '@id' => $canonical . '#howto-' . $i,
                'name' => $heading['text'] ?? $page->title,
                'inLanguage' => $lang,
                'step' => array_map(fn ($s, $n) => array_filter([
                    '@type' => 'HowToStep',
                    'position' => $n + 1,
                    'name' => trim((string) ($s['title'] ?? '')) ?: Str::limit(SitePages::plain((string) ($s['text'] ?? '')), 80, ''),
                    'text' => SitePages::plain((string) ($s['text'] ?? $s['title'] ?? '')),
                    'url' => $heading ? $canonical . '#' . SitePages::anchor($heading['text'], $heading['i']) : null,
                ], fn ($v) => $v !== null && $v !== ''), $steps, array_keys($steps)),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
