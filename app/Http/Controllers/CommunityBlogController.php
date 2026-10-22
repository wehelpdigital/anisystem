<?php

namespace App\Http\Controllers;

use App\Models\AsCommunityBlogComment;
use App\Models\AsCommunityBlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Technician's Blog (member-facing). Articles the anee.io team publishes from
 * the mother app; members read them and comment. Sits beside Discussions.
 */
class CommunityBlogController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        // The articles follow the public site's guides and blog.
        \App\Support\TechBlog::syncIfStale();

        /* An article is its title, the line under it, the words in it and
         * whoever wrote it — all four are searched, because a reader looking
         * for "the one about mechanisation" does not know which of them they
         * remember it from. % and _ are wildcards down in SQL and letters up
         * here: somebody asking about "50% urea" means fifty per cent. */
        $q = \Illuminate\Support\Str::limit(trim((string) $request->query('q', '')), 120, '');

        /* The public blog's categories (2026-10-23, the owner: "the
         * technicians blog has no categories yet"): each article carries its
         * site page's, and the chips are every category with a live article,
         * the one with the newest article first, as the public blog's run. */
        $cats = collect();
        if (\Illuminate\Support\Facades\Schema::hasColumn('as_community_blog_posts', 'category')) {
            $cats = AsCommunityBlogPost::active()->published()
                ->whereNotNull('category')->where('category', '!=', '')
                ->selectRaw('category, count(*) as n, max(publishedAt) as latest')
                ->groupBy('category')
                ->orderByDesc('latest')
                ->get()
                ->map(fn ($r) => ['name' => (string) $r->category, 'n' => (int) $r->n]);
        }
        $cat = trim((string) $request->query('cat', ''));
        if ($cat !== '' && ! $cats->contains('name', $cat)) {
            $cat = '';
        }

        $posts = AsCommunityBlogPost::active()
            ->published()
            ->when($cat !== '', fn ($sql) => $sql->where('category', $cat))
            ->when($q !== '', function ($sql) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $sql->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('body', 'like', $like)
                        ->orWhere('authorName', 'like', $like);
                });
            })
            ->withCount('comments')
            ->orderByDesc('publishedAt')
            ->orderByDesc('id')
            ->paginate(12)
            // Only the reader's own filters ride along: the live answer's
            // rows=1 must never end up in a page link.
            ->appends(array_filter(['q' => $q, 'cat' => $cat], fn ($v) => $v !== ''));

        // Opening the blog is reading it — the badge counts what was
        // published while nobody was looking, not what remains unopened.
        app(\App\Services\CommunityUnreadService::class)
            ->markRead(\App\Services\CommunityUnreadService::KIND_BLOG);

        /* The live search asks for the cards alone: the page it is standing
         * on is already drawn, and everything else in this answer — the
         * name plate, the nav, the scripts — it already has. */
        if ($request->boolean('rows')) {
            return response()->json(['success' => true, 'data' => [
                'html' => view('community.blog.partials.cards', ['posts' => $posts])->render(),
                'pager' => $posts->hasPages() ? $posts->links('community.partials.blog-pagination')->toHtml() : '',
                'count' => $posts->count(),
                'total' => $posts->total(),
                'hasMore' => $posts->hasMorePages(),
                'q' => $q,
                'cat' => $cat,
            ]]);
        }

        // "All" counts every live article, a hand written one with no category too.
        $all = ($cat === '' && $q === '') ? $posts->total() : AsCommunityBlogPost::active()->published()->count();

        return view('community.blog.index', ['posts' => $posts, 'q' => $q, 'cats' => $cats, 'cat' => $cat, 'all' => $all]);
    }

    public function show(int $id)
    {
        \App\Support\TechBlog::syncIfStale();

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

        // The reading page (2026-10-23, the owner: "fix how each blog looks when you read
        // it"): how long it takes, where it lives on the public site (the link a member
        // can share with a farmer who has no account), and what to read next.
        $minutes = max(1, (int) ceil(str_word_count(strip_tags((string) $post->body)) / 200));
        $publicUrl = null;
        if ($post->sitePageId) {
            $page = \App\Models\AsSitePage::live()->where('id', $post->sitePageId)->first(['id', 'section', 'slug']);
            $publicUrl = $page ? url(\App\Support\SitePages::url($page->section, $page->slug)) : null;
        }
        $next = AsCommunityBlogPost::active()->published()
            ->where('id', '!=', $post->id)
            ->when($post->category, fn ($sql) => $sql->where('category', $post->category))
            ->withCount('comments')
            ->orderByDesc('publishedAt')->orderByDesc('id')
            ->limit(3)->get();

        return view('community.blog.show', [
            'post' => $post,
            'comments' => $comments,
            'minutes' => $minutes,
            'publicUrl' => $publicUrl,
            'next' => $next,
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
