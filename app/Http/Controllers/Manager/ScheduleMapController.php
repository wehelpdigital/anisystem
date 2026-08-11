<?php

namespace App\Http\Controllers\Manager;

use App\Events\ScheduleMapLocation;
use App\Events\ScheduleMapPushed;
use App\Models\ScheduleMapObject;
use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The Collab Room map: persistent shapes the team draws over real ground,
 * plus ephemeral live positions. Shapes persist and broadcast; positions
 * only broadcast — where someone stood is not something to keep.
 */
class ScheduleMapController extends BaseScheduleController
{
    public function objects(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        if (! ScheduleTeam::canAccess($schedule, (int) Auth::id())) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $rows = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->orderBy('id')
            ->limit(2000)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['objects' => $rows->map(fn ($o) => $o->shaped())->all()],
        ]);
    }

    public function push(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'kind' => 'required|in:pen,line,path,rect,area,text',
            'color' => 'nullable|string|max:16',
            'width' => 'nullable|integer|min:1|max:20',
            'points' => 'required|array|min:1|max:2000',
            'points.*' => 'array|size:2',
            'label' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = ScheduleMapObject::create([
            'scheduleId' => $schedule->id,
            'userId' => $meId,
            'kind' => $request->input('kind'),
            'color' => $request->input('color'),
            'width' => (int) $request->input('width', 3),
            'points' => json_encode($request->input('points')),
            'label' => $request->input('label'),
            'deleteStatus' => 1,
        ]);

        $this->emit($schedule->id, ['action' => 'add', 'object' => $object->shaped(), 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'data' => ['object' => $object->shaped()]]);
    }

    /** Move or reshape an existing object; the team sees it land live. */
    public function update(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'points' => 'required|array|min:1|max:2000',
            'points.*' => 'array|size:2',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape no longer exists.', 404);
        }

        $object->update(['points' => json_encode($request->input('points'))]);
        $this->emit($schedule->id, ['action' => 'update', 'object' => $object->fresh()->shaped(), 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'data' => ['object' => $object->fresh()->shaped()]]);
    }

    public function remove(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $object = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape is already gone.', 404);
        }

        $object->update(['deleteStatus' => 0]);
        $this->emit($schedule->id, ['action' => 'remove', 'id' => (int) $object->id, 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }

    public function clear(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        ScheduleMapObject::active()->where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);
        $this->emit($schedule->id, ['action' => 'clear', 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => 'Map cleared for the team.']);
    }

    /**
     * Drawing-in-progress relay: the half-drawn shape under a member's finger,
     * so the room watches it grow instead of having it pop in finished.
     * Broadcast-only; `done` tells viewers to drop the ghost.
     */
    public function trace(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $me = Auth::user();
        if (! ScheduleTeam::canAccess($schedule, (int) $me->id)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'done' => 'nullable|boolean',
            'kind' => 'nullable|in:pen,line,path,rect,area',
            'color' => 'nullable|string|max:16',
            'points' => 'nullable|array|max:200',
            'points.*' => 'array|size:2',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Bad trace.', 422);
        }

        try {
            broadcast(new \App\Events\ScheduleMapTrace($schedule->id, [
                'userId' => (int) $me->id,
                'name' => (string) \Illuminate\Support\Str::of($me->full_name)->explode(' ')->first(),
                'done' => (bool) $request->boolean('done'),
                'kind' => $request->input('kind'),
                'color' => $request->input('color'),
                'points' => $request->input('points', []),
            ]));
        } catch (\Throwable $e) {
            // best-effort — a lost frame just makes the ghost jump
        }

        return response()->json(['success' => true]);
    }

    /** Live GPS position — broadcast to the room, never stored. */
    public function location(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $me = Auth::user();
        if (! ScheduleTeam::canAccess($schedule, (int) $me->id)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'acc' => 'nullable|numeric|min:0|max:100000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Bad position.', 422);
        }

        try {
            broadcast(new ScheduleMapLocation($schedule->id, [
                'userId' => (int) $me->id,
                'name' => (string) \Illuminate\Support\Str::of($me->full_name)->explode(' ')->first(),
                'lat' => (float) $request->input('lat'),
                'lng' => (float) $request->input('lng'),
                'acc' => (float) $request->input('acc', 0),
                'at' => now('Asia/Manila')->timestamp,
            ]));
        } catch (\Throwable $e) {
            // best-effort — a missed beacon just means a slightly staler dot
        }

        return response()->json(['success' => true]);
    }

    private function emit(int $scheduleId, array $payload): void
    {
        try {
            broadcast(new ScheduleMapPushed($scheduleId, $payload));
        } catch (\Throwable $e) {
            // best-effort — the poll fallback reconciles
        }
    }
}
