<?php

use App\Support\FieldCatalogue;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Pests and diseases, the second stage (the owner, 2026-10-19): after rice,
 * every other crop's pests and diseases get the active ingredients their
 * Philippine labels name, crop by crop and best first, the ones on labels but
 * not advised set apart with the reason, and what prevents them without a
 * spray (field practice, natural enemies, when it pays to act, the resistance
 * line, a virus's carrier). Some entries gain the crops their labels name
 * (thrips on cotton and potato, powdery mildew on mango and tomato, aphids on
 * citrus and others). The catalogue and the pest and disease pages that
 * changed are loaded again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_crop_problems')) {
            FieldCatalogue::sync();
        }
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
