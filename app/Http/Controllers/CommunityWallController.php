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
            'body' => 'required_without:image|nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'), 'community-wall/' . $wallOwner->id);
        }

        $post = CommunityWallPost::create([
            'wallUserId' => $wallOwner->id,
            'authorUserId' => Auth::id(),
            'body' => $request->input('body') ?: null,
            'imagePath' => $imagePath,
            'deleteStatus' => 1,
        ]);

        // Tell the wall owner someone posted (unless it's their own wall).
        $actor = Auth::user();
        $this->notifications->notify(
            userId: $wallOwner->id,
            type: 'wall',
            title: ($actor->full_name ?: 'A member') . ' posted on your wall',
            body: Str::limit(trim(strip_tags((string) $request->input('body'))) ?: 'Shared a photo.', 90),
            url: route('community.connect.profile', ['userId' => $wallOwner->id]),
            actorUserId: (int) Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Posted.',
            'data' => ['html' => view('community.connect.partials.wall-posts', ['posts' => collect([$post->load('author')])])->render()],
        ]);
    }

    public function storeComment(Request $request, int $postId)
    {
        $post = CommunityWallPost::active()->where('id', $postId)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }
        $request->validate(['body' => 'required|string|max:2000']);

        $comment = CommunityWallComment::create([
            'wallPostId' => $post->id,
            'userId' => Auth::id(),
            'body' => $request->input('body'),
            'deleteStatus' => 1,
        ]);

        $actor = Auth::user();
        // Notify the post author (if someone else).
        $this->notifications->notify(
            userId: (int) $post->authorUserId,
            type: 'comment',
            title: ($actor->full_name ?: 'A member') . ' commented on your post',
            body: Str::limit(trim(strip_tags($request->input('body'))), 90),
            url: route('community.connect.profile', ['userId' => $post->wallUserId]),
            actorUserId: (int) Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added.',
            'data' => ['html' => view('community.connect.partials.wall-comment', ['comment' => $comment->load('author')])->render()],
        ]);
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
            ->where('wallUserId', $wallUserId)
            ->with(['author', 'comments.author'])
            ->orderByDesc('id')
            ->skip($offset)
            ->take(self::PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::PER_PAGE;

        return ['items' => $rows->take(self::PER_PAGE)->values(), 'hasMore' => $hasMore];
    }

    private function storeImage($file, string $dir): string
    {
        $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp']);
        $stem = Str::uuid()->toString();
        Storage::disk('public')->putFileAs($dir, $file, $stem . '.' . $ext);

        return $dir . '/' . $stem . '.' . $ext;
    }
}
