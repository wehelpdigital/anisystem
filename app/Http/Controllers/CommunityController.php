<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\CommunityComment;
use App\Models\CommunityRating;
use App\Services\CommunityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Community: browse, question and rate cropping plans other members have
 * published. Access is earned by publishing one of your own.
 */
class CommunityController extends Controller
{
    public function __construct(private readonly CommunityService $community)
    {
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

        $plan->load(['lots', 'owner', 'activities.lots']);

        return view('community.show', [
            'plan' => $plan,
            'isOwner' => $isOwner,
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
