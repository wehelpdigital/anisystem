<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The welcome emails (2026-11-16, the owner: "after registration, there's no welcome to anee email being sent, do
 * that and make sure have the email content to explain what is anee.io, make it welcoming, persuasive"). The new
 * member's welcome is rewritten in Anee's voice, saying what anee.io is, what Libre opens and what Libre + Anee adds;
 * a worker who sets up their login from an invite gets a welcome of their own (worker_welcome). The seeder only
 * replaces a body nobody has edited in the mother app's email editor.
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
        // The words stay: an older welcome is not worth bringing back.
    }
};
