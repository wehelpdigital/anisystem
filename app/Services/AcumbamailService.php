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

    /** What the last refused or failed call said, for the newsletter's screens. */
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** The account token alone: the newsletter's lists need no ACUMBAMAIL_LIST_ID. */
    public function tokenSet(): bool
    {
        return config('acumbamail.token') !== '';
    }

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

    /**
     * A lead, not a member: someone who gave an email on the public site (Try
     * and Ask Anee) without making an account. Lands on the same list with
     * the plan field stamped "lead" (ACUMBAMAIL_LEAD_VALUE), so a segment can
     * tell them from the free-users; any extra field the list does not have
     * is ignored on their side.
     *
     * @param  array<string, string>  $extra  merge field name => value
     */
    public function addLead(string $email, array $extra = []): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $f = config('acumbamail.fields');
        $merge = [$f['email'] => $email] + array_filter($extra, fn ($v) => (string) $v !== '');
        $planField = (string) config('acumbamail.plan_field');
        if ($planField !== '') {
            $merge[$planField] = (string) config('acumbamail.lead_value', 'lead');
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
        Log::info('Acumbamail: added lead '.$email.' to list '.config('acumbamail.list_id'));

        return true;
    }

    /**
     * A lead who is added only when the list does not have them yet (the
     * free tools' email gate, 2026-10-07). Anyone already on the list stays
     * exactly as they are: a member keeps Plan = free-users, and someone who
     * unsubscribed is not signed back up. When the search itself fails, the
     * add still goes out with update_subscriber off, so Acumbamail refuses an
     * address it already holds instead of rewriting it.
     *
     * @param  array<string, string>  $extra  merge field name => value
     * @return string  'added', 'exists', 'failed' or 'off'
     */
    public function addLeadIfNew(string $email, array $extra = []): string
    {
        if (! $this->configured()) {
            return 'off';
        }
        $listId = (int) config('acumbamail.list_id');
        $found = $this->findSubscriber($email);
        if (is_array($found)) {
            foreach ($found as $row) {
                if (is_array($row) && (int) ($row['list_id'] ?? 0) === $listId) {
                    return 'exists';
                }
            }
        }

        $f = config('acumbamail.fields');
        $merge = [$f['email'] => $email] + array_filter($extra, fn ($v) => (string) $v !== '');
        $planField = (string) config('acumbamail.plan_field');
        if ($planField !== '') {
            $merge[$planField] = (string) config('acumbamail.lead_value', 'lead');
        }
        $res = $this->call('addSubscriber', [
            'list_id' => $listId,
            'merge_fields' => $merge,
            'double_optin' => 0,
            'update_subscriber' => 0,
            'complete_json' => 1,
        ]);
        if ($res === null) {
            return 'failed';
        }
        Log::info('Acumbamail: added tool lead '.$email.' to list '.$listId);

        return 'added';
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

    /* ------------------------------------------------------------------
     * THE NEWSLETTER'S CALLS (2026-10-17). Its eight lists hold the email
     * and a name; who belongs on which is App\Support\Newsletter\Audience's
     * to say, and these only carry it out.
     * ------------------------------------------------------------------ */

    /** One address onto one list, kept up to date if it is there already (name and greet are list fields). */
    public function listAdd(int $listId, string $email, string $name = '', string $greet = ''): bool
    {
        $merge = ['email' => $email];
        if ($name !== '') {
            $merge['name'] = $name;
        }
        if ($greet !== '') {
            $merge['greet'] = $greet;
        }

        return $this->call('addSubscriber', [
            'list_id' => $listId,
            'merge_fields' => $merge,
            'double_optin' => 0,
            'update_subscriber' => 1,
            'complete_json' => 1,
        ]) !== null;
    }

    /**
     * Up to a thousand addresses onto one list in one call.
     *
     * @param  list<array{email: string, name?: string}>  $rows
     * @return array<string, string>|null  email => '' when added, or what was wrong
     */
    public function listBatchAdd(int $listId, array $rows): ?array
    {
        $data = [];
        foreach (array_slice($rows, 0, 1000) as $r) {
            $one = ['email' => (string) $r['email']];
            foreach (['name', 'greet'] as $f) {
                if (($r[$f] ?? '') !== '') {
                    $one[$f] = (string) $r[$f];
                }
            }
            $data[] = $one;
        }
        if (! $data) {
            return [];
        }
        $res = $this->call('batchAddSubscribers', [
            'list_id' => $listId,
            'update_subscriber' => 1,
            'subscribers_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'complete_json' => 1,
        ]);
        if ($res === null) {
            return null;
        }
        // Each answer row is {email: id} or {email, id} or {email: "what was
        // wrong"}; anything that is not a number is that address refused.
        $out = [];
        foreach ($data as $d) {
            $out[strtolower($d['email'])] = '';
        }
        foreach ((isset($res['result']) && is_array($res['result']) ? $res['result'] : $res) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $email = strtolower((string) ($row['email'] ?? array_key_first($row) ?? ''));
            $val = array_key_exists('id', $row) ? $row['id'] : ($row[$email] ?? reset($row));
            if ($email !== '' && isset($out[$email]) && ! is_numeric($val)) {
                $out[$email] = is_string($val) ? mb_substr($val, 0, 200) : 'refused';
            }
        }

        return $out;
    }

    /** Off one list altogether (they moved to another plan). */
    public function listRemove(int $listId, string $email): bool
    {
        return $this->call('deleteSubscriber', ['list_id' => $listId, 'email' => $email]) !== null;
    }

    /** Marked unsubscribed on one list (they asked to stop on another). */
    public function listUnsubscribe(int $listId, string $email): bool
    {
        return $this->call('unsubscribeSubscriber', ['list_id' => $listId, 'email' => $email]) !== null;
    }

    /**
     * A list's people in one state: 0 active, 2 unsubscribed, 3 hard bounced,
     * 4 complained. Ten thousand a block. An empty answer is an empty array,
     * not a failure ("No subscribers for selected parameters").
     *
     * @return array<string, mixed>|null  keyed by email
     */
    public function listSubscribers(int $listId, int $status = 0, int $block = 0): ?array
    {
        $res = $this->call('getSubscribers', ['list_id' => $listId, 'status' => $status, 'block_index' => $block], true);
        if ($res === null) {
            return str_contains((string) $this->lastError, 'No subscribers') ? [] : null;
        }

        return $res;
    }

    /** A field on a list (the name the newsletter keeps beside each email). */
    public function addListField(int $listId, string $name, string $type = 'text'): bool
    {
        return $this->call('addMergeTag', ['list_id' => $listId, 'field_name' => $name, 'field_type' => $type]) !== null;
    }

    /** The account's templates, as Acumbamail lists them (id, name, subject). */
    public function templates(): ?array
    {
        return $this->call('getTemplates', ['complete_json' => 1]);
    }

    /** A new list (the newsletter's test list), its id or null. */
    public function createList(string $name, string $senderEmail): ?int
    {
        $res = $this->call('createList', [
            'name' => $name, 'sender_email' => $senderEmail, 'company' => 'anee.io',
            'country' => 'Philippines', 'city' => 'Manila', 'address' => 'anee.io', 'phone' => '',
        ]);
        $id = $res['result'] ?? ($res['id'] ?? ($res['list_id'] ?? null));

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * A campaign of our own HTML to these lists: sent at once, or at
     * $dateSend (Manila time, "Y-m-d H:i"). Proven 2026-10-17 that the lists
     * travel as lists[0]=id: a made-up id is refused as "not your active
     * list" and an empty list as "no receivers".
     *
     * @param  list<int>  $listIds
     * @return array{id: int|null, error: string|null}
     */
    public function createCampaign(string $name, string $fromName, string $fromEmail, string $subject, string $html, array $listIds, ?string $dateSend = null): array
    {
        $p = [
            'name' => mb_substr($name, 0, 190),
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'subject' => $subject,
            'content' => $html,
            'lists' => array_values(array_map('intval', $listIds)),
            'tracking_urls' => 1,
            'complete_json' => 1,
        ];
        if ($dateSend) {
            $p['date_send'] = $dateSend;
        }

        return $this->campaignAnswer($this->call('createCampaign', $p, false, 40));
    }

    /**
     * The same for a template built in Acumbamail (the sequence written there
     * before this module): its words never leave Acumbamail.
     *
     * @param  list<int>  $listIds
     * @return array{id: int|null, error: string|null}
     */
    public function sendTemplateCampaign(string $name, string $fromName, string $fromEmail, string $subject, int $templateId, array $listIds, ?string $dateSend = null): array
    {
        $p = [
            'name' => mb_substr($name, 0, 190),
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'subject' => $subject,
            'template_id' => $templateId,
            'lists' => array_values(array_map('intval', $listIds)),
        ];
        if ($dateSend) {
            $p['date_send'] = $dateSend;
        }

        return $this->campaignAnswer($this->call('sendTemplateCampaign', $p, false, 40));
    }

    /** A campaign call answers with its id (bare or in a dict) or with what was wrong. */
    private function campaignAnswer(?array $res): array
    {
        if ($res === null) {
            return ['id' => null, 'error' => $this->lastError ?: 'Acumbamail could not be reached.'];
        }
        $id = $res['result'] ?? ($res['id'] ?? ($res['campaign_id'] ?? null));
        if (is_numeric($id)) {
            return ['id' => (int) $id, 'error' => null];
        }
        $this->lastError = mb_substr(is_string($id) ? $id : (string) json_encode($res), 0, 500);

        return ['id' => null, 'error' => $this->lastError];
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
    private function call(string $fn, array $params, bool $quiet = false, int $timeout = self::TIMEOUT): ?array
    {
        $this->lastError = null;
        if (config('acumbamail.token') === '') {
            $this->lastError = 'ACUMBAMAIL_TOKEN is not set.';

            return null;
        }

        $url = config('acumbamail.base').'/'.$fn.'/';
        $body = array_merge([
            'auth_token' => config('acumbamail.token'),
            'response_type' => 'json',
        ], $params);

        try {
            $res = Http::timeout($timeout)->asForm()->post($url, $body);
        } catch (\Throwable $e) {
            $this->lastError = 'Acumbamail could not be reached: '.$e->getMessage();
            Log::warning('Acumbamail '.$fn.' could not be reached: '.$e->getMessage());

            return null;
        }

        if (! $res->successful()) {
            // Their refusals come as {"error": ...} or {"status": "error", "message": ...}.
            $j = $res->json();
            $why = is_array($j) ? ($j['message'] ?? $j['error'] ?? null) : null;
            $this->lastError = mb_substr(is_string($why) ? $why : (is_array($why) ? (string) json_encode($why) : trim($res->body())), 0, 500) ?: 'Acumbamail answered '.$res->status().'.';
            if (! $quiet) {
                Log::warning('Acumbamail '.$fn.' answered '.$res->status().': '.mb_substr($res->body(), 0, 400));
            }

            return null;
        }

        $decoded = $res->json();

        // A bare id or a plain string is a fine answer from this API; wrap it
        // so the caller always gets an array back and never has to ask which.
        return is_array($decoded) ? $decoded : ['result' => $decoded];
    }
}
