<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

/**
 * What the crop in this lot looks like right now.
 *
 * Eighty-five crops, and a drawing for each of them at each point of its
 * season would be five hundred drawings that mostly agreed with each other. A
 * mango and a santol are the same silhouette; so are a mungbean and a soybean.
 * What differs is the FAMILY — the shape of the plant — and the BAND, how far
 * through the season it is. Fifteen families and six bands is ninety pictures,
 * and every one of them is honestly different from its neighbours.
 *
 * The family a crop belongs to and the recipe for drawing it are facts about
 * the plant, so they live in code. What each band is CALLED for that family
 * ("Tillering" for a grain, "Bulking" for a root, "Flushing" for a tree) and
 * what it says are opinions about farming, so they live in the table.
 */
class AsCropScene extends BaseModel
{
    protected $table = 'as_crop_scenes';

    protected $fillable = ['family', 'band', 'label', 'hue', 'blurb', 'deleteStatus'];

    protected $casts = ['band' => 'integer', 'deleteStatus' => 'integer'];

    /** Six points in any season, whatever the crop is. */
    public const BANDS = ['seed', 'sprout', 'vegetative', 'flower', 'fill', 'harvest'];

    /**
     * How to draw each family.
     *
     * `leaf`   the leaf shape — blade (grass), broad, trifoliate, lobed, frond, sword, needle
     * `stem`   how it stands — tuft, stalk, cane, vine, trunk, bush, none
     * `fruit`  what it carries when it carries anything — panicle, cob, pod,
     *          berry, globe, head, hanging, bunch, nut, root, bulb, none
     * `below`  does the thing you harvest grow under the line
     * `leafHue`/`fruitHue`/`stemHue` the three colours the drawing is made of
     */
    public const FAMILIES = [
        'grain'    => ['leaf' => 'blade', 'stem' => 'tuft', 'fruit' => 'panicle', 'below' => false,
                       'leafHue' => '#6aa84f', 'stemHue' => '#7fae55', 'fruitHue' => '#d9a441'],
        'corn'     => ['leaf' => 'blade', 'stem' => 'stalk', 'fruit' => 'cob', 'below' => false,
                       'leafHue' => '#5f9c46', 'stemHue' => '#6f9b4c', 'fruitHue' => '#e8b93f'],
        'cane'     => ['leaf' => 'blade', 'stem' => 'cane', 'fruit' => 'none', 'below' => false,
                       'leafHue' => '#67a24b', 'stemHue' => '#a3823f', 'fruitHue' => '#cfa74a'],
        'legume'   => ['leaf' => 'trifoliate', 'stem' => 'bush', 'fruit' => 'pod', 'below' => false,
                       'leafHue' => '#5aa04a', 'stemHue' => '#6f9b4c', 'fruitHue' => '#8cc152'],
        'root'     => ['leaf' => 'broad', 'stem' => 'tuft', 'fruit' => 'root', 'below' => true,
                       'leafHue' => '#4f9a45', 'stemHue' => '#6f9b4c', 'fruitHue' => '#c9752f'],
        'leafy'    => ['leaf' => 'broad', 'stem' => 'none', 'fruit' => 'head', 'below' => false,
                       'leafHue' => '#63ab4c', 'stemHue' => '#7cb45c', 'fruitHue' => '#8fc96a'],
        'vine'     => ['leaf' => 'lobed', 'stem' => 'vine', 'fruit' => 'hanging', 'below' => false,
                       'leafHue' => '#569b45', 'stemHue' => '#6f9b4c', 'fruitHue' => '#4f9e3f'],
        'fruitveg' => ['leaf' => 'broad', 'stem' => 'bush', 'fruit' => 'berry', 'below' => false,
                       'leafHue' => '#4f9a45', 'stemHue' => '#6f9b4c', 'fruitHue' => '#d4483f'],
        'bulb'     => ['leaf' => 'needle', 'stem' => 'none', 'fruit' => 'bulb', 'below' => true,
                       'leafHue' => '#5fa64f', 'stemHue' => '#7cb45c', 'fruitHue' => '#d8c39a'],
        'banana'   => ['leaf' => 'frond', 'stem' => 'trunk', 'fruit' => 'bunch', 'below' => false,
                       'leafHue' => '#4f9a45', 'stemHue' => '#8a9a5b', 'fruitHue' => '#e0c04a'],
        'palm'     => ['leaf' => 'frond', 'stem' => 'trunk', 'fruit' => 'nut', 'below' => false,
                       'leafHue' => '#4c9b52', 'stemHue' => '#9a7b4f', 'fruitHue' => '#8d6a3f'],
        'tree'     => ['leaf' => 'broad', 'stem' => 'trunk', 'fruit' => 'globe', 'below' => false,
                       'leafHue' => '#417f3c', 'stemHue' => '#8a6a45', 'fruitHue' => '#e0912f'],
        'shrub'    => ['leaf' => 'broad', 'stem' => 'bush', 'fruit' => 'berry', 'below' => false,
                       'leafHue' => '#3f8c46', 'stemHue' => '#6e8a4c', 'fruitHue' => '#b6482f'],
        'spiky'    => ['leaf' => 'sword', 'stem' => 'none', 'fruit' => 'globe', 'below' => false,
                       'leafHue' => '#5f9a4a', 'stemHue' => '#7cb45c', 'fruitHue' => '#e0a635'],
        'mixed'    => ['leaf' => 'broad', 'stem' => 'tuft', 'fruit' => 'berry', 'below' => false,
                       'leafHue' => '#5a9f4b', 'stemHue' => '#7cb45c', 'fruitHue' => '#c9723a'],
    ];

