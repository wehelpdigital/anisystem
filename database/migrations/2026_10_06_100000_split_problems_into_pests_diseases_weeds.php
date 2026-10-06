<?php

use App\Models\AsSitePage;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * "Crop problems" becomes three sections (2026-10-06): Crop Pests (/pests),
 * Crop Diseases (/diseases) and Weeds and Grasses (/weeds), the last with a
 * catalogue of 81 rice weeds and three new guides (database/site-pages/weeds).
 *
 * The 14 old problem pages move to their new section first, so the sync
 * below finds them instead of making copies. Every page's own links to
 * /problems/{slug}, edited ones included, are pointed at the new address
 * (the old address still answers with a 301). Then the shipped files load.
 */
return new class extends Migration
{
    private const MOVES = [
        'rice-bug' => 'pests', 'rice-black-bug' => 'pests', 'brown-planthopper' => 'pests', 'rice-insects' => 'pests',
        'rice-leaffolder' => 'pests', 'thrips' => 'pests', 'fall-armyworm' => 'pests', 'asian-corn-borer' => 'pests',
        'cutworm' => 'pests', 'hanip-mites-and-aphids' => 'pests',
        'anthracnose' => 'diseases', 'fusarium-wilt' => 'diseases', 'sheath-blight' => 'diseases',
        'common-weeds-philippines' => 'weeds',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        foreach (self::MOVES as $slug => $section) {
            // A copy already in the new section (a second run) wins; the old row goes.
            if (AsSitePage::where('section', $section)->where('slug', $slug)->exists()) {
                AsSitePage::where('section', 'problems')->where('slug', $slug)->delete();
                continue;
            }
            AsSitePage::where('section', 'problems')->where('slug', $slug)->update(['section' => $section]);
        }

        $relink = fn (?string $s) => $s === null ? null : preg_replace_callback('#/problems/([a-z0-9\-]+)#',
            fn ($m) => isset(self::MOVES[$m[1]]) ? '/' . self::MOVES[$m[1]] . '/' . $m[1] : $m[0], $s);
        AsSitePage::query()->where(fn ($q) => $q->where('blocks', 'like', '%problems%')->orWhere('excerpt', 'like', '%problems%'))
            ->get()->each(function (AsSitePage $p) use ($relink) {
                $blocks = json_decode($relink(json_encode($p->blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), true);
                $p->forceFill(['blocks' => is_array($blocks) ? $blocks : $p->blocks, 'excerpt' => $relink($p->excerpt)])->saveQuietly();
            });

        SitePages::sync();
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The pages stay where they are; /problems/{slug} keeps redirecting.
    }
};
