<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The email frame keeps its own colors where an inbox would make a link of "anee.io" (2026-11-19, the owner: "in the
 * intro email header, the font of anee.io link in Welcome sa anee.io, vetdent is blue, please fix"). App\Support\
 * EmailSkin now writes the masthead's "anee.io" as its own white link and the sign-off's as a dark one; the stored
 * templates are built whole, so they are built again. The seeder only replaces a body nobody has edited in the
 * mother app's email editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_email_templates')) {
            (new \Database\Seeders\AniSystemEmailTemplateSeeder)->run();
        }
    }

    public function down(): void
    {
        // The frame stays fixed.
    }
};
