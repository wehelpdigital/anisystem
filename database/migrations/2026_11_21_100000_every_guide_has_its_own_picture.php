<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The owner, 2026-11-21: "in the blog and the contents, some images are used repeatedly because our pool of images
 * are lacking. can you add more images and update the images on these contents to make it more unique for each
 * article". 79 written guides (blog, crops, features, nutrients, diseases, pests, weeds) that showed another page's
 * hero get a photo of their own from Wikimedia Commons, Flickr or Rawpixel (free licences only, credited on the page,
 * the file's own page kept in picture-rights.json), at /images/site/guides/{section}-{slug}.webp with -800 and -480
 * copies. /pests/rice-bug keeps its rice bug; /blog/atangya-sa-palay, which borrowed it, has its own now.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The pictures stay.
    }
};
