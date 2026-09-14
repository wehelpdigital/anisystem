<?php

namespace App\Http\Controllers;

use App\Models\AsTutorial;
use App\Models\AsTutorialDismissal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Tutorial videos, both ways they reach a member.
 *
 * The LIBRARY (index): a page of its own beside Community and Support,
 * whose catalogue is curated in the mother app.
 *
 * The CARD ON EACH SCREEN (dataFor, dismiss): a short video about the
 * screen just opened, from config/tutorials.php. A page asks for the
 * tutorials it may show and gets back their words, their clips, and which
 * of them this person has already told to stay closed; "don't show this
 * again" is written against the account, so the card stays closed on
 * every device they sign in from.
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

    /**
     * What a page needs to offer its tutorials: the items it asked for, made
     * whole with the placeholder where no recording exists yet, and the keys
     * this person has already dismissed for good.
     *
     * Only the keys asked for are looked up, and only when there is somebody
     * signed in -- one small IN query on the screens that have a video, and
     * nothing at all anywhere else.
     *
     * @param  string[]  $keys
     * @return array{items: array<string, array{title:string, blurb:string, youtube:string, video:string, poster:string}>, seen: string[]}
     */
    public static function dataFor(array $keys): array
    {
        $pages = config('tutorials.pages', []);
        $fallback = config('tutorials.placeholder', []);
        $items = [];

        foreach ($keys as $key) {
            if (! isset($pages[$key])) {
                continue;
            }
            $p = $pages[$key];
            $items[$key] = [
                'title'   => (string) ($p['title'] ?? ''),
                'blurb'   => (string) ($p['blurb'] ?? ''),
                // A YouTube id wins over a file; either falls back to the placeholder.
                'youtube' => (string) ($p['youtube'] ?? ''),
                'video'   => asset(($p['video'] ?? null) ?: ($fallback['video'] ?? '')),
                'poster'  => asset(($p['poster'] ?? null) ?: ($fallback['poster'] ?? '')),
            ];
        }

        $seen = [];
        if ($items && Auth::check()) {
            $seen = AsTutorialDismissal::where('userId', (int) Auth::id())
                ->whereIn('tutorialKey', array_keys($items))
                ->pluck('tutorialKey')
                ->values()
                ->all();
        }

        return ['items' => $items, 'seen' => $seen];
    }

    /** "Don't show this again" -- for this person, on this screen, for good. */
    public function dismiss(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64', Rule::in(array_keys(config('tutorials.pages', [])))],
        ]);

        AsTutorialDismissal::firstOrCreate([
            'userId'      => (int) Auth::id(),
            'tutorialKey' => $data['key'],
        ]);

        return response()->json(['success' => true]);
    }
}
