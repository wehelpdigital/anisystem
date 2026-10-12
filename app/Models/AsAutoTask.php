<?php

namespace App\Models;

/** A task on an Anee Auto calendar, held to a day by an anchor and a window. */
class AsAutoTask extends BaseModel
{
    protected $table = 'as_auto_tasks';

    protected $fillable = ['autoId', 'lotId', 'kind', 'title', 'summary', 'why', 'detail', 'anchor', 'offsetDays', 'windowBefore', 'windowAfter', 'notBefore',
        'plannedDate', 'firstDate', 'timeOfDay', 'critical', 'weather', 'crew', 'workerIds', 'planNote', 'status', 'doneDate', 'doneAt', 'doneNote', 'doneData',
        'movedCount', 'origin', 'sortOrder', 'deleteStatus'];

    protected $casts = [
        'detail' => 'array', 'workerIds' => 'array', 'planNote' => 'array', 'doneData' => 'array',
        'notBefore' => 'date:Y-m-d', 'plannedDate' => 'date:Y-m-d', 'firstDate' => 'date:Y-m-d', 'doneDate' => 'date:Y-m-d', 'doneAt' => 'datetime',
        'critical' => 'integer', 'offsetDays' => 'integer', 'windowBefore' => 'integer', 'windowAfter' => 'integer', 'crew' => 'integer', 'movedCount' => 'integer',
    ];
}
