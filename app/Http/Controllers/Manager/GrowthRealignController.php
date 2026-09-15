<?php

namespace App\Http\Controllers\Manager;

use App\Models\AiSetting;
use App\Models\AsGrowthRealign;
use App\Models\AsScheduleLot;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropStages;
use App\Support\LotCalendar;
use App\Support\Tier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Realign by Anee: where the crop in a lot ACTUALLY is.
 *
 * The calendar counts days and reads a stage off the count. The plant does
 * not always agree: a herbicide sets it back a week, a heatwave stunts it,
 * a hungry field runs late, a good stretch runs early. Anee reads the
 * lot's whole history since day zero -- every activity and what was
 * applied, the notes and tags, the observations, the sky the field had --
 * and says which stage the crop is in today and how many days it is ahead
 * of or behind the calendar. The lot keeps that as a shift applied to
 * every stage reading (the board's day headers, the Tools sheet, the
 * Growth Stages module), so the whole app reads the plant rather than the
 * date from then on.
 *
 * A paid plan's. Flat price, charged only once the answer has landed and
 * been applied -- when-to-plant's job walk, so the gateway never waits on
 * the model.
 */
class GrowthRealignController extends BaseScheduleController
{
    public const PRICE = AiPrices::DEFAULTS['realign'];

    /**
     * What the run will cost, and whether this lot can be asked about.
     *
     * The price and the balance only. The calendar is NOT read here: that
     * read walks every activity of the season to find the lot's anchors,
     * and on a long season it held the sheet on "Reading the calendar…"
     * for so long it looked hung (the owner's report, 2026-09-15). The
     * page already knows the calendar's reading -- the board and the
     * Growth Stages module both drew it -- and hands it to the sheet; the
     * run itself does the full read, and says so if the lot cannot be.
     */
    public function quote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $lot = $this->lotOf($schedule, (int) $request->query('lotId'));
        $payer = $this->payer();
        $credits = app(AiCreditService::class);

