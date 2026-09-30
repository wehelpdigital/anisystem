<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsFarmReport;
use App\Models\AsProtocol;
use App\Models\AsProtocolAnalysis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What Anee has already worked out, carried into the questions after it
 * (2026-09-30).
 *
 * The owner discussed a Realign reading with her, asked the next question
 * from the season's chat, and met a stranger who had never read it. The same
 * was true of every other thing she makes: a season report, an Analyze So
 * Far, a protocol review, a When to Plant. So two memories:
 *
 *   season()  -- a season's chat knows, in one line each, what she has
 *                already said about that season: her Realign readings, the
 *                reports saved on it, her review of the protocol it follows,
 *                and the planting analyses for its crop. Summaries only; the
 *                full thing is still one tap on the paper clip away.
 *   carried() -- inside one chat, everything the farmer attached keeps
 *                riding along, not only the newest.
 */
class AneeMemory
{
    /** The most of a chat's own earlier attachments carried in full, in characters. */
    public const CARRY_CHARS = 16000;

    private const ANALYSIS_KINDS = [
        'when' => 'When to Plant analysis',
        'variety' => 'Variety research',
        'protocol' => 'Crop Protocol analysis',
    ];

    /** A season's memory, when the season is one this person may read. */
    public static function seasonById($scheduleId, int $userId): string
    {
        if (! $scheduleId) {
            return '';
        }
        $schedule = AsCroppingSchedule::active()
            ->forClient(WorkerContext::effectiveOwnerId())
            ->where('as_cropping_schedules.id', (int) $scheduleId)
            ->first();

        return $schedule ? self::season($schedule, $userId) : '';
    }

