<?php

namespace App\Http\Controllers;

use App\Models\AsTutorial;

/**
 * Tutorial video library (member-facing). Cards open a player; the catalogue is
 * curated in the mother app. A page of its own beside Community and Support.
 */
class TutorialController extends Controller
{
    public function index()
    {
        $grouped = AsTutorial::active()
            ->published()
            ->orderBy('sortOrder')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($t) => $t->category ?: 'General');

        return view('tutorials.index', ['grouped' => $grouped]);
    }
}
