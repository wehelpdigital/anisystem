<?php

namespace App\Support;

/**
 * The land preparation planner (2026-10-14): every step of getting a field
 * ready for a crop, dated back from the planting day. A step is a window in
 * days relative to planting (from -30 to -21 means thirty to twenty one days
 * before), what to do, and how, in a farmhand's words.
 *
 * The timings follow ordinary Philippine extension practice: PhilRice's
 * PalayCheck System for rice (plow three to four weeks ahead, harrow three
 * times five to seven days apart, level under shallow water, seedlings of 18
 * to 21 days by hand), and DA, BPI and university guides for the rest. Each
 * plan points to its full guide on the public site.
 *
 * A soil trouble adds its own steps (lime for acid soil, gypsum for sodic,
 * flushing for salty ground, keeping acid sulfate soil wet).
 */
class LandPrep
{
    /** The plans, keyed; each: label, guide (site page slug), steps. */
    public const PLANS = [
        'rice_tpr' => [
            'label' => 'Transplanted rice',
            'guide' => 'rice-land-preparation',
            'steps' => [
                [-35, -30, 'Clean the field and fix the water', 'Cut weeds on the dikes and canals. Patch every leak in the dikes and clear the inlets and outlets, so the field holds water and drains when you want it to.'],
                [-30, -30, 'Return the straw', 'Spread the last crop\'s straw over the field, or incorporate the stubble, so it rots before planting. It gives back potassium and feeds the soil.'],
                [-30, -21, 'First plowing', 'Soak the field a day or two until the soil is soft, then plow with a hand tractor or rotavator. Leave it about a week so the clods soften and the weeds rot.'],
                [-23, -23, 'Soak the seed', 'Soak the seed in clean water for 24 hours, then drain and keep it covered in a shaded, airy place for another 24 hours until the first sprouts show. Use about 40 kg of good inbred seed or 20 kg of hybrid seed per hectare.'],
                [-24, -22, 'Make the seedbed', 'Raise wet seedbed plots about 5 cm, level them well, and leave a canal about 40 cm wide between plots for drainage. Make them a day to three days before sowing.'],
                [-21, -21, 'Sow the seedbed', 'Drain the seedbed and spread the sprouted seed evenly, about 1 kg for every 10 square meters, so the seedlings do not crowd.'],
                [-21, -16, 'First harrowing', 'Harrow along the length of the field, five to seven days after plowing.'],
                [-16, -12, 'Feed the seedbed', 'About 7 to 10 days after sowing, give the seedbed a light fertilizer. When the seedlings are 2 to 3 cm tall, keep 2 cm of water and raise it slowly to 5 cm as they grow, never drowning them.'],
                [-14, -14, 'Spread manure', 'If you use chicken manure or vermicompost, spread it now, about 10 bags per hectare, so it mixes in with the last harrowing.'],
                [-14, -9, 'Second harrowing', 'Harrow across the field, five to seven days after the first, to break the clods and bury the weeds that sprouted.'],
                [-7, -3, 'Third harrowing and leveling', 'Harrow a third time, then level the field with a wooden board pulled by a carabao or hand tractor under about 5 cm of water. A level field takes water evenly, so the crop ripens together and weeds have no dry spots.'],
                [-1, 0, 'Pull the seedlings and transplant', 'Transplant seedlings of 18 to 21 days by hand, or 14 to 18 days for a mechanical transplanter, in straight rows, two or three seedlings per hill for inbreds. Do not cut the leaf tips, it opens a door to disease.'],
                [0, 3, 'Weeds and snails', 'If weeds are a problem, apply a pre emergence herbicide within 3 days of transplanting, as its label says. Control golden apple snails a day after transplanting where they are present.'],
                [3, 7, 'Replant the gaps', 'Three days after transplanting, bring the water to 2 to 3 cm. Fill missing hills within the week with the spare seedlings, so the crop matures together.'],
            ],
        ],
        'rice_dsr_wet' => [
            'label' => 'Direct seeded rice, wet (sabog tanim)',
            'guide' => 'direct-seeded-rice-land-preparation',
            'steps' => [
                [-35, -30, 'Clean the field and fix the water', 'Patch the dikes and clear the canals. Direct seeded rice needs a field you can drain completely, so make small drainage canals along the dikes.'],
                [-30, -21, 'First plowing', 'Soak the field until soft, plow, and leave it about a week so the clods soften and the weeds rot.'],
                [-21, -16, 'First harrowing', 'Harrow along the length of the field, five to seven days after plowing.'],
                [-14, -9, 'Second harrowing', 'Harrow across the field five to seven days later, to bury the new flush of weeds.'],
                [-3, -2, 'Soak the seed', 'Soak the seed in clean water for 24 hours, then drain and keep it covered for 24 hours until the first sprouts show. Air dry the sprouted seed a little before sowing so it spreads evenly.'],
                [-2, -1, 'Final harrowing and leveling', 'Harrow a last time and level the field very carefully under shallow water. Finish a day before sowing. A field with high and low spots is the main reason direct seeded rice fails: seed drowns in the low spots and weeds take the high ones.'],
                [0, 0, 'Drain and sow', 'Drain the field to a saturated mud and broadcast the seed evenly, or use a drum seeder or seed spreader. Protect the seed from birds, rats and snails.'],
                [0, 3, 'Weeds', 'Apply a pre emergence herbicide within 3 days of sowing, or an early post emergence one 6 to 9 days after, as the label says.'],
                [0, 10, 'Keep the field wet, not flooded', 'Keep the soil saturated with no standing water for the first 10 days, unless a dry spell is drying it out.'],
                [10, 12, 'First water', 'After 10 days, flood the field to 2 to 3 cm and fill the gaps with spare seedlings at the same time, so the crop matures together.'],
            ],
        ],
        'rice_dsr_dry' => [
            'label' => 'Direct seeded rice, dry',
            'guide' => 'direct-seeded-rice-land-preparation',
            'steps' => [
                [-45, -30, 'Clean the field', 'Clear weeds and the old stubble. Patch the dikes so the first rains are held in the field.'],
                [-30, -21, 'Dry plowing', 'Plow while the soil is dry enough to crumble, before the rains settle in.'],
                [-14, -7, 'Harrow and level', 'Harrow twice to a fine, crumbly bed and level it. Dry seeded rice needs a level field just as much as wet seeded rice.'],
                [-3, -1, 'Final harrowing', 'Harrow once more to kill the weeds that came up after the first rains.'],
                [0, 0, 'Seed', 'Drill or dibble dry seed 2 to 3 cm deep in rows, as soon as the soil has enough moisture.'],
                [0, 3, 'Weeds', 'Apply a pre emergence herbicide within 3 days of seeding, as the label says. Weeds are the biggest threat to dry seeded rice.'],
            ],
        ],
        'corn' => [
            'label' => 'Corn and sorghum',
            'guide' => 'corn-land-preparation',
            'steps' => [
                [-30, -21, 'Clear the field', 'Cut and remove the weeds and the old crop. Burn nothing: chop it and leave it to rot, or compost it.'],
                [-21, -14, 'Plow', 'Plow 15 to 20 cm deep when the soil is moist but not sticky. Leave the clods to dry and break for about a week.'],
                [-10, -7, 'Harrow', 'Harrow once or twice to a fine bed, crossing the plowing direction.'],
                [-3, -1, 'Furrow', 'Open furrows about 75 cm apart across the slope. On flat, wet ground, make drainage canals so water does not stand in the wet season.'],
                [-1, 0, 'Basal fertilizer and planting', 'Spread the basal fertilizer and manure in the furrows, cover it with a little soil, and plant the seed beside it, never touching it.'],
                [0, 3, 'Weeds', 'Apply a pre emergence herbicide right after planting if weeds are heavy, as the label says.'],
            ],
        ],
        'legume' => [
            'label' => 'Mungbean, peanut, soybean and other legumes',
            'guide' => 'legume-land-preparation',
            'steps' => [
                [-21, -14, 'Clear or plow', 'After rice, you can plant straight into the stubble without plowing while the soil is still moist. Otherwise plow once when the soil is workable.'],
                [-10, -7, 'Harrow', 'Harrow to a fine, crumbly bed. Peanut needs loose soil so its pegs can push into the ground.'],
                [-3, -1, 'Furrows and drainage', 'Open furrows 50 to 60 cm apart and drainage canals around the field. Legumes hate wet feet.'],
                [-1, 0, 'Plant', 'Plant in the furrows or dibble holes. Treat the seed with a rhizobium inoculant where it is available.'],
            ],
        ],
        'vegetable' => [
            'label' => 'Vegetables on beds',
            'guide' => 'vegetable-land-preparation',
            'steps' => [
                [-30, -25, 'Sow the nursery', 'For transplanted vegetables (tomato, eggplant, pepper, cabbage), sow the seedlings in trays or a nursery now. They are ready to move at 3 to 4 weeks.'],
                [-21, -14, 'Clear and plow', 'Clear the field and plow 20 to 25 cm deep. Leave the clods a week to dry and break.'],
                [-14, -10, 'Harrow and spread compost', 'Harrow to a fine soil and spread well rotted compost or manure, then harrow it in.'],
                [-10, -7, 'Form the beds', 'Make raised beds about 1 meter wide with 30 to 50 cm paths. Make them 20 to 30 cm high in the wet season so the roots stay out of standing water.'],
                [-3, 0, 'Mulch and holes', 'Lay plastic or straw mulch on the beds and punch the planting holes at the right spacing. Water the beds the day before planting.'],
                [0, 0, 'Transplant', 'Transplant in the late afternoon and water right after, so the seedlings settle overnight.'],
            ],
        ],
        'cucurbit' => [
            'label' => 'Gourds and melons',
            'guide' => 'cucurbit-land-preparation',
            'steps' => [
                [-21, -14, 'Clear and plow', 'Clear and plow the field 20 to 25 cm deep.'],
                [-14, -10, 'Harrow and compost', 'Harrow to a fine soil and work in compost or manure.'],
                [-10, -7, 'Beds or hills', 'Make raised beds or hills, wide apart: melons and squash run far, and ampalaya, upo and patola climb a trellis.'],
                [-3, 0, 'Mulch', 'Lay plastic mulch on the beds and punch the holes. It keeps the fruit clean and the weeds down.'],
                [0, 0, 'Plant', 'Plant the seed or transplant the seedlings.'],
                [7, 14, 'Build the trellis', 'For ampalaya, upo and patola, put up the trellis before the vines start to run.'],
            ],
        ],
        'root' => [
            'label' => 'Root crops',
            'guide' => 'root-crop-land-preparation',
            'steps' => [
                [-21, -14, 'Clear and plow', 'Clear the field and plow 20 to 30 cm deep. Roots need loose soil to swell.'],
                [-10, -7, 'Harrow', 'Harrow once or twice to break the clods.'],
                [-3, -1, 'Ridges or mounds', 'Make ridges about 75 to 100 cm apart and 30 cm high for sweet potato, or mounds for gabi and ube. Cassava grows on flat or ridged ground.'],
                [-1, 0, 'Prepare the planting material', 'Cut healthy vine tips (kamote), stem cuttings (cassava) or setts (gabi, ube) from a clean, pest free source.'],
                [0, 0, 'Plant', 'Plant on the ridges with the soil moist.'],
            ],
        ],
        'bulb' => [
            'label' => 'Onion and garlic',
            'guide' => 'onion-garlic-land-preparation',
            'steps' => [
                [-45, -40, 'Sow the onion seedbed', 'For onion grown from seedlings, sow the seedbed now. The seedlings are ready to move at about 40 to 45 days.'],
                [-21, -14, 'Clear and plow', 'Plow the field after the rice harvest, once the soil is workable.'],
                [-14, -7, 'Harrow fine', 'Harrow two or three times to a fine, even soil. Onion and garlic have short roots and need a smooth bed.'],
                [-7, -3, 'Beds and canals', 'Make beds about 1 meter wide with canals between them for irrigation and drainage.'],
                [-3, 0, 'Mulch', 'Spread rice straw mulch on the beds, especially for garlic. It keeps the soil moist and the weeds down.'],
                [0, 0, 'Plant', 'Transplant the onion seedlings or plant the garlic cloves, pointed end up.'],
            ],
        ],
        'sugarcane' => [
            'label' => 'Sugarcane',
            'guide' => 'sugarcane-land-preparation',
            'steps' => [
                [-60, -45, 'Clear and subsoil', 'Clear the field and break the hardpan with a subsoiler where the soil is compacted.'],
                [-45, -30, 'Deep plowing', 'Plow 30 to 40 cm deep. Sugarcane stays in the field for years of ratoons, so deep preparation pays for a long time.'],
                [-21, -14, 'Harrow', 'Harrow twice to break the clods.'],
                [-7, -1, 'Furrow', 'Open furrows 1.2 to 1.5 meters apart, 20 to 30 cm deep.'],
                [-1, 0, 'Prepare and plant the setts', 'Cut setts with two or three buds from healthy cane, treat them as recommended, and lay them in the furrows.'],
            ],
        ],
        'banana' => [
            'label' => 'Banana and abaca',
            'guide' => 'banana-land-preparation',
            'steps' => [
                [-45, -30, 'Clear and lay out', 'Clear the field and stake the planting points, about 2 to 3 meters apart.'],
                [-30, -21, 'Drainage', 'Dig drainage canals where water stands. Banana roots rot in waterlogged ground.'],
                [-21, -14, 'Dig the holes', 'Dig holes about 60 cm wide and deep. Keep the topsoil apart.'],
                [-14, -7, 'Fill the holes', 'Mix the topsoil with compost or manure and fill the holes back.'],
                [0, 0, 'Plant', 'Plant clean, tissue cultured plantlets or healthy suckers at the start of the rains.'],
            ],
        ],
        'tree' => [
            'label' => 'Fruit and plantation trees',
            'guide' => 'fruit-tree-land-preparation',
            'steps' => [
                [-90, -60, 'Clear and lay out', 'Clear the land and stake the planting points at the spacing the tree needs. On slopes, follow the contour.'],
                [-60, -45, 'Windbreak and shade', 'Plant windbreaks on the typhoon side, and fast shade trees where young cacao or coffee need them.'],
                [-45, -30, 'Dig the holes', 'Dig holes 60 cm to 1 meter wide and deep, keeping the topsoil apart.'],
                [-30, -14, 'Fill the holes', 'Mix the topsoil with compost or manure and fill the holes back, a little above the ground so it settles level.'],
                [0, 0, 'Plant', 'Plant grafted or marcotted trees from an accredited nursery at the start of the rains, and stake them.'],
            ],
        ],
    ];

