<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The agreements a person accepts, and the record that they did (2026-10-28, the owner: "in the registration of
 * anee.io, add a eula"; and before Anee Auto starts a season, "a eula that anee is just a guide ... we have no
 * responsibility in anything that could happen ... this is to protect anee as well").
 *
 * Each document has a version (the day its words last changed). Accepting writes a row in as_terms_acceptances:
 * who, which document, which version, when, from which address and browser, and what it was for (an Anee Auto
 * season). A changed document gets a new version, so an old acceptance never stands for words the person did not see.
 */
final class Legal
{
    /** The End User License Agreement, ticked at sign up (legal page `eula`). */
    public const EULA_VERSION = '2026-10-28';

    /** The Anee Auto agreement, read to the end and ticked before a season starts (auto.partials.terms). */
    public const AUTO_VERSION = '2026-10-28';

    /** Keep the record of an acceptance. Never fails the step it belongs to: a missing table only skips the row. */
    public static function record(int $userId, string $doc, string $version, ?int $refId = null, ?Request $request = null): void
    {
        if (! self::ready()) {
            return;
        }
        rescue(fn () => DB::table('as_terms_acceptances')->insert([
            'userId' => $userId, 'doc' => mb_substr($doc, 0, 30), 'version' => mb_substr($version, 0, 20), 'refId' => $refId,
            'ip' => $request ? mb_substr((string) $request->ip(), 0, 64) : null,
            'userAgent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            'created_at' => now(), 'updated_at' => now(),
        ]), null, true);
    }

    /** The table is there (once a request asks; a "no" is never kept, so the deploy's migration counts at once). */
    private static function ready(): bool
    {
        static $ok = null;

        return $ok ??= Schema::hasTable('as_terms_acceptances');
    }
}