    /** What Anee has already said about this season, one line a piece. The caller has checked access. */
    public static function season(AsCroppingSchedule $schedule, int $userId): string
    {
        try {
            $lots = $schedule->lots()->get();
            $parts = array_filter([
                self::section('Your Realign by Anee readings (where each lot\'s crop really is):', self::realigns($lots)),
                self::section('Reports saved on this season:', self::reports($schedule)),
                self::section('Your review of the protocol this season follows:', self::reviews($schedule)),
                self::section('Your planting analyses for this season\'s crop:', self::analyses($lots, $userId)),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
        if (! $parts) {
            return '';
        }

        return "\n\n--- What you (Anee) have already worked out for this season. Reference only: use a line when the question is about it, "
            . "and stand by what you said unless the farmer shows you otherwise. These are summaries: when an answer needs the details, "
            . "say what you remember and ask the farmer to attach that reading, report or analysis with the paper clip so you can read all of it ---\n"
            . implode("\n", $parts)
            . "\n--- END ---\n";
    }

    /**
     * A chat's own earlier attachments: every distinct one, newest first, as
     * much as fits. Returned as the ones that ride on their own turn (still in
     * the history window), a block for the ones that have scrolled out of it,
     * and a line naming any too long to carry again.
     *
     * @param  Collection  $earlier  this chat's earlier user messages carrying attachedContext, newest first
     * @param  array<int>  $window  ids of the turns in the history being sent
     * @return array{turns: array<int,string>, block: string}
     */
    public static function carried(Collection $earlier, array $window, string $thisTurn): array
    {
        $turns = [];
        $out = [];
        $left = [];
        $seen = [];
        $room = self::CARRY_CHARS;
        foreach ($earlier as $m) {
            $text = (string) $m->attachedContext;
            $key = md5(trim($text));
            if (trim($text) === '' || isset($seen[$key]) || ($thisTurn !== '' && str_contains($thisTurn, trim($text)))) {
                continue;
            }
            $seen[$key] = true;
            if (mb_strlen($text) > $room) {
                $left[] = self::titleOf($text);

                continue;
            }
            $room -= mb_strlen($text);
            if (in_array((int) $m->id, $window, true)) {
                $turns[(int) $m->id] = $text;
            } else {
                $out[] = $text;
            }
        }
        $block = '';
        if ($out) {
            $block .= "\n\n--- Attached earlier in this chat (still in play; the farmer may be asking about it) ---"
                . implode('', array_reverse($out)) . "\n--- END OF EARLIER ATTACHMENTS ---\n";
        }
        if ($left) {
            $block .= "\n[Also attached earlier in this chat, too long to carry again: " . implode('; ', array_unique($left))
                . '. If a question needs it, ask the farmer to attach it again.]' . "\n";
        }

        return ['turns' => $turns, 'block' => $block];
    }

    private static function section(string $head, array $lines): string
    {
        return $lines ? $head . "\n" . implode("\n", $lines) : '';
    }

    private static function realigns(Collection $lots): array
    {
        $lines = [];
        foreach ($lots as $lot) {
            $r = is_array($lot->growthRealign) ? $lot->growthRealign : null;
            if (! $r || $lot->growthRealignedAt === null) {
                continue;
            }
            $shift = (int) ($lot->growthShiftDays ?? 0);
            $lines[] = '- ' . $lot->lotName . ' (read ' . $lot->growthRealignedAt->format('M j') . '): '
                . ($r['stageLabel'] ?? '?') . ', ' . ($shift === 0 ? 'on the calendar' : abs($shift) . ' days ' . ($shift < 0 ? 'behind' : 'ahead of') . ' the calendar')
                . ' (the calendar counted ' . ($r['counter'] ?? 'day') . ' ' . ($r['calendarDay'] ?? '?') . '). '
                . Str::limit(trim((string) ($r['summary'] ?? '')), 180);
        }

        return $lines;
    }

    /** The newest saved report of each kind, four at most. */
    private static function reports(AsCroppingSchedule $schedule): array
    {
        return AsFarmReport::where('croppingScheduleId', $schedule->id)
            ->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->limit(24)
            ->get(['id', 'kind', 'title', 'body', 'created_at'])
            ->unique('kind')->take(4)
            ->map(fn ($r) => '- "' . $r->title . '" (saved ' . $r->created_at?->format('M j') . '): ' . self::gist((string) $r->body, 220))
            ->values()->all();
    }

    /** The newest review of each protocol ported into this season. */
    private static function reviews(AsCroppingSchedule $schedule): array
    {
        $lines = [];
        foreach (AsProtocol::active()->where('portedScheduleId', $schedule->id)->orderByDesc('portedAt')->limit(2)->get(['id', 'title']) as $p) {
            $a = AsProtocolAnalysis::where('protocolId', $p->id)->where('deleteStatus', 1)->orderByDesc('id')->first();
            $r = $a && is_array($a->analysis) ? $a->analysis : null;
            if (! $r) {
                continue;
            }
            $lines[] = '- "' . $p->title . '" (reviewed ' . $a->created_at?->format('M j') . '): score ' . (int) ($r['score'] ?? 0) . '/100, '
                . trim((string) ($r['verdict'] ?? '')) . '. ' . Str::limit(trim((string) ($r['headline'] ?? $r['summary'] ?? '')), 200);
        }

        return $lines;
    }

    /** This person's recent planting analyses for a crop the season grows, three at most. */
    private static function analyses(Collection $lots, int $userId): array
    {
        $family = fn ($crop) => strtok((string) (CropStages::normalize($crop) ?? $crop), '_') ?: null;
        $grown = $lots->map(fn ($l) => $family($l->crop))->filter()->unique()->all();
        if (! $grown) {
            return [];
        }

        return DB::table('as_plant_analyses')
            ->where('userId', $userId)->where('status', 'ready')->where('deleteStatus', 1)
            ->whereIn('kind', array_keys(self::ANALYSIS_KINDS))
            ->where('created_at', '>=', now()->subDays(180))
            ->orderByDesc('id')->limit(30)
            ->get(['kind', 'title', 'params', 'report', 'created_at'])
            ->filter(fn ($a) => in_array($family((json_decode($a->params, true) ?: [])['crop'] ?? null), $grown, true))
            ->take(3)
            ->map(function ($a) {
                $r = json_decode($a->report, true) ?: [];
                $say = trim((string) ($r['headline'] ?? ''))
                    ?: trim(($r['bestWindow']['label'] ?? '') . ' ' . ($r['summary'] ?? ''));

                return '- ' . self::ANALYSIS_KINDS[$a->kind] . ' "' . $a->title . '" (' . \Carbon\Carbon::parse($a->created_at)->format('M j') . '): ' . Str::limit($say, 180);
            })
            ->values()->all();
    }

    /** A report's opening lines, after its title and rule: the headline and the totals. */
    private static function gist(string $body, int $max): string
    {
        $lines = preg_split('/\R/', $body) ?: [];
        foreach ($lines as $i => $l) {
            if (preg_match('/^={5,}$/', trim($l))) {
                $lines = array_slice($lines, $i + 1);
                break;
            }
        }
        $keep = [];
        foreach ($lines as $l) {
            // The first ruled section ("BY WORKER" over a dashed line) is
            // where the opening ends; its heading goes with it.
            if (preg_match('/^-{5,}$/', trim($l))) {
                array_pop($keep);
                break;
            }
            $l = trim(trim($l), '- ');
            if ($l === '' || str_starts_with($l, 'Generated:')) {
                continue;
            }
            $keep[] = $l;
            if (mb_strlen(implode(' · ', $keep)) >= $max) {
                break;
            }
        }

        return Str::limit(implode(' · ', $keep), $max);
    }

    /** "Realign by Anee reading" out of "--- ATTACHED: Realign by Anee reading (…) ---". */
    private static function titleOf(string $text): string
    {
        return preg_match('/--- ATTACHED: ([^(\n-]+)/', $text, $m) ? trim($m[1]) : 'an attachment';
    }
}
