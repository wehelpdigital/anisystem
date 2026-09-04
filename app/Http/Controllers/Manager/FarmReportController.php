<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsFarmReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The farm's report shelf: freezing a computed report so it can ride into an
 * Anee chat, and (in later kinds) the AI-written season reads.
 *
 * The attach walk mirrors when-to-plant exactly: snapshot → the composer
 * boots with ?freport=ID → preview() weighs the tokens → ask() folds
 * contextFor() into the priced prompt.
 */
class FarmReportController extends BaseScheduleController
{
    /**
     * Freeze a computed report (labor / expenses / profit) as a shelf row.
     * The body is the report SAID IN TEXT — the same rendering Copy-as-Text
     * gives the farmer — because what Anee reads should be what they saw.
     */
    public function snapshot(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));

        $v = Validator::make($request->all(), [
            'kind' => 'required|in:labor,expenses,profit',
            'title' => 'required|string|max:180',
            'body' => 'required|string|max:60000',
            'params' => 'nullable|array',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $row = AsFarmReport::create([
            'userId' => Auth::id(),
            'croppingScheduleId' => $schedule->id,
            'kind' => $request->input('kind'),
            'title' => trim($request->input('title')),
            'params' => $request->input('params') ?: null,
            'body' => $request->input('body'),
            'status' => 'ready',
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Report frozen for the chat.', ['data' => ['id' => $row->id]]);
    }

    /** What an attached report adds to a question — the composer's estimate. */
    public function preview(int $id)
    {
        $ctx = self::contextFor($id, (int) Auth::id());
        if (! $ctx) {
            return $this->jsonFail('That report is gone.', 404);
        }

        return $this->jsonOk('ok', ['data' => [
            'id' => $id,
            'title' => $ctx['title'],
            'tokens' => (int) ceil(mb_strlen($ctx['text']) / 4),
        ]]);
    }

    /**
     * The attached report as prompt context. Static, like the when-to-plant
     * twin, so AiController::ask can fold it in without a request cycle.
     */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = AsFarmReport::where('userId', $userId)
            ->where('id', $id)
            ->where('deleteStatus', 1)
            ->where('status', 'ready')
            ->first();
        if (! $r || trim((string) $r->body) === '') {
            return null;
        }
        $text = "\n\n--- ATTACHED: Farm report (the farmer generated this from their own season's records; treat it as shared context) ---\n"
            . 'Report: ' . $r->title . "\n"
            . $r->body
            . "\n--- END OF ATTACHED REPORT ---\n";

        return ['title' => $r->title, 'text' => $text];
    }
}
