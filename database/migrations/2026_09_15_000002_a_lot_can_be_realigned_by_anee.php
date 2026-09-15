<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A lot's growth stage can be realigned by Anee.
 *
 * The calendar counts days and reads a stage off the count; the plant
 * does not always agree -- a herbicide sets it back, a heatwave stunts
 * it, a hungry field runs late. Anee reads the lot's whole history and
 * says where the crop actually is. The lot keeps the answer as a shift
 * in days applied to every stage reading, and the run itself is kept
 * as a row: what was asked, what she said, and what it cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_lots') && ! Schema::hasColumn('as_schedule_lots', 'growthShiftDays')) {
            Schema::table('as_schedule_lots', function (Blueprint $table) {
                // Signed: negative = behind the calendar, positive = ahead.
                $table->smallInteger('growthShiftDays')->nullable()->after('dayType');
                $table->dateTime('growthRealignedAt')->nullable()->after('growthShiftDays');
                // What she found: stage, confidence, why, what to do.
                $table->json('growthRealign')->nullable()->after('growthRealignedAt');
            });
        }

        if (! Schema::hasTable('as_growth_realigns')) {
            Schema::create('as_growth_realigns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('croppingScheduleId')->index();
                $table->unsignedBigInteger('lotId')->index();
                $table->unsignedBigInteger('userId')->index();
                $table->date('asOf');
                // What the calendar said when she was asked.
                $table->integer('calendarDay')->nullable();
                $table->string('calendarStage', 120)->nullable();
                // pending | ready | failed
                $table->string('status', 16)->default('pending');
                $table->decimal('credits', 10, 2)->default(0);
                $table->json('result')->nullable();
                $table->string('error', 500)->nullable();
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_growth_realigns');
        if (Schema::hasColumn('as_schedule_lots', 'growthShiftDays')) {
            Schema::table('as_schedule_lots', function (Blueprint $table) {
                $table->dropColumn(['growthShiftDays', 'growthRealignedAt', 'growthRealign']);
            });
        }
    }
};
