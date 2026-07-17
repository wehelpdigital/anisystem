<?php

namespace App\Models;

class AsScheduleActivityItem extends BaseModel
{
    protected $table = 'as_schedule_activity_items';

    protected $fillable = [
        'activityId',
        'itemType',
        'materialId',
        'serviceId',
        'quantity',
        'unitOfMeasure',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'deleteStatus' => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(AsScheduleActivity::class, 'activityId');
    }

    public function material()
    {
        return $this->belongsTo(AsScheduleMaterial::class, 'materialId');
    }

    public function service()
    {
        return $this->belongsTo(AsScheduleService::class, 'serviceId');
    }
}
