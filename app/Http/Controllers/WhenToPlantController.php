<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\CropCatalog;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * When to Plant — one deliberate analysis, bought with credits.
 *
 * The farmer answers a short wizard (year, season, crop, variety, place,
 * the field's known troubles) and the model is asked for a planting window
 * grounded in climatological pattern — typhoon seasonality, ENSO caution,
 * the crop's own calendar arithmetic — with its uncertainty said out loud.
 * The price is quoted BEFORE anything is spent, the charge goes through the
 * same ledger every AI question uses (so the subscription page's credit log
 * shows it), and a report worth keeping can be saved and later handed to
 * Anee as an attachment.
 */
class WhenToPlantController extends Controller
{
    /** The field troubles the wizard offers. Keys are stable; words can move. */
    public const PROBLEMS = [
        'floods' => 'Floods / standing water after rain',
        'cracking' => 'Cracking clay soil in the dry months',
        'sandy' => 'Sandy / fast-draining soil',
        'water_source' => 'Limited irrigation water source',
        'rainfed' => 'Rain-fed only (no irrigation)',
        'river' => 'Beside a river (overflow reaches the field)',
        'sea' => 'Near the sea (salt spray / brackish water)',
        'wind' => 'Strong winds pass through (typhoon corridor)',
        'pests' => 'Pests have been heavy in past seasons',
        'weeds' => 'Weed pressure is heavy',
        'drainage' => 'Poor drainage',
        'shade' => 'Part of the field is shaded',
    ];

    public const SEASONS = [
        'dry' => 'Dry season',
        'wet' => 'Wet season',
        'third' => 'Third crop (after the dry season)',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    /** The tier wall: the analyses are Anee's, and Anee comes with Libre + Anee and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('aiAnalyses')) {
            \App\Support\Tier::farmDenyFor('aiAnalyses', 'The When to Plant analysis comes with {plan}, and with every plan above it.');
        }
    }

    public function page()
    {
        $this->guardTier();
        return view('when-to-plant.index');
    }

    /** Everything the wizard needs, plus the standing price of one analysis. */
    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            // The whole crop book, in the farmer's words, as What to Plant
            // sends it: the page hides the temperate ones (`intl`) while the
            // FIELD is in the Philippines, and shows them for a field abroad.
            'crops' => collect(CropCatalog::CROPS)->map(fn ($c, $key) => [
                'key' => $key,
                'label' => CropCatalog::label($key),
                'icon' => $c['icon'] ?? '🌱',
                'group' => $c['group'] ?? 'Other',
                'maturity' => $c['maturity'] ?? null,
                'perennial' => CropCatalog::isPerennial($key),
                'intl' => ! empty($c['intl']),
            ])->values(),
            'problems' => self::PROBLEMS,
            'seasons' => \App\Support\Region::seasons(),
            // The farmer's own country: the field is there unless they say otherwise.
            'country' => \App\Support\Region::code(),
            'years' => range((int) now('Asia/Manila')->format('Y'), (int) now('Asia/Manila')->format('Y') + 2),
            'quote' => $canUse ? $this->quote($settings) : null,
            'aneeFace' => $settings->faceUrl(),
            'balance' => round($this->credits->balance($payer->id), 2),
            // A super admin's wallet has no floor — the note says infinity
            // rather than a zero that reads as empty.
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null
                : ($settings->isUsable()
                    ? 'This analysis runs on the AI Technician, which needs a Boss or Lifetime plan'
                        . ((int) $payer->id === (int) Auth::id() ? '.' : ' on the farm owner\'s account.')
                    : 'The AI Technician is not switched on yet. Please check back soon.'),
        ]]);
    }

    /** The house's flat price for one analysis, in credits: the owner's,
     *  on Anee's price list (App\Support\AiPrices, default 50); the
     *  metered cost (~19 on real runs) sits under it. */
    public const PRICE = AiPrices::DEFAULTS['wtp'];

    /**
     * The standing price, quoted before anything is spent — one number,
     * shown up front, so the farmer decides with it in hand. Flat, not
     * metered: the number said first is exactly the number charged.
     */
    private function quote(AiSetting $settings): float
    {
        return (float) AiPrices::of('wtp');
    }

    /** Run the analysis. Nothing is saved unless the farmer asks to keep it. */
    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'The analysis needs the AI Technician (Boss or Lifetime plan).', [], 403);
        }

        // The field may be in another country than the farmer: its seasons
        // are that country's, and so is the advice.
        $fieldCountry = \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code();
        $fieldSeasons = \App\Support\Region::as($fieldCountry, fn () => array_keys(\App\Support\Region::seasons()));
        $v = Validator::make($request->all(), [
            'year' => 'required|integer|min:' . now('Asia/Manila')->format('Y') . '|max:' . (now('Asia/Manila')->year + 2),
            'country' => 'nullable|string|size:2',
            'season' => 'required|in:' . implode(',', $fieldSeasons),
            'crop' => 'required|string',
            'variety' => 'nullable|string|max:80',
            'location' => 'required|string|max:160',
            'problems' => 'nullable|array',
            'problems.*' => 'string|in:' . implode(',', array_keys(self::PROBLEMS)),
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }
        if (! isset(CropCatalog::CROPS[$request->input('crop')])) {
            return $this->json(false, 'Pick a crop from the list.', [], 422);
        }

        $prompt = $this->prompt($request);

        // The wall asks for the number the farmer was TOLD, not the smaller
        // chat-sized estimate — a quote passed and then doubled is a lie.
        $balance = $this->credits->balance($payer->id);
        $estimate = $this->quote($settings);
        if ($balance < $estimate && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . ceil($estimate) . ' credits for this analysis and have '
                . number_format((int) floor($balance)) . '.',
                ['outOfCredits' => true], 402);
        }

        /* One in flight at a time: a double press must not buy two. A
         * standing job is simply handed back for the page to keep polling. */
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'when')
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
        $title = CropCatalog::label($p['crop']) . ' · ' . \App\Support\Region::seasonTitle($p['season'], (int) $p['year'], $p['country']) . ' · ' . $p['location'];
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'title' => mb_substr($title, 0, 190),
            'params' => json_encode($p),
            'report' => json_encode(new \stdClass),
            'credits' => 0,
            'status' => 'pending',
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /* THE GATEWAY MUST NOT WAIT ON THE MODEL. A hosted proxy times a
         * long request out — a chat answer slips under its limit, this
         * module's full JSON does not, and the farmer read that as "server
         * error". Under php-fpm the response leaves NOW, the connection is
         * handed back, and the model is asked afterwards while the page
         * polls the row. Where that hand-off does not exist (local mod_php)
         * the work runs inline — that host has 600 patient seconds. */
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

        @set_time_limit(600);
        $this->runJob($id, (int) $payer->id, $settings, $prompt, $p);

        return $this->jobState($id);
    }

    /** The model call and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt, array $p = []): void
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
            /* Off the request's clock, the job can afford patience a chat
             * cannot. Two asks (2026-09-17): first the web is read for the
             * region's twenty-year record of storms, droughts, floods and
             * heat (a search-backed research step -- asked for JSON the
             * model does not search, see AiClient::researchThenJson), then
             * the document is written with those notes appended. A
             * document-sized answer lane: the chat cap (1200) cut the JSON
             * mid-object on longer runs, which read as "unreadable". */
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p), $prompt, 4500, fn (string $t) => $this->parseReport($t), $beat);
            $report = $result['data'];
            if ($report === null) {
                // The head of what came back, kept where a debugger can read
                // it — the farmer just needs to know nothing was charged.
                \Illuminate\Support\Facades\Log::warning('when-to-plant: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException($result['error'] ?? 'The analysis came back unreadable. Nothing was charged — please try again.');
            }

            $report['webSources'] = (array) ($result['sources'] ?? []);
            $report['searched'] = (bool) ($result['searched'] ?? false);
            $report['researchNotes'] = mb_substr((string) ($result['researchText'] ?? ''), 0, 6000);

            // The charge lands through the same ledger every question uses,
            // so the subscription page's credit log shows it.
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $p = json_decode((string) ($row->params ?? '[]'), true) ?: [];
            $crop = CropCatalog::CROPS[$p['crop'] ?? ''] ?? ['label' => 'Crop'];
            // The flat price, exactly as quoted — never the meter's smaller
            // figure, and never a surprise above the number the farmer read.
            $charged = (float) AiPrices::of('wtp');
            $note = AiUsage::record('wtp', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('When-to-plant analysis — ' . $crop['label'] . ', ' . ($p['year'] ?? '') . $note, 0, 250));

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
            ->where('id', $id)->where('deleteStatus', 1)->first();
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
            // A failed job is not worth a place on the shelf.
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

    /** Keep one. */
    public function save(Request $request)
    {
        $v = Validator::make($request->all(), [
            'params' => 'required|array',
            'report' => 'required|array',
            'charged' => 'nullable|numeric|min:0',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Nothing to save yet — run the analysis first.', [], 422);
        }

        $p = (array) $request->input('params');
        $crop = CropCatalog::CROPS[$p['crop'] ?? ''] ?? null;
        $title = ($crop['label'] ?? 'Crop') . ' · ' . \App\Support\Region::seasonTitle((string) ($p['season'] ?? ''), (int) ($p['year'] ?? now('Asia/Manila')->year), $p['country'] ?? null)
            . ' · ' . ($p['location'] ?? '');

        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'title' => mb_substr($title, 0, 190),
            'params' => json_encode($p),
            'report' => json_encode($request->input('report')),
            'credits' => (float) $request->input('charged', 0),
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->json(true, 'Saved — it is on the Saved tab now.', ['id' => $id]);
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'when')
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')->orWhere('tags', 'like', '%' . $q . '%')))
            ->skip(($page - 1) * $per)->take($per + 1)
->get(['id', 'title', 'description', 'tags', 'credits', 'created_at']);

        $hasMore = $rows->count() > $per;
        $rows = $rows->take($per);

        return $this->json(true, 'ok', ['page' => $page, 'hasMore' => $hasMore, 'q' => $q, 'rows' => $rows->map(fn ($r) => [
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
            ->where('id', $id)->where('deleteStatus', 1)->where('status', 'ready')->first();
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

    /**
     * The analysis as chat context: what Anee reads when it is attached.
     * Static so AiController builds the identical text it was priced by.
     */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = DB::table('as_plant_analyses')->where('userId', $userId)
            ->where('id', $id)->where('kind', 'when')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return null;
        }
        $report = json_decode($r->report, true) ?: [];
        $params = json_decode($r->params, true) ?: [];
        $problems = collect($params['problems'] ?? [])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ');
        $bw = $report['bestWindow'] ?? [];
        $text = "\n\n--- ATTACHED: When-to-plant analysis (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Field problems considered: ' . ($problems ?: 'none') . "\n"
            . 'Recommended window: ' . ($bw['label'] ?? '') . ' — ' . ($bw['why'] ?? '') . "\n"
            . 'Timeline: ' . collect($report['timeline'] ?? [])->map(fn ($t) => ($t['stage'] ?? '') . ' ' . ($t['days'] ?? 0) . 'd')->implode(', ') . "\n"
            . 'Threats outside the window: ' . collect($report['threats'] ?? [])->map(fn ($t) => ($t['whenNot'] ?? '') . ': ' . ($t['threat'] ?? '') . ' (' . ($t['severity'] ?? '') . ')')->implode(' | ') . "\n"
            . 'Month scores (planting suitability 0-100): ' . collect($report['monthScores'] ?? [])->map(fn ($m) => ($m['month'] ?? '') . '=' . ($m['score'] ?? ''))->implode(' ') . "\n"
            . 'Summary: ' . ($report['summary'] ?? '') . "\n"
            . 'Stated confidence: ' . ($report['confidence'] ?? '') . '; data gaps: ' . collect($report['dataGaps'] ?? [])->implode('; ')
            . "\n--- END OF ATTACHED ANALYSIS ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    /** Weighed for the composer, the way a plan is before it is attached. */
    public function preview(int $id)
    {
        $ctx = self::contextFor($id, (int) Auth::id())
            ?? \App\Http\Controllers\WhatToPlantController::contextFor($id, (int) Auth::id())
            ?? \App\Http\Controllers\VarietyAnalysisController::contextFor($id, (int) Auth::id())
            ?? \App\Http\Controllers\CropProtocolController::contextFor($id, (int) Auth::id());
        if (! $ctx) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }

        return $this->json(true, 'ok', [
            'id' => $id,
            'title' => $ctx['title'],
            'tokens' => (int) ceil(mb_strlen($ctx['text']) / 4),
        ]);
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)
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
            ->where('kind', 'when')
            ->where('id', (int) $request->input('id'))
            ->where('deleteStatus', 1)
            ->update([
                'title' => $title,
                'description' => $description !== '' ? mb_substr($description, 0, 2000) : null,
                'tags' => json_encode(\App\Http\Controllers\UserTagController::tidy($request->input('tags', []))),
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
            'year' => (int) $request->input('year'),
            'season' => $request->input('season'),
            'crop' => $request->input('crop'),
            'variety' => trim((string) $request->input('variety', '')),
            'location' => trim((string) $request->input('location')),
            'problems' => array_values((array) $request->input('problems', [])),
            'country' => \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code(),
        ];
    }

    /**
     * The research brief: the region's twenty-year record of the things
     * that ruin a planting, read from the web before the document is
     * written. Prose, not JSON -- asked for JSON the model does not search.
     */
    private function researchPrompt(array $p): string
    {
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $country = \App\Support\Region::name($fc);
        $met = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('met'));
        $sources = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('sources'));
        $storms = $fieldPH
            ? 'tropical cyclones (typhoons and tropical storms) that made landfall in or passed close enough to damage crops in the province or region'
            : 'severe storms that damaged crops in the region (hurricanes or tropical storms where they occur, plus severe thunderstorms, hail, tornadoes and damaging wind events)';
        $frost = $fieldPH ? '' : "\n6. Frost: the average first and last frost dates for the area and the years a late spring or early autumn frost damaged crops.";
        $to = (int) now('Asia/Manila')->year;
        $from = $to - 20;
        $cropLabel = CropCatalog::label($p['crop']);
        // The variety, when the farmer named one: its published traits are
        // looked up in the same search pass, so the document can time the
        // crop by what this variety actually does rather than the crop's
        // typical figures.
        $variety = trim((string) ($p['variety'] ?? ''));
        $registry = $fieldPH
            ? 'the NSIC / PhilRice / IRRI registration and seed catalogues, the seed company\'s own page, DA and university extension notes'
            : 'the breeder\'s or seed company\'s own page, the national variety registry, university extension notes';
        $varietyAsk = $variety !== '' ? "\n0. THE VARIETY FIRST: \"{$variety}\" of {$cropLabel}. Search for its published characteristics: days to maturity (or harvest), yield potential, plant height and lodging, the season it is bred for (wet / dry, early / late), its tolerance to drought, flooding or submergence, heat, salinity and cold where they apply, its pest and disease resistance (for rice: blast, bacterial leaf blight, tungro, brown planthopper, stem borer; for other crops the ones that matter), and who released it and when. Prefer {$registry}. Give the figures with their source. If nothing reliable turns up, say plainly that the variety could not be found — never guess its traits.\n" : '';
        $varietyHead = $variety !== '' ? ' and on the variety the farmer named' : '';

        return <<<PROMPT
You are a research assistant for agronomy in {$country} with web search. SEARCH THE WEB NOW and write research notes on the climate RISK RECORD of one place over the past twenty years ({$from}–{$to}){$varietyHead}. Do not answer from memory alone; every finding must come from a page you read, with the source name and year beside it.

THE PLACE
- {$p['location']}, {$country}. The farmer will plant {$cropLabel} there and needs to know, month by month, what has historically gone wrong.

FIND, IN THIS ORDER{$varietyAsk}
1. Storms: the {$storms}, {$from}–{$to}: for each, the year, the month, the name where it has one, and the damage to agriculture where reported (hectares, pesos or dollars, or a plain word like severe / moderate).
2. Drought and dry spells: the El Niño years and other drought years that hurt crops in this region in the same span, with the months affected and the damage reported.
3. Floods: flooding events (from storms, monsoon rains or river overflow) that damaged crops there, with year, month and damage.
4. Heat: heat waves or hot dry spells that damaged crops or stressed them at flowering, with year and months.
5. The region's monthly rainfall and temperature normals, and its usual wet and dry (or growing and dormant) months, from {$met}.{$frost}

THEN TALLY: for each of the twelve months, how many of the twenty years had a damaging event of each kind in that month, and how bad they tended to be. List the five worst years for this place and what happened. Where the record is thin or you could not find it, say so plainly.

Prefer {$sources}, disaster databases (EM-DAT, ReliefWeb, NDRRMC / national disaster agencies), the national statistics office's crop-damage reports, and reputable news archives. Write plain prose notes under the headings, at most 1100 words, no JSON, no markdown tables.
PROMPT;
    }

    /**
     * The question, spelled out so the answer is bounded: known facts in,
     * uncertainty said out loud, strict JSON back, no invention and no bias.
     */
    private function prompt(Request $request): string
    {
        $p = $this->params($request);
        $crop = CropCatalog::CROPS[$p['crop']];
        // stages() rows are positional: [startDay, label, what, care]. Some
        // crops keep hand-written tables instead and return [] — the model
        // then works from maturity alone, which is honest.
        $stages = collect(CropCatalog::stages($p['crop']))
            ->map(fn ($s) => ($s[1] ?? 'stage') . ' from day ' . (int) ($s[0] ?? 0))
            ->implode('; ') ?: 'not tabulated — use typical stages for this crop';
        $problems = collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported';
        $maturity = CropCatalog::maturity($p['crop']);
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';
        $ensoRule = $enso !== ''
            ? 'the observed ENSO state AND the official NOAA CPC forecast given above (weigh its stated probabilities toward the planting window rather than assuming neutral conditions, and say plainly where the forecast still leaves uncertainty)'
            : 'general ENSO behaviour — state plainly that you cannot know the live ENSO state for ' . $p['year'] . ' and mark it as uncertainty rather than inventing a forecast';

        // The field's country, which may not be the farmer's: its name, its
        // weather authority, its seasons; the language stays the farmer's.
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $countryName = \App\Support\Region::name($fc);
        $regionBlock = \App\Support\Region::promptBlock($fc, \App\Support\Region::code());
        $cropLabel = CropCatalog::label($p['crop']);
        $met = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('met'));
        $climateRule = $fieldPH
            ? 'PAGASA climatological normals (wet/dry season timing for the region named), historical tropical-cyclone seasonality in the Philippines (including the Aug–Oct peak and regional differences)'
            : 'the climatological normals for the region named as published by ' . $met . ' (frost dates and the growing season where they apply, rainfall and temperature timing), the historical severe-weather seasonality of that region (storms, heat, drought, floods)';
        $crossNote = $fieldPH
            ? 'which for the dry season runs from the end of ' . $p['year'] . ' into the first months of the following year'
            : 'which may cross into the following year for a winter or cool-season planting';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$countryName}. Recommend when to PLANT for the farmer's chosen cropping season, for the case below. The season's own span is given with its years — plant within THAT span, {$crossNote}.

FACTS GIVEN
- {$regionBlock}
- Target season: {$this->seasonWords($p['season'], (int) $p['year'], $fc)}
- Crop: {$cropLabel} — typical days to maturity: {$maturity}
- Growth stages for calendar arithmetic: {$stages}
- Stated variety: "{$p['variety']}" — the research notes at the end carry what the web says about it (maturity, season it is bred for, tolerance to drought / flood / heat / cold, pest and disease resistance, who released it). USE those published traits: time the crop by the variety's own days to maturity where the notes give one, and let its tolerances and weaknesses move the windows, the month scores and the threats (a submergence-tolerant variety fears the flood month less; an early-maturing one can dodge it). Where the notes say the variety could not be found, say variety-specific data is unavailable in dataGaps and reason from the crop's typical range. Never invent varietal traits, and never use a trait the notes do not carry.
- Location as the farmer wrote it: {$p['location']}
- Field problems the farmer reports: {$problems}
{$ensoBlock}
GROUND RULES
- Research notes gathered from the web just now follow at the end of this brief: the place's twenty-year record of storms, droughts, floods and heat, month by month, with its worst years. Rely on them first for riskHistory and for the avoid windows; where they and memory disagree, the notes win; where they are silent, say so in dataGaps.
- Reason only from established knowledge: {$climateRule}, {$ensoRule}, soil-water behaviour implied by the reported problems, and the crop calendar arithmetic above.
- Where the given facts cannot answer something (exact distance to river or sea, microclimate, irrigation reliability), name it in dataGaps instead of guessing.
- Be scientific and neutral: no product recommendations, no marketing tone, no bias toward any input or brand.
- Write the summary and the "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"bestWindow":{"fromMonth":1,"fromDay":1,"fromYear":2026,"toMonth":1,"toDay":1,"toYear":2026,"label":"","why":""},"avoidWindows":[{"fromMonth":1,"fromDay":1,"fromYear":2026,"toMonth":1,"toDay":1,"toYear":2026,"label":"","why":"","severity":"high"}],"monthScores":[{"month":1,"year":2026,"score":0,"note":""}],"riskHistory":{"years":"","months":[{"month":1,"storm":0,"flood":0,"drought":0,"heat":0,"frost":0,"note":""}],"events":[{"year":2013,"month":11,"kind":"storm","what":"","impact":"high"}],"note":""},"threats":[{"whenNot":"","threat":"","severity":"low"}],"variety":{"found":false,"name":"","maturityDays":0,"season":"","traits":"","caution":"","source":""},"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- variety: what the research found about the stated variety — found true only when the notes carry real published traits; name as published; maturityDays (0 when unknown); season it is bred for in a few words; traits ≤ 60 words in plain words (yield, height, tolerances, resistances, and how they shaped this timing); caution ≤ 25 words (its known weakness on this ground, or ""); source the registry, breeder or agency the notes cite. When the farmer named no variety, or none was found: found false and the rest empty or 0.
- bestWindow must be a SPECIFIC, actionable range of roughly 2–6 weeks with explicit dates, and its label must spell the dates out WITH THE YEAR (e.g. "Dec 10, 2026 – Jan 5, 2027") — NEVER a season name or a whole season. fromYear/toYear carry the calendar year of each end; for the dry season the window may begin in {$p['year']} and end in the year after, or sit wholly in the year after.
- avoidWindows: one to three ranges to KEEP AWAY FROM, each specific to the month and week and year (e.g. "Late July – mid October 2026") and grounded in the named region's historical typhoon/climate pattern; why says what historically happens there then; severity "moderate" or "high".
- monthScores carries ALL twelve months of the season's own run, each with its year (for a season that crosses into the next year — the dry season at home, a winter/cool-season planting elsewhere: its first month with its year, then the eleven months after; for the others: January–December {$p['year']}); score 0–100 = how suitable STARTING to plant that month is; note ≤ 10 words. Differentiate months even inside the target season — a flat run of equal scores is an unfinished answer.
- riskHistory: the place's twenty-year record from the research notes. years = the span read (e.g. "2006–2025"). months = ALL twelve calendar months (month 1–12, calendar order); for each kind — storm (typhoons/hurricanes/severe storms), flood, drought, heat, frost — a 0–100 DAMAGE INDEX for that month: how often a damaging event of that kind struck in that month over the twenty years, weighted by how bad it was (0 = never, 100 = most years and severe); frost is 0 where it does not occur; note ≤ 12 words on the month. events = the four to eight worst events found, each with year, month, kind, what (≤ 14 words, name the storm where it has one) and impact "high"/"moderate". note ≤ 40 words on how the record was read and how thin it is. Never invent an event; a month with no record scores 0 and says so.
- threats: at most three, what the farmer risks by planting OUTSIDE bestWindow, each naming when; severity "low"/"moderate"/"high"; confidence "low"/"moderate"/"high"; dataGaps at most three; summary ≤ 90 words. Keep the whole answer tight.
PROMPT;
    }

    /**
     * The season's span, with its years spelt out.
     *
     * The dry season chosen for a year is the one that BEGINS at the end of
     * it: early December of that year through May of the next. A farmer who
     * picks "Dry season 2026" may well plant in January 2027, and the
     * analysis has to say so rather than fold everything into one calendar
     * year (the owner's rule, 2026-09-15). The wet season sits inside its
     * own year; the third crop is the gap after it.
     */
    /** The season spelled out with its months — the field's country's own (App\Support\Region). */
    private function seasonWords(string $key, int $year, ?string $country = null): string
    {
        return \App\Support\Region::as($country ?: \App\Support\Region::code(), fn () => \App\Support\Region::seasonWords($key, $year));
    }

    /** The season named with its years, for titles: "Dry season 2026–27". */
    public static function seasonTitle(string $key, int $year): string
    {
        return \App\Support\Region::seasonTitle($key, $year);
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
        if (! is_array($json) || ! isset($json['bestWindow'], $json['monthScores'], $json['summary'])) {
            return null;
        }
        // Twelve months or the chart lies by omission.
        if (! is_array($json['monthScores']) || count($json['monthScores']) < 12) {
            return null;
        }

        // The persona's emoji shortcodes have no renderer here — swept out
        // of the prose fields so :anee-…: never reaches a farmer raw.
        $sweep = fn ($v) => is_string($v) ? trim(preg_replace('/:[a-z0-9_-]+:/i', '', $v)) : $v;
        $json['summary'] = $sweep($json['summary'] ?? '');
        if (isset($json['bestWindow']['why'])) {
            $json['bestWindow']['why'] = $sweep($json['bestWindow']['why']);
        }

        return $json;
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
