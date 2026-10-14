<?php

namespace App\Http\Middleware;

use App\Http\Controllers\FieldHelperController;
use App\Support\FieldPack;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The field helpers pack's door (2026-10-14, the owner): on a farm without
 * the pack (the free plan, no add-on), the Weed Control Helper, the Pest
 * Finder, the Disease Finder and the Variety Finder answer with the offer
 * (a page) or a 403 the pages turn into the offer (JSON). Every route under
 * /app/field-helpers/ that names one of those tools is behind it.
 */
class FieldPackGate
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only the field helpers' own paths, and only for a signed in member.
        if (! $request->is('app/field-helpers/*') || ! $request->user()) {
            return $next($request);
        }
        $tool = (string) ($request->route('tool') ?? $request->route('section') ?? '');
        if ($tool === '') {
            // A catalogue or detail route without a {tool}: read the path.
            if (preg_match('#^app/field-helpers/(?:[a-z-]+/)?(weeds|pests|diseases|varieties)\b#', $request->path(), $m)) {
                $tool = $m[1];
            }
        }
        if (! in_array($tool, FieldPack::TOOLS, true) || ! FieldPack::lockedHere()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'packLock' => true,
                'message' => 'The field helpers are a small add-on on the free plan. Open them for a year, or upgrade to Libre + Anee.'], 403);
        }

        $name = FieldHelperController::TOOLS[$tool][0] ?? 'Field helpers';

        return response()->view('field-helpers.locked', ['fhName' => $name]);
    }
}
