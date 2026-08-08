<?php

namespace App\Models;

/**
 * What the live whiteboard of a schedule is currently doing: how far it has
 * been archived, and which saved drawing (if any) it is editing.
 *
 * Without this the board has no memory of having been saved, so archiving on
 * every empty room would pile up duplicate drafts of a drawing already kept as
 * a note, and reopening a draft could not update it in place.
 */
class ScheduleBoardState extends BaseModel
{
    protected $table = 'as_schedule_board_state';

    protected $fillable = [
        'scheduleId',
        'savedUpToEventId',
        'currentDraftId',
        'currentNoteId',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'savedUpToEventId' => 'integer',
        'currentDraftId' => 'integer',
        'currentNoteId' => 'integer',
    ];

    public static function forSchedule(int $scheduleId): self
    {
        return static::firstOrCreate(['scheduleId' => $scheduleId], [
            'savedUpToEventId' => 0,
        ]);
    }

    /** The drawing on the canvas is a fresh one, not a reopened save. */
    public function isUntitledDrawing(): bool
    {
        return ! $this->currentDraftId && ! $this->currentNoteId;
    }
}
