<?php

namespace App\Models;

class AsScheduleTagLink extends BaseModel
{
    protected $table = 'as_schedule_tag_links';

    /**
     * The kinds a link can point at. One string here per add-form that
     * carries the tag picker; the Tags module resolves each kind to a
     * title and a door in TagController::describe().
     */
    public const KINDS = [
        'activity',   // as_schedule_activities
        'daynote',    // as_schedule_date_notes (the day-book note)
        'note',       // as_schedule_notes (Notes module, Draw saves, map notes)
        'inote',      // as_inline_notes (strips between cards: day drawings, captures)
        'expense',    // as_schedule_day_expenses
        'income',     // as_schedule_day_incomes
        'move',       // as_inventory_moves
        'item',       // as_inventory_items
        'map',        // as_schedule_map_saves
    ];

    protected $fillable = [
        'tagId',
        'kind',
        'refId',
    ];

    protected $casts = [
        'tagId' => 'integer',
        'refId' => 'integer',
    ];

    public function tag()
    {
        return $this->belongsTo(AsScheduleTag::class, 'tagId');
    }
}
