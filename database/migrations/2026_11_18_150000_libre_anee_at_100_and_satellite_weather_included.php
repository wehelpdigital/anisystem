<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18, the owner: "change our pricing of anee plus to 100php per month ... also remove the subscription in the
 * satellite weather feature. it should be included now in the anee plus".
 *
 * - Libre + Anee is ₱100 a month (₱1,000 a year) in config/tiers.php; here, the sold plan row and its shop variant.
 * - The newsletters still waiting to go out (queued and drafts) say the new price. Only the plan's price phrases
 *   change: a market price such as "₱70 kada kilo" in a story is left alone. The arithmetic around them still holds
 *   (every "costs more than a year of Libre + Anee" example is over ₱1,000).
 * - Satellite Weather's week pass is gone: no pass renews again (the table stays as history).
 * - sync(): the guides and feature pages with the new price and Satellite Weather in the plan.
 */
return new class extends Migration
{
    /** [pattern, replacement] on the words of a newsletter, in this order. */
    private const PHRASES = [
        ['/₱700 sa isang taon kung saan nakakatipid ka ng ₱140/u', '₱1,000 sa isang taon kung saan nakakatipid ka ng ₱200'],
        ['/sa ₱700, ang isang buong taon ng Libre \+ Anee/u', 'sa ₱1,000, ang isang buong taon ng Libre + Anee'],
        ['/ng Libre \+ Anee ay ₱700 lang, o ₱70 bawat buwan/u', 'ng Libre + Anee ay ₱1,000 lang, o ₱100 bawat buwan'],
        ['/₱700 (a year|kada taon|sa isang taon)/u', '₱1,000 $1'],
        ['/₱70 (a month|kada buwan|bawat buwan|isang buwan)/u', '₱100 $1'],
        ['/Ang ₱70 na binayaran mo sa isang buwan/u', 'Ang ₱100 na binayaran mo sa isang buwan'],
        ['/"₱70 lang yan/u', '"₱100 lang yan'],
        ['/nakabawi ba ang ₱70 mo/u', 'nakabawi ba ang ₱100 mo'],
    ];

    public function up(): void
    {
        if (Schema::hasTable('anisystem_plans')) {
            DB::table('anisystem_plans')->where('planKey', 'libre-anee')->where('price', 70)->update(['price' => 100, 'updated_at' => now()]);
            $variant = DB::table('anisystem_plans')->where('planKey', 'libre-anee')->value('ecomVariantId');
            if ($variant && Schema::hasTable('ecom_products_variants')) {
                DB::table('ecom_products_variants')->where('id', $variant)->where('ecomVariantPrice', 70)->update(['ecomVariantPrice' => 100, 'updated_at' => now()]);
            }
        }

        if (Schema::hasTable('as_nl_emails')) {
            $fix = function (string $s): string {
                foreach (self::PHRASES as [$p, $r]) {
                    $s = (string) preg_replace($p, $r, $s);
                }

                return $s;
            };
            $rows = DB::table('as_nl_emails')->whereIn('status', ['queued', 'draft'])->where('deleteStatus', 1)->get(['id', 'subject', 'preheader', 'blocks']);
            foreach ($rows as $r) {
                $blocks = json_decode((string) $r->blocks, true);
                if (! is_array($blocks)) {
                    continue;
                }
                array_walk_recursive($blocks, function (&$v) use ($fix) {
                    if (is_string($v)) {
                        $v = $fix($v);
                    }
                });
                $new = [
                    'subject' => $fix((string) $r->subject),
                    'preheader' => $r->preheader === null ? null : $fix((string) $r->preheader),
                    'blocks' => json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                if ($new['subject'] !== (string) $r->subject || $new['preheader'] !== $r->preheader
                    || json_decode($new['blocks'], true) !== json_decode((string) $r->blocks, true)) {
                    DB::table('as_nl_emails')->where('id', $r->id)->update($new + ['updated_at' => now()]);
                }
            }
        }

        if (Schema::hasTable('as_sky_passes')) {
            DB::table('as_sky_passes')->where('autoRenew', 1)->update(['autoRenew' => 0, 'renewNote' => 'Satellite Weather is part of the plans since 2026-11-18: no pass renews.', 'updated_at' => now()]);
        }

        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
            SitePages::forgetCaches();
        }
    }

    public function down(): void
    {
        // The new price stays.
    }
};
