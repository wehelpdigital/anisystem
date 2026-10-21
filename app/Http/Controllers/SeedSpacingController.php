<?php

namespace App\Http\Controllers;

use App\Support\SeedPenalty;
use App\Support\SeedSpacing;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Seed Count and Distance Calculator (2026-10-19, the owner): the rows and
 * the distance between plants against the seeds, the final stand and the
 * yield, either way round. The page counts as you type (App\Support\
 * SeedSpacing is the same arithmetic); a calculation the farmer saves is
 * counted again here and kept on the analyses shelf (as_plant_analyses, kind
 * 'seedcalc', no credits), listed on the Saved tab, to open again or delete.
 */
class SeedSpacingController extends Controller
{
    public const KIND = 'seedcalc';

    public function page()
    {
        return view('seed-spacing.index', ['boot' => SeedSpacing::boot()]);
    }

    /**
     * A variety's own yield parts, for the page's twin of the count (2026-10-19,
     * the owner): what App\Support\VarietyYield keeps, or null while the shelf
     * does not know it (or does not exist yet). No Anee, no lookup that costs.
     */
    public function variety(Request $request)
    {
        $crop = (string) $request->query('crop', '');
        $name = mb_substr(trim((string) $request->query('name', '')), 0, 120);
        $vy = array_key_exists($crop, SeedSpacing::CROPS) && $name !== '' ? SeedSpacing::varietyYield(['crop' => $crop, 'variety' => $name]) : null;

        $ok = array_key_exists($crop, SeedSpacing::CROPS) && $name !== '';

        return $this->json(true, 'ok', ['yield' => $vy, 'trial' => $ok ? SeedSpacing::trial($crop, $name, $vy) : null]);
    }

    /**
     * The lot and the season for the page (2026-10-19, the owner): the lot's
     * facts and, with a planting month, twenty years of the same weeks, the
     * ENSO outlook and the kept forecast (SeedPenalty::context, the shared
     * stores only). The page counts the penalties from these, as save() does.
     */
    public function context(Request $request)
    {
        $crop = (string) $request->query('crop', '');
        if (! array_key_exists($crop, SeedSpacing::CROPS)) {
            return $this->json(false, 'Pick a crop first.', [], 422);
        }
        $c = SeedSpacing::read(['crop' => $crop, 'lotId' => $request->query('lotId'), 'month' => $request->query('month'), 'day' => $request->query('day')]);

        return $this->json(true, 'ok', SeedPenalty::context(WorkerContext::effectiveOwnerId(), $c['lotId'], $crop, $c['month'], $c['day']));
    }

    /**
     * The seeds a spacing takes, for the modules that ask how much is planted
     * (2026-10-21, the owner: the Crop Protocol and NPK Plus, "By spacing"):
     * App\Support\PlantAmount::count in words, and that count as NPK Plus's
     * seed answer for the lot when `npk` names its crop. No Anee, no credits.
     */
    public function count(Request $request)
    {
        $q = fn ($k) => is_numeric($request->query($k)) ? (float) $request->query($k) : null;
        $sp = ['row' => $q('row'), 'plant' => $q('plant'), 'perHill' => $q('perHill') ? max(1, min(20, (int) $q('perHill'))) : null];
        $germ = $q('germ') !== null ? max(1.0, min(100.0, $q('germ'))) : null;
        $area = max(0.0, min(100000.0, (float) $q('area')));
        $n = \App\Support\PlantAmount::count((string) $request->query('crop', ''), (string) $request->query('method', ''), $sp, $germ, $area,
            mb_substr(trim((string) $request->query('variety', '')), 0, 120));
        if (! $n) {
            return $this->json(false, 'Fill in the rows and the distance between plants.', [], 422);
        }
        $npk = (string) $request->query('npk', '');

        return $this->json(true, 'ok', ['count' => $n, 'seed' => $npk !== '' ? \App\Support\PlantAmount::asSeed($n, $npk, $area) : null]);
    }

    /** Save one: counted again here, so what is kept is what the rule says. */
    public function save(Request $request)
    {
        $c = SeedSpacing::clean($request->all());
        if (! $c) {
            return $this->json(false, SeedSpacing::missing(SeedSpacing::read($request->all())) ?: 'Fill in the spacing first.', [], 422);
        }
        // The lot and the season counted again from the shelves, as the page counted them.
        $ctx = $c['lotId'] || $c['month'] ? SeedPenalty::context(WorkerContext::effectiveOwnerId(), $c['lotId'], $c['crop'], $c['month'], $c['day']) : null;
        if ($ctx && ! $ctx['lot']) {
            $c['lotId'] = null;
        }
        $res = SeedSpacing::compute($c, null, $ctx);
        $res['lot'] = $ctx['lot'] ?? null;
        $res['trial'] = SeedSpacing::trial($c['crop'], $c['variety'], SeedSpacing::varietyYield($c));
        $title = mb_substr(SeedSpacing::title($c, $res, $ctx['lot']['name'] ?? null), 0, 190);
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => $title,
            'params' => json_encode($c), 'report' => json_encode($res), 'credits' => 0, 'status' => 'ready', 'deleteStatus' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->json(true, 'Saved.', ['id' => $id, 'title' => $title] + $this->shelf(1));
    }

    /** One saved calculation, its inputs to put back on the page. */
    public function one(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)
            ->where('status', 'ready')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That calculation is gone.', [], 404);
        }

        return $this->json(true, 'ok', ['id' => (int) $r->id, 'title' => $r->title, 'params' => json_decode((string) $r->params, true),
            'report' => json_decode((string) $r->report, true)]);
    }

    public function list(Request $request)
    {
        return $this->json(true, 'ok', $this->shelf(max(1, (int) $request->query('page', 1))));
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Deleted.', $this->shelf(1));
    }

    /** The farmer's calculations, newest first, twelve to a page. */
    private function shelf(int $page): array
    {
        $per = 12;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->skip(($page - 1) * $per)->take($per + 1)->get(['id', 'title', 'report', 'params', 'created_at']);

        return [
            'history' => $rows->take($per)->map(function ($r) {
                $rep = json_decode((string) $r->report, true) ?: [];
                $p = json_decode((string) $r->params, true) ?: [];
                $at = Carbon::parse($r->created_at)->timezone('Asia/Manila');

                return ['id' => (int) $r->id, 'title' => (string) $r->title, 'status' => $rep['status'] ?? 'good', 'mode' => $p['mode'] ?? 'seeds',
                    'crop' => $p['crop'] ?? null, 'standHa' => $rep['standHa'] ?? null,
                    'day' => $at->format('j'), 'month' => $at->format('M'), 'at' => $at->format('M j, Y g:i A')];
            })->values()->all(),
            'hasMore' => $rows->count() > $per,
            'page' => $page,
        ];
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
