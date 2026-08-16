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
            // One clip, recording or file per message, and 50 MB is the
            // ceiling — these travel over a farm's phone signal.
            'clip' => 'nullable|file|max:51200',
            'kind' => 'nullable|in:video,audio,file',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $body = trim((string) $request->input('body'));
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = MediaOptimizer::storeImageAsWebp($request->file('image'), 'schedule-chat/' . $schedule->id);
        }

        $attachments = [];
        if ($request->hasFile('clip')) {
            try {
                $attachments[] = $this->keepAttachment($request->file('clip'), (string) $request->input('kind'), (int) $schedule->id);
            } catch (\Throwable $e) {
                return $this->jsonFail($e->getMessage() ?: 'Could not keep that attachment.', 422);
            }
        }

        if ($body === '' && ! $imagePath && ! $attachments) {
            return $this->jsonFail('Write a message, or attach something.', 422);
        }

        $message = ScheduleChatMessage::create([
            'scheduleId' => $schedule->id,
            'userId' => $meId,
            'body' => $body !== '' ? $body : null,
            'imagePath' => $imagePath,
            'attachments' => $attachments ?: null,
            'deleteStatus' => 1,
        ]);
        $message->setRelation('author', Auth::user());

        $this->announce($schedule, $message, $meId);

        return response()->json([
            'success' => true,
            'data' => ['message' => $this->present($message, $meId)],
        ]);
    }

    /**
     * Keep a clip, a recording or a file — compressed where compressing it
     * means anything.
     *
     * A phone's own video is enormous and a farm's signal is not, so video
     * goes through the same ffmpeg pass the notes use. Audio and ordinary
     * files are stored as they are: re-encoding a voice note gains nothing
     * worth the wait.
     */
    private function keepAttachment(\Illuminate\Http\UploadedFile $file, string $kind, int $scheduleId): array
    {
        $mime = (string) $file->getMimeType();
        $kind = $kind ?: (str_starts_with($mime, 'video/') ? 'video'
            : (str_starts_with($mime, 'audio/') ? 'audio' : 'file'));

        if ($kind === 'video') {
            $made = \App\Support\VideoOptimizer::compress($file, 'schedule-chat/' . $scheduleId);
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $path = \App\Support\MediaStore::putBinary($disk->get($made['video']), 'schedule-chat', 'mp4', $scheduleId);
            $poster = null;
            if (! empty($made['poster'])) {
                $poster = \App\Support\MediaStore::putBinary($disk->get($made['poster']), 'schedule-chat', 'webp', $scheduleId);
                $disk->delete($made['poster']);
            }
            $disk->delete($made['video']);

            return array_filter([
                'type' => 'video',
                'path' => $path,
                'poster' => $poster,
                'name' => $file->getClientOriginalName(),
                'mime' => 'video/mp4',
            ], fn ($v) => $v !== null);
        }

        $path = \App\Support\MediaStore::putFile($file, 'schedule-chat', $scheduleId);
        if ($path === null) {
            throw new \RuntimeException('Could not keep that file.');
        }

        return [
            'type' => $kind === 'audio' ? 'audio' : 'file',
            'path' => $path,
            'name' => $file->getClientOriginalName() ?: 'attachment',
            'size' => (int) $file->getSize(),
            'mime' => $mime,
        ];
    }

    /**
     * Put the message on the team's channel, and in everyone else's bell.
     *
     * The thread still polls, so a lost broadcast costs seconds. The bell is
     * what reaches somebody who is not looking at the chat at all — deduped
     * to one an hour per schedule, because a busy morning should not leave
     * forty identical rows in the notification list.
     */
    private function announce($schedule, ScheduleChatMessage $message, int $meId): void
    {
        $driver = config('broadcasting.default');
        $ready = in_array($driver, ['pusher', 'reverb', 'ably'], true)
            && filled(config("broadcasting.connections.$driver.key"));
        if ($ready) {
            try {
                broadcast(new \App\Events\ScheduleChatPushed((int) $schedule->id, [
                    'id' => (int) $message->id,
                    'userId' => $meId,
                ]));
            } catch (\Throwable $e) {
                // The poll will pick it up.
            }
        }

        $who = Auth::user()?->firstName ?: 'A teammate';
        $said = trim((string) $message->body);
        if ($said === '') {
            $said = $message->imagePath ? 'sent a photo' : 'sent an attachment';
        }
        $notes = app(\App\Services\NotificationService::class);
        foreach (ScheduleTeam::memberIds($schedule) as $memberId) {
            if ((int) $memberId === $meId) {
                continue;
            }
            $notes->notify(
                (int) $memberId,
                'team-chat',
                $who . ' messaged the team',
                // Plain words: a chat body can carry mention tokens, and a
                // notification line has no way to render them as links.
                \App\Support\CommunityText::plain($said, 120),
                // Through the entry route so a member who also farms their
                // own land does not land on a 404 — their session is pointed
                // at their farm, not at the one that messaged them.
                route('sm.enter', ['schedule' => $schedule->id, 'to' => 'sm.collab']),
                $meId,
                (int) $schedule->id,
                1,
            );
        }
    }

    /**
     * Mark messages as seen by me, and say who has seen mine.
     *
     * Called by the thread only while it is actually on screen: a message
     * that arrived behind a shut panel has not been seen by anybody.
     */
    public function seen(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $upto = (int) $request->input('upto');
        if ($upto > 0) {
            $ids = ScheduleChatMessage::active()
                ->where('scheduleId', $schedule->id)
                ->where('id', '<=', $upto)
                ->where('userId', '!=', $meId)
                ->orderByDesc('id')
                ->limit(200)
                ->pluck('id');
            $now = now();
            foreach ($ids as $id) {
                \App\Models\ScheduleChatRead::firstOrCreate(
                    ['messageId' => (int) $id, 'userId' => $meId],
                    ['seenAt' => $now]
                );
            }
        }

        return response()->json([
            'success' => true,
            'data' => ['seen' => $this->seenMap((int) $schedule->id, $meId)],
        ]);
    }

    /**
     * For my own recent messages: who has seen each one.
     *
     * Only mine — "has it been seen" is a question about what you sent, and
     * answering it for the whole thread would be a query per message.
     *
     * @return array<int, list<string>> messageId => first names
     */
    private function seenMap(int $scheduleId, int $meId): array
    {
        $mine = ScheduleChatMessage::active()
            ->where('scheduleId', $scheduleId)
            ->where('userId', $meId)
            ->orderByDesc('id')
            ->limit(30)
            ->pluck('id');
        if ($mine->isEmpty()) {
            return [];
        }

        $reads = \App\Models\ScheduleChatRead::whereIn('messageId', $mine)->get();
        if ($reads->isEmpty()) {
            return [];
        }
        $names = \App\Models\User::whereIn('id', $reads->pluck('userId')->unique())->get()->keyBy('id');

        $out = [];
        foreach ($reads as $r) {
            $who = $names->get($r->userId);
            $out[(int) $r->messageId][] = (string) \Illuminate\Support\Str::of($who?->full_name ?: 'Someone')
                ->explode(' ')->first();
        }

        return $out;
    }

    /** Shape a message for the client. */
    private function present(ScheduleChatMessage $m, int $meId): array
    {
        $author = $m->author;

        return [
            'id' => $m->id,
            'body' => $m->body,
            'image' => $m->imagePath ? \App\Support\MediaStore::url($m->imagePath) : null,
            'files' => collect(is_array($m->attachments) ? $m->attachments : [])
                ->map(fn ($a) => [
                    'type' => $a['type'] ?? 'file',
                    'name' => $a['name'] ?? 'Attachment',
                    'size' => $a['size'] ?? null,
                    'url' => \App\Support\MediaStore::url($a['path'] ?? null),
                    'posterUrl' => \App\Support\MediaStore::url($a['poster'] ?? null),
                ])->filter(fn ($a) => $a['url'])->values()->all(),
            'mine' => (int) $m->userId === $meId,
            'userId' => (int) $m->userId,
            'name' => $author?->full_name ?: 'Member',
            'avatar' => $author && $author->avatarPath ? \App\Support\MediaStore::url($author->avatarPath) : null,
            'initials' => $author?->initials ?: '·',
            'at' => optional($m->created_at)->format('M j, g:i A'),
        ];
    }
}
