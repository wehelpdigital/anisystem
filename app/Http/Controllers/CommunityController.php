<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\CommunityComment;
use App\Models\CommunityRating;
use App\Services\CommunityService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Community: the wall, plus reading, questioning and rating the cropping plans
 * members have published. Open to every member — publishing a plan of your own
 * is a thing you may do, never a toll you pay to get in.
 */
class CommunityController extends Controller
{
    public function __construct(
        private readonly CommunityService $community,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * The community's front door: a wall feed of what members are sharing,
     * ranked so friends and farmers from the same area surface first.
     */
    public function feed(Request $request)
    {
        $me = Auth::user();
        $friendIds = \App\Models\CommunityConnection::connectedIds((int) $me->id);

        // Ten posts, no threads. The first paint used to be forty posts EACH
        // carrying its complete comment thread with authors — on a farm phone
        // that is the whole feed's history as one blocking document. The
        // feed-post partial has always had a second mode for exactly this:
        // when comments are not loaded it renders "View all N comments", which
        // fetches the thread ten at a time through the sentinel pager. One tap
        // costs less than every thread nobody asked for. The 24-row scan
        // (for 10 kept) buys headroom for the deleted-author filter below, the
        // same ratio the old 120-for-40 did.
        $posts = \App\Models\CommunityWallPost::where('deleteStatus', 1)->wallOnly()
            ->with(['author'])
            ->withCount('comments')
            ->withLastActivity()
            ->orderByDesc('lastActivityAt')
            ->limit(24)
            ->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1);

        // Liveliest first: a fresh post lands on top (the composer reloads onto
        // page 1), and so does an old one somebody just answered or reacted
        // to — a wall is about what is happening, and a thread three people
        // are in this morning is happening more than yesterday's silence.
        // feedMore() continues quieter posts beneath, in the same order.
        $posts = $posts->sortByDesc(fn ($p) => [
            \Illuminate\Support\Carbon::parse($p->lastActivityAt ?: $p->created_at)->timestamp,
            $p->id,
        ])->values()->take(10);

        \App\Models\CommunityReaction::attach($posts, 'wallpost', (int) $me->id);
        // No comment reactions to attach: the threads are not loaded any more,
        // and flatMap->comments here would lazy-load every one of them back —
        // one query per post, which is the N+1 this page just stopped paying.
        // The thread fetch attaches its own when a thread is actually opened.

        /* Nobody you follow goes unheard.
         *
         * Following is a promise that this person's news reaches you, so their
         * newest post is lifted above the general wall — one apiece, because
         * the promise is "not missed", not "takes the whole screen". Whatever
         * the feed already holds is excluded, so nothing appears twice. */
        $social = app(\App\Services\CommunitySocialService::class);
        $followingIds = $social->followingIds((int) $me->id);
        $lifted = $social->latestFromFollowed((int) $me->id, $posts->pluck('id')->all(), 6);
        if ($lifted->isNotEmpty()) {
            $lifted->loadCount('comments');
            \App\Models\CommunityReaction::attach($lifted, 'wallpost', (int) $me->id);
            $posts = $lifted->concat($posts)->values();
        }
        $posts->loadMissing('sharedPost');
        // Who wrote each one: where they farm, whether you already farm with
        // them, how many follow them. Two queries for the page.
        $social->attachAuthorFacts($posts, (int) $me->id);

        // Left rail — incoming friend (co-farmer) requests.
        $requestRows = \App\Models\CommunityConnection::active()
            ->where('friendUserId', (int) $me->id)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();
        $friendRequestCount = $requestRows->count();
        $friendRequests = \App\Models\User::whereIn('id', $requestRows->pluck('userId'))
            ->where('deleteStatus', 1)
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        /* One discussion and one article, dealt into the wall itself.
         *
         * Randomised rather than newest, so the same two never own the page —
         * and drawn as posts because that is how they get read: a rail card is
         * furniture, a card in the stream is something you stop at. */
        $discussion = $this->liveDiscussion((int) $me->id);
        // What gets dealt between the posts, and where — a few of each,
        // alternating, at positions drawn fresh on every visit.
        $interruptions = $this->feedInterruptions((int) $me->id, $posts->count());
        $article = \App\Models\AsCommunityBlogPost::where('deleteStatus', 1)
            ->where('isPublished', 1)
            // How much of a conversation there is on it — the card offers to
            // join one, and could not say whether there was one to join.
            ->withCount(['comments as comment_count'])
            ->inRandomOrder()
            ->first();

        return view('community.feed', [
            'posts' => $posts,
            'friendIds' => $friendIds,
            'followingIds' => $followingIds,
            'savedIds' => $social->bookmarkedIds((int) $me->id),
            'friendRequests' => $friendRequests,
            'friendRequestCount' => $friendRequestCount,
            'injectDiscussion' => $discussion,
            'interruptions' => $interruptions,
            'injectArticle' => $article,
            // No sponsor inventory yet — the rail hides while this is empty.
            'sponsors' => collect(),
        ]);
    }

