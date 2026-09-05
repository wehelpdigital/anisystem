<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tags, and the strings that tie them to things.
 *
 * A tag is a word the farmer chooses — "fertilizer run", "typhoon damage",
 * "lot A replant" — and it can be tied to anything the activities module
 * can add: an activity, a day note, an expense, an income, a drawing, a
 * map, a photo, a video, a stock move. The tie is one row in the links
 * table: the tag, the kind of thing, and that thing's own id.
 *
 * Tags belong to a schedule, not to the app: two seasons can both have a
 * "harvest" tag without seeing each other's.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_tags')) {
            Schema::create('as_schedule_tags', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('croppingScheduleId')->index();
                $t->integer('userId');                    // who coined it
                $t->string('name', 60);
                $t->tinyInteger('deleteStatus')->default(1)->index();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('as_schedule_tag_links')) {
            Schema::create('as_schedule_tag_links', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('tagId')->index();
                $t->string('kind', 30);                   // activity | note | expense | income | drawing | map | media | move ...
                $t->unsignedBigInteger('refId');
                $t->timestamps();
                $t->unique(['tagId', 'kind', 'refId']);
                $t->index(['kind', 'refId']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_tag_links');
        Schema::dropIfExists('as_schedule_tags');
    }
};
