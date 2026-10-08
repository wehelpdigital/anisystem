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
    ];

    public function page(string $tool)
    {
        abort_unless(isset(self::TOOLS[$tool]), 404);

        return view('field-helpers.index', [
            'tool' => $tool,
            'tools' => self::TOOLS,
            'facts' => $tool === 'weeds' ? [] : ProblemCatalogue::finderFacts($tool),
            'defaultCrop' => $this->newestCrop(),
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
