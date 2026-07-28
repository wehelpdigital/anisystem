<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workers can carry a phone number. Priority is no longer edited in the UI —
 * the column stays only to preserve the existing list ordering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_workers', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_workers', 'phone')) {
                $table->string('phone', 32)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_workers', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_workers', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
