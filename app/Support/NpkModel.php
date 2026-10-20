<?php

namespace App\Support;

/**
 * NPK Plus's need model (2026-10-08): how much of each nutrient a crop needs
 * from fertilizer for the farmer's yield goal, on their soil. The arithmetic
 * runs in the page (it answers as the farmer types), and on the server in
 * App\Support\NpkNeed for the Crop Protocol (2026-10-16); this class holds
 * the numbers both run on, in one place, with where they come from.
 *
 * N, P2O5, K2O. The official guide rate for the crop (NpkCrops) is written
 * for a good harvest of about GUIDE_YIELD, or 1.25 times the national
 * average where no source names one. The share of the crop's uptake the
 * guide expects fertilizer to cover is kept, so the need grows with the
 * yield goal. Nitrogen grows in step with it (each ton takes about the same
 * nitrogen): need = guide rate x goal / guide yield. Phosphorus and
 * potassium grow only part way (PK_BASE): part of their guide rate keeps the
 * soil's supply up whatever the harvest, the rest follows the tons the
 * harvest carries away: need = guide rate x (PK_BASE + (1 - PK_BASE) x goal
 * / guide yield). Scaled whole, a 12 t corn goal asked for about 125 kg of
 * P2O5 before the soil, 15 bags of 16-20-0 on alkaline soil; the high yield
 * trials (IPNI, SSNM) put it near 90 to 100 kg (2026-10-08). A crop with no guide
 * counts from its uptake per ton: goal x uptake x (1 - soil share) /
 * recovery. Each is then sized by the soil (ADJUST; two conditions that
 * lose a nutrient the same way, `same`, count once, the larger) and, for
 * trees, by the number of plants. A legume makes its own nitrogen.
 *
 * The soil's own share (SOIL_SHARE) is what omission plots show: a field
 * given no nitrogen still yields about two thirds of its goal, one given
 * no phosphorus or potassium most of it. It makes each plank of the barrel:
 * soil share + (1 - soil share) x the part of the need the plan covers.
 *
 * Secondary, micro and beneficial elements (ELEMENTS) are needed from a bag
 * only where a shortfall is likely: a score from the crop's sensitivity, the
 * soil type, the pH and the soil's conditions decides none, watch (half the
 * usual corrective dose) or likely (the dose); a soil test overrides it.
 */
final class NpkModel
{
    public const TEXTURES = [
        'sandy' => ['Sandy (light)', 'Gritty, drains fast and dries quickly'],
        'loam' => ['Loam (medium)', 'Crumbly, holds water and still drains'],
        'clay' => ['Clay (heavy)', 'Sticky when wet, cracks when dry'],
        'unsure' => ['Not sure', 'NPK Plus counts it as a loam'],
    ];

    /** Beyond App\Support\SoilConditions, for this calculator only. */
    public const MORE_CONDITIONS = [
        'low_om' => ['Low organic matter', 'Pale, hard soil that crusts; little manure or straw goes back'],
        'peat' => ['Peat or muck', 'Very dark, spongy, light when dry'],
        'waterlogged' => ['Waterlogged', 'Water sits for days after rain (not counting a flooded rice paddy)'],
    ];

    /**
     * Omission plots: the share of the yield goal a field reaches with none of
     * that nutrient. Rice and corn from the trials (irrigated rice in
     * Indonesia and across Asia, PhilRice's soil supply figures; corn from
     * China and Southeast Asia); other crops a little lower in nitrogen,
     * since vegetables answer it more.
     */
    public const SOIL_SHARE = ['N' => 0.6, 'P2O5' => 0.85, 'K2O' => 0.85];

    public const SOIL_SHARE_CROP = [
        'rice' => ['N' => 0.7, 'P2O5' => 0.92, 'K2O' => 0.92], 'rice_dsr_wet' => ['N' => 0.7, 'P2O5' => 0.92, 'K2O' => 0.92],
        'rice_dsr_dry' => ['N' => 0.7, 'P2O5' => 0.85, 'K2O' => 0.82], 'corn_yellow' => ['N' => 0.7, 'P2O5' => 0.88, 'K2O' => 0.88],
        'corn_sweet' => ['N' => 0.7, 'P2O5' => 0.88, 'K2O' => 0.88],
    ];

    /** Recovery in the first season where the crop's source gives none. */
    public const RECOVERY = ['N' => 0.4, 'P2O5' => 0.2, 'K2O' => 0.5];

    /** The yield a crop's guide rate is written for, where its source says. */
    public const GUIDE_YIELD = ['corn_yellow' => 6.8];

    /**
     * How much a fertilizer acidifies the soil, in kg of lime (CaCO3) it
     * takes to undo it (2026-10-12): per kg of N for the ammonium and urea
     * fertilizers (the classic equivalent acidity: ammonium sulfate 5.35,
     * monoammonium phosphate 5.0, diammonium phosphate 3.9, urea 1.8; the
     * nitrates are basic), per kg of S for elemental sulfur (3.1), per kg of
     * product for lime (-0.9) and dolomite (-1.0). A product not named here
     * counts as urea for its nitrogen. Sources: Havlin, Tisdale, Beaton and
     * Nelson, Soil Fertility and Fertilizers (acidity of N sources); IPNI and
     * IFA fertilizer manuals.
     *
     * ACID_AT is the plan's acidity per hectare at which the effect is full;
     * below it the effect scales down. On alkaline, limy or sodic soil the
     * acidity frees up to ACID_UNLOCK of the phosphorus lock and lowers the
     * zinc, iron, manganese and copper scores (and calcium's on sodic soil)
     * by up to one point; on acid soil it adds to the phosphorus lock.
     */
    public const ACIDITY = [
        'perN' => ['ammonium-sulfate' => 5.35, 'map' => 5.0, 'ammonium-phosphate-s' => 5.0, 'ammonium-phosphate' => 4.4, 'dap' => 3.9,
            'complete-14' => 3.0, 'complete-15' => 3.0, 'complete-16' => 3.0, 'npk-17-0-17' => 3.0, 'complete-16-16-8-s' => 4.0, 'urea' => 1.8,
            'calcium-nitrate' => -1.35, 'potassium-nitrate' => -2.0],
        'perS' => ['sulfur' => 3.1],
        'perKg' => ['lime' => -0.9, 'dolomite' => -1.0],
        'defaultPerN' => 1.8,
    ];

    public const ACID_AT = 400;

    public const ACID_UNLOCK = 0.5;

    /** The share of a phosphorus or potassium guide rate that does not follow the yield goal. */
    public const PK_BASE = 0.5;

    /**
     * The variety's potential when the farmer gives none: this many times the
     * guide's good harvest. The season's planks are what the sun, the water
     * and the heat let that potential carry, in tons, so they do not move with
     * the goal (2026-10-08).
     */
    public const POTENTIAL_X = 1.6;

