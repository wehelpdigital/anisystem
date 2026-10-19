<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus (2026-10-20, the owner: "cobalt sulfate is not in the micros
 * options"). The Micros tab already counts cobalt as a beneficial element
 * for beans and peanut, but no product on the shelf carried it, so a plan
 * could never cover it. The shelf gains a cobalt column and cobalt sulfate
 * (the heptahydrate sold for seed treatment: about 21% cobalt, 11% sulfur),
 * in 1 kg packs since a hectare takes grams of it. A farmer's own product can
 * carry cobalt from its label too.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_npk_products')) {
            return;
        }
        if (! Schema::hasColumn('as_npk_products', 'Co')) {
            Schema::table('as_npk_products', function (Blueprint $t) {
                $t->decimal('Co', 7, 3)->default(0)->after('Cl');
            });
        }
        if (DB::table('as_npk_products')->whereNull('userId')->where('slug', 'cobalt-sulfate')->exists()) {
            return;
        }
        $order = (int) (DB::table('as_npk_products')->whereNull('userId')->where('slug', 'sodium-molybdate')->value('sortOrder') ?? 34);
        DB::table('as_npk_products')->insert([
            'userId' => null, 'slug' => 'cobalt-sulfate', 'name' => 'Cobalt sulfate', 'local' => null,
            'category' => 'micro', 'form' => 'powder', 'unit' => 'kg', 'bagKg' => 1, 'densityKgL' => null,
            'note' => 'Used in grams, mostly mixed with the seed of beans and peanut, often with molybdenum, to help the root nodules fix nitrogen.',
            'sortOrder' => $order, 'deleteStatus' => 1, 'estimate' => null,
            'N' => 0, 'P2O5' => 0, 'K2O' => 0, 'Ca' => 0, 'Mg' => 0, 'S' => 11.4, 'Zn' => 0, 'B' => 0, 'Fe' => 0, 'Mn' => 0, 'Cu' => 0, 'Mo' => 0, 'Si' => 0, 'Cl' => 0, 'Co' => 21,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'cobalt-sulfate')->delete();
    }
};
