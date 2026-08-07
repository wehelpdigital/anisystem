<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-page whiteboards: each board event belongs to a page, and each page has
 * its own orientation (landscape / portrait).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_board_events', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_board_events', 'page')) {
                $table->unsignedInteger('page')->default(1)->index()->after('scheduleId');
            }
        });

        if (! Schema::hasTable('as_schedule_board_pages')) {
            Schema::create('as_schedule_board_pages', function (Blueprint $table) {
                $table->id();
                $table->integer('scheduleId')->index();
                $table->unsignedInteger('page');
                $table->string('orientation', 12)->default('landscape'); // landscape | portrait
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_board_pages');
        Schema::table('as_schedule_board_events', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_board_events', 'page')) {
                $table->dropColumn('page');
            }
        });
    }
};
