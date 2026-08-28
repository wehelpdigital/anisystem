<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The rest of the words. Found by reading the schema rather than by guessing.
 *
 * The first content rename covered the tables somebody thought of. This one
 * covers what a scan of every text column in the app's own tables turned up:
 * a schedule's own description, six queued emails, the homepage copy and its
 * testimonials, and the name the mailer signs off with.
 *
 * What the scan ALSO turned up is the reason this is an explicit list rather
 * than a sweep. Twenty columns hold one of the old names, and half of them
 * are not words at all:
 *
 *   - groupKey on four tables: a foreign key spelled as a word, matching rows
 *     in the mother app, which has not been renamed.
 *   - path, imagePath, posterPath, videoPath, image, image2: FILES. Renaming
 *     the string renames nothing on disk; it just 404s the picture.
 *   - authorEmail, email, invitedEmail: addresses people sign in with.
 *
 * Renaming any of those changes nothing on a screen and breaks something
 * behind it, which is the whole shape of this rebrand in one table.
 */
return new class extends Migration
{
    /** table => the columns on it that a person actually reads. */
    private const BOOKS = [
        'as_cropping_schedules' => ['description'],
        'as_email_tasks' => ['subject', 'bodyHtml'],
        'as_homepage_items' => ['description'],
        'as_homepage_sections' => ['settings'],
        'as_testimonials' => ['testimonial'],
        'as_schedule_date_notes' => ['noteContent'],
        'as_mail_smtp_settings' => ['smtpFromName'],
    ];

    /* Longest first: "AniSystem by AniSenso" has to be spent before either
     * half of it is, or it becomes "anee.io by anee.io". */
    private const SAYS = [
        'AniSystem by AniSenso' => 'anee.io',
        'AniSenso Team' => 'anee.io Team',
        'the AniSenso team' => 'the anee.io team',
        'AniSenso Technology' => 'anee.io technology',
        'AniSenso fertilization technology' => 'anee.io fertilization technology',
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
        // Deliberately nothing: these are words somebody may have edited
        // since, and a rollback has no business writing the old name back
        // over an author's corrections.
    }
};
