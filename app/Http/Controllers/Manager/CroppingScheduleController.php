<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsCroppingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Cropping schedule list / create / hub / settings for AniSystem clients.
 *
 * Ported from the mother app's CroppingScheduleController. The mother's
 * single "setup" SPA page is split here into a module launcher ("hub")
 * plus dedicated per-module pages ("settingsPage" being the first).
 * Ownership is anisystemUserId (forClient) — never usersId.
 */
class CroppingScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = AsCroppingSchedule::active()
            ->forClient(Auth::id())
            ->withCount([
                'lots as lots_count' => fn ($q) => $q->where('as_schedule_lots.deleteStatus', 1),
                'workers as workers_count' => fn ($q) => $q->where('as_schedule_workers.deleteStatus', 1),
                'activities as activities_count' => fn ($q) => $q->where('as_schedule_activities.deleteStatus', 1),
            ]);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        return view('sm.index', compact('schedules'));
    }

    public function create()
    {
        return view('sm.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ], [
            'title.required' => 'Cropping schedule title is required.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $schedule = AsCroppingSchedule::create([
                'anisystemUserId' => Auth::id(),
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);

            // Every schedule needs an Original version — the version-scoped
            // relations (activities/drafts/notes/markers) can't see rows
            // on a schedule with zero version rows.
            \App\Models\AsScheduleActivityVersion::create([
                'croppingScheduleId' => $schedule->id,
                'versionName' => 'Original',
                'isOriginal' => 1,
                'isActive' => 1,
                'versionOrder' => 0,
                'deleteStatus' => 1,
            ]);

            return redirect()
                ->route('sm.hub', ['id' => $schedule->id])
                ->with('success', 'Cropping schedule created. Now set up its modules.');
        } catch (\Throwable $e) {
            Log::error('CroppingSchedule store failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create cropping schedule.');
        }
    }

    /**
     * The module launcher page (replaces the mother's tabbed "setup" page).
     */
    public function hub(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));

        $schedule->loadCount([
            'lots',
            'workers',
            'materials',
            'services',
            'activities',    // relation is already active-version + non-draft scoped
            'irrigations',
            'attachments',
            'criticalRules',
        ]);
        $schedule->load('protocol');

        $hasProtocol = $schedule->protocol
            && ($schedule->protocol->protocolContent || $schedule->protocol->protocolFile);

        $documentationCount = (int) $schedule->attachments_count
            + (int) $schedule->critical_rules_count
            + ($hasProtocol ? 1 : 0);

        return view('sm.hub', compact('schedule', 'documentationCount'));
    }

    /**
     * Settings module page (Basic Info + Default Groupings).
     */
    public function settingsPage(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));
        $schedule->load(['lots', 'defaultGroupings.lots']);

        return view('sm.settings', compact('schedule'));
    }

    public function update(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'), true);

        $validator = Validator::make($request->all(), [
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:5000',
            'dayType'            => 'nullable|in:DAP,DAS,DAT',
            'defaultStaggerDays' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'title'       => $request->title,
            'description' => $request->description,
        ];
        if ($request->filled('dayType')) {
            $payload['dayType'] = $request->dayType;
        }
        if ($request->has('defaultStaggerDays')) {
            $payload['defaultStaggerDays'] = (int) $request->input('defaultStaggerDays', 0);
        }

        $schedule->update($payload);

        return response()->json(['success' => true, 'message' => 'Schedule updated.', 'data' => $schedule]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'), true);
        $schedule->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Schedule deleted.']);
    }

    /**
     * Resolve an owned schedule from `?id=` or abort.
     * `$json = true` for AJAX endpoints (JSON envelope aborts),
     * false for page views (plain HTTP aborts).
     */
    protected function findOwnedOrFail($id, bool $json = false): AsCroppingSchedule
    {
        if (!$id) {
            if ($json) {
                abort(response()->json(['success' => false, 'message' => 'Missing schedule id.'], 400));
            }
            abort(400, 'Missing schedule id.');
        }

        $schedule = AsCroppingSchedule::active()
            ->forClient(Auth::id())
            ->where('id', $id)
            ->first();

        if (!$schedule) {
            if ($json) {
                abort(response()->json(['success' => false, 'message' => 'Cropping schedule not found.'], 404));
            }
            abort(404, 'Cropping schedule not found.');
        }

        return $schedule;
    }
}
