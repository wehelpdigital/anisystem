<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Four emails that were written in PHP get templates like everything else.
 *
 * The worker invite and the farm-access note were built as string literals
 * inside a controller, so nobody could change a word of them without a
 * deploy — while every other message this app sends has been editable in the
 * mother app for months. The two new ones (a day's work, and one activity,
 * both sent by hand off the board) join them there rather than starting the
 * same way.
 *
 * Safe to re-run: the seeder adds what is missing and leaves anything an
 * owner has edited alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_email_templates')) {
            return;
        }
        (new \Database\Seeders\anee.ioEmailTemplateSeeder)->run();
    }

    public function down(): void
    {
        // Templates are content. A rollback of the code has no business
        // deleting something somebody may have spent an afternoon wording.
    }
};
