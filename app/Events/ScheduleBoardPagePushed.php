<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A whiteboard page change (a new page was added, or a page's orientation
 * flipped) for one schedule, pushed live so collaborators' page strip stays in
 * sync without a reload.
 */
class ScheduleBoardPagePushed implements ShouldBroadcastNow
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
        return 'board.page';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
