<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A message has landed in a schedule's team chat.
 *
 * Only the id travels, not the message: every listener already knows how to
 * ask for "everything after N", the reply is authorised per person, and a
 * body on the wire would be a second copy of something the poll can fetch
 * correctly. This is a nudge, not a delivery.
 */
class ScheduleChatPushed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $scheduleId,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('schedule-board.' . $this->scheduleId)];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
