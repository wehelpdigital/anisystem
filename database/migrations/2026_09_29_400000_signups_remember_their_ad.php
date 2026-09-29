<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which ad brought each signup (2026-09-29). The landing page's ads carry
 * utm_* tags (and Facebook's fbclid, Google's gclid); the visitor's session
 * keeps them from the first page to the signup, and the new account's row
 * here keeps them for good. The mother app counts them by campaign on its
 * Landing page editor. One row per account, only when tags came with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_signup_sources')) {
            Schema::create('as_signup_sources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId')->index();
                $table->string('source', 60)->nullable()->index();
                $table->string('medium', 60)->nullable();
                $table->string('campaign', 120)->nullable();
                $table->string('content', 120)->nullable();
                $table->string('term', 120)->nullable();
                $table->string('clickKind', 8)->nullable();   // fbclid | gclid
                $table->string('clickId', 255)->nullable();
                $table->string('landing', 120)->nullable();   // the page the tags arrived on
                $table->string('method', 16)->default('email');  // email | google
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_signup_sources');
    }
};
