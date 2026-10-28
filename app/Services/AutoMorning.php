<?php

namespace App\Services;

use App\Models\AsAutoEvent;
use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Models\User;
use App\Support\AutoBook;
use App\Support\EmailSkin;
use Illuminate\Support\Carbon;

/**
 * Anee Auto's morning (2026-10-12). Once a day per season: the open tasks
 * are placed again with the new forecast (AutoPlanner), Anee says what the
 * day holds in the chat, and the owner and every worker with an email get
 * their day by mail. Nothing here asks the model: the words are put
 * together from the plan, so the morning is free and never late.
 *
 * The auto:daily command runs it at five, Manila time; opening a season
 * whose morning has not run yet runs it then (without the mail).
 */
class AutoMorning
{
    public function __construct(private AutoPlanner $planner, private MailService $mail)
    {
    }

    /** @return array{moved:int,emails:int} */
    public function run(AsAutoSeason $auto, bool $email = true): array
    {
        $tz = 'Asia/Manila';
        $today = now($tz)->startOfDay();
        if ($auto->status !== AsAutoSeason::ACTIVE) {
            return ['moved' => 0, 'emails' => 0];
        }
        $schedule = AsCroppingSchedule::find($auto->croppingScheduleId);
        if (! $schedule || (int) $schedule->deleteStatus !== 1) {
            return ['moved' => 0, 'emails' => 0];
        }
        $moved = [];
        $said = $auto->briefDate && $auto->briefDate->isSameDay($today);
        if (! $said) {
            $moved = array_values(array_filter($this->planner->plan($auto), fn ($m) => $m['from'] !== null));
            // The days moved: the sprays that now share a day are checked for one tank again (App\Support\AutoMix).
            rescue(fn () => \App\Support\AutoMix::audit($auto), null, true);
            $auto->refresh();
            $this->brief($auto, $today, $moved);
            $auto->forceFill(['briefDate' => $today->toDateString()])->save();
            $this->maybeFinish($auto, $schedule, $today);
        }
        $sent = 0;
        if ($email && ! ($auto->emailDate && $auto->emailDate->isSameDay($today))) {
            $sent = $this->mailOut($auto, $schedule, $today, $moved);
            $auto->forceFill(['emailDate' => $today->toDateString()])->save();
        }

        return ['moved' => count($moved), 'emails' => $sent];
    }

