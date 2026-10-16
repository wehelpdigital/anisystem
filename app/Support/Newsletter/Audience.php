<?php

namespace App\Support\Newsletter;

use App\Models\AsSiteSetting;
use App\Models\User;
use App\Services\AcumbamailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * WHO IS ON WHICH LIST (2026-10-17, the owner: "adapt all our registrations
 * and payments into these segments ... if they upgraded, move them up").
 *
 * desired() works out, from anee.io's own tables, where every address
 * belongs: a member on "all", on the list of their plan, and on "paid" and
 * "credits" when they ever paid or bought credits; a visitor who asked Anee
 * on the public site (no account) on "all" and "not members yet". sync()
 * makes Acumbamail agree, by difference against as_nl_audience (the mirror
 * of what was last put there), so a run that changes nothing calls nothing.
 *
 * The doors that change someone's place (a verified signup, an approved or
 * revoked order, a plan set by hand, an Ask Anee visitor) call touchUser()
 * or touchLead(), which syncs that one address after the response; the
 * scheduler's tick syncs everyone (a plan that ran out, a change the mother
 * app made in the database) before every send.
 *
 * UNSUBSCRIBES ARE FINAL. Acumbamail unsubscribes a person from the one list
 * the campaign went to; pullUnsubscribes() reads every list's unsubscribed,
 * bounced and complaining people, marks them here, and unsubscribes them
 * from every other list they are on. Nobody marked is ever added again.
 */
final class Audience
{
    /** Addresses that are nobody's inbox: test accounts, seeds, the house AI. */
    private const SKIP_DOMAINS = ['example.com', 'example.org', 'example.net', 'themesbrand.com', 'anisenso.system'];

    private const SKIP_ENDS = ['.test', '.local', '.invalid', '.example', '.localhost'];

    /** Acumbamail's states that end a person's mail: unsubscribed, hard bounced, complained. */
    private const GONE_STATES = [2 => 'unsubscribed', 3 => 'bounced', 4 => 'complained'];

