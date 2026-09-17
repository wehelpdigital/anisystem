<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Support\AiPrices;
use App\Support\AiUsage;
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
    public const PRICE = AiPrices::DEFAULTS['what'];

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

    /**
     * The extra signals (2026-09-17): each optional, each a fact a farmer
     * can read off the field without a laboratory, each one that moves the
     * ranking. "Not sure" is always the first answer and costs nothing.
     */
    public const PH_LEVELS = [
        'unsure' => 'Not sure / never tested',
        'acidic' => 'Acidic — below 6 (moss, ferns, poor legumes)',
        'neutral' => 'Around neutral — 6 to 7.5',
        'alkaline' => 'Alkaline — above 7.5 (white crust, yellowing young leaves)',
    ];

    public const WATER_LOOKS = [
        'unsure' => 'Not sure / no irrigation water',
        'clear' => 'Clear and clean',
        'muddy' => 'Muddy or brown — carries silt',
        'milky' => 'Cloudy white or milky',
        'green' => 'Greenish — algae in it',
        'salty' => 'Tastes salty or leaves a white crust',
        'smelly' => 'Smells bad, oily film or foam',
    ];

    public const LAYS = [
        'flat' => 'Flat',
        'gentle' => 'Gently sloping',
        'steep' => 'Steep — water runs off fast',
        'low' => 'Low-lying — water collects',
    ];

    public const ELEVATIONS = [
        'lowland' => 'Lowland — near sea level, warm',
        'upland' => 'Upland / hilly — rolling ground',
        'highland' => 'Highland — cool, cloudy mornings',
    ];

    public const SUNS = [
        'full' => 'Full sun all day',
        'part' => 'Shaded part of the day (trees, buildings)',
        'shade' => 'Mostly shaded',
    ];

    public const LABORS = [
        'hand' => 'Mostly by hand, family labor',
        'some' => 'Some hired help or a small machine',
        'mech' => 'Machinery and a crew when needed',
    ];

    public const BUDGETS = [
        'tight' => 'Tight — seeds and little else',
        'moderate' => 'Moderate — the usual inputs',
        'invest' => 'Can invest — irrigation, good seed, protection',
    ];

    public const MARKETS = [
        'farm' => 'Buyers come to the farm',
        'town' => 'The nearby town market',
        'city' => 'A city market or trader, hours away',
        'contract' => 'A contract or cooperative buyer',
    ];

    /**
     * What the farmer is after, ranked by them in the wizard: the top one
     * weighs most in every crop's score. The order below is the default.
     */
    public const PRIORITIES = [
        'profit' => 'Profit — the best money for this ground',
        'survival' => 'Survivability — stands up to the weather, pests and disease',
        'ease' => 'Low cost and easy management — few inputs, little labor',
        'speed' => 'Quick harvest — money or food sooner',
        'market' => 'A sure market — easy to sell where I am',
        'food' => 'Food for the family — what we actually eat',
    ];

    /**
     * The five crop families the ranking speaks in — and the ones a farmer
     * may leave out of it. Never all five: the server refuses that.
     */
    public const FAMILIES = [
        'grain' => 'Grains — rice, corn, sorghum',
        'vegetable' => 'Vegetables — leafy, fruiting, bulbs, gourds',
        'root' => 'Root crops — sweet potato, cassava, taro, ube',
        'legume' => 'Legumes — mungbean, peanut, soybean, beans',
        'tree' => 'Fruit and tree crops — banana, mango, calamansi, coconut',
    ];

    /** The family key → the category word the report uses. */
    public const FAMILY_CATEGORY = [
        'grain' => 'Grain', 'vegetable' => 'Vegetable', 'root' => 'Root crop', 'legume' => 'Legume', 'tree' => 'Fruit / tree',
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

    /** The tier wall: analyses ride the Solo Farmer plan and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('reportsAll')) {
            \App\Support\Tier::deny('The What to Plant analysis comes with the Solo Farmer plan.');
        }
    }

    public function page()
    {
        $this->guardTier();
        return view('what-to-plant.index');
    }

    /** Everything the wizard needs, plus the standing price. */
    public function options()
    {
        $farmerCountry = \App\Support\Region::code();
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
            'phLevels' => self::PH_LEVELS,
            'waterLooks' => self::WATER_LOOKS,
            'lays' => self::LAYS,
            'elevations' => self::ELEVATIONS,
            'suns' => self::SUNS,
            'labors' => self::LABORS,
            'budgets' => self::BUDGETS,
            'markets' => self::MARKETS,
            'priorities' => self::PRIORITIES,
            'families' => self::FAMILIES,
            // The whole crop book, in the farmer's words; the page hides the
            // temperate ones (`intl`) while the FIELD is in the Philippines.
            'crops' => collect(\App\Support\CropCatalog::CROPS)->map(fn ($c, $key) => [
                'key' => $key,
                'label' => \App\Support\CropCatalog::label($key),
                'icon' => $c['icon'] ?? '🌱',
                'group' => $c['group'] ?? 'Other',
                'maturity' => $c['maturity'] ?? null,
                'perennial' => \App\Support\CropCatalog::isPerennial($key),
                'intl' => ! empty($c['intl']),
            ])->values(),
            'waters' => self::WATERS,
            'aims' => self::AIMS,
            'problems' => self::PROBLEMS,
            'months' => $months,
            'country' => $farmerCountry,
            'quote' => $canUse ? (float) AiPrices::of('what') : null,
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
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'The analysis needs the AI Technician (Boss or Lifetime plan).', [], 403);
        }

        $v = Validator::make($request->all(), [
            'location' => 'required|string|max:160',
            'country' => 'nullable|string|size:2',
            'startMonth' => 'required|date_format:Y-m',
            'soil' => 'required|in:' . implode(',', array_keys(self::SOILS)),
            'water' => 'required|in:' . implode(',', array_keys(self::WATERS)),
            'aim' => 'required|in:' . implode(',', array_keys(self::AIMS)),
            'area' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:400',
            'ph' => 'nullable|in:' . implode(',', array_keys(self::PH_LEVELS)),
            'phValue' => 'nullable|string|max:24',
            // The two pick-many answers arrive as lists (an older page may still send one key).
            'waterLook' => 'nullable',
            'waterLook.*' => 'string|max:24',
            'lay' => 'nullable|in:' . implode(',', array_keys(self::LAYS)),
            'elevation' => 'nullable|in:' . implode(',', array_keys(self::ELEVATIONS)),
            'sun' => 'nullable|in:' . implode(',', array_keys(self::SUNS)),
            'prevCrop' => 'nullable|string|max:120',
            'grewWell' => 'nullable|string|max:160',
            'labor' => 'nullable|in:' . implode(',', array_keys(self::LABORS)),
            'budget' => 'nullable|in:' . implode(',', array_keys(self::BUDGETS)),
            'market' => 'nullable',
            'market.*' => 'string|max:24',
            'problems' => 'nullable|array',
            'problems.*' => 'string|in:' . implode(',', array_keys(self::PROBLEMS)),
            // Crops the farmer has in mind (catalogue keys, plus free words) and
            // what matters to them, in their order.
            'cropsAsked' => 'nullable|array|max:8',
            'cropsAsked.*' => 'string|max:40',
            'cropsOther' => 'nullable|string|max:160',
            'priorities' => 'nullable|array|max:8',
            'priorities.*' => 'string|max:24',
            // Families left out — never all of them.
            'exclude' => 'nullable|array',
            'exclude.*' => 'string|max:24',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        if (count(self::picks(self::FAMILIES, $request->input('exclude'))) >= count(self::FAMILIES)) {
            return $this->json(false, 'Keep at least one crop family in play — you have left out all of them.', [], 422);
        }

        $balance = $this->credits->balance($payer->id);
        if ($balance < AiPrices::of('what') && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . AiPrices::of('what') . ' credits for this analysis and have '
                . number_format((int) floor($balance)) . '.',
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
        // A field in another country than the farmer's: Anee is told so in
        // her own instructions, or she declines for being "set up" at home.
        $settings = $settings->forField($p['country']);
        $title = 'What to plant · ' . \Illuminate\Support\Carbon::parse($p['startMonth'] . '-01')->format('M Y')
            . ' · ' . $p['location'] . ($p['country'] !== \App\Support\Region::code() ? ', ' . \App\Support\Region::name($p['country']) : '');
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
        /* The heartbeat: before every call to the model the row says which
         * phase it is in and touches updated_at, so the page can move its
         * bar and jobState can tell a killed job from a slow one. */
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                'report' => json_encode(['phase' => $phase, 'try' => $try]),
                'updated_at' => now(),
            ]);
        };
        try {
            // Up to ten picks plus the farmer's own, each with its harvest and
            // six fits, and twelve months of risk: a longer answer than before.
            $result = $this->ai->askForJson($settings, $prompt, 14000, fn (string $t) => $this->parseReport($t), ['onPhase' => $beat]);
            $report = $result['data'];
            if ($report === null) {
                \Illuminate\Support\Facades\Log::warning('what-to-plant: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException($result['error'] ?? 'The analysis came back unreadable. Nothing was charged — please try again.');
            }

            $charged = (float) AiPrices::of('what');
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $p = json_decode((string) ($row->params ?? '[]'), true) ?: [];
            $note = AiUsage::record('what', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('What-to-plant analysis — ' . ($p['location'] ?? '') . $note, 0, 250));

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
    /**
     * A pending row is dead when its heart has not beaten for longer than
     * any one call to the model may take (the document timeout plus a
     * generous minute), or when it is simply too old. Killed processes
     * write nothing, so this is the only way to tell.
     */
    private function dead(object $r): bool
    {
        $beatAt = \Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at);
        $quiet = \App\Services\AiClient::TIMEOUT_DOCUMENT + 60;

        return $beatAt->lt(now()->subSeconds($quiet))
            || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(20));
    }

    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'what')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            if ($this->dead($r)) {
                $why = \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(20))
                    ? 'The analysis took too long and was stopped. Nothing was charged — please try again.'
                    : 'The analysis was interrupted mid-way (the server restarted under it). Nothing was charged — please run it again.';
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

    public function list(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')))
            ->skip(($page - 1) * $per)->take($per + 1)
            ->get(['id', 'title', 'description', 'credits', 'created_at']);
        $hasMore = $rows->count() > $per;
        $rows = $rows->take($per);

        return $this->json(true, 'ok', ['page' => $page, 'hasMore' => $hasMore, 'q' => $q, 'rows' => $rows->map(fn ($r) => [
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
            . (($params['ph'] ?? 'unsure') !== 'unsure' ? '; pH ' . (self::PH_LEVELS[$params['ph']] ?? '') : '')
            . (($looks = self::picks(self::WATER_LOOKS, $params['waterLook'] ?? null)) ? '; water looks ' . collect($looks)->map(fn ($k) => self::WATER_LOOKS[$k])->implode(', ') : '')
            . (($outlets = self::picks(self::MARKETS, $params['market'] ?? null)) ? '; sells to ' . collect($outlets)->map(fn ($k) => self::MARKETS[$k])->implode(', ') : '')
            . '; aim ' . (self::AIMS[$params['aim'] ?? ''] ?? '') . ($params['area'] ?? null ? '; area ' . $params['area'] : '') . "\n"
            . 'Troubles considered: ' . ($problems ?: 'none') . "\n"
            . (! empty($params['priorities']) ? 'What matters to the farmer, most first: ' . collect(self::ordered($params['priorities']))->map(fn ($k) => explode(' — ', self::PRIORITIES[$k])[0])->implode(' > ') . "\n" : '')
            . (! empty($params['cropsAsked']) || ! empty($params['cropsOther']) ? 'Crops the farmer had in mind: ' . collect($params['cropsAsked'] ?? [])->map(fn ($k) => \App\Support\CropCatalog::label($k))->push($params['cropsOther'] ?? '')->filter()->implode('; ') . "\n" : '')
            . 'Top pick: ' . ($top['crop'] ?? '') . ' (' . ($top['category'] ?? '') . ') — ' . ($top['why'] ?? '') . "\n"
            . 'Ranked: ' . collect($report['recommendations'] ?? [])->map(fn ($x) => ($x['rank'] ?? '') . '. ' . ($x['crop'] ?? '') . ' [' . ($x['category'] ?? '') . '] score ' . ($x['score'] ?? '')
                . (! empty($x['farmerAsked']) ? ' (the farmer\'s own)' : '')
                . (! empty($x['daysToHarvest']) ? ', harvest in ~' . (int) $x['daysToHarvest'] . ' days' . (! empty($x['harvestWindow']) ? ' (' . $x['harvestWindow'] . ')' : '') : '')
                . (isset($x['harvestRisk']['score']) ? ', harvest-day risk ' . (int) $x['harvestRisk']['score'] . '/100' . (! empty($x['harvestRisk']['kind']) && $x['harvestRisk']['kind'] !== 'none' ? ' ' . $x['harvestRisk']['kind'] : '') : ''))->implode(' | ') . "\n"
            . (! empty($params['exclude']) ? 'Families the farmer left out: ' . collect(self::picks(self::FAMILIES, $params['exclude']))->map(fn ($k) => self::FAMILY_CATEGORY[$k])->implode(', ') . "\n" : '')
            . (! empty($report['surprises']) ? 'Surprises (unusual here, but argued for): ' . collect($report['surprises'])->map(fn ($x) => ($x['crop'] ?? '') . ' — ' . ($x['why'] ?? ''))->implode(' | ') . "\n" : '')
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

    /**
     * A pick-many answer as a clean list of the table's keys: takes a list or
     * a single key (an older page, an older saved row), drops anything
     * unknown and the "unsure" key, keeps the farmer's order.
     */
    public static function picks(array $table, mixed $v): array
    {
        $out = [];
        foreach ((array) $v as $k) {
            $k = (string) $k;
            if ($k !== 'unsure' && isset($table[$k]) && ! in_array($k, $out, true)) {
                $out[] = $k;
            }
        }

        return $out;
    }

    /**
     * The priorities in the farmer's order: the known keys they sent, first
     * to last, then any they left out in the default order.
     */
    public static function ordered(mixed $v): array
    {
        $out = self::picks(self::PRIORITIES, $v);
        foreach (array_keys(self::PRIORITIES) as $k) {
            if (! in_array($k, $out, true)) {
                $out[] = $k;
            }
        }

        return $out;
    }

    private function params(Request $request): array
    {
        $country = \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code();
        // The crops the farmer named must be ones the FIELD's country grows.
        $book = \App\Support\Region::as($country, fn () => \App\Support\CropCatalog::visible());

        return [
            'location' => trim((string) $request->input('location')),
            'country' => $country,
            'cropsAsked' => self::picks($book, $request->input('cropsAsked')),
            'exclude' => self::picks(self::FAMILIES, $request->input('exclude')),
            'cropsOther' => trim((string) preg_replace('/\s+/', ' ', (string) $request->input('cropsOther', ''))),
            'priorities' => self::ordered($request->input('priorities')),
            'startMonth' => (string) $request->input('startMonth'),
            'soil' => (string) $request->input('soil'),
            'water' => (string) $request->input('water'),
            'aim' => (string) $request->input('aim'),
            'area' => trim((string) $request->input('area', '')),
            'notes' => trim((string) $request->input('notes', '')),
            'problems' => array_values((array) $request->input('problems', [])),
            // The extra signals, each optional; an unknown reads as "not sure".
            'ph' => array_key_exists((string) $request->input('ph'), self::PH_LEVELS) ? (string) $request->input('ph') : 'unsure',
            // A tested pH as the farmer wrote it: one reading or a range ("5.5–6.2").
            'phValue' => $request->filled('phValue') ? trim((string) preg_replace('/\s+/', ' ', (string) $request->input('phValue'))) : null,
            'waterLook' => self::picks(self::WATER_LOOKS, $request->input('waterLook')),
            'lay' => array_key_exists((string) $request->input('lay'), self::LAYS) ? (string) $request->input('lay') : null,
            'elevation' => array_key_exists((string) $request->input('elevation'), self::ELEVATIONS) ? (string) $request->input('elevation') : null,
            'sun' => array_key_exists((string) $request->input('sun'), self::SUNS) ? (string) $request->input('sun') : null,
            'prevCrop' => trim((string) $request->input('prevCrop', '')),
            'grewWell' => trim((string) $request->input('grewWell', '')),
            'labor' => array_key_exists((string) $request->input('labor'), self::LABORS) ? (string) $request->input('labor') : null,
            'budget' => array_key_exists((string) $request->input('budget'), self::BUDGETS) ? (string) $request->input('budget') : null,
            'market' => self::picks(self::MARKETS, $request->input('market')),
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
        // The extra signals, in words; "not stated" where the farmer skipped one.
        $said = fn (array $table, ?string $k) => ($k && $k !== 'unsure' && isset($table[$k])) ? $table[$k] : 'not stated';
        $ph = $said(self::PH_LEVELS, $p['ph'] ?? null) . (($p['phValue'] ?? null) ? ' (tested: pH ' . $p['phValue'] . ')' : '');
        $saidMany = fn (array $table, mixed $v) => collect(self::picks($table, $v))->map(fn ($k) => $table[$k])->implode('; ') ?: 'not stated';
        $waterLook = $saidMany(self::WATER_LOOKS, $p['waterLook'] ?? null);
        $lay = $said(self::LAYS, $p['lay'] ?? null);
        $elevation = $said(self::ELEVATIONS, $p['elevation'] ?? null);
        $sun = $said(self::SUNS, $p['sun'] ?? null);
        $prevCrop = ($p['prevCrop'] ?? '') !== '' ? $p['prevCrop'] : 'not stated';
        $grewWell = ($p['grewWell'] ?? '') !== '' ? $p['grewWell'] : 'not stated';
        $labor = $said(self::LABORS, $p['labor'] ?? null);
        $budget = $said(self::BUDGETS, $p['budget'] ?? null);
        $market = $saidMany(self::MARKETS, $p['market'] ?? null);
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';

        // The FIELD's country, which may not be the farmer's: its name, its
        // weather authority, its crops; the language stays the farmer's.
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $countryName = \App\Support\Region::name($fc);
        $regionBlock = \App\Support\Region::promptBlock($fc, \App\Support\Region::code());
        $met = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('met'));
        $climateRule = $fieldPH
            ? 'PAGASA climatological normals for the region named (wet/dry timing, typhoon seasonality)'
            : 'the climatological normals for the region named as published by ' . $met . ' (frost dates and growing-season length where they apply, rainfall and temperature timing, the severe-weather season)';

        // The crops the app itself knows for the FIELD's country: at home the
        // 85 Philippine crops and none of the temperate staples, abroad the
        // whole book. She picks from this pool, so a Philippine field is never
        // told to sow wheat and every pick has a calendar behind it.
        $book = \App\Support\Region::as($fc, fn () => \App\Support\CropCatalog::visible());
        $pool = implode(', ', array_map(
            fn (array $c, string $k) => (string) $c['label'] . (\App\Support\CropCatalog::isPerennial($k)
                ? ' [bears at ' . (int) ($c['bearingAt'] ?? 0) . ' months]'
                : (! empty($c['maturity']) ? ' [' . (int) $c['maturity'] . ' d]' : '')),
            $book, array_keys($book)
        ));
        // The crops the farmer has in mind, by the book's name, plus their own words.
        $askedNames = array_map(fn ($k) => (string) ($book[$k]['label'] ?? $k), self::picks($book, $p['cropsAsked'] ?? []));
        $asked = ($askedNames ? implode('; ', $askedNames) : '')
            . (($p['cropsOther'] ?? '') !== '' ? ($askedNames ? '; and in the farmer\'s own words: "' : 'in the farmer\'s own words: "') . $p['cropsOther'] . '"' : '');
        $asked = $asked !== '' ? $asked : 'none';
        // The families the farmer left out, in the report's own category words.
        $excluded = self::picks(self::FAMILIES, $p['exclude'] ?? []);
        $excludedLine = $excluded ? implode(', ', array_map(fn ($k) => self::FAMILY_CATEGORY[$k], $excluded)) : 'none — every family is in play';
        $familiesLeft = implode(', ', array_map(fn ($k) => self::FAMILY_CATEGORY[$k], array_diff(array_keys(self::FAMILIES), $excluded)));
        // What matters to the farmer, most first, with the weight each carries.
        $prio = self::ordered($p['priorities'] ?? null);
        $prioLine = implode('; ', array_map(fn ($k, $i) => ($i + 1) . '. ' . self::PRIORITIES[$k] . ' (weight ' . (count($prio) - $i) . ')', $prio, array_keys($prio)));
        $prioKeys = implode(', ', $prio);
        $realism = $fieldPH
            ? 'wheat, barley, oats, rye, apples, pears, cherries, plums, kiwi, blueberries, hops or any other temperate crop must not appear as a recommendation anywhere in the Philippines (the cool-highland exceptions such as Benguet strawberries, lettuce and cabbage only where the field is really up in that highland)'
            : 'coconut, lowland rice, sugarcane, mango, banana, papaya, cacao, coffee or any other tropical crop must not appear as a recommendation where the region named has frost and no season long enough for it; recommend them only where they are really grown commercially in that region';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$countryName}. The farmer asks WHAT to plant on the ground described below, starting around {$start}. Recommend the best-suited crops, ranked, drawn from across the families a farm in {$countryName} weighs: grains, vegetables, root crops, legumes, and fruit/tree crops — only crops actually grown and sold in {$countryName}, in the climate of the region named.

FACTS GIVEN
- {$regionBlock}
- Location as the farmer wrote it: {$p['location']}
- Planned start: {$start}
- Soil, as the farmer describes it: {$soil}
- Water: {$water}
- What the harvest is for: {$aim}
- Land area: {$area}
- Soil pH, as far as the farmer knows: {$ph}
- The irrigation water, as it looks (every look the farmer ticked): {$waterLook}
- The lay of the land: {$lay}
- Elevation: {$elevation}
- Sunlight: {$sun}
- What grew there last: {$prevCrop}
- What has grown well there before: {$grewWell}
- Labor and machinery: {$labor}
- Budget for inputs: {$budget}
- Where the harvest would be sold (every outlet the farmer ticked): {$market}
- Field troubles the farmer reports: {$problems}
- The farmer's own notes: {$notes}
- Crops the farmer has in mind (each must be analysed and ranked, honestly): {$asked}
- Crop families the farmer does NOT want at all: {$excludedLine}. Families still in play: {$familiesLeft}.
- What matters most to the farmer, in their order, with the weight each carries in the score: {$prioLine}
{$ensoBlock}
GROUND RULES
- BE REALISTIC ABOUT THE PLACE. Recommend only crops that are actually grown commercially and sold in {$countryName}, and that really grow in the climate of the region and elevation named — the climate decides, not the wish list. Concretely: {$realism}. A crop that cannot grow in that climate is simply left out; it is not a recommendation and not padding for the avoid list.
- CANDIDATE POOL. Choose the top pick and every recommendation from these crops, which this app keeps a calendar for in {$countryName} (days to harvest in brackets): {$pool}. If a crop that is genuinely important in the region named is missing from the pool, you may add it only when it is really grown commercially there, and you must say so in dataGaps.
- THE FARMER'S OWN CROPS. Every crop listed under "crops the farmer has in mind" must appear in recommendations with farmerAsked true, scored and ranked as honestly as your own picks: if it fits this ground poorly, say so plainly in its why and rank it low — never drop it, never flatter it. A name there that is not a real crop grown in {$countryName} goes in avoid instead, with the reason.
- THE FARMER'S PRIORITIES. Score every crop on the six fits (fit.profit, fit.survival, fit.ease, fit.speed, fit.market, fit.food, each 0-100 for THIS ground and farmer), then make score the weighted average of those six using the weights given above (the farmer's first priority weighs most). Rank by score. When a priority decided a placing, say so in the why.
- HARVEST DAY. A fine planting window is not enough: the crop must also come off the field safely. For every recommendation give plantMonth (1-12, the month its window opens), daysToHarvest (days from planting to first harvest for this crop in this climate, from its real maturity — for a tree crop, months to first bearing times 30), harvestWindow in words (e.g. "late February – mid March"), harvestMonth (1-12, when most of the harvest lands), and harvestRisk: what the region's climate typically does in that harvest month — floods, typhoons/storms, drought, heat, frost — as score 0-100 (0 = calm, 100 = a harvest very likely lost or delayed), kind (one of "flood", "storm", "drought", "heat", "frost", "none") and a note of at most 20 words. A crop whose harvest lands in the flood or typhoon peak must say so, and that risk must pull down fit.survival and the score.
- monthRisk: twelve rows, January to December, each 0-100 for storm, flood, drought, heat and frost in the region named, from its climatological normals — the chart under the ranking draws every crop's growing bar and harvest against it. Be consistent: a crop's harvestRisk must agree with monthRisk in its harvestMonth.
- Reason only from established knowledge: {$climateRule}, the soil-water behaviour implied by the described soil and troubles, each candidate crop's real agronomic needs and calendar, and typical {$countryName} market/home-use patterns for the stated aim. No invented prices, no yield promises.
- Read the extra signals as an agronomist would, and say in each "why" which ones moved the pick: soil pH sets which crops tolerate the ground (acid-tolerant vs lime-loving); the LOOK of the irrigation water is a clue — muddy/brown carries silt (fine for paddy, clogs drip), cloudy white or milky suggests suspended lime/minerals or fine clay (check salinity and hardness before drip or sensitive vegetables), greenish means algae and nutrient load (watch clogging and disease), salty taste or a white crust means salinity (favor salt-tolerant crops), a bad smell or oily film means contamination (avoid leafy vegetables eaten raw); the lay and elevation set drainage, cold and cloud; sunlight rules out sun-loving crops in shade; the previous crop sets rotation (do not repeat a family that shares its pests and diseases); labor, budget and the market decide whether a labor-heavy, input-heavy or perishable crop is realistic. A skipped signal is "not stated" — do not guess it; name it in dataGaps if it would have changed the ranking.
- EXCLUDED FAMILIES. A family the farmer does not want never appears in recommendations, topPick or surprises — not one crop of it. The family-coverage rule below applies only to the families still in play. The crops the farmer named themselves are exempt: they asked, so they are ranked.
- Cover the families still in play honestly: where root crops and tree crops are in play, at least one strong root crop and one perennial/tree option must be CONSIDERED — recommended if they fit, or placed in avoid with the reason if they do not.
- SURPRISE ME. Besides the ranking, offer zero to three crops the farmer would not usually think of for this place — not commonly planted in the region named, perhaps not in {$countryName} at all — that this ground, this climate and their priorities nonetheless honestly argue for: it must really grow in this exact climate and season, be grown commercially somewhere with a like climate, and have a use or a buyer here (for example soybean or sorghum on a Philippine lowland where everyone plants rice, or a tropical fruit in a frost-free corner of a temperate country). Prefer the pool; you may go outside it. It must still obey the realism rule and the excluded families, and it must not repeat a ranked crop. Say plainly in each why what makes it unusual here and why it would work. When nothing honest qualifies, return an empty surprises list — an empty list is better than a forced one.
- Where the given facts cannot answer something (soil test values, exact microclimate, market access), name it in dataGaps instead of guessing.
- Be scientific and neutral: no seed brands, no product recommendations, no marketing tone.
- Write every "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"topPick":{"crop":"","category":"","why":"","window":"","daysToHarvest":0,"harvestWindow":"","harvestRisk":{"score":0,"kind":"none","note":""}},"recommendations":[{"rank":1,"crop":"","category":"","score":0,"farmerAsked":false,"window":"","plantMonth":0,"daysToHarvest":0,"harvestWindow":"","harvestMonth":0,"harvestRisk":{"score":0,"kind":"none","note":""},"fit":{"profit":0,"survival":0,"ease":0,"speed":0,"market":0,"food":0},"why":"","watch":""}],"monthRisk":[{"month":1,"storm":0,"flood":0,"drought":0,"heat":0,"frost":0}],"surprises":[{"crop":"","category":"","why":"","window":"","plantMonth":0,"daysToHarvest":0,"harvestWindow":"","harvestMonth":0,"harvestRisk":{"score":0,"kind":"none","note":""}}],"avoid":[{"crop":"","why":""}],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- surprises: zero to three, as defined above, each with the same harvest fields as a recommendation and a why of at most 45 words; [] when none.
- recommendations: TEN crops of your own choosing — the best ten, even where the tenth is only a modest fit, the score says how modest — plus every crop the farmer had in mind (so 10 + those), all in ONE ranking by score, best first, each with: category (one of "Grain", "Vegetable", "Root crop", "Legume", "Fruit / tree"), score 0-100 (the priority-weighted fit for THIS ground and start month), farmerAsked (true only for the farmer's own crops), window (when to plant it, specific — e.g. "mid May – early June"), plantMonth, daysToHarvest, harvestWindow, harvestMonth and harvestRisk as defined above, fit with all six numbers, why (≤ 35 words, grounded in the facts), watch (the one thing to watch for on this ground, ≤ 15 words). Fewer than ten of your own only when the pool truly has no more crops that could be grown on this ground at all.
- topPick repeats the rank-1 crop with a fuller why (≤ 60 words), its window, daysToHarvest, harvestWindow and harvestRisk.
- monthRisk: exactly twelve rows, month 1 to 12.
- avoid: two to four crops a farmer in this region might otherwise try — crops that DO grow in {$countryName} but that this ground, water, season or market argues against — each with the plain reason. Never fill it with crops from another climate that no farmer there would plant anyway.
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
