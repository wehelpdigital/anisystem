<?php

namespace App\Http\Controllers;

use App\Support\PageViews;
use Illuminate\Http\Request;

/**
 * The season shell swaps its modules without loading a page; it says here which one came up, so the admin panel's
 * Interests report sees Notes and Lots, not only Activities (App\Support\PageViews::seen). Answers nothing.
 */
class PageSeenController extends Controller
{
    public function __invoke(Request $request)
    {
        PageViews::seen($request);

        return response()->noContent();
    }
}
