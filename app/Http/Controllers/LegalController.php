<?php

namespace App\Http\Controllers;

use App\Models\AsLegalPage;

/**
 * Public legal / info pages (Privacy, Terms, Cookies, About) — content is
 * managed from the mother app and rendered here read-only.
 */
class LegalController extends Controller
{
    public function show(string $slug)
    {
        $page = AsLegalPage::active()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            abort(404);
        }

        return view('legal.show', ['page' => $page]);
    }
}
