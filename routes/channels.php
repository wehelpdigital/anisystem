<?php

use App\Models\AsCroppingSchedule;
use App\Models\User;
use App\Support\ScheduleTeam;
use Illuminate\Support\Facades\Broadcast;

/**
 * A schedule's private whiteboard channel. Only members of that schedule's team
 * (the owner and their active worker sub-members) may subscribe.
 */
Broadcast::channel('schedule-board.{scheduleId}', function (User $user, int $scheduleId) {
    $schedule = AsCroppingSchedule::find($scheduleId);

    return $schedule !== null && ScheduleTeam::canAccess($schedule, (int) $user->id);
});
