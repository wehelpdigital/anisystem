<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved field profiles (2026-10-15): a lot's soil, water and history as the
 * farmer answered them once (App\Support\FieldProfile), kept so every
 * analysis that needs them can offer "Use the answers from ..." instead of
 * asking again. One row a lot: keyed by the season lot when the lot came
 * from a season, else by its name. Owned by the farm owner
 * (WorkerContext::effectiveOwnerId), soft deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_field_profiles')) {
            return;
        }
        Schema::create('as_field_profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('userId');
            $t->unsignedBigInteger('scheduleId')->nullable();
            $t->unsignedBigInteger('lotId')->nullable();
            $t->string('name', 120);
            $t->string('crop', 40)->nullable();
            $t->json('answers')->nullable();
            $t->timestamps();
            $t->tinyInteger('deleteStatus')->default(1);
            $t->index(['userId', 'deleteStatus']);
            $t->index(['userId', 'lotId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_field_profiles');
    }
};
