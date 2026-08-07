<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved Post-Harvest revenue calculations. A frozen copy of "what did this
 * season actually earn": the farmer's harvest inputs (yield × price) minus the
 * schedule's costs (materials, services, labour, extra expenses) at save time.
 * Snapshotted so a later edit to the schedule doesn't rewrite last season's
 * numbers.
 *
 * House conventions: camelCase columns, integer `deleteStatus`, no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_revenue_reports')) {
            return;
        }

        Schema::create('as_schedule_revenue_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId')->index();
            $table->integer('versionId')->nullable()->index();
            $table->string('title', 191);

            // Harvest inputs the farmer typed.
            $table->decimal('yieldAmount', 14, 2)->nullable();
            $table->string('yieldUnit', 24)->nullable();
            $table->decimal('pricePerUnit', 14, 2)->nullable();

            // Frozen figures at save time.
            $table->decimal('grossRevenue', 14, 2)->default(0);
            $table->decimal('materialsCost', 14, 2)->default(0);
            $table->decimal('servicesCost', 14, 2)->default(0);
            $table->decimal('laborCost', 14, 2)->default(0);
            $table->decimal('expensesCost', 14, 2)->default(0);
            $table->decimal('totalCost', 14, 2)->default(0);
            $table->decimal('netProfit', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_revenue_reports');
    }
};
