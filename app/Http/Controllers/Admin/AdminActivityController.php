<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityReport;
use App\Support\UserActions;
use Illuminate\Http\Request;

/**
 * What members do in the app (2026-11-26, the owner: "track inside the app all the activities of the user, then create
 * a general report for it in the admin panel for us to know what are they trying to do? and check also per user if we
 * click on the user in the admin panel"). The report is App\Support\ActivityReport; one member's pages and deeds
 * together are App\Support\UserActions::timeline, shown in the Clients sheet.
 */
class AdminActivityController extends Controller
{
    public function page()
    {
        return view('admin.activity');
    }

    public function data(Request $request)
    {
        $report = ActivityReport::build((int) $request->input('days', 30), $request->boolean('team'), $request->boolean('fresh'));

        return response()->json(['success' => true, 'message' => 'ok', 'data' => $report]);
    }

    /** One member: what they did at a glance, then everything in order, 40 lines at a time. */
    public function client(Request $request, int $id)
    {
        User::findOrFail($id);
        $offset = max(0, (int) $request->input('offset', 0));
        $data = ['timeline' => UserActions::timeline($id, $offset)];
        if ($offset === 0) {
            $data['profile'] = UserActions::profile($id);
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => $data]);
    }
}
