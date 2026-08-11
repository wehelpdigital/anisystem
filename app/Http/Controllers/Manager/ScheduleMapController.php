<?php

namespace App\Http\Controllers\Manager;

use App\Events\ScheduleMapLocation;
use App\Events\ScheduleMapPushed;
use App\Models\AsScheduleNote;
use App\Models\ScheduleMapObject;
use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The Collab Room map: persistent shapes the team draws over real ground,
 * plus ephemeral live positions. Shapes persist and broadcast; positions
 * only broadcast — where someone stood is not something to keep.
 */
class ScheduleMapController extends BaseScheduleController
{
    /**
     * The map as a schedule module of its own, for planning outside a call.
     * Same partial the Collab Room embeds, so the tools, the saved maps and
     * the live team drawing are the same map — not a copy of one.
     */
    public function page(Request $request)
    {
        return view('sm.maps', ['schedule' => $this->scheduleFromRequest($request, 'id')]);
    }

    public function objects(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        if (! ScheduleTeam::canAccess($schedule, (int) Auth::id())) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $rows = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->orderBy('id')
            ->limit(2000)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['objects' => $rows->map(fn ($o) => $o->shaped())->all()],
        ]);
    }

    public function push(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'kind' => 'required|in:pen,line,path,rect,area,text,arrow',
            'color' => 'nullable|string|max:16',
            'width' => 'nullable|integer|min:1|max:20',
            'points' => 'required|array|min:1|max:2000',
            'points.*' => 'array|size:2',
            'label' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = ScheduleMapObject::create([
            'scheduleId' => $schedule->id,
            'userId' => $meId,
            'kind' => $request->input('kind'),
            'color' => $request->input('color'),
            'width' => (int) $request->input('width', 3),
            'points' => json_encode($request->input('points')),
            'label' => $request->input('label'),
            'deleteStatus' => 1,
        ]);

        $this->emit($schedule->id, ['action' => 'add', 'object' => $object->shaped(), 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'data' => ['object' => $object->shaped()]]);
    }

    /** Move or reshape an existing object; the team sees it land live. */
    public function update(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'points' => 'required|array|min:1|max:2000',
            'points.*' => 'array|size:2',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape no longer exists.', 404);
        }

        $object->update(['points' => json_encode($request->input('points'))]);
        $this->emit($schedule->id, ['action' => 'update', 'object' => $object->fresh()->shaped(), 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'data' => ['object' => $object->fresh()->shaped()]]);
    }

    public function remove(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $object = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape is already gone.', 404);
        }

        $object->update(['deleteStatus' => 0]);
        $this->emit($schedule->id, ['action' => 'remove', 'id' => (int) $object->id, 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }

    public function clear(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        ScheduleMapObject::active()->where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);
        $this->emit($schedule->id, ['action' => 'clear', 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => 'Map cleared for the team.']);
    }

    /**
     * Drawing-in-progress relay: the half-drawn shape under a member's finger,
     * so the room watches it grow instead of having it pop in finished.
     * Broadcast-only; `done` tells viewers to drop the ghost.
     */
    public function trace(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $me = Auth::user();
        if (! ScheduleTeam::canAccess($schedule, (int) $me->id)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'done' => 'nullable|boolean',
            'kind' => 'nullable|in:pen,line,path,rect,area,arrow',
            'color' => 'nullable|string|max:16',
            'points' => 'nullable|array|max:200',
            'points.*' => 'array|size:2',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Bad trace.', 422);
        }

        try {
            broadcast(new \App\Events\ScheduleMapTrace($schedule->id, [
                'userId' => (int) $me->id,
                'name' => (string) \Illuminate\Support\Str::of($me->full_name)->explode(' ')->first(),
                'done' => (bool) $request->boolean('done'),
                'kind' => $request->input('kind'),
                'color' => $request->input('color'),
                'points' => $request->input('points', []),
            ]));
        } catch (\Throwable $e) {
            // best-effort — a lost frame just makes the ghost jump
        }

        return response()->json(['success' => true]);
    }

    /**
     * Plain map imagery for the given viewport, streamed from our own origin.
     * The client draws the shapes, points and measurements over it on a canvas
     * — which is the only way the saved picture can carry the same labels the
     * screen shows, since Static Maps can letter a marker but not write
     * "12.34 m", and a canvas that has loaded a googleapis.com image directly
     * is tainted and cannot be exported at all.
     */
    public function basemap(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        if (! ScheduleTeam::canAccess($schedule, (int) Auth::id())) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'zoom' => 'required|numeric|between:1,22',
            'maptype' => 'nullable|in:roadmap,hybrid',
            'size' => 'nullable|integer|min:256|max:640',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $key = (string) config('services.google_maps.key');
        if ($key === '') {
            return $this->jsonFail('No map key configured.', 404);
        }

        $size = (int) $request->input('size', 640);
        $url = 'https://maps.googleapis.com/maps/api/staticmap'
            . '?size=' . $size . 'x' . $size
            . '&scale=2'
            . '&maptype=' . ($request->input('maptype') === 'roadmap' ? 'roadmap' : 'hybrid')
            . '&center=' . round((float) $request->input('lat'), 6) . ',' . round((float) $request->input('lng'), 6)
            . '&zoom=' . (int) round((float) $request->input('zoom'))
            . '&key=' . rawurlencode($key);

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(20)->get($url);
            if (! $res->ok() || ! str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                return $this->jsonFail('Could not fetch the map imagery.', 502);
            }

            return response($res->body(), 200, [
                'Content-Type' => $res->header('Content-Type'),
                'Cache-Control' => 'private, max-age=120',
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFail('Could not fetch the map imagery.', 502);
        }
    }

    /** Named map snapshots the team saved, newest first. */
    public function saves(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        if (! ScheduleTeam::canAccess($schedule, (int) Auth::id())) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $rows = \App\Models\ScheduleMapSave::active()
            ->where('scheduleId', $schedule->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();
        $users = \App\Models\User::whereIn('id', $rows->pluck('userId')->unique())->get()->keyBy('id');

        return response()->json([
            'success' => true,
            'data' => ['saves' => $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'title' => $r->title,
                'by' => (string) \Illuminate\Support\Str::of(optional($users->get($r->userId))->full_name ?? 'Someone')->explode(' ')->first(),
                'when' => $r->created_at?->timezone('Asia/Manila')->format('M j, Y g:ia'),
                'count' => count(json_decode((string) $r->objects, true) ?: []),
            ])->all()],
        ]);
    }

    /**
     * Save the whole map. mode=map keeps a reopenable snapshot AND files a
     * picture note in the notebook; mode=image files only the picture. The
     * picture comes from the Static Maps API with every shape drawn on it —
     * the live WebGL map cannot be screenshotted from JS.
     */
    public function saveMap(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:map,image',
            // A PNG data URL the client composed: imagery, shapes, points and
            // every measurement label, exactly as the screen shows them.
            'image' => 'nullable|string',
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:2000',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'zoom' => 'nullable|numeric|between:1,22',
            'maptype' => 'nullable|in:roadmap,hybrid',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $objects = ScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->orderBy('id')
            ->limit(2000)
            ->get()
            ->map(fn ($o) => $o->shaped())
            ->all();
        if (empty($objects)) {
            return $this->jsonFail('The map has no shapes to save yet.', 422);
        }

        $mode = $request->input('mode');
        $title = trim((string) $request->input('title')) ?: 'Team map';
        $description = trim((string) $request->input('description'));

        // Best-effort picture; the reopenable snapshot never depends on it.
        $media = [];

        // Preferred: the canvas the client composed, which carries the points
        // and measurement labels. Static Maps can draw the shapes but cannot
        // write their sizes, so that path is the fallback, not the goal.
        $binary = $this->decodeDataUrlImage((string) $request->input('image'));
        if ($binary !== null) {
            $path = 'schedule-notes/' . $schedule->id . '/map-' . \Illuminate\Support\Str::random(20) . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $binary);
            $media[] = ['type' => 'map', 'path' => $path, 'poster' => null];
        }

        $url = $media ? null : $this->staticMapUrl(
            $objects,
            $request->input('lat'),
            $request->input('lng'),
            $request->input('zoom'),
            $request->input('maptype', 'hybrid')
        );
        if ($url !== null) {
            try {
                $img = \Illuminate\Support\Facades\Http::timeout(20)->get($url);
                if ($img->ok() && str_starts_with((string) $img->header('Content-Type'), 'image/')) {
                    $path = 'schedule-notes/' . $schedule->id . '/map-' . \Illuminate\Support\Str::random(20) . '.png';
                    \Illuminate\Support\Facades\Storage::disk('public')->put($path, $img->body());
                    $media[] = ['type' => 'map', 'path' => $path, 'poster' => null];
                }
            } catch (\Throwable $e) {
                // fall through — picture is optional for mode=map
            }
        }

        if ($mode === 'image' && empty($media)) {
            return $this->jsonFail('Could not take the map picture — the Static Maps API may not be enabled for this key.', 422);
        }

        $bodyText = $description !== '' ? $description : null;
        if ($mode === 'map') {
            $bodyText = trim(($description !== '' ? $description . "\n\n" : '')
                . 'Saved team map — reopen it from the Collab Room map tools.');
        }
        $note = AsScheduleNote::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => $meId,
            'title' => mb_substr($title, 0, 180),
            'body' => $bodyText !== null
                ? \App\Support\HtmlSanitizer::rich('<p>' . nl2br(e($bodyText)) . '</p>')
                : null,
            'media' => $media,
            'deleteStatus' => 1,
        ]);

        if ($mode === 'map') {
            \App\Models\ScheduleMapSave::create([
                'scheduleId' => $schedule->id,
                'userId' => $meId,
                'title' => mb_substr($title, 0, 180),
                'objects' => json_encode(array_map(fn ($o) => [
                    'kind' => $o['kind'], 'color' => $o['color'], 'width' => $o['width'],
                    'points' => $o['points'], 'label' => $o['label'],
                ], $objects)),
                'noteId' => $note->id,
                'deleteStatus' => 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $mode === 'map'
                ? 'Map saved — reopen it any time from the tools' . (empty($media) ? ' (no picture: Static Maps API unavailable).' : ', picture filed in the notebook.')
                : 'Map picture filed in the schedule notebook.',
        ]);
    }

    /** Replace the live team map with a saved snapshot, for everyone. */
    public function loadSave(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $save = \App\Models\ScheduleMapSave::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $save) {
            return $this->jsonFail('That saved map no longer exists.', 404);
        }

        $objects = json_decode((string) $save->objects, true) ?: [];
        ScheduleMapObject::active()->where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);
        foreach (array_slice($objects, 0, 2000) as $o) {
            if (! is_array($o['points'] ?? null) || empty($o['points'])) {
                continue;
            }
            ScheduleMapObject::create([
                'scheduleId' => $schedule->id,
                'userId' => $meId,
                'kind' => $o['kind'] ?? 'pen',
                'color' => $o['color'] ?? null,
                'width' => (int) ($o['width'] ?? 3),
                'points' => json_encode($o['points']),
                'label' => $o['label'] ?? null,
                'deleteStatus' => 1,
            ]);
        }

        // One event; every client refetches rather than replaying a giant diff.
        $this->emit($schedule->id, ['action' => 'reload', 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => 'Map loaded for the team.']);
    }

    /**
     * A PNG/JPEG data URL from the client's canvas → raw bytes, or null when
     * it is absent or not an image we recognise. Size-capped: this arrives in
     * a normal form post, not an upload.
     */
    private function decodeDataUrlImage(string $dataUrl): ?string
    {
        if ($dataUrl === '' || strlen($dataUrl) > 12_000_000) {
            return null;
        }
        if (! preg_match('~^data:image/(png|jpe?g);base64,~i', $dataUrl, $m)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen($m[0])), true);
        if ($binary === false || strlen($binary) < 100) {
            return null;
        }
        // Trust the bytes, not the prefix.
        $info = @getimagesizefromstring($binary);
        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return null;
        }

        return $binary;
    }

    /**
     * A Static Maps URL with the team's shapes drawn on. Auto-fits to the
     * shapes when any are drawable; stops adding paths near the URL length
     * cap rather than producing a request Google will reject.
     */
    private function staticMapUrl(array $objects, $lat, $lng, $zoom, ?string $maptype): ?string
    {
        $key = (string) config('services.google_maps.key');
        if ($key === '') {
            return null;
        }

        $base = 'https://maps.googleapis.com/maps/api/staticmap?size=640x640&scale=2'
            . '&maptype=' . ($maptype === 'roadmap' ? 'roadmap' : 'hybrid')
            . '&key=' . rawurlencode($key);
        $url = $base;
        $drawn = 0;

        foreach ($objects as $o) {
            $pts = $o['points'];
            $color = ltrim((string) ($o['color'] ?: '#f5c518'), '#');
            if (! preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
                $color = 'f5c518';
            }

            if ($o['kind'] === 'text') {
                $piece = '&markers=' . rawurlencode('size:small|color:0x' . $color . '|'
                    . round($pts[0][0], 6) . ',' . round($pts[0][1], 6));
            } else {
                $keep = $pts;
                if ($o['kind'] === 'rect' && count($pts) >= 2) {
                    [$sw, $ne] = [$pts[0], $pts[1]];
                    $keep = [[$sw[0], $sw[1]], [$sw[0], $ne[1]], [$ne[0], $ne[1]], [$ne[0], $sw[1]], [$sw[0], $sw[1]]];
                } else {
                    $step = max(1, (int) ceil(count($pts) / 50));
                    $keep = [];
                    foreach ($pts as $i => $p) {
                        if ($i % $step === 0) {
                            $keep[] = $p;
                        }
                    }
                    if (end($pts) !== end($keep)) {
                        $keep[] = end($pts);
                    }
                    if ($o['kind'] === 'area' && count($keep) > 2) {
                        $keep[] = $keep[0];
                    }
                }
                $enc = 'color:0x' . $color . 'ff|weight:' . max(2, (int) ($o['width'] ?: 3));
                if ($o['kind'] === 'rect' || $o['kind'] === 'area') {
                    $enc .= '|fillcolor:0x' . $color . '33';
                }
                foreach ($keep as $p) {
                    $enc .= '|' . round($p[0], 6) . ',' . round($p[1], 6);
                }
                $piece = '&path=' . rawurlencode($enc);
            }

            if (strlen($url) + strlen($piece) > 7500) {
                break;
            }
            $url .= $piece;
            $drawn++;
        }

        if ($drawn === 0) {
            // Nothing framed the picture — need an explicit viewport.
            if ($lat === null || $lng === null || $zoom === null) {
                return null;
            }
            $url .= '&center=' . round((float) $lat, 6) . ',' . round((float) $lng, 6) . '&zoom=' . (int) round((float) $zoom);
        }

        return $url;
    }

    /** Live GPS position — broadcast to the room, never stored. */
    public function location(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $me = Auth::user();
        if (! ScheduleTeam::canAccess($schedule, (int) $me->id)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'acc' => 'nullable|numeric|min:0|max:100000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Bad position.', 422);
        }

        try {
            broadcast(new ScheduleMapLocation($schedule->id, [
                'userId' => (int) $me->id,
                'name' => (string) \Illuminate\Support\Str::of($me->full_name)->explode(' ')->first(),
                'lat' => (float) $request->input('lat'),
                'lng' => (float) $request->input('lng'),
                'acc' => (float) $request->input('acc', 0),
                'at' => now('Asia/Manila')->timestamp,
            ]));
        } catch (\Throwable $e) {
            // best-effort — a missed beacon just means a slightly staler dot
        }

        return response()->json(['success' => true]);
    }

    private function emit(int $scheduleId, array $payload): void
    {
        try {
            broadcast(new ScheduleMapPushed($scheduleId, $payload));
        } catch (\Throwable $e) {
            // best-effort — the poll fallback reconciles
        }
    }
}
