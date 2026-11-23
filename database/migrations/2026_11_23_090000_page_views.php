<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every page a person opens, one row each (2026-11-23, the owner: "in the admin panel, i wanna have the option to view
 * what users opened (pages) to know what makes them interested and a report module as well to have a general
 * understanding of what they are interestred with").
 *
 * Guests are known by the anee_vid cookie and members by their account; a guest's rows pass to the account they sign
 * up with. Each row is sorted when it is written: the site section or the app tool, the page, and the crop the page is
 * about, so the admin panel's Interests report only has to count. App\Support\PageViews writes it; guest rows go
 * after 180 days and every row after 400 (PageViews::prune).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_page_views')) {
            Schema::create('as_page_views', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId')->nullable();
                $table->string('vid', 40)->nullable()->index();
                $table->string('area', 8);                       // site | app
                $table->string('kind', 40);                      // the site section (pests, blog...) or app
                $table->string('topic', 120)->nullable();        // the page's slug, or the app tool (npk-plus, sm:notes)
                $table->string('crop', 40)->nullable();          // the crop the page is about, when it names one
                $table->string('path', 191);                     // ids and tokens folded to :id
                $table->string('query', 191)->nullable();        // the kept keys only (q, crop, tab, module, read, plan)
                $table->string('search', 120)->nullable();       // what they typed in a search box (?q=)
                $table->string('title', 160)->nullable();
                $table->string('referrer', 80)->nullable();      // the other site that sent them
                $table->string('device', 12)->nullable();        // Phone | Tablet | Computer
                $table->dateTime('viewedAt')->index();
                $table->index(['userId', 'viewedAt']);
                $table->index(['kind', 'viewedAt']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_page_views');
    }
};
