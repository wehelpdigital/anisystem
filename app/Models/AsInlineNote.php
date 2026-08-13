<?php

namespace App\Models;

/**
 * A positioned sticky note that lives among a day's activity cards. Multiple
 * per day; `sortKey` places it in the same ordering space as the activities'
 * sequenceOrder so it can be dragged between cards.
 */
class AsInlineNote extends BaseModel
{
    protected $table = 'as_inline_notes';

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'noteDate',
        'sortKey',
        'title',
        'content',
        'media',
        'deleteStatus',
    ];

    protected $casts = [
        'noteDate' => 'date:Y-m-d',
        'sortKey' => 'integer',
        'media' => 'array',
        'deleteStatus' => 'integer',
    ];

    public function scopeForSchedule($query, int $scheduleId)
    {
        return $query->where('croppingScheduleId', $scheduleId);
    }

    public function scopeForVersion($query, ?int $versionId)
    {
        return $query->where('versionId', $versionId);
    }
}
