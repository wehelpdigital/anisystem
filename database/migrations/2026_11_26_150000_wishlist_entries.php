<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The First 50 wishlist (2026-11-26, the owner: "a wishlist form or wizard that i can market for the first 50 farmers
 * ... ask questions from contact details, farmer questions, etc."). One row per farmer who answered at /unang-50: who
 * they are, their farm, what hurts, what they wish a farm helper did, how they would pay, and how to reach them. A row
 * is found again by phone or email, so answering twice updates the same row and keeps its place. Read in the admin
 * panel (Unang 50). App\Support\Wishlist writes and reads it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_wishlist_entries')) {
            Schema::create('as_wishlist_entries', function (Blueprint $table) {
                $table->id();
                $table->string('refCode', 12)->unique();            // their own share code (?r=)
                $table->string('referredBy', 12)->nullable()->index();
                $table->string('firstName', 60);
                $table->string('lastName', 60)->nullable();
                $table->string('phone', 20)->nullable()->index();   // +639XXXXXXXXX
                $table->string('email', 120)->nullable()->index();
                $table->string('contactVia', 20)->nullable();       // text | call | viber | messenger | whatsapp | email
                $table->string('contactTime', 20)->nullable();
                $table->string('role', 20)->nullable();
                $table->string('province', 60)->nullable();
                $table->string('town', 80)->nullable();
                $table->json('crops')->nullable();
                $table->string('seasons', 10)->nullable();
                $table->string('farmSize', 10)->nullable();
                $table->string('lots', 10)->nullable();
                $table->string('helpers', 12)->nullable();
                $table->json('pains')->nullable();
                $table->string('yieldNum', 12)->nullable();
                $table->string('yieldUnit', 12)->nullable();
                $table->json('wishes')->nullable();
                $table->text('wishText')->nullable();
                $table->string('phoneType', 12)->nullable();
                $table->string('signal', 10)->nullable();
                $table->string('payWay', 12)->nullable();
                $table->string('payAmount', 12)->nullable();
                $table->string('heardFrom', 12)->nullable();
                $table->string('lang', 4)->nullable();
                $table->json('utm')->nullable();
                $table->string('device', 12)->nullable();
                $table->string('status', 12)->default('new');       // new | contacted | accepted | notfit
                $table->text('adminNote')->nullable();
                $table->unsignedInteger('answers')->default(1);     // how many times they sent it
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_wishlist_entries');
    }
};
