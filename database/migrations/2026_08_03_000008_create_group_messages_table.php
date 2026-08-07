<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live group chat — a real-time-ish message stream per discussion group, shown
 * on its own tab beside the topic posts. Members only. House conventions.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_group_messages')) {
            return;
        }

        Schema::create('as_community_group_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('groupId')->index();
            $table->integer('userId')->index();
            $table->text('body')->nullable();
            $table->string('imagePath', 500)->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_group_messages');
    }
};
