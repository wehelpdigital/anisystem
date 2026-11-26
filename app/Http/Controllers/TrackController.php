<?php

namespace App\Http\Controllers;

use App\Support\UserActions;
use Illuminate\Http\Request;

/**
 * What the page noticed and the server could not: a window opened, a locked door tapped, a script that broke. Sent in
 * small batches by core.js (aneeNote); kept by App\Support\UserActions for the admin panel. Answers nothing.
 */
class TrackController extends Controller
{
    public function __invoke(Request $request)
    {
        UserActions::client($request);

        return response()->noContent();
    }
}
