<?php

namespace App\Http\Controllers;

use App\Models\AsOrder;
use App\Services\OrderService;
use App\Services\ReceiptCheck;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * The mother app's door to deciding an order (2026-09-30). The mother lists
 * orders and shows their proofs straight from the shared database, but it
 * never applies a purchase itself: approving, rejecting and revoking run
 * here, through OrderService, so there is exactly one implementation of what
 * a decision does. It answers only a request carrying the shared secret the
 * two apps already use for media (config mother.media_token).
 */
class MotherOrdersController extends Controller
{
    public function act(Request $request, int $id, string $action)
    {
        $token = (string) config('mother.media_token');
        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Anee-Token'))) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }
        $o = AsOrder::find($id);
        if (! $o) {
            return response()->json(['success' => false, 'message' => 'No such order.'], 404);
        }
        $by = 'mother:' . mb_substr(trim((string) $request->input('admin', 'an admin')), 0, 60);
        $reason = (string) $request->input('reason', '');
        $orders = app(OrderService::class);

        try {
            match ($action) {
                'approve' => $orders->approve($o, $by),
                'reject' => $orders->reject($o, $by, $reason),
                'revoke' => $orders->revoke($o, $by, $reason),
                'recheck' => $o->proofFileId ? app(ReceiptCheck::class)->run($o) : throw new RuntimeException('There is no picture or PDF to read.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json(['success' => true, 'status' => $o->fresh()->status]);
    }
}
