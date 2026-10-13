<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * The LCC Checker (2026-10-14): the leaf color chart, read for the farmer.
 *
 * The leaf color chart is PhilRice and IRRI's way to time nitrogen on rice:
 * from 14 days after transplanting (21 after sowing for direct wet seeded
 * rice) and every week until the first flowers, read the youngest fully
 * opened leaf of 10 hills against the chart's green panels; when more than
 * half read below the critical panel (4 for transplanted rice, 3 for direct
 * wet seeded), top dress about 23 kg of nitrogen per hectare in the wet
 * season or 30 kg in the dry season.
 *
 * Two ways in. A farmer with a chart taps each leaf's panel: free, the rule
 * above does the rest. Without a chart, they photograph the leaves on white
 * paper and Anee reads the color (AiPrices 'lcc'): the page measures each
 * photo's dark green color index (DGCI) itself and gives it to her beside
 * the pictures, she names each leaf's panel and anything other than
 * nitrogen that could be yellowing it, and this code, not the model, decides
 * from her panels whether nitrogen is due and how much urea that is for
 * the farmer's area.
 *
 * Kept on the analyses shelf (as_plant_analyses, kind 'lcc'); the photos are
 * never stored, only what was read from them.
 */
class LccCheckerController extends Controller
{
    public const KIND = 'lcc';

    /** The chart's panels a reading may take, halves for a leaf between two. */
    public const PANELS = [2, 2.5, 3, 3.5, 4, 4.5, 5];

    public const METHODS = ['tpr' => 'Transplanted', 'dsr' => 'Direct seeded (wet)'];

    public const MATURITY = ['early' => 'Early, about 100 to 110 days', 'medium' => 'Medium, about 111 to 120 days', 'late' => 'Late, about 121 to 130 days'];

    public function page()
    {
        return view('lcc-checker.index');
    }

