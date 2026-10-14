<?php

namespace App\Support;

/**
 * The land preparation plans (2026-10-14): every step of getting a field
 * ready for a crop, dated back from the planting day. A step is a window in
 * days relative to planting (from -30 to -21 means thirty to twenty one days
 * before), what to do, how, in a farmhand's words, and a key the Land Prep
 * Planner (App\Support\LandPrepPlanner) uses to move, drop or schedule it.
 *
 * Rice follows the PalayCheck System of PhilRice (2022 revised edition) and
 * its field calendar, one plan per planting method: by hand, by machine
 * (trays or dapog), wet seeded by broadcasting, wet seeded with a drum
 * seeder. Dry seeded rice follows PhilRice and IRRI dry seeding practice,
 * since PalayCheck covers transplanted and wet seeded rice only. The day
 * counts match database/land-prep/rice-methods.json, which the planner reads
 * for the full steps (basis, anchors). The other crops follow DA, BPI and
 * university extension guides.
 *
 * A soil trouble adds its own steps (lime for strongly acid soil, gypsum for
 * sodic, flushing for salty ground, keeping acid sulfate soil wet).
 */
class LandPrep
{
    /** The plans, keyed; each: label, guide (site page slug), steps [from, to, what, how, key]. */
    public const PLANS = [
        'rice_tpr' => [
            'label' => 'Transplanted rice, by hand',
            'guide' => 'rice-land-preparation',
            'steps' => [
                [-51, -21, 'Let the field rest', 'Leave at least 30 days between the last harvest and sowing the seedbed, with no rice growing on the field. Plow under any ratoon or stray rice plants, so the pests and diseases of the last crop starve.', 'rest'],
                [-45, -35, 'Check the planting schedule in your area', 'Ask your irrigators association or the municipal agriculture office when your area plants, and set your planting day no more than 14 days before or after it.', 'calendar'],
                [-32, -28, 'Clean and fix the dikes and canals', 'Cut the weeds on the dikes, patch every leak and pack the soil firm. Build them about 15 cm high and 20 cm wide, a size rats find hard to burrow into. Clear the canals so the field fills and drains when you want it to.', 'dikes'],
                [-30, -30, 'Spread the old straw back', 'Scatter the straw of the last crop over the field, about 3 to 8 tons per hectare, so it rots before planting and gives back potassium. Burning wastes it.', 'straw'],
                [-30, -21, 'Plow or rotavate', 'Plow once, 21 to 30 days before transplanting, with a hand tractor, a four wheel tractor, a rotavator or a carabao. If the ground is dry and hard, let water in a day or two before so the plow bites.', 'plow'],
                [-29, -14, 'Keep the plowed field wet for a week', 'Let shallow water back in a day or two after plowing and leave the field for about 7 days, so the clods soften and the weeds and straw start to rot.', 'soak_field'],
                [-28, -25, 'Get the seedbed ground ready', 'About a week before sowing, plow and harrow about 400 square meters for every hectare you will plant (20 beds of 1 by 20 meters). On heavy clay, work in 3 to 4 bags of organic fertilizer or carbonized rice hull before leveling.', 'seedbed_site'],
                [-24, -19, 'Shape the seedbed plots', 'One to three days before sowing, shape wet beds about 1 meter wide and 20 meters long, raised about 5 cm, level them well, and leave a canal about 40 cm wide between beds.', 'seedbed_beds'],
                [-23, -23, 'Soak the seed', 'Two days before sowing, soak the seed in clean water for 24 hours. Use 20 to 40 kg of good seed per hectare: about 40 kg of inbred or 20 kg of hybrid.', 'seed_soak'],
                [-22, -22, 'Let the seed sprout', 'Take the seed out of the water and keep it covered for another 24 hours in a shaded, airy spot, until most grains show a small sprout.', 'seed_incubate'],
                [-21, -21, 'Sow the seedbed', 'Drain the beds and scatter the sprouted seed evenly, about 1 kg on every 10 square meters, 18 to 21 days before transplanting.', 'sow_seedbed'],
                [-16, -14, 'First harrowing, lengthwise', 'Harrow along the lines you plowed, 5 to 7 days or more after plowing, to break the clods and wake the weed seeds so the next pass buries them.', 'harrow1'],
                [-16, -14, 'Water the seedbed', 'When the seedlings stand 2 to 3 cm tall, about a week after sowing, let in 2 cm of water and raise it slowly to 5 cm as they grow, never covering them.', 'seedbed_water'],
                [-14, -14, 'Spread manure or compost', 'Two weeks before transplanting, spread about 10 bags per hectare of air dried chicken manure, organic fertilizer or vermicompost, so the next harrowing mixes it in.', 'manure'],
                [-14, -11, 'Zinc for the seedbed', 'Seven to ten days after sowing, mix 1 to 2 kg of zinc sulfate into the 400 square meter seedbed (0.5 kg for a mild zinc shortage, 2 kg for a severe one).', 'seedbed_zinc'],
                [-11, -7, 'Feed the seedbed', 'Ten to fourteen days after sowing, spread 2 to 4 kg of 14-14-14 over the seedbed (4 kg for hybrid).', 'seedbed_feed'],
                [-9, -7, 'Second harrowing, crosswise', 'Five to seven days after the first pass, harrow across the field to bury the new flush of weeds and start the leveling.', 'harrow2'],
                [-2, -1, 'Final harrowing and leveling', 'Make the last pass a day or two before transplanting, then level under about 5 cm of water with a wooden plank or a leveler. The field is ready when no spot holds water deeper than 5 cm and no lump shows above the water.', 'level'],
                [-1, 0, 'Pull and lay out the seedlings', 'Pull the seedlings the day before or the morning of transplanting and set the bundles out across the field. Do not cut the leaf tips: the wound lets disease in.', 'pull'],
                [0, 0, 'Transplant in straight rows', 'Plant 2 to 3 seedlings per hill for inbred or 1 to 2 for hybrid, 20 by 20 cm apart in the wet season or 20 by 15 cm in the dry season, for at least 25 hills in a square meter. Seedlings of 18 to 21 days are best: past 25 days they recover slowly and make fewer tillers.', 'transplant'],
                [0, 3, 'Stop weeds and snails early', 'If weeds are a problem, spray a pre emergence herbicide within 3 days of transplanting, following its label. Where golden apple snails are present, control them the day after transplanting.', 'weeds_snails'],
                [3, 7, 'First water and replanting', 'Three days after transplanting, raise the water to 2 to 3 cm. Fill missing hills with spare seedlings within 7 days, the sooner the better, so the whole field ripens together.', 'water_replant'],
            ],
        ],
        'rice_tpr_mech' => [
            'label' => 'Transplanted rice, by machine (trays or dapog)',
            'guide' => 'rice-land-preparation',
            'steps' => [
                [-48, -14, 'Let the field rest', 'Leave at least 30 days between the last harvest and sowing the nursery, with no rice growing on the field. Plow under any ratoon or stray rice plants.', 'rest'],
                [-45, -35, 'Check the planting schedule in your area', 'Ask your irrigators association or the municipal agriculture office when your area plants, and set your planting day no more than 14 days before or after it.', 'calendar'],
                [-32, -28, 'Clean and fix the dikes and canals', 'Cut the weeds on the dikes, patch every leak and pack the soil firm, about 15 cm high and 20 cm wide. Clear the canals.', 'dikes'],
                [-30, -30, 'Spread the old straw back', 'Scatter the straw of the last crop over the field, about 3 to 8 tons per hectare, so it rots before planting.', 'straw'],
                [-30, -21, 'Plow or rotavate', 'Plow once, 21 to 30 days before transplanting.', 'plow'],
                [-29, -14, 'Keep the plowed field wet for a week', 'Let shallow water back in after plowing and leave the field for about 7 days.', 'soak_field'],
                [-25, -25, 'Plan the nursery', 'About a week before sowing, choose a level spot near the field: 250 to 300 trays of 28 by 58 cm per hectare, or a wet bed of about 50 square meters for the modified wet dapog method.', 'nursery_site'],
                [-21, -19, 'Fill the trays or lay the dapog bed', 'Fill the trays about 2 cm deep with fine, sieved soil mixed with some carbonized rice hull. For dapog, cover the leveled bed with plastic or banana leaves and spread a thin layer of fine mud on top.', 'nursery_fill'],
                [-20, -20, 'Soak the seed', 'Two days before sowing, soak 20 to 40 kg of seed per hectare in clean water for 24 hours.', 'seed_soak'],
                [-19, -19, 'Let the seed sprout', 'Keep the seed covered for 24 hours in a shaded, airy spot, until most grains show a small sprout.', 'seed_incubate'],
                [-18, -18, 'Sow the trays or dapog bed', 'Spread the sprouted seed evenly: 70 to 160 g in each tray, or 420 to 960 g per square meter of dapog bed, 14 to 18 days before the transplanter comes.', 'sow_nursery'],
                [-17, -1, 'Keep the nursery moist', 'Keep the trays or mat damp every day, never with water standing over the seedlings, so the roots knit into a firm mat the machine can cut.', 'nursery_care'],
                [-16, -14, 'First harrowing, lengthwise', 'Harrow along the lines you plowed, 5 to 7 days or more after plowing.', 'harrow1'],
                [-14, -14, 'Spread manure or compost', 'Spread about 10 bags per hectare of air dried chicken manure, organic fertilizer or vermicompost.', 'manure'],
                [-11, -8, 'Zinc for the nursery', 'Seven to ten days after sowing, give zinc sulfate where you aim for 5 tons or more per hectare or the soil is short of zinc: 1 to 2 kg covers the seedlings for a hectare.', 'nursery_zinc'],
                [-9, -7, 'Second harrowing, crosswise', 'Five to seven days after the first pass, harrow across the field.', 'harrow2'],
                [-8, -8, 'Feed the nursery', 'About 10 days after sowing, give 2 to 4 kg of 14-14-14 across the seedlings for one hectare.', 'nursery_feed'],
                [-3, -2, 'Final harrowing and leveling', 'Make the last pass two or three days before transplanting and level under about 5 cm of water.', 'level'],
                [-2, -1, 'Let the mud settle', 'A machine needs firmer mud than hands do. Leave the leveled field a day or two, then drain it to about 1 to 2 cm of water.', 'settle'],
                [0, 0, 'Transplant by machine', 'Set the transplanter to 30 by 15 cm with 3 to 6 seedlings per hill, the same in both seasons, for at least 21 hills in a square meter. Seedlings of 14 to 18 days are right.', 'transplant'],
                [0, 3, 'Stop weeds and snails early', 'If weeds are a problem, spray a pre emergence herbicide within 3 days of transplanting, following its label. Control golden apple snails the day after transplanting where they are present.', 'weeds_snails'],
                [3, 7, 'First water and replanting', 'Three days after transplanting, raise the water to 2 to 3 cm. Fill missing hills by hand within 7 days.', 'water_replant'],
            ],
        ],
        'rice_dsr_wet' => [
            'label' => 'Wet seeded rice, broadcast (sabog tanim)',
            'guide' => 'direct-seeded-rice-land-preparation',
            'steps' => [
                [-45, -35, 'Check the planting schedule in your area', 'Ask your irrigators association or the municipal agriculture office when your area plants, and set your sowing day no more than 14 days before or after it.', 'calendar'],
                [-30, -21, 'Spread the old straw back', 'Scatter the straw of the last crop over the field before plowing, about 3 to 8 tons per hectare.', 'straw'],
                [-30, -21, 'Plow or rotavate', 'Plow once, 21 to 30 days before sowing.', 'plow'],
                [-30, -1, 'Let the field rest', 'Leave at least 30 days between the last harvest and sowing, with no rice growing on the field.', 'rest'],
                [-29, -14, 'Keep the plowed field wet for a week', 'Let shallow water back in after plowing and leave the field for about 7 days.', 'soak_field'],
                [-24, -20, 'Clean and fix the dikes and canals', 'Patch the dikes, about 15 cm high and 20 cm wide, and clear the canals. Wet seeded rice needs a field you can drain completely.', 'dikes'],
                [-15, -13, 'First harrowing, lengthwise', 'Harrow along the lines you plowed, 5 to 7 days or more after plowing.', 'harrow1'],
                [-8, -6, 'Second harrowing, crosswise', 'Five to seven days after the first pass, harrow across the field.', 'harrow2'],
                [-2, -2, 'Soak the seed', 'Two days before sowing, soak 60 to 80 kg of seed per hectare in clean water for 24 hours. Where zinc is short, add 1 kg of zinc sulfate for every 40 kg of seed to the last change of soaking water.', 'seed_soak'],
                [-2, -1, 'Final harrowing and leveling', 'Make the last pass so it is done by the day before sowing, and level very carefully under shallow water. Seed drowns in low spots and weeds take the high ones.', 'level'],
                [-1, -1, 'Cut the small canals', 'Right after leveling, cut small canals about 25 cm wide and 5 cm deep along the inside of the dikes and through the middle of the field, to drain fast and collect snails.', 'canals'],
                [-1, -1, 'Let the seed sprout', 'Keep the seed covered for 24 hours in a shaded, airy spot, until most grains show a small sprout.', 'seed_incubate'],
                [0, 0, 'Drain and sow', 'Drain the field to soft, saturated mud and broadcast the sprouted seed evenly. Sow an extra 1 kg along one side of the field for filling gaps later.', 'sow'],
                [0, 3, 'Pre emergence herbicide', 'If weeds are a problem, spray a pre emergence herbicide within 3 days of sowing, following its label.', 'weeds_pre'],
                [0, 7, 'Guard the seed', 'Birds, rats and snails go for sown seed first. Chase birds off, fill rat burrows with mud and water, and keep the dikes clean.', 'guard'],
                [0, 14, 'Keep the soil wet, not flooded', 'Hold back irrigation for the first 10 to 14 days so the soil stays saturated with no standing water, unless drought stresses the seedlings.', 'keep_wet'],
                [6, 9, 'Early post emergence herbicide', 'If many weeds still come up, spray an early post emergence herbicide 6 to 9 days after sowing, by its label.', 'weeds_post'],
                [7, 10, 'Fill the bare patches', 'Replant bare patches more than 20 cm across with seedlings from the extra seed sown along the edge.', 'replant'],
                [10, 14, 'First water', 'From about 10 days after sowing, and by day 14 at the latest, let water in to 2 to 3 cm. Control golden apple snails as the water rises.', 'first_water'],
            ],
        ],
        'rice_dsr_drum' => [
            'label' => 'Wet seeded rice, drum seeder',
            'guide' => 'direct-seeded-rice-land-preparation',
            'steps' => [
                [-45, -35, 'Check the planting schedule in your area', 'Ask your irrigators association or the municipal agriculture office when your area plants, and set your sowing day no more than 14 days before or after it.', 'calendar'],
                [-30, -21, 'Spread the old straw back', 'Scatter the straw of the last crop over the field before plowing, about 3 to 8 tons per hectare.', 'straw'],
                [-30, -21, 'Plow or rotavate', 'Plow once, 21 to 30 days before sowing.', 'plow'],
                [-30, -1, 'Let the field rest', 'Leave at least 30 days between the last harvest and sowing, with no rice growing on the field.', 'rest'],
                [-29, -14, 'Keep the plowed field wet for a week', 'Let shallow water back in after plowing and leave the field for about 7 days.', 'soak_field'],
                [-24, -20, 'Clean and fix the dikes and canals', 'Patch the dikes, about 15 cm high and 20 cm wide, and clear the canals.', 'dikes'],
                [-15, -13, 'First harrowing, lengthwise', 'Harrow along the lines you plowed, 5 to 7 days or more after plowing.', 'harrow1'],
                [-8, -6, 'Second harrowing, crosswise', 'Five to seven days after the first pass, harrow across the field.', 'harrow2'],
                [-2, -2, 'Soak the seed', 'Two days before sowing, soak 40 to 60 kg of seed per hectare in clean water for 24 hours. Where zinc is short, add 1 kg of zinc sulfate for every 40 kg of seed to the last change of soaking water.', 'seed_soak'],
                [-2, -1, 'Final harrowing and leveling', 'Make the last pass so it is done by the day before sowing, and level very carefully under shallow water.', 'level'],
                [-1, -1, 'Cut the small canals', 'Right after leveling, cut small canals about 25 cm wide and 5 cm deep along the inside of the dikes and through the middle of the field.', 'canals'],
                [-1, -1, 'Let the seed sprout', 'Keep the seed covered in a shaded, airy spot and sow when the sprouts are just showing, 1 to 2 mm. Longer sprouts tangle and block the drum holes.', 'seed_incubate'],
                [0, 0, 'Drain and sow in rows', 'Drain the field to saturated mud and pull the drum seeder along the field in straight rows. Sow an extra 1 kg along one side of the field for filling gaps later.', 'sow'],
                [0, 3, 'Pre emergence herbicide', 'If weeds are a problem, spray a pre emergence herbicide within 3 days of sowing, following its label.', 'weeds_pre'],
                [0, 7, 'Guard the seed', 'Chase birds off, fill rat burrows with mud and water, and keep the dikes clean.', 'guard'],
                [0, 14, 'Keep the soil wet, not flooded', 'Hold back irrigation for the first 10 to 14 days so the soil stays saturated with no standing water.', 'keep_wet'],
                [6, 9, 'Early post emergence herbicide', 'If many weeds still come up, spray an early post emergence herbicide 6 to 9 days after sowing, by its label.', 'weeds_post'],
                [7, 10, 'Fill the bare patches', 'Replant bare patches more than 20 cm across with seedlings from the extra seed sown along the edge.', 'replant'],
                [10, 14, 'First water', 'From about 10 days after sowing, and by day 14 at the latest, let water in to 2 to 3 cm.', 'first_water'],
            ],
        ],
        'rice_dsr_dry' => [
            'label' => 'Dry seeded rice',
            'guide' => 'direct-seeded-rice-land-preparation',
            'steps' => [
                [-60, -30, 'Shallow dry tillage', 'Right after the last harvest, or with the first rains, run a shallow dry tillage so later rain soaks in (PhilRice).', 'crack_plow'],
                [-45, -35, 'Check the planting schedule in your area', 'Ask the municipal agriculture office when your area plants, and plant with your neighbors.', 'calendar'],
                [-45, -30, 'Fix the dikes before the rains', 'Patch and pack the dikes, about 15 cm high and 20 cm wide, so the first rains stay in the field.', 'dikes'],
                [-35, -28, 'Till once the soil is workable', 'When the first rains or an irrigation have softened the soil, till it 5 to 10 cm deep for a fine seedbed. Do not work it too wet (IRRI).', 'plow'],
                [-30, -1, 'Let the field rest', 'Leave at least 30 days between the last harvest and sowing, with no rice growing on the field.', 'rest'],
                [-28, -21, 'Harrow fine and level', 'Harrow to a fine, crumbly bed and level it carefully. Laser leveling is best where you can get it (IRRI).', 'harrow_level'],
                [-25, -14, 'Wake the weed seeds', 'Let the rains or one or two irrigations wet the leveled field so the weed seeds in the topsoil sprout (IRRI).', 'stale_wet'],
                [-5, -1, 'Kill the weeds that came up', 'Kill the weed seedlings with a very shallow harrowing or a non selective herbicide used by its label. Do not till deep again (IRRI).', 'stale_kill'],
                [0, 0, 'Sow into moist soil', 'Sow dry seed into moist topsoil, 1 to 2 cm deep and never deeper than 3 cm, in rows about 20 cm apart: about 40 to 60 kg per hectare drilled or dibbled, or 60 to 80 kg if broadcast and covered.', 'sow'],
                [0, 3, 'Pre emergence herbicide', 'Spray a pre emergence herbicide on moist soil within 3 days of sowing, following its label. Weeds are the biggest threat to dry seeded rice.', 'weeds_pre'],
                [0, 21, 'Do not flood the young crop', 'Keep the field moist but not flooded until the seedlings have 3 to 4 leaves, and drain off water that pools after heavy rain.', 'no_flood'],
                [10, 14, 'Fill the gaps', 'Fill bare rows with extra seed, or seedlings moved from crowded spots, before 14 days.', 'replant'],
            ],
        ],
        'corn' => [
            'label' => 'Corn and sorghum',
            'guide' => 'corn-land-preparation',
            'steps' => [
                [-30, -21, 'Clear the field', 'Cut and remove the weeds and the old crop. Burn nothing: chop it and leave it to rot, or compost it.', 'clear'],
                [-21, -14, 'Plow', 'Plow 15 to 20 cm deep when the soil is moist but not sticky. Leave the clods to dry and break for about a week.', 'plow'],
                [-10, -7, 'Harrow', 'Harrow once or twice to a fine bed, crossing the plowing direction.', 'harrow'],
                [-3, -1, 'Furrow', 'Open furrows about 75 cm apart across the slope. On flat, wet ground, make drainage canals so water does not stand in the wet season.', 'furrow'],
                [-1, 0, 'Basal fertilizer and planting', 'Spread the basal fertilizer and manure in the furrows, cover it with a little soil, and plant the seed beside it, never touching it.', 'plant'],
                [0, 3, 'Weeds', 'Apply a pre emergence herbicide right after planting if weeds are heavy, as the label says.', 'weeds'],
            ],
        ],
        'legume' => [
            'label' => 'Mungbean, peanut, soybean and other legumes',
            'guide' => 'legume-land-preparation',
            'steps' => [
                [-21, -14, 'Clear or plow', 'After rice, you can plant straight into the stubble without plowing while the soil is still moist. Otherwise plow once when the soil is workable.', 'plow'],
                [-10, -7, 'Harrow', 'Harrow to a fine, crumbly bed. Peanut needs loose soil so its pegs can push into the ground.', 'harrow'],
                [-3, -1, 'Furrows and drainage', 'Open furrows 50 to 60 cm apart and drainage canals around the field. Legumes hate wet feet.', 'furrow'],
                [-1, 0, 'Plant', 'Plant in the furrows or dibble holes. Treat the seed with a rhizobium inoculant where it is available.', 'plant'],
            ],
        ],
        'vegetable' => [
            'label' => 'Vegetables on beds',
            'guide' => 'vegetable-land-preparation',
            'steps' => [
                [-30, -25, 'Sow the nursery', 'For transplanted vegetables (tomato, eggplant, pepper, cabbage), sow the seedlings in trays or a nursery now. They are ready to move at 3 to 4 weeks.', 'nursery'],
                [-21, -14, 'Clear and plow', 'Clear the field and plow 20 to 25 cm deep. Leave the clods a week to dry and break.', 'plow'],
                [-14, -10, 'Harrow and spread compost', 'Harrow to a fine soil and spread well rotted compost or manure, then harrow it in.', 'harrow'],
                [-10, -7, 'Form the beds', 'Make raised beds about 1 meter wide with 30 to 50 cm paths. Make them 20 to 30 cm high in the wet season so the roots stay out of standing water.', 'beds'],
                [-3, 0, 'Mulch and holes', 'Lay plastic or straw mulch on the beds and punch the planting holes at the right spacing. Water the beds the day before planting.', 'mulch'],
                [0, 0, 'Transplant', 'Transplant in the late afternoon and water right after, so the seedlings settle overnight.', 'plant'],
            ],
        ],
        'cucurbit' => [
            'label' => 'Gourds and melons',
            'guide' => 'cucurbit-land-preparation',
            'steps' => [
                [-21, -14, 'Clear and plow', 'Clear and plow the field 20 to 25 cm deep.', 'plow'],
                [-14, -10, 'Harrow and compost', 'Harrow to a fine soil and work in compost or manure.', 'harrow'],
                [-10, -7, 'Beds or hills', 'Make raised beds or hills, wide apart: melons and squash run far, and ampalaya, upo and patola climb a trellis.', 'beds'],
                [-3, 0, 'Mulch', 'Lay plastic mulch on the beds and punch the holes. It keeps the fruit clean and the weeds down.', 'mulch'],
                [0, 0, 'Plant', 'Plant the seed or transplant the seedlings.', 'plant'],
                [7, 14, 'Build the trellis', 'For ampalaya, upo and patola, put up the trellis before the vines start to run.', 'trellis'],
            ],
        ],
        'root' => [
            'label' => 'Root crops',
            'guide' => 'root-crop-land-preparation',
            'steps' => [
                [-21, -14, 'Clear and plow', 'Clear the field and plow 20 to 30 cm deep. Roots need loose soil to swell.', 'plow'],
                [-10, -7, 'Harrow', 'Harrow once or twice to break the clods.', 'harrow'],
                [-3, -1, 'Ridges or mounds', 'Make ridges about 75 to 100 cm apart and 30 cm high for sweet potato, or mounds for gabi and ube. Cassava grows on flat or ridged ground.', 'ridges'],
                [-1, 0, 'Prepare the planting material', 'Cut healthy vine tips (kamote), stem cuttings (cassava) or setts (gabi, ube) from a clean, pest free source.', 'material'],
                [0, 0, 'Plant', 'Plant on the ridges with the soil moist.', 'plant'],
            ],
        ],
        'bulb' => [
            'label' => 'Onion and garlic',
            'guide' => 'onion-garlic-land-preparation',
            'steps' => [
                [-45, -40, 'Sow the onion seedbed', 'For onion grown from seedlings, sow the seedbed now. The seedlings are ready to move at about 40 to 45 days.', 'nursery'],
                [-21, -14, 'Clear and plow', 'Plow the field after the rice harvest, once the soil is workable.', 'plow'],
                [-14, -7, 'Harrow fine', 'Harrow two or three times to a fine, even soil. Onion and garlic have short roots and need a smooth bed.', 'harrow'],
                [-7, -3, 'Beds and canals', 'Make beds about 1 meter wide with canals between them for irrigation and drainage.', 'beds'],
                [-3, 0, 'Mulch', 'Spread rice straw mulch on the beds, especially for garlic. It keeps the soil moist and the weeds down.', 'mulch'],
                [0, 0, 'Plant', 'Transplant the onion seedlings or plant the garlic cloves, pointed end up.', 'plant'],
            ],
        ],
        'sugarcane' => [
            'label' => 'Sugarcane',
            'guide' => 'sugarcane-land-preparation',
            'steps' => [
                [-60, -45, 'Clear and subsoil', 'Clear the field and break the hardpan with a subsoiler where the soil is compacted.', 'subsoil'],
                [-45, -30, 'Deep plowing', 'Plow 30 to 40 cm deep. Sugarcane stays in the field for years of ratoons, so deep preparation pays for a long time.', 'plow'],
                [-21, -14, 'Harrow', 'Harrow twice to break the clods.', 'harrow'],
                [-7, -1, 'Furrow', 'Open furrows 1.2 to 1.5 meters apart, 20 to 30 cm deep.', 'furrow'],
                [-1, 0, 'Prepare and plant the setts', 'Cut setts with two or three buds from healthy cane, treat them as recommended, and lay them in the furrows.', 'plant'],
            ],
        ],
        'banana' => [
            'label' => 'Banana and abaca',
            'guide' => 'banana-land-preparation',
            'steps' => [
                [-45, -30, 'Clear and lay out', 'Clear the field and stake the planting points, about 2 to 3 meters apart.', 'clear'],
                [-30, -21, 'Drainage', 'Dig drainage canals where water stands. Banana roots rot in waterlogged ground.', 'drainage'],
                [-21, -14, 'Dig the holes', 'Dig holes about 60 cm wide and deep. Keep the topsoil apart.', 'holes'],
                [-14, -7, 'Fill the holes', 'Mix the topsoil with compost or manure and fill the holes back.', 'fill'],
                [0, 0, 'Plant', 'Plant clean, tissue cultured plantlets or healthy suckers at the start of the rains.', 'plant'],
            ],
        ],
        'tree' => [
            'label' => 'Fruit and plantation trees',
            'guide' => 'fruit-tree-land-preparation',
            'steps' => [
                [-90, -60, 'Clear and lay out', 'Clear the land and stake the planting points at the spacing the tree needs. On slopes, follow the contour.', 'clear'],
                [-60, -45, 'Windbreak and shade', 'Plant windbreaks on the typhoon side, and fast shade trees where young cacao or coffee need them.', 'windbreak'],
                [-45, -30, 'Dig the holes', 'Dig holes 60 cm to 1 meter wide and deep, keeping the topsoil apart.', 'holes'],
                [-30, -14, 'Fill the holes', 'Mix the topsoil with compost or manure and fill the holes back, a little above the ground so it settles level.', 'fill'],
                [0, 0, 'Plant', 'Plant grafted or marcotted trees from an accredited nursery at the start of the rains, and stake them.', 'plant'],
            ],
        ],
    ];

