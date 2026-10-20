<?php

namespace App\Http\Controllers;

use App\Support\FieldProfile;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Saved field profiles (2026-10-15): a lot's soil, water and history,
 * answered once (App\Support\FieldProfile) and offered again by every
 * analysis through the shared partial partials/field-profile. The farm
 * owner's (WorkerContext::effectiveOwnerId), so a worker sees the farm's.
 */
class FieldProfileController extends Controller
{
    /** The pests, diseases and other troubles a crop's last season may have had, with photos where we have them. */
    public function troubles(Request $request)
    {
        $crop = (string) $request->query('crop', 'rice');
        $rows = FieldProfile::troubles($crop, true);
        // Every crop's list (2026-10-16): the crops it can be filtered by, those with a trouble of their own.
        $crops = [];
        if ($crop === 'all') {
            $have = array_flip(array_merge(...array_map(fn ($r) => (array) ($r['c'] ?? []), $rows)));
            foreach (\App\Support\FieldCatalogue::crops() as $k => $c) {
                if (isset($have[$k])) {
                    $crops[] = [$k, $c[1], $c[2], $c[0]];
                }
            }
        }

        return $this->json(true, 'ok', ['crop' => $crop, 'rows' => $rows, 'crops' => $crops]);
    }

    /**
     * The weeds and grasses catalogue the weeds question opens (2026-10-21, the owner):
     * every weed with its photo, names, group and how to keep it out. Only for looking;
     * nothing is picked from it. Kept six hours (FieldBook::weeds() reads the site pages).
     */
    public function weeds()
    {
        $out = \Illuminate\Support\Facades\Cache::remember('fq-weeds:v1', 21600, function () {
            $book = \App\Support\FieldBook::weeds();
            $crops = collect(\App\Support\FieldCatalogue::crops())->map(fn ($c) => $c[1])->all();
            $keep = ['k', 'name', 'sci', 'local', 'kind', 'life', 'hint', 'stop', 'thumb', 'photo', 'alt', 'credit'];

            return [
                'rows' => array_map(fn ($e) => array_intersect_key($e, array_flip($keep))
                    + ['crops' => array_values(array_filter(array_map(fn ($c) => $crops[$c] ?? null, (array) $e['crops'])))], $book['entries']),
                'groups' => collect(\App\Support\WeedControl::GROUPS)->map(fn ($g) => [$g['label'], $g['hint']])->all(),
            ];
        });

        return $this->json(true, 'ok', $out);
    }

    /** The farm's saved profiles, the newest first. */
    public function list()
    {
        return $this->json(true, 'ok', ['rows' => FieldProfile::saved(WorkerContext::effectiveOwnerId()), 'ready' => FieldProfile::storeReady()]);
    }

    /** Save a profile: a new one, or the one already kept for the same season lot or name. */
    public function save(Request $request)
    {
        return $this->keep($request, null);
    }

    /** Update one saved profile. */
    public function update(Request $request, int $id)
    {
        return $this->keep($request, $id);
    }

    private function keep(Request $request, ?int $id)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:120', 'crop' => 'nullable|string|max:40', 'answers' => 'required|array',
            'lotId' => 'nullable|integer', 'scheduleId' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        if (! FieldProfile::storeReady()) {
            return $this->json(false, 'Saved answers are not ready yet. Please try again later.', [], 503);
        }
        $row = FieldProfile::store(WorkerContext::effectiveOwnerId(), $request->only(['name', 'crop', 'answers', 'lotId', 'scheduleId']), $id);
        if (! $row) {
            return $this->json(false, $id ? 'That saved field is not yours.' : 'There is nothing to save yet.', [], $id ? 404 : 422);
        }

        return $this->json(true, 'Saved.', ['row' => $row]);
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
