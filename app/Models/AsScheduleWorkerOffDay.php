<?php

namespace App\Models;

class AsScheduleWorkerOffDay extends BaseModel
{
    protected $table = 'as_schedule_worker_off_days';

    protected $fillable = [
        'workerId',
        'dayOfWeek',
    ];

    protected $casts = [
        'dayOfWeek' => 'integer',
    ];

    public function worker()
    {
        return $this->belongsTo(AsScheduleWorker::class, 'workerId');
    }
}
