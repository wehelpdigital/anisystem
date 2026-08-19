<?php

namespace App\Http\Controllers;

use App\Models\CommunityConnection;
use App\Models\CommunityMessage;
use App\Models\CommunityWallPost;
use App\Models\User;
use App\Services\CommunitySocialService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Following, keeping and passing on — the three things a member can do with
 * somebody's post that are not a reaction.
 *
 * They live together because they share one rule: none of them changes the
 * original. A follow is about a person, a bookmark is private to the keeper,
 * and a share makes a NEW post that points at the old one.
 */
class CommunitySocialController extends Controller
{
    public function __construct(
        private readonly CommunitySocialService $social,
        private readonly NotificationService $notifications,
    ) {
    }

    /** Follow or unfollow somebody; the button reports back what it now is. */
    public function follow(Request $request, int $userId)
    {
        $meId = (int) Auth::id();
        if ($meId === $userId) {
            return $this->json(false, 'You cannot follow yourself.', [], 422);
        }
        $target = User::where('deleteStatus', 1)->find($userId);
        if (! $target) {
            return $this->json(false, 'That member is no longer here.', [], 404);
        }

        $following = $this->social->toggleFollow($meId, $userId);

        // Told once, on the way in. An unfollow is nobody else's business —
        // announcing it would make the button unusable.
        if ($following) {
            $this->notifications->notify(
                $userId,
                'community.follow',
                (Auth::user()->full_name ?: 'A co-farmer') . ' is now following you',
                null,
                route('community.connect.profile', ['userId' => $meId]),
                $meId,
                null,
                24,
            );
        }

        return $this->json(true, $following ? 'Following.' : 'Unfollowed.', [
            'following' => $following,
            'followers' => $this->social->followerCount($userId),
        ]);
    }

    /** Keep a post, or stop keeping it. */
    public function bookmark(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->find($postId);
        if (! $post) {
            return $this->json(false, 'That post is gone.', [], 404);
        }

        $saved = $this->social->toggleBookmark((int) Auth::id(), $postId);

        return $this->json(true, $saved ? 'Saved to your bookmarks.' : 'Removed from your bookmarks.', [
            'saved' => $saved,
            'count' => $this->social->bookmarkCount((int) Auth::id()),
        ]);
    }

    /**
     * People you may know, fetched rather than rendered with the page.
     *
     * The ranking walks every connection of every connection and then the
     * comment threads the viewer has been in — cheap on a small farm, slow on
     * a busy one, and always slower than the wall it would otherwise hold up.
     * So the strip paints its own skeleton and asks for this.
     */
    public function suggestions(Request $request)
    {
        $meId = (int) Auth::id();
        $people = CommunityConnection::recommendationsFor($meId, 12);
        $followingIds = $this->social->followingIds($meId);

        $html = '';
        foreach ($people as $person) {
            $html .= view('community.partials.reco-card', [
                'u' => $person,
                'following' => in_array((int) $person->id, $followingIds, true),
            ])->render();
        }

        return $this->json(true, 'Suggestions.', ['html' => $html, 'count' => $people->count()]);
    }

    /** Everything this member kept, newest first. */
    public function saved(Request $request)
    {
        $meId = (int) Auth::id();
        $ids = $this->social->bookmarkedIds($meId);

        $posts = $ids === [] ? collect() : CommunityWallPost::active()
            ->whereIn('id', $ids)
            ->with(['author', 'sharedPost'])
            ->withCount(['comments as comment_count'])
            ->orderByDesc('id')
            ->get();

        return view('community.saved', [
            'posts' => $posts,
            'savedIds' => $ids,
        ]);
    }

    /**
     * Share a post onto your own wall, with your own words above it.
     *
     * A share is a post in its own right — it can be commented on, reacted to
     * and shared again — and it points at the original rather than copying it,
     * so an edit or a removal upstream is honoured everywhere at once.
     */
    public function shareToWall(Request $request, int $postId)
    {
        $original = CommunityWallPost::active()->find($postId);
        if (! $original) {
            return $this->json(false, 'That post is gone.', [], 404);
        }

        $data = $request->validate(['body' => 'nullable|string|max:5000']);
        $meId = (int) Auth::id();

        // Sharing a share points at what was actually written, not at the
        // chain: two hops deep is a rabbit hole nobody reads back.
        $rootId = $original->sharedPostId ? (int) $original->sharedPostId : (int) $original->id;

        $post = CommunityWallPost::create([
            'wallUserId' => $meId,
            'authorUserId' => $meId,
            'body' => trim((string) ($data['body'] ?? '')),
            'sharedPostId' => $rootId,
            'deleteStatus' => 1,
        ]);

        if ((int) $original->authorUserId !== $meId) {
            $this->notifications->notify(
                (int) $original->authorUserId,
                'community.share',
                (Auth::user()->full_name ?: 'A co-farmer') . ' shared your post',
                null,
                route('community.index') . '#post-' . $post->id,
                $meId,
            );
        }

        /* The card itself, so the wall shows the share the moment it is made.
         *
         * Rendered the way the composer's own new post is — one card partial,
         * one appearance — and with sharedPost loaded, because the whole point
         * of a share is the post quoted inside it. */
        $html = $request->input('render') === 'wall'
            ? view('community.connect.partials.wall-posts', ['posts' => collect([$post->load('author', 'sharedPost')])])->render()
            : view('community.partials.feed-post', ['post' => $post->load('author', 'sharedPost'), 'friendIds' => []])->render();

        return $this->json(true, 'Shared to your wall.', ['postId' => (int) $post->id, 'html' => $html]);
    }

    /** Pass a post to one co-farmer as a message. */
    public function shareToMessage(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->find($postId);
        if (! $post) {
            return $this->json(false, 'That post is gone.', [], 404);
        }
        $data = $request->validate([
            'userId' => 'required|integer',
            'note' => 'nullable|string|max:1000',
        ]);

        $meId = (int) Auth::id();
        $toId = (int) $data['userId'];
        // Only to somebody who has agreed to hear from you.
        if (CommunityConnection::statusFor($meId, $toId) !== 'accepted') {
            return $this->json(false, 'You can only send this to a co-farmer.', [], 403);
        }

        $link = $this->publicUrl($post);
        $body = trim((string) ($data['note'] ?? ''));
        $body = $body === '' ? $link : ($body . "\n" . $link);

        CommunityMessage::create([
            'senderId' => $meId,
            'recipientId' => $toId,
            'body' => $body,
            'isRead' => 0,
            'deleteStatus' => 1,
        ]);

        $this->notifications->notify(
            $toId,
            'community.message',
            (Auth::user()->full_name ?: 'A co-farmer') . ' sent you a post',
            null,
            route('community.index') . '?dm=' . $meId,
            $meId,
        );

        return $this->json(true, 'Sent.', []);
    }

    /** The link that works outside the app — minted on first ask. */
    public function publicLink(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->find($postId);
        if (! $post) {
            return $this->json(false, 'That post is gone.', [], 404);
        }

        return $this->json(true, 'Link ready.', ['url' => $this->publicUrl($post)]);
    }

    /**
     * A post's public address, created the first time somebody asks for it.
     *
     * Nothing is public until a member decides it is: a post with no token has
     * no address at all, which is why this mints rather than derives one.
     */
    private function publicUrl(CommunityWallPost $post): string
    {
        if (blank($post->publicToken)) {
            $post->publicToken = Str::random(40);
            $post->save();
        }

        return route('community.public.post', ['token' => $post->publicToken]);
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
