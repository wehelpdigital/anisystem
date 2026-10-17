<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The usage ledger counts the prompt tokens a provider's prefix cache served
 * (2026-10-19): Gemini's implicit caching bills them at a fraction, and it
 * only applies when a prompt's fixed text comes first. Measured before any
 * price counts on it. AiUsage::record writes the column once it exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_ai_usage') || Schema::hasColumn('as_ai_usage', 'tokensCached')) {
            return;
        }
        Schema::table('as_ai_usage', function (Blueprint $table) {
            $table->unsignedInteger('tokensCached')->default(0)->after('tokensOut');
        });
        Cache::forget('ai-usage:has-cached');
    }

    public function down(): void
    {
        if (Schema::hasTable('as_ai_usage') && Schema::hasColumn('as_ai_usage', 'tokensCached')) {
            Schema::table('as_ai_usage', function (Blueprint $table) {
                $table->dropColumn('tokensCached');
            });
        }
        Cache::forget('ai-usage:has-cached');
    }
};
