<?php

use Illuminate\Database\Migrations\Migration;

/**
 * A first page behind the question marks that have just appeared on Home,
 * Community and Account.
 *
 * The icon shipped with the schedule modules and stayed there. It now covers
 * the three screens people actually live on, plus each Community page — which
 * are separate places with separate rules, so "how to use Community" as one
 * page would have to be so general as to say nothing.
 *
 * The seeder only writes pages that do not exist, so this can be re-run and
 * so a page corrected in the mother app's builder is never overwritten by a
 * deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\OuterHelpPageSeeder)->run();
    }

    public function down(): void
    {
        // Deliberately nothing. These are content rows; somebody may have
        // edited them by now, and a rollback of a schema change has no
        // business deleting what a person wrote.
    }
};
