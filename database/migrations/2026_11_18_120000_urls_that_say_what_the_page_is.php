<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The 2026-11-18 URL audit ("poorly formatted URL": the address shares no keyword with the page's title).
 *
 * The dated farm news roundups move to addresses named for their news (SitePages::MOVED keeps the old ones as 301s):
 * their old addresses still chased the palay, rice and fertilizer price searches the permanent price pages own,
 * though 2026_11_05_100000 had already renamed the pages themselves.
 *
 * sync(): the How to Plant pages whose Tagalog title shared no word with their English address get the English name
 * in their SEO title too ("Paano Magtanim ng Abaka: How to Plant Abaca, Step by Step").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        foreach (SitePages::MOVED as $from => $to) {
            if (! str_starts_with($from, 'blog/') || ! str_starts_with($to, 'blog/')) {
                continue;
            }
            $old = substr($from, 5);
            $new = substr($to, 5);
            $row = DB::table('as_site_pages')->where('section', 'blog')->where('slug', $old)->where('kind', 'roundup')->first(['id']);
            if (! $row || DB::table('as_site_pages')->where('section', 'blog')->where('slug', $new)->exists()) {
                continue;
            }
            DB::table('as_site_pages')->where('id', $row->id)->update(['slug' => $new, 'updated_at' => now()]);
        }

        SitePages::sync();
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The new addresses stay; the old ones answer with a 301 either way.
    }
};
