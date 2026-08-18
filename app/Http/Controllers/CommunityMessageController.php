<?php

namespace App\Http\Controllers;

use App\Models\CommunityMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Direct 1-on-1 messaging that powers the Messenger-style dock. Threads are
 * derived from message pairs; a member can turn their inbox off (allowMessages).
 */
class CommunityMessageController extends Controller
{
    /** Extensions that mark a stored media path as a clip rather than a picture. */
    private const VIDEO_EXTS = ['mp4', 'mov', 'webm', 'mkv', 'm4v', '3gp', 'avi'];

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /** Conversation list for the dock (latest message + unread per thread). */
    public function threads(Request $request)
    {
        $meId = (int) Auth::id();
        $rows = CommunityMessage::where('deleteStatus', 1)
            ->where(fn ($q) => $q->where('senderId', $meId)->orWhere('recipientId', $meId))
            ->orderByDesc('id')->limit(400)->get();

        $threads = [];
        foreach ($rows as $m) {
            $other = (int) $m->senderId === $meId ? (int) $m->recipientId : (int) $m->senderId;
            if (! isset($threads[$other])) {
                $threads[$other] = ['last' => $m, 'unread' => 0];
            }
            if ((int) $m->recipientId === $meId && ! $m->isRead) {
                $threads[$other]['unread']++;
            }
        }

        $users = User::whereIn('id', array_keys($threads) ?: [0])->where('deleteStatus', 1)->get()->keyBy('id');
        $items = collect($threads)->map(function ($t, $otherId) use ($users, $meId) {
            $u = $users->get($otherId);
            if (! $u) {
                return null;
            }
            // A media-only message used to leave the preview line blank, which
            // reads as an empty thread rather than "they sent you a photo".
            $lastText = trim((string) $t['last']->body) !== ''
                ? Str::limit($t['last']->body, 48)
                : ($t['last']->imagePath ? $this->mediaLabel($t['last']->imagePath) : '');

            return [
                'userId' => (int) $otherId,
                'name' => $u->full_name,
                'avatar' => $u->avatarPath ? \App\Support\MediaStore::url($u->avatarPath) : null,
                'initials' => $u->initials,
                'lastBody' => ((int) $t['last']->senderId === $meId ? 'You: ' : '') . $lastText,
                'lastAt' => $t['last']->created_at?->diffForHumans(null, true),
                'unread' => $t['unread'],
            ];
        })->filter()->values();

        return response()->json(['success' => true, 'data' => [
            'threads' => $items,
            'unread' => (int) CommunityMessage::where('recipientId', $meId)->where('isRead', 0)->where('deleteStatus', 1)->count(),
        ]]);
    }

    /** Messages in one thread; marks the other party's messages read. */
    public function thread(Request $request, int $userId)
    {
        $meId = (int) Auth::id();
        $other = User::where('deleteStatus', 1)->find($userId);
        if (! $other) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        CommunityMessage::where('recipientId', $meId)->where('senderId', $userId)
            ->where('isRead', 0)->update(['isRead' => 1]);

        // The NEWEST 200, delivered oldest-first for rendering. Ascending with
        // a limit took the OLDEST 200, so a long conversation froze at its own
        // history and today's messages never reached the window.
        $rows = CommunityMessage::where('deleteStatus', 1)
            ->where(function ($q) use ($meId, $userId) {
                $q->where(fn ($w) => $w->where('senderId', $meId)->where('recipientId', $userId))
                  ->orWhere(fn ($w) => $w->where('senderId', $userId)->where('recipientId', $meId));
            })
            ->latest('id')->limit(200)->get()->reverse()->values();

        // Lookup for resolving quoted-reply snippets without extra queries.
        $byId = $rows->keyBy('id');
        $msgs = $rows->map(fn ($m) => array_merge([
            'id' => $m->id,
            'body' => $m->body,
            'mine' => (int) $m->senderId === $meId,
            'at' => $m->created_at?->diffForHumans(null, true),
            'replyTo' => $this->replySnippet($byId->get($m->replyToId), $meId),
        ], $this->mediaPayload($m->imagePath)))->values();

        return response()->json(['success' => true, 'data' => [
            'user' => [
                'id' => $other->id,
                'name' => $other->full_name,
                'avatar' => $other->avatarPath ? \App\Support\MediaStore::url($other->avatarPath) : null,
                'initials' => $other->initials,
            ],
            'canMessage' => (bool) $other->allowMessages || (int) $other->id === $meId,
            'messages' => $msgs,
        ]]);
    }

