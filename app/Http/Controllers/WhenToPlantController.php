<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
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
        'third' => 'Third crop (in between)',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    public function page()
    {
        return view('when-to-plant.index');
    }

    /** Everything the wizard needs, plus the standing price of one analysis. */
    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'crops' => collect(CropCatalog::CROPS)->map(fn ($c, $key) => [
                'key' => $key,
                'label' => $c['label'],
                'icon' => $c['icon'],
                'group' => $c['group'],
                'maturity' => $c['maturity'] ?? null,
                'perennial' => CropCatalog::isPerennial($key),
            ])->values(),
            'problems' => self::PROBLEMS,
            'seasons' => self::SEASONS,
            'years' => range((int) now('Asia/Manila')->format('Y'), (int) now('Asia/Manila')->format('Y') + 2),
            'quote' => $canUse ? $this->quote($settings) : null,
            'aneeFace' => $settings->faceUrl(),
            'balance' => round($this->credits->balance($payer->id), 2),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null
                : ($settings->isUsable()
                    ? 'This analysis runs on the AI Technician, which needs a Boss or Lifetime plan'
                        . ((int) $payer->id === (int) Auth::id() ? '.' : ' on the farm owner\'s account.')
                    : 'The AI Technician is not switched on yet. Please check back soon.'),
        ]]);
    }

    /**
     * The standing price, quoted before anything is spent. Measured with the
     * same estimate the wall enforces, over a stand-in for the real prompt —
     * one number, shown up front, so the farmer decides with it in hand.
     */
    private function quote(AiSetting $settings): float
    {
        /* estimate() budgets a chat-sized answer; this module's answer is a
         * full JSON report — twelve scored months, a staged timeline, the
         * threats — which measured ~2.7× that on real runs. Quoted at the
         * measured weight, so the number said first is the number charged,
         * near enough. */
        return ceil($this->credits->estimate($settings, str_repeat('x', 3600)) * 2.7);
    }

    /** Run the analysis. Nothing is saved unless the farmer asks to keep it. */
    public function generate(Request $request)
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'The analysis needs the AI Technician (Boss or Lifetime plan).', [], 403);
        }

        $v = Validator::make($request->all(), [
            'year' => 'required|integer|min:' . now('Asia/Manila')->format('Y') . '|max:' . (now('Asia/Manila')->year + 2),
            'season' => 'required|in:' . implode(',', array_keys(self::SEASONS)),
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
            return $this->json(false, 'You need about ' . ceil($estimate) . ' credits for this analysis and have '
                . rtrim(rtrim(number_format($balance, 2), '0'), '.') . '.',
                ['outOfCredits' => true], 402);
        }

        /* One in flight at a time: a double press must not buy two. A
         * standing job is simply handed back for the page to keep polling. */
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        $p = $this->params($request);
        $crop = CropCatalog::CROPS[$p['crop']];
        $title = $crop['label'] . ' · ' . (self::SEASONS[$p['season']] ?? '') . ' ' . $p['year'] . ' · ' . $p['location'];
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
            /* Off the request's clock, the job can afford patience a chat
             * cannot: a transport blip (the provider timing out once) gets a
             * second try before the job is called failed. */
            $result = $this->ai->ask($settings, [], $prompt);
            if (! ($result['ok'] ?? false)) {
                sleep(3);
                $result = $this->ai->ask($settings, [], $prompt);
            }
            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException($result['error'] ?? 'The AI could not be reached. Nothing was charged.');
            }

            $report = $this->parseReport((string) $result['text']);
            if ($report === null) {
                // One polite retry. History turns carry 'text', not
                // 'content' — every provider branch reads $turn['text'].
                $retry = $this->ai->ask($settings, [
                    ['role' => 'user', 'text' => $prompt],
                    ['role' => 'assistant', 'text' => (string) $result['text']],
                ], 'That was not valid JSON. Return ONLY the JSON object described, with no fences and no commentary.');
                if ($retry['ok'] ?? false) {
                    $report = $this->parseReport((string) $retry['text']);
                    $result['tokensIn'] += (int) ($retry['tokensIn'] ?? 0);
                    $result['tokensOut'] += (int) ($retry['tokensOut'] ?? 0);
                }
            }
            if ($report === null) {
                throw new \RuntimeException('The analysis came back unreadable. Nothing was charged — please try again.');
            }

            // The charge lands through the same ledger every question uses,
            // so the subscription page's credit log shows it.
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $p = json_decode((string) ($row->params ?? '[]'), true) ?: [];
            $crop = CropCatalog::CROPS[$p['crop'] ?? ''] ?? ['label' => 'Crop'];
            $charged = $this->credits->priceFor($settings, (int) $result['tokensIn'], (int) $result['tokensOut']);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                'When-to-plant analysis — ' . $crop['label'] . ', ' . ($p['year'] ?? ''));

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
            ->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending']);
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
        $title = ($crop['label'] ?? 'Crop') . ' · ' . (self::SEASONS[$p['season'] ?? ''] ?? '') . ' ' . ($p['year'] ?? '')
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

    public function list()
    {
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            ->get(['id', 'title', 'credits', 'created_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
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
            ->where('id', $id)->where('deleteStatus', 1)->where('status', 'ready')->first();
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
        $ctx = self::contextFor($id, (int) Auth::id());
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
        ];
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
        $enso = $this->ensoFacts();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';
        $ensoRule = $enso !== ''
            ? 'the observed ENSO state given above (project it toward the planting window only with typical persistence and decay, and say plainly how uncertain that projection is)'
            : 'general ENSO behaviour — state plainly that you cannot know the live ENSO state for ' . $p['year'] . ' and mark it as uncertainty rather than inventing a forecast';

        return <<<PROMPT
You are an agronomic decision-support analyst for Philippine farming. Recommend when to PLANT within the year {$p['year']}, aiming at the farmer's chosen cropping season, for the case below.

FACTS GIVEN
- Target season: {$this->seasonWords($p['season'])}
- Crop: {$crop['label']} — typical days to maturity in Philippine practice: {$maturity}
- Growth stages for calendar arithmetic: {$stages}
- Stated variety: "{$p['variety']}" — use published characteristics of this variety ONLY if you genuinely know them; otherwise say variety-specific data is unavailable in dataGaps and reason from the crop's typical range. Never invent varietal traits.
- Location as the farmer wrote it: {$p['location']}
- Field problems the farmer reports: {$problems}
{$ensoBlock}
GROUND RULES
- Reason only from established knowledge: PAGASA climatological normals (wet/dry season timing for the region named), historical tropical-cyclone seasonality in the Philippines (including the Aug–Oct peak and regional differences), {$ensoRule}, soil-water behaviour implied by the reported problems, and the crop calendar arithmetic above.
- Where the given facts cannot answer something (exact distance to river or sea, microclimate, irrigation reliability), name it in dataGaps instead of guessing.
- Be scientific and neutral: no product recommendations, no marketing tone, no bias toward any input or brand.
- Write the summary and the "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"bestWindow":{"fromMonth":1,"fromDay":1,"toMonth":1,"toDay":1,"label":"","why":""},"avoidWindows":[{"fromMonth":1,"fromDay":1,"toMonth":1,"toDay":1,"label":"","why":"","severity":"high"}],"monthScores":[{"month":1,"score":0,"note":""}],"threats":[{"whenNot":"","threat":"","severity":"low"}],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- bestWindow must be a SPECIFIC, actionable range of roughly 2–6 weeks with explicit dates, and its label must spell the dates out (e.g. "May 10 – June 5") — NEVER a season name or a whole season.
- avoidWindows: one to three ranges to KEEP AWAY FROM, each specific to the month and week (e.g. "Late July – mid October") and grounded in the named region's historical typhoon/climate pattern; why says what historically happens there then; severity "moderate" or "high".
- monthScores carries ALL twelve months (score 0–100 = how suitable STARTING to plant that month is; note ≤ 10 words). Differentiate months even inside the target season — a flat run of equal scores is an unfinished answer.
- threats: at most three, what the farmer risks by planting OUTSIDE bestWindow, each naming when; severity "low"/"moderate"/"high"; confidence "low"/"moderate"/"high"; dataGaps at most three; summary ≤ 90 words. Keep the whole answer tight.
PROMPT;
    }

    /**
     * The observed ENSO state, read from NOAA CPC's public ONI table and
     * cached for a day. A FACT for the prompt, not a forecast: the model is
     * told what the ocean has actually been doing and may project forward
     * only with stated uncertainty. Falls back to '' quietly — the analysis
     * then runs its honest-ignorance wording instead.
     */
    private function ensoFacts(): string
    {
        try {
            return Cache::remember('wtp-oni-facts', 86400, function () {
                $txt = Http::timeout(6)->get('https://www.cpc.ncep.noaa.gov/data/indices/oni.ascii.txt')->body();
                $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
                $parsed = [];
                foreach (array_slice($rows, -4) as $r) {
                    $p = preg_split('/\s+/', $r);
                    if (count($p) >= 4 && is_numeric($p[3])) {
                        $parsed[] = $p[0] . ' ' . $p[1] . ' anomaly ' . $p[3] . '°C';
                    }
                }
                if (! $parsed) {
                    return '';
                }
                preg_match('/(-?\d+(?:\.\d+)?)°C$/', end($parsed), $m);
                $last = (float) ($m[1] ?? 0);
                $state = $last >= 0.5 ? 'El Niño conditions'
                    : ($last <= -0.5 ? 'La Niña conditions' : 'ENSO-neutral conditions');

                return 'Observed ENSO state (NOAA CPC ONI, 3-month running anomalies, most recent last): '
                    . implode('; ', $parsed) . ' — i.e. currently ' . $state . '.';
            });
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function seasonWords(string $key): string
    {
        return match ($key) {
            'dry' => 'Dry season (roughly November–April in most lowland PH regions)',
            'wet' => 'Wet season (roughly May–October in most lowland PH regions)',
            default => 'Third crop — the in-between window after the main two',
        };
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
