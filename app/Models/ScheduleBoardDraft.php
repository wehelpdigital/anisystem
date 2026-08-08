<?php

namespace App\Models;

/**
 * A past whiteboard drawing, archived when the room emptied.
 *
 * `payload` holds the strokes themselves (pages + events), not just the
 * thumbnail, so a draft can be reopened onto the live board and carried on.
 * A drawing that was already exported to the notebook carries `savedNoteId`
 * and is not archived again — it is kept as a note instead.
 */
class ScheduleBoardDraft extends BaseModel
{
    protected $table = 'as_schedule_board_drafts';

    protected $fillable = [
        'scheduleId',
        'title',
        'pageCount',
        'thumbPath',
        'payload',
        'savedNoteId',
        'archivedByUserId',
        'archivedAt',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'pageCount' => 'integer',
        'savedNoteId' => 'integer',
        'archivedByUserId' => 'integer',
        'archivedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /** The archived strokes, page by page. */
    public function strokes(): array
    {
        $decoded = json_decode((string) $this->payload, true);

        return is_array($decoded) ? $decoded : ['pages' => [], 'events' => []];
    }

    public function thumbUrl(): ?string
    {
        return $this->thumbPath ? asset('storage/' . ltrim($this->thumbPath, '/')) : null;
    }
}
