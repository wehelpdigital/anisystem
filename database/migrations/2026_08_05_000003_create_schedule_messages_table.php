<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-schedule team group chat. A shared message stream scoped to one cropping
 * schedule, for the owner and their worker sub-members. (Private 1:1 messages
 * reuse the community `as_community_messages` store, not this table.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_messages')) {
            return;
        }
        Schema::create('as_schedule_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId')->index();
            $table->integer('userId')->index();
            $table->text('body')->nullable();
            $table->string('imagePath', 500)->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_messages');
    }
};