    public static function skip(string $email): bool
    {
        $email = strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, User::SYSTEM_EMAILS, true)) {
            return true;
        }
        $domain = (string) substr(strrchr($email, '@') ?: '', 1);
        if (in_array($domain, self::SKIP_DOMAINS, true)) {
            return true;
        }
        foreach (self::SKIP_ENDS as $end) {
            if (str_ends_with($domain, $end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Where everyone (or these addresses) belongs.
     *
     * @param  list<string>|null  $only  lower-case emails, or null for everyone
     * @return array<string, array{name: string, userId: int|null, cells: list<string>}>
     */
    public static function desired(?array $only = null): array
    {
        $now = Carbon::now('Asia/Manila');
        $out = [];

        $users = DB::table('anisystem_users')
            ->where('deleteStatus', 1)->where('status', 'active')
            ->when($only !== null, fn ($q) => $q->whereIn(DB::raw('LOWER(email)'), $only ?: ['-']))
            ->get(['id', 'email', 'firstName', 'lastName', 'adminUserId', 'panelAdmin']);
        $ids = $users->pluck('id')->all();

        // The plan in force, per member, read in one go (User::activeSubscription's rules).
        $tier = [];
        if ($ids) {
            foreach (DB::table('anisystem_subscriptions')->whereIn('userId', $ids)
                ->where('deleteStatus', 1)->where('status', 'active')->where('expiresAt', '>', $now)
                ->where(fn ($q) => $q->whereNull('startsAt')->orWhere('startsAt', '<=', $now))
                ->orderByDesc('id')->get(['userId', 'planKey', 'planName']) as $s) {
                $tier[$s->userId] ??= User::tierForPlan($s->planKey, $s->planName);
            }
        }
        [$paid, $credits] = self::buyers($ids);

        foreach ($users as $u) {
            $email = strtolower(trim((string) $u->email));
            if (self::skip($email)) {
                continue;
            }
            $t = (! empty($u->adminUserId) || ! empty($u->panelAdmin)) ? 'admin' : ($tier[$u->id] ?? 'libre');
            $cells = [Seg::ALL, Seg::tierCell($t)];
            if (isset($paid[$u->id])) {
                $cells[] = Seg::PAID;
            }
            if (isset($credits[$u->id])) {
                $cells[] = Seg::CREDITS;
            }
            $out[$email] = ['name' => trim($u->firstName . ' ' . $u->lastName), 'userId' => (int) $u->id, 'cells' => $cells];
        }

        // Visitors who left an email on Try and Ask Anee, and anyone a lead
        // door recorded here (touchLead): not members, unless they joined.
        $leads = [];
        if (Schema::hasTable('as_ask_questions')) {
            foreach (DB::table('as_ask_questions')->where('deleteStatus', 1)->whereNotNull('email')->where('email', '!=', '')
                ->when($only !== null, fn ($q) => $q->whereIn(DB::raw('LOWER(email)'), $only ?: ['-']))
                ->orderBy('id')->get(['email', 'name']) as $q) {
                $leads[strtolower(trim((string) $q->email))] = trim((string) $q->name);
            }
        }
        foreach (DB::table('as_nl_audience')->whereNull('userId')
            ->when($only !== null, fn ($q) => $q->whereIn('email', $only ?: ['-']))
            ->get(['email', 'name']) as $r) {
            $leads[$r->email] ??= (string) $r->name;
        }
        foreach ($leads as $email => $name) {
            if (isset($out[$email]) || self::skip($email)) {
                continue;
            }
            $out[$email] = ['name' => $name, 'userId' => null, 'cells' => [Seg::ALL, Seg::NONUSERS]];
        }

        return $out;
    }

    /**
     * Members who ever paid anything (a plan, a tool pack, credits), and who
     * ever bought credits: what was bought and not taken back (SalesLedger's
     * rules). Plans given by hand cost nothing and do not count.
     *
     * @return array{0: array<int, true>, 1: array<int, true>}
     */
    private static function buyers(array $ids): array
    {
        if (! $ids) {
            return [[], []];
        }
        $paid = [];
        $credits = [];
        foreach (DB::table('anisystem_subscriptions')->whereIn('userId', $ids)->where('deleteStatus', 1)
            ->whereIn('status', ['active', 'expired'])->where('price', '>', 0)->distinct()->pluck('userId') as $id) {
            $paid[(int) $id] = true;
        }
        foreach (DB::table('anisystem_ai_credit_purchases')->whereIn('userId', $ids)->where('deleteStatus', 1)->where('price', '>', 0)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['revoked', 'cancelled', 'refunded', 'failed', 'pending', 'rejected']))
            ->distinct()->pluck('userId') as $id) {
            $paid[(int) $id] = true;
            $credits[(int) $id] = true;
        }
        if (Schema::hasTable('as_user_addons')) {
            foreach (DB::table('as_user_addons')->whereIn('userId', $ids)->where('price', '>', 0)
                ->whereNotIn('status', ['revoked', 'cancelled', 'refunded'])->distinct()->pluck('userId') as $id) {
                $paid[(int) $id] = true;
            }
        }

        return [$paid, $credits];
    }

    /**
     * Make Acumbamail agree with desired(), for these addresses or everyone.
     *
     * @param  list<string>|null  $only
     * @return array{checked: int, added: int, removed: int, failed: int, skipped: int, done: bool, errors: list<string>}
     */
    public static function sync(?array $only = null, int $budgetSeconds = 50): array
    {
        $stats = ['checked' => 0, 'added' => 0, 'removed' => 0, 'failed' => 0, 'skipped' => 0, 'done' => true, 'errors' => []];
        if (! Schema::hasTable('as_nl_audience')) {
            $stats['errors'][] = 'The newsletter tables are not there yet.';

            return $stats;
        }
        $am = app(AcumbamailService::class);
        if (! $am->tokenSet()) {
            $stats['errors'][] = 'ACUMBAMAIL_TOKEN is not set.';

            return $stats;
        }
        $only = $only === null ? null : array_values(array_unique(array_map(fn ($e) => strtolower(trim((string) $e)), $only)));
        $want = self::desired($only);
        $have = DB::table('as_nl_audience')
            ->when($only !== null, fn ($q) => $q->whereIn('email', $only ?: ['-']))
            ->get()->keyBy('email');
        $lists = Seg::lists();
        $started = microtime(true);

        // What each address is missing and carrying, list by list.
        $adds = [];
        $removes = [];
        foreach (array_unique(array_merge(array_keys($want), $have->keys()->all())) as $email) {
            $stats['checked']++;
            $row = $have[$email] ?? null;
            if ($row && (int) $row->unsubscribed === 1) {
                $stats['skipped']++;

                continue;
            }
            $w = $want[$email]['cells'] ?? [];
            $h = $row ? array_values(array_filter((array) json_decode((string) $row->cells, true), 'is_string')) : [];
            foreach (array_diff($w, $h) as $cell) {
                $adds[$cell][$email] = $want[$email]['name'] ?? '';
            }
            foreach (array_diff($h, $w) as $cell) {
                $removes[$cell][] = $email;
            }
        }

        $gained = [];
        $lost = [];
        $errs = [];
        foreach ($adds as $cell => $people) {
            $listId = (int) ($lists[$cell] ?? 0);
            if ($listId <= 0) {
                continue;
            }
            foreach (array_chunk($people, 1000, true) as $chunk) {
                if (microtime(true) - $started > $budgetSeconds) {
                    $stats['done'] = false;
                    break 2;
                }
                $rows = [];
                foreach ($chunk as $email => $name) {
                    $rows[] = self::fieldsFor($email, $name);
                }
                $res = count($rows) === 1
                    ? ($am->listAdd($listId, $rows[0]['email'], $rows[0]['name'], $rows[0]['greet']) ? [$rows[0]['email'] => ''] : null)
                    : $am->listBatchAdd($listId, $rows);
                if ($res === null) {
                    $why = $am->lastError() ?: 'Acumbamail refused.';
                    foreach ($chunk as $email => $_) {
                        $errs[$email] = Seg::LABELS[$cell][0] . ': ' . $why;
                    }
                    $stats['failed'] += count($chunk);
                    $stats['errors'][] = Seg::LABELS[$cell][0] . ': ' . $why;

                    continue;
                }
                foreach ($res as $email => $why) {
                    if ($why === '') {
                        $gained[$email][] = $cell;
                        $stats['added']++;
                    } else {
                        $errs[$email] = Seg::LABELS[$cell][0] . ': ' . $why;
                        $stats['failed']++;
                    }
                }
            }
        }
        foreach ($removes as $cell => $emails) {
            $listId = (int) ($lists[$cell] ?? 0);
            foreach ($emails as $email) {
                if (microtime(true) - $started > $budgetSeconds) {
                    $stats['done'] = false;
                    break 2;
                }
                // A list id that is gone, or an address already off it, is off it.
                if ($listId <= 0 || $am->listRemove($listId, $email) || preg_match('/does not exist|no existe|not found|not in (the|this) list/i', (string) $am->lastError())) {
                    $lost[$email][] = $cell;
                    $stats['removed']++;
                } else {
                    $errs[$email] = 'Off ' . Seg::LABELS[$cell][0] . ': ' . ($am->lastError() ?: 'refused');
                    $stats['failed']++;
                }
            }
        }

        // The mirror: what each address now holds at Acumbamail.
        $now = now();
        foreach (array_unique(array_merge(array_keys($gained), array_keys($lost), array_keys($errs), array_keys($want))) as $email) {
            $row = $have[$email] ?? null;
            if ($row && (int) $row->unsubscribed === 1) {
                continue;
            }
            $h = $row ? array_values(array_filter((array) json_decode((string) $row->cells, true), 'is_string')) : [];
            $cells = array_values(array_unique(array_diff(array_merge($h, $gained[$email] ?? []), $lost[$email] ?? [])));
            $w = $want[$email] ?? null;
            $fields = [
                'cells' => json_encode($cells),
                'syncedAt' => $now,
                'error' => isset($errs[$email]) ? mb_substr($errs[$email], 0, 600) : null,
                'updated_at' => $now,
            ];
            if ($w) {
                $fields['name'] = mb_substr($w['name'], 0, 190) ?: ($row->name ?? null);
                $fields['userId'] = $w['userId'];
            }
            if ($row) {
                $same = $row->cells === $fields['cells'] && (string) $row->error === (string) $fields['error']
                    && (! $w || ((string) $row->name === (string) $fields['name'] && (int) $row->userId === (int) $w['userId']));
                if ($same && empty($gained[$email]) && empty($lost[$email])) {
                    continue;
                }
                DB::table('as_nl_audience')->where('id', $row->id)->update($fields);
            } elseif ($cells || isset($errs[$email])) {
                DB::table('as_nl_audience')->insert($fields + ['email' => $email, 'unsubscribed' => 0, 'created_at' => $now]);
            }
        }
        $stats['errors'] = array_values(array_unique(array_slice($stats['errors'], 0, 8)));
        AsSiteSetting::put('newsletter.synced_at', $now->toDateTimeString());

        return $stats;
    }

    /** The member's address, synced after the response (a signup, an order, a plan). */
    public static function touchUser(int $userId, ?string $oldEmail = null): void
    {
        $emails = array_filter([
            strtolower((string) DB::table('anisystem_users')->where('id', $userId)->value('email')),
            strtolower(trim((string) $oldEmail)),
        ], fn ($e) => $e !== '' && ! self::skip($e));
        if (! $emails || ! Schema::hasTable('as_nl_audience')) {
            return;
        }
        self::later(fn () => self::sync(array_values(array_unique($emails)), 20));
    }

    /** A visitor's address (Try and Ask Anee), kept as a lead and synced after the response. */
    public static function touchLead(string $email, string $name = ''): void
    {
        $email = strtolower(trim($email));
        if (self::skip($email) || ! Schema::hasTable('as_nl_audience')) {
            return;
        }
        if (! DB::table('as_nl_audience')->where('email', $email)->exists()) {
            DB::table('as_nl_audience')->insert([
                'email' => $email, 'userId' => null, 'name' => mb_substr(trim($name), 0, 190) ?: null,
                'cells' => '[]', 'unsubscribed' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        self::later(fn () => self::sync([$email], 20));
    }

    /** After the response when there is one; a console run does it at its end. */
    private static function later(\Closure $job): void
    {
        $safe = function () use ($job) {
            try {
                $job();
            } catch (\Throwable $e) {
                Log::warning('Newsletter audience sync failed: ' . $e->getMessage());
            }
        };
        app()->runningInConsole() ? $safe() : app()->terminating($safe);
    }

    /**
     * Read who left, one list a call (Acumbamail allows ten reads a minute):
     * mark them here and unsubscribe them from every other list they are on.
     *
     * @return array{list: string, gone: int, newly: int, error: string|null}
     */
    public static function pullUnsubscribes(?string $cell = null): array
    {
        $am = app(AcumbamailService::class);
        $lists = Seg::lists();
        $keys = array_keys($lists);
        if ($cell === null) {
            $i = (int) AsSiteSetting::get('newsletter.pull_at', 0) % count($keys);
            $cell = $keys[$i];
            AsSiteSetting::put('newsletter.pull_at', (string) ($i + 1));
        }
        $out = ['list' => $cell, 'gone' => 0, 'newly' => 0, 'error' => null];
        $gone = [];
        foreach (self::GONE_STATES as $state => $word) {
            $res = $am->listSubscribers((int) $lists[$cell], $state);
            if ($res === null) {
                $out['error'] = $am->lastError();

                continue;
            }
            foreach (array_keys($res) as $email) {
                $email = strtolower(trim((string) $email));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $gone[$email] = $word;
                }
            }
        }
        $out['gone'] = count($gone);
        if (! $gone) {
            return $out;
        }
        $rows = DB::table('as_nl_audience')->whereIn('email', array_keys($gone))->get()->keyBy('email');
        foreach ($gone as $email => $word) {
            $row = $rows[$email] ?? null;
            if ($row && (int) $row->unsubscribed === 1) {
                continue;
            }
            $out['newly']++;
            $cells = $row ? array_values(array_filter((array) json_decode((string) $row->cells, true), 'is_string')) : [];
            foreach ($cells as $c) {
                if ($c !== $cell && isset($lists[$c])) {
                    $am->listUnsubscribe((int) $lists[$c], $email);
                }
            }
            $fields = ['unsubscribed' => 1, 'error' => ucfirst($word) . ' (from ' . Seg::LABELS[$cell][0] . ')', 'syncedAt' => now(), 'updated_at' => now()];
            $row ? DB::table('as_nl_audience')->where('id', $row->id)->update($fields)
                : DB::table('as_nl_audience')->insert($fields + ['email' => $email, 'cells' => json_encode([$cell]), 'created_at' => now()]);
        }

        return $out;
    }

    /**
     * How many can be reached on each list right now (the mirror, minus the
     * unsubscribed).
     *
     * @return array<string, int>
     */
    public static function counts(): array
    {
        $out = array_fill_keys(array_keys(Seg::LABELS), 0);
        if (! Schema::hasTable('as_nl_audience')) {
            return $out;
        }
        foreach (DB::table('as_nl_audience')->where('unsubscribed', 0)->pluck('cells') as $cells) {
            foreach ((array) json_decode((string) $cells, true) as $c) {
                if (isset($out[$c])) {
                    $out[$c]++;
                }
            }
        }

        return $out;
    }

    /** A subscriber's list fields: *|NAME|* the first name, *|GREET|* the same or "kaibigan" (MergeTags). */
    public static function fieldsFor(string $email, string $name): array
    {
        $first = self::firstName($name);

        return ['email' => $email, 'name' => $first, 'greet' => $first !== '' ? $first : MergeTags::GREET_FALLBACK];
    }

    /**
     * Write every reachable person's name and greeting onto each list they
     * are on (after a field is added, or names changed): a batch per list.
     * Each list's unsubscribes are read first, so nobody who left is written
     * back, and a pause between lists keeps under Acumbamail's call limit.
     *
     * @return array<string, string>  segment => what happened
     */
    public static function pushFields(int $pause = 20): array
    {
        $am = app(AcumbamailService::class);
        $want = self::desired();
        $out = [];
        $first = true;
        foreach (Seg::lists() as $cell => $listId) {
            if (! $first && $pause > 0) {
                sleep($pause);
            }
            $first = false;
            $pulled = self::pullUnsubscribes($cell);
            if ($pulled['error']) {
                $out[$cell] = 'skipped, its unsubscribes could not be read: ' . $pulled['error'];

                continue;
            }
            $rows = [];
            foreach (DB::table('as_nl_audience')->where('unsubscribed', 0)->where('cells', 'like', '%"' . $cell . '"%')->get(['email', 'name']) as $r) {
                $rows[] = self::fieldsFor($r->email, (string) ($want[$r->email]['name'] ?? $r->name));
            }
            if (! $rows) {
                $out[$cell] = 'nobody on it';

                continue;
            }
            $bad = 0;
            foreach (array_chunk($rows, 1000) as $chunk) {
                $res = $am->listBatchAdd((int) $listId, $chunk);
                $bad += $res === null ? count($chunk) : count(array_filter($res));
            }
            $out[$cell] = count($rows) . ' updated' . ($bad ? ', ' . $bad . ' refused: ' . $am->lastError() : '');
        }

        return $out;
    }

    /** "Juan" from "Juan Dela Cruz": the name an email can greet. */
    private static function firstName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $name === '' ? '' : mb_substr(explode(' ', $name)[0], 0, 60);
    }
}
