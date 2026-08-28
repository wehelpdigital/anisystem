<?php

namespace Database\Seeders;

use App\Models\AsActivityScene;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * One moving picture per kind of working day.
 *
 * The blurb is written for somebody who has not opened the board yet — the
 * card says how many activities there are, the picture says what kind, and
 * this line says what that kind of day asks of you.
 */
class ActivitySceneSeeder extends Seeder
{
    public function run(): void
    {
        // [key, label, scene, hue, blurb]
        $rows = [
            ['land_prep', 'Land preparation', 'plough', 'soil',
                'Ground work today. Soft soil ploughs clean and hard soil fights the tines, so start while the moisture is still in it.'],

            ['equipment_prep', 'Equipment preparation', 'toolbox', 'slate',
                'Setting up today. An hour with the sprayer and the blades now is the hour you do not lose in the middle of the field tomorrow.'],

            ['seed_treatment', 'Seed treatment', 'seedbed', 'amber',
                'Seed work today. Treat and dry in the shade, and keep the treated seed well away from anything anybody eats.'],

            ['planting', 'Planting', 'planting', 'leaf',
                'Planting today. Spacing you set this morning is spacing you live with all season, so take the extra minute on the first row.'],

            ['irrigation', 'Irrigation', 'water', 'water',
                'Water today. Walk the bunds before you open the gate — a leak found now is cheaper than a field drained by morning.'],

            ['fertilizer', 'Fertiliser — granular', 'granular', 'amber',
                'Granular going out today. Spread on damp soil and keep it off the leaf; a granule sitting on a wet blade will burn it.'],

            ['foliar_spray', 'Foliar spray', 'sprayer', 'leaf',
                'Spraying today. Early or late, never in the heat, and never into the wind — the drift lands on somebody, and it is usually you.'],

            ['herbicide', 'Herbicide', 'herbicide', 'violet',
                'Herbicide today. Drain the field first if you can, and mark the sprayer so it never carries anything else again.'],

            ['pesticide', 'Pesticide / insecticide', 'pesticide', 'rose',
                'Insecticide today. Gloves and a mask that still breathes hard, and keep everybody else out of the field and downwind.'],

            ['fungicide', 'Fungicide', 'fungicide', 'sky',
                'Fungicide today. It protects what is still healthy rather than curing what is not, so cover the new growth first.'],

            ['copper_fungicide', 'Copper fungicide', 'fungicide', 'sky',
                'Copper going out today. It stains and it builds up in soil, so mix only what the field needs and rinse the tank three times.'],

            ['microbial', 'Microbial / bio', 'microbe', 'violet',
                'Living product today. Cool, shaded and used the same day it is mixed — sun and a hot tank kill what you paid for.'],

            ['harvest', 'Harvest', 'harvest', 'sun',
                'Harvest today. Get the crew fed and watered before the heat, and have the sacks and the tarp out before the first cut.'],

            ['monitoring', 'Monitoring', 'scout', 'leaf',
                'Scouting today. Walk the same five spots you always walk — a pattern only shows if you look in the same places.'],

            ['service', 'Service', 'service', 'slate',
                'Service work today. Whoever is coming, have the field and the gate ready so the visit is not spent waiting.'],

            ['worker_payroll', 'Worker checklist', 'crew', 'amber',
                'People on the board today. Tick who was there while you can still see them; a list written at night is a list that argues.'],

            ['reminder_checklist', 'Reminder checklist', 'checklist', 'slate',
                'Errands today. Nobody is paid for these and nothing grows from them, which is exactly why they are the ones that slip.'],

            ['other', 'On the board', 'mixed', 'leaf',
                'Something on the board today. Open it before the day gets away from you.'],

            ['quiet', 'A quiet day', 'quiet', 'sky',
                'Nothing planned today. A quiet board is the day to walk the field, fix what is broken and look at the week ahead.'],
        ];

        // The hue set is small on purpose; anything unknown falls back in the
        // model rather than painting a colour the stylesheet has never heard of.
        $allowed = AsActivityScene::HUES;
        $keys = [];
        foreach ($rows as $i => [$key, $label, $scene, $hue, $blurb]) {
            $keys[] = $key;
            AsActivityScene::updateOrCreate(['key' => $key], [
                'label' => $label,
                'scene' => $scene,
                'hue' => in_array($hue, $allowed, true) ? $hue : 'leaf',
                'blurb' => $blurb,
                'sortOrder' => $i,
                'deleteStatus' => 1,
            ]);
        }

        // Anything that used to be here and is not now is retired rather than
        // deleted — the app's own convention, and it keeps an edited row from
        // vanishing under somebody who was still using it.
        AsActivityScene::query()->whereNotIn('key', $keys)->update(['deleteStatus' => 0]);

        Cache::forget('as_activity_scenes.map');
        $this->command?->info('Activity scenes: ' . count($rows) . ' kinds of day.');
    }
}
