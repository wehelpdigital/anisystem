<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Anee's credit packs at round prices (2026-09-19, the owner's list):
 *
 *   ₱100   → 100 credits
 *   ₱500   → 550 credits
 *   ₱1,000 → 1,150 credits
 *
 * The three rows keep their keys (the dollar price list on the mother
 * app's shelf is keyed by them) and take the new figures; the words say
 * what a pack is for without promising a question count that the metered
 * price makes untrue.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            'starter' => ['packName' => 'Starter', 'credits' => 100, 'price' => 100, 'description' => 'A first pack: a few questions, or one analysis.', 'sortOrder' => 1],
            'farmer' => ['packName' => 'Farmer', 'credits' => 550, 'price' => 500, 'description' => 'Fifty credits free: a season of questions and a couple of analyses.', 'sortOrder' => 2],
            'season' => ['packName' => 'Whole Season', 'credits' => 1150, 'price' => 1000, 'description' => 'A hundred and fifty free: the whole season, analyses included.', 'sortOrder' => 3],
        ];
        foreach ($rows as $key => $row) {
            DB::table('anisystem_ai_credit_packs')->where('packKey', $key)->where('deleteStatus', 1)->update($row + ['isActive' => 1, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $old = [
            'starter' => ['credits' => 100, 'price' => 99],
            'farmer' => ['credits' => 350, 'price' => 299],
            'season' => ['credits' => 1000, 'price' => 749],
        ];
        foreach ($old as $key => $row) {
            DB::table('anisystem_ai_credit_packs')->where('packKey', $key)->update($row);
        }
    }
};
