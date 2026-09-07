<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Undo that survives leaving the page.
 *
 * One row per (season, module key, user): the undo and redo stacks, capped
 * at fifteen steps each, as JSON. The board, the drawing pad and the map
 * mirror their local stacks here and seed themselves from it on load.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_undo_steps')) {
            return;
        }

        Schema::create('as_undo_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('croppingScheduleId');
            $table->string('module', 48);
            $table->unsignedBigInteger('userId');
            $table->mediumText('steps');
            $table->timestamp('updated_at')->nullable();
            $table->unique(['croppingScheduleId', 'module', 'userId'], 'undo_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_undo_steps');
    }
};
