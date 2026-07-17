<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_subscriptions')) {
            return;
        }

        Schema::create('anisystem_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index(); // -> anisystem_users.id
            $table->unsignedBigInteger('planId')->nullable(); // -> anisystem_plans.id
            $table->string('planKey', 50)->nullable();
            $table->string('planName', 100);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('durationDays')->default(30);
            $table->unsignedBigInteger('ecomOrderId')->nullable()->index(); // soft FK -> ecom_orders.id
            $table->string('orderNumber', 50)->nullable();
            // pending | active | suspended | cancelled | expired | rejected
            $table->string('status', 20)->default('pending')->index();
            $table->dateTime('startsAt')->nullable();
            $table->dateTime('expiresAt')->nullable()->index();
            $table->dateTime('verifiedAt')->nullable();
            $table->dateTime('suspendedAt')->nullable();
            $table->dateTime('cancelledAt')->nullable();
            $table->dateTime('expiryNotifiedAt')->nullable(); // last "expiring soon" email
            $table->text('notes')->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anisystem_subscriptions');
    }
};