    /** The day in Anee's words, from the plan. */
    private function brief(AsAutoSeason $auto, Carbon $today, array $moved): void
    {
        $tasks = $this->todays($auto, $today);
        $lots = AsScheduleLot::whereIn('id', AsAutoLot::where('autoId', $auto->id)->pluck('lotId'))->get()->keyBy('id');
        $als = AsAutoLot::where('autoId', $auto->id)->get()->keyBy('lotId');
        $ages = [];
        foreach ($als as $lotId => $al) {
            if (! $al->fieldDate || ! ($lot = $lots->get($lotId))) {
                continue;
            }
            $d = (int) Carbon::parse($al->fieldDate->toDateString(), 'Asia/Manila')->diffInDays($today, false);
            $counter = AutoBook::counterOf((string) $al->method);
            $ages[] = $d >= 0 ? $lot->lotName . ' is ' . $counter . ' ' . $d : $lot->lotName . ' goes in the field in ' . (-$d) . ' day' . (-$d === 1 ? '' : 's');
        }
        $sky = $this->planner->skies($lots)[0][$today->toDateString()] ?? null;
        $hello = ['Magandang umaga! :anee-happy:', 'Good morning! :anee-salute:', 'Magandang umaga po :anee-smile:', 'Rise and shine! :anee-grin:'][$today->dayOfYear % 4];
        $lines = [$hello . ' ' . $today->format('l, M j') . '.'];
        if ($ages) {
            $lines[] = implode('; ', $ages) . '.';
        }
        if ($sky) {
            $lines[] = $sky['emoji'] . ' Rain chance ' . $sky['pop'] . '% today' . ($sky['mm'] ? ', about ' . $sky['mm'] . ' mm' : '') . ($sky['wind'] ? ', wind up to ' . $sky['wind'] . ' km/h' : '') . '.';
        }
        if ($tasks->count()) {
            $crit = $tasks->where('critical', 1)->count();
            $named = $tasks->take(4)->map(fn ($t) => $t->title . ($t->lotId && $lots->get($t->lotId) && $lots->count() > 1 ? ' (' . $lots->get($t->lotId)->lotName . ')' : ''))->implode('; ');
            $lines[] = 'Today: ' . $tasks->count() . ' task' . ($tasks->count() > 1 ? 's' : '') . ($crit ? ', ' . $crit . ' that cannot wait' : '') . '. ' . $named . ($tasks->count() > 4 ? '; and more.' : '.');
        } else {
            $lines[] = 'Nothing planned for today. A good day to walk the field and rest the back :anee-content:';
        }
        $late = $tasks->filter(fn ($t) => ! empty($t->planNote['late']))->count();
        if ($late) {
            $lines[] = $late . ' of them ' . ($late > 1 ? 'are' : 'is') . ' already late: let us catch up :anee-concerned:';
        }
        if ($moved) {
            $lines[] = 'I moved ' . count($moved) . ' task' . (count($moved) > 1 ? 's' : '') . ' for the weather or the workers: ' . implode('; ', array_map(fn ($m) => $m['title'] . ' to ' . Carbon::parse($m['to'])->format('D, M j'), array_slice($moved, 0, 3))) . (count($moved) > 3 ? '; and more' : '') . '.';
        }
        // The shed and the payroll (2026-10-28): what to buy before the week's tasks need it, and on Saturdays what is owed.
        $notes = [];
        if (\App\Support\AutoShed::on($auto)) {
            $buy = array_filter(rescue(fn () => \App\Support\AutoShed::book($auto)['rows'], [], true), fn ($r) => $r['state'] === 'soon');
            if ($buy) {
                $notes[] = ['open' => 'shed', 'icon' => '📦', 'text' => 'Buy before it is needed: ' . implode('; ', array_map(fn ($r) => $r['name'] . ', ' . $r['buySays'] . ' by ' . $r['next'], array_slice($buy, 0, 3)))
                    . (count($buy) > 3 ? '; and more' : '') . '.'];
            }
        }
        if ($today->isSaturday() && \App\Support\AutoPay::on($auto)) {
            $owed = rescue(fn () => \App\Support\AutoPay::book($auto), null, true);
            $who = $owed ? array_filter($owed['rows'], fn ($r) => $r['owed'] > 0) : [];
            if ($who) {
                $notes[] = ['open' => 'pay', 'icon' => '💵', 'text' => 'Payday? ' . \App\Support\AutoShed::peso($owed['totals']['owed']) . ' is owed to ' . implode(', ', array_map(fn ($r) => $r['name'], $who)) . '.'];
            }
        }
        foreach ($notes as $n) {
            $lines[] = $n['icon'] . ' ' . $n['text'];
        }
        // The same morning as data, for the morning card the season page draws (2026-10-27, the owner: "daily thing
        // should appear as cards"): the sky, each lot's day and stage, and what moved.
        $lotCards = [];
        foreach ($als as $lotId => $al) {
            if (! ($lot = $lots->get($lotId))) {
                continue;
            }
            $counter = AutoBook::counterOf((string) $al->method);
            $d = $al->fieldDate ? (int) Carbon::parse($al->fieldDate->toDateString(), 'Asia/Manila')->diffInDays($today, false) : null;
            $stage = null;
            if ($d !== null && $d >= 0) {
                try {
                    $stage = \App\Support\CropStages::stageFor($lot->crop, $d, $counter, $lot->maturityDays())['label'] ?? null;
                } catch (\Throwable $e) {
                }
            }
            $lotCards[] = ['name' => $lot->lotName, 'icon' => \App\Support\CropCatalog::CROPS[$lot->crop]['icon'] ?? '🌱', 'counter' => $counter, 'day' => $d, 'stage' => $stage];
        }
        AsAutoEvent::say($auto->id, 'briefing', implode("\n", $lines), [
            'date' => $today->toDateString(), 'tasks' => $tasks->pluck('id')->all(), 'moved' => count($moved),
            'sky' => $sky ? array_intersect_key($sky, array_flip(['emoji', 'pop', 'mm', 'wind', 'tmax'])) : null,
            'lots' => $lotCards, 'late' => $late, 'notes' => $notes ?: null,
            'movedList' => array_map(fn ($m) => ['id' => $m['id'] ?? null, 'title' => $m['title'], 'to' => $m['to'], 'why' => $m['why'] ?? null], array_slice($moved, 0, 6)),
        ]);
    }

    private function todays(AsAutoSeason $auto, Carbon $today)
    {
        return AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')
            ->whereNotNull('plannedDate')->where('plannedDate', '<=', $today->toDateString())->orderByDesc('critical')->orderBy('sortOrder')->get();
    }

