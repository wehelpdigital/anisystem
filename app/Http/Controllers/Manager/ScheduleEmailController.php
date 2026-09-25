<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleWorker;
use App\Services\MailService;
use App\Support\EmailSkin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sending a day, or one job, to the people who have to do it.
 *
 * The morning digest goes out on its own schedule to whoever the season is
 * set up to tell. This is the other thing an owner needs: standing on the
 * board looking at Thursday, deciding that Nena and Boyet had better know
 * about it now, and saying so.
 *
 * Nothing here invents an audience. The day's list offers every worker on the
 * season; one activity offers the workers on that activity and nobody else.
 * A worker with no address on file is offered and refused in the same breath
 * — shown, named, and locked — because "why is Nena not in this list" is a
 * worse question than "why is Nena greyed out", which answers itself.
 *
 * Both errands also take other addresses typed by hand (the buyer, the
 * agronomist, a helper who is not on the season) and a short note from the
 * sender. Those are the only strangers this file ever writes to, so they are
 * counted: a few to a message, a few dozen an hour.
 */
class ScheduleEmailController extends BaseScheduleController
{
    /** Typed addresses allowed on one send. */
    private const MAX_EXTRA = 10;

    /** Typed addresses one person may send to in an hour, across every send. */
    private const EXTRA_PER_HOUR = 60;

    /** The sender's note, in characters. */
    private const MAX_NOTE = 1000;

    public function __construct(private MailService $mail)
    {
    }

    /**
     * Who could be written to about a day, or about one activity.
     *
     * Answered from the server rather than read off the board, because the
     * board only knows the workers it happens to have drawn.
     */
    public function audience(Request $request)
    {
        // A read: who COULD be written to.
        $schedule = $this->mine($request);

        $activityId = (int) $request->query('activityId');
        if ($activityId) {
            $activity = AsScheduleActivity::active()
                ->where('croppingScheduleId', $schedule->id)
                ->with('workers')
                ->find($activityId);
            if (! $activity) {
                return $this->jsonFail('Activity not found.', 404);
            }
            $workers = $activity->workers;
            $what = (string) $activity->activityTitle;
            $when = $activity->targetDate;
        } else {
            $date = $this->readDate($request);
            if (! $date) {
                return $this->jsonFail('Which day?', 422);
            }
            $workers = $schedule->workers()->where('as_schedule_workers.deleteStatus', 1)->get();
            $what = null;
            $when = $date;
            // What the sheet says is going out: "3 activities".
            $count = AsScheduleActivity::active()
                ->where('croppingScheduleId', $schedule->id)
                ->where('isDraft', 0)
                ->where('isHidden', 0)
                ->whereDate('targetDate', $date->toDateString())
                ->count();
        }

        return response()->json(['success' => true, 'data' => [
            'title' => $what,
            'dateLabel' => $when ? Carbon::parse($when)->format('l, M j, Y') : null,
            'count' => $count ?? null,
            'maxExtra' => self::MAX_EXTRA,
            'maxNote' => self::MAX_NOTE,
            'workers' => $workers->map(fn ($w) => [
                'id' => (int) $w->id,
                'name' => (string) $w->workerName,
                'email' => (string) ($w->email ?: ''),
                // The one thing the checklist needs to know about each row.
                'reachable' => filled($w->email),
            ])->values()->all(),
        ]]);
    }

    /** Send a day's work to the chosen workers. */
    public function sendDay(Request $request)
    {
        // Sending is a write in the sense that matters: only somebody who
        // may run the plan gets to speak to the farm in its name.
        $schedule = $this->mine($request, true);

        $date = $this->readDate($request);
        if (! $date) {
            return $this->jsonFail('Which day?', 422);
        }

        $activities = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 0)
            ->where('isHidden', 0)
            ->whereDate('targetDate', $date->toDateString())
            ->with(['lots', 'workers'])
            ->orderBy('sequenceOrder')
            ->get();

        if ($activities->isEmpty()) {
            return $this->jsonFail('There is nothing planned on that day to send.', 422);
        }

        $to = $this->recipients($request, $schedule);
        if (is_string($to)) {
            return $this->jsonFail($to, 422);
        }

        $label = $date->format('l, M j, Y');
        $sentBy = optional($request->user())->full_name ?: 'the farm';
        // The sender's note sits above the work, in every copy.
        $table = $this->notePanel($request, $sentBy) . $this->tasksTable($activities);
        $sent = 0;

        foreach ($to as $person) {
            /* Each person is told about the whole day, not only their own
             * jobs. An owner reaching for this button has decided that this
             * day matters to these people; narrowing it behind their back
             * would send somebody an email that says nothing. A typed
             * address has no name on file, so it is greeted "Hi there". */
            $ok = $this->mail->sendTemplate('day_schedule', $person['email'], $person['name'], [
                'workerName' => e($person['name'] ?: 'there'),
                'scheduleTitle' => (string) $schedule->title,
                'dateLabel' => $label,
                'tasksTable' => $table,
                'sentBy' => e($sentBy),
            ], [
                'relatedType' => 'schedule_day',
                'croppingScheduleId' => $schedule->id,
            ]);
            $sent += $ok ? 1 : 0;
        }

