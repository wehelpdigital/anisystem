<?php

use App\Support\HowToPlant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19, the owner): how traditional Filipino farmers
 * plant each crop, one guide per crop of the catalog, read by
 * App\Support\HowToPlant and drawn by /app/how-to-plant. The guides ship as
 * database/how-to-plant/guides/*.json and are loaded here (a later release
 * of them adds a migration that calls HowToPlant::sync() again).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_how_to_plant')) {
            Schema::create('as_how_to_plant', function (Blueprint $t) {
                $t->id();
                $t->string('crop', 40)->unique();
                $t->string('slug', 80)->default('');
                $t->string('name', 120)->default('');
                $t->string('local', 120)->default('');
                $t->longText('guide');
                $t->boolean('hasVideo')->default(false);
                $t->string('rev', 20)->default('');
                $t->unsignedSmallInteger('sortOrder')->default(0);
                $t->string('source', 10)->default('file');
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
        HowToPlant::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_how_to_plant');
    }
};
