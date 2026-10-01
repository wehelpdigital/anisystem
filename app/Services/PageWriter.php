<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\AsSitePage;
use App\Support\ArticleStyle;
use App\Support\SitePages;
use Illuminate\Support\Str;

/**
 * Anee writes a public page (2026-10-01): a Try and Ask Anee answer, or a
 * blog post drafted from the mother app's builder.
 *
 * One engine for both, so both follow the same house rules
 * (database/site-pages/STYLE.md): a Filipino agriculturist's plain voice, the
 * Yoast checks, keywords from as_seo_keywords woven in only where they read
 * naturally, links only to pages that exist, and an honest word about what
 * anee.io does for this exact topic with a "Try it for free" call. Research
 * first when asked (the web is read, then the page is written from the
 * notes: AiClient::researchThenJson), and the words are cleaned of dashes
 * and banned phrases afterwards (ArticleStyle), because a model still slips.
 *
 * The page comes back as as_site_pages fields: the caller saves it.
 */
class PageWriter
{
    public const BLOCK_TYPES = ['heading', 'text', 'list', 'steps', 'table', 'callout', 'image', 'quote', 'faq', 'cta', 'links', 'sources', 'divider'];

    public function __construct(private AiClient $ai) {}

    /**
     * @param  array{section?: string, topic?: string, question?: ?string, farm?: ?string, focusKeyword?: ?string,
     *               keywords?: array, lang?: ?string, notes?: ?string, research?: bool, country?: ?string,
     *               words?: string, current?: ?array}  $brief
     * @return array{ok: bool, page: ?array, error: ?string, result: array}
     */
    public function write(array $brief, ?callable $beat = null): array
    {
        $settings = AiSetting::current()->forField($brief['country'] ?? 'PH');
        $json = $this->jsonPrompt($brief);
        $parse = fn (string $t) => $this->parse($t);
        $result = ! empty($brief['research'])
            ? $this->ai->researchThenJson($settings, $this->researchPrompt($brief), $json, 9000, $parse, $beat)
            : $this->ai->askForJson($settings, $json, 9000, $parse, ['onPhase' => $beat]);
        $data = $result['data'] ?? null;
        if (! is_array($data)) {
            return ['ok' => false, 'page' => null, 'error' => $result['error'] ?? 'The page came back unreadable.', 'result' => $result];
        }

        $page = $this->shape($data, $brief, (array) ($result['sources'] ?? []));

        /* The rules a page must meet are checked, not hoped for (audit): the
         * meta tags, the headings, the keyphrase, the main keywords. A page
         * that misses any goes back once with the list, and the better of
         * the two is kept. */
        $problems = self::audit($page);
        if ($problems) {
            $fix = $this->ai->askForJson($settings, $this->repairPrompt($page, $problems), 9000, $parse,
                ['onPhase' => $beat ? fn (string $phase, int $try = 1) => $beat('document-json', $try) : null]);
            foreach (['tokensIn', 'tokensOut'] as $k) {
                $result[$k] = (int) ($result[$k] ?? 0) + (int) ($fix[$k] ?? 0);
            }
            if (is_array($fix['data'] ?? null)) {
                $again = $this->shape($fix['data'], $brief, (array) ($result['sources'] ?? []));
                if (count(self::audit($again)) < count($problems)) {
                    $page = $again;
                }
            }
        }

        return ['ok' => true, 'page' => $page, 'error' => null, 'result' => $result];
    }

