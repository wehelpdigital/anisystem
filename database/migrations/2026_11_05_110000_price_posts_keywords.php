<?php

use App\Support\PriceHomes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The rest of the 2026-11-05 price fix: a dated roundup's keyword list still named the price searches its permanent page
 * now owns, and the page prints that list as article tags and in its schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        foreach (DB::table('as_site_pages')->where('section', 'blog')->where('kind', 'roundup')->whereNull('editedAt')->get(['id', 'keywords']) as $row) {
            $list = json_decode((string) $row->keywords, true);
            if (! is_array($list)) {
                continue;
            }
            $kept = array_values(array_filter($list, fn ($k) => ! PriceHomes::ownsPhrase((string) $k)));
            if (count($kept) !== count($list)) {
                DB::table('as_site_pages')->where('id', $row->id)->update(['keywords' => json_encode($kept, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        // The words stay.
    }
};