    public function options()
    {
        $payer = $this->payer();
        $credits = app(AiCreditService::class);
        $settings = AiSetting::current();
        $month = (int) now('Asia/Manila')->format('n');

        return $this->json(true, 'ok', [
            'price' => AiPrices::of(self::KIND),
            'balance' => round((float) $credits->balance($payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'canUse' => Tier::farmCan('aiAnalyses') && $payer->canUseAi() && $settings->isUsable(),
            'whyNot' => ! Tier::farmCan('aiAnalyses') ? 'Anee reading the photos comes with Libre + Anee and every plan above it. Reading your own chart stays free.'
                : (! $payer->canUseAi() ? Tier::aneeNeeds($payer, 'The photo reading') : (! $settings->isUsable() ? 'Anee is not switched on yet. Please check back soon.' : null)),
            'aneeFace' => $settings->faceUrl(),
            'panels' => self::PANELS,
            'methods' => self::METHODS,
            'maturity' => self::MATURITY,
            'season' => ($month >= 5 && $month <= 10) ? 'wet' : 'dry',
            'history' => $this->history(),
        ]);
    }

    /**
     * The rule, from panels: is nitrogen due, and how much. The decision is
     * this arithmetic, never the model's.
     */
    public static function decide(array $panels, string $method, string $season, ?int $days, ?string $maturity, float $areaHa): array
    {
        $panels = array_values(array_filter(array_map('floatval', $panels), fn ($p) => $p >= 1 && $p <= 6));
        $critical = $method === 'dsr' ? 3.0 : 4.0;
        $n = count($panels);
        $below = count(array_filter($panels, fn ($p) => $p < $critical));
        $start = $method === 'dsr' ? 21 : 14;
        // Flowering comes about 30 days before harvest; after it, no more nitrogen.
        $flower = ['early' => 75, 'medium' => 85, 'late' => 95][$maturity ?? ''] ?? null;
        $kgN = $season === 'dry' ? 30 : 23;
        $urea = (int) round($kgN / 0.46);
        $stage = 'on';
        if ($days !== null && $days < $start) {
            $stage = 'early';
        } elseif ($days !== null && $flower && $days >= $flower) {
            $stage = 'late';
        }
        $due = $stage === 'on' && $n > 0 && $below > $n / 2;
        $area = max(0.0001, $areaHa);

        return [
            'critical' => $critical,
            'leaves' => $n,
            'below' => $below,
            'average' => $n ? round(array_sum($panels) / $n, 1) : null,
            'stage' => $stage,
            'startDay' => $start,
            'flowerDay' => $flower,
            'due' => $due,
            'kgN' => $kgN,
            'ureaPerHa' => $urea,
            'bagsPerHa' => round($urea / 50, 1),
            'areaHa' => round($area, 4),
            'ureaForArea' => (int) round($urea * $area),
            'bagsForArea' => round($urea * $area / 50, 1),
            'nextCheck' => now('Asia/Manila')->addDays(7)->format('D, M j'),
        ];
    }

    /** A reading from the farmer's own chart: free, saved, no Anee. */
    public function manual(Request $request)
    {
        $v = $this->validateCase($request, ['panels' => 'required|array|min:3|max:12', 'panels.*' => 'numeric|in:' . implode(',', self::PANELS)]);
        if ($v) {
            return $v;
        }
        $case = $this->case($request);
        $decision = self::decide((array) $request->input('panels'), $case['method'], $case['season'], $case['days'], $case['maturity'], $case['areaHa']);
        $report = ['mode' => 'chart', 'decision' => $decision, 'panels' => array_map('floatval', (array) $request->input('panels')),
            'at' => now('Asia/Manila')->format('M j, Y g:i A')];
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($this->title($case, $decision), 0, 190),
            'params' => json_encode($case), 'report' => json_encode($report), 'credits' => 0, 'status' => 'ready', 'deleteStatus' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->json(true, 'Saved.', ['status' => 'ready', 'id' => $id, 'report' => $report, 'params' => $case, 'history' => $this->history()]);
    }

    /** Anee reads the leaf photos (one picture of up to 12 numbered tiles). */
    public function generate(Request $request)
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Anee reading your leaf photos comes with {plan}, and with every plan above it. Reading your own chart stays free.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'The photo reading'), [], 403);
        }
        $v = $this->validateCase($request, [
            'image' => 'required|string|max:4500000',
            'leaves' => 'required|integer|min:3|max:12',
            'dgci' => 'nullable|array|max:12',
            'dgci.*' => 'nullable|numeric|min:0|max:1',
        ]);
        if ($v) {
            return $v;
        }
        if (! preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#', (string) $request->input('image'), $m)) {
            return $this->json(false, 'The photos did not come through. Please add them again.', [], 422);
        }
        $price = (float) AiPrices::of(self::KIND);
        $credits = app(AiCreditService::class);
        if ($credits->balance($payer->id) < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->json(false, 'You need ' . ceil($price) . ' credits for this reading and have ' . number_format((int) floor($credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(8))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }
        $case = $this->case($request) + [
            'leaves' => (int) $request->input('leaves'),
            'dgci' => array_map(fn ($x) => $x === null ? null : round((float) $x, 3), array_slice((array) $request->input('dgci', []), 0, 12)),
        ];
        $image = ['mime' => 'image/' . $m[1], 'data' => $m[2]];
        $rid = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => 'Leaf color check · ' . now('Asia/Manila')->format('M j'), 'params' => json_encode($case),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $rid]])->send();
            fastcgi_finish_request();
            $this->run($rid, (int) $payer->id, $settings, $case, $image);
            exit;
        }
        @set_time_limit(300);
        $this->run($rid, (int) $payer->id, $settings, $case, $image);

        return $this->job($rid);
    }

    private function run(int $id, int $payerId, AiSetting $settings, array $case, array $image): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('reading');
            $result = app(AiClient::class)->askForJson($settings->forField('PH'), $this->prompt($case), 3000, fn (string $t) => $this->parse($t, (int) $case['leaves']),
                ['image' => $image, 'onPhase' => $beat]);
            if ($result['data'] === null) {
                Log::warning('lcc: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The reading could not be read. Nothing was charged. Please try again.');
            }
            $anee = $result['data'];
            $panels = array_values(array_filter(array_map(fn ($l) => $l['panel'] ?? null, $anee['leaves']), fn ($p) => $p !== null));
            $decision = self::decide($panels, $case['method'], $case['season'], $case['days'], $case['maturity'], $case['areaHa']);
            $price = (float) AiPrices::of(self::KIND);
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            app(AiCreditService::class)->chargeAllowingNegative($payerId, $price, mb_substr('LCC Checker: ' . count($panels) . ' leaves' . $note, 0, 250));
            $report = ['mode' => 'anee', 'decision' => $decision, 'panels' => $panels, 'anee' => $anee, 'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            DB::table('as_plant_analyses')->where('id', $id)->update(['title' => mb_substr($this->title($case, $decision), 0, 190), 'report' => json_encode($report),
                'credits' => round($price, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    private function prompt(array $case): string
    {
        $dg = [];
        foreach ($case['dgci'] ?? [] as $i => $x) {
            if ($x !== null) {
                $dg[] = 'leaf ' . ($i + 1) . ': ' . $x;
            }
        }
        $critical = $case['method'] === 'dsr' ? 3 : 4;

        return 'You are Anee, a smart farm technician for rice farmers in the Philippines, reading a farmer\'s rice leaves the way a leaf color chart (LCC) is read. '
            . 'The picture holds ' . $case['leaves'] . ' numbered tiles, one leaf each, photographed by the farmer (ideally on white paper, shaded). '
            . "The standard 4 panel LCC: panel 2 is a pale yellowish green, 3 a light green, 4 a medium green, 5 a deep dark green. A leaf between two panels reads as the half (for example 3.5).\n\n"
            . 'The case: ' . self::METHODS[$case['method']] . ' rice, ' . $case['season'] . ' season'
            . ($case['days'] !== null ? ', ' . $case['days'] . ' days after ' . ($case['method'] === 'dsr' ? 'sowing' : 'transplanting') : ', crop age not given')
            . ($case['variety'] !== '' ? ', variety ' . $case['variety'] : '') . '. The critical panel for this crop is ' . $critical . ".\n"
            . ($dg ? 'The app measured each photo\'s dark green color index (DGCI, 0 to 1, higher is darker green; it varies with light, so weigh it as relative): ' . implode('; ', $dg) . ".\n" : '')
            . ($case['notes'] !== '' ? 'The farmer adds: ' . $case['notes'] . "\n" : '')
            . "\nRULES: Give each tile the LCC panel its leaf matches (2, 2.5, 3, 3.5, 4, 4.5 or 5), reading the middle of the leaf blade, not the tip or the edge. "
            . 'If a tile is not a rice leaf, is badly lit, blurred, or shows mostly something else, give panel null and say why. '
            . 'Then look for anything other than nitrogen that could change the color: insect damage, disease spots or lesions (blast, bacterial leaf blight, brown spot, tungro), '
            . 'potassium shortage (yellow to brown tips and edges on older leaves), zinc shortage (dusty brown spots, stunted), sulfur, drought or waterlogging, and say plainly if the leaves look healthy. '
            . 'Do NOT decide whether to apply fertilizer: the app does that from your panels. Plain English with the odd Tagalog farm word where natural. No emoji. '
            . "Never invent what you cannot see.\n\n"
            . 'Return ONE JSON object only: {"leaves":[{"n":1,"panel":4,"note":"short, only if something stands out"}],'
            . '"photoQuality":"good|fair|poor","photoNote":"one line on the light, background and focus, with one tip to do better",'
            . '"otherCauses":[{"cause":"short","sign":"what you see","action":"what to do"}],"health":"one or two plain sentences on how the leaves look",'
            . '"tip":"one practical tip for this field this week","confidence":"low|medium|high"}';
    }

    private function parse(string $text, int $leaves): ?array
    {
        $t = preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? $text;
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $d = json_decode(substr($t, $a, $b - $a + 1), true);
        if (! is_array($d) || ! is_array($d['leaves'] ?? null) || ! $d['leaves']) {
            return null;
        }
        // Every panel snapped to the chart's halves, every leaf numbered.
        $d['leaves'] = array_values(array_map(function ($l, $i) {
            $p = isset($l['panel']) && is_numeric($l['panel']) ? max(2, min(5, round((float) $l['panel'] * 2) / 2)) : null;

            return ['n' => (int) ($l['n'] ?? $i + 1), 'panel' => $p, 'note' => trim((string) ($l['note'] ?? ''))];
        }, array_slice($d['leaves'], 0, max(1, $leaves)), array_keys(array_slice($d['leaves'], 0, max(1, $leaves)))));
        $sweep = fn ($v) => is_string($v) ? trim(preg_replace('/:[a-z0-9_-]+:/i', '', $v)) : $v;
        array_walk_recursive($d, function (&$v) use ($sweep) {
            $v = $sweep($v);
        });

        return $d;
    }

    public function job(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That reading is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            $beatAt = Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(10))) {
                $why = 'The reading was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending',
                'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The reading failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Ready.', ['status' => 'ready', 'id' => (int) $r->id, 'report' => json_decode((string) $r->report, true),
            'params' => json_decode((string) $r->params, true), 'charged' => (float) $r->credits,
            'balance' => round((float) app(AiCreditService::class)->balance($this->payer()->id), 2), 'history' => $this->history()]);
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Deleted.', ['history' => $this->history()]);
    }

    /** The farmer's last readings, newest first. */
    private function history(): array
    {
        return DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->limit(20)->get(['id', 'title', 'credits', 'created_at'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'title' => $r->title, 'credits' => (float) $r->credits,
                'at' => Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A')])->values()->all();
    }

    private function validateCase(Request $request, array $more)
    {
        $v = Validator::make($request->all(), [
            'method' => 'required|in:' . implode(',', array_keys(self::METHODS)),
            'season' => 'required|in:wet,dry',
            'days' => 'nullable|integer|min:0|max:200',
            'maturity' => 'nullable|in:' . implode(',', array_keys(self::MATURITY)),
            'areaHa' => 'nullable|numeric|min:0.0001|max:10000',
            'variety' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:400',
        ] + $more);

        return $v->fails() ? $this->json(false, $v->errors()->first(), [], 422) : null;
    }

    private function case(Request $request): array
    {
        return [
            'method' => (string) $request->input('method'),
            'season' => (string) $request->input('season'),
            'days' => $request->filled('days') ? (int) $request->input('days') : null,
            'maturity' => $request->filled('maturity') ? (string) $request->input('maturity') : null,
            'areaHa' => (float) ($request->input('areaHa') ?: 1),
            'variety' => trim((string) $request->input('variety', '')),
            'notes' => trim((string) $request->input('notes', '')),
        ];
    }

    private function title(array $case, array $d): string
    {
        $what = match ($d['stage']) {
            'early' => 'too early to read',
            'late' => 'past flowering',
            default => $d['due'] ? 'nitrogen due' : 'no nitrogen yet',
        };

        return 'Leaf color · ' . now('Asia/Manila')->format('M j') . ' · ' . $d['below'] . ' of ' . $d['leaves'] . ' below ' . $d['critical'] . ' · ' . $what;
    }

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
