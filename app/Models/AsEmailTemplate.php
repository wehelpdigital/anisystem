<?php

namespace App\Models;

/**
 * An email layout written in the mother app and sent from here.
 *
 * `bodyHtml` is the finished HTML with {{tags}} still in it; `blocks` is the
 * editable form behind it, which only the builder over there touches.
 */
class AsEmailTemplate extends BaseModel
{
    protected $table = 'as_email_templates';

    public const KEY_DAILY_DIGEST = 'daily_digest';

    protected $fillable = [
        'groupKey', 'templateKey', 'templateName', 'subject',
        'bodyHtml', 'blocks', 'availableTags', 'isActive', 'deleteStatus',
    ];

    protected $casts = [
        'blocks' => 'array',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    /** The live layout for a key, or null when nobody has written one. */
    public static function forKey(string $key): ?self
    {
        return static::active()->where('templateKey', $key)->where('isActive', 1)->first();
    }
}
