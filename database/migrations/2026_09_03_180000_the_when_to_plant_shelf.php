<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved when-to-plant analyses.
 *
 * The questions asked (params) and the answer given (report) both ride as
 * JSON: the report is a document the farmer keeps, not state the app
 * recomputes — the analysis that cost credits yesterday must read the same
 * tomorrow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_plant_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->string('title', 190);
            $table->json('params');
            $table->json('report');
            $table->decimal('credits', 8, 2)->default(0);
            $table->unsignedTinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_plant_analyses');
    }
};
