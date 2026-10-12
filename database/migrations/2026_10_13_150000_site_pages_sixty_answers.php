<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Sixty answer posts for Latest in Agriculture (2026-10-13): the questions
 * farmers, and the AI assistants that answer them, ask about planning,
 * yield, fertilizer, crop health, money, weather and farm apps. Also: three
 * feature pages and the fertilizer for rice post corrected (offline mode is
 * on every plan; the leaf color chart cutoff for direct wet seeded rice is
 * 3). Pages edited in the mother app keep their edits; see
 * database/site-pages/README.md.
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
