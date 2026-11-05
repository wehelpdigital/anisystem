<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Clips saved as filmed, waiting to be compressed by the scheduler. See App\Support\VideoSqueeze. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_video_squeezes')) {
            return;
        }
        Schema::create('as_video_squeezes', function (Blueprint $t) {
            $t->id();
            $t->string('path', 500);
            $t->string('status', 12)->default('waiting')->index();   // waiting, working, done, kept, skipped, failed
            $t->unsignedTinyInteger('tries')->default(0);
            $t->unsignedBigInteger('bytesBefore')->nullable();
            $t->unsignedBigInteger('bytesAfter')->nullable();
            $t->string('note', 255)->nullable();
            $t->timestamp('startedAt')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_video_squeezes');
    }
};
