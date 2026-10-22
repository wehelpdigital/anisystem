<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Models\AsProtocol;
use App\Models\AsProtocolVersion;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPricing;
use App\Support\AiUsage;
use App\Support\CropStages;
use App\Support\LandPrepPlanner as LP;
use App\Support\PlanPort;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Putting a saved plan to work (2026-10-22, the owner): a Land Prep Planner
 * or Crop Protocol Analysis plan into a cropping schedule, or into the
 * Protocol Builder.
 *
 * Into a NEW season, or one with nothing on its board yet: straight in.
 * Into a season with work: the plan goes onto the lots the farmer picks
 * (not harvested ones), moved to each lot's own planting day, and then
 *   - a lot whose part of the season is over refuses it ("land preparation
 *     is over: it was planted on ..."), the past work is left out;
 *   - Anee checks the rest against the board, for credits (preview() says
 *     how many, check() runs her and keeps the answer on as_plan_ports): the
 *     same work already there, the crew on each day, the other lots, the
 *     order and spacing of work, the crop;
 *   - the farmer then places it by the crew's free days, or by Anee's dates
 *     (apply()), once.
 * Into the Protocol Builder no check is asked: a protocol is a count of days,
 * and the builder keeps its tasks in the order of their days (builder()).
 *
 * The owner's own act: a worker in someone else's farm is refused here.
 */
class PlanPortController extends Controller
{
    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }

    private function refusal()
    {
        return WorkerContext::inWorkerContext()
            ? $this->json(false, 'Switch to your own farm to use your plans there.', [], 403) : null;
    }

    /** The plan named by the request, read as lots of work, or a refusal. */
    private function planFrom(Request $request): array
    {
        $source = (string) $request->input('source');
        $row = PlanPort::row($source, (int) $request->input('id'), (int) Auth::id());
        if (! $row) {
            abort($this->json(false, 'That plan is gone.', [], 404));
        }
        $plan = PlanPort::plan($source, $row);
        if (! empty($plan['legacy']) || ! $plan['lots']) {
            abort($this->json(false, 'This plan is from before plans could go into a season. Make the plan again, then add it.', [], 422));
        }

        return [$row, $plan];
    }

    /** One of the owner's open seasons, or a refusal. */
    private function seasonFrom(Request $request): AsCroppingSchedule
    {
        $s = AsCroppingSchedule::active()->where('anisystemUserId', (int) Auth::id())->where('id', (int) $request->input('scheduleId'))->first();
        if (! $s) {
            abort($this->json(false, 'That season is not yours.', [], 404));
        }
        if ($s->isLocked()) {
            abort($this->json(false, $s->title . ' is closed. Reopen it to add work to it.', [], 423));
        }
        $request->attributes->set('auditScheduleId', (int) $s->id);

        return $s;
    }

    /**
     * Which plan lot goes onto which of the season's lots: a lot that is not
     * harvested, or "new", or "skip". At least one must go in, and a season's
     * room for lots is the plan's.
     */
    private function mapFrom(Request $request, array $plan, AsCroppingSchedule $s): array
    {
        $lots = \App\Models\AsScheduleLot::active()->where('croppingScheduleId', $s->id)->get()->keyBy('id');
        $map = [];
        foreach ($plan['lots'] as $pl) {
            $to = (string) (($request->input('map') ?? [])[$pl['i']] ?? 'skip');
            if ($to === 'new' || $to === 'skip') {
                $map[$pl['i']] = $to;

                continue;
            }
            $lot = $lots->get((int) $to);
            if (! $lot) {
                abort($this->json(false, 'Pick a lot of ' . $s->title . ' for ' . $pl['name'] . ', or add it as a new lot.', [], 422));
            }
            if ($lot->harvestedAt) {
                abort($this->json(false, $lot->lotName . ' is already harvested. Pick another lot, or add ' . $pl['name'] . ' as a new lot.', [], 422));
            }
            $map[$pl['i']] = (int) $lot->id;
        }
        $taken = array_filter($map, fn ($v) => is_int($v));
        if (count($taken) !== count(array_unique($taken))) {
            abort($this->json(false, 'Two lots of the plan point at the same lot of the season. Give each its own.', [], 422));
        }
        if (! array_filter($map, fn ($v) => $v !== 'skip')) {
            abort($this->json(false, 'Pick where at least one lot of the plan goes.', [], 422));
        }
        $new = count(array_filter($map, fn ($v) => $v === 'new'));
        $cap = Tier::limit('lotsPerSchedule');
        if ($new && $cap !== null && $lots->count() + $new > (int) $cap) {
            Tier::denyFor('lotsPerSchedule', 'Your plan allows ' . (int) $cap . ' ' . ((int) $cap === 1 ? 'lot' : 'lots') . ' in a season. Pick lots the season has, or move up to {plan} for more room.');
        }

        return $map;
    }

    /** The mapped lots, each told whether the plan can still go onto it. */
    private function sorted(array $plan, AsCroppingSchedule $s, array $map): array
    {
        $sh = PlanPort::shifted($plan, $s, $map);
        $phase = PlanPort::SOURCES[$plan['source']]['phase'];
        $ok = [];
        $over = [];
        foreach ($sh as $x) {
            $why = PlanPort::over($x, $phase);
            if ($why) {
                $over[] = ['i' => $x['plan']['i'], 'name' => $x['plan']['name'], 'why' => $why];
            } else {
                $ok[] = $x;
            }
        }

        return [$ok, $over];
    }

    private function payer(): User
    {
        return Auth::user();
    }

    /* ============================================================ reading */

    /** The owner's open seasons and their lots, for the sheet. */
    public function targets()
    {
        if ($no = $this->refusal()) {
            return $no;
        }

        return $this->json(true, 'ok', ['seasons' => PlanPort::targets((int) Auth::id())]);
    }

    /** The owner's protocols, for "add to one of mine". */
    public function protocols()
    {
        if ($no = $this->refusal()) {
            return $no;
        }
        $rows = AsProtocol::active()->where('userId', (int) Auth::id())->orderByDesc('updated_at')->limit(200)->get(['id', 'title', 'crop', 'dayType', 'updated_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($p) => [
            'id' => (int) $p->id, 'title' => $p->title, 'crop' => $p->crop,
            'cropLabel' => $p->crop ? str_replace(' — ', ', ', (string) CropStages::label($p->crop)) : null,
            'cropIcon' => $p->crop ? CropStages::icon($p->crop) : '🌱',
            'dayType' => $p->dayType, 'updated' => Carbon::parse($p->updated_at)->format('M j, Y'),
        ])->values()]);
    }

    /* ============================================================ a new season */

    public function season(Request $request)
    {
        if ($no = $this->refusal()) {
            return $no;
        }
        $user = $request->user();
        if (! $user->canCreateSchedule()) {
            $limit = $user->scheduleLimit();
            Tier::denyFor('schedulesActive', 'Your plan allows ' . ($limit === 0 ? 'no' : 'up to ' . $limit) . ' active ' . ($limit === 1 ? 'season' : 'seasons') . '. Finish or archive one, or move up to {plan} for more.');
        }
        [$row, $plan] = $this->planFrom($request);
        $cap = Tier::limit('lotsPerSchedule');
        if ($cap !== null && count($plan['lots']) > (int) $cap) {
            Tier::denyFor('lotsPerSchedule', 'Your plan allows ' . (int) $cap . ' ' . ((int) $cap === 1 ? 'lot' : 'lots') . ' in a season, and this plan has ' . count($plan['lots']) . '. Move up to {plan} for more room.');
        }
        $title = trim((string) $request->input('title')) ?: mb_substr((string) $row->title, 0, 255);
        [$schedule, $made] = PlanPort::newSeason($plan, $title, (int) Auth::id());
        $request->attributes->set('auditScheduleId', (int) $schedule->id);
        $season = $this->remember($row, $schedule);

        return $this->json(true, 'The season is made: ' . $made['lots'] . ' ' . ($made['lots'] === 1 ? 'lot' : 'lots') . ' and ' . $made['activities'] . ' activities on the board, each on its day.', [
            'scheduleId' => (int) $schedule->id, 'title' => $schedule->title, 'url' => route('sm.hub', ['id' => $schedule->id]), 'made' => $made, 'season' => $season,
        ]);
    }

    /** The plan remembers the season it went into. */
    private function remember(object $row, AsCroppingSchedule $s): array
    {
        $report = json_decode((string) $row->report, true) ?: [];
        $season = ['id' => (int) $s->id, 'title' => $s->title, 'at' => now('Asia/Manila')->format('M j, Y')];
        $report['season'] = $season;
        DB::table('as_plant_analyses')->where('id', $row->id)->update(['report' => json_encode($report), 'updated_at' => now()]);

        return $season;
    }

    /* ============================================================ into a season */

    /**
     * What putting the plan into this season means: straight in (a board
     * with nothing on it), not at all (every picked lot is past it), or
     * Anee's check first, at this price.
     */
    public function preview(Request $request)
    {
        if ($no = $this->refusal()) {
            return $no;
        }
        [, $plan] = $this->planFrom($request);
        $s = $this->seasonFrom($request);
        $map = $this->mapFrom($request, $plan, $s);
        [$ok, $over] = $this->sorted($plan, $s, $map);
        $lots = array_map(fn ($x) => [
            'i' => $x['plan']['i'], 'name' => $x['plan']['name'], 'into' => $x['lot'] ? $x['lot']->lotName : 'a new lot',
            'tasks' => count(array_filter($x['items'], fn ($it) => ! $it['past'])), 'past' => count(array_filter($x['items'], fn ($it) => $it['past'])),
            'moved' => $x['delta'], 'anchor' => $x['anchor'],
        ], $ok);
        if (! $ok) {
            return $this->json(true, 'This plan cannot go into ' . $s->title . ' any more.', ['state' => 'past', 'over' => $over]);
        }
        if (! PlanPort::hasWork($s)) {
            return $this->json(true, 'ok', ['state' => 'direct', 'lots' => $lots, 'over' => $over]);
        }
        $size = $this->size($ok, $s);
        $quote = AiPricing::quote('fitcheck', ['units' => $size]);
        $payer = $this->payer();
        // A check already paid for, of this plan onto these lots, not put on the board yet (the sheet was
        // closed after it): shown again for free rather than bought twice.
        $prior = DB::table('as_plan_ports')->where('userId', (int) Auth::id())->where('source', $plan['source'])->where('sourceId', $plan['id'])
            ->where('scheduleId', $s->id)->where('status', 'ready')->where('deleteStatus', 1)->where('created_at', '>', now()->subDay())->orderByDesc('id')->get()
            ->first(fn ($r) => (json_decode((string) $r->map, true) ?: []) == $map);

        return $this->json(true, 'ok', [
            'state' => 'check', 'lots' => $lots, 'over' => $over, 'board' => $this->boardCount($ok, $s),
            'prior' => $prior ? ['id' => (int) $prior->id, 'at' => Carbon::parse($prior->created_at)->timezone('Asia/Manila')->format('M j, g:i A')] : null,
            'price' => (int) $quote['credits'], 'quote' => (int) $quote['credits'],
            'balance' => round((float) $this->credits->balance($payer->id), 2), 'unlimited' => $this->credits->unlimited((int) $payer->id),
            'locked' => ! Tier::can('aiAnalyses'), 'aiUsable' => $payer->canUseAi() && AiSetting::current()->isUsable(),
        ]);
    }

    /** The first and last day of the new work. */
    private function span(array $ok): array
    {
        $days = [];
        foreach ($ok as $x) {
            foreach ($x['items'] as $it) {
                if (! $it['past']) {
                    $days[] = $it['at'];
                    $days[] = $it['atEnd'] ?: $it['at'];
                }
            }
        }
        sort($days);

        return [$days[0] ?? now('Asia/Manila')->toDateString(), end($days) ?: now('Asia/Manila')->toDateString()];
    }

    private function boardCount(array $ok, AsCroppingSchedule $s): int
    {
        [$from, $to] = $this->span($ok);

        return count(PlanPort::boardAround($s, $from, $to));
    }

    /** What Anee reads: the new tasks and the board's around them. */
    private function size(array $ok, AsCroppingSchedule $s): int
    {
        $n = 0;
        foreach ($ok as $x) {
            $n += count(array_filter($x['items'], fn ($it) => ! $it['past']));
        }

        return $n + $this->boardCount($ok, $s);
    }

    /** Anee's check, paid for and kept: a job the sheet polls. */
    public function check(Request $request)
    {
        if ($no = $this->refusal()) {
            return $no;
        }
        [, $plan] = $this->planFrom($request);
        $s = $this->seasonFrom($request);
        $map = $this->mapFrom($request, $plan, $s);
        [$ok] = $this->sorted($plan, $s, $map);
        if (! $ok) {
            return $this->json(false, 'This plan cannot go into ' . $s->title . ' any more.', [], 422);
        }
        if (! Tier::can('aiAnalyses')) {
            Tier::denyFor('aiAnalyses', 'Anee\'s check comes with {plan}.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'Anee is not available right now.', [], 403);
        }
        $quote = AiPricing::quote('fitcheck', ['units' => $this->size($ok, $s)]);
        $price = AiPricing::agreed($request, $quote, (int) $quote['credits']);
        if ($price === null) {
            return $this->json(false, 'Anee\'s check now costs ' . $quote['credits'] . ' credits. Please check again.', ['quote' => $quote['credits']], 409);
        }
        $balance = $this->credits->balance($payer->id);
        if ($balance < $price && ! $this->credits->unlimited((int) $payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this and have ' . number_format((int) floor($balance)) . '.', ['outOfCredits' => true], 402);
        }
        // One in flight per plan and season: a double press must not buy two.
        $standing = DB::table('as_plan_ports')->where('userId', (int) Auth::id())->where('source', $plan['source'])->where('sourceId', $plan['id'])
            ->where('scheduleId', $s->id)->where('status', 'pending')->where('deleteStatus', 1)->where('created_at', '>', now()->subMinutes(10))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already checking.', ['pending' => true, 'id' => (int) $standing->id]);
        }
        $id = DB::table('as_plan_ports')->insertGetId([
            'userId' => (int) Auth::id(), 'payerId' => (int) $payer->id, 'source' => $plan['source'], 'sourceId' => $plan['id'], 'scheduleId' => $s->id,
            'map' => json_encode($map), 'status' => 'pending', 'phase' => json_encode(['phase' => 'read', 'at' => now()->toIso8601String()]),
            'credits' => 0, 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Checking…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->run($id, $plan, $s, $ok, (int) $payer->id, $settings, $price);
            exit;
        }
        // No way to answer first (a local server): the run finishes even if the page is left.
        ignore_user_abort(true);
        @set_time_limit(300);
        $this->run($id, $plan, $s, $ok, (int) $payer->id, $settings, $price);

        return $this->job($id);
    }

    /** Where a check stands. */
    public function job(int $id)
    {
        $r = DB::table('as_plan_ports')->where('id', $id)->where('userId', (int) Auth::id())->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That check is gone.', [], 404);
        }
        if ($r->status === 'failed') {
            return $this->json(false, $r->error ?: 'The check failed. Nothing was charged.', ['status' => 'failed'], 422);
        }
        if ($r->status === 'pending') {
            $quiet = now()->diffInSeconds(Carbon::parse($r->updated_at)) > 420;
            if ($quiet) {
                DB::table('as_plan_ports')->where('id', $id)->update(['status' => 'failed', 'error' => 'The check took too long and was stopped. Nothing was charged.', 'updated_at' => now()]);

                return $this->json(false, 'The check took too long and was stopped. Nothing was charged.', ['status' => 'failed'], 422);
            }

            return $this->json(true, 'Checking…', ['pending' => true, 'id' => $id, 'status' => 'pending', 'phase' => json_decode((string) $r->phase, true)]);
        }
        $res = json_decode((string) $r->result, true) ?: [];

        return $this->json(true, 'ok', ['status' => $r->status, 'id' => $id, 'credits' => (float) $r->credits, 'result' => $this->publicResult($res),
            'balance' => round((float) $this->credits->balance((int) ($r->payerId ?: Auth::id())), 2)]);
    }

    /** What the sheet shows of a check: her reading, and what each placement does. */
    private function publicResult(array $res): array
    {
        $a = (array) ($res['anee'] ?? []);

        return [
            'verdict' => $a['verdict'] ?? 'ok', 'headline' => $a['headline'] ?? '', 'summary' => $a['summary'] ?? '',
            'conflicts' => array_values((array) ($a['conflicts'] ?? [])), 'skipped' => array_values((array) ($a['skipped'] ?? [])), 'tips' => array_values((array) ($a['tips'] ?? [])),
            'aneeMoved' => (int) ($a['moved'] ?? 0), 'crew' => ['moved' => (int) ($res['crew']['moved'] ?? 0), 'short' => array_values((array) ($res['crew']['short'] ?? [])), 'workers' => (int) ($res['crew']['workers'] ?? 0)],
            'tasks' => (int) ($res['tasks'] ?? 0),
        ];
    }

    /** The model call, the charge, and the answer kept. */
    private function run(int $id, array $plan, AsCroppingSchedule $s, array $ok, int $payerId, AiSetting $settings, int $price): void
    {
        $beat = fn (string $phase) => DB::table('as_plan_ports')->where('id', $id)->update(['phase' => json_encode(['phase' => $phase, 'at' => now()->toIso8601String()]), 'updated_at' => now()]);
        try {
            [$from, $to] = $this->span($ok);
            $board = PlanPort::boardAround($s, $from, $to);
            $crew = PlanPort::crewPlace($s, $ok, $board);
            [$prompt, $ids] = $this->prompt($plan, $s, $ok, $board, PlanPort::crew($s, $board), $from, $to);
            $beat('think');
            $result = $this->ai->askForJson($settings, $prompt, min(16000, 2500 + 60 * count($ids)), fn (string $t) => $this->parse($t));
            $found = $result['data'];
            if ($found === null) {
                Log::warning('plan-port: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'Anee\'s answer could not be read. Nothing was charged. Please try again.');
            }
            $beat('place');
            $anee = $this->settle($found, $ids, $ok);
            $note = AiUsage::record('fitcheck', (int) Auth::id(), $payerId, $id, $settings, $result, $price);
            $this->credits->chargeAllowingNegative($payerId, (float) $price,
                mb_substr('Anee checked a plan for ' . mb_substr((string) $s->title, 0, 80) . ' · ' . now('Asia/Manila')->format('M j, Y') . $note, 0, 250));
            AiPricing::audit('fitcheck', $result, (float) $price, ['units' => count($ids) + count($board)], $settings);
            $tasks = 0;
            foreach ($ok as $x) {
                $tasks += count(array_filter($x['items'], fn ($it) => ! $it['past']));
            }
            DB::table('as_plan_ports')->where('id', $id)->update([
                'status' => 'ready', 'credits' => $price, 'error' => null, 'updated_at' => now(),
                'result' => json_encode(['anee' => $anee, 'crew' => $crew, 'tasks' => $tasks, 'from' => $from, 'to' => $to]),
            ]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plan_ports')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 480), 'updated_at' => now()]);
        }
    }

    /**
     * What Anee is asked. Every new task has a short id (L1.3: the first
     * lot's third task) so her answer stays short and is read back exactly.
     */
    private function prompt(array $plan, AsCroppingSchedule $s, array $ok, array $board, array $crew, string $from, string $to): array
    {
        $today = now('Asia/Manila');
        $src = PlanPort::SOURCES[$plan['source']];
        $d = fn (?string $x) => $x ? Carbon::parse($x)->format('D Y-m-d') : '';
        $ids = [];
        $lines = [];
        $seasonLots = \App\Models\AsScheduleLot::active()->where('croppingScheduleId', $s->id)->get();
        $lotLines = $seasonLots->map(fn ($l) => '- ' . $l->lotName . ': ' . ($l->crop ? CropStages::label($l->crop) : 'no crop set') . ($l->variety ? ', ' . $l->variety : '')
            . (PlanPort::plantingOf($l) ? ', planted (day zero) ' . $d(PlanPort::plantingOf($l)) : ', no planting day yet') . ($l->harvestedAt ? ', HARVESTED' : ''))->implode("\n");
        foreach ($ok as $k => $x) {
            $pl = $x['plan'];
            $lines[] = 'Plan lot "' . $pl['name'] . '" (' . ($pl['cropLabel'] ?: $pl['crop']) . ($pl['variety'] !== '' ? ', ' . $pl['variety'] : '') . ') goes onto '
                . ($x['lot'] ? 'the season\'s lot "' . $x['lot']->lotName . '" (' . ($x['lot']->crop ? CropStages::label($x['lot']->crop) : 'no crop set') . ')' : 'a NEW lot of the season')
                . '. Its planting day there: ' . $d($x['anchor']) . ($x['delta'] ? ' (the plan said ' . $d($pl['planting']) . ', so every day moved ' . abs($x['delta']) . ($x['delta'] > 0 ? ' later' : ' earlier') . ')' : '') . '.';
            $n = 0;
            foreach ($x['items'] as $it) {
                if ($it['past']) {
                    continue;
                }
                $n++;
                $tag = 'L' . ($k + 1) . '.' . $n;
                $ids[$tag] = [$k, $it['key']];
                $lines[] = '  ' . $tag . ' · ' . $it['title'] . ' · ' . (\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$it['type']] ?? $it['type']) . ' · planned ' . $d($it['at'])
                    . ($it['atEnd'] && $it['atEnd'] !== $it['at'] ? ' to ' . $d($it['atEnd']) : '')
                    . ($it['atLo'] && $it['atHi'] && $it['atLo'] !== $it['atHi'] ? ' · may move ' . $d($it['atLo']) . ' to ' . $d($it['atHi']) : '')
                    . ' · ' . ($it['hired'] ? 'hired, no farm workers' : ($it['hands'] > 0 ? $it['hands'] . ' worker' . ($it['hands'] === 1 ? '' : 's') . ' a day' : 'no crew'))
                    . ($it['anchor'] ? ' · ANCHOR (never move)' : '');
            }
            $past = count(array_filter($x['items'], fn ($it) => $it['past']));
            if ($past) {
                $lines[] = '  (' . $past . ' step' . ($past === 1 ? '' : 's') . ' of this lot fell before today and are left out.)';
            }
        }
        $boardLines = $board ? implode("\n", array_map(fn ($a) => '- ' . $d($a['date']) . ($a['end'] && $a['end'] !== $a['date'] ? ' to ' . $d($a['end']) : '') . ' · '
            . ($a['lots'] ? implode(', ', $a['lots']) : 'the whole farm') . ' · ' . $a['title'] . ' · ' . (\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a['type']] ?? $a['type'])
            . ' · ' . count($a['workers']) . ' worker' . (count($a['workers']) === 1 ? '' : 's') . ($a['done'] ? ' · DONE' : '') . ($a['anchor'] ? ' · planting' : ''), $board)) : '(nothing on the board in these weeks)';
        $crewLine = $crew['workers'] > 0
            ? $crew['workers'] . ' workers on this season: ' . implode('; ', array_map(fn ($w) => $w['name'] . ($w['days'] ? ' (off ' . implode(', ', array_map(fn ($x) => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$x] ?? '', $w['days'])) . ')' : '')
                . ($w['dates'] ? ' (also off ' . implode(', ', array_slice($w['dates'], 0, 6)) . ')' : ''), $crew['off'])) . '. A worker does one task a day.'
            : 'No workers are listed on this season, so the crew cannot be counted. Do not move work for the crew; say so once in tips.';

        $prompt = "You are Anee, a smart farm technician in the Philippines. A farmer is adding a plan made by the app's {$src['label']} to a cropping schedule that already has work on its board."
            . " Check the new work against the board and the crew, then place every new task on a day.\n\n"
            . 'TODAY: ' . $today->format('l, Y-m-d') . ".\n"
            . 'SEASON: "' . $s->title . '"' . ($s->cropType ? ', ' . $s->cropType : '') . ".\n"
            . "ITS LOTS:\n" . ($lotLines ?: '- (none yet)') . "\n"
            . 'CREW: ' . $crewLine . "\n\n"
            . 'THE BOARD from ' . $d(Carbon::parse($from)->subDays(7)->toDateString()) . ' to ' . $d(Carbon::parse($to)->addDays(7)->toDateString()) . " (every lot; workers = those given the task):\n" . $boardLines . "\n\n"
            . "THE NEW WORK (id · task · type · planned day · the days it may move between · workers a day):\n" . implode("\n", $lines) . "\n\n"
            . "CHECK, in this order:\n"
            . "1. Duplicates: a new task whose same work is already on the board for that lot (done or planned) within 7 days of it, like a second plowing or a second basal fertilizer. Leave it out: its id goes in \"skip\", and say why in conflicts (kind \"duplicate\").\n"
            . "2. The crop: if a lot already grows a different crop than the plan's lot, the plan does not fit it (kind \"crop\", high).\n"
            . "3. The crew: on any day, the workers the board already uses plus the new work's must not pass the workers free that day (their days off count). Hired work uses no farm workers.\n"
            . "4. The other lots: do not stack heavy work of the new lot on the days other lots already have heavy work, when the task may move to a free day.\n"
            . "5. Order and spacing on one lot: keep the plan's order of work (plowing before harrowing, the basal fertilizer at or before the planting). No herbicide within 3 days of a fertilizer, no two sprays on one day, no spray the day after a fertilizer.\n"
            . "6. Timing: never place work before today; keep each task inside the days it may move between, or on its planned day when none are given; never move an ANCHOR task.\n\n"
            . "PLACE every new task that is not skipped: the planned day when it has no conflict, else the nearest day inside its range that clears the conflict. When nothing clears it, keep the planned day and report the conflict.\n\n"
            . "VERDICT: \"ok\" when the new work fits as planned; \"caution\" when there are conflicts your placement solves, or the farmer should know something; \"stop\" ONLY when the plan does not fit this season at all (a lot grows another crop, most of the work is already on the board, or the lot is past it).\n\n"
            . "Write for the farmer in plain words and short sentences, no dashes, no jargon. Name lots and tasks as written.\n"
            . 'Answer ONLY this JSON: {"verdict":"ok|caution|stop","headline":"one line","summary":"two to four sentences","conflicts":[{"id":"L1.2","kind":"duplicate|crop|crew|lots|order|spacing|timing","severity":"low|medium|high","text":"what and why"}],"skip":["L1.4"],"placement":[{"id":"L1.1","date":"YYYY-MM-DD","why":"short reason, only when moved"}],"tips":["up to three short tips"]}'
            . "\nThe placement lists EVERY id that is not skipped.";

        return [$prompt, $ids];
    }

    private function parse(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $t);
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $json = json_decode(substr($t, $a, $b - $a + 1), true);

        return is_array($json) && isset($json['verdict']) ? $json : null;
    }

    /**
     * Her answer, held to the rules: known ids only, dates real, never before
     * today, inside each task's range (else its planned day), anchors kept;
     * a task she did not place keeps its planned day.
     */
    private function settle(array $j, array $ids, array $ok): array
    {
        $today = now('Asia/Manila')->toDateString();
        $items = [];
        foreach ($ok as $k => $x) {
            foreach ($x['items'] as $it) {
                $items[$k . ':' . $it['key']] = $it;
            }
        }
        $name = function (string $tag) use ($ids, $ok): string {
            [$k, $key] = $ids[$tag] ?? [null, null];
            if ($k === null) {
                return '';
            }
            $it = collect($ok[$k]['items'])->firstWhere('key', $key);

            return ($it['title'] ?? '') . ' (' . $ok[$k]['plan']['name'] . ')';
        };
        $skip = [];
        foreach ((array) ($j['skip'] ?? []) as $tag) {
            if (isset($ids[(string) $tag])) {
                [$k, $key] = $ids[(string) $tag];
                if (! ($items[$k . ':' . $key]['anchor'] ?? false)) {
                    $skip[] = $k . ':' . $key;
                }
            }
        }
        $dates = [];
        $why = [];
        foreach ((array) ($j['placement'] ?? []) as $p) {
            $tag = (string) ($p['id'] ?? '');
            $date = (string) ($p['date'] ?? '');
            if (! isset($ids[$tag]) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            [$k, $key] = $ids[$tag];
            $it = $items[$k . ':' . $key];
            $lo = max($today, $it['atLo'] ?: $it['at']);
            $hi = $it['atHi'] && $it['atHi'] >= $lo ? $it['atHi'] : max($it['at'], $lo);
            if ($it['anchor'] || $date < $lo || $date > max($hi, Carbon::parse($it['at'])->addDays(3)->toDateString())) {
                $date = $it['at'];
            }
            $dates[$k][$key] = $date;
            if ($date !== $it['at'] && trim((string) ($p['why'] ?? '')) !== '') {
                $why[] = ['task' => $name($tag), 'from' => $it['at'], 'to' => $date, 'why' => mb_substr(trim((string) $p['why']), 0, 240)];
            }
        }
        foreach ($ids as [$k, $key]) {
            $dates[$k][$key] ??= $items[$k . ':' . $key]['at'];
        }
        $moved = 0;
        foreach ($dates as $k => $row) {
            foreach ($row as $key => $date) {
                $moved += $date !== $items[$k . ':' . $key]['at'] ? 1 : 0;
            }
        }
        $conflicts = [];
        foreach (array_slice((array) ($j['conflicts'] ?? []), 0, 20) as $c) {
            if (! is_array($c) || trim((string) ($c['text'] ?? '')) === '') {
                continue;
            }
            $conflicts[] = [
                'task' => $name((string) ($c['id'] ?? '')),
                'kind' => in_array($c['kind'] ?? '', ['duplicate', 'crop', 'crew', 'lots', 'order', 'spacing', 'timing'], true) ? $c['kind'] : 'timing',
                'severity' => in_array($c['severity'] ?? '', ['low', 'medium', 'high'], true) ? $c['severity'] : 'medium',
                'text' => mb_substr(trim((string) $c['text']), 0, 400),
            ];
        }
        $verdict = in_array($j['verdict'] ?? '', ['ok', 'caution', 'stop'], true) ? $j['verdict'] : 'caution';
        if ($verdict === 'ok' && $conflicts) {
            $verdict = 'caution';
        }

        return [
            'verdict' => $verdict, 'headline' => mb_substr(trim((string) ($j['headline'] ?? '')), 0, 200), 'summary' => mb_substr(trim((string) ($j['summary'] ?? '')), 0, 1200),
            'conflicts' => $conflicts, 'skip' => $skip, 'skipped' => array_map(fn ($s) => (function () use ($s, $items, $ok) {
                [$k, $key] = explode(':', $s, 2);
                $it = $items[$s] ?? null;

                return ($it['title'] ?? $key) . ' (' . ($ok[(int) $k]['plan']['name'] ?? '') . ')';
            })(), $skip),
            'dates' => $dates, 'moves' => array_slice($why, 0, 30), 'moved' => $moved,
            'tips' => array_values(array_slice(array_filter(array_map(fn ($t) => mb_substr(trim((string) $t), 0, 240), (array) ($j['tips'] ?? []))), 0, 3)),
        ];
    }

    /**
     * Put it on the board: a season with nothing on it by the plan's days; a
     * season with work only after Anee's check, by the crew's free days or by
     * her dates, and only once.
     */
    public function apply(Request $request)
    {
        if ($no = $this->refusal()) {
            return $no;
        }
        $placement = (string) $request->input('placement', 'plan');
        $portId = (int) $request->input('portId', 0);
        $port = null;
        if ($portId) {
            $port = DB::table('as_plan_ports')->where('id', $portId)->where('userId', (int) Auth::id())->where('deleteStatus', 1)->first();
            if (! $port) {
                return $this->json(false, 'That check is gone.', [], 404);
            }
            if ($port->status === 'applied') {
                return $this->json(false, 'This plan is already on that season\'s board.', [], 409);
            }
            if ($port->status !== 'ready') {
                return $this->json(false, 'Anee has not finished checking it yet.', [], 409);
            }
            $request->merge(['source' => $port->source, 'id' => $port->sourceId, 'scheduleId' => $port->scheduleId, 'map' => json_decode((string) $port->map, true) ?: []]);
        }
        [$row, $plan] = $this->planFrom($request);
        $s = $this->seasonFrom($request);
        $map = $this->mapFrom($request, $plan, $s);
        [$ok, $over] = $this->sorted($plan, $s, $map);
        if (! $ok) {
            return $this->json(false, 'This plan cannot go into ' . $s->title . ' any more.', ['over' => $over], 422);
        }
        $skip = [];
        if (! $port) {
            if (PlanPort::hasWork($s)) {
                return $this->json(false, $s->title . ' already has work on its board, so Anee checks the plan first.', ['needsCheck' => true], 409);
            }
            $dates = PlanPort::planDates($ok);
        } else {
            $res = json_decode((string) $port->result, true) ?: [];
            if (($res['anee']['verdict'] ?? '') === 'stop') {
                return $this->json(false, 'Anee found that this plan does not fit ' . $s->title . '.', [], 422);
            }
            $skip = (array) ($res['anee']['skip'] ?? []);
            if ($placement === 'anee') {
                $dates = PlanPort::planDates($ok);
                foreach ((array) ($res['anee']['dates'] ?? []) as $k => $row2) {
                    foreach ((array) $row2 as $key => $date) {
                        if (isset($dates[$k][$key])) {
                            $dates[$k][$key] = max((string) $date, now('Asia/Manila')->toDateString());
                        }
                    }
                }
            } else {
                // The crew's free days, counted again now: the board may have changed since the check.
                [$from, $to] = $this->span($ok);
                $dates = PlanPort::crewPlace($s, $ok, PlanPort::boardAround($s, $from, $to))['dates'];
            }
        }
        $made = PlanPort::intoSeason($plan, $s, $ok, $dates, $skip);
        if ($port) {
            DB::table('as_plan_ports')->where('id', $port->id)->update(['status' => 'applied', 'updated_at' => now()]);
        }
        $season = $this->remember($row, $s);

        return $this->json(true, $made['activities'] . ' ' . ($made['activities'] === 1 ? 'activity was' : 'activities were') . ' added to ' . $s->title
            . ($made['skipped'] ? ', ' . $made['skipped'] . ' already there left out' : '') . ($made['past'] ? ', ' . $made['past'] . ' past ones left out' : '') . '.', [
                'scheduleId' => (int) $s->id, 'title' => $s->title, 'url' => route('sm.activities', ['id' => $s->id]), 'made' => $made, 'season' => $season,
            ]);
    }

    /* ============================================================ the builder */

    /**
     * To the Protocol Builder: a new protocol counted the way the lot is
     * planted, or the version in use of one the member has, the plan's work
     * on that protocol's own count, in the order of its days. Sending the
     * same plan twice adds nothing twice.
     */
    public function builder(Request $request)
    {
        if ($no = $this->refusal()) {
            return $no;
        }
        $v = Validator::make($request->all(), ['mode' => 'required|in:new,existing', 'protocolId' => 'nullable|integer', 'title' => 'nullable|string|max:190', 'lot' => 'nullable|integer']);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        [$row, $plan] = $this->planFrom($request);
        $pl = collect($plan['lots'])->firstWhere('i', (int) $request->input('lot', $plan['lots'][0]['i'])) ?? $plan['lots'][0];
        $prefix = ($plan['source'] === 'landprep' ? 'lp' : 'cp') . $plan['id'] . ($plan['source'] === 'protocol' ? 'l' . $pl['i'] : '');
        $entries = function (array $counters) use ($plan, $pl, $row, $prefix): array {
            if ($plan['source'] === 'landprep') {
                return LP::toProtocol(LP::plainReport(json_decode((string) $row->report, true) ?: []), $counters, (int) $row->id);
            }

            return PlanPort::toBuilder($plan, $pl, $counters, $prefix);
        };
        if ($request->input('mode') === 'new') {
            $dayType = ProtocolBuilderController::fitDayType($pl['crop'] ?: null, $pl['dayType']);
            $tasks = $entries(ProtocolBuilderController::DAY_TYPES[$dayType]['counters'] ?? ['DAS']);
            $title = trim((string) $request->input('title')) ?: mb_substr(($plan['source'] === 'landprep' ? 'Land prep: ' : 'Protocol: ') . $row->title, 0, 190);
            $p = AsProtocol::create([
                'userId' => (int) Auth::id(), 'title' => mb_substr($title, 0, 190),
                'description' => 'From the ' . PlanPort::SOURCES[$plan['source']]['label'] . ': every step of "' . $row->title . '"' . ($plan['source'] === 'protocol' && count($plan['lots']) > 1 ? ' for ' . $pl['name'] : '') . ', on the protocol\'s own day count.',
                'tags' => [], 'crop' => $pl['crop'] ?: null, 'variety' => $pl['variety'] !== '' ? mb_substr($pl['variety'], 0, 120) : null,
                'dayType' => $dayType, 'tasks' => [], 'history' => ['undo' => [], 'redo' => []], 'rev' => 1, 'deleteStatus' => 1,
            ]);
            $ver = AsProtocolVersion::create(['protocolId' => $p->id, 'userId' => (int) Auth::id(), 'name' => 'Version 1', 'tasks' => $tasks, 'materials' => [],
                'rules' => null, 'files' => [], 'history' => ['undo' => [], 'redo' => []], 'rev' => 1, 'sortOrder' => 0, 'deleteStatus' => 1]);
            $p->forceFill(['versionId' => $ver->id])->save();
            $n = count(array_filter($tasks, fn ($t) => ($t['kind'] ?? 'task') !== 'divider'));

            return $this->json(true, 'A new protocol was made with ' . $n . ' steps.', ['protocolId' => (int) $p->id, 'url' => route('pb.open', ['id' => $p->id]), 'added' => $n, 'title' => $p->title]);
        }
        $p = AsProtocol::active()->where('userId', (int) Auth::id())->where('id', (int) $request->input('protocolId'))->first();
        if (! $p) {
            return $this->json(false, 'That protocol is not yours.', [], 404);
        }
        $ver = AsProtocolVersion::active()->where('protocolId', $p->id)->where('userId', (int) Auth::id())->orderByRaw('id = ? desc', [(int) $p->versionId])->orderBy('sortOrder')->orderBy('id')->first();
        if (! $ver) {
            return $this->json(false, 'Open that protocol once in the Protocol Builder, then send the plan again.', [], 409);
        }
        $have = array_values((array) ($ver->tasks ?? []));
        $ids = array_flip(array_map(fn ($t) => (string) ($t['id'] ?? ''), $have));
        $new = array_values(array_filter($entries(ProtocolBuilderController::DAY_TYPES[$p->dayType]['counters'] ?? ['DAS']), fn ($t) => ! isset($ids[$t['id']])));
        if (! array_filter($new, fn ($t) => ($t['kind'] ?? 'task') !== 'divider')) {
            return $this->json(true, 'This plan is already in that protocol.', ['protocolId' => (int) $p->id, 'url' => route('pb.open', ['id' => $p->id]), 'added' => 0, 'title' => $p->title]);
        }
        if (count($have) + count($new) > 300) {
            return $this->json(false, 'That protocol is too full to take ' . count($new) . ' more steps. Make a new protocol instead.', [], 422);
        }
        // Placed by the day count: the builder keeps its list in the order of
        // (count, day, place), so each step lands among the protocol's own on
        // its day; on the same day it follows what was there.
        $top = $have ? max(array_map(fn ($t) => (int) ($t['pos'] ?? 0), $have)) : 0;
        foreach ($new as $k => $t) {
            $new[$k]['pos'] = $top + 10 * ($k + 1);
        }
        $hist = is_array($ver->history) ? $ver->history : ['undo' => [], 'redo' => []];
        $hist['undo'] = array_slice(array_merge((array) ($hist['undo'] ?? []), [['tasks' => $have, 'materials' => array_values((array) ($ver->materials ?? [])), 'tab' => 'tasks']]), -15);
        $hist['redo'] = [];
        $ver->forceFill(['tasks' => array_merge($have, $new), 'history' => $hist, 'rev' => (int) $ver->rev + 1])->save();
        $added = count(array_filter($new, fn ($t) => ($t['kind'] ?? 'task') !== 'divider'));

        return $this->json(true, $added . ' steps were added to ' . $p->title . ', each on its day.', ['protocolId' => (int) $p->id, 'url' => route('pb.open', ['id' => $p->id]), 'added' => $added, 'title' => $p->title]);
    }
}
