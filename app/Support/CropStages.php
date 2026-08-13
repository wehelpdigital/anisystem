<?php

namespace App\Support;

/**
 * What a crop is doing on a given day of its life.
 *
 * A schedule already knows how old every lot is — that is what DAS, DAP and
 * DAT count. What it never said is what that number means for the plant: day
 * 21 of rice is tillering and wants water and nitrogen; day 21 of corn is
 * still building leaves. Growers know this; the app made them hold it in
 * their heads while reading a board that only counted days.
 *
 * Each crop is a list of stages with the day it starts on, in the counter that
 * crop is actually managed by. Everything counts from planting (DAP) except
 * rice, which has two calendars and needs both: transplanted rice counts from
 * transplanting (DAT), and direct-seeded rice — DSR, which is what DAS means
 * — counts from sowing and passes the same stages on different days, because
 * DAT starts about three weeks into the plant's life.
 *
 * The ranges are the common Philippine field guidance — a guide, not a law,
 * which is what the note on the sheet says.
 */
class CropStages
{
    /**
     * crop key => [label, emoji, counter, stages[]]
     * A stage is [from-day, label, what is happening, what it usually needs].
     */
    public const CROPS = [
        /*
         * Rice is two crops as far as a calendar is concerned.
         *
         * Transplanted rice is counted from the day the seedlings went into
         * the paddy (DAT) and starts with a week of recovery. Direct-seeded
         * rice — DSR, counted in DAS from sowing — never had a transplant to
         * recover from: it germinates in the field, and every stage after
         * that falls later in its own count than the same stage does in DAT,
         * because DAT starts about three weeks into the plant's life.
         *
         * Reading one against the other is how a field at DAS 42 gets told it
         * is at panicle initiation when it is still tillering, and told to
         * spend the season's biggest fertiliser a fortnight early.
         */
        'rice' => [
            'label' => 'Rice (Palay)',
            'icon' => '🌾',
            'counter' => 'DAT',
            // Transplanted, from the day of transplanting (~18–21 DAS).
            'stages' => [
                [0, 'Recovery', 'The seedling settles into the paddy and puts out new roots.', 'Shallow water, 2–3 cm. Do not let it dry out.'],
                [7, 'Early tillering', 'Side shoots begin — every one of them is a future panicle.', 'First nitrogen. Keep weeds down now, not later.'],
                [21, 'Active tillering', 'The plant is deciding how many stems it will carry.', 'Second nitrogen. Water 3–5 cm.'],
                [35, 'Panicle initiation', 'The grain head forms inside the stem, out of sight.', 'The important fertiliser. Never let the field dry here.'],
                [50, 'Booting & heading', 'The head swells, then pushes out.', 'Watch for stem borer and blast. Keep water steady.'],
                [60, 'Flowering', 'Pollination — the days that decide how much is filled.', 'No stress of any kind. Water at 3–5 cm.'],
                [73, 'Grain filling', 'Milk, then dough, then hard grain.', 'Drain gradually near the end. Watch for rats and birds.'],
                [90, 'Ripening & harvest', 'Straw yellows, grain hardens.', 'Harvest at 80–85% golden grains.'],
            ],
            // Direct seeded (DSR), from sowing. Same eight stages so the
            // guidance lines up, but at the days a direct-seeded field
            // actually reaches them.
            'stagesDirect' => [
                [0, 'Germination & emergence', 'The seed swells, splits and pushes a shoot through — no transplant, no shock.', 'Keep the field saturated but not flooded until the shoots are through.'],
                [8, 'Seedling establishment', 'Roots take hold and the first true leaves open.', 'Shallow water once the shoots stand. Weeds start here and never get easier.'],
                [21, 'Active tillering', 'The plant is deciding how many stems it will carry.', 'First and second nitrogen. Water 3–5 cm.'],
                [40, 'Panicle initiation', 'The grain head forms inside the stem, out of sight.', 'The important fertiliser. Never let the field dry here.'],
                [55, 'Booting & heading', 'The head swells, then pushes out.', 'Watch for stem borer and blast. Keep water steady.'],
                [70, 'Flowering', 'Pollination — the days that decide how much is filled.', 'No stress of any kind. Water at 3–5 cm.'],
                [85, 'Grain filling', 'Milk, then dough, then hard grain.', 'Drain gradually near the end. Watch for rats and birds.'],
                [105, 'Ripening & harvest', 'Straw yellows, grain hardens.', 'Harvest at 80–85% golden grains.'],
            ],
        ],
        'corn' => [
            'label' => 'Corn (Mais)',
            'icon' => '🌽',
            'counter' => 'DAP',
            'stages' => [
                [0, 'Emergence', 'The shoot breaks through and lives on the seed.', 'Keep the soil damp, not wet. Watch for cutworm.'],
                [10, 'Early vegetative', 'Leaves come one after another; roots go down.', 'First side-dress. Weed early — corn hates competition.'],
                [30, 'Rapid growth', 'The stalk lengthens fast and the plant sets its size.', 'Second side-dress. Water is critical from here.'],
                [45, 'Tasselling', 'The tassel shows and pollen is nearly ready.', 'Do not let it dry out. This is the thirstiest week.'],
                [55, 'Silking & pollination', 'Silks catch pollen — one silk, one kernel.', 'Water every few days. Nothing else matters as much.'],
                [70, 'Grain filling', 'Kernels fill; the ear takes its weight.', 'Steady water. Watch for earworm.'],
                [95, 'Maturity & harvest', 'Husks dry and the kernel dents.', 'Harvest at the black layer, or green at 70–75 days.'],
            ],
        ],
        'banana' => [
            'label' => 'Banana (Saging)',
            'icon' => '🍌',
            'counter' => 'DAP',
            'stages' => [
                [0, 'Establishment', 'The sucker roots and holds.', 'Water weekly. Mulch the base.'],
                [60, 'Vegetative growth', 'Leaf after leaf; the pseudostem thickens.', 'Feed every 6–8 weeks. Desucker to one follower.'],
                [180, 'Late vegetative', 'The plant builds the reserves the bunch will spend.', 'Keep potassium up. Prop tall plants.'],
                [270, 'Shooting', 'The bunch emerges and the fingers set.', 'Bag the bunch. Remove the bell.'],
                [330, 'Bunch filling', 'Fingers fill out and round off.', 'Water steadily. Support against wind.'],
                [390, 'Harvest', 'Fingers are full; angles have softened.', 'Cut at three-quarters full for market.'],
            ],
        ],
        'mango' => [
            'label' => 'Mango (Mangga)',
            'icon' => '🥭',
            'counter' => 'DAP',
            'stages' => [
                [0, 'Flower induction', 'The tree is pushed to flower rather than flush.', 'Induce on mature, rested flushes only.'],
                [14, 'Flowering', 'Panicles open over two to three weeks.', 'Protect from hoppers and anthracnose. No overhead water.'],
                [35, 'Fruit set', 'Most of what set will now drop — that is normal.', 'Steady moisture. Spray on schedule.'],
                [55, 'Fruit growth', 'The fruit sizes up.', 'Bag the fruit. Feed potassium.'],
                [95, 'Maturation', 'Shoulders fill and the flesh yellows inside.', 'Ease off water near the end for sweetness.'],
                [115, 'Harvest', 'Specific gravity says ready before the eye does.', 'Harvest with a pedicel; keep the latex off the skin.'],
            ],
        ],
        'sugarcane' => [
            'label' => 'Sugarcane (Tubo)',
            'icon' => '🎋',
            'counter' => 'DAP',
            'stages' => [
                [0, 'Germination', 'Buds sprout from the setts.', 'Keep the furrow moist. Fill gaps by day 30.'],
                [45, 'Tillering', 'The stool forms — the number of millable canes is set here.', 'First fertiliser. Weed thoroughly.'],
                [120, 'Grand growth', 'The cane lengthens fastest of all; most of the yield is made now.', 'Water and nitrogen. Earth up.'],
                [270, 'Maturation', 'Sugar accumulates from the bottom up.', 'Withhold nitrogen. Ease water.'],
                [330, 'Ripening & harvest', 'Brix rises and leaves dry off.', 'Harvest on mill schedule; burn or green-cut as agreed.'],
            ],
        ],
        'coconut' => [
            'label' => 'Coconut (Niyog)',
            'icon' => '🥥',
            'counter' => 'DAP',
            'stages' => [
                [0, 'Establishment', 'The seedling roots and holds.', 'Water in dry months. Ring weed.'],
                [365, 'Juvenile', 'Fronds build; no fruit yet.', 'Feed twice a year. Keep the ring clean.'],
                [1460, 'First flowering', 'Inflorescences appear.', 'Salt and potassium. Watch for beetle.'],
                [1825, 'Bearing', 'Nuts set and mature over the year.', 'Harvest every 45–60 days.'],
            ],
        ],
        'vegetables' => [
            'label' => 'Vegetables (Gulay)',
            'icon' => '🥬',
            'counter' => 'DAP',
            'stages' => [
                [0, 'Seedling', 'Roots and first true leaves.', 'Light, frequent water. Shade at midday if harsh.'],
                [14, 'Vegetative', 'Leaf and frame growth.', 'Nitrogen. Stake what needs staking.'],
                [30, 'Flowering', 'Flowers set the crop.', 'Even moisture. Do not overfeed nitrogen now.'],
                [45, 'Fruiting / heading', 'The part you sell forms.', 'Potassium. Watch for worms.'],
                [60, 'Harvest', 'Pick as it comes; keep picking.', 'Harvest in the cool of the morning.'],
            ],
        ],
    ];

