<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\WorkerGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Open a schedule, from wherever you happen to be standing.
 *
 * A notification about a team call or a chat message names a schedule, and
 * that schedule may belong to somebody else's farm. Whether the request can
 * see it depends on WorkerContext's idea of which farm the session is
 * currently in — and that defaults to the reader's OWN farm as soon as they
 * have one of their own. So a worker who also grows something of their own
 * tapped a notification about their boss's call and got a 404: the link was
 * correct, the session was simply looking somewhere else.
 *
 * This puts the session in the right farm first, then goes where the link
 * meant. It will only ever switch to a farm the reader actually holds an
 * active grant for.
 */
class EnterScheduleController extends Controller
{
    public function __invoke(Request $request, int $schedule)
    {
        $me = (int) Auth::id();

        $row = AsCroppingSchedule::active()->where('id', $schedule)->first();
        if (! $row) {
            abort(404);
        }

        $owner = (int) $row->anisystemUserId;

        if ($owner !== $me) {
            $granted = WorkerGrant::active()
                ->where('workerUserId', $me)
                ->where('bossUserId', $owner)
                ->where('status', WorkerGrant::STATUS_ACTIVE)
                ->exists();
            if (! $granted) {
                abort(404);
            }
            $request->session()->put('activeBossId', $owner);
        } else {
            // Their own farm: make sure a previously-chosen boss is not still
            // shadowing it, or the redirect lands on the same 404 it fixes.
            $request->session()->put('activeBossId', $me);
        }

        // Only our own module routes, and only ones that take a schedule —
        // an open redirect parameter is a phishing link with our name on it.
        $to = (string) $request->query('to', 'sm.hub');
        $allowed = [
            'sm.hub', 'sm.collab', 'sm.activities', 'sm.notes',
            'sm.gallery', 'sm.maps', 'sm.draw', 'sm.ai', 'sm.growth',
        ];
        if (! in_array($to, $allowed, true)) {
            $to = 'sm.hub';
        }

        $params = ['id' => $row->id];
        if ($request->filled('tab')) {
            $params['tab'] = (string) $request->query('tab');
        }

        return redirect()->route($to, $params);
    }
}
