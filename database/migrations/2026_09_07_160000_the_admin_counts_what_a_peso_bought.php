<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acquisition analysis batches: a named ad spend over a date range, read
 * against who registered and who became a paying client.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_sales_analyses')) {
            return;
        }

        Schema::create('as_sales_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->date('dateFrom');
            $table->date('dateTo');
            $table->decimal('adCost', 12, 2)->default(0);
            $table->string('adCadence', 12)->default('daily');
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_sales_analyses');
    }
};
