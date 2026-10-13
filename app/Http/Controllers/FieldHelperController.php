<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Support\ProblemCatalogue;
use App\Support\WorkerContext;

/**
 * Field helpers in the app (2026-10-07): the weed control helper and the
 * pest and disease finders from the public site, as small modules of their
 * own under Global and Quick Tools. The same partials the public hubs use,
 * without the email gate: a member is already in. Every crop of the catalog
 * since 2026-10-08, opened on the crop of the farmer's newest season.
 */
class FieldHelperController extends Controller
{
    public const TOOLS = [
        'weeds' => ['Weed Control Helper', 'What to do first and what to spray, by your crop and its age', 'weed'],
        'pests' => ['Pest Finder', 'Find the pest from the damage you see on any crop, and what to spray', 'pest'],
        'diseases' => ['Disease Finder', 'Find the disease from the signs you see on any crop, and what to spray', 'disease'],
        // Every rice variety in PhilRice's seed database (2026-10-13), the finder of /rice-varieties,
        // and every other crop's since the same day, the finders of /crop-varieties/{crop}.
        'varieties' => ['Variety Finder', 'Every registered variety of rice, corn, vegetables, root crops and fruit trees, with days to harvest, yield and resistance', 'rice-variety'],
        // Every land preparation step, dated back from the planting day (2026-10-14, App\Support\LandPrep).
        'landprep' => ['Land Prep Planner', 'Every step of getting your field ready, dated back from your planting day', 'land-prep'],
    ];

    public function page(string $tool)
    {
        abort_unless(isset(self::TOOLS[$tool]), 404);
        $defaultCrop = $this->newestCrop();
        // The variety finder's crop: the one asked for, else the newest season's.
        $vCrop = null;
        $vCrops = [];
        if ($tool === 'varieties') {
            $vCrops = collect(\App\Support\CropVarieties::crops())->filter(fn ($c) => $c['hasHub'])->sortByDesc('count')->all();
            $want = (string) request()->query('crop', '');
            $fromSeason = $defaultCrop ? (str_starts_with($defaultCrop, 'corn') ? 'corn' : $defaultCrop) : null;
            foreach ([$want, $fromSeason] as $k) {
                if ($k && isset($vCrops[$k])) {
                    $vCrop = $k;
                    break;
                }
            }
        }

        return view('field-helpers.index', [
            'tool' => $tool,
            'tools' => self::TOOLS,
            'facts' => in_array($tool, ['weeds', 'varieties', 'landprep'], true) ? [] : ProblemCatalogue::finderFacts($tool),
            'landBook' => $tool === 'landprep' ? \App\Support\LandPrep::book() : null,
            'varieties' => $tool === 'varieties' ? ($vCrop ? \App\Support\CropVarieties::forCrop($vCrop) : \App\Support\RiceVarieties::all()) : [],
            'vCrop' => $vCrop,
            'vCrops' => $vCrops,
            'defaultCrop' => $defaultCrop,
        ]);
    }

    /** The crop of the farmer's newest season (a lot's own crop first), or null. */
    private function newestCrop(): ?string
    {
        try {
            $season = AsCroppingSchedule::active()->forClient(WorkerContext::effectiveOwnerId())->where('deleteStatus', 1)
                ->orderByDesc('id')->first(['id', 'cropType']);
            if (! $season) {
                return null;
            }
            $lotCrop = AsScheduleLot::where('croppingScheduleId', $season->id)->where('deleteStatus', 1)->whereNotNull('crop')->where('crop', '!=', '')->value('crop');

            return $lotCrop ?: ($season->cropType ?: null);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
