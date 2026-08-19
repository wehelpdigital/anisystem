<?php

namespace App\Http\Controllers;

use App\Models\AsCommunityBlogComment;
use App\Models\AsCommunityBlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Technician's Blog (member-facing). Articles the AniSenso team publishes from
 * the mother app; members read them and comment. Sits beside Discussions.
 */
class CommunityBlogController extends Controller
{
    public function index()
    {
        $posts = AsCommunityBlogPost::active()
            ->published()
            ->withCount('comments')
            ->orderByDesc('publishedAt')
            ->orderByDesc('id')
            ->paginate(12);

        // Opening the blog is reading it — the badge counts what was
        // published while nobody was looking, not what remains unopened.
        app(\App\Services\CommunityUnreadService::class)
            ->markRead(\App\Services\CommunityUnreadService::KIND_BLOG);

        return view('community.blog.index', ['posts' => $posts]);
    }

    public function show(int $id)
    {
        $post = AsCommunityBlogPost::active()->published()->where('id', $id)->first();
        if (! $post) {
            abort(404);
        }

        // Cheap view counter (not deduped — good enough for a reading metric).
        $post->increment('viewCount');

        // Newest 150, oldest-first — a popular post's thread was unbounded,
        // and this page also carries the article itself. Deleted-author
        // filtering happens after, so the page can run a little short rather
        // than a query per comment.
        $comments = $post->comments()
            ->with('author')
            ->latest('id')
            ->limit(150)
            ->get()
            ->reverse()
            ->filter(fn ($c) => $c->author && (int) $c->author->deleteStatus === 1)
            ->values();

        \App\Models\CommunityReaction::attach($comments, 'blogcomment', (int) Auth::id());

        return view('community.blog.show', [
            'post' => $post,
            'comments' => $comments,
        ]);
    }

    public function comment(Request $request, int $id)
    {
        $post = AsCommunityBlogPost::active()->published()->where('id', $id)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Article not found.'], 404);
        }

        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:8192',
        ]);
        $body = trim((string) ($data['body'] ?? ''));

        // Photos are compressed to WebP on upload like every community image.
        $imagePath = $request->hasFile('image')
            ? \App\Support\MediaOptimizer::storeImageAsWebp($request->file('image'), 'community/blog-comments')
            : null;

        if ($body === '' && ! $imagePath) {
            return response()->json(['success' => false, 'message' => 'Write a comment or add a photo.'], 422);
        }

        $comment = AsCommunityBlogComment::create([
            'blogPostId' => $post->id,
            'userId' => (int) Auth::id(),
            'body' => $body,
            'imagePath' => $imagePath,
            'deleteStatus' => 1,
        ]);
        $comment->setRelation('author', Auth::user());
        $comment->reactionSummary = ['counts' => [], 'mine' => null];

        return response()->json([
            'success' => true,
            'message' => 'Comment posted.',
            'data' => ['html' => view('community.blog.partials.comment', ['comment' => $comment])->render()],
        ]);
    }
}