    /**
     * How the soil sizes the N, P2O5 and K2O need (multipliers), and the
     * soil's own share (share: multipliers on SOIL_SHARE). Kept within 0.5 to
     * 1.8 when several apply.
     */
    public const ADJUST = [
        'sandy' => ['N' => 1.15, 'K2O' => 1.2, 'share' => ['N' => 0.9, 'K2O' => 0.9], 'why' => 'Sandy soil lets nitrogen and potassium wash down'],
        'clay' => ['K2O' => 0.9, 'why' => 'Clay holds potassium'],
        'strongAcid' => ['P2O5' => 1.3, 'same' => ['P2O5' => 'pfix'], 'why' => 'Very acid soil (pH under 5) locks up phosphorus'],
        'acid' => ['P2O5' => 1.2, 'same' => ['P2O5' => 'pfix'], 'why' => 'Acid soil locks up phosphorus'],
        // A high pH locks phosphorus up as calcium phosphate in a limy soil; in a
        // sodic one the high pH is sodium, and sodium phosphate stays soluble,
        // so there (unless sodic) only the nitrogen lost to the air counts.
        'alkaline' => ['P2O5' => 1.15, 'N' => 1.05, 'same' => ['P2O5' => 'pfix', 'N' => 'nair'], 'unless' => ['P2O5' => 'sodic'], 'why' => 'Alkaline soil locks up phosphorus and loses some urea nitrogen to the air',
            'whyUnless' => 'Alkaline soil loses some urea nitrogen to the air'],
        'strongAlk' => ['P2O5' => 1.25, 'N' => 1.1, 'same' => ['P2O5' => 'pfix', 'N' => 'nair'], 'unless' => ['P2O5' => 'sodic'], 'why' => 'Strongly alkaline soil (pH over 8) locks up phosphorus and loses urea nitrogen to the air',
            'whyUnless' => 'Strongly alkaline soil (pH over 8) loses urea nitrogen to the air; its sodium keeps phosphorus soluble, so phosphorus is not raised'],
        'sodic' => ['N' => 1.1, 'K2O' => 1.1, 'same' => ['N' => 'nair'], 'why' => 'Sodic soil loses nitrogen to the air, and sodium crowds out potassium'],
        'saline' => ['K2O' => 1.15, 'N' => 1.05, 'why' => 'On salty soil potassium helps the crop keep the salt out'],
        'acid_sulfate' => ['P2O5' => 1.3, 'same' => ['P2O5' => 'pfix'], 'why' => 'Acid sulfate soil locks up phosphorus'],
        // Low organic matter and a low nitrogen test are one shortfall (a test reads the soil's own N supply,
        // and the soil atlas rates N by organic matter): they count once, the larger (2026-10-19).
        'low_om' => ['N' => 1.15, 'same' => ['N' => 'nsupply'], 'share' => ['N' => 0.9], 'why' => 'Low organic matter gives less nitrogen of its own'],
        'peat' => ['N' => 0.85, 'share' => ['N' => 1.1], 'why' => 'Peat gives more nitrogen of its own'],
        'waterlogged' => ['N' => 1.1, 'why' => 'Waterlogged upland soil loses nitrogen'],
        'testLow' => ['N' => 1.2, 'P2O5' => 1.3, 'K2O' => 1.3, 'same' => ['N' => 'nsupply'], 'share' => ['N' => 0.85, 'P2O5' => 0.85, 'K2O' => 0.85], 'why' => 'Your soil test reads low'],
        'testHigh' => ['N' => 0.8, 'P2O5' => 0.6, 'K2O' => 0.6, 'share' => ['N' => 1.1, 'P2O5' => 1.12, 'K2O' => 1.12], 'why' => 'Your soil test reads high'],
    ];

