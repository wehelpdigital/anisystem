<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profile extras: a short one-line "headline" and a cover photo. Plus
 * `adminUserId`, which links an anisystem account to a shared mother-site
 * `users` (super-admin) record so those admins can sign in here as a normal
 * member without a second account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            if (! Schema::hasColumn('anisystem_users', 'headline')) {
                $table->string('headline', 120)->nullable()->after('bio');
            }
            if (! Schema::hasColumn('anisystem_users', 'coverPath')) {
                $table->string('coverPath', 255)->nullable()->after('avatarPath');
            }
            if (! Schema::hasColumn('anisystem_users', 'adminUserId')) {
                $table->unsignedBigInteger('adminUserId')->nullable()->after('clientId');
                $table->index('adminUserId');
            }
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            if (Schema::hasColumn('anisystem_users', 'adminUserId')) {
                $table->dropIndex(['adminUserId']);
                $table->dropColumn('adminUserId');
            }
            foreach (['headline', 'coverPath'] as $col) {
                if (Schema::hasColumn('anisystem_users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
