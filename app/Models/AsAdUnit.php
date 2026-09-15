<?php

namespace App\Models;

/**
 * One advertisement the free plan can carry: a Google AdSense slot, a
 * picture that links somewhere, or a script an ad network handed over.
 * Managed from the mother app (AniSystem > Ads); drawn by App\Support\Ads.
 */
class AsAdUnit extends BaseModel
{
    protected $table = 'as_ad_units';

    public const KINDS = [
        'adsense' => 'Google AdSense slot',
        'image' => 'Picture with a link',
        'script' => 'Ad network script',
    ];

    /** The surfaces a unit may be placed on; empty placements = all of them. */
    public const PLACEMENTS = [
        'dashboard' => 'Dashboard',
        'schedules' => 'Schedules page',
        'activities' => 'Activities board',
        'modules' => 'Other schedule modules',
        'community' => 'Community',
        'pricing' => 'Public pricing page',
        'upgrade' => 'Upgrade / renewal pages',
    ];

    protected $fillable = [
        'name', 'kind', 'placements', 'weight', 'isActive', 'startsAt', 'endsAt',
        'adClient', 'adSlot', 'adFormat', 'imagePath', 'imageAlt', 'linkUrl', 'scriptHtml',
        'impressions', 'clicks', 'sortOrder', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return parent::casts() + [
            'placements' => 'array',
            'isActive' => 'boolean',
            'startsAt' => 'datetime',
            'endsAt' => 'datetime',
            'weight' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /** Units that may be shown right now. */
    public function scopeLive($query)
    {
        $now = now('Asia/Manila');

        return $query->active()
            ->where('isActive', 1)
            ->where(fn ($q) => $q->whereNull('startsAt')->orWhere('startsAt', '<=', $now))
            ->where(fn ($q) => $q->whereNull('endsAt')->orWhere('endsAt', '>=', $now));
    }

    /** Whether this unit may appear on a surface. */
    public function fits(string $placement): bool
    {
        $list = array_values(array_filter((array) ($this->placements ?? [])));

        return ! $list || in_array($placement, $list, true);
    }

    /** The picture's address, wherever the mother app keeps it. */
    public function imageUrl(): ?string
    {
        if (blank($this->imagePath)) {
            return null;
        }
        if (preg_match('#^https?://#i', $this->imagePath)) {
            return $this->imagePath;
        }

        return \App\Support\MediaStore::url(\App\Support\MediaStore::REMOTE_PREFIX . ltrim($this->imagePath, '/'));
    }
}
