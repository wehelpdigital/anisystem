<?php

namespace App\Http\Controllers\Manager;

use App\Mail\ScheduleDayDigest;
use App\Models\AsCroppingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * "Quick Share" server actions for the activities toolbar — email today's or
 * tomorrow's plan to the workers who have a registered email. (The public,
 * link-based schedule/activity pages live in the top-level ShareController.)
 */
class ScheduleShareController extends BaseScheduleController
{
    /** Email the workers with the given day's activities. */
    public function emailWorkers(Request $request)
    {
        $data = $request->validate([
            'scheduleId' => 'required|integer',
            'scope' => 'required|in:today,tomorrow',
        ]);

        $schedule = $this->schedule($data['scheduleId']);
        $schedule->load(['lots', 'workers', 'activities.lots']);

        $date = $data['scope'] === 'tomorrow'
            ? Carbon::tomorrow()
            : Carbon::today();
        $dateLabel = ($data['scope'] === 'tomorrow' ? 'Tomorrow' : 'Today')
            . ' · ' . $date->format('l, M j');

        $rows = $this->activitiesForDate($schedule, $date);
        if (empty($rows)) {
            return $this->jsonFail('No activities scheduled for ' . strtolower($data['scope']) . ' (' . $date->format('M j') . ').');
        }

        $recipients = $schedule->workers
            ->filter(fn ($w) => filter_var($w->email, FILTER_VALIDATE_EMAIL))
            ->values();

        if ($recipients->isEmpty()) {
            return $this->jsonFail('No workers have an email yet. Add worker emails in the Workers module first.');
        }

        $publicUrl = $schedule->shareToken ? url('/s/' . $schedule->shareToken) : null;

        $sent = 0;
        try {
            foreach ($recipients as $worker) {
                Mail::to($worker->email)->send(new ScheduleDayDigest(
                    scheduleTitle: $schedule->title,
                    dateLabel: $dateLabel,
                    workerName: $worker->workerName,
                    activities: $rows,
                    publicUrl: $publicUrl,
                ));
                $sent++;
            }
        } catch (\Throwable $e) {
            Log::warning('Quick Share email failed', ['schedule' => $schedule->id, 'error' => $e->getMessage()]);

            return $this->jsonFail('Email could not be sent. The mail server may not be configured yet.', 500, ['sent' => $sent]);
        }

        return $this->jsonOk(
            'Emailed ' . strtolower($data['scope']) . "'s plan to {$sent} " . str('worker')->plural($sent) . '.',
            ['sent' => $sent]
        );
    }

    /**
     * Non-hidden activities whose date range covers $date, each flattened to
     * the plain shape the email view expects.
     *
     * @return array<int,array{title:string,tags:string,description:?string}>
     */
    private function activitiesForDate(AsCroppingSchedule $schedule, Carbon $date): array
    {
        $dayType = $schedule->dayType ?: 'DAS';
        $anchors = $this->lotDayZero($schedule);
        $target = $date->format('Y-m-d');

        $rows = [];
        foreach ($schedule->activities as $a) {
            if ($a->isHidden || ! $a->targetDate) {
                continue;
            }
            $start = Carbon::parse($a->targetDate)->format('Y-m-d');
            $end = $a->targetEndDate ? Carbon::parse($a->targetEndDate)->format('Y-m-d') : $start;
            if ($target < $start || $target > $end) {
                continue;
            }

            $tags = [];
            foreach ($a->lots as $lot) {
                $anchor = $anchors[$lot->id] ?? null;
                if ($anchor) {
                    $das = (int) $anchor->copy()->startOfDay()->diffInDays(Carbon::parse($a->targetDate)->startOfDay(), false);
                    $tags[] = $lot->lotName . ' · ' . $dayType . ' ' . $das;
                } else {
                    $tags[] = $lot->lotName;
                }
            }

            $rows[] = [
                'title' => $a->activityTitle,
                'tags' => implode('   ', $tags),
                'description' => $a->description,
            ];
        }

        return $rows;
    }

    /** Effective day-0 date per lot: manual lot date, or earliest day-zero activity. */
    private function lotDayZero(AsCroppingSchedule $schedule): array
    {
        $map = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $map[$lot->id] = Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (! $a->isDayZero || ! $a->targetDate) {
                continue;
            }
            $d = Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (! isset($map[$lot->id]) || $d->lt($map[$lot->id])) {
                    $map[$lot->id] = $d;
                }
            }
        }

        return $map;
    }
}
