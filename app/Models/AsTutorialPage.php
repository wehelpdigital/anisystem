<?php

namespace App\Models;

/**
 * One "How to use" page: a module, a device, and the blocks that explain it.
 *
 * Written by the page builder in the mother app, read here. Both sides agree
 * on the block shapes listed in {@see \App\Support\TutorialBlocks}.
 */
class AsTutorialPage extends BaseModel
{
    protected $table = 'as_tutorial_pages';

    /** The devices a page can be written for, widest first. */
    public const DEVICES = ['desktop', 'tablet', 'mobile'];

    /** Every module that can carry a help page, with the name people call it. */
    public const MODULES = [
        'activities' => 'Activities',
        'notes' => 'Notes',
        'maps' => 'Maps',
        'draw' => 'Draw',
        'growth' => 'Growth Stages',
        'gallery' => 'Gallery',
        'weather' => 'Weather',
        'lots' => 'Lots',
        'workers' => 'Workers',
        'documentation' => 'Documentation',
        'post-harvest' => 'Post-harvest',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'collab' => 'Collab Room',
        'ai' => 'AI Technician',
        'hub' => 'Schedule Hub',
        'schedules' => 'Schedules',

        /* Outside the cropping schedule.
         *
         * The question mark started life in the schedule modules and stayed
         * there, which left the three screens people actually live on — the
         * home board, Community and Account — with nothing behind it and no
         * way to write anything. These are those, plus the two schedule
         * modules that shipped a help key and were never added to this list:
         * their pages 404 rather than opening, which is the same as having
         * no help at all except that it looks broken.
         *
         * Community is not one screen and does not get one page. Discussions,
         * Members, Co-farmers and the rest are separate places with separate
         * rules, and a single "how to use Community" would have to be so
         * general as to say nothing. */
        'inventory' => 'Inventory',
        'media' => 'Media',
        'home' => 'Home',
        'account' => 'Account',
        'community' => 'Community — the feed',
        'community-discussions' => 'Community — Discussions',
        'community-members' => 'Community — Members',
        'community-cofarmers' => 'Community — Co-farmers',
        'community-blog' => 'Community — Blog',
        'community-ranking' => 'Community — Rankings',
        'community-saved' => 'Community — Saved',
        'community-messages' => 'Community — Messages',
        'community-profile' => 'Community — Your profile',
    ];

    protected $fillable = [
        'moduleKey',
        'device',
        'title',
        'summary',
        'blocks',
        'updatedByUserId',
        'deleteStatus',
    ];

    protected $casts = [
        'blocks' => 'array',
        'deleteStatus' => 'integer',
    ];

    public static function label(string $moduleKey): string
    {
        return self::MODULES[$moduleKey] ?? ucfirst(str_replace('-', ' ', $moduleKey));
    }
}
