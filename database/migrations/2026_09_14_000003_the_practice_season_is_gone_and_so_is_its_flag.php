<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The practice season was withdrawn before it reached anybody but the two
 * accounts it was tried on, and a page-by-page tutorial video took its place.
 * The flag that told a given season from a made one has nothing left to say,
 * so it goes.
 *
 * Guarded both ways: a fresh database never had the column, and a live one
 * may already have lost it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_cropping_schedules') || ! Schema::hasColumn('as_cropping_schedules', 'isDemo')) {
            return;
        }

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->dropColumn('isDemo');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_cropping_schedules') || Schema::hasColumn('as_cropping_schedules', 'isDemo')) {
            return;
        }

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->boolean('isDemo')->default(false)->after('isActive');
        });
    }
};
