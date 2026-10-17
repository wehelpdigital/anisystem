<?php

use App\Support\VarietyRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The registration trial facts of the registered varieties (2026-10-18, the
 * owner: scan every registered variety's papers and keep what they say, for
 * the analyses only, never the files and never their source).
 *
 *   as_variety_registry  one row per registered variety: its names, year,
 *                        type, growing environments and season, days and
 *                        height, yields (average, by season, the best test),
 *                        and in `facts` the per test yields, the advantage
 *                        over the checks, the seasons and sites tried, the
 *                        rating for each pest and disease, grain or fruit
 *                        quality, areas and limits; `stress` the tolerance
 *                        of salt, drought, flooding, cold, heat and lodging.
 *                        bookName names the curated record it matched;
 *                        conflicts keeps both figures where they differ.
 *                        sourceRef is a private item id for a re-run, never
 *                        shown. Read by App\Support\VarietyRegistry, through
 *                        App\Support\VarietyBook.
 *
 * The rows come from database/variety-registry/registry.json (sync(), in
 * batches of 100, idempotent: unchanged rows are skipped, rows gone from the
 * file are retired). A later data change ships a new file and a migration
 * that calls VarietyRegistry::sync() again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_variety_registry')) {
            Schema::create('as_variety_registry', function (Blueprint $t) {
                $t->id();
                $t->string('crop', 40);
                $t->string('nameKey', 120);
                $t->string('name', 120);
                $t->string('localName', 120)->nullable();
                $t->json('aliases')->nullable();
                $t->string('regNo', 60)->nullable();
                $t->smallInteger('year')->nullable();
                $t->string('type', 24)->nullable();
                $t->string('kind', 60)->nullable();
                $t->string('company', 120)->nullable();
                $t->json('envs')->nullable();
                $t->string('seasonFit', 8)->nullable();
                $t->decimal('days', 6, 1)->nullable();
                $t->decimal('heightCm', 6, 1)->nullable();
                $t->decimal('yieldAvg', 7, 2)->nullable();
                $t->decimal('yieldTop', 7, 2)->nullable();
                $t->json('stress')->nullable();
                $t->tinyInteger('salinityTolerant')->default(0);
                $t->json('facts')->nullable();
                $t->text('notes')->nullable();
                $t->string('bookName', 120)->nullable();
                $t->json('conflicts')->nullable();
                $t->tinyInteger('quality')->default(0);
                $t->string('sourceRef', 80)->nullable();
                $t->unsignedInteger('sortOrder')->default(0);
                $t->tinyInteger('status')->default(1);
                $t->char('hash', 32)->default('');
                $t->timestamp('created_at')->nullable();
                $t->timestamp('updated_at')->nullable();
                $t->unique(['crop', 'nameKey']);
                $t->index(['crop', 'status']);
                $t->index(['crop', 'salinityTolerant']);
            });
        }
        VarietyRegistry::sync(database_path(VarietyRegistry::FILE));
    }

    public function down(): void
    {
        Schema::dropIfExists('as_variety_registry');
    }
};
