<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cross-app sign-in for mother-site super admins.
 *
 * AniSystem and the mother site share one database but are separate apps with
 * isolated sessions (different host, cookie name and session table). The mother
 * site's admins live in the shared `users` table; here they live in
 * `anisystem_users`. This bridge lets an admin sign in with their mother
 * credentials and transparently maps them to a normal AniSystem member — their
 * own schedules and full community access — creating that member on first use.
 *
 * It only READS the `users` table, so the mother site's own auth/session state
 * is never modified.
 */
class SuperAdminBridge
{
    /** The shared mother-site admin roster (same DB, different table). */
    private const ADMIN_TABLE = 'users';

    /**
     * Validate mother-site credentials and return the linked AniSystem member,
     * or null when the email/password isn't a valid, active super admin.
     */
    public static function attempt(string $email, string $password): ?User
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || $password === '') {
            return null;
        }

        $admin = DB::table(self::ADMIN_TABLE)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $admin || empty($admin->password)) {
            return null;
        }
        // Respect the mother site's soft-delete flag ('active' == usable).
        if (isset($admin->delete_status) && (string) $admin->delete_status !== 'active') {
            return null;
        }
        if (! Hash::check($password, $admin->password)) {
            return null;
        }

        $member = self::linkedMember($admin);
        self::adoptOwnSchedules($admin, $member);

        return $member;
    }

    /**
     * Claim the schedules this admin created in the mother site (they carry the
     * mother `usersId` but no `anisystemUserId` yet) so they appear as the
     * bridged member's own plans. Idempotent — only unclaimed rows are linked,
     * and the mother `usersId` is left intact for the mother site.
     */
    private static function adoptOwnSchedules(object $admin, User $member): void
    {
        DB::table('as_cropping_schedules')
            ->where('usersId', $admin->id)
            ->whereNull('anisystemUserId')
            ->update(['anisystemUserId' => $member->id]);
    }

    /** Find (or create) the AniSystem member mapped to this mother admin. */
    private static function linkedMember(object $admin): User
    {
        $member = User::where('adminUserId', $admin->id)->first();

        // Fall back to an existing member that already uses this email.
        if (! $member) {
            $member = User::whereRaw('LOWER(email) = ?', [mb_strtolower((string) $admin->email)])->first();
        }

        if ($member) {
            // Keep the link fresh and make sure a super admin is never locked out.
            $member->forceFill([
                'adminUserId' => (int) $admin->id,
                'status' => 'active',
                'deleteStatus' => 1,
            ])->save();

            return $member;
        }

        [$first, $last] = self::splitName((string) ($admin->name ?? ''));

        return User::create([
            'firstName' => $first !== '' ? $first : 'Admin',
            'lastName' => $last,
            'email' => $admin->email,
            // Sign-in uses the mother credentials; this local hash is never used.
            'password' => Hash::make(Str::random(48)),
            'adminUserId' => (int) $admin->id,
            'status' => 'active',
            'deleteStatus' => 1,
        ]);
    }

    /** Split a single "name" column into first + last for our schema. */
    private static function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return ['Admin', ''];
        }
        $pos = mb_strpos($name, ' ');

        return $pos === false
            ? [$name, '']
            : [mb_substr($name, 0, $pos), mb_substr($name, $pos + 1)];
    }
}
