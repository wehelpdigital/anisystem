<?php

namespace App\Http\Controllers\Manager;

use App\Events\ScheduleCallSignal;
use App\Models\AsCroppingSchedule;
use App\Support\ScheduleTeam;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * LiveKit audio/video calls for the Collab Room. Issues short-lived join tokens
 * (server derives the room so members can't join arbitrary rooms) and relays
 * ring/hang-up signals over the shared Pusher board channel. A "group" call is
 * one room per schedule; a "worker" call is a deterministic 1:1 room.
 */
class ScheduleCallController extends BaseScheduleController
{
    /** Issue a LiveKit join token for the group or a 1:1 worker room. */
    public function token(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId') ?? $request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        [$room, $ok] = $this->resolveRoom($schedule, $meId, $request);
        if (! $ok) {
            return $this->jsonFail('Invalid call target.', 422);
        }

        $key = config('services.livekit.key');
        $secret = config('services.livekit.secret');
        $url = config('services.livekit.url');
        if (! $key || ! $secret || ! $url) {
            return $this->jsonFail('Calls are not set up yet — a LiveKit key/secret is missing.', 503);
        }

        $me = Auth::user();
        $name = $me->full_name ?: 'Member';
        $now = time();
        $token = JWT::encode([
            'iss' => $key,
            'sub' => 'u' . $meId,
            'name' => $name,
            'nbf' => $now - 5,
            'exp' => $now + 3600 * 6,
            'video' => [
                'room' => $room,
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
            ],
        ], $secret, 'HS256');

        return $this->jsonOk('Token issued.', ['data' => [
            'url' => $url,
            'token' => $token,
            'room' => $room,
            'identity' => 'u' . $meId,
            'name' => $name,
        ]]);
    }

    /** Ring the team (group) or a specific worker (1:1) about a starting call. */
    public function ring(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId') ?? $request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        [$room, $ok, $kind, $with] = $this->resolveRoom($schedule, $meId, $request, true);
        if (! $ok) {
            return $this->jsonFail('Invalid call target.', 422);
        }

        $me = Auth::user();
        $this->emit($schedule->id, 'call.ring', [
            'kind' => $kind,
            'room' => $room,
            'fromUserId' => $meId,
            'fromName' => $me->full_name ?: 'A teammate',
            'toUserId' => $kind === 'worker' ? $with : null,
            'title' => $kind === 'group' ? 'Team call' : (($me->full_name ?: 'A teammate') . ' is calling'),
        ]);

        // The ring only reaches whoever has the app open. The bell reaches
        // the rest — and on a phone that has the app installed, the phone
        // itself. A call is the one thing worth interrupting someone for,
        // so it is not deduped.
        $notes = app(\App\Services\NotificationService::class);
        $who = $me->firstName ?: 'A teammate';
        // Through the entry route, not straight at the room: a member who
        // also farms land of their own has their session pointed at THEIR
        // farm, so the direct link 404s for exactly the people most likely
        // to be called. The entry route puts them in the right farm first.
        $url = route('sm.enter', ['schedule' => $schedule->id, 'to' => 'sm.collab']);
        $targets = $kind === 'worker' && $with
            ? [(int) $with]
            : ScheduleTeam::memberIds($schedule);
        foreach ($targets as $memberId) {
            if ((int) $memberId === $meId) {
                continue;
            }
            $notes->notify(
                (int) $memberId,
                'team-call',
                $kind === 'group' ? $who . ' started a team call' : $who . ' is calling you',
                'Tap to join.',
                $url,
                $meId,
                (int) $schedule->id,
            );
        }

        return $this->jsonOk('Ringing.');
    }

    /** Tell the other side a call was ended/cancelled. */
    public function end(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId') ?? $request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        [$room, $ok] = $this->resolveRoom($schedule, $meId, $request);
        if ($ok) {
            $this->emit($schedule->id, 'call.ended', ['room' => $room, 'byUserId' => $meId]);
        }

        return $this->jsonOk('Ended.');
    }

    /**
     * Force-mute a participant's microphone (moderator action). Only the
     * schedule owner may do this; the mute is applied at the LiveKit server via
     * the RoomService API, so the target actually goes muted (they can unmute
     * themselves again — like a host mute, not a ban).
     */
    public function mute(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId') ?? $request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }
        if ($meId !== (int) $schedule->anisystemUserId) {
            return $this->jsonFail('Only the schedule owner can mute participants.', 403);
        }

        $validator = Validator::make($request->all(), [
            'room' => 'required|string|max:120',
            'identity' => 'required|string|max:60',
            'trackSid' => 'required|string|max:80',
            'muted' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Invalid mute request.', 422);
        }

        // The room must belong to this schedule — never touch an arbitrary room.
        $room = (string) $request->input('room');
        if (! str_starts_with($room, 'sched-' . $schedule->id . '-')) {
            return $this->jsonFail('Invalid room.', 422);
        }

        $key = config('services.livekit.key');
        $secret = config('services.livekit.secret');
        $url = config('services.livekit.url');
        if (! $key || ! $secret || ! $url) {
            return $this->jsonFail('Calls are not set up yet.', 503);
        }

        // Short-lived RoomService admin token scoped to this room.
        $now = time();
        $admin = JWT::encode([
            'iss' => $key,
            'sub' => $key,
            'nbf' => $now - 5,
            'exp' => $now + 60,
            'video' => ['roomAdmin' => true, 'room' => $room],
        ], $secret, 'HS256');

        // The RoomService HTTP (Twirp) API lives on the same host as the wss URL.
        $httpBase = preg_replace('#^wss?://#', 'https://', rtrim($url, '/'));
        try {
            $resp = Http::withToken($admin)->asJson()->timeout(8)->post(
                $httpBase . '/twirp/livekit.RoomService/MutePublishedTrack',
                [
                    'room' => $room,
                    'identity' => (string) $request->input('identity'),
                    'track_sid' => (string) $request->input('trackSid'),
                    'muted' => $request->boolean('muted', true),
                ]
            );
        } catch (\Throwable $e) {
            return $this->jsonFail('Could not reach the call server.', 502);
        }
        if (! $resp->successful()) {
            return $this->jsonFail('The call server rejected the request.', 502);
        }

        return $this->jsonOk('Muted.');
    }

    /**
     * Derive the room server-side so a member can never join an arbitrary room.
     * group  → sched-{id}-group ; worker → sched-{id}-dm-{minId}-{maxId}.
     *
     * @return array{0:string,1:bool,2:string,3:?int}
     */
    private function resolveRoom(AsCroppingSchedule $schedule, int $meId, Request $request, bool $withMeta = false): array
    {
        $kind = $request->input('kind') === 'worker' ? 'worker' : 'group';

        if ($kind === 'group') {
            return ['sched-' . $schedule->id . '-group', true, 'group', null];
        }

        $with = (int) $request->input('withUserId');
        if ($with === $meId || ! ScheduleTeam::canAccess($schedule, $with)) {
            return ['', false, 'worker', null];
        }
        $a = min($meId, $with);
        $b = max($meId, $with);

        return ['sched-' . $schedule->id . '-dm-' . $a . '-' . $b, true, 'worker', $with];
    }

    /** Best-effort Pusher signal (never breaks the request). */
    private function emit(int $scheduleId, string $name, array $payload): void
    {
        $driver = config('broadcasting.default');
        $ready = in_array($driver, ['pusher', 'reverb', 'ably'], true) && filled(config("broadcasting.connections.$driver.key"));
        if (! $ready) {
            return;
        }
        try {
            broadcast(new ScheduleCallSignal($scheduleId, $name, $payload));
        } catch (\Throwable $e) {
            // swallow
        }
    }
}
