<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleNote;
use App\Models\AsScheduleWorker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A whole season, compiled once, for the technician to read.
 *
 * Attaching "this season's plan" used to hand over a list of activity titles.
 * What a farmer means by it is everything: where each lot is in its own
 * calendar today, what has actually been done since day zero, what was
 * written down along the way, who is on the team, what is in the store and
 * what has come out of it. Gathering that per question is six modules' worth
 * of queries on every ask.
 *
 * So it is gathered once and kept. Each module contributes its own section —
 * they are built and stored separately, and joined into one document at the
 * moment the model is given it, which is the only moment the whole thing is
 * needed.
 *
 * FRESHNESS WITHOUT HOOKS.
 * The obvious way to keep this current is for every module to say when it has
 * written something. That is a promise every future module also has to keep,
 * and the first one that forgets makes the technician quietly wrong. So
 * nothing is asked to remember: the snapshot stores a fingerprint of what it
 * was built from — the row counts and the newest updated_at of each table it
 * read — and a rebuild happens when the fingerprint no longer matches. Six
 * cheap aggregates, and no module can fall out of step with it.
 */
class SeasonContext
{
    /**
     * What this compiler is up to.
     *
     * Bumped whenever the shape of a snapshot changes, because a snapshot is
     * keyed on the DATA it was built from and would otherwise survive a fix
     * to the code that built it.
     */
    private const BUILD = 2;

    /** Activities are capped: a season with three hundred is not a preamble. */
    private const MAX_ACTIVITIES = 90;

    /** As are notes, which can each be a page long. */
    private const MAX_NOTES = 25;

    /** And moves, of which a busy store makes hundreds. */
    private const MAX_MOVES = 40;

    /** The tables a season is made of, and the module each one speaks for. */
    private const SOURCES = [
        'plan' => 'as_cropping_schedules',
        'lots' => 'as_schedule_lots',
        'activities' => 'as_schedule_activities',
        'notes' => 'as_schedule_notes',
        'workers' => 'as_schedule_workers',
        'inventory' => 'as_inventory_items',
        'moves' => 'as_inventory_moves',
    ];

