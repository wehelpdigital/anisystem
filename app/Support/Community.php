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
