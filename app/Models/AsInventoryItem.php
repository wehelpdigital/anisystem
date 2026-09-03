<?php

namespace App\Models;

/**
 * A thing the farm keeps: Urea, Cartap, a drum of diesel.
 *
 * ONE unit, and the count is in that unit. A farm that buys Urea in 50 kg bags
 * counts bags: "12 bags (50 kg)". A farm that buys it loose counts kilos.
 * Which of those it is, is a single answer given once.
 *
 * It used to be two answers — a base unit, plus an optional pack size and pack
 * name that were only a way of SAYING the base unit. That is a defensible model
 * and it was the wrong one to put in front of somebody: three fields to
 * describe one thing, a form that asked "counted in kg" and then "bought in
 * packs of 50 called a bag", and a shelf that read "12 bags · 600 kg" when the
 * question was how many bags are left. The pack columns are still in the table
 * and are no longer written or read.
 */
class AsInventoryItem extends BaseModel
{
    protected $table = 'as_inventory_items';

    /**
     * Every unit stock can be counted in.
     *
     * `one` and `many` are the word itself; `of` is what one of them holds,
     * said rather than converted — nothing is ever divided by it. A bag is
     * 50 kg because that is what the farm buys, and if a farm's bags are 25 kg
     * it picks the other one. The size is in the name so that a number on its
     * own — 12 — is never ambiguous on a shelf, in a log, or in a total.
     */
    /*
     * `dim` and `factor`: what a unit is made of, and how much of it. A bag
     * (50 kg) IS fifty kilos — dim mass, factor 50 — so a move typed in kg
     * lands in a bag-counted book as bags, arithmetic instead of refusal.
     * Units without a stated size (piece, sack, sachet, box, roll) are
     * honest counts: each its own dimension, converting to nothing.
     */
    public const UNITS = [
        'kg' => ['one' => 'kg', 'many' => 'kg', 'dim' => 'mass', 'factor' => 1],
        'g' => ['one' => 'g', 'many' => 'g', 'dim' => 'mass', 'factor' => 0.001],
        'L' => ['one' => 'L', 'many' => 'L', 'dim' => 'volume', 'factor' => 1],
        'ml' => ['one' => 'ml', 'many' => 'ml', 'dim' => 'volume', 'factor' => 0.001],
        'piece' => ['one' => 'piece', 'many' => 'pieces', 'dim' => 'piece', 'factor' => 1],
        'bag50' => ['one' => 'bag', 'many' => 'bags', 'of' => '50 kg', 'dim' => 'mass', 'factor' => 50],
        'bag40' => ['one' => 'bag', 'many' => 'bags', 'of' => '40 kg', 'dim' => 'mass', 'factor' => 40],
        'bag25' => ['one' => 'bag', 'many' => 'bags', 'of' => '25 kg', 'dim' => 'mass', 'factor' => 25],
        'bag20' => ['one' => 'bag', 'many' => 'bags', 'of' => '20 kg', 'dim' => 'mass', 'factor' => 20],
        'sack' => ['one' => 'sack', 'many' => 'sacks', 'dim' => 'sack', 'factor' => 1],
        'bottle1' => ['one' => 'bottle', 'many' => 'bottles', 'of' => '1 L', 'dim' => 'volume', 'factor' => 1],
        'bottle250' => ['one' => 'bottle', 'many' => 'bottles', 'of' => '250 ml', 'dim' => 'volume', 'factor' => 0.25],
        'jug5' => ['one' => 'jug', 'many' => 'jugs', 'of' => '5 L', 'dim' => 'volume', 'factor' => 5],
        'drum200' => ['one' => 'drum', 'many' => 'drums', 'of' => '200 L', 'dim' => 'volume', 'factor' => 200],
        'sachet' => ['one' => 'sachet', 'many' => 'sachets', 'dim' => 'sachet', 'factor' => 1],
        'box' => ['one' => 'box', 'many' => 'boxes', 'dim' => 'box', 'factor' => 1],
        'roll' => ['one' => 'roll', 'many' => 'rolls', 'dim' => 'roll', 'factor' => 1],
    ];

    /**
     * A quantity in one unit, said in another — or null when the two are not
     * made of the same stuff. 70 kg into bag50 is 1.4; 500 ml into L is 0.5;
     * 3 pieces into kg is nobody's arithmetic and stays a refusal.
     */
    public static function convert(float $qty, string $from, string $to): ?float
    {
        if ($from === $to) {
            return $qty;
        }
        $a = self::UNITS[$from] ?? null;
        $b = self::UNITS[$to] ?? null;
        if (! $a || ! $b || $a['dim'] !== $b['dim']) {
            return null;
        }

        return $qty * $a['factor'] / $b['factor'];
    }

