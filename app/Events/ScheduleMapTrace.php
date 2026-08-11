<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A member's drawing-in-progress on the Collab Room map: the half-drawn line
 * as it moves under their finger. Broadcast-only, like the GPS beacons — the
 * finished shape is what gets stored, the gesture never is.
 */
class ScheduleMapTrace implements ShouldBroadcastNow
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
        return 'map.trace';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
