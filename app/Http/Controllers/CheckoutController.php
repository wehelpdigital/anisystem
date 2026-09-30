<?php

namespace App\Http\Controllers;

use App\Models\AsOrder;
use App\Services\OrderService;
use App\Support\ManualPay;
use App\Support\Region;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * The buyer's side of an order paid by hand (2026-09-30): one page walks
 * through choosing how to pay, paying, sending the proof and the outcome.
 * It opens on an item (?item=solo:month) or on an order (?order=AN-...),
 * which resumes wherever that order stands.
 */
class CheckoutController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function page(Request $request)
    {
        $user = $request->user();
        $this->orders->expireStale($user);

        $order = null;
        if ($request->filled('order')) {
            $order = AsOrder::where('orderNumber', (string) $request->query('order'))->where('userId', $user->id)->first();
            abort_unless($order, 404);
        }
        $itemKey = $order?->itemKey ?? (string) $request->query('item', '');
        $item = ManualPay::item($itemKey);
        if (! $order && ! $item) {
            return redirect()->route('account.subscription')->with('error', 'That plan or pack is not on sale.');
        }
        if (! $order && $item['kind'] === 'credits' && ! $user->canUseAi()) {
            return redirect()->route('ai.credits', ['tab' => 'buy']);
        }
        // An item page for something already waiting for a decision shows that order.
        if (! $order && $item && ($waiting = $this->orders->inReview($user, $item['kind']))) {
            $order = $waiting;
            $item = ManualPay::item($order->itemKey) ?? $item;
        }

        return view('checkout.index', [
            'user' => $user,
            'item' => $item,
            'order' => $order,
            'orderJson' => $order ? $this->payload($order) : null,
            'methods' => ManualPay::methods(),
            'pay' => ManualPay::settings(),
            'qrUrl' => ManualPay::qrUrl(),
            'paypal' => Region::paypal(),
            'isPh' => Region::ph(),
            'current' => $user->planTier(),
        ]);
    }

    /** The buyer chose how to pay: open the order. */
    public function start(Request $request)
    {
        $data = $request->validate([
            'item' => ['required', 'string', 'max:48'],
            'method' => ['required', 'string', 'in:gcash,bank,paypal'],
        ]);
        $item = ManualPay::item($data['item']);
        $methods = ManualPay::methods();
        if (! $item) {
            return response()->json(['success' => false, 'message' => 'That plan or pack is not on sale.'], 422);
        }
        if ($item['kind'] === 'credits' && ! $request->user()->canUseAi()) {
            return response()->json(['success' => false, 'message' => 'Credits are spent with Anee, who comes with Libre + Anee and every plan above it.'], 422);
        }
        if (! isset($methods[$data['method']]) || ! $methods[$data['method']]['ready']) {
            return response()->json(['success' => false, 'message' => 'That way of paying is not available yet.'], 422);
        }
        try {
            $order = $this->orders->start($request->user(), $item, $data['method']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json(['success' => true, 'data' => $this->payload($order)]);
    }

    /** The proof. A GCash plan is read, and maybe approved, before this answers. */
    public function proof(Request $request, int $id)
    {
        $order = AsOrder::where('id', $id)->where('userId', $request->user()->id)->firstOrFail();
        $data = $request->validate([
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:8192', 'required_without:refNumber'],
            'refNumber' => ['nullable', 'string', 'max:64', 'required_without:file'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'file.required_without' => 'Add a screenshot or PDF of the receipt, or type the reference number.',
            'refNumber.required_without' => 'Add a screenshot or PDF of the receipt, or type the reference number.',
            'file.mimes' => 'Send a picture (JPG, PNG, WebP) or a PDF.',
            'file.max' => 'That file is over 8 MB. A screenshot is enough.',
        ]);
        if ($request->hasFile('file') && $request->file('file')->getMimeType() === 'application/pdf' && $request->file('file')->getSize() > 5 * 1048576) {
            return response()->json(['success' => false, 'message' => 'That PDF is over 5 MB. A screenshot of the receipt is enough.'], 422);
        }

        try {
            $order = $this->orders->submit($order, $request->file('file'), $data['refNumber'] ?? null, $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        // A GCash plan with a receipt: the buyer waits while Anee reads it
        // (a pass approves it now). Anything else: answer at once, and let
        // any reading happen after the page has its answer.
        $settings = ManualPay::settings();
        if ($order->method === 'gcash' && $order->isPlan() && $order->proofFileId && $settings['aiAutoApprove']) {
            @set_time_limit(150);
            $order = $this->orders->afterProof($order);
        } else {
            // After the response has left: nothing here is serialized or
            // queued, so it needs no worker (the host runs none).
            $orderId = $order->id;
            app()->terminating(function () use ($orderId) {
                if ($o = AsOrder::find($orderId)) {
                    app(OrderService::class)->afterProof($o);
                }
            });
        }

        return response()->json(['success' => true, 'data' => $this->payload($order->fresh())]);
    }

    public function cancel(Request $request, int $id)
    {
        $order = AsOrder::where('id', $id)->where('userId', $request->user()->id)->firstOrFail();
        $this->orders->cancel($order);

        return response()->json(['success' => true]);
    }

    /** Where an order stands, for the page to poll while it waits. */
    public function status(Request $request, int $id)
    {
        $order = AsOrder::where('id', $id)->where('userId', $request->user()->id)->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->payload($order)]);
    }

    private function payload(AsOrder $o): array
    {
        $effect = (array) $o->effect;
        // Anee's reading is never shown to the buyer: telling a faker which
        // check failed would teach them what to fix.

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
            'price' => (float) $o->price,
            'fee' => (float) $o->fee,
            'total' => (float) $o->total,
            'totalText' => Region::money((float) $o->total),
            'credits' => (int) $o->credits,
            'decidedByAi' => $o->decidedBy === 'ai',
            'aiStatus' => $o->aiStatus,
            'startsAt' => ! empty($effect['startsAt']) ? \Carbon\Carbon::parse($effect['startsAt'])->format('M j, Y') : null,
            'expiresAt' => ! empty($effect['expiresAt']) ? \Carbon\Carbon::parse($effect['expiresAt'])->format('M j, Y') : null,
            'queued' => (bool) ($effect['queued'] ?? false),
            'reason' => $o->status === AsOrder::REVOKED ? $o->revokeReason : $o->rejectReason,
            'createdAt' => $o->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'submittedAt' => $o->submittedAt?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'urls' => [
                'proof' => route('checkout.proof', ['id' => $o->id]),
                'cancel' => route('checkout.cancel', ['id' => $o->id]),
                'status' => route('checkout.status', ['id' => $o->id]),
                'self' => route('checkout', ['order' => $o->orderNumber]),
            ],
        ];
    }
}
