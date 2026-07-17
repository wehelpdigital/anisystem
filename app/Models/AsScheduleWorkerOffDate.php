<?php

namespace App\Models;

class AsScheduleWorkerOffDate extends BaseModel
{
    protected $table = 'as_schedule_worker_off_dates';

    protected $fillable = [
        'workerId',
        'offDate',
        'reason',
    ];

    protected $casts = [
        'offDate' => 'date:Y-m-d',
    ];

    public function worker()
    {
        return $this->belongsTo(AsScheduleWorker::class, 'workerId');
    }
}
