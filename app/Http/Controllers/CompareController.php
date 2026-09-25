<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Manager\BaseScheduleController;
use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Models\AsFarmReport;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleLot;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropStages;
use App\Support\Region;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * COMPARE REPORTS, a Quick Tool (2026-09-25).
 *
 * It used to live inside one season's Reports hub, which quietly made the
 * obvious question impossible: this season against LAST season. Now each
 * side is chosen twice over -- the cropping schedule it comes from, then
 * the saved report on that season's shelf -- so a Labor Report of the wet
 * season can stand beside the dry season's.
 *
 * Who may read what is the season's own rule, asked through the same door
 * every report page uses (BaseScheduleController::schedule): an owner reads
 * all of their seasons, a worker the seasons their grant opens, and the
 * Reports grant decides looking (view) from making (edit). The tier is the
 * farm's (reportsAll), and Anee's read is the old flat price
 * (AiPrices 'compare') paid by the farm, on the old job walk.
 *
 * The saved comparisons stay where they always were: as_farm_reports rows
 * of kind 'compare'. croppingScheduleId is NOT NULL there, so a comparison
 * lives on Report A's season (a real season it truly draws from) and
 * params.scheduleIds names every season it reads; it is readable only
 * where ALL of them are. Rows saved before this carry no scheduleIds and
 * keep the rule they were written under (their own season), with the
 * sources' seasons checked too when the sources still exist.
 *
 * What the page draws is the app's own arithmetic, taken at the moment of
 * comparing: each saved report is read into figures (metrics, breakdowns,
 * facts, and for protocols the steps day by day) and the two are zipped
 * into A-vs-B rows. Anee reads those rows as well as the two texts, so her
 * words and the page's numbers are the same numbers.
 */
class CompareController extends BaseScheduleController
{
    /** The report kinds a comparison can be made of, in the hub's order. */
    public const KINDS = [
        'labor' => ['label' => 'Labor Report', 'icon' => 'images/icons/tea.png'],
        'expenses' => ['label' => 'Expenses Report', 'icon' => 'images/icons/money-bag.png'],
        'profit' => ['label' => 'Profit Report', 'icon' => 'images/icons/profit.png'],
        'season' => ['label' => 'Anee Season Report', 'icon' => 'images/anee/emoji/thinking.png'],
        'sofar' => ['label' => 'Analyze So Far', 'icon' => 'images/icons/calendar.png'],
        'protocol' => ['label' => 'Protocol', 'icon' => 'images/icons/checklist.png'],
    ];

    /** The shape a comparison's report is written in; older rows are 1. */
    private const VERSION = 2;

    private const PAGE = 20;

    /** The seasons this request may read, asked once. */
    private $seasonsMemo = null;

    /**
     * What each kind is compared on. Metrics: [label, unit, better, icon,
     * hint, hideZero]; better is 'higher', 'lower' or null (a difference,
     * not a verdict). Groups: [label, unit, hint, open-ended (keep the
     * biggest rows), both (drawn only when BOTH sides recorded it -- an
     * older save that never kept the list is not a row of zeros)]. Facts
     * are words side by side.
     */
    private const SPEC = [
        'labor' => [
            'metrics' => [
                'cost' => ['Labor cost', 'money', 'lower', 'coins', null, false],
                'workdays' => ['Worker-days', 'num1', null, 'people', 'whole days, plus half days counted as half', false],
                'perDay' => ['Cost per worker-day', 'money', 'lower', 'scale', null, false],
                'assignments' => ['Worker assignments', 'count', null, 'list', null, false],
                'activities' => ['Activities counted', 'count', null, 'check', null, false],
            ],
            'groups' => [
                'phases' => ['When the pay went', 'money', 'land preparation against the main crop', false, false],
                'types' => ['Pay by kind of work', 'money', 'by activity type', true, true],
                'workers' => ['Pay by worker', 'money', 'matched by name', true, false],
            ],
            'facts' => ['filters' => 'What was counted', 'dayType' => 'Day count'],
        ],
        'expenses' => [
            'metrics' => [
                'spend' => ['Total spent', 'money', 'lower', 'coins', null, false],
                'income' => ['Income recorded', 'money', 'higher', 'trend', null, true],
                'net' => ['Net (income less spend)', 'money', 'higher', 'scale', null, false],
                'entries' => ['Entries in the book', 'count', null, 'list', null, false],
            ],
            'groups' => [
                'cats' => ['Where the money went', 'money', 'by category', false, false],
                'months' => ['Month by month', 'money', 'each season from its first month', false, false],
            ],
            'facts' => ['filters' => 'What was counted'],
        ],
        'profit' => [
            'metrics' => [
                'profit' => ['Net profit', 'money', 'higher', 'trend', null, false],
                'revenue' => ['Money in', 'money', 'higher', 'coins', null, false],
                'cost' => ['Money out', 'money', 'lower', 'scale', null, false],
                'margin' => ['Margin', 'pct', 'higher', 'percent', 'profit as a share of money in', false],
            ],
            'groups' => [
                'cats' => ['Money out, by category', 'money', 'what the season cost', false, false],
                'lots' => ['Profit, lot by lot', 'money', 'matched by lot name', true, false],
            ],
            'facts' => [],
        ],
        'season' => [
            'metrics' => [
                'overall' => ['Overall score', 'score', 'higher', 'star', 'Anee\'s score out of 100', false],
            ],
            'groups' => [
                'scores' => ['The scores, one by one', 'score', 'out of 100', false, true],
            ],
            'facts' => ['headline' => 'Anee\'s headline'],
        ],
        'sofar' => [
            'metrics' => [
                'standing' => ['Standing', 'word', 'higher', 'flag', null, false],
                'score' => ['Score', 'score', 'higher', 'star', 'Anee\'s score out of 100', false],
                'onTime' => ['Work done on time', 'pct', 'higher', 'check', 'of the work planned up to that day', false],
                'overdue' => ['Overdue activities', 'count', 'lower', 'alert', null, false],
                'cost' => ['Spent so far', 'money', 'lower', 'coins', null, false],
                'revenue' => ['Money in so far', 'money', 'higher', 'trend', null, true],
            ],
            'groups' => [
                'scores' => ['The scores, one by one', 'score', 'out of 100', false, true],
                'risks' => ['Risks Anee saw', 'count', 'by how serious', false, false],
            ],
            'facts' => ['headline' => 'Anee\'s headline', 'asOf' => 'Read on', 'lots' => 'Where the crop stood'],
        ],
        'protocol' => [
            'metrics' => [
                'yield' => ['Yield', 'qty', 'higher', 'sack', null, false],
                'yieldValue' => ['Harvest value', 'money', 'higher', 'coins', 'yield times the price it sold at', false],
                'steps' => ['Steps done', 'count', null, 'list', null, false],
                'span' => ['Days from first step to last', 'days', null, 'clock', null, false],
                'crewDays' => ['Crew-days', 'num1', null, 'people', 'workers times whole or half days', false],
                'materials' => ['Material lines', 'count', null, 'box', null, false],
                'skipped' => ['Planned, never ticked', 'count', 'lower', 'alert', null, false],
            ],
            'groups' => [
                'types' => ['Steps by kind of work', 'count', 'by activity type', true, false],
            ],
            'facts' => [
                'lot' => 'Lot', 'crop' => 'Crop', 'variety' => 'Variety', 'size' => 'Size',
                'daySystem' => 'Day count', 'zeroDate' => 'Day zero', 'span' => 'Span', 'yields' => 'Harvest',
            ],
        ],
    ];

    /* =============================== PAGES ============================== */

    public function page(Request $request)
    {
        $this->gate(false, true);

        $canWrite = WorkerContext::canWriteModule('reports');
        $settings = AiSetting::current();

        return view('compare.index', [
            'canWrite' => $canWrite,
            // "Ask Anee about it" opens the chat, which is its own grant and
            // runs on the farm's plan.
            'canAsk' => WorkerContext::canUseModule('ai') && $this->payer()->canUseAi() && $settings->isUsable(),
            'openId' => (int) $request->query('open', 0),
            'seasonId' => (int) $request->query('season', 0),
            'kinds' => collect(self::KINDS)->map(fn ($k, $key) => ['key' => $key, 'label' => $k['label'], 'icon' => asset($k['icon'])])->values(),
            'aneeName' => $settings->assistantName,
            'aneeFace' => $settings->faceUrl(),
        ]);
    }

    /**
     * The old door, /app/sm-compare-report?id=SEASON[&open=ID]: tag
     * shelves and bookmarks still knock on it. The season rides along so
     * Report A starts in it.
     */
    public function legacy(Request $request)
    {
        return redirect()->route('compare.page', array_filter([
            'open' => (int) $request->query('open', 0) ?: null,
            'season' => (int) $request->query('id', 0) ?: null,
        ]));
    }

    /* ============================== THE WIZARD ========================== */

