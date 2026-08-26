<?php

namespace App\Http\Controllers;

use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Account walls: any member can post text/photos on another member's wall (or
 * their own) and comment on wall posts, Facebook-style. Visible to anyone who
 * opens the profile.
 */
class CommunityWallController extends Controller
{
    private const PER_PAGE = 8;

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /** JSON page of wall posts for "load more". */
    public function posts(Request $request, int $userId)
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->pagePosts($userId, $page);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => view('community.connect.partials.wall-posts', ['posts' => $result['items']])->render(),
                'hasMore' => $result['hasMore'],
                'nextPage' => $page + 1,
            ],
        ]);
    }

    public function storePost(Request $request, int $userId)
    {
        $wallOwner = User::where('id', $userId)->where('deleteStatus', 1)->first();
        if (! $wallOwner) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        $request->validate([
            'body' => 'required_without_all:image,images,galleryPaths,video,videos,galleryVideoPaths|nullable|string|max:5000',
            // One photo (what every older caller sends) or several.
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            // Pictures the app already keeps, pointed at rather than sent.
            'galleryPaths' => 'nullable|array|max:8',
            'galleryPaths.*' => 'string|max:500',
            'video' => 'nullable|file|max:2097152', // the ceiling is length, not size: VideoOptimizer refuses a clip over a minute
            'videos' => 'nullable|array|max:3',
            'videos.*' => 'file|max:2097152',
            'galleryVideoPath' => 'nullable|string|max:500',
            'galleryVideoPaths' => 'nullable|array|max:3',
            'galleryVideoPaths.*' => 'string|max:500',
        ]);

        /* The pictures, in the order they were added.
         *
         * Uploads are stored; a gallery pick is a path the browser handed
         * back, so it goes through GalleryPick before anything trusts it —
         * that is the check that keeps a typed path from reaching somebody
         * else's folder. A picture that fails it is refused out loud rather
         * than quietly posting fewer photos than the farmer attached. */
        $shots = [];
        foreach (array_merge(
            $request->hasFile('image') ? [$request->file('image')] : [],
            array_values((array) $request->file('images', []))
        ) as $file) {
            $shots[] = $this->storeImage($file, 'community-wall/' . $wallOwner->id);
        }
        foreach (array_values((array) $request->input('galleryPaths', [])) as $picked) {
            $ok = \App\Support\GalleryPick::path((string) $picked);
            if ($ok === null) {
                return response()->json(['success' => false, 'message' => 'One of the pictures could not be read. Remove it and try again.'], 422);
            }
            $shots[] = $ok;
        }
        $shots = array_slice(array_values(array_unique($shots)), 0, 8);
        $imagePath = $shots[0] ?? null;

        /* The clips, up to three — the same walk a comment's endpoint makes.
         * An upload is compressed and given a poster; one picked out of the
         * gallery is referenced where it lies and has a frame cut for it if
         * nobody has yet. */
        $clips = [];
        foreach (array_merge(
            $request->hasFile('video') ? [$request->file('video')] : [],
            array_values((array) $request->file('videos', []))
        ) as $file) {
            try {
                $vid = $this->storeVideo($file, 'community-wall/' . $wallOwner->id);
                $clips[] = ['video' => $vid['video'], 'poster' => $vid['poster'] ?? null];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }
        foreach (array_merge(
            $request->filled('galleryVideoPath') ? [(string) $request->input('galleryVideoPath')] : [],
            array_values((array) $request->input('galleryVideoPaths', []))
        ) as $picked) {
            $ok = \App\Support\GalleryPick::path((string) $picked, \App\Support\GalleryPick::VIDEO_EXTS);
            if ($ok === null) {
                return response()->json(['success' => false, 'message' => 'One of the clips could not be attached. Remove it and try again.'], 422);
            }
            $clips[] = ['video' => $ok, 'poster' => \App\Support\VideoPoster::ensure($ok)];
        }
        $seenClips = [];
        $clips = array_values(array_filter($clips, function ($c) use (&$seenClips) {
            if (isset($seenClips[$c['video']])) { return false; }
            $seenClips[$c['video']] = true;
            return true;
        }));
        $clips = array_slice($clips, 0, 3);
        $videoPath = $clips[0]['video'] ?? null;
        $videoPoster = $clips[0]['poster'] ?? null;

        $post = CommunityWallPost::create([
            'wallUserId' => $wallOwner->id,
            'authorUserId' => Auth::id(),
            'body' => $request->input('body') ?: '',
            // The first picture where every older renderer looks, the whole
            // list where the new one does. Schema-guarded, so a deploy that
            // has not run the migration yet posts the first photo instead of
            // answering with a column-not-found 500.
            'imagePath' => $imagePath,
        ] + (count($shots) > 1 && \Illuminate\Support\Facades\Schema::hasColumn((new CommunityWallPost)->getTable(), 'imagePaths')
            ? ['imagePaths' => $shots] : [])
          + (count($clips) > 1 && \Illuminate\Support\Facades\Schema::hasColumn((new CommunityWallPost)->getTable(), 'videoPaths')
            ? ['videoPaths' => $clips] : []) + [
            'videoPath' => $videoPath,
            'videoPoster' => $videoPoster,
            'deleteStatus' => 1,
        ]);

        // Tell the wall owner someone posted (unless it's their own wall).
        $actor = Auth::user();
        $preview = Str::limit(trim(strip_tags((string) $request->input('body'))) ?: ($videoPath ? 'Shared a video.' : 'Shared a photo.'), 90);
        $url = route('community.connect.profile', ['userId' => $wallOwner->id]) . '#wallpost-' . $post->id;
        $this->notifications->notify(
            userId: $wallOwner->id,
            type: 'wall',
            title: ($actor->full_name ?: 'A member') . ' posted on your wall',
            body: $preview,
            url: $url,
            actorUserId: (int) Auth::id(),
        );

        // @mention notifications for anyone tagged in the post body.
        foreach (\App\Support\CommunityText::mentionedUserIds($post->body) as $mid) {
            if ($mid === (int) Auth::id() || $mid === (int) $wallOwner->id) {
                continue;
            }
            $this->notifications->notify(
                userId: $mid,
                type: 'mention',
                title: ($actor->full_name ?: 'A member') . ' mentioned you in a post',
                body: $preview,
                url: $url,
                actorUserId: (int) Auth::id(),
            );
        }

        // The dashboard/community feed renders posts with the feed-post partial;
        // the profile wall uses wall-posts. Return whichever the caller wants.
        $html = $request->input('render') === 'feed'
            ? view('community.partials.feed-post', [
                'post' => tap($post->load('author'), fn ($p) => app(\App\Services\CommunitySocialService::class)
                    ->attachAuthorFacts(collect([$p]), (int) Auth::id())),
                'friendIds' => [],
            ])->render()
            : view('community.connect.partials.wall-posts', ['posts' => collect([$post->load('author')])])->render();

        return response()->json([
            'success' => true,
            'message' => 'Posted.',
            'data' => ['html' => $html],
        ]);
    }

    public function storeComment(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->where('id', $postId)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }
        $request->validate([
            'body' => 'required_without_all:image,images,video,videos,galleryPath,galleryPaths,galleryVideoPath,galleryVideoPaths|nullable|string|max:2000',
            // One picture (what every older caller sends) or several.
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            'video' => 'nullable|file|max:2097152', // the ceiling is length, not size: VideoOptimizer refuses a clip over a minute
            'videos' => 'nullable|array|max:3',
            'videos.*' => 'file|max:2097152',
            // Pictures and clips already kept here, pointed at rather than uploaded.
            'galleryPath' => 'nullable|string|max:500',
            'galleryPaths' => 'nullable|array|max:8',
            'galleryPaths.*' => 'string|max:500',
            'galleryVideoPath' => 'nullable|string|max:500',
            'galleryVideoPaths' => 'nullable|array|max:3',
            'galleryVideoPaths.*' => 'string|max:500',
            'parentId' => 'nullable|integer',
        ]);

        // Repeats are refused before they are kept: points made comments
        // farmable, and the cheapest farm is pasting yesterday's words.
        if ($why = \App\Support\CommunitySpam::repeats((int) Auth::id(), $request->input('body'), 'as_community_wall_comments')) {
            return response()->json(['success' => false, 'message' => $why], 422);
        }

        // Replying to a comment: threads stay two levels deep, so replying to
        // a reply re-attaches to the thread's top comment.
        $parent = null;
        if ($request->filled('parentId')) {
            $parent = CommunityWallComment::active()
                ->where('id', (int) $request->input('parentId'))
                ->where('wallPostId', $post->id)
                ->first();
            if (! $parent) {
                return response()->json(['success' => false, 'message' => 'Comment not found.'], 404);
            }
            if ($parent->parentId) {
                $parent = CommunityWallComment::active()->find($parent->parentId) ?: $parent;
            }
        }

        /* The pictures, in the order they were added — the same walk a post
         * makes. Uploads are stored; a picture pointed at rather than
         * uploaded (from a season's gallery) goes through GalleryPick first,
         * which is what decides a string from a browser is a path at all. One
         * that fails is refused out loud rather than quietly attaching fewer
         * pictures than the farmer picked. */
        $shots = [];
        foreach (array_merge(
            $request->hasFile('image') ? [$request->file('image')] : [],
            array_values((array) $request->file('images', []))
        ) as $file) {
            $shots[] = $this->storeImage($file, 'community-wall/' . $post->wallUserId);
        }
        foreach (array_merge(
            $request->filled('galleryPath') ? [(string) $request->input('galleryPath')] : [],
            array_values((array) $request->input('galleryPaths', []))
        ) as $picked) {
            $ok = \App\Support\GalleryPick::path((string) $picked);
            if ($ok === null) {
                return response()->json(['success' => false, 'message' => 'One of the pictures could not be attached. Remove it and try again.'], 422);
            }
            $shots[] = $ok;
        }
        $shots = array_slice(array_values(array_unique($shots)), 0, 8);
        $imagePath = $shots[0] ?? null;

        /* The clips, in the order they were added, up to three — the same
         * walk a discussion's answer makes. An upload is compressed and given
         * a poster; one picked out of the gallery is referenced where it lies
         * and has a frame cut for it if nobody has yet. */
        $clips = [];
        foreach (array_merge(
            $request->hasFile('video') ? [$request->file('video')] : [],
            array_values((array) $request->file('videos', []))
        ) as $file) {
            try {
                $vid = $this->storeVideo($file, 'community-wall/' . $post->wallUserId);
                $clips[] = ['video' => $vid['video'], 'poster' => $vid['poster'] ?? null];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }
        foreach (array_merge(
            $request->filled('galleryVideoPath') ? [(string) $request->input('galleryVideoPath')] : [],
            array_values((array) $request->input('galleryVideoPaths', []))
        ) as $picked) {
            $ok = \App\Support\GalleryPick::path((string) $picked, \App\Support\GalleryPick::VIDEO_EXTS);
            if ($ok === null) {
                return response()->json(['success' => false, 'message' => 'One of the clips could not be attached. Remove it and try again.'], 422);
            }
            $clips[] = ['video' => $ok, 'poster' => \App\Support\VideoPoster::ensure($ok)];
        }
        $seenClips = [];
        $clips = array_values(array_filter($clips, function ($c) use (&$seenClips) {
            if (isset($seenClips[$c['video']])) { return false; }
            $seenClips[$c['video']] = true;
            return true;
        }));
        $clips = array_slice($clips, 0, 3);
        $videoPath = $clips[0]['video'] ?? null;
        $videoPoster = $clips[0]['poster'] ?? null;

        $comment = CommunityWallComment::create([
            'wallPostId' => $post->id,
            'parentId' => $parent?->id,
            'userId' => Auth::id(),
            'videoPath' => $videoPath,
            'videoPoster' => $videoPoster,
            'body' => $request->input('body') ?: '',
            // The first picture where every older renderer looks, the whole
            // set where the new one does — and only when the column is
            // actually there, so a deploy that has not migrated yet keeps the
            // first picture instead of answering with a 500.
            'imagePath' => $imagePath,
        ] + (count($shots) > 1 && \Illuminate\Support\Facades\Schema::hasColumn((new CommunityWallComment)->getTable(), 'imagePaths')
            ? ['imagePaths' => $shots] : [])
          + (count($clips) > 1 && \Illuminate\Support\Facades\Schema::hasColumn((new CommunityWallComment)->getTable(), 'videoPaths')
            ? ['videoPaths' => $clips] : []) + [
            'deleteStatus' => 1,
        ]);

        $actor = Auth::user();
        $preview = Str::limit(trim(strip_tags((string) $request->input('body'))) ?: ($videoPath ? 'Shared a video.' : 'Shared a photo.'), 90);
        // Deep-link straight to the post so the bell can scroll + highlight it.
        $url = route('community.connect.profile', ['userId' => $post->wallUserId]) . '#wallpost-' . $post->id;
        // Notify the parent comment's author on replies, and the post author —
        // each once, and never the actor themselves.
        $targets = [];
        if ($parent && (int) $parent->userId !== (int) Auth::id()) {
            $targets[(int) $parent->userId] = ' replied to your comment';
        }
        if ((int) $post->authorUserId !== (int) Auth::id() && ! isset($targets[(int) $post->authorUserId])) {
            $targets[(int) $post->authorUserId] = $parent ? ' replied on your post' : ' commented on your post';
        }
        foreach ($targets as $targetId => $verb) {
            $this->notifications->notify(
                userId: $targetId,
                type: 'comment',
                title: ($actor->full_name ?: 'A member') . $verb,
                body: $preview,
                url: $url,
                actorUserId: (int) Auth::id(),
            );
        }

        // @mention notifications — anyone tagged who isn't already notified.
        foreach (\App\Support\CommunityText::mentionedUserIds($comment->body) as $mid) {
            if ($mid === (int) Auth::id() || isset($targets[$mid])) {
                continue;
            }
            $this->notifications->notify(
                userId: $mid,
                type: 'mention',
                title: ($actor->full_name ?: 'A member') . ' mentioned you in a comment',
                body: $preview,
                url: $url,
                actorUserId: (int) Auth::id(),
            );
        }

        return response()->json([
            'success' => true,
            'message' => $parent ? 'Reply added.' : 'Comment added.',
            'data' => [
                'parentId' => $comment->parentId,
                'html' => view('community.connect.partials.wall-comment', [
                    'comment' => $comment->load('author'),
                    'isReply' => (bool) $comment->parentId,
                    'replies' => collect(),
                ])->render(),
            ],
        ]);
    }

    /**
     * All comments (with replies) for one post — feeds the "View all N
     * comments" modal on the wall, so the inline card only previews a couple.
     */
    public function comments(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->where('id', $postId)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }

        // Paginate top-level comments (oldest first) so a long thread expands
        // inline in chunks rather than loading everything at once.
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        $base = CommunityWallComment::active()->where('wallPostId', $post->id)->whereNull('parentId');
        /* Every comment on the post, answers included — the number the card
         * has always shown, because comment_count counts the same rows.
         * Counting only the top level here meant the card's number CHANGED
         * when the sheet opened: eight comments became three, and neither
         * number was wrong, they were answers to different questions. Paging
         * still walks the top level; it is threads that are paged. */
        $total = CommunityWallComment::active()->where('wallPostId', $post->id)->count();
        $top = $base->orderBy('id')->skip(($page - 1) * $perPage)->take($perPage + 1)->get();
        $hasMore = $top->count() > $perPage;
        $top = $top->take($perPage)->values();

        $replies = CommunityWallComment::active()
            ->where('wallPostId', $post->id)
            ->whereIn('parentId', $top->pluck('id')->all() ?: [-1])
            ->orderBy('id')
            ->get();

        $top->concat($replies)->load('author');
        \App\Models\CommunityReaction::attach($top->concat($replies), 'wallcomment', (int) Auth::id());

        $html = '';
        foreach ($top as $comment) {
            $html .= view('community.connect.partials.wall-comment', [
                'comment' => $comment,
                'isReply' => false,
                'replies' => $replies->where('parentId', $comment->id)->sortBy('id')->values(),
            ])->render();
        }

        return response()->json(['success' => true, 'data' => [
            'html' => $html,
            'hasMore' => $hasMore,
            'nextPage' => $page + 1,
            'total' => $total,
        ]]);
    }

    /**
     * Tombstone a member's own wall comment/reply: keep the row so replies
     * don't orphan, blank the body + photo, flag isDeleted. The UI then shows
     * "This comment was deleted".
     */
    public function deleteComment(Request $request, int $commentId)
    {
        $comment = CommunityWallComment::active()->where('id', $commentId)->first();
        if (! $comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found.'], 404);
        }
        if ((int) $comment->userId !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You can only delete your own comment.'], 403);
        }
        try {
            Storage::disk('public')->delete(array_filter(array_merge(
                $comment->shots(),
                [$comment->videoPath, $comment->videoPoster]
            )));
        } catch (\Throwable $e) {
            // Non-fatal — orphan files can be janitor-cleaned.
        }
        $comment->update(['isDeleted' => true, 'body' => '', 'imagePath' => null, 'videoPath' => null, 'videoPoster' => null]
            + (\Illuminate\Support\Facades\Schema::hasColumn($comment->getTable(), 'imagePaths') ? ['imagePaths' => null] : []));

        return response()->json(['success' => true, 'message' => 'Comment deleted.']);
    }

    public function deletePost(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->where('id', $postId)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }
        $canDelete = (int) $post->authorUserId === (int) Auth::id()
            || (int) $post->wallUserId === (int) Auth::id();
        if (! $canDelete) {
            return response()->json(['success' => false, 'message' => 'You cannot delete that post.'], 403);
        }

        DB::transaction(function () use ($post) {
            CommunityWallComment::where('wallPostId', $post->id)->update(['deleteStatus' => 0]);
            $post->update(['deleteStatus' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Post removed.']);
    }

    // ------------------------------------------------------------------

    /**
     * @return array{items:\Illuminate\Support\Collection, hasMore:bool}
     */
    private function pagePosts(int $wallUserId, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = CommunityWallPost::active()
            ->wallOnly()
            ->where('wallUserId', $wallUserId)
            ->with(['author', 'comments.author'])
            ->orderByDesc('id')
            ->skip($offset)
            ->take(self::PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::PER_PAGE;
        $items = $rows->take(self::PER_PAGE)->values();

        // Batch-attach reaction summaries to the posts and every loaded comment.
        $uid = (int) Auth::id();
        \App\Models\CommunityReaction::attach($items, 'wallpost', $uid);
        \App\Models\CommunityReaction::attach($items->flatMap->comments, 'wallcomment', $uid);

        return ['items' => $items, 'hasMore' => $hasMore];
    }

    private function storeImage($file, string $dir): string
    {
        // Auto-compress to WebP (animated GIFs pass through untouched).
        return \App\Support\MediaOptimizer::storeImageAsWebp($file, $dir);
    }

    /**
     * Compress an uploaded/recorded video to a streamable MP4 (+poster).
     *
     * @return array{video:string, poster:?string}
     */
    private function storeVideo($file, string $dir): array
    {
        return \App\Support\VideoOptimizer::storeCompressed($file, $dir . '/videos');
    }
}
