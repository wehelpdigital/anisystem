<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Models\AsProtocol;
use App\Models\AsProtocolVersion;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleLot;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropStages;
use App\Support\HtmlSanitizer;
use App\Support\MediaStore;
use App\Support\Tier;
use App\Support\WorkerContext;
use App\Http\Controllers\UserTagController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * PROTOCOL BUILDER — a farmer writes their own crop protocol.
 *
 * A protocol is a crop, a variety, a way of counting days, and a list of
 * tasks each pinned to a day of that count (DAS 14, DAT 7 …). Every task
 * carries what to apply as groups of items ("per knapsack: 50 ml of X"),
 * a note, an importance and how many hands it needs. The page autosaves
 * every change and keeps undo/redo on the row; a finished protocol can be
 * ported into a real cropping schedule from a start date, or handed to
 * Anee for a review that costs credits.
 *
 * A protocol has VERSIONS ("Wet season", "Dry season"): each carries its
 * own tasks, its own list of materials (what the tasks draw from, with the
 * total on hand), its own rules-and-notes document and files, and its own
 * undo/redo and rev. The protocol row keeps the crop, the count, the tags,
 * Anee's review and which version is in use.
 *
 * Rows are the acting user's own — every query is fenced on userId.
 */
class ProtocolBuilderController extends Controller
{
    /** How a protocol counts its days, and which counters its tasks may use. */
    public const DAY_TYPES = [
        'DAT' => ['label' => 'DAS → DAT', 'sub' => 'Sown in a seedbed, then transplanted — the count restarts at the transplant. Work before the program is counted back from the sowing.', 'counters' => ['DAS', 'DAT'], 'icon' => '🌾'],
        'DAS' => ['label' => 'DAS only', 'sub' => 'Direct seeded — one count from sowing to harvest.', 'counters' => ['DAS'], 'icon' => '🌱'],
        'DAP' => ['label' => 'DAP', 'sub' => 'Planted from seedlings, cuttings, tubers or setts — days after planting.', 'counters' => ['DAP'], 'icon' => '🪴'],
        // A standing orchard has no sowing and no planting this season. The
        // board counts an orchard lot's days from the day its program starts —
        // DOS, the Day of Start (activities-js activityRefCounter) — while the
        // growth stages are read against the trees' age. The protocol pins
        // its tasks to that same DOS count.
        'TREE' => ['label' => 'Mature trees', 'sub' => 'Standing trees, read by their age. Tasks are pinned to days from the program’s start (DOS) — the count the board keeps for an orchard lot.', 'counters' => ['DOS'], 'icon' => '🌳'],
    ];

    /** The colours a phase divider may wear. */
    public const DIVIDER_COLORS = ['amber', 'green', 'sky', 'violet', 'rose', 'slate'];

    /** What an item on a task's list can be. */
    public const KINDS = [
        'herbicide' => ['label' => 'Herbicide', 'icon' => '🌿'],
        'insecticide' => ['label' => 'Insecticide', 'icon' => '🐛'],
        'fungicide' => ['label' => 'Fungicide', 'icon' => '🍄'],
        'molluscicide' => ['label' => 'Molluscicide', 'icon' => '🐌'],
        'rodenticide' => ['label' => 'Rodenticide', 'icon' => '🐀'],
        'fertilizer' => ['label' => 'Fertilizer (granular)', 'icon' => '🧂'],
        'foliar' => ['label' => 'Foliar fertilizer', 'icon' => '💧'],
        'growth' => ['label' => 'Growth regulator / hormone', 'icon' => '📈'],
        'adjuvant' => ['label' => 'Sticker / spreader / adjuvant', 'icon' => '🧴'],
        'bio' => ['label' => 'Biological / organic', 'icon' => '🦠'],
        'seed' => ['label' => 'Seed / planting material', 'icon' => '🌰'],
        'water' => ['label' => 'Water', 'icon' => '🚰'],
        'tool' => ['label' => 'Tool / equipment', 'icon' => '🔧'],
        'other' => ['label' => 'Other', 'icon' => '📦'],
    ];

    public const PRIORITIES = [
        'critical' => ['label' => 'Critical', 'sub' => 'Cannot slip. The season turns on it.'],
        'high' => ['label' => 'High', 'sub' => 'Do it on the day if at all possible.'],
        'medium' => ['label' => 'Medium', 'sub' => 'The ordinary run of work.'],
        'low' => ['label' => 'Low', 'sub' => 'When there is time.'],
    ];

    /** The units a material is counted in; the sheet also takes one typed in. */
    public const UNITS = ['kg', 'g', 'L', 'mL', 'bag', 'sack', 'bottle', 'pack', 'sachet', 'can', 'piece', 'roll'];

    private const MAX_TASKS = 300;
    private const MAX_MATERIALS = 100;
    private const MAX_VERSIONS = 12;
    private const MAX_FILES = 20;
    private const MAX_HISTORY = 15;
    private const MAX_HISTORY_BYTES = 2000000;

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    /* ------------------------------------------------------------ pages */

    public function page()
    {
        return view('protocol-builder.index', ['options' => $this->options()]);
    }

    public function open(int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            abort(404);
        }

