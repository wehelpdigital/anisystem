<?php

namespace App\Models;

/**
 * Append-only record of every credit movement. A client's balance is the sum of
 * their deltas, so it can always be reconstructed and audited.
 */
class AiCreditLedger extends BaseModel
{
    protected $table = 'anisystem_ai_credit_ledger';

    protected $fillable = [
        'userId', 'delta', 'balanceAfter', 'reason', 'source',
        'messageId', 'adminUserId', 'deleteStatus',
    ];

    protected $casts = [
        'delta' => 'decimal:2',
        'balanceAfter' => 'decimal:2',
        'deleteStatus' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
