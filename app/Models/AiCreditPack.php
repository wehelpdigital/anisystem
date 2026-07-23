<?php

namespace App\Models;

class AiCreditPack extends BaseModel
{
    protected $table = 'anisystem_ai_credit_packs';

    protected $fillable = [
        'packKey', 'packName', 'credits', 'price', 'description',
        'ecomProductId', 'ecomVariantId', 'isActive', 'sortOrder', 'deleteStatus',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price' => 'decimal:2',
        'isActive' => 'boolean',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    /** Peso per credit — drives the "best value" line on the purchase page. */
    public function getPerCreditAttribute(): float
    {
        return $this->credits > 0 ? round((float) $this->price / $this->credits, 2) : 0.0;
    }
}
