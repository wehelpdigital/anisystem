<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * What to Plant — the sister of When to Plant, asked the other way round.
 *
 * The farmer answers a wizard about the ground itself (place, soil, water,
 * troubles, when they want to start, what the harvest is for) and the model
 * is asked WHICH crops suit that ground best — ranked across the families a
 * Philippine farm actually weighs: grains, vegetables, root crops, legumes,
 * fruit trees. Same shelf, same job walk, same honest ledger as its sister:
 * the price is said before anything is spent and charged exactly as said.
 */
class WhatToPlantController extends Controller
{
    /** The house's flat price for one analysis, in credits. */
    public const PRICE = 100;

    public const SOILS = [
        'clay' => 'Heavy clay — sticky wet, cracks dry',
        'loam' => 'Loam — dark, crumbly, holds water well',
        'sandy' => 'Sandy — light, drains fast',
        'silty' => 'Silty — fine, smooth, floodplain soil',
        'rocky' => 'Rocky / shallow soil',
        'unsure' => 'Not sure — it grows weeds fine',
    ];

    public const WATERS = [
        'irrigated' => 'Reliable irrigation all season',
        'limited' => 'Some irrigation, but limited',
        'rainfed' => 'Rain only',
    ];

    public const AIMS = [
        'sell' => 'To sell at the market',
        'family' => 'To feed the family',
        'both' => 'Both — eat some, sell the rest',
    ];