        return view('protocol-builder.edit', [
            'protocol' => $this->shape($p, true),
            'options' => $this->options(),
            'stages' => $this->stagesFor($p->crop),
        ]);
    }

    /* ------------------------------------------------------------ the shelf */

    public function list()
    {
        $rows = AsProtocol::active()->where('userId', (int) Auth::id())
            ->orderByDesc('updated_at')->limit(300)->get();
        // Every version of every listed protocol in one query — the port
        // sheet asks which version a lot runs.
        $versions = $rows->isEmpty() ? collect() : AsProtocolVersion::active()->where('userId', (int) Auth::id())
            ->whereIn('protocolId', $rows->pluck('id')->all())
            ->orderBy('sortOrder')->orderBy('id')->get()->groupBy('protocolId');

        return $this->json(true, '', ['rows' => $rows->map(fn ($p) => $this->shape($p, false, $versions->get($p->id)))->values()->all()]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:190',
            'description' => 'nullable|string|max:2000',
            'crop' => 'nullable|string|max:60',
            'variety' => 'nullable|string|max:120',
            'dayType' => 'required|in:DAS,DAT,DAP,TREE',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:30',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        $crop = CropStages::normalize($request->input('crop'));
        $dayType = self::fitDayType($crop, (string) $request->input('dayType'));
        $p = AsProtocol::create([
            'userId' => (int) Auth::id(),
            'title' => trim((string) $request->input('title')),
            'description' => $this->text($request->input('description'), 2000),
            'tags' => UserTagController::tidy($request->input('tags', [])),
            'crop' => $crop,
            'variety' => $this->text($request->input('variety'), 120),
            'dayType' => $dayType,
            'tasks' => [],
            'history' => ['undo' => [], 'redo' => []],
            'rev' => 1,
            'deleteStatus' => 1,
        ]);
        $ver = AsProtocolVersion::create($this->blankVersion($p, 'Version 1'));
        $p->forceFill(['versionId' => $ver->id])->save();

        return $this->json(true, 'Protocol started.', ['id' => $p->id, 'url' => route('pb.open', ['id' => $p->id])]);
    }

    /**
     * The autosave of one version: the whole task list, the materials, the
     * history beside them, and the rev the page holds for that version.
     */
    public function save(Request $request, int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $v = Validator::make($request->all(), [
            'versionId' => 'nullable|integer',
            'rev' => 'required|integer|min:1',
            'tasks' => 'present|array|max:' . self::MAX_TASKS,
            'materials' => 'nullable|array|max:' . self::MAX_MATERIALS,
            'history' => 'nullable|array',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        // A tab from before versions came sends none: it writes the one in use.
        $ver = $request->filled('versionId') ? $this->versionOf($p, (int) $request->input('versionId')) : $this->current($p);
        if (! $ver) {
            return $this->json(false, 'That version is gone — it was deleted somewhere else. Reload to keep working.', ['stale' => true], 409);
        }
        if ((int) $request->input('rev') !== (int) $ver->rev) {
            return $this->json(false, 'This protocol was changed somewhere else. Reload to keep working on the latest.', ['stale' => true, 'rev' => (int) $ver->rev], 409);
        }
        $materials = $request->has('materials')
            ? $this->cleanMaterials((array) $request->input('materials', []))
            : $this->cleanMaterials((array) ($ver->materials ?? []));
        $tasks = $this->cleanTasks((array) $request->input('tasks', []), $p->dayType, $materials);
        $history = $this->cleanHistory((array) $request->input('history', []));

        $ver->forceFill([
            'tasks' => $tasks,
            'materials' => $materials,
            'history' => $history,
            'rev' => (int) $ver->rev + 1,
        ])->save();

        return $this->json(true, 'Saved.', ['rev' => (int) $ver->rev, 'savedAt' => now()->format('H:i'), 'count' => count($tasks)]);
    }

    /** Title, description, crop, variety, day count — the head of the protocol. */
    public function meta(Request $request, int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:190',
            'description' => 'nullable|string|max:2000',
            'crop' => 'nullable|string|max:60',
            'variety' => 'nullable|string|max:120',
            'dayType' => 'required|in:DAS,DAT,DAP,TREE',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:30',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        $crop = CropStages::normalize($request->input('crop'));
        // The count follows the crop the way the Lots form narrows it; an
        // answer already on the row survives a list that narrowed around it.
        $dayType = self::fitDayType($crop, (string) $request->input('dayType'), $p->dayType);
        if ($dayType !== $p->dayType) {
            // Every version's tasks keep their days; only the word changes
            // when the new count does not know their counter.
            $allowed = self::DAY_TYPES[$dayType]['counters'];
            foreach ($this->versions($p) as $ver) {
                $tasks = (array) ($ver->tasks ?? []);
                foreach ($tasks as &$t) {
                    if (is_array($t) && ! in_array($t['counter'] ?? '', $allowed, true)) {
                        $t['counter'] = $allowed[0];
                    }
                }
                unset($t);
                $ver->forceFill([
                    'tasks' => $this->cleanTasks($tasks, $dayType, (array) ($ver->materials ?? [])),
                    'rev' => (int) $ver->rev + 1,
                ])->save();
            }
        }
        $p->forceFill([
            'title' => trim((string) $request->input('title')),
            'description' => $this->text($request->input('description'), 2000),
            'tags' => UserTagController::tidy($request->input('tags', [])),
            'crop' => $crop,
            'variety' => $this->text($request->input('variety'), 120),
            'dayType' => $dayType,
        ])->save();
        $p = $p->fresh();

        return $this->json(true, 'Saved.', ['protocol' => $this->shape($p, true), 'stages' => $this->stagesFor($p->crop)]);
    }

    public function duplicate(int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $copy = AsProtocol::create([
            'userId' => (int) Auth::id(),
            'title' => mb_substr($p->title . ' (copy)', 0, 190),
            'description' => $p->description,
            'tags' => $p->tags ?? [],
            'crop' => $p->crop,
            'variety' => $p->variety,
            'dayType' => $p->dayType,
            'tasks' => [],
            'history' => ['undo' => [], 'redo' => []],
            'rev' => 1,
            'deleteStatus' => 1,
        ]);
        // Every version comes along — tasks, materials, rules, files — each
        // with a fresh history of its own; the copy runs the same one.
        $inUse = null;
        foreach ($this->versions($p) as $ver) {
            $made = AsProtocolVersion::create($this->copyOf($ver, $copy, $ver->name, (int) $ver->sortOrder));
            if ($inUse === null || (int) $ver->id === (int) $p->versionId) {
                $inUse = $made;
            }
        }
        $copy->forceFill(['versionId' => $inUse ? $inUse->id : null])->save();

        return $this->json(true, 'Copied.', ['id' => $copy->id, 'row' => $this->shape($copy, false)]);
    }

    /* ------------------------------------------------------------ versions */

    /** Switch the protocol to another of its versions; the page gets that version's whole content. */
    public function versionUse(int $id, int $vid)
    {
        $p = $this->mine($id);
        $ver = $p ? $this->versionOf($p, $vid) : null;
        if (! $ver) {
            return $this->json(false, 'That version is not yours.', [], 404);
        }
        $p->forceFill(['versionId' => $ver->id])->save();

        return $this->json(true, 'Now on ' . $ver->name . '.', [
            'version' => $this->versionContent($ver, $p),
            'versions' => $this->versionRows($this->versions($p)),
        ]);
    }

    /** A new version: a copy of the one named (or the one in use), put in use at once. */
    public function versionStore(Request $request, int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:80',
            'from' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Name the version.', ['errors' => $v->errors()], 422);
        }
        $rows = $this->versions($p);
        if ($rows->count() >= self::MAX_VERSIONS) {
            return $this->json(false, 'A protocol keeps up to ' . self::MAX_VERSIONS . ' versions. Delete one you no longer use first.', [], 422);
        }
        $from = $request->filled('from') ? $rows->firstWhere('id', (int) $request->input('from')) : null;
        $from = $from ?: $this->current($p, $rows);
        $ver = AsProtocolVersion::create($this->copyOf($from, $p, trim((string) $request->input('name')), (int) $rows->max('sortOrder') + 1));
        $p->forceFill(['versionId' => $ver->id])->save();

        return $this->json(true, 'Made "' . $ver->name . '" — a copy of "' . $from->name . '". You are on it now.', [
            'version' => $this->versionContent($ver, $p),
            'versions' => $this->versionRows($this->versions($p)),
        ]);
    }

    public function versionRename(Request $request, int $id, int $vid)
    {
        $p = $this->mine($id);
        $ver = $p ? $this->versionOf($p, $vid) : null;
        if (! $ver) {
            return $this->json(false, 'That version is not yours.', [], 404);
        }
        $name = $this->text($request->input('name'), 80);
        if ($name === null) {
            return $this->json(false, 'Name the version.', [], 422);
        }
        $ver->forceFill(['name' => $name])->save();

        return $this->json(true, 'Renamed.', ['versions' => $this->versionRows($this->versions($p))]);
    }

    /** Remove a version — never the last. Deleting the one in use moves the protocol to another. */
    public function versionDestroy(int $id, int $vid)
    {
        $p = $this->mine($id);
        $ver = $p ? $this->versionOf($p, $vid) : null;
        if (! $ver) {
            return $this->json(false, 'That version is not yours.', [], 404);
        }
        $rows = $this->versions($p);
        if ($rows->count() <= 1) {
            return $this->json(false, 'A protocol keeps at least one version.', [], 422);
        }
        $ver->forceFill(['deleteStatus' => 0])->save();
        foreach ((array) ($ver->files ?? []) as $f) {
            if (! empty($f['path']) && ! $this->fileShared((string) $f['path'], (int) $ver->id)) {
                MediaStore::delete((string) $f['path']);
            }
        }
        $left = $rows->reject(fn ($x) => (int) $x->id === (int) $ver->id)->values();
        $switched = null;
        if ((int) $p->versionId === (int) $ver->id) {
            $switched = $left->first();
            $p->forceFill(['versionId' => $switched->id])->save();
        }

        return $this->json(true, 'Deleted "' . $ver->name . '".', [
            'versions' => $this->versionRows($left),
            'version' => $switched ? $this->versionContent($switched, $p) : null,
        ]);
    }

    /* ------------------------------------------------------------ rules & notes, and files */

    /**
     * The rules-and-notes document of a version. Written in the rich editor
     * and saved on its own, beside the tasks' rev: the editor keeps its own
     * undo, and the tasks' stale-tab guard must not turn a paragraph away.
     */
    public function rules(Request $request, int $id, int $vid)
    {
        $p = $this->mine($id);
        $ver = $p ? $this->versionOf($p, $vid) : null;
        if (! $ver) {
            return $this->json(false, 'That version is not yours.', [], 404);
        }
        $v = Validator::make($request->all(), ['rules' => 'nullable|string|max:300000']);
        if ($v->fails()) {
            return $this->json(false, 'The document is too long to keep in one piece.', ['errors' => $v->errors()], 422);
        }
        $html = HtmlSanitizer::rich((string) $request->input('rules', ''));
        if (trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0") === '') {
            $html = null;
        }
        $ver->forceFill(['rules' => $html])->save();

        return $this->json(true, 'Saved.', ['savedAt' => now('Asia/Manila')->format('g:i A')]);
    }

    /** A file beside the rules: a label, a leaflet, a soil test. Libre + Anee and above. */
    public function fileStore(Request $request, int $id, int $vid)
    {
        if (! self::canUpload()) {
            Tier::deny('Files beside your protocol — labels, leaflets, soil tests — come with Libre + Anee and every plan above it. Writing the rules and notes stays free.', 'libreAnee');
        }
        $p = $this->mine($id);
        $ver = $p ? $this->versionOf($p, $vid) : null;
        if (! $ver) {
            return $this->json(false, 'That version is not yours.', [], 404);
        }
        $v = Validator::make($request->all(), [
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,txt,csv,xls,xlsx,ppt,pptx',
        ], [
            'file.mimes' => 'Pictures, PDF, Word, Excel, PowerPoint or text files only.',
            'file.max' => 'That file is over 10 MB.',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), ['errors' => $v->errors()], 422);
        }
        if (count((array) ($ver->files ?? [])) >= self::MAX_FILES) {
            return $this->json(false, 'A version keeps up to ' . self::MAX_FILES . ' files. Delete one first.', [], 422);
        }
        $file = $request->file('file');
        $path = MediaStore::putFile($file, 'protocol-files', (int) Auth::id());
        if (! $path) {
            return $this->json(false, 'The file could not be kept. Please try again.', [], 500);
        }
        // The media store renames a kind it does not keep (a PDF came back as
        // .png before the mother app learned documents) — a file that would
        // not open is given back rather than listed.
        $norm = fn ($e) => $e === 'jpeg' ? 'jpg' : $e;
        $sent = $norm(strtolower((string) $file->getClientOriginalExtension()));
        $kept = $norm(strtolower(pathinfo(MediaStore::strip($path), PATHINFO_EXTENSION)));
        if ($sent !== '' && $kept !== '' && $sent !== $kept) {
            MediaStore::delete($path);

            return $this->json(false, 'The file store cannot keep .' . $sent . ' files yet — pictures work now, and documents will once the server is updated.', ['kept' => $kept], 422);
        }
        $entry = [
            'id' => $this->key(null),
            'name' => mb_substr(trim((string) $file->getClientOriginalName()) ?: 'File', 0, 160),
            'path' => $path,
            'size' => (int) $file->getSize(),
            'mime' => mb_substr((string) $file->getClientMimeType(), 0, 100),
            'at' => now('Asia/Manila')->format('M j, Y'),
        ];
        $ver = $ver->fresh();   // the upload took a while; add to the list as it is now
        $files = array_values((array) ($ver->files ?? []));
        $files[] = $entry;
        $ver->forceFill(['files' => $files])->save();

        return $this->json(true, 'Added "' . $entry['name'] . '".', ['file' => $this->fileShape($entry)]);
    }

    public function fileDestroy(int $id, int $vid, string $fid)
    {
        $p = $this->mine($id);
        $ver = $p ? $this->versionOf($p, $vid) : null;
        if (! $ver) {
            return $this->json(false, 'That version is not yours.', [], 404);
        }
        $files = array_values((array) ($ver->files ?? []));
        $gone = null;
        $keep = [];
        foreach ($files as $f) {
            if ($gone === null && (string) ($f['id'] ?? '') === $fid) {
                $gone = $f;
                continue;
            }
            $keep[] = $f;
        }
        if (! $gone) {
            return $this->json(false, 'That file is already gone.', [], 404);
        }
        $ver->forceFill(['files' => $keep])->save();
        // A copied version or protocol points at the same stored file; it is
        // only let go once nothing points at it.
        if (! empty($gone['path']) && ! $this->fileShared((string) $gone['path'], (int) $ver->id)) {
            MediaStore::delete((string) $gone['path']);
        }

        return $this->json(true, 'Removed "' . ($gone['name'] ?? 'the file') . '".');
    }

    public function destroy(int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $p->forceFill(['deleteStatus' => 0])->save();

        return $this->json(true, 'Protocol removed.');
    }

    /* ------------------------------------------------------------ Anee reads it */

    private function guardTier(): void
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::deny('Anee\'s review of a protocol comes with Libre + Anee, and with every plan above it.', 'libreAnee');
        }
    }

    public function analyze(int $id)
    {
        $this->guardTier();
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'Anee is not available on this plan.', [], 403);
        }
        // Anee reads the version in use.
        $ver = $this->current($p);
        $materials = $this->cleanMaterials((array) ($ver->materials ?? []));
        $tasks = $this->cleanTasks((array) ($ver->tasks ?? []), $p->dayType, $materials);
        if (! count(array_filter($tasks, [self::class, 'isTask']))) {
            return $this->json(false, 'Add at least one task before asking Anee to read the protocol.', [], 422);
        }
        $price = AiPrices::of('builder');
        $balance = $this->credits->balance($payer->id);
        if ($balance < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this review and have ' . number_format((int) floor($balance)) . '.', ['outOfCredits' => true], 402);
        }
        if ($p->analysisStatus === 'pending' && ! $this->dead($p)) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $p->id]);
        }

        $p->forceFill([
            'analysisStatus' => 'pending',
            'analysis' => ['phase' => 'start', 'try' => 1],
            'analysisError' => null,
            'analysisBeatAt' => now(),
        ])->save();
        $prompt = $this->prompt($p, $tasks, $materials, $ver->name, $this->versions($p)->count() > 1);

        register_shutdown_function(function () use ($id) {
            try {
                AsProtocol::where('id', $id)->where('analysisStatus', 'pending')->update([
                    'analysisStatus' => 'failed',
                    'analysisError' => 'The review took too long and was stopped. Nothing was charged — please try again.',
                ]);
            } catch (\Throwable $e) {
            }
        });

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $p->id]])->send();
            fastcgi_finish_request();
            $this->runJob($p->id, (int) $payer->id, $settings, $prompt);
            exit;
        }
        @set_time_limit(900);
        $this->runJob($p->id, (int) $payer->id, $settings, $prompt);

        return $this->job($p->id);
    }

    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            AsProtocol::where('id', $id)->where('analysisStatus', 'pending')->update([
                'analysis' => json_encode(['phase' => $phase, 'try' => $try]),
                'analysisBeatAt' => now(),
            ]);
        };
        try {
            $result = $this->ai->askForJson($settings, $prompt, 7000, fn (string $t) => $this->parseReview($t), ['onPhase' => $beat]);
            $review = $result['data'];
            if ($review === null) {
                \Log::warning('protocol-builder: unparsable review', ['head' => mb_substr((string) ($result['text'] ?? ''), 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The review came back unreadable. Nothing was charged — please try again.');
            }
            $row = AsProtocol::find($id);
            $charged = (float) AiPrices::of('builder');
            $note = AiUsage::record('builder', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged, mb_substr('Protocol Builder review — ' . $row->title . $note, 0, 250));
            $row->forceFill([
                'analysis' => $review,
                'analysisStatus' => 'ready',
                'analysisError' => null,
                'analysisCredits' => round($charged, 2),
                'analysisAt' => now(),
                'analysisBeatAt' => now(),
            ])->save();
        } catch (\Throwable $e) {
            report($e);
            AsProtocol::where('id', $id)->update([
                'analysisStatus' => 'failed',
                'analysisError' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }
    }

    private function dead(AsProtocol $p): bool
    {
        $beatAt = $p->analysisBeatAt ? Carbon::parse($p->analysisBeatAt) : Carbon::parse($p->updated_at);

        return $beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60));
    }

    public function job(int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        if ($p->analysisStatus === 'pending') {
            if ($this->dead($p)) {
                $p->forceFill(['analysisStatus' => 'failed', 'analysisError' => 'The review was interrupted mid-way. Nothing was charged — please try again.'])->save();

                return $this->json(false, $p->analysisError, ['status' => 'failed'], 502);
            }
            $beat = is_array($p->analysis) ? $p->analysis : [];

            return $this->json(true, 'Working…', [
                'pending' => true, 'id' => $p->id, 'status' => 'pending',
                'phase' => $beat['phase'] ?? 'start', 'try' => (int) ($beat['try'] ?? 1),
                'beatAgo' => $p->analysisBeatAt ? now()->diffInSeconds(Carbon::parse($p->analysisBeatAt)) : null,
            ]);
        }
        if ($p->analysisStatus === 'failed') {
            return $this->json(false, $p->analysisError ?: 'The review failed. Nothing was charged.', ['status' => 'failed'], 502);
        }
        if ($p->analysisStatus === 'ready') {
            $payer = $this->payer();

            return $this->json(true, 'Ready.', [
                'status' => 'ready',
                'analysis' => $p->analysis,
                'analysisAt' => $p->analysisAt ? Carbon::parse($p->analysisAt)->format('M j, Y · g:i A') : null,
                'charged' => (float) $p->analysisCredits,
                'balance' => round($this->credits->balance($payer->id), 2),
            ]);
        }

        return $this->json(false, 'No review yet.', ['status' => 'none'], 404);
    }

    /** What Anee is asked, in the document voice, with the app's own stage table beside the farmer's plan. */
    private function prompt(AsProtocol $p, array $tasks, array $materials = [], string $versionName = '', bool $manyVersions = false): string
    {
        $matById = [];
        foreach ($materials as $m) {
            $matById[$m['id']] = $m;
        }
        $cropLabel = $p->crop ? (CropStages::label($p->crop) ?: $p->crop) : 'an unnamed crop';
        $dt = self::DAY_TYPES[$p->dayType] ?? self::DAY_TYPES['DAS'];
        $region = \App\Support\Region::name();
        $lines = [];
        $n = 0;
        foreach ($tasks as $t) {
            if (($t['kind'] ?? '') === 'divider') {
                // A phase heading the farmer drew between the tasks — not a task.
                $lines[] = '=== PHASE: ' . $t['label'] . ' (from ' . self::sayWhen($t['counter'], $t['day']) . ') ===';
                continue;
            }
            if (($t['kind'] ?? '') === 'note') {
                $lines[] = '   NOTE (the farmer\'s own words, placed here): ' . preg_replace('/\s+/', ' ', $t['text']);
                continue;
            }
            $i = $n++;
            $when = self::sayWhen($t['counter'], $t['day']);
            $head = sprintf('%d. [%s] %s — %s', $i + 1, $t['id'], $when, $t['title']);
            if ($t['subtitle'] !== '') {
                $head .= ' (' . $t['subtitle'] . ')';
            }
            $head .= ' · type: ' . (AsScheduleActivity::ACTIVITY_TYPES[$t['type']] ?? 'not stated')
                . ' · importance: ' . $t['priority']
                . ($t['workers'] !== null ? ' · workers needed: ' . $t['workers'] : '');
            $lines[] = $head;
            if ($t['description'] !== '') {
                $lines[] = '   Description: ' . preg_replace('/\s+/', ' ', $t['description']);
            }
            foreach ($t['groups'] as $g) {
                $loads = $g['perKnapsack'] && ! empty($g['loads']) ? ', ' . self::num((float) $g['loads']) . ' loads' : '';
                $lines[] = '   Apply' . ($g['title'] !== '' ? ' — ' . $g['title'] : '') . ($g['perKnapsack'] ? ' (per knapsack' . $loads . ')' : '') . ':';
                foreach ($g['items'] as $it) {
                    $use = self::itemUse($g, $it);
                    $unit = isset($it['materialId'], $matById[$it['materialId']]) ? $matById[$it['materialId']]['unit'] : '';
                    $lines[] = '     - ' . $it['name'] . ' [' . (self::KINDS[$it['kind']]['label'] ?? $it['kind']) . ']' . ($it['amount'] !== '' ? ' · ' . $it['amount'] : '')
                        . ($use !== null && $g['perKnapsack'] ? ' (' . self::num($use) . ' ' . $unit . ' in all)' : '');
                }
            }
            if ($t['note'] !== '') {
                $lines[] = '   Note: ' . preg_replace('/\s+/', ' ', $t['note']);
            }
        }
        $use = self::materialUse($tasks);
        $matLines = [];
        foreach ($materials as $m) {
            $used = $use[$m['id']] ?? 0.0;
            $matLines[] = '- ' . $m['name'] . ' [' . (self::KINDS[$m['kind']]['label'] ?? $m['kind']) . ']'
                . ($m['qty'] !== null ? ' · on hand ' . self::num($m['qty']) . ' ' . $m['unit'] : '')
                . ' · the tasks plan ' . self::num($used) . ' ' . $m['unit']
                . ($m['qty'] !== null && $used > $m['qty'] + 1e-9 ? ' — SHORT by ' . self::num($used - $m['qty']) . ' ' . $m['unit'] : '')
                . ($m['note'] !== '' ? ' · ' . preg_replace('/\s+/', ' ', $m['note']) : '');
        }
        $materialText = $this->joined($matLines, 'No list of materials.');
        $versionLine = $manyVersions && $versionName !== '' ? "
Version: {$versionName} (one of several versions of this protocol)" : '';
        $stages = [];
        foreach ($this->stagesFor($p->crop) as $counter => $rows) {
            if (! $rows) {
                continue;
            }
            $stages[] = $counter === 'AGE'
                ? 'By the trees\' age: ' . implode('; ', array_map(fn ($r) => $r[1] . ' from month ' . $r[0], $rows))
                : $counter . ': ' . implode('; ', array_map(fn ($r) => $r[1] . ' from ' . $counter . ' ' . $r[0], $rows));
        }
        $isTree = $p->dayType === 'TREE';
        $countWords = $isTree
            ? 'these are standing, mature trees: DOS N means N days after the day this program starts on them (DOS 0), not the trees\' age — the growth stage is read from the trees\' age, which the protocol does not fix; "7 days before DOS 0" is work done before the program starts'
            : '"14 days before DAS 0" is work done before the count starts, such as land preparation';
        $counterList = implode('|', $dt['counters']);
        $beforeExample = $isTree ? 'e.g. -7 to buy the inputs a week before the program starts' : 'e.g. -14 for land preparation two weeks before';
        $treeTiming = $isTree ? ' For standing trees, judge the rhythm through the year (flushing, flowering, fruit set, harvest, after-harvest care) rather than a seasonal calendar.' : '';
        $stageHead = $isTree ? 'the month of the trees\' age each stage begins' : 'day the stage begins';
        $typeKeys = implode(', ', array_keys(AsScheduleActivity::ACTIVITY_TYPES));
        $taskText = $this->joined($lines);
        $stageText = $this->joined($stages, 'No table for this crop.');
        $variety = $this->orNot($p->variety);
        $description = $this->orNot($p->description);

        return <<<PROMPT
You are reviewing a crop protocol a farmer wrote for themselves in the Protocol Builder. Read it as an experienced agronomist in {$region} would, and judge it honestly: is it complete, is the timing right, is anything missing, is anything risky or wasteful.

THE PROTOCOL
Title: {$p->title}
Crop: {$cropLabel}
Variety: {$variety}
Day count: {$dt['label']} — {$dt['sub']}
Description: {$description}{$versionLine}

THE TASKS, in the farmer's order (the code in [brackets] is the task's id — quote it exactly in "tasks"; lines marked === PHASE === are the farmer's own phase headings, not tasks — read the tasks under each as that phase's work; {$countWords})
{$taskText}

THE FARMER'S MATERIALS (what is on hand for the whole protocol, and what the tasks draw from it)
{$materialText}

THE APP'S OWN GROWTH STAGE TABLE for this crop ({$stageHead})
{$stageText}

WHAT TO JUDGE
- Coverage: land preparation, planting/transplanting, water, nutrition by stage, weed / pest / disease control, monitoring, harvest — what is there and what is missing for this crop.
- Timing: is each task on a sensible day of the count for its stage? Point out anything too early, too late, or out of order.{$treeTiming}
- Products and rates: are the items sensible for the stated purpose? Flag a wrong product for the job, a tank mix that must not be made (e.g. copper with a herbicide), and any obvious over- or under-application. Do not invent brand prices.
- Safety and sequence: pre-harvest intervals, re-entry, and anything that endangers the crop or the worker.
- Be concrete and specific to THIS crop and count; never generic praise. When something is fine, say so briefly. Keep every string short and plain — this is a report, not a chat.

RETURN ONLY A JSON OBJECT of exactly this shape (no fences, no commentary):
{
  "score": <integer 0-100, how complete and sound the protocol is>,
  "verdict": "<3-6 word verdict, e.g. 'Solid, thin on weed control'>",
  "headline": "<one sentence on the protocol as a whole>",
  "strengths": [{"point": "<what is good>", "why": "<one sentence>"}],
  "gaps": [{"what": "<what is missing or weak>", "why": "<why it matters>", "fix": "<what to add or change>"}],
  "risks": [{"risk": "<what could go wrong>", "when": "<the day or stage, e.g. 'DAS 20-35'>", "action": "<what to do about it>"}],
  "tasks": [{"id": "<task id from the brackets>", "verdict": "good|check|concern", "note": "<one short sentence about this task>"}],
  "additions": [{"counter": "<{$counterList}>", "day": <integer; a NEGATIVE number means that many days BEFORE that counter's day 0, {$beforeExample}>, "title": "<task to add>", "type": "<one of: {$typeKeys}>", "why": "<one sentence>"}],
  "sequence": "<one short paragraph on the order and spacing of the tasks>",
  "summary": "<2-4 sentences the farmer can act on>"
}
Give one "tasks" entry for EVERY task listed. Give 2-6 strengths, 0-8 gaps, 0-6 risks and 0-8 additions. Keep "score" honest: a two-task protocol cannot score high.
PROMPT;
    }

    private function parseReview(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $from = strpos($text, '{');
        $to = strrpos($text, '}');
        if ($from === false || $to === false || $to <= $from) {
            return null;
        }
        $json = json_decode(substr($text, $from, $to - $from + 1), true);
        if (! is_array($json) || ! isset($json['score'], $json['summary']) || ! is_array($json['tasks'] ?? null)) {
            return null;
        }
        $sweep = fn ($v) => is_string($v) ? trim(preg_replace('/:[a-z0-9_-]+:/i', '', $v)) : $v;
        array_walk_recursive($json, function (&$v) use ($sweep) {
            $v = $sweep($v);
        });
        $json['score'] = max(0, min(100, (int) $json['score']));
        foreach (['strengths', 'gaps', 'risks', 'tasks', 'additions'] as $k) {
            $json[$k] = array_values(array_filter((array) ($json[$k] ?? []), 'is_array'));
        }
        foreach ($json['additions'] as &$a) {
            $a['day'] = (int) ($a['day'] ?? 0);
            $a['counter'] = strtoupper((string) ($a['counter'] ?? 'DAS'));
            if (! isset(AsScheduleActivity::ACTIVITY_TYPES[$a['type'] ?? ''])) {
                $a['type'] = 'other';
            }
        }
        unset($a);

        return $json;
    }

    /* ------------------------------------------------------------ into a season */

    /** The member's lots across their seasons — the port sheet's "from an existing lot" picker. */
    public function lots()
    {
        $rows = DB::table('as_schedule_lots as l')
            ->join('as_cropping_schedules as s', 's.id', '=', 'l.croppingScheduleId')
            ->where('s.anisystemUserId', (int) Auth::id())->where('s.deleteStatus', 1)->where('l.deleteStatus', 1)
            ->orderByDesc('s.id')->orderBy('l.id')->limit(300)
            ->get(['l.id', 'l.lotName', 'l.lotSize', 'l.lotSizeUnit', 'l.crop', 'l.variety', 'l.dayType', 'l.treePlantedAt', 's.title as scheduleTitle', 's.id as scheduleId']);

        return $this->json(true, '', ['lots' => $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'name' => $r->lotName,
            'size' => (float) $r->lotSize,
            'unit' => $r->lotSizeUnit ?: 'hectare',
            'crop' => $r->crop,
            'cropLabel' => $r->crop ? (CropStages::label($r->crop) ?: $r->crop) : null,
            'cropIcon' => $r->crop ? CropStages::icon($r->crop) : '🌱',
            'variety' => $r->variety,
            'dayType' => $r->dayType,
            'treePlantedAt' => $r->treePlantedAt ? Carbon::parse($r->treePlantedAt)->format('Y-m-d') : null,
            'schedule' => $r->scheduleTitle,
            'scheduleId' => (int) $r->scheduleId,
        ])->values()->all()]);
    }

    /**
     * PORT TO A CROPPING SCHEDULE — one new season, several lots, each lot on
     * its own protocol from its own start date. Every task becomes an
     * activity on its computed date and every note a day-book note. With
     * "spread", no day holds more activities than there are hands: the
     * overflow slides to the next day, in order, while day-zero and
     * transplant work stand where they are.
     */
    public function port(Request $request)
    {
        $user = $request->user();
        if (! $user->canCreateSchedule()) {
            $limit = $user->scheduleLimit();
            Tier::deny('Your plan allows ' . ($limit === 0 ? 'no' : 'up to ' . $limit) . ' active ' . ($limit === 1 ? 'season' : 'seasons') . '. Finish or archive one, or upgrade for more.', $this->portRung());
        }
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'workers' => 'nullable|integer|min:1|max:200',
            'adjust' => 'nullable|in:spread,allow',
            'lots' => 'required|array|min:1|max:20',
            'lots.*.name' => 'required|string|max:255',
            'lots.*.size' => 'nullable|numeric|min:0|max:99999',
            'lots.*.unit' => 'nullable|in:hectare,sqm,acre',
            'lots.*.sourceLotId' => 'nullable|integer',
            'lots.*.protocolId' => 'required|integer',
            'lots.*.versionId' => 'nullable|integer',
            'lots.*.startDate' => 'required|date',
            'lots.*.transplantDate' => 'nullable|date',
            'lots.*.treePlantedAt' => 'nullable|date|before_or_equal:today',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        $lotsIn = array_values((array) $request->input('lots'));
        $cap = Tier::limit('lotsPerSchedule');
        if ($cap !== null && count($lotsIn) > (int) $cap) {
            Tier::deny('Your plan allows ' . (int) $cap . ' ' . ((int) $cap === 1 ? 'lot' : 'lots') . ' in a season.', $this->portRung());
        }
        $protocols = AsProtocol::active()->where('userId', (int) Auth::id())
            ->whereIn('id', array_map(fn ($l) => (int) $l['protocolId'], $lotsIn))->get()->keyBy('id');
        // The versions of those protocols, fenced to the member like the protocols.
        $versionsBy = $protocols->isEmpty() ? collect() : AsProtocolVersion::active()->where('userId', (int) Auth::id())
            ->whereIn('protocolId', $protocols->keys()->all())->orderBy('sortOrder')->orderBy('id')->get()->groupBy('protocolId');
        $sources = DB::table('as_schedule_lots as l')->join('as_cropping_schedules as s', 's.id', '=', 'l.croppingScheduleId')
            ->where('s.anisystemUserId', (int) Auth::id())
            ->whereIn('l.id', array_values(array_filter(array_map(fn ($l) => (int) ($l['sourceLotId'] ?? 0), $lotsIn))))
            ->get(['l.id', 'l.lotSize', 'l.lotSizeUnit', 'l.crop', 'l.variety', 'l.locBarangay', 'l.locZone', 'l.locTown', 'l.locProvince', 'l.daysToMaturity', 'l.treePlantedAt', 'l.dayType'])->keyBy('id');
        $plan = [];
        foreach ($lotsIn as $n => $l) {
            $proto = $protocols->get((int) $l['protocolId']);
            if (! $proto) {
                return $this->json(false, 'Lot ' . ($n + 1) . ' points at a protocol that is not yours.', [], 422);
            }
            // Which version runs the lot: the one the sheet confirmed, or
            // the one in use when the protocol has only that.
            $rowsOf = $versionsBy->get($proto->id) ?? $this->versions($proto);
            if (! empty($l['versionId'])) {
                $ver = $rowsOf->firstWhere('id', (int) $l['versionId']);
                if (! $ver) {
                    return $this->json(false, 'Lot ' . ($n + 1) . ' points at a version that is not one of "' . $proto->title . '".', [], 422);
                }
            } else {
                $ver = $this->current($proto, $rowsOf);
            }
            $materials = $this->cleanMaterials((array) ($ver->materials ?? []));
            $tasks = $this->cleanTasks((array) ($ver->tasks ?? []), $proto->dayType, $materials);
            if (! count(array_filter($tasks, [self::class, 'isTask']))) {
                return $this->json(false, '"' . $proto->title . '"' . ($rowsOf->count() > 1 ? ' (' . $ver->name . ')' : '') . ' has no tasks yet — nothing to port for ' . $l['name'] . '.', [], 422);
            }
            $start = Carbon::parse($l['startDate'])->startOfDay();
            $transplant = null;
            if ($proto->dayType === 'DAT') {
                if (empty($l['transplantDate'])) {
                    return $this->json(false, $l['name'] . ' runs a DAS → DAT protocol and needs a transplant date.', [], 422);
                }
                $transplant = Carbon::parse($l['transplantDate'])->startOfDay();
                if ($transplant->lt($start)) {
                    return $this->json(false, $l['name'] . ': the transplant date is before the sowing date.', [], 422);
                }
            }
            $source = $sources->get((int) ($l['sourceLotId'] ?? 0));
            // Mature trees: the lot is read by the trees' age, so it needs to
            // know when they were planted — from the lot it is taken from, or
            // from the age typed in the port sheet (turned into a date there,
            // the way the Lots form stamps it).
            $treePlanted = null;
            if ($proto->dayType === 'TREE') {
                $treePlanted = ($source && $source->treePlantedAt) ? $source->treePlantedAt : ($l['treePlantedAt'] ?? null);
                if (! $treePlanted) {
                    return $this->json(false, $l['name'] . ' runs a protocol for mature trees — say how old the trees are.', [], 422);
                }
                $treePlanted = Carbon::parse($treePlanted)->format('Y-m-d');
            }
            $plan[] = ['in' => $l, 'proto' => $proto, 'tasks' => $tasks, 'materials' => $materials, 'start' => $start, 'transplant' => $transplant, 'source' => $source, 'treePlanted' => $treePlanted];
        }
        $workers = max(1, (int) $request->input('workers', 1));
        $adjust = $request->input('adjust') === 'allow' ? 'allow' : 'spread';
        $dayTypes = array_unique(array_map(fn ($x) => $x['proto']->dayType, $plan));
        $dayTypes = array_values($dayTypes);
        // A season of one count says it; a mixed one takes the two-phase count
        // when any lot has it, else the first field count — an orchard among
        // field lots does not make the whole season a standing orchard.
        $fields = array_values(array_filter($dayTypes, fn ($d) => $d !== 'TREE'));
        $dayType = count($dayTypes) === 1 ? $dayTypes[0] : (in_array('DAT', $dayTypes, true) ? 'DAT' : ($fields[0] ?? $dayTypes[0]));
        $firstCrop = $plan[0]['source']->crop ?? $plan[0]['proto']->crop;

        $made = ['activities' => 0, 'notes' => 0, 'moved' => 0];
        $schedule = DB::transaction(function () use ($request, $plan, $dayType, $firstCrop, $workers, $adjust, &$made) {
            $schedule = AsCroppingSchedule::create([
                'anisystemUserId' => (int) Auth::id(),
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'title' => trim((string) $request->input('title')),
                'description' => $this->text($request->input('description'), 5000),
                'cropType' => $firstCrop ? (CropStages::label($firstCrop) ?: $firstCrop) : null,
                'cropVariety' => $plan[0]['source']->variety ?? $plan[0]['proto']->variety,
                'dayType' => $dayType,
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);
            $version = AsScheduleActivityVersion::create([
                'croppingScheduleId' => $schedule->id,
                'versionName' => 'Original',
                'isOriginal' => 1,
                'isActive' => 1,
                'versionOrder' => 0,
                'deleteStatus' => 1,
            ]);
            $all = [];
            foreach ($plan as $x) {
                $in = $x['in']; $proto = $x['proto']; $src = $x['source'];
                $isTree = $proto->dayType === 'TREE';
                $crop = $src->crop ?? $proto->crop;
                // A tree protocol on a lot that grew something else becomes the protocol's tree.
                if ($isTree && ! CropStages::isPerennial($crop) && $proto->crop) {
                    $crop = $proto->crop;
                }
                $lot = AsScheduleLot::create(array_filter([
                    'croppingScheduleId' => $schedule->id,
                    'lotName' => mb_substr(trim((string) $in['name']), 0, 255),
                    'lotSize' => isset($in['size']) && $in['size'] !== '' && $in['size'] !== null ? (float) $in['size'] : (float) ($src->lotSize ?? 1),
                    'lotSizeUnit' => $in['unit'] ?? ($src->lotSizeUnit ?? 'hectare'),
                    'variety' => $src->variety ?? $proto->variety,
                    'crop' => $crop,
                    'daysToMaturity' => $isTree ? null : ($src->daysToMaturity ?? null),
                    'treePlantedAt' => $isTree ? $x['treePlanted'] : null,
                    'locBarangay' => $src->locBarangay ?? null,
                    'locZone' => $src->locZone ?? null,
                    'locTown' => $src->locTown ?? null,
                    'locProvince' => $src->locProvince ?? null,
                    // TREE for an orchard lot, as LotController::cropTiming() sets it.
                    'dayType' => $proto->dayType,
                    'dayZeroDate' => $x['start']->format('Y-m-d'),
                    'transplantDate' => $x['transplant'] ? $x['transplant']->format('Y-m-d') : null,
                    'deleteStatus' => 1,
                ], fn ($v) => $v !== null));
                $planted = $this->plant($schedule, $version, $lot, $x['tasks'], $x['start'], $x['transplant'], $x['materials']);
                $made['activities'] += count($planted['activities']);
                $made['notes'] += $planted['notes'];
                $all = array_merge($all, $planted['activities']);
            }
            if ($adjust === 'spread') {
                $made['moved'] = $this->spread($all, $workers);
            }

            return $schedule;
        });
        foreach ($plan as $x) {
            $x['proto']->forceFill(['portedScheduleId' => $schedule->id, 'portedAt' => now()])->save();
        }
        $say = 'The season is set up — ' . count($plan) . ($plan && count($plan) === 1 ? ' lot, ' : ' lots, ') . $made['activities'] . ' activities on the board'
            . ($made['notes'] ? ' and ' . $made['notes'] . ($made['notes'] === 1 ? ' note' : ' notes') . ' on the day book' : '')
            . ($made['moved'] ? '; ' . $made['moved'] . ($made['moved'] === 1 ? ' activity slid' : ' activities slid') . ' to a later day so no day asks for more than ' . $workers . ($workers === 1 ? ' worker' : ' workers') : '') . '.';

        return $this->json(true, $say, [
            'scheduleId' => $schedule->id,
            'redirect' => route('sm.hub', ['id' => $schedule->id]),
            'made' => $made,
        ]);
    }

    /**
     * One protocol onto one lot: an activity per task on its computed date
     * (DAS/DAP from the start, DAT from the transplant), a day-book note per
     * note. Returns the activities as [{id, date, fixed}] for the spread.
     */
    private function plant(AsCroppingSchedule $schedule, AsScheduleActivityVersion $version, AsScheduleLot $lot, array $tasks, Carbon $start, ?Carbon $transplant, array $materials = []): array
    {
        $isDat = $lot->dayType === 'DAT';
        $matById = [];
        foreach ($materials as $m) {
            $matById[$m['id']] = $m;
        }
        $out = ['activities' => [], 'notes' => 0];
        $perDate = [];
        $anchored = [];   // the first day-0 task of each count is the anchor and stays put
        foreach ($tasks as $t) {
            $base = ($t['counter'] === 'DAT' && $transplant) ? $transplant : $start;
            $date = $base->copy()->addDays((int) $t['day'])->format('Y-m-d');
            if (($t['kind'] ?? '') === 'divider') {
                // The board's only dated, named marker is "Resume here" (one per
                // day, a progress bookmark), so a phase would be mislabelled
                // there. It lands as a day-book note headed with the phase.
                \App\Models\AsScheduleDateNote::create([
                    'croppingScheduleId' => $schedule->id,
                    'versionId' => $version->id,
                    'noteDate' => $date,
                    'noteContent' => HtmlSanitizer::rich('<p><strong>▸ ' . htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') . '</strong></p><p>This phase begins here (' . htmlspecialchars(self::sayWhen($t['counter'], (int) $t['day']), ENT_QUOTES, 'UTF-8') . ').</p>'),
                    'lotId' => $lot->id,
                    'deleteStatus' => 1,
                ]);
                $out['notes']++;
                continue;
            }
            if (($t['kind'] ?? '') === 'note') {
                \App\Models\AsScheduleDateNote::create([
                    'croppingScheduleId' => $schedule->id,
                    'versionId' => $version->id,
                    'noteDate' => $date,
                    'noteContent' => HtmlSanitizer::rich('<p>' . nl2br(htmlspecialchars($t['text'], ENT_QUOTES, 'UTF-8')) . '</p>'),
                    'lotId' => $lot->id,
                    'deleteStatus' => 1,
                ]);
                $out['notes']++;
                continue;
            }
            $perDate[$date] = ($perDate[$date] ?? 0) + 1;
            $fixed = $t['day'] === 0 && empty($anchored[$t['counter']]);
            if ($fixed) {
                $anchored[$t['counter']] = true;
            }
            $activity = AsScheduleActivity::create([
                'croppingScheduleId' => $schedule->id,
                'versionId' => $version->id,
                'activityTitle' => mb_substr($t['title'], 0, 255),
                'targetDate' => $date,
                'priority' => $t['priority'],
                'activityType' => $t['type'],
                'description' => HtmlSanitizer::rich($this->activityHtml($t, $matById)),
                'timeRequired' => 'n/a',
                'isDayZero' => $t['day'] === 0 && $t['counter'] !== 'DAT',
                'isTransplant' => $t['day'] === 0 && $t['counter'] === 'DAT' && $isDat,
                'isDraft' => false,
                'isHidden' => false,
                'isDone' => false,
                'workerChecklist' => false,
                'workerSelfCheck' => false,
                'sequenceOrder' => ($perDate[$date] - 1) * 10,
                'deleteStatus' => 1,
            ]);
            $activity->lots()->attach($lot->id);
            // What the task draws from the protocol's materials becomes the
            // activity's own item lines — name, quantity, unit — the lines
            // the board's Items section reads. One insert per activity.
            $lines = [];
            $stamp = now('Asia/Manila');
            foreach ($t['groups'] as $g) {
                foreach ($g['items'] as $it) {
                    $m = isset($it['materialId']) ? ($matById[$it['materialId']] ?? null) : null;
                    $used = $m ? self::itemUse($g, $it) : null;
                    if (! $m || $used === null) {
                        continue;
                    }
                    $how = $g['perKnapsack'] && ! empty($g['loads'])
                        ? self::num((float) $it['qty']) . ' ' . $m['unit'] . ' × ' . self::num((float) $g['loads']) . ' knapsack loads'
                        : ($g['title'] !== '' ? $g['title'] : '');
                    $lines[] = [
                        'activityId' => $activity->id,
                        'itemType' => 'custom',
                        'itemName' => mb_substr($m['name'], 0, 255),
                        'quantity' => round($used, 4),
                        'unitOfMeasure' => mb_substr($m['unit'], 0, 30) ?: null,
                        'notes' => mb_substr(trim('From the protocol\'s materials' . ($how !== '' ? ' · ' . $how : '')), 0, 190),
                        'newBuy' => 0,
                        'deleteStatus' => 1,
                        'created_at' => $stamp,
                        'updated_at' => $stamp,
                    ];
                }
            }
            if ($lines) {
                AsScheduleActivityItem::insert($lines);
            }
            $out['activities'][] = ['id' => $activity->id, 'date' => $date, 'fixed' => $fixed];
        }

        return $out;
    }

    /**
     * No day heavier than the hands: walk the calendar day by day; the
     * fixed work (day zero, the transplant) stays, the rest fills what room
     * is left in order and the overflow carries to the next day. Returns
     * how many activities moved.
     */
    private function spread(array $made, int $workers): int
    {
        if (! $made) {
            return 0;
        }
        $byDate = [];
        foreach ($made as $a) {
            $byDate[$a['date']][] = $a;
        }
        ksort($byDate);
        $day = Carbon::parse(array_key_first($byDate));
        $last = Carbon::parse(array_key_last($byDate));
        $carry = [];
        $moved = 0;
        $guard = 0;
        while (($day->lte($last) || $carry) && $guard++ < 2000) {
            $key = $day->format('Y-m-d');
            $today = $byDate[$key] ?? [];
            $fixed = array_values(array_filter($today, fn ($a) => $a['fixed']));
            $loose = array_values(array_filter($today, fn ($a) => ! $a['fixed']));
            $queue = array_merge($carry, $loose);   // what waited longest goes first
            $room = max(0, $workers - count($fixed));
            $keep = array_slice($queue, 0, $room);
            $carry = array_slice($queue, $room);
            $seq = 0;
            foreach (array_merge($fixed, $keep) as $a) {
                $set = ['sequenceOrder' => ($seq++) * 10];
                if ($a['date'] !== $key) {
                    $set['targetDate'] = $key;
                    $moved++;
                }
                AsScheduleActivity::where('id', $a['id'])->update($set);
            }
            $day->addDay();
        }

        return $moved;
    }

    /** The task's words as the activity's description: subtitle, description, what to apply, the note, the hands. */
    private function activityHtml(array $t, array $matById = []): string
    {
        $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $para = fn ($s) => '<p>' . nl2br($e($s)) . '</p>';
        $out = '';
        if ($t['subtitle'] !== '') {
            $out .= '<p><strong>' . $e($t['subtitle']) . '</strong></p>';
        }
        if ($t['description'] !== '') {
            $out .= $para($t['description']);
        }
        if ($t['groups']) {
            $out .= '<p><strong>What to apply</strong></p>';
            foreach ($t['groups'] as $g) {
                $head = trim($g['title']);
                $says = stripos($head, 'knapsack') !== false;   // "Per knapsack (16 L)" already says it
                $loads = ! empty($g['loads']) ? self::num((float) $g['loads']) . ' loads' : '';
                $tail = $g['perKnapsack'] ? ($says ? ($loads !== '' ? ' · ' . $loads : '') : ' (per knapsack' . ($loads !== '' ? ' · ' . $loads : '') . ')') : '';
                if ($head !== '' || $tail !== '') {
                    $out .= '<p><em>' . $e($head !== '' ? $head : 'Per knapsack') . ($head !== '' ? $tail : '') . '</em></p>';
                }
                if ($g['items']) {
                    $out .= '<ul>';
                    foreach ($g['items'] as $it) {
                        $kind = self::KINDS[$it['kind']]['label'] ?? '';
                        $m = isset($it['materialId']) ? ($matById[$it['materialId']] ?? null) : null;
                        $use = $m ? self::itemUse($g, $it) : null;
                        // A quantity drawn from the materials says the whole of it.
                        $total = ($use !== null && $g['perKnapsack'] && ! empty($g['loads']))
                            ? ' × ' . self::num((float) $g['loads']) . ' loads = ' . self::num($use) . ' ' . $m['unit']
                            : '';
                        $out .= '<li>' . $e($it['name']) . ($kind !== '' && $it['kind'] !== 'other' ? ' — ' . $e($kind) : '') . ($it['amount'] !== '' ? ' · ' . $e($it['amount']) . $e($total) : '') . '</li>';
                    }
                    $out .= '</ul>';
                }
            }
        }
        if ($t['note'] !== '') {
            $out .= '<p><em>Note:</em> ' . nl2br($e($t['note'])) . '</p>';
        }
        if ($t['workers'] !== null) {
            $out .= '<p>Workers needed: ' . (int) $t['workers'] . '</p>';
        }

        return $out;
    }

    /* ------------------------------------------------------------ helpers */

    /** A real task — not a note between the tasks, not a phase divider. */
    public static function isTask($t): bool
    {
        $kind = is_array($t) ? ($t['kind'] ?? 'task') : 'task';

        return $kind !== 'note' && $kind !== 'divider';
    }

    /** "DAT 14", or "7 days before DAS 0". */
    public static function sayWhen(string $counter, int $day): string
    {
        return $day < 0
            ? sprintf('%d %s before %s 0', -$day, -$day === 1 ? 'day' : 'days', $counter)
            : sprintf('%s %d', $counter, $day);
    }

    /**
     * The count a crop can honestly take — the Lots form's narrowing
     * (CropCatalog::countersFor): a tree is only ever read by its age, each
     * annual keeps the counts that fit it, no crop leaves the three field
     * counts open. `$keep` is what the row already says: an answer stored
     * before the list narrowed survives, as it does on a lot.
     */
    public static function fitDayType(?string $crop, string $want, ?string $keep = null): string
    {
        $allowed = \App\Support\CropCatalog::countersFor($crop);
        if (in_array($want, $allowed, true)) {
            return $want;
        }
        if ($keep !== null && $want === $keep && $want !== 'TREE' && ! in_array('TREE', $allowed, true)) {
            return $want;
        }

        return $allowed[0] ?? 'DAS';
    }

    private function mine(int $id): ?AsProtocol
    {
        return AsProtocol::active()->where('userId', (int) Auth::id())->where('id', $id)->first();
    }

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    /** What the pages need to draw their pickers and price cards. */
    private function options(): array
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canAnalyze = Tier::farmCan('aiAnalyses') && $payer->canUseAi() && $settings->isUsable();

        // Each crop carries the counts it can honestly take, first = how it is
        // usually grown — the same list the Lots form narrows its select by.
        $crops = [];
        foreach (CropStages::grouped() as $group => $rows) {
            $crops[$group] = array_map(fn ($c) => $c + ['counters' => \App\Support\CropCatalog::countersFor($c['value'])], $rows);
        }

        return [
            'crops' => $crops,
            'dayTypes' => self::DAY_TYPES,
            'dividerColors' => self::DIVIDER_COLORS,
            'types' => AsScheduleActivity::ACTIVITY_TYPES,
            'kinds' => self::KINDS,
            'units' => self::UNITS,
            'canUpload' => self::canUpload(),
            'priorities' => self::PRIORITIES,
            'quote' => (float) AiPrices::of('builder'),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canAnalyze' => $canAnalyze,
            'aiLocked' => ! Tier::farmCan('aiAnalyses'),
            'aneeFace' => $settings->faceUrl(),
            'isWorker' => WorkerContext::inWorkerContext(),
            'creditsUrl' => route('ai.credits'),
        ];
    }

    /** The plan a season-capped farmer is sold: Solo below it, Owner above. */
    private function portRung(): string
    {
        $tier = (string) optional(Auth::user())->planTier();

        return in_array($tier, ['libre', 'libreAnee'], true) ? 'solo' : 'owner';
    }

    private function shape(AsProtocol $p, bool $full, ?Collection $versions = null): array
    {
        $versions = $versions && $versions->isNotEmpty() ? $versions : $this->versions($p);
        $ver = $this->current($p, $versions);
        $tasks = (array) ($ver->tasks ?? []);
        $review = ($p->analysisStatus === 'ready' && is_array($p->analysis)) ? $p->analysis : null;
        $out = [
            'id' => $p->id,
            'title' => $p->title,
            'description' => $p->description,
            'tags' => array_values((array) ($p->tags ?? [])),
            'crop' => $p->crop,
            'cropLabel' => $p->crop ? CropStages::label($p->crop) : null,
            'cropIcon' => $p->crop ? CropStages::icon($p->crop) : '🌱',
            'variety' => $p->variety,
            'dayType' => $p->dayType,
            'count' => count(array_filter($tasks, [self::class, 'isTask'])),
            'notes' => count(array_filter($tasks, fn ($t) => ($t['kind'] ?? 'task') === 'note')),
            'dividers' => count(array_filter($tasks, fn ($t) => ($t['kind'] ?? 'task') === 'divider')),
            'score' => $review ? (int) ($review['score'] ?? 0) : null,
            'reviewed' => ($review && $p->analysisAt) ? Carbon::parse($p->analysisAt)->format('M j, Y') : null,
            'ported' => $p->portedScheduleId ? [
                'scheduleId' => (int) $p->portedScheduleId,
                'at' => $p->portedAt ? Carbon::parse($p->portedAt)->format('M j, Y') : null,
                'url' => route('sm.hub', ['id' => (int) $p->portedScheduleId]),
                'title' => optional(AsCroppingSchedule::find($p->portedScheduleId))->title,
            ] : null,
            'updated' => Carbon::parse(max((string) $p->updated_at, (string) $versions->max('updated_at')))->format('M j, Y'),
            'versionId' => (int) $ver->id,
            'versionName' => $ver->name,
            'versions' => $this->versionRows($versions),
        ];
        if ($full) {
            $out = array_merge($out, $this->versionContent($ver, $p));
            $out['analysis'] = $review;
            $out['analysisStatus'] = $p->analysisStatus;
            $out['analysisAt'] = $p->analysisAt ? Carbon::parse($p->analysisAt)->format('M j, Y · g:i A') : null;
            $out['analysisCredits'] = (float) $p->analysisCredits;
        }

        return $out;
    }

    /** The stage table per counter the protocol may use, as [fromDay, label] rows. */
    private function stagesFor(?string $crop): array
    {
        $out = [];
        if (! $crop) {
            return $out;
        }
        // A tree's stages are months of its age, not days of any count.
        if (CropStages::isPerennial($crop)) {
            $rows = CropStages::stagesFor($crop, 'AGE');
            $out['AGE'] = array_values(array_map(fn ($r) => [(int) $r[0], (string) $r[1]], array_filter($rows, fn ($r) => is_array($r) && isset($r[0], $r[1]))));

            return $out;
        }
        foreach (['DAS', 'DAT', 'DAP'] as $counter) {
            $rows = CropStages::stagesFor($crop, $counter);
            $out[$counter] = array_values(array_map(fn ($r) => [(int) $r[0], (string) $r[1]], array_filter($rows, fn ($r) => is_array($r) && isset($r[0], $r[1]))));
        }

        return $out;
    }

    /** Every task, every group, every item — trimmed, bounded, and in the protocol's own counters. */
    private function cleanTasks(array $tasks, string $dayType, array $materials = []): array
    {
        $allowed = self::DAY_TYPES[$dayType]['counters'] ?? ['DAS'];
        // An item drawn from the materials takes its name and kind from the
        // material and says its amount in the material's unit.
        $matById = [];
        foreach ($materials as $m) {
            if (is_array($m) && isset($m['id'], $m['name'])) {
                $matById[(string) $m['id']] = $m;
            }
        }
        $out = [];
        $seen = [];
        foreach (array_slice(array_values($tasks), 0, self::MAX_TASKS) as $i => $t) {
            if (! is_array($t)) {
                continue;
            }
            $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($t['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                $id = 't' . substr(md5(uniqid((string) $i, true)), 0, 8);
            }
            $seen[$id] = true;
            $counter = strtoupper((string) ($t['counter'] ?? $allowed[0]));
            if (! in_array($counter, $allowed, true)) {
                $counter = $allowed[0];
            }
            // "Before" is only ever before the program starts — before the
            // sowing or the planting, never before a transplant.
            if ((int) ($t['day'] ?? 0) < 0) {
                $counter = $allowed[0];
            }
            // A note between the tasks: words on a day, nothing else.
            if (($t['kind'] ?? '') === 'note') {
                $text = $this->text($t['text'] ?? '', 2000);
                if ($text === null) {
                    continue;
                }
                $out[] = [
                    'id' => $id,
                    'kind' => 'note',
                    'counter' => $counter,
                    'day' => max(-365, min(999, (int) ($t['day'] ?? 0))),
                    'text' => $text,
                    'pos' => (int) ($t['pos'] ?? $i * 10),
                ];
                continue;
            }
            // A phase divider: a named line between the tasks ("Vegetative
            // phase"), on the day it begins. Not a task — never counted,
            // never warned about, handed to Anee as a heading.
            if (($t['kind'] ?? '') === 'divider') {
                $label = $this->text($t['label'] ?? '', 80);
                if ($label === null) {
                    continue;
                }
                $color = (string) ($t['color'] ?? 'amber');
                $out[] = [
                    'id' => $id,
                    'kind' => 'divider',
                    'counter' => $counter,
                    'day' => max(-365, min(999, (int) ($t['day'] ?? 0))),
                    'label' => $label,
                    'color' => in_array($color, self::DIVIDER_COLORS, true) ? $color : 'amber',
                    'pos' => (int) ($t['pos'] ?? $i * 10),
                ];
                continue;
            }
            $groups = [];
            foreach (array_slice((array) ($t['groups'] ?? []), 0, 20) as $g) {
                if (! is_array($g)) {
                    continue;
                }
                $items = [];
                foreach (array_slice((array) ($g['items'] ?? []), 0, 40) as $it) {
                    if (! is_array($it)) {
                        continue;
                    }
                    $mid = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($it['materialId'] ?? ''));
                    $m = $mid !== '' ? ($matById[$mid] ?? null) : null;
                    if ($m) {
                        $qty = self::number($it['qty'] ?? null);
                        $unit = (string) ($m['unit'] ?? '');
                        $mk = (string) ($m['kind'] ?? 'other');
                        $items[] = [
                            'id' => $this->key($it['id'] ?? null),
                            'name' => mb_substr((string) $m['name'], 0, 160),
                            'kind' => isset(self::KINDS[$mk]) ? $mk : 'other',
                            'amount' => $qty !== null ? trim(self::num($qty) . ' ' . $unit) : '',
                            'materialId' => $mid,
                            'qty' => $qty,
                        ];
                        continue;
                    }
                    $name = $this->text($it['name'] ?? '', 160) ?? '';
                    if ($name === '') {
                        continue;
                    }
                    $kind = (string) ($it['kind'] ?? 'other');
                    $items[] = [
                        'id' => $this->key($it['id'] ?? null),
                        'name' => $name,
                        'kind' => isset(self::KINDS[$kind]) ? $kind : 'other',
                        'amount' => $this->text($it['amount'] ?? '', 80) ?? '',
                    ];
                }
                $knap = (bool) ($g['perKnapsack'] ?? false);
                $loads = $knap ? self::number($g['loads'] ?? null) : null;
                $groups[] = [
                    'id' => $this->key($g['id'] ?? null),
                    'title' => $this->text($g['title'] ?? '', 120) ?? '',
                    'perKnapsack' => $knap,
                    // How many knapsack loads the group is mixed for: what a
                    // task draws from the materials is the quantity × loads.
                    'loads' => ($loads !== null && $loads > 0) ? min(9999.0, $loads) : null,
                    'items' => $items,
                ];
            }
            $type = (string) ($t['type'] ?? '');
            $priority = (string) ($t['priority'] ?? 'medium');
            $workers = $t['workers'] ?? null;
            $out[] = [
                'id' => $id,
                'counter' => $counter,
                'day' => max(-365, min(999, (int) ($t['day'] ?? 0))),
                'title' => $this->text($t['title'] ?? '', 160) ?? 'Untitled task',
                'subtitle' => $this->text($t['subtitle'] ?? '', 200) ?? '',
                'description' => $this->text($t['description'] ?? '', 4000) ?? '',
                'type' => isset(AsScheduleActivity::ACTIVITY_TYPES[$type]) ? $type : null,
                'groups' => $groups,
                'note' => $this->text($t['note'] ?? '', 2000) ?? '',
                'priority' => isset(self::PRIORITIES[$priority]) ? $priority : 'medium',
                'workers' => ($workers === null || $workers === '') ? null : max(0, min(999, (int) $workers)),
                'pos' => (int) ($t['pos'] ?? $i * 10),
            ];
        }

        return $out;
    }

    /** The protocol's own list of materials: what there is, how much, in what unit. */
    private function cleanMaterials(array $list): array
    {
        $out = [];
        $seen = [];
        foreach (array_slice(array_values($list), 0, self::MAX_MATERIALS) as $i => $m) {
            if (! is_array($m)) {
                continue;
            }
            $name = $this->text($m['name'] ?? '', 120);
            if ($name === null) {
                continue;
            }
            $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($m['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                $id = 'm' . substr(md5(uniqid((string) $i, true)), 0, 8);
            }
            $seen[$id] = true;
            $kind = (string) ($m['kind'] ?? 'other');
            $qty = self::number($m['qty'] ?? null);
            $out[] = [
                'id' => mb_substr($id, 0, 24),
                'name' => $name,
                'kind' => isset(self::KINDS[$kind]) ? $kind : 'other',
                'qty' => $qty,
                'unit' => $this->text($m['unit'] ?? '', 20) ?? '',
                'note' => $this->text($m['note'] ?? '', 500) ?? '',
                'pos' => (int) ($m['pos'] ?? $i * 10),
            ];
        }
        usort($out, fn ($a, $b) => $a['pos'] <=> $b['pos']);

        return $out;
    }

    /** A number typed by a person, or null: never negative, never absurd, three decimals at most. */
    private static function number($v): ?float
    {
        if ($v === null || $v === '' || ! is_numeric($v)) {
            return null;
        }

        return round(max(0.0, min(10000000.0, (float) $v)), 3);
    }

    /** 1.5, 2, 0.125 — a quantity as a person writes it. */
    public static function num(float $n): string
    {
        $s = rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }

    /** How much of its material one item uses: the quantity, times the loads for a knapsack group. */
    public static function itemUse(array $g, array $it): ?float
    {
        if (empty($it['materialId']) || ! isset($it['qty']) || $it['qty'] === null) {
            return null;
        }
        $loads = ! empty($g['perKnapsack']) && ! empty($g['loads']) ? (float) $g['loads'] : 1.0;

        return (float) $it['qty'] * $loads;
    }

    /** What the tasks draw from each material, by material id. */
    public static function materialUse(array $tasks): array
    {
        $use = [];
        foreach ($tasks as $t) {
            if (! self::isTask($t)) {
                continue;
            }
            foreach ((array) ($t['groups'] ?? []) as $g) {
                foreach ((array) ($g['items'] ?? []) as $it) {
                    $u = self::itemUse((array) $g, (array) $it);
                    if ($u !== null) {
                        $use[$it['materialId']] = ($use[$it['materialId']] ?? 0.0) + $u;
                    }
                }
            }
        }

        return $use;
    }

    /** Files beside a protocol are Libre + Anee and above; writing its rules is every tier's. */
    public static function canUpload(): bool
    {
        return Tier::of() !== 'libre';
    }

    /** The protocol's versions, in order. A protocol that has none gets Version 1 from what it holds. */
    private function versions(AsProtocol $p): Collection
    {
        $rows = AsProtocolVersion::active()->where('protocolId', $p->id)->where('userId', (int) $p->userId)
            ->orderBy('sortOrder')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $ver = AsProtocolVersion::create(array_merge($this->blankVersion($p, 'Version 1'), [
                'tasks' => array_values((array) ($p->tasks ?? [])),
                'history' => is_array($p->history) ? $p->history : ['undo' => [], 'redo' => []],
                'rev' => max(1, (int) $p->rev),
            ]));
            $p->forceFill(['versionId' => $ver->id])->save();
            $rows = collect([$ver]);
        }

        return $rows;
    }

    /** The version in use — or the first, when the one named is gone. */
    private function current(AsProtocol $p, ?Collection $rows = null): AsProtocolVersion
    {
        $rows = ($rows && $rows->isNotEmpty()) ? $rows : $this->versions($p);

        return $rows->firstWhere('id', (int) $p->versionId) ?? $rows->first();
    }

    /** One of the member's own versions of this protocol, or null. */
    private function versionOf(AsProtocol $p, int $vid): ?AsProtocolVersion
    {
        return AsProtocolVersion::active()->where('protocolId', $p->id)->where('userId', (int) Auth::id())->where('id', $vid)->first();
    }

    private function blankVersion(AsProtocol $p, string $name): array
    {
        return [
            'protocolId' => $p->id,
            'userId' => (int) $p->userId,
            'name' => mb_substr($name, 0, 80),
            'tasks' => [],
            'materials' => [],
            'rules' => null,
            'files' => [],
            'history' => ['undo' => [], 'redo' => []],
            'rev' => 1,
            'sortOrder' => 0,
            'deleteStatus' => 1,
        ];
    }

    /** A version's content as a new version of `$into` — its own history starts empty. */
    private function copyOf(AsProtocolVersion $from, AsProtocol $into, string $name, int $sortOrder): array
    {
        return array_merge($this->blankVersion($into, $name), [
            'tasks' => array_values((array) ($from->tasks ?? [])),
            'materials' => array_values((array) ($from->materials ?? [])),
            'rules' => $from->rules,
            'files' => array_values((array) ($from->files ?? [])),
            'sortOrder' => $sortOrder,
        ]);
    }

    /** The version chooser's rows: name, how many tasks, how many materials. */
    private function versionRows(Collection $rows): array
    {
        return $rows->map(fn ($v) => [
            'id' => (int) $v->id,
            'name' => $v->name,
            'count' => count(array_filter((array) ($v->tasks ?? []), [self::class, 'isTask'])),
            'materials' => count((array) ($v->materials ?? [])),
        ])->values()->all();
    }

    /** Everything the editor holds for one version. */
    private function versionContent(AsProtocolVersion $v, AsProtocol $p): array
    {
        $history = is_array($v->history) ? $v->history : [];

        return [
            'versionId' => (int) $v->id,
            'versionName' => $v->name,
            'tasks' => array_values((array) ($v->tasks ?? [])),
            'materials' => array_values((array) ($v->materials ?? [])),
            'rules' => (string) ($v->rules ?? ''),
            'files' => array_map(fn ($f) => $this->fileShape($f), array_values(array_filter((array) ($v->files ?? []), 'is_array'))),
            'history' => ['undo' => array_values((array) ($history['undo'] ?? [])), 'redo' => array_values((array) ($history['redo'] ?? []))],
            'rev' => (int) $v->rev,
        ];
    }

    private function fileShape(array $f): array
    {
        return [
            'id' => (string) ($f['id'] ?? ''),
            'name' => (string) ($f['name'] ?? 'File'),
            'size' => (int) ($f['size'] ?? 0),
            'mime' => (string) ($f['mime'] ?? ''),
            'at' => (string) ($f['at'] ?? ''),
            'url' => MediaStore::url($f['path'] ?? null),
        ];
    }

    /** True when another live version (of any protocol — copies share files) still lists this stored file. */
    private function fileShared(string $path, int $exceptVersionId): bool
    {
        $needle = basename(MediaStore::strip($path));
        if ($needle === '') {
            return false;
        }

        return AsProtocolVersion::active()->where('id', '!=', $exceptVersionId)
            ->where('files', 'like', '%' . addcslashes($needle, '%_\\') . '%')
            ->get(['id', 'files'])
            ->contains(fn ($x) => collect((array) ($x->files ?? []))->contains(fn ($f) => is_array($f) && ($f['path'] ?? null) === $path));
    }

    /** The two stacks, the newest few, and never more than the row can hold. */
    private function cleanHistory(array $h): array
    {
        $undo = array_slice(array_values(array_filter((array) ($h['undo'] ?? []), 'is_array')), -self::MAX_HISTORY);
        $redo = array_slice(array_values(array_filter((array) ($h['redo'] ?? []), 'is_array')), -self::MAX_HISTORY);
        $payload = json_encode(['undo' => $undo, 'redo' => $redo]);
        while (strlen($payload) > self::MAX_HISTORY_BYTES && (count($undo) || count($redo))) {
            if (count($redo) >= count($undo)) {
                array_shift($redo);
            } else {
                array_shift($undo);
            }
            $payload = json_encode(['undo' => $undo, 'redo' => $redo]);
        }

        return ['undo' => $undo, 'redo' => $redo];
    }

    private function key($v): string
    {
        $k = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($v ?? ''));

        return $k !== '' ? mb_substr($k, 0, 24) : 'k' . substr(md5(uniqid('', true)), 0, 8);
    }

    private function text($v, int $max): ?string
    {
        $s = trim((string) ($v ?? ''));
        if ($s === '') {
            return null;
        }

        return mb_substr($s, 0, $max);
    }

    private function orNot(?string $s): string
    {
        return ($s !== null && trim($s) !== '') ? trim($s) : 'not stated';
    }

    private function joined(array $lines, string $empty = '(none)'): string
    {
        return $lines ? implode("\n", $lines) : $empty;
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
