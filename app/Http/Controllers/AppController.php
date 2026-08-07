<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function dashboard(Request $request, NotificationService $notifications)
    {
        $user = $request->user();

        // Keep the expiry reminder current whenever the client lands here.
        $notifications->syncExpiryReminder($user);

        $subscription = $user->currentSubscription();

        $scheduleCount = $user->schedules()->count();

        // A schedule counts as "active" when it has at least one activity dated
        // within 6 months of today (either side). The dashboard only lists these
        // — schedules with no near-term activity are hidden here.
        $scheduleIds = $user->schedules()->pluck('id')->all();
        $activeVersionIds = \App\Models\AsScheduleActivityVersion::active()
            ->whereIn('croppingScheduleId', $scheduleIds ?: [-1])
            ->where('isActive', 1)
            ->pluck('id')->all();
        $today = \Illuminate\Support\Carbon::today('Asia/Manila');
        $activeScheduleIds = \App\Models\AsScheduleActivity::active()
            ->whereIn('versionId', $activeVersionIds ?: [-1])
            ->where('isDraft', 0)
            ->where('isHidden', 0)
            ->whereNotNull('targetDate')
            ->whereBetween('targetDate', [
                $today->copy()->subMonths(6)->toDateString(),
                $today->copy()->addMonths(6)->toDateString(),
            ])
            ->distinct()
            ->pluck('croppingScheduleId')
            ->all();

        $latestSchedules = $user->schedules()
            ->whereIn('id', $activeScheduleIds ?: [-1])
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        // Which of the shown schedules have a lot with a geocodeable address —
        // so the dashboard knows whether to show a weather widget or a prompt.
        $latestSchedules->load(['lots' => fn ($q) => $q->select('id', 'croppingScheduleId', 'locTown', 'locProvince')]);
        $scheduleHasLocation = $latestSchedules->mapWithKeys(fn ($s) => [
            $s->id => $s->lots->contains(fn ($l) => filled($l->geocode_query)),
        ])->all();

        $aiBalance = app(\App\Services\AiCreditService::class)->balance($user->id);

        $latestBlog = \App\Models\AsCommunityBlogPost::active()
            ->published()
            ->orderByDesc('publishedAt')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // Quick view: what's happening today across my schedules, or the nearest
        // upcoming day if nothing is due today. (Active versions + today are
        // already resolved above for the active-schedule filter.)
        $upcoming = \App\Models\AsScheduleActivity::active()
            ->whereIn('versionId', $activeVersionIds ?: [-1])
            ->where('isDraft', 0)
            ->where('isHidden', 0)
            ->whereNotNull('targetDate')
            ->whereDate('targetDate', '>=', $today->toDateString())
            ->orderBy('targetDate')
            ->orderBy('sequenceOrder')
            ->limit(60)
            ->get(['id', 'croppingScheduleId', 'activityTitle', 'targetDate', 'targetEndDate', 'timeRequired']);
        // Per-schedule quick view: for each schedule, the activities due today,
        // or — if none today — the ones on its nearest upcoming day. Keyed by
        // schedule id so each card can show its own "what's next" strip.
        $scheduleNext = [];
        foreach ($upcoming->groupBy('croppingScheduleId') as $sid => $acts) {
            $nearest = $acts->first()->targetDate; // already ordered by date, sequence
            if (! $nearest) {
                continue;
            }
            $sameDay = $acts->filter(fn ($a) => $a->targetDate && $a->targetDate->isSameDay($nearest))->values();
            $scheduleNext[(int) $sid] = [
                'date' => $nearest,
                'isToday' => $nearest->isSameDay($today),
                'daysAway' => (int) $today->diffInDays($nearest),
                'activities' => $sameDay,
                'moreCount' => max(0, $sameDay->count() - 2),
            ];
        }

        $meId = (int) $user->id;

        // Community — latest discussion posts from the groups I belong to,
        // loaded like the wall (with replies + reactions) so the dashboard can
        // render the real post card + inline reply box.
        $myGroupIds = \App\Models\CommunityGroupMember::where('userId', $meId)
            ->where('deleteStatus', 1)
            ->pluck('groupId')
            ->all();
        $latestDiscussions = \App\Models\CommunityGroupPost::active()
            ->whereIn('groupId', $myGroupIds ?: [-1])
            ->with(['author', 'group', 'replies.author'])
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->filter(fn ($p) => $p->author && $p->group)
            ->values();
        \App\Models\CommunityReaction::attach($latestDiscussions, 'post', $meId);

        // Community — wall posts from me + my co-farmers (accepted connections),
        // with their comments + reactions for the full wall-style card. My own
        // posts are included so what I post from the dashboard composer shows up
        // here too (the same wall as /app/community and my profile wall).
        $friendIds = \App\Models\CommunityConnection::connectedIds($meId);
        $wallAuthorIds = array_values(array_unique(array_merge($friendIds, [$meId])));
        $connectedWall = \App\Models\CommunityWallPost::where('deleteStatus', 1)
            ->whereIn('authorUserId', $wallAuthorIds ?: [-1])
            ->where(fn ($q) => $q->where('isRestricted', 0)->orWhereNull('isRestricted'))
            ->with(['author', 'comments.author'])
            ->withCount('comments')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1)
            ->values();
        \App\Models\CommunityReaction::attach($connectedWall, 'wallpost', $meId);
        \App\Models\CommunityReaction::attach($connectedWall->flatMap->comments, 'wallcomment', $meId);

        // AI — my most recent chats.
        $recentChats = \App\Models\AiConversation::where('userId', $meId)
            ->where('deleteStatus', 1)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        // Support — only surfaced when I have an unresolved (not closed) ticket.
        $openTickets = \App\Models\SupportTicket::where('userId', $meId)
            ->where('deleteStatus', 1)
            ->where('status', '!=', 'closed')
            ->orderByDesc('lastReplyAt')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('app.dashboard', [
            'user' => $user,
            'subscription' => $subscription,
            'scheduleCount' => $scheduleCount,
            'latestSchedules' => $latestSchedules,
            'scheduleHasLocation' => $scheduleHasLocation,
            'aiBalance' => $aiBalance,
            'latestBlog' => $latestBlog,
            'scheduleNext' => $scheduleNext,
            'latestDiscussions' => $latestDiscussions,
            'connectedWall' => $connectedWall,
            'friendIds' => $friendIds,
            'recentChats' => $recentChats,
            'openTickets' => $openTickets,
            'canUseAi' => $user->canUseAi(),
        ]);
    }
}
