<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_plans')) {
            return;
        }

        Schema::create('anisystem_plans', function (Blueprint $table) {
            $table->id();
            $table->string('planKey', 50)->index();
            $table->string('planName', 100);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('durationDays');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedBigInteger('ecomProductId')->nullable(); // soft FK -> ecom_products.id
            $table->unsignedBigInteger('ecomVariantId')->nullable(); // soft FK -> ecom_products_variants.id
            $table->tinyInteger('isActive')->default(1);
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anisystem_plans');
    }
};