    /** A season whose every task is done or past, and whose harvest has come, is finished. */
    private function maybeFinish(AsAutoSeason $auto, AsCroppingSchedule $schedule, Carbon $today): void
    {
        $open = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')->count();
        $harvest = AsAutoLot::where('autoId', $auto->id)->max('harvestDate');
        if ($open === 0 && (! $harvest || Carbon::parse($harvest)->lt($today))) {
            $auto->forceFill(['status' => AsAutoSeason::FINISHED])->save();
            AsAutoEvent::say($auto->id, 'briefing', 'Every task of this season is done :anee-starstruck: Salamat for farming with me. Your season log is kept for the post harvest report.', ['finished' => true]);
        }
    }

    /** The day by mail: the owner gets everything, each worker their own. */
    private function mailOut(AsAutoSeason $auto, AsCroppingSchedule $schedule, Carbon $today, array $moved): int
    {
        $tasks = $this->todays($auto, $today);
        $week = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')
            ->where('plannedDate', '>', $today->toDateString())->where('plannedDate', '<=', $today->copy()->addDays(6)->toDateString())->orderBy('plannedDate')->get();
        $lots = AsScheduleLot::whereIn('id', AsAutoLot::where('autoId', $auto->id)->pluck('lotId'))->pluck('lotName', 'id');
        $sky = $this->planner->skies(AsScheduleLot::whereIn('id', $lots->keys())->get())[0][$today->toDateString()] ?? null;
        $people = AutoPlanner::people($auto)->keyBy('id');
        $url = route('auto.show', $auto->id);

        $rows = function ($list, bool $withDay) use ($lots) {
            return EmailSkin::taskList($list->map(fn (AsAutoTask $t) => EmailSkin::taskRow(
                e(trim((AutoBook::KINDS[$t->kind][1] ?? '') . ' ' . $t->title)),
                e(implode(' · ', array_filter([$t->lotId ? ($lots[$t->lotId] ?? null) : null, $t->timeOfDay ? ucfirst($t->timeOfDay) : null, $withDay && $t->plannedDate ? $t->plannedDate->format('D, M j') : null]))),
                e((string) $t->summary),
                $t->critical ? 'Cannot wait' : (! empty($t->planNote['late']) ? 'Late' : '')
            ))->implode(''));
        };
        $weather = $sky ? EmailSkin::panel(e($sky['emoji'] . ' Rain chance ' . $sky['pop'] . '% today' . ($sky['mm'] ? ', about ' . $sky['mm'] . ' mm' : '') . ($sky['wind'] ? ', wind up to ' . $sky['wind'] . ' km/h' : '') . '.'), 'leaf') : '';
        $movedLine = $moved ? '<p style="margin:0 0 16px;">' . e('Anee moved ' . count($moved) . ' task' . (count($moved) > 1 ? 's' : '') . ' for the weather or the workers.') . '</p>' : '';
        $send = function (string $email, string $name, $mine, $mineWeek) use ($schedule, $today, $rows, $weather, $movedLine, $url) {
            return $this->mail->sendTemplate('auto_daily', $email, $name, [
                'recipient_name' => e($name), 'season_title' => e((string) $schedule->title), 'today_date' => $today->format('l, M j'),
                'today_count' => (string) $mine->count(), 'week_count' => (string) $mineWeek->count(),
                'tasks_list' => $mine->count() ? EmailSkin::label('Today') . $rows($mine, false) : '<p style="margin:0 0 16px;">Nothing planned for today.</p>',
                'week_list' => $mineWeek->count() ? EmailSkin::label('The rest of the week') . $rows($mineWeek->take(10), true) : '',
                'weather_line' => $weather, 'moved_line' => $movedLine, 'auto_url' => $url, 'app_name' => 'anee.io',
            ], ['scheduleId' => $schedule->id]);
        };

        $sent = 0;
        $owner = User::find($auto->userId);
        if ($owner && $owner->email && ($tasks->count() || $week->count())) {
            $sent += $send($owner->email, (string) ($owner->full_name ?: 'farmer'), $tasks, $week) ? 1 : 0;
        }
        foreach ($people as $pid => $p) {
            if ($pid <= 0 || empty($p['email']) || ($owner && strcasecmp((string) $p['email'], (string) $owner->email) === 0)) {
                continue;
            }
            $mine = $tasks->filter(fn ($t) => in_array($pid, (array) $t->workerIds, true));
            $mineWeek = $week->filter(fn ($t) => in_array($pid, (array) $t->workerIds, true));
            if (! $mine->count() && ! $mineWeek->count()) {
                continue;
            }
            $sent += $send((string) $p['email'], $p['name'], $mine, $mineWeek) ? 1 : 0;
        }
        if ($sent) {
            AsAutoEvent::say($auto->id, 'email', null, ['sent' => $sent, 'date' => $today->toDateString()], ['role' => 'system']);
        }

        return $sent;
    }
}
