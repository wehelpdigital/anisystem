<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a shed note sits among its day's activities on the board.
 *
 * Null means the note lives in the day's strip at the top — the only place
 * that existed before. A number is a seat between the day's cards, on the
 * same number line the activity cards and sticky notes already share.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_inventory_moves', function (Blueprint $table) {
            $table->integer('boardSort')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('as_inventory_moves', function (Blueprint $table) {
            $table->dropColumn('boardSort');
        });
    }
};
