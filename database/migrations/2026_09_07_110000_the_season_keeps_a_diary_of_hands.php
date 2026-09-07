<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Logs tab's book: one line per write, per season, per hand.
 *
 * Written by the audit middleware after any successful non-GET request that
 * resolved a schedule, so every module reports here without any module
 * knowing about it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_audits')) {
            return;
        }

        Schema::create('as_schedule_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('croppingScheduleId')->index();
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('routeName', 120);
            $table->string('method', 8);
            $table->string('label', 255);
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_audits');
    }
};