    /**
     * One post, on its own page.
     *
     * A share quotes what it carries and offers to show the original; until
     * now that offer was a wall link with #wallpost-N on the end, which does
     * nothing unless the post happens to be in the first screenful. This is
     * the same card the wall draws, so comments, reactions, saving and
     * sharing all still work on it.
     */
    public function post(Request $request, int $id)
    {
        $me = Auth::user();
        $post = \App\Models\CommunityWallPost::active()
            ->with(['author', 'sharedPost.author'])
            ->withCount(['comments as comment_count'])
            ->find($id);

        if (! $post || ! $post->author || (int) $post->author->deleteStatus !== 1) {
            abort(404);
        }

        $one = collect([$post]);
        \App\Models\CommunityReaction::attach($one, 'wallpost', (int) $me->id);
        $social = app(\App\Services\CommunitySocialService::class);
        $social->attachAuthorFacts($one, (int) $me->id);

        return view('community.post', [
            'post' => $one->first(),
            'savedIds' => $social->bookmarkedIds((int) $me->id),
            'friendIds' => \App\Models\CommunityConnection::connectedIds((int) $me->id),
            'followingIds' => $social->followingIds((int) $me->id),
        ]);
    }

    /**
     * What gets dealt between the posts, and where.
     *
     * A plan rather than two fixed seats: rooms and articles alternate, land
     * every three to five posts, and the positions are drawn fresh each time
     * so the wall reads differently on every visit. Nothing repeats inside a
     * page — the pools are only cycled when they run dry.
     *
     * @return array<int,array{kind:string,item:mixed}>  position => what
     */
    private function feedInterruptions(int $meId, int $postCount, int $startAfter = 0): array
    {
        if ($postCount < 1) {
            return [];
        }

        // As many as the plan could want, so it never has to deal the same
        // room twice on one wall.
        $rooms = $this->liveDiscussions($meId, 4);
        $articles = \App\Models\AsCommunityBlogPost::where('deleteStatus', 1)
            ->where('isPublished', 1)
            ->withCount(['comments as comment_count'])
            ->inRandomOrder()
            ->limit(3)
            ->get();

        if ($rooms->isEmpty() && $articles->isEmpty()) {
            return [];
        }

        $plan = [];
        // The first one lands early, but never on top of the composer.
        $at = random_int(2, 3) + $startAfter;
        $kind = $rooms->isNotEmpty() ? 'discussion' : 'article';
        $r = 0;
        $a = 0;

        while ($at <= $postCount && count($plan) < 4) {
            /* Each one only once. Cycling the pool put the same discussion on
             * the wall twice, which reads as the wall being stuck rather than
             * as a room worth joining. When a pool runs dry the other kind
             * carries on alone; when both are dry, so is the plan. */
            $haveRoom = $r < $rooms->count();
            $haveArticle = $a < $articles->count();
            if (! $haveRoom && ! $haveArticle) {
                break;
            }
            if ($kind === 'discussion' && ! $haveRoom) {
                $kind = 'article';
            } elseif ($kind === 'article' && ! $haveArticle) {
                $kind = 'discussion';
            }

            if ($kind === 'discussion') {
                $plan[$at] = ['kind' => 'discussion', 'item' => $rooms[$r]];
                $r++;
            } else {
                $plan[$at] = ['kind' => 'article', 'item' => $articles[$a]];
                $a++;
            }
            // Alternate, unless there is only one kind to give.
            if ($rooms->isNotEmpty() && $articles->isNotEmpty()) {
                $kind = $kind === 'discussion' ? 'article' : 'discussion';
            }
            $at += random_int(3, 5);
        }

        return $plan;
    }

