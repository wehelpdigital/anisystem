<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "How to use" pages, one per module per device.
 *
 * Three devices rather than one responsive page, because the instructions
 * genuinely differ: a phone taps a kebab and a sheet slides up, a desktop
 * clicks a toolbar button that was never on the phone. Telling someone to
 * "click the toolbar" while they hold a phone is worse than saying nothing.
 *
 * The body is a list of blocks rather than free HTML so the page builder in
 * the mother app has something structured to drag around, and so the app can
 * render each kind the way it renders everything else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_tutorial_pages', function (Blueprint $table) {
            $table->id();
            $table->string('moduleKey', 40);
            $table->string('device', 10);              // mobile | tablet | desktop
            $table->string('title', 191);
            $table->string('summary', 400)->nullable();
            $table->json('blocks')->nullable();
            $table->unsignedBigInteger('updatedByUserId')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();

            // One page per module per device — the builder edits, never stacks.
            $table->unique(['moduleKey', 'device']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_tutorial_pages');
    }
};
