<?php

namespace App\Models;

class AsScheduleMaterial extends BaseModel
{
    protected $table = 'as_schedule_materials';

    protected $fillable = [
        'croppingScheduleId',
        'materialName',
        'description',
        'materialType',
        'unitOfMeasure',
        'priceAmount',
        'priceQuantity',
        'deleteStatus',
    ];

    protected $casts = [
        'priceAmount' => 'decimal:2',
        'priceQuantity' => 'decimal:4',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function getPricePerUnitAttribute(): float
    {
        $qty = (float) $this->priceQuantity;
        if ($qty <= 0) {
            return 0;
        }
        return round(((float) $this->priceAmount) / $qty, 4);
    }
}
