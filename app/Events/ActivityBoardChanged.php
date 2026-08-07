<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A single change to a schedule's activities board (create/move/delete/note/…),
 * pushed live over the existing private board channel so every Collab Room
 * member's board updates in place — no reload. Payload:
 *   { type, actorUserId, versionId, data }
 */
class ActivityBoardChanged implements ShouldBroadcastNow
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
        return 'activity';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