    /**
     * The usual seeding rate or stand per hectare: w = kg of seed or planting
     * material, n = plants (or hills, cuttings, trees), from Philippine
     * guides (DA, PhilRice, BPI, ATI, PhilMech, VSU, CMU) researched on
     * 2026-10-08; sweet corn and the dry seeded rice width lean on a seed
     * company guide and IRRI. GENERATED from the scratchpad's
     * npk/seeding.json by gen_seeding.py. Since 2026-10-17 the notes say each
     * rate as our own and the sources keep no government body, state
     * university or research institute (the owner; GovSource): a rerun of
     * the generator must do the same.
     */
    public const SEEDING = [
        'rice' => ['w' => [18, 40], 'n' => null, 'countWord' => null, 'note' => 'Use 20 to 40 kg of certified inbred seed per hectare for transplanting (40 kg is the standard), and hybrid seed needs about 18 to 20 kg since its 400 square meter seedbed is sown at 50 g per square meter.',
            'sources' => []],
        'rice_dsr_wet' => ['w' => [40, 80], 'n' => null, 'countWord' => null, 'note' => 'Use 60 to 80 kg per hectare for wet direct seeding by broadcast and about 40 to 60 kg when seeds go in rows with a drum seeder.',
            'sources' => []],
        'rice_dsr_dry' => ['w' => [40, 80], 'n' => null, 'countWord' => null, 'note' => 'Direct seeded rice takes 60 to 80 kg per hectare, and dry seeding takes 40 to 45 kg by seed drill or 50 to 60 kg broadcast.',
            'sources' => []],
        'corn_yellow' => ['w' => [18, 24], 'n' => [60000, 91000], 'countWord' => 'plants', 'note' => 'One 18 kg bag of hybrid seed covers a hectare, and 22 to 24 kg are needed at 70 cm rows and 18 to 20 cm hills for at least 75,000 plants, with double row planting reaching about 91,000.',
            'sources' => []],
        'corn_sweet' => ['w' => [8, 15], 'n' => [50000, 66667], 'countWord' => 'plants', 'note' => 'No Philippine guide states a sweet corn rate, so this follows a seed company guideline of 55,000 to 60,000 plants per hectare from roughly 8 to 15 kg depending on seed size, which matches 75 cm rows at 20 to 25 cm.',
            'sources' => [['label' => 'Starke Ayres, Sweetcorn production guideline (South Africa, not Philippine)', 'url' => 'https://www.starkeayres.com/uploads/files/Sweetcorn-Production-Guideline-2019.pdf']]],
        'sorghum' => ['w' => [8, 10], 'n' => null, 'countWord' => null, 'note' => 'A sweet sorghum guide for the Philippines lists 8 kg of seed per hectare, and sweet sorghum practice in India goes to about 10 kg.',
            'sources' => []],
        'mungbean' => ['w' => [18, 20], 'n' => null, 'countWord' => null, 'note' => 'Drill 18 to 20 kg of mungbean seed per hectare in rows 50 to 60 cm apart.',
            'sources' => []],
        'peanut' => ['w' => [90, 100], 'n' => null, 'countWord' => null, 'note' => 'A hectare needs 90 to 100 kg of shelled peanut seed, which is about 150 kg of unshelled pods, in furrows 50 to 60 cm apart.',
            'sources' => []],
        'soybean' => ['w' => [40, 60], 'n' => null, 'countWord' => null, 'note' => 'Soybean needs 40 to 60 kg of seed per hectare drilled at 18 to 20 seeds per meter in furrows 60 cm apart, or only about 20 kg for hill planting.',
            'sources' => []],
        'stringbean' => ['w' => [10, 20], 'n' => [40000, 53333], 'countWord' => 'plants', 'note' => 'Use 10 to 20 kg of seed per hectare for pole sitaw at 100 by 50 cm (about 40,000 plants) and 25 to 30 kg for bush sitaw at 75 by 25 cm (about 53,333 plants).',
            'sources' => []],
        'sweetpotato' => ['w' => null, 'n' => [33000, 44000], 'countWord' => 'cuttings', 'note' => 'Plant one cutting per hill at 25 to 30 cm in rows 75 to 100 cm apart, giving 33,000 to 44,000 cuttings per hectare, and 75 by 25 cm (53,333) works well for NSIC Sp30.',
            'sources' => []],
        'cassava' => ['w' => null, 'n' => [10000, 15000], 'countWord' => 'cuttings', 'note' => 'Cassava is usually planted at 1 by 1 m (10,000 cuttings per hectare), and Philippine agronomy trials found yields rise at closer stands of 15,000 or more.',
            'sources' => [['label' => 'CIAT, Recent progress in cassava agronomy research in the Philippines', 'url' => 'https://alliancebioversityciat.org/publications-data/recent-progress-cassava-agronomy-research-philippines']]],
        'taro' => ['w' => null, 'n' => [26667, 40000], 'countWord' => 'setts', 'note' => 'Plant gabi setts of 100 to 120 g at 75 by 50 cm or 50 by 50 cm, which works out to 26,667 to 40,000 setts per hectare.',
            'sources' => []],
        'potato' => ['w' => [1200, 2000], 'n' => null, 'countWord' => null, 'note' => 'White potato needs 1,200 to 2,000 kg of 30 to 40 g seed tubers per hectare at 75 by 30 cm, and about 2,500 kg when growing seed potatoes.',
            'sources' => []],
        'carrot' => ['w' => [3, 10], 'n' => null, 'countWord' => null, 'note' => 'Sow 3 to 4 kg of carrot seed per hectare when drilled thin at 25 by 10 cm and up to 5 to 10 kg in denser bed sowing.',
            'sources' => []],
        'ginger' => ['w' => [800, 3666], 'n' => null, 'countWord' => null, 'note' => 'A hectare needs 800 to 1,500 kg of seed pieces in the highlands and 2,444 to 3,666 kg in the lowlands (about 2,000 kg under coconut), so the amount depends mostly on seed piece size.',
            'sources' => []],
        'pechay' => ['w' => [0.8, 3], 'n' => null, 'countWord' => null, 'note' => 'Pechay needs about 3 kg of seed per hectare when direct sown and about 1 kg when raised in a seedbed and transplanted at 20 by 10 cm.',
            'sources' => []],
        'cabbage' => ['w' => [0.2, 0.5], 'n' => [40000, 50000], 'countWord' => 'plants', 'note' => 'Transplant cabbage at 50 by 40 cm (50,000 plants per hectare) or 60 by 40 cm (about 41,667) from 200 to 500 g of seed.',
            'sources' => []],
        'lettuce' => ['w' => [0.15, 0.2], 'n' => [28600, 106700], 'countWord' => 'plants', 'note' => 'Guides use 150 to 200 g of seed per hectare, and the stated spacings (heading types 30 to 40 cm in 2 rows per bed, leaf types 20 to 25 cm square on 1 m beds) work out to about 28,600 to 106,700 plants per hectare once paths are counted.',
            'sources' => []],
        'broccoli' => ['w' => null, 'n' => [38000, 44000], 'countWord' => 'plants', 'note' => 'Set broccoli at 35 by 35 cm in double rows per plot, which is about 38,000 to 44,000 plants per hectare with 30 to 50 cm paths, from about 280 g of seed per hectare.',
            'sources' => []],
        'tomato' => ['w' => null, 'n' => [15000, 20000], 'countWord' => 'plants', 'note' => 'Transplant tomato at 50 cm hills in rows about 1 m apart (20,000 plants per hectare), and the usual stand is 15,000 to 17,000 from 150 to 250 g of seed.',
            'sources' => []],
        'eggplant' => ['w' => null, 'n' => [13333, 26667], 'countWord' => 'plants', 'note' => 'Plant eggplant 50 cm apart in rows 75 cm to 1 m apart (20,000 to 26,667 plants per hectare), widening to 75 cm in the wet season (about 13,333), from 100 to 250 g of seed.',
            'sources' => []],
        'ampalaya' => ['w' => [2, 3], 'n' => [6000, 16667], 'countWord' => 'hills', 'note' => 'Guides use 2 to 3 kg of ampalaya seed per hectare, giving about 6,000 to 11,000 hills when direct seeded and up to 16,667 at 2 m rows with 30 cm hills.',
            'sources' => []],
        'squash' => ['w' => [2, 2.5], 'n' => [2500, 5000], 'countWord' => 'hills', 'note' => 'Plant squash 1 to 2 m apart in rows 2 to 3 m apart, about 2,500 to 5,000 hills per hectare, from 2 to 2.5 kg of seed.',
            'sources' => []],
        'cucumber' => ['w' => [1.5, 2], 'n' => [33333, 44444], 'countWord' => 'hills', 'note' => 'Sow 2 to 3 seeds per hill 30 cm apart in furrows 75 cm to 1 m apart (33,333 to 44,444 hills per hectare) using about 1.5 to 2 kg of seed.',
            'sources' => []],
        'okra' => ['w' => [3, 10], 'n' => [33333, 50000], 'countWord' => 'hills', 'note' => 'Sow okra at 20 to 30 cm hills in rows 75 cm to 1.5 m apart (about 33,333 to 50,000 hills per hectare, thinned to 2 plants) using 3 to 10 kg of seed.',
            'sources' => []],
        'chili' => ['w' => null, 'n' => [17778, 41667], 'countWord' => 'plants', 'note' => 'Set hot pepper at 50 to 75 cm square (17,778 to 40,000 plants per hectare) or 30 cm hills in 80 cm furrows (41,667), from about 100 to 200 g of seed.',
            'sources' => []],
        'bellpepper' => ['w' => null, 'n' => [26667, 44444], 'countWord' => 'plants', 'note' => 'Set bell pepper 30 to 50 cm apart in rows 60 to 75 cm apart (about 26,667 to 44,444 plants per hectare), with the closest stated spacing reaching 66,667, from 100 to 300 g of seed.',
            'sources' => []],
        'watermelon' => ['w' => null, 'n' => [1600, 4444], 'countWord' => 'hills', 'note' => 'Place watermelon hills 1.5 by 1.5 m to 2.5 by 2.5 m apart depending on variety, which is 1,600 to 4,444 hills per hectare thinned to one plant each.',
            'sources' => []],
        'onion' => ['w' => [3, 4.5], 'n' => [400000, 800000], 'countWord' => 'seedlings', 'note' => 'Transplanted bulb onion uses 3 to 4.5 kg of seed per hectare in the seedbed, and beds of 1 m with 6 to 8 rows at 7.5 to 10 cm work out to about 400,000 to 800,000 seedlings per hectare once paths are counted.',
            'sources' => []],
        'garlic' => ['w' => [400, 700], 'n' => null, 'countWord' => null, 'note' => 'A hectare needs about 400 to 700 kg of garlic cloves depending on bulb size and spacing, though some cost budgets use up to 1,000 kg.',
            'sources' => []],
        'sugarcane' => ['w' => null, 'n' => [30000, 40000], 'countWord' => 'setts', 'note' => 'Philippine practice uses about 30,000 to 40,000 two or three budded setts (3 to 4 lacsas) per hectare.',
            'sources' => []],
        'pineapple' => ['w' => null, 'n' => [40000, 60000], 'countWord' => 'suckers', 'note' => 'A hectare of pineapple takes 40,000 to 60,000 suckers, slips or crowns, with double rows giving the higher stands.',
            'sources' => []],
        'banana' => ['w' => null, 'n' => [400, 2200], 'countWord' => 'plants', 'note' => 'Space Saba or Cardaba at 4 by 4 m to 5 by 5 m (400 to 625 plants per hectare), Lakatan at 3 by 3 m to 2 by 2.5 m (1,111 to 2,000), and Cavendish plantations run about 2,200.',
            'sources' => []],
        'papaya' => ['w' => null, 'n' => [1333, 2500], 'countWord' => 'plants', 'note' => 'Guides space papaya 2 to 2.5 m apart in rows 2.5 to 3 m apart (about 1,333 to 2,000 plants per hectare), Solo plantations use about 2,067, and closer 2 by 2 m planting reaches 2,500.',
            'sources' => []],
        'mango' => ['w' => null, 'n' => [69, 100], 'countWord' => 'trees', 'note' => 'Plant mango 10 by 10 m (100 trees per hectare) to 12 by 12 m (69 trees), and some Mindanao farms go as wide as 20 m.',
            'sources' => []],
        'coconut' => ['w' => null, 'n' => [100, 180], 'countWord' => 'palms', 'note' => 'Triangular planting gives 143 palms per hectare for talls at 9 m, about 160 for hybrids at 8.5 m and 180 for dwarfs at 8 m, while older square planting at 10 m holds about 100.',
            'sources' => []],
        'calamansi' => ['w' => null, 'n' => [400, 625], 'countWord' => 'trees', 'note' => 'The usual calamansi spacing is 5 m between plants (400 trees per hectare), and closer 4 by 4 m planting holds 625.',
            'sources' => [['label' => 'GSU Himal us journal, calamansi study at 4 by 4 m', 'url' => 'https://journals.gsu.edu.ph/himal-us/article/download/42/40']]],
        'coffee' => ['w' => null, 'n' => [1111, 3333], 'countWord' => 'trees', 'note' => 'Space Arabica 3 by 1 m to 3 by 2 m and Robusta 3 by 1.5 m to 3 by 3 m (about 1,111 to 3,333 trees per hectare), while Liberica and Excelsa go 4 by 5 m to 5 by 5.5 m (about 364 to 500).',
            'sources' => []],
        'cacao' => ['w' => null, 'n' => [600, 1111], 'countWord' => 'trees', 'note' => 'A usual budget is 1,100 cacao trees per hectare as a monocrop (3 by 3 m) and 600 as an intercrop under coconut.',
            'sources' => []],
    ];

