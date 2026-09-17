<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\WeatherService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Variety research and comparison -- the third of the Quick Tools.
 *
 * When to Plant names the window and What to Plant names the crop; this
 * one names the VARIETY. The farmer says where the field is, what the
 * soil is like, which crop, which varieties they are weighing (or none,
 * and Anee picks the top-yielding ones herself), and ranks what matters
 * most to them -- yield, protection and tolerance, survival, quickness of
 * harvest. Anee then SEARCHES THE WEB, not only her own memory: the newest
 * Philippine registrations and releases, regional trial yields, disease
 * resistance, maturity -- and reads them against the soil, its troubles,
 * the region's climate and the coming weather. Every variety gets a score
 * on each of the four, and the ranking is weighed by the farmer's own
 * order, recomputed here so it honours what they said matters.
 *
 * Same shelf (as_plant_analyses, kind 'variety'), same job walk, same
 * honest ledger as its sisters: the price is said before anything is
 * spent and charged exactly as said. Web search costs the house a flat
 * fee per run on top of the tokens, which is why this one is priced above
 * the other two (App\Support\AiPrices).
 */
class VarietyAnalysisController extends Controller
{
    /** The house's flat price for one analysis, in credits. */
    public const PRICE = AiPrices::DEFAULTS['variety'];

    /** The four things a farmer weighs a variety by, in the order they are offered. */
    public const PRIORITIES = [
        'yield' => ['label' => 'Yield', 'sub' => 'How much it gives per hectare when it goes well', 'icon' => '🌾'],
        'protection' => ['label' => 'Protection & tolerance', 'sub' => 'Resistance to pests and disease, tolerance of stress', 'icon' => '🛡️'],
        'survival' => ['label' => 'Survival', 'sub' => 'Comes through drought, flood, heat, poor soil', 'icon' => '💪'],
        'quickness' => ['label' => 'Quickness of harvest', 'sub' => 'Days to maturity — the sooner, the sooner it sells', 'icon' => '⏱️'],
    ];

    /** How much each place in the farmer's order weighs. */
    public const WEIGHTS = [0.40, 0.30, 0.20, 0.10];

    public const SOILS = WhatToPlantController::SOILS;

    /** The lay of the land — a variety bred for the lowland is not the highland's. */
    public const ELEVATIONS = WhatToPlantController::ELEVATIONS;

