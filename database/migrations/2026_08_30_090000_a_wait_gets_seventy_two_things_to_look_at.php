<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Re-file the reminders against the drawings that match their words.
 *
 * A hundred and fifty-seven reminders were sharing sixteen pictures, so the
 * same one came round about every tenth wait and the card read as one
 * animation with the text changed. There are seventy-two pictures now, and
 * the seeder points each line at the one its own words are about — gloves for
 * the glove reminder, a snail for the snail one, a bolo for the bolo.
 *
 * The lines themselves are unchanged; only which picture each carries. The
 * seeder is idempotent and retires rather than deletes, so this is safe to
 * run over an installation whose lines have already been edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\LoadingLineSeeder)->run();
    }

    public function down(): void
    {
        // Nothing. The rows are content and the change is which picture they
        // point at; there is nothing here worth undoing and a rollback has no
        // business rewriting content either way.
    }
};
