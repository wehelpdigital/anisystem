<?php

namespace App\Http\Controllers\Manager;

use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The schedule "Collab Room": a shared team workspace with Chat, Drawing,
 * Activities and AI Technician tabs. Team-only (owner + active worker
 * sub-members), gated by ScheduleTeam like the group chat and whiteboard.
 */
class CollabRoomController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            abort(403);
        }

        // The opener can pick who's in this room (?members=1,2,3). Whoever opens
        // is always in; unknown ids are ignored; an empty result falls back to all.
        $all = ScheduleTeam::members($schedule);
        $param = (string) $request->query('members', '');
        if ($param !== '') {
            $picked = collect(explode(',', $param))
                ->map(fn ($v) => (int) trim($v))
                ->filter()
                ->push($meId)
                ->unique();
            $members = $all->filter(fn ($m) => $picked->contains((int) $m->id))->values();
            if ($members->isEmpty()) {
                $members = $all;
            }
        } else {
            $members = $all;
        }

        return view('sm.collab', [
            'schedule' => $schedule,
            'members' => $members,
        ]);
    }
}
