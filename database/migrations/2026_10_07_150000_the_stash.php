<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Stash (2026-10-07): resources shared by anee.io's partners, grouped by
 * partner and by type. First shelf: PhilRice's e-magazines, seeded from
 * database/stash/philrice-emagazines.json. The PDFs themselves are fetched
 * into the media disk by `stash:fetch` (the scheduler runs it), so a row
 * reads 'pending' until its file is home.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_stash_items')) {
            Schema::create('as_stash_items', function (Blueprint $t) {
                $t->id();
                $t->string('partner', 40);
                $t->string('type', 40);
                $t->string('slug', 160);
                $t->string('title', 255);
                $t->text('blurb')->nullable();
                $t->date('publishedOn')->nullable();
                $t->string('sourcePost', 500)->nullable();
                $t->string('sourcePdf', 500)->nullable();
                $t->string('coverPath', 255)->nullable();
                $t->string('pdfPath', 255)->nullable();
                $t->unsignedBigInteger('bytes')->nullable();
                $t->unsignedInteger('pages')->nullable();
                $t->string('status', 16)->default('pending');
                $t->string('error', 500)->nullable();
                $t->unsignedInteger('tries')->default(0);
                $t->integer('sortOrder')->default(0);
                $t->unsignedTinyInteger('deleteStatus')->default(1);
                $t->timestamps();
                $t->unique(['partner', 'type', 'slug']);
                $t->index(['partner', 'type', 'deleteStatus', 'publishedOn']);
            });
        }

        $file = database_path('stash/philrice-emagazines.json');
        $rows = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        foreach ((array) $rows as $r) {
            $key = ['partner' => 'philrice', 'type' => 'e-magazines', 'slug' => $r['slug']];
            $fields = [
                'title' => mb_substr((string) $r['title'], 0, 255),
                'blurb' => $r['blurb'] ?: null,
                'publishedOn' => ! empty($r['published']) ? $r['published'] . '-01' : null,
                'sourcePost' => $r['post'] ?? null,
                'sourcePdf' => $r['pdf'] ?? null,
                'coverPath' => $r['cover'] ?? null,
                'bytes' => $r['bytes'] ?? null,
                'sortOrder' => (int) ($r['sort'] ?? 0),
                'updated_at' => now(),
            ];
            if (DB::table('as_stash_items')->where($key)->exists()) {
                DB::table('as_stash_items')->where($key)->update($fields);
            } else {
                DB::table('as_stash_items')->insert($key + $fields + ['status' => 'pending', 'deleteStatus' => 1, 'created_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_stash_items');
    }
};
