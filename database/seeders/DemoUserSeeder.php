<?php

namespace Database\Seeders;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleDefaultGrouping;
use App\Models\AsScheduleIrrigation;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleMaterial;
use App\Models\AsScheduleService;
use App\Models\AsScheduleWorker;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or refreshes) a ready-to-use demo client for anisystem.test with an
 * active subscription and a fully populated sample rice season — lots, workers,
 * materials, services, an activity timeline and irrigation — so the app looks
 * alive on first login. Idempotent: the sample content is only built once.
 */
class DemoUserSeeder extends Seeder
{
    public const EMAIL = 'demo@anisystem.test';
    public const PASSWORD = 'demo1234';

    public function run(): void
    {
        $now = Carbon::now('Asia/Manila');

        $user = $this->ensureUser();
        $this->ensureSubscription($user, $now);
        $schedule = $this->ensureSchedule($user, $now);
        $version = $this->ensureVersion($schedule);

        // Only build the sample season once.
        if ($schedule->lots()->count() === 0) {
            $this->seedSeason($schedule, $version, $now);
            $this->command?->info('Sample season seeded.');
        } else {
            $this->command?->info('Sample season already present — left untouched.');
        }

        $this->command?->info('Demo user ready: '.self::EMAIL.' / '.self::PASSWORD);
    }

    private function ensureUser(): User
    {
        $user = User::where('email', self::EMAIL)->first() ?: new User;
        $user->email = self::EMAIL;
        $user->firstName = 'Demo';
        $user->lastName = 'Farmer';
        $user->phone = '09171234567';
        $user->password = Hash::make(self::PASSWORD);
        $user->status = 'active';
        $user->deleteStatus = 1;
        $user->save();

        return $user;
    }

    private function ensureSubscription(User $user, Carbon $now): void
    {
        $plan = Plan::visible()->orderByDesc('durationDays')->first();

        $attrs = [
            'userId' => $user->id,
            'planId' => $plan?->id,
            'planKey' => $plan?->planKey ?? 'annual',
            'planName' => $plan?->planName ?? 'Annual Plan',
            'price' => $plan?->price ?? 0,
            'durationDays' => $plan?->durationDays ?? 365,
            'status' => Subscription::STATUS_ACTIVE,
            'startsAt' => $now->copy()->subDay(),
            'expiresAt' => $now->copy()->addDays($plan?->durationDays ?? 365),
            'verifiedAt' => $now->copy()->subDay(),
            'notes' => 'Demo account subscription (seeded).',
            'deleteStatus' => 1,
        ];

        $subscription = Subscription::where('userId', $user->id)->where('deleteStatus', 1)->orderByDesc('id')->first();
        $subscription ? $subscription->update($attrs) : Subscription::create($attrs);
    }