    /** The list a lot's crop picker offers. */
    public static function options(): array
    {
        return collect(self::CROPS)
            ->map(fn ($c, $key) => ['value' => $key, 'label' => $c['label'], 'icon' => $c['icon']])
            ->values()->all();
    }

    /** A stored crop key, matched loosely so old free-text values still land. */
    public static function normalize(?string $crop): ?string
    {
        $crop = strtolower(trim((string) $crop));
        if ($crop === '') {
            return null;
        }
        if (isset(self::CROPS[$crop])) {
            return $crop;
        }
        foreach (self::CROPS as $key => $c) {
            if (str_contains($crop, $key) || str_contains(strtolower($c['label']), $crop)) {
                return $key;
            }
        }

        return null;
    }

    public static function label(?string $crop): ?string
    {
        $key = self::normalize($crop);

        return $key ? self::CROPS[$key]['label'] : null;
    }

    public static function icon(?string $crop): string
    {
        $key = self::normalize($crop);

        return $key ? self::CROPS[$key]['icon'] : '🌱';
    }

    /**
     * The stage table to read a lot against.
     *
     * A crop can be grown more than one way, and the way decides the
     * calendar: rice counted in DAS was direct seeded and has never been
     * transplanted, so it wants its own timeline rather than the transplanted
     * one shifted by three weeks.
     */
    public static function stagesFor(?string $crop, ?string $counter = null): array
    {
        $key = self::normalize($crop);
        if (! $key) {
            return [];
        }

        $crop = self::CROPS[$key];
        $direct = $counter !== null
            && strtoupper($counter) !== 'DAT'
            && isset($crop['stagesDirect']);

        return $direct ? $crop['stagesDirect'] : $crop['stages'];
    }

