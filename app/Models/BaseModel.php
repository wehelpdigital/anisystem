<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared base for all AniSystem models living in the shared btc-check DB.
 * Mirrors the mother system's conventions: Asia/Manila timestamps and the
 * `deleteStatus` integer soft-delete flag (1 = active, 0 = soft-deleted).
 */
abstract class BaseModel extends Model
{
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function freshTimestamp()
    {
        return Carbon::now('Asia/Manila');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::instance($date)->timezone('Asia/Manila')->format('Y-m-d H:i:s');
    }

    public function scopeActive($query)
    {
        return $query->where($this->getTable().'.deleteStatus', 1);
    }
}
