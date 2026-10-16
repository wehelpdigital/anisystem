<?php

use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Compatibility Checker (2026-10-18, the owner): the product book every
 * field helper can read (as_products, filled from database/products by
 * ProductLibrary::sync) and the farmers' own products (as_user_products, one
 * row a product, kept per farm owner). A later release that changes the
 * book's files adds a migration that calls ProductLibrary::sync() again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_products')) {
            Schema::create('as_products', function (Blueprint $table) {
                $table->id();
                $table->string('pkey', 120)->unique();
                $table->string('brand', 190);
                $table->string('brandKey', 190)->default('')->index();
                $table->string('company', 120)->default('');
                $table->string('type', 24)->default('other');
                $table->string('form', 16)->default('');
                $table->string('applied', 8)->default('spray');
                $table->json('ai')->nullable();
                $table->json('nutrients')->nullable();
                $table->json('traits')->nullable();
                $table->json('crops')->nullable();
                $table->json('uses')->nullable();
                $table->json('info')->nullable();
                $table->text('search')->nullable();
                $table->string('source', 12)->default('file');
                $table->string('sourceRef', 255)->nullable();
                $table->unsignedBigInteger('ownerId')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['status', 'type']);
                $table->index('source');
            });
        }
        if (! Schema::hasTable('as_user_products')) {
            Schema::create('as_user_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ownerId')->index();
                $table->unsignedBigInteger('userId')->nullable();
                $table->string('name', 120);
                $table->string('type', 24)->default('other');
                $table->string('form', 16)->default('');
                $table->string('applied', 8)->default('spray');
                $table->json('ai')->nullable();
                $table->json('nutrients')->nullable();
                $table->decimal('rateAmount', 14, 4)->nullable();
                $table->string('rateUnit', 12)->nullable();
                $table->string('ratePer', 12)->nullable();
                $table->string('notes', 500)->default('');
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
        // Other names a product is asked by (ProductLibrary::learn, find): apart from the rows, so a sync never loses them.
        if (! Schema::hasTable('as_product_aliases')) {
            Schema::create('as_product_aliases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('productId')->index();
                $table->string('alias', 190);
                $table->string('aliasKey', 190)->index();
                $table->string('via', 24)->default('');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique(['productId', 'aliasKey']);
            });
        }
        ProductLibrary::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_product_aliases');
        Schema::dropIfExists('as_user_products');
        Schema::dropIfExists('as_products');
    }
};
