<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every peso the platform was paid, as one list (2026-09-30).
 *
 * Two kinds of thing are sold -- plans and packs of Anee's credits -- and
 * each lands as its own record, however it was paid:
 *
 *   plans    anisystem_subscriptions, priced, active or since expired (the
 *            same rule the admin dashboard's "Sales" has always used: a
 *            pending or cancelled row -- a revoked order's plan is cancelled
 *            -- is not money);
 *   credits  anisystem_ai_credit_purchases, priced, not revoked.
 *
 * How it was paid comes from the order behind it (as_orders, by order
 * number): GCash, bank transfer or PayPal. Older rows came through the
 * shop's checkout (ecomOrderId) or were recorded by hand.
 */
class SalesLedger
{
    public const METHOD_LABELS = [
        'gcash' => 'GCash',
        'bank' => 'Bank transfer',
        'paypal' => 'PayPal',
        'checkout' => 'Online checkout',
        'manual' => 'Recorded by hand',
    ];

    /**
     * The sales between two moments (either may be null), newest first.
     *
     * @return Collection<int, array{at: Carbon, userId: int, item: string, kind: string, amount: float, currency: string, method: string, orderNumber: ?string}>
     */
    public static function between(?Carbon $from, ?Carbon $to): Collection
    {
        $window = function ($q, string $col) use ($from, $to) {
            if ($from) {
                $q->where($col, '>=', $from);
            }
            if ($to) {
                $q->where($col, '<=', $to);
            }
        };

        $plans = DB::table('anisystem_subscriptions')
            ->where('deleteStatus', 1)->whereIn('status', ['active', 'expired'])->where('price', '>', 0)
            ->tap(fn ($q) => $window($q, 'created_at'))
            ->get(['userId', 'planName', 'price', 'ecomOrderId', 'orderNumber', 'created_at']);

        $packs = DB::table('anisystem_ai_credit_purchases')
            ->where('deleteStatus', 1)->where('price', '>', 0)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['revoked', 'cancelled', 'refunded', 'failed', 'pending']))
            ->tap(fn ($q) => $window($q, 'created_at'))
            ->get(['userId', 'packName', 'credits', 'price', 'ecomOrderId', 'orderNumber', 'created_at']);

        $numbers = $plans->pluck('orderNumber')->merge($packs->pluck('orderNumber'))->filter()->unique()->values()->all();
        $orders = $numbers ? DB::table('as_orders')->whereIn('orderNumber', $numbers)->get(['orderNumber', 'method', 'currency'])->keyBy('orderNumber') : collect();

        $how = function ($row) use ($orders) {
            $o = $row->orderNumber ? $orders->get($row->orderNumber) : null;

            return [
                'method' => $o ? (string) $o->method : ($row->ecomOrderId ? 'checkout' : 'manual'),
                'currency' => $o ? (string) ($o->currency ?: 'PHP') : 'PHP',
            ];
        };

        return $plans->map(fn ($r) => [
            'at' => Carbon::parse($r->created_at),
            'userId' => (int) $r->userId,
            'item' => trim((string) $r->planName) ?: 'Plan',
            'kind' => 'plan',
            'amount' => round((float) $r->price, 2),
            'orderNumber' => $r->orderNumber,
        ] + $how($r))->concat($packs->map(fn ($r) => [
            'at' => Carbon::parse($r->created_at),
            'userId' => (int) $r->userId,
            'item' => (trim((string) $r->packName) ?: 'Credit pack') . ' · ' . number_format((int) $r->credits) . ' credits',
            'kind' => 'credits',
            'amount' => round((float) $r->price, 2),
            'orderNumber' => $r->orderNumber,
        ] + $how($r)))->sortByDesc(fn ($s) => $s['at']->timestamp)->values();
    }

    public static function methodLabel(string $key): string
    {
        return self::METHOD_LABELS[$key] ?? ucfirst($key);
    }
}
