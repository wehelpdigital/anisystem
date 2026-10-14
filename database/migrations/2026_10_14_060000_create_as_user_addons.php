<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-ons bought apart from a plan (2026-10-14): the first is the field
 * helpers pack (App\Support\FieldPack), a year of the Weed Control Helper,
 * the Pest Finder, the Disease Finder and the Variety Finder on the free
 * plan. One row a purchase; a revoked order marks its row revoked.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_user_addons')) {
            return;
        }
        Schema::create('as_user_addons', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('userId');
            $t->string('addonKey', 32);
            $t->string('name', 120)->nullable();
            $t->decimal('price', 10, 2)->default(0);
            $t->string('currency', 3)->default('PHP');
            $t->dateTime('startsAt');
            $t->dateTime('expiresAt');
            $t->string('orderNumber', 40)->nullable();
            $t->string('status', 16)->default('active');
            $t->string('notes', 255)->nullable();
            $t->timestamps();
            $t->index(['userId', 'addonKey', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_user_addons');
    }
};
