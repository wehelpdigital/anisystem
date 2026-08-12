<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let an email template keep the blocks it was built from.
 *
 * The table already existed with a `bodyHtml` column that every sender reads,
 * and that stays the thing that gets sent. This is the editable form behind
 * it: the builder saves blocks and the HTML it rendered from them, so a
 * template can be reopened and rearranged instead of being hand-edited HTML
 * from then on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_email_templates', 'blocks')) {
            Schema::table('as_email_templates', function (Blueprint $table) {
                $table->json('blocks')->nullable()->after('bodyHtml');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_email_templates', 'blocks')) {
            Schema::table('as_email_templates', function (Blueprint $table) {
                $table->dropColumn('blocks');
            });
        }
    }
};