    /** Steps a soil trouble adds, keyed as SoilConditions names them. */
    public const SOIL_STEPS = [
        'acidic' => [
            [-45, -40, 'Test the soil', 'Have a soil test done (your municipal agriculturist or the BSWM soil lab) to know how much lime to apply.'],
            [-30, -14, 'Apply lime', 'Spread agricultural lime or dolomite at the rate the test gives and plow or harrow it in, at least two weeks before planting so it starts working.'],
        ],
        'saline' => [
            [-30, -14, 'Flush the salt', 'Flood the field with good water and drain it, two or three times, to wash the salt below the roots. Make raised beds for vegetables.'],
            [-7, 0, 'Plant salt tolerant', 'Choose a salt tolerant variety and use plenty of organic matter.'],
        ],
        'sodic' => [
            [-45, -30, 'Apply gypsum', 'Spread gypsum at the rate a soil test gives and work it in. It replaces the sodium that makes the soil crust and seal.'],
            [-21, -14, 'Leach', 'Flood and drain the field to carry the sodium away after the gypsum.'],
        ],
        'acid_sulfate' => [
            [-30, -1, 'Keep it wet', 'Do not let acid sulfate soil dry out and crack: drying releases the acid. Keep it wet through land preparation and apply lime as a test advises.'],
        ],
    ];