        return response()->json([
            'success' => true,
            'message' => $this->said($sent, count($to)),
            'data' => ['sentTo' => array_column($to, 'email')],
        ]);
    }

    /** Send one activity to the workers on it. */
    public function sendActivity(Request $request)
    {
        $schedule = $this->mine($request, true);

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->with(['lots', 'workers', 'items'])
            ->find((int) $request->input('activityId'));
        if (! $activity) {
            return $this->jsonFail('Activity not found.', 404);
        }

        $to = $this->recipients($request, $schedule, $activity);
        if (is_string($to)) {
            return $this->jsonFail($to, 422);
        }

        $label = $activity->targetDate ? Carbon::parse($activity->targetDate)->format('l, M j, Y') : 'a day yet to be set';
        $sentBy = optional($request->user())->full_name ?: 'the farm';
        $panel = $this->notePanel($request, $sentBy) . $this->activityPanel($activity);
        $sent = 0;

        foreach ($to as $person) {
            $ok = $this->mail->sendTemplate('activity_notice', $person['email'], $person['name'], [
                'workerName' => e($person['name'] ?: 'there'),
                'scheduleTitle' => (string) $schedule->title,
                'activityTitle' => (string) $activity->activityTitle,
                'dateLabel' => $label,
                'activityBody' => $panel,
                'sentBy' => e($sentBy),
            ], [
                'relatedType' => 'activity',
                'relatedId' => $activity->id,
                'croppingScheduleId' => $schedule->id,
            ]);
            $sent += $ok ? 1 : 0;
        }

        return response()->json([
            'success' => true,
            'message' => $this->said($sent, count($to)),
            'data' => ['sentTo' => array_column($to, 'email')],
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * This farm's schedule, and the right to act on it.
     *
     * The base class's scheduleFromRequest reads the QUERY string, and these
     * endpoints are POSTs carrying the id in the body — so the id is read
     * from either and the same two questions are asked by hand.
     */
    private function mine(Request $request, bool $write = false): AsCroppingSchedule
    {
        $schedule = $this->schedule(
            (int) ($request->input('scheduleId') ?: $request->query('scheduleId') ?: $request->query('id'))
        );

        if ($write) {
            $this->assertCanEdit();
            $this->assertUnlocked($schedule);
        }

        return $schedule;
    }

    /**
     * Everybody this send goes to: the chosen workers, then the typed
     * addresses, one message per address.
     *
     * Returns a list of ['email', 'name'], or a sentence saying what is
     * wrong, which the caller hands back as a 422. A typed address that is
     * already a chosen worker's is dropped rather than refused: the person
     * gets one email, addressed by name.
     */
    private function recipients(Request $request, AsCroppingSchedule $schedule, ?AsScheduleActivity $activity = null): array|string
    {
        $note = trim((string) $request->input('message', ''));
        if (mb_strlen($note) > self::MAX_NOTE) {
            return 'The message can be up to ' . number_format(self::MAX_NOTE) . ' characters.';
        }

        // Typed addresses: an array, or one pasted string of them.
        $raw = $request->input('emails', []);
        $raw = is_array($raw) ? $raw : preg_split('/[\s,;]+/', (string) $raw);
        if (count($raw) > self::MAX_EXTRA * 3) {
            return 'Up to ' . self::MAX_EXTRA . ' other addresses at a time.';
        }
        $typed = [];
        $bad = [];
        foreach ($raw as $one) {
            $one = strtolower(trim(is_scalar($one) ? (string) $one : ''));
            if ($one === '') {
                continue;
            }
            if (strlen($one) > 190 || ! filter_var($one, FILTER_VALIDATE_EMAIL)) {
                $bad[] = mb_strimwidth($one, 0, 60, '…');

                continue;
            }
            $typed[$one] = true;
        }
        if ($bad) {
            return (count($bad) === 1 ? 'This is not an email address: ' : 'These are not email addresses: ')
                . implode(', ', array_slice($bad, 0, 3)) . (count($bad) > 3 ? '…' : '') . '.';
        }
        if (count($typed) > self::MAX_EXTRA) {
            return 'Up to ' . self::MAX_EXTRA . ' other addresses at a time.';
        }

        $to = [];
        foreach ($this->chosenWorkers($request, $schedule, $activity) as $worker) {
            $key = strtolower(trim((string) $worker->email));
            // Two workers sharing one inbox get one email, not two.
            $to[$key] ??= ['email' => trim((string) $worker->email), 'name' => (string) $worker->workerName];
        }

        $outside = array_values(array_diff(array_keys($typed), array_keys($to)));
        foreach ($outside as $addr) {
            $to[$addr] = ['email' => $addr, 'name' => ''];
        }

        if (! $to) {
            return 'Choose a worker, or type an email address.';
        }

        /* The typed addresses are the only strangers this file writes to, so
         * they are counted per sender, per hour. Workers on the season are
         * not: they are the point of the button. */
        if ($outside) {
            $key = 'sm-email-outside:' . (int) optional($request->user())->id;
            if (RateLimiter::remaining($key, self::EXTRA_PER_HOUR) < count($outside)) {
                $mins = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

                return "That is a lot of other addresses for one hour. Try again in {$mins} min, or send to the workers only.";
            }
            RateLimiter::increment($key, 3600, count($outside));
        }

        return array_values($to);
    }

    /** The sender's own words, set above the work. Empty when there are none. */
    private function notePanel(Request $request, string $sentBy): string
    {
        $note = trim((string) $request->input('message', ''));
        if ($note === '') {
            return '';
        }

        return EmailSkin::panel(
            EmailSkin::label('A note from ' . $sentBy)
            . '<div style="white-space:pre-line;">' . e($note) . '</div>',
            'gold'
        );
    }

    /**
     * The workers actually asked for, minus anyone who cannot be reached.
     * One activity is only ever sent to the workers on it.
     */
    private function chosenWorkers(Request $request, AsCroppingSchedule $schedule, ?AsScheduleActivity $activity = null)
    {
        $ids = collect((array) $request->input('workerIds', []))
            ->map(fn ($i) => (int) $i)->filter()->unique()->all();

        if ($activity) {
            $on = $activity->workers->pluck('id')->map(fn ($i) => (int) $i)->all();
            $ids = array_values(array_intersect($ids, $on));
        }

        if (! $ids) {
            return collect();
        }

        return AsScheduleWorker::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->whereIn('id', $ids)
            ->get()
            // Belt and braces: the checklist locks these rows, and the server
            // refuses them anyway. A queued row that can only fail is noise.
            ->filter(fn ($w) => filled($w->email))
            ->values();
    }

    private function said(int $sent, int $asked): string
    {
        if ($sent === $asked) {
            return $sent === 1 ? 'Sent to 1 person.' : "Sent to {$sent} people.";
        }

        return "Sent to {$sent} of {$asked}. The rest are in the mail log with the reason.";
    }

    /** The day's work, as a table an email client will actually draw. */
    private function tasksTable($activities): string
    {
        $rows = '';
        foreach ($activities as $a) {
            $lots = $a->lots->pluck('lotName')->filter()->implode(', ');
            $who = $a->workers->pluck('workerName')->filter()->implode(', ');
            $meta = collect([
                $lots ? '📐 ' . e($lots) : null,
                $who ? '👷 ' . e($who) : null,
                filled($a->timeRequired) ? '⏱ ' . e((string) $a->timeRequired) : null,
            ])->filter()->implode(' &nbsp;·&nbsp; ');

            $rows .= EmailSkin::taskRow(
                e((string) $a->activityTitle),
                $meta,
                filled($a->description) ? e(\Illuminate\Support\Str::limit(strip_tags((string) $a->description), 220)) : '',
            );
        }

        return EmailSkin::taskList($rows);
    }

    /** One activity, said properly. */
    private function activityPanel(AsScheduleActivity $activity): string
    {
        $lots = $activity->lots->pluck('lotName')->filter()->implode(', ');
        $who = $activity->workers->pluck('workerName')->filter()->implode(', ');
        $items = $activity->items->where('deleteStatus', 1)
            ->map(fn ($i) => trim($i->itemName . ' ' . ($i->quantity ? '× ' . rtrim(rtrim(number_format((float) $i->quantity, 2), '0'), '.') : '') . ' ' . $i->unitOfMeasure))
            ->filter()->implode(', ');

        $facts = array_filter([
            'Where' => $lots ? e($lots) : null,
            'Who' => $who ? e($who) : null,
            'How long' => filled($activity->timeRequired) ? e((string) $activity->timeRequired) : null,
            'Priority' => filled($activity->priority) ? e(ucfirst((string) $activity->priority)) : null,
            'Bring' => $items ? e($items) : null,
        ]);

        $out = $facts ? EmailSkin::facts($facts) : '';

        if (filled($activity->description)) {
            $out .= EmailSkin::label('What to do')
                . '<p style="margin:0 0 16px;white-space:pre-line;">' . e(trim(strip_tags((string) $activity->description))) . '</p>';
        }

        return $out;
    }

    private function readDate(Request $request): ?Carbon
    {
        $raw = (string) ($request->input('date') ?: $request->query('date'));
        if ($raw === '') {
            return null;
        }
        try {
            return Carbon::parse($raw, 'Asia/Manila')->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
