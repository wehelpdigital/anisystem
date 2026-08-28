<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anee, in the community, under her own name.
 *
 * Her account there was called "Agricultural AI Technician" — a job title
 * standing in for a person, which is what you write when the thing answering
 * has not been given a name yet. She has one, it is on the chat header, the
 * floating button and the collab tab, and a farmer who has been talking to
 * Anee all week should not meet a stranger in the feed.
 *
 * So the name is the name and the title goes where every other member's job
 * goes: the small print under it. A post of hers now reads
 *
 *     Anee
 *     Agricultural Smart Technician
 *
 * which is the same thing the header says, said the way the community says
 * everything else.
 *
 * Her face is not set here. The avatar partial reads it from the AI settings,
 * because that is where the app keeps it and it is the same file the chat
 * already wears.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The model's own table, not a guess: this is a mother-app table and
        // carries no `as_` prefix, and a hasTable guard on a wrong name is a
        // migration that says DONE and does nothing.
        $table = (new \App\Models\User)->getTable();
        if (! Schema::hasTable($table)) {
            return;
        }

        $set = ['firstName' => 'Anee', 'lastName' => ''];
        foreach (['profession', 'headline'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $set[$column] = 'Agricultural Smart Technician';
            }
        }

        DB::table($table)
            ->whereRaw('LOWER(email) = ?', [strtolower(\App\Models\User::ASSISTANT_EMAIL)])
            ->update($set);
    }

    public function down(): void
    {
        // Nothing. Putting "Agricultural AI Technician" back over a name is
        // not an improvement anybody would want rolled forward to.
    }
};
