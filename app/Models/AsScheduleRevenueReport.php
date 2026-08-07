<?php

namespace App\Models;

class AsScheduleRevenueReport extends BaseModel
{
    protected $table = 'as_schedule_revenue_reports';

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'title',
        'yieldAmount',
        'yieldUnit',
        'pricePerUnit',
        'grossRevenue',
        'materialsCost',
        'servicesCost',
        'laborCost',
        'expensesCost',
        'totalCost',
        'netProfit',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'yieldAmount'   => 'decimal:2',
        'pricePerUnit'  => 'decimal:2',
        'grossRevenue'  => 'decimal:2',
        'materialsCost' => 'decimal:2',
        'servicesCost'  => 'decimal:2',
        'laborCost'     => 'decimal:2',
        'expensesCost'  => 'decimal:2',
        'totalCost'     => 'decimal:2',
        'netProfit'     => 'decimal:2',
        'deleteStatus'  => 'integer',
    ];

    public function scopeForSchedule($q, $scheduleId)
    {
        return $q->where('croppingScheduleId', $scheduleId);
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
