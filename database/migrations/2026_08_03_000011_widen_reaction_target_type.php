<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The reaction targetType column was varchar(10) — too short for the 11-char
 * keys 'wallcomment' and the new 'blogcomment'. Widen it so every target type
 * (including blog-comment reactions) stores correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_reactions')) {
            DB::statement('ALTER TABLE `as_community_reactions` MODIFY `targetType` VARCHAR(32) NOT NULL');
        }
    }

    public function down(): void
    {
        // Not narrowing back — would truncate valid data.
    }
};
