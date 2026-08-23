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

        // Whose farm this dashboard is about. A worker who has switched into
        // a boss's account was still shown their OWN schedules here — cards
        // for a farm the session was no longer looking at, every one of which
        // 404'd when tapped, because the modules resolve schedules against the
        // effective owner and these ids belonged to somebody else's context.
        $ownerId = \App\Support\WorkerContext::effectiveOwnerId();
        $schedulesQ = fn () => \App\Models\AsCroppingSchedule::active()->forClient($ownerId);

        $scheduleCount = $schedulesQ()->count();

        // This shelf is about the season being farmed now, so it is scoped to
        // the calendar year rather than to a rolling window: a plan belongs
        // here when it has an activity dated in this year. A plan made this
        // year that has not been filled in yet counts too — it is the one
        // most likely to be opened next, and hiding a season the day it is
        // created reads as if it failed to save.
        $scheduleIds = $schedulesQ()->pluck('id')->all();
        $activeVersionIds = \App\Models\AsScheduleActivityVersion::active()
            ->whereIn('croppingScheduleId', $scheduleIds ?: [-1])
            ->where('isActive', 1)
            ->pluck('id')->all();
        $today = \Illuminate\Support\Carbon::today('Asia/Manila');
        $year = (int) $today->year;

        $thisYearIds = \App\Models\AsScheduleActivity::active()
            ->whereIn('versionId', $activeVersionIds ?: [-1])
            ->where('isDraft', 0)
            ->where('isHidden', 0)
            ->whereNotNull('targetDate')
            ->whereBetween('targetDate', [$year . '-01-01', $year . '-12-31'])
            ->distinct()
            ->pluck('croppingScheduleId')
            ->all();

        $bareThisYearIds = $schedulesQ()
            ->whereYear('created_at', $year)
            ->whereNotIn('id', $thisYearIds ?: [-1])
            ->whereDoesntHave('activities')
            ->pluck('id')
            ->all();

        $activeScheduleIds = array_values(array_unique(array_merge($thisYearIds, $bareThisYearIds)));

        // Most recently worked on, first.
        //
        // A season's own row barely changes — a title, a status — while the
        // work happens in its activities, so ordering by the schedule's
        // updated_at put a plan edited all morning below one renamed in
        // March. The shelf reads the later of the two.
        $latestSchedules = $schedulesQ()
            ->whereIn('id', $activeScheduleIds ?: [-1])
            ->select('as_cropping_schedules.*')
            ->selectRaw('GREATEST(
                as_cropping_schedules.updated_at,
                COALESCE((SELECT MAX(a.updated_at)
                            FROM as_schedule_activities a
                           WHERE a.croppingScheduleId = as_cropping_schedules.id
                             AND a.deleteStatus = 1), as_cropping_schedules.updated_at)
            ) as lastTouchedAt')
            ->orderByDesc('lastTouchedAt')
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
            // Enough to make a task card worth looking at: what kind of work
            // it is, how urgent, how long, and which ground it is on.
            ->with(['lots:as_schedule_lots.id,lotName'])
            ->withCount('workers')
            ->get(['id', 'croppingScheduleId', 'activityTitle', 'targetDate', 'targetEndDate',
                'timeRequired', 'activityType', 'priority']);
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
                'moreCount' => max(0, $sameDay->count() - 3),
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
        /* The homepage wall keeps the same promise the community wall makes:
         * whoever you follow does not go unheard, one post apiece, above the
         * co-farmer stream. Without this the same card on two pages disagreed
         * about whether you follow its author. */
        $social = app(\App\Services\CommunitySocialService::class);
        $wallFollowing = $social->followingIds($meId);
        $lifted = $social->latestFromFollowed($meId, $connectedWall->pluck('id')->all(), 3);
        if ($lifted->isNotEmpty()) {
            $lifted->loadCount('comments');
            \App\Models\CommunityReaction::attach($lifted, 'wallpost', $meId);
            $connectedWall = $lifted->concat($connectedWall)->take(6)->values();
        }
        $connectedWall->loadMissing('sharedPost');
        $wallSaved = $social->bookmarkedIds($meId);

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

        // One thing worth knowing, chosen for whatever this grower's own crops
        // are doing today — held steady until tomorrow. It reads at the top of
        // the dashboard now, above the schedules: the first screen of the app
        // is where advice is actually read.
        $tip = \App\Support\FarmTips::forToday((int) $user->id, $latestSchedules->first());

        return view('app.dashboard', [
            'user' => $user,
            'tip' => $tip,
            'subscription' => $subscription,
            'scheduleCount' => $scheduleCount,
            'shelfYear' => $year,
            'latestSchedules' => $latestSchedules,
            'scheduleHasLocation' => $scheduleHasLocation,
            'aiBalance' => $aiBalance,
            'latestBlog' => $latestBlog,
            'scheduleNext' => $scheduleNext,
            'latestDiscussions' => $latestDiscussions,
            'connectedWall' => $connectedWall,
            'followingIds' => $wallFollowing,
            'savedIds' => $wallSaved,
            'friendIds' => $friendIds,
            'recentChats' => $recentChats,
            'openTickets' => $openTickets,
            'canUseAi' => $user->canUseAi(),
        ]);
    }
}
