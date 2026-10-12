<?php

namespace App\Models;

/** One lot of an Anee Auto season: its days in the field and its fertilizer plan. */
class AsAutoLot extends BaseModel
{
    protected $table = 'as_auto_lots';

    protected $fillable = ['autoId', 'lotId', 'method', 'fieldDate', 'sowDate', 'prepDate', 'harvestDate', 'treeMonths', 'calcId', 'fert',
        'imbalance', 'imbalanceReason', 'imbalanceNote', 'imbalanceAt'];

    protected $casts = [
        'fieldDate' => 'date:Y-m-d', 'sowDate' => 'date:Y-m-d', 'prepDate' => 'date:Y-m-d', 'harvestDate' => 'date:Y-m-d',
        'fert' => 'array', 'imbalance' => 'integer', 'imbalanceAt' => 'datetime', 'treeMonths' => 'integer', 'calcId' => 'integer',
    ];

    public function lot()
    {
        return $this->belongsTo(AsScheduleLot::class, 'lotId');
    }
}
