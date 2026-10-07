<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsProtocol;
use App\Models\AsProtocolVersion;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropStages;
use App\Support\EnsoOutlook;
use App\Support\NpkPlan;
use App\Support\SoilConditions;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * NPK Plus in the Protocol Builder (2026-10-07).
 *
 * The protocol's fertilizer, task by task on its day count, added up: each
 * item's amount (times the loads for a knapsack group) read against the
 * NPK Plus shelf, as kg of every nutrient, a running total, and the whole
 * plan per hectare against the usual rate for the protocol's crop. Free.
 * A protocol is written for an area the farmer names (one hectare unless
 * they say otherwise); the amounts are taken as written for it.
 *
 * Anee's reading (AiPrices 'npkproto') weighs the same plan by growth stage,
 * with the place, soil and water if the farmer gives them, and ENSO: what is
 * short or too much, which split to move or add, and when.
 */
class ProtocolNpkController extends Controller
{
    public const KIND = 'npkproto';

    public function summary(Request $request, int $id)
    {
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $area = max(0.0001, min(100000, (float) ($request->query('areaHa') ?: 1)));
        $payer = $this->payer();
        $credits = app(AiCreditService::class);

        return $this->json(true, 'ok', self::plan($p, $area) + [
            'price' => AiPrices::of(self::KIND),
            'balance' => round((float) $credits->balance($payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'canUse' => Tier::farmCan('aiAnalyses') && $payer->canUseAi() && AiSetting::current()->isUsable(),
            'soilConditions' => SoilConditions::OPTIONS,
            'water' => NpkPlusController::WATER,
        ]);
    }

    /** The protocol's fertilizer, task by task, and the whole per hectare. */
    public static function plan(AsProtocol $p, float $area): array
    {
        $ver = self::current($p);
        $materials = collect((array) ($ver?->materials ?? []))->keyBy('id');
        $tasks = collect((array) ($ver?->tasks ?? []))->filter(fn ($t) => is_array($t) && ($t['kind'] ?? 'task') === 'task')
            ->sortBy(fn ($t) => [self::order($t['counter'] ?? $p->dayType), (float) ($t['day'] ?? 0)])->values();
        $total = [];
        $rows = [];
        $unread = [];
        foreach ($tasks as $t) {
            $lines = [];
            $sum = [];
            foreach ((array) ($t['groups'] ?? []) as $g) {
                $loads = ! empty($g['perKnapsack']) && ! empty($g['loads']) ? (float) $g['loads'] : 1.0;
                foreach ((array) ($g['items'] ?? []) as $it) {
                    $name = trim((string) ($it['name'] ?? ''));
                    $m = ! empty($it['materialId']) ? $materials->get($it['materialId']) : null;
                    $prod = NpkPlan::match($name) ?? ($m ? NpkPlan::match((string) ($m['name'] ?? '')) : null);
                    if (! $prod) {
                        if ($name !== '' && NpkPlan::looksLikeFertilizer($name)) {
                            $unread[$name] = 'We could not tell what this product carries. Add it in NPK Plus from its label, with the same name.';
                        }
                        continue;
                    }
                    [$qty, $unit] = self::amount($it, $m);
                    if ($qty === null) {
                        $unread[$name] = 'It has no amount yet.';
                        continue;
                    }
                    $qty *= $loads;
                    $kg = NpkPlan::kg($qty, $unit, $prod);
                    if ($kg === null && empty($prod['estimate'])) {
                        $unread[$name] = 'The amount is in ' . ($unit ?: 'no unit') . ', which cannot be weighed. Write it in kg, bags or liters.';
                        continue;
                    }
                    $nut = NpkPlan::nutrients($prod, $kg, $area);
                    foreach ($nut as $n => $v) {
                        $sum[$n] = ($sum[$n] ?? 0) + $v;
                        $total[$n] = ($total[$n] ?? 0) + $v;
                    }
                    $lines[] = ['name' => $name, 'as' => $prod['name'] . (! empty($prod['local']) && preg_match('/^\d/', (string) $prod['local']) ? ' ' . $prod['local'] : ''),
                        'amount' => rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') . ' ' . $unit, 'kg' => $kg !== null ? round($kg, 2) : null,
                        'estimate' => ! empty($prod['estimate']), 'npk' => NpkPlan::round(array_map(fn ($v) => $v / $area, $nut))];
                }
            }
            if ($lines) {
                $rows[] = ['id' => (string) ($t['id'] ?? ''), 'counter' => (string) ($t['counter'] ?? $p->dayType), 'day' => (float) ($t['day'] ?? 0),
                    'title' => trim((string) ($t['title'] ?? '')) ?: 'Task', 'lines' => $lines, 'npk' => NpkPlan::round(array_map(fn ($v) => $v / $area, $sum))];
            }
        }
        $crop = NpkPlan::crop($p->crop);

        return [
            'protocol' => ['id' => (int) $p->id, 'title' => $p->title, 'crop' => $p->crop, 'cropLabel' => $p->crop ? (CropStages::label($p->crop) ?: $p->crop) : null,
                'variety' => $p->variety, 'dayType' => $p->dayType, 'version' => $ver?->name],
            'areaHa' => $area,
            'tasks' => $rows,
            'total' => NpkPlan::round(array_map(fn ($v) => $v / $area, $total)),
            'usual' => $crop['recommendedPerHa'] ?? null,
            'trees' => $crop['treesPerHa'] ?? null,
            'unread' => collect($unread)->map(fn ($why, $name) => ['name' => $name, 'why' => $why])->values()->all(),
        ];
    }

    /** An item's quantity and unit: its own qty with its material's unit, or read from "4 bag". */
    private static function amount(array $it, ?array $m): array
    {
        $qty = isset($it['qty']) && $it['qty'] !== null && $it['qty'] !== '' ? (float) $it['qty'] : null;
        $unit = $m['unit'] ?? null;
        if (preg_match('/^\s*([\d.,]+)\s*([a-zA-Z²]+)?/', (string) ($it['amount'] ?? ''), $mm)) {
            $qty ??= (float) str_replace(',', '', $mm[1]);
            $unit ??= $mm[2] ?? null;
            if (! $unit && ! empty($mm[2])) {
                $unit = $mm[2];
            }
        }

        return [$qty, $unit];
    }

    private static function order(string $counter): int
    {
        return ['DAS' => 0, 'DAP' => 0, 'DAT' => 1, 'DAH' => 2, 'MAP' => 0, 'DOS' => 0][strtoupper($counter)] ?? 0;
    }

    private static function current(AsProtocol $p): ?AsProtocolVersion
    {
        $rows = AsProtocolVersion::active()->where('protocolId', $p->id)->orderBy('sortOrder')->orderBy('id')->get();

        return $rows->firstWhere('id', (int) $p->versionId) ?? $rows->first();
    }

    /* --------------------------------------------------------------- Anee */

    public function generate(Request $request, int $id)
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Anee\'s fertilizer reading of a protocol comes with {plan}, and with every plan above it.');
        }
        $p = $this->mine($id);
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'The reading'), [], 403);
        }
        $v = Validator::make($request->all(), [
            'areaHa' => 'nullable|numeric|min:0.0001|max:100000', 'location' => 'nullable|string|max:160', 'soilConditions' => 'nullable|array|max:7',
            'soilConditions.*' => 'string|in:' . implode(',', array_keys(SoilConditions::OPTIONS)), 'phValue' => 'nullable|numeric|min:2|max:12',
            'water' => 'nullable|in:' . implode(',', array_keys(NpkPlusController::WATER)), 'notes' => 'nullable|string|max:800',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $plan = self::plan($p, (float) ($request->input('areaHa') ?: 1));
        if (! $plan['tasks']) {
            return $this->json(false, 'No fertilizer is in this protocol yet. Add the fertilizer to its tasks first.', [], 422);
        }
        $price = (float) AiPrices::of(self::KIND);
        $credits = app(AiCreditService::class);
        if ($credits->balance($payer->id) < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->json(false, 'You need ' . ceil($price) . ' credits for this reading and have ' . number_format((int) floor($credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(12))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }
        $ask = ['protocolId' => (int) $p->id, 'areaHa' => $plan['areaHa'], 'location' => trim((string) $request->input('location', '')),
            'soilConditions' => SoilConditions::normalize($request->input('soilConditions', [])), 'phValue' => SoilConditions::phValue($request->input('phValue')),
            'water' => (string) ($request->input('water') ?: ''), 'notes' => trim((string) $request->input('notes', ''))];
        $rid = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr('NPK Plus · ' . $p->title, 0, 190), 'params' => json_encode($ask),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $rid]])->send();
            fastcgi_finish_request();
            $this->run($rid, (int) $payer->id, $settings, $plan, $ask);
            exit;
        }
        @set_time_limit(400);
        $this->run($rid, (int) $payer->id, $settings, $plan, $ask);

        return $this->job($rid);
    }

    private function run(int $id, int $payerId, AiSetting $settings, array $plan, array $ask): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('thinking');
            $result = app(AiClient::class)->askForJson($settings->forField('PH'), $this->prompt($plan, $ask), 6000, fn (string $t) => $this->parse($t));
            if ($result['data'] === null) {
                Log::warning('npkproto: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The reading could not be read. Nothing was charged. Please try again.');
            }
            $price = (float) AiPrices::of(self::KIND);
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            app(AiCreditService::class)->chargeAllowingNegative($payerId, $price, mb_substr('NPK Plus reading: ' . $plan['protocol']['title'] . $note, 0, 250));
            $report = ['format' => 1, 'anee' => $result['data'], 'plan' => $plan, 'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report), 'credits' => round($price, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    private function prompt(array $plan, array $ask): string
    {
        $pr = $plan['protocol'];
        $tasks = implode("\n", array_map(fn ($t) => '- ' . $t['counter'] . ' ' . rtrim(rtrim(number_format($t['day'], 1, '.', ''), '0'), '.') . ': ' . $t['title'] . ' — '
            . implode('; ', array_map(fn ($l) => $l['name'] . ' ' . trim($l['amount']) . ($l['estimate'] ? ' (biofertilizer, estimate)' : ''), $t['lines'])) . ' → per ha ' . NpkPlan::words($t['npk']), $plan['tasks']));
        $region = \App\Support\Region::englishOnly() ? 'Write in plain English only.' : 'Write in plain English with the odd Tagalog farm word where natural.';

        return 'You are Anee, a smart farm technician for farmers in ' . \App\Support\Region::name() . '. A farmer wrote a crop protocol ahead of the season and asks you to check its fertilizer plan, nutrient by nutrient and stage by stage. Today is '
            . Carbon::now('Asia/Manila')->format('M j, Y') . ".\n\n"
            . 'Protocol: "' . $pr['title'] . '"; crop ' . ($pr['cropLabel'] ?: 'not set') . ($pr['variety'] ? ' (' . $pr['variety'] . ')' : '') . '; counted in ' . $pr['dayType'] . ($pr['version'] ? '; version "' . $pr['version'] . '"' : '')
            . '; amounts written for ' . $plan['areaHa'] . " ha.\n"
            . "Fertilizer tasks in order:\n" . $tasks . "\n"
            . 'Whole plan, per ha: ' . NpkPlan::words($plan['total']) . "\n"
            . 'Usual recommended rate for this crop, kg per ha: ' . ($plan['usual'] ? json_encode($plan['usual']) : 'not in our table') . ($plan['trees'] ? ' (counted at ' . $plan['trees'] . ' trees per ha)' : '') . "\n"
            . ($plan['unread'] ? 'Not counted (could not be read): ' . implode('; ', array_column($plan['unread'], 'name')) . "\n" : '')
            . ($ask['location'] !== '' ? 'Where it will be used: ' . $ask['location'] . "\n" : 'Place not given.' . "\n")
            . 'Soil: ' . (SoilConditions::words($ask['soilConditions'], $ask['phValue']) ?: 'not stated') . "\n"
            . (SoilConditions::guidance($ask['soilConditions']) ? SoilConditions::guidance($ask['soilConditions']) . "\n" : '')
            . 'Water: ' . (NpkPlusController::WATER[$ask['water']] ?? 'not stated') . "\n"
            . 'ENSO: ' . (EnsoOutlook::forPrompt() ?: 'not available') . "\n"
            . ($ask['notes'] !== '' ? 'The farmer adds: ' . $ask['notes'] . "\n" : '')
            . "\nRULES: Judge the totals against the usual rate and the crop's need; then the timing: is each nutrient there when the crop needs it (for rice: phosphorus and potassium early, nitrogen split to tillering and panicle initiation; adapt to this crop). Say what is short, right or too much, which application to move, cut, add or split, by the protocol's own day count. Name products by grade or nutrient, never a brand. Never invent numbers that are not given; say what you cannot know. Remember the yield still depends on the weather, the variety and the soil. "
            . $region . " No emoji.\n\n"
            . 'Return ONE JSON object only: {"verdict":"good|close|needs changes|unbalanced","headline":"one line","summary":"3 plain sentences",'
            . '"nutrients":[{"nutrient":"N|P2O5|K2O|Zn|...","status":"short|right|over","comment":"short"}],'
            . '"timing":[{"when":"day count","what":"what to apply and how much per ha","why":"short"}],'
            . '"changes":[{"change":"what to change in the protocol","why":"short"}],"soil":"what the soil means for this plan (empty if not given)",'
            . '"weather":"what the season and ENSO mean for losses and timing","warnings":["short"],"confidence":"low|medium|high"}';
    }

    private function parse(string $text): ?array
    {
        $t = preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? $text;
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $d = json_decode(substr($t, $a, $b - $a + 1), true);

        return is_array($d) && isset($d['verdict']) ? $d : null;
    }

    public function job(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That reading is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            $beatAt = Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(15))) {
                $why = 'The reading was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1), 'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The reading failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Ready.', ['status' => 'ready', 'id' => (int) $r->id, 'report' => json_decode((string) $r->report, true),
            'charged' => (float) $r->credits, 'balance' => round((float) app(AiCreditService::class)->balance($this->payer()->id), 2)]);
    }

    /** This protocol's earlier readings, newest first. */
    public function list(int $id)
    {
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'ready')->where('deleteStatus', 1)
            ->where('params', 'like', '{"protocolId":' . $id . ',%')->orderByDesc('id')->limit(20)->get(['id', 'title', 'created_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($r) => ['id' => (int) $r->id, 'title' => $r->title,
            'at' => Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A')])->values()]);
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

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
