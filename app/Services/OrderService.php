<?php

namespace App\Services;

use App\Models\AiCreditPurchase;
use App\Models\AsOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ManualPay;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orders paid by hand, from start to decision (2026-09-30).
 *
 *   start()   the buyer picks what and how to pay: an order waits (awaiting)
 *   submit()  the proof arrives: the order waits for a decision (review);
 *             a GCash receipt is read by Anee, and a GCash PLAN she can
 *             vouch for on every check is approved there and then
 *   approve() the payment is good: the plan starts, or the credits land
 *   reject()  the payment could not be verified
 *   revoke()  an approval is taken back: the plan row is cancelled (and any
 *             plan queued behind it moves up), the credits are taken back
 *
 * WHEN A PLAN STARTS. One rule covers renewal, upgrade and downgrade: a plan
 * starts now, or when every active plan at its level or above has ended.
 * Renewing Solo queues behind the Solo in force; Owner bought over Solo
 * starts at once (Solo's remaining days wait underneath and resume after);
 * Solo bought over Owner waits for Owner to end. User::activeSubscription()
 * ignores a row that has not started yet, so a queued plan never runs early.
 *
 * Every decision locks the order row first, so the AI, the admin panel and
 * the mother app can never apply the same payment twice.
 */
class OrderService
{
    /** An order nobody paid is let go after this long. */
    public const AWAITING_HOURS = 48;

    public function __construct(
        private MailService $mail,
        private AiCreditService $credits,
        private NotificationService $notes,
    ) {}

    // ------------------------------------------------------------------
    // The buyer
    // ------------------------------------------------------------------

    /** The order this buyer has waiting for a decision, of this kind, if any. */
    public function inReview(User $user, string $kind): ?AsOrder
    {
        return AsOrder::where('userId', $user->id)->where('kind', $kind)->where('status', AsOrder::REVIEW)->latest('id')->first();
    }

    /**
     * Open (or reopen) an order: what, and how it will be paid. An unpaid
     * order for the same thing and method is picked up again rather than
     * duplicated; one for anything else of the same kind is let go.
     */
    public function start(User $user, array $item, string $method): AsOrder
    {
        $this->expireStale($user);

        if ($this->inReview($user, $item['kind'])) {
            throw new RuntimeException($item['kind'] === 'plan'
                ? 'Your payment for a plan is still being reviewed. You can buy again once it is decided.'
                : 'Your payment for credits is still being reviewed. You can buy again once it is decided.');
        }

        $fee = ManualPay::fee($method);
        $total = round($item['price'] + $fee, 2);

        return DB::transaction(function () use ($user, $item, $method, $fee, $total) {
            $open = AsOrder::where('userId', $user->id)->where('kind', $item['kind'])->where('status', AsOrder::AWAITING)->lockForUpdate()->get();
            foreach ($open as $o) {
                if ($o->itemKey === $item['key'] && $o->method === $method && (float) $o->total === $total) {
                    return $o;
                }
                $o->update(['status' => AsOrder::CANCELLED]);
            }

            return AsOrder::create([
                'orderNumber' => $this->number(),
                'userId' => $user->id,
                'kind' => $item['kind'],
                'itemKey' => $item['key'],
                'itemName' => $item['name'],
                'tier' => $item['tier'],
                'period' => $item['period'],
                'days' => $item['days'],
                'months' => $item['months'],
                'packId' => $item['packId'],
                'credits' => $item['credits'] ?: null,
                'currency' => $item['currency'],
                'price' => $item['price'],
                'fee' => $fee,
                'total' => $total,
                'method' => $method,
                'status' => AsOrder::AWAITING,
            ]);
        });
    }

    /** The buyer walks away from an order they never paid. */
    public function cancel(AsOrder $order): void
    {
        if ($order->status === AsOrder::AWAITING) {
            $order->update(['status' => AsOrder::CANCELLED]);
        }
    }

    /** Unpaid orders older than AWAITING_HOURS are let go. */
    public function expireStale(?User $user = null): void
    {
        AsOrder::where('status', AsOrder::AWAITING)
            ->when($user, fn ($q) => $q->where('userId', $user->id))
            ->where('created_at', '<', now()->subHours(self::AWAITING_HOURS))
            ->update(['status' => AsOrder::CANCELLED, 'updated_at' => now()]);
    }

    /**
     * The proof: a picture or PDF of the receipt, a reference number, or
     * both. The order goes to review; a GCash receipt is then read.
     *
     * @return AsOrder the order as it stands after the proof (maybe approved)
     */
    public function submit(AsOrder $order, ?UploadedFile $file, ?string $ref, ?string $note): AsOrder
    {
        if ($order->status !== AsOrder::AWAITING) {
            throw new RuntimeException('This order is no longer waiting for a payment.');
        }

        $fileRow = null;
        if ($file) {
            $fileRow = $this->keepFile($order, $file);
            if ($this->shaSeenElsewhere($fileRow['sha'], $order->id)) {
                $fileRow['duplicate'] = true;
            }
        }
        $ref = trim((string) $ref) !== '' ? mb_substr(trim($ref), 0, 64) : null;

        $order->update([
            'status' => AsOrder::REVIEW,
            'refNumber' => $ref,
            'proofKind' => $fileRow ? $fileRow['kind'] : 'ref',
            'proofFileId' => $fileRow['id'] ?? null,
            'proofSha' => $fileRow['sha'] ?? null,
            'buyerNote' => $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null,
            'submittedAt' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * After the proof: read a GCash receipt, approve a plan Anee vouches for,
     * and tell everyone what happened. The buyer waits for this call when a
     * plan could be approved on the spot; for credits it runs after the page
     * has answered (the reading only helps the admin).
     */
    public function afterProof(AsOrder $order): AsOrder
    {
        $settings = ManualPay::settings();
        if ($order->method === 'gcash' && $order->proofFileId) {
            $report = app(ReceiptCheck::class)->run($order);
            $order->refresh();
            $mayAuto = $order->isPlan() && $settings['aiAutoApprove'];
            if ($mayAuto && ($report['verdict'] ?? '') === 'pass' && $order->status === AsOrder::REVIEW) {
                try {
                    $this->approve($order, 'ai');

                    return $order->fresh();
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } elseif ($order->method === 'gcash') {
            $order->update(['aiStatus' => 'skipped', 'aiReport' => ['verdict' => 'skipped', 'summary' => 'Only a reference number was sent, so there was nothing for Anee to read.', 'checks' => []]]);
        }

        $order->refresh();
        if ($order->status === AsOrder::REVIEW) {
            $this->tellReceived($order);
            $this->tellAdmins($order);
        }

        return $order;
    }

    // ------------------------------------------------------------------
    // Decisions
    // ------------------------------------------------------------------

    /** The payment is good: the plan starts or the credits land. */
    public function approve(AsOrder $order, string $by): AsOrder
    {
        $done = DB::transaction(function () use ($order, $by) {
            $o = AsOrder::whereKey($order->id)->lockForUpdate()->first();
            if (! $o || ! in_array($o->status, [AsOrder::REVIEW, AsOrder::AWAITING], true)) {
                throw new RuntimeException('Only an order waiting for a decision can be approved.');
            }
            $user = User::find($o->userId);
            if (! $user) {
                throw new RuntimeException('The buyer of this order no longer exists.');
            }
            $effect = $o->isPlan() ? $this->applyPlan($o, $user) : $this->applyCredits($o, $user);
            $o->update(['status' => AsOrder::APPROVED, 'approvedAt' => now(), 'decidedBy' => $by, 'effect' => $effect]);

            return $o->fresh();
        });

        $this->tellApproved($done);

        return $done;
    }

    public function reject(AsOrder $order, string $by, ?string $reason): AsOrder
    {
        $done = DB::transaction(function () use ($order, $by, $reason) {
            $o = AsOrder::whereKey($order->id)->lockForUpdate()->first();
            if (! $o || ! in_array($o->status, [AsOrder::REVIEW, AsOrder::AWAITING], true)) {
                throw new RuntimeException('Only an order waiting for a decision can be rejected.');
            }
            $o->update(['status' => AsOrder::REJECTED, 'rejectedAt' => now(), 'decidedBy' => $by, 'rejectReason' => $this->clip($reason)]);

            return $o->fresh();
        });

        $this->tell($done, 'order_rejected', 'We could not verify your payment', 'Order ' . $done->orderNumber . ' was not approved.');

        return $done;
    }

    /** Take an approval back: exactly what it gave, and nothing else. */
    public function revoke(AsOrder $order, string $by, ?string $reason): AsOrder
    {
        $done = DB::transaction(function () use ($order, $by, $reason) {
            $o = AsOrder::whereKey($order->id)->lockForUpdate()->first();
            if (! $o || $o->status !== AsOrder::APPROVED) {
                throw new RuntimeException('Only an approved order can be revoked.');
            }
            $effect = (array) $o->effect;
            $why = 'Revoked order ' . $o->orderNumber;

            if (! empty($effect['subscriptionId'])) {
                $sub = Subscription::whereKey($effect['subscriptionId'])->lockForUpdate()->first();
                if ($sub && $sub->status === Subscription::STATUS_ACTIVE) {
                    $sub->update([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelledAt' => now(),
                        'notes' => trim(($sub->notes ? $sub->notes . "\n" : '') . $why . ($reason ? ': ' . $reason : '')),
                    ]);
                    $this->rechain((int) $o->userId);
                }
            }
            if (! empty($effect['credits'])) {
                $this->credits->takeBack((int) $o->userId, (float) $effect['credits'], $why);
            }
            if (! empty($effect['creditPurchaseId'])) {
                AiCreditPurchase::whereKey($effect['creditPurchaseId'])->update(['status' => 'revoked']);
            }
            $o->update(['status' => AsOrder::REVOKED, 'revokedAt' => now(), 'revokedBy' => $by, 'revokeReason' => $this->clip($reason)]);

            return $o->fresh();
        });

        $this->tell($done, 'order_revoked', 'Your purchase was revoked', 'Order ' . $done->orderNumber . ' was revoked.');

        return $done;
    }

    // ------------------------------------------------------------------
    // What an approval does
    // ------------------------------------------------------------------

    private function applyPlan(AsOrder $o, User $user): array
    {
        $now = Carbon::now('Asia/Manila');
        $rank = ManualPay::rank($o->tier);

        // Start now, or when every active plan at this level or above ends.
        $start = $now->copy();
        $live = Subscription::where('userId', $user->id)->where('deleteStatus', 1)
            ->where('status', Subscription::STATUS_ACTIVE)->where('expiresAt', '>', $now)->get();
        foreach ($live as $row) {
            if (ManualPay::rank(User::tierForPlan($row->planKey, $row->planName)) >= $rank && $row->expiresAt->gt($start)) {
                $start = $row->expiresAt->copy();
            }
        }

        $sub = Subscription::create([
            'userId' => $user->id,
            'planId' => null,
            'planKey' => ManualPay::planKey($o->tier, $o->period),
            'planName' => $o->itemName,
            'price' => $o->total,
            'durationDays' => (int) $o->days,
            'orderNumber' => $o->orderNumber,
            'status' => Subscription::STATUS_ACTIVE,
            'startsAt' => $start,
            'expiresAt' => $start->copy()->addDays((int) $o->days),
            'verifiedAt' => $now,
            'notes' => 'Paid by ' . $o->methodLabel() . ', order ' . $o->orderNumber,
            'deleteStatus' => 1,
        ]);

        $credits = (int) ($o->credits ?? 0);
        if ($credits > 0) {
            $this->credits->grant($user->id, $credits, $o->itemName . ' — ' . number_format($credits) . ' AI credits with the plan, order ' . $o->orderNumber, 'purchase');
        }

        return [
            'subscriptionId' => $sub->id,
            'startsAt' => $sub->startsAt->toDateTimeString(),
            'expiresAt' => $sub->expiresAt->toDateTimeString(),
            'queued' => $start->gt($now->copy()->addMinute()),
            'credits' => $credits,
        ];
    }

    private function applyCredits(AsOrder $o, User $user): array
    {
        $credits = (int) $o->credits;
        $this->credits->grant($user->id, $credits, $o->itemName . ', order ' . $o->orderNumber, 'purchase');
        // The credit shop's own record, which the mother app's AI page counts as sales.
        $purchase = AiCreditPurchase::create([
            'userId' => $user->id,
            'packId' => $o->packId,
            'packName' => $o->itemName,
            'credits' => $credits,
            'price' => $o->total,
            'orderNumber' => $o->orderNumber,
            'status' => 'active',
            'grantedAt' => now(),
            'deleteStatus' => 1,
        ]);

        return ['credits' => $credits, 'creditPurchaseId' => $purchase->id];
    }

    /**
     * After a plan row is cancelled, the plans queued behind it move up:
     * each one waiting to start starts as soon as every other active plan
     * at its level or above allows -- never later than it was going to.
     */
    private function rechain(int $userId): void
    {
        $now = Carbon::now('Asia/Manila');
        $rows = Subscription::where('userId', $userId)->where('deleteStatus', 1)
            ->where('status', Subscription::STATUS_ACTIVE)->where('expiresAt', '>', $now)
            ->orderBy('startsAt')->orderBy('id')->get();

        foreach ($rows as $row) {
            if (! $row->startsAt || $row->startsAt->lte($now)) {
                continue;
            }
            $rank = ManualPay::rank(User::tierForPlan($row->planKey, $row->planName));
            $start = $now->copy();
            foreach ($rows as $other) {
                if ($other->id === $row->id) {
                    continue;
                }
                $otherStarted = ! $other->startsAt || $other->startsAt->lt($row->startsAt);
                if ($otherStarted && ManualPay::rank(User::tierForPlan($other->planKey, $other->planName)) >= $rank && $other->expiresAt->gt($start)) {
                    $start = $other->expiresAt->copy();
                }
            }
            if ($start->lt($row->startsAt)) {
                $length = $row->startsAt->diffInSeconds($row->expiresAt);
                $row->update(['startsAt' => $start, 'expiresAt' => $start->copy()->addSeconds($length)]);
            }
        }
    }

    // ------------------------------------------------------------------
    // Files
    // ------------------------------------------------------------------

    /**
     * Keep the proof in the database. A large photo is re-encoded (long edge
     * 2000 px, JPEG) so the row stays small; the sha is of the file as sent,
     * so the same receipt sent twice is recognised.
     */
    private function keepFile(AsOrder $order, UploadedFile $file): array
    {
        $raw = (string) file_get_contents($file->getRealPath());
        $sha = hash('sha256', $raw);
        $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $kind = $mime === 'application/pdf' ? 'pdf' : 'image';
        $bytes = $raw;

        if ($kind === 'image' && strlen($raw) > 1500000 && function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($raw);
            if ($img) {
                [$w, $h] = [imagesx($img), imagesy($img)];
                $scale = min(1, 2000 / max($w, $h));
                if ($scale < 1) {
                    $small = imagescale($img, (int) round($w * $scale), (int) round($h * $scale));
                    imagedestroy($img);
                    $img = $small;
                }
                ob_start();
                imagejpeg($img, null, 88);
                $bytes = (string) ob_get_clean();
                imagedestroy($img);
                $mime = 'image/jpeg';
            }
        }

        $id = DB::table('as_order_files')->insertGetId([
            'orderId' => $order->id,
            'mime' => $mime,
            'name' => mb_substr((string) $file->getClientOriginalName(), 0, 160),
            'size' => strlen($bytes),
            'sha' => $sha,
            'bytes' => $bytes,
            'created_at' => now(),
        ]);

        return ['id' => $id, 'sha' => $sha, 'kind' => $kind];
    }

    public function shaSeenElsewhere(?string $sha, int $orderId): bool
    {
        return $sha !== null && AsOrder::where('proofSha', $sha)->where('id', '!=', $orderId)
            ->whereNotIn('status', [AsOrder::CANCELLED])->exists();
    }

    /** The proof file's bytes and type, or null. */
    public static function file(AsOrder $order): ?object
    {
        return $order->proofFileId ? DB::table('as_order_files')->where('id', $order->proofFileId)->first() : null;
    }

    // ------------------------------------------------------------------
    // Telling people
    // ------------------------------------------------------------------

    private function tellReceived(AsOrder $o): void
    {
        $user = User::find($o->userId);
        if (! $user) {
            return;
        }
        $this->safe(fn () => $this->mail->sendTemplateToUser('order_received', $user, $this->tags($o)));
        $this->safe(fn () => $this->notes->notify($user->id, 'order', 'Payment received — being reviewed',
            'Order ' . $o->orderNumber . ' · ' . $o->itemName . '. We will tell you the moment it is decided.', $this->buyerUrl($o)));
    }

    private function tellApproved(AsOrder $o): void
    {
        $user = User::find($o->userId);
        if (! $user) {
            return;
        }
        if ($o->isPlan()) {
            $effect = (array) $o->effect;
            $tags = $this->tags($o) + ['planName' => $o->itemName, 'expiresAt' => Carbon::parse($effect['expiresAt'] ?? now())->format('M j, Y')];
            $this->safe(fn () => $this->mail->sendTemplateToUser('payment_approved', $user, $tags));
            $queued = ! empty($effect['queued']);
            $this->safe(fn () => $this->notes->notify($user->id, 'order', $queued ? 'Payment approved — your plan is lined up' : 'Payment approved — ' . $o->itemName . ' is active',
                $queued ? 'It starts ' . Carbon::parse($effect['startsAt'])->format('M j, Y') . ', when your current plan ends.' : 'Everything in your plan is open now.', route('account.subscription')));
        } else {
            $this->safe(fn () => $this->mail->sendTemplateToUser('credits_approved', $user, $this->tags($o)));
            $this->safe(fn () => $this->notes->notify($user->id, 'order', 'Payment approved — ' . number_format((int) $o->credits) . ' credits added',
                'They are in your account now and never expire.', route('ai.credits')));
        }
    }

    private function tell(AsOrder $o, string $template, string $title, string $body): void
    {
        $user = User::find($o->userId);
        if (! $user) {
            return;
        }
        $reason = $o->status === AsOrder::REVOKED ? $o->revokeReason : $o->rejectReason;
        $tags = $this->tags($o) + ['reason' => $reason ?: 'The payment could not be matched to what arrived.'];
        $this->safe(fn () => $this->mail->sendTemplateToUser($template, $user, $tags));
        $this->safe(fn () => $this->notes->notify($user->id, 'order', $title, $body . ($reason ? ' ' . $reason : ''), route('account.subscription')));
    }

    /** Every admin hears of a payment waiting for them. */
    private function tellAdmins(AsOrder $o): void
    {
        $admins = User::query()->where('deleteStatus', 1)
            ->where(fn ($q) => $q->whereNotNull('adminUserId')->where('adminUserId', '!=', '')->orWhere('panelAdmin', 1))
            ->pluck('id');
        $buyer = User::find($o->userId);
        foreach ($admins as $id) {
            $this->safe(fn () => $this->notes->notify((int) $id, 'order-admin', 'Payment to review — ' . $o->orderNumber,
                trim(($buyer?->firstName . ' ' . $buyer?->lastName)) . ' · ' . $o->itemName . ' · ' . $o->methodLabel() . ' ' . number_format((float) $o->total, 2),
                route('admin.orders', ['open' => $o->id])));
        }
    }

    private function tags(AsOrder $o): array
    {
        $sym = $o->currency === 'PHP' ? '₱' : '$';

        return [
            'orderNumber' => $o->orderNumber,
            'itemName' => $o->itemName,
            'planName' => $o->itemName,
            'price' => number_format((float) $o->total, 2),
            'currency' => $sym,
            'method' => $o->methodLabel(),
            'credits' => number_format((int) $o->credits),
            'reviewHours' => (string) ManualPay::settings()['reviewHours'],
            'orderUrl' => $this->buyerUrl($o),
        ];
    }

    public function buyerUrl(AsOrder $o): string
    {
        return route('checkout', ['order' => $o->orderNumber]);
    }

    private function number(): string
    {
        // Four characters no one misreads over the phone: no 0/O, 1/I/L.
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        do {
            $tail = '';
            for ($i = 0; $i < 4; $i++) {
                $tail .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $n = 'AN-' . now('Asia/Manila')->format('ymd') . '-' . $tail;
        } while (AsOrder::where('orderNumber', $n)->exists());

        return $n;
    }

    private function clip(?string $s): ?string
    {
        $s = trim((string) $s);

        return $s === '' ? null : mb_substr($s, 0, 500);
    }

    private function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
