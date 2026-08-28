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

        /* Opening the room is joining it.
         *
         * Only a cold heartbeat counts as an arrival — a refresh, or a second
         * tab, is the same person still here — so the question is asked
         * before the beat is marked, never after. */
        $arriving = ! ScheduleChatController::isInRoom((int) $schedule->id, $meId);
        ScheduleChatController::markInRoom((int) $schedule->id, $meId);
        if ($arriving) {
            ScheduleChatController::announceJoin($schedule, $meId);
        }

        /* The opener picks who is in this room (?members=1,2,3).
         *
         * Presence of the parameter is what decides, not whether it has
         * anything in it. It used to test the string for emptiness, so a
         * farmer who cleared every tick sent no list at all and the room
         * answered by inviting the whole team back in — the one case where
         * unchecking was most clearly meant, and the one case it was ignored.
         *
         * Whoever opens the room is always in it; unknown ids are dropped. */
        $all = ScheduleTeam::members($schedule);
        if ($request->has('members')) {
            $picked = collect(explode(',', (string) $request->query('members', '')))
                ->map(fn ($v) => (int) trim($v))
                ->filter()
                ->push($meId)
                ->unique();
            $members = $all->filter(fn ($m) => $picked->contains((int) $m->id))->values();
            // Never nobody: a room with an empty roster has no one to show the
            // opener their own place in it.
            if ($members->isEmpty()) {
                $members = $all->filter(fn ($m) => (int) $m->id === $meId)->values();
            }
        } else {
            $members = $all;
        }

        return view('sm.collab', [
            'schedule' => $schedule,
            'members' => $members,
            // Recording is the owner's to do: it captures other people.
            'isOwner' => $meId === (int) $schedule->anisystemUserId,
        ]);
    }

    /**
     * Keep a recording the room made.
     *
     * Anyone who may be in the room may keep what it produced — the check
     * that matters happened at the door, and a member who was present for a
     * walkthrough is not a stranger to it afterwards.
     *
     * Compressed on the way in, like every other video: a browser hands over
     * raw VP8/VP9 webm, which no phone in a field wants to download twice.
     */
    public function storeRecording(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'kind' => 'nullable|in:camera,call',
            'seconds' => 'nullable|integer|min:0',
            'clip' => 'required|file|max:2097152',
        ], [
            'title.required' => 'Give the recording a title.',
            'clip.max' => 'That recording is larger than 300 MB.',
        ]);

        try {
            $stored = \App\Support\VideoOptimizer::storeCompressed($request->file('clip'), 'collab-recordings');
        } catch (\Throwable $e) {
            return $this->jsonFail($e->getMessage() ?: 'The recording could not be saved.', 422);
        }

        $rec = \App\Models\TeamRecording::create([
            'scheduleId' => $schedule->id,
            'userId' => $meId,
            'kind' => $request->input('kind', 'camera'),
            'title' => trim((string) $request->input('title')),
            'description' => filled($request->input('description'))
                ? trim((string) $request->input('description'))
                : null,
            'path' => $stored['video'],
            'poster' => $stored['poster'] ?? null,
            'seconds' => $request->integer('seconds') ?: null,
            'deleteStatus' => 1,
        ]);

        // Everyone who was in the room should hear that it was kept — a
        // recording nobody knows about is a recording nobody watches.
        $notes = app(\App\Services\NotificationService::class);
        $url = route('sm.gallery', ['id' => $schedule->id, 'tab' => 'team']);
        foreach (ScheduleTeam::memberIds($schedule) as $memberId) {
            if ((int) $memberId === $meId) {
                continue;
            }
            $notes->notify(
                (int) $memberId,
                'team-recording',
                'New team recording: ' . $rec->title,
                'Saved to the Team box.',
                $url,
                $meId,
                (int) $schedule->id,
            );
        }

        return $this->jsonOk('Recording saved to the Team box.', [
            'id' => $rec->id,
            'url' => \App\Support\MediaStore::url($rec->path),
            'teamBoxUrl' => $url,
        ]);
    }
}
