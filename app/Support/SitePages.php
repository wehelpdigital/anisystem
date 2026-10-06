<?php

namespace App\Support;

use App\Models\AsSitePage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The public site's guides, blog and feature pages (2026-10-01).
 *
 * The sections, each with a hub and its pages:
 *
 *   crops     /crops, /crops/{slug}          crop guides
 *   pests     /pests, /pests/{slug}          insect and mite pests
 *   diseases  /diseases, /diseases/{slug}    crop diseases
 *   weeds     /weeds, /weeds/{slug}          weeds and grasses (a catalogue
 *                                            of rice weeds and the guides)
 *   blog      /blog, /blog/{slug}            fertilizer, pesticides, words, culture
 *   features  /features (the tour), /features/{slug}
 *
 * Pests, diseases and weeds were one "problems" section until 2026-10-06.
 * /problems is now a front door to the three, and /problems/{slug} sends
 * an old address to the page's new home.
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
        'pests' => [
            'label' => 'Crop Pests',
            'crumb' => 'Crop pests',
            'hubTitle' => 'Crop Pests in the Philippines',
            'metaTitle' => 'Crop Pests in the Philippines: Insects of Rice and Corn',
            'metaDescription' => 'Spot and stop the insect pests of Philippine farms: rice bug, black bug, brown planthopper, leaffolder, fall armyworm, corn borer, thrips and mites.',
            'intro' => 'Know which insect is on your crop before you buy a spray. Each guide shows the signs, the damage, the right time to act and the ways to manage it.',
            'kicker' => 'Crop pests',
        ],
        'diseases' => [
            'label' => 'Crop Diseases',
            'crumb' => 'Crop diseases',
            'hubTitle' => 'Crop Diseases in the Philippines',
            'metaTitle' => 'Crop Diseases in the Philippines: Signs and Control',
            'metaDescription' => 'Learn the signs of common crop diseases in the Philippines, like sheath blight, anthracnose and fusarium wilt, and how to manage them before they spread.',
            'intro' => 'A spot on a leaf can be a fungus, a bacterium or just the weather. These guides show the signs of each disease, why it comes and what to do before it spreads.',
            'kicker' => 'Crop diseases',
        ],
        'weeds' => [
            'label' => 'Weeds and Grasses',
            'crumb' => 'Weeds and grasses',
            'hubTitle' => 'Weeds and Grasses in Philippine Rice Fields',
            'metaTitle' => 'Rice Field Weeds in the Philippines: Names and Control',
            'metaDescription' => 'A field guide to the weeds of Philippine rice fields: grasses, sedges and broadleaves, their local names, photos and how to control each one.',
            'intro' => 'Damo steals the light, water and fertilizer your palay needs. Find the weeds in your field by their look and their local names, then see what to do about each one at every age of your rice.',
            'kicker' => 'Weeds and grasses',
        ],
        // The old one section door, kept as the front door to the three.
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
        // Try and Ask Anee's answers (2026-10-01): one page per question a
        // visitor asked, at /question/{slug}, listed at /questions.
        'questions' => [
            'label' => "Farmers' questions",
            'crumb' => "Farmers' questions",
            'hubTitle' => "Farmers' Questions, Answered by Anee",
            'metaTitle' => 'Farming Questions Answered: Palay, Mais, Pests, Fertilizer',
            'metaDescription' => 'Real questions from Filipino farmers about palay, mais, vegetables, pests and fertilizer, each answered in full by Anee, the anee.io AI technician.',
            'intro' => 'Real questions from farmers, each answered in full: what to do, when, and how much. Ask your own and the answer comes to your email.',
            'kicker' => 'Ask Anee',
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

    /**
     * How each feature page shows up as a product card: a short name, an
     * icon (a 24px stroke path), one line on what it does, and a hue for its
     * badge. Keyed by slug; a page without an entry gets a plain one.
     */
    public const FEATURES = [
        'cropping-calendar' => ['Cropping Calendar', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 10l2 2 4-4', 'Plan the whole season by DAS, DAT or DAP, from land preparation to harvest. Every lot keeps its own day zero.', 100],
        'ai-agricultural-technician' => ['Anee, the AI Technician', 'M8 10h8M8 14h5m8-2a8 8 0 01-11.6 7.1L4 20l1-4.2A8 8 0 1121 12z', 'Ask about pests, fertilizer or a sick plant in Tagalog or English, and show her a photo of it.', 150],
        'growth-stages-and-weather' => ['Growth Stages and Weather', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', 'See the crop growth stage of every lot on any date, with the weather forecast for your farm.', 88],
        'farm-workers-and-payroll' => ['Workers and Payroll', 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 015-3.9m6-4.1a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z', 'Keep your workers, their daily rates and attendance, and let the labor cost add itself up.', 32],
        'farm-inventory-and-expenses' => ['Inventory and Expenses', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'Know what fertilizer, seeds and chemicals sit in the shed, and what each bag really cost you.', 24],
        'farm-reports' => ['Farm Reports', 'M9 19V13a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Labor, expenses, profit and your ani per hectare, added up from the records you already keep.', 200],
        'notes-photos-and-voice' => ['Notes, Photos and Voice', 'M19 11a7 7 0 01-14 0m7 7v3m-4 0h8m-4-6a3 3 0 003-3V6a3 3 0 00-6 0v6a3 3 0 003 3z', 'Write down what you see in the field with photos, video clips and voice notes, dated to the day.', 330],
        'farm-maps' => ['Farm Maps', 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2', 'Trace your fields over a satellite view and measure their area, boundaries and distances.', 175],
        'when-to-plant-analysis' => ['When to Plant', 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-10A5 5 0 007.2 10.1 4 4 0 003 15z', 'Anee reads the rainfall and typhoon record of your town and ranks the best planting weeks.', 205],
        'protocol-builder' => ['Protocol Builder', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'Write your crop protocol once, on a day count, and bring it into every new cropping schedule.', 270],
        'farmer-community' => ['Farmer Community', 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m8-10H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l4-4h4a2 2 0 002-2V6a2 2 0 00-2-2z', 'Share wins and warnings with farmers across the Philippines, and climb a ladder of 100 levels.', 350],
        // Every How It Works tool has its own page (2026-10-05).
        'what-to-plant-analysis' => ['What to Plant', 'M12 3c4 4 6 7 6 10a6 6 0 01-12 0c0-3 2-6 6-10zm0 8v7', 'Anee weighs your climate, soil and water and ranks the crops that should do best this season.', 110],
        'variety-research' => ['Variety Research', 'M9 5h11M9 12h11M9 19h11M4.5 5h.01M4.5 12h.01M4.5 19h.01', 'Compare seed varieties for your area on yield, maturity and resistance before you buy.', 70],
        'crop-protocol-analysis' => ['Crop Protocol Analysis', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'Anee writes the season for your crop and variety, stage by stage, checked against your field.', 140],
        'farm-drawing' => ['Farm Drawing', 'M15.2 5.2l3.6 3.6M4 20l1-4.6L16.4 4a1.5 1.5 0 012.1 0l1.5 1.5a1.5 1.5 0 010 2.1L8.6 19 4 20z', 'Sketch the seedbed, the water and who works which lot, and keep one plan for the team.', 30],
        'protocol-review-by-anee' => ['Protocol Review by Anee', 'M8 10h8M8 14h5m8-2a8 8 0 01-11.6 7.1L4 20l1-4.2A8 8 0 1121 12z', 'Before you commit, Anee reads your protocol and points out the problems and weak spots.', 160],
        'farm-lots' => ['Farm Lots', 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2', 'Each field as a lot with its own crop, variety, size, place and day count.', 190],
        'team-logins' => ['Team Logins', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.7 5.7L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.6a1 1 0 01.3-.7l6-6A6 6 0 1121 9z', 'Give a worker their own login and decide what they may see and change, module by module.', 220],
        'collab-room' => ['Collab Room', 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 015-3.9m6-4.1a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z', 'A room for each season: team chat, a shared whiteboard, live cameras and Anee.', 250],
        'morning-plan-email' => ['Morning Plan Email', 'M3 8l7.9 4.3a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'The day\'s farm work in every inbox at 6 AM, sent on its own.', 40],
        'automatic-stock-deduction' => ['Automatic Stock Deduction', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'Tick a fertilizer or spray task done and the shed takes it out of stock.', 20],
        'farm-weather-forecast' => ['Farm Weather Forecast', 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-10A5 5 0 007.2 10.1 4 4 0 003 15z', 'The forecast for each lot, beside the plan, with a reminder when rain falls on a spray day.', 200],
        'realign-by-anee' => ['Realign by Anee', 'M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 1.5M18.9 9A7.5 7.5 0 005.5 7.5', 'When a crop runs ahead or behind, Anee finds the stage it is truly in.', 120],
        'daily-farm-tasks' => ['Daily Farm Tasks', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Open the day, see each task on its lot and tick the work as it gets done.', 95],
        'offline-farm-app' => ['Offline Farm App', 'M8.1 16.1a5.5 5.5 0 017.8 0M4.9 12.9a10 10 0 0114.2 0M12 20h.01M3 3l18 18', 'Keep ticking tasks and writing notes where there is no signal, and it syncs later.', 215],
        'farm-tip-of-the-day' => ['Farm Tip of the Day', 'M9.7 17h4.6M12 3a6 6 0 00-3.6 10.8c.4.3.6.8.6 1.3V16h6v-.9c0-.5.2-1 .6-1.3A6 6 0 0012 3zm-2 17h4', 'One useful farm tip each morning for the stage your crop is in.', 45],
        'analyze-so-far' => ['Analyze So Far', 'M3 12h4l3-8 4 16 3-8h4', 'Halfway through, Anee reads the season so far: each lot, the risks ahead and what to do next.', 280],
        'crop-problem-guides' => ['Crop Problem Guides', 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3zM9 12l2 2 4-4', 'Free guides to the pests, diseases and weeds of Philippine crops, with what to do.', 0],
        'harvest-records' => ['Harvest Records', 'M5 9h14l-1.5 10a2 2 0 01-2 1.7h-7a2 2 0 01-2-1.7L5 9zm3 0V7a4 4 0 018 0v2', 'Yield, moisture, price and buyer for every lot, so next season plans from real numbers.', 35],
        'farm-contact-list' => ['Farm Contact List', 'M3 5a2 2 0 012-2h3.3a1 1 0 01.9.7l1.5 4.5a1 1 0 01-.5 1.2l-2.3 1.1a11 11 0 005.5 5.5l1.1-2.3a1 1 0 011.2-.5l4.5 1.5a1 1 0 01.7.9V19a2 2 0 01-2 2h-1C9.7 21 3 14.3 3 6V5z', 'Buyers, dealers, drivers and workers in one phonebook, with call and text buttons.', 185],
        'farm-documentation' => ['Farm Documentation', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z', 'The season\'s protocol, rules, receipts and files kept together.', 230],
        'farm-photo-gallery' => ['Farm Photo Gallery', 'M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'Every photo, clip and drawing of every season, with albums you make.', 300],
        'labor-report' => ['Labor Report', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'Worker days and labor cost for the season, added up from the board.', 25],
        'expenses-report' => ['Expenses Report', 'M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8c1.1 0 2.1.4 2.6 1M12 8V7m0 1v8m0 0v1m0-1c-1.1 0-2.1-.4-2.6-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Every peso the season cost in materials, services and labor.', 10],
        'profit-report' => ['Profit Report', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'What the harvest earned against everything the season spent, lot by lot.', 130],
        'anee-season-report' => ['Anee Season Report', 'M11.5 3.4l2.6 5.3 5.9.9-4.3 4.1 1 5.9-5.2-2.8-5.3 2.8 1-5.9L3 9.6l5.9-.9z', 'Anee reads your finished season and says what went well, what went wrong and what to change.', 50],
        'compare-reports' => ['Compare Reports', 'M12 3v18M5 7h14M5 7l-3 7a4 4 0 006 0L5 7zm14 0l-3 7a4 4 0 006 0l-3-7z', 'Two reports side by side, the difference line by line, and Anee on what changed.', 260],
        'view-as-protocol' => ['View as Protocol', 'M9 5h11M9 12h11M9 19h11M4.5 5h.01M4.5 12h.01M4.5 19h.01', 'Your best lot\'s finished season as a step by step recipe for the next one.', 150],
    ];

    /** A feature page as a product card: name, icon path, blurb, hue. */
    public static function feature(AsSitePage $p): array
    {
        [$name, $icon, $blurb, $hue] = self::FEATURES[$p->slug] ?? [
            self::shortTitle($p), 'M5 13l4 4L19 7', Str::limit(self::plain($p->excerpt), 110), 100,
        ];

        return ['name' => $name, 'icon' => $icon, 'blurb' => $blurb, 'hue' => $hue];
    }

    /** Where a page lives. */
    public static function url(string $section, ?string $slug = null): string
    {
        // One question lives at /question/{slug}; the list at /questions.
        if ($section === 'questions') {
            return url($slug ? '/question/' . $slug : '/questions');
        }

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

    /** Where a page that used to live under /problems lives now (or null). */
    public static function movedProblem(string $slug): ?AsSitePage
    {
        try {
            return AsSitePage::live()->whereIn('section', ['pests', 'diseases', 'weeds'])->where('slug', $slug)->first();
        } catch (\Throwable $e) {
            return null;
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
            $rows = AsSitePage::live()->whereIn('section', ['crops', 'pests', 'weeds', 'blog'])
                ->orderBy('sortOrder')->orderBy('title')
                ->get(['id', 'section', 'slug', 'lang', 'category', 'title', 'excerpt', 'heroImage']);
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach (['crops', 'pests', 'weeds', 'blog'] as $s) {
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

                return ['crops' => $pick('crops', 6), 'pests' => $pick('pests', 6), 'weeds' => $pick('weeds', 6), 'blog' => $pick('blog', 6)];
            });
        } catch (\Throwable $e) {
            return ['crops' => [], 'pests' => [], 'weeds' => [], 'blog' => []];
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
        // The Tech Blog's articles follow the pages.
        try {
            TechBlog::sync();
        } catch (\Throwable $e) {
            report($e);
        }

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