    /**
     * The season as data, rebuilt only when something has changed.
     *
     * @return array<string, mixed>
     */
    public static function json(AsCroppingSchedule $schedule): array
    {
        $mark = self::fingerprint($schedule->id);
        $table = self::table();

        if ($table) {
            $row = DB::table($table)->where('croppingScheduleId', $schedule->id)->first();
            if ($row && $row->fingerprint === $mark) {
                $held = json_decode((string) $row->payload, true);
                if (is_array($held)) {
                    return $held;
                }
            }
        }

        $built = self::build($schedule);

        if ($table) {
            DB::table($table)->updateOrInsert(
                ['croppingScheduleId' => $schedule->id],
                [
                    'fingerprint' => $mark,
                    'payload' => json_encode($built, JSON_UNESCAPED_UNICODE),
                    'builtAt' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return $built;
    }

    /**
     * The same season as the paragraph the model is actually given.
     *
     * Written rather than handed over as JSON: a language model reads a
     * sentence better than it reads a brace, and the shape costs tokens that
     * say nothing.
     */
    public static function text(AsCroppingSchedule $schedule): string
    {
        $d = self::json($schedule);
        $out = [];

        $p = $d['plan'] ?? [];
        $out[] = 'THE FARMER HAS ATTACHED THIS SEASON as background for THIS question.';
        $out[] = 'Season: "' . ($p['title'] ?? 'Untitled') . '"'
            . (! empty($p['crop']) ? ', ' . $p['crop'] : '')
            . (! empty($p['variety']) ? ' (' . $p['variety'] . ')' : '')
            . (! empty($p['status']) ? ', ' . $p['status'] : '') . '.';
        if (! empty($p['start'])) {
            $out[] = 'Started ' . $p['start'] . '. Today is ' . ($p['today'] ?? '') . '.';
        }

        if (! empty($d['lots'])) {
            $out[] = '';
            $out[] = 'LOTS, as they stand today:';
            foreach ($d['lots'] as $l) {
                $out[] = '- ' . $l['name']
                    . (! empty($l['size']) ? ' (' . $l['size'] . ')' : '')
                    . (! empty($l['crop']) ? ' — ' . $l['crop'] : '')
                    . (! empty($l['variety']) ? ' ' . $l['variety'] : '')
                    . (! empty($l['age']) ? ', ' . $l['age'] : '')
                    . (! empty($l['stage']) ? ', ' . $l['stage'] : '')
                    . '.';
            }
        }

        if (! empty($d['activities'])) {
            $out[] = '';
            $out[] = 'WORK from the first entry to today (oldest first):';
            foreach ($d['activities'] as $a) {
                $out[] = '- ' . $a['when'] . ': ' . $a['title']
                    . (! empty($a['type']) ? ' [' . $a['type'] . ']' : '')
                    . ' — ' . $a['state']
                    . (! empty($a['lots']) ? ' (' . $a['lots'] . ')' : '')
                    . (! empty($a['note']) ? '. ' . $a['note'] : '');
            }
            if (! empty($d['activitiesCut'])) {
                $out[] = '- (…' . $d['activitiesCut'] . ' older entries left out)';
            }
        }

        if (! empty($d['notes'])) {
            $out[] = '';
            $out[] = 'NOTES written along the way:';
            foreach ($d['notes'] as $n) {
                $out[] = '- ' . $n['when'] . ' — ' . $n['title'] . ($n['body'] ? ': ' . $n['body'] : '');
            }
        }

        if (! empty($d['workers'])) {
            $out[] = '';
            $out[] = 'THE TEAM: ' . implode('; ', array_map(
                fn ($w) => $w['name'] . (! empty($w['skills']) ? ' (' . $w['skills'] . ')' : ''),
                $d['workers']
            )) . '.';
        }

        if (! empty($d['inventory'])) {
            $out[] = '';
            $out[] = 'IN THE STORE (on hand now):';
            foreach ($d['inventory'] as $i) {
                $out[] = '- ' . $i['name'] . ': ' . $i['onHand'] . ' ' . $i['unit']
                    . (! empty($i['kind']) ? ' [' . $i['kind'] . ']' : '')
                    . (! empty($i['low']) ? ' — below its low mark' : '');
            }
        }

        if (! empty($d['moves'])) {
            $out[] = '';
            $out[] = 'WHAT HAS MOVED (newest first):';
            foreach ($d['moves'] as $m) {
                $out[] = '- ' . $m['when'] . ': ' . $m['item'] . ' ' . $m['delta']
                    . (! empty($m['reason']) ? ' (' . $m['reason'] . ')' : '');
            }
        }

        $out[] = '';
        $out[] = 'Read it before answering and use it where it bears on the question. '
            . 'It is the farmer\'s own record, not a rule: where it disagrees with good practice, say so plainly. '
            . 'Do not recite it back — answer the question.';
        $out[] = '';
        $out[] = 'Question: ';

        return implode("\n", $out);
    }

    /** About four characters to a token, the same rule the credit service uses. */
    public static function tokens(AsCroppingSchedule $schedule): int
    {
        return (int) ceil(mb_strlen(self::text($schedule)) / 4);
    }

    // ------------------------------------------------------------------

    /**
     * What this season was made of when the snapshot was taken.
     *
     * Count and newest-touch per table. A row added, changed or retired moves
     * one of the two, and no module has to remember to say so.
     */
    private static function fingerprint(int $scheduleId): string
    {
        /* One round trip, not fourteen.
         *
         * A count and a max per table is two queries each, and on a database
         * that is not on this machine every one of them is a fifth of a
         * second — the check meant to make the snapshot cheap cost more than
         * building it. Unioned, the whole fingerprint is one statement. */
        $sql = [];
        $binds = [];
        foreach (self::SOURCES as $key => $table) {
            $col = $key === 'plan' ? 'id' : 'croppingScheduleId';
            $sql[] = "SELECT '$key' AS k, COUNT(*) AS c, MAX(updated_at) AS m FROM $table WHERE $col = ?";
            $binds[] = $scheduleId;
        }

        $parts = [];
        foreach (DB::select(implode(' UNION ALL ', $sql), $binds) as $r) {
            $parts[] = $r->k . ':' . (int) $r->c . ':' . (string) $r->m;
        }
        sort($parts);

        // The builder's own version, so changing what goes INTO a snapshot
        // retires every snapshot built by the old code. Without it a fix to
        // the compiler is invisible until somebody edits their season.
        $parts[] = 'build:' . self::BUILD;

        return substr(hash('sha256', implode('|', $parts)), 0, 40);
    }

    /** @return array<string, mixed> */
    private static function build(AsCroppingSchedule $schedule): array
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        $schedule->loadMissing('lots');

        return [
            'plan' => self::plan($schedule, $today),
            'lots' => self::lots($schedule, $today),
            'activities' => self::activities($schedule, $today, $cut),
            'activitiesCut' => $cut,
            'notes' => self::notes($schedule),
            'workers' => self::workers($schedule),
            'inventory' => self::inventory($schedule),
            'moves' => self::moves($schedule),
        ];
    }

    private static function plan(AsCroppingSchedule $s, Carbon $today): array
    {
        return array_filter([
            'title' => (string) $s->title,
            'crop' => (string) ($s->cropType ?: ''),
            'variety' => (string) ($s->cropVariety ?: ''),
            'status' => (string) ($s->status ?: ''),
            'counter' => (string) ($s->dayType ?: ''),
            'start' => $s->season_start ? Carbon::parse($s->season_start)->format('M j, Y') : '',
            'today' => $today->format('M j, Y'),
        ], fn ($v) => $v !== '');
    }

    private static function lots(AsCroppingSchedule $s, Carbon $today): array
    {
        [$zero, $transplant] = LotCalendar::effectiveAnchors($s);
        $rows = [];
        foreach ($s->lots as $l) {
            if ((int) $l->deleteStatus !== 1) {
                continue;
            }
            $age = LotCalendar::ageOf($l, $today, $zero[$l->id] ?? null, $transplant[$l->id] ?? null);
            $crop = CropStages::normalize($l->crop);
            $stage = $age && $crop ? CropStages::stageFor($crop, $age['day'], $age['counter']) : null;
            $size = trim(rtrim(rtrim((string) $l->lotSize, '0'), '.') . ' ' . $l->lotSizeUnit);

            $rows[] = array_filter([
                'name' => (string) $l->lotName,
                'size' => $size,
                'crop' => (string) ($l->crop ?: ''),
                'variety' => (string) ($l->variety ?: ''),
                'age' => $age ? LotCalendar::says($age) : '',
                'stage' => (string) ($stage['label'] ?? ''),
            ], fn ($v) => $v !== '');
        }

        return $rows;
    }

    /**
     * Everything from the first entry up to today, oldest first.
     *
     * The tail is what is cut when there is too much of it, not the head:
     * "what did I do at the start of this season" is a question the record
     * answers and nothing else does, while the last fortnight is usually
     * still in somebody's head.
     */
    private static function activities(AsCroppingSchedule $s, Carbon $today, &$cut): array
    {
        $cut = 0;
        /* The schedule's own relation, not a bare query on the table.
         *
         * Activities are versioned: every past edit of the plan is still in
         * that table, so a plain where(croppingScheduleId) hands back the
         * same job once per version — the technician was reading "First
         * Plowing" twice and would have believed the field was ploughed
         * twice. The relation filters to the live version and drops drafts,
         * which is what the board itself shows. */
        $all = $s->activities()
            ->with('lots')
            ->whereDate('targetDate', '<=', $today->toDateString())
            ->orderBy('targetDate')
            ->orderBy('id')
            ->get();

        if ($all->count() > self::MAX_ACTIVITIES) {
            // Keep the first third and the most recent two thirds: a season
            // is remembered by how it started and what has just happened.
            $head = (int) floor(self::MAX_ACTIVITIES / 3);
            $tail = self::MAX_ACTIVITIES - $head;
            $cut = $all->count() - self::MAX_ACTIVITIES;
            $all = $all->take($head)->concat($all->slice(-$tail));
        }

        return $all->map(function ($a) {
            $lots = '';
            if (method_exists($a, 'lots')) {
                try {
                    $lots = $a->lots->pluck('lotName')->filter()->implode(', ');
                } catch (\Throwable $e) {
                    $lots = '';
                }
            }

            return array_filter([
                'when' => $a->targetDate ? Carbon::parse($a->targetDate)->format('M j, Y') : 'no date',
                'title' => trim((string) $a->activityTitle) ?: 'Task',
                'type' => (string) ($a->activityType ?: ''),
                'state' => (int) ($a->isDone ?? 0) === 1 ? 'done' : 'not done',
                'lots' => $lots,
                'note' => Str::limit(trim(strip_tags((string) $a->description)), 180, ''),
            ], fn ($v) => $v !== '');
        })->values()->all();
    }

    private static function notes(AsCroppingSchedule $s): array
    {
        return AsScheduleNote::active()
            ->where('croppingScheduleId', $s->id)
            ->orderByDesc('id')
            ->limit(self::MAX_NOTES)
            ->get()
            ->map(fn ($n) => [
                'when' => optional($n->created_at)->format('M j, Y') ?: '',
                'title' => trim((string) $n->title) ?: 'Note',
                'body' => Str::limit(trim(strip_tags((string) $n->body)), 220, ''),
            ])
            ->values()
            ->all();
    }

    private static function workers(AsCroppingSchedule $s): array
    {
        return AsScheduleWorker::active()
            ->where('croppingScheduleId', $s->id)
            ->orderBy('id')
            ->limit(40)
            ->get()
            ->map(fn ($w) => array_filter([
                'name' => trim((string) $w->workerName) ?: 'Worker',
                // Cast on the model, so it arrives as a list rather than a
                // string — and a list joined reads better than one anyway.
                'skills' => trim(is_array($w->skills) ? implode(', ', $w->skills) : (string) $w->skills),
            ], fn ($v) => $v !== ''))
            ->values()
            ->all();
    }

    /**
     * What is in the store, on hand.
     *
     * On-hand is the sum of the moves rather than a number kept on the item —
     * that is how this app has always counted stock, and reading it any other
     * way would let the technician quote a figure the Inventory module does
     * not agree with.
     */
    private static function inventory(AsCroppingSchedule $s): array
    {
        $sums = AsInventoryMove::active()
            ->where('croppingScheduleId', $s->id)
            ->select('itemId', DB::raw('SUM(delta) as onHand'))
            ->groupBy('itemId')
            ->pluck('onHand', 'itemId');

        return AsInventoryItem::active()
            ->where('croppingScheduleId', $s->id)
            ->orderBy('name')
            ->limit(60)
            ->get()
            ->map(function ($i) use ($sums) {
                $on = (float) ($sums[$i->id] ?? 0);

                return array_filter([
                    'name' => (string) $i->name,
                    'kind' => (string) ($i->kind ?: ''),
                    'unit' => (string) ($i->unit ?: ''),
                    'onHand' => rtrim(rtrim(number_format($on, 2, '.', ''), '0'), '.'),
                    'low' => $i->lowAt !== null && $on <= (float) $i->lowAt,
                ], fn ($v) => $v !== '' && $v !== false);
            })
            ->values()
            ->all();
    }

    private static function moves(AsCroppingSchedule $s): array
    {
        $names = AsInventoryItem::where('croppingScheduleId', $s->id)->pluck('name', 'id');

        return AsInventoryMove::active()
            ->where('croppingScheduleId', $s->id)
            ->orderByDesc('id')
            ->limit(self::MAX_MOVES)
            ->get()
            ->map(function ($m) use ($names) {
                $d = (float) $m->delta;

                return array_filter([
                    'when' => $m->happenedOn
                        ? Carbon::parse($m->happenedOn)->format('M j, Y')
                        : (optional($m->created_at)->format('M j, Y') ?: ''),
                    'item' => (string) ($names[$m->itemId] ?? 'Item'),
                    'delta' => ($d > 0 ? '+' : '') . rtrim(rtrim(number_format($d, 2, '.', ''), '0'), '.'),
                    'reason' => (string) ($m->reason ?: ''),
                ], fn ($v) => $v !== '');
            })
            ->values()
            ->all();
    }

    /** Null when the table has not been migrated yet: the snapshot is a cache. */
    private static function table(): ?string
    {
        static $known = null;
        if ($known === null) {
            $known = \Illuminate\Support\Facades\Schema::hasTable('as_ai_season_context')
                ? 'as_ai_season_context'
                : false;
        }

        return $known ?: null;
    }
}
