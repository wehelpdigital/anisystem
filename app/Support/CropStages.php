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
 * WHERE THE STAGES COME FROM
 *
 * Two places, and the split is deliberate.
 *
 * A handful of crops have calendars that are genuinely their own and are
 * written out here by hand: rice, which is two crops as far as a calendar is
 * concerned; the two corns, which share a plant and not a season; sugarcane;
 * and banana. Their day numbers are the crop's real dates and are used as
 * written.
 *
 * Everything else names a shape and a maturity in CropCatalog, and its
 * stages are laid out against whichever maturity applies — the lot's own
 * figure when it has one, the crop's typical figure otherwise. That is what
 * lets one entry serve a 100-day cabbage in Benguet and an 80-day one in the
 * lowlands, and it is why a lot that knows its days to maturity gets a
 * calendar that actually matches its field.
 *
 * Perennials count months of age rather than days from planting, because a
 * mango tree has no day 40 — it has an age, and what it wants depends on
 * whether it is four years old or fourteen.
 *
 * The ranges are the common Philippine field guidance — a guide, not a law,
 * which is what the note on the sheet says.
 */
class CropStages
{
    /**
     * The crops whose calendar is their own, written out in real days.
     *
     * A stage is [from-day, label, what is happening, what it usually needs].
     * These are NOT stretched to a lot's maturity: the numbers are the crop's
     * actual dates, not a shape fitted to a length.
     */
    public const TABLES = [
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
            'stages' => [
                [0, 'Recovery', 'The seedlings settle and put out new roots.', 'Shallow water, 2–3 cm. Do not let it dry out.'],
                [7, 'Early tillering', 'The first tillers come out from the base.', 'First nitrogen. Keep the water shallow.'],
                [21, 'Active tillering', 'Tiller after tiller — the panicle count is decided here.', 'Weed now. Keep 3–5 cm of water.'],
                [35, 'Panicle initiation', 'The panicle forms inside the stem, out of sight.', 'The biggest fertiliser of the season goes on here.'],
                [50, 'Booting & heading', 'The flag leaf swells; panicles push out.', 'Never let the field dry. Watch for stem borer.'],
                [60, 'Flowering', 'Pollination — a few days that set the grain.', 'Keep water on. Do not spray at midday.'],
                [73, 'Grain filling', 'Grains fill from milk to dough.', 'Water to the dough stage. Guard against rats and birds.'],
                [90, 'Ripening & harvest', 'Grain hardens and the straw turns.', 'Drain 7–10 days before cutting. Harvest at 80–85% golden.'],
            ],
            // Direct seeded (DSR), from sowing.
            'stagesDirect' => [
                [0, 'Germination & emergence', 'The seed sprouts where it will stand.', 'Keep the bed saturated, not flooded. Guard against birds.'],
                [8, 'Seedling establishment', 'Roots anchor and the first leaves open.', 'Shallow water once anchored. Weed early — DSR fights weeds.'],
                [21, 'Active tillering', 'Tillers build the panicle count.', 'First and second nitrogen. Keep 3–5 cm of water.'],
                [40, 'Panicle initiation', 'The panicle forms inside the stem.', 'The season\'s biggest fertiliser goes on here.'],
                [55, 'Booting & heading', 'The flag leaf swells; panicles push out.', 'Never let the field dry. Watch for stem borer.'],
                [70, 'Flowering', 'Pollination — a few days that set the grain.', 'Keep water on. Do not spray at midday.'],
                [85, 'Grain filling', 'Grains fill from milk to dough.', 'Water to the dough stage. Guard against rats and birds.'],
                [105, 'Ripening & harvest', 'Grain hardens and the straw turns.', 'Drain 7–10 days before cutting.'],
            ],
        ],
        'corn_yellow' => [
            'stages' => [
                [0, 'Emergence', 'The shoot breaks through and lives on the seed.', 'Keep the soil damp, not wet. Watch for cutworm.'],
                [10, 'Early vegetative', 'Leaves come one after another; roots go down.', 'First side-dress. Weed early — corn hates competition.'],
                [30, 'Rapid growth', 'The stalk lengthens fast and the plant sets its size.', 'Second side-dress. Water is critical from here.'],
                [45, 'Tasselling', 'The tassel shows and pollen is nearly ready.', 'Do not let it dry out. This is the thirstiest week.'],
                [55, 'Silking & pollination', 'Silks catch pollen — one silk, one kernel.', 'Water every few days. Nothing else matters as much.'],
                [70, 'Grain filling', 'Kernels fill; the ear takes its weight.', 'Steady water. Watch for earworm.'],
                [95, 'Maturity & harvest', 'Husks dry and the kernel dents.', 'Harvest at the black layer, when the husk has dried down.'],
            ],
        ],
        /*
         * Sweet corn is picked green, at the milk stage, about a month
         * before field corn is dry. Read against the field-corn table it
         * would be told to wait for a black layer that will never come, and
         * the ear would go starchy in the meantime.
         */
        'corn_sweet' => [
            'stages' => [
                [0, 'Emergence', 'The shoot breaks through and lives on the seed.', 'Keep the soil damp, not wet. Watch for cutworm.'],
                [8, 'Early vegetative', 'Leaves come one after another.', 'First side-dress. Weed early.'],
                [24, 'Rapid growth', 'The stalk lengthens and sets the plant\'s size.', 'Second side-dress. Water is critical from here.'],
                [38, 'Tasselling', 'The tassel shows and pollen is nearly ready.', 'Do not let it dry out. Plant in blocks, not rows, for pollination.'],
                [46, 'Silking & pollination', 'Silks catch pollen — one silk, one kernel.', 'Water every few days. A missed silk is a missing kernel.'],
                [58, 'Milk stage', 'Kernels fill with sweet liquid; this is the eating stage.', 'Steady water. Watch for earworm at the silk.'],
                [70, 'Harvest', 'Silks brown, kernels squirt milky juice when pressed.', 'Pick in the cool of the morning and cool it fast — sugar turns to starch within hours.'],
            ],
        ],
        'sugarcane' => [
            'stages' => [
                [0, 'Germination', 'Buds sprout from the setts.', 'Keep the furrow moist. Fill gaps by day 30.'],
                [45, 'Tillering', 'The stool forms — the number of millable canes is set here.', 'First fertiliser. Weed thoroughly.'],
                [120, 'Grand growth', 'The cane lengthens fastest of all; most of the yield is made now.', 'Water and nitrogen. Earth up.'],
                [270, 'Maturation', 'Sugar accumulates from the bottom up.', 'Withhold nitrogen. Ease water.'],
                [330, 'Ripening & harvest', 'Brix rises and leaves dry off.', 'Harvest on mill schedule; burn or green-cut as agreed.'],
            ],
        ],
        'banana' => [
            'stages' => [
                [0, 'Establishment', 'The sucker roots and holds.', 'Water weekly. Mulch the base.'],
                [60, 'Vegetative growth', 'Leaf after leaf; the pseudostem thickens.', 'Feed every 6–8 weeks. Desucker to one follower.'],
                [180, 'Late vegetative', 'The plant builds the reserves the bunch will spend.', 'Keep potassium up. Prop tall plants.'],
                [270, 'Shooting', 'The bunch emerges and the fingers set.', 'Bag the bunch. Remove the bell.'],
                [330, 'Bunch filling', 'Fingers fill out and round off.', 'Water steadily. Support against wind.'],
                [390, 'Harvest', 'Fingers are full; angles have softened.', 'Cut at three-quarters full for market.'],
            ],
        ],
    ];

    /**
     * Every crop the app knows, as one map.
     *
     * The catalogue's facts, with a hand-written stage table folded in where
     * one exists. Memoised because the picker, the board and the growth
     * module all ask for it on the same request.
     */
    public static function crops(): array
    {
        static $all = null;
        if ($all !== null) {
            return $all;
        }

        $all = [];
        foreach (CropCatalog::CROPS as $key => $c) {
            $all[$key] = $c + (self::TABLES[$key] ?? []);
        }

        return $all;
    }

    /** The list a lot's crop picker offers, in the catalogue's group order. */
    public static function options(): array
    {
        $rows = [];
        foreach (self::crops() as $key => $c) {
            $rows[] = [
                'value' => $key,
                'label' => $c['label'],
                'icon' => $c['icon'],
                'group' => $c['group'] ?? 'Other',
                'perennial' => ($c['kind'] ?? CropCatalog::ANNUAL) === CropCatalog::PERENNIAL,
                'maturity' => isset($c['maturity']) ? (int) $c['maturity'] : null,
                'bearingAt' => isset($c['bearingAt']) ? (int) $c['bearingAt'] : null,
                'counter' => $c['counter'] ?? 'DAP',
            ];
        }

        return $rows;
    }

    /** The picker's sections, each with the crops in it. */
    public static function grouped(): array
    {
        $by = [];
        foreach (self::options() as $row) {
            $by[$row['group']][] = $row;
        }

        $out = [];
        foreach (CropCatalog::GROUPS as $g) {
            if (! empty($by[$g])) {
                $out[$g] = $by[$g];
            }
        }
        // Anything filed under a group the order forgot still gets shown.
        foreach ($by as $g => $rows) {
            if (! isset($out[$g])) {
                $out[$g] = $rows;
            }
        }

        return $out;
    }

    /**
     * A stored crop key, matched so old values still land.
     *
     * Exact key first, then the whole label, then — only for the seven names
     * this app used to store — a rename map. The old loose substring match is
     * gone: with eighty crops in the list it turned "corn" into whichever of
     * the three corns happened to be first, and matched 'rice' inside
     * 'price'. A value that means nothing is better read as nothing.
     */
    public const RENAMED = [
        'corn' => 'corn_yellow',
        'mais' => 'corn_yellow',
        'palay' => 'rice',
        'saging' => 'banana',
        'mangga' => 'mango',
        'niyog' => 'coconut',
        'tubo' => 'sugarcane',
        'gulay' => 'vegetables',
    ];

    public static function normalize(?string $crop): ?string
    {
        $crop = strtolower(trim((string) $crop));
        if ($crop === '') {
            return null;
        }
        $all = self::crops();
        if (isset($all[$crop])) {
            return $crop;
        }
        if (isset(self::RENAMED[$crop])) {
            return self::RENAMED[$crop];
        }
        foreach ($all as $key => $c) {
            if (strtolower($c['label']) === $crop) {
                return $key;
            }
        }

        return null;
    }

    public static function label(?string $crop): ?string
    {
        $key = self::normalize($crop);

        return $key ? self::crops()[$key]['label'] : null;
    }

    public static function icon(?string $crop): string
    {
        $key = self::normalize($crop);

        return $key ? self::crops()[$key]['icon'] : '🌱';
    }

    /** Does this crop count months of age rather than days from planting? */
    public static function isPerennial(?string $crop): bool
    {
        return CropCatalog::isPerennial(self::normalize($crop));
    }

    /** The crop's own typical days to harvest, before a lot overrides it. */
    public static function maturity(?string $crop): ?int
    {
        return CropCatalog::maturity(self::normalize($crop));
    }

    /**
     * The stage table to read a lot against.
     *
     * A crop can be grown more than one way, and the way decides the
     * calendar: rice counted in DAS was direct seeded and has never been
     * transplanted, so it wants its own timeline rather than the transplanted
     * one shifted by three weeks.
     *
     * @param  int|null  $maturity  the lot's own days to harvest, if it knows
     *                              — patterned crops are laid out against it.
     */
    public static function stagesFor(?string $crop, ?string $counter = null, ?int $maturity = null): array
    {
        $key = self::normalize($crop);
        if (! $key) {
            return [];
        }

        $c = self::crops()[$key];
        $direct = $counter !== null
            && strtoupper($counter) !== 'DAT'
            && isset($c['stagesDirect']);
        if ($direct) {
            return $c['stagesDirect'];
        }
        if (isset($c['stages'])) {
            return $c['stages'];
        }

        return CropCatalog::stages($key, $maturity);
    }

    /**
     * Where this crop is on day $day of its count.
     *
     * For a perennial, $day is the tree's age in MONTHS.
     *
     * @param  string|null  $counter  which count the day is in — 'DAT', 'DAS'
     *                                or 'DAP'. Rice reads differently in each.
     *
     * @return array{index:int,label:string,what:string,needs:string,from:int,
     *     until:?int,dayInStage:int,lengthDays:?int,progress:?float,
     *     next:?array{label:string,inDays:int}}|null
     */
    public static function stageFor(?string $crop, ?int $day, ?string $counter = null, ?int $maturity = null): ?array
    {
        $key = self::normalize($crop);
        if (! $key || $day === null) {
            return null;
        }

        $stages = self::stagesFor($key, $counter, $maturity);
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
            'unit' => CropCatalog::isPerennial($key) ? 'month' : 'day',
            'next' => isset($stages[$at + 1])
                ? ['label' => $stages[$at + 1][1], 'inDays' => $stages[$at + 1][0] - $day]
                : null,
        ];
    }

    /** Every stage of a crop, flagged with which one a day falls in. */
    public static function timeline(?string $crop, ?int $day = null, ?string $counter = null, ?int $maturity = null): array
    {
        $key = self::normalize($crop);
        if (! $key) {
            return [];
        }
        $current = self::stageFor($key, $day, $counter, $maturity);

        return collect(self::stagesFor($key, $counter, $maturity))->map(fn ($s, $i) => [
            'from' => $s[0],
            'label' => $s[1],
            'what' => $s[2],
            'needs' => $s[3],
            'isNow' => $current && $current['index'] === $i,
            'isPast' => $current && $i < $current['index'],
        ])->all();
    }

    /** Which counter this crop is managed by ('DAT', 'DAP', 'DAS' or 'AGE'). */
    public static function counter(?string $crop): string
    {
        $key = self::normalize($crop);

        return $key ? (self::crops()[$key]['counter'] ?? 'DAP') : 'DAP';
    }
}
