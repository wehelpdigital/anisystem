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
 * Community: browse, question and rate cropping plans other members have
 * published. Access is earned by publishing one of your own.
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
        $posts = \App\Models\CommunityWallPost::where('deleteStatus', 1)
            ->with(['author'])
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->limit(24)
            ->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1);

        // Newest first, always — a fresh post lands on top (the composer reloads
        // onto page 1), and the wall reads chronologically. feedMore() continues
        // older posts beneath, in the same order.
        $posts = $posts->sortByDesc(fn ($p) => [$p->created_at->timestamp, $p->id])->values()->take(10);

        \App\Models\CommunityReaction::attach($posts, 'wallpost', (int) $me->id);
        // No comment reactions to attach: the threads are not loaded any more,
        // and flatMap->comments here would lazy-load every one of them back —
        // one query per post, which is the N+1 this page just stopped paying.
        // The thread fetch attaches its own when a thread is actually opened.

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

        // Right rail — recent discussions (groups).
        $recentGroups = \App\Models\CommunityGroup::active()
            ->withCount(['members as member_count'])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('community.feed', [
            'posts' => $posts,
            'friendIds' => $friendIds,
            'recommendations' => \App\Models\CommunityConnection::recommendationsFor((int) $me->id, 8),
            'friendRequests' => $friendRequests,
            'friendRequestCount' => $friendRequestCount,
            'recentGroups' => $recentGroups,
            // No sponsor inventory yet — the rail hides while this is empty.
            'sponsors' => collect(),
        ]);
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

        $rows = \App\Models\CommunityWallPost::where('deleteStatus', 1)
            ->where('created_at', '<', $beforeTs)
            ->with(['author'])->withCount('comments')
            ->orderByDesc('created_at')
            ->limit(11)->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1)
            ->values();

        $hasMore = $rows->count() > 10;
        $items = $rows->take(10)->values();
        \App\Models\CommunityReaction::attach($items, 'wallpost', (int) $me->id);

        $html = '';
        foreach ($items as $post) {
            $html .= view('community.partials.feed-post', ['post' => $post, 'friendIds' => $friendIds])->render();
        }

        return response()->json(['success' => true, 'data' => [
            'html' => $html,
            'hasMore' => $hasMore,
            'before' => optional($items->last()?->created_at)->toIso8601String(),
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
        $wallPosts = \App\Models\CommunityWallPost::where('deleteStatus', 1)
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
        $wallPosts = \App\Models\CommunityWallPost::where('deleteStatus', 1)
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

    public function index(Request $request)
    {
        $userId = Auth::id();

        // The gate: you see other people's plans once you have shared one.
        if (! $this->community->hasPublished($userId)) {
            return view('community.locked', [
                'candidates' => $this->publishCandidates($userId),
            ]);
        }

        $filters = [
            'q' => trim((string) $request->query('q')),
            'crop' => trim((string) $request->query('crop')),
        ];

        $plans = $this->community->browse($userId, $filters);

        $crops = AsCroppingSchedule::active()
            ->where('isPublic', 1)
            ->whereNotNull('cropType')
            ->where('cropType', '!=', '')
            ->distinct()
            ->orderBy('cropType')
            ->pluck('cropType');

        return view('community.index', [
            'plans' => $plans,
            'crops' => $crops,
            'filters' => $filters,
            'myPlans' => $this->community->decorate(
                AsCroppingSchedule::active()->forClient($userId)->where('isPublic', 1)->with('owner')->get()
            ),
        ]);
    }

    /** A public plan, read-only, with its thread and ratings. */
    public function show(Request $request)
    {
        $userId = Auth::id();
        $plan = $this->publicPlan($request->query('id'));
        $isOwner = (int) $plan->anisystemUserId === (int) $userId;

        // Non-owners must have published something before they can read plans.
        if (! $isOwner && ! $this->community->hasPublished($userId)) {
            return redirect()->route('community.index');
        }

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
        $isOwner = (int) $plan->anisystemUserId === (int) $userId;

        if (! $isOwner && ! $this->community->hasPublished($userId)) {
            return $this->json(false, 'Publish one of your own plans first.', [], 403);
        }

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
        if (! $this->community->hasPublished($userId)) {
            return $this->json(false, 'Publish one of your own plans first.', [], 403);
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

    /** Schedules the member could publish, with the reasons blocking each. */
    private function publishCandidates(int $userId)
    {
        return AsCroppingSchedule::active()
            ->forClient($userId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($schedule) {
                $schedule->eligibility = $this->community->publishEligibility($schedule);

                return $schedule;
            });
    }

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
