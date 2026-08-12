<?php

use App\Support\CropStages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The crop belongs to the lot, not to the season.
 *
 * A schedule held one cropType for everything under it, which is only true of
 * the simplest farm: the moment one lot is corn and the next is rice, the
 * season's single answer is wrong for one of them. It also made the growth
 * stage unanswerable — stages are a property of a plant on a patch of ground,
 * and both of those live on the lot.
 *
 * Existing lots inherit whatever their schedule said, so nothing that was
 * already true stops being true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->string('crop', 60)->nullable()->after('variety');
        });

        foreach (DB::table('as_cropping_schedules')->whereNotNull('cropType')->get(['id', 'cropType']) as $s) {
            $key = CropStages::normalize($s->cropType);
            if ($key) {
                DB::table('as_schedule_lots')->where('croppingScheduleId', $s->id)->update(['crop' => $key]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->dropColumn('crop');
        });
    }
};