    /**
     * What a page still gets wrong against the house rules, one sentence
     * each, written to be handed back to the model. Empty when it passes.
     *
     * @return list<string>
     */
    public static function audit(array $p): array
    {
        $out = [];
        $plain = fn ($s) => mb_strtolower(SitePages::plain((string) $s));
        $re = fn (string $needle) => '/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u';
        $has = fn (string $hay, string $needle) => $needle !== '' && (bool) preg_match($re($needle), $hay);
        $count = fn (string $hay, string $needle) => $needle === '' ? 0 : (int) preg_match_all($re($needle), $hay);

        $focus = mb_strtolower(trim((string) ($p['focusKeyword'] ?? '')));
        $heads = [];
        $levels = [];
        $body = $plain($p['excerpt'] ?? '');
        foreach ((array) ($p['blocks'] ?? []) as $b) {
            if (($b['type'] ?? '') === 'heading') {
                $heads[] = $plain($b['text'] ?? '');
                $levels[] = (int) ($b['level'] ?? 2);
            }
            $t = [];
            array_walk_recursive($b, function ($v, $k) use (&$t) {
                if (is_string($v) && ! in_array($k, ['type', 'url', 'src', 'tone', 'level'], true)) {
                    $t[] = $v;
                }
            });
            $body .= ' ' . $plain(implode(' ', $t));
        }

        $mt = mb_strlen((string) ($p['metaTitle'] ?? ''));
        $md = mb_strlen((string) ($p['metaDescription'] ?? ''));
        if ($focus === '') {
            $out[] = 'There is no focusKeyword.';
        }
        if ($mt < 35 || $mt > 60) {
            $out[] = "metaTitle is {$mt} characters. It must be 35 to 60.";
        }
        if ($focus !== '' && ! $has($plain($p['metaTitle'] ?? ''), $focus)) {
            $out[] = 'metaTitle must contain the focus keyphrase, near the start.';
        }
        if ($md < 120 || $md > 156) {
            $out[] = "metaDescription is {$md} characters. It must be 120 to 156.";
        }
        if ($focus !== '' && ! $has($plain($p['metaDescription'] ?? ''), $focus)) {
            $out[] = 'metaDescription must contain the focus keyphrase.';
        }
        if ($focus !== '' && ! $has($plain($p['title'] ?? ''), $focus)) {
            $out[] = 'The title (the H1) must contain the focus keyphrase.';
        }
        if ($focus !== '' && ! $has($plain($p['excerpt'] ?? ''), $focus)) {
            $out[] = 'The excerpt (the first paragraph) must contain the focus keyphrase.';
        }
        $h2 = count(array_filter($levels, fn ($l) => $l === 2));
        if ($h2 < 3) {
            $out[] = "There are {$h2} H2 headings. Use at least 3, one per main section.";
        }
        if ($focus !== '' && ! array_filter($heads, fn ($h) => $has($h, $focus))) {
            $out[] = 'At least one H2 must contain the focus keyphrase.';
        }
        $mains = array_values(array_filter(array_map(fn ($k) => mb_strtolower(trim((string) $k)), (array) ($p['mainKeywords'] ?? []))));
        if (! $mains) {
            $out[] = 'Choose 1 or 2 mainKeywords (the highest search volume keywords from the list that fit the topic) and return them.';
        }
        foreach ($mains as $k) {
            $n = $count($body, $k);
            if ($n < 2) {
                $out[] = 'The main keyword "' . $k . '" appears ' . $n . ' time(s) in the content. Use it at least 2 times, naturally (the excerpt, an H2, the paragraphs).';
            }
        }
        if (! array_filter((array) ($p['blocks'] ?? []), fn ($b) => ($b['type'] ?? '') === 'faq')) {
            $out[] = 'Add an faq block with 3 to 6 questions people really ask.';
        }
        $words = str_word_count($body);
        if ($words < 700) {
            $out[] = "The content is {$words} words. Write at least 800.";
        }

        return $out;
    }

