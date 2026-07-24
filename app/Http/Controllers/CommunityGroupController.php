<?php

namespace App\Http\Controllers;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Services\NotificationService;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Community Groups: members start a group, join others, post a topic and
 * reply to each other. Posts load a page at a time (FB-style "load more").
 */
class CommunityGroupController extends Controller
{
    private const POSTS_PER_PAGE = 8;

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /** All groups, with membership + counts, plus the ones you're in. */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $groups = CommunityGroup::active()
            ->withCount([
                'members as member_count',
                'posts as post_count',
            ])
            ->orderByDesc('id')
            ->get();

        $myGroupIds = CommunityGroupMember::active()
            ->where('userId', $userId)
            ->pluck('groupId')
            ->all();

        foreach ($groups as $g) {
            $g->joined = in_array($g->id, $myGroupIds, true);
        }

        return view('community.groups.index', [
            'groups' => $groups,
            'myGroupIds' => $myGroupIds,
        ]);
    }

    /** One group: its posts (first page) and the composer if you're a member. */
    public function show(Request $request, int $id)
    {
        $group = $this->group($id);
        $userId = Auth::id();
        $isMember = $this->isMember($group->id, $userId);

        $posts = $this->pagePosts($group->id, 1);

        return view('community.groups.show', [
            'group' => $group,
            'isMember' => $isMember,
            'isOwner' => (int) $group->createdByUserId === (int) $userId,
            'memberCount' => $group->members()->count(),
            'posts' => $posts['items'],
            'hasMore' => $posts['hasMore'],
        ]);
    }

    /** JSON page of posts for "load more". */
    public function posts(Request $request, int $id)
    {
        $group = $this->group($id);
        $page = max(1, (int) $request->query('page', 1));
        $posts = $this->pagePosts($group->id, $page);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => view('community.groups.partials.posts', ['posts' => $posts['items'], 'group' => $group])->render(),
                'hasMore' => $posts['hasMore'],
                'nextPage' => $page + 1,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $group = null;
        DB::transaction(function () use ($data, &$group) {
            $group = CommunityGroup::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'createdByUserId' => Auth::id(),
                'deleteStatus' => 1,
            ]);
            CommunityGroupMember::create([
                'groupId' => $group->id,
                'userId' => Auth::id(),
                'role' => 'owner',
                'deleteStatus' => 1,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Group created.',
            'data' => ['url' => route('community.groups.show', ['id' => $group->id])],
        ]);
    }

    public function join(Request $request, int $id)
    {
        $group = $this->group($id);
        CommunityGroupMember::updateOrCreate(
            ['groupId' => $group->id, 'userId' => Auth::id()],
            ['role' => 'member', 'deleteStatus' => 1]
        );

        return response()->json(['success' => true, 'message' => 'Joined ' . $group->name . '.']);
    }

    public function leave(Request $request, int $id)
    {
        $group = $this->group($id);
        if ((int) $group->createdByUserId === (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'The owner cannot leave their own group.'], 422);
        }
        CommunityGroupMember::where('groupId', $group->id)->where('userId', Auth::id())->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Left ' . $group->name . '.']);
    }

    public function storePost(Request $request, int $id)
    {
        $group = $this->group($id);
        if (! $this->isMember($group->id, Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Join the group to post.'], 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:191',
            'body' => 'required|string|max:8000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'), 'community-groups/' . $group->id);
        }

        $post = CommunityGroupPost::create([
            'groupId' => $group->id,
            'userId' => Auth::id(),
            'title' => $request->input('title') ?: null,
            'body' => $request->input('body'),
            'imagePath' => $imagePath,
            'deleteStatus' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posted.',
            'data' => ['html' => view('community.groups.partials.posts', ['posts' => collect([$post->load('author')]), 'group' => $group])->render()],
        ]);
    }

    public function storeReply(Request $request, int $postId)
    {
        $post = CommunityGroupPost::active()->where('id', $postId)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }
        if (! $this->isMember($post->groupId, Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Join the group to reply.'], 403);
        }

        $request->validate(['body' => 'required|string|max:4000']);

        $reply = CommunityGroupReply::create([
            'postId' => $post->id,
            'userId' => Auth::id(),
            'body' => $request->input('body'),
            'deleteStatus' => 1,
        ]);

        // Tell the topic author someone replied.
        $actor = Auth::user();
        $this->notifications->notify(
            userId: (int) $post->userId,
            type: 'reply',
            title: ($actor->full_name ?: 'A member') . ' replied in a group',
            body: Str::limit(trim(strip_tags($request->input('body'))), 90),
            url: route('community.groups.show', ['id' => $post->groupId]),
            actorUserId: (int) Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Reply posted.',
            'data' => ['html' => view('community.groups.partials.reply', ['reply' => $reply->load('author')])->render()],
        ]);
    }

    public function deletePost(Request $request, int $postId)
    {
        $post = CommunityGroupPost::active()->where('id', $postId)->first();
        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }
        $group = CommunityGroup::active()->where('id', $post->groupId)->first();
        $canDelete = (int) $post->userId === (int) Auth::id()
            || ($group && (int) $group->createdByUserId === (int) Auth::id());
        if (! $canDelete) {
            return response()->json(['success' => false, 'message' => 'You cannot delete that post.'], 403);
        }

        DB::transaction(function () use ($post) {
            CommunityGroupReply::where('postId', $post->id)->update(['deleteStatus' => 0]);
            $post->update(['deleteStatus' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Post removed.']);
    }

    // ------------------------------------------------------------------

    private function group(int $id): CommunityGroup
    {
        $group = CommunityGroup::active()->where('id', $id)->first();
        if (! $group) {
            abort(404);
        }

        return $group;
    }

    private function isMember(int $groupId, int $userId): bool
    {
        return CommunityGroupMember::active()
            ->where('groupId', $groupId)
            ->where('userId', $userId)
            ->exists();
    }

    /**
     * @return array{items:\Illuminate\Support\Collection, hasMore:bool}
     */
    private function pagePosts(int $groupId, int $page): array
    {
        $offset = ($page - 1) * self::POSTS_PER_PAGE;
        $rows = CommunityGroupPost::active()
            ->where('groupId', $groupId)
            ->with(['author', 'replies.author'])
            ->orderByDesc('id')
            ->skip($offset)
            ->take(self::POSTS_PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::POSTS_PER_PAGE;

        return ['items' => $rows->take(self::POSTS_PER_PAGE)->values(), 'hasMore' => $hasMore];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'group';
        $slug = $base;
        $i = 1;
        while (CommunityGroup::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function storeImage($file, string $dir): string
    {
        $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp']);
        $stem = Str::uuid()->toString();
        Storage::disk('public')->putFileAs($dir, $file, $stem . '.' . $ext);

        return $dir . '/' . $stem . '.' . $ext;
    }
}
