<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Something happened that one particular person should hear about now.
 *
 * The bell used to find out by asking every sixty seconds, which is a long
 * time to not know that the team is on a call waiting for you. This rides
 * the person's own channel, so the bell can ring the moment the thing
 * happens — and, when the app is not on screen, the device can too.
 */
class UserNotified implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $userId,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notify';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
