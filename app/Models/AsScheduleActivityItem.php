<?php

namespace App\Models;

class AsScheduleActivityItem extends BaseModel
{
    protected $table = 'as_schedule_activity_items';

    protected $fillable = [
        'activityId',
        'itemType',
        'materialId',
        // Which thing in the shed this line spends, if any. Null for a line
        // that is only a note to itself.
        'inventoryItemId',
        // The line's own word that it is a PURCHASE: the save posts a
        // stock-in at the line's price, and the tick spends it back out.
        'newBuy',
        'serviceId',
        'itemName',
        'unitPrice',
        'quantity',
        'unitOfMeasure',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unitPrice' => 'decimal:2',
        'deleteStatus' => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(AsScheduleActivity::class, 'activityId');
    }

    public function material()
    {
        return $this->belongsTo(AsScheduleMaterial::class, 'materialId');
    }

    public function service()
    {
        return $this->belongsTo(AsScheduleService::class, 'serviceId');
    }

    /** Display name — the typed itemName, or the legacy material/service name. */
    public function displayName(): string
    {
        if (filled($this->itemName)) {
            return $this->itemName;
        }
        if ($this->itemType === 'material') {
            return optional($this->material)->materialName ?: 'Item';
        }
        if ($this->itemType === 'service') {
            return optional($this->service)->serviceName ?: 'Item';
        }
        return 'Item';
    }

    /** Display unit — the item's unit, or a legacy material's unit. */
    public function displayUnit(): ?string
    {
        return $this->unitOfMeasure ?: optional($this->material)->unitOfMeasure;
    }
}
