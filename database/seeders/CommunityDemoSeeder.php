<?php

namespace Database\Seeders;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleLot;
use App\Models\CommunityComment;
use App\Models\CommunityRating;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Gives the Community something to show on a fresh install: a second member's
 * plan, published, with a question already asked and answered.
 *
 * Safe to re-run — everything keys off a stable title.
 */
class CommunityDemoSeeder extends Seeder
{
    private const TITLE = 'Dry Season — Corn (Community sample)';

    public function run(): void
    {
        $owner = User::where('email', 'e2e-test@anisystem.test')->first()
            ?? User::where('email', '!=', 'demo@anisystem.test')->orderBy('id')->first();

        if (! $owner) {
            $this->command?->warn('CommunityDemoSeeder: no second member to own the sample plan; skipped.');

            return;
        }

        $schedule = AsCroppingSchedule::where('anisystemUserId', $owner->id)
            ->where('title', self::TITLE)
            ->first();

        if (! $schedule) {
            $schedule = AsCroppingSchedule::create([
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'anisystemUserId' => $owner->id,
                'title' => self::TITLE,
                'description' => 'A shared plan so the Community has something to read.',
                'cropType' => 'Corn (Mais)',
                'cropVariety' => 'Pioneer 30D80',
                'dayType' => 'DAS',
                'defaultStaggerDays' => 0,
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);

            $version = AsScheduleActivityVersion::create([
                'croppingScheduleId' => $schedule->id,
                'versionName' => 'Original',
                'isOriginal' => 1,
                'isActive' => 1,
                'versionOrder' => 0,
                'deleteStatus' => 1,
            ]);

            $this->seedSeason($schedule, $version);
        }

        $schedule->forceFill([
            'isPublic' => 1,
            'publishedAt' => $schedule->publishedAt ?: now(),
            'publicSummary' => 'Dry-season corn on 2.2 ha, single application basal + two side dressings. '
                . 'Kept the water light on purpose — the field drains fast.',
            'publicRegion' => 'Isabela',
        ])->save();

        $this->seedThread($schedule, $owner);
    }

    private function seedSeason(AsCroppingSchedule $schedule, AsScheduleActivityVersion $version): void
    {
        $dayZero = Carbon::now()->startOfDay()->subDays(45);
        $on = fn (int $das) => $dayZero->copy()->addDays($das)->format('Y-m-d');

        $upper = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName' => 'Upper block',
            'lotSize' => 1.2, 'lotSizeUnit' => 'hectare',
            'dayZeroDate' => $dayZero->format('Y-m-d'),
            'notes' => 'Sandy loam, drains fast. Needs the earlier side dressing.',
            'deleteStatus' => 1,
        ]);
        $lower = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName' => 'Lower block',
            'lotSize' => 1.0, 'lotSizeUnit' => 'hectare',
            'dayZeroDate' => $dayZero->format('Y-m-d'),
            'notes' => 'Holds moisture longer at the bottom end.',
            'deleteStatus' => 1,
        ]);
        $both = [$upper->id, $lower->id];

        $activities = [
            [-14, 'Land preparation — plowing', 'high', 'land_prep', false,
                'Two passes. Left the residue to dry for a week before harrowing.'],
            [-5, 'Harrowing and furrowing', 'high', 'land_prep', false,
                'Furrows 75 cm apart.'],
            [0, 'Planting', 'critical', 'planting', true,
                'Two seeds per hill, 25 cm apart. This is Day 0.'],
            [0, 'Basal fertilizer at planting', 'high', 'fertilizer_granular', false,
                'Complete 14-14-14 placed in the furrow, then covered.'],
            [12, 'First side dressing — Urea', 'high', 'fertilizer_granular', false,
                'Went in just before the first heavy rain, so it washed in.'],
            [21, 'Off-barring and weeding', 'medium', 'weeding', false,
                'Hand weeded the wide gaps the cultivator missed.'],
            [35, 'Second side dressing — Urea', 'high', 'fertilizer_granular', false,
                'At knee height. Skipped the lower block, it was already dark green.'],
            [48, 'Corn borer scouting', 'medium', 'monitoring', false,
                'Checked 20 plants per block. Two hits on the upper block only.'],
            [62, 'Detasseling check', 'low', 'monitoring', false, null],
            [105, 'Harvest', 'critical', 'harvest', false,
                'Moisture was still high — dried two extra days on the pavement.'],
        ];

        $seq = 0;
        foreach ($activities as [$das, $title, $priority, $type, $isDayZero, $description]) {
            $activity = AsScheduleActivity::create([
                'croppingScheduleId' => $schedule->id,
                'versionId' => $version->id,
                'activityTitle' => $title,
                'targetDate' => $on($das),
                'priority' => $priority,
                'activityType' => $type,
                'isDayZero' => $isDayZero ? 1 : 0,
                'isDraft' => 0,
                'isHidden' => 0,
                'description' => $description ? '<p>' . e($description) . '</p>' : null,
                'timeRequired' => 'half',
                'sequenceOrder' => ($seq += 10),
                'deleteStatus' => 1,
            ]);
            $activity->lots()->sync($both);
        }
    }

    /** One question from the demo member, answered by the plan's owner. */
    private function seedThread(AsCroppingSchedule $schedule, User $owner): void
    {
        $asker = User::where('email', 'demo@anisystem.test')->first();
        if (! $asker || $asker->id === $owner->id) {
            return;
        }

        $question = CommunityComment::firstOrCreate(
            [
                'croppingScheduleId' => $schedule->id,
                'anisystemUserId' => $asker->id,
                'parentId' => null,
            ],
            [
                'body' => 'Why did you skip the second side dressing on the lower block? '
                    . 'Mine always looks pale at that stage.',
                'isQuestion' => 1,
                'deleteStatus' => 1,
            ]
        );

        CommunityComment::firstOrCreate(
            [
                'croppingScheduleId' => $schedule->id,
                'anisystemUserId' => $owner->id,
                'parentId' => $question->id,
            ],
            [
                'body' => 'It was already dark green and the bottom end holds water, so more urea '
                    . 'would have gone to leaves instead of ears. If yours is pale, it is probably '
                    . 'drying out faster than mine — check the moisture before you add nitrogen.',
                'isQuestion' => 0,
                'deleteStatus' => 1,
            ]
        );

        CommunityRating::updateOrCreate(
            ['croppingScheduleId' => $schedule->id, 'anisystemUserId' => $asker->id],
            ['rating' => 5, 'review' => 'Clear and honest about what was skipped and why.', 'deleteStatus' => 1]
        );
    }
}