    /**
     * Everything the wizard needs: the seasons this person may read, how
     * many saved reports of each kind sit on each one, and the wallet for
     * Anee's read.
     */
    public function options()
    {
        $this->gate();
        $seasons = $this->visibleSeasons();
        $ids = $seasons->pluck('id')->all();

        $counts = [];
        if ($ids) {
            AsFarmReport::whereIn('croppingScheduleId', $ids)
                ->where('status', 'ready')->where('deleteStatus', 1)
                ->whereIn('kind', array_keys(self::KINDS))
                ->selectRaw('croppingScheduleId, kind, COUNT(*) AS n')
                ->groupBy('croppingScheduleId', 'kind')
                ->get()
                ->each(function ($r) use (&$counts) {
                    $counts[(int) $r->croppingScheduleId][$r->kind] = (int) $r->n;
                });
        }

        $payer = $this->payer();
        $credits = app(AiCreditService::class);

        return $this->jsonOk('ok', ['data' => [
            'seasons' => $seasons->map(fn ($s) => [
                'id' => (int) $s->id,
                'title' => (string) $s->title,
                'status' => (string) $s->status,
                'statusLabel' => $this->statusLabel((string) $s->status),
                'crop' => $s->cropType ? (CropStages::label($s->cropType) ?: null) : null,
                'icon' => $s->cropType ? CropStages::icon($s->cropType) : '🌱',
                'since' => $s->created_at?->format('M Y'),
                'counts' => (object) ($counts[(int) $s->id] ?? []),
            ])->values(),
            'price' => AiPrices::of('compare'),
            'balance' => round($credits->balance((int) $payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'canUseAi' => $payer->canUseAi() && AiSetting::current()->isUsable(),
            'canWrite' => WorkerContext::canWriteModule('reports'),
            'payerIsMe' => (int) $payer->id === (int) Auth::id(),
        ]]);
    }

    /** One season's shelf, one kind: what can stand on side A or B. */
    public function shelf(Request $request)
    {
        $this->gate();
        $schedule = $this->schedule((int) $request->query('scheduleId'));
        $kind = (string) $request->query('kind');
        if (! isset(self::KINDS[$kind])) {
            return $this->jsonFail('Choose what kind of report first.', 422);
        }

        $rows = AsFarmReport::where('croppingScheduleId', $schedule->id)
            ->where('kind', $kind)->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->limit(60)
            ->get(['id', 'userId', 'title', 'description', 'params', 'credits', 'created_at']);
        $names = $this->authorNames($rows->pluck('userId')->all());

        return $this->jsonOk('ok', ['data' => ['rows' => $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'title' => (string) $r->title,
            'description' => $r->description,
            'when' => $r->created_at?->format('M j, Y'),
            'author' => (int) $r->userId === (int) Auth::id() ? null : ($names[(int) $r->userId] ?? null),
            'filtered' => $this->filterWords($kind, (array) ($r->params ?? [])) !== null,
        ])->values()]]);
    }

    /* ============================ COMPARING ============================= */

    /**
     * Two saved reports of one kind, side by side. By hand it is free and
     * instant; with Anee's read the flat price is charged on the job walk
     * the other analyses use. `replaces` names a free comparison of the
     * same two that this one supersedes: once her read has landed, the
     * free copy leaves the shelf, so asking later is not a duplicate.
     */
    public function generate(Request $request)
    {
        $this->gate(true);
        $aId = (int) $request->input('aId');
        $bId = (int) $request->input('bId');
        $withAi = $request->boolean('withAi');
        if ($aId <= 0 || $bId <= 0 || $aId === $bId) {
            return $this->jsonFail('Pick two different saved reports.', 422);
        }

        $found = AsFarmReport::whereIn('id', [$aId, $bId])
            ->where('status', 'ready')->where('deleteStatus', 1)
            ->whereIn('kind', array_keys(self::KINDS))
            ->get()->keyBy('id');
        $a = $found->get($aId);
        $b = $found->get($bId);
        if (! $a || ! $b) {
            return $this->jsonFail('One of those reports is no longer on its shelf. Pick again.', 422);
        }
        if ($a->kind !== $b->kind) {
            return $this->jsonFail('Compare two reports of the same type — apples with apples.', 422);
        }

        // The season's own door, both times: a season this person may not
        // read stops here exactly as its report page would.
        $seasonA = $this->schedule($a->croppingScheduleId);
        $seasonB = (int) $b->croppingScheduleId === (int) $seasonA->id ? $seasonA : $this->schedule($b->croppingScheduleId);
        if (! Tier::scheduleCan($seasonA, 'reportsAll')) {
            Tier::deny('Compare Reports comes with the Solo Farmer plan — Libre includes the Labor report.');
        }
        // The diary line belongs to the season the comparison lives on.
        $request->attributes->set('auditScheduleId', (int) $seasonA->id);

        $snap = $this->snapshot($a, $b, $seasonA, $seasonB);
        $title = $snap['title'];
        $report = $snap['report'];
        $params = [
            'aId' => $a->id, 'bId' => $b->id, 'withAi' => $withAi,
            'kind' => $a->kind, 'v' => self::VERSION,
            'scheduleIds' => array_values(array_unique([(int) $seasonA->id, (int) $seasonB->id])),
        ];
        $body = $this->bodyText($report, $title);

        if (! $withAi) {
            $row = AsFarmReport::create([
                'userId' => Auth::id(), 'croppingScheduleId' => $seasonA->id,
                'kind' => 'compare', 'title' => mb_substr($title, 0, 190),
                'params' => $params, 'report' => $report, 'body' => mb_substr($body, 0, 60000),
                'status' => 'ready', 'deleteStatus' => 1,
            ]);

            return $this->jsonOk('Comparison saved.', ['data' => $this->payload($row)]);
        }

        $payer = $this->payer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->jsonFail('Anee\'s read needs the AI Technician on the farm\'s plan. You can still compare by hand.', 403);
        }
        $price = AiPrices::of('compare');
        $balance = $credits->balance((int) $payer->id);
        if ($balance < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('Anee\'s read costs ' . $price . ' credits and '
                . ((int) $payer->id === (int) Auth::id() ? 'you have ' : 'the farm has ')
                . number_format((int) floor($balance)) . '. You can still compare by hand.', 402, ['outOfCredits' => true]);
        }