    /** The page handed back with what to fix. */
    private function repairPrompt(array $page, array $problems): string
    {
        $show = array_intersect_key($page, array_flip(['title', 'slug', 'metaTitle', 'metaDescription', 'focusKeyword', 'mainKeywords', 'keywords', 'excerpt', 'category', 'lang', 'crop', 'blocks']));
        $show['keywordsUsed'] = $show['keywords'] ?? [];
        unset($show['keywords']);

        return "You wrote this page for anee.io (JSON below). It breaks these rules:\n- " . implode("\n- ", $problems) . "\n\n"
            . "Fix every one. Keep everything else as it is: the facts, the links, the sources, the cta, the order of the sections. "
            . "Keep the house rules: plain words, no dashes, no semicolons, no forbidden words, keywords in natural word order with names capitalized. "
            . "The title is the only H1, body headings are H2 (H3 only inside an H2 section).\n\n"
            . json_encode($show, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n\nReturn ONLY the corrected JSON object in the same format, no fences, no commentary.";
    }

    /** The pages a writer may link to, "url — title", from the live site. */
    public static function urlMap(int $cap = 140): string
    {
        $lines = ['/ — home', '/features — every feature', '/pricing — plans and prices', '/signup — create a free account',
            '/ask-anee — ask Anee a free farming question', '/questions — farmers\' questions answered by Anee'];
        try {
            foreach (AsSitePage::live()->orderBy('section')->orderBy('sortOrder')->limit($cap)->get(['section', 'slug', 'title']) as $p) {
                $lines[] = parse_url(SitePages::url($p->section, $p->slug), PHP_URL_PATH) . ' — ' . $p->title;
            }
        } catch (\Throwable $e) {
            // no pages yet: the site's own pages still stand
        }

        return implode("\n", $lines);
    }

    /** The paths the map offers, for checking the links a page came back with. */
    public static function knownPaths(): array
    {
        $paths = ['/', '/features', '/pricing', '/signup', '/about', '/contact', '/tutorial', '/crops', '/problems', '/blog', '/ask-anee', '/questions'];
        try {
            foreach (AsSitePage::live()->get(['section', 'slug']) as $p) {
                $paths[] = parse_url(SitePages::url($p->section, $p->slug), PHP_URL_PATH);
            }
        } catch (\Throwable $e) {
        }

        return array_values(array_unique($paths));
    }

    private function researchPrompt(array $b): string
    {
        $what = trim(($b['question'] ?? '') !== '' ? 'A farmer asked: "' . $b['question'] . '"' : 'Topic: ' . ($b['topic'] ?? ''));

        return "You are researching for a page on anee.io, written for farmers in the Philippines.\n\n{$what}\n"
            . (! empty($b['farm']) ? 'Their farm: ' . $b['farm'] . "\n" : '')
            . (! empty($b['notes']) ? 'The editor\'s notes: ' . $b['notes'] . "\n" : '')
            . "\nUse the search tool. Find what the official and expert sources say: PhilRice, the Department of Agriculture and its regional offices, BPI, FPA, IRRI, PCA, PhilMech, ATI, UPLB and other universities, and manufacturers' own label pages for any product. "
            . "Collect exact figures with their source: rates per hectare, timing in days after sowing or transplanting, varieties, thresholds, prices (with the date), local Tagalog names, and warnings. "
            . "Note where sources disagree. Write plain research notes, a short paragraph per point, each with the URL it came from. Do not write the page yet.";
    }

    private function jsonPrompt(array $b): string
    {
        $section = $b['section'] ?? 'blog';
        $lang = ($b['lang'] ?? 'en') === 'tl' ? 'tl' : 'en';
        $words = $b['words'] ?? ($section === 'questions' ? '800 to 1300' : '900 to 1600');
        $keywords = array_values(array_filter(array_map('strval', (array) ($b['keywords'] ?? []))));
        $focus = trim((string) ($b['focusKeyword'] ?? ''));
        $crops = collect(\App\Support\CropCatalog::CROPS)->map(fn ($c, $k) => $k)->implode(', ');

        $about = match ($section) {
            'questions' => "A farmer asked Anee this question on the public site:\n\"" . ($b['question'] ?? '') . "\"\n"
                . (! empty($b['farm']) ? 'Their farm: ' . $b['farm'] . ".\n" : '')
                . "Write the page that answers it, as an article anyone with the same question can read and use. Answer the question directly in the excerpt and first section, then explain. Speak to the farmer's situation (the crop, the size, the place) where it changes the answer, without naming or quoting the person.\n",
            default => 'Write a ' . ($section === 'blog' ? 'blog post' : ($section === 'crops' ? 'crop guide' : 'crop problem guide')) . " for anee.io.\nTopic: " . ($b['topic'] ?? '') . "\n",
        };
        $current = ! empty($b['current']) ? "\nThe page as it stands now (improve and complete it, keep what is right):\n" . mb_substr(json_encode($b['current'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 30000) . "\n" : '';

        return $about
            . (! empty($b['notes']) ? "The editor's instructions: " . $b['notes'] . "\n" : '')
            . $current
            . "\n--- Voice ---\n"
            . ($lang === 'tl'
                ? "Write in natural Filipino as a Filipino farm writer would, with the English terms farmers really use (fertilizer, urea, insecticide, hectare). Not stiff textbook Tagalog.\n"
                : "Write in English as a Filipino agriculturist talking to farmers: plain words, short sentences, real field detail (cavans, hectares, the DA, PhilRice, wet and dry seasons, typhoons, local names). Common Filipino farm words (palay, mais, abono, punla, ani) are fine, each explained once.\n")
            . "Write as the anee.io agriculture team (\"we\"), not as a chat: no greetings, no emoji, no :anee: codes. Be exact: numbers, names, active ingredients and dates must be true (use the research notes when given). When a value varies, give the range and the source. Never invent a statistic, a study, a product claim or a quote. Pesticides: name the active ingredient and the group, and tell the reader to follow the product label and its FPA registration. Never give a spray rate you did not verify. No fluff opening, no hype, no filler conclusion.\n"
            . "\n--- Forbidden words ---\nNever use: " . implode(', ', array_keys(ArticleStyle::SWAPS)) . ".\n"
            . "No dashes of any kind in the text (no em dash, en dash or hyphen: write \"well drained soil\", \"14 14 14\", \"day 30 after transplanting\"). No semicolons, no ampersands, no ellipses, no arrows, no emoji, no % sign (write \"percent\"), straight quotes only.\n"
            . "\n--- SEO (Yoast) ---\n"
            . ($focus !== '' ? "The focus keyphrase is \"{$focus}\".\n" : "Choose one focus keyphrase: the phrase a farmer would type into Google for this, 2 to 5 words, preferably one of the keywords below when one fits.\n")
            . "Put the focus keyphrase in the title, at the start of the metaTitle, in the metaDescription, in the slug, in the excerpt (the first paragraph) and in at least one H2. Use it naturally, about 1 percent of words, and use synonyms and the secondary keywords for the rest.\n"
            . "Meta tags: metaTitle 35 to 60 characters, starting with the focus keyphrase (the site adds \" | anee.io\"). metaDescription 120 to 156 characters, one or two active sentences with the focus keyphrase and a reason to click. The slug: the keyphrase words, lower case, joined by hyphens.\n"
            . "Headings: the title is the page's only H1 (it carries the focus keyphrase). Body headings are H2 for each main section (4 to 7 of them) and H3 only inside an H2 section, never an H3 before the first H2, never a skipped level. The focus keyphrase in at least one H2, a main keyword in another H2 where it reads naturally.\n"
            . "Structure for rich results: a steps block for any procedure (it becomes HowTo data), an faq block with 3 to 6 questions farmers really ask with direct answers (FAQ data), a table for rates, doses or comparisons, and a clear direct answer to the main question in the excerpt.\n"
            . "Length: {$words} words. H2 for sections, H3 inside them, a heading at least every 300 words. Paragraphs of 2 to 4 sentences, never over 150 words. At most a quarter of sentences over 20 words. Transition words (also, but, so, because, for example, first, next, then, after that, finally, besides, instead, in fact, as a result) in at least 30 percent of sentences. Passive voice in under 10 percent. Never three sentences in a row starting with the same word.\n"
            . ($keywords ? "\nKeywords from our keyword research, with monthly searches:\n- " . implode("\n- ", $keywords) . "\n"
                . "MAIN KEYWORDS: choose 1 or 2 of these, the highest search volume ones that truly fit this topic, and use each one at least 2 times in the content (the excerpt, an H2, the paragraphs), naturally. Return them as mainKeywords.\n"
                . "Secondary keywords: weave in the others that fit the topic, at least three if they fit. Never force one: a keyword that does not belong to this topic is left out.\n"
                . "Every keyword reads naturally and in proper case: rewrite the word order (\"fertilizer urea\" becomes \"urea fertilizer\", \"fertilizer yara\" becomes \"Yara fertilizer\"), capitalize names of brands, agencies and varieties (Atlas, Yara, Fertilizer and Pesticide Authority, PhilRice, NSIC Rc 222), and use a farmer's own word where a keyword would sound odd (binhi or seed, not \"corn kernel\", when seeds are meant).\n" : '')
            . "\n--- Links ---\nAt least 3 internal links inside the body text as [label](/path), using ONLY these pages:\n" . self::urlMap() . "\n"
            . "At least 1 outbound link to an authoritative source, in a sources block (full https addresses from the research notes).\n"
            . "\n--- anee.io, honestly ---\nOnly claim what anee.io really does: a cropping calendar that dates every task from each lot's own day zero (DAS, DAT or DAP), Anee the AI technician who answers in Tagalog or English and can look at a photo of a pest or a sick plant, growth stages for 85 Philippine crops with the weather forecast per lot, When to Plant and What to Plant analyses, Variety Research, Crop Protocol Analysis, the Protocol Builder, workers and payroll, inventory that tracks fertilizer and chemicals, labor, expenses and profit reports, notes with photos and voice, farm maps, a farmer community. Free to start (Libre), paid plans for more.\n"
            . "Mention once in the body how anee.io helps with this exact topic, and include one cta block that promotes it for this topic (higher yield, lower cost, less guesswork), with \"label\": \"Try it for free\" and \"url\": \"/signup\". Its text: one sentence, then two or three short benefit lines, each on its own line (separated by a single newline).\n"
            . "\n--- Blocks ---\nThe page body is a list of blocks, exactly these kinds:\n"
            . "heading {level: 2 or 3, text} | text {text: paragraphs separated by a blank line, inline [label](/url) and **bold** only} | list {ordered: true/false, items: [strings]} | steps {items: [{title, text}]} | table {caption, rows: [[header cells], [cells]...]} | callout {tone: tip, warn or info, title, text} | quote {text, cite} | faq {items: [{q, a}]} (3 to 6 questions people really ask) | cta {title, text, label, url} | links {title, items: [{label, url}]} (3 to 6 related pages from the list) | sources {items: [{label, url}]} | divider {}\n"
            . "A good shape: 1 or 2 text blocks, then H2 sections with text, lists, tables or steps as the content needs, a callout, the cta about two thirds down, a faq, a links block, a sources block last. The excerpt is the first paragraph (shown under the title): do not repeat it as the first text block.\n"
            . "\n--- Answer ---\nReturn ONLY this JSON object, no fences, no commentary:\n"
            . '{"title": "...", "slug": "words-of-the-keyphrase", "metaTitle": "...", "metaDescription": "...", "focusKeyword": "...", "mainKeywords": ["1 or 2 main keywords"], "keywordsUsed": ["the secondary keywords you used"], "excerpt": "...", "category": "a short category, e.g. Palay, Mais, Fertilizer, Pests", "lang": "' . $lang . '", "crop": "one crop key or null", "blocks": [ ... ]}'
            . "\nCrop keys: {$crops}\n";
    }

    private function parse(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $t);
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start === false || $end === false) {
            return null;
        }
        $data = json_decode(substr($t, $start, $end - $start + 1), true);

        return is_array($data) && ! empty($data['title']) && is_array($data['blocks'] ?? null) && count($data['blocks']) >= 3 ? $data : null;
    }

    /** What came back, made safe and true to the rules, as page fields. */
    private function shape(array $d, array $b, array $sources): array
    {
        $known = self::knownPaths();
        $fixLinks = function (string $s) use ($known): string {
            return (string) preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) use ($known) {
                $url = $m[2];
                if (preg_match('#^https?://#i', $url)) {
                    $host = (string) parse_url($url, PHP_URL_HOST);
                    // Our own host written in full: kept as a path when it is a page.
                    if (str_ends_with($host, 'anee.io')) {
                        $path = parse_url($url, PHP_URL_PATH) ?: '/';

                        return in_array(rtrim($path, '/') ?: '/', $known, true) ? '[' . $m[1] . '](' . (rtrim($path, '/') ?: '/') . ')' : $m[1];
                    }

                    return $m[0];
                }
                $path = rtrim((string) strtok($url, '#?'), '/') ?: '/';

                return in_array($path, $known, true) || str_starts_with($path, '/features/') && in_array($path, $known, true) ? $m[0] : $m[1];
            }, $s);
        };

