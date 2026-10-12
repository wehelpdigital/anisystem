<?php

use App\Support\PhFertilizers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Philippine fertilizer products (2026-10-12): NPK Plus's "Philippine
 * products" tab. The hand checked ones load here from
 * database/fertilizers/ph-products.json; the monthly fertilizers:fpa-sync
 * adds every product on the FPA's list of active registered fertilizers.
 * See App\Support\PhFertilizers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_ph_fertilizers')) {
            Schema::create('as_ph_fertilizers', function (Blueprint $t) {
                $t->id();
                $t->string('regKey', 191)->unique();
                $t->string('company', 191)->nullable();
                $t->string('brand', 120)->nullable()->index();
                $t->string('name', 191);
                $t->string('fpaName', 191)->nullable();
                $t->string('fpaReg', 60)->nullable();
                $t->date('fpaExpiry')->nullable();
                $t->string('fpaType', 80)->nullable();
                $t->string('category', 20)->index();
                $t->string('form', 20)->default('granular');
                $t->text('analysis')->nullable();
                $t->json('pct')->nullable();
                $t->json('packs')->nullable();
                $t->decimal('densityKgL', 6, 3)->nullable();
                $t->string('basis', 6)->default('w/w');
                $t->tinyInteger('curated')->default(0)->index();
                $t->tinyInteger('active')->default(1)->index();
                $t->date('asOf')->nullable();
                $t->timestamps();
            });
        }
        PhFertilizers::seedCurated();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ph_fertilizers');
    }
};
