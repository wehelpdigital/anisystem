<?php

namespace App\Support;

/**
 * What to ask about an observation, depending on what kind of observation it
 * is.
 *
 * The form used to ask everything at once: yield, unit, moisture, price and
 * buyer, whether you were recording a harvest figure or a lesson for next
 * season. Most of it was blank on most records, and the fields that mattered
 * for pests, weather or storage were not there at all — they ended up as prose
 * in the notes box, where no report can ever count them.
 *
 * Each category names its own fields. Four of them map onto columns that
 * already exist (yieldAmount, yieldUnit, moisturePercent, pricePerUnit,
 * buyer); everything else is kept in the `details` JSON, which is the honest
 * place for answers that only some kinds of observation have.
 */
class PostHarvestFields
{
    /**
     * category => [key, label, type, extra]
     *
     * type: number | text | select | percent | money
     * A key matching a real column is written there; the rest go to details.
     */
    public const FIELDS = [
        'yield' => [
            ['yieldAmount', 'How much was harvested', 'number', ['placeholder' => 'e.g. 4600']],
            ['yieldUnit', 'Measured in', 'unit', ['placeholder' => 'kg, sacks, cavans']],
            ['moisturePercent', 'Moisture at harvest', 'percent', ['placeholder' => 'e.g. 21']],
            ['wetOrDry', 'Wet or dry weight', 'select', ['options' => ['wet' => 'Wet (fresh off the field)', 'dry' => 'Dry (after drying)']]],
            ['areaHarvested', 'Area harvested', 'text', ['placeholder' => 'e.g. 1.5 ha']],
        ],
        'quality' => [
            ['moisturePercent', 'Moisture', 'percent', ['placeholder' => 'e.g. 14']],
            ['grade', 'Grade or class given', 'text', ['placeholder' => 'e.g. Premium, Class A, rejected']],
            ['defectPercent', 'Rejected or downgraded', 'percent', ['placeholder' => 'e.g. 8']],
            ['defectKind', 'What was wrong with it', 'text', ['placeholder' => 'e.g. chalky grains, cracked, discoloured']],
        ],
        'pest' => [
            ['pestName', 'Pest or disease', 'text', ['placeholder' => 'e.g. rice black bug, sheath blight']],
            ['severity', 'How bad it got', 'select', ['options' => ['light' => 'Light — noticed, little damage', 'moderate' => 'Moderate — visible loss', 'severe' => 'Severe — serious loss']]],
            ['affectedArea', 'How much was affected', 'text', ['placeholder' => 'e.g. 0.4 ha, the north corner']],
            ['actionTaken', 'What was done about it', 'text', ['placeholder' => 'e.g. sprayed on the 12th, drained the paddy']],
            ['lossEstimate', 'Estimated loss', 'money', ['placeholder' => 'e.g. 8000']],
        ],
        'weather' => [
            ['event', 'What happened', 'select', ['options' => [
                'typhoon' => 'Typhoon or strong wind', 'flood' => 'Flooding', 'drought' => 'Drought or no rain',
                'heat' => 'Heat', 'rain' => 'Too much rain', 'other' => 'Something else',
            ]]],
            ['whenDays', 'How many days it lasted', 'number', ['placeholder' => 'e.g. 3']],
            ['affectedArea', 'How much was affected', 'text', ['placeholder' => 'e.g. half of Lot B']],
            ['lossEstimate', 'Estimated loss', 'money', ['placeholder' => 'e.g. 12000']],
        ],
        'storage' => [
            ['dryingMethod', 'How it was dried', 'select', ['options' => [
                'sun' => 'Sun drying', 'mechanical' => 'Mechanical dryer', 'none' => 'Not dried',
            ]]],
            ['dryingDays', 'Days spent drying', 'number', ['placeholder' => 'e.g. 2']],
            ['moisturePercent', 'Moisture after drying', 'percent', ['placeholder' => 'e.g. 14']],
            ['storedWhere', 'Where it is kept', 'text', ['placeholder' => 'e.g. warehouse, at home, cooperative']],
            ['storageLoss', 'Lost in drying or storage', 'text', ['placeholder' => 'e.g. 2 sacks, spillage']],
        ],
        'market' => [
            ['yieldAmount', 'How much was sold', 'number', ['placeholder' => 'e.g. 4200']],
            ['yieldUnit', 'Measured in', 'unit', ['placeholder' => 'kg, sacks, cavans']],
            ['pricePerUnit', 'Price per unit', 'money', ['placeholder' => 'e.g. 23.50']],
            ['buyer', 'Sold to', 'text', ['placeholder' => 'e.g. NFA, local trader']],
            ['paymentTerms', 'How it was paid', 'select', ['options' => [
                'cash' => 'Cash on pickup', 'partial' => 'Partly paid', 'credit' => 'On credit — still owed',
            ]]],
        ],
        'lesson' => [
            ['whatHappened', 'What went wrong or right', 'text', ['placeholder' => 'e.g. transplanted too late']],
            ['changeNext', 'What to do differently next season', 'text', ['placeholder' => 'e.g. sow by the first week of June']],
            ['priority', 'How much it matters', 'select', ['options' => [
                'high' => 'Must not repeat', 'medium' => 'Worth changing', 'low' => 'Minor',
            ]]],
        ],
        'other' => [],
    ];

    /** Which of the above keys are real columns rather than JSON details. */
    public const COLUMNS = ['yieldAmount', 'yieldUnit', 'moisturePercent', 'pricePerUnit', 'buyer'];

    /** The fields for one category, each as a keyed row the views can loop. */
    public static function for(string $category): array
    {
        return collect(self::FIELDS[$category] ?? [])
            ->map(fn ($f) => [
                'key' => $f[0],
                'label' => $f[1],
                'type' => $f[2],
                'options' => $f[3]['options'] ?? null,
                'placeholder' => $f[3]['placeholder'] ?? null,
                'isColumn' => in_array($f[0], self::COLUMNS, true),
            ])
            ->all();
    }

    /** Everything, shaped for the client so one definition drives both. */
    public static function all(): array
    {
        return collect(self::FIELDS)->keys()
            ->mapWithKeys(fn ($c) => [$c => self::for($c)])
            ->all();
    }

    /** A stored detail, said the way it was offered ("severe" → "Severe — …"). */
    public static function labelFor(string $category, string $key, $value): string
    {
        foreach (self::for($category) as $f) {
            if ($f['key'] === $key && $f['options']) {
                return $f['options'][$value] ?? (string) $value;
            }
        }

        return (string) $value;
    }

    /** The question a stored detail was the answer to. */
    public static function questionFor(string $category, string $key): ?string
    {
        foreach (self::for($category) as $f) {
            if ($f['key'] === $key) {
                return $f['label'];
            }
        }

        return null;
    }
}