    /**
     * How sure a shortfall is, from the risk score: from 'at' up it is that
     * level. dose = the share of the element's corrective dose the field
     * needs; share = the share of the yield goal the field reaches with none
     * of it (a likely zinc shortfall costs rice about a fifth of its harvest,
     * one a soil test confirms about a third).
     */
    public const MICRO_LEVELS = [
        'watch' => ['at' => 1, 'dose' => 0.5, 'share' => 0.92],
        'likely' => ['at' => 2.5, 'dose' => 1, 'share' => 0.8],
        'test' => ['dose' => 1.2, 'share' => 0.65],
    ];

    /**
     * What the soil gives of a secondary or micro element on its own
     * (2026-10-21, the owner: "analyze the soil type and location if it
     * already by default has micronutrients ... and if it is locked or not";
     * nothing should start from zero). The share of the crop's need the soil
     * meets before any bag: each point of the element's score (its weights:
     * the soil type, the pH, the soil's conditions, the area's soils, the
     * plan's own lockouts, the crop's hunger for it) takes SOIL_STEP off a
     * full share, never under SOIL_FLOOR, so the levels keep their shares
     * (a score of 1, "may run short", leaves 92%; 2.5, "likely short", 80%).
     * Nothing flagged leaves at least SOIL_NONE; a soil test that reads low
     * leaves the test level's share at most, one that reads enough all of
     * it. The Micros tab and the barrel's planks start from it, never from 0.
     */
    public const SOIL_STEP = 0.08;

    public const SOIL_FLOOR = 0.5;

    public const SOIL_NONE = 0.97;

    /**
     * Why the soil gives less, as a farmer reads it: the soil holds little
     * of it (low), holds some but locks it up (lock), or the season takes it
     * away (loss, said as a whole clause). SOIL_FACTOR_EL: an element that
     * reads a factor its own way (acid soil holds molybdenum but locks it
     * up; peat binds copper and manganese).
     */
    public const SOIL_FACTOR = [
        'sandy' => ['low', 'sandy soil'], 'peat' => ['low', 'peat soil'], 'low_om' => ['low', 'low organic matter'],
        'acid' => ['low', 'acid soil'], 'strongAcid' => ['low', 'very acid soil'], 'acid_sulfate' => ['low', 'acid sulfate soil'],
        'area_zn_low' => ['low', 'the soil around here'], 'area_zn_mlow' => ['low', 'the soil around here'],
        'alkaline' => ['lock', 'alkaline soil'], 'strongAlk' => ['lock', 'strongly alkaline soil'], 'calcareous' => ['lock', 'limy soil'],
        'sodic' => ['lock', 'the sodium in the soil'], 'flooded' => ['lock', 'the flooding'], 'waterlogged' => ['lock', 'wet soil'],
        'drought' => ['lock', 'a dry season'], 'upland' => ['lock', 'soil that is never flooded'],
        'high_p' => ['lock', 'the heavy phosphorus in the plan'], 'high_k' => ['lock', 'the heavy potassium in the plan'],
        'liming' => ['lock', 'the lime in the plan'], 'excess_zn' => ['lock', 'the heavy zinc in the plan'],
        'high_rainfall' => ['loss', 'heavy rain washes it out'], 'intensive_cropping' => ['loss', 'two or three crops a year draw it down'],
    ];

