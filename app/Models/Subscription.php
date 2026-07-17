<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Subscription extends BaseModel
{
    protected $table = 'anisystem_subscriptions';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'userId', 'planId', 'planKey', 'planName', 'price', 'durationDays',
        'ecomOrderId', 'orderNumber', 'status', 'startsAt', 'expiresAt',
        'verifiedAt', 'suspendedAt', 'cancelledAt', 'expiryNotifiedAt',
        'notes', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'price' => 'decimal:2',
            'durationDays' => 'integer',
            'startsAt' => 'datetime:Y-m-d H:i:s',
            'expiresAt' => 'datetime:Y-m-d H:i:s',
            'verifiedAt' => 'datetime:Y-m-d H:i:s',
            'suspendedAt' => 'datetime:Y-m-d H:i:s',
            'cancelledAt' => 'datetime:Y-m-d H:i:s',
            'expiryNotifiedAt' => 'datetime:Y-m-d H:i:s',
            'deleteStatus' => 'integer',
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'planId');
    }

    /**
     * Status as it should be presented right now: an 'active' row whose
     * expiresAt has passed is effectively expired even before the daily
     * maintenance command persists that transition.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === self::STATUS_ACTIVE
            && $this->expiresAt !== null
            && $this->expiresAt->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }

    public function isUsable(): bool
    {
        return $this->effective_status === self::STATUS_ACTIVE;
    }

    public function daysRemaining(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }
        $now = Carbon::now('Asia/Manila');

        return $now->greaterThanOrEqualTo($this->expiresAt)
            ? 0
            : (int) $now->diffInDays($this->expiresAt);
    }

    /**
     * The linked mother-system order row (ecom_orders), if any.
     */
    public function ecomOrder(): ?object
    {
        if (! $this->ecomOrderId) {
            return null;
        }

        return DB::table('ecom_orders')->where('id', $this->ecomOrderId)->first();
    }
}
