<?php

namespace App\Support;

/**
 * Weed control in rice by the age of the crop (2026-10-06).
 *
 * For each way of planting and each window of days after planting: what to
 * do, in order, and the active ingredients that work on each weed group at
 * that age. Built from PhilRice's eDamuhan recommendations and written in
 * Anee's words. Active ingredients only, never a product or brand name: a
 * farmer takes the ingredient to the store and reads the label there.
 *
 * A field with two or three groups at once takes the ingredients that are
 * in every one of those groups' lists (the app's own mixed lists are
 * exactly that overlap, checked when this file was written), so only the
 * per group lists live here. A window a group has no list for (grasses at
 * 31 to 40 days) means hand weeding and water, not a spray. One entry of
 * the app is left out on purpose: MCPA on the grass list at 21 to 30 days
 * after transplanting (a broadleaf killer that grasses, rice among them,
 * tolerate).
 *
 * Shown by the weed control helper on /weeds and on every weed profile.
 */
final class WeedControl
{
    public const METHODS = [
        'transplanted' => ['label' => 'Transplanted', 'after' => 'after transplanting', 'local' => 'Lipat tanim'],
        'direct' => ['label' => 'Direct seeded', 'after' => 'after seeding', 'local' => 'Sabog tanim'],
    ];

    public const GROUPS = [
        'grasses' => ['label' => 'Grasses', 'hint' => 'Round, hollow stems with joints, and leaves in two rows.'],
        'sedges' => ['label' => 'Sedges', 'hint' => 'Solid, three sided stems with no joints. Leaves in threes.'],
        'broadleaves' => ['label' => 'Broadleaves', 'hint' => 'Wide leaves with a net of veins.'],
    ];

    /**
     * Herbicide groups by the way they kill a weed (the HRAC numbers on a
     * label). Spraying one group season after season breeds weeds it can no
     * longer kill, so the helper shows each ingredient's group.
     */
    public const MODES = [
        1 => 'ACCase inhibitors, grass killers',
        2 => 'ALS inhibitors',
        3 => 'Root and shoot growth inhibitors',
        4 => 'Synthetic auxins, broadleaf killers',
        5 => 'Photosynthesis inhibitors (propanil)',
        6 => 'Photosynthesis inhibitors (bentazone)',
        13 => 'Pigment inhibitors (clomazone)',
        14 => 'PPO inhibitors',
        15 => 'Seedling growth inhibitors',
        27 => 'HPPD inhibitors',
    ];

    /** Each active ingredient and the group or groups it belongs to. */
    public const INGREDIENTS = [
        '2,4 D amine' => [4],
        '2,4 D IBE' => [4],
        'Bensulfuron methyl' => [2],
        'Bentazone' => [6],
        'Bispyribac sodium' => [2],
        'Butachlor' => [15],
        'Butachlor plus 2,4 D IBE' => [4, 15],
        'Butachlor plus propanil' => [5, 15],
        'Butachlor with safener' => [15],
        'Clomazone' => [13],
        'Clomazone plus propanil' => [5, 13],
        'Cyhalofop butyl' => [1],
        'Cyhalofop butyl plus bispyribac sodium' => [1, 2],
        'Cyhalofop butyl plus ethoxysulfuron' => [1, 2],
        'Cyhalofop butyl plus florpyrauxifen benzyl' => [1, 4],
        'Fenoxaprop P ethyl plus ethoxysulfuron' => [1, 2],
        'Flucetosulfuron' => [2],
        'MCPA' => [4],
        'Metamifop' => [1],
        'Metamifop plus penoxsulam' => [1, 2],
        'Metsulfuron methyl plus chlorimuron ethyl' => [2],
        'Oxadiazon' => [14],
        'Pendimethalin' => [3],
        'Penoxsulam' => [2],
        'Penoxsulam plus butachlor' => [2, 15],
        'Penoxsulam plus cyhalofop butyl' => [1, 2],
        'Pretilachlor' => [15],
        'Pretilachlor plus butachlor' => [15],
        'Profoxydim' => [1],
        'Pyrazosulfuron ethyl plus pretilachlor' => [2, 15],
        'Pyribenzoxim' => [2],
        'Pyribenzoxim plus cyhalofop butyl' => [1, 2],
        'Tefuryltrione plus triafamone' => [2, 27],
    ];

