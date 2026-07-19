<?php

namespace Database\Seeders;

use App\Models\AsScheduleActivityVersion;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or refreshes) a ready-to-use demo client for anisystem.test with an
 * active subscription and a sample cropping schedule, so the credentials shown
 * under the login form always work. Idempotent.
 */
class DemoUserSeeder extends Seeder
{
    public const EMAIL = 'demo@anisystem.test';
    public const PASSWORD = 'demo1234';

    public function run(): void
    {
        $now = Carbon::now('Asia/Manila');

        $user = User::where('email', self::EMAIL)->first();
        if (! $user) {
            $user = new User;
            $user->email = self::EMAIL;
        }
        $user->firstName = 'Demo';
        $user->lastName = 'Farmer';
        $user->phone = '09171234567';
        $user->password = Hash::make(self::PASSWORD);
        $user->status = 'active';
        $user->deleteStatus = 1;
        $user->save();

        // Ensure exactly one active subscription (refresh the newest, else create).
        $plan = Plan::visible()->orderByDesc('durationDays')->first();

        $subscription = Subscription::where('userId', $user->id)
            ->where('deleteStatus', 1)
            ->orderByDesc('id')
            ->first();

        $attrs = [
            'userId' => $user->id,
            'planId' => $plan?->id,
            'planKey' => $plan?->planKey ?? 'annual',
            'planName' => $plan?->planName ?? 'Annual Plan',
            'price' => $plan?->price ?? 0,
            'durationDays' => $plan?->durationDays ?? 365,
            'status' => Subscription::STATUS_ACTIVE,
            'startsAt' => $now->copy()->subDays(1),
            'expiresAt' => $now->copy()->addDays($plan?->durationDays ?? 365),
            'verifiedAt' => $now->copy()->subDays(1),
            'notes' => 'Demo account subscription (seeded).',
            'deleteStatus' => 1,
        ];

        if ($subscription) {
            $subscription->update($attrs);
        } else {
            $subscription = Subscription::create($attrs);
        }

        // A sample schedule so the app isn't empty on first login.
        $hasSchedule = DB::table('as_cropping_schedules')
            ->where('anisystemUserId', $user->id)
            ->where('deleteStatus', 1)
            ->exists();

        if (! $hasSchedule) {
            $scheduleId = DB::table('as_cropping_schedules')->insertGetId([
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'anisystemUserId' => $user->id,
                'title' => 'Wet Season 2026 — Rice (Demo)',
                'description' => 'A sample cropping schedule to explore AniSystem.',
                'dayType' => 'DAS',
                'defaultStaggerDays' => 0,
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AsScheduleActivityVersion::create([
                'croppingScheduleId' => $scheduleId,
                'versionName' => 'Original',
                'isOriginal' => 1,
                'isActive' => 1,
                'versionOrder' => 0,
                'deleteStatus' => 1,
            ]);
        }

        $this->command?->info('Demo user ready: '.self::EMAIL.' / '.self::PASSWORD);
    }
}