    /** Every unit sharing this one's dimension, this one first. */
    public static function kin(string $unit): array
    {
        $dim = self::UNITS[$unit]['dim'] ?? null;
        if ($dim === null) {
            return [$unit];
        }
        $keys = array_keys(array_filter(self::UNITS, fn ($u) => $u['dim'] === $dim));
        usort($keys, fn ($x, $y) => ($x === $unit ? 0 : 1) <=> ($y === $unit ? 0 : 1));

        return $keys;
    }

    /**
     * The kinds, and the units each one is actually bought in.
     *
     * The first is the default, so the common answer is already given: a
     * granular fertiliser is a 50 kg bag unless somebody says otherwise.
     */
    public const KINDS = [
        'granular' => ['label' => 'Granular fertiliser', 'icon' => '🧂',
            'units' => ['bag50', 'bag25', 'kg', 'g', 'sack']],
        'foliar' => ['label' => 'Foliar / liquid feed', 'icon' => '🧪',
            'units' => ['bottle1', 'L', 'ml', 'jug5', 'sachet']],
        'pesticide' => ['label' => 'Pesticide', 'icon' => '🐛',
            'units' => ['bottle250', 'bottle1', 'L', 'ml', 'sachet', 'kg', 'g']],
        'herbicide' => ['label' => 'Herbicide', 'icon' => '🌿',
            'units' => ['bottle1', 'L', 'ml', 'sachet', 'kg', 'g']],
        'fungicide' => ['label' => 'Fungicide', 'icon' => '🍄',
            'units' => ['sachet', 'bottle250', 'kg', 'g', 'L', 'ml']],
        'molluscicide' => ['label' => 'Molluscicide', 'icon' => '🐌',
            'units' => ['bag25', 'kg', 'g', 'sachet']],
        'seed' => ['label' => 'Seed', 'icon' => '🌱',
            'units' => ['bag40', 'bag20', 'kg', 'g', 'sack', 'piece']],
        'fuel' => ['label' => 'Fuel', 'icon' => '⛽',
            'units' => ['L', 'drum200']],
        'tool' => ['label' => 'Tool / supply', 'icon' => '🧰',
            'units' => ['piece', 'box', 'roll', 'sack']],
        'other' => ['label' => 'Other', 'icon' => '📦',
            'units' => ['piece', 'kg', 'g', 'L', 'ml', 'box', 'sack']],
    ];

    protected $fillable = [
        'croppingScheduleId', 'name', 'kind', 'unit',
        'lowAt', 'unitPrice', 'note', 'deleteStatus',
    ];

    protected $casts = [
        'croppingScheduleId' => 'integer',
        'lowAt' => 'decimal:3',
        'unitPrice' => 'decimal:2',
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

    /** The units this kind is offered, defaulting to the whole list. */
    public static function unitsFor(?string $kind): array
    {
        return self::KINDS[$kind]['units'] ?? array_keys(self::UNITS);
    }

    /**
     * The unit as a name on its own — "bags (50 kg)", "kg".
     *
     * The plural, because this names the unit rather than a quantity of it: a
     * dropdown row and a column heading are both about the many.
     */
    public function unitLabel(): string
    {
        return self::unitSays($this->unit, false);
    }

    /** The same, for any unit key, singular or plural. */
    public static function unitSays(?string $key, bool $singular = true): string
    {
        $u = self::UNITS[$key] ?? null;
        if (! $u) {
            // A unit from before this list existed, or one hand-typed by an
            // import. Said as it stands rather than swapped for a guess.
            return (string) $key;
        }
        $word = $singular ? $u['one'] : $u['many'];

        return isset($u['of']) ? $word . ' (' . $u['of'] . ')' : $word;
    }

    /**
     * A quantity said the way this item is counted.
     *
     * "12 bags (50 kg)", "600 kg", "1 piece". One number, one unit, and the
     * pack size carried along in the name so the number never has to be
     * converted by the person reading it.
     */
    public function say(float $qty): string
    {
        $n = self::trim($qty);

        return $n . ' ' . self::unitSays($this->unit, abs($qty) == 1.0);
    }

    /** A number without the noise: 12 rather than 12.000, 0.5 rather than .500. */
    public static function trim(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.') ?: '0';
    }
}
