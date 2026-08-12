<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A worker with no half/whole choice of their own follows the activity's own
 * length instead of being assumed to be there all day.
 *
 * The column defaulted to 'whole', so every row written before the checklist
 * existed claims a whole day — including work the activity itself calls half a
 * day. NULL now means "however long the task is", which is the honest answer
 * when nobody has said otherwise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activity_workers', function (Blueprint $table) {
            $table->string('dayPart', 8)->nullable()->default(null)->change();
        });
        // Rows that only say 'whole' because that was the default: nobody chose
        // it, so let them inherit rather than overstate the wage bill.
        DB::table('as_schedule_activity_workers')
            ->where('dayPart', 'whole')
            ->whereNull('salaryAmount')
            ->update(['dayPart' => null]);
    }

    public function down(): void
    {
        DB::table('as_schedule_activity_workers')->whereNull('dayPart')->update(['dayPart' => 'whole']);
        Schema::table('as_schedule_activity_workers', function (Blueprint $table) {
            $table->string('dayPart', 8)->default('whole')->change();
        });
    }
};
