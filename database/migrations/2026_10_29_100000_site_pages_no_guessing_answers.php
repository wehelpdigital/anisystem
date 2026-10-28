<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * "Will an app raise my yield?" answered the way the owner wants (2026-10-29: "no app can promise but AI is the most
 * accurate as you can get ... there's no guessing game"): four blog pages reworded, synced here. A page edited in the
 * mother app keeps its edits.
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
        // The words stay.
    }
};
