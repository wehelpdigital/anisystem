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

    public function maturityDays(): ?int
    {
        $mine = (int) ($this->daysToMaturity ?? 0);

        return $mine > 0 ? $mine : \App\Support\CropStages::maturity($this->crop);
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
        return collect([
            filled($this->locBarangay) ? 'Brgy. ' . trim($this->locBarangay) : null,
            filled($this->locZone) ? 'Zone ' . trim($this->locZone) : null,
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
