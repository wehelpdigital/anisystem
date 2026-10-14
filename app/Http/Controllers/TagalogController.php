<?php

namespace App\Http\Controllers;

use App\Support\Tagalog;

/**
 * The Tagalog help flags' glossary (App\Support\Tagalog), fetched once by the
 * browser on the first tap of a flag. The URL carries the glossary's version,
 * so the answer can be cached for a year: a new glossary is a new URL.
 * Served without a session (see routes/web.php), so no cookie rides along on
 * a publicly cacheable answer and no database is touched.
 */
class TagalogController extends Controller
{
    public function glossary()
    {
        $current = request()->query('v') === Tagalog::version();

        return response()->json(Tagalog::payload(), 200, [
            // An old version's URL gets today's words, briefly cached.
            'Cache-Control' => $current ? 'public, max-age=31536000, immutable' : 'public, max-age=300',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
