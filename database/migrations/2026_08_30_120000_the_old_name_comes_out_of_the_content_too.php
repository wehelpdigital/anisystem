<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The rename, applied to the words already written down.
 *
 * Renaming the app in the code changes what the code says. It does not change
 * a blog post signed "AniSenso Team", a privacy policy that names the old
 * product, a help page that tells farmers about a service that no longer goes
 * by that name, or fifteen email templates with the old name in the footer —
 * all of which are rows, written once and read ever since.
 *
 * Text columns only, and only where the name is a NAME. The mail templates'
 * `groupKey` is untouched: that is a foreign key spelled as a word, matching
 * a row in the mother app, which has not been renamed.
 */
return new class extends Migration
{
    /** table => the text columns on it that a person reads. */
    private const BOOKS = [
        'as_community_blog_posts' => ['authorName', 'title', 'excerpt', 'body'],
        'as_legal_pages' => ['title', 'body'],
        'as_tutorial_pages' => ['title', 'summary', 'blocks'],
        'as_tutorials' => ['title', 'description'],
        'as_email_templates' => ['subject', 'bodyHtml'],
        'as_loading_lines' => ['line', 'subline'],
    ];

    /* Order matters. "AniSystem by AniSenso" has to be caught before either
     * half of it is, or it becomes "anee.io by anee.io". */
    private const SAYS = [
        'AniSystem by AniSenso' => 'anee.io',
        'AniSenso Team' => 'anee.io Team',
        'the AniSenso team' => 'the anee.io team',
        'AniSenso team' => 'anee.io team',
        'An AniSenso product' => 'Grown for Filipino farms',
        'support@anisenso.com' => 'support@anee.io',
        'AniSenso' => 'anee.io',
        'AniSystem' => 'anee.io',
    ];

    public function up(): void
    {
        foreach (self::BOOKS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                // REPLACE nested outward-in, so the longest phrase is spent
                // first and the general rules only see what is left.
                $expr = '`' . $column . '`';
                foreach (self::SAYS as $from => $to) {
                    $expr = 'REPLACE(' . $expr . ', ' . DB::getPdo()->quote($from)
                        . ', ' . DB::getPdo()->quote($to) . ')';
                }
                DB::table($table)
                    ->where(function ($q) use ($column) {
                        $q->where($column, 'like', '%AniSenso%')
                            ->orWhere($column, 'like', '%AniSystem%')
                            ->orWhere($column, 'like', '%anisenso.com%');
                    })
                    ->update([$column => DB::raw($expr)]);
            }
        }
    }

    public function down(): void
    {
        // Deliberately nothing. These are words somebody may have edited
        // since, and a rollback of a rename has no business writing the old
        // name back over an author's corrections.
    }
};
