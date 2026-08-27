<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How long this lot's crop takes, and how old this lot's trees are.
 *
 * The same crop does not mature in the same number of days everywhere. A
 * hundred-day cabbage in Benguet is eighty in the lowlands, and rice varieties
 * are sold by their duration — 105, 115, 120 — because that is the number a
 * farmer plans around. The stage guidance was reading every lot against one
 * figure per crop, so a short-duration variety was told it had three weeks
 * left when it had one.
 *
 * `daysToMaturity` is optional on purpose. Left empty, the crop's own typical
 * figure stands, which is what happens today and is a reasonable answer. Given,
 * every stage moves with it.
 *
 * `treePlantedAt` is the other half. A mango has no day 40 — it has an age,
 * and what it wants depends on whether it is four years old or fourteen. The
 * form asks the age, because that is what somebody standing in the orchard
 * knows; what is stored is the date that age implies, so the number stays
 * right next season instead of quietly ageing into a lie.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_lots')) {
            return;
        }
        Schema::table('as_schedule_lots', function (Blueprint $t) {
            if (! Schema::hasColumn('as_schedule_lots', 'daysToMaturity')) {
                $t->unsignedSmallInteger('daysToMaturity')->nullable()->after('crop');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'treePlantedAt')) {
                $t->date('treePlantedAt')->nullable()->after('daysToMaturity');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_schedule_lots')) {
            return;
        }
        Schema::table('as_schedule_lots', function (Blueprint $t) {
            foreach (['daysToMaturity', 'treePlantedAt'] as $col) {
                if (Schema::hasColumn('as_schedule_lots', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
