<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsSalesAnalysis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
}
