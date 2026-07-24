<?php

namespace App\Http\Controllers;

use App\Models\AnisystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Top-bar notification bell: list, unread count, and mark-as-read actions.
 * Everything is scoped to the signed-in client.
 */
class NotificationController extends Controller
{
    /** Recent notifications + unread count as JSON for the bell panel. */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $items = AnisystemNotification::active()
            ->forUser($userId)
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'url' => $n->url,
                'isRead' => $n->readAt !== null,
                'ago' => $n->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'unread' => $this->unreadCount($userId),
            ],
        ]);
    }

    /** Lightweight unread badge count (polled). */
    public function count()
    {
        return response()->json([
            'success' => true,
            'data' => ['unread' => $this->unreadCount(Auth::id())],
        ]);
    }

    public function markRead(Request $request)
    {
        $id = (int) $request->input('id');
        AnisystemNotification::active()
            ->forUser(Auth::id())
            ->where('id', $id)
            ->update(['readAt' => now()]);

        return response()->json(['success' => true, 'data' => ['unread' => $this->unreadCount(Auth::id())]]);
    }

    public function markAllRead()
    {
        AnisystemNotification::active()
            ->forUser(Auth::id())
            ->unread()
            ->update(['readAt' => now()]);

        return response()->json(['success' => true, 'data' => ['unread' => 0]]);
    }

    private function unreadCount($userId): int
    {
        return AnisystemNotification::active()->forUser($userId)->unread()->count();
    }
}
