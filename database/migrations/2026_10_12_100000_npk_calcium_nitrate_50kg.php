<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus (2026-10-12): calcium nitrate comes in 50 kg bags here, so that
 * is its bag now; 25 kg bags stay one tap away (every bagged product offers
 * both sizes). Saved plans that counted it in 25 kg bags keep their weight:
 * their lines move to the 25 kg bag unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_npk_products')) {
            return;
        }
        $id = DB::table('as_npk_products')->whereNull('userId')->where('slug', 'calcium-nitrate')->where('bagKg', 25)->value('id');
        if (! $id) {
            return;
        }
        DB::table('as_npk_products')->where('id', $id)->update(['bagKg' => 50, 'updated_at' => now()]);
        if (! Schema::hasTable('as_npk_calcs')) {
            return;
        }
        foreach (DB::table('as_npk_calcs')->where('lines', 'like', '%"bag"%')->get(['id', 'lines']) as $calc) {
            $lines = json_decode((string) $calc->lines, true);
            if (! is_array($lines)) {
                continue;
            }
            $changed = false;
            foreach ($lines as &$l) {
                if ((int) ($l['id'] ?? 0) === (int) $id && ($l['unit'] ?? '') === 'bag') {
                    $l['unit'] = 'bag25';
                    $changed = true;
                }
            }
            unset($l);
            if ($changed) {
                DB::table('as_npk_calcs')->where('id', $calc->id)->update(['lines' => json_encode($lines)]);
            }
        }
    }

    public function down(): void
    {
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'calcium-nitrate')->update(['bagKg' => 25]);
    }
};
