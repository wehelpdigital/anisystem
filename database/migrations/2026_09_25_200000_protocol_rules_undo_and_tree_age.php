<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROTOCOL BUILDER — two small additions.
 *
 * `as_protocol_versions.rulesHistory`: the rules-and-notes document's own
 * undo/redo, {"undo":[{html,at}],"redo":[...],"pushedAt":…}, kept apart
 * from the tasks' `history` so the document's autosave and the tasks'
 * autosave never write over each other's steps.
 *
 * `as_protocols.treeAgeMonths`: for a protocol on mature trees, how old the
 * trees are on DOS 0 — optional; with it the task rail can name the stage
 * each DOS day falls in, and the port starts the trees' age from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_protocol_versions') && ! Schema::hasColumn('as_protocol_versions', 'rulesHistory')) {
            Schema::table('as_protocol_versions', function (Blueprint $t) {
                $t->mediumText('rulesHistory')->nullable()->after('history');
            });
        }
        if (Schema::hasTable('as_protocols') && ! Schema::hasColumn('as_protocols', 'treeAgeMonths')) {
            Schema::table('as_protocols', function (Blueprint $t) {
                $t->unsignedSmallInteger('treeAgeMonths')->nullable()->after('dayType');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_protocol_versions', 'rulesHistory')) {
            Schema::table('as_protocol_versions', fn (Blueprint $t) => $t->dropColumn('rulesHistory'));
        }
        if (Schema::hasColumn('as_protocols', 'treeAgeMonths')) {
            Schema::table('as_protocols', fn (Blueprint $t) => $t->dropColumn('treeAgeMonths'));
        }
    }
};
