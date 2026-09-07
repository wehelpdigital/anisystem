<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The way back, kept: each module's undo/redo stacks, per user per season.
 *
 * The stacks are the client's own shapes, stored as they are sent — the
 * server only enforces the scope, the fifteen-step cap and a size ceiling.
 * Logging out or walking to another page no longer forfeits the way back.
 */
class UndoJournalController extends BaseScheduleController
{
    private const MAX_STEPS = 15;

    private const MAX_BYTES = 2000000;   // strokes can carry pictures; the cap keeps rows sane

    public function get(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $module = $this->moduleKey($request);

        $row = DB::table('as_undo_steps')
            ->where('croppingScheduleId', $schedule->id)
            ->where('module', $module)
            ->where('userId', (int) Auth::id())
            ->first();

        $steps = $row ? json_decode((string) $row->steps, true) : null;

        return response()->json(['success' => true, 'data' => [
            'undo' => is_array($steps['undo'] ?? null) ? $steps['undo'] : [],
            'redo' => is_array($steps['redo'] ?? null) ? $steps['redo'] : [],
        ]]);
    }

    public function put(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $module = $this->moduleKey($request);

        $undo = array_slice((array) $request->input('undo', []), -self::MAX_STEPS);
        $redo = array_slice((array) $request->input('redo', []), -self::MAX_STEPS);

        // Older steps fall off first until the row fits the ceiling.
        $payload = json_encode(['undo' => $undo, 'redo' => $redo]);
        while (strlen($payload) > self::MAX_BYTES && (count($undo) || count($redo))) {
            if (count($redo) >= count($undo)) {
                array_shift($redo);
            } else {
                array_shift($undo);
            }
            $payload = json_encode(['undo' => $undo, 'redo' => $redo]);
        }

        DB::table('as_undo_steps')->updateOrInsert(
            [
                'croppingScheduleId' => $schedule->id,
                'module' => $module,
                'userId' => (int) Auth::id(),
            ],
            ['steps' => $payload, 'updated_at' => now()]
        );

        return response()->json(['success' => true, 'data' => [
            'kept' => ['undo' => count($undo), 'redo' => count($redo)],
        ]]);
    }

    /** 'activities', 'draw', 'map' — optionally suffixed with an identity. */
    private function moduleKey(Request $request): string
    {
        $module = (string) $request->query('module', '');
        if (! preg_match('/^(activities|draw|map)(:[A-Za-z0-9:_-]{1,30})?$/', $module)) {
            abort(response()->json(['success' => false, 'message' => 'Unknown undo journal.'], 422));
        }

        return $module;
    }
}
