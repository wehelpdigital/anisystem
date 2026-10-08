<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The field catalogue (2026-10-08): the pests, diseases and weeds of every
 * crop anee.io knows, and the weed control plans by crop and age, kept in
 * the database instead of in code (App\Support\FieldCatalogue). They were
 * rice first; now every crop of the catalog has its own.
 *
 *   as_crop_problems  one pest or disease: names, the crops it attacks,
 *                     where it shows, what to spray or why not
 *   as_weeds          one weed: names, group, life, the crops it troubles
 *   as_weed_plans     one crop group's weed control: the windows of its
 *                     age, what to do in each, the active ingredients
 *
 * The rows come from database/field-catalogue/*.json (the next migration
 * loads them). A row the files brought in carries source = file; the sync
 * never touches any other.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_crop_problems')) {
            Schema::create('as_crop_problems', function (Blueprint $t) {
                $t->id();
                $t->string('section', 12);
                $t->string('slug', 120);
                $t->string('name', 160);
                $t->string('sci', 255)->nullable();
                $t->string('shelf', 24);
                $t->string('kind', 24);
                $t->json('crops');
                $t->json('parts')->nullable();
                $t->json('signs')->nullable();
                $t->text('local')->nullable();
                $t->text('hint')->nullable();
                $t->json('ai')->nullable();
                $t->text('noSpray')->nullable();
                $t->json('sources')->nullable();
                // crop => place in that crop's list of its worst problems (0 first)
                $t->json('ranks')->nullable();
                $t->integer('sortOrder')->default(0);
                $t->string('source', 12)->default('file');
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
                $t->unique(['section', 'slug']);
            });
        }
        if (! Schema::hasTable('as_weeds')) {
            Schema::create('as_weeds', function (Blueprint $t) {
                $t->id();
                $t->string('slug', 120)->unique();
                $t->string('name', 160);
                $t->string('sci', 255)->nullable();
                $t->string('wgroup', 16);
                $t->string('life', 60)->nullable();
                $t->text('local')->nullable();
                $t->text('hint')->nullable();
                $t->json('crops');
                $t->json('sources')->nullable();
                $t->integer('sortOrder')->default(0);
                $t->string('source', 12)->default('file');
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_weed_plans')) {
            Schema::create('as_weed_plans', function (Blueprint $t) {
                $t->id();
                $t->string('planKey', 40)->unique();
                $t->string('label', 120);
                $t->string('local', 120)->nullable();
                $t->json('crops');
                $t->string('after', 60);
                $t->json('windows');
                $t->json('ingredients');
                $t->json('sources')->nullable();
                $t->integer('sortOrder')->default(0);
                $t->string('source', 12)->default('file');
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_weed_plans');
        Schema::dropIfExists('as_weeds');
        Schema::dropIfExists('as_crop_problems');
    }
};
