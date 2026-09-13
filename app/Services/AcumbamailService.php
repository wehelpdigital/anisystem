<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The mailing list, kept in step with who has actually joined.
 *
 * Acumbamail's API is one URL per function — https://acumbamail.com/api/1/
 * <functionName>/ — with the account's auth_token travelling as an ordinary
 * parameter rather than a header. Everything here is POST: the documentation
 * asks for it wherever the call changes stored data, and addSubscriber does.
 *
 * NOTHING IN HERE MAY BREAK A SIGNUP. A marketing list being unreachable is
 * not a reason to refuse somebody an account, so every call is wrapped, every
 * failure is a log line, and the caller is never handed an exception. The
 * welcome-credits and welcome-email steps beside it already work this way.
 */
class AcumbamailService
{
    /** Long enough for a slow third party, short enough not to hold a signup. */
    private const TIMEOUT = 12;

    public function configured(): bool
    {
        return config('acumbamail.token') !== '' && (int) config('acumbamail.list_id') > 0;
    }

    /**
     * Put a member on the list.
     *
     * `update_subscriber` is on: somebody who signs up, is deleted, and comes
     * back — or who simply already sits on the list from an earlier import —
     * should have their details corrected rather than the call refused.
     *
     * `double_optin` is off. The address has already been proved by this app,
     * with a link the person clicked; asking them to prove it a second time to
     * a company they have never heard of is how a confirmed member ends up
     * unconfirmed on the list.
     */
    public function addMember(User $user, string $plan = 'free'): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $f = config('acumbamail.fields');
        $merge = [
            $f['email'] => $user->email,
            $f['first_name'] => (string) $user->firstName,
            $f['last_name'] => (string) $user->lastName,
            $f['phone'] => (string) $user->phone,
        ];

        /* The segment is a rule over a field, not a bag with a door — see the
         * note in config/acumbamail.php. Stamping the field is the whole of
         * joining "free-users"; where no field has been named, the member
         * still lands on the list and simply matches no segment. */
        $planField = (string) config('acumbamail.plan_field');
        if ($planField !== '') {
            $merge[$planField] = $plan === 'free'
                ? (string) config('acumbamail.free_plan_value')
                : $plan;
        }

        $res = $this->call('addSubscriber', [
            'list_id' => (int) config('acumbamail.list_id'),
            'merge_fields' => $merge,
            'double_optin' => 0,
            'update_subscriber' => 1,
            'complete_json' => 1,
        ]);

        if ($res === null) {
            return false;
        }

        Log::info('Acumbamail: added '.$user->email.' to list '.config('acumbamail.list_id'));

        return true;
    }

    /** The lists this token can see — used to check the configured id is real. */
    public function lists(): ?array
    {
        return $this->call('getLists', []);
    }

    /** The list's own field names, which is what merge_fields must be keyed by. */
    public function fields(?int $listId = null): ?array
    {
        return $this->call('getFields', ['list_id' => $listId ?: (int) config('acumbamail.list_id')]);
    }

    /** Everything the list knows about its fields, values and visibility. */
    public function listFields(?int $listId = null): ?array
    {
        return $this->call('getListFields', ['list_id' => $listId ?: (int) config('acumbamail.list_id')]);
    }

    /** The saved rules a subscriber can fall into. Read-only, on their side too. */
    public function segments(?int $listId = null): ?array
    {
        return $this->call('getListSegments', ['list_id' => $listId ?: (int) config('acumbamail.list_id')]);
    }

    /** What the list holds about one address, across every list it is on. */
    public function findSubscriber(string $email): ?array
    {
        return $this->call('searchSubscriber', ['subscriber' => $email]);
    }

    /**
     * One road out to them.
     *
     * merge_fields is a dict, and a dict does not survive a flat form post —
     * it is sent as merge_fields[FirstName]=… , which is what their examples
     * show and what Laravel's asForm produces from a nested array.
     *
     * Returns the decoded body, or null when the call could not be made or
     * came back refused. Null is the caller's cue to carry on regardless.
     */
    private function call(string $fn, array $params): ?array
    {
        if (config('acumbamail.token') === '') {
            return null;
        }

        $url = config('acumbamail.base').'/'.$fn.'/';
        $body = array_merge([
            'auth_token' => config('acumbamail.token'),
            'response_type' => 'json',
        ], $params);

        try {
            $res = Http::timeout(self::TIMEOUT)->asForm()->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('Acumbamail '.$fn.' could not be reached: '.$e->getMessage());

            return null;
        }

        if (! $res->successful()) {
            Log::warning('Acumbamail '.$fn.' answered '.$res->status().': '.mb_substr($res->body(), 0, 400));

            return null;
        }

        $decoded = $res->json();

        // A bare id or a plain string is a fine answer from this API; wrap it
        // so the caller always gets an array back and never has to ask which.
        return is_array($decoded) ? $decoded : ['result' => $decoded];
    }
}