        /* One in flight at a time: a double press must not buy two. A row
           whose heart has stopped is not in flight; it is failed here. */
        $standing = AsFarmReport::where('userId', Auth::id())->where('kind', 'compare')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(15))
            ->orderByDesc('id')->first();
        if ($standing && ! $this->dead($standing)) {
            return $this->jsonOk('Already working on it.', ['data' => ['pending' => true, 'id' => (int) $standing->id]]);
        }

        $replaces = (int) $request->input('replaces', 0);
        if ($replaces > 0) {
            $params['replaces'] = $replaces;
        }
        $params['phase'] = 'start';
        $params['try'] = 1;
        $row = AsFarmReport::create([
            'userId' => Auth::id(), 'croppingScheduleId' => $seasonA->id,
            'kind' => 'compare', 'title' => mb_substr($title, 0, 190),
            'params' => $params, 'report' => $report, 'body' => mb_substr($body, 0, 60000),
            'status' => 'pending', 'deleteStatus' => 1,
        ]);
        $id = (int) $row->id;
        $prompt = $this->prompt($report, $a, $b);

        /* Should PHP itself be cut off mid-run, the row must not stay
           pending -- that blocks the next run for a quarter of an hour. */
        register_shutdown_function(function () use ($id) {
            try {
                AsFarmReport::where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed', 'deleteStatus' => 0,
                    'error' => 'The comparison took too long and was stopped. Nothing was charged — please try again.',
                ]);
            } catch (\Throwable $e) {
                // Nothing more to do at shutdown.
            }
        });

        /* The gateway must not wait on the model -- the sisters' walk. */
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $id,
            ]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $prompt, $price);
            exit;
        }

        @set_time_limit(300);
        $this->runJob($id, (int) $payer->id, $settings, $prompt, $price);

        return $this->job($id);
    }

    /** The model call and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt, int $price): void
    {
        $ai = app(AiClient::class);
        $credits = app(AiCreditService::class);

        /* The heartbeat: before every model call the row says where it is
           (params.phase/try) and touches updated_at, so a run killed under
           it (a deploy, a restart) is told apart from a slow one. */
        $beat = function (string $phase, int $try = 1) use ($id): void {
            $row = AsFarmReport::find($id);
            if (! $row || $row->status !== 'pending') {
                return;
            }
            $p = (array) ($row->params ?? []);
            $p['phase'] = $phase;
            $p['try'] = $try;
            $row->params = $p;
            $row->updated_at = now();
            $row->save();
        };

        try {
            $result = $ai->askForJson($settings, $prompt, 3000, fn (string $t) => $this->parseAnalysis($t), ['onPhase' => $beat]);
            $analysis = $result['data'];
            if ($analysis === null) {
                Log::warning('compare: unparsable answer', ['head' => mb_substr((string) ($result['text'] ?? ''), 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'Anee\'s read came back unreadable. Nothing was charged — please try again.');
            }

            $row = AsFarmReport::find($id);
            $note = AiUsage::record('compare', (int) $row->userId, $payerId, $id, $settings, $result, $price);
            $credits->chargeAllowingNegative($payerId, (float) $price,
                mb_substr('Comparison analysis — ' . mb_substr((string) $row->title, 0, 140) . $note, 0, 250));

            $rep = (array) $row->report;
            $rep['analysis'] = $analysis;
            $p = (array) ($row->params ?? []);
            $replaces = (int) ($p['replaces'] ?? 0);
            unset($p['phase'], $p['try']);
            $row->update([
                'report' => $rep,
                'params' => $p,
                'body' => mb_substr($this->bodyText($rep, (string) $row->title), 0, 60000),
                'credits' => $price,
                'status' => 'ready',
                'error' => null,
            ]);

            // The free copy of the same two leaves the shelf: this one is it now.
            if ($replaces > 0) {
                AsFarmReport::where('id', $replaces)->where('userId', $row->userId)
                    ->where('kind', 'compare')->where('credits', 0)->where('deleteStatus', 1)
                    ->update(['deleteStatus' => 0]);
            }
        } catch (\Throwable $e) {
            report($e);
            AsFarmReport::where('id', $id)->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'deleteStatus' => 0,   // failed runs delist themselves
            ]);
        }
    }

    /** No heartbeat for longer than one model call may take, or too old. */
    private function dead(AsFarmReport $r): bool
    {
        $beatAt = $r->updated_at ?: $r->created_at;
        if (! $beatAt) {
            return true;
        }

        return $beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60))
            || ($r->created_at && $r->created_at->lt(now()->subMinutes(15)));
    }

    /** Where a job stands -- polled by the page until ready or failed. */
    public function job(int $id)
    {
        $r = AsFarmReport::where('userId', Auth::id())->where('id', $id)->where('kind', 'compare')->first();
        if (! $r) {
            return $this->jsonFail('That comparison is gone.', 404);
        }
        if ($r->status === 'pending') {
            if ($this->dead($r)) {
                $why = ($r->created_at && $r->created_at->lt(now()->subMinutes(15)))
                    ? 'The comparison took too long and was stopped. Nothing was charged — please try again.'
                    : 'Anee was interrupted mid-way (the server restarted under her). Nothing was charged — please run it again.';
                AsFarmReport::where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed', 'deleteStatus' => 0, 'error' => $why,
                ]);

                return $this->jsonFail($why, 422, ['data' => ['status' => 'failed']]);
            }
            $p = (array) ($r->params ?? []);

            return $this->jsonOk('Working…', ['data' => [
                'pending' => true, 'id' => (int) $r->id, 'status' => 'pending',
                'phase' => (string) ($p['phase'] ?? 'start'),
                'try' => (int) ($p['try'] ?? 1),
                'since' => $r->created_at ? (int) max(0, now()->diffInSeconds($r->created_at, true)) : 0,
                'beatAgo' => (int) max(0, now()->diffInSeconds($r->updated_at ?: $r->created_at, true)),
            ]]);
        }
        if ($r->status === 'failed' || (int) $r->deleteStatus !== 1) {
            return $this->jsonFail($r->error ?: 'The comparison failed. Nothing was charged.', 422, ['data' => ['status' => 'failed']]);
        }

        return $this->jsonOk('ok', ['data' => $this->payload($r) + ['status' => 'ready']]);
    }

    /* ============================ THE SHELF ============================= */

    /**
     * The saved comparisons this person may read, newest first, twenty to
     * a page, with a search over the name and the description.
     */
    public function saved(Request $request)
    {
        $this->gate();
        $seasons = $this->visibleSeasons();
        $visible = $seasons->pluck('id')->map(fn ($v) => (int) $v)->all();
        $titles = $seasons->pluck('title', 'id')->all();
        $page = max(1, (int) $request->query('page', 1));
        $q = trim((string) $request->query('q', ''));

        if (! $visible) {
            return $this->jsonOk('ok', ['data' => ['rows' => [], 'total' => 0, 'hasMore' => false, 'page' => 1]]);
        }

        $query = AsFarmReport::where('kind', 'compare')->where('status', 'ready')->where('deleteStatus', 1)
            ->whereIn('croppingScheduleId', $visible);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like));
        }
        $rows = $query->orderByDesc('id')->limit(400)
            ->get(['id', 'userId', 'croppingScheduleId', 'title', 'description', 'params', 'credits', 'created_at']);

        [$srcSeason, $srcKind] = $this->legacySources($rows);
        $alive = $this->aliveSeasons($rows, $srcSeason, $visible);

        $readable = $rows->filter(fn ($r) => $this->readable($this->seasonIdsOf($r, $srcSeason), $visible, $alive))->values();
        $total = $readable->count();
        $slice = $readable->slice(($page - 1) * self::PAGE, self::PAGE)->values();
        $names = $this->authorNames($slice->pluck('userId')->all());

        return $this->jsonOk('ok', ['data' => [
            'rows' => $slice->map(function ($r) use ($titles, $srcSeason, $srcKind, $names) {
                $p = (array) ($r->params ?? []);
                $kind = (string) ($p['kind'] ?? ($srcKind[(int) $r->id] ?? ''));

                return [
                    'id' => (int) $r->id,
                    'title' => (string) $r->title,
                    'description' => $r->description,
                    'kind' => $kind,
                    'kindLabel' => self::KINDS[$kind]['label'] ?? 'Report',
                    'seasons' => collect($this->seasonIdsOf($r, $srcSeason))
                        ->map(fn ($id) => $titles[$id] ?? null)->filter()->unique()->values(),
                    'when' => $r->created_at?->format('M j, Y'),
                    'anee' => (float) $r->credits > 0,
                    'credits' => (float) $r->credits,
                    'mine' => (int) $r->userId === (int) Auth::id(),
                    'author' => (int) $r->userId === (int) Auth::id() ? null : ($names[(int) $r->userId] ?? null),
                ];
            }),
            'total' => $total,
            'page' => $page,
            'hasMore' => $total > $page * self::PAGE,
        ]]);
    }

    /** One saved comparison, whole -- an old one read into today's shape. */
    public function one(int $id)
    {
        $this->gate();
        $r = $this->readableRow($id);
        if (! $r) {
            return $this->jsonFail('That comparison is gone.', 404);
        }

        return $this->jsonOk('ok', ['data' => $this->payload($r)]);
    }

    /** Rename a saved comparison, describe it. The author's pen only. */
    public function meta(Request $request)
    {
        $this->gate(true);
        $r = AsFarmReport::where('userId', Auth::id())->where('kind', 'compare')
            ->where('id', (int) $request->input('id'))->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->jsonFail('That comparison no longer exists.', 404);
        }
        $title = trim((string) $request->input('title'));
        $desc = trim((string) $request->input('description', ''));
        if ($title === '') {
            return $this->jsonFail('Give it a name.', 422);
        }
        if (mb_strlen($title) > 191 || mb_strlen($desc) > 2000) {
            return $this->jsonFail('That is longer than a name or description can be.', 422);
        }
        $r->update(['title' => $title, 'description' => $desc !== '' ? $desc : null]);

        return $this->jsonOk('Comparison updated.', ['data' => [
            'id' => (int) $r->id, 'title' => $r->title, 'description' => $r->description,
        ]]);
    }

    public function destroy(int $id)
    {
        $this->gate(true);
        $r = AsFarmReport::where('kind', 'compare')->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->jsonFail('That comparison is already gone.', 404);
        }
        if ((int) $r->userId !== (int) Auth::id()) {
            return $this->jsonFail('Only the person who made a comparison can delete it.', 403);
        }
        $r->update(['deleteStatus' => 0]);

        return $this->jsonOk('Comparison removed.');
    }

    /* ============================== DOORS =============================== */

    /**
     * The tier is the farm's; the rest is the worker's grant. A GET for the
     * page gets a page back, everything else a JSON refusal the page's
     * toast already knows how to say.
     */
    private function gate(bool $write = false, bool $page = false): void
    {
        if (! Tier::farmCan('reportsAll')) {
            Tier::deny('Compare Reports comes with the Solo Farmer plan — Libre includes the Labor report.');
        }
        $may = WorkerContext::canView() && ($write ? WorkerContext::canWriteModule('reports') : WorkerContext::canUseModule('reports'));
        if ($may) {
            return;
        }
        $message = $write && WorkerContext::canUseModule('reports')
            ? 'You have view-only access to Reports on this farm.'
            : 'The farm owner has not given you access to Reports.';
        if ($page) {
            abort(response()->view('sm.no-access', ['what' => 'Reports', 'backUrl' => null, 'backLabel' => null], 403));
        }
        abort(response()->json(['success' => false, 'message' => $message], 403));
    }

    /** The farm pays for Anee's read, as it does for every report of hers. */
    private function payer(): User
    {
        $id = WorkerContext::effectiveOwnerId();

        return $id === (int) Auth::id() ? Auth::user() : (User::find($id) ?? Auth::user());
    }

    /**
     * The seasons this request may read: the farm it stands in, narrowed by
     * the grant exactly as schedule() narrows it -- archived and closed
     * seasons included, because last season is the point.
     */
    private function visibleSeasons()
    {
        if ($this->seasonsMemo !== null) {
            return $this->seasonsMemo;
        }
        if (! WorkerContext::canView()) {
            return $this->seasonsMemo = collect();
        }

        return $this->seasonsMemo = AsCroppingSchedule::active()
            ->forClient(WorkerContext::effectiveOwnerId())
            ->orderByDesc('id')
            ->get(['id', 'title', 'status', 'cropType', 'created_at']);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Completed',
            'archived' => 'Archived',
            'draft' => 'Draft',
            'setup' => 'Being set up',
            default => 'In progress',
        };
    }

    /** First and last names of the people who saved these rows. */
    private function authorNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($i) => $i > 0 && $i !== (int) Auth::id())));
        if (! $ids) {
            return [];
        }

        return User::whereIn('id', $ids)->get(['id', 'firstName', 'lastName'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->firstName . ' ' . $u->lastName) ?: 'A teammate'])->all();
    }

    /**
     * Rows saved before scheduleIds existed name their two sources only by
     * id; this reads those sources' seasons (and kinds) in one query each.
     *
     * @return array{0: array<int,int>, 1: array<int,string>} source id => season id, comparison id => kind
     */
    private function legacySources($rows): array
    {
        $old = $rows->filter(fn ($r) => empty(((array) ($r->params ?? []))['scheduleIds']));
        if ($old->isEmpty()) {
            return [[], []];
        }
        $src = [];
        foreach ($old as $r) {
            $p = (array) ($r->params ?? []);
            foreach (['aId', 'bId'] as $k) {
                if (! empty($p[$k])) {
                    $src[] = (int) $p[$k];
                }
            }
        }
        $seasonOf = $src ? AsFarmReport::whereIn('id', array_unique($src))->pluck('croppingScheduleId', 'id')
            ->map(fn ($v) => (int) $v)->all() : [];
        $kinds = AsFarmReport::whereIn('id', $old->pluck('id')->all())
            ->selectRaw("id, JSON_UNQUOTE(JSON_EXTRACT(report, '$.a.kind')) AS srcKind")
            ->pluck('srcKind', 'id')->map(fn ($v) => (string) $v)->all();

        return [$seasonOf, $kinds];
    }

    /** Every season a comparison reads. */
    private function seasonIdsOf(AsFarmReport $r, array $srcSeason): array
    {
        $p = (array) ($r->params ?? []);
        if (! empty($p['scheduleIds'])) {
            return array_values(array_unique(array_map('intval', (array) $p['scheduleIds'])));
        }
        $ids = [(int) $r->croppingScheduleId];
        foreach (['aId', 'bId'] as $k) {
            $sid = $srcSeason[(int) ($p[$k] ?? 0)] ?? null;
            if ($sid) {
                $ids[] = (int) $sid;
            }
        }

        return array_values(array_unique($ids));
    }

    /** Of the seasons these rows read beyond the visible ones, which still exist. */
    private function aliveSeasons($rows, array $srcSeason, array $visible): array
    {
        $all = [];
        foreach ($rows as $r) {
            foreach ($this->seasonIdsOf($r, $srcSeason) as $sid) {
                if (! in_array($sid, $visible, true)) {
                    $all[] = $sid;
                }
            }
        }
        if (! $all) {
            return [];
        }

        return AsCroppingSchedule::active()->whereIn('id', array_unique($all))->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Readable when every season it reads is one this person may read. A
     * season deleted since has nothing left to guard (the comparison keeps
     * its own copy); a season that exists and is not theirs shuts it.
     */
    private function readable(array $seasonIds, array $visible, array $alive): bool
    {
        foreach ($seasonIds as $sid) {
            if (in_array($sid, $visible, true)) {
                continue;
            }
            if (in_array($sid, $alive, true)) {
                return false;
            }
        }

        return true;
    }

    private function readableRow(int $id): ?AsFarmReport
    {
        $r = AsFarmReport::where('id', $id)->where('kind', 'compare')
            ->where('status', 'ready')->where('deleteStatus', 1)->first();
        if (! $r) {
            return null;
        }
        $visible = $this->visibleSeasons()->pluck('id')->map(fn ($v) => (int) $v)->all();
        if (! in_array((int) $r->croppingScheduleId, $visible, true)) {
            return null;
        }
        [$srcSeason] = $this->legacySources(collect([$r]));
        $alive = $this->aliveSeasons(collect([$r]), $srcSeason, $visible);

        return $this->readable($this->seasonIdsOf($r, $srcSeason), $visible, $alive) ? $r : null;
    }

    /* ========================= THE COMPARISON =========================== */

    /** What the page draws and what the job hands back. */
    private function payload(AsFarmReport $r): array
    {
        $rep = (array) ($r->report ?? []);
        if ((int) ($rep['v'] ?? 1) < self::VERSION) {
            $rep = $this->upgrade($r, $rep);
        }

        return [
            'id' => (int) $r->id,
            'title' => (string) $r->title,
            'description' => $r->description,
            'kind' => 'compare',
            'report' => $rep,
            'credits' => (float) $r->credits,
            'when' => $r->created_at?->format('M j, Y'),
            'mine' => (int) $r->userId === (int) Auth::id(),
        ];
    }

    /**
     * Both sides frozen as they are now: who they are (season, date, lot,
     * author), their text, and the figures zipped into A-vs-B rows.
     */
    private function snapshot(AsFarmReport $a, AsFarmReport $b, AsCroppingSchedule $sa, AsCroppingSchedule $sb): array
    {
        $kind = $a->kind;
        $names = $this->authorNames([$a->userId, $b->userId]);
        $lots = $this->lotNames([$a, $b]);
        $fa = $this->figures($kind, $a, $lots);
        $fb = $this->figures($kind, $b, $lots);
        $sideA = $this->side($a, $sa, $fa, $names);
        $sideB = $this->side($b, $sb, $fb, $names);

        $report = [
            'v' => self::VERSION,
            'kind' => $kind,
            'kindLabel' => self::KINDS[$kind]['label'],
            'a' => $sideA,
            'b' => $sideB,
            'sameSeason' => (int) $sa->id === (int) $sb->id,
        ] + $this->pair($kind, $fa, $fb) + ['analysis' => null];

        return ['title' => $this->titleFor($report), 'report' => $report];
    }

    /** A row saved before the figures were taken, read into today's shape. */
    private function upgrade(AsFarmReport $r, array $rep): array
    {
        $a = (array) ($rep['a'] ?? []);
        $b = (array) ($rep['b'] ?? []);
        $kind = (string) ($a['kind'] ?? ($b['kind'] ?? ''));
        $kindB = (string) ($b['kind'] ?? $kind);
        // Before the same-kind rule two different kinds could be stacked; they
        // keep their texts and their read, but there are no figures to pair.
        $mixed = $kindB !== $kind;
        $out = [
            'v' => self::VERSION, 'legacy' => true, 'mixed' => $mixed, 'kind' => $kind,
            'kindLabel' => $mixed
                ? (self::KINDS[$kind]['label'] ?? 'Report') . ' & ' . (self::KINDS[$kindB]['label'] ?? 'Report')
                : (self::KINDS[$kind]['label'] ?? 'Report'),
            'analysis' => $rep['analysis'] ?? null,
            'metrics' => [], 'groups' => [], 'facts' => [], 'timeline' => [],
        ];
        $src = AsFarmReport::whereIn('id', array_filter([(int) ($a['id'] ?? 0), (int) ($b['id'] ?? 0)]))->get()->keyBy('id');
        $visible = $this->visibleSeasons()->keyBy('id');
        $names = $this->authorNames($src->pluck('userId')->all());
        $lots = $this->lotNames($src->all());
        $figs = [];
        foreach (['a' => $a, 'b' => $b] as $k => $old) {
            $s = $src->get((int) ($old['id'] ?? 0));
            $season = $s ? $visible->get((int) $s->croppingScheduleId) : null;
            if ($s && isset(self::KINDS[$s->kind])) {
                $fig = $this->figures($s->kind, $s, $lots);
                if (! $mixed) {
                    $figs[$k] = $fig;
                }
                $side = $this->side($s, $season, $fig, $names);
                // What was compared is what was saved then.
                $side['body'] = (string) ($old['body'] ?? $side['body']);
                $side['title'] = (string) ($old['title'] ?? $side['title']);
                $out[$k] = $side;
            } else {
                $out[$k] = [
                    'id' => (int) ($old['id'] ?? 0), 'kind' => (string) ($old['kind'] ?? $kind), 'title' => (string) ($old['title'] ?? ''),
                    'body' => (string) ($old['body'] ?? ''), 'season' => null, 'when' => null, 'gone' => true,
                ];
            }
        }
        if (isset($figs['a'], $figs['b'])) {
            $out = array_merge($out, $this->pair($kind, $figs['a'], $figs['b']));
        }
        $out['sameSeason'] = ($out['a']['scheduleId'] ?? -1) === ($out['b']['scheduleId'] ?? -2);

        return $out;
    }

    /** One side: who it is, and what it says. */
    private function side(AsFarmReport $r, ?AsCroppingSchedule $s, array $fig, array $names): array
    {
        $p = (array) ($r->params ?? []);

        return [
            'id' => (int) $r->id,
            'kind' => (string) $r->kind,
            'title' => (string) $r->title,
            'description' => $r->description,
            'body' => mb_substr((string) $r->body, 0, 25000),
            'when' => $r->created_at?->format('M j, Y'),
            'whenIso' => $r->created_at?->toIso8601String(),
            'scheduleId' => (int) $r->croppingScheduleId,
            'season' => $s?->title,
            'seasonStatus' => $s ? $this->statusLabel((string) $s->status) : null,
            'crop' => ($s && $s->cropType) ? (CropStages::label($s->cropType) ?: null) : null,
            'cropIcon' => ($s && $s->cropType) ? CropStages::icon($s->cropType) : null,
            'subject' => $fig['subject'] ?? null,
            'filtered' => $this->filterWords((string) $r->kind, $p, is_array($r->report) ? $r->report : null),
            'author' => (int) $r->userId === (int) Auth::id() ? null : ($names[(int) $r->userId] ?? null),
            'authorId' => (int) $r->userId,
        ];
    }

    private function titleFor(array $rep): string
    {
        $k = (string) ($rep['kindLabel'] ?? 'Reports');
        $a = (array) $rep['a'];
        $b = (array) $rep['b'];
        $withSubject = fn (array $s) => (string) ($s['season'] ?? 'A season') . (! empty($s['subject']) ? ' (' . $s['subject'] . ')' : '');
        if (empty($rep['sameSeason'])) {
            return $k . ' · ' . $withSubject($a) . ' vs ' . $withSubject($b);
        }
        if (! empty($a['subject']) && ! empty($b['subject']) && $a['subject'] !== $b['subject']) {
            return $k . ' · ' . $a['subject'] . ' vs ' . $b['subject'] . ' · ' . ($a['season'] ?? '');
        }
        // One season twice: say what tells the two apart -- the narrowing,
        // the day, or the hour they were saved.
        if (empty($a['filtered']) !== empty($b['filtered'])) {
            $say = fn (array $s) => ! empty($s['filtered']) ? 'filtered' : 'whole season';

            return $k . ' · ' . ($a['season'] ?? '') . ' · ' . $say($a) . ' vs ' . $say($b);
        }
        if (($a['when'] ?? '') !== ($b['when'] ?? '')) {
            return $k . ' · ' . ($a['season'] ?? '') . ' · ' . ($a['when'] ?? '') . ' vs ' . ($b['when'] ?? '');
        }
        $at = fn (array $s) => ! empty($s['whenIso']) ? \Illuminate\Support\Carbon::parse($s['whenIso'])->format('g:i A') : '#' . $s['id'];

        return $k . ' · ' . ($a['season'] ?? '') . ' · ' . ($a['when'] ?? '') . ', ' . $at($a) . ' vs ' . $at($b);
    }

    /** Lot names for the reports that name a lot in their params. */
    private function lotNames(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            $lid = (int) (((array) ($r->params ?? []))['lotId'] ?? 0);
            if ($lid > 0) {
                $ids[] = $lid;
            }
        }

        return $ids ? AsScheduleLot::whereIn('id', array_unique($ids))->pluck('lotName', 'id')->all() : [];
    }

    /**
     * What a report was narrowed to, in words -- null when it counted the
     * whole season. Labor keeps its filters in the report, expenses in
     * the params.
     */
    private function filterWords(string $kind, array $params, ?array $report = null): ?string
    {
        $words = [];
        if ($kind === 'labor') {
            $f = (array) (($report ?? [])['filters'] ?? $params);
            if (! empty($f['lotIds'])) $words[] = 'some lots';
            if (! empty($f['workerIds'])) $words[] = 'some workers';
            if (! empty($f['groupIds'])) $words[] = 'some groups';
            if (! empty($f['startDate']) || ! empty($f['endDate'])) $words[] = 'a date range';
            if (($f['dasMin'] ?? null) !== null || ($f['dasMax'] ?? null) !== null) $words[] = 'a day-count range';
        } elseif ($kind === 'expenses') {
            if (! empty($params['lotIds'])) $words[] = 'some lots';
            if (! empty($params['cats'])) $words[] = 'some categories';
            if (! empty($params['from']) || ! empty($params['to'])) $words[] = 'a date range';
            if (isset($params['dayMin']) || isset($params['dayMax'])) $words[] = 'a day-count range';
            if (! empty($params['status']) && $params['status'] !== 'all') $words[] = $params['status'] === 'done' ? 'done work only' : 'planned work only';
            if (! empty($params['invKind'])) $words[] = 'one kind of stock';
        }

        return $words ? 'Filtered to ' . implode(', ', $words) : null;
    }

    /* -------------------------- the figures ----------------------------- */

    /**
     * One saved report read into figures: m (metrics), g (breakdowns as
     * [label, value, note?] rows), f (facts in words), u (units), steps
     * (a protocol's day by day) and subject (the lot it is about). An
     * empty array when the report carries nothing to read (old text-only
     * saves) -- the page then lays the two texts side by side.
     */
    private function figures(string $kind, AsFarmReport $row, array $lots = []): array
    {
        $r = is_array($row->report) ? $row->report : [];
        try {
            $fig = match ($kind) {
                'labor' => $this->laborFigures($r),
                'expenses' => $this->expensesFigures($r),
                'profit' => $this->profitFigures($r, (string) $row->body),
                'season' => $this->seasonFigures($r),
                'sofar' => $this->sofarFigures($r),
                'protocol' => $this->protocolFigures($r),
                default => [],
            };
        } catch (\Throwable $e) {
            report($e);
            $fig = [];
        }
        if ($kind === 'labor' && $fig) {
            $fig['f']['filters'] = $this->filterWords('labor', (array) ($row->params ?? []), $r) ?? 'The whole season';
        }
        if ($kind === 'expenses' && $fig) {
            $fig['f']['filters'] = $this->filterWords('expenses', (array) ($row->params ?? [])) ?? 'The whole season';
        }
        if ($kind === 'sofar') {
            $lid = (int) (((array) ($row->params ?? []))['lotId'] ?? 0);
            $fig['subject'] = $lid ? ($lots[$lid] ?? null) : null;
        }

        return $fig;
    }

    private function laborFigures(array $r): array
    {
        if (! isset($r['grandTotal']) && ! isset($r['totals'])) {
            return [];
        }
        $t = (array) ($r['totals'] ?? []);
        $wd = (float) ($t['wholeDays'] ?? 0) + (float) ($t['halfDays'] ?? 0) / 2;
        $cost = (float) ($r['grandTotal'] ?? 0);
        $ph = (array) ($r['phases'] ?? []);

        $workers = [];
        foreach ((array) ($r['perWorker'] ?? []) as $w) {
            $name = trim((string) ($w['name'] ?? ''));
            if ($name === '' || (int) ($w['assignmentCount'] ?? 0) <= 0) {
                continue;
            }
            $workers[$name] = ($workers[$name] ?? 0) + (float) ($w['total'] ?? 0);
        }
        $types = [];
        foreach ((array) ($r['perActivity'] ?? []) as $act) {
            $label = (string) ($act['typeLabel'] ?? '');
            if ($label === '' && ! empty($act['activityType'])) {
                $label = AsScheduleActivity::ACTIVITY_TYPES[$act['activityType']] ?? '';
            }
            if ($label === '') {
                continue;   // saves older than the type column carry none
            }
            $types[$label] = ($types[$label] ?? 0) + (float) ($act['cost'] ?? 0);
        }

        return [
            'm' => [
                'cost' => $cost,
                'workdays' => $wd,
                'perDay' => $wd > 0 ? round($cost / $wd, 2) : null,
                'assignments' => isset($t['totalAssignments']) ? (int) $t['totalAssignments'] : null,
                'activities' => isset($r['totalActivities']) ? (int) $r['totalActivities'] : null,
            ],
            'g' => [
                'phases' => [
                    ['Land preparation (before day 0)', (float) (($ph['preDayZero'] ?? [])['cost'] ?? 0)],
                    ['Main cropping (day 0 on)', (float) (($ph['cropping'] ?? [])['cost'] ?? 0)],
                    ['Not anchored to a day', (float) (($ph['unanchored'] ?? [])['cost'] ?? 0)],
                ],
                'types' => $this->rows($types),
                'workers' => $this->rows($workers),
            ],
            'f' => ['dayType' => (string) ($r['dayType'] ?? '') ?: null],
        ];
    }

    private function expensesFigures(array $r): array
    {
        if (! isset($r['spend']) && ! isset($r['totals'])) {
            return [];
        }
        $t = (array) ($r['totals'] ?? []);
        $pm = (array) ($r['perMonth'] ?? []);
        ksort($pm);
        $months = [];
        foreach ($pm as $ym => $v) {
            $spend = is_array($v) ? (float) ($v['spend'] ?? 0) : (float) $v;
            $label = preg_match('/^\d{4}-\d{2}$/', (string) $ym) ? \Carbon\Carbon::createFromFormat('Y-m-d', $ym . '-01')->format('M Y') : (string) $ym;
            $months[] = [$label, $spend];
        }

        return [
            'm' => [
                'spend' => (float) ($r['spend'] ?? 0),
                'income' => (float) ($t['income'] ?? 0),
                'net' => isset($r['net']) ? (float) $r['net'] : null,
                'entries' => isset($r['rowCount']) ? (int) $r['rowCount'] : null,
            ],
            'g' => [
                'cats' => [
                    ['Materials', (float) ($t['materials'] ?? 0)],
                    ['Labor', (float) ($t['labor'] ?? 0)],
                    ['Services', (float) ($t['services'] ?? 0)],
                    ['Extra expenses', (float) ($t['expense'] ?? 0)],
                    ['Stock buys', (float) ($t['purchase'] ?? 0)],
                ],
                'months' => $months,
            ],
            'f' => [],
        ];
    }

    /**
     * Profit snapshots have always carried only their text, so the figures
     * are read back out of it when there is no dataset: the lines are the
     * profit page's own buildText().
     */
    private function profitFigures(array $r, string $body): array
    {
        if (array_key_exists('profit', $r) || array_key_exists('revenue', $r)) {
            $lots = [];
            foreach ((array) ($r['lots'] ?? []) as $l) {
                $name = trim((string) ($l['name'] ?? ''));
                if ($name !== '') {
                    $lots[$name] = (float) ($l['profit'] ?? 0);
                }
            }
            $c = (array) ($r['costCats'] ?? []);

            return [
                'm' => [
                    'profit' => (float) ($r['profit'] ?? 0),
                    'revenue' => (float) ($r['revenue'] ?? 0),
                    'cost' => (float) ($r['cost'] ?? 0),
                    'margin' => isset($r['margin']) && $r['margin'] !== null ? (float) $r['margin'] : null,
                ],
                'g' => ['cats' => $this->costCats($c), 'lots' => $this->rows($lots)],
                'f' => [],
            ];
        }

        if (trim($body) === '' || ! preg_match('/^NET PROFIT:(.*)$/mi', $body, $np)) {
            return [];
        }
        $line = fn (string $label) => preg_match('/^\s*' . preg_quote($label, '/') . ':(.*)$/mi', $body, $m) ? $this->moneyIn($m[1]) : null;
        $margin = preg_match('/\(([\-−]?[\d.,]+)%\s*margin\)/u', $np[1], $mm) ? (float) str_replace([',', '−'], ['', '-'], $mm[1]) : null;
        $cats = [
            'materials' => $line('Materials'), 'labor' => $line('Labor'), 'services' => $line('Services'),
            'expense' => $line('Extra expenses'), 'purchase' => $line('Stock buys'),
        ];
        $lots = [];
        $tail = preg_split('/^LOT BY LOT\s*$/mi', $body);
        if (count($tail) > 1) {
            foreach (preg_split('/\R/', $tail[1]) as $l) {
                if (preg_match('/^(.+?):\s*earned\b.*?\bprofit\s+(.+?)(?:\s+·|$)/u', trim($l), $lm)) {
                    $lots[$this->withoutCrop($lm[1])] = $this->moneyIn($lm[2]) ?? 0;
                }
            }
        }

        return [
            'm' => [
                'profit' => $this->moneyIn($np[1]),
                'revenue' => $line('Money in'),
                'cost' => $line('Money out'),
                'margin' => $margin,
            ],
            'g' => ['cats' => $this->costCats(array_map(fn ($v) => $v ?? 0, $cats)), 'lots' => $this->rows($lots)],
            'f' => [],
        ];
    }

    private function costCats(array $c): array
    {
        return [
            ['Materials', (float) ($c['materials'] ?? 0)],
            ['Labor', (float) ($c['labor'] ?? 0)],
            ['Services', (float) ($c['services'] ?? 0)],
            ['Extra expenses', (float) ($c['expense'] ?? 0)],
            ['Stock buys', (float) ($c['purchase'] ?? 0)],
        ];
    }

    /** "Lot A (Rice — transplanted (Palay))" -> "Lot A": the crop rides in brackets. */
    private function withoutCrop(string $name): string
    {
        $name = trim($name);
        if (! str_ends_with($name, ')')) {
            return $name;
        }
        $depth = 0;
        for ($i = mb_strlen($name) - 1; $i >= 0; $i--) {
            $c = mb_substr($name, $i, 1);
            if ($c === ')') {
                $depth++;
            } elseif ($c === '(') {
                $depth--;
            }
            if ($depth === 0) {
                return trim(mb_substr($name, 0, $i)) ?: $name;
            }
        }

        return $name;
    }

    /** The first amount in a line of text, sign and all ("₱-1,200.50"). */
    private function moneyIn(string $s): ?float
    {
        if (! preg_match('/([\-−])?[^\d\-−]*?([\-−])?\s*(\d[\d,]*(?:\.\d+)?)/u', $s, $m)) {
            return null;
        }
        $v = (float) str_replace(',', '', $m[3]);

        return ($m[1] ?? '') !== '' || ($m[2] ?? '') !== '' ? -$v : $v;
    }

    private function seasonFigures(array $r): array
    {
        if (! isset($r['headline']) && ! isset($r['scores'])) {
            return [];
        }
        $sc = (array) ($r['scores'] ?? []);
        $rows = [];
        foreach ($sc as $k => $v) {
            if ($k !== 'overall' && is_numeric($v)) {
                $rows[] = [$this->words((string) $k), (float) $v];
            }
        }

        return [
            'm' => ['overall' => is_numeric($sc['overall'] ?? null) ? (float) $sc['overall'] : null],
            'g' => ['scores' => $rows],
            'f' => ['headline' => trim((string) ($r['headline'] ?? '')) ?: null],
        ];
    }

    private function sofarFigures(array $r): array
    {
        if (! isset($r['headline']) && ! isset($r['standing'])) {
            return [];
        }
        $standing = match (strtolower(str_replace(['_', ' '], '-', (string) ($r['standing'] ?? '')))) {
            'on-track', 'ontrack', 'good' => 3,
            'watch' => 2,
            'rescue' => 1,
            default => null,
        };
        $facts = (array) ($r['facts'] ?? []);
        $plan = (array) ($facts['plan'] ?? []);
        $money = (array) ($facts['money'] ?? []);
        $risks = ['High' => 0, 'Medium' => 0, 'Low' => 0];
        foreach ((array) ($r['risks'] ?? []) as $x) {
            $sev = ucfirst(strtolower((string) (is_array($x) ? ($x['severity'] ?? '') : '')));
            if (isset($risks[$sev])) {
                $risks[$sev]++;
            }
        }
        $scores = [];
        foreach ((array) ($r['scores'] ?? []) as $k => $v) {
            if (is_numeric($v)) {
                $scores[] = [$this->words((string) $k), (float) $v];
            }
        }
        $lots = collect((array) ($facts['lots'] ?? []))->map(function ($l) {
            $l = (array) $l;
            $bits = [trim((string) ($l['name'] ?? ''))];
            if (isset($l['day']) && $l['day'] !== null) {
                $bits[] = trim(($l['counter'] ?? '') . ' ' . $l['day']);
            }
            if (! empty($l['stage'])) {
                $bits[] = (string) $l['stage'];
            }

            return implode(' · ', array_filter($bits));
        })->filter()->implode("\n");

        return [
            'm' => [
                'standing' => $standing,
                'score' => is_numeric($r['score'] ?? null) ? (float) $r['score'] : null,
                'onTime' => ((int) ($plan['planned'] ?? 0)) > 0 ? round(((int) ($plan['done'] ?? 0)) / (int) $plan['planned'] * 100, 1) : null,
                'overdue' => isset($plan['overdue']) ? (int) $plan['overdue'] : null,
                'cost' => isset($money['cost']) ? (float) $money['cost'] : null,
                'revenue' => isset($money['revenue']) ? (float) $money['revenue'] : null,
            ],
            'g' => [
                'scores' => $scores,
                'risks' => array_sum($risks) > 0 ? [['High', $risks['High']], ['Medium', $risks['Medium']], ['Low', $risks['Low']]] : [],
            ],
            'f' => [
                'headline' => trim((string) ($r['headline'] ?? '')) ?: null,
                'asOf' => $facts['asOf'] ?? null,
                'lots' => $lots !== '' ? $lots : null,
            ],
        ];
    }

    private function protocolFigures(array $r): array
    {
        if (! isset($r['steps'])) {
            return [];
        }
        $steps = array_values(array_filter((array) $r['steps'], 'is_array'));
        $days = array_values(array_filter(array_map(fn ($s) => $s['day'] ?? null, $steps), fn ($v) => $v !== null));
        $crewDays = 0.0;
        $materials = 0;
        $types = [];
        foreach ($steps as $s) {
            $share = ($s['time'] ?? null) === 'whole day' ? 1 : (($s['time'] ?? null) === 'half day' ? .5 : 0);
            $crewDays += (int) ($s['crew'] ?? 0) * $share;
            $materials += count((array) ($s['materials'] ?? []));
            $type = (string) ($s['type'] ?? '');
            $label = AsScheduleActivity::ACTIVITY_TYPES[$type] ?? ($type !== '' ? ucwords(str_replace('_', ' ', $type)) : 'Not typed');
            $types[$label] = ($types[$label] ?? 0) + 1;
        }

        // "95 sacks sold at ₱1,150.00", one line per harvest row.
        $qty = [];
        $value = 0.0;
        $priced = false;
        foreach ((array) ($r['yields'] ?? []) as $y) {
            if (! preg_match('/^\s*(\d[\d,]*(?:\.\d+)?)\s*(.*?)(?:\s+sold at\s+\D*?(\d[\d,]*(?:\.\d+)?))?\s*$/u', (string) $y, $m)) {
                continue;
            }
            $q = (float) str_replace(',', '', $m[1]);
            $u = trim($m[2]) !== '' ? mb_strtolower(trim($m[2])) : 'units';
            $qty[$u] = ($qty[$u] ?? 0) + $q;
            if (! empty($m[3])) {
                $value += $q * (float) str_replace(',', '', $m[3]);
                $priced = true;
            }
        }
        $unit = count($qty) === 1 ? (string) array_key_first($qty) : null;

        return [
            'm' => [
                'yield' => $unit !== null ? $qty[$unit] : null,
                'yieldValue' => $priced ? round($value, 2) : null,
                'steps' => count($steps),
                'span' => $days ? (max($days) - min($days)) : null,
                'crewDays' => $crewDays,
                'materials' => $materials,
                'skipped' => (int) ($r['skippedPlanned'] ?? 0),
            ],
            'u' => ['yield' => $unit],
            'g' => ['types' => $this->rows($types)],
            'f' => [
                'lot' => $r['lot'] ?? null, 'crop' => $r['crop'] ?? null, 'variety' => $r['variety'] ?? null,
                'size' => $r['size'] ?? null, 'daySystem' => $r['daySystem'] ?? null, 'zeroDate' => $r['zeroDate'] ?? null,
                'span' => $r['span'] ?? null,
                'yields' => ($r['yields'] ?? []) ? implode("\n", (array) $r['yields']) : 'No harvest recorded',
            ],
            'subject' => trim((string) ($r['lot'] ?? '')) ?: null,
            'steps' => array_map(fn ($s) => [
                'day' => isset($s['day']) && $s['day'] !== null ? (int) $s['day'] : null,
                'label' => $s['dayLabel'] ?? null,
                'date' => $s['date'] ?? null,
                'title' => (string) ($s['title'] ?? ''),
                'time' => $s['time'] ?? null,
                'crew' => (int) ($s['crew'] ?? 0),
                'materials' => array_slice(array_map('strval', (array) ($s['materials'] ?? [])), 0, 6),
            ], $steps),
        ];
    }

    /** camelCase keys into words: costControl -> Cost control. */
    private function words(string $k): string
    {
        return ucfirst(strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', str_replace('_', ' ', $k)))));
    }

    /** An associative [label => value] as [[label, value], ...]. */
    private function rows(array $map): array
    {
        $out = [];
        foreach ($map as $label => $v) {
            $out[] = [(string) $label, (float) $v];
        }

        return $out;
    }

    /* ---------------------------- the pairing --------------------------- */

    /** Two sides' figures zipped into what the page draws. */
    private function pair(string $kind, array $fa, array $fb): array
    {
        $spec = self::SPEC[$kind] ?? null;
        $out = ['metrics' => [], 'groups' => [], 'facts' => [], 'timeline' => []];
        if (! $spec || ! $fa || ! $fb) {
            return $out;
        }

        foreach ($spec['metrics'] as $key => [$label, $unit, $better, $icon, $hint, $hideZero]) {
            $a = $fa['m'][$key] ?? null;
            $b = $fb['m'][$key] ?? null;
            if ($a === null && $b === null) {
                continue;
            }
            if ($hideZero && (float) $a == 0 && (float) $b == 0) {
                continue;
            }
            $qtyUnit = null;
            if ($unit === 'qty') {
                $ua = $fa['u'][$key] ?? null;
                $ub = $fb['u'][$key] ?? null;
                if ($ua && $ub && $ua !== $ub) {
                    continue;   // sacks against kilos is not a comparison
                }
                $qtyUnit = $ua ?: $ub;
            }
            $out['metrics'][] = [
                'key' => $key, 'label' => $label, 'unit' => $unit, 'qtyUnit' => $qtyUnit,
                'better' => $better, 'icon' => $icon, 'hint' => $hint,
                'a' => $a === null ? null : (float) $a, 'b' => $b === null ? null : (float) $b,
            ];
        }

        foreach ($spec['groups'] as $key => [$label, $unit, $hint, $open, $both]) {
            $ra = (array) ($fa['g'][$key] ?? []);
            $rb = (array) ($fb['g'][$key] ?? []);
            if ((! $ra && ! $rb) || ($both && (! $ra || ! $rb))) {
                continue;
            }
            $rows = $key === 'months' ? $this->zipByIndex($ra, $rb) : $this->zipByLabel($ra, $rb, $open ? 8 : null);
            $rows = array_values(array_filter($rows, fn ($x) => (float) $x['a'] != 0 || (float) $x['b'] != 0));
            if (! $rows) {
                continue;
            }
            $out['groups'][] = ['key' => $key, 'label' => $label, 'unit' => $unit, 'hint' => $hint, 'rows' => $rows];
        }

        foreach ($spec['facts'] as $key => $label) {
            $a = $fa['f'][$key] ?? null;
            $b = $fb['f'][$key] ?? null;
            if (($a === null || $a === '') && ($b === null || $b === '')) {
                continue;
            }
            $out['facts'][] = [
                'key' => $key, 'label' => $label,
                'a' => $a === null ? null : (string) $a, 'b' => $b === null ? null : (string) $b,
                'same' => mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b)),
            ];
        }

        if (isset($fa['steps'], $fb['steps'])) {
            $out['timeline'] = $this->zipSteps($fa['steps'], $fb['steps']);
        }

        return $out;
    }

    /**
     * Rows matched by their label, A's order first. An open-ended list
     * (workers, lots, kinds of work) keeps its biggest rows and folds the
     * rest into one "Everything else".
     */
    private function zipByLabel(array $ra, array $rb, ?int $cap): array
    {
        $rows = [];
        foreach (['a' => $ra, 'b' => $rb] as $side => $list) {
            foreach ($list as $x) {
                $label = (string) ($x[0] ?? '');
                $key = mb_strtolower($label);
                $rows[$key] ??= ['label' => $label, 'a' => 0.0, 'b' => 0.0];
                $rows[$key][$side] += (float) ($x[1] ?? 0);
            }
        }
        $rows = array_values($rows);
        if ($cap !== null && count($rows) > $cap) {
            usort($rows, fn ($x, $y) => max(abs($y['a']), abs($y['b'])) <=> max(abs($x['a']), abs($x['b'])));
            $keep = array_slice($rows, 0, $cap - 1);
            $rest = array_slice($rows, $cap - 1);
            $keep[] = [
                'label' => 'Everything else (' . count($rest) . ')',
                'a' => array_sum(array_column($rest, 'a')),
                'b' => array_sum(array_column($rest, 'b')),
                'rest' => true,
            ];
            $rows = $keep;
        }

        return $rows;
    }

    /** Months line up by their place in each season, not by the calendar. */
    private function zipByIndex(array $ra, array $rb): array
    {
        $rows = [];
        $n = max(count($ra), count($rb));
        for ($i = 0; $i < $n; $i++) {
            $rows[] = [
                'label' => 'Month ' . ($i + 1),
                'a' => (float) ($ra[$i][1] ?? 0), 'b' => (float) ($rb[$i][1] ?? 0),
                'aNote' => $ra[$i][0] ?? null, 'bNote' => $rb[$i][0] ?? null,
            ];
        }

        return $rows;
    }

    /** Two protocols on one count: day by day, what each side did. */
    private function zipSteps(array $sa, array $sb): array
    {
        $days = [];
        foreach (['a' => $sa, 'b' => $sb] as $side => $steps) {
            foreach ($steps as $s) {
                $k = $s['day'] === null ? 'x' : (string) $s['day'];
                $days[$k] ??= ['day' => $s['day'], 'a' => [], 'b' => [], 'labels' => []];
                $days[$k][$side][] = $s;
                if (! empty($s['label'])) {
                    $days[$k]['labels'][$side] = $s['label'];
                }
            }
        }
        uksort($days, function ($x, $y) {
            if ($x === 'x') return 1;
            if ($y === 'x') return -1;

            return (int) $x <=> (int) $y;
        });

        return array_slice(array_values($days), 0, 150);
    }

    /* ----------------------------- the words ---------------------------- */

    /** A figure said in words, for the saved text and for Anee. */
    private function say(?float $v, string $unit, ?string $qtyUnit = null): string
    {
        if ($v === null) {
            return 'not recorded';
        }

        return match ($unit) {
            'money' => ($v < 0 ? '-' : '') . Region::symbol() . number_format(abs($v), abs($v) >= 1000 || floor($v) == $v ? 0 : 2),
            'pct' => rtrim(rtrim(number_format($v, 1), '0'), '.') . '%',
            'score' => (int) round($v) . '/100',
            'days' => (int) $v . ' days',
            'qty' => rtrim(rtrim(number_format($v, 2), '0'), '.') . ' ' . $qtyUnit,
            'word' => [3 => 'on track', 2 => 'watch', 1 => 'rescue'][(int) $v] ?? '—',
            'num1' => rtrim(rtrim(number_format($v, 1), '0'), '.'),
            default => number_format($v),
        };
    }

    /**
     * The comparison said in text: what Copy/Attach reads, and what the
     * Ask-Anee chip carries into a chat.
     */
    private function bodyText(array $rep, string $title): string
    {
        $a = (array) ($rep['a'] ?? []);
        $b = (array) ($rep['b'] ?? []);
        $who = fn (array $s) => trim(($s['title'] ?? '') . (! empty($s['season']) ? ' — season "' . $s['season'] . '"' : '') . (! empty($s['when']) ? ', saved ' . $s['when'] : ''));
        $L = ['COMPARISON — ' . $title, str_repeat('=', 50), 'A: ' . $who($a), 'B: ' . $who($b)];
        if (! empty($rep['metrics'])) {
            $L[] = '';
            $L[] = 'AT A GLANCE (the app\'s own figures)';
            foreach ($rep['metrics'] as $m) {
                $L[] = ' - ' . $m['label'] . ': A ' . $this->say($m['a'], $m['unit'], $m['qtyUnit'] ?? null)
                    . ' · B ' . $this->say($m['b'], $m['unit'], $m['qtyUnit'] ?? null)
                    . ($m['better'] ? ' (' . $m['better'] . ' is better)' : '');
            }
        }
        foreach ((array) ($rep['groups'] ?? []) as $g) {
            $L[] = '';
            $L[] = strtoupper($g['label']);
            foreach ($g['rows'] as $row) {
                $L[] = ' - ' . $row['label'] . ': A ' . $this->say($row['a'], $g['unit']) . ' · B ' . $this->say($row['b'], $g['unit']);
            }
        }
        foreach ((array) ($rep['facts'] ?? []) as $f) {
            if (! $f['same']) {
                $L[] = $f['label'] . ': A ' . str_replace("\n", '; ', (string) $f['a']) . ' · B ' . str_replace("\n", '; ', (string) $f['b']);
            }
        }
        $L[] = '';
        $L[] = '### REPORT A ###';
        $L[] = mb_substr((string) ($a['body'] ?? ''), 0, 22000);
        $L[] = '';
        $L[] = '### REPORT B ###';
        $L[] = mb_substr((string) ($b['body'] ?? ''), 0, 22000);

        $an = $rep['analysis'] ?? null;
        if (is_array($an)) {
            $L[] = '';
            $L[] = '### ANEE\'S READ OF THE DIFFERENCE ###';
            $L[] = (string) ($an['headline'] ?? '');
            $L[] = (string) ($an['verdict'] ?? '');
            if (! empty($an['overall']['pick'])) {
                $L[] = 'Overall: ' . ($an['overall']['pick'] === 'even' ? 'even' : $an['overall']['pick'] . ' comes out ahead') . ' — ' . ($an['overall']['why'] ?? '');
            }
            foreach ([['differences', 'What changed'], ['betterInA', 'Strengths of A'], ['betterInB', 'Strengths of B'], ['advice', 'Carry forward']] as [$k, $h]) {
                if (! empty($an[$k])) {
                    $L[] = $h . ': ' . implode(' | ', (array) $an[$k]);
                }
            }
        }

        return implode("\n", array_map('strval', $L));
    }

    /** What Anee reads: the app's figures first, then both reports whole. */
    private function prompt(array $rep, AsFarmReport $a, AsFarmReport $b): string
    {
        $sa = (array) $rep['a'];
        $sb = (array) $rep['b'];
        $name = fn (array $s) => '"' . ($s['season'] ?? 'a season') . '"' . (! empty($s['subject']) ? ' (' . $s['subject'] . ')' : '') . ', saved ' . ($s['when'] ?? '');
        $figures = [];
        foreach ((array) ($rep['metrics'] ?? []) as $m) {
            $figures[] = '- ' . $m['label'] . ': A ' . $this->say($m['a'], $m['unit'], $m['qtyUnit'] ?? null)
                . ' | B ' . $this->say($m['b'], $m['unit'], $m['qtyUnit'] ?? null)
                . ($m['better'] ? ' (' . $m['better'] . ' is better)' : '');
        }
        foreach ((array) ($rep['groups'] ?? []) as $g) {
            $figures[] = '- ' . $g['label'] . ': ' . implode('; ', array_map(fn ($r) => $r['label'] . ' A ' . $this->say($r['a'], $g['unit']) . ' / B ' . $this->say($r['b'], $g['unit']), $g['rows']));
        }

        return 'You are an agricultural analyst for a smallholder farm in ' . Region::name() . '. ' . Region::promptBlock()
            . ' Below are two of the farm\'s own saved ' . ($rep['kindLabel'] ?? 'reports') . 's. '
            . 'Report A is from the season ' . $name($sa) . '. Report B is from the season ' . $name($sb) . '. '
            . (! empty($rep['sameSeason']) ? 'Both come from the same season, saved at different times or for different parts of it. ' : 'They come from two different seasons, so this is season against season. ')
            . 'Compare them honestly and usefully, in the same warm, plain voice as a debrief between friends. '
            . 'Name the seasons when it reads better than "A" and "B". Use the figures below as the truth — they are the app\'s own arithmetic; do not re-add them.'
            . ($figures ? "\n\nTHE FIGURES, SIDE BY SIDE:\n" . implode("\n", $figures) : '')
            . "\n\n### REPORT A: " . $a->title . " ###\n" . mb_substr((string) $a->body, 0, 9000)
            . "\n\n### REPORT B: " . $b->title . " ###\n" . mb_substr((string) $b->body, 0, 9000)
            . "\n\nReturn ONLY a single JSON object, no fences, exactly this shape:\n"
            . '{"headline": string (one sentence on the biggest difference), '
            . '"verdict": string (3-4 plain sentences), '
            . '"overall": {"pick": "A" | "B" | "even", "why": string (one sentence — which came out ahead on the whole, and on what)}, '
            . '"differences": [3-6 strings — what changed between them, concrete, with the figures], '
            . '"betterInA": [1-4 strings — the strengths of A], "betterInB": [1-4 strings — the strengths of B], '
            . '"advice": [2-4 strings — what to carry forward into the next season]}';
    }

    private function parseAnalysis(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = preg_replace('/\s*```$/', '', $t);
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $obj = json_decode(substr($t, $start, $end - $start + 1), true);
        if (! is_array($obj) || ! isset($obj['headline'])) {
            return null;
        }
        // Persona shortcodes out of every string, wherever they hide.
        array_walk_recursive($obj, function (&$v) {
            if (is_string($v)) {
                $v = trim(preg_replace('/\s{2,}/', ' ', preg_replace('/:[a-z0-9_-]+:/i', '', $v)));
            }
        });
        foreach (['differences', 'betterInA', 'betterInB', 'advice'] as $k) {
            $obj[$k] = array_values(array_filter(array_map(
                fn ($x) => is_string($x) ? $x : (is_array($x) ? implode(' — ', array_filter(array_map('strval', array_filter($x, 'is_scalar')))) : (string) $x),
                (array) ($obj[$k] ?? [])
            ), fn ($x) => trim($x) !== ''));
        }
        if (isset($obj['overall']) && is_array($obj['overall'])) {
            $pick = strtoupper(trim((string) ($obj['overall']['pick'] ?? '')));
            $obj['overall']['pick'] = in_array($pick, ['A', 'B'], true) ? $pick : 'even';
            $obj['overall']['why'] = (string) ($obj['overall']['why'] ?? '');
        } else {
            unset($obj['overall']);
        }

        return $obj;
    }
}
