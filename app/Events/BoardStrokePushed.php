<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A whiteboard event (a draw segment or a clear) for one schedule, pushed live
 * over Pusher to the schedule's private board channel. Sent immediately
 * (ShouldBroadcastNow) so collaborators see strokes without a poll.
 */
class BoardStrokePushed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $scheduleId, public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('schedule-board.' . $this->scheduleId)];
    }

    public function broadcastAs(): string
    {
        return 'stroke';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
