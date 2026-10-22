<?php

namespace App\Models\Concerns;

use App\Support\Trash;

/**
 * A model saved with deleteStatus 0 lands in the Trash at that moment, by
 * whoever is signed in; saved back to 1, it leaves it. Only the kinds
 * App\Support\Trash lists are filed; the rest pass through untouched.
 */
trait GoesToTrash
{
    public static function bootGoesToTrash(): void
    {
        static::updated(function ($model) {
            if ($model->wasChanged('deleteStatus')) {
                Trash::changed($model);
            }
        });
    }
}
