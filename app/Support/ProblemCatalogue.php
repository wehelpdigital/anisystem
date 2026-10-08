<?php

namespace App\Support;

/**
 * The Crop Pests and Crop Diseases catalogues (2026-10-07), built like the
 * weeds one: a profile page per pest or disease where one has been written
 * (database/site-pages/{pests,diseases}/{slug}.json, editable in the mother
 * app), a short fact sheet for the rest, and here what the hub needs beside
 * the pages: the finder's questions and the words around the catalogue.
 *
 * The entries themselves, for every crop of the catalog (2026-10-08), live
 * in the field catalogue (App\Support\FieldCatalogue); its shelves are the
 * hubs' shelves.
 */
final class ProblemCatalogue
{
    /** Pests, step two: where the damage shows. */
    public const PARTS = [
        'leaves' => 'On the leaves', 'stems' => 'In the stems, vines or trunk', 'grain' => 'On the grain, panicles or ears',
        'fruit' => 'On fruits, pods or nuts', 'roots' => 'At the roots, tubers or base', 'seedlings' => 'On young seedlings',
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
            'finderLead' => 'Tell us your crop and where you see the damage. You get the pests that cause it and the active ingredients to spray against each one, with the page that shows when to spray.',
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
            'finderLead' => 'Tell us your crop and what you see. You get the diseases that look like it and the active ingredients to spray against each one, or why no spray helps.',
            'askWhere' => 'What do you see?',
            'guides' => 'Disease Guides for Filipino Farmers',
            'guidesLead' => 'How to tell a fungus from a bacterium or a virus, how diseases spread, and the diseases of palay by growth stage.',
            'bandKick' => 'Not sure which disease it is?',
            'bandTitle' => 'Send Anee a Photo of the Leaf',
            'bandText' => 'Anee, the smart farm technician in anee.io, reads the photo with your crop, its age and the weather, and tells you what it most likely is and what to do. In Tagalog or English.',
        ],
    ];

    /** One section's entries, every crop's: slug => facts. */
    public static function entries(string $section): array
    {
        return FieldCatalogue::problems($section);
    }

    /** One entry's facts, or null for a page that is not in the catalogue (a guide). */
    public static function get(string $section, string $slug): ?array
    {
        return FieldCatalogue::problem($section, $slug);
    }

    /**
     * The catalogue as the hub and the finder show it: every entry, with
     * its page (its profile, or its fact sheet when none is written yet),
     * its picture when the profile has one, and every shelf whose crops it
     * attacks, its own first (the fall armyworm lives on the corn shelf and
     * shows under rice too, so a shelf's count is the finder's count).
     */
    public static function items(string $section, ?\Illuminate\Support\Collection $pages = null): \Illuminate\Support\Collection
    {
        $S = SitePages::class;
        $live = ($pages ?? $S::inSection($section))->keyBy('slug');
        $thumbOf = function ($p) use ($S) {
            $h = is_array($p->heroImage) ? $p->heroImage : [];
            $src = (string) ($h['thumb'] ?? ($h['src'] ?? ''));
            if ($src !== '' && ! isset($h['thumb']) && str_starts_with($src, '/images/')) {
                $twin = preg_replace('/\.(jpe?g|png|webp)$/i', '-480.webp', $src);
                if ($twin && is_file(public_path(ltrim($twin, '/')))) {
                    $src = $twin;
                }
            }

            return $S::img($src);
        };

        return collect(self::entries($section))->map(function ($e, $slug) use ($section, $live, $thumbOf, $S) {
            $page = $live->get($slug);
            $hero = $page && is_array($page->heroImage) ? $page->heroImage : [];

            return $e + ['slug' => $slug, 'url' => $S::url($section, $slug), 'hasPage' => (bool) $page,
                'thumb' => $page ? $thumbOf($page) : null, 'alt' => ($hero['alt'] ?? null) ?: $e['name'],
                'shelves' => array_values(array_unique(array_merge([$e['group']], array_map([FieldCatalogue::class, 'shelfOf'], $e['crops'])))),
            ];
        });
    }

    /**
     * What the finder partial needs, on the hub and in the app's Field
     * helpers (2026-10-07): the items, every crop that has some, the
     * questions and the words.
     */
    public static function finderFacts(string $section, ?\Illuminate\Support\Collection $items = null): array
    {
        $items ??= self::items($section);
        $isPests = $section === 'pests';
        $used = array_count_values($items->pluck('crops')->flatten()->all());

        return [
            'section' => $section,
            'items' => $items,
            // crop => [local, English, icon, shelf, how many]
            'crops' => collect(FieldCatalogue::crops())->filter(fn ($c, $k) => isset($used[$k]))->map(fn ($c, $k) => [...$c, $used[$k]])->all(),
            'askOpts' => $isPests ? self::PARTS : self::SIGNS,
            'words' => self::HUB[$section],
            'isPests' => $isPests,
            'kindHue' => self::KINDS,
        ];
    }
}
