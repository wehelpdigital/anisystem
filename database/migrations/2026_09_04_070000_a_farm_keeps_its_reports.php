<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One shelf for every farm report.
 *
 * Four kinds live here: snapshots of the computed reports (labor, expenses,
 * profit — frozen so they can ride into an Anee chat exactly as the farmer
 * saw them), and the AI-written ones (season, sofar) that arrive through the
 * same pending → ready job walk the when-to-plant analyses use.
 *
 *   body    — the report said in plain text: what Anee reads when attached,
 *             and what Copy-as-Text copies.
 *   report  — the structured JSON the AI kinds are drawn from.
 *   params  — the filters / lot the report was asked with.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_farm_reports')) {
            return;
        }
        Schema::create('as_farm_reports', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->integer('userId')->index();
            $t->integer('croppingScheduleId')->index();
            $t->string('kind', 16)->index();      // labor | expenses | profit | season | sofar
            $t->string('title', 191);
            $t->json('params')->nullable();
            $t->mediumText('body')->nullable();
            $t->json('report')->nullable();
            $t->decimal('credits', 8, 2)->default(0);
            $t->string('status', 12)->default('ready');   // pending | ready | failed
            $t->string('error', 500)->nullable();
            $t->tinyInteger('deleteStatus')->default(1)->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_farm_reports');
    }
};
