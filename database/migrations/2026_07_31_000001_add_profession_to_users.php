<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member's role in agriculture (farm owner, agriculturist, farm worker, …)
 * shown as a chip on their community profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('anisystem_users', 'profession')) {
            return;
        }
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->string('profession', 60)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('profession');
        });
    }
};