    /** The ground's troubles, the sister's list plus the two the soil matters most to. */
    public const PROBLEMS = [
        'drought' => 'Dries out / water runs short mid-season',
        'floods' => 'Floods / standing water after rain',
        'water_source' => 'Limited irrigation water source',
        'salinity' => 'Salty or brackish (near the sea, saline soil)',
        'acidic' => 'Acidic soil (low pH — yellowing, poor growth)',
        'alkaline' => 'Alkaline soil (high pH — white crust, pale leaves)',
        'wind' => 'Strong winds pass through (typhoon corridor)',
        'pests' => 'Pests have been heavy in past seasons',
        'disease' => 'Disease has hit past crops (blast, tungro, wilt, rot…)',
        'weeds' => 'Weed pressure is heavy',
        'drainage' => 'Poor drainage',
        'low_fertility' => 'Low fertility / exhausted soil',
        'slope' => 'Sloping / hilly ground',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    /** The tier wall: analyses ride the Solo Farmer plan and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('reportsAll')) {
            \App\Support\Tier::deny('Variety research comes with the Solo Farmer plan.');
        }
    }

    public function page()
    {
        $this->guardTier();

        return view('variety-analysis.index');
    }

    /** Everything the wizard needs, plus the standing price. */
    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'crops' => collect(CropCatalog::visible())->map(fn ($c, $key) => [
                'key' => $key,
                'label' => $c['label'],
                'icon' => $c['icon'],
                'group' => $c['group'],
                'maturity' => $c['maturity'] ?? null,
                'perennial' => CropCatalog::isPerennial($key),
            ])->values(),
            'soils' => self::SOILS,
            'elevations' => self::ELEVATIONS,
            'seasons' => \App\Support\Region::seasons(),
            // The farmer's own country: the field is there unless they say otherwise.
            'country' => \App\Support\Region::code(),
            'years' => range((int) now('Asia/Manila')->format('Y'), (int) now('Asia/Manila')->format('Y') + 2),
            'problems' => self::PROBLEMS,
            'priorities' => self::PRIORITIES,
            'weights' => self::WEIGHTS,
            'quote' => $canUse ? (float) AiPrices::of('variety') : null,
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

    /** Run the analysis — the sisters' job walk, the question swapped. */
    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'The analysis needs the AI Technician (Boss or Lifetime plan).', [], 403);
        }

        // The seasons on offer are the FIELD's country's, not the farmer's.
        $fieldCountry = \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code();
        $seasonKeys = array_keys(\App\Support\Region::as($fieldCountry, fn () => \App\Support\Region::seasons()));
        $thisYear = (int) now('Asia/Manila')->format('Y');
        $v = Validator::make($request->all(), [
            'location' => 'required|string|max:160',
            'country' => 'nullable|string|size:2',
            'year' => 'required|integer|min:' . $thisYear . '|max:' . ($thisYear + 2),
            'season' => 'required|in:' . implode(',', $seasonKeys),
            'soil' => 'required|in:' . implode(',', array_keys(self::SOILS)),
            'elevation' => 'nullable|in:' . implode(',', array_keys(self::ELEVATIONS)),
            'crop' => 'required|in:' . implode(',', array_keys(CropCatalog::CROPS)),
            'varieties' => 'nullable|array|max:8',
            'varieties.*' => 'string|max:60',
            'priorities' => 'required|array|size:4',
            'priorities.*' => 'string|distinct|in:' . implode(',', array_keys(self::PRIORITIES)),
            'problems' => 'nullable|array',
            'problems.*' => 'string|in:' . implode(',', array_keys(self::PROBLEMS)),
            'notes' => 'nullable|string|max:400',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }

        $price = AiPrices::of('variety');
        $balance = $this->credits->balance($payer->id);
        if ($balance < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this analysis and have '
                . number_format((int) floor($balance)) . '.',
                ['outOfCredits' => true], 402);
        }

        // One in flight at a time — a double press must not buy two.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'variety')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        $p = $this->params($request);
        // A field in another country than the farmer's: Anee is told so in
        // her own instructions, or she declines for being "set up" at home.
        $settings = $settings->forField($p['country']);
        $crop = CropCatalog::CROPS[$p['crop']];
        $title = 'Varieties · ' . $crop['label'] . ' · ' . \App\Support\Region::seasonTitle($p['season'], (int) $p['year'], $p['country']) . ' · ' . $p['location'];
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'kind' => 'variety',
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

        /* Should PHP itself be cut off mid-run (a wall-clock limit, a fatal),
         * the row must not stay "pending" -- that blocks every next run for
         * ten minutes and the page polls a job that will never answer. */
        register_shutdown_function(function () use ($id) {
            try {
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed',
                    'error' => 'The research took too long and was stopped. Nothing was charged — please try again.',
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Nothing more to do at shutdown.
            }
        });

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $id,
            ]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $prompt, $p);
            exit;
        }

        // Inline (no FPM): the research step alone may take a few minutes.
        @set_time_limit(900);
        $this->runJob($id, (int) $payer->id, $settings, $prompt, $p);

        return $this->jobState($id);
    }

    /** The model call — with the web open — and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt, array $p): void
    {
        /* The heartbeat: before every call to the model the row says which
         * phase it is in and touches updated_at, so the page can show a
         * live line and jobState can tell a killed job from a slow one. */
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                'report' => json_encode(['phase' => $phase, 'try' => $try]),
                'updated_at' => now(),
            ]);
        };
        try {
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p), $prompt, 6000, fn (string $t) => $this->parseReport($t), $beat);
            $report = $result['data'];
            if ($report === null) {
                \Illuminate\Support\Facades\Log::warning('variety-analysis: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException($result['error'] ?? 'The analysis came back unreadable. Nothing was charged — please try again.');
            }

            // The ranking honours the farmer's own order, whatever the model
            // felt: overall = the four scores weighed by that order.
            $report = $this->weigh($report, $p['priorities']);
            $report['webSources'] = $result['sources'];
            $report['searched'] = (bool) $result['searched'];

            $charged = (float) AiPrices::of('variety');
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $crop = CropCatalog::CROPS[$p['crop']] ?? ['label' => 'Crop'];
            $note = AiUsage::record('variety', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('Variety research — ' . $crop['label'] . ', ' . $p['location'] . $note, 0, 250));

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

    /**
     * Overall = the four scores weighed by the farmer's order (40/30/20/10),
     * and the list sorted by it; the top pick follows the list.
     */
    private function weigh(array $report, array $order): array
    {
        $weights = [];
        foreach ($order as $i => $key) {
            $weights[$key] = self::WEIGHTS[$i] ?? 0;
        }
        $rows = [];
        foreach ((array) ($report['ranking'] ?? []) as $r) {
            $scores = (array) ($r['scores'] ?? []);
            $overall = 0;
            foreach (array_keys(self::PRIORITIES) as $k) {
                $overall += max(0, min(100, (float) ($scores[$k] ?? 0))) * ($weights[$k] ?? 0);
            }
            $r['overall'] = (int) round($overall);
            // Hybrid or inbred: the two are bought, priced and grown
            // differently, so they are ranked apart (the owner's ask,
            // 2026-09-16). Anything not plainly a hybrid is an inbred/OPV.
            $r['type'] = mb_strtolower(trim((string) ($r['type'] ?? ''))) === 'hybrid' ? 'hybrid' : 'inbred';
            $rows[] = $r;
        }
        usort($rows, fn ($a, $b) => $b['overall'] <=> $a['overall']);
        // Ranked within each type, so the best hybrid is #1 among hybrids
        // and the best inbred #1 among inbreds; the list itself stays in
        // one overall order.
        $seen = ['hybrid' => 0, 'inbred' => 0];
        foreach ($rows as $i => &$r) {
            $r['rank'] = ++$seen[$r['type']];
            $r['overallRank'] = $i + 1;
        }
        unset($r);
        $report['ranking'] = $rows;
        $report['bestHybrid'] = collect($rows)->firstWhere('type', 'hybrid')['variety'] ?? null;
        $report['bestInbred'] = collect($rows)->firstWhere('type', 'inbred')['variety'] ?? null;
        if ($rows) {
            // The model's own "why" is kept when it picked the same one the
            // weighing did; otherwise the winner's fit notes speak for it.
            $top = $rows[0];
            $said = (array) ($report['topPick'] ?? []);
            $same = mb_strtolower(trim((string) ($said['variety'] ?? ''))) === mb_strtolower(trim((string) ($top['variety'] ?? '')));
            $report['topPick'] = [
                'variety' => (string) ($top['variety'] ?? ''),
                'by' => (string) ($top['by'] ?? ''),
                'why' => (string) ($same && ! empty($said['why']) ? $said['why'] : ($top['fitNotes'] ?? ($said['why'] ?? ''))),
            ];
        }
        $report['weights'] = $weights;

        return $report;
    }

    /**
     * A pending row is dead when its heart has not beaten for longer than
     * any one call to the model may take (the document timeout plus a
     * generous minute), or when it is simply too old. Killed processes
     * write nothing, so this is the only way to tell — and the only way a
     * job that finished late is never mistaken for one that hung.
     */
    private function dead(object $r): bool
    {
        $beatAt = \Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at);
        $quiet = \App\Services\AiClient::TIMEOUT_DOCUMENT + 60;

        return $beatAt->lt(now()->subSeconds($quiet))
            || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(20));
    }

    /** Where a job stands — polled by the page until ready or failed. */
    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'variety')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            if ($this->dead($r)) {
                $why = \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(20))
                    ? 'The research took too long and was stopped. Nothing was charged — please try again.'
                    : 'The research was interrupted mid-way (the server restarted under it). Nothing was charged — please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed', 'deleteStatus' => 0,
                    'error' => $why,
                    'updated_at' => now(),
                ]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', [
                'pending' => true, 'id' => (int) $r->id, 'status' => 'pending',
                'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1),
                'since' => (int) max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($r->created_at), true)),
                // Seconds since the job last spoke: the page's own hang check.
                'beatAgo' => (int) max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at), true)),
            ]);
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
            ->where('kind', 'variety')
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
            ->where('id', $id)->where('kind', 'variety')
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

    /** What Anee reads when a variety analysis is attached to a chat. */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = DB::table('as_plant_analyses')->where('userId', $userId)
            ->where('id', $id)->where('kind', 'variety')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return null;
        }
        $report = json_decode($r->report, true) ?: [];
        $params = json_decode($r->params, true) ?: [];
        $order = collect($params['priorities'] ?? [])->map(fn ($k) => self::PRIORITIES[$k]['label'] ?? $k)->implode(' > ');
        $top = $report['topPick'] ?? [];
        $text = "\n\n--- ATTACHED: Variety research (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Ground: soil ' . (self::SOILS[$params['soil'] ?? ''] ?? '')
            . (! empty($params['elevation']) ? '; the land ' . (self::ELEVATIONS[$params['elevation']] ?? $params['elevation']) : '')
            . '; troubles: ' . (collect($params['problems'] ?? [])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none') . "\n"
            . (! empty($params['season']) ? 'Planned for: ' . \App\Support\Region::seasonTitle($params['season'], (int) ($params['year'] ?? now('Asia/Manila')->year), $params['country'] ?? null) . "\n" : '')
            . 'Priorities, most important first: ' . $order . "\n"
            . 'Top pick: ' . ($top['variety'] ?? '') . ($top['by'] ?? null ? ' (' . $top['by'] . ')' : '') . ' — ' . ($top['why'] ?? '') . "\n"
            . 'Ranked: ' . collect($report['ranking'] ?? [])->map(fn ($x) => ($x['rank'] ?? '') . '. ' . ($x['variety'] ?? '')
                . ' [overall ' . ($x['overall'] ?? '') . '; yield ' . ($x['scores']['yield'] ?? '') . ', protection ' . ($x['scores']['protection'] ?? '')
                . ', survival ' . ($x['scores']['survival'] ?? '') . ', quickness ' . ($x['scores']['quickness'] ?? '') . '; '
                . ($x['maturityDays'] ?? '') . ']')->implode(' | ') . "\n"
            . 'Newest PH releases noted: ' . collect($report['newest'] ?? [])->map(fn ($x) => ($x['variety'] ?? '') . ' (' . ($x['year'] ?? '') . ')')->implode(', ') . "\n"
            . 'Summary: ' . ($report['summary'] ?? '') . "\n"
            . 'Stated confidence: ' . ($report['confidence'] ?? '') . '; data gaps: ' . collect($report['dataGaps'] ?? [])->implode('; ')
            . "\n--- END OF ATTACHED ANALYSIS ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)
            ->where('kind', 'variety')
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Analysis removed.');
    }

    /** Rename a saved analysis and describe it in your own words. */
    public function meta(Request $request)
    {
        $title = trim((string) $request->input('title'));
        if ($title === '' || mb_strlen($title) > 191) {
            return $this->json(false, 'Give it a name up to 191 characters.', [], 422);
        }
        $description = trim((string) $request->input('description'));
        $updated = DB::table('as_plant_analyses')
            ->where('userId', Auth::id())
            ->where('kind', 'variety')
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
        $varieties = collect((array) $request->input('varieties', []))
            ->map(fn ($v) => trim((string) $v))->filter()->unique()->values()->take(8)->all();

        return [
            'location' => trim((string) $request->input('location')),
            'country' => \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code(),
            'year' => (int) $request->input('year'),
            'season' => (string) $request->input('season'),
            'soil' => (string) $request->input('soil'),
            'elevation' => array_key_exists((string) $request->input('elevation'), self::ELEVATIONS) ? (string) $request->input('elevation') : null,
            'crop' => (string) $request->input('crop'),
            'varieties' => $varieties,
            'priorities' => array_values((array) $request->input('priorities')),
            'problems' => array_values((array) $request->input('problems', [])),
            'notes' => trim((string) $request->input('notes', '')),
        ];
    }

    /** The coming days at the field, read from the forecast, in a line or two. */
    private function forecastLines(string $place): string
    {
        try {
            $fc = app(WeatherService::class)->forecastForPlace($place, 7);
        } catch (\Throwable $e) {
            $fc = null;
        }
        if (! $fc || empty($fc['days'])) {
            return 'not available — search the seasonal outlook for the region instead';
        }
        $days = collect($fc['days'])->map(fn ($d) => $d['label'] . ': ' . $d['text']
            . (isset($d['max'], $d['min']) ? ', ' . $d['min'] . '–' . $d['max'] . '°C' : '')
            . (isset($d['pop']) ? ', rain ' . $d['pop'] . '%' : ''))->implode('; ');

        return 'the next 7 days at ' . $fc['place'] . ' (Open-Meteo): ' . $days;
    }

    /**
     * The research brief: what to look up, with the web open, before the
     * document is written. Prose, not JSON -- asked for JSON the model
     * does not search (see AiClient::researchThenJson).
     */
    private function researchPrompt(array $p): string
    {
        $crop = CropCatalog::CROPS[$p['crop']];
        $year = now('Asia/Manila')->year;
        $named = $p['varieties'] ? implode(', ', $p['varieties']) : 'none named — find the top-yielding released ones for these conditions';
        $soil = self::SOILS[$p['soil']] ?? $p['soil'];
        $problems = collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported';

        // The FIELD's country, which may not be the farmer's: its agencies,
        // its seed houses, its seasons; the language stays the farmer's.
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $countryName = \App\Support\Region::name($fc);
        $seeds = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('seeds'));
        $met = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('met'));
        $sources = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('sources'));
        $cropLabel = CropCatalog::label($p['crop']);
        $regionLine = \App\Support\Region::promptBlock($fc, \App\Support\Region::code());
        $plan = ! empty($p['season']) ? \App\Support\Region::as($fc, fn () => \App\Support\Region::seasonWords($p['season'], (int) ($p['year'] ?: $year))) : 'not stated';
        $land = self::ELEVATIONS[$p['elevation'] ?? ''] ?? 'not stated';
        $hybridHouses = $fieldPH
            ? '(for rice: SL Agritech / SL-8H and its line, Bayer Arize, Syngenta, Corteva-Pioneer, Bioseed, Longping High-Tech; for corn: Pioneer, Bayer-Dekalb, Syngenta NK, Bioseed; for vegetables: East-West Seed, Allied Botanical, Known-You, Condor, Ramgo — whichever apply to this crop), and the NSIC-registered public hybrids (e.g. Mestiso / Mestizo lines for rice)'
            : '(the top seed companies and public breeding programs actually selling this crop in ' . $countryName . ' — name them from what you find, never from memory)';

        return <<<PROMPT
