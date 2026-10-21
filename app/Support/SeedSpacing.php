<?php

namespace App\Support;

/**
 * Seed Count and Distance Calculator (2026-10-19, the owner): play with the
 * distance between rows and between plants and see the seeds a hectare (or a
 * lot) needs, the plants that stand at harvest and the yield they can carry;
 * or the other way round, the spacing that fits the seeds you have, or the
 * spacing and seeds for the stand you want. No Anee, no credits.
 *
 * The arithmetic, the same in the page (resources/views/seed-spacing/index,
 * between the calc:start and calc:end marks) and here (what a saved
 * calculation keeps):
 *
 *  1. The ground one hill takes: the row distance times the plant distance.
 *     On beds the row distance is the bed and its path shared by the rows on
 *     the bed; in a triangle the rows sit 0.866 of the plant distance apart.
 *     Hills a hectare = 100,000,000 cm² / (row cm x plant cm).
 *  2. What goes in a hill: n seeds (sown where they grow), n seedlings
 *     (raised in a seedbed and transplanted) or n pieces (cuttings, cloves,
 *     setts, suckers, tubers). Seeds for the seedbed = seedlings / (the
 *     germination x the share of seedlings fit to plant). The seeds to buy are
 *     a range: what the hills take, up to that plus the reserve for
 *     replanting; the kilos follow the weight of 1,000 seeds (the crop's spread
 *     of seed sizes when the farmer gives none).
 *  3. The final stand: each of the n comes up with the germination (or, for a
 *     seedling, lives with one less the transplant loss). The plants a hill
 *     keeps after thinning, on average, is the mean of min(X, kept) for X
 *     binomial (n, chance), counted exactly; then the losses after thinning.
 *     A hill comes up empty with (1 - chance)^n.
 *  4. The other way round: the seeds you have (a count, or kilos through the
 *     seed weight) give the hills, through the seedbed for transplanted crops
 *     and less the replant reserve; a target stand gives the hills through
 *     what one hill adds to the stand. The hills give the spacing: the plant
 *     distance at your rows (or the crop's usual rows, with a square spacing
 *     beside it), the plant distance on your beds, or the triangle's side.
 *     Then everything is counted from that spacing, as in 1 to 3.
 *  5. The yield, a range: rice from the hills with plants x the productive
 *     tillers a hill x the grains a panicle x the filled share x the weight of
 *     1,000 grains, no more panicles than a field carries; corn and sorghum
 *     from the plants x the ears (heads) a plant x the grain an ear;
 *     sugarcane from the clumps (the setts that took) x the tillers a clump x
 *     the share that grows into millable cane x the weight of a stalk, no more
 *     millable stalks than a field carries; the rest
 *     from the plants x the harvest a plant. Plants past the top of the good
 *     range add nothing more (they share the same light, water and food). The
 *     top of the range is that potential; the bottom is less the usual loss at
 *     harvest.
 *  6. The flags: the stand against the crop's good range (very thin, thin,
 *     good, crowded, danger zone of crowding), the seeds a hill against the
 *     germination (hills that come up short, more seed than needed, thinning),
 *     rice seedlings a hill past what pays, the spacing against the crop's
 *     usual rows and plant distances, a germination under good seed's, more
 *     tillers than a field holds, a stand past the good range in the yield.
 */
final class SeedSpacing
{
    /** What the farmer works out. */
    public const MODES = [
        'seeds' => ['Seeds from my spacing', 'Set the rows and the distance between plants: see the seeds, the stand and the yield'],
        'spacing' => ['Spacing from my seeds', 'Say how many seeds or kilos you have: see the distance that fits them'],
        'stand' => ['Spacing and seeds for a target stand', 'Say how many plants you want: see the spacing and the seeds for it'],
    ];

    public const LAYOUTS = [
        'rows' => ['In rows', 'Rows a set distance apart, plants a set distance along each row'],
        'beds' => ['On beds', 'Raised beds with a path between them, a few rows on each bed'],
        'triangle' => ['Triangle (zigzag)', 'Every plant the same distance from its neighbors, each row shifted half a step'],
    ];

    public const METHODS = [
        'direct' => ['Sown where it grows', 'The seeds go straight into the field'],
        'transplant' => ['Seedbed, then transplanted', 'Seedlings raised in a seedbed or trays, then planted out'],
        'piece' => ['Planted from pieces', 'Cuttings, stakes, cloves, setts, suckers or tubers'],
    ];

    /** Rows when working back from seeds or a stand: the farmer's own, or not set. */
    public const ROWSET = [
        'set' => ['I set my rows', 'Keep my row distance and find the distance between plants'],
        'free' => ['Not set', 'A square spacing, and the crop\'s usual rows with the plant distance they need'],
    ];

    public const HILLMODE = [
        'auto' => ['Suggested for my germination', 'The fewest that leave under 1 hill in 10 short'],
        'set' => ['I set it', 'Type your own number'],
    ];

    /**
     * The kind of seed (2026-10-19, the owner: germination and losses are no
     * longer typed; they come from the variety, else the seed's kind, else the
     * crop). SEED_KINDS lists the kinds a crop is sold as, each [usual
     * germination, the least such seed shows], and its usual kind (default).
     */
    public const SEED_TYPES = [
        'hybrid' => ['Hybrid seed', 'A new bag each season', 'a hybrid bag'],
        'opv' => ['Open pollinated or inbred', 'Bought, not saved', 'open pollinated or inbred seed'],
        'good' => ['Certified or good seed', 'From a seed store, this season', 'good seed'],
        'saved' => ['Seed I saved myself', 'From my own last harvest', 'seed saved from your own harvest'],
    ];

    public const AMOUNT_UNITS = ['count' => 'Count', 'kg' => 'Kilos', 'g' => 'Grams'];

    public const PER = ['ha' => ['A hectare', 'The amount is for one hectare'], 'lot' => ['My lot', 'The amount is for the lot size above']];

    public const AREA_UNITS = ['ha' => ['Hectares', 'ha'], 'sqm' => ['Square meters', 'm²']];

    public const GROUPS = [
        'grain' => 'Rice, corn and sorghum',
        'legume' => 'Beans and peanuts',
        'fruitveg' => 'Fruit vegetables',
        'vine' => 'Vines and gourds',
        'leafy' => 'Leafy, bulb and root vegetables',
        'piece' => 'Planted from pieces',
        'tree' => 'Fruit trees and long crops',
    ];

    /** The stand bands: under lo x THIN is very thin, over hi x CROWD the danger zone. */
    public const THIN = 0.7;

    public const CROWD = 1.3;

    /** The share of empty hills a seeds a hill count may leave. */
    public const SHORT_OK = 0.10;

    /**
     * A crop the guides sow one seed a hill and replant the gaps (oneSeed:
     * hybrid corn, white and sweet corn, ampalaya from sprouted seed): up to
     * this share of empty hills before a second seed is suggested; the replant
     * reserve is sized to the empty share, and the replanted hills count.
     */
    public const SHORT_ONE = 0.15;

    /** For a crop kept 2 or more a hill: the share of hills that may come up with fewer. */
    public const SHORT_PART = 0.20;

    /** An empty share past this is a red flag. */
    public const SHORT_BAD = 0.25;

    /** The rows of a triangle sit this share of the plant distance apart. */
    public const TRI = 0.8660254037844386;

    /** A bed and its path, cm, and the rows on it, when a crop names none. */
    public const BED = [130, 4];

    /** Rice seedlings a hill past this do not pay; at BAD or more, a red flag. */
    public const RICE_HILL_MAX = 3;

    public const RICE_HILL_BAD = 5;

    /*
     * Sugarcane's stalk rule (2026-10-20, the owner: "yes, fix all"): the cane a
     * hectare is the clumps (the setts that took) x the tillers a clump x the
     * share of them that grows into millable cane x the weight of one stalk,
     * never more millable stalks than a field carries. A variety's own tillers a
     * hill (the registration list) and stalk weight (our books) come from the
     * variety shelf (VarietyYield extra stalksClump and stalkKg). Researched
     * 2026-10-20; the notes are in our own words and no source is named.
     */

    /** Tillers a clump at the peak: the registration list's tillers a hill for the Phil lines (2.2 to 5, middle 3.4); a 120 cm row trial counted 3.8 to 4.5 a sett. */
    public const CANE_TILLERS = 4;

    /** The share of those tillers that become millable cane, %: plant crops in 120 cm rows kept 52% and 66% of their peak (a ratoon 50%), a double row trial 49%, two seasons elsewhere 53% and 67%. */
    public const CANE_MILLABLE = [50, 65];

    /** One millable stalk, kg: the middle of the stalk weights of the 15 registered varieties in our books (1.23 to 2.48 kg, middle 1.48). */
    public const CANE_STALK_KG = 1.5;

    /** The most millable stalks a hectare a field carries, about 12 a meter of 1.2 m rows: a trial of Phil lines counted about 48,000 to 70,000, a double row trial about 80,000, thin cane in 60 to 120 cm rows 118,000 to 141,000. */
    public const CANE_STALKS_MAX = 100000;

