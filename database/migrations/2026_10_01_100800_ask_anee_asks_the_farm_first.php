<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Try and Ask Anee asks for the farm first (2026-10-01, the owner's word):
 * the visitor's name joins the row, and the answer email greets them by it
 * (the ask_anee_answer template gains {{firstName}}).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_ask_questions') && ! Schema::hasColumn('as_ask_questions', 'name')) {
            Schema::table('as_ask_questions', function (Blueprint $t) {
                $t->string('name', 120)->nullable()->after('token');
            });
        }
        if (Schema::hasTable('as_email_templates')) {
            (new \Database\Seeders\AniSystemEmailTemplateSeeder())->run();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_ask_questions', 'name')) {
            Schema::table('as_ask_questions', fn (Blueprint $t) => $t->dropColumn('name'));
        }
    }
};
