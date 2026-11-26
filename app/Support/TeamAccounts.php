<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Our own people, left out of the counts that are about farmers (the owner, 2026-11-25: "all that says test, admin,
 * wehelp.digital, gowebdevhero, kathleen, ariel, and cris"). Staff, the test and system accounts, those names, and
 * anybody working on a staff member's farm. The Activity page can put them back in with one tap.
 */
final class TeamAccounts
{
    private const MAIL = '/test|admin|wehelp\.digital|gowebdevhero|vetdent|example\.|anisenso\.system|technician@anee\.io|themesbrand/i';

    private const NAMES = ['kathleen', 'ariel', 'cris', 'chris'];

    /** Their account ids, worked out once every ten minutes. */
    public static function ids(): array
    {
        return Cache::remember('team.accounts', 600, function () {
            $ids = [];
            $staff = [];
            foreach (DB::table('anisystem_users')->get(['id', 'email', 'firstName', 'lastName', 'adminUserId', 'panelAdmin']) as $u) {
                $words = preg_split('/[^a-z]+/', strtolower($u->firstName . ' ' . $u->lastName), -1, PREG_SPLIT_NO_EMPTY);
                $isStaff = ! empty($u->adminUserId) || ! empty($u->panelAdmin);
                if ($isStaff) {
                    $staff[] = (int) $u->id;
                }
                if ($isStaff || preg_match(self::MAIL, (string) $u->email) || array_intersect($words, self::NAMES)) {
                    $ids[] = (int) $u->id;
                }
            }
            // The people who work on our own farms.
            try {
                if ($staff) {
                    $ids = array_merge($ids, DB::table('as_worker_grants')->whereIn('bossUserId', $staff)->pluck('workerUserId')->map(fn ($v) => (int) $v)->all());
                }
            } catch (\Throwable $e) {
                // No grants table: the names and addresses are enough.
            }

            return array_values(array_unique(array_filter($ids)));
        });
    }
}
