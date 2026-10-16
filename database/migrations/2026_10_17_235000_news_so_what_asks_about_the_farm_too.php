<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The green box's title asks about the farm too (2026-10-17, the owner:
 * "change this to Anong epekto sayo o sa bukid mo"): on the news pages
 * already written and on the news emails not yet sent. Their words in
 * Taglish are written by Anee, not here (NewsEmail::retaglish, run once by
 * hand for the pending emails; new emails get it as they are made).
 */
return new class extends Migration
{
    private const WAS = 'Anong epekto nito sayo';

    private const NOW = 'Anong epekto sayo o sa bukid mo';

    public function up(): void
    {
        foreach (['as_site_pages' => fn ($q) => $q, 'as_nl_emails' => fn ($q) => $q->where('status', '!=', 'sent')] as $table => $only) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($only(DB::table($table)->where('blocks', 'like', '%' . self::WAS . '%'))->orderBy('id')->get(['id', 'blocks']) as $row) {
                $blocks = str_replace('"' . self::WAS . '"', '"' . self::NOW . '"', (string) $row->blocks);
                if ($blocks !== $row->blocks) {
                    DB::table($table)->where('id', $row->id)->update(['blocks' => $blocks]);
                }
            }
        }
    }

    public function down(): void
    {
        // The words stay as the owner asked; nothing to undo.
    }
};
