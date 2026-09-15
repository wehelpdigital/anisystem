<?php

namespace App\Events\Concerns;

use App\Support\CollabPresence;
use Illuminate\Support\Facades\Auth;

/**
 * An event on a schedule's private channel goes out only while somebody
 * other than its sender is in that schedule's Collab Room.
 *
 * The channel is only ever listened to from inside the room (every other
 * screen polls), so with nobody else there the message would be paid for
 * and heard by no one. The sender is whoever is signed in on this request
 * -- every one of these events is dispatched inside the request that made
 * the change, and the sender's own screen already shows it.
 */
trait HeardOnlyInTheRoom
{
    public function broadcastWhen(): bool
    {
        return CollabPresence::othersHere((int) $this->scheduleId, (int) Auth::id());
    }
}
