<?php

namespace App\Models;

/**
 * One unified documentation entry: a typed, rich-text note with any number
 * of attached files. Introduction and Critical Rule entries keep their
 * special placement on the printed worker documents; every other entry is a
 * custom-tagged reference document.
 */
class AsScheduleDocEntry extends BaseModel
{
    protected $table = 'as_schedule_doc_entries';

    public const TYPE_PROTOCOL = 'protocol';
    public const TYPE_INTRODUCTION = 'introduction';
    public const TYPE_CRITICAL_RULE = 'critical_rule';
    public const TYPE_MISCELLANEOUS = 'miscellaneous';
    public const TYPE_CUSTOM = 'custom';

    /** Built-in type => label. Custom entries take their label from the tag. */
    public const TYPE_LABELS = [
        self::TYPE_PROTOCOL => 'Protocol',
        self::TYPE_INTRODUCTION => 'Introduction',
        self::TYPE_CRITICAL_RULE => 'Critical Rule',
        self::TYPE_MISCELLANEOUS => 'Miscellaneous',
    ];

    protected $fillable = [
        'croppingScheduleId',
        'type',
        'tagId',
        'title',
        'content',
        'files',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'files' => 'array',
        'tagId' => 'integer',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function tag()
    {
        return $this->belongsTo(AsScheduleDocTag::class, 'tagId');
    }

    /** Human label for this entry's type/tag. */
    public function getTypeLabelAttribute(): string
    {
        if ($this->type === self::TYPE_CUSTOM) {
            return optional($this->tag)->name ?: 'Document';
        }

        return self::TYPE_LABELS[$this->type] ?? 'Document';
    }
}
