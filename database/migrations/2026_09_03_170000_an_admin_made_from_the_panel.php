<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin hat the panel itself can grant.
 *
 * adminUserId stays what it has always been — the link to a mother-site
 * admin row, positive and unsigned, the SuperAdminBridge's key. A panel-made
 * admin is its own fact instead of a sentinel squeezed into that column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->unsignedTinyInteger('panelAdmin')->default(0)->after('adminUserId');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('panelAdmin');
        });
    }
};
