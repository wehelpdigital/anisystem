<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Text content for whiteboard "text" shapes (the shape geometry / position lives
 * in the existing points column; other shapes leave this null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_board_events', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_board_events', 'shapeText')) {
                $table->string('shapeText', 500)->nullable()->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_board_events', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_board_events', 'shapeText')) {
                $table->dropColumn('shapeText');
            }
        });
    }
};