You are a research assistant for agronomy in {$countryName} with web search. SEARCH THE WEB NOW and write research notes for a variety comparison. Do not answer from memory alone; every finding must come from a page you read, with the source name and year beside it. {$regionLine}

THE CASE
- Crop: {$cropLabel}
- Field: {$p['location']}; soil: {$soil}; the land: {$land}; troubles: {$problems}
- When the farmer plans to plant: {$plan}
- Varieties the farmer named: {$named}

FIND, IN THIS ORDER
1. The newest {$cropLabel} varieties registered or released in {$countryName} ({$seeds}) in {$year}, {$year}-1 and {$year}-2: name, breeder or company, year, what it was bred for.
2. For each of the farmer's named varieties AND for 4–6 top-yielding released INBRED or open-pollinated varieties suited to this soil and these troubles: documented yield (trial or published figure, with unit and source), days to maturity, pest and disease resistance ratings, stress tolerance (drought, submergence, salinity, heat, acidity, alkalinity, lodging), grain or fruit quality notes, and any regional trial results in or near the farmer's region.
2b. The same for 3–5 HYBRID varieties of {$cropLabel} sold in {$countryName} by the top seed companies {$hybridHouses}: yield, maturity, resistance, seed cost and availability per hectare where published, and whether the seed must be bought fresh each season.
3. The seasonal climate outlook from {$met} for the farmer's region for the PLANTING SEASON named above (rainfall, ENSO state, severe-weather expectation across the months the crop will stand in the field).
4. Anything published about which of these varieties do well or poorly on this soil type, at this elevation, in this season and with these troubles — which are bred for the wet season and which for the dry, which stand the highland's cool nights or the lowland's heat.

