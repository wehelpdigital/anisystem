<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A shared group-AI event (question or answer) for one schedule, pushed live
 * over the existing private board channel so the whole team sees it. `$name` is
 * the broadcast event name — 'ai.question' or 'ai.answer'.
 */
class ScheduleAiMessagePushed implements ShouldBroadcastNow
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