    /**
     * Where this crop is on day $day of its count.
     *
     * @param  string|null  $counter  which count the day is in — 'DAT', 'DAS'
     *                                or 'DAP'. Rice reads differently in each.
     *
     * @return array{index:int,label:string,what:string,needs:string,from:int,
     *     until:?int,dayInStage:int,lengthDays:?int,progress:?float,
     *     next:?array{label:string,inDays:int}}|null
     */
    public static function stageFor(?string $crop, ?int $day, ?string $counter = null): ?array
    {
        $key = self::normalize($crop);
        if (! $key || $day === null) {
            return null;
        }

        $stages = self::stagesFor($key, $counter);
        $at = null;
        foreach ($stages as $i => $s) {
            if ($day >= $s[0]) {
                $at = $i;
            }
        }
        if ($at === null) {
            // Before day zero: the first stage has not started yet.
            return null;
        }

        [$from, $label, $what, $needs] = $stages[$at];
        $until = isset($stages[$at + 1]) ? $stages[$at + 1][0] : null;
        $length = $until !== null ? $until - $from : null;
        $inStage = $day - $from;

        return [
            'index' => $at,
            'count' => count($stages),
            'label' => $label,
            'what' => $what,
            'needs' => $needs,
            'from' => $from,
            'until' => $until,
            'dayInStage' => $inStage,
            'lengthDays' => $length,
            'progress' => $length ? min(1, max(0, $inStage / $length)) : null,
            'next' => isset($stages[$at + 1])
                ? ['label' => $stages[$at + 1][1], 'inDays' => $stages[$at + 1][0] - $day]
                : null,
        ];
    }

    /** Every stage of a crop, flagged with which one a day falls in. */
    public static function timeline(?string $crop, ?int $day = null, ?string $counter = null): array
    {
        $key = self::normalize($crop);
        if (! $key) {
            return [];
        }
        $current = self::stageFor($key, $day, $counter);

        return collect(self::stagesFor($key, $counter))->map(fn ($s, $i) => [
            'from' => $s[0],
            'label' => $s[1],
            'what' => $s[2],
            'needs' => $s[3],
            'isNow' => $current && $current['index'] === $i,
            'isPast' => $current && $i < $current['index'],
        ])->all();
    }

    /** Which counter this crop is managed by ('DAT' or 'DAP'). */
    public static function counter(?string $crop): string
    {
        $key = self::normalize($crop);

        return $key ? self::CROPS[$key]['counter'] : 'DAP';
    }
}
