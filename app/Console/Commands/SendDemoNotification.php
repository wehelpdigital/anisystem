<?php

namespace App\Console\Commands;

use App\Models\AnisystemNotification;
use App\Models\AsCroppingSchedule;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Drop a couple of sample notifications on an account so the bell can be seen
 * doing its job. Nothing in the app writes to this table on a quiet farm, so
 * "does the bell work" was otherwise unanswerable without waiting for a real
 * event.
 */
class SendDemoNotification extends Command
{
    protected $signature = 'anisystem:demo-notification
                            {email? : Who to notify (defaults to every AniSystem user with a schedule)}';

    protected $description = 'Write sample notifications so the bell has something to show';

    public function handle(): int
    {
        $users = $this->argument('email')
            ? User::where('email', $this->argument('email'))->get()
            : User::whereIn('id', AsCroppingSchedule::active()->pluck('anisystemUserId')->filter()->unique())->get();

        if ($users->isEmpty()) {
            $this->error('No matching user.');

            return self::FAILURE;
        }

        foreach ($users as $user) {
            $schedule = AsCroppingSchedule::active()->where('anisystemUserId', $user->id)->latest('id')->first();

            AnisystemNotification::create([
                'userId' => $user->id,
                'type' => 'demo',
                'title' => 'Welcome to notifications',
                'body' => 'This is what a notification looks like. Real ones land here too: '
                    . 'expiring plans, comments on a shared season, and requests to connect.',
                // A console run builds its URLs from APP_URL, which on a
                // laptop is a laptop. The bell wants the place, not the
                // machine (NotificationService::localUrl).
                'url' => $schedule ? \App\Services\NotificationService::localUrl(route('sm.hub', ['id' => $schedule->id])) : null,
                'croppingScheduleId' => $schedule?->id,
                'deleteStatus' => 1,
            ]);

            AnisystemNotification::create([
                'userId' => $user->id,
                'type' => 'demo',
                'title' => $schedule ? ('Something to check in ' . $schedule->title) : 'Something to check',
                'body' => 'Tap a notification to go straight to what it is about.',
                'url' => $schedule ? \App\Services\NotificationService::localUrl(route('sm.activities', ['id' => $schedule->id])) : null,
                'croppingScheduleId' => $schedule?->id,
                'deleteStatus' => 1,
            ]);

            $this->info('Notified ' . $user->email);
        }

        return self::SUCCESS;
    }
}
