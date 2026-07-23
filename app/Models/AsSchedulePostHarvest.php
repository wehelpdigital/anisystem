<?php

namespace App\Models;

/**
 * A post-harvest observation: what the season actually produced, and anything
 * worth remembering when planning the next one.
 */
class AsSchedulePostHarvest extends BaseModel
{
    protected $table = 'as_schedule_post_harvests';

    /** category => label shown in the UI. */
    public const CATEGORIES = [
        'yield' => 'Yield',
        'quality' => 'Grain / produce quality',
        'pest' => 'Pest & disease outcome',
        'weather' => 'Weather impact',
        'storage' => 'Drying & storage',
        'market' => 'Selling & price',
        'lesson' => 'Lesson for next season',
        'other' => 'Other observation',
    ];

    protected $fillable = [
        'croppingScheduleId',
        'lotId',
        'observationDate',
        'title',
        'category',
        'yieldAmount',
        'yieldUnit',
        'moisturePercent',
        'pricePerUnit',
        'buyer',
        'notes',
        'imagePath',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'observationDate' => 'date',
        'yieldAmount' => 'decimal:2',
        'moisturePercent' => 'decimal:2',
        'pricePerUnit' => 'decimal:2',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function lot()
    {
        return $this->belongsTo(AsScheduleLot::class, 'lotId');
    }

    /** Gross value of this observation's harvest, when both figures are known. */
    public function getGrossValueAttribute(): ?float
    {
        if ($this->yieldAmount === null || $this->pricePerUnit === null) {
            return null;
        }

        return round((float) $this->yieldAmount * (float) $this->pricePerUnit, 2);
    }
}