    /**
     * A few discussions worth putting in front of somebody — the same rule
     * as one, asked for several.
     *
     * @return \Illuminate\Support\Collection
     */
    private function liveDiscussions(int $meId, int $take): \Illuminate\Support\Collection
    {
        $since = now()->subDays(30);

        $spoken = \App\Models\CommunityGroupPost::active()
            ->where('created_at', '>=', $since)->distinct()->pluck('groupId');
        $answered = \App\Models\CommunityGroupReply::active()
            ->where('as_community_group_replies.created_at', '>=', $since)
            ->join('as_community_group_posts as t', 't.id', '=', 'as_community_group_replies.postId')
            ->where('t.deleteStatus', 1)
            ->distinct()->pluck('t.groupId');
        $live = $spoken->merge($answered)->map(fn ($id) => (int) $id)->unique()->values();

        $rooms = \App\Models\CommunityGroup::active()
            ->withCount(['members as member_count', 'posts as post_count'])
            ->when($live->isNotEmpty(), fn ($q) => $q->whereIn('id', $live->all()))
            ->inRandomOrder()
            ->limit($take)
            ->get();

        if ($rooms->isEmpty()) {
            return $rooms;
        }

        $mine = \App\Models\CommunityGroupMember::active()
            ->where('userId', $meId)
            ->pluck('groupId')
            ->map(fn ($id) => (int) $id)
            ->all();
        $mineSet = array_flip($mine);

        $topics = \App\Models\CommunityGroupPost::active()
            ->whereIn('groupId', $rooms->pluck('id')->all())
            ->withCount(['replies as reply_count'])
            ->with('author')
            ->orderByDesc('id')
            ->get()
            ->unique('groupId')
            ->keyBy('groupId');

        foreach ($rooms as $room) {
            $room->joined = isset($mineSet[(int) $room->id]);
            $room->latestTopic = $topics->get($room->id);
        }

        return $rooms;
    }

    /**
     * A discussion worth putting in front of somebody.
     *
     * Only rooms where something has actually been said lately: a card
     * advertising a room whose last word was in March is an invitation to
     * walk into an empty hall. Among those, at random — so the wall does not
     * hand the same room the same slot every day.
     *
     * Comes back carrying the last topic and how many have answered it, which
     * is what tells a reader whether this is a conversation they want.
     */
    private function liveDiscussion(int $meId): ?\App\Models\CommunityGroup
    {
        $since = now()->subDays(30);

        $spoken = \App\Models\CommunityGroupPost::active()
            ->where('created_at', '>=', $since)
            ->distinct()
            ->pluck('groupId');
        $answered = \App\Models\CommunityGroupReply::active()
            ->where('as_community_group_replies.created_at', '>=', $since)
            ->join('as_community_group_posts as t', 't.id', '=', 'as_community_group_replies.postId')
            ->where('t.deleteStatus', 1)
            ->distinct()
            ->pluck('t.groupId');
        $live = $spoken->merge($answered)->map(fn ($id) => (int) $id)->unique()->values();

        $discussion = \App\Models\CommunityGroup::active()
            ->withCount(['members as member_count', 'posts as post_count'])
            // Nothing recent anywhere (a new farm): show a room anyway rather
            // than a gap where the card should be.
            ->when($live->isNotEmpty(), fn ($q) => $q->whereIn('id', $live->all()))
            ->inRandomOrder()
            ->first();

        if (! $discussion) {
            return null;
        }

        $discussion->joined = \App\Models\CommunityGroupMember::active()
            ->where('groupId', $discussion->id)
            ->where('userId', $meId)
            ->exists();

        $discussion->latestTopic = \App\Models\CommunityGroupPost::active()
            ->where('groupId', $discussion->id)
            ->withCount(['replies as reply_count'])
            ->with('author')
            ->orderByDesc('id')
            ->first();

        return $discussion;
    }

