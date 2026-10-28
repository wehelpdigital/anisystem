<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who accepted which agreement, which version, and when (App\Support\Legal, 2026-10-28): the End User License
 * Agreement at sign up and the Anee Auto agreement before a season starts. The EULA also gets its legal page row,
 * so the mother app's legal pages can edit it like Privacy and Terms.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_terms_acceptances')) {
            Schema::create('as_terms_acceptances', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('userId')->index();
                // eula, anee-auto
                $t->string('doc', 30);
                $t->string('version', 20);
                // What it was accepted for: the Anee Auto season's id.
                $t->unsignedBigInteger('refId')->nullable();
                $t->string('ip', 64)->nullable();
                $t->string('userAgent', 255)->nullable();
                $t->timestamps();
                $t->index(['doc', 'version']);
            });
        }

        if (Schema::hasTable('as_legal_pages') && ! DB::table('as_legal_pages')->where('slug', 'eula')->exists()) {
            $body = @file_get_contents(resource_path('views/legal/defaults/eula.html'));
            if ($body) {
                DB::table('as_legal_pages')->insert([
                    'slug' => 'eula', 'title' => 'End User License Agreement', 'body' => $body,
                    'sortOrder' => (int) DB::table('as_legal_pages')->max('sortOrder') + 1, 'isPublished' => 1, 'deleteStatus' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_terms_acceptances');
    }
};
