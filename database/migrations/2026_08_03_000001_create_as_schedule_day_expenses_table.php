<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra per-day expenses: ad-hoc costs a farmer incurs on a given date that
 * aren't captured by materials/labour (fuel, a rented sprayer, a snack for
 * the crew...). Each row is one amount + a short note, keyed to a date within
 * a schedule's active version. A day can carry several.
 *
 * House conventions of the shared `as_*` tables: camelCase columns, integer
 * `deleteStatus` soft delete, no FK constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_day_expenses')) {
            return;
        }

        Schema::create('as_schedule_day_expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId')->index();
            $table->integer('versionId')->nullable()->index();
            $table->date('expenseDate')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note', 500)->nullable();
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_day_expenses');
    }
};