    /**
     * The crops. row, plant and perHill are [min, usual, max] (cm, and seeds,
     * seedlings or pieces a hill; a method's own perHill under its key);
     * keep = plants kept a hill after thinning (null: all that come up);
     * germ = [usual, the least good seed shows] (for pieces, the share that
     * takes root); fit = the share of seedbed seedlings fit to plant; loss =
     * the share lost after coming up or after transplanting; reserve = the
     * extra kept for replanting; tgw = the weight of 1,000 seeds, g [low,
     * high]; pieceWt = the weight of one piece, g [low, high]; stand = the
     * good final stand a hectare [low, high], established plants (or hills for
     * transplanted rice) in standBy; yield = the model and its defaults (loss =
     * the usual share lost at harvest); book = the variety book's crop key
     * where it is not this one (App\Support\VarietyBook, the variety pick);
     * packs = the bags, cans and packets the seed is usually sold in here,
     * each kind kg, g or seeds (sold by count) and size, the usual one
     * first, 'seed' = the kinds of seed it is the usual pack for (a variety's
     * own packs, from the shelf, come before them). Packs researched
     * 2026-10-19 from Philippine seed sellers' bags, cans and listings and
     * seed program bags (scratchpad packs/: DEKALB, NK and Pioneer bags of
     * 35,000 kernels, about 9 to 10 kg; 9 kg and 18 kg corn bags; 20 kg corn
     * and certified rice seed bags; hybrid rice in 3 kg and 15 kg bags;
     * vegetable cans and packs from East-West, Allied Botanical, Ramgo,
     * Known-You and Seminis listings).
     * Researched 2026-10-19 from extension and seed company guides, trials
     * and seed standards (notes in our own words, no agency named); the
     * stands agree with App\Support\NpkModel::SEEDING, counted after the
     * usual germination and losses.
     */
    public const CROPS = [
        // ---- Rice, corn and sorghum ----
        'rice' => ['name' => 'Rice, transplanted', 'local' => 'Palay, lipat tanim', 'group' => 'grain', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [15, 20, 30], 'plant' => [10, 20, 30], 'perHill' => [1, 2, 3], 'keep' => null,
            'germ' => [85, 85], 'fit' => 70, 'loss' => 5, 'reserve' => 10, 'tgw' => [20, 28],
            'stand' => [195000, 333333], 'standBy' => 'hills',
            'yield' => ['model' => 'tiller', 'tillers' => 12, 'grains' => 100, 'filled' => 80, 'ceiling' => 600, 'loss' => 5, 'what' => 'palay', 'sack' => 50],
            'testDays' => '5 to 10',
            'packs' => [['kind' => 'kg', 'size' => 20, 'label' => 'bag', 'seed' => ['good', 'opv', 'saved'], 'what' => 'certified seed'], ['kind' => 'kg', 'size' => 40, 'label' => 'sack', 'what' => 'certified seed'], ['kind' => 'kg', 'size' => 3, 'label' => 'bag', 'seed' => ['hybrid'], 'what' => 'hybrid seed'], ['kind' => 'kg', 'size' => 15, 'label' => 'bag', 'what' => 'hybrid seed']],
            'note' => '20 by 20 cm is the usual, 20 by 15 cm in the dry season. 25 by 25 cm suits tall, leafy varieties in the wet season but leaves fewer hills than a full field wants. Hybrids: 1 to 2 seedlings a hill; inbreds: 2 to 3.'],
        'rice_dsr' => ['name' => 'Rice, direct seeded in rows', 'local' => 'Palay, sabog tanim sa hanay', 'group' => 'grain', 'methods' => ['direct'], 'layout' => 'rows', 'book' => 'rice_dsr_wet',
            'row' => [15, 20, 30], 'plant' => [5, 10, 20], 'perHill' => [2, 4, 8], 'keep' => null,
            'germ' => [85, 85], 'loss' => 15, 'reserve' => 10, 'tgw' => [20, 28],
            'stand' => [1200000, 2500000], 'standBy' => 'plants',
            'yield' => ['model' => 'tiller', 'tillers' => 8, 'grains' => 90, 'filled' => 80, 'ceiling' => 600, 'loss' => 5, 'what' => 'palay', 'sack' => 50],
            'testDays' => '5 to 10',
            'packs' => [['kind' => 'kg', 'size' => 20, 'label' => 'bag', 'seed' => ['good', 'opv', 'saved'], 'what' => 'certified seed'], ['kind' => 'kg', 'size' => 40, 'label' => 'sack', 'what' => 'certified seed'], ['kind' => 'kg', 'size' => 3, 'label' => 'bag', 'seed' => ['hybrid'], 'what' => 'hybrid seed'], ['kind' => 'kg', 'size' => 15, 'label' => 'bag', 'what' => 'hybrid seed']],
            'note' => 'A drum seeder or a seed drill on rows 20 to 25 cm apart takes about 40 to 60 kg a hectare. Broadcast seed has no rows to count: it takes about 60 to 80 kg.'],
        'corn_yellow' => ['name' => 'Yellow corn, hybrid', 'local' => 'Mais, dilaw', 'group' => 'grain', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [60, 70, 90], 'plant' => [15, 20, 25], 'perHill' => [1, 1, 2], 'keep' => 1, 'oneSeed' => true,
            'germ' => [90, 85], 'loss' => 5, 'reserve' => 5, 'tgw' => [250, 350],
            'stand' => [60000, 91000], 'standBy' => 'plants',
            'yield' => ['model' => 'ear', 'ears' => 0.95, 'perEar' => 120, 'fresh' => false, 'loss' => 5, 'what' => 'dry grain', 'sack' => 50, 'word' => 'ears'],
            'testDays' => '4 to 7',
            'packs' => [['kind' => 'seeds', 'size' => 35000, 'label' => 'bag', 'seed' => ['hybrid'], 'word' => 'kernels'], ['kind' => 'kg', 'size' => 9, 'label' => 'bag'], ['kind' => 'kg', 'size' => 18, 'label' => 'bag'], ['kind' => 'kg', 'size' => 20, 'label' => 'bag', 'seed' => ['opv', 'saved']]],
            'note' => 'One seed a hill in 70 cm rows with 18 to 20 cm between hills is the usual with hybrid seed: about 22 to 24 kg a hectare for 75,000 plants or more. Fill any gaps within a week.'],
        'corn_white' => ['name' => 'White or glutinous corn', 'local' => 'Mais, puti o lagkitan', 'group' => 'grain', 'methods' => ['direct'], 'layout' => 'rows', 'book' => 'corn_glutinous',
            'row' => [60, 75, 90], 'plant' => [20, 25, 30], 'perHill' => [1, 1, 2], 'keep' => 1, 'oneSeed' => true,
            'germ' => [85, 85], 'loss' => 5, 'reserve' => 10, 'tgw' => [180, 320],
            'stand' => [40000, 60000], 'standBy' => 'plants',
            'yield' => ['model' => 'ear', 'ears' => 0.95, 'perEar' => 90, 'fresh' => false, 'loss' => 5, 'what' => 'dry grain', 'sack' => 50, 'word' => 'ears'],
            'testDays' => '4 to 7',
            'packs' => [['kind' => 'kg', 'size' => 20, 'label' => 'bag', 'seed' => ['opv', 'saved']], ['kind' => 'kg', 'size' => 1, 'label' => 'bag', 'seed' => ['hybrid']]],
            'note' => 'About 20 kg of seed at 75 by 25 cm; 75 by 30 cm for bigger green ears to sell fresh. A fresh glutinous ear in its husk weighs about 150 to 250 g.'],
        'corn_sweet' => ['name' => 'Sweet corn', 'local' => 'Mais, matamis', 'group' => 'grain', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [60, 75, 90], 'plant' => [20, 25, 30], 'perHill' => [1, 1, 2], 'keep' => 1, 'oneSeed' => true,
            'germ' => [85, 85], 'loss' => 5, 'reserve' => 10, 'tgw' => [100, 200],
            'stand' => [46000, 66667], 'standBy' => 'plants',
            'yield' => ['model' => 'ear', 'ears' => 0.9, 'perEar' => 300, 'fresh' => true, 'loss' => 5, 'what' => 'fresh ears', 'word' => 'ears'],
            'testDays' => '4 to 7',
            'packs' => [['kind' => 'kg', 'size' => 1, 'label' => 'bag'], ['kind' => 'kg', 'size' => 0.5, 'label' => 'bag']],
            'note' => 'Sweet corn seed is shriveled and comes up weaker than field corn: sow into moist, warm soil, not too deep. Ears are ready about 70 to 80 days after sowing.'],
        'sorghum' => ['name' => 'Sorghum', 'local' => 'Batad', 'group' => 'grain', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [45, 60, 75], 'plant' => [10, 15, 20], 'perHill' => [1, 2, 3], 'keep' => 1,
            'germ' => [85, 80], 'loss' => 10, 'reserve' => 10, 'tgw' => [25, 35],
            'stand' => [90000, 180000], 'standBy' => 'plants',
            'yield' => ['model' => 'ear', 'ears' => 1, 'perEar' => 30, 'fresh' => false, 'loss' => 5, 'what' => 'dry grain', 'sack' => 50, 'word' => 'heads'],
            'testDays' => '4 to 10',
            'note' => 'About 8 to 12 kg of seed a hectare in rows; thin to one plant every 15 cm or so.'],

        // ---- Beans and peanuts ----
        'mungbean' => ['name' => 'Mungbean', 'local' => 'Monggo', 'group' => 'legume', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [40, 50, 60], 'plant' => [5, 10, 20], 'perHill' => [1, 2, 3], 'keep' => null,
            'germ' => [85, 80], 'loss' => 10, 'reserve' => 5, 'tgw' => [45, 70],
            'stand' => [200000, 333333], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 4, 'unit' => 'g', 'loss' => 5, 'what' => 'dry beans'],
            'testDays' => '5 to 7',
            'packs' => [['kind' => 'kg', 'size' => 25, 'label' => 'bag']],
            'note' => 'Drilled at 15 to 18 seeds a meter in rows 50 to 60 cm apart, about 18 to 20 kg a hectare, and not thinned.'],
        'peanut' => ['name' => 'Peanut', 'local' => 'Mani', 'group' => 'legume', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [40, 50, 60], 'plant' => [10, 15, 25], 'perHill' => [1, 2, 2], 'keep' => null,
            'germ' => [80, 80], 'loss' => 10, 'reserve' => 5, 'tgw' => [350, 650],
            'stand' => [150000, 250000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 8, 'unit' => 'g', 'loss' => 5, 'what' => 'dry pods'],
            'testDays' => '5 to 10',
            'note' => 'The seeds are shelled kernels: shell them a few days before sowing and drop the broken or shriveled ones. Kept dry, seed holds about 80% germination for a year.'],
        'soybean' => ['name' => 'Soybean', 'local' => 'Utaw', 'group' => 'legume', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [40, 50, 60], 'plant' => [5, 15, 20], 'perHill' => [1, 2, 3], 'keep' => null,
            'germ' => [85, 80], 'loss' => 10, 'reserve' => 5, 'tgw' => [100, 170],
            'stand' => [150000, 250000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 8, 'unit' => 'g', 'loss' => 5, 'what' => 'dry beans'],
            'testDays' => '5 to 8',
            'note' => 'Two seeds a hill, or 15 to 20 seeds a meter of row. Soybean seed loses its germination fast in warm storage: test it before you sow.'],
        'stringbean' => ['name' => 'Pole sitaw', 'local' => 'Sitaw', 'group' => 'legume', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [75, 100, 120], 'plant' => [30, 50, 60], 'perHill' => [2, 3, 4], 'keep' => 2,
            'germ' => [85, 75], 'loss' => 10, 'reserve' => 10, 'tgw' => [120, 250],
            'stand' => [34000, 53333], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.25, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh pods'],
            'testDays' => '5 to 8',
            'packs' => [['kind' => 'kg', 'size' => 1, 'label' => 'bag'], ['kind' => 'g', 'size' => 500, 'label' => 'can'], ['kind' => 'g', 'size' => 100, 'label' => 'can']],
            'note' => 'Sown 2 to 3 seeds a hill beside the trellis and thinned to 2 strong plants. Picking starts 45 to 60 days after planting and goes on every few days.'],
        'bushsitaw' => ['name' => 'Bush sitaw', 'local' => 'Sitaw, palumpong', 'group' => 'legume', 'methods' => ['direct'], 'layout' => 'rows', 'book' => 'stringbean',
            'row' => [60, 75, 90], 'plant' => [20, 25, 30], 'perHill' => [2, 2, 3], 'keep' => 1,
            'germ' => [85, 75], 'loss' => 10, 'reserve' => 10, 'tgw' => [100, 200],
            'stand' => [45000, 80000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.15, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh pods'],
            'testDays' => '5 to 8',
            'packs' => [['kind' => 'kg', 'size' => 1, 'label' => 'bag'], ['kind' => 'g', 'size' => 500, 'label' => 'can'], ['kind' => 'g', 'size' => 100, 'label' => 'can']],
            'note' => 'No trellis: rows 60 cm apart in the dry season and 75 cm in the wet, one plant a hill. Picking starts about 40 to 45 days after it comes up.'],

        // ---- Fruit vegetables ----
        'okra' => ['name' => 'Okra', 'local' => 'Okra', 'group' => 'fruitveg', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [60, 75, 100], 'plant' => [20, 30, 50], 'perHill' => [2, 3, 3], 'keep' => 2,
            'germ' => [75, 65], 'loss' => 5, 'reserve' => 10, 'tgw' => [50, 65],
            'stand' => [40000, 90000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.15, 'unit' => 'kg', 'loss' => 5, 'what' => 'fresh pods'],
            'testDays' => '7 to 14',
            'packs' => [['kind' => 'g', 'size' => 500, 'label' => 'can']],
            'note' => 'Rows 75 cm apart in the wet season, up to 1 m in the dry, hills 30 cm, thinned to 2 plants about two weeks after sowing. The seed coat is hard: soak it overnight before sowing.'],
        'tomato' => ['name' => 'Tomato', 'local' => 'Kamatis', 'group' => 'fruitveg', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [75, 100, 150], 'plant' => [30, 50, 60], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => [85, 70], 'fit' => 80, 'loss' => 5, 'reserve' => 10, 'tgw' => [2.5, 4],
            'stand' => [12500, 26667], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 1.25, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'packs' => [['kind' => 'g', 'size' => 25, 'label' => 'can']],
            'note' => 'One seed a tray cell (about 100 to 150 g of seed a hectare) or a seedbed (200 to 300 g), planted out at 20 to 30 days. Rows of 75 cm to 1 m for bush types, wider for tall, staked types.'],
        'eggplant' => ['name' => 'Eggplant', 'local' => 'Talong', 'group' => 'fruitveg', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [75, 100, 120], 'plant' => [40, 50, 100], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => [80, 70], 'fit' => 80, 'loss' => 5, 'reserve' => 10, 'tgw' => [3, 4.5],
            'stand' => [9500, 26667], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 1.5, 'unit' => 'kg', 'loss' => 8, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'packs' => [['kind' => 'g', 'size' => 50, 'label' => 'can'], ['kind' => 'g', 'size' => 25, 'label' => 'can']],
            'note' => 'Planted out at 30 to 35 days, with 3 to 4 true leaves. Wider hills in the wet season keep the leaves drier and the fruit borer easier to find.'],
        'chili' => ['name' => 'Hot pepper', 'local' => 'Sili, haba o labuyo', 'group' => 'fruitveg', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [60, 75, 100], 'plant' => [30, 40, 60], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => [75, 60], 'fit' => 80, 'loss' => 5, 'reserve' => 10, 'tgw' => [3.5, 6],
            'stand' => [15800, 55556], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.4, 'unit' => 'kg', 'loss' => 8, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'packs' => [['kind' => 'g', 'size' => 25, 'label' => 'can']],
            'note' => 'Pepper seed comes up slowly: keep the seedbed moist and shaded, and plant out at 30 to 40 days. Labuyo bears far less a plant than siling haba: lower the harvest a plant.'],
        'bellpepper' => ['name' => 'Bell pepper', 'local' => 'Sili, malaki', 'group' => 'fruitveg', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [60, 75, 100], 'plant' => [30, 40, 50], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => [80, 60], 'fit' => 80, 'loss' => 5, 'reserve' => 10, 'tgw' => [5.5, 8],
            'stand' => [19000, 44444], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.5, 'unit' => 'kg', 'loss' => 8, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'packs' => [['kind' => 'g', 'size' => 50, 'label' => 'can']],
            'note' => 'Raise the seedlings in trays under a rain shelter. Close enough that the leaves shade the fruit from sunscald, wide enough for air to move.'],

        // ---- Vines and gourds ----
        'ampalaya' => ['name' => 'Ampalaya', 'local' => 'Ampalaya', 'group' => 'vine', 'methods' => ['direct', 'transplant'], 'layout' => 'rows',
            'row' => [150, 200, 300], 'plant' => [30, 50, 100], 'perHill' => [1, 2, 3], 'keep' => 1, 'oneSeed' => true, 'transplant' => ['perHill' => [1, 1, 1]],
            'germ' => [80, 60], 'fit' => 90, 'loss' => 5, 'reserve' => 10, 'tgw' => [130, 220],
            'stand' => [6000, 22222], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 2, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'packs' => [['kind' => 'g', 'size' => 100, 'label' => 'pack'], ['kind' => 'g', 'size' => 50, 'label' => 'can']],
            'note' => 'Nick the hard seed coat, soak it half a day, and keep it in a damp cloth until the root shows: one sprouted seed a hill is enough. Rows 2 m apart in the dry season, 3 m in the wet.'],
        'squash' => ['name' => 'Squash', 'local' => 'Kalabasa', 'group' => 'vine', 'methods' => ['direct', 'transplant'], 'layout' => 'rows',
            'row' => [200, 250, 400], 'plant' => [75, 100, 200], 'perHill' => [1, 2, 3], 'keep' => 1, 'transplant' => ['perHill' => [1, 1, 1]],
            'germ' => [90, 60], 'fit' => 90, 'loss' => 5, 'reserve' => 10, 'tgw' => [90, 150],
            'stand' => [2300, 6667], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 6, 'unit' => 'kg', 'loss' => 5, 'what' => 'fresh fruit'],
            'testDays' => '4 to 7',
            'packs' => [['kind' => 'g', 'size' => 100, 'label' => 'pack'], ['kind' => 'g', 'size' => 250, 'label' => 'can'], ['kind' => 'g', 'size' => 50, 'label' => 'can']],
            'note' => 'Soak the seed overnight and wrap it in a damp cloth until the root shows. The vines run 3 m or more; use ridges or raised beds in the wet season.'],
        'cucumber' => ['name' => 'Cucumber', 'local' => 'Pipino', 'group' => 'vine', 'methods' => ['direct', 'transplant'], 'layout' => 'rows',
            'row' => [75, 100, 150], 'plant' => [20, 30, 50], 'perHill' => [1, 2, 3], 'keep' => 1, 'transplant' => ['perHill' => [1, 1, 1]],
            'germ' => [85, 60], 'fit' => 90, 'loss' => 5, 'reserve' => 10, 'tgw' => [22, 32],
            'stand' => [20000, 44444], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.6, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit'],
            'testDays' => '3 to 7',
            'packs' => [['kind' => 'g', 'size' => 50, 'label' => 'can'], ['kind' => 'g', 'size' => 10, 'label' => 'pack']],
            'note' => 'Two to three seeds a hill 30 cm apart, thinned to one. No soaking needed; on a trellis the hills can sit closer than on the ground.'],
        'watermelon' => ['name' => 'Watermelon', 'local' => 'Pakwan', 'group' => 'vine', 'methods' => ['direct', 'transplant'], 'layout' => 'rows',
            'row' => [150, 200, 300], 'plant' => [60, 150, 250], 'perHill' => [1, 2, 4], 'keep' => 1, 'transplant' => ['perHill' => [1, 1, 1]],
            'germ' => [85, 60], 'fit' => 90, 'loss' => 5, 'reserve' => 10, 'tgw' => [35, 100],
            'stand' => [1500, 6667], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 6, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit'],
            'testDays' => '5 to 14',
            'packs' => [['kind' => 'seeds', 'size' => 2500, 'label' => 'pack']],
            'note' => 'Hybrid seed is costly: many growers sow one seed a tray cell and transplant (about 0.5 to 0.8 kg a hectare). Test the seed somewhere warm.'],
        'upo' => ['name' => 'Upo', 'local' => 'Upo', 'group' => 'vine', 'methods' => ['direct', 'transplant'], 'layout' => 'rows',
            'row' => [200, 300, 400], 'plant' => [75, 100, 150], 'perHill' => [1, 2, 2], 'keep' => 1, 'transplant' => ['perHill' => [1, 1, 1]],
            'germ' => [80, 60], 'fit' => 90, 'loss' => 5, 'reserve' => 10, 'tgw' => [120, 220],
            'stand' => [1500, 6667], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 6, 'unit' => 'kg', 'loss' => 5, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'note' => 'Grown on an overhead trellis so the long fruit hangs straight. Soak the seed overnight; mounds in the wet season, furrows in the dry.'],
        'patola' => ['name' => 'Patola', 'local' => 'Patola', 'group' => 'vine', 'methods' => ['direct', 'transplant'], 'layout' => 'rows',
            'row' => [150, 250, 300], 'plant' => [50, 100, 250], 'perHill' => [1, 2, 2], 'keep' => 1, 'transplant' => ['perHill' => [1, 1, 1]],
            'germ' => [80, 60], 'fit' => 90, 'loss' => 5, 'reserve' => 10, 'tgw' => [90, 150],
            'stand' => [1500, 6667], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 4, 'unit' => 'kg', 'loss' => 5, 'what' => 'fresh fruit'],
            'testDays' => '7 to 14',
            'note' => 'A trellis or a fence. Soak the seed overnight and keep it damp in the dark until the coat splits.'],

        // ---- Leafy, bulb and root vegetables ----
        'pechay' => ['name' => 'Pechay', 'local' => 'Pechay', 'group' => 'leafy', 'methods' => ['transplant', 'direct'], 'layout' => 'beds', 'bed' => [130, 5],
            'row' => [20, 20, 25], 'plant' => [10, 10, 20], 'perHill' => [1, 1, 2], 'keep' => 1, 'direct' => ['perHill' => [2, 3, 5]],
            'germ' => [85, 75], 'fit' => 80, 'loss' => 10, 'reserve' => 10, 'tgw' => [1.6, 3],
            'stand' => [190000, 385000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.05, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh leaves'],
            'testDays' => '5 to 7',
            'packs' => [['kind' => 'g', 'size' => 500, 'label' => 'can'], ['kind' => 'g', 'size' => 100, 'label' => 'can'], ['kind' => 'kg', 'size' => 1, 'label' => 'bag']],
            'note' => 'Beds 1 m wide with 30 cm paths, rows 20 cm apart, plants 10 cm apart. Transplanting takes about 1 to 2.5 kg of seed a hectare; sowing in rows and thinning, about 3 kg.'],
        'cabbage' => ['name' => 'Cabbage', 'local' => 'Repolyo', 'group' => 'leafy', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [40, 50, 75], 'plant' => [30, 40, 50], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => [85, 75], 'fit' => 85, 'loss' => 5, 'reserve' => 10, 'tgw' => [3, 5],
            'stand' => [35000, 55000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.7, 'unit' => 'kg', 'loss' => 15, 'what' => 'heads'],
            'testDays' => '7 to 10',
            'packs' => [['kind' => 'g', 'size' => 25, 'label' => 'pack'], ['kind' => 'g', 'size' => 50, 'label' => 'pack'], ['kind' => 'g', 'size' => 10, 'label' => 'pack']],
            'note' => 'Single rows 50 cm apart, or two rows on a bed 1 m wide. Wider spacing gives bigger heads; closer gives more, smaller heads.'],
        'lettuce' => ['name' => 'Lettuce', 'local' => 'Letsugas', 'group' => 'leafy', 'methods' => ['transplant'], 'layout' => 'beds', 'bed' => [175, 3],
            'row' => [30, 30, 40], 'plant' => [20, 30, 40], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => [85, 80], 'fit' => 80, 'loss' => 8, 'reserve' => 10, 'tgw' => [0.8, 1.2],
            'stand' => [28600, 57100], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.2, 'unit' => 'kg', 'loss' => 15, 'what' => 'fresh heads'],
            'testDays' => '5 to 7',
            'packs' => [['kind' => 'g', 'size' => 100, 'label' => 'can'], ['kind' => 'g', 'size' => 10, 'label' => 'pack']],
            'note' => 'Beds 1 m wide with paths of about 75 cm: 3 rows for leaf types, 2 for heading types, plants 30 to 40 cm apart.'],
        'onion' => ['name' => 'Bulb onion', 'local' => 'Sibuyas', 'group' => 'leafy', 'methods' => ['transplant', 'direct'], 'layout' => 'beds', 'bed' => [130, 7],
            'row' => [10, 15, 20], 'plant' => [7.5, 10, 15], 'perHill' => [1, 1, 1], 'keep' => 1, 'direct' => ['perHill' => [2, 3, 4]],
            'germ' => [75, 70], 'fit' => 70, 'loss' => 10, 'reserve' => 10, 'tgw' => [3, 4.5],
            'stand' => [300000, 800000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.04, 'unit' => 'kg', 'loss' => 10, 'what' => 'dry bulbs'],
            'testDays' => '7 to 12',
            'packs' => [['kind' => 'g', 'size' => 400, 'label' => 'can'], ['kind' => 'seeds', 'size' => 25000, 'label' => 'pack']],
            'note' => 'Most growers transplant seedlings 5 to 7 weeks old, 6 to 8 rows on a bed. Onion seed loses its germination within a year: buy fresh seed every season.'],
        'carrot' => ['name' => 'Carrot', 'local' => 'Karot', 'group' => 'leafy', 'methods' => ['direct'], 'layout' => 'beds', 'bed' => [105, 7],
            'row' => [10, 10, 20], 'plant' => [5, 10, 12], 'perHill' => [2, 3, 5], 'keep' => 1,
            'germ' => [70, 55], 'loss' => 10, 'reserve' => 10, 'tgw' => [0.8, 1.5],
            'stand' => [380000, 700000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.05, 'unit' => 'kg', 'loss' => 15, 'what' => 'roots'],
            'testDays' => '10 to 14',
            'packs' => [['kind' => 'g', 'size' => 400, 'label' => 'can'], ['kind' => 'g', 'size' => 300, 'label' => 'can'], ['kind' => 'g', 'size' => 100, 'label' => 'can']],
            'note' => 'Beds 70 to 80 cm wide with 30 cm paths, lines 10 cm apart, thinned to 10 cm about a month after sowing. Forked and cracked roots do not sell.'],
        'radish' => ['name' => 'Radish', 'local' => 'Labanos', 'group' => 'leafy', 'methods' => ['direct'], 'layout' => 'rows',
            'row' => [25, 30, 50], 'plant' => [10, 15, 20], 'perHill' => [1, 2, 3], 'keep' => 1,
            'germ' => [85, 75], 'loss' => 10, 'reserve' => 10, 'tgw' => [8, 16],
            'stand' => [100000, 260000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.1, 'unit' => 'kg', 'loss' => 10, 'what' => 'roots'],
            'testDays' => '4 to 10',
            'note' => 'Long white labanos wants room: thin early, or the roots stay thin.'],

        // ---- Planted from pieces ----
        'garlic' => ['name' => 'Garlic', 'local' => 'Bawang', 'group' => 'piece', 'methods' => ['piece'], 'layout' => 'beds', 'bed' => [130, 6], 'piece' => ['clove', 'cloves'],
            'row' => [15, 20, 25], 'plant' => [10, 15, 20], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 80], 'loss' => 10, 'reserve' => 5, 'pieceWt' => [1.5, 3],
            'stand' => [200000, 450000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.015, 'unit' => 'kg', 'loss' => 10, 'what' => 'dry bulbs'],
            'note' => 'Plant the big outer cloves, two thirds buried; the small inner ones make small bulbs. About 400 to 700 kg of cloves a hectare.'],
        'sweetpotato' => ['name' => 'Sweet potato', 'local' => 'Kamote', 'group' => 'piece', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['cutting', 'cuttings'],
            'row' => [75, 75, 100], 'plant' => [25, 30, 40], 'perHill' => [1, 1, 2], 'keep' => null,
            'germ' => [90, 85], 'loss' => 10, 'reserve' => 5, 'pieceWt' => null,
            'stand' => [30000, 53333], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.25, 'unit' => 'kg', 'loss' => 15, 'what' => 'fresh roots'],
            'note' => 'Tip cuttings 25 to 35 cm long with 6 to 8 nodes, on ridges. Replant when more than 15% of the hills are missing.'],
        'cassava' => ['name' => 'Cassava', 'local' => 'Kamoteng kahoy', 'group' => 'piece', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['stake', 'stakes'],
            'row' => [75, 100, 100], 'plant' => [75, 100, 100], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 80], 'loss' => 10, 'reserve' => 5, 'pieceWt' => null,
            'stand' => [8000, 17800], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 2, 'unit' => 'kg', 'loss' => 5, 'what' => 'fresh roots'],
            'note' => 'Stakes 20 to 25 cm long with 5 nodes or more, from plants 8 to 12 months old, set upright or slanted.'],
        'taro' => ['name' => 'Taro', 'local' => 'Gabi', 'group' => 'piece', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['sett', 'setts'],
            'row' => [50, 75, 75], 'plant' => [50, 50, 60], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 80], 'loss' => 10, 'reserve' => 5, 'pieceWt' => [100, 120],
            'stand' => [21000, 40000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.3, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh corms'],
            'note' => 'Setts of 100 to 120 g, the top of the corm with a short stalk, set 4 to 5 cm deep in the wet months and deeper in the dry.'],
        'potato' => ['name' => 'Potato', 'local' => 'Patatas', 'group' => 'piece', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['seed tuber', 'seed tubers'],
            'row' => [60, 75, 90], 'plant' => [20, 30, 35], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 85], 'loss' => 5, 'reserve' => 5, 'pieceWt' => [30, 50],
            'stand' => [36000, 55000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 0.45, 'unit' => 'kg', 'loss' => 10, 'what' => 'tubers'],
            'note' => 'Whole seed tubers of 30 to 40 g, well sprouted: about 1,200 to 2,000 kg a hectare.'],

        // ---- Fruit trees and long crops ----
        'papaya' => ['name' => 'Papaya', 'local' => 'Papaya', 'group' => 'tree', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [200, 250, 300], 'plant' => [150, 200, 250], 'perHill' => [1, 3, 3], 'keep' => 1,
            'germ' => [75, 60], 'fit' => 85, 'loss' => 10, 'reserve' => 10, 'tgw' => [13, 20],
            'stand' => [1333, 3333], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 30, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit', 'perYear' => true],
            'testDays' => '10 to 14',
            'packs' => [['kind' => 'g', 'size' => 10, 'label' => 'pack'], ['kind' => 'g', 'size' => 5, 'label' => 'pack']],
            'thinNote' => 'When they flower, keep the one with perfect (bisexual) flowers, the one that bears the best fruit, and cut the others.',
            'note' => 'Many growers set 3 seedlings a hill and keep the one that flowers right for fruiting.'],
        'banana' => ['name' => 'Banana, Lakatan or Latundan', 'local' => 'Saging, lakatan o latundan', 'group' => 'tree', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['plantlet', 'plantlets'],
            'row' => [200, 250, 300], 'plant' => [180, 200, 300], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 80], 'loss' => 5, 'reserve' => 5, 'pieceWt' => null,
            'stand' => [1111, 2700], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 16, 'unit' => 'kg', 'loss' => 10, 'what' => 'fruit', 'perYear' => true],
            'note' => 'Tissue culture plantlets or sword suckers at 2 by 2.5 m (2,000 mats) or 3 by 3 m (1,111). Cavendish has an entry of its own.'],
        'banana_cavendish' => ['name' => 'Banana, Cavendish', 'local' => 'Saging, Cavendish', 'group' => 'tree', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['plantlet', 'plantlets'], 'book' => 'banana',
            'row' => [200, 230, 300], 'plant' => [180, 200, 250], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [95, 85], 'loss' => 10, 'reserve' => 5, 'pieceWt' => null,
            'stand' => [1700, 2500], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 18, 'unit' => 'kg', 'loss' => 8, 'what' => 'fruit', 'perYear' => true],
            'note' => 'Tissue culture plantlets, often in double rows, about 2,000 to 2,300 a hectare. A bunch weighs about 20 kg without its stalk, and a mat bears about once a year.'],
        'banana_saba' => ['name' => 'Banana, Saba or Cardaba', 'local' => 'Saging na saba', 'group' => 'tree', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['plantlet', 'plantlets'], 'book' => 'banana',
            'row' => [300, 400, 700], 'plant' => [300, 400, 500], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 80], 'loss' => 10, 'reserve' => 5, 'pieceWt' => null,
            'stand' => [286, 833], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 25, 'unit' => 'kg', 'loss' => 10, 'what' => 'fruit', 'perYear' => true],
            'note' => 'Saba and Cardaba at 4 by 4 m, or as wide as 5 by 7 m; a bunch takes more than a year from planting.'],
        'pineapple' => ['name' => 'Pineapple', 'local' => 'Pinya', 'group' => 'tree', 'methods' => ['piece'], 'layout' => 'beds', 'bed' => [150, 2], 'piece' => ['sucker', 'suckers'],
            'row' => [40, 50, 60], 'plant' => [20, 25, 40], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [95, 85], 'loss' => 3, 'reserve' => 5, 'pieceWt' => null,
            'stand' => [40000, 60000], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 1, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit'],
            'note' => 'Double rows 50 cm apart on each bed, 25 to 30 cm in the row, with paths of 80 to 100 cm. One fruit a plant, 16 to 24 months after planting.'],
        'sugarcane' => ['name' => 'Sugarcane', 'local' => 'Tubo', 'group' => 'tree', 'methods' => ['piece'], 'layout' => 'rows', 'piece' => ['sett', 'setts'],
            'row' => [100, 120, 150], 'plant' => [15, 25, 40], 'perHill' => [1, 1, 1], 'keep' => null,
            'germ' => [90, 75], 'loss' => 5, 'reserve' => 10, 'pieceWt' => null,
            'stand' => [25000, 45000], 'standBy' => 'plants',
            'yield' => ['model' => 'stalk', 'stalks' => self::CANE_TILLERS, 'millable' => self::CANE_MILLABLE, 'stalkKg' => self::CANE_STALK_KG, 'stalkMax' => self::CANE_STALKS_MAX,
                'loss' => 5, 'what' => 'millable cane', 'word' => 'millable stalks'],
            'note' => 'Setts of 2 to 3 buds laid in furrows, about 30,000 to 40,000 a hectare. Each sett grows into a clump of tillers, and only about half to two thirds of them grow into cane the mill takes.'],
        'coconut' => ['name' => 'Coconut', 'local' => 'Niyog', 'group' => 'tree', 'methods' => ['transplant'], 'layout' => 'triangle',
            'row' => [700, 800, 1000], 'plant' => [800, 900, 1000], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => null, 'loss' => 5, 'reserve' => 5, 'tgw' => null,
            'stand' => [100, 180], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 60, 'unit' => 'count', 'loss' => 5, 'what' => 'nuts', 'perYear' => true],
            'note' => 'Talls in 9 m triangles (143 palms a hectare), dwarfs at about 8 m (180). Hybrids and dwarfs reach full bearing years before talls.'],
        'cacao' => ['name' => 'Cacao', 'local' => 'Kakaw', 'group' => 'tree', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [250, 300, 400], 'plant' => [250, 300, 400], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => null, 'loss' => 10, 'reserve' => 10, 'tgw' => null,
            'stand' => [540, 1111], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 1, 'unit' => 'kg', 'loss' => 5, 'what' => 'dry beans', 'perYear' => true],
            'note' => 'Grafted trees at 3 by 3 m on their own (about 1,100 a hectare), wider under coconut (about 600). Full bearing at about 5 years.'],
        'coffee' => ['name' => 'Coffee', 'local' => 'Kape', 'group' => 'tree', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [200, 300, 400], 'plant' => [200, 300, 400], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => null, 'loss' => 10, 'reserve' => 10, 'tgw' => null,
            'stand' => [560, 2500], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 1, 'unit' => 'kg', 'loss' => 5, 'what' => 'green beans', 'perYear' => true],
            'note' => 'Robusta at 3 by 3 m or 3 by 2 m, Arabica at 2 by 2 m, Liberica and Excelsa at 3 by 4 m to 4 by 4 m.'],
        'mango' => ['name' => 'Mango', 'local' => 'Mangga', 'group' => 'tree', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [800, 1000, 1200], 'plant' => [800, 1000, 1200], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => null, 'loss' => 10, 'reserve' => 5, 'tgw' => null,
            'stand' => [62, 156], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 100, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit', 'perYear' => true],
            'note' => 'Grafted trees at 10 by 10 m (100 a hectare) to 12 by 12 m (69); full bearing takes about 10 years.'],
        'calamansi' => ['name' => 'Calamansi', 'local' => 'Kalamansi', 'group' => 'tree', 'methods' => ['transplant'], 'layout' => 'rows',
            'row' => [400, 500, 600], 'plant' => [400, 500, 600], 'perHill' => [1, 1, 1], 'keep' => 1,
            'germ' => null, 'loss' => 10, 'reserve' => 5, 'tgw' => null,
            'stand' => [250, 625], 'standBy' => 'plants',
            'yield' => ['model' => 'plant', 'perPlant' => 25, 'unit' => 'kg', 'loss' => 10, 'what' => 'fresh fruit', 'perYear' => true],
            'note' => 'Marcotted or budded trees at about 5 m; real bearing from the fourth year.'],
    ];

    /** The yield parts a farmer can type; left empty, the variety's own or the crop's usual count. */
    public const YIELD_KEYS = ['tillers', 'grains', 'filled', 'ceiling', 'ears', 'perEar', 'perPlant', 'stalks', 'millable', 'stalkKg'];

    /** Sugarcane's parts the variety shelf keeps beside its parts (VarietyYield extra), until a fold makes them parts. */
    public const STALK_EXTRA = ['stalks' => 'stalksClump', 'stalkKg' => 'stalkKg'];

    /**
     * The kinds of seed a crop is sold as, [usual germination %, the least
     * such seed shows], and the usual kind (researched 2026-10-19: the printed
     * germination on seed bags and seller lots, seed standards for the least,
     * and seed testing surveys of saved seed; the scratchpad's seed_types.json).
     */
    public const SEED_KINDS = [
        'rice' => ['default' => 'good', 'types' => ['hybrid' => [88, 85], 'opv' => [85, 80], 'good' => [88, 85], 'saved' => [80, 65]]],
        'rice_dsr' => ['default' => 'good', 'types' => ['hybrid' => [88, 85], 'opv' => [85, 80], 'good' => [88, 85], 'saved' => [80, 65]]],
        'corn_yellow' => ['default' => 'hybrid', 'types' => ['hybrid' => [90, 85], 'opv' => [85, 85], 'saved' => [80, 50]]],
        'corn_white' => ['default' => 'opv', 'types' => ['hybrid' => [85, 85], 'opv' => [85, 85], 'saved' => [80, 50]]],
        'corn_sweet' => ['default' => 'hybrid', 'types' => ['hybrid' => [88, 85], 'opv' => [85, 75], 'saved' => [65, 40]]],
        'sorghum' => ['default' => 'opv', 'types' => ['hybrid' => [85, 80], 'opv' => [85, 80], 'saved' => [70, 50]]],
        'mungbean' => ['default' => 'opv', 'types' => ['opv' => [85, 80], 'saved' => [75, 50]]],
        'peanut' => ['default' => 'opv', 'types' => ['opv' => [80, 70], 'saved' => [70, 40]]],
        'soybean' => ['default' => 'opv', 'types' => ['opv' => [85, 80], 'saved' => [75, 50]]],
        'stringbean' => ['default' => 'opv', 'types' => ['opv' => [85, 75], 'saved' => [80, 50]]],
        'bushsitaw' => ['default' => 'opv', 'types' => ['opv' => [85, 75], 'saved' => [80, 50]]],
        'okra' => ['default' => 'opv', 'types' => ['hybrid' => [85, 65], 'opv' => [80, 65], 'saved' => [70, 40]]],
        'tomato' => ['default' => 'hybrid', 'types' => ['hybrid' => [90, 85], 'opv' => [88, 70], 'saved' => [70, 40]]],
        'eggplant' => ['default' => 'hybrid', 'types' => ['hybrid' => [85, 70], 'opv' => [90, 70], 'saved' => [70, 40]]],
        'chili' => ['default' => 'hybrid', 'types' => ['hybrid' => [88, 85], 'opv' => [85, 60], 'saved' => [70, 40]]],
        'bellpepper' => ['default' => 'hybrid', 'types' => ['hybrid' => [85, 60], 'opv' => [85, 60], 'saved' => [65, 40]]],
        'ampalaya' => ['default' => 'hybrid', 'types' => ['hybrid' => [85, 60], 'opv' => [85, 60], 'saved' => [75, 40]]],
        'squash' => ['default' => 'hybrid', 'types' => ['hybrid' => [90, 85], 'opv' => [85, 60], 'saved' => [75, 40]]],
        'cucumber' => ['default' => 'hybrid', 'types' => ['hybrid' => [90, 85], 'opv' => [85, 60], 'saved' => [70, 40]]],
        'watermelon' => ['default' => 'hybrid', 'types' => ['hybrid' => [85, 60], 'opv' => [88, 60], 'saved' => [70, 40]]],
        'upo' => ['default' => 'opv', 'types' => ['hybrid' => [85, 60], 'opv' => [85, 60], 'saved' => [75, 40]]],
        'patola' => ['default' => 'opv', 'types' => ['hybrid' => [85, 60], 'opv' => [85, 60], 'saved' => [75, 40]]],
        'pechay' => ['default' => 'opv', 'types' => ['hybrid' => [90, 75], 'opv' => [90, 75], 'saved' => [75, 50]]],
        'cabbage' => ['default' => 'hybrid', 'types' => ['hybrid' => [88, 75], 'opv' => [90, 70]]],
        'lettuce' => ['default' => 'opv', 'types' => ['opv' => [92, 80], 'saved' => [75, 50]]],
        'onion' => ['default' => 'opv', 'types' => ['hybrid' => [85, 70], 'opv' => [85, 70], 'saved' => [65, 40]]],
        'carrot' => ['default' => 'opv', 'types' => ['hybrid' => [80, 55], 'opv' => [80, 55]]],
        'radish' => ['default' => 'opv', 'types' => ['hybrid' => [85, 75], 'opv' => [85, 75], 'saved' => [70, 40]]],
        'papaya' => ['default' => 'hybrid', 'types' => ['hybrid' => [75, 60], 'opv' => [75, 60], 'saved' => [70, 40]]],
    ];

    /** What the usual field's answer keeps. */
    public const USUAL_KEYS = ['status', 'standHa', 'standLot', 'plantsHa', 'emptyShare', 'buyLo', 'buyHi', 'buyLotLo', 'buyLotHi', 'kgLo', 'kgHi', 'kgLotLo', 'kgLotHi',
        'kgHaLo', 'kgHaHi', 'yLotLo', 'yLotHi', 'piecesHa', 'piecesHaLo', 'piecesLot', 'piecesLotLo', 'row', 'plant', 'square', 'perHill', 'fieldHa', 'reserve',
        'packLo', 'packHi', 'packLotLo', 'packLotHi'];

    /** The words a pack may be called by; anything else is a "pack". */
    public const PACK_LABELS = ['bag', 'can', 'pack', 'pouch', 'sack', 'box', 'tin'];

    /** A pack's id, its size written the same in PHP and JS ("seeds-35000-bag", "kg-0.5-bag"). */
    private static function packNum(float $v): string
    {
        return rtrim(rtrim(sprintf('%.4f', $v), '0'), '.');
    }

    /** One pack held to its shape (kind, size, label, word, about, what, seed), or null. */
    private static function packOf($p, string $from): ?array
    {
        if (! is_array($p)) {
            return null;
        }
        $kind = (string) ($p['kind'] ?? '');
        $size = $p['size'] ?? null;
        if (! in_array($kind, ['kg', 'g', 'seeds'], true) || ! is_numeric($size) || (float) $size <= 0 || (float) $size > 1e7) {
            return null;
        }
        $size = (float) $size;
        $label = substr((string) preg_replace('/[^a-z]/', '', strtolower((string) ($p['label'] ?? ''))), 0, 12);
        $label = in_array($label, self::PACK_LABELS, true) ? $label : 'pack';
        $what = is_string($p['what'] ?? null) && preg_match('/^[A-Za-z ]{1,30}$/', $p['what']) ? $p['what'] : null;

        return [
            'id' => $kind . '-' . self::packNum($size) . '-' . $label, 'kind' => $kind, 'size' => $size, 'label' => $label,
            'word' => $kind === 'seeds' && ($p['word'] ?? '') === 'kernels' ? 'kernels' : 'seeds',
            'about' => isset($p['about']) && is_numeric($p['about']) && (float) $p['about'] > 0 ? (float) $p['about'] : null,
            'what' => $what, 'seed' => array_values(array_filter((array) ($p['seed'] ?? []), 'is_string')), 'from' => $from,
        ];
    }

    /**
     * The packs this seed is sold in (2026-10-19, the owner: "say the # of
     * bags needed for seeds, not only kg"): the variety's own (VarietyYield
     * part 'pack', one or a list) first, then the crop's. Only for seed that
     * is sown or raised in a seedbed; bought plants and pieces have none, and
     * a weighed pack needs a seed weight.
     */
    public static function packs(array $c, ?array $vy): array
    {
        $C = self::CROPS[$c['crop']];
        $m = $c['method'];
        if ($m === 'piece' || ($m === 'transplant' && empty($C['tgw']))) {
            return [];
        }
        $v = $vy['pack'] ?? null;
        $own = is_array($v) ? (array_is_list($v) ? array_slice($v, 0, 8) : [$v]) : [];
        $out = [];
        foreach ([[$own, 'variety'], [(array) ($C['packs'] ?? []), 'crop']] as [$list, $from]) {
            foreach ($list as $p) {
                $p = self::packOf($p, $from);
                if ($p && ! isset($out[$p['id']]) && ($p['kind'] === 'seeds' || self::weighs($C, $m))) {
                    $out[$p['id']] = $p;
                }
            }
        }

        return array_values($out);
    }

    /** The pack counted: the farmer's pick, else the variety's first, else the crop's usual for the kind of seed, else its first. */
    public static function packPick(array $list, array $c): ?array
    {
        if (! $list) {
            return null;
        }
        foreach ($list as $p) {
            if ($p['id'] === ($c['pack'] ?? null)) {
                return $p;
            }
        }
        if ($list[0]['from'] === 'variety') {
            return $list[0];
        }
        $t = $c['seed']['type'] ?? null;
        foreach ($list as $p) {
            if ($t !== null && in_array($t, $p['seed'], true)) {
                return $p;
            }
        }

        return $list[0];
    }

    /**
     * The seed's germination, its least, the seedbed fit, the losses, the
     * replant reserve and the harvest loss: the variety's own (VarietyYield
     * parts germ, germMin, seedFit, loss), else its kind's (seedTypes), else
     * the crop's. Said back as plain lines; the farmer types none of them.
     */
    public static function withSeed(array $c, ?array $vy): array
    {
        $C = self::CROPS[$c['crop']];
        $kinds = self::SEED_KINDS[$c['crop']] ?? null;
        $types = (array) ($kinds['types'] ?? []);
        $type = isset($types[$c['seedType'] ?? '']) ? $c['seedType'] : ($kinds['default'] ?? null);
        $mid = fn ($v) => ($s = self::span($v)) ? ($s[0] + $s[1]) / 2 : null;
        $vg = $mid($vy['germ'] ?? null);
        $tg = $type !== null && isset($types[$type]) ? (float) $types[$type][0] : null;
        $tag = isset($c['germTag']) && $c['germTag'] > 0 ? (float) $c['germTag'] : null;
        $germ = $tag ?? $vg ?? $tg ?? (float) (($C['germ'] ?? [100])[0]);
        $vm = $mid($vy['germMin'] ?? null);
        // The least good seed shows: the variety's, else the crop's usual kind's, else the crop's.
        $dk = $kinds['default'] ?? null;
        $germMin = $vm ?? ($dk !== null && isset($types[$dk][1]) ? (float) $types[$dk][1] : (float) (($C['germ'] ?? [100, 100])[1]));
        $vf = $mid($vy['seedFit'] ?? null);
        $fit = $vf ?? (float) ($C['fit'] ?? 100);
        $vl = $mid($vy['loss'] ?? null);
        $loss = $vl ?? (float) $C['loss'];
        $hold = fn ($v, $lo, $hi) => max($lo, min($hi, (float) $v));
        $out = [
            'germ' => $hold($germ, 1, 100), 'germMin' => $hold($germMin, 1, 100), 'fit' => $hold($fit, 1, 100), 'loss' => $hold($loss, 0, 90),
            'reserve' => (float) $C['reserve'], 'harvestLoss' => (float) $C['yield']['loss'],
        ];

        return $c + $out + ['seed' => $out + ['type' => $type,
            'from' => ['germ' => $tag !== null ? 'tag' : ($vg !== null ? 'variety' : ($tg !== null ? 'type' : 'crop')), 'germMin' => $vm !== null ? 'variety' : 'crop',
                'fit' => $vf !== null ? 'variety' : 'crop', 'loss' => $vl !== null ? 'variety' : 'crop']]];
    }

    /** What one crop starts on, for the page and the server alike. */
    public static function crop(string $key): array
    {
        $C = self::CROPS[$key] ?? self::CROPS['corn_yellow'];

        return $C + ['key' => array_key_exists($key, self::CROPS) ? $key : 'corn_yellow'];
    }

    /** The seeds (seedlings, pieces) a hill a crop starts on for a method. */
    public static function perHillFor(array $C, string $method): array
    {
        return $C[$method]['perHill'] ?? $C['perHill'];
    }

    /** Inputs read and held to sensible ranges; null when a must is missing. */
    public static function clean(array $in): ?array
    {
        $c = self::read($in);

        return self::missing($c) === '' ? $c : null;
    }

    public static function read(array $in): array
    {
        $key = array_key_exists((string) ($in['crop'] ?? ''), self::CROPS) ? (string) $in['crop'] : 'corn_yellow';
        $C = self::CROPS[$key];
        $n = fn ($k, $min, $max, $def = null) => isset($in[$k]) && $in[$k] !== '' && is_numeric($in[$k]) ? max($min, min($max, (float) $in[$k])) : $def;
        $pick = fn ($k, array $dict, $def) => array_key_exists((string) ($in[$k] ?? ''), $dict) ? (string) $in[$k] : $def;
        $method = in_array($in['method'] ?? '', $C['methods'], true) ? (string) $in['method'] : $C['methods'][0];
        $bed = $C['bed'] ?? self::BED;
        $ph = self::perHillFor($C, $method);
        $Y = $C['yield'];
        $variety = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($in['variety'] ?? ''))));
        $c = [
            'crop' => $key, 'method' => $method,
            'variety' => $variety === '' ? null : mb_substr($variety, 0, 120),
            // The lot it is for, and when it is planted (2026-10-19, the owner): its soil, water and season.
            'lotId' => isset($in['lotId']) && is_numeric($in['lotId']) && (int) $in['lotId'] > 0 ? (int) $in['lotId'] : null,
            'month' => preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($in['month'] ?? '')) ? (string) $in['month'] : null,
            'day' => ($dd = $n('day', 1, 31)) === null ? null : (int) round($dd),
            'layout' => $pick('layout', self::LAYOUTS, $C['layout']),
            'mode' => $pick('mode', self::MODES, 'seeds'),
            'area' => $n('area', 0, 1000000), 'areaUnit' => $pick('areaUnit', self::AREA_UNITS, 'ha'),
            'row' => $n('row', 1, 5000, (float) $C['row'][1]),
            'plant' => $n('plant', 0.5, 5000, (float) $C['plant'][1]),
            'bed' => $n('bed', 10, 1000, (float) $bed[0]),
            'bedRows' => (int) round($n('bedRows', 1, 12, (float) $bed[1])),
            'rowSet' => $pick('rowSet', self::ROWSET, 'set'),
            'amount' => $n('amount', 0, 1e12),
            'amountUnit' => $pick('amountUnit', self::AMOUNT_UNITS, 'count'),
            'amountPer' => $pick('amountPer', self::PER, 'ha'),
            'target' => $n('target', 0, 1e9),
            'targetPer' => $pick('targetPer', self::PER, 'ha'),
            'hillMode' => $pick('hillMode', self::HILLMODE, 'auto'),
            'perHill' => (int) round($n('perHill', 1, 20, (float) $ph[1])),
            'keep' => ($k = $n('keep', 1, 20, $C['keep'] === null ? null : (float) $C['keep'])) === null ? null : (int) round($k),
            // Germination and losses are no longer typed (withSeed resolves them): the seed's kind is.
            'seedType' => isset(self::SEED_KINDS[$key]['types'][$in['seedType'] ?? '']) ? (string) $in['seedType'] : null,
            // The seed tag's germination, where another module asks it (2026-10-21, PlantAmount): it wins in withSeed.
            // This page never sends it.
            'germTag' => $n('germTag', 1, 100),
            'weight' => $n('weight', 0.01, 10000000),
            // The pack the farmer picked (2026-10-19): null counts the variety's or the crop's usual.
            'pack' => preg_match('/^(kg|g|seeds)-\d+(\.\d+)?-[a-z]{1,12}$/', (string) ($in['pack'] ?? '')) ? (string) $in['pack'] : null,
            // The yield parts: null keeps the variety's or the crop's own.
            'tillers' => $n('tillers', 0, 100),
            'grains' => $n('grains', 1, 500),
            'filled' => $n('filled', 1, 100),
            'ears' => $n('ears', 0, 5),
            'perEar' => $n('perEar', 0, 5000),
            'perPlant' => $n('perPlant', 0, 100000),
            // Sugarcane: tillers a clump, the millable share %, the weight of one stalk kg.
            'stalks' => $n('stalks', 0, 100),
            'millable' => $n('millable', 1, 100),
            'stalkKg' => $n('stalkKg', 0, 20),
        ];
        // A weighed amount needs a seed weight; with none to go on it is a count.
        if ($c['amountUnit'] !== 'count' && ! self::weighs($C, $method)) {
            $c['amountUnit'] = 'count';
        }

        return $c;
    }

    /** Why the inputs cannot be counted yet; '' when they can. */
    public static function missing(array $c): string
    {
        $areaHa = self::areaHa($c);
        if ($c['mode'] === 'spacing') {
            if (! $c['amount']) {
                return 'Fill in how many seeds you have.';
            }
            if ($c['amountPer'] === 'lot' && ! $areaHa) {
                return 'Fill in the lot size, or say the seeds are for a hectare.';
            }
        } elseif ($c['mode'] === 'stand') {
            if (! $c['target']) {
                return 'Fill in the stand you want.';
            }
            if ($c['targetPer'] === 'lot' && ! $areaHa) {
                return 'Fill in the lot size, or say the stand is for a hectare.';
            }
        }

        return '';
    }

    public static function areaHa(array $c): ?float
    {
        return $c['area'] ? ($c['areaUnit'] === 'sqm' ? $c['area'] / 10000 : $c['area']) : null;
    }

    /** Whether the crop's planting material can be weighed: seeds with a known weight, or pieces with one. */
    public static function weighs(array $C, string $method): bool
    {
        return $method === 'piece' ? ! empty($C['pieceWt']) : ! empty($C['tgw']);
    }

    /**
     * A variety's own yield parts (2026-10-19, the owner), when the shelf that
     * keeps them exists (App\Support\VarietyYield, researched once and kept):
     * keys as in a crop's yield (tillers, grains, filled, tgw, ceiling, ears,
     * perEar, perPlant, fruits, fruitWt), each a number or [low, high], with
     * 'basis' and 'name', and 'extra' (sugarcane's stalksClump and stalkKg).
     * Null when there is no variety, no shelf, or no row.
     */
    public static function varietyYield(array $c): ?array
    {
        $name = trim((string) ($c['variety'] ?? ''));
        if ($name === '' || ! class_exists(\App\Support\VarietyYield::class)) {
            return null;
        }
        $v = rescue(fn () => \App\Support\VarietyYield::for((string) $c['crop'], $name), null);

        return is_array($v) && $v ? $v : null;
    }

    /**
     * What a variety gave in its trials, to say beside what the stand can
     * carry (2026-10-19, the owner): the shelf's fitted average, else the
     * variety book's average and top. Never multiplied into the count.
     */
    public static function trial(string $crop, ?string $name, ?array $vy): ?array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        $book = class_exists(\App\Support\VarietyYield::class) ? \App\Support\VarietyYield::bookKey($crop) : (self::CROPS[$crop]['book'] ?? $crop);
        $rec = rescue(fn () => VarietyBook::match($book, $name), null, false);
        $fy = $vy['extra']['fitYield'] ?? null;
        $fromFit = is_numeric($fy) && (float) $fy > 0;
        $avg = $fromFit ? (float) $fy : (isset($rec['yield']) && is_numeric($rec['yield']) && (float) $rec['yield'] > 0 ? (float) $rec['yield'] : null);
        if ($avg === null) {
            return null;
        }
        // The shelf's fitted yield is always t/ha (a sugarcane book counts LKg of sugar: its top is another unit).
        $recUnit = (string) ($rec['yieldUnit'] ?? 't/ha') ?: 't/ha';
        $unit = $fromFit ? 't/ha' : $recUnit;
        $top = $recUnit === $unit && isset($rec['yieldTop']) && is_numeric($rec['yieldTop']) && (float) $rec['yieldTop'] > $avg ? (float) $rec['yieldTop'] : null;

        return ['name' => (string) ($rec['name'] ?? $vy['name'] ?? $name), 'avg' => $avg, 'top' => $top, 'unit' => $unit];
    }

    /** A variety part as [low, high], or null. */
    public static function span($o): ?array
    {
        if (is_array($o) && isset($o[0], $o[1]) && is_numeric($o[0]) && is_numeric($o[1])) {
            return [min((float) $o[0], (float) $o[1]), max((float) $o[0], (float) $o[1])];
        }

        return is_numeric($o) ? [(float) $o, (float) $o] : null;
    }

    /**
     * The yield parts the count runs on, each [low, high]: the farmer's own
     * number, else the variety's, else the crop's usual. A plant's harvest can
     * come from the variety's fruits a plant times the weight of one (g).
     * Sugarcane's tillers a clump and stalk weight are the variety's part, else
     * what the shelf keeps beside its parts (STALK_EXTRA).
     */
    public static function parts(array $c, ?array $vy): array
    {
        $Y = self::CROPS[$c['crop']]['yield'];
        $ex = is_array($vy['extra'] ?? null) ? $vy['extra'] : [];
        $out = [];
        foreach (self::YIELD_KEYS as $k) {
            if (($c[$k] ?? null) !== null) {
                $out[$k] = [(float) $c[$k], (float) $c[$k]];
                continue;
            }
            $v = self::span($vy[$k] ?? null) ?? (isset(self::STALK_EXTRA[$k]) ? self::span($ex[self::STALK_EXTRA[$k]] ?? null) : null);
            if (! $v && $k === 'perPlant' && $vy) {
                $f = self::span($vy['fruits'] ?? null);
                $w = self::span($vy['fruitWt'] ?? null);
                $u = $Y['unit'] ?? 'kg';
                if ($f && ($u === 'count' || $w)) {
                    $v = $u === 'count' ? $f : [$f[0] * $w[0] / ($u === 'kg' ? 1000 : 1), $f[1] * $w[1] / ($u === 'kg' ? 1000 : 1)];
                }
            }
            $out[$k] = $v ?? (isset($Y[$k]) ? self::span($Y[$k]) : null);
        }

        return $out;
    }

    /** n choose x. */
    private static function choose(int $n, int $x): float
    {
        $r = 1.0;
        for ($i = 1; $i <= $x; $i++) {
            $r = $r * ($n - $x + $i) / $i;
        }

        return $r;
    }

    /** The mean of min(X, k), X binomial (n, p): the plants a hill keeps after thinning. */
    public static function keptMean(int $n, float $p, int $k): float
    {
        $s = 0.0;
        for ($x = 0; $x <= $n; $x++) {
            $s += self::choose($n, $x) * ($p ** $x) * ((1 - $p) ** ($n - $x)) * min($x, $k);
        }

        return $s;
    }

    /** The share of hills with fewer than k plants, X binomial (n, p). */
    public static function shortShare(int $n, float $p, int $k): float
    {
        $s = 0.0;
        for ($x = 0; $x < min($k, $n + 1); $x++) {
            $s += self::choose($n, $x) * ($p ** $x) * ((1 - $p) ** ($n - $x));
        }

        return $s;
    }

    /** Whether n a hill leaves few enough hills empty (SHORT_OK, or SHORT_ONE for a crop sown one a hill), and (kept 2 or more) few enough short. */
    public static function enough(int $n, float $p, ?int $keep, float $ok = self::SHORT_OK): bool
    {
        if (((1 - $p) ** $n) > $ok + 1e-9) {
            return false;
        }

        return $keep === null || $keep < 2 || self::shortShare($n, $p, $keep) <= self::SHORT_PART + 1e-9;
    }

    /** The fewest a hill that are enough; ok = false when even the most are not. */
    public static function suggest(array $C, string $method, float $p, ?int $keep): array
    {
        $ph = self::perHillFor($C, $method);
        if ($method === 'transplant') {
            return ['n' => $ph[1], 'ok' => true];
        }
        $top = max($ph[2], $keep ?? 1);
        $ok = ! empty($C['oneSeed']) ? self::SHORT_ONE : self::SHORT_OK;
        for ($n = max($keep ?? 1, $ph[0]); $n <= $top; $n++) {
            if (self::enough($n, $p, $keep, $ok)) {
                // A crop not thinned is sown for its stand: never fewer than its usual.
                return ['n' => $keep === null ? max($n, $ph[1]) : $n, 'ok' => true];
            }
        }

        return ['n' => $top, 'ok' => false];
    }

    /**
     * The whole count, from cleaned inputs: a variety's yield parts (looked
     * up when not given) and the lot and season (SeedPenalty::context(), when
     * given): the field and the season count in, and the same plan is counted
     * again on an ideal field (ideal), so the farmer sees what each costs.
     */
    public static function compute(array $c, ?array $vy = null, ?array $ctx = null): array
    {
        $vy ??= self::varietyYield($c);
        $C = self::crop($c['crop']);
        $c = self::withSeed($c, $vy);
        $pen = $ctx && ($ctx['facts'] || $ctx['season']) ? SeedPenalty::of($C, $c['crop'], $c['method'], $ctx['facts'] ?? null, $ctx['season'] ?? null) : SeedPenalty::none();
        $r = self::core($c, $vy, $pen);
        // On a usual field: the same inputs, counted with no lot, season or forecast taken off (2026-10-19, the owner: a range between the two).
        $usual = null;
        if ($pen['emerge']['lines'] || $pen['seedbed']['lines'] || $pen['growth']['lines']) {
            $b = self::core($c, $vy, SeedPenalty::none());
            $usual = array_intersect_key($b, array_flip(self::USUAL_KEYS));
        }
        unset($r['_row'], $r['_plant']);

        return $r + ['pen' => $pen, 'usual' => $usual, 'seed' => $c['seed'], 'packs' => self::packs($c, $vy)];
    }

    /** The count itself, with the field and season penalties given (SeedPenalty::of or none), and the seeds a hill when held. */
    private static function core(array $c, ?array $vy, array $pen, ?int $forceN = null): array
    {
        $C = self::crop($c['crop']);
        $Y = $C['yield'];
        $method = $c['method'];
        $areaHa = self::areaHa($c);
        $g = $c['germ'] / 100;
        $L = $c['loss'] / 100;
        $fit = $c['fit'] / 100;
        $res = $c['reserve'] / 100;
        // Seedlings from the farmer's own seedbed are counted back to seeds; bought plants are not.
        $nursery = $method === 'transplant' && ! empty($C['tgw']);
        $hasGerm = $method !== 'transplant' || $nursery;
        // The chance one seed, piece or seedling put in a hill comes up (or lives); the losses after thinning.
        // The field and the season at emergence lower the chance (SeedPenalty); the seedbed's temperature, its germination.
        $pCome = ($method === 'transplant' ? 1 - $L : $g) * $pen['emerge']['k'];
        $gBed = $g * $pen['seedbed']['k'];
        $post = $method === 'transplant' ? 1.0 : 1 - $L;
        $pLive = $pCome * $post;
        $sug = self::suggest($C, $method, $pCome, $c['keep']);
        $n = $forceN ?? ($c['hillMode'] === 'auto' ? $sug['n'] : $c['perHill']);
        $k = min($c['keep'] ?? $n, $n);
        $kept = self::keptMean($n, $pCome, $k) * $post;
        $empty = (1 - $pCome) ** $n;
        $short = $c['keep'] !== null && $c['keep'] >= 2 ? self::shortShare($n, $pCome, min($c['keep'], $n)) : $empty;
        $filled = 1 - (1 - $pLive) ** $n;
        // Sown one a hill, the gaps replanted within a week from the reserve: the reserve covers the empty share, and the hills replanted count.
        $replant = ! empty($C['oneSeed']) && $method === 'direct' && $n === 1;
        if ($replant) {
            $res = max($res, $empty);
            $kept = ($pCome + (1 - $pCome) * $pCome) * $post;
        }
        $byHills = $C['standBy'] === 'hills';
        $perHillStand = $byHills ? $filled : $kept;
        // The weight of one seed or piece, g: the farmer's own, the variety's, or the crop's spread.
        $wts = null;
        if (self::weighs($C, $method)) {
            $own = $c['weight'] ? ($method === 'piece' ? $c['weight'] : $c['weight'] / 1000) : null;
            $vw = $method === 'piece' ? null : self::span($vy['tgw'] ?? null);
            $span = $method === 'piece' ? $C['pieceWt'] : array_map(fn ($v) => $v / 1000, $vw ?? $C['tgw']);
            $wts = $own ? ['lo' => $own, 'mid' => $own, 'hi' => $own, 'own' => true]
                : ['lo' => $span[0], 'mid' => ($span[0] + $span[1]) / 2, 'hi' => $span[1], 'own' => false];
        }
        $usualRow = (float) $C['row'][1];

        // 4. The other way round: the hills a hectare from the seeds or the target, then the spacing.
        $row = $c['row'];
        $plant = $c['plant'];
        $square = null;
        $plantSpan = null;
        $given = null;
        if ($c['mode'] !== 'seeds') {
            $hillsFor = function (?float $unit) use ($c, $areaHa, $nursery, $gBed, $fit, $n, $res, $perHillStand) {
                if ($c['mode'] === 'spacing') {
                    $count = $c['amountUnit'] === 'count' ? $c['amount'] : ($c['amountUnit'] === 'kg' ? $c['amount'] * 1000 : $c['amount']) / $unit;
                    $perHa = $c['amountPer'] === 'lot' ? $count / $areaHa : $count;
                    $field = $nursery ? $perHa * $gBed * $fit : $perHa;

                    return ['count' => $count, 'hills' => $field / ($n * (1 + $res))];
                }
                $perHa = $c['targetPer'] === 'lot' ? $c['target'] / $areaHa : $c['target'];

                return ['count' => null, 'hills' => $perHa / max(1e-9, $perHillStand)];
            };
            $solve = function (float $hills) use ($c, $usualRow) {
                $a = 1e8 / max(1e-9, $hills);
                if ($c['layout'] === 'triangle') {
                    return ['plant' => sqrt($a / self::TRI), 'row' => $c['row'], 'square' => null];
                }
                if ($c['layout'] === 'beds') {
                    return ['plant' => $a / ($c['bed'] / $c['bedRows']), 'row' => $c['row'], 'square' => null];
                }
                $r = $c['rowSet'] === 'free' ? $usualRow : $c['row'];

                return ['plant' => $a / $r, 'row' => $r, 'square' => $c['rowSet'] === 'free' ? sqrt($a) : null];
            };
            $mid = $hillsFor($wts['mid'] ?? null);
            $s = $solve($mid['hills']);
            $plant = $s['plant'];
            $row = $s['row'];
            $square = $s['square'];
            $given = $mid['count'];
            // Seeds weighed with no seed weight of the farmer's own: the spacing spans the seed sizes.
            if ($c['mode'] === 'spacing' && $c['amountUnit'] !== 'count' && $wts && ! $wts['own'] && $wts['lo'] < $wts['hi']) {
                $plantSpan = [$solve($hillsFor($wts['hi'])['hills'])['plant'], $solve($hillsFor($wts['lo'])['hills'])['plant']];
                sort($plantSpan);
            }
        }

        // 1. The ground a hill takes, and the hills.
        $effRow = $c['layout'] === 'beds' ? $c['bed'] / $c['bedRows'] : ($c['layout'] === 'triangle' ? $plant * self::TRI : $row);
        $areaHill = $effRow * $plant;
        $hillsHa = 1e8 / $areaHill;
        // 2. What the hills take, and the seeds to buy.
        $fieldHa = $hillsHa * $n;
        $buyHa = $nursery ? $fieldHa / ($gBed * $fit) : $fieldHa;
        $needLo = $buyHa;
        $needHi = $buyHa * (1 + $res);
        $kgLo = $wts ? $needLo * $wts['lo'] / 1000 : null;
        $kgHi = $wts ? $needHi * $wts['hi'] / 1000 : null;
        // The packs to buy: a pack sold by count from the seeds (the seed size does not matter), a weighed one from the kilos.
        $pk = self::packPick(self::packs($c, $vy), $c);
        $packLo = null;
        $packHi = null;
        $packEach = null;
        if ($pk && $pk['kind'] === 'seeds') {
            $packLo = $needLo / $pk['size'];
            $packHi = $needHi / $pk['size'];
            $packEach = $wts ? $pk['size'] * $wts['mid'] / 1000 : null;
        } elseif ($pk && $wts) {
            $kgP = $pk['kind'] === 'kg' ? $pk['size'] : $pk['size'] / 1000;
            $packLo = $kgLo / $kgP;
            $packHi = $kgHi / $kgP;
            $packEach = $kgP * 1000 / $wts['mid'];
        }
        // 3. The stand.
        $plantsHa = $hillsHa * $kept;
        $standHa = $hillsHa * $perHillStand;
        [$lo, $hi] = $C['stand'];
        $status = $standHa < $lo * self::THIN ? 'sparse' : ($standHa < $lo ? 'thin' : ($standHa <= $hi ? 'good' : ($standHa <= $hi * self::CROWD ? 'crowded' : 'danger')));
        // The plant distance at these rows that lands the stand in the good range.
        $at = fn ($stand) => $c['layout'] === 'triangle' ? sqrt(1e8 * $perHillStand / ($stand * self::TRI)) : 1e8 * $perHillStand / ($stand * $effRow);
        $goodPlant = [$at($hi), $at($lo)];

        // 5. The yield: the low end from the low parts less the harvest loss, the top from the high parts.
        $P = self::parts($c, $vy);
        $keepShare = 1 - $c['harvestLoss'] / 100;
        $kgHa = null;
        $kgHaLo = null;
        $pieces = null;
        $piecesLo = null;
        $panM2 = null;
        $panM2Lo = null;
        $stalks = null;
        $stalksLo = null;
        $capped = false;
        $plateau = false;
        $plantsY = $plantsHa;
        $gw = null;
        if ($Y['model'] === 'tiller') {
            $gw = $c['weight'] ? [$c['weight'], $c['weight']] : (self::span($vy['tgw'] ?? null) ?? array_fill(0, 2, ($C['tgw'][0] + $C['tgw'][1]) / 2));
            $raw = $hillsHa * $filled * $P['tillers'][1] / 10000;
            $panM2 = min($raw, $P['ceiling'][1]);
            $panM2Lo = min($hillsHa * $filled * $P['tillers'][0] / 10000, $P['ceiling'][0]);
            $capped = $raw > $P['ceiling'][1];
            // Heat at flowering empties spikelets: the filled share falls (SeedPenalty::FLOWER_HEAT).
            $kgHa = $panM2 * $P['grains'][1] * $P['filled'][1] * $pen['flower'] / 100 * $gw[1] / 1000 * 10;
            $kgHaLo = $panM2Lo * $P['grains'][0] * $P['filled'][0] * $pen['flower'] / 100 * $gw[0] / 1000 * 10 * $keepShare;
        } else {
            if ($plantsHa > $hi) {
                $plantsY = (float) $hi;
                $plateau = true;
            }
            if ($Y['model'] === 'ear') {
                $kgHa = $plantsY * $P['ears'][1] * $P['perEar'][1] / 1000;
                $kgHaLo = $plantsY * $P['ears'][0] * $P['perEar'][0] / 1000 * $keepShare;
                if (! empty($Y['fresh'])) {
                    $pieces = $plantsY * $P['ears'][1];
                    $piecesLo = $plantsY * $P['ears'][0] * $keepShare;
                }
            } elseif ($Y['model'] === 'stalk') {
                // Sugarcane: the clumps x the tillers a clump x the millable share, no more stalks than a field carries, x the weight of a stalk.
                $max = (float) $Y['stalkMax'];
                $raw = $plantsY * $P['stalks'][1] * $P['millable'][1] / 100;
                $stalks = min($raw, $max);
                $stalksLo = min($plantsY * $P['stalks'][0] * $P['millable'][0] / 100, $max);
                $capped = $raw > $max;
                $pieces = $stalks;
                $piecesLo = $stalksLo * $keepShare;
                $kgHa = $stalks * $P['stalkKg'][1];
                $kgHaLo = $stalksLo * $P['stalkKg'][0] * $keepShare;
            } elseif (($Y['unit'] ?? 'kg') === 'count') {
                $pieces = $plantsY * $P['perPlant'][1];
                $piecesLo = $plantsY * $P['perPlant'][0] * $keepShare;
            } else {
                $div = ($Y['unit'] ?? 'kg') === 'g' ? 1000 : 1;
                $kgHa = $plantsY * $P['perPlant'][1] / $div;
                $kgHaLo = $plantsY * $P['perPlant'][0] / $div * $keepShare;
            }
        }

        // The field and the season at growth: the harvest the plants that stand can carry.
        $GK = $pen['growth']['k'];
        foreach (['kgHa', 'kgHaLo', 'pieces', 'piecesLo'] as $v) {
            if ($$v !== null) {
                $$v *= $GK;
            }
        }

        // 6. The flags: [code, level].
        $flags = [['stand-' . $status, ['sparse' => 'bad', 'thin' => 'warn', 'good' => 'good', 'crowded' => 'warn', 'danger' => 'bad'][$status]]];
        $phMax = self::perHillFor($C, $method)[2];
        if ($c['crop'] === 'rice' && $n > self::RICE_HILL_MAX) {
            $flags[] = ['rice-seedlings', $n >= self::RICE_HILL_BAD ? 'bad' : 'warn'];
        } elseif ($method !== 'transplant') {
            if (! self::enough($n, $pCome, $c['keep'], ! empty($C['oneSeed']) ? self::SHORT_ONE : self::SHORT_OK)) {
                $flags[] = ['hill-short', $empty > self::SHORT_BAD ? 'bad' : 'warn'];
            } elseif ($c['keep'] !== null && $n > $sug['n'] + 1) {
                $flags[] = ['hill-extra', 'warn'];
            } elseif ($c['keep'] === null && $n > $phMax) {
                $flags[] = ['hill-crowded', 'warn'];
            } else {
                $flags[] = ['hill-ok', 'good'];
            }
        } elseif ($n > $phMax) {
            $flags[] = ['hill-crowded', 'warn'];
        }
        if ($c['keep'] !== null && $n > $c['keep']) {
            $flags[] = ['hill-thin', 'info'];
        }
        if ($c['layout'] === 'rows') {
            if ($row < $C['row'][0]) {
                $flags[] = ['row-close', 'warn'];
            } elseif ($row > $C['row'][2]) {
                $flags[] = ['row-wide', 'info'];
            }
        }
        if ($plant < $C['plant'][0]) {
            $flags[] = ['plant-close', 'warn'];
        } elseif ($plant > $C['plant'][2]) {
            $flags[] = ['plant-wide', 'info'];
        }
        if ($hasGerm && $C['germ']) {
            if ($c['germ'] < $c['germMin']) {
                $flags[] = ['germ-low', $c['germ'] < $c['germMin'] - 15 ? 'bad' : 'warn'];
            } elseif ($c['germ'] > 98) {
                $flags[] = ['germ-high', 'info'];
            }
        }
        if ($capped) {
            $flags[] = [$Y['model'] === 'stalk' ? 'stalk-cap' : 'tiller-cap', 'warn'];
        }
        if ($plateau) {
            $flags[] = ['yield-plateau', 'info'];
        }

        $r = fn ($v, $d = 2) => $v === null ? null : round($v, $d);
        $lot = fn ($v) => $v === null || ! $areaHa ? null : $v * $areaHa;

        return [
            '_row' => $row, '_plant' => $plant,
            'status' => $status,
            'flags' => $flags,
            'penK' => ['emerge' => round($pen['emerge']['k'], 4), 'seedbed' => round($pen['seedbed']['k'], 4), 'growth' => round($pen['growth']['k'], 4), 'flower' => round($pen['flower'], 4)],
            'variety' => $c['variety'] ?? null, 'varietyYield' => $vy ? true : false, 'basis' => $vy['basis'] ?? null,
            'areaHa' => $r($areaHa, 4),
            'row' => $r($row, 2), 'plant' => $r($plant, 2), 'effRow' => $r($effRow, 2), 'square' => $r($square, 2),
            'plantSpan' => $plantSpan ? [$r($plantSpan[0], 2), $r($plantSpan[1], 2)] : null,
            'given' => $r($given, 1),
            'areaHill' => $r($areaHill, 1),
            'hillsHa' => $r($hillsHa, 1), 'hillsLot' => $r($lot($hillsHa), 1),
            'perHill' => $n, 'suggest' => $sug['n'], 'suggestOk' => $sug['ok'], 'kept' => $k, 'reserve' => $r($res * 100, 2),
            'replant' => $replant ? ['share' => $r($empty, 4), 'after' => $r((1 - $pCome) ** 2, 4), 'seedsHa' => $r($hillsHa * $empty, 1), 'kgHa' => $wts ? $r($hillsHa * $empty * $wts['mid'] / 1000, 3) : null] : null,
            'fieldHa' => $r($fieldHa, 1),
            'buyLo' => $r($needLo, 1), 'buyHi' => $r($needHi, 1),
            'buyLotLo' => $r($lot($needLo), 1), 'buyLotHi' => $r($lot($needHi), 1),
            'kgLo' => $r($kgLo, 3), 'kgHi' => $r($kgHi, 3),
            'kgLotLo' => $r($lot($kgLo), 3), 'kgLotHi' => $r($lot($kgHi), 3),
            // The packs: [low, high] packs a hectare and for the lot, unrounded (the page rounds up to buy), and what one holds
            // (kilos for a pack sold by count, seeds for a weighed one).
            'pack' => $packLo === null ? null : array_diff_key($pk, ['seed' => 1]),
            'packLo' => $r($packLo, 4), 'packHi' => $r($packHi, 4), 'packLotLo' => $r($lot($packLo), 4), 'packLotHi' => $r($lot($packHi), 4),
            'packEach' => $r($packEach, 3),
            'nursery' => $nursery,
            'emptyShare' => $r($empty, 4), 'shortShare' => $r($short, 4), 'keptPerHill' => $r($kept, 4),
            'plantsHa' => $r($plantsHa, 1), 'plantsLot' => $r($lot($plantsHa), 1), 'plantsM2' => $r($plantsHa / 10000, 2),
            'standHa' => $r($standHa, 1), 'standLot' => $r($lot($standHa), 1),
            'goodPlant' => [$r($goodPlant[0], 2), $r($goodPlant[1], 2)],
            'parts' => array_map(fn ($v) => $v === null ? null : [round($v[0], 4), round($v[1], 4)], $P + ['gw' => $gw]),
            'panM2' => $r($panM2, 1), 'panM2Lo' => $r($panM2Lo, 1), 'capped' => $capped, 'plateau' => $plateau,
            // Sugarcane: the millable stalks a hectare the clumps carry (before the field, the season and the harvest loss).
            'stalksHa' => $r($stalks, 1), 'stalksHaLo' => $r($stalksLo, 1),
            'kgHaHi' => $r($kgHa, 1), 'kgHaLo' => $r($kgHaLo, 1),
            'yLotHi' => $r($lot($kgHa), 1), 'yLotLo' => $r($lot($kgHaLo), 1),
            'piecesHa' => $r($pieces, 1), 'piecesHaLo' => $r($piecesLo, 1),
            'piecesLot' => $r($lot($pieces), 1), 'piecesLotLo' => $r($lot($piecesLo), 1),
        ];
    }

    /** A saved calculation's name: "Yellow corn, hybrid · 70 × 20 cm · 19 to 29 kg a ha · Oct 19". */
    public static function title(array $c, array $res, ?string $lot = null): string
    {
        $C = self::crop($c['crop']);
        $num = fn ($v, $d) => str_contains($s = number_format((float) $v, $d), '.') ? rtrim(rtrim($s, '0'), '.') : $s;
        $big = $res['plant'] >= 100 && ($c['layout'] !== 'rows' || $res['row'] >= 100);
        $cm = fn ($v) => $big ? $num($v / 100, 2) : $num($v, $v < 10 ? 1 : 0);
        $u = $big ? ' m' : ' cm';
        $space = $c['layout'] === 'triangle' ? $cm($res['plant']) . $u . ' triangle' : ($c['layout'] === 'beds' ? $cm($res['plant']) . $u . ' on beds' : $cm($res['row']) . ' × ' . $cm($res['plant']) . $u);
        $kg = fn ($v) => $v >= 10 ? number_format($v, 0) : $num($v, $v >= 1 ? 1 : 2);
        $seeds = $res['kgLo'] !== null ? $kg($res['kgLo']) . ' to ' . $kg($res['kgHi']) . ' kg a ha' : number_format($res['buyLo']) . ' a ha';
        // The packs to buy, rounded up: for the lot when it has a size, else a hectare.
        $pk = $res['pack'] ?? null;
        $hiP = $res['areaHa'] ? ($res['packLotHi'] ?? null) : ($res['packHi'] ?? null);
        $bags = $pk && $hiP !== null && $c['mode'] !== 'spacing' ? ' · ' . self::packWords($pk, (int) max(1, ceil($hiP - 1e-9))) : '';
        $name = $C['name'] . (! empty($c['variety']) ? ', ' . $c['variety'] : '');

        return $name . ' · ' . $space . ' · ' . $seeds . $bags . ($lot ? ' · ' . $lot : '') . ' · ' . now('Asia/Manila')->format('M j');
    }

    /** "3 bags of 35,000 kernels", "2 bags of 20 kg certified seed", "4 cans of 25 g". */
    public static function packWords(array $p, int $n): string
    {
        $label = $n === 1 ? $p['label'] : (in_array($p['label'], ['pouch', 'box'], true) ? $p['label'] . 'es' : $p['label'] . 's');
        $size = $p['kind'] === 'seeds' ? number_format($p['size']) . ' ' . $p['word'] : self::packNum($p['size']) . ' ' . $p['kind'];

        return number_format($n) . ' ' . $label . ' of ' . $size . (! empty($p['what']) ? ' ' . $p['what'] : '');
    }

    /** What the page boots with. */
    public static function boot(): array
    {
        return [
            'crops' => self::CROPS, 'groups' => self::GROUPS, 'modes' => self::MODES, 'layouts' => self::LAYOUTS, 'methods' => self::METHODS,
            'rowSet' => self::ROWSET, 'hillMode' => self::HILLMODE, 'seedTypes' => self::SEED_TYPES, 'seedKinds' => self::SEED_KINDS, 'usualKeys' => self::USUAL_KEYS, 'packLabels' => self::PACK_LABELS, 'per' => self::PER, 'areaUnits' => self::AREA_UNITS, 'yieldKeys' => self::YIELD_KEYS, 'stalkExtra' => self::STALK_EXTRA,
            'k' => ['thin' => self::THIN, 'crowd' => self::CROWD, 'shortOk' => self::SHORT_OK, 'shortOne' => self::SHORT_ONE, 'shortPart' => self::SHORT_PART, 'shortBad' => self::SHORT_BAD, 'tri' => self::TRI,
                'bed' => self::BED, 'riceMax' => self::RICE_HILL_MAX, 'riceBad' => self::RICE_HILL_BAD],
            'varietyShelf' => class_exists(\App\Support\VarietyYield::class),
            'pen' => SeedPenalty::boot(),
        ];
    }
}
