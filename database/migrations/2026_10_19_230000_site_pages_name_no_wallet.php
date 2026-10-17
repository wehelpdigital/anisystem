<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * No wallet is named as how anee.io is paid (the owner, 2026-10-17: "remove
 * anything specifying gcash in all payment gateways or pricing texts").
 * Twelve blog posts said plans are paid "by GCash or bank transfer"; the
 * seeds now say "from any bank or wallet app", which is what the QR (a QR
 * Ph code) takes, and the marketplace guide's payment terms no longer name
 * one wallet either. Synced here; a page edited in the mother app keeps its
 * edits (none of the twelve was). The Technician's Blog articles follow.
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
        // The new words stay.
    }
};