    /** The plan an app crop key uses. */
    public static function planFor(?string $cropKey): string
    {
        $k = (string) $cropKey;
        if ($k === 'rice') {
            return 'rice_tpr';
        }
        if (in_array($k, ['rice_dsr_wet', 'rice_dsr_dry'], true)) {
            return $k;
        }
        if (str_starts_with($k, 'corn') || $k === 'sorghum') {
            return 'corn';
        }
        if (in_array($k, ['onion', 'garlic', 'shallot', 'onion_spring'], true)) {
            return 'bulb';
        }
        if ($k === 'sugarcane') {
            return 'sugarcane';
        }
        if (in_array($k, ['banana', 'abaca'], true)) {
            return 'banana';
        }
        $c = CropCatalog::CROPS[$k] ?? [];
        $pattern = $c['pattern'] ?? null;

        return match (true) {
            $pattern === 'legume' => 'legume',
            $pattern === 'root' && $k !== 'pineapple' => 'root',
            $pattern === 'cucurbit' => 'cucurbit',
            in_array($pattern, ['leafy', 'fruiting'], true) => 'vegetable',
            CropCatalog::isPerennial($k) || $k === 'pineapple' => 'tree',
            default => 'vegetable',
        };
    }

    /** Everything the planner page needs, as plain data for its script. */
    public static function book(): array
    {
        $crops = [];
        foreach (CropCatalog::visible() as $k => $c) {
            if ($k === 'vegetables') {
                continue;
            }
            $crops[] = ['key' => $k, 'label' => $c['label'], 'icon' => $c['icon'] ?? '🌱', 'group' => $c['group'] ?? '', 'plan' => self::planFor($k)];
        }
        $plans = [];
        foreach (self::PLANS as $k => $p) {
            $plans[$k] = ['label' => $p['label'], 'guide' => url('/land-preparation/' . $p['guide']),
                'steps' => array_map(fn ($s) => ['from' => $s[0], 'to' => $s[1], 'what' => $s[2], 'how' => $s[3]], $p['steps'])];
        }
        $soil = [];
        foreach (self::SOIL_STEPS as $k => $steps) {
            $soil[$k] = array_map(fn ($s) => ['from' => $s[0], 'to' => $s[1], 'what' => $s[2], 'how' => $s[3]], $steps);
        }

        return [
            'crops' => $crops,
            'plans' => $plans,
            'soil' => $soil,
            'soilLabels' => ['acidic' => 'Acidic (sour)', 'saline' => 'Salty', 'sodic' => 'Sodic (crusts, seals)', 'acid_sulfate' => 'Acid sulfate'],
            'soilGuides' => ['acidic' => url('/land-preparation/acidic-soil-preparation'), 'saline' => url('/land-preparation/saline-soil-preparation'),
                'sodic' => url('/land-preparation/sodic-alkaline-soil-preparation')],
        ];
    }
}
