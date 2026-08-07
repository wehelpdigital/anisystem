<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A Philippine place (province, city/municipality or barangay) used for
 * @location tagging suggestions. Populated by `php artisan locations:import`.
 */
class AsLocation extends Model
{
    protected $table = 'as_locations';

    protected $fillable = ['type', 'name', 'label', 'province', 'city', 'slug', 'sort'];
}
