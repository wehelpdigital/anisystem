<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsSalesAnalysis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Sales Analysis — what a peso of advertising actually bought.
 *
 * A batch is a named spend over a date range. Everything else is read at
 * request time from the rows the platform already keeps: who registered in
 * the window (workers excluded — an invited hand is not an acquisition),
 * which of them verified a paid subscription, and what those subscriptions
 * are worth. Cost per registration, cost per client, and revenue against
 * spend fall out of those three counts.
 */
class AdminSalesController extends Controller
{
    public function page()
    {
        return view('admin.sales');
    }

    public function list()
    {
        $rows = AsSalesAnalysis::where('deleteStatus', 1)
            ->orderByDesc('id')->limit(100)->get();

        return response()->json(['success' => true, 'data' => [
            'rows' => $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'from' => $r->dateFrom->format('Y-m-d'),
                'to' => $r->dateTo->format('Y-m-d'),
                'fromSays' => $r->dateFrom->format('M j, Y'),
                'toSays' => $r->dateTo->format('M j, Y'),
                'adCost' => (float) $r->adCost,
                'adCadence' => $r->adCadence,
            ])->values(),
        ]]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date|after_or_equal:dateFrom',
            'adCost' => 'required|numeric|min:0|max:100000000',
            'adCadence' => 'required|in:daily,weekly,monthly',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $row = AsSalesAnalysis::create([
            'name' => trim((string) $request->input('name')),
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'adCost' => (float) $request->input('adCost'),
            'adCadence' => $request->input('adCadence'),
            'createdBy' => (int) Auth::id(),
            'deleteStatus' => 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Analysis created.', 'data' => ['id' => (int) $row->id]]);
    }

    public function destroy(Request $request, int $id)
    {
        AsSalesAnalysis::where('id', $id)->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Analysis removed.']);
    }

    /** The batch, computed: the funnel and every unit economic beside it. */
    public function one(int $id)
    {
        $row = AsSalesAnalysis::where('deleteStatus', 1)->find($id);
        if (! $row) {
            return response()->json(['success' => false, 'message' => 'That analysis is gone.'], 404);
        }

        $from = $row->dateFrom->copy()->startOfDay();
        $to = $row->dateTo->copy()->endOfDay();
        $days = $from->diffInDays($to->copy()->startOfDay()) + 1;

        // The spend, said in the cadence it was bought in.
        $units = match ($row->adCadence) {
            'weekly' => (int) ceil($days / 7),
            'monthly' => max(1, (int) ceil($days / 30)),
            default => $days,
        };
        $spend = round($row->adCost * $units, 2);

        // Who arrived: real registrations, not invited hands. An account
        // that exists because a boss sent a worker invite is not something
        // the ads bought.
        $workerIds = DB::table('as_worker_grants')->whereNotNull('workerUserId')->pluck('workerUserId');
        $regs = DB::table('anisystem_users')
            ->where('deleteStatus', 1)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('id', $workerIds->all() ?: [0])
            ->get(['id', 'firstName', 'lastName', 'created_at']);
        $regIds = $regs->pluck('id')->all();

        // Who of them paid: a verified subscription with a real price.
        $subs = $regIds
            ? DB::table('anisystem_subscriptions')
                ->whereIn('userId', $regIds)
                ->where('deleteStatus', 1)
                ->whereNotNull('verifiedAt')
                ->where('price', '>', 0)
                ->get(['userId', 'planName', 'price'])
            : collect();
        $convertedIds = $subs->pluck('userId')->unique()->values();
        $revenue = round((float) $subs->sum('price'), 2);

        // The mix: which plans the batch's clients bought.
        $mix = $subs->groupBy('planName')->map(fn ($g, $plan) => [
            'plan' => $plan ?: 'Unnamed plan',
            'count' => $g->count(),
            'revenue' => round((float) $g->sum('price'), 2),
        ])->values();

        $nRegs = $regs->count();
        $nConv = $convertedIds->count();

        return response()->json(['success' => true, 'data' => [
            'id' => (int) $row->id,
            'name' => $row->name,
            'from' => $row->dateFrom->format('M j, Y'),
            'to' => $row->dateTo->format('M j, Y'),
            'days' => $days,
            'adCost' => (float) $row->adCost,
            'adCadence' => $row->adCadence,
            'units' => $units,
            'spend' => $spend,
            'registrations' => $nRegs,
            'converted' => $nConv,
            'conversionRate' => $nRegs > 0 ? round($nConv / $nRegs * 100, 1) : 0,
            'revenue' => $revenue,
            'costPerRegistration' => $nRegs > 0 ? round($spend / $nRegs, 2) : null,
            'costPerClient' => $nConv > 0 ? round($spend / $nConv, 2) : null,
            'avgRevenuePerClient' => $nConv > 0 ? round($revenue / $nConv, 2) : null,
            'roas' => $spend > 0 ? round($revenue / $spend, 2) : null,
            'net' => round($revenue - $spend, 2),
            'planMix' => $mix,
        ]]);
    }

    /**
     * The Sales Dashboard: money in, at a glance (2026-09-30).
     *
     * Read from App\Support\SalesLedger, so it agrees with the admin
     * dashboard's "Sales" and counts credit packs too. ?range= is 7d, 30d
     * (the default), 90d, 12m or all; days are bucketed by day up to ninety
     * of them, by month beyond. Every figure is compared with the window of
     * the same length just before it.
     */
    public function dashboard(Request $request)
    {
        $tz = 'Asia/Manila';
        $now = Carbon::now($tz);
        $range = (string) $request->query('range', '30d');
        [$from, $label, $bucket] = match ($range) {
            '7d' => [$now->copy()->subDays(6)->startOfDay(), 'Last 7 days', 'day'],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), 'Last 90 days', 'day'],
            '12m' => [$now->copy()->subMonths(11)->startOfMonth(), 'Last 12 months', 'month'],
            'all' => [null, 'All time', 'month'],
            default => [$now->copy()->subDays(29)->startOfDay(), 'Last 30 days', 'day'],
        };
        if (! in_array($range, ['7d', '30d', '90d', '12m', 'all'], true)) {
            $range = '30d';
        }
        if ($range === 'all') {
            $first = \App\Support\SalesLedger::between(null, null)->last();
            $from = ($first ? $first['at']->copy()->timezone($tz) : $now->copy())->startOfMonth();
        }
        $to = $now->copy()->endOfDay();
        $span = $from->diffInSeconds($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($span);

        $sales = \App\Support\SalesLedger::between($from->copy()->utc(), $to->copy()->utc())
            ->map(fn ($s) => array_merge($s, ['at' => $s['at']->copy()->timezone($tz)]));
        $prev = $range === 'all' ? collect() : \App\Support\SalesLedger::between($prevFrom->copy()->utc(), $prevTo->copy()->utc());

        // Money is counted in pesos; a sale in another currency is listed and
        // counted apart rather than added to them.
        $php = $sales->where('currency', 'PHP');
        $other = $sales->where('currency', '!=', 'PHP');
        $revenue = round((float) $php->sum('amount'), 2);
        $prevRevenue = round((float) $prev->where('currency', 'PHP')->sum('amount'), 2);

        // The series, every bucket present so a quiet day reads as zero.
        $series = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $bucket === 'day' ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $series[$key] = [
                'key' => $key,
                'label' => $bucket === 'day' ? $cursor->format('M j') : $cursor->format('M Y'),
                'amount' => 0.0,
                'count' => 0,
            ];
            $bucket === 'day' ? $cursor->addDay() : $cursor->addMonthNoOverflow()->startOfMonth();
        }
        foreach ($php as $s) {
            $key = $bucket === 'day' ? $s['at']->format('Y-m-d') : $s['at']->format('Y-m');
            if (isset($series[$key])) {
                $series[$key]['amount'] = round($series[$key]['amount'] + $s['amount'], 2);
                $series[$key]['count']++;
            }
        }

