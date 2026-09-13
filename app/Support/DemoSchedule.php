<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The worked example every account is handed on the way in.
 *
 * A new member lands on an app with nineteen modules and nothing in any of
 * them. Every door opens onto an empty room, and an empty room teaches
 * nobody what the room is for. So they are given a small season that is
 * already running — two lots on different crops, a hired hand, three weeks
 * of work with some of it ticked off, money against a few days — and a
 * guided walk that points at the parts of it.
 *
 * WHAT MAKES THIS ONE DIFFERENT from a season the farmer made:
 *
 *  - It carries the isDemo flag, which is what the guided walk's button
 *    asks for. No other season offers the walk.
 *  - It does not spend the one active season a free account may keep. See
 *    User::canCreateSchedule.
 *  - It is given ONCE. Someone who deletes it has decided something, and a
 *    demo that grows back on the next login is not a gift, it is a weed.
 *
 * Everything in it is ordinary data through the ordinary tables. Nothing in
 * the app has to know it is a demo except the three things above — so every
 * module opens it, edits it and reports on it exactly as it would any other
 * season, which is the whole point of practising on it.
 */
class DemoSchedule
{
    public const TITLE = 'Demo Season — a worked example';

    /** Has this account ever been given one, kept or not? */
    public static function existsFor(int $userId): bool
    {
        return AsCroppingSchedule::withoutGlobalScopes()
            ->where('anisystemUserId', $userId)
            ->where('isDemo', true)
            ->exists();
    }

    /** The one they still have, if they kept it. */
    public static function forUser(int $userId): ?AsCroppingSchedule
    {
        return AsCroppingSchedule::active()
            ->where('anisystemUserId', $userId)
            ->where('isDemo', true)
            ->first();
    }

    /**
     * Build it, once.
     *
     * Returns the schedule, or null if they already have one or the build
     * failed. A failure is logged and swallowed: a demo that could not be
     * written is not a reason to refuse somebody the account they just
     * confirmed, and the signup path calls this.
     */
    public static function createFor(User $user): ?AsCroppingSchedule
    {
        if (self::existsFor($user->id)) {
            return null;
        }

        try {
            return DB::transaction(fn () => self::build($user));
        } catch (\Throwable $e) {
            Log::warning('Demo schedule failed for user ' . $user->id . ': ' . $e->getMessage());

            return null;
        }
    }

