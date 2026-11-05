<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a worker saves from their worker account is also the farm's (2026-11-06, the
 * owner). farmOwnerId names the boss whose farm the save was made in, and stays empty
 * for anything saved from a personal account. App\Support\FarmShelf writes and reads it.
 */
return new class extends Migration
{
    private const TABLES = [
        'as_schedule_notes', 'as_gallery_images', 'as_schedule_map_saves', 'as_plant_analyses',
        'as_protocols', 'as_npk_calcs', 'as_contacts', 'as_range_effects',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'farmOwnerId')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('farmOwnerId')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'farmOwnerId')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('farmOwnerId');
                });
            }
        }
    }
};
