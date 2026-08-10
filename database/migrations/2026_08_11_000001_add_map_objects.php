<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shapes the team draws on the Collab Room map: field boundaries, measured
 * lines, labels. Unlike the whiteboard these are planning artifacts tied to
 * real ground, so they persist — no session archiving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_map_objects', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId')->index();
            $table->integer('userId');
            $table->string('kind', 12);          // pen | line | path | rect | area | text
            $table->string('color', 16)->nullable();
            $table->unsignedSmallInteger('width')->default(3);
            $table->text('points');              // JSON [[lat,lng],...]
            $table->string('label', 500)->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index(['scheduleId', 'deleteStatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_map_objects');
    }
};