        $blocks = [];
        foreach ((array) $d['blocks'] as $blk) {
            if (! is_array($blk) || ! in_array($blk['type'] ?? '', self::BLOCK_TYPES, true)) {
                continue;
            }
            if ($blk['type'] === 'image') {
                continue;   // pictures are chosen by the site, never invented
            }
            if ($blk['type'] === 'cta') {
                $blk['label'] = trim((string) ($blk['label'] ?? '')) ?: 'Try it for free';
                $u = (string) ($blk['url'] ?? '/signup');
                $blk['url'] = str_starts_with($u, '/') && in_array(rtrim(strtok($u, '#?'), '/') ?: '/', $known, true) ? $u : '/signup';
            }
            if (in_array($blk['type'], ['links'], true)) {
                $blk['items'] = array_values(array_filter((array) ($blk['items'] ?? []), fn ($i) => is_array($i)
                    && in_array(rtrim((string) strtok((string) ($i['url'] ?? ''), '#?'), '/') ?: '/', $known, true)));
                if (! $blk['items']) {
                    continue;
                }
            }
            if ($blk['type'] === 'sources') {
                $blk['items'] = array_values(array_filter((array) ($blk['items'] ?? []), fn ($i) => is_array($i) && preg_match('#^https://#i', (string) ($i['url'] ?? ''))));
                if (! $blk['items']) {
                    continue;
                }
            }
            array_walk_recursive($blk, function (&$v, $k) use ($fixLinks) {
                if (is_string($v) && ! in_array($k, ['type', 'url', 'src', 'tone'], true)) {
                    $v = $fixLinks($v);
                }
            });
            $blocks[] = $blk;
        }