Prefer {$sources}. Where a variety cannot be found online, say so plainly. Write plain prose notes under the four headings, at most 900 words, no JSON, no markdown tables.
PROMPT;
    }

    /** The question, spelled out so the answer is bounded, searched and honest. */
    private function prompt(array $p): string
    {
        $crop = CropCatalog::CROPS[$p['crop']];
        $soil = self::SOILS[$p['soil']] ?? $p['soil'];
        $problems = collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported';
        $notes = $p['notes'] !== '' ? $p['notes'] : 'none';
        $order = [];
        foreach ($p['priorities'] as $i => $k) {
            $order[] = ($i + 1) . '. ' . (self::PRIORITIES[$k]['label'] ?? $k) . ' (weight ' . (int) round((self::WEIGHTS[$i] ?? 0) * 100) . '%)';
        }
        $orderText = implode('; ', $order);
        $given = $p['varieties']
            ? 'The farmer is weighing these: ' . implode(', ', $p['varieties']) . '. Assess EVERY one of them (in givenVarieties), and put those that fit in the ranking; add the top-yielding released varieties for these conditions that the farmer did not name — inbred/open-pollinated AND hybrid — so the ranking has 6–9 in all.'
            : 'The farmer has not named any. Choose them yourself: 4–5 top-yielding released INBRED / open-pollinated varieties AND 3–4 HYBRID varieties from the top seed companies selling in ' . \App\Support\Region::name($p['country'] ?? null) . ', for these conditions — 7–9 in all, every one with its type filled in.';
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';
        $forecast = $this->forecastLines($p['location']);
        $year = now('Asia/Manila')->year;

        // The FIELD's country, which may not be the farmer's.
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $countryName = \App\Support\Region::name($fc);
        $regionBlock = \App\Support\Region::promptBlock($fc, \App\Support\Region::code());
        $cropLabel = CropCatalog::label($p['crop']);
        $climateHint = $fieldPH ? 'wet/dry timing, typhoon seasonality' : 'frost dates, growing-season length, rainfall and heat timing, the severe-weather season';
        $plan = ! empty($p['season']) ? \App\Support\Region::as($fc, fn () => \App\Support\Region::seasonWords($p['season'], (int) ($p['year'] ?: $year))) : 'not stated';
        $land = self::ELEVATIONS[$p['elevation'] ?? ''] ?? 'not stated';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$countryName}, with web search available. The farmer asks WHICH VARIETY of {$cropLabel} to plant on the ground described below. Research and compare varieties, score each on four criteria, and rank them by the farmer's own priorities.

