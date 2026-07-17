<?php

namespace App\Models;

class Plan extends BaseModel
{
    protected $table = 'anisystem_plans';

    protected $fillable = [
        'planKey', 'planName', 'price', 'durationDays', 'description', 'features',
        'ecomProductId', 'ecomVariantId', 'isActive', 'sortOrder', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'price' => 'decimal:2',
            'durationDays' => 'integer',
            'features' => 'array',
            'isActive' => 'boolean',
            'sortOrder' => 'integer',
            'deleteStatus' => 'integer',
        ]);
    }

    public function scopeVisible($query)
    {
        return $query->where('deleteStatus', 1)->where('isActive', 1)->orderBy('sortOrder');
    }

    public function getDurationLabelAttribute(): string
    {
        return match (true) {
            $this->durationDays >= 365 => '12 months',
            $this->durationDays >= 120 => (int) round($this->durationDays / 30).' months',
            $this->durationDays >= 60 => (int) round($this->durationDays / 30).' months',
            default => $this->durationDays.' days',
        };
    }
}
