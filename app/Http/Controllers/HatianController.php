<?php

namespace App\Http\Controllers;

use App\Support\Hatian;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Hatian Calculator (2026-10-26, the owner): the harvest shared fairly between
 * the owner and the farm manager, the manager's share counted from the gross
 * and the owner's home food out of the owner's own share. The page counts as
 * you type (App\Support\Hatian is the same arithmetic); a calculation the
 * farmer saves is counted again here and kept on the analyses shelf
 * (as_plant_analyses, kind 'hatian', no credits), listed on the Saved tab.
 */
class HatianController extends Controller
{
    public const KIND = 'hatian';

    public function page()
    {
        return view('hatian.index', ['modes' => Hatian::MODES, 'quick' => Hatian::QUICK, 'defaults' => Hatian::DEFAULTS]);
    }

    /** Save one: counted again here, so what is kept is what the rule says. */
    public function save(Request $request)
    {
        $c = Hatian::clean($request->all());
        if (! $c) {
            return $this->json(false, 'Fill in the sacks harvested and the farm manager\'s share.', [], 422);
        }
        $res = Hatian::compute($c);
        if ($why = Hatian::problem($c, $res)) {
            return $this->json(false, $why, [], 422);
        }
        $title = mb_substr(Hatian::title($c, $res), 0, 190);
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

        return $this->json(true, 'Moved to the Trash. Restore it from the Trash for 30 days.', $this->shelf(1));
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
                $at = Carbon::parse($r->created_at)->timezone('Asia/Manila');

                return ['id' => (int) $r->id, 'title' => (string) $r->title,
                    'manager' => $rep['manager']['sacks'] ?? null, 'home' => $rep['home']['sacks'] ?? null, 'left' => $rep['left']['sacks'] ?? null,
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
