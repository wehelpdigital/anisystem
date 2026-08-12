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
        'media' => 'Media Box',
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
