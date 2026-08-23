<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The documentation a season had before it was one list.
 *
 * Documentation used to be three separate things — a protocol row, a table of
 * critical rules, and a shelf of attachments — and is now one list of typed
 * entries. The screen was rebuilt; the rows were not carried over. A season
 * with five critical rules therefore counted five documents on the hub and
 * showed "No documents yet" when opened, because the count read the old
 * tables and the page read the new one.
 *
 * This carries them across, once, and closes the old rows behind them. It has
 * to close them: the printed documents (export, worker presentation, card
 * viewer) render BOTH shapes, so a rule left in place would appear twice on
 * paper the day after it appeared once.
 *
 * Nothing is destroyed — deleteStatus 0 is how this app puts a row away — and
 * an entry is only created where one is not already there, so re-running is
 * harmless.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['as_schedule_doc_entries', 'as_schedule_critical_rules', 'as_schedule_attachments', 'as_schedule_protocols'] as $table) {
            if (! Schema::hasTable($table)) {
                return;   // an install that never had one of these has nothing to move
            }
        }

        $now = now();

        // ---- The protocol: one row per schedule, if it holds anything ----
        $protocols = DB::table('as_schedule_protocols')
            ->where('deleteStatus', 1)
            ->where(function ($q) {
                $q->whereNotNull('protocolContent')->where('protocolContent', '!=', '')
                    ->orWhereNotNull('protocolFile');
            })
            ->get();

        foreach ($protocols as $p) {
            $already = DB::table('as_schedule_doc_entries')
                ->where('croppingScheduleId', $p->croppingScheduleId)
                ->where('type', 'protocol')
                ->where('deleteStatus', 1)
                ->exists();
            if ($already) {
                continue;
            }

            $files = [];
            if (filled($p->protocolFile)) {
                $files[] = [
                    'path' => $p->protocolFile,
                    'name' => $p->protocolFileOriginalName ?: basename((string) $p->protocolFile),
                    'size' => 0,
                    'mime' => null,
                ];
            }

            DB::table('as_schedule_doc_entries')->insert([
                'croppingScheduleId' => $p->croppingScheduleId,
                'type' => 'protocol',
                'tagId' => null,
                'title' => $p->protocolType ?: 'Protocol',
                'content' => $p->protocolContent,
                'files' => $files ? json_encode($files) : null,
                'sortOrder' => 0,
                'deleteStatus' => 1,
                'created_at' => $p->created_at ?: $now,
                'updated_at' => $now,
            ]);

            DB::table('as_schedule_protocols')->where('id', $p->id)->update([
                'deleteStatus' => 0,
                'updated_at' => $now,
            ]);
        }

        // ---- Critical rules: one entry apiece, in the order they were kept ----
        $rules = DB::table('as_schedule_critical_rules')->where('deleteStatus', 1)->orderBy('sortOrder')->orderBy('id')->get();
        foreach ($rules as $r) {
            $text = trim((string) $r->ruleText);
            if ($text !== '') {
                DB::table('as_schedule_doc_entries')->insert([
                    'croppingScheduleId' => $r->croppingScheduleId,
                    'type' => 'critical_rule',
                    'tagId' => null,
                    // The old rule was one line of text and had no title of its
                    // own; the list shows the type as its heading.
                    'title' => null,
                    'content' => $text,
                    'files' => null,
                    'sortOrder' => (int) $r->sortOrder,
                    'deleteStatus' => 1,
                    'created_at' => $r->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('as_schedule_critical_rules')->where('id', $r->id)->update([
                'deleteStatus' => 0,
                'updated_at' => $now,
            ]);
        }

        // ---- Attachments: a file with a description is a miscellaneous entry ----
        $files = DB::table('as_schedule_attachments')->where('deleteStatus', 1)->orderBy('sortOrder')->orderBy('id')->get();
        foreach ($files as $f) {
            DB::table('as_schedule_doc_entries')->insert([
                'croppingScheduleId' => $f->croppingScheduleId,
                'type' => 'miscellaneous',
                'tagId' => null,
                'title' => $f->filename ?: 'Attachment',
                'content' => $f->description,
                'files' => json_encode([[
                    'path' => $f->storagePath,
                    'name' => $f->filename ?: basename((string) $f->storagePath),
                    'size' => (int) $f->fileSize,
                    'mime' => $f->mimeType,
                ]]),
                'sortOrder' => (int) $f->sortOrder,
                'deleteStatus' => 1,
                'created_at' => $f->created_at ?: $now,
                'updated_at' => $now,
            ]);

            DB::table('as_schedule_attachments')->where('id', $f->id)->update([
                'deleteStatus' => 0,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reversing this would mean guessing which entries were once rules and
     * putting rows back that the new screen may since have edited. The old
     * rows are still there, hidden; bringing them back is a deliberate act,
     * not something a rollback should do behind anyone's back.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
