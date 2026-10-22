<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A plan put into a season that already has work (2026-10-22, the owner):
 * Anee's check of it against the board, kept so the placement the farmer
 * then picks is the one she worked out, and so a check is paid for once and
 * applied once. App\Http\Controllers\PlanPortController.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_plan_ports')) {
            return;
        }
        Schema::create('as_plan_ports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->unsignedBigInteger('payerId')->nullable();
            // landprep | protocol, and the saved plan's id (as_plant_analyses).
            $table->string('source', 16);
            $table->unsignedBigInteger('sourceId');
            $table->unsignedBigInteger('scheduleId')->index();
            // plan lot index => season lot id, or "new".
            $table->json('map')->nullable();
            // pending | ready | failed | applied
            $table->string('status', 12)->default('pending');
            $table->json('result')->nullable();
            $table->json('phase')->nullable();
            $table->decimal('credits', 10, 2)->default(0);
            $table->string('error', 500)->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_plan_ports');
    }
};
