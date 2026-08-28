<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

/**
 * Something worth knowing while a screen is still working.
 *
 * A wait is a few seconds of somebody's attention and the app has it whether
 * it wants it or not, so it spends it on something useful: wear the coat,
 * drink before you are thirsty, spray with the wind behind you. One line, the
 * reason underneath it, and a small scene to draw beside them.
 *
 * The board takes a handful at page render and re-rolls in the browser every
 * time a veil goes up, so a farmer who opens the same board four times in a
 * morning is not told the same thing four times.
 */
class AsLoadingLine extends BaseModel
{
    protected $table = 'as_loading_lines';

    /**
     * The scenes the front end knows how to draw.
     *
     * A line naming anything else still shows — it falls back to the
     * seedling — so somebody adding lines from the admin cannot break a
     * screen by mistyping this.
     */
    public const SCENES = [
        // Health and weather — what a wait is mostly spent reminding people of
        'rain', 'sun', 'water', 'vitamin', 'firstaid', 'boots', 'moon',
        // The work, and doing it without hurting yourself
        'spray', 'tools', 'tractor', 'notebook',
        // The farm itself
        'seedling', 'rice', 'watering', 'carabao', 'bee',
        'hat', 'gloves', 'mask', 'goggles', 'helmet', 'ear', 'soap', 'bottle', 'shade', 'nap', 'phone', 'torch', 'sack', 'back', 'cart', 'rope', 'bolo', 'sharpen', 'ladder', 'bucket', 'hose', 'pump', 'canal', 'flood', 'soil', 'compost', 'fertbag', 'granule', 'sprout', 'nursery', 'transplant', 'weeds', 'pest', 'spider', 'bird', 'snail', 'mosquito', 'net', 'mouse', 'thermometer', 'pills', 'stetho', 'eye', 'dry', 'store', 'weevil', 'label', 'scale', 'basket', 'clock', 'money', 'lightning', 'windy', 'tarp', 'roof', 'calendar',
    ];

    protected $fillable = ['line', 'subline', 'scene', 'surface', 'deleteStatus'];

    protected $casts = ['deleteStatus' => 'integer'];

    /**
     * A pool for one page, shuffled.
     *
     * Cached for a few minutes because this is asked for on the way into the
     * busiest screen in the app and the answer is decoration: a farmer who
     * gets a five-minute-old set of jokes has lost nothing. The shuffle
     * happens AFTER the cache, so the order still changes on every render.
     */
    public static function pool(string $surface = 'board', int $take = 30): array
    {
        $all = Cache::remember("as_loading_lines.$surface", 300, function () use ($surface) {
            return self::query()
                ->where('surface', $surface)
                ->where('deleteStatus', 1)
                ->get(['line', 'subline', 'scene'])
                ->map(fn ($r) => [
                    'line' => $r->line,
                    'sub' => (string) $r->subline,
                    'scene' => $r->scene,
                ])
                ->all();
        });

        if (! $all) {
            return [['line' => 'Working out the day…', 'sub' => 'One moment.', 'scene' => 'seedling']];
        }

        shuffle($all);

        /* Pick for VARIETY OF PICTURE, not just of words.
         *
         * A straight shuffle of a hundred and fifty-seven lines regularly
         * handed a page fourteen reminders that between them used six
         * drawings, so re-rolling three times in a row showed the same
         * seedling three times and the card read as one animation with the
         * text changed. This takes the first line of each distinct scene
         * first, and only then fills up from what is left — so a page's
         * worth of rolls is a page's worth of different pictures.
         */
        $take = max(1, $take);
        $first = [];
        $rest = [];
        $used = [];
        foreach ($all as $row) {
            $scene = $row['scene'] ?? '';
            if (! isset($used[$scene])) {
                $used[$scene] = true;
                $first[] = $row;
            } else {
                $rest[] = $row;
            }
        }

        return array_slice(array_merge($first, $rest), 0, $take);
    }
}
