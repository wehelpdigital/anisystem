<?php

use App\Support\VarietyRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Registration trial rows filed under the wrong crop or joined to the wrong
 * record (2026-10-20, the owner: "yes, fix all"), each settled from the
 * registration papers:
 *  - Orbase and Allen 02 are pili (Canarium ovatum), not pineapple; JMG 5W,
 *    JMG 3 and Angel Sweet are rambutans, not rubber; Roxas Purple is a
 *    mangosteen, not a mungbean; NSIC 1996 Sg 03 is a sorghum (an open line,
 *    not a hybrid corn). Pili is no app crop, so no analysis reads those two.
 *  - Rows joined to a neighbour by a number the list and the papers write
 *    differently now join the record of their own name (Raniag, Solibao,
 *    Igorota, Bengueta and Gloria Patatas, NOMIARC Tm 1, Tublay, 30W30,
 *    USM Pn1, Cepillo Green, three pili).
 *  - Rc 716H's online file holds SL 39H's form (Rc 718H): its row is read
 *    again from its own papers (SAC 01, filed under Rc 718H); Rc 718H keeps
 *    SL 39H's figures. Rc 696H gains its line name, LP 906.
 * VarietyRegistry::sync() is idempotent: a row moved to another crop is a new
 * row, and its old one is retired.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_variety_registry')) {
            return;
        }
        VarietyRegistry::sync(database_path(VarietyRegistry::FILE));
        Cache::forget('variety-registry:' . config('database.default') . ':ready');
    }

    public function down(): void
    {
        // The rows keep their right crops.
    }
};
