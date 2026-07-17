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
        $schedule = AsCroppingSchedule::active()
            ->forClient(Auth::id())
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
        return $this->schedule($request->query($key));
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
}
