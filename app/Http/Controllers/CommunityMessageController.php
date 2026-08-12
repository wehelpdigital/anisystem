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
            return [
                'userId' => (int) $otherId,
                'name' => $u->full_name,
                'avatar' => $u->avatarPath ? \App\Support\MediaStore::url($u->avatarPath) : null,
                'initials' => $u->initials,
                'lastBody' => ((int) $t['last']->senderId === $meId ? 'You: ' : '') . Str::limit($t['last']->body, 48),
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

        $rows = CommunityMessage::where('deleteStatus', 1)
            ->where(function ($q) use ($meId, $userId) {
                $q->where(fn ($w) => $w->where('senderId', $meId)->where('recipientId', $userId))
                  ->orWhere(fn ($w) => $w->where('senderId', $userId)->where('recipientId', $meId));
            })
            ->orderBy('id')->limit(200)->get();

        // Lookup for resolving quoted-reply snippets without extra queries.
        $byId = $rows->keyBy('id');
        $msgs = $rows->map(fn ($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'image' => $m->imagePath ? \App\Support\MediaStore::url($m->imagePath) : null,
            'mine' => (int) $m->senderId === $meId,
            'at' => $m->created_at?->diffForHumans(null, true),
            'replyTo' => $this->replySnippet($byId->get($m->replyToId), $meId),
        ])->values();

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
            $text = '📷 Photo';
        }

        return [
            'id' => $m->id,
            'mine' => (int) $m->senderId === $meId,
            'body' => Str::limit($text, 80),
        ];
    }

    /** Send a message (respects the recipient's allowMessages switch). */
    public function send(Request $request, int $userId)
    {
        $meId = (int) Auth::id();
        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:5120',
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
        $imagePath = $request->hasFile('image')
            ? \App\Support\MediaOptimizer::storeImageAsWebp($request->file('image'), 'community/messages')
            : null;
        if ($body === '' && ! $imagePath) {
            return response()->json(['success' => false, 'message' => 'Write a message or add a photo.'], 422);
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
            'imagePath' => $imagePath,
            'isRead' => 0,
            'deleteStatus' => 1,
        ]);

        $actor = Auth::user();
        $this->notifications->notify(
            userId: $recipient->id,
            type: 'message',
            title: ($actor->full_name ?: 'A member') . ' sent you a message',
            body: $body !== '' ? Str::limit($body, 90) : '📷 Photo',
            url: route('community.index') . '?dm=' . $meId,
            actorUserId: $meId,
            dedupeWindowHours: null,
        );

        return response()->json(['success' => true, 'data' => [
            'id' => $msg->id,
            'body' => $msg->body,
            'image' => $imagePath ? \App\Support\MediaStore::url($imagePath) : null,
            'mine' => true,
            'at' => 'now',
            'replyTo' => $this->replySnippet($replyRow, $meId),
        ]]);
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
        $incoming = $rows->map(fn ($m) => [
            'id' => (int) $m->id,
            'senderId' => (int) $m->senderId,
            'senderName' => optional($users->get((int) $m->senderId))->full_name ?? 'Member',
            'senderAvatar' => optional($users->get((int) $m->senderId))->avatarPath ? \App\Support\MediaStore::url($users->get((int) $m->senderId)->avatarPath) : null,
            'senderInitials' => optional($users->get((int) $m->senderId))->initials ?? '?',
            'body' => $m->body,
            'image' => $m->imagePath ? \App\Support\MediaStore::url($m->imagePath) : null,
            'replyTo' => $this->replySnippet($parents->get($m->replyToId), $meId),
        ])->values();

        return response()->json(['success' => true, 'data' => [
            'incoming' => $incoming,
            'maxId' => $maxId,
            'unread' => (int) CommunityMessage::where('recipientId', $meId)->where('isRead', 0)->where('deleteStatus', 1)->count(),
        ]]);
    }
}
