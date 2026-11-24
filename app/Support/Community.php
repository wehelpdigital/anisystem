<?php

namespace App\Support;

use App\Models\AsSiteSetting;

/**
 * Whether the Community, the social part of anee.io, is open (2026-11-24, the owner: "let's hide the social media part
 * inside anee for now, and change its bottom navigation to Shop which when opened is coming soon").
 *
 * Closed means: the News Feed, Discussions, Members, Cofarmers, Profiles, Rankings, saved posts, shared plans, the
 * messenger dock and the public post links are out of sight. The Tech Blog lives under /app/community/blog and stays
 * open. The data and the endpoints stay as they are, so reopening loses nothing: set `community.open` to '1' on the
 * site settings shelf (as_site_settings, the mother writes it too).
 */
final class Community
{
    public static function open(): bool
    {
        return AsSiteSetting::yes('community.open', false);
    }

    /**
     * A plan's lines (config/tiers features, excludes, sheet) as a reader should see them today. While the Community is
     * closed, a plan must not sell "Full community access": a line that only speaks of the Community goes, and
     * "Community, the farm news and offline mode" keeps the rest ("The farm news and offline mode"). Open, nothing
     * changes. The gates read their own keys (communityVideo, discussionCreate), never these words.
     */
    public static function planLines(array $lines): array
    {
        if (self::open()) {
            return $lines;
        }
        $out = [];
        foreach ($lines as $line) {
            if (stripos((string) $line, 'communit') === false) {
                $out[] = $line;
                continue;
            }
            $rest = preg_replace('/^community,\s*/i', '', (string) $line, 1, $cut);
            if ($cut && stripos($rest, 'communit') === false) {
                $out[] = ucfirst($rest);
            }
        }

        return $out;
    }

    /** Every plan in a config('tiers') list, its lines passed through planLines(). */
    public static function plans(array $tiers): array
    {
        foreach ($tiers as $k => $t) {
            foreach (['features', 'excludes', 'sheet'] as $list) {
                if (isset($t[$list]) && is_array($t[$list])) {
                    $tiers[$k][$list] = self::planLines($t[$list]);
                }
            }
        }

        return $tiers;
    }

    /** A page of the social Community (never the Tech Blog), by its path. */
    public static function isSocialPath(string $path): bool
    {
        $path = trim($path, '/');
        if ($path === 'app/community/blog' || str_starts_with($path, 'app/community/blog/')) {
            return false;
        }

        return $path === 'app/community' || str_starts_with($path, 'app/community/') || str_starts_with($path, 'app/community-')
            || str_starts_with($path, 'pw/');
    }
}
