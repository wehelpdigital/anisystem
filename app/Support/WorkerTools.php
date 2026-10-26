<?php

namespace App\Support;

use App\Http\Controllers\FieldHelperController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The tools on the home screen, as a worker's login switches them
 * (2026-10-22, the owner: "in the workers add also management switches for
 * the tools in the home or dashboard").
 *
 * One list for the four places that must agree: the switches on both worker
 * pages (sm.partials.worker-rights), the grant that keeps the ones turned off
 * (WorkerGrant::toolsOff), the tiles on the dashboard (partials.global-tools)
 * and the gate (WorkerModuleAccess), so a tool switched off is neither drawn
 * nor reachable by its address.
 *
 * A tool is [name, group, icon, routes, pages]. Routes are Str::is patterns
 * on route names; `name@tool` also asks the route's {tool} (the field helpers
 * share fh.page). Pages are routes refused only as a page: the Gallery's
 * address also feeds the board's picture picker, and Global Lots, Maps and
 * Draw keep the endpoints every lot picker, map and drawing on a season use.
 * A route several tools need (Satellite Weather's place search, the seed count
 * Crop Protocol and NPK Plus read) opens while any one of them is on.
 *
 * Quick Capture, Quick Record and Quick Voice are not here: they follow the
 * Camera, Record video and Voice notes switches. Global Workers is never a
 * worker's tile.
 */
class WorkerTools
{
    public const GROUPS = [
        'anee' => 'Anee does it for you',
        'tools' => 'Your tools',
        'records' => 'Your farm records',
    ];

    /** @return array<string, array{0: string, 1: string, 2: string, 3: array<int, string>, 4: array<int, string>}> */
    public static function all(): array
    {
        static $all = null;
        if ($all !== null) {
            return $all;
        }

        $all = [
            'wtp' => ['When to Plant Analysis', 'anee', 'images/appointment.png', ['wtp.*'], []],
            'whatp' => ['What to Plant Analysis', 'anee', 'images/plant.png', ['whatp.*'], []],
            'vary' => ['Variety Ranking', 'anee', 'images/icons/biotechnology.png', ['vary.*'], []],
            'proto' => ['Crop Protocol Analysis', 'anee', 'images/icons/checklist.png', ['proto.*', 'seedcalc.count'], []],
            'sat' => ['Satellite Analysis', 'anee', 'images/icons/satellite.svg', ['sat.*'], []],
            'landprep' => ['Land Prep Planner', 'anee', 'images/icons/land-prep.svg', ['landprep.*', 'fh.*@landprep'], []],
            'sky' => ['Satellite Weather', 'tools', 'images/icons/storm.svg', ['sky.*', 'sat.places'], []],
            'compare' => ['Compare Reports', 'tools', 'images/icons/ab-testing.png', ['compare.*', 'sm.compare.*'], []],
            'pb' => ['Protocol Builder', 'tools', 'images/icons/bricks.png', ['pb.*'], []],
            'npk' => ['NPK Plus Calculator', 'tools', 'images/icons/npk.svg', ['npk.*', 'seedcalc.count'], []],
            'lcc' => ['LCC Checker', 'tools', 'images/icons/leaf-color.svg', ['lcc.*'], []],
            'bilad' => ['Benta o Bilad Calculator', 'tools', 'images/icons/benta-bilad.svg', ['bilad.*'], []],
            'hatian' => ['Hatian Calculator', 'tools', 'images/icons/hatian.svg', ['hatian.*'], []],
            'seedcalc' => ['Seed Count and Distance Calculator', 'tools', 'images/icons/seed-count.svg', ['seedcalc.*'], []],
            'htp' => ['How to Plant', 'tools', 'images/icons/how-to-plant.svg', ['htp.*'], []],
        ];
        // The field helpers, in the order the dashboard draws them.
        foreach (FieldHelperController::TOOLS as $key => [$name, , $icon]) {
            if ($key === 'landprep') {
                continue;
            }
            $all[$key] = [$name, 'tools', 'images/icons/' . $icon . '.svg', array_merge(['fh.*@' . $key], $key === 'compat' ? ['compat.*'] : []), []];
        }
        $all += [
            'notes' => ['Global Notes', 'records', 'images/sticky-note.png', ['notes.hub', 'notes.hub.store', 'notes.hub.destroy'], []],
            'gallery' => ['Global Gallery', 'records', 'images/gallery.png', [], ['gallery.hub']],
            'farmlots' => ['Global Lots', 'records', 'images/treasure-map.png', [], ['farmlots.index']],
            'maps' => ['Maps', 'records', 'images/location-marker.png', [], ['maps.page']],
            'draw' => ['Draw', 'records', 'images/writting.png', [], ['draw.page']],
            'contacts' => ['Contact List', 'records', 'images/list.png', ['contacts.*'], []],
            'tags' => ['Global Tags', 'records', 'images/label.png', ['tags.global', 'tags.global.*'], []],
        ];

        return $all;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * What a form sent as the tools turned off, kept to known keys.
     *
     * @return array<int, string>|null  null when every tool is on
     */
    public static function clean($sent): ?array
    {
        $off = array_values(array_intersect(self::keys(), array_map('strval', (array) $sent)));

        return $off ?: null;
    }

    /**
     * The tools a request belongs to, empty when it is none of theirs.
     *
     * @return array<int, string>
     */
    public static function forRequest(string $routeName, Request $request): array
    {
        $isPage = $request->isMethodSafe() && ! $request->wantsJson() && ! $request->boolean('json');
        $hit = [];
        foreach (self::all() as $key => [, , , $routes, $pages]) {
            foreach ($routes as $pattern) {
                [$pattern, $param] = array_pad(explode('@', $pattern, 2), 2, null);
                if (Str::is($pattern, $routeName) && ($param === null || (string) $request->route('tool') === $param)) {
                    $hit[] = $key;
                    continue 2;
                }
            }
            if ($isPage && in_array($routeName, $pages, true)) {
                $hit[] = $key;
            }
        }

        return $hit;
    }
}
