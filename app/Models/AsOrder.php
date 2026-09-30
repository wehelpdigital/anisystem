<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A purchase paid by hand: a plan or a pack of credits, from the moment the
 * buyer picks how to pay to the decision on the proof they sent. See the
 * as_orders migration and App\Services\OrderService.
 */
class AsOrder extends Model
{
    protected $table = 'as_orders';

    public const AWAITING = 'awaiting';   // picked how to pay; nothing sent yet

    public const REVIEW = 'review';       // proof sent, waiting for a decision

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const REVOKED = 'revoked';

    public const CANCELLED = 'cancelled';  // abandoned before any proof

    protected $fillable = [
        'orderNumber', 'userId', 'kind', 'itemKey', 'itemName', 'tier', 'period', 'days', 'months',
        'packId', 'credits', 'currency', 'price', 'fee', 'total', 'method', 'status',
        'refNumber', 'proofKind', 'proofFileId', 'proofSha', 'buyerNote', 'submittedAt',
        'aiStatus', 'aiScore', 'aiReport', 'aiCheckedAt',
        'decidedBy', 'approvedAt', 'rejectedAt', 'rejectReason', 'revokedAt', 'revokedBy', 'revokeReason', 'effect',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'fee' => 'decimal:2',
        'total' => 'decimal:2',
        'aiReport' => 'array',
        'effect' => 'array',
        'submittedAt' => 'datetime',
        'aiCheckedAt' => 'datetime',
        'approvedAt' => 'datetime',
        'rejectedAt' => 'datetime',
        'revokedAt' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function isPlan(): bool
    {
        return $this->kind === 'plan';
    }

    /** How the buyer's own screens name the state. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::AWAITING => 'Waiting for your payment',
            self::REVIEW => 'Being reviewed',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Not approved',
            self::REVOKED => 'Revoked',
            self::CANCELLED => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'gcash' => 'GCash',
            'bank' => 'Bank transfer',
            'paypal' => 'PayPal',
            default => ucfirst((string) $this->method),
        };
    }
}
