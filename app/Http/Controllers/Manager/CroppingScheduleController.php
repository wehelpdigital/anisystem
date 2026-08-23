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
    // The Hub predates BaseScheduleController and keeps its own resolver, so
    // it takes the write guards directly rather than by inheritance.
    use \App\Support\Concerns\GuardsScheduleWrites;

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
            ])
            // What the season cards read: the lots for their crops and day
            // counters, only the ANCHOR activities (day zero / transplant)
            // for LotCalendar, and the season's first and last planned dates
            // for the progress bar.
            ->with([
                'lots' => fn ($q) => $q->where('as_schedule_lots.deleteStatus', 1),
                'activities' => fn ($q) => $q->where('as_schedule_activities.deleteStatus', 1)
                    ->where(fn ($w) => $w->where('isDayZero', 1)->orWhere('isTransplant', 1))
                    ->with('lots:as_schedule_lots.id'),
            ])
            ->withMin(['activities as season_start' => fn ($q) => $q->where('as_schedule_activities.deleteStatus', 1)], 'targetDate')
            ->withMax(['activities as season_last' => fn ($q) => $q->where('as_schedule_activities.deleteStatus', 1)], 'targetDate')
            ->withMax(['activities as season_last_end' => fn ($q) => $q->where('as_schedule_activities.deleteStatus', 1)], 'targetEndDate')
            // When anything on this season was last touched. The schedule's
            // own updated_at only moves when its title or settings change,
            // which is rarely — the season people are actually working is the
            // one whose activities changed this morning.
            ->withMax(['activities as last_touched_at' => fn ($q) => $q->where('as_schedule_activities.deleteStatus', 1)], 'updated_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        /* How the shelf is arranged.
         *
         * Newest-first was the only order, and it is the least useful one on
         * a farm that has been running a while: the season you opened an hour
         * ago sinks under seasons you have not thought about since planting.
         * "Last updated" leads now, and "recently worked" reads the
         * activities rather than the schedule row, because editing a task is
         * what working a season looks like.
         */
        $sorts = [
            'updated' => ['label' => 'Last updated', 'column' => 'updated_at',
                'why' => 'What you changed most recently comes first.'],
            'active'  => ['label' => 'Recently worked', 'column' => 'last_touched_at',
                'why' => 'By the last task edited on the season, not the season itself.'],
            'created' => ['label' => 'Newest first', 'column' => 'created_at',
                'why' => 'The order they were made in.'],
            'title'   => ['label' => 'Name (A–Z)', 'column' => 'title',
                'why' => 'Alphabetical, for finding one you already know the name of.'],
        ];
        $sort = $request->query('sort');
        $sort = isset($sorts[$sort]) ? $sort : 'updated';

        if ($sort === 'title') {
            $query->orderBy('title');
        } else {
            // A season with no activities has no last-touched date; it sorts
            // to the end rather than to the top, which is where a NULL would
            // otherwise land on some engines.
            $query->orderByRaw('COALESCE(' . $sorts[$sort]['column'] . ', created_at) DESC');
        }

        $schedules = $query->paginate(12)->withQueryString();

        // What each season card says at a glance: the crops growing on it,
        // the leading lot's reading today (same arithmetic as Growth Stages,
        // via LotCalendar), and how far through its planned span it is.
        $today = \Illuminate\Support\Carbon::today();
        $cards = [];
        foreach ($schedules as $s) {
            [$dayZeroEff, $transplantEff] = \App\Support\LotCalendar::effectiveAnchors($s);
            $icons = [];
            // Every lot that can be read, not just the furthest on: a farm
            // with four lots was being told about one of them.
            $readings = [];
            foreach ($s->lots as $lot) {
                $crop = \App\Support\CropStages::normalize($lot->crop);
                if ($crop && ! isset($icons[$crop])) {
                    $icons[$crop] = \App\Support\CropStages::icon($lot->crop);
                }
                $age = \App\Support\LotCalendar::ageOf($lot, $today, $dayZeroEff[$lot->id] ?? null, $transplantEff[$lot->id] ?? null);
                if (! $age || ! $crop) {
                    continue;
                }
                $stage = \App\Support\CropStages::stageFor($crop, $age['day'], $age['counter']);
                // How far this lot is through its crop's whole calendar —
                // the stages it has finished, plus its way through this one.
                $through = null;
                if ($stage && ! empty($stage['count'])) {
                    $through = (int) round(100 * min(1, max(0,
                        ($stage['index'] + ($stage['progress'] ?? 0)) / $stage['count']
                    )));
                }
                $readings[] = [
                    'day' => $age['day'],
                    'counter' => $age['counter'],
                    'stage' => $stage['label'] ?? null,
                    'lot' => $lot->lotName,
                    'icon' => \App\Support\CropStages::icon($lot->crop),
                    'through' => $through,
                    'next' => $stage['next']['label'] ?? null,
                    'nextIn' => $stage['next']['inDays'] ?? null,
                ];
            }
            // The one furthest through its season leads, because that is the
            // lot with the most happening to it.
            usort($readings, fn ($a, $b) => $b['day'] <=> $a['day']);
            $reading = $readings[0] ?? null;

            $start = $s->season_start ? \Illuminate\Support\Carbon::parse($s->season_start) : null;
            $last = collect([$s->season_last, $s->season_last_end])->filter()
                ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d))->max();
            $progress = null;
            if ($start && $last && $last->gt($start)) {
                $progress = (int) round(100 * max(0, min(1,
                    $start->diffInDays($today, false) / max(1, $start->diffInDays($last))
                )));
            }

            $cards[$s->id] = [
                'icons' => array_slice(array_values($icons), 0, 3),
                'reading' => $reading,
                'readings' => $readings,
                'progress' => $progress,
                'window' => $start && $last
                    ? $start->format('M j') . ' – ' . $last->format('M j, Y')
                    : null,
            ];
        }

        // Full list (not just this page) for the Quick Capture schedule picker.
        $allSchedules = AsCroppingSchedule::active()
            ->forClient($ownerId)
            ->orderBy('title')
            ->get(['id', 'title']);

        // The one line worth reading before the list: how much there is, and
        // how much of it is live. Counted across every schedule, not just the
        // page being shown.
        $summary = [
            'schedules' => AsCroppingSchedule::active()->forClient($ownerId)->count(),
            'active' => AsCroppingSchedule::active()->forClient($ownerId)->where('status', 'active')->count(),
            'lots' => \App\Models\AsScheduleLot::where('deleteStatus', 1)
                ->whereIn('croppingScheduleId', AsCroppingSchedule::active()->forClient($ownerId)->select('id'))
                ->count(),
            'workers' => \App\Models\AsScheduleWorker::where('deleteStatus', 1)
                ->whereIn('croppingScheduleId', AsCroppingSchedule::active()->forClient($ownerId)->select('id'))
                ->count(),
            // What TODAY holds across every board — the number a farmer
            // opens this page wanting, before any totals.
            'today' => \App\Models\AsScheduleActivity::where('deleteStatus', 1)
                ->whereIn('croppingScheduleId', AsCroppingSchedule::active()->forClient($ownerId)->select('id'))
                ->where(function ($q) {
                    $d = \Illuminate\Support\Carbon::today()->toDateString();
                    $q->whereDate('targetDate', $d)
                        ->orWhere(fn ($w) => $w->whereDate('targetDate', '<=', $d)->whereDate('targetEndDate', '>=', $d));
                })
                ->count(),
        ];

        // Where "open today's board" lands: the season that is running, or
        // failing that the newest one on the shelf.
        $todaySchedule = AsCroppingSchedule::active()->forClient($ownerId)
            ->where('status', 'active')->orderByDesc('created_at')->first()
            ?? $schedules->first();
        $todayHref = $todaySchedule ? route('sm.activities', ['id' => $todaySchedule->id]) : null;

        /* Which hat this page is being read through.
         *
         * One account is often several things — a farm of one's own, work
         * on a neighbour's, sometimes both — and every screen below is
         * scoped to whichever is active. Somebody looking at an unfamiliar
         * list of seasons deserves to be told whose they are rather than
         * having to work it out from the names. */
        $grant = \App\Support\WorkerContext::activeGrant();
        $boss = $grant?->boss;

        return view('sm.index', compact('schedules', 'allSchedules', 'summary', 'cards', 'todayHref', 'sorts', 'sort') + [
            'isWorkerHere' => $grant !== null,
            'workerBossName' => $boss ? trim(($boss->firstName ?? '') . ' ' . ($boss->lastName ?? '')) : null,
            'hats' => \App\Support\UserHats::for(Auth::user()),
        ]);
    }

    public function create()
    {
        /* A season belongs to the farm that owns it.
         *
         * A worker is inside somebody else's farm; there is no farm of their
         * own here to add a season to, so the form is not theirs to open. The
         * dashboard withholds the buttons, and this answers anyone who types
         * the URL. */
        if ($no = $this->workerNoAccess('creating a cropping schedule', route('sm.index'), 'Back to the seasons')) {
            return $no;
        }

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
        // The form's own door, closed to the same people (see create()).
        if ($no = $this->workerNoAccess('creating a cropping schedule', route('sm.index'), 'Back to the seasons')) {
            return $no;
        }

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

        // Day counter is per-lot. The wizard's pick is the default applied to
        // every lot; lots can be changed later in the Lots module. Three modes:
        // DAT (sown then transplanted), DAS (direct seeded, never flips), DAP.
        $scheduleDayType = in_array(strtoupper((string) $request->input('dayType')), ['DAP', 'DAS', 'DAT'], true)
            ? strtoupper((string) $request->input('dayType'))
            : 'DAT';

        try {
            $schedule = DB::transaction(function () use ($request, $lotUnits, $skillKeys, $materialTypes, $materialUnits, $scheduleDayType) {
                $schedule = AsCroppingSchedule::create([
                    'anisystemUserId' => Auth::id(),
                    'usersId' => (int) config('anisystem.order_users_id', 1),
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'cropType' => $request->filled('cropType') ? trim($request->input('cropType')) : null,
                    'cropVariety' => $request->filled('cropVariety') ? trim($request->input('cropVariety')) : null,
                    'dayType' => $scheduleDayType,
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
                        'dayType' => in_array(strtoupper((string) ($row['dayType'] ?? '')), ['DAP', 'DAS', 'DAT'], true)
                            ? strtoupper((string) $row['dayType'])
                            : $scheduleDayType,
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
            // The documentation module is one list of typed entries now, so
            // the tile counts THAT list. It used to add up the three tables
            // the module was built from before it was unified — a season with
            // five old critical rules said "5 documents" and then opened on
            // "No documents yet", because the number and the page were
            // reading different shelves.
            'docEntries',
        ]);

        $documentationCount = (int) $schedule->doc_entries_count;

        $postHarvestCount = \App\Models\AsSchedulePostHarvest::active()
            ->where('croppingScheduleId', $schedule->id)
            ->count();

        $notesCount = \App\Models\AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->count();

        // How many pictures and videos the season has, counted the same way
        // the Media Box gathers them so the tile and the module agree.
        $mediaCount = 0;
        foreach (\App\Models\AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)->get() as $n) {
            $mediaCount += (int) filled($n->imagePath);
            foreach ((array) ($n->media ?? []) as $m) {
                if (in_array($m['type'] ?? 'image', ['image', 'video', 'drawing'], true) && filled($m['path'] ?? null)) {
                    $mediaCount++;
                }
            }
        }
        foreach (\App\Models\AsInlineNote::active()->where('croppingScheduleId', $schedule->id)->get() as $n) {
            foreach ((array) ($n->media ?? []) as $m) {
                if (in_array($m['type'] ?? 'image', ['image', 'video', 'drawing'], true) && filled($m['path'] ?? null)) {
                    $mediaCount++;
                }
            }
        }
        $mediaCount += \App\Models\ScheduleAiMessage::active()->where('scheduleId', $schedule->id)
            ->whereNotNull('imagePath')->count();

        $tip = \App\Support\FarmTips::forToday((int) \Illuminate\Support\Facades\Auth::id(), $schedule);

        $albumCount = \App\Models\AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->count();

        return view('sm.hub', compact('schedule', 'documentationCount', 'postHarvestCount', 'notesCount', 'mediaCount', 'tip', 'albumCount'));
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
        $this->assertCanEdit();
        $this->assertUnlocked($schedule);

        $validator = Validator::make($request->all(), [
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:5000',
            'defaultStaggerDays' => 'nullable|integer|min:0',
            // The daily digest, saved from the Notifications tab.
            'notifyWorkersDaily' => 'nullable|boolean',
            'notifyOwnerDaily'   => 'nullable|boolean',
            'notifyHour'         => 'nullable|integer|min:0|max:23',
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
        // Only touched when the notifications tab actually sent them, so a
        // title edit never quietly switches somebody's mail on or off.
        if ($request->has('notifyWorkersDaily')) {
            $payload['notifyWorkersDaily'] = $request->boolean('notifyWorkersDaily');
            $payload['notifyOwnerDaily'] = $request->boolean('notifyOwnerDaily');
            $payload['notifyHour'] = max(0, min(23, (int) $request->input('notifyHour', 6)));
        }

        $schedule->update($payload);

        return response()->json(['success' => true, 'message' => 'Schedule updated.', 'data' => $schedule]);
    }

    /**
     * "Send me one now" from the Notifications tab — the same digest the
     * morning run would send, to the owner only, so the layout and the wording
     * can be checked without waiting for tomorrow or mailing the whole crew.
     */
    public function sendTestDigest(Request $request, \App\Services\DailyDigestService $digests)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));
        // Sends real mail; not deliberately locked, because a completed
        // season's digest is still a reasonable thing for an owner to check.
        $this->assertCanEdit();
        $result = $digests->sendFor($schedule, null, true);

        if ($result['sent'] < 1) {
            return response()->json([
                'success' => false,
                'message' => $result['skipped'] > 0
                    ? 'Could not send — check your account has an email address and that SMTP is set up in the mother app.'
                    : 'Nothing scheduled for today or tomorrow, so there is nothing to send.',
            ], 422);
        }

        return response()->json(['success' => true, 'message' => 'Sent. Check your inbox.']);
    }

    /**
     * Convert the schedule's day-counter type (e.g. DAS → DAT). Lightweight
     * endpoint so the Activities view can switch it in place and relabel every
     * counter without a full settings save.
     */
    public function setDayType(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'), true);
        // Changing this relabels every day counter on the board, for everyone.
        $this->assertCanEdit();
        $this->assertUnlocked($schedule);

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
        // Only the owner may delete a schedule — never a worker, even an
        // editing one. The question is whose farm this request is in, not
        // whether the user is a worker anywhere: an owner who also helps on a
        // neighbour's farm was being refused on her own land.
        if (\App\Support\WorkerContext::inWorkerContext()) {
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
        // Copying a season creates a whole farm's worth of rows against the
        // owner's plan limit. Not a thing a view-only worker does.
        $this->assertCanEdit();

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

        // This controller has its own door, so it needs its own version of
        // the check the shared one makes: a grant that permits no schedule
        // access should not resolve a schedule.
        if (! \App\Support\WorkerContext::canView()) {
            if ($json) {
                abort(response()->json(['success' => false, 'message' => 'You do not have access to this farm\'s schedules.'], 403));
            }
            abort(403);
        }

        return $schedule;
    }
}
