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
    ];

    protected $casts = [
        'croppingScheduleId' => 'integer',
        'itemId' => 'integer',
        'activityId' => 'integer',
        'byUserId' => 'integer',
        'delta' => 'decimal:3',
        'qtyBefore' => 'decimal:3',
        'qtyAfter' => 'decimal:3',
        'happenedOn' => 'date:Y-m-d',
        'deleteStatus' => 'integer',
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
