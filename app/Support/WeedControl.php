<?php

namespace App\Support;

/**
 * Weed control by the age of the crop: the words and groups around the
 * plans (2026-10-06, rice; every crop since 2026-10-08).
 *
 * The plans themselves (for each crop group, and for rice each way of
 * planting: the windows of its age, what to do in each, and the active
 * ingredients that work on each weed group then) live in the field
 * catalogue (App\Support\FieldCatalogue::weedPlans()). Rice's are from
 * PhilRice's recommendations; the rest from DA, BPI, SRA, PCA and
 * university extension guides. Active ingredients only, never a product or
 * brand name: a farmer takes the ingredient to the store and reads the
 * label there.
 *
 * A field with two or three groups at once takes the ingredients that are
 * in every one of those groups' lists, so only the per group lists are
 * kept. A window a group has no list for means hand weeding, not a spray.
 *
 * Shown by the weed control helper on /weeds and in the app's Field helpers.
 */
final class WeedControl
{
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
        5 => 'Photosynthesis inhibitors (propanil, atrazine, ametryn, diuron)',
        6 => 'Photosynthesis inhibitors (bentazone)',
        9 => 'EPSP inhibitors (glyphosate)',
        10 => 'Glutamine synthetase inhibitors (glufosinate)',
        12 => 'Pigment inhibitors (diflufenican, norflurazon)',
        13 => 'Pigment inhibitors (clomazone)',
        14 => 'PPO inhibitors',
        15 => 'Seedling growth inhibitors',
        22 => 'Contact burners (paraquat, diquat)',
        27 => 'HPPD inhibitors',
        29 => 'Cellulose inhibitors (indaziflam)',
    ];
}