        $group = fn (Collection $rows, callable $by) => $rows->groupBy($by)
            ->map(fn ($g, $k) => ['label' => (string) $k, 'amount' => round((float) $g->sum('amount'), 2), 'count' => $g->count()])
            ->sortByDesc('amount')->values();
        $byProduct = $group($php, fn ($s) => $s['kind'] === 'credits' ? 'AI credits' : $s['item']);
        $byMethod = $group($php, fn ($s) => \App\Support\SalesLedger::methodLabel($s['method']));

        // What is still coming, and what was taken back in the window.
        $inReview = DB::table('as_orders')->where('status', 'review');
        $revoked = DB::table('as_orders')->where('status', 'revoked')->where('revokedAt', '>=', $from->copy()->utc());
        $recent = $sales->take(12);
        $users = DB::table('anisystem_users')->whereIn('id', $recent->pluck('userId')->unique()->all() ?: [0])
            ->get(['id', 'firstName', 'lastName', 'email'])->keyBy('id');
        $orderIds = DB::table('as_orders')->whereIn('orderNumber', $recent->pluck('orderNumber')->filter()->all() ?: ['-'])->pluck('id', 'orderNumber');

        $count = $php->count();
        $prevCount = $prev->where('currency', 'PHP')->count();

        return response()->json(['success' => true, 'data' => [
            'range' => $range,
            'label' => $label,
            'from' => $from->format('M j, Y'),
            'to' => $now->format('M j, Y'),
            'bucket' => $bucket,
            'compare' => $range === 'all' ? null : strtolower($label),
            'kpis' => [
                'revenue' => $revenue,
                'revenuePrev' => $prevRevenue,
                'count' => $count,
                'countPrev' => $prevCount,
                'average' => $count ? round($revenue / $count, 2) : 0,
                'averagePrev' => $prevCount ? round($prevRevenue / $prevCount, 2) : 0,
                'buyers' => $php->pluck('userId')->unique()->count(),
                'today' => round((float) $php->filter(fn ($s) => $s['at']->isSameDay($now))->sum('amount'), 2),
                'plans' => round((float) $php->where('kind', 'plan')->sum('amount'), 2),
                'credits' => round((float) $php->where('kind', 'credits')->sum('amount'), 2),
                'inReview' => ['count' => (int) (clone $inReview)->count(), 'amount' => round((float) (clone $inReview)->sum('total'), 2)],
                'revoked' => ['count' => (int) (clone $revoked)->count(), 'amount' => round((float) (clone $revoked)->sum('total'), 2)],
                'otherCurrency' => $other->groupBy('currency')->map(fn ($g) => round((float) $g->sum('amount'), 2)),
            ],
            'series' => array_values($series),
            'byProduct' => $byProduct,
            'byMethod' => $byMethod,
            'recent' => $recent->map(function ($s) use ($users, $orderIds) {
                $u = $users->get($s['userId']);

                return [
                    'when' => $s['at']->format('M j, Y g:i A'),
                    'buyer' => $u ? (trim($u->firstName . ' ' . $u->lastName) ?: $u->email) : 'Deleted account',
                    'email' => $u?->email,
                    'item' => $s['item'],
                    'method' => \App\Support\SalesLedger::methodLabel($s['method']),
                    'amount' => $s['amount'],
                    'currency' => $s['currency'],
                    'href' => isset($orderIds[$s['orderNumber'] ?? '']) ? route('admin.orders', ['open' => $orderIds[$s['orderNumber']]]) : null,
                ];
            })->values(),
            'ordersUrl' => route('admin.orders'),
        ]]);
    }
}
