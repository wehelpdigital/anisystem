<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Models\AsProtocol;
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
use App\Support\Tier;
use App\Support\WorkerContext;
use App\Http\Controllers\UserTagController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
 * Rows are the acting user's own — every query is fenced on userId.
 */
class ProtocolBuilderController extends Controller
{
    /** How a protocol counts its days, and which counters its tasks may use. */
    public const DAY_TYPES = [
        'DAT' => ['label' => 'DAS → DAT', 'sub' => 'Sown in a seedbed, then transplanted — the count restarts at the transplant. Work before the program is counted back from the sowing.', 'counters' => ['DAS', 'DAT'], 'icon' => '🌾'],
        'DAS' => ['label' => 'DAS only', 'sub' => 'Direct seeded — one count from sowing to harvest.', 'counters' => ['DAS'], 'icon' => '🌱'],
        'DAP' => ['label' => 'DAP', 'sub' => 'Planted from seedlings, cuttings, tubers or setts — days after planting.', 'counters' => ['DAP'], 'icon' => '🪴'],
    ];

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

    private const MAX_TASKS = 300;
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
            'startInEdit' => request()->boolean('edit'),
        ]);
    }

    /* ------------------------------------------------------------ the shelf */

    public function list()
    {
        $rows = AsProtocol::active()->where('userId', (int) Auth::id())
            ->orderByDesc('updated_at')->limit(300)->get();

        return $this->json(true, '', ['rows' => $rows->map(fn ($p) => $this->shape($p, false))->values()->all()]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:190',
            'description' => 'nullable|string|max:2000',
            'crop' => 'nullable|string|max:60',
            'variety' => 'nullable|string|max:120',
            'dayType' => 'required|in:DAS,DAT,DAP',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:30',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        $crop = CropStages::normalize($request->input('crop'));
        $p = AsProtocol::create([
            'userId' => (int) Auth::id(),
            'title' => trim((string) $request->input('title')),
            'description' => $this->text($request->input('description'), 2000),
            'tags' => UserTagController::tidy($request->input('tags', [])),
            'crop' => $crop,
            'variety' => $this->text($request->input('variety'), 120),
            'dayType' => $request->input('dayType'),
            'tasks' => [],
            'history' => ['undo' => [], 'redo' => []],
            'rev' => 1,
            'deleteStatus' => 1,
        ]);

        return $this->json(true, 'Protocol started.', ['id' => $p->id, 'url' => route('pb.open', ['id' => $p->id, 'edit' => 1])]);
    }

    /** The autosave: the whole task list, the history beside it, and the rev the page holds. */
    public function save(Request $request, int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $v = Validator::make($request->all(), [
            'rev' => 'required|integer|min:1',
            'tasks' => 'present|array|max:' . self::MAX_TASKS,
            'history' => 'nullable|array',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        if ((int) $request->input('rev') !== (int) $p->rev) {
            return $this->json(false, 'This protocol was changed somewhere else. Reload to keep working on the latest.', ['stale' => true, 'rev' => (int) $p->rev], 409);
        }
        $tasks = $this->cleanTasks((array) $request->input('tasks', []), $p->dayType);
        $history = $this->cleanHistory((array) $request->input('history', []));

        $p->forceFill([
            'tasks' => $tasks,
            'history' => $history,
            'rev' => (int) $p->rev + 1,
        ])->save();

        return $this->json(true, 'Saved.', ['rev' => (int) $p->rev, 'savedAt' => now()->format('H:i'), 'count' => count($tasks)]);
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
            'dayType' => 'required|in:DAS,DAT,DAP',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:30',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        $dayType = $request->input('dayType');
        $tasks = (array) ($p->tasks ?? []);
        if ($dayType !== $p->dayType) {
            // The tasks keep their days; only the word changes when the new
            // count does not know their counter.
            $allowed = self::DAY_TYPES[$dayType]['counters'];
            foreach ($tasks as &$t) {
                if (! in_array($t['counter'] ?? '', $allowed, true)) {
                    $t['counter'] = $allowed[0];
                }
            }
            unset($t);
        }
        $p->forceFill([
            'title' => trim((string) $request->input('title')),
            'description' => $this->text($request->input('description'), 2000),
            'tags' => UserTagController::tidy($request->input('tags', [])),
            'crop' => CropStages::normalize($request->input('crop')),
            'variety' => $this->text($request->input('variety'), 120),
            'dayType' => $dayType,
            'tasks' => $this->cleanTasks($tasks, $dayType),
            'rev' => (int) $p->rev + 1,
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
            'tasks' => $p->tasks ?? [],
            'history' => ['undo' => [], 'redo' => []],
            'rev' => 1,
            'deleteStatus' => 1,
        ]);

        return $this->json(true, 'Copied.', ['id' => $copy->id, 'row' => $this->shape($copy, false)]);
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
        $tasks = $this->cleanTasks((array) ($p->tasks ?? []), $p->dayType);
        if (! count(array_filter($tasks, fn ($t) => ($t['kind'] ?? '') !== 'note'))) {
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
        $prompt = $this->prompt($p, $tasks);

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
    private function prompt(AsProtocol $p, array $tasks): string
    {
        $cropLabel = $p->crop ? (CropStages::label($p->crop) ?: $p->crop) : 'an unnamed crop';
        $dt = self::DAY_TYPES[$p->dayType] ?? self::DAY_TYPES['DAS'];
        $region = \App\Support\Region::name();
        $lines = [];
        $n = 0;
        foreach ($tasks as $t) {
            if (($t['kind'] ?? '') === 'note') {
                $lines[] = '   NOTE (the farmer\'s own words, placed here): ' . preg_replace('/\s+/', ' ', $t['text']);
                continue;
            }
            $i = $n++;
            $when = $t['day'] < 0 ? sprintf('%d days before %s 0', -$t['day'], $t['counter']) : sprintf('%s %d', $t['counter'], $t['day']);
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
                $lines[] = '   Apply' . ($g['title'] !== '' ? ' — ' . $g['title'] : '') . ($g['perKnapsack'] ? ' (per knapsack)' : '') . ':';
                foreach ($g['items'] as $it) {
                    $lines[] = '     - ' . $it['name'] . ' [' . (self::KINDS[$it['kind']]['label'] ?? $it['kind']) . ']' . ($it['amount'] !== '' ? ' · ' . $it['amount'] : '');
                }
            }
            if ($t['note'] !== '') {
                $lines[] = '   Note: ' . preg_replace('/\s+/', ' ', $t['note']);
            }
        }
        $stages = [];
        foreach ($this->stagesFor($p->crop) as $counter => $rows) {
            if (! $rows) {
                continue;
            }
            $stages[] = $counter . ': ' . implode('; ', array_map(fn ($r) => $r[1] . ' from ' . $counter . ' ' . $r[0], $rows));
        }
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
Description: {$description}

THE TASKS, in the farmer's order (the code in [brackets] is the task's id — quote it exactly in "tasks"; "14 days before DAS 0" is work done before the count starts, such as land preparation)
{$taskText}

THE APP'S OWN GROWTH STAGE TABLE for this crop (day the stage begins)
{$stageText}

WHAT TO JUDGE
- Coverage: land preparation, planting/transplanting, water, nutrition by stage, weed / pest / disease control, monitoring, harvest — what is there and what is missing for this crop.
- Timing: is each task on a sensible day of the count for its stage? Point out anything too early, too late, or out of order.
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
  "additions": [{"counter": "<DAS|DAT|DAP, one this protocol uses>", "day": <integer; a NEGATIVE number means that many days BEFORE that counter's day 0, e.g. -14 for land preparation two weeks before>, "title": "<task to add>", "type": "<one of: {$typeKeys}>", "why": "<one sentence>"}],
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

    /**
     * Make a cropping schedule out of the protocol: one lot, one activity per
     * task, dated from a start. NOT ROUTED for now — the owner will give the
     * port its own module; the method waits here for it.
     */
    public function port(Request $request, int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $user = $request->user();
        if (! $user->canCreateSchedule()) {
            $limit = $user->scheduleLimit();
            Tier::deny('Your plan allows ' . ($limit === 0 ? 'no' : 'up to ' . $limit) . ' active ' . ($limit === 1 ? 'season' : 'seasons') . '. Finish or archive one, or upgrade for more.', $this->portRung());
        }
        $isDat = $p->dayType === 'DAT';
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'startDate' => 'required|date',
            'transplantDate' => ($isDat ? 'required' : 'nullable') . '|date|after_or_equal:startDate',
            'lotName' => 'nullable|string|max:255',
            'lotSize' => 'nullable|numeric|min:0|max:99999',
            'lotSizeUnit' => 'nullable|in:hectare,sqm,acre',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        $tasks = $this->cleanTasks((array) ($p->tasks ?? []), $p->dayType);
        $notes = array_values(array_filter($tasks, fn ($t) => ($t['kind'] ?? '') === 'note'));
        $tasks = array_values(array_filter($tasks, fn ($t) => ($t['kind'] ?? '') !== 'note'));
        if (! count($tasks)) {
            return $this->json(false, 'The protocol has no tasks yet — nothing to port.', [], 422);
        }
        $start = Carbon::parse($request->input('startDate'))->startOfDay();
        $transplant = $isDat ? Carbon::parse($request->input('transplantDate'))->startOfDay() : null;
        $cropLabel = $p->crop ? (CropStages::label($p->crop) ?: $p->crop) : null;

        $schedule = DB::transaction(function () use ($p, $tasks, $notes, $request, $start, $transplant, $cropLabel, $isDat) {
            $schedule = AsCroppingSchedule::create([
                'anisystemUserId' => (int) Auth::id(),
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'title' => trim((string) $request->input('title')),
                'description' => $this->text($p->description, 5000),
                'cropType' => $cropLabel,
                'cropVariety' => $p->variety,
                'dayType' => $p->dayType,
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
            $lot = AsScheduleLot::create([
                'croppingScheduleId' => $schedule->id,
                'lotName' => $this->text($request->input('lotName'), 255) ?: (($cropLabel ?: 'Main') . ' lot'),
                'lotSize' => $request->filled('lotSize') ? (float) $request->input('lotSize') : 1,
                'lotSizeUnit' => $request->input('lotSizeUnit') ?: 'hectare',
                'variety' => $p->variety,
                'crop' => $p->crop,
                'dayType' => $p->dayType,
                'dayZeroDate' => $start->format('Y-m-d'),
                'transplantDate' => $transplant ? $transplant->format('Y-m-d') : null,
                'deleteStatus' => 1,
            ]);
            $perDate = [];
            foreach ($tasks as $t) {
                $base = ($t['counter'] === 'DAT' && $transplant) ? $transplant : $start;
                $date = $base->copy()->addDays((int) $t['day'])->format('Y-m-d');
                $perDate[$date] = ($perDate[$date] ?? 0) + 1;
                $activity = AsScheduleActivity::create([
                    'croppingScheduleId' => $schedule->id,
                    'versionId' => $version->id,
                    'activityTitle' => mb_substr($t['title'], 0, 255),
                    'targetDate' => $date,
                    'priority' => $t['priority'],
                    'activityType' => $t['type'],
                    'description' => HtmlSanitizer::rich($this->activityHtml($t)),
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
            }
            // The notes land on the day book, on the day their place implies.
            foreach ($notes as $nt) {
                $base = ($nt['counter'] === 'DAT' && $transplant) ? $transplant : $start;
                \App\Models\AsScheduleDateNote::create([
                    'croppingScheduleId' => $schedule->id,
                    'versionId' => $version->id,
                    'noteDate' => $base->copy()->addDays((int) $nt['day'])->format('Y-m-d'),
                    'noteContent' => HtmlSanitizer::rich('<p>' . nl2br(htmlspecialchars($nt['text'], ENT_QUOTES, 'UTF-8')) . '</p>'),
                    'lotId' => $lot->id,
                    'deleteStatus' => 1,
                ]);
            }

            return $schedule;
        });

        $p->forceFill(['portedScheduleId' => $schedule->id, 'portedAt' => now()])->save();

        return $this->json(true, 'The season is set up — ' . count($tasks) . ' activities on the board' . (count($notes) ? ' and ' . count($notes) . ($notes && count($notes) === 1 ? ' note' : ' notes') . ' on the day book' : '') . '.', [
            'scheduleId' => $schedule->id,
            'redirect' => route('sm.hub', ['id' => $schedule->id]),
        ]);
    }

    /** The task's words as the activity's description: subtitle, description, what to apply, the note, the hands. */
    private function activityHtml(array $t): string
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
                $tail = $g['perKnapsack'] ? ' (per knapsack)' : '';
                if ($head !== '' || $tail !== '') {
                    $out .= '<p><em>' . $e($head !== '' ? $head : 'Per knapsack') . ($head !== '' ? $tail : '') . '</em></p>';
                }
                if ($g['items']) {
                    $out .= '<ul>';
                    foreach ($g['items'] as $it) {
                        $kind = self::KINDS[$it['kind']]['label'] ?? '';
                        $out .= '<li>' . $e($it['name']) . ($kind !== '' && $it['kind'] !== 'other' ? ' — ' . $e($kind) : '') . ($it['amount'] !== '' ? ' · ' . $e($it['amount']) : '') . '</li>';
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

        return [
            'crops' => CropStages::grouped(),
            'dayTypes' => self::DAY_TYPES,
            'types' => AsScheduleActivity::ACTIVITY_TYPES,
            'kinds' => self::KINDS,
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

    private function shape(AsProtocol $p, bool $full): array
    {
        $tasks = (array) ($p->tasks ?? []);
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
            'count' => count(array_filter($tasks, fn ($t) => ($t['kind'] ?? 'task') !== 'note')),
            'notes' => count(array_filter($tasks, fn ($t) => ($t['kind'] ?? 'task') === 'note')),
            'score' => $review ? (int) ($review['score'] ?? 0) : null,
            'reviewed' => ($review && $p->analysisAt) ? Carbon::parse($p->analysisAt)->format('M j, Y') : null,
            'ported' => $p->portedScheduleId ? [
                'scheduleId' => (int) $p->portedScheduleId,
                'at' => $p->portedAt ? Carbon::parse($p->portedAt)->format('M j, Y') : null,
                'url' => route('sm.hub', ['id' => (int) $p->portedScheduleId]),
                'title' => optional(AsCroppingSchedule::find($p->portedScheduleId))->title,
            ] : null,
            'updated' => $p->updated_at ? Carbon::parse($p->updated_at)->format('M j, Y') : null,
        ];
        if ($full) {
            $out['tasks'] = array_values($tasks);
            $history = is_array($p->history) ? $p->history : [];
            $out['history'] = ['undo' => array_values((array) ($history['undo'] ?? [])), 'redo' => array_values((array) ($history['redo'] ?? []))];
            $out['rev'] = (int) $p->rev;
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
        foreach (['DAS', 'DAT', 'DAP'] as $counter) {
            $rows = CropStages::stagesFor($crop, $counter);
            $out[$counter] = array_values(array_map(fn ($r) => [(int) $r[0], (string) $r[1]], array_filter($rows, fn ($r) => is_array($r) && isset($r[0], $r[1]))));
        }

        return $out;
    }

    /** Every task, every group, every item — trimmed, bounded, and in the protocol's own counters. */
    private function cleanTasks(array $tasks, string $dayType): array
    {
        $allowed = self::DAY_TYPES[$dayType]['counters'] ?? ['DAS'];
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
                $groups[] = [
                    'id' => $this->key($g['id'] ?? null),
                    'title' => $this->text($g['title'] ?? '', 120) ?? '',
                    'perKnapsack' => (bool) ($g['perKnapsack'] ?? false),
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
