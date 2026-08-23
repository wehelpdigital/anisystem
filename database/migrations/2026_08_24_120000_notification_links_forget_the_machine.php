<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Take the machine off the notification links already written down.
 *
 * One database is shared by every copy of this app, and route() writes an
 * absolute address: the table holds links to anisystem.test, to 127.0.0.1,
 * to localhost and to the live site, depending on which machine happened to
 * ring the bell. On the live site the first three are links to somebody's
 * laptop — a farmer taps the notification and the browser goes nowhere.
 *
 * The bell already serves these through NotificationService::localUrl, so
 * this is not what fixes the panel; it is so every other reader of the table
 * (the mother site's admin views among them) sees the same honest path.
 *
 * The rule is localUrl's, not this migration's, so the two can never say
 * different things.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('anisystem_notifications')
            ->whereNotNull('url')
            ->where('url', 'like', 'http%')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $path = \App\Services\NotificationService::localUrl($row->url);
                    if ($path !== null && $path !== $row->url) {
                        DB::table('anisystem_notifications')
                            ->where('id', $row->id)
                            ->update(['url' => $path]);
                    }
                }
            });
    }

    /**
     * Deliberately empty: the host that was there was the wrong one, and
     * putting a host back would only be guessing which wrong one it was.
     */
    public function down(): void
    {
    }
};
