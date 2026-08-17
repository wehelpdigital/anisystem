<?php

namespace App\Models;

/** The one photo a schedule's Collab Room is currently drawing over. */
class SchedulePhotoBoard extends BaseModel
{
    protected $table = 'as_schedule_photo_boards';

    protected $fillable = ['scheduleId', 'imagePath', 'setBy'];

    protected $casts = [
        'scheduleId' => 'integer',
        'setBy' => 'integer',
    ];

    public static function forSchedule(int $scheduleId): self
    {
        return static::firstOrCreate(['scheduleId' => $scheduleId]);
    }
}
