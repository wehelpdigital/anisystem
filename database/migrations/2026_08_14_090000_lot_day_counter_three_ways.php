<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The day counter used to have two answers, and one of them meant two things.
 *
 * "DAS / DAT — seeded, then transplanted" was stored as 'DAS', so a lot that
 * was genuinely direct seeded had no way to say so and was read against the
 * transplanted calendar. The mode now has its own value, 'DAT', and 'DAS'
 * means direct seeding (DSR) only.
 *
 * Every existing 'DAS' lot chose the option that said "seeded, then
 * transplanted", so that is what it is moved to — behaviour is unchanged, and
 * a grower who direct seeds can now say it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('as_schedule_lots')->where('dayType', 'DAS')->update(['dayType' => 'DAT']);
        DB::table('as_cropping_schedules')->where('dayType', 'DAS')->update(['dayType' => 'DAT']);
    }

    public function down(): void
    {
        DB::table('as_schedule_lots')->where('dayType', 'DAT')->update(['dayType' => 'DAS']);
        DB::table('as_cropping_schedules')->where('dayType', 'DAT')->update(['dayType' => 'DAS']);
    }
};
