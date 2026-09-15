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
            // Titles and bodies are shown as plain text in the panel, so the
            // composer's mention tokens have to become the names they stand
            // for — a preview reading "@[admin john tugare](24)" is a name
            // wearing the machinery that found it. Done here rather than at
            // write time so rows already in the table read correctly too.
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => \App\Support\CommunityText::plain($n->title, 120),
                'body' => \App\Support\CommunityText::plain($n->body, 160),
                // Rows already in the table — and rows the mother site writes
                // — carry whichever host wrote them, which on the live site is
                // a link to somebody's laptop. Served as a path, it lands on
                // the site the reader is already on.
                'url' => \App\Services\NotificationService::localUrl($n->url),
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

    /**
     * Everything now shown has been seen.
     *
     * The badge counted every unread row while the panel showed the newest
     * thirty, so somebody with more than that read all they could see and the
     * number stayed up — which reads exactly like "it is counting ones I have
     * already read". Opening the panel is the act of reading them; the rows
     * keep their unread dots for this view, because which ones were new is
     * still worth seeing while you are looking at them.
     */
    public function markSeen()
    {
        $userId = Auth::id();
        AnisystemNotification::active()->forUser($userId)->unread()->update(['readAt' => now()]);

        return response()->json(['success' => true, 'data' => ['unread' => 0]]);
    }

    /**
     * The unread badge count, polled — and the newest unread row with it.
     *
     * Outside a Collab Room no socket is open (see bootstrap.js), so this
     * poll is how the bell learns that something new has landed. The count
     * alone cannot say WHAT: one row read and another arrived is the same
     * number. The newest unread row's id lets the bell tell a new arrival
     * from a shuffle, and its words let the device say so when the app is
     * not on screen.
     */
    public function count()
    {
        $userId = Auth::id();
        $latest = AnisystemNotification::active()->forUser($userId)->unread()->orderByDesc('id')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'unread' => $this->unreadCount($userId),
                'latest' => $latest ? [
                    'id' => (int) $latest->id,
                    'type' => (string) $latest->type,
                    'title' => \App\Support\CommunityText::plain($latest->title, 120),
                    'body' => \App\Support\CommunityText::plain($latest->body, 160),
                    'url' => \App\Services\NotificationService::localUrl($latest->url),
                ] : null,
            ],
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
