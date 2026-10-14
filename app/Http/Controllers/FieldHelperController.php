<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Support\FieldBook;
use App\Support\VarietyFinder;
use App\Support\WorkerContext;

/**
 * Field helpers in the app (2026-10-07): the weed control helper and the
 * pest and disease finders from the public site, as small modules of their
 * own under Global and Quick Tools. Every crop of the catalog since
 * 2026-10-08, opened on the crop of the farmer's newest season. Since
 * 2026-10-14 they have partials of their own (field-helpers/partials), made
 * for a phone first, with the whole catalogue in a full screen sheet; the
 * public hubs' partials are no longer used here, and nothing in the app
 * links out to the public catalogue.
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
        // The in-app helpers (2026-10-14): their own partials (field-helpers/partials),
        // fed by App\Support\FieldBook and VarietyFinder from the same files and rows
        // the public catalogues read, and never a link out to them.
        $book = null;
        $vBook = null;
        $vCrops = [];
        if (in_array($tool, ['pests', 'diseases'], true)) {
            $book = FieldBook::problems($tool);
        } elseif ($tool === 'weeds') {
            $book = FieldBook::weeds();
        } elseif ($tool === 'varieties') {
            // The crop asked for (?crop=), else the newest season's, else rice.
            $vCrops = VarietyFinder::crops();
            $want = (string) request()->query('crop', '');
            $vBook = VarietyFinder::book($want !== '' ? $want : (string) $defaultCrop);
        }

        return view('field-helpers.index', [
            'tool' => $tool,
            'tools' => self::TOOLS,
            'book' => $book,
            'landBook' => $tool === 'landprep' ? \App\Support\LandPrep::book() : null,
            'vBook' => $vBook,
            'vCrops' => $vCrops,
            'defaultCrop' => $defaultCrop,
        ]);
    }

    /**
     * The Variety Finder's data (2026-10-14), as JSON: one crop's varieties,
     * filters and words (?crop=), or every variety's names for the variety
     * catalogue's search (?index=1).
     */
    public function data(string $tool)
    {
        abort_unless($tool === 'varieties', 404);
        $payload = request()->boolean('index')
            ? ['success' => true, 'items' => VarietyFinder::index()]
            : ['success' => true] + VarietyFinder::book((string) request()->query('crop', 'rice'));

        return response()->json($payload, 200, ['Cache-Control' => 'private, max-age=600'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
