<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Things to say while a screen is still working.
 *
 * A spinner over the word "Loading…" tells a farmer nothing except that the
 * app is not broken yet. These are the lines it says instead — one drawn at
 * random every time a wait begins, each paired with a little scene to draw
 * while it is up.
 *
 * They live in a table rather than in a PHP array so the mother site can add
 * to them, retire the ones that stop being funny, and turn a line off for a
 * season without a deploy. `scene` names an animation the front end knows how
 * to draw; an unknown scene falls back to the seedling, so a line added from
 * the admin can never render an empty box.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_loading_lines')) {
            // Already built on an earlier deploy — but the seeder is keyed on
            // the line itself, so running it again only adds what is new.
            (new \Database\Seeders\LoadingLineSeeder)->run();

            return;
        }
        Schema::create('as_loading_lines', function (Blueprint $t) {
            $t->increments('id');
            $t->string('line', 120);
            $t->string('scene', 40)->default('seedling');
            // Which waits a line is allowed to appear in. 'board' is the
            // activities timeline; the column exists so a later screen can
            // have its own voice without a second table.
            $t->string('surface', 40)->default('board')->index();
            $t->integer('deleteStatus')->default(1)->index();
            $t->timestamps();
        });

        (new \Database\Seeders\LoadingLineSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_loading_lines');
    }
};
