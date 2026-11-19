<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The signup asks who the member is (2026-11-19, the owner: "add a question if the user being registered is a farmer,
 * student, others ... saved to the user and in acumbamail so that we can segment later on").
 *
 * anisystem_users.occupation: farmer | student | other (User::OCCUPATIONS), null for accounts made before the question.
 * as_nl_audience.occupation: what Acumbamail was last given for that address, so a change is written onto every list
 * the address is on (App\Support\Newsletter\Audience).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_users') && ! Schema::hasColumn('anisystem_users', 'occupation')) {
            Schema::table('anisystem_users', function (Blueprint $t) {
                $t->string('occupation', 20)->nullable()->after('profession');
            });
        }
        if (Schema::hasTable('as_nl_audience') && ! Schema::hasColumn('as_nl_audience', 'occupation')) {
            Schema::table('as_nl_audience', function (Blueprint $t) {
                $t->string('occupation', 20)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_nl_audience', 'occupation')) {
            Schema::table('as_nl_audience', fn (Blueprint $t) => $t->dropColumn('occupation'));
        }
        if (Schema::hasColumn('anisystem_users', 'occupation')) {
            Schema::table('anisystem_users', fn (Blueprint $t) => $t->dropColumn('occupation'));
        }
    }
};
