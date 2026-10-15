<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global Lots (2026-10-16, the owner): a saved field profile becomes the
 * farm's lot, with where it is (province, town or city, barangay), its
 * size, and the day the bell last asked to check it (every six months,
 * App\Support\FarmLots::remind). Nullable and additive: a profile saved
 * before simply has no place yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_field_profiles') || Schema::hasColumn('as_field_profiles', 'barangay')) {
            return;
        }
        Schema::table('as_field_profiles', function (Blueprint $t) {
            $t->string('province', 120)->nullable()->after('name');
            $t->string('town', 120)->nullable()->after('province');
            $t->string('barangay', 160)->nullable()->after('town');
            $t->decimal('areaHa', 12, 4)->nullable()->after('barangay');
            $t->timestamp('staleNotifiedAt')->nullable()->after('deleteStatus');
            $t->index(['deleteStatus', 'updated_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_field_profiles') || ! Schema::hasColumn('as_field_profiles', 'barangay')) {
            return;
        }
        Schema::table('as_field_profiles', function (Blueprint $t) {
            $t->dropIndex(['deleteStatus', 'updated_at']);
            $t->dropColumn(['province', 'town', 'barangay', 'areaHa', 'staleNotifiedAt']);
        });
    }
};
