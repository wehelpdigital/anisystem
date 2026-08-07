<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Call signalling for the Collab Room (LiveKit media, Pusher signalling). `$name`
 * is 'call.ring' (someone started/invited a call) or 'call.ended'. Pushed over
 * the shared private board channel so team members get the incoming-call prompt.
 */
class ScheduleCallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $scheduleId, public string $name, public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('schedule-board.' . $this->scheduleId)];
    }

    public function broadcastAs(): string
    {
        return $this->name;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
