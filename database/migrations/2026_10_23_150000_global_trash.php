<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The global Trash (2026-10-23, the owner: "have a global trash module. now
 * all deleted items will go to trash and will be permanently delete after 30
 * days").
 *
 * The app already deletes by setting deleteStatus to 0; this is the book of
 * those deletions: what was deleted (a table and a row), whose it is (the
 * season's owner for the season's things), its name, when and by whom.
 * App\Support\Trash writes it (the models' update event, and a sweep for the
 * deletes written without one), restores from it, and empties it 30 days on.
 *
 * Rows deleted before today are filed as `legacy`: never shown and never
 * emptied, so nothing a farmer deleted long ago is taken away by surprise.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_trash')) {
            Schema::create('as_trash', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ownerId')->index();
                $t->string('kind', 30);
                $t->string('tableName', 64);
                $t->unsignedBigInteger('rowId');
                $t->string('title', 255)->nullable();
                $t->string('subtitle', 255)->nullable();
                $t->unsignedBigInteger('scheduleId')->nullable()->index();
                $t->string('parentTable', 64)->nullable();
                $t->unsignedBigInteger('parentId')->nullable();
                $t->unsignedBigInteger('deletedBy')->nullable();
                $t->dateTime('deletedAt')->index();
                $t->string('state', 10)->default('trash');
                $t->timestamps();
                $t->unique(['tableName', 'rowId']);
            });
        }
        // What was already deleted is filed as legacy, so only deletes from now on reach the Trash.
        \App\Support\Trash::fileLegacy();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_trash');
    }
};
