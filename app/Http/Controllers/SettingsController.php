<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Settings: how the app behaves for this person, as opposed to who they
 * are (Account) or what they pay for (Subscription).
 *
 * Accessibility lives entirely in the browser — the choices have to apply
 * before the first paint, and a round trip to the server would mean a
 * frame of the wrong size on every page. The page below is the door to
 * them; app.blade.php's head script is what actually applies them.
 */
class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('settings.index', [
            'tab' => in_array($request->query('tab'), ['accessibility'], true)
                ? $request->query('tab')
                : 'accessibility',
        ]);
    }
}
