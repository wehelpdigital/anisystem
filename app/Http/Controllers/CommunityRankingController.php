<?php

namespace App\Http\Controllers;

use App\Support\CommunityRank;
use Illuminate\Support\Facades\Auth;

/**
 * The community's ladder page: who stands where, how points are earned, and
 * what each of the fifty ranks asks of you.
 *
 * All reading — the ranks are computed from what members have already done
 * (see CommunityRank), so this controller has nothing to write and no state
 * to guard beyond being signed in.
 */
class CommunityRankingController extends Controller
{
    public function index()
    {
        $meId = (int) Auth::id();
        $me = Auth::user();

        $points = CommunityRank::pointsFor($meId);
        $rank = CommunityRank::rankOf($points);
        $next = CommunityRank::next($rank);

        /* The free summit. The scoreboard itself already holds a free
         * member's points at the cap (see CommunityRank::map()); the page
         * additionally needs to KNOW it is holding them, so it can say why
         * the bar has stopped moving instead of looking broken. */
        $unlocked = $me && ($me->isSuperAdmin() || $me->hasActiveSubscription());
        $capped = ! $unlocked && $points >= CommunityRank::freePointsCap();

        /* How far along the CURRENT step the member is, for the progress
         * bar: nought at the rank's own doorstep, one at the next rank's.
         * The top of the ladder has no next step and shows a full bar. */
        $progress = 1.0;
        if ($next) {
            $span = max(1, $next['min'] - $rank['min']);
            $progress = max(0.0, min(1.0, ($points - $rank['min']) / $span));
        }

        return view('community.ranking', [
            'rows' => CommunityRank::leaderboard(100),
            'myPoints' => $points,
            'myRank' => $rank,
            'myNext' => $next,
            'myNextTitle' => CommunityRank::nextTitle($rank),
            'myProgress' => $progress,
            'myPosition' => CommunityRank::positionOf($meId),
            'breakdown' => CommunityRank::breakdown($meId),
            'actions' => CommunityRank::ACTIONS,
            'titles' => CommunityRank::TITLES,
            'levels' => CommunityRank::thresholds(),
            'maxLevel' => CommunityRank::MAX_LEVEL,
            'me' => $me,
            'unlocked' => $unlocked,
            'capped' => $capped,
            'freeCap' => CommunityRank::FREE_LEVEL_CAP,
        ]);
    }
}
