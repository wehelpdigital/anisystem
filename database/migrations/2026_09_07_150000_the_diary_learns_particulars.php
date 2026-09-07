<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The diary of hands learns particulars: which thing, and what changed —
 * field by field, from and to — kept as JSON beside the one-line label.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_schedule_audits', 'detail')) {
            return;
        }

        Schema::table('as_schedule_audits', function (Blueprint $table) {
            $table->mediumText('detail')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_schedule_audits', 'detail')) {
            return;
        }

        Schema::table('as_schedule_audits', function (Blueprint $table) {
            $table->dropColumn('detail');
        });
    }
};
