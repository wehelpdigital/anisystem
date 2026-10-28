<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use App\Services\AcumbamailService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * WHO GETS WHICH EMAIL TODAY (2026-10-17, the owner).
 *
 * The rules, as the owner gave them:
 *   - one email a day per person, at most;
 *   - except a high priority email with an exact time, which goes at that
 *     time beside the day's email (runTimed);
 *   - high priority first, then today's news, then the regular line in the
 *     order it is dragged into;
 *   - news not sent today is gone tomorrow;
 *   - an email can be for some segments only, and the rest still get the
 *     next email in line, as a second campaign.
 *
 * HOW THE DAY IS DEALT. Acumbamail sends a campaign to whole lists, and a
 * person on two lists that get two campaigns gets two emails. The five plan
 * lists (Seg::TIERS) never overlap, so the day is dealt to them: each takes
 * the first email in line that is for it, and one campaign goes per email
 * to the lists it took. An email for "paid" or "credits" (Seg::FLAGS) could
 * reach a member of any plan, so it goes only on a day when every plan list
 * with members is still free, and takes all of them.
 *
 * An email that only some of its lists could take today is "partial": its
 * other lists take it on a later day, before anything after it (it keeps its
 * place). News and anything bound to a day expire when the day ends.
 *
 * FOUR DAYS A WEEK (2026-10-28, the owner: "to avoid spamming emails in
 * newsletter, adjust it only into 4 times a week"): the line is dealt only on
 * the send days (NlSettings::sendDays). On a day between, only an email bound
 * to that very day goes (a holiday greeting keeps its day); the news of the
 * days between is gathered into the next send day's news email.
 */
final class Planner
{
    /** The order of the line: lower goes first. */
    private const RANK = ['high-day' => 0, 'high' => 1, 'news' => 2, 'regular-day' => 3, 'regular' => 4];

    /** Acumbamail takes ten campaign calls a minute. */
    private const CALL_GAP_SECONDS = 7;

    /** When the morning's farm news roundup asks (bootstrap/app.php news:roundup), an hour before the 6:00 send. */
    public const NEWS_AT = '05:00';

    /** A builder email goes only with a SpamAssassin score of 0 (2026-10-17, the owner: "0 spam score"). */
    public const SPAM_MAX = 0.0;

    /** How many emails at the head of the line get a fresh spam check before the day is dealt. */
    private const SPAM_FRESH = 12;

    public static function now(): Carbon
    {
        return Carbon::now('Asia/Manila');
    }

    /* ============================================================ expiry */

    /** Close what can no longer go: news of an earlier day, a passed day, an hour long gone. */
    public static function expire(?Carbon $now = null): int
    {
        $now ??= self::now();
        $today = $now->toDateString();
        $n = AsNlEmail::active()->whereIn('status', AsNlEmail::LIVE)->where('priority', 'news')
            ->where(fn ($q) => $q->whereNull('newsDate')->orWhere('newsDate', '<', $today))
            ->update(['status' => 'expired', 'note' => 'News of a day that passed.', 'updated_at' => now()]);
        $n += AsNlEmail::active()->whereIn('status', AsNlEmail::LIVE)->whereNotNull('sendOn')->where('sendOn', '<', $today)
            ->update(['status' => 'expired', 'updated_at' => now()]);
        // A timed email whose hour passed by more than a few hours was missed (the scheduler was down).
        $n += AsNlEmail::active()->whereIn('status', AsNlEmail::LIVE)->where('priority', 'high')->whereNotNull('sendAt')
            ->where('sendAt', '<', $now->copy()->subHours(6))
            ->update(['status' => 'expired', 'note' => 'Its time passed before it could be sent.', 'updated_at' => now()]);

        return $n;
    }

    /* ============================================================== plan */

    /**
     * The emails in line for a day, in the order they are dealt.
     *
     * @param  Collection<int, AsNlEmail>|null  $emails
     * @return Collection<int, AsNlEmail>
     */
    public static function line(Carbon $day, ?Collection $emails = null): Collection
    {
        $d = $day->toDateString();
        $emails ??= AsNlEmail::active()->whereIn('status', AsNlEmail::LIVE)->get();

        $rest = ! NlSettings::isSendDay($day);

        return $emails->filter(function (AsNlEmail $e) use ($d, $rest) {
            if ($e->isTimed() || ! in_array($e->status, AsNlEmail::LIVE, true)) {
                return false;
            }
            // A day between the send days: only what is bound to this very day.
            if ($rest) {
                return $e->priority !== 'news' && $e->sendOn && $e->sendOn->toDateString() === $d;
            }
            if ($e->priority === 'news') {
                return $e->newsDate && $e->newsDate->toDateString() === $d;
            }
            if ($e->sendOn) {
                return $e->sendOn->toDateString() === $d;
            }
            if ($e->kind === 'acumba' && ! $e->acumbaTemplateId) {
                return false;
            }

            return true;
        })->sortBy(fn (AsNlEmail $e) => sprintf('%d-%09d-%09d', self::rank($e), $e->position + 100000000, $e->id))->values();
    }

    private static function rank(AsNlEmail $e): int
    {
        return match ($e->priority) {
            'high' => $e->sendOn ? self::RANK['high-day'] : self::RANK['high'],
            'news' => self::RANK['news'],
            default => $e->sendOn ? self::RANK['regular-day'] : self::RANK['regular'],
        };
    }

    /**
     * Deal one day.
     *
     * @param  array<string, int>|null  $counts  people per list (Audience::counts)
     * @param  list<string>  $taken  plan lists that already had an email today
     * @return array{date: string, counts: array<string, int>, picks: list<array>, waiting: list<array>, cells: array<string, array|null>}
     */
    public static function plan(Carbon $day, ?Collection $emails = null, ?array $counts = null, array $taken = []): array
    {
        $counts ??= Audience::counts();
        $lists = Seg::lists();
        $empty = array_values(array_filter(Seg::TIERS, fn ($c) => ($counts[$c] ?? 0) <= 0));
        $free = array_values(array_diff(Seg::TIERS, $empty, $taken));
        $members = array_values(array_filter(Seg::MEMBER_TIERS, fn ($c) => ($counts[$c] ?? 0) > 0));
        $picks = [];
        $waiting = [];
        $cells = array_fill_keys(Seg::TIERS, null);

        foreach (self::line($day, $emails) as $e) {
            $x = Seg::expand($e->aud());
            $sent = $e->sent();
            $base = ['emailId' => $e->id, 'title' => $e->title, 'subject' => $e->subject, 'priority' => $e->priority, 'kind' => $e->kind];

            // Only at a 0 spam score (2026-10-17, the owner): an email that scores higher waits for its fix,
            // and the next one in the line takes its day. No score yet (the checker was down) does not hold it.
            $spam = $e->kind === 'builder' ? SpamCheck::badge($e) : null;
            if ($spam && $spam['score'] !== null && (float) $spam['score'] > self::SPAM_MAX) {
                $waiting[] = $base + ['why' => 'Its spam score is ' . $spam['score'] . '. Open it in the builder: its badge shows what to change.'];

                continue;
            }

            if ($x['flags']) {
                $flag = $x['flags'][0];
                if (in_array($flag, $sent, true)) {
                    continue;
                }
                if (($counts[$flag] ?? 0) <= 0) {
                    $waiting[] = $base + ['why' => 'Nobody is on ' . Seg::LABELS[$flag][0] . ' yet.'];

                    continue;
                }
                if (! $members || array_diff($members, $free)) {
                    $waiting[] = $base + ['why' => 'Goes on a day when every plan group is still free (anyone on ' . Seg::LABELS[$flag][0] . ' could be in any of them).'];

                    continue;
                }
                $picks[] = $base + ['cells' => $members, 'send' => [$flag], 'lists' => [(int) $lists[$flag]], 'people' => (int) $counts[$flag]];
                foreach ($members as $c) {
                    $cells[$c] = ['emailId' => $e->id, 'title' => $e->title, 'priority' => $e->priority, 'via' => $flag];
                }
                $free = array_values(array_diff($free, $members));

                continue;
            }

            $targets = array_values(array_diff($x['tiers'], $sent, $empty));
            if (! $targets) {
                continue;
            }
            $take = array_values(array_intersect($targets, $free));
            if (! $take) {
                if ($free) {
                    $waiting[] = $base + ['why' => 'Its groups already have an email today.'];
                }

                continue;
            }
            // Everyone free gets it (2026-10-17, the owner: the news and the regular emails go to
            // "Everyone"): one campaign to the Everyone list, not one per plan list. The cells still
            // say which plan groups had their email today.
            $everyone = ! array_diff(array_diff(Seg::TIERS, $empty), $take) && ! empty($lists[Seg::ALL]);
            $picks[] = $base + [
                'cells' => $take, 'send' => $take,
                'lists' => $everyone ? [(int) $lists[Seg::ALL]] : array_map(fn ($c) => (int) $lists[$c], $take),
                'people' => $everyone && isset($counts[Seg::ALL]) ? (int) $counts[Seg::ALL] : array_sum(array_map(fn ($c) => (int) ($counts[$c] ?? 0), $take)),
                'rest' => array_values(array_diff($targets, $take)),
                'via' => $everyone ? Seg::ALL : null,
            ];
            foreach ($take as $c) {
                $cells[$c] = ['emailId' => $e->id, 'title' => $e->title, 'priority' => $e->priority, 'via' => null];
            }
            $free = array_values(array_diff($free, $take));
        }

        return ['date' => $day->toDateString(), 'counts' => $counts, 'picks' => $picks, 'waiting' => array_slice($waiting, 0, 12), 'cells' => $cells, 'empty' => $empty, 'taken' => $taken];
    }

    /**
     * The days ahead as things stand: each day dealt as if the one before
     * went out in full. Nothing is written; news is not known yet.
     */
    public static function forecast(int $days = 7, bool $fromToday = false): array
    {
        $counts = Audience::counts();
        $emails = AsNlEmail::active()->whereIn('status', AsNlEmail::LIVE)->get()->map(fn ($e) => clone $e);
        $out = [];
        $day = self::now()->startOfDay();
        if (! $fromToday) {
            // Today's own plan first, as if it went: tomorrow starts after it.
            $p = self::plan($day, $emails, $counts, self::takenToday());
            self::apply($emails, $p, $counts);
        }
        for ($i = 0; $i < $days; $i++) {
            $day = $day->copy()->addDay();
            $p = self::plan($day, $emails, $counts);
            $out[] = ['date' => $day->toDateString(), 'rest' => ! NlSettings::isSendDay($day), 'picks' => array_map(fn ($k) => array_intersect_key($k, array_flip(['emailId', 'title', 'priority', 'kind', 'cells', 'send', 'people'])), $p['picks'])];
            self::apply($emails, $p, $counts);
        }

        return $out;
    }

    /** In memory only: what the day's picks would change. */
    private static function apply(Collection $emails, array $plan, array $counts): void
    {
        $byId = $emails->keyBy('id');
        foreach ($plan['picks'] as $p) {
            $e = $byId[$p['emailId']] ?? null;
            if (! $e) {
                continue;
            }
            $e->sentCells = array_values(array_unique(array_merge($e->sent(), $p['send'])));
            $e->status = self::complete($e, $counts) ? 'sent' : 'partial';
        }
        foreach ($emails as $e) {
            if ($e->status === 'partial' && ($e->priority === 'news' || $e->sendOn)) {
                $e->status = 'expired';
            }
        }
    }

    /** Every list it was for that has people has had it. */
    public static function complete(AsNlEmail $e, array $counts): bool
    {
        $x = Seg::expand($e->aud());
        $sent = $e->sent();
        if ($x['flags']) {
            return in_array($x['flags'][0], $sent, true);
        }
        $empty = array_filter(Seg::TIERS, fn ($c) => ($counts[$c] ?? 0) <= 0);

        return ! array_diff($x['tiers'], $sent, $empty);
    }

    /** Plan lists that had a daily email today already (a run that stopped halfway). */
    public static function takenToday(): array
    {
        if (! Schema::hasTable('as_nl_runs')) {
            return [];
        }
        $runIds = DB::table('as_nl_runs')->where('runDate', self::now()->toDateString())->where('kind', 'daily')->pluck('id');
        $taken = [];
        foreach (DB::table('as_nl_campaigns')->whereIn('runId', $runIds)->where('status', 'created')->pluck('cells') as $c) {
            foreach ((array) json_decode((string) $c, true) as $cell) {
                $taken[$cell] = true;
            }
        }

        return array_keys($taken);
    }

    /* =============================================================== send */

    /** Whether this morning's news run is behind us (the daily email waits for it). */
    public static function newsSettled(Carbon $now): bool
    {
        if (! Schema::hasTable('as_news_runs')) {
            return true;
        }
        $send = Carbon::parse($now->toDateString() . ' ' . NlSettings::sendTime(), 'Asia/Manila');
        if ($now->gte($send->copy()->addHours(3))) {
            return true;
        }
        $runs = DB::table('as_news_runs')->where('created_at', '>=', $now->copy()->startOfDay())->orderByDesc('id')->get(['status', 'created_at']);
        if ($runs->isEmpty()) {
            // The roundup asks at NEWS_AT: give it half an hour before going without it.
            return $now->gte(Carbon::parse($now->toDateString() . ' ' . self::NEWS_AT, 'Asia/Manila')->addMinutes(30));
        }
        $last = $runs->first();

        return $last->status !== 'running' || Carbon::parse($last->created_at, 'Asia/Manila')->lt($now->copy()->subHours(2));
    }

    /**
     * The day's send. Safe to call every tick: it goes once a day, after the
     * send time and the morning's news, and a run that stopped halfway picks
     * up only what did not go.
     */
    public static function runDaily(bool $force = false): array
    {
        $now = self::now();
        $today = $now->toDateString();
        $send = Carbon::parse($today . ' ' . NlSettings::sendTime(), 'Asia/Manila');
        if (! $force && $now->lt($send)) {
            return ['status' => 'waiting', 'reason' => 'Before the send time (' . $send->format('g:i A') . ').'];
        }
        // A day between the send days goes quiet, unless an email is bound to it (a holiday greeting).
        if (! $force && ! NlSettings::isSendDay($now) && self::line($now)->isEmpty()) {
            return ['status' => 'rest', 'reason' => 'Not a send day. The next one is ' . NlSettings::nextSendDay($now, true)->format('l, M j') . '.'];
        }
        if (! $force && NlSettings::isSendDay($now) && ! self::newsSettled($now)) {
            return ['status' => 'waiting', 'reason' => 'Waiting for this morning\'s farm news.'];
        }

        return self::locked('nl-daily', function () use ($now, $today) {
            $run = DB::table('as_nl_runs')->where('runDate', $today)->where('kind', 'daily')->orderByDesc('id')->first();
            if ($run && in_array($run->status, ['done', 'dry'], true)) {
                return ['status' => 'already', 'reason' => 'Today\'s send is done.', 'runId' => $run->id];
            }
            if ($run && $run->status === 'failed' && (int) $run->attempts >= 3) {
                return ['status' => 'failed', 'reason' => 'Three tries failed today: ' . $run->error, 'runId' => $run->id];
            }
            if ($run && $run->status === 'running' && Carbon::parse($run->updated_at, 'Asia/Manila')->gt($now->copy()->subMinutes(15))) {
                return ['status' => 'busy', 'reason' => 'Another run is sending right now.', 'runId' => $run->id];
            }
            if ($run) {
                DB::table('as_nl_runs')->where('id', $run->id)->update(['status' => 'running', 'attempts' => (int) $run->attempts + 1, 'error' => null, 'updated_at' => now()]);
                $runId = (int) $run->id;
            } else {
                $runId = (int) DB::table('as_nl_runs')->insertGetId(['runDate' => $today, 'kind' => 'daily', 'status' => 'running', 'attempts' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }

            try {
                self::expire($now);
                NewsEmail::ensure($now);
                self::freshSpam($now);
                $sync = Audience::sync(null, 90);
                $counts = Audience::counts();
                $plan = self::plan($now, null, $counts, self::takenToday());
                $plan['sync'] = array_intersect_key($sync, array_flip(['added', 'removed', 'failed', 'errors']));

                if (! NlSettings::enabled()) {
                    DB::table('as_nl_runs')->where('id', $runId)->update([
                        'status' => 'dry', 'plan' => json_encode($plan), 'finishedAt' => now(), 'updated_at' => now(),
                        'error' => 'Sending is off: this is what would have gone.',
                    ]);

                    return ['status' => 'dry', 'runId' => $runId, 'plan' => $plan];
                }

                $errors = [];
                $made = 0;
                foreach ($plan['picks'] as $i => $p) {
                    if ($i > 0) {
                        sleep(self::CALL_GAP_SECONDS);
                    }
                    $e = AsNlEmail::find($p['emailId']);
                    $res = $e ? self::campaign($runId, $e, $p, $counts, $now) : ['error' => 'The email was removed.'];
                    if ($res['error'] ?? null) {
                        $errors[] = ($e->title ?? '#' . $p['emailId']) . ': ' . $res['error'];
                    } else {
                        $made++;
                    }
                    DB::table('as_nl_runs')->where('id', $runId)->update(['updated_at' => now()]);
                }
                $failedAll = $plan['picks'] && ! $made;
                DB::table('as_nl_runs')->where('id', $runId)->update([
                    'status' => $failedAll ? 'failed' : 'done',
                    'plan' => json_encode($plan),
                    'error' => $errors ? mb_substr(implode(' | ', $errors), 0, 2000) : null,
                    'finishedAt' => now(), 'updated_at' => now(),
                ]);

                return ['status' => $failedAll ? 'failed' : 'done', 'runId' => $runId, 'campaigns' => $made, 'errors' => $errors, 'plan' => $plan];
            } catch (\Throwable $e) {
                Log::error('Newsletter daily run failed: ' . $e->getMessage());
                DB::table('as_nl_runs')->where('id', $runId)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 2000), 'updated_at' => now()]);

                return ['status' => 'failed', 'runId' => $runId, 'reason' => $e->getMessage()];
            }
        });
    }

    /** High priority emails with an exact time: their own campaign, at their own time. */
    public static function runTimed(): array
    {
        $now = self::now();
        $due = AsNlEmail::active()->where('priority', 'high')->whereIn('status', AsNlEmail::LIVE)->whereNotNull('sendAt')
            ->where('sendAt', '<=', $now->copy()->addMinutes(12))->orderBy('sendAt')->get();
        if ($due->isEmpty() || ! NlSettings::enabled()) {
            return ['status' => $due->isEmpty() ? 'none' : 'off', 'due' => $due->count()];
        }

        return self::locked('nl-timed', function () use ($due, $now) {
            $counts = Audience::counts();
            $lists = Seg::lists();
            $out = [];
            foreach ($due as $i => $e) {
                $e->refresh();
                if (! in_array($e->status, AsNlEmail::LIVE, true)) {
                    continue;
                }
                $x = Seg::expand($e->aud());
                $send = $x['flags'] ?: array_values(array_diff($x['tiers'], $e->sent(), array_filter(Seg::TIERS, fn ($c) => ($counts[$c] ?? 0) <= 0)));
                if (! $send) {
                    $e->update(['status' => 'sent', 'note' => 'Nobody was on its lists at its time.']);

                    continue;
                }
                if ($i > 0) {
                    sleep(self::CALL_GAP_SECONDS);
                }
                $runId = (int) DB::table('as_nl_runs')->insertGetId(['runDate' => $now->toDateString(), 'kind' => 'timed', 'status' => 'running', 'attempts' => 1, 'created_at' => now(), 'updated_at' => now()]);
                $p = ['emailId' => $e->id, 'cells' => $x['flags'] ? [] : $send, 'send' => $send, 'lists' => array_map(fn ($c) => (int) $lists[$c], $send)];
                // Ahead of its hour, Acumbamail holds it until then.
                $at = $e->sendAt->copy()->timezone('Asia/Manila');
                $res = self::campaign($runId, $e, $p, $counts, $now, $at->gt($now->copy()->addMinute()) ? $at->format('Y-m-d H:i') : null);
                DB::table('as_nl_runs')->where('id', $runId)->update([
                    'status' => ($res['error'] ?? null) ? 'failed' : 'done',
                    'plan' => json_encode(['picks' => [$p + ['title' => $e->title, 'priority' => 'high']], 'at' => $at->toDateTimeString()]),
                    'error' => $res['error'] ?? null, 'finishedAt' => now(), 'updated_at' => now(),
                ]);
                $out[] = ['emailId' => $e->id, 'error' => $res['error'] ?? null];
            }

            return ['status' => 'done', 'sent' => $out];
        });
    }

    /** One Acumbamail campaign for one pick, written down whatever happens. */
    private static function campaign(int $runId, AsNlEmail $e, array $p, array $counts, Carbon $now, ?string $dateSend = null): array
    {
        $am = app(AcumbamailService::class);
        $label = ($p['via'] ?? null) === Seg::ALL || ! $p['send'] ? 'everyone' : Seg::words($p['send']);
        $name = 'anee.io ' . $now->format('Y-m-d') . ' · ' . mb_substr($e->title, 0, 80) . ' · ' . $label;
        $subject = trim((string) $e->subject) !== '' ? $e->subject : $e->title;

        if ($e->kind === 'acumba') {
            $res = $am->sendTemplateCampaign($name, NlSettings::fromName(), NlSettings::fromEmail(), $subject, (int) $e->acumbaTemplateId, $p['lists'], $dateSend);
        } else {
            $html = Renderer::html(['subject' => $subject, 'preheader' => $e->preheader, 'content' => $e->blocks], Theme::get(), 'live', 'nl-' . $e->id);
            $res = $am->createCampaign($name, NlSettings::fromName(), NlSettings::fromEmail(), $subject, $html, $p['lists'], $dateSend);
        }

        DB::table('as_nl_campaigns')->insert([
            'runId' => $runId, 'emailId' => $e->id,
            'cells' => json_encode($p['cells'] ?: $p['send']), 'lists' => json_encode($p['lists']),
            'acumbaCampaignId' => $res['id'], 'status' => $res['id'] ? 'created' : 'failed',
            'error' => $res['error'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (! $res['id']) {
            return ['error' => $res['error'] ?: 'Acumbamail did not make the campaign.'];
        }
        $e->sentCells = array_values(array_unique(array_merge($e->sent(), $p['send'])));
        $e->lastSentAt = now();
        $e->status = self::complete($e, $counts) ? 'sent' : 'partial';
        $e->save();

        return ['id' => $res['id']];
    }

    /** The emails at the head of today's line get a spam score for their current words before the day is dealt. */
    private static function freshSpam(Carbon $now): void
    {
        foreach (self::line($now)->take(self::SPAM_FRESH) as $e) {
            if ($e->kind !== 'builder' || SpamCheck::badge($e)) {
                continue;
            }
            try {
                SpamCheck::forSaved($e);
            } catch (\Throwable $x) {
                report($x);
            }
        }
    }

    private static function locked(string $key, \Closure $job): array
    {
        try {
            $lock = Cache::lock($key, 600);
            if (! $lock->get()) {
                return ['status' => 'busy', 'reason' => 'Another run holds the lock.'];
            }
        } catch (\Throwable $e) {
            $lock = null;
        }
        try {
            return $job();
        } finally {
            $lock?->release();
        }
    }
}
