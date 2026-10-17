<?php

use App\Models\AsNlEmail;
use App\Support\Newsletter\SpamCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter emails in the queue: the advice is Anee's own (the owner,
 * 2026-10-17: never an agency as where the farmer's advice comes from).
 * Six queued emails (S022, S061, S079, S080, S109, S178) said a body's
 * recommendations were the source; their sentences are said our way, from
 * database/newsletter/advice-in-our-words.json. Plain referrals stay (the
 * MAO, the municipal agriculturist, where to get a soil test, the text
 * center). Only an email not yet sent is touched, and only where its old
 * words are still there: an email edited in the kit since keeps its edit.
 * Each changed email is scored again (SpamCheck), so the line sees a fresh
 * badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_nl_emails')) {
            return;
        }
        $file = database_path('newsletter/advice-in-our-words.json');
        $edits = (array) (json_decode((string) @file_get_contents($file), true)['emails'] ?? []);
        foreach ($edits as $id => $pairs) {
            $e = AsNlEmail::find((int) $id);
            if (! $e || ! in_array($e->status, ['draft', 'queued', 'partial'], true)) {
                continue;
            }
            $blocks = (array) $e->blocks;
            $hit = 0;
            $walk = function ($v) use (&$walk, $pairs, &$hit) {
                if (is_array($v)) {
                    return array_map($walk, $v);
                }
                if (! is_string($v)) {
                    return $v;
                }
                foreach ($pairs as [$old, $new]) {
                    if (str_contains($v, $old)) {
                        $v = str_replace($old, $new, $v);
                        $hit++;
                    }
                }

                return $v;
            };
            $blocks = $walk($blocks);
            if ($hit === 0) {
                continue;
            }
            $e->blocks = $blocks;
            $e->save();
            try {
                SpamCheck::forSaved($e->fresh());
            } catch (\Throwable $x) {
                report($x);
            }
        }
    }

    public function down(): void
    {
        // The new words stay.
    }
};
