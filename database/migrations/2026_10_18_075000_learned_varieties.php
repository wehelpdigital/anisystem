<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Learn once (2026-10-18, the owner: "what you get, save it in the db as well
 * so that next time, it won't need anee to research online for that ... do
 * this for all other modules where anee needs to research for a product or
 * variety").
 *
 *   as_learned_varieties  a variety Anee looked up on the web for a farmer
 *                         (Land Prep, When to Plant, Variety Ranking, Anee
 *                         Auto...), kept as facts: its published name, the
 *                         names it is sold under, its NSIC number, company,
 *                         type, days, yields, height, ratings, tolerances,
 *                         seed preparation and seed rate, kept so the next
 *                         module that meets the name reads it here instead of
 *                         searching again (App\Support\VarietyLearned). A row
 *                         with aliasOf set only says that a name the farmer
 *                         typed is a variety already on the curated record.
 *                         Rows go stale after a year (staleAt) unless the
 *                         mother app has reviewed them.
 *
 * The products Anee learns go to as_products (source anee), made by the
 * Compatibility Checker's migration (App\Support\ProductLibrary::learn).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_learned_varieties')) {
            return;
        }
        Schema::create('as_learned_varieties', function (Blueprint $t) {
            $t->id();
            $t->string('crop', 40);
            $t->string('name', 120);
            $t->string('nameKey', 120);
            $t->json('aliases')->nullable();
            $t->string('aliasOf', 120)->nullable();
            $t->string('nsic', 60)->nullable();
            $t->string('company', 120)->nullable();
            $t->string('type', 16)->nullable();
            $t->smallInteger('year')->nullable();
            $t->smallInteger('maturityDays')->nullable();
            $t->decimal('yieldAvg', 6, 2)->nullable();
            $t->decimal('yieldTop', 6, 2)->nullable();
            $t->smallInteger('heightCm')->nullable();
            $t->string('season', 160)->nullable();
            $t->string('reactions', 400)->nullable();
            $t->string('tolerances', 400)->nullable();
            $t->string('grain', 300)->nullable();
            $t->string('caution', 300)->nullable();
            $t->json('seed')->nullable();
            $t->json('facts')->nullable();
            $t->json('sources')->nullable();
            $t->string('confidence', 8)->default('medium');
            $t->string('foundBy', 24)->default('');
            $t->unsignedBigInteger('userId')->nullable();
            $t->tinyInteger('status')->default(1);
            $t->tinyInteger('reviewed')->default(0);
            $t->timestamp('reviewedAt')->nullable();
            $t->unsignedInteger('hits')->default(0);
            $t->timestamp('lastHitAt')->nullable();
            $t->timestamp('staleAt')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
            $t->unique(['crop', 'nameKey']);
            $t->index(['crop', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_learned_varieties');
    }
};
