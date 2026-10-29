<?php

use App\Support\RangeEffectBook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Range Effect calendar (2026-10-29, the owner): the effect book (as_effect_book, filled from
 * database/range-effect by RangeEffectBook::sync: how long each active ingredient and each nutrient keeps working,
 * and how much each matters at each growth stage; rows Anee looks up later are kept beside them as source 'anee')
 * and the readings farmers save (as_range_effects, one row a saved reading, kept per user and season). A later
 * release that changes the book's files adds a migration that calls RangeEffectBook::sync() again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_effect_book')) {
            Schema::create('as_effect_book', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 20);
                $table->string('ekey', 160);
                $table->json('data')->nullable();
                $table->string('source', 10)->default('file');
                $table->string('conf', 8)->nullable();
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique(['kind', 'ekey']);
                $table->index('source');
            });
        }
        if (! Schema::hasTable('as_range_effects')) {
            Schema::create('as_range_effects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId')->index();
                $table->unsignedBigInteger('croppingScheduleId')->index();
                $table->unsignedBigInteger('lotId')->nullable();
                $table->string('title', 190)->default('');
                $table->json('params')->nullable();
                $table->longText('result')->nullable();
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
        RangeEffectBook::forget();
        RangeEffectBook::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_range_effects');
        Schema::dropIfExists('as_effect_book');
    }
};
