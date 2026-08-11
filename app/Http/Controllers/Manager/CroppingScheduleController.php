<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleMaterial;
use App\Models\AsScheduleService;
use App\Models\AsScheduleWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // Workers list their active boss's schedules; owners list their own.
        $ownerId = \App\Support\WorkerContext::effectiveOwnerId();
        $query = AsCroppingSchedule::active()
            ->forClient($ownerId)
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

        $schedules = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        // Full list (not just this page) for the Quick Capture schedule picker.
        $allSchedules = AsCroppingSchedule::active()
            ->forClient($ownerId)
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('sm.index', compact('schedules', 'allSchedules'));
    }

    public function create()
    {
        if ($redirect = $this->guardScheduleLimit()) {
            return $redirect;
        }

        return view('sm.create');
    }

    /**
     * Basic tier caps the number of schedules. Returns a redirect when the
     * member is over their limit, or null when they may create another.
     */
    private function guardScheduleLimit()
    {
        $user = Auth::user();
        if ($user && ! $user->canCreateSchedule()) {
            $limit = $user->scheduleLimit();
            return redirect()->route('sm.index')->with('error',
                'Your ' . ucfirst($user->planTier()) . ' plan allows ' . ($limit === 0 ? 'no' : ('up to ' . $limit))
                . ' cropping schedules. Upgrade to Boss for unlimited schedules.');
        }

        return null;
    }

    public function store(Request $request)
    {
        if ($redirect = $this->guardScheduleLimit()) {
            return $redirect;
        }

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
     * Create a schedule from the step-by-step wizard, optionally seeding crop
     * info, lots, workers, materials and services in one transaction. Every
     * sub-step is optional (skippable); child rows are best-effort sanitized so
     * a smooth wizard never hard-fails on an incomplete row.
     *
     * Responds JSON {success, message, data:{scheduleId, redirect}}.
     */
    public function storeWizard(Request $request)
    {
        if ($request->user() && ! $request->user()->canCreateSchedule()) {
            $limit = $request->user()->scheduleLimit();
            return response()->json(['success' => false, 'message' =>
                'Your ' . ucfirst($request->user()->planTier()) . ' plan allows ' . ($limit === 0 ? 'no' : ('up to ' . $limit))
                . ' schedules. Upgrade to Boss for unlimited.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'cropType' => 'nullable|string|max:100',
            'cropVariety' => 'nullable|string|max:255',
            'dayType' => 'nullable|in:DAP,DAS,DAT',
            'lots' => 'nullable|array',
            'workers' => 'nullable|array',
            'materials' => 'nullable|array',
            'services' => 'nullable|array',
        ], [
            'title.required' => 'Give your cropping schedule a title to continue.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $lotUnits = ['hectare', 'sqm', 'acre'];
        $skillKeys = array_keys(AsScheduleWorker::SKILLS);
        $materialTypes = ['granular', 'foliar', 'pesticide', 'herbicide', 'molluscicide', 'fungicide', 'fertilizer', 'seed', 'other'];
        $materialUnits = ['kg', 'g', 'ml', 'l', 'bottle', 'sachet', 'piece', 'pack'];

        // Day counter is per-lot now (DAP, or DAS→DAT). The wizard's pick is the
        // default applied to every lot; lots can be changed later in the Lots
        // module. DAT collapses into the DAS/DAT mode.
        $scheduleDayType = $request->input('dayType') === 'DAP' ? 'DAP' : 'DAS';

        try {
            $schedule = DB::transaction(function () use ($request, $lotUnits, $skillKeys, $materialTypes, $materialUnits, $scheduleDayType) {
                $schedule = AsCroppingSchedule::create([
                    'anisystemUserId' => Auth::id(),
                    'usersId' => (int) config('anisystem.order_users_id', 1),
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'cropType' => $request->filled('cropType') ? trim($request->input('cropType')) : null,
                    'cropVariety' => $request->filled('cropVariety') ? trim($request->input('cropVariety')) : null,
                    'dayType' => $request->input('dayType') ?: 'DAS',
                    'status' => 'setup',
                    'isActive' => 1,
                    'deleteStatus' => 1,
                ]);

                AsScheduleActivityVersion::create([
                    'croppingScheduleId' => $schedule->id,
                    'versionName' => 'Original',
                    'isOriginal' => 1,
                    'isActive' => 1,
                    'versionOrder' => 0,
                    'deleteStatus' => 1,
                ]);

                // --- Lots ---
                foreach ((array) $request->input('lots', []) as $row) {
                    $name = trim((string) ($row['lotName'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $unit = strtolower((string) ($row['lotSizeUnit'] ?? 'hectare'));
                    AsScheduleLot::create([
                        'croppingScheduleId' => $schedule->id,
                        'lotName' => mb_substr($name, 0, 255),
                        'lotSize' => is_numeric($row['lotSize'] ?? null) ? max(0, (float) $row['lotSize']) : 0,
                        'lotSizeUnit' => in_array($unit, $lotUnits, true) ? $unit : 'hectare',
                        'variety' => ! empty($row['variety']) ? mb_substr(trim($row['variety']), 0, 255) : null,
                        'dayZeroDate' => $this->sanitizeDate($row['dayZeroDate'] ?? null),
                        'dayType' => ($row['dayType'] ?? null) === 'DAP' ? 'DAP' : $scheduleDayType,
                        'notes' => ! empty($row['notes']) ? mb_substr(trim($row['notes']), 0, 2000) : null,
                        'deleteStatus' => 1,
                    ]);
                }

                // --- Workers ---
                $priorityFallback = 1;
                foreach ((array) $request->input('workers', []) as $row) {
                    $name = trim((string) ($row['workerName'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $skills = array_values(array_intersect($skillKeys, (array) ($row['skills'] ?? [])));
                    AsScheduleWorker::create([
                        'croppingScheduleId' => $schedule->id,
                        'workerName' => mb_substr($name, 0, 255),
                        'costPerHalfDay' => is_numeric($row['costPerHalfDay'] ?? null) ? max(0, (float) $row['costPerHalfDay']) : 0,
                        'priority' => isset($row['priority']) && (int) $row['priority'] >= 1 ? (int) $row['priority'] : $priorityFallback,
                        'skills' => $skills ?: null,
                        'notes' => ! empty($row['notes']) ? mb_substr(trim($row['notes']), 0, 2000) : null,
                        'deleteStatus' => 1,
                    ]);
                    $priorityFallback++;
                }

                // --- Materials ---
                foreach ((array) $request->input('materials', []) as $row) {
                    $name = trim((string) ($row['materialName'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $type = strtolower((string) ($row['materialType'] ?? 'other'));
                    $unit = strtolower((string) ($row['unitOfMeasure'] ?? 'kg'));
                    $qty = is_numeric($row['priceQuantity'] ?? null) ? (float) $row['priceQuantity'] : 1;
                    AsScheduleMaterial::create([
                        'croppingScheduleId' => $schedule->id,
                        'materialName' => mb_substr($name, 0, 255),
                        'description' => ! empty($row['description']) ? mb_substr(trim($row['description']), 0, 2000) : null,
                        'materialType' => in_array($type, $materialTypes, true) ? $type : 'other',
                        'unitOfMeasure' => in_array($unit, $materialUnits, true) ? $unit : 'kg',
                        'priceAmount' => is_numeric($row['priceAmount'] ?? null) ? max(0, (float) $row['priceAmount']) : 0,
                        'priceQuantity' => $qty > 0 ? $qty : 1,
                        'deleteStatus' => 1,
                    ]);
                }

                // --- Services ---
                foreach ((array) $request->input('services', []) as $row) {
                    $name = trim((string) ($row['serviceName'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    AsScheduleService::create([
                        'croppingScheduleId' => $schedule->id,
                        'serviceName' => mb_substr($name, 0, 255),
                        'description' => ! empty($row['description']) ? mb_substr(trim($row['description']), 0, 2000) : null,
                        'serviceCost' => is_numeric($row['serviceCost'] ?? null) ? max(0, (float) $row['serviceCost']) : 0,
                        'deleteStatus' => 1,
                    ]);
                }

                return $schedule;
            });
        } catch (\Throwable $e) {
            Log::error('CroppingSchedule wizard store failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'We could not create your schedule. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cropping schedule created.',
            'data' => [
                'scheduleId' => $schedule->id,
                'redirect' => route('sm.hub', ['id' => $schedule->id]),
            ],
        ]);
    }

    /**
     * Accepts Y-m-d (or anything Carbon can parse) and returns Y-m-d, else null.
     */
    private function sanitizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The module launcher page (replaces the mother's tabbed "setup" page).
     */
    public function hub(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));

        // Ensure a public share token exists so the hub's Share sheet always
        // has a link to hand out (older schedules were created without one).
        if (empty($schedule->shareToken)) {
            $schedule->shareToken = \Illuminate\Support\Str::random(32);
            $schedule->save();
        }

        $schedule->loadCount([
            'lots',
            'workers',
            'activities',    // relation is already active-version + non-draft scoped
            'attachments',
            'criticalRules',
        ]);
        $schedule->load('protocol');

        $hasProtocol = $schedule->protocol
            && ($schedule->protocol->protocolContent || $schedule->protocol->protocolFile);

        $documentationCount = (int) $schedule->attachments_count
            + (int) $schedule->critical_rules_count
            + ($hasProtocol ? 1 : 0);

        $postHarvestCount = \App\Models\AsSchedulePostHarvest::active()
            ->where('croppingScheduleId', $schedule->id)
            ->count();

        $notesCount = \App\Models\AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->count();

        return view('sm.hub', compact('schedule', 'documentationCount', 'postHarvestCount', 'notesCount'));
    }

    /** Reports landing for a schedule — labor and post-harvest figures. */
    public function reports(Request $request)
    {
        // The observations count went with the row that showed it: that entry
        // only linked to the Post-harvest module, which has its own hub tile.
        return view('sm.reports', ['schedule' => $this->findOwnedOrFail($request->query('id'))]);
    }

    /** Toggle a schedule between 'setup' (editable) and 'completed' (locked). */
    public function setStatus(Request $request)
    {
        if (! \App\Support\WorkerContext::canEdit()) {
            return response()->json(['success' => false, 'message' => 'You have view-only access to this schedule.'], 403);
        }
        $schedule = $this->findOwnedOrFail($request->input('id'), true);
        $completed = $request->input('status') === AsCroppingSchedule::STATUS_COMPLETED;
        $schedule->update(['status' => $completed ? AsCroppingSchedule::STATUS_COMPLETED : AsCroppingSchedule::STATUS_SETUP]);

        return response()->json([
            'success' => true,
            'message' => $completed
                ? 'Schedule marked completed — it is now locked.'
                : 'Schedule reopened for editing.',
            'data' => ['status' => $schedule->status, 'locked' => $schedule->isLocked()],
        ]);
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
            'defaultStaggerDays' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'title'       => $request->title,
            'description' => $request->description,
        ];
        if ($request->has('defaultStaggerDays')) {
            $payload['defaultStaggerDays'] = (int) $request->input('defaultStaggerDays', 0);
        }

        $schedule->update($payload);

        return response()->json(['success' => true, 'message' => 'Schedule updated.', 'data' => $schedule]);
    }

    /**
     * Convert the schedule's day-counter type (e.g. DAS → DAT). Lightweight
     * endpoint so the Activities view can switch it in place and relabel every
     * counter without a full settings save.
     */
    public function setDayType(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'), true);

        $validator = Validator::make($request->all(), [
            'dayType' => 'required|in:DAP,DAS,DAT',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid day type.'], 422);
        }

        $schedule->update(['dayType' => $request->dayType]);

        return response()->json([
            'success' => true,
            'message' => 'Counters now use ' . $request->dayType . '.',
            'data' => ['dayType' => $schedule->dayType],
        ]);
    }

    public function destroy(Request $request)
    {
        // Only the owner may delete a schedule — never a worker (even editors).
        if (\App\Support\WorkerContext::isWorker()) {
            return response()->json(['success' => false, 'message' => 'Only the farm owner can delete a schedule.'], 403);
        }
        $schedule = $this->findOwnedOrFail($request->query('id'), true);
        $schedule->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Schedule deleted.']);
    }

    /**
     * Deep-duplicate an entire schedule — every module and version — into a
     * new "Copy of …" schedule owned by the same client. Child ids are remapped
     * so the copy is fully independent (uploaded files are shared by path).
     */
    public function duplicate(Request $request)
    {
        $old = $this->findOwnedOrFail($request->query('id'), true);

        try {
            $new = DB::transaction(function () use ($old) {
                $copy = $old->replicate();
                $copy->title = 'Copy of ' . $old->title;
                $copy->status = 'setup';
                $copy->isPublic = 0;
                $copy->publishedAt = null;
                $copy->publicSummary = null;
                $copy->publicRegion = null;
                $copy->deleteStatus = 1;
                // shareToken is unique — a copy needs its own.
                if (\Illuminate\Support\Facades\Schema::hasColumn('as_cropping_schedules', 'shareToken')) {
                    $copy->shareToken = \Illuminate\Support\Str::random(32);
                }
                $copy->save();

                $rep = function ($row, array $attrs = []) use ($copy) {
                    $c = $row->replicate();
                    $c->croppingScheduleId = $copy->id;
                    foreach ($attrs as $k => $v) $c->{$k} = $v;
                    $c->save();
                    return $c;
                };
                $mapIds = fn ($ids, $map) => collect($ids)->map(fn ($i) => $map[$i] ?? null)->filter()->values()->all();

                // Lots
                $lotMap = [];
                foreach ($old->lots as $lot) $lotMap[$lot->id] = $rep($lot)->id;

                // Workers + their off dates/days
                $workerMap = [];
                foreach ($old->workers()->with(['offDates', 'offDays'])->get() as $w) {
                    $nw = $rep($w);
                    $workerMap[$w->id] = $nw->id;
                    foreach ($w->offDates as $od) { $x = $od->replicate(); $x->workerId = $nw->id; $x->save(); }
                    foreach ($w->offDays as $od) { $x = $od->replicate(); $x->workerId = $nw->id; $x->save(); }
                }

                if ($old->protocol) $rep($old->protocol);
                foreach ($old->materials as $m) $rep($m);
                foreach ($old->services as $s) $rep($s);

                // Doc tags → entries (remap tagId)
                $tagMap = [];
                foreach ($old->docTags as $t) $tagMap[$t->id] = $rep($t)->id;
                foreach ($old->docEntries as $e) $rep($e, ['tagId' => $e->tagId ? ($tagMap[$e->tagId] ?? null) : null]);

                // Versions
                $versionMap = [];
                foreach ($old->versions as $v) $versionMap[$v->id] = $rep($v)->id;

                // Activities (all versions + drafts) + items + lot/worker pivots
                $activities = \App\Models\AsScheduleActivity::where('croppingScheduleId', $old->id)
                    ->where('deleteStatus', 1)->with(['items', 'lots:id', 'workers:id'])->get();
                foreach ($activities as $a) {
                    $na = $rep($a, [
                        'versionId' => $a->versionId ? ($versionMap[$a->versionId] ?? null) : null,
                        'sourceActivityId' => null,
                    ]);
                    foreach ($a->items as $it) { $x = $it->replicate(); $x->activityId = $na->id; $x->save(); }
                    $na->lots()->attach($mapIds($a->lots->pluck('id'), $lotMap));
                    $na->workers()->attach($mapIds($a->workers->pluck('id'), $workerMap));
                }

                // Date notes + progress markers (all versions)
                foreach (\App\Models\AsScheduleDateNote::where('croppingScheduleId', $old->id)->where('deleteStatus', 1)->get() as $n) {
                    $rep($n, ['versionId' => $n->versionId ? ($versionMap[$n->versionId] ?? null) : null]);
                }
                foreach (\App\Models\AsScheduleProgressMarker::where('croppingScheduleId', $old->id)->where('deleteStatus', 1)->get() as $n) {
                    $rep($n, ['versionId' => $n->versionId ? ($versionMap[$n->versionId] ?? null) : null]);
                }

                // Irrigations + pivots
                foreach ($old->irrigations()->with(['lots:id', 'workers:id'])->get() as $irr) {
                    $ni = $rep($irr, ['assignedWorkerId' => $irr->assignedWorkerId ? ($workerMap[$irr->assignedWorkerId] ?? null) : null]);
                    $ni->lots()->attach($mapIds($irr->lots->pluck('id'), $lotMap));
                    $ni->workers()->attach($mapIds($irr->workers->pluck('id'), $workerMap));
                }

                foreach ($old->attachments as $x) $rep($x);
                foreach ($old->criticalRules as $x) $rep($x);

                // Default groupings + lot pivot
                foreach ($old->defaultGroupings()->with('lots:id')->get() as $g) {
                    $ng = $rep($g);
                    $ng->lots()->attach($mapIds($g->lots->pluck('id'), $lotMap));
                }

                // Post-harvest observations (remap lotId) + notes
                foreach (\App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $old->id)->where('deleteStatus', 1)->get() as $ph) {
                    $rep($ph, ['lotId' => $ph->lotId ? ($lotMap[$ph->lotId] ?? null) : null]);
                }
                foreach (\App\Models\AsScheduleNote::where('croppingScheduleId', $old->id)->where('deleteStatus', 1)->get() as $nt) {
                    $rep($nt);
                }

                return $copy;
            });
        } catch (\Throwable $e) {
            Log::error('Schedule duplicate failed', ['id' => $old->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not duplicate the schedule: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule duplicated.',
            'data' => ['id' => $new->id, 'title' => $new->title, 'hubUrl' => route('sm.hub', ['id' => $new->id])],
        ]);
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
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
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
