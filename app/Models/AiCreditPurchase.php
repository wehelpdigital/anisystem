<?php

namespace App\Models;

class AiCreditPurchase extends BaseModel
{
    protected $table = 'anisystem_ai_credit_purchases';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'userId', 'packId', 'packName', 'credits', 'price',
        'ecomOrderId', 'orderNumber', 'status', 'grantedAt', 'deleteStatus',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price' => 'decimal:2',
        'grantedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
