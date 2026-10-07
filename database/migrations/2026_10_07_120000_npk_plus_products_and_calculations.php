<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus (2026-10-07): the fertilizer calculator's shelf of products and
 * the farmer's saved calculations.
 *
 * as_npk_products   what each fertilizer, lime, micronutrient, organic or
 *                   biofertilizer carries, in percent by weight as labels
 *                   say it (N, P2O5, K2O; the rest as elements). System rows
 *                   (userId null) are the predefined list; a farmer's own
 *                   products carry their userId.
 * as_npk_calcs      one saved calculation: crop, area, the lines, the soil
 *                   test and the result, and the Anee reading if one ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_npk_products')) {
            Schema::create('as_npk_products', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('userId')->nullable()->index();
                $t->string('slug', 60)->nullable()->index();
                $t->string('name', 120);
                $t->string('local', 120)->nullable();
                $t->string('category', 24)->index();
                $t->string('form', 16)->default('granular');
                $t->string('unit', 8)->default('kg');
                $t->decimal('bagKg', 8, 2)->nullable();
                $t->decimal('densityKgL', 6, 3)->nullable();
                foreach (['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'] as $n) {
                    $t->decimal($n, 7, 3)->default(0);
                }
                $t->json('estimate')->nullable();
                $t->string('note', 300)->nullable();
                $t->unsignedSmallInteger('sortOrder')->default(0);
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_npk_calcs')) {
            Schema::create('as_npk_calcs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('userId')->index();
                $t->string('title', 190);
                $t->string('crop', 40)->nullable();
                $t->decimal('areaHa', 10, 4)->default(1);
                $t->decimal('targetYield', 8, 2)->nullable();
                $t->json('lines');
                $t->json('soil')->nullable();
                $t->json('result')->nullable();
                $t->unsignedBigInteger('analysisId')->nullable();
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }

        $rows = [
            // slug, name, local, category, form, unit, bagKg, density, [N, P2O5, K2O, Ca, Mg, S, Zn, B, Fe, Mn, Cu, Mo, Si, Cl], note
            ['urea', 'Urea', '46-0-0', 'nitrogen', 'granular', 'kg', 50, null, [46], 'Lost to the air on hot, dry soil: work it in or apply before rain or irrigation.'],
            ['ammonium-sulfate', 'Ammonium sulfate', '21-0-0', 'nitrogen', 'granular', 'kg', 50, null, [21, 0, 0, 0, 0, 24], 'Also feeds sulfur. Acidifies the soil a little, which helps alkaline ground.'],
            ['calcium-nitrate', 'Calcium nitrate', '15.5-0-0', 'nitrogen', 'granular', 'kg', 25, null, [15.5, 0, 0, 19], 'Quick nitrogen with calcium, often for vegetables.'],
            ['ammonium-phosphate', 'Ammonium phosphate', '16-20-0', 'phosphate', 'granular', 'kg', 50, null, [16, 20], 'Ammophos. A common basal fertilizer for palay and mais.'],
            ['ammonium-phosphate-s', 'Ammonium phosphate with sulfur', '16-20-0-13S', 'phosphate', 'granular', 'kg', 50, null, [16, 20, 0, 0, 0, 13], null],
            ['dap', 'Diammonium phosphate (DAP)', '18-46-0', 'phosphate', 'granular', 'kg', 50, null, [18, 46], null],
            ['map', 'Monoammonium phosphate (MAP)', '11-52-0', 'phosphate', 'granular', 'kg', 25, null, [11, 52], null],
            ['tsp', 'Triple superphosphate', '0-46-0', 'phosphate', 'granular', 'kg', 50, null, [0, 46, 0, 13], null],
            ['ssp', 'Single superphosphate (solophos)', '0-20-0', 'phosphate', 'granular', 'kg', 50, null, [0, 20, 0, 20, 0, 12], 'Also carries calcium and sulfur.'],
            ['rock-phosphate', 'Rock phosphate', '0-30-0', 'phosphate', 'powder', 'kg', 50, null, [0, 30, 0, 32], 'Slow to release. Works best on acidic soil.'],
            ['mop', 'Muriate of potash (MOP)', '0-0-60', 'potash', 'granular', 'kg', 50, null, [0, 0, 60, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 47], 'Carries chloride. Avoid on saline soil.'],
            ['sop', 'Sulfate of potash (SOP)', '0-0-50', 'potash', 'granular', 'kg', 25, null, [0, 0, 50, 0, 0, 18], 'Potash without chloride, with sulfur.'],
            ['potassium-nitrate', 'Potassium nitrate', '13-0-46', 'potash', 'granular', 'kg', 25, null, [13, 0, 46], null],
            ['complete-14', 'Complete fertilizer', '14-14-14', 'complete', 'granular', 'kg', 50, null, [14, 14, 14], null],
            ['complete-15', 'Complete fertilizer', '15-15-15', 'complete', 'granular', 'kg', 50, null, [15, 15, 15], null],
            ['complete-16', 'Complete fertilizer', '16-16-16', 'complete', 'granular', 'kg', 50, null, [16, 16, 16], null],
            ['npk-17-0-17', 'Nitrogen and potash', '17-0-17', 'complete', 'granular', 'kg', 50, null, [17, 0, 17], null],
            ['ws-20', 'Water soluble fertilizer', '20-20-20', 'foliar', 'powder', 'kg', null, null, [20, 20, 20], 'For foliar or drip feeding.'],
            ['ws-19', 'Water soluble fertilizer', '19-19-19', 'foliar', 'powder', 'kg', null, null, [19, 19, 19], null],
            ['ws-10-52', 'Water soluble high phosphate', '10-52-10', 'foliar', 'powder', 'kg', null, null, [10, 52, 10], 'Often used at flowering.'],
            ['kieserite', 'Kieserite (magnesium sulfate)', null, 'secondary', 'granular', 'kg', 50, null, [0, 0, 0, 0, 15, 20], null],
            ['epsom', 'Epsom salt (magnesium sulfate)', null, 'secondary', 'powder', 'kg', 25, null, [0, 0, 0, 0, 9.9, 13], null],
            ['gypsum', 'Gypsum (calcium sulfate)', null, 'secondary', 'powder', 'kg', 50, null, [0, 0, 0, 23, 0, 18], 'Replaces sodium on sodic soil, then the water carries the sodium out.'],
            ['lime', 'Agricultural lime (calcitic)', 'Apog', 'secondary', 'powder', 'kg', 50, null, [0, 0, 0, 36], 'Raises the pH of acidic soil.'],
            ['dolomite', 'Dolomite', null, 'secondary', 'powder', 'kg', 50, null, [0, 0, 0, 21, 12], 'Raises the pH and feeds magnesium.'],
            ['sulfur', 'Elemental sulfur', null, 'secondary', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 90], 'Lowers the pH of alkaline soil slowly.'],
            ['zinc-sulfate', 'Zinc sulfate (monohydrate)', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 17, 35], 'For zinc short paddies and alkaline soil.'],
            ['zinc-sulfate-7', 'Zinc sulfate (heptahydrate)', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 11, 22], null],
            ['borax', 'Borax', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 0, 0, 11], null],
            ['boric-acid', 'Boric acid', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 0, 0, 17], null],
            ['ferrous-sulfate', 'Ferrous sulfate', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 11, 0, 0, 20], null],
            ['manganese-sulfate', 'Manganese sulfate', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 18, 0, 0, 0, 31], null],
            ['copper-sulfate', 'Copper sulfate', null, 'micro', 'powder', 'kg', 25, null, [0, 0, 0, 0, 0, 12, 0, 0, 0, 0, 25], null],
            ['sodium-molybdate', 'Sodium molybdate', null, 'micro', 'powder', 'kg', 1, null, [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 39], 'Used in grams, mainly for legumes and cabbage family.'],
            ['chicken-manure', 'Chicken manure (dried)', 'Ipot ng manok', 'organic', 'granular', 'kg', 50, null, [3, 3, 2, 6, 0.8, 0.5], 'Values vary a lot with the feed and the drying.'],
            ['cattle-manure', 'Cattle or carabao manure (dried)', 'Dumi ng baka o kalabaw', 'organic', 'granular', 'kg', 50, null, [1.5, 1, 1.5, 1, 0.4, 0.3], 'Values vary with the animal and the age of the pile.'],
            ['vermicompost', 'Vermicompost', null, 'organic', 'granular', 'kg', 50, null, [1.5, 1, 1, 1, 0.3, 0.2], 'Values vary by what the worms were fed.'],
            ['organic-fertilizer', 'Commercial organic fertilizer', null, 'organic', 'granular', 'kg', 50, null, [2, 1, 1], 'Check the label: products differ.'],
            ['rice-straw', 'Rice straw returned to the field', 'Dayami', 'organic', 'bulk', 'kg', null, null, [0.6, 0.2, 1.7, 0, 0, 0.1, 0, 0, 0, 0, 0, 0, 5], 'Most of its potash comes back within a season.'],
            ['fish-emulsion', 'Fish emulsion', null, 'organic', 'liquid', 'L', null, 1.0, [5, 1, 1], 'Liquid, values vary.'],
            ['azospirillum', 'Azospirillum inoculant', null, 'bio', 'inoculant', 'dose', null, null, [], 'A living biofertilizer: bacteria that fix nitrogen from the air near the roots.'],
            ['azotobacter', 'Azotobacter inoculant', null, 'bio', 'inoculant', 'dose', null, null, [], 'Free living bacteria that fix nitrogen.'],
            ['rhizobium', 'Rhizobium inoculant (legumes)', null, 'bio', 'inoculant', 'dose', null, null, [], 'Only works on legumes such as mungbean, peanut and soybean.'],
            ['bacillus-megaterium', 'Bacillus megaterium (phosphate solubilizer)', null, 'bio', 'inoculant', 'dose', null, null, [], 'Frees phosphate locked in the soil.'],
            ['mycorrhiza', 'Mycorrhiza (VAM)', null, 'bio', 'inoculant', 'dose', null, null, [], 'A root fungus that helps the crop reach phosphate and water.'],
            ['ksb', 'Potassium solubilizing bacteria', null, 'bio', 'inoculant', 'dose', null, null, [], 'Frees potash from soil minerals.'],
        ];
        // What one hectare dose of a biofertilizer may add, in kg (field trial
        // ranges; the screen calls these estimates and says they vary).
        $estimates = [
            'azospirillum' => ['N' => 20], 'azotobacter' => ['N' => 15], 'rhizobium' => ['N' => 40],
            'bacillus-megaterium' => ['P2O5' => 15], 'mycorrhiza' => ['P2O5' => 10], 'ksb' => ['K2O' => 15],
        ];
        $cols = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'];
        foreach ($rows as $i => [$slug, $name, $local, $cat, $form, $unit, $bag, $density, $vals, $note]) {
            if (DB::table('as_npk_products')->whereNull('userId')->where('slug', $slug)->exists()) {
                continue;
            }
            $row = ['userId' => null, 'slug' => $slug, 'name' => $name, 'local' => $local, 'category' => $cat, 'form' => $form, 'unit' => $unit,
                'bagKg' => $bag, 'densityKgL' => $density, 'note' => $note, 'sortOrder' => $i + 1, 'deleteStatus' => 1,
                'estimate' => isset($estimates[$slug]) ? json_encode($estimates[$slug]) : null, 'created_at' => now(), 'updated_at' => now()];
            foreach ($cols as $k => $c) {
                $row[$c] = $vals[$k] ?? 0;
            }
            DB::table('as_npk_products')->insert($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_npk_calcs');
        Schema::dropIfExists('as_npk_products');
    }
};