        // A page without its call gets one, two thirds of the way down.
        if (! collect($blocks)->contains(fn ($x) => $x['type'] === 'cta')) {
            array_splice($blocks, (int) floor(count($blocks) * 2 / 3), 0, [[
                'type' => 'cta',
                'title' => 'Grow it with a plan, not a guess',
                'text' => "anee.io dates every task on your cropping calendar and lets you ask Anee when something looks wrong.\nKnow what to do on each day of the season\nSee the growth stage and the weather for every lot\nKeep every peso of fertilizer and labor on record",
                'label' => 'Try it for free',
                'url' => '/signup',
            ]]);
        }
        // The research's own pages, when the writer left the sources out.
        if (! collect($blocks)->contains(fn ($x) => $x['type'] === 'sources') && $sources) {
            $items = [];
            foreach ($sources as $s) {
                $url = (string) ($s['url'] ?? '');
                if (preg_match('#^https://#', $url) && ! str_contains($url, 'grounding-api-redirect')) {
                    $items[] = ['label' => (string) ($s['title'] ?? parse_url($url, PHP_URL_HOST)), 'url' => $url];
                }
            }
            if ($items) {
                $blocks[] = ['type' => 'sources', 'items' => array_slice($items, 0, 6)];
            }
        }

        // Headings in order: H2 for sections, H3 only after an H2 (the title is the H1).
        $seenH2 = false;
        foreach ($blocks as $i => $blk) {
            if ($blk['type'] === 'heading') {
                $level = (int) ($blk['level'] ?? 2) >= 3 && $seenH2 ? 3 : 2;
                $seenH2 = $seenH2 || $level === 2;
                $blocks[$i]['level'] = $level;
            }
        }
        $blocks = ArticleStyle::cleanBlocks($blocks);
        $focus = ArticleStyle::clean((string) ($d['focusKeyword'] ?? ($b['focusKeyword'] ?? '')));
        $mains = array_values(array_slice(array_filter(array_map(fn ($k) => ArticleStyle::clean((string) $k), (array) ($d['mainKeywords'] ?? []))), 0, 2));
        $title = ArticleStyle::clean((string) $d['title']);
        $metaTitle = ArticleStyle::clean((string) ($d['metaTitle'] ?? $title));
        $meta = ArticleStyle::clean((string) ($d['metaDescription'] ?? ''));
        $slug = Str::slug((string) ($d['slug'] ?? '') ?: ($focus ?: $title));
        $crop = isset(\App\Support\CropCatalog::CROPS[(string) ($d['crop'] ?? '')]) ? (string) $d['crop'] : null;

