<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rules & notes as many entries (2026-10-24, the owner: "rules and notes
 * should be not just one wysiwyg but user can add multiple with either
 * uploaded file or wysiwyg"). `notes` keeps the entries in their order:
 * written ones with their title and words, file ones pointing at the
 * version's `files`. `rules` stays, written from the entries on every save,
 * so everything that reads it (a season's Notes, the plan ports) still
 * does. A version without `notes` reads its old document and files as
 * entries (App\Support\ProtocolNotes), so nothing is moved here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_protocol_versions') && ! Schema::hasColumn('as_protocol_versions', 'notes')) {
            Schema::table('as_protocol_versions', function (Blueprint $t) {
                $t->json('notes')->nullable()->after('files');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_protocol_versions', 'notes')) {
            Schema::table('as_protocol_versions', function (Blueprint $t) {
                $t->dropColumn('notes');
            });
        }
    }
};
