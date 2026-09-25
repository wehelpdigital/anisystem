<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every email anee.io sends takes the new design, and each template keeps the
 * version we shipped beside the one that is sent.
 *
 * `defaultSubject` / `defaultBodyHtml` are how the seeder tells an owner's
 * edit from our own words (a body equal to the default was never touched, so
 * a new design may replace it), and what the mother app's editor restores
 * when the owner asks for the anee.io design back.
 *
 * Two emails that were written in PHP join the rest in the editor: the
 * sign-up confirmation (email_verification) and the support desk's reply
 * (support_reply).
 *
 * Safe to re-run; nothing an owner has written is overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_email_templates')) {
            return;
        }

        if (! Schema::hasColumn('as_email_templates', 'defaultSubject')) {
            Schema::table('as_email_templates', function (Blueprint $t) {
                $t->string('defaultSubject', 500)->nullable()->after('availableTags');
            });
        }
        if (! Schema::hasColumn('as_email_templates', 'defaultBodyHtml')) {
            Schema::table('as_email_templates', function (Blueprint $t) {
                $t->longText('defaultBodyHtml')->nullable()->after('defaultSubject');
            });
        }

        (new \Database\Seeders\AniSystemEmailTemplateSeeder)->run();
    }

    public function down(): void
    {
        // Templates are content, and the columns hold what "restore" restores.
        // A rollback of the code has no business deleting either.
    }
};
