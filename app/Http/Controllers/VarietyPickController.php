<?php

namespace App\Http\Controllers;

use App\Support\Region;
use App\Support\VarietyBook;
use Illuminate\Http\Request;

/**
 * The variety sheet's records (2026-10-15, the owner): every variety on
 * record for a crop (rice from App\Support\RiceVarieties, the rest from the
 * crop records, through App\Support\VarietyBook), compact, for the shared
 * partial partials/variety-pick to search; and, where the place is known,
 * the ones VarietyBook ranks as suited to the area. A plain lookup: no AI,
 * no charge. The records are the Philippines' registry, so a field abroad
 * gets none (and types its own).
 */
class VarietyPickController extends Controller
{
    public function list(Request $request)
    {
        $crop = mb_substr((string) $request->query('crop', ''), 0, 40);
        $country = Region::valid($request->query('country')) ?: Region::code();
        $rows = $country === Region::HOME ? VarietyBook::rows($crop) : [];
        if (! $rows) {
            return response()->json(['success' => true, 'message' => 'ok', 'data' => ['crop' => $crop, 'count' => 0, 'all' => [], 'suited' => []]]);
        }
        $all = array_map(function ($r) {
            $b = VarietyBook::brief($r);

            return [$b['name'], $b['alias'], $b['type'], $b['days'], $b['daysUnit'], $r['yield'] !== null ? round((float) $r['yield'], 2) : null,
                ($r['yieldTop'] ?? null) !== null ? round((float) $r['yieldTop'], 2) : null, $r['yieldUnit'] ?? 't/ha', $b['year'],
                implode(' ', array_slice((array) $r['aliases'], 0, 4)), mb_substr((string) ($r['where'] ?? ''), 0, 120)];
        }, $rows);
        usort($all, fn ($a, $b) => ($b[8] ?? 0) <=> ($a[8] ?? 0) ?: strnatcasecmp($a[0], $b[0]));

        // Suited to the area: only where the place says which island group the trials speak for.
        $location = mb_substr(trim((string) $request->query('location', '')), 0, 160);
        $island = $location !== '' ? VarietyBook::island($location) : null;
        $suited = [];
        if ($island) {
            $season = (string) $request->query('season', '');
            $elevation = (string) $request->query('elevation', '');
            $case = [
                'island' => $island,
                'envs' => VarietyBook::envsFor($crop, array_values(array_filter((array) $request->query('problems', []), 'is_string')), $request->query('water') ?: null, $elevation ?: null),
                'elevation' => $elevation ?: null,
                'season' => in_array($season, ['wet', 'dry'], true) ? $season : null,
                'problems' => [],
            ];
            $suited = array_map(fn ($r) => $r['name'], VarietyBook::candidates($crop, $case, 6));
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => ['crop' => $crop, 'count' => count($all), 'all' => $all, 'suited' => $suited, 'island' => $island]]);
    }
}
