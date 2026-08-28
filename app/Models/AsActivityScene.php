<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

/**
 * What a day's work looks like.
 *
 * A schedules page that says "4 activities today" has told you a number. The
 * picture beside it is meant to tell you the KIND of day — a knapsack sprayer
 * says wind matters and the neighbours should know, a plough says the field
 * will be soft by evening, a calendar with nothing in front of it says the
 * board is clear.
 *
 * Which slug maps to which drawing is a fact about this app's activity
 * catalogue and lives in code. The name and the sentence under it are
 * opinions, so they live in the table.
 */
class AsActivityScene extends BaseModel
{
    protected $table = 'as_activity_scenes';

    protected $fillable = ['key', 'label', 'scene', 'hue', 'blurb', 'sortOrder', 'deleteStatus'];

    protected $casts = ['sortOrder' => 'integer', 'deleteStatus' => 'integer'];

    /** The drawings the front end knows how to animate. */
    public const SCENES = [
        'plough', 'seedbed', 'planting', 'sprayer', 'granular', 'herbicide',
        'pesticide', 'fungicide', 'microbe', 'water', 'harvest', 'scout',
        'toolbox', 'crew', 'checklist', 'service', 'quiet', 'mixed',
    ];

    /** The colour families a card can wear behind one of these. */
    public const HUES = ['leaf', 'soil', 'water', 'sky', 'sun', 'amber', 'rose', 'violet', 'slate'];

    /**
     * Which day this is when several things are on the board.
     *
     * Not "the first one" and not "the most numerous" — the loudest. A day
     * with three monitorings and one spraying is a spraying day, because the
     * spraying is the thing that has to be got right and the thing the
     * weather can ruin. The order below is that ranking, read once.
     */
    public const LOUDNESS = [
        'harvest', 'copper_fungicide', 'fungicide', 'pesticide', 'herbicide',
        'foliar_spray', 'microbial', 'fertilizer', 'planting', 'seed_treatment',
        'land_prep', 'irrigation', 'equipment_prep', 'service', 'worker_payroll',
        'reminder_checklist', 'monitoring', 'other',
    ];

    /**
     * The one activity type that speaks for a day.
     *
     * @param  array<int,string>  $types  every activityType on the board today
     */
    public static function leadFor(array $types): string
    {
        $have = array_flip(array_filter($types));
        if (! $have) {
            return 'quiet';
        }
        foreach (self::LOUDNESS as $slug) {
            if (isset($have[$slug])) {
                return $slug;
            }
        }

        return 'other';
    }

    /** Every scene, keyed, cached the way the skies are. */
    public static function map(): array
    {
        return Cache::remember('as_activity_scenes.map', 600, function () {
            return self::query()
                ->where('deleteStatus', 1)
                ->orderBy('sortOrder')
                ->get()
                ->keyBy('key')
                ->map(fn ($r) => [
                    'label' => $r->label,
                    'scene' => in_array($r->scene, self::SCENES, true) ? $r->scene : 'mixed',
                    'hue' => in_array($r->hue, self::HUES, true) ? $r->hue : 'leaf',
                    'blurb' => (string) $r->blurb,
                ])
                ->all();
        });
    }

    /** One scene, with an answer for a slug nobody has written a row for. */
    public static function one(string $key): array
    {
        $all = self::map();

        return $all[$key] ?? ['label' => 'On the board', 'scene' => 'mixed', 'hue' => 'leaf', 'blurb' => ''];
    }
}
