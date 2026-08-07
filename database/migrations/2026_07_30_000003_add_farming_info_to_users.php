<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Farming profile fields so co-farmers can see each other's experience and
 * what they grow — years farming, farm size, crops, and method.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('anisystem_users', 'yearsFarming')) {
            return;
        }
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->unsignedSmallInteger('yearsFarming')->nullable()->after('bio');
            $table->string('farmSize', 60)->nullable()->after('yearsFarming');
            $table->string('cropsGrown', 255)->nullable()->after('farmSize');
            $table->string('farmingMethod', 60)->nullable()->after('cropsGrown');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn(['yearsFarming', 'farmSize', 'cropsGrown', 'farmingMethod']);
        });
    }
};
