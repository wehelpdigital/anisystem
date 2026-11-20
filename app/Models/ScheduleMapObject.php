<?php

namespace App\Models;

/**
 * One shape on the Collab Room map. Points are [[lat,lng],...]; `kind` decides
 * how the client renders and measures them. Persistent on purpose — the map is
 * where the team plans against real ground, not a scratchpad.
 */
class ScheduleMapObject extends BaseModel
{
    protected $table = 'as_schedule_map_objects';

    protected $fillable = [
        'scheduleId', 'userId', 'kind', 'color', 'width', 'font', 'fill', 'points', 'label', 'name', 'hidden', 'locked', 'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'userId' => 'integer',
        'width' => 'integer',
        'fill' => 'integer',
        'hidden' => 'boolean',
        'locked' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function shaped(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'color' => $this->color,
            'width' => (int) $this->width,
            // Stroke thickness for every kind but text, where it is the type
            // size — and `font` is what says which of the two it is.
            'font' => $this->font,
            // How solid a closed shape is, 0 to 100; null is the light fill
            // every shape had before 2026-11-09.
            'fill' => $this->fill,
            'points' => json_decode((string) $this->points, true) ?: [],
            'label' => $this->label,
            // Its layer (2026-11-20): what the Layers list calls it (null = its kind and number), whether it is off
            // the screen, and whether it is locked against picking up, moving, reshaping and erasing.
            'name' => $this->name,
            'hidden' => (bool) $this->hidden,
            'locked' => (bool) $this->locked,
            'userId' => (int) $this->userId,
        ];
    }
}
