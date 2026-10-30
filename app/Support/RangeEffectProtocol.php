<?php

namespace App\Support;

use App\Http\Controllers\ProtocolBuilderController;
use App\Models\AsCroppingSchedule;
use App\Models\AsProtocol;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleLot;
use Illuminate\Support\Carbon;

/**
 * A protocol of the Protocol Builder, as a season the Coverage Planner reads (2026-10-30, the owner: "this module should
 * also be in the protocol builder so that as you plan in the protocol builder, you can also open it and check if you are
 * doing right (you can select the lot where it will be applied to as a basis)").
 *
 * One lot on the protocol's crop and count, its day zero (and the transplant, for a DAT protocol) on the dates picked,
 * the protocol's tasks as that lot's tasks on those dates with what they apply: a material's own amount, or a line's
 * amount as written ("0.1 bag"). Built in memory only: nothing is written to a season, and the reading is never saved
 * against one. The soil is the basis lot's from Global Lots, or a what-if lot's (RangeEffect::run custom).
 */
class RangeEffectProtocol
{
    /** The protocol's counts the planner can read: a tree protocol counts its days another way. */
    public static function readable(AsProtocol $p): bool
    {
        return in_array(strtoupper((string) $p->dayType), ['DAS', 'DAT', 'DAP'], true) && ! CropStages::isPerennial($p->crop);
    }

    /**
     * The season in memory: its one lot (id 0) and the tasks.
     *
     * @param  array  $tasks  the version's entries (tasks, notes, dividers), as the builder keeps them
     * @param  array  $materials  the version's materials
     */
    public static function season(AsProtocol $p, array $tasks, array $materials, string $start, ?string $transplant, float $ha, string $lotName): AsCroppingSchedule
    {
        $isDat = strtoupper((string) $p->dayType) === 'DAT';
        $counter = strtoupper((string) $p->dayType) === 'DAP' ? 'DAP' : ($isDat ? 'DAT' : 'DAS');
        $s = new AsCroppingSchedule();
        $s->forceFill(['id' => 0, 'title' => (string) $p->title, 'cropType' => $p->crop, 'anisystemUserId' => (int) $p->userId, 'status' => 'generated', 'deleteStatus' => 1]);
        $lot = new AsScheduleLot();
        $lot->forceFill(['id' => 0, 'croppingScheduleId' => 0, 'lotName' => $lotName, 'crop' => $p->crop, 'variety' => $p->variety, 'dayType' => $counter,
            'dayZeroDate' => $start, 'transplantDate' => $isDat ? $transplant : null, 'lotSize' => $ha > 0 ? $ha : 1, 'lotSizeUnit' => 'hectare', 'deleteStatus' => 1]);
        $mat = [];
        foreach ($materials as $m) {
            if (is_array($m) && isset($m['id'])) {
                $mat[(string) $m['id']] = $m;
            }
        }
        $acts = [];
        $n = 0;
        foreach ($tasks as $t) {
            if (! is_array($t) || ! ProtocolBuilderController::isTask($t)) {
                continue;
            }
            $base = ($t['counter'] ?? '') === 'DAT' && $isDat && $transplant ? Carbon::parse($transplant) : Carbon::parse($start);
            $day = (int) ($t['day'] ?? 0);
            $items = [];
            foreach ((array) ($t['groups'] ?? []) as $g) {
                foreach ((array) ($g['items'] ?? []) as $it) {
                    if (! is_array($it)) {
                        continue;
                    }
                    $m = isset($it['materialId']) ? ($mat[(string) $it['materialId']] ?? null) : null;
                    $use = $m && is_array($g) ? ProtocolBuilderController::itemUse($g, $it) : null;
                    $name = trim((string) ($m['name'] ?? ($it['name'] ?? '')));
                    if ($name === '') {
                        continue;
                    }
                    [$qty, $unit] = $use !== null ? [$use, (string) ($m['unit'] ?? '')] : self::amount((string) ($it['amount'] ?? ''));
                    $line = new AsScheduleActivityItem();
                    $line->forceFill(['id' => 0, 'activityId' => $n - 1, 'itemType' => 'custom', 'itemName' => mb_substr($name, 0, 255), 'quantity' => $qty,
                        'unitOfMeasure' => $unit !== '' ? $unit : null, 'inventoryItemId' => null, 'deleteStatus' => 1]);
                    $line->setRelation('material', null);
                    $items[] = $line;
                }
            }
            $a = new AsScheduleActivity();
            $a->forceFill(['id' => --$n, 'croppingScheduleId' => 0, 'activityTitle' => mb_substr((string) ($t['title'] ?? 'Task'), 0, 255),
                // A watering's words say its depth and how it goes on ("Irrigate 3 to 5 cm"), for the water check.
                'subtitle' => mb_substr((string) ($t['subtitle'] ?? ''), 0, 255), 'description' => mb_substr(strip_tags((string) ($t['description'] ?? '')), 0, 2000),
                'targetDate' => $base->copy()->addDays($day)->toDateString(), 'activityType' => (string) ($t['type'] ?? 'other'),
                'isDayZero' => $day === 0 && ($t['counter'] ?? '') !== 'DAT', 'isTransplant' => $day === 0 && ($t['counter'] ?? '') === 'DAT' && $isDat,
                'isDraft' => false, 'isHidden' => false, 'isDone' => false, 'deleteStatus' => 1]);
            $a->setRelation('items', collect($items));
            $a->setRelation('lots', collect());
            $acts[] = $a;
        }
        usort($acts, fn ($x, $y) => strcmp((string) $x->targetDate, (string) $y->targetDate));
        $s->setRelation('lots', collect([$lot]));
        $s->setRelation('activities', collect($acts));

        return $s;
    }

    /** A line's amount as written: "0.1 bag", "250 mL", "2 sachets" -> [qty, unit]; [0, ''] when it says none. */
    public static function amount(string $said): array
    {
        if (preg_match('/^\s*([\d]+(?:[.,]\d+)?)\s*([a-zA-Z]+)?/u', $said, $m)) {
            return [(float) str_replace(',', '.', $m[1]), (string) ($m[2] ?? '')];
        }

        return [0.0, ''];
    }
}
