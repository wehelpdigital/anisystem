<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global Lots (2026-10-16, the owner): a lot can wear a pin, set on the map
 * where it is, and a boundary drawn on the map point by point. The pin is
 * one latitude and longitude; the boundary is its points as JSON, with the
 * area it encloses in hectares beside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_field_profiles')) {
            return;
        }
        Schema::table('as_field_profiles', function (Blueprint $t) {
            if (! Schema::hasColumn('as_field_profiles', 'pinLat')) {
                $t->decimal('pinLat', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('as_field_profiles', 'pinLng')) {
                $t->decimal('pinLng', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('as_field_profiles', 'boundary')) {
                $t->text('boundary')->nullable();
            }
            if (! Schema::hasColumn('as_field_profiles', 'boundaryHa')) {
                $t->decimal('boundaryHa', 14, 4)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_field_profiles')) {
            return;
        }
        $have = array_values(array_filter(['pinLat', 'pinLng', 'boundary', 'boundaryHa'], fn ($c) => Schema::hasColumn('as_field_profiles', $c)));
        if ($have) {
            Schema::table('as_field_profiles', function (Blueprint $t) use ($have) {
                $t->dropColumn($have);
            });
        }
    }
};