    private static function build(User $user): AsCroppingSchedule
    {
        /* Dated from TODAY, backwards and forwards.
         *
         * A demo with fixed dates is a museum piece: opened six months after
         * it was written every day in it is in the past, the board opens on
         * nothing, and the growth stages read as a harvest long gone. Anchored
         * to the day it is made, it is always a season in progress — some work
         * behind, today's work waiting, and a few weeks still to come. */
        $today = Carbon::today();
        $sown = $today->copy()->subDays(20);

        $schedule = new AsCroppingSchedule();
        $schedule->anisystemUserId = $user->id;
        /* Ownership is anisystemUserId. usersId is the MOTHER site's account
         * column — not null, and not something an anee.io member has one of,
         * so every season this app writes puts the configured house id there
         * exactly as the create screen does. Reading it off the member gave
         * null, and null is what that column refuses. */
        $schedule->usersId = (int) config('anisystem.order_users_id', 1);
        $schedule->title = self::TITLE;
        $schedule->description = 'Yours to poke at. Change anything, break anything — nothing here touches a real farm. Delete it whenever you are done with it.';
        $schedule->cropType = 'rice';
        $schedule->dayType = 'DAS';
        $schedule->status = 'generated';
        $schedule->isDemo = true;
        $schedule->isActive = 1;
        $schedule->deleteStatus = 1;
        $schedule->save();

        // Every season needs a version for its activities to belong to.
        $versionId = DB::table('as_schedule_activity_versions')->insertGetId([
            'croppingScheduleId' => $schedule->id,
            'versionName' => 'Original',
            'isOriginal' => 1,
            'isActive' => 1,
            'versionOrder' => 1,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /* Two lots, on purpose: one crop teaches nothing about the lot
         * chips, the day counters or the filters. Two different crops on
         * two different clocks teach all three at a glance. */
        $lotA = DB::table('as_schedule_lots')->insertGetId([
            'croppingScheduleId' => $schedule->id,
            'lotName' => 'Riverside',
            'lotSize' => 1.5,
            'lotSizeUnit' => 'ha',
            'crop' => 'rice',
            'variety' => 'IR64',
            'dayType' => 'DAS',
            'dayZeroDate' => $sown->toDateString(),
            'notes' => 'The low block by the river. Floods first, dries last.',
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lotB = DB::table('as_schedule_lots')->insertGetId([
            'croppingScheduleId' => $schedule->id,
            'lotName' => 'Upper Field',
            'lotSize' => 0.8,
            'lotSizeUnit' => 'ha',
            'crop' => 'corn',
            'variety' => 'Sweet corn',
            'dayType' => 'DAP',
            'dayZeroDate' => $today->copy()->subDays(12)->toDateString(),
            'notes' => 'Planted later than the rice, and on higher ground.',
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // One hired hand, so wages have somebody to be owed to and the day's
        // cash pill has something to add up.
        $worker = DB::table('as_schedule_workers')->insertGetId([
            'croppingScheduleId' => $schedule->id,
            'workerName' => 'Mang Ben',
            'phone' => '09171234567',
            'costPerHalfDay' => 250,
            // Both of these are shaped, not free text: skills is a JSON list
            // the roster reads back, and priority is a rank the column stores
            // as an integer. Written as words they were refused.
            'skills' => json_encode(['spray', 'weeding', 'harrowing']),
            'priority' => 1,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /* The plan: [days from today, title, type, lot, done?, whole day?]
         *
         * Behind today it is ticked off, today and ahead it is not — so the
         * board opens the way a real one does mid-season, and the tour has a
         * done card and an open card to point at without inventing either. */
        $plan = [
            [-18, 'Land preparation — first pass', 'land_prep', $lotA, true,  true],
            [-15, 'Sowing — IR64 across Riverside', 'planting', $lotA, true,  true],
            [-11, 'Basal fertiliser', 'fertilizer', $lotA, true, false],
            [-9,  'Planting — sweet corn, Upper Field', 'planting', $lotB, true, true],
            [-5,  'First weeding', 'herbicide', $lotA, true, false],
            [-2,  'Water check after the rain', 'irrigation', $lotA, true, false],
            [0,   'Top dressing — urea', 'fertilizer', $lotA, false, false],
            [0,   'Walk the corn for fall armyworm', 'pesticide', $lotB, false, false],
            [3,   'Second weeding', 'herbicide', $lotA, false, true],
            [7,   'Spray — leaf folder watch', 'pesticide', $lotA, false, false],
            [12,  'Side dressing — the corn', 'fertilizer', $lotB, false, false],
            [18,  'Check the grain fill', 'other', $lotA, false, false],
        ];

        foreach ($plan as $i => [$offset, $title, $type, $lotId, $done, $whole]) {
            $a = new AsScheduleActivity();
            $a->croppingScheduleId = $schedule->id;
            $a->versionId = $versionId;
            $a->activityTitle = $title;
            $a->targetDate = $today->copy()->addDays($offset)->toDateString();
            $a->activityType = $type;
            $a->priority = $offset <= 0 ? 'high' : 'medium';
            $a->isDone = $done;
            $a->isDraft = 0;
            $a->isHidden = 0;
            $a->sequenceOrder = ($i + 1) * 10;
            $a->timeRequired = $whole ? 'whole' : 'half';
            // Sowing is what DAS counts from; the board reads day zero off it.
            $a->isDayZero = $title === 'Sowing — IR64 across Riverside';
            $a->save();
            $a->lots()->sync([$lotId]);
            // Somebody has to be on the work for a day to cost anything.
            if (in_array($offset, [-15, -5, 0, 3, 7], true)) {
                DB::table('as_schedule_activity_workers')->insert([
                    'activityId' => $a->id,
                    'workerId' => $worker,
                    'dayPart' => $whole ? 'whole' : 'half',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // A couple of costs that are not wages, so the day's money has two
        // halves to divide and the range total has something to add up.
        foreach ([[-15, 3200, 'Seed — IR64, 2 bags'], [-11, 4800, 'Fertiliser — complete'], [0, 1750, 'Urea']] as $j => [$offset, $amount, $note]) {
            DB::table('as_schedule_day_expenses')->insert([
                'croppingScheduleId' => $schedule->id,
                'versionId' => $versionId,
                'expenseDate' => $today->copy()->addDays($offset)->toDateString(),
                'amount' => $amount,
                'note' => $note,
                'sortOrder' => ($j + 1) * 10,
                'blockSort' => 0,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $schedule->fresh();
    }
}
