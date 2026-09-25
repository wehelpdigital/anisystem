<?php

namespace App\Http\Controllers;

use App\Models\AsTutorial;
use App\Models\AsTutorialDismissal;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Tutorial videos, both ways they reach a member.
 *
 * The LIBRARY (index): a page of its own beside Community and Support,
 * whose catalogue is curated in the mother app.
 *
 * The CARD ON EACH SCREEN (dataFor, forRoute, dismiss): a short video
 * about the screen just opened, from config/tutorials.php. A page asks for
 * the tutorials it may show -- or the layout asks on its behalf, by route
 * name -- and gets back their words and their clips.
 *
 * "Don't show this again" is a cookie per key in this browser, set by the
 * card itself (see partials/tutorial-modal). The account rows written
 * before that (as_tutorial_dismissals) are still read, for the keys that
 * existed then, so nobody who said "never" is asked again; none are
 * written any more.
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
     * @return array{items: array<string, array{title:string, blurb:string, youtube:string, video:string, poster:string, youtubePortrait:string, portrait:string, portraitPoster:string}>, seen: string[]}
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
            /* The placeholder stands in only where the screen has NO recording
               of either shape. A screen with one real clip shows that clip
               on every device -- a real landscape tutorial beats a portrait
               card that says "coming soon". */
            $real = ! empty($p['video']) || ! empty($p['youtube']) || ! empty($p['portrait']) || ! empty($p['youtube_portrait']);
            $path = fn (?string $own, string $fb) => $own ? asset($own) : ($real ? '' : asset($fallback[$fb] ?? ''));
            $items[$key] = [
                'title'           => (string) ($p['title'] ?? ''),
                'blurb'           => (string) ($p['blurb'] ?? ''),
                'youtube'         => (string) ($p['youtube'] ?? ''),
                'video'           => $path($p['video'] ?? null, 'video'),
                'poster'          => $path($p['poster'] ?? null, 'poster'),
                'youtubePortrait' => (string) ($p['youtube_portrait'] ?? ''),
                'portrait'        => $path($p['portrait'] ?? null, 'portrait'),
                'portraitPoster'  => $path($p['portrait_poster'] ?? null, 'portrait_poster'),
            ];
        }

        /* The old account rows. Only keys that existed while the answer was
           kept on the account can have one, so a page asking only for newer
           keys runs no query at all. */
        $seen = [];
        $legacy = array_values(array_filter(
            array_keys($items),
            fn (string $k) => Str::is((array) config('tutorials.account_keys', []), $k)
        ));
        if ($legacy && Auth::check()) {
            $seen = AsTutorialDismissal::where('userId', (int) Auth::id())
                ->whereIn('tutorialKey', $legacy)
                ->pluck('tutorialKey')
                ->values()
                ->all();
        }

        return ['items' => $items, 'seen' => $seen];
    }

    /**
     * The tutorial a route offers by itself (config tutorials.routes), or
     * null. Read whole and indexed: route names hold dots, which config()'s
     * own path would walk into.
     */
    public static function forRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }
        $key = ((array) config('tutorials.routes', []))[$routeName] ?? null;

        return is_string($key) && isset(config('tutorials.pages', [])[$key]) ? $key : null;
    }

    /** The cookie that says "never again" for one key. Safe as a name: PHP
        and browsers both keep letters, digits, `_` and `-` as they are. */
    public static function cookieName(string $key): string
    {
        return config('tutorials.cookie_prefix', 'anee_tutv_') . preg_replace('/[^A-Za-z0-9_-]/', '_', $key);
    }

    /**
     * "Don't show this again", for a page that still posts it here -- one
     * held from before the answer moved to the browser (an offline copy, a
     * tab left open over a deploy). It gets the same cookie the card now
     * sets for itself, and no account row, so clearing the site's cookies
     * brings its card back like every other.
     */
    public function dismiss(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64', Rule::in(array_keys(config('tutorials.pages', [])))],
        ]);

        $name = self::cookieName($data['key']);
        // Plain, not encrypted: the card reads it from document.cookie.
        EncryptCookies::except($name);

        return response()->json(['success' => true])->cookie(
            $name, '1', (int) config('tutorials.cookie_days', 1826) * 1440,
            '/', null, $request->isSecure(), false, false, 'lax'
        );
    }
}
