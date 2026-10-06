<?php

namespace App\Support;

/**
 * The Crop Pests and Crop Diseases catalogues (2026-10-07), built like the
 * weeds one (App\Support\WeedCatalogue): one profile page per pest or
 * disease (database/site-pages/{pests,diseases}/{slug}.json, editable in the
 * mother app), and here what the hub needs beside the pages: the shelves,
 * the finder's questions and each entry's facts.
 *
 * The entries themselves are generated (ProblemCatalogueData) from the
 * researched profiles; this class holds the words around them.
 */
final class ProblemCatalogue
{
    /** The crop shelves of both hubs: label, a local word, hue, icon path. */
    public const GROUPS = [
        'rice' => ['Rice', 'Palay', 98, 'M12 21V9m0 0c0-3 1.5-5.5 4-7m-4 7C12 6 10.5 3.5 8 2m4 13c2-2 4.5-3 7-3m-7 3c-2-2-4.5-3-7-3'],
        'corn' => ['Corn', 'Mais', 45, 'M12 21c-3 0-4.5-4-4.5-9S9 3 12 3s4.5 4 4.5 9-1.5 9-4.5 9zm-2.5-14h5m-5.5 4h6m-6 4h6'],
        'vegetables' => ['Vegetables', 'Gulay', 140, 'M7 21c-2-4-1-9 3-12m4 12c2-4 1-9-3-12M12 9c0-3 2-6 5-6-1 3-2.5 5-5 6zm0 0c0-3-2-6-5-6 1 3 2.5 5 5 6z'],
        'fruits' => ['Fruits and Plantation Crops', 'Niyog, saging, mangga', 25, 'M12 8c-4 0-7 3-7 7s3 6 7 6 7-2 7-6-3-7-7-7zm0 0c0-2 1-4 3-5'],
    ];

    /** Step one of the finder: the crop. */
    public const CROPS = [
        'rice' => ['Palay', 'Rice'], 'corn' => ['Mais', 'Corn'], 'vegetables' => ['Gulay', 'Vegetables'],
        'coconut' => ['Niyog', 'Coconut'], 'banana' => ['Saging', 'Banana'], 'mango' => ['Mangga', 'Mango'],
        'cacao' => ['Cacao', 'Cacao'], 'coffee' => ['Kape', 'Coffee'], 'papaya' => ['Papaya', 'Papaya'],
        'citrus' => ['Citrus', 'Calamansi and other citrus'], 'fruit' => ['Prutas', 'Other fruit trees'],
    ];

    /** Pests, step two: where the damage shows. */
    public const PARTS = [
        'leaves' => 'On the leaves', 'stems' => 'In the stems or tillers', 'grain' => 'On the panicles or ears',
        'fruit' => 'On fruits, pods or nuts', 'roots' => 'At the roots or base', 'seedlings' => 'On young seedlings',
        'whole' => 'The whole plant',
    ];

    /** Diseases, step two: what the farmer sees. */
    public const SIGNS = [
        'spots' => 'Spots or streaks', 'blight' => 'Large dead patches', 'wilting' => 'Wilting',
        'yellowing' => 'Yellowing', 'stunting' => 'Stunted plants', 'rot' => 'Rotting',
        'mold' => 'Powdery or fuzzy growth', 'curling' => 'Curled or mottled leaves',
    ];

    /** What each kind is called on a card, and its tint. */
    public const KINDS = [
        'Insect' => 98, 'Mite' => 15, 'Snail' => 190, 'Rodent' => 30, 'Bird' => 210,
        'Fungus' => 35, 'Bacterium' => 350, 'Virus' => 280, 'Viroid' => 300, 'Nematode' => 170, 'Water mold' => 200,
    ];

    /** The words of each hub, around its catalogue. */
    public const HUB = [
        'pests' => [
            'noun' => 'pest', 'nouns' => 'pests',
            'catalogue' => 'Crop Pests of Philippine Farms',
            'catalogueLead' => 'Search by any name you know, English, scientific or local, like atangya or kuhol.',
            'finder' => 'What Is Attacking My Crop?',
            'finderLead' => 'Tell us your crop and where you see the damage. You get the pests that cause it, and the page that shows how to stop each one.',
            'askWhere' => 'Where do you see the damage?',
            'guides' => 'Pest Guides for Filipino Farmers',
            'guidesLead' => 'The whole picture: how to tell the pests apart, the friends in your field, and how to manage pests without wasting money on sprays.',
            'bandKick' => 'Not sure which pest it is?',
            'bandTitle' => 'Send Anee a Photo of the Insect',
            'bandText' => 'Anee, the smart farm technician in anee.io, tells you what it most likely is, how much damage to expect at the age of your crop and what to do first. In Tagalog or English.',
        ],
        'diseases' => [
            'noun' => 'disease', 'nouns' => 'diseases',
            'catalogue' => 'Crop Diseases in the Philippines',
            'catalogueLead' => 'Search by any name you know, English, scientific or local, like tungro or bugtok.',
            'finder' => 'What Is Wrong With My Crop?',
            'finderLead' => 'Tell us your crop and what you see. You get the diseases that look like it, and the page that shows how to tell them apart and what to do.',
            'askWhere' => 'What do you see?',
            'guides' => 'Disease Guides for Filipino Farmers',
            'guidesLead' => 'How to tell a fungus from a bacterium or a virus, how diseases spread, and the rice diseases by growth stage.',
            'bandKick' => 'Not sure which disease it is?',
            'bandTitle' => 'Send Anee a Photo of the Leaf',
            'bandText' => 'Anee, the smart farm technician in anee.io, reads the photo with your crop, its age and the weather, and tells you what it most likely is and what to do. In Tagalog or English.',
        ],
    ];

    /** One section's entries: slug => facts. */
    public static function entries(string $section): array
    {
        return match ($section) {
            'pests' => ProblemCatalogueData::PESTS,
            'diseases' => ProblemCatalogueData::DISEASES,
            default => [],
        };
    }

    /** One entry's facts, or null for a page that is not in the catalogue (a guide). */
    public static function get(string $section, string $slug): ?array
    {
        return self::entries($section)[$slug] ?? null;
    }
}
