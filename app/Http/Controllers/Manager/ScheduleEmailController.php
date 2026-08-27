<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleWorker;
use App\Services\MailService;
use App\Support\EmailSkin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
 */
class ScheduleEmailController extends BaseScheduleController
{
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
        }

        return response()->json(['success' => true, 'data' => [
            'title' => $what,
            'dateLabel' => $when ? Carbon::parse($when)->format('l, M j, Y') : null,
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

        $chosen = $this->chosenWorkers($request, $schedule);
        if ($chosen->isEmpty()) {
            return $this->jsonFail('Choose at least one worker with an email address.', 422);
        }

        $label = $date->format('l, M j, Y');
        $sentBy = optional($request->user())->full_name ?: 'the farm';
        $sent = 0;

        foreach ($chosen as $worker) {
            /* Each worker is told about the whole day, not only their own
             * jobs. An owner reaching for this button has decided that this
             * day matters to these people; narrowing it behind their back
             * would send somebody an email that says nothing. */
            $ok = $this->mail->sendTemplate('day_schedule', $worker->email, $worker->workerName, [
                'workerName' => $worker->workerName ?: 'there',
                'scheduleTitle' => (string) $schedule->title,
                'dateLabel' => $label,
                'tasksTable' => $this->tasksTable($activities),
                'sentBy' => $sentBy,
            ], [
                'relatedType' => 'schedule_day',
                'croppingScheduleId' => $schedule->id,
            ]);
            $sent += $ok ? 1 : 0;
        }

        return response()->json(['success' => true, 'message' => $this->said($sent, $chosen->count())]);
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

        $chosen = $this->chosenWorkers($request, $schedule);
        if ($chosen->isEmpty()) {
            return $this->jsonFail('Choose at least one worker with an email address.', 422);
        }

        $label = $activity->targetDate ? Carbon::parse($activity->targetDate)->format('l, M j, Y') : 'a day yet to be set';
        $sentBy = optional($request->user())->full_name ?: 'the farm';
        $sent = 0;

        foreach ($chosen as $worker) {
            $ok = $this->mail->sendTemplate('activity_notice', $worker->email, $worker->workerName, [
                'workerName' => $worker->workerName ?: 'there',
                'scheduleTitle' => (string) $schedule->title,
                'activityTitle' => (string) $activity->activityTitle,
                'dateLabel' => $label,
                'activityBody' => $this->activityPanel($activity),
                'sentBy' => $sentBy,
            ], [
                'relatedType' => 'activity',
                'relatedId' => $activity->id,
                'croppingScheduleId' => $schedule->id,
            ]);
            $sent += $ok ? 1 : 0;
        }

        return response()->json(['success' => true, 'message' => $this->said($sent, $chosen->count())]);
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

    /** The workers actually asked for, minus anyone who cannot be reached. */
    private function chosenWorkers(Request $request, AsCroppingSchedule $schedule)
    {
        $ids = collect((array) $request->input('workerIds', []))
            ->map(fn ($i) => (int) $i)->filter()->unique()->all();

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
            return $sent === 1 ? 'Sent to 1 worker.' : "Sent to {$sent} workers.";
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

            $rows .= '<tr><td style="padding:12px 0;border-bottom:1px solid ' . EmailSkin::LINE . ';">'
                . '<div style="font-size:15px;font-weight:700;color:' . EmailSkin::INK . ';">' . e((string) $a->activityTitle) . '</div>'
                . ($meta ? '<div style="margin-top:3px;font-size:12.5px;color:' . EmailSkin::MUTED . ';">' . $meta . '</div>' : '')
                . (filled($a->description)
                    ? '<div style="margin-top:6px;font-size:13.5px;color:' . EmailSkin::INK . ';">'
                        . e(\Illuminate\Support\Str::limit(strip_tags((string) $a->description), 220)) . '</div>'
                    : '')
                . '</td></tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
            . 'style="margin:14px 0;">' . $rows . '</table>';
    }

    /** One activity, said properly. */
    private function activityPanel(AsScheduleActivity $activity): string
    {
        $lots = $activity->lots->pluck('lotName')->filter()->implode(', ');
        $who = $activity->workers->pluck('workerName')->filter()->implode(', ');
        $items = $activity->items->where('deleteStatus', 1)
            ->map(fn ($i) => trim($i->itemName . ' ' . ($i->quantity ? '× ' . rtrim(rtrim(number_format((float) $i->quantity, 2), '0'), '.') : '') . ' ' . $i->unitOfMeasure))
            ->filter()->implode(', ');

        $facts = collect([
            $lots ? '<div><strong>Where:</strong> ' . e($lots) . '</div>' : null,
            $who ? '<div><strong>Who:</strong> ' . e($who) . '</div>' : null,
            filled($activity->timeRequired) ? '<div><strong>How long:</strong> ' . e((string) $activity->timeRequired) . '</div>' : null,
            filled($activity->priority) ? '<div><strong>Priority:</strong> ' . e(ucfirst((string) $activity->priority)) . '</div>' : null,
            $items ? '<div><strong>Bring:</strong> ' . e($items) . '</div>' : null,
        ])->filter()->implode('');

        $body = '<div style="font-size:17px;font-weight:800;color:' . EmailSkin::DEEP . ';margin-bottom:6px;">'
            . e((string) $activity->activityTitle) . '</div>' . $facts;

        if (filled($activity->description)) {
            $body .= '<div style="margin-top:10px;">' . e(strip_tags((string) $activity->description)) . '</div>';
        }

        return EmailSkin::panel($body);
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
