<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROTOCOL VERSIONS — one protocol, several ways of running it ("Wet
 * season", "Dry season"). A version carries everything that can differ:
 * the tasks, the protocol's own list of materials, the rules-and-notes
 * document, the files beside it, and its own undo/redo with its own rev.
 * The protocol row keeps the crop, the count, the tags and which version
 * is in use (`versionId`).
 *
 * Every existing protocol gets "Version 1" made from what it holds now,
 * so nothing is lost and nothing has to be moved by hand. The controller
 * makes one lazily as well, for a row this migration never saw.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_protocol_versions')) {
            Schema::create('as_protocol_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('protocolId')->index();
                $table->unsignedBigInteger('userId')->index();
                $table->string('name', 80);
                $table->mediumText('tasks');                 // JSON list of tasks, notes and dividers
                $table->mediumText('materials')->nullable(); // JSON list of the protocol's materials
                $table->mediumText('rules')->nullable();     // the rules & notes document (sanitised HTML)
                $table->text('files')->nullable();           // JSON list of {id,name,path,size,mime,at}
                $table->mediumText('history')->nullable();   // {"undo":[...],"redo":[...]} snapshots of {tasks, materials}
                $table->unsignedInteger('rev')->default(1);
                $table->integer('sortOrder')->default(0);
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('as_protocols') && ! Schema::hasColumn('as_protocols', 'versionId')) {
            Schema::table('as_protocols', function (Blueprint $t) {
                $t->unsignedBigInteger('versionId')->nullable()->after('rev');
            });
        }

        if (! Schema::hasTable('as_protocols')) {
            return;
        }
        // Version 1 for every protocol that has none yet.
        $have = DB::table('as_protocol_versions')->where('deleteStatus', 1)->pluck('protocolId')->unique()->all();
        $now = now('Asia/Manila');
        DB::table('as_protocols')->whereNotIn('id', $have ?: [0])
            ->orderBy('id')
            ->get(['id', 'userId', 'tasks', 'history', 'rev'])
            ->each(function ($p) use ($now) {
                $vid = DB::table('as_protocol_versions')->insertGetId([
                    'protocolId' => $p->id,
                    'userId' => $p->userId,
                    'name' => 'Version 1',
                    'tasks' => $p->tasks ?: '[]',
                    'materials' => '[]',
                    'rules' => null,
                    'files' => '[]',
                    'history' => $p->history ?: '{"undo":[],"redo":[]}',
                    'rev' => max(1, (int) $p->rev),
                    'sortOrder' => 0,
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('as_protocols')->where('id', $p->id)->update(['versionId' => $vid]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_protocols') && Schema::hasColumn('as_protocols', 'versionId')) {
            Schema::table('as_protocols', function (Blueprint $t) {
                $t->dropColumn('versionId');
            });
        }
        Schema::dropIfExists('as_protocol_versions');
    }
};
