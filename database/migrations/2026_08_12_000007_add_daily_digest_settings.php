<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The daily "what's on today and tomorrow" email, per schedule.
 *
 * Off by default: nobody should start receiving mail because they upgraded.
 * The hour is local to the farm (Asia/Manila), and the last-sent date is what
 * stops an hourly runner sending the same digest twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->boolean('notifyWorkersDaily')->default(false)->after('isActive');
            $table->boolean('notifyOwnerDaily')->default(false)->after('notifyWorkersDaily');
            $table->unsignedTinyInteger('notifyHour')->default(6)->after('notifyOwnerDaily');
            $table->date('notifyLastSentDate')->nullable()->after('notifyHour');
        });
    }

    public function down(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->dropColumn(['notifyWorkersDaily', 'notifyOwnerDaily', 'notifyHour', 'notifyLastSentDate']);
        });
    }
};
