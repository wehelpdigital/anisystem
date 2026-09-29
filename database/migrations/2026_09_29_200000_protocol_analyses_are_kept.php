<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every "Analyze by Anee" run on a protocol is kept, and read on the
 * builder's own Analyses tab (2026-09-29). Until now a protocol held one
 * review and a new one replaced it; that latest review still rides on
 * as_protocols (the task cards and the list read it), and this table is
 * the shelf of all of them, the latest included.
 *
 * The one review each protocol already held is copied onto the shelf.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_protocol_analyses')) {
            Schema::create('as_protocol_analyses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('protocolId')->index();
                $table->unsignedBigInteger('userId')->index();
                $table->unsignedBigInteger('versionId')->nullable();   // the version Anee read
                $table->string('versionName', 120)->nullable();
                $table->unsignedTinyInteger('score')->nullable();
                $table->json('analysis');
                $table->decimal('credits', 8, 2)->default(0);
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('as_protocols') && ! DB::table('as_protocol_analyses')->exists()) {
            $rows = DB::table('as_protocols')->where('analysisStatus', 'ready')->whereNotNull('analysis')->get();
            foreach ($rows as $p) {
                $a = json_decode((string) $p->analysis, true);
                if (! is_array($a) || ! isset($a['score'])) {
                    continue;
                }
                $at = $p->analysisAt ?: $p->updated_at;
                DB::table('as_protocol_analyses')->insert([
                    'protocolId' => $p->id,
                    'userId' => $p->userId,
                    'versionId' => $p->versionId ?? null,
                    'versionName' => null,
                    'score' => max(0, min(100, (int) $a['score'])),
                    'analysis' => $p->analysis,
                    'credits' => $p->analysisCredits ?? 0,
                    'deleteStatus' => 1,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_protocol_analyses');
    }
};
