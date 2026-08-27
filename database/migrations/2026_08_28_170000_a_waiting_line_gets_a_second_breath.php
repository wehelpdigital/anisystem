<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The second line under the joke.
 *
 * One sentence on a loading card is a caption. Two are a voice — the first
 * says what the farm is doing, the second is the aside somebody would
 * actually add: "Waking the carabao… / He heard you. He is thinking about
 * it." The pair is what makes the card feel written rather than generated,
 * and it fills the space a spinner used to waste.
 *
 * Nullable, because a line is still a line without one, and because the
 * mother site should be able to add a bare one and think of the second half
 * later.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_loading_lines')) {
            return;
        }
        Schema::table('as_loading_lines', function (Blueprint $t) {
            if (! Schema::hasColumn('as_loading_lines', 'subline')) {
                $t->string('subline', 160)->nullable()->after('line');
            }
        });

        (new \Database\Seeders\LoadingLineSeeder)->run();
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_loading_lines')) {
            return;
        }
        Schema::table('as_loading_lines', function (Blueprint $t) {
            if (Schema::hasColumn('as_loading_lines', 'subline')) {
                $t->dropColumn('subline');
            }
        });
    }
};