    public const TABLE = [
        'transplanted' => [
            '0-5' => [
                'label' => '0 to 5 days',
                'timing' => 'pre',
                'stage' => 'Before the weeds come up',
                'steps' => [
                    'Keep the soil saturated after transplanting. Wet soil holds back many weed seeds.',
                    'If this field had a lot of weeds last season, apply a pre emergence herbicide now, before the weeds come up. Read and follow the label on the bottle, box or sachet.',
                ],
                'grasses' => [
                    'Butachlor',
                    'Butachlor plus 2,4 D IBE',
                    'Clomazone',
                    'Oxadiazon',
                    'Pendimethalin',
                    'Penoxsulam plus butachlor',
                    'Pretilachlor',
                    'Pyrazosulfuron ethyl plus pretilachlor',
                    'Tefuryltrione plus triafamone',
                ],
                'sedges' => [
                    'Butachlor',
                    'Butachlor plus 2,4 D IBE',
                    'Oxadiazon',
                    'Penoxsulam plus butachlor',
                    'Pretilachlor',
                    'Pyrazosulfuron ethyl plus pretilachlor',
                    'Tefuryltrione plus triafamone',
                ],
                'broadleaves' => [
                    'Bensulfuron methyl',
                    'Butachlor plus 2,4 D IBE',
                    'Oxadiazon',
                    'Penoxsulam plus butachlor',
                    'Pretilachlor',
                    'Pyrazosulfuron ethyl plus pretilachlor',
                    'Tefuryltrione plus triafamone',
                ],
            ],
            '6-10' => [
                'label' => '6 to 10 days',
                'timing' => 'early',
                'stage' => 'Weeds at two to three leaves',
                'steps' => [
                    'Pull the weeds by hand while they are small.',
                    'If hand weeding is not possible, apply a post emergence herbicide. Read and follow the label on the bottle, box or sachet.',
                    'Bring the water back 1 to 3 days after you spray, 3 to 5 cm deep, and hold it there for 4 to 5 days so the weeds stay under water. Keep the young rice above it.',
                ],
                'grasses' => [
                    'Bispyribac sodium',
                    'Butachlor plus propanil',
                    'Clomazone plus propanil',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Flucetosulfuron',
                    'Pendimethalin',
                    'Penoxsulam plus cyhalofop butyl',
                ],
                'sedges' => [
                    'Bispyribac sodium',
                    'Butachlor plus propanil',
                    'Clomazone plus propanil',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Flucetosulfuron',
                    'Penoxsulam plus cyhalofop butyl',
                ],
                'broadleaves' => [
                    'Bispyribac sodium',
                    'Bensulfuron methyl',
                    'Butachlor plus propanil',
                    'Clomazone plus propanil',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Flucetosulfuron',
                    'Pendimethalin',
                    'Penoxsulam plus cyhalofop butyl',
                ],
            ],
            '11-20' => [
                'label' => '11 to 20 days',
                'timing' => 'post',
                'stage' => 'Weeds growing with the rice',
                'steps' => [
                    'Keep water in the field. It stops weed seeds from sprouting and holds back the seedlings that already did.',
                    'Weed by hand or run a rotary weeder between the rows. It cuts, tramples and buries the weeds.',
                    'Let nature help. Grow Azolla on the water, and spare the small black Altica beetle, which eats the leaves of Ludwigia weeds.',
                    'If weeding and nature cannot keep up, apply a post emergence herbicide. Read and follow the label on the bottle, box or sachet.',
                    'Bring the water back 1 to 3 days after you spray, 3 to 5 cm deep, and hold it there for 4 to 5 days so the weeds stay under water. Keep the young rice above it.',
                ],
                'grasses' => [
                    'Bispyribac sodium',
                    'Cyhalofop butyl plus ethoxysulfuron',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Flucetosulfuron',
                    'Metamifop plus penoxsulam',
                    'Penoxsulam plus cyhalofop butyl',
                    'Profoxydim',
                ],
                'sedges' => [
                    'Bentazone',
                    'Bispyribac sodium',
                    'Cyhalofop butyl plus ethoxysulfuron',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Flucetosulfuron',
                    'Metamifop plus penoxsulam',
                    'Penoxsulam plus cyhalofop butyl',
                ],
                'broadleaves' => [
                    'Bentazone',
                    'Bispyribac sodium',
                    'Cyhalofop butyl plus ethoxysulfuron',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Flucetosulfuron',
                    'Metamifop plus penoxsulam',
                    'Penoxsulam plus cyhalofop butyl',
                ],
            ],
            '21-30' => [
                'label' => '21 to 30 days',
                'timing' => 'post',
                'stage' => 'Rice tillering',
                'steps' => [
                    'Top up the water if the level has dropped.',
                    'Weed by hand or run a rotary weeder between the rows. It cuts, tramples and buries the weeds.',
                    'Let nature help. Grow Azolla on the water, and spare the small black Altica beetle, which eats the leaves of Ludwigia weeds.',
                    'If weeding and nature cannot keep up, apply a post emergence herbicide. Read and follow the label on the bottle, box or sachet.',
                    'Bring the water back 1 to 3 days after you spray, 3 to 5 cm deep, and hold it there for 4 to 5 days so the weeds stay under water.',
                ],
                'grasses' => [
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Metamifop plus penoxsulam',
                ],
                'sedges' => [
                    '2,4 D amine',
                    '2,4 D IBE',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'MCPA',
                    'Metamifop plus penoxsulam',
                    'Metsulfuron methyl plus chlorimuron ethyl',
                ],
                'broadleaves' => [
                    '2,4 D amine',
                    '2,4 D IBE',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'MCPA',
                    'Metamifop plus penoxsulam',
                    'Metsulfuron methyl plus chlorimuron ethyl',
                ],
            ],
            '31-40' => [
                'label' => '31 to 40 days',
                'timing' => 'rescue',
                'stage' => 'Rice near full tillering',
                'steps' => [
                    'Top up the water if the level has dropped.',
                    'Pull the weeds by hand.',
                    'Spare the small black Altica beetle, which eats the leaves of Ludwigia weeds.',
                    'Spray only as a rescue, when weeding and nature have failed. Use a post emergence herbicide and follow the label on the bottle, box or sachet.',
                ],
                'grasses' => [],
                'sedges' => [
                    'Metsulfuron methyl plus chlorimuron ethyl',
                ],
                'broadleaves' => [
                    'Metsulfuron methyl plus chlorimuron ethyl',
                ],
            ],
        ],
        'direct' => [
            '0-5' => [
                'label' => '0 to 5 days',
                'timing' => 'pre',
                'stage' => 'Before the weeds come up',
                'steps' => [
                    'Keep the soil saturated after seeding. Wet soil holds back many weed seeds.',
                    'If this field had a lot of weeds last season, apply a pre emergence herbicide now, before the weeds come up. Read and follow the label on the bottle, box or sachet.',
                ],
                'grasses' => [
                    'Butachlor',
                    'Butachlor plus 2,4 D IBE',
                    'Butachlor with safener',
                    'Clomazone',
                    'Oxadiazon',
                    'Pendimethalin',
                    'Pretilachlor',
                    'Pretilachlor plus butachlor',
                ],
                'sedges' => [
                    'Butachlor',
                    'Butachlor plus 2,4 D IBE',
                    'Butachlor with safener',
                    'Oxadiazon',
                    'Pretilachlor',
                    'Pretilachlor plus butachlor',
                ],
                'broadleaves' => [
                    'Butachlor plus 2,4 D IBE',
                    'Butachlor with safener',
                    'Oxadiazon',
                    'Pendimethalin',
                    'Pretilachlor',
                    'Pretilachlor plus butachlor',
                ],
            ],
            '6-10' => [
                'label' => '6 to 10 days',
                'timing' => 'early',
                'stage' => 'Weeds at two to three leaves',
                'steps' => [
                    'Apply an early post emergence herbicide while the weeds are still small. Read and follow the label on the bottle, box or sachet.',
                    'Bring the water back 1 to 3 days after you spray, 3 to 5 cm deep, and hold it there for 4 to 5 days so the weeds stay under water. Keep the young rice above it.',
                ],
                'grasses' => [
                    'Bispyribac sodium',
                    'Butachlor plus propanil',
                    'Cyhalofop butyl',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Flucetosulfuron',
                    'Metamifop',
                    'Pendimethalin',
                    'Penoxsulam',
                    'Penoxsulam plus cyhalofop butyl',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
                'sedges' => [
                    'Bispyribac sodium',
                    'Butachlor plus propanil',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Flucetosulfuron',
                    'Penoxsulam',
                    'Penoxsulam plus cyhalofop butyl',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
                'broadleaves' => [
                    'Bispyribac sodium',
                    'Butachlor plus propanil',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Flucetosulfuron',
                    'Pendimethalin',
                    'Penoxsulam',
                    'Penoxsulam plus cyhalofop butyl',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
            ],
            '11-20' => [
                'label' => '11 to 20 days',
                'timing' => 'post',
                'stage' => 'Weeds growing with the rice',
                'steps' => [
                    'Keep water in the field. It stops weed seeds from sprouting and holds back the seedlings that already did.',
                    'Pull the weeds by hand.',
                    'Spare the small black Altica beetle, which eats the leaves of Ludwigia weeds.',
                    'If hand weeding and nature cannot keep up, apply a post emergence herbicide. Read and follow the label on the bottle, box or sachet.',
                    'Bring the water back 1 to 3 days after you spray, 3 to 5 cm deep, and hold it there for 4 to 5 days so the weeds stay under water. Keep the young rice above it.',
                ],
                'grasses' => [
                    'Bispyribac sodium',
                    'Cyhalofop butyl plus bispyribac sodium',
                    'Cyhalofop butyl plus ethoxysulfuron',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Flucetosulfuron',
                    'Metamifop',
                    'Penoxsulam plus cyhalofop butyl',
                    'Profoxydim',
                    'Pyribenzoxim',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
                'sedges' => [
                    'Bispyribac sodium',
                    'Cyhalofop butyl plus bispyribac sodium',
                    'Cyhalofop butyl plus ethoxysulfuron',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Flucetosulfuron',
                    'Penoxsulam plus cyhalofop butyl',
                    'Pyribenzoxim',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
                'broadleaves' => [
                    'Bispyribac sodium',
                    'Cyhalofop butyl plus bispyribac sodium',
                    'Cyhalofop butyl plus florpyrauxifen benzyl',
                    'Fenoxaprop P ethyl plus ethoxysulfuron',
                    'Flucetosulfuron',
                    'Penoxsulam plus cyhalofop butyl',
                    'Pyribenzoxim',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
            ],
            '21-30' => [
                'label' => '21 to 30 days',
                'timing' => 'post',
                'stage' => 'Rice tillering',
                'steps' => [
                    'Top up the water if the level has dropped.',
                    'Pull the weeds by hand.',
                    'Spare the small black Altica beetle, which eats the leaves of Ludwigia weeds.',
                    'If hand weeding and nature cannot keep up, apply a post emergence herbicide. Read and follow the label on the bottle, box or sachet.',
                    'Bring the water back 1 to 3 days after you spray, 3 to 5 cm deep, and hold it there for 4 to 5 days so the weeds stay under water.',
                ],
                'grasses' => [
                    'Cyhalofop butyl plus bispyribac sodium',
                    'Metamifop',
                    'Pyribenzoxim',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
                'sedges' => [
                    'Cyhalofop butyl plus bispyribac sodium',
                    'Pyribenzoxim',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
                'broadleaves' => [
                    'Cyhalofop butyl plus bispyribac sodium',
                    'Pyribenzoxim',
                    'Pyribenzoxim plus cyhalofop butyl',
                ],
            ],
        ],
    ];
}
