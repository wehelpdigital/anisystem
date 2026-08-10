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
        'scheduleId', 'userId', 'kind', 'color', 'width', 'points', 'label', 'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'userId' => 'integer',
        'width' => 'integer',
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
            'points' => json_decode((string) $this->points, true) ?: [],
            'label' => $this->label,
            'userId' => (int) $this->userId,
        ];
    }
}
