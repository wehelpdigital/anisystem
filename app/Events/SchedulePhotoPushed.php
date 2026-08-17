<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something happened on the Collab Room's shared photo — a stroke, an undo,
 * a clear, or the photo itself changing. Rides the schedule's existing board
 * channel, so no new channel authorisation is needed.
 */
class SchedulePhotoPushed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $scheduleId, public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('schedule-board.' . $this->scheduleId)];
    }

    public function broadcastAs(): string
    {
        return 'photo.event';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
