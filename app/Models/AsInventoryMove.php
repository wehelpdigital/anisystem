<?php

namespace App\Models;

/**
 * One change to what the farm has on hand.
 *
 * `delta` is signed and in the item's base unit; `qtyBefore` and `qtyAfter`
 * are what the stock stood at either side of it. Those two are stored rather
 * than recomputed on the way out, because the log is a record of what was
 * true at the time: a correction made in March must not rewrite the reading
 * somebody took in January.
 */
class AsInventoryMove extends BaseModel
{
    /**
     * What this line cost the farm, in pesos: stock that came IN (a
     * delivery or an opening count) times what one unit cost on this line,
     * or, when the line carries no price of its own, what one unit of the
     * item costs. Nothing for stock going out -- that was paid for when it
     * came in. The day's cash on the board and the expense report both read
     * this, so they agree to the peso.
     */
    public function cost(?AsInventoryItem $item = null): float
    {
        if (! in_array($this->reason, [self::IN, self::OPEN], true) || (float) $this->delta <= 0) {
            return 0.0;
        }
        $price = $this->unitPrice !== null ? (float) $this->unitPrice : (float) (($item ?? $this->item)?->unitPrice ?? 0);

        return $price > 0 ? round((float) $this->delta * $price, 2) : 0.0;
    }

    protected $table = 'as_inventory_moves';

    /** The opening count — what was already there when the book was started. */
    public const OPEN = 'open';

    /** Stock arriving: bought, delivered, carried over. */
    public const IN = 'in';

    /** Stock used or lost, entered by hand. */
    public const OUT = 'out';

    /** Stock spent by an activity being marked done. */
    public const ACTIVITY = 'activity';

    /** A correction to make the book agree with the shed. */
    public const ADJUST = 'adjust';

    /** The item joining the shed's list — a line in the diary, no stock. */
    public const CREATED = 'created';

    public const REASONS = [
        self::OPEN => ['label' => 'Start', 'icon' => '📖'],
        self::IN => ['label' => 'Stock added', 'icon' => '📥'],
        self::OUT => ['label' => 'Used', 'icon' => '📤'],
        self::ACTIVITY => ['label' => 'Used by an activity', 'icon' => '✅'],
        self::ADJUST => ['label' => 'Correction', 'icon' => '✏️'],
        self::CREATED => ['label' => 'Added to the shed', 'icon' => '🏷️'],
    ];

    protected $fillable = [
        'croppingScheduleId', 'itemId', 'delta', 'qtyBefore', 'qtyAfter',
        'reason', 'activityId', 'happenedOn', 'note', 'byUserId', 'deleteStatus',
        'enteredQty', 'enteredUnit', 'boardSort', 'unitPrice',
    ];

    protected $casts = [
        'croppingScheduleId' => 'integer',
        'itemId' => 'integer',
        'activityId' => 'integer',
        'byUserId' => 'integer',
        'delta' => 'decimal:3',
        'qtyBefore' => 'decimal:3',
        'qtyAfter' => 'decimal:3',
        'enteredQty' => 'decimal:3',
        'happenedOn' => 'date:Y-m-d',
        'deleteStatus' => 'integer',
        'boardSort' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(AsInventoryItem::class, 'itemId');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason]['label'] ?? 'Change';
    }

    public function reasonIcon(): string
    {
        return self::REASONS[$this->reason]['icon'] ?? '•';
    }

    /** Did this move put stock in, rather than take it out? */
    public function isIn(): bool
    {
        return (float) $this->delta > 0;
    }
}
