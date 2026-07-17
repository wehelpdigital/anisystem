<?php

namespace App\Models;

class AsScheduleActivityVersion extends BaseModel
{
    protected $table = 'as_schedule_activity_versions';

    protected $fillable = [
        'croppingScheduleId',
        'versionName',
        'description',
        'globalActivityNote',
        'parentVersionId',
        'isOriginal',
        'isActive',
        'versionOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'isOriginal'   => 'boolean',
        'isActive'     => 'boolean',
        'versionOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeForSchedule($q, $scheduleId)
    {
        return $q->where('croppingScheduleId', $scheduleId);
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parentVersionId');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parentVersionId')->where('deleteStatus', 1);
    }

    public function activities()
    {
        return $this->hasMany(AsScheduleActivity::class, 'versionId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 0)
            ->orderBy('targetDate', 'asc');
    }

    public function drafts()
    {
        return $this->hasMany(AsScheduleActivity::class, 'versionId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 1)
            ->orderBy('updated_at', 'desc');
    }

    public function dateNotes()
    {
        return $this->hasMany(AsScheduleDateNote::class, 'versionId')
            ->where('as_schedule_date_notes.deleteStatus', 1)
            ->orderBy('noteDate', 'asc');
    }
}
