<?php

namespace App\Models;

/**
 * One acquisition batch: what the ads cost over a window, to be read
 * against the registrations and conversions the window produced.
 */
class AsSalesAnalysis extends BaseModel
{
    protected $table = 'as_sales_analyses';

    protected $fillable = [
        'name', 'dateFrom', 'dateTo', 'adCost', 'adCadence', 'createdBy', 'deleteStatus',
    ];

    protected $casts = [
        'dateFrom' => 'date',
        'dateTo' => 'date',
        'adCost' => 'float',
    ];
}
