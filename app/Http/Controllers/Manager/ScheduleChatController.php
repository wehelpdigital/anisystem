<?php

namespace App\Http\Controllers\Manager;

use App\Models\ScheduleChatMessage;
use App\Support\MediaOptimizer;
use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * The per-schedule team GROUP chat (owner + worker sub-members). Private 1:1
 * worker messages are NOT handled here — those reuse the community DM endpoints
 * so the history stays shared with the community. Group messages live in
 * `as_schedule_messages`, scoped by schedule.
 */
class ScheduleChatController extends BaseScheduleController
{
    /** History + live poll. `?after=<lastId>` returns only newer messages. */
    public function messages(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $after = (int) $request->query('after', 0);
        $query = ScheduleChatMessage::active()->where('scheduleId', $schedule->id)->with('author');

        if ($after > 0) {
            $rows = $query->where('id', '>', $after)->orderBy('id')->limit(100)->get();
        } else {
            // Newest 60, returned oldest-first so the thread reads top→bottom.
            $rows = $query->orderByDesc('id')->limit(60)->get()->sortBy('id')->values();
        }

        $messages = $rows->map(fn ($m) => $this->present($m, $meId))->all();

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages,
                'maxId' => $rows->max('id') ?: $after,
            ],
        ]);
    }

    /** Team roster with live presence (online = seen in the last 5 minutes). */
    public function members(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $ownerId = (int) $schedule->anisystemUserId;
        $members = ScheduleTeam::members($schedule)->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->full_name ?: 'Member',
            'avatar' => $u->avatarPath ? \App\Support\MediaStore::url($u->avatarPath) : null,
            'initials' => $u->initials,
            'online' => $u->isOnline(),
            'isMe' => $u->id === $meId,
            'isOwner' => $u->id === $ownerId,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'members' => $members,
                'online' => $members->where('online', true)->count(),
                'total' => $members->count(),
            ],
        ]);
    }

    /** Post a group message. View-only workers can still chat (not a schedule edit). */
    public function send(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'nullable|string|max:4000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $body = trim((string) $request->input('body'));
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = MediaOptimizer::storeImageAsWebp($request->file('image'), 'schedule-chat/' . $schedule->id);
        }
        if ($body === '' && ! $imagePath) {
            return $this->jsonFail('Write a message or attach a photo.', 422);
        }

        $message = ScheduleChatMessage::create([
            'scheduleId' => $schedule->id,
            'userId' => $meId,
            'body' => $body !== '' ? $body : null,
            'imagePath' => $imagePath,
            'deleteStatus' => 1,
        ]);
        $message->setRelation('author', Auth::user());

        return response()->json([
            'success' => true,
            'data' => ['message' => $this->present($message, $meId)],
        ]);
    }

    /** Shape a message for the client. */
    private function present(ScheduleChatMessage $m, int $meId): array
    {
        $author = $m->author;

        return [
            'id' => $m->id,
            'body' => $m->body,
            'image' => $m->imagePath ? \App\Support\MediaStore::url($m->imagePath) : null,
            'mine' => (int) $m->userId === $meId,
            'userId' => (int) $m->userId,
            'name' => $author?->full_name ?: 'Member',
            'avatar' => $author && $author->avatarPath ? \App\Support\MediaStore::url($author->avatarPath) : null,
            'initials' => $author?->initials ?: '·',
            'at' => optional($m->created_at)->format('M j, g:i A'),
        ];
    }
}
