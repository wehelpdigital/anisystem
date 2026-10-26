<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\WeatherService;
use App\Support\AiPrices;
use App\Support\AiPricing;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\FieldProfile;
use App\Support\Tier;
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
 * The variety records (2026-10-14): before Anee writes, she is handed the
 * registered varieties of the crop that best fit the field, from anee.io's own
 * records (App\Support\VarietyBook), with their trial days, yields and
 * ratings, and since 2026-10-18 the registration trials of each in short
 * (App\Support\VarietyRegistry: by season, the advantage over the checks,
 * the tolerances and limits), so the research no longer looks those up;
 * and every variety in her answer is linked back to its
 * record, with a "by the numbers" shortlist beside her ranking.
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

    /** The most varieties one run can name: the one price the page shows is the engine's quote at this size. */
    private const PRICE_VARIETIES = 8;

    /** The four things a farmer weighs a variety by, in the order they are offered. */
    public const PRIORITIES = [
        'yield' => ['label' => 'Yield', 'sub' => 'How much it gives per hectare when it goes well', 'icon' => '🌾'],
        'protection' => ['label' => 'Protection & tolerance', 'sub' => 'Resistance to pests and disease, tolerance of stress', 'icon' => '🛡️'],
        'survival' => ['label' => 'Survival', 'sub' => 'Comes through drought, flood, heat, poor soil', 'icon' => '💪'],
        'quickness' => ['label' => 'Quickness of harvest', 'sub' => 'Days to maturity. The sooner it is ready, the sooner it sells', 'icon' => '⏱️'],
    ];

    /** How much each place in the farmer's order weighs. */
    public const WEIGHTS = [0.40, 0.30, 0.20, 0.10];

    public const SOILS = WhatToPlantController::SOILS;

    /** The lay of the land — a variety bred for the lowland is not the highland's. */
    public const ELEVATIONS = WhatToPlantController::ELEVATIONS;

    /** The ground's troubles, the sister's list plus the two the soil matters most to. */
    public const PROBLEMS = [
        'drought' => 'Dries out / water runs short in the middle of the season',
        'floods' => 'Floods / standing water after rain',
        'water_source' => 'Limited irrigation water source',
        'salinity' => 'Salty or brackish (near the sea, saline soil)',
        'acidic' => 'Acidic soil (low pH: yellowing, poor growth)',
        'alkaline' => 'Alkaline soil (high pH: white crust, pale leaves)',
        // The rest of the soil's chemistry (2026-09-29), as every analysis asks it.
        'sodic' => 'Sodic soil (high sodium: crusts, seals, water sits)',
        'acid_sulfate' => 'Acid sulfate soil (very sour: yellow mottles, rusty water, old mangrove or swamp)',
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

    /** The tier wall: the analyses are Anee's, and Anee comes with Libre + Anee and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('aiAnalyses')) {
            \App\Support\Tier::farmDenyFor('aiAnalyses', 'Variety Ranking comes with {plan}, and with every plan above it.');
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
            // The months to plant in (2026-10-16, the owner: a month is more direct than a season).
            'months' => self::plantingMonths(),
            'problems' => self::PROBLEMS,
            'priorities' => self::PRIORITIES,
            'weights' => self::WEIGHTS,
            // One price for any number of varieties named (App\Support\AiPricing): the engine's quote at the most a run can name.
            'quote' => $canUse ? (float) AiPricing::credits('variety', ['varieties' => self::PRICE_VARIETIES]) : null,
            'aneeFace' => $settings->faceUrl(),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null
                : ($settings->isUsable()
                    ? Tier::aneeNeeds($payer)
                    : 'Anee is not switched on yet. Please check back soon.'),
        ]]);
    }

    /** Run the analysis — the sisters' job walk, the question swapped. */
    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi()
                ? 'Anee is not switched on yet. Please check back soon.'
                : Tier::aneeNeeds($payer, 'The analysis'), [], 403);
        }

        // The seasons on offer are the FIELD's country's, not the farmer's.
        $fieldCountry = \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code();
        $seasonKeys = array_keys(\App\Support\Region::as($fieldCountry, fn () => \App\Support\Region::seasons()));
        $thisYear = (int) now('Asia/Manila')->format('Y');
        // A planting month names its season and the season's year.
        $month = (string) $request->input('month', '');
        if (in_array($month, array_column(self::plantingMonths(), 'key'), true)) {
            [$season, $year] = self::seasonOfMonth($month, $seasonKeys);
            $request->merge(['season' => $season, 'year' => $year]);
        } elseif ($month !== '') {
            return $this->json(false, 'Pick the month you will plant again: that one is not on the list any more.', [], 422);
        }
        $v = Validator::make($request->all(), [
            'location' => 'required|string|max:160',
            'country' => 'nullable|string|size:2',
            'year' => 'required|integer|min:' . ($thisYear - 1) . '|max:' . ($thisYear + 2),
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
            // The field in detail (App\Support\FieldProfile), optional.
            'fieldProfile' => 'nullable|array',
            'fieldProfileFrom' => 'nullable|integer',
            // The lot it is for (Global Lots, App\Support\FarmLots): its name rides on the report.
            'lotId' => 'nullable|integer',
            'lotName' => 'nullable|string|max:120',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), ['errors' => $v->errors()], 422);
        }

        /* The price the page showed is the price charged: one price for any
           number of varieties. A page that sends the quote for its own count
           must match it. */
        $named = count((array) $request->input('varieties', []));
        $quote = AiPricing::quote('variety', ['varieties' => $named]);
        $price = AiPricing::agreed($request, $quote, AiPricing::credits('variety', ['varieties' => self::PRICE_VARIETIES]));
        if ($price === null) {
            return $this->json(false, 'This research now costs ' . $quote['credits'] . ' credits. Please run it again.', ['quote' => $quote['credits']], 409);
        }
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
        $title = 'Varieties · ' . $crop['label'] . ' · ' . (! empty($p['month']) ? self::monthWords($p['month']) . ' planting' : \App\Support\Region::seasonTitle($p['season'], (int) $p['year'], $p['country'])) . ' · '
            . ($p['lotName'] ? $p['lotName'] . ', ' : '') . $p['location'];
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
        // The field in detail is kept for the next analysis, named after the place. A lot's
        // answers are already kept, in the lot: saving them again would add a second lot.
        if (! $p['lotId']) {
            FieldProfile::keepFor((int) $payer->id, (array) ($p['fieldProfile'] ?? []), $p['location'], $p['crop'], $request->input('fieldProfileFrom'));
        }

        /* Should PHP itself be cut off mid-run (a wall-clock limit, a fatal),
         * the row must not stay "pending" -- that blocks every next run for
         * ten minutes and the page polls a job that will never answer. */
        register_shutdown_function(function () use ($id) {
            try {
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed',
                    'error' => 'The research took too long and was stopped. Nothing was charged. Please try again.',
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
            $this->runJob($id, (int) $payer->id, $settings, $prompt, $p, $price);
            exit;
        }

        // Inline (no FPM): the research step alone may take a few minutes.
        @set_time_limit(900);
        $this->runJob($id, (int) $payer->id, $settings, $prompt, $p, $price);

        return $this->jobState($id);
    }

    /** The model call — with the web open — and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt, array $p, int $price): void
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
            // Ten ranked, each with the derbies' and the farmers' own harvests (2026-10-17, the owner): a longer answer, longer notes.
            // The brief is the case's (crop, place, season, soil, troubles, the varieties named): kept for the next farmer of the same case (2026-10-18).
            $shelf = ['kind' => 'variety', 'scope' => mb_substr(implode(' · ', array_filter([CropCatalog::label($p['crop']), $p['location'], $p['season'], implode(', ', $p['varieties'])])), 0, 250)];
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p), $prompt, 12000, fn (string $t) => $this->parseReport($t), $beat, 4500, $shelf);
            $report = $result['data'];
            if ($report === null) {
                \Illuminate\Support\Facades\Log::warning('variety-analysis: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException($result['error'] ?? 'The analysis could not be read. Nothing was charged. Please try again.');
            }

            // The ranking honours the farmer's own order, whatever the model
            // felt: overall = the four scores weighed by that order.
            // Learn once: the named varieties the research looked up are kept for every module after.
            if ($unknown = self::toLookUp($p)) {
                \App\Support\VarietyLearned::harvest((string) $p['crop'], $unknown, (string) ($result['researchText'] ?? ''),
                    ['via' => 'variety', 'userId' => $payerId, 'searched' => (bool) ($result['searched'] ?? false), 'sources' => (array) ($result['sources'] ?? [])]);
            }
            foreach ((array) ($p['varieties'] ?? []) as $v) {
                \App\Support\VarietyBook::used(\App\Support\VarietyBook::find((string) $p['crop'], (string) $v));
            }
            $report = $this->weigh($report, $p['priorities']);
            $report = self::markNewest($report);
            $report['webSources'] = $result['sources'];
            $report['searched'] = (bool) $result['searched'];

            // The price agreed when the run began.
            $charged = (float) $price;
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $crop = CropCatalog::CROPS[$p['crop']] ?? ['label' => 'Crop'];
            $note = AiUsage::record('variety', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('Variety Ranking: ' . $crop['label'] . ', ' . $p['location'] . $note, 0, 250));
            AiPricing::audit('variety', $result, $charged, ['varieties' => count((array) ($p['varieties'] ?? []))], $settings);

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
                    ? 'The research took too long and was stopped. Nothing was charged. Please try again.'
                    : 'The research was stopped halfway because the server restarted. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed', 'deleteStatus' => 0,
                    'error' => $why,
                    'updated_at' => now(),
                ]);

                return $this->json(false, $why, ['status' => 'failed'], 422);
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

            return $this->json(false, $r->error ?: 'The analysis failed and nothing was charged. Please try again.', ['status' => 'failed'], 422);
        }

        $params = json_decode($r->params, true) ?: [];

        return $this->json(true, 'Analysis ready.', [
            'status' => 'ready',
            'savedId' => (int) $r->id,
            'report' => self::withRecords(json_decode($r->report, true) ?: [], $params),
            'params' => $params,
            'charged' => (float) $r->credits,
            'balance' => round($this->credits->balance($this->payer()->id), 2),
        ]);
    }

    public function list()
    {
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'variety')
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            ->get(['id', 'title', 'description', 'tags', 'credits', 'created_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'tags' => array_values(array_filter((array) (json_decode((string) ($r->tags ?? ''), true) ?: []), 'is_string')),
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

        $params = json_decode($r->params, true) ?: [];

        return $this->json(true, 'ok', [
            'id' => $r->id,
            'title' => $r->title,
            'params' => $params,
            'report' => self::withRecords(json_decode($r->report, true) ?: [], $params),
            'credits' => (float) $r->credits,
        ]);
    }

    /**
     * The varieties on record for a crop, for the wizard (2026-10-14): every
     * name to type from (registered names and the names sold under), and the
     * ones that best fit the field said so far, to add with a tap.
     */
    public function book(Request $request)
    {
        $crop = (string) $request->query('crop', '');
        $rows = self::onRecord(['country' => $request->query('country')]) ? \App\Support\VarietyBook::rows($crop) : [];
        if (! $rows) {
            return $this->json(true, 'ok', ['count' => 0, 'names' => [], 'picks' => []]);
        }
        $case = self::bookCase([
            'crop' => $crop,
            'location' => (string) $request->query('location', ''),
            'elevation' => (string) $request->query('elevation', ''),
            'season' => (string) $request->query('season', ''),
            'problems' => array_values(array_filter((array) $request->query('problems', []), 'is_string')),
        ]);
        // The search sheet (2026-10-15, the owner): every record of the crop, compact, to search and pick from.
        if ($request->boolean('all')) {
            $all = array_map(function ($r) {
                $b = \App\Support\VarietyBook::brief($r);

                return [$b['name'], $b['alias'], $b['type'], $b['days'], $b['daysUnit'], $b['yield'], $b['yieldUnit'] ?? null, $b['where'] ?? null, $b['year'],
                    implode(' ', array_slice($r['aliases'], 0, 4))];
            }, $rows);
            usort($all, fn ($a, $b) => ($b[8] ?? 0) <=> ($a[8] ?? 0) ?: strnatcasecmp($a[0], $b[0]));

            return $this->json(true, 'ok', ['count' => count($rows), 'all' => $all]);
        }
        $names = [];
        foreach ($rows as $r) {
            $names[] = $r['name'];
            foreach (array_slice($r['aliases'], 0, 2) as $a) {
                $names[] = $a;
            }
        }

        return $this->json(true, 'ok', [
            'count' => count($rows),
            'finder' => \App\Support\VarietyBook::finderUrl($crop),
            'names' => array_values(array_unique($names)),
            'picks' => array_map(fn ($r) => \App\Support\VarietyBook::brief($r), \App\Support\VarietyBook::candidates($crop, $case, 8)),
        ]);
    }

    /** The records are the Philippines' registry: they speak only for a field there. */
    public static function onRecord(array $p): bool
    {
        $c = \App\Support\Region::valid($p['country'] ?? null) ?: \App\Support\Region::code();

        return $c === \App\Support\Region::HOME;
    }

    /** The next twenty four months, from this one: key Y-m, label "October 2026". */
    public static function plantingMonths(): array
    {
        $out = [];
        $c = now('Asia/Manila')->startOfMonth();
        for ($i = 0; $i < 24; $i++) {
            $out[] = ['key' => $c->format('Y-m'), 'label' => $c->format('F Y'), 'short' => $c->format('M Y')];
            $c = $c->addMonth();
        }

        return $out;
    }

    /** "2026-10" as "October 2026". */
    public static function monthWords(string $ym): string
    {
        try {
            return \Illuminate\Support\Carbon::createFromFormat('!Y-m', $ym, 'Asia/Manila')->format('F Y');
        } catch (\Throwable $e) {
            return $ym;
        }
    }

    /**
     * The season a planting month falls in, and the season's year. In the
     * Philippines the wet season is planted May to October, the dry season
     * November to February (it carries the year it starts in, so January
     * 2027 is the dry season 2026 to 2027), the third crop March and April.
     * Elsewhere the northern seasons; winter carries the December's year.
     * The page has the same rule (seasonOf).
     */
    public static function seasonOfMonth(string $ym, array $keys): array
    {
        [$y, $m] = array_map('intval', explode('-', $ym) + [0, 0]);
        $has = fn (string $k) => in_array($k, $keys, true);
        if ($has('wet') && $has('dry')) {
            $out = match (true) {
                $m >= 5 && $m <= 10 => ['wet', $y],
                $m >= 11 => ['dry', $y],
                $m <= 2 => ['dry', $y - 1],
                default => $has('third') ? ['third', $y] : ['dry', $y - 1],
            };
        } else {
            $out = match (true) {
                $m >= 3 && $m <= 5 => ['spring', $y],
                $m >= 6 && $m <= 8 => ['summer', $y],
                $m >= 9 && $m <= 11 => ['autumn', $y],
                default => ['winter', $m === 12 ? $y : $y - 1],
            };
        }
        if (! $has($out[0]) && $keys) {
            $out[0] = $keys[0];
        }

        return $out;
    }

    /** The field as the variety records read it. */
    public static function bookCase(array $p): array
    {
        $season = (string) ($p['season'] ?? '');

        return [
            'island' => \App\Support\VarietyBook::island($p['location'] ?? ''),
            'envs' => \App\Support\VarietyBook::envsFor($p['crop'] ?? '', (array) ($p['problems'] ?? []), null, $p['elevation'] ?? null),
            'elevation' => ($p['elevation'] ?? '') ?: null,
            'season' => in_array($season, ['wet', 'dry'], true) ? $season : null,
            'problems' => (array) ($p['problems'] ?? []),
        ];
    }

    /**
     * A report with its varieties linked to their records: each ranked,
     * given and newest variety gets its record (trial days, yield, ratings,
     * page), and the report a "by the numbers" shortlist from the records.
     * Done on every read, so reports written before the records still get
     * them.
     */
    public static function withRecords(array $report, array $p): array
    {
        $crop = (string) ($p['crop'] ?? '');
        $B = \App\Support\VarietyBook::class;
        if (! self::onRecord($p) || ! $B::rows($crop)) {
            return $report;
        }
        $link = function (array $x) use ($B, $crop) {
            $m = $B::match($crop, (string) ($x['variety'] ?? ''));
            if ($m) {
                $x['record'] = $B::brief($m);
            }

            return $x;
        };
        foreach (['ranking', 'givenVarieties', 'newest'] as $k) {
            if (! empty($report[$k]) && is_array($report[$k])) {
                $report[$k] = array_map(fn ($x) => is_array($x) ? $link($x) : $x, $report[$k]);
            }
        }
        $named = array_filter(array_map(fn ($x) => $x['record']['name'] ?? null, (array) ($report['ranking'] ?? [])));
        $report['records'] = [
            'count' => count($B::rows($crop)),
            'finder' => $B::finderUrl($crop),
            'shortlist' => array_map(fn ($r) => $B::brief($r) + ['inRanking' => in_array($r['name'], $named, true)],
                $B::candidates($crop, self::bookCase($p), 6)),
        ];

        return $report;
    }

    /**
     * The newest releases belong in the ranking (2026-10-17, the owner: "featured
     * newest in the ph and not add it to the rankings"): each newest one says
     * whether it is ranked, by its name or the name it is sold under.
     */
    public static function markNewest(array $report): array
    {
        $norm = fn ($s) => preg_replace('/[^a-z0-9]+/', '', mb_strtolower((string) $s));
        $ranked = [];
        foreach ((array) ($report['ranking'] ?? []) as $r) {
            foreach (preg_split('/[()\/]/', (string) ($r['variety'] ?? '')) as $part) {
                if (strlen($norm($part)) >= 3) {
                    $ranked[] = $norm($part);
                }
            }
        }
        foreach ((array) ($report['newest'] ?? []) as $i => $n) {
            $hit = false;
            foreach (preg_split('/[()\/]/', (string) ($n['variety'] ?? '')) as $part) {
                $k = $norm($part);
                if (strlen($k) >= 3 && array_filter($ranked, fn ($r) => $r === $k || str_contains($r, $k) || str_contains($k, $r))) {
                    $hit = true;
                }
            }
            $report['newest'][$i]['inRanking'] = $hit;
        }

        return $report;
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
        $text = "\n\n--- ATTACHED: Variety Ranking (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Ground: soil ' . (self::SOILS[$params['soil'] ?? ''] ?? '')
            . (! empty($params['elevation']) ? '; the land ' . (self::ELEVATIONS[$params['elevation']] ?? $params['elevation']) : '')
            . '; troubles: ' . (collect($params['problems'] ?? [])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none') . "\n"
            . FieldProfile::promptLine((array) ($params['fieldProfile'] ?? []), $params['crop'] ?? null, '')
            . (! empty($params['season']) ? 'Planned for: ' . (! empty($params['month']) ? 'planting in ' . self::monthWords($params['month']) . ', ' : '') . \App\Support\Region::seasonTitle($params['season'], (int) ($params['year'] ?? now('Asia/Manila')->year), $params['country'] ?? null) . "\n" : '')
            . 'Priorities, most important first: ' . $order . "\n"
            . 'Top pick: ' . ($top['variety'] ?? '') . ($top['by'] ?? null ? ' (' . $top['by'] . ')' : '') . ' — ' . ($top['why'] ?? '') . "\n"
            . 'Ranked: ' . collect($report['ranking'] ?? [])->map(fn ($x) => ($x['rank'] ?? '') . '. ' . ($x['variety'] ?? '')
                . ' [overall ' . ($x['overall'] ?? '') . '; yield ' . ($x['scores']['yield'] ?? '') . ', protection ' . ($x['scores']['protection'] ?? '')
                . ', survival ' . ($x['scores']['survival'] ?? '') . ', quickness ' . ($x['scores']['quickness'] ?? '') . '; '
                . ($x['maturityDays'] ?? '') . ']')->implode(' | ') . "\n"
            . 'Newest PH releases noted: ' . collect($report['newest'] ?? [])->map(fn ($x) => ($x['variety'] ?? '') . ' (' . ($x['year'] ?? '') . ')')->implode(', ') . "\n"
            . (! empty($report['derbies']) ? 'Derbies and farmers\' harvests found: ' . collect($report['derbies'])->map(fn ($d) => ($d['name'] ?? '') . ' (' . ($d['where'] ?? '') . ', ' . ($d['when'] ?? '') . '): '
                . collect($d['results'] ?? [])->map(fn ($x) => ($x['variety'] ?? '') . ' ' . ($x['yield'] ?? ''))->implode(', '))->implode(' | ') . "\n" : '')
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

        return $this->json(true, 'Moved to the Trash. Restore it from the Trash for 30 days.');
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
                'tags' => json_encode(\App\Http\Controllers\UserTagController::tidy($request->input('tags', []))),
                'updated_at' => now(),
            ]);
        if (! $updated) {
            return $this->json(false, 'That research is not in your saved list.', [], 404);
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
            'month' => preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? (string) $request->input('month') : null,
            'year' => (int) $request->input('year'),
            'season' => (string) $request->input('season'),
            'soil' => (string) $request->input('soil'),
            'elevation' => array_key_exists((string) $request->input('elevation'), self::ELEVATIONS) ? (string) $request->input('elevation') : null,
            'crop' => (string) $request->input('crop'),
            'varieties' => $varieties,
            'priorities' => array_values((array) $request->input('priorities')),
            'problems' => array_values((array) $request->input('problems', [])),
            'notes' => trim((string) $request->input('notes', '')),
            'lotId' => (int) $request->input('lotId') ?: null,
            'lotName' => mb_substr(trim((string) $request->input('lotName', '')), 0, 120) ?: null,
        ] + FieldProfile::forParams(FieldProfile::fromInput($request->input('fieldProfile'), (string) $request->input('crop')), (string) $request->input('crop'));
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
            return 'not available — read the season from the weather record and the ENSO outlook given here';
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
    /**
     * Whether the season's weather is the app's own for this field: a town
     * at home the hazard book knows, with its twenty years of rain, storms
     * and floods and a kept ENSO outlook. The outlook is then not searched.
     */
    private static function keptSky(array $p): bool
    {
        return ($p['country'] ?? \App\Support\Region::code()) === \App\Support\Region::HOME
            && \App\Support\HazardBook::odds((string) $p['location']) !== null && \App\Support\EnsoOutlook::forPrompt() !== '';
    }

    private function researchPrompt(array $p): string
    {
        $crop = CropCatalog::CROPS[$p['crop']];
        $year = now('Asia/Manila')->year;
        // Learn once (2026-10-18): a named variety on the record or kept from an earlier lookup is known; only the rest are looked up, and their facts come back as a block the app keeps.
        $unknown = self::toLookUp($p);
        $named = $p['varieties']
            ? implode(', ', array_map(fn ($v) => $v . (in_array($v, $unknown, true) ? '' : ' (known to anee.io, see 5 below)'), $p['varieties']))
            : 'none named — find the top-yielding released ones for these conditions';
        $block = $unknown ? \App\Support\VarietyLearned::askBlock($unknown, (string) $p['crop']) : '';
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
        $plan = ! empty($p['season']) ? (! empty($p['month']) ? 'Planting in ' . self::monthWords($p['month']) . ', in this season: ' : '') . \App\Support\Region::as($fc, fn () => \App\Support\Region::seasonWords($p['season'], (int) ($p['year'] ?: $year))) : 'not stated';
        $land = self::ELEVATIONS[$p['elevation'] ?? ''] ?? 'not stated';
        // The records on file that fit this field: the search checks them first.
        $onFile = self::onRecord($p) ? \App\Support\VarietyBook::namedThenFit($p['crop'], $p['varieties'] ?? [], self::bookCase($p), 14) : [];
        // The registration trial facts the records now carry (2026-10-18): the days, yields, ratings and tolerances of
        // these varieties are not looked up again; the search spends itself on what is newer and on real harvests.
        $covered = array_values(array_filter($onFile, fn ($r) => ! empty($r['trials']) || ($r['days'] !== null && $r['yield'] !== null && $r['ratings'])));
        $onFileLine = $onFile
            ? "\n5. These {$cropLabel} varieties are on anee.io's own records and fit this field on paper: " . implode(', ', array_map(fn ($r) => $r['name'] . ($r['aliases'] ? ' (' . $r['aliases'][0] . ')' : ''), $onFile)) . '. For each, look for anything newer than its registration: regional trial results near the farmer, seed availability this season, field reports of how it did.'
                . ($covered ? ' The app already holds the trial days, yields, pest and disease ratings and stress tolerance of ' . implode(', ', array_map(fn ($r) => $r['name'], $covered)) . ': do NOT look those figures up again and do not repeat them in the notes.' : '')
            : '';
        $wordCap = count($covered) >= 6 ? 1200 : 1600;
        // The season's weather is the app's own for a town at home (2026-10-19): its twenty years of rain, storms and
        // floods (ClimateStore, HazardBook) and the ENSO outlook reach the document, so the outlook is not searched.
        $keptSky = self::keptSky($p);
        $outlookAsk = $keptSky ? '' : "3. The seasonal climate outlook from {$met} for the farmer's region for the PLANTING SEASON named above (rainfall, ENSO state, severe-weather expectation across the months the crop will stand in the field).
";
        $keptLine = $keptSky ? "
The app keeps this place's weather record, its storms, floods and dry spells of twenty years and the ENSO outlook: do not look those up." : '';
        $derbyRunners = $fieldPH
            ? 'the DA regional field offices, PhilRice, the provincial and municipal agriculture offices, state universities, and the seed companies (SL Agritech, Bayer, Syngenta, Corteva Pioneer, Bioseed, East West Seed and others)'
            : 'the agriculture department and extension services, universities, state variety trial programs and the seed companies';
        $hybridHouses = $fieldPH
            ? '(for rice: SL Agritech / SL-8H and its line, Bayer Arize, Syngenta, Corteva-Pioneer, Bioseed, Longping High-Tech; for corn: Pioneer, Bayer-Dekalb, Syngenta NK, Bioseed; for vegetables: East-West Seed, Allied Botanical, Known-You, Condor, Ramgo — whichever apply to this crop), and the NSIC-registered public hybrids (e.g. Mestiso / Mestizo lines for rice)'
            : '(the top seed companies and public breeding programs actually selling this crop in ' . $countryName . ' — name them from what you find, never from memory)';

        return <<<PROMPT
You are a research assistant for agronomy in {$countryName} with web search. SEARCH THE WEB NOW and write research notes for a variety comparison. Do not answer from memory alone; every finding must come from a page you read, with the source name and year beside it.{$keptLine}

THE CASE
- Crop: {$cropLabel}
- Field: {$p['location']}; soil: {$soil}; the land: {$land}; troubles: {$problems}
- When the farmer plans to plant: {$plan}
- Varieties the farmer named: {$named}

FIND, IN THIS ORDER
1. The newest {$cropLabel} varieties registered or released in {$countryName} ({$seeds}) in {$year}, {$year}-1 and {$year}-2: name, breeder or company, year, what it was bred for.
2. For each of the farmer's named varieties AND for 4–6 top-yielding released INBRED or open-pollinated varieties suited to this soil and these troubles (skip the trial figures of any variety listed in 5 below as already held by the app): documented yield (trial or published figure, with unit and source), days to maturity, pest and disease resistance ratings, stress tolerance (drought, submergence, salinity, heat, acidity, alkalinity, lodging), grain or fruit quality notes, and any regional trial results in or near the farmer's region.
2b. The same for 3–5 HYBRID varieties of {$cropLabel} sold in {$countryName} by the top seed companies {$hybridHouses}: yield, maturity, resistance, seed cost and availability per hectare where published, and whether the seed must be bought fresh each season.
{$outlookAsk}4. Anything published about which of these varieties do well or poorly on this soil type, at this elevation, in this season and with these troubles — which are bred for the wet season and which for the dry, which stand the highland's cool nights or the lowland's heat.{$onFileLine}
6. REAL HARVESTS, NOT ONLY BROCHURES (the most important part after the newest releases). Search for the MOST RECENT {$cropLabel} derbies in {$countryName}: variety derbies, techno demo farms, hybrid derbies, field days and yield contests run by {$derbyRunners}, newest first, in or near the farmer's province and region first, then anywhere in the country. For each: its name, the town and province, the season and year, the organizer, and EVERY variety's harvested yield as reported (t/ha, or cavans per hectare with the kilos per cavan). Then search for real farmers' own reported harvests with these varieties in the last two seasons: news reports, agriculture office reports, farmer cooperatives, farmers' field schools, farmer groups and seed company field posts naming the farmer or the place, each with the yield, the place, the season and the source. Real harvests weigh more than the seller's or the brochure's figure: say plainly where they differ.

Prefer {$sources}. Where a variety cannot be found online, say so plainly. Write plain prose notes under the headings, at most {$wordCap} words, no JSON, no markdown tables.{$block}
PROMPT;
    }

    /** The varieties the farmer named that neither the records nor an earlier lookup know (at most four are looked up as facts to keep). */
    public static function toLookUp(array $p): array
    {
        if (! self::onRecord($p)) {
            return [];
        }
        $out = [];
        foreach ((array) ($p['varieties'] ?? []) as $v) {
            $v = trim((string) $v);
            if ($v !== '' && ! \App\Support\VarietyBook::find((string) $p['crop'], $v)) {
                $out[] = $v;
            }
        }

        return array_slice($out, 0, 4);
    }

    /** The question, spelled out so the answer is bounded, searched and honest. */
    private function prompt(array $p): string
    {
        $crop = CropCatalog::CROPS[$p['crop']];
        $soil = self::SOILS[$p['soil']] ?? $p['soil'];
        $problems = collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported';
        // The soil troubles among them, read as every analysis reads them:
        // which varieties tolerate it, and what a correction costs in time.
        $soilKeys = array_map(fn ($k) => $k === 'salinity' ? 'saline' : $k, (array) $p['problems']);
        $soilGuide = \App\Support\SoilConditions::guidance(\App\Support\SoilConditions::normalize($soilKeys));
        $soilBlock = $soilGuide !== '' ? "- What the soil troubles mean for the choice (weigh each variety's published tolerance to them, and say so):\n" . $soilGuide . "\n" : '';
        // The field in detail, when the farmer gave it: one compact line.
        $soilBlock .= FieldProfile::promptLine((array) ($p['fieldProfile'] ?? []), $p['crop']);
        // The area's typical soil from the soil atlas (2026-10-18, the soil atlas agent's block): the varieties are weighed against it.
        if (self::onRecord($p) && class_exists(\App\Support\SoilAtlas::class)) {
            try {
                $atlas = \App\Support\SoilAtlas::forWhere((string) $p['location'], (string) $p['crop']);
                if ($atlas && ($sw = \App\Support\SoilAtlas::words($atlas, 'short')) !== '') {
                    // The block says itself that it is typical for the area, not a test of this field.
                    $soilBlock .= $sw . "\n";
                    if (in_array(\App\Support\SoilAtlas::saltRisk($atlas), ['moderate', 'severe'], true)) {
                        $soilBlock .= "- The coastal land of this town often holds salt (typical for the area, not a test of this field): favour salt tolerant varieties, and say so.\n";
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $notes = $p['notes'] !== '' ? $p['notes'] : 'none';
        $order = [];
        foreach ($p['priorities'] as $i => $k) {
            $order[] = ($i + 1) . '. ' . (self::PRIORITIES[$k]['label'] ?? $k) . ' (weight ' . (int) round((self::WEIGHTS[$i] ?? 0) * 100) . '%)';
        }
        $orderText = implode('; ', $order);
        $given = $p['varieties']
            ? 'The farmer is weighing these: ' . implode(', ', $p['varieties']) . '. Assess EVERY one of them (in givenVarieties), and put those that fit in the ranking; add the top-yielding released varieties for these conditions that the farmer did not name — inbred/open-pollinated AND hybrid, the newest releases among them — so the ranking has TEN in all.'
            : 'The farmer has not named any. Choose them yourself: about five top-yielding released INBRED / open-pollinated varieties AND about five HYBRID varieties from the top seed companies selling in ' . \App\Support\Region::name($p['country'] ?? null) . ', for these conditions, the newest releases among them — TEN in all, every one with its type filled in.';
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';
        // The field's measured weather for the months the crop will stand (the climate shelf, 2026-10-18).
        $focus = [];
        if (! empty($p['month'])) {
            // The months the crop stands, from the planting month (the crop's usual days, and the month it is cut).
            for ($i = 0, $n = min(12, (int) ceil(CropCatalog::maturity((string) $p['crop']) / 30) + 1); $i < $n; $i++) {
                $focus[] = ((int) substr($p['month'], 5, 2) - 1 + $i) % 12 + 1;
            }
        } else {
            $focus = ['wet' => [6, 7, 8, 9, 10], 'dry' => [12, 1, 2, 3, 4], 'third' => [3, 4, 5, 6]][$p['season'] ?? ''] ?? [];
        }
        // Built while the farmer's request waits: what the shelf keeps, no lookup.
        $ensoBlock .= \App\Support\ClimateStore::wordsFor((string) $p['location'], null, null, $focus, $focus !== [], false);
        // The town's storms, floods and dry spells in the season's months (the hazard book, 2026-10-18, the hazard history agent's block): an early variety dodges the typhoon peak.
        if (class_exists(\App\Support\HazardBook::class)) {
            $ensoBlock .= \App\Support\HazardBook::wordsFor((string) $p['location'], $focus, 1500);
        }
        $forecast = $this->forecastLines($p['location']);
        $year = now('Asia/Manila')->year;
        // anee.io's own variety records that best fit this field (2026-10-14).
        $B = \App\Support\VarietyBook::class;
        $records = self::onRecord($p) ? $B::namedThenFit($p['crop'], $p['varieties'] ?? [], self::bookCase($p), 22) : [];
        $recordsBlock = $records ? "\nVARIETY RECORDS ON FILE\nanee.io keeps its own records of " . CropCatalog::label($p['crop']) . ' varieties (days to maturity and yields from the national trials, pest and disease ratings, the growing environment and area each is recommended for, and for most of them the registration trials in short: days and yield by season, the best test, the advantage over the check varieties, the seasons and sites tried, the ratings, the tolerance of salt, drought, flooding and cold, the season it does better in and its known limits). These ' . count($records) . ' fit this field best on paper (the farmer\'s own varieties first, where they are on file), out of' . count($B::rows($p['crop'])) . " on file:\n"
            . $B::promptLines($records) . "\n"
            . "How to use them: draw most of the ranking from these records where they fit, and add a variety from the research notes only when it is newer or plainly better here. For a variety on this list, take maturityDays and yieldPotential from its record (the record's own figure first; where its registration trials differ, give the range) and write 'trials' in its sources, never an agency, the registry or a research institute (a newer figure from the notes may stand beside it, with its own source). Its ratings, tolerances and limits are measured: score protection and survival from them, not from memory. Write each variety's registered name first, exactly as written here, with the name it is sold under in brackets when it has one, e.g. 'NSIC Rc 666H (Jackpot 102)'. A record marked 'seller figures' holds the seller's own claims, not trial results: say so where it matters.\n" : '';

        // The FIELD's country, which may not be the farmer's.
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $countryName = \App\Support\Region::name($fc);
        $cropLabel = CropCatalog::label($p['crop']);
        $climateHint = $fieldPH ? 'wet/dry timing, typhoon seasonality' : 'frost dates, growing-season length, rainfall and heat timing, the severe-weather season';
        $plan = ! empty($p['season']) ? (! empty($p['month']) ? 'Planting in ' . self::monthWords($p['month']) . ', in this season: ' : '') . \App\Support\Region::as($fc, fn () => \App\Support\Region::seasonWords($p['season'], (int) ($p['year'] ?: $year))) : 'not stated';
        $land = self::ELEVATIONS[$p['elevation'] ?? ''] ?? 'not stated';
        // The season's outlook is the app's own for a town the hazard book knows, so the notes do not carry one (2026-10-19).
        $outlookNote = self::keptSky($p) ? '' : ', the seasonal outlook';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$countryName}, with web search available. The farmer asks WHICH VARIETY of {$cropLabel} to plant on the ground described below. Research and compare varieties, score each on four criteria, and rank them by the farmer's own priorities.

FACTS GIVEN
- Location as the farmer wrote it: {$p['location']}
- Crop: {$cropLabel}
- When the farmer plans to plant: {$plan}
- The lay of the land: {$land}
- Soil, as the farmer describes it: {$soil}
- Field troubles the farmer reports: {$problems}
{$soilBlock}- The farmer's priorities, most important first: {$orderText}
- Varieties: {$given}
- The farmer's own notes: {$notes}
- Weather now: {$forecast}
{$ensoBlock}{$recordsBlock}
WHAT TO READ FROM
Research notes gathered from the web just now follow at the end of this brief (the newest registrations and releases of {$cropLabel} in {$countryName} in {$year} and the two years before, trial yields, the most recent derbies and real farmers' harvests, resistance ratings, days to maturity{$outlookNote}). Rely on them first, over memory; note the year of each finding. Where a claim is neither in the notes nor established agronomy, say so in dataGaps rather than inventing it.

GROUND RULES
- Reason from what you found and from established agronomy: the soil-water behaviour implied by the described soil and troubles, the region's climate ({$climateHint}) and the outlook above, each variety's documented traits. No invented yields; a yield figure must be a trial or published figure with its source.
- THE SEASON AND THE LAND decide as much as the soil: a variety bred for the wet season is not the dry season's, an early one dodges the season's hazard (the typhoon peak, the late heat, the first frost) that a late one meets at flowering or harvest, and the highland's cool nights and the lowland's heat each rule varieties out. Score for the planting season and the elevation named, and say so in the why where they decided it.
- REAL HARVESTS FIRST: the yield score rests most on the derbies' and real farmers' harvests in the notes (the newest and the nearest weigh most), then the national trials, and least on a seller's or brochure figure. A variety that did well in a derby near the farmer this year scores above one with a bigger brochure number and no field record.
- Score each variety 0–100 on each criterion FOR THESE CONDITIONS: yield (what it really harvests here, see above), protection (pest/disease resistance and stress tolerance relevant to the reported troubles), survival (establishment and hardiness through this ground's stresses), quickness (earliness — fewer days to maturity scores higher).
- Be scientific and neutral: name the breeder or seed company as a fact, never as a recommendation to buy; no marketing tone.
- Write every "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"headline":"","topPick":{"variety":"","by":"","why":""},"ranking":[{"rank":1,"variety":"","type":"inbred","by":"","released":"","maturityDays":"","yieldPotential":"","fieldResults":[{"kind":"derby","where":"","when":"","yield":"","source":""}],"scores":{"yield":0,"protection":0,"survival":0,"quickness":0},"strengths":[""],"weaknesses":[""],"fitNotes":"","sources":[""]}],"givenVarieties":[{"variety":"","verdict":""}],"newest":[{"variety":"","by":"","year":"","note":""}],"derbies":[{"name":"","where":"","when":"","by":"","results":[{"variety":"","yield":""}],"source":""}],"conditions":{"soil":"","weather":"","risks":[""]},"management":[""],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- headline: one line, ≤ 14 words, the answer in a breath.
- ranking: TEN varieties (fewer only when fewer than ten released varieties can be grown here at all, and then say so in dataGaps). Every variety in newest that can be grown on this ground is IN the ranking too, scored like the rest. Each with: type ("hybrid" for F1 hybrids whose seed is bought fresh each season, "inbred" for inbred, open-pollinated and public registered varieties — never leave it empty), by (the seed company that bred or sells it; for a public variety write "public variety", never an agency, a university or an institute), released (year or "n/a"), maturityDays (e.g. "110–115 DAS" or "n/a"), yieldPotential (a published figure with unit, e.g. "6.5–8.0 t/ha (trials)", or "not published"), scores (all four, 0–100), strengths (2–3 short items), weaknesses (1–3 short items), fitNotes (≤ 40 words on THIS ground and climate), fieldResults (zero to three real harvests from the notes, newest and nearest first: kind "derby" for a derby, techno demo or yield contest, "farmer" for a farmer's own reported harvest, "trial" for a regional trial; where = town and province; when = season and year; yield = as reported, with t/ha and cavans where given; source = the short source name; an empty list when none was found), sources (1–3 short source names, e.g. "trials", "seed company leaflet 2023", "Nueva Ecija derby 2025"; never an agency, the registry or a research institute).
- givenVarieties: one entry for EACH variety the farmer named (empty list if none), verdict ≤ 35 words — including when it is unsuitable, unavailable, or could not be found (say so plainly).
- newest: two to five of the most recent relevant releases in {$countryName} found online (year required), each with a ≤ 20-word note on why it matters here (and, when it is not in the ranking, why not).
- derbies: zero to four of the most recent derbies, techno demos or yield contests found in the notes, the nearest and newest first: name, where (town and province), when (season and year), by (the organizer when it is a seed company, a cooperative or a town; empty for a government office, a university or an institute), results (each variety's harvested yield as reported, best first, up to eight), source; [] when none was found.
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