        return [
            'title' => Str::limit($title, 180, ''),
            'slug' => Str::limit(trim($slug, '-'), 90, ''),
            'metaTitle' => self::cut($metaTitle, 60),
            'metaDescription' => self::cut($meta, 156),
            'focusKeyword' => Str::limit($focus, 120, ''),
            'mainKeywords' => $mains,
            'keywords' => array_values(array_slice(array_unique(array_merge($mains, array_filter(array_map(fn ($k) => ArticleStyle::clean((string) $k), (array) ($d['keywordsUsed'] ?? []))))), 0, 12)),
            'excerpt' => $fixLinks(ArticleStyle::clean((string) ($d['excerpt'] ?? ''))),
            'category' => Str::limit(ArticleStyle::clean((string) ($d['category'] ?? '')), 60, '') ?: null,
            'lang' => ($d['lang'] ?? '') === 'tl' ? 'tl' : 'en',
            'crop' => $crop,
            'blocks' => $blocks,
        ];
    }

    /** Cut at a word, never mid word, never with an ellipsis. */
    public static function cut(string $s, int $max): string
    {
        $s = trim($s);
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        $cut = mb_substr($s, 0, $max);
        $sp = mb_strrpos($cut, ' ');

        return rtrim($sp > $max * 0.6 ? mb_substr($cut, 0, $sp) : $cut, " ,.:");
    }
}