    /** The ground's troubles — the sister module's list, minus the two the
     *  soil question already answers. */
    public const PROBLEMS = [
        'floods' => 'Floods / standing water after rain',
        'water_source' => 'Limited irrigation water source',
        'river' => 'Beside a river (overflow reaches the field)',
        'sea' => 'Near the sea (salt spray / brackish water)',
        'wind' => 'Strong winds pass through (typhoon corridor)',
        'pests' => 'Pests have been heavy in past seasons',
        'weeds' => 'Weed pressure is heavy',
        'drainage' => 'Poor drainage',
        'shade' => 'Part of the field is shaded',
        'slope' => 'Sloping / hilly ground',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    public function page()
    {
        return view('what-to-plant.index');
    }

    /** Everything the wizard needs, plus the standing price. */
    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        // The next twelve months, so "when do you want to start" is a tap.
        $months = [];
        $cursor = now('Asia/Manila')->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $months[] = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('F Y')];
            $cursor = $cursor->addMonth();
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'soils' => self::SOILS,
            'waters' => self::WATERS,
            'aims' => self::AIMS,
            'problems' => self::PROBLEMS,
            'months' => $months,
            'quote' => $canUse ? (float) self::PRICE : null,
            'aneeFace' => $settings->faceUrl(),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null
                : ($settings->isUsable()
                    ? 'This analysis runs on the AI Technician, which needs a Boss or Lifetime plan'
                        . ((int) $payer->id === (int) Auth::id() ? '.' : ' on the farm owner\'s account.')
                    : 'The AI Technician is not switched on yet. Please check back soon.'),
        ]]);
    }

    /** Run the analysis — the sister's job walk, question swapped. */
    public function generate(Request $request)
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'The analysis needs the AI Technician (Boss or Lifetime plan).', [], 403);
        }

        $v = Validator::make($request->all(), [
            'location' => 'required|string|max:160',
            'startMonth' => 'required|date_format:Y-m',
            'soil' => 'required|in:' . implode(',', array_keys(self::SOILS)),
            'water' => 'required|in:' . implode(',', array_keys(self::WATERS)),
            'aim' => 'required|in:' . implode(',', array_keys(self::AIMS)),
            'area' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:400',
            'problems' => 'nullable|array',
            'problems.*' => 'string|in:' . implode(',', array_keys(self::PROBLEMS)),
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }

        $balance = $this->credits->balance($payer->id);
        if ($balance < self::PRICE && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . self::PRICE . ' credits for this analysis and have '
                . rtrim(rtrim(number_format($balance, 2), '0'), '.') . '.',
                ['outOfCredits' => true], 402);
        }

        // One in flight at a time — a double press must not buy two.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        $p = $this->params($request);
        $title = 'What to plant · ' . \Illuminate\Support\Carbon::parse($p['startMonth'] . '-01')->format('M Y')
            . ' · ' . $p['location'];
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'kind' => 'what',
            'title' => mb_substr($title, 0, 190),
            'params' => json_encode($p),
            'report' => json_encode(new \stdClass),
            'credits' => 0,
            'status' => 'pending',
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $prompt = $this->prompt($p);

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $id,
            ]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $prompt);
            exit;
        }

        @set_time_limit(300);
        $this->runJob($id, (int) $payer->id, $settings, $prompt);

        return $this->jobState($id);
    }

    /** The model call and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt): void
    {
        try {
            $result = $this->ai->ask($settings, [], $prompt, null, 4000);
            if (! ($result['ok'] ?? false)) {
                sleep(3);
                $result = $this->ai->ask($settings, [], $prompt, null, 4000);
            }
            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException($result['error'] ?? 'The AI could not be reached. Nothing was charged.');
            }

            $report = $this->parseReport((string) $result['text']);
            if ($report === null) {
                $retry = $this->ai->ask($settings, [
                    ['role' => 'user', 'text' => $prompt],
                    ['role' => 'assistant', 'text' => (string) $result['text']],
                ], 'That was not valid JSON. Return ONLY the JSON object described, with no fences and no commentary.', null, 4000);
                if ($retry['ok'] ?? false) {
                    $report = $this->parseReport((string) $retry['text']);
                }
            }
            if ($report === null) {
                \Illuminate\Support\Facades\Log::warning('what-to-plant: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException('The analysis came back unreadable. Nothing was charged — please try again.');
            }

            $charged = (float) self::PRICE;
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $p = json_decode((string) ($row->params ?? '[]'), true) ?: [];
            $this->credits->chargeAllowingNegative($payerId, $charged,
                'What-to-plant analysis — ' . ($p['location'] ?? ''));

            DB::table('as_plant_analyses')->where('id', $id)->update([
                'report' => json_encode($report),
                'credits' => round($charged, 2),
                'status' => 'ready',
                'error' => null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'updated_at' => now(),
            ]);
        }
    }

    /** Where a job stands — polled by the page until ready or failed. */
    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'what')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending']);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The analysis failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Analysis ready.', [
            'status' => 'ready',
            'savedId' => (int) $r->id,
            'report' => json_decode($r->report, true),
            'params' => json_decode($r->params, true),
            'charged' => (float) $r->credits,
            'balance' => round($this->credits->balance($this->payer()->id), 2),
        ]);
    }

    public function list()
    {
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            ->get(['id', 'title', 'description', 'credits', 'created_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'credits' => (float) $r->credits,
            'at' => \Illuminate\Support\Carbon::parse($r->created_at)->format('M j, Y'),
        ])->values()]);
    }

    public function one(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }

        return $this->json(true, 'ok', [
            'id' => $r->id,
            'title' => $r->title,
            'params' => json_decode($r->params, true),
            'report' => json_decode($r->report, true),
            'credits' => (float) $r->credits,
        ]);
    }

    /** What Anee reads when a what-to-plant report is attached. */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = DB::table('as_plant_analyses')->where('userId', $userId)
            ->where('id', $id)->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return null;
        }
        $report = json_decode($r->report, true) ?: [];
        $params = json_decode($r->params, true) ?: [];
        $problems = collect($params['problems'] ?? [])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ');
        $top = $report['topPick'] ?? [];
        $text = "\n\n--- ATTACHED: What-to-plant analysis (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Ground: soil ' . (self::SOILS[$params['soil'] ?? ''] ?? '') . '; water ' . (self::WATERS[$params['water'] ?? ''] ?? '')
            . '; aim ' . (self::AIMS[$params['aim'] ?? ''] ?? '') . ($params['area'] ?? null ? '; area ' . $params['area'] : '') . "\n"
            . 'Troubles considered: ' . ($problems ?: 'none') . "\n"
            . 'Top pick: ' . ($top['crop'] ?? '') . ' (' . ($top['category'] ?? '') . ') — ' . ($top['why'] ?? '') . "\n"
            . 'Ranked: ' . collect($report['recommendations'] ?? [])->map(fn ($x) => ($x['rank'] ?? '') . '. ' . ($x['crop'] ?? '') . ' [' . ($x['category'] ?? '') . '] score ' . ($x['score'] ?? ''))->implode(' | ') . "\n"
            . 'Better avoided: ' . collect($report['avoid'] ?? [])->map(fn ($x) => ($x['crop'] ?? '') . ' — ' . ($x['why'] ?? ''))->implode(' | ') . "\n"
            . 'Summary: ' . ($report['summary'] ?? '') . "\n"
            . 'Stated confidence: ' . ($report['confidence'] ?? '') . '; data gaps: ' . collect($report['dataGaps'] ?? [])->implode('; ')
            . "\n--- END OF ATTACHED ANALYSIS ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)
            ->where('kind', 'what')
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Analysis removed.');
    }

    /** Rename a saved analysis and describe it in your own words. */
    public function meta(\Illuminate\Http\Request $request)
    {
        $title = trim((string) $request->input('title'));
        if ($title === '' || mb_strlen($title) > 191) {
            return $this->json(false, 'Give it a name up to 191 characters.', [], 422);
        }
        $description = trim((string) $request->input('description'));
        $updated = DB::table('as_plant_analyses')
            ->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('id', (int) $request->input('id'))
            ->where('deleteStatus', 1)
            ->update([
                'title' => $title,
                'description' => $description !== '' ? mb_substr($description, 0, 2000) : null,
                'updated_at' => now(),
            ]);
        if (! $updated) {
            return $this->json(false, 'That analysis is not on your shelf.', [], 404);
        }

        return $this->json(true, 'Analysis updated.', ['title' => $title, 'description' => $description ?: null]);
    }

    /* ------------------------------------------------------------ helpers */

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function params(Request $request): array
    {
        return [
            'location' => trim((string) $request->input('location')),
            'startMonth' => (string) $request->input('startMonth'),
            'soil' => (string) $request->input('soil'),
            'water' => (string) $request->input('water'),
            'aim' => (string) $request->input('aim'),
            'area' => trim((string) $request->input('area', '')),
            'notes' => trim((string) $request->input('notes', '')),
            'problems' => array_values((array) $request->input('problems', [])),
        ];
    }

    /** The question, spelled out so the answer is bounded and honest. */
    private function prompt(array $p): string
    {
        $start = \Illuminate\Support\Carbon::parse($p['startMonth'] . '-01')->format('F Y');
        $soil = self::SOILS[$p['soil']] ?? $p['soil'];
        $water = self::WATERS[$p['water']] ?? $p['water'];
        $aim = self::AIMS[$p['aim']] ?? $p['aim'];
        $problems = collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported';
        $area = $p['area'] !== '' ? $p['area'] : 'not stated';
        $notes = $p['notes'] !== '' ? $p['notes'] : 'none';
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';

        return <<<PROMPT
You are an agronomic decision-support analyst for Philippine farming. The farmer asks WHAT to plant on the ground described below, starting around {$start}. Recommend the best-suited crops, ranked, drawn from across the families a Philippine farm weighs: grains, vegetables, root crops, legumes, and fruit/tree crops.

FACTS GIVEN
- Location as the farmer wrote it: {$p['location']}
- Planned start: {$start}
- Soil, as the farmer describes it: {$soil}
- Water: {$water}
- What the harvest is for: {$aim}
- Land area: {$area}
- Field troubles the farmer reports: {$problems}
- The farmer's own notes: {$notes}
{$ensoBlock}
GROUND RULES
- Reason only from established knowledge: PAGASA climatological normals for the region named (wet/dry timing, typhoon seasonality), the soil-water behaviour implied by the described soil and troubles, each candidate crop's real agronomic needs and calendar, and typical Philippine market/home-use patterns for the stated aim. No invented prices, no yield promises.
- Cover the families honestly: at least one strong root crop and one perennial/tree option must be CONSIDERED — recommended if they fit, or placed in avoid with the reason if they do not.
- Where the given facts cannot answer something (soil test values, exact microclimate, market access), name it in dataGaps instead of guessing.
- Be scientific and neutral: no seed brands, no product recommendations, no marketing tone.
- Write every "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"topPick":{"crop":"","category":"","why":"","window":""},"recommendations":[{"rank":1,"crop":"","category":"","score":0,"window":"","why":"","watch":""}],"avoid":[{"crop":"","why":""}],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- recommendations: SIX to EIGHT crops, ranked best-first, each with: category (one of "Grain", "Vegetable", "Root crop", "Legume", "Fruit / tree"), score 0-100 (fit for THIS ground and start month), window (when to plant it, specific — e.g. "mid May – early June"), why (≤ 35 words, grounded in the facts), watch (the one thing to watch for on this ground, ≤ 15 words).
- topPick repeats the rank-1 crop with a fuller why (≤ 60 words) and its window.
- avoid: two to four crops a farmer in this region might otherwise try, each with the plain reason this ground argues against it.
- confidence "low"/"moderate"/"high"; dataGaps at most three; summary ≤ 90 words, plain and warm but factual.
PROMPT;
    }

    /** The model's JSON, taken carefully. Null when it cannot be trusted. */
    private function parseReport(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $from = strpos($text, '{');
        $to = strrpos($text, '}');
        if ($from === false || $to === false || $to <= $from) {
            return null;
        }
        $json = json_decode(substr($text, $from, $to - $from + 1), true);
        if (! is_array($json) || ! isset($json['topPick'], $json['recommendations'], $json['summary'])) {
            return null;
        }
        if (! is_array($json['recommendations']) || count($json['recommendations']) < 3) {
            return null;
        }

        // The persona's shortcodes have no renderer here.
        $sweep = fn ($v) => is_string($v) ? trim(preg_replace('/:[a-z0-9_-]+:/i', '', $v)) : $v;
        array_walk_recursive($json, function (&$v) use ($sweep) {
            $v = $sweep($v);
        });

        return $json;
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
