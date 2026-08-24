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
    /** How many kept posts arrive at once, first page and every page after. */
    private const SAVED_PER_PAGE = 10;

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

        /* A page at a time now, not the lot.
         *
         * Somebody who has kept posts for a season has hundreds, each of them
         * a card with photographs in it, and the page was rendering every one
         * before the first was on screen. The rest arrive as the reader gets
         * near the bottom. */
        $page = $this->savedPage($ids, $meId, null, '');

        return view('community.saved', [
            'posts' => $page['posts'],
            'savedIds' => $ids,
            'hasMore' => $page['hasMore'],
            'before' => $page['before'],
            'savedTotal' => count($ids),
            // Who you already follow and already farm with. Without these the
            // card has no way to know, and every author on the page was
            // offered a Follow button the reader had already pressed.
            'followingIds' => $this->social->followingIds($meId),
            'friendIds' => \App\Models\CommunityConnection::connectedIds($meId),
        ]);
    }

    /**
     * The next page of kept posts, and the answer to a search through them.
     *
     * One road for both, the way the wall does it: a search is page one with
     * words on it, so the reader can keep scrolling through what matched.
     */
    public function savedMore(Request $request)
    {
        $meId = (int) Auth::id();
        $ids = $this->social->bookmarkedIds($meId);
        $before = $request->query('before');
        $q = Str::limit(trim((string) $request->query('q', '')), 120, '');

        $page = $this->savedPage($ids, $meId, is_numeric($before) ? (int) $before : null, $q);

        $followingIds = $this->social->followingIds($meId);
        $friendIds = \App\Models\CommunityConnection::connectedIds($meId);

        $html = '';
        foreach ($page['posts'] as $post) {
            $html .= view('community.partials.feed-post', [
                'post' => $post,
                'friendIds' => $friendIds,
                'followingIds' => $followingIds,
                'savedIds' => $ids,
                // Every card here carries the way back to where it lives.
                'permalink' => true,
            ])->render();
        }

        return $this->json(true, '', [
            'html' => $html,
            'hasMore' => $page['hasMore'],
            'count' => $page['posts']->count(),
            'before' => $page['before'],
            'q' => $q,
        ]);
    }

    /**
     * One page of kept posts, oldest cursor last.
     *
     * The cursor is the post's own id because that is what the list is
     * ordered by; searching looks at what was written and at who wrote it,
     * since "the one Nena posted about tungro" is one memory, not two.
     *
     * @param  array<int, int>  $ids  every post this member has kept
     * @return array{posts: \Illuminate\Support\Collection, hasMore: bool, before: ?int}
     */
    private function savedPage(array $ids, int $meId, ?int $before, string $q): array
    {
        if ($ids === []) {
            return ['posts' => collect(), 'hasMore' => false, 'before' => null];
        }

        $rows = CommunityWallPost::active()
            ->whereIn('id', $ids)
            ->when($before !== null, fn ($sql) => $sql->where('id', '<', $before))
            ->when($q !== '', function ($sql) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $sql->where(function ($w) use ($like) {
                    $w->where('body', 'like', $like)
                        ->orWhereHas('author', fn ($a) => $a
                            ->where('firstName', 'like', $like)
                            ->orWhere('lastName', 'like', $like));
                });
            })
            ->with(['author', 'sharedPost'])
            ->withCount(['comments as comment_count'])
            ->orderByDesc('id')
            ->limit(self::SAVED_PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::SAVED_PER_PAGE;
        $posts = $rows->take(self::SAVED_PER_PAGE)->values();

        \App\Models\CommunityReaction::attach($posts, 'wallpost', $meId);
        $this->social->attachAuthorFacts($posts, $meId);

        return [
            'posts' => $posts,
            'hasMore' => $hasMore,
            'before' => $posts->last() ? (int) $posts->last()->id : null,
        ];
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
            : view('community.partials.feed-post', [
                // A share lands on the wall as a card like any other, so it
                // is introduced like any other.
                'post' => tap($post->load('author', 'sharedPost'), fn ($p) => $this->social->attachAuthorFacts(collect([$p]), (int) Auth::id())),
                'friendIds' => [],
            ])->render();

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
