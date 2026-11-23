<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\InterestReport;
use App\Support\PageViews;
use Illuminate\Http\Request;

/**
 * What people read and use (2026-11-23, the owner: "i wanna have the option to view what users opened (pages) to know
 * what makes them interested and a report module as well"). The report reads App\Support\InterestReport; one
 * member's pages are App\Support\PageViews::forUser, shown in the Clients sheet.
 */
class AdminInterestsController extends Controller
{
    public function page()
    {
        return view('admin.interests');
    }

    /** The report for one period and one audience. */
    public function data(Request $request)
    {
        $report = InterestReport::build((int) $request->input('days', 30), (string) $request->input('who', 'all'), $request->boolean('fresh'));

        return response()->json(['success' => true, 'message' => 'ok', 'data' => $report]);
    }

    /** One member's pages, newest first, paged for the client sheet's list. */
    public function clientViews(Request $request, int $id)
    {
        User::findOrFail($id);
        $page = PageViews::forUser($id, $request->filled('cursor') ? (int) $request->input('cursor') : null);

        return response()->json(['success' => true, 'message' => 'ok', 'data' => $page]);
    }
}
