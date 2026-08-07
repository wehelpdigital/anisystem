<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsCroppingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BaseScheduleController extends Controller
{
    /**
     * Resolve the owning schedule for the current client or abort 404.
     * Clients only ever see their own schedules (anisystemUserId scoping).
     */
    protected function schedule($scheduleId): AsCroppingSchedule
    {
        // Workers see their active boss's schedules; owners see their own.
        $ownerId = \App\Support\WorkerContext::effectiveOwnerId();

        $schedule = AsCroppingSchedule::active()
            ->forClient($ownerId)
            ->where('id', $scheduleId)
            ->first();

        if (!$schedule) {
            abort(response()->json(['success' => false, 'message' => 'Cropping schedule not found.'], 404));
        }

        return $schedule;
    }

    /**
     * Pull the schedule from `?scheduleId=...` (or override key).
     */
    protected function scheduleFromRequest(Request $request, string $key = 'scheduleId'): AsCroppingSchedule
    {
        $schedule = $this->schedule($request->query($key));

        if (! $request->isMethodSafe()) {
            // View-only workers can read but never write.
            if (! \App\Support\WorkerContext::canEdit()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'You have view-only access to this schedule.',
                ], 403));
            }

            // A completed schedule is locked: block every write in one place.
            if ($schedule->isLocked()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'This schedule is marked completed and locked. Reopen it in the Hub to make changes.',
                ], 423));
            }
        }

        return $schedule;
    }

    /**
     * Pull a query-string integer or abort with 400.
     */
    protected function queryId(Request $request, string $key = 'id'): int
    {
        $value = $request->query($key);
        if ($value === null || $value === '' || !is_numeric($value)) {
            abort(response()->json(['success' => false, 'message' => "Missing query parameter: {$key}"], 400));
        }
        return (int) $value;
    }

    protected function jsonOk(string $message = 'Success', array $extra = [])
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $extra));
    }

    protected function jsonFail(string $message = 'Error', int $status = 400, array $extra = [])
    {
        return response()->json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    /**
     * Best-effort broadcast of an activities-board change to the schedule's
     * Collab Room members (live per-change merge). Persistence has already
     * happened; a transport error never breaks the request, and the client's
     * reconcile handling covers a missed event.
     */
    protected function broadcastBoard(AsCroppingSchedule $schedule, string $type, array $data, ?int $versionId = null): void
    {
        // Only schedules with a Collab Room team have listeners — don't spend
        // realtime messages on solo schedules.
        if (! \App\Support\ScheduleTeam::hasTeam($schedule)) {
            return;
        }
        $driver = config('broadcasting.default');
        $ready = in_array($driver, ['pusher', 'reverb', 'ably'], true)
            && filled(config("broadcasting.connections.$driver.key"));
        if (! $ready) {
            return;
        }

        try {
            broadcast(new \App\Events\ActivityBoardChanged($schedule->id, [
                'type' => $type,
                'actorUserId' => (int) Auth::id(),
                'versionId' => $versionId,
                'data' => $data,
            ]));
        } catch (\Throwable $e) {
            // swallow — best-effort
        }
    }
}
