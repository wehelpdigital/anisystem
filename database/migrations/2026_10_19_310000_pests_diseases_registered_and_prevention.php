<?php

use App\Support\FieldCatalogue;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pests and diseases, what really answers them here (the owner, 2026-10-19:
 * "check for the pests solutions ... in rice bug another solution is the
 * active ingredient in marshal insecticide but it is not indicated. also
 * cartap is a solution for rice folders, stem borers ... also ... how to
 * prevent these and add it to the solution").
 *
 * Every pest and disease now carries, crop by crop, the active ingredients
 * whose Philippine labels name it (from the registration list, best first,
 * with the ones on labels but not advised set apart), and what prevents it
 * without a spray: field practice, natural enemies, when it pays to act, the
 * resistance line, and for a virus the insect that carries it. Those fields
 * live in one JSON column, advice, which FieldCatalogue::sync() writes once
 * the column is there (any later sync migration too, whichever runs first).
 * The pest and disease pages that changed are loaded again as well.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_crop_problems') && ! Schema::hasColumn('as_crop_problems', 'advice')) {
            Schema::table('as_crop_problems', function (Blueprint $t) {
                $t->json('advice')->nullable()->after('ranks');
            });
        }
        if (Schema::hasTable('as_crop_problems')) {
            FieldCatalogue::sync();
        }
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        // The rows stay; the column stays with them.
    }
};
