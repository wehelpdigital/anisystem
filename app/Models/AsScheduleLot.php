<?php

namespace App\Models;

class AsScheduleLot extends BaseModel
{
    protected $table = 'as_schedule_lots';

    protected $fillable = [
        'croppingScheduleId',
        'lotName',
        'lotSize',
        'lotSizeUnit',
        'variety',
        'crop',
        'daysToMaturity',
        'treePlantedAt',
        'locBarangay',
        'locZone',
        'locTown',
        'locProvince',
        // Where the place actually IS, as against what it is called. An
        // address gets a delivery note written; a pin gets somebody to the
        // gate.
        'pinLat',
        'pinLng',
        'pinLabel',
        'mapSaveId',
        'dayZeroDate',
        'transplantDate',
        'dayType',
        // Realign by Anee: a signed shift in days applied to every stage
        // reading of this lot, when it was set, and what she found.
        'growthShiftDays',
        'growthRealignedAt',
        'growthRealign',
        // The farmer's own delay counter: days the crop is behind the calendar.
        'delayDays',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'lotSize' => 'decimal:4',
        'pinLat' => 'float',
        'pinLng' => 'float',
        'mapSaveId' => 'integer',
        'dayZeroDate' => 'date:Y-m-d',
        'transplantDate' => 'date:Y-m-d',
        'growthShiftDays' => 'integer',
        'growthRealignedAt' => 'datetime',
        'growthRealign' => 'array',
        'treePlantedAt' => 'date:Y-m-d',
        'daysToMaturity' => 'integer',
        'deleteStatus' => 'integer',
    ];

    /**
     * How long this lot's crop takes, in days.
     *
     * Its own figure when it has one — varieties are sold by their duration
     * and that is the number a farmer plans around — and the crop's typical
     * figure otherwise, which is a reasonable answer and what every lot got
     * before this was askable.
     */
    /** Has somebody said where this is, in the way a phone can act on? */
    public function isPinned(): bool
    {
        return $this->pinLat !== null && $this->pinLng !== null;
    }

    /**
     * The link that opens this lot in Maps.
     *
     * The universal form on purpose: it opens the Maps app on a phone that
     * has one and the website on a machine that does not, and it drops the
     * marker on the exact point rather than on whatever Google decides the
     * nearest named thing is.
     */
    public function mapsHref(): ?string
    {
        if (! $this->isPinned()) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='
            . number_format((float) $this->pinLat, 6, '.', '') . '%2C'
            . number_format((float) $this->pinLng, 6, '.', '');
    }

    /**
     * The day (or month) the crop's STAGE is read at: the calendar's count
     * plus Realign by Anee's shift, when she has set one. The count itself
     * is still the calendar's -- "DAT 45" stays DAT 45 on every screen --
     * only the stage read off it moves.
     */
    public function stageDay(?array $age): ?int
    {
        if (! $age || ! isset($age['day'])) {
            return null;
        }

        return max(0, (int) $age['day'] + (int) ($this->growthShiftDays ?? 0));
    }

    public function maturityDays(): ?int
    {
        $mine = (int) ($this->daysToMaturity ?? 0);

        return $mine > 0 ? $mine : \App\Support\CropStages::maturity($this->crop);
    }

    /**
     * Realign by Anee, as every page draws it: her stored reading plus the
     * shift, when she read, and the crop's own stage table (first day and
     * name of each stage, and the days to harvest) so a page can lay the
     * calendar's day and hers on the same rail. Null until she has read
     * this lot. The table is read fresh, never stored: it is the crop's,
     * and an older reading draws against today's table.
     */
    public function realignPayload(): ?array
    {
        if ($this->growthRealignedAt === null || ! is_array($this->growthRealign)) {
            return null;
        }
        $r = $this->growthRealign;
        $crop = \App\Support\CropStages::normalize($this->crop);
        $maturity = $this->maturityDays();
        $stages = $crop ? \App\Support\CropStages::stagesFor($crop, $r['counter'] ?? null, $maturity) : [];

        return $r + [
            'shiftDays' => (int) $this->growthShiftDays,
            'at' => $this->growthRealignedAt->toIso8601String(),
            'maturity' => $maturity,
            'stages' => array_values(array_map(fn ($s) => ['from' => (int) $s[0], 'label' => (string) $s[1], 'what' => (string) ($s[2] ?? '')], $stages)),
        ];
    }

    /**
     * How old this lot's trees are, in whole months, or null if it is not a
     * perennial or nobody has said when they went in.
     *
     * Worked out from the planting date every time it is asked, so it is
     * right this season and right the next one. An age typed in and stored as
     * a number would be wrong by exactly as long as the app had been running.
     */
    public function treeAgeMonths(): ?int
    {
        if (! $this->treePlantedAt || ! \App\Support\CropStages::isPerennial($this->crop)) {
            return null;
        }

        return max(0, (int) $this->treePlantedAt->diffInMonths(now('Asia/Manila')));
    }

    /** Human-readable full address for display, e.g. "Brgy. San Jose, Zone 3, Talavera, Nueva Ecija". */
    public function getFullAddressAttribute(): string
    {
        // The prefixes are the country's ("Brgy." at home, none in the US).
        $lot = \App\Support\Region::lot();

        return collect([
            filled($this->locBarangay) ? ($lot['barangay']['prefix'] ?? '') . trim($this->locBarangay) : null,
            filled($this->locZone) ? ($lot['zone']['prefix'] ?? 'Zone ') . trim($this->locZone) : null,
            filled($this->locTown) ? trim($this->locTown) : null,
            filled($this->locProvince) ? trim($this->locProvince) : null,
        ])->filter()->implode(', ');
    }

    /** The geocodable part of the address ("Town, Province"), or null if unusable. */
    public function getGeocodeQueryAttribute(): ?string
    {
        $q = collect([$this->locTown, $this->locProvince])
            ->filter(fn ($p) => filled($p))
            ->map(fn ($p) => trim($p))
            ->implode(', ');

        return $q !== '' ? $q : null;
    }

    /** Stable key for de-duplicating identical locations across lots/schedules. */
    public function getLocationKeyAttribute(): ?string
    {
        $q = $this->geocode_query;

        return $q ? substr(md5(mb_strtolower($q)), 0, 12) : null;
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function activities()
    {
        return $this->belongsToMany(
            AsScheduleActivity::class,
            'as_schedule_activity_lots',
            'lotId',
            'activityId'
        );
    }
}
