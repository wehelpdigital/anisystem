<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The footer pages in plain words (2026-10-22 SEO audit of every public
 * page: the owner's banned words and no dashes apply to every word a reader
 * sees). Four small swaps in as_legal_pages, each only where the exact old
 * words still stand, so anything the owner wrote in the mother app stays as
 * written. The default files in resources/views/legal/defaults say the same
 * new words. The pages' headings also move up a level (LegalController).
 */
return new class extends Migration
{
    private const LEGAL = [
        'privacy' => [
            ['is a farm-management app made in the Philippines.', 'is a farm management app made in the Philippines.'],
            ['If a change significantly affects your rights,', 'If a change affects your rights in a big way,'],
        ],
        'terms' => [
            ['anee.io is a farm-management app. It helps you', 'anee.io is a farm management app. It helps you'],
        ],
        'cookies' => [
            ['These services receive your IP address in order to deliver the file,', 'These services receive your IP address to deliver the file,'],
        ],
        'about' => [
            ['anee.io is a farm-management app from the Philippines.', 'anee.io is a farm management app from the Philippines.'],
            ['learning the craft who want a guide beside them.', 'learning the trade who want a guide beside them.'],
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('as_legal_pages')) {
            return;
        }
        foreach (self::LEGAL as $slug => $pairs) {
            $row = DB::table('as_legal_pages')->where('slug', $slug)->first();
            if (! $row) {
                continue;
            }
            $body = (string) $row->body;
            $new = $body;
            foreach ($pairs as [$old, $now]) {
                $new = str_replace($old, $now, $new);
            }
            if ($new !== $body) {
                DB::table('as_legal_pages')->where('id', $row->id)->update(['body' => $new]);
            }
        }
    }

    public function down(): void
    {
        // Words only; nothing to undo.
    }
};
