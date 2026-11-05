<?php

namespace App\Models\Concerns;

use App\Support\FarmShelf;

/** Marks a row saved from a worker account as the farm's too. See App\Support\FarmShelf. */
trait HeldByFarm
{
    public static function bootHeldByFarm(): void
    {
        static::creating(function ($model) {
            if (empty($model->farmOwnerId) && ($farm = FarmShelf::stamp())) {
                $model->setAttribute('farmOwnerId', $farm);
            }
        });
    }
}
