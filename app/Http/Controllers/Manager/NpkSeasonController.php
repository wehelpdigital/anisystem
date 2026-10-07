<?php

namespace App\Http\Controllers\Manager;

use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropStages;
use App\Support\EnsoOutlook;
use App\Support\FieldContext;
use App\Support\LotCalendar;
use App\Support\NpkPlan;
use App\Support\Tier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NPK Plus on the Activities board (2026-10-07).
 *
 * The season's own record, added up: every fertilizer the board's tasks
 * carry, lot by lot, split into what is already on (ticked done) and what
 * is still planned, per hectare, against the usual rate for the lot's crop.
 * That much is free and drawn the moment the sheet opens.
 *
 * Anee's reading (AiPrices 'npkplan', charged per lot read) takes the same
 * record with each lot's stage, its place and its weather (the last month
 * and the next two weeks) and ENSO, and says per lot what is short, right
 * or too much, what to do next and when, and what to change in the plan.
 * A task with no lot named covers every lot; a task's amounts are shared
 * between its lots by their size (evenly when a size is missing).
 */
class NpkSeasonController extends BaseScheduleController
{
    public const KIND = 'npkplan';

    public const MAX_LOTS = 8;

    /** The free reading, and what Anee's would cost. */
    public function summary(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $payer = $this->payer();
        $credits = app(AiCreditService::class);

        return $this->jsonOk('ok', ['data' => [
            'lots' => self::season($schedule),
            'price' => AiPrices::of(self::KIND),
            'balance' => round((float) $credits->balance($payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'locked' => ! Tier::scheduleCan($schedule, 'ai'),
            'aiUsable' => $payer->canUseAi() && AiSetting::current()->isUsable(),
            'maxLots' => self::MAX_LOTS,
        ]]);
    }

    /**
     * Every lot of the season with its fertilizer, applied and planned.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function season(AsCroppingSchedule $schedule): array
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        $lots = $schedule->lots()->where('deleteStatus', 1)->orderBy('id')->get();
        if ($lots->isEmpty()) {
            return [];
        }
        [$zero, $transplant] = LotCalendar::effectiveAnchors($schedule);
        $out = [];
        foreach ($lots as $l) {
            $ha = NpkPlan::hectares($l->lotSize, $l->lotSizeUnit);
            $age = null;
            $stage = null;
            try {
                $age = LotCalendar::ageOf($l, $today, $zero[$l->id] ?? null, $transplant[$l->id] ?? null);
                $crop = CropStages::normalize($l->crop ?: $schedule->cropType);
                $stage = $age && $crop ? CropStages::stageFor($crop, $l->stageDay($age), $age['counter'], $l->maturityDays()) : null;
            } catch (\Throwable $e) {
            }
            $cropRow = NpkPlan::crop($l->crop ?: $schedule->cropType);
            $out[$l->id] = [
                'id' => (int) $l->id, 'name' => (string) ($l->lotName ?: 'Lot ' . $l->id),
                'crop' => CropStages::label($l->crop ?: $schedule->cropType) ?: ($l->crop ?: 'Crop not set'), 'variety' => (string) ($l->variety ?: ''),
                'areaHa' => $ha, 'size' => $l->lotSize ? rtrim(rtrim((string) $l->lotSize, '0'), '.') . ' ' . ($l->lotSizeUnit ?: 'ha') : null,
                'age' => $age ? LotCalendar::says($age) : null, 'stage' => $stage['label'] ?? null,
                'dayZero' => $l->dayZeroDate ? Carbon::parse($l->dayZeroDate)->format('M j, Y') : null,
                'place' => trim(implode(', ', array_filter([$l->locBarangay, $l->locTown, $l->locProvince]))),
                'lat' => $l->pinLat ? (float) $l->pinLat : null, 'lng' => $l->pinLng ? (float) $l->pinLng : null,
                'usual' => $cropRow['recommendedPerHa'] ?? null, 'trees' => $cropRow['treesPerHa'] ?? null,
                'applied' => [], 'planned' => [], 'lines' => [], 'unread' => [],
            ];
        }
        $allIds = array_keys($out);

        $activities = $schedule->activities()->with(['lots', 'items.material'])->orderBy('targetDate')->orderBy('id')->get();
        foreach ($activities as $a) {
            $ids = $a->lots->pluck('id')->map(fn ($i) => (int) $i)->filter(fn ($i) => isset($out[$i]))->values()->all() ?: $allIds;
            // Shared by size; evenly when any lot has none.
            $sizes = array_map(fn ($i) => $out[$i]['areaHa'], $ids);
            $even = in_array(null, $sizes, true) || array_sum($sizes) <= 0;
            $sum = $even ? count($ids) : array_sum($sizes);
            $done = (int) ($a->isDone ?? 0) === 1;
            foreach ($a->items as $it) {
                if ($it->itemType === 'service') {
                    continue;
                }
                $name = trim($it->displayName());
                $p = NpkPlan::match($name);
                if (! $p) {
                    if (NpkPlan::looksLikeFertilizer($name)) {
                        foreach ($ids as $i) {
                            $out[$i]['unread'][$name] = 'We could not tell what this product carries. Add it in NPK Plus from its label, with the same name.';
                        }
                    }
                    continue;
                }
                $qty = (float) $it->quantity;
                $kg = NpkPlan::kg($qty, $it->unitOfMeasure, $p);
                if ($kg === null && empty($p['estimate'])) {
                    foreach ($ids as $i) {
                        $out[$i]['unread'][$name] = 'The amount is in ' . ($it->unitOfMeasure ?: 'no unit') . ', which cannot be weighed. Write it in kg, bags or liters.';
                    }
                    continue;
                }
                foreach ($ids as $i) {
                    $share = $even ? 1 / $sum : $out[$i]['areaHa'] / $sum;
                    $lotKg = $kg !== null ? $kg * $share : null;
                    $nut = NpkPlan::nutrients($p, $lotKg, (float) ($out[$i]['areaHa'] ?? 1));
                    $bucket = $done ? 'applied' : 'planned';
                    foreach ($nut as $n => $v) {
                        $out[$i][$bucket][$n] = ($out[$i][$bucket][$n] ?? 0) + $v;
                    }
                    $out[$i]['lines'][] = [
                        'date' => $a->targetDate ? Carbon::parse($a->targetDate)->format('M j') : '', 'iso' => $a->targetDate ? Carbon::parse($a->targetDate)->toDateString() : null,
                        'title' => trim((string) $a->activityTitle) ?: 'Task', 'name' => $name, 'as' => $p['name'] . (! empty($p['local']) && preg_match('/^\d/', (string) $p['local']) ? ' ' . $p['local'] : ''),
                        'amount' => rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') . ' ' . ($it->unitOfMeasure ?: ''), 'kg' => $lotKg !== null ? round($lotKg, 2) : null,
                        'shared' => count($ids) > 1, 'done' => $done, 'estimate' => ! empty($p['estimate']), 'npk' => NpkPlan::round($nut),
                    ];
                }
            }
        }

        foreach ($out as &$lot) {
            $ha = $lot['areaHa'];
            $per = fn (array $t) => $ha ? array_map(fn ($v) => $v / $ha, $t) : [];
            $total = $lot['applied'];
            foreach ($lot['planned'] as $n => $v) {
                $total[$n] = ($total[$n] ?? 0) + $v;
            }
            $lot['kg'] = ['applied' => NpkPlan::round($lot['applied']), 'planned' => NpkPlan::round($lot['planned'])];
            $lot['applied'] = NpkPlan::round($per($lot['applied']));
            $lot['planned'] = NpkPlan::round($per($lot['planned']));
            $lot['total'] = NpkPlan::round($per($total));
            $lot['unread'] = collect($lot['unread'])->map(fn ($why, $name) => ['name' => $name, 'why' => $why])->values()->all();
        }

        return array_values($out);
    }

    /* --------------------------------------------------------------- Anee */

    public function generate(Request $request)
    {
        // A reading writes nothing to the season, so a finished (locked)
        // season can be read too; a view-only worker still cannot spend
        // the farm's credits.
        $schedule = $this->schedule($request->query('scheduleId'));
        $this->assertCanEdit();
        if (! Tier::scheduleCan($schedule, 'ai')) {
            Tier::scheduleDenyFor($schedule, 'ai', 'Anee\'s fertilizer reading comes with {plan}. Add Anee and she checks every lot\'s nutrients against its stage, its weather and ENSO.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->jsonFail('Anee is not available right now.', 403);
        }
        $all = collect(self::season($schedule))->keyBy('id');
        $want = collect((array) $request->input('lotIds', []))->map(fn ($i) => (int) $i)->filter(fn ($i) => $all->has($i))->unique()->values();
        if ($want->isEmpty()) {
            return $this->jsonFail('Choose at least one lot.', 422);
        }
        if ($want->count() > self::MAX_LOTS) {
            return $this->jsonFail('Up to ' . self::MAX_LOTS . ' lots at a time, please.', 422);
        }
        $lots = $want->map(fn ($i) => $all[$i])->values()->all();
        if (! collect($lots)->contains(fn ($l) => $l['lines'])) {
            return $this->jsonFail('No fertilizer is on these lots yet. Add the materials to the fertilizer tasks first.', 422);
        }
        $price = (float) AiPrices::of(self::KIND) * count($lots);
        if ($credits->balance($payer->id) < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('You need ' . ceil($price) . ' credits for ' . count($lots) . ' lot' . (count($lots) === 1 ? '' : 's') . ' and have '
                . number_format((int) floor($credits->balance($payer->id))) . '.', 402, ['outOfCredits' => true]);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(12))->orderByDesc('id')->first();
        if ($standing) {
            return $this->jsonOk('Already working on it.', ['data' => ['pending' => true, 'id' => $standing->id]]);
        }
        $notes = trim(Str::limit(strip_tags((string) $request->input('notes', '')), 600, ''));
        $p = ['scheduleId' => (int) $schedule->id, 'lotIds' => $want->all(), 'notes' => $notes];
        $title = 'NPK Plus · ' . Str::limit((string) $schedule->title, 80, '') . ' · ' . (count($lots) === 1 ? $lots[0]['name'] : count($lots) . ' lots');
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($title, 0, 190), 'params' => json_encode($p),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->run($id, (int) $payer->id, $settings, $schedule, $lots, $notes);
            exit;
        }
        @set_time_limit(400);
        $this->run($id, (int) $payer->id, $settings, $schedule, $lots, $notes);

        return $this->job($id);
    }

    private function run(int $id, int $payerId, AiSetting $settings, AsCroppingSchedule $schedule, array $lots, string $notes): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('weather');
            $prompt = $this->prompt($schedule, $lots, $notes);
            $beat('thinking');
            $result = app(AiClient::class)->askForJson($settings->forField('PH'), $prompt, 7000, fn (string $t) => $this->parse($t));
            if ($result['data'] === null) {
                Log::warning('npkplan: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The reading could not be read. Nothing was charged. Please try again.');
            }
            $price = (float) AiPrices::of(self::KIND) * count($lots);
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            app(AiCreditService::class)->chargeAllowingNegative($payerId, $price,
                mb_substr('NPK Plus reading: ' . $schedule->title . ' · ' . count($lots) . ' lot' . (count($lots) === 1 ? '' : 's') . $note, 0, 250));
            $report = ['format' => 1, 'anee' => $result['data'], 'lots' => array_map(fn ($l) => array_diff_key($l, array_flip(['lat', 'lng'])), $lots),
                'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report), 'credits' => round($price, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    private function prompt(AsCroppingSchedule $schedule, array $lots, string $notes): string
    {
        $today = Carbon::now('Asia/Manila');
        $blocks = [];
        $wxSeen = [];
        foreach ($lots as $l) {
            $b = 'LOT "' . $l['name'] . '": ' . $l['crop'] . ($l['variety'] ? ' (' . $l['variety'] . ')' : '')
                . '; size ' . ($l['size'] ?: 'not set') . ($l['areaHa'] ? ' (' . round($l['areaHa'], 4) . ' ha)' : '')
                . '; ' . ($l['age'] ?: 'no day zero yet') . ($l['stage'] ? ', stage ' . $l['stage'] : '') . ($l['dayZero'] ? '; day zero ' . $l['dayZero'] : '')
                . '; place: ' . ($l['place'] ?: 'not set') . ".\n";
            $b .= 'Usual recommended rate for this crop, kg per ha: ' . ($l['usual'] ? json_encode($l['usual']) : 'not in our table') . ($l['trees'] ? ' (counted at ' . $l['trees'] . ' trees per ha)' : '') . "\n";
            $b .= 'Already applied (ticked done), per ha: ' . NpkPlan::words($l['applied']) . "\n";
            $b .= 'Still planned (not done yet), per ha: ' . NpkPlan::words($l['planned']) . "\n";
            $b .= 'Season total if all is applied, per ha: ' . NpkPlan::words($l['total']) . "\n";
            $b .= "Fertilizer tasks on this lot:\n" . implode("\n", array_map(fn ($x) => '- ' . $x['date'] . ' ' . ($x['done'] ? 'DONE' : 'planned') . ': ' . $x['title'] . ', ' . $x['name']
                . ' ' . trim($x['amount']) . ($x['shared'] ? ' (task shared with other lots; this lot\'s share ' . ($x['kg'] ?? '?') . ' kg)' : '') . ($x['estimate'] ? ' (biofertilizer, estimate)' : ''), array_slice($l['lines'], 0, 40))) . "\n";
            if ($l['unread']) {
                $b .= 'Not counted (could not be read): ' . implode('; ', array_column($l['unread'], 'name')) . "\n";
            }
            $wx = $this->weatherFor($l);
            if ($wx !== '') {
                $key = md5($wx);
                $b .= isset($wxSeen[$key]) ? 'Weather: same place as lot "' . $wxSeen[$key] . "\".\n" : $wx;
                $wxSeen[$key] = $l['name'];
            }
            $blocks[] = $b;
        }
        $region = \App\Support\Region::englishOnly() ? 'Write in plain English only.' : 'Write in plain English with the odd Tagalog farm word where natural.';

        return "You are Anee, a smart farm technician for farmers in " . \App\Support\Region::name() . '. A farmer asks you to check the fertilizer in their season "'
            . $schedule->title . '", lot by lot: what each lot has already received, what is still planned, and whether that fits the crop, its stage, the place and the weather. Today is '
            . $today->format('M j, Y') . ".\n\n" . implode("\n", $blocks)
            . 'ENSO: ' . (EnsoOutlook::forPrompt() ?: 'not available') . "\n"
            . ($notes !== '' ? 'The farmer adds: ' . $notes . "\n" : '')
            . "\nRULES: Judge each lot on its own. Compare applied plus planned against the usual rate and the stage: a lot early in the season should not have had everything yet. Say what is short, right or too much for N, P2O5, K2O and any secondary or micronutrient that matters for this crop (zinc for rice, boron for vegetables and so on). Say what to apply NEXT and WHEN, by date or by the lot's day count, and what to change in the planned tasks (cut, add, move, split). Use the weather: heavy rain ahead means split or delay urea; dry spells mean water first. Name products by grade or nutrient, never a brand. Never invent numbers that are not given; say what you cannot know (no soil test, unread products). Remember the yield still depends on the weather, the variety and the soil. "
            . $region . " No emoji.\n\n"
            . 'Return ONE JSON object only: {"headline":"one line for the whole season","summary":"2 to 3 plain sentences",'
            . '"lots":[{"lot":"<the lot name exactly>","verdict":"on track|short|too much|unbalanced|too early to tell","summary":"2 sentences",'
            . '"nutrients":[{"nutrient":"N|P2O5|K2O|Zn|...","status":"short|right|over","comment":"short"}],'
            . '"next":[{"when":"date or day count","what":"what to apply and how much per ha","why":"short"}],'
            . '"changes":[{"change":"what to change in the planned tasks","why":"short"}],"weather":"what the weather and ENSO mean for this lot\'s fertilizer"}],'
            . '"warnings":["short"],"confidence":"low|medium|high"}';
    }

    /** The lot's last month and next two weeks of weather, in a few lines. */
    private function weatherFor(array $l): string
    {
        try {
            $lat = $l['lat'];
            $lng = $l['lng'];
            if (($lat === null || $lng === null) && $l['place'] !== '') {
                $geo = app(\App\Services\WeatherService::class)->geocode($l['place']);
                if ($geo) {
                    [$lat, $lng] = [(float) $geo['lat'], (float) $geo['lon']];
                }
            }
            if ($lat === null || $lng === null) {
                return '';
            }
            $w = FieldContext::weather((float) $lat, (float) $lng);
            if (! $w) {
                return '';
            }
            $next = array_values(array_filter($w['days'], fn ($d) => ! $d['past']));
            $days = implode('; ', array_map(fn ($d) => Carbon::parse($d['date'])->format('M j') . ' ' . ($d['rain'] ?? 0) . ' mm, ' . ($d['tmax'] ?? '?') . ' C', array_slice($next, 0, 10)));

            return 'Weather: last 30 days ' . $w['past30Rain'] . ' mm of rain; next 10 days ' . $w['next10Rain'] . ' mm, ' . $w['hotDaysNext10'] . ' days at 35 C or more, strongest gust '
                . $w['maxGustNext10'] . " km/h. Next 10 days: " . $days . ".\n";
        } catch (\Throwable $e) {
            return '';
        }
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

        return is_array($d) && isset($d['lots']) && is_array($d['lots']) ? $d : null;
    }

    public function job(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r || ((int) $r->userId !== (int) Auth::id() && ! $this->mayRead($r))) {
            return $this->jsonFail('That reading is gone.', 404);
        }
        if ($r->status === 'pending') {
            $beatAt = Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(15))) {
                $why = 'The reading was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->jsonFail($why, 502, ['data' => ['status' => 'failed']]);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->jsonOk('Working…', ['data' => ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1), 'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->jsonFail($r->error ?: 'The reading failed and nothing was charged. Please try again.', 502, ['data' => ['status' => 'failed']]);
        }
        $payer = $this->payer();

        return $this->jsonOk('Ready.', ['data' => ['status' => 'ready', 'id' => (int) $r->id, 'title' => $r->title, 'report' => json_decode((string) $r->report, true),
            'charged' => (float) $r->credits, 'balance' => round((float) app(AiCreditService::class)->balance($payer->id), 2)]]);
    }

    /** The season's saved readings, newest first. */
    public function list(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $rows = DB::table('as_plant_analyses')->where('kind', self::KIND)->where('status', 'ready')->where('deleteStatus', 1)
            ->where('params', 'like', '{"scheduleId":' . (int) $schedule->id . ',%')->orderByDesc('id')->limit(30)->get(['id', 'title', 'credits', 'created_at', 'userId']);

        return $this->jsonOk('ok', ['data' => ['rows' => $rows->map(fn ($r) => ['id' => (int) $r->id, 'title' => $r->title, 'mine' => (int) $r->userId === (int) Auth::id(),
            'at' => Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A')])->values()]]);
    }

    /** Whether a reading belongs to a season this person can see. */
    private function mayRead(object $r): bool
    {
        $p = json_decode((string) $r->params, true) ?: [];
        if (empty($p['scheduleId'])) {
            return false;
        }

        return AsCroppingSchedule::active()->forClient(\App\Support\WorkerContext::effectiveOwnerId())->where('as_cropping_schedules.id', (int) $p['scheduleId'])->exists()
            && \App\Support\WorkerContext::canView();
    }

    private function payer(): \App\Models\User
    {
        return \App\Models\User::findOrFail((int) \App\Support\WorkerContext::effectiveOwnerId());
    }
}
