<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A number a person can quote.
 *
 * "My ticket about the missing photos" is not something a support desk can
 * look up. AS-2026-0007 is. Existing tickets are numbered in the order they
 * were raised, so nothing that already exists is left without one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_support_tickets', 'ticketNumber')) {
            Schema::table('as_support_tickets', function (Blueprint $table) {
                $table->string('ticketNumber', 20)->nullable()->after('id')->index();
            });
        }

        $byYear = [];
        foreach (DB::table('as_support_tickets')->orderBy('id')->get(['id', 'created_at', 'ticketNumber']) as $t) {
            if (filled($t->ticketNumber)) {
                continue;
            }
            $year = $t->created_at ? date('Y', strtotime($t->created_at)) : date('Y');
            $byYear[$year] = ($byYear[$year] ?? 0) + 1;
            DB::table('as_support_tickets')->where('id', $t->id)
                ->update(['ticketNumber' => sprintf('AS-%s-%04d', $year, $byYear[$year])]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_support_tickets', 'ticketNumber')) {
            Schema::table('as_support_tickets', function (Blueprint $table) {
                $table->dropColumn('ticketNumber');
            });
        }
    }
};
