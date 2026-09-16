<?php

namespace App\Http\Controllers;

use App\Models\AsLegalPage;

/**
 * Public legal / info pages (Privacy, Terms, Cookies, About) — content is
 * managed from the mother app and rendered here read-only.
 */
class LegalController extends Controller
{
    /** The face comes first on the address (/ph/legal/privacy); scalar route parameters arrive by position. */
    public function show(string $face, string $slug)
    {
        $page = AsLegalPage::active()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            abort(404);
        }
        // Written for the home market; on the international face the
        // identity words widen (the text in the database is untouched).
        // Country names stay -- a governing-law clause must keep its country.
        if (\App\Support\Region::englishOnly()) {
            $page->body = str_replace(['Filipino farmers', 'Filipino farmer', 'Filipino farms'], ['farmers', 'farmer', 'farms'], (string) $page->body);
        }

        return view('legal.show', ['page' => $page]);
    }
}
