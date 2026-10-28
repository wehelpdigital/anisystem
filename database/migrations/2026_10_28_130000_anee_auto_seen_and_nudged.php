<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anee Auto misses the farmer (2026-10-28, the owner: "if user is not active in the anee auto, user should be
 * receiving email asking user reminder (have this email template in the mother app editable), and a notice in the
 * dashboard"): when the farmer last opened or answered a running season (seenAt), and when Anee last asked them
 * back (nudgedAt). The reminder's template (auto_inactive) joins the ones the mother app edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_auto_seasons')) {
            Schema::table('as_auto_seasons', function (Blueprint $t) {
                if (! Schema::hasColumn('as_auto_seasons', 'seenAt')) {
                    $t->dateTime('seenAt')->nullable()->after('emailDate');
                }
                if (! Schema::hasColumn('as_auto_seasons', 'nudgedAt')) {
                    $t->dateTime('nudgedAt')->nullable()->after('seenAt');
                }
            });
        }
        if (Schema::hasTable('as_email_templates')) {
            (new \Database\Seeders\AniSystemEmailTemplateSeeder)->run();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('as_auto_seasons')) {
            Schema::table('as_auto_seasons', function (Blueprint $t) {
                foreach (['nudgedAt', 'seenAt'] as $c) {
                    if (Schema::hasColumn('as_auto_seasons', $c)) {
                        $t->dropColumn($c);
                    }
                }
            });
        }
    }
};
