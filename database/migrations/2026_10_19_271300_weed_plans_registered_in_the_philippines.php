<?php

use App\Support\FieldCatalogue;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The weed plans checked against the Philippine registrations (2026-10-18,
 * the owner: research every solution, how to prevent them, "and so on",
 * "prioritize on local companies in ph").
 *
 *  - database/field-catalogue/weed-plans.json: every ingredient of every
 *    window looked up in the products registered for sale here, crop by
 *    crop. Each window now carries `on` (name => the plan's crops a current
 *    registration names), `abroad` (no product registered here) and `only`
 *    (the crops a name is meant for), read through FieldCatalogue::forCrop().
 *    Added: the registered ones that were missing (tetflupyrolimet,
 *    propyrisulfuron, imazosulfuron, carfentrazone, thiobencarb mixes,
 *    metamifop mixes for rice; tembotrione, tolpyralate, topramezone with
 *    atrazine, isoxaflutole with thiencarbazone for corn; sulfentrazone,
 *    hexazinone with diuron, indaziflam, flumioxazin, imazapic, flazasulfuron
 *    for sugarcane; and more). Dropped: paraquat (restricted to
 *    institutional use, no product registered), nicosulfuron, mesotrione and
 *    topramezone alone, atrazine with S metolachlor, imazethapyr,
 *    metsulfuron alone, quizalofop where a registered grass killer covers,
 *    and two rice ones whose registrations lapsed. A name with no product
 *    registered here stays only where nothing registered covers that window,
 *    and the helper says so. Lists put what Philippine companies sell first.
 *  - Each plan gains `prevent` (land preparation, the stale seedbed, water,
 *    clean seed, mulch, cover crops) and `critical` (each crop's weed free
 *    stretch); two new columns hold them.
 *  - database/field-catalogue/weeds.json: each weed gains `stop` (how to keep
 *    it out without a spray) and `named` (the active ingredients its
 *    Philippine labels name, by crop); two new columns hold them. Sources
 *    naming an agency, institute or university left the file.
 *  - The /weeds pages: the rice age tables follow the new lists, the guides
 *    name active ingredients only (no brand, no rate) and no university as a
 *    source.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_weed_plans')) {
            Schema::table('as_weed_plans', function (Blueprint $t) {
                if (! Schema::hasColumn('as_weed_plans', 'prevent')) {
                    $t->json('prevent')->nullable();
                }
                if (! Schema::hasColumn('as_weed_plans', 'critical')) {
                    $t->json('critical')->nullable();
                }
            });
        }
        if (Schema::hasTable('as_weeds')) {
            Schema::table('as_weeds', function (Blueprint $t) {
                if (! Schema::hasColumn('as_weeds', 'stop')) {
                    $t->text('stop')->nullable();
                }
                if (! Schema::hasColumn('as_weeds', 'named')) {
                    $t->json('named')->nullable();
                }
            });
        }
        if (Schema::hasTable('as_crop_problems') && Schema::hasTable('as_weeds') && Schema::hasTable('as_weed_plans')) {
            FieldCatalogue::sync();
        }
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        foreach (['as_weed_plans' => ['prevent', 'critical'], 'as_weeds' => ['stop', 'named']] as $table => $cols) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $drop = array_values(array_filter($cols, fn ($c) => Schema::hasColumn($table, $c)));
            if ($drop) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($drop));
            }
        }
        FieldCatalogue::forget();
    }
};
