<?php

namespace App\Http\Controllers;

use App\Support\ProblemCatalogue;

/**
 * Field helpers in the app (2026-10-07): the weed control helper and the
 * pest and disease finders from the public site, as small modules of their
 * own under Global and Quick Tools. The same partials the public hubs use,
 * without the email gate: a member is already in.
 */
class FieldHelperController extends Controller
{
    public const TOOLS = [
        'weeds' => ['Weed Control Helper', 'What to do first and what to spray, by the age of your rice', 'weed'],
        'pests' => ['Pest Finder', 'Find the pest from the damage you see, and what to spray', 'pest'],
        'diseases' => ['Disease Finder', 'Find the disease from the signs you see, and what to spray', 'disease'],
    ];

    public function page(string $tool)
    {
        abort_unless(isset(self::TOOLS[$tool]), 404);

        return view('field-helpers.index', [
            'tool' => $tool,
            'tools' => self::TOOLS,
            'facts' => $tool === 'weeds' ? [] : ProblemCatalogue::finderFacts($tool),
        ]);
    }
}
