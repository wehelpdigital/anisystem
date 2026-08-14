<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsCroppingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BaseScheduleController extends Controller
{
    use \App\Support\Concerns\GuardsScheduleWrites;

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

        // A grant can say 'none' — community access without the farm. Until
        // now nothing asked: effectiveOwnerId() is derived from the grant
        // whatever it permits, so a worker who had been given no schedule
        // access at all still resolved every one of the boss's schedules.
        // Every module reached through this method inherited that.
        if (! \App\Support\WorkerContext::canView()) {
            abort(response()->json([
                'success' => false,
                'message' => 'You do not have access to this farm\'s schedules.',
            ], 403));
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
            $this->assertCanEdit();
            $this->assertUnlocked($schedule);
        }

        return $schedule;
    }

    /**
     * Same as scheduleFromRequest(), but for writes that only record what
     * happened — day notes and their attachments.
     *
     * A worker can be given this without being given the run of the plan, so
     * these endpoints ask the looser question. Everything else about a write
     * still holds: a completed schedule is still locked.
     */
    protected function scheduleForNote(Request $request, string $key = 'scheduleId'): AsCroppingSchedule
    {
        $schedule = $this->schedule($request->query($key));

        if (! $request->isMethodSafe()) {
            if (! \App\Support\WorkerContext::canAddNotes()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to write notes on this schedule.',
                ], 403));
            }

            $this->assertUnlocked($schedule);
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
