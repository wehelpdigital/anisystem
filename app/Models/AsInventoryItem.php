<?php

namespace App\Models;

/**
 * A thing the farm keeps: Urea, Cartap, a drum of diesel.
 *
 * Stock is held in `unit` — the base unit — and `packSize`/`packLabel` are a
 * way of SAYING it, not a second unit to keep count in. A farm buys Urea in
 * 50 kg bags and applies it in kilos; storing bags would make "12 kg out" an
 * awkward fraction, and storing both would make them disagree.
 */
class AsInventoryItem extends BaseModel
{
    protected $table = 'as_inventory_items';

    /** The kinds, and the units each one is normally counted in. */
    public const KINDS = [
        'granular' => ['label' => 'Granular fertiliser', 'icon' => '🧂', 'units' => ['kg', 'g']],
        'foliar' => ['label' => 'Foliar / liquid feed', 'icon' => '🧪', 'units' => ['L', 'ml']],
        'pesticide' => ['label' => 'Pesticide', 'icon' => '🐛', 'units' => ['L', 'ml', 'kg', 'g']],
        'herbicide' => ['label' => 'Herbicide', 'icon' => '🌿', 'units' => ['L', 'ml', 'kg', 'g']],
        'fungicide' => ['label' => 'Fungicide', 'icon' => '🍄', 'units' => ['L', 'ml', 'kg', 'g']],
        'molluscicide' => ['label' => 'Molluscicide', 'icon' => '🐌', 'units' => ['kg', 'g', 'L']],
        'seed' => ['label' => 'Seed', 'icon' => '🌱', 'units' => ['kg', 'g', 'piece']],
        'fuel' => ['label' => 'Fuel', 'icon' => '⛽', 'units' => ['L']],
        'tool' => ['label' => 'Tool / supply', 'icon' => '🧰', 'units' => ['piece']],
        'other' => ['label' => 'Other', 'icon' => '📦', 'units' => ['kg', 'g', 'L', 'ml', 'piece']],
    ];

    /** Every unit stock can be held in. */
    public const UNITS = ['kg', 'g', 'L', 'ml', 'piece'];

    protected $fillable = [
        'croppingScheduleId', 'name', 'kind', 'unit',
        'packSize', 'packLabel', 'lowAt', 'note', 'deleteStatus',
    ];

    protected $casts = [
        'croppingScheduleId' => 'integer',
        'packSize' => 'decimal:3',
        'lowAt' => 'decimal:3',
        'deleteStatus' => 'integer',
    ];

    public function moves()
    {
        return $this->hasMany(AsInventoryMove::class, 'itemId')
            ->where('as_inventory_moves.deleteStatus', 1);
    }

    public function icon(): string
    {
        return self::KINDS[$this->kind]['icon'] ?? '📦';
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind]['label'] ?? 'Other';
    }

    /** Does this item come in packs somebody counts in? */
    public function hasPack(): bool
    {
        return (float) $this->packSize > 0 && filled($this->packLabel);
    }

    /**
     * A quantity said the way this item is spoken about.
     *
     * "600 kg" for something bought loose; "12 bags · 600 kg" for something
     * bought in bags, because both numbers are useful and neither on its own
     * answers "have I got enough" and "how much do I order".
     */
    public function say(float $qty): string
    {
        $base = self::trim($qty) . ' ' . $this->unit;
        if (! $this->hasPack()) {
            return $base;
        }
        $packs = $qty / (float) $this->packSize;

        return self::trim($packs) . ' ' . \Illuminate\Support\Str::plural($this->packLabel, (int) round($packs))
            . ' · ' . $base;
    }

    /** A number without the noise: 12 rather than 12.000, 0.5 rather than .500. */
    public static function trim(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.') ?: '0';
    }
}