    /** A compact quoted-reply snippet for the message being replied to. */
    private function replySnippet(?CommunityMessage $m, int $meId): ?array
    {
        if (! $m) {
            return null;
        }
        $text = trim((string) $m->body);
        if ($text === '' && $m->imagePath) {
            $text = $this->mediaLabel($m->imagePath);
        }

        return [
            'id' => $m->id,
            'mine' => (int) $m->senderId === $meId,
            'body' => Str::limit($text, 80),
        ];
    }

    /** True when a stored media path is a clip rather than a picture. */
    private function isVideo(?string $path): bool
    {
        return filled($path)
            && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::VIDEO_EXTS, true);
    }

    /** How this media is described in words (previews, notifications). */
    private function mediaLabel(?string $path): string
    {
        return $this->isVideo($path) ? '🎬 Video' : '📷 Photo';
    }

    /**
     * One media slot, told apart by extension: imagePath predates clips, so a
     * video rides in the same column and this payload says which one it was.
     * A local clip's poster shares its basename (VideoOptimizer writes
     * uuid.mp4 + uuid.webp side by side), so it is derived here rather than
     * given a column of its own; a remote clip has no derivable sibling and
     * the <video preload="metadata"> bubble paints its own first frame.
     */
    private function mediaPayload(?string $path): array
    {
        if (blank($path)) {
            return ['image' => null, 'video' => null, 'poster' => null];
        }
        if (! $this->isVideo($path)) {
            return ['image' => \App\Support\MediaStore::url($path), 'video' => null, 'poster' => null];
        }

        $poster = null;
        if (! \App\Support\MediaStore::isRemote($path)) {
            $sibling = preg_replace('/\.[A-Za-z0-9]+$/', '.webp', $path);
            if ($sibling !== $path && Storage::disk('public')->exists($sibling)) {
                $poster = \App\Support\MediaStore::url($sibling);
            }
        }

        return ['image' => null, 'video' => \App\Support\MediaStore::url($path), 'poster' => $poster];
    }

    /**
     * A gallery share arrives as the stored path the media picker handed the
     * browser — attach-by-reference, the same contract notes and observations
     * use, so a season keeps one copy of each file. The path space is
     * unguessable (uuid/random names from the picker), but the shape is still
     * checked and, when the path claims to live on this disk, so is the file:
     * a share that points nowhere would render as a broken bubble forever.
     */
    private function galleryShare(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, '..') || str_contains($path, '://')) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            && ! in_array($ext, self::VIDEO_EXTS, true)) {
            return null;
        }
        if (\App\Support\MediaStore::isRemote($path)) {
            return $path;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /** Send a message (respects the recipient's allowMessages switch). */
    public function send(Request $request, int $userId)
    {
        $meId = (int) Auth::id();
        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            // 8 MB like the rest of the app — "capture photo" hands over a
            // camera JPEG, which routinely cleared the old 5 MB cap.
            'image' => 'nullable|image|max:8192',
            // 300 MB ceiling and the mime list every clip path enforces;
            // VideoOptimizer re-encodes to a streamable size on the way in.
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-m4v|max:307200',
            // A stored path picked from the season gallery — a reference,
            // not a copy (validated in galleryShare()).
            'galleryPath' => 'nullable|string|max:500',
            'replyToId' => 'nullable|integer',
        ]);
        $recipient = User::where('deleteStatus', 1)->find($userId);
        if (! $recipient) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }
        if ((int) $recipient->id === $meId) {
            return response()->json(['success' => false, 'message' => 'You cannot message yourself.'], 422);
        }
        if (! $recipient->allowMessages) {
            return response()->json(['success' => false, 'message' => $recipient->firstName . ' has turned off messages.'], 403);
        }

        $body = trim((string) ($data['body'] ?? ''));
        $mediaPath = null;
        if ($request->hasFile('video')) {
            // The same pass every clip in the app takes (Quick Record, walls):
            // ≤720p MP4 with a poster frame beside it, because these travel
            // over a farm's phone signal in both directions.
            try {
                $stored = \App\Support\VideoOptimizer::storeCompressed($request->file('video'), 'community/messages');
                $mediaPath = $stored['video'];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'The video could not be saved.'], 422);
            }
        } elseif ($request->hasFile('image')) {
            $mediaPath = \App\Support\MediaOptimizer::storeImageAsWebp($request->file('image'), 'community/messages');
        } elseif (filled($data['galleryPath'] ?? null)) {
            $mediaPath = $this->galleryShare((string) $data['galleryPath']);
            if ($mediaPath === null) {
                return response()->json(['success' => false, 'message' => 'That gallery item could not be shared.'], 422);
            }
        }
        if ($body === '' && ! $mediaPath) {
            return response()->json(['success' => false, 'message' => 'Write a message or add a photo or video.'], 422);
        }

        // A reply must point at a real message in *this* conversation.
        $replyToId = null;
        $replyRow = null;
        if (! empty($data['replyToId'])) {
            $replyRow = CommunityMessage::where('deleteStatus', 1)
                ->where('id', (int) $data['replyToId'])
                ->where(function ($q) use ($meId, $userId) {
                    $q->where(fn ($w) => $w->where('senderId', $meId)->where('recipientId', $userId))
                      ->orWhere(fn ($w) => $w->where('senderId', $userId)->where('recipientId', $meId));
                })
                ->first();
            if ($replyRow) {
                $replyToId = $replyRow->id;
            }
        }

        $msg = CommunityMessage::create([
            'senderId' => $meId,
            'recipientId' => $recipient->id,
            'replyToId' => $replyToId,
            'body' => $body,
            'imagePath' => $mediaPath,
            'isRead' => 0,
            'deleteStatus' => 1,
        ]);

        $actor = Auth::user();
        $this->notifications->notify(
            userId: $recipient->id,
            type: 'message',
            title: ($actor->full_name ?: 'A member') . ' sent you a message',
            body: $body !== '' ? Str::limit($body, 90) : $this->mediaLabel($mediaPath),
            url: route('community.index') . '?dm=' . $meId,
            actorUserId: $meId,
            dedupeWindowHours: null,
        );

        return response()->json(['success' => true, 'data' => array_merge([
            'id' => $msg->id,
            'body' => $msg->body,
            'mine' => true,
            'at' => 'now',
            'replyTo' => $this->replySnippet($replyRow, $meId),
        ], $this->mediaPayload($mediaPath))]);
    }

    /** Just the unread total, for the dock badge poll. */
    public function unreadCount(Request $request)
    {
        return response()->json(['success' => true, 'data' => [
            'unread' => (int) CommunityMessage::where('recipientId', (int) Auth::id())->where('isRead', 0)->where('deleteStatus', 1)->count(),
        ]]);
    }

    /**
     * Live poll for the dock: incoming messages newer than $after so the
     * recipient's chat window pops up / updates in near real time. Does not
     * mark anything read — the open window handles that. On the first call
     * (after=0) it only reports maxId so the client syncs without replaying.
     */
    public function poll(Request $request)
    {
        $meId = (int) Auth::id();
        $after = (int) $request->query('after', 0);

        $maxId = (int) CommunityMessage::where('deleteStatus', 1)
            ->where(fn ($q) => $q->where('senderId', $meId)->orWhere('recipientId', $meId))
            ->max('id');

        // Incoming messages newer than what the client has seen. The client's
        // first call (synced=false) ignores these and just adopts maxId, so it
        // is safe to always return them (the after=0 no-history case included).
        $rows = CommunityMessage::where('deleteStatus', 1)
            ->where('recipientId', $meId)
            ->where('id', '>', $after)
            ->orderBy('id')->limit(50)->get();
        $users = User::whereIn('id', $rows->pluck('senderId')->unique()->values() ?: [0])
            ->get()->keyBy('id');
        // Resolve any quoted-reply parents in one query.
        $parentIds = $rows->pluck('replyToId')->filter()->unique()->values();
        $parents = $parentIds->isNotEmpty()
            ? CommunityMessage::whereIn('id', $parentIds)->get()->keyBy('id')
            : collect();
        $incoming = $rows->map(fn ($m) => array_merge([
            'id' => (int) $m->id,
            'senderId' => (int) $m->senderId,
            'senderName' => optional($users->get((int) $m->senderId))->full_name ?? 'Member',
            'senderAvatar' => optional($users->get((int) $m->senderId))->avatarPath ? \App\Support\MediaStore::url($users->get((int) $m->senderId)->avatarPath) : null,
            'senderInitials' => optional($users->get((int) $m->senderId))->initials ?? '?',
            'body' => $m->body,
            'replyTo' => $this->replySnippet($parents->get($m->replyToId), $meId),
        ], $this->mediaPayload($m->imagePath)))->values();

        return response()->json(['success' => true, 'data' => [
            'incoming' => $incoming,
            'maxId' => $maxId,
            'unread' => (int) CommunityMessage::where('recipientId', $meId)->where('isRead', 0)->where('deleteStatus', 1)->count(),
        ]]);
    }
}
