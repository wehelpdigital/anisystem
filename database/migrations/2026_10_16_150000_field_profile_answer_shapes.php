<?php

use App\Support\FieldProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lots saved before "Any signs of salt?" took several picks (2026-10-16)
 * kept one word ("salt": "crust"), and the form refused every tap on that
 * question. Each saved answer is put in the shape its question asks for now
 * (FieldProfile::shape): a list where several picks are allowed, one word
 * where one is. Nothing else in the answers changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_field_profiles')) {
            return;
        }
        foreach (DB::table('as_field_profiles')->get(['id', 'answers']) as $r) {
            $a = json_decode((string) $r->answers, true);
            if (! is_array($a)) {
                continue;
            }
            $shaped = FieldProfile::shape($a);
            if ($shaped !== $a) {
                DB::table('as_field_profiles')->where('id', $r->id)->update(['answers' => json_encode($shaped, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        // A list is what the form wants; there is nothing to put back.
    }
};
