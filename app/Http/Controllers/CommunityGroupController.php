<?php

namespace App\Http\Controllers;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupMessage;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    /** Discussions per page — the same handful the room's posts arrive in. */
    private const GROUPS_PER_PAGE = 8;

    public function index(Request $request)
    {
        $userId = Auth::id();
        $page = $this->pageGroups($userId, 1);

        return view('community.groups.index', [
            'groups' => $page['items'],
            'hasMore' => $page['hasMore'],
            'myGroupIds' => $page['myGroupIds'],
        ]);
    }

    /** JSON page of discussion cards for "load more". */
    public function groupsPage(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $data = $this->pageGroups(Auth::id(), $page);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => view('community.groups.partials.cards', ['groups' => $data['items']])->render(),
                'hasMore' => $data['hasMore'],
                'nextPage' => $page + 1,
            ],
        ]);
    }

    /**
     * One page of discussions, newest first, each told whether the viewer is
     * already in it.
     *
     * Asked for one more row than the page holds: that surplus is the whole
     * answer to "is there another page", and it costs nothing next to a
     * second count query.
     *
     * @return array{items:\Illuminate\Support\Collection, hasMore:bool, myGroupIds:array<int,int>}
     */
    private function pageGroups(int $userId, int $page): array
    {
        $per = self::GROUPS_PER_PAGE;
        $rows = CommunityGroup::active()
            ->withCount([
                'members as member_count',
                'posts as post_count',
                // How much talking has actually happened in here — the owner
                // asked for it beside the topics and the members.
                'replies as reply_count',
            ])
            ->orderByDesc('id')
            ->skip(($page - 1) * $per)
            ->take($per + 1)
            ->get();

        $hasMore = $rows->count() > $per;
        $items = $rows->take($per)->values();

        // What is new in the rooms this member joined — the list is where a
        // farmer decides which conversation to walk back into.
        $unreadByGroup = app(\App\Services\CommunityUnreadService::class)->discussionCounts($userId);

        $myGroupIds = CommunityGroupMember::active()
            ->where('userId', $userId)
            ->pluck('groupId')
            ->all();

        foreach ($items as $g) {
            $g->joined = in_array($g->id, $myGroupIds, true);
            $g->unreadCount = $unreadByGroup[(int) $g->id] ?? 0;
        }

        return ['items' => $items, 'hasMore' => $hasMore, 'myGroupIds' => $myGroupIds];
    }

    /** One group: its posts (first page) and the composer if you're a member. */
    public function show(Request $request, int $id)
    {
        $group = $this->group($id);
        $userId = Auth::id();
        $isMember = $this->isMember($group->id, $userId);

        $posts = $this->pagePosts($group->id, 1);
        $this->withReactions($posts['items']);

        // Being here IS reading it: the room's badge clears on arrival, and
        // only for somebody who actually joined (a visitor has no badge).
        if ($isMember) {
            app(\App\Services\CommunityUnreadService::class)
                ->markRead(\App\Services\CommunityUnreadService::KIND_GROUP, $group->id);
        }

        return view('community.groups.show', [
            'group' => $group,
            'isMember' => $isMember,
            'isOwner' => (int) $group->createdByUserId === (int) $userId,
            // Whoever started the room keeps it; the house can fix any of them.
            'canEditGroup' => $this->canEditGroup($group),
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
        $this->withReactions($posts['items']);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => view('community.groups.partials.posts', ['posts' => $posts['items'], 'group' => $group])->render(),
                'hasMore' => $posts['hasMore'],
                'nextPage' => $page + 1,
            ],
        ]);
    }

    /**
     * Whether this account may change the room: the member who started it, or
     * a mother-site admin bridged in (who runs the platform and has to be able
     * to fix a name nobody else can).
     */
    private function canEditGroup(CommunityGroup $group): bool
    {
        $user = Auth::user();

        return $user !== null
            && ((int) $group->createdByUserId === (int) $user->id || $user->isSuperAdmin());
    }

    /** Rename a discussion, or give it a new face and banner. */
    public function update(Request $request, int $id)
    {
        $group = $this->group($id);
        if (! $this->canEditGroup($group)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the one who started this discussion can change it.',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $patch = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ];
        // A picture left alone keeps the one it had: an edit that quietly
        // cleared the banner because the field was empty would be a trap.
        if ($request->hasFile('image')) {
            $patch['coverImagePath'] = $this->storeImage($request->file('image'), 'community-groups/covers');
        }
        if ($request->hasFile('banner')) {
            $patch['bannerImagePath'] = $this->storeImage($request->file('banner'), 'community-groups/banners');
        }
        $group->update($patch);

        return response()->json(['success' => true, 'message' => 'Discussion updated.', 'data' => [
            'name' => $group->name,
            'description' => $group->description,
            'cover' => \App\Support\MediaStore::url($group->coverImagePath),
            'banner' => \App\Support\MediaStore::url($group->bannerImagePath),
        ]]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            // Two pictures, two jobs: the badge is the room's face in a list,
            // the banner is what makes its own page look like somewhere.
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $coverPath = null;
        if ($request->hasFile('image')) {
            $coverPath = $this->storeImage($request->file('image'), 'community-groups/covers');
        }
        $bannerPath = null;
        if ($request->hasFile('banner')) {
            $bannerPath = $this->storeImage($request->file('banner'), 'community-groups/banners');
        }

        $group = null;
        DB::transaction(function () use ($data, $coverPath, $bannerPath, &$group) {
            $group = CommunityGroup::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'coverImagePath' => $coverPath,
                'bannerImagePath' => $bannerPath,
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
            'message' => 'Discussion created.',
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
            return response()->json(['success' => false, 'message' => 'The owner cannot leave their own discussion.'], 422);
        }
        CommunityGroupMember::where('groupId', $group->id)->where('userId', Auth::id())->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Left ' . $group->name . '.']);
    }

    public function storePost(Request $request, int $id)
    {
        $group = $this->group($id);
        if (! $this->isMember($group->id, Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Join the discussion to post.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:191',
            'body' => 'required|string|max:20000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
            // A topic can carry a clip, uploaded or filmed on the spot — the
            // wall has always allowed it and a discussion is where a farmer
            // most wants to SHOW the problem. VideoOptimizer enforces the mime.
            'video' => 'nullable|file|max:307200',
        ]);
        // The body arrives as WYSIWYG HTML — sanitize to a safe subset on store.
        $safeBody = \App\Support\CommunityText::safeHtml($request->input('body'));
        if (trim(strip_tags($safeBody)) === '') {
            return response()->json(['success' => false, 'message' => 'Write something first.'], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'), 'community-groups/' . $group->id);
        }

        $videoPath = null;
        $videoPoster = null;
        if ($request->hasFile('video')) {
            try {
                $stored = \App\Support\VideoOptimizer::storeCompressed(
                    $request->file('video'),
                    'community-groups/' . $group->id . '/videos'
                );
                $videoPath = $stored['video'];
                $videoPoster = $stored['poster'] ?? null;
            } catch (\Throwable $e) {
                // The clip is the reason the post exists often enough that
                // saving it without one would be the wrong kind of helpful.
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $post = CommunityGroupPost::create([
            'groupId' => $group->id,
            'userId' => Auth::id(),
            'title' => $request->input('title') ?: null,
            'body' => $safeBody,
            'imagePath' => $imagePath,
            'videoPath' => $videoPath,
            'videoPoster' => $videoPoster,
            'deleteStatus' => 1,
        ]);

        // @mention notifications for anyone tagged in the topic body.
        $actor = Auth::user();
        $url = route('community.groups.show', ['id' => $group->id]);
        foreach (\App\Support\CommunityText::mentionedUserIds($post->body) as $mid) {
            if ($mid === (int) Auth::id()) {
                continue;
            }
            $this->notifications->notify(
                userId: $mid,
                type: 'mention',
                title: ($actor->full_name ?: 'A member') . ' mentioned you in ' . $group->name,
                body: Str::limit(trim(strip_tags((string) $post->body)), 90),
                url: $url,
                actorUserId: (int) Auth::id(),
            );
        }

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
            return response()->json(['success' => false, 'message' => 'Join the discussion to reply.'], 403);
        }

        $request->validate([
            'body' => 'required_without:image|nullable|string|max:4000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
            'parentId' => 'nullable|integer',
        ]);

        // Replying to a reply: threads stay two levels deep, so it re-attaches
        // to the thread's top reply.
        $parent = null;
        if ($request->filled('parentId')) {
            $parent = CommunityGroupReply::active()
                ->where('id', (int) $request->input('parentId'))
                ->where('postId', $post->id)
                ->first();
            if (! $parent) {
                return response()->json(['success' => false, 'message' => 'Reply not found.'], 404);
            }
            if ($parent->parentId) {
                $parent = CommunityGroupReply::active()->find($parent->parentId) ?: $parent;
            }
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'), 'community-groups/' . $post->groupId);
        }

        $reply = CommunityGroupReply::create([
            'postId' => $post->id,
            'parentId' => $parent?->id,
            'userId' => Auth::id(),
            'body' => $request->input('body') ?: '',
            'imagePath' => $imagePath,
            'deleteStatus' => 1,
        ]);

        // Tell the parent reply's author (on nested replies) and the topic
        // author — each once, never the actor.
        $actor = Auth::user();
        $preview = Str::limit(trim(strip_tags((string) $request->input('body'))) ?: 'Shared a photo.', 90);
        $url = route('community.groups.show', ['id' => $post->groupId]);
        $targets = [];
        if ($parent && (int) $parent->userId !== (int) Auth::id()) {
            $targets[(int) $parent->userId] = ' replied to your comment';
        }
        if ((int) $post->userId !== (int) Auth::id() && ! isset($targets[(int) $post->userId])) {
            $targets[(int) $post->userId] = ' replied in a group';
        }
        foreach ($targets as $targetId => $verb) {
            $this->notifications->notify(
                userId: $targetId,
                type: 'reply',
                title: ($actor->full_name ?: 'A member') . $verb,
                body: $preview,
                url: $url,
                actorUserId: (int) Auth::id(),
            );
        }

        // @mention notifications — anyone tagged who isn't already notified.
        foreach (\App\Support\CommunityText::mentionedUserIds($reply->body) as $mid) {
            if ($mid === (int) Auth::id() || isset($targets[$mid])) {
                continue;
            }
            $this->notifications->notify(
                userId: $mid,
                type: 'mention',
                title: ($actor->full_name ?: 'A member') . ' mentioned you in a reply',
                body: $preview,
                url: $url,
                actorUserId: (int) Auth::id(),
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply posted.',
            'data' => [
                'parentId' => $reply->parentId,
                'html' => view('community.groups.partials.reply', [
                    'reply' => $reply->load('author'),
                    'isReply' => (bool) $reply->parentId,
                    'children' => collect(),
                ])->render(),
            ],
        ]);
    }

    /**
     * Tombstone a member's own group reply: keep the row (replies to it stay
     * anchored), blank the body + photo, flag isDeleted. UI shows the "deleted"
     * placeholder.
     */
    public function deleteReply(Request $request, int $replyId)
    {
        $reply = CommunityGroupReply::where('deleteStatus', 1)->where('id', $replyId)->first();
        if (! $reply) {
            return response()->json(['success' => false, 'message' => 'Reply not found.'], 404);
        }
        if ((int) $reply->userId !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You can only delete your own reply.'], 403);
        }
        if ($reply->imagePath) {
            try {
                Storage::disk('public')->delete($reply->imagePath);
            } catch (\Throwable $e) {
                // Non-fatal.
            }
        }
        $reply->update(['isDeleted' => true, 'body' => '', 'imagePath' => null]);

        return response()->json(['success' => true, 'message' => 'Reply deleted.']);
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

    /** Proxy GIF search to Giphy so the API key never reaches the browser. */
    public function gifSearch(Request $request)
    {
        $key = config('services.giphy.key');
        if (! $key) {
            return response()->json(['success' => false, 'message' => 'GIF search is not configured.'], 422);
        }

        $q = trim((string) $request->query('q', ''));
        $endpoint = $q === '' ? 'https://api.giphy.com/v1/gifs/trending' : 'https://api.giphy.com/v1/gifs/search';
        try {
            $res = Http::timeout(10)->get($endpoint, array_filter([
                'api_key' => $key,
                'q' => $q ?: null,
                'limit' => 24,
                'rating' => 'g',
            ]));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'GIF search is unreachable right now.'], 502);
        }
        if (! $res->successful()) {
            return response()->json(['success' => false, 'message' => 'GIF search failed.'], 502);
        }

        $gifs = collect($res->json('data') ?? [])->map(fn ($g) => [
            'id' => $g['id'] ?? '',
            'preview' => $g['images']['fixed_width_small']['url'] ?? ($g['images']['fixed_width']['url'] ?? null),
            'url' => $g['images']['downsized']['url'] ?? ($g['images']['original']['url'] ?? null),
        ])->filter(fn ($g) => $g['preview'] && $g['url'])->values();

        return response()->json(['success' => true, 'data' => ['gifs' => $gifs]]);
    }

    /**
     * Download a chosen Giphy GIF and store it like any uploaded image, so
     * feeds (and the admin app) keep rendering a plain local file. Only
     * giphy.com media is fetched, capped at 8 MB, and the payload must
     * actually be a GIF (magic bytes) — no open proxying.
     */
    private function storeGiphyGif(string $url, string $dir): ?string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if (! preg_match('/(^|\.)giphy\.com$/i', $host)) {
            return null;
        }
        try {
            $res = Http::timeout(15)->get($url);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $res->successful()) {
            return null;
        }
        $bytes = $res->body();
        if (strlen($bytes) < 100 || strlen($bytes) > 8 * 1024 * 1024 || substr($bytes, 0, 3) !== 'GIF') {
            return null;
        }
        $path = $dir . '/' . Str::uuid()->toString() . '.gif';
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    /**
     * Tap-to-react on a post or reply: same reaction again removes it,
     * a different one switches. Returns fresh counts + the caller's own.
     */
    public function react(Request $request)
    {
        $data = $request->validate([
            'targetType' => 'required|in:post,reply,wallpost,wallcomment,blogcomment',
            'targetId' => 'required|integer',
            'reaction' => 'required|in:' . implode(',', \App\Models\CommunityReaction::REACTIONS),
        ]);

        // Same reaction table serves group posts/replies, wall posts/comments,
        // and Technician's Blog comments.
        $target = match ($data['targetType']) {
            'post' => CommunityGroupPost::active()->where('id', $data['targetId'])->first(),
            'reply' => CommunityGroupReply::where('deleteStatus', 1)->where('id', $data['targetId'])->first(),
            'wallpost' => \App\Models\CommunityWallPost::where('deleteStatus', 1)->where('id', $data['targetId'])->first(),
            'wallcomment' => \App\Models\CommunityWallComment::where('deleteStatus', 1)->where('id', $data['targetId'])->first(),
            'blogcomment' => \App\Models\AsCommunityBlogComment::where('deleteStatus', 1)->where('id', $data['targetId'])->first(),
            default => null,
        };
        if (! $target) {
            return response()->json(['success' => false, 'message' => 'That post is gone.'], 404);
        }

        $userId = (int) Auth::id();
        $existing = \App\Models\CommunityReaction::where('targetType', $data['targetType'])
            ->where('targetId', $data['targetId'])
            ->where('userId', $userId)
            ->first();

        if ($existing && $existing->reaction === $data['reaction']) {
            $existing->delete();
            $mine = null;
        } elseif ($existing) {
            $existing->update(['reaction' => $data['reaction']]);
            $mine = $data['reaction'];
        } else {
            \App\Models\CommunityReaction::create([
                'targetType' => $data['targetType'],
                'targetId' => $data['targetId'],
                'userId' => $userId,
                'reaction' => $data['reaction'],
            ]);
            $mine = $data['reaction'];
        }

        // Somebody reacting to your post is worth hearing about the moment it
        // happens — it is the commonest thing anyone does on a wall, and it
        // was the only one that happened silently. Only on the way ON: taking
        // a reaction back, or swapping one for another, is not news, and
        // notifying every hesitation would make the bell useless.
        if ($mine !== null && ! $existing) {
            $this->tellAboutReaction($data['targetType'], $target, $userId, $mine);
        }

        $summary = \App\Models\CommunityReaction::summaryFor($data['targetType'], [$data['targetId']], $userId);

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => $summary[$data['targetId']]['counts'] ?? new \stdClass(),
                'mine' => $mine,
            ],
        ]);
    }

    /**
     * Tell the author, and give the bell somewhere to land.
     *
     * Five different things can be reacted to and they are linked five
     * different ways, so the URL is worked out per kind rather than guessed —
     * a notification that opens the wrong page is worse than none, because
     * the reader has to go and find the thing themselves anyway.
     */
    private function tellAboutReaction(string $type, $target, int $actorId, string $reaction): void
    {
        $authorId = (int) ($type === 'wallpost' ? $target->authorUserId : $target->userId);
        if ($authorId === 0 || $authorId === $actorId) {
            return;                          // reacting to your own says nothing
        }

        $url = match ($type) {
            'post' => route('community.groups.show', ['groupId' => $target->groupId]) . '#post-' . $target->id,
            'reply' => (function () use ($target) {
                $post = CommunityGroupPost::active()->find($target->postId);
                return $post
                    ? route('community.groups.show', ['groupId' => $post->groupId]) . '#post-' . $post->id
                    : route('community.index');
            })(),
            // `wallpost-<id>` is the id feed-post.blade.php actually renders;
            // an anchor that matches nothing quietly drops the reader at the
            // top of a long wall to go hunting.
            'wallpost' => route('community.connect.profile', ['userId' => $target->wallUserId]) . '#wallpost-' . $target->id,
            'wallcomment' => (function () use ($target) {
                $post = \App\Models\CommunityWallPost::where('deleteStatus', 1)->find($target->wallPostId);
                return $post
                    ? route('community.connect.profile', ['userId' => $post->wallUserId]) . '#wallpost-' . $post->id
                    : route('community.index');
            })(),
            'blogcomment' => route('community.blog.show', ['id' => $target->blogPostId]),
            default => route('community.index'),
        };

        $actor = \App\Models\User::find($actorId);
        $what = match ($type) {
            'post', 'wallpost' => 'your post',
            'reply' => 'your reply',
            default => 'your comment',
        };

        app(\App\Services\NotificationService::class)->notify(
            $authorId,
            'reaction',
            ($actor?->full_name ?: 'Someone') . ' reacted to ' . $what,
            \App\Support\CommunityText::plain($target->body ?? '', 90),
            $url,
            $actorId,
            null,
            // One line per person per hour: a wall post can collect a dozen
            // reactions in a minute and each is not its own errand.
            1,
        );
    }

    /** Attach reactionSummary to posts and their replies before rendering. */
    private function withReactions($posts): void
    {
        $userId = (int) Auth::id();
        $postIds = collect($posts)->pluck('id')->all();
        $replyIds = collect($posts)->flatMap(fn ($p) => $p->replies ? $p->replies->pluck('id') : collect())->all();
        $postMap = \App\Models\CommunityReaction::summaryFor('post', $postIds, $userId);
        $replyMap = \App\Models\CommunityReaction::summaryFor('reply', $replyIds, $userId);
        foreach ($posts as $p) {
            $p->reactionSummary = $postMap[$p->id] ?? ['counts' => [], 'mine' => null];
            if ($p->replies) {
                foreach ($p->replies as $r) {
                    $r->reactionSummary = $replyMap[$r->id] ?? ['counts' => [], 'mine' => null];
                }
            }
        }
    }

    // ------------------------------------------------------------------

    /**
     * Group chat messages. Initial load (after=0) returns the most recent
     * window; subsequent polls pass ?after=<lastId> for just the new ones.
     * Members only.
     */
    public function chatMessages(Request $request, int $id)
    {
        $group = $this->group($id);
        $meId = (int) Auth::id();
        if (! $this->isMember($group->id, $meId)) {
            return response()->json(['success' => false, 'message' => 'Join the group to see the chat.'], 403);
        }

        $after = (int) $request->query('after', 0);
        if ($after > 0) {
            $rows = CommunityGroupMessage::where('groupId', $group->id)->where('deleteStatus', 1)
                ->where('id', '>', $after)->orderBy('id')->limit(100)->get();
        } else {
            $rows = CommunityGroupMessage::where('groupId', $group->id)->where('deleteStatus', 1)
                ->orderByDesc('id')->limit(60)->get()->sortBy('id')->values();
        }

        $users = User::whereIn('id', $rows->pluck('userId')->unique()->values() ?: [0])
            ->get()->keyBy('id');

        $items = $rows->map(fn ($m) => $this->presentChatMessage($m, $users->get($m->userId), $meId))->values();
        $maxId = (int) (CommunityGroupMessage::where('groupId', $group->id)->where('deleteStatus', 1)->max('id') ?? 0);

        return response()->json(['success' => true, 'data' => ['messages' => $items, 'maxId' => $maxId]]);
    }

    /** Group members with presence (online/offline) for the chat sidebar. */
    public function chatMembers(Request $request, int $id)
    {
        $group = $this->group($id);
        $meId = (int) Auth::id();
        if (! $this->isMember($group->id, $meId)) {
            return response()->json(['success' => false, 'message' => 'Members only.'], 403);
        }

        $memberIds = CommunityGroupMember::active()->where('groupId', $group->id)->pluck('userId');
        $users = User::whereIn('id', $memberIds->all() ?: [0])
            ->where('deleteStatus', 1)
            ->orderBy('firstName')
            ->get();

        $items = $users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->full_name,
            'avatar' => $u->avatarPath ? \App\Support\MediaStore::url($u->avatarPath) : null,
            'initials' => $u->initials,
            'online' => $u->isOnline(),
            'allowMessages' => (bool) $u->allowMessages,
            'isMe' => (int) $u->id === $meId,
        ])->sortByDesc('online')->values();

        return response()->json(['success' => true, 'data' => [
            'members' => $items,
            'online' => $items->where('online', true)->count(),
            'total' => $items->count(),
        ]]);
    }

    /** Post a chat message (text and/or a photo). Members only. */
    public function chatSend(Request $request, int $id)
    {
        $group = $this->group($id);
        $meId = (int) Auth::id();
        if (! $this->isMember($group->id, $meId)) {
            return response()->json(['success' => false, 'message' => 'Join the group to chat.'], 403);
        }

        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:8192',
            'video' => 'nullable|file|max:307200', // 300 MB; VideoOptimizer enforces the mime
        ]);
        $body = trim((string) ($data['body'] ?? ''));
        $imagePath = $request->hasFile('image')
            ? \App\Support\MediaOptimizer::storeImageAsWebp($request->file('image'), 'community/group-chat/' . $group->id)
            : null;

        $videoPath = $videoPoster = null;
        if ($request->hasFile('video')) {
            try {
                $vid = \App\Support\VideoOptimizer::storeCompressed($request->file('video'), 'community/group-chat/' . $group->id . '/videos');
                $videoPath = $vid['video'];
                $videoPoster = $vid['poster'];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        if ($body === '' && ! $imagePath && ! $videoPath) {
            return response()->json(['success' => false, 'message' => 'Write a message or add a photo/video.'], 422);
        }

        $msg = CommunityGroupMessage::create([
            'groupId' => $group->id,
            'userId' => $meId,
            'body' => $body,
            'imagePath' => $imagePath,
            'videoPath' => $videoPath,
            'videoPoster' => $videoPoster,
            'deleteStatus' => 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->presentChatMessage($msg, Auth::user(), $meId),
        ]);
    }

    private function presentChatMessage(CommunityGroupMessage $m, ?User $user, int $meId): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'image' => $m->imagePath ? \App\Support\MediaStore::url($m->imagePath) : null,
            'video' => $m->videoPath ? \App\Support\MediaStore::url($m->videoPath) : null,
            'poster' => $m->videoPoster ? \App\Support\MediaStore::url($m->videoPoster) : null,
            'mine' => (int) $m->userId === $meId,
            'name' => optional($user)->full_name ?: 'Member',
            'avatar' => optional($user)->avatarPath ? \App\Support\MediaStore::url($user->avatarPath) : null,
            'initials' => optional($user)->initials ?: '?',
            'at' => $m->created_at?->diffForHumans(null, true),
        ];
    }

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
        // Compress photos to WebP; animated GIFs pass through untouched so they
        // keep their motion (used for GIF reactions in discussions).
        return \App\Support\MediaOptimizer::storeImageAsWebp($file, $dir);
    }
}
