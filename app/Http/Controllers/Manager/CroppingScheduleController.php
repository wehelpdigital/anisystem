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
     * Create a schedule from the step-by-step wizard, optionally seeding crop
     * info, lots, workers, materials and services in one transaction. Every
     * sub-step is optional (skippable); child rows are best-effort sanitized so
     * a smooth wizard never hard-fails on an incomplete row.
     *
     * Responds JSON {success, message, data:{scheduleId, redirect}}.
     */
    public function storeWizard(Request $request)
    {
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

        try {
            $schedule = DB::transaction(function () use ($request, $lotUnits, $skillKeys, $materialTypes, $materialUnits) {
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

        $postHarvestCount = \App\Models\AsSchedulePostHarvest::active()
            ->where('croppingScheduleId', $schedule->id)
            ->count();

        return view('sm.hub', compact('schedule', 'documentationCount', 'postHarvestCount'));
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
