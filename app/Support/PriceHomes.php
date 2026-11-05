<?php

namespace App\Support;

use App\Models\AsSitePage;
use Illuminate\Support\Collection;

/**
 * One permanent page for each price farmers search for (2026-11-05, the SEO audit): 20 dated news roundups were chasing
 * the same words ("rice palay price philippines" 11 times), so Google had no single page to rank, and a post dated last
 * week is the wrong answer to "palay price today" anyway.
 *
 * The two pages below own those searches. They show the newest price records and news by themselves, every dated roundup
 * links up to them, and NewsRoundup never takes one of their phrases for its own focus.
 */
final class PriceHomes
{
    /** Slug in the blog section => what the page prices. */
    public const PAGES = ['palay-price-philippines' => 'palay', 'fertilizer-price-philippines' => 'fertilizer'];

    /** A search phrase about one of these prices, which only its permanent page may aim at. */
    public static function ownsPhrase(string $phrase): bool
    {
        return preg_match('/\b(palay|rice|fertili[sz]er|urea)\b.*\bprices?\b|\bprices?\b.*\b(palay|rice|fertili[sz]er|urea)\b/i', $phrase) === 1;
    }

    /** What this page prices, or null when it is not one of the permanent pages. */
    public static function topicOf(object $page): ?string
    {
        return ($page->section ?? null) === 'blog' ? (self::PAGES[$page->slug ?? ''] ?? null) : null;
    }

    public static function latestNews(int $count = 5): Collection
    {
        return rescue(fn () => AsSitePage::live()->where('section', 'blog')->where('kind', 'roundup')
            ->orderByDesc('publishedAt')->limit($count)->get(['section', 'slug', 'title', 'publishedAt']), collect(), false);
    }

    /** The reports of the last 75 days, country wide, from the app's own farmgate price records. */
    public static function palayNow(): ?array
    {
        return rescue(fn () => MarketBook::palayNow(null), null, false);
    }

    /** New news on the page means the page is new: the sitemap's date says so. */
    public static function freshen(): void
    {
        rescue(fn () => AsSitePage::query()->where('section', 'blog')->whereIn('slug', array_keys(self::PAGES))->update(['updated_at' => now()]), null, false);
    }
}