FACTS GIVEN
- {$regionBlock}
- Location as the farmer wrote it: {$p['location']}
- Crop: {$cropLabel}
- When the farmer plans to plant: {$plan}
- The lay of the land: {$land}
- Soil, as the farmer describes it: {$soil}
- Field troubles the farmer reports: {$problems}
- The farmer's priorities, most important first: {$orderText}
- Varieties: {$given}
- The farmer's own notes: {$notes}
- Weather now: {$forecast}
{$ensoBlock}
WHAT TO READ FROM
Research notes gathered from the web just now follow at the end of this brief (the newest registrations and releases of {$cropLabel} in {$countryName} in {$year} and the two years before, trial yields, resistance ratings, days to maturity, the seasonal outlook). Rely on them first, over memory; note the year of each finding. Where a claim is neither in the notes nor established agronomy, say so in dataGaps rather than inventing it.

GROUND RULES
- Reason from what you found and from established agronomy: the soil-water behaviour implied by the described soil and troubles, the region's climate ({$climateHint}) and the outlook above, each variety's documented traits. No invented yields; a yield figure must be a trial or published figure with its source.
- THE SEASON AND THE LAND decide as much as the soil: a variety bred for the wet season is not the dry season's, an early one dodges the season's hazard (the typhoon peak, the late heat, the first frost) that a late one meets at flowering or harvest, and the highland's cool nights and the lowland's heat each rule varieties out. Score for the planting season and the elevation named, and say so in the why where they decided it.
- Score each variety 0–100 on each criterion FOR THESE CONDITIONS: yield (documented yield potential here), protection (pest/disease resistance and stress tolerance relevant to the reported troubles), survival (establishment and hardiness through this ground's stresses), quickness (earliness — fewer days to maturity scores higher).
- Be scientific and neutral: name the breeder or seed company as a fact, never as a recommendation to buy; no marketing tone.
- Write every "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"headline":"","topPick":{"variety":"","by":"","why":""},"ranking":[{"rank":1,"variety":"","type":"inbred","by":"","released":"","maturityDays":"","yieldPotential":"","scores":{"yield":0,"protection":0,"survival":0,"quickness":0},"strengths":[""],"weaknesses":[""],"fitNotes":"","sources":[""]}],"givenVarieties":[{"variety":"","verdict":""}],"newest":[{"variety":"","by":"","year":"","note":""}],"conditions":{"soil":"","weather":"","risks":[""]},"management":[""],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- headline: one line, ≤ 14 words, the answer in a breath.
- ranking: SIX to NINE varieties, each with: type ("hybrid" for F1 hybrids whose seed is bought fresh each season, "inbred" for inbred, open-pollinated and public registered varieties — never leave it empty), by (breeder / seed company / institution), released (year or "n/a"), maturityDays (e.g. "110–115 DAS" or "n/a"), yieldPotential (a published figure with unit, e.g. "6.5–8.0 t/ha (PhilRice trials)", or "not published"), scores (all four, 0–100), strengths (2–3 short items), weaknesses (1–3 short items), fitNotes (≤ 40 words on THIS ground and climate), sources (1–3 short source names, e.g. "PhilRice Rc 222 factsheet 2023").
- givenVarieties: one entry for EACH variety the farmer named (empty list if none), verdict ≤ 35 words — including when it is unsuitable, unavailable, or could not be found (say so plainly).
- newest: two to five of the most recent relevant releases in {$countryName} found online (year required), each with a ≤ 20-word note on why it matters here.
- conditions: soil (≤ 45 words on what this soil and its troubles demand of a variety), weather (≤ 45 words on the outlook and what it favours), risks (2–4 short items).
- management: three to five short, practical lines specific to the top pick on this ground.
- topPick: the variety you judge best for the farmer's priority order, with why ≤ 60 words.
- confidence "low"/"moderate"/"high"; dataGaps at most four; summary ≤ 90 words, plain and warm but factual.
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
        if (! is_array($json) || ! isset($json['topPick'], $json['ranking'], $json['summary'])) {
            return null;
        }
        if (! is_array($json['ranking']) || count($json['ranking']) < 3) {
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
