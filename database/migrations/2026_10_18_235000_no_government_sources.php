<?php

use App\Support\FieldCatalogue;
use App\Support\GovSource;
use App\Support\LandPrepPlanner;
use App\Support\ProductLibrary;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * No government body, state university or research institute shown as
 * anee.io's source (the owner, 2026-10-17: "never mention that our source is
 * any government sources like Philrice or so", and "remove any mentions of
 * IRRI and FAO as a source").
 *
 *  - The site pages (guides, posts, varieties, deficiencies, weeds, pests,
 *    diseases, land preparation, features) are loaded again from their files.
 *  - The field catalogue (pests, diseases, weeds, weed plans) and the
 *    product book likewise.
 *  - Saved analyses, farm reports and Anee's chat answers are said again in
 *    today's words: GovSource::scrubAll on their text, the sources lists
 *    without those bodies (a land prep plan through LandPrepPlanner::
 *    plainReport). Only rows that name one are touched; keys, numbers and
 *    addresses never change.
 *  - One queued newsletter email named the leaf color chart's makers.
 *
 * Down leaves everything as it is now: the old words are not put back.
 */
return new class extends Migration
{
    /** What a stored row must contain to be read at all. */
    private const LIKE = ['%philrice%', '%bswm%', '%pagasa%', '%bureau of%', '%department of agriculture%', '%nsic registry%', '%uplb%',
        '%pcaarrd%', '%irri%', '%fao%', '%knowledge bank%', '%palaycheck%', '%tnau%', '%noaa%', '%ndrrmc%', '% DA %', '%(DA%', '%"DA%'];

    public function up(): void
    {
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
        if (Schema::hasTable('as_crop_problems') && Schema::hasTable('as_weeds') && Schema::hasTable('as_weed_plans')) {
            FieldCatalogue::sync();
            FieldCatalogue::forget();
        }

        // The product book (a cocolisap note and a zinc oxide line named the PCA and IRRI).
        if (Schema::hasTable('as_products')) {
            ProductLibrary::sync();
        }

        $this->rewrite('as_plant_analyses', ['report'], fn ($r) => ($r->kind ?? '') === 'landprep');
        $this->rewrite('as_farm_reports', ['report', 'body']);
        $this->rewrite('anisystem_ai_messages', ['content']);

        // The leaf color chart has no maker (the owner, 2026-10-17).
        if (Schema::hasTable('as_nl_emails')) {
            $old = 'Kaya matagal nang binuo ng PhilRice at IRRI ang isang napakasimpleng kagamitan para dito:';
            $new = 'Kaya may napakasimpleng kagamitan para dito:';
            foreach (DB::table('as_nl_emails')->where('blocks', 'like', '%binuo ng PhilRice at IRRI%')->get(['id', 'blocks']) as $e) {
                $blocks = json_decode((string) $e->blocks, true);
                if (! is_array($blocks)) {
                    continue;
                }
                array_walk_recursive($blocks, function (&$v) use ($old, $new) {
                    if (is_string($v)) {
                        $v = str_replace($old, $new, $v);
                    }
                });
                DB::table('as_nl_emails')->where('id', $e->id)->update(['blocks' => json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        }
    }

    public function down(): void
    {
        // The words stay as they are now.
    }

    /**
     * Each row of $table naming one of them, its $cols said again. A JSON
     * column is walked (GovSource::scrubAll), a text column scrubbed whole.
     * $plan picks the rows that are land prep plans.
     */
    private function rewrite(string $table, array $cols, ?Closure $plan = null): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $cols = array_values(array_filter($cols, fn ($c) => Schema::hasColumn($table, $c)));
        if (! $cols) {
            return;
        }
        $q = DB::table($table)->where(function ($w) use ($cols) {
            foreach ($cols as $c) {
                foreach (self::LIKE as $l) {
                    $w->orWhereRaw('LOWER(`' . $c . '`) LIKE ?', [$l]);
                }
            }
        });
        $select = array_merge(['id'], $cols, Schema::hasColumn($table, 'kind') ? ['kind'] : []);
        foreach ($q->get($select) as $r) {
            $set = [];
            foreach ($cols as $c) {
                $raw = (string) ($r->{$c} ?? '');
                if ($raw === '') {
                    continue;
                }
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    $clean = $plan && $plan($r) ? LandPrepPlanner::plainReport($data) : GovSource::scrubAll($data);
                    if ($clean !== $data) {
                        $set[$c] = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } else {
                    $clean = GovSource::scrub($raw);
                    if ($clean !== $raw) {
                        $set[$c] = $clean;
                    }
                }
            }
            if ($set) {
                DB::table($table)->where('id', $r->id)->update($set);
            }
        }
    }
};
