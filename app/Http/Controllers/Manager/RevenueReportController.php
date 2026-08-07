<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleRevenueReport;
use App\Services\ScheduleFinancials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Post-Harvest Revenue Report — the profitability view of a schedule. Live-adds
 * up the season's costs (materials, services, labour, extra expenses), takes
 * the farmer's harvest figures (yield × price), and shows the net. Each
 * calculation can be frozen as a savable copy for the record.
 *
 * Reads use the un-guarded schedule() resolver so a completed/locked schedule
 * can still be reported on and saved — a report is derived, not a schedule edit.
 */
class RevenueReportController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        $costs = (new ScheduleFinancials())->costs($schedule);

        $saved = AsScheduleRevenueReport::active()
            ->forSchedule($schedule->id)
            ->orderByDesc('id')
            ->get();

        return view('sm.revenue-report', [
            'schedule' => $schedule,
            'costs'    => $costs,
            'saved'    => $saved,
        ]);
    }

    /**
     * Live-recompute the current costs (JSON) so the page can refresh the
     * breakdown without a reload — e.g. after logging a new expense elsewhere.
     */
    public function compute(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $costs = (new ScheduleFinancials())->costs($schedule);

        return $this->jsonOk('Costs computed.', ['data' => $costs]);
    }

    /**
     * Freeze the current calculation as a saved copy. Costs are recomputed
     * server-side (never trust the client's numbers); revenue and net are
     * derived from the farmer's yield/price inputs.
     */
    public function store(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:191',
            'yieldAmount'  => 'nullable|numeric|min:0|max:99999999999',
            'yieldUnit'    => 'nullable|string|max:24',
            'pricePerUnit' => 'nullable|numeric|min:0|max:99999999999',
            'notes'        => 'nullable|string|max:5000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $costs = (new ScheduleFinancials())->costs($schedule);

        $yieldAmount  = $request->filled('yieldAmount') ? round((float) $request->input('yieldAmount'), 2) : null;
        $pricePerUnit = $request->filled('pricePerUnit') ? round((float) $request->input('pricePerUnit'), 2) : null;
        $grossRevenue = round((float) ($yieldAmount ?? 0) * (float) ($pricePerUnit ?? 0), 2);
        $netProfit    = round($grossRevenue - $costs['total'], 2);

        $report = AsScheduleRevenueReport::create([
            'croppingScheduleId' => $schedule->id,
            'versionId'          => $this->activeVersionId($schedule->id),
            'title'              => trim($request->input('title')),
            'yieldAmount'        => $yieldAmount,
            'yieldUnit'          => $request->input('yieldUnit') ? trim($request->input('yieldUnit')) : null,
            'pricePerUnit'       => $pricePerUnit,
            'grossRevenue'       => $grossRevenue,
            'materialsCost'      => $costs['materials'],
            'servicesCost'       => $costs['services'],
            'laborCost'          => $costs['labor'],
            'expensesCost'       => $costs['expenses'],
            'totalCost'          => $costs['total'],
            'netProfit'          => $netProfit,
            'notes'              => $request->input('notes') ? trim($request->input('notes')) : null,
            'deleteStatus'       => 1,
        ]);

        return $this->jsonOk('Report saved.', ['data' => $this->present($report)]);
    }

    public function destroy(Request $request)
    {
        // Schedule via ?scheduleId, report row via ?id (distinct params).
        $schedule = $this->schedule($request->query('scheduleId'));

        $report = AsScheduleRevenueReport::active()
            ->forSchedule($schedule->id)
            ->where('id', $this->queryId($request))
            ->first();
        if (! $report) {
            return $this->jsonFail('Report not found.', 404);
        }

        $report->update(['deleteStatus' => 0]);

        return $this->jsonOk('Report deleted.');
    }

    private function present(AsScheduleRevenueReport $r): array
    {
        return [
            'id'            => $r->id,
            'title'         => $r->title,
            'yieldAmount'   => $r->yieldAmount !== null ? (float) $r->yieldAmount : null,
            'yieldUnit'     => $r->yieldUnit,
            'pricePerUnit'  => $r->pricePerUnit !== null ? (float) $r->pricePerUnit : null,
            'grossRevenue'  => (float) $r->grossRevenue,
            'materialsCost' => (float) $r->materialsCost,
            'servicesCost'  => (float) $r->servicesCost,
            'laborCost'     => (float) $r->laborCost,
            'expensesCost'  => (float) $r->expensesCost,
            'totalCost'     => (float) $r->totalCost,
            'netProfit'     => (float) $r->netProfit,
            'notes'         => $r->notes,
            'savedAt'       => $r->created_at?->format('M j, Y g:i A'),
        ];
    }

    private function activeVersionId(int $scheduleId): ?int
    {
        $id = \App\Models\AsScheduleActivityVersion::active()
            ->where('croppingScheduleId', $scheduleId)
            ->orderByDesc('isActive')
            ->orderByDesc('isOriginal')
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }
}
