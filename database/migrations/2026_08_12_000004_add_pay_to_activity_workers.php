<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a worker was actually on an activity for: a whole day or a half, and
 * what that costs when the usual rate is not the one that was agreed.
 *
 * It belongs on the pivot rather than on the worker, because the same person
 * can be a half day here and a whole day there in the same week.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activity_workers', function (Blueprint $table) {
            $table->string('dayPart', 8)->default('whole')->after('workerId');   // whole | half
            $table->decimal('salaryAmount', 12, 2)->nullable()->after('dayPart'); // null = the worker's own rate
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activity_workers', function (Blueprint $table) {
            $table->dropColumn(['dayPart', 'salaryAmount']);
        });
    }
};