    public const SOIL_FACTOR_EL = [
        'Mo' => ['acid' => 'lock', 'strongAcid' => 'lock', 'acid_sulfate' => 'lock'],
        'Cu' => ['peat' => 'lock'], 'Mn' => ['peat' => 'lock'],
    ];

    /**
     * Secondary, micro and beneficial elements. base = the corrective dose,
     * kg of the element per hectare; weights = what raises the risk of a
     * shortfall; sensitive = crops that need more or show it first.
     * 2026-10-19: the researched factors the first build left out are in
     * (high_rainfall, drought, intensive_cropping, organic_inputs, upland,
     * neutral, loam), with zinc_recent (a zinc dose lasts two or three
     * crops) and the DTPA critical levels for Fe, Mn and Cu. Hand edited:
     * a rerun of gen_elements.py must carry these and keep the sources the
     * 2026-10-17 sweep removed out.
     */
    public const ELEMENTS = [
        'Ca' => ['base' => 100, 'rate' => [50, 250], 'weights' => ['acid' => 1, 'strongAcid' => 1.5, 'sandy' => 0.5, 'sodic' => 1, 'peat' => 0.5, 'high_k' => 0.5, 'calcareous' => -1, 'liming' => -1, 'high_rainfall' => 0.5, 'drought' => 0.5, 'neutral' => -0.5], 'sensitive' => ['peanut', 'tomato', 'bellpepper', 'chili', 'watermelon', 'cabbage', 'carrot', 'banana', 'mango'], 'over' => 8, 'fert' => 'calcium sulfate (gypsum, dihydrate or anhydrous), or agricultural lime on acid soil', 'toxic' => 'Too much lime pushes the pH up and locks away zinc, iron, manganese, copper and boron.', 'down' => ['calcareous' => 'limy (calcareous) soil holds plenty of calcium', 'liming' => 'the lime in the plan adds calcium', 'neutral' => 'a near neutral pH keeps calcium within reach'],
            'sources' => [['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'Rajendrudu and Williams 1987, Effect of gypsum and drought on pod initiation and crop yield in early maturing groundnut (ICRISAT, Experimental Agriculture 23)', 'url' => 'https://oar.icrisat.org/3423/1/JA_384.pdf']]],
        'Mg' => ['base' => 20, 'rate' => [10, 50], 'weights' => ['acid' => 1, 'strongAcid' => 1.5, 'sandy' => 1, 'high_k' => 1, 'peat' => 0.5, 'waterlogged' => 0.25, 'flooded' => 0.25, 'calcareous' => -0.5, 'high_rainfall' => 0.5, 'neutral' => -0.5], 'sensitive' => ['banana', 'sugarcane', 'pineapple', 'cassava', 'corn_yellow', 'corn_sweet', 'potato', 'coffee', 'cacao', 'coconut', 'calamansi', 'tomato', 'bellpepper', 'chili', 'cabbage', 'broccoli', 'cucumber', 'watermelon', 'onion', 'stringbean', 'carrot'], 'over' => 6, 'fert' => 'dolomite on acid soil, or magnesium sulfate', 'toxic' => 'Too much magnesium crowds out potassium and calcium.', 'down' => ['calcareous' => 'limy soil carries magnesium', 'neutral' => 'a near neutral pH keeps magnesium within reach'],
            'sources' => [['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'S' => ['base' => 17.5, 'rate' => [10, 40], 'weights' => ['low_om' => 1, 'sandy' => 1, 'calcareous' => 0.25, 'acid_sulfate' => -1, 'saline' => -0.25, 'intensive_cropping' => 1], 'sensitive' => ['cabbage', 'broccoli', 'pechay', 'onion', 'garlic', 'mungbean', 'peanut', 'soybean', 'stringbean', 'rice', 'rice_dsr_wet', 'rice_dsr_dry', 'coconut'], 'critical' => 10, 'over' => 6, 'fert' => 'ammonium sulfate, calcium sulfate (gypsum) or single superphosphate', 'down' => ['acid_sulfate' => 'acid sulfate soil holds too much sulfur, not too little', 'saline' => 'salty soil often carries sulfate'],
            'sources' => [['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'Zn' => ['base' => 5, 'rate' => [5, 10], 'weights' => ['alkaline' => 1, 'strongAlk' => 1.5, 'calcareous' => 1, 'sodic' => 1, 'waterlogged' => 1, 'flooded' => 1, 'high_p' => 0.5, 'low_om' => 0.5, 'sandy' => 0.5, 'peat' => 0.5, 'liming' => 0.5, 'acid' => -0.5, 'strongAcid' => -0.5, 'intensive_cropping' => 0.5, 'organic_inputs' => -0.25, 'zinc_recent' => -2, 'area_zn_low' => 1.5, 'area_zn_mlow' => 1, 'area_zn_ok' => -1], 'sensitive' => ['rice', 'rice_dsr_wet', 'rice_dsr_dry', 'corn_yellow', 'corn_sweet', 'sorghum', 'stringbean', 'mungbean', 'onion', 'cassava', 'ginger', 'watermelon', 'pineapple', 'banana', 'mango', 'calamansi', 'coffee', 'cacao', 'coconut'], 'critical' => 0.8, 'over' => 5, 'fert' => 'zinc sulfate', 'down' => ['acid' => 'acid soil frees zinc', 'strongAcid' => 'acid soil frees zinc', 'organic_inputs' => 'manure or compost in the plan brings zinc', 'zinc_recent' => 'the zinc put on in the last two seasons still works', 'area_zn_ok' => 'soils around here usually test fine in zinc'],
            'sources' => [['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf']]],
        'B' => ['base' => 0.875, 'rate' => [0.5, 2], 'weights' => ['sandy' => 1, 'alkaline' => 0.5, 'strongAlk' => 1, 'liming' => 0.5, 'calcareous' => 0.5, 'peat' => 0.5, 'low_om' => 0.5, 'saline' => -1, 'sodic' => -1, 'clay' => -0.5, 'high_rainfall' => 1, 'drought' => 0.5], 'sensitive' => ['broccoli', 'cabbage', 'pechay', 'carrot', 'tomato', 'papaya', 'pineapple', 'sweetpotato', 'coconut', 'coffee', 'cacao', 'mango', 'banana', 'watermelon', 'calamansi'], 'sensitiveWeight' => 1.5, 'critical' => 0.5, 'over' => 2, 'fert' => 'borax or boric acid', 'toxic' => 'Boron burns crops just above the dose they need, and beans, cucumber and soybean are very sensitive: keep to the need.', 'down' => ['sodic' => 'sodic and salty soils usually hold plenty of boron, often too much', 'saline' => 'sodic and salty soils usually hold plenty of boron, often too much', 'clay' => 'clay holds more boron than sandy soil'],
            'sources' => [['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'Fe' => ['base' => 1.125, 'rate' => [0.5, 3], 'weights' => ['alkaline' => 1, 'strongAlk' => 1.5, 'calcareous' => 1, 'sandy' => 0.5, 'low_om' => 0.5, 'high_p' => 0.5, 'liming' => 0.5, 'waterlogged' => -1, 'flooded' => -1, 'acid' => -1, 'strongAcid' => -1, 'acid_sulfate' => -1, 'upland' => 1, 'drought' => 0.25, 'organic_inputs' => -0.5], 'critical' => 4.5, 'sensitive' => ['sorghum', 'soybean', 'stringbean', 'mungbean', 'rice', 'rice_dsr_wet', 'rice_dsr_dry', 'tomato', 'broccoli', 'cucumber', 'watermelon', 'pineapple', 'calamansi', 'coffee', 'banana'], 'over' => 5, 'fert' => 'ferrous sulfate as a foliar spray, or an iron chelate', 'down' => ['flooded' => 'a flooded paddy frees plenty of iron', 'waterlogged' => 'wet soil frees plenty of iron', 'acid' => 'acid soil frees iron', 'strongAcid' => 'acid soil frees iron', 'acid_sulfate' => 'acid sulfate soil frees too much iron', 'organic_inputs' => 'manure or compost frees iron'],
            'sources' => [['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf']]],
        'Mn' => ['base' => 2.75, 'rate' => [1, 8], 'weights' => ['alkaline' => 1, 'strongAlk' => 1.5, 'liming' => 1, 'peat' => 1, 'sodic' => 0.5, 'calcareous' => 0.5, 'sandy' => 0.5, 'acid' => -1, 'strongAcid' => -1, 'waterlogged' => -1, 'flooded' => -1, 'drought' => 0.25], 'critical' => 1, 'sensitive' => ['soybean', 'stringbean', 'sorghum', 'cucumber', 'lettuce', 'onion', 'potato', 'calamansi', 'coffee'], 'over' => 5, 'fert' => 'manganese sulfate', 'toxic' => 'On acid soil extra manganese turns toxic.', 'down' => ['flooded' => 'a flooded paddy frees plenty of manganese', 'waterlogged' => 'wet soil frees plenty of manganese', 'acid' => 'acid soil frees manganese', 'strongAcid' => 'acid soil frees manganese'],
            'sources' => [['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf']]],
        'Cu' => ['base' => 2.625, 'rate' => [1.5, 6], 'weights' => ['peat' => 1, 'sandy' => 0.5, 'calcareous' => 0.5, 'liming' => 0.5, 'excess_zn' => 0.5, 'intensive_cropping' => 0.25, 'loam' => -0.5], 'critical' => 0.2, 'down' => ['loam' => 'most loam soils hold enough copper'], 'sensitive' => ['onion', 'lettuce', 'tomato', 'carrot', 'cucumber', 'watermelon', 'pineapple', 'calamansi', 'coconut'], 'over' => 3, 'fert' => 'copper sulfate', 'toxic' => 'Copper does not wash out of the soil, so extra builds up and harms roots.',
            'sources' => [['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'Mo' => ['base' => 0.0563, 'rate' => [0.025, 0.15], 'weights' => ['acid' => 1, 'strongAcid' => 1.5, 'sandy' => 0.5, 'peat' => 1, 'acid_sulfate' => 0.25, 'alkaline' => -1, 'strongAlk' => -1, 'liming' => -1, 'neutral' => -1], 'sensitive' => ['soybean', 'mungbean', 'peanut', 'stringbean', 'broccoli', 'cabbage', 'pechay', 'lettuce', 'onion', 'tomato'], 'sensitiveWeight' => 1.5, 'over' => 3, 'fert' => 'sodium or ammonium molybdate, as a seed treatment', 'toxic' => 'Crops take extra molybdenum, but forage high in it harms cattle.', 'down' => ['alkaline' => 'alkaline soil frees molybdenum', 'strongAlk' => 'alkaline soil frees molybdenum', 'liming' => 'lime frees molybdenum', 'neutral' => 'a near neutral pH frees molybdenum'],
            'sources' => [['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'Embrapa Soja Documentos 322, Soja: Molibdenio e Cobalto (Sfredo and Oliveira 2010)', 'url' => 'https://www.infoteca.cnptia.embrapa.br/bitstream/doc/859439/1/Doc322online1.pdf']]],
        'Si' => ['base' => 150, 'rate' => [150, 600], 'weights' => ['peat' => 1, 'intensive_cropping' => 1, 'neutral' => -0.5], 'down' => ['neutral' => 'lowland soils near neutral usually hold plenty of silicon'], 'sensitive' => ['rice', 'rice_dsr_wet', 'rice_dsr_dry', 'sugarcane'], 'sensitiveWeight' => 1, 'beneficial' => true, 'over' => 6, 'fert' => 'rice straw or rice hull ash put back, or calcium silicate',
            'sources' => [['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'Kono, Effectiveness of silicate fertilizer to japonica varieties (JIRCAS Tropical Agriculture Research Series 3)', 'url' => 'https://www.jircas.go.jp/sites/default/files/publication/tars/tars3-_241-247.pdf']]],
        'Co' => ['base' => 0.0027, 'rate' => [0.002, 0.005], 'weights' => ['acid' => 0.25, 'strongAcid' => 0.75, 'loam' => -0.5], 'down' => ['loam' => 'loam soils usually hold enough cobalt'], 'sensitive' => ['mungbean', 'peanut', 'soybean', 'stringbean'], 'sensitiveWeight' => 2, 'beneficial' => true, 'over' => 2, 'fert' => 'cobalt sulfate, as a seed treatment', 'toxic' => 'Too much cobalt blocks iron and yellows the crop: it is needed in grams.',
            'sources' => [['label' => 'Embrapa Soja Documentos 322, Soja: Molibdenio e Cobalto (Sfredo and Oliveira 2010)', 'url' => 'https://www.infoteca.cnptia.embrapa.br/bitstream/doc/859439/1/Doc322online1.pdf'], ['label' => 'Lana et al. 2009, Cobalt and molybdenum concentrated suspension for soybean seed treatment (Revista Brasileira de Ciencia do Solo)', 'url' => 'https://www.scielo.br/j/rbcs/a/V9z6YVxh745dGSVygM6XTKD/?lang=en'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf']]],
    ];

    /**
     * How each crop meets the season (2026-10-08), for the sun, water and
     * temperature planks. kc = the crop's water use against the reference
     * evapotranspiration (FAO 56, averaged over the season); ky = how hard a
     * water shortfall cuts the yield (FAO 33: maize 1.25, sorghum 0.9,
     * groundnut 0.7, soybean 0.85, potato and onion 1.1, tomato 1.05,
     * cabbage 0.95, sugarcane 1.2, banana 1.25, pepper 1.1); extraMm = water
     * a flooded paddy also loses to seepage and land soaking; rad = the daily
     * sunshine (MJ per m2) a full potential yield wants; opt = the mean
     * temperatures it grows best in; hot = the day's high above which flowers
     * and fruit start to fail. Rough, common agronomy values: a guide, said so.
     */
    public const CLIMATE = [
        'default' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'rice' => ['kc' => 1.1, 'ky' => 1.2, 'rad' => 19, 'opt' => [22, 30], 'hot' => 35, 'extraMm' => 250],
        'rice_dsr_wet' => ['kc' => 1.1, 'ky' => 1.2, 'rad' => 19, 'opt' => [22, 30], 'hot' => 35, 'extraMm' => 200],
        'rice_dsr_dry' => ['kc' => 1.05, 'ky' => 1.2, 'rad' => 19, 'opt' => [22, 30], 'hot' => 35, 'extraMm' => 100],
        'corn_yellow' => ['kc' => 0.85, 'ky' => 1.25, 'rad' => 20, 'opt' => [20, 30], 'hot' => 35],
        'corn_sweet' => ['kc' => 0.85, 'ky' => 1.25, 'rad' => 20, 'opt' => [20, 30], 'hot' => 35],
        'sorghum' => ['kc' => 0.8, 'ky' => 0.9, 'rad' => 20, 'opt' => [22, 32], 'hot' => 38],
        'mungbean' => ['kc' => 0.75, 'ky' => 1.0, 'rad' => 18, 'opt' => [25, 32], 'hot' => 38],
        'peanut' => ['kc' => 0.8, 'ky' => 0.7, 'rad' => 18, 'opt' => [22, 30], 'hot' => 35],
        'soybean' => ['kc' => 0.8, 'ky' => 0.85, 'rad' => 18, 'opt' => [20, 30], 'hot' => 35],
        'stringbean' => ['kc' => 0.8, 'ky' => 1.15, 'rad' => 16, 'opt' => [20, 30], 'hot' => 35],
        'sweetpotato' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'cassava' => ['kc' => 0.75, 'ky' => 0.8, 'rad' => 17, 'opt' => [22, 32], 'hot' => 38],
        'taro' => ['kc' => 1.0, 'ky' => 1.1, 'rad' => 15, 'opt' => [21, 27], 'hot' => 33],
        'potato' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 16, 'opt' => [15, 22], 'hot' => 28],
        'carrot' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 16, 'opt' => [15, 24], 'hot' => 30],
        'ginger' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 14, 'opt' => [20, 30], 'hot' => 35],
        'pechay' => ['kc' => 0.85, 'ky' => 1.0, 'rad' => 14, 'opt' => [18, 28], 'hot' => 32],
        'cabbage' => ['kc' => 0.85, 'ky' => 0.95, 'rad' => 15, 'opt' => [15, 24], 'hot' => 30],
        'lettuce' => ['kc' => 0.85, 'ky' => 1.0, 'rad' => 14, 'opt' => [15, 24], 'hot' => 29],
        'broccoli' => ['kc' => 0.85, 'ky' => 1.0, 'rad' => 15, 'opt' => [15, 24], 'hot' => 30],
        'tomato' => ['kc' => 0.9, 'ky' => 1.05, 'rad' => 17, 'opt' => [18, 27], 'hot' => 32],
        'eggplant' => ['kc' => 0.85, 'ky' => 1.05, 'rad' => 17, 'opt' => [22, 30], 'hot' => 35],
        'ampalaya' => ['kc' => 0.85, 'ky' => 1.05, 'rad' => 17, 'opt' => [22, 30], 'hot' => 35],
        'squash' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'cucumber' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'okra' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 17, 'opt' => [22, 32], 'hot' => 38],
        'chili' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'bellpepper' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 16, 'opt' => [18, 27], 'hot' => 32],
        'watermelon' => ['kc' => 0.8, 'ky' => 1.1, 'rad' => 18, 'opt' => [22, 30], 'hot' => 36],
        'onion' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 16, 'opt' => [15, 25], 'hot' => 32],
        'garlic' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 15, 'opt' => [12, 24], 'hot' => 30],
        'sugarcane' => ['kc' => 1.0, 'ky' => 1.2, 'rad' => 20, 'opt' => [22, 32], 'hot' => 38],
        'pineapple' => ['kc' => 0.4, 'ky' => 0.6, 'rad' => 17, 'opt' => [22, 32], 'hot' => 36],
        'banana' => ['kc' => 1.0, 'ky' => 1.25, 'rad' => 16, 'opt' => [22, 31], 'hot' => 35],
        'papaya' => ['kc' => 0.9, 'ky' => 1.1, 'rad' => 17, 'opt' => [22, 32], 'hot' => 35],
        'mango' => ['kc' => 0.8, 'ky' => 0.8, 'rad' => 18, 'opt' => [24, 30], 'hot' => 38],
        'coconut' => ['kc' => 0.9, 'ky' => 0.8, 'rad' => 18, 'opt' => [24, 30], 'hot' => 36],
        'calamansi' => ['kc' => 0.8, 'ky' => 0.9, 'rad' => 17, 'opt' => [22, 30], 'hot' => 36],
        'coffee' => ['kc' => 0.9, 'ky' => 0.9, 'rad' => 14, 'opt' => [18, 26], 'hot' => 32],
        'cacao' => ['kc' => 0.95, 'ky' => 0.9, 'rad' => 13, 'opt' => [21, 30], 'hot' => 33],
    ];

    /**
     * How ENSO tilts a Philippine season, by strength (weak, moderate,
     * strong, very strong): El Niño brings less rain, more sun and heat, La
     * Niña more rain and cloud. PAGASA's outlooks see the El Niño rain cut
     * most from October to May, so the tilt is full for those months and
     * half in the southwest monsoon; a season more than nine months out
     * gets half, since the forecast that far is weak. A rough tilt, said so.
     */
    public const ENSO_TILT = [
        'el_nino' => ['rain' => [-0.15, -0.25, -0.35, -0.45], 'sun' => [0.03, 0.05, 0.07, 0.08], 'temp' => [0.3, 0.5, 0.7, 0.9]],
        'la_nina' => ['rain' => [0.1, 0.2, 0.3, 0.3], 'sun' => [-0.02, -0.04, -0.05, -0.06], 'temp' => [-0.2, -0.3, -0.3, -0.3]],
    ];

    /**
     * Your field, counted (2026-10-15, the owner): what the detailed field
     * answers (App\Support\FieldProfile) add to the need, on top of the soil
     * factors above, each said to the farmer as a plain line. Conservative,
     * from PhilRice, IRRI and DA practice:
     *  - omHighN: soil rich in organic matter gives some nitrogen of its own;
     *    slowN: slow drainage on an upland crop loses a little nitrogen;
     *  - legumeN: kg N per ha a mungbean, peanut or soybean crop before leaves
     *    (20 to 30 in the guides); strawK: kg K2O per ha rice straw left on
     *    the field and plowed in gives back (20 to 30); never more than
     *    creditFloor of the need is taken off;
     *  - caps: the share of the goal the field can carry, for rain only or a
     *    short water source (rice is grown for irrigation in the guides, so it
     *    is held lower), for salt and for sodium; never below capFloor. A goal
     *    the farmer typed is kept as theirs, and only warned about;
     *  - splits: how many nitrogen doses suit each texture;
     *  - acidPh and alkPh: the pH words the lines use (the factors keep their own).
     */
    public const FIELD = [
        'omHighN' => 0.9, 'slowN' => 1.05,
        'legumeN' => 25, 'strawK' => 25, 'creditFloor' => 0.5,
        'caps' => ['rainfed' => ['rice' => 0.8, 'other' => 0.9], 'partial' => ['rice' => 0.9, 'other' => 0.95], 'saline' => 0.9, 'sodic' => 0.9],
        'capFloor' => 0.75,
        // A pump or a well (2026-10-20, the owner): it waters whenever the crop needs it, so it makes up most of
        // what the rain leaves short, El Nino or not; a little is lost when the water table drops late in a dry
        // season or the fuel runs out. A canal that runs only some of the time ('partial') makes up half.
        'pumpCover' => 0.9, 'partialCover' => 0.5,
        'splits' => ['sandy' => 3, 'loam' => 2, 'clay' => 2, 'unsure' => 2],
        'acidPh' => 5.5, 'alkPh' => 7.5,
    ];

    /**
     * What a yield figure weighs (2026-10-20, the owner: say whether a yield is dry or wet, and show the
     * other). A crop sold dried is counted dry, as its potential and the national averages are written;
     * beside it, its weight at harvest: by moisture ('mc' => [dry %, at harvest %], wet = dry x (100 - dry)
     * / (100 - wet)), or by a ratio where more than water goes ('x' => [lo, hi]: coffee's fresh cherry
     * gives about a fifth of its weight in green beans; cacao's wet beans keep 34 to 40% once fermented
     * and dried; onions lose about 5 to 9% curing, garlic about a quarter drying). Every other crop is
     * counted fresh, as picked.
     */
    public const YIELD_BASIS = [
        'rice' => ['dry' => 'dry palay', 'wet' => 'wet palay at harvest', 'mc' => [14, 24]],
        'rice_dsr_wet' => ['dry' => 'dry palay', 'wet' => 'wet palay at harvest', 'mc' => [14, 24]],
        'rice_dsr_dry' => ['dry' => 'dry palay', 'wet' => 'wet palay at harvest', 'mc' => [14, 24]],
        'corn_yellow' => ['dry' => 'dry shelled corn', 'wet' => 'wet shelled corn at harvest', 'mc' => [14, 25]],
        'sorghum' => ['dry' => 'dry grain', 'wet' => 'wet grain at harvest', 'mc' => [14, 22]],
        'soybean' => ['dry' => 'dry beans', 'wet' => 'beans at harvest', 'mc' => [13, 18]],
        'mungbean' => ['dry' => 'dry beans', 'wet' => 'beans at harvest', 'mc' => [12, 15]],
        'peanut' => ['dry' => 'dry pods in the shell', 'wet' => 'freshly dug pods', 'mc' => [10, 35]],
        'coffee' => ['dry' => 'green coffee beans', 'wet' => 'fresh red cherries', 'x' => [4.8, 6]],
        'cacao' => ['dry' => 'dry fermented beans', 'wet' => 'wet beans from the pods', 'x' => [2.5, 2.9]],
        'onion' => ['dry' => 'cured bulbs', 'wet' => 'bulbs at harvest, before curing', 'x' => [1.05, 1.1]],
        'garlic' => ['dry' => 'dried bulbs', 'wet' => 'bulbs at harvest, before drying', 'x' => [1.3, 1.4]],
    ];

    /** The yield basis in words, for a prompt: "Yields are dry palay at 14% moisture; ..." */
    public static function yieldBasisWords(?string $crop): string
    {
        $b = self::YIELD_BASIS[['corn' => 'corn_yellow'][(string) $crop] ?? (string) $crop] ?? null;
        if (! $b) {
            return 'Yields are the fresh weight, as picked.';
        }
        [$lo, $hi] = isset($b['mc']) ? array_fill(0, 2, (100 - $b['mc'][0]) / (100 - $b['mc'][1])) : $b['x'];

        return 'Yields are ' . $b['dry'] . (isset($b['mc']) ? ' at ' . $b['mc'][0] . '% moisture' : '') . '; ' . $b['wet']
            . (isset($b['mc']) ? ' (about ' . $b['mc'][1] . '% moisture)' : '') . ' weigh about ' . ($lo === $hi ? round($lo, 2) : round($lo, 2) . ' to ' . round($hi, 2))
            . ' times as much. Say which you mean when you give a yield.';
    }

    public static function forClient(): array
    {
        $conditions = [];
        foreach (SoilConditions::OPTIONS as $k => $v) {
            if ($k === 'unsure') {
                continue;
            }
            [$label, $sub] = array_pad(explode(' — ', $v, 2), 2, '');
            $conditions[$k] = [$label, ucfirst($sub)];
        }

        return [
            'textures' => self::TEXTURES, 'conditions' => $conditions + self::MORE_CONDITIONS, 'soilShare' => self::SOIL_SHARE, 'soilShareCrop' => self::SOIL_SHARE_CROP, 'recovery' => self::RECOVERY,
            'guideYield' => self::GUIDE_YIELD, 'acidity' => self::ACIDITY, 'acidAt' => self::ACID_AT, 'acidUnlock' => self::ACID_UNLOCK, 'pkBase' => self::PK_BASE, 'potentialX' => self::POTENTIAL_X, 'adjust' => self::ADJUST, 'seeding' => array_map(fn ($s) => array_diff_key($s, ['sources' => 1]), self::SEEDING), 'elements' => array_map(fn ($e) => array_diff_key($e, ['sources' => 1]), self::ELEMENTS), 'levels' => self::MICRO_LEVELS, 'soilMicro' => ['step' => self::SOIL_STEP, 'floor' => self::SOIL_FLOOR, 'none' => self::SOIL_NONE, 'factor' => self::SOIL_FACTOR, 'byEl' => self::SOIL_FACTOR_EL], 'climate' => self::CLIMATE, 'ensoTilt' => self::ENSO_TILT,
            'field' => self::FIELD, 'yieldBasis' => self::YIELD_BASIS,
        ];
    }
}