    /**
     * Infinite-scroll for the feed: older wall posts (created before the
     * client's cursor), newest-first. Page 1 stays ranked (friends/nearby
     * first) in feed(); this continues chronologically beneath it, and the
     * client dedupes any post the ranked page already surfaced.
     */
    public function feedMore(Request $request)
    {
        $me = Auth::user();
        $before = $request->query('before');
        $beforeTs = $before ? \Illuminate\Support\Carbon::parse($before) : now();
        $friendIds = \App\Models\CommunityConnection::connectedIds((int) $me->id);
        $moreSocial = app(\App\Services\CommunitySocialService::class);
        $moreFollowing = $moreSocial->followingIds((int) $me->id);
        $moreSaved = $moreSocial->bookmarkedIds((int) $me->id);

        /* The same road serves the wall's search: page one with words on it.
         *
         * A post is what somebody wrote and who wrote it — both are searched,
         * because "what did Nena say about tungro" is one question and a
         * reader does not know which half they remember. % and _ are
         * wildcards down in SQL and letters up here. */
        $q = \Illuminate\Support\Str::limit(trim((string) $request->query('q', '')), 120, '');
        $rows = \App\Models\CommunityWallPost::where('deleteStatus', 1)->wallOnly()
            ->quieterThan($beforeTs)
            ->when($q !== '', function ($sql) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $sql->where(function ($w) use ($like) {
                    $w->where('body', 'like', $like)
                        ->orWhereHas('author', fn ($a) => $a
                            ->where('firstName', 'like', $like)
                            ->orWhere('lastName', 'like', $like));
                });
            })
            ->with(['author'])->withCount('comments')
            ->withLastActivity()
            ->orderByDesc('lastActivityAt')
            ->limit(11)->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1)
            ->values();

        $hasMore = $rows->count() > 10;
        $items = $rows->take(10)->values();
        \App\Models\CommunityReaction::attach($items, 'wallpost', (int) $me->id);

        // The next page introduces its people exactly as the first one did.
        app(\App\Services\CommunitySocialService::class)
            ->attachAuthorFacts($items, (int) Auth::id());

        /* The next page carries on dealing.
         *
         * Told how far down the wall it starts, so the first interruption
         * does not land on the very first post the reader sees after the
         * seam. */
        // A search returns answers, not a magazine: the rooms and articles
        // dealt into an idle scroll are not what was asked for.
        $plan = $q === '' ? $this->feedInterruptions((int) Auth::id(), $items->count()) : [];

        $html = '';
        foreach ($items as $i => $post) {
            $html .= view('community.partials.feed-post', [
                'post' => $post,
                'friendIds' => $friendIds,
                'followingIds' => $moreFollowing,
                'savedIds' => $moreSaved,
            ])->render();

            $slot = $plan[$i + 1] ?? null;
            if (! $slot) {
                continue;
            }
            $html .= $slot['kind'] === 'discussion'
                ? view('community.partials.feed-discussion', ['discussion' => $slot['item']])->render()
                : view('community.partials.feed-article', ['article' => $slot['item']])->render();
        }

        return response()->json(['success' => true, 'data' => [
            'html' => $html,
            'hasMore' => $hasMore,
            'count' => $items->count(),
            'q' => $q,
            // The cursor is the thing the order is on, or the next page
            // would start from a different measure than the one it continues.
            'before' => $items->last()
                ? \Illuminate\Support\Carbon::parse($items->last()->lastActivityAt ?: $items->last()->created_at)->toIso8601String()
                : null,
        ]]);
    }

    /**
     * A hashtag page: every wall post (and the group posts you can see) that
     * carries #tag, newest first. Reached by clicking a #hashtag anywhere.
     */
    public function hashtag(Request $request, string $tag)
    {
        $tag = ltrim(mb_strtolower(trim($tag)), '#');
        if ($tag === '' || ! preg_match('/^[\p{L}0-9_]{1,50}$/u', $tag)) {
            abort(404);
        }
        $me = Auth::user();
        $like = '%#' . $tag . '%';
        $hasTag = fn ($p) => in_array($tag, \App\Support\CommunityText::hashtags($p->body), true);

        // Wall posts are public on profiles — safe to surface here.
        $wallPosts = \App\Models\CommunityWallPost::where('deleteStatus', 1)->wallOnly()
            ->where('body', 'like', $like)
            ->with('author')->withCount('comments')
            ->orderByDesc('id')->limit(80)->get()
            ->filter(fn ($p) => $hasTag($p) && $p->author && (int) $p->author->deleteStatus === 1)
            ->values();
        \App\Models\CommunityReaction::attach($wallPosts, 'wallpost', (int) $me->id);

        // Group posts only from groups you belong to (respects membership).
        $myGroupIds = \App\Models\CommunityGroupMember::where('userId', (int) $me->id)
            ->pluck('groupId')->all();
        $groupPosts = \App\Models\CommunityGroupPost::active()
            ->where('body', 'like', $like)
            ->whereIn('groupId', $myGroupIds ?: [-1])
            ->with(['author', 'group'])
            ->orderByDesc('id')->limit(80)->get()
            ->filter($hasTag)->values();

        return view('community.hashtag', [
            'tag' => $tag,
            'wallPosts' => $wallPosts,
            'groupPosts' => $groupPosts,
            'friendIds' => \App\Models\CommunityConnection::connectedIds((int) $me->id),
        ]);
    }

    /**
     * A place page: every member from a town/province, plus wall posts either
     * written by them or tagging the location via @[Town, Province](loc:slug).
     * The slug is stable — slug("{city} {province}") — so it round-trips from
     * the mention token without needing a locations table.
     */
    public function location(Request $request, string $slug)
    {
        $slug = mb_strtolower(trim($slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9\-]{1,80}$/', $slug)) {
            abort(404);
        }
        $me = Auth::user();

        // Resolve members at this location by matching the computed slug.
        $candidates = \App\Models\User::where('deleteStatus', 1)
            ->where(function ($w) {
                $w->where(fn ($c) => $c->whereNotNull('city')->where('city', '!=', ''))
                    ->orWhere(fn ($c) => $c->whereNotNull('province')->where('province', '!=', ''));
            })
            ->orderBy('firstName')
            ->limit(500)
            ->get(['id', 'firstName', 'lastName', 'avatarPath', 'city', 'province', 'statusBubble']);

        $label = null;
        $memberIds = [];
        $members = collect();
        foreach ($candidates as $u) {
            $s = Str::slug(trim(($u->city ?? '') . ' ' . ($u->province ?? '')));
            if ($s !== $slug) {
                continue;
            }
            if ($label === null) {
                $label = collect([$u->city, $u->province])->filter(fn ($p) => filled($p))->implode(', ');
            }
            $memberIds[] = (int) $u->id;
            $members->push($u);
        }
        if ($label === null) {
            // Prefer the gazetteer's proper label (e.g. "Brgy X, City, Province").
            $label = \App\Models\AsLocation::where('slug', $slug)->value('label')
                ?: ucwords(str_replace('-', ' ', $slug));
        }

        // Posts tagging this place (📍[Label] or legacy (loc:slug)), or written
        // by members from here.
        $locLike = '%(loc:' . $slug . ')%';
        $pinLike = '%📍[' . $label . ']%';
        $pinLike2 = '%📍 [' . $label . ']%';
        $wallPosts = \App\Models\CommunityWallPost::where('deleteStatus', 1)->wallOnly()
            ->where(function ($q) use ($memberIds, $locLike, $pinLike, $pinLike2) {
                $q->where('body', 'like', $locLike)
                    ->orWhere('body', 'like', $pinLike)
                    ->orWhere('body', 'like', $pinLike2);
                if (! empty($memberIds)) {
                    $q->orWhereIn('authorUserId', $memberIds);
                }
            })
            ->with('author')->withCount('comments')
            ->orderByDesc('id')->limit(60)->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1)
            ->values();
        \App\Models\CommunityReaction::attach($wallPosts, 'wallpost', (int) $me->id);

        return view('community.location', [
            'slug' => $slug,
            'label' => $label,
            'members' => $members,
            'wallPosts' => $wallPosts,
            'friendIds' => \App\Models\CommunityConnection::connectedIds((int) $me->id),
        ]);
    }

    /** A public plan, read-only, with its thread and ratings. */
    public function show(Request $request)
    {
        $userId = Auth::id();
        $plan = $this->publicPlan($request->query('id'));
        $isOwner = (int) $plan->anisystemUserId === (int) $userId;

        $plan->load(['lots', 'owner', 'workers', 'activities.lots']);

        return view('community.show', [
            'plan' => $plan,
            'isOwner' => $isOwner,
            'dayZero' => $this->community->lotDayZero($plan),
            'dayType' => $plan->dayType ?: 'DAS',
            'thread' => $this->community->thread($plan->id),
            'ratings' => $this->community->ratingSummary($plan->id),
            'myRating' => CommunityRating::active()
                ->where('croppingScheduleId', $plan->id)
                ->where('anisystemUserId', $userId)
                ->first(),
        ]);
    }

    /** Publish or unpublish one of your own schedules. */
    public function togglePublish(Request $request)
    {
        $schedule = $this->ownedSchedule($request->input('scheduleId') ?? $request->query('scheduleId'));
        $wantPublic = $request->boolean('isPublic');

        if (! $wantPublic) {
            $schedule->update(['isPublic' => 0]);

            return $this->json(true, 'Plan removed from the Community.', ['isPublic' => false]);
        }

        $eligibility = $this->community->publishEligibility($schedule);
        if (! $eligibility['ok']) {
            return $this->json(false, 'This plan is not ready to publish yet.', $eligibility, 422);
        }

        $validator = Validator::make($request->all(), [
            'publicSummary' => 'nullable|string|max:500',
            'publicRegion' => 'nullable|string|max:120',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $schedule->update([
            'isPublic' => 1,
            'publishedAt' => $schedule->publishedAt ?: now(),
            'publicSummary' => $request->input('publicSummary'),
            'publicRegion' => $request->input('publicRegion'),
        ]);

        return $this->json(true, 'Plan published to the Community.', [
            'isPublic' => true,
            'url' => route('community.show', ['id' => $schedule->id]),
        ]);
    }

    public function comment(Request $request)
    {
        $userId = Auth::id();
        $plan = $this->publicPlan($request->input('scheduleId'));

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:4000',
            'parentId' => 'nullable|integer',
            'isQuestion' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        // A reply may only attach to a top-level comment on this same plan,
        // which keeps the thread two deep and stops cross-plan grafting.
        $parentId = $request->input('parentId');
        if ($parentId) {
            $parent = CommunityComment::active()
                ->where('id', $parentId)
                ->where('croppingScheduleId', $plan->id)
                ->whereNull('parentId')
                ->first();
            if (! $parent) {
                return $this->json(false, 'That comment is no longer available.', [], 404);
            }
        }

        $comment = CommunityComment::create([
            'croppingScheduleId' => $plan->id,
            'anisystemUserId' => $userId,
            'parentId' => $parentId ?: null,
            'body' => $request->input('body'),
            'isQuestion' => $request->boolean('isQuestion') ? 1 : 0,
            'deleteStatus' => 1,
        ]);

        $this->notifyOnComment($plan, $comment, $parentId ? ($parent ?? null) : null);

        return $this->json(true, $parentId ? 'Reply posted.' : 'Posted.', [
            'comment' => $this->presentComment($comment->fresh('author')),
        ]);
    }

    public function deleteComment(Request $request)
    {
        $comment = CommunityComment::active()->where('id', $request->query('id'))->first();
        if (! $comment) {
            return $this->json(false, 'Comment not found.', [], 404);
        }

        // Your own comment, or anything left on your own plan.
        $plan = AsCroppingSchedule::active()->where('id', $comment->croppingScheduleId)->first();
        $mine = (int) $comment->anisystemUserId === (int) Auth::id();
        $onMyPlan = $plan && (int) $plan->anisystemUserId === (int) Auth::id();
        if (! $mine && ! $onMyPlan) {
            return $this->json(false, 'You cannot remove that comment.', [], 403);
        }

        DB::transaction(function () use ($comment) {
            // Removing a question takes its replies with it.
            CommunityComment::where('parentId', $comment->id)->update(['deleteStatus' => 0]);
            $comment->update(['deleteStatus' => 0]);
        });

        return $this->json(true, 'Comment removed.');
    }

    public function rate(Request $request)
    {
        $userId = Auth::id();
        $plan = $this->publicPlan($request->input('scheduleId'));

        if ((int) $plan->anisystemUserId === (int) $userId) {
            return $this->json(false, 'You cannot rate your own plan.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        CommunityRating::updateOrCreate(
            ['croppingScheduleId' => $plan->id, 'anisystemUserId' => $userId],
            [
                'rating' => (int) $request->input('rating'),
                'review' => $request->input('review'),
                'deleteStatus' => 1,
            ]
        );

        return $this->json(true, 'Thanks for rating this plan.', [
            'summary' => $this->community->ratingSummary($plan->id),
        ]);
    }

    // ------------------------------------------------------------------

    private function publicPlan($id): AsCroppingSchedule
    {
        $plan = AsCroppingSchedule::active()->where('id', $id)->first();

        // A member can always reach their own plan, published or not.
        if (! $plan || (! $plan->isPublic && (int) $plan->anisystemUserId !== (int) Auth::id())) {
            abort(404);
        }

        return $plan;
    }

    private function ownedSchedule($id): AsCroppingSchedule
    {
        $schedule = AsCroppingSchedule::active()->forClient(Auth::id())->where('id', $id)->first();
        if (! $schedule) {
            abort(404);
        }

        return $schedule;
    }

    /**
     * Tell the plan owner someone engaged, and (for a reply) tell the person
     * being replied to. Self-actions are skipped by NotificationService.
     */
    private function notifyOnComment(AsCroppingSchedule $plan, CommunityComment $comment, ?CommunityComment $parent): void
    {
        $actorId = (int) $comment->anisystemUserId;
        $actor = Auth::user();
        $actorName = $actor ? ($actor->full_name ?: 'A member') : 'A member';
        $url = route('community.show', ['id' => $plan->id]);
        $snippet = Str::limit(trim(strip_tags($comment->body)), 90);
        $verb = $comment->isQuestion ? 'asked about' : 'commented on';

        // Plan owner.
        $this->notifications->notify(
            userId: (int) $plan->anisystemUserId,
            type: 'comment',
            title: $actorName . ' ' . $verb . ' your plan',
            body: $snippet,
            url: $url,
            actorUserId: $actorId,
            croppingScheduleId: $plan->id,
        );

        // Person being replied to (if different from the plan owner).
        if ($parent && (int) $parent->anisystemUserId !== (int) $plan->anisystemUserId) {
            $this->notifications->notify(
                userId: (int) $parent->anisystemUserId,
                type: 'reply',
                title: $actorName . ' replied to you',
                body: $snippet,
                url: $url,
                actorUserId: $actorId,
                croppingScheduleId: $plan->id,
            );
        }
    }

    private function presentComment(CommunityComment $c): array
    {
        return [
            'id' => $c->id,
            'parentId' => $c->parentId,
            'body' => $c->body,
            'isQuestion' => (bool) $c->isQuestion,
            'authorName' => optional($c->author)->full_name ?: 'Member',
            'authorInitials' => optional($c->author)->initials ?: '?',
            'createdAt' => $c->created_at?->diffForHumans(),
            'mine' => (int) $c->anisystemUserId === (int) Auth::id(),
        ];
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => $ok,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
