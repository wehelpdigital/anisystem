<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsOrder;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ReceiptCheck;
use App\Support\ManualPay;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Admin panel > Orders (2026-09-30): every purchase paid by hand, with the
 * proof, Anee's reading and the decisions. Approve and reject a payment
 * waiting for a person; revoke one that was approved (by Anee or by anyone).
 * The mother app's AniSystem > Orders does the same through
 * MotherOrdersController, so there is one way an order is decided.
 */
class AdminOrdersController extends Controller
{
    private const PAGE = 25;

    public function __construct(private OrderService $orders) {}

    public function page()
    {
        $counts = AsOrder::query()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status')->all();

        return view('admin.orders', ['counts' => $counts]);
    }

    public function list(Request $request)
    {
        $this->orders->expireStale();
        $status = (string) $request->query('status', 'review');
        $q = trim((string) $request->query('q', ''));
        $cursor = (int) $request->query('cursor', 0);

        $rows = AsOrder::query()
            ->when($status !== 'all', fn ($w) => $w->where('status', $status))
            ->when($q !== '', function ($w) use ($q) {
                $ids = User::query()->where(fn ($u) => $u->where('email', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$q}%"]))->limit(200)->pluck('id');
                $w->where(fn ($x) => $x->where('orderNumber', 'like', "%{$q}%")->orWhere('refNumber', 'like', "%{$q}%")->orWhereIn('userId', $ids));
            })
            ->when($cursor > 0, fn ($w) => $w->where('id', '<', $cursor))
            ->orderByDesc('id')->limit(self::PAGE + 1)->get();

        $more = $rows->count() > self::PAGE;
        $rows = $rows->take(self::PAGE);
        $users = User::whereIn('id', $rows->pluck('userId')->unique())->get(['id', 'firstName', 'lastName', 'email'])->keyBy('id');

        return response()->json(['success' => true, 'data' => [
            'rows' => $rows->map(fn ($o) => $this->row($o, $users[$o->userId] ?? null))->values(),
            'nextCursor' => $more ? $rows->last()->id : null,
        ]]);
    }

    public function one(int $id)
    {
        $o = AsOrder::findOrFail($id);
        $user = User::find($o->userId);
        $file = OrderService::file($o);

        return response()->json(['success' => true, 'data' => $this->row($o, $user) + [
            'user' => $user ? ['id' => $user->id, 'name' => trim($user->firstName . ' ' . $user->lastName), 'email' => $user->email, 'phone' => $user->phone, 'tier' => $user->planTier()] : null,
            'price' => (float) $o->price,
            'fee' => (float) $o->fee,
            'refNumber' => $o->refNumber,
            'proofKind' => $o->proofKind,
            'file' => $file ? ['mime' => $file->mime, 'size' => (int) $file->size, 'name' => $file->name, 'url' => route('admin.orders.file', ['id' => $o->id])] : null,
            'buyerNote' => $o->buyerNote,
            'ai' => $o->aiReport,
            'expected' => ['total' => (float) $o->total, 'number' => ManualPay::spacedNumber(), 'name' => ManualPay::settings()['gcashName']],
            'effect' => $o->effect,
            'timeline' => array_values(array_filter([
                ['at' => $this->at($o->created_at), 'say' => 'Order opened, paying by ' . $o->methodLabel()],
                $o->submittedAt ? ['at' => $this->at($o->submittedAt), 'say' => 'Proof sent (' . ($o->proofKind === 'ref' ? 'reference number' : $o->proofKind) . ')'] : null,
                $o->aiCheckedAt ? ['at' => $this->at($o->aiCheckedAt), 'say' => 'Anee read the receipt: ' . ($o->aiStatus ?? '')] : null,
                $o->approvedAt ? ['at' => $this->at($o->approvedAt), 'say' => 'Approved by ' . $this->who($o->decidedBy)] : null,
                $o->rejectedAt ? ['at' => $this->at($o->rejectedAt), 'say' => 'Rejected by ' . $this->who($o->decidedBy) . ($o->rejectReason ? ': ' . $o->rejectReason : '')] : null,
                $o->revokedAt ? ['at' => $this->at($o->revokedAt), 'say' => 'Revoked by ' . $this->who($o->revokedBy) . ($o->revokeReason ? ': ' . $o->revokeReason : '')] : null,
            ])),
        ]]);
    }

    /** The proof itself, only ever streamed to an admin. */
    public function file(int $id)
    {
        $o = AsOrder::findOrFail($id);
        $file = OrderService::file($o);
        abort_unless($file, 404);

        return response($file->bytes, 200, [
            'Content-Type' => $file->mime,
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $o->orderNumber . '-proof.' . ($file->mime === 'application/pdf' ? 'pdf' : 'jpg')) . '"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function approve(Request $request, int $id)
    {
        return $this->act(fn ($o) => $this->orders->approve($o, 'admin:' . $request->user()->id), $id, 'Approved. The purchase is applied and the buyer was told.');
    }

    public function reject(Request $request, int $id)
    {
        $reason = (string) $request->input('reason', '');

        return $this->act(fn ($o) => $this->orders->reject($o, 'admin:' . $request->user()->id, $reason), $id, 'Rejected. The buyer was told.');
    }

    public function revoke(Request $request, int $id)
    {
        $reason = (string) $request->input('reason', '');

        return $this->act(fn ($o) => $this->orders->revoke($o, 'admin:' . $request->user()->id, $reason), $id, 'Revoked. What it gave was taken back and the buyer was told.');
    }

    /** Ask Anee to read the receipt again. Advisory: it never decides. */
    public function recheck(int $id)
    {
        $o = AsOrder::findOrFail($id);
        if (! $o->proofFileId) {
            return response()->json(['success' => false, 'message' => 'There is no picture or PDF to read on this order.'], 422);
        }
        @set_time_limit(150);
        $report = app(ReceiptCheck::class)->run($o);

        return response()->json(['success' => true, 'message' => 'Anee read it again: ' . ($report['summary'] ?? $report['verdict'] ?? 'done') . '.']);
    }

    private function act(callable $do, int $id, string $said)
    {
        $o = AsOrder::findOrFail($id);
        try {
            $do($o);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json(['success' => true, 'message' => $said]);
    }

    private function row(AsOrder $o, ?User $user): array
    {
        return [
            'id' => $o->id,
            'number' => $o->orderNumber,
            'status' => $o->status,
            'statusLabel' => $o->statusLabel(),
            'kind' => $o->kind,
            'itemName' => $o->itemName,
            'method' => $o->method,
            'methodLabel' => $o->methodLabel(),
            'currency' => $o->currency,
            'total' => (float) $o->total,
            'aiStatus' => $o->aiStatus,
            'decidedByAi' => $o->decidedBy === 'ai',
            'buyer' => $user ? trim($user->firstName . ' ' . $user->lastName) : 'Deleted account',
            'email' => $user?->email,
            'at' => $this->at($o->submittedAt ?? $o->created_at),
        ];
    }

    private function at($t): ?string
    {
        return $t ? \Carbon\Carbon::parse($t)->timezone('Asia/Manila')->format('M j, Y g:i A') : null;
    }

    public static function who(?string $by): string
    {
        if ($by === 'ai') {
            return 'Anee (automatic)';
        }
        if (str_starts_with((string) $by, 'admin:')) {
            $u = User::find((int) substr($by, 6));

            return $u ? trim($u->firstName . ' ' . $u->lastName) : 'an admin';
        }
        if (str_starts_with((string) $by, 'mother:')) {
            return substr($by, 7) . ' (mother app)';
        }

        return $by ?: 'someone';
    }
}
