<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The crop variety pages again (2026-10-19, the owner: no agency, state
 * university or research institute as our source): their research notes
 * no longer say "DA Region 02's ampalaya guide", "a DOST PCAARRD project",
 * "the Benguet State University trial" or "(UPLB study)", and an
 * unregistered variety "is not a registered variety". Pages edited in the
 * mother app keep their edits; see database/site-pages/README.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
    }

    public function down(): void
    {
        // The pages stay.
    }
};
