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
        'details',
        'imagePath',
        'imagePaths',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'observationDate' => 'date',
        'imagePaths' => 'array',
        'details' => 'array',
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

    /**
     * The rows a harvest's size is counted from, out of a set of observations.
     *
     * A harvest is often said twice: what came off the field (Yield) and how
     * much of it was sold (Selling and price carries an amount too). Added
     * together they doubled the harvest, and the board's harvest record writes
     * both. So, lot by lot, the rows that are not a sale are the harvest; a
     * lot with only sales on record counts its sales, as it always did. The
     * money is not decided here: every priced row is still amount × price.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $rows
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function harvestRows($rows)
    {
        return collect($rows)
            ->filter(fn ($h) => $h->yieldAmount !== null && (float) $h->yieldAmount > 0)
            ->groupBy(fn ($h) => (int) ($h->lotId ?: 0))
            ->flatMap(function ($lotRows) {
                $field = $lotRows->filter(fn ($h) => $h->category !== 'market');

                return $field->isNotEmpty() ? $field : $lotRows;
            })
            ->values();
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
