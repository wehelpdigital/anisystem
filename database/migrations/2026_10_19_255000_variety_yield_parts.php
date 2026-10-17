<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The yield parts of each variety (2026-10-19, the owner: research every
 * variety's tillers, spikelets, grains, grain weight and the rest for the
 * Seed Count and Distance Calculator's potential yield, and keep them in
 * the database so the tool never looks them up again).
 *
 *   as_variety_yield_parts  one row per variety and calculator crop (rice
 *                           and rice_dsr are two rows of one variety):
 *                           crop (SeedSpacing::CROPS key), bookCrop (the
 *                           variety book's crop), nameKey, name, aliases
 *                           (for matching), type (inbred, hybrid, open
 *                           pollinated, variety), model (tiller, ear,
 *                           plant), parts (the overrides the calculator
 *                           takes, keyed as SeedSpacing::CROPS[..]['yield']),
 *                           by (the basis of each part), extra (the traits
 *                           behind them), fit (the recorded yield over the
 *                           parts' potential at the usual spacing), basis,
 *                           quality. Rows keyed '*hybrid', '*inbred' ... are
 *                           a kind's typical parts. Read by
 *                           App\Support\VarietyYield.
 *
 * The rows come from database/variety-yield/*.json (sync(), in batches of
 * 100, idempotent: unchanged rows are skipped, rows gone from the files are
 * retired). A later data change ships new files and a migration that calls
 * VarietyYield::sync() again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_variety_yield_parts')) {
            Schema::create('as_variety_yield_parts', function (Blueprint $t) {
                $t->id();
                $t->string('crop', 24);
                $t->string('nameKey', 120);
                $t->string('bookCrop', 40);
                $t->string('name', 160);
                $t->json('aliases')->nullable();
                $t->string('type', 24)->default('');
                $t->string('model', 8)->default('');
                $t->json('parts');
                $t->json('by')->nullable();
                $t->json('extra')->nullable();
                $t->decimal('fit', 6, 3)->nullable();
                $t->string('basis', 12)->default('');
                $t->tinyInteger('quality')->default(0);
                $t->unsignedInteger('sortOrder')->default(0);
                $t->tinyInteger('status')->default(1);
                $t->char('hash', 32)->default('');
                $t->timestamp('created_at')->nullable();
                $t->timestamp('updated_at')->nullable();
                $t->unique(['crop', 'nameKey']);
                $t->index(['crop', 'status']);
            });
        }
        VarietyYield::sync();
        Cache::forget('variety-yield:' . config('database.default') . ':ready');
    }

    public function down(): void
    {
        Schema::dropIfExists('as_variety_yield_parts');
    }
};