        return $this->jsonOk('ok', ['data' => [
            'price' => AiPrices::of('realign'),
            'balance' => (float) $credits->balance($payer->id),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'locked' => Tier::forSchedule($schedule) === 'libre',
            'aiUsable' => $payer->canUseAi() && AiSetting::current()->isUsable(),
            'lot' => ['id' => (int) $lot->id, 'name' => $lot->lotName, 'crop' => CropStages::label($lot->crop)],
            // Only what is known without a read: a lot with no crop cannot be asked about.
            'blocked' => CropStages::normalize($lot->crop) ? null : $this->whyNot($lot),
            'realign' => $this->applied($lot),
        ]]);
    }

    public function generate(Request $request)
    {
        // A write: scheduleFromRequest refuses a view-only worker and a locked season.
        $schedule = $this->scheduleFromRequest($request);
        $lot = $this->lotOf($schedule, (int) $request->input('lotId'));

        // The door: a paid plan's, said the way every other wall says it.
        if (Tier::forSchedule($schedule) === 'libre') {
            Tier::deny('Realign by Anee comes with a paid plan. Upgrade and she reads your lot\'s whole history to say where the crop really is.', 'solo');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->jsonFail('The AI Technician is not available right now.', 403);
        }
        $asOf = now('Asia/Manila');
        $reading = $this->reading($schedule, $lot, $asOf);
        if (! $reading) {
            return $this->jsonFail($this->whyNot($lot) ?: 'This lot cannot be read yet.', 422);
        }
        $balance = $credits->balance($payer->id);
        if ($balance < AiPrices::of('realign') && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('You need ' . AiPrices::of('realign') . ' credits for this and have '
                . number_format((int) floor($balance)) . '.', 402, ['outOfCredits' => true]);
        }

        /* One in flight per lot -- a double press must not buy two. */
        $standing = AsGrowthRealign::where('lotId', $lot->id)->where('status', 'pending')
            ->where('deleteStatus', 1)->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->jsonOk('Already working on it.', ['data' => ['pending' => true, 'id' => $standing->id]]);
        }

        $row = AsGrowthRealign::create([
            'croppingScheduleId' => $schedule->id,
            'lotId' => $lot->id,
            'userId' => Auth::id(),
            'asOf' => $asOf->toDateString(),
            'calendarDay' => $reading['age']['day'],
            'calendarStage' => $reading['stage']['label'] ?? null,
            'status' => 'pending',
            'deleteStatus' => 1,
        ]);

        $prompt = $this->prompt($schedule, $lot, $reading, $asOf);

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $row->id]])->send();
            fastcgi_finish_request();
            $this->run($row->id, (int) $payer->id, $settings, $prompt, $reading);
            exit;
        }

        @set_time_limit(300);
        $this->run($row->id, (int) $payer->id, $settings, $prompt, $reading);

        return $this->job($row->id);
    }

    /** Where a run stands -- polled until ready or failed. */
    public function job(int $id)
    {
        $r = AsGrowthRealign::where('id', $id)->first();
        if (! $r || (int) $r->userId !== (int) Auth::id()) {
            return $this->jsonFail('That run is gone.', 404);
        }
        if ($r->status === 'failed') {
            return $this->jsonFail($r->error ?: 'The analysis failed. Nothing was charged.', 422);
        }
        if ($r->status !== 'ready') {
            return $this->jsonOk('Working…', ['data' => ['pending' => true, 'id' => $r->id, 'status' => 'pending']]);
        }
        $lot = AsScheduleLot::find($r->lotId);

        return $this->jsonOk('ok', ['data' => [
            'status' => 'ready', 'id' => $r->id, 'credits' => (float) $r->credits,
            'result' => $r->result,
            'lot' => $lot ? ['id' => (int) $lot->id, 'name' => $lot->lotName] : null,
            'realign' => $lot ? $this->applied($lot) : null,
        ]]);
    }

    /* ------------------------------------------------------------------ */

    /** The model call, the charge, and the answer written onto the lot. */
    private function run(int $id, int $payerId, AiSetting $settings, string $prompt, array $reading): void
    {
        $ai = app(AiClient::class);
        $credits = app(AiCreditService::class);
        try {
            $result = $ai->askForJson($settings, $prompt, 2500, fn (string $t) => $this->parse($t));
            $found = $result['data'];
            if ($found === null) {
                Log::warning('growth-realign: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The answer came back unreadable. Nothing was charged — please try again.');
            }

            $row = AsGrowthRealign::findOrFail($id);
            $lot = AsScheduleLot::findOrFail($row->lotId);
            $applied = $this->apply($lot, $reading, $found, $row);

            $price = AiPrices::of('realign');
            $note = AiUsage::record('realign', (int) $row->userId, $payerId, $id, $settings, $result, $price);
            $credits->chargeAllowingNegative($payerId, (float) $price,
                mb_substr('Realign by Anee — ' . mb_substr((string) $lot->lotName, 0, 80) . ' · ' . $row->asOf?->format('M j, Y') . $note, 0, 250));

            $row->update(['result' => $applied, 'credits' => $price, 'status' => 'ready', 'error' => null]);
        } catch (\Throwable $e) {
            report($e);
            AsGrowthRealign::where('id', $id)->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'deleteStatus' => 0,
            ]);
        }
    }

    /**
     * Her answer, made safe and written onto the lot.
     *
     * She names a stage and a physiological day; the shift is the day she
     * names less the day the calendar counted, bounded so one bad answer
     * cannot throw a crop across its whole life. The stage she named is
     * checked against the table: if her day does not fall in it, the day
     * is moved to that stage's first day, because the stage is the thing
     * she was asked for and the day is how the app carries it.
     */
    private function apply(AsScheduleLot $lot, array $reading, array $found, AsGrowthRealign $row): array
    {
        $stages = $reading['stages'];
        $count = count($stages);
        $calDay = (int) $reading['age']['day'];
        $index = max(0, min($count - 1, (int) ($found['stageIndex'] ?? ($reading['stage']['index'] ?? 0))));
        $day = (int) ($found['physiologicalDay'] ?? $calDay);
        $from = (int) $stages[$index][0];
        $until = isset($stages[$index + 1]) ? (int) $stages[$index + 1][0] : null;
        if ($day < $from || ($until !== null && $day >= $until)) {
            $day = $from;
        }
        $cap = max(14, (int) round(($reading['maturity'] ?: 120) * 0.5));
        $shift = max(-$cap, min($cap, $day - $calDay));

        $clean = fn ($v) => is_string($v) ? trim(preg_replace('/\s{2,}/', ' ', preg_replace('/:[a-z0-9_-]+:/i', '', $v))) : '';
        $list = fn ($v) => array_values(array_filter(array_map($clean, is_array($v) ? $v : []), fn ($s) => $s !== ''));
        $applied = [
            'stageIndex' => $index,
            'stageLabel' => (string) $stages[$index][1],
            'calendarStageIndex' => $reading['stage']['index'] ?? null,
            'calendarStageLabel' => $reading['stage']['label'] ?? null,
            'calendarDay' => $calDay,
            'physiologicalDay' => $calDay + $shift,
            'shiftDays' => $shift,
            'counter' => $reading['age']['counter'],
            'confidence' => max(0, min(100, (int) ($found['confidence'] ?? 50))),
            'summary' => $clean($found['summary'] ?? ''),
            'reasons' => array_slice($list($found['reasons'] ?? []), 0, 6),
            'recommendations' => array_slice($list($found['recommendations'] ?? []), 0, 6),
            'watch' => array_slice($list($found['watch'] ?? []), 0, 5),
            'asOf' => $row->asOf?->toDateString(),
            'at' => now('Asia/Manila')->toIso8601String(),
            'runId' => (int) $row->id,
        ];

        $lot->forceFill([
            'growthShiftDays' => $shift,
            'growthRealignedAt' => now('Asia/Manila'),
            'growthRealign' => $applied,
        ])->save();

        return $applied;
    }

    /** The applied answer as the pages draw it, or null. */
    private function applied(AsScheduleLot $lot): ?array
    {
        if ($lot->growthRealignedAt === null || ! is_array($lot->growthRealign)) {
            return null;
        }

        return $lot->growthRealign + ['shiftDays' => (int) $lot->growthShiftDays, 'at' => $lot->growthRealignedAt->toIso8601String()];
    }

    /** The calendar's reading of this lot on a date, and the table it read from. */
    private function reading($schedule, AsScheduleLot $lot, Carbon $on): ?array
    {
        $crop = CropStages::normalize($lot->crop);
        if (! $crop) {
            return null;
        }
        [$dayZeroEff, $transplantEff] = LotCalendar::effectiveAnchors($schedule);
        $age = LotCalendar::ageOf($lot, $on, $dayZeroEff[$lot->id] ?? null, $transplantEff[$lot->id] ?? null);
        if (! $age) {
            return null;
        }
        $maturity = $lot->maturityDays();
        $stages = CropStages::stagesFor($crop, $age['counter'], $maturity);
        $stage = CropStages::stageFor($crop, $age['day'], $age['counter'], $maturity);
        if (! $stages || ! $stage) {
            return null;
        }

        return ['crop' => $crop, 'age' => $age, 'maturity' => $maturity, 'stages' => array_values($stages), 'stage' => $stage];
    }

    private function whyNot(AsScheduleLot $lot): ?string
    {
        if (! CropStages::normalize($lot->crop)) {
            return 'No crop set on this lot yet — say what is growing here in Lots first.';
        }

        return 'This lot has no day zero yet — set one in Lots, or tick "this is day zero" on the activity that starts the count.';
    }

    private function lotOf($schedule, int $lotId): AsScheduleLot
    {
        $lot = $schedule->lots()->where('id', $lotId)->first();
        if (! $lot) {
            abort(response()->json(['success' => false, 'message' => 'That lot is not on this schedule.'], 404));
        }

        return $lot;
    }

    private function payer(): \App\Models\User
    {
        return \App\Models\User::findOrFail((int) \App\Support\WorkerContext::effectiveOwnerId());
    }

    /* ------------------------------ the brief ------------------------------ */

    private function prompt($schedule, AsScheduleLot $lot, array $reading, Carbon $asOf): string
    {
        $age = $reading['age'];
        $stage = $reading['stage'];
        $unit = CropStages::isPerennial($reading['crop']) ? 'month' : 'day';
        $ctx = [];

        $ctx[] = 'THE TASK. You are reading ONE lot of a Philippine farm to say where its crop ACTUALLY is in its growth today, '
            . 'as against where the calendar says it should be. The calendar counts days from day zero (or transplanting) and reads a stage '
            . 'off the count; the plant does not always agree. A herbicide or a wrong spray sets a crop back; heat stress, drought, flooding, '
            . 'a typhoon, pests, a hungry or late-fertilised field, poor establishment, or a slow variety make it run late; a good stretch, '
            . 'early vigour or a fast variety can put it ahead. Read EVERYTHING below -- every activity and what was applied, the notes, the '
            . 'tags, the observations, the weather the field had -- and decide the stage and the physiological age. Be specific about what '
            . 'in the record moved your answer. If the record gives you no reason to move, say so and keep the calendar\'s reading with a '
            . 'confidence that says why.';

        $ctx[] = 'THE LOT: "' . $lot->lotName . '", crop ' . CropStages::label($lot->crop)
            . ($lot->variety ? ' (' . $lot->variety . ')' : '')
            . '. Counter: ' . $age['counter'] . ' (' . GrowthStageController::counterSays($lot->dayType) . ').'
            . ($lot->dayZeroDate ? ' Day zero ' . Carbon::parse($lot->dayZeroDate)->format('M j, Y') . '.' : '')
            . ($lot->transplantDate ? ' Transplanted ' . Carbon::parse($lot->transplantDate)->format('M j, Y') . '.' : '')
            . ($reading['maturity'] ? ' Days to maturity for this lot: ' . $reading['maturity'] . '.' : '')
            . ' Today is ' . $asOf->format('M j, Y') . '.';

        $ctx[] = 'THE CALENDAR SAYS: ' . $age['counter'] . ' ' . $age['day'] . ' -> stage ' . $stage['index'] . ' "' . $stage['label'] . '"'
            . ' (' . $unit . ' ' . ($stage['dayInStage'] + 1) . ' of ' . ($stage['lengthDays'] ?? '?') . ' in this stage).';

        $table = [];
        foreach ($reading['stages'] as $i => $s) {
            $table[] = $i . ': "' . $s[1] . '" from ' . $unit . ' ' . $s[0] . ' -- ' . Str::limit((string) $s[2], 110, '') . '';
        }
        $ctx[] = 'THE STAGE TABLE for this crop and counter (index: name, from which ' . $unit . '): ' . implode(' | ', $table) . '.';

        if ($prev = $this->applied($lot)) {
            $ctx[] = 'A PREVIOUS REALIGNMENT stands: on ' . ($prev['asOf'] ?? '?') . ' the crop was judged ' . ($prev['shiftDays'] ?? 0)
                . ' days ' . (($prev['shiftDays'] ?? 0) < 0 ? 'behind' : 'ahead of') . ' the calendar (' . ($prev['stageLabel'] ?? '') . '): '
                . Str::limit((string) ($prev['summary'] ?? ''), 240, '') . ' Judge afresh today; do not simply repeat it.';
        }

        $ctx[] = $this->history($schedule, $lot, $asOf);
        $wx = $this->weatherHistory($schedule);
        if ($wx !== '') {
            $ctx[] = $wx;
        }

        $ctx[] = 'ANSWER WITH ONE JSON OBJECT ONLY, no fences, no commentary, these keys exactly:'
            . ' {"stageIndex": <integer index from the stage table>, "stageLabel": <its name>,'
            . ' "physiologicalDay": <integer: the ' . $unit . ' of its count the crop is physiologically at today>,'
            . ' "confidence": <0-100>,'
            . ' "summary": <two or three plain sentences a farmer reads in the field: where the crop is, how far off the calendar, and the one reason that matters most>,'
            . ' "reasons": [<3-6 short lines, each naming the specific activity, note, observation or weather that moved the reading, with its date>],'
            . ' "recommendations": [<3-6 short lines of what to do now for THIS stage in THIS state, most urgent first, in a farmer\'s words>],'
            . ' "watch": [<2-5 short lines of what to look for in the field over the next week>]}'
            . ' Write in English with the odd Tagalog farm word where it is the natural one. Keep every line short. No emoji, no shortcodes.';

        return implode("\n\n", $ctx);
    }

    /**
     * Everything that happened on this lot since day zero, in order: the
     * work and what was applied, the notes, the tags, the observations.
     */
    private function history($schedule, AsScheduleLot $lot, Carbon $asOf): string
    {
        $out = [];
        $activities = $schedule->activities()
            ->with(['lots', 'items.material', 'items.service'])
            ->whereDate('targetDate', '<=', $asOf->toDateString())
            ->orderBy('targetDate')->orderBy('id')
            ->get()
            ->filter(fn ($a) => $a->lots->isEmpty() || $a->lots->contains('id', $lot->id));
        $tags = \App\Support\ScheduleTags::forMany((int) $schedule->id, 'activity', $activities->pluck('id')->map(fn ($i) => (int) $i)->all());

        if ($activities->count() > 90) {
            $activities = $activities->take(30)->concat($activities->slice(-60));
        }
        $lines = [];
        foreach ($activities as $a) {
            $bits = [];
            $bits[] = Carbon::parse($a->targetDate)->format('M j') . ': ' . (trim((string) $a->activityTitle) ?: 'Task');
            $types = array_filter(array_merge([(string) $a->activityType], (array) ($a->extraTypes ?? [])));
            if ($types) {
                $bits[] = '[' . implode(', ', $types) . ']';
            }
            if ($a->isDayZero) {
                $bits[] = '(DAY ZERO)';
            }
            if ($a->isTransplant) {
                $bits[] = '(TRANSPLANT)';
            }
            $bits[] = (int) ($a->isDone ?? 0) === 1 ? 'done' : 'NOT done';
            $applied = [];
            foreach ($a->items as $it) {
                $what = $it->material?->materialName ?? $it->material?->name ?? $it->service?->serviceName ?? $it->service?->name ?? null;
                if (! $what) {
                    continue;
                }
                $qty = trim(rtrim(rtrim((string) ($it->quantity ?? ''), '0'), '.') . ' ' . ((string) ($it->unitOfMeasure ?? '')));
                $applied[] = $what . ($qty !== '' ? ' ' . $qty : '');
            }
            if ($applied) {
                $bits[] = 'applied: ' . implode(', ', array_slice($applied, 0, 8));
            }
            if (! empty($tags[(int) $a->id])) {
                $bits[] = 'tags: ' . implode(', ', array_column($tags[(int) $a->id], 'name'));
            }
            $note = Str::limit(trim(strip_tags((string) $a->description)), 200, '');
            if ($note !== '') {
                $bits[] = 'note: "' . $note . '"';
            }
            $lines[] = implode(' ', $bits);
        }
        $out[] = 'THE WORK ON THIS LOT SINCE DAY ZERO (' . count($lines) . ' activities; a task with no lot named covers every lot):'
            . ($lines ? "\n- " . implode("\n- ", $lines) : ' none recorded.');

        // Day notes and inline notes on the board, this lot's or the whole board's.
        $notes = [];
        try {
            $day = \App\Models\AsScheduleDateNote::where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->where(fn ($q) => $q->whereNull('lotId')->orWhere('lotId', 0)->orWhere('lotId', $lot->id))
                ->whereDate('noteDate', '<=', $asOf->toDateString())->orderBy('noteDate')->limit(60)->get();
            foreach ($day as $n) {
                $t = Str::limit(trim(strip_tags((string) $n->noteContent)), 240, '');
                if ($t !== '') {
                    $notes[] = Carbon::parse($n->noteDate)->format('M j') . ': ' . $t;
                }
            }
        } catch (\Throwable $e) {
        }
        try {
            $inline = \App\Models\AsInlineNote::where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->where(fn ($q) => $q->whereNull('lotId')->orWhere('lotId', 0)->orWhere('lotId', $lot->id))
                ->whereDate('noteDate', '<=', $asOf->toDateString())->orderBy('noteDate')->limit(60)->get();
            foreach ($inline as $n) {
                $t = Str::limit(trim(strip_tags((string) $n->content)), 240, '');
                if ($t !== '') {
                    $notes[] = Carbon::parse($n->noteDate)->format('M j') . ': ' . (trim((string) $n->title) ? $n->title . ' — ' : '') . $t;
                }
            }
        } catch (\Throwable $e) {
        }
        try {
            $general = \App\Models\AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)
                ->orderByDesc('id')->limit(20)->get();
            foreach ($general as $n) {
                $t = Str::limit(trim(strip_tags((string) $n->body)), 240, '');
                if ($t !== '') {
                    $notes[] = optional($n->created_at)->format('M j') . ': ' . (trim((string) $n->title) ? $n->title . ' — ' : '') . $t;
                }
            }
        } catch (\Throwable $e) {
        }
        $out[] = 'THE NOTES (day notes, board notes and the season\'s notes):' . ($notes ? "\n- " . implode("\n- ", array_slice($notes, 0, 80)) : ' none.');

        // Observations filed on this lot.
        try {
            $obs = \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->where(fn ($q) => $q->whereNull('lotId')->orWhere('lotId', $lot->id))
                ->orderBy('observationDate')->limit(40)->get();
            $olines = [];
            foreach ($obs as $o) {
                $olines[] = ($o->observationDate ? Carbon::parse($o->observationDate)->format('M j') : '?') . ': '
                    . ($o->category ? '[' . $o->category . '] ' : '') . (trim((string) $o->title) ?: 'Observation')
                    . ($o->notes ? ' — ' . Str::limit(trim(strip_tags((string) $o->notes)), 200, '') : '');
            }
            $out[] = 'THE OBSERVATIONS on this lot:' . ($olines ? "\n- " . implode("\n- ", $olines) : ' none filed.');
        } catch (\Throwable $e) {
        }

        return implode("\n\n", $out);
    }

    /** The sky the field had, month by month (the reports' own reading, cached a day). */
    private function weatherHistory($schedule): string
    {
        try {
            $lat = null;
            $lng = null;
            foreach ($schedule->lots as $L) {
                if ($L->pinLat && $L->pinLng) { $lat = (float) $L->pinLat; $lng = (float) $L->pinLng; break; }
            }
            if ($lat === null) {
                foreach ($schedule->lots as $L) {
                    $place = trim(implode(', ', array_filter([(string) $L->locTown, (string) $L->locProvince])));
                    if ($place !== '') {
                        $geo = app(\App\Services\WeatherService::class)->geocode($place);
                        if ($geo) { $lat = (float) $geo['lat']; $lng = (float) $geo['lon']; break; }
                    }
                }
            }
            if ($lat === null) {
                return '';
            }
            $dates = $schedule->activities->pluck('targetDate')->filter();
            $start = $dates->min()?->format('Y-m-d') ?? now('Asia/Manila')->subMonths(4)->toDateString();
            $end = now('Asia/Manila')->subDays(3)->toDateString();
            if ($start >= $end) {
                return '';
            }
            $key = 'anee-wx-' . md5($lat . '|' . $lng . '|' . $start . '|' . $end);

            return Cache::remember($key, 86400, function () use ($lat, $lng, $start, $end) {
                $res = Http::timeout(20)->get('https://archive-api.open-meteo.com/v1/archive', [
                    'latitude' => $lat, 'longitude' => $lng, 'start_date' => $start, 'end_date' => $end,
                    'daily' => 'precipitation_sum,temperature_2m_max,temperature_2m_min,wind_speed_10m_max',
                    'timezone' => 'Asia/Manila',
                ])->json();
                $days = $res['daily']['time'] ?? [];
                if (! $days) {
                    return '';
                }
                $rain = $res['daily']['precipitation_sum'] ?? [];
                $tmax = $res['daily']['temperature_2m_max'] ?? [];
                $wind = $res['daily']['wind_speed_10m_max'] ?? [];
                $m = [];
                foreach ($days as $i => $d) {
                    $ym = substr($d, 0, 7);
                    $m[$ym] = $m[$ym] ?? ['rain' => 0.0, 'wet' => 0, 'dry' => 0, 'hot' => 0, 'windy' => 0];
                    $r = (float) ($rain[$i] ?? 0);
                    $m[$ym]['rain'] += $r;
                    $m[$ym]['wet'] += $r >= 1 ? 1 : 0;
                    $m[$ym]['dry'] += $r < 1 ? 1 : 0;
                    $m[$ym]['hot'] += ((float) ($tmax[$i] ?? 0)) >= 35 ? 1 : 0;
                    $m[$ym]['windy'] += ((float) ($wind[$i] ?? 0)) >= 40 ? 1 : 0;
                }
                $bits = [];
                foreach ($m as $ym => $v) {
                    $bits[] = $ym . ': ' . round($v['rain']) . 'mm rain over ' . $v['wet'] . ' wet days, ' . $v['dry'] . ' dry days'
                        . ($v['hot'] ? ', ' . $v['hot'] . ' days ≥35°C' : '') . ($v['windy'] ? ', ' . $v['windy'] . ' windy days (≥40 km/h gusts)' : '');
                }

                return 'THE SKY OVER THE SEASON (Open-Meteo daily archive for the field, ' . $start . ' → ' . $end . '): ' . implode(' | ', $bits) . '.';
            });
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Strict-JSON parse: fences stripped, must decode to an object with a stage in it. */
    private function parse(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = preg_replace('/\s*```$/', '', $t);
        $start = strpos($t, '{');
        $endPos = strrpos($t, '}');
        if ($start === false || $endPos === false || $endPos <= $start) {
            return null;
        }
        $obj = json_decode(substr($t, $start, $endPos - $start + 1), true);
        if (! is_array($obj) || ! isset($obj['stageIndex'])) {
            return null;
        }

        return $obj;
    }
}