    /** Which family each crop key belongs to. Anything unlisted is 'mixed'. */
    public const OF_CROP = [
        'rice' => 'grain', 'rice_upland' => 'grain', 'sorghum' => 'grain',
        'corn_yellow' => 'corn', 'corn_sweet' => 'corn', 'corn_glutinous' => 'corn',
        'sugarcane' => 'cane', 'bamboo' => 'cane', 'abaca' => 'cane',
        'mungbean' => 'legume', 'peanut' => 'legume', 'soybean' => 'legume',
        'stringbean' => 'legume', 'cowpea' => 'legume', 'wingedbean' => 'legume',
        'limabean' => 'legume', 'pigeonpea' => 'legume',
        'sweetpotato' => 'root', 'cassava' => 'root', 'taro' => 'root', 'ubi' => 'root',
        'potato' => 'root', 'carrot' => 'root', 'radish' => 'root', 'ginger' => 'root',
        'turmeric' => 'root',
        'pechay' => 'leafy', 'cabbage' => 'leafy', 'lettuce' => 'leafy', 'kangkong' => 'leafy',
        'mustard' => 'leafy', 'broccoli' => 'leafy', 'cauliflower' => 'leafy',
        'alugbati' => 'leafy', 'saluyot' => 'leafy', 'celery' => 'leafy',
        'ampalaya' => 'vine', 'cucumber' => 'vine', 'patola' => 'vine', 'upo' => 'vine',
        'sayote' => 'vine', 'watermelon' => 'vine', 'melon' => 'vine', 'squash' => 'vine',
        'tomato' => 'fruitveg', 'eggplant' => 'fruitveg', 'okra' => 'fruitveg',
        'chili' => 'fruitveg', 'bellpepper' => 'fruitveg', 'strawberry' => 'fruitveg',
        'onion' => 'bulb', 'onion_spring' => 'bulb', 'garlic' => 'bulb', 'shallot' => 'bulb',
        'banana' => 'banana', 'papaya' => 'banana',
        'coconut' => 'palm', 'oilpalm' => 'palm',
        'mango' => 'tree', 'calamansi' => 'tree', 'citrus' => 'tree', 'pomelo' => 'tree',
        'jackfruit' => 'tree', 'avocado' => 'tree', 'guava' => 'tree', 'lanzones' => 'tree',
        'rambutan' => 'tree', 'durian' => 'tree', 'mangosteen' => 'tree', 'chico' => 'tree',
        'atis' => 'tree', 'guyabano' => 'tree', 'santol' => 'tree', 'starapple' => 'tree',
        'tamarind' => 'tree', 'cashew' => 'tree', 'malunggay' => 'tree', 'rubber' => 'tree',
        'coffee' => 'shrub', 'cacao' => 'shrub', 'dragonfruit' => 'shrub',
        'tobacco' => 'shrub', 'cotton' => 'shrub',
        'pineapple' => 'spiky',
        'vegetables' => 'mixed',
    ];

    /** The family a crop key belongs to. */
    public static function familyFor(?string $crop): string
    {
        $key = \App\Support\CropStages::normalize($crop);

        return self::OF_CROP[$key] ?? 'mixed';
    }

    /**
     * Which of the six bands a stage sits in.
     *
     * By fraction rather than by name, for the same reason the colours are:
     * rice has eight stages, a mango tree has five, and "how far through is
     * this" is the one question both of them can answer.
     */
    public static function bandFor(?int $index, ?int $count): int
    {
        if ($index === null || ! $count || $count < 2) {
            return 0;
        }
        $b = (int) round(($index / max(1, $count - 1)) * 5);

        return max(0, min(5, $b));
    }

    /** Every band of every family, cached the way the skies are. */
    public static function map(): array
    {
        return Cache::remember('as_crop_scenes.map', 600, function () {
            $out = [];
            foreach (self::query()->where('deleteStatus', 1)->orderBy('family')->orderBy('band')->get() as $r) {
                $out[$r->family . ':' . $r->band] = [
                    'label' => $r->label,
                    'hue' => $r->hue,
                    'blurb' => (string) $r->blurb,
                ];
            }

            return $out;
        });
    }

    /** One band of one family, with an answer when the row is missing. */
    public static function one(string $family, int $band): array
    {
        $all = self::map();

        return $all[$family . ':' . $band]
            ?? $all['mixed:' . $band]
            ?? ['label' => 'Growing', 'hue' => 'leaf', 'blurb' => ''];
    }
}
