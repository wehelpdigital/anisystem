<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Privacy page says, in general words, that anee.io notes which parts of it are opened (the owner, 2026-11-23,
 * on the admin Interests report and as_page_views: "yes, update the privacy page as well", then "but be vauge").
 *
 * One line goes in after the AI usage line of "What we collect as you use anee.io", and only where that line still
 * reads as written, so anything the owner wrote in the mother app stays as it is. The default file
 * resources/views/legal/defaults/privacy.html says the same.
 */
return new class extends Migration
{
    private const AFTER = '<li><strong>AI usage:</strong> how many credits each request used, so we can show your balance and keep our prices fair.</li>';

    private const LINE = '<li><strong>Pages and features used:</strong> which parts of anee.io are opened, so we can see what people find useful and make anee.io better.</li>';

    public function up(): void
    {
        if (! Schema::hasTable('as_legal_pages')) {
            return;
        }
        $row = DB::table('as_legal_pages')->where('slug', 'privacy')->first();
        if (! $row) {
            return;
        }
        $body = (string) $row->body;
        if (str_contains($body, 'Pages and features used:') || ! str_contains($body, self::AFTER)) {
            return;
        }
        $at = strpos($body, self::AFTER) + strlen(self::AFTER);
        $eol = str_contains($body, "\r\n") ? "\r\n" : "\n";
        $body = substr($body, 0, $at) . $eol . self::LINE . substr($body, $at);
        DB::table('as_legal_pages')->where('id', $row->id)->update(['body' => $body, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // The line stays; the row may hold the owner's own words by now.
    }
};