    private function ensureSchedule(User $user, Carbon $now): AsCroppingSchedule
    {
        // Match only the seeded demo schedule, never one the user made themselves.
        $schedule = AsCroppingSchedule::active()->forClient($user->id)
            ->where('title', 'like', '%(Demo)%')
            ->orderBy('id')->first();

        if (! $schedule) {
            $schedule = AsCroppingSchedule::create([
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'anisystemUserId' => $user->id,
                'title' => 'Wet Season '.$now->year.' — Rice (Demo)',
                'description' => 'A sample cropping schedule you can explore, edit or delete.',
                'dayType' => 'DAS',
                'defaultStaggerDays' => 0,
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);
        }

        // Fill in crop info (columns added after the first demo runs).
        $schedule->forceFill([
            'cropType' => 'Rice (Palay)',
            'cropVariety' => 'NSIC Rc222',
        ])->save();

        return $schedule;
    }

    private function ensureVersion(AsCroppingSchedule $schedule): AsScheduleActivityVersion
    {
        return AsScheduleActivityVersion::firstOrCreate(
            ['croppingScheduleId' => $schedule->id, 'isOriginal' => 1, 'deleteStatus' => 1],
            ['versionName' => 'Original', 'isActive' => 1, 'versionOrder' => 0]
        );
    }

    /**
     * Builds a realistic wet-season rice program. Day 0 (sowing) is set 20 days
     * in the past so the timeline shows both completed and upcoming work.
     */
    private function seedSeason(AsCroppingSchedule $schedule, AsScheduleActivityVersion $version, Carbon $now): void
    {
        $dayZero = $now->copy()->startOfDay()->subDays(20);
        $on = fn (int $das) => $dayZero->copy()->addDays($das)->format('Y-m-d');

        // ---- Lots ----
        $lotA = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName' => 'Lot A — North Field',
            'lotSize' => 1.5, 'lotSizeUnit' => 'hectare',
            'dayZeroDate' => $dayZero->format('Y-m-d'),
            'notes' => 'Beside the irrigation canal. Clay loam, holds water well.',
            'deleteStatus' => 1,
        ]);
        $lotB = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName' => 'Lot B — South Field',
            'lotSize' => 2.0, 'lotSizeUnit' => 'hectare',
            'dayZeroDate' => $dayZero->format('Y-m-d'),
            'notes' => 'Slightly elevated, drains faster — watch water level.',
            'deleteStatus' => 1,
        ]);
        $bothLots = [$lotA->id, $lotB->id];

        // ---- Workers ----
        $juan = AsScheduleWorker::create([
            'croppingScheduleId' => $schedule->id, 'workerName' => 'Juan Dela Cruz',
            'costPerHalfDay' => 350, 'priority' => 1,
            'skills' => ['manager', 'broadcast_granulars'],
            'notes' => 'Farm lead. Handles fertilizer computation.', 'deleteStatus' => 1,
        ]);
        $pedro = AsScheduleWorker::create([
            'croppingScheduleId' => $schedule->id, 'workerName' => 'Pedro Santos',
            'costPerHalfDay' => 300, 'priority' => 2,
            'skills' => ['operate_machine', 'harrowing'],
            'notes' => 'Operates the hand tractor.', 'deleteStatus' => 1,
        ]);
        $maria = AsScheduleWorker::create([
            'croppingScheduleId' => $schedule->id, 'workerName' => 'Maria Reyes',
            'costPerHalfDay' => 300, 'priority' => 3,
            'skills' => ['spray'], 'notes' => 'Trained on spraying and scouting.', 'deleteStatus' => 1,
        ]);
        $allWorkers = [$juan->id, $pedro->id, $maria->id];

        // ---- Materials ----
        $mat = fn (string $name, string $type, string $unit, float $amount, float $qty, string $desc) => AsScheduleMaterial::create([
            'croppingScheduleId' => $schedule->id, 'materialName' => $name, 'description' => $desc,
            'materialType' => $type, 'unitOfMeasure' => $unit,
            'priceAmount' => $amount, 'priceQuantity' => $qty, 'deleteStatus' => 1,
        ]);
        $seeds = $mat('Certified Rice Seeds (NSIC Rc222)', 'seed', 'kg', 1200, 40, 'Certified inbred seeds, 40 kg per bag.');
        $complete = $mat('Complete 14-14-14', 'fertilizer', 'kg', 1700, 50, 'Basal fertilizer, 50 kg per bag.');
        $urea = $mat('Urea 46-0-0', 'fertilizer', 'kg', 1500, 50, 'Top dressing nitrogen, 50 kg per bag.');
        $foliar = $mat('Foliar Micronutrients', 'foliar', 'l', 650, 1, 'Zinc + boron foliar supplement.');
        $herbicide = $mat('Post-emergence Herbicide', 'herbicide', 'l', 800, 1, 'For grassy and broadleaf weeds.');

        // ---- Services ----
        $svc = fn (string $name, float $cost, string $desc) => AsScheduleService::create([
            'croppingScheduleId' => $schedule->id, 'serviceName' => $name,
            'description' => $desc, 'serviceCost' => $cost, 'deleteStatus' => 1,
        ]);
        $tractor = $svc('Tractor Rental (Land Prep)', 3500, 'Per pass, includes operator and fuel.');
        $harvester = $svc('Combine Harvester Rental', 6000, 'Per hectare, includes hauling to the road.');

        // ---- Default grouping (anchors the irrigation DAS bands) ----
        $group = AsScheduleDefaultGrouping::create([
            'croppingScheduleId' => $schedule->id, 'groupName' => 'All Lots',
            'staggerDays' => 0, 'startDate' => $dayZero->format('Y-m-d'),
            'groupOrder' => 0, 'deleteStatus' => 1,
        ]);
        $group->lots()->sync($bothLots);

        // ---- Activities ----
        $seq = 0;
        $add = function (array $a) use ($schedule, $version, &$seq) {
            $activity = AsScheduleActivity::create([
                'croppingScheduleId' => $schedule->id,
                'versionId' => $version->id,
                'activityTitle' => $a['title'],
                'targetDate' => $a['date'],
                'targetEndDate' => $a['endDate'] ?? null,
                'priority' => $a['priority'] ?? 'medium',
                'activityType' => $a['type'] ?? null,
                'isDayZero' => $a['dayZero'] ?? false,
                'isDraft' => 0, 'isHidden' => 0,
                'description' => $a['description'] ?? null,
                'timeRequired' => $a['time'] ?? 'half',
                'sequenceOrder' => ($seq += 10),
                'deleteStatus' => 1,
            ]);
            $activity->lots()->sync($a['lots'] ?? []);
            $activity->workers()->sync($a['workers'] ?? []);
            foreach ($a['items'] ?? [] as $item) {
                AsScheduleActivityItem::create([
                    'activityId' => $activity->id,
                    'itemType' => $item[0],
                    'materialId' => $item[0] === 'material' ? $item[1] : null,
                    'serviceId' => $item[0] === 'service' ? $item[1] : null,
                    'quantity' => $item[2] ?? 1,
                    'unitOfMeasure' => $item[3] ?? null,
                    'deleteStatus' => 1,
                ]);
            }

            return $activity;
        };

        $add(['title' => 'Land Preparation — First Plowing', 'date' => $on(-14), 'type' => 'land_prep',
            'priority' => 'high', 'time' => 'whole', 'lots' => $bothLots, 'workers' => [$pedro->id],
            'description' => '<p>Plow both fields while there is enough moisture. Target a good soil break for easier harrowing.</p>',
            'items' => [['service', $tractor->id, 1]]]);

        $add(['title' => 'Harrowing (Pagsusuyod)', 'date' => $on(-7), 'type' => 'land_prep',
            'priority' => 'high', 'time' => 'whole', 'lots' => $bothLots, 'workers' => [$pedro->id],
            'description' => '<p>Two passes to level the field. Keep water shallow so the mud settles evenly.</p>',
            'items' => [['service', $tractor->id, 1]]]);

        $add(['title' => 'Basal Fertilizer — Complete 14-14-14', 'date' => $on(-3), 'type' => 'fertilizer',
            'priority' => 'high', 'lots' => $bothLots, 'workers' => [$juan->id],
            'description' => '<p>Broadcast evenly before final leveling so the fertilizer is incorporated.</p>',
            'items' => [['material', $complete->id, 175, 'kg']]]);

        $add(['title' => 'Seed Soaking & Incubation', 'date' => $on(-2), 'type' => 'seed_treatment',
            'lots' => $bothLots, 'workers' => [$maria->id],
            'description' => '<p>Soak 24 hours, then incubate 24 hours until the sprouts just break.</p>',
            'items' => [['material', $seeds->id, 140, 'kg']]]);

        $add(['title' => 'Sowing / Direct Seeding', 'date' => $on(0), 'type' => 'planting',
            'priority' => 'critical', 'time' => 'whole', 'dayZero' => true,
            'lots' => $bothLots, 'workers' => $allWorkers,
            'description' => '<p><strong>This is Day 0.</strong> Broadcast the pre-germinated seeds on well-drained mud. All later timings count from this date.</p>']);

        $add(['title' => 'First Top Dressing — Urea', 'date' => $on(7), 'type' => 'fertilizer',
            'priority' => 'high', 'lots' => $bothLots, 'workers' => [$juan->id],
            'description' => '<p>Apply with a thin layer of water, then hold the water for 2–3 days.</p>',
            'items' => [['material', $urea->id, 87.5, 'kg']]]);

        $add(['title' => 'Weed Control — Post-emergence Spray', 'date' => $on(14), 'type' => 'foliar_spray',
            'lots' => $bothLots, 'workers' => [$maria->id],
            'description' => '<p>Spray early morning while there is no wind. Drain slightly before spraying.</p>',
            'items' => [['material', $herbicide->id, 3, 'l']]]);

        $add(['title' => 'Foliar Micronutrient Spray', 'date' => $on(21), 'type' => 'foliar_spray',
            'lots' => $bothLots, 'workers' => [$maria->id],
            'description' => '<p>Zinc and boron supplement to support tillering.</p>',
            'items' => [['material', $foliar->id, 4, 'l']]]);

        $add(['title' => 'Second Top Dressing — Urea', 'date' => $on(30), 'type' => 'fertilizer',
            'priority' => 'high', 'lots' => $bothLots, 'workers' => [$juan->id],
            'description' => '<p>Maximum tillering stage. Do not apply if heavy rain is expected.</p>',
            'items' => [['material', $urea->id, 87.5, 'kg']]]);

        $add(['title' => 'Pest Monitoring & Scouting', 'date' => $on(40), 'type' => 'monitoring',
            'priority' => 'low', 'lots' => $bothLots, 'workers' => [$maria->id],
            'description' => '<p>Walk both fields and check for stem borer and leaf folder. Record what you find.</p>']);

        $add(['title' => 'Panicle Initiation Fertilizer', 'date' => $on(50), 'type' => 'fertilizer',
            'priority' => 'high', 'lots' => $bothLots, 'workers' => [$juan->id],
            'description' => '<p>Apply at panicle initiation — check by splitting a few tillers.</p>',
            'items' => [['material', $urea->id, 50, 'kg']]]);

        $add(['title' => 'Harvest', 'date' => $on(105), 'endDate' => $on(107), 'type' => 'harvest',
            'priority' => 'critical', 'time' => 'whole', 'lots' => $bothLots, 'workers' => $allWorkers,
            'description' => '<p>Harvest when about 80–85% of the grains are straw-coloured. Arrange hauling in advance.</p>',
            'items' => [['service', $harvester->id, 1]]]);

        // ---- Irrigation ----
        $irr = function (string $title, int $start, int $end, string $task, int $priority, int $order, string $desc, array $workers = []) use ($schedule, $bothLots) {
            $row = AsScheduleIrrigation::create([
                'croppingScheduleId' => $schedule->id,
                'irrigationTitle' => $title, 'description' => $desc,
                'dayMode' => 'das', 'startDay' => $start, 'endDay' => $end,
                'taskType' => $task, 'priority' => $priority, 'sortOrder' => $order,
                'timeRequired' => 'half',
                'assignedWorkerId' => $workers[0] ?? null,
                'deleteStatus' => 1,
            ]);
            $row->lots()->sync($bothLots);
            $row->workers()->sync($workers);

            return $row;
        };

        $irr('Keep field saturated after sowing', 0, 6, 'maintain', 4, 1,
            'Just saturated — no standing water yet so the seedlings can anchor.', [$pedro->id]);
        $irr('Irrigate after first top dressing', 7, 10, 'irrigate', 5, 2,
            'Bring water up to about 3 cm and hold it to dissolve the urea.', [$pedro->id]);
        $irr('Maintain 3–5 cm water level', 11, 60, 'maintain', 5, 3,
            'Main growing period. Keep a steady shallow level.', [$pedro->id]);
        $irr('Drain for spraying', 14, 14, 'drain', 1, 4,
            'Priority drain so the herbicide is not diluted — splits the maintain band for a day.', [$maria->id]);
        $irr('Drain before harvest', 95, 100, 'drain_water', 1, 5,
            'Drain early so the field is firm enough for the harvester.', [$pedro->id]);

        $schedule->forceFill(['status' => 'setup'])->save();
    }
}
