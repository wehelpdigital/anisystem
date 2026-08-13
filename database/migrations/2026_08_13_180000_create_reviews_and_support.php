<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What growers think of the app.
 *
 * One row per person: their rating, their words if they left any, and how
 * many times they said "not now" — which is what stops the prompt asking a
 * third time. Support tickets already had their own tables; this only adds
 * the reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_app_reviews')) {
            return;
        }

        Schema::create('as_app_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->string('device', 20)->nullable();
            $table->unsignedTinyInteger('dismissals')->default(0);
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_app_reviews');
    }
};
