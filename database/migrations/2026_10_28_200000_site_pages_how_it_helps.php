<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Every feature page gains a section on how the tool helps the farmer and the farm, four points right before its call
 * to action (the owner, 2026-10-28: "in all pages explaining each feature, also add a section in the content about how
 * it helps you and your farm for each"). Synced here; a page edited in the mother app keeps its edits.
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
        // The new sections stay.
    }
};
