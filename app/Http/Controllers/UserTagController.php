<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * A member's own tag vocabulary, gathered across the things that are
 * theirs and not a season's: contacts, the four analysis shelves, the
 * protocols they wrote. The picker reads it to offer words already used.
 */
class UserTagController extends Controller
{
    /** The words a row's tags column may hold, trimmed, bounded, deduped. */
    public static function tidy($tags): array
    {
        $out = [];
        $seen = [];
        foreach (array_slice((array) $tags, 0, 10) as $t) {
            $w = trim(preg_replace('/\s+/', ' ', (string) $t));
            if ($w === '') continue;
            $w = mb_substr($w, 0, 30);
            $k = mb_strtolower($w);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $w;
        }

        return $out;
    }

    public function index()
    {
        $uid = (int) Auth::id();
        $counts = [];
        $tally = function ($json) use (&$counts) {
            $list = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);
            foreach ((array) $list as $t) {
                if (! is_string($t) || $t === '') continue;
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        };
        foreach (DB::table('as_contacts')->where('userId', $uid)->where('deleteStatus', 1)->pluck('tags') as $j) $tally($j);
        foreach (DB::table('as_protocols')->where('userId', $uid)->where('deleteStatus', 1)->pluck('tags') as $j) $tally($j);
        foreach (DB::table('as_plant_analyses')->where('userId', $uid)->where('deleteStatus', 1)->pluck('tags') as $j) $tally($j);
        ksort($counts, SORT_NATURAL | SORT_FLAG_CASE);

        return response()->json(['success' => true, 'message' => '', 'data' => [
            'tags' => array_map(fn ($name, $n) => ['name' => $name, 'count' => $n], array_keys($counts), $counts),
        ]]);
    }
}
