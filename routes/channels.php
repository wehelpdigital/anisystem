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

/**
 * One person's own line. Everything addressed to them personally — the bell,
 * a message from the team, a call starting — arrives here, and nobody else
 * can listen to it.
 */
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
});