    /** Steps a soil trouble adds, keyed as SoilConditions names them: [from, to, what, how, key]. */
    public const SOIL_STEPS = [
        'acidic' => [
            [-60, -45, 'Test the soil', 'Take a soil sample to the regional soils laboratory (BSWM) early enough for the result to come back before plowing, or check it with a soil test kit.', 'soil_test'],
            [-30, -14, 'Apply lime', 'Spread agricultural lime or dolomite at the rate the test gives and plow or harrow it in, at least two weeks before planting. For flooded rice, lime only strongly acidic soil (around pH below 5): flooding itself raises the pH. Avoid ammonium sulfate on strongly acidic soil.', 'lime'],
        ],
        'saline' => [
            [-35, -25, 'Choose a salt tolerant variety', 'Pick a salt tolerant variety before you buy or soak the seed, not at planting time.', 'salt_variety'],
            [-30, -14, 'Flush the salt', 'Flood the field with good water and drain it, two or three times, to wash the salt below the roots. For rice, keep water standing after the last flush so the salt does not climb back. For vegetables, plant on raised beds.', 'flush_salt'],
        ],
        'sodic' => [
            [-45, -30, 'Apply gypsum', 'Spread gypsum at the rate a soil test gives and work it in. It replaces the sodium that makes the soil crust and seal.', 'gypsum'],
            [-21, -14, 'Leach', 'Flood and drain the field to carry the sodium away after the gypsum.', 'leach'],
        ],
        'acid_sulfate' => [
            [-30, -1, 'Keep it wet', 'Do not let acid sulfate soil dry out and crack: drying releases the acid. Keep it wet through land preparation and apply lime only as a test advises.', 'keep_wet_soil'],
        ],
    ];

    /** The rice plan for a planting method of the Land Prep Planner. */
    public const RICE_METHOD_PLANS = [
        'tpr_manual' => 'rice_tpr',
        'tpr_mechanical' => 'rice_tpr_mech',
        'dsr_wet_broadcast' => 'rice_dsr_wet',
        'dsr_wet_drum' => 'rice_dsr_drum',
        'dsr_dry' => 'rice_dsr_dry',
    ];

    /**
     * The plan an app crop key uses. Rice asks how it is planted: pass the
     * planting method (a RICE_METHOD_PLANS key) and the plan follows it; with
     * none, the old crop keys still answer (rice is hand transplanted).
     */
    public static function planFor(?string $cropKey, ?string $method = null): string
    {
        $k = (string) $cropKey;
        if (str_starts_with($k, 'rice') && $method && isset(self::RICE_METHOD_PLANS[$method])) {
            return self::RICE_METHOD_PLANS[$method];
        }
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

    /** Everything the old planner page needs, as plain data for its script. */
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
