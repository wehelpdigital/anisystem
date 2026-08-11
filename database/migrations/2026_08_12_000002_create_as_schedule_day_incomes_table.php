<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money the day BROUGHT IN — a service sold, produce sold off the side of the
 * field, anything earned during a cropping season that is not the harvest
 * itself. Its own table rather than a flag on day expenses: every expense
 * total in the app sums that table, and a stray income row would quietly
 * corrupt each one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_day_incomes', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId');
            $table->integer('versionId')->nullable();
            $table->date('incomeDate');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('title', 191)->nullable();
            $table->string('note', 500)->nullable();
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index(['croppingScheduleId', 'incomeDate'], 'as_day_income_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_day_incomes');
    }
};
